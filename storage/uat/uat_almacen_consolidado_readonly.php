<?php

/**
 * IA: Codex GPT-5 | Fecha: 2026-11-02
 * Proposito: auditar el impacto de operar temporalmente con un solo almacen consolidado.
 * Impacto: solo lectura; no modifica almacenes, existencias, recepciones, ventas ni compras.
 * Contrato: identifica almacenes, existencias y referencias operativas por id_almacen antes de proponer migracion.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10
);

$db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, $options);

function tablaExisteConsolidado($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaExisteConsolidado($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function consultarConteoPorAlmacen($db, $tabla, $columna) {
    if (!tablaExisteConsolidado($db, $tabla) || !columnaExisteConsolidado($db, $tabla, $columna)) {
        return array("tabla" => $tabla, "disponible" => false, "filas" => 0, "por_almacen" => array());
    }
    $stmt = $db->query("SELECT " . $columna . " AS id_almacen, COUNT(*) AS filas FROM " . $tabla . " GROUP BY " . $columna . " ORDER BY filas DESC");
    $porAlmacen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $filas = 0;
    foreach ($porAlmacen as $grupo) {
        $filas += intval($grupo["filas"]);
    }
    return array("tabla" => $tabla, "disponible" => true, "filas" => $filas, "por_almacen" => $porAlmacen);
}

$almacenes = $db->query("SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, estatus, tipo_almacen,
    permite_recepcion, permite_venta, permite_preparacion, permite_ajustes
    FROM erp_almacenes ORDER BY COALESCE(estatus,'activo') DESC, id_almacen")->fetchAll(PDO::FETCH_ASSOC);

$existencias = array("tabla" => "erp_inventario_existencias", "disponible" => false, "filas" => 0, "por_almacen" => array());
if (tablaExisteConsolidado($db, "erp_inventario_existencias") && columnaExisteConsolidado($db, "erp_inventario_existencias", "id_almacen_clave")) {
    $sqlExistencias = "SELECT id_almacen_clave AS id_almacen, COUNT(*) AS filas,
        COALESCE(SUM(cantidad),0) AS cantidad, COALESCE(SUM(cantidad_disponible),0) AS cantidad_disponible
        FROM erp_inventario_existencias GROUP BY id_almacen_clave ORDER BY id_almacen_clave";
    $porAlmacen = $db->query($sqlExistencias)->fetchAll(PDO::FETCH_ASSOC);
    $filas = 0;
    foreach ($porAlmacen as $grupo) {
        $filas += intval($grupo["filas"]);
    }
    $existencias = array("tabla" => "erp_inventario_existencias", "disponible" => true, "filas" => $filas, "por_almacen" => $porAlmacen);
}

$resultado = array(
    "ok" => true,
    "modo" => "almacen_consolidado_readonly",
    "read_only" => true,
    "base_operativa" => MYSQLBASE,
    "almacenes" => $almacenes,
    "existencias" => $existencias,
    "referencias_por_almacen" => array(
        consultarConteoPorAlmacen($db, "erp_compras_ordenes", "id_almacen"),
        consultarConteoPorAlmacen($db, "erp_ventas", "id_almacen"),
        consultarConteoPorAlmacen($db, "erp_almacen_recepciones", "id_almacen"),
        consultarConteoPorAlmacen($db, "erp_inventario_movimientos", "id_almacen"),
        consultarConteoPorAlmacen($db, "erp_inventario_incidencias_operativas", "id_almacen")
    ),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_mueve_existencias" => true,
        "no_cambia_estatus" => true
    ),
    "siguiente_decision" => "Elegir almacen General y definir si la consolidacion sera virtual o mediante corte fisico autorizado."
);

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

