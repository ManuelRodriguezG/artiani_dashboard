<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar que la UI operativa de Listas de precios expone controles de segmentos CRM.
 * Impacto: evita regresar a una pantalla de prevalidacion despues de activar `erp_segmentos_listas_precios`.
 * Contrato: read-only; solo lee archivos locales, no carga MVC, no conecta MySQL ni modifica BD.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$bloqueos = array();

$checks = array(
    checkUi("vista_legible", is_readable($vista), "Vista de Listas de precios legible"),
    checkUi("js_legible", is_readable($js), "JS de Listas de precios legible")
);

$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks[] = checkUi("mensaje_segmentos_activo", strpos($contenidoVista, "Segmentos CRM activo") !== false, "La vista muestra segmentos como operativos");
$checks[] = checkUi("segmento_nombre_visible", strpos($contenidoVista, "lp_seg_nombre") !== false, "La captura usa nombre de segmento seleccionado");
$checks[] = checkUi("segmento_id_oculto", strpos($contenidoVista, "type=\"hidden\" id=\"lp_seg_id\"") !== false, "El id tecnico queda oculto en la captura principal");
$checks[] = checkUi("boton_nuevo_vinculo", strpos($contenidoVista, "lp_seg_nuevo") !== false, "Existe accion para limpiar nuevo vinculo");
$checks[] = checkUi("version_cache_operativa", strpos($contenidoVista, "20260718-segmentos-operativo1") !== false, "La vista fuerza version nueva del JS operativo");
$checks[] = checkUi("selector_segmento_por_nombre", strpos($contenidoJs, "function seleccionarSegmento") !== false, "JS selecciona segmento por nombre");
$checks[] = checkUi("acciones_rapidas_segmento", strpos($contenidoJs, "function cambiarEstatusSegmento") !== false, "JS permite pausar/activar/cancelar vinculos");
$checks[] = checkUi("acciones_sin_carga_cruzada", strpos($contenidoJs, "[data-lp-seg-cargar]") !== false, "JS separa cargar de acciones rapidas");

foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_UI_SEGMENTOS_OPERATIVA" : "FAIL_UI_SEGMENTOS_OPERATIVA",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkUi($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}
