<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-09.
 * Proposito: aplicar un inventario inicial real/UAT solo con autorizacion explicita y respaldo externo.
 * Impacto: escribe existencias, kardex y posibles unidades fisicas mediante InventarioErp::aplicarAjuste.
 * Contrato: bloqueado por defecto; requiere --autorizar=INV_INICIAL_REAL_UAT y --respaldo legible.
 */

$args = argumentos($argv);
$autorizar = trim(valor($args, "autorizar", ""));
$respaldo = trim(valor($args, "respaldo", ""));
$idUsuario = intval(valor($args, "id_usuario", 1));
$idAlmacen = intval(valor($args, "id_almacen", 0));
$idUbicacion = intval(valor($args, "id_ubicacion", 0));
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

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/InventarioErp.php";

class UatInvInicialRealApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
$bloqueos = array();
if ($autorizar !== "INV_INICIAL_REAL_UAT") {
    $bloqueos[] = "Falta --autorizar=INV_INICIAL_REAL_UAT";
}
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}
if ($idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0 || $referencia === "") {
    $bloqueos[] = "Faltan id_usuario, id_almacen, id_sku o referencia";
}
if (strpos($referencia, "INV-INICIAL-") !== 0) {
    $bloqueos[] = "La referencia debe iniciar con INV-INICIAL-";
}
if (!in_array($modoCaptura, array("base", "unidad_compra", "unidad_fisica_cerrada", "unidad_fisica_abierta"), true)) {
    $bloqueos[] = "Modo de captura no valido";
}

$db = (new UatInvInicialRealApplyDb())->db();
if (!$db) {
    $bloqueos[] = "Conexion MySQL no disponible";
}

if (empty($bloqueos)) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
    $stmt->execute(array(":referencia" => $referencia));
    if (intval($stmt->fetchColumn()) > 0) {
        $bloqueos[] = "La referencia ya existe en Kardex";
    }
}

if (!empty($bloqueos)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "inventario_inicial_real_apply_authorized",
        "mensaje" => "No se aplico inventario inicial real.",
        "bloqueos" => $bloqueos,
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => array("escritura_bloqueada" => true)
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}

$antes = evidencia($db, $idSku, $idAlmacen, $referencia);
$item = array(
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "modo_captura" => $modoCaptura,
    "cantidad_compra" => $cantidadCompra,
    "factor_conversion" => $factorConversion,
    "cantidad_unidades_fisicas" => $unidadesFisicas,
    "contenido_base_original" => $contenidoOriginal,
    "contenido_base_disponible" => $contenidoDisponible,
    "lote" => $lote,
    "fecha_caducidad" => $caducidad,
    "ubicacion_id" => $idUbicacion
);
$payload = array(
    "id_almacen" => $idAlmacen,
    "tipo_ajuste" => "entrada",
    "documento_operacion" => "inventario_inicial",
    "motivo_ajuste" => "inventario_inicial",
    "referencia" => $referencia,
    "observaciones" => "Inventario inicial real/UAT aplicado con respaldo " . basename($respaldo),
    "items" => json_encode(array($item))
);

$modelo = new InventarioErp();
$respuesta = $modelo->aplicarAjuste($payload, $idUsuario);
$despues = evidencia($db, $idSku, $idAlmacen, $referencia);

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "inventario_inicial_real_apply_authorized",
    "referencia" => $referencia,
    "respuesta" => $respuesta,
    "antes" => $antes,
    "despues" => $despues,
    "respaldo_ref" => $respaldo,
    "payload_item" => $item
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($respuesta["error"]) ? 0 : 1);

function evidencia($db, $idSku, $idAlmacen, $referencia) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad),0) cantidad, COALESCE(SUM(cantidad_disponible),0) disponible, COUNT(*) existencias
        FROM erp_inventario_existencias
        WHERE id_sku_erp=:sku AND id_almacen_clave=:almacen");
    $stmt->execute(array(":sku" => $idSku, ":almacen" => $idAlmacen));
    $existencias = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia=:referencia");
    $stmt->execute(array(":referencia" => $referencia));
    $movimientosRef = intval($stmt->fetchColumn());

    $stmt = $db->prepare("SELECT COUNT(*)
        FROM erp_inventario_unidades u
        INNER JOIN erp_inventario_movimientos m ON m.id_movimiento_inventario=u.origen_id
        WHERE u.origen_tipo='inventario_inicial' AND m.referencia=:referencia");
    $stmt->execute(array(":referencia" => $referencia));
    $unidadesRef = intval($stmt->fetchColumn());

    return array(
        "existencias_sku_almacen" => $existencias,
        "movimientos_referencia" => $movimientosRef,
        "unidades_referencia" => $unidadesRef
    );
}

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

function validarRespaldo($respaldo) {
    $respaldo = trim((string)$respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false
    );
}
