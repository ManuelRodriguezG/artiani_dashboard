<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-01.
 * Proposito: probar por HTTP los endpoints publicos ecommerce usando el host real configurado.
 * Impacto: valida que el frontend externo use base URL correcta y no rutas de filesystem.
 * Contrato: read-only; no escribe BD, no registra cotizaciones y no mueve inventario.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$pruebas = array(
  "estado" => requestHttp($base . "/ecommercePublico/estado"),
  "contratos" => requestHttp($base . "/ecommercePublico/contratos"),
  "frontend_handoff" => requestHttp($base . "/ecommercePublico/frontend_handoff?limite=2"),
  "bootstrap" => requestHttp($base . "/ecommercePublico/bootstrap?limite_secciones=3"),
  "configuracion" => requestHttp($base . "/ecommercePublico/configuracion"),
  "seo" => requestHttp($base . "/ecommercePublico/seo?limite=20"),
  "filtros" => requestHttp($base . "/ecommercePublico/filtros"),
  "busqueda_sugerencias" => requestHttp($base . "/ecommercePublico/busqueda_sugerencias?q=filtro&limite=4"),
  "navegacion" => requestHttp($base . "/ecommercePublico/navegacion?limite=5"),
  "secciones" => requestHttp($base . "/ecommercePublico/secciones?limite=3"),
  "catalogo" => requestHttp($base . "/ecommercePublico/catalogo?limite=3"),
  "catalogo_manifest" => requestHttp($base . "/ecommercePublico/catalogo_manifest?limite_preview=2"),
  "fase_2_checklist" => requestHttp($base . "/ecommercePublico/fase_2_checklist"),
  "catalogo_disponible_ordenado" => requestHttp($base . "/ecommercePublico/catalogo?disponibilidad=disponible&orden=precio_asc&limite=3"),
  "catalogo_sin_resultados" => requestHttp($base . "/ecommercePublico/catalogo?q=__sin_resultados_catalogo_frontend__&limite=3"),
  "canales_estado" => requestHttp($base . "/ecommercePublico/canales_estado"),
  "producto" => requestHttp($base . "/ecommercePublico/producto/slug-de-prueba-no-publicado"),
  "disponibilidad" => requestHttp($base . "/ecommercePublico/disponibilidad?slug=slug-de-prueba-no-publicado"),
  "cotizacion_dryrun" => requestHttp($base . "/ecommercePublico/cotizacion_dryrun", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
  )),
  "cotizacion_preflight" => requestHttp($base . "/ecommercePublico/cotizacion_preflight", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1)),
    "contacto" => array("nombre" => "Smoke read-only", "telefono" => "3322068429"),
    "acepta_contacto_whatsapp" => true,
    "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp")
  )),
  "cotizacion_registrar" => requestHttp($base . "/ecommercePublico/cotizacion_registrar", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1)),
    "contacto" => array("nombre" => "Smoke read-only", "telefono" => "5555555555")
  )),
  "facturacion_solicitar" => requestHttp($base . "/ecommercePublico/facturacion_solicitar", "POST", array(
    "folio_compra" => "TICKET-123",
    "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente Smoke", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
    "contacto" => array("correo" => "cliente@example.com"),
    "acepta_aviso_privacidad" => true
  )),
  "evento_navegacion" => requestHttp($base . "/ecommercePublico/evento_navegacion", "POST", array(
    "session_id" => "sess_smoke_123",
    "tipo_evento" => "page_view",
    "ruta" => "/"
  )),
  "busqueda_registrar" => requestHttp($base . "/ecommercePublico/busqueda_registrar", "POST", array(
    "session_id" => "sess_smoke_123",
    "query" => "alimento perro",
    "mascota" => "perro",
    "resultados_total" => 1
  ))
);

$primerSlugCatalogo = (string) valorHttpSmoke($pruebas, array("catalogo", "depurar_resumen", "primer_slug"), "");
if ($primerSlugCatalogo !== "") {
  $pruebas["producto_real"] = requestHttp($base . "/ecommercePublico/producto/" . rawurlencode($primerSlugCatalogo));
  $pruebas["disponibilidad_real"] = requestHttp($base . "/ecommercePublico/disponibilidad?slug=" . rawurlencode($primerSlugCatalogo));
}

