<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar separacion UI de Listas de precios en listado, nueva y editar.
 * Impacto: evita volver a mezclar consulta general con editor operativo en una sola experiencia confusa.
 * Contrato: read-only; no carga MVC, no conecta MySQL, no escribe BD ni archivos.
 */

$raiz = dirname(__DIR__, 2);
$controlador = leerEstructura($raiz, "app/controladores/Comercial.php");
$inicio = leerEstructura($raiz, "app/vistas/paginas/apps/erp/ventas/listas_precios_inicio.php");
$editor = leerEstructura($raiz, "app/vistas/paginas/apps/erp/ventas/listas_precios.php");
$jsInicio = leerEstructura($raiz, "public/assets/js/custom/apps/erp/ventas/listas_precios_inicio.js");
$jsEditor = leerEstructura($raiz, "public/assets/js/custom/apps/erp/ventas/listas_precios.js");
$sidebar = leerEstructura($raiz, "app/vistas/includes/header/sidebar.php");
$plan = leerEstructura($raiz, "docs/erp_listas_precios_plan.md");

$checks = array(
    checkEstructura("ruta_listado", contieneEstructura($controlador, array("function listas_precios()", "listas_precios_inicio")), "Ruta principal carga listado/portada"),
    checkEstructura("ruta_nueva", contieneEstructura($controlador, array("function listas_precios_nueva", "ventas.listas.crear", "modo_editor")), "Ruta nueva carga editor con permiso de crear"),
    checkEstructura("ruta_editar", contieneEstructura($controlador, array("function listas_precios_editar", "id_lista_precio", "modo_editor")), "Ruta editar carga editor por id"),
    checkEstructura("vista_inicio", contieneEstructura($inicio, array("Listado de listas", "Crear nueva lista", "lp_inicio_listas", "listas_precios_inicio.js")), "Existe portada clara de listas"),
    checkEstructura("js_inicio", contieneEstructura($jsInicio, array("listas_precios_resumen_erp", "listas_precios_fase1_readiness_erp", "listas_precios_editar?id_lista_precio")), "JS del listado consulta y manda a editar"),
    checkEstructura("editor_regreso", contieneEstructura($editor, array("Editor de lista de precios", "/comercial/listas_precios", "Listado")), "Editor tiene regreso al listado"),
    checkEstructura("editor_url_id", contieneEstructura($jsEditor, array("id_lista_precio", "consultarLista(idInicial)", "nuevaLista()")), "Editor abre lista inicial desde URL"),
    checkEstructura("editor_tabs_vista", contieneEstructura($editor, array("Seccion de trabajo", "data-lp-editor-tab=\"encabezado\"", "data-lp-editor-tab=\"productos\"", "data-lp-editor-panel=\"asignacion\"")), "Editor separa trabajo en pestanas internas"),
    checkEstructura("editor_tabs_js", contieneEstructura($jsEditor, array("activarTabsEditor", "cambiarTabEditor", "data-lp-editor-panel", "tabDesdePasoFlujo")), "JS muestra una seccion del editor a la vez"),
    checkEstructura("productos_subtabs_vista", contieneEstructura($editor, array("data-lp-product-tab=\"captura\"", "data-lp-product-tab=\"herramientas\"", "data-lp-product-tab=\"importar\"", "data-lp-product-tab=\"prevalidacion\"")), "Productos se divide en captura, herramientas, importar y prevalidacion"),
    checkEstructura("productos_subtabs_js", contieneEstructura($jsEditor, array("activarTabsProductos", "cambiarTabProducto", "data-lp-product-panel", "cambiarTabProducto(\"prevalidacion\")")), "JS controla subpestanas de productos"),
    checkEstructura("productos_granel_vista", contieneEstructura($editor, array("Para granel", "precio capturado aqui es por unidad base", "SKU/presentacion")), "Editor explica precio por unidad base contra presentacion fisica"),
    checkEstructura("productos_granel_modelo", contieneEstructura(leerEstructura($raiz, "app/modelos/ListasPreciosErp.php"), array("erp_catalogo_skus_reglas", "permite_venta_fraccionaria", "incremento_minimo_venta", "precision_decimal")), "Listado de productos expone reglas de granel de Catalogo"),
    checkEstructura("productos_botones_visibles", contieneEstructura($jsEditor, array("Previsualizar precio POS", "cambiarTabEditor(\"revision\")", "enfocarElemento(\"lp_preview_resultado\")", "enfocarElemento(\"lp_auditoria\")")), "Botones de calculadora e historial llevan al resultado visible"),
    checkEstructura("productos_costo_fallback", contieneEstructura(leerEstructura($raiz, "app/modelos/ListasPreciosErp.php"), array("resolverCostoComercialFila", "costo_promedio_inventario", "costo_proveedor", "costo_ultima_compra", "costoComercialDetalle")), "Productos resuelve costo por inventario, proveedor, ultima compra o catalogo fallback"),
    checkEstructura("productos_costo_fuente_ui", contieneEstructura($jsEditor, array("costoComercialHtml", "costo_fuente_label", "costo_fuente")), "UI muestra fuente del costo usado para margen"),
    checkEstructura("productos_preserva_cambios", contieneEstructura($jsEditor, array("cargarProductos(opciones)", "cambioPendiente", "precioOriginal", "limpiarCambiosGuardados")), "Guardar fila/lote conserva cambios pendientes no guardados"),
    checkEstructura("productos_solo_activos_operativos", contieneEstructura(leerEstructura($raiz, "app/modelos/ListasPreciosErp.php"), array("p.estatus='activo'", "productoPrecioOperativo", "estatus='activo'")), "Lista solo muestra/permite productos maestros activos con SKU activo"),
    checkEstructura("productos_paginacion_visible", contieneEstructura($editor, array("lp_producto_por_pagina", "lp_productos_paginacion", "lp_productos_siguiente", "lp_res_total")), "UI pagina productos y muestra total disponible"),
    checkEstructura("productos_total_backend", contieneEstructura(leerEstructura($raiz, "app/modelos/ListasPreciosErp.php"), array("total_disponible", "COUNT(*) total", "total_paginas", "por_pagina")), "Backend devuelve total disponible y paginacion"),
    checkEstructura("productos_guardado_masivo_visible", contieneEstructura($editor, array("lp_guardar_cambios_top", "Guardar cambios")) && contieneEstructura($jsEditor, array("lp_guardar_cambios_top", "guardarCambiosLote", "productosPorSku", "lp_lote_guardar_validado", "aplicarLotePrevalidado")), "Captura muestra guardado masivo de cambios modificados aunque el operador navegue paginas"),
    checkEstructura("productos_lote_amplio", contieneEstructura(leerEstructura($raiz, "app/modelos/ListasPreciosErp.php"), array("1000 precios por guardado", "1000 precios por prevalidacion")), "Backend permite lotes operativos mayores a 200 precios"),
    checkEstructura("sidebar_apunta_listado", contieneEstructura($sidebar, array("'Listas de precios'", "'/comercial/listas_precios'")), "Sidebar apunta al listado principal"),
    checkEstructura("plan_documenta", contieneEstructura($plan, array("Estructura UI 2026-07-20", "listas_precios_nueva", "listas_precios_editar")), "Plan vivo documenta separacion UI")
);

$fallos = array_values(array_filter($checks, function ($check) {
    return !$check["ok"];
}));

echo json_encode(array(
    "ok" => empty($fallos),
    "modo" => "read-only",
    "resultado" => empty($fallos) ? "PASS_LISTAS_PRECIOS_ESTRUCTURA_UI" : "FAIL_LISTAS_PRECIOS_ESTRUCTURA_UI",
    "checks" => $checks,
    "fallos" => $fallos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit(empty($fallos) ? 0 : 1);

function leerEstructura($raiz, $ruta) {
    $archivo = $raiz . DIRECTORY_SEPARATOR . str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $ruta);
    return is_readable($archivo) ? file_get_contents($archivo) : "";
}

function contieneEstructura($texto, $tokens) {
    foreach ($tokens as $token) {
        if (strpos($texto, $token) === false) {
            return false;
        }
    }
    return true;
}

function checkEstructura($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}
