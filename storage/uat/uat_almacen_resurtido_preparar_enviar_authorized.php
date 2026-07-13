<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: arnes autorizado para futura prueba RES-T009 preparar/enviar.
 * Impacto: define token, respaldo y alcance para acciones que si moveran inventario cuando se implementen.
 * Contrato actual: bloqueado por defecto; aun con autorizacion no mueve inventario porque la implementacion real esta pendiente.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::", "folio::", "id::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 0;
$token = "ALMACEN_RESURTIDO_PREPARAR_ENVIAR_UAT";
$frase = "AUTORIZO UAT PREPARAR ENVIAR RESURTIDO usando respaldo RUTA_O_REFERENCIA";
$validacion = validarRespaldoResurtidoAccion($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se preparo ni envio resurtido. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_O_REFERENCIA_RESPALDO",
            "folio" => "RES-YYYYMMDD-####"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcancePrepararEnviar()
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$respuesta = $modelo->preparar_enviar_resurtido_pendiente(array(
    "folio" => $folio,
    "id_resurtido_almacen" => $id
), 0);

responder(array(
    "ok" => false,
    "modo" => "backend_pendiente",
    "mensaje" => "Arnes autorizado ejecuto contrato backend; no se preparo ni envio resurtido.",
    "folio" => $folio,
    "id_resurtido_almacen" => $id,
    "respuesta_backend" => $respuesta,
    "validacion_respaldo" => $validacion,
    "preflight_requerido" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_preparacion_envio_preflight.php --folio={$folio}",
    "alcance" => alcancePrepararEnviar(),
    "guardrails" => array(
        "no_ejecuto_movimientos" => true,
        "no_modifico_existencias" => true,
        "no_modifico_unidades" => true,
        "no_toco_pos_ecommerce" => true
    )
), 1);

function validarRespaldoResurtidoAccion($respaldo) {
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

function alcancePrepararEnviar() {
    return array(
        "futuro_crea_preparacion" => true,
        "futuro_crea_envio" => true,
        "futuro_salida_origen" => true,
        "futuro_entrada_transito" => true,
        "futuro_mueve_inventario" => true,
        "futuro_modifica_unidades" => true,
        "toca_pos" => false,
        "toca_ecommerce" => false,
        "implementacion_actual" => "pendiente"
    );
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
