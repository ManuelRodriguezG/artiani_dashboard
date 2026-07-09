<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: simular cierre POS cuadrado, con faltante y con sobrante sin escribir BD.
 * Impacto: valida que la diferencia no bloquea cierre y alimenta reportes.
 * Contrato: read-only; no cierra turno ni modifica caja.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$delta = 10.0;
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--delta=") === 0) {
        $delta = max(0.01, floatval(trim(substr($arg, 8), "\"' ")));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

if (empty($depurarAsignacion["asignacion_activa"]) || empty($turno)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "cierre_diferencias_readonly",
        "read_only" => true,
        "mensaje" => "No hay turno abierto para simular diferencias.",
        "asignacion" => $depurarAsignacion,
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_cierra_turno" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$esperado = round(floatval(valor($turno, "monto_esperado", 0)), 6);
if ($esperado <= 0) {
    $base = $ventas->cierreTurnoDryRun(array(
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "monto_contado" => 0
    ));
    $esperado = round(floatval(valor(valor($base, "depurar", array()), "monto_esperado", 0)), 6);
}
$escenarios = array(
    "cuadrado" => $esperado,
    "faltante" => max(0, $esperado - $delta),
    "sobrante" => $esperado + $delta
);
$resultados = array();
foreach ($escenarios as $nombre => $montoContado) {
    $resultados[$nombre] = $ventas->cierreTurnoDryRun(array(
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "monto_contado" => $montoContado
    ));
}

echo json_encode(array(
    "ok" => true,
    "modo" => "cierre_diferencias_readonly",
    "read_only" => true,
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
        "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
        "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
        "folio_turno" => valor($turno, "folio", ""),
        "monto_esperado" => $esperado,
        "delta" => $delta
    ),
    "resultados" => $resultados,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "permite_validar_faltante" => true,
        "permite_validar_sobrante" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
