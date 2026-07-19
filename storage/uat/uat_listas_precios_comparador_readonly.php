<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar contrato UI del comparador de listas sin guardar precios.
 * Impacto: permite revisar diferencias antes de copiar precios entre listas.
 * Contrato: read-only; solo lee archivos locales, no carga MVC, no conecta MySQL ni modifica BD.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkComparador("vista_boton_comparar", strpos($contenidoVista, "lp_comparar_lista") !== false, "Existe boton para comparar lista origen"),
    checkComparador("vista_boton_usar_diferencias", strpos($contenidoVista, "lp_aplicar_comparacion") !== false, "Existe boton para usar diferencias revisadas"),
    checkComparador("vista_panel_resultado", strpos($contenidoVista, "lp_comparacion_resultado") !== false, "Existe panel de resultado de comparacion"),
    checkComparador("estado_comparacion", strpos($contenidoJs, "comparacion: null") !== false, "JS mantiene estado separado de comparacion"),
    checkComparador("funcion_comparar", strpos($contenidoJs, "function compararListaOrigen") !== false, "JS compara lista origen contra productos visibles"),
    checkComparador("funcion_render", strpos($contenidoJs, "function renderComparacionListas") !== false, "JS renderiza resumen y diferencias"),
    checkComparador("funcion_aplicar", strpos($contenidoJs, "function aplicarDiferenciasComparacion") !== false, "JS aplica diferencias revisadas"),
    checkComparador("solo_pendientes", strpos($contenidoJs, "Se aplicaron ") !== false && strpos($contenidoJs, "como cambios pendientes") !== false, "Aplicar diferencias no guarda directamente"),
    checkComparador("sin_endpoint_escritura", strpos($contenidoJs, "aplicarDiferenciasComparacion") !== false && substr_count(extraerFuncion($contenidoJs, "aplicarDiferenciasComparacion"), "postRequest(") === 0, "La aplicacion de diferencias no llama endpoints POST"),
    checkComparador("usa_endpoint_read", strpos($contenidoJs, "/comercial/listas_precios_productos_erp") !== false, "La comparacion consulta productos por endpoint read-only"),
    checkComparador("eventos_registrados", strpos($contenidoJs, "lp_comparar_lista") !== false && strpos($contenidoJs, "lp_aplicar_comparacion") !== false, "Los botones tienen listeners registrados")
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
    "resultado" => empty($bloqueos) ? "PASS_COMPARADOR_LISTAS" : "FAIL_COMPARADOR_LISTAS",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkComparador($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
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
