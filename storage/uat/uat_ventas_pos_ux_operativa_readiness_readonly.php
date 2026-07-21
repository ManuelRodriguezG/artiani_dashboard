<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar UX operativa POS sin ejecutar acciones reales.
 * Impacto: asegura menu superior, manual, pagos rapidos, atajos y textos criticos antes del piloto.
 * Contrato: read-only; no consulta BD, no escribe BD, no invoca HTTP y no mueve caja/inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$archivos = array(
    "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
    "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
    "controlador" => "app/controladores/Ventas.php",
    "sidebar" => "app/vistas/includes/header/sidebar.php",
    "manual" => "app/vistas/paginas/apps/erp/ventas/manual_pos.php",
);

$checks = array(
    "vista_pos" => array(
        "pos-action-strip" => "menu superior POS con iconos",
        ".pos-module-bar { display: flex; flex-wrap: wrap;" => "menu superior sin scroll horizontal propio",
        "pos-shortcut-hint" => "atajos visibles dentro de botones clave",
        "/ventas/manual_pos#manual-arranque" => "acceso a checklist de arranque desde POS",
        "pos-pay-quick" => "botones rapidos de metodos de pago visibles",
        "pos_compromiso_wrap" => "compromiso ocultable por tipo de documento",
        "pos_inventario_pendiente_dryrun" => "inventario pendiente queda como accion avanzada",
        "/ventas/manual_pos" => "acceso al manual desde POS",
    ),
    "js_pos" => array(
        "textoLegible" => "limpieza de caracteres mojibake",
        "actualizarVisibilidadDocumento" => "visibilidad de compromiso por venta/pedido",
        "event.altKey && key === \"1\"" => "atajo pago efectivo implementado",
        "agregarPagoRapido(\"efectivo\")" => "atajo/boton pago efectivo conectado",
        "key === \"Enter\"" => "atajo cobrar conectado",
    ),
    "controlador" => array(
        "function manual_pos" => "ruta manual POS",
        "ventas.ver" => "permiso de lectura para manual",
    ),
    "sidebar" => array(
        "'Manual POS'" => "manual visible en sidebar",
        "'/ventas/manual_pos'" => "ruta manual POS en sidebar",
    ),
    "manual" => array(
        "Manual Ventas y POS" => "titulo manual del modulo completo",
        "Indice del modulo" => "navegacion interna por pestañas del modulo",
        "Checklist para empezar a usar POS" => "checklist de arranque operativo",
        "Regla para activar venta con existencia negativa" => "politica de inventario pendiente explicada",
        "Tablero de ventas" => "explicacion tablero ventas",
        "Checador de precios" => "explicacion checador",
        "Pedidos y apartados" => "explicacion pedidos y apartados",
        "Devoluciones y reversas" => "explicacion devoluciones",
        "Caja y turnos" => "explicacion caja y turnos",
        "Movimientos caja" => "explicacion movimientos caja",
        "Evidencias caja" => "explicacion evidencias caja",
        "Reportes POS" => "explicacion reportes POS",
        "Configuracion POS" => "explicacion configuracion POS",
        "Inventario pendiente" => "explicacion de inventario pendiente",
        "Stock es lo disponible" => "explicacion stock/pieza/granel",
        "La fecha compromiso solo aplica a pedidos o apartados" => "explicacion compromiso",
        "Prevalidar no vende ni descuenta inventario" => "diferencia entre prevalidar y cobrar",
    ),
);

$bloqueos = array();
$detalle = array();
foreach ($archivos as $clave => $relativa) {
    $ruta = $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativa);
    $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
    $detalle[$clave] = array("ruta" => $relativa, "existe" => is_file($ruta), "checks" => array());
    if (!is_file($ruta)) {
        $bloqueos[] = "Falta archivo " . $relativa;
        continue;
    }
    foreach ($checks[$clave] as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $detalle[$clave]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = $relativa . " no contiene " . $descripcion . " [" . $token . "]";
        }
    }
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ux_operativa_readiness_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "resultado" => empty($bloqueos) ? "ux_pos_operativa_lista" : "ux_pos_requiere_revision",
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_invoca_http" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);
