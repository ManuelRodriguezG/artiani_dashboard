<?php

/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
 * Proposito: generar respaldo externo y aplicar exclusivamente esquema/permisos de Distribucion API.
 * Impacto: Base ERP efectiva segun configuracion; no toca catalogo, precios, inventario, compras ni ecommerce.
 * Contrato: CLI local; requiere token DISTRIBUCION_API_DDL y ruta de respaldo fuera del repo.
 */

if (php_sapi_name() !== "cli") {
  exit("Solo CLI\n");
}

$token = isset($argv[1]) ? trim((string) $argv[1]) : "";
if ($token !== "DISTRIBUCION_API_DDL") {
  exit(json_encode(array("error" => true, "mensaje" => "Token de autorizacion invalido")) . PHP_EOL);
}

require __DIR__ . "/../../app/iniciador.php";
require_once RUTA_APP . "/modelos/DistribucionApiEsquema.php";

$timestamp = date("Ymd_His");
$backupDir = "C:\\xampp\\panel_db_backups";
$backup = $backupDir . "\\" . MYSQLBASE . "_panel_de_control_" . $timestamp . "_antes_distribucion_api_schema.sql";

if (!is_dir($backupDir)) {
  mkdir($backupDir, 0777, true);
}

$defaults = tempnam(sys_get_temp_dir(), "mysqldump_");
$contenidoDefaults = "[client]\n"
  . "host=" . MYSQLHOST . "\n"
  . "port=" . MYSQLPORT . "\n"
  . "user=" . MYSQLUSER . "\n"
  . "password=" . MYSQLPASS . "\n";
file_put_contents($defaults, $contenidoDefaults);

$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
$cmd = escapeshellarg($mysqldump)
  . " --defaults-extra-file=" . escapeshellarg($defaults)
  . " --result-file=" . escapeshellarg($backup)
  . " " . escapeshellarg(MYSQLBASE);
exec($cmd, $salida, $codigo);
@unlink($defaults);

clearstatcache(true, $backup);
$backupOk = $codigo === 0 && file_exists($backup) && filesize($backup) > 0;
if (!$backupOk) {
  exit(json_encode(array(
    "error" => true,
    "mensaje" => "No se pudo generar respaldo externo",
    "depurar" => array("backup" => $backup, "codigo" => $codigo)
  )) . PHP_EOL);
}

$esquema = new DistribucionApiEsquema();
$antes = $esquema->auditarDistribucionApi();
$ddl = $esquema->planActualizarDistribucionApi(true);
$permisos = $esquema->planPermisosInternosDistribucion(true);
$despues = $esquema->auditarDistribucionApi();

echo json_encode(array(
  "error" => false,
  "mensaje" => "Distribucion API aplicado con respaldo externo",
  "backup" => array(
    "ruta" => $backup,
    "tamano_bytes" => filesize($backup)
  ),
  "antes" => array(
    "ready" => isset($antes["depurar"]["ready"]) ? $antes["depurar"]["ready"] : null,
    "faltantes" => isset($antes["depurar"]["faltantes"]) ? $antes["depurar"]["faltantes"] : array()
  ),
  "ddl" => array(
    "error" => isset($ddl["error"]) ? $ddl["error"] : true,
    "mensaje" => isset($ddl["mensaje"]) ? $ddl["mensaje"] : ""
  ),
  "permisos" => array(
    "error" => isset($permisos["error"]) ? $permisos["error"] : true,
    "mensaje" => isset($permisos["mensaje"]) ? $permisos["mensaje"] : ""
  ),
  "despues" => array(
    "ready" => isset($despues["depurar"]["ready"]) ? $despues["depurar"]["ready"] : null,
    "faltantes" => isset($despues["depurar"]["faltantes"]) ? $despues["depurar"]["faltantes"] : array()
  )
), JSON_UNESCAPED_SLASHES) . PHP_EOL;
