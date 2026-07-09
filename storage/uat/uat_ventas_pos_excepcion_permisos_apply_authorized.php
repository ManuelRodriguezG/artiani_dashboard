<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-28.
 * Proposito: sembrar permisos POS de excepcion comercial solo con autorizacion explicita.
 * Impacto: habilita permisos finos de precio manual/descuento/supervisor para futuras ventas reales.
 * Contrato: escribe BD; requiere respaldo externo y --autorizar=VENTAS_POS_EXCEPCION_PERMISOS.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

if ($autorizar !== "VENTAS_POS_EXCEPCION_PERMISOS" || $idUsuario <= 0 || $respaldo === "" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se sembraron permisos POS. Falta autorizacion explicita, respaldo valido o id_usuario.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_EXCEPCION_PERMISOS",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/SeguridadPermisos.php";

class SeguridadPermisosPosExcepcionApply extends SeguridadPermisos {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$permisos = array(
    array(
        "modulo" => "ventas",
        "accion" => "precio_manual",
        "permiso" => "ventas.precio_manual",
        "descripcion" => "Solicitar o aplicar precio manual en POS segun politica autorizada"
    ),
    array(
        "modulo" => "ventas",
        "accion" => "descuento_partida",
        "permiso" => "ventas.descuento_partida",
        "descripcion" => "Solicitar o aplicar descuento por partida en POS segun politica autorizada"
    ),
    array(
        "modulo" => "ventas",
        "accion" => "descuento_general",
        "permiso" => "ventas.descuento_general",
        "descripcion" => "Solicitar o aplicar descuento general en POS segun politica autorizada"
    ),
    array(
        "modulo" => "ventas",
        "accion" => "autorizar_excepcion_comercial",
        "permiso" => "ventas.autorizar_excepcion_comercial",
        "descripcion" => "Autorizar excepciones comerciales de precio o descuento en POS"
    )
);
$roles = array("direccion", "administrador_erp");

$modelo = new SeguridadPermisosPosExcepcionApply();
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
    foreach ($roles as $rol) {
        foreach ($permisos as $permiso) {
            $stmtRolPermiso->execute(array(
                ":rol" => $rol,
                ":permiso" => $permiso["permiso"]
            ));
        }
    }

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "ventas_pos_excepcion_permisos_apply_authorized",
        "permisos" => array_map(function ($permiso) {
            return $permiso["permiso"];
        }, $permisos),
        "roles" => $roles,
        "id_usuario" => $idUsuario,
        "siguiente_paso" => "Cerrar sesion o refrescar permisos antes de probar autorizaciones comerciales reales."
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

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
