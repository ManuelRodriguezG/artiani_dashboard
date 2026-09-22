<?php
/**
 * Diagnostico readonly de publicaciones ecommerce por slug, SKU o texto.
 * Version IA: GPT-5 Codex, 2026-09-22.
 * Proposito: ubicar rapidamente la publicacion, SKU, producto, SEO y redirecciones relacionados.
 * Impacto: no modifica datos; solo consulta informacion para soporte SEO/catalogo.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class UatEcommerceBuscarPublicacionReadonly extends CRUD
{
  public function db()
  {
    return $this->getConexion();
  }
}

$entrada = isset($argv[1]) ? trim((string)$argv[1]) : "";
if ($entrada === "") {
  fwrite(STDERR, "Uso: php storage/uat/uat_ecommerce_buscar_publicacion_readonly.php <slug|sku|texto>\n");
  exit(1);
}

$slug = strtolower(trim(parse_url($entrada, PHP_URL_PATH) ?: $entrada, "/"));
if (strpos($slug, "producto/") === 0) {
  $slug = substr($slug, strlen("producto/"));
}

$db = (new UatEcommerceBuscarPublicacionReadonly())->db();
$likeEntrada = "%" . $entrada . "%";
$likeSlug = "%" . $slug . "%";

$sqlPublicaciones = "
  SELECT
    pub.id_publicacion,
    pub.id_producto_erp,
    pub.id_sku,
    pub.slug,
    pub.url_publica,
    pub.canonical_url,
    pub.titulo_publico,
    pub.estatus_publicacion,
    pub.fecha_actualizacion,
    s.sku,
    s.nombre AS nombre_sku,
    s.estatus AS estatus_sku,
    p.nombre AS nombre_producto,
    p.descripcion AS descripcion_producto,
    p.estatus AS estatus_producto,
    m.nombre AS marca
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku = pub.id_sku
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = pub.id_producto_erp
  LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp = p.id_marca_erp
  WHERE pub.slug = :slug
    OR pub.url_publica = :path
    OR s.sku = :entrada
    OR pub.slug LIKE :like_slug
    OR pub.titulo_publico LIKE :like_entrada
    OR s.nombre LIKE :like_entrada
    OR p.nombre LIKE :like_entrada
    OR p.descripcion LIKE :like_entrada
  ORDER BY
    (pub.slug = :slug) DESC,
    (s.sku = :entrada) DESC,
    (pub.estatus_publicacion = 'publicado') DESC,
    pub.fecha_actualizacion DESC
  LIMIT 40
";
$stmt = $db->prepare($sqlPublicaciones);
$stmt->execute(array(
  ":slug" => $slug,
  ":path" => "/producto/" . $slug,
  ":entrada" => $entrada,
  ":like_slug" => $likeSlug,
  ":like_entrada" => $likeEntrada,
));
$publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtSeo = $db->prepare("
  SELECT *
  FROM erp_ecommerce_seo_urls
  WHERE path = :path OR path LIKE :like_path
  ORDER BY (path = :path) DESC, fecha_actualizacion DESC
  LIMIT 20
");
$stmtSeo->execute(array(
  ":path" => "/producto/" . $slug,
  ":like_path" => "%" . $slug . "%",
));
$seoUrls = $stmtSeo->fetchAll(PDO::FETCH_ASSOC);

$stmtRed = $db->prepare("
  SELECT id_redireccion, url_origen, url_destino, status_code, tipo, activo, revisado, motivo, fecha_actualizacion
  FROM erp_ecommerce_seo_redirecciones
  WHERE url_origen LIKE :like_path OR url_destino LIKE :like_path OR url_origen LIKE :like_entrada OR url_destino LIKE :like_entrada
  ORDER BY fecha_actualizacion DESC
  LIMIT 20
");
$stmtRed->execute(array(
  ":like_path" => "%" . $slug . "%",
  ":like_entrada" => $likeEntrada,
));
$redirecciones = $stmtRed->fetchAll(PDO::FETCH_ASSOC);

$catalogo = new EcommerceCatalogoPublico();
$api = $catalogo->productoPublico($slug);

$salida = array(
  "entrada" => $entrada,
  "slug_normalizado" => $slug,
  "publicaciones" => $publicaciones,
  "seo_urls" => $seoUrls,
  "redirecciones" => $redirecciones,
  "api_producto" => array(
    "error" => isset($api["error"]) ? $api["error"] : null,
    "tipo" => isset($api["tipo"]) ? $api["tipo"] : null,
    "mensaje" => isset($api["mensaje"]) ? $api["mensaje"] : null,
    "item_slug" => isset($api["item"]["slug"]) ? $api["item"]["slug"] : null,
    "item_id_publicacion" => isset($api["item"]["id_publicacion"]) ? $api["item"]["id_publicacion"] : null,
    "redirect_to" => isset($api["redirect_to"]) ? $api["redirect_to"] : null,
  ),
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
