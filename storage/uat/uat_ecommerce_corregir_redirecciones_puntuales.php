<?php
/**
 * Correcciones puntuales autorizadas de redirecciones SEO.
 * Version IA: GPT-5 Codex, 2026-09-22.
 * Proposito: actualizar destinos 301 que apuntan a slugs inexistentes detectados en auditoria.
 * Impacto: escribe solo en `erp_ecommerce_seo_redirecciones`; no toca publicaciones ni sitemap.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

$correcciones = array(
  array(
    "from" => "/producto/-Juguete-para-gato-canatira-verde-/JUGG-08V",
    "to" => "/producto/juguete-para-gato-canatira-morada",
    "status" => 301,
    "tipo" => "producto",
    "motivo" => "correccion_destino_inexistente_seo_uat",
  ),
  array(
    "from" => "/producto/Aclarador-de-ojos-para-tortuga/ACLA-30",
    "to" => "/producto/aclarador-de-ojos-para-tortugas-30ml",
    "status" => 301,
    "tipo" => "producto",
    "motivo" => "correccion_slug_limpio_sin_sku_seo_uat",
  ),
);

$modelo = new EcommerceCatalogoPublico();
$resultados = array();
foreach ($correcciones as $correccion) {
  $resultados[] = $modelo->seoRedireccionGuardarAutorizada($correccion);
}

echo json_encode(array(
  "total" => count($resultados),
  "resultados" => $resultados,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
