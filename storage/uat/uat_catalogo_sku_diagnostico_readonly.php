<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: diagnosticar un SKU ERP por codigo, estatus y relaciones principales sin modificar datos.
 * Impacto: solo lectura; no toca Catalogo, Inventario, Compras, Ventas ni Ecommerce.
 * Contrato: recibe --sku=CODIGO y devuelve producto padre, codigos, proveedor, imagenes y posibles bloqueos de UI.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoSkuDiagnosticoReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function diag_arg($nombre, $default = '') {
  global $argv;
  $prefijo = '--' . $nombre . '=';
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo) === 0) {
      return substr($arg, strlen($prefijo));
    }
  }
  return $default;
}

$sku = trim((string) diag_arg('sku'));
if ($sku === '') {
  fwrite(STDERR, "Uso: php storage\\uat\\uat_catalogo_sku_diagnostico_readonly.php --sku=SPH-600\n");
  exit(1);
}

$db = (new CatalogoSkuDiagnosticoReadonlyDb())->db();
$like = '%' . $sku . '%';

$stmt = $db->prepare("
  SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre AS nombre_sku, s.estatus AS estatus_sku,
    s.tipo_inventario, s.id_unidad_base, s.factor_unidad_base,
    p.codigo_producto, p.nombre AS nombre_producto, p.estatus AS estatus_producto,
    u.nombre AS unidad, u.abreviatura,
    pc.id_categoria_erp,
    c.ruta AS categoria
  FROM erp_catalogo_skus s
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
  LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
  LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
  WHERE UPPER(s.sku)=UPPER(:sku)
     OR UPPER(p.codigo_producto)=UPPER(:sku)
     OR EXISTS (
       SELECT 1 FROM erp_catalogo_sku_codigos cod
       WHERE cod.id_sku=s.id_sku AND UPPER(cod.codigo)=UPPER(:sku)
     )
  ORDER BY s.id_sku
");
$stmt->execute(array(':sku' => $sku));
$exactos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
  SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre AS nombre_sku, s.estatus AS estatus_sku,
    p.codigo_producto, p.nombre AS nombre_producto, p.estatus AS estatus_producto
  FROM erp_catalogo_skus s
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  WHERE s.sku LIKE :like OR p.codigo_producto LIKE :like OR s.nombre LIKE :like OR p.nombre LIKE :like
  ORDER BY s.sku
  LIMIT 25
");
$stmt->execute(array(':like' => $like));
$parecidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$detalles = array();
foreach ($exactos as $fila) {
  $idSku = intval($fila['id_sku']);
  $idProducto = intval($fila['id_producto_erp']);

  $q = $db->prepare("SELECT id_sku_codigo, tipo_codigo, codigo, es_principal, estatus FROM erp_catalogo_sku_codigos WHERE id_sku=:sku ORDER BY es_principal DESC, id_sku_codigo");
  $q->execute(array(':sku' => $idSku));
  $codigos = $q->fetchAll(PDO::FETCH_ASSOC);

  $q = $db->prepare("SELECT id_sku_proveedor, id_proveedor, sku_proveedor, factor_conversion, es_preferido, estatus FROM erp_catalogo_sku_proveedores WHERE id_sku=:sku ORDER BY es_preferido DESC, id_sku_proveedor");
  $q->execute(array(':sku' => $idSku));
  $proveedores = $q->fetchAll(PDO::FETCH_ASSOC);

  $q = $db->prepare("SELECT id_imagen_erp, id_sku, tipo_imagen, url_imagen, orden, estatus FROM erp_catalogo_imagenes WHERE id_producto_erp=:producto ORDER BY FIELD(tipo_imagen, 'portada', 'empaque', 'detalle', 'galeria', 'referencia'), orden, id_imagen_erp");
  $q->execute(array(':producto' => $idProducto));
  $imagenes = $q->fetchAll(PDO::FETCH_ASSOC);

  $q = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_producto_erp=:producto");
  $q->execute(array(':producto' => $idProducto));
  $totalSkusProducto = intval($q->fetchColumn());

  $q = $db->prepare("SELECT COUNT(*) FROM erp_catalogo_skus WHERE id_producto_erp=:producto AND estatus NOT IN ('inactivo','descontinuado','fusionado')");
  $q->execute(array(':producto' => $idProducto));
  $skusVisibles = intval($q->fetchColumn());

  $bloqueosUi = array();
  if (in_array((string) $fila['estatus_producto'], array('inactivo', 'descontinuado', 'fusionado'), true)) {
    $bloqueosUi[] = 'producto_padre_archivado';
  }
  if (in_array((string) $fila['estatus_sku'], array('inactivo', 'descontinuado', 'fusionado'), true)) {
    $bloqueosUi[] = 'sku_archivado';
  }
  if ($totalSkusProducto > 0 && $skusVisibles === 0) {
    $bloqueosUi[] = 'sin_skus_visibles_por_filtro_vigentes';
  }

  $detalles[] = array(
    'sku' => $fila,
    'total_skus_producto' => $totalSkusProducto,
    'skus_visibles_vigentes' => $skusVisibles,
    'bloqueos_ui_probables' => $bloqueosUi,
    'codigos' => $codigos,
    'proveedores' => $proveedores,
    'imagenes' => $imagenes
  );
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'busqueda' => $sku,
  'exactos' => $detalles,
  'parecidos' => $parecidos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
