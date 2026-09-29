<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: aplicador protegido del esquema persistente de estudios de rentabilidad.
 * Impacto: crea tablas para guardar estudios y sus SKUs solo con autorizacion explicita.
 * Contrato: por defecto dry-run; ejecutar requiere --execute, respaldo externo y frase exacta.
 */

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

$frase = "AUTORIZO APLICAR ESQUEMA ESTUDIOS RENTABILIDAD";
$puedeEjecutar = $ejecutar && strlen($respaldo) >= 8 && $confirmacion === $frase;
$respuesta = $modelo->planEstudiosRentabilidad($puedeEjecutar);
$resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
$errores = intval(isset($resumen["errores"]) ? $resumen["errores"] : 0);

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($respuesta["error"]) && $errores === 0 && (!$ejecutar || $puedeEjecutar),
    "modo" => $puedeEjecutar ? "execute" : "dry-run",
    "ejecucion_solicitada" => $ejecutar,
    "ejecucion_autorizada" => $puedeEjecutar,
    "frase_requerida" => $frase,
    "respaldo_externo_ref" => $respaldo,
    "mensaje" => $puedeEjecutar ? $respuesta["mensaje"] : "Dry-run: esquema no ejecutado",
    "bloqueo" => $ejecutar && !$puedeEjecutar ? "Falta respaldo externo o frase exacta de autorizacion" : null,
    "resumen" => $resumen,
    "tablas" => isset($respuesta["depurar"]["tablas"]) ? $respuesta["depurar"]["tablas"] : array(),
    "reglas" => array(
        "No ejecutar sin respaldo externo vigente.",
        "Este esquema solo permite guardar estudios; no actualiza precios ni costos.",
        "Guardar estudios mantiene su propio permiso y frase de autorizacion."
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
