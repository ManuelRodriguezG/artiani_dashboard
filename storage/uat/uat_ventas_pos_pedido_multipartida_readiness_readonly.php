<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: validar readiness actual para UAT POS apartado multipartida.
 * Impacto: confirma asignacion, turno, stock, politica, metodo de pago y dry-run antes de autorizaciones reales.
 * Contrato: read-only; no abre turno, no carga stock, no crea apartado, no reserva y no mueve caja/kardex.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidadPorPartida = 1;
$precio = 295;
$anticipo = 120;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidadPorPartida = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--anticipo=") === 0) {
        $anticipo = floatval(trim(substr($arg, 11), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidoMultipartidaReadinessDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosPedidoMultipartidaReadinessDb();
$db = $ventas->db();
$stockNecesario = round($cantidadPorPartida * 2, 6);
$total = round($stockNecesario * $precio, 6);
$saldo = round(max(0, $total - $anticipo), 6);

if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedido_multipartida_readiness_readonly",
        "read_only" => true,
        "bloqueos" => array("Conexion BD no disponible"),
        "contrato" => contratoReadOnly()
    ));
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$asignacionActiva = !empty($depurarAsignacion["asignacion_activa"]);
$datosAsignacion = $asignacionActiva && isset($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
$turno = !empty($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();
if (!empty($datosAsignacion["id_almacen"])) {
    $idAlmacen = intval($datosAsignacion["id_almacen"]);
}

$politica = tablaExiste($db, "erp_ventas_politicas_apartado")
    ? consultarUno($db, "SELECT codigo, porcentaje_anticipo_minimo, monto_anticipo_minimo, dias_vigencia, permite_abonos, estatus
        FROM erp_ventas_politicas_apartado
        WHERE estatus='activa'
        ORDER BY id_politica_apartado ASC
        LIMIT 1", array())
    : null;

$metodoPago = tablaExiste($db, "erp_metodos_pago")
    ? consultarUno($db, "SELECT id_metodo_pago, metodo_pago, estatus FROM erp_metodos_pago WHERE id_metodo_pago=1 LIMIT 1", array())
    : null;

$stock = tablaExiste($db, "erp_inventario_existencias")
    ? consultarUno($db, "SELECT COALESCE(SUM(cantidad_disponible),0) disponible_total,
            COUNT(*) existencias_con_stock
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku AND cantidad_disponible>0",
        array(":almacen" => $idAlmacen, ":sku" => $idSku))
    : array("disponible_total" => 0, "existencias_con_stock" => 0);

$items = array(
    array("id_sku" => $idSku, "cantidad" => $cantidadPorPartida, "precio_unitario" => $precio, "modo_salida" => "existencia_agregada"),
    array("id_sku" => $idSku, "cantidad" => $cantidadPorPartida, "precio_unitario" => $precio, "modo_salida" => "existencia_agregada")
);
$dryRun = $ventas->pedidoReservaDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => !empty($datosAsignacion["id_caja"]) ? intval($datosAsignacion["id_caja"]) : 0,
    "id_turno_caja" => !empty($turno["id_turno_caja"]) ? intval($turno["id_turno_caja"]) : 0,
    "canal" => "pedido_tienda",
    "tipo_documento" => "apartado",
    "cliente_nombre_publico" => "Cliente UAT Apartado Multipartida POS",
    "identificador_cliente" => "3312345678",
    "fecha_entrega_compromiso" => date("Y-m-d", strtotime("+7 days")),
    "items" => $items,
    "pagos" => array(array("id_metodo_pago" => 1, "monto" => $anticipo, "referencia" => "UAT-PED-MULTI-DRY"))
));

$dryDepurar = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
$dryBloqueos = isset($dryDepurar["bloqueos"]) && is_array($dryDepurar["bloqueos"]) ? $dryDepurar["bloqueos"] : array();
$reservasPropuestas = isset($dryDepurar["propuesta_reserva"]["reservas"]) && is_array($dryDepurar["propuesta_reserva"]["reservas"])
    ? count($dryDepurar["propuesta_reserva"]["reservas"])
    : 0;

$bloqueos = array();
$hallazgos = array();
if (!$asignacionActiva) {
    $bloqueos[] = "Usuario sin asignacion POS activa";
}
if (empty($turno)) {
    $bloqueos[] = "Sin turno abierto";
}
if (!$politica) {
    $bloqueos[] = "Sin politica activa de apartado";
}
if (!$metodoPago) {
    $bloqueos[] = "Metodo de pago efectivo no disponible";
}
if (floatval($stock["disponible_total"]) + 0.0001 < $stockNecesario) {
    $bloqueos[] = "Stock insuficiente para multipartida";
}
foreach ($dryBloqueos as $bloqueo) {
    if (!in_array($bloqueo, $bloqueos, true)) {
        $hallazgos[] = "Dry-run: " . $bloqueo;
    }
}

$salida = array(
    "ok" => empty($bloqueos) && empty($dryBloqueos),
    "modo" => "pedido_multipartida_readiness_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "partidas" => 2,
        "cantidad_por_partida" => $cantidadPorPartida,
        "stock_necesario" => $stockNecesario,
        "precio" => $precio,
        "total" => $total,
        "anticipo" => $anticipo,
        "saldo" => $saldo
    ),
    "contexto_pos" => array(
        "asignacion_activa" => $asignacionActiva,
        "id_caja" => !empty($datosAsignacion["id_caja"]) ? intval($datosAsignacion["id_caja"]) : 0,
        "turno_abierto" => !empty($turno),
        "id_turno_caja" => !empty($turno["id_turno_caja"]) ? intval($turno["id_turno_caja"]) : 0,
        "folio_turno" => !empty($turno["folio"]) ? $turno["folio"] : ""
    ),
    "politica_apartado" => $politica,
    "metodo_pago_efectivo" => $metodoPago,
    "stock" => array(
        "disponible_total" => round(floatval($stock["disponible_total"]), 6),
        "existencias_con_stock" => intval($stock["existencias_con_stock"]),
        "suficiente" => floatval($stock["disponible_total"]) + 0.0001 >= $stockNecesario
    ),
    "dry_run" => array(
        "mensaje" => isset($dryRun["mensaje"]) ? $dryRun["mensaje"] : "",
        "bloqueos" => $dryBloqueos,
        "reservas_propuestas" => $reservasPropuestas,
        "anticipo_minimo" => isset($dryDepurar["anticipo_minimo"]) ? $dryDepurar["anticipo_minimo"] : null,
        "pagado_total" => isset($dryDepurar["pagado_total"]) ? $dryDepurar["pagado_total"] : null,
        "saldo_estimado" => isset($dryDepurar["saldo_estimado"]) ? $dryDepurar["saldo_estimado"] : null
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "hallazgos" => array_values(array_unique($hallazgos)),
    "contrato" => contratoReadOnly()
);

if ($compacto) {
    $salida = array(
        "ok" => $salida["ok"],
        "modo" => $salida["modo"],
        "read_only" => true,
        "parametros" => $salida["parametros"],
        "contexto_pos" => $salida["contexto_pos"],
        "stock" => $salida["stock"],
        "dry_run" => $salida["dry_run"],
        "bloqueos" => $salida["bloqueos"],
        "hallazgos" => $salida["hallazgos"],
        "contrato" => $salida["contrato"]
    );
}

responder($salida);

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function consultarUno($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function contratoReadOnly() {
    return array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_crea_apartado" => true,
        "no_registra_pago" => true,
        "no_reserva_inventario" => true,
        "no_mueve_caja" => true,
        "no_mueve_kardex" => true
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
