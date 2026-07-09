<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar si un usuario POS puede abrir turno sin escribir BD.
 * Impacto: confirma asignacion, caja, terminal y turnos existentes antes de apertura real.
 * Contrato: read-only; no crea turno ni movimiento de caja.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$montoInicial = 0;
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$observaciones = "";
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosTurnoDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosTurnoDb())->db();
$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$asignacionActiva = !empty($depurarAsignacion["asignacion_activa"]);
$datosAsignacion = $asignacionActiva && isset($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();

$bloqueos = array();
if ($idUsuario <= 0) {
    $bloqueos[] = "Indica --id_usuario=ID";
}
if ($montoInicial < 0) {
    $bloqueos[] = "El monto inicial no puede ser negativo";
}
foreach (isset($depurarAsignacion["bloqueos"]) && is_array($depurarAsignacion["bloqueos"]) ? $depurarAsignacion["bloqueos"] : array() as $bloqueo) {
    if (stripos($bloqueo, "No hay turno abierto") !== false) {
        continue;
    }
    $bloqueos[] = $bloqueo;
}

$turnoAbierto = null;
if ($asignacionActiva && tablaExiste($db, "erp_pos_turnos")) {
    $stmt = $db->prepare("SELECT id_turno_caja, folio, id_caja, id_almacen, id_usuario_apertura, monto_inicial, fecha_apertura
        FROM erp_pos_turnos
        WHERE id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
        ORDER BY fecha_apertura DESC, id_turno_caja DESC
        LIMIT 1");
    $stmt->execute(array(
        ":caja" => intval($datosAsignacion["id_caja"]),
        ":almacen" => intval($datosAsignacion["id_almacen"])
    ));
    $turnoAbierto = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($turnoAbierto) {
        $bloqueos[] = "Ya existe turno abierto para esta caja";
    }
} elseif (!tablaExiste($db, "erp_pos_turnos")) {
    $bloqueos[] = "Falta tabla erp_pos_turnos";
}
if (!tablaExiste($db, "erp_pos_movimientos_caja")) {
    $bloqueos[] = "Falta tabla erp_pos_movimientos_caja";
}
$observaciones = $observaciones !== "" ? $observaciones : "Apertura UAT POS posterior a cierre";
$numeroMonto = rtrim(rtrim(number_format($montoInicial, 6, ".", ""), "0"), ".");
$autorizacion = "AUTORIZO ABRIR TURNO POS UAT usando respaldo " . $respaldo
    . " con id_usuario=" . $idUsuario
    . " y monto_inicial=" . $numeroMonto
    . " observaciones=\"" . $observaciones . "\"";
$comando = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_apertura_apply_authorized.php"
    . " --autorizar=VENTAS_POS_TURNO_APERTURA"
    . " --respaldo=" . $respaldo
    . " --id_usuario=" . $idUsuario
    . " --monto_inicial=" . $numeroMonto
    . " --observaciones=\"" . $observaciones . "\"";
$autorizacionHumana = str_replace($respaldo, etiquetaRespaldoHumana($respaldo), $autorizacion);

echo json_encode(array(
    "ok" => !$asignacion["error"],
    "modo" => "read-only",
    "read_only" => true,
    "id_usuario" => $idUsuario,
    "monto_inicial" => $montoInicial,
    "puede_abrir_turno" => empty($bloqueos),
    "asignacion_activa" => $asignacionActiva,
    "asignacion" => $datosAsignacion,
    "turno_abierto_actual" => $turnoAbierto ?: null,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "autorizacion_sugerida" => empty($bloqueos) ? $autorizacionHumana : "",
    "comando_aplicador" => empty($bloqueos) ? $comando : "",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Puede ejecutarse apertura autorizada de turno."
        : "Resolver bloqueos antes de abrir turno real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function etiquetaRespaldoHumana($respaldo) {
    return basename((string) $respaldo) === "artianilocal_respaldo_completo_20260625_post_repair.sql"
        ? "UAT POS vigente"
        : $respaldo;
}
