<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: UAT read-only del payload RES-T009 preparar/enviar.
 * Impacto: valida contrato de POST futuro sin apartar stock, sin crear preparaciones y sin movimientos.
 * Contrato: no ejecuta DDL, no escribe BD, no mueve inventario, no modifica unidades y no toca POS/ecommerce.
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
        "modo" => "almacen_resurtido_payload_preparacion_envio_readonly",
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

$respuesta = $almacenes->payload_preparacion_envio_resurtido_readonly($filtros);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$payload = isset($depurar["payload"]) && is_array($depurar["payload"]) ? $depurar["payload"] : array();
$preparaciones = isset($payload["preparaciones"]) && is_array($payload["preparaciones"]) ? $payload["preparaciones"] : array();
$bloqueos = array();
$avisos = array();

if (!empty($respuesta["error"])) {
    $bloqueos[] = isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "Payload preparacion/envio devolvio error";
}
if (intval(valor($depurar, array("read_only"), 0)) !== 1) {
    $bloqueos[] = "El payload no declara read_only";
}
if (intval(valor($depurar, array("movimientos_generados"), 1)) !== 0) {
    $bloqueos[] = "El payload reporto movimientos generados";
}
if (intval(valor($depurar, array("preparaciones_generadas"), 1)) !== 0) {
    $bloqueos[] = "El payload reporto preparaciones generadas";
}
if (intval(valor($depurar, array("puede_enviar_post"), 1)) !== 0) {
    $bloqueos[] = "El payload quedo habilitado para POST antes de DDL/cobertura completa";
}
if (count($preparaciones) === 0) {
    $avisos[] = "Payload sin preparaciones candidatas";
}
if (count(valor($depurar, array("advertencias"), array())) > 0) {
    $avisos[] = "Payload con advertencias operativas";
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_payload_preparacion_envio_readonly",
    "read_only" => true,
    "respuesta" => array(
        "error" => !empty($respuesta["error"]),
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : null,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null,
        "schema_pendiente" => valor($depurar, array("schema_pendiente"), null)
    ),
    "endpoint_futuro" => valor($depurar, array("endpoint_futuro"), null),
    "puede_enviar_post" => valor($depurar, array("puede_enviar_post"), null),
    "preparaciones_payload" => count($preparaciones),
    "bloqueos_payload" => valor($depurar, array("bloqueos"), array()),
    "advertencias_payload" => valor($depurar, array("advertencias"), array()),
    "guardrails" => guardrails(),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => "Usar este payload como contrato; el POST real debe revalidar y bloquear saldos transaccionalmente."
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
