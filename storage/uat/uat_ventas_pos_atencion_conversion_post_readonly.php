<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: validar read-only que una atencion POS multiusuario quedo convertida en venta real.
 * Impacto: cruza atencion, venta, pagos, caja, trazabilidad inventario y garantia sin modificar BD.
 * Contrato: solo lectura; no cobra, no convierte atencion, no mueve caja, no mueve inventario.
 */

$idAtencion = 0;
$folioVenta = "";
$compact = true;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folioVenta = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--compact=") === 0) {
        $compact = intval(trim(substr($arg, 10), "\"' ")) === 1;
    }
}

if ($idAtencion <= 0 && $folioVenta === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_atencion=ID o --folio=POS-YYYYMMDD-######.",
        "contrato" => array("read_only" => true, "no_escribe_bd" => true)
    ));
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";

class UatVentasPosAtencionConversionPostDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosAtencionConversionPostDb())->db();
$faltantes = tablasFaltantes($db, array(
    "erp_pos_atenciones",
    "erp_pos_atenciones_detalle",
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_pagos",
    "erp_pos_movimientos_caja",
    "erp_ventas_detalle_inventario",
    "erp_ventas_detalle_garantias",
    "erp_inventario_movimientos"
));
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "No se puede validar conversion porque falta esquema.",
        "tablas_faltantes" => $faltantes
    ));
}

