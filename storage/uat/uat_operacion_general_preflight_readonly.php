<?php

/**
 * IA: Codex GPT-5 | Fecha: 2026-11-02
 * Proposito: validar si existe la operación central y qué objetos POS/Compras requieren configuración.
 * Impacto: solo lectura; no crea almacenes, cajas, terminales, turnos ni documentos.
 * Contrato: devuelve JSON con el preflight para autorizar una configuración productiva controlada.
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

function tablaExisteOperacionGeneral($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnasOperacionGeneral($db, $tabla, $columnas) {
    if (!tablaExisteOperacionGeneral($db, $tabla)) {
        return array("tabla" => $tabla, "disponible" => false, "columnas" => array());
    }
    $resultado = array();
    foreach ($columnas as $columna) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
        $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
        $resultado[$columna] = intval($stmt->fetchColumn()) > 0;
    }
    return array("tabla" => $tabla, "disponible" => true, "columnas" => $resultado);
}

function consultarFilasOperacionGeneral($db, $sql, $parametros = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$almacen = consultarFilasOperacionGeneral($db, "SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, estatus,
        tipo_almacen, permite_recepcion, permite_venta, permite_preparacion, permite_ajustes
    FROM erp_almacenes WHERE codigo_almacen=:codigo LIMIT 1", array(":codigo" => "OPERACION-GENERAL"));

$idAlmacen = !empty($almacen) ? intval($almacen[0]["id_almacen"]) : 0;
$resultado = array(
    "ok" => true,
    "modo" => "operacion_general_preflight_readonly",
    "read_only" => true,
    "base_operativa" => MYSQLBASE,
    "codigo_propuesto" => "OPERACION-GENERAL",
    "almacen_existente" => $almacen,
    "esquema_requerido" => array(
        columnasOperacionGeneral($db, "erp_almacenes", array("codigo_almacen", "tipo_almacen", "permite_recepcion", "permite_venta", "permite_preparacion", "permite_ajustes")),
        columnasOperacionGeneral($db, "erp_pos_cajas", array("id_caja", "id_almacen", "estatus")),
        columnasOperacionGeneral($db, "erp_pos_terminales", array("id_terminal_pos", "id_almacen", "id_caja", "estatus")),
        columnasOperacionGeneral($db, "erp_pos_turnos", array("id_turno_caja", "id_almacen", "id_caja", "estatus"))
    ),
    "cajas_configuradas" => array(),
    "terminales_configuradas" => array(),
    "turnos_abiertos" => array(),
    "documentos_sin_destino" => array(),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_cambia_configuracion" => true,
        "no_cierra_turnos" => true
    ),
    "siguiente_paso" => "Autorizar alta/configuración del almacén OPERACION-GENERAL y vinculación controlada de POS, Compras y Recepción"
);

if (tablaExisteOperacionGeneral($db, "erp_pos_cajas")) {
    $resultado["cajas_configuradas"] = consultarFilasOperacionGeneral($db, "SELECT id_caja, codigo, nombre, id_almacen, estatus FROM erp_pos_cajas ORDER BY id_caja");
}
if (tablaExisteOperacionGeneral($db, "erp_pos_terminales")) {
    $resultado["terminales_configuradas"] = consultarFilasOperacionGeneral($db, "SELECT id_terminal_pos, codigo, nombre, id_almacen, id_caja, estatus FROM erp_pos_terminales ORDER BY id_terminal_pos");
}
if ($idAlmacen > 0) {
    if (tablaExisteOperacionGeneral($db, "erp_pos_turnos")) {
        $resultado["turnos_abiertos"] = consultarFilasOperacionGeneral($db, "SELECT id_turno_caja, folio, id_almacen, id_caja, id_usuario_apertura, fecha_apertura FROM erp_pos_turnos WHERE id_almacen=:almacen AND estatus='abierto' ORDER BY id_turno_caja DESC", array(":almacen" => $idAlmacen));
    }
}

if (tablaExisteOperacionGeneral($db, "erp_compras_solicitudes")) {
    $resultado["documentos_sin_destino"]["solicitudes"] = intval($db->query("SELECT COUNT(*) FROM erp_compras_solicitudes WHERE (id_almacen_destino IS NULL OR id_almacen_destino=0) AND COALESCE(estatus,'borrador') NOT IN ('cancelada')")->fetchColumn());
}
if (tablaExisteOperacionGeneral($db, "erp_compras_ordenes")) {
    $resultado["documentos_sin_destino"]["ordenes"] = intval($db->query("SELECT COUNT(*) FROM erp_compras_ordenes WHERE (id_almacen_destino IS NULL OR id_almacen_destino=0) AND COALESCE(estatus,'borrador') NOT IN ('cancelada')")->fetchColumn());
}

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
