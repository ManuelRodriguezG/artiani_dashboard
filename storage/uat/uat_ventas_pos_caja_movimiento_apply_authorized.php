<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: registrar movimiento real de caja POS solo con autorizacion explicita.
 * Impacto: inserta `erp_pos_movimientos_caja` y actualiza esperado de turno para gastos/retiros/entradas/vales/reembolsos.
 * Contrato: BLOQUEADO por defecto; requiere DDL caja completa, respaldo, usuario y --autorizar=VENTAS_POS_CAJA_MOVIMIENTO_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$tipo = "";
$motivo = "";
$monto = 0;
$referencia = "";
$responsable = "";
$observaciones = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--referencia=") === 0) {
        $referencia = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--responsable=") === 0) {
        $responsable = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_CAJA_MOVIMIENTO_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $monto <= 0 || $tipo === "" || $motivo === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro movimiento de caja. Falta autorizacion, respaldo, usuario, tipo, motivo o monto valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_MOVIMIENTO_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--tipo=gasto_caja|retiro_efectivo|entrada_extraordinaria|vale_interno|reembolso_cliente",
            "--motivo=TEXTO",
            "--monto=MONTO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

class UatVentasPosCajaMovimientoDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosCajaMovimientoDb();
$db = $ventas->db();
$esquema = new VentasErpEsquema();
$auditoria = $esquema->auditarCajaCompleta();
$faltantes = faltantesCajaCompleta($auditoria);
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado_schema",
        "mensaje" => "No se registro movimiento. Falta DDL de caja completa.",
        "faltantes" => $faltantes,
        "siguiente_paso" => "Aplicar primero VENTAS_POS_CAJA_DDL con respaldo externo y autorizacion."
    ));
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
if (!empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($turno)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No hay asignacion POS activa o turno abierto para registrar movimiento.",
        "asignacion" => $depurarAsignacion
    ));
}

$datosMovimiento = array(
    "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "tipo_movimiento" => $tipo,
    "motivo" => $motivo,
    "monto" => $monto,
    "referencia" => $referencia,
    "responsable" => $responsable,
    "observaciones" => $observaciones
);

$dryRun = $ventas->movimientoCajaDryRun($datosMovimiento);
$depurarDryRun = valor($dryRun, "depurar", array());
$bloqueos = valor($depurarDryRun, "bloqueos", array());
if (!empty($dryRun["error"]) || !empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado_preflight",
        "mensaje" => "No se registro movimiento; dry-run con bloqueos.",
        "bloqueos" => $bloqueos,
        "dry_run" => $dryRun
    ));
}

$movimiento = valor($depurarDryRun, "movimiento", array());
$impacto = round(floatval(valor($movimiento, "impacto_esperado", 0)), 6);
$tipoCaja = valor($movimiento, "tipo_caja", "");
$motivoCaja = valor($movimiento, "motivo_caja", $motivo);
$requiereAutorizacion = in_array($tipo, array("entrada_extraordinaria", "retiro_efectivo", "gasto_caja", "vale_interno", "reembolso_cliente")) ? 1 : 0;
$requiereEvidencia = in_array($tipo, array("gasto_caja", "reembolso_cliente")) ? 1 : 0;

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id_turno_caja, estatus
        FROM erp_pos_turnos
        WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen
        LIMIT 1 FOR UPDATE");
    $stmt->execute(array(
        ":turno" => intval($datosMovimiento["id_turno_caja"]),
        ":caja" => intval($datosMovimiento["id_caja"]),
        ":almacen" => intval($datosMovimiento["id_almacen"])
    ));
    $turnoBloqueado = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turnoBloqueado || $turnoBloqueado["estatus"] !== "abierto") {
        throw new Exception("El turno ya no esta abierto para la caja asignada");
    }

    $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
        (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus,
         referencia, responsable, requiere_autorizacion, autorizado_por, fecha_autorizacion,
         requiere_evidencia, evidencia_estado, observaciones, creado_por, fecha_registro)
        VALUES (:turno, :caja, :almacen, :tipo, :categoria, :motivo, :monto, 'registrado',
         :referencia, :responsable, :requiere_autorizacion, :autorizado_por, NOW(),
         :requiere_evidencia, :evidencia_estado, :observaciones, :usuario, NOW())");
    $stmt->execute(array(
        ":turno" => intval($datosMovimiento["id_turno_caja"]),
        ":caja" => intval($datosMovimiento["id_caja"]),
        ":almacen" => intval($datosMovimiento["id_almacen"]),
        ":tipo" => $tipoCaja,
        ":categoria" => $tipo,
        ":motivo" => $motivoCaja,
        ":monto" => round(floatval($monto), 6),
        ":referencia" => $referencia !== "" ? $referencia : null,
        ":responsable" => $responsable !== "" ? $responsable : null,
        ":requiere_autorizacion" => $requiereAutorizacion,
        ":autorizado_por" => $idUsuario,
        ":requiere_evidencia" => $requiereEvidencia,
        ":evidencia_estado" => $requiereEvidencia ? "pendiente" : null,
        ":observaciones" => $observaciones,
        ":usuario" => $idUsuario
    ));
    $idMovimientoCaja = intval($db->lastInsertId());

    $db->prepare("UPDATE erp_pos_turnos
        SET monto_esperado=ROUND(monto_esperado+:impacto, 6)
        WHERE id_turno_caja=:turno")
        ->execute(array(":impacto" => $impacto, ":turno" => intval($datosMovimiento["id_turno_caja"])));

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "movimiento_caja_real",
        "respaldo_ref" => $respaldo,
        "id_movimiento_caja" => $idMovimientoCaja,
        "id_usuario" => $idUsuario,
        "id_turno_caja" => intval($datosMovimiento["id_turno_caja"]),
        "id_caja" => intval($datosMovimiento["id_caja"]),
        "id_almacen" => intval($datosMovimiento["id_almacen"]),
        "movimiento" => $movimiento,
        "impacto_esperado" => $impacto,
        "siguiente_paso" => "Ejecutar cierre dry-run y documentar impacto en corte."
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

function faltantesCajaCompleta($auditoria) {
    $depurar = valor($auditoria, "depurar", array());
    $faltantes = array();
    foreach (valor($depurar, "columnas", array()) as $columna) {
        if (empty($columna["existe"])) {
            $faltantes[] = "columna:" . valor($columna, "columna", "");
        }
    }
    foreach (valor($depurar, "indices", array()) as $indice) {
        if (empty($indice["existe"])) {
            $faltantes[] = "indice:" . valor($indice, "indice", "");
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
