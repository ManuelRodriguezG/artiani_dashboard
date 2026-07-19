<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15 / 2026-07-19.
 * Proposito: ejecutar una suite compacta read-only para saber si POS esta listo para la siguiente UAT real o si el ciclo ya quedo completado.
 * Impacto: agrupa semaforo, prevalidacion de ciclo, bandeja, detalle y post-check esperado sin escribir BD.
 * Contrato: solo lectura; no abre turno, no carga stock, no cobra, no cierra caja y no mueve inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
$pago = 295;
$montoInicial = 500;
$montoContado = 795;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    }
}

$checks = array();
$checks[] = ejecutar("dependencias_ciclo", array(
    "uat_ventas_pos_ciclo_dependencias_readonly.php"
), false);

$checks[] = ejecutar("ui_sintaxis", array(
    "uat_ventas_pos_ui_sintaxis_readonly.php"
), false);

$checks[] = ejecutar("rutas_surface", array(
    "uat_ventas_pos_rutas_surface_readonly.php"
), false);

$checks[] = ejecutar("encoding_bom", array(
    "uat_ventas_pos_encoding_bom_readonly.php"
), false);

$checks[] = ejecutar("permisos_usuario", array(
    "uat_ventas_pos_permisos_usuario_readonly.php",
    "--id_usuario=" . $idUsuario
), false);

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
), false);

$checks[] = ejecutar("semaforo_cierre", array(
    "uat_ventas_pos_cierre_modulo_readiness_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_atencion=" . $idAtencion,
    "--id_sku=" . $idSku,
    "--compact=1"
), true);

$checks[] = ejecutar("prevalidacion_ciclo", array(
    "uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php",
    "--prevalidar=1",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion,
    "--cantidad_stock=1",
    "--pago=" . $pago,
    "--monto_inicial=" . $montoInicial,
    "--monto_contado=" . $montoContado
), false);

$checks[] = ejecutar("bandeja_atenciones", array(
    "uat_ventas_pos_atenciones_bandeja_readonly.php",
    "--id_almacen=" . $idAlmacen
), false);

$checks[] = ejecutar("detalle_atencion", array(
    "uat_ventas_pos_atencion_detalle_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--id_almacen=" . $idAlmacen
), false);

$checks[] = ejecutar("post_conversion_esperado_pendiente", array(
    "uat_ventas_pos_atencion_conversion_post_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--compact=1"
), true);

$semaforo = buscarCheck($checks, "semaforo_cierre");
$prevalidacion = buscarCheck($checks, "prevalidacion_ciclo");
$postConversion = buscarCheck($checks, "post_conversion_esperado_pendiente");
$inventarioSku = buscarCheck($checks, "inventario_sku");
$prevalidacionJson = valor($prevalidacion, "salida_json", array());
$postConversionJson = valor($postConversion, "salida_json", array());
$atencionYaConvertida = atencionYaConvertida($prevalidacionJson);
$postConversionOk = postConversionOk($postConversionJson);

$bloqueos = array();
$avisos = array();
foreach ($checks as $check) {
    if (empty($check["ok"]) && empty($check["permitir_falla"])) {
        if (valor($check, "paso", "") === "prevalidacion_ciclo" && $atencionYaConvertida && $postConversionOk) {
            $avisos[] = "Prevalidacion omitida: la atencion ya esta convertida y el post-check confirma venta/pago/kardex.";
        } else {
            $bloqueos[] = "Fallo check " . $check["paso"];
        }
    }
}

