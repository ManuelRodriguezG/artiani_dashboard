<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: UAT read-only del plan RES-T009F de preparacion de resurtido.
 * Impacto: valida seleccion FEFO por existencia/lote/caducidad/unidad antes de cualquier movimiento.
 * Contrato: no ejecuta DDL, no escribe BD, no aparta stock, no mueve inventario y no modifica unidades.
 */

$opciones = getopt("", array("folio::", "id::", "destino::", "origen::", "q::"));
$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 0;
$destino = isset($opciones["destino"]) ? intval($opciones["destino"]) : 4;
$origen = isset($opciones["origen"]) ? intval($opciones["origen"]) : 3;
$q = isset($opciones["q"]) ? trim((string) $opciones["q"]) : "";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$almacenes = new Almacenes();
if (!$almacenes->conexion_disponible_readonly()) {
    responder(array(
        "ok" => false,
        "modo" => "almacen_resurtido_plan_preparacion_readonly",
        "read_only" => true,
        "error_entorno" => "Conexion de BD no disponible",
        "guardrails" => guardrails()
    ), 1);
}

$filtros = array("q" => $q);
if ($folio !== "" || $id > 0) {
    $filtros["folio"] = $folio;
    $filtros["id_resurtido_almacen"] = $id;
} else {
    $filtros["id_almacen_destino"] = $destino;
    $filtros["id_almacen_origen"] = $origen;
}

$respuesta = $almacenes->plan_preparacion_resurtido_readonly($filtros);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$totales = isset($depurar["totales"]) && is_array($depurar["totales"]) ? $depurar["totales"] : array();
$plan = isset($depurar["plan"]) && is_array($depurar["plan"]) ? $depurar["plan"] : array();
$bloqueos = array();
$avisos = array();

if (!empty($respuesta["error"])) {
    $bloqueos[] = isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "Plan preparacion devolvio error";
}
if (intval(valor($depurar, array("read_only"), 0)) !== 1) {
    $bloqueos[] = "El plan no declara read_only";
}
if (intval(valor($depurar, array("movimientos_generados"), 1)) !== 0) {
    $bloqueos[] = "El plan reporto movimientos generados";
}
if (intval(valor($depurar, array("preparaciones_generadas"), 1)) !== 0) {
    $bloqueos[] = "El plan reporto preparaciones generadas";
}
if (count($plan) === 0) {
    $avisos[] = "No hay partidas planeadas con los filtros usados";
}
if (floatval(valor($totales, array("cantidad_faltante"), 0)) > 0) {
    $avisos[] = "El origen no cubre toda la cantidad requerida";
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_plan_preparacion_readonly",
    "read_only" => true,
    "respuesta" => array(
        "error" => !empty($respuesta["error"]),
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : null,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null,
        "schema_pendiente" => valor($depurar, array("schema_pendiente"), null)
    ),
    "totales" => $totales,
    "partidas" => count($plan),
    "primeras_partidas" => array_slice($plan, 0, 3),
    "guardrails" => guardrails(),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => "Usar este plan como evidencia antes de RES-T009 real; no sustituye bloqueo transaccional al confirmar."
), empty($bloqueos) ? 0 : 1);

function valor($data, $ruta, $default = null) {
    $actual = $data;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function guardrails() {
    return array(
        "no_ejecuta_ddl" => true,
        "no_escribe_bd" => true,
        "no_aparta_stock" => true,
        "no_mueve_kardex" => true,
        "no_modifica_unidades" => true,
        "no_toca_pos_ecommerce" => true
    );
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
