<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: diagnosticar estado parcial de una UAT POS multiusuario interrumpida usando checks read-only probados.
 * Impacto: ayuda a decidir si falta abrir/cerrar turno, cargar stock, cobrar atencion o recolectar evidencia.
 * Contrato: read-only; no corrige, no revierte, no cierra turno, no ajusta inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    }
}

$checks = array();
$checks[] = ejecutar("configuracion_pos", array(
    "uat_ventas_pos_configuracion_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen
), false);
$checks[] = ejecutar("inventario_sku", array(
    "uat_ventas_pos_inventario_sku_readonly.php",
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--cantidad_requerida=1"
), true);
$checks[] = ejecutar("post_conversion", array(
    "uat_ventas_pos_atencion_conversion_post_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--compact=1"
), true);
$checks[] = ejecutar("semaforo_cierre", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_atencion=" . $idAtencion,
    "--id_sku=" . $idSku,
    "--compact=1"
), true);

$config = buscarCheck($checks, "configuracion_pos");
$inventario = buscarCheck($checks, "inventario_sku");
$conversion = buscarCheck($checks, "post_conversion");
$semaforo = buscarCheck($checks, "semaforo_cierre");

$resConfig = valor(valor($config, "salida_json", array()), "resumen", array());
$contextoConfig = valor(valor($config, "salida_json", array()), "contexto", array());
$resInventario = valor(valor($inventario, "salida_json", array()), "resumen", array());
$resConversion = valor(valor($conversion, "salida_json", array()), "resumen", array());
$bloqueosSemaforo = valor(valor($semaforo, "salida_json", array()), "bloqueos_para_cobro_atencion", array());

$estado = array(
    "usuario_asignado" => !empty($resConfig["usuario_asignado"]) || !empty($contextoConfig["asignacion_activa"]),
    "turno_abierto" => intval(valor($resConfig, "turnos_abiertos_almacen", valor($resConfig, "turnos_abiertos", 0))) > 0 || !empty($contextoConfig["turno_abierto"]),
    "inventario_cubre" => !empty($resInventario["cubre_cantidad_requerida"]),
    "reservas_activas" => intval(valor($resInventario, "reservas_activas", 0)),
    "pendientes_pos_abiertos" => intval(valor($resInventario, "pendientes_pos_abiertos", 0)),
    "atencion_convertida" => valor($resConversion, "estatus_atencion", "") === "convertida",
    "venta_ligada" => intval(valor($resConversion, "id_venta_convertida", 0)) > 0,
    "venta_confirmada" => valor($resConversion, "estatus_venta", "") === "confirmada"
);

$recomendaciones = array();
if (!$estado["usuario_asignado"]) {
    $recomendaciones[] = "Asignar usuario a caja/terminal antes de operar.";
}
if (!$estado["turno_abierto"] && !$estado["venta_ligada"]) {
    $recomendaciones[] = "Si aun no inicio el ciclo: abrir turno desde ciclo autorizado.";
}
if (!$estado["inventario_cubre"] && !$estado["venta_ligada"]) {
    $recomendaciones[] = "Si aun no inicio el ciclo: cargar stock UAT desde ciclo autorizado.";
}
if ($estado["venta_ligada"] && $estado["turno_abierto"]) {
    $recomendaciones[] = "Venta ligada y turno abierto: recolectar evidencia y cerrar turno autorizado.";
}
if ($estado["venta_ligada"] && !$estado["turno_abierto"]) {
    $recomendaciones[] = "Venta ligada y sin turno abierto: correr suite de evidencia post-ciclo.";
}
if (!$estado["venta_ligada"] && empty($bloqueosSemaforo)) {
    $recomendaciones[] = "Sin venta ligada y sin bloqueos: ejecutar cobro autorizado o revisar si el ciclo se detuvo antes del cobro.";
}

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_ciclo_recuperacion_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion
    ),
    "estado" => $estado,
    "recomendaciones" => array_values(array_unique($recomendaciones)),
    "checks" => compactarChecks($checks),
    "contrato" => array(
        "read_only" => true,
        "no_corrige" => true,
        "no_revierte" => true,
        "no_cierra_turno" => true,
        "no_mueve_inventario" => true
    )
));

function ejecutar($nombre, $argumentos, $permitirFalla = false) {
    $script = array_shift($argumentos);
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($ruta);
    foreach ($argumentos as $arg) {
        $cmd .= " " . escapeshellarg($arg);
    }
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $texto = implode("\n", $lineas);
    $json = json_decode($texto, true);
    return array(
        "paso" => $nombre,
        "ok" => $codigo === 0 || $permitirFalla,
        "exit_code" => $codigo,
        "permitir_falla" => $permitirFalla,
        "salida_json" => is_array($json) ? $json : null,
        "salida_texto" => is_array($json) ? null : $texto
    );
}

function buscarCheck($checks, $paso) {
    foreach ($checks as $check) {
        if (valor($check, "paso", "") === $paso) {
            return $check;
        }
    }
    return array();
}

function compactarChecks($checks) {
    $compactos = array();
    foreach ($checks as $check) {
        $json = valor($check, "salida_json", array());
        $compactos[] = array(
            "paso" => valor($check, "paso", ""),
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($json, "modo", null),
            "resumen" => valor($json, "resumen", null),
            "bloqueos_para_cobro_atencion" => valor($json, "bloqueos_para_cobro_atencion", null)
        );
    }
    return $compactos;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
