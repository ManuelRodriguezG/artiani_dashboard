<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: ejecutar UAT real de venta POS con pago mixto caja + saldo CRM.
 * Impacto: crea venta, descuenta inventario, registra pago caja, descuenta saldo CRM y deja trazabilidad.
 * Contrato: bloqueado por defecto; requiere token VENTAS_POS_SALDO_CRM_REAL y respaldo UAT vigente.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idClienteCrm = 0;
$idSku = 0;
$cantidad = 0;
$precio = 0;
$montoSaldoCrm = 0;
$pagoCaja = 0;
$cliente = "Cliente CRM POS saldo";
$observaciones = "UAT venta POS con saldo CRM";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = redondear(substr($arg, 11));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = redondear(substr($arg, 9));
    } elseif (strpos($arg, "--monto_saldo_crm=") === 0) {
        $montoSaldoCrm = redondear(substr($arg, 18));
    } elseif (strpos($arg, "--pago_caja=") === 0) {
        $pagoCaja = redondear(substr($arg, 12));
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_SALDO_CRM_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idClienteCrm <= 0 || $idSku <= 0 || $cantidad <= 0 || $precio <= 0 || $montoSaldoCrm <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto venta POS con saldo CRM. Falta token, respaldo vigente o datos obligatorios.",
        "validacion_respaldo" => $validacionRespaldo,
        "requisitos" => array(
            "--autorizar=VENTAS_POS_SALDO_CRM_REAL",
            "--respaldo=UAT_POS_VIGENTE_O_RUTA",
            "--id_usuario=ID",
            "--id_cliente_crm=ID",
            "--id_sku=ID",
            "--cantidad=CANTIDAD",
            "--precio=PRECIO",
            "--monto_saldo_crm=MONTO",
            "--pago_caja=MONTO_CAJA_OPCIONAL"
        ),
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$pagos = array();
if ($pagoCaja > 0) {
    $pagos[] = array(
        "id_metodo_pago" => 1,
        "monto" => $pagoCaja,
        "referencia" => "UAT-SALDO-CRM-CAJA"
    );
}
$pagos[] = array(
    "id_metodo_pago" => null,
    "metodo_pago" => "saldo_crm",
    "tipo_pago" => "saldo_cliente",
    "monto" => $montoSaldoCrm,
    "referencia" => "UAT-SALDO-CRM"
);

$datos = array(
    "id_usuario" => $idUsuario,
    "id_cliente" => $idClienteCrm,
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => "",
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )), JSON_UNESCAPED_UNICODE),
    "pagos" => json_encode($pagos, JSON_UNESCAPED_UNICODE),
    "observaciones" => $observaciones
);

$ventas = new VentasErp();
$resultado = $ventas->confirmarVentaPosReal($datos);
$depurar = isset($resultado["depurar"]) && is_array($resultado["depurar"]) ? $resultado["depurar"] : array();

responder(array(
    "ok" => empty($resultado["error"]),
    "modo" => "venta_pos_saldo_crm_real",
    "mensaje" => isset($resultado["mensaje"]) ? $resultado["mensaje"] : "",
    "tipo" => isset($resultado["tipo"]) ? $resultado["tipo"] : "",
    "folio" => isset($depurar["folio"]) ? $depurar["folio"] : null,
    "id_venta" => isset($depurar["id_venta"]) ? $depurar["id_venta"] : null,
    "totales" => isset($depurar["totales"]) ? $depurar["totales"] : null,
    "pagos" => isset($depurar["pagos"]) ? $depurar["pagos"] : array(),
    "inventario" => isset($depurar["inventario"]) ? $depurar["inventario"] : array(),
    "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "rollback" => isset($depurar["rollback"]) ? $depurar["rollback"] : false,
    "validacion_respaldo" => $validacionRespaldo,
    "contrato" => contrato(empty($resultado["error"]))
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $placeholder = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|\\[RUTA|<ruta|ruta real)/i', $respaldo) === 1;
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $esRutaLocal ? file_exists($respaldo) : null;
    $legible = $esRutaLocal ? ($existe && is_readable($respaldo)) : null;
    $tamano = $esRutaLocal && $existe ? filesize($respaldo) : null;
    $respaldoOk = strlen($respaldo) >= 8 && !$placeholder && (!$esRutaLocal || ($existe && $legible && $tamano > 0));
    return array(
        "ok" => $respaldoOk,
        "referencia" => $respaldo,
        "placeholder_detectado" => $placeholder,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $existe,
        "archivo_legible" => $legible,
        "tamano_bytes" => $tamano
    );
}

function contrato($ejecutado) {
    return array(
        "crea_venta" => $ejecutado,
        "descuenta_inventario" => $ejecutado,
        "registra_pago_caja_si_aplica" => $ejecutado,
        "descuenta_saldo_crm" => $ejecutado,
        "mueve_caja_por_saldo_crm" => false,
        "usa_recompensas" => false
    );
}

function redondear($valor) {
    return round(floatval(trim((string) $valor, "\"' ")), 6);
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
