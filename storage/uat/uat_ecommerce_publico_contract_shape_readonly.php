<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar llaves minimas de contratos ecommerce publico para frontend externo.
 * Impacto: detecta cambios de shape antes de romper catalogo, filtros, ficha o carrito.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no registra cotizaciones y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();

$respuestas = array(
  "contratos" => $modelo->contratosApiPublicos(),
  "estado" => $modelo->estadoApiPublica(),
  "frontend_handoff" => $modelo->frontendHandoffPublico(array("limite" => 2)),
  "bootstrap" => $modelo->bootstrapPublico(array("limite_secciones" => 3)),
  "configuracion" => $modelo->configuracionPublica(),
  "seo" => $modelo->seoPublico(),
  "filtros" => $modelo->filtrosPublicos(),
  "busqueda_sugerencias" => $modelo->busquedaSugerenciasPublicas(array("q" => "filtro", "limite" => 4)),
  "navegacion" => $modelo->navegacionPublica(array("limite" => 5)),
  "secciones" => $modelo->seccionesPublicas(array("limite" => 3)),
  "politicas" => $modelo->politicasPublicas(),
  "politica_facturacion" => $modelo->politicaPublica("facturacion"),
  "taxonomia_mascotas" => $modelo->taxonomiaMascotasPublica(),
  "catalogo" => $modelo->catalogoPublico(array("limite" => 3)),
  "catalogo_manifest" => $modelo->catalogoManifestPublico(array("limite_preview" => 2)),
  "producto" => $modelo->productoPublico("slug-de-prueba-no-publicado"),
  "disponibilidad" => $modelo->disponibilidadPublica(array("slug" => "slug-de-prueba-no-publicado")),
  "canales_estado" => $modelo->canalesApiEstadoPublico(),
  "cotizacion_dryrun" => $modelo->cotizacionDryRun(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1)))),
  "cotizacion_preflight" => $modelo->cotizacionPreflight(array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1)),
    "contacto" => array("nombre" => "Cliente prueba", "telefono" => "3322068429"),
    "acepta_contacto_whatsapp" => true,
    "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp")
  )),
  "cotizacion_registrar" => $modelo->cotizacionRegistrarBloqueada(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1)))),
  "facturacion_preflight" => $modelo->facturacionSolicitudPreflight(array(
    "folio_compra" => "TICKET-123",
    "fecha_compra" => "2026-07-29",
    "importe" => 250,
    "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente Publico", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
    "contacto" => array("correo" => "cliente@example.com", "telefono" => "3322068429"),
    "acepta_aviso_privacidad" => true
  )),
  "evento_navegacion_preflight" => $modelo->eventoNavegacionPreflight(array(
    "session_id" => "sess_demo_123",
    "tipo_evento" => "select_mascota",
    "ruta" => "/catalogo",
    "mascota" => "perro",
    "metadata" => array("origen" => "uat")
  )),
  "busqueda_preflight" => $modelo->busquedaRegistrarPreflight(array(
    "session_id" => "sess_demo_123",
    "query" => "croquetas cachorro",
    "mascota" => "perro",
    "necesidad" => "alimento",
    "resultados_total" => 3
  ))
);

$bloqueos = array();
foreach ($respuestas as $nombre => $respuesta) {
  validarWrapper($nombre, $respuesta, $bloqueos);
}

