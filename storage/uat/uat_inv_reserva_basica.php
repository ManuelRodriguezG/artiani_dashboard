<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$ejecutar = in_array("--execute", $argv, true);
$modelo = new InventarioErp();
$skuObjetivo = "TP-40372";
$cantidadReserva = 0.1;

if (!$ejecutar) {
    echo json_encode(array(
        "ok" => true,
        "dry_run" => true,
        "sku" => $skuObjetivo,
        "cantidad_reserva" => $cantidadReserva,
        "mensaje" => "Ejecuta con --execute para crear y liberar una reserva controlada"
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}

function seleccionarExistenciaDisponible($modelo, $skuObjetivo, $cantidadReserva) {
    $respuesta = $modelo->listarExistencias(array(
        "id_almacen" => 0,
        "q" => $skuObjetivo,
        "incluir_agotadas" => 0
    ));
    if (!empty($respuesta["error"])) {
        throw new Exception("Consultar existencias: " . $respuesta["mensaje"]);
    }
    foreach ($respuesta["depurar"] as $existencia) {
        if ($existencia["sku"] === $skuObjetivo && floatval($existencia["cantidad_disponible"]) >= $cantidadReserva) {
            return $existencia;
        }
    }
    throw new Exception("No se encontro existencia disponible para " . $skuObjetivo);
}

function consultarExistenciaPorCodigo($modelo, $codigo) {
    $respuesta = $modelo->listarExistencias(array(
        "id_almacen" => 0,
        "q" => $codigo,
        "incluir_agotadas" => 1
    ));
    if (!empty($respuesta["error"])) {
        throw new Exception("Consultar existencia final: " . $respuesta["mensaje"]);
    }
    foreach ($respuesta["depurar"] as $existencia) {
        if ($existencia["codigo_existencia"] === $codigo) {
            return $existencia;
        }
    }
    throw new Exception("No se encontro existencia " . $codigo);
}

try {
    $inicial = seleccionarExistenciaDisponible($modelo, $skuObjetivo, $cantidadReserva);
    $crear = $modelo->crearReserva(array(
        "id_existencia_inventario" => intval($inicial["id_existencia_inventario"]),
        "cantidad" => $cantidadReserva,
        "origen_tipo" => "uat_reserva",
        "observaciones" => "UAT reserva basica: crear y liberar"
    ), 0);
    if (!empty($crear["error"])) {
        throw new Exception("Crear reserva: " . $crear["mensaje"]);
    }

    $despuesCrear = consultarExistenciaPorCodigo($modelo, $inicial["codigo_existencia"]);
    $liberar = $modelo->liberarReserva(array(
        "id_reserva_inventario" => intval($crear["depurar"]["id_reserva_inventario"]),
        "observaciones" => "UAT reserva basica: liberacion compensatoria"
    ), 0);
    if (!empty($liberar["error"])) {
        throw new Exception("Liberar reserva: " . $liberar["mensaje"]);
    }

    $final = consultarExistenciaPorCodigo($modelo, $inicial["codigo_existencia"]);
    $regresoDisponible = abs(floatval($final["cantidad_disponible"]) - floatval($inicial["cantidad_disponible"])) < 0.0001;
    $regresoApartada = abs(floatval($final["cantidad_apartada"]) - floatval($inicial["cantidad_apartada"])) < 0.0001;
    $stockFisicoIgual = abs(floatval($final["cantidad"]) - floatval($inicial["cantidad"])) < 0.0001;

    echo json_encode(array(
        "ok" => $regresoDisponible && $regresoApartada && $stockFisicoIgual,
        "folio" => $crear["depurar"]["folio"],
        "codigo_existencia" => $inicial["codigo_existencia"],
        "sku" => $inicial["sku"],
        "cantidad_reservada" => $cantidadReserva,
        "inicial" => array(
            "cantidad" => round(floatval($inicial["cantidad"]), 4),
            "disponible" => round(floatval($inicial["cantidad_disponible"]), 4),
            "apartada" => round(floatval($inicial["cantidad_apartada"]), 4)
        ),
        "despues_crear" => array(
            "cantidad" => round(floatval($despuesCrear["cantidad"]), 4),
            "disponible" => round(floatval($despuesCrear["cantidad_disponible"]), 4),
            "apartada" => round(floatval($despuesCrear["cantidad_apartada"]), 4)
        ),
        "final" => array(
            "cantidad" => round(floatval($final["cantidad"]), 4),
            "disponible" => round(floatval($final["cantidad_disponible"]), 4),
            "apartada" => round(floatval($final["cantidad_apartada"]), 4)
        )
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    echo json_encode(array("ok" => false, "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
