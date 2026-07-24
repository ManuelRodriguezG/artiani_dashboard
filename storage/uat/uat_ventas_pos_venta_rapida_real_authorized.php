<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-23.
 * Proposito: ejecutar UAT real autorizada de venta rapida controlada POS.
 * Impacto: si hay turno abierto y pagos validos, crea venta, pago/caja, detalle provisional, pendiente VRP, evento y notificacion.
 * Contrato: no crea SKU definitivo, no mueve inventario/kardex y no toca ecommerce.
 */

$args = isset($argv) ? $argv : array();
$params = array(
    "token" => "",
    "respaldo" => "",
    "id_usuario" => 0,
    "descripcion" => "",
    "cantidad" => 0,
    "precio" => 0,
    "pago" => 0,
    "motivo" => "",
    "cliente" => "Cliente UAT venta rapida",
    "controla_inventario" => 1,
);

foreach ($args as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
        continue;
    }
    $partes = explode("=", substr($arg, 2), 2);
    $clave = $partes[0];
    $valor = trim($partes[1], "\"' ");
    if (array_key_exists($clave, $params)) {
        $params[$clave] = $valor;
    }
}

$params["id_usuario"] = intval($params["id_usuario"]);
$params["cantidad"] = floatval($params["cantidad"]);
$params["precio"] = floatval($params["precio"]);
$params["pago"] = floatval($params["pago"]);
$params["controla_inventario"] = intval($params["controla_inventario"]);

$validacionRespaldo = validarRespaldo($params["respaldo"]);
$bloqueos = array();
if ($params["token"] !== "VENTAS_POS_VENTA_RAPIDA_REAL") {
    $bloqueos[] = "Token invalido";
}
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "Respaldo no valido";
}
if ($params["id_usuario"] <= 0) {
    $bloqueos[] = "id_usuario obligatorio";
}
if (strlen($params["descripcion"]) < 12) {
    $bloqueos[] = "descripcion detallada obligatoria";
}
if ($params["cantidad"] <= 0) {
    $bloqueos[] = "cantidad debe ser mayor a cero";
}
if ($params["precio"] <= 0) {
    $bloqueos[] = "precio debe ser mayor a cero";
}
if ($params["pago"] <= 0) {
    $bloqueos[] = "pago debe ser mayor a cero";
}
if (trim($params["motivo"]) === "") {
    $bloqueos[] = "motivo obligatorio";
}
if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto UAT real venta rapida POS. Faltan datos autorizados.",
        "bloqueos" => $bloqueos,
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$item = array(
    "tipo_partida" => "venta_rapida",
    "origen_partida" => "venta_rapida_controlada",
    "descripcion_manual" => $params["descripcion"],
    "descripcion" => $params["descripcion"],
    "cantidad" => $params["cantidad"],
    "precio_unitario" => $params["precio"],
    "motivo" => $params["motivo"],
    "controla_inventario" => $params["controla_inventario"],
);

$pago = array(
    "id_metodo_pago" => 1,
    "metodo_pago" => "efectivo",
    "monto" => $params["pago"],
    "referencia" => "UAT-VRP-" . date("Ymd-His"),
);

$modelo = new VentasErp();
$respuesta = $modelo->confirmarVentaPosReal(array(
    "id_usuario" => $params["id_usuario"],
    "cliente_nombre_publico" => $params["cliente"],
    "items" => json_encode(array($item)),
    "pagos" => json_encode(array($pago)),
    "autorizar_venta_rapida_real" => "VENTAS_POS_VENTA_RAPIDA_REAL_MODELO",
    "token" => "VENTAS_POS_VENTA_RAPIDA_REAL",
    "observaciones" => "UAT real venta rapida controlada POS",
));

responder(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_venta_rapida_real_authorized",
    "validacion_respaldo" => $validacionRespaldo,
    "entrada" => array(
        "id_usuario" => $params["id_usuario"],
        "descripcion" => $params["descripcion"],
        "cantidad" => $params["cantidad"],
        "precio" => $params["precio"],
        "pago" => $params["pago"],
        "motivo" => $params["motivo"],
    ),
    "respuesta_modelo" => $respuesta,
    "contrato" => contrato(true)
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente") {
        return array("ok" => true, "tipo" => "referencia_operativa", "referencia" => $respaldo);
    }
    $esRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    if ($esRuta) {
        return array(
            "ok" => is_file($respaldo) && is_readable($respaldo),
            "tipo" => "archivo",
            "ruta" => $respaldo,
            "existe" => is_file($respaldo),
            "legible" => is_readable($respaldo),
            "tamano" => is_file($respaldo) ? filesize($respaldo) : null
        );
    }
    return array("ok" => false, "tipo" => "invalido", "recibido" => $respaldo);
}

function contrato($ejecucionRealIntentada) {
    return array(
        "ejecucion_real_intentada" => $ejecucionRealIntentada,
        "no_crea_sku" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true,
        "requiere_turno_abierto" => true,
        "si_exitoso_crea_venta_pago_caja_detalle_pendiente_evento_notificacion" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
