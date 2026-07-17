<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar dependencias PHP del ciclo real de atencion POS multiusuario antes de pedir autorizacion.
 * Impacto: reduce riesgo de interrumpir una UAT real por scripts faltantes o sintaxis invalida.
 * Contrato: read-only; no consulta BD, no abre turno, no carga stock, no cobra y no cierra caja.
 */

$scripts = array(
    "semaforo_cierre" => "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "turno_apertura" => "uat_ventas_pos_turno_apertura_apply_authorized.php",
    "stock_uat" => "uat_ventas_pos_stock_uat_apply_authorized.php",
    "atencion_cobro" => "uat_ventas_pos_atencion_cobro_apply_authorized.php",
    "post_conversion" => "uat_ventas_pos_atencion_conversion_post_readonly.php",
    "ticket_formal" => "uat_ventas_pos_ticket_formal_readonly.php",
    "post_venta" => "uat_ventas_pos_post_venta_readonly.php",
    "turno_cierre" => "uat_ventas_pos_turno_cierre_apply_authorized.php",
    "ciclo_evidencia" => "uat_ventas_pos_ciclo_evidencia_readonly.php",
    "ciclo_recuperacion" => "uat_ventas_pos_ciclo_recuperacion_readonly.php",
    "suite_pase_real" => "uat_ventas_pos_pase_prueba_real_suite_readonly.php"
);

$resultados = array();
$bloqueos = array();

foreach ($scripts as $clave => $script) {
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    $existe = is_file($ruta);
    $legible = $existe && is_readable($ruta);
    $sintaxis = null;
    $salida = "";
    $exitCode = null;

    if ($legible) {
        $cmd = escapeshellarg(PHP_BINARY) . " -l " . escapeshellarg($ruta);
        $lineas = array();
        $codigo = 0;
        exec($cmd, $lineas, $codigo);
        $exitCode = $codigo;
        $salida = implode("\n", $lineas);
        $sintaxis = $codigo === 0;
    }

    if (!$existe) {
        $bloqueos[] = "Falta script requerido: " . $script;
    } elseif (!$legible) {
        $bloqueos[] = "Script requerido no es legible: " . $script;
    } elseif (!$sintaxis) {
        $bloqueos[] = "Script requerido con sintaxis invalida: " . $script;
    }

    $resultados[] = array(
        "clave" => $clave,
        "script" => $script,
        "existe" => $existe,
        "legible" => $legible,
        "sintaxis_php_ok" => $sintaxis,
        "php_lint_exit_code" => $exitCode,
        "php_lint_salida" => $salida
    );
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ciclo_dependencias_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "resumen" => array(
        "scripts_revisados" => count($resultados),
        "bloqueos" => $bloqueos,
        "listo_dependencias_ciclo_real" => empty($bloqueos)
    ),
    "dependencias" => $resultados,
    "contrato" => array(
        "read_only" => true,
        "no_consulta_bd" => true,
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_cobra" => true,
        "no_cierra_turno" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);
