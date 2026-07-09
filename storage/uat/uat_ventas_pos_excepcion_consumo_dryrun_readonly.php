<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: simular consumo de una excepcion comercial POS autorizada dentro de una venta.
 * Impacto: valida folio, carrito, precio backend, pagos y no reutilizacion antes de una escritura real.
 * Contrato: read-only; no crea venta, no actualiza excepcion, no mueve caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$folio = "EXC-20260628-000001";
$idUsuario = 1;
$idAlmacen = 0;
$idCaja = 0;
$idTurno = 0;
$idSku = 1760;
$cantidad = 1;
$precioEnviado = 285;
$pago = 285;
$identificador = "5550000000";

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_turno_caja=") === 0) {
        $idTurno = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precioEnviado = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpExcepcionConsumoDryRunUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpExcepcionConsumoDryRunUat();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turno = valor($depurarAsignacion, array("turno_abierto"), array());

if ($idAlmacen <= 0) {
    $idAlmacen = intval(valor($datosAsignacion, array("id_almacen"), 0));
}
if ($idCaja <= 0) {
    $idCaja = intval(valor($datosAsignacion, array("id_caja"), 0));
}
if ($idTurno <= 0) {
    $idTurno = intval(valor($turno, array("id_turno_caja"), 0));
}

$items = array(array(
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "precio_unitario" => $precioEnviado,
    "modo_salida" => "existencia_agregada"
));
$pagos = array(array(
    "id_metodo_pago" => 1,
    "monto" => $pago,
    "referencia" => "UAT-EXC-DRYRUN"
));

$respuesta = $ventas->excepcionComercialConsumoDryRun(array(
    "folio_excepcion" => $folio,
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "identificador_cliente" => $identificador,
    "items" => json_encode($items),
    "pagos" => json_encode($pagos),
    "exigir_pago_completo" => 1
));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_excepcion_consumo_dryrun_readonly",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "folio_excepcion" => $folio,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_enviado_pos" => $precioEnviado,
        "pago" => $pago
    ),
    "asignacion_bloqueos" => valor($depurarAsignacion, array("bloqueos"), array()),
    "respuesta" => $respuesta,
    "siguiente_paso" => "Si el dry-run no tiene bloqueos, preparar aplicador autorizado de venta real con consumo de excepcion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valor($origen, $ruta, $default = null) {
    $actual = $origen;
    foreach ($ruta as $clave) {
        if (!is_array($actual) || !array_key_exists($clave, $actual)) {
            return $default;
        }
        $actual = $actual[$clave];
    }
    return $actual;
}
