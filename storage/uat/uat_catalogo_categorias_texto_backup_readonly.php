<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-13
 * Proposito: generar respaldo externo puntual de categorias con texto danado antes de repararlas.
 * Impacto: solo lectura sobre BD; escribe archivo JSON fuera del proyecto en C:\xampp\panel_db_backups.
 * Contrato: exporta id, codigo, nombre, descripcion y ruta actuales de categorias afectadas.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoCategoriasTextoBackupDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_backup_texto_danado($texto) {
  $texto = (string) $texto;
  return $texto !== '' && (
    strpos($texto, "\xE2\x94\x9C") !== false ||
    strpos($texto, "\xE2\x94\xAC") !== false ||
    strpos($texto, "\xC3\x83") !== false ||
    strpos($texto, "\xC3\x82") !== false ||
    strpos($texto, "\xEF\xBF\xBD") !== false
  );
}

$db = (new CatalogoCategoriasTextoBackupDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$categorias = $db->query("
  SELECT id_categoria_erp, id_categoria_padre, codigo, nombre, descripcion, ruta, nivel, tipo_categoria, origen, permite_productos, estatus
  FROM erp_catalogo_categorias
  ORDER BY id_categoria_erp
")->fetchAll(PDO::FETCH_ASSOC);

$afectadas = array();
foreach ($categorias as $categoria) {
  if (
    cat_backup_texto_danado($categoria['nombre']) ||
    cat_backup_texto_danado($categoria['descripcion']) ||
    cat_backup_texto_danado($categoria['ruta'])
  ) {
    $afectadas[] = $categoria;
  }
}

$dir = 'C:\\xampp\\panel_db_backups';
if (!is_dir($dir)) {
  mkdir($dir, 0777, true);
}
$archivo = $dir . '\\catalogo_categorias_texto_' . date('Ymd_His') . '_pre.json';
file_put_contents($archivo, json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'proyecto' => 'C:\\xampp\\htdocs\\panel_de_control',
  'tabla' => 'erp_catalogo_categorias',
  'total_afectadas' => count($afectadas),
  'categorias' => $afectadas
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode(array(
  'error' => false,
  'archivo' => $archivo,
  'total_afectadas' => count($afectadas)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
