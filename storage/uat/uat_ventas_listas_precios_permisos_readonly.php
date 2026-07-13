<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: auditar permisos ERP Ventas/Listas de precios sin escribir BD.
 * Impacto: prepara siembra autorizada de `ventas.listas.*` sin tocar esquema ni listas.
 * Contrato: read-only; no inserta permisos, no asigna roles y no cambia sesiones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadEsquema.php";
require_once "../app/modelos/SeguridadPermisos.php";

class SeguridadPermisosListasPreciosReadonly extends SeguridadPermisos {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$esquema = new SeguridadEsquema();
$esperados = permisosListas($esquema->permisosBaseERP());
$rolesCodigo = rolesListas($esquema->permisosPorRolBaseERP());
$modelo = new SeguridadPermisosListasPreciosReadonly();
$db = $modelo->conexionUat();

$permisosBd = array();
$rolesBd = array();
$faltantesBd = array_values($esperados);
if (!empty($esperados)) {
    $marcadores = implode(",", array_fill(0, count($esperados), "?"));
    $stmt = $db->prepare("SELECT id_permiso, modulo, accion, permiso, descripcion, estatus
        FROM sys_permisos
        WHERE permiso IN ($marcadores)
        ORDER BY permiso");
    $stmt->execute(array_values($esperados));
    $permisosBd = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $setBd = array();
    foreach ($permisosBd as $permiso) {
        $setBd[$permiso["permiso"]] = true;
    }
    $faltantesBd = array();
    foreach ($esperados as $permiso) {
        if (!isset($setBd[$permiso])) {
            $faltantesBd[] = $permiso;
        }
    }

    $stmt = $db->prepare("SELECT r.rol, p.permiso
        FROM sys_roles r
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso
        WHERE p.permiso IN ($marcadores)
        ORDER BY r.rol, p.permiso");
    $stmt->execute(array_values($esperados));
    $rolesBd = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(array(
    "ok" => count($esperados) === 8,
    "modo" => "ventas_listas_precios_permisos_readonly",
    "read_only" => true,
    "esperados" => array_values($esperados),
    "roles_codigo" => $rolesCodigo,
    "permisos_bd" => $permisosBd,
    "roles_bd" => $rolesBd,
    "faltantes_bd" => $faltantesBd,
    "requiere_siembra" => !empty($faltantesBd),
    "guardrails" => array(
        "token" => "VENTAS_LISTAS_PRECIOS_PERMISOS",
        "requiere_respaldo_externo" => false,
        "motivo" => "Sembrar permisos no modifica esquema; se audita como cambio de seguridad."
    ),
    "siguiente_paso" => empty($faltantesBd)
        ? "Permisos de Listas ya existen en BD; revisar roles y refrescar sesion."
        : "Autorizar siembra de permisos ventas.listas.* sin respaldo de esquema."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

function permisosListas($permisosBase) {
    $resultado = array();
    foreach ($permisosBase as $permiso) {
        if (strpos($permiso["permiso"], "ventas.listas.") === 0) {
            $resultado[$permiso["permiso"]] = $permiso["permiso"];
        }
    }
    return $resultado;
}

function rolesListas($rolesBase) {
    $resultado = array();
    foreach ($rolesBase as $rol => $permisos) {
        foreach ($permisos as $permiso) {
            if (strpos($permiso, "ventas.listas.") === 0) {
                if (!isset($resultado[$rol])) {
                    $resultado[$rol] = array();
                }
                $resultado[$rol][] = $permiso;
            }
        }
    }
    return $resultado;
}
