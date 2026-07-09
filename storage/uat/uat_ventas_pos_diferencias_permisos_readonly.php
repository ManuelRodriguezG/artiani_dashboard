<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: auditar permisos finos de diferencias de caja POS sin escribir BD.
 * Impacto: valida si Seguridad ya puede separar ver/revisar/resolver diferencias.
 * Contrato: read-only; no crea permisos ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
})->conexion();

$permisos = ventas_pos_diferencias_permisos_seed();
$permisosClaves = array_map(function ($permiso) {
    return $permiso["permiso"];
}, $permisos);
$resultado = array();
$roles = array();
$tablas = array("sys_permisos", "sys_roles", "sys_roles_permisos");

try {
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
        $stmt->execute(array(":tabla" => $tabla));
        $resultado["tablas"][$tabla] = intval($stmt->fetchColumn()) > 0;
    }

    $stmtPermiso = $db->prepare("SELECT permiso, modulo, accion, descripcion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
    foreach ($permisosClaves as $permiso) {
        $stmtPermiso->execute(array(":permiso" => $permiso));
        $row = $stmtPermiso->fetch(PDO::FETCH_ASSOC);
        $resultado["permisos"][] = array(
            "permiso" => $permiso,
            "existe" => (bool) $row,
            "detalle" => $row ?: null
        );
    }

    $stmtRoles = $db->prepare("SELECT r.rol, p.permiso
        FROM sys_roles r
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso
        WHERE p.permiso IN ('" . implode("','", array_map("addslashes", $permisosClaves)) . "')
        ORDER BY r.rol, p.permiso");
    $stmtRoles->execute();
    foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!isset($roles[$row["rol"]])) {
            $roles[$row["rol"]] = array();
        }
        $roles[$row["rol"]][] = $row["permiso"];
    }
    $resultado["roles_permisos"] = $roles;
    $resultado["faltantes"] = array_values(array_filter($resultado["permisos"], function ($item) {
        return empty($item["existe"]);
    }));

    echo json_encode(array(
        "ok" => true,
        "modo" => "ventas_pos_diferencias_permisos_readonly",
        "read_only" => true,
        "resultado" => $resultado,
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_crea_permisos" => true,
            "no_asigna_roles" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function ventas_pos_diferencias_permisos_seed() {
    return array(
        array("modulo" => "ventas", "accion" => "caja_diferencias_ver", "permiso" => "ventas.caja_diferencias.ver", "descripcion" => "Consultar faltantes y sobrantes de caja POS por turno, usuario y sucursal"),
        array("modulo" => "ventas", "accion" => "caja_diferencias_revisar", "permiso" => "ventas.caja_diferencias.revisar", "descripcion" => "Crear o tomar expedientes de revision de diferencias de caja POS"),
        array("modulo" => "ventas", "accion" => "caja_diferencias_resolver", "permiso" => "ventas.caja_diferencias.resolver", "descripcion" => "Resolver administrativamente diferencias de caja POS sin mover efectivo ni inventario")
    );
}
