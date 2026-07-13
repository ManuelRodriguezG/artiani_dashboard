<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar UAT de asignacion de lista a cliente CRM sin escribir BD.
 * Impacto: confirma si el contrato CRM/listas esta listo para resolver `lista_cliente`.
 * Contrato: read-only; no crea asignacion ni cambia condiciones del cliente.
 */

$args = isset($argv) ? $argv : array();
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$idClienteCrm = 1;
$prioridad = 1;
$estatus = "activo";

foreach ($args as $arg) {
    if (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--prioridad=") === 0) {
        $prioridad = intval(trim(substr($arg, 12), "\"' "));
    } elseif (strpos($arg, "--estatus=") === 0) {
        $estatus = trim(substr($arg, 10), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatAsignacionReadonly extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatAsignacionReadonly();
$db = $listas->conexionUat();
if ($idLista <= 0 && $codigoLista !== "") {
    $idLista = buscarListaPorCodigo($db, $codigoLista);
}

$esquema = new VentasErpEsquema();
$auditoriaCrm = $esquema->auditarListasPreciosCrm();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();
$entrada = array(
    "id_lista_precio" => $idLista,
    "id_cliente_crm" => $idClienteCrm,
    "prioridad" => $prioridad,
    "estatus" => $estatus
);
$dryRun = $listas->asignacionClienteDryRun($entrada);

$bloqueos = array();
if ($idLista <= 0) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado o indica --id_lista_precio";
}
if (!columnaExisteEnAuditoria($auditoriaCrm, "id_cliente_crm")) {
    $bloqueos[] = "Falta DDL CRM/listas id_cliente_crm";
}
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de asignacion no permite guardar";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_asignacion_cliente_readonly",
    "read_only" => true,
    "entrada" => $entrada,
    "codigo_lista" => $codigoLista,
    "dry_run" => $dryRun,
    "auditoria_crm" => $auditoriaCrm,
    "auditoria_eventos" => $auditoriaEventos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "token_guardado" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "permiso" => "ventas.listas.asignar_cliente",
        "requiere_respaldo_externo" => false,
        "motivo" => "Asignar cliente/lista es CRUD normal; no cambia esquema."
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para ejecutar apply autorizado de asignacion cliente/lista."
        : "Aplicar CRM/listas, auditoria, permisos o crear lista antes de asignar cliente."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT id_lista_precio FROM erp_listas_precios WHERE codigo=:codigo LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    return intval($stmt->fetchColumn());
}

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
