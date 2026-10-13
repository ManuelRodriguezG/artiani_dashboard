<?php
/**
 * IA: Codex GPT-5 | Fecha: 2026-10-12
 * Proposito: crear SKUs granel del producto GRAVA-EST y aperturas desde los SKUs cerrados.
 * Impacto: Catalogo ERP; usa CatalogoErpDatos para guardar SKUs y aperturas, sin tocar inventario, ventas, POS, ecommerce, precios ni costos.
 * Contrato: requiere token CATALOGO_GRAVA_EST_GRANEL para backup/apply; genera respaldo externo antes de escribir.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

const TOKEN_AUTORIZADO = "CATALOGO_GRAVA_EST_GRANEL";
const BACKUP_DIR = "C:\\xampp\\panel_db_backups";

$modo = isset($argv[1]) ? (string) $argv[1] : "preflight";
$token = isset($argv[2]) ? (string) $argv[2] : "";

if (!in_array($modo, array("preflight", "backup", "apply", "postcheck"), true)) {
  salida(array("error" => true, "mensaje" => "Modo invalido. Usa preflight, backup, apply o postcheck."));
}
if (in_array($modo, array("backup", "apply"), true) && $token !== TOKEN_AUTORIZADO) {
  salida(array("error" => true, "mensaje" => "Token no autorizado para escritura/respaldo externo."));
}

$db = conectar();
$modelo = new CatalogoErpDatos();
$plan = array(
  array("producto_codigo" => "GRAVA-EST", "sku_cerrado" => "GRAVA-14", "sku_granel" => "GRAVA-14-GRANEL", "nombre_granel" => "Grava para acuario espesor de 3 mm estandar color azul cielo 1 kg GRANEL", "kg_por_empaque" => 25),
  array("producto_codigo" => "GRAVA-EST", "sku_cerrado" => "GRAVA-25", "sku_granel" => "GRAVA-25-GRANEL", "nombre_granel" => "Grava para acuario espesor 3mm estandar color rojo 1 kg GRANEL", "kg_por_empaque" => 25),
  array("producto_codigo" => "GRAVA-EST", "sku_cerrado" => "GRAVA-32", "sku_granel" => "GRAVA-32-GRANEL", "nombre_granel" => "Grava para acuario espesor de 3 mm estandar color negro 1 kg GRANEL", "kg_por_empaque" => 25)
);

try {
  if ($modo === "preflight") { salida(preflight($db, $plan)); }
  if ($modo === "backup") { salida(respaldo($db, $plan)); }
  if ($modo === "apply") { salida(aplicar($db, $modelo, $plan)); }
  salida(postcheck($db, $modelo, $plan));
} catch (Exception $e) {
  salida(array("error" => true, "mensaje" => $e->getMessage()));
}

function conectar() {
  return new PDO(
    "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8mb4",
    MYSQLUSER,
    MYSQLPASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
  );
}

function salida($payload) {
  echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  echo PHP_EOL;
  exit(empty($payload["error"]) ? 0 : 1);
}

function unidades($db) {
  $rows = $db->query("SELECT id_unidad, codigo FROM erp_catalogo_unidades WHERE codigo IN ('PZA','KG') AND estatus='activa'")->fetchAll(PDO::FETCH_ASSOC);
  $mapa = array();
  foreach ($rows as $row) { $mapa[$row["codigo"]] = intval($row["id_unidad"]); }
  foreach (array("PZA", "KG") as $codigo) {
    if (empty($mapa[$codigo])) { throw new Exception("Falta unidad activa requerida: " . $codigo); }
  }
  return $mapa;
}

function skuPorCodigo($db, $sku) {
  $stmt = $db->prepare("SELECT s.*, p.codigo_producto, p.nombre producto_nombre, p.estatus producto_estatus
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    WHERE s.sku=:sku LIMIT 1");
  $stmt->execute(array(":sku" => $sku));
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function detalleSku($db, $sku) {
  $stmt = $db->prepare("SELECT s.*, p.codigo_producto, p.nombre producto_nombre, p.estatus producto_estatus,
      COALESCE(r.controla_inventario, 1) controla_inventario,
      COALESCE(r.permite_existencia_negativa, 0) permite_existencia_negativa,
      COALESCE(r.requiere_lote, 0) requiere_lote,
      COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
      COALESCE(r.requiere_serie, 0) requiere_serie,
      COALESCE(r.requiere_serie_fabricante, 0) requiere_serie_fabricante,
      COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
      COALESCE(r.requiere_escaneo_venta, 0) requiere_escaneo_venta,
      COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
      COALESCE(r.precision_decimal, 0) precision_decimal,
      COALESCE(r.incremento_minimo_venta, 1) incremento_minimo_venta,
      COALESCE(r.unidad_venta_label, '') unidad_venta_label,
      COALESCE(r.permite_etiqueta_fraccionada, 0) permite_etiqueta_fraccionada,
      COALESCE(r.prefijo_etiqueta_interna, '') prefijo_etiqueta_interna,
      COALESCE(r.plantilla_etiqueta, '') plantilla_etiqueta,
      COALESCE(r.tipo_etiqueta_seguridad, '') tipo_etiqueta_seguridad,
      COALESCE(r.instrucciones_etiquetado, '') instrucciones_etiquetado,
      COALESCE(r.estrategia_salida, 'FIFO') estrategia_salida,
      COALESCE(r.stock_minimo, 0) stock_minimo,
      r.stock_maximo,
      COALESCE(r.punto_reorden, 0) punto_reorden,
      COALESCE(r.dias_alerta_caducidad, 90) dias_alerta_caducidad,
      COALESCE(r.dias_minimos_recepcion, 0) dias_minimos_recepcion,
      COALESCE(r.requiere_cantidad_variable_recepcion, 0) requiere_cantidad_variable_recepcion,
      COALESCE(r.requiere_unidades_fisicas_recepcion, 0) requiere_unidades_fisicas_recepcion,
      r.tolerancia_recepcion_porcentaje,
      COALESCE(r.nota_recepcion_variable, '') nota_recepcion_variable,
      COALESCE(imp.clave_producto_sat, '') clave_producto_sat,
      COALESCE(imp.clave_unidad_sat, '') clave_unidad_sat,
      COALESCE(imp.objeto_impuesto, '') objeto_impuesto,
      imp.iva_porcentaje,
      imp.ieps_porcentaje,
      COALESCE(imp.incluye_impuestos, 0) incluye_impuestos
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
    LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku=s.id_sku
    WHERE s.sku=:sku LIMIT 1");
  $stmt->execute(array(":sku" => $sku));
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function datosSkuDesdeBase($base, $overrides) {
  $datos = array(
    "id_producto_erp" => intval($base["id_producto_erp"]),
    "sku" => $base["sku"],
    "nombre_sku" => $base["nombre"],
    "id_unidad_base" => intval($base["id_unidad_base"]),
    "factor_unidad_base" => $base["factor_unidad_base"],
    "tipo_inventario" => $base["tipo_inventario"],
    "estatus" => $base["estatus"],
    "costo_referencia" => $base["costo_referencia"],
    "permite_venta_sin_existencia" => intval($base["permite_venta_sin_existencia"]),
    "stock_minimo" => $base["stock_minimo"],
    "stock_maximo" => $base["stock_maximo"] === null ? "" : $base["stock_maximo"],
    "punto_reorden" => $base["punto_reorden"],
    "estrategia_salida" => $base["estrategia_salida"],
    "dias_alerta_caducidad" => $base["dias_alerta_caducidad"],
    "dias_minimos_recepcion" => $base["dias_minimos_recepcion"],
    "requiere_lote" => intval($base["requiere_lote"]),
    "requiere_caducidad" => intval($base["requiere_caducidad"]),
    "requiere_serie" => intval($base["requiere_serie"]),
    "permite_existencia_negativa" => intval($base["permite_existencia_negativa"]),
    "requiere_serie_fabricante" => intval($base["requiere_serie_fabricante"]),
    "generar_etiqueta_interna" => intval($base["generar_etiqueta_interna"]),
    "requiere_escaneo_venta" => intval($base["requiere_escaneo_venta"]),
    "permite_venta_fraccionaria" => intval($base["permite_venta_fraccionaria"]),
    "precision_decimal" => intval($base["precision_decimal"]),
    "incremento_minimo_venta" => $base["incremento_minimo_venta"],
    "unidad_venta_label" => $base["unidad_venta_label"],
    "permite_etiqueta_fraccionada" => intval($base["permite_etiqueta_fraccionada"]),
    "prefijo_etiqueta_interna" => $base["prefijo_etiqueta_interna"],
    "plantilla_etiqueta" => $base["plantilla_etiqueta"],
    "tipo_etiqueta_seguridad" => $base["tipo_etiqueta_seguridad"],
    "instrucciones_etiquetado" => $base["instrucciones_etiquetado"],
    "requiere_cantidad_variable_recepcion" => intval($base["requiere_cantidad_variable_recepcion"]),
    "requiere_unidades_fisicas_recepcion" => intval($base["requiere_unidades_fisicas_recepcion"]),
    "tolerancia_recepcion_porcentaje" => $base["tolerancia_recepcion_porcentaje"] === null ? "" : $base["tolerancia_recepcion_porcentaje"],
    "nota_recepcion_variable" => $base["nota_recepcion_variable"],
    "clave_producto_sat" => $base["clave_producto_sat"],
    "clave_unidad_sat" => $base["clave_unidad_sat"],
    "objeto_impuesto" => $base["objeto_impuesto"],
    "iva_porcentaje" => $base["iva_porcentaje"] === null ? "" : $base["iva_porcentaje"],
    "ieps_porcentaje" => $base["ieps_porcentaje"] === null ? "" : $base["ieps_porcentaje"],
    "incluye_impuestos" => intval($base["incluye_impuestos"]),
    "codigo_barras" => ""
  );
  foreach ($overrides as $key => $value) { $datos[$key] = $value; }
  return $datos;
}

function guardarSku($db, $modelo, $sku, $datos, $idUsuario) {
  $existente = skuPorCodigo($db, $sku);
  if ($existente) {
    $datos["id_sku"] = intval($existente["id_sku"]);
    $respuesta = $modelo->actualizarSku($datos, $idUsuario);
  } else {
    $respuesta = $modelo->agregarSku($datos, $idUsuario);
  }
  if (!empty($respuesta["error"])) { throw new Exception("No se pudo guardar SKU " . $sku . ": " . $respuesta["mensaje"]); }
  $actual = skuPorCodigo($db, $sku);
  if (!$actual) { throw new Exception("SKU " . $sku . " no quedo disponible despues de guardar"); }
  return intval($actual["id_sku"]);
}

function skusPlan($plan) {
  $skus = array();
  foreach ($plan as $cfg) { $skus[] = $cfg["sku_cerrado"]; $skus[] = $cfg["sku_granel"]; }
  return array_values(array_unique($skus));
}

function preflight($db, $plan) {
  $items = array();
  foreach ($plan as $cfg) {
    $items[$cfg["sku_cerrado"]] = array(
      "sku_cerrado" => detalleSku($db, $cfg["sku_cerrado"]),
      "sku_granel_existente" => detalleSku($db, $cfg["sku_granel"]),
      "aperturas_existentes" => aperturas($db, $cfg),
      "kg_por_empaque" => $cfg["kg_por_empaque"]
    );
  }
  return array("error" => false, "mensaje" => "Preflight read-only", "items" => $items, "unidades" => unidades($db));
}

function respaldo($db, $plan) {
  if (!is_dir(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0777, true)) {
    throw new Exception("No se pudo crear directorio de respaldo: " . BACKUP_DIR);
  }
  $skus = skusPlan($plan);
  $payload = array(
    "fecha" => date("Y-m-d H:i:s"),
    "token" => TOKEN_AUTORIZADO,
    "base" => MYSQLBASE,
    "alcance" => "GRAVA-EST cerrado -> granel",
    "tablas" => array(
      "erp_catalogo_productos" => seleccionarPorSkus($db, "SELECT DISTINCT p.* FROM erp_catalogo_productos p INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_skus" => seleccionarPorSkus($db, "SELECT * FROM erp_catalogo_skus WHERE sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_reglas_inventario" => seleccionarPorSkus($db, "SELECT r.* FROM erp_catalogo_sku_reglas_inventario r INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_codigos" => seleccionarPorSkus($db, "SELECT c.* FROM erp_catalogo_sku_codigos c INNER JOIN erp_catalogo_skus s ON s.id_sku=c.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_impuestos" => seleccionarPorSkus($db, "SELECT i.* FROM erp_catalogo_sku_impuestos i INNER JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_proveedores" => seleccionarPorSkus($db, "SELECT sp.* FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_aperturas_empaque" => seleccionarPorSkus($db, "SELECT ae.* FROM erp_catalogo_sku_aperturas_empaque ae INNER JOIN erp_catalogo_skus o ON o.id_sku=ae.id_sku_origen INNER JOIN erp_catalogo_skus d ON d.id_sku=ae.id_sku_destino WHERE o.sku IN (__SKUS__) OR d.sku IN (__SKUS__)", $skus),
      "erp_notificaciones" => seleccionarPorSkus($db, "SELECT n.* FROM erp_notificaciones n INNER JOIN erp_catalogo_skus s ON s.id_sku=n.id_entidad_origen WHERE n.entidad_origen='erp_catalogo_skus' AND s.sku IN (__SKUS__)", $skus)
    )
  );
  $archivo = BACKUP_DIR . "\\catalogo_grava_est_granel_" . date("Ymd_His") . "_pre.json";
  if (file_put_contents($archivo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    throw new Exception("No se pudo escribir respaldo externo");
  }
  return array("error" => false, "mensaje" => "Respaldo externo generado", "archivo" => $archivo, "resumen" => array_map("count", $payload["tablas"]));
}

function seleccionarPorSkus($db, $sql, $skus) {
  $skus = array_values(array_unique($skus));
  $placeholders = array();
  $params = array();
  foreach ($skus as $i => $sku) { $ph = ":sku" . $i; $placeholders[] = $ph; $params[$ph] = $sku; }
  $stmt = $db->prepare(str_replace("__SKUS__", implode(",", $placeholders), $sql));
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function aplicar($db, $modelo, $plan) {
  $u = unidades($db);
  $idUsuario = 0;
  $acciones = array();
  foreach ($plan as $cfg) {
    $base = detalleSku($db, $cfg["sku_cerrado"]);
    if (!$base) { throw new Exception("No existe SKU cerrado " . $cfg["sku_cerrado"]); }
    if ($base["codigo_producto"] !== $cfg["producto_codigo"]) { throw new Exception("El SKU cerrado no pertenece al producto esperado " . $cfg["producto_codigo"]); }
    $idGranel = guardarSku($db, $modelo, $cfg["sku_granel"], datosSkuDesdeBase($base, array(
      "sku" => $cfg["sku_granel"],
      "nombre_sku" => $cfg["nombre_granel"],
      "id_unidad_base" => $u["KG"],
      "factor_unidad_base" => "1",
      "codigo_barras" => "INT-" . $cfg["sku_granel"],
      "estatus" => "activo",
      "permite_venta_fraccionaria" => 1,
      "precision_decimal" => 3,
      "incremento_minimo_venta" => "1",
      "unidad_venta_label" => "kg",
      "permite_etiqueta_fraccionada" => 0,
      "generar_etiqueta_interna" => 0,
      "requiere_unidades_fisicas_recepcion" => 0,
      "requiere_cantidad_variable_recepcion" => 0
    )), $idUsuario);
    $apertura = $modelo->guardarSkuAperturaEmpaque(array(
      "id_sku_origen" => intval($base["id_sku"]),
      "id_sku_destino" => $idGranel,
      "factor_conversion" => $cfg["kg_por_empaque"],
      "requiere_unidad_fisica" => 0,
      "conserva_lote" => 1,
      "conserva_caducidad" => 1,
      "permite_merma" => 0,
      "merma_porcentaje_default" => 0,
      "instrucciones_operativas" => "Apertura de costal de 25 kg hacia SKU granel.",
      "estatus" => "activo"
    ), $idUsuario);
    if (!empty($apertura["error"])) { throw new Exception("No se pudo guardar apertura " . $cfg["sku_cerrado"] . ": " . $apertura["mensaje"]); }
    $acciones[] = array("sku_granel" => $cfg["sku_granel"], "id_sku_granel" => $idGranel, "apertura" => $apertura["depurar"]);
  }
  return array("error" => false, "mensaje" => "SKUs granel GRAVA-EST creados y aperturas guardadas", "acciones" => $acciones, "postcheck" => postcheck($db, $modelo, $plan));
}

function aperturas($db, $cfg) {
  $stmt = $db->prepare("SELECT ae.id_apertura_empaque, o.sku origen, d.sku destino, ae.factor_conversion,
      ae.requiere_unidad_fisica, ae.conserva_lote, ae.conserva_caducidad, ae.permite_merma,
      ae.merma_porcentaje_default, ae.estatus
    FROM erp_catalogo_sku_aperturas_empaque ae
    INNER JOIN erp_catalogo_skus o ON o.id_sku=ae.id_sku_origen
    INNER JOIN erp_catalogo_skus d ON d.id_sku=ae.id_sku_destino
    WHERE o.sku IN (:cerrado,:granel) OR d.sku IN (:cerrado,:granel)
    ORDER BY ae.id_apertura_empaque");
  $stmt->execute(array(":cerrado" => $cfg["sku_cerrado"], ":granel" => $cfg["sku_granel"]));
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function notificacionesCosto($db, $cfg) {
  $stmt = $db->prepare("SELECT n.id_notificacion, n.tipo, n.area_responsable, n.estatus, n.titulo, n.fecha_registro
    FROM erp_notificaciones n
    INNER JOIN erp_catalogo_skus s ON s.id_sku=n.id_entidad_origen
    WHERE n.tipo='catalogo_sku_derivado_costo_pendiente'
      AND n.entidad_origen='erp_catalogo_skus'
      AND s.sku=:sku
    ORDER BY n.id_notificacion DESC");
  $stmt->execute(array(":sku" => $cfg["sku_granel"]));
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function postcheck($db, $modelo, $plan) {
  $items = array();
  foreach ($plan as $cfg) {
    $cerrado = detalleSku($db, $cfg["sku_cerrado"]);
    $granel = detalleSku($db, $cfg["sku_granel"]);
    $contexto = null;
    if ($granel) {
      $ctx = $modelo->resolverContextoSkuVendible(intval($granel["id_sku"]));
      $contexto = empty($ctx["error"]) ? $ctx["depurar"]["contexto"] : array("error" => $ctx["mensaje"]);
    }
    $items[$cfg["sku_cerrado"]] = array(
      "cerrado" => resumenSku($cerrado),
      "granel" => resumenSku($granel),
      "aperturas" => aperturas($db, $cfg),
      "contexto_granel" => $contexto ? array(
        "tipo_derivacion_rentabilidad" => isset($contexto["tipo_derivacion_rentabilidad"]) ? $contexto["tipo_derivacion_rentabilidad"] : null,
        "id_sku_origen" => isset($contexto["id_sku_origen"]) ? $contexto["id_sku_origen"] : null,
        "sku_origen" => isset($contexto["sku_origen"]) ? $contexto["sku_origen"] : null,
        "factor_conversion" => isset($contexto["factor_conversion"]) ? $contexto["factor_conversion"] : null,
        "requiere_costo" => isset($contexto["requiere_costo"]) ? $contexto["requiere_costo"] : null,
        "costo_derivable" => isset($contexto["costo_derivable"]) ? $contexto["costo_derivable"] : null,
        "advertencias_configuracion" => isset($contexto["advertencias_configuracion"]) ? $contexto["advertencias_configuracion"] : null
      ) : null,
      "notificaciones_costo_derivado" => notificacionesCosto($db, $cfg)
    );
  }
  return array("error" => false, "mensaje" => "Postcheck GRAVA-EST", "items" => $items);
}

function resumenSku($sku) {
  if (!$sku) { return null; }
  return array(
    "id_sku" => intval($sku["id_sku"]),
    "sku" => $sku["sku"],
    "nombre" => $sku["nombre"],
    "estatus" => $sku["estatus"],
    "id_unidad_base" => intval($sku["id_unidad_base"]),
    "factor_unidad_base" => $sku["factor_unidad_base"],
    "controla_inventario" => intval($sku["controla_inventario"]),
    "permite_venta_fraccionaria" => intval($sku["permite_venta_fraccionaria"]),
    "precision_decimal" => intval($sku["precision_decimal"]),
    "incremento_minimo_venta" => $sku["incremento_minimo_venta"],
    "unidad_venta_label" => $sku["unidad_venta_label"]
  );
}
