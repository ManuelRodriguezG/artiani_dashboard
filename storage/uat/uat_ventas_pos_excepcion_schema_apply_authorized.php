<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: aplicar DDL de excepciones comerciales POS solo con autorizacion explicita.
 * Impacto: crea/expande estructura para precio manual, descuentos, politica, supervisor y snapshot.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_EXCEPCION_DDL y respaldo valido.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_EXCEPCION_DDL" || !$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de excepciones comerciales. Falta autorizacion explicita o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_EXCEPCION_DDL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarExcepcionesComerciales();
$plan = $esquema->planActualizarExcepcionesComerciales(true);
$despues = $esquema->auditarExcepcionesComerciales();

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_excepcion_schema_aplicado",
    "respaldo_ref" => $respaldo,
    "antes" => $antes,
    "plan" => $plan,
    "despues" => $despues,
    "siguiente_paso" => "Registrar evidencia y preparar semilla/politica UAT de excepcion comercial con autorizacion separada."
));

function validarRespaldo($respaldo) {
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
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
