<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: validar si el turno POS puede cerrarse antes de ejecutar el aplicador real.
 * Impacto: genera autorizacion sugerida y comando real sin escribir BD.
 * Contrato: read-only; no cierra turno, no modifica caja y no mueve dinero.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$montoContado = 0;
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$observaciones = "";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

if ($idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta --id_usuario=ID para ubicar asignacion POS."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
$bloqueos = array();

if (!empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($datosAsignacion)) {
    $bloqueos[] = "Usuario sin asignacion POS activa";
}
if (empty($turno)) {
    $bloqueos[] = "Usuario sin turno abierto";
}
if ($montoContado < 0) {
    $bloqueos[] = "Monto contado invalido";
}

$cierre = null;
if (empty($bloqueos)) {
    $cierre = $ventas->cierreTurnoDryRun(array(
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "monto_contado" => $montoContado
    ));
    $bloqueos = array_merge($bloqueos, valor(valor($cierre, "depurar", array()), "bloqueos", array()));
    if (!empty($cierre["error"])) {
        $bloqueos[] = valor($cierre, "mensaje", "Cierre dry-run con error");
    }
}

$depurarCierre = valor($cierre, "depurar", array());
$folioTurno = valor($turno, "folio", "TURNO");
if ($observaciones === "") {
    $observaciones = "Cierre UAT POS preflight " . $folioTurno;
}
$autorizacion = "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo " . $respaldo
    . " con id_usuario=" . $idUsuario
    . " monto_contado=" . numero($montoContado)
    . " observaciones=\"" . $observaciones . "\"";
$comando = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_cierre_apply_authorized.php"
    . " --autorizar=VENTAS_POS_TURNO_CIERRE"
    . " --respaldo=" . $respaldo
    . " --id_usuario=" . $idUsuario
    . " --monto_contado=" . numero($montoContado)
    . " --observaciones=\"" . $observaciones . "\"";
$autorizacionHumana = str_replace($respaldo, etiquetaRespaldoHumana($respaldo), $autorizacion);

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "turno_cierre_preflight_readonly",
    "read_only" => true,
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "folio_turno" => $folioTurno,
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turno)
    ),
    "resumen" => array(
        "monto_esperado" => valor($depurarCierre, "monto_esperado", null),
        "monto_contado" => $montoContado,
        "diferencia" => valor($depurarCierre, "diferencia", null),
        "bloqueos" => $bloqueos
    ),
    "autorizacion_sugerida" => empty($bloqueos) ? $autorizacionHumana : "",
    "comando_aplicador" => empty($bloqueos) ? $comando : "",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_modifica_caja" => true,
        "no_mueve_dinero" => true
    )
));

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function numero($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
}

function etiquetaRespaldoHumana($respaldo) {
    return basename((string) $respaldo) === "artianilocal_respaldo_completo_20260625_post_repair.sql"
        ? "UAT POS vigente"
        : $respaldo;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
