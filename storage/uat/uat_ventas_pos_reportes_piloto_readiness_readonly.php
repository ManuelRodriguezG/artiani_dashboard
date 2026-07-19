<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: concentrar semaforos read-only de reportes POS necesarios para piloto operativo.
 * Impacto: valida que caja, diferencias, evidencias, corte e inventario pendiente sean visibles sin escribir BD.
 * Contrato: solo lectura; no cierra turnos, no resuelve diferencias, no adjunta evidencias y no mueve inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    }
}

$checks = array(
    "reportes_caja" => ejecutar("uat_ventas_pos_reportes_caja_readonly.php", array(
        "--id_almacen=" . $idAlmacen
    )),
    "diferencias_caja" => ejecutar("uat_ventas_pos_diferencias_caja_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen
    )),
    "evidencias_caja" => ejecutar("uat_ventas_pos_caja_evidencias_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen
    )),
    "corte_turno" => ejecutar("uat_ventas_pos_corte_turno_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen
    )),
    "readiness_productivo" => ejecutar("uat_ventas_pos_productivo_readiness_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
        "--cantidad=1"
    ))
);

$bloqueos = array();
$avisos = array();
foreach ($checks as $nombre => $check) {
    if (empty(valor($check, "ok", false))) {
        $bloqueos[] = "Check " . $nombre . " no esta en ok";
    }
}

$resumenReportes = valor(valor($checks["reportes_caja"], "resumen", array()), "turnos", 0);
if (intval($resumenReportes) <= 0) {
    $avisos[] = "No hay turnos en la ventana de reportes; puede ser normal si se filtra otra sucursal.";
}

$diferencias = valor($checks["diferencias_caja"], "resumen", array());
if (intval(valor($diferencias, "total_registros", 0)) > 0) {
    $avisos[] = "Hay diferencias de caja pendientes de revision.";
}

$evidencias = valor($checks["evidencias_caja"], "resumen", array());
if (intval(valor($evidencias, "total_registros", 0)) > 0) {
    $avisos[] = "Hay evidencias de caja pendientes de cierre administrativo.";
}

$productivo = $checks["readiness_productivo"];
foreach (valor($productivo, "avisos", array()) as $aviso) {
    $avisos[] = "readiness_productivo: " . $aviso;
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_reportes_piloto_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku
    ),
    "resumen" => array(
        "turnos_reportados" => intval(valor(valor($checks["reportes_caja"], "resumen", array()), "turnos", 0)),
        "ventas_total_reportado" => floatval(valor(valor($checks["reportes_caja"], "resumen", array()), "ventas_total", 0)),
        "diferencias_pendientes" => intval(valor($diferencias, "total_registros", 0)),
        "evidencias_pendientes" => intval(valor($evidencias, "total_registros", 0)),
        "pendientes_inventario_abiertos" => intval(valor(valor(valor($productivo, "estado_operativo", array()), "pendientes_inventario_pos", array()), "abiertos", 0)),
        "bloqueos" => $bloqueos,
        "avisos" => array_values(array_unique($avisos))
    ),
    "checks" => compactarChecks($checks),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_resuelve_diferencias" => true,
        "no_adjunta_evidencias" => true,
        "no_mueve_inventario" => true
    )
), empty($bloqueos) ? 0 : 1);

function ejecutar($script, $args) {
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    if (!is_file($ruta)) {
        return array("ok" => false, "bloqueos" => array("Script no encontrado: " . $script));
    }
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($ruta);
    foreach ($args as $arg) {
        $cmd .= " " . escapeshellarg($arg);
    }
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $json = json_decode(implode("\n", $lineas), true);
    if (!is_array($json)) {
        return array("ok" => false, "exit_code" => $codigo, "bloqueos" => array("Salida no JSON de " . $script));
    }
    $json["exit_code"] = $codigo;
    return $json;
}

function compactarChecks($checks) {
    $salida = array();
    foreach ($checks as $nombre => $check) {
        $salida[$nombre] = array(
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($check, "modo", null),
            "resumen" => valor($check, "resumen", null),
            "bloqueos" => valor($check, "bloqueos", array()),
            "avisos" => valor($check, "avisos", array())
        );
    }
    return $salida;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