validarRutas($respuestas["contratos"], $bloqueos);
validarEstado($respuestas["estado"], $bloqueos);
validarFrontendHandoff($respuestas["frontend_handoff"], $bloqueos);
validarBootstrap($respuestas["bootstrap"], $bloqueos);
validarConfiguracion($respuestas["configuracion"], $bloqueos);
validarSeo($respuestas["seo"], $bloqueos);
validarFiltros($respuestas["filtros"], $bloqueos);
validarBusquedaSugerencias($respuestas["busqueda_sugerencias"], $bloqueos);
validarNavegacion($respuestas["navegacion"], $bloqueos);
validarSecciones($respuestas["secciones"], $bloqueos);
validarPoliticas($respuestas["politicas"], $bloqueos);
validarPolitica($respuestas["politica_facturacion"], $bloqueos);
validarTaxonomiaMascotas($respuestas["taxonomia_mascotas"], $bloqueos);
validarCatalogo($respuestas["catalogo"], $bloqueos);
validarCatalogoManifest($respuestas["catalogo_manifest"], $bloqueos);
validarProducto($respuestas["producto"], $bloqueos);
validarDisponibilidad($respuestas["disponibilidad"], $bloqueos);
validarCanalesEstado($respuestas["canales_estado"], $bloqueos);
validarDryRun($respuestas["cotizacion_dryrun"], $bloqueos);
validarPreflight($respuestas["cotizacion_preflight"], $bloqueos);
validarRegistroBloqueado($respuestas["cotizacion_registrar"], $bloqueos);
validarExperienciaPreflight("facturacion_preflight", $respuestas["facturacion_preflight"], $bloqueos);
validarExperienciaPreflight("evento_navegacion_preflight", $respuestas["evento_navegacion_preflight"], $bloqueos);
validarExperienciaPreflight("busqueda_preflight", $respuestas["busqueda_preflight"], $bloqueos);

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "shape" => array(
    "wrappers_validados" => array_keys($respuestas),
    "endpoints_publicos_esperados" => 23,
    "item_catalogo_keys" => itemCatalogoKeys()
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_registra_cotizaciones" => true,
    "no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarWrapper($nombre, $respuesta, &$bloqueos) {
  foreach (array("error", "tipo", "mensaje", "api", "depurar") as $key) {
    if (!is_array($respuesta) || !array_key_exists($key, $respuesta)) {
      $bloqueos[] = $nombre . "_falta_wrapper_" . $key;
    }
  }
  if (valorShape($respuesta, array("api", "version"), "") !== "fase1-2026-07-12") {
    $bloqueos[] = $nombre . "_api_version_invalida";
  }
  if (valorShape($respuesta, array("api", "fuente_verdad"), "") !== "ERP") {
    $bloqueos[] = $nombre . "_fuente_verdad_invalida";
  }
}

function validarRutas($respuesta, &$bloqueos) {
  $endpoints = valorShape($respuesta, array("depurar", "endpoints_publicos"), array());
  $rutas = array();
  foreach ($endpoints as $endpoint) {
    $rutas[] = isset($endpoint["ruta"]) ? $endpoint["ruta"] : "";
  }
  foreach (array(
    "/ecommercePublico/estado",
    "/ecommercePublico/frontend_handoff",
    "/ecommercePublico/bootstrap",
    "/ecommercePublico/catalogo",
    "/ecommercePublico/producto/{slug}",
    "/ecommercePublico/filtros",
    "/ecommercePublico/busqueda_sugerencias",
    "/ecommercePublico/navegacion",
    "/ecommercePublico/secciones",
    "/ecommercePublico/politicas",
    "/ecommercePublico/politica/{slug}",
    "/ecommercePublico/taxonomia_mascotas",
    "/ecommercePublico/configuracion",
    "/ecommercePublico/canales_estado",
    "/ecommercePublico/seo",
    "/ecommercePublico/catalogo_manifest",
    "/ecommercePublico/disponibilidad",
    "/ecommercePublico/cotizacion_dryrun",
    "/ecommercePublico/cotizacion_preflight",
    "/ecommercePublico/cotizacion_registrar",
    "/ecommercePublico/facturacion_solicitar",
    "/ecommercePublico/evento_navegacion",
    "/ecommercePublico/busqueda_registrar"
  ) as $ruta) {
    if (!in_array($ruta, $rutas, true)) {
      $bloqueos[] = "contratos_falta_ruta_" . $ruta;
    }
  }
}

function validarFrontendHandoff($respuesta, &$bloqueos) {
  foreach (array(
    "estado_actual",
    "variables_env_frontend",
    "endpoints_para_consumir",
    "orden_recomendado_integracion",
    "pruebas_con_api",
    "contratos_ui",
    "ejemplos",
    "fase_2",
    "no_usar",
    "guardrails"
  ) as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "frontend_handoff_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "variables_env_frontend", "VITE_ERP_API_BASE_URL"), "") === "") {
    $bloqueos[] = "frontend_handoff_falta_base_url";
  }
  if (valorShape($respuesta, array("depurar", "variables_env_frontend", "VITE_ERP_ECOMMERCE_BASE_PATH"), "") !== "/ecommercePublico") {
    $bloqueos[] = "frontend_handoff_base_path_invalido";
  }
  if (valorShape($respuesta, array("depurar", "estado_actual", "senal_frontend"), "") === "") {
    $bloqueos[] = "frontend_handoff_falta_senal_frontend";
  }
  if (count(valorShape($respuesta, array("depurar", "endpoints_para_consumir"), array())) < 20) {
    $bloqueos[] = "frontend_handoff_debe_exponer_endpoints";
  }
  if (count(valorShape($respuesta, array("depurar", "pruebas_con_api"), array())) < 7) {
    $bloqueos[] = "frontend_handoff_debe_exponer_pruebas_http";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_requiere_filesystem"), false) !== true) {
    $bloqueos[] = "frontend_handoff_no_debe_requerir_filesystem";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "frontend_handoff_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "frontend_handoff_fase_2_debe_bloquear_granel";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "ejemplos", "catalogo_manifest"), null))) {
    $bloqueos[] = "frontend_handoff_debe_exponer_ejemplo_manifest";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "ejemplos", "seo"), null))) {
    $bloqueos[] = "frontend_handoff_debe_exponer_ejemplo_seo";
  }
}

