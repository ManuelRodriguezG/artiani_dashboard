<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: registrar evidencia UAT de un movimiento de caja POS solo con autorizacion explicita.
 * Impacto: inserta `erp_pos_movimientos_caja_evidencias` y cambia `evidencia_estado` del movimiento a recibida.
 * Contrato: BLOQUEADO por defecto; no sube archivos fisicos, puede registrar referencia externa/simulada.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$respaldoUatVigente = false;
$idUsuario = 0;
$idMovimiento = 0;
$tipoEvidencia = "ticket_firmado";
$referenciaExterna = "";
$descripcion = "";
$titulo = "";
$archivoRuta = "";
$archivoNombre = "";
$archivoMime = "";
$archivoHash = "";
$archivoTamano = null;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif ($arg === "--respaldo_uat_vigente=1") {
        $respaldoUatVigente = true;
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_movimiento_caja=") === 0) {
        $idMovimiento = intval(trim(substr($arg, 21), "\"' "));
    } elseif (strpos($arg, "--tipo_evidencia=") === 0) {
        $tipoEvidencia = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--referencia_externa=") === 0) {
        $referenciaExterna = trim(substr($arg, 21), "\"' ");
    } elseif (strpos($arg, "--descripcion=") === 0) {
        $descripcion = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--titulo=") === 0) {
        $titulo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--archivo_ruta=") === 0) {
        $archivoRuta = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--archivo_nombre=") === 0) {
        $archivoNombre = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--archivo_mime=") === 0) {
        $archivoMime = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--archivo_hash=") === 0) {
        $archivoHash = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--archivo_tamano=") === 0) {
        $archivoTamano = intval(trim(substr($arg, 17), "\"' "));
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIA_REAL"
    || !respaldoValido($respaldo, $respaldoUatVigente)
    || $idUsuario <= 0
    || $idMovimiento <= 0
    || $tipoEvidencia === ""
    || ($referenciaExterna === "" && $descripcion === "" && $archivoRuta === "")) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro evidencia de caja. Falta token, respaldo, usuario, movimiento o contenido de evidencia.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIA_REAL",
            "--respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1 o --respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_movimiento_caja=ID",
            "--tipo_evidencia=TIPO",
            "--referencia_externa=REFERENCIA o --descripcion=TEXTO o --archivo_ruta=RUTA"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->registrarEvidenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "id_movimiento_caja" => $idMovimiento,
    "tipo_evidencia" => $tipoEvidencia,
    "titulo" => $titulo,
    "descripcion" => $descripcion,
    "archivo_ruta" => $archivoRuta,
    "archivo_nombre" => $archivoNombre,
    "archivo_mime" => $archivoMime,
    "archivo_tamano" => $archivoTamano,
    "archivo_hash" => $archivoHash,
    "referencia_externa" => $referenciaExterna
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencia_real_uat",
    "respaldo_ref" => $respaldo,
    "id_movimiento_caja" => $idMovimiento,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar bandeja de evidencias recibidas y preparar revision/aprobacion."
        : "Resolver bloqueo antes de repetir evidencia."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function respaldoValido($respaldo, $respaldoUatVigente) {
    if ($respaldoUatVigente && strtolower((string) $respaldo) === "uat_pos_vigente") {
        return true;
    }
    return $respaldo !== "" && is_file($respaldo);
}
