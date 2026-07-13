<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: indicar si el proyecto frontend externo puede iniciar contra contratos o contra datos reales.
 * Impacto: evita arrancar la vista publica con supuestos incorrectos de publicaciones, CORS o WhatsApp.
 * Contrato: read-only; no crea publicaciones, no configura canal, no registra cotizaciones y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$contratos = $api->contratosApiPublicos();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$filtros = $api->filtrosPublicos();
$dryrun = $api->cotizacionDryRun(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1))));

$endpoints = valorFrontendReadiness($contratos, array("depurar", "endpoints_publicos"), array());
$publicadas = intval(valorFrontendReadiness($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$ddlPendiente = valorFrontendReadiness($estado, array("depurar", "schema", "ddl_pendiente"), true);
$config = valorFrontendReadiness($configuracion, array("depurar", "configuracion"), array());
$whatsapp = trim((string) valorFrontendReadiness($config, array("whatsapp_numero_principal"), ""));
$cors = trim((string) valorFrontendReadiness($config, array("cors_origenes_permitidos"), ""));

$puedeMock = count($endpoints) >= 8
  && valorFrontendReadiness($dryrun, array("depurar", "dry_run"), false) === true;

$bloqueosDatosReales = array();
if ($ddlPendiente) {
  $bloqueosDatosReales[] = "ddl_ecommerce_publico_pendiente";
}
if ($publicadas <= 0) {
  $bloqueosDatosReales[] = "sin_publicaciones_activas";
}
if ($whatsapp === "") {
  $bloqueosDatosReales[] = "whatsapp_no_configurado";
}
if ($cors === "") {
  $bloqueosDatosReales[] = "cors_origenes_permitidos_no_configurado";
}

$senal = empty($bloqueosDatosReales)
  ? "verde_datos_reales"
  : ($puedeMock ? "amarillo_mock_contratos" : "rojo_bloqueado");

echo json_encode(array(
  "ok" => $puedeMock,
  "modo" => "read-only",
  "senal_frontend" => $senal,
  "puede_iniciar_frontend_mock" => $puedeMock,
  "puede_integrar_datos_reales" => empty($bloqueosDatosReales),
  "api" => array(
    "base_path" => valorFrontendReadiness($contratos, array("depurar", "base_path"), "/ecommercePublico"),
    "version" => valorFrontendReadiness($contratos, array("depurar", "api", "version"), ""),
    "endpoints_total" => count($endpoints)
  ),
  "estado" => array(
    "ready" => valorFrontendReadiness($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => $ddlPendiente,
    "publicadas" => $publicadas
  ),
  "configuracion" => array(
    "whatsapp_configurado" => $whatsapp !== "",
    "cors_configurado" => $cors !== ""
  ),
  "respuestas_publicas" => array(
    "catalogo_configurado" => valorFrontendReadiness($catalogo, array("depurar", "configurado"), false),
    "filtros_configurados" => valorFrontendReadiness($filtros, array("depurar", "configurado"), false),
    "cotizacion_dryrun_responde" => valorFrontendReadiness($dryrun, array("depurar", "dry_run"), false)
  ),
  "bloqueos_datos_reales" => $bloqueosDatosReales,
  "mensaje_para_frontend" => empty($bloqueosDatosReales)
    ? "Ya puedes iniciar/integrar la vista del ecommerce externo con datos reales."
    : "Puedes iniciar el proyecto frontend externo como maqueta tecnica/cliente API, pero aun no como catalogo vivo real.",
  "documentos_frontend" => array(
    "docs/erp_ecommerce_publico_frontend_handoff.md",
    "docs/erp_ecommerce_publico_instrucciones_proyecto_frontend.md",
    "docs/erp_ecommerce_publico_api_contratos.md"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_descuenta_inventario" => true,
    "no_crea_checkout" => true,
    "no_usa_ecom_legacy_como_fuente" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valorFrontendReadiness($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
