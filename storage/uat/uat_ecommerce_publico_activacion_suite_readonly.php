<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: concentrar la verificacion read-only previa a activar ecommerce publico Fase 1.
 * Impacto: reduce pasos manuales antes de DDL/configuracion/publicaciones y evita declarar verde antes de tiempo.
 * Contrato: read-only; no ejecuta DDL, no guarda configuracion, no publica SKUs y no registra cotizaciones.
 */

$opciones = getopt("", array(
  "base::",
  "origin::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();

$contratos = $api->contratosApiPublicos();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$filtros = $api->filtrosPublicos();
$seo = $api->seoPublico(array("limite" => 5));
$registro = $api->cotizacionRegistrarBloqueada(array(
  "contacto" => array("telefono" => "5215555555555"),
  "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
));

$items = valorSuite($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorSuite($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
  ));
} else {
  $dryrun = $api->cotizacionDryRun(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1))));
}

$endpoints = valorSuite($contratos, array("depurar", "endpoints_publicos"), array());
$ddlPendiente = valorSuite($estado, array("depurar", "schema", "ddl_pendiente"), true);
$publicadas = intval(valorSuite($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$publicables = intval(valorSuite($estado, array("depurar", "publicaciones", "skus_publicables_fase_1"), 0));
$config = valorSuite($configuracion, array("depurar", "configuracion"), array());
$whatsapp = trim((string) valorSuite($config, array("whatsapp_numero_principal"), ""));
$cors = trim((string) valorSuite($config, array("cors_origenes_permitidos"), ""));
$corsPermiteOrigin = $cors !== "" && in_array($origin, array_map("trim", explode(",", $cors)), true);
$catalogoTieneItem = !empty($primerItem);
$dryrunConItem = !empty(valorSuite($dryrun, array("depurar", "lineas"), array()));

$bloqueos = array();
if (count($endpoints) < 9) {
  $bloqueos[] = "contratos_incompletos";
}
if ($ddlPendiente) {
  $bloqueos[] = "ddl_ecommerce_publico_pendiente";
}
if ($publicadas <= 0) {
  $bloqueos[] = "sin_publicaciones_activas";
}
if ($whatsapp === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if ($cors === "") {
  $bloqueos[] = "cors_origenes_permitidos_no_configurado";
} elseif (!$corsPermiteOrigin) {
  $bloqueos[] = "cors_no_permite_origin_" . $origin;
}
if (!$catalogoTieneItem) {
  $bloqueos[] = "catalogo_sin_item_real";
}
if (!$dryrunConItem) {
  $bloqueos[] = "cotizacion_dryrun_sin_item_real";
}
if (empty(valorSuite($registro, array("depurar", "bloqueado"), false))) {
  $bloqueos[] = "cotizacion_registrar_no_deberia_estar_desbloqueado";
}

$puedeMock = count($endpoints) >= 9
  && valorSuite($dryrun, array("depurar", "dry_run"), false) === true
  && valorSuite($registro, array("depurar", "bloqueado"), true) === true;
$puedeReal = empty($bloqueos);
$senal = $puedeReal ? "verde_datos_reales" : ($puedeMock ? "amarillo_mock_contratos" : "rojo_bloqueado");

echo json_encode(array(
  "ok" => $puedeMock,
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "origin_validado" => $origin,
  "senal_frontend" => $senal,
  "puede_iniciar_frontend_mock" => $puedeMock,
  "puede_integrar_datos_reales" => $puedeReal,
  "resumen" => array(
    "endpoints_publicos" => count($endpoints),
    "ddl_pendiente" => $ddlPendiente,
    "publicadas" => $publicadas,
    "publicables_fase_1" => $publicables,
    "whatsapp_configurado" => $whatsapp !== "",
    "cors_configurado" => $cors !== "",
    "cors_permite_origin" => $corsPermiteOrigin,
    "catalogo_tiene_item_real" => $catalogoTieneItem,
    "dryrun_con_item_real" => $dryrunConItem,
    "registro_cotizacion_bloqueado" => valorSuite($registro, array("depurar", "bloqueado"), false)
  ),
  "contratos" => array(
    "api_version" => valorSuite($contratos, array("api", "version"), ""),
    "seo_configurado" => valorSuite($seo, array("depurar", "configurado"), false),
    "filtros_configurados" => valorSuite($filtros, array("depurar", "configurado"), false),
    "catalogo_configurado" => valorSuite($catalogo, array("depurar", "configurado"), false),
    "dryrun_no_escribe_bd" => valorSuite($dryrun, array("depurar", "no_escribe_bd"), false)
  ),
  "bloqueos_para_verde" => array_values(array_unique($bloqueos)),
  "siguiente_paso" => $puedeReal
    ? "Avisar al frontend externo que puede integrar datos reales."
    : "Completar activacion autorizada: DDL, configuracion WhatsApp/CORS y primera publicacion real.",
  "comandos_recomendados" => array(
    "paquete_frontend" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_package_readonly.php --base=" . $base,
    "orden_autorizacion" => "docs/erp_ecommerce_publico_orden_activacion_autorizada.md",
    "checklist_apply" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_apply_checklist_readonly.php --base=" . $base . " --respaldo=RESPALDO_EXTERNO --whatsapp=WHATSAPP_NUMERO_PRINCIPAL --cors=" . $origin . " --url=" . $origin . " --sku1=1759 --sku2=1757",
    "green_gate" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=" . $base
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_crea_publicaciones" => true,
    "no_registra_cotizaciones" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorSuite($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
