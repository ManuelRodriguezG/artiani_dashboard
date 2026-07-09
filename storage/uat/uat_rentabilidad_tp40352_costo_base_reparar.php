<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

class UatRentabilidadTp40352CostoBaseReparar extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatOneTp40352($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uatAllTp40352($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uatRoundTp40352($value) {
    return round(floatval($value), 6);
}

$execute = in_array("--execute", $argv, true);
$modelo = new UatRentabilidadTp40352CostoBaseReparar();
$db = $modelo->db();

try {
    $base = uatOneTp40352($db, "SELECT id_sku, sku, costo_referencia, factor_unidad_base
        FROM erp_catalogo_skus WHERE sku='TP-40352' LIMIT 1");
    $presentacion = uatOneTp40352($db, "SELECT id_sku, sku, costo_referencia, factor_unidad_base
        FROM erp_catalogo_skus WHERE sku='TP-40352-500GR' LIMIT 1");
    $transformacion = uatOneTp40352($db, "SELECT t.id_sku_transformacion, so.sku sku_origen, sr.sku sku_resultado,
            t.cantidad_origen, t.unidades_resultado, t.merma_porcentaje, t.estatus
        FROM erp_catalogo_sku_transformaciones t
        INNER JOIN erp_catalogo_skus so ON so.id_sku=t.id_sku_origen
        INNER JOIN erp_catalogo_skus sr ON sr.id_sku=t.id_sku_resultado
        WHERE so.sku='TP-40352' AND sr.sku='TP-40352-500GR'
        LIMIT 1");

    if (!$base || !$presentacion || !$transformacion) {
        throw new Exception("No se encontro catalogo/transformacion completa para TP-40352");
    }

    $factorBase = floatval($base["factor_unidad_base"]);
    $costoPresentacion = floatval($presentacion["costo_referencia"]);
    $cantidadOrigen = floatval($transformacion["cantidad_origen"]);
    $unidadesResultado = floatval($transformacion["unidades_resultado"]);
    $mermaPct = max(0, floatval($transformacion["merma_porcentaje"]));
    $factorMerma = 1 + ($mermaPct / 100);

    if ($factorBase <= 0 || $costoPresentacion <= 0 || $cantidadOrigen <= 0 || $unidadesResultado <= 0) {
        throw new Exception("Datos insuficientes para inferir costo base de TP-40352");
    }

    $costoUnitarioOrigen = ($costoPresentacion * $unidadesResultado) / ($cantidadOrigen * $factorMerma);
    $costoBaseComercial = $costoUnitarioOrigen * $factorBase;
    $costoObjetivo = uatRoundTp40352($costoBaseComercial);

    $antes = array(
        "catalogo" => uatAllTp40352($db, "SELECT sku, costo_referencia, factor_unidad_base
            FROM erp_catalogo_skus WHERE sku IN ('TP-40352','TP-40352-500GR') ORDER BY sku"),
        "existencias" => uatAllTp40352($db, "SELECT ex.id_existencia_inventario, s.sku, ex.cantidad, ex.costo_promedio
            FROM erp_inventario_existencias ex
            INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
            WHERE s.sku IN ('TP-40352','TP-40352-500GR')
            ORDER BY s.sku, ex.id_existencia_inventario"),
        "movimientos" => uatAllTp40352($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento, m.origen_tipo, m.referencia, m.cantidad, m.costo_unitario, m.costo_total
            FROM erp_inventario_movimientos m
            INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
            WHERE s.sku IN ('TP-40352','TP-40352-500GR')
            ORDER BY m.id_movimiento_inventario")
    );

    $rows = array();
    if ($execute) {
        $db->beginTransaction();
        $stmt = $db->prepare("UPDATE erp_catalogo_skus
            SET costo_referencia=:costo, fecha_actualizacion=NOW()
            WHERE id_sku=:id_sku AND sku='TP-40352'");
        $stmt->execute(array(":costo" => $costoObjetivo, ":id_sku" => intval($base["id_sku"])));
        $rows["catalogo_tp40352"] = $stmt->rowCount();
        $db->commit();
    }

    $despues = array(
        "catalogo" => uatAllTp40352($db, "SELECT sku, costo_referencia, factor_unidad_base
            FROM erp_catalogo_skus WHERE sku IN ('TP-40352','TP-40352-500GR') ORDER BY sku"),
        "existencias" => uatAllTp40352($db, "SELECT ex.id_existencia_inventario, s.sku, ex.cantidad, ex.costo_promedio
            FROM erp_inventario_existencias ex
            INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
            WHERE s.sku IN ('TP-40352','TP-40352-500GR')
            ORDER BY s.sku, ex.id_existencia_inventario"),
        "movimientos" => uatAllTp40352($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento, m.origen_tipo, m.referencia, m.cantidad, m.costo_unitario, m.costo_total
            FROM erp_inventario_movimientos m
            INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
            WHERE s.sku IN ('TP-40352','TP-40352-500GR')
            ORDER BY m.id_movimiento_inventario")
    );

    $rentabilidad = new RentabilidadErp();
    $auditoria = $rentabilidad->auditarCostosPresentaciones(array("q" => "TP-40352"));

    echo json_encode(array(
        "ok" => true,
        "execute" => $execute,
        "formula" => array(
            "sku_base" => "TP-40352",
            "sku_presentacion" => "TP-40352-500GR",
            "costo_presentacion_500gr" => uatRoundTp40352($costoPresentacion),
            "cantidad_origen" => uatRoundTp40352($cantidadOrigen),
            "unidades_resultado" => uatRoundTp40352($unidadesResultado),
            "merma_porcentaje" => uatRoundTp40352($mermaPct),
            "costo_unitario_origen" => uatRoundTp40352($costoUnitarioOrigen),
            "factor_base" => uatRoundTp40352($factorBase),
            "costo_base_comercial_objetivo" => $costoObjetivo
        ),
        "rows" => $rows,
        "antes" => $antes,
        "despues" => $despues,
        "auditoria_presentaciones" => array(
            "total" => $auditoria["depurar"]["total"] ?? null,
            "alertas" => $auditoria["depurar"]["alertas"] ?? null,
            "items" => $auditoria["depurar"]["items"] ?? array()
        )
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    if ($execute && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array("ok" => false, "execute" => $execute, "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}
