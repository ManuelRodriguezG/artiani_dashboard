<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-31.
 * Proposito: registrar tres miniaturas WebP proporcionadas por el usuario en Media CMS y asociarlas a videos borrador.
 * Impacto: crea/usa registros de erp_ecommerce_media_archivos y actualiza thumbnail_url/og_image de tres videos; no modifica productos.
 * Contrato: requiere autorizacion explicita y respaldo externo. Conserva los archivos originales del usuario.
 */
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
class EcommerceVideosThumbnailsApply extends CRUD { public function db() { return $this->getConexion(); } }
$args = argumentos($argv); $token = "ECOMMERCE_VIDEOS_THUMBNAILS_20261031"; $respaldo = $args["respaldo"] ?? "";
if (($args["autorizar"] ?? "") !== $token) salida(false, "Token de autorizacion invalido o ausente", array("token_requerido" => $token));
if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) salida(false, "Respaldo externo valido requerido", array("respaldo_recibido" => $respaldo));
$db = (new EcommerceVideosThumbnailsApply())->db(); if (!$db) salida(false, "No hay conexion a base de datos", array());
$base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "media" . DIRECTORY_SEPARATOR . "cms" . DIRECTORY_SEPARATOR . "ecommerce";
if (!is_dir($base) && !mkdir($base, 0775, true) && !is_dir($base)) salida(false, "No se pudo preparar la carpeta Media CMS", array());
$items = array(
  array("video" => 2, "fuente" => "C:\\Users\\aleja\\Downloads\\oYDIBiw4Eu1AgcCTIBYA8KiAzbU62AfG6rAHyY~tplv-photomode-zoomcover_720_720.webp", "nombre" => "jaula-mamiferos-iii100-video-7699142386961616136.webp", "alt" => "Jaula para mamíferos y roedores con medidas"),
  array("video" => 3, "fuente" => "C:\\Users\\aleja\\Downloads\\oMCgocQgARJE0fUqRWGCB86PmEItEAFHADAxfD~tplv-photomode-zoomcover_720_720.webp", "nombre" => "pecera-50x26x30-39l-video-7694191470479363336.webp", "alt" => "Pecera de 50 por 26 por 30 centímetros y 39 litros"),
  array("video" => 4, "fuente" => "C:\\Users\\aleja\\Downloads\\o4yxuARAEEBEiBHBADNSAIzilYvAavn8Eai5L~tplv-photomode-zoomcover_720_720.webp", "nombre" => "terrario-sumag-50x26x30-video-7701973097561345287.webp", "alt" => "Terrario estándar Sumag de 50 por 26 centímetros")
);
$salida = array();
try {
  $db->beginTransaction();
  foreach ($items as $item) {
    if (!is_file($item["fuente"])) throw new Exception("No se encontro la imagen fuente: " . basename($item["fuente"]));
    $ins = CmsMediaArchivo::inspeccionar($item["fuente"], $item["nombre"]);
    if (strtolower($ins["extension"]) !== "webp") throw new Exception("La miniatura no es WebP: " . basename($item["fuente"]));
    if ((int) $ins["bytes"] <= 0 || (int) $ins["bytes"] > 2097152) throw new Exception("La miniatura supera 2 MB: " . basename($item["fuente"]));
    $hash = $ins["hash"];
    $buscar = $db->prepare("SELECT id_media_archivo, ruta_publica FROM erp_ecommerce_media_archivos WHERE hash_sha256=:hash LIMIT 1"); $buscar->execute(array(":hash" => $hash)); $media = $buscar->fetch(PDO::FETCH_ASSOC);
    if ($media) { $url = (string) $media["ruta_publica"]; $idMedia = (int) $media["id_media_archivo"]; }
    else {
      $destino = $base . DIRECTORY_SEPARATOR . $item["nombre"];
      if (!is_file($destino) && !copy($item["fuente"], $destino)) throw new Exception("No se pudo copiar la miniatura a Media CMS");
      if (!CmsMediaArchivo::asegurarLecturaPublica($destino)) throw new Exception("No se pudo habilitar la lectura publica de la miniatura");
      $valid = CmsMediaArchivo::verificarGuardado($destino, $ins["bytes"], $hash); if (!$valid["ok"]) throw new Exception($valid["mensaje"]);
      $url = "/assets/media/cms/ecommerce/" . $item["nombre"];
      $codigo = "media_video_" . substr($hash, 0, 16);
      $meta = json_encode(array("origen" => "usuario", "modulo" => "videos", "formato_original" => "webp", "dimensiones_originales" => array("ancho" => $ins["ancho"], "alto" => $ins["alto"])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      $insert = $db->prepare("INSERT INTO erp_ecommerce_media_archivos (codigo, nombre_original, nombre_archivo, ruta_publica, mime, extension, bytes, ancho, alto, hash_sha256, alt_text, uso_sugerido, tipo_sugerido, estatus, metadata_json, creado_por) VALUES (:codigo,:original,:archivo,:ruta,:mime,:ext,:bytes,:ancho,:alto,:hash,:alt,'comercial','thumb','activo',:meta,NULL)");
      $insert->execute(array(":codigo" => $codigo, ":original" => basename($item["fuente"]), ":archivo" => $item["nombre"], ":ruta" => $url, ":mime" => $ins["mime"], ":ext" => $ins["extension"], ":bytes" => $ins["bytes"], ":ancho" => $ins["ancho"], ":alto" => $ins["alto"], ":hash" => $hash, ":alt" => $item["alt"], ":meta" => $meta));
      $idMedia = (int) $db->lastInsertId();
    }
    $upd = $db->prepare("UPDATE erp_ecommerce_videos SET thumbnail_url=:url, thumbnail_alt=:alt, og_image=:url, metadata_json=JSON_SET(COALESCE(metadata_json, '{}'), '$.miniatura_fuente', 'usuario_webp', '$.miniatura_media_id', :media, '$.miniatura_dimensiones', :dimensiones), updated_at=NOW() WHERE id_video=:video");
    $dim = json_encode(array("ancho" => $ins["ancho"], "alto" => $ins["alto"]), JSON_UNESCAPED_SLASHES); $upd->execute(array(":url" => $url, ":alt" => $item["alt"], ":media" => $idMedia, ":dimensiones" => $dim, ":video" => $item["video"]));
    $salida[] = array("id_video" => $item["video"], "id_media_archivo" => $idMedia, "url" => $url, "bytes" => $ins["bytes"], "dimensiones" => array($ins["ancho"], $ins["alto"]));
  }
  $db->commit();
} catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); salida(false, "No se pudieron asociar las miniaturas", array("error" => $e->getMessage())); }
salida(true, "Miniaturas WebP asociadas a los videos", array("respaldo" => $respaldo, "items" => $salida, "nota" => "Las imagenes originales ya eran WebP; se conservaron sus dimensiones y el frontend las recorta visualmente a 9:16."));
function argumentos($argv) { $r = array(); foreach ($argv as $arg) { if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) continue; $p = explode("=", substr($arg, 2), 2); $r[$p[0]] = $p[1]; } return $r; }
function salida($ok, $mensaje, $depurar) { echo json_encode(array("ok" => $ok, "mensaje" => $mensaje, "depurar" => $depurar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL; exit($ok ? 0 : 1); }
