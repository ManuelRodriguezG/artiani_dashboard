<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar que la UI operativa de Listas de precios expone segmentos CRM y edicion masiva segura.
 * Impacto: evita regresar a una pantalla de prevalidacion y protege acciones masivas de precios por SKU.
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
$checks[] = checkUi("segmentos_camino_recomendado", strpos($contenidoVista, "lp_segmentos_bloque") !== false && strpos($contenidoVista, "recomendado") !== false, "La vista separa segmentos como camino recomendado");
$checks[] = checkUi("cliente_directo_excepcion", strpos($contenidoVista, "lp_clientes_excepcion_bloque") !== false && strpos($contenidoVista, "Excepcion por cliente") !== false, "La vista separa cliente directo como excepcion comercial");
$checks[] = checkUi("segmento_nombre_visible", strpos($contenidoVista, "lp_seg_nombre") !== false, "La captura usa nombre de segmento seleccionado");
$checks[] = checkUi("segmento_id_oculto", strpos($contenidoVista, "type=\"hidden\" id=\"lp_seg_id\"") !== false, "El id tecnico queda oculto en la captura principal");
$checks[] = checkUi("boton_nuevo_vinculo", strpos($contenidoVista, "lp_seg_nuevo") !== false, "Existe accion para limpiar nuevo vinculo");
$checks[] = checkUi("version_cache_operativa", strpos($contenidoVista, "20260719-flujo-guiado1") !== false, "La vista fuerza version nueva del JS operativo");
$checks[] = checkUi("flujo_guiado_operativo", strpos($contenidoVista, "lp_flujo_operativo") !== false && strpos($contenidoJs, "function actualizarFlujoOperativo") !== false, "La vista guia la creacion de listas por pasos operativos");
$checks[] = checkUi("alcance_guia_prioridad", strpos($contenidoVista, "lp_prioridad_guia") !== false && strpos($contenidoVista, "Cliente directo") !== false && strpos($contenidoJs, "function actualizarGuiaPrioridad") !== false, "La vista explica alcance y prioridad del resolutor");
$checks[] = checkUi("selector_segmento_por_nombre", strpos($contenidoJs, "function seleccionarSegmento") !== false, "JS selecciona segmento por nombre");
$checks[] = checkUi("acciones_rapidas_segmento", strpos($contenidoJs, "function cambiarEstatusSegmento") !== false, "JS permite pausar/activar/cancelar vinculos");
$checks[] = checkUi("acciones_sin_carga_cruzada", strpos($contenidoJs, "[data-lp-seg-cargar]") !== false, "JS separa cargar de acciones rapidas");
$checks[] = checkUi("seleccion_productos_visible", strpos($contenidoVista, "lp_productos_select_all") !== false && strpos($contenidoVista, "lp_res_seleccionados") !== false, "La vista permite seleccionar SKUs visibles");
$checks[] = checkUi("alcance_acciones_masivas", strpos($contenidoVista, "lp_accion_alcance") !== false, "La vista permite elegir seleccionados o visibles para acciones masivas");
$checks[] = checkUi("estado_seleccionados", strpos($contenidoJs, "seleccionados") !== false, "JS mantiene seleccion de productos");
$checks[] = checkUi("acciones_masivas_seguras", strpos($contenidoJs, "function validarProductosAccionMasiva") !== false && substr_count($contenidoJs, "validarProductosAccionMasiva()") >= 4, "JS valida alcance antes de aplicar margen, copiar o redondear");
$checks[] = checkUi("seleccionar_visibles", strpos($contenidoJs, "function seleccionarProductosVisibles") !== false, "JS permite seleccionar o deseleccionar todos los visibles");
$checks[] = checkUi("mesa_productos_cambios", strpos($contenidoVista, "lp_cambios_barra") !== false && strpos($contenidoJs, "function renderBarraCambios") !== false, "La mesa muestra resumen accionable de cambios pendientes");
$checks[] = checkUi("precios_sugeridos_ui", strpos($contenidoVista, "lp_sugerir_margen") !== false && strpos($contenidoVista, "lp_usar_sugeridos") !== false, "La vista separa calcular sugeridos de aplicarlos");
$checks[] = checkUi("precios_sugeridos_js", strpos($contenidoJs, "function calcularPreciosSugeridos") !== false && strpos($contenidoJs, "function usarPreciosSugeridos") !== false, "JS calcula sugeridos sin guardar y los aplica solo como cambios pendientes");
$checks[] = checkUi("export_csv_productos", strpos($contenidoVista, "lp_exportar_csv") !== false && strpos($contenidoJs, "function exportarProductosCsv") !== false, "La mesa permite exportar productos visibles a CSV sin escribir BD");
$checks[] = checkUi("import_csv_prevalidacion", strpos($contenidoVista, "lp_importar_csv") !== false && strpos($contenidoJs, "function prevalidarImportacionCsv") !== false, "La mesa permite prevalidar CSV contra productos visibles");
$checks[] = checkUi("import_csv_pendientes", strpos($contenidoJs, "function aplicarImportacionCsv") !== false && strpos($contenidoJs, "Importacion aplicada como cambios pendientes") !== false, "La importacion solo se aplica como cambios pendientes");
$checks[] = checkUi("prevalidacion_backend_lote", strpos($contenidoJs, "listas_precios_detalles_lote_dryrun_erp") !== false && strpos($contenidoJs, "function prevalidarCambiosLoteBackend") !== false, "El guardado masivo prevalida en backend antes de persistir");
$checks[] = checkUi("prevalidacion_manual_lote", strpos($contenidoVista, "lp_prevalidar_cambios") !== false && strpos($contenidoJs, "function prevalidarCambiosPendientes") !== false, "La UI permite prevalidar lote sin guardar");
$checks[] = checkUi("prevalidacion_lote_visible", strpos($contenidoVista, "lp_lote_prevalidacion") !== false && strpos($contenidoJs, "function renderPrevalidacionLote") !== false, "La prevalidacion de lote muestra resumen visible antes de guardar");
$checks[] = checkUi("prevalidacion_lote_bloquea_perdida", strpos($contenidoJs, "El lote tiene precios con perdida") !== false && strpos($contenidoJs, "window.confirm") !== false, "La UI bloquea perdidas y confirma avisos antes de guardar lote");
$checks[] = checkUi("resultado_guardado_lote", strpos($contenidoJs, "function renderResultadoGuardadoLote") !== false && strpos($contenidoJs, "Primeros guardados") !== false, "La UI muestra resultado por SKU despues de guardar lote");
$checks[] = checkUi("revision_local_activacion", strpos($contenidoJs, "function revisionLocalPantalla") !== false && strpos($contenidoJs, "cambio(s) de precio sin guardar") !== false, "La activacion considera pendientes locales de pantalla");
$checks[] = checkUi("auditoria_operativa", strpos($contenidoVista, "lp_auditoria_tipo") !== false && strpos($contenidoJs, "function badgeAuditoria") !== false, "La auditoria visible tiene filtros y badges operativos");
$checks[] = checkUi("historial_sku", strpos($contenidoJs, "function cargarAuditoriaSku") !== false && strpos($contenidoJs, "data-lp-historial-detalle") !== false, "La mesa permite abrir historial por SKU/detalle");
$checks[] = checkUi("comparador_listas_ui", strpos($contenidoVista, "lp_comparar_lista") !== false && strpos($contenidoVista, "lp_comparacion_resultado") !== false, "La vista permite comparar contra una lista origen antes de copiar");
$checks[] = checkUi("comparador_listas_js", strpos($contenidoJs, "function compararListaOrigen") !== false && strpos($contenidoJs, "function renderComparacionListas") !== false, "JS calcula y muestra diferencias entre lista origen y lista actual");
$checks[] = checkUi("comparador_aplica_pendientes", strpos($contenidoJs, "function aplicarDiferenciasComparacion") !== false && strpos($contenidoJs, "como cambios pendientes") !== false, "Las diferencias comparadas solo se aplican como cambios pendientes");
$checks[] = checkUi("filtros_margen_operativo_ui", strpos($contenidoVista, "lp_margen_minimo") !== false && strpos($contenidoVista, "value=\"perdida\"") !== false && strpos($contenidoVista, "value=\"sin_costo\"") !== false, "La vista separa perdida, sin costo y umbral de margen");
$checks[] = checkUi("filtros_margen_operativo_js", strpos($contenidoJs, "function margenMinimoOperativo") !== false && strpos($contenidoJs, "margen_minimo") !== false && strpos($contenidoJs, "lp_res_perdida") !== false, "JS usa margen minimo configurable y resume perdida/sin precio");
$checks[] = checkUi("revision_comercial_visible", strpos($contenidoJs, "function construirRevisionProductosVisibles") !== false && strpos($contenidoJs, "function renderRevisionProductosVisibles") !== false, "La revision muestra semaforo comercial de productos visibles");
$checks[] = checkUi("revision_comercial_accionable", strpos($contenidoJs, "data-lp-revision-filtro") !== false && strpos($contenidoJs, "function filtrarDesdeRevisionComercial") !== false, "La revision permite saltar a filtros de pendientes comerciales");
$checks[] = checkUi("sugeridos_pendientes", strpos($contenidoVista, "lp_sugerir_pendientes") !== false && strpos($contenidoJs, "function usarSugeridosPendientes") !== false, "La mesa permite sugerir y usar precios solo para pendientes visibles");
$checks[] = checkUi("edicion_fila_asistida", strpos($contenidoJs, "data-lp-pendiente-motivo") !== false && strpos($contenidoJs, "data-lp-usar-sugerido-fila") !== false, "Cada fila muestra motivo y permite aplicar sugerido individual");

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
