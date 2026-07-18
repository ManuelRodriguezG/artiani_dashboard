<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: prevalidar el piloto de venta POS multiusuario sin escribir BD.
 * Impacto: concentra usuarios, turno, stock, venta, escaner y pendientes administrativos antes de solicitar autorizaciones reales.
 * Contrato: read-only; no abre turno, no carga stock, no crea atencion, no cobra, no reserva, no mueve caja ni inventario.
 */

$idUsuarioSupervisor = 1;
$idUsuarioCobra = 2;
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$pago = 295;
$montoInicial = 500;
$usuarios = "1,2,3";
$cliente = "Cliente UAT POS multiusuario";
$compact = false;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario_supervisor=") === 0) {
        $idUsuarioSupervisor = intval(trim(substr($arg, 24), "\"' "));
    } elseif (strpos($arg, "--id_usuario_cobra=") === 0) {
        $idUsuarioCobra = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(str_replace(",", ".", trim(substr($arg, 9), "\"' ")));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(str_replace(",", ".", trim(substr($arg, 7), "\"' ")));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(str_replace(",", ".", trim(substr($arg, 16), "\"' ")));
    } elseif (strpos($arg, "--usuarios=") === 0) {
        $usuarios = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--compact=") === 0) {
        $compact = intval(trim(substr($arg, 10), "\"' ")) === 1;
    }
}

$checks = array(
    "go_nogo" => ejecutar("uat_ventas_pos_piloto_go_nogo_readonly.php", array(
        "--id_usuario=" . $idUsuarioSupervisor,
        "--id_almacen=" . $idAlmacen,
        "--id_caja=" . $idCaja,
        "--id_terminal=" . $idTerminal,
        "--id_sku=" . $idSku,
        "--cantidad=" . $cantidad,
        "--usuarios=" . $usuarios
    )),
    "turno_apertura_supervisor" => ejecutar("uat_ventas_pos_turno_preflight_readonly.php", array(
        "--id_usuario=" . $idUsuarioSupervisor,
        "--monto_inicial=" . numero($montoInicial),
        "--respaldo=UAT_POS_VIGENTE",
        "--observaciones=Piloto POS multiusuario"
    )),
    "inventario_sku" => ejecutar("uat_ventas_pos_inventario_sku_readonly.php", array(
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
        "--cantidad_requerida=" . numero($cantidad)
    )),
    "venta_usuario_cobra" => ejecutar("uat_ventas_pos_venta_preflight_readonly.php", array(
        "--id_usuario=" . $idUsuarioCobra,
        "--id_sku=" . $idSku,
        "--cantidad=" . numero($cantidad),
        "--precio=" . numero($precio),
        "--pago=" . numero($pago),
        "--respaldo=UAT_POS_VIGENTE",
        "--cliente=" . $cliente
    )),
    "escaner_ui" => ejecutar("uat_ventas_pos_escaner_ui_readiness_readonly.php", array()),
    "evidencias_caja" => ejecutar("uat_ventas_pos_caja_evidencias_readonly.php", array())
);

$bloqueos = array();
$avisos = array();
$autorizaciones = array();

foreach ($checks as $nombre => $check) {
    if (empty(valor($check, "ok", false))) {
        $avisos[] = "Check " . $nombre . " no esta en ok; revisar detalle.";
    }
    foreach (valor($check, "bloqueos", array()) as $bloqueo) {
        $avisos[] = $nombre . ": " . $bloqueo;
    }
}

$inventarioResumen = valor($checks["inventario_sku"], "resumen", array());
$cubreStock = !empty(valor($inventarioResumen, "cubre_cantidad_requerida", false));
if (!$cubreStock) {
    $bloqueos[] = "Stock insuficiente para SKU " . $idSku . " en almacen " . $idAlmacen . ".";
    $autorizaciones[] = "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario=" . $idUsuarioSupervisor
        . " id_almacen=" . $idAlmacen
        . " id_sku=" . $idSku
        . " cantidad=" . numero($cantidad)
        . " referencia=INV-INICIAL-POS-UAT-MULTIUSUARIO-01";
}

$turnoPuedeAbrir = !empty(valor($checks["turno_apertura_supervisor"], "puede_abrir_turno", false));
$turnoAbiertoActual = valor($checks["turno_apertura_supervisor"], "turno_abierto_actual", null);
if ($turnoPuedeAbrir) {
    $autorizaciones[] = normalizarRespaldoHumano(valor($checks["turno_apertura_supervisor"], "autorizacion_sugerida", ""));
} elseif (is_array($turnoAbiertoActual) && !empty($turnoAbiertoActual)) {
    $avisos[] = "Ya existe turno abierto; usar ese turno para el piloto o cerrarlo antes de abrir otro.";
} else {
    $bloqueos[] = "No se puede abrir turno con el supervisor indicado; revisar asignacion/caja.";
}

$ventaPuede = !empty(valor($checks["venta_usuario_cobra"], "puede_vender_real", false));
if (!$ventaPuede) {
    $avisos[] = "La venta del usuario cobra aun no esta lista; normalmente se resolvera despues de abrir turno y tener stock.";
} else {
    $autorizaciones[] = valor($checks["venta_usuario_cobra"], "autorizacion_sugerida", "");
}

$evidenciasResumen = valor($checks["evidencias_caja"], "resumen", array());
if (intval(valor($evidenciasResumen, "total_registros", 0)) > 0) {
    $avisos[] = "Hay evidencias historicas de caja pendientes; no bloquean venta piloto normal, pero deben cerrarse por control administrativo.";
}

$decision = empty($bloqueos) && $ventaPuede ? "listo_para_venta_real" : "requiere_autorizaciones_previas";
$payload = array(
    "ok" => true,
    "modo" => "ventas_pos_piloto_multiusuario_venta_readiness_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => $decision,
    "objetivo" => array(
        "id_usuario_supervisor" => $idUsuarioSupervisor,
        "id_usuario_cobra" => $idUsuarioCobra,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_terminal_pos" => $idTerminal,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio" => $precio,
        "pago" => $pago,
        "usuarios" => $usuarios
    ),
    "bloqueos_para_venta_real" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique(array_filter($avisos))),
    "autorizaciones_sugeridas" => array_values(array_unique(array_filter($autorizaciones))),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_crea_venta" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
);
if (!$compact) {
    $payload["checks"] = $checks;
}
responder($payload);

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

function numero($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
}

function normalizarRespaldoHumano($texto) {
    return str_replace("UAT_POS_VIGENTE", "UAT POS vigente", (string) $texto);
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}
