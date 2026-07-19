<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar que la navegacion visible del modulo Ventas/POS expone las pantallas necesarias para piloto.
 * Impacto: evita que vistas listas queden ocultas del operador aunque existan rutas/controlador.
 * Contrato: read-only; no consulta BD, no cambia permisos y no escribe archivos.
 */

$base = dirname(__DIR__, 2);
$sidebarRuta = $base . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "vistas" . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "header" . DIRECTORY_SEPARATOR . "sidebar.php";
$controladorRuta = $base . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Ventas.php";
$sidebar = is_file($sidebarRuta) ? file_get_contents($sidebarRuta) : "";
$controlador = is_file($controladorRuta) ? file_get_contents($controladorRuta) : "";

$items = array(
    "tablero_ventas" => array("titulo" => "Tablero de ventas", "ruta" => "/ventas/mostrar", "metodo" => "mostrar"),
    "pos" => array("titulo" => "POS", "ruta" => "/ventas/pos", "metodo" => "pos"),
    "checador" => array("titulo" => "Checador de precios", "ruta" => "/ventas/checador_precios", "metodo" => "checador_precios"),
    "pedidos" => array("titulo" => "Pedidos", "ruta" => "/ventas/pedidos", "metodo" => "pedidos"),
    "devoluciones" => array("titulo" => "Devoluciones", "ruta" => "/ventas/devoluciones", "metodo" => "devoluciones"),
    "caja_turnos" => array("titulo" => "Caja y turnos", "ruta" => "/ventas/caja_turnos", "metodo" => "caja_turnos"),
    "movimientos_caja" => array("titulo" => "Movimientos caja", "ruta" => "/ventas/caja_movimientos", "metodo" => "caja_movimientos"),
    "evidencias_caja" => array("titulo" => "Evidencias caja", "ruta" => "/ventas/caja_evidencias", "metodo" => "caja_evidencias"),
    "reportes_pos" => array("titulo" => "Reportes POS", "ruta" => "/ventas/reportes", "metodo" => "reportes"),
    "configuracion_pos" => array("titulo" => "Configuracion POS", "ruta" => "/ventas/pos_configuracion", "metodo" => "pos_configuracion")
);

$bloqueos = array();
$detalle = array();
foreach ($items as $clave => $item) {
    $tituloOk = strpos($sidebar, $item["titulo"]) !== false;
    $rutaOk = strpos($sidebar, $item["ruta"]) !== false;
    $metodoOk = preg_match('/function\s+' . preg_quote($item["metodo"], '/') . '\s*\(/', $controlador) === 1;
    $detalle[$clave] = array(
        "titulo" => $item["titulo"],
        "ruta" => $item["ruta"],
        "metodo" => $item["metodo"],
        "titulo_en_sidebar" => $tituloOk,
        "ruta_en_sidebar" => $rutaOk,
        "metodo_controlador" => $metodoOk
    );
    if (!$tituloOk || !$rutaOk) {
        $bloqueos[] = "Falta item de menu " . $item["titulo"] . " (" . $item["ruta"] . ")";
    }
    if (!$metodoOk) {
        $bloqueos[] = "Falta metodo Ventas::" . $item["metodo"];
    }
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_navegacion_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "items_revisados" => count($items),
    "bloqueos" => $bloqueos,
    "detalle" => $detalle,
    "contrato" => array(
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_modifica_permisos" => true,
        "no_invoca_http" => true
    )
), empty($bloqueos) ? 0 : 1);

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
