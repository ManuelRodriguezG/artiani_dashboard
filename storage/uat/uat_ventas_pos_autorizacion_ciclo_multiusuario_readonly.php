<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: generar paquete de autorizacion para ciclo real POS multiusuario desde panel_de_control.
 * Impacto: deja claro alcance, guardrails, comandos y postchecks antes de ejecutar escrituras reales.
 * Contrato: read-only; no abre turno, no carga stock, no cobra, no cierra caja y no modifica inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
$cantidadStock = 1;
$pago = 295;
$montoInicial = 500;
$montoContado = 795;
$respaldo = "UAT_POS_VIGENTE";

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--cantidad_stock=") === 0) {
        $cantidadStock = floatval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$prechecks = array();
$prechecks[] = ejecutar("cierre_final", array(
    "uat_ventas_pos_cierre_final_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion,
    "--cantidad=" . $cantidadStock
), false);
$prechecks[] = ejecutar("guardrail_sin_token", array(
    "uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion,
    "--cantidad_stock=" . $cantidadStock,
    "--pago=" . $pago,
    "--monto_inicial=" . $montoInicial,
    "--monto_contado=" . $montoContado
), true);

$bloqueos = array();
foreach ($prechecks as $check) {
    $json = valor($check, "salida_json", array());
    if (valor($check, "paso", "") === "guardrail_sin_token") {
        if (empty($json["bloqueado"])) {
            $bloqueos[] = "Guardrail sin token no bloqueo el ciclo real.";
        }
        continue;
    }
    if (empty($check["ok"])) {
        $bloqueos[] = "Fallo precheck " . valor($check, "paso", "");
    }
    foreach (valor(valor($json, "resumen_ejecutivo", array()), "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = valor($check, "paso", "") . ": " . $bloqueo;
    }
}

$autorizacionHumana = "AUTORIZO EJECUTAR CICLO REAL ATENCION POS MULTIUSUARIO usando respaldo UAT POS vigente con token VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} id_atencion={$idAtencion} cantidad_stock={$cantidadStock} pago={$pago} monto_inicial={$montoInicial} monto_contado={$montoContado} para UAT POS";

$comandoTecnico = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_atencion_multiusuario_ciclo_apply_authorized.php"
    . " --autorizar=VENTAS_POS_ATENCION_MULTIUSUARIO_CICLO_REAL"
    . " --respaldo=" . $respaldo
    . " --id_usuario=" . $idUsuario
    . " --id_almacen=" . $idAlmacen
    . " --id_sku=" . $idSku
    . " --id_atencion=" . $idAtencion
    . " --cantidad_stock=" . $cantidadStock
    . " --pago=" . $pago
    . " --monto_inicial=" . $montoInicial
    . " --monto_contado=" . $montoContado;

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_autorizacion_ciclo_multiusuario_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion,
        "cantidad_stock" => $cantidadStock,
        "pago" => $pago,
        "monto_inicial" => $montoInicial,
        "monto_contado" => $montoContado,
        "respaldo" => $respaldo
    ),
    "prechecks" => compactar($prechecks),
    "bloqueos" => $bloqueos,
    "alcance_si_autorizado" => array(
        "abre_turno" => true,
        "carga_stock_uat" => true,
        "cobra_atencion" => true,
        "crea_venta" => true,
        "mueve_caja" => true,
        "mueve_inventario_kardex" => true,
        "convierte_atencion" => true,
        "valida_ticket" => true,
        "valida_postventa" => true,
        "cierra_turno" => true
    ),
    "autorizacion_humana" => $autorizacionHumana,
    "comando_tecnico" => $comandoTecnico,
    "postchecks" => array(
        "evidencia_ciclo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_ciclo_evidencia_readonly.php --id_atencion={$idAtencion} --id_usuario={$idUsuario}",
        "cierre_final" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cierre_final_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku} --id_atencion={$idAtencion} --cantidad={$cantidadStock}"
    ),
    "contrato" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_cobra" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

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
        "salida_json" => is_array($json) ? $json : null
    );
}

function compactar($checks) {
    $salida = array();
    foreach ($checks as $check) {
        $json = valor($check, "salida_json", array());
        $salida[] = array(
            "paso" => valor($check, "paso", ""),
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($json, "modo", null),
            "bloqueado" => valor($json, "bloqueado", null),
            "resumen_ejecutivo" => valor($json, "resumen_ejecutivo", null)
        );
    }
    return $salida;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
