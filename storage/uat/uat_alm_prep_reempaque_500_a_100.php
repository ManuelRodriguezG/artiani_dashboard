<?php

if (!in_array('--execute', $argv, true)) {
    echo "UAT ALM-PREP-T015 no ejecutado. Para afectar inventario, usa --execute de forma explicita." . PHP_EOL;
    exit(0);
}

chdir(__DIR__ . '/../../public');
require '../app/iniciador.php';
require '../app/modelos/Almacenes.php';

class AlmacenesUatReempaque extends Almacenes {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$almacenes = new AlmacenesUatReempaque();
$db = $almacenes->conexionUat();

function uatFetchOne($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function uatFetchAll($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function uatSkuId($db, $sku) {
    $row = uatFetchOne($db, "SELECT id_sku FROM erp_catalogo_skus WHERE sku=:sku LIMIT 1", array(":sku" => $sku));
    if (!$row) {
        throw new Exception("SKU no encontrado: " . $sku);
    }
    return intval($row["id_sku"]);
}

function uatTransformacionId($db, $skuOrigen, $skuResultado) {
    $row = uatFetchOne($db, "SELECT tr.id_sku_transformacion
        FROM erp_catalogo_sku_transformaciones tr
        INNER JOIN erp_catalogo_skus so ON so.id_sku=tr.id_sku_origen
        INNER JOIN erp_catalogo_skus sr ON sr.id_sku=tr.id_sku_resultado
        WHERE so.sku=:origen AND sr.sku=:resultado AND tr.estatus='activa'
        ORDER BY tr.id_sku_transformacion DESC LIMIT 1", array(
        ":origen" => $skuOrigen,
        ":resultado" => $skuResultado
    ));
    if (!$row) {
        throw new Exception("Transformacion no encontrada: " . $skuOrigen . " -> " . $skuResultado);
    }
    return intval($row["id_sku_transformacion"]);
}

function uatExistenciasSku($db, $sku) {
    return uatFetchAll($db, "SELECT ex.id_existencia_inventario, ex.codigo_existencia, s.sku, ex.lote,
            ex.fecha_caducidad, ex.cantidad, ex.cantidad_disponible, ex.estatus_existencia
        FROM erp_inventario_existencias ex
        INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
        WHERE s.sku=:sku AND ex.id_almacen_clave=3
        ORDER BY ex.id_existencia_inventario", array(":sku" => $sku));
}

try {
    $idSku100 = uatSkuId($db, "TP-40372-100GR");
    $idTransformacion500 = uatTransformacionId($db, "TP-40372", "TP-40372-500GR");
    $idTransformacion100 = uatTransformacionId($db, "TP-40372-500GR", "TP-40372-100GR");

    $prep500Existente = uatFetchOne($db, "SELECT p.id_preparacion_almacen, p.folio, m.id_existencia_inventario
        FROM erp_almacen_preparaciones p
        INNER JOIN erp_inventario_movimientos m ON m.origen_tipo='preparacion_presentacion'
            AND m.origen_id=p.id_preparacion_almacen
            AND m.tipo_movimiento='entrada'
        INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_presentacion
        INNER JOIN erp_inventario_existencias ex ON ex.id_existencia_inventario=m.id_existencia_inventario
        WHERE p.observaciones='UAT ALM-PREP-T015 paso 1: preparar una bolsa 500GR controlada'
          AND p.estatus='confirmada'
          AND s.sku='TP-40372-500GR'
        ORDER BY p.id_preparacion_almacen DESC LIMIT 1");

    $prep100Existente = uatFetchOne($db, "SELECT p.id_preparacion_almacen, p.folio
        FROM erp_almacen_preparaciones p
        INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_presentacion
        WHERE p.observaciones='UAT ALM-PREP-T015 paso 2: reempaque 500GR a 5 bolsas 100GR'
          AND p.estatus='confirmada'
          AND s.sku='TP-40372-100GR'
        ORDER BY p.id_preparacion_almacen DESC LIMIT 1");

    $existenciaGranel = uatFetchOne($db, "SELECT id_existencia_inventario, cantidad_disponible
        FROM erp_inventario_existencias ex
        INNER JOIN erp_catalogo_skus s ON s.id_sku=ex.id_sku_erp
        WHERE s.sku='TP-40372' AND ex.id_almacen_clave=3 AND ex.lote='L1'
          AND ex.estatus_existencia='disponible' AND ex.cantidad_disponible >= 0.500000
        ORDER BY ex.fecha_caducidad, ex.id_existencia_inventario LIMIT 1");
    if (!$prep500Existente && !$existenciaGranel) {
        throw new Exception("No hay existencia granel L1 suficiente para crear la bolsa 500GR controlada");
    }

    $antes = array(
        "tp40372" => uatExistenciasSku($db, "TP-40372"),
        "tp40372_500gr" => uatExistenciasSku($db, "TP-40372-500GR"),
        "tp40372_100gr" => uatExistenciasSku($db, "TP-40372-100GR"),
        "regla_100gr" => uatFetchOne($db, "SELECT generar_etiqueta_interna, prefijo_etiqueta_interna FROM erp_catalogo_sku_reglas_inventario WHERE id_sku=:sku", array(":sku" => $idSku100))
    );

    $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_reglas_inventario
            (id_sku, controla_inventario, generar_etiqueta_interna, prefijo_etiqueta_interna, fecha_actualizacion)
        VALUES (:sku, 1, 1, 'P100', NOW())
        ON DUPLICATE KEY UPDATE generar_etiqueta_interna=1, prefijo_etiqueta_interna='P100', fecha_actualizacion=NOW()");
    $stmt->execute(array(":sku" => $idSku100));

    $borrador500 = null;
    $confirmacion500 = null;
    if ($prep500Existente) {
        $idExistencia500 = intval($prep500Existente["id_existencia_inventario"]);
    } else {
        $borrador500 = $almacenes->guardar_borrador_preparacion(array(
            "id_almacen" => 3,
            "id_sku_transformacion" => $idTransformacion500,
            "id_existencia_origen" => intval($existenciaGranel["id_existencia_inventario"]),
            "unidades_preparadas" => 1,
            "observaciones" => "UAT ALM-PREP-T015 paso 1: preparar una bolsa 500GR controlada"
        ), 0);

        if (empty($borrador500["error"])) {
            $confirmacion500 = $almacenes->confirmar_preparacion(intval($borrador500["depurar"]["id_preparacion_almacen"]), 0);
        }
        if (!empty($borrador500["error"]) || empty($confirmacion500) || !empty($confirmacion500["error"])) {
            throw new Exception("No se pudo crear la existencia 500GR controlada");
        }

        $mov500 = uatFetchOne($db, "SELECT id_existencia_inventario FROM erp_inventario_movimientos WHERE id_movimiento_inventario=:movimiento", array(
            ":movimiento" => intval($confirmacion500["depurar"]["movimiento_entrada"])
        ));
        if (!$mov500) {
            throw new Exception("No se encontro la existencia 500GR creada por el movimiento de entrada");
        }
        $idExistencia500 = intval($mov500["id_existencia_inventario"]);
    }

    $borrador100 = null;
    $confirmacion100 = null;
    if (!$prep100Existente) {
        $borrador100 = $almacenes->guardar_borrador_preparacion(array(
            "id_almacen" => 3,
            "id_sku_transformacion" => $idTransformacion100,
            "id_existencia_origen" => $idExistencia500,
            "unidades_preparadas" => 5,
            "observaciones" => "UAT ALM-PREP-T015 paso 2: reempaque 500GR a 5 bolsas 100GR"
        ), 0);

        if (empty($borrador100["error"])) {
            $confirmacion100 = $almacenes->confirmar_preparacion(intval($borrador100["depurar"]["id_preparacion_almacen"]), 0);
        }
        if (!empty($borrador100["error"]) || empty($confirmacion100) || !empty($confirmacion100["error"])) {
            throw new Exception("No se pudo confirmar el reempaque 500GR -> 100GR");
        }
    }

    $idPrep500 = $prep500Existente ? intval($prep500Existente["id_preparacion_almacen"]) : intval($borrador500["depurar"]["id_preparacion_almacen"]);
    $idPrep100 = $prep100Existente ? intval($prep100Existente["id_preparacion_almacen"]) : intval($borrador100["depurar"]["id_preparacion_almacen"]);

    $despues = array(
        "tp40372" => uatExistenciasSku($db, "TP-40372"),
        "tp40372_500gr" => uatExistenciasSku($db, "TP-40372-500GR"),
        "tp40372_100gr" => uatExistenciasSku($db, "TP-40372-100GR"),
        "movimientos" => uatFetchAll($db, "SELECT id_movimiento_inventario, tipo_movimiento, origen_tipo, origen_id, id_existencia_inventario,
                codigo_existencia, cantidad, existencia_anterior, existencia_nueva, referencia
            FROM erp_inventario_movimientos
            WHERE origen_tipo='preparacion_presentacion' AND origen_id IN (:prep500, :prep100)
            ORDER BY id_movimiento_inventario", array(":prep500" => $idPrep500, ":prep100" => $idPrep100)),
        "etiquetas_100gr" => uatFetchAll($db, "SELECT codigo_unico, codigo_etiqueta_interna, estatus, estado_etiqueta, origen_tipo, origen_id
            FROM erp_inventario_unidades
            WHERE origen_tipo='preparacion_presentacion' AND origen_id=:prep100
            ORDER BY id_inventario_unidad", array(":prep100" => $idPrep100))
    );

    echo json_encode(array(
        "success" => true,
        "antes" => $antes,
        "paso_500gr" => array(
            "reutilizado" => $prep500Existente,
            "borrador" => $borrador500,
            "confirmacion" => $confirmacion500,
            "id_existencia_500gr_creada" => $idExistencia500
        ),
        "paso_100gr" => array(
            "reutilizado" => $prep100Existente,
            "borrador" => $borrador100,
            "confirmacion" => $confirmacion100
        ),
        "despues" => $despues
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo PHP_EOL;
} catch (Exception $e) {
    echo json_encode(array(
        "success" => false,
        "mensaje" => $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo PHP_EOL;
    exit(1);
}
