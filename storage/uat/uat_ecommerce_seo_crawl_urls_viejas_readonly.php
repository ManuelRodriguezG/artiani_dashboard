<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: rastrear URLs publicas actuales de artiani.com.mx para preparar migracion SEO.
 * Impacto: genera inventario de URLs viejas por path para compararlas contra canonicas nuevas.
 * Contrato: read-only; solo hace GET/HEAD publicos y escribe archivos de evidencia en storage/tmp.
 */

$args = argumentosSeoCrawl($argv);
$base = rtrim(valorSeoCrawl($args, "base", "https://artiani.com.mx"), "/");
$maxPaginas = max(1, min(1000, intval(valorSeoCrawl($args, "max", 250))));
$delayMs = max(0, min(2000, intval(valorSeoCrawl($args, "delay_ms", 120))));
$outDir = realpath(__DIR__ . "/../tmp");
if (!$outDir) {
  mkdir(__DIR__ . "/../tmp", 0775, true);
  $outDir = realpath(__DIR__ . "/../tmp");
}

$hostBase = parse_url($base, PHP_URL_HOST);
$hostsPermitidos = array_values(array_unique(array_filter(array(
  $hostBase,
  preg_replace('/^www\./', '', (string) $hostBase),
  "www." . preg_replace('/^www\./', '', (string) $hostBase)
))));
$colas = array($base . "/", $base . "/robots.txt", $base . "/sitemap.xml");
$visitadas = array();
$descubiertas = array();
$paginas = array();
$errores = array();
$inicio = microtime(true);

while (!empty($colas) && count($visitadas) < $maxPaginas) {
  $url = array_shift($colas);
  $url = normalizarUrlSeoCrawl($url, $base, $hostsPermitidos);
  if ($url === "" || isset($visitadas[$url])) {
    continue;
  }
  $visitadas[$url] = true;
  $resp = fetchSeoCrawl($url);
  $path = pathSeoCrawl($url);
  $tipo = tipoSeoCrawl($path, $resp["content_type"]);
  $descubiertas[$path] = array(
    "url" => $url,
    "path" => $path,
    "tipo_detectado" => $tipo,
    "status" => $resp["status"],
    "content_type" => $resp["content_type"],
    "titulo" => tituloSeoCrawl($resp["body"]),
    "fuente" => "crawl"
  );
  $paginas[] = $descubiertas[$path];

  if ($resp["status"] >= 400 || $resp["body"] === "") {
    $errores[] = array("url" => $url, "status" => $resp["status"], "error" => $resp["error"]);
    continue;
  }

  $links = extraerLinksSeoCrawl($resp["body"], $url, $base, $hostsPermitidos);
  foreach ($links as $link) {
    if (count($visitadas) + count($colas) >= $maxPaginas * 3) {
      break;
    }
    if (!isset($visitadas[$link]) && !in_array($link, $colas, true)) {
      $colas[] = $link;
    }
  }

  if ($delayMs > 0) {
    usleep($delayMs * 1000);
  }
}

ksort($descubiertas);
$timestamp = date("Ymd_His");
$jsonPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_crawl_" . $timestamp . ".json";
$csvPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_crawl_" . $timestamp . ".csv";
$urlsTextoPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_import_" . $timestamp . ".txt";
$items = array_values($descubiertas);

file_put_contents($jsonPath, json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base" => $base,
  "hosts_permitidos" => $hostsPermitidos,
  "total_urls" => count($items),
  "total_paginas_visitadas" => count($paginas),
  "total_errores" => count($errores),
  "duracion_segundos" => round(microtime(true) - $inicio, 3),
  "items" => $items,
  "errores" => $errores,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true,
    "mismo_dominio" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$csv = fopen($csvPath, "w");
fputcsv($csv, array("url", "path", "tipo_detectado", "status", "content_type", "titulo", "fuente"));
foreach ($items as $item) {
  fputcsv($csv, array(
    $item["url"],
    $item["path"],
    $item["tipo_detectado"],
    $item["status"],
    $item["content_type"],
    $item["titulo"],
    $item["fuente"]
  ));
}
fclose($csv);
file_put_contents($urlsTextoPath, implode(PHP_EOL, array_map(function ($item) {
  return $item["url"];
}, $items)) . PHP_EOL);

$tipos = array();
foreach ($items as $item) {
  $tipo = $item["tipo_detectado"];
  if (!isset($tipos[$tipo])) { $tipos[$tipo] = 0; }
  $tipos[$tipo]++;
}
ksort($tipos);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base" => $base,
  "total_urls" => count($items),
  "total_paginas_visitadas" => count($paginas),
  "total_errores" => count($errores),
  "tipos" => $tipos,
  "archivos" => array(
    "json" => $jsonPath,
    "csv" => $csvPath,
    "urls_texto" => $urlsTextoPath
  ),
  "muestra" => array_slice($items, 0, 12),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function argumentosSeoCrawl($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function valorSeoCrawl($datos, $key, $default = null) {
  return is_array($datos) && array_key_exists($key, $datos) ? $datos[$key] : $default;
}

function fetchSeoCrawl($url) {
  $ch = curl_init($url);
  curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 5,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_USERAGENT => "ArtianiSeoMigrationBot/1.0 (+https://artiani.com.mx)",
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => ""
  ));
  $body = curl_exec($ch);
  $error = curl_error($ch);
  $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
  $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  curl_close($ch);
  return array(
    "status" => $status,
    "content_type" => $contentType,
    "body" => is_string($body) ? $body : "",
    "error" => $error
  );
}

