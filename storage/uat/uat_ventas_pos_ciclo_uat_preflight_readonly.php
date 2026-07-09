<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: consolidar el siguiente ciclo UAT POS antes de solicitar autorizacion robusta.
 * Impacto: resume cierre actual, apertura siguiente, stock, venta y pedidos/apartados sin escribir BD.
 * Contrato: read-only; no cierra turno, no abre turno, no carga stock, no vende y no crea pedidos.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$pago = 295;
$montoContado = 795;
$montoInicialSiguiente = 500;
$montoAbono = 100;
$telefono = "3312345678";
$cliente = "Cliente UAT POS";
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$referenciaStock = "INV-INICIAL-POS-UAT-20260703-A5-S1760-CIERRE1";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicialSiguiente = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_abono=") === 0) {
        $montoAbono = floatval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--referencia_stock=") === 0) {
        $referenciaStock = trim(substr($arg, 19), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosCicloDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

class UatVentasPosCicloCrud extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosCicloDb();
$db = $ventas->db();
if (!$db) {
    $db = (new UatVentasPosCicloCrud())->db();
}
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());
$idCaja = intval(valor($datosAsignacion, "id_caja", 0));
$idTurno = intval(valor($turno, "id_turno_caja", 0));
if (intval(valor($datosAsignacion, "id_almacen", 0)) > 0) {
    $idAlmacen = intval(valor($datosAsignacion, "id_almacen", $idAlmacen));
}

$cierre = $idTurno > 0 ? $ventas->cierreTurnoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "monto_contado" => $montoContado
)) : respuestaBloqueada("Sin turno abierto para cierre");

$postCierre = $idTurno > 0 ? consultarPostCierreCompacto($db, $idTurno) : array(
    "hallazgos" => array("Sin turno para post-cierre"),
    "turno" => array()
);

$stock = preflightStock($db, $idUsuario, $idAlmacen, $idSku, $cantidad, $referenciaStock, $respaldo);

$ventaDatos = array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "items" => array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )),
    "pagos" => array(array(
        "id_metodo_pago" => 1,
        "monto" => $pago,
        "referencia" => "UAT-CICLO-VENTA"
    )),
    "exigir_pago_completo" => 1
);
$ventaPre = $ventas->prevalidarCarritoPos($ventaDatos);
$ventaDry = $ventas->confirmarVentaPosDryRun($ventaDatos);
$pedido = $ventas->pedidoReservaDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "canal" => "pedido_tienda",
    "tipo_documento" => "apartado",
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $telefono,
    "fecha_entrega_compromiso" => date("Y-m-d", strtotime("+7 days")),
    "items" => array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )),
    "pagos" => array(array(
        "id_metodo_pago" => 1,
        "monto" => $montoAbono,
        "referencia" => "UAT-CICLO-APARTADO"
    ))
));

$cierreBloqueos = valor(valor($cierre, "depurar", array()), "bloqueos", array());
$ventaBloqueos = array_values(array_unique(array_merge(
    valor(valor($ventaPre, "depurar", array()), "bloqueos", array()),
    valor(valor($ventaDry, "depurar", array()), "bloqueos", array())
)));
$pedidoBloqueos = valor(valor($pedido, "depurar", array()), "bloqueos", array());
$montoEsperadoActual = round(floatval(valor(valor($cierre, "depurar", array()), "monto_esperado", 0)), 6);
$montoEsperadoPostVenta = round($montoEsperadoActual + (empty($ventaBloqueos) ? floatval($pago) : 0), 6);
$diferenciaPostVenta = round($montoContado - $montoEsperadoPostVenta, 6);
$hallazgos = array();
if (empty($depurarAsignacion["asignacion_activa"])) {
    $hallazgos[] = "Sin asignacion POS activa";
}
if (empty($turno)) {
    $hallazgos[] = "Sin turno abierto para cierre";
}
if (!empty($cierreBloqueos)) {
    $hallazgos[] = "Cierre bloqueado: " . implode("; ", $cierreBloqueos);
}
if (!empty($postCierre["hallazgos"])) {
    $hallazgos[] = "Post-cierre pendiente: " . implode("; ", $postCierre["hallazgos"]);
}
if (!empty($stock["bloqueos"])) {
    $hallazgos[] = "Stock preflight bloqueado: " . implode("; ", $stock["bloqueos"]);
}
if (!empty($ventaBloqueos)) {
    $hallazgos[] = "Venta siguiente bloqueada: " . implode("; ", $ventaBloqueos);
}
if (!empty($pedidoBloqueos)) {
    $hallazgos[] = "Apartado siguiente bloqueado: " . implode("; ", $pedidoBloqueos);
}

$folioTurno = valor($turno, "folio", "TURNO");
$respaldoHumano = etiquetaRespaldoHumana($respaldo);
$autorizacionCierre = "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo " . $respaldoHumano
    . " con id_usuario=" . $idUsuario
    . " monto_contado=" . numero($montoContado)
    . " observaciones=\"Cierre UAT POS ciclo " . $folioTurno . "\"";
$autorizacionStock = $stock["autorizacion_sugerida"];
$autorizacionApertura = "AUTORIZO ABRIR TURNO POS UAT usando respaldo " . $respaldoHumano
    . " con id_usuario=" . $idUsuario
    . " y monto_inicial=" . numero($montoInicialSiguiente)
    . " observaciones=\"Apertura UAT POS posterior a cierre\"";
