<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: sembrar permisos ERP Ventas/Listas de precios solo con autorizacion explicita.
 * Impacto: habilita permisos finos `ventas.listas.*` para roles base; no cambia esquema ni listas.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_LISTAS_PRECIOS_PERMISOS e --id_usuario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_PERMISOS" || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se sembraron permisos de Listas de precios. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_PERMISOS",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false,
        "regla_respaldo" => "No cambia esquema; el respaldo externo queda reservado para DDL."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadEsquema.php";
require_once "../app/modelos/SeguridadPermisos.php";

class SeguridadPermisosListasPreciosApply extends SeguridadPermisos {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$esquema = new SeguridadEsquema();
$permisos = array();
foreach ($esquema->permisosBaseERP() as $permiso) {
    if (strpos($permiso["permiso"], "ventas.listas.") === 0) {
        $permisos[] = $permiso;
    }
}
$rolesPermisos = array();
foreach ($esquema->permisosPorRolBaseERP() as $rol => $listaPermisos) {
    foreach ($listaPermisos as $permiso) {
        if (strpos($permiso, "ventas.listas.") === 0) {
            if (!isset($rolesPermisos[$rol])) {
                $rolesPermisos[$rol] = array();
            }
            $rolesPermisos[$rol][] = $permiso;
        }
    }
}

if (count($permisos) !== 8) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Catalogo de permisos ventas.listas.* incompleto en codigo.",
        "total_detectado" => count($permisos)
    ));
}

$modelo = new SeguridadPermisosListasPreciosApply();
$db = $modelo->conexionUat();

try {
    $db->beginTransaction();

    $stmtPermiso = $db->prepare("INSERT INTO sys_permisos
        (modulo, accion, permiso, descripcion, estatus, fecha_actualizacion)
        VALUES (:modulo, :accion, :permiso, :descripcion, 1, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
            modulo=VALUES(modulo),
            accion=VALUES(accion),
            descripcion=VALUES(descripcion),
            estatus=1,
            fecha_actualizacion=CURRENT_TIMESTAMP");
    foreach ($permisos as $permiso) {
        $stmtPermiso->execute(array(
            ":modulo" => $permiso["modulo"],
            ":accion" => $permiso["accion"],
            ":permiso" => $permiso["permiso"],
            ":descripcion" => $permiso["descripcion"]
        ));
    }

    $stmtRolPermiso = $db->prepare("INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso)
        SELECT r.id_rol, p.id_permiso
        FROM sys_roles r
        INNER JOIN sys_permisos p ON p.permiso=:permiso AND p.estatus=1
        WHERE r.rol=:rol AND r.estatus=1");
    foreach ($rolesPermisos as $rol => $listaPermisos) {
        foreach ($listaPermisos as $permiso) {
            $stmtRolPermiso->execute(array(
                ":rol" => $rol,
                ":permiso" => $permiso
            ));
        }
    }

    registrarAuditoria($db, $idUsuario, $permisos, $rolesPermisos);
    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "ventas_listas_precios_permisos_apply_authorized",
        "permisos" => array_map(function ($permiso) {
            return $permiso["permiso"];
        }, $permisos),
        "roles" => array_keys($rolesPermisos),
        "id_usuario" => $idUsuario,
        "requiere_respaldo_externo" => false,
        "siguiente_paso" => "Cerrar sesion o refrescar permisos antes de cambiar rutas a ventas.listas.ver."
    ));
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => $e->getMessage()
    ));
}

function registrarAuditoria($db, $idUsuario, $permisos, $rolesPermisos) {
    try {
        $stmt = $db->prepare("INSERT INTO sys_auditoria_eventos
            (id_usuario, modulo, accion, entidad, entidad_id, resultado, datos_despues, mensaje)
            VALUES (:usuario, 'seguridad', 'ventas_listas_precios_permisos', 'sys_permisos', 'ventas.listas.*', 'ok', :datos, :mensaje)");
        $stmt->execute(array(
            ":usuario" => intval($idUsuario),
            ":datos" => json_encode(array(
                "permisos" => array_map(function ($permiso) {
                    return $permiso["permiso"];
                }, $permisos),
                "roles" => $rolesPermisos
            ), JSON_UNESCAPED_UNICODE),
            ":mensaje" => "Siembra autorizada de permisos ERP Ventas/Listas de precios"
        ));
    } catch (Exception $e) {
        // La auditoria generica no debe impedir commit si la tabla aun no soporta todos los campos esperados.
    }
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
