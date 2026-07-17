<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: generar paquete compacto de autorizacion para activar ecommerce publico Fase 1.
 * Impacto: concentra estado vivo, hashes y comandos sin ejecutar escrituras.
 * Contrato: read-only; no aplica DDL, no configura canal, no crea publicaciones y no toca inventario.
 */

$opciones = getopt("", array(
  "base::",
  "respaldo::",
  "whatsapp::",
  "cors::",
  "url::",
  "sku1::",
  "sku2::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "RUTA_O_REFERENCIA";
$whatsapp = isset($opciones["whatsapp"]) ? trim((string) $opciones["whatsapp"]) : "NUMERO_WHATSAPP";
$cors = isset($opciones["cors"]) ? trim((string) $opciones["cors"]) : "ORIGEN_FRONTEND";
$url = isset($opciones["url"]) ? trim((string) $opciones["url"]) : "URL_FRONTEND";
$sku1 = isset($opciones["sku1"]) ? intval($opciones["sku1"]) : 1759;
$sku2 = isset($opciones["sku2"]) ? intval($opciones["sku2"]) : 1757;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$api = new EcommerceCatalogoPublico();
$esquema = new EcommercePublicoEsquema();

$estado = $api->estadoApiPublica();
$planSchema = $esquema->planActualizarEcommercePublico(false);
$planConfig = $api->planConfiguracionInicial(array(
  "whatsapp_numero_principal" => $whatsapp,
  "cors_origenes_permitidos" => $cors,
  "url_sitio_publico" => $url
));
$planSku1 = $api->planGuardarPublicacion(array("id_sku" => $sku1));
$planSku2 = $api->planGuardarPublicacion(array("id_sku" => $sku2));

$ddlSql = extraerSqlAutorizacion($planSchema);
$ddlHash = hash("sha256", implode("\n\n", $ddlSql));
$bloqueos = array();
foreach (array(
  "respaldo" => $respaldo,
  "whatsapp" => $whatsapp,
  "cors" => $cors,
  "url" => $url
) as $campo => $valor) {
  if (placeholderAutorizacion($valor)) {
    $bloqueos[] = $campo . "_real_pendiente";
  }
}
if (strpos($cors, "*") !== false) {
  $bloqueos[] = "cors_no_debe_usar_wildcard";
}

$textoAutorizacion = "Autorizo aplicar DDL ecommerce publico Fase 1 con token ECOMMERCE_PUBLICO_DDL_FASE1 usando respaldo " . $respaldo . ". Confirmo que no se activara checkout, pagos online ni descuento de inventario.";

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "estado_vivo" => array(
    "base_api" => $base . "/ecommercePublico",
    "ddl_pendiente" => valorAutorizacion($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => intval(valorAutorizacion($estado, array("depurar", "publicaciones", "total_publicadas"), 0)),
    "publicables_fase_1" => intval(valorAutorizacion($estado, array("depurar", "publicaciones", "skus_publicables_fase_1"), 0)),
    "senal_actual" => "amarillo_mock_contratos"
  ),
  "hashes" => array(
    "ddl_sha256" => $ddlHash,
    "configuracion_sha256" => valorAutorizacion($planConfig, array("depurar", "sha256_sql"), ""),
    "sku1_borrador_sha256" => valorAutorizacion($planSku1, array("depurar", "sha256_sql"), ""),
    "sku2_borrador_sha256" => valorAutorizacion($planSku2, array("depurar", "sha256_sql"), "")
  ),
  "skus_recomendados" => array(
    resumenSkuAutorizacion($planSku1),
    resumenSkuAutorizacion($planSku2)
  ),
  "bloqueos" => $bloqueos,
  "texto_autorizacion_sugerido" => $textoAutorizacion,
  "comandos_no_ejecutados" => array(
    "checklist" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_apply_checklist_readonly.php --base=" . $base . " --respaldo=" . $respaldo . " --whatsapp=" . $whatsapp . " --cors=" . $cors . " --url=" . $url . " --sku1=" . $sku1 . " --sku2=" . $sku2,
    "aplicar_ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=" . $respaldo,
    "post_apply" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=" . $base . " --origin=" . $cors,
    "green_gate" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=" . $base
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_publica_skus" => true,
    "no_registra_cotizaciones" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function resumenSkuAutorizacion($plan) {
  return array(
    "id_sku" => intval(valorAutorizacion($plan, array("depurar", "producto_vivo_erp", "id_sku"), 0)),
    "sku" => valorAutorizacion($plan, array("depurar", "producto_vivo_erp", "sku"), ""),
    "nombre" => valorAutorizacion($plan, array("depurar", "producto_vivo_erp", "nombre"), ""),
    "disponibilidad" => valorAutorizacion($plan, array("depurar", "producto_vivo_erp", "disponibilidad_publica_sugerida"), ""),
    "slug" => valorAutorizacion($plan, array("depurar", "publicacion_normalizada", "slug"), "")
  );
}

function extraerSqlAutorizacion($plan) {
  $items = valorAutorizacion($plan, array("depurar", "plan"), array());
  $sql = array();
  foreach ($items as $item) {
    $linea = valorAutorizacion($item, array("depurar", "sql"), "");
    if ($linea !== "") {
      $sql[] = $linea;
    }
  }
  return $sql;
}

function placeholderAutorizacion($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "NUMERO_WHATSAPP") !== false
    || strpos($valor, "ORIGEN_FRONTEND") !== false
    || strpos($valor, "URL_FRONTEND") !== false;
}

function valorAutorizacion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
