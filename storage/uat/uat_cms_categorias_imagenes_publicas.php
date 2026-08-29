<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-29.
 * Proposito: verificar que /ecommercePublico/categorias exponga imagenes maestras de categoria al CMS Home.
 * Impacto: CMS Frontend; permite reutilizar imagenes del Catalogo ERP en Esenciales sin duplicarlas en Media CMS.
 * Contrato: solo lectura; no modifica categorias, media, catalogo, precios ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->categoriasPublicas(array("limite" => 500));
$items = isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"])
  ? $respuesta["depurar"]["items"]
  : array();

$conImagenCard = 0;
$conImagenBanner = 0;
$conImagenesCatalogo = 0;
$ejemplos = array();

foreach ($items as $item) {
  $tieneCard = !empty($item["imagen_card"]);
  $tieneBanner = !empty($item["imagen_banner"]);
  $tieneCatalogo = !empty($item["imagenes_catalogo"]) && is_array($item["imagenes_catalogo"]);
  if ($tieneCard) { $conImagenCard++; }
  if ($tieneBanner) { $conImagenBanner++; }
  if ($tieneCatalogo) { $conImagenesCatalogo++; }
  if (($tieneCard || $tieneBanner || $tieneCatalogo) && count($ejemplos) < 8) {
    $ejemplos[] = array(
      "id" => isset($item["id"]) ? $item["id"] : null,
      "nombre" => isset($item["nombre"]) ? $item["nombre"] : "",
      "imagen_card" => isset($item["imagen_card"]) ? $item["imagen_card"] : null,
      "imagen_banner" => isset($item["imagen_banner"]) ? $item["imagen_banner"] : null,
      "imagenes_catalogo_total" => $tieneCatalogo ? count($item["imagenes_catalogo"]) : 0
    );
  }
}

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "total_categorias" => count($items),
  "categorias_con_imagen_card" => $conImagenCard,
  "categorias_con_imagen_banner" => $conImagenBanner,
  "categorias_con_imagenes_catalogo" => $conImagenesCatalogo,
  "ejemplos" => $ejemplos,
  "nota" => "Si los conteos salen en 0, revisa que las imagenes de categoria esten activas en erp_catalogo_categoria_imagenes."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
