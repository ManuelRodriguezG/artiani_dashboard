<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-02.
 * Proposito: validar modulo Pedidos/Apartados POS en modo read-only/dry-run.
 * Impacto: consulta pedidos/apartados y simula reserva/abono sin crear pagos, caja, reservas ni kardex.
 * Contrato: no escribe BD; solo invoca metodos read-only/dry-run de VentasErp.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$folio = "APT-UAT-000001";
$montoAbono = 100;
$idMetodoPago = 1;
$referencia = "UAT-PED-ABONO-DRY";
$tipoDocumento = "apartado";
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$cliente = "Cliente UAT POS";
$identificadorCliente = "3312345678";
$fechaCompromiso = date("Y-m-d", strtotime("+7 days"));
$compacto = false;
$itemsJson = "";
$itemsCli = array();
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--monto_abono=") === 0) {
        $montoAbono = floatval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_metodo_pago=") === 0) {
        $idMetodoPago = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--referencia=") === 0) {
        $referencia = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--tipo_documento=") === 0) {
        $tipoDocumento = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--identificador_cliente=") === 0) {
        $identificadorCliente = trim(substr($arg, 24), "\"' ");
    } elseif (strpos($arg, "--fecha_compromiso=") === 0) {
        $fechaCompromiso = trim(substr($arg, 20), "\"' ");
    } elseif (strpos($arg, "--items_json=") === 0) {
        $itemsJson = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--item=") === 0) {
        $partes = preg_split('/[:,]/', trim(substr($arg, 7), "\"' "));
        if (count($partes) >= 3) {
            $itemsCli[] = array(
                "id_sku" => intval($partes[0]),
                "cantidad" => floatval($partes[1]),
                "precio_unitario" => floatval($partes[2]),
                "modo_salida" => "existencia_agregada"
            );
        }
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$datosAsignacion = !empty($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
$turnoAbierto = !empty($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();

$idCaja = intval(isset($datosAsignacion["id_caja"]) ? $datosAsignacion["id_caja"] : 0);
$idTurno = intval(isset($turnoAbierto["id_turno_caja"]) ? $turnoAbierto["id_turno_caja"] : 0);
if (intval(isset($datosAsignacion["id_almacen"]) ? $datosAsignacion["id_almacen"] : 0) > 0) {
    $idAlmacen = intval($datosAsignacion["id_almacen"]);
}

$items = array();
if ($itemsJson !== "") {
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) {
        $items = array();
    }
} elseif (!empty($itemsCli)) {
    $items = $itemsCli;
} else {
    $items = array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    ));
}

$pedidos = $ventas->listarVentasErp(array(
    "tipo" => "pedido",
    "limite" => 50
));
$apartados = $ventas->listarVentasErp(array(
    "tipo" => "apartado",
    "limite" => 50
));
$reserva = $ventas->pedidoReservaDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "canal" => "pedido_tienda",
    "tipo_documento" => $tipoDocumento,
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $identificadorCliente,
    "fecha_entrega_compromiso" => $fechaCompromiso,
    "items" => $items,
    "pagos" => array(array(
        "id_metodo_pago" => $idMetodoPago,
        "monto" => $montoAbono,
        "referencia" => "UAT-PED-RESERVA-DRY"
    ))
));
$abono = $ventas->apartadoAbonoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "folio" => $folio,
    "monto_abono" => $montoAbono,
    "id_metodo_pago" => $idMetodoPago,
    "referencia" => $referencia
));

$pedidosLista = isset($pedidos["depurar"]["ventas"]) ? $pedidos["depurar"]["ventas"] : array();
$apartadosLista = isset($apartados["depurar"]["ventas"]) ? $apartados["depurar"]["ventas"] : array();
$reservaBloqueos = isset($reserva["depurar"]["bloqueos"]) ? $reserva["depurar"]["bloqueos"] : array();
$abonoBloqueos = isset($abono["depurar"]["bloqueos"]) ? $abono["depurar"]["bloqueos"] : array();

$salida = array(
    "ok" => !$pedidos["error"] && !$apartados["error"] && !$reserva["error"] && !$abono["error"],
    "modo" => "pedidos_apartados_readonly",
    "read_only" => true,
    "id_usuario" => $idUsuario,
    "contexto_pos" => array(
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turnoAbierto)
    ),
    "conteos" => array(
        "pedidos" => count($pedidosLista),
        "apartados" => count($apartadosLista)
    ),
    "pedidos" => $pedidosLista,
    "apartados" => $apartadosLista,
    "reserva_dryrun" => $reserva,
    "abono_dryrun" => $abono,
    "hallazgos" => array_values(array_filter(array(
        empty($pedidosLista) ? "Sin pedidos ERP en muestra UAT" : "",
        empty($apartadosLista) ? "Sin apartados ERP en muestra UAT" : "",
        !empty($reservaBloqueos) ? "Reserva dry-run bloqueada: " . implode("; ", $reservaBloqueos) : "",
        !empty($abonoBloqueos) ? "Abono dry-run bloqueado: " . implode("; ", $abonoBloqueos) : ""
    ))),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_pedido" => true,
        "no_registra_abono" => true,
        "no_mueve_caja" => true,
        "no_reserva_inventario" => true,
        "no_mueve_kardex" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => $salida["ok"],
        "modo" => $salida["modo"],
        "read_only" => true,
        "id_usuario" => $idUsuario,
        "contexto_pos" => $salida["contexto_pos"],
        "conteos" => $salida["conteos"],
        "resumen_reserva" => array(
            "tipo_documento" => isset($reserva["depurar"]["tipo_documento"]) ? $reserva["depurar"]["tipo_documento"] : $tipoDocumento,
            "partidas_enviadas" => count($items),
            "reservas_propuestas" => isset($reserva["depurar"]["propuesta_reserva"]["reservas"]) ? count($reserva["depurar"]["propuesta_reserva"]["reservas"]) : 0,
            "anticipo_minimo" => isset($reserva["depurar"]["anticipo_minimo"]) ? $reserva["depurar"]["anticipo_minimo"] : null,
            "pagado_total" => isset($reserva["depurar"]["pagado_total"]) ? $reserva["depurar"]["pagado_total"] : null,
            "saldo_estimado" => isset($reserva["depurar"]["saldo_estimado"]) ? $reserva["depurar"]["saldo_estimado"] : null,
            "bloqueos" => $reservaBloqueos
        ),
        "resumen_abono" => array(
            "folio" => $folio,
            "monto_abono" => $montoAbono,
            "bloqueos" => $abonoBloqueos
        ),
        "hallazgos" => $salida["hallazgos"],
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
