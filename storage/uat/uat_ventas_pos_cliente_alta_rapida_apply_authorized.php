<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-28.
 * Proposito: crear cliente express POS UAT solo con autorizacion explicita.
 * Impacto: escribe CRM canonico; no crea ventas, pagos, lista ni mezcla legacy/ecommerce.
 * Contrato: bloqueado por defecto; requiere respaldo externo validado y preflight sin duplicados.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAlmacen = 5;
$nombre = "";
$identificador = "";
$consentimiento = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--nombre=") === 0) {
        $nombre = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--consentimiento=") === 0) {
        $consentimiento = intval(trim(substr($arg, 17), "\"' "));
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_CLIENTE_ALTA_RAPIDA" || !$validacionRespaldo["ok"] || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo cliente. Falta autorizacion, respaldo valido o usuario.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CLIENTE_ALTA_RAPIDA",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_almacen=ID",
            "--nombre=NOMBRE_PUBLICO",
            "--identificador=TELEFONO_CORREO_CODIGO"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "reglas" => array(
            "No crea ventas, pagos ni movimientos de caja.",
            "No vincula clientes legacy/ecommerce.",
            "Bloquea identificador duplicado antes de insertar."
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ClientesCrm.php";

$crm = new ClientesCrm();
$resultado = $crm->altaRapidaCrearAutorizado(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "nombre_publico" => $nombre,
    "identificador" => $identificador,
    "consentimiento_contacto" => $consentimiento,
    "origen_alta" => "pos_uat"
));

responder(array(
    "ok" => empty($resultado["error"]) && $resultado["tipo"] === "success",
    "modo" => "cliente_alta_rapida_apply_authorized",
    "respaldo_ref" => $respaldo,
    "resultado" => $resultado,
    "siguiente_paso" => empty($resultado["error"]) && $resultado["tipo"] === "success"
        ? "Validar cliente/precio dry-run con el nuevo identificador CRM y registrar evidencia."
        : "Resolver bloqueos antes de intentar crear cliente real."
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    return array(
        "ok" => $respaldo !== "" && is_file($respaldo) && is_readable($respaldo),
        "referencia_presente" => $respaldo !== "",
        "parece_ruta_local" => preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false,
        "archivo_existe" => $respaldo !== "" && is_file($respaldo),
        "archivo_legible" => $respaldo !== "" && is_readable($respaldo),
        "tamano_bytes" => ($respaldo !== "" && is_file($respaldo)) ? filesize($respaldo) : 0
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
