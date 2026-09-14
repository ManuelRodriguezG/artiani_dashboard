<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

class EcommerceCatalogoPublicoSeoAuditDebug extends EcommerceCatalogoPublico {
  public function conexionDebug() {
    return $this->getConexion();
  }
}

function seo_audit_slug_local($texto) {
  $texto = strtolower(trim((string) $texto));
  $transliterado = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
  if ($transliterado !== false) {
    $texto = strtolower($transliterado);
  }
  $texto = str_replace(array("&", "+"), " y ", $texto);
  $texto = preg_replace('/[\'"`´]+/', '', $texto);
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  return trim($texto, "-");
}

function seo_audit_similitud($a, $b) {
  $a = seo_audit_slug_local($a);
  $b = seo_audit_slug_local($b);
  if ($a === "" || $b === "") { return 0; }
  similar_text($a, $b, $pct);
  return round($pct, 2);
}

$modelo = new EcommerceCatalogoPublicoSeoAuditDebug();
$db = $modelo->conexionDebug();
if (!$db) {
  echo json_encode(array("ok" => false, "error" => "conexion_no_disponible"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$sql = "SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug, pub.url_publica,
    pub.titulo_publico, pub.estatus_publicacion, s.sku, s.nombre nombre_sku, p.nombre nombre_producto,
    t.total_publicaciones_producto
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
  INNER JOIN (
    SELECT id_producto_erp, COUNT(*) total_publicaciones_producto
    FROM erp_ecommerce_publicaciones
    WHERE canal='catalogo_publico' AND estatus_publicacion IN ('publicado','borrador','pausado')
    GROUP BY id_producto_erp
    HAVING COUNT(*) > 1
  ) t ON t.id_producto_erp=pub.id_producto_erp
  WHERE pub.canal='catalogo_publico'
    AND pub.estatus_publicacion IN ('publicado','borrador','pausado')
  ORDER BY pub.id_producto_erp ASC, pub.estatus_publicacion='publicado' DESC, pub.id_publicacion ASC";
$filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sospechosos = array();
foreach ($filas as $fila) {
  $slugProducto = seo_audit_slug_local($fila["nombre_producto"]);
  $slugPublicacion = seo_audit_slug_local($fila["slug"]);
  if ($slugProducto === "" || $slugPublicacion !== $slugProducto) { continue; }
  $simSkuProducto = seo_audit_similitud($fila["nombre_sku"], $fila["nombre_producto"]);
  $simTituloProducto = seo_audit_similitud($fila["titulo_publico"], $fila["nombre_producto"]);
  $skuDifiere = seo_audit_slug_local($fila["nombre_sku"]) !== "" && $simSkuProducto < 58;
  $tituloPareceProducto = $simTituloProducto >= 82;
  if (!$skuDifiere || !$tituloPareceProducto) { continue; }
  $sospechosos[] = array(
    "id_producto_erp" => intval($fila["id_producto_erp"]),
    "id_publicacion" => intval($fila["id_publicacion"]),
    "id_sku" => intval($fila["id_sku"]),
    "sku" => $fila["sku"],
    "estatus_publicacion" => $fila["estatus_publicacion"],
    "slug_actual" => $fila["slug"],
    "url_actual" => $fila["url_publica"] ?: "/producto/" . $fila["slug"],
    "nombre_producto" => $fila["nombre_producto"],
    "nombre_sku" => $fila["nombre_sku"],
    "titulo_publico" => $fila["titulo_publico"],
    "total_publicaciones_producto" => intval($fila["total_publicaciones_producto"]),
    "similitud_sku_producto" => $simSkuProducto,
    "motivo" => "slug_de_producto_global_usado_por_sku_con_nombre_distinto"
  );
}

echo json_encode(array(
  "ok" => true,
  "read_only" => true,
  "total_revisadas" => count($filas),
  "total_sospechosas" => count($sospechosos),
  "items" => array_slice($sospechosos, 0, 80)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
