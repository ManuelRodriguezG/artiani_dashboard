<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: localizar categorias publicas candidatas para el primer video de tortugueros.
 * Impacto: soporte read-only para relacionar videos editoriales por categoria sin asociarlos a producto.
 * Contrato: solo lectura; no escribe BD, no ejecuta DDL, no toca productos, ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();
$categorias = $catalogo->categoriasPublicas(array("limite" => 500));
$items = $categorias["depurar"]["items"] ?? array();
$terminos = array("tortug", "tortuguero", "reptil", "habitat", "acuario", "pecera");
$candidatas = array();

foreach ($items as $item) {
  $texto = normalizar_texto(($item["nombre_completo"] ?? "") . " " . ($item["path_slug"] ?? "") . " " . ($item["descripcion_corta"] ?? ""));
  $hits = array();
  foreach ($terminos as $termino) {
    if (strpos($texto, $termino) !== false) {
      $hits[] = $termino;
    }
  }
  if (empty($hits)) { continue; }
  $candidatas[] = array(
    "id" => $item["id"] ?? 0,
    "nombre" => $item["nombre"] ?? "",
    "nombre_completo" => $item["nombre_completo"] ?? "",
    "path_slug" => $item["path_slug"] ?? "",
    "url" => $item["url"] ?? "",
    "total_productos" => $item["total_productos"] ?? 0,
    "hits" => $hits
  );
}

usort($candidatas, function($a, $b) {
  $scoreA = score_categoria($a);
  $scoreB = score_categoria($b);
  if ($scoreA === $scoreB) {
    return strcmp((string) $a["path_slug"], (string) $b["path_slug"]);
  }
  return $scoreB <=> $scoreA;
});

echo json_encode(array(
  "ok" => !empty($candidatas),
  "modo" => "read-only",
  "senal_categoria_tortugueros" => !empty($candidatas) ? "candidatas_encontradas" : "sin_categoria_especifica",
  "candidatas" => array_slice($candidatas, 0, 12),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_toca_productos" => true,
    "no_toca_ventas" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function normalizar_texto($texto) {
  $texto = strtolower((string) $texto);
  $buscar = array("á", "é", "í", "ó", "ú", "ü", "ñ");
  $reemplazar = array("a", "e", "i", "o", "u", "u", "n");
  return str_replace($buscar, $reemplazar, $texto);
}

function score_categoria($item) {
  $texto = normalizar_texto(($item["nombre_completo"] ?? "") . " " . ($item["path_slug"] ?? ""));
  $score = 0;
  if (strpos($texto, "tortug") !== false) { $score += 100; }
  if (strpos($texto, "tortuguero") !== false) { $score += 100; }
  if (strpos($texto, "reptil") !== false) { $score += 80; }
  if (strpos($texto, "habitat") !== false) { $score += 50; }
  if (strpos($texto, "acuario") !== false) { $score += 20; }
  $score += min(20, intval($item["total_productos"] ?? 0));
  return $score;
}
