<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: aplicar DDL Ecommerce Videos solo con autorizacion explicita y respaldo documentado.
 * Impacto: crea tablas de videos, relaciones producto y relaciones categoria; no publica videos ni toca inventario.
 * Contrato: apply_authorized; requiere --autorizar=ECOMMERCE_VIDEOS_DDL y --respaldo=...
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

class EcommerceVideosApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$args = argumentos($argv);
$token = $args["autorizar"] ?? "";
$respaldo = $args["respaldo"] ?? "";
$tokenEsperado = "ECOMMERCE_VIDEOS_DDL";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado,
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_videos_schema_apply_authorized.php --autorizar=ECOMMERCE_VIDEOS_DDL --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql"
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo)) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo,
    "guardrail" => "No aplicar DDL sin respaldo existente."
  ));
}

$videos = new EcommerceVideosErp();
$antes = $videos->esquemaAuditarVideos();
$plan = $videos->esquemaPlanVideos();
$sqlPlan = $plan["depurar"]["sql_plan"] ?? array();
$db = (new EcommerceVideosApplyDb())->db();

if (!$db) {
  salida(false, "No hay conexion a base de datos", array("modo" => "apply_authorized", "ejecutado" => false));
}

$ejecutados = array();
try {
  foreach ($sqlPlan as $tabla => $sql) {
    $sql = preg_replace('/^CREATE TABLE `/', 'CREATE TABLE IF NOT EXISTS `', trim((string) $sql));
    $db->exec($sql);
    $ejecutados[] = $tabla;
  }
} catch (Exception $e) {
  salida(false, "DDL Ecommerce Videos fallo", array(
    "modo" => "apply_authorized",
    "ejecutado" => true,
    "respaldo" => $respaldo,
    "ejecutados" => $ejecutados,
    "error" => $e->getMessage(),
    "antes" => $antes["depurar"] ?? array()
  ));
}

$despues = $videos->esquemaAuditarVideos();
$ok = empty($despues["error"])
  && intval($despues["depurar"]["tablas_faltantes"] ?? 1) === 0
  && intval($despues["depurar"]["columnas_faltantes_total"] ?? 1) === 0
  && intval($despues["depurar"]["indices_faltantes_total"] ?? 1) === 0;

salida($ok, $ok ? "DDL Ecommerce Videos aplicado" : "DDL Ecommerce Videos con pendientes", array(
  "modo" => "apply_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "ejecutados" => $ejecutados,
  "antes" => $antes["depurar"] ?? array(),
  "despues" => $despues["depurar"] ?? array(),
  "guardrails" => array(
    "no_publica_videos" => true,
    "no_descarga_videos" => true,
    "no_toca_ventas" => true,
    "no_toca_inventario" => true,
    "no_toca_publicaciones_existentes" => true
  )
));

function argumentos($argv) {
  $salida = array();
  foreach ($argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = $partes[1];
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
