<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-05.
 * Proposito: auditar preparacion real de Pedidos/Apartados POS sin escribir BD.
 * Impacto: identifica brechas de esquema, metodos, turno, stock, reservas y abonos antes de pedir autorizacion fuerte.
 * Contrato: read-only; no crea pedidos, no registra abonos, no reserva, no mueve caja y no mueve kardex.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/InventarioErp.php";

class UatVentasPosPedidosProbe extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

function tablaExisteUat($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaExisteUat($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function auditarTablaColumnasUat($db, $tabla, $columnas) {
    $existe = tablaExisteUat($db, $tabla);
    $faltantes = array();
    foreach ($columnas as $columna) {
        if (!$existe || !columnaExisteUat($db, $tabla, $columna)) {
            $faltantes[] = $columna;
        }
    }
    return array(
        "tabla" => $tabla,
        "existe" => $existe,
        "faltantes" => $faltantes,
        "lista" => empty($faltantes)
    );
}

function contarUat($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return intval($stmt->fetchColumn());
}

$ventas = new UatVentasPosPedidosProbe();
$inventario = new InventarioErp();
$db = $ventas->db();
$hallazgos = array();
$bloqueos = array();

if (!$db) {
    $salida = array(
        "ok" => false,
        "read_only" => true,
        "bloqueos" => array("Conexion MySQL no disponible"),
        "contrato" => array("no_escribe_bd" => true)
    );
    echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$tablas = array(
    "erp_ventas" => array("folio", "tipo_documento", "estatus", "id_almacen", "id_caja", "id_turno_caja", "id_cliente", "cliente_nombre_publico", "cliente_identificador_publico", "total", "pagado_total", "saldo_total", "anticipo_minimo", "fecha_vencimiento", "politica_apartado_snapshot", "fecha_entrega_compromiso"),
    "erp_ventas_detalle" => array("id_venta", "id_sku_erp", "sku", "descripcion", "controla_inventario", "modo_salida", "cantidad_venta", "cantidad_base", "precio_unitario", "total", "estatus"),
    "erp_ventas_pagos" => array("id_venta", "id_caja", "id_turno_caja", "id_movimiento_caja", "id_metodo_pago", "tipo_pago", "monto", "referencia", "estatus"),
    "erp_ventas_eventos" => array("id_venta", "tipo_evento", "estatus_anterior", "estatus_nuevo", "monto", "referencia", "datos_snapshot", "creado_por"),
    "erp_ventas_detalle_inventario" => array("id_venta", "id_venta_detalle", "id_existencia_inventario", "id_reserva_inventario", "id_movimiento_inventario", "cantidad_base", "estatus"),
    "erp_ventas_politicas_apartado" => array("codigo", "porcentaje_anticipo_minimo", "monto_anticipo_minimo", "dias_vigencia", "permite_abonos", "permite_entrega_sin_liquidar", "estatus"),
    "erp_inventario_reservas" => array("folio", "origen_tipo", "origen_id", "origen_detalle_id", "id_existencia_inventario", "id_sku_erp", "id_almacen", "cantidad_reservada", "cantidad_consumida", "cantidad_liberada", "estatus", "fecha_vencimiento"),
    "erp_pos_movimientos_caja" => array("id_turno_caja", "tipo", "motivo", "monto", "referencia", "creado_por")
);

$auditoriaTablas = array();
foreach ($tablas as $tabla => $columnas) {
    $auditoria = auditarTablaColumnasUat($db, $tabla, $columnas);
    $auditoriaTablas[$tabla] = $auditoria;
    if (!$auditoria["lista"]) {
        $bloqueos[] = "Tabla/columnas incompletas: " . $tabla;
    }
}

$politicasActivas = tablaExisteUat($db, "erp_ventas_politicas_apartado")
    ? contarUat($db, "SELECT COUNT(*) FROM erp_ventas_politicas_apartado WHERE estatus='activa'")
    : 0;
if ($politicasActivas <= 0) {
    $bloqueos[] = "No existe politica activa de apartado";
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$turnoAbierto = !empty($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();
if (empty($depurarAsignacion["asignacion_activa"])) {
    $bloqueos[] = "Usuario sin asignacion POS activa";
}
if (empty($turnoAbierto)) {
    $hallazgos[] = "Sin turno abierto: bloquea anticipos/abonos reales, pero no bloquea auditoria";
}

$existenciasDisponibles = 0;
$cantidadDisponible = 0;
if (tablaExisteUat($db, "erp_inventario_existencias")) {
    $stmt = $db->prepare("SELECT COUNT(*) existencias, COALESCE(SUM(cantidad_disponible),0) disponible
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku AND cantidad_disponible>=:cantidad");
    $stmt->execute(array(":almacen" => $idAlmacen, ":sku" => $idSku, ":cantidad" => $cantidad));
    $stock = $stmt->fetch(PDO::FETCH_ASSOC);
    $existenciasDisponibles = intval($stock["existencias"]);
    $cantidadDisponible = round(floatval($stock["disponible"]), 6);
}
if ($existenciasDisponibles <= 0) {
    $hallazgos[] = "Sin existencia suficiente para reserva UAT SKU " . $idSku . " en almacen " . $idAlmacen;
}

$metodos = array(
    "VentasErp::pedidoGuardarReal" => method_exists($ventas, "pedidoGuardarReal"),
    "VentasErp::apartadoAbonoReal" => method_exists($ventas, "apartadoAbonoReal"),
    "VentasErp::pedidoEntregarReal" => method_exists($ventas, "pedidoEntregarReal"),
    "VentasErp::pedidoCancelarReal" => method_exists($ventas, "pedidoCancelarReal"),
    "InventarioErp::crearReserva" => method_exists($inventario, "crearReserva"),
    "InventarioErp::liberarReserva" => method_exists($inventario, "liberarReserva"),
    "InventarioErp::consumirReserva" => method_exists($inventario, "consumirReserva")
);
foreach ($metodos as $metodo => $existe) {
    if (!$existe) {
        $bloqueos[] = "Metodo real pendiente: " . $metodo;
    }
}

$salida = array(
    "ok" => empty($bloqueos),
    "read_only" => true,
    "modo" => "pedidos_apartados_real_preflight",
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "tablas" => $auditoriaTablas,
    "politicas_activas_apartado" => $politicasActivas,
    "contexto_pos" => array(
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turnoAbierto),
        "id_turno_caja" => !empty($turnoAbierto["id_turno_caja"]) ? intval($turnoAbierto["id_turno_caja"]) : 0
    ),
    "stock_uat" => array(
        "existencias_disponibles" => $existenciasDisponibles,
        "cantidad_disponible_en_existencias_suficientes" => $cantidadDisponible
    ),
    "metodos_reales" => $metodos,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "hallazgos" => array_values(array_unique($hallazgos)),
    "siguiente_autorizacion_sugerida" => "AUTORIZO PREPARAR FLUJO REAL PEDIDOS APARTADOS POS usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDOS_APARTADOS_REAL para UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_pedido" => true,
        "no_registra_abono" => true,
        "no_mueve_caja" => true,
        "no_reserva_inventario" => true,
        "no_mueve_kardex" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => $salida["ok"],
        "read_only" => true,
        "modo" => $salida["modo"],
        "contexto_pos" => $salida["contexto_pos"],
        "stock_uat" => $salida["stock_uat"],
        "politicas_activas_apartado" => $politicasActivas,
        "metodos_reales" => $metodos,
        "bloqueos" => $salida["bloqueos"],
        "hallazgos" => $salida["hallazgos"],
        "siguiente_autorizacion_sugerida" => $salida["siguiente_autorizacion_sugerida"],
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
