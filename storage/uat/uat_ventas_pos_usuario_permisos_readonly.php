<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: auditar permisos puntuales de un usuario POS sin escribir BD.
 * Impacto: valida cierres de permisos finos antes de retirar compatibilidades temporales.
 * Contrato: read-only; no asigna roles ni permisos.
 */

$opciones = getopt("", array("id_usuario::", "permisos::"));
$idUsuario = isset($opciones["id_usuario"]) ? intval($opciones["id_usuario"]) : 1;
$permisosArg = isset($opciones["permisos"]) ? trim((string) $opciones["permisos"]) : "";
$permisos = $permisosArg !== ""
    ? array_filter(array_map("trim", explode(",", $permisosArg)))
    : array(
        "ventas.caja_diferencias.ver",
        "ventas.caja_diferencias.revisar",
        "ventas.caja_diferencias.resolver",
        "ventas.pos_config.ver",
        "ventas.pos_config.crear",
        "ventas.pos_config.editar",
        "ventas.pos_config.desactivar",
        "ventas.pos_config.asignar_usuario",
        "ventas.operar"
    );

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

$seguridad = new SeguridadPermisos();
$resultado = array();
foreach ($permisos as $permiso) {
    $resultado[$permiso] = $seguridad->usuarioTienePermiso($idUsuario, $permiso);
}

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_usuario_permisos_readonly",
    "read_only" => true,
    "id_usuario" => $idUsuario,
    "permisos" => $resultado,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_asigna_roles" => true,
        "no_asigna_permisos" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
