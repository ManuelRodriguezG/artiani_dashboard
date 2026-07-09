<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: sembrar permiso fino de revision de evidencias de caja POS solo con autorizacion explicita.
 * Impacto: crea/actualiza `ventas.caja_evidencias.revisar` y lo asigna a roles administrativos base.
 * Contrato: escribe BD; requiere respaldo externo y token VENTAS_POS_CAJA_EVIDENCIAS_PERMISO.
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

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIAS_PERMISO" || $idUsuario <= 0 || $respaldo === "" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se sembro permiso de evidencias de caja POS. Falta autorizacion explicita, respaldo valido o id_usuario.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIAS_PERMISO",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosCajaEvidenciasPermisoApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosCajaEvidenciasPermisoApplyDb())->db();
$permiso = array(
    "modulo" => "ventas",
    "accion" => "caja_evidencias_revisar",
    "permiso" => "ventas.caja_evidencias.revisar",
    "descripcion" => "Aprobar o rechazar evidencias de movimientos sensibles de caja POS"
);
$roles = array("direccion", "administrador_erp");

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
    $stmtPermiso->execute(array(
        ":modulo" => $permiso["modulo"],
        ":accion" => $permiso["accion"],
        ":permiso" => $permiso["permiso"],
        ":descripcion" => $permiso["descripcion"]
    ));

    $stmtRolPermiso = $db->prepare("INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso)
        SELECT r.id_rol, p.id_permiso
        FROM sys_roles r
        INNER JOIN sys_permisos p ON p.permiso=:permiso AND p.estatus=1
        WHERE r.rol=:rol AND r.estatus=1");
    foreach ($roles as $rol) {
        $stmtRolPermiso->execute(array(
            ":rol" => $rol,
            ":permiso" => $permiso["permiso"]
        ));
    }

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "ventas_pos_caja_evidencias_permiso_apply_authorized",
        "permiso" => $permiso["permiso"],
        "roles" => $roles,
        "id_usuario" => $idUsuario,
        "siguiente_paso" => "Cerrar sesion o refrescar permisos antes de probar revision de evidencias desde UI."
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
