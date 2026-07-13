<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: crear una solicitud UAT de resurtido solo con autorizacion explicita y respaldo externo.
 * Impacto: prueba RES-T008 despues de aplicar DDL; crea encabezado/detalle, no mueve inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar, --confirmacion y --respaldo.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::", "destino::", "origen::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$idDestino = isset($opciones["destino"]) ? intval($opciones["destino"]) : 4;
$idOrigen = isset($opciones["origen"]) ? intval($opciones["origen"]) : 0;
$token = "ALMACEN_RESURTIDO_GUARDAR_UAT";
$frase = "AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA";
$validacion = validarRespaldoResurtidoGuardar($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo solicitud UAT de resurtido. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_O_REFERENCIA_RESPALDO",
            "destino" => "ID_ALMACEN_TIENDA",
            "origen" => "ID_ALMACEN_ORIGEN opcional"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcanceGuardarResurtido()
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$filtros = array("id_almacen_destino" => $idDestino);
if ($idOrigen > 0) {
    $filtros["id_almacen_origen"] = $idOrigen;
}
$payload = $modelo->payload_solicitud_resurtido_readonly($filtros);
if (!empty($payload["error"])) {
    responder(array(
        "ok" => false,
        "modo" => "payload_error",
        "payload" => $payload,
        "alcance" => alcanceGuardarResurtido()
    ), 1);
}

$datosPayload = isset($payload["depurar"]["payload"]) ? $payload["depurar"]["payload"] : array();
$guardado = $modelo->guardar_solicitud_resurtido(array("payload" => json_encode($datosPayload)), 0);

responder(array(
    "ok" => empty($guardado["error"]) && intval(isset($guardado["depurar"]["guardado"]) ? $guardado["depurar"]["guardado"] : 0) === 1,
    "modo" => "almacen_resurtido_guardar_uat",
    "destino" => $idDestino,
    "origen" => $idOrigen,
    "validacion_respaldo" => $validacion,
    "payload_resumen" => array(
        "puede_enviar_post" => isset($payload["depurar"]["puede_enviar_post"]) ? $payload["depurar"]["puede_enviar_post"] : null,
        "lineas" => isset($datosPayload["detalle"]) && is_array($datosPayload["detalle"]) ? count($datosPayload["detalle"]) : 0
    ),
    "guardado" => $guardado,
    "alcance" => alcanceGuardarResurtido(),
    "siguiente_paso" => "Consultar folio, validar detalle y mantener sin movimientos hasta RES-T009."
), empty($guardado["error"]) ? 0 : 1);

function validarRespaldoResurtidoGuardar($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "referencia" => $respaldo,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function alcanceGuardarResurtido() {
    return array(
        "crea_encabezado_resurtido" => true,
        "crea_detalle_resurtido" => true,
        "mueve_inventario" => false,
        "aparta_stock" => false,
        "crea_preparacion" => false,
        "crea_envio" => false,
        "crea_recepcion" => false,
        "toca_pos" => false,
        "toca_ecommerce" => false
    );
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