$autorizacionVenta = "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo " . $respaldoHumano
    . " con id_usuario=" . $idUsuario
    . " id_sku=" . $idSku
    . " cantidad=" . numero($cantidad)
    . " precio=" . numero($precio)
    . " pago=" . numero($pago)
    . " cliente=\"" . $cliente . "\"";

responder(array(
    "ok" => empty($cierreBloqueos) && !empty($turno),
    "modo" => "ventas_pos_ciclo_uat_preflight_readonly",
    "read_only" => true,
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_turno_caja" => $idTurno,
        "folio_turno" => $folioTurno,
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty($turno)
    ),
    "resumen" => array(
        "cierre_diferencia" => valor(valor($cierre, "depurar", array()), "diferencia", null),
        "post_cierre_hallazgos" => valor($postCierre, "hallazgos", array()),
        "stock_bloqueos" => valor($stock, "bloqueos", array()),
        "venta_bloqueos" => $ventaBloqueos,
        "apartado_bloqueos" => $pedidoBloqueos,
        "anticipo_minimo_apartado" => valor(valor($pedido, "depurar", array()), "anticipo_minimo", null),
        "saldo_apartado" => valor(valor($pedido, "depurar", array()), "saldo_estimado", null),
        "monto_esperado_actual" => $montoEsperadoActual,
        "monto_esperado_postventa_estimado" => $montoEsperadoPostVenta,
        "monto_contado_objetivo" => $montoContado,
        "diferencia_postventa_estimada" => $diferenciaPostVenta
    ),
    "hallazgos" => $hallazgos,
    "autorizacion_robusta_recomendada" => !empty($turno) ? $autorizacionCierre : "",
    "autorizaciones_siguientes_preparadas" => array(
        "ejecutar_venta" => empty($ventaBloqueos) ? $autorizacionVenta : "",
        "abrir_turno" => $autorizacionApertura,
        "cargar_stock" => $autorizacionStock
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_vende" => true,
        "no_crea_pedido" => true,
        "no_mueve_kardex" => true
    ),
    "siguiente_paso" => empty($turno)
        ? "Abrir turno y cargar stock antes de probar cierre con diferencia."
        : "Puede solicitarse cierre autorizado con monto contado distinto para validar reportes."
));

function preflightStock($db, $idUsuario, $idAlmacen, $idSku, $cantidad, $referencia, $respaldo) {
    $bloqueos = array();
    if (!$db) {
        return array(
            "sku" => array(),
            "bloqueos" => array("Conexion auxiliar no disponible para validar SKU en consolidado"),
            "referencia" => $referencia,
            "autorizacion_sugerida" => ""
        );
    }
    if ($idAlmacen <= 0) {
        $bloqueos[] = "Indica almacen";
    }
    if ($idSku <= 0) {
        $bloqueos[] = "Indica SKU";
    }
    if ($cantidad <= 0) {
        $bloqueos[] = "Cantidad invalida";
    }
    $sku = array();
    if (empty($bloqueos)) {
        $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, COALESCE(pr.precio, 0) precio
            FROM erp_catalogo_skus s
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku
                AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo'
            WHERE s.id_sku=:sku AND s.estatus='activo'
            LIMIT 1");
        $stmt->execute(array(":sku" => $idSku));
        $sku = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sku) {
            $bloqueos[] = "SKU no encontrado o inactivo";
        }
    }
    return array(
        "sku" => $sku,
        "bloqueos" => $bloqueos,
        "referencia" => $referencia,
        "autorizacion_sugerida" => empty($bloqueos)
            ? "AUTORIZO CARGAR STOCK UAT POS usando respaldo " . etiquetaRespaldoHumana($respaldo) . " con id_usuario=" . $idUsuario . " id_almacen=" . $idAlmacen . " id_sku=" . $idSku . " cantidad=" . numero($cantidad) . " referencia=" . $referencia
            : ""
    );
}

function consultarPostCierreCompacto($db, $idTurno) {
    if (!$db) {
        return array("turno" => array(), "hallazgos" => array("Conexion auxiliar no disponible para post-cierre consolidado"));
    }
    $stmt = $db->prepare("SELECT id_turno_caja, folio, estatus, id_almacen, id_caja, monto_inicial,
            monto_esperado, monto_contado, diferencia, fecha_cierre
        FROM erp_pos_turnos
        WHERE id_turno_caja=:turno
        LIMIT 1");
    $stmt->execute(array(":turno" => $idTurno));
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turno) {
        return array("turno" => array(), "hallazgos" => array("Turno no encontrado"));
    }
    $hallazgos = array();
    if ($turno["estatus"] !== "cerrado") {
        $hallazgos[] = "El turno no esta cerrado.";
    }
    if ($turno["fecha_cierre"] === null || $turno["fecha_cierre"] === "") {
        $hallazgos[] = "El turno no tiene fecha de cierre.";
    }
    if (round(floatval($turno["monto_esperado"]), 6) !== round(floatval($turno["monto_contado"]), 6)) {
        $hallazgos[] = "Monto esperado y contado no coinciden.";
    }
    return array("turno" => $turno, "hallazgos" => $hallazgos);
}

function respuestaBloqueada($mensaje) {
    return array(
        "error" => false,
        "tipo" => "warning",
        "mensaje" => $mensaje,
        "depurar" => array("bloqueos" => array($mensaje))
    );
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function numero($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
}

function etiquetaRespaldoHumana($respaldo) {
    return basename((string) $respaldo) === "artianilocal_respaldo_completo_20260625_post_repair.sql"
        ? "UAT POS vigente"
        : $respaldo;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
