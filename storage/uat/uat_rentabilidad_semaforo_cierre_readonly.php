<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatSemaforoResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_estado" => isset($primer["estado"]) ? $primer["estado"] : null,
        "primer_paso" => isset($primer["siguiente_paso"]) ? $primer["siguiente_paso"] : null
    );
}

$general = $modelo->semaforoCierre(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->semaforoCierre(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->semaforoCierre(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$incompleto = $modelo->semaforoCierre(array("q" => "0080", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->semaforoCierre(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatSemaforoResumen($general),
    "tp40372" => uatSemaforoResumen($tp40372),
    "tp40352" => uatSemaforoResumen($tp40352),
    "incompleto_0080" => uatSemaforoResumen($incompleto),
    "subir_precio" => uatSemaforoResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["tp40372"]["resumen"]["bloqueados"]) !== 0 || intval($salida["tp40372"]["resumen"]["precaucion"]) !== 5) {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["bloqueados"]) !== 0 || intval($salida["tp40352"]["resumen"]["precaucion"]) !== 2) {
    $ok = false;
}
if (intval($salida["incompleto_0080"]["resumen"]["bloqueados"]) < 1) {
    $ok = false;
}
if (intval($salida["subir_precio"]["resumen"]["bloqueados"]) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "semaforo" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
