<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: generar ticket formal POS desde venta confirmada sin escribir BD.
 * Impacto: valida folio, caja, turno, pagos, precios, garantia snapshot e inventario antes de imprimir/reimprimir.
 * Contrato: read-only; requiere --folio=FOLIO o --id_venta=ID.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idVenta = 0;
$compacto = false;
$esperarSaldoCrm = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    } elseif ($arg === "--esperar_saldo_crm=1") {
        $esperarSaldoCrm = true;
    }
}

if ($folio === "" && $idVenta <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=FOLIO o --id_venta=ID."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->ticketVentaFormalReadOnly(array(
    "folio" => $folio,
    "id_venta" => $idVenta
));

$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$ticketTexto = isset($depurar["ticket_texto"]) ? (string) $depurar["ticket_texto"] : "";
$hallazgos = isset($depurar["hallazgos"]) && is_array($depurar["hallazgos"]) ? $depurar["hallazgos"] : array();
$checks = array(
    "tiene_ticket_pos" => strpos($ticketTexto, "TICKET POS") !== false,
    "tiene_folio" => $folio === "" || strpos($ticketTexto, $folio) !== false,
    "muestra_saldo_cliente_no_caja" => strpos($ticketTexto, "Saldo cliente no caja") !== false
);
if ($esperarSaldoCrm && !$checks["muestra_saldo_cliente_no_caja"]) {
    $hallazgos[] = "El ticket no muestra saldo cliente como pago sin caja.";
}

$salida = array(
    "ok" => empty($respuesta["error"]) && empty($hallazgos),
    "modo" => "ticket_formal_readonly",
    "folio" => $folio,
    "id_venta" => $idVenta,
    "respuesta" => $respuesta,
    "resumen" => array(
        "ticket_lineas" => isset($depurar["ticket_lineas"]) ? count($depurar["ticket_lineas"]) : 0,
        "hallazgos" => $hallazgos,
        "checks" => $checks
    )
);

if ($compacto) {
    $salida = array(
        "ok" => empty($respuesta["error"]) && empty($hallazgos),
        "modo" => "ticket_formal_readonly",
        "folio" => $folio,
        "id_venta" => $idVenta,
        "lineas" => isset($depurar["ticket_lineas"]) ? count($depurar["ticket_lineas"]) : 0,
        "hallazgos" => $hallazgos,
        "checks" => $checks,
        "preview" => implode("\n", array_slice(explode("\n", $ticketTexto), 0, 28)),
        "contrato" => array(
            "read_only" => true,
            "no_escribe_bd" => true,
            "no_recalcula_venta" => true
        )
    );
}

responder($salida);

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
