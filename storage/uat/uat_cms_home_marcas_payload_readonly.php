<?php

/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-31
 * Proposito: diagnosticar el payload publico de Home marcas destacadas.
 * Impacto: UAT read-only; no publica, no edita marcas, no toca catalogo, precios ni inventario.
 * Contrato: imprime resumen del slot `home.marcas` desde contenidoPaginaPublica.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$cms = new EcommerceCatalogoPublico();
$respuesta = $cms->contenidoPaginaPublica(array("pagina" => "home"));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$slots = isset($depurar["slots"]) && is_array($depurar["slots"]) ? $depurar["slots"] : array();
$slotMarcas = isset($slots["home.marcas"]) ? $slots["home.marcas"] : null;
if ($slotMarcas === null) {
  foreach ($slots as $slotItem) {
    if (!is_array($slotItem) || !isset($slotItem["slot"]) || (string) $slotItem["slot"] !== "home.marcas") { continue; }
    $slotMarcas = $slotItem;
    break;
  }
}

$bloques = array();
if (is_array($slotMarcas)) {
  if (isset($slotMarcas["bloques"]) && is_array($slotMarcas["bloques"])) {
    $bloques = $slotMarcas["bloques"];
  } elseif (isset($slotMarcas[0])) {
    $bloques = $slotMarcas;
  } else {
    $bloques = array($slotMarcas);
  }
}

$resumenBloques = array();
foreach ($bloques as $bloque) {
  if (!is_array($bloque)) { continue; }
  $payload = isset($bloque["payload"]) && is_array($bloque["payload"]) ? $bloque["payload"] : $bloque;
  $items = isset($payload["items"]) && is_array($payload["items"]) ? $payload["items"] : array();
  $imagenes = array();
  foreach ($items as $item) {
    if (!is_array($item)) { continue; }
    $imagenes[] = array(
      "marca_id" => isset($item["marca_id"]) ? $item["marca_id"] : (isset($item["id"]) ? $item["id"] : null),
      "nombre" => isset($item["nombre"]) ? $item["nombre"] : "",
      "logo" => isset($item["logo"]) ? $item["logo"] : "",
      "imagen_banner" => isset($item["imagen_banner"]) ? $item["imagen_banner"] : "",
      "tiene_imagen" => isset($item["tiene_imagen"]) ? $item["tiene_imagen"] : null,
      "estado_imagen" => isset($item["estado_imagen"]) ? $item["estado_imagen"] : ""
    );
  }
  $resumenBloques[] = array(
    "codigo" => isset($bloque["codigo"]) ? $bloque["codigo"] : (isset($payload["codigo"]) ? $payload["codigo"] : ""),
    "tipo" => isset($bloque["tipo"]) ? $bloque["tipo"] : (isset($payload["tipo"]) ? $payload["tipo"] : ""),
    "items_total" => count($items),
    "categoria_contexto" => isset($payload["categoria_contexto"]) ? $payload["categoria_contexto"] : null,
    "imagenes_items" => $imagenes
  );
}

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "slots_keys" => array_keys($slots),
  "home_marcas_existe" => $slotMarcas !== null,
  "home_marcas_tipo" => is_array($slotMarcas) ? "array" : gettype($slotMarcas),
  "bloques" => $resumenBloques
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
