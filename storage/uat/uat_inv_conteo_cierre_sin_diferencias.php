<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$ejecutar = in_array("--execute", $argv, true);
$folio = "CON-20260622-0001";
$modelo = new InventarioErp();

if (!$ejecutar) {
    echo json_encode(array(
        "ok" => true,
        "dry_run" => true,
        "folio" => $folio,
        "mensaje" => "Ejecuta con --execute para capturar todo igual al sistema y cerrar sin diferencias"
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit;
}

$lista = $modelo->listarConteos(array());
$idConteo = 0;
foreach (isset($lista["depurar"]) ? $lista["depurar"] : array() as $conteo) {
    if ($conteo["folio"] === $folio) {
        $idConteo = intval($conteo["id_conteo_inventario"]);
        break;
    }
}
if ($idConteo <= 0) {
    echo json_encode(array("ok" => false, "mensaje" => "Conteo no encontrado", "folio" => $folio), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$consulta = $modelo->consultarConteo(array("id_conteo_inventario" => $idConteo));
if (!empty($consulta["error"])) {
    echo json_encode(array("ok" => false, "paso" => "consultar", "respuesta" => $consulta), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$items = array();
foreach ($consulta["depurar"]["detalle"] as $detalle) {
    $items[] = array(
        "id_conteo_detalle" => intval($detalle["id_conteo_detalle"]),
        "cantidad_fisica" => floatval($detalle["cantidad_sistema"]),
        "motivo_diferencia" => "",
        "observaciones" => "UAT cierre sin diferencias"
    );
}

$captura = $modelo->capturarConteo(array(
    "id_conteo_inventario" => $idConteo,
    "items" => json_encode($items)
), 0);
if (!empty($captura["error"])) {
    echo json_encode(array("ok" => false, "paso" => "captura", "respuesta" => $captura), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}

$preview = $modelo->previewCerrarConteo(array("id_conteo_inventario" => $idConteo));
$cierre = $modelo->cerrarConteo(array(
    "id_conteo_inventario" => $idConteo,
    "observaciones" => "UAT cierre sin diferencias, sin movimiento de inventario"
), 0);

echo json_encode(array(
    "ok" => empty($cierre["error"]),
    "folio" => $folio,
    "id_conteo_inventario" => $idConteo,
    "captura" => $captura["depurar"],
    "preview" => isset($preview["depurar"]) ? $preview["depurar"] : array(),
    "cierre" => $cierre
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
