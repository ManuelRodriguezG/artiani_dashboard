<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: registrar una excepcion comercial POS autorizada sin crear venta ni mover caja/inventario.
 * Impacto: crea trazabilidad en `erp_ventas_excepciones_comerciales` para consumo futuro por venta real.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_EXCEPCION_REAL, respaldo e id_usuario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 5;
$idSku = 1760;
$identificador = "5550000000";
$tipo = "precio_manual";
$precioManual = 285;
$descuentoMonto = 20;
$motivo = "UAT excepcion comercial POS";
$codigoAutorizacion = "SUP-UAT-001";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--precio_manual=") === 0) {
        $precioManual = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--descuento_monto=") === 0) {
        $descuentoMonto = floatval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--codigo_autorizacion=") === 0) {
        $codigoAutorizacion = trim(substr($arg, 22), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_EXCEPCION_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAlmacen <= 0 || $idSku <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro excepcion comercial. Falta autorizacion, respaldo, usuario, almacen o SKU.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_EXCEPCION_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_almacen=ID_ALMACEN",
            "--id_sku=ID_SKU"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$items = json_encode(array(array("id_sku" => $idSku, "cantidad" => 1, "modo_salida" => "existencia_agregada")));
$respuesta = $ventas->registrarExcepcionComercialAutorizada(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => $tipo,
    "id_sku" => $idSku,
    "precio_manual" => $precioManual,
    "descuento_monto" => $tipo === "precio_manual" ? 0 : $descuentoMonto,
    "motivo" => $motivo,
    "codigo_autorizacion" => $codigoAutorizacion,
    "items" => $items,
    "id_usuario" => $idUsuario,
    "solicitado_por" => $idUsuario,
    "autorizado_por" => $idUsuario,
    "observaciones" => "UAT excepcion comercial autorizada sin venta"
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_excepcion_registro_apply_authorized",
    "respuesta" => $respuesta,
    "siguiente_paso" => "Verificar excepcion read-only y despues preparar consumo por venta real con autorizacion separada."
));

function validarRespaldo($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array("ok" => $okReferencia && $okRuta, "referencia_presente" => $okReferencia, "parece_ruta_local" => $esRutaLocal, "archivo_existe" => $esRutaLocal ? $existe : null, "archivo_legible" => $esRutaLocal ? $legible : null, "tamano_bytes" => $tamano);
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
