<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

class UatRentabilidadTp40352Costos extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatAll($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uatRoundRows($rows) {
    foreach ($rows as &$row) {
        foreach ($row as $key => $value) {
            if (is_numeric($value) && preg_match('/(cantidad|costo|precio|valor|factor|unidad|total|equivalente|disponible|apartada|existencia|merma)/i', $key)) {
                $row[$key] = round(floatval($value), 6);
            }
        }
    }
    return $rows;
}

$modelo = new UatRentabilidadTp40352Costos();
$db = $modelo->db();
$skus = array("TP-40352", "TP-40352-500GR");
$placeholders = implode(",", array_fill(0, count($skus), "?"));

$catalogo = uatAll($db, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp,
        s.id_unidad_base, s.factor_unidad_base, s.costo_referencia, s.estatus
    FROM erp_catalogo_skus s
    WHERE s.sku IN ($placeholders)
    ORDER BY FIELD(s.sku, " . implode(",", array_fill(0, count($skus), "?")) . ")", array_merge($skus, $skus));

$transformaciones = uatAll($db, "SELECT ori.sku sku_origen, res.sku sku_resultado,
        tr.cantidad_origen, tr.unidades_resultado, tr.tipo_transformacion,
        tr.modo_disponibilidad, tr.merma_porcentaje, tr.estatus
    FROM erp_catalogo_sku_transformaciones tr
    INNER JOIN erp_catalogo_skus ori ON ori.id_sku=tr.id_sku_origen
    INNER JOIN erp_catalogo_skus res ON res.id_sku=tr.id_sku_resultado
    WHERE ori.sku IN ($placeholders) OR res.sku IN ($placeholders)
    ORDER BY ori.sku, res.sku", array_merge($skus, $skus));

$existencias = uatAll($db, "SELECT ex.id_existencia_inventario, ex.codigo_existencia, s.sku,
        ex.id_almacen_clave, ex.lote, ex.cantidad, ex.cantidad_disponible,
        ex.costo_promedio, ROUND(ex.cantidad * ex.costo_promedio, 6) valor_existencia,
        ex.estatus_existencia
    FROM erp_inventario_existencias ex
    INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
    WHERE s.sku IN ($placeholders)
    ORDER BY s.sku, ex.id_existencia_inventario", $skus);

$movimientos = uatAll($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento,
        m.origen_tipo, m.origen_id, m.origen_detalle_id, m.id_existencia_inventario,
        m.cantidad, m.costo_unitario, m.costo_total, m.existencia_anterior, m.existencia_nueva,
        m.referencia, m.fecha_registro
    FROM erp_inventario_movimientos m
    INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
    WHERE s.sku IN ($placeholders)
    ORDER BY m.id_movimiento_inventario", $skus);

$rentabilidadModelo = new RentabilidadErp();
$rentabilidad = $rentabilidadModelo->analizarSkus(array("q" => "TP-40352", "canal" => "menudeo"));
$presentaciones = $rentabilidadModelo->auditarCostosPresentaciones(array("q" => "TP-40352", "limite" => 20));

echo json_encode(array(
    "ok" => true,
    "sku_auditado" => "TP-40352",
    "catalogo" => uatRoundRows($catalogo),
    "transformaciones" => uatRoundRows($transformaciones),
    "existencias" => uatRoundRows($existencias),
    "movimientos" => uatRoundRows($movimientos),
    "rentabilidad" => isset($rentabilidad["depurar"]["items"]) ? $rentabilidad["depurar"]["items"] : $rentabilidad,
    "auditoria_presentaciones" => isset($presentaciones["depurar"]) ? $presentaciones["depurar"] : $presentaciones
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
