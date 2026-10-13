<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-11.
 * Proposito: diagnosticar por que una venta POS no genero alerta de inventario.
 * Impacto: solo lectura sobre ventas, cajas, pendientes y notificaciones POS.
 * Contrato: no escribe BD, no modifica caja, no mueve inventario, no resuelve alertas.
 */

chdir(__DIR__ . "/../..");
require_once "app/iniciador.php";

$salida = array(
    "ok" => true,
    "modo" => "diagnostico_alertas_pos_readonly",
    "base" => defined("MYSQLBASE") ? MYSQLBASE : null,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_resuelve_alertas" => true
    )
);

try {
    $pdo = new PDO(
        "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE,
        MYSQLUSER,
        MYSQLPASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $salida["cajas"] = consultar($pdo, "SELECT id_caja, codigo, nombre, id_almacen, estatus,
            afectar_inventario, modo_operacion_inventario, generar_alertas_inventario
        FROM erp_pos_cajas
        ORDER BY id_caja");

    $salida["turnos_recientes"] = consultar($pdo, "SELECT id_turno_caja, folio, id_caja, id_almacen,
            id_usuario_apertura, estatus, monto_inicial, monto_esperado, monto_contado,
            fecha_apertura, fecha_cierre
        FROM erp_pos_turnos
        ORDER BY id_turno_caja DESC
        LIMIT 10");

    $salida["ventas_recientes"] = consultar($pdo, "SELECT id_venta, folio, fecha_venta, estatus,
            id_caja, id_turno_caja, total, pagado_total, origen_venta, modo_operacion,
            afecta_inventario, afecta_caja, inventario_validacion_estado, inventario_pendiente_total
        FROM erp_ventas
        ORDER BY id_venta DESC
        LIMIT 12");

    $salida["detalles_recientes"] = consultar($pdo, "SELECT d.id_venta_detalle, d.id_venta, v.folio,
            d.sku, d.descripcion, d.tipo_partida, d.origen_partida, d.modo_salida,
            d.inventario_estado, d.controla_inventario, d.permite_inventario_pendiente,
            d.cantidad_venta, d.cantidad_base, d.cantidad_inventario_pendiente,
            d.id_inventario_pendiente, d.id_almacen, d.total
        FROM erp_ventas_detalle d
        INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
        ORDER BY d.id_venta_detalle DESC
        LIMIT 30");

    $salida["pendientes_inventario"] = consultar($pdo, "SELECT id_inventario_pendiente, folio,
            id_venta, id_venta_detalle, id_almacen, id_sku_erp, sku, descripcion,
            cantidad_vendida, cantidad_cubierta, cantidad_pendiente, estatus,
            fecha_registro, id_notificacion
        FROM erp_pos_inventario_pendientes
        ORDER BY id_inventario_pendiente DESC
        LIMIT 20");

    $salida["venta_rapida_pendientes"] = consultar($pdo, "SELECT id_venta_rapida_pendiente, folio,
            id_venta, id_venta_detalle, descripcion_manual, cantidad, total, estatus,
            fecha_registro, id_sku_erp_resuelto
        FROM erp_pos_venta_rapida_pendientes
        ORDER BY id_venta_rapida_pendiente DESC
        LIMIT 20");

    $salida["notificaciones_recientes"] = consultar($pdo, "SELECT id_notificacion, tipo,
            modulo_origen, entidad_origen, id_entidad_origen, area_responsable,
            permiso_requerido, titulo, estatus, fecha_registro, url_accion
        FROM erp_notificaciones
        ORDER BY id_notificacion DESC
        LIMIT 30");
} catch (Exception $e) {
    $salida["ok"] = false;
    $salida["error"] = $e->getMessage();
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function consultar($pdo, $sql) {
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
