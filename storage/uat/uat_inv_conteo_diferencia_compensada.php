<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$ejecutar = in_array("--execute", $argv, true);
$modelo = new InventarioErp();
$skuObjetivo = "TP-40372";
$delta = 0.1;

if (!$ejecutar) {
    echo json_encode(array(
        "ok" => true,
        "dry_run" => true,
        "sku" => $skuObjetivo,
        "delta" => $delta,
        "mensaje" => "Ejecuta con --execute para crear dos conteos: diferencia positiva y compensacion"
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}

function buscarDetallePorSku($detalle, $skuObjetivo) {
    foreach ($detalle as $item) {
        if ($item["sku"] === $skuObjetivo && floatval($item["cantidad_sistema"]) > 1) {
            return $item;
        }
    }
    throw new Exception("No se encontro detalle disponible para " . $skuObjetivo);
}

function crearCapturarCerrar($modelo, $skuObjetivo, $delta, $observacion) {
    $crear = $modelo->crearConteo(array(
        "id_almacen" => 3,
        "tipo_conteo" => "ciclico",
        "observaciones" => $observacion
    ), 0);
    if (!empty($crear["error"])) {
        throw new Exception("Crear conteo: " . $crear["mensaje"]);
    }

    $idConteo = intval($crear["depurar"]["id_conteo_inventario"]);
    $consulta = $modelo->consultarConteo(array("id_conteo_inventario" => $idConteo));
    if (!empty($consulta["error"])) {
        throw new Exception("Consultar conteo: " . $consulta["mensaje"]);
    }
    $detalleObjetivo = buscarDetallePorSku($consulta["depurar"]["detalle"], $skuObjetivo);

    $items = array();
    foreach ($consulta["depurar"]["detalle"] as $detalle) {
        $fisica = floatval($detalle["cantidad_sistema"]);
        $motivo = "";
        $nota = "UAT sin diferencia";
        if (intval($detalle["id_conteo_detalle"]) === intval($detalleObjetivo["id_conteo_detalle"])) {
            $fisica = round($fisica + $delta, 4);
            $motivo = $delta > 0 ? "sobrante_conteo" : "faltante_conteo";
            $nota = "UAT diferencia " . ($delta > 0 ? "+" : "") . number_format($delta, 4, ".", "");
        }
        $items[] = array(
            "id_conteo_detalle" => intval($detalle["id_conteo_detalle"]),
            "cantidad_fisica" => $fisica,
            "motivo_diferencia" => $motivo,
            "observaciones" => $nota
        );
    }

    $captura = $modelo->capturarConteo(array(
        "id_conteo_inventario" => $idConteo,
        "items" => json_encode($items)
    ), 0);
    if (!empty($captura["error"])) {
        throw new Exception("Capturar conteo: " . $captura["mensaje"]);
    }

    $preview = $modelo->previewCerrarConteo(array("id_conteo_inventario" => $idConteo));
    if (!empty($preview["error"])) {
        throw new Exception("Preview cierre: " . $preview["mensaje"]);
    }

    $cierre = $modelo->cerrarConteo(array(
        "id_conteo_inventario" => $idConteo,
        "observaciones" => $observacion
    ), 0);
    if (!empty($cierre["error"])) {
        throw new Exception("Cerrar conteo: " . $cierre["mensaje"]);
    }

    return array(
        "folio" => $crear["depurar"]["folio"],
        "id_conteo_inventario" => $idConteo,
        "sku" => $detalleObjetivo["sku"],
        "codigo_existencia" => $detalleObjetivo["codigo_existencia"],
        "cantidad_sistema" => $detalleObjetivo["cantidad_sistema"],
        "delta" => $delta,
        "preview" => $preview["depurar"],
        "cierre" => $cierre["depurar"]
    );
}

try {
    $positivo = crearCapturarCerrar($modelo, $skuObjetivo, $delta, "UAT conteo con diferencia positiva controlada");
    $compensacion = crearCapturarCerrar($modelo, $skuObjetivo, -$delta, "UAT conteo compensatorio para dejar saldo original");

    echo json_encode(array(
        "ok" => true,
        "positivo" => $positivo,
        "compensacion" => $compensacion
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    echo json_encode(array("ok" => false, "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
