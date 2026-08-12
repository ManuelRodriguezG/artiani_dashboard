<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: validar contratos read-only del CMS ligero ecommerce para frontend.
 * Impacto: protege manifest, contenido de pagina y configuracion inicial sin activar persistencia real.
 * Contrato: read-only; no crea banners, no sube imagenes, no modifica catalogo ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$manifest = $api->contenidoManifestPublico();
$home = $api->contenidoPaginaPublica(array("pagina" => "home"));
$categoria = $api->contenidoPaginaPublica(array("pagina" => "categoria", "categoria" => "peces"));
$configuracionInicial = $api->configuracionInicialPublica(array("limite_secciones" => 2));

$bloqueos = array();

if (!empty($manifest["error"])) {
  $bloqueos[] = "manifest_error";
}
if (!empty($home["error"])) {
  $bloqueos[] = "home_error";
}
if (!empty($categoria["error"])) {
  $bloqueos[] = "categoria_error";
}
if (empty(valorContenidoReadonly($manifest, array("depurar", "guardrails", "read_only"), false))) {
  $bloqueos[] = "manifest_no_readonly";
}
if (empty(valorContenidoReadonly($manifest, array("depurar", "guardrails", "frontend_renderiza_plantilla_vista"), false))) {
  $bloqueos[] = "manifest_no_declara_renderer_plantilla_vista";
}
if (trim((string) valorContenidoReadonly($manifest, array("depurar", "tema_visual_activo", "codigo"), "")) !== "wokiee_artiani") {
  $bloqueos[] = "manifest_sin_tema_visual_activo";
}
if (!plantillaVistaExisteContenidoReadonly(valorContenidoReadonly($manifest, array("depurar", "plantillas_vista"), array()), "wokiee_home_default")) {
  $bloqueos[] = "manifest_sin_wokiee_home_default";
}
if (count(valorContenidoReadonly($manifest, array("depurar", "componentes_frontend"), array())) < 6) {
  $bloqueos[] = "manifest_componentes_frontend_insuficientes";
}
if (empty(valorContenidoReadonly($home, array("depurar", "guardrails", "no_escribe_bd"), false))) {
  $bloqueos[] = "home_no_declara_no_escribe_bd";
}
if (valorContenidoReadonly($home, array("depurar", "fuente"), "") !== "default_readonly") {
  $bloqueos[] = "home_fuente_no_default_readonly";
}
if (!slotExisteContenidoReadonly(valorContenidoReadonly($home, array("depurar", "slots"), array()), "home.hero")) {
  $bloqueos[] = "home_sin_hero";
}
if (!slotExisteContenidoReadonly(valorContenidoReadonly($home, array("depurar", "slots"), array()), "home.categorias")) {
  $bloqueos[] = "home_sin_categorias";
}
if (!slotExisteContenidoReadonly(valorContenidoReadonly($home, array("depurar", "slots"), array()), "home.destacados")) {
  $bloqueos[] = "home_sin_destacados";
}
if (trim((string) valorContenidoReadonly($home, array("depurar", "plantilla_vista", "codigo"), "")) !== "wokiee_home_default") {
  $bloqueos[] = "home_sin_plantilla_vista";
}
if (count(valorContenidoReadonly($home, array("depurar", "plantilla_vista", "secciones"), array())) < 4) {
  $bloqueos[] = "home_plantilla_vista_sin_secciones";
}
if (!slotExisteContenidoReadonly(valorContenidoReadonly($categoria, array("depurar", "slots"), array()), "categoria.banner")) {
  $bloqueos[] = "categoria_sin_banner";
}
if (trim((string) valorContenidoReadonly($categoria, array("depurar", "plantilla_vista", "codigo"), "")) !== "wokiee_categoria_default") {
  $bloqueos[] = "categoria_sin_plantilla_vista";
}
if (trim((string) valorContenidoReadonly($configuracionInicial, array("depurar", "contenido", "manifest"), "")) !== "/ecommercePublico/contenido_manifest") {
  $bloqueos[] = "configuracion_inicial_sin_link_manifest";
}
if (trim((string) valorContenidoReadonly($configuracionInicial, array("depurar", "contenido_inicial", "home", "plantilla_vista", "codigo"), "")) !== "wokiee_home_default") {
  $bloqueos[] = "configuracion_inicial_sin_plantilla_vista_home";
}
if (!slotExisteContenidoReadonly(valorContenidoReadonly($configuracionInicial, array("depurar", "contenido_inicial", "home", "slots"), array()), "home.hero")) {
  $bloqueos[] = "configuracion_inicial_sin_home_hero";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "cms_contenido_ligero_readonly" : "cms_contenido_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "manifest" => array(
    "plantilla_activa" => valorContenidoReadonly($manifest, array("depurar", "plantilla_activa"), ""),
    "tema_visual_activo" => valorContenidoReadonly($manifest, array("depurar", "tema_visual_activo", "codigo"), ""),
    "tipos_bloque_total" => count(valorContenidoReadonly($manifest, array("depurar", "tipos_bloque"), array())),
    "plantillas_total" => count(valorContenidoReadonly($manifest, array("depurar", "plantillas"), array())),
    "plantillas_vista_total" => count(valorContenidoReadonly($manifest, array("depurar", "plantillas_vista"), array())),
    "componentes_frontend_total" => count(valorContenidoReadonly($manifest, array("depurar", "componentes_frontend"), array()))
  ),
  "home" => array(
    "pagina" => valorContenidoReadonly($home, array("depurar", "pagina"), ""),
    "plantilla_vista" => valorContenidoReadonly($home, array("depurar", "plantilla_vista", "codigo"), ""),
    "slots_total" => valorContenidoReadonly($home, array("depurar", "resumen", "slots_total"), 0),
    "bloques_total" => valorContenidoReadonly($home, array("depurar", "resumen", "bloques_total"), 0),
    "fuente" => valorContenidoReadonly($home, array("depurar", "fuente"), "")
  ),
  "configuracion_inicial" => array(
    "contenido_home" => valorContenidoReadonly($configuracionInicial, array("depurar", "contenido_inicial", "home", "pagina"), ""),
    "plantilla_vista_home" => valorContenidoReadonly($configuracionInicial, array("depurar", "contenido_inicial", "home", "plantilla_vista", "codigo"), "")
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true,
    "panel_pendiente" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorContenidoReadonly($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function slotExisteContenidoReadonly($slots, $codigo) {
  foreach ((array) $slots as $slot) {
    if (isset($slot["slot"]) && $slot["slot"] === $codigo) {
      return true;
    }
  }
  return false;
}

function plantillaVistaExisteContenidoReadonly($plantillas, $codigo) {
  foreach ((array) $plantillas as $plantilla) {
    if (isset($plantilla["codigo"]) && $plantilla["codigo"] === $codigo) {
      return true;
    }
  }
  return false;
}
