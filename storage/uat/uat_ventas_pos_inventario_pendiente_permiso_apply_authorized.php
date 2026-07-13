<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: sembrar permiso fino para autorizar venta POS con inventario pendiente en productivo.
 * Impacto: prepara reemplazo de token UAT por permiso de supervisor; no crea ventas, politicas, pendientes ni movimientos.
 * Contrato: bloqueado por defecto; requiere respaldo externo, id_usuario y token VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO.
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

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO" || $idUsuario <= 0 || !$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se sembro permiso de inventario pendiente POS. Falta autorizacion explicita, respaldo valido o id_usuario.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_INVENTARIO_PENDIENTE_PERMISO",
            "--respaldo=RUTA_O_REFERENCIA_RESPALDO",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "alcance" => array(
            "crea_permiso" => true,
            "asigna_roles_base" => true,
            "crea_ventas" => false,
            "mueve_caja" => false,
            "mueve_inventario" => false,
            "activa_ecommerce" => false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class VentasPosInventarioPendientePermisoApply extends CRUD {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$db = (new VentasPosInventarioPendientePermisoApply())->conexionUat();
$permiso = array(
    "modulo" => "ventas",
    "accion" => "pos_inventario_pendiente_autorizar",
    "permiso" => "ventas.pos.inventario_pendiente.autorizar",
    "descripcion" => "Autorizar venta POS con inventario pendiente bajo politica por sucursal/SKU/canal"
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
        "modo" => "ventas_pos_inventario_pendiente_permiso_apply_authorized",
        "mensaje" => "Permiso de inventario pendiente POS sembrado",
        "permiso" => $permiso["permiso"],
        "roles" => $roles,
        "id_usuario" => $idUsuario,
        "siguiente_paso" => "Refrescar permisos/sesion y despues preparar endpoint productivo sin token UAT."
    ));
} catch (Exception $e) {
    if ($db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "error",
        "mensaje" => $e->getMessage()
    ));
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "es_ruta_local" => $esRutaLocal,
        "existe" => $existe,
        "legible" => $legible,
        "tamano" => $tamano,
        "referencia_valida" => $okReferencia
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
