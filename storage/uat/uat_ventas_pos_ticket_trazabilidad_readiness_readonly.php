<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar superficie de ticket formal, garantia y trazabilidad POS sin consultar BD.
 * Impacto: POS/Postventa; revisa preview, ticket real, detalle, devoluciones, kardex y garantia snapshot.
 * Contrato: read-only; no genera folios, no imprime, no cobra, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "controlador" => $root . "/app/controladores/Ventas.php",
    "modelo" => $root . "/app/modelos/VentasErp.php",
    "pos_vista" => $root . "/app/vistas/paginas/apps/erp/ventas/pos.php",
    "pos_js" => $root . "/public/assets/js/custom/apps/erp/ventas/pos.js",
    "listado_vista" => $root . "/app/vistas/paginas/apps/erp/ventas/listado.php",
    "listado_js" => $root . "/public/assets/js/custom/apps/erp/ventas/listado.js",
    "detalle_vista" => $root . "/app/vistas/paginas/apps/erp/ventas/venta_detalle.php",
    "detalle_js" => $root . "/public/assets/js/custom/apps/erp/ventas/venta_detalle.js",
    "devoluciones_vista" => $root . "/app/vistas/paginas/apps/erp/ventas/devoluciones.php",
    "devoluciones_js" => $root . "/public/assets/js/custom/apps/erp/ventas/devoluciones.js",
);

$checks = array(
    "controlador" => array(
        "function ticket_preview_dryrun_erp" => "endpoint preview ticket",
        "function ticket_venta_readonly_erp" => "endpoint ticket venta formal",
        "function pos_ticket_devolucion_erp" => "endpoint ticket devolucion",
        "function venta_detalle" => "vista detalle venta",
    ),
    "modelo" => array(
        "function ticketPreviewDryRun" => "modelo preview ticket",
        "function ticketVentaFormalReadOnly" => "modelo ticket venta",
        "function ticketDevolucionFormalReadOnly" => "modelo ticket devolucion",
        "function formatearTicketVenta" => "formato ticket venta",
        "function formatearTicketDevolucion" => "formato ticket devolucion",
        "trazabilidad_inventario" => "trazabilidad inventario en respuesta",
        "erp_ventas_detalle_garantias" => "snapshot garantia por detalle",
        "no_recalcula_garantia_historica" => "garantia historica no recalculada",
    ),
    "pos_vista" => array(
        "pos_ticket_preview" => "boton ticket preview",
        "pos_ticket_modal" => "modal ticket",
        "pos_ticket_texto" => "texto ticket POS",
    ),
    "pos_js" => array(
        "/ventas/ticket_preview_dryrun_erp" => "llamada preview ticket",
        "/ventas/ticket_venta_readonly_erp" => "llamada ticket real",
        "data-pos-ticket-real" => "boton ticket al cobrar",
        "abrirTicketVentaReal" => "abrir ticket real",
        "Caja, kardex, garantias y trazabilidad registrados por backend" => "mensaje post cobro",
        "/ventas/venta_detalle?folio=" => "liga detalle venta",
        "/ventas/devoluciones?folio=" => "liga devoluciones",
    ),
    "listado_vista" => array(
        "ventas_ticket_modal" => "modal ticket listado",
        "ventas_ticket_texto" => "texto ticket listado",
        "ventas_ticket_imprimir" => "boton imprimir listado",
    ),
    "listado_js" => array(
        "/ventas/ticket_venta_readonly_erp" => "ticket desde listado",
        "ventas_ticket_imprimir" => "imprimir desde listado",
    ),
    "detalle_vista" => array(
        "Ticket, pagos, garantias y trazabilidad de inventario" => "detalle enfocado en trazabilidad",
        "venta-ticket-pre" => "bloque ticket detalle",
    ),
    "detalle_js" => array(
        "/ventas/ticket_venta_readonly_erp" => "ticket detalle",
        "garantia" => "garantia en detalle",
        "trazabilidad" => "trazabilidad en detalle",
    ),
    "devoluciones_vista" => array(
        "dev_ticket_consultar" => "consulta ticket devolucion",
        "dev_ticket_texto" => "texto ticket devolucion",
    ),
    "devoluciones_js" => array(
        "/ventas/pos_ticket_devolucion_erp" => "endpoint ticket devolucion UI",
        "function consultarTicket" => "consultar ticket devolucion",
    ),
);

$bloqueos = array();
$detalle = array();
foreach ($archivos as $clave => $archivo) {
    $detalle[$clave] = revisar($archivo, $root, isset($checks[$clave]) ? $checks[$clave] : array(), $bloqueos);
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ticket_trazabilidad_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_genera_folio" => true,
        "no_imprime" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function revisar($archivo, $root, $checks, &$bloqueos)
{
    $contenido = is_file($archivo) ? file_get_contents($archivo) : "";
    $resultado = array(
        "archivo" => str_replace("\\", "/", str_replace($root . DIRECTORY_SEPARATOR, "", $archivo)),
        "existe" => is_file($archivo),
        "checks" => array(),
    );
    if (!is_file($archivo)) {
        $bloqueos[] = "No existe " . $resultado["archivo"];
        return $resultado;
    }
    foreach ($checks as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $resultado["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = "Falta " . $descripcion . " [" . $resultado["archivo"] . " :: " . $token . "]";
        }
    }
    return $resultado;
}
