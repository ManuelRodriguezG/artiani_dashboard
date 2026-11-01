<?php
/** UAT read-only: resolve public product slugs for Ecommerce Videos. */
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
class EcommerceVideosProductosReadonly extends CRUD { public function db() { return $this->getConexion(); } }
$slugs = array(
  "jaula-mamiferos-iii100-x-55-x-41cm",
  "pecera-50-x-26-x-30-cm-3-mm-39l",
  "terrario-sumag-50-x-26-x-30-cm-3-mm-rectangular"
);
$db = (new EcommerceVideosProductosReadonly())->db();
$stmt = $db->prepare("SELECT id_publicacion, id_producto_erp, id_sku, slug, titulo_publico, url_publica, estatus_publicacion FROM erp_ecommerce_publicaciones WHERE canal='catalogo_publico' AND slug=:slug LIMIT 1");
$items = array();
foreach ($slugs as $slug) { $stmt->execute(array(":slug" => $slug)); $row = $stmt->fetch(PDO::FETCH_ASSOC); $items[] = $row ?: array("slug" => $slug, "encontrado" => false); }
echo json_encode(array("ok" => true, "items" => $items, "no_escribe_bd" => true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
