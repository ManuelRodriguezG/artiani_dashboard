<?php

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

if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
}

$okReferencia = strlen($respaldo) >= 8;
$okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
$ok = $okReferencia && $okRuta;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "respaldo_externo_ref" => $respaldo,
    "validacion" => array(
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    ),
    "siguiente_paso" => $ok
        ? "El respaldo puede usarse como referencia para solicitar autorizacion del esquema."
        : "Indica una referencia de respaldo externo valida; si es ruta local debe existir y ser legible.",
    "comando_ejemplo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php --respaldo=\"RUTA_O_REFERENCIA\"",
    "reglas" => array(
        "Este preflight no crea respaldo, no modifica archivos y no escribe BD.",
        "Si la referencia es una ruta local, valida existencia, lectura y tamano mayor a cero.",
        "Si la referencia no es ruta local, solo valida longitud minima como referencia externa."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

