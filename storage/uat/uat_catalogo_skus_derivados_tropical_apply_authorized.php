<?php
/**
 * IA: Codex GPT-5 | Fecha: 2026-10-11
 * Proposito: ajustar SKUs derivados para alimentos Tropical siguiendo el patron cerrado -> granel -> presentaciones.
 * Impacto: Catalogo ERP; crea/actualiza SKUs, codigos internos, reglas, aperturas y presentaciones usando CatalogoErpDatos.
 * Contrato: requiere token CATALOGO_SKUS_DERIVADOS_TROPICAL; no toca inventario, ventas, precios ni costos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpDatos.php";

const TOKEN_AUTORIZADO = "CATALOGO_SKUS_DERIVADOS_TROPICAL";
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
  "TP-40311" => array(
    "producto_codigo" => "ECOM-1444",
    "sku_cerrado" => "TP-40311",
    "nombre_cerrado" => "Alimento tropical pond sticks mixed 4 kg",
    "kg_por_empaque" => 4,
    "nombre_granel" => "Alimento tropical pond sticks mixed 1 kg GRANEL",
    "nombre_presentacion_base" => "Alimento tropical pond sticks mixed"
  ),
  "TP-76700" => array(
    "producto_codigo" => "TP-76700",
    "sku_cerrado" => "TP-76700",
    "nombre_cerrado" => "ALIMENTO TROPICAL FLAKES MIX 5KG.",
    "kg_por_empaque" => 5,
    "nombre_granel" => "ALIMENTO TROPICAL FLAKES MIX 1KG GRANEL",
    "nombre_presentacion_base" => "ALIMENTO TROPICAL FLAKES MIX"
  )
);

try {
  if ($modo === "backup") {
    salida(respaldo($db, $plan));
  }
  if ($modo === "preflight") {
    salida(preflight($db, $plan));
  }
  if ($modo === "apply") {
    salida(aplicar($db, $modelo, $plan));
  }
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
  $rows = $db->query("SELECT id_unidad, codigo FROM erp_catalogo_unidades WHERE codigo IN ('PZA','KG')")->fetchAll(PDO::FETCH_ASSOC);
  $mapa = array();
  foreach ($rows as $row) {
    $mapa[$row["codigo"]] = intval($row["id_unidad"]);
  }
  foreach (array("PZA", "KG") as $codigo) {
    if (empty($mapa[$codigo])) {
      throw new Exception("Falta unidad activa requerida: " . $codigo);
    }
  }
  return $mapa;
}

function productoPorCodigo($db, $codigo) {
  $stmt = $db->prepare("SELECT * FROM erp_catalogo_productos WHERE codigo_producto=:codigo LIMIT 1");
  $stmt->execute(array(":codigo" => $codigo));
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$row) {
    throw new Exception("No se encontro producto " . $codigo);
  }
  return $row;
}

function skuPorCodigo($db, $sku) {
  $stmt = $db->prepare("SELECT s.*, p.codigo_producto, p.nombre producto_nombre
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    WHERE s.sku=:sku LIMIT 1");
  $stmt->execute(array(":sku" => $sku));
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function detalleSku($db, $sku) {
  $stmt = $db->prepare("SELECT s.*, p.codigo_producto, p.nombre producto_nombre,
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

function datosSku($base, $overrides) {
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
  foreach ($overrides as $key => $value) {
    $datos[$key] = $value;
  }
  return $datos;
}

function guardarSku($db, $modelo, $sku, $datos, $idUsuario) {
  $existente = skuPorCodigo($db, $sku);
  if ($existente) {
    $datos["id_sku"] = intval($existente["id_sku"]);
    if (!isset($datos["id_producto_erp"])) {
      $datos["id_producto_erp"] = intval($existente["id_producto_erp"]);
    }
    if (!array_key_exists("codigo_barras", $datos) || $datos["codigo_barras"] === "") {
      $datos["codigo_barras"] = codigoPrincipal($db, intval($existente["id_sku"]));
    }
    $respuesta = $modelo->actualizarSku($datos, $idUsuario);
  } else {
    $respuesta = $modelo->agregarSku($datos, $idUsuario);
  }
  if (!empty($respuesta["error"])) {
    throw new Exception("No se pudo guardar SKU " . $sku . ": " . $respuesta["mensaje"]);
  }
  $actual = skuPorCodigo($db, $sku);
  if (!$actual) {
    throw new Exception("SKU " . $sku . " no quedo disponible despues de guardar");
  }
  return intval($actual["id_sku"]);
}

function codigoPrincipal($db, $idSku) {
  $stmt = $db->prepare("SELECT codigo FROM erp_catalogo_sku_codigos WHERE id_sku=:sku AND es_principal=1 AND estatus='activo' ORDER BY id_sku_codigo DESC LIMIT 1");
  $stmt->execute(array(":sku" => intval($idSku)));
  return (string) $stmt->fetchColumn();
}

function aplicar($db, $modelo, $plan) {
  $u = unidades($db);
  $acciones = array();
  $idUsuario = 0;
  foreach ($plan as $codigo => $cfg) {
    productoPorCodigo($db, $cfg["producto_codigo"]);
    $base = detalleSku($db, $cfg["sku_cerrado"]);
    if (!$base) {
      throw new Exception("No existe SKU cerrado " . $cfg["sku_cerrado"]);
    }

    $idCerrado = guardarSku($db, $modelo, $cfg["sku_cerrado"], datosSku($base, array(
      "sku" => $cfg["sku_cerrado"],
      "nombre_sku" => $cfg["nombre_cerrado"],
      "id_unidad_base" => $u["PZA"],
      "factor_unidad_base" => "1",
      "estatus" => "activo",
      "permite_venta_fraccionaria" => 0,
      "precision_decimal" => 0,
      "incremento_minimo_venta" => "1",
      "unidad_venta_label" => "",
      "permite_etiqueta_fraccionada" => 0,
      "generar_etiqueta_interna" => 0,
      "requiere_unidades_fisicas_recepcion" => 0,
      "requiere_cantidad_variable_recepcion" => 0
    )), $idUsuario);
    $acciones[] = "cerrado_actualizado:" . $cfg["sku_cerrado"];

    $baseActualizada = detalleSku($db, $cfg["sku_cerrado"]);
    $skuGranel = $cfg["sku_cerrado"] . "-GRANEL";
    $idGranel = guardarSku($db, $modelo, $skuGranel, datosSku($baseActualizada, array(
      "sku" => $skuGranel,
      "nombre_sku" => $cfg["nombre_granel"],
      "id_unidad_base" => $u["KG"],
      "factor_unidad_base" => "1",
      "codigo_barras" => "INT-" . $skuGranel,
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
    $acciones[] = "granel_guardado:" . $skuGranel;

    $apertura = $modelo->guardarSkuAperturaEmpaque(array(
      "id_sku_origen" => $idCerrado,
      "id_sku_destino" => $idGranel,
      "factor_conversion" => $cfg["kg_por_empaque"],
      "requiere_unidad_fisica" => 0,
      "conserva_lote" => 1,
      "conserva_caducidad" => 1,
      "permite_merma" => 0,
      "merma_porcentaje_default" => 0,
      "instrucciones_operativas" => "",
      "estatus" => "activo"
    ), $idUsuario);
    if (!empty($apertura["error"])) {
      throw new Exception("No se pudo guardar apertura " . $cfg["sku_cerrado"] . ": " . $apertura["mensaje"]);
    }
    $acciones[] = "apertura_guardada:" . $cfg["sku_cerrado"] . "->" . $skuGranel;

    foreach (array(25 => "25GR", 50 => "50GR", 100 => "100GR", 500 => "500GR") as $gramos => $sufijo) {
      $skuPresentacion = $cfg["sku_cerrado"] . "-" . $sufijo;
      $idPresentacion = guardarSku($db, $modelo, $skuPresentacion, datosSku($baseActualizada, array(
        "sku" => $skuPresentacion,
        "nombre_sku" => $cfg["nombre_presentacion_base"] . " " . $gramos . " gr",
        "id_unidad_base" => $u["PZA"],
        "factor_unidad_base" => "1",
        "codigo_barras" => "INT-" . $skuPresentacion,
        "estatus" => "activo",
        "permite_venta_fraccionaria" => 0,
        "precision_decimal" => 0,
        "incremento_minimo_venta" => "1",
        "unidad_venta_label" => "",
        "permite_etiqueta_fraccionada" => 0,
        "generar_etiqueta_interna" => 0,
        "requiere_unidades_fisicas_recepcion" => 0,
        "requiere_cantidad_variable_recepcion" => 0
      )), $idUsuario);
      $factorKg = number_format($gramos / 1000, 6, ".", "");
      $presentacion = $modelo->guardarSkuPresentacion(array(
        "id_sku_base" => $idGranel,
        "id_sku_presentacion" => $idPresentacion,
        "factor_salida_base" => $factorKg,
        "modo_disponibilidad" => "preparada",
        "consume_stock_base_en" => "preparacion",
        "requiere_empaque" => 1,
        "capacidad_diaria" => "",
        "merma_porcentaje" => 0,
        "estatus" => "activa"
      ), $idUsuario);
      if (!empty($presentacion["error"])) {
        throw new Exception("No se pudo guardar presentacion " . $skuPresentacion . ": " . $presentacion["mensaje"]);
      }
      $acciones[] = "presentacion_guardada:" . $skuPresentacion . ":" . $factorKg;
    }
  }
  return array("error" => false, "mensaje" => "Ajuste aplicado", "acciones" => $acciones, "postcheck" => postcheck($db, $modelo, $plan));
}

function respaldo($db, $plan) {
  if (!is_dir(BACKUP_DIR) && !mkdir(BACKUP_DIR, 0777, true)) {
    throw new Exception("No se pudo crear directorio de respaldo: " . BACKUP_DIR);
  }
  $skus = array();
  foreach ($plan as $cfg) {
    $skus[] = $cfg["sku_cerrado"];
    $skus[] = $cfg["sku_cerrado"] . "-GRANEL";
    foreach (array("25GR", "50GR", "100GR", "500GR", "25G", "50G", "100G") as $sufijo) {
      $skus[] = $cfg["sku_cerrado"] . "-" . $sufijo;
    }
  }
  $payload = array(
    "fecha" => date("Y-m-d H:i:s"),
    "token" => TOKEN_AUTORIZADO,
    "base" => MYSQLBASE,
    "tablas" => array(
      "erp_catalogo_productos" => seleccionarPorSkus($db, "SELECT DISTINCT p.* FROM erp_catalogo_productos p INNER JOIN erp_catalogo_skus s ON s.id_producto_erp=p.id_producto_erp WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_skus" => seleccionarPorSkus($db, "SELECT * FROM erp_catalogo_skus WHERE sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_reglas_inventario" => seleccionarPorSkus($db, "SELECT r.* FROM erp_catalogo_sku_reglas_inventario r INNER JOIN erp_catalogo_skus s ON s.id_sku=r.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_codigos" => seleccionarPorSkus($db, "SELECT c.* FROM erp_catalogo_sku_codigos c INNER JOIN erp_catalogo_skus s ON s.id_sku=c.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_impuestos" => seleccionarPorSkus($db, "SELECT i.* FROM erp_catalogo_sku_impuestos i INNER JOIN erp_catalogo_skus s ON s.id_sku=i.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_proveedores" => seleccionarPorSkus($db, "SELECT sp.* FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE s.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_presentaciones" => seleccionarPorSkus($db, "SELECT pr.* FROM erp_catalogo_sku_presentaciones pr INNER JOIN erp_catalogo_skus b ON b.id_sku=pr.id_sku_base INNER JOIN erp_catalogo_skus p ON p.id_sku=pr.id_sku_presentacion WHERE b.sku IN (__SKUS__) OR p.sku IN (__SKUS__)", $skus),
      "erp_catalogo_sku_aperturas_empaque" => seleccionarPorSkus($db, "SELECT ae.* FROM erp_catalogo_sku_aperturas_empaque ae INNER JOIN erp_catalogo_skus o ON o.id_sku=ae.id_sku_origen INNER JOIN erp_catalogo_skus d ON d.id_sku=ae.id_sku_destino WHERE o.sku IN (__SKUS__) OR d.sku IN (__SKUS__)", $skus),
      "erp_notificaciones" => seleccionarPorSkus($db, "SELECT n.* FROM erp_notificaciones n INNER JOIN erp_catalogo_skus s ON s.id_sku=n.id_entidad_origen WHERE n.entidad_origen='erp_catalogo_skus' AND s.sku IN (__SKUS__)", $skus)
    )
  );
  $archivo = BACKUP_DIR . "\\catalogo_skus_derivados_tropical_" . date("Ymd_His") . "_pre.json";
  if (file_put_contents($archivo, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
    throw new Exception("No se pudo escribir respaldo externo");
  }
  return array("error" => false, "mensaje" => "Respaldo externo generado", "archivo" => $archivo, "resumen" => array_map("count", $payload["tablas"]));
}

function seleccionarPorSkus($db, $sql, $skus) {
  $skus = array_values(array_unique($skus));
  $placeholders = array();
  $params = array();
  foreach ($skus as $i => $sku) {
    $ph = ":sku" . $i;
    $placeholders[] = $ph;
    $params[$ph] = $sku;
  }
  $sql = str_replace("__SKUS__", implode(",", $placeholders), $sql);
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function preflight($db, $plan) {
  $resultado = array("error" => false, "mensaje" => "Preflight read-only", "productos" => array());
  foreach ($plan as $codigo => $cfg) {
    $producto = productoPorCodigo($db, $cfg["producto_codigo"]);
    $cerrado = detalleSku($db, $cfg["sku_cerrado"]);
    $resultado["productos"][$codigo] = array(
      "producto" => array("id_producto_erp" => intval($producto["id_producto_erp"]), "codigo_producto" => $producto["codigo_producto"], "nombre" => $producto["nombre"]),
      "cerrado_existe" => $cerrado ? 1 : 0,
      "granel_existe" => skuPorCodigo($db, $cfg["sku_cerrado"] . "-GRANEL") ? 1 : 0,
      "presentaciones_objetivo" => array($cfg["sku_cerrado"] . "-25GR", $cfg["sku_cerrado"] . "-50GR", $cfg["sku_cerrado"] . "-100GR", $cfg["sku_cerrado"] . "-500GR"),
      "kg_por_empaque" => $cfg["kg_por_empaque"]
    );
  }
  return $resultado;
}

function postcheck($db, $modelo, $plan) {
  $out = array("error" => false, "mensaje" => "Postcheck", "productos" => array());
  foreach ($plan as $codigo => $cfg) {
    $prefijo = $cfg["sku_cerrado"] . "%";
    $stmt = $db->prepare("SELECT s.sku, s.nombre, u.codigo unidad, s.factor_unidad_base, s.estatus,
        COALESCE(r.permite_venta_fraccionaria,0) venta_fraccionaria,
        COALESCE(r.precision_decimal,0) precision_decimal,
        COALESCE(r.incremento_minimo_venta,1) incremento_minimo
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE s.sku LIKE :prefijo
      ORDER BY s.sku");
    $stmt->execute(array(":prefijo" => $prefijo));
    $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT b.sku base, p.sku presentacion, pr.factor_salida_base, pr.estatus
      FROM erp_catalogo_sku_presentaciones pr
      INNER JOIN erp_catalogo_skus b ON b.id_sku=pr.id_sku_base
      INNER JOIN erp_catalogo_skus p ON p.id_sku=pr.id_sku_presentacion
      WHERE b.sku LIKE :prefijo OR p.sku LIKE :prefijo
      ORDER BY b.sku, pr.factor_salida_base");
    $stmt->execute(array(":prefijo" => $prefijo));
    $presentaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT o.sku origen, d.sku destino, ae.factor_conversion, ae.estatus
      FROM erp_catalogo_sku_aperturas_empaque ae
      INNER JOIN erp_catalogo_skus o ON o.id_sku=ae.id_sku_origen
      INNER JOIN erp_catalogo_skus d ON d.id_sku=ae.id_sku_destino
      WHERE o.sku LIKE :prefijo OR d.sku LIKE :prefijo
      ORDER BY o.sku, d.sku");
    $stmt->execute(array(":prefijo" => $prefijo));
    $aperturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contextos = array();
    foreach (array($cfg["sku_cerrado"] . "-GRANEL", $cfg["sku_cerrado"] . "-25GR", $cfg["sku_cerrado"] . "-50GR", $cfg["sku_cerrado"] . "-100GR", $cfg["sku_cerrado"] . "-500GR") as $sku) {
      $row = skuPorCodigo($db, $sku);
      if ($row) {
        $ctx = $modelo->resolverContextoSkuVendible(intval($row["id_sku"]));
        $contextos[$sku] = empty($ctx["error"]) ? array(
          "tipo_derivacion_rentabilidad" => $ctx["depurar"]["contexto"]["tipo_derivacion_rentabilidad"],
          "requiere_costo" => $ctx["depurar"]["contexto"]["requiere_costo"],
          "costo_derivable" => $ctx["depurar"]["contexto"]["costo_derivable"],
          "advertencias_configuracion" => $ctx["depurar"]["contexto"]["advertencias_configuracion"]
        ) : array("error" => $ctx["mensaje"]);
      }
    }
    $out["productos"][$codigo] = array(
      "skus" => $skus,
      "aperturas" => $aperturas,
      "presentaciones" => $presentaciones,
      "contextos_rentabilidad" => $contextos
    );
  }
  return $out;
}
