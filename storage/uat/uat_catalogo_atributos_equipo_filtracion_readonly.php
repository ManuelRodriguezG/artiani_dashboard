<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: auditar candidatos de atributos tecnicos para equipo electrico y filtracion.
 * Impacto: solo lectura; no modifica catalogo, productos, SKUs, atributos, categorias ni unidades.
 * Contrato: imprime JSON con sugerencias detectadas desde atributos actuales, nombre y descripcion.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosEquipoReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_atributos_equipo_normalizar($texto) {
  $texto = html_entity_decode(strip_tags((string) $texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $texto = mb_strtolower($texto, 'UTF-8');
  $buscar = array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'Ã¡', 'Ã©', 'Ã­', 'Ã³', 'Ãº', 'Ã¼', 'Ã±');
  $reemplazar = array('a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'u', 'n');
  $texto = str_replace($buscar, $reemplazar, $texto);
  return preg_replace('/\s+/', ' ', trim($texto));
}

function cat_atributos_equipo_decimal($valor) {
  $valor = str_replace(',', '.', (string) $valor);
  if (!preg_match('/-?\d+(?:\.\d+)?/', $valor, $m)) {
    return null;
  }
  return round((float) $m[0], 4);
}

function cat_atributos_equipo_medida_metros($valor) {
  $normalizado = cat_atributos_equipo_normalizar($valor);
  $numero = cat_atributos_equipo_decimal($normalizado);
  if ($numero === null) {
    return null;
  }
  if (preg_match('/\bcm\b/u', $normalizado)) {
    return round($numero / 100, 4);
  }
  return $numero;
}

function cat_atributos_equipo_agregar_sugerencia(&$registro, $atributo, $valor, $unidad, $confianza, $evidencia, $motivo) {
  if ($valor === null || $valor === '') {
    return;
  }
  $clave = $atributo . '|' . $valor . '|' . $unidad;
  if (isset($registro['claves_sugerencias'][$clave])) {
    return;
  }
  $registro['claves_sugerencias'][$clave] = true;
  $registro['sugerencias'][] = array(
    'atributo_canonico' => $atributo,
    'valor' => $valor,
    'unidad_atributo' => $unidad,
    'confianza' => $confianza,
    'evidencia' => $evidencia,
    'motivo' => $motivo
  );
}

function cat_atributos_equipo_extraer_texto($registro, $texto) {
  $normalizado = cat_atributos_equipo_normalizar($texto);

  if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:w|watts?)\b/u', $normalizado, $m)) {
    cat_atributos_equipo_agregar_sugerencia($registro, 'consumo_electrico', cat_atributos_equipo_decimal($m[1]), 'w', 'media', $m[0], 'Detectado en texto libre.');
  }

  if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:l\/h|lt\/h|lts\/h|litros?\s*(?:por|\/)\s*hora)\b/u', $normalizado, $m)) {
    cat_atributos_equipo_agregar_sugerencia($registro, 'caudal', cat_atributos_equipo_decimal($m[1]), 'l/h', 'media', $m[0], 'Detectado en texto libre.');
  }

  if (preg_match('/(?:subida|altura|maxima|max\.?|head).{0,24}?(\d+(?:[\.,]\d+)?)\s*(?:cm|m|mts?|metros?)\b/u', $normalizado, $m)) {
    cat_atributos_equipo_agregar_sugerencia($registro, 'altura_maxima', cat_atributos_equipo_medida_metros($m[0]), 'm', 'media', $m[0], 'Detectado en texto libre con contexto de altura/subida.');
  }

  if (preg_match('/(?:acuarios?|peceras?).{0,28}?(\d+(?:[\.,]\d+)?)\s*(?:-|a|hasta)\s*(\d+(?:[\.,]\d+)?)\s*(?:l|lt|lts|litros?)\b/u', $normalizado, $m)) {
    cat_atributos_equipo_agregar_sugerencia($registro, 'capacidad_acuario_min', cat_atributos_equipo_decimal($m[1]), 'l', 'media', $m[0], 'Detectado como rango recomendado para acuario.');
    cat_atributos_equipo_agregar_sugerencia($registro, 'capacidad_acuario_max', cat_atributos_equipo_decimal($m[2]), 'l', 'media', $m[0], 'Detectado como rango recomendado para acuario.');
  } elseif (preg_match('/(?:para|recomendado).{0,32}?(\d+(?:[\.,]\d+)?)\s*(?:l|lt|lts|litros?)\b/u', $normalizado, $m)) {
    cat_atributos_equipo_agregar_sugerencia($registro, 'capacidad_acuario_max', cat_atributos_equipo_decimal($m[1]), 'l', 'baja', $m[0], 'Detectado como posible capacidad recomendada; requiere revision.');
  }
}

function cat_atributos_equipo_es_candidato($texto, $atributos) {
  $normalizado = cat_atributos_equipo_normalizar($texto);
  $palabras = array('filtro', 'filtracion', 'bomba', 'cabeza de poder', 'cascada', 'canister', 'sumergible', 'aireador', 'oxigenador', 'oxigeno', 'calentador', 'termocalentador', 'lampara', 'luz', 'led', 'uv');
  foreach ($palabras as $palabra) {
    if (strpos($normalizado, $palabra) !== false) {
      return true;
    }
  }
  foreach ($atributos as $nombre => $valor) {
    $n = cat_atributos_equipo_normalizar($nombre);
    if (in_array($n, array('potencia', 'subida'), true)) {
      return true;
    }
  }
  return false;
}

