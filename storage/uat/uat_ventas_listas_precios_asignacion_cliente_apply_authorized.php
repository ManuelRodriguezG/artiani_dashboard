<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: ejecutar UAT autorizado de asignacion de lista a cliente CRM.
 * Impacto: crea o edita una relacion en `erp_clientes_listas_precios` y su evento comercial.
 * Contrato: BLOQUEADO por defecto; no modifica esquema ni requiere respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$idClienteCrm = 1;
$prioridad = 1;
$estatus = "activo";
$motivo = "UAT asignacion cliente CRM listas de precios";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--prioridad=") === 0) {
        $prioridad = intval(trim(substr($arg, 12), "\"' "));
    } elseif (strpos($arg, "--estatus=") === 0) {
        $estatus = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT" || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo asignacion cliente/lista. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false,
        "regla_respaldo" => "Asignar cliente/lista es CRUD normal; el respaldo externo queda reservado para DDL."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatAsignacionApply extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatAsignacionApply();
$db = $listas->conexionUat();
if ($idLista <= 0 && $codigoLista !== "") {
    $idLista = buscarListaPorCodigo($db, $codigoLista);
}

$entrada = array(
    "id_lista_precio" => $idLista,
    "id_cliente_crm" => $idClienteCrm,
    "prioridad" => $prioridad,
    "estatus" => $estatus,
    "motivo" => $motivo
);
$dryRun = $listas->asignacionClienteDryRun($entrada);
$bloqueos = array();
if ($idLista <= 0) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado o indica --id_lista_precio";
}
if (!columnaExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
    $bloqueos[] = "Falta id_cliente_crm en erp_clientes_listas_precios";
}
if (!tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta erp_listas_precios_eventos; no se permite guardar sin auditoria comercial";
}
if (!permisoExiste($db, "ventas.listas.asignar_cliente")) {
    $bloqueos[] = "Falta permiso ventas.listas.asignar_cliente en BD";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de asignacion no permite guardar";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo asignacion cliente/lista; faltan precondiciones UAT.",
        "entrada" => $entrada,
        "codigo_lista" => $codigoLista,
        "dry_run" => $dryRun,
        "bloqueos" => $bloqueos,
        "requiere_respaldo_externo" => false
    ));
}

$resultado = $listas->asignacionClienteGuardarAutorizado($entrada, $idUsuario);
responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_listas_precios_asignacion_cliente_apply_authorized",
    "entrada" => $entrada,
    "codigo_lista" => $codigoLista,
    "resultado" => $resultado,
    "id_usuario" => $idUsuario,
    "requiere_respaldo_externo" => false,
    "siguiente_paso" => empty($resultado["error"])
        ? "Ejecutar resolutor POS con cliente CRM para confirmar origen lista_cliente."
        : "Resolver bloqueo reportado antes de intentar nueva asignacion."
));

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_lista_precio FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return intval($stmt->fetchColumn());
}

function tablaExiste($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function columnaExiste($db, $tabla, $columna) {
    if (!tablaExiste($db, $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
        return false;
    }
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetchColumn();
}

function permisoExiste($db, $permiso) {
    $stmt = $db->prepare("SELECT id_permiso FROM sys_permisos WHERE permiso=:permiso AND estatus=1 LIMIT 1");
    $stmt->execute(array(":permiso" => $permiso));
    return (bool) $stmt->fetchColumn();
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
