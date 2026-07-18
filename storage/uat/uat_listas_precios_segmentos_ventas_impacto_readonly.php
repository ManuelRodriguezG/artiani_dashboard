<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: generar linea base read-only de impacto en ventas antes/despues de activar listas por segmento.
 * Impacto: permite confirmar que los applies de segmentos no modifican ventas pasadas ni snapshots POS.
 * Contrato: no escribe BD, no ejecuta DDL, no modifica ventas, listas, clientes ni segmentos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class LpSegmentosVentasImpactoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosVentasImpactoReadonlyDb())->db();
$tablas = array("erp_ventas", "erp_ventas_detalle");
$bloqueos = array();
$avisos = array();

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}

$schema = array();
foreach ($tablas as $tabla) {
    $schema[$tabla] = array(
        "existe" => $db ? tablaVentasImpactoExiste($db, $tabla) : false,
        "columnas" => array()
    );
}

$columnasRequeridas = array(
    "erp_ventas" => array("id_venta", "folio", "fecha_venta", "id_cliente_crm", "id_lista_precio", "total", "estatus"),
    "erp_ventas_detalle" => array("id_venta_detalle", "id_venta", "id_sku_erp", "precio_base", "precio_aplicado", "id_lista_precio", "lista_precio_snapshot", "regla_precio_origen", "total", "estatus")
);

foreach ($columnasRequeridas as $tabla => $columnas) {
    foreach ($columnas as $columna) {
        $schema[$tabla]["columnas"][$columna] = $db && $schema[$tabla]["existe"] ? columnaVentasImpactoExiste($db, $tabla, $columna) : false;
    }
}

$resumenVentas = $db && $schema["erp_ventas"]["existe"] ? resumenTablaVentas($db, "erp_ventas", "id_venta", "fecha_venta") : null;
$resumenDetalle = $db && $schema["erp_ventas_detalle"]["existe"] ? resumenTablaVentas($db, "erp_ventas_detalle", "id_venta_detalle", null) : null;
$resumenSnapshots = $db && $schema["erp_ventas_detalle"]["existe"] ? resumenSnapshotsVentas($db, $schema["erp_ventas_detalle"]["columnas"]) : array();
$ultimasVentas = $db && $schema["erp_ventas"]["existe"] ? ultimasVentasImpacto($db) : array();

foreach ($schema as $tabla => $info) {
    if (!$info["existe"]) {
        $bloqueos[] = "tabla_no_existe_" . $tabla;
    }
}
foreach ($columnasRequeridas["erp_ventas_detalle"] as $columna) {
    if (in_array($columna, array("id_lista_precio", "lista_precio_snapshot", "regla_precio_origen"), true) && empty($schema["erp_ventas_detalle"]["columnas"][$columna])) {
        $bloqueos[] = "columna_snapshot_pendiente_" . $columna;
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "proposito" => "baseline_ventas_antes_despues_segmentos",
    "schema" => $schema,
    "baseline" => array(
        "erp_ventas" => $resumenVentas,
        "erp_ventas_detalle" => $resumenDetalle,
        "snapshots_detalle" => $resumenSnapshots,
        "ultimas_ventas" => $ultimasVentas
    ),
    "criterio_post_apply" => array(
        "conteo_erp_ventas_no_debe_cambiar_por_apply_segmentos" => true,
        "conteo_erp_ventas_detalle_no_debe_cambiar_por_apply_segmentos" => true,
        "max_id_venta_no_debe_cambiar_por_apply_segmentos" => true,
        "max_id_venta_detalle_no_debe_cambiar_por_apply_segmentos" => true,
        "snapshots_existentes_no_deben_actualizarse" => true
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_ventas" => true,
        "no_modifica_listas" => true,
        "no_modifica_clientes" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function resumenTablaVentas($db, $tabla, $idColumna, $fechaColumna) {
    $tablaSegura = "`" . str_replace("`", "``", $tabla) . "`";
    $idSeguro = "`" . str_replace("`", "``", $idColumna) . "`";
    $selectFecha = $fechaColumna && columnaVentasImpactoExiste($db, $tabla, $fechaColumna)
        ? ", MIN(`" . str_replace("`", "``", $fechaColumna) . "`) fecha_min, MAX(`" . str_replace("`", "``", $fechaColumna) . "`) fecha_max"
        : ", NULL fecha_min, NULL fecha_max";
    $stmt = $db->query("SELECT COUNT(*) total, MIN($idSeguro) min_id, MAX($idSeguro) max_id $selectFecha FROM $tablaSegura");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function resumenSnapshotsVentas($db, $columnas) {
    if (empty($columnas["id_lista_precio"]) || empty($columnas["lista_precio_snapshot"]) || empty($columnas["regla_precio_origen"])) {
        return array("schema_snapshot_incompleto" => true);
    }
    $stmt = $db->query("SELECT
            COUNT(*) total_detalles,
            SUM(CASE WHEN id_lista_precio IS NOT NULL THEN 1 ELSE 0 END) detalles_con_lista,
            SUM(CASE WHEN lista_precio_snapshot IS NOT NULL AND lista_precio_snapshot<>'' THEN 1 ELSE 0 END) detalles_con_snapshot,
            SUM(CASE WHEN regla_precio_origen IS NOT NULL AND regla_precio_origen<>'' THEN 1 ELSE 0 END) detalles_con_origen
        FROM erp_ventas_detalle");
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT COALESCE(regla_precio_origen, 'sin_origen') origen, COUNT(*) total
        FROM erp_ventas_detalle
        GROUP BY COALESCE(regla_precio_origen, 'sin_origen')
        ORDER BY total DESC, origen ASC
        LIMIT 20");
    $resumen["origenes"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $resumen;
}

function ultimasVentasImpacto($db) {
    if (!tablaVentasImpactoExiste($db, "erp_ventas") || !tablaVentasImpactoExiste($db, "erp_ventas_detalle")) {
        return array();
    }
    $stmt = $db->query("SELECT v.id_venta, v.folio, v.fecha_venta, v.estatus, v.total,
            COUNT(d.id_venta_detalle) partidas,
            SUM(CASE WHEN d.id_lista_precio IS NOT NULL THEN 1 ELSE 0 END) partidas_con_lista,
            MAX(d.regla_precio_origen) regla_muestra
        FROM erp_ventas v
        LEFT JOIN erp_ventas_detalle d ON d.id_venta=v.id_venta
        GROUP BY v.id_venta, v.folio, v.fecha_venta, v.estatus, v.total
        ORDER BY v.id_venta DESC
        LIMIT 10");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tablaVentasImpactoExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaVentasImpactoExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}
