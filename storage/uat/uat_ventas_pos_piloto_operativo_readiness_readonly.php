<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: decidir en modo solo lectura si POS esta listo para piloto operativo controlado.
 * Impacto: consolida cierre final, evidencia de ciclo y criterios de piloto sin mover caja, inventario ni ventas.
 * Contrato: read-only; no abre turno, no cobra, no carga stock, no cierra caja y no modifica inventario.
 */

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$idAtencion = 2;
$cantidad = 1;
$folio = "";

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
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    }
}

$checks = array();
$checks[] = ejecutar("cierre_final", array(
    "uat_ventas_pos_cierre_final_readonly.php",
    "--id_usuario=" . $idUsuario,
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--id_atencion=" . $idAtencion,
    "--cantidad=" . $cantidad
), false);

$checks[] = ejecutar("evidencia_ciclo", array(
    "uat_ventas_pos_ciclo_evidencia_readonly.php",
    "--id_atencion=" . $idAtencion,
    "--id_usuario=" . $idUsuario,
    "--folio=" . $folio
), true);
$checks[] = ejecutar("inventario_sku", array(
    "uat_ventas_pos_inventario_sku_readonly.php",
    "--id_almacen=" . $idAlmacen,
    "--id_sku=" . $idSku,
    "--cantidad=" . $cantidad
), true);

$checks[] = ejecutar("evidencias_caja", array(
    "uat_ventas_pos_caja_evidencias_readonly.php"
), true);

$bloqueos = array();
$avisos = array();
foreach ($checks as $check) {
    if (empty($check["ok"]) && empty($check["permitir_falla"])) {
        $bloqueos[] = "Fallo check " . valor($check, "paso", "");
    }
}

$cierre = buscarCheck($checks, "cierre_final");
$evidencia = buscarCheck($checks, "evidencia_ciclo");
$cierreJson = valor($cierre, "salida_json", array());
$evidenciaJson = valor($evidencia, "salida_json", array());
$inventarioCheck = buscarCheck($checks, "inventario_sku");
$evidenciasCajaCheck = buscarCheck($checks, "evidencias_caja");
$inventarioJson = valor($inventarioCheck, "salida_json", array());
$evidenciasCajaJson = valor($evidenciasCajaCheck, "salida_json", array());
$cierreResumen = valor($cierreJson, "resumen_ejecutivo", array());
$evidenciaResumen = valor($evidenciaJson, "resumen", array());
$folioDetectado = trim((string) valor($evidenciaResumen, "folio_detectado", ""));
$idVentaDetectado = intval(valor($evidenciaResumen, "id_venta_detectado", 0));

foreach (valor($cierreResumen, "bloqueos", array()) as $bloqueo) {
    $bloqueos[] = "cierre_final: " . $bloqueo;
}
foreach (valor($evidenciaResumen, "bloqueos", array()) as $bloqueo) {
    $bloqueos[] = "evidencia_ciclo: " . $bloqueo;
}
foreach (valor($cierreResumen, "avisos", array()) as $aviso) {
    $avisos[] = "cierre_final: " . $aviso;
}
foreach (valor($evidenciaResumen, "avisos", array()) as $aviso) {
    $avisos[] = "evidencia_ciclo: " . $aviso;
}
if (!empty($inventarioJson) && empty(valor(valor($inventarioJson, "resumen", array()), "cubre_cantidad_requerida", false))) {
    $avisos[] = "inventario_sku: Stock piloto insuficiente para SKU " . $idSku . " en almacen " . $idAlmacen . "; cargar stock/recepcion/ajuste autorizado antes de venta real.";
}
$totalEvidenciasPendientes = intval(valor(valor($evidenciasCajaJson, "resumen", array()), "total_registros", 0));
if ($totalEvidenciasPendientes > 0) {
    $avisos[] = "evidencias_caja: Hay " . $totalEvidenciasPendientes . " evidencia(s) de caja pendiente(s); no bloquea piloto normal, pero debe cerrarse por control administrativo.";
}

$cicloRealCompleto = $folioDetectado !== "" && $idVentaDetectado > 0;
if ($cicloRealCompleto) {
    $avisos = array_values(array_filter($avisos, function ($aviso) {
        return strpos($aviso, "cierre_final: pase_prueba_real:") !== 0
            && strpos($aviso, "evidencia_ciclo:") !== 0
            && strpos($aviso, "pase_prueba_real:") !== 0;
    }));
}
$baseProductiva = !empty($cierreResumen["pos_base_productivo_sin_bloqueos"]);
$uatLista = !empty($cierreResumen["pase_uat_multiusuario_listo"]) || $cicloRealCompleto;

if (!$uatLista) {
    $bloqueos[] = "La suite UAT multiusuario no esta lista.";
}
if (!$baseProductiva) {
    $bloqueos[] = "La base productiva POS tiene bloqueos.";
}
if (!$cicloRealCompleto) {
    $bloqueos[] = "Falta ejecutar o evidenciar el ciclo real multiusuario antes del piloto.";
}

$decision = empty($bloqueos) ? "apto_para_piloto_controlado" : "no_apto_aun";
$siguientePaso = empty($bloqueos)
    ? "Ejecutar piloto corto: 1 sucursal, 1 caja, 1 turno y 1 a 2 usuarios."
    : "Completar ciclo real autorizado y repetir este semaforo read-only.";

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_operativo_readiness_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "id_atencion" => $idAtencion,
        "cantidad" => $cantidad,
        "folio" => $folioDetectado
    ),
    "resumen" => array(
        "decision" => $decision,
        "pase_uat_multiusuario_listo" => $uatLista,
        "pos_base_productivo_sin_bloqueos" => $baseProductiva,
        "ciclo_real_completo" => $cicloRealCompleto,
        "folio_detectado" => $folioDetectado,
        "id_venta_detectado" => $idVentaDetectado,
        "bloqueos" => array_values(array_unique($bloqueos)),
        "avisos" => array_values(array_unique($avisos)),
        "siguiente_paso" => $siguientePaso
    ),
    "checks" => compactarChecks($checks),
    "contrato" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_cobra" => true
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
    $salida = array();
    foreach ($checks as $check) {
        $json = valor($check, "salida_json", array());
        $salida[] = array(
            "paso" => valor($check, "paso", ""),
            "ok" => !empty($check["ok"]),
            "exit_code" => intval(valor($check, "exit_code", 0)),
            "modo" => valor($json, "modo", null),
            "resumen" => valor($json, "resumen", null),
            "resumen_ejecutivo" => valor($json, "resumen_ejecutivo", null)
        );
    }
    return $salida;
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}

