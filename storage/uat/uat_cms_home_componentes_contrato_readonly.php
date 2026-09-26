<?php

/**
 * Documentacion IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: validar contrato publico CMS/Home para componentes separados y slots repetibles.
 * Impacto: UAT read-only; no publica, no edita contenido, no toca catalogo, precios ni inventario.
 * Contrato: consulta contenidoPaginaPublica(home) y resume secciones/slots para frontend.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$cms = new EcommerceCatalogoPublico();
$respuesta = $cms->contenidoPaginaPublica(array("pagina" => "home"));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$slots = isset($depurar["slots"]) && is_array($depurar["slots"]) ? $depurar["slots"] : array();
$secciones = isset($depurar["secciones"]) && is_array($depurar["secciones"]) ? $depurar["secciones"] : array();
$componentes = isset($depurar["componentes_home"]) && is_array($depurar["componentes_home"]) ? $depurar["componentes_home"] : array();

$slotsResumen = array();
foreach ($slots as $slot) {
  if (!is_array($slot)) { continue; }
  $bloques = isset($slot["bloques"]) && is_array($slot["bloques"]) ? $slot["bloques"] : array();
  $bloquesResumen = array();
  foreach ($bloques as $bloque) {
    if (!is_array($bloque)) { continue; }
    $items = isset($bloque["items"]) && is_array($bloque["items"]) ? $bloque["items"] : array();
    $bloquesResumen[] = array(
      "codigo" => isset($bloque["codigo"]) ? $bloque["codigo"] : "",
      "tipo" => isset($bloque["tipo"]) ? $bloque["tipo"] : "",
      "orden" => isset($bloque["orden"]) ? intval($bloque["orden"]) : 0,
      "visible" => array_key_exists("visible", $bloque) ? (bool) $bloque["visible"] : true,
      "items_total" => count($items)
    );
  }
  $slotsResumen[] = array(
    "slot" => isset($slot["slot"]) ? $slot["slot"] : "",
    "bloques_total" => count($bloques),
    "bloques" => $bloquesResumen
  );
}

$seccionesResumen = array();
foreach ($secciones as $seccion) {
  if (!is_array($seccion)) { continue; }
  $items = isset($seccion["items"]) && is_array($seccion["items"]) ? $seccion["items"] : array();
  $primerItem = isset($items[0]) && is_array($items[0]) ? $items[0] : array();
  $seccionesResumen[] = array(
    "codigo" => isset($seccion["codigo"]) ? $seccion["codigo"] : "",
    "tipo" => isset($seccion["tipo"]) ? $seccion["tipo"] : "",
    "slot" => isset($seccion["slot"]) ? $seccion["slot"] : "",
    "orden" => isset($seccion["orden"]) ? intval($seccion["orden"]) : 0,
    "visible" => array_key_exists("visible", $seccion) ? (bool) $seccion["visible"] : true,
    "items_total" => count($items),
    "primer_item_imagen_desktop" => isset($primerItem["imagen_desktop"]) ? $primerItem["imagen_desktop"] : "",
    "primer_item_url" => isset($primerItem["categoria"]["url"]) ? $primerItem["categoria"]["url"] : (isset($primerItem["url"]) ? $primerItem["url"] : "")
  );
}

echo json_encode(array(
  "error" => isset($respuesta["error"]) ? (bool) $respuesta["error"] : null,
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "fuente" => isset($depurar["fuente"]) ? $depurar["fuente"] : "",
  "version_contenido" => isset($depurar["version_contenido"]) ? $depurar["version_contenido"] : "",
  "componentes_home" => array(
    "publicados" => isset($componentes["publicados"]) ? (bool) $componentes["publicados"] : false,
    "modo" => isset($componentes["modo"]) ? $componentes["modo"] : "",
    "contrato" => isset($componentes["contrato"]) ? $componentes["contrato"] : "",
    "componentes_total" => isset($componentes["componentes_total"]) ? intval($componentes["componentes_total"]) : 0,
    "tipos_gestionados" => isset($componentes["tipos_gestionados"]) ? $componentes["tipos_gestionados"] : array(),
    "slots_gestionados" => isset($componentes["slots_gestionados"]) ? $componentes["slots_gestionados"] : array()
  ),
  "slots" => $slotsResumen,
  "secciones" => $seccionesResumen,
  "validacion" => array(
    "home_banner_ancho_completo_bloques" => count(array_filter($slotsResumen, function ($slot) {
      return isset($slot["slot"]) && $slot["slot"] === "home.banner_ancho_completo" && intval($slot["bloques_total"]) > 0;
    })),
    "home_banners_divididos_bloques" => count(array_filter($slotsResumen, function ($slot) {
      return isset($slot["slot"]) && $slot["slot"] === "home.banners_divididos" && intval($slot["bloques_total"]) > 0;
    })),
    "secciones_total" => count($seccionesResumen)
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
