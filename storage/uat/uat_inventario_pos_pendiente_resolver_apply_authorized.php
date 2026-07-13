<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: ejecutar UAT real de resolucion de pendiente POS desde Inventario/Existencias.
 * Impacto: puede crear ajuste, salida de venta pendiente, actualizar pendiente/venta/detalle/notificacion.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo/referencia vigente, usuario, folio, conteo, decision y confirmacion literal.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$folio = "";
$cantidadFisica = null;
$decision = "ajustar_a_conteo";
$confirmacion = "";
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--cantidad_fisica=") === 0) {
        $cantidadFisica = floatval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "INVENTARIO_POS_PENDIENTE_RESOLVER_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $folio === "" || $cantidadFisica === null || $confirmacion !== "RESOLVER PENDIENTE" || $motivo === "") {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se resolvio pendiente POS. Falta token, respaldo, usuario, folio, conteo, motivo o confirmacion literal.",
        "requisitos" => array(
            "--autorizar=INVENTARIO_POS_PENDIENTE_RESOLVER_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE_O_REFERENCIA_VIGENTE",
            "--id_usuario=ID",
            "--folio=PINV-...",
            "--cantidad_fisica=CANTIDAD",
            "--decision=ajustar_a_conteo|cerrar_sin_ajuste|mantener_pendiente",
            "--confirmacion=\"RESOLVER PENDIENTE\"",
            "--motivo=TEXTO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/InventarioErp.php";

$inventario = new InventarioErp();
$datos = array(
    "folio" => $folio,
    "cantidad_fisica" => $cantidadFisica,
    "decision" => $decision,
    "motivo" => $motivo
);

$dryRun = $inventario->resolucionPendientePosInventarioDryRun($datos, $idUsuario);
$dry = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
$bloqueos = isset($dry["bloqueos"]) && is_array($dry["bloqueos"]) ? $dry["bloqueos"] : array();
if (!empty($dryRun["error"]) || !empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "resolver_pendiente_pos_uat_bloqueado_por_dry_run",
        "dry_run" => $dryRun
    ));
}

$respuesta = $inventario->resolverPendientePosInventarioReal($datos, $idUsuario);
responder(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "resolver_pendiente_pos_uat_real",
    "respaldo" => $validacionRespaldo,
    "dry_run_previo" => array(
        "propuesta" => isset($dry["propuesta"]) ? $dry["propuesta"] : null,
        "conteo" => isset($dry["conteo"]) ? $dry["conteo"] : null
    ),
    "respuesta" => $respuesta
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "") {
        return array("ok" => false, "referencia" => $respaldo);
    }
    $pareceRuta = preg_match('/^[A-Za-z]:\\\\/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    if ($pareceRuta) {
        return array("ok" => is_file($respaldo), "referencia" => $respaldo, "parece_ruta_local" => true, "archivo_existe" => is_file($respaldo));
    }
    return array("ok" => true, "referencia" => $respaldo, "parece_ruta_local" => false, "archivo_existe" => false);
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

