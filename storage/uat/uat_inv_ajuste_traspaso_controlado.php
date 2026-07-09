<?php

$_SERVER["SERVER_NAME"] = "dashboard.com.local";

require_once __DIR__ . "/../../app/config/configuracion.php";
require_once __DIR__ . "/../../app/config/mysql.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$db = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
));

$inventario = new InventarioErp();
$idSku = 1138; // SP-2823
$idAlmacenOrigen = 3;
$idAlmacenDestino = 1;
$idUbicacionOrigen = 12;
$idExistenciaOrigen = 24;
$cantidad = 1;
$usuario = 1;

function movimientoPorReferencia(PDO $db, $referencia, $tipo, $idAlmacen) {
    $stmt = $db->prepare("SELECT id_movimiento_inventario, id_existencia_inventario, existencia_anterior, existencia_nueva
        FROM erp_inventario_movimientos
        WHERE referencia=:referencia AND tipo_movimiento=:tipo AND id_almacen=:almacen
        ORDER BY id_movimiento_inventario DESC LIMIT 1");
    $stmt->execute(array(
        ":referencia" => $referencia,
        ":tipo" => $tipo,
        ":almacen" => $idAlmacen
    ));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$resultado = array();

$resultado["ajuste_entrada"] = $inventario->aplicarAjuste(array(
    "id_almacen" => $idAlmacenOrigen,
    "tipo_ajuste" => "entrada",
    "items" => json_encode(array(
        array(
            "id_sku" => $idSku,
            "cantidad" => $cantidad,
            "ubicacion_id" => $idUbicacionOrigen
        )
    )),
    "observaciones" => "UAT-INV-004 ajuste entrada controlada SP-2823"
), $usuario);

if (!empty($resultado["ajuste_entrada"]["error"])) {
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

$refEntrada = $resultado["ajuste_entrada"]["depurar"]["referencia"];
$movEntrada = movimientoPorReferencia($db, $refEntrada, "entrada", $idAlmacenOrigen);
sleep(1);

$resultado["ajuste_salida"] = $inventario->aplicarAjuste(array(
    "id_almacen" => $idAlmacenOrigen,
    "tipo_ajuste" => "salida",
    "items" => json_encode(array(
        array(
            "id_sku" => $idSku,
            "id_existencia_inventario" => intval($movEntrada["id_existencia_inventario"]),
            "cantidad" => $cantidad
        )
    )),
    "observaciones" => "UAT-INV-004 ajuste salida controlada SP-2823"
), $usuario);

if (!empty($resultado["ajuste_salida"]["error"])) {
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

sleep(1);

$resultado["traspaso_ida"] = $inventario->aplicarTraspaso(array(
    "id_almacen_origen" => $idAlmacenOrigen,
    "id_almacen_destino" => $idAlmacenDestino,
    "items" => json_encode(array(
        array(
            "id_sku" => $idSku,
            "id_existencia_inventario" => $idExistenciaOrigen,
            "cantidad" => $cantidad
        )
    )),
    "observaciones" => "UAT-INV-005 traspaso ida controlado SP-2823"
), $usuario);

if (!empty($resultado["traspaso_ida"]["error"])) {
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}

$refTraspasoIda = $resultado["traspaso_ida"]["depurar"]["referencia"];
$movEntradaDestino = movimientoPorReferencia($db, $refTraspasoIda, "entrada", $idAlmacenDestino);
sleep(1);

$resultado["traspaso_regreso"] = $inventario->aplicarTraspaso(array(
    "id_almacen_origen" => $idAlmacenDestino,
    "id_almacen_destino" => $idAlmacenOrigen,
    "items" => json_encode(array(
        array(
            "id_sku" => $idSku,
            "id_existencia_inventario" => intval($movEntradaDestino["id_existencia_inventario"]),
            "cantidad" => $cantidad,
            "ubicacion_destino_id" => $idUbicacionOrigen
        )
    )),
    "observaciones" => "UAT-INV-005 traspaso regreso controlado SP-2823"
), $usuario);

$resultado["movimientos_control"] = array(
    "ajuste_entrada" => $movEntrada,
    "traspaso_entrada_destino" => $movEntradaDestino
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
