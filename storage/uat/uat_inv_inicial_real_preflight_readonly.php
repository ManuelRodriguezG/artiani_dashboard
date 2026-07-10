<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-09.
 * Proposito: preparar inventario inicial real multi-tienda sin escribir BD.
 * Impacto: read-only; valida almacen, ubicacion, SKU, lote/caducidad, folio y cantidad base calculada.
 * Contrato: no crea existencias, no crea movimientos y no genera unidades; devuelve payload y autorizacion sugerida.
 */

$args = argumentos($argv);
$idUsuario = intval(valor($args, "id_usuario", 1));
$idAlmacen = intval(valor($args, "id_almacen", 0));
$idUbicacion = intval(valor($args, "id_ubicacion", 0));
$skuCodigo = trim(valor($args, "sku", ""));
$idSku = intval(valor($args, "id_sku", 0));
$modoCaptura = trim(valor($args, "modo", "base"));
$cantidad = floatval(valor($args, "cantidad", 0));
$cantidadCompra = floatval(valor($args, "cantidad_compra", $cantidad > 0 ? $cantidad : 1));
$factorConversion = floatval(valor($args, "factor_conversion", 0));
$unidadesFisicas = intval(valor($args, "unidades_fisicas", $cantidad > 0 ? $cantidad : 1));
$contenidoOriginal = floatval(valor($args, "contenido_original", 0));
$contenidoDisponible = floatval(valor($args, "contenido_disponible", 0));
$lote = trim(valor($args, "lote", ""));
$caducidad = trim(valor($args, "caducidad", ""));
$referencia = strtoupper(trim(valor($args, "referencia", "")));
$respaldo = trim(valor($args, "respaldo", "C:\\xampp\\panel_db_backups\\RESPALDO_PENDIENTE.sql"));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class UatInvInicialRealPreflightDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatInvInicialRealPreflightDb())->db();
$bloqueos = array();
$advertencias = array();
$almacen = array();
$ubicacion = array();
$sku = array();
$existenciaActual = array();
$referenciasExistentes = 0;

if (!$db) {
    $bloqueos[] = "Conexion MySQL no disponible";
}
if ($idAlmacen <= 0) {
    $bloqueos[] = "Indica --id_almacen=ID";
}
if ($skuCodigo === "" && $idSku <= 0) {
    $bloqueos[] = "Indica --sku=CODIGO o --id_sku=ID";
}
if (!in_array($modoCaptura, array("base", "unidad_compra", "unidad_fisica_cerrada", "unidad_fisica_abierta"), true)) {
    $bloqueos[] = "Modo no valido. Usa base, unidad_compra, unidad_fisica_cerrada o unidad_fisica_abierta";
}

if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, tipo_almacen, estatus
        FROM erp_almacenes
        WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo'
        LIMIT 1");
    $stmt->execute(array(":almacen" => $idAlmacen));
    $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$almacen) {
        $bloqueos[] = "Almacen no encontrado o inactivo";
    }
}

if (empty($bloqueos) && $idUbicacion > 0) {
    $stmt = $db->prepare("SELECT id_ubicacion, codigo_ubicacion, nombre
        FROM erp_almacen_ubicaciones
        WHERE id_ubicacion=:ubicacion AND id_almacen_clave=:almacen AND estatus IN ('activo','activa')
        LIMIT 1");
    $stmt->execute(array(":ubicacion" => $idUbicacion, ":almacen" => $idAlmacen));
    $ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ubicacion) {
        $bloqueos[] = "La ubicacion no existe o no pertenece al almacen";
    }
}

