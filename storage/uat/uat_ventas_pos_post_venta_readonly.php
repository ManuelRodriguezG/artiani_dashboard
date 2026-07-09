<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar evidencia post-venta POS por folio sin escribir BD.
 * Impacto: confirma venta, pagos, caja, kardex, garantia snapshot y trazabilidad detalle-inventario despues de una UAT autorizada.
 * Contrato: read-only; no corrige datos, no cancela ventas y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    }
}

if ($folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "Indica --folio=FOLIO_POS para validar evidencia post-venta.",
        "ejemplo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_post_venta_readonly.php --folio=POS-YYYYMMDD-000001"
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpPostVentaUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpPostVentaUat();
$db = $ventas->conexionUat();

$tablas = array(
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_pagos",
    "erp_ventas_detalle_inventario",
    "erp_ventas_detalle_garantias",
    "erp_pos_movimientos_caja",
    "erp_inventario_movimientos",
    "erp_inventario_existencias",
    "erp_inventario_unidades"
);
$faltantes = array();
foreach ($tablas as $tabla) {
    if (!tablaExiste($db, $tabla)) {
        $faltantes[] = $tabla;
    }
}
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "No se puede validar post-venta porque falta esquema.",
        "tablas_faltantes" => $faltantes
    ));
}

$stmt = $db->prepare("SELECT v.*, a.almacen, c.codigo caja_codigo, c.nombre caja_nombre,
        t.folio turno_folio
    FROM erp_ventas v
    LEFT JOIN erp_almacenes a ON a.id_almacen=v.id_almacen
    LEFT JOIN erp_pos_cajas c ON c.id_caja=v.id_caja
    LEFT JOIN erp_pos_turnos t ON t.id_turno_caja=v.id_turno_caja
    WHERE v.folio=:folio
    LIMIT 1");
$stmt->execute(array(":folio" => $folio));
$venta = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$venta) {
    responder(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "Folio no encontrado en erp_ventas.",
        "folio" => $folio
    ));
}

