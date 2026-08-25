<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-21.
 * Proposito: aplicar persistencia real de Media CMS con respaldo previo autorizado.
 * Impacto: crea tablas Media CMS y carpeta publica controlada para imagenes ecommerce.
 * Contrato: requiere autorizacion explicita del dueno; no expone credenciales; no sube archivos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$bloqueos = array();
$backupDir = "C:\\xampp\\panel_db_backups";
$stamp = date("Ymd_His");
$backupFile = $backupDir . "\\artianilocal_panel_" . $stamp . "_antes_cms_media_persistencia.sql";
$mediaDir = __DIR__ . "/../../public/assets/media/cms/ecommerce";

if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
  $bloqueos[] = "no_se_pudo_crear_directorio_respaldo";
}

if (count($bloqueos) === 0) {
  $respaldo = ejecutarMysqldumpSeguro($backupFile);
  if (!empty($respaldo["error"])) {
    $bloqueos[] = "respaldo_fallo";
  }
}

$plan = null;
if (count($bloqueos) === 0) {
  $esquema = new EcommercePublicoEsquema();
  $plan = $esquema->planActualizarCmsMediaBiblioteca(true);
  if (!empty($plan["error"])) {
    $bloqueos[] = "ddl_media_fallo";
  }
}

if (count($bloqueos) === 0 && !is_dir($mediaDir) && !mkdir($mediaDir, 0775, true)) {
  $bloqueos[] = "no_se_pudo_crear_carpeta_media";
}

echo json_encode(array(
  "ok" => count($bloqueos) === 0,
  "modo" => "cms_media_persistencia_authorized_apply",
  "bloqueos" => $bloqueos,
  "respaldo" => array(
    "archivo" => $backupFile,
    "existe" => is_file($backupFile),
    "bytes" => is_file($backupFile) ? filesize($backupFile) : 0
  ),
  "ddl" => array(
    "ejecutado" => true,
    "plan_error" => is_array($plan) ? !empty($plan["error"]) : null,
    "ddl_total" => is_array($plan) ? valorCmsMediaApply($plan, array("depurar", "ddl_total"), 0) : 0
  ),
  "media" => array(
    "carpeta_publica" => "/assets/media/cms/ecommerce",
    "ruta_fisica" => "public/assets/media/cms/ecommerce",
    "carpeta_existe" => is_dir($mediaDir)
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function ejecutarMysqldumpSeguro($backupFile) {
  $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
  if (!is_file($mysqldump)) {
    return array("error" => true, "mensaje" => "mysqldump_no_encontrado");
  }
  $cmd = escapeshellarg($mysqldump)
    . " --host=" . escapeshellarg(MYSQLHOST)
    . " --port=" . escapeshellarg(MYSQLPORT)
    . " --user=" . escapeshellarg(MYSQLUSER)
    . " --password=" . escapeshellarg(MYSQLPASS)
    . " --single-transaction --routines --triggers "
    . escapeshellarg(MYSQLBASE);

  $descriptor = array(
    0 => array("pipe", "r"),
    1 => array("file", $backupFile, "w"),
    2 => array("pipe", "w")
  );
  $process = proc_open($cmd, $descriptor, $pipes);
  if (!is_resource($process)) {
    return array("error" => true, "mensaje" => "proc_open_fallo");
  }
  fclose($pipes[0]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $code = proc_close($process);
  return array(
    "error" => $code !== 0 || !is_file($backupFile) || filesize($backupFile) <= 0,
    "code" => $code,
    "stderr" => $stderr
  );
}

function valorCmsMediaApply($origen, $ruta, $default = null) {
  $actual = $origen;
  foreach ((array) $ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
