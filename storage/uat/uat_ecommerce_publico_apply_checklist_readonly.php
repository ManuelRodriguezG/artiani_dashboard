<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: preparar checklist de apply ecommerce publico sin ejecutar escrituras.
 * Impacto: reduce errores al pasar de contratos read-only a DDL/configuracion/publicaciones autorizadas.
 * Contrato: read-only; no ejecuta DDL, no configura canal, no crea publicaciones y no toca inventario.
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
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$whatsapp = isset($opciones["whatsapp"]) ? trim((string) $opciones["whatsapp"]) : "";
$cors = isset($opciones["cors"]) ? trim((string) $opciones["cors"]) : "";
$url = isset($opciones["url"]) ? trim((string) $opciones["url"]) : "";
$sku1 = isset($opciones["sku1"]) ? intval($opciones["sku1"]) : 1759;
$sku2 = isset($opciones["sku2"]) ? intval($opciones["sku2"]) : 1757;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->planConfiguracionInicial(array(
  "whatsapp_numero_principal" => $whatsapp,
  "cors_origenes_permitidos" => $cors,
  "url_sitio_publico" => $url
));
$planSku1 = $api->planGuardarPublicacion(array("id_sku" => $sku1));
$planSku2 = $api->planGuardarPublicacion(array("id_sku" => $sku2));

$bloqueos = array();
$advertencias = array();
$respaldoOk = validarRespaldoChecklist($respaldo);
if (!$respaldoOk["ok"]) {
  $bloqueos[] = "respaldo_externo_o_referencia_valida_pendiente";
}
if (esPlaceholderChecklist($respaldo)) {
  $bloqueos[] = "respaldo_es_placeholder";
}
if ($whatsapp === "" || esPlaceholderChecklist($whatsapp)) {
  $bloqueos[] = "whatsapp_real_pendiente";
}
if ($cors === "" || esPlaceholderChecklist($cors)) {
  $bloqueos[] = "cors_real_pendiente";
}
if ($url === "" || esPlaceholderChecklist($url)) {
  $bloqueos[] = "url_frontend_real_pendiente";
}
if (strpos($cors, "*") !== false) {
  $bloqueos[] = "cors_no_debe_usar_wildcard";
}
if (valorChecklist($estado, array("depurar", "schema", "ddl_pendiente"), true) !== true) {
  $advertencias[] = "ddl_ya_no_parece_pendiente_revisar_antes_de_apply";
}
if (intval(valorChecklist($estado, array("depurar", "publicaciones", "total_publicadas"), 0)) > 0) {
  $advertencias[] = "ya_existen_publicaciones_revisar_duplicados";
}

$sku1Disponible = valorChecklist($planSku1, array("depurar", "producto_vivo_erp", "disponibilidad_publica_sugerida"), "");
$sku2Disponible = valorChecklist($planSku2, array("depurar", "producto_vivo_erp", "disponibilidad_publica_sugerida"), "");
if (!in_array($sku1Disponible, array("disponible", "pocas_piezas"), true)) {
  $advertencias[] = "sku1_no_disponible_revisar_politica";
}
if (!in_array($sku2Disponible, array("disponible", "pocas_piezas"), true)) {
  $advertencias[] = "sku2_no_disponible_revisar_politica";
}

$comandos = array(
  "validar_entorno" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_entorno_readonly.php --base=" . $base,
  "revisar_ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_sql_readonly.php --respaldo=" . $respaldo,
  "aplicar_ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=" . $respaldo,
  "aplicar_configuracion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=" . $respaldo . " --whatsapp=" . $whatsapp . " --cors=" . $cors . " --url=" . $url,
  "crear_borrador_sku1" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=" . $respaldo . " --id_sku=" . $sku1,
  "crear_borrador_sku2" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=" . $respaldo . " --id_sku=" . $sku2,
  "publicar_sku1" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=" . $respaldo . " --id_sku=" . $sku1 . " --confirmar_revision=1",
  "validar_green_gate" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=" . $base
);

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base" => $base,
  "respaldo" => $respaldoOk,
  "entrada" => array(
    "whatsapp" => $whatsapp,
    "cors" => $cors,
    "url" => $url,
    "sku1" => $sku1,
    "sku2" => $sku2
  ),
  "estado_actual" => array(
    "ddl_pendiente" => valorChecklist($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => intval(valorChecklist($estado, array("depurar", "publicaciones", "total_publicadas"), 0)),
    "publicables" => intval(valorChecklist($estado, array("depurar", "publicaciones", "skus_publicables_fase_1"), 0))
  ),
  "planes" => array(
    "configuracion_sha256" => valorChecklist($configuracion, array("depurar", "sha256_sql"), ""),
    "sku1" => resumenPlanSkuChecklist($planSku1),
    "sku2" => resumenPlanSkuChecklist($planSku2)
  ),
  "bloqueos" => $bloqueos,
  "advertencias" => $advertencias,
  "comandos_no_ejecutados" => $comandos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_crea_publicaciones" => true,
    "no_publica_skus" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function resumenPlanSkuChecklist($plan) {
  return array(
    "id_sku" => intval(valorChecklist($plan, array("depurar", "producto_vivo_erp", "id_sku"), 0)),
    "sku" => valorChecklist($plan, array("depurar", "producto_vivo_erp", "sku"), ""),
    "nombre" => valorChecklist($plan, array("depurar", "producto_vivo_erp", "nombre"), ""),
    "disponibilidad" => valorChecklist($plan, array("depurar", "producto_vivo_erp", "disponibilidad_publica_sugerida"), ""),
    "slug" => valorChecklist($plan, array("depurar", "publicacion_normalizada", "slug"), ""),
    "sha256_sql" => valorChecklist($plan, array("depurar", "sha256_sql"), ""),
    "bloqueos" => valorChecklist($plan, array("depurar", "bloqueos_publicacion"), array())
  );
}

function validarRespaldoChecklist($respaldo) {
  $esPlaceholder = esPlaceholderChecklist($respaldo);
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => !$esPlaceholder && $okReferencia && $okRuta,
    "referencia" => $respaldo,
    "es_placeholder" => $esPlaceholder,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}

function esPlaceholderChecklist($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "NUMERO_WHATSAPP") !== false
    || strpos($valor, "ORIGEN_FRONTEND") !== false
    || strpos($valor, "URL_FRONTEND") !== false;
}

function valorChecklist($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
