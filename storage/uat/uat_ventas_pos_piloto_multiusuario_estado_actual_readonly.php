<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: resumir el estado vigente del piloto POS multiusuario sin depender de UATs historicas.
 * Impacto: permite decidir el siguiente paso operativo por folio/turno actual sin mover caja ni inventario.
 * Contrato: read-only; no abre turno, no cobra, no cierra caja, no reserva y no modifica inventario.
 */

$folioVenta = "POS-20260717-000001";
$idUsuarioSupervisor = 1;
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
$idSku = 1760;
$usuarios = "1,2,3";
$montoContado = 795;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folioVenta = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_usuario_supervisor=") === 0) {
        $idUsuarioSupervisor = intval(trim(substr($arg, 24), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--usuarios=") === 0) {
        $usuarios = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(str_replace(",", ".", trim(substr($arg, 16), "\"' ")));
    }
}

$checks = array(
    "venta" => ejecutar("uat_ventas_pos_post_venta_readonly.php", array("--folio=" . $folioVenta)),
    "ticket" => ejecutar("uat_ventas_pos_ticket_formal_readonly.php", array("--folio=" . $folioVenta)),
    "cierre_preflight" => ejecutar("uat_ventas_pos_turno_cierre_preflight_readonly.php", array(
        "--id_usuario=" . $idUsuarioSupervisor,
        "--monto_contado=" . $montoContado,
        "--respaldo=C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql"
    )),
    "productivo" => ejecutar("uat_ventas_pos_productivo_readiness_readonly.php", array(
        "--id_usuario=" . $idUsuarioSupervisor,
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
        "--cantidad=1"
    )),
    "multiusuario" => ejecutar("uat_ventas_pos_multiusuario_preflight_readonly.php", array(
        "--usuarios=" . $usuarios,
        "--id_almacen=" . $idAlmacen,
        "--id_caja=" . $idCaja,
        "--id_terminal=" . $idTerminal
    )),
    "escaner_ui" => ejecutar("uat_ventas_pos_escaner_ui_readiness_readonly.php", array()),
    "evidencias_caja" => ejecutar("uat_ventas_pos_caja_evidencias_readonly.php", array())
);

$bloqueos = array();
$avisos = array();
foreach ($checks as $nombre => $check) {
    if (empty($check["ok"])) {
        $bloqueos[] = "Check " . $nombre . " no esta en ok";
    }
    foreach (valor($check, "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $nombre . ": " . texto($bloqueo);
    }
    foreach (valor($check, "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . texto($aviso);
    }
    foreach (valor(valor($check, "resumen", array()), "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $nombre . ": " . texto($bloqueo);
    }
    foreach (valor(valor($check, "resumen", array()), "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . texto($aviso);
    }
}

$venta = valor($checks["venta"], "venta", array());
$cuadres = valor($checks["venta"], "cuadres", array());
$cierre = valor($checks["cierre_preflight"], "resumen", array());
$contextoCierre = valor($checks["cierre_preflight"], "contexto", array());
$productivoBloqueos = valor($checks["productivo"], "bloqueos", array());
$evidenciasResumen = valor($checks["evidencias_caja"], "resumen", array());
$evidenciasPendientes = intval(valor($evidenciasResumen, "total_registros", 0));

if ($evidenciasPendientes > 0) {
    $avisos[] = "Hay " . $evidenciasPendientes . " evidencia(s) historica(s) de caja pendiente(s); no bloquea el cierre actual, pero debe revisarse administrativamente.";
}

$ventaPagada = valor($venta, "estatus", "") === "pagada";
$ticketOk = !empty(valor(valor($checks["ticket"], "resumen", array()), "checks", array())["tiene_ticket_pos"]);
$cierreSinBloqueos = empty(valor($cierre, "bloqueos", array()));
$productivoSinBloqueos = empty($productivoBloqueos);
$multiusuarioOk = !empty(valor($checks["multiusuario"], "ok", false));
$escanerOk = !empty(valor($checks["escaner_ui"], "ok", false));

if (!$ventaPagada) {
    $bloqueos[] = "La venta " . $folioVenta . " no esta pagada.";
}
if (!$ticketOk) {
    $bloqueos[] = "El ticket formal no confirma encabezado POS.";
}
if (!$cierreSinBloqueos) {
    $bloqueos[] = "El preflight de cierre tiene bloqueos.";
}
if (!$productivoSinBloqueos) {
    $bloqueos[] = "El readiness productivo tiene bloqueos.";
}
if (!$multiusuarioOk) {
    $bloqueos[] = "El preflight multiusuario no esta listo.";
}
if (!$escanerOk) {
    $bloqueos[] = "La superficie de escaner POS no esta lista.";
}

$decision = empty($bloqueos) ? "listo_para_cerrar_turno_piloto" : "requiere_atencion";

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_multiusuario_estado_actual_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => $decision,
    "folio_venta" => $folioVenta,
    "resumen" => array(
        "venta_pagada" => $ventaPagada,
        "id_venta" => intval(valor($venta, "id_venta", 0)),
        "total" => floatval(valor($venta, "total", 0)),
        "pagado_total" => floatval(valor($venta, "pagado_total", 0)),
        "partidas" => intval(valor($cuadres, "partidas", 0)),
        "pagos" => intval(valor($cuadres, "pagos", 0)),
        "garantias" => intval(valor($cuadres, "garantias", 0)),
        "trazabilidades" => intval(valor($cuadres, "trazabilidades", 0)),
        "turno_abierto" => !empty(valor($contextoCierre, "turno_abierto", false)),
        "folio_turno" => valor($contextoCierre, "folio_turno", ""),
        "id_turno_caja" => intval(valor($contextoCierre, "id_turno_caja", 0)),
        "monto_esperado" => floatval(valor($cierre, "monto_esperado", 0)),
        "monto_contado" => floatval(valor($cierre, "monto_contado", 0)),
        "diferencia" => floatval(valor($cierre, "diferencia", 0)),
        "multiusuario_ok" => $multiusuarioOk,
        "escaner_ui_ok" => $escanerOk,
        "productivo_sin_bloqueos" => $productivoSinBloqueos,
        "evidencias_caja_pendientes" => $evidenciasPendientes
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "autorizacion_siguiente" => valor($checks["cierre_preflight"], "autorizacion_sugerida", ""),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_cierra_turno" => true,
        "no_mueve_caja" => true,
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

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function texto($valor) {
    return is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : (string) $valor;
}

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
