<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: cargar el primer video TikTok real de prueba en tablas Ecommerce Videos.
 * Impacto: crea/actualiza un video publicado de prueba para que frontend consuma BD real.
 * Contrato: escritura controlada; requiere token, respaldo externo existente y no toca ventas/inventario/productos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class EcommerceVideosSeedDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$args = argumentos($argv);
$token = $args["autorizar"] ?? "";
$respaldo = $args["respaldo"] ?? "";
$tokenEsperado = "ECOMMERCE_VIDEOS_SEED_PRUEBA";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "seed_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo valido requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "seed_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo
  ));
}

$db = (new EcommerceVideosSeedDb())->db();
if (!$db) {
  salida(false, "No hay conexion a base de datos", array("modo" => "seed_authorized", "ejecutado" => false));
}

$slug = "tiktok-articulos-para-animales-7526057146073566471";
$datos = array(
  ":titulo" => "Video TikTok de prueba - articulos para animales",
  ":slug" => $slug,
  ":descripcion_corta" => "Video real de TikTok cargado como primera prueba para el frontend.",
  ":descripcion_larga" => "Registro temporal para validar el modulo publico de videos con un enlace TikTok real. La miniatura es local de prueba y debe reemplazarse por una miniatura comercial final antes de produccion.",
  ":tipo_video" => "demo_producto",
  ":provider" => "tiktok",
  ":tiktok_post_id" => "7526057146073566471",
  ":tiktok_author" => "articulos_para_animales",
  ":video_url" => "https://www.tiktok.com/@articulos_para_animales/video/7526057146073566471",
  ":embed_url" => "https://www.tiktok.com/player/v1/7526057146073566471?autoplay=0&description=0",
  ":copy_tiktok" => "Video compartido desde la cuenta articulos_para_animales para validar el flujo de videos Artiani con carga diferida del player TikTok.",
  ":hashtags" => "#mascotas #articulosparamascotas #tiktok #artiani",
  ":texto_busqueda" => "tiktok articulos para animales mascotas prueba frontend videos artiani demo producto",
  ":thumbnail_url" => "/assets/fixtures/videos/tiktok-articulos-para-animales-7526057146073566471.svg",
  ":thumbnail_alt" => "Miniatura temporal de video TikTok de prueba",
  ":orientacion" => "vertical",
  ":duracion_segundos" => 0,
  ":estado" => "publicado",
  ":fecha_publicacion" => date("Y-m-d H:i:s"),
  ":orden" => 0,
  ":destacado" => 1,
  ":seo_title" => "Video TikTok de prueba | Artiani",
  ":seo_description" => "Video real de TikTok para validar el modulo publico de videos Artiani.",
  ":seo_canonical" => "/videos/" . $slug,
  ":og_image" => "/assets/fixtures/videos/tiktok-articulos-para-animales-7526057146073566471.svg",
  ":metadata_json" => json_encode(array("fixture_origen" => "primer_tiktok_prueba", "temporal" => true), JSON_UNESCAPED_UNICODE),
  ":usuario" => 0
);

try {
  $db->beginTransaction();
  $stmt = $db->prepare("SELECT id_video FROM erp_ecommerce_videos WHERE slug=:slug LIMIT 1");
  $stmt->execute(array(":slug" => $slug));
  $id = intval($stmt->fetchColumn());

  if ($id > 0) {
    $sql = "UPDATE erp_ecommerce_videos SET titulo=:titulo, descripcion_corta=:descripcion_corta, descripcion_larga=:descripcion_larga, tipo_video=:tipo_video, provider=:provider, tiktok_post_id=:tiktok_post_id, tiktok_author=:tiktok_author, video_url=:video_url, embed_url=:embed_url, copy_tiktok=:copy_tiktok, hashtags=:hashtags, texto_busqueda=:texto_busqueda, thumbnail_url=:thumbnail_url, thumbnail_alt=:thumbnail_alt, orientacion=:orientacion, duracion_segundos=:duracion_segundos, estado=:estado, fecha_publicacion=:fecha_publicacion, orden=:orden, destacado=:destacado, seo_title=:seo_title, seo_description=:seo_description, seo_canonical=:seo_canonical, og_image=:og_image, metadata_json=:metadata_json, updated_at=NOW(), actualizado_por=:usuario WHERE id_video=:id";
    $params = $datos;
    unset($params[":slug"]);
    $params[":id"] = $id;
    $db->prepare($sql)->execute($params);
  } else {
    $sql = "INSERT INTO erp_ecommerce_videos (titulo, slug, descripcion_corta, descripcion_larga, tipo_video, provider, tiktok_post_id, tiktok_author, video_url, embed_url, copy_tiktok, hashtags, texto_busqueda, thumbnail_url, thumbnail_alt, orientacion, duracion_segundos, estado, fecha_publicacion, orden, destacado, seo_title, seo_description, seo_canonical, og_image, metadata_json, creado_por, actualizado_por) VALUES (:titulo, :slug, :descripcion_corta, :descripcion_larga, :tipo_video, :provider, :tiktok_post_id, :tiktok_author, :video_url, :embed_url, :copy_tiktok, :hashtags, :texto_busqueda, :thumbnail_url, :thumbnail_alt, :orientacion, :duracion_segundos, :estado, :fecha_publicacion, :orden, :destacado, :seo_title, :seo_description, :seo_canonical, :og_image, :metadata_json, :usuario, :usuario)";
    $db->prepare($sql)->execute($datos);
    $id = intval($db->lastInsertId());
  }

  $db->prepare("DELETE FROM erp_ecommerce_video_categoria WHERE id_video=:id")->execute(array(":id" => $id));
  $db->prepare("INSERT INTO erp_ecommerce_video_categoria (id_video, categoria_path_slug, url_categoria, orden) VALUES (:id, :path, :url, 0)")
    ->execute(array(":id" => $id, ":path" => "videos/pruebas", ":url" => "/categoria/videos/pruebas"));
  $db->commit();
} catch (Exception $e) {
  if ($db->inTransaction()) { $db->rollBack(); }
  salida(false, "No se pudo cargar video TikTok de prueba", array(
    "modo" => "seed_authorized",
    "ejecutado" => true,
    "error" => $e->getMessage()
  ));
}

salida(true, "Video TikTok de prueba cargado", array(
  "modo" => "seed_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "id_video" => $id,
  "slug" => $slug,
  "estado" => "publicado",
  "provider" => "tiktok",
  "tiktok_post_id" => "7526057146073566471",
  "guardrails" => array(
    "no_descarga_video" => true,
    "no_toca_productos" => true,
    "no_toca_ventas" => true,
    "no_toca_inventario" => true
  )
));

function argumentos($argv) {
  $salida = array();
  foreach ($argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = $partes[1];
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit($ok ? 0 : 1);
}
