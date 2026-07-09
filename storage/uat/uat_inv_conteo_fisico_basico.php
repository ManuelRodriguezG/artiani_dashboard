<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$ejecutar = in_array("--execute", $argv, true);
$modelo = new InventarioErp();

if (!$ejecutar) {
    echo json_encode(array(
        "ok" => true,
        "dry_run" => true,
        "mensaje" => "Ejecuta con --execute para crear un conteo UAT sin ajustar stock"
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}

$crear = $modelo->crearConteo(array(
    "id_almacen" => 3,
    "tipo_conteo" => "ciclico",
    "observaciones" => "UAT conteo fisico basico sin ajuste de stock"
), 0);

if (!empty($crear["error"])) {
    echo json_encode(array("ok" => false, "paso" => "crear", "respuesta" => $crear), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$idConteo = intval($crear["depurar"]["id_conteo_inventario"]);
$consultar = $modelo->consultarConteo(array("id_conteo_inventario" => $idConteo));
if (!empty($consultar["error"]) || empty($consultar["depurar"]["detalle"])) {
    echo json_encode(array("ok" => false, "paso" => "consultar", "respuesta" => $consultar), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$primerDetalle = $consultar["depurar"]["detalle"][0];
$capturar = $modelo->capturarConteo(array(
    "id_conteo_inventario" => $idConteo,
    "items" => json_encode(array(array(
        "id_conteo_detalle" => intval($primerDetalle["id_conteo_detalle"]),
        "cantidad_fisica" => floatval($primerDetalle["cantidad_sistema"]),
        "motivo_diferencia" => "",
        "observaciones" => "UAT captura igual a sistema"
    )))
), 0);

$final = $modelo->consultarConteo(array("id_conteo_inventario" => $idConteo));
$detalleFinal = array();
if (empty($final["error"]) && !empty($final["depurar"]["detalle"])) {
    foreach ($final["depurar"]["detalle"] as $detalle) {
        if (intval($detalle["id_conteo_detalle"]) === intval($primerDetalle["id_conteo_detalle"])) {
            $detalleFinal = $detalle;
            break;
        }
    }
}

echo json_encode(array(
    "ok" => empty($capturar["error"]),
    "folio" => $crear["depurar"]["folio"],
    "id_conteo_inventario" => $idConteo,
    "partidas_snapshot" => intval($crear["depurar"]["partidas"]),
    "captura" => $capturar,
    "detalle_capturado" => array(
        "sku" => isset($detalleFinal["sku"]) ? $detalleFinal["sku"] : "",
        "codigo_existencia" => isset($detalleFinal["codigo_existencia"]) ? $detalleFinal["codigo_existencia"] : "",
        "cantidad_sistema" => isset($detalleFinal["cantidad_sistema"]) ? $detalleFinal["cantidad_sistema"] : "",
        "cantidad_fisica" => isset($detalleFinal["cantidad_fisica"]) ? $detalleFinal["cantidad_fisica"] : "",
        "diferencia" => isset($detalleFinal["diferencia"]) ? $detalleFinal["diferencia"] : ""
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
