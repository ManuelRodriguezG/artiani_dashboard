<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: auditar registros UAT creados por CRUD real de Configuracion POS.
 * Impacto: confirma baja logica y que la configuracion base queda operativa.
 * Contrato: read-only; no crea, edita, desactiva, abre turnos ni mueve caja.
 */

$opciones = getopt("", array("codigo_caja::", "codigo_terminal::", "id_asignacion::"));
$codigoCaja = isset($opciones["codigo_caja"]) ? trim((string) $opciones["codigo_caja"]) : "CJ-UAT-20260704-01";
$codigoTerminal = isset($opciones["codigo_terminal"]) ? trim((string) $opciones["codigo_terminal"]) : "TERM-UAT-20260704-01";
$idAsignacion = isset($opciones["id_asignacion"]) ? intval($opciones["id_asignacion"]) : 3;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

$db = (new class extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
})->conexion();

$salida = array(
    "ok" => true,
    "modo" => "ventas_pos_configuracion_crud_auditoria_readonly",
    "read_only" => true,
    "caja" => buscarCaja($db, $codigoCaja),
    "terminal" => buscarTerminal($db, $codigoTerminal),
    "asignacion" => buscarAsignacion($db, $idAsignacion),
    "turnos_abiertos" => contar($db, "SELECT COUNT(*) FROM erp_pos_turnos WHERE estatus='abierto'"),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
);

$salida["ok"] = isset($salida["caja"]["estatus"], $salida["terminal"]["estatus"], $salida["asignacion"]["estatus"])
    && $salida["caja"]["estatus"] === "inactiva"
    && $salida["terminal"]["estatus"] === "inactiva"
    && $salida["asignacion"]["estatus"] === "inactivo"
    && intval($salida["turnos_abiertos"]) === 0;

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function buscarCaja($db, $codigo) {
    $stmt = $db->prepare("SELECT id_caja, codigo, nombre, id_almacen, estatus, observaciones FROM erp_pos_cajas WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscarTerminal($db, $codigo) {
    $stmt = $db->prepare("SELECT id_terminal_pos, codigo, nombre, id_almacen, id_caja, estatus, observaciones FROM erp_pos_terminales WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function buscarAsignacion($db, $idAsignacion) {
    $stmt = $db->prepare("SELECT id_usuario_caja, id_usuario, id_almacen, id_caja, id_terminal_pos, estatus, prioridad, observaciones FROM erp_pos_usuarios_cajas WHERE id_usuario_caja=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idAsignacion)));
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function contar($db, $sql) {
    return intval($db->query($sql)->fetchColumn());
}