if (empty($bloqueos)) {
    $where = $idSku > 0 ? "s.id_sku=:sku" : "s.sku=:sku_codigo";
    $params = $idSku > 0 ? array(":sku" => $idSku) : array(":sku_codigo" => $skuCodigo);
    $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp, s.factor_unidad_base,
            COALESCE(ub.abreviatura, ub.codigo, '') unidad_base_label,
            COALESCE(sp.factor_conversion, s.factor_unidad_base, 1.000000) factor_conversion_compra,
            COALESCE(uc.abreviatura, uc.codigo, '') unidad_compra_label,
            COALESCE(r.requiere_lote, 0) requiere_lote,
            COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
            COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
            COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
        LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1
        LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad=sp.id_unidad_compra
        WHERE {$where} AND s.estatus='activo' AND p.estatus='activo'
        LIMIT 1");
    $stmt->execute($params);
    $sku = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sku) {
        $bloqueos[] = "SKU no encontrado o inactivo";
    }
}

if (empty($bloqueos)) {
    if (intval($sku["requiere_lote"]) === 1 && $lote === "") {
        $bloqueos[] = "El SKU requiere lote";
    }
    if (intval($sku["requiere_caducidad"]) === 1 && $caducidad === "") {
        $bloqueos[] = "El SKU requiere caducidad";
    }
    if ($referencia === "") {
        $referencia = "INV-INICIAL-" . date("Ymd") . "-A" . $idAlmacen . "-S" . intval($sku["id_sku"]) . "-UAT01";
    }
    if (strpos($referencia, "INV-INICIAL-") !== 0) {
        $bloqueos[] = "La referencia debe iniciar con INV-INICIAL-";
    }
}

$cantidadBase = 0;
if (empty($bloqueos)) {
    if ($factorConversion <= 0) {
        $factorConversion = floatval($sku["factor_conversion_compra"]);
    }
    if ($contenidoOriginal <= 0) {
        $contenidoOriginal = $factorConversion > 0 ? $factorConversion : 1;
    }
    if ($contenidoDisponible <= 0) {
        $contenidoDisponible = $contenidoOriginal;
    }
    $cantidadBase = calcularCantidadBase($modoCaptura, $cantidad, $cantidadCompra, $factorConversion, $unidadesFisicas, $contenidoOriginal, $contenidoDisponible);
    if ($cantidadBase <= 0) {
        $bloqueos[] = "La cantidad base calculada debe ser mayor a cero";
    }
    if (intval($sku["permite_venta_fraccionaria"]) !== 1 && abs($cantidadBase - round($cantidadBase)) > 0.0001) {
        $bloqueos[] = "El SKU no permite fraccionarios y la cantidad base calculada no es entera";
    }
    if ($modoCaptura === "unidad_fisica_abierta" && $contenidoDisponible > $contenidoOriginal + 0.000001) {
        $bloqueos[] = "La unidad abierta no puede tener disponible mayor al contenido original";
    }
    if ($modoCaptura === "unidad_compra" && strtolower((string)$sku["unidad_compra_label"]) === strtolower((string)$sku["unidad_base_label"])) {
        $advertencias[] = "La unidad compra preferida del SKU coincide con unidad base; valida Catalogo/Proveedor si esperabas CAJA u otra unidad.";
    }
}

if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
    $stmt->execute(array(":referencia" => $referencia));
    $referenciasExistentes = intval($stmt->fetchColumn());
    if ($referenciasExistentes > 0) {
        $bloqueos[] = "La referencia ya existe en Kardex";
    }
}

if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT codigo_existencia, cantidad, cantidad_disponible, cantidad_apartada, costo_promedio, estatus_existencia
        FROM erp_inventario_existencias
        WHERE id_sku_erp=:sku AND id_almacen_clave=:almacen
          AND lote_clave=:lote AND fecha_caducidad_clave=:caducidad AND ubicacion_clave=:ubicacion
        LIMIT 1");
    $stmt->execute(array(
        ":sku" => intval($sku["id_sku"]),
        ":almacen" => $idAlmacen,
        ":lote" => clave($lote),
        ":caducidad" => $caducidad !== "" ? $caducidad : "1000-01-01",
        ":ubicacion" => $idUbicacion
    ));
    $existenciaActual = $stmt->fetch(PDO::FETCH_ASSOC);
}

