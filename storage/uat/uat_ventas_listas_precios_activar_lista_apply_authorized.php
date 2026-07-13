<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: ejecutar UAT autorizado de activacion de lista de precios.
 * Impacto: cambia estatus de una lista a activa y registra evento comercial.
 * Contrato: BLOQUEADO por defecto; no modifica esquema ni requiere respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$motivo = "UAT activacion lista de precios";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT" || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se activo lista de precios. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false,
        "regla_respaldo" => "Activar lista es CRUD comercial; el respaldo externo queda reservado para DDL."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatActivarApply extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatActivarApply();
$db = $listas->conexionUat();
$listaActual = null;
if ($idLista > 0) {
    $listaActual = buscarListaPorId($db, $idLista);
} elseif ($codigoLista !== "") {
    $listaActual = buscarListaPorCodigo($db, $codigoLista);
    $idLista = intval(valor($listaActual, "id_lista_precio", 0));
}

$entrada = normalizarEntradaActivacion($listaActual);
$entrada["motivo"] = $motivo;
$dryRun = $listas->listaDryRun($entrada);
$detallesActivos = $idLista > 0 ? contarDetallesActivos($db, $idLista) : 0;
$bloqueos = array();

if (!$listaActual) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado";
}
if (!tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta erp_listas_precios_eventos; no se permite activar sin auditoria comercial";
}
if (!permisoExiste($db, "ventas.listas.editar")) {
    $bloqueos[] = "Falta permiso ventas.listas.editar en BD";
}
if (!permisoExiste($db, "ventas.listas.activar")) {
    $bloqueos[] = "Falta permiso ventas.listas.activar en BD";
}
if ($detallesActivos <= 0) {
    $bloqueos[] = "La lista no tiene detalles activos; no conviene activarla para POS";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de activacion no permite guardar";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se activo lista de precios; faltan precondiciones UAT.",
        "codigo_lista" => $codigoLista,
        "entrada" => $entrada,
        "detalles_activos" => $detallesActivos,
        "dry_run" => $dryRun,
        "bloqueos" => $bloqueos,
        "requiere_respaldo_externo" => false
    ));
}

$resultado = $listas->listaGuardarAutorizado($entrada, $idUsuario);
responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_listas_precios_activar_lista_apply_authorized",
    "codigo_lista" => $codigoLista,
    "entrada" => $entrada,
    "resultado" => $resultado,
    "id_usuario" => $idUsuario,
    "requiere_respaldo_externo" => false,
    "siguiente_paso" => empty($resultado["error"])
        ? "Ejecutar resolutor POS para confirmar origen de precio."
        : "Resolver bloqueo reportado antes de intentar nueva activacion."
));

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarListaPorId($db, $idLista) {
    $stmt = $db->prepare("SELECT * FROM erp_listas_precios WHERE id_lista_precio=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($idLista)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function normalizarEntradaActivacion($lista) {
    if (!$lista) {
        return array("id_lista_precio" => 0, "estatus" => "activa");
    }
    return array(
        "id_lista_precio" => intval(valor($lista, "id_lista_precio", 0)),
        "codigo" => valor($lista, "codigo", ""),
        "nombre" => valor($lista, "nombre", ""),
        "canal" => valor($lista, "canal", ""),
        "id_almacen" => intval(valor($lista, "id_almacen", 0)),
        "prioridad" => intval(valor($lista, "prioridad", 100)),
        "fecha_inicio" => valor($lista, "fecha_inicio", null),
        "fecha_fin" => valor($lista, "fecha_fin", null),
        "estatus" => "activa"
    );
}

function contarDetallesActivos($db, $idLista) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_listas_precios_detalle WHERE id_lista_precio=:id AND estatus='activo'");
    $stmt->execute(array(":id" => intval($idLista)));
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

function permisoExiste($db, $permiso) {
    $stmt = $db->prepare("SELECT id_permiso FROM sys_permisos WHERE permiso=:permiso AND estatus=1 LIMIT 1");
    $stmt->execute(array(":permiso" => $permiso));
    return (bool) $stmt->fetchColumn();
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
