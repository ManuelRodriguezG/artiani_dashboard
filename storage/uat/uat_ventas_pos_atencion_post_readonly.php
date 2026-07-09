<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: inspeccionar atencion POS UAT creada sin modificar BD.
 * Impacto: valida bandeja, detalle y conteos posteriores a una atencion persistente.
 * Contrato: read-only; no crea ventas, pagos, reservas ni movimientos.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 5;
$idAtencion = 0;
foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosAtencionPostDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosAtencionPostDb())->db();
$ventas = new VentasErp();

$where = $idAtencion > 0 ? "WHERE id_atencion_pos=:id" : "WHERE id_almacen=:almacen";
$stmt = $db->prepare("SELECT id_atencion_pos, folio_temporal, id_almacen, id_caja, id_turno_caja,
        id_usuario, id_cliente, cliente_nombre_publico, estatus, origen, subtotal, total,
        pagos_temporales_total, fecha_apertura
    FROM erp_pos_atenciones
    $where
    ORDER BY id_atencion_pos DESC
    LIMIT 10");
$stmt->execute($idAtencion > 0 ? array(":id" => $idAtencion) : array(":almacen" => $idAlmacen));
$atenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtDetalle = $db->prepare("SELECT id_atencion_detalle, id_atencion_pos, renglon, id_sku_erp,
        sku, descripcion, cantidad_venta, precio_unitario, subtotal, total, estatus
    FROM erp_pos_atenciones_detalle
    WHERE (:id=0 OR id_atencion_pos=:id_filtro)
    ORDER BY id_atencion_detalle DESC
    LIMIT 20");
$stmtDetalle->execute(array(":id" => $idAtencion, ":id_filtro" => $idAtencion));

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "id_almacen" => $idAlmacen,
    "id_atencion" => $idAtencion,
    "atenciones" => $atenciones,
    "detalles" => $stmtDetalle->fetchAll(PDO::FETCH_ASSOC),
    "bandeja" => $ventas->atencionesBandejaDryRun(array("id_almacen" => $idAlmacen)),
    "conteos" => array(
        "ventas" => contar($db, "erp_ventas"),
        "pagos" => contar($db, "erp_ventas_pagos"),
        "movimientos_caja" => contar($db, "erp_pos_movimientos_caja"),
        "pagos_temporales_atencion" => contar($db, "erp_pos_atenciones_pagos_temporales")
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
}
