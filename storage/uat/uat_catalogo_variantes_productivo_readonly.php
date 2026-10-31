<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-10-24
 * Proposito: auditar en modo read-only productos, SKUs, atributos y relaciones
 * para proponer variantes reales, atributos y SKUs derivados.
 * Impacto: Catalogo ERP y contrato Ecommerce; no modifica datos ni esquema.
 * Contrato: usa la conexion productiva definida por SERVER_NAME y devuelve JSON.
 */

if (PHP_SAPI !== "cli") {
  http_response_code(404);
  exit;
}

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
require_once __DIR__ . "/../../app/iniciador.php";

$db = null;
$salida = array(
  "ok" => false,
  "read_only" => true,
  "conexion" => "productiva_según_SERVER_NAME",
  "fecha" => date("c"),
  "resumen" => array(),
  "atributos" => array(),
  "candidatos" => array(),
  "relaciones" => array(),
  "limitaciones" => array()
);

try {
  $db = new PDO(
    "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8mb4",
    MYSQLUSER,
    MYSQLPASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
  );
  $db->exec("START TRANSACTION READ ONLY");

  $salida["resumen"] = array(
    "productos_no_fusionados" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus <> 'fusionado'")->fetchColumn()),
    "productos_manejan_variantes" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos WHERE estatus <> 'fusionado' AND maneja_variantes=1")->fetchColumn()),
    "skus_no_fusionados" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus WHERE estatus <> 'fusionado'")->fetchColumn()),
    "skus_activos" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus WHERE estatus='activo'")->fetchColumn()),
    "productos_multiples_skus" => intval($db->query("SELECT COUNT(*) FROM (SELECT id_producto_erp FROM erp_catalogo_skus WHERE estatus NOT IN ('fusionado','inactivo','descontinuado') GROUP BY id_producto_erp HAVING COUNT(*) > 1) x")->fetchColumn()),
    "productos_variantes_sin_atributo" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_productos p WHERE p.estatus <> 'fusionado' AND p.maneja_variantes=1 AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s INNER JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku INNER JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.es_variante=1 WHERE s.id_producto_erp=p.id_producto_erp)")->fetchColumn()),
    "skus_fraccionarios" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_skus s INNER JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku WHERE s.estatus NOT IN ('fusionado','inactivo','descontinuado') AND r.permite_venta_fraccionaria=1")->fetchColumn()),
    "presentaciones_activas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_sku_presentaciones WHERE estatus='activo'")->fetchColumn()),
    "aperturas_activas" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_sku_aperturas_empaque WHERE estatus='activo'")->fetchColumn()),
    "paquetes_activos" => intval($db->query("SELECT COUNT(*) FROM erp_catalogo_sku_paquetes WHERE estatus='activo'")->fetchColumn())
  );

  $salida["atributos"] = $db->query("SELECT a.id_atributo_erp, a.codigo, a.nombre, a.tipo_dato, a.unidad, a.es_variante, a.estatus, COUNT(sa.id_sku_atributo) uso_skus FROM erp_catalogo_atributos a LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_atributo_erp=a.id_atributo_erp WHERE a.estatus='activo' GROUP BY a.id_atributo_erp, a.codigo, a.nombre, a.tipo_dato, a.unidad, a.es_variante, a.estatus ORDER BY a.es_variante DESC, a.nombre")->fetchAll();

  $salida["candidatos"] = $db->query("SELECT p.id_producto_erp, p.codigo_producto, p.nombre producto, p.maneja_variantes, COUNT(DISTINCT s.id_sku) skus_activos, GROUP_CONCAT(DISTINCT CONCAT(s.id_sku, ':', s.sku, COALESCE(CONCAT(' [', s.nombre, ']'), '')) ORDER BY s.id_sku SEPARATOR ' || ') skus, GROUP_CONCAT(DISTINCT CONCAT(a.codigo, '=', sa.valor) ORDER BY a.codigo, sa.valor SEPARATOR ' || ') firma_atributos, CASE WHEN p.maneja_variantes=1 AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus sx INNER JOIN erp_catalogo_sku_atributos sax ON sax.id_sku=sx.id_sku INNER JOIN erp_catalogo_atributos ax ON ax.id_atributo_erp=sax.id_atributo_erp AND ax.es_variante=1 WHERE sx.id_producto_erp=p.id_producto_erp) THEN 'variantes_sin_atributos' WHEN MAX(CASE WHEN LOWER(CONCAT(' ', p.nombre, ' ', COALESCE(s.nombre,''), ' ', s.sku)) REGEXP '([0-9]+([.,][0-9]+)?)[ ]*(g|gr|kg|ml|l|lt|litro|litros|cm|mm|pza|pzas)' THEN 1 ELSE 0 END) > 0 THEN 'posible_presentacion_o_medida' ELSE 'revision_identidad' END propuesta FROM erp_catalogo_productos p INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp AND s.estatus NOT IN ('fusionado','inactivo','descontinuado') LEFT JOIN erp_catalogo_sku_atributos sa ON sa.id_sku=s.id_sku LEFT JOIN erp_catalogo_atributos a ON a.id_atributo_erp=sa.id_atributo_erp AND a.estatus='activo' WHERE p.estatus <> 'fusionado' GROUP BY p.id_producto_erp, p.codigo_producto, p.nombre, p.maneja_variantes HAVING COUNT(DISTINCT s.id_sku) > 1 ORDER BY skus_activos DESC, p.id_producto_erp LIMIT 300")->fetchAll();

  $salida["relaciones"] = array(
    "presentaciones" => $db->query("SELECT base.id_sku id_sku_base, base.sku sku_base, pres.id_sku id_sku_presentacion, pres.sku sku_presentacion, r.factor_salida_base, r.modo_disponibilidad, r.requiere_empaque FROM erp_catalogo_sku_presentaciones r INNER JOIN erp_catalogo_skus base ON base.id_sku=r.id_sku_base INNER JOIN erp_catalogo_skus pres ON pres.id_sku=r.id_sku_presentacion WHERE r.estatus='activo' ORDER BY base.id_sku, pres.id_sku LIMIT 300")->fetchAll(),
    "aperturas" => $db->query("SELECT o.id_sku id_sku_origen, o.sku sku_origen, d.id_sku id_sku_destino, d.sku sku_destino, a.factor_conversion, a.requiere_unidad_fisica, a.conserva_lote, a.conserva_caducidad FROM erp_catalogo_sku_aperturas_empaque a INNER JOIN erp_catalogo_skus o ON o.id_sku=a.id_sku_origen INNER JOIN erp_catalogo_skus d ON d.id_sku=a.id_sku_destino WHERE a.estatus='activo' ORDER BY o.id_sku, d.id_sku LIMIT 300")->fetchAll(),
    "paquetes" => $db->query("SELECT p.id_paquete, s.id_sku, s.sku, s.nombre, p.tipo_paquete, p.modo_disponibilidad, p.permite_configuracion_cliente, COUNT(c.id_componente) componentes_activos FROM erp_catalogo_sku_paquetes p INNER JOIN erp_catalogo_skus s ON s.id_sku=p.id_sku_paquete LEFT JOIN erp_catalogo_sku_paquete_componentes c ON c.id_paquete=p.id_paquete AND c.estatus='activo' WHERE p.estatus='activo' GROUP BY p.id_paquete, s.id_sku, s.sku, s.nombre, p.tipo_paquete, p.modo_disponibilidad, p.permite_configuracion_cliente ORDER BY p.id_paquete LIMIT 300")->fetchAll()
  );

  $salida["limitaciones"] = array(
    "propuesta_no_es_decision" => true,
    "nombres_son_pista" => true,
    "no_se_infieren_variantes_solo_por_texto" => true,
    "requiere_validacion_fisica_o_documental" => true
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