function validarBootstrap($respuesta, &$bloqueos) {
  foreach (array("estado", "configuracion", "filtros", "navegacion", "secciones", "politicas", "canales", "fase_2", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "bootstrap_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_expone_secretos"), false) !== true) {
    $bloqueos[] = "bootstrap_debe_indicar_no_expone_secretos";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "bootstrap_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "bootstrap_fase_2_debe_bloquear_granel";
  }
}

function validarEstado($respuesta, &$bloqueos) {
  foreach (array(
    array("depurar", "ready"),
    array("depurar", "schema", "ddl_pendiente"),
    array("depurar", "publicaciones", "total_publicadas"),
    array("depurar", "publicaciones", "catalogo_publico_vacio"),
    array("depurar", "seguridad", "post_dryrun_disponible"),
    array("depurar", "guardrails", "no_checkout")
  ) as $ruta) {
    if (valorShape($respuesta, $ruta, "__missing__") === "__missing__") {
      $bloqueos[] = "estado_falta_" . implode(".", $ruta);
    }
  }
}

function validarConfiguracion($respuesta, &$bloqueos) {
  $config = valorShape($respuesta, array("depurar", "configuracion"), array());
  foreach (array("moneda_default", "whatsapp_numero_principal", "whatsapp_mensaje_base", "cotizacion_habilitada", "mostrar_stock_exacto", "modo_sin_stock", "texto_total_estimado", "url_sitio_publico") as $key) {
    if (!array_key_exists($key, $config)) {
      $bloqueos[] = "configuracion_falta_" . $key;
    }
  }
  if (isset($config["mostrar_stock_exacto"]) && (string) $config["mostrar_stock_exacto"] !== "0") {
    $bloqueos[] = "configuracion_mostrar_stock_exacto_debe_ser_0";
  }
}

function validarSeo($respuesta, &$bloqueos) {
  foreach (array("meta", "robots", "sitemap", "rutas", "json_ld", "resumen", "fase_2") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "seo_falta_array_" . $key;
    }
  }
  if (!is_string(valorShape($respuesta, array("depurar", "sitemap_xml_sugerido"), null))) {
    $bloqueos[] = "seo_sitemap_xml_sugerido_debe_ser_string";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_muestra_stock_exacto"), false) !== true) {
    $bloqueos[] = "seo_debe_indicar_no_stock_exacto";
  }
  foreach (array("archivos_sugeridos", "rutas", "canonical", "json_ld", "ui", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", "fase_2", $key), null))) {
      $bloqueos[] = "seo_fase_2_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "seo_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "seo_fase_2_debe_bloquear_granel";
  }
}

function validarFiltros($respuesta, &$bloqueos) {
  foreach (array("mascotas", "necesidades", "marcas", "categorias", "disponibilidad") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "filtros_falta_array_" . $key;
    }
  }
  foreach (array("facetados", "totales", "ui", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", "fase_2", $key), null))) {
      $bloqueos[] = "filtros_fase_2_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "filtros_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "filtros_fase_2_debe_bloquear_granel";
  }
}

