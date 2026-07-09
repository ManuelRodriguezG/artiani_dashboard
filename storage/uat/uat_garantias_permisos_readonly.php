<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-27
 * Proposito: validar permisos sembrados de Garantias ERP despues de aplicar SeguridadEsquema.
 * Impacto: Seguridad/Garantias; solo lectura.
 * Contrato: no modifica BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatGarantiasPermisosReadonly extends CRUD {
    public function ejecutar() {
        $db = $this->getConexion();
        $stmt = $db->query("SELECT permiso, modulo, accion FROM sys_permisos WHERE permiso LIKE 'garantias.%' ORDER BY permiso");
        $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->query("SELECT r.rol, p.permiso
            FROM sys_roles r
            INNER JOIN sys_roles_permisos rp ON rp.id_rol = r.id_rol
            INNER JOIN sys_permisos p ON p.id_permiso = rp.id_permiso
            WHERE p.permiso LIKE 'garantias.%'
            ORDER BY r.rol, p.permiso");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array(
            "error" => false,
            "tipo" => count($permisos) >= 7 ? "success" : "warning",
            "mensaje" => "Permisos de Garantias consultados",
            "depurar" => array(
                "permisos_total" => count($permisos),
                "permisos" => $permisos,
                "roles_permisos_total" => count($roles),
                "roles_permisos" => $roles
            )
        );
    }
}

$uat = new UatGarantiasPermisosReadonly();
echo json_encode($uat->ejecutar(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
