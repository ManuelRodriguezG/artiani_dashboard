<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar UAT de detalle de precio por SKU sin escribir BD.
 * Impacto: confirma si una lista existente puede recibir un precio para SKU 1760.
 * Contrato: read-only; no crea detalle, no cambia precio y no activa lista.
 */

$args = isset($argv) ? $argv : array();
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";
$idSku = 1760;
$precio = 315.00;
$moneda = "MXN";
$estatus = "activo";

foreach ($args as $arg) {
    if (strpos($arg, "--id_lista_precio=") === 0) {
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
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatDetalleReadonly extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatDetalleReadonly();
$db = $listas->conexionUat();
if ($idLista <= 0 && $codigoLista !== "") {
    $idLista = buscarListaPorCodigo($db, $codigoLista);
}

$esquema = new VentasErpEsquema();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();
$entrada = array(
    "id_lista_precio" => $idLista,
    "id_sku" => $idSku,
    "precio" => $precio,
    "moneda" => $moneda,
    "estatus" => $estatus
);
$dryRun = $listas->detalleDryRun($entrada);

$bloqueos = array();
if ($idLista <= 0) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado en borrador o indica --id_lista_precio";
}
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de detalle no permite guardar";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_detalle_sku_readonly",
    "read_only" => true,
    "entrada" => $entrada,
    "codigo_lista" => $codigoLista,
    "dry_run" => $dryRun,
    "auditoria_eventos" => $auditoriaEventos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "token_guardado" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "permiso" => "ventas.listas.editar",
        "requiere_respaldo_externo" => false,
        "motivo" => "Guardar detalle es CRUD normal; no cambia esquema."
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para ejecutar apply autorizado de detalle SKU."
        : "Crear encabezado, aplicar auditoria/permisos o resolver dry-run antes de guardar detalle."
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
