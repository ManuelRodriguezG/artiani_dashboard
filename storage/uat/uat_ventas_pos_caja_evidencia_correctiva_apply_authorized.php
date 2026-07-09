<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: registrar evidencia correctiva UAT ligada a una correccion abierta solo con autorizacion explicita.
 * Impacto: inserta nueva evidencia en estado recibida_correccion y actualiza folio de correccion a en_revision.
 * Contrato: BLOQUEADO por defecto; no modifica evidencia original ni movimiento de caja.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$folio = "";
$tipoEvidencia = "ticket_firmado_correccion";
$referenciaExterna = "";
$descripcion = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--tipo_evidencia=") === 0) {
        $tipoEvidencia = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--referencia_externa=") === 0) {
        $referenciaExterna = trim(substr($arg, 21), "\"' ");
    } elseif (strpos($arg, "--descripcion=") === 0) {
        $descripcion = trim(substr($arg, 14), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL"
    || $respaldo === ""
    || !is_file($respaldo)
    || $idUsuario <= 0
    || $folio === ""
    || $tipoEvidencia === ""
    || ($referenciaExterna === "" && $descripcion === "")) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro evidencia correctiva. Falta token, respaldo, usuario, folio o contenido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECTIVA_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--folio=COR-EVC-...",
            "--referencia_externa=REFERENCIA o --descripcion=TEXTO"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->registrarEvidenciaCorrectivaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "folio" => $folio,
    "tipo_evidencia" => $tipoEvidencia,
    "referencia_externa" => $referenciaExterna,
    "descripcion" => $descripcion
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencia_correctiva_real_uat",
    "respaldo_ref" => $respaldo,
    "folio" => $folio,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar folio en_revision y preparar resolucion de correccion."
        : "Resolver bloqueo antes de repetir evidencia correctiva."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
