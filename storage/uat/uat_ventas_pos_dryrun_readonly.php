<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: ejecutar UAT read-only del arranque Ventas/POS/Pedidos.
 * Impacto: valida diagnostico, cajas, turnos, ticket, pedido/reserva y devolucion sin escribir BD.
 * Contrato: no ejecuta DDL, no crea ventas, no registra pagos, no reserva y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$alcance = "base";
foreach ($args as $arg) {
    if (strpos($arg, "--alcance=") === 0) {
        $valorAlcance = strtolower(trim(substr($arg, 10), "\"' "));
        $alcance = $valorAlcance === "expandido" ? "expandido" : "base";
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$itemsVenta = json_encode(array(array(
    "id_sku" => 1113,
    "cantidad" => 1,
    "precio_unitario" => 10,
    "modo_salida" => "existencia_agregada"
)));
$pagosVenta = json_encode(array(array(
    "id_metodo_pago" => 1,
    "monto" => 10,
    "referencia" => ""
)));

$diagnostico = $ventas->diagnosticoModuloVentas();
$catalogos = $ventas->catalogosPos();
$planCajas = $ventas->planCajasInicialesPos();
$planTerminales = $ventas->planAsignacionTerminalPos(array(
    "id_usuario" => 0,
    "usuario_nombre" => "Usuario POS por asignar"
));
$asignacionActual = $ventas->asignacionActualTerminalPos(array("id_usuario" => 0));
$prevalidacion = $ventas->prevalidarCarritoPos(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "items" => $itemsVenta,
    "pagos" => $pagosVenta
));
$confirmacion = $ventas->confirmarVentaPosDryRun(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "items" => $itemsVenta,
    "pagos" => $pagosVenta
));
$pedido = $ventas->pedidoReservaDryRun(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "tipo_documento" => "pedido",
    "cliente_nombre_publico" => "",
    "fecha_entrega_compromiso" => "",
    "items" => $itemsVenta
));
$apertura = $ventas->aperturaTurnoDryRun(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "monto_inicial" => 500
));
$cierre = $ventas->cierreTurnoDryRun(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "monto_esperado" => 1000,
    "monto_contado" => 950
));
$ticket = $ventas->ticketPreviewDryRun(array(
    "id_almacen" => 4,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "items" => $itemsVenta,
    "pagos" => $pagosVenta
));
$devolucion = $ventas->devolucionDryRun(array(
    "tipo" => "devolucion",
    "folio" => "",
    "motivo" => "",
    "decision_inventario" => "cuarentena",
    "items" => json_encode(array())
));
$auditoriaEsquema = $esquema->auditarVentasPos($alcance);
$busquedaImagenes = $ventas->buscarSkusPos(array("q" => "TP-40372", "id_almacen" => 3, "limite" => 10));