$db = (new CatalogoAtributosEquipoReadonlyDb())->db();
if (!$db) {
  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'modo' => 'readonly',
    'error' => true,
    'mensaje' => 'No se pudo obtener conexion a base de datos.'
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(1);
}

$sql = "
  SELECT
    p.id_producto_erp,
    p.codigo_producto,
    p.nombre AS producto,
    p.descripcion,
    p.estatus AS producto_estatus,
    s.id_sku,
    s.sku,
    s.estatus AS sku_estatus,
    COALESCE(c.ruta, c.nombre, '') AS categoria_actual,
    a.nombre AS atributo_nombre,
    sa.valor AS atributo_valor
  FROM erp_catalogo_productos p
  INNER JOIN erp_catalogo_skus s ON s.id_producto_erp = p.id_producto_erp
  LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_sku = s.id_sku
  LEFT JOIN erp_catalogo_atributos a ON a.id_atributo_erp = sa.id_atributo_erp
  LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp = p.id_producto_erp AND pc.es_principal = 1
  LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp = pc.id_categoria_erp
  WHERE p.estatus <> 'fusionado'
    AND s.estatus <> 'fusionado'
  ORDER BY p.id_producto_erp DESC, s.id_sku, a.nombre
";

$filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$skus = array();

foreach ($filas as $fila) {
  $idSku = intval($fila['id_sku']);
  if (!isset($skus[$idSku])) {
    $skus[$idSku] = array(
      'id_producto_erp' => intval($fila['id_producto_erp']),
      'codigo_producto' => $fila['codigo_producto'],
      'producto' => $fila['producto'],
      'descripcion' => $fila['descripcion'],
      'producto_estatus' => $fila['producto_estatus'],
      'id_sku' => $idSku,
      'sku' => $fila['sku'],
      'sku_estatus' => $fila['sku_estatus'],
      'categoria_actual' => $fila['categoria_actual'],
      'atributos_actuales' => array()
    );
  }
  if (!empty($fila['atributo_nombre']) && trim((string) $fila['atributo_valor']) !== '') {
    $skus[$idSku]['atributos_actuales'][$fila['atributo_nombre']] = $fila['atributo_valor'];
  }
}

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'skus_revisados' => count($skus),
  'candidatos_total' => 0,
  'conteo_sugerencias' => array(),
  'advertencias' => array(),
  'candidatos_muestra' => array()
);

foreach ($skus as $sku) {
  $texto = implode(' ', array($sku['producto'], $sku['descripcion'], $sku['categoria_actual']));
  if (!cat_atributos_equipo_es_candidato($texto, $sku['atributos_actuales'])) {
    continue;
  }

  $registro = array(
    'id_producto_erp' => $sku['id_producto_erp'],
    'codigo_producto' => $sku['codigo_producto'],
    'producto' => $sku['producto'],
    'id_sku' => $sku['id_sku'],
    'sku' => $sku['sku'],
    'categoria_actual' => $sku['categoria_actual'],
    'atributos_actuales' => $sku['atributos_actuales'],
    'sugerencias' => array(),
    'advertencias' => array(),
    'claves_sugerencias' => array()
  );

  foreach ($sku['atributos_actuales'] as $nombre => $valor) {
    $nombreNormalizado = cat_atributos_equipo_normalizar($nombre);
    if ($nombreNormalizado === 'potencia') {
      cat_atributos_equipo_agregar_sugerencia($registro, 'consumo_electrico', cat_atributos_equipo_decimal($valor), 'w', 'alta', $nombre . ': ' . $valor, 'Atributo actual Potencia se mapea a consumo_electrico.');
    }
    if ($nombreNormalizado === 'subida') {
      cat_atributos_equipo_agregar_sugerencia($registro, 'altura_maxima', cat_atributos_equipo_medida_metros($valor), 'm', 'alta', $nombre . ': ' . $valor, 'Atributo actual Subida se mapea a altura_maxima.');
    }
    if ($nombreNormalizado === 'capacidad') {
      if (preg_match('/(\d+(?:[\.,]\d+)?)\s*(?:-|a)\s*(\d+(?:[\.,]\d+)?)\s*(?:l|lt|lts|litros?)?/iu', (string) $valor, $m)) {
        cat_atributos_equipo_agregar_sugerencia($registro, 'capacidad_acuario_min', cat_atributos_equipo_decimal($m[1]), 'l', 'media', $nombre . ': ' . $valor, 'Capacidad parece rango recomendado; requiere revision por familia.');
        cat_atributos_equipo_agregar_sugerencia($registro, 'capacidad_acuario_max', cat_atributos_equipo_decimal($m[2]), 'l', 'media', $nombre . ': ' . $valor, 'Capacidad parece rango recomendado; requiere revision por familia.');
      } else {
        $registro['advertencias'][] = 'Capacidad existe pero es ambigua; no se sugiere destino automatico sin revisar familia.';
      }
    }
  }

  cat_atributos_equipo_extraer_texto($registro, $sku['producto'] . ' ' . $sku['descripcion']);

  unset($registro['claves_sugerencias']);
  if (empty($registro['sugerencias']) && empty($registro['advertencias'])) {
    continue;
  }

  $salida['candidatos_total']++;
  foreach ($registro['sugerencias'] as $sugerencia) {
    $attr = $sugerencia['atributo_canonico'];
    if (!isset($salida['conteo_sugerencias'][$attr])) {
      $salida['conteo_sugerencias'][$attr] = 0;
    }
    $salida['conteo_sugerencias'][$attr]++;
  }
  if (count($salida['candidatos_muestra']) < 120) {
    $salida['candidatos_muestra'][] = $registro;
  }
}

ksort($salida['conteo_sugerencias']);
echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
