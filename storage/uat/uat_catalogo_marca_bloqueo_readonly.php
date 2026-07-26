<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: auditar por que una marca de Catalogo ERP no puede inactivarse.
 * Impacto: Read-only; no modifica marcas, productos ni relaciones.
 * Contrato: ejecutar por CLI con --codigo=ECOM-MAR-24 o --id=<id_marca_erp>.
 */

require_once __DIR__ . '/../../app/iniciador.php';
require_once __DIR__ . '/../../app/core/CRUD.php';

$opciones = getopt('', array('codigo::', 'id::'));
$codigo = isset($opciones['codigo']) ? trim((string) $opciones['codigo']) : '';
$id = isset($opciones['id']) ? intval($opciones['id']) : 0;

if ($codigo === '' && $id <= 0) {
  echo json_encode(array(
    'error' => true,
    'mensaje' => 'Captura --codigo o --id para auditar la marca.'
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

try {
  $auditor = new class extends CRUD {
    public function conexionReadOnly() {
      return $this->getConexion();
    }
  };
  $db = $auditor->conexionReadOnly();

  if ($id > 0) {
    $stmt = $db->prepare('SELECT id_marca_erp, codigo, nombre, descripcion, estatus FROM erp_catalogo_marcas WHERE id_marca_erp = :id LIMIT 1');
    $stmt->execute(array(':id' => $id));
  } else {
    $stmt = $db->prepare('SELECT id_marca_erp, codigo, nombre, descripcion, estatus FROM erp_catalogo_marcas WHERE codigo = :codigo LIMIT 1');
    $stmt->execute(array(':codigo' => $codigo));
  }

  $marca = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$marca) {
    echo json_encode(array(
      'error' => true,
      'mensaje' => 'Marca no encontrada.',
      'criterio' => array('codigo' => $codigo, 'id' => $id)
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(1);
  }

  $idMarca = intval($marca['id_marca_erp']);

  $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_productos WHERE id_marca_erp = :id AND estatus IN ('activo','borrador','en_revision')");
  $stmt->execute(array(':id' => $idMarca));
  $productosOperativosBloqueantes = intval($stmt->fetchColumn());

  $stmt = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_productos WHERE id_marca_erp = :id AND estatus IN ('inactivo','descontinuado','fusionado')");
  $stmt->execute(array(':id' => $idMarca));
  $productosArchivados = intval($stmt->fetchColumn());

  $stmt = $db->prepare("SELECT estatus, COUNT(*) total FROM erp_catalogo_productos WHERE id_marca_erp = :id GROUP BY estatus ORDER BY estatus");
  $stmt->execute(array(':id' => $idMarca));
  $resumenProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $stmt = $db->prepare("SELECT id_producto_erp, codigo_producto, nombre, estatus FROM erp_catalogo_productos WHERE id_marca_erp = :id AND estatus IN ('activo','borrador','en_revision') ORDER BY estatus, id_producto_erp LIMIT 100");
  $stmt->execute(array(':id' => $idMarca));
  $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(array(
    'error' => false,
    'mensaje' => 'Auditoria read-only de marca completada.',
    'marca' => $marca,
    'bloqueo_actual' => array(
      'regla' => "No se puede inactivar una marca si tiene productos operativos: activo, borrador o en_revision.",
      'productos_operativos_bloqueantes' => $productosOperativosBloqueantes,
      'productos_archivados_no_bloqueantes' => $productosArchivados
    ),
    'resumen_productos_por_estatus' => $resumenProductos,
    'productos_operativos_bloqueantes_muestra' => $productos
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
  echo json_encode(array(
    'error' => true,
    'mensaje' => $e->getMessage()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}
