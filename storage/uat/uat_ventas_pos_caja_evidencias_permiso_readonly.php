<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: diagnosticar permiso fino para revisar evidencias de caja POS sin modificar datos.
 * Impacto: confirma si sys_permisos y roles ya tienen `ventas.caja_evidencias.revisar`.
 * Contrato: read-only; no crea permisos ni asigna roles.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosCajaEvidenciasPermisoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosCajaEvidenciasPermisoReadonlyDb())->db();
$permiso = "ventas.caja_evidencias.revisar";
$rolesObjetivo = array("direccion", "administrador_erp");

$stmt = $db->prepare("SELECT * FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
$stmt->execute(array(":permiso" => $permiso));
$permisoFila = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT r.rol, r.id_rol, p.id_permiso,
        CASE WHEN rp.id_rol IS NULL THEN 0 ELSE 1 END asignado
    FROM sys_roles r
    LEFT JOIN sys_permisos p ON p.permiso=:permiso AND p.estatus=1
    LEFT JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol AND rp.id_permiso=p.id_permiso
    WHERE r.rol IN ('direccion', 'administrador_erp')
    ORDER BY r.rol");
$stmt->execute(array(":permiso" => $permiso));
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$faltantes = array();
if (!$permisoFila) {
    $faltantes[] = "permiso";
}
foreach ($roles as $rol) {
    if (intval($rol["asignado"]) !== 1) {
        $faltantes[] = "rol:" . $rol["rol"];
    }
}

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_caja_evidencias_permiso_readonly",
    "read_only" => true,
    "permiso" => $permiso,
    "permiso_existe" => (bool) $permisoFila,
    "permiso_fila" => $permisoFila,
    "roles_objetivo" => $rolesObjetivo,
    "roles" => $roles,
    "faltantes" => $faltantes,
    "requiere_autorizacion" => count($faltantes) > 0
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
