<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar manual operativo de Comercial/Listas de precios.
 * Impacto: protege capacitacion fase 1, ruta del manual y acceso desde sidebar.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$controlador = $raiz . DIRECTORY_SEPARATOR . "app/controladores/Comercial.php";
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios_manual.php";
$listas = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$sidebar = $raiz . DIRECTORY_SEPARATOR . "app/vistas/includes/header/sidebar.php";

$contenidoControlador = is_readable($controlador) ? file_get_contents($controlador) : "";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoListas = is_readable($listas) ? file_get_contents($listas) : "";
$contenidoSidebar = is_readable($sidebar) ? file_get_contents($sidebar) : "";

$checks = array(
    checkManual("vista_existe", is_readable($vista), "Existe vista del manual de Listas de precios"),
    checkManual("ruta_controlador", strpos($contenidoControlador, "function listas_precios_manual") !== false && strpos($contenidoControlador, "ventas.listas.ver") !== false, "Controlador expone ruta protegida del manual"),
    checkManual("sidebar_link", strpos($contenidoSidebar, "/comercial/listas_precios_manual") !== false, "Sidebar enlaza manual en Comercial"),
    checkManual("vista_link", strpos($contenidoListas, "/comercial/listas_precios_manual") !== false, "Vista de listas enlaza el manual"),
    checkManual("fase_1", contieneTodos($contenidoVista, array("Listo para piloto POS", "Puede operarse ya", "Ecommerce debe esperar")), "Manual explica estado de fase 1"),
    checkManual("flujo_operativo", contieneTodos($contenidoVista, array("Crear encabezado", "Definir alcance", "Capturar productos", "Prevalidar", "Guardar lote", "Asignar comercialmente")), "Manual describe flujo operativo"),
    checkManual("prioridad", contieneTodos($contenidoVista, array("Cliente directo CRM", "Segmento CRM", "Canal / almacen", "General ERP")), "Manual documenta prioridad del resolutor"),
    checkManual("uat_minimo", strpos($contenidoVista, "UAT minimo antes de operar en tienda") !== false, "Manual incluye UAT minimo"),
    checkManual("uat_previo_venta", contieneTodos($contenidoVista, array("Prueba previa a venta real", "uat_listas_precios_piloto_pos_readonly.php", "PASS_PILOTO_POS_LISTAS_PRECIOS")), "Manual incluye dry-run previo a venta real"),
    checkManual("uat_post_venta", contieneTodos($contenidoVista, array("Despues de vender en UAT", "uat_listas_precios_pos_venta_snapshot_readonly.php", "origen_esperado")), "Manual incluye verificacion post-venta por folio")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_MANUAL_LISTAS_PRECIOS" : "FAIL_MANUAL_LISTAS_PRECIOS",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkManual($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function contieneTodos($texto, $tokens) {
    foreach ($tokens as $token) {
        if (strpos($texto, $token) === false) {
            return false;
        }
    }
    return true;
}
