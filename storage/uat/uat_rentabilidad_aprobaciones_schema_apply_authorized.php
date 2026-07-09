<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";

$modelo = new RentabilidadEsquema();

$ejecutar = in_array("--execute", isset($argv) ? $argv : array(), true);
$respaldo = "";
$confirmacion = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11));
    }
    if (strpos($arg, "--confirmar=") === 0) {
        $confirmacion = trim(substr($arg, 12));
    }
}

$frase = "AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS";
$puedeEjecutar = $ejecutar && strlen($respaldo) >= 8 && $confirmacion === $frase;
$respuesta = $modelo->planAprobacionesComerciales($puedeEjecutar);

$resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
echo json_encode(array(
    "ok" => empty($respuesta["error"]) && (!$ejecutar || $puedeEjecutar),
    "modo" => $puedeEjecutar ? "execute" : "dry-run",
    "ejecucion_solicitada" => $ejecutar,
    "ejecucion_autorizada" => $puedeEjecutar,
    "frase_requerida" => $frase,
    "respaldo_externo_ref" => $respaldo,
    "mensaje" => $puedeEjecutar ? $respuesta["mensaje"] : "Dry-run: esquema no ejecutado",
    "bloqueo" => $ejecutar && !$puedeEjecutar ? "Falta respaldo externo o frase exacta de autorizacion" : null,
    "resumen" => $resumen,
    "tablas" => isset($respuesta["depurar"]["tablas"]) ? $respuesta["depurar"]["tablas"] : array()
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

