<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar entrega fase 1 de Comercial/Listas de precios sin cargar MVC ni escribir BD.
 * Impacto: confirma que existe semaforo operativo para piloto POS, manual y contrato de pendientes fase 2.
 * Contrato: read-only; no conecta MySQL, no crea listas, no toca ventas ni ecommerce.
 */

$root = dirname(__DIR__, 2);
$checks = array();

function lpFase1Check(&$checks, $id, $ok, $descripcion) {
    $checks[] = array(
        "id" => $id,
        "ok" => (bool) $ok,
        "descripcion" => $descripcion
    );
}

function lpFase1Contenido($root, $ruta) {
    $archivo = $root . DIRECTORY_SEPARATOR . str_replace(array("/", "\\"), DIRECTORY_SEPARATOR, $ruta);
    return is_file($archivo) && is_readable($archivo) ? file_get_contents($archivo) : false;
}

function lpFase1Tiene($contenido, $tokens) {
    if ($contenido === false) {
        return false;
    }
    foreach ($tokens as $token) {
        if (strpos($contenido, $token) === false) {
            return false;
        }
    }
    return true;
}

$controlador = lpFase1Contenido($root, "app/controladores/Comercial.php");
$modelo = lpFase1Contenido($root, "app/modelos/ListasPreciosErp.php");
$vista = lpFase1Contenido($root, "app/vistas/paginas/apps/erp/ventas/listas_precios.php");
$js = lpFase1Contenido($root, "public/assets/js/custom/apps/erp/ventas/listas_precios.js");
$manual = lpFase1Contenido($root, "app/vistas/paginas/apps/erp/ventas/listas_precios_manual.php");
$plan = lpFase1Contenido($root, "docs/erp_listas_precios_plan.md");

lpFase1Check($checks, "ruta_endpoint", lpFase1Tiene($controlador, array("listas_precios_fase1_readiness_erp", "ventas.listas.ver", "fase1ReadinessReadOnly")), "Comercial expone endpoint read-only protegido");
lpFase1Check($checks, "modelo_readiness", lpFase1Tiene($modelo, array("fase1ReadinessReadOnly", "puede_piloto_pos", "puede_ecommerce", "siguiente_uat")), "Modelo calcula readiness fase 1");
lpFase1Check($checks, "checks_base", lpFase1Tiene($modelo, array("tablas_base", "auditoria_comercial", "snapshot_venta", "resolutor_backend", "mesa_operativa")), "Readiness valida base, auditoria, snapshot, resolutor y mesa");
lpFase1Check($checks, "fase2_no_bloquea", lpFase1Tiene($modelo, array("ecommerce_contrato", "granel_presentaciones", "pendientes_fase_2")), "Ecommerce y granel quedan como fase 2 documentada");
lpFase1Check($checks, "vista_panel", lpFase1Tiene($vista, array("Arranque fase 1", "lp_fase1_readiness", "lp_fase1_recargar")), "Vista muestra panel de arranque fase 1");
lpFase1Check($checks, "js_render", lpFase1Tiene($js, array("cargarFase1Readiness", "renderFase1Readiness", "/comercial/listas_precios_fase1_readiness_erp")), "JS consume y renderiza semaforo");
lpFase1Check($checks, "manual_existe", lpFase1Tiene($manual, array("Manual de Listas de precios", "Listo para piloto POS", "UAT minimo")), "Manual operativo sigue disponible");
lpFase1Check($checks, "plan_documentado", lpFase1Tiene($plan, array("Semaforo de entrega 2026-07-20", "piloto POS controlado")), "Plan vivo documenta entrega fase 1");

$fallos = array_values(array_filter($checks, function ($check) {
    return !$check["ok"];
}));

$resultado = array(
    "ok" => empty($fallos),
    "modo" => "read-only",
    "resultado" => empty($fallos) ? "PASS_FASE1_ENTREGABLE_LISTAS_PRECIOS" : "FAIL_FASE1_ENTREGABLE_LISTAS_PRECIOS",
    "checks" => $checks,
    "fallos" => $fallos,
    "guardrails" => array(
        "no_carga_mvc" => true,
        "no_conecta_mysql" => true,
        "no_escribe_bd" => true,
        "no_modifica_archivos" => true
    )
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(empty($fallos) ? 0 : 1);
