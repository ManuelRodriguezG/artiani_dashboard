<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: concentrar semaforos read-only para decidir si POS puede pasar a piloto controlado.
 * Impacto: evita revisar scripts sueltos y muestra condiciones previas sin escribir BD.
 * Contrato: solo ejecuta checks read-only; no abre turno, no cobra, no reserva, no mueve caja ni inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idCaja = 2;
$idTerminal = 2;
$idSku = 1760;
$idAtencion = 2;
$cantidad = 1;
$usuarios = "1,2,3";

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(str_replace(",", ".", trim(substr($arg, 11), "\"' ")));
    } elseif (strpos($arg, "--usuarios=") === 0) {
        $usuarios = trim(substr($arg, 11), "\"' ");
    }
}

$checks = array(
    "piloto_operativo" => ejecutar("uat_ventas_pos_piloto_operativo_readiness_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
        "--id_atencion=" . $idAtencion,
        "--cantidad=" . $cantidad
    )),
    "escaner_ui" => ejecutar("uat_ventas_pos_escaner_ui_readiness_readonly.php", array()),
    "multiusuario" => ejecutar("uat_ventas_pos_multiusuario_preflight_readonly.php", array(
        "--usuarios=" . $usuarios,
        "--id_almacen=" . $idAlmacen,
        "--id_caja=" . $idCaja,
        "--id_terminal=" . $idTerminal
    ))
);

$bloqueos = array();
$avisos = array();
$recomendaciones = array();

foreach ($checks as $nombre => $check) {
    if (empty(valor($check, "ok", false))) {
        $bloqueos[] = "Check " . $nombre . " no esta en ok";
    }
    foreach (valor($check, "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $nombre . ": " . $bloqueo;
    }
    foreach (valor($check, "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . (is_array($aviso) ? json_encode($aviso, JSON_UNESCAPED_UNICODE) : $aviso);
    }
    foreach (valor(valor($check, "resumen", array()), "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . (is_array($aviso) ? json_encode($aviso, JSON_UNESCAPED_UNICODE) : $aviso);
    }
}

$multiBloqueos = valor($checks["multiusuario"], "bloqueos_para_piloto_multiusuario", array());
foreach ($multiBloqueos as $bloqueo) {
    $avisos[] = "multiusuario: " . $bloqueo;
}

$pilotoAvisos = array_merge(
    valor($checks["piloto_operativo"], "avisos", array()),
    valor(valor($checks["piloto_operativo"], "resumen", array()), "avisos", array())
);
$stockAviso = false;
$evidenciaAviso = false;
foreach ($pilotoAvisos as $aviso) {
    if (is_string($aviso) && stripos($aviso, "stock") !== false) {
        $stockAviso = true;
    }
    if (is_string($aviso) && stripos($aviso, "evidencia") !== false) {
        $evidenciaAviso = true;
    }
}

if ($stockAviso) {
    $recomendaciones[] = "Antes de venta real, cargar/recibir/ajustar stock del SKU piloto o elegir un SKU con disponible.";
}
if ($evidenciaAviso) {
    $recomendaciones[] = "Cerrar o documentar evidencias historicas de caja pendientes por control administrativo.";
}
if (!empty($multiBloqueos)) {
    $recomendaciones[] = "Para piloto multiusuario, habilitar rol/asignacion POS de usuarios faltantes con autorizacion fuerte.";
}
$recomendaciones[] = "Ejecutar primer piloto con venta normal; dejar fuera devoluciones, apartados nuevos, descuentos libres e inventario pendiente productivo.";

$decision = empty($bloqueos) ? "apto_con_condiciones" : "no_apto";
responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_go_nogo_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => $decision,
    "objetivo" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_terminal_pos" => $idTerminal,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion,
        "cantidad" => $cantidad,
        "usuarios_multiusuario" => $usuarios
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "recomendaciones" => $recomendaciones,
    "checks" => $checks,
    "autorizacion_sugerida_multiusuario" => valor($checks["multiusuario"], "autorizacion_sugerida_si_se_quiere_habilitar", ""),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_reserva" => true,
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

function responder($payload, $exit = 0) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($exit);
}
