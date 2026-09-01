<?php

/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-31
 * Proposito: listar marcas publicas con estado de logo/banner para diagnostico CMS.
 * Impacto: UAT read-only; no escribe BD ni modifica marcas/productos.
 * Contrato: imprime resumen de /ecommercePublico/marcas derivado del modelo publico.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$cms = new EcommerceCatalogoPublico();
$respuesta = $cms->marcasPublicas(array("limite" => 300));
$items = isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
$conImagen = array();
$sinImagen = array();

foreach ($items as $item) {
  if (!is_array($item)) { continue; }
  $resumen = array(
    "marca_id" => isset($item["marca_id"]) ? $item["marca_id"] : (isset($item["id"]) ? $item["id"] : null),
    "nombre" => isset($item["nombre_publico"]) ? $item["nombre_publico"] : (isset($item["nombre"]) ? $item["nombre"] : ""),
    "slug" => isset($item["slug_publico"]) ? $item["slug_publico"] : (isset($item["slug"]) ? $item["slug"] : ""),
    "logo" => isset($item["logo"]) ? $item["logo"] : "",
    "imagen_banner" => isset($item["imagen_banner"]) ? $item["imagen_banner"] : ""
  );
  if (trim((string) $resumen["logo"]) !== "" || trim((string) $resumen["imagen_banner"]) !== "") {
    $conImagen[] = $resumen;
  } else {
    $sinImagen[] = $resumen;
  }
}

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "total" => count($items),
  "con_imagen_total" => count($conImagen),
  "sin_imagen_total" => count($sinImagen),
  "con_imagen_preview" => array_slice($conImagen, 0, 30),
  "sin_imagen_preview" => array_slice($sinImagen, 0, 30)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

