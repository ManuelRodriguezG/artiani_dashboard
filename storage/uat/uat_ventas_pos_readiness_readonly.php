<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: consolidar readiness UAT del POS sin escribir BD.
 * Impacto: resume turno, ticket, pedidos/apartados y devoluciones fisicas para decidir siguiente autorizacion.
 * Contrato: read-only; no cierra turno, no crea venta, no reserva, no abona y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$montoContado = 795;
$montoAbono = 100;
$folioVenta = "POS-20260701-000001";
$folioApartado = "APT-UAT-000001";
$cliente = "Cliente UAT POS";
$identificadorCliente = "3312345678";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_abono=") === 0) {
        $montoAbono = floatval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--folio_venta=") === 0) {
        $folioVenta = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--folio_apartado=") === 0) {
        $folioApartado = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--identificador_cliente=") === 0) {
        $identificadorCliente = trim(substr($arg, 24), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
$idCaja = intval(valor($datosAsignacion, "id_caja", 0));
$idTurno = intval(valor($turno, "id_turno_caja", 0));
if (intval(valor($datosAsignacion, "id_almacen", 0)) > 0) {
    $idAlmacen = intval(valor($datosAsignacion, "id_almacen", $idAlmacen));
}

$cierre = $idTurno > 0 ? $ventas->cierreTurnoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "monto_contado" => $montoContado
)) : respuestaBloqueada("Sin turno abierto para cierre dry-run");

$ticket = $folioVenta !== "" ? $ventas->ticketVentaFormalReadOnly(array(
    "folio" => $folioVenta
)) : respuestaBloqueada("Sin folio de venta para ticket");

$fechaCompromiso = date("Y-m-d", strtotime("+7 days"));
$reserva = $ventas->pedidoReservaDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "canal" => "pedido_tienda",
    "tipo_documento" => "apartado",
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $identificadorCliente,
    "fecha_entrega_compromiso" => $fechaCompromiso,
    "items" => array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )),
    "pagos" => array(array(
        "id_metodo_pago" => 1,
        "monto" => $montoAbono,
        "referencia" => "READINESS-PED-RESERVA"
    ))
));

$abono = $ventas->apartadoAbonoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "folio" => $folioApartado,
    "monto_abono" => $montoAbono,
    "id_metodo_pago" => 1,
    "referencia" => "READINESS-PED-ABONO"
));

$devoluciones = $ventas->devolucionesInventarioPendientesReadOnly(array(
    "id_almacen" => 0,
    "decision_inventario" => "pendientes",
    "limite" => 50
));

$hallazgos = array();
$cierreBloqueos = valor(valor($cierre, "depurar", array()), "bloqueos", array());
$ticketHallazgos = valor(valor($ticket, "depurar", array()), "hallazgos", array());
$reservaBloqueos = valor(valor($reserva, "depurar", array()), "bloqueos", array());
$abonoBloqueos = valor(valor($abono, "depurar", array()), "bloqueos", array());
$devolucionesResumen = valor(valor($devoluciones, "depurar", array()), "resumen", array());

if (empty($depurarAsignacion["asignacion_activa"])) {
    $hallazgos[] = "Sin asignacion POS activa";
}
if (empty($turno)) {
    $hallazgos[] = "Sin turno abierto";
}
if (!empty($cierreBloqueos)) {
    $hallazgos[] = "Cierre dry-run bloqueado: " . implode("; ", $cierreBloqueos);
}
if (!empty($ticketHallazgos)) {
    $hallazgos[] = "Ticket con hallazgos: " . implode("; ", $ticketHallazgos);
}
if (!empty($reservaBloqueos)) {
    $hallazgos[] = "Reserva dry-run bloqueada: " . implode("; ", $reservaBloqueos);
}
if (!empty($abonoBloqueos)) {
    $hallazgos[] = "Abono dry-run bloqueado: " . implode("; ", $abonoBloqueos);
}
if (intval(valor($devolucionesResumen, "total_registros", 0)) > 0) {
    $hallazgos[] = "Devoluciones fisicas pendientes: " . intval(valor($devolucionesResumen, "total_registros", 0));
}

$salida = array(
    "ok" => true,
    "modo" => "ventas_pos_readiness_readonly",
    "read_only" => true,
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turno)
    ),
    "resumen" => array(
        "cierre_diferencia" => valor(valor($cierre, "depurar", array()), "diferencia", null),
        "ticket_lineas" => count(valor(valor($ticket, "depurar", array()), "ticket_lineas", array())),
        "reserva_bloqueos" => $reservaBloqueos,
        "abono_bloqueos" => $abonoBloqueos,
        "devoluciones_fisicas_pendientes" => intval(valor($devolucionesResumen, "total_registros", 0))
    ),
    "hallazgos" => $hallazgos,
    "siguiente_recomendado" => empty($cierreBloqueos) && !empty($turno)
        ? "Autorizar cierre real del turno o cargar stock para UAT de pedidos/apartados reales."
        : "Resolver bloqueos de turno/asignacion antes de autorizar operaciones reales.",
    "detalle" => array(
        "cierre" => $cierre,
        "ticket" => $ticket,
        "reserva" => $reserva,
        "abono" => $abono,
        "devoluciones" => $devoluciones
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_crea_pedido" => true,
        "no_registra_abono" => true,
        "no_reserva_inventario" => true,
        "no_mueve_kardex" => true
    )
);

if ($compacto) {
    unset($salida["detalle"]);
}

responder($salida);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function respuestaBloqueada($mensaje) {
    return array(
        "error" => false,
        "tipo" => "warning",
        "mensaje" => $mensaje,
        "depurar" => array("bloqueos" => array($mensaje))
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
