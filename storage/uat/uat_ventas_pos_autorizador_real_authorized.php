<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-10.
 * Proposito: crear/actualizar un autorizador POS real con autorizacion explicita.
 * Impacto: escribe en `erp_pos_autorizadores`; no genera codigos, no crea ventas, no mueve caja ni inventario.
 * Contrato: bloqueado por token, respaldo vigente, usuario ejecutor y motivo.
 */

$args = isset($argv) ? $argv : array();
$params = array(
    "autorizar" => "",
    "respaldo" => "",
    "id_usuario_ejecuta" => 0,
    "id_usuario_autorizador" => 0,
    "rol" => "supervisor",
    "alcance" => "global",
    "id_almacen" => 0,
    "id_caja" => 0,
    "tipo" => "precio_conjunto",
    "monto_maximo" => 0,
    "descuento_max_porcentaje" => 0,
    "vigencia_dias" => 365,
    "motivo" => ""
);

foreach ($args as $arg) {
    foreach ($params as $key => $value) {
        $prefix = "--" . $key . "=";
        if (strpos($arg, $prefix) === 0) {
            $params[$key] = trim(substr($arg, strlen($prefix)), "\"' ");
        }
    }
}

$params["id_usuario_ejecuta"] = intval($params["id_usuario_ejecuta"]);
$params["id_usuario_autorizador"] = intval($params["id_usuario_autorizador"]);
$params["id_almacen"] = intval($params["id_almacen"]);
$params["id_caja"] = intval($params["id_caja"]);
$params["monto_maximo"] = floatval($params["monto_maximo"]);
$params["descuento_max_porcentaje"] = floatval($params["descuento_max_porcentaje"]);
$params["vigencia_dias"] = intval($params["vigencia_dias"]);

$validacionRespaldo = validarRespaldo($params["respaldo"]);
$bloqueos = array();
if ($params["autorizar"] !== "VENTAS_POS_AUTORIZADOR_REAL") {
    $bloqueos[] = "Token invalido";
}
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "Respaldo/referencia vigente invalida";
}
if ($params["id_usuario_ejecuta"] <= 0) {
    $bloqueos[] = "id_usuario_ejecuta obligatorio";
}
if ($params["id_usuario_autorizador"] <= 0) {
    $bloqueos[] = "id_usuario_autorizador obligatorio";
}
if (trim((string) $params["motivo"]) === "") {
    $bloqueos[] = "motivo obligatorio";
}
if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo autorizador POS. Falta token, respaldo, usuarios o motivo.",
        "bloqueos" => $bloqueos,
        "requerido" => array(
            "--autorizar=VENTAS_POS_AUTORIZADOR_REAL",
            "--respaldo=\"productivo vigente\"",
            "--id_usuario_ejecuta=1",
            "--id_usuario_autorizador=1",
            "--motivo=\"...\""
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$antes = $modelo->configuracionPosReadOnly();
$datos = array(
    "id_usuario" => $params["id_usuario_autorizador"],
    "rol_autorizador" => $params["rol"],
    "alcance" => $params["alcance"],
    "id_almacen" => $params["id_almacen"],
    "id_caja" => $params["id_caja"],
    "tipo_autorizacion" => $params["tipo"],
    "descuento_max_porcentaje" => $params["descuento_max_porcentaje"],
    "monto_maximo" => $params["monto_maximo"],
    "vigencia_dias" => $params["vigencia_dias"],
    "observaciones" => $params["motivo"]
);
$validacion = $modelo->configuracionAutorizadorPosDryRun($datos);
$resultado = $modelo->configuracionAutorizadorPosGuardarReal($datos, $params["id_usuario_ejecuta"]);
$despues = $modelo->configuracionPosReadOnly();

responder(array(
    "ok" => empty($resultado["error"]) && isset($resultado["tipo"]) && $resultado["tipo"] === "success",
    "modo" => "ventas_pos_autorizador_real_authorized",
    "respaldo_ref" => $params["respaldo"],
    "validacion_respaldo" => $validacionRespaldo,
    "validacion" => $validacion,
    "resultado" => $resultado,
    "conteo_antes" => conteoAutorizadores($antes),
    "conteo_despues" => conteoAutorizadores($despues),
    "contrato" => contrato(true),
    "siguiente_paso" => "Generar codigo temporal POS para precio conjunto y probarlo en paquete."
));

function conteoAutorizadores($respuesta) {
    if (!isset($respuesta["depurar"]["autorizadores_pos"]) || !is_array($respuesta["depurar"]["autorizadores_pos"])) {
        return null;
    }
    return count($respuesta["depurar"]["autorizadores_pos"]);
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if (strtolower($respaldo) === "productivo vigente" || $respaldo === "UAT POS vigente") {
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

function contrato($aplica) {
    return array(
        "escritura" => $aplica ? "erp_pos_autorizadores" : false,
        "no_genera_codigo" => true,
        "no_crea_venta" => true,
        "no_modifica_importes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