$atencion = null;
if ($idAtencion > 0) {
    $atencion = consultarUno($db, "SELECT *
        FROM erp_pos_atenciones
        WHERE id_atencion_pos=:id
        LIMIT 1", array(":id" => $idAtencion));
}

$venta = null;
if ($atencion && intval(valor($atencion, "id_venta_convertida", 0)) > 0) {
    $venta = consultarUno($db, "SELECT *
        FROM erp_ventas
        WHERE id_venta=:venta
        LIMIT 1", array(":venta" => intval($atencion["id_venta_convertida"])));
} elseif ($folioVenta !== "") {
    $venta = consultarUno($db, "SELECT *
        FROM erp_ventas
        WHERE folio=:folio
        LIMIT 1", array(":folio" => $folioVenta));
    if ($venta && !$atencion) {
        $atencion = consultarUno($db, "SELECT *
            FROM erp_pos_atenciones
            WHERE id_venta_convertida=:venta
            LIMIT 1", array(":venta" => intval($venta["id_venta"])));
    }
}

$idVenta = $venta ? intval($venta["id_venta"]) : 0;
$detallesAtencion = $atencion ? consultarTodos($db, "SELECT *
    FROM erp_pos_atenciones_detalle
    WHERE id_atencion_pos=:atencion
    ORDER BY renglon ASC", array(":atencion" => intval($atencion["id_atencion_pos"]))) : array();
$detallesVenta = $idVenta > 0 ? consultarTodos($db, "SELECT *
    FROM erp_ventas_detalle
    WHERE id_venta=:venta
    ORDER BY renglon ASC", array(":venta" => $idVenta)) : array();
$pagos = $idVenta > 0 ? consultarTodos($db, "SELECT *
    FROM erp_ventas_pagos
    WHERE id_venta=:venta
    ORDER BY id_venta_pago ASC", array(":venta" => $idVenta)) : array();
$movimientosCaja = $idVenta > 0 ? consultarTodos($db, "SELECT *
    FROM erp_pos_movimientos_caja
    WHERE id_venta=:venta OR referencia=:folio
    ORDER BY id_movimiento_caja ASC", array(":venta" => $idVenta, ":folio" => valor($venta, "folio", ""))) : array();
$trazabilidad = $idVenta > 0 ? consultarTodos($db, "SELECT vi.*, im.tipo_movimiento, im.origen_tipo, im.referencia
    FROM erp_ventas_detalle_inventario vi
    LEFT JOIN erp_inventario_movimientos im ON im.id_movimiento_inventario=vi.id_movimiento_inventario
    WHERE vi.id_venta=:venta
    ORDER BY vi.id_venta_detalle_inventario ASC", array(":venta" => $idVenta)) : array();
$garantias = $idVenta > 0 ? consultarTodos($db, "SELECT *
    FROM erp_ventas_detalle_garantias
    WHERE id_venta=:venta
    ORDER BY id_venta_detalle_garantia ASC", array(":venta" => $idVenta)) : array();

$hallazgos = array();
if (!$atencion) {
    $hallazgos[] = hallazgo("ATN-CONV-001", "alta", "No se encontro atencion POS.");
}
if ($atencion && valor($atencion, "estatus", "") !== "convertida") {
    $hallazgos[] = hallazgo("ATN-CONV-002", "alta", "La atencion no esta en estatus convertida.", array("estatus" => valor($atencion, "estatus", "")));
}
if ($atencion && intval(valor($atencion, "id_venta_convertida", 0)) <= 0) {
    $hallazgos[] = hallazgo("ATN-CONV-003", "alta", "La atencion no tiene id_venta_convertida.");
}
if (!$venta) {
    $hallazgos[] = hallazgo("ATN-CONV-004", "alta", "No se encontro venta convertida.");
}
if ($venta && !in_array(valor($venta, "estatus", ""), array("confirmada", "pagada"), true)) {
    $hallazgos[] = hallazgo("ATN-CONV-005", "media", "La venta convertida no esta en estatus operativo valido.", array("estatus" => valor($venta, "estatus", "")));
}
if (count($detallesVenta) <= 0) {
    $hallazgos[] = hallazgo("ATN-CONV-006", "alta", "La venta convertida no tiene detalle.");
}
if (count($pagos) <= 0 && $venta && floatval(valor($venta, "total", 0)) > 0) {
    $hallazgos[] = hallazgo("ATN-CONV-007", "alta", "La venta convertida no tiene pagos.");
}
if (count($movimientosCaja) <= 0 && $venta && floatval(valor($venta, "pagado_total", 0)) > 0) {
    $hallazgos[] = hallazgo("ATN-CONV-008", "alta", "La venta convertida no tiene movimiento de caja asociado.");
}
foreach ($detallesVenta as $detalle) {
    if (intval(valor($detalle, "controla_inventario", 0)) === 1) {
        $tieneTraza = false;
        foreach ($trazabilidad as $traza) {
            if (intval(valor($traza, "id_venta_detalle", 0)) === intval(valor($detalle, "id_venta_detalle", 0))) {
                $tieneTraza = true;
            }
        }
        if (!$tieneTraza) {
            $hallazgos[] = hallazgo("ATN-CONV-009", "alta", "Partida inventariable sin trazabilidad de inventario.", array(
                "id_venta_detalle" => intval(valor($detalle, "id_venta_detalle", 0)),
                "sku" => valor($detalle, "sku", "")
            ));
        }
    }
}
if (count($garantias) < count($detallesVenta)) {
    $hallazgos[] = hallazgo("ATN-CONV-010", "media", "No todas las partidas tienen snapshot de garantia.", array(
        "detalles_venta" => count($detallesVenta),
        "garantias" => count($garantias)
    ));
}

$salida = array(
    "ok" => empty($hallazgos),
    "modo" => "ventas_pos_atencion_conversion_post_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "id_atencion" => $atencion ? intval($atencion["id_atencion_pos"]) : $idAtencion,
    "folio_atencion" => valor($atencion, "folio_temporal", null),
    "folio_venta" => valor($venta, "folio", $folioVenta),
    "resumen" => array(
        "estatus_atencion" => valor($atencion, "estatus", null),
        "id_venta_convertida" => intval(valor($atencion, "id_venta_convertida", 0)),
        "estatus_venta" => valor($venta, "estatus", null),
        "total_venta" => floatval(valor($venta, "total", 0)),
        "pagado_total" => floatval(valor($venta, "pagado_total", 0)),
        "detalles_atencion" => count($detallesAtencion),
        "detalles_venta" => count($detallesVenta),
        "pagos" => count($pagos),
        "movimientos_caja" => count($movimientosCaja),
        "trazabilidad_inventario" => count($trazabilidad),
        "garantias" => count($garantias)
    ),
    "hallazgos" => $hallazgos,
    "contrato" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
);

if (!$compact) {
    $salida["atencion"] = $atencion;
    $salida["venta"] = $venta;
    $salida["detalles_atencion"] = $detallesAtencion;
    $salida["detalles_venta"] = $detallesVenta;
    $salida["pagos"] = $pagos;
    $salida["movimientos_caja"] = $movimientosCaja;
    $salida["trazabilidad"] = $trazabilidad;
    $salida["garantias"] = $garantias;
}

responder($salida);

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

function consultarUno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function hallazgo($id, $severidad, $mensaje, $datos = array()) {
    return array(
        "id" => $id,
        "severidad" => $severidad,
        "mensaje" => $mensaje,
        "datos" => $datos
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}


