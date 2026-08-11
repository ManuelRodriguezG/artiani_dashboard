<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: auditar caudal de filtros/bombas desde nombre y descripcion de SKUs de Catalogo ERP.
 * Impacto: solo lectura; no modifica productos, SKUs ni atributos.
 * Contrato: detecta valores expresados como l/h, l/hr, lt/hr, lph o litros por hora y reporta conflictos.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoCaudalReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_caudal_norm($texto) {
  $texto = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $texto = mb_strtolower($texto, 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  $texto = preg_replace('/\s+/', ' ', $texto);
  return trim($texto);
}

function cat_caudal_decimal($valor) {
  $valor = str_replace(',', '.', (string) $valor);
  if (!preg_match('/\d+(?:\.\d+)?/', $valor, $m)) {
    return null;
  }
  return round((float) $m[0], 4);
}

function cat_caudal_extraer($texto) {
  $normalizado = cat_caudal_norm($texto);
  $patrones = array(
    '/(\d+(?:[\.,]\d+)?)\s*(?:l|lt|lts)\s*\/\s*(?:h|hr|hrs|hora)\b/u',
    '/(\d+(?:[\.,]\d+)?)\s*(?:lph|l\.p\.h\.)\b/u',
    '/(\d+(?:[\.,]\d+)?)\s*litros?\s*(?:por|\/)\s*hora\b/u'
  );
  foreach ($patrones as $patron) {
    if (preg_match($patron, $normalizado, $m)) {
      return array(
        'valor' => cat_caudal_decimal($m[1]),
        'evidencia' => $m[0]
      );
    }
  }
  return null;
}

$db = (new CatalogoCaudalReadonlyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$stmt = $db->query("
  SELECT id_atributo_erp, nombre
  FROM erp_catalogo_atributos
  WHERE LOWER(TRIM(nombre)) IN ('caudal')
");
$idCaudal = null;
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
  $idCaudal = intval($fila['id_atributo_erp']);
}
if (!$idCaudal) {
  echo json_encode(array('error' => true, 'mensaje' => 'No existe atributo canonico caudal.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$sql = "
  SELECT
    p.id_producto_erp,
    p.codigo_producto,
    p.nombre AS producto,
    p.descripcion,
    s.id_sku,
    s.sku,
    c.ruta AS categoria_actual,
    sc.valor AS caudal_existente
  FROM erp_catalogo_productos p
  INNER JOIN erp_catalogo_skus s ON s.id_producto_erp = p.id_producto_erp
  LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
  LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp = pc.id_categoria_erp
  LEFT JOIN erp_catalogo_sku_atributos sc ON sc.id_sku = s.id_sku AND sc.id_atributo_erp = :caudal
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
  ORDER BY p.id_producto_erp DESC, s.id_sku
";

$stmt = $db->prepare($sql);
$stmt->execute(array(':caudal' => $idCaudal));
$filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'id_atributo_caudal' => $idCaudal,
  'filas_revisadas' => count($filas),
  'candidatos' => array(),
  'resumen' => array(
    'detectados' => 0,
    'insertables' => 0,
    'ya_existentes' => 0,
    'conflictos' => 0,
    'sin_valor_extraible' => 0
  )
);

foreach ($filas as $fila) {
  $texto = implode(' ', array($fila['producto'], $fila['descripcion'], $fila['sku']));
  $extraido = cat_caudal_extraer($texto);
  if (!$extraido || $extraido['valor'] === null) {
    $salida['resumen']['sin_valor_extraible']++;
    $salida['candidatos'][] = array(
      'sku' => $fila['sku'],
      'producto' => $fila['producto'],
      'categoria_actual' => $fila['categoria_actual'],
      'estado' => 'sin_valor_extraible'
    );
    continue;
  }

  $estado = empty($fila['caudal_existente']) ? 'insertar' : 'ya_existe';
  if (!empty($fila['caudal_existente'])) {
    $existente = cat_caudal_decimal($fila['caudal_existente']);
    if ($existente !== null && abs($existente - $extraido['valor']) > 0.0001) {
      $estado = 'conflicto';
      $salida['resumen']['conflictos']++;
    } else {
      $salida['resumen']['ya_existentes']++;
    }
  } else {
    $salida['resumen']['insertables']++;
  }
  $salida['resumen']['detectados']++;

  $salida['candidatos'][] = array(
    'id_producto_erp' => intval($fila['id_producto_erp']),
    'codigo_producto' => $fila['codigo_producto'],
    'producto' => $fila['producto'],
    'id_sku' => intval($fila['id_sku']),
    'sku' => $fila['sku'],
    'categoria_actual' => $fila['categoria_actual'],
    'valor_caudal' => $extraido['valor'],
    'unidad' => 'l/h',
    'evidencia' => $extraido['evidencia'],
    'caudal_existente' => $fila['caudal_existente'],
    'estado' => $estado
  );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
