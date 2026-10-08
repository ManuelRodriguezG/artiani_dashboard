<?php

class DistribucionNotificacionesApi extends CRUD {

  private $tablaNotificaciones = "erp_distribucion_notificaciones";
  private $tablaEnvios = "erp_distribucion_notificacion_envios";

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: devolver contadores seguros de avisos para el portal externo Distribucion.
   * Impacto: Portal Distribucion; alimenta la barra superior sin exponer avisos de otros clientes.
   * Contrato: GET autenticado por token externo; el cliente se resuelve desde contexto, no desde request.
   */
  public function resumen($contexto = array()) {
    $validacion = $this->validarCliente($contexto);
    if ($validacion) { return $validacion; }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(false, "warning", "Notificaciones Distribucion pendientes de esquema", array(
        "configurado" => false,
        "no_leidas" => 0,
        "pendientes" => 0,
        "total" => 0
      )) + array("no_leidas" => 0, "pendientes" => 0, "total" => 0);
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT
          COUNT(*) total,
          SUM(CASE WHEN leida=0 THEN 1 ELSE 0 END) no_leidas,
          SUM(CASE WHEN requiere_accion=1 AND estatus IN ('pendiente','en_revision') THEN 1 ELSE 0 END) pendientes
        FROM {$this->tablaNotificaciones}
        WHERE id_cliente_distribucion=:cliente
          AND estatus IN ('pendiente','en_revision','resuelta','informativa')");
      $stmt->execute(array(":cliente" => $idCliente));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      return array(
        "error" => false,
        "tipo" => "success",
        "mensaje" => "Resumen de notificaciones consultado",
        "no_leidas" => intval($this->valor($fila, "no_leidas", 0)),
        "pendientes" => intval($this->valor($fila, "pendientes", 0)),
        "total" => intval($this->valor($fila, "total", 0)),
        "depurar" => array(
          "configurado" => true,
          "no_leidas" => intval($this->valor($fila, "no_leidas", 0)),
          "pendientes" => intval($this->valor($fila, "pendientes", 0)),
          "total" => intval($this->valor($fila, "total", 0))
        )
      );
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar resumen de notificaciones", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: listar notificaciones comerciales propias de un cliente Distribucion.
   * Impacto: Portal Distribucion; muestra avisos accionables sin datos internos del ERP.
   * Contrato: filtros estatus/tipo/pagina/limite; siempre acota por cliente autenticado.
   */
  public function listar($filtros = array(), $contexto = array()) {
    $validacion = $this->validarCliente($contexto);
    if ($validacion) { return $validacion; }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(false, "warning", "Notificaciones Distribucion pendientes de esquema", array(
        "configurado" => false,
        "items" => array(),
        "paginacion" => array("pagina" => 1, "limite" => 20, "total" => 0)
      )) + array("items" => array(), "paginacion" => array("pagina" => 1, "limite" => 20, "total" => 0));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(1, min(50, intval($this->valor($filtros, "limite", 20))));
      $offset = ($pagina - 1) * $limite;
      $where = array("id_cliente_distribucion=:cliente");
      $params = array(":cliente" => $idCliente);
      $estatus = trim((string) $this->valor($filtros, "estatus", ""));
      if ($estatus !== "") {
        if ($estatus === "no_leida") {
          $where[] = "leida=0";
        } elseif (in_array($estatus, array("pendiente", "en_revision", "resuelta", "informativa", "cancelada"), true)) {
          $where[] = "estatus=:estatus";
          $params[":estatus"] = $estatus;
        }
      } else {
        $where[] = "estatus IN ('pendiente','en_revision','resuelta','informativa')";
      }
      $tipo = trim((string) $this->valor($filtros, "tipo", ""));
      if ($tipo !== "") {
        $where[] = "tipo=:tipo";
        $params[":tipo"] = $tipo;
      }
      $sqlWhere = implode(" AND ", $where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM {$this->tablaNotificaciones} WHERE " . $sqlWhere);
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());
      $stmt = $db->prepare("SELECT id_notificacion, tipo, titulo, mensaje, folio_referencia, url_accion,
          requiere_accion, canales_disponibles_json, leida, estatus, fecha_registro
        FROM {$this->tablaNotificaciones}
        WHERE " . $sqlWhere . "
        ORDER BY leida ASC, requiere_accion DESC, id_notificacion DESC
        LIMIT " . intval($limite) . " OFFSET " . intval($offset));
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearNotificacion($fila);
      }
      $paginacion = array("pagina" => $pagina, "limite" => $limite, "total" => $total);
      return array(
        "error" => false,
        "tipo" => "success",
        "mensaje" => "Notificaciones consultadas",
        "items" => $items,
        "paginacion" => $paginacion,
        "depurar" => array("configurado" => true, "items" => $items, "paginacion" => $paginacion)
      );
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar notificaciones", array("detalle" => "error_controlado", "items" => array()));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: marcar como leida una notificacion propia del cliente.
   * Impacto: Portal Distribucion; mantiene privacidad entre clientes y evita auditoria duplicada.
   * Contrato: POST JSON con id_notificacion; id_cliente se resuelve desde token.
   */
  public function marcarLeida($datos = array(), $contexto = array()) {
    $validacion = $this->validarCliente($contexto);
    if ($validacion) { return $validacion; }
    $idNotificacion = intval($this->valor($datos, "id_notificacion", 0));
    if ($idNotificacion <= 0) {
      return $this->respuesta(true, "warning", "Notificacion requerida");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Notificaciones Distribucion pendientes de esquema", array("configurado" => false));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT id_notificacion, leida FROM {$this->tablaNotificaciones} WHERE id_notificacion=:id AND id_cliente_distribucion=:cliente LIMIT 1");
      $stmt->execute(array(":id" => $idNotificacion, ":cliente" => $idCliente));
      $notificacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$notificacion) {
        return $this->respuesta(true, "warning", "Notificacion no encontrada");
      }
      if (intval($this->valor($notificacion, "leida", 0)) !== 1) {
        $db->prepare("UPDATE {$this->tablaNotificaciones}
          SET leida=1, fecha_lectura=NOW(), fecha_actualizacion=NOW()
          WHERE id_notificacion=:id AND id_cliente_distribucion=:cliente")
          ->execute(array(":id" => $idNotificacion, ":cliente" => $idCliente));
        $this->registrarAuditoria($db, "notificacion", $idNotificacion, "marcar_leida", "ok", "Notificacion Distribucion marcada como leida", array(), null, $idCliente);
      }
      return $this->respuesta(false, "success", "Notificacion marcada como leida", array("id_notificacion" => $idNotificacion));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo marcar la notificacion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: registrar intento de envio o reenvio por canal autorizado.
   * Impacto: Portal Distribucion; prepara correo/WhatsApp sin exponer contactos internos ni enviar si falta proveedor.
   * Contrato: valida propiedad, canal permitido, contacto y rate limit basico.
   */
  public function enviar($datos = array(), $contexto = array()) {
    $validacion = $this->validarCliente($contexto);
    if ($validacion) { return $validacion; }
    $idNotificacion = intval($this->valor($datos, "id_notificacion", 0));
    $canal = strtolower(trim((string) $this->valor($datos, "canal", "")));
    if ($idNotificacion <= 0 || !in_array($canal, array("correo", "whatsapp", "sms", "llamada"), true)) {
      return $this->respuesta(true, "warning", "Notificacion o canal no valido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Notificaciones Distribucion pendientes de esquema", array("configurado" => false));
    }
    try {
      $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
      $stmt = $db->prepare("SELECT n.*, c.correo, c.whatsapp, c.telefono
        FROM {$this->tablaNotificaciones} n
        INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=n.id_cliente_distribucion
        WHERE n.id_notificacion=:id AND n.id_cliente_distribucion=:cliente
        LIMIT 1");
      $stmt->execute(array(":id" => $idNotificacion, ":cliente" => $idCliente));
      $notificacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$notificacion) {
        return $this->respuesta(true, "warning", "Notificacion no encontrada");
      }
      $canales = $this->jsonArray($this->valor($notificacion, "canales_disponibles_json", ""));
      if (!in_array($canal, $canales, true)) {
        return $this->respuesta(true, "warning", "Este canal no esta disponible para la notificacion.");
      }
      $destino = $this->destinoCanal($notificacion, $canal);
      if ($destino === "") {
        return $this->respuesta(true, "warning", $this->mensajeSinDestino($canal));
      }
      $stmtIntentos = $db->prepare("SELECT COUNT(*) FROM {$this->tablaEnvios}
        WHERE id_notificacion=:id AND id_cliente_distribucion=:cliente AND canal=:canal AND fecha_envio >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
      $stmtIntentos->execute(array(":id" => $idNotificacion, ":cliente" => $idCliente, ":canal" => $canal));
      if (intval($stmtIntentos->fetchColumn()) >= 3) {
        $this->registrarEnvio($db, $notificacion, $canal, $destino, "bloqueado_rate_limit", "Limite de reenvios por hora alcanzado");
        return $this->respuesta(true, "warning", "Espera un momento antes de solicitar otro reenvio.");
      }
      $mensaje = $this->mensajeCanalNoConfigurado($canal);
      $this->registrarEnvio($db, $notificacion, $canal, $destino, "no_configurado", $mensaje);
      return array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => $mensaje,
        "canal" => $canal,
        "estatus_envio" => "no_configurado",
        "configurado" => false,
        "depurar" => array("configurado" => false, "canal" => $canal, "estatus_envio" => "no_configurado")
      );
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo procesar el envio", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: crear/actualizar notificaciones comerciales de Distribucion desde modelos de dominio.
   * Impacto: Pedidos, cotizaciones y cuenta Distribucion; no bloquea el flujo si el esquema aun no existe.
   * Contrato: acepta id_cliente_distribucion, tipo, titulo, mensaje, folio, url, requiere_accion, canales y metadata.
   */
  public function crearNotificacion($datos = array()) {
    try {
      $id = $this->crearNotificacionEnConexion($this->getConexion(), $datos);
      return $this->respuesta(false, "success", "Notificacion Distribucion guardada", array("id_notificacion" => $id));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo guardar notificacion Distribucion", array("detalle" => "error_controlado"));
    }
  }

  public function crearNotificacionEnConexion($db, $datos = array()) {
    if (!$this->esquemaOperativo($db)) { return 0; }
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    $tipo = $this->texto($this->valor($datos, "tipo", ""), 80);
    if ($idCliente <= 0 || $tipo === "") { return 0; }
    $titulo = $this->texto($this->valor($datos, "titulo", "Aviso Distribucion"), 180);
    $mensaje = $this->texto($this->valor($datos, "mensaje", ""), 1000);
    $folio = $this->texto($this->valor($datos, "folio_referencia", ""), 80);
    $url = $this->urlAccion($this->valor($datos, "url_accion", "/notificaciones"));
    $requiereAccion = intval($this->valor($datos, "requiere_accion", 0)) === 1 ? 1 : 0;
    $canales = $this->canalesNormalizados($this->valor($datos, "canales_disponibles", array("correo")));
    $metadata = $this->valor($datos, "metadata", array());
    if (!is_array($metadata)) { $metadata = array(); }
    $huella = $this->texto($this->valor($metadata, "huella", ""), 180);
    if ($huella === "") {
      $huella = $tipo . "|" . $idCliente . "|" . $folio . "|" . $this->texto($this->valor($datos, "id_entidad", ""), 80);
      $metadata["huella"] = $huella;
    }
    $stmt = $db->prepare("SELECT id_notificacion FROM {$this->tablaNotificaciones}
      WHERE id_cliente_distribucion=:cliente AND tipo=:tipo AND metadata_json LIKE :huella
      ORDER BY id_notificacion DESC LIMIT 1");
    $stmt->execute(array(":cliente" => $idCliente, ":tipo" => $tipo, ":huella" => '%"huella":"' . $huella . '"%'));
    $id = intval($stmt->fetchColumn());
    $params = array(
      ":cliente" => $idCliente,
      ":tipo" => $tipo,
      ":titulo" => $titulo,
      ":mensaje" => $mensaje,
      ":folio" => $folio,
      ":url" => $url,
      ":requiere" => $requiereAccion,
      ":canales" => json_encode($canales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      ":metadata" => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      ":estatus" => $this->estatusNormalizado($this->valor($datos, "estatus", $requiereAccion ? "pendiente" : "informativa"))
    );
    if ($id > 0) {
      $params[":id"] = $id;
      $db->prepare("UPDATE {$this->tablaNotificaciones}
        SET titulo=:titulo, mensaje=:mensaje, folio_referencia=:folio, url_accion=:url,
            requiere_accion=:requiere, canales_disponibles_json=:canales, metadata_json=:metadata,
            estatus=:estatus, fecha_actualizacion=NOW()
        WHERE id_notificacion=:id")
        ->execute($params);
      return $id;
    }
    $db->prepare("INSERT INTO {$this->tablaNotificaciones}
      (id_cliente_distribucion, tipo, titulo, mensaje, folio_referencia, url_accion, requiere_accion,
       canales_disponibles_json, metadata_json, leida, estatus, fecha_registro, fecha_actualizacion)
      VALUES (:cliente, :tipo, :titulo, :mensaje, :folio, :url, :requiere,
       :canales, :metadata, 0, :estatus, NOW(), NOW())")
      ->execute($params);
    return intval($db->lastInsertId());
  }

  private function validarCliente($contexto) {
    if (empty($contexto["autenticado"]) || intval($this->valor($contexto, "id_cliente_distribucion", 0)) <= 0) {
      return $this->respuesta(true, "warning", "Sesion Distribucion requerida", array("requiere_autenticacion" => true));
    }
    return null;
  }

  private function esquemaOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_distribucion_clientes")
      && $this->tablaExiste($db, $this->tablaNotificaciones)
      && $this->tablaExiste($db, $this->tablaEnvios)
      && $this->tablaExiste($db, "erp_distribucion_auditoria");
  }

  private function formatearNotificacion($fila) {
    return array(
      "id_notificacion" => intval($this->valor($fila, "id_notificacion", 0)),
      "tipo" => $this->valor($fila, "tipo", ""),
      "titulo" => $this->valor($fila, "titulo", ""),
      "mensaje" => $this->valor($fila, "mensaje", ""),
      "folio_referencia" => $this->valor($fila, "folio_referencia", ""),
      "url_accion" => $this->urlAccion($this->valor($fila, "url_accion", "")),
      "leida" => intval($this->valor($fila, "leida", 0)) === 1,
      "requiere_accion" => intval($this->valor($fila, "requiere_accion", 0)) === 1,
      "estatus" => $this->valor($fila, "estatus", "pendiente"),
      "canales_disponibles" => $this->jsonArray($this->valor($fila, "canales_disponibles_json", "[]")),
      "fecha_registro" => $this->valor($fila, "fecha_registro", "")
    );
  }

  private function registrarEnvio($db, $notificacion, $canal, $destino, $estatus, $mensajeError = "") {
    $idNotificacion = intval($this->valor($notificacion, "id_notificacion", 0));
    $idCliente = intval($this->valor($notificacion, "id_cliente_distribucion", 0));
    $db->prepare("INSERT INTO {$this->tablaEnvios}
      (id_notificacion, id_cliente_distribucion, canal, destino_mascarado, estatus_envio, mensaje_error, metadata_json, fecha_envio)
      VALUES (:notificacion, :cliente, :canal, :destino, :estatus, :error, :metadata, NOW())")
      ->execute(array(
        ":notificacion" => $idNotificacion,
        ":cliente" => $idCliente,
        ":canal" => $canal,
        ":destino" => $this->mascararDestino($destino, $canal),
        ":estatus" => $estatus,
        ":error" => $this->texto($mensajeError, 255),
        ":metadata" => json_encode(array("proveedor_integrado" => false), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ));
    $this->registrarAuditoria($db, "notificacion_envio", intval($db->lastInsertId()), "enviar_" . $canal, $estatus, "Intento de envio de notificacion Distribucion", array(
      "id_notificacion" => $idNotificacion,
      "canal" => $canal,
      "estatus_envio" => $estatus,
      "destino_mascarado" => $this->mascararDestino($destino, $canal)
    ), null, $idCliente);
  }

  private function registrarAuditoria($db, $entidad, $idEntidad, $accion, $resultado, $mensaje, $detalle, $idUsuario, $idCliente = null) {
    if (!$this->tablaExiste($db, "erp_distribucion_auditoria")) { return; }
    $db->prepare("INSERT INTO erp_distribucion_auditoria
      (entidad, id_entidad, accion, resultado, mensaje, detalle_json, id_usuario_erp, id_cliente_distribucion, fecha_registro)
      VALUES (:entidad, :id_entidad, :accion, :resultado, :mensaje, :detalle, :usuario, :cliente, NOW())")
      ->execute(array(
        ":entidad" => $entidad,
        ":id_entidad" => $idEntidad,
        ":accion" => $accion,
        ":resultado" => $resultado,
        ":mensaje" => $mensaje,
        ":detalle" => json_encode($detalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ":usuario" => $idUsuario,
        ":cliente" => $idCliente
      ));
  }

  private function destinoCanal($notificacion, $canal) {
    if ($canal === "correo") { return trim((string) $this->valor($notificacion, "correo", "")); }
    if ($canal === "whatsapp") { return trim((string) $this->valor($notificacion, "whatsapp", "")); }
    if ($canal === "sms" || $canal === "llamada") { return trim((string) $this->valor($notificacion, "telefono", "")); }
    return "";
  }

  private function mensajeSinDestino($canal) {
    if ($canal === "correo") { return "Este cliente no tiene correo disponible para notificaciones."; }
    if ($canal === "whatsapp") { return "Este cliente no tiene WhatsApp disponible para notificaciones."; }
    if ($canal === "sms") { return "Este cliente no tiene telefono disponible para SMS."; }
    return "Este cliente no tiene telefono disponible para llamada.";
  }

  private function mensajeCanalNoConfigurado($canal) {
    if ($canal === "correo") { return "El envio por correo estara disponible proximamente."; }
    if ($canal === "whatsapp") { return "El envio por WhatsApp estara disponible proximamente."; }
    if ($canal === "sms") { return "El envio por SMS estara disponible proximamente."; }
    return "La solicitud de llamada estara disponible proximamente.";
  }

  private function canalesNormalizados($canales) {
    if (is_string($canales)) {
      $json = json_decode($canales, true);
      $canales = is_array($json) ? $json : explode(",", $canales);
    }
    $canales = is_array($canales) ? $canales : array();
    $permitidos = array("correo", "whatsapp", "sms", "llamada");
    $salida = array();
    foreach ($canales as $canal) {
      $canal = strtolower(trim((string) $canal));
      if (in_array($canal, $permitidos, true)) { $salida[$canal] = $canal; }
    }
    return array_values($salida);
  }

  private function estatusNormalizado($estatus) {
    $estatus = trim((string) $estatus);
    return in_array($estatus, array("pendiente", "en_revision", "resuelta", "informativa", "cancelada"), true) ? $estatus : "pendiente";
  }

  private function urlAccion($url) {
    $url = trim((string) $url);
    if ($url === "") { return "/notificaciones"; }
    if (strpos($url, "http://") === 0 || strpos($url, "https://") === 0 || strpos($url, "//") === 0) {
      return "/notificaciones";
    }
    return substr($url, 0, 700);
  }

  private function mascararDestino($destino, $canal) {
    $destino = trim((string) $destino);
    if ($destino === "") { return ""; }
    if ($canal === "correo" && strpos($destino, "@") !== false) {
      list($local, $dominio) = explode("@", $destino, 2);
      return substr($local, 0, 2) . "***@" . $dominio;
    }
    $digitos = preg_replace('/\D+/', '', $destino);
    if ($digitos !== "") {
      return str_repeat("*", max(0, strlen($digitos) - 4)) . substr($digitos, -4);
    }
    return "***";
  }

  private function jsonArray($valor) {
    if (is_array($valor)) { return array_values($valor); }
    $json = json_decode((string) $valor, true);
    return is_array($json) ? array_values($json) : array();
  }

  private function tablaExiste($db, $tabla) {
    try {
      $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function texto($valor, $max) {
    return substr(trim((string) $valor), 0, intval($max));
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
