<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: simular persistencia de una cuenta POS como atencion compartida.
 * Impacto: valida contrato de atenciones multiusuario sin crear registros ni reservar inventario.
 * Contrato: read-only; no inserta, no reserva y no descuenta.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$idSku = 0;
$cantidad = 1;
$precio = 0;
$cliente = "Cliente atencion UAT";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

if ($idUsuario <= 0 || $idSku <= 0 || $cantidad <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta --id_usuario, --id_sku o cantidad valida."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

$datos = array(
    "id_usuario" => $idUsuario,
    "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "cliente_nombre_publico" => $cliente,
    "origen" => "pos_uat",
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )))
);

$resultado = $ventas->atencionPersistenteDryRun($datos);
responder(array(
    "ok" => empty($resultado["error"]) && empty(valor(valor($resultado, "depurar", array()), "bloqueos", array())),
    "modo" => "atencion_persistente_dryrun_readonly",
    "id_usuario" => $idUsuario,
    "resultado" => $resultado
));

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
