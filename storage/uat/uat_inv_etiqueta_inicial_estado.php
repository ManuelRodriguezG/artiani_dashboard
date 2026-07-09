<?php

if (!in_array("--execute", $argv, true)) {
    echo "UAT etiqueta inventario inicial no ejecutado. Usa --execute para afectar estado de etiqueta." . PHP_EOL;
    exit(0);
}

require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/core/CRUD.php";
require __DIR__ . "/../../app/modelos/InventarioErp.php";

$codigo = "P25-II000043-0001";
$referencia = "INV-INICIAL-20260622-UAT01";

$db = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 10
));

$stmt = $db->prepare("SELECT u.id_inventario_unidad, u.codigo_etiqueta_interna, u.estado_etiqueta, u.origen_tipo, m.referencia
    FROM erp_inventario_unidades u
    INNER JOIN erp_inventario_movimientos m ON m.id_movimiento_inventario=u.origen_id
    WHERE u.codigo_etiqueta_interna=:codigo AND u.origen_tipo='inventario_inicial'");
$stmt->execute(array(":codigo" => $codigo));
$unidad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$unidad || $unidad["referencia"] !== $referencia) {
    echo json_encode(array("ok" => false, "mensaje" => "Etiqueta UAT no encontrada o referencia no coincide", "unidad" => $unidad), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

if ($unidad["estado_etiqueta"] === "pegada") {
    echo json_encode(array("ok" => true, "mensaje" => "Etiqueta ya estaba pegada; no se reejecuta", "unidad" => $unidad), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$modelo = new InventarioErp();
$antes = $unidad["estado_etiqueta"];

if ($unidad["estado_etiqueta"] === "pendiente_impresion" || $unidad["estado_etiqueta"] === "reimpresa") {
    $respuestaImpresa = $modelo->marcarEtiquetaImpresa(array("id_inventario_unidad" => $unidad["id_inventario_unidad"]), 0);
    if (!empty($respuestaImpresa["error"])) {
        echo json_encode(array("ok" => false, "paso" => "impresa", "respuesta" => $respuestaImpresa), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        exit(1);
    }
}

$respuestaPegada = $modelo->marcarEtiquetaPegada(array("id_inventario_unidad" => $unidad["id_inventario_unidad"]), 0);
if (!empty($respuestaPegada["error"])) {
    echo json_encode(array("ok" => false, "paso" => "pegada", "respuesta" => $respuestaPegada), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
}

$stmt->execute(array(":codigo" => $codigo));
$despues = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(array(
    "ok" => true,
    "codigo" => $codigo,
    "referencia" => $referencia,
    "estado_antes" => $antes,
    "estado_despues" => $despues["estado_etiqueta"],
    "unidad" => $despues
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
