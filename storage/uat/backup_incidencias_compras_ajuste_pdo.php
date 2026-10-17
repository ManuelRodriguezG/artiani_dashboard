<?php

/**
 * Documentacion IA: Codex GPT-5
 * Fecha: 2026-10-16
 * Proposito: respaldo focalizado previo al ajuste real de incidencias de compras.
 * Impacto: solo lee tablas ERP y escribe un SQL fuera del proyecto.
 * Contrato: no modifica la base de datos ni imprime credenciales.
 */

if (PHP_SAPI !== "cli") {
    exit("Solo CLI\n");
}

$config = require __DIR__ . "/../../app/config/mysql.local.php";
$archivo = isset($argv[1]) ? trim($argv[1]) : "";
$directorioPermitido = "C:\\xampp\\panel_db_backups\\";
if ($archivo === "" || stripos($archivo, $directorioPermitido) !== 0) {
    exit("Ruta de respaldo invalida\n");
}

$tablas = array(
    "erp_inventario_incidencias_operativas",
    "erp_inventario_existencias",
    "erp_inventario_movimientos",
    "erp_inventario_unidades",
    "erp_catalogo_sku_reglas_inventario"
);

$pdo = new PDO(
    "mysql:host=" . $config["host"] . ";port=" . $config["port"] . ";dbname=" . $config["base"] . ";charset=utf8mb4",
    $config["usuario"],
    $config["password"],
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
);

$salida = array("SET FOREIGN_KEY_CHECKS=0;", "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';");
foreach ($tablas as $tabla) {
    $existe = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=:base AND table_name=:tabla");
    $existe->execute(array(":base" => $config["base"], ":tabla" => $tabla));
    if (intval($existe->fetchColumn()) !== 1) {
        continue;
    }
    $create = $pdo->query("SHOW CREATE TABLE `" . $tabla . "`")->fetch();
    $sentencia = isset($create["Create Table"]) ? $create["Create Table"] : "";
    if ($sentencia !== "") {
        $salida[] = "DROP TABLE IF EXISTS `" . $tabla . "`;";
        $salida[] = $sentencia . ";";
    }
    $filas = $pdo->query("SELECT * FROM `" . $tabla . "`")->fetchAll();
    foreach ($filas as $fila) {
        $columnas = array();
        $valores = array();
        foreach ($fila as $columna => $valor) {
            $columnas[] = "`" . str_replace("`", "``", $columna) . "`";
            $valores[] = $valor === null ? "NULL" : $pdo->quote((string) $valor);
        }
        $salida[] = "INSERT INTO `" . $tabla . "` (" . implode(",", $columnas) . ") VALUES (" . implode(",", $valores) . ");";
    }
}
$salida[] = "SET FOREIGN_KEY_CHECKS=1;";
file_put_contents($archivo, implode("\n", $salida) . "\n");
$tamano = filesize($archivo);
if ($tamano <= 0) {
    exit("Respaldo vacio\n");
}
echo json_encode(array("ok" => true, "archivo" => $archivo, "bytes" => $tamano), JSON_UNESCAPED_SLASHES) . PHP_EOL;