$resumen = array(
    "ok" => !$diagnostico["error"]
        && !$catalogos["error"]
        && !$planCajas["error"]
        && !$planTerminales["error"]
        && !$asignacionActual["error"]
        && !$prevalidacion["error"]
        && !$confirmacion["error"]
        && !$pedido["error"]
        && !$apertura["error"]
        && !$cierre["error"]
        && !$ticket["error"]
        && !$devolucion["error"]
        && !$auditoriaEsquema["error"],
    "modo" => "read-only",
    "alcance" => $alcance,
    "diagnostico" => array(
        "almacenes_pos" => count(valor($diagnostico, array("depurar", "almacenes_pos"), array())),
        "hallazgos" => ids(valor($diagnostico, array("depurar", "hallazgos"), array()))
    ),
    "catalogos" => array(
        "almacenes" => count(valor($catalogos, array("depurar", "almacenes"), array())),
        "cajas" => count(valor($catalogos, array("depurar", "cajas"), array())),
        "turnos_abiertos" => count(valor($catalogos, array("depurar", "turnos_abiertos"), array())),
        "schema_cajas_pendiente" => valor($catalogos, array("depurar", "schema_cajas_pendiente"), null),
        "schema_turnos_pendiente" => valor($catalogos, array("depurar", "schema_turnos_pendiente"), null)
    ),
    "plan_cajas" => array_map(function ($item) {
        return array(
            "codigo_almacen" => $item["codigo_almacen"],
            "caja" => $item["caja_sugerida"]["codigo"],
            "crear" => $item["crear"]
        );
    }, valor($planCajas, array("depurar", "propuestas"), array())),
    "plan_terminales" => array(
        "schema_terminales_pendiente" => valor($planTerminales, array("depurar", "schema_terminales_pendiente"), null),
        "schema_usuarios_cajas_pendiente" => valor($planTerminales, array("depurar", "schema_usuarios_cajas_pendiente"), null),
        "regla_operativa" => valor($planTerminales, array("depurar", "regla_operativa"), ""),
        "propuestas" => array_map(function ($item) {
            return array(
                "codigo_almacen" => $item["codigo_almacen"],
                "terminal" => $item["terminal_sugerida"]["codigo"],
                "caja" => $item["asignacion_sugerida"]["caja_codigo"],
                "id_usuario" => $item["asignacion_sugerida"]["id_usuario"]
            );
        }, valor($planTerminales, array("depurar", "propuestas"), array()))
    ),
    "asignacion_actual" => array(
        "mensaje" => $asignacionActual["mensaje"],
        "schema_pendiente" => valor($asignacionActual, array("depurar", "schema_pendiente"), null),
        "asignacion_activa" => valor($asignacionActual, array("depurar", "asignacion_activa"), null),
        "modo_ui" => valor($asignacionActual, array("depurar", "modo_ui"), ""),
        "bloqueos" => valor($asignacionActual, array("depurar", "bloqueos"), array())
    ),
    "prevalidacion" => array(
        "mensaje" => $prevalidacion["mensaje"],
        "bloqueos" => valor($prevalidacion, array("depurar", "bloqueos"), array()),
        "totales" => valor($prevalidacion, array("depurar", "totales"), array()),
        "plan_salida" => valor($prevalidacion, array("depurar", "partidas", 0, "plan_salida_inventario"), array())
    ),
    "confirmacion" => array(
        "mensaje" => $confirmacion["mensaje"],
        "schema_pendiente" => valor($confirmacion, array("depurar", "schema_pendiente"), null),
        "bloqueos" => valor($confirmacion, array("depurar", "bloqueos"), array())
    ),
    "pedido" => array(
        "mensaje" => $pedido["mensaje"],
        "bloqueos" => valor($pedido, array("depurar", "bloqueos"), array())
    ),
    "apertura" => array(
        "mensaje" => $apertura["mensaje"],
        "bloqueos" => valor($apertura, array("depurar", "bloqueos"), array())
    ),
    "cierre" => array(
        "mensaje" => $cierre["mensaje"],
        "diferencia" => valor($cierre, array("depurar", "diferencia"), null),
        "bloqueos" => valor($cierre, array("depurar", "bloqueos"), array())
    ),
    "ticket" => array(
        "mensaje" => $ticket["mensaje"],
        "preview_contiene_no_confirmada" => strpos(valor($ticket, array("depurar", "ticket_texto"), ""), "NO ES VENTA CONFIRMADA") !== false
    ),
    "devolucion" => array(
        "mensaje" => $devolucion["mensaje"],
        "bloqueos" => valor($devolucion, array("depurar", "bloqueos"), array())
    ),
    "esquema" => array(
        "tablas_pendientes" => tablasPendientes(valor($auditoriaEsquema, array("depurar"), array()))
    ),
    "imagenes_pos" => array(
        "ok" => !$busquedaImagenes["error"] && imagenesConUrl(valor($busquedaImagenes, array("depurar"), array())) > 0,
        "skus_con_url" => imagenesConUrl(valor($busquedaImagenes, array("depurar"), array())),
        "muestra" => array_map(function ($item) {
            return array("sku" => $item["sku"], "url_imagen" => $item["url_imagen"]);
        }, array_slice(valor($busquedaImagenes, array("depurar"), array()), 0, 5))
    )
);

echo json_encode($resumen, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function ids($hallazgos) {
    return array_map(function ($item) {
        return isset($item["id"]) ? $item["id"] : null;
    }, $hallazgos);
}

function tablasPendientes($tablas) {
    $pendientes = array();
    foreach ($tablas as $tabla) {
        if (empty($tabla["existe"])) {
            $pendientes[] = $tabla["tabla"];
        }
    }
    return $pendientes;
}

function imagenesConUrl($items) {
    $total = 0;
    foreach ($items as $item) {
        if (isset($item["url_imagen"]) && trim((string) $item["url_imagen"]) !== "") {
            $total++;
        }
    }
    return $total;
}
