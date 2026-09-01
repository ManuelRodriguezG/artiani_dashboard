<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: generar respaldo externo antes de aplicar DDL Ecommerce Leads en la base configurada del sistema.
 * Impacto: no escribe BD; crea archivo .sql fuera del repo en C:\xampp\panel_db_backups.
 * Contrato: usa constantes MYSQL* del proyecto y no imprime credenciales.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$directorio = "C:\\xampp\\panel_db_backups";
$dump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
if (!is_file($dump)) {
  $dump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
}
if (!is_dir($directorio)) {
  salida(false, "Directorio de respaldos no existe", array("directorio" => $directorio));
}
if (!is_file($dump)) {
  salida(false, "mysqldump no encontrado", array("mysqldump" => $dump));
}

$fecha = date("Ymd_His");
$baseLimpia = preg_replace('/[^a-zA-Z0-9_\-]/', '', MYSQLBASE);
$archivo = $directorio . "\\" . $baseLimpia . "_panel_" . $fecha . "_antes_ecommerce_leads.sql";
$defaults = tempnam(sys_get_temp_dir(), "mysql_defaults_");
file_put_contents($defaults, "[client]\r\nuser=\"" . addcslashes(MYSQLUSER, "\\\"") . "\"\r\npassword=\"" . addcslashes(MYSQLPASS, "\\\"") . "\"\r\nhost=\"" . addcslashes(MYSQLHOST, "\\\"") . "\"\r\nport=\"" . addcslashes(MYSQLPORT, "\\\"") . "\"\r\n");

$cmd = array(
  $dump,
  "--defaults-extra-file=" . $defaults,
  "--single-transaction",
  "--routines",
  "--triggers",
  "--events",
  "--default-character-set=utf8mb4",
  "--result-file=" . $archivo,
  MYSQLBASE
);

$descriptor = array(
  1 => array("pipe", "w"),
  2 => array("pipe", "w")
);
$proceso = proc_open($cmd, $descriptor, $pipes);
$stdout = "";
$stderr = "";
$codigo = 1;
if (is_resource($proceso)) {
  $stdout = stream_get_contents($pipes[1]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[1]);
  fclose($pipes[2]);
  $codigo = proc_close($proceso);
}
@unlink($defaults);

$existe = is_file($archivo);
$tamano = $existe ? filesize($archivo) : 0;
$ok = $codigo === 0 && $existe && $tamano > 0;
salida($ok, $ok ? "Respaldo productivo generado" : "No se pudo generar respaldo productivo", array(
  "base" => MYSQLBASE,
  "host" => MYSQLHOST,
  "port" => MYSQLPORT,
  "archivo" => $archivo,
  "tamano_bytes" => $tamano,
  "sha256" => $ok ? hash_file("sha256", $archivo) : null,
  "codigo" => $codigo,
  "stdout" => trim($stdout),
  "stderr_sin_secretos" => limpiarSecretos($stderr)
));

function limpiarSecretos($texto) {
  $texto = str_replace(MYSQLPASS, "[password]", (string) $texto);
  $texto = str_replace(MYSQLUSER, "[user]", $texto);
  return trim($texto);
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
