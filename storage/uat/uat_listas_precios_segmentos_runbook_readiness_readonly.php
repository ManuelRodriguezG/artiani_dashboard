<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar preparacion documental y de scripts para activar listas por segmento CRM.
 * Impacto: permite revisar readiness antes de pedir autorizacion real.
 * Contrato: read-only; no carga MVC, no conecta MySQL, no ejecuta scripts apply_authorized.
 */

$raiz = realpath(__DIR__ . "/../..");
$respaldo = isset($argv[1]) ? trim((string) $argv[1]) : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql";

$archivos = array(
    "docs/erp_listas_precios_segmentos_runbook_activacion.md",
    "docs/erp_listas_precios_segmentos_schema_solicitud_autorizacion.md",
    "docs/erp_listas_precios_segmentos_catalogo_inicial.md",
    "docs/erp_listas_precios_segmentos_plan_reversa.md",
    "docs/erp_listas_precios_segmentos_guardrails_apply.md",
    "storage/uat/uat_listas_precios_segmentos_autorizacion_paquete_readonly.php",
    "storage/uat/uat_crm_segmentos_catalogo_apply_authorized.php",
    "storage/uat/uat_listas_precios_segmentos_schema_apply_authorized.php",
    "storage/uat/uat_listas_precios_segmento_vinculo_apply_authorized.php",
    "storage/uat/uat_crm_cliente_segmento_apply_authorized.php",
    "storage/uat/uat_listas_precios_segmento_cliente_puro_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_go_nogo_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_post_apply_acceptance_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_post_apply_suite_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_ventas_impacto_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_ventas_baseline_compare_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_suite_readonly.php",
    "storage/uat/uat_listas_precios_prioridad_resolutor_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_reversa_preflight_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_ui_readiness_readonly.php",
    "storage/uat/uat_listas_precios_segmentos_estatus_ui_dryrun_readonly.php",
    "storage/uat/uat_listas_precios_lote_dryrun_readonly.php",
    "storage/uat/uat_listas_precios_revision_activacion_readonly.php",
    "storage/uat/uat_listas_precios_auditoria_operativa_readonly.php",
    "storage/uat/uat_listas_precios_comparador_readonly.php",
    "storage/uat/uat_crm_segmentos_catalogo_ui_readiness_readonly.php",
    "storage/uat/uat_crm_segmentos_catalogo_estatus_dryrun_readonly.php"
);

$tokens = array(
    "CRM_CLIENTES_SEGMENTO_CATALOGO",
    "VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL",
    "VENTAS_LISTAS_PRECIOS_SEGMENTO_ASIGNAR_REAL",
    "CRM_CLIENTES_SEGMENTO"
);

$resultadoArchivos = array();
$faltantes = array();
foreach ($archivos as $relativo) {
    $ruta = $raiz . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $existe = file_exists($ruta);
    $legible = $existe && is_readable($ruta);
    $resultadoArchivos[] = array(
        "archivo" => $relativo,
        "existe" => $existe,
        "legible" => $legible,
        "tamano_bytes" => $existe ? filesize($ruta) : null
    );
    if (!$existe || !$legible) {
        $faltantes[] = $relativo;
    }
}

$runbook = $raiz . DIRECTORY_SEPARATOR . "docs" . DIRECTORY_SEPARATOR . "erp_listas_precios_segmentos_runbook_activacion.md";
$contenidoRunbook = is_readable($runbook) ? file_get_contents($runbook) : "";
$tokensRunbook = array();
$tokensFaltantes = array();
foreach ($tokens as $token) {
    $presente = strpos($contenidoRunbook, $token) !== false;
    $tokensRunbook[] = array("token" => $token, "documentado" => $presente);
    if (!$presente) {
        $tokensFaltantes[] = $token;
    }
}

$validacionRespaldo = validarRespaldoRunbookSegmentos($respaldo);

echo json_encode(array(
    "ok" => empty($faltantes) && empty($tokensFaltantes) && !empty($validacionRespaldo["ok"]),
    "modo" => "read-only",
    "raiz" => $raiz,
    "respaldo" => $validacionRespaldo,
    "archivos" => $resultadoArchivos,
    "faltantes" => $faltantes,
    "tokens_runbook" => $tokensRunbook,
    "tokens_faltantes" => $tokensFaltantes,
    "siguiente_preflight" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_segmentos_autorizacion_paquete_readonly.php",
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_ejecuta_apply" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function validarRespaldoRunbookSegmentos($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano_bytes" => $tamano
    );
}
