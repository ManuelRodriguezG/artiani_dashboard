<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: guardar caudal canonico detectado desde nombre/descripcion de SKUs de Catalogo ERP.
 * Impacto: inserta/actualiza solo valores del atributo caudal en erp_catalogo_sku_atributos.
 * Contrato: aplica unicamente valores con evidencia explicita l/h, l/hr, lt/hr, lph o litros por hora.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoCaudalApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_caudal_apply_norm($texto) {
  $texto = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $texto = mb_strtolower($texto, 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  return trim(preg_replace('/\s+/', ' ', $texto));
}

function cat_caudal_apply_decimal($valor) {
  $valor = str_replace(',', '.', (string) $valor);
  if (!preg_match('/\d+(?:\.\d+)?/', $valor, $m)) {
    return null;
  }
  return round((float) $m[0], 4);
}

function cat_caudal_apply_extraer($texto) {
  $normalizado = cat_caudal_apply_norm($texto);
  $patrones = array(
    '/(\d+(?:[\.,]\d+)?)\s*(?:l|lt|lts)\s*\/\s*(?:h|hr|hrs|hora)\b/u',
    '/(\d+(?:[\.,]\d+)?)\s*(?:lph|l\.p\.h\.)\b/u',
    '/(\d+(?:[\.,]\d+)?)\s*litros?\s*(?:por|\/)\s*hora\b/u'
  );
  foreach ($patrones as $patron) {
    if (preg_match($patron, $normalizado, $m)) {
      return cat_caudal_apply_decimal($m[1]);
    }
  }
  return null;
}

$db = (new CatalogoCaudalApplyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

try {
  $stmt = $db->query("SELECT id_atributo_erp FROM erp_catalogo_atributos WHERE LOWER(TRIM(nombre)) = 'caudal' AND estatus = 'activo' ORDER BY id_atributo_erp LIMIT 1");
  $idCaudal = intval($stmt->fetchColumn());
  if (!$idCaudal) {
    throw new RuntimeException('No existe atributo canonico caudal activo.');
  }

  $select = $db->prepare("
    SELECT
      p.nombre AS producto,
      p.descripcion,
      s.id_sku,
      s.sku
    FROM erp_catalogo_productos p
    INNER JOIN erp_catalogo_skus s ON s.id_producto_erp = p.id_producto_erp
    WHERE p.estatus <> 'fusionado'
      AND s.estatus <> 'fusionado'
      AND (
        LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%l/h%'
        OR LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%l / h%'
        OR LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%l/hr%'
        OR LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%lt/hr%'
        OR LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%lph%'
        OR LOWER(CONCAT_WS(' ', p.nombre, p.descripcion, s.sku)) LIKE '%litros por hora%'
      )
  ");
  $upsert = $db->prepare("
    INSERT INTO erp_catalogo_sku_atributos (id_sku, id_atributo_erp, valor)
    VALUES (:sku, :atributo, :valor)
    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
  ");

  $db->beginTransaction();
  $select->execute();

  $resultado = array('aplicados' => 0, 'omitidos' => 0, 'detalles' => array());
  foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $valor = cat_caudal_apply_extraer(implode(' ', array($fila['producto'], $fila['descripcion'], $fila['sku'])));
    if ($valor === null) {
      $resultado['omitidos']++;
      continue;
    }
    $upsert->execute(array(
      ':sku' => intval($fila['id_sku']),
      ':atributo' => $idCaudal,
      ':valor' => rtrim(rtrim(number_format($valor, 4, '.', ''), '0'), '.')
    ));
    $resultado['aplicados']++;
    $resultado['detalles'][] = array(
      'sku' => $fila['sku'],
      'producto' => $fila['producto'],
      'caudal_l_h' => $valor
    );
  }

  $db->commit();
  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'error' => false,
    'mensaje' => 'Caudal canonico aplicado desde evidencia explicita.',
    'resultado' => $resultado
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'error' => true,
    'mensaje' => $e->getMessage()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(1);
}
