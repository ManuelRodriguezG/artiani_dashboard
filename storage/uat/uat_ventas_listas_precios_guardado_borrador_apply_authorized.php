<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: ejecutar UAT autorizado de guardado de lista de precios en borrador.
 * Impacto: crea un encabezado en `erp_listas_precios` y su evento comercial si los guardrails estan listos.
 * Contrato: BLOQUEADO por defecto; no modifica esquema ni requiere respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$idUsuario = 0;
$codigo = "LP-UAT-BORRADOR-01";
$nombre = "Lista UAT borrador";
$canal = "pos";
$idAlmacen = 5;
$prioridad = 100;
$motivo = "UAT inicial listas de precios";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--codigo=") === 0) {
        $codigo = strtoupper(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--prioridad=") === 0) {
        $prioridad = intval(trim(substr($arg, 12), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT" || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo lista de precios. Falta autorizacion explicita o id_usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
            "--id_usuario=ID_USUARIO_AUTORIZA"
        ),
        "requiere_respaldo_externo" => false,
        "regla_respaldo" => "Guardar borrador es CRUD normal; el respaldo externo queda reservado para DDL."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatGuardado extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatGuardado();
$db = $listas->conexionUat();
$bloqueos = array();

if (!tablaExiste($db, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta erp_listas_precios_eventos; no se permite guardar sin auditoria comercial";
}
if (!permisoExiste($db, "ventas.listas.crear")) {
    $bloqueos[] = "Falta permiso ventas.listas.crear en BD";
}

$entrada = array(
    "codigo" => $codigo,
    "nombre" => $nombre,
    "canal" => $canal,
    "id_almacen" => $idAlmacen,
    "prioridad" => $prioridad,
    "estatus" => "borrador",
    "motivo" => $motivo,
    "observaciones" => "Creada por UAT autorizado de ERP Ventas/Listas de precios"
);
$dryRun = $listas->listaDryRun($entrada);
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de lista no permite guardar";
}

if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se guardo lista de precios; faltan precondiciones UAT.",
        "entrada" => $entrada,
        "dry_run" => $dryRun,
        "bloqueos" => $bloqueos,
        "requiere_respaldo_externo" => false
    ));
}

$resultado = $listas->listaGuardarAutorizado($entrada, $idUsuario);
responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_listas_precios_guardado_borrador_apply_authorized",
    "entrada" => $entrada,
    "resultado" => $resultado,
    "id_usuario" => $idUsuario,
    "requiere_respaldo_externo" => false,
    "siguiente_paso" => empty($resultado["error"])
        ? "Consultar evento comercial y repetir preflight antes de agregar detalle."
        : "Resolver bloqueo reportado antes de intentar nuevo guardado."
));

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
