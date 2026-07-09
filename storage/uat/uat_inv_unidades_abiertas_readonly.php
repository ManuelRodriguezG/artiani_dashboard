<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$modelo = new InventarioErp();
$codigoUnidad = "UAT-EXI-26-20260625-001";
$codigoExistencia = "EXI-50-26";

try {
    $existencias = $modelo->listarExistencias(array(
        "q" => $codigoUnidad,
        "incluir_agotadas" => 1
    ));
    $unidades = $modelo->listarEtiquetas(array(
        "q" => $codigoUnidad,
        "estado_fisico" => "abierta"
    ));
    $trazabilidad = $modelo->consultarTrazabilidad(array("q" => $codigoUnidad));
    $diagnostico = $modelo->diagnosticoOperativo(array("id_almacen" => 0));

    if (!empty($existencias["error"])) {
        throw new Exception($existencias["mensaje"]);
    }
    if (!empty($unidades["error"])) {
        throw new Exception($unidades["mensaje"]);
    }
    if (!empty($trazabilidad["error"])) {
        throw new Exception($trazabilidad["mensaje"]);
    }
    if (!empty($diagnostico["error"])) {
        throw new Exception($diagnostico["mensaje"]);
    }

    $existencia = null;
    foreach ($existencias["depurar"] as $item) {
        if ($item["codigo_existencia"] === $codigoExistencia) {
            $existencia = $item;
            break;
        }
    }
    $unidad = isset($unidades["depurar"][0]) ? $unidades["depurar"][0] : null;
    if (!$existencia || !$unidad) {
        throw new Exception("No se encontro la existencia o unidad abierta esperada");
    }

    $okUnidad = $unidad["estado_fisico"] === "abierta"
        && $unidad["estatus"] === "disponible"
        && abs(floatval($unidad["cantidad_base_disponible"]) - 14.95) < 0.0001;
    $okExistencia = $existencia["codigo_existencia"] === $codigoExistencia
        && intval($existencia["unidades_abiertas"]) >= 1
        && abs(floatval($existencia["cantidad_disponible"]) - floatval($existencia["contenido_base_disponible"])) < 0.0001;

    echo json_encode(array(
        "ok" => $okUnidad && $okExistencia,
        "unidad" => array(
            "codigo" => $codigoUnidad,
            "sku" => $unidad["sku"],
            "estado_fisico" => $unidad["estado_fisico"],
            "estatus" => $unidad["estatus"],
            "contenido_disponible" => round(floatval($unidad["cantidad_base_disponible"]), 6),
            "unidad_base" => $unidad["unidad_base"]
        ),
        "existencia" => array(
            "codigo" => $existencia["codigo_existencia"],
            "cantidad" => round(floatval($existencia["cantidad"]), 4),
            "disponible" => round(floatval($existencia["cantidad_disponible"]), 4),
            "unidades_abiertas" => intval($existencia["unidades_abiertas"]),
            "contenido_trazable" => round(floatval($existencia["contenido_base_disponible"]), 6),
            "diferencia" => round(floatval($existencia["diferencia_contenido_unidades"]), 6)
        ),
        "trazabilidad" => array(
            "existencias" => count($trazabilidad["depurar"]["existencias"]),
            "movimientos" => count($trazabilidad["depurar"]["movimientos"]),
            "unidades" => count($trazabilidad["depurar"]["unidades"])
        ),
        "diagnostico" => isset($diagnostico["depurar"]["resumen"]) ? $diagnostico["depurar"]["resumen"] : array()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    echo json_encode(array("ok" => false, "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
