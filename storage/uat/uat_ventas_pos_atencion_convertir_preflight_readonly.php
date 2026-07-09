<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: prevalidar conversion/cobro de una atencion POS a venta sin escribir BD.
 * Impacto: confirma que caja puede cobrar una atencion revalidando stock, turno, precio y pago.
 * Contrato: read-only; no crea venta, no registra pago, no descuenta inventario y no cambia estatus de atencion.
 */

$args = isset($argv) ? $argv : array();
$idAtencion = 0;
$idUsuario = 1;
$pago = 0;
foreach ($args as $arg) {
    if (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    }
}

if ($idAtencion <= 0 || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_atencion=ID y --id_usuario=ID."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosAtencionConvertirDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosAtencionConvertirDb())->db();
$ventas = new VentasErp();

$stmt = $db->prepare("SELECT * FROM erp_pos_atenciones WHERE id_atencion_pos=:id LIMIT 1");
$stmt->execute(array(":id" => $idAtencion));
$atencion = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$atencion) {
    responder(array("ok" => false, "modo" => "read-only", "mensaje" => "Atencion no encontrada.", "id_atencion" => $idAtencion));
}

$detalles = consultarTodos($db, "SELECT * FROM erp_pos_atenciones_detalle WHERE id_atencion_pos=:id AND estatus='activa' ORDER BY renglon ASC", array(":id" => $idAtencion));
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turno = valor($depurarAsignacion, array("turno_abierto"), array());

$items = array();
foreach ($detalles as $detalle) {
    $items[] = array(
        "id_sku" => intval($detalle["id_sku_erp"]),
        "cantidad" => floatval($detalle["cantidad_venta"]),
        "precio_unitario" => floatval($detalle["precio_unitario"]),
        "modo_salida" => $detalle["modo_salida"] !== "" ? $detalle["modo_salida"] : "existencia_agregada"
    );
}
$total = floatval($atencion["total"]);
$montoPago = $pago > 0 ? $pago : $total;
$pagos = array(array(
    "id_metodo_pago" => 1,
    "monto" => $montoPago,
    "referencia" => "ATN-" . $idAtencion
));

$datosVenta = array(
    "id_almacen" => intval($atencion["id_almacen"]),
    "id_caja" => intval(valor($datosAsignacion, array("id_caja"), $atencion["id_caja"])),
    "id_turno_caja" => intval(valor($turno, array("id_turno_caja"), $atencion["id_turno_caja"])),
    "items" => json_encode($items),
    "pagos" => json_encode($pagos),
    "exigir_pago_completo" => 1
);

$prevalidacion = $ventas->prevalidarCarritoPos($datosVenta);
$confirmacion = $ventas->confirmarVentaPosDryRun($datosVenta);
$clientePrecio = $ventas->clientePrecioDryRun(array(
    "id_almacen" => intval($atencion["id_almacen"]),
    "canal" => "pos",
    "id_cliente" => intval($atencion["id_cliente"]),
    "identificador_cliente" => (string) $atencion["cliente_identificador_publico"],
    "items" => json_encode($items)
));

$bloqueos = array();
if (!in_array($atencion["estatus"], array("abierta", "lista_para_cobro", "tomada_por_caja"), true)) {
    $bloqueos[] = "La atencion no esta en estatus cobrable: " . $atencion["estatus"];
}
foreach (array(
    valor($asignacion, array("depurar", "bloqueos"), array()),
    valor($prevalidacion, array("depurar", "bloqueos"), array()),
    valor($confirmacion, array("depurar", "bloqueos"), array()),
    valor($clientePrecio, array("depurar", "bloqueos"), array())
) as $lista) {
    foreach ($lista as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
}

responder(array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "read-only",
    "id_atencion" => $idAtencion,
    "atencion" => array(
        "folio_temporal" => $atencion["folio_temporal"],
        "estatus" => $atencion["estatus"],
        "cliente" => $atencion["cliente_nombre_publico"],
        "total" => floatval($atencion["total"])
    ),
    "contexto_caja" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $datosVenta["id_almacen"],
        "id_caja" => $datosVenta["id_caja"],
        "id_turno_caja" => $datosVenta["id_turno_caja"]
    ),
    "items" => $items,
    "pagos" => $pagos,
    "prevalidacion" => $prevalidacion,
    "confirmacion" => $confirmacion,
    "cliente_precio" => $clientePrecio,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "contrato_conversion" => array(
        "bloquear_atencion_for_update" => true,
        "revalidar_stock_y_precio" => true,
        "crear_venta_pos" => true,
        "crear_kardex" => true,
        "crear_pago_y_movimiento_caja" => true,
        "marcar_atencion_convertida" => true
    ),
    "siguiente_paso" => empty(array_unique($bloqueos))
        ? "Preparar aplicador autorizado para convertir/cobrar atencion."
        : "Resolver bloqueos antes de convertir/cobrar."
));

function consultarTodos($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
