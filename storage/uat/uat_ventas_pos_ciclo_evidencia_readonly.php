<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: recolectar evidencia read-only posterior al ciclo real de atencion POS multiusuario.
 * Impacto: permite validar folio, ticket, postventa y estado de turno/caja sin modificar BD.
 * Contrato: solo lectura; no cobra, no cierra turno, no ajusta inventario y no reimprime ticket.
 */

$idAtencion = 2;
$folio = "";
$idUsuario = 1;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

if ($idAtencion <= 0 && $folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_atencion=ID o --folio=POS-YYYYMMDD-######"
    ));
}

$checks = array();
$checks[] = ejecutar("post_conversion", array(
    "uat_ventas_pos_atencion_conversion_post_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--folio=" . $folio,
    "--compact=1"
), true);

$folioVenta = extraerFolio($checks, $folio);
$idVenta = extraerIdVenta($checks);
if ($folioVenta !== "") {
    $checks[] = ejecutar("ticket_formal", array(
        "uat_ventas_pos_ticket_formal_readonly.php",
        "--folio=" . $folioVenta,
        "--compact=1"
    ), false);
    $checks[] = ejecutar("post_venta", array(
        "uat_ventas_pos_post_venta_readonly.php",
        "--folio=" . $folioVenta
    ), false);
} else {
    $checks[] = array(
        "paso" => "ticket_formal",
        "ok" => true,
        "exit_code" => 0,
        "permitir_falla" => true,
        "salida_json" => array("modo" => "omitido", "mensaje" => "Sin folio de venta aun"),
        "salida_texto" => null
    );
    $checks[] = array(
        "paso" => "post_venta",
        "ok" => true,
        "exit_code" => 0,
        "permitir_falla" => true,
        "salida_json" => array("modo" => "omitido", "mensaje" => "Sin folio de venta aun"),
        "salida_texto" => null
    );
}

$checks[] = ejecutar("asignacion_turno", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_atencion=" . $idAtencion,
    "--compact=1"
), true);

$bloqueos = array();
$avisos = array();
foreach ($checks as $check) {
    if (empty($check["ok"]) && empty($check["permitir_falla"])) {
        $bloqueos[] = "Fallo evidencia " . $check["paso"];
    }
}
if ($folioVenta === "") {
    $avisos[] = "Sin folio de venta ligado; esperado antes de ejecutar ciclo real.";
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ciclo_evidencia_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "contexto" => array(
        "id_atencion" => $idAtencion,
        "folio" => $folioVenta,
        "id_venta" => $idVenta,
        "id_usuario" => $idUsuario
    ),
    "resumen" => array(
        "checks" => count($checks),
        "folio_detectado" => $folioVenta,
        "id_venta_detectado" => $idVenta,
        "bloqueos" => $bloqueos,
        "avisos" => $avisos
    ),
    "checks" => compactarChecks($checks),
    "contrato" => array(
        "read_only" => true,
        "no_cobra" => true,
        "no_cierra_turno" => true,
        "no_mueve_inventario" => true,
        "no_reimprime_ticket" => true
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

function extraerFolio($checks, $fallback = "") {
    foreach ($checks as $check) {
        $json = isset($check["salida_json"]) && is_array($check["salida_json"]) ? $check["salida_json"] : array();
        if (isset($json["folio_venta"]) && trim((string) $json["folio_venta"]) !== "") {
            return trim((string) $json["folio_venta"]);
        }
    }
    return trim((string) $fallback);
}

function extraerIdVenta($checks) {
    foreach ($checks as $check) {
        $json = isset($check["salida_json"]) && is_array($check["salida_json"]) ? $check["salida_json"] : array();
        $resumen = isset($json["resumen"]) && is_array($json["resumen"]) ? $json["resumen"] : array();
        if (isset($resumen["id_venta_convertida"]) && intval($resumen["id_venta_convertida"]) > 0) {
            return intval($resumen["id_venta_convertida"]);
        }
    }
    return 0;
}

function compactarChecks($checks) {
    $salida = array();
    foreach ($checks as $check) {
        $json = isset($check["salida_json"]) && is_array($check["salida_json"]) ? $check["salida_json"] : array();
        $salida[] = array(
            "paso" => $check["paso"],
            "ok" => !empty($check["ok"]),
            "exit_code" => intval($check["exit_code"]),
            "modo" => isset($json["modo"]) ? $json["modo"] : null,
            "mensaje" => isset($json["mensaje"]) ? $json["mensaje"] : null,
            "resumen" => isset($json["resumen"]) ? $json["resumen"] : null,
            "hallazgos" => isset($json["hallazgos"]) ? $json["hallazgos"] : null
        );
    }
    return $salida;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
