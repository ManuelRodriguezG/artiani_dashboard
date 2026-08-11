<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: previsualizar migracion de atributos heredados de equipo a atributos canonicos.
 * Impacto: solo lectura; no modifica productos, SKUs ni atributos.
 * Contrato: reporta candidatos para Potencia -> consumo_electrico y Subida -> altura_maxima, con conflictos.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosEquipoMigracionPreviewDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_equipo_prev_norm($texto) {
  $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  return preg_replace('/\s+/', ' ', $texto);
}

function cat_equipo_prev_decimal($valor) {
  $valor = str_replace(',', '.', (string) $valor);
  if (!preg_match('/-?\d+(?:\.\d+)?/', $valor, $m)) {
    return null;
  }
  return round((float) $m[0], 4);
}

function cat_equipo_prev_metros($valor) {
  $normalizado = cat_equipo_prev_norm($valor);
  $numero = cat_equipo_prev_decimal($normalizado);
  if ($numero === null) {
    return null;
  }
  if (preg_match('/\bcm\b/u', $normalizado)) {
    return round($numero / 100, 4);
  }
  return $numero;
}

$db = (new CatalogoAtributosEquipoMigracionPreviewDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$stmt = $db->query("
  SELECT id_atributo_erp, nombre
  FROM erp_catalogo_atributos
  WHERE LOWER(TRIM(nombre)) IN ('potencia', 'subida', 'consumo_electrico', 'altura_maxima')
");
$ids = array();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
  $ids[cat_equipo_prev_norm($fila['nombre'])] = intval($fila['id_atributo_erp']);
}

$pares = array(
  array('origen' => 'potencia', 'destino' => 'consumo_electrico', 'unidad' => 'w'),
  array('origen' => 'subida', 'destino' => 'altura_maxima', 'unidad' => 'm')
);

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'ids' => $ids,
  'resumen' => array(),
  'muestras' => array(),
  'conflictos' => array()
);

foreach ($pares as $par) {
  $origen = $par['origen'];
  $destino = $par['destino'];
  if (empty($ids[$origen]) || empty($ids[$destino])) {
    $salida['resumen'][$destino] = array(
      'error' => true,
      'mensaje' => 'No existe atributo origen o destino.',
      'origen' => $origen,
      'destino' => $destino
    );
    continue;
  }

  $stmt = $db->prepare("
    SELECT
      sa.id_sku_atributo,
      sa.id_sku,
      sa.valor AS valor_origen,
      sd.id_sku_atributo AS id_destino_existente,
      sd.valor AS valor_destino_existente,
      s.sku,
      p.id_producto_erp,
      p.codigo_producto,
      p.nombre AS producto
    FROM erp_catalogo_sku_atributos sa
    INNER JOIN erp_catalogo_skus s ON s.id_sku = sa.id_sku
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
    LEFT JOIN erp_catalogo_sku_atributos sd
      ON sd.id_sku = sa.id_sku AND sd.id_atributo_erp = :destino
    WHERE sa.id_atributo_erp = :origen
      AND TRIM(sa.valor) <> ''
      AND p.estatus <> 'fusionado'
      AND s.estatus <> 'fusionado'
    ORDER BY p.id_producto_erp DESC, s.id_sku
  ");
  $stmt->execute(array(':origen' => $ids[$origen], ':destino' => $ids[$destino]));
  $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $validos = 0;
  $insertables = 0;
  $yaExistentes = 0;
  $conflictos = 0;

  foreach ($filas as $fila) {
    $valorNormalizado = $origen === 'subida'
      ? cat_equipo_prev_metros($fila['valor_origen'])
      : cat_equipo_prev_decimal($fila['valor_origen']);

    if ($valorNormalizado === null) {
      $conflictos++;
      $salida['conflictos'][] = array(
        'tipo' => 'valor_no_numerico',
        'destino' => $destino,
        'sku' => $fila['sku'],
        'producto' => $fila['producto'],
        'valor_origen' => $fila['valor_origen']
      );
      continue;
    }
    $validos++;

    if (!empty($fila['id_destino_existente'])) {
      $yaExistentes++;
      $existente = cat_equipo_prev_decimal($fila['valor_destino_existente']);
      if ($existente !== null && abs($existente - $valorNormalizado) > 0.0001) {
        $conflictos++;
        $salida['conflictos'][] = array(
          'tipo' => 'destino_existente_distinto',
          'destino' => $destino,
          'sku' => $fila['sku'],
          'producto' => $fila['producto'],
          'valor_origen' => $fila['valor_origen'],
          'valor_normalizado' => $valorNormalizado,
          'valor_destino_existente' => $fila['valor_destino_existente']
        );
      }
    } else {
      $insertables++;
    }

    if (count($salida['muestras']) < 80) {
      $salida['muestras'][] = array(
        'destino' => $destino,
        'sku' => $fila['sku'],
        'producto' => $fila['producto'],
        'valor_origen' => $fila['valor_origen'],
        'valor_normalizado' => $valorNormalizado,
        'unidad_destino' => $par['unidad'],
        'accion' => empty($fila['id_destino_existente']) ? 'insertar' : 'ya_existe'
      );
    }
  }

  $salida['resumen'][$destino] = array(
    'origen' => $origen,
    'origen_id' => $ids[$origen],
    'destino_id' => $ids[$destino],
    'filas_origen' => count($filas),
    'validos' => $validos,
    'insertables' => $insertables,
    'ya_existentes' => $yaExistentes,
    'conflictos' => $conflictos
  );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
