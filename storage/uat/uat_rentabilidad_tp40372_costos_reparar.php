<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatRentabilidadTp40372CostosReparar extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatOne($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uatAll($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uatExec($db, $execute, &$plan, $label, $sql, $params) {
    $plan[] = array("accion" => $label, "params" => $params);
    if (!$execute) {
        return 0;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

function uatRound($value) {
    return round(floatval($value), 6);
}

$execute = in_array("--execute", $argv, true);
$modelo = new UatRentabilidadTp40372CostosReparar();
$db = $modelo->db();
$plan = array();
$rows = array();

try {
    $base = uatOne($db, "SELECT id_sku, sku, costo_referencia, factor_unidad_base
        FROM erp_catalogo_skus WHERE sku='TP-40372' LIMIT 1");
    if (!$base) {
        throw new Exception("SKU base TP-40372 no encontrado");
    }
    $factorBase = floatval($base["factor_unidad_base"]);
    $costoCostal = floatval($base["costo_referencia"]);
    if ($factorBase <= 0 || $costoCostal <= 0) {
        throw new Exception("Costo/factor base no validos para TP-40372");
    }

    $costoKg = uatRound($costoCostal / $factorBase);
    $costos = array(
        "TP-40372" => $costoKg,
        "TP-40372-500GR" => uatRound($costoKg * 0.5),
        "TP-40372-100GR" => uatRound($costoKg * 0.1),
        "TP-40372-50GR" => uatRound($costoKg * 0.05),
        "TP-40372-25GR" => uatRound($costoKg * 0.025)
    );
    $ids = array();
    foreach (array_keys($costos) as $sku) {
        $row = uatOne($db, "SELECT id_sku FROM erp_catalogo_skus WHERE sku=:sku LIMIT 1", array(":sku" => $sku));
        if ($row) {
            $ids[$sku] = intval($row["id_sku"]);
        }
    }

    $antes = array(
        "catalogo" => uatAll($db, "SELECT sku, costo_referencia FROM erp_catalogo_skus WHERE sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR') ORDER BY sku"),
        "existencias" => uatAll($db, "SELECT ex.id_existencia_inventario, s.sku, ex.lote, ex.cantidad, ex.cantidad_disponible, ex.costo_promedio, ROUND(ex.cantidad*ex.costo_promedio,6) valor
            FROM erp_inventario_existencias ex INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
            WHERE s.sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR')
            ORDER BY s.sku, ex.id_existencia_inventario"),
        "movimientos" => uatAll($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento, m.origen_tipo, m.referencia, m.cantidad, m.costo_unitario, m.costo_total
            FROM erp_inventario_movimientos m INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
            WHERE s.sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR')
              AND (m.origen_tipo IN ('recepcion_compra','preparacion_presentacion') OR m.referencia LIKE 'PREP-%')
            ORDER BY m.id_movimiento_inventario")
    );

    if ($execute) {
        $db->beginTransaction();
    }

    foreach ($costos as $sku => $costo) {
        if (!isset($ids[$sku])) {
            continue;
        }
        if ($sku !== "TP-40372") {
            $rows["catalogo_" . $sku] = uatExec($db, $execute, $plan, "Actualizar costo referencia {$sku}",
                "UPDATE erp_catalogo_skus SET costo_referencia=:costo, fecha_actualizacion=NOW() WHERE id_sku=:sku",
                array(":costo" => $costo, ":sku" => $ids[$sku]));
        }
        $rows["existencias_" . $sku] = uatExec($db, $execute, $plan, "Actualizar costo promedio existencias {$sku}",
            "UPDATE erp_inventario_existencias SET costo_promedio=:costo, fecha_actualizacion=NOW() WHERE id_sku_erp=:sku",
            array(":costo" => $costo, ":sku" => $ids[$sku]));
        $rows["recepcion_lotes_" . $sku] = uatExec($db, $execute, $plan, "Actualizar costo recepcion lote {$sku}",
            "UPDATE erp_almacen_recepciones_lotes SET costo_unitario=:costo WHERE id_sku_erp=:sku",
            array(":costo" => $costo, ":sku" => $ids[$sku]));
        $rows["movimientos_" . $sku] = uatExec($db, $execute, $plan, "Actualizar movimientos {$sku}",
            "UPDATE erp_inventario_movimientos
                SET costo_unitario=:costo, costo_total=ROUND(cantidad*:costo, 6)
                WHERE id_sku_erp=:sku AND origen_tipo IN ('recepcion_compra','preparacion_presentacion')",
            array(":costo" => $costo, ":sku" => $ids[$sku]));
    }

    foreach ($costos as $sku => $costo) {
        if (!isset($ids[$sku])) {
            continue;
        }
        $rows["prep_consumos_" . $sku] = uatExec($db, $execute, $plan, "Actualizar consumos preparacion {$sku}",
            "UPDATE erp_almacen_preparacion_consumos
                SET costo_unitario=:costo, costo_total=ROUND(cantidad_consumida*:costo, 6)
                WHERE id_sku_base=:sku",
            array(":costo" => $costo, ":sku" => $ids[$sku]));
        $rows["prep_resultados_" . $sku] = uatExec($db, $execute, $plan, "Actualizar resultados preparacion {$sku}",
            "UPDATE erp_almacen_preparacion_resultados
                SET costo_unitario=:costo, costo_total=ROUND(unidades_preparadas*:costo, 6)
                WHERE id_sku_presentacion=:sku",
            array(":costo" => $costo, ":sku" => $ids[$sku]));
    }

    if ($execute) {
        $db->commit();
    }

    $despues = array(
        "catalogo" => uatAll($db, "SELECT sku, costo_referencia FROM erp_catalogo_skus WHERE sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR') ORDER BY sku"),
        "existencias" => uatAll($db, "SELECT ex.id_existencia_inventario, s.sku, ex.lote, ex.cantidad, ex.cantidad_disponible, ex.costo_promedio, ROUND(ex.cantidad*ex.costo_promedio,6) valor
            FROM erp_inventario_existencias ex INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
            WHERE s.sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR')
            ORDER BY s.sku, ex.id_existencia_inventario"),
        "movimientos" => uatAll($db, "SELECT m.id_movimiento_inventario, s.sku, m.tipo_movimiento, m.origen_tipo, m.referencia, m.cantidad, m.costo_unitario, m.costo_total
            FROM erp_inventario_movimientos m INNER JOIN erp_catalogo_skus s ON s.id_sku=m.id_sku_erp
            WHERE s.sku IN ('TP-40372','TP-40372-500GR','TP-40372-100GR','TP-40372-50GR','TP-40372-25GR')
              AND (m.origen_tipo IN ('recepcion_compra','preparacion_presentacion') OR m.referencia LIKE 'PREP-%')
            ORDER BY m.id_movimiento_inventario")
    );

    echo json_encode(array(
        "ok" => true,
        "execute" => $execute,
        "formula" => array(
            "costo_costal_4kg" => uatRound($costoCostal),
            "factor_kg_por_costal" => uatRound($factorBase),
            "costo_kg" => $costoKg,
            "costos_objetivo" => $costos
        ),
        "rows" => $rows,
        "plan" => $plan,
        "antes" => $antes,
        "despues" => $despues
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    if ($execute && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array("ok" => false, "execute" => $execute, "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}
