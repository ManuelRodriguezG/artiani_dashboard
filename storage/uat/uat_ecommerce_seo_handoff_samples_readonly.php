<?php
/**
 * Muestras compactas readonly para handoff SEO a frontend.
 * Version IA: GPT-5 Codex, 2026-09-23.
 * Proposito: extraer estructura real de redirecciones, sitemap y robots sin exponer datos innecesarios.
 * Impacto: solo lectura; no modifica BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$redirecciones = $modelo->seoRedireccionesPublicas(array());
$sitemap = $modelo->seoSitemapPublico(array("limite" => 5000));
$robots = $modelo->seoRobotsPublico(array());
$estado = $modelo->seoEstadoPublico(array());

$out = array(
  "estado_resumen" => array(
    "error" => $estado["error"] ?? null,
    "mensaje" => $estado["mensaje"] ?? "",
    "resumen" => $estado["depurar"]["resumen"] ?? array(),
  ),
  "redirecciones" => array(
    "total" => count($redirecciones["depurar"]["redirecciones"] ?? array()),
    "gone_total" => count($redirecciones["depurar"]["gone"] ?? array()),
    "sample" => array_slice($redirecciones["depurar"]["redirecciones"] ?? array(), 0, 3),
  ),
  "sitemap" => array(
    "total" => count($sitemap["depurar"]["items"] ?? array()),
    "sample" => array_slice($sitemap["depurar"]["items"] ?? array(), 0, 3),
  ),
  "robots" => array(
    "base_url" => $robots["depurar"]["base_url"] ?? "",
    "robots_txt" => $robots["depurar"]["robots_txt"] ?? "",
  ),
);

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
