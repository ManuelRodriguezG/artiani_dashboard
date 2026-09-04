<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: validar postcheck read-only del esquema SEO/migracion ecommerce.
 * Impacto: confirma tablas SEO despues de aplicar DDL autorizado.
 * Contrato: read-only; no ejecuta DDL, no importa URLs y no crea redirecciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$schema = new EcommercePublicoEsquema();
$catalogo = new EcommerceCatalogoPublico();
$auditoria = $schema->auditarSeoMigracion();
$estado = $catalogo->seoEstadoPublico();
$urls = $catalogo->seoUrlsPublicas(array("limite" => 20));
$sitemap = $catalogo->seoSitemapPublico(array("limite" => 20));
$depurar = isset($auditoria["depurar"]) ? $auditoria["depurar"] : array();
$tablasFaltantes = intval(isset($depurar["tablas_faltantes"]) ? $depurar["tablas_faltantes"] : 0);
$urlsItems = valorSeoPostcheck($urls, array("depurar", "urls"), array());
$sitemapItems = valorSeoPostcheck($sitemap, array("depurar", "items"), array());
$sinApiInterna = strpos(json_encode($urlsItems), "/ecommercePublico") === false && strpos(json_encode($sitemapItems), "/ecommercePublico") === false;
$esquemaCompleto = $tablasFaltantes === 0;

echo json_encode(array(
  "ok" => empty($auditoria["error"]) && $sinApiInterna,
  "modo" => "read-only",
  "senal_schema_postcheck" => $esquemaCompleto ? "esquema_seo_migracion_completo" : "esquema_seo_migracion_pendiente",
  "esquema_completo" => $esquemaCompleto,
  "tablas_faltantes" => $tablasFaltantes,
  "auditoria" => isset($depurar["auditoria"]) ? $depurar["auditoria"] : array(),
  "seo" => array(
    "estado" => valorSeoPostcheck($estado, array("depurar"), array()),
    "urls_muestra" => count($urlsItems),
    "sitemap_muestra" => count($sitemapItems),
    "sin_api_interna" => $sinApiInterna
  ),
  "guardrails" => array(
    "read_only" => true,
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorSeoPostcheck($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
