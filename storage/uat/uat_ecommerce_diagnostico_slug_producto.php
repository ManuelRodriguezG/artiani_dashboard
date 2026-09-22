<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-22
 * Proposito: diagnosticar por que una URL publica de producto no es leida por frontend.
 * Impacto: Ecommerce SEO/frontend; solo lectura sobre publicaciones, sitemap, redirecciones y API publica.
 * Contrato: no escribe BD; no cambia slugs, redirecciones ni publicaciones.
 */

require __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class UatEcommerceDiagnosticoSlugProducto extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$entrada = isset($argv[1]) ? trim((string) $argv[1]) : "";
if ($entrada === "") {
  $entrada = "/producto/alimento-tropical-koi-y-goldfish-colour-sticks-4kg";
}

$modelo = new UatEcommerceDiagnosticoSlugProducto();
$api = new EcommerceCatalogoPublico();
$db = $modelo->conexion();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$path = parse_url($entrada, PHP_URL_PATH);
if (!$path) { $path = $entrada; }
$path = "/" . trim($path, "/");
$slug = preg_replace('/^\/producto\//', '', $path);
$slug = strtolower(trim((string) $slug));

$salida = array(
  "entrada" => $entrada,
  "path" => $path,
  "slug" => $slug,
  "exactas" => array(),
  "similares" => array(),
  "seo_urls" => array(),
  "redirecciones" => array(),
  "api_producto" => array(),
  "sitemap" => array(),
  "diagnostico" => array()
);

$stmt = $db->prepare("SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug, pub.url_publica,
    pub.canonical_url, pub.titulo_publico, pub.descripcion_publica, pub.estatus_publicacion, pub.canal,
    pub.mostrar_precio, pub.mostrar_disponibilidad, pub.fecha_publicacion, pub.fecha_actualizacion,
    s.sku, s.nombre nombre_sku, s.estatus estatus_sku,
    p.nombre nombre_producto, p.estatus estatus_producto,
    m.nombre marca
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
  LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
  WHERE pub.slug=:slug OR pub.url_publica=:path OR pub.canonical_url LIKE :canonical
  ORDER BY pub.id_publicacion ASC");
$stmt->execute(array(
  ":slug" => $slug,
  ":path" => $path,
  ":canonical" => "%/producto/" . $slug
));
$salida["exactas"] = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tokens = preg_split('/[-_\s]+/', $slug);
$tokens = array_values(array_filter($tokens, function ($t) {
  return strlen((string) $t) >= 3 && !in_array($t, array("para", "con", "los", "las", "del"), true);
}));
$where = array();
$params = array();
foreach (array_slice($tokens, 0, 8) as $i => $token) {
  $key = ":t" . $i;
  $where[] = "(pub.slug LIKE " . $key . " OR pub.titulo_publico LIKE " . $key . " OR s.nombre LIKE " . $key . " OR p.nombre LIKE " . $key . " OR s.sku LIKE " . $key . ")";
  $params[$key] = "%" . $token . "%";
}
if (!empty($where)) {
  $stmt = $db->prepare("SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug, pub.url_publica,
      pub.canonical_url, pub.titulo_publico, pub.estatus_publicacion,
      s.sku, s.nombre nombre_sku, s.estatus estatus_sku,
      p.nombre nombre_producto, p.estatus estatus_producto,
      m.nombre marca
    FROM erp_ecommerce_publicaciones pub
    INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
    LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
    WHERE " . implode(" OR ", $where) . "
    ORDER BY pub.estatus_publicacion='publicado' DESC, pub.slug=:slug DESC, pub.fecha_actualizacion DESC
    LIMIT 50");
  $params[":slug"] = $slug;
  $stmt->execute($params);
  $salida["similares"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (in_array("erp_ecommerce_seo_urls", $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN), true)) {
  $stmt = $db->prepare("SELECT id_url, tipo, entidad_id, path, url, canonical, title, indexable, activo, fecha_actualizacion
    FROM erp_ecommerce_seo_urls
    WHERE path=:path OR path LIKE :like
    ORDER BY activo DESC, id_url DESC
    LIMIT 20");
  $stmt->execute(array(":path" => $path, ":like" => "%" . $slug . "%"));
  $salida["seo_urls"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (in_array("erp_ecommerce_seo_redirecciones", $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN), true)) {
  $stmt = $db->prepare("SELECT id_redireccion, url_origen, url_destino, status_code, tipo, activo, revisado, motivo, fecha_actualizacion
    FROM erp_ecommerce_seo_redirecciones
    WHERE url_origen=:path OR url_destino=:path OR url_origen LIKE :like OR url_destino LIKE :like
    ORDER BY activo DESC, id_redireccion DESC
    LIMIT 50");
  $stmt->execute(array(":path" => $path, ":like" => "%" . $slug . "%"));
  $salida["redirecciones"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$respuestaApi = $api->productoPublico($slug);
$salida["api_producto"] = array(
  "error" => isset($respuestaApi["error"]) ? $respuestaApi["error"] : null,
  "tipo" => isset($respuestaApi["tipo"]) ? $respuestaApi["tipo"] : null,
  "mensaje" => isset($respuestaApi["mensaje"]) ? $respuestaApi["mensaje"] : null,
  "item_slug" => isset($respuestaApi["depurar"]["item"]["slug"]) ? $respuestaApi["depurar"]["item"]["slug"] : null,
  "item_id_publicacion" => isset($respuestaApi["depurar"]["item"]["id_publicacion"]) ? $respuestaApi["depurar"]["item"]["id_publicacion"] : null,
  "redirect_to" => isset($respuestaApi["depurar"]["redirect_to"]) ? $respuestaApi["depurar"]["redirect_to"] : null
);

$sitemap = $api->seoSitemapPublico(array("limite" => 5000, "limite_blog" => 1000));
$itemsSitemap = isset($sitemap["depurar"]["items"]) && is_array($sitemap["depurar"]["items"]) ? $sitemap["depurar"]["items"] : array();
foreach ($itemsSitemap as $item) {
  $loc = isset($item["loc"]) ? (string) $item["loc"] : "";
  if (strpos($loc, $path) !== false || strpos($loc, $slug) !== false) {
    $salida["sitemap"][] = $item;
  }
}

$exactaPublica = null;
foreach ($salida["exactas"] as $row) {
  if ((string) $row["slug"] === $slug && (string) $row["estatus_publicacion"] === "publicado" && (string) $row["estatus_sku"] === "activo" && (string) $row["estatus_producto"] === "activo") {
    $exactaPublica = $row;
  }
}

if (!$exactaPublica) {
  $salida["diagnostico"][] = "No existe una publicacion publica exacta para ese slug con publicacion=publicado, SKU=activo y producto=activo.";
}
if (empty($salida["seo_urls"])) {
  $salida["diagnostico"][] = "La ruta no aparece en el snapshot erp_ecommerce_seo_urls.";
}
if (empty($salida["sitemap"])) {
  $salida["diagnostico"][] = "La ruta no aparece en el sitemap publico actual.";
}
if ($salida["api_producto"]["tipo"] !== "success") {
  $salida["diagnostico"][] = "El endpoint /ecommercePublico/producto/{slug} no devuelve success para ese slug.";
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
