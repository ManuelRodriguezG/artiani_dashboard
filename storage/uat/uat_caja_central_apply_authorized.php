<?php

/**
 * IA: Codex GPT-5 | Fecha: 2026-11-03
 * Proposito: crear la unica caja central POS para OPERACION-GENERAL.
 * Impacto: inserta una caja POS; no crea terminal, no abre turno y no mueve inventario.
 * Contrato: exige token y confirmacion; respalda erp_pos_cajas antes de escribir.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

const TOKEN_CAJA_CENTRAL = "CAJA_CENTRAL_POS_ALTA_APPLY";
const CONFIRMACION_CAJA_CENTRAL = "AUTORIZO CREAR CAJA CENTRAL POS PRODUCTIVA";

function argumentoCajaCentral($nombre, $default = "") {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ((array) $argv as $argumento) {
        if (strpos($argumento, $prefijo) === 0) {
            return substr($argumento, strlen($prefijo));
        }
    }
    return $default;
}

function salidaCajaCentral($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($datos["error"]) ? 1 : 0);
}

function respaldoCajaCentral($backupFile) {
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
        . escapeshellarg(MYSQLBASE) . " erp_pos_cajas";
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

$token = argumentoCajaCentral("token");
$confirmacion = argumentoCajaCentral("confirmacion");
if ($token !== TOKEN_CAJA_CENTRAL) {
    salidaCajaCentral(array("error" => true, "mensaje" => "Token de aplicación inválido"));
}
if (stripos($confirmacion, CONFIRMACION_CAJA_CENTRAL) === false) {
    salidaCajaCentral(array("error" => true, "mensaje" => "Confirmación textual inválida"));
}

$db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10
));

$almacen = $db->prepare("SELECT id_almacen, codigo_almacen, estatus, permite_venta FROM erp_almacenes WHERE id_almacen=:id LIMIT 1");
$almacen->execute(array(":id" => 6));
$almacen = $almacen->fetch(PDO::FETCH_ASSOC);
if (!$almacen || $almacen["estatus"] !== "activo" || intval($almacen["permite_venta"]) !== 1) {
    salidaCajaCentral(array("error" => true, "mensaje" => "El almacén central no está activo o no permite venta"));
}

$backupDir = "C:\\xampp\\panel_db_backups";
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
    salidaCajaCentral(array("error" => true, "mensaje" => "No se pudo crear directorio de respaldos"));
}
$backupFile = $backupDir . "\\productivo_panel_" . date("Ymd_His") . "_antes_caja_central.sql";
$respaldo = respaldoCajaCentral($backupFile);
if (!empty($respaldo["error"])) {
    salidaCajaCentral(array("error" => true, "mensaje" => "No se aplicó: respaldo externo falló", "respaldo" => $respaldo));
}

try {
    $db->beginTransaction();
    $stmt = $db->prepare("SELECT id_caja, codigo, id_almacen, estatus FROM erp_pos_cajas WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => "CJ-OPERACION-GENERAL-01"));
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existente) {
        $db->rollBack();
        salidaCajaCentral(array("error" => false, "mensaje" => "La caja central ya existía; no se duplicó", "caja" => $existente, "respaldo" => $respaldo, "escritura" => false));
    }
    $stmt = $db->prepare("INSERT INTO erp_pos_cajas
        (codigo, nombre, id_almacen, estatus, permite_efectivo, permite_tarjeta, permite_transferencia,
         observaciones, fecha_registro, fecha_actualizacion)
        VALUES (:codigo, :nombre, :almacen, 'activa', 1, 1, 1, :observaciones, NOW(), NOW())");
    $stmt->execute(array(
        ":codigo" => "CJ-OPERACION-GENERAL-01",
        ":nombre" => "Caja central Operacion General",
        ":almacen" => 6,
        ":observaciones" => "Caja central temporal del almacen OPERACION-GENERAL"
    ));
    $id = intval($db->lastInsertId());
    $db->commit();
    $stmt = $db->prepare("SELECT id_caja, codigo, nombre, id_almacen, estatus, permite_efectivo, permite_tarjeta, permite_transferencia FROM erp_pos_cajas WHERE id_caja=:id");
    $stmt->execute(array(":id" => $id));
    salidaCajaCentral(array("error" => false, "mensaje" => "Caja central creada", "caja" => $stmt->fetch(PDO::FETCH_ASSOC), "respaldo" => $respaldo, "escritura" => true, "no_crea_terminal" => true, "no_abre_turno" => true));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    salidaCajaCentral(array("error" => true, "mensaje" => "No se pudo crear la caja central", "detalle" => $e->getMessage(), "respaldo" => $respaldo));
}
