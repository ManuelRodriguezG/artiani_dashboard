<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: consultar la evidencia reciente de ventas POS con inventario pendiente/mixto sin escribir BD.
 * Impacto: solo lectura; ayuda a confirmar si una UAT fallo antes o despues de persistir datos.
 * Contrato: no modifica ventas, caja, inventario, pendientes ni notificaciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpPendienteMixtoLatestUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpPendienteMixtoLatestUat();
$db = $ventas->conexionUat();

$respuesta = array(
    "ok" => true,
    "modo" => "pos_inventario_pendiente_mixto_latest_readonly",
    "read_only" => true,
    "ventas_pos_recientes" => consultar($db, "SELECT id_venta, folio, total, pagado_total, inventario_validacion_estado, estatus, id_turno_caja
        FROM erp_ventas
        ORDER BY id_venta DESC
        LIMIT 12"),
    "pendientes_recientes" => consultar($db, "SELECT id_inventario_pendiente, folio, id_venta, id_venta_detalle, id_almacen, id_sku_erp,
            cantidad_vendida, cantidad_cubierta, cantidad_pendiente, estatus, id_notificacion, fecha_registro
        FROM erp_pos_inventario_pendientes
        ORDER BY id_inventario_pendiente DESC
        LIMIT 8"),
    "movimientos_pos_hoy" => consultar($db, "SELECT id_movimiento_inventario, referencia, tipo_movimiento, origen_tipo, origen_id,
            id_existencia_inventario, cantidad, existencia_anterior, existencia_nueva
        FROM erp_inventario_movimientos
        WHERE referencia LIKE :folio
        ORDER BY id_movimiento_inventario DESC
        LIMIT 12", array(":folio" => "POS-" . date("Ymd") . "-%")),
    "turnos_abiertos" => consultar($db, "SELECT id_turno_caja, folio, id_caja, id_almacen, estatus, monto_inicial, monto_esperado, fecha_apertura
        FROM erp_pos_turnos
        WHERE estatus='abierto'
        ORDER BY id_turno_caja DESC
        LIMIT 5"),
    "movimientos_caja_turno_22" => consultar($db, "SELECT mc.id_movimiento_caja, mc.id_turno_caja, mc.tipo, mc.categoria, mc.motivo,
            mc.monto, mc.estatus, mc.id_venta, mc.referencia, v.folio folio_venta, v.estatus estatus_venta
        FROM erp_pos_movimientos_caja mc
        LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
        WHERE mc.id_turno_caja=22
        ORDER BY mc.id_movimiento_caja ASC"),
    "existencia_sku_1760_a5" => consultar($db, "SELECT id_existencia_inventario, codigo_existencia, id_almacen_clave, id_sku_erp,
            cantidad, cantidad_disponible, cantidad_apartada, estatus_existencia, ultimo_movimiento_id
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=5 AND id_sku_erp=1760
        ORDER BY id_existencia_inventario DESC
        LIMIT 5")
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function consultar($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
