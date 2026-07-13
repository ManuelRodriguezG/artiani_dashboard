<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: corregir movimientos de caja POS huerfanos generados por una UAT fallida y recalcular monto esperado del turno.
 * Impacto: actualiza estatus de movimientos caja sin venta valida y recalcula el turno; no toca ventas, pagos ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo/referencia vigente, turno, ids exactos y confirmacion literal.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idTurno = 0;
$confirmacion = "";
$ids = array();

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_turno_caja=") === 0) {
        $idTurno = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--ids_movimiento_caja=") === 0) {
        $raw = trim(substr($arg, 22), "\"' ");
        foreach (explode(",", $raw) as $id) {
            $id = intval(trim($id));
            if ($id > 0) {
                $ids[] = $id;
            }
        }
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = trim(substr($arg, 15), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_CAJA_ORFANOS_CORREGIR" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idTurno <= 0 || empty($ids) || $confirmacion !== "CORREGIR CAJA ORFANA") {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se corrigio caja. Falta token, respaldo, usuario, turno, ids o confirmacion literal.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_CAJA_ORFANOS_CORREGIR",
            "--respaldo=RUTA_RESPALDO_EXISTENTE_O_REFERENCIA_VIGENTE",
            "--id_usuario=ID",
            "--id_turno_caja=ID_TURNO",
            "--ids_movimiento_caja=ID1,ID2",
            "--confirmacion=\"CORREGIR CAJA ORFANA\""
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpCajaOrfanaUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

try {
    $ventas = new VentasErpCajaOrfanaUat();
    $db = $ventas->conexionUat();
    $placeholders = implode(",", array_fill(0, count($ids), "?"));
    $params = $ids;
    array_unshift($params, $idTurno);

    $stmt = $db->prepare("SELECT mc.id_movimiento_caja, mc.id_turno_caja, mc.tipo, mc.categoria, mc.monto,
            mc.estatus, mc.id_venta, mc.referencia, v.id_venta venta_valida
        FROM erp_pos_movimientos_caja mc
        LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
        WHERE mc.id_turno_caja=? AND mc.id_movimiento_caja IN ($placeholders)
        ORDER BY mc.id_movimiento_caja ASC");
    $stmt->execute($params);
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($movimientos) !== count($ids)) {
        throw new Exception("No se encontraron todos los movimientos solicitados en el turno indicado");
    }
    foreach ($movimientos as $mov) {
        if ($mov["categoria"] !== "venta_pos" || $mov["tipo"] !== "ingreso" || intval($mov["venta_valida"]) > 0) {
            throw new Exception("Movimiento no elegible como huerfano: " . intval($mov["id_movimiento_caja"]));
        }
    }

    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE erp_pos_movimientos_caja
        SET estatus='cancelado',
            observaciones=CONCAT(COALESCE(observaciones,''), ' | Correccion UAT caja huerfana por usuario ', ?),
            fecha_actualizacion=NOW()
        WHERE id_turno_caja=?
          AND id_movimiento_caja IN ($placeholders)
          AND categoria='venta_pos'
          AND tipo='ingreso'
          AND estatus='registrado'");
    $executeParams = array_merge(array($idUsuario, $idTurno), $ids);
    $stmt->execute($executeParams);

    $db->prepare("UPDATE erp_pos_turnos t
        SET monto_esperado=ROUND(monto_inicial + COALESCE((
            SELECT SUM(CASE
                WHEN mc.tipo='ingreso' THEN mc.monto
                WHEN mc.tipo IN ('egreso','gasto_caja','reembolso') THEN -mc.monto
                ELSE 0
            END)
            FROM erp_pos_movimientos_caja mc
            LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
            WHERE mc.id_turno_caja=t.id_turno_caja
              AND mc.estatus IN ('registrado','aprobado')
              AND (mc.categoria<>'venta_pos' OR v.id_venta IS NOT NULL)
        ), 0), 6),
        fecha_actualizacion=NOW()
        WHERE t.id_turno_caja=:turno")
        ->execute(array(":turno" => $idTurno));

    $stmt = $db->prepare("SELECT id_turno_caja, folio, estatus, monto_inicial, monto_esperado
        FROM erp_pos_turnos
        WHERE id_turno_caja=:turno");
    $stmt->execute(array(":turno" => $idTurno));
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "caja_orfanos_corregidos_uat_real",
        "id_turno_caja" => $idTurno,
        "ids_movimiento_caja_cancelados" => $ids,
        "turno" => $turno
    ));
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "caja_orfanos_corregir_error",
        "error" => $e->getMessage()
    ));
}

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
