<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: ejecutar UAT real del endpoint productivo POS para inventario pendiente.
 * Impacto: crea venta, pago/caja, expediente pendiente, evento y notificacion; en mixto descuenta kardex cubierto.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo vigente, confirmacion exacta, motivo, turno abierto y politica activa.
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
$motivo = "";
$confirmacion = "";
$cliente = "Cliente mostrador UAT inventario pendiente";

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
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(str_replace(",", ".", trim(substr($arg, 7), "\"' ")));
    } elseif (strpos($arg, "--id_metodo_pago=") === 0) {
        $idMetodoPago = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if (
    $autorizar !== "VENTAS_POS_INVENTARIO_PENDIENTE_PRODUCTIVO_REAL"
    || !$validacionRespaldo["ok"]
    || strtoupper($confirmacion) !== "AUTORIZAR INVENTARIO PENDIENTE"
    || $motivo === ""
    || $idUsuario <= 0
    || $idAlmacen <= 0
    || $idSku <= 0
    || $cantidad <= 0
    || $pago <= 0
) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail_productivo",
        "mensaje" => "No se ejecuto UAT productiva de inventario pendiente POS. Falta token, respaldo, confirmacion, motivo o datos obligatorios.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_INVENTARIO_PENDIENTE_PRODUCTIVO_REAL",
            "--respaldo=UAT_POS_VIGENTE",
            "--id_usuario=ID",
            "--id_almacen=ID_ALMACEN",
            "--id_sku=ID_SKU",
            "--cantidad=CANTIDAD",
            "--pago=MONTO",
            "--motivo=MOTIVO",
            "--confirmacion=\"AUTORIZAR INVENTARIO PENDIENTE\""
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

$root = realpath(__DIR__ . "/../..");
$controlador = is_file($root . "/app/controladores/Ventas.php") ? file_get_contents($root . "/app/controladores/Ventas.php") : "";
$js = is_file($root . "/public/assets/js/custom/apps/erp/ventas/pos.js") ? file_get_contents($root . "/public/assets/js/custom/apps/erp/ventas/pos.js") : "";
$endpointPreparado = strpos($controlador, "public function pos_inventario_pendiente_cobrar_erp") !== false
    && strpos($controlador, "ventas.pos.inventario_pendiente.autorizar") !== false
    && strpos($controlador, "AUTORIZAR INVENTARIO PENDIENTE") !== false
    && strpos($js, "/ventas/pos_inventario_pendiente_cobrar_erp") !== false;
if (!$endpointPreparado) {
    responder(array(
        "ok" => false,
        "modo" => "endpoint_no_preparado",
        "mensaje" => "El endpoint productivo o la UI no estan preparados.",
        "checks" => array(
            "controlador_endpoint" => strpos($controlador, "public function pos_inventario_pendiente_cobrar_erp") !== false,
            "permiso" => strpos($controlador, "ventas.pos.inventario_pendiente.autorizar") !== false,
            "confirmacion" => strpos($controlador, "AUTORIZAR INVENTARIO PENDIENTE") !== false,
            "ui_endpoint" => strpos($js, "/ventas/pos_inventario_pendiente_cobrar_erp") !== false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$dryRun = $ventas->ventaInventarioPendienteDryRun(array(
    "id_usuario" => $idUsuario,
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
        "siguiente_paso" => "Resolver bloqueos antes de repetir UAT productiva."
    ));
}

$respuesta = $ventas->ventaInventarioPendienteReal(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "pago" => $pago,
    "id_metodo_pago" => $idMetodoPago,
    "motivo" => $motivo,
    "confirmacion" => $confirmacion,
    "cliente" => $cliente,
    "cliente_nombre_publico" => $cliente,
    "referencia_pago" => "UAT-PROD-PINV"
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_inventario_pendiente_productivo_real",
    "respaldo" => $validacionRespaldo,
    "endpoint_productivo_preparado" => $endpointPreparado,
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
    "id_turno_caja" => isset($depurar["id_turno_caja"]) ? $depurar["id_turno_caja"] : null,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar post-venta, expediente pendiente, notificacion, ticket y cerrar turno."
        : "Resolver error antes de repetir UAT productiva."
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