$idVenta = intval($venta["id_venta"]);
$detalles = consultarTodos($db, "SELECT * FROM erp_ventas_detalle WHERE id_venta=:venta ORDER BY renglon ASC", array(":venta" => $idVenta));
$pagos = consultarTodos($db, "SELECT p.*, mc.tipo movimiento_tipo, mc.motivo movimiento_motivo
    FROM erp_ventas_pagos p
    LEFT JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=p.id_movimiento_caja
    WHERE p.id_venta=:venta
    ORDER BY p.id_venta_pago ASC", array(":venta" => $idVenta));
$garantias = consultarTodos($db, "SELECT *
    FROM erp_ventas_detalle_garantias
    WHERE id_venta=:venta
    ORDER BY id_venta_detalle_garantia ASC", array(":venta" => $idVenta));
$trazabilidad = consultarTodos($db, "SELECT vi.*, im.tipo_movimiento, im.origen_tipo, im.referencia,
        im.existencia_anterior, im.existencia_nueva, im.cantidad movimiento_cantidad,
        e.codigo_existencia, e.cantidad existencia_actual, e.cantidad_disponible disponible_actual,
        u.codigo_unico, u.codigo_etiqueta_interna, u.estatus unidad_estatus,
        u.estado_fisico unidad_estado_fisico, u.cantidad_base_disponible unidad_disponible_actual
    FROM erp_ventas_detalle_inventario vi
    LEFT JOIN erp_inventario_movimientos im ON im.id_movimiento_inventario=vi.id_movimiento_inventario
    LEFT JOIN erp_inventario_existencias e ON e.id_existencia_inventario=vi.id_existencia_inventario
    LEFT JOIN erp_inventario_unidades u ON u.id_inventario_unidad=vi.id_inventario_unidad
    WHERE vi.id_venta=:venta
    ORDER BY vi.id_venta_detalle_inventario ASC", array(":venta" => $idVenta));

$sumaDetalle = 0;
foreach ($detalles as $detalle) {
    $sumaDetalle += floatval($detalle["total"]);
}
$sumaPagos = 0;
foreach ($pagos as $pago) {
    if ($pago["estatus"] === "registrado") {
        $sumaPagos += floatval($pago["monto"]);
    }
}

$hallazgos = array();
if (empty($detalles)) {
    $hallazgos[] = hallazgo("VENTAS-POST-001", "alta", "La venta no tiene detalle.");
}
if (empty($pagos) && floatval($venta["total"]) > 0) {
    $hallazgos[] = hallazgo("VENTAS-POST-002", "alta", "La venta no tiene pagos registrados.");
}
if (abs($sumaDetalle - floatval($venta["total"])) > 0.0001) {
    $hallazgos[] = hallazgo("VENTAS-POST-003", "alta", "El total del encabezado no cuadra contra detalle.");
}
if (abs($sumaPagos - floatval($venta["pagado_total"])) > 0.0001) {
    $hallazgos[] = hallazgo("VENTAS-POST-004", "alta", "El pagado_total no cuadra contra pagos registrados.");
}
foreach ($detalles as $detalle) {
    $detalleTieneGarantia = false;
    foreach ($garantias as $garantia) {
        if (intval($garantia["id_venta_detalle"]) === intval($detalle["id_venta_detalle"])) {
            $detalleTieneGarantia = true;
            if (trim((string) $garantia["resumen_ticket"]) === "") {
                $hallazgos[] = hallazgo("VENTAS-POST-007", "media", "La garantia de la partida no tiene resumen para ticket.", array(
                    "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
                    "id_venta_detalle_garantia" => intval($garantia["id_venta_detalle_garantia"])
                ));
            }
            if (trim((string) $garantia["tipo_garantia_snapshot"]) === "") {
                $hallazgos[] = hallazgo("VENTAS-POST-008", "media", "La garantia de la partida no tiene tipo snapshot.", array(
                    "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
                    "id_venta_detalle_garantia" => intval($garantia["id_venta_detalle_garantia"])
                ));
            }
        }
    }
    if (!$detalleTieneGarantia) {
        $hallazgos[] = hallazgo("VENTAS-POST-009", "media", "Partida sin snapshot de garantia; el ticket mostrara garantia pendiente.", array(
            "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
            "sku" => $detalle["sku"]
        ));
    }

    if (intval($detalle["controla_inventario"]) === 1) {
        $detalleTieneTrazabilidad = false;
        foreach ($trazabilidad as $traza) {
            if (intval($traza["id_venta_detalle"]) === intval($detalle["id_venta_detalle"])) {
                $detalleTieneTrazabilidad = true;
                if ($traza["tipo_movimiento"] !== "salida" || $traza["origen_tipo"] !== "venta_pos" || $traza["referencia"] !== $folio) {
                    $hallazgos[] = hallazgo("VENTAS-POST-005", "alta", "La trazabilidad no apunta a kardex de venta_pos con folio ERP.", array(
                        "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
                        "id_movimiento_inventario" => intval($traza["id_movimiento_inventario"])
                    ));
                }
            }
        }
        if (!$detalleTieneTrazabilidad) {
            $hallazgos[] = hallazgo("VENTAS-POST-006", "alta", "Partida inventariable sin trazabilidad detalle-inventario.", array(
                "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
                "sku" => $detalle["sku"]
            ));
        }
    }
}

responder(array(
    "ok" => empty($hallazgos),
    "modo" => "read-only",
    "folio" => $folio,
    "venta" => array(
        "id_venta" => $idVenta,
        "estatus" => $venta["estatus"],
        "canal" => $venta["canal"],
        "tipo_documento" => $venta["tipo_documento"],
        "almacen" => $venta["almacen"],
        "caja" => trim($venta["caja_codigo"] . " " . $venta["caja_nombre"]),
        "turno" => $venta["turno_folio"],
        "total" => redondear($venta["total"]),
        "pagado_total" => redondear($venta["pagado_total"]),
        "saldo_total" => redondear($venta["saldo_total"])
    ),
    "cuadres" => array(
        "suma_detalle" => redondear($sumaDetalle),
        "suma_pagos_registrados" => redondear($sumaPagos),
        "partidas" => count($detalles),
        "pagos" => count($pagos),
        "garantias" => count($garantias),
        "trazabilidades" => count($trazabilidad)
    ),
    "detalles" => $detalles,
    "pagos" => $pagos,
    "garantias" => $garantias,
    "trazabilidad_inventario" => $trazabilidad,
    "hallazgos" => $hallazgos,
    "siguiente_paso" => empty($hallazgos)
        ? "Capturar esta evidencia en docs/erp_ventas_pos_evidencia_uat.md."
        : "Resolver hallazgos antes de aprobar el flujo POS real."
));

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function consultarTodos($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function hallazgo($id, $severidad, $mensaje, $depurar = array()) {
    return array(
        "id" => $id,
        "severidad" => $severidad,
        "mensaje" => $mensaje,
        "depurar" => $depurar
    );
}

function redondear($valor) {
    return round(floatval($valor), 6);
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
