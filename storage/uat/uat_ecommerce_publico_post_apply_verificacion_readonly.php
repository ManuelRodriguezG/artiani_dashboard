<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: verificar estado despues de aplicar DDL/configuracion/publicaciones ecommerce.
 * Impacto: permite avanzar por etapas sin confundir DDL aplicado, configuracion incompleta o catalogo aun vacio.
 * Contrato: read-only; no escribe BD, no publica SKUs, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://localhost:5173";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$items = valorPostApply($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorPostApply($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
  ));
}

$ddlOk = valorPostApply($estado, array("depurar", "schema", "ddl_pendiente"), true) === false;
$whatsappOk = trim((string) valorPostApply($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "";
$corsConfig = trim((string) valorPostApply($configuracion, array("depurar", "configuracion", "cors_origenes_permitidos"), ""));
$corsOk = origenPermitidoPostApply($corsConfig, $origin);
$catalogoOk = !empty($primerItem);
$dryrunOk = !empty($dryrun)
  && empty($dryrun["error"])
  && !empty(valorPostApply($dryrun, array("depurar", "lineas"), array()));

$bloqueos = array();
if (!$ddlOk) {
  $bloqueos[] = "ddl_pendiente";
}
if (!$whatsappOk) {
  $bloqueos[] = "whatsapp_no_configurado";
}
if (!$corsOk) {
  $bloqueos[] = "cors_no_permite_origin_" . $origin;
}
if (!$catalogoOk) {
  $bloqueos[] = "catalogo_sin_publicaciones";
}
if (!$dryrunOk) {
  $bloqueos[] = "cotizacion_dryrun_sin_item_real";
}

$etapa = "pendiente_ddl";
if ($ddlOk && (!$whatsappOk || !$corsOk)) {
  $etapa = "ddl_ok_configuracion_pendiente";
} elseif ($ddlOk && $whatsappOk && $corsOk && !$catalogoOk) {
  $etapa = "configuracion_ok_publicaciones_pendientes";
} elseif ($ddlOk && $whatsappOk && $corsOk && $catalogoOk && !$dryrunOk) {
  $etapa = "catalogo_ok_dryrun_pendiente";
} elseif (empty($bloqueos)) {
  $etapa = "verde_datos_reales";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base" => $base,
  "origin_validado" => $origin,
  "etapa" => $etapa,
  "checks" => array(
    "ddl_ok" => $ddlOk,
    "whatsapp_ok" => $whatsappOk,
    "cors_ok" => $corsOk,
    "catalogo_ok" => $catalogoOk,
    "dryrun_ok" => $dryrunOk
  ),
  "primer_item" => empty($primerItem) ? null : array(
    "id_publicacion" => valorPostApply($primerItem, array("id_publicacion"), null),
    "id_sku" => valorPostApply($primerItem, array("id_sku"), null),
    "slug" => valorPostApply($primerItem, array("slug"), ""),
    "nombre" => valorPostApply($primerItem, array("nombre"), ""),
    "disponibilidad" => valorPostApply($primerItem, array("disponibilidad"), "")
  ),
  "bloqueos" => $bloqueos,
  "siguiente_paso" => siguientePasoPostApply($etapa),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizaciones" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function siguientePasoPostApply($etapa) {
  if ($etapa === "pendiente_ddl") {
    return "Aplicar DDL solo con respaldo externo y token autorizado.";
  }
  if ($etapa === "ddl_ok_configuracion_pendiente") {
    return "Aplicar configuracion WhatsApp/CORS/URL con token autorizado.";
  }
  if ($etapa === "configuracion_ok_publicaciones_pendientes") {
    return "Crear borradores y publicar al menos un SKU revisado.";
  }
  if ($etapa === "catalogo_ok_dryrun_pendiente") {
    return "Revisar cotizacion_dryrun contra publicacion real.";
  }
  return "Avisar al frontend que puede integrar datos reales.";
}

function origenPermitidoPostApply($config, $origin) {
  $origin = rtrim(trim((string) $origin), "/");
  if ($origin === "" || trim((string) $config) === "") {
    return false;
  }
  $permitidos = preg_split('/[\r\n,]+/', $config);
  foreach ($permitidos as $permitido) {
    if (rtrim(trim((string) $permitido), "/") === $origin) {
      return true;
    }
  }
  return false;
}

function valorPostApply($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
