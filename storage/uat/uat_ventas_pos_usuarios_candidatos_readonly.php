<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: listar usuarios candidatos para operar POS sin mostrar datos sensibles.
 * Impacto: ayuda a elegir `id_usuario` para asignacion usuario/caja/terminal.
 * Contrato: read-only; no crea usuarios, no asigna roles y no escribe BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

$seguridad = new SeguridadPermisos();
$usuarios = $seguridad->listarUsuariosRoles();

$candidatos = array();
foreach (isset($usuarios["depurar"]) && is_array($usuarios["depurar"]) ? $usuarios["depurar"] : array() as $usuario) {
    $rolesTexto = isset($usuario["roles"]) ? (string) $usuario["roles"] : "";
    $roles = array_values(array_filter(array_map("trim", explode(",", $rolesTexto))));
    $idUsuario = intval($usuario["id_usuario"]);
    $estatus = intval($usuario["estatus"]);
    $tieneVentas = $seguridad->usuarioTienePermiso($idUsuario, "ventas.operar");
    $candidatos[] = array(
        "id_usuario" => $idUsuario,
        "nombre" => trim($usuario["nombres"] . " " . $usuario["apellido_paterno"] . " " . $usuario["apellido_materno"]),
        "estatus" => $estatus,
        "roles" => $roles,
        "puede_operar_pos" => $estatus === 1 && $tieneVentas,
        "recomendado_pos" => $estatus === 1 && $tieneVentas
    );
}

usort($candidatos, function ($a, $b) {
    if ($a["recomendado_pos"] !== $b["recomendado_pos"]) {
        return $a["recomendado_pos"] ? -1 : 1;
    }
    return $a["id_usuario"] - $b["id_usuario"];
});

echo json_encode(array(
    "ok" => !$usuarios["error"],
    "modo" => "read-only",
    "mensaje" => "Usuarios candidatos POS consultados",
    "total" => count($candidatos),
    "candidatos" => $candidatos,
    "nota" => "Usar un id_usuario con puede_operar_pos=true para las semillas de asignacion POS."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
