<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: diagnosticar salud local de MySQL/XAMPP sin reparar ni modificar archivos.
 * Impacto: permite decidir si se pueden ejecutar UAT reales de ERP/Listas de precios.
 * Contrato: read-only; no ejecuta DDL, no corre aria_chk --recover y no borra logs.
 */

$raiz = dirname(__DIR__, 2);
$log = "C:\\xampp\\mysql\\data\\mysql_error.log";
$dataDir = "C:\\xampp\\mysql\\data";
$mysqlAlive = false;
$pdoOk = false;
$pdoError = "";
$logContenido = is_readable($log) ? file_get_contents($log) : "";
$logTail = ultimasLineas($logContenido, 80);
$ariaLogs = glob($dataDir . DIRECTORY_SEPARATOR . "aria_log*") ?: array();

chdir($raiz . DIRECTORY_SEPARATOR . "public");
require_once "../app/config/mysql.php";

try {
    $pdo = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE . ";charset=utf8", MYSQLUSER, MYSQLPASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
    $stmt = $pdo->query("SELECT 1 ok");
    $fila = $stmt ? $stmt->fetch() : null;
    $pdoOk = isset($fila["ok"]) && intval($fila["ok"]) === 1;
    $mysqlAlive = $pdoOk;
} catch (Exception $e) {
    $pdoError = $e->getMessage();
}

$checks = array(
    checkMysql("log_legible", is_readable($log), "Log de MySQL legible"),
    checkMysql("pdo_conecta", $pdoOk, "Conexion PDO local responde SELECT 1"),
    checkMysql("aria_logs_detectados", count($ariaLogs) > 0, "Se detectan archivos Aria en data dir"),
    checkMysql("log_registra_aria_recovery", stripos($logTail, "Aria recovery failed") !== false, "El tail reciente conserva evidencia de fallo Aria"),
    checkMysql("log_registra_socket", stripos($logTail, "Server socket created") !== false, "El tail reciente muestra creacion de socket"),
    checkMysql("sin_reparacion_desde_script", true, "Este script no repara ni borra archivos")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"] && in_array($check["id"], array("log_legible", "pdo_conecta"), true)) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_MYSQL_XAMPP_HEALTH" : "FAIL_MYSQL_XAMPP_HEALTH",
    "conexion" => array(
        "mysql_alive" => $mysqlAlive,
        "pdo_ok" => $pdoOk,
        "pdo_error" => $pdoOk ? "" : $pdoError
    ),
    "archivos" => array(
        "log" => $log,
        "log_legible" => is_readable($log),
        "aria_logs" => array_map("basename", $ariaLogs)
    ),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "observaciones" => array(
        "Si vuelve a fallar Aria, preparar respaldo externo/copia de data antes de cualquier repair.",
        "No ejecutar aria_chk --recover ni borrar aria_log.* sin autorizacion explicita."
    ),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_repara_aria" => true,
        "no_borra_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkMysql($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function ultimasLineas($texto, $lineas) {
    $partes = preg_split('/\r\n|\r|\n/', (string) $texto);
    return implode("\n", array_slice($partes, -1 * intval($lineas)));
}
