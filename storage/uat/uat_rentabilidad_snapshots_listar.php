<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$respuesta = $modelo->listarSnapshots(array());

echo json_encode(array(
    "ok" => !$respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "total" => isset($respuesta["depurar"]["items"]) ? count($respuesta["depurar"]["items"]) : 0,
    "primer_folio" => isset($respuesta["depurar"]["items"][0]["folio"]) ? $respuesta["depurar"]["items"][0]["folio"] : null,
    "primer_resumen" => isset($respuesta["depurar"]["items"][0]["resumen"]) ? $respuesta["depurar"]["items"][0]["resumen"] : null
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

