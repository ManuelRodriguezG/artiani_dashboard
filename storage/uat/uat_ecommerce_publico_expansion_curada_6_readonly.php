<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
 * Proposito: validar expansion curada a 6 productos usando titulo publico revisado para SKU 1138.
 * Impacto: permite recuperar la compuerta de 6 tarjetas sin escribir BD ni alterar Catalogo ERP maestro.
 * Contrato: read-only; no crea borradores, no publica, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::", "respaldo::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$respaldo = isset($opciones["respaldo"])
  ? trim((string) $opciones["respaldo"])
  : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql";

$curados = array(
  array("id_sku" => 415, "mascota" => "pez", "necesidades" => "habitat", "titulo" => null),
  array("id_sku" => 866, "mascota" => "pez", "necesidades" => "habitat", "titulo" => null),
  array("id_sku" => 386, "mascota" => "pez", "necesidades" => "habitat", "titulo" => null),
  array("id_sku" => 1138, "mascota" => "ave", "necesidades" => "habitat", "titulo" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm")
);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$catalogo = $api->catalogoPublico(array("limite" => 60));
$publicadas = intval(valorExpansionCurada($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$itemsCatalogo = valorExpansionCurada($catalogo, array("depurar", "items"), array());
$validacionRespaldo = validarRespaldoExpansionCurada($respaldo);

$items = array();
$bloqueos = array();
foreach ($curados as $curado) {
  $datos = array(
    "id_sku" => intval($curado["id_sku"]),
    "mascota_especie" => $curado["mascota"],
    "necesidades" => $curado["necesidades"],
    "estatus_publicacion" => "borrador"
  );
  if ($curado["titulo"] !== null) {
    $datos["titulo_publico"] = $curado["titulo"];
  }
  $plan = $api->planGuardarPublicacion($datos);
  $publicacion = valorExpansionCurada($plan, array("depurar", "publicacion_normalizada"), array());
  $producto = valorExpansionCurada($plan, array("depurar", "producto_vivo_erp"), array());
  $bloqueosPublicacion = valorExpansionCurada($plan, array("depurar", "bloqueos_publicacion"), array());
  $titulo = valorExpansionCurada($publicacion, array("titulo_publico"), "");
  if (textoSospechosoExpansionCurada($titulo)) {
    $bloqueosPublicacion[] = "validar_texto_publico";
  }
  $disponibilidad = valorExpansionCurada($producto, array("disponibilidad_publica_sugerida"), "");
  if (!in_array($disponibilidad, array("disponible", "pocas_piezas"), true)) {
    $bloqueosPublicacion[] = "disponibilidad_no_apta_fase1";
  }
  $listo = empty($plan["error"]) && empty($bloqueosPublicacion);
  if (!$listo) {
    $bloqueos[] = "sku_" . intval($curado["id_sku"]) . "_no_listo";
  }

  $items[] = array(
    "id_sku" => intval($curado["id_sku"]),
    "sku" => valorExpansionCurada($producto, array("sku"), ""),
    "titulo_publico" => $titulo,
    "usa_titulo_curado" => $curado["titulo"] !== null,
    "mascota" => valorExpansionCurada($publicacion, array("mascota_especie"), ""),
    "necesidades" => valorExpansionCurada($publicacion, array("necesidades"), array()),
    "precio" => valorExpansionCurada($producto, array("precio"), null),
    "disponibilidad" => $disponibilidad,
    "texto_publico_ok" => !textoSospechosoExpansionCurada($titulo),
    "listo_para_borrador" => $listo,
    "sha256_sql_borrador" => valorExpansionCurada($plan, array("depurar", "sha256_sql"), ""),
    "comando_plan" => comandoPlanExpansionCurada($curado),
    "comando_apply_borrador_si_autorizado" => comandoApplyExpansionCurada($curado, $respaldo),
    "bloqueos" => array_values(array_unique($bloqueosPublicacion))
  );
}

$listos = contarListosExpansionCurada($items);
if (!$validacionRespaldo["ok"]) {
  $bloqueos[] = "respaldo_no_valido";
}
if (valorExpansionCurada($estado, array("depurar", "ready"), false) !== true) {
  $bloqueos[] = "api_no_ready";
}
if ($publicadas + $listos < 6) {
  $bloqueos[] = "expansion_curada_no_alcanza_6";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "origin" => $origin,
  "senal_expansion_curada" => empty($bloqueos) ? "verde_expansion_curada_6_lista_para_revision" : "amarillo_revision_expansion_curada",
  "publicadas_actuales" => $publicadas,
  "publicaciones_estimadas_post_expansion" => $publicadas + $listos,
  "respaldo" => $validacionRespaldo,
  "items" => $items,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "orden_seguro" => array(
    "revisar_titulo_curado_sku_1138",
    "si_el_dueno_autoriza_crear_borradores_con_apply_authorized",
    "revisar_borradores_en_consola_interna",
    "publicar_en_paso_separado_con_confirmar_revision"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_borradores" => true,
    "no_publica" => true,
    "no_toca_nombre_maestro_sku" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function comandoPlanExpansionCurada($curado) {
  $cmd = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_plan_readonly.php --id_sku=" . intval($curado["id_sku"]);
  if ($curado["titulo"] !== null) { $cmd .= " --titulo=" . argumentoExpansionCurada($curado["titulo"]); }
  $cmd .= " --mascota=" . argumentoExpansionCurada($curado["mascota"]);
  $cmd .= " --necesidades=" . argumentoExpansionCurada($curado["necesidades"]);
  return $cmd;
}

function comandoApplyExpansionCurada($curado, $respaldo) {
  $cmd = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=" . argumentoExpansionCurada($respaldo) . " --id_sku=" . intval($curado["id_sku"]);
  if ($curado["titulo"] !== null) { $cmd .= " --titulo=" . argumentoExpansionCurada($curado["titulo"]); }
  $cmd .= " --mascota=" . argumentoExpansionCurada($curado["mascota"]);
  $cmd .= " --necesidades=" . argumentoExpansionCurada($curado["necesidades"]);
  return $cmd;
}

function contarListosExpansionCurada($items) {
  $total = 0;
  foreach ($items as $item) {
    if (!empty($item["listo_para_borrador"])) { $total++; }
  }
  return $total;
}

function validarRespaldoExpansionCurada($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = strtoupper($respaldo) === "" || strpos(strtoupper($respaldo), "RUTA_O_REFERENCIA") !== false || strpos(strtoupper($respaldo), "PLACEHOLDER") !== false;
  return array(
    "ok" => strlen($respaldo) >= 8 && !$placeholder && (!$esRutaLocal || ($existe && $legible && $tamano > 0)),
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function textoSospechosoExpansionCurada($texto) {
  $texto = (string) $texto;
  return strpos($texto, chr(239) . chr(191) . chr(189)) !== false
    || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $texto) === 1;
}

function argumentoExpansionCurada($valor) {
  return "\"" . str_replace("\"", "\\\"", (string) $valor) . "\"";
}

function valorExpansionCurada($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
