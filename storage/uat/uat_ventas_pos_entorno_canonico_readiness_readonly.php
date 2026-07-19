<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar que POS use el proyecto y host canonicos vigentes.
 * Impacto: evita que pruebas nuevas apunten al proyecto historico o a hosts obsoletos.
 * Contrato: read-only; no consulta BD, no invoca HTTP, no escribe archivos y no ejecuta acciones POS.
 */

date_default_timezone_set("America/Mexico_City");

$root = realpath(__DIR__ . "/../..");
$bloqueos = array();
$avisos = array();
$detalle = array();

$docsRequeridos = array(
    "AGENTS.md" => array(
        "C:\\xampp\\htdocs\\panel_de_control" => "ruta canonica",
        "http://panel.com.local/" => "host canonico",
        "No realizar cambios en `C:\\xampp\\htdocs\\panel`" => "bloqueo ruta historica",
    ),
    "docs/erp_ventas_pos_estado_cierre_modulo.md" => array(
        "C:\\xampp\\htdocs\\panel_de_control" => "ruta canonica POS",
        "http://panel.com.local/" => "host canonico POS",
        "listo_para_piloto_controlado_con_condiciones" => "decision vigente POS",
    ),
);

foreach ($docsRequeridos as $rutaRelativa => $tokens) {
    $ruta = ruta($root, $rutaRelativa);
    $contenido = is_file($ruta) ? file_get_contents($ruta) : "";
    $detalle["documentos"][$rutaRelativa] = array("existe" => is_file($ruta), "checks" => array());
    if (!is_file($ruta)) {
        $bloqueos[] = "Falta documento canonico: " . $rutaRelativa;
        continue;
    }
    foreach ($tokens as $token => $descripcion) {
        $ok = strpos($contenido, $token) !== false;
        $detalle["documentos"][$rutaRelativa]["checks"][$token] = array("descripcion" => $descripcion, "ok" => $ok);
        if (!$ok) {
            $bloqueos[] = "Documento " . $rutaRelativa . " no menciona " . $descripcion . " [" . $token . "]";
        }
    }
}

$scriptsJs = glob($root . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "uat" . DIRECTORY_SEPARATOR . "uat_ventas_pos*.js");
$detalle["scripts_playwright"] = array(
    "total_revisados" => count($scriptsJs),
    "dashboard_defaults" => array(),
    "panel_defaults" => array(),
);

foreach ($scriptsJs as $script) {
    $rel = str_replace($root . DIRECTORY_SEPARATOR, "", $script);
    $contenido = file_get_contents($script);
    if (strpos($contenido, "dashboard.com.local") !== false) {
        $detalle["scripts_playwright"]["dashboard_defaults"][] = $rel;
        $bloqueos[] = "Script ejecutable POS conserva dashboard.com.local: " . $rel;
    }
    if (strpos($contenido, "panel.com.local") !== false) {
        $detalle["scripts_playwright"]["panel_defaults"][] = $rel;
    }
}

if (empty($detalle["scripts_playwright"]["panel_defaults"])) {
    $avisos[] = "No se encontraron scripts POS Playwright con default panel.com.local; revisar si no hay pruebas UI vigentes.";
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_entorno_canonico_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
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

function ruta($root, $relativa)
{
    return $root . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativa);
}

