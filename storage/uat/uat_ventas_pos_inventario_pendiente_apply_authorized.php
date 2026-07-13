<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: ejecutar UAT real de venta POS con inventario pendiente, incluido flujo mixto con kardex parcial.
 * Impacto: crea venta, pago, caja, kardex cubierto, expediente pendiente, evento y notificacion operativa.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo/referencia vigente, usuario, turno abierto, pago completo y politica activa.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 0;
$idSku = 0;
$cantidad = 0;
$pago = 0;
$idMetodoPago = 1;
$motivo = "UAT venta POS con inventario pendiente";
$cliente = "Cliente mostrador UAT";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--id_metodo_pago=") === 0) {
        $idMetodoPago = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_INVENTARIO_PENDIENTE_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0 || $cantidad <= 0 || $pago <= 0) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto venta POS con inventario pendiente. Falta autorizacion, respaldo, usuario, almacen, SKU, cantidad o pago valido.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_INVENTARIO_PENDIENTE_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE_O_REFERENCIA_VIGENTE",
            "--id_usuario=ID",
            "--id_almacen=ID_ALMACEN",
            "--id_sku=ID_SKU",
            "--cantidad=CANTIDAD",
            "--pago=MONTO_TOTAL"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$dryRun = $ventas->ventaInventarioPendienteDryRun(array(
    "id_sku" => $idSku,
    "id_almacen" => $idAlmacen,
    "cantidad" => $cantidad,
    "canal" => "pos",
    "motivo" => $motivo
));
$depurarDry = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
if (!empty($dryRun["error"]) || (isset($depurarDry["estado"]) && $depurarDry["estado"] !== "pendiente_autorizable") || !empty($depurarDry["bloqueos"])) {
    responder(array(
        "ok" => false,
        "modo" => "prevalidacion_bloqueada",
        "respuesta" => $dryRun,
        "siguiente_paso" => "Resolver bloqueos antes de ejecutar UAT real."
    ));
}

$respuesta = $ventas->ventaInventarioPendienteReal(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "pago" => $pago,
    "id_metodo_pago" => $idMetodoPago,
    "motivo" => $motivo,
    "cliente" => $cliente
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "venta_pos_inventario_pendiente_uat_real",
    "respaldo" => $validacionRespaldo,
    "dry_run_previo" => array(
        "estado" => isset($depurarDry["estado"]) ? $depurarDry["estado"] : null,
        "cantidad_cubierta" => isset($depurarDry["cantidad_cubierta"]) ? $depurarDry["cantidad_cubierta"] : null,
        "cantidad_pendiente" => isset($depurarDry["cantidad_pendiente"]) ? $depurarDry["cantidad_pendiente"] : null,
        "flujo_mixto" => !empty($depurarDry["flujo_mixto"])
    ),
    "respuesta" => $respuesta,
    "folio" => isset($depurar["folio"]) ? $depurar["folio"] : null,
    "folio_pendiente" => isset($depurar["folio_pendiente"]) ? $depurar["folio_pendiente"] : null,
    "id_venta" => isset($depurar["id_venta"]) ? $depurar["id_venta"] : null,
    "id_inventario_pendiente" => isset($depurar["id_inventario_pendiente"]) ? $depurar["id_inventario_pendiente"] : null,
    "siguiente_paso" => empty($respuesta["error"]) ? "Validar post-venta, kardex cubierto, expediente pendiente, notificacion e Inventario/Existencias." : "Resolver error antes de repetir UAT."
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? is_file($respaldo) : false;
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || $existe),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
