<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();
$respuesta = $catalogo->seoUrlsViejasRevisionInterna(array(
  "fuente" => "indexadas",
  "accion" => "relacionada_301",
  "limite" => 20
));

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
  "total_filtrado" => isset($respuesta["depurar"]["total_filtrado"]) ? $respuesta["depurar"]["total_filtrado"] : null,
  "items" => array_slice(isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array(), 0, 3)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
