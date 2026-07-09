<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$respuesta = $modelo->listarAprobacionesInternas(array("q" => "TP-40372", "limite" => 30));
$resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();

$ok = empty($respuesta["error"])
    && intval(isset($resumen["schema_disponible"]) ? $resumen["schema_disponible"] : -1) === 0
    && intval(isset($resumen["total"]) ? $resumen["total"] : -1) === 0;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null,
    "resumen" => $resumen,
    "items" => isset($respuesta["depurar"]["items"]) ? count($respuesta["depurar"]["items"]) : null
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

