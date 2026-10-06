<?php

class DistribucionClientesApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: validar y persistir solicitud comercial MAYOREO de Distribucion sin aprobar acceso automaticamente.
   * Impacto: Clientes Distribucion; guarda datos comerciales estructurados para revision interna ERP.
   * Contrato: POST JSON; no crea usuarios internos, no asigna listas, permisos ni credenciales sensibles.
   */
  public function registrarSolicitud($datos = array(), $contexto = array()) {
    $nombre = trim((string) $this->valor($datos, "nombre", ""));
    $nombreNegocio = trim((string) $this->valor($datos, "nombre_negocio", ""));
    $empresa = trim((string) $this->valor($datos, "empresa", $nombreNegocio));
    if ($empresa === "") { $empresa = $nombreNegocio; }
    $correo = strtolower(trim((string) $this->valor($datos, "correo", "")));
    $telefono = trim((string) $this->valor($datos, "telefono", ""));
    $whatsapp = trim((string) $this->valor($datos, "whatsapp", ""));
    $rfc = strtoupper(trim((string) $this->valor($datos, "rfc", "")));
    $ciudad = trim((string) $this->valor($datos, "ciudad", ""));
    $estado = trim((string) $this->valor($datos, "estado", ""));
    $tipoInteresEntrada = strtolower(trim((string) $this->valor($datos, "tipo_interes", "mayorista")));
    $tipoInteres = "mayorista";
    $tipoNegocio = strtolower(trim((string) $this->valor($datos, "tipo_negocio", "")));
    $calle = trim((string) $this->valor($datos, "calle", ""));
    $numeroExterior = trim((string) $this->valor($datos, "numero_exterior", ""));
    $numeroInterior = trim((string) $this->valor($datos, "numero_interior", ""));
    $colonia = trim((string) $this->valor($datos, "colonia", ""));
    $codigoPostal = trim((string) $this->valor($datos, "codigo_postal", ""));
    $referencias = trim((string) $this->valor($datos, "referencias", ""));
    $interesesComerciales = trim((string) $this->valor($datos, "intereses_comerciales", ""));
    $categoriasInteres = $this->normalizarCategoriasInteres($this->valor($datos, "categorias_interes", $this->valor($datos, "categorias", array())));
    $mensaje = trim((string) $this->valor($datos, "mensaje", ""));
    $facturacionEntrada = $this->valor($datos, "facturacion", array());
    if (!is_array($facturacionEntrada)) { $facturacionEntrada = array(); }
    $requiereFactura = intval($this->valor($datos, "requiere_factura", $this->valor($facturacionEntrada, "requiere_factura", 0))) === 1 ? 1 : 0;
    $rfcFiscal = strtoupper(trim((string) $this->valor($facturacionEntrada, "rfc", $rfc)));
    if ($rfc === "" && $rfcFiscal !== "") { $rfc = $rfcFiscal; }
    $facturacion = array(
      "requiere_factura" => $requiereFactura,
      "rfc" => $requiereFactura ? $rfcFiscal : "",
      "razon_social" => $requiereFactura ? trim((string) $this->valor($facturacionEntrada, "razon_social", $this->valor($datos, "razon_social", ""))) : "",
      "regimen_fiscal" => $requiereFactura ? trim((string) $this->valor($facturacionEntrada, "regimen_fiscal", $this->valor($datos, "regimen_fiscal", ""))) : "",
      "uso_cfdi" => $requiereFactura ? strtoupper(trim((string) $this->valor($facturacionEntrada, "uso_cfdi", $this->valor($datos, "uso_cfdi", "")))) : "",
      "codigo_postal_fiscal" => $requiereFactura ? trim((string) $this->valor($facturacionEntrada, "codigo_postal_fiscal", $this->valor($datos, "codigo_postal_fiscal", ""))) : "",
      "correo_facturacion" => $requiereFactura ? strtolower(trim((string) $this->valor($facturacionEntrada, "correo_facturacion", $this->valor($datos, "correo_facturacion", "")))) : "",
      "comentarios_facturacion" => $requiereFactura ? trim((string) $this->valor($facturacionEntrada, "comentarios_facturacion", $this->valor($datos, "comentarios_facturacion", ""))) : ""
    );
    $tiposNegocio = array("venta_internet", "veterinaria", "petshop", "acuario", "acuario_petshop", "estetica_canina", "criador", "vendedor_mercado", "vendedor_ambulante", "otro");
    $errores = array();
    $camposFaltantes = array();
    $erroresCampos = array();
    if ($nombre === "") { $errores[] = "nombre_requerido"; $camposFaltantes[] = "nombre"; }
    if ($nombreNegocio === "") { $errores[] = "nombre_negocio_requerido"; $camposFaltantes[] = "nombre_negocio"; }
    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) { $errores[] = "correo_invalido"; $camposFaltantes[] = "correo"; $erroresCampos["correo"] = "Ingresa un correo valido."; }
    if ($telefono === "") { $errores[] = "telefono_requerido"; $camposFaltantes[] = "telefono"; $erroresCampos["telefono"] = "Ingresa un telefono de contacto."; }
    if ($ciudad === "") { $errores[] = "ciudad_requerida"; $camposFaltantes[] = "ciudad"; }
    if ($estado === "") { $errores[] = "estado_requerido"; $camposFaltantes[] = "estado"; }
    if (!in_array($tipoNegocio, $tiposNegocio, true)) { $errores[] = "tipo_negocio_invalido"; $camposFaltantes[] = "tipo_negocio"; $erroresCampos["tipo_negocio"] = "Selecciona un tipo de negocio valido."; }
    if ($facturacion["requiere_factura"] === 1) {
      foreach (array("rfc", "razon_social", "regimen_fiscal", "uso_cfdi", "codigo_postal_fiscal", "correo_facturacion") as $campoFiscal) {
        if (trim((string) $facturacion[$campoFiscal]) === "") {
          $errores[] = "facturacion_" . $campoFiscal . "_requerido";
          $camposFaltantes[] = "facturacion." . $campoFiscal;
          $erroresCampos["facturacion." . $campoFiscal] = "Campo fiscal requerido.";
        }
      }
      if ($facturacion["correo_facturacion"] !== "" && !filter_var($facturacion["correo_facturacion"], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "correo_facturacion_invalido";
        $erroresCampos["facturacion.correo_facturacion"] = "Ingresa un correo de facturacion valido.";
      }
    }
    if (!empty($errores)) {
      $respuesta = $this->respuesta(true, "warning", "Completa los campos requeridos para enviar tu solicitud.", array(
        "codigo" => "solicitud_incompleta",
        "campos_faltantes" => array_values(array_unique($camposFaltantes)),
        "errores_campos" => $erroresCampos,
        "errores" => $errores
      ));
      $respuesta["codigo"] = "solicitud_incompleta";
      $respuesta["campos_faltantes"] = array_values(array_unique($camposFaltantes));
      $respuesta["errores_campos"] = $erroresCampos;
      return $respuesta;
    }

    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_solicitudes")) {
      return $this->respuesta(true, "warning", "Contrato de registro listo; persistencia pendiente en ERP", array(
        "folio" => null,
        "estatus" => "pendiente",
        "configurado" => false,
        "no_aprueba_automaticamente" => true,
        "campos_recibidos" => array("nombre", "nombre_negocio", "empresa", "correo", "telefono", "whatsapp", "ciudad", "estado", "tipo_negocio", "tipo_interes", "mensaje")
      ));
    }

    try {
      if (!$this->columnasSolicitudComercialListas($db)) {
        return $this->respuesta(true, "warning", "Esquema de registro comercial Distribucion pendiente", array(
          "configurado" => false,
          "requiere_actualizar_esquema" => true,
          "campos_busqueda_requeridos" => array("nombre_negocio", "whatsapp", "ciudad", "estado", "tipo_negocio", "datos_comerciales_json")
        ));
      }
      $folio = $this->folioSolicitud($db);
      $datosComerciales = array(
        "nombre" => $nombre,
        "nombre_negocio" => $nombreNegocio,
        "empresa" => $empresa,
        "correo" => $correo,
        "telefono" => $telefono,
        "whatsapp" => $whatsapp,
        "rfc" => $rfc,
        "ciudad" => $ciudad,
        "estado" => $estado,
        "tipo_interes" => $tipoInteres,
        "tipo_interes_recibido" => $tipoInteresEntrada,
        "tipo_negocio" => $tipoNegocio,
        "calle" => $calle,
        "numero_exterior" => $numeroExterior,
        "numero_interior" => $numeroInterior,
        "colonia" => $colonia,
        "codigo_postal" => $codigoPostal,
        "referencias" => $referencias,
        "requiere_factura" => $facturacion["requiere_factura"],
        "facturacion" => $facturacion,
        "categorias_interes" => $categoriasInteres,
        "intereses_comerciales" => $interesesComerciales,
        "mensaje" => $mensaje,
        "pendiente_permitir_registro_sin_correo" => true
      );
      $stmt = $db->prepare("INSERT INTO erp_distribucion_solicitudes
        (folio, nombre, nombre_negocio, empresa, correo, telefono, whatsapp, rfc, ciudad, estado, tipo_interes, tipo_negocio, calle, numero_exterior, numero_interior, colonia, codigo_postal, referencias, intereses_comerciales, categorias_interes_json, mensaje, datos_comerciales_json, ip_registro, user_agent, estatus, fecha_registro)
        VALUES (:folio, :nombre, :nombre_negocio, :empresa, :correo, :telefono, :whatsapp, :rfc, :ciudad, :estado, :tipo_interes, :tipo_negocio, :calle, :numero_exterior, :numero_interior, :colonia, :codigo_postal, :referencias, :intereses_comerciales, :categorias_interes_json, :mensaje, :datos_comerciales_json, :ip_registro, :user_agent, 'pendiente', NOW())");
      $stmt->execute(array(
        ":folio" => $folio,
        ":nombre" => $nombre,
        ":nombre_negocio" => $nombreNegocio,
        ":empresa" => $empresa,
        ":correo" => $correo,
        ":telefono" => $telefono,
        ":whatsapp" => $whatsapp,
        ":rfc" => $rfc,
        ":ciudad" => $ciudad,
        ":estado" => $estado,
        ":tipo_interes" => $tipoInteres,
        ":tipo_negocio" => $tipoNegocio,
        ":calle" => $calle,
        ":numero_exterior" => $numeroExterior,
        ":numero_interior" => $numeroInterior,
        ":colonia" => $colonia,
        ":codigo_postal" => $codigoPostal,
        ":referencias" => $referencias,
        ":intereses_comerciales" => $interesesComerciales,
        ":categorias_interes_json" => json_encode($categoriasInteres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ":mensaje" => $mensaje,
        ":datos_comerciales_json" => json_encode($datosComerciales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ":ip_registro" => $this->ipContexto($contexto),
        ":user_agent" => $this->userAgentContexto($contexto)
      ));
      $respuesta = $this->respuesta(false, "success", "Solicitud recibida. Tu acceso sera revisado por Artiani.", array(
        "folio" => $folio,
        "estatus" => "pendiente",
        "siguiente_paso" => "Te contactaremos cuando la revision comercial avance.",
        "configurado" => true,
        "no_aprueba_automaticamente" => true,
        "no_asigna_lista_automaticamente" => true,
        "no_asigna_permisos_automaticamente" => true,
        "tipo_interes_forzado" => $tipoInteresEntrada !== "mayorista"
      ));
      $respuesta["folio"] = $folio;
      $respuesta["estatus"] = "pendiente";
      $respuesta["siguiente_paso"] = "Te contactaremos cuando la revision comercial avance.";
      return $respuesta;
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
      return $this->respuestaLoginError("credenciales_invalidas", "No pudimos iniciar sesion. Revisa tu correo y contrasenia. Si aun no tienes acceso aprobado o contrasenia activa, solicita acceso comercial o contacta a Artiani.", array("token" => null));
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
      $stmt = $db->prepare("SELECT *
        FROM erp_distribucion_clientes
        WHERE correo=:correo
        LIMIT 1");
      $stmt->execute(array(":correo" => strtolower($correo)));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente) {
        $this->registrarAuditoria($db, "auth", null, "login", "error", "Credenciales Distribucion invalidas", $this->detalleAcceso($contexto, array(
          "correo" => strtolower($correo),
          "motivo" => "credenciales_invalidas"
        )), null, null);
        return $this->respuestaLoginError("credenciales_invalidas", "No pudimos iniciar sesion. Revisa tu correo y contrasenia. Si aun no tienes acceso aprobado o contrasenia activa, solicita acceso comercial o contacta a Artiani.", array("token" => null));
      }
      if (empty($cliente["contrasenia_hash"])) {
        $codigoActivacion = $this->tieneTokenActivacionActivo($db, intval($cliente["id_cliente_distribucion"])) ? "requiere_activacion" : "contrasenia_no_creada";
        $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "login", "bloqueado", "Login Distribucion requiere activacion", $this->detalleAcceso($contexto, array(
          "correo" => strtolower($correo),
          "motivo" => $codigoActivacion
        )), null, intval($cliente["id_cliente_distribucion"]));
        return $this->respuestaLoginError($codigoActivacion, "Tu cuenta aun necesita activar o crear contrasenia antes de iniciar sesion.", array(
          "token" => null,
          "estatus" => $this->valor($cliente, "estatus", ""),
          "siguiente_paso" => "Usa el link de activacion enviado por Artiani o solicita que te lo reenviemos."
        ));
      }
      if (!password_verify($contrasenia, $cliente["contrasenia_hash"])) {
        $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "login", "error", "Credenciales Distribucion invalidas", $this->detalleAcceso($contexto, array(
          "correo" => strtolower($correo),
          "motivo" => "credenciales_invalidas"
        )), null, intval($cliente["id_cliente_distribucion"]));
        return $this->respuestaLoginError("credenciales_invalidas", "No pudimos iniciar sesion. Revisa tu correo y contrasenia. Si aun no tienes acceso aprobado o contrasenia activa, solicita acceso comercial o contacta a Artiani.", array("token" => null));
      }
      $estatusCliente = (string) $cliente["estatus"];
      if ($estatusCliente !== "aprobado") {
        $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "login", "bloqueado", "Login Distribucion bloqueado por estatus", $this->detalleAcceso($contexto, array(
          "correo" => strtolower($correo),
          "estatus" => $estatusCliente
        )), null, intval($cliente["id_cliente_distribucion"]));
        $estado = $this->estadoAccesoCliente($estatusCliente, array());
        return $this->respuestaLoginError($this->codigoLoginPorEstatus($estatusCliente), $estado["mensaje"], array(
          "token" => null,
          "estatus" => $estatusCliente,
          "siguiente_paso" => $estado["siguiente_paso"]
        ));
      }

      $token = bin2hex(random_bytes(32));
      $tokenHash = hash("sha256", $token);
      $stmtToken = $db->prepare("INSERT INTO erp_distribucion_tokens
        (id_cliente_distribucion, token_hash, tipo_token, estatus, ip_creacion, user_agent, fecha_expiracion, fecha_registro)
        VALUES (:cliente, :token_hash, 'sesion', 'activo', :ip, :ua, DATE_ADD(NOW(), INTERVAL 12 HOUR), NOW())");
      $stmtToken->execute(array(
        ":cliente" => intval($cliente["id_cliente_distribucion"]),
        ":token_hash" => $tokenHash,
        ":ip" => isset($_SERVER["REMOTE_ADDR"]) ? (string) $_SERVER["REMOTE_ADDR"] : "",
        ":ua" => isset($_SERVER["HTTP_USER_AGENT"]) ? substr((string) $_SERVER["HTTP_USER_AGENT"], 0, 255) : ""
      ));
      $db->prepare("UPDATE erp_distribucion_clientes SET fecha_ultimo_login=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":cliente" => intval($cliente["id_cliente_distribucion"])));
      $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "login", "ok", "Sesion Distribucion iniciada", $this->detalleAcceso($contexto, array(
        "correo" => strtolower($correo),
        "token_sesion_expira_horas" => 12
      )), null, intval($cliente["id_cliente_distribucion"]));

      $permisos = $this->permisosCliente($db, intval($cliente["id_cliente_distribucion"]));
      $acciones = $this->accionesPermitidasCliente($permisos);
      $perfil = $this->formatearPerfilCliente($db, $cliente, $permisos, $acciones);
      $mensajeLogin = $estatusCliente === "aprobado" ? "Inicio de sesion correcto." : "Acceso en revision consultado.";
      $respuesta = $this->respuesta(false, "success", $mensajeLogin, array(
        "token" => $token,
        "perfil" => $perfil,
        "permisos" => $permisos,
        "acciones" => $acciones,
        "configurado" => true,
        "no_usa_sesion_erp" => true
      ));
      $respuesta["token"] = $token;
      $respuesta["perfil"] = $perfil;
      $respuesta["permisos"] = $permisos;
      $respuesta["acciones"] = $acciones;
      if (empty($permisos)) {
        $respuesta["codigo"] = "sin_permisos_comerciales";
      }
      return $respuesta;
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
      $stmt = $db->prepare("SELECT c.*
        FROM erp_distribucion_tokens t
        INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=t.id_cliente_distribucion
        WHERE t.token_hash=:token_hash
          AND (t.tipo_token='sesion' OR t.tipo_token IS NULL)
          AND t.estatus='activo'
          AND t.fecha_expiracion>=NOW()
        LIMIT 1");
      $stmt->execute(array(":token_hash" => hash("sha256", $token)));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente) { return null; }
      $permisos = $this->permisosCliente($db, intval($cliente["id_cliente_distribucion"]));
      $acciones = $this->accionesPermitidasCliente($permisos);
      return $this->formatearPerfilCliente($db, $cliente, $permisos, $acciones);
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-06
   * Proposito: reconstruir perfil completo para `/auth/perfil` desde un cliente autenticado.
   * Impacto: Portal Distribucion; entrega Mi cuenta, estado de acceso y acciones sin usar sesion ERP.
   * Contrato: read-only; no expone hashes, tokens, costos, margenes, proveedores ni stock.
   */
  public function perfilPorId($idCliente) {
    $idCliente = intval($idCliente);
    if ($idCliente <= 0) { return null; }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes")) {
      return null;
    }
    try {
      $stmt = $db->prepare("SELECT * FROM erp_distribucion_clientes WHERE id_cliente_distribucion=:cliente LIMIT 1");
      $stmt->execute(array(":cliente" => $idCliente));
      $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cliente) { return null; }
      $permisos = $this->permisosCliente($db, $idCliente);
      $acciones = $this->accionesPermitidasCliente($permisos);
      return $this->formatearPerfilCliente($db, $cliente, $permisos, $acciones);
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-06
   * Proposito: responder recuperacion de acceso sin enumerar cuentas ni enviar correos desde este contrato.
   * Impacto: Login Distribucion; permite al frontend mostrar guia segura.
   * Contrato: POST seguro; respuesta uniforme exista o no exista el correo.
   */
  public function recuperarAcceso($datos = array(), $contexto = array()) {
    return $this->respuesta(false, "success", "Si el correo esta registrado y habilitado, enviaremos instrucciones para recuperar tu acceso.", array(
      "codigo" => "recuperacion_recibida",
      "no_enumera_cuentas" => true,
      "envio_automatico_pendiente" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-06
   * Proposito: responder solicitud de reenvio de activacion sin exponer existencia o estatus de cuenta.
   * Impacto: Login Distribucion; mantiene aprobacion y activacion bajo control del ERP.
   * Contrato: POST seguro; no asigna permisos, listas ni aprueba clientes.
   */
  public function reenviarActivacion($datos = array(), $contexto = array()) {
    return $this->respuesta(false, "success", "Si tu cuenta requiere activacion, enviaremos nuevamente las instrucciones disponibles.", array(
      "codigo" => "reenviar_activacion_recibido",
      "no_enumera_cuentas" => true,
      "envio_automatico_pendiente" => true
    ));
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
      $extraSolicitud = $this->columnaExiste($db, "erp_distribucion_solicitudes", "categorias_interes_json") ? "categorias_interes_json," : "NULL categorias_interes_json,";
      $stmt = $db->prepare("SELECT id_solicitud_distribucion, id_cliente_distribucion, folio, nombre, nombre_negocio, empresa, correo, telefono, whatsapp, rfc, ciudad, estado, tipo_interes, tipo_negocio, intereses_comerciales, " . $extraSolicitud . " estatus, fecha_registro
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
      $extraEntrega = $this->columnasEntregaClienteDisponibles($db)
        ? "c.metodo_entrega_default, c.entrega_habilitar_envio, c.entrega_habilitar_recoger_tienda, c.costo_envio_default,"
        : "NULL metodo_entrega_default, NULL entrega_habilitar_envio, NULL entrega_habilitar_recoger_tienda, NULL costo_envio_default,";
      $extraCatalogo = $this->columnasCatalogoClienteDisponibles($db)
        ? "c.catalogo_modo, c.categorias_interes_json,"
        : "'general' catalogo_modo, NULL categorias_interes_json,";
      $stmt = $db->prepare("SELECT c.id_cliente_distribucion, c.nombre, c.empresa, c.correo, c.telefono, c.tipo_cliente, c.estatus,
          c.id_lista_precio, " . $extraCatalogo . " " . $extraEntrega . " l.nombre lista_precio, c.fecha_aprobacion, c.fecha_ultimo_login, c.fecha_registro,
          (SELECT COUNT(*) FROM erp_distribucion_cliente_permisos cp WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo') permisos_activos,
          (SELECT GROUP_CONCAT(cp.permiso ORDER BY cp.permiso SEPARATOR ',') FROM erp_distribucion_cliente_permisos cp WHERE cp.id_cliente_distribucion=c.id_cliente_distribucion AND cp.estatus='activo') permisos
        FROM erp_distribucion_clientes c
        " . $joinLista . "
        WHERE " . implode(" AND ", $where) . "
        ORDER BY c.id_cliente_distribucion DESC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $fila["categorias_interes"] = $this->jsonArray($this->valor($fila, "categorias_interes_json", ""));
        $items[] = $fila;
      }
      return $this->respuesta(false, "success", "Clientes Distribucion consultados", array("configurado" => true, "items" => $items));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-11
   * Proposito: listar eventos de acceso/activacion de un cliente Distribucion para soporte interno.
   * Impacto: Admin ERP Distribucion; muestra trazabilidad sin revelar tokens, hashes ni contrasenas.
   * Contrato: read-only sobre `erp_distribucion_auditoria`.
   */
  public function auditoriaClienteInterna($filtros = array()) {
    $idCliente = intval($this->valor($filtros, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido", array("items" => array()));
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_auditoria")) {
      return $this->respuesta(false, "warning", "Auditoria Distribucion no disponible", array("configurado" => false, "items" => array()));
    }
    try {
      $stmt = $db->prepare("SELECT id_auditoria_distribucion, entidad, id_entidad, accion, resultado, mensaje, detalle_json, id_usuario_erp, fecha_registro
        FROM erp_distribucion_auditoria
        WHERE id_cliente_distribucion=:cliente
           OR (entidad='cliente' AND id_entidad=:cliente)
        ORDER BY id_auditoria_distribucion DESC
        LIMIT 80");
      $stmt->execute(array(":cliente" => $idCliente));
      return $this->respuesta(false, "success", "Auditoria Distribucion consultada", array(
        "configurado" => true,
        "id_cliente_distribucion" => $idCliente,
        "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo consultar auditoria Distribucion", array("detalle" => "error_controlado", "items" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: aprobar cliente externo desde solicitud Distribucion y configurar acceso comercial explicito.
   * Impacto: Admin ERP Distribucion; crea/actualiza prospecto sin asumir lista, permisos ni credenciales automaticas.
   * Contrato: escritura transaccional; lista/permisos solo se aplican cuando el admin los envia y el controlador los autoriza.
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
      $tipo = "mayorista";
      $empresa = trim((string) (isset($solicitud["nombre_negocio"]) && $solicitud["nombre_negocio"] !== "" ? $solicitud["nombre_negocio"] : $solicitud["empresa"]));
      $categoriasInteres = $this->jsonArray($this->valor($solicitud, "categorias_interes_json", ""));
      $datosSolicitud = $this->jsonArray($this->valor($solicitud, "datos_comerciales_json", ""));
      $facturacionSolicitud = $this->valor($datosSolicitud, "facturacion", array());
      if (!is_array($facturacionSolicitud)) { $facturacionSolicitud = array(); }
      $contrasenia = (string) $this->valor($datos, "contrasenia", "");
      $hash = $contrasenia !== "" ? password_hash($contrasenia, PASSWORD_DEFAULT) : null;
      $idLista = intval($this->valor($datos, "id_lista_precio", 0));
      $lista = null;
      if ($idLista > 0) {
        $lista = $this->listaPrecioActiva($db, $idLista);
        if (!$lista) {
          return $this->respuesta(true, "warning", "Lista de precio no activa");
        }
      }
      $permisos = $this->normalizarPermisos($this->valor($datos, "permisos", array()));
      if (!empty($permisos)) {
        $validacionPermisos = $this->validarPermisosComerciales($permisos);
        if ($validacionPermisos !== true) {
          return $this->respuesta(true, "warning", "Permiso comercial no valido", array("permiso" => $validacionPermisos));
        }
      }
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_distribucion_clientes
        (nombre, empresa, nombre_negocio, correo, telefono, whatsapp, tipo_cliente, estatus, contrasenia_hash, tipo_negocio, ciudad, estado,
          calle, numero_exterior, numero_interior, colonia, codigo_postal, referencias, requiere_factura, rfc, razon_social, regimen_fiscal,
          uso_cfdi, codigo_postal_fiscal, correo_facturacion, comentarios_facturacion, catalogo_modo, categorias_interes_json, fecha_aprobacion, fecha_registro, fecha_actualizacion)
        VALUES (:nombre, :empresa, :nombre_negocio, :correo, :telefono, :whatsapp, :tipo, 'aprobado', :hash, :tipo_negocio, :ciudad, :estado,
          :calle, :numero_exterior, :numero_interior, :colonia, :codigo_postal, :referencias, :requiere_factura, :rfc, :razon_social, :regimen_fiscal,
          :uso_cfdi, :codigo_postal_fiscal, :correo_facturacion, :comentarios_facturacion, 'general', :categorias_interes_json, NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), empresa=VALUES(empresa), nombre_negocio=VALUES(nombre_negocio), telefono=VALUES(telefono), whatsapp=VALUES(whatsapp), tipo_cliente=VALUES(tipo_cliente),
          tipo_negocio=VALUES(tipo_negocio), ciudad=VALUES(ciudad), estado=VALUES(estado), calle=VALUES(calle), numero_exterior=VALUES(numero_exterior),
          numero_interior=VALUES(numero_interior), colonia=VALUES(colonia), codigo_postal=VALUES(codigo_postal), referencias=VALUES(referencias),
          requiere_factura=VALUES(requiere_factura), rfc=VALUES(rfc), razon_social=VALUES(razon_social), regimen_fiscal=VALUES(regimen_fiscal),
          uso_cfdi=VALUES(uso_cfdi), codigo_postal_fiscal=VALUES(codigo_postal_fiscal), correo_facturacion=VALUES(correo_facturacion),
          comentarios_facturacion=VALUES(comentarios_facturacion),
          estatus='aprobado', contrasenia_hash=COALESCE(VALUES(contrasenia_hash), contrasenia_hash), categorias_interes_json=VALUES(categorias_interes_json), fecha_aprobacion=COALESCE(fecha_aprobacion, NOW()), fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":nombre" => $solicitud["nombre"],
        ":empresa" => $empresa,
        ":nombre_negocio" => $this->textoNullable($this->valor($solicitud, "nombre_negocio", $empresa), 180),
        ":correo" => strtolower($solicitud["correo"]),
        ":telefono" => $solicitud["telefono"],
        ":whatsapp" => $this->textoNullable($this->valor($solicitud, "whatsapp", ""), 40),
        ":tipo" => $tipo,
        ":hash" => $hash,
        ":tipo_negocio" => $this->textoNullable($this->valor($solicitud, "tipo_negocio", ""), 60),
        ":ciudad" => $this->textoNullable($this->valor($solicitud, "ciudad", ""), 120),
        ":estado" => $this->textoNullable($this->valor($solicitud, "estado", ""), 120),
        ":calle" => $this->textoNullable($this->valor($solicitud, "calle", ""), 180),
        ":numero_exterior" => $this->textoNullable($this->valor($solicitud, "numero_exterior", ""), 40),
        ":numero_interior" => $this->textoNullable($this->valor($solicitud, "numero_interior", ""), 40),
        ":colonia" => $this->textoNullable($this->valor($solicitud, "colonia", ""), 120),
        ":codigo_postal" => $this->textoNullable($this->valor($solicitud, "codigo_postal", ""), 20),
        ":referencias" => $this->textoNullable($this->valor($solicitud, "referencias", ""), 2000),
        ":requiere_factura" => intval($this->valor($facturacionSolicitud, "requiere_factura", $this->valor($datosSolicitud, "requiere_factura", 0))) === 1 ? 1 : 0,
        ":rfc" => $this->textoNullable(strtoupper((string) $this->valor($solicitud, "rfc", $this->valor($facturacionSolicitud, "rfc", ""))), 20),
        ":razon_social" => $this->textoNullable($this->valor($facturacionSolicitud, "razon_social", ""), 220),
        ":regimen_fiscal" => $this->textoNullable($this->valor($facturacionSolicitud, "regimen_fiscal", ""), 120),
        ":uso_cfdi" => $this->textoNullable(strtoupper((string) $this->valor($facturacionSolicitud, "uso_cfdi", "")), 20),
        ":codigo_postal_fiscal" => $this->textoNullable($this->valor($facturacionSolicitud, "codigo_postal_fiscal", ""), 20),
        ":correo_facturacion" => $this->textoNullable(strtolower((string) $this->valor($facturacionSolicitud, "correo_facturacion", "")), 180),
        ":comentarios_facturacion" => $this->textoNullable($this->valor($facturacionSolicitud, "comentarios_facturacion", ""), 2000),
        ":categorias_interes_json" => json_encode($categoriasInteres, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
      ));
      $stmtCliente = $db->prepare("SELECT id_cliente_distribucion FROM erp_distribucion_clientes WHERE correo=:correo LIMIT 1");
      $stmtCliente->execute(array(":correo" => strtolower($solicitud["correo"])));
      $idCliente = intval($stmtCliente->fetchColumn());
      $db->prepare("UPDATE erp_distribucion_solicitudes
        SET estatus='aprobado', id_cliente_distribucion=:cliente, fecha_actualizacion=NOW()
        WHERE id_solicitud_distribucion=:solicitud")
        ->execute(array(":cliente" => $idCliente, ":solicitud" => $idSolicitud));
      if ($idLista > 0) {
        $this->aplicarListaCliente($db, $idCliente, $idLista);
      }
      if (!empty($permisos)) {
        $this->aplicarPermisosCliente($db, $idCliente, $permisos);
      }
      $activacion = $this->crearTokenActivacionContrasenia($db, $idCliente);
      $this->registrarAuditoria($db, "cliente", $idCliente, "aprobar", "ok", "Cliente Distribucion aprobado", array(
        "id_solicitud_distribucion" => $idSolicitud,
        "tipo_cliente" => $tipo,
        "contrasenia_recibida" => $hash !== null,
        "lista_asignada" => $idLista > 0,
        "id_lista_precio" => $idLista > 0 ? $idLista : null,
        "permisos_asignados" => !empty($permisos),
        "permisos" => $permisos,
        "token_activacion_generado" => true,
        "token_activacion_expira" => $activacion["fecha_expiracion"]
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Cliente Distribucion aprobado", array(
        "ejecutado" => true,
        "id_cliente_distribucion" => $idCliente,
        "id_solicitud_distribucion" => $idSolicitud,
        "tipo_cliente" => $tipo,
        "id_lista_precio" => $idLista > 0 ? $idLista : null,
        "permisos" => $permisos,
        "lista_asignada_explicitamente" => $idLista > 0,
        "permisos_asignados_explicitamente" => !empty($permisos),
        "activacion" => array(
          "url" => $activacion["url"],
          "fecha_expiracion" => $activacion["fecha_expiracion"],
          "mensaje_whatsapp" => $this->mensajeActivacionWhatsApp($solicitud, $activacion["url"])
        )
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo aprobar cliente Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: consultar token de activacion para que Distribucion muestre formulario de crear contrasenia.
   * Impacto: Autenticacion externa; no inicia sesion ni expone hash.
   * Contrato: POST JSON `{ token }`; token de un solo proposito y con expiracion.
   */
  public function activacionConsultar($datos = array()) {
    $token = trim((string) $this->valor($datos, "token", ""));
    if ($token === "") {
      return $this->respuesta(true, "warning", "Token requerido", array("valido" => false));
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes") || !$this->tablaExiste($db, "erp_distribucion_tokens")) {
      return $this->respuesta(true, "warning", "Activacion Distribucion no disponible", array("configurado" => false, "valido" => false));
    }
    $cliente = $this->clientePorTokenActivacion($db, $token);
    if (!$cliente) {
      $this->registrarAuditoria($db, "auth", null, "activar_consultar", "error", "Link de activacion invalido o vencido", $this->detalleAcceso(array(), array(
        "token_hash" => hash("sha256", $token)
      )), null, null);
      return $this->respuesta(true, "warning", "Link de activacion invalido o vencido", array("valido" => false));
    }
    $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "activar_consultar", "ok", "Link de activacion consultado", $this->detalleAcceso(array(), array(
      "correo" => $cliente["correo"]
    )), null, intval($cliente["id_cliente_distribucion"]));
    return $this->respuesta(false, "success", "Link de activacion valido", array(
      "valido" => true,
      "cliente" => array(
        "nombre" => $cliente["nombre"],
        "empresa" => $cliente["empresa"],
        "correo" => $cliente["correo"],
        "estatus" => $cliente["estatus"]
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: definir contrasenia inicial desde token de activacion enviado manualmente por WhatsApp.
   * Impacto: Autenticacion externa; consume token y no crea sesion automaticamente.
   * Contrato: POST JSON `{ token, contrasenia }`; despues el cliente debe usar `/auth/login`.
   */
  public function activarContrasenia($datos = array(), $contexto = array()) {
    $token = trim((string) $this->valor($datos, "token", ""));
    $contrasenia = (string) $this->valor($datos, "contrasenia", "");
    if ($token === "" || strlen($contrasenia) < 8) {
      return $this->respuesta(true, "warning", "Token o contrasenia invalida", array("minimo_contrasenia" => 8));
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste($db, "erp_distribucion_clientes") || !$this->tablaExiste($db, "erp_distribucion_tokens")) {
      return $this->respuesta(true, "warning", "Activacion Distribucion no disponible", array("configurado" => false));
    }
    try {
      $cliente = $this->clientePorTokenActivacion($db, $token);
      if (!$cliente) {
        $this->registrarAuditoria($db, "auth", null, "activar_contrasenia", "error", "Intento de activar contrasenia con link invalido o vencido", $this->detalleAcceso($contexto, array(
          "token_hash" => hash("sha256", $token)
        )), null, null);
        return $this->respuesta(true, "warning", "Link de activacion invalido o vencido");
      }
      $db->beginTransaction();
      $hash = password_hash($contrasenia, PASSWORD_DEFAULT);
      $db->prepare("UPDATE erp_distribucion_clientes SET contrasenia_hash=:hash, fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
        ->execute(array(":hash" => $hash, ":cliente" => intval($cliente["id_cliente_distribucion"])));
      $db->prepare("UPDATE erp_distribucion_tokens SET estatus='usado', fecha_ultimo_uso=NOW() WHERE token_hash=:token_hash AND tipo_token='activacion_contrasenia'")
        ->execute(array(":token_hash" => hash("sha256", $token)));
      $this->registrarAuditoria($db, "cliente", intval($cliente["id_cliente_distribucion"]), "activar_contrasenia", "ok", "Contrasenia Distribucion definida por cliente", array(
        "ip" => $this->ipContexto($contexto),
        "user_agent" => $this->userAgentContexto($contexto)
      ), null, intval($cliente["id_cliente_distribucion"]));
      $db->commit();
      return $this->respuesta(false, "success", "Contrasenia creada. Ya puedes iniciar sesion.", array(
        "activado" => true,
        "requiere_login" => true,
        "correo" => $cliente["correo"]
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo activar la contrasenia", array("detalle" => "error_controlado"));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: generar/reemitir link de activacion de contrasenia para envio manual por WhatsApp/correo.
   * Impacto: Admin ERP Distribucion; no inicia sesion y revoca activaciones previas activas.
   * Contrato: escritura auditada; requiere cliente aprobado.
   */
  public function clienteActivacionLinkInterno($datos = array(), $idUsuario = null) {
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    $idSolicitud = intval($this->valor($datos, "id_solicitud_distribucion", 0));
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema Distribucion no disponible", array("configurado" => false));
    }
    try {
      if ($idCliente <= 0 && $idSolicitud > 0) {
        $solicitud = $this->buscarSolicitud($db, $idSolicitud);
        $idCliente = $solicitud ? intval($this->valor($solicitud, "id_cliente_distribucion", 0)) : 0;
      }
      if ($idCliente <= 0) {
        return $this->respuesta(true, "warning", "Cliente aprobado requerido");
      }
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente || (string) $cliente["estatus"] !== "aprobado") {
        return $this->respuesta(true, "warning", "Cliente aprobado requerido");
      }
      $db->beginTransaction();
      $activacion = $this->crearTokenActivacionContrasenia($db, $idCliente);
      $this->registrarAuditoria($db, "cliente", $idCliente, "generar_link_activacion", "ok", "Link de activacion Distribucion generado", array(
        "fecha_expiracion" => $activacion["fecha_expiracion"],
        "id_solicitud_distribucion" => $idSolicitud ?: null
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Link de activacion generado", array(
        "id_cliente_distribucion" => $idCliente,
        "url" => $activacion["url"],
        "fecha_expiracion" => $activacion["fecha_expiracion"],
        "mensaje_whatsapp" => $this->mensajeActivacionWhatsApp($cliente, $activacion["url"]),
        "cliente" => array(
          "nombre" => $cliente["nombre"],
          "empresa" => $cliente["empresa"],
          "correo" => $cliente["correo"],
          "telefono" => $cliente["telefono"],
          "estatus" => $cliente["estatus"]
        )
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo generar link de activacion", array("detalle" => "error_controlado"));
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
      $lista = $this->listaPrecioActiva($db, $idLista);
      if (!$lista) {
        return $this->respuesta(true, "warning", "Lista de precio no activa");
      }
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $this->aplicarListaCliente($db, $idCliente, $idLista);
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
    $permisos = $this->normalizarPermisos($this->valor($datos, "permisos", array()));
    $validacionPermisos = $this->validarPermisosComerciales($permisos);
    if ($validacionPermisos !== true) {
      return $this->respuesta(true, "warning", "Permiso comercial no valido", array("permiso" => $validacionPermisos));
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
      $this->aplicarPermisosCliente($db, $idCliente, $permisos);
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

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: guardar configuracion logistica default del cliente Distribucion.
   * Impacto: Admin ERP; permite proponer envio/recoger y costo base en pedidos futuros.
   * Contrato: escritura auditada sobre cliente externo, no crea pedidos ni ventas.
   */
  public function entregaClientePlanInterno($datos = array(), $idUsuario = null) {
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $metodo = trim((string) $this->valor($datos, "metodo_entrega_default", $this->valor($datos, "tipo_entrega", "por_definir")));
    if (!in_array($metodo, array("por_definir", "envio", "recoger_tienda"), true)) {
      $metodo = "por_definir";
    }
    $habilitarEnvio = intval($this->valor($datos, "entrega_habilitar_envio", 1)) === 1 ? 1 : 0;
    $habilitarRecoger = intval($this->valor($datos, "entrega_habilitar_recoger_tienda", 1)) === 1 ? 1 : 0;
    if ($metodo === "envio" && $habilitarEnvio !== 1) { $metodo = "por_definir"; }
    if ($metodo === "recoger_tienda" && $habilitarRecoger !== 1) { $metodo = "por_definir"; }
    $costoEnvio = max(0, floatval($this->valor($datos, "costo_envio_default", $this->valor($datos, "costo_envio", 0))));
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasEntregaClienteDisponibles($db)) {
      return $this->respuesta(true, "warning", "Configuracion de entrega pendiente de esquema", array("configurado" => false, "requiere_plan_esquema" => true));
    }
    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("UPDATE erp_distribucion_clientes
        SET metodo_entrega_default=:metodo,
            entrega_habilitar_envio=:habilitar_envio,
            entrega_habilitar_recoger_tienda=:habilitar_recoger,
            costo_envio_default=:costo_envio,
            fecha_actualizacion=NOW()
        WHERE id_cliente_distribucion=:cliente")
        ->execute(array(
          ":metodo" => $metodo,
          ":habilitar_envio" => $habilitarEnvio,
          ":habilitar_recoger" => $habilitarRecoger,
          ":costo_envio" => $costoEnvio,
          ":cliente" => $idCliente
        ));
      $detalle = array(
        "metodo_entrega_default" => $metodo,
        "entrega_habilitar_envio" => $habilitarEnvio,
        "entrega_habilitar_recoger_tienda" => $habilitarRecoger,
        "costo_envio_default" => $costoEnvio
      );
      $this->registrarAuditoria($db, "cliente", $idCliente, "configurar_entrega", "ok", "Entrega Distribucion actualizada", $detalle, $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Entrega del cliente actualizada", array("ejecutado" => true, "id_cliente_distribucion" => $idCliente) + $detalle);
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo actualizar entrega del cliente", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: guardar modo de catalogo e intereses por cliente Distribucion.
   * Impacto: Admin ERP; habilita catalogos generales o personalizados sin tocar productos globales.
   * Contrato: escritura auditada sobre cliente externo.
   */
  public function catalogoPreferenciasPlanInterno($datos = array(), $idUsuario = null) {
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $modo = $this->catalogoModoNormalizado($this->valor($datos, "catalogo_modo", $this->valor($datos, "modo", "general")));
    $categorias = $this->normalizarCategoriasInteres($this->valor($datos, "categorias_interes", $this->valor($datos, "categorias", array())));
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->columnasCatalogoClienteDisponibles($db)) {
      return $this->respuesta(true, "warning", "Preferencias de catalogo pendientes de esquema", array("configurado" => false));
    }
    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("UPDATE erp_distribucion_clientes
        SET catalogo_modo=:modo, categorias_interes_json=:categorias, fecha_actualizacion=NOW()
        WHERE id_cliente_distribucion=:cliente")
        ->execute(array(
          ":modo" => $modo,
          ":categorias" => json_encode($categorias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ":cliente" => $idCliente
        ));
      $this->registrarAuditoria($db, "cliente_catalogo", $idCliente, "configurar_preferencias", "ok", "Preferencias de catalogo Distribucion actualizadas", array(
        "catalogo_modo" => $modo,
        "categorias_interes" => $categorias
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Preferencias de catalogo actualizadas", array(
        "ejecutado" => true,
        "id_cliente_distribucion" => $idCliente,
        "catalogo_modo" => $modo,
        "categorias_interes" => $categorias
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudieron actualizar preferencias de catalogo", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: consultar reglas de visibilidad por cliente Distribucion.
   * Impacto: Admin ERP; permite revisar permitidos y ocultos por SKU/categoria/marca.
   */
  public function catalogoReglasInternas($filtros = array()) {
    $idCliente = intval($this->valor($filtros, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido", array("items" => array()));
    }
    $db = $this->getConexion();
    if (!$this->tablaExiste($db, "erp_distribucion_cliente_catalogo_reglas")) {
      return $this->respuesta(false, "warning", "Reglas de catalogo pendientes de esquema", array("configurado" => false, "items" => array()));
    }
    try {
      $stmt = $db->prepare("SELECT r.id_cliente_catalogo_regla, r.id_cliente_distribucion, r.tipo_regla, r.objeto_clave,
          r.id_sku, r.id_categoria_erp, r.id_marca_erp, r.accion, r.prioridad, r.estatus, r.origen, r.notas,
          r.fecha_registro, r.fecha_actualizacion,
          s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) sku_nombre,
          c.nombre categoria_nombre, c.ruta categoria_ruta,
          m.nombre marca_nombre
        FROM erp_distribucion_cliente_catalogo_reglas r
        LEFT JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku
        LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=r.id_categoria_erp
        LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=r.id_marca_erp
        WHERE r.id_cliente_distribucion=:cliente
        ORDER BY r.estatus ASC, r.accion ASC, r.tipo_regla ASC, r.prioridad DESC, r.id_cliente_catalogo_regla DESC
        LIMIT 500");
      $stmt->execute(array(":cliente" => $idCliente));
      return $this->respuesta(false, "success", "Reglas de catalogo consultadas", array(
        "configurado" => true,
        "id_cliente_distribucion" => $idCliente,
        "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar reglas de catalogo", array("detalle" => "error_controlado", "items" => array()));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: guardar regla de permitir/ocultar catalogo por cliente.
   * Impacto: Admin ERP; no afecta catalogo global ni otros clientes.
   */
  public function catalogoReglaGuardarInterna($datos = array(), $idUsuario = null) {
    $idCliente = intval($this->valor($datos, "id_cliente_distribucion", 0));
    if ($idCliente <= 0) {
      return $this->respuesta(true, "warning", "Cliente requerido");
    }
    $tipo = trim((string) $this->valor($datos, "tipo_regla", $this->valor($datos, "tipo", "")));
    $tipo = in_array($tipo, array("sku", "categoria", "marca"), true) ? $tipo : "";
    $accion = trim((string) $this->valor($datos, "accion", "permitir"));
    $accion = in_array($accion, array("permitir", "ocultar"), true) ? $accion : "permitir";
    $estatus = trim((string) $this->valor($datos, "estatus", "activo"));
    $estatus = in_array($estatus, array("activo", "inactivo"), true) ? $estatus : "activo";
    $idSku = intval($this->valor($datos, "id_sku", 0));
    $idCategoria = intval($this->valor($datos, "id_categoria_erp", $this->valor($datos, "id_categoria", 0)));
    $idMarca = intval($this->valor($datos, "id_marca_erp", $this->valor($datos, "id_marca", 0)));
    if ($tipo === "" || ($tipo === "sku" && $idSku <= 0) || ($tipo === "categoria" && $idCategoria <= 0) || ($tipo === "marca" && $idMarca <= 0)) {
      return $this->respuesta(true, "warning", "Regla de catalogo incompleta");
    }
    $objetoClave = $tipo . ":" . ($tipo === "sku" ? $idSku : ($tipo === "categoria" ? $idCategoria : $idMarca));
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->tablaExiste($db, "erp_distribucion_cliente_catalogo_reglas")) {
      return $this->respuesta(true, "warning", "Reglas de catalogo pendientes de esquema", array("configurado" => false));
    }
    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      $db->prepare("INSERT INTO erp_distribucion_cliente_catalogo_reglas
        (id_cliente_distribucion, tipo_regla, objeto_clave, id_sku, id_categoria_erp, id_marca_erp, accion, prioridad, estatus, origen, notas, fecha_registro, fecha_actualizacion)
        VALUES (:cliente, :tipo, :objeto, :sku, :categoria, :marca, :accion, :prioridad, :estatus, :origen, :notas, NOW(), NOW())
        ON DUPLICATE KEY UPDATE id_sku=VALUES(id_sku), id_categoria_erp=VALUES(id_categoria_erp), id_marca_erp=VALUES(id_marca_erp),
          prioridad=VALUES(prioridad), estatus=VALUES(estatus), origen=VALUES(origen), notas=VALUES(notas), fecha_actualizacion=NOW()")
        ->execute(array(
          ":cliente" => $idCliente,
          ":tipo" => $tipo,
          ":objeto" => $objetoClave,
          ":sku" => $tipo === "sku" ? $idSku : null,
          ":categoria" => $tipo === "categoria" ? $idCategoria : null,
          ":marca" => $tipo === "marca" ? $idMarca : null,
          ":accion" => $accion,
          ":prioridad" => max(0, min(999, intval($this->valor($datos, "prioridad", 0)))),
          ":estatus" => $estatus,
          ":origen" => substr(trim((string) $this->valor($datos, "origen", "admin")), 0, 40),
          ":notas" => $this->textoNullable($this->valor($datos, "notas", null), 2000)
        ));
      $this->registrarAuditoria($db, "cliente_catalogo", $idCliente, "guardar_regla", "ok", "Regla de catalogo Distribucion guardada", array(
        "tipo_regla" => $tipo,
        "objeto_clave" => $objetoClave,
        "accion" => $accion,
        "estatus" => $estatus
      ), $idUsuario, $idCliente);
      $db->commit();
      return $this->respuesta(false, "success", "Regla de catalogo guardada", array(
        "ejecutado" => true,
        "id_cliente_distribucion" => $idCliente,
        "tipo_regla" => $tipo,
        "objeto_clave" => $objetoClave,
        "accion" => $accion,
        "estatus" => $estatus
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo guardar regla de catalogo", array("detalle" => "error_controlado"));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: recibir cambios de perfil desde el portal externo separando simples y sensibles.
   * Impacto: Mi cuenta Distribucion; aplica contacto basico con auditoria y deja datos comerciales/fiscales en revision.
   * Contrato: POST autenticado; no permite cambiar lista, permisos, tipo_cliente ni estatus.
   */
  public function solicitarCambioPerfil($datos = array(), $contexto = array()) {
    if (empty($contexto["autenticado"]) || intval($this->valor($contexto, "id_cliente_distribucion", 0)) <= 0) {
      return $this->respuesta(true, "warning", "Debes iniciar sesion para solicitar cambios", array("requiere_autenticacion" => true));
    }
    $db = $this->getConexion();
    if (!$this->esquemaOperativo($db) || !$this->tablaExiste($db, "erp_distribucion_cliente_solicitudes_cambio") || !$this->columnasPerfilClienteDisponibles($db)) {
      return $this->respuesta(true, "warning", "Mi cuenta Distribucion pendiente de esquema", array("configurado" => false));
    }
    $idCliente = intval($this->valor($contexto, "id_cliente_distribucion", 0));
    $simplesPermitidos = array("telefono", "whatsapp", "correo_alterno", "contacto_principal");
    $sensiblesPermitidos = array("empresa", "nombre_negocio", "tipo_negocio", "ciudad", "estado", "calle", "numero_exterior", "numero_interior", "colonia", "codigo_postal", "referencias", "requiere_factura", "rfc", "razon_social", "regimen_fiscal", "uso_cfdi", "codigo_postal_fiscal", "correo_facturacion", "comentarios_facturacion");
    $simples = array();
    $sensibles = array();

    foreach ($simplesPermitidos as $campo) {
      if (array_key_exists($campo, $datos)) {
        $simples[$campo] = $this->normalizarCampoPerfil($campo, $datos[$campo]);
      }
    }
    $datosComerciales = $this->valor($datos, "datos_comerciales", array());
    $entrega = $this->valor($datos, "entrega", array());
    $facturacion = $this->valor($datos, "facturacion", array());
    $fuentesSensibles = array(is_array($datos) ? $datos : array(), is_array($datosComerciales) ? $datosComerciales : array(), is_array($entrega) ? $entrega : array(), is_array($facturacion) ? $facturacion : array());
    foreach ($sensiblesPermitidos as $campo) {
      foreach ($fuentesSensibles as $fuente) {
        if (array_key_exists($campo, $fuente)) {
          $sensibles[$campo] = $this->normalizarCampoPerfil($campo, $fuente[$campo]);
          break;
        }
      }
    }
    foreach (array("id_lista_precio", "permisos", "tipo_cliente", "estatus", "catalogo_modo") as $bloqueado) {
      unset($simples[$bloqueado], $sensibles[$bloqueado]);
    }
    if (empty($simples) && empty($sensibles)) {
      return $this->respuesta(true, "warning", "No hay cambios validos para procesar");
    }

    try {
      $db->beginTransaction();
      $cliente = $this->buscarCliente($db, $idCliente);
      if (!$cliente) { throw new Exception("cliente_no_encontrado"); }
      if (!empty($simples)) {
        $sets = array();
        $params = array(":cliente" => $idCliente);
        foreach ($simples as $campo => $valor) {
          $sets[] = $campo . "=:" . $campo;
          $params[":" . $campo] = $valor;
        }
        $sets[] = "fecha_actualizacion=NOW()";
        $db->prepare("UPDATE erp_distribucion_clientes SET " . implode(", ", $sets) . " WHERE id_cliente_distribucion=:cliente")
          ->execute($params);
        $this->registrarAuditoria($db, "cliente", $idCliente, "perfil_contacto_actualizar", "ok", "Contacto Distribucion actualizado desde portal", array(
          "campos" => array_keys($simples)
        ), null, $idCliente);
      }
      $idSolicitud = null;
      if (!empty($sensibles)) {
        $resumen = $this->resumenSolicitudCambio($sensibles);
        $stmt = $db->prepare("INSERT INTO erp_distribucion_cliente_solicitudes_cambio
          (id_cliente_distribucion, tipo, resumen, datos_json, estatus, ip_registro, user_agent, fecha_registro)
          VALUES (:cliente, :tipo, :resumen, :datos, 'pendiente', :ip, :ua, NOW())");
        $stmt->execute(array(
          ":cliente" => $idCliente,
          ":tipo" => "perfil_comercial",
          ":resumen" => $resumen,
          ":datos" => json_encode(array("campos" => $sensibles), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          ":ip" => $this->ipContexto($contexto),
          ":ua" => $this->userAgentContexto($contexto)
        ));
        $idSolicitud = intval($db->lastInsertId());
        $this->registrarAuditoria($db, "cliente", $idCliente, "perfil_solicitar_cambio", "pendiente", "Solicitud de cambio sensible recibida", array(
          "id_solicitud_cambio" => $idSolicitud,
          "campos" => array_keys($sensibles)
        ), null, $idCliente);
      }
      $db->commit();
      return $this->respuesta(false, "success", empty($sensibles) ? "Datos de contacto actualizados" : "Solicitud de cambio recibida", array(
        "ejecutado" => true,
        "cambios_aplicados" => array_keys($simples),
        "requiere_revision" => !empty($sensibles),
        "id_solicitud" => $idSolicitud,
        "estatus" => $idSolicitud ? "pendiente" : "aplicado"
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo procesar la solicitud de cambio", array("detalle" => "error_controlado"));
    }
  }

  private function formatearPerfilCliente($db, $cliente, $permisos, $acciones) {
    $idCliente = intval($this->valor($cliente, "id_cliente_distribucion", 0));
    return array(
      "id_cliente_distribucion" => $idCliente,
      "nombre" => $this->valor($cliente, "nombre", ""),
      "correo" => $this->valor($cliente, "correo", ""),
      "tipo_cliente" => $this->valor($cliente, "tipo_cliente", ""),
      "estatus" => $this->valor($cliente, "estatus", ""),
      "id_lista_precio" => intval($this->valor($cliente, "id_lista_precio", 0)),
      "campos_pendientes" => $this->camposPendientesCliente($cliente),
      "estado_acceso" => $this->estadoAccesoCliente($this->valor($cliente, "estatus", ""), $acciones),
      "catalogo_modo" => $this->catalogoModoNormalizado($this->valor($cliente, "catalogo_modo", "general")),
      "categorias_interes" => $this->jsonArray($this->valor($cliente, "categorias_interes_json", "")),
      "contacto" => array(
        "telefono" => $this->valor($cliente, "telefono", ""),
        "whatsapp" => $this->valor($cliente, "whatsapp", ""),
        "correo" => $this->valor($cliente, "correo", ""),
        "correo_alterno" => $this->valor($cliente, "correo_alterno", ""),
        "contacto_principal" => $this->valor($cliente, "contacto_principal", $this->valor($cliente, "nombre", ""))
      ),
      "datos_comerciales" => array(
        "empresa" => $this->valor($cliente, "empresa", ""),
        "nombre_negocio" => $this->valor($cliente, "nombre_negocio", $this->valor($cliente, "empresa", "")),
        "tipo_negocio" => $this->valor($cliente, "tipo_negocio", ""),
        "ciudad" => $this->valor($cliente, "ciudad", ""),
        "estado" => $this->valor($cliente, "estado", "")
      ),
      "entrega" => array(
        "calle" => $this->valor($cliente, "calle", ""),
        "numero_exterior" => $this->valor($cliente, "numero_exterior", ""),
        "numero_interior" => $this->valor($cliente, "numero_interior", ""),
        "colonia" => $this->valor($cliente, "colonia", ""),
        "ciudad" => $this->valor($cliente, "ciudad", ""),
        "estado" => $this->valor($cliente, "estado", ""),
        "codigo_postal" => $this->valor($cliente, "codigo_postal", ""),
        "referencias" => $this->valor($cliente, "referencias", "")
      ),
      "facturacion" => array(
        "requiere_factura" => intval($this->valor($cliente, "requiere_factura", 0)),
        "rfc" => $this->valor($cliente, "rfc", ""),
        "razon_social" => $this->valor($cliente, "razon_social", ""),
        "regimen_fiscal" => $this->valor($cliente, "regimen_fiscal", ""),
        "uso_cfdi" => $this->valor($cliente, "uso_cfdi", ""),
        "codigo_postal_fiscal" => $this->valor($cliente, "codigo_postal_fiscal", ""),
        "correo_facturacion" => $this->valor($cliente, "correo_facturacion", ""),
        "comentarios_facturacion" => $this->valor($cliente, "comentarios_facturacion", "")
      ),
      "solicitudes_cambio" => $this->solicitudesCambioCliente($db, $idCliente),
      "permisos" => $permisos,
      "acciones" => $acciones
    );
  }

  private function estadoAccesoCliente($estatus, $acciones = array()) {
    $estatus = trim((string) $estatus);
    $estados = array(
      "pendiente" => array(
        "titulo" => "Solicitud recibida",
        "mensaje" => "Tu solicitud esta registrada y pendiente de revision comercial por Artiani.",
        "siguiente_paso" => "Espera la validacion comercial o contacta a Artiani si necesitas actualizar tus datos."
      ),
      "en_revision" => array(
        "titulo" => "Acceso en revision",
        "mensaje" => "Tu solicitud esta en revision. El equipo Artiani debe habilitar tu acceso antes de ver catalogo o precios.",
        "siguiente_paso" => "Espera la aprobacion o completa los datos pendientes."
      ),
      "aprobado" => array(
        "titulo" => "Acceso aprobado",
        "mensaje" => empty($acciones["ver_catalogo"]) ? "Tu cuenta esta aprobada, pero aun no tiene permisos comerciales de catalogo." : "Tu acceso comercial esta activo.",
        "siguiente_paso" => empty($acciones["ver_catalogo"]) ? "Contacta a Artiani para habilitar permisos comerciales." : "Ya puedes usar las acciones disponibles en el portal."
      ),
      "rechazado" => array(
        "titulo" => "Solicitud rechazada",
        "mensaje" => "Por ahora tu solicitud comercial no fue aprobada.",
        "siguiente_paso" => "Contacta a Artiani si necesitas aclarar o actualizar tus datos."
      ),
      "suspendido" => array(
        "titulo" => "Acceso suspendido",
        "mensaje" => "Tu acceso comercial esta suspendido temporalmente.",
        "siguiente_paso" => "Contacta a Artiani para revisar tu cuenta."
      )
    );
    $estado = isset($estados[$estatus]) ? $estados[$estatus] : $estados["pendiente"];
    $estado["contacto_soporte"] = "ventas@artiani.com.mx";
    return $estado;
  }

  private function camposPendientesCliente($cliente) {
    $pendientes = array();
    foreach (array("nombre", "correo", "telefono", "empresa", "ciudad", "estado") as $campo) {
      if (trim((string) $this->valor($cliente, $campo, "")) === "") {
        $pendientes[] = $campo;
      }
    }
    return $pendientes;
  }

  private function codigoLoginPorEstatus($estatus) {
    $mapa = array(
      "pendiente" => "cuenta_pendiente",
      "en_revision" => "cuenta_en_revision",
      "rechazado" => "cuenta_rechazada",
      "suspendido" => "cuenta_suspendida"
    );
    return isset($mapa[$estatus]) ? $mapa[$estatus] : "cuenta_en_revision";
  }

  private function tieneTokenActivacionActivo($db, $idCliente) {
    if (!$this->tablaExiste($db, "erp_distribucion_tokens")) { return false; }
    try {
      $stmt = $db->prepare("SELECT 1 FROM erp_distribucion_tokens WHERE id_cliente_distribucion=:cliente AND tipo_token='activacion_contrasenia' AND estatus='activo' AND fecha_expiracion>=NOW() LIMIT 1");
      $stmt->execute(array(":cliente" => intval($idCliente)));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function respuestaLoginError($codigo, $mensaje, $depurar = array()) {
    $respuesta = $this->respuesta(true, "warning", $mensaje, array_merge(array("codigo" => $codigo), is_array($depurar) ? $depurar : array()));
    $respuesta["codigo"] = $codigo;
    if (isset($depurar["estatus"])) { $respuesta["estatus"] = $depurar["estatus"]; }
    if (isset($depurar["siguiente_paso"])) { $respuesta["siguiente_paso"] = $depurar["siguiente_paso"]; }
    return $respuesta;
  }

  private function solicitudesCambioCliente($db, $idCliente) {
    if (!$this->tablaExiste($db, "erp_distribucion_cliente_solicitudes_cambio")) { return array(); }
    $stmt = $db->prepare("SELECT id_solicitud_cambio id_solicitud, tipo, resumen, estatus, fecha_registro
      FROM erp_distribucion_cliente_solicitudes_cambio
      WHERE id_cliente_distribucion=:cliente
      ORDER BY id_solicitud_cambio DESC
      LIMIT 10");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function normalizarCampoPerfil($campo, $valor) {
    if ($campo === "requiere_factura") { return intval($valor) === 1 ? 1 : 0; }
    $valor = trim((string) $valor);
    if (in_array($campo, array("correo_alterno", "correo_facturacion"), true)) {
      $valor = strtolower($valor);
      return filter_var($valor, FILTER_VALIDATE_EMAIL) ? substr($valor, 0, 180) : "";
    }
    if (in_array($campo, array("rfc", "uso_cfdi"), true)) {
      $valor = strtoupper($valor);
    }
    $maximos = array(
      "telefono" => 40, "whatsapp" => 40, "contacto_principal" => 160, "empresa" => 180, "nombre_negocio" => 180,
      "tipo_negocio" => 60, "ciudad" => 120, "estado" => 120, "calle" => 180, "numero_exterior" => 40,
      "numero_interior" => 40, "colonia" => 120, "codigo_postal" => 20, "rfc" => 20, "razon_social" => 220,
      "regimen_fiscal" => 120, "uso_cfdi" => 20, "codigo_postal_fiscal" => 20
    );
    $max = isset($maximos[$campo]) ? $maximos[$campo] : 2000;
    return substr($valor, 0, $max);
  }

  private function resumenSolicitudCambio($campos) {
    $nombres = array_keys($campos);
    return substr("Cambio de " . implode(", ", array_slice($nombres, 0, 8)) . (count($nombres) > 8 ? " y otros datos" : ""), 0, 255);
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

  private function accionesPermitidasCliente($permisos) {
    if (!class_exists("DistribucionPermisosApi")) {
      require_once RUTA_APP . "/modelos/DistribucionPermisosApi.php";
    }
    return (new DistribucionPermisosApi())->accionesPermitidas($permisos);
  }

  private function crearTokenActivacionContrasenia($db, $idCliente) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash("sha256", $token);
    $fechaExpiracion = date("Y-m-d H:i:s", strtotime("+72 hours"));
    $db->prepare("UPDATE erp_distribucion_tokens
      SET estatus='revocado', fecha_ultimo_uso=NOW()
      WHERE id_cliente_distribucion=:cliente AND tipo_token='activacion_contrasenia' AND estatus='activo'")
      ->execute(array(":cliente" => intval($idCliente)));
    $stmt = $db->prepare("INSERT INTO erp_distribucion_tokens
      (id_cliente_distribucion, token_hash, tipo_token, estatus, ip_creacion, user_agent, fecha_expiracion, fecha_registro)
      VALUES (:cliente, :token_hash, 'activacion_contrasenia', 'activo', NULL, NULL, :expira, NOW())");
    $stmt->execute(array(
      ":cliente" => intval($idCliente),
      ":token_hash" => $tokenHash,
      ":expira" => $fechaExpiracion
    ));
    return array(
      "token" => $token,
      "url" => $this->urlActivacion($token),
      "fecha_expiracion" => $fechaExpiracion
    );
  }

  private function urlActivacion($token) {
    return rtrim($this->baseFrontendDistribucion(), "/") . "/activar-cuenta?token=" . rawurlencode($token);
  }

  private function baseFrontendDistribucion() {
    if (defined("DISTRIBUCION_FRONTEND_URL")) {
      $configurada = trim((string) DISTRIBUCION_FRONTEND_URL);
      if ($configurada !== "") {
        return $configurada;
      }
    }
    $env = trim((string) getenv("DISTRIBUCION_FRONTEND_URL"));
    if ($env !== "") {
      return $env;
    }
    $host = isset($_SERVER["HTTP_HOST"]) ? strtolower(trim((string) $_SERVER["HTTP_HOST"])) : "";
    $server = isset($_SERVER["SERVER_NAME"]) ? strtolower(trim((string) $_SERVER["SERVER_NAME"])) : "";
    $host = preg_replace('/:\d+$/', '', $host);
    $server = preg_replace('/:\d+$/', '', $server);
    $rutaUrl = defined("RUTA_URL") ? strtolower((string) RUTA_URL) : "";
    if (
      in_array($host, array("localhost", "panel.com.local", "dashboard.com.local"), true)
      || in_array($server, array("localhost", "panel.com.local", "dashboard.com.local"), true)
      || strpos($host, ".com.local") !== false
      || strpos($server, ".com.local") !== false
      || strpos($rutaUrl, ".com.local") !== false
    ) {
      return "http://distribucion.artiani.com.local";
    }
    return "https://distribucion.artiani.com.mx";
  }

  private function mensajeActivacionWhatsApp($cliente, $url) {
    $nombre = trim((string) $this->valor($cliente, "nombre", ""));
    $correo = trim((string) $this->valor($cliente, "correo", ""));
    if ($nombre === "") { $nombre = "buen dia"; }
    $lineas = array(
      "Hola " . $nombre . ", tu solicitud de acceso mayorista Artiani ya fue aprobada.",
      "Para crear tu contrasenia entra a este link:",
      $url,
      $correo !== "" ? "Tu usuario sera este correo: " . $correo . "." : "Ahi podras activar tu acceso.",
      "El link vence en 72 horas. Si necesitas apoyo, te ayudamos por este medio."
    );
    return implode("\n", $lineas);
  }

  private function clientePorTokenActivacion($db, $token) {
    $stmt = $db->prepare("SELECT c.id_cliente_distribucion, c.nombre, c.empresa, c.correo, c.telefono, c.tipo_cliente, c.estatus
      FROM erp_distribucion_tokens t
      INNER JOIN erp_distribucion_clientes c ON c.id_cliente_distribucion=t.id_cliente_distribucion
      WHERE t.token_hash=:token_hash
        AND t.tipo_token='activacion_contrasenia'
        AND t.estatus='activo'
        AND t.fecha_expiracion>=NOW()
        AND c.estatus='aprobado'
      LIMIT 1");
    $stmt->execute(array(":token_hash" => hash("sha256", trim((string) $token))));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function listaPrecioActiva($db, $idLista) {
    if (!$this->tablaExiste($db, "erp_listas_precios")) { return false; }
    $stmtLista = $db->prepare("SELECT id_lista_precio, nombre, estatus FROM erp_listas_precios WHERE id_lista_precio=:lista LIMIT 1");
    $stmtLista->execute(array(":lista" => intval($idLista)));
    $lista = $stmtLista->fetch(PDO::FETCH_ASSOC);
    return $lista && (string) $lista["estatus"] === "activa" ? $lista : false;
  }

  private function aplicarListaCliente($db, $idCliente, $idLista) {
    $db->prepare("UPDATE erp_distribucion_clientes SET id_lista_precio=:lista, fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
      ->execute(array(":lista" => intval($idLista), ":cliente" => intval($idCliente)));
    $db->prepare("UPDATE erp_distribucion_cliente_listas SET estatus='inactivo', fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente AND estatus='activo'")
      ->execute(array(":cliente" => intval($idCliente)));
    $db->prepare("INSERT INTO erp_distribucion_cliente_listas
      (id_cliente_distribucion, id_lista_precio, prioridad, estatus, fecha_inicio, fecha_registro)
      VALUES (:cliente, :lista, 1, 'activo', NOW(), NOW())")
      ->execute(array(":cliente" => intval($idCliente), ":lista" => intval($idLista)));
  }

  private function validarPermisosComerciales($permisos) {
    require_once RUTA_APP . "/modelos/DistribucionPermisosApi.php";
    $permitidos = (new DistribucionPermisosApi())->permisosComerciales();
    foreach ($permisos as $permiso) {
      if (!in_array($permiso, $permitidos, true)) {
        return $permiso;
      }
    }
    return true;
  }

  private function aplicarPermisosCliente($db, $idCliente, $permisos) {
    $db->prepare("UPDATE erp_distribucion_cliente_permisos SET estatus='inactivo', fecha_actualizacion=NOW() WHERE id_cliente_distribucion=:cliente")
      ->execute(array(":cliente" => intval($idCliente)));
    foreach ($permisos as $permiso) {
      $db->prepare("INSERT INTO erp_distribucion_cliente_permisos
        (id_cliente_distribucion, permiso, estatus, fecha_registro, fecha_actualizacion)
        VALUES (:cliente, :permiso, 'activo', NOW(), NOW())
        ON DUPLICATE KEY UPDATE estatus='activo', fecha_actualizacion=NOW()")
        ->execute(array(":cliente" => intval($idCliente), ":permiso" => $permiso));
    }
  }

  private function normalizarCategoriasInteres($entrada) {
    if (is_string($entrada)) {
      $decodificada = json_decode($entrada, true);
      if (is_array($decodificada)) {
        $entrada = $decodificada;
      } else {
        $entrada = array_filter(array_map("trim", explode(",", $entrada)));
      }
    }
    if (!is_array($entrada)) { return array(); }
    $salida = array();
    foreach (array_slice($entrada, 0, 40) as $item) {
      if (is_array($item)) {
        $id = intval($this->valor($item, "id_categoria_erp", $this->valor($item, "id", 0)));
        $nombre = substr(trim((string) $this->valor($item, "nombre", $this->valor($item, "categoria", ""))), 0, 180);
        $ruta = substr(trim((string) $this->valor($item, "ruta", "")), 0, 240);
        $incluyeDescendientes = intval($this->valor($item, "incluye_descendientes", $this->valor($item, "seleccion_incluye_descendientes", 1))) === 1;
      } else {
        $id = is_numeric($item) ? intval($item) : 0;
        $nombre = is_numeric($item) ? "" : substr(trim((string) $item), 0, 180);
        $ruta = "";
        $incluyeDescendientes = true;
      }
      if ($id <= 0 && $nombre === "") { continue; }
      $clave = $id > 0 ? "id:" . $id : "nombre:" . strtolower($nombre);
      $salida[$clave] = array(
        "id_categoria_erp" => $id > 0 ? $id : null,
        "nombre" => $nombre,
        "ruta" => $ruta,
        "incluye_descendientes" => $incluyeDescendientes
      );
    }
    return array_values($salida);
  }

  private function jsonArray($valor) {
    if (is_array($valor)) { return $valor; }
    $valor = trim((string) $valor);
    if ($valor === "") { return array(); }
    $json = json_decode($valor, true);
    return is_array($json) ? $json : array();
  }

  private function catalogoModoNormalizado($modo) {
    $modo = trim((string) $modo);
    return in_array($modo, array("general", "personalizado"), true) ? $modo : "general";
  }

  private function textoNullable($valor, $max) {
    $valor = trim((string) $valor);
    return $valor === "" ? null : substr($valor, 0, intval($max));
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
      && $this->tablaExiste($db, "erp_distribucion_cliente_catalogo_reglas")
      && $this->tablaExiste($db, "erp_distribucion_cliente_solicitudes_cambio")
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

  private function detalleAcceso($contexto = array(), $extra = array()) {
    $detalle = array(
      "ip" => $this->ipContexto($contexto),
      "user_agent" => $this->userAgentContexto($contexto)
    );
    if (is_array($extra)) {
      foreach ($extra as $clave => $valor) {
        if ($clave === "contrasenia" || $clave === "token") { continue; }
        $detalle[$clave] = $valor;
      }
    }
    return $detalle;
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

  private function columnaExiste($db, $tabla, $columna) {
    try {
      $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function columnasSolicitudComercialListas($db) {
    $columnas = array("nombre_negocio", "whatsapp", "rfc", "ciudad", "estado", "tipo_negocio", "calle", "numero_exterior", "numero_interior", "colonia", "codigo_postal", "referencias", "intereses_comerciales", "categorias_interes_json", "datos_comerciales_json", "ip_registro", "user_agent");
    foreach ($columnas as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_solicitudes", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasEntregaClienteDisponibles($db) {
    foreach (array("metodo_entrega_default", "entrega_habilitar_envio", "entrega_habilitar_recoger_tienda", "costo_envio_default") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_clientes", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasCatalogoClienteDisponibles($db) {
    foreach (array("catalogo_modo", "categorias_interes_json") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_clientes", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function columnasPerfilClienteDisponibles($db) {
    foreach (array("whatsapp", "correo_alterno", "contacto_principal", "nombre_negocio", "tipo_negocio", "ciudad", "estado", "calle", "numero_exterior", "numero_interior", "colonia", "codigo_postal", "referencias", "requiere_factura", "rfc", "razon_social", "regimen_fiscal", "uso_cfdi", "codigo_postal_fiscal", "correo_facturacion", "comentarios_facturacion") as $columna) {
      if (!$this->columnaExiste($db, "erp_distribucion_clientes", $columna)) {
        return false;
      }
    }
    return true;
  }

  private function ipContexto($contexto) {
    $ip = is_array($contexto) ? trim((string) $this->valor($contexto, "ip", "")) : "";
    if ($ip === "" && isset($_SERVER["REMOTE_ADDR"])) { $ip = (string) $_SERVER["REMOTE_ADDR"]; }
    return substr($ip, 0, 80);
  }

  private function userAgentContexto($contexto) {
    $ua = is_array($contexto) ? trim((string) $this->valor($contexto, "user_agent", "")) : "";
    if ($ua === "" && isset($_SERVER["HTTP_USER_AGENT"])) { $ua = (string) $_SERVER["HTTP_USER_AGENT"]; }
    return substr($ua, 0, 255);
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    $respuesta = array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    if (is_array($depurar)) {
      foreach ($depurar as $clave => $valor) {
        if (!array_key_exists($clave, $respuesta)) {
          $respuesta[$clave] = $valor;
        }
      }
    }
    return $respuesta;
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }
}