function validarBusquedaSugerencias($respuesta, &$bloqueos) {
  foreach (array("grupos", "resumen", "fase_2", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "busqueda_sugerencias_falta_array_" . $key;
    }
  }
  foreach (array("productos", "marcas", "categorias", "mascotas", "necesidades") as $grupo) {
    if (!is_array(valorShape($respuesta, array("depurar", "grupos", $grupo), null))) {
      $bloqueos[] = "busqueda_sugerencias_falta_grupo_" . $grupo;
    }
  }
  foreach (array("links", "ui", "estado_vacio", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", "fase_2", $key), null))) {
      $bloqueos[] = "busqueda_sugerencias_fase_2_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "busqueda_sugerencias_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "busqueda_sugerencias_fase_2_debe_bloquear_granel";
  }
}

function validarNavegacion($respuesta, &$bloqueos) {
  foreach (array("primaria", "mascotas", "necesidades", "categorias", "marcas", "disponibilidad", "resumen", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "navegacion_falta_array_" . $key;
    }
  }
  foreach (array("resumen_grupos", "chips_home", "links", "ui", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", "fase_2", $key), null))) {
      $bloqueos[] = "navegacion_fase_2_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "navegacion_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "navegacion_fase_2_debe_bloquear_granel";
  }
}

function validarSecciones($respuesta, &$bloqueos) {
  $secciones = valorShape($respuesta, array("depurar", "secciones"), null);
  if (!is_array($secciones)) {
    $bloqueos[] = "secciones_lista_debe_ser_array";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "fase_2"), null))) {
    $bloqueos[] = "secciones_fase_2_debe_ser_array";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "secciones_fase_2_invalida";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "secciones_fase_2_debe_bloquear_granel";
  }
  if (is_array($secciones) && count($secciones) > 0) {
    if (!is_array(valorShape($respuesta, array("depurar", "secciones", 0, "frontend"), null))) {
      $bloqueos[] = "secciones_primer_item_debe_exponer_frontend";
    }
    if (valorShape($respuesta, array("depurar", "secciones", 0, "url_catalogo"), "") === "") {
      $bloqueos[] = "secciones_primer_item_debe_exponer_url_catalogo";
    }
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_stock_exacto"), false) !== true) {
    $bloqueos[] = "secciones_debe_indicar_no_stock_exacto";
  }
}

function validarPoliticas($respuesta, &$bloqueos) {
  if (!is_array(valorShape($respuesta, array("depurar", "items"), null))) {
    $bloqueos[] = "politicas_items_debe_ser_array";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_checkout"), false) !== true) {
    $bloqueos[] = "politicas_debe_indicar_no_checkout";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_factura_automatica"), false) !== true) {
    $bloqueos[] = "politicas_debe_indicar_no_factura_automatica";
  }
}

function validarPolitica($respuesta, &$bloqueos) {
  $item = valorShape($respuesta, array("depurar", "item"), null);
  if (!is_array($item)) {
    $bloqueos[] = "politica_facturacion_falta_item";
    return;
  }
  foreach (array("codigo", "tipo", "titulo", "resumen", "contenido", "version", "requiere_aceptacion") as $key) {
    if (!array_key_exists($key, $item)) {
      $bloqueos[] = "politica_facturacion_falta_" . $key;
    }
  }
}

function validarTaxonomiaMascotas($respuesta, &$bloqueos) {
  foreach (array("mascotas", "necesidades") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "taxonomia_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_requiere_cliente_registrado"), false) !== true) {
    $bloqueos[] = "taxonomia_debe_indicar_no_requiere_cliente_registrado";
  }
}

function validarCatalogo($respuesta, &$bloqueos) {
  if (!is_array(valorShape($respuesta, array("depurar", "items"), null))) {
    $bloqueos[] = "catalogo_items_debe_ser_array";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "paginacion"), null))) {
    $bloqueos[] = "catalogo_paginacion_debe_ser_array";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "frontend"), null))) {
    $bloqueos[] = "catalogo_frontend_debe_ser_array";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "guardrails_ui", "cotizacion_requiere_dryrun"), false) !== true) {
    $bloqueos[] = "catalogo_frontend_debe_requerir_dryrun";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "catalogo_debe_bloquear_granel";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "catalogo_debe_exponer_fase_2";
  }
  if (valorShape($respuesta, array("depurar", "fase_2", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "catalogo_fase_2_debe_bloquear_granel";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "fase_2", "links"), null))) {
    $bloqueos[] = "catalogo_fase_2_debe_exponer_links";
  }
}

function validarCatalogoManifest($respuesta, &$bloqueos) {
  foreach (array("fase", "estado_catalogo", "parametros_soportados", "ordenamientos", "endpoints_relacionados", "ejemplos", "preview", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null)) && $key !== "fase") {
      $bloqueos[] = "catalogo_manifest_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "fase"), "") !== "fase_2_api_catalogo_robusta") {
    $bloqueos[] = "catalogo_manifest_fase_invalida";
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_granel"), false) !== true) {
    $bloqueos[] = "catalogo_manifest_debe_bloquear_granel";
  }
  if (!array_key_exists("limite", valorShape($respuesta, array("depurar", "parametros_soportados"), array()))) {
    $bloqueos[] = "catalogo_manifest_falta_parametro_limite";
  }
  if (count(valorShape($respuesta, array("depurar", "ordenamientos"), array())) < 5) {
    $bloqueos[] = "catalogo_manifest_faltan_ordenamientos";
  }
}

