<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: aplicar esquema Ventas/POS solo con autorizacion explicita y respaldo validado.
 * Impacto: crea tablas `erp_pos*` y `erp_ventas*` cuando el dueño autorice.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_DDL_BASE o VENTAS_POS_DDL_EXPANDIDO y --respaldo=...
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
$alcance = "";
if ($autorizar === "VENTAS_POS_DDL_BASE") {
    $alcance = "base";
}
if ($autorizar === "VENTAS_POS_DDL_EXPANDIDO") {
    $alcance = "expandido";
}
if ($alcance === "" || !$validacionRespaldo["ok"]) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto DDL. Falta autorizacion explicita o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_DDL_BASE para POS/caja/ventas",
            "--autorizar=VENTAS_POS_DDL_EXPANDIDO",
            "--respaldo=RUTA_O_REFERENCIA"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "reglas" => array(
            "No ejecutar sin respaldo externo verificado.",
            "No ejecutar sin autorizacion textual del dueño.",
            "Despues de ejecutar, correr UAT read-only y diagnostico."
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$resultado = $esquema->planActualizarVentasPos(true, $alcance);

echo json_encode(array(
    "ok" => true,
    "modo" => "ddl_ejecutado",
    "alcance" => $alcance,
    "respaldo_ref" => $respaldo,
    "resultado" => $resultado,
    "siguiente_paso" => "Crear/validar cajas iniciales y ejecutar UAT Ventas/POS."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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
