<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-31.
 * Proposito: publicar los tres videos TikTok de productos despues de validar su contrato minimo.
 * Impacto: actualiza estado/fecha_publicacion en erp_ecommerce_videos para IDs 2,3,4; no toca productos, ventas ni inventario.
 * Contrato: requiere autorizacion y respaldo; bloquea cualquier video incompleto.
 */
chdir(__DIR__ . "/../../public"); require_once "../app/iniciador.php";
class EcommerceVideosPublicarApply extends CRUD { public function db() { return $this->getConexion(); } }
$args = argumentos($argv); $token = "ECOMMERCE_VIDEOS_PUBLICAR_PRODUCTOS_20261031"; $respaldo = $args["respaldo"] ?? "";
if (($args["autorizar"] ?? "") !== $token) salida(false, "Token de autorizacion invalido o ausente", array("token_requerido" => $token));
if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) salida(false, "Respaldo externo valido requerido", array("respaldo_recibido" => $respaldo));
$db = (new EcommerceVideosPublicarApply())->db(); if (!$db) salida(false, "No hay conexion a base de datos", array());
$ids = array(2, 3, 4); $resultado = array();
try {
  $db->beginTransaction();
  $find = $db->prepare("SELECT id_video, titulo, slug, video_url, embed_url, thumbnail_url, thumbnail_alt, tiktok_post_id, estado FROM erp_ecommerce_videos WHERE id_video=:id FOR UPDATE");
  $update = $db->prepare("UPDATE erp_ecommerce_videos SET estado='publicado', fecha_publicacion=COALESCE(fecha_publicacion,NOW()), updated_at=NOW(), actualizado_por=0 WHERE id_video=:id");
  foreach ($ids as $id) {
    $find->execute(array(":id" => $id)); $row = $find->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception("No existe el video ID " . $id);
    $faltantes = array(); foreach (array("titulo", "slug", "video_url", "embed_url", "thumbnail_url", "thumbnail_alt", "tiktok_post_id") as $campo) if (trim((string) $row[$campo]) === "") $faltantes[] = $campo;
    if (!preg_match('~^https://(www\\.)?tiktok\\.com/.+~i', $row["video_url"])) $faltantes[] = "video_url_tiktok";
    if ($faltantes) throw new Exception("El video " . $id . " no cumple requisitos: " . implode(", ", $faltantes));
    $update->execute(array(":id" => $id)); $resultado[] = array("id_video" => $id, "slug" => $row["slug"], "titulo" => $row["titulo"], "estado_anterior" => $row["estado"], "estado" => "publicado");
  }
  $db->commit();
} catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); salida(false, "No se pudieron publicar los videos", array("error" => $e->getMessage())); }
salida(true, "Videos publicados para prueba de Frontend", array("respaldo" => $respaldo, "videos" => $resultado, "guardrails" => array("no_toca_productos" => true, "no_toca_ventas" => true, "no_toca_inventario" => true)));
function argumentos($argv) { $r = array(); foreach ($argv as $arg) { if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) continue; $p = explode("=", substr($arg, 2), 2); $r[$p[0]] = $p[1]; } return $r; }
function salida($ok, $mensaje, $depurar) { echo json_encode(array("ok" => $ok, "mensaje" => $mensaje, "depurar" => $depurar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL; exit($ok ? 0 : 1); }