function extraerLinksSeoCrawl($body, $urlActual, $base, $hostsPermitidos) {
  $links = array();
  if (preg_match_all('/(?:href|src)=["\']([^"\']+)["\']/i', $body, $matches)) {
    foreach ($matches[1] as $href) {
      $normalizada = normalizarUrlSeoCrawl($href, $urlActual, $hostsPermitidos);
      if ($normalizada !== "" && !assetSeoCrawl($normalizada)) {
        $links[$normalizada] = true;
      }
    }
  }
  if (stripos($body, "<urlset") !== false && preg_match_all('/<loc>\s*([^<]+)\s*<\/loc>/i', $body, $matches)) {
    foreach ($matches[1] as $loc) {
      $normalizada = normalizarUrlSeoCrawl(html_entity_decode($loc, ENT_QUOTES, "UTF-8"), $base, $hostsPermitidos);
      if ($normalizada !== "" && !assetSeoCrawl($normalizada)) {
        $links[$normalizada] = true;
      }
    }
  }
  if (stripos($body, "<sitemapindex") !== false && preg_match_all('/<loc>\s*([^<]+)\s*<\/loc>/i', $body, $matches)) {
    foreach ($matches[1] as $loc) {
      $normalizada = normalizarUrlSeoCrawl(html_entity_decode($loc, ENT_QUOTES, "UTF-8"), $base, $hostsPermitidos);
      if ($normalizada !== "") {
        $links[$normalizada] = true;
      }
    }
  }
  if (preg_match_all('/^Sitemap:\s*(\S+)/im', $body, $matches)) {
    foreach ($matches[1] as $loc) {
      $normalizada = normalizarUrlSeoCrawl($loc, $base, $hostsPermitidos);
      if ($normalizada !== "") {
        $links[$normalizada] = true;
      }
    }
  }
  return array_keys($links);
}

function normalizarUrlSeoCrawl($href, $base, $hostsPermitidos) {
  $href = trim(html_entity_decode((string) $href, ENT_QUOTES, "UTF-8"));
  if ($href === "" || preg_match('/^(javascript:|mailto:|tel:|whatsapp:|data:|blob:|#)/i', $href)) {
    return "";
  }
  if (strpos($href, "//") === 0) {
    $baseScheme = parse_url($base, PHP_URL_SCHEME) ?: "https";
    $href = $baseScheme . ":" . $href;
  } elseif (!preg_match('/^https?:\/\//i', $href)) {
    $href = resolverRelativaSeoCrawl($href, $base);
  }
  $partes = parse_url($href);
  if (!$partes || empty($partes["host"])) {
    return "";
  }
  $host = strtolower($partes["host"]);
  if (!in_array($host, array_map("strtolower", $hostsPermitidos), true)) {
    return "";
  }
  $scheme = "https";
  $path = isset($partes["path"]) ? $partes["path"] : "/";
  $path = "/" . ltrim($path, "/");
  $path = preg_replace('/\/+/', '/', $path);
  $query = isset($partes["query"]) && $partes["query"] !== "" ? "?" . $partes["query"] : "";
  return $scheme . "://" . $host . $path . $query;
}

function resolverRelativaSeoCrawl($href, $base) {
  $partes = parse_url($base);
  $scheme = isset($partes["scheme"]) ? $partes["scheme"] : "https";
  $host = isset($partes["host"]) ? $partes["host"] : "";
  $basePath = isset($partes["path"]) ? $partes["path"] : "/";
  if (strpos($href, "/") === 0) {
    return $scheme . "://" . $host . $href;
  }
  $dir = preg_replace('/\/[^\/]*$/', "/", $basePath);
  return $scheme . "://" . $host . $dir . $href;
}

function assetSeoCrawl($url) {
  $path = strtolower(parse_url($url, PHP_URL_PATH) ?: "");
  return preg_match('/\.(jpg|jpeg|png|gif|webp|svg|css|js|ico|woff|woff2|ttf|eot|pdf|zip|rar|mp4|webm|mp3)$/', $path) === 1;
}

function pathSeoCrawl($url) {
  $partes = parse_url($url);
  $path = isset($partes["path"]) ? $partes["path"] : "/";
  $path = "/" . ltrim($path, "/");
  $path = preg_replace('/\/+/', '/', $path);
  if (!empty($partes["query"])) {
    $path .= "?" . $partes["query"];
  }
  return $path;
}

function tipoSeoCrawl($path, $contentType) {
  $pathLower = strtolower($path);
  if (strpos($pathLower, "sitemap") !== false || stripos($contentType, "xml") !== false) { return "sitemap"; }
  if ($pathLower === "/" || $pathLower === "") { return "home"; }
  if (strpos($pathLower, "/producto/clasificacion/") === 0) { return "clasificacion"; }
  if (strpos($pathLower, "/producto/categoria/") === 0) { return "categoria_legacy"; }
  if (strpos($pathLower, "/producto/marca/") === 0) { return "marca_legacy"; }
  if (strpos($pathLower, "/producto/") === 0) { return "producto_legacy"; }
  if (strpos($pathLower, "/categoria") === 0) { return "categoria"; }
  if (strpos($pathLower, "/contacto") === 0) { return "contacto"; }
  if (strpos($pathLower, "/como") === 0) { return "como_comprar"; }
  return "otro";
}

function tituloSeoCrawl($body) {
  if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
    return limpiarTextoSeoCrawl($m[1]);
  }
  if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $body, $m)) {
    return limpiarTextoSeoCrawl($m[1]);
  }
  return "";
}

function limpiarTextoSeoCrawl($texto) {
  $texto = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES, "UTF-8");
  $texto = preg_replace('/\s+/', ' ', $texto);
  return trim($texto);
}
