<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: prevalidar cobro real de una atencion POS multiusuario sin escribir BD.
 * Impacto: revisa atencion, turno/caja asignados, pagos y bloqueos esperados antes de autorizar conversion real.
 * Contrato: solo lectura; no abre turno, no cobra, no convierte atencion, no mueve caja ni inventario.
 */

$idUsuario = 1;
$idAtencion = 0;
$pago = 0;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$detalle = $ventas->atencionDetalleReadOnly(array("id_atencion" => $idAtencion));
$depurarDetalle = valor($detalle, "depurar", array());
$atencion = valor($depurarDetalle, "atencion", array());
$partidas = valor($depurarDetalle, "partidas", array());

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

$items = array();
foreach ($partidas as $partida) {
    $items[] = array(
        "id_sku" => intval(valor($partida, "id_sku", 0)),
        "cantidad" => floatval(valor($partida, "cantidad", 0)),
        "precio_unitario" => floatval(valor($partida, "precio_unitario", 0)),
        "modo_salida" => valor($partida, "modo_salida", "existencia_agregada")
    );
}

$idAlmacen = intval(valor($atencion, "id_almacen", valor($datosAsignacion, "id_almacen", 0)));
$idCaja = intval(valor($datosAsignacion, "id_caja", 0));
$idTurno = intval(valor($turno, "id_turno_caja", 0));
$total = floatval(valor($atencion, "total", 0));
$pago = $pago > 0 ? $pago : $total;

$payload = array(
    "id_usuario" => $idUsuario,
    "id_atencion" => $idAtencion,
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "canal" => "pos",
    "tipo_documento" => "venta",
    "cliente_nombre_publico" => valor($atencion, "cliente_nombre_publico", ""),
    "identificador_cliente" => valor($atencion, "cliente_identificador_publico", ""),
    "items" => json_encode($items),
    "pagos" => json_encode(array(array(
        "id_metodo_pago" => 1,
        "monto" => $pago,
        "referencia" => "UAT-ATENCION-" . $idAtencion
    ))),
    "exigir_pago_completo" => 1
);

$dryRun = empty($detalle["error"]) ? $ventas->confirmarVentaPosDryRun($payload) : null;
$bloqueos = array();
if (!empty($detalle["error"])) {
    $bloqueos[] = "No se pudo consultar detalle de atencion";
}
if ($idTurno <= 0 || $idCaja <= 0) {
    $bloqueos[] = "No hay turno/caja abierta para el usuario";
}
foreach (valor(valor($dryRun, "depurar", array()), "bloqueos", array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_atencion_cobro_preflight_readonly",
    "host" => "panel.com.local",
    "detalle" => $detalle,
    "asignacion" => array(
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno
    ),
    "payload_preview" => array(
        "id_atencion" => $idAtencion,
        "partidas" => count($items),
        "pago" => $pago
    ),
    "dry_run" => $dryRun,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "siguiente_autorizacion_sugerida" => "AUTORIZO EJECUTAR UAT REAL COBRAR ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL id_usuario=1 id_atencion=" . $idAtencion . " pago=" . $pago . " para UAT POS"
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