$bloqueos = array();
foreach ($pruebas as $nombre => $prueba) {
  if (!$prueba["json_valido"]) {
    $bloqueos[] = $nombre . "_no_responde_json";
  }
}
foreach (array("frontend_handoff", "bootstrap", "seo", "filtros", "busqueda_sugerencias", "navegacion", "secciones", "catalogo_manifest", "fase_2_checklist", "catalogo_disponible_ordenado", "canales_estado") as $endpointNuevo) {
  if (!$pruebas[$endpointNuevo]["json_valido"] || !in_array($pruebas[$endpointNuevo]["tipo"], array("success", "info"), true)) {
    $bloqueos[] = $endpointNuevo . "_no_responde_success_o_info";
  }
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_senal_frontend"] === "") {
  $bloqueos[] = "frontend_handoff_debe_exponer_senal_frontend";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_endpoints_total"] < 20) {
  $bloqueos[] = "frontend_handoff_debe_exponer_endpoints";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_endpoints_total"] < 24) {
  $bloqueos[] = "frontend_handoff_debe_incluir_checklist_fase_2";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_pruebas_total"] < 7) {
  $bloqueos[] = "frontend_handoff_debe_exponer_pruebas_api";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_no_filesystem"] !== true) {
  $bloqueos[] = "frontend_handoff_no_debe_requerir_filesystem";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_fase_2_no_granel"] !== true) {
  $bloqueos[] = "frontend_handoff_fase_2_debe_bloquear_granel";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_ejemplo_manifest"] !== true) {
  $bloqueos[] = "frontend_handoff_debe_exponer_ejemplo_manifest";
}
if ($pruebas["frontend_handoff"]["depurar_resumen"]["handoff_ejemplo_seo"] !== true) {
  $bloqueos[] = "frontend_handoff_debe_exponer_ejemplo_seo";
}
if (empty($pruebas["bootstrap"]["depurar_resumen"]["bootstrap_guardrails"])) {
  $bloqueos[] = "bootstrap_debe_exponer_guardrails";
}
if ($pruebas["bootstrap"]["depurar_resumen"]["bootstrap_fase_2_no_granel"] !== true) {
  $bloqueos[] = "bootstrap_fase_2_debe_bloquear_granel";
}
if ($pruebas["bootstrap"]["depurar_resumen"]["bootstrap_fase_2_primer_render"] !== true) {
  $bloqueos[] = "bootstrap_fase_2_debe_exponer_primer_render";
}
if ($pruebas["seo"]["depurar_resumen"]["rutas_total"] === null) {
  $bloqueos[] = "seo_debe_exponer_rutas";
}
if ($pruebas["seo"]["depurar_resumen"]["seo_fase_2_no_granel"] !== true) {
  $bloqueos[] = "seo_fase_2_debe_bloquear_granel";
}
if ($pruebas["seo"]["depurar_resumen"]["seo_fase_2_canonical"] !== true) {
  $bloqueos[] = "seo_fase_2_debe_exponer_canonical";
}
if ($pruebas["navegacion"]["depurar_resumen"]["navegacion_total"] === null) {
  $bloqueos[] = "navegacion_debe_exponer_resumen";
}
if ($pruebas["filtros"]["depurar_resumen"]["filtros_fase_2_no_granel"] !== true) {
  $bloqueos[] = "filtros_fase_2_debe_bloquear_granel";
}
if ($pruebas["filtros"]["depurar_resumen"]["filtros_fase_2_facetados"] !== true) {
  $bloqueos[] = "filtros_fase_2_debe_exponer_facetados";
}
if ($pruebas["navegacion"]["depurar_resumen"]["navegacion_fase_2_no_granel"] !== true) {
  $bloqueos[] = "navegacion_fase_2_debe_bloquear_granel";
}
if ($pruebas["navegacion"]["depurar_resumen"]["navegacion_fase_2_chips"] !== true) {
  $bloqueos[] = "navegacion_fase_2_debe_exponer_chips";
}
if ($pruebas["busqueda_sugerencias"]["depurar_resumen"]["sugerencias_total"] === null) {
  $bloqueos[] = "busqueda_sugerencias_debe_exponer_resumen";
}
if ($pruebas["busqueda_sugerencias"]["depurar_resumen"]["busqueda_fase_2_no_granel"] !== true) {
  $bloqueos[] = "busqueda_sugerencias_fase_2_debe_bloquear_granel";
}
if ($pruebas["busqueda_sugerencias"]["depurar_resumen"]["busqueda_fase_2_links"] !== true) {
  $bloqueos[] = "busqueda_sugerencias_fase_2_debe_exponer_links";
}
if ($pruebas["secciones"]["depurar_resumen"]["secciones_fase_2_no_granel"] !== true) {
  $bloqueos[] = "secciones_fase_2_debe_bloquear_granel";
}
if ($pruebas["secciones"]["depurar_resumen"]["secciones_primer_frontend"] !== true) {
  $bloqueos[] = "secciones_debe_exponer_frontend_en_primer_bloque";
}
if ($pruebas["catalogo"]["depurar_resumen"]["catalogo_frontend_hay_resultados"] !== true) {
  $bloqueos[] = "catalogo_debe_exponer_frontend_hay_resultados";
}
if ($pruebas["catalogo"]["depurar_resumen"]["catalogo_frontend_rango_texto"] === "") {
  $bloqueos[] = "catalogo_debe_exponer_rango_visible";
}
if ($pruebas["catalogo"]["depurar_resumen"]["catalogo_frontend_requiere_dryrun"] !== true) {
  $bloqueos[] = "catalogo_debe_indicar_cotizacion_requiere_dryrun";
}
if ($pruebas["catalogo"]["depurar_resumen"]["catalogo_fase_2_no_granel"] !== true) {
  $bloqueos[] = "catalogo_fase_2_debe_bloquear_granel";
}
if ($pruebas["catalogo"]["depurar_resumen"]["catalogo_fase_2_links"] !== true) {
  $bloqueos[] = "catalogo_fase_2_debe_exponer_links";
}
if ($pruebas["catalogo_sin_resultados"]["depurar_resumen"]["catalogo_frontend_estado_vacio"] !== true) {
  $bloqueos[] = "catalogo_sin_resultados_debe_exponer_estado_vacio";
}
if ($pruebas["catalogo_manifest"]["depurar_resumen"]["catalogo_manifest_fase"] !== "fase_2_api_catalogo_robusta") {
  $bloqueos[] = "catalogo_manifest_debe_exponer_fase_2";
}
if ($pruebas["catalogo_manifest"]["depurar_resumen"]["catalogo_manifest_no_granel"] !== true) {
  $bloqueos[] = "catalogo_manifest_debe_bloquear_granel";
}
if ($pruebas["catalogo_manifest"]["depurar_resumen"]["catalogo_manifest_ordenamientos_total"] < 5) {
  $bloqueos[] = "catalogo_manifest_debe_exponer_ordenamientos";
}
if ($pruebas["fase_2_checklist"]["depurar_resumen"]["fase_2_checklist_no_granel"] !== true) {
  $bloqueos[] = "fase_2_checklist_debe_bloquear_granel";
}
if ($pruebas["fase_2_checklist"]["depurar_resumen"]["fase_2_checklist_endpoints_total"] < 12) {
  $bloqueos[] = "fase_2_checklist_debe_exponer_endpoints";
}
if ($pruebas["fase_2_checklist"]["depurar_resumen"]["fase_2_checklist_escenarios_total"] < 8) {
  $bloqueos[] = "fase_2_checklist_debe_exponer_escenarios";
}
if ($primerSlugCatalogo === "") {
  $bloqueos[] = "catalogo_debe_exponer_primer_slug_para_smoke";
} elseif (empty($pruebas["producto_real"]["depurar_resumen"]["item_presente"])) {
  $bloqueos[] = "producto_real_debe_responder_item";
} else {
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_breadcrumbs_total"] < 2) {
    $bloqueos[] = "producto_real_debe_exponer_breadcrumbs";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_relacionados_total"] === null) {
    $bloqueos[] = "producto_real_debe_exponer_relacionados";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_acciones_puede_cotizar"] !== true) {
    $bloqueos[] = "producto_real_debe_exponer_acciones";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_fase_2_no_granel"] !== true) {
    $bloqueos[] = "producto_real_fase_2_debe_bloquear_granel";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_fase_2_links"] !== true) {
    $bloqueos[] = "producto_real_fase_2_debe_exponer_links";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_fase_2_cta"] !== true) {
    $bloqueos[] = "producto_real_fase_2_debe_exponer_cta";
  }
  if ($pruebas["producto_real"]["depurar_resumen"]["producto_seo_title"] === "") {
    $bloqueos[] = "producto_real_debe_exponer_seo";
  }
  if (empty($pruebas["disponibilidad_real"]["depurar_resumen"]["disponibilidad_frontend_cta_label"])) {
    $bloqueos[] = "disponibilidad_real_debe_exponer_cta_frontend";
  }
  if ($pruebas["disponibilidad_real"]["depurar_resumen"]["disponibilidad_frontend_stock_exacto"] !== false) {
    $bloqueos[] = "disponibilidad_real_no_debe_mostrar_stock_exacto";
  }
  if ($pruebas["disponibilidad_real"]["depurar_resumen"]["disponibilidad_frontend_requiere_dryrun"] !== true) {
    $bloqueos[] = "disponibilidad_real_debe_requerir_dryrun";
  }
  if ($pruebas["disponibilidad_real"]["depurar_resumen"]["disponibilidad_fase_2_no_granel"] !== true) {
    $bloqueos[] = "disponibilidad_real_fase_2_debe_bloquear_granel";
  }
  if ($pruebas["disponibilidad_real"]["depurar_resumen"]["disponibilidad_fase_2_puede_carrito"] !== true) {
    $bloqueos[] = "disponibilidad_real_fase_2_debe_permitir_carrito";
  }
}
if (empty($pruebas["cotizacion_registrar"]["depurar_resumen"]["bloqueado"])) {
  $bloqueos[] = "cotizacion_registrar_debe_seguir_bloqueado";
}
if ($pruebas["cotizacion_dryrun"]["depurar_resumen"]["dryrun_frontend_puede_preflight"] !== true) {
  $bloqueos[] = "dryrun_frontend_debe_permitir_preflight";
}
if ($pruebas["cotizacion_dryrun"]["depurar_resumen"]["dryrun_frontend_cta_endpoint"] !== "/ecommercePublico/cotizacion_preflight") {
  $bloqueos[] = "dryrun_frontend_debe_indicar_endpoint_preflight";
}
if ($pruebas["cotizacion_dryrun"]["depurar_resumen"]["dryrun_frontend_no_precio_local"] !== true) {
  $bloqueos[] = "dryrun_frontend_debe_bloquear_precio_local";
}
if ($pruebas["cotizacion_dryrun"]["depurar_resumen"]["dryrun_fase_2_no_granel"] !== true) {
  $bloqueos[] = "dryrun_fase_2_debe_bloquear_granel";
}
if ($pruebas["cotizacion_dryrun"]["depurar_resumen"]["dryrun_fase_2_endpoint"] !== "/ecommercePublico/cotizacion_preflight") {
  $bloqueos[] = "dryrun_fase_2_debe_indicar_preflight";
}
if ($pruebas["cotizacion_preflight"]["depurar_resumen"]["preflight_fase_2_no_granel"] !== true) {
  $bloqueos[] = "preflight_fase_2_debe_bloquear_granel";
}
if ($pruebas["cotizacion_preflight"]["depurar_resumen"]["preflight_fase_2_embudo"] !== true) {
  $bloqueos[] = "preflight_fase_2_debe_exponer_embudo";
}
foreach (array("facturacion_solicitar", "evento_navegacion", "busqueda_registrar") as $endpointPreflight) {
  if (empty($pruebas[$endpointPreflight]["depurar_resumen"]["preflight"])) {
    $bloqueos[] = $endpointPreflight . "_debe_responder_preflight";
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_url" => $base,
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "frontend_base_correcta" => $base . "/ecommercePublico",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function requestHttp($url, $method = "GET", $body = null) {
  $headers = "Accept: application/json\r\n";
  $content = null;
  if ($body !== null) {
    $content = json_encode($body);
    $headers .= "Content-Type: application/json\r\n";
  }
  $context = stream_context_create(array(
    "http" => array(
      "method" => $method,
      "header" => $headers,
      "content" => $content,
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "method" => $method,
    "json_valido" => is_array($json),
    "error" => is_array($json) ? (bool) valorHttpSmoke($json, array("error"), false) : true,
    "tipo" => is_array($json) ? valorHttpSmoke($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorHttpSmoke($json, array("mensaje"), "") : "",
    "api_version" => is_array($json) ? valorHttpSmoke($json, array("api", "version"), "") : "",
    "depurar_resumen" => resumenDepurarHttpSmoke(is_array($json) ? valorHttpSmoke($json, array("depurar"), array()) : array()),
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}

function resumenDepurarHttpSmoke($depurar) {
  if (!is_array($depurar)) {
    return array();
  }
  return array(
    "ready" => valorHttpSmoke($depurar, array("ready"), null),
    "configurado" => valorHttpSmoke($depurar, array("configurado"), null),
    "handoff_senal_frontend" => valorHttpSmoke($depurar, array("estado_actual", "senal_frontend"), ""),
    "handoff_endpoints_total" => is_array(valorHttpSmoke($depurar, array("endpoints_para_consumir"), null)) ? count($depurar["endpoints_para_consumir"]) : null,
    "handoff_pruebas_total" => is_array(valorHttpSmoke($depurar, array("pruebas_con_api"), null)) ? count($depurar["pruebas_con_api"]) : null,
    "handoff_no_usar_total" => is_array(valorHttpSmoke($depurar, array("no_usar"), null)) ? count($depurar["no_usar"]) : null,
    "handoff_no_filesystem" => valorHttpSmoke($depurar, array("guardrails", "no_requiere_filesystem"), null),
    "handoff_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "handoff_ejemplo_manifest" => is_array(valorHttpSmoke($depurar, array("ejemplos", "catalogo_manifest"), null)),
    "handoff_ejemplo_seo" => is_array(valorHttpSmoke($depurar, array("ejemplos", "seo"), null)),
    "dry_run" => valorHttpSmoke($depurar, array("dry_run"), null),
    "dryrun_frontend_estado" => valorHttpSmoke($depurar, array("frontend", "estado"), ""),
    "dryrun_frontend_puede_preflight" => valorHttpSmoke($depurar, array("frontend", "puede_continuar_preflight"), null),
    "dryrun_frontend_cta_label" => valorHttpSmoke($depurar, array("frontend", "cta_principal", "label"), ""),
    "dryrun_frontend_cta_endpoint" => valorHttpSmoke($depurar, array("frontend", "cta_principal", "endpoint_siguiente"), ""),
    "dryrun_frontend_no_precio_local" => valorHttpSmoke($depurar, array("frontend", "guardrails_ui", "no_usar_precio_local_como_total"), null),
    "dryrun_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "dryrun_fase_2_endpoint" => valorHttpSmoke($depurar, array("fase_2", "flujo", "endpoint_siguiente"), ""),
    "preflight" => valorHttpSmoke($depurar, array("preflight"), null),
    "listo_para_whatsapp" => valorHttpSmoke($depurar, array("listo_para_whatsapp"), null),
    "preflight_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "preflight_fase_2_embudo" => is_array(valorHttpSmoke($depurar, array("fase_2", "embudo"), null)),
    "bloqueado" => valorHttpSmoke($depurar, array("bloqueado"), null),
    "disponibilidad" => valorHttpSmoke($depurar, array("disponibilidad"), null),
    "disponibilidad_frontend_estado" => valorHttpSmoke($depurar, array("frontend", "estado"), null),
    "disponibilidad_frontend_badge" => valorHttpSmoke($depurar, array("frontend", "badge", "label"), ""),
    "disponibilidad_frontend_cta_label" => valorHttpSmoke($depurar, array("frontend", "cta", "label"), ""),
    "disponibilidad_frontend_stock_exacto" => valorHttpSmoke($depurar, array("frontend", "mostrar_stock_exacto"), null),
    "disponibilidad_frontend_requiere_dryrun" => valorHttpSmoke($depurar, array("frontend", "requiere_dryrun_antes_de_whatsapp"), null),
    "disponibilidad_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "disponibilidad_fase_2_puede_carrito" => valorHttpSmoke($depurar, array("fase_2", "puede_agregar_cotizacion"), null),
    "bootstrap_guardrails" => is_array(valorHttpSmoke($depurar, array("guardrails"), null)),
    "bootstrap_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "bootstrap_fase_2_primer_render" => is_array(valorHttpSmoke($depurar, array("fase_2", "primer_render"), null)),
    "rutas_total" => is_array(valorHttpSmoke($depurar, array("rutas"), null)) ? count($depurar["rutas"]) : null,
    "seo_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "seo_fase_2_canonical" => is_array(valorHttpSmoke($depurar, array("fase_2", "canonical"), null)),
    "navegacion_total" => valorHttpSmoke($depurar, array("resumen", "total_items"), null),
    "navegacion_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "navegacion_fase_2_chips" => is_array(valorHttpSmoke($depurar, array("fase_2", "chips_home"), null)),
    "sugerencias_total" => valorHttpSmoke($depurar, array("resumen", "total_sugerencias"), null),
    "busqueda_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "busqueda_fase_2_links" => is_array(valorHttpSmoke($depurar, array("fase_2", "links"), null)),
    "secciones_total" => is_array(valorHttpSmoke($depurar, array("secciones"), null)) ? count($depurar["secciones"]) : null,
    "secciones_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "secciones_primer_frontend" => is_array(valorHttpSmoke($depurar, array("secciones", 0, "frontend"), null)),
    "filtros_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "filtros_fase_2_facetados" => is_array(valorHttpSmoke($depurar, array("fase_2", "facetados"), null)),
    "catalogo_frontend_hay_resultados" => valorHttpSmoke($depurar, array("frontend", "hay_resultados"), null),
    "catalogo_frontend_estado_vacio" => valorHttpSmoke($depurar, array("frontend", "estado_vacio", "mostrar"), null),
    "catalogo_frontend_rango_texto" => valorHttpSmoke($depurar, array("frontend", "rango_visible", "texto"), ""),
    "catalogo_frontend_requiere_dryrun" => valorHttpSmoke($depurar, array("frontend", "guardrails_ui", "cotizacion_requiere_dryrun"), null),
    "catalogo_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "catalogo_fase_2_links" => is_array(valorHttpSmoke($depurar, array("fase_2", "links"), null)),
    "catalogo_manifest_fase" => valorHttpSmoke($depurar, array("fase"), ""),
    "catalogo_manifest_no_granel" => valorHttpSmoke($depurar, array("guardrails", "no_granel"), null),
    "catalogo_manifest_ordenamientos_total" => is_array(valorHttpSmoke($depurar, array("ordenamientos"), null)) ? count($depurar["ordenamientos"]) : null,
    "fase_2_checklist_no_granel" => valorHttpSmoke($depurar, array("criterios_pase_fase_3", "no_granel"), null),
    "fase_2_checklist_endpoints_total" => is_array(valorHttpSmoke($depurar, array("endpoints_obligatorios"), null)) ? count($depurar["endpoints_obligatorios"]) : null,
    "fase_2_checklist_escenarios_total" => is_array(valorHttpSmoke($depurar, array("escenarios_prueba"), null)) ? count($depurar["escenarios_prueba"]) : null,
    "fase_2_checklist_pasa_fase_3" => valorHttpSmoke($depurar, array("puede_pasar_a_fase_3"), null),
    "item_presente" => array_key_exists("item", $depurar) ? ($depurar["item"] !== null) : null,
    "items_total" => is_array(valorHttpSmoke($depurar, array("items"), null)) ? count($depurar["items"]) : null,
    "primer_slug" => valorHttpSmoke($depurar, array("items", 0, "slug"), ""),
    "producto_variantes_total" => is_array(valorHttpSmoke($depurar, array("variantes"), null)) ? count($depurar["variantes"]) : null,
    "producto_relacionados_total" => is_array(valorHttpSmoke($depurar, array("relacionados"), null)) ? count($depurar["relacionados"]) : null,
    "producto_breadcrumbs_total" => is_array(valorHttpSmoke($depurar, array("breadcrumbs"), null)) ? count($depurar["breadcrumbs"]) : null,
    "producto_seo_title" => valorHttpSmoke($depurar, array("seo", "title"), ""),
    "producto_acciones_puede_cotizar" => valorHttpSmoke($depurar, array("acciones", "puede_cotizar"), null),
    "producto_fase_2_no_granel" => valorHttpSmoke($depurar, array("fase_2", "guardrails", "no_granel"), null),
    "producto_fase_2_links" => is_array(valorHttpSmoke($depurar, array("fase_2", "links"), null)),
    "producto_fase_2_cta" => is_array(valorHttpSmoke($depurar, array("fase_2", "cta"), null)),
    "bloqueos" => valorHttpSmoke($depurar, array("bloqueos"), array())
  );
}

function valorHttpSmoke($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
