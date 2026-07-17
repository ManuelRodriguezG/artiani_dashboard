<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: consolidar el cierre read-only de POS en el proyecto canonico panel_de_control.
 * Impacto: resume pase UAT multiusuario, readiness productivo y evidencia pre/post ciclo sin mover datos.
 * Contrato: read-only; no abre turno, no carga stock, no cobra, no cierra caja y no modifica inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
$cantidad = 1;

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    }
}

$checks = array();
$checks[] = ejecutar("pase_prueba_real", array(
    "uat_ventas_pos_pase_prueba_real_suite_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion
), false);

$checks[] = ejecutar("readiness_productivo", array(
    "uat_ventas_pos_productivo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--cantidad=" . $cantidad
), false);

$checks[] = ejecutar("evidencia_ciclo", array(
    "uat_ventas_pos_ciclo_evidencia_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--id_usuario=" . $idUsuario
), true);

$checks[] = ejecutar("recuperacion_ciclo", array(
    "uat_ventas_pos_ciclo_recuperacion_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion
), false);

$bloqueos = array();
$avisos = array();
foreach ($checks as $check) {
    $json = valor($check, "salida_json", array());
    if (empty($check["ok"]) && empty($check["permitir_falla"])) {
        $bloqueos[] = "Fallo check " . valor($check, "paso", "");
    }
    foreach (valor(valor($json, "resumen", array()), "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = valor($check, "paso", "") . ": " . $bloqueo;
    }
    foreach (valor(valor($json, "resumen", array()), "avisos", array()) as $aviso) {
        $avisos[] = valor($check, "paso", "") . ": " . $aviso;
    }
    foreach (valor($json, "avisos", array()) as $aviso) {
        $avisos[] = valor($check, "paso", "") . ": " . $aviso;
    }
}

$pase = buscarCheck($checks, "pase_prueba_real");
$productivo = buscarCheck($checks, "readiness_productivo");
$recuperacion = buscarCheck($checks, "recuperacion_ciclo");
$evidencia = buscarCheck($checks, "evidencia_ciclo");

$paseResumen = valor(valor($pase, "salida_json", array()), "resumen", array());
$productivoJson = valor($productivo, "salida_json", array());
$recuperacionEstado = valor(valor($recuperacion, "salida_json", array()), "estado", array());
$evidenciaResumen = valor(valor($evidencia, "salida_json", array()), "resumen", array());
$cicloRealCompleto = !empty($recuperacionEstado["venta_ligada"]) && empty($recuperacionEstado["turno_abierto"]) && trim((string) valor($evidenciaResumen, "folio_detectado", "")) !== "" && intval(valor($evidenciaResumen, "id_venta_detectado", 0)) > 0;
if ($cicloRealCompleto) {
    $avisos = array_values(array_filter($avisos, function ($aviso) {
        return strpos($aviso, "pase_prueba_real:") !== 0;
    }));
    $avisos[] = "Ciclo real multiusuario completado; avisos de prevalidacion previa omitidos del resumen ejecutivo.";
    $bloqueos = array_values(array_filter($bloqueos, function ($bloqueo) {
        return strpos($bloqueo, "Fallo check pase_prueba_real") !== 0
            && strpos($bloqueo, "pase_prueba_real: Fallo check prevalidacion_ciclo") !== 0
            && strpos($bloqueo, "pase_prueba_real: Prevalidacion ciclo: La referencia de stock ya fue usada") !== 0
            && strpos($bloqueo, "pase_prueba_real: Prevalidacion ciclo: La atencion no esta disponible para cobro") !== 0
            && strpos($bloqueo, "pase_prueba_real: Prevalidacion ciclo: La atencion ya tiene venta convertida") !== 0;
    }));
}

$listoUat = !empty($paseResumen["listo_para_autorizacion_agrupada"]) || $cicloRealCompleto;
$posBaseProductivo = empty(valor($productivoJson, "bloqueos", array()));
$atencionPendiente = empty(valor($recuperacionEstado, "venta_ligada", false));

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_cierre_final_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion,
        "cantidad" => $cantidad
    ),
    "resumen_ejecutivo" => array(
        "pase_uat_multiusuario_listo" => $listoUat,
        "pos_base_productivo_sin_bloqueos" => $posBaseProductivo,
        "atencion_sigue_pendiente_pre_ciclo" => $atencionPendiente,
        "ciclo_real_completo" => $cicloRealCompleto,
        "checks" => count($checks),
        "bloqueos" => array_values(array_unique($bloqueos)),
        "avisos" => array_values(array_unique($avisos))
    ),
    "checks" => compactarChecks($checks),
    "autorizacion_siguiente" => "AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS",
    "pendiente_productivo_no_bloqueante" => "Sembrar permiso fino ventas.pos.inventario_pendiente.autorizar para reemplazar token UAT en inventario pendiente productivo.",
    "contrato" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_cobra" => true
    )
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);

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
    $salida = array();
    foreach ($checks as $check) {
        $json = valor($check, "salida_json", array());
        $salida[] = array(
            "paso" => valor($check, "paso", ""),
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($json, "modo", null),
            "resumen" => valor($json, "resumen", null),
            "bloqueos" => valor($json, "bloqueos", null),
            "avisos" => valor($json, "avisos", null),
            "estado" => valor($json, "estado", null)
        );
    }
    return $salida;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

