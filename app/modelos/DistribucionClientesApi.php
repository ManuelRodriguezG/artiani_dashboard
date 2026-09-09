<?php

class DistribucionClientesApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: validar contrato de solicitud de acceso comercial sin aprobar clientes automaticamente.
   * Impacto: Clientes Distribucion; prepara registro B2B externo sin asignar listas ni permisos sensibles.
   * Contrato: POST JSON; bloquea persistencia hasta contar con esquema/flujo admin autorizado.
   */
  public function registrarSolicitud($datos = array(), $contexto = array()) {
    $nombre = trim((string) $this->valor($datos, "nombre", ""));
    $empresa = trim((string) $this->valor($datos, "empresa", ""));
    $correo = trim((string) $this->valor($datos, "correo", ""));
    $telefono = trim((string) $this->valor($datos, "telefono", ""));
    $tipoInteres = trim((string) $this->valor($datos, "tipo_interes", "registrado"));
    $mensaje = trim((string) $this->valor($datos, "mensaje", ""));
    $errores = array();
    if ($nombre === "") { $errores[] = "nombre_requerido"; }
    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) { $errores[] = "correo_invalido"; }
    if (!in_array($tipoInteres, array("registrado", "revendedor", "mayorista", "distribuidor_autorizado"), true)) {
      $errores[] = "tipo_interes_invalido";
    }
    if (!empty($errores)) {
      return $this->respuesta(true, "warning", "Solicitud de acceso incompleta", array("errores" => $errores));
    }

    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_solicitudes")) {
      return $this->respuesta(true, "warning", "Contrato de registro listo; persistencia pendiente en ERP", array(
        "folio" => null,
        "estatus" => "pendiente",
        "configurado" => false,
        "no_aprueba_automaticamente" => true,
        "campos_recibidos" => array("nombre", "empresa", "correo", "telefono", "tipo_interes", "mensaje")
      ));
    }

    try {
      $folio = $this->folioSolicitud($db);
      $stmt = $db->prepare("INSERT INTO erp_distribucion_solicitudes
        (folio, nombre, empresa, correo, telefono, tipo_interes, mensaje, estatus, fecha_registro)
        VALUES (:folio, :nombre, :empresa, :correo, :telefono, :tipo_interes, :mensaje, 'pendiente', NOW())");
      $stmt->execute(array(
        ":folio" => $folio,
        ":nombre" => $nombre,
        ":empresa" => $empresa,
        ":correo" => strtolower($correo),
        ":telefono" => $telefono,
        ":tipo_interes" => $tipoInteres,
        ":mensaje" => $mensaje
      ));
      return $this->respuesta(false, "success", "Solicitud recibida. Tu acceso sera revisado.", array(
        "folio" => $folio,
        "estatus" => "pendiente",
        "configurado" => true,
        "no_aprueba_automaticamente" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo registrar la solicitud", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar login externo sin mezclar sesion interna ERP.
   * Impacto: Autenticacion Distribucion; evita emitir tokens hasta que exista tabla/token seguro.
   * Contrato: POST JSON; no valida contra usuarios ERP ni revela si un correo existe.
   */
  public function login($datos = array(), $contexto = array()) {
    $correo = trim((string) $this->valor($datos, "correo", ""));
    $contrasenia = (string) $this->valor($datos, "contrasenia", "");
    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $contrasenia === "") {
      return $this->respuesta(true, "warning", "Credenciales incompletas", array("token" => null));
    }

    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes") || !$this->tablaExiste($db, "erp_distribucion_tokens")) {
      return $this->respuesta(true, "warning", "Login Distribucion pendiente de tokens externos ERP", array(
        "token" => null,
        "perfil" => null,
        "configurado" => false,
        "no_usa_sesion_erp" => true
      ));
    }

    try {
      $stmt = $db->prepare("SELECT id_cliente_distribucion, nombre, correo, tipo_cliente, estatus, contrasenia_hash, id_lista_precio
        FROM erp_distribucion_clientes
        WHERE correo=:correo
        LIMIT 1");
      $stmt->execute(array(":correo" => strtolower($correo)));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente || empty($cliente["contrasenia_hash"]) || !password_verify($contrasenia, $cliente["contrasenia_hash"])) {
        return $this->respuesta(true, "warning", "Credenciales invalidas", array("token" => null));
      }
      if ((string) $cliente["estatus"] !== "aprobado") {
        return $this->respuesta(true, "warning", "Tu acceso comercial aun no esta aprobado", array(
          "token" => null,
          "estatus" => $cliente["estatus"]
        ));
      }

      $token = bin2hex(random_bytes(32));
      $tokenHash = hash("sha256", $token);
      $stmtToken = $db->prepare("INSERT INTO erp_distribucion_tokens
        (id_cliente_distribucion, token_hash, estatus, ip_creacion, user_agent, fecha_expiracion, fecha_registro)
        VALUES (:cliente, :token_hash, 'activo', :ip, :ua, DATE_ADD(NOW(), INTERVAL 12 HOUR), NOW())");
      $stmtToken->execute(array(
        ":cliente" => intval($cliente["id_cliente_distribucion"]),
        ":token_hash" => $tokenHash,
        ":ip" => isset($_SERVER["REMOTE_ADDR"]) ? (string) $_SERVER["REMOTE_ADDR"] : "",
        ":ua" => isset($_SERVER["HTTP_USER_AGENT"]) ? substr((string) $_SERVER["HTTP_USER_AGENT"], 0, 255) : ""
      ));
      $db->prepare("UPDATE erp_distribucion_clientes SET fecha_ultimo_login=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":cliente" => intval($cliente["id_cliente_distribucion"])));

      return $this->respuesta(false, "success", "Sesion Distribucion iniciada", array(
        "token" => $token,
        "perfil" => array(
          "id_cliente_distribucion" => intval($cliente["id_cliente_distribucion"]),
          "nombre" => $cliente["nombre"],
          "tipo_cliente" => $cliente["tipo_cliente"],
          "estatus" => $cliente["estatus"],
          "id_lista_precio" => intval($cliente["id_lista_precio"]),
          "permisos" => $this->permisosCliente($db, intval($cliente["id_cliente_distribucion"]))
        ),
        "configurado" => true,
        "no_usa_sesion_erp" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo iniciar sesion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar perfil externo desde token Bearer sin depender de sesion ERP.
   * Impacto: API Distribucion; habilita permisos/lista por cliente en catalogo, precios y cotizaciones.
   * Contrato: read-only; devuelve null si token no es valido.
   */
  public function perfilPorToken($token) {
    $token = trim((string) $token);
    if ($token === "") { return null; }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes") || !$this->tablaExiste($db, "erp_distribucion_tokens")) {
      return null;
    }
    try {
      $stmt = $db->prepare("SELECT c.id_cliente_distribucion, c.nombre, c.tipo_cliente, c.estatus, c.id_lista_precio
        FROM erp_distribucion_tokens t
        INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=t.id_cliente_distribucion
        WHERE t.token_hash=:token_hash
          AND t.estatus='activo'
          AND t.fecha_expiracion>=NOW()
        LIMIT 1");
      $stmt->execute(array(":token_hash" => hash("sha256", $token)));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente) { return null; }
      return array(
        "id_cliente_distribucion" => intval($cliente["id_cliente_distribucion"]),
        "nombre" => $cliente["nombre"],
        "tipo_cliente" => $cliente["tipo_cliente"],
        "estatus" => $cliente["estatus"],
        "id_lista_precio" => intval($cliente["id_lista_precio"]),
        "permisos" => $this->permisosCliente($db, intval($cliente["id_cliente_distribucion"]))
      );
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar solicitudes Distribucion para administracion interna.
   * Impacto: Admin ERP Distribucion; prepara bandeja de aprobacion sin exponerla al frontend externo.
   * Contrato: read-only; devuelve lista vacia segura si falta esquema.
   */
  public function solicitudesInternas($filtros = array()) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_solicitudes")) {
      return $this->respuesta(false, "warning", "Solicitudes Distribucion pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $limite = max(1, min(200, intval($this->valor($filtros, "limite", 50))));
      $estatus = trim((string) $this->valor($filtros, "estatus", ""));
      $where = array("1=1");
      $params = array();
      if ($estatus !== "") {
        $where[] = "estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      $stmt = $db->prepare("SELECT id_solicitud_distribucion, folio, nombre, empresa, correo, telefono, tipo_interes, estatus, fecha_registro
        FROM erp_distribucion_solicitudes
        WHERE " . implode(" AND ", $where) . "
        ORDER BY id_solicitud_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "Solicitudes Distribucion consultadas", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar solicitudes", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar detalle interno de solicitud Distribucion.
   * Impacto: Admin ERP Distribucion; prepara decisiones de aprobacion/rechazo.
   * Contrato: read-only; no crea cliente.
   */
  public function solicitudDetalleInterna($filtros = array()) {
    $id = intval($this->valor($filtros, "id_solicitud_distribucion", $this->valor($filtros, "id", 0)));
    if ($id <= 0) {
      return $this->respuesta(true, "warning", "Solicitud requerida");
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_solicitudes")) {
      return $this->respuesta(false, "warning", "Detalle Distribucion pendiente de esquema", array("configurado" => false, "solicitud" => null));
    }
    try {
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_solicitudes WHERE id_solicitud_distribucion=:id LIMIT 1");
      $stmt->execute(array(":id" => $id));
      return $this->respuesta(false, "success", "Solicitud Distribucion consultada", array("configurado" => true, "solicitud" => $stmt->fetch(PDO::FETCH_ASSOC) ?: null));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar solicitud", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar clientes externos Distribucion para administracion ERP.
   * Impacto: Admin ERP Distribucion; alimenta bandeja de tipo, lista y permisos sin exponer costos.
   * Contrato: read-only; no emite tokens ni cambia estatus.
   */
  public function clientesInternos($filtros = array()) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes")) {
      return $this->respuesta(false, "warning", "Clientes Distribucion pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $limite = max(1, min(200, intval($this->valor($filtros, "limite", 50))));
      $estatus = trim((string) $this->valor($filtros, "estatus", ""));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $where = array("1=1");
      $params = array();
      if ($estatus !== "") {
        $where[] = "c.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      if ($q !== "") {
        $where[] = "(c.nombre LIKE :q OR c.empresa LIKE :q OR c.correo LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      $joinLista = $this->tablaExiste($db, "erp_listas_precios") ? "LEFT JOIN erp_listas_precios l ON l.id_lista_precio=c.id_lista_precio" : "LEFT JOIN (SELECT NULL id_lista_precio, NULL nombre) l ON 1=0";
      $stmt = $db->prepare("SELECT c.id_cliente_distribucion, c.nombre, c.empresa, c.correo, c.telefono, c.tipo_cliente, c.estatus,
          c.id_lista_precio, l.nombre lista_precio, c.fecha_aprobacion, c.fecha_ultimo_login, c.fecha_registro,
          (SELECT COUNT(*) FROM erp_distribucion_cliente_permisos cp WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo') permisos_activos,
          (SELECT GROUP_CONCAT(cp.permiso ORDER BY cp.permiso SEPARATOR ',') FROM erp_distribucion_cliente_permisos cp WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo') permisos
        FROM erp_distribucion_clientes c
        " . $joinLista . "
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.id_cliente_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "Clientes Distribucion consultados", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar clientes Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar listas de precio ERP activas para asignacion Distribucion.
   * Impacto: Admin ERP Distribucion; evita duplicar catalogo de listas en el frontend.
   * Contrato: read-only; solo devuelve identificadores y nombres comerciales.
   */
  public function listasPrecioInternas($filtros = array()) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_listas_precios")) {
      return $this->respuesta(false, "warning", "Listas de precio ERP no disponibles", array("configurado" => false, "items" => array()));
    }
    try {
      $stmt = $db->prepare("SELECT id_lista_precio, codigo, nombre, canal, prioridad, estatus
        FROM erp_listas_precios
        WHERE estatus='activa'
        ORDER BY prioridad ASC, nombre ASC
        LIMIT 200");
      $stmt->execute();
      return $this->respuesta(false, "success", "Listas de precio consultadas", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar listas de precio", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: aprobar cliente externo desde una solicitud Distribucion con trazabilidad propia.
   * Impacto: Admin ERP Distribucion; crea/actualiza perfil externo sin asignar lista ni permisos automaticamente.
   * Contrato: escritura transaccional; requiere esquema aplicado y permiso del controlador.
   */
  public function clienteAprobarPlanInterno($datos = array(), $idUsuario = null) {
    $idSolicitud = intval($this->valor($datos, "id_solicitud_distribucion", 0));
    if ($idSolicitud <= 0) {
      return $this->respuesta(true, "warning", "Solicitud requerida para aprobar cliente");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema Distribucion no disponible", array("configurado" => false));
    }
    try {
      $solicitud = $this->buscarSolicitud($db, $idSolicitud);
      if (!$solicitud) {
        return $this->respuesta(true, "warning", "Solicitud no encontrada");
      }
      $tipo = in_array($solicitud["tipo_interes"], array("registrado", "revendedor", "mayorista", "distribuidor_autorizado"), true) ? $solicitud["tipo_interes"] : "registrado";
      $contrasenia = (string) $this->valor($datos, "contrasenia", "");
      $hash = $contrasenia !== "" ? password_hash($contrasenia, PASSWORD_DEFAULT) : null;
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_clientes
        (nombre, empresa, correo, telefono, tipo_cliente, estatus, contrasenia_hash, fecha_aprobacion, fecha_registro, fecha_actualizacion)
        VALUES (:nombre, :empresa, :correo, :telefono, :tipo, 'aprobado', :hash, NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), empresa=VALUES(empresa), telefono=VALUES(telefono), tipo_cliente=VALUES(tipo_cliente),
          estatus='aprobado', contrasenia_hash=COALESCE(VALUES(contrasenia_hash), contrasenia_hash), fecha_aprobacion=COALESCE(fecha_aprobacion, NOW()), fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":nombre" => $solicitud["nombre"],
        ":empresa" => $solicitud["empresa"],
        ":correo" => strtolower($solicitud["correo"]),
        ":telefono" => $solicitud["telefono"],
        ":tipo" => $tipo,
        ":hash" => $hash
      ));
      $stmtCliente = $db->prepare("SELECT id_cliente_distribucion FROM erp_distribucion_clientes WHERE correo=:correo LIMIT 1");
      $stmtCliente->execute(array(":correo" => strtolower($solicitud["correo"])));
      $idCliente = intval($stmtCliente->fetchColumn());
      $db->prepare("UPDATE erp_distribucion_solicitudes
        SET estatus='aprobado', id_cliente_distribucion=:cliente, fecha_actualizacion=NOW()
        WHERE id_solicitud_distribucion=:solicitud")
        ->execute(array(":cliente" => $idCliente, ":solicitud" => $idSolicitud));
      $this->registrarAuditoria($db, "cliente", $idCliente, "aprobar", "ok", "Cliente Distribucion aprobado", array(
        "id_solicitud_distribucion" => $idSolicitud,
        "tipo_cliente" => $tipo,
        "contrasenia_recibida" => $hash !== null,
        "lista_asignada" => false,
        "permisos_asignados" => false
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cliente Distribucion aprobado", array(
        "ejecutado" => true,
        "id_cliente_distribucion" => $idCliente,
        "id_solicitud_distribucion" => $idSolicitud,
        "no_asigna_lista_automaticamente" => true,
        "no_asigna_permisos_automaticamente" => true
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo aprobar cliente Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: cambiar estatus logico de cliente/solicitud Distribucion.
   * Impacto: Admin ERP Distribucion; bloquea acceso sin borrar historial.
   * Contrato: escritura transaccional; registra auditoria propia.
   */
  public function clienteEstatusPlanInterno($datos = array(), $estatus, $idUsuario = null) {
    if (!in_array($estatus, array("rechazado", "suspendido", "aprobado", "pendiente"), true)) {
      return $this->respuesta(true, "warning", "Estatus no valido");
    }
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    $idSolicitud = intval($this->valor($datos, "id_solicitud_distribucion", 0));
    if ($idCliente <= 0 && $idSolicitud <= 0) {
      return $this->respuesta(true, "warning", "Cliente o solicitud requerida");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema Distribucion no disponible", array("configurado" => false));
    }
    try {
      $db->beginTransaction();
      if ($idCliente > 0) {
        $cliente = $this->buscarCliente($db, $idCliente);
        if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
        $db->prepare("UPDATE erp_distribucion_clientes SET estatus=:estatus, fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:id")
          ->execute(array(":estatus" => $estatus, ":id" => $idCliente));
        if ($estatus === "suspendido") {
          $db->prepare("UPDATE erp_distribucion_tokens SET estatus='revocado', fecha_ultimo_uso=NOW() WHERE id_cliente_distribucion=:id AND estatus='activo'")
            ->execute(array(":id" => $idCliente));
        }
      }
      if ($idSolicitud > 0) {
        $solicitud = $this->buscarSolicitud($db, $idSolicitud);
        if (!$solicitud) { throw new Exception("solicitud_no_encontrada"); }
        $db->prepare("UPDATE erp_distribucion_solicitudes SET estatus=:estatus, fecha_actualizacion=NOW() WHERE id_solicitud_distribucion=:id")
          ->execute(array(":estatus" => $estatus, ":id" => $idSolicitud));
      }
      $this->registrarAuditoria($db, $idCliente > 0 ? "cliente" : "solicitud", $idCliente > 0 ? $idCliente : $idSolicitud, "cambiar_estatus", "ok", "Estatus Distribucion actualizado", array(
        "estatus" => $estatus,
        "id_cliente_distribucion" => $idCliente ?: null,
        "id_solicitud_distribucion" => $idSolicitud ?: null
      ), $idUsuario, $idCliente ?: null);
      $db->commit();
      return $this->respuesta(false, "success", "Estatus Distribucion actualizado", array(
        "ejecutado" => true,
        "estatus" => $estatus,
        "id_cliente_distribucion" => $idCliente ?: null,
        "id_solicitud_distribucion" => $idSolicitud ?: null
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo actualizar estatus Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: guardar cambio de tipo comercial de cliente Distribucion.
   * Impacto: Admin ERP Distribucion; tipo no concede permisos automaticamente.
   * Contrato: escritura sobre cliente externo; registra auditoria.
   */
  public function tipoClientePlanInterno($datos = array(), $idUsuario = null) {
    $tipo = trim((string) $this->valor($datos, "tipo_cliente", ""));
    if (!in_array($tipo, array("registrado", "revendedor", "mayorista", "distribuidor_autorizado"), true)) {
      return $this->respuesta(true, "warning", "Tipo de cliente no valido");
    }
    return $this->actualizarClienteCampo($datos, "tipo_cliente", $tipo, "asignar_tipo_cliente", $idUsuario);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: asignar lista de precio ERP a cliente Distribucion.
   * Impacto: Admin ERP Distribucion; precios se resolveran siempre en ERP.
   * Contrato: valida lista activa y registra historial/auditoria.
   */
  public function listaPrecioPlanInterno($datos = array(), $idUsuario = null) {
    $idLista = intval($this->valor($datos, "id_lista_precio", 0));
    if ($idLista <= 0) {
      return $this->respuesta(true, "warning", "Lista de precio requerida");
    }
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->tablaExiste($db, "erp_listas_precios")) {
      return $this->respuesta(true, "warning", "Esquema de listas no disponible", array("configurado" => false));
    }
    try {
      $stmtLista = $db->prepare("SELECT id_lista_precio, nombre, estatus FROM erp_listas_precios WHERE id_lista_precio=:lista LIMIT 1");
      $stmtLista->execute(array(":lista" => $idLista));
      $lista = $stmtLista->fetch(PDO::FETCH_ASSOC);
      if (!$lista || (string) $lista["estatus"] !== "activa") {
        return $this->respuesta(true, "warning", "Lista de precio no activa");
      }
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("UPDATE erp_distribucion_clientes SET id_lista_precio=:lista, fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":lista" => $idLista, ":cliente" => $idCliente));
      $db->prepare("UPDATE erp_distribucion_cliente_listas SET estatus='inactivo', fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente AND estatus='activo'")
        ->execute(array(":cliente" => $idCliente));
      $db->prepare("INSERT INTO erp_distribucion_cliente_listas
        (id_cliente_distribucion, id_lista_precio, prioridad, estatus, fecha_inicio, fecha_registro)
        VALUES (:cliente, :lista, 1, 'activo', NOW(), NOW())")
        ->execute(array(":cliente" => $idCliente, ":lista" => $idLista));
      $this->registrarAuditoria($db, "cliente", $idCliente, "asignar_lista_precio", "ok", "Lista de precio Distribucion asignada", array(
        "id_lista_precio" => $idLista,
        "lista" => $lista["nombre"]
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Lista de precio asignada", array("ejecutado" => true, "id_cliente_distribucion" => $idCliente, "id_lista_precio" => $idLista));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo asignar lista de precio", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: guardar permisos comerciales externos de un cliente Distribucion.
   * Impacto: Admin ERP Distribucion; evita codigos no reconocidos por contrato.
   * Contrato: reemplazo idempotente de permisos activos; registra auditoria.
   */
  public function permisosPlanInterno($datos = array(), $idUsuario = null) {
    require_once RUTA_APP . "/modelos/DistribucionPermisosApi.php";
    $permitidos = (new DistribucionPermisosApi())->permisosComerciales();
    $permisos = $this->normalizarPermisos($this->valor($datos, "permisos", array()));
    foreach ($permisos as $permiso) {
      if (!in_array($permiso, $permitidos, true)) {
        return $this->respuesta(true, "warning", "Permiso comercial no valido", array("permiso" => $permiso));
      }
    }
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema Distribucion no disponible", array("configurado" => false));
    }
    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("UPDATE erp_distribucion_cliente_permisos SET estatus='inactivo', fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":cliente" => $idCliente));
      foreach ($permisos as $permiso) {
        $db->prepare("INSERT INTO erp_distribucion_cliente_permisos
          (id_cliente_distribucion, permiso, estatus, fecha_registro, fecha_actualizacion)
          VALUES (:cliente, :permiso, 'activo', NOW(), NOW())
          ON DUPLICATE KEY UPDATE estatus='activo', fecha_actualizacion=NOW()")
          ->execute(array(":cliente" => $idCliente, ":permiso" => $permiso));
      }
      $this->registrarAuditoria($db, "cliente", $idCliente, "asignar_permisos", "ok", "Permisos Distribucion actualizados", array(
        "permisos" => $permisos
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Permisos Distribucion actualizados", array("ejecutado" => true, "id_cliente_distribucion" => $idCliente, "permisos" => $permisos));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudieron actualizar permisos Distribucion", array("detalle" => "error_controlado"));
    }
  }

  private function permisosCliente($db, $idCliente) {
    if (!$this->tablaExiste($db, "erp_distribucion_cliente_permisos")) {
      return array();
    }
    $stmt = $db->prepare("SELECT permiso FROM erp_distribucion_cliente_permisos WHERE id_cliente_distribucion=:cliente AND estatus='activo' ORDER BY permiso ASC");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    $permisos = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $permisos[] = $fila["permiso"];
    }
    return $permisos;
  }

  private function actualizarClienteCampo($datos, $campo, $valor, $accion, $idUsuario) {
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema Distribucion no disponible", array("configurado" => false));
    }
    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("UPDATE erp_distribucion_clientes SET " . $campo . "=:valor, fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":valor" => $valor, ":cliente" => $idCliente));
      $this->registrarAuditoria($db, "cliente", $idCliente, $accion, "ok", "Cliente Distribucion actualizado", array($campo => $valor), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cliente Distribucion actualizado", array("ejecutado" => true, "id_cliente_distribucion" => $idCliente, $campo => $valor));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo actualizar cliente Distribucion", array("detalle" => "error_controlado"));
    }
  }

  private function esquemaOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_distribucion_clientes")
      && $this->tablaExiste($db, "erp_distribucion_solicitudes")
      && $this->tablaExiste($db, "erp_distribucion_cliente_permisos")
      && $this->tablaExiste($db, "erp_distribucion_cliente_listas")
      && $this->tablaExiste($db, "erp_distribucion_tokens")
      && $this->tablaExiste($db, "erp_distribucion_auditoria");
  }

  private function buscarSolicitud($db, $idSolicitud) {
    $stmt = $db->prepare("SELECT * FROM erp_distribucion_solicitudes WHERE id_solicitud_distribucion=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idSolicitud)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function buscarCliente($db, $idCliente) {
    $stmt = $db->prepare("SELECT * FROM erp_distribucion_clientes WHERE id_cliente_distribucion=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idCliente)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function normalizarPermisos($permisos) {
    if (is_string($permisos)) {
      $permisos = json_decode($permisos, true);
    }
    if (!is_array($permisos)) { return array(); }
    $limpios = array();
    foreach ($permisos as $permiso) {
      $permiso = trim((string) $permiso);
      if ($permiso !== "") { $limpios[] = $permiso; }
    }
    return array_values(array_unique($limpios));
  }

  private function registrarAuditoria($db, $entidad, $idEntidad, $accion, $resultado, $mensaje, $detalle, $idUsuario, $idCliente = null) {
    if (!$this->tablaExiste($db, "erp_distribucion_auditoria")) { return; }
    $stmt = $db->prepare("INSERT INTO erp_distribucion_auditoria
      (entidad, id_entidad, accion, resultado, mensaje, detalle_json, id_usuario_erp, id_cliente_distribucion, fecha_registro)
      VALUES (:entidad, :id_entidad, :accion, :resultado, :mensaje, :detalle, :usuario, :cliente, NOW())");
    $stmt->execute(array(
      ":entidad" => $entidad,
      ":id_entidad" => $idEntidad,
      ":accion" => $accion,
      ":resultado" => $resultado,
      ":mensaje" => $mensaje,
      ":detalle" => json_encode($detalle),
      ":usuario" => $idUsuario,
      ":cliente" => $idCliente
    ));
  }

  private function folioSolicitud($db) {
    $prefijo = "DIST-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_distribucion_solicitudes WHERE folio LIKE :prefijo");
    $stmt->execute(array(":prefijo" => $prefijo . "%"));
    return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 4, "0", STR_PAD_LEFT);
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

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
