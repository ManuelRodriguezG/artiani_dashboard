<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar readiness de impresion de ticket/corte POS sin imprimir ni tocar BD.
 * Impacto: asegura que POS, listado, detalle, caja y reportes tengan acciones imprimibles antes de piloto/hardware.
 * Contrato: read-only; no invoca navegador, no imprime, no consulta BD, no cobra y no mueve caja/inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$bloqueos = array();
$detalle = array(
    "pos" => revisar("POS ticket directo", array(
        "app/vistas/paginas/apps/erp/ventas/pos.php" => array(
            "pos_ticket_modal" => "modal ticket",
            "pos_ticket_imprimir" => "boton imprimir ticket",
        ),
        "public/assets/js/custom/apps/erp/ventas/pos.js" => array(
            "ticketPosActual" => "estado ticket POS",
            "function imprimirTicketPos" => "funcion imprimir ticket POS",
            "window.open(\"\", \"erp_pos_ticket_directo\"" => "ventana impresion POS",
            "ventana.print()" => "print POS",
            "pos_ticket_imprimir" => "listener boton imprimir POS",
        ),
    ), $root, $bloqueos),
    "listado" => revisar("Listado ventas ticket", array(
        "app/vistas/paginas/apps/erp/ventas/listado.php" => array(
            "ventas_ticket_imprimir" => "boton imprimir listado",
        ),
        "public/assets/js/custom/apps/erp/ventas/listado.js" => array(
            "function imprimirTicket" => "funcion imprimir listado",
            "window.open(\"\", \"erp_pos_ticket\"" => "ventana impresion listado",
            "ventana.print()" => "print listado",
        ),
    ), $root, $bloqueos),
    "detalle_venta" => revisar("Detalle venta ticket", array(
        "public/assets/js/custom/apps/erp/ventas/venta_detalle.js" => array(
            "function imprimirTicket" => "funcion imprimir detalle",
            "window.open(\"\", \"erp_pos_ticket_detalle\"" => "ventana impresion detalle",
            "ventana.print()" => "print detalle",
        ),
    ), $root, $bloqueos),
    "caja_turnos" => revisar("Caja turnos corte", array(
        "app/vistas/paginas/apps/erp/ventas/caja_turnos.php" => array(
            "pos_caja_corte_imprimir" => "boton imprimir corte caja",
        ),
        "public/assets/js/custom/apps/erp/ventas/caja_turnos.js" => array(
            "function imprimirCorte" => "funcion imprimir corte caja",
            "window.open(\"\", \"erp_pos_corte\"" => "ventana impresion corte caja",
            "ventana.print()" => "print corte caja",
        ),
    ), $root, $bloqueos),
    "reportes" => revisar("Reportes corte", array(
        "app/vistas/paginas/apps/erp/ventas/reportes.php" => array(
            "pos_rep_corte_imprimir" => "boton imprimir corte reportes",
        ),
        "public/assets/js/custom/apps/erp/ventas/reportes.js" => array(
            "function imprimirCorte" => "funcion imprimir corte reportes",
            "window.open(\"\", \"erp_pos_corte_reporte\"" => "ventana impresion corte reportes",
            "ventana.print()" => "print corte reportes",
        ),
    ), $root, $bloqueos),
);

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_impresion_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "bloqueos" => array_values(array_unique($bloqueos)),
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_invoca_http" => true,
        "no_imprime" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function revisar($titulo, $archivos, $root, &$bloqueos)
{
    $salida = array("titulo" => $titulo, "archivos" => array(), "ok" => true);
    foreach ($archivos as $relativa => $tokens) {
        $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativa);
        $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
        $salida["archivos"][$relativa] = array("existe" => is_file($ruta), "checks" => array());
        if (!is_file($ruta)) {
            $bloqueos[] = "Falta archivo de impresion: " . $relativa;
            $salida["ok"] = false;
            continue;
        }
        foreach ($tokens as $token => $descripcion) {
            $ok = strpos($contenido, $token) !== false;
            $salida["archivos"][$relativa]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
            if (!$ok) {
                $bloqueos[] = $titulo . " no tiene " . $descripcion . " [" . $token . "]";
                $salida["ok"] = false;
            }
        }
    }
    return $salida;
}
