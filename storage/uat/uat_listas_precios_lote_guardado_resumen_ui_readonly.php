<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar resumen operativo posterior al guardado por lote de Listas de precios.
 * Impacto: protege que el usuario vea guardados/errores por SKU sin consultar auditoria tecnica.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$modelo = $raiz . DIRECTORY_SEPARATOR . "app/modelos/ListasPreciosErp.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$contenidoModelo = is_readable($modelo) ? file_get_contents($modelo) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkResumen("modelo_guardados_detalle", strpos($contenidoModelo, "guardados_detalle") !== false, "El modelo devuelve detalle acotado de guardados"),
    checkResumen("modelo_errores_fila", strpos($contenidoModelo, '"fila" => $idx + 1') !== false, "El modelo devuelve fila en errores de lote"),
    checkResumen("ui_render_resultado", strpos($contenidoJs, "function renderResultadoGuardadoLote") !== false, "La UI renderiza resultado posterior al guardado"),
    checkResumen("ui_muestra_guardados", contieneTodos($contenidoJs, array("Primeros guardados", "guardados_detalle", "SKU")), "La UI muestra primeros SKUs guardados"),
    checkResumen("ui_muestra_errores", contieneTodos($contenidoJs, array("Errores", "Mas errores", "item.mensaje")), "La UI muestra errores por fila/SKU"),
    checkResumen("guardado_invoca_resumen", strpos($contenidoJs, "renderResultadoGuardadoLote(data, response)") !== false, "El guardado por lote invoca el resumen operativo")
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
    "resultado" => empty($bloqueos) ? "PASS_LOTE_GUARDADO_RESUMEN_UI" : "FAIL_LOTE_GUARDADO_RESUMEN_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkResumen($id, $ok, $descripcion) {
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
