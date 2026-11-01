<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-31.
 * Proposito: registrar tres videos TikTok como borradores y relacionarlos con productos publicados.
 * Impacto: solo tablas erp_ecommerce_videos y erp_ecommerce_video_producto; no modifica productos, ventas ni inventario.
 * Contrato: escritura controlada; requiere token y respaldo externo existente. Miniaturas quedan pendientes de carga manual.
 */
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
class EcommerceVideosProductosApply extends CRUD { public function db() { return $this->getConexion(); } }
$args = argumentos($argv);
$tokenEsperado = "ECOMMERCE_VIDEOS_PRODUCTOS_20261031";
$respaldo = $args["respaldo"] ?? "";
if (($args["autorizar"] ?? "") !== $tokenEsperado) salida(false, "Token de autorizacion invalido o ausente", array("token_requerido" => $tokenEsperado));
if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) salida(false, "Respaldo externo valido requerido", array("respaldo_recibido" => $respaldo));
$db = (new EcommerceVideosProductosApply())->db();
if (!$db) salida(false, "No hay conexion a base de datos", array());
$videos = array(
  array("post" => "7699142386961616136", "slug" => "jaula-mamiferos-iii100-x-55-x-41cm-video-tiktok-7699142386961616136", "titulo" => "Jaula mamíferos III100 x 55 x 41 cm | Video de producto", "producto" => array("id_publicacion" => 1740, "id_sku" => 698, "slug" => "jaula-mamiferos-iii100-x-55-x-41cm")),
  array("post" => "7694191470479363336", "slug" => "pecera-50-x-26-x-30-cm-3-mm-39l-video-tiktok-7694191470479363336", "titulo" => "Pecera 50 x 26 x 30 cm 3 mm 39 litros | Video de producto", "producto" => array("id_publicacion" => 1616, "id_sku" => 536, "slug" => "pecera-50-x-26-x-30-cm-3-mm-39l")),
  array("post" => "7701973097561345287", "slug" => "terrario-sumag-50-x-26-x-30-cm-3-mm-rectangular-video-tiktok-7701973097561345287", "titulo" => "Terrario Sumag 50 x 26 x 30 cm 3 mm rectangular | Video de producto", "producto" => array("id_publicacion" => 1903, "id_sku" => 907, "slug" => "terrario-sumag-50-x-26-x-30-cm-3-mm-rectangular"))
);
try {
  $db->beginTransaction();
  foreach ($videos as $video) {
    $url = "https://www.tiktok.com/@articulos_para_animales/video/" . $video["post"];
    $embed = "https://www.tiktok.com/player/v1/" . $video["post"] . "?autoplay=0&description=0";
    $meta = json_encode(array("alta_lote" => "productos_tiktok_20261031", "miniatura_pendiente" => true, "miniatura_fuente" => "pendiente_cover_image_url_o_manual", "producto_principal_confirmado" => true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $params = array(":titulo" => $video["titulo"], ":slug" => $video["slug"], ":descripcion_corta" => "Video TikTok relacionado con el producto " . $video["producto"]["slug"] . ".", ":descripcion_larga" => "Borrador para revisar el contenido del video, su miniatura y sus productos relacionados antes de publicar.", ":tipo" => "demo_producto", ":post" => $video["post"], ":url" => $url, ":embed" => $embed, ":thumb" => "", ":alt" => "", ":seo_title" => $video["titulo"] . " | Artiani", ":seo_description" => "Conoce este producto en video y consulta sus características en Artiani.", ":canonical" => "/videos/" . $video["slug"], ":og" => "", ":meta" => $meta, ":usuario" => 0);
    $find = $db->prepare("SELECT id_video FROM erp_ecommerce_videos WHERE tiktok_post_id=:post LIMIT 1");
    $find->execute(array(":post" => $video["post"]));
    $id = intval($find->fetchColumn());
    if ($id > 0) {
      $sql = "UPDATE erp_ecommerce_videos SET titulo=:titulo, slug=:slug, descripcion_corta=:descripcion_corta, descripcion_larga=:descripcion_larga, tipo_video=:tipo, provider='tiktok', tiktok_post_id=:post, tiktok_author='articulos_para_animales', video_url=:url, embed_url=:embed, thumbnail_url=:thumb, thumbnail_alt=:alt, orientacion='vertical', estado='borrador', fecha_publicacion=NULL, destacado=0, seo_title=:seo_title, seo_description=:seo_description, seo_canonical=:canonical, og_image=:og, metadata_json=:meta, updated_at=NOW(), actualizado_por=:usuario WHERE id_video=:id";
      $params[":id"] = $id; $db->prepare($sql)->execute($params);
    } else {
      $sql = "INSERT INTO erp_ecommerce_videos (titulo, slug, descripcion_corta, descripcion_larga, tipo_video, provider, tiktok_post_id, tiktok_author, video_url, embed_url, thumbnail_url, thumbnail_alt, orientacion, estado, destacado, seo_title, seo_description, seo_canonical, og_image, metadata_json, creado_por, actualizado_por) VALUES (:titulo, :slug, :descripcion_corta, :descripcion_larga, :tipo, 'tiktok', :post, 'articulos_para_animales', :url, :embed, :thumb, :alt, 'vertical', 'borrador', 0, :seo_title, :seo_description, :canonical, :og, :meta, :usuario, :usuario)";
      $db->prepare($sql)->execute($params); $id = intval($db->lastInsertId());
    }
    $db->prepare("DELETE FROM erp_ecommerce_video_producto WHERE id_video=:id")->execute(array(":id" => $id));
    $db->prepare("INSERT INTO erp_ecommerce_video_producto (id_video, id_publicacion, id_sku, slug_producto, producto_principal, orden) VALUES (:id, :pub, :sku, :slug, 1, 0)")->execute(array(":id" => $id, ":pub" => $video["producto"]["id_publicacion"], ":sku" => $video["producto"]["id_sku"], ":slug" => $video["producto"]["slug"]));
    $resultado[] = array("id_video" => $id, "post" => $video["post"], "slug" => $video["slug"], "producto_principal" => $video["producto"]["slug"], "estado" => "borrador", "miniatura" => "pendiente_carga_manual");
  }
  $db->commit();
} catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); salida(false, "No se pudieron registrar los videos", array("error" => $e->getMessage())); }
salida(true, "Videos TikTok registrados como borradores", array("respaldo" => $respaldo, "videos" => $resultado, "guardrails" => array("no_descarga_video" => true, "no_toca_productos" => true, "no_toca_ventas" => true, "no_toca_inventario" => true, "requiere_revision_miniatura" => true)));
function argumentos($argv) { $r = array(); foreach ($argv as $arg) { if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) continue; $p = explode("=", substr($arg, 2), 2); $r[$p[0]] = $p[1]; } return $r; }
function salida($ok, $mensaje, $depurar) { echo json_encode(array("ok" => $ok, "mensaje" => $mensaje, "depurar" => $depurar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL; exit($ok ? 0 : 1); }
