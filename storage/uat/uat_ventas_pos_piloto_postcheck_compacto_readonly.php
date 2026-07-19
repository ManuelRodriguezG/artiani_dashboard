<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: revisar despues de un piloto POS que caja, reportes, ticket y trazabilidad queden visibles.
 * Impacto: da una salida compacta para decidir si el turno piloto se acepta, se observa o requiere investigacion.
 * Contrato: read-only; no cierra turnos, no resuelve diferencias, no adjunta evidencias, no cobra y no mueve inventario.
 */

date_default_timezone_set("America/Mexico_City");

$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$folioVenta = "";

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folioVenta = trim(substr($arg, 8), "\"' ");
    }
}

$checks = array(
    "entorno_canonico" => ejecutar("uat_ventas_pos_entorno_canonico_readiness_readonly.php", array()),
    "reportes_piloto" => ejecutar("uat_ventas_pos_reportes_piloto_readiness_readonly.php", array(
        "--id_usuario=" . $idUsuario,
        "--id_almacen=" . $idAlmacen,
        "--id_sku=" . $idSku,
    )),
    "ticket_trazabilidad" => ejecutar("uat_ventas_pos_ticket_trazabilidad_readiness_readonly.php", array()),
    "docs_estado" => ejecutar("uat_ventas_pos_docs_estado_vigente_readonly.php", array()),
);

$bloqueos = array();
$avisos = array();
foreach ($checks as $nombre => $check) {
    if (empty(valor($check, "ok", false))) {
        $bloqueos[] = "Check " . $nombre . " no esta en ok";
    }
    foreach (valor($check, "bloqueos", array()) as $bloqueo) {
        $bloqueos[] = $nombre . ": " . texto($bloqueo);
    }
    foreach (valor($check, "avisos", array()) as $aviso) {
        $avisos[] = $nombre . ": " . texto($aviso);
    }
}

$resumenReportes = valor(valor($checks["reportes_piloto"], "resumen", array()), "resumen", array());
if (empty($resumenReportes)) {
    $resumenReportes = valor($checks["reportes_piloto"], "resumen", array());
}

$ventasTotal = floatval(valor($resumenReportes, "ventas_total_reportado", 0));
$turnosReportados = intval(valor($resumenReportes, "turnos_reportados", 0));
$diferenciasPendientes = intval(valor($resumenReportes, "diferencias_pendientes", 0));
$evidenciasPendientes = intval(valor($resumenReportes, "evidencias_pendientes", 0));
$pendientesInventario = intval(valor($resumenReportes, "pendientes_inventario_abiertos", 0));

if ($turnosReportados <= 0) {
    $avisos[] = "No hay turnos visibles en reportes para el almacen auditado.";
}
if ($ventasTotal <= 0) {
    $avisos[] = "No hay ventas visibles en reportes para el almacen auditado.";
}
if ($diferenciasPendientes > 0) {
    $avisos[] = "Hay diferencias de caja pendientes; revisar antes de ampliar piloto.";
}
if ($evidenciasPendientes > 0) {
    $avisos[] = "Hay evidencias de caja pendientes; cerrar administrativamente.";
}
if ($pendientesInventario > 0) {
    $avisos[] = "Hay pendientes de inventario POS abiertos; mantenerlos identificados o resolverlos.";
}

$criteriosAceptar = array(
    "Ticket formal y trazabilidad visibles.",
    "Reportes POS muestran ventas y turnos.",
    "Diferencias de caja, evidencias y pendientes quedan visibles para administracion.",
    "Si hubo diferencia, no se borra ni se corrige manualmente: se revisa por flujo administrativo.",
);

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_postcheck_compacto_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "folio_venta_referencia" => $folioVenta !== "" ? $folioVenta : null,
    "decision" => empty($bloqueos) ? "postcheck_apto_con_observaciones" : "postcheck_no_apto",
    "resumen" => array(
        "turnos_reportados" => $turnosReportados,
        "ventas_total_reportado" => $ventasTotal,
        "diferencias_pendientes" => $diferenciasPendientes,
        "evidencias_pendientes" => $evidenciasPendientes,
        "pendientes_inventario_abiertos" => $pendientesInventario,
        "bloqueos_total" => count(array_unique($bloqueos)),
        "avisos_total" => count(array_unique($avisos)),
    ),
    "criterios_para_aceptar_piloto" => $criteriosAceptar,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turnos" => true,
        "no_resuelve_diferencias" => true,
        "no_adjunta_evidencias" => true,
        "no_cobra" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function ejecutar($script, $args)
{
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
    return $json;
}

function valor($datos, $campo, $default = null)
{
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function texto($valor)
{
    return is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : strval($valor);
}

