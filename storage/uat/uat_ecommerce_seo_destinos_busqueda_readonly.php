<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$tipo = isset($argv[1]) ? $argv[1] : "producto";
$q = isset($argv[2]) ? $argv[2] : "base";

$catalogo = new EcommerceCatalogoPublico();
$respuesta = $catalogo->seoDestinosCanonicosInterno(array(
  "tipo" => $tipo,
  "q" => $q,
  "limite" => 10
));

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "total_filtrado" => isset($respuesta["depurar"]["total_filtrado"]) ? $respuesta["depurar"]["total_filtrado"] : null,
  "items" => isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
