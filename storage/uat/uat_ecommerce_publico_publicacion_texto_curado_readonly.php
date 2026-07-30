<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
 * Proposito: validar un titulo publico curado para una publicacion ecommerce antes de crear borrador.
 * Impacto: permite corregir copy publico sin tocar el nombre maestro del SKU ni escribir BD.
 * Contrato: read-only; no crea publicaciones, no publica y no toca inventario.
 */

$opciones = getopt("", array("id_sku::", "titulo::", "mascota::", "necesidades::"));
$idSku = isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 1138;
$titulo = isset($opciones["titulo"])
  ? trim((string) $opciones["titulo"])
  : "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm";
$mascota = isset($opciones["mascota"]) ? trim((string) $opciones["mascota"]) : "ave";
$necesidades = isset($opciones["necesidades"]) ? trim((string) $opciones["necesidades"]) : "habitat";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$plan = $api->planGuardarPublicacion(array(
  "id_sku" => $idSku,
  "titulo_publico" => $titulo,
  "mascota_especie" => $mascota,
  "necesidades" => $necesidades,
  "estatus_publicacion" => "borrador"
));

$publicacion = valorTextoCurado($plan, array("depurar", "publicacion_normalizada"), array());
$bloqueos = valorTextoCurado($plan, array("depurar", "bloqueos_publicacion"), array());
$advertencias = array();
if (textoSospechosoTextoCurado($titulo)) {
  $bloqueos[] = "titulo_curado_contiene_caracteres_sospechosos";
}
if (strlen($titulo) < 12) {
  $bloqueos[] = "titulo_curado_demasiado_corto";
}
if (strlen($titulo) > 180) {
  $bloqueos[] = "titulo_curado_demasiado_largo";
}
if (preg_match('/\d+\s*x\s*\d+/i', $titulo) !== 1 && $idSku === 1138) {
  $advertencias[] = "confirmar_medida_publica_del_producto";
}

echo json_encode(array(
  "ok" => empty($plan["error"]) && empty($bloqueos),
  "modo" => "read-only",
  "id_sku" => $idSku,
  "titulo_curado" => $titulo,
  "mascota" => $mascota,
  "necesidades" => $necesidades,
  "plan_ok" => empty($plan["error"]),
  "publicacion_normalizada" => $publicacion,
  "sha256_sql" => valorTextoCurado($plan, array("depurar", "sha256_sql"), ""),
  "comando_plan" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_plan_readonly.php --id_sku=" . $idSku . " --titulo=" . argumentoTextoCurado($titulo) . " --mascota=" . argumentoTextoCurado($mascota) . " --necesidades=" . argumentoTextoCurado($necesidades),
  "comando_apply_borrador_si_autorizado" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=RUTA_RESPALDO_EXTERNO --id_sku=" . $idSku . " --titulo=" . argumentoTextoCurado($titulo) . " --mascota=" . argumentoTextoCurado($mascota) . " --necesidades=" . argumentoTextoCurado($necesidades),
  "advertencias" => $advertencias,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_borrador" => true,
    "no_publica" => true,
    "no_toca_nombre_maestro_sku" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function textoSospechosoTextoCurado($texto) {
  $texto = (string) $texto;
  return strpos($texto, chr(239) . chr(191) . chr(189)) !== false
    || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $texto) === 1;
}

function argumentoTextoCurado($valor) {
  return "\"" . str_replace("\"", "\\\"", (string) $valor) . "\"";
}

function valorTextoCurado($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
