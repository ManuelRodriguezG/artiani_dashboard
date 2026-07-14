<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: arnes autorizado para futura prueba RES-T008 autorizar/rechazar.
 * Impacto: define token, respaldo y alcance para autorizacion documental de resurtido.
 * Contrato actual: bloqueado por defecto; con DDL aplicado y autorizacion procesa folios sin mover inventario.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::", "folio::", "id::", "accion::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 0;
$accion = isset($opciones["accion"]) ? trim((string) $opciones["accion"]) : "autorizar";
$token = "ALMACEN_RESURTIDO_AUTORIZAR_UAT";
$frase = "AUTORIZO UAT AUTORIZAR RESURTIDO usando respaldo RUTA_O_REFERENCIA";
$validacion = validarRespaldoResurtidoAccion($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacion["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se autorizo ni rechazo resurtido. Falta token, confirmacion textual o respaldo valido.",
        "requerido" => array(
            "autorizar" => $token,
            "confirmacion" => $frase,
            "respaldo" => "RUTA_O_REFERENCIA_RESPALDO",
            "folio" => "RES-YYYYMMDD-####",
            "accion" => "autorizar|rechazar"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => alcanceAutorizar()
    ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$respuesta = $modelo->autorizar_resurtido_pendiente(array(
    "folio" => $folio,
    "id_resurtido_almacen" => $id,
    "accion" => $accion
), 0);

$okBackend = empty($respuesta["error"]) && (
    intval(isset($respuesta["depurar"]["autorizado"]) ? $respuesta["depurar"]["autorizado"] : 0) === 1 ||
    intval(isset($respuesta["depurar"]["rechazado"]) ? $respuesta["depurar"]["rechazado"] : 0) === 1
);

responder(array(
    "ok" => $okBackend,
    "modo" => $okBackend ? "almacen_resurtido_autorizar_uat" : "backend_pendiente_o_bloqueado",
    "mensaje" => $okBackend ? "Folio procesado por backend de autorizacion." : "Arnes autorizado ejecuto contrato backend; no se modifico folio.",
    "folio" => $folio,
    "id_resurtido_almacen" => $id,
    "accion" => $accion,
    "respuesta_backend" => $respuesta,
    "validacion_respaldo" => $validacion,
    "alcance" => alcanceAutorizar(),
    "guardrails" => array(
        "autorizo_o_rechazo" => $okBackend,
        "modifico_folio" => $okBackend,
        "no_ejecuto_movimientos" => true,
        "no_toco_pos_ecommerce" => true
    )
), $okBackend ? 0 : 1);

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

function alcanceAutorizar() {
    return array(
        "futuro_autoriza_encabezado" => true,
        "futuro_autoriza_detalle" => true,
        "futuro_rechaza_lineas" => true,
        "futuro_mueve_inventario" => false,
        "toca_pos" => false,
        "toca_ecommerce" => false,
        "implementacion_actual" => "lista_post_ddl"
    );
}

function responder($datos, $codigoSalida) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($codigoSalida);
}
