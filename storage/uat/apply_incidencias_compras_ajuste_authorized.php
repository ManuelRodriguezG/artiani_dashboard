<?php

/**
 * Documentacion IA: Codex GPT-5
 * Fecha: 2026-10-16
 * Proposito: aplicar columnas de trazabilidad para ajustes desde incidencias de compras.
 * Impacto: DDL acotado en erp_inventario_incidencias_operativas; no modifica existencias.
 * Contrato: requiere ejecucion CLI y respaldo externo previo autorizado.
 */

if (PHP_SAPI !== "cli") {
    exit("Solo CLI\n");
}
chdir(__DIR__ . "/../../public");
$config = require "../app/config/mysql.local.php";
$pdo = new PDO(
    "mysql:host=" . $config["host"] . ";port=" . $config["port"] . ";dbname=" . $config["base"] . ";charset=utf8mb4",
    $config["usuario"],
    $config["password"],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$tabla = "erp_inventario_incidencias_operativas";
$columnas = array(
    "cantidad_existencia_anterior DECIMAL(18,6) NULL",
    "cantidad_existencia_resultado DECIMAL(18,6) NULL",
    "cantidad_ajuste DECIMAL(18,6) NULL",
    "tipo_ajuste VARCHAR(20) NULL",
    "movimientos_ids_json TEXT NULL",
    "ajustado_por INT NULL",
    "fecha_ajuste DATETIME NULL"
);
$resultados = array();
foreach ($columnas as $definicion) {
    $nombre = preg_replace('/\s+.*/', '', $definicion);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":base" => $config["base"], ":tabla" => $tabla, ":columna" => $nombre));
    if (intval($stmt->fetchColumn()) > 0) {
        $resultados[] = array("columna" => $nombre, "accion" => "existente");
        continue;
    }
    $pdo->exec("ALTER TABLE `" . $tabla . "` ADD COLUMN `" . $nombre . "` " . substr($definicion, strlen($nombre)));
    $resultados[] = array("columna" => $nombre, "accion" => "creada");
}
echo json_encode(array("ok" => true, "base" => $config["base"], "tabla" => $tabla, "resultados" => $resultados), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
