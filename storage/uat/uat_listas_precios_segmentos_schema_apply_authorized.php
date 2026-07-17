<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: aplicar DDL de `erp_segmentos_listas_precios` solo con token y respaldo externo.
 * Impacto: crea tabla puente para vincular segmentos CRM con listas de precios.
 * Contrato: bloqueado por defecto; no crea segmentos, no asigna listas, no cambia precios ni ventas pasadas.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoListasSegmentos($respaldo);

if ($autorizar !== "VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL" || !$validacion["ok"]) {
    responderListasSegmentos(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto DDL de listas por segmentos. Falta token o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_LISTAS_PRECIOS_SEGMENTOS_DDL",
            "--respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_HHmmss_antes_listas_precios_segmentos.sql"
        ),
        "validacion_respaldo" => $validacion,
        "alcance" => array(
            "crea_tabla_erp_segmentos_listas_precios" => true,
            "crea_segmentos_crm" => false,
            "asigna_clientes" => false,
            "asigna_listas" => false,
            "modifica_precios" => false,
            "modifica_ventas_pasadas" => false,
            "toca_pos" => false,
            "toca_ecommerce" => false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/VentasErpEsquema.php";

$modelo = new VentasErpEsquema();
$antes = $modelo->auditarSegmentosListasPrecios();
$respuesta = $modelo->planActualizarSegmentosListasPrecios(true);
$despues = $modelo->auditarSegmentosListasPrecios();

responderListasSegmentos(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "apply_authorized",
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "validacion_respaldo" => $validacion,
    "auditoria_antes" => $antes,
    "respuesta" => $respuesta,
    "auditoria_despues" => $despues,
    "siguiente_paso" => "Crear o activar segmentos CRM base y ejecutar UAT de lista por segmento."
));

function validarRespaldoListasSegmentos($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $placeholder = respaldoPlaceholderListasSegmentos($respaldo);
    return array(
        "ok" => $respaldo !== "" && strlen($respaldo) >= 8 && !$placeholder && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano_bytes" => $tamano,
        "placeholder_bloqueado" => $placeholder
    );
}

function respaldoPlaceholderListasSegmentos($valor) {
    $valor = strtoupper(trim((string) $valor));
    return $valor === ""
        || strpos($valor, "RUTA_O_REFERENCIA") !== false
        || strpos($valor, "YYYYMMDD") !== false
        || strpos($valor, "PLACEHOLDER") !== false;
}

function responderListasSegmentos($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit;
}
