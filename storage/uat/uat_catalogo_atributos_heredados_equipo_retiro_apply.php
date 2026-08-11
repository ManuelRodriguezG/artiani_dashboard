<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-10
 * Proposito: inactivar atributos heredados de equipo ya migrados a canonicos.
 * Impacto: modifica solo estatus en erp_catalogo_atributos para Potencia y Subida; no borra valores historicos.
 * Contrato: inactiva solo si todos los SKUs con atributo heredado tienen destino canonico correspondiente.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoAtributosHeredadosEquipoApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function cat_ret_apply_norm($texto) {
  $texto = mb_strtolower(trim((string) $texto), 'UTF-8');
  $texto = str_replace(array('á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'), array('a', 'e', 'i', 'o', 'u', 'u', 'n'), $texto);
  return preg_replace('/\s+/', ' ', $texto);
}

$db = (new CatalogoAtributosHeredadosEquipoApplyDb())->db();
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
  $attrs = array();
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $attrs[cat_ret_apply_norm($fila['nombre'])] = intval($fila['id_atributo_erp']);
  }

  $pares = array(
    array('origen' => 'potencia', 'destino' => 'consumo_electrico'),
    array('origen' => 'subida', 'destino' => 'altura_maxima')
  );

  foreach ($pares as $par) {
    if (empty($attrs[$par['origen']]) || empty($attrs[$par['destino']])) {
      throw new RuntimeException('Falta atributo requerido: ' . $par['origen'] . ' / ' . $par['destino']);
    }
    $check = $db->prepare("
      SELECT COUNT(*)
      FROM erp_catalogo_sku_atributos so
      LEFT JOIN erp_catalogo_sku_atributos sd
        ON sd.id_sku = so.id_sku AND sd.id_atributo_erp = :destino
      WHERE so.id_atributo_erp = :origen
        AND sd.id_sku_atributo IS NULL
    ");
    $check->execute(array(':origen' => $attrs[$par['origen']], ':destino' => $attrs[$par['destino']]));
    if (intval($check->fetchColumn()) > 0) {
      throw new RuntimeException('No se puede inactivar ' . $par['origen'] . ': hay SKUs sin destino canonico.');
    }
  }

  $db->beginTransaction();
  $update = $db->prepare("
    UPDATE erp_catalogo_atributos
    SET estatus = 'inactivo'
    WHERE id_atributo_erp = :id
  ");
  $inactivados = array();
  foreach ($pares as $par) {
    $update->execute(array(':id' => $attrs[$par['origen']]));
    $inactivados[] = $par['origen'];
  }
  $db->commit();

  echo json_encode(array(
    'fecha' => date('Y-m-d H:i:s'),
    'error' => false,
    'mensaje' => 'Atributos heredados de equipo inactivados sin borrar valores historicos.',
    'inactivados' => $inactivados
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
