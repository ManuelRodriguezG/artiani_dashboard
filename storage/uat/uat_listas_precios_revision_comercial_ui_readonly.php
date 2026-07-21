<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar revision comercial local en la mesa de listas de precios.
 * Impacto: protege semaforo visible de perdida, margen bajo, sin costo y sin precio antes de activar.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";

$checks = array(
    checkRevisionUi("vista_revision", strpos($contenidoVista, "lp_revision") !== false, "La vista contiene panel de revision"),
    checkRevisionUi("funcion_revision_visible", strpos($contenidoJs, "function construirRevisionProductosVisibles") !== false, "JS calcula revision local de productos visibles"),
    checkRevisionUi("render_revision_visible", strpos($contenidoJs, "function renderRevisionProductosVisibles") !== false, "JS renderiza semaforo comercial local"),
    checkRevisionUi("metricas_revision", contieneTodos($contenidoJs, array("sin_precio", "sin_costo", "perdida", "margen_bajo", "margen_minimo")), "Revision local contempla metricas comerciales clave"),
    checkRevisionUi("revision_integrada", strpos($contenidoJs, "renderRevisionProductosVisibles(revisionProductos)") !== false, "Revision local se integra al panel de activacion"),
    checkRevisionUi("acciones_revision", strpos($contenidoJs, "data-lp-revision-filtro") !== false && strpos($contenidoJs, "function filtrarDesdeRevisionComercial") !== false, "Revision comercial ofrece filtros rapidos de pendientes"),
    checkRevisionUi("acciones_revision_activadas", strpos($contenidoJs, "function activarAccionesRevisionComercial") !== false && strpos($contenidoJs, "activarAccionesRevisionComercial()") !== false, "JS activa los botones de revision despues de renderizar"),
    checkRevisionUi("filtros_esperados", contieneTodos($contenidoJs, array("Ver sin precio", "Ver sin costo", "Ver perdida", "Ver margen bajo")), "Acciones rapidas cubren pendientes comerciales clave"),
    checkRevisionUi("sugeridos_pendientes_ui", strpos($contenidoVista, "lp_sugerir_pendientes") !== false && strpos($contenidoVista, "lp_usar_pendientes") !== false, "La mesa ofrece sugerir/usar solo pendientes comerciales"),
    checkRevisionUi("sugeridos_pendientes_js", strpos($contenidoJs, "function calcularSugeridosPendientes") !== false && strpos($contenidoJs, "function usarSugeridosPendientes") !== false, "JS calcula y aplica sugeridos solo para pendientes visibles"),
    checkRevisionUi("sugeridos_pendientes_sin_post", strpos(extraerFuncion($contenidoJs, "usarSugeridosPendientes"), "postRequest(") === false, "Usar sugeridos pendientes solo modifica pantalla"),
    checkRevisionUi("motivo_pendiente_fila", strpos($contenidoJs, "function motivoPendienteComercial") !== false && strpos($contenidoJs, "data-lp-pendiente-motivo") !== false, "Cada fila muestra motivo de pendiente comercial"),
    checkRevisionUi("sugerido_por_fila", strpos($contenidoJs, "data-lp-usar-sugerido-fila") !== false && strpos($contenidoJs, "function usarSugeridoFila") !== false, "Cada fila puede aplicar sugerido individual"),
    checkRevisionUi("sugerido_fila_sin_post", strpos(extraerFuncion($contenidoJs, "usarSugeridoFila"), "postRequest(") === false, "Aplicar sugerido por fila solo modifica pantalla"),
    checkRevisionUi("avisos_no_bloquean_click", strpos(extraerFuncion($contenidoJs, "cambiarEstatusLista"), "revisionLocal.avisos.length") === false, "Activar con avisos no queda bloqueado por avisos locales"),
    checkRevisionUi("bloqueos_si_detienen", strpos(extraerFuncion($contenidoJs, "cambiarEstatusLista"), "revisionLocal.bloqueos.length") !== false, "Bloqueos locales si detienen activacion")
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
    "resultado" => empty($bloqueos) ? "PASS_REVISION_COMERCIAL_UI" : "FAIL_REVISION_COMERCIAL_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkRevisionUi($id, $ok, $descripcion) {
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
