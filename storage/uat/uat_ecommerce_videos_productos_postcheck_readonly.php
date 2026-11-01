<?php
/** UAT read-only: verifies published video rows, thumbnails and product relations. */
chdir(__DIR__ . "/../../public"); require_once "../app/iniciador.php";
class EcommerceVideosProductosPostcheck extends CRUD { public function db() { return $this->getConexion(); } }
$db = (new EcommerceVideosProductosPostcheck())->db();
$stmt = $db->query("SELECT v.id_video, v.slug, v.estado, v.thumbnail_url, v.thumbnail_alt, vp.slug_producto, vp.producto_principal FROM erp_ecommerce_videos v LEFT JOIN erp_ecommerce_video_producto vp ON vp.id_video=v.id_video AND vp.producto_principal=1 WHERE v.id_video IN (2,3,4) ORDER BY v.id_video");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC); echo json_encode(array("ok" => true, "items" => $items, "no_escribe_bd" => true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
