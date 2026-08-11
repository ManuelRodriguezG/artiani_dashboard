<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: verificar si existen atributos canonicos propuestos para saneamiento del Catalogo ERP.
 * Impacto: solo lectura; no modifica productos, SKUs, atributos ni unidades.
 * Contrato: imprime JSON con atributos canonicos encontrados y faltantes.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosCanonicosReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$canonicos = array(
  'consumo_electrico' => array('unidad' => 'w', 'tipo_dato' => 'numero'),
  'caudal' => array('unidad' => 'l/h', 'tipo_dato' => 'numero'),
  'altura_maxima' => array('unidad' => 'm', 'tipo_dato' => 'numero'),
  'capacidad_acuario_min' => array('unidad' => 'l', 'tipo_dato' => 'numero'),
  'capacidad_acuario_max' => array('unidad' => 'l', 'tipo_dato' => 'numero')
);

$db = (new CatalogoAtributosCanonicosReadonlyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$nombres = array_keys($canonicos);
$placeholders = implode(',', array_fill(0, count($nombres), '?'));
$stmt = $db->prepare("
  SELECT id_atributo_erp, codigo, nombre, tipo_dato, unidad, es_variante, estatus
  FROM erp_catalogo_atributos
  WHERE LOWER(TRIM(nombre)) IN ($placeholders)
  ORDER BY nombre
");
$stmt->execute($nombres);
$encontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$porNombre = array();
foreach ($encontrados as $fila) {
  $porNombre[strtolower(trim($fila['nombre']))] = $fila;
}

$faltantes = array();
foreach ($canonicos as $nombre => $config) {
  if (!isset($porNombre[$nombre])) {
    $faltantes[] = array(
      'nombre' => $nombre,
      'tipo_dato_sugerido' => $config['tipo_dato'],
      'unidad_sugerida' => $config['unidad']
    );
  }
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'canonicos_revisados' => count($canonicos),
  'encontrados' => $encontrados,
  'faltantes' => $faltantes
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
