<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar activacion UAT de una lista de precios sin escribir BD.
 * Impacto: confirma que la lista tiene condiciones minimas para entrar al resolutor POS.
 * Contrato: read-only; no activa lista ni modifica detalles.
 */

$args = isset($argv) ? $argv : array();
$idLista = 0;
$codigoLista = "LP-UAT-BORRADOR-01";

foreach ($args as $arg) {
    if (strpos($arg, "--id_lista_precio=") === 0) {
        $idLista = intval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

class ListasPreciosErpUatActivarReadonly extends ListasPreciosErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$listas = new ListasPreciosErpUatActivarReadonly();
$db = $listas->conexionUat();
$listaActual = null;
if ($idLista > 0) {
    $listaActual = buscarListaPorId($db, $idLista);
} elseif ($codigoLista !== "") {
    $listaActual = buscarListaPorCodigo($db, $codigoLista);
    $idLista = intval(valor($listaActual, "id_lista_precio", 0));
}

$entrada = normalizarEntradaActivacion($listaActual);
$dryRun = $listas->listaDryRun($entrada);
$esquema = new VentasErpEsquema();
$auditoriaEventos = $esquema->auditarAuditoriaListasPrecios();
$detallesActivos = $idLista > 0 ? contarDetallesActivos($db, $idLista) : 0;

$bloqueos = array();
if (!$listaActual) {
    $bloqueos[] = "No existe la lista objetivo; crea primero el encabezado";
}
if (!tablaExisteEnAuditoria($auditoriaEventos, "erp_listas_precios_eventos")) {
    $bloqueos[] = "Falta DDL de auditoria erp_listas_precios_eventos";
}
if ($detallesActivos <= 0) {
    $bloqueos[] = "La lista no tiene detalles activos; no conviene activarla para POS";
}
if (!empty($dryRun["error"]) || empty($dryRun["depurar"]["puede_guardar"])) {
    $bloqueos[] = "Dry-run de activacion no permite guardar";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_activar_lista_readonly",
    "read_only" => true,
    "codigo_lista" => $codigoLista,
    "lista_actual" => $listaActual,
    "entrada_activacion" => $entrada,
    "detalles_activos" => $detallesActivos,
    "dry_run" => $dryRun,
    "auditoria_eventos" => $auditoriaEventos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "token_guardado" => "VENTAS_LISTAS_PRECIOS_GUARDAR_UAT",
        "permisos" => array("ventas.listas.editar", "ventas.listas.activar"),
        "requiere_respaldo_externo" => false,
        "motivo" => "Activar lista es cambio comercial auditado; no modifica esquema."
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para ejecutar apply autorizado de activacion."
        : "Crear encabezado/detalles y aplicar auditoria/permisos antes de activar."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

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
    if (!is_array($ruta)) {
        return is_array($datos) && array_key_exists($ruta, $datos) ? $datos[$ruta] : $default;
    }
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
