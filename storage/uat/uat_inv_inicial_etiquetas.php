<?php

if (!in_array("--execute", $argv, true)) {
    echo "UAT INV-T017 no ejecutado. Para afectar inventario, usa --execute de forma explicita." . PHP_EOL;
    exit(0);
}

require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/core/CRUD.php";
require __DIR__ . "/../../app/modelos/InventarioErp.php";

$referencia = "INV-INICIAL-20260622-UAT01";
$skuCodigo = "TP-40372-25GR";
$idAlmacen = 3;
$idUbicacion = 12; // UAT-ALM-013
$lote = "UAT-INV-INICIAL-25GR";
$caducidad = "2027-12-31";

$modelo = new InventarioErp();
$db = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10
));

$stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
$stmt->execute(array(":referencia" => $referencia));
if (intval($stmt->fetchColumn()) > 0) {
    echo "UAT ya ejecutado para referencia {$referencia}; no se reejecuta." . PHP_EOL;
    exit(1);
}

$stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE sku=:sku AND estatus='activo'");
$stmt->execute(array(":sku" => $skuCodigo));
$idSku = intval($stmt->fetchColumn());
if ($idSku <= 0) {
    echo "SKU {$skuCodigo} no encontrado." . PHP_EOL;
    exit(1);
}

$antes = array(
    "existencias" => contar($db, "SELECT COUNT(*) FROM erp_inventario_existencias WHERE id_sku_erp=? AND id_almacen_clave=?", array($idSku, $idAlmacen)),
    "movimientos_ref" => contar($db, "SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=?", array($referencia)),
    "unidades_ref" => contar($db, "SELECT COUNT(*) FROM erp_inventario_unidades u INNER JOIN erp_inventario_movimientos m ON m.id_movimiento_inventario=u.origen_id WHERE u.origen_tipo='inventario_inicial' AND m.referencia=?", array($referencia))
);

$payload = array(
    "id_almacen" => $idAlmacen,
    "tipo_ajuste" => "entrada",
    "documento_operacion" => "inventario_inicial",
    "referencia" => $referencia,
    "observaciones" => "UAT INV-T017 inventario inicial con etiqueta TP-40372-25GR",
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => 1,
        "lote" => $lote,
        "fecha_caducidad" => $caducidad,
        "ubicacion_id" => $idUbicacion
    )))
);

$respuesta = $modelo->aplicarAjuste($payload, 0);
if (!empty($respuesta["error"])) {
    echo json_encode(array("ok" => false, "respuesta" => $respuesta, "antes" => $antes), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$stmt = $db->prepare("SELECT e.codigo_existencia, e.sku, e.lote, e.fecha_caducidad, e.ubicacion, e.cantidad, e.cantidad_disponible, e.estatus_existencia
    FROM (
        SELECT ie.*, s.sku
        FROM erp_inventario_existencias ie
        INNER JOIN erp_catalogo_skus s ON s.id_sku=ie.id_sku_erp
        WHERE ie.id_sku_erp=:sku AND ie.id_almacen_clave=:almacen AND ie.lote=:lote
    ) e
    ORDER BY e.id_existencia_inventario DESC LIMIT 5");
$stmt->execute(array(":sku" => $idSku, ":almacen" => $idAlmacen, ":lote" => $lote));
$existencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT id_movimiento_inventario, tipo_movimiento, origen_tipo, referencia, codigo_existencia, lote, cantidad, existencia_anterior, existencia_nueva
    FROM erp_inventario_movimientos WHERE referencia=:referencia ORDER BY id_movimiento_inventario");
$stmt->execute(array(":referencia" => $referencia));
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT u.codigo_unico, u.codigo_etiqueta_interna, u.estado_etiqueta, u.estatus, u.origen_tipo, u.origen_id, u.lote, u.fecha_caducidad
    FROM erp_inventario_unidades u
    INNER JOIN erp_inventario_movimientos m ON m.id_movimiento_inventario=u.origen_id
    WHERE u.origen_tipo='inventario_inicial' AND m.referencia=:referencia
    ORDER BY u.id_inventario_unidad");
$stmt->execute(array(":referencia" => $referencia));
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array(
    "ok" => true,
    "referencia" => $referencia,
    "respuesta" => $respuesta["depurar"],
    "antes" => $antes,
    "existencias" => $existencias,
    "movimientos" => $movimientos,
    "unidades" => $unidades
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function contar($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return intval($stmt->fetchColumn());
}
