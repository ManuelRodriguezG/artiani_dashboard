<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: completar snapshot de garantia en ventas POS historicas UAT cuando el flujo ya fue validado.
 * Impacto: inserta filas faltantes en erp_ventas_detalle_garantias; no cambia venta, pagos, caja ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere token, respaldo/referencia vigente, usuario, folio, confirmacion y motivo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$folio = "";
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
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_GARANTIA_SNAPSHOT_BACKFILL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $folio === "" || $confirmacion !== "BACKFILL GARANTIA POS" || $motivo === "") {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se aplico backfill de garantia POS. Falta token, respaldo, usuario, folio, confirmacion o motivo.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_GARANTIA_SNAPSHOT_BACKFILL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE_O_REFERENCIA_VIGENTE",
            "--id_usuario=ID",
            "--folio=POS-...",
            "--confirmacion=\"BACKFILL GARANTIA POS\"",
            "--motivo=TEXTO"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/GarantiasErp.php";

class UatVentasGarantiaSnapshotBackfillApplyDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

try {
    $ventas = new UatVentasGarantiaSnapshotBackfillApplyDb();
    $garantias = new GarantiasErp();
    $db = $ventas->db();

    $stmt = $db->prepare("SELECT v.id_venta, v.folio, v.id_almacen, v.estatus,
            d.id_venta_detalle, d.id_producto_erp, d.id_sku_erp, d.sku
        FROM erp_ventas v
        INNER JOIN erp_ventas_detalle d ON d.id_venta=v.id_venta
        LEFT JOIN erp_ventas_detalle_garantias g ON g.id_venta_detalle=d.id_venta_detalle
        WHERE v.folio=:folio
          AND v.canal='pos'
          AND g.id_venta_detalle_garantia IS NULL
        ORDER BY d.renglon ASC");
    $stmt->execute(array(":folio" => $folio));
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($detalles)) {
        responder(array(
            "ok" => true,
            "modo" => "garantia_snapshot_backfill_sin_cambios",
            "folio" => $folio,
            "mensaje" => "No hay detalles POS sin snapshot de garantia para este folio."
        ));
    }

    $idVenta = intval($detalles[0]["id_venta"]);
    $idAlmacen = intval($detalles[0]["id_almacen"]);
    $payloadDetalles = array();
    foreach ($detalles as $detalle) {
        $payloadDetalles[] = array(
            "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
            "id_producto_erp" => intval($detalle["id_producto_erp"]),
            "id_sku_erp" => intval($detalle["id_sku_erp"])
        );
    }

    $db->beginTransaction();
    $respuesta = $garantias->guardarSnapshotsVenta($db, array(
        "id_venta" => $idVenta,
        "id_almacen" => $idAlmacen,
        "canal" => "pos",
        "fecha" => date("Y-m-d"),
        "detalles" => $payloadDetalles
    ));
    if (!empty($respuesta["error"])) {
        throw new Exception(isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "Error guardando snapshots");
    }
    $bloqueos = isset($respuesta["depurar"]["bloqueos"]) && is_array($respuesta["depurar"]["bloqueos"]) ? $respuesta["depurar"]["bloqueos"] : array();
    if (!empty($bloqueos)) {
        throw new Exception("Backfill bloqueado: " . implode("; ", $bloqueos));
    }
    $db->commit();

    responder(array(
        "ok" => true,
        "modo" => "garantia_snapshot_backfill_uat_real",
        "folio" => $folio,
        "id_venta" => $idVenta,
        "guardados" => isset($respuesta["depurar"]["guardados"]) ? $respuesta["depurar"]["guardados"] : array(),
        "motivo" => $motivo,
        "id_usuario" => $idUsuario
    ));
} catch (Exception $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "garantia_snapshot_backfill_error",
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

