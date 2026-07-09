<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: cerrar turno POS UAT real solo con autorizacion explicita.
 * Impacto: actualiza `erp_pos_turnos` con monto contado, esperado, diferencia, usuario y fecha de cierre.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_TURNO_CIERRE, respaldo, usuario y monto contado.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$montoContado = null;
$observaciones = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_TURNO_CIERRE" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $montoContado === null || $montoContado < 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se cerro turno. Falta autorizacion, respaldo, usuario o monto contado valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_TURNO_CIERRE",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--monto_contado=MONTO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosTurnoCierreDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosTurnoCierreDb();
$db = $ventas->db();
$faltantes = tablasFaltantes($db, array(
    "erp_pos_cajas",
    "erp_pos_usuarios_cajas",
    "erp_pos_turnos",
    "erp_pos_movimientos_caja",
    "erp_ventas",
    "erp_ventas_pagos"
));
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta esquema POS antes de cerrar turno.",
        "tablas_faltantes" => $faltantes
    ));
}

$asignacionRespuesta = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacionRespuesta, "depurar", array());
$asignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
if (!empty($asignacionRespuesta["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($asignacion) || empty($turno)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Usuario sin asignacion POS activa o sin turno abierto.",
        "asignacion" => $depurarAsignacion
    ));
}

$idCaja = intval(valor($asignacion, "id_caja", 0));
$idAlmacen = intval(valor($asignacion, "id_almacen", 0));
$idTurno = intval(valor($turno, "id_turno_caja", 0));

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id_turno_caja, folio, estatus
        FROM erp_pos_turnos
        WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen
        LIMIT 1 FOR UPDATE");
    $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja, ":almacen" => $idAlmacen));
    $turnoBloqueado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turnoBloqueado || $turnoBloqueado["estatus"] !== "abierto") {
        throw new Exception("El turno ya no esta abierto para la caja asignada");
    }

    $cierre = $ventas->cierreTurnoDryRun(array(
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "monto_contado" => $montoContado
    ));
    $depurarCierre = valor($cierre, "depurar", array());
    $bloqueos = valor($depurarCierre, "bloqueos", array());
    if (!empty($cierre["error"]) || !empty($bloqueos)) {
        throw new Exception("Cierre bloqueado por dry-run: " . implode("; ", $bloqueos));
    }

    $montoEsperado = round(floatval(valor($depurarCierre, "monto_esperado", 0)), 6);
    $diferencia = round(floatval(valor($depurarCierre, "diferencia", 0)), 6);

    $stmt = $db->prepare("UPDATE erp_pos_turnos
        SET id_usuario_cierre=:usuario,
            monto_esperado=:esperado,
            monto_contado=:contado,
            diferencia=:diferencia,
            estatus='cerrado',
            fecha_cierre=NOW(),
            observaciones_cierre=:observaciones
        WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'");
    $stmt->execute(array(
        ":usuario" => $idUsuario,
        ":esperado" => $montoEsperado,
        ":contado" => round(floatval($montoContado), 6),
        ":diferencia" => $diferencia,
        ":observaciones" => $observaciones,
        ":turno" => $idTurno,
        ":caja" => $idCaja,
        ":almacen" => $idAlmacen
    ));

    if ($stmt->rowCount() !== 1) {
        throw new Exception("No se actualizo el turno; posible cambio concurrente");
    }

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "turno_cerrado",
        "respaldo_ref" => $respaldo,
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "folio" => $turnoBloqueado["folio"],
        "monto_esperado" => $montoEsperado,
        "monto_contado" => round(floatval($montoContado), 6),
        "diferencia" => $diferencia,
        "resumen" => valor($depurarCierre, "resumen", array()),
        "siguiente_paso" => "Registrar evidencia en docs/erp_ventas_pos_evidencia_uat.md y validar que no quede turno abierto."
    ));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage()
    ));
}

function tablasFaltantes($db, $tablas) {
    $faltantes = array();
    foreach ($tablas as $tabla) {
        $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
        $stmt->execute(array(":tabla" => $tabla));
        if (!$stmt->fetchColumn()) {
            $faltantes[] = $tabla;
        }
    }
    return $faltantes;
}

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

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