function validarProducto($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "item"), "__missing__") === "__missing__") {
    $bloqueos[] = "producto_falta_item";
  }
}

function validarDisponibilidad($respuesta, &$bloqueos) {
  $estado = valorShape($respuesta, array("depurar", "disponibilidad"), "");
  if (!in_array($estado, array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado"), true)) {
    $bloqueos[] = "disponibilidad_estado_invalido";
  }
  if (valorShape($respuesta, array("depurar", "mostrar_cantidad_exacta"), false) === true) {
    $bloqueos[] = "disponibilidad_no_debe_mostrar_cantidad_exacta";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "frontend"), null))) {
    $bloqueos[] = "disponibilidad_frontend_debe_ser_array";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "mostrar_stock_exacto"), true) !== false) {
    $bloqueos[] = "disponibilidad_frontend_no_debe_mostrar_stock_exacto";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "requiere_dryrun_antes_de_whatsapp"), false) !== true) {
    $bloqueos[] = "disponibilidad_frontend_debe_requerir_dryrun";
  }
}

function validarCanalesEstado($respuesta, &$bloqueos) {
  foreach (array("tablas", "canales", "autenticacion", "activacion", "guardrails") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "canales_estado_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_expone_api_secret"), false) !== true) {
    $bloqueos[] = "canales_estado_debe_ocultar_api_secret";
  }
}

function validarDryRun($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "dry_run"), false) !== true) {
    $bloqueos[] = "dryrun_falta_flag_dry_run";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = "dryrun_debe_indicar_no_escribe_bd";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "lineas"), null))) {
    $bloqueos[] = "dryrun_lineas_debe_ser_array";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "frontend"), null))) {
    $bloqueos[] = "dryrun_frontend_debe_ser_array";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "puede_continuar_preflight"), null) !== true) {
    $bloqueos[] = "dryrun_frontend_debe_permitir_preflight_con_payload_valido";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "cta_principal", "endpoint_siguiente"), "") !== "/ecommercePublico/cotizacion_preflight") {
    $bloqueos[] = "dryrun_frontend_debe_indicar_endpoint_preflight";
  }
  if (valorShape($respuesta, array("depurar", "frontend", "guardrails_ui", "no_usar_precio_local_como_total"), false) !== true) {
    $bloqueos[] = "dryrun_frontend_debe_bloquear_precio_local_como_total";
  }
}

function validarPreflight($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "preflight"), false) !== true) {
    $bloqueos[] = "preflight_falta_flag_preflight";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = "preflight_debe_indicar_no_escribe_bd";
  }
  if (valorShape($respuesta, array("depurar", "no_descuenta_inventario"), false) !== true) {
    $bloqueos[] = "preflight_debe_indicar_no_descuenta_inventario";
  }
  if (valorShape($respuesta, array("depurar", "folio_no_persistido"), false) !== true) {
    $bloqueos[] = "preflight_debe_indicar_folio_no_persistido";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "whatsapp"), null))) {
    $bloqueos[] = "preflight_falta_whatsapp";
  }
}

function validarRegistroBloqueado($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "bloqueado"), false) !== true) {
    $bloqueos[] = "cotizacion_registrar_debe_seguir_bloqueado";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = "cotizacion_registrar_debe_indicar_no_escribe_bd";
  }
  if (valorShape($respuesta, array("depurar", "preflight_disponible"), false) !== true) {
    $bloqueos[] = "cotizacion_registrar_debe_indicar_preflight_disponible";
  }
}

function validarExperienciaPreflight($nombre, $respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "preflight"), false) !== true) {
    $bloqueos[] = $nombre . "_falta_preflight";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = $nombre . "_debe_indicar_no_escribe_bd";
  }
  if (valorShape($respuesta, array("depurar", "listo_para_registro_futuro"), false) !== true) {
    $bloqueos[] = $nombre . "_debe_quedar_listo_para_registro_futuro_con_payload_valido";
  }
}

function itemCatalogoKeys() {
  return array("id_publicacion", "id_producto_erp", "id_sku", "slug", "sku", "nombre", "marca", "categoria", "presentacion", "descripcion", "imagen", "precio", "moneda", "disponibilidad", "mascota_especie", "necesidades", "permite_cotizacion", "permite_whatsapp");
}

function valorShape($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
