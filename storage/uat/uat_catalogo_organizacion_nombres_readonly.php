<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-08
 * Proposito: auditar candidatos de mejora de nombres para Catalogo ERP sin modificar productos ni propuestas.
 * Impacto: solo lectura; compara revision existente, relaciones proveedor-SKU y listas proveedor ERP/legacy.
 * Contrato: devuelve conteos y ejemplos para decidir si se deben generar nuevas propuestas de nombre.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class UatCatalogoOrganizacionNombresDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function limpiar_nombre_audit($nombre) {
  $nombre = preg_replace('/\s+/', ' ', trim((string) $nombre));
  $nombre = strip_tags(html_entity_decode($nombre, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
  $nombre = preg_replace('/\s+/', ' ', trim($nombre));
  if ($nombre === '' || !mb_check_encoding($nombre, 'UTF-8') || preg_match('/[Â¢Â£Â ï¿½]/u', $nombre)) {
    return '';
  }
  $nombre = preg_replace('/\s+by\s+/i', ' ', $nombre);
  $nombre = preg_replace('/\bIMPORTAD[OA]S?\b/i', '', $nombre);
  $nombre = preg_replace('/\s+/', ' ', trim($nombre));
  $nombre = preg_replace_callback('/(\d+(?:\.\d+)?)\s*[x×]\s*(\d+(?:\.\d+)?)(?:\s*[x×]\s*(\d+(?:\.\d+)?))?/iu', function ($m) {
    return $m[1] . ' x ' . $m[2] . (isset($m[3]) && $m[3] !== '' ? ' x ' . $m[3] : '');
  }, $nombre);
  return $nombre;
}

function clave_nombre_audit($nombre) {
  return mb_strtolower(preg_replace('/[^[:alnum:]]+/u', '', (string) $nombre), 'UTF-8');
}

$db = (new UatCatalogoOrganizacionNombresDb())->db();
$salida = array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'revision_estatus' => array(),
  'candidatos_erp_nuevo' => array('total' => 0, 'sin_revision' => 0, 'ejemplos' => array()),
  'candidatos_legacy' => array('total' => 0, 'sin_revision' => 0, 'ejemplos' => array()),
  'observaciones' => array()
);

$salida['revision_estatus'] = $db->query("SELECT estatus, COUNT(*) total FROM erp_catalogo_revision_nombres GROUP BY estatus ORDER BY estatus")->fetchAll(PDO::FETCH_ASSOC);

$sqlNuevo = "SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre nombre_actual, p.nombre nombre_maestro,
    sp.id_proveedor, sp.sku_proveedor, ld.descripcion_proveedor nombre_proveedor, ld.id_lista_detalle_erp,
    r.id_revision_nombre, r.estatus estatus_revision
  FROM erp_catalogo_sku_proveedores sp
  INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku AND s.estatus<>'fusionado'
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp AND p.estatus<>'fusionado'
  INNER JOIN erp_proveedores_listas_detalle_erp ld ON ld.id_sku_proveedor=sp.id_sku_proveedor AND ld.id_sku=sp.id_sku
  LEFT JOIN erp_catalogo_revision_nombres r ON r.id_sku=s.id_sku
  WHERE sp.estatus='activo' AND TRIM(COALESCE(ld.descripcion_proveedor,''))<>''
  ORDER BY s.id_sku, ld.id_lista_detalle_erp DESC";

$vistos = array();
foreach ($db->query($sqlNuevo)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
  $idSku = intval($fila['id_sku']);
  if (isset($vistos[$idSku])) {
    continue;
  }
  $vistos[$idSku] = true;
  $propuesto = limpiar_nombre_audit($fila['nombre_proveedor']);
  if ($propuesto === '' || clave_nombre_audit($propuesto) === clave_nombre_audit($fila['nombre_actual'])) {
    continue;
  }
  $salida['candidatos_erp_nuevo']['total']++;
  if (empty($fila['id_revision_nombre'])) {
    $salida['candidatos_erp_nuevo']['sin_revision']++;
    if (count($salida['candidatos_erp_nuevo']['ejemplos']) < 25) {
      $salida['candidatos_erp_nuevo']['ejemplos'][] = array(
        'id_sku' => $idSku,
        'sku' => $fila['sku'],
        'nombre_actual' => $fila['nombre_actual'],
        'nombre_proveedor' => $fila['nombre_proveedor'],
        'id_proveedor' => intval($fila['id_proveedor']),
        'sku_proveedor' => $fila['sku_proveedor']
      );
    }
  }
}

$tablaLegacy = null;
$stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='erp_proveedores_listas_productos' LIMIT 1");
$stmt->execute();
if ($stmt->fetchColumn()) {
  $tablaLegacy = 'erp_proveedores_listas_productos';
}

if ($tablaLegacy) {
  $sqlLegacy = "SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre nombre_actual,
      pl.nombre nombre_proveedor, pl.marca marca_proveedor, pl.id_lista_proveedor,
      r.id_revision_nombre, r.estatus estatus_revision
    FROM erp_catalogo_skus s
    INNER JOIN " . $tablaLegacy . " pl ON LOWER(TRIM(pl.sku))=LOWER(TRIM(s.sku))
    INNER JOIN (
      SELECT LOWER(TRIM(sku)) sku_clave, MAX(id_producto) id_producto
      FROM " . $tablaLegacy . "
      WHERE sku IS NOT NULL AND TRIM(sku)<>''
      GROUP BY LOWER(TRIM(sku))
    ) ultima ON ultima.id_producto=pl.id_producto
    LEFT JOIN erp_catalogo_revision_nombres r ON r.id_sku=s.id_sku
    WHERE s.estatus<>'fusionado' AND TRIM(COALESCE(pl.nombre,''))<>''
    ORDER BY s.id_sku";
  foreach ($db->query($sqlLegacy)->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $propuesto = limpiar_nombre_audit($fila['nombre_proveedor']);
    if ($propuesto === '' || clave_nombre_audit($propuesto) === clave_nombre_audit($fila['nombre_actual'])) {
      continue;
    }
    $salida['candidatos_legacy']['total']++;
    if (empty($fila['id_revision_nombre'])) {
      $salida['candidatos_legacy']['sin_revision']++;
      if (count($salida['candidatos_legacy']['ejemplos']) < 25) {
        $salida['candidatos_legacy']['ejemplos'][] = array(
          'id_sku' => intval($fila['id_sku']),
          'sku' => $fila['sku'],
          'nombre_actual' => $fila['nombre_actual'],
          'nombre_proveedor' => $fila['nombre_proveedor'],
          'marca_proveedor' => $fila['marca_proveedor']
        );
      }
    }
  }
} else {
  $salida['observaciones'][] = 'No existe erp_proveedores_listas_productos en la base actual; la generacion legacy del modelo no tendria fuente local.';
}

if ($salida['candidatos_erp_nuevo']['sin_revision'] > 0) {
  $salida['observaciones'][] = 'Hay candidatos desde listas ERP nuevas que no aparecen en Organizacion porque el generador actual solo usa la fuente legacy.';
}
if ($salida['candidatos_legacy']['sin_revision'] > 0) {
  $salida['observaciones'][] = 'Hay candidatos legacy sin revision; falta exponer o ejecutar generacion controlada de propuestas.';
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
