<?php

class EcommerceAnalyticsErp extends CRUD {

  private $eventosPermitidos = array(
    "page_view",
    "view_product",
    "search",
    "select_mascota",
    "select_necesidad",
    "add_to_quote",
    "remove_from_quote",
    "quote_dryrun",
    "quote_preflight",
    "open_whatsapp",
    "facturacion_view",
    "facturacion_submit"
  );

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: entregar contrato publico de analytics ecommerce para frontend externo.
   * Impacto: evita que el frontend lea docs/archivos internos y fija payloads anonimos sin persistencia real aun.
   * Contrato: solo lectura; no escribe BD ni expone datos internos.
   */
  public function contratoFrontend() {
    return $this->respuesta(false, "success", "Contrato Ecommerce / Analytics", array(
      "version" => "fase1-analytics-readonly-2026-08-04",
      "estado" => "preflight_sin_persistencia",
      "endpoints_publicos" => array(
        array("metodo" => "POST", "ruta" => "/ecommercePublico/analytics_sesion", "uso" => "Validar/normalizar sesion anonima."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/evento_navegacion", "uso" => "Validar evento anonimo."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/busqueda_registrar", "uso" => "Validar busqueda anonima."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/analytics_conversion", "uso" => "Validar conversion anonima.")
      ),
      "eventos_permitidos" => $this->eventosPermitidos,
      "datos_permitidos" => array("session_id", "tipo_evento", "canal", "ruta", "referrer", "utm_source", "utm_medium", "utm_campaign", "dispositivo", "mascota", "necesidad", "id_publicacion", "id_sku", "slug", "query", "resultados_total", "sin_resultados", "metadata"),
      "datos_prohibidos" => array("nombre", "telefono", "correo", "email", "rfc", "razon_social", "direccion", "datos_fiscales", "stock_exacto"),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: validar una sesion anonima ecommerce sin crearla todavia.
   * Impacto: prepara persistencia futura con hash irreversible de session_id.
   * Contrato: preflight publico; no escribe BD.
   */
  public function sesionPreflight($datos = array()) {
    $sessionId = $this->sessionIdLimpio($this->valor($datos, "session_id", ""));
    $metadata = is_array($this->valor($datos, "metadata", array())) ? $this->valor($datos, "metadata", array()) : array();
    $bloqueos = array();
    $datosPersonales = $this->detectarDatosPersonales(array_merge($metadata, $datos));
    if ($sessionId === "") { $bloqueos[] = "session_id_anonimo_requerido"; }
    if (!empty($datosPersonales)) { $bloqueos[] = "payload_no_debe_incluir_datos_personales"; }

    $sesion = array(
      "session_id_hash" => $this->hashAnonimo($sessionId),
      "canal" => $this->limpiarToken($this->valor($datos, "canal", "web_publica"), 50),
      "primer_ruta" => $this->limpiarRuta($this->valor($datos, "ruta", "")),
      "referrer" => $this->limpiarRuta($this->valor($datos, "referrer", "")),
      "utm_source" => $this->limpiarToken($this->valor($datos, "utm_source", ""), 120),
      "utm_medium" => $this->limpiarToken($this->valor($datos, "utm_medium", ""), 120),
      "utm_campaign" => $this->limpiarTextoCorto($this->valor($datos, "utm_campaign", ""), 160),
      "dispositivo_aproximado" => $this->dispositivoAproximado($datos),
      "metadata" => $this->limpiarMetadata($metadata)
    );

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Sesion analytics validada sin guardar" : "Sesion analytics con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "sesion_normalizada" => $sesion,
      "datos_personales_detectados" => $datosPersonales,
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? array("INSERT INTO `erp_ecommerce_analytics_sesiones` (`session_id_hash`, `canal`, `primer_ruta`, `referrer`, `utm_source`, `utm_medium`, `utm_campaign`, `dispositivo_aproximado`, `fecha_inicio`) VALUES (...)") : array(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: validar evento anonimo ecommerce sin persistirlo.
   * Impacto: prepara tracking de navegacion, producto, cotizacion, dry-run, preflight, WhatsApp y facturacion.
   * Contrato: preflight publico; no escribe BD, no guarda PII ni stock exacto.
   */
  public function eventoPreflight($datos = array()) {
    $evento = $this->normalizarEvento($datos);
    $bloqueos = $this->bloqueosEvento($evento, $datos);
    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Evento analytics validado sin guardar" : "Evento analytics con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_registra_tracking" => true,
      "evento_normalizado" => $evento,
      "datos_personales_detectados" => $this->detectarDatosPersonales($datos),
      "listo_para_registro_futuro" => empty($bloqueos),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? $this->sqlPlanEvento($evento) : array(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: validar busqueda anonima ecommerce sin persistirla.
   * Impacto: prepara analisis de demanda, faltantes, mascotas y necesidades sin datos personales.
   * Contrato: preflight publico; no escribe BD.
   */
  public function busquedaPreflight($datos = array()) {
    $query = $this->limpiarTextoCorto($this->valor($datos, "query", ""), 180);
    $sessionId = $this->sessionIdLimpio($this->valor($datos, "session_id", ""));
    $filtros = is_array($this->valor($datos, "filtros", array())) ? $this->valor($datos, "filtros", array()) : array();
    $datosPersonales = $this->detectarDatosPersonales(array_merge(array("query" => $query), $filtros, $datos));
    $bloqueos = array();
    if ($query === "") { $bloqueos[] = "query_requerido"; }
    if ($sessionId === "") { $bloqueos[] = "session_id_anonimo_requerido"; }
    if (!empty($datosPersonales)) { $bloqueos[] = "busqueda_no_debe_incluir_datos_personales"; }
    $resultadosTotal = max(0, intval($this->valor($datos, "resultados_total", 0)));
    $busqueda = array(
      "session_id_hash" => $this->hashAnonimo($sessionId),
      "canal" => $this->limpiarToken($this->valor($datos, "canal", "web_publica"), 50),
      "query" => $query,
      "query_normalizada" => $this->normalizarTexto($query),
      "ruta" => $this->limpiarRuta($this->valor($datos, "ruta", "")),
      "mascota" => $this->limpiarToken($this->valor($datos, "mascota", ""), 80),
      "necesidad" => $this->limpiarToken($this->valor($datos, "necesidad", ""), 80),
      "resultados_total" => $resultadosTotal,
      "sin_resultados" => $this->valor($datos, "sin_resultados", null) === null ? $resultadosTotal <= 0 : $this->bool($this->valor($datos, "sin_resultados", false)),
      "filtros" => $this->limpiarMetadata($filtros),
      "metadata" => $this->limpiarMetadata($this->valor($datos, "metadata", array()))
    );

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Busqueda analytics validada sin guardar" : "Busqueda analytics con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_registra_busqueda" => true,
      "busqueda_normalizada" => $busqueda,
      "datos_personales_detectados" => $datosPersonales,
      "listo_para_registro_futuro" => empty($bloqueos),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? array("INSERT INTO `erp_ecommerce_analytics_busquedas` (`session_id_hash`, `canal`, `query`, `query_normalizada`, `ruta`, `mascota`, `necesidad`, `resultados_total`, `sin_resultados`, `filtros_json`, `metadata_json`, `fecha_registro`) VALUES (...)") : array(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: validar conversion anonima ecommerce sin persistirla.
   * Impacto: prepara embudo add-to-quote, dry-run, preflight y WhatsApp sin crear checkout, venta ni inventario.
   * Contrato: preflight publico; no escribe BD.
   */
  public function conversionPreflight($datos = array()) {
    $datos["tipo_evento"] = $this->limpiarToken($this->valor($datos, "tipo_conversion", $this->valor($datos, "tipo_evento", "")), 60);
    $evento = $this->normalizarEvento($datos);
    $conversiones = array("add_to_quote", "remove_from_quote", "quote_dryrun", "quote_preflight", "open_whatsapp", "facturacion_submit");
    $bloqueos = $this->bloqueosEvento($evento, $datos);
    if (!in_array($evento["tipo_evento"], $conversiones, true)) { $bloqueos[] = "tipo_conversion_no_permitido"; }

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Conversion analytics validada sin guardar" : "Conversion analytics con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_crea_checkout" => true,
      "no_crea_venta" => true,
      "no_descuenta_inventario" => true,
      "conversion_normalizada" => $evento,
      "datos_personales_detectados" => $this->detectarDatosPersonales($datos),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? array("INSERT INTO `erp_ecommerce_analytics_conversiones` (`session_id_hash`, `tipo_conversion`, `canal`, `id_publicacion`, `id_sku`, `slug`, `ruta_origen`, `etapa_origen`, `metadata_json`, `fecha_registro`) VALUES (...)") : array(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: consultar dashboard interno read-only de Ecommerce / Analytics.
   * Impacto: permite decisiones de catalogo, navegacion y conversion sin exponer PII ni stock exacto.
   * Contrato: solo lectura; no escribe BD.
   */
  public function dashboardInterno($filtros = array()) {
    $db = $this->getConexion();
    $desde = $this->fechaFiltro($this->valor($filtros, "desde", date("Y-m-d", strtotime("-30 days"))), date("Y-m-d", strtotime("-30 days")));
    $hasta = $this->fechaFiltro($this->valor($filtros, "hasta", date("Y-m-d")), date("Y-m-d"));
    $limite = max(5, min(50, intval($this->valor($filtros, "limite", 10))));
    $tablas = $this->tablasDisponibles($db);
    $depurar = array(
      "read_only" => true,
      "configurado" => in_array(true, $tablas, true),
      "rango" => array("desde" => $desde, "hasta" => $hasta, "limite" => $limite),
      "tablas" => $tablas,
      "fuente_metricas" => "eventos_crudos",
      "resumen" => array("sesiones_total" => 0, "eventos_total" => 0, "busquedas_total" => 0, "busquedas_sin_resultados" => 0, "whatsapp_total" => 0),
      "visitas_por_dia" => array(),
      "urls_mas_vistas" => array(),
      "productos_mas_vistos" => array(),
      "productos_agregados_cotizacion" => array(),
      "busquedas_frecuentes" => array(),
      "busquedas_sin_resultados" => array(),
      "embudo" => $this->embudoVacio(),
      "abandono_por_etapa" => array(),
      "mascotas_consultadas" => array(),
      "necesidades_consultadas" => array(),
      "productos_interes_sin_conversion" => array(),
      "oportunidades_publicacion" => array(),
      "guardrails" => $this->guardrails()
    );
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", $depurar);
    }
    $inicio = $desde . " 00:00:00";
    $fin = $hasta . " 23:59:59";

    try {
      if ($tablas["eventos"]) {
        $this->cargarDashboardEventos($db, $depurar, $inicio, $fin, $limite);
      }
      if ($tablas["busquedas"]) {
        $this->cargarDashboardBusquedas($db, $depurar, $inicio, $fin, $limite);
      }
      if ($tablas["sesiones"]) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_analytics_sesiones WHERE fecha_inicio BETWEEN :inicio AND :fin");
        $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
        $depurar["resumen"]["sesiones_total"] = intval($stmt->fetchColumn());
      }
      if ($tablas["resumen_diario"]) {
        $this->cargarDashboardResumenDiario($db, $depurar, $desde, $hasta);
      }
      $depurar["abandono_por_etapa"] = $this->calcularAbandono($depurar["embudo"]);
      return $this->respuesta(false, "success", "Dashboard Ecommerce / Analytics consultado", $depurar);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), $depurar);
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: planear la activacion de persistencia analytics sin escribir BD.
   * Impacto: permite revisar tablas, tokens, guardrails y contratos antes de habilitar tracking real.
   * Contrato: interno read-only; no registra eventos.
   */
  public function persistenciaPlanInterno($filtros = array()) {
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach ($tablas as $tabla => $existe) {
      if (!$existe) { $faltantes[] = $tabla; }
    }
    return $this->respuesta(false, empty($faltantes) ? "success" : "warning", empty($faltantes) ? "Persistencia analytics lista para autorizacion final" : "Persistencia analytics pendiente de esquema", array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "persistencia_activa" => false,
      "requiere_autorizacion_explicita" => true,
      "token_requerido" => "ECOMMERCE_ANALYTICS_TRACKING",
      "tablas" => $tablas,
      "faltantes" => $faltantes,
      "endpoints_publicos_en_preflight" => array(
        "/ecommercePublico/analytics_sesion",
        "/ecommercePublico/evento_navegacion",
        "/ecommercePublico/busqueda_registrar",
        "/ecommercePublico/analytics_conversion"
      ),
      "orden_activacion" => array(
        "aplicar_ddl_con_respaldo",
        "validar_uat_postcheck_readonly",
        "definir_retencion_y_cookie_consent",
        "activar_rate_limit",
        "habilitar_persistencia_en_codigo_con_token_operativo"
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: registrar sesion analytics solo cuando exista autorizacion explicita.
   * Impacto: prepara escritura real anonima sin datos personales; no se usa por endpoints publicos en Fase 1.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_TRACKING`.
   */
  public function registrarSesionAutorizada($datos = array(), $opciones = array()) {
    $bloqueo = $this->bloqueoPersistenciaAutorizada($opciones, array("sesiones"));
    if ($bloqueo) { return $bloqueo; }
    $preflight = $this->sesionPreflight($datos);
    $bloqueos = $this->valor($this->valor($preflight, "depurar", array()), "bloqueos", array());
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "Sesion analytics no registrada por bloqueos", array("no_escribe_bd" => true, "bloqueos" => $bloqueos, "preflight" => $preflight));
    }
    $sesion = $preflight["depurar"]["sesion_normalizada"];
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_sesiones
          (session_id_hash, canal, primer_ruta, ultimo_ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, fecha_inicio, fecha_ultima_actividad, eventos_total)
        VALUES
          (:session_id_hash, :canal, :primer_ruta, :ultimo_ruta, :referrer, :utm_source, :utm_medium, :utm_campaign, :dispositivo, NOW(), NOW(), 0)
        ON DUPLICATE KEY UPDATE
          ultimo_ruta=VALUES(ultimo_ruta),
          fecha_ultima_actividad=NOW(),
          referrer=COALESCE(referrer, VALUES(referrer)),
          utm_source=COALESCE(utm_source, VALUES(utm_source)),
          utm_medium=COALESCE(utm_medium, VALUES(utm_medium)),
          utm_campaign=COALESCE(utm_campaign, VALUES(utm_campaign)),
          dispositivo_aproximado=COALESCE(dispositivo_aproximado, VALUES(dispositivo_aproximado))");
      $stmt->execute(array(
        ":session_id_hash" => $sesion["session_id_hash"],
        ":canal" => $sesion["canal"],
        ":primer_ruta" => $sesion["primer_ruta"],
        ":ultimo_ruta" => $sesion["primer_ruta"],
        ":referrer" => $sesion["referrer"],
        ":utm_source" => $sesion["utm_source"],
        ":utm_medium" => $sesion["utm_medium"],
        ":utm_campaign" => $sesion["utm_campaign"],
        ":dispositivo" => $sesion["dispositivo_aproximado"]
      ));
      return $this->respuesta(false, "success", "Sesion analytics registrada", array("escribe_bd" => true, "session_id_hash" => $sesion["session_id_hash"], "guardrails" => $this->guardrails()));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: registrar evento analytics solo cuando exista autorizacion explicita.
   * Impacto: escritura anonima futura para navegacion/embudo sin checkout, ventas ni inventario.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_TRACKING`.
   */
  public function registrarEventoAutorizado($datos = array(), $opciones = array()) {
    $bloqueo = $this->bloqueoPersistenciaAutorizada($opciones, array("eventos"));
    if ($bloqueo) { return $bloqueo; }
    $preflight = $this->eventoPreflight($datos);
    $bloqueos = $this->valor($this->valor($preflight, "depurar", array()), "bloqueos", array());
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "Evento analytics no registrado por bloqueos", array("no_escribe_bd" => true, "bloqueos" => $bloqueos, "preflight" => $preflight));
    }
    $evento = $preflight["depurar"]["evento_normalizado"];
    try {
      $db = $this->getConexion();
      $db->beginTransaction();
      $this->upsertSesionLigera($db, $evento);
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_eventos
          (session_id_hash, tipo_evento, canal, ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, mascota, necesidad, id_publicacion, id_sku, slug, metadata_json, fecha_registro)
        VALUES
          (:session_id_hash, :tipo_evento, :canal, :ruta, :referrer, :utm_source, :utm_medium, :utm_campaign, :dispositivo, :mascota, :necesidad, :id_publicacion, :id_sku, :slug, :metadata_json, NOW())");
      $stmt->execute($this->paramsEvento($evento));
      $idEvento = intval($db->lastInsertId());
      if (in_array($evento["tipo_evento"], array("add_to_quote", "remove_from_quote", "quote_dryrun", "quote_preflight", "open_whatsapp", "facturacion_submit"), true) && $this->tablaExisteDb($db, "erp_ecommerce_analytics_conversiones")) {
        $this->insertarConversionDesdeEvento($db, $evento);
      }
      $db->commit();
      return $this->respuesta(false, "success", "Evento analytics registrado", array("escribe_bd" => true, "id_analytics_evento" => $idEvento, "guardrails" => $this->guardrails()));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: registrar busqueda analytics solo cuando exista autorizacion explicita.
   * Impacto: escritura anonima futura para demanda y faltantes sin datos personales.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_TRACKING`.
   */
  public function registrarBusquedaAutorizada($datos = array(), $opciones = array()) {
    $bloqueo = $this->bloqueoPersistenciaAutorizada($opciones, array("busquedas"));
    if ($bloqueo) { return $bloqueo; }
    $preflight = $this->busquedaPreflight($datos);
    $bloqueos = $this->valor($this->valor($preflight, "depurar", array()), "bloqueos", array());
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "Busqueda analytics no registrada por bloqueos", array("no_escribe_bd" => true, "bloqueos" => $bloqueos, "preflight" => $preflight));
    }
    $busqueda = $preflight["depurar"]["busqueda_normalizada"];
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_busquedas
          (session_id_hash, canal, query, query_normalizada, ruta, mascota, necesidad, resultados_total, sin_resultados, filtros_json, metadata_json, fecha_registro)
        VALUES
          (:session_id_hash, :canal, :query, :query_normalizada, :ruta, :mascota, :necesidad, :resultados_total, :sin_resultados, :filtros_json, :metadata_json, NOW())");
      $stmt->execute(array(
        ":session_id_hash" => $busqueda["session_id_hash"],
        ":canal" => $busqueda["canal"],
        ":query" => $busqueda["query"],
        ":query_normalizada" => $busqueda["query_normalizada"],
        ":ruta" => $busqueda["ruta"],
        ":mascota" => $busqueda["mascota"],
        ":necesidad" => $busqueda["necesidad"],
        ":resultados_total" => intval($busqueda["resultados_total"]),
        ":sin_resultados" => !empty($busqueda["sin_resultados"]) ? 1 : 0,
        ":filtros_json" => json_encode($busqueda["filtros"], JSON_UNESCAPED_UNICODE),
        ":metadata_json" => json_encode($busqueda["metadata"], JSON_UNESCAPED_UNICODE)
      ));
      return $this->respuesta(false, "success", "Busqueda analytics registrada", array("escribe_bd" => true, "id_analytics_busqueda" => intval($db->lastInsertId()), "guardrails" => $this->guardrails()));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: registrar conversion analytics solo cuando exista autorizacion explicita.
   * Impacto: escritura anonima futura del embudo sin crear checkout, pago, venta ni inventario.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_TRACKING`.
   */
  public function registrarConversionAutorizada($datos = array(), $opciones = array()) {
    $bloqueo = $this->bloqueoPersistenciaAutorizada($opciones, array("conversiones"));
    if ($bloqueo) { return $bloqueo; }
    $preflight = $this->conversionPreflight($datos);
    $bloqueos = $this->valor($this->valor($preflight, "depurar", array()), "bloqueos", array());
    if (!empty($bloqueos)) {
      return $this->respuesta(true, "warning", "Conversion analytics no registrada por bloqueos", array("no_escribe_bd" => true, "bloqueos" => $bloqueos, "preflight" => $preflight));
    }
    $evento = $preflight["depurar"]["conversion_normalizada"];
    try {
      $db = $this->getConexion();
      $id = $this->insertarConversionDesdeEvento($db, $evento);
      return $this->respuesta(false, "success", "Conversion analytics registrada", array("escribe_bd" => true, "id_analytics_conversion" => $id, "guardrails" => $this->guardrails()));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: planear el recalc de resumen diario analytics sin escribir BD.
   * Impacto: prepara performance del dashboard sin consultar eventos crudos en cada carga.
   * Contrato: interno read-only; no modifica resumen.
   */
  public function resumenDiarioPlanInterno($filtros = array()) {
    $desde = $this->fechaFiltro($this->valor($filtros, "desde", date("Y-m-d", strtotime("-7 days"))), date("Y-m-d", strtotime("-7 days")));
    $hasta = $this->fechaFiltro($this->valor($filtros, "hasta", date("Y-m-d")), date("Y-m-d"));
    $dias = $this->diasEntre($desde, $hasta);
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach (array("sesiones", "eventos", "busquedas", "resumen_diario") as $tabla) {
      if (empty($tablas[$tabla])) { $faltantes[] = $tabla; }
    }
    return $this->respuesta(false, empty($faltantes) ? "success" : "warning", empty($faltantes) ? "Resumen diario listo para recalc autorizado" : "Resumen diario pendiente de esquema", array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "rango" => array("desde" => $desde, "hasta" => $hasta, "dias" => $dias),
      "bloqueos" => $dias > 31 ? array("rango_maximo_31_dias_por_recalculo") : array(),
      "tablas" => $tablas,
      "faltantes" => $faltantes,
      "token_requerido" => "ECOMMERCE_ANALYTICS_RESUMEN_DIARIO",
      "sql_plan" => array(
        "DELETE FROM `erp_ecommerce_analytics_resumen_diario` WHERE `fecha` BETWEEN :desde AND :hasta AND `canal`=:canal",
        "INSERT INTO `erp_ecommerce_analytics_resumen_diario` (fecha, canal, sesiones_total, eventos_total, page_views, productos_vistos, busquedas_total, busquedas_sin_resultados, add_to_quote_total, dryrun_total, preflight_total, whatsapp_total, facturacion_view_total, facturacion_submit_total, metadata_json, fecha_registro, fecha_actualizacion) VALUES (...)"
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: recalcular resumen diario analytics solo con autorizacion explicita.
   * Impacto: agrega conteos anonimos por fecha/canal sin datos personales, ventas ni inventario.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_RESUMEN_DIARIO`.
   */
  public function recalcularResumenDiarioAutorizado($datos = array(), $opciones = array()) {
    $desde = $this->fechaFiltro($this->valor($datos, "desde", date("Y-m-d", strtotime("-7 days"))), date("Y-m-d", strtotime("-7 days")));
    $hasta = $this->fechaFiltro($this->valor($datos, "hasta", date("Y-m-d")), date("Y-m-d"));
    $dias = $this->diasEntre($desde, $hasta);
    $bloqueo = $this->bloqueoResumenAutorizado($opciones);
    if ($bloqueo) { return $bloqueo; }
    if ($dias > 31) {
      return $this->respuesta(true, "warning", "Rango maximo de resumen diario excedido", array("no_escribe_bd" => true, "dias" => $dias, "maximo" => 31));
    }
    try {
      $db = $this->getConexion();
      $canales = $this->obtenerCanalesResumen($db, $desde . " 00:00:00", $hasta . " 23:59:59");
      $db->beginTransaction();
      $insertados = 0;
      foreach ($canales as $canal) {
        foreach ($this->fechasRango($desde, $hasta) as $fecha) {
          $resumen = $this->calcularResumenFechaCanal($db, $fecha, $canal);
          $stmt = $db->prepare("DELETE FROM erp_ecommerce_analytics_resumen_diario WHERE fecha=:fecha AND canal=:canal");
          $stmt->execute(array(":fecha" => $fecha, ":canal" => $canal));
          $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_resumen_diario
              (fecha, canal, sesiones_total, eventos_total, page_views, productos_vistos, busquedas_total, busquedas_sin_resultados, add_to_quote_total, dryrun_total, preflight_total, whatsapp_total, facturacion_view_total, facturacion_submit_total, metadata_json, fecha_registro, fecha_actualizacion)
            VALUES
              (:fecha, :canal, :sesiones_total, :eventos_total, :page_views, :productos_vistos, :busquedas_total, :busquedas_sin_resultados, :add_to_quote_total, :dryrun_total, :preflight_total, :whatsapp_total, :facturacion_view_total, :facturacion_submit_total, :metadata_json, NOW(), NOW())");
          $stmt->execute($resumen);
          $insertados++;
        }
      }
      $db->commit();
      return $this->respuesta(false, "success", "Resumen diario analytics recalculado", array(
        "escribe_bd" => true,
        "rango" => array("desde" => $desde, "hasta" => $hasta, "dias" => $dias),
        "canales_total" => count($canales),
        "resumenes_insertados" => $insertados,
        "guardrails" => $this->guardrails()
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: planear retencion de analytics sin borrar datos.
   * Impacto: define purga futura de eventos crudos conservando resumen diario agregado.
   * Contrato: interno read-only; no elimina registros.
   */
  public function retencionPlanInterno($filtros = array()) {
    $diasRetencion = max(30, min(730, intval($this->valor($filtros, "dias_retencion", 180))));
    $fechaCorte = date("Y-m-d", strtotime("-" . $diasRetencion . " days"));
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach (array("sesiones", "eventos", "busquedas", "conversiones") as $tabla) {
      if (empty($tablas[$tabla])) { $faltantes[] = $tabla; }
    }
    return $this->respuesta(false, empty($faltantes) ? "success" : "warning", empty($faltantes) ? "Retencion analytics lista para autorizacion" : "Retencion analytics pendiente de esquema", array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "dias_retencion" => $diasRetencion,
      "fecha_corte_exclusiva" => $fechaCorte,
      "tablas" => $tablas,
      "faltantes" => $faltantes,
      "token_requerido" => "ECOMMERCE_ANALYTICS_RETENCION",
      "conserva_resumen_diario" => true,
      "sql_plan" => array(
        "DELETE FROM `erp_ecommerce_analytics_eventos` WHERE `fecha_registro` < :fecha_corte",
        "DELETE FROM `erp_ecommerce_analytics_busquedas` WHERE `fecha_registro` < :fecha_corte",
        "DELETE FROM `erp_ecommerce_analytics_conversiones` WHERE `fecha_registro` < :fecha_corte",
        "DELETE FROM `erp_ecommerce_analytics_sesiones` WHERE COALESCE(`fecha_ultima_actividad`, `fecha_inicio`) < :fecha_corte"
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: purgar eventos crudos analytics antiguos solo con autorizacion explicita.
   * Impacto: reduce datos crudos anonimos; conserva resumen diario y no toca ventas/clientes/inventario.
   * Contrato: bloqueado sin token `ECOMMERCE_ANALYTICS_RETENCION`.
   */
  public function purgarRetencionAutorizada($datos = array(), $opciones = array()) {
    $diasRetencion = max(30, min(730, intval($this->valor($datos, "dias_retencion", 180))));
    $fechaCorte = date("Y-m-d", strtotime("-" . $diasRetencion . " days"));
    $bloqueo = $this->bloqueoRetencionAutorizada($opciones);
    if ($bloqueo) { return $bloqueo; }
    try {
      $db = $this->getConexion();
      $db->beginTransaction();
      $eliminados = array();
      $queries = array(
        "eventos" => "DELETE FROM erp_ecommerce_analytics_eventos WHERE fecha_registro < :fecha_corte",
        "busquedas" => "DELETE FROM erp_ecommerce_analytics_busquedas WHERE fecha_registro < :fecha_corte",
        "conversiones" => "DELETE FROM erp_ecommerce_analytics_conversiones WHERE fecha_registro < :fecha_corte",
        "sesiones" => "DELETE FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) < :fecha_corte"
      );
      foreach ($queries as $clave => $sql) {
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":fecha_corte" => $fechaCorte . " 00:00:00"));
        $eliminados[$clave] = $stmt->rowCount();
      }
      $db->commit();
      return $this->respuesta(false, "success", "Retencion analytics aplicada", array(
        "escribe_bd" => true,
        "dias_retencion" => $diasRetencion,
        "fecha_corte_exclusiva" => $fechaCorte,
        "eliminados" => $eliminados,
        "conserva_resumen_diario" => true,
        "guardrails" => $this->guardrails()
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  private function cargarDashboardEventos($db, &$depurar, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT COUNT(*) total FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["resumen"]["eventos_total"] = intval($stmt->fetchColumn());
    $depurar["visitas_por_dia"] = $this->consulta($db, "SELECT DATE(fecha_registro) fecha, COUNT(*) visitas FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin AND tipo_evento='page_view' GROUP BY DATE(fecha_registro) ORDER BY fecha ASC", array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["urls_mas_vistas"] = $this->consultaTop($db, "ruta", "erp_ecommerce_analytics_eventos", "tipo_evento='page_view'", $inicio, $fin, $limite);
    $depurar["productos_mas_vistos"] = $this->consultaProductos($db, "view_product", $inicio, $fin, $limite);
    $depurar["productos_agregados_cotizacion"] = $this->consultaProductos($db, "add_to_quote", $inicio, $fin, $limite);
    $depurar["mascotas_consultadas"] = $this->consultaTop($db, "mascota", "erp_ecommerce_analytics_eventos", "TRIM(COALESCE(mascota,''))<>''", $inicio, $fin, $limite);
    $depurar["necesidades_consultadas"] = $this->consultaTop($db, "necesidad", "erp_ecommerce_analytics_eventos", "TRIM(COALESCE(necesidad,''))<>''", $inicio, $fin, $limite);
    $stmt = $db->prepare("SELECT tipo_evento, COUNT(*) total FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin GROUP BY tipo_evento");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $tipo = $this->valor($fila, "tipo_evento", "");
      $total = intval($this->valor($fila, "total", 0));
      if (isset($depurar["embudo"][$tipo])) { $depurar["embudo"][$tipo] = $total; }
      if ($tipo === "open_whatsapp") { $depurar["resumen"]["whatsapp_total"] = $total; }
    }
    $depurar["productos_interes_sin_conversion"] = $this->consultaProductosInteresSinConversion($db, $inicio, $fin, $limite);
  }

  private function cargarDashboardBusquedas($db, &$depurar, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultados FROM erp_ecommerce_analytics_busquedas WHERE fecha_registro BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $depurar["resumen"]["busquedas_total"] = intval($this->valor($fila, "total", 0));
    $depurar["resumen"]["busquedas_sin_resultados"] = intval($this->valor($fila, "sin_resultados", 0));
    $depurar["busquedas_frecuentes"] = $this->consultaTop($db, "query_normalizada", "erp_ecommerce_analytics_busquedas", "1=1", $inicio, $fin, $limite);
    $depurar["busquedas_sin_resultados"] = $this->consultaTop($db, "query_normalizada", "erp_ecommerce_analytics_busquedas", "sin_resultados=1", $inicio, $fin, $limite);
    $depurar["oportunidades_publicacion"] = $depurar["busquedas_sin_resultados"];
  }

  private function cargarDashboardResumenDiario($db, &$depurar, $desde, $hasta) {
    $stmt = $db->prepare("SELECT fecha,
        SUM(sesiones_total) sesiones_total,
        SUM(eventos_total) eventos_total,
        SUM(page_views) page_views,
        SUM(productos_vistos) productos_vistos,
        SUM(busquedas_total) busquedas_total,
        SUM(busquedas_sin_resultados) busquedas_sin_resultados,
        SUM(add_to_quote_total) add_to_quote_total,
        SUM(dryrun_total) dryrun_total,
        SUM(preflight_total) preflight_total,
        SUM(whatsapp_total) whatsapp_total,
        SUM(facturacion_view_total) facturacion_view_total,
        SUM(facturacion_submit_total) facturacion_submit_total
      FROM erp_ecommerce_analytics_resumen_diario
      WHERE fecha BETWEEN :desde AND :hasta
      GROUP BY fecha
      ORDER BY fecha ASC");
    $stmt->execute(array(":desde" => $desde, ":hasta" => $hasta));
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($filas)) { return; }
    $totales = array(
      "sesiones_total" => 0,
      "eventos_total" => 0,
      "page_views" => 0,
      "productos_vistos" => 0,
      "busquedas_total" => 0,
      "busquedas_sin_resultados" => 0,
      "add_to_quote_total" => 0,
      "dryrun_total" => 0,
      "preflight_total" => 0,
      "whatsapp_total" => 0,
      "facturacion_view_total" => 0,
      "facturacion_submit_total" => 0
    );
    $visitas = array();
    foreach ($filas as $fila) {
      foreach ($totales as $clave => $valor) {
        $totales[$clave] += intval($this->valor($fila, $clave, 0));
      }
      $visitas[] = array(
        "fecha" => $this->valor($fila, "fecha", ""),
        "visitas" => intval($this->valor($fila, "page_views", 0))
      );
    }
    $depurar["fuente_metricas"] = "resumen_diario";
    $depurar["resumen"]["sesiones_total"] = $totales["sesiones_total"];
    $depurar["resumen"]["eventos_total"] = $totales["eventos_total"];
    $depurar["resumen"]["busquedas_total"] = $totales["busquedas_total"];
    $depurar["resumen"]["busquedas_sin_resultados"] = $totales["busquedas_sin_resultados"];
    $depurar["resumen"]["whatsapp_total"] = $totales["whatsapp_total"];
    $depurar["visitas_por_dia"] = $visitas;
    $depurar["embudo"]["page_view"] = $totales["page_views"];
    $depurar["embudo"]["view_product"] = $totales["productos_vistos"];
    $depurar["embudo"]["add_to_quote"] = $totales["add_to_quote_total"];
    $depurar["embudo"]["quote_dryrun"] = $totales["dryrun_total"];
    $depurar["embudo"]["quote_preflight"] = $totales["preflight_total"];
    $depurar["embudo"]["open_whatsapp"] = $totales["whatsapp_total"];
  }

  private function bloqueoPersistenciaAutorizada($opciones, $tablasRequeridas) {
    $token = trim((string) $this->valor($opciones, "autorizar", ""));
    if ($token !== "ECOMMERCE_ANALYTICS_TRACKING") {
      return $this->respuesta(true, "warning", "Persistencia Ecommerce / Analytics bloqueada", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_ANALYTICS_TRACKING",
        "guardrails" => $this->guardrails()
      ));
    }
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
    }
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach ($tablasRequeridas as $tabla) {
      if (empty($tablas[$tabla])) { $faltantes[] = $tabla; }
    }
    if (!empty($faltantes)) {
      return $this->respuesta(true, "warning", "Persistencia Ecommerce / Analytics pendiente de esquema", array(
        "no_escribe_bd" => true,
        "faltantes" => $faltantes,
        "tablas" => $tablas
      ));
    }
    return null;
  }

  private function bloqueoResumenAutorizado($opciones) {
    $token = trim((string) $this->valor($opciones, "autorizar", ""));
    if ($token !== "ECOMMERCE_ANALYTICS_RESUMEN_DIARIO") {
      return $this->respuesta(true, "warning", "Recalculo de resumen diario bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_ANALYTICS_RESUMEN_DIARIO",
        "guardrails" => $this->guardrails()
      ));
    }
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
    }
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach (array("sesiones", "eventos", "busquedas", "resumen_diario") as $tabla) {
      if (empty($tablas[$tabla])) { $faltantes[] = $tabla; }
    }
    if (!empty($faltantes)) {
      return $this->respuesta(true, "warning", "Resumen diario pendiente de esquema", array(
        "no_escribe_bd" => true,
        "faltantes" => $faltantes,
        "tablas" => $tablas
      ));
    }
    return null;
  }

  private function bloqueoRetencionAutorizada($opciones) {
    $token = trim((string) $this->valor($opciones, "autorizar", ""));
    if ($token !== "ECOMMERCE_ANALYTICS_RETENCION") {
      return $this->respuesta(true, "warning", "Purga de retencion analytics bloqueada", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_ANALYTICS_RETENCION",
        "guardrails" => $this->guardrails()
      ));
    }
    $db = $this->getConexion();
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
    }
    $tablas = $this->tablasDisponibles($db);
    $faltantes = array();
    foreach (array("sesiones", "eventos", "busquedas", "conversiones") as $tabla) {
      if (empty($tablas[$tabla])) { $faltantes[] = $tabla; }
    }
    if (!empty($faltantes)) {
      return $this->respuesta(true, "warning", "Retencion analytics pendiente de esquema", array(
        "no_escribe_bd" => true,
        "faltantes" => $faltantes,
        "tablas" => $tablas
      ));
    }
    return null;
  }

  private function obtenerCanalesResumen($db, $inicio, $fin) {
    $canales = array();
    foreach (array(
      "SELECT DISTINCT canal FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin",
      "SELECT DISTINCT canal FROM erp_ecommerce_analytics_busquedas WHERE fecha_registro BETWEEN :inicio AND :fin",
      "SELECT DISTINCT canal FROM erp_ecommerce_analytics_sesiones WHERE fecha_inicio BETWEEN :inicio AND :fin"
    ) as $sql) {
      $stmt = $db->prepare($sql);
      $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
      foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $canal) {
        $canal = $this->limpiarToken($canal, 50);
        if ($canal !== "") { $canales[$canal] = true; }
      }
    }
    if (empty($canales)) { $canales["web_publica"] = true; }
    return array_keys($canales);
  }

  private function calcularResumenFechaCanal($db, $fecha, $canal) {
    $inicio = $fecha . " 00:00:00";
    $fin = $fecha . " 23:59:59";
    $eventos = array(
      "eventos_total" => 0,
      "page_views" => 0,
      "productos_vistos" => 0,
      "add_to_quote_total" => 0,
      "dryrun_total" => 0,
      "preflight_total" => 0,
      "whatsapp_total" => 0,
      "facturacion_view_total" => 0,
      "facturacion_submit_total" => 0
    );
    $stmt = $db->prepare("SELECT tipo_evento, COUNT(*) total FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin AND canal=:canal GROUP BY tipo_evento");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin, ":canal" => $canal));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $tipo = $this->valor($fila, "tipo_evento", "");
      $total = intval($this->valor($fila, "total", 0));
      $eventos["eventos_total"] += $total;
      if ($tipo === "page_view") { $eventos["page_views"] = $total; }
      if ($tipo === "view_product") { $eventos["productos_vistos"] = $total; }
      if ($tipo === "add_to_quote") { $eventos["add_to_quote_total"] = $total; }
      if ($tipo === "quote_dryrun") { $eventos["dryrun_total"] = $total; }
      if ($tipo === "quote_preflight") { $eventos["preflight_total"] = $total; }
      if ($tipo === "open_whatsapp") { $eventos["whatsapp_total"] = $total; }
      if ($tipo === "facturacion_view") { $eventos["facturacion_view_total"] = $total; }
      if ($tipo === "facturacion_submit") { $eventos["facturacion_submit_total"] = $total; }
    }
    $stmt = $db->prepare("SELECT COUNT(*) total FROM erp_ecommerce_analytics_sesiones WHERE fecha_inicio BETWEEN :inicio AND :fin AND canal=:canal");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin, ":canal" => $canal));
    $sesionesTotal = intval($stmt->fetchColumn());
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultados FROM erp_ecommerce_analytics_busquedas WHERE fecha_registro BETWEEN :inicio AND :fin AND canal=:canal");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin, ":canal" => $canal));
    $busquedas = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    return array(
      ":fecha" => $fecha,
      ":canal" => $canal,
      ":sesiones_total" => $sesionesTotal,
      ":eventos_total" => $eventos["eventos_total"],
      ":page_views" => $eventos["page_views"],
      ":productos_vistos" => $eventos["productos_vistos"],
      ":busquedas_total" => intval($this->valor($busquedas, "total", 0)),
      ":busquedas_sin_resultados" => intval($this->valor($busquedas, "sin_resultados", 0)),
      ":add_to_quote_total" => $eventos["add_to_quote_total"],
      ":dryrun_total" => $eventos["dryrun_total"],
      ":preflight_total" => $eventos["preflight_total"],
      ":whatsapp_total" => $eventos["whatsapp_total"],
      ":facturacion_view_total" => $eventos["facturacion_view_total"],
      ":facturacion_submit_total" => $eventos["facturacion_submit_total"],
      ":metadata_json" => json_encode(array("origen" => "recalculo_autorizado", "version" => "2026-08-05"), JSON_UNESCAPED_UNICODE)
    );
  }

  private function paramsEvento($evento) {
    return array(
      ":session_id_hash" => $evento["session_id_hash"],
      ":tipo_evento" => $evento["tipo_evento"],
      ":canal" => $evento["canal"],
      ":ruta" => $evento["ruta"],
      ":referrer" => $evento["referrer"],
      ":utm_source" => $evento["utm_source"],
      ":utm_medium" => $evento["utm_medium"],
      ":utm_campaign" => $evento["utm_campaign"],
      ":dispositivo" => $evento["dispositivo_aproximado"],
      ":mascota" => $evento["mascota"],
      ":necesidad" => $evento["necesidad"],
      ":id_publicacion" => intval($evento["id_publicacion"]) > 0 ? intval($evento["id_publicacion"]) : null,
      ":id_sku" => intval($evento["id_sku"]) > 0 ? intval($evento["id_sku"]) : null,
      ":slug" => $evento["slug"],
      ":metadata_json" => json_encode($evento["metadata"], JSON_UNESCAPED_UNICODE)
    );
  }

  private function upsertSesionLigera($db, $evento) {
    if (!$this->tablaExisteDb($db, "erp_ecommerce_analytics_sesiones")) { return; }
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_sesiones
        (session_id_hash, canal, primer_ruta, ultimo_ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, fecha_inicio, fecha_ultima_actividad, eventos_total)
      VALUES
        (:session_id_hash, :canal, :ruta, :ruta2, :referrer, :utm_source, :utm_medium, :utm_campaign, :dispositivo, NOW(), NOW(), 1)
      ON DUPLICATE KEY UPDATE
        ultimo_ruta=VALUES(ultimo_ruta),
        fecha_ultima_actividad=NOW(),
        eventos_total=eventos_total+1");
    $stmt->execute(array(
      ":session_id_hash" => $evento["session_id_hash"],
      ":canal" => $evento["canal"],
      ":ruta" => $evento["ruta"],
      ":ruta2" => $evento["ruta"],
      ":referrer" => $evento["referrer"],
      ":utm_source" => $evento["utm_source"],
      ":utm_medium" => $evento["utm_medium"],
      ":utm_campaign" => $evento["utm_campaign"],
      ":dispositivo" => $evento["dispositivo_aproximado"]
    ));
  }

  private function insertarConversionDesdeEvento($db, $evento) {
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_conversiones
        (session_id_hash, tipo_conversion, canal, id_publicacion, id_sku, slug, ruta_origen, etapa_origen, metadata_json, fecha_registro)
      VALUES
        (:session_id_hash, :tipo_conversion, :canal, :id_publicacion, :id_sku, :slug, :ruta_origen, :etapa_origen, :metadata_json, NOW())");
    $stmt->execute(array(
      ":session_id_hash" => $evento["session_id_hash"],
      ":tipo_conversion" => $evento["tipo_evento"],
      ":canal" => $evento["canal"],
      ":id_publicacion" => intval($evento["id_publicacion"]) > 0 ? intval($evento["id_publicacion"]) : null,
      ":id_sku" => intval($evento["id_sku"]) > 0 ? intval($evento["id_sku"]) : null,
      ":slug" => $evento["slug"],
      ":ruta_origen" => $evento["ruta"],
      ":etapa_origen" => $evento["tipo_evento"],
      ":metadata_json" => json_encode($evento["metadata"], JSON_UNESCAPED_UNICODE)
    ));
    return intval($db->lastInsertId());
  }

  private function normalizarEvento($datos) {
    $sessionId = $this->sessionIdLimpio($this->valor($datos, "session_id", ""));
    $metadata = is_array($this->valor($datos, "metadata", array())) ? $this->valor($datos, "metadata", array()) : array();
    return array(
      "session_id_hash" => $this->hashAnonimo($sessionId),
      "tipo_evento" => $this->limpiarToken($this->valor($datos, "tipo_evento", ""), 60),
      "canal" => $this->limpiarToken($this->valor($datos, "canal", "web_publica"), 50),
      "ruta" => $this->limpiarRuta($this->valor($datos, "ruta", "")),
      "referrer" => $this->limpiarRuta($this->valor($datos, "referrer", $this->valor($datos, "referer", ""))),
      "utm_source" => $this->limpiarToken($this->valor($datos, "utm_source", ""), 120),
      "utm_medium" => $this->limpiarToken($this->valor($datos, "utm_medium", ""), 120),
      "utm_campaign" => $this->limpiarTextoCorto($this->valor($datos, "utm_campaign", ""), 160),
      "dispositivo_aproximado" => $this->dispositivoAproximado($datos),
      "mascota" => $this->limpiarToken($this->valor($datos, "mascota", ""), 80),
      "necesidad" => $this->limpiarToken($this->valor($datos, "necesidad", ""), 80),
      "id_publicacion" => max(0, intval($this->valor($datos, "id_publicacion", 0))),
      "id_sku" => max(0, intval($this->valor($datos, "id_sku", 0))),
      "slug" => $this->limpiarSlug($this->valor($datos, "slug", "")),
      "metadata" => $this->limpiarMetadata($metadata)
    );
  }

  private function bloqueosEvento($evento, $datos) {
    $bloqueos = array();
    if ($evento["session_id_hash"] === "") { $bloqueos[] = "session_id_anonimo_requerido"; }
    if (!in_array($evento["tipo_evento"], $this->eventosPermitidos, true)) { $bloqueos[] = "tipo_evento_no_permitido"; }
    if (!empty($this->detectarDatosPersonales($datos))) { $bloqueos[] = "payload_no_debe_incluir_datos_personales"; }
    if ($this->contieneStockExacto($datos)) { $bloqueos[] = "stock_exacto_no_permitido_en_analytics"; }
    return $bloqueos;
  }

  private function sqlPlanEvento($evento) {
    $plan = array("INSERT INTO `erp_ecommerce_analytics_eventos` (`session_id_hash`, `tipo_evento`, `canal`, `ruta`, `referrer`, `utm_source`, `utm_medium`, `utm_campaign`, `dispositivo_aproximado`, `mascota`, `necesidad`, `id_publicacion`, `id_sku`, `slug`, `metadata_json`, `fecha_registro`) VALUES (...)");
    if (in_array($evento["tipo_evento"], array("add_to_quote", "remove_from_quote", "quote_dryrun", "quote_preflight", "open_whatsapp", "facturacion_submit"), true)) {
      $plan[] = "INSERT INTO `erp_ecommerce_analytics_conversiones` (`session_id_hash`, `tipo_conversion`, `canal`, `id_publicacion`, `id_sku`, `slug`, `ruta_origen`, `metadata_json`, `fecha_registro`) VALUES (...)";
    }
    return $plan;
  }

  private function tablasDisponibles($db) {
    return array(
      "sesiones" => $this->tablaExisteDb($db, "erp_ecommerce_analytics_sesiones"),
      "eventos" => $this->tablaExisteDb($db, "erp_ecommerce_analytics_eventos"),
      "busquedas" => $this->tablaExisteDb($db, "erp_ecommerce_analytics_busquedas"),
      "conversiones" => $this->tablaExisteDb($db, "erp_ecommerce_analytics_conversiones"),
      "resumen_diario" => $this->tablaExisteDb($db, "erp_ecommerce_analytics_resumen_diario")
    );
  }

  private function tablaExisteDb($db, $tabla) {
    if (!$db) { return false; }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function consulta($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultaTop($db, $campo, $tabla, $where, $inicio, $fin, $limite) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $campo) || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) { return array(); }
    $sql = "SELECT " . $campo . " valor, COUNT(*) total, MAX(fecha_registro) ultima_fecha FROM " . $tabla . " WHERE fecha_registro BETWEEN :inicio AND :fin AND " . $where . " AND TRIM(COALESCE(" . $campo . ",''))<>'' GROUP BY " . $campo . " ORDER BY total DESC, valor ASC LIMIT " . intval($limite);
    return $this->consulta($db, $sql, array(":inicio" => $inicio, ":fin" => $fin));
  }

  private function consultaProductos($db, $tipoEvento, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT id_publicacion, id_sku, slug, COUNT(*) total, MAX(fecha_registro) ultima_fecha FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :inicio AND :fin AND tipo_evento=:tipo AND (id_publicacion>0 OR id_sku>0 OR TRIM(COALESCE(slug,''))<>'') GROUP BY id_publicacion, id_sku, slug ORDER BY total DESC, id_publicacion ASC LIMIT " . intval($limite));
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin, ":tipo" => $tipoEvento));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultaProductosInteresSinConversion($db, $inicio, $fin, $limite) {
    $sql = "SELECT v.id_publicacion, v.id_sku, v.slug, COUNT(*) vistas
      FROM erp_ecommerce_analytics_eventos v
      LEFT JOIN erp_ecommerce_analytics_eventos c
        ON c.fecha_registro BETWEEN :inicio2 AND :fin2
       AND c.tipo_evento IN ('add_to_quote','quote_dryrun','quote_preflight','open_whatsapp')
       AND COALESCE(c.id_publicacion,0)=COALESCE(v.id_publicacion,0)
       AND COALESCE(c.id_sku,0)=COALESCE(v.id_sku,0)
       AND COALESCE(c.slug,'')=COALESCE(v.slug,'')
      WHERE v.fecha_registro BETWEEN :inicio AND :fin
        AND v.tipo_evento='view_product'
        AND c.id_analytics_evento IS NULL
      GROUP BY v.id_publicacion, v.id_sku, v.slug
      ORDER BY vistas DESC
      LIMIT " . intval($limite);
    return $this->consulta($db, $sql, array(":inicio" => $inicio, ":fin" => $fin, ":inicio2" => $inicio, ":fin2" => $fin));
  }

  private function embudoVacio() {
    return array("page_view" => 0, "view_product" => 0, "add_to_quote" => 0, "quote_dryrun" => 0, "quote_preflight" => 0, "open_whatsapp" => 0);
  }

  private function calcularAbandono($embudo) {
    $orden = array("page_view", "view_product", "add_to_quote", "quote_dryrun", "quote_preflight", "open_whatsapp");
    $salida = array();
    for ($i = 0; $i < count($orden) - 1; $i++) {
      $actual = max(0, intval($this->valor($embudo, $orden[$i], 0)));
      $siguiente = max(0, intval($this->valor($embudo, $orden[$i + 1], 0)));
      $salida[] = array("de" => $orden[$i], "a" => $orden[$i + 1], "abandono_estimado" => max(0, $actual - $siguiente), "ratio_paso" => $actual > 0 ? round($siguiente / $actual, 4) : null);
    }
    return $salida;
  }

  private function fechaFiltro($valor, $fallback) {
    $valor = trim((string) $valor);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : $fallback;
  }

  private function diasEntre($desde, $hasta) {
    $inicio = strtotime($desde . " 00:00:00");
    $fin = strtotime($hasta . " 00:00:00");
    if ($inicio === false || $fin === false || $fin < $inicio) { return 0; }
    return intval(floor(($fin - $inicio) / 86400)) + 1;
  }

  private function fechasRango($desde, $hasta) {
    $fechas = array();
    $inicio = strtotime($desde . " 00:00:00");
    $fin = strtotime($hasta . " 00:00:00");
    if ($inicio === false || $fin === false || $fin < $inicio) { return $fechas; }
    for ($ts = $inicio; $ts <= $fin; $ts += 86400) {
      $fechas[] = date("Y-m-d", $ts);
    }
    return $fechas;
  }

  private function hashAnonimo($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") { return ""; }
    return hash("sha256", "ecommerce_analytics|" . $valor);
  }

  private function sessionIdLimpio($valor) {
    return substr(preg_replace('/[^a-zA-Z0-9_\-.]/', '', trim((string) $valor)), 0, 100);
  }

  private function limpiarToken($valor, $limite) {
    $valor = strtolower(trim((string) $valor));
    $valor = preg_replace('/[^a-z0-9_\-]/', '', $valor);
    return substr($valor, 0, $limite);
  }

  private function limpiarSlug($valor) {
    return substr(preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) $valor))), 0, 180);
  }

  private function limpiarRuta($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/[\x00-\x1F\x7F]/', '', $valor);
    return substr($valor, 0, 255);
  }

  private function limpiarTextoCorto($valor, $limite) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/[\x00-\x1F\x7F]/', '', $valor);
    return substr($valor, 0, $limite);
  }

  private function normalizarTexto($valor) {
    $valor = strtolower(trim((string) $valor));
    $valor = preg_replace('/\s+/', ' ', $valor);
    return substr($valor, 0, 255);
  }

  private function dispositivoAproximado($datos) {
    $valor = $this->limpiarToken($this->valor($datos, "dispositivo", ""), 40);
    if ($valor !== "") { return $valor; }
    $ua = strtolower(isset($_SERVER["HTTP_USER_AGENT"]) ? (string) $_SERVER["HTTP_USER_AGENT"] : "");
    if ($ua === "") { return ""; }
    if (strpos($ua, "mobile") !== false || strpos($ua, "android") !== false || strpos($ua, "iphone") !== false) { return "mobile"; }
    if (strpos($ua, "tablet") !== false || strpos($ua, "ipad") !== false) { return "tablet"; }
    return "desktop";
  }

  private function limpiarMetadata($datos, $nivel = 0) {
    if (!is_array($datos) || $nivel > 2) { return is_scalar($datos) ? $this->limpiarTextoCorto($datos, 160) : null; }
    $salida = array();
    foreach ($datos as $clave => $valor) {
      $claveLimpia = $this->limpiarToken($clave, 60);
      if ($claveLimpia === "" || in_array($claveLimpia, array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion", "datos_fiscales", "stock", "stock_exacto", "existencia"), true)) { continue; }
      $salida[$claveLimpia] = is_array($valor) ? $this->limpiarMetadata($valor, $nivel + 1) : $this->limpiarTextoCorto($valor, 160);
      if (count($salida) >= 30) { break; }
    }
    return $salida;
  }

  private function detectarDatosPersonales($datos) {
    $detectados = array();
    $this->detectarDatosPersonalesRec($datos, "", $detectados);
    return array_values(array_unique($detectados));
  }

  private function detectarDatosPersonalesRec($datos, $prefijo, &$detectados) {
    $claves = array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion", "datos_fiscales", "codigo_postal", "cp", "calle", "colonia");
    if (!is_array($datos)) {
      $valor = trim((string) $datos);
      if ($valor !== "" && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $valor)) { $detectados[] = $prefijo !== "" ? $prefijo . ":correo" : "correo"; }
      if ($valor !== "" && preg_match('/(?:\D|^)(\d{10})(?:\D|$)/', $valor)) { $detectados[] = $prefijo !== "" ? $prefijo . ":telefono" : "telefono"; }
      if ($valor !== "" && preg_match('/\b[A-Z&\x{00D1}]{3,4}\d{6}[A-Z0-9]{3}\b/u', strtoupper($valor))) { $detectados[] = $prefijo !== "" ? $prefijo . ":rfc" : "rfc"; }
      return;
    }
    foreach ($datos as $clave => $valor) {
      $claveLimpia = strtolower(trim((string) $clave));
      $ruta = $prefijo === "" ? $claveLimpia : $prefijo . "." . $claveLimpia;
      if (in_array($claveLimpia, $claves, true)) { $detectados[] = $ruta; }
      $this->detectarDatosPersonalesRec($valor, $ruta, $detectados);
    }
  }

  private function contieneStockExacto($datos) {
    if (!is_array($datos)) { return false; }
    foreach ($datos as $clave => $valor) {
      $claveLimpia = strtolower(trim((string) $clave));
      if (in_array($claveLimpia, array("stock", "stock_exacto", "existencia", "existencias", "cantidad_disponible"), true)) { return true; }
      if (is_array($valor) && $this->contieneStockExacto($valor)) { return true; }
    }
    return false;
  }

  private function bool($valor) {
    return $valor === true || $valor === 1 || $valor === "1" || $valor === "true";
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function guardrails() {
    return array(
      "no_escribe_bd" => true,
      "persistencia_requiere_autorizacion_explicita" => true,
      "no_guardar_datos_personales" => true,
      "session_id_se_devuelve_como_hash" => true,
      "no_mostrar_stock_exacto" => true,
      "no_checkout" => true,
      "no_pagos" => true,
      "no_descuenta_inventario" => true,
      "no_mezclar_con_ventas_reales" => true,
      "no_usar_ecom_legacy_como_fuente" => true
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
