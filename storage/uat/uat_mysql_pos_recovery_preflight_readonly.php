<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: preparar recuperacion MySQL UAT POS sin ejecutar acciones destructivas.
 * Impacto: valida rutas, respaldo, procesos y comandos sugeridos para recuperar MariaDB.
 * Contrato: read-only; no detiene procesos, no modifica my.ini, no mueve data, no restaura ni importa SQL.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$mysqlData = "C:\\xampp\\mysql\\data";
$mysqlBin = "C:\\xampp\\mysql\\bin";
$token = "MYSQL_UAT_POS_RECOVERY";

foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--mysql_data=") === 0) {
        $mysqlData = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--mysql_bin=") === 0) {
        $mysqlBin = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--token=") === 0) {
        $token = trim(substr($arg, 8), "\"' ");
    }
}

$mysqld = rtrim($mysqlBin, "\\/") . "\\mysqld.exe";
$mysqladmin = rtrim($mysqlBin, "\\/") . "\\mysqladmin.exe";
$mysql = rtrim($mysqlBin, "\\/") . "\\mysql.exe";
$myIni = rtrim($mysqlBin, "\\/") . "\\my.ini";
$errorLog = rtrim($mysqlData, "\\/") . "\\mysql_error.log";
$backupDataDir = rtrim(dirname($mysqlData), "\\/") . "\\data_pos_recovery_" . date("Ymd_His");

$bloqueos = array();
$avisos = array();
if (!is_file($respaldo) || filesize($respaldo) <= 0) {
    $bloqueos[] = "Respaldo SQL no existe o esta vacio";
}
if (!is_dir($mysqlData)) {
    $bloqueos[] = "Carpeta mysql data no existe";
}
if (!is_file($mysqld)) {
    $bloqueos[] = "mysqld.exe no existe";
}
if (!is_file($mysqladmin)) {
    $avisos[] = "mysqladmin.exe no encontrado; se omitira ping propuesto";
}
if (!is_file($mysql)) {
    $bloqueos[] = "mysql.exe no existe";
}
if (!is_file($myIni)) {
    $bloqueos[] = "my.ini no existe";
}
$logTail = array();
if (is_file($errorLog)) {
    $lineas = file($errorLog, FILE_IGNORE_NEW_LINES);
    $logTail = array_slice($lineas ?: array(), -20);
} else {
    $avisos[] = "mysql_error.log no existe en data";
}

$autorizacion = "AUTORIZO RECUPERAR MYSQL UAT POS usando respaldo " . $respaldo
    . " con token " . $token
    . " permitiendo respaldo previo de " . $mysqlData
    . ", arranque controlado de MariaDB, diagnostico InnoDB y restauracion/importacion solo si es necesario para continuar UAT POS";

$comandosPropuestos = array(
    "preflight_ping" => $mysqladmin . " ping -h 127.0.0.1 -u root",
    "arranque_normal" => "Start-Process -FilePath " . $mysqld . " -ArgumentList \"--defaults-file=" . $myIni . "\" -WindowStyle Hidden",
    "arranque_recovery_1" => "Start-Process -FilePath " . $mysqld . " -ArgumentList \"--defaults-file=" . $myIni . " --innodb-force-recovery=1\" -WindowStyle Hidden",
    "respaldo_data_previo" => "Copy-Item -Path " . $mysqlData . " -Destination " . $backupDataDir . " -Recurse",
    "importar_respaldo" => $mysql . " -h 127.0.0.1 -u root < " . $respaldo
);

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "mysql_pos_recovery_preflight_readonly",
    "read_only" => true,
    "rutas" => array(
        "respaldo" => $respaldo,
        "mysql_data" => $mysqlData,
        "mysql_bin" => $mysqlBin,
        "mysqld" => $mysqld,
        "mysqladmin" => $mysqladmin,
        "mysql" => $mysql,
        "my_ini" => $myIni,
        "error_log" => $errorLog,
        "backup_data_dir_propuesto" => $backupDataDir
    ),
    "validacion" => array(
        "respaldo_existe" => is_file($respaldo),
        "respaldo_bytes" => is_file($respaldo) ? filesize($respaldo) : 0,
        "mysql_data_existe" => is_dir($mysqlData),
        "mysqld_existe" => is_file($mysqld),
        "mysql_existe" => is_file($mysql),
        "my_ini_existe" => is_file($myIni)
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "log_tail" => $logTail,
    "autorizacion_sugerida" => empty($bloqueos) ? $autorizacion : "",
    "comandos_propuestos_no_ejecutados" => $comandosPropuestos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_detiene_mysql" => true,
        "no_modifica_my_ini" => true,
        "no_mueve_data" => true,
        "no_importa_sql" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
