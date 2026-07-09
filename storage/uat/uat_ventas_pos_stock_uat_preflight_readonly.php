<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: preparar parametros seguros para cargar stock UAT POS por InventarioErp.
 * Impacto: solo lectura; no crea existencias ni movimientos.
 * Contrato: devuelve SKU, ubicacion y payload recomendado para autorizacion posterior.
 */

$idAlmacen = 0;
$idSku = 0;
$cantidad = 1;
$idUsuario = 1;
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$referenciaSolicitada = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    }
    if (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--referencia=") === 0) {
        $referenciaSolicitada = trim(substr($arg, 13), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosStockPreflightDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosStockPreflightDb())->db();
$bloqueos = array();
if (!$db) {
    $bloqueos[] = "Conexion MySQL no disponible";
}
if ($idAlmacen <= 0) {
    $bloqueos[] = "Indica --id_almacen=ID";
}
if ($cantidad <= 0) {
    $bloqueos[] = "La cantidad debe ser mayor a cero";
}

$almacen = array();
$ubicacion = array();
$sku = array();
if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT id_almacen, codigo_almacen, almacen, nombre_comercial, estatus
        FROM erp_almacenes
        WHERE id_almacen=:almacen AND COALESCE(estatus,'activo')='activo'
        LIMIT 1");
    $stmt->execute(array(":almacen" => $idAlmacen));
    $almacen = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$almacen) {
        $bloqueos[] = "Almacen no encontrado o inactivo";
    }
}
if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT id_ubicacion, codigo_ubicacion, nombre
        FROM erp_almacen_ubicaciones
        WHERE id_almacen_clave=:almacen AND estatus IN ('activo','activa')
        ORDER BY id_ubicacion
        LIMIT 1");
    $stmt->execute(array(":almacen" => $idAlmacen));
    $ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (empty($bloqueos)) {
    $params = array();
    $whereSku = "";
    if ($idSku > 0) {
        $whereSku = "AND s.id_sku=:sku";
        $params[":sku"] = $idSku;
    }
    $sql = "SELECT s.id_sku, s.sku, s.nombre, s.tipo_inventario, s.costo_referencia,
            p.nombre producto,
            COALESCE(r.controla_inventario, 1) controla_inventario,
            COALESCE(r.requiere_lote, 0) requiere_lote,
            COALESCE(r.requiere_caducidad, 0) requiere_caducidad,
            COALESCE(r.generar_etiqueta_interna, 0) generar_etiqueta_interna,
            COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
            COALESCE(pr.precio, 0) precio
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
        LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
            AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
        WHERE s.estatus='activo' AND p.estatus='activo'
          AND COALESCE(r.controla_inventario, 1)=1
          AND s.tipo_inventario NOT IN ('servicio','cargo')
          AND COALESCE(pr.precio, 0) > 0
          $whereSku
        ORDER BY CASE WHEN COALESCE(r.generar_etiqueta_interna,0)=0 THEN 0 ELSE 1 END,
                 s.id_sku DESC
        LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $sku = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sku) {
        $bloqueos[] = $idSku > 0
            ? "SKU solicitado no esta activo, no controla inventario o no tiene precio general"
            : "No se encontro SKU activo con precio general para UAT POS";
    }
}

$requiereLote = !empty($sku) && intval($sku["requiere_lote"]) === 1;
$requiereCaducidad = !empty($sku) && intval($sku["requiere_caducidad"]) === 1;
$lote = "POS-UAT-" . date("Ymd");
$caducidad = date("Y-m-d", strtotime("+1 year"));
$payload = empty($bloqueos) ? array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => intval($sku["id_sku"]),
    "sku" => $sku["sku"],
    "cantidad" => $cantidad,
    "ubicacion_id" => !empty($ubicacion["id_ubicacion"]) ? intval($ubicacion["id_ubicacion"]) : 0,
    "lote" => $requiereLote ? $lote : "",
    "fecha_caducidad" => $requiereCaducidad ? $caducidad : "",
    "referencia" => $referenciaSolicitada !== "" ? $referenciaSolicitada : "INV-INICIAL-POS-UAT-" . date("Ymd") . "-A" . $idAlmacen . "-S" . intval($sku["id_sku"])
) : array();
$autorizacion = empty($bloqueos) ? (
    "AUTORIZO CARGAR STOCK UAT POS usando respaldo " . $respaldo
    . " con id_usuario=" . $idUsuario
    . " id_almacen=" . $idAlmacen
    . " id_sku=" . intval($sku["id_sku"])
    . " cantidad=" . rtrim(rtrim(number_format($cantidad, 6, ".", ""), "0"), ".")
    . " referencia=" . $payload["referencia"]
) : "";
$autorizacionHumana = $autorizacion !== "" ? str_replace($respaldo, etiquetaRespaldoHumana($respaldo), $autorizacion) : "";
$comando = empty($bloqueos) ? (
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_stock_uat_apply_authorized.php"
    . " --autorizar=VENTAS_POS_STOCK_UAT"
    . " --respaldo=" . $respaldo
    . " --id_usuario=" . $idUsuario
    . " --id_almacen=" . $idAlmacen
    . " --id_sku=" . intval($sku["id_sku"])
    . " --cantidad=" . rtrim(rtrim(number_format($cantidad, 6, ".", ""), "0"), ".")
    . " --referencia=" . $payload["referencia"]
) : "";

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "read_only" => true,
    "id_almacen" => $idAlmacen,
    "id_usuario" => $idUsuario,
    "almacen" => $almacen,
    "ubicacion" => $ubicacion,
    "sku" => $sku,
    "payload_recomendado" => $payload,
    "autorizacion_sugerida" => $autorizacionHumana,
    "comando_aplicador" => $comando,
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_carga_stock" => true,
        "no_mueve_kardex" => true
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Solicitar autorizacion VENTAS_POS_STOCK_UAT para cargar inventario inicial UAT por kardex."
        : "Resolver bloqueos antes de preparar venta POS real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function etiquetaRespaldoHumana($respaldo) {
    return basename((string) $respaldo) === "artianilocal_respaldo_completo_20260625_post_repair.sql"
        ? "UAT POS vigente"
        : $respaldo;
}
