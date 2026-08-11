<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-30.
 * Proposito: validar el contrato robusto de cotizacion preflight para frontend ecommerce.
 * Impacto: protege carrito/contacto/consentimiento/CTA WhatsApp sin habilitar persistencia real.
 * Contrato: read-only; no registra cotizacion, no crea pedido y no descuenta inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$items = valorPreflightRobusto($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$payload = array(
  "items" => empty($primerItem) ? array() : array(array(
    "slug" => valorPreflightRobusto($primerItem, array("slug"), ""),
    "cantidad" => 2
  )),
  "contacto" => array(
    "nombre" => "Cliente prueba preflight",
    "telefono" => "5512345678",
    "correo" => "cliente.preflight@example.com",
    "mensaje" => "Quiero validar esta cotizacion."
  ),
  "acepta_contacto_whatsapp" => true,
  "politicas_aceptadas" => array("aviso_privacidad", "terminos_cotizacion"),
  "utm" => array("source" => "uat", "medium" => "readonly")
);

$dryRun = $api->cotizacionDryRun($payload);
$preflight = $api->cotizacionPreflight($payload);
$dep = valorPreflightRobusto($preflight, array("depurar"), array());
$dryDep = valorPreflightRobusto($dryRun, array("depurar"), array());
$payloadFrontend = $payload;
$payloadFrontend["contacto"]["acepta_whatsapp"] = true;
$payloadFrontend["contacto"]["acepta_politicas"] = true;
unset($payloadFrontend["acepta_contacto_whatsapp"], $payloadFrontend["politicas_aceptadas"]);
$preflightFrontend = $api->cotizacionPreflight($payloadFrontend);
$depFrontend = valorPreflightRobusto($preflightFrontend, array("depurar"), array());
$advertenciasFrontend = valorPreflightRobusto($depFrontend, array("advertencias"), array());

$bloqueos = array();
if (empty($primerItem)) {
  $bloqueos[] = "catalogo_sin_publicaciones";
}
if (!empty($dryRun["error"]) || intval(valorPreflightRobusto($dryDep, array("resumen", "lineas_validas"), 0)) <= 0) {
  $bloqueos[] = "dryrun_sin_lineas_validas";
}
if (empty(valorPreflightRobusto($dryDep, array("frontend", "precio_es_estimado"), false))) {
  $bloqueos[] = "dryrun_sin_senal_precio_estimado";
}
if (empty(valorPreflightRobusto($dep, array("listo_para_whatsapp"), false))) {
  $bloqueos[] = "preflight_no_listo_whatsapp";
}
if (empty(valorPreflightRobusto($dep, array("listo_para_registro_futuro"), false))) {
  $bloqueos[] = "preflight_no_listo_registro_futuro";
}
if (valorPreflightRobusto($dep, array("cta", "tipo"), "") !== "whatsapp") {
  $bloqueos[] = "cta_whatsapp_no_generado";
}
if (trim((string) valorPreflightRobusto($dep, array("cta", "url"), "")) === "") {
  $bloqueos[] = "cta_url_faltante";
}
if (empty(valorPreflightRobusto($dep, array("validacion_contacto", "valido_para_registro_futuro"), false))) {
  $bloqueos[] = "contacto_no_valido_para_registro_futuro";
}
if (empty(valorPreflightRobusto($dep, array("consentimiento", "aviso_privacidad_aceptado"), false))) {
  $bloqueos[] = "aviso_privacidad_no_aceptado";
}
if (empty(valorPreflightRobusto($dep, array("no_escribe_bd"), false)) || empty(valorPreflightRobusto($dep, array("no_descuenta_inventario"), false))) {
  $bloqueos[] = "guardrails_preflight_incompletos";
}
foreach (array("contacto_telefono_recomendado", "aceptacion_whatsapp_recomendada", "politicas_aceptadas_no_informadas") as $advertenciaNoEsperada) {
  if (in_array($advertenciaNoEsperada, $advertenciasFrontend, true)) {
    $bloqueos[] = "frontend_payload_con_advertencia_" . $advertenciaNoEsperada;
  }
}
if (empty(valorPreflightRobusto($depFrontend, array("acepta_contacto_whatsapp"), false))) {
  $bloqueos[] = "frontend_payload_no_reconoce_acepta_whatsapp";
}
if (empty(valorPreflightRobusto($depFrontend, array("consentimiento", "aviso_privacidad_aceptado"), false))) {
  $bloqueos[] = "frontend_payload_no_reconoce_acepta_politicas";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "cotizacion_preflight_robusto" : "cotizacion_preflight_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "producto" => empty($primerItem) ? null : array(
    "slug" => valorPreflightRobusto($primerItem, array("slug"), ""),
    "nombre" => valorPreflightRobusto($primerItem, array("nombre"), "")
  ),
  "dryrun" => array(
    "lineas_validas" => intval(valorPreflightRobusto($dryDep, array("resumen", "lineas_validas"), 0)),
    "cantidad_total" => valorPreflightRobusto($dryDep, array("resumen", "cantidad_total"), 0),
    "precio_es_estimado" => valorPreflightRobusto($dryDep, array("frontend", "precio_es_estimado"), false),
    "bloqueos" => valorPreflightRobusto($dryDep, array("bloqueos"), array())
  ),
  "preflight" => array(
    "listo_para_whatsapp" => valorPreflightRobusto($dep, array("listo_para_whatsapp"), false),
    "listo_para_registro_futuro" => valorPreflightRobusto($dep, array("listo_para_registro_futuro"), false),
    "cta_tipo" => valorPreflightRobusto($dep, array("cta", "tipo"), ""),
    "cta_url_generada" => trim((string) valorPreflightRobusto($dep, array("cta", "url"), "")) !== "",
    "contacto_valido" => valorPreflightRobusto($dep, array("validacion_contacto", "valido_para_registro_futuro"), false),
    "aviso_privacidad_aceptado" => valorPreflightRobusto($dep, array("consentimiento", "aviso_privacidad_aceptado"), false)
  ),
  "preflight_payload_frontend" => array(
    "acepta_contacto_whatsapp" => valorPreflightRobusto($depFrontend, array("acepta_contacto_whatsapp"), false),
    "politicas_aceptadas" => valorPreflightRobusto($depFrontend, array("politicas_aceptadas"), array()),
    "advertencias" => $advertenciasFrontend
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_crea_pedido" => true,
    "no_descuenta_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorPreflightRobusto($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
