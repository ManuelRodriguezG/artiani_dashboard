<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: crear atributos canonicos base para equipo electrico y filtracion en Catalogo ERP.
 * Impacto: modifica solo el maestro erp_catalogo_atributos; no migra valores de SKUs ni toca productos.
 * Contrato: inserta/actualiza cinco atributos canonicos idempotentes y devuelve JSON con resultado.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosCanonicosEquipoApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$atributos = array(
  array('ATR-CONSUMO-ELECTRICO', 'consumo_electrico', 'numero', 'w'),
  array('ATR-CAUDAL', 'caudal', 'numero', 'l/h'),
  array('ATR-ALTURA-MAXIMA', 'altura_maxima', 'numero', 'm'),
  array('ATR-CAP-ACUARIO-MIN', 'capacidad_acuario_min', 'numero', 'l'),
  array('ATR-CAP-ACUARIO-MAX', 'capacidad_acuario_max', 'numero', 'l')
);

$db = (new CatalogoAtributosCanonicosEquipoApplyDb())->db();
if (!$db) {
  echo json_encode(array('error' => true, 'mensaje' => 'No se pudo obtener conexion a base de datos.'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

try {
  $db->beginTransaction();
  $stmt = $db->prepare("
    INSERT INTO erp_catalogo_atributos
      (codigo, nombre, tipo_dato, unidad, configuracion_json, es_variante, estatus)
    VALUES
      (:codigo, :nombre, :tipo_dato, :unidad, NULL, 0, 'activo')
    ON DUPLICATE KEY UPDATE
      nombre = VALUES(nombre),
      tipo_dato = VALUES(tipo_dato),
      unidad = VALUES(unidad),
      es_variante = VALUES(es_variante),
      estatus = VALUES(estatus)
  ");

  $aplicados = array();
  foreach ($atributos as $atributo) {
    $stmt->execute(array(
      ':codigo' => $atributo[0],
      ':nombre' => $atributo[1],
      ':tipo_dato' => $atributo[2],
      ':unidad' => $atributo[3]
    ));
    $aplicados[] = array(
      'codigo' => $atributo[0],
      'nombre' => $atributo[1],
      'tipo_dato' => $atributo[2],
      'unidad' => $atributo[3]
    );
  }

  $db->commit();
  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'error' => false,
    'mensaje' => 'Atributos canonicos de equipo creados/actualizados.',
    'aplicados' => $aplicados
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
