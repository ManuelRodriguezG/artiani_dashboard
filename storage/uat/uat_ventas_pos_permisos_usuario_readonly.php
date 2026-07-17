<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: auditar permisos minimos de un usuario para operar POS sin modificar roles ni permisos.
 * Impacto: ayuda a validar pase a prueba real por usuario/caja/tienda.
 * Contrato: read-only; no asigna roles, no crea permisos y no cambia seguridad.
 */

$idUsuario = 1;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

if ($idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_usuario=ID"
    ));
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

$seguridad = new SeguridadPermisos();
$autorizacion = $seguridad->autorizacionUsuario($idUsuario);
$permisos = isset($autorizacion["permisos"]) && is_array($autorizacion["permisos"]) ? $autorizacion["permisos"] : array();

$requeridosOperacion = array(
    "ventas.ver",
    "ventas.operar"
);
$requeridosConfiguracion = array(
    "ventas.pos_config.ver"
);
$sensibles = array(
    "ventas.precio_manual",
    "ventas.descuento_partida",
    "ventas.descuento_general",
    "ventas.autorizar_excepcion_comercial",
    "ventas.caja_diferencias.ver",
    "ventas.caja_diferencias.revisar",
    "ventas.caja_diferencias.resolver",
    "ventas.pos_config.crear",
    "ventas.pos_config.editar",
    "ventas.pos_config.desactivar",
    "ventas.pos_config.asignar_usuario",
    "crm.crear"
);

$faltantesOperacion = faltantes($requeridosOperacion, $permisos);
$faltantesConfiguracion = faltantes($requeridosConfiguracion, $permisos);
$sensiblesPresentes = presentes($sensibles, $permisos);

responder(array(
    "ok" => empty($faltantesOperacion),
    "modo" => "ventas_pos_permisos_usuario_readonly",
    "host" => "panel.com.local",
    "id_usuario" => $idUsuario,
    "roles" => isset($autorizacion["roles"]) ? $autorizacion["roles"] : array(),
    "resumen" => array(
        "puede_operar_pos" => empty($faltantesOperacion),
        "faltantes_operacion" => $faltantesOperacion,
        "faltantes_configuracion" => $faltantesConfiguracion,
        "permisos_sensibles_presentes" => $sensiblesPresentes,
        "total_permisos" => count($permisos)
    ),
    "contrato" => array(
        "read_only" => true,
        "no_modifica_roles" => true,
        "no_modifica_permisos" => true
    )
));

function faltantes($esperados, $permisos) {
    return array_values(array_diff($esperados, $permisos));
}

function presentes($esperados, $permisos) {
    return array_values(array_intersect($esperados, $permisos));
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
