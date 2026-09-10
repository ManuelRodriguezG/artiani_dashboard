<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: verificar estado de publicaciones/slugs y snapshot SEO canonico despues de aplicar cambios.
 * Impacto: diagnostico read-only para frontend ecommerce.
 * Contrato: no modifica BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$publicaciones = $pdo->query("SELECT COUNT(*) total,
    SUM(CASE WHEN slug IS NOT NULL AND TRIM(slug)<>'' THEN 1 ELSE 0 END) con_slug,
    SUM(CASE WHEN url_publica IS NOT NULL AND TRIM(url_publica)<>'' THEN 1 ELSE 0 END) con_url_publica,
    SUM(CASE WHEN canonical_url IS NOT NULL AND TRIM(canonical_url)<>'' THEN 1 ELSE 0 END) con_canonical,
    SUM(CASE WHEN slug REGEXP '-pza(-|$)' THEN 1 ELSE 0 END) slug_con_pza,
    SUM(CASE WHEN slug REGEXP '-[0-9]{4,}$' THEN 1 ELSE 0 END) slug_termina_codigo
  FROM erp_ecommerce_publicaciones")->fetch();

$seoUrls = $pdo->query("SELECT tipo, COUNT(*) total FROM erp_ecommerce_seo_urls GROUP BY tipo ORDER BY tipo")->fetchAll();
$seoUrlsActivas = $pdo->query("SELECT tipo, COUNT(*) total FROM erp_ecommerce_seo_urls WHERE activo=1 AND indexable=1 GROUP BY tipo ORDER BY tipo")->fetchAll();
$redirecciones = $pdo->query("SELECT COUNT(*) total,
    SUM(CASE WHEN activo=1 THEN 1 ELSE 0 END) activas,
    SUM(CASE WHEN tipo_entidad='producto' THEN 1 ELSE 0 END) relacionadas_producto
  FROM erp_ecommerce_seo_redirecciones")->fetch();
$residuales = $pdo->query("SELECT slug, titulo_publico
  FROM erp_ecommerce_publicaciones
  WHERE slug REGEXP '-pza(-|$)' OR slug REGEXP '-[0-9]{4,}$'
  ORDER BY id_publicacion
  LIMIT 20")->fetchAll();

echo json_encode(array(
  "ok" => true,
  "read_only" => true,
  "base" => MYSQLBASE,
  "publicaciones" => $publicaciones,
  "seo_urls_por_tipo" => $seoUrls,
  "seo_urls_activas_indexables_por_tipo" => $seoUrlsActivas,
  "redirecciones" => $redirecciones
  ,"slugs_residuales_ejemplos" => $residuales
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
