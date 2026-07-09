<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-28.
 * Proposito: auditar permisos POS para excepciones comerciales sin modificar roles ni BD.
 * Impacto: prepara siembra autorizada de precio manual/descuentos/supervisor.
 * Contrato: read-only; no inserta permisos, no asigna roles y no cambia sesiones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadEsquema.php";
require_once "../app/modelos/SeguridadPermisos.php";

class SeguridadPermisosUatReadonly extends SeguridadPermisos {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$esperados = array(
    "ventas.precio_manual",
    "ventas.descuento_partida",
    "ventas.descuento_general",
    "ventas.autorizar_excepcion_comercial"
);

$esquema = new SeguridadEsquema();
$permisosCodigo = array();
foreach ($esquema->permisosBaseERP() as $permiso) {
    if (in_array($permiso["permiso"], $esperados, true)) {
        $permisosCodigo[$permiso["permiso"]] = $permiso;
    }
}

$rolesCodigo = array();
foreach ($esquema->permisosPorRolBaseERP() as $rol => $permisos) {
    foreach ($esperados as $permiso) {
        if (in_array($permiso, $permisos, true)) {
            if (!isset($rolesCodigo[$rol])) {
                $rolesCodigo[$rol] = array();
            }
            $rolesCodigo[$rol][] = $permiso;
        }
    }
}

$permisosModelo = new SeguridadPermisosUatReadonly();
$db = $permisosModelo->conexionUat();
$marcadores = implode(",", array_fill(0, count($esperados), "?"));
$stmt = $db->prepare("SELECT id_permiso, modulo, accion, permiso, descripcion, estatus
    FROM sys_permisos
    WHERE permiso IN ($marcadores)
    ORDER BY permiso");
$stmt->execute($esperados);
$permisosBd = $stmt->fetchAll(PDO::FETCH_ASSOC);
$permisosBdSet = array();
foreach ($permisosBd as $permiso) {
    $permisosBdSet[$permiso["permiso"]] = true;
}

$faltantesBd = array();
foreach ($esperados as $permiso) {
    if (!isset($permisosBdSet[$permiso])) {
        $faltantesBd[] = $permiso;
    }
}

$rolesBd = array();
if (!empty($permisosBd)) {
    $stmt = $db->prepare("SELECT r.rol, p.permiso
        FROM sys_roles r
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso
        WHERE p.permiso IN ($marcadores)
        ORDER BY r.rol, p.permiso");
    $stmt->execute($esperados);
    $rolesBd = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(array(
    "ok" => count($permisosCodigo) === count($esperados),
    "modo" => "ventas_pos_excepcion_permisos_readonly",
    "esperados" => $esperados,
    "permisos_codigo" => array_values($permisosCodigo),
    "roles_codigo" => $rolesCodigo,
    "permisos_bd" => $permisosBd,
    "roles_bd" => $rolesBd,
    "faltantes_bd" => $faltantesBd,
    "requiere_autorizacion_siembra" => !empty($faltantesBd),
    "siguiente_paso" => "Si faltan permisos en BD, autorizar siembra de SeguridadEsquema con respaldo externo; no hacerlo desde este script."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