$item = empty($bloqueos) ? array(
    "id_sku" => intval($sku["id_sku"]),
    "cantidad" => round($cantidadBase, 4),
    "modo_captura" => $modoCaptura,
    "cantidad_compra" => round($cantidadCompra, 6),
    "factor_conversion" => round($factorConversion, 6),
    "cantidad_unidades_fisicas" => $unidadesFisicas,
    "contenido_base_original" => round($contenidoOriginal, 6),
    "contenido_base_disponible" => round($contenidoDisponible, 6),
    "lote" => $lote,
    "fecha_caducidad" => $caducidad,
    "ubicacion_id" => $idUbicacion
) : array();

$autorizacion = empty($bloqueos)
    ? "AUTORIZO INVENTARIO INICIAL REAL UAT usando respaldo " . $respaldo
        . " con id_usuario=" . $idUsuario
        . " id_almacen=" . $idAlmacen
        . " id_sku=" . intval($sku["id_sku"])
        . " modo=" . $modoCaptura
        . " referencia=" . $referencia
    : "";
$comando = empty($bloqueos)
    ? "C:\\xampp\\php\\php.exe storage\\uat\\uat_inv_inicial_real_apply_authorized.php"
        . " --autorizar=INV_INICIAL_REAL_UAT"
        . " --respaldo=\"" . $respaldo . "\""
        . " --id_usuario=" . $idUsuario
        . " --id_almacen=" . $idAlmacen
        . " --id_ubicacion=" . $idUbicacion
        . " --id_sku=" . intval($sku["id_sku"])
        . " --modo=" . $modoCaptura
        . " --cantidad=" . numero($cantidadBase)
        . " --cantidad_compra=" . numero($cantidadCompra)
        . " --factor_conversion=" . numero($factorConversion)
        . " --unidades_fisicas=" . $unidadesFisicas
        . " --contenido_original=" . numero($contenidoOriginal)
        . " --contenido_disponible=" . numero($contenidoDisponible)
        . " --lote=\"" . $lote . "\""
        . " --caducidad=" . $caducidad
        . " --referencia=" . $referencia
    : "";

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "inventario_inicial_real_preflight_readonly",
    "read_only" => true,
    "almacen" => $almacen,
    "ubicacion" => $ubicacion,
    "sku" => $sku,
    "existencia_actual_mismo_corte" => $existenciaActual,
    "cantidad_base_calculada" => round($cantidadBase, 6),
    "payload_item" => $item,
    "referencia" => $referencia,
    "autorizacion_sugerida" => $autorizacion,
    "comando_aplicador" => $comando,
    "bloqueos" => $bloqueos,
    "advertencias" => $advertencias,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_mueve_kardex" => true,
        "requiere_respaldo_para_aplicar" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

function argumentos($argv) {
    $out = array();
    foreach (isset($argv) ? $argv : array() as $arg) {
        if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
            continue;
        }
        $partes = explode("=", substr($arg, 2), 2);
        $out[$partes[0]] = trim($partes[1], "\"' ");
    }
    return $out;
}

function valor($array, $key, $default = "") {
    return array_key_exists($key, $array) ? $array[$key] : $default;
}

function calcularCantidadBase($modo, $cantidad, $cantidadCompra, $factor, $unidades, $original, $disponible) {
    if ($modo === "unidad_compra") {
        return round(floatval($cantidadCompra) * floatval($factor), 4);
    }
    if ($modo === "unidad_fisica_cerrada") {
        return round(intval($unidades) * floatval($original), 4);
    }
    if ($modo === "unidad_fisica_abierta") {
        return round(floatval($disponible), 4);
    }
    return round(floatval($cantidad), 4);
}

function clave($valor) {
    $valor = strtoupper(trim((string)$valor));
    return preg_replace('/[^A-Z0-9_-]+/', '-', $valor);
}

function numero($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
}
