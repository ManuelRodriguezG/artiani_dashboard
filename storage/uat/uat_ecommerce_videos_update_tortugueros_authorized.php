<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: convertir el primer video TikTok real en contenido editorial de tortugueros.
 * Impacto: actualiza copy, busqueda, miniatura y categoria del modulo Ecommerce Videos sin relacionarlo a producto.
 * Contrato: escritura controlada; requiere token, respaldo externo existente y no toca ventas, inventario ni catalogo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class EcommerceVideosTortuguerosUpdateDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$args = argumentos($argv);
$token = $args["autorizar"] ?? "";
$respaldo = $args["respaldo"] ?? "";
$tokenEsperado = "ECOMMERCE_VIDEOS_TORTUGUEROS_UPDATE";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "tortugueros_update_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo valido requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "tortugueros_update_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo
  ));
}

$db = (new EcommerceVideosTortuguerosUpdateDb())->db();
if (!$db) {
  salida(false, "No hay conexion a base de datos", array("modo" => "tortugueros_update_authorized", "ejecutado" => false));
}

$slug = "tiktok-articulos-para-animales-7526057146073566471";
$categoriaPath = "reptiles-anfibios-e-invertebrados/tortugas/tortugueros";
$categoriaUrl = "/categoria/" . $categoriaPath;
$thumbnailUrl = "/assets/media/cms/ecommerce/videos/tortugueros-tiktok-7526057146073566471.png";
$thumbnailPath = __DIR__ . "/../../public" . $thumbnailUrl;

if (!is_file($thumbnailPath) || filesize($thumbnailPath) <= 0) {
  salida(false, "Miniatura local requerida no encontrada", array(
    "modo" => "tortugueros_update_authorized",
    "ejecutado" => false,
    "thumbnail_url" => $thumbnailUrl
  ));
}

$copyTikTok = "❌ Una tortuga en una pecera común puede enfermarse.\n✅ Un tortuguero bien hecho tiene zona seca, buena filtración y el espacio que necesita.\n\nAdaptamos las plataformas a la medida de tu tortuga, para que viva como debe.\n\n¿Tienes una tortuga o piensas tener una? Escríbenos y te asesoramos.";
$hashtags = "#Tortugas #Tortugueros #HábitatParaTortugas #CuidaATuTortuga #MascotasFelices #TortugasSaludables #PecerasPersonalizadas";

$datos = array(
  ":titulo" => "Tortugueros con zona seca, filtración y espacio adecuado",
  ":descripcion_corta" => "Consejo rápido sobre por qué una tortuga necesita un tortuguero bien hecho, no una pecera común.",
  ":descripcion_larga" => $copyTikTok,
  ":tipo_video" => "consejo_rapido",
  ":provider" => "tiktok",
  ":tiktok_post_id" => "7526057146073566471",
  ":tiktok_author" => "articulos_para_animales",
  ":video_url" => "https://www.tiktok.com/@articulos_para_animales/video/7526057146073566471",
  ":embed_url" => "https://www.tiktok.com/player/v1/7526057146073566471?autoplay=0&description=0",
  ":copy_tiktok" => $copyTikTok,
  ":hashtags" => $hashtags,
  ":texto_busqueda" => "tortugas tortugueros tortuguero habitat para tortugas zona seca filtracion espacio peceras personalizadas plataformas a medida tortuga saludable asesoria reptiles",
  ":thumbnail_url" => $thumbnailUrl,
  ":thumbnail_alt" => "Tortugas en un tortuguero con agua, piedras y zona seca",
  ":orientacion" => "vertical",
  ":duracion_segundos" => 0,
  ":estado" => "publicado",
  ":fecha_publicacion" => date("Y-m-d H:i:s"),
  ":orden" => 0,
  ":destacado" => 1,
  ":seo_title" => "Tortugueros con zona seca y filtración | Artiani",
  ":seo_description" => "Una tortuga necesita zona seca, buena filtración y espacio. En Artiani adaptamos plataformas y tortugueros a su medida.",
  ":seo_canonical" => "/videos/" . $slug,
  ":og_image" => $thumbnailUrl,
  ":metadata_json" => json_encode(array(
    "contenido" => "tortugueros",
    "thumbnail_fuente" => "imagen_temporal_usuario",
    "categoria_principal" => $categoriaPath,
    "sin_producto_especifico" => true,
    "temporal" => true
  ), JSON_UNESCAPED_UNICODE),
  ":usuario" => 0,
  ":slug" => $slug
);

try {
  $db->beginTransaction();
  $stmt = $db->prepare("SELECT id_video FROM erp_ecommerce_videos WHERE slug=:slug LIMIT 1");
  $stmt->execute(array(":slug" => $slug));
  $id = intval($stmt->fetchColumn());

  if ($id <= 0) {
    if ($db->inTransaction()) { $db->rollBack(); }
    salida(false, "No existe el video base que se desea actualizar", array(
      "modo" => "tortugueros_update_authorized",
      "ejecutado" => true,
      "slug" => $slug
    ));
  }

  $sql = "UPDATE erp_ecommerce_videos SET titulo=:titulo, descripcion_corta=:descripcion_corta, descripcion_larga=:descripcion_larga, tipo_video=:tipo_video, provider=:provider, tiktok_post_id=:tiktok_post_id, tiktok_author=:tiktok_author, video_url=:video_url, embed_url=:embed_url, copy_tiktok=:copy_tiktok, hashtags=:hashtags, texto_busqueda=:texto_busqueda, thumbnail_url=:thumbnail_url, thumbnail_alt=:thumbnail_alt, orientacion=:orientacion, duracion_segundos=:duracion_segundos, estado=:estado, fecha_publicacion=:fecha_publicacion, orden=:orden, destacado=:destacado, seo_title=:seo_title, seo_description=:seo_description, seo_canonical=:seo_canonical, og_image=:og_image, metadata_json=:metadata_json, updated_at=NOW(), actualizado_por=:usuario WHERE slug=:slug";
  $db->prepare($sql)->execute($datos);

  $db->prepare("DELETE FROM erp_ecommerce_video_producto WHERE id_video=:id")->execute(array(":id" => $id));
  $db->prepare("DELETE FROM erp_ecommerce_video_categoria WHERE id_video=:id")->execute(array(":id" => $id));
  $db->prepare("INSERT INTO erp_ecommerce_video_categoria (id_video, categoria_path_slug, url_categoria, orden) VALUES (:id, :path, :url, 0)")
    ->execute(array(":id" => $id, ":path" => $categoriaPath, ":url" => $categoriaUrl));

  $db->commit();
} catch (Exception $e) {
  if ($db->inTransaction()) { $db->rollBack(); }
  salida(false, "No se pudo actualizar el video de tortugueros", array(
    "modo" => "tortugueros_update_authorized",
    "ejecutado" => true,
    "error" => $e->getMessage()
  ));
}

salida(true, "Video de tortugueros actualizado", array(
  "modo" => "tortugueros_update_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "id_video" => $id,
  "slug" => $slug,
  "tipo_video" => "consejo_rapido",
  "categoria_path_slug" => $categoriaPath,
  "thumbnail_url" => $thumbnailUrl,
  "producto_relacionado" => false,
  "guardrails" => array(
    "no_descarga_video" => true,
    "no_toca_catalogo" => true,
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
