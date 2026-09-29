<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-29
 * Proposito: generar respaldo externo antes de aplicar esquema de estudios de rentabilidad.
 * Impacto: escribe un dump SQL fuera del repo en C:\xampp\panel_db_backups; no modifica BD.
 * Contrato: no expone credenciales y elimina archivo temporal de conexion.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$dir = "C:\\xampp\\panel_db_backups";
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

$stamp = date("Ymd_His");
$archivo = $dir . DIRECTORY_SEPARATOR . MYSQLBASE . "_panel_de_control_" . $stamp . "_antes_rentabilidad_estudios_schema.sql";
$cnf = $dir . DIRECTORY_SEPARATOR . "mysqldump_rentabilidad_estudios_" . getmypid() . ".cnf";
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

if (!is_file($mysqldump)) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array("ok" => false, "mensaje" => "No se encontro mysqldump", "ruta" => $mysqldump), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}

file_put_contents($cnf, "[client]\n"
    . "user=\"" . MYSQLUSER . "\"\n"
    . "password=\"" . MYSQLPASS . "\"\n"
    . "host=\"" . MYSQLHOST . "\"\n"
    . "port=\"" . MYSQLPORT . "\"\n");

$cmd = escapeshellarg($mysqldump)
    . " --defaults-extra-file=" . escapeshellarg($cnf)
    . " --single-transaction --routines --triggers --events"
    . " --result-file=" . escapeshellarg($archivo)
    . " " . escapeshellarg(MYSQLBASE);

$salida = array();
$codigo = 1;
exec($cmd, $salida, $codigo);
@unlink($cnf);
clearstatcache(true, $archivo);

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => $codigo === 0 && is_file($archivo) && filesize($archivo) > 0,
    "codigo" => $codigo,
    "archivo" => $archivo,
    "bytes" => is_file($archivo) ? filesize($archivo) : 0,
    "base" => MYSQLBASE,
    "host" => MYSQLHOST,
    "reglas" => array(
        "Respaldo fuera del repo.",
        "No modifica BD.",
        "No imprime credenciales."
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
