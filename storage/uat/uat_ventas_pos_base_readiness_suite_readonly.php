<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: ejecutar una suite read-only de preparacion para DDL base Ventas/POS.
 * Impacto: consolida validaciones previas a autorizacion sin escribir BD.
 * Contrato: no ejecuta DDL, no inserta semillas, no abre turnos y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$idUsuario = 1;
foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../..");

$checks = array(
    ejecutar("respaldo", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_respaldo_preflight_readonly.php --respaldo=" . escapeshellarg($respaldo)),
    ejecutar("paquete_base", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_paquete_autorizacion_dryrun.php --id_usuario=" . intval($idUsuario) . " --alcance=base"),
    ejecutar("preflight_base", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_base_autorizacion_preflight_readonly.php --respaldo=" . escapeshellarg($respaldo) . " --id_usuario=" . intval($idUsuario)),
    ejecutar("compatibilidad", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_base_compatibilidad_readonly.php"),
    ejecutar("guardrails", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_guardrails_readonly.php"),
    ejecutar("post_config_pre_ddl", "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_post_config_readonly.php --id_usuario=" . intval($idUsuario) . " --alcance=base")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["fase"] . ": " . $check["mensaje"];
    }
}

$paquete = buscarCheck($checks, "paquete_base");
$preflight = buscarCheck($checks, "preflight_base");
$compatibilidad = buscarCheck($checks, "compatibilidad");
$guardrails = buscarCheck($checks, "guardrails");
$postConfig = buscarCheck($checks, "post_config_pre_ddl");

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "alcance" => "base",
    "id_usuario" => $idUsuario,
    "respaldo" => $respaldo,
    "resumen" => array(
        "ddl_base_total" => valor($paquete, array("json", "ddl_total"), null),
        "seed_cajas_total" => valor($paquete, array("json", "seed_cajas_total"), null),
        "seed_terminales_total" => valor($paquete, array("json", "seed_terminales_total"), null),
        "seed_asignaciones_total" => valor($paquete, array("json", "seed_asignaciones_total"), null),
        "preflight_bloqueos" => valor($preflight, array("json", "bloqueos"), array()),
        "compatibilidad_bloqueos" => valor($compatibilidad, array("json", "bloqueos"), array()),
        "guardrails_fallas" => valor($guardrails, array("json", "fallas"), array()),
        "tablas_base_pendientes_pre_ddl" => valor($postConfig, array("json", "tablas_pendientes"), array())
    ),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "siguiente_paso" => empty($bloqueos)
        ? "Readiness base completo. Siguiente paso requiere autorizacion VENTAS_POS_DDL_BASE."
        : "Resolver bloqueos antes de solicitar autorizacion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function ejecutar($fase, $comando) {
    $salida = array();
    $codigo = 0;
    exec($comando, $salida, $codigo);
    $texto = implode("\n", $salida);
    $json = json_decode($texto, true);
    $okJson = is_array($json);
    $ok = $codigo === 0 && $okJson && !empty($json["ok"]);
    return array(
        "fase" => $fase,
        "ok" => $ok,
        "exit_code" => $codigo,
        "mensaje" => $okJson && isset($json["mensaje"]) ? $json["mensaje"] : ($ok ? "ok" : "Salida no valida o check fallido"),
        "json" => $okJson ? $json : null
    );
}

function buscarCheck($checks, $fase) {
    foreach ($checks as $check) {
        if ($check["fase"] === $fase) {
            return $check;
        }
    }
    return array();
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
