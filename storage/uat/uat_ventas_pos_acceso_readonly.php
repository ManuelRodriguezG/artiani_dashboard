<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: diagnosticar acceso visible al POS por usuario/rol/permiso.
 * Impacto: solo lectura; no asigna permisos, no modifica roles y no cambia menu.
 * Contrato: recibe --id_usuario opcional y reporta permisos ventas.ver/ventas.operar.
 */

$idUsuario = 0;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosAccesoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosAccesoDb())->db();
$permisosVentas = consultar($db, "SELECT id_permiso, permiso, modulo, accion, descripcion, estatus
    FROM sys_permisos
    WHERE permiso IN ('ventas.ver','ventas.operar')
    ORDER BY permiso", array());

$usuariosSql = "SELECT su.id_usuario, su.nombres, su.apellido_paterno, su.apellido_materno, su.estatus,
        GROUP_CONCAT(DISTINCT sr.rol ORDER BY sr.rol SEPARATOR '|') roles,
        GROUP_CONCAT(DISTINCT sp.permiso ORDER BY sp.permiso SEPARATOR '|') permisos_ventas
    FROM sys_usuarios su
    LEFT JOIN sys_usuarios_roles sur ON sur.id_usuario=su.id_usuario AND sur.estatus=1
    LEFT JOIN sys_roles sr ON sr.id_rol=sur.id_rol AND sr.estatus=1
    LEFT JOIN sys_roles_permisos srp ON srp.id_rol=sr.id_rol
    LEFT JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso AND sp.permiso IN ('ventas.ver','ventas.operar') AND sp.estatus=1";
$params = array();
if ($idUsuario > 0) {
    $usuariosSql .= " WHERE su.id_usuario=:usuario";
    $params[":usuario"] = $idUsuario;
}
$usuariosSql .= " GROUP BY su.id_usuario, su.nombres, su.apellido_paterno, su.apellido_materno, su.estatus
    ORDER BY su.id_usuario";
$usuarios = consultar($db, $usuariosSql, $params);

$rolesSql = "SELECT sr.id_rol, sr.rol, sr.estatus,
        GROUP_CONCAT(DISTINCT sp.permiso ORDER BY sp.permiso SEPARATOR '|') permisos_ventas
    FROM sys_roles sr
    LEFT JOIN sys_roles_permisos srp ON srp.id_rol=sr.id_rol
    LEFT JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso AND sp.permiso IN ('ventas.ver','ventas.operar') AND sp.estatus=1
    GROUP BY sr.id_rol, sr.rol, sr.estatus
    HAVING permisos_ventas IS NOT NULL
    ORDER BY sr.rol";
$roles = consultar($db, $rolesSql, array());

$diagnosticoUsuarios = array();
foreach ($usuarios as $usuario) {
    $permisos = array_filter(explode("|", (string) $usuario["permisos_ventas"]));
    $diagnosticoUsuarios[] = array(
        "id_usuario" => intval($usuario["id_usuario"]),
        "nombre" => trim($usuario["nombres"] . " " . $usuario["apellido_paterno"] . " " . $usuario["apellido_materno"]),
        "estatus" => $usuario["estatus"],
        "roles" => $usuario["roles"],
        "tiene_ventas_ver" => in_array("ventas.ver", $permisos, true),
        "tiene_ventas_operar" => in_array("ventas.operar", $permisos, true),
        "puede_ver_menu_ventas" => in_array("ventas.ver", $permisos, true),
        "puede_ver_menu_pos" => in_array("ventas.operar", $permisos, true),
        "ruta_pos" => "/ventas/pos"
    );
}

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "id_usuario_filtro" => $idUsuario,
    "permisos_ventas" => $permisosVentas,
    "roles_con_permisos_ventas" => $roles,
    "usuarios" => $diagnosticoUsuarios,
    "regla_menu" => array(
        "ventas" => "requiere ventas.ver",
        "pos" => "requiere ventas.operar",
        "controlador_pos" => "Ventas::pos tambien requiere ventas.operar"
    ),
    "siguiente_paso" => "Si el usuario no tiene ventas.operar, asignar rol/permisos desde Seguridad con autorizacion."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function consultar($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
