<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar el flujo guiado de creacion/operacion en Comercial > Listas de precios.
 * Impacto: asegura que la vista tenga pasos operativos sin guardar BD ni depender de JS para precios finales.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkFlujo("panel_flujo", strpos($contenidoVista, "lp_flujo_operativo") !== false, "La vista tiene mapa de flujo operativo"),
    checkFlujo("pasos_base", contieneTodos($contenidoVista, array("data-lp-flujo=\"encabezado\"", "data-lp-flujo=\"productos\"", "data-lp-flujo=\"alcance\"", "data-lp-flujo=\"asignacion\"", "data-lp-flujo=\"revision\"")), "El mapa contiene los cinco pasos base"),
    checkFlujo("scroll_operativo", strpos($contenidoVista, "data-lp-scroll") !== false && strpos($contenidoJs, "scrollIntoView") !== false, "Los pasos navegan a la seccion correspondiente"),
    checkFlujo("estado_real", contieneTodos($contenidoJs, array("function actualizarFlujoOperativo", "estado.asignacionesClientes", "estado.asignacionesSegmentos", "estado.revision", "estado.cambios")), "El flujo se alimenta de estado real de lista/productos/asignaciones"),
    checkFlujo("activo_y_listo", strpos($contenidoJs, "is-ready") !== false && strpos($contenidoJs, "is-active") !== false, "El JS marca pasos listos y paso activo"),
    checkFlujo("listeners_encabezado", contieneTodos($contenidoJs, array("lp_lista_codigo", "lp_lista_nombre", "actualizarFlujoOperativo(\"encabezado\")")), "El encabezado refresca el flujo durante captura"),
    checkFlujo("version_cache", strpos($contenidoVista, "20260719-flujo-guiado1") !== false, "La vista fuerza version de JS del flujo guiado")
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
    "resultado" => empty($bloqueos) ? "PASS_FLUJO_GUIADO_UI" : "FAIL_FLUJO_GUIADO_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkFlujo($id, $ok, $descripcion) {
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
