<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: ejecutar resolucion real autorizada de venta rapida POS contra SKU existente.
 * Impacto: actualiza pendiente VRP y detalle de venta; registra evento y notificacion de inventario si aplica.
 * Contrato: BLOQUEADO por defecto; no mueve kardex ni cambia precio/ticket historico.
 */

$args = isset($argv) ? $argv : array();
$params = array(
    "token" => "",
    "respaldo" => "",
    "id_usuario" => 0,
    "folio" => "",
    "id_sku" => 0,
    "decision_inventario" => "mantener_pendiente_regularizacion",
    "confirmacion" => "",
    "motivo" => ""
);
foreach ($args as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $clave = $partes[0];
    $valor = trim($partes[1], "\"' ");
    if (array_key_exists($clave, $params)) { $params[$clave] = $valor; }
}
$params["id_usuario"] = intval($params["id_usuario"]);
$params["id_sku"] = intval($params["id_sku"]);

$validacionRespaldo = validarRespaldo($params["respaldo"]);
$bloqueos = array();
if ($params["token"] !== "VENTAS_POS_VENTA_RAPIDA_RESOLVER_REAL") { $bloqueos[] = "token invalido"; }
if (!$validacionRespaldo["ok"]) { $bloqueos[] = "respaldo no valido"; }
if ($params["id_usuario"] <= 0) { $bloqueos[] = "id_usuario obligatorio"; }
if ($params["folio"] === "") { $bloqueos[] = "folio VRP obligatorio"; }
if ($params["id_sku"] <= 0) { $bloqueos[] = "id_sku obligatorio"; }
if (strtoupper($params["confirmacion"]) !== "RESOLVER VENTA RAPIDA POS") { $bloqueos[] = "confirmacion exacta requerida"; }
if ($params["motivo"] === "") { $bloqueos[] = "motivo obligatorio"; }
if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto resolucion real VRP.",
        "bloqueos" => $bloqueos,
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$dryRunAntes = $modelo->ventaRapidaResolucionDryRun($params);
$respuesta = $modelo->ventaRapidaResolverReal($params);
$detalleDespues = $modelo->ventaRapidaPendienteConsultarReadOnly(array("folio" => $params["folio"]));

responder(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_venta_rapida_resolver_real_authorized",
    "validacion_respaldo" => $validacionRespaldo,
    "entrada" => array(
        "id_usuario" => $params["id_usuario"],
        "folio" => $params["folio"],
        "id_sku" => $params["id_sku"],
        "decision_inventario" => $params["decision_inventario"]
    ),
    "dry_run_antes" => $dryRunAntes,
    "respuesta_modelo" => $respuesta,
    "detalle_despues" => $detalleDespues,
    "contrato" => array(
        "no_mueve_kardex" => true,
        "no_cambia_precio_ticket" => true,
        "conserva_snapshot_manual" => true,
        "regularizacion_inventario_posterior" => true
    )
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente") {
        return array("ok" => true, "tipo" => "referencia_operativa", "referencia" => $respaldo);
    }
    $esRuta = preg_match('/^[A-Za-z]:[\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
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

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}