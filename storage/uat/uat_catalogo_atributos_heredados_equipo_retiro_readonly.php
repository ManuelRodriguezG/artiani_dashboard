<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: auditar si los atributos heredados Potencia y Subida pueden retirarse de operacion.
 * Impacto: solo lectura; no modifica productos, SKUs ni atributos.
 * Contrato: verifica usos, cobertura canonica y conflictos antes de inactivar atributos heredados.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosHeredadosEquipoReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_ret_equipo_norm($texto) {
  $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  return preg_replace('/\s+/', ' ', $texto);
}

$db = (new CatalogoAtributosHeredadosEquipoReadonlyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$stmt = $db->query("
  SELECT id_atributo_erp, nombre, codigo, estatus
  FROM erp_catalogo_atributos
  WHERE LOWER(TRIM(nombre)) IN ('potencia', 'subida', 'consumo_electrico', 'altura_maxima')
");
$attrs = array();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
  $attrs[cat_ret_equipo_norm($fila['nombre'])] = $fila;
}

$pares = array(
  array('origen' => 'potencia', 'destino' => 'consumo_electrico'),
  array('origen' => 'subida', 'destino' => 'altura_maxima')
);

$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'atributos' => $attrs,
  'resumen' => array(),
  'puede_inactivar' => true
);

foreach ($pares as $par) {
  $origen = $par['origen'];
  $destino = $par['destino'];
  if (empty($attrs[$origen]) || empty($attrs[$destino])) {
    $salida['puede_inactivar'] = false;
    $salida['resumen'][$origen] = array('error' => true, 'mensaje' => 'Falta origen o destino.');
    continue;
  }

  $stmt = $db->prepare("
    SELECT
      COUNT(*) AS usos_origen,
      SUM(CASE WHEN sd.id_sku_atributo IS NULL THEN 1 ELSE 0 END) AS sin_destino,
      SUM(CASE WHEN sd.id_sku_atributo IS NOT NULL THEN 1 ELSE 0 END) AS con_destino
    FROM erp_catalogo_sku_atributos so
    LEFT JOIN erp_catalogo_sku_atributos sd
      ON sd.id_sku = so.id_sku AND sd.id_atributo_erp = :destino
    WHERE so.id_atributo_erp = :origen
  ");
  $stmt->execute(array(
    ':origen' => intval($attrs[$origen]['id_atributo_erp']),
    ':destino' => intval($attrs[$destino]['id_atributo_erp'])
  ));
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  $sinDestino = intval($r['sin_destino']);
  if ($sinDestino > 0) {
    $salida['puede_inactivar'] = false;
  }
  $salida['resumen'][$origen] = array(
    'destino' => $destino,
    'estatus_origen' => $attrs[$origen]['estatus'],
    'usos_origen' => intval($r['usos_origen']),
    'con_destino_canonico' => intval($r['con_destino']),
    'sin_destino_canonico' => $sinDestino,
    'decision' => $sinDestino === 0 ? 'inactivable' : 'no_inactivar'
  );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
