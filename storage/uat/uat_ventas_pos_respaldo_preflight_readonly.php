<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar referencia de respaldo externo antes de autorizar DDL Ventas/POS.
 * Impacto: prepara autorizacion sin crear respaldos, sin DDL y sin escrituras en BD.
 * Contrato: read-only; si la referencia es ruta local valida existencia/lectura/tamano.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "";
foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
$existe = false;
$legible = false;
$tamano = null;
$modificado = null;

if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
    $modificado = $existe ? date("Y-m-d H:i:s", filemtime($respaldo)) : null;
}

$okReferencia = strlen($respaldo) >= 8;
$okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
$ok = $okReferencia && $okRuta;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "modulo" => "ventas_pos_pedidos",
    "respaldo_externo_ref" => $respaldo,
    "validacion" => array(
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano,
        "modificado" => $modificado
    ),
    "siguiente_paso" => $ok
        ? "La referencia de respaldo puede adjuntarse a la solicitud de autorizacion DDL Ventas/POS."
        : "Indica una referencia de respaldo externo valida; si es ruta local debe existir, ser legible y tener tamano mayor a cero.",
    "comando_ejemplo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_respaldo_preflight_readonly.php --respaldo=\"RUTA_O_REFERENCIA\"",
    "reglas" => array(
        "Este preflight no crea respaldo, no modifica archivos y no escribe BD.",
        "No autoriza por si solo la ejecucion de DDL.",
        "La autorizacion debe mencionar explicitamente Ventas/POS/Pedidos y el alcance de tablas erp_pos*/erp_ventas*."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
