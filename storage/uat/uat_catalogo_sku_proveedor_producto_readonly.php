<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-10-24
 * Proposito: conciliar SKUs de proveedor contra SKUs ERP de un producto piloto.
 * Impacto: Catalogo ERP y Compras; no modifica relaciones, costos ni listas.
 * Contrato: ejecutar con `--id_producto=ID`; usa conexion productiva read-only.
 */

if (PHP_SAPI !== "cli") {
  http_response_code(404);
  exit;
}

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
require_once __DIR__ . "/../../app/iniciador.php";

$opciones = getopt("", array("id_producto::"));
$idProducto = isset($opciones["id_producto"]) ? intval($opciones["id_producto"]) : 24;
$db = null;
$salida = array("ok" => false, "read_only" => true, "id_producto_erp" => $idProducto);

try {
  $db = new PDO(
    "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8mb4",
    MYSQLUSER,
    MYSQLPASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
  );
  $db->exec("START TRANSACTION READ ONLY");

  $stmtProducto = $db->prepare("SELECT id_producto_erp, codigo_producto, nombre, maneja_variantes, estatus FROM erp_catalogo_productos WHERE id_producto_erp=:producto LIMIT 1");
  $stmtProducto->execute(array(":producto" => $idProducto));
  $salida["producto"] = $stmtProducto->fetch();

  $stmtSkus = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, s.estatus, u.codigo unidad_base, s.factor_unidad_base, sp.id_sku_proveedor, sp.id_proveedor, prov.proveedor, sp.sku_proveedor, sp.id_unidad_compra, uc.codigo unidad_compra, sp.factor_conversion, sp.costo_ultimo, sp.cantidad_minima, sp.es_preferido, sp.estatus estatus_relacion FROM erp_catalogo_skus s LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku LEFT JOIN erp_proveedores prov ON prov.id_proveedor=sp.id_proveedor LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad=sp.id_unidad_compra WHERE s.id_producto_erp=:producto ORDER BY s.id_sku, sp.id_proveedor, sp.id_sku_proveedor");
  $stmtSkus->execute(array(":producto" => $idProducto));
  $salida["skus_y_relaciones"] = $stmtSkus->fetchAll();

  $stmtListas = $db->prepare("SELECT l.id_proveedor, prov.proveedor, l.id_lista_proveedor, l.lista, l.fch_r, lp.id_producto id_producto_proveedor, lp.sku sku_proveedor, lp.nombre nombre_proveedor, lp.costo, lp.codigo_barras, CASE WHEN EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_proveedor=l.id_proveedor AND LOWER(TRIM(sp.sku_proveedor))=LOWER(TRIM(lp.sku)) AND sp.id_sku IN (SELECT id_sku FROM erp_catalogo_skus WHERE id_producto_erp=:producto)) THEN 'vinculado_producto' ELSE 'no_vinculado_producto' END estado_match FROM erp_proveedores_listas_productos lp INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor=lp.id_lista_proveedor LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor WHERE TRIM(COALESCE(lp.sku,''))<>'' AND l.id_proveedor IN (SELECT DISTINCT sp.id_proveedor FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE s.id_producto_erp=:producto2) ORDER BY l.id_proveedor, l.fch_r DESC, lp.id_producto LIMIT 500");
  $stmtListas->execute(array(":producto" => $idProducto, ":producto2" => $idProducto));
  $salida["filas_listas_proveedor"] = $stmtListas->fetchAll();

  $salida["listas_del_proveedor"] = $db->query("SELECT l.id_lista_proveedor, l.id_proveedor, prov.proveedor, l.lista, l.fch_r, COUNT(lp.id_producto) total_filas, SUM(CASE WHEN TRIM(COALESCE(lp.sku,''))<>'' THEN 1 ELSE 0 END) filas_con_sku FROM erp_proveedores_listas l LEFT JOIN erp_proveedores prov ON prov.id_proveedor=l.id_proveedor LEFT JOIN erp_proveedores_listas_productos lp ON lp.id_lista_proveedor=l.id_lista_proveedor WHERE l.id_proveedor IN (SELECT DISTINCT sp.id_proveedor FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE s.id_producto_erp=" . intval($idProducto) . ") GROUP BY l.id_lista_proveedor, l.id_proveedor, prov.proveedor, l.lista, l.fch_r ORDER BY l.fch_r DESC, l.id_lista_proveedor DESC")->fetchAll();

  $salida["resumen"] = array(
    "skus_producto" => count($salida["skus_y_relaciones"]),
    "relaciones_activas" => count(array_filter($salida["skus_y_relaciones"], function ($fila) { return isset($fila["estatus_relacion"]) && $fila["estatus_relacion"] === "activo"; })),
    "proveedores_distintos" => count(array_unique(array_filter(array_column($salida["skus_y_relaciones"], "id_proveedor")))),
    "filas_listas" => count($salida["filas_listas_proveedor"])
  );
  $salida["reglas"] = array(
    "no_reacomodar_por_nombre_unicamente" => true,
    "validar_sku_proveedor_con_empaque_o_lista" => true,
    "no_mover_costo_historico_automaticamente" => true,
    "no_cambiar_preferido_sin_propuesta" => true
  );
  $salida["ok"] = true;
} catch (Throwable $e) {
  $salida["error"] = $e->getMessage();
} finally {
  if ($db && $db->inTransaction()) {
    $db->rollBack();
  }
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
