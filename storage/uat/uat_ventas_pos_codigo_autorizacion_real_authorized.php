<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-10.
 * Proposito: generar codigo POS real temporal o maestro con autorizacion explicita.
 * Impacto: escribe en `erp_pos_codigos_autorizacion`; no crea ventas, no mueve caja ni inventario.
 * Contrato: bloqueado por token, respaldo vigente, usuario ejecutor, autorizador activo y motivo.
 */

$args = isset($argv) ? $argv : array();
$params = array(
    "autorizar" => "",
    "respaldo" => "",
    "id_usuario_ejecuta" => 0,
    "id_autorizador_pos" => 0,
    "tipo" => "precio_conjunto",
    "uso_maximo" => 1,
    "expira_minutos" => 60,
    "codigo_maestro" => 0,
    "uso_ilimitado" => 0,
    "sin_caducidad" => 0,
    "monto_maximo" => 0,
    "motivo" => "",
    "server_name" => ""
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
$params["id_autorizador_pos"] = intval($params["id_autorizador_pos"]);
$params["uso_maximo"] = intval($params["uso_maximo"]);
$params["expira_minutos"] = intval($params["expira_minutos"]);
$params["codigo_maestro"] = intval($params["codigo_maestro"]);
$params["uso_ilimitado"] = intval($params["uso_ilimitado"]);
$params["sin_caducidad"] = intval($params["sin_caducidad"]);
$params["monto_maximo"] = floatval($params["monto_maximo"]);

$serverName = trim((string) $params["server_name"]);
if ($serverName === "" && strtolower(trim((string) $params["respaldo"])) === "productivo vigente") {
    $serverName = "sys.artiani.com.mx";
}
if ($serverName !== "") {
    $_SERVER["SERVER_NAME"] = $serverName;
}

$validacionRespaldo = validarRespaldo($params["respaldo"]);
$bloqueos = array();
if ($params["autorizar"] !== "VENTAS_POS_CODIGO_AUTORIZACION_REAL") {
    $bloqueos[] = "Token invalido";
}
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "Respaldo/referencia vigente invalida";
}
if ($params["id_usuario_ejecuta"] <= 0) {
    $bloqueos[] = "id_usuario_ejecuta obligatorio";
}
if ($params["id_autorizador_pos"] <= 0) {
    $bloqueos[] = "id_autorizador_pos obligatorio";
}
if (trim((string) $params["motivo"]) === "") {
    $bloqueos[] = "motivo obligatorio";
}
if (!empty($bloqueos)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se genero codigo POS. Falta token, respaldo, autorizador o motivo.",
        "bloqueos" => $bloqueos,
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
    "id_autorizador_pos" => $params["id_autorizador_pos"],
    "tipo_autorizacion" => $params["tipo"],
    "uso_maximo" => $params["uso_maximo"],
    "expira_minutos" => $params["expira_minutos"],
    "codigo_maestro" => $params["codigo_maestro"],
    "uso_ilimitado" => $params["uso_ilimitado"],
    "sin_caducidad" => $params["sin_caducidad"],
    "monto_maximo" => $params["monto_maximo"],
    "motivo" => $params["motivo"]
);
$validacion = $modelo->configuracionCodigoAutorizacionPosDryRun($datos);
$resultado = $modelo->configuracionCodigoAutorizacionPosGenerarReal($datos, $params["id_usuario_ejecuta"]);
$despues = $modelo->configuracionPosReadOnly();

responder(array(
    "ok" => empty($resultado["error"]) && isset($resultado["tipo"]) && $resultado["tipo"] === "success",
    "modo" => "ventas_pos_codigo_autorizacion_real_authorized",
    "respaldo_ref" => $params["respaldo"],
    "validacion_respaldo" => $validacionRespaldo,
    "validacion" => $validacion,
    "resultado" => $resultado,
    "conteo_antes" => conteoCodigos($antes),
    "conteo_despues" => conteoCodigos($despues),
    "contrato" => contrato(true),
    "siguiente_paso" => "Usar el codigo en el modal Paquete / Precio conjunto. El codigo completo se muestra una sola vez."
));

function conteoCodigos($respuesta) {
    if (!isset($respuesta["depurar"]["codigos_autorizacion_pos"]) || !is_array($respuesta["depurar"]["codigos_autorizacion_pos"])) {
        return null;
    }
    return count($respuesta["depurar"]["codigos_autorizacion_pos"]);
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
        "escritura" => $aplica ? "erp_pos_codigos_autorizacion" : false,
        "no_crea_autorizador" => true,
        "no_crea_venta" => true,
        "no_modifica_importes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true,
        "codigo_plano_solo_en_resultado" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
