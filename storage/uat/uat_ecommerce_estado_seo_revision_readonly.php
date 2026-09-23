<?php
/**
 * Estado resumido readonly de verificacion SEO.
 * Version IA: GPT-5 Codex, 2026-09-23.
 * Proposito: confirmar avance de destinos, origenes, sitemap y redirecciones antes de cierre frontend.
 * Impacto: solo lectura; no modifica BD ni ejecuta HTTP.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class UatEcommerceEstadoSeoRevisionReadonly extends CRUD
{
  public function db()
  {
    return $this->getConexion();
  }
}

$db = (new UatEcommerceEstadoSeoRevisionReadonly())->db();
$modelo = new EcommerceCatalogoPublico();

$redirecciones = $modelo->seoRedireccionesPublicas(array());
$sitemap = $modelo->seoSitemapPublico(array("limite" => 5000));
$errores = $modelo->seoRedireccionesErroresReporteInterno(array());

$out = array(
  "redirecciones_activas" => count((array)($redirecciones["depurar"]["redirecciones"] ?? array())),
  "urls_410_activas" => count((array)($redirecciones["depurar"]["gone"] ?? array())),
  "sitemap_urls" => count((array)($sitemap["depurar"]["items"] ?? array())),
  "errores_accionables_reporte" => intval($errores["depurar"]["total"] ?? 0),
);

if ($db && tableExists($db, "erp_ecommerce_seo_verificaciones")) {
  $out["destinos_ok"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_seo_verificaciones WHERE tipo='regla_destino' AND probada=1")->fetchColumn());
  $out["origenes_ok"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_seo_verificaciones WHERE tipo='regla_origen' AND probada=1")->fetchColumn());
  $out["sitemap_ok"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_seo_verificaciones WHERE tipo='sitemap' AND probada=1")->fetchColumn());
} else {
  $out["destinos_ok"] = 0;
  $out["origenes_ok"] = 0;
  $out["sitemap_ok"] = 0;
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tableExists(PDO $db, $table)
{
  $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
  $stmt->execute(array(":tabla" => $table));
  return (bool)$stmt->fetchColumn();
}
