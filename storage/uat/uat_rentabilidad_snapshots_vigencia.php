<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$respuesta = $modelo->auditarVigenciaSnapshots(array("limite" => 10));
$items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
$desfasados = 0;
$primer = null;
foreach ($items as $item) {
    if ($item["vigencia"] !== "vigente") {
        $desfasados++;
        if ($primer === null) {
            $primer = $item;
        }
    }
}

echo json_encode(array(
    "ok" => !$respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "total" => count($items),
    "desfasados" => $desfasados,
    "primer_desfasado" => $primer
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
