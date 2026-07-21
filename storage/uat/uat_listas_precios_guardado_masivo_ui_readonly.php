<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar guardado masivo seguro de precios en la mesa Comercial/Listas.
 * Impacto: protege que el lote muestre resumen, bloquee perdida y confirme avisos antes de guardar.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkMasivo("panel_prevalidacion", strpos($contenidoVista, "lp_lote_prevalidacion") !== false, "La vista tiene panel de prevalidacion de lote"),
    checkMasivo("boton_prevalidar_manual", strpos($contenidoVista, "lp_prevalidar_cambios") !== false, "La vista permite prevalidar cambios antes de guardar"),
    checkMasivo("render_prevalidacion", strpos($contenidoJs, "function renderPrevalidacionLote") !== false, "JS renderiza resumen de prevalidacion"),
    checkMasivo("prevalidacion_manual_no_guarda", strpos(extraerFuncion($contenidoJs, "prevalidarCambiosPendientes"), "guardarCambiosLoteConfirmado") === false, "La prevalidacion manual no invoca guardado"),
    checkMasivo("dryrun_backend", strpos($contenidoJs, "listas_precios_detalles_lote_dryrun_erp") !== false, "La UI llama dry-run backend antes de guardar"),
    checkMasivo("bloquea_perdida", strpos($contenidoJs, "El lote tiene precios con perdida") !== false, "La UI bloquea lotes con perdida"),
    checkMasivo("confirma_avisos", strpos(extraerFuncion($contenidoJs, "prevalidarCambiosLoteBackend"), "window.confirm") !== false, "La UI confirma avisos antes de guardar"),
    checkMasivo("resumen_metricas", contieneTodos($contenidoJs, array("OK margen", "Margen bajo", "Sin costo", "Perdida", "Errores")), "El resumen visible muestra metricas clave"),
    checkMasivo("limpia_prevalidacion", strpos($contenidoJs, "renderPrevalidacionLote(null)") !== false, "La UI limpia resumen al recargar o descartar cambios")
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
    "resultado" => empty($bloqueos) ? "PASS_GUARDADO_MASIVO_UI" : "FAIL_GUARDADO_MASIVO_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkMasivo($id, $ok, $descripcion) {
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

function extraerFuncion($contenido, $nombre) {
    $inicio = strpos($contenido, "function " . $nombre);
    if ($inicio === false) {
        return "";
    }
    $siguiente = strpos($contenido, "\n    function ", $inicio + 10);
    if ($siguiente === false) {
        return substr($contenido, $inicio);
    }
    return substr($contenido, $inicio, $siguiente - $inicio);
}
