<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-11.
 * Proposito: aplicar DDL de carga historica de ventas con autorizacion explicita.
 * Impacto: agrega banderas en `erp_ventas` para separar ventas historicas de POS real.
 * Contrato: no crea ventas, no mueve caja, no mueve inventario, no recalcula reportes.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$confirmacion = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = strtoupper(trim(substr($arg, 15), "\"' "));
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_HISTORICAS_DDL" || !$validacionRespaldo["ok"] || $confirmacion !== "APLICAR CARGA HISTORICA VENTAS") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL carga historica ventas. Falta token, respaldo valido o confirmacion exacta.",
        "requerido" => array(
            "--autorizar=VENTAS_HISTORICAS_DDL",
            "--respaldo=\"respaldo productivo vigente\" o archivo .sql existente",
            "--confirmacion=\"APLICAR CARGA HISTORICA VENTAS\""
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../..");
require_once "app/iniciador.php";
require_once "app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarVentasHistoricas();

if (empty($antes["depurar"]["existe"])) {
    responder(array(
        "ok" => false,
        "modo" => "ventas_historicas_schema_apply_authorized",
        "mensaje" => "DDL detenido antes de ejecutar: el contexto actual no ve la tabla erp_ventas.",
        "respaldo_ref" => $respaldo,
        "validacion_respaldo" => $validacionRespaldo,
        "contexto" => contextoSeguro(),
        "auditoria_antes" => $antes,
        "contrato" => contrato(false)
    ));
}

$plan = $esquema->planActualizarVentasHistoricas(true);
$despues = $esquema->auditarVentasHistoricas();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_historicas_schema_apply_authorized",
    "respaldo_ref" => $respaldo,
    "validacion_respaldo" => $validacionRespaldo,
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "contrato" => contrato(true),
    "siguiente_paso" => "Crear vista/endpoints de carga historica con afecta_inventario=0 y afecta_caja=0."
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
    if ($respaldo === "respaldo productivo vigente" || $respaldo === "productivo vigente") {
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
        "no_crea_venta" => true,
        "no_modifica_ventas_existentes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_crea_kardex" => true,
        "no_toca_ecommerce" => true
    );
}

function contextoSeguro() {
    return array(
        "cwd" => getcwd(),
        "server_name" => isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : null,
        "mysql_base" => defined("MYSQLBASE") ? MYSQLBASE : null,
        "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control"
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
