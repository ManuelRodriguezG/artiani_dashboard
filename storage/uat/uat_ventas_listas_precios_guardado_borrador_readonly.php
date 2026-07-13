<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar guardado UAT de una lista de precios en borrador sin escribir BD.
 * Impacto: confirma que el CRUD inicial puede operar una vez aplicado DDL/auditoria/permisos.
 * Contrato: read-only; no crea lista, detalle, asignacion ni evento.
 */

$args = isset($argv) ? $argv : array();
$codigo = "LP-UAT-BORRADOR-01";
$nombre = "Lista UAT borrador";
$canal = "pos";
$idAlmacen = 5;
$prioridad = 100;

foreach ($args as $arg) {
    if (strpos($arg, "--codigo=") === 0) {
        $codigo = strtoupper(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--canal=") === 0) {
        $canal = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--prioridad=") === 0) {
        $prioridad = intval(trim(substr($arg, 12), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

$esquema = new VentasErpEsquema();
$listas = new ListasPreciosErp();
$auditoriaCrm = $esquema->auditarListasPreciosCrm();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();
$dryRun = $listas->listaDryRun(array(
    "codigo" => $codigo,
    "nombre" => $nombre,
    "canal" => $canal,
    "id_almacen" => $idAlmacen,
    "prioridad" => $prioridad,
    "estatus" => "borrador"
));

$bloqueos = array();
$avisos = array();
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if (!columnaExisteEnAuditoria($auditoriaCrm, "id_cliente_crm")) {
    $avisos[] = "Falta DDL CRM/listas id_cliente_crm; bloquea asignacion cliente/lista, no encabezado borrador";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de lista no permite guardar";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_guardado_borrador_readonly",
    "read_only" => true,
    "entrada" => array(
        "codigo" => $codigo,
        "nombre" => $nombre,
        "canal" => $canal,
        "id_almacen" => $idAlmacen,
        "prioridad" => $prioridad,
        "estatus" => "borrador"
    ),
    "dry_run" => $dryRun,
    "auditoria_crm" => $auditoriaCrm,
    "auditoria_eventos" => $auditoriaEventos,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "guardrails" => array(
        "token_guardado" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "requiere_respaldo_externo" => false,
        "motivo" => "Guardar borrador es CRUD normal; no cambia esquema."
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para ejecutar apply autorizado de lista borrador."
        : "Aplicar DDL/permisos o resolver dry-run antes de guardar."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function tablaExisteEnAuditoria($auditoria, $tabla) {
    $tablas = valor($auditoria, array("depurar", "tablas"), array());
    foreach ($tablas as $item) {
        if (isset($item["tabla"]) && $item["tabla"] === $tabla) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function columnaExisteEnAuditoria($auditoria, $columna) {
    $columnas = valor($auditoria, array("depurar", "columnas"), array());
    foreach ($columnas as $item) {
        if (isset($item["columna"]) && $item["columna"] === $columna) {
            return !empty($item["existe"]);
        }
    }
    return false;
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
