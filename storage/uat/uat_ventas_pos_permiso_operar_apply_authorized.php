<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: habilitar POS visible para administradores ERP asignando ventas.operar al rol administrador_erp.
 * Impacto: escribe una relacion rol-permiso; no modifica ventas, caja, inventario ni turnos.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_PERMISO_OPERAR y respaldo valido.
 */

$autorizar = "";
$respaldo = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_PERMISO_OPERAR" || !$validacionRespaldo["ok"]) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se asigno ventas.operar. Falta autorizacion explicita o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_PERMISO_OPERAR",
            "--respaldo=RUTA_RESPALDO_EXISTENTE"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPermisoOperarDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPermisoOperarDb())->db();
try {
    $db->beginTransaction();
    $stmt = $db->prepare("SELECT id_rol FROM sys_roles WHERE rol='administrador_erp' AND estatus=1 LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $idRol = intval($stmt->fetchColumn());
    if ($idRol <= 0) {
        throw new Exception("Rol administrador_erp no encontrado o inactivo");
    }
    $stmt = $db->prepare("SELECT id_permiso FROM sys_permisos WHERE permiso='ventas.operar' AND estatus=1 LIMIT 1 FOR UPDATE");
    $stmt->execute();
    $idPermiso = intval($stmt->fetchColumn());
    if ($idPermiso <= 0) {
        throw new Exception("Permiso ventas.operar no encontrado o inactivo");
    }
    $stmt = $db->prepare("INSERT INTO sys_roles_permisos (id_rol, id_permiso)
        SELECT :rol, :permiso
        WHERE NOT EXISTS (
            SELECT 1 FROM sys_roles_permisos
            WHERE id_rol=:rol_existe AND id_permiso=:permiso_existe
        )");
    $stmt->execute(array(
        ":rol" => $idRol,
        ":permiso" => $idPermiso,
        ":rol_existe" => $idRol,
        ":permiso_existe" => $idPermiso
    ));
    $filas = $stmt->rowCount();
    $db->commit();
    echo json_encode(array(
        "ok" => true,
        "modo" => "permiso_asignado",
        "respaldo_ref" => $respaldo,
        "rol" => "administrador_erp",
        "id_rol" => $idRol,
        "permiso" => "ventas.operar",
        "id_permiso" => $idPermiso,
        "filas_insertadas" => $filas,
        "siguiente_paso" => "Cerrar sesion y volver a iniciar, o recargar para que sidebar refresque permisos."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false
    );
}
