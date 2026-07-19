<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar que CRM expone UI operativa para administrar tipos/segmentos de cliente.
 * Impacto: conecta CRM Segmentos con Listas de precios sin depender de scripts para operar catalogo.
 * Contrato: read-only; solo lee vista/JS locales, no carga MVC, no conecta MySQL ni escribe BD.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/crm/clientes/listado.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/crm/clientes/listado.js";
$bloqueos = array();

$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkCrmSegUi("vista_legible", is_readable($vista), "Vista CRM Clientes legible"),
    checkCrmSegUi("js_legible", is_readable($js), "JS CRM Clientes legible"),
    checkCrmSegUi("titulo_tipos_cliente", strpos($contenidoVista, "Tipos de cliente") !== false, "La seccion usa lenguaje operativo"),
    checkCrmSegUi("contexto_listas_precios", strpos($contenidoVista, "Comercial/Listas") !== false, "La vista explica relacion con Listas de precios"),
    checkCrmSegUi("boton_nuevo", strpos($contenidoVista, "crm_seg_nuevo") !== false, "Existe boton Nuevo para limpiar formulario"),
    checkCrmSegUi("version_cache_operativa", strpos($contenidoVista, "20260718-segmentos-operativo2") !== false, "La vista fuerza version nueva del JS"),
    checkCrmSegUi("csrf_post", strpos($contenidoJs, "X-CSRF-Token") !== false, "POST CRM envia CSRF"),
    checkCrmSegUi("acciones_rapidas", strpos($contenidoJs, "data-crm-seg-estatus-rapido") !== false, "JS expone pausar/activar/cancelar en UI"),
    checkCrmSegUi("limpiar_formulario", strpos($contenidoJs, "function limpiarSegmentoCatalogo") !== false, "JS permite nuevo segmento sin arrastrar datos previos"),
    checkCrmSegUi("carga_desde_boton", strpos($contenidoJs, "function cargarSegmentoCatalogoDesdeBoton") !== false, "JS carga segmento desde tabla de forma controlada")
);

foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_CRM_SEGMENTOS_UI" : "FAIL_CRM_SEGMENTOS_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkCrmSegUi($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}
