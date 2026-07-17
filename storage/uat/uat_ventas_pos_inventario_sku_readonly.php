<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: auditar inventario disponible de un SKU/almacen para UAT POS sin modificar existencias.
 * Impacto: permite distinguir bloqueos esperados de stock contra problemas de inventario/reservas/pendientes.
 * Contrato: read-only; no ajusta inventario, no reserva y no mueve kardex.
 */

$idAlmacen = 5;
$idSku = 1760;
$cantidadRequerida = 1;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad_requerida=") === 0) {
        $cantidadRequerida = floatval(trim(substr($arg, 21), "\"' "));
    }
}

if ($idAlmacen <= 0 || $idSku <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_almacen=ID y --id_sku=ID"
    ));
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";

class UatVentasPosInventarioSkuDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosInventarioSkuDb())->db();
$faltantes = tablasFaltantes($db, array(
    "erp_catalogo_skus",
    "erp_catalogo_productos",
    "erp_inventario_existencias",
    "erp_inventario_movimientos"
));
if (!empty($faltantes)) {
    responder(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "Falta esquema para auditar inventario SKU.",
        "tablas_faltantes" => $faltantes
    ));
}

$sku = consultarUno($db, "SELECT s.id_sku, s.sku, COALESCE(s.nombre, p.nombre) producto, s.estatus sku_estatus,
        p.estatus producto_estatus
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    WHERE s.id_sku=:sku
    LIMIT 1", array(":sku" => $idSku));

$existencias = consultarTodos($db, "SELECT id_existencia_inventario, codigo_existencia, id_almacen_clave id_almacen,
        cantidad, cantidad_disponible, cantidad_apartada, estatus_existencia, lote, fecha_caducidad, fecha_actualizacion
    FROM erp_inventario_existencias
    WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku
    ORDER BY cantidad_disponible DESC, id_existencia_inventario ASC", array(":almacen" => $idAlmacen, ":sku" => $idSku));

$fechaMovimientoCol = columnaExiste($db, "erp_inventario_movimientos", "fecha_movimiento")
    ? "fecha_movimiento"
    : (columnaExiste($db, "erp_inventario_movimientos", "fecha_registro") ? "fecha_registro" : "NULL");
$movimientos = consultarTodos($db, "SELECT id_movimiento_inventario, tipo_movimiento, origen_tipo, referencia,
        cantidad, existencia_anterior, existencia_nueva, " . $fechaMovimientoCol . " fecha_movimiento
    FROM erp_inventario_movimientos
    WHERE id_almacen=:almacen AND id_sku_erp=:sku
    ORDER BY id_movimiento_inventario DESC
    LIMIT 10", array(":almacen" => $idAlmacen, ":sku" => $idSku));

$fechaReservaCol = tablaExiste($db, "erp_inventario_reservas")
    ? (columnaExiste($db, "erp_inventario_reservas", "fecha_registro")
        ? "fecha_registro"
        : (columnaExiste($db, "erp_inventario_reservas", "fecha_creacion") ? "fecha_creacion" : "NULL"))
    : "NULL";
$reservas = tablaExiste($db, "erp_inventario_reservas")
    ? consultarTodos($db, "SELECT id_reserva_inventario, folio, cantidad_reservada, cantidad_consumida,
            cantidad_liberada, estatus, origen_tipo, " . $fechaReservaCol . " fecha_registro
        FROM erp_inventario_reservas
        WHERE id_almacen=:almacen AND id_sku_erp=:sku
          AND estatus IN ('activa','parcial')
        ORDER BY id_reserva_inventario DESC
        LIMIT 20", array(":almacen" => $idAlmacen, ":sku" => $idSku))
    : array();

$pendientes = tablaExiste($db, "erp_pos_inventario_pendientes")
    ? consultarTodos($db, "SELECT id_inventario_pendiente, folio, cantidad_vendida, cantidad_cubierta,
            cantidad_pendiente, estatus, prioridad, fecha_registro
        FROM erp_pos_inventario_pendientes
        WHERE id_almacen=:almacen AND id_sku_erp=:sku
          AND estatus IN ('pendiente_revision','en_revision')
        ORDER BY id_inventario_pendiente DESC
        LIMIT 20", array(":almacen" => $idAlmacen, ":sku" => $idSku))
    : array();

$totalCantidad = 0;
$totalDisponible = 0;
$totalApartada = 0;
foreach ($existencias as $existencia) {
    $totalCantidad += floatval($existencia["cantidad"]);
    $totalDisponible += floatval($existencia["cantidad_disponible"]);
    $totalApartada += floatval($existencia["cantidad_apartada"]);
}

$bloqueos = array();
$avisos = array();
if (!$sku) {
    $bloqueos[] = "SKU no encontrado";
} elseif ($sku["sku_estatus"] !== "activo" || $sku["producto_estatus"] !== "activo") {
    $bloqueos[] = "SKU/producto no activo";
}
if ($totalDisponible + 0.0001 < $cantidadRequerida) {
    $avisos[] = "Disponible menor a cantidad requerida; para UAT real se requiere cargar stock o autorizar inventario pendiente";
}
if (!empty($pendientes)) {
    $avisos[] = "Existen pendientes POS abiertos para este SKU/almacen";
}
if (!empty($reservas)) {
    $avisos[] = "Existen reservas activas/parciales para este SKU/almacen";
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_inventario_sku_readonly",
    "host" => "panel.com.local",
    "contexto" => array(
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad_requerida" => $cantidadRequerida
    ),
    "sku" => $sku,
    "resumen" => array(
        "existencias" => count($existencias),
        "cantidad_total" => round($totalCantidad, 6),
        "disponible_total" => round($totalDisponible, 6),
        "apartada_total" => round($totalApartada, 6),
        "reservas_activas" => count($reservas),
        "pendientes_pos_abiertos" => count($pendientes),
        "movimientos_recientes" => count($movimientos),
        "cubre_cantidad_requerida" => $totalDisponible + 0.0001 >= $cantidadRequerida
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "existencias" => $existencias,
    "reservas" => $reservas,
    "pendientes" => $pendientes,
    "movimientos_recientes" => $movimientos,
    "contrato" => array(
        "read_only" => true,
        "no_ajusta" => true,
        "no_reserva" => true,
        "no_mueve_kardex" => true
    )
));

function tablasFaltantes($db, $tablas) {
    $faltantes = array();
    foreach ($tablas as $tabla) {
        if (!tablaExiste($db, $tabla)) {
            $faltantes[] = $tabla;
        }
    }
    return $faltantes;
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SHOW COLUMNS FROM `" . str_replace("`", "", $tabla) . "` LIKE :columna");
    $stmt->execute(array(":columna" => $columna));
    return (bool) $stmt->fetchColumn();
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

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
