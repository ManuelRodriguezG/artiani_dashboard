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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: entregar contrato publico de analytics ecommerce para frontend externo.
   * Impacto: evita que el frontend lea docs/archivos internos y fija payloads anonimos listos para persistencia real.
   * Contrato: solo lectura; no escribe BD ni expone datos internos.
   */
  public function contratoFrontend() {
    $db = $this->getConexion();
    $tablas = $this->tablasDisponibles($db);
    $persistenciaActiva = $this->trackingPublicoActivo($tablas);
    return $this->respuesta(false, "success", "Contrato Ecommerce / Analytics", array(
      "version" => "fase2-analytics-atribucion-2026-09-27",
      "estado" => $persistenciaActiva ? "persistencia_publica_activa" : "preflight_listo_para_persistencia",
      "persistencia" => array(
        "activa" => $persistenciaActiva,
        "modo_actual" => $persistenciaActiva ? "registra_bd" : "valida_sin_guardar",
        "tablas" => $tablas,
        "activacion_backend" => "Cuando exista esquema y se habilite ECOMMERCE_ANALYTICS_TRACKING_PUBLICO=true, estos mismos endpoints guardaran eventos anonimos."
      ),
      "endpoints_publicos" => array(
        array("metodo" => "POST", "ruta" => "/ecommercePublico/analytics_sesion", "uso" => "Crear/actualizar sesion anonima o validarla si la persistencia aun no esta activa."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/evento_navegacion", "uso" => "Registrar o validar evento anonimo de navegacion/catalogo/embudo."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/busqueda_registrar", "uso" => "Registrar o validar busqueda anonima con conteo de resultados."),
        array("metodo" => "POST", "ruta" => "/ecommercePublico/analytics_conversion", "uso" => "Registrar o validar conversion anonima del embudo.")
      ),
      "eventos_permitidos" => $this->eventosPermitidos,
      "datos_permitidos" => array("session_id", "tipo_evento", "canal", "ruta", "referrer", "utm_source", "utm_medium", "utm_campaign", "dispositivo", "mascota", "necesidad", "id_publicacion", "id_sku", "slug", "query", "resultados_total", "sin_resultados", "metadata"),
      "datos_derivados" => array("fuente_detectada", "medio_detectado", "campania_detectada", "click_id_tipo", "referrer_host", "es_pago_probable"),
      "datos_prohibidos" => array("nombre", "telefono", "correo", "email", "rfc", "razon_social", "direccion", "datos_fiscales", "stock_exacto", "valor_crudo_fbclid", "valor_crudo_gclid", "valor_crudo_gbraid", "valor_crudo_wbraid", "valor_crudo_msclkid", "valor_crudo_ttclid"),
      "regla_cliente" => "Frontend debe generar un session_id anonimo persistente en localStorage; no debe enviar datos personales en analytics. Cuando el usuario deje contacto/cotizacion, el backend podra enlazar esa sesion por flujo separado.",
      "guardrails" => $this->guardrails($persistenciaActiva)
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: indicar si los endpoints publicos pueden persistir tracking anonimo.
   * Impacto: permite activar analytics real con una bandera operativa sin cambiar contrato de frontend.
   * Contrato: solo evalua constantes y tablas requeridas; no escribe BD.
   */
  public function trackingPublicoActivo($tablas = null) {
    if (!defined("ECOMMERCE_ANALYTICS_TRACKING_PUBLICO") || ECOMMERCE_ANALYTICS_TRACKING_PUBLICO !== true) {
      return false;
    }
    if ($tablas === null) {
      $tablas = $this->tablasDisponibles($this->getConexion());
    }
    foreach (array("sesiones", "eventos", "busquedas", "conversiones") as $tabla) {
      if (empty($tablas[$tabla])) { return false; }
    }
    return true;
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
      "primer_ruta" => $this->limpiarRutaAnalytics($this->valor($datos, "ruta", "")),
      "referrer" => $this->limpiarRutaAnalytics($this->valor($datos, "referrer", "")),
      "utm_source" => $this->limpiarToken($this->valor($datos, "utm_source", ""), 120),
      "utm_medium" => $this->limpiarToken($this->valor($datos, "utm_medium", ""), 120),
      "utm_campaign" => $this->limpiarTextoCorto($this->valor($datos, "utm_campaign", ""), 160),
      "dispositivo_aproximado" => $this->dispositivoAproximado($datos),
      "metadata" => $this->limpiarMetadata($metadata)
    );
    $sesion["atribucion"] = $this->atribucionDesdeDatos($sesion);

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
      "ruta" => $this->limpiarRutaAnalytics($this->valor($datos, "ruta", "")),
      "referrer" => $this->limpiarRutaAnalytics($this->valor($datos, "referrer", $this->valor($datos, "referer", ""))),
      "utm_source" => $this->limpiarToken($this->valor($datos, "utm_source", ""), 120),
      "utm_medium" => $this->limpiarToken($this->valor($datos, "utm_medium", ""), 120),
      "utm_campaign" => $this->limpiarTextoCorto($this->valor($datos, "utm_campaign", ""), 160),
      "mascota" => $this->limpiarToken($this->valor($datos, "mascota", ""), 80),
      "necesidad" => $this->limpiarToken($this->valor($datos, "necesidad", ""), 80),
      "resultados_total" => $resultadosTotal,
      "sin_resultados" => $this->valor($datos, "sin_resultados", null) === null ? $resultadosTotal <= 0 : $this->bool($this->valor($datos, "sin_resultados", false)),
      "filtros" => $this->limpiarMetadata($filtros),
      "metadata" => $this->limpiarMetadata($this->valor($datos, "metadata", array()))
    );
    $busqueda["atribucion"] = $this->atribucionDesdeDatos(array_merge($datos, $busqueda));
    $busqueda["metadata"] = $this->metadataConAtribucion($busqueda["metadata"], $busqueda["atribucion"]);

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
      "fecha_consulta" => date("Y-m-d H:i:s"),
      "ultimo_evento" => array(),
      "resumen" => array(
        "sesiones_total" => 0,
        "eventos_total" => 0,
        "page_views" => 0,
        "productos_vistos" => 0,
        "busquedas_total" => 0,
        "busquedas_sin_resultados" => 0,
        "add_to_quote_total" => 0,
        "quote_dryrun_total" => 0,
        "quote_preflight_total" => 0,
        "whatsapp_total" => 0,
        "facturacion_view_total" => 0,
        "facturacion_submit_total" => 0
      ),
      "visitas_por_dia" => array(),
      "urls_mas_vistas" => array(),
      "productos_mas_vistos" => array(),
      "productos_agregados_cotizacion" => array(),
      "busquedas_frecuentes" => array(),
      "busquedas_sin_resultados" => array(),
      "embudo" => $this->embudoVacio(),
      "abandono_por_etapa" => array(),
      "sesiones_recientes" => array(),
      "conversiones_por_tipo" => array(),
      "facturacion_eventos" => array(),
      "canales" => array(),
      "fuentes_trafico" => array(),
      "medios_trafico" => array(),
      "campanias_trafico" => array(),
      "click_ids_detectados" => array(),
      "mascotas_consultadas" => array(),
      "necesidades_consultadas" => array(),
      "productos_interes_sin_conversion" => array(),
      "oportunidades_publicacion" => array(),
      "calidad_tracking" => array(
        "sesiones_un_evento" => 0,
        "sesiones_un_evento_pct" => 0,
        "sesiones_distintas_eventos" => 0,
        "eventos_por_sesion" => 0,
        "diagnostico" => "sin_datos"
      ),
      "persistencia" => array(
        "activa" => $this->trackingPublicoActivo($tablas),
        "modo_actual" => $this->trackingPublicoActivo($tablas) ? "registra_bd" : "valida_sin_guardar"
      ),
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
        $this->cargarDashboardSesiones($db, $depurar, $inicio, $fin, $limite);
        $this->cargarDashboardAtribucion($db, $depurar, $inicio, $fin, $limite);
        $this->cargarDashboardCalidadTracking($db, $depurar, $inicio, $fin);
      }
      if ($tablas["conversiones"]) {
        $this->cargarDashboardConversiones($db, $depurar, $inicio, $fin, $limite);
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-05
   * Proposito: consultar flujo de navegacion anonimo por sesiones ecommerce.
   * Impacto: permite seguir recorridos reales sin exponer session_id completo, PII, ventas ni inventario.
   * Contrato: interno read-only; solo SELECT sobre tablas `erp_ecommerce_analytics_*`.
   */
  public function flujoSesionesInterno($filtros = array()) {
    $db = $this->getConexion();
    $desde = $this->fechaFiltro($this->valor($filtros, "desde", date("Y-m-d", strtotime("-7 days"))), date("Y-m-d", strtotime("-7 days")));
    $hasta = $this->fechaFiltro($this->valor($filtros, "hasta", date("Y-m-d")), date("Y-m-d"));
    $limite = max(5, min(100, intval($this->valor($filtros, "limite", 25))));
    $minEventos = max(1, min(50, intval($this->valor($filtros, "min_eventos", 1))));
    $sessionKey = $this->limpiarToken($this->valor($filtros, "session_key", ""), 16);
    $tablas = $this->tablasDisponibles($db);
    $depurar = array(
      "read_only" => true,
      "configurado" => !empty($tablas["sesiones"]) && !empty($tablas["eventos"]),
      "rango" => array("desde" => $desde, "hasta" => $hasta, "limite" => $limite, "min_eventos" => $minEventos),
      "session_key" => $sessionKey,
      "fecha_consulta" => date("Y-m-d H:i:s"),
      "tablas" => $tablas,
      "sesiones" => array(),
      "sesion_seleccionada" => array(),
      "timeline" => array(),
      "resumen" => array("sesiones_total" => 0, "eventos_total" => 0, "busquedas_total" => 0, "conversiones_total" => 0),
      "guardrails" => $this->guardrails()
    );
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", $depurar);
    }
    if (empty($tablas["sesiones"]) || empty($tablas["eventos"])) {
      return $this->respuesta(false, "warning", "Esquema Analytics incompleto para flujo de sesiones", $depurar);
    }
    $inicio = $desde . " 00:00:00";
    $fin = $hasta . " 23:59:59";

    try {
      $depurar["sesiones"] = $this->consultarSesionesFlujo($db, $inicio, $fin, $limite, $minEventos);
      $depurar["resumen"]["sesiones_total"] = count($depurar["sesiones"]);
      if ($sessionKey === "" && !empty($depurar["sesiones"][0]["session_key"])) {
        $sessionKey = $depurar["sesiones"][0]["session_key"];
        $depurar["session_key"] = $sessionKey;
      }
      if ($sessionKey !== "") {
        $depurar["sesion_seleccionada"] = $this->consultarSesionFlujo($db, $sessionKey, $inicio, $fin);
        $depurar["timeline"] = $this->consultarTimelineSesion($db, $sessionKey, $inicio, $fin, $tablas, 200);
        foreach ($depurar["timeline"] as $item) {
          if ($item["origen"] === "evento") { $depurar["resumen"]["eventos_total"]++; }
          if ($item["origen"] === "busqueda") { $depurar["resumen"]["busquedas_total"]++; }
          if (!empty($item["es_conversion"])) { $depurar["resumen"]["conversiones_total"]++; }
        }
      }
      return $this->respuesta(false, "success", "Flujo de navegacion Ecommerce / Analytics", $depurar);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), $depurar);
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: explorar secciones detalladas de Ecommerce Analytics en modo interno.
   * Impacto: permite analizar sesiones, page views, productos, busquedas y WhatsApp sin exponer PII ni tocar ventas/inventario.
   * Contrato: solo lectura; devuelve session_id truncado como session_key anonima.
   */
  public function analyticsAnalisisInterno($filtros = array()) {
    $db = $this->getConexion();
    $desde = $this->fechaFiltro($this->valor($filtros, "desde", date("Y-m-d", strtotime("-7 days"))), date("Y-m-d", strtotime("-7 days")));
    $hasta = $this->fechaFiltro($this->valor($filtros, "hasta", date("Y-m-d")), date("Y-m-d"));
    $limite = max(10, min(500, intval($this->valor($filtros, "limite", 100))));
    $seccion = $this->seccionAnalisis($this->valor($filtros, "seccion", "sesiones"));
    $texto = $this->textoFiltroAnalisis($this->valor($filtros, "q", ""));
    $tablas = $this->tablasDisponibles($db);
    $depurar = array(
      "read_only" => true,
      "configurado" => !empty($tablas["sesiones"]) || !empty($tablas["eventos"]) || !empty($tablas["busquedas"]),
      "seccion" => $seccion,
      "rango" => array("desde" => $desde, "hasta" => $hasta, "limite" => $limite, "q" => $texto),
      "fecha_consulta" => date("Y-m-d H:i:s"),
      "tablas" => $tablas,
      "resumen" => array(),
      "columnas" => array(),
      "items" => array(),
      "guardrails" => $this->guardrails()
    );
    if (!$db) {
      return $this->respuesta(true, "warning", "Conexion MySQL no disponible", $depurar);
    }
    $inicio = $desde . " 00:00:00";
    $fin = $hasta . " 23:59:59";
    try {
      if ($seccion === "sesiones") { $this->analisisSesiones($db, $depurar, $inicio, $fin, $limite, $texto); }
      if ($seccion === "page_views") { $this->analisisPageViews($db, $depurar, $inicio, $fin, $limite, $texto); }
      if ($seccion === "productos") { $this->analisisProductosVistos($db, $depurar, $inicio, $fin, $limite, $texto); }
      if ($seccion === "busquedas") { $this->analisisBusquedas($db, $depurar, $inicio, $fin, $limite, $texto); }
      if ($seccion === "whatsapp") { $this->analisisWhatsapp($db, $depurar, $inicio, $fin, $limite, $texto); }
      if ($seccion === "eventos") { $this->analisisEventos($db, $depurar, $inicio, $fin, $limite, $texto); }
      return $this->respuesta(false, "success", "Analisis detallado Ecommerce / Analytics", $depurar);
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
      $incidenciaSeo = $this->registrarIncidenciaSeoUrlSiAplica($db, $sesion["primer_ruta"], array("fuente" => "analytics_sesion"));
      return $this->respuesta(false, "success", "Sesion analytics registrada", array(
        "escribe_bd" => true,
        "session_id_hash" => $sesion["session_id_hash"],
        "incidencia_seo_url" => $incidenciaSeo,
        "guardrails" => $this->guardrails(true)
      ));
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
      $incidenciaSeo = $this->registrarIncidenciaSeoUrlSiAplica($db, $evento["ruta"], array("fuente" => "analytics_evento", "tipo_evento" => $evento["tipo_evento"]));
      $db->commit();
      return $this->respuesta(false, "success", "Evento analytics registrado", array(
        "escribe_bd" => true,
        "id_analytics_evento" => $idEvento,
        "incidencia_seo_url" => $incidenciaSeo,
        "guardrails" => $this->guardrails(true)
      ));
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
      $this->upsertSesionLigera($db, $this->eventoDesdeBusqueda($busqueda));
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
      return $this->respuesta(false, "success", "Busqueda analytics registrada", array("escribe_bd" => true, "id_analytics_busqueda" => intval($db->lastInsertId()), "guardrails" => $this->guardrails(true)));
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
      $db->beginTransaction();
      $this->upsertSesionLigera($db, $evento);
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_analytics_eventos
          (session_id_hash, tipo_evento, canal, ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, mascota, necesidad, id_publicacion, id_sku, slug, metadata_json, fecha_registro)
        VALUES
          (:session_id_hash, :tipo_evento, :canal, :ruta, :referrer, :utm_source, :utm_medium, :utm_campaign, :dispositivo, :mascota, :necesidad, :id_publicacion, :id_sku, :slug, :metadata_json, NOW())");
      $stmt->execute($this->paramsEvento($evento));
      $idEvento = intval($db->lastInsertId());
      $id = $this->insertarConversionDesdeEvento($db, $evento);
      $incidenciaSeo = $this->registrarIncidenciaSeoUrlSiAplica($db, $evento["ruta"], array("fuente" => "analytics_conversion", "tipo_evento" => $evento["tipo_evento"]));
      $db->commit();
      return $this->respuesta(false, "success", "Conversion analytics registrada", array(
        "escribe_bd" => true,
        "id_analytics_evento" => $idEvento,
        "id_analytics_conversion" => $id,
        "incidencia_seo_url" => $incidenciaSeo,
        "guardrails" => $this->guardrails(true)
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) { $db->rollBack(); }
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
    $stmt = $db->prepare("SELECT tipo_evento, canal, ruta, slug, fecha_registro
      FROM erp_ecommerce_analytics_eventos
      WHERE fecha_registro BETWEEN :inicio AND :fin
      ORDER BY fecha_registro DESC, id_analytics_evento DESC
      LIMIT 1");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["ultimo_evento"] = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
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
      if ($tipo === "page_view") { $depurar["resumen"]["page_views"] = $total; }
      if ($tipo === "view_product") { $depurar["resumen"]["productos_vistos"] = $total; }
      if ($tipo === "add_to_quote") { $depurar["resumen"]["add_to_quote_total"] = $total; }
      if ($tipo === "quote_dryrun") { $depurar["resumen"]["quote_dryrun_total"] = $total; }
      if ($tipo === "quote_preflight") { $depurar["resumen"]["quote_preflight_total"] = $total; }
      if ($tipo === "open_whatsapp") { $depurar["resumen"]["whatsapp_total"] = $total; }
      if ($tipo === "facturacion_view") { $depurar["resumen"]["facturacion_view_total"] = $total; }
      if ($tipo === "facturacion_submit") { $depurar["resumen"]["facturacion_submit_total"] = $total; }
    }
    $depurar["productos_interes_sin_conversion"] = $this->consultaProductosInteresSinConversion($db, $inicio, $fin, $limite);
  }

  private function analisisSesiones($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("session_key", "eventos_total", "primer_ruta", "ultimo_ruta", "canal", "dispositivo_aproximado", "fecha_inicio", "fecha_ultima_actividad");
    $where = "COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :inicio AND :fin";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (session_id_hash LIKE :q_prefix OR primer_ruta LIKE :q OR ultimo_ruta LIKE :q OR referrer LIKE :q OR canal LIKE :q OR dispositivo_aproximado LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
      $params[":q_prefix"] = $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN eventos_total<=1 THEN 1 ELSE 0 END) un_evento, AVG(eventos_total) promedio_eventos
      FROM erp_ecommerce_analytics_sesiones
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array(
      "total" => intval($this->valor($resumen, "total", 0)),
      "sesiones_un_evento" => intval($this->valor($resumen, "un_evento", 0)),
      "eventos_promedio" => round(floatval($this->valor($resumen, "promedio_eventos", 0)), 2)
    );
    $stmt = $db->prepare("SELECT LEFT(session_id_hash, 12) session_key, canal, primer_ruta, ultimo_ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, fecha_inicio, fecha_ultima_actividad, eventos_total
      FROM erp_ecommerce_analytics_sesiones
      WHERE " . $where . "
      ORDER BY COALESCE(fecha_ultima_actividad, fecha_inicio) DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function analisisPageViews($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("ruta", "total", "sesiones", "primera_fecha", "ultima_fecha");
    $where = "fecha_registro BETWEEN :inicio AND :fin AND tipo_evento='page_view'";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (ruta LIKE :q OR referrer LIKE :q OR utm_source LIKE :q OR utm_campaign LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones, COUNT(DISTINCT ruta) urls
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array("page_views" => intval($this->valor($resumen, "total", 0)), "sesiones" => intval($this->valor($resumen, "sesiones", 0)), "urls" => intval($this->valor($resumen, "urls", 0)));
    $stmt = $db->prepare("SELECT ruta, COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones, MIN(fecha_registro) primera_fecha, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where . " AND TRIM(COALESCE(ruta,''))<>''
      GROUP BY ruta
      ORDER BY total DESC, ultima_fecha DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function analisisProductosVistos($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("slug", "id_publicacion", "id_sku", "vistas", "sesiones", "primera_fecha", "ultima_fecha");
    $where = "fecha_registro BETWEEN :inicio AND :fin AND tipo_evento='view_product'";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (slug LIKE :q OR ruta LIKE :q OR CAST(id_publicacion AS CHAR) LIKE :q OR CAST(id_sku AS CHAR) LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) vistas, COUNT(DISTINCT session_id_hash) sesiones, COUNT(DISTINCT COALESCE(NULLIF(slug,''), CONCAT(id_publicacion,'/',id_sku))) productos
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array("vistas" => intval($this->valor($resumen, "vistas", 0)), "sesiones" => intval($this->valor($resumen, "sesiones", 0)), "productos" => intval($this->valor($resumen, "productos", 0)));
    $stmt = $db->prepare("SELECT slug, id_publicacion, id_sku, COUNT(*) vistas, COUNT(DISTINCT session_id_hash) sesiones, MIN(fecha_registro) primera_fecha, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where . " AND (TRIM(COALESCE(slug,''))<>'' OR COALESCE(id_publicacion,0)>0 OR COALESCE(id_sku,0)>0)
      GROUP BY slug, id_publicacion, id_sku
      ORDER BY vistas DESC, ultima_fecha DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function analisisBusquedas($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("query_normalizada", "total", "sin_resultados_total", "resultados_promedio", "sesiones", "ultima_fecha");
    $where = "fecha_registro BETWEEN :inicio AND :fin";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (query_normalizada LIKE :q OR query LIKE :q OR ruta LIKE :q OR mascota LIKE :q OR necesidad LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultados, COUNT(DISTINCT session_id_hash) sesiones
      FROM erp_ecommerce_analytics_busquedas
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array("busquedas" => intval($this->valor($resumen, "total", 0)), "sin_resultados" => intval($this->valor($resumen, "sin_resultados", 0)), "sesiones" => intval($this->valor($resumen, "sesiones", 0)));
    $stmt = $db->prepare("SELECT query_normalizada, COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultados_total, ROUND(AVG(resultados_total), 2) resultados_promedio, COUNT(DISTINCT session_id_hash) sesiones, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_analytics_busquedas
      WHERE " . $where . "
      GROUP BY query_normalizada
      ORDER BY total DESC, sin_resultados_total DESC, query_normalizada ASC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function analisisWhatsapp($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("fecha", "session_key", "ruta", "slug", "id_publicacion", "id_sku", "canal", "dispositivo_aproximado");
    $where = "fecha_registro BETWEEN :inicio AND :fin AND tipo_evento='open_whatsapp'";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (ruta LIKE :q OR slug LIKE :q OR canal LIKE :q OR CAST(id_publicacion AS CHAR) LIKE :q OR CAST(id_sku AS CHAR) LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones, COUNT(DISTINCT COALESCE(NULLIF(slug,''), CONCAT(id_publicacion,'/',id_sku))) productos
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array("aperturas_whatsapp" => intval($this->valor($resumen, "total", 0)), "sesiones" => intval($this->valor($resumen, "sesiones", 0)), "productos" => intval($this->valor($resumen, "productos", 0)));
    $stmt = $db->prepare("SELECT fecha_registro fecha, LEFT(session_id_hash, 12) session_key, ruta, slug, id_publicacion, id_sku, canal, dispositivo_aproximado
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where . "
      ORDER BY fecha_registro DESC, id_analytics_evento DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function analisisEventos($db, &$depurar, $inicio, $fin, $limite, $texto) {
    $depurar["columnas"] = array("fecha", "tipo_evento", "session_key", "ruta", "slug", "id_publicacion", "id_sku", "canal");
    $where = "fecha_registro BETWEEN :inicio AND :fin";
    $params = array(":inicio" => $inicio, ":fin" => $fin);
    if ($texto !== "") {
      $where .= " AND (tipo_evento LIKE :q OR ruta LIKE :q OR slug LIKE :q OR canal LIKE :q OR LEFT(session_id_hash, 12) LIKE :q)";
      $params[":q"] = "%" . $texto . "%";
    }
    $stmt = $db->prepare("SELECT COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones, COUNT(DISTINCT tipo_evento) tipos
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where);
    $stmt->execute($params);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $depurar["resumen"] = array("eventos" => intval($this->valor($resumen, "total", 0)), "sesiones" => intval($this->valor($resumen, "sesiones", 0)), "tipos" => intval($this->valor($resumen, "tipos", 0)));
    $stmt = $db->prepare("SELECT fecha_registro fecha, tipo_evento, LEFT(session_id_hash, 12) session_key, ruta, slug, id_publicacion, id_sku, canal, dispositivo_aproximado
      FROM erp_ecommerce_analytics_eventos
      WHERE " . $where . "
      ORDER BY fecha_registro DESC, id_analytics_evento DESC
      LIMIT " . intval($limite));
    $stmt->execute($params);
    $depurar["items"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultarSesionesFlujo($db, $inicio, $fin, $limite, $minEventos = 1) {
    $stmt = $db->prepare("SELECT s.session_id_hash, s.canal, s.primer_ruta, s.ultimo_ruta, s.referrer, s.utm_source, s.utm_medium, s.utm_campaign, s.dispositivo_aproximado, s.fecha_inicio, s.fecha_ultima_actividad, s.eventos_total,
        (SELECT COUNT(*) FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_ev AND :fin_ev) eventos_rango,
        (SELECT COUNT(*) FROM erp_ecommerce_analytics_busquedas b WHERE b.session_id_hash=s.session_id_hash AND b.fecha_registro BETWEEN :inicio_bus AND :fin_bus) busquedas_rango,
        (SELECT e.ruta FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr AND :fin_attr AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_ruta,
        (SELECT e.referrer FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr2 AND :fin_attr2 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_referrer,
        (SELECT e.utm_source FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr3 AND :fin_attr3 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_source,
        (SELECT e.utm_medium FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr4 AND :fin_attr4 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_medium,
        (SELECT e.utm_campaign FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr5 AND :fin_attr5 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_campaign
      FROM erp_ecommerce_analytics_sesiones s
      WHERE COALESCE(s.fecha_ultima_actividad, s.fecha_inicio) BETWEEN :inicio AND :fin
      HAVING (eventos_rango + busquedas_rango) >= :min_eventos
      ORDER BY COALESCE(s.fecha_ultima_actividad, s.fecha_inicio) DESC
      LIMIT " . intval($limite));
    $stmt->execute(array(
      ":inicio" => $inicio,
      ":fin" => $fin,
      ":min_eventos" => $minEventos,
      ":inicio_ev" => $inicio,
      ":fin_ev" => $fin,
      ":inicio_bus" => $inicio,
      ":fin_bus" => $fin,
      ":inicio_attr" => $inicio,
      ":fin_attr" => $fin,
      ":inicio_attr2" => $inicio,
      ":fin_attr2" => $fin,
      ":inicio_attr3" => $inicio,
      ":fin_attr3" => $fin,
      ":inicio_attr4" => $inicio,
      ":fin_attr4" => $fin,
      ":inicio_attr5" => $inicio,
      ":fin_attr5" => $fin
    ));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $atribucion = $this->atribucionFlujoDesdeFila($fila);
      $items[] = array(
        "session_key" => substr((string) $this->valor($fila, "session_id_hash", ""), 0, 12),
        "canal" => $this->valor($fila, "canal", ""),
        "primer_ruta" => $this->valor($fila, "primer_ruta", ""),
        "ultimo_ruta" => $this->valor($fila, "ultimo_ruta", ""),
        "referrer" => $this->valor($fila, "referrer", ""),
        "utm_source" => $this->valor($fila, "utm_source", ""),
        "utm_medium" => $this->valor($fila, "utm_medium", ""),
        "utm_campaign" => $this->valor($fila, "utm_campaign", ""),
        "dispositivo_aproximado" => $this->valor($fila, "dispositivo_aproximado", ""),
        "fecha_inicio" => $this->valor($fila, "fecha_inicio", ""),
        "fecha_ultima_actividad" => $this->valor($fila, "fecha_ultima_actividad", ""),
        "eventos_total" => intval($this->valor($fila, "eventos_total", 0)),
        "eventos_rango" => intval($this->valor($fila, "eventos_rango", 0)),
        "busquedas_rango" => intval($this->valor($fila, "busquedas_rango", 0)),
        "atribucion" => $atribucion
      );
    }
    return $items;
  }

  private function consultarSesionFlujo($db, $sessionKey, $inicio, $fin) {
    $stmt = $db->prepare("SELECT s.session_id_hash, LEFT(s.session_id_hash, 12) session_key, s.canal, s.primer_ruta, s.ultimo_ruta, s.referrer, s.utm_source, s.utm_medium, s.utm_campaign, s.dispositivo_aproximado, s.fecha_inicio, s.fecha_ultima_actividad, s.eventos_total,
        (SELECT e.ruta FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr AND :fin_attr AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_ruta,
        (SELECT e.referrer FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr2 AND :fin_attr2 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_referrer,
        (SELECT e.utm_source FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr3 AND :fin_attr3 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_source,
        (SELECT e.utm_medium FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr4 AND :fin_attr4 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_medium,
        (SELECT e.utm_campaign FROM erp_ecommerce_analytics_eventos e WHERE e.session_id_hash=s.session_id_hash AND e.fecha_registro BETWEEN :inicio_attr5 AND :fin_attr5 AND (e.ruta LIKE '%fbclid=%' OR e.ruta LIKE '%gclid=%' OR e.ruta LIKE '%gbraid=%' OR e.ruta LIKE '%wbraid=%' OR e.ruta LIKE '%msclkid=%' OR e.ruta LIKE '%ttclid=%' OR TRIM(COALESCE(e.referrer,''))<>'' OR TRIM(COALESCE(e.utm_source,''))<>'') ORDER BY e.fecha_registro ASC, e.id_analytics_evento ASC LIMIT 1) evento_atribucion_utm_campaign
      FROM erp_ecommerce_analytics_sesiones s
      WHERE s.session_id_hash LIKE :session_key
        AND COALESCE(s.fecha_ultima_actividad, s.fecha_inicio) BETWEEN :inicio AND :fin
      ORDER BY COALESCE(s.fecha_ultima_actividad, s.fecha_inicio) DESC
      LIMIT 1");
    $stmt->execute(array(
      ":session_key" => $sessionKey . "%",
      ":inicio" => $inicio,
      ":fin" => $fin,
      ":inicio_attr" => $inicio,
      ":fin_attr" => $fin,
      ":inicio_attr2" => $inicio,
      ":fin_attr2" => $fin,
      ":inicio_attr3" => $inicio,
      ":fin_attr3" => $fin,
      ":inicio_attr4" => $inicio,
      ":fin_attr4" => $fin,
      ":inicio_attr5" => $inicio,
      ":fin_attr5" => $fin
    ));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$fila) { return array(); }
    $atribucion = $this->atribucionFlujoDesdeFila($fila);
    return array(
      "session_key" => $this->valor($fila, "session_key", ""),
      "canal" => $this->valor($fila, "canal", ""),
      "primer_ruta" => $this->valor($fila, "primer_ruta", ""),
      "ultimo_ruta" => $this->valor($fila, "ultimo_ruta", ""),
      "referrer" => $this->valor($fila, "referrer", ""),
      "utm_source" => $this->valor($fila, "utm_source", ""),
      "utm_medium" => $this->valor($fila, "utm_medium", ""),
      "utm_campaign" => $this->valor($fila, "utm_campaign", ""),
      "dispositivo_aproximado" => $this->valor($fila, "dispositivo_aproximado", ""),
      "fecha_inicio" => $this->valor($fila, "fecha_inicio", ""),
      "fecha_ultima_actividad" => $this->valor($fila, "fecha_ultima_actividad", ""),
      "eventos_total" => intval($this->valor($fila, "eventos_total", 0)),
      "atribucion" => $atribucion
    );
  }

  private function consultarTimelineSesion($db, $sessionKey, $inicio, $fin, $tablas, $limite) {
    $timeline = array();
    $stmt = $db->prepare("SELECT id_analytics_evento id_registro, tipo_evento, canal, ruta, referrer, utm_source, utm_medium, utm_campaign, id_publicacion, id_sku, slug, mascota, necesidad, fecha_registro
      FROM erp_ecommerce_analytics_eventos
      WHERE session_id_hash LIKE :session_key
        AND fecha_registro BETWEEN :inicio AND :fin
      ORDER BY fecha_registro ASC, id_analytics_evento ASC
      LIMIT " . intval($limite));
    $stmt->execute(array(":session_key" => $sessionKey . "%", ":inicio" => $inicio, ":fin" => $fin));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $tipo = $this->valor($fila, "tipo_evento", "");
      $atribucion = $this->atribucionDesdeDatos($fila);
      $timeline[] = array(
        "origen" => "evento",
        "tipo" => $tipo,
        "etiqueta" => $this->etiquetaEventoFlujo($tipo),
        "ruta" => $this->valor($fila, "ruta", ""),
        "referrer" => $this->valor($fila, "referrer", ""),
        "utm_source" => $this->valor($fila, "utm_source", ""),
        "utm_medium" => $this->valor($fila, "utm_medium", ""),
        "utm_campaign" => $this->valor($fila, "utm_campaign", ""),
        "canal" => $this->valor($fila, "canal", ""),
        "id_publicacion" => intval($this->valor($fila, "id_publicacion", 0)),
        "id_sku" => intval($this->valor($fila, "id_sku", 0)),
        "slug" => $this->valor($fila, "slug", ""),
        "mascota" => $this->valor($fila, "mascota", ""),
        "necesidad" => $this->valor($fila, "necesidad", ""),
        "fecha" => $this->valor($fila, "fecha_registro", ""),
        "es_conversion" => in_array($tipo, array("add_to_quote", "remove_from_quote", "quote_dryrun", "quote_preflight", "open_whatsapp", "facturacion_submit"), true),
        "atribucion" => $atribucion
      );
    }
    if (!empty($tablas["busquedas"])) {
      $stmt = $db->prepare("SELECT id_analytics_busqueda id_registro, canal, query_normalizada, ruta, resultados_total, sin_resultados, mascota, necesidad, fecha_registro
        FROM erp_ecommerce_analytics_busquedas
        WHERE session_id_hash LIKE :session_key
          AND fecha_registro BETWEEN :inicio AND :fin
        ORDER BY fecha_registro ASC, id_analytics_busqueda ASC
        LIMIT " . intval($limite));
      $stmt->execute(array(":session_key" => $sessionKey . "%", ":inicio" => $inicio, ":fin" => $fin));
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $timeline[] = array(
          "origen" => "busqueda",
          "tipo" => "search",
          "etiqueta" => "Busqueda",
          "ruta" => $this->valor($fila, "ruta", ""),
          "canal" => $this->valor($fila, "canal", ""),
          "query" => $this->valor($fila, "query_normalizada", ""),
          "resultados_total" => intval($this->valor($fila, "resultados_total", 0)),
          "sin_resultados" => intval($this->valor($fila, "sin_resultados", 0)) === 1,
          "mascota" => $this->valor($fila, "mascota", ""),
          "necesidad" => $this->valor($fila, "necesidad", ""),
          "fecha" => $this->valor($fila, "fecha_registro", ""),
          "es_conversion" => false
        );
      }
    }
    usort($timeline, function ($a, $b) {
      return strcmp($a["fecha"], $b["fecha"]);
    });
    return array_slice($timeline, 0, $limite);
  }

  private function etiquetaEventoFlujo($tipo) {
    $mapa = array(
      "page_view" => "Pagina vista",
      "view_product" => "Producto visto",
      "search" => "Busqueda",
      "select_mascota" => "Mascota seleccionada",
      "select_necesidad" => "Necesidad seleccionada",
      "add_to_quote" => "Agregado a cotizacion",
      "remove_from_quote" => "Quitado de cotizacion",
      "quote_dryrun" => "Validacion carrito",
      "quote_preflight" => "Preflight cotizacion",
      "open_whatsapp" => "Apertura WhatsApp",
      "facturacion_view" => "Vista facturacion",
      "facturacion_submit" => "Envio facturacion"
    );
    return isset($mapa[$tipo]) ? $mapa[$tipo] : $tipo;
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

  private function cargarDashboardSesiones($db, &$depurar, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT session_id_hash, canal, primer_ruta, ultimo_ruta, referrer, utm_source, utm_medium, utm_campaign, dispositivo_aproximado, fecha_inicio, fecha_ultima_actividad, eventos_total
      FROM erp_ecommerce_analytics_sesiones
      WHERE fecha_inicio BETWEEN :inicio AND :fin
      ORDER BY COALESCE(fecha_ultima_actividad, fecha_inicio) DESC
      LIMIT " . intval($limite));
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $sesiones = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $atribucion = $this->atribucionDesdeDatos($fila);
      $sesiones[] = array(
        "session_id_hash_corto" => substr((string) $this->valor($fila, "session_id_hash", ""), 0, 12),
        "canal" => $this->valor($fila, "canal", ""),
        "primer_ruta" => $this->valor($fila, "primer_ruta", ""),
        "ultimo_ruta" => $this->valor($fila, "ultimo_ruta", ""),
        "referrer" => $this->valor($fila, "referrer", ""),
        "utm_source" => $this->valor($fila, "utm_source", ""),
        "utm_medium" => $this->valor($fila, "utm_medium", ""),
        "utm_campaign" => $this->valor($fila, "utm_campaign", ""),
        "dispositivo_aproximado" => $this->valor($fila, "dispositivo_aproximado", ""),
        "fecha_inicio" => $this->valor($fila, "fecha_inicio", ""),
        "fecha_ultima_actividad" => $this->valor($fila, "fecha_ultima_actividad", ""),
        "eventos_total" => intval($this->valor($fila, "eventos_total", 0)),
        "atribucion" => $atribucion
      );
    }
    $depurar["sesiones_recientes"] = $sesiones;
    $stmt = $db->prepare("SELECT canal valor, COUNT(*) total, MAX(fecha_inicio) ultima_fecha
      FROM erp_ecommerce_analytics_sesiones
      WHERE fecha_inicio BETWEEN :inicio AND :fin
        AND TRIM(COALESCE(canal,''))<>''
      GROUP BY canal
      ORDER BY total DESC, valor ASC
      LIMIT " . intval($limite));
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["canales"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function cargarDashboardCalidadTracking($db, &$depurar, $inicio, $fin) {
    $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN eventos_total<=1 THEN 1 ELSE 0 END) un_evento
      FROM erp_ecommerce_analytics_sesiones
      WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $sesiones = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $totalSesiones = intval($this->valor($sesiones, "total", 0));
    $unEvento = intval($this->valor($sesiones, "un_evento", 0));

    $stmt = $db->prepare("SELECT COUNT(*) eventos, COUNT(DISTINCT session_id_hash) sesiones
      FROM erp_ecommerce_analytics_eventos
      WHERE fecha_registro BETWEEN :inicio AND :fin");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $eventos = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
    $eventosTotal = intval($this->valor($eventos, "eventos", 0));
    $sesionesEventos = intval($this->valor($eventos, "sesiones", 0));
    $porcentajeUnEvento = $totalSesiones > 0 ? round(($unEvento / $totalSesiones) * 100, 2) : 0;
    $eventosPorSesion = $sesionesEventos > 0 ? round($eventosTotal / $sesionesEventos, 2) : 0;
    $diagnostico = "normal";
    if ($totalSesiones > 0 && $porcentajeUnEvento >= 65) {
      $diagnostico = "sesiones_un_evento_altas";
    } elseif ($sesionesEventos > 0 && $eventosPorSesion < 1.5) {
      $diagnostico = "baja_profundidad";
    }

    $depurar["calidad_tracking"] = array(
      "sesiones_un_evento" => $unEvento,
      "sesiones_un_evento_pct" => $porcentajeUnEvento,
      "sesiones_distintas_eventos" => $sesionesEventos,
      "eventos_por_sesion" => $eventosPorSesion,
      "diagnostico" => $diagnostico,
      "lectura" => $diagnostico === "sesiones_un_evento_altas"
        ? "Muchas sesiones tienen un solo evento; revisar persistencia de session_id, doble carga del tracker o trafico automatico."
        : "La relacion eventos/sesion esta dentro de un rango revisable."
    );
  }

  private function cargarDashboardAtribucion($db, &$depurar, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT primer_ruta, ultimo_ruta, referrer, utm_source, utm_medium, utm_campaign
      FROM erp_ecommerce_analytics_sesiones
      WHERE fecha_inicio BETWEEN :inicio AND :fin
      ORDER BY COALESCE(fecha_ultima_actividad, fecha_inicio) DESC
      LIMIT 5000");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $fuentes = array();
    $medios = array();
    $campanias = array();
    $clickIds = array();
    $muestra = 0;
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $muestra++;
      $atribucion = $this->atribucionDesdeDatos($fila);
      $this->incrementarConteo($fuentes, $atribucion["fuente_detectada"]);
      $this->incrementarConteo($medios, $atribucion["medio_detectado"]);
      if ($atribucion["campania_detectada"] !== "") {
        $this->incrementarConteo($campanias, $atribucion["campania_detectada"]);
      }
      if ($atribucion["click_id_tipo"] !== "") {
        $this->incrementarConteo($clickIds, $atribucion["click_id_tipo"]);
      }
    }
    $depurar["fuentes_trafico"] = $this->conteosTop($fuentes, $limite);
    $depurar["medios_trafico"] = $this->conteosTop($medios, $limite);
    $depurar["campanias_trafico"] = $this->conteosTop($campanias, $limite);
    $depurar["click_ids_detectados"] = $this->conteosTop($clickIds, $limite);
    $depurar["atribucion"] = array(
      "read_only" => true,
      "muestra_sesiones" => $muestra,
      "muestra_maxima" => 5000,
      "no_guarda_valor_click_id" => true,
      "senales" => array("fbclid", "gclid", "gbraid", "wbraid", "msclkid", "ttclid", "utm_source", "utm_medium", "referrer")
    );
  }

  private function cargarDashboardConversiones($db, &$depurar, $inicio, $fin, $limite) {
    $stmt = $db->prepare("SELECT tipo_conversion valor, COUNT(*) total, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_analytics_conversiones
      WHERE fecha_registro BETWEEN :inicio AND :fin
      GROUP BY tipo_conversion
      ORDER BY total DESC, valor ASC");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["conversiones_por_tipo"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT tipo_conversion, canal, id_publicacion, id_sku, slug, ruta_origen, etapa_origen, fecha_registro
      FROM erp_ecommerce_analytics_conversiones
      WHERE fecha_registro BETWEEN :inicio AND :fin
        AND tipo_conversion IN ('facturacion_view', 'facturacion_submit')
      ORDER BY fecha_registro DESC
      LIMIT " . intval($limite));
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $depurar["facturacion_eventos"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    $depurar["resumen"]["page_views"] = $totales["page_views"];
    $depurar["resumen"]["productos_vistos"] = $totales["productos_vistos"];
    $depurar["resumen"]["busquedas_total"] = $totales["busquedas_total"];
    $depurar["resumen"]["busquedas_sin_resultados"] = $totales["busquedas_sin_resultados"];
    $depurar["resumen"]["add_to_quote_total"] = $totales["add_to_quote_total"];
    $depurar["resumen"]["quote_dryrun_total"] = $totales["dryrun_total"];
    $depurar["resumen"]["quote_preflight_total"] = $totales["preflight_total"];
    $depurar["resumen"]["whatsapp_total"] = $totales["whatsapp_total"];
    $depurar["resumen"]["facturacion_view_total"] = $totales["facturacion_view_total"];
    $depurar["resumen"]["facturacion_submit_total"] = $totales["facturacion_submit_total"];
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

  private function eventoDesdeBusqueda($busqueda) {
    return array(
      "session_id_hash" => $busqueda["session_id_hash"],
      "tipo_evento" => "search",
      "canal" => $busqueda["canal"],
      "ruta" => $busqueda["ruta"],
      "referrer" => $this->valor($busqueda, "referrer", ""),
      "utm_source" => $this->valor($busqueda, "utm_source", ""),
      "utm_medium" => $this->valor($busqueda, "utm_medium", ""),
      "utm_campaign" => $this->valor($busqueda, "utm_campaign", ""),
      "dispositivo_aproximado" => "",
      "mascota" => $busqueda["mascota"],
      "necesidad" => $busqueda["necesidad"],
      "id_publicacion" => 0,
      "id_sku" => 0,
      "slug" => "",
      "metadata" => $busqueda["metadata"]
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
    $evento = array(
      "session_id_hash" => $this->hashAnonimo($sessionId),
      "tipo_evento" => $this->limpiarToken($this->valor($datos, "tipo_evento", ""), 60),
      "canal" => $this->limpiarToken($this->valor($datos, "canal", "web_publica"), 50),
      "ruta" => $this->limpiarRutaAnalytics($this->valor($datos, "ruta", "")),
      "referrer" => $this->limpiarRutaAnalytics($this->valor($datos, "referrer", $this->valor($datos, "referer", ""))),
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
    $evento["atribucion"] = $this->atribucionDesdeDatos($evento);
    $evento["metadata"] = $this->metadataConAtribucion($evento["metadata"], $evento["atribucion"]);
    return $evento;
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

  private function seccionAnalisis($valor) {
    $valor = $this->limpiarToken($valor, 40);
    $permitidas = array("sesiones", "page_views", "productos", "busquedas", "whatsapp", "eventos");
    return in_array($valor, $permitidas, true) ? $valor : "sesiones";
  }

  private function textoFiltroAnalisis($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/[\x00-\x1F\x7F]/', '', $valor);
    return substr($valor, 0, 120);
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

  private function limpiarRutaAnalytics($valor) {
    return $this->redactarClickIds($this->limpiarRuta($valor));
  }

  private function redactarClickIds($valor) {
    $valor = (string) $valor;
    if ($valor === "") { return ""; }
    return preg_replace('/([?&](?:srsltid|fbclid|gclid|gbraid|wbraid|msclkid|ttclid)=)[^&#]*/i', '$1__redacted__', $valor);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-28
   * Proposito: crear una incidencia SEO pendiente cuando Analytics detecta una URL publica no canonica.
   * Impacto: Ecommerce Analytics/SEO; alimenta `erp_ecommerce_seo_urls_viejas` sin crear redirecciones automaticas.
   * Contrato: escritura acotada solo si Analytics ya esta autorizado y la tabla SEO existe; no guarda valores completos de click-id.
   */
  private function registrarIncidenciaSeoUrlSiAplica($db, $ruta, $contexto = array()) {
    if (!$db || !$this->tablaExisteDb($db, "erp_ecommerce_seo_urls_viejas")) {
      return array("aplica" => false, "motivo" => "tabla_seo_urls_viejas_no_disponible");
    }
    $incidencia = $this->incidenciaSeoAnalyticsDesdeRuta($ruta);
    if (!$incidencia) {
      return array("aplica" => false, "motivo" => "url_sin_incidencia_seo");
    }
    if ($this->seoUrlYaTieneRedireccion($db, $incidencia["path_original"], $incidencia["path_base"])) {
      return array(
        "aplica" => true,
        "registrada" => false,
        "motivo" => "redireccion_ya_existente",
        "path_original" => $incidencia["path_original"]
      );
    }

    $origen = "analytics";
    $fuente = $this->limpiarToken($this->valor($contexto, "fuente", ""), 40);
    if ($fuente !== "") { $origen .= "_" . $fuente; }
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_seo_urls_viejas
        (url_original, path_original, tipo_detectado, titulo_detectado, origen, estatus_mapeo, url_destino_sugerida, fecha_registro)
      VALUES
        (:url_original, :path_original, :tipo_detectado, NULL, :origen, 'pendiente', NULL, NOW())
      ON DUPLICATE KEY UPDATE
        url_original=VALUES(url_original),
        tipo_detectado=COALESCE(tipo_detectado, VALUES(tipo_detectado))");
    $stmt->execute(array(
      ":url_original" => $incidencia["path_original"],
      ":path_original" => $incidencia["path_original"],
      ":tipo_detectado" => $incidencia["tipo_detectado"],
      ":origen" => substr($origen, 0, 80)
    ));

    return array(
      "aplica" => true,
      "registrada" => true,
      "filas_afectadas" => $stmt->rowCount(),
      "path_original" => $incidencia["path_original"],
      "path_base" => $incidencia["path_base"],
      "tipo_detectado" => $incidencia["tipo_detectado"],
      "motivos" => $incidencia["motivos"],
      "tracking_params_detectados" => $incidencia["tracking_params_detectados"],
      "sin_redireccion_automatica" => true
    );
  }

  private function incidenciaSeoAnalyticsDesdeRuta($ruta) {
    $ruta = trim((string) $ruta);
    if ($ruta === "") { return null; }
    $partes = preg_match('/^https?:\/\//i', $ruta) ? parse_url($ruta) : parse_url($ruta);
    $path = isset($partes["path"]) ? (string) $partes["path"] : "/";
    $query = isset($partes["query"]) ? (string) $partes["query"] : "";
    if ($path === "") { $path = "/"; }
    $path = "/" . ltrim($path, "/");
    $path = preg_replace('/\/+/', '/', $path);
    if (stripos($path, "/ecommercePublico") === 0) { return null; }
    $segmentos = array_values(array_filter(explode("/", trim($path, "/")), function ($segmento) {
      return trim((string) $segmento) !== "";
    }));
    $queryParams = array();
    if ($query !== "") { parse_str($query, $queryParams); }
    $tracking = array();
    foreach (array_keys($queryParams) as $clave) {
      $claveLimpia = strtolower(trim((string) $clave));
      if ($this->esParametroTrackingSeo($claveLimpia)) { $tracking[] = $claveLimpia; }
    }

    $tipo = $this->tipoSeoPathAnalytics($path);
    $motivos = array();
    if (!empty($tracking)) { $motivos[] = "tracking_query_detectado"; }
    if (!empty($segmentos) && strtolower((string) $segmentos[0]) === "producto" && isset($segmentos[1])) {
      $tipo = "producto";
      $slugEntrada = trim(rawurldecode((string) $segmentos[1]));
      if ($slugEntrada !== $this->slugBasicoSeo($slugEntrada)) { $motivos[] = "slug_producto_no_canonico"; }
      if (count($segmentos) > 2) { $motivos[] = "producto_con_segmentos_extra"; }
      if ($query !== "" && empty($tracking)) { $motivos[] = "query_no_canonica_en_producto"; }
    }

    if (empty($motivos)) { return null; }
    return array(
      "path_original" => $this->pathSeoAnalyticsRedactado($path, array_keys($queryParams), $tracking),
      "path_base" => substr($path, 0, 500),
      "tipo_detectado" => $tipo,
      "motivos" => array_values(array_unique($motivos)),
      "tracking_params_detectados" => array_values(array_unique($tracking))
    );
  }

  private function seoUrlYaTieneRedireccion($db, $pathOriginal, $pathBase) {
    if (!$db || !$this->tablaExisteDb($db, "erp_ecommerce_seo_redirecciones")) { return false; }
    $stmt = $db->prepare("SELECT id_redireccion FROM erp_ecommerce_seo_redirecciones WHERE activo=1 AND url_origen IN (:path_original, :path_base) LIMIT 1");
    $stmt->execute(array(":path_original" => $pathOriginal, ":path_base" => $pathBase));
    return (bool) $stmt->fetchColumn();
  }

  private function pathSeoAnalyticsRedactado($path, $queryParams, $tracking) {
    $path = substr((string) $path, 0, 500);
    $queryParams = array_values(array_unique(array_filter(array_map(function ($clave) {
      return strtolower(trim((string) $clave));
    }, (array) $queryParams))));
    if (empty($queryParams)) { return $path; }
    sort($queryParams);
    $tracking = array_values(array_unique(array_map("strtolower", (array) $tracking)));
    $partes = array();
    foreach ($queryParams as $clave) {
      if ($clave === "") { continue; }
      $partes[] = rawurlencode($clave) . "=" . (in_array($clave, $tracking, true) || $this->esParametroTrackingSeo($clave) ? "__redacted__" : "__value__");
    }
    return substr($path . "?" . implode("&", $partes), 0, 500);
  }

  private function tipoSeoPathAnalytics($path) {
    $segmentos = array_values(array_filter(explode("/", trim((string) $path, "/"))));
    $primero = isset($segmentos[0]) ? strtolower((string) $segmentos[0]) : "";
    if (in_array($primero, array("producto", "categoria", "marca", "buscar", "busqueda"), true)) {
      return $primero === "buscar" ? "busqueda" : $primero;
    }
    return "url";
  }

  private function esParametroTrackingSeo($clave) {
    $clave = strtolower(trim((string) $clave));
    if ($clave === "") { return false; }
    if (strpos($clave, "utm_") === 0) { return true; }
    return in_array($clave, array("srsltid", "fbclid", "gclid", "gbraid", "wbraid", "msclkid", "ttclid", "mc_cid", "mc_eid"), true);
  }

  private function slugBasicoSeo($valor) {
    $valor = strtolower(trim((string) $valor));
    $valor = preg_replace('/[^a-z0-9]+/', '-', $valor);
    $valor = trim($valor, "-");
    return $valor;
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
      if ($claveLimpia === "" || in_array($claveLimpia, array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion", "datos_fiscales", "stock", "stock_exacto", "existencia", "srsltid", "fbclid", "gclid", "gbraid", "wbraid", "msclkid", "ttclid"), true)) { continue; }
      $salida[$claveLimpia] = is_array($valor) ? $this->limpiarMetadata($valor, $nivel + 1) : $this->limpiarTextoCorto($valor, 160);
      if (count($salida) >= 30) { break; }
    }
    return $salida;
  }

  private function atribucionFlujoDesdeFila($fila) {
    $atribucion = $this->atribucionDesdeDatos($fila);
    $rutaEvento = $this->valor($fila, "evento_atribucion_ruta", "");
    $referrerEvento = $this->valor($fila, "evento_atribucion_referrer", "");
    $utmSourceEvento = $this->valor($fila, "evento_atribucion_utm_source", "");
    $utmMediumEvento = $this->valor($fila, "evento_atribucion_utm_medium", "");
    $utmCampaignEvento = $this->valor($fila, "evento_atribucion_utm_campaign", "");
    if ($atribucion["fuente_detectada"] !== "directo" || ($rutaEvento === "" && $referrerEvento === "" && $utmSourceEvento === "")) {
      $atribucion["origen_deteccion"] = "sesion";
      return $atribucion;
    }
    $fallback = $this->atribucionDesdeDatos(array(
      "ruta" => $rutaEvento,
      "referrer" => $referrerEvento,
      "utm_source" => $utmSourceEvento,
      "utm_medium" => $utmMediumEvento,
      "utm_campaign" => $utmCampaignEvento
    ));
    $fallback["origen_deteccion"] = "evento";
    return $fallback;
  }

  private function atribucionDesdeDatos($datos) {
    $ruta = $this->limpiarRutaAnalytics($this->valor($datos, "ruta", $this->valor($datos, "primer_ruta", "")));
    $ultimoRuta = $this->limpiarRutaAnalytics($this->valor($datos, "ultimo_ruta", ""));
    $referrer = $this->limpiarRutaAnalytics($this->valor($datos, "referrer", $this->valor($datos, "referer", "")));
    $utmSource = $this->limpiarToken($this->valor($datos, "utm_source", ""), 120);
    $utmMedium = $this->limpiarToken($this->valor($datos, "utm_medium", ""), 120);
    $utmCampaign = $this->limpiarTextoCorto($this->valor($datos, "utm_campaign", ""), 160);
    $urls = array($ruta, $ultimoRuta, $referrer);
    $params = array_merge($this->queryParamsDesdeUrl($ruta), $this->queryParamsDesdeUrl($ultimoRuta), $this->queryParamsDesdeUrl($referrer));
    if ($utmSource === "") { $utmSource = $this->limpiarToken($this->queryValorDesdeUrls($urls, "utm_source"), 120); }
    if ($utmMedium === "") { $utmMedium = $this->limpiarToken($this->queryValorDesdeUrls($urls, "utm_medium"), 120); }
    if ($utmCampaign === "") { $utmCampaign = $this->limpiarTextoCorto($this->queryValorDesdeUrls($urls, "utm_campaign"), 160); }
    $host = $this->hostDesdeUrl($referrer);
    $clickIdTipo = $this->clickIdTipo($params);
    $medioPagado = in_array($utmMedium, array("cpc", "ppc", "paid", "paidsearch", "paid_search", "sem", "display", "paid_social", "social_paid"), true);

    $fuente = "directo";
    $medio = "directo";
    $regla = "sin_referrer_ni_utm";
    $esPago = false;

    if ($clickIdTipo === "fbclid" || in_array($utmSource, array("facebook", "fb", "meta", "instagram", "ig"), true) || $this->hostCoincide($host, array("facebook.com", "fb.com", "instagram.com"))) {
      $fuente = "meta";
      $medio = $medioPagado ? "paid_social" : "social";
      $regla = $clickIdTipo === "fbclid" ? "click_id_fbclid" : ($utmSource !== "" ? "utm_source" : "referrer_host");
      $esPago = $medioPagado || $clickIdTipo === "fbclid";
    } elseif (in_array($clickIdTipo, array("gclid", "gbraid", "wbraid"), true) || $utmSource === "google" || $this->hostCoincide($host, array("google.com", "google.com.mx"))) {
      $esPago = in_array($clickIdTipo, array("gclid", "gbraid", "wbraid"), true) || $medioPagado;
      $fuente = $esPago ? "google_ads" : "google_organico";
      $medio = $esPago ? "paid_search" : "organic_search";
      $regla = $clickIdTipo !== "" ? "click_id_" . $clickIdTipo : ($utmSource !== "" ? "utm_source" : "referrer_host");
    } elseif ($clickIdTipo === "msclkid" || $utmSource === "bing" || $this->hostCoincide($host, array("bing.com"))) {
      $esPago = $clickIdTipo === "msclkid" || $medioPagado;
      $fuente = $esPago ? "bing_ads" : "bing_organico";
      $medio = $esPago ? "paid_search" : "organic_search";
      $regla = $clickIdTipo === "msclkid" ? "click_id_msclkid" : ($utmSource !== "" ? "utm_source" : "referrer_host");
    } elseif ($clickIdTipo === "ttclid" || $utmSource === "tiktok" || $this->hostCoincide($host, array("tiktok.com"))) {
      $fuente = "tiktok";
      $medio = $medioPagado ? "paid_social" : "social";
      $regla = $clickIdTipo === "ttclid" ? "click_id_ttclid" : ($utmSource !== "" ? "utm_source" : "referrer_host");
      $esPago = $medioPagado || $clickIdTipo === "ttclid";
    } elseif ($utmMedium === "email" || in_array($utmSource, array("mail", "newsletter", "email"), true)) {
      $fuente = $utmSource !== "" ? $utmSource : "email";
      $medio = "email";
      $regla = "utm_email";
    } elseif ($utmSource !== "") {
      $fuente = $utmSource;
      $medio = $utmMedium !== "" ? $utmMedium : "campaign";
      $regla = "utm_source";
      $esPago = $medioPagado;
    } elseif ($host !== "") {
      $fuente = $host;
      $medio = "referral";
      $regla = "referrer_host";
    }

    return array(
      "fuente_detectada" => $fuente,
      "medio_detectado" => $medio,
      "campania_detectada" => $utmCampaign,
      "click_id_tipo" => $clickIdTipo,
      "tiene_click_id" => $clickIdTipo !== "",
      "referrer_host" => $host,
      "es_pago_probable" => $esPago,
      "regla_atribucion" => $regla
    );
  }

  private function metadataConAtribucion($metadata, $atribucion) {
    if (!is_array($metadata)) { $metadata = array(); }
    $metadata["atribucion"] = $atribucion;
    return $metadata;
  }

  private function queryParamsDesdeUrl($url) {
    $url = trim((string) $url);
    if ($url === "") { return array(); }
    $query = parse_url($url, PHP_URL_QUERY);
    if ($query === null && strpos($url, "?") !== false) {
      $query = substr($url, strpos($url, "?") + 1);
    }
    if ($query === null || $query === false || $query === "") { return array(); }
    $params = array();
    parse_str($query, $params);
    $salida = array();
    foreach ($params as $clave => $valor) {
      $claveLimpia = strtolower(trim((string) $clave));
      if ($claveLimpia !== "") { $salida[$claveLimpia] = true; }
    }
    return $salida;
  }

  private function clickIdTipo($params) {
    foreach (array("fbclid", "gclid", "gbraid", "wbraid", "msclkid", "ttclid") as $clave) {
      if (array_key_exists($clave, $params)) { return $clave; }
    }
    return "";
  }

  private function queryValorDesdeUrls($urls, $claveBuscada) {
    $claveBuscada = strtolower((string) $claveBuscada);
    foreach ($urls as $url) {
      $query = parse_url((string) $url, PHP_URL_QUERY);
      if ($query === null && strpos((string) $url, "?") !== false) {
        $query = substr((string) $url, strpos((string) $url, "?") + 1);
      }
      if ($query === null || $query === false || $query === "") { continue; }
      $params = array();
      parse_str($query, $params);
      foreach ($params as $clave => $valor) {
        if (strtolower((string) $clave) === $claveBuscada && is_scalar($valor)) {
          return (string) $valor;
        }
      }
    }
    return "";
  }

  private function hostDesdeUrl($url) {
    $url = trim((string) $url);
    if ($url === "") { return ""; }
    if (strpos($url, "//") === 0) { $url = "https:" . $url; }
    $host = parse_url($url, PHP_URL_HOST);
    if (!$host && preg_match('/^([a-z0-9.-]+\.[a-z]{2,})(?:[\/?#]|$)/i', $url, $m)) {
      $host = $m[1];
    }
    $host = strtolower((string) $host);
    $host = preg_replace('/^www\./', '', $host);
    return substr($host, 0, 120);
  }

  private function hostCoincide($host, $dominios) {
    foreach ($dominios as $dominio) {
      if ($host === $dominio || substr($host, -strlen("." . $dominio)) === "." . $dominio) { return true; }
    }
    return false;
  }

  private function incrementarConteo(&$conteos, $clave) {
    $clave = trim((string) $clave);
    if ($clave === "") { return; }
    if (!isset($conteos[$clave])) { $conteos[$clave] = 0; }
    $conteos[$clave]++;
  }

  private function conteosTop($conteos, $limite) {
    arsort($conteos);
    $salida = array();
    foreach ($conteos as $clave => $total) {
      $salida[] = array("valor" => $clave, "total" => intval($total));
      if (count($salida) >= $limite) { break; }
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

  private function guardrails($escrituraAnonimaActiva = false) {
    return array(
      "no_escribe_bd" => !$escrituraAnonimaActiva,
      "escribe_solo_analytics_anonimo" => $escrituraAnonimaActiva,
      "persistencia_requiere_autorizacion_explicita" => !$escrituraAnonimaActiva,
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
