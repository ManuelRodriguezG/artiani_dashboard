<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: cargar inventario UAT POS por el modelo oficial InventarioErp con autorizacion explicita.
 * Impacto: escribe existencia y movimiento kardex; no crea ventas ni pagos.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_STOCK_UAT, respaldo, usuario, almacen, SKU y cantidad.
 */

$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 0;
$idSku = 0;
$cantidad = 0;
$ubicacionId = 0;
$lote = "";
$caducidad = "";
$referencia = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
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
    if (strpos($arg, "--ubicacion_id=") === 0) {
        $ubicacionId = intval(trim(substr($arg, 15), "\"' "));
    }
    if (strpos($arg, "--lote=") === 0) {
        $lote = trim(substr($arg, 7), "\"' ");
    }
    if (strpos($arg, "--fecha_caducidad=") === 0) {
        $caducidad = trim(substr($arg, 18), "\"' ");
    }
    if (strpos($arg, "--referencia=") === 0) {
        $referencia = trim(substr($arg, 13), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_STOCK_UAT" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0 || $cantidad <= 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se cargo inventario UAT. Falta autorizacion, respaldo, usuario, almacen, SKU o cantidad.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_STOCK_UAT",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--id_almacen=ID",
            "--id_sku=ID",
            "--cantidad=CANTIDAD"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/InventarioErp.php";

class UatVentasPosStockApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosStockApplyDb())->db();
$referencia = $referencia !== "" ? $referencia : "INV-INICIAL-POS-UAT-" . date("Ymd") . "-A" . $idAlmacen . "-S" . $idSku;

$stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
$stmt->execute(array(":referencia" => $referencia));
if (intval($stmt->fetchColumn()) > 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "La referencia UAT ya fue usada; no se reejecuta para evitar duplicar stock.",
        "referencia" => $referencia
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$item = array(
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "ubicacion_id" => $ubicacionId
);
if ($lote !== "") {
    $item["lote"] = $lote;
}
if ($caducidad !== "") {
    $item["fecha_caducidad"] = $caducidad;
}

$inventario = new InventarioErp();
$respuesta = $inventario->aplicarAjuste(array(
    "id_almacen" => $idAlmacen,
    "tipo_ajuste" => "entrada",
    "documento_operacion" => "inventario_inicial",
    "referencia" => $referencia,
    "observaciones" => "UAT POS stock inicial autorizado para venta real",
    "items" => json_encode(array($item))
), $idUsuario);

if (!empty($respuesta["error"])) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "error_modelo",
        "respuesta" => $respuesta
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

echo json_encode(array(
    "ok" => true,
    "modo" => "stock_uat_cargado",
    "respaldo_ref" => $respaldo,
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "referencia" => $referencia,
    "respuesta" => $respuesta,
    "siguiente_paso" => "Ejecutar preflight de venta POS con este SKU y luego solicitar autorizacion de venta real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false
    );
}
