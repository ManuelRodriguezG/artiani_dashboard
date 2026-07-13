<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: ejecutar UAT autorizado para agregar precio por SKU a una lista existente.
 * Impacto: crea o edita un detalle en `erp_listas_precios_detalle` y su evento comercial.
 * Contrato: BLOQUEADO por defecto; no modifica esquema ni requiere respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$precio = 315.00;
$moneda = "MXN";
$estatus = "activo";
$motivo = "UAT detalle SKU listas de precios";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = round(floatval(trim(substr($arg, 9), "\"' ")), 6);
    } elseif (strpos($arg, "--moneda=") === 0) {
        $moneda = strtoupper(trim(substr($arg, 9), "\"' "));
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
        "mensaje" => "No se guardo detalle de lista. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false,
        "regla_respaldo" => "Guardar detalle es CRUD normal; el respaldo externo queda reservado para DDL."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatDetalleApply extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatDetalleApply();
$db = $listas->conexionUat();
if ($idLista <= 0 && $codigoLista !== "") {
    $idLista = buscarListaPorCodigo($db, $codigoLista);
}

$entrada = array(
    "id_lista_precio" => $idLista,
    "id_sku" => $idSku,
    "precio" => $precio,
    "moneda" => $moneda,
    "estatus" => $estatus,
    "motivo" => $motivo
);
$dryRun = $listas->detalleDryRun($entrada);
$bloqueos = array();
if ($idLista <= 0) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado en borrador o indica --id_lista_precio";
}
if (!tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta erp_listas_precios_eventos; no se permite guardar sin auditoria comercial";
}
if (!permisoExiste($db, "ventas.listas.editar")) {
    $bloqueos[] = "Falta permiso ventas.listas.editar en BD";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de detalle no permite guardar";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo detalle de lista; faltan precondiciones UAT.",
        "entrada" => $entrada,
        "codigo_lista" => $codigoLista,
        "dry_run" => $dryRun,
        "bloqueos" => $bloqueos,
        "requiere_respaldo_externo" => false
    ));
}

$resultado = $listas->detalleGuardarAutorizado($entrada, $idUsuario);
responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_listas_precios_detalle_sku_apply_authorized",
    "entrada" => $entrada,
    "codigo_lista" => $codigoLista,
    "resultado" => $resultado,
    "id_usuario" => $idUsuario,
    "requiere_respaldo_externo" => false,
    "siguiente_paso" => empty($resultado["error"])
        ? "Prevalidar resolutor POS para confirmar origen/precio antes de activar la lista."
        : "Resolver bloqueo reportado antes de intentar nuevo guardado."
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

function permisoExiste($db, $permiso) {
    $stmt = $db->prepare("SELECT id_permiso FROM sys_permisos WHERE permiso=:permiso AND estatus=1 LIMIT 1");
    $stmt->execute(array(":permiso" => $permiso));
    return (bool) $stmt->fetchColumn();
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
