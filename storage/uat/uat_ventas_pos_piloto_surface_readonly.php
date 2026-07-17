<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar en solo lectura que existe la superficie minima de POS para piloto operativo.
 * Impacto: revisa controlador, vistas, JS y documentos de operacion sin conectarse a BD ni modificar archivos.
 * Contrato: read-only; no ejecuta endpoints, no abre navegador, no cobra y no mueve inventario.
 */

$base = dirname(__DIR__, 2);

$archivos = array(
    "controlador_ventas" => "app/controladores/Ventas.php",
    "modelo_ventas_erp" => "app/modelos/VentasErp.php",
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "vista_caja_turnos" => "app/vistas/paginas/apps/erp/ventas/caja_turnos.php",
    "vista_listado_ventas" => "app/vistas/paginas/apps/erp/ventas/listado.php",
    "vista_detalle_venta" => "app/vistas/paginas/apps/erp/ventas/venta_detalle.php",
    "vista_reportes" => "app/vistas/paginas/apps/erp/ventas/reportes.php",
    "vista_configuracion" => "app/vistas/paginas/apps/erp/ventas/pos_configuracion.php",
    "vista_checador" => "app/vistas/paginas/apps/erp/ventas/checador_precios.php",
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "js_caja_turnos" => "public/assets/js/custom/apps/erp/ventas/caja_turnos.js",
    "js_listado" => "public/assets/js/custom/apps/erp/ventas/listado.js",
    "js_detalle" => "public/assets/js/custom/apps/erp/ventas/venta_detalle.js",
    "js_reportes" => "public/assets/js/custom/apps/erp/ventas/reportes.js",
    "js_configuracion" => "public/assets/js/custom/apps/erp/ventas/pos_configuracion.js",
    "js_checador" => "public/assets/js/custom/apps/erp/ventas/checador_precios.js",
    "manual_cajero" => "docs/erp_ventas_pos_manual_cajero.md",
    "prueba_real_usuario" => "docs/erp_ventas_pos_prueba_real_usuario.md",
    "checklist_piloto" => "docs/erp_ventas_pos_piloto_operativo_checklist.md",
    "handoff" => "docs/erp_ventas_pos_handoff_contexto.md"
);

$metodos = array(
    "pos",
    "caja_turnos",
    "mostrar",
    "venta_detalle",
    "reportes",
    "pos_configuracion",
    "checador_precios",
    "atenciones_bandeja_dryrun_erp",
    "ticket_preview_dryrun_erp",
    "ticket_venta_readonly_erp",
    "pos_configuracion_resumen_erp",
    "pos_configuracion_caja_dryrun_erp",
    "pos_configuracion_terminal_dryrun_erp",
    "pos_configuracion_asignacion_dryrun_erp",
    "pos_configuracion_caja_guardar_erp",
    "pos_configuracion_terminal_guardar_erp",
    "pos_configuracion_asignacion_guardar_erp",
    "pos_configuracion_desactivar_erp"
);

$urls = array(
    "/ventas/pos",
    "/ventas/caja_turnos",
    "/ventas/mostrar",
    "/ventas/reportes",
    "/ventas/pos_configuracion",
    "/ventas/checador_precios",
    "/ventas/atenciones_bandeja_dryrun_erp",
    "/ventas/ticket_preview_dryrun_erp",
    "/ventas/ticket_venta_readonly_erp"
);

$bloqueos = array();
$avisos = array();
$detalleArchivos = array();
foreach ($archivos as $clave => $relativo) {
    $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $existe = is_file($ruta);
    $detalleArchivos[$clave] = array(
        "ruta" => $relativo,
        "existe" => $existe,
        "bytes" => $existe ? filesize($ruta) : 0
    );
    if (!$existe) {
        $bloqueos[] = "Falta archivo " . $relativo;
    }
}

$controladorRuta = $base . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Ventas.php";
$controlador = is_file($controladorRuta) ? file_get_contents($controladorRuta) : "";
$detalleMetodos = array();
foreach ($metodos as $metodo) {
    $existe = preg_match('/function\s+' . preg_quote($metodo, '/') . '\s*\(/', $controlador) === 1;
    $detalleMetodos[$metodo] = $existe;
    if (!$existe) {
        $bloqueos[] = "Falta metodo Ventas::" . $metodo;
    }
}

$jsPosRuta = $base . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "pos.js";
$jsPos = is_file($jsPosRuta) ? file_get_contents($jsPosRuta) : "";
$marcasUi = array(
    "buscador_producto" => "pos_buscar",
    "ticket_preview" => "pos_ticket_preview",
    "atenciones_modal" => "pos_atenciones_modal",
    "atenciones_bandeja" => "atencionesBandejaDryRun",
    "ticket_real" => "abrirTicketVentaReal",
    "cliente_crm" => "pos_cliente_identificador"
);
$detalleUi = array();
foreach ($marcasUi as $clave => $marca) {
    $existe = strpos($jsPos, $marca) !== false;
    $detalleUi[$clave] = $existe;
    if (!$existe) {
        $avisos[] = "No se encontro marca UI " . $marca . " en pos.js";
    }
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_surface_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "resumen" => array(
        "archivos_revisados" => count($archivos),
        "metodos_revisados" => count($metodos),
        "urls_referencia" => $urls,
        "bloqueos" => $bloqueos,
        "avisos" => $avisos
    ),
    "detalle" => array(
        "archivos" => $detalleArchivos,
        "metodos" => $detalleMetodos,
        "ui" => $detalleUi
    ),
    "contrato" => array(
        "read_only" => true,
        "no_bd" => true,
        "no_http" => true,
        "no_cobra" => true,
        "no_mueve_inventario" => true
    )
));

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
