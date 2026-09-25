<?php
/** IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: nombres descriptivos de imagen independientes de su identidad y formato.
 * Impacto: altas y reemplazos CMS; no infiere contenido ni agrega palabras clave.
 */
class CmsMediaNombre {
  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Contrato: entrada humana hasta 120 caracteres utiles; slug ASCII sin extension/rutas.
   * Impacto: URL legible y segura con guiones, preservando palabras proporcionadas.
   */
  public static function normalizar($nombre) {
    $nombre = preg_replace('/\.(?:jpe?g|png|webp|gif|avif|ico)$/i', '', trim((string) $nombre));
    $nombre = mb_strtolower($nombre, 'UTF-8');
    $nombre = strtr($nombre, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'));
    $nombre = preg_replace('/[^a-z0-9]+/', '-', $nombre);
    return trim(substr(trim($nombre, '-'), 0, 120), '-');
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: separar descripcion SEO de sufijos generados por CMS anteriores.
   * Contrato: preferir nombre_seo guardado; fallback legible del nombre original.
   */
  public static function sugerir(array $item) {
    $nombre = (string) ($item['nombre_seo'] ?? $item['nombre_original'] ?? '');
    $nombre = preg_replace('/^cms_\d{8}_\d{6}_[a-f0-9]+_(?:[a-f0-9]{12}_)?/i', '', $nombre);
    return self::normalizar($nombre);
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: evitar colisiones y reutilizacion de URLs anteriores tras renombrar.
   * Contrato: slug + identificador + sufijo aleatorio; extension validada por inspector.
   */
  public static function archivo($slug, $extension, $id = 0) {
    if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 120 || !in_array($extension, array('jpg','jpeg','png','webp','gif','avif','ico'), true)) throw new Exception('Indica un nombre descriptivo valido para la imagen.');
    return $slug . ($id > 0 ? '-' . intval($id) : '') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
  }
}
