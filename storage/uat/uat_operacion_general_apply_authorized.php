<?php

/**
 * IA: Codex GPT-5 | Fecha: 2026-11-02
 * Proposito: crear el almacen principal OPERACION-GENERAL con autorizacion explicita.
 * Impacto: solo inserta un registro en erp_almacenes; no mueve existencias ni reubica documentos.
 * Contrato: exige token y confirmacion textual; genera respaldo externo de erp_almacenes antes de escribir.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

const TOKEN_OPERACION_GENERAL = "OPERACION_GENERAL_ALTA_APPLY";
const CONFIRMACION_OPERACION_GENERAL = "AUTORIZO CREAR Y CONFIGURAR OPERACION GENERAL PRODUCTIVA";

function argumentoOperacionGeneral($nombre, $default = "") {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ((array) $argv as $argumento) {
        if (strpos($argumento, $prefijo) === 0) {
            return substr($argumento, strlen($prefijo));
        }
    }
    return $default;
}

function salidaOperacionGeneral($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($datos["error"]) ? 1 : 0);
}

function respaldoAlmacenesOperacionGeneral($backupFile) {
    $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
    if (!is_file($mysqldump)) {
        return array("error" => true, "mensaje" => "mysqldump_no_encontrado");
    }
    $cmd = escapeshellarg($mysqldump)
        . " --host=" . escapeshellarg(MYSQLHOST)
        . " --port=" . escapeshellarg(MYSQLPORT)
        . " --user=" . escapeshellarg(MYSQLUSER)
        . " --password=" . escapeshellarg(MYSQLPASS)
        . " --single-transaction --skip-lock-tables "
        . escapeshellarg(MYSQLBASE) . " erp_almacenes";
    $descriptor = array(0 => array("pipe", "r"), 1 => array("file", $backupFile, "w"), 2 => array("pipe", "w"));
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
        "stderr" => $stderr,
        "archivo" => $backupFile,
        "bytes" => is_file($backupFile) ? filesize($backupFile) : 0
    );
}

$token = argumentoOperacionGeneral("token");
$confirmacion = argumentoOperacionGeneral("confirmacion");
if ($token !== TOKEN_OPERACION_GENERAL) {
    salidaOperacionGeneral(array("error" => true, "mensaje" => "Token de aplicación inválido"));
}
if (stripos($confirmacion, CONFIRMACION_OPERACION_GENERAL) === false) {
    salidaOperacionGeneral(array("error" => true, "mensaje" => "Confirmación textual inválida"));
}

$backupDir = "C:\\xampp\\panel_db_backups";
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
    salidaOperacionGeneral(array("error" => true, "mensaje" => "No se pudo crear directorio de respaldos"));
}
$backupFile = $backupDir . "\\productivo_panel_" . date("Ymd_His") . "_antes_operacion_general.sql";
$respaldo = respaldoAlmacenesOperacionGeneral($backupFile);
if (!empty($respaldo["error"])) {
    salidaOperacionGeneral(array("error" => true, "mensaje" => "No se aplicó: respaldo externo falló", "respaldo" => $respaldo));
}

$db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10
));

try {
    $db->beginTransaction();
    $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, estatus FROM erp_almacenes WHERE codigo_almacen=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => "OPERACION-GENERAL"));
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existente) {
        $db->rollBack();
        salidaOperacionGeneral(array("error" => false, "mensaje" => "El almacén central ya existía; no se duplicó", "almacen" => $existente, "respaldo" => $respaldo, "escritura" => false));
    }
    $stmt = $db->prepare("INSERT INTO erp_almacenes
        (codigo_almacen, almacen, nombre_comercial, estatus, tipo_almacen,
         permite_recepcion, permite_venta, permite_preparacion, permite_ajustes,
         es_tecnico, orden, observaciones, fecha_actualizacion)
        VALUES (:codigo, :almacen, :comercial, 'activo', 'principal',
         1, 1, 1, 1, 0, 1, :observaciones, NOW())");
    $stmt->execute(array(
        ":codigo" => "OPERACION-GENERAL",
        ":almacen" => "Operación General",
        ":comercial" => "Operación General",
        ":observaciones" => "Almacén operativo central temporal; conservar almacenes históricos"
    ));
    $id = intval($db->lastInsertId());
    $db->commit();
    $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, estatus,
            tipo_almacen, permite_recepcion, permite_venta, permite_preparacion, permite_ajustes
        FROM erp_almacenes WHERE id_almacen=:id");
    $stmt->execute(array(":id" => $id));
    salidaOperacionGeneral(array("error" => false, "mensaje" => "Almacén central creado", "almacen" => $stmt->fetch(PDO::FETCH_ASSOC), "respaldo" => $respaldo, "escritura" => true, "no_mueve_inventario" => true));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    salidaOperacionGeneral(array("error" => true, "mensaje" => "No se pudo crear el almacén central", "detalle" => $e->getMessage(), "respaldo" => $respaldo));
}
