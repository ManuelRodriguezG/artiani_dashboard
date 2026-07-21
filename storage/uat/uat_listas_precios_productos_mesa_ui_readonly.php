<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar ergonomia de la mesa de productos/precios en Comercial > Listas de precios.
 * Impacto: protege captura por lote, resumen de cambios pendientes y tabla escaneable.
 * Contrato: read-only; solo lee archivos locales, no carga MVC ni conecta MySQL.
 */

$raiz = dirname(__DIR__, 2);
$vista = $raiz . DIRECTORY_SEPARATOR . "app/vistas/paginas/apps/erp/ventas/listas_precios.php";
$js = $raiz . DIRECTORY_SEPARATOR . "public/assets/js/custom/apps/erp/ventas/listas_precios.js";
$contenidoVista = is_readable($vista) ? file_get_contents($vista) : "";
$contenidoJs = is_readable($js) ? file_get_contents($js) : "";

$checks = array(
    checkMesa("tabla_sticky", strpos($contenidoVista, ".lp-productos thead th") !== false && strpos($contenidoVista, "position: sticky") !== false, "La tabla mantiene encabezado visible al hacer scroll"),
    checkMesa("barra_cambios", strpos($contenidoVista, "lp_cambios_barra") !== false && strpos($contenidoJs, "function renderBarraCambios") !== false, "La mesa muestra barra de cambios pendientes"),
    checkMesa("acciones_cambios_top", contieneTodos($contenidoVista, array("lp_ver_modificados", "lp_prevalidar_cambios_top", "lp_limpiar_cambios_top")), "La barra tiene acciones directas para modificados, prevalidar y limpiar"),
    checkMesa("acciones_conectadas", contieneTodos($contenidoJs, array("verProductosModificados", "lp_prevalidar_cambios_top", "lp_limpiar_cambios_top")), "JS conecta acciones superiores con funciones existentes"),
    checkMesa("riesgo_visible", contieneTodos($contenidoJs, array("perdida", "margenBajo", "sinCosto", "renderBarraCambios(productos.length")), "El resumen considera perdida, margen bajo y sin costo")
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
    "resultado" => empty($bloqueos) ? "PASS_PRODUCTOS_MESA_UI" : "FAIL_PRODUCTOS_MESA_UI",
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkMesa($id, $ok, $descripcion) {
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
