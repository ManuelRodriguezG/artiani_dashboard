<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: migrar valores heredados de equipo a atributos canonicos sin borrar los atributos origen.
 * Impacto: inserta/actualiza valores en erp_catalogo_sku_atributos para consumo_electrico y altura_maxima.
 * Contrato: Potencia -> consumo_electrico en w; Subida -> altura_maxima en m; conserva atributos heredados.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosEquipoMigracionApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_equipo_apply_norm($texto) {
  $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  return preg_replace('/\s+/', ' ', $texto);
}

function cat_equipo_apply_decimal($valor) {
  $valor = str_replace(',', '.', (string) $valor);
  if (!preg_match('/-?\d+(?:\.\d+)?/', $valor, $m)) {
    return null;
  }
  return round((float) $m[0], 4);
}

function cat_equipo_apply_metros($valor) {
  $normalizado = cat_equipo_apply_norm($valor);
  $numero = cat_equipo_apply_decimal($normalizado);
  if ($numero === null) {
    return null;
  }
  if (preg_match('/\bcm\b/u', $normalizado)) {
    return round($numero / 100, 4);
  }
  return $numero;
}

$db = (new CatalogoAtributosEquipoMigracionApplyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

try {
  $stmt = $db->query("
    SELECT id_atributo_erp, nombre
    FROM erp_catalogo_atributos
    WHERE LOWER(TRIM(nombre)) IN ('potencia', 'subida', 'consumo_electrico', 'altura_maxima')
  ");
  $ids = array();
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $ids[cat_equipo_apply_norm($fila['nombre'])] = intval($fila['id_atributo_erp']);
  }

  foreach (array('potencia', 'subida', 'consumo_electrico', 'altura_maxima') as $requerido) {
    if (empty($ids[$requerido])) {
      throw new RuntimeException('Falta atributo requerido: ' . $requerido);
    }
  }

  $db->beginTransaction();

  $select = $db->prepare("
    SELECT sa.id_sku, sa.valor
    FROM erp_catalogo_sku_atributos sa
    INNER JOIN erp_catalogo_skus s ON s.id_sku = sa.id_sku
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
    WHERE sa.id_atributo_erp = :origen
      AND TRIM(sa.valor) <> ''
      AND p.estatus <> 'fusionado'
      AND s.estatus <> 'fusionado'
  ");
  $upsert = $db->prepare("
    INSERT INTO erp_catalogo_sku_atributos (id_sku, id_atributo_erp, valor)
    VALUES (:sku, :atributo, :valor)
    ON DUPLICATE KEY UPDATE valor = VALUES(valor)
  ");

  $resultado = array(
    'consumo_electrico' => array('insertados_actualizados' => 0, 'omitidos' => 0),
    'altura_maxima' => array('insertados_actualizados' => 0, 'omitidos' => 0)
  );

  $migraciones = array(
    array('origen' => 'potencia', 'destino' => 'consumo_electrico', 'normalizador' => 'cat_equipo_apply_decimal'),
    array('origen' => 'subida', 'destino' => 'altura_maxima', 'normalizador' => 'cat_equipo_apply_metros')
  );

  foreach ($migraciones as $migracion) {
    $select->execute(array(':origen' => $ids[$migracion['origen']]));
    foreach ($select->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $valor = call_user_func($migracion['normalizador'], $fila['valor']);
      if ($valor === null) {
        $resultado[$migracion['destino']]['omitidos']++;
        continue;
      }
      $upsert->execute(array(
        ':sku' => intval($fila['id_sku']),
        ':atributo' => $ids[$migracion['destino']],
        ':valor' => rtrim(rtrim(number_format($valor, 4, '.', ''), '0'), '.')
      ));
      $resultado[$migracion['destino']]['insertados_actualizados']++;
    }
  }

  $db->commit();
  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'error' => false,
    'mensaje' => 'Migracion de atributos tecnicos aplicada sin borrar atributos heredados.',
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
