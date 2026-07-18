<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: consolidar validaciones post-apply de listas por segmento CRM.
 * Impacto: entrega un resultado unico para acceptance, baseline de ventas y estado go/no-go.
 * Contrato: read-only; solo invoca scripts read-only, no ejecuta apply_authorized ni modifica BD.
 */

$idLista = obtenerArgSuite("id_lista_precio", 2);
$codigoSegmento = obtenerArgTextoSuite("codigo_segmento", "RECURRENTE");
$idCliente = obtenerArgSuite("id_cliente_crm", 2);
$idSku = obtenerArgSuite("id_sku", 1760);
$idAlmacen = obtenerArgSuite("id_almacen", 5);
$canal = obtenerArgTextoSuite("canal", "pos");
$ventasTotal = obtenerArgSuite("ventas_total", 23);
$ventasMax = obtenerArgSuite("ventas_max_id", 26);
$detalleTotal = obtenerArgSuite("detalle_total", 24);
$detalleMax = obtenerArgSuite("detalle_max_id", 27);

$base = __DIR__;
$scripts = array(
    "go_nogo" => array(
        "script" => $base . DIRECTORY_SEPARATOR . "uat_listas_precios_segmentos_go_nogo_readonly.php",
        "args" => array()
    ),
    "acceptance" => array(
        "script" => $base . DIRECTORY_SEPARATOR . "uat_listas_precios_segmentos_post_apply_acceptance_readonly.php",
        "args" => array($idLista, $codigoSegmento, $idCliente, $idSku, $idAlmacen, $canal)
    ),
    "baseline_ventas" => array(
        "script" => $base . DIRECTORY_SEPARATOR . "uat_listas_precios_segmentos_ventas_baseline_compare_readonly.php",
        "args" => array(
            "--ventas_total=" . $ventasTotal,
            "--ventas_max_id=" . $ventasMax,
            "--detalle_total=" . $detalleTotal,
            "--detalle_max_id=" . $detalleMax
        )
    )
);

$resultados = array();
$bloqueos = array();
foreach ($scripts as $id => $config) {
    $resultado = ejecutarJsonSuite($config["script"], $config["args"]);
    $resultados[$id] = $resultado;
    if (empty($resultado["ok"])) {
        $bloqueos[] = $id . "_no_ok";
    }
}

$acceptancePass = isset($resultados["acceptance"]["resultado"]) && $resultados["acceptance"]["resultado"] === "PASS_POST_APPLY";
$baselinePass = isset($resultados["baseline_ventas"]["resultado"]) && $resultados["baseline_ventas"]["resultado"] === "BASELINE_VENTAS_INTACTA";
$origenSegmento = valorSuitePostApply($resultados, array("acceptance", "estado", "origen_resolutor"), "");

if (!$acceptancePass) {
    $bloqueos[] = "acceptance_no_pass";
}
if (!$baselinePass) {
    $bloqueos[] = "baseline_ventas_no_intacta";
}
if ($origenSegmento !== "lista_segmento_cliente") {
    $bloqueos[] = "origen_resolutor_no_segmento";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_SUITE_POST_APPLY" : "PENDIENTE_O_FAIL_SUITE_POST_APPLY",
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "id_cliente_crm" => $idCliente,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "canal" => $canal,
        "baseline" => array(
            "ventas_total" => $ventasTotal,
            "ventas_max_id" => $ventasMax,
            "detalle_total" => $detalleTotal,
            "detalle_max_id" => $detalleMax
        )
    ),
    "resumen" => array(
        "go_nogo_decision" => valorSuitePostApply($resultados, array("go_nogo", "decision"), null),
        "acceptance_resultado" => valorSuitePostApply($resultados, array("acceptance", "resultado"), null),
        "baseline_resultado" => valorSuitePostApply($resultados, array("baseline_ventas", "resultado"), null),
        "origen_resolutor" => $origenSegmento
    ),
    "resultados" => $resultados,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "guardrails" => array(
        "solo_invoca_readonly" => true,
        "no_invoca_apply_authorized" => true,
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function ejecutarJsonSuite($script, $args) {
    if (!is_readable($script)) {
        return array("ok" => false, "error" => "script_no_legible", "script" => $script);
    }
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($script);
    foreach ($args as $arg) {
        $cmd .= " " . escapeshellarg((string) $arg);
    }
    $salida = shell_exec($cmd);
    $json = json_decode((string) $salida, true);
    if (!is_array($json)) {
        return array(
            "ok" => false,
            "error" => "salida_json_invalida",
            "script" => basename($script),
            "salida" => $salida
        );
    }
    return $json;
}

function obtenerArgSuite($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return intval(substr($arg, strlen($prefijo)));
        }
    }
    return intval($default);
}

function obtenerArgTextoSuite($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return trim((string) substr($arg, strlen($prefijo)));
        }
    }
    return $default;
}

function valorSuitePostApply($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
