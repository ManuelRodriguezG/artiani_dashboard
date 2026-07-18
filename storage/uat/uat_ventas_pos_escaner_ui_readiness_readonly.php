<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: auditar en modo read-only que el escaner rapido dentro de POS este cableado en vista y JS.
 * Impacto: permite validar superficie UI sin abrir camara, sin sesion de navegador y sin escribir BD.
 * Contrato: solo lectura de archivos; no consulta ni modifica base de datos.
 */

$base = realpath(__DIR__ . "/../..");
$vista = $base . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "vistas" . DIRECTORY_SEPARATOR . "paginas" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "pos.php";
$js = $base . DIRECTORY_SEPARATOR . "public" . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "js" . DIRECTORY_SEPARATOR . "custom" . DIRECTORY_SEPARATOR . "apps" . DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "ventas" . DIRECTORY_SEPARATOR . "pos.js";

$bloqueos = array();
$avisos = array();
$checks = array();

$vistaContenido = leerArchivo($vista, $bloqueos, "vista POS");
$jsContenido = leerArchivo($js, $bloqueos, "JS POS");

if ($vistaContenido !== "") {
    revisarTokens($vistaContenido, array(
        "pos_scan_camera_btn" => "boton camara junto al buscador",
        "pos_scan_modal" => "modal escaner",
        "pos_scan_video" => "preview video",
        "pos_scan_camera_device" => "selector camara",
        "pos_scan_torch" => "control luz",
        "pos_scan_focus" => "control enfoque",
        "20260717-scan-pos" => "cache buster escaner"
    ), $checks, $bloqueos);
}

if ($jsContenido !== "") {
    revisarTokens($jsContenido, array(
        "function abrirEscanerPos" => "abrir modal escaner",
        "function iniciarCamaraPos" => "iniciar camara",
        "function detectarLoopCamaraPos" => "loop lector codigos",
        "function buscarCodigoEscaneado" => "resolver codigo leido",
        "new BarcodeDetector" => "lector nativo navegador",
        "agregarProducto(items[0])" => "agregado automatico coincidencia unica",
        "key === \"F3\"" => "atajo F3",
        "Selecciona o configura el punto de venta antes de escanear" => "guard punto de venta"
    ), $checks, $bloqueos);

    if (strpos($jsContenido, "/ventas/pos_checador_precio_erp") !== false) {
        $avisos[] = "El POS escaner no debe depender del checador read-only; revisar si se agrego dependencia accidental.";
    }
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_escaner_ui_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "checks" => $checks,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => $avisos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_camara" => true,
        "no_cobra" => true,
        "no_reserva" => true,
        "no_mueve_inventario" => true,
        "no_mueve_caja" => true
    )
), empty($bloqueos) ? 0 : 1);

function leerArchivo($ruta, &$bloqueos, $etiqueta) {
    if (!is_file($ruta)) {
        $bloqueos[] = "No existe " . $etiqueta . ": " . $ruta;
        return "";
    }
    $contenido = file_get_contents($ruta);
    if ($contenido === false) {
        $bloqueos[] = "No se pudo leer " . $etiqueta . ": " . $ruta;
        return "";
    }
    return $contenido;
}

function revisarTokens($contenido, $tokens, &$checks, &$bloqueos) {
    foreach ($tokens as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $checks[] = array(
            "token" => $token,
            "descripcion" => $descripcion,
            "ok" => $ok
        );
        if (!$ok) {
            $bloqueos[] = "Falta " . $descripcion;
        }
    }
}

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