foreach (valor(valor($semaforo, "salida_json", array()), "bloqueos_para_cobro_atencion", array()) as $bloqueo) {
    if (in_array($bloqueo, array(
        "No hay turno abierto para usuario/caja",
        "Selecciona turno abierto de caja",
        "Existencia insuficiente",
        "La politica POS autoriza inventario pendiente, pero este cobro debe pasar por el flujo real de inventario pendiente con alerta y trazabilidad"
    ), true)) {
        $avisos[] = "Bloqueo operativo esperado antes de UAT real: " . $bloqueo;
    } else {
        $bloqueos[] = "Bloqueo no esperado en semaforo: " . $bloqueo;
    }
}
foreach (valor(valor($prevalidacionJson, "prevalidacion", array()), "bloqueos", array()) as $bloqueo) {
    if ($atencionYaConvertida && $postConversionOk && bloqueoPrevalidacionPorAtencionConvertida($bloqueo)) {
        $avisos[] = "Prevalidacion ciclo omitida por atencion ya convertida: " . $bloqueo;
    } else {
        $bloqueos[] = "Prevalidacion ciclo: " . $bloqueo;
    }
}
$resumenInventario = valor(valor($inventarioSku, "salida_json", array()), "resumen", array());
if (!empty($resumenInventario) && empty($resumenInventario["cubre_cantidad_requerida"])) {
    $avisos[] = "Inventario SKU no cubre la cantidad requerida; es esperado antes de cargar stock UAT.";
}
if (!empty(valor($postConversion, "salida_json", array())) && !empty(valor(valor($postConversion, "salida_json", array()), "hallazgos", array()))) {
    $avisos[] = "Post-conversion todavia falla porque la atencion sigue pendiente; es esperado antes del cobro real.";
}

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_pase_prueba_real_suite_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion
    ),
    "resumen" => array(
        "checks" => count($checks),
        "bloqueos" => $bloqueos,
        "avisos" => array_values(array_unique($avisos)),
        "listo_para_autorizacion_agrupada" => empty($bloqueos) && !$atencionYaConvertida,
        "ciclo_real_ya_completado" => $atencionYaConvertida && $postConversionOk
    ),
    "checks" => compactarChecks($checks),
    "autorizacion_siguiente" => $atencionYaConvertida && $postConversionOk
        ? null
        : "AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario=1 id_almacen=5 id_sku=1760 id_atencion=2 cantidad_stock=1 pago=295 monto_inicial=500 monto_contado=795 para UAT POS",
    "siguiente_paso" => $atencionYaConvertida && $postConversionOk
        ? "No repetir esta atencion: ya esta convertida. Continuar con piloto controlado, stock disponible y cierre de pendientes administrativos."
        : "Preparar autorizacion agrupada solo si la atencion sigue disponible para cobro.",
    "contrato" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
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
    $compactos = array();
    foreach ($checks as $check) {
        $json = valor($check, "salida_json", array());
        $compactos[] = array(
            "paso" => valor($check, "paso", ""),
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($json, "modo", null),
            "mensaje" => valor($json, "mensaje", null),
            "resumen" => valor($json, "resumen", null),
            "prevalidacion" => valor($json, "prevalidacion", null),
            "bloqueos_para_cobro_atencion" => valor($json, "bloqueos_para_cobro_atencion", null)
        );
    }
    return $compactos;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function atencionYaConvertida($prevalidacionJson) {
    $prevalidacion = valor($prevalidacionJson, "prevalidacion", array());
    $atencion = valor($prevalidacion, "atencion", array());
    if (valor($atencion, "estatus", "") === "convertida" && intval(valor($atencion, "id_venta_convertida", 0)) > 0) {
        return true;
    }
    foreach (valor($prevalidacion, "bloqueos", array()) as $bloqueo) {
        if (stripos($bloqueo, "atencion ya tiene venta convertida") !== false) {
            return true;
        }
    }
    return false;
}

function postConversionOk($postConversionJson) {
    $resumen = valor($postConversionJson, "resumen", array());
    if (empty($resumen)) {
        return false;
    }
    return valor($resumen, "estatus_atencion", "") === "convertida"
        && intval(valor($resumen, "id_venta_convertida", 0)) > 0
        && valor($resumen, "estatus_venta", "") === "pagada"
        && floatval(valor($resumen, "total_venta", 0)) > 0
        && floatval(valor($resumen, "pagado_total", 0)) >= floatval(valor($resumen, "total_venta", 0))
        && intval(valor($resumen, "detalles_venta", 0)) > 0
        && intval(valor($resumen, "pagos", 0)) > 0
        && intval(valor($resumen, "movimientos_caja", 0)) > 0
        && intval(valor($resumen, "trazabilidad_inventario", 0)) > 0;
}

function bloqueoPrevalidacionPorAtencionConvertida($bloqueo) {
    return stripos($bloqueo, "referencia de stock ya fue usada") !== false
        || stripos($bloqueo, "atencion no esta disponible para cobro: convertida") !== false
        || stripos($bloqueo, "atencion ya tiene venta convertida") !== false;
}
