<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: abrir turno POS real solo con autorizacion explicita y respaldo validado.
 * Impacto: inserta `erp_pos_turnos` y movimiento inicial de caja en una transaccion.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_TURNO_APERTURA, respaldo, usuario y monto.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$montoInicial = null;
$observaciones = "";
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    }
    if (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_TURNO_APERTURA" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $montoInicial === null || $montoInicial < 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se abrio turno. Falta autorizacion, respaldo, usuario o monto inicial valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_TURNO_APERTURA",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--monto_inicial=MONTO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosTurnoAperturaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosTurnoAperturaDb())->db();
$ventas = new VentasErp();
$faltantes = tablasFaltantes($db, array(
    "erp_pos_cajas",
    "erp_pos_terminales",
    "erp_pos_usuarios_cajas",
    "erp_pos_turnos",
    "erp_pos_movimientos_caja"
));
if (!empty($faltantes)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta esquema POS antes de abrir turno.",
        "tablas_faltantes" => $faltantes
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$asignacionRespuesta = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacionRespuesta["depurar"]) && is_array($asignacionRespuesta["depurar"]) ? $asignacionRespuesta["depurar"] : array();
if (!empty($asignacionRespuesta["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($depurarAsignacion["asignacion"])) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Usuario sin asignacion POS activa.",
        "asignacion" => $depurarAsignacion
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$asignacion = $depurarAsignacion["asignacion"];
$idCaja = intval($asignacion["id_caja"]);
$idAlmacen = intval($asignacion["id_almacen"]);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id_turno_caja, folio
        FROM erp_pos_turnos
        WHERE id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
        LIMIT 1 FOR UPDATE");
    $stmt->execute(array(":caja" => $idCaja, ":almacen" => $idAlmacen));
    $abierto = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($abierto) {
        throw new Exception("Ya existe turno abierto para esta caja: " . $abierto["folio"]);
    }

    $folio = siguienteFolioTurno($db, $idCaja);
    $stmt = $db->prepare("INSERT INTO erp_pos_turnos
        (folio, id_caja, id_almacen, id_usuario_apertura, monto_inicial, monto_esperado, monto_contado, diferencia, estatus, fecha_apertura, observaciones_apertura)
        VALUES (:folio, :caja, :almacen, :usuario, :monto, :monto_esperado, 0, 0, 'abierto', NOW(), :observaciones)");
    $stmt->execute(array(
        ":folio" => $folio,
        ":caja" => $idCaja,
        ":almacen" => $idAlmacen,
        ":usuario" => $idUsuario,
        ":monto" => $montoInicial,
        ":monto_esperado" => $montoInicial,
        ":observaciones" => $observaciones
    ));
    $idTurno = intval($db->lastInsertId());

    if (columnaExiste($db, "erp_pos_movimientos_caja", "id_caja")) {
        $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
            (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus,
             referencia, requiere_autorizacion, requiere_evidencia, observaciones, creado_por, fecha_registro, fecha_actualizacion)
            VALUES (:turno, :caja, :almacen, 'entrada', 'apertura_turno', 'monto_inicial', :monto, 'registrado',
             :referencia, 0, 0, :observaciones, :usuario, NOW(), NOW())");
        $stmt->execute(array(
            ":turno" => $idTurno,
            ":caja" => $idCaja,
            ":almacen" => $idAlmacen,
            ":monto" => $montoInicial,
            ":referencia" => $folio,
            ":observaciones" => $observaciones,
            ":usuario" => $idUsuario
        ));
    } else {
        $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
            (id_turno_caja, tipo, motivo, monto, referencia, observaciones, creado_por, fecha_registro)
            VALUES (:turno, 'entrada', 'monto_inicial', :monto, :referencia, :observaciones, :usuario, NOW())");
        $stmt->execute(array(
            ":turno" => $idTurno,
            ":monto" => $montoInicial,
            ":referencia" => $folio,
            ":observaciones" => $observaciones,
            ":usuario" => $idUsuario
        ));
    }
    $idMovimiento = intval($db->lastInsertId());

    $db->commit();
    echo json_encode(array(
        "ok" => true,
        "modo" => "turno_abierto",
        "respaldo_ref" => $respaldo,
        "id_turno_caja" => $idTurno,
        "folio" => $folio,
        "id_movimiento_caja" => $idMovimiento,
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "monto_inicial" => $montoInicial,
        "siguiente_paso" => "Ejecutar UAT read-only y validar prevalidacion POS con turno abierto."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

function siguienteFolioTurno($db, $idCaja) {
    $prefijo = "TUR-" . date("Ymd") . "-" . str_pad((string) $idCaja, 3, "0", STR_PAD_LEFT) . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_pos_turnos WHERE folio LIKE :prefijo");
    $stmt->execute(array(":prefijo" => $prefijo . "%"));
    return $prefijo . str_pad((string) (intval($stmt->fetchColumn()) + 1), 3, "0", STR_PAD_LEFT);
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

function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `{$tabla}` LIKE :columna");
    $stmt->execute(array(":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}
