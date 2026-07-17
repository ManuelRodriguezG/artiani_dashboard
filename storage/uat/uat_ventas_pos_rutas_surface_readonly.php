<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar que la superficie minima de rutas POS/checador exista antes de prueba real.
 * Impacto: detecta metodos de controlador o referencias JS faltantes sin ejecutar endpoints ni consultar BD.
 * Contrato: read-only; analiza archivos fuente, no invoca rutas y no modifica datos.
 */

$raiz = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "..");
$controlador = $raiz . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Ventas.php";
$jsPos = $raiz . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "pos.js";
$jsChecador = $raiz . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "checador_precios.js";

$metodosEsperados = array(
    "pos",
    "checador_precios",
    "pedidos",
    "devoluciones",
    "caja_turnos",
    "caja_movimientos",
    "caja_evidencias",
    "pos_configuracion",
    "pos_catalogos_erp",
    "pos_buscar_skus_erp",
    "pos_checador_precio_erp",
    "pos_disponibilidad_erp",
    "pos_carrito_prevalidar_erp",
    "pos_confirmar_dryrun_erp",
    "pos_confirmar_erp",
    "pos_ticket_devolucion_erp",
    "pos_cliente_alta_rapida_dryrun_erp",
    "pos_excepcion_comercial_dryrun_erp",
    "pos_excepcion_comercial_registrar_erp",
    "caja_movimiento_dryrun_erp",
    "ticket_venta_readonly_erp",
    "devolucion_dryrun_erp"
);

$urlsJsEsperadas = array(
    "/ventas/pos_catalogos_erp",
    "/ventas/pos_buscar_skus_erp",
    "/ventas/pos_checador_precio_erp",
    "/ventas/pos_disponibilidad_erp",
    "/ventas/pos_carrito_prevalidar_erp",
    "/ventas/pos_confirmar_dryrun_erp",
    "/ventas/pos_confirmar_erp",
    "/ventas/pos_ticket_devolucion_erp",
    "/ventas/pos_cliente_alta_rapida_dryrun_erp",
    "/ventas/pos_excepcion_comercial_dryrun_erp",
    "/ventas/pos_excepcion_comercial_registrar_erp",
    "/ventas/caja_movimiento_dryrun_erp"
);

$bloqueos = array();
$ventasSrc = leer($controlador, $bloqueos, "controlador Ventas");
$jsSrc = leer($jsPos, $bloqueos, "JS POS") . "\n" . leer($jsChecador, $bloqueos, "JS checador");

$metodosEncontrados = array();
if ($ventasSrc !== "") {
    preg_match_all('/public\s+function\s+([A-Za-z0-9_]+)\s*\(/', $ventasSrc, $matches);
    $metodosEncontrados = array_values(array_unique($matches[1]));
}

$faltanMetodos = array();
foreach ($metodosEsperados as $metodo) {
    if (!in_array($metodo, $metodosEncontrados, true)) {
        $faltanMetodos[] = $metodo;
        $bloqueos[] = "Falta metodo Ventas::" . $metodo;
    }
}

$faltanUrls = array();
foreach ($urlsJsEsperadas as $url) {
    if (strpos($jsSrc, $url) === false) {
        $faltanUrls[] = $url;
        $bloqueos[] = "Falta referencia JS a " . $url;
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_rutas_surface_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "resumen" => array(
        "metodos_esperados" => count($metodosEsperados),
        "metodos_encontrados_en_controlador" => count($metodosEncontrados),
        "urls_js_esperadas" => count($urlsJsEsperadas),
        "faltan_metodos" => $faltanMetodos,
        "faltan_urls_js" => $faltanUrls,
        "bloqueos" => $bloqueos,
        "surface_ok" => empty($bloqueos)
    ),
    "contrato" => array(
        "read_only" => true,
        "no_consulta_bd" => true,
        "no_invoca_endpoints" => true,
        "no_escribe_bd" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function leer($ruta, &$bloqueos, $nombre) {
    if (!is_file($ruta)) {
        $bloqueos[] = "No existe " . $nombre . ": " . $ruta;
        return "";
    }
    if (!is_readable($ruta)) {
        $bloqueos[] = "No es legible " . $nombre . ": " . $ruta;
        return "";
    }
    $contenido = file_get_contents($ruta);
    return is_string($contenido) ? $contenido : "";
}
