<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: relacionar URLs viejas contra la estructura nueva por codigo/SKU y similitud de texto.
 * Impacto: mejora revision SEO antes de aprobar redirecciones 301.
 * Contrato: read-only; no escribe BD, no importa URLs y no crea redirecciones.
 */

$args = argumentosSeoRelacion($argv);
$archivoViejas = rutaSeoRelacion($args, "viejas");
$fuenteNuevas = valorSeoRelacion($args, "nuevas", "erp");
$limiteCanonicas = max(1, min(1000, intval(valorSeoRelacion($args, "limite_nuevas", 500))));
if ($archivoViejas === "") {
  salidaSeoRelacion(false, "Archivo de URLs viejas no legible", array(
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_seo_relacionar_urls_readonly.php --viejas=storage\\tmp\\ecommerce_seo_urls_viejas_reporte_enriquecido_YYYYMMDD_HHMMSS.json --nuevas=erp"
  ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();
$viejasPayload = json_decode(file_get_contents($archivoViejas), true);
$viejas = valorRutaSeoRelacion($viejasPayload, array("items"), array());
$nuevas = cargarNuevasSeoRelacion($catalogo, $fuenteNuevas, $limiteCanonicas);

$items = array();
$resumen = array(
  "accion" => array(),
  "confianza" => array(),
  "tipo" => array(),
  "fuente_nuevas" => $fuenteNuevas,
  "con_sugerencias" => 0,
  "sin_sugerencias" => 0
);
foreach ($viejas as $vieja) {
  $relacion = relacionarViejaSeo($vieja, $nuevas);
  $fila = $vieja;
  $fila["sugerencias"] = $relacion["sugerencias"];
  if (!empty($relacion["sugerencias"])) {
    $mejor = $relacion["sugerencias"][0];
    $fila["url_destino_sugerida"] = $mejor["path"];
    $fila["confianza"] = $mejor["confianza"];
    $fila["motivo"] = $mejor["motivo"];
    $fila["accion_sugerida"] = accionRelacionSeo($fila, $mejor);
    $fila["nota"] = notaRelacionSeo($fila, $mejor);
    $resumen["con_sugerencias"]++;
  } else {
    $fila["url_destino_sugerida"] = "";
    $fila["confianza"] = "baja";
    $fila["motivo"] = "sin_candidato_en_nuevas";
    $fila["accion_sugerida"] = accionSinRelacionSeo($fila);
    $fila["nota"] = "requiere_revision_manual";
    $resumen["sin_sugerencias"]++;
  }
  contarSeoRelacion($resumen["accion"], $fila["accion_sugerida"]);
  contarSeoRelacion($resumen["confianza"], $fila["confianza"]);
  contarSeoRelacion($resumen["tipo"], valorSeoRelacion($fila, "tipo_plan", "desconocido"));
  $items[] = $fila;
}

foreach (array("accion", "confianza", "tipo") as $key) {
  ksort($resumen[$key]);
}

usort($items, function ($a, $b) {
  $orden = array("sin_redireccion_necesaria" => 0, "aprobar_301_candidato" => 1, "validar_301_candidato" => 2, "revisar_manual" => 3, "excluir_o_410" => 4);
  $aa = isset($orden[$a["accion_sugerida"]]) ? $orden[$a["accion_sugerida"]] : 9;
  $ab = isset($orden[$b["accion_sugerida"]]) ? $orden[$b["accion_sugerida"]] : 9;
  if ($aa !== $ab) { return $aa - $ab; }
  return strcmp(valorSeoRelacion($a, "path_original", ""), valorSeoRelacion($b, "path_original", ""));
});

$outDir = realpath(__DIR__ . "/../tmp");
$timestamp = date("Ymd_His");
$jsonPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_relaciones_" . $timestamp . ".json";
$csvPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_relaciones_" . $timestamp . ".csv";

file_put_contents($jsonPath, json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "archivo_viejas" => $archivoViejas,
  "fuente_nuevas" => $fuenteNuevas,
  "total_viejas" => count($viejas),
  "total_nuevas" => count($nuevas),
  "resumen" => $resumen,
  "items" => $items,
  "guardrails" => array("no_escribe_bd" => true, "no_importa_urls" => true, "no_crea_redirecciones" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$csv = fopen($csvPath, "w");
fputcsv($csv, array("url_original", "path_original", "tipo", "status_http", "titulo_detectado", "destino_1", "confianza_1", "motivo_1", "destino_2", "destino_3", "accion_sugerida", "nota"));
foreach ($items as $item) {
  $sugs = valorSeoRelacion($item, "sugerencias", array());
  fputcsv($csv, array(
    valorSeoRelacion($item, "url_original", ""),
    valorSeoRelacion($item, "path_original", ""),
    valorSeoRelacion($item, "tipo_plan", ""),
    valorSeoRelacion($item, "status_http", ""),
    valorSeoRelacion($item, "titulo_detectado", ""),
    valorRutaSeoRelacion($sugs, array(0, "path"), ""),
    valorRutaSeoRelacion($sugs, array(0, "confianza"), ""),
    valorRutaSeoRelacion($sugs, array(0, "motivo"), ""),
    valorRutaSeoRelacion($sugs, array(1, "path"), ""),
    valorRutaSeoRelacion($sugs, array(2, "path"), ""),
    valorSeoRelacion($item, "accion_sugerida", ""),
    valorSeoRelacion($item, "nota", "")
  ));
}
fclose($csv);

salidaSeoRelacion(true, "Relaciones SEO generadas", array(
  "total_viejas" => count($viejas),
  "total_nuevas" => count($nuevas),
  "resumen" => $resumen,
  "archivos" => array("json" => $jsonPath, "csv" => $csvPath),
  "muestra" => array_slice($items, 0, 15),
  "guardrails" => array("no_escribe_bd" => true, "no_importa_urls" => true, "no_crea_redirecciones" => true)
));

function cargarNuevasSeoRelacion($catalogo, $fuente, $limite) {
  if ($fuente !== "erp") {
    $archivo = realpath($fuente);
    if ($archivo && is_readable($archivo)) {
      $payload = json_decode(file_get_contents($archivo), true);
      $items = valorRutaSeoRelacion($payload, array("items"), array());
      return normalizarNuevasSeoRelacion($items);
    }
  }
  $resp = $catalogo->seoUrlsPublicas(array("limite" => $limite));
  return normalizarNuevasSeoRelacion(valorRutaSeoRelacion($resp, array("depurar", "urls"), array()));
}

function normalizarNuevasSeoRelacion($items) {
  $salida = array();
  foreach ((array) $items as $item) {
    $path = normalizarPathSeoRelacion(valorSeoRelacion($item, "path", valorSeoRelacion($item, "url", "")));
    if ($path === "") { continue; }
    $tipo = valorSeoRelacion($item, "tipo", valorSeoRelacion($item, "tipo_detectado", "url"));
    $texto = implode(" ", array($path, valorSeoRelacion($item, "title", ""), valorSeoRelacion($item, "titulo", ""), valorSeoRelacion($item, "description", ""), valorSeoRelacion($item, "sku", "")));
    $salida[] = array(
      "tipo" => $tipo,
      "path" => $path,
      "title" => valorSeoRelacion($item, "title", valorSeoRelacion($item, "titulo", "")),
      "sku" => skuSeoRelacion(valorSeoRelacion($item, "sku", "")),
      "tokens" => tokensSeoRelacion($texto),
      "codigos" => codigosSeoRelacion($texto)
    );
  }
  return $salida;
}

function relacionarViejaSeo($vieja, $nuevas) {
  $path = normalizarPathSeoRelacion(valorSeoRelacion($vieja, "path_original", valorSeoRelacion($vieja, "url_original", "")));
  $tipo = tipoComparableSeoRelacion(valorSeoRelacion($vieja, "tipo_plan", valorSeoRelacion($vieja, "tipo_crawl", "desconocido")));
  $textoViejo = implode(" ", array($path, valorSeoRelacion($vieja, "titulo_detectado", "")));
  $tokensViejos = tokensSeoRelacion($textoViejo);
  $codigosViejos = codigosSeoRelacion($textoViejo);
  $skuViejo = skuSeoRelacion(basename(parse_url($path, PHP_URL_PATH) ?: ""));
  $candidatos = array();

  foreach ($nuevas as $nueva) {
    $tipoNueva = tipoComparableSeoRelacion($nueva["tipo"]);
    if ($tipo !== "desconocido" && $tipoNueva !== $tipo) { continue; }
    $score = 0;
    $motivos = array();
    if ($path === $nueva["path"]) {
      $score += 100;
      $motivos[] = "path_exacto";
    }
    if ($skuViejo !== "" && $nueva["sku"] !== "" && $skuViejo === $nueva["sku"]) {
      $score += 90;
      $motivos[] = "sku_exacto";
    }
    $codigosCompartidos = array_values(array_intersect($codigosViejos, $nueva["codigos"]));
    if (!empty($codigosCompartidos)) {
      $score += min(60, count($codigosCompartidos) * 25);
      $motivos[] = "codigos_" . implode("-", array_slice($codigosCompartidos, 0, 3));
    }
    $tokensCompartidos = array_values(array_intersect($tokensViejos, $nueva["tokens"]));
    $score += count($tokensCompartidos) * 6;
    if (!empty($tokensCompartidos)) {
      $motivos[] = "tokens_" . count($tokensCompartidos);
    }
    $score -= penalizacionVariantesSeoRelacion($tokensViejos, $nueva["tokens"]);
    if ($score < 18) { continue; }
    $candidatos[] = array(
      "path" => $nueva["path"],
      "tipo" => $nueva["tipo"],
      "title" => $nueva["title"],
      "score" => $score,
      "confianza" => confianzaSeoRelacion($score, $motivos),
      "motivo" => implode("+", $motivos)
    );
  }

  usort($candidatos, function ($a, $b) {
    if ($a["score"] !== $b["score"]) { return $b["score"] - $a["score"]; }
    return strlen($a["path"]) - strlen($b["path"]);
  });
  return array("sugerencias" => array_slice($candidatos, 0, 3));
}

function penalizacionVariantesSeoRelacion($origen, $destino) {
  $medidasOrigen = array_values(array_filter($origen, "esMedidaSeoRelacion"));
  $medidasDestino = array_values(array_filter($destino, "esMedidaSeoRelacion"));
  if (empty($medidasOrigen) || empty($medidasDestino)) { return 0; }
  return empty(array_intersect($medidasOrigen, $medidasDestino)) ? 12 : 0;
}

function esMedidaSeoRelacion($token) {
  return preg_match('/^[0-9]+(kg|gr|g|ml|lt|lts|cm|mm)?$/', (string) $token) === 1 || preg_match('/^[0-9]+x[0-9]+/', (string) $token) === 1;
}

function accionRelacionSeo($fila, $mejor) {
  if (esBasuraPlantillaSeoRelacion(valorSeoRelacion($fila, "path_original", ""))) { return "excluir_o_410"; }
  if (intval(valorSeoRelacion($fila, "status_http", 0)) >= 400) { return "excluir_o_410"; }
  if (normalizarPathSeoRelacion(valorSeoRelacion($fila, "path_original", "")) === normalizarPathSeoRelacion(valorSeoRelacion($mejor, "path", ""))) { return "sin_redireccion_necesaria"; }
  if (in_array($mejor["confianza"], array("exacta", "alta"), true)) { return "aprobar_301_candidato"; }
  return "validar_301_candidato";
}

function accionSinRelacionSeo($fila) {
  if (esBasuraPlantillaSeoRelacion(valorSeoRelacion($fila, "path_original", ""))) { return "excluir_o_410"; }
  if (intval(valorSeoRelacion($fila, "status_http", 0)) >= 400) { return "excluir_o_410"; }
  return "revisar_manual";
}

function notaRelacionSeo($fila, $mejor) {
  if (esBasuraPlantillaSeoRelacion(valorSeoRelacion($fila, "path_original", ""))) { return "ruta_plantilla_legacy"; }
  if (normalizarPathSeoRelacion(valorSeoRelacion($fila, "path_original", "")) === normalizarPathSeoRelacion(valorSeoRelacion($mejor, "path", ""))) { return "misma_uri_se_conserva"; }
  if ($mejor["confianza"] === "exacta" || $mejor["confianza"] === "alta") { return "validar_visual_y_aprobar"; }
  return "validar_variante_marca_tamano_codigo";
}

function esBasuraPlantillaSeoRelacion($path) {
  return preg_match('/(index-skin|empty-cart|listing-|collections\/|\/product\.html|\/carrito\/product\.html)/', strtolower((string) $path)) === 1;
}

function confianzaSeoRelacion($score, $motivos) {
  if (in_array("path_exacto", $motivos, true) || in_array("sku_exacto", $motivos, true) || $score >= 90) { return "exacta"; }
  if ($score >= 55) { return "alta"; }
  if ($score >= 28) { return "media"; }
  return "baja";
}

function tipoComparableSeoRelacion($tipo) {
  $tipo = strtolower((string) $tipo);
  if (strpos($tipo, "producto") !== false) { return "producto"; }
  if (strpos($tipo, "categoria") !== false || $tipo === "clasificacion") { return "categoria"; }
  if (strpos($tipo, "marca") !== false) { return "marca"; }
  if ($tipo === "home" || $tipo === "contacto") { return $tipo; }
  return "desconocido";
}

function tokensSeoRelacion($texto) {
  $texto = strtolower(normalizarTextoSeoRelacion((string) $texto));
  $texto = preg_replace('/[^a-z0-9]+/', ' ', $texto);
  $tokens = array();
  foreach (preg_split('/\s+/', trim($texto)) as $token) {
    if (strlen($token) < 3 || in_array($token, array("https", "http", "www", "com", "mx", "producto", "categoria", "marca", "artiani", "pza", "para", "con", "los", "las", "del"), true)) {
      continue;
    }
    $tokens[] = $token;
  }
  return array_values(array_unique($tokens));
}

function codigosSeoRelacion($texto) {
  $texto = strtoupper((string) $texto);
  preg_match_all('/\b[A-Z]{2,}[A-Z0-9-]*[0-9][A-Z0-9-]*\b|\b[0-9]{3,}[A-Z0-9-]*\b/', $texto, $m);
  $codigos = array();
  foreach ($m[0] as $codigo) {
    $sku = skuSeoRelacion($codigo);
    if ($sku !== "") { $codigos[] = $sku; }
  }
  return array_values(array_unique($codigos));
}

function skuSeoRelacion($sku) {
  $sku = strtoupper(trim((string) $sku));
  $sku = preg_replace('/[^A-Z0-9]/', '', $sku);
  return strlen($sku) >= 4 ? $sku : "";
}

function normalizarTextoSeoRelacion($texto) {
  $mapa = array("á"=>"a","é"=>"e","í"=>"i","ó"=>"o","ú"=>"u","ü"=>"u","ñ"=>"n","Á"=>"A","É"=>"E","Í"=>"I","Ó"=>"O","Ú"=>"U","Ü"=>"U","Ñ"=>"N");
  return strtr((string) $texto, $mapa);
}

function normalizarPathSeoRelacion($path) {
  $path = trim((string) $path);
  if ($path === "") { return ""; }
  if (preg_match('/^https?:\/\//i', $path)) {
    $partes = parse_url($path);
    $path = isset($partes["path"]) ? $partes["path"] : "/";
    if (!empty($partes["query"])) { $path .= "?" . $partes["query"]; }
  }
  $path = "/" . ltrim($path, "/");
  return preg_replace('/\/+/', '/', $path);
}

function rutaSeoRelacion($args, $key) {
  $valor = valorSeoRelacion($args, $key, "");
  if ($valor === "" || !is_file($valor) || !is_readable($valor)) { return ""; }
  return realpath($valor);
}

function argumentosSeoRelacion($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function valorSeoRelacion($datos, $key, $default = null) {
  return is_array($datos) && array_key_exists($key, $datos) ? $datos[$key] : $default;
}

function valorRutaSeoRelacion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) { return $default; }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function contarSeoRelacion(&$items, $key) {
  $key = (string) $key;
  if (!isset($items[$key])) { $items[$key] = 0; }
  $items[$key]++;
}

function salidaSeoRelacion($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
