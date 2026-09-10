<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: probar sugerencias de migracion para una URL vieja especifica.
 * Impacto: diagnostico read-only de matching SEO.
 * Contrato: no modifica BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$args = argumentos($argv);
$q = isset($args["q"]) ? $args["q"] : "";
$limite = isset($args["limite"]) ? intval($args["limite"]) : 10;

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->seoUrlsViejasRevisionInterna(array(
  "fuente" => "indexadas",
  "q" => $q,
  "limite" => $limite
));

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}
