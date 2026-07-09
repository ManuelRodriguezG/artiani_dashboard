<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatRentabilidadTp40372Costos extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatFetchAll($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uatFetchOne($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uatTableExists($db, $table) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $table));
    return (bool) $stmt->fetch(PDO::FETCH_NUM);
}

function uatRoundRows($rows) {
    foreach ($rows as &$row) {
        foreach ($row as $key => $value) {
            if (is_numeric($value) && preg_match('/(cantidad|costo|precio|valor|factor|unidad|total|equivalente|disponible|apartada|existencia)/i', $key)) {
                $row[$key] = round(floatval($value), 6);
            }
        }
    }
    return $rows;
}

$modelo = new UatRentabilidadTp40372Costos();
$db = $modelo->db();
$skus = array("TP-40372", "TP-40372-500GR", "TP-40372-100GR", "TP-40372-50GR", "TP-40372-25GR");
$placeholders = implode(",", array_fill(0, count($skus), "?"));

$catalogo = uatFetchAll($db, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp,
        s.id_unidad_base, s.factor_unidad_base, s.costo_referencia, s.estatus
    FROM erp_catalogo_skus s
    WHERE s.sku IN ($placeholders)
    ORDER BY FIELD(s.sku, " . implode(",", array_fill(0, count($skus), "?")) . ")", array_merge($skus, $skus));

$presentaciones = uatTableExists($db, "erp_catalogo_sku_presentaciones")
    ? uatFetchAll($db, "SELECT base.sku sku_base, pres.sku sku_presentacion,
            pr.factor_salida_base, pr.consume_stock_base_en, pr.modo_disponibilidad,
            pr.requiere_empaque, pr.merma_porcentaje, pr.estatus
        FROM erp_catalogo_sku_presentaciones pr
        INNER JOIN erp_catalogo_skus base ON base.id_sku=pr.id_sku_base
        INNER JOIN erp_catalogo_skus pres ON pres.id_sku=pr.id_sku_presentacion
        WHERE base.sku IN ($placeholders) OR pres.sku IN ($placeholders)
        ORDER BY base.sku, pr.factor_salida_base", array_merge($skus, $skus))
    : array();

$transformaciones = uatTableExists($db, "erp_catalogo_sku_transformaciones")
    ? uatFetchAll($db, "SELECT ori.sku sku_origen, res.sku sku_resultado,
            tr.cantidad_origen, tr.unidades_resultado, tr.tipo_transformacion,
            tr.modo_disponibilidad, tr.merma_porcentaje, tr.estatus
        FROM erp_catalogo_sku_transformaciones tr
        INNER JOIN erp_catalogo_skus ori ON ori.id_sku=tr.id_sku_origen
        INNER JOIN erp_catalogo_skus res ON res.id_sku=tr.id_sku_resultado
        WHERE ori.sku IN ($placeholders) OR res.sku IN ($placeholders)
        ORDER BY ori.sku, res.sku", array_merge($skus, $skus))
    : array();

$existencias = uatFetchAll($db, "SELECT ex.id_existencia_inventario, ex.codigo_existencia, s.sku,
        ex.id_almacen_clave, ex.lote, ex.cantidad, ex.cantidad_disponible,
        ex.costo_promedio, ROUND(ex.cantidad * ex.costo_promedio, 6) valor_existencia,
        ex.estatus_existencia
    FROM erp_inventario_existencias ex
    INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
    WHERE s.sku IN ($placeholders)
    ORDER BY s.sku, ex.id_existencia_inventario", $skus);

$movimientos = uatFetchAll($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento,
        m.origen_tipo, m.origen_id, m.origen_detalle_id, m.id_existencia_inventario,
        m.cantidad, m.costo_unitario, m.costo_total, m.existencia_anterior, m.existencia_nueva,
        m.referencia, m.fecha_registro
    FROM erp_inventario_movimientos m
    INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
    WHERE s.sku IN ($placeholders)
      AND (m.referencia LIKE 'PREP-%' OR m.origen_tipo IN ('preparacion_presentacion','recepcion_compra'))
    ORDER BY m.id_movimiento_inventario", $skus);

$preparaciones = uatTableExists($db, "erp_almacen_preparaciones")
    ? uatFetchAll($db, "SELECT p.id_preparacion_almacen, p.folio, ori.sku sku_origen,
            res.sku sku_resultado, p.unidades_preparadas, p.cantidad_base_consumida,
            p.estatus, p.fecha_preparacion, p.observaciones
        FROM erp_almacen_preparaciones p
        INNER JOIN erp_catalogo_skus ori ON ori.id_sku=p.id_sku_base
        INNER JOIN erp_catalogo_skus res ON res.id_sku=p.id_sku_presentacion
        WHERE ori.sku IN ($placeholders) OR res.sku IN ($placeholders)
        ORDER BY p.id_preparacion_almacen", array_merge($skus, $skus))
    : array();

$consumos = uatTableExists($db, "erp_almacen_preparacion_consumos")
    ? uatFetchAll($db, "SELECT p.folio, s.sku sku_consumido, c.id_existencia_inventario,
            c.cantidad_consumida, c.costo_unitario, c.costo_total
        FROM erp_almacen_preparacion_consumos c
        INNER JOIN erp_almacen_preparaciones p ON p.id_preparacion_almacen=c.id_preparacion_almacen
        INNER JOIN erp_catalogo_skus s ON s.id_sku=c.id_sku_base
        WHERE s.sku IN ($placeholders)
        ORDER BY c.id_preparacion_consumo", $skus)
    : array();

$resultados = uatTableExists($db, "erp_almacen_preparacion_resultados")
    ? uatFetchAll($db, "SELECT p.folio, s.sku sku_resultado, r.id_existencia_inventario,
            r.unidades_preparadas, r.factor_salida_base, r.cantidad_base_equivalente,
            r.costo_unitario, r.costo_total
        FROM erp_almacen_preparacion_resultados r
        INNER JOIN erp_almacen_preparaciones p ON p.id_preparacion_almacen=r.id_preparacion_almacen
        INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku_presentacion
        WHERE s.sku IN ($placeholders)
        ORDER BY r.id_preparacion_resultado", $skus)
    : array();

$rentabilidad = null;
if (file_exists(__DIR__ . "/../../app/modelos/RentabilidadErp.php")) {
    require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";
    $rentabilidadModelo = new RentabilidadErp();
    $rentabilidad = $rentabilidadModelo->analizarSkus(array("q" => "TP-40372", "canal" => "menudeo"));
}

echo json_encode(array(
    "ok" => true,
    "sku_auditado" => "TP-40372",
    "catalogo" => uatRoundRows($catalogo),
    "presentaciones" => uatRoundRows($presentaciones),
    "transformaciones" => uatRoundRows($transformaciones),
    "existencias" => uatRoundRows($existencias),
    "movimientos" => uatRoundRows($movimientos),
    "preparaciones" => uatRoundRows($preparaciones),
    "preparacion_consumos" => uatRoundRows($consumos),
    "preparacion_resultados" => uatRoundRows($resultados),
    "rentabilidad" => isset($rentabilidad["depurar"]["items"]) ? $rentabilidad["depurar"]["items"] : $rentabilidad
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
