<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-10.
 * Proposito: aplicar DDL de autorizadores y codigos temporales POS con autorizacion explicita.
 * Impacto: crea tablas de autorizadores/codigos; no crea ventas, no mueve caja, no mueve inventario y no genera codigos.
 * Contrato: escritura de esquema bloqueada por token, respaldo vigente y confirmacion exacta.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$confirmacion = "";
$serverName = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--server_name=") === 0) {
        $serverName = trim(substr($arg, 14), "\"' ");
    }
}

if ($serverName === "" && strtolower($respaldo) === "productivo vigente") {
    $serverName = "sys.artiani.com.mx";
}
if ($serverName !== "") {
    $_SERVER["SERVER_NAME"] = $serverName;
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_AUTORIZADORES_DDL" || !$validacionRespaldo["ok"] || $confirmacion !== "APLICAR AUTORIZADORES POS") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL autorizadores POS. Falta token, respaldo valido o confirmacion exacta.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_AUTORIZADORES_DDL",
            "--respaldo=\"productivo vigente\", \"UAT POS vigente\" o archivo .sql existente",
            "--confirmacion=\"APLICAR AUTORIZADORES POS\""
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarAutorizadoresPos();
$plan = $esquema->planActualizarAutorizadoresPos(true);
$despues = $esquema->auditarAutorizadoresPos();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_pos_autorizadores_schema_apply_authorized",
    "server_name_activo" => isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "",
    "respaldo_ref" => $respaldo,
    "validacion_respaldo" => $validacionRespaldo,
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "contrato" => contrato(true),
    "siguiente_paso" => "Crear autorizador POS, generar codigo temporal y probarlo en precio conjunto sin mostrarlo en ticket."
));

function hayErrores($plan) {
    foreach ($plan as $paso) {
        if (!empty($paso["error"])) {
            return true;
        }
    }
    return false;
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente" || strtolower($respaldo) === "productivo vigente") {
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
        "aplica_ddl" => $aplica,
        "no_crea_autorizador" => true,
        "no_genera_codigo" => true,
        "no_crea_venta" => true,
        "no_modifica_importes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true,
        "codigo_plano_no_existe_en_ddl" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
