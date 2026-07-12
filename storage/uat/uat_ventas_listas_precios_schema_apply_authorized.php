<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: aplicar DDL minimo de Listas de precios solo con autorizacion explicita.
 * Impacto: agrega contrato CRM/listas y auditoria comercial para guardado UAT.
 * Contrato: BLOQUEADO por defecto; requiere dos tokens y respaldo valido.
 */

$args = isset($argv) ? $argv : array();
$autorizarCrm = "";
$autorizarAuditoria = "";
$respaldo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar_crm=") === 0) {
        $autorizarCrm = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--autorizar_auditoria=") === 0) {
        $autorizarAuditoria = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizarCrm !== "VENTAS_LISTAS_PRECIOS_CRM_DDL" || $autorizarAuditoria !== "VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL" || !$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de Listas de precios. Falta autorizacion explicita o respaldo valido.",
        "requerido" => array(
            "--autorizar_crm=VENTAS_LISTAS_PRECIOS_CRM_DDL",
            "--autorizar_auditoria=VENTAS_LISTAS_PRECIOS_AUDITORIA_DDL",
            "--respaldo=RUTA_O_REFERENCIA_RESPALDO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = array(
    "crm" => $esquema->auditarListasPreciosCrm(),
    "auditoria" => $esquema->auditarAuditoriaListasPrecios()
);
$planCrm = $esquema->planActualizarListasPreciosCrm(true);
$planAuditoria = $esquema->planActualizarAuditoriaListasPrecios(true);
$despues = array(
    "crm" => $esquema->auditarListasPreciosCrm(),
    "auditoria" => $esquema->auditarAuditoriaListasPrecios()
);

responder(array(
    "ok" => true,
    "modo" => "ventas_listas_precios_schema_aplicado",
    "respaldo_ref" => $respaldo,
    "antes" => $antes,
    "plan" => array(
        "crm" => $planCrm,
        "auditoria" => $planAuditoria
    ),
    "despues" => $despues,
    "siguiente_paso" => "Ejecutar preflight, aplicar permisos ventas.listas.* y probar guardado UAT en borrador."
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
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($datos["ok"]) ? 0 : 1);
}
