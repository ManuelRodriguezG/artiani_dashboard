<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();
$confianza = isset($argv[1]) ? trim((string) $argv[1]) : "";
$respuesta = $catalogo->seoUrlsViejasRevisionInterna(array(
  "fuente" => "indexadas",
  "limite" => 20,
  "confianza" => $confianza
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$resumen = isset($depurar["resumen"]) && is_array($depurar["resumen"]) ? $depurar["resumen"] : array();
$confianzaResumen = isset($resumen["confianza"]) && is_array($resumen["confianza"]) ? $resumen["confianza"] : array();

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
  "fuente" => isset($depurar["fuente"]) ? $depurar["fuente"] : "",
  "total_reporte" => isset($depurar["total_reporte"]) ? $depurar["total_reporte"] : 0,
  "total_coincidencias" => isset($depurar["total_coincidencias"]) ? $depurar["total_coincidencias"] : 0,
  "items_lote" => isset($depurar["items"]) && is_array($depurar["items"]) ? count($depurar["items"]) : 0,
  "filtro_confianza" => $confianza,
  "total_resumen" => isset($resumen["total_resumen"]) ? $resumen["total_resumen"] : 0,
  "pendientes_no_exactas" => isset($resumen["pendientes_no_exactas"]) ? $resumen["pendientes_no_exactas"] : 0,
  "confianza" => array(
    "exacta" => isset($confianzaResumen["exacta"]) ? $confianzaResumen["exacta"] : 0,
    "alta" => isset($confianzaResumen["alta"]) ? $confianzaResumen["alta"] : 0,
    "media" => isset($confianzaResumen["media"]) ? $confianzaResumen["media"] : 0,
    "baja" => isset($confianzaResumen["baja"]) ? $confianzaResumen["baja"] : 0,
    "aprobada" => isset($confianzaResumen["aprobada"]) ? $confianzaResumen["aprobada"] : 0
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
