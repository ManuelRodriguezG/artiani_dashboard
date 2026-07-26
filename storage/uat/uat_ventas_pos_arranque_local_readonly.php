<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: entregar un semaforo operativo simple para iniciar POS en local.
 * Impacto: consolida condiciones de turno, operador, ticket, UX/manual, stock y SKU recomendado sin acciones reales.
 * Contrato: read-only; no abre turno, no cobra, no crea pedidos, no resuelve pendientes y no mueve caja/inventario.
 */

date_default_timezone_set("America/Mexico_City");

$root = dirname(__DIR__, 2);
$php = "C:\\xampp\\php\\php.exe";
$args = parseArgs($argv);

$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSku = entero($args, "id_sku", 1760);
$cantidad = decimal($args, "cantidad", 1);
$montoInicial = decimal($args, "monto_inicial", 500);
$usuarios = isset($args["usuarios"]) ? trim((string) $args["usuarios"]) : "1,2,3";
$timeoutScript = isset($args["timeout_script"]) ? max(2, (int) $args["timeout_script"]) : 10;

$checks = array(
    "operacion_basica" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_operacion_basica_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku} --cantidad={$cantidad} --usuarios={$usuarios} --compact=1", $timeoutScript),
    "siguiente_piloto" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_siguiente_piloto_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku} --cantidad={$cantidad} --monto_inicial={$montoInicial} --usuarios={$usuarios}", $timeoutScript),
    "paquete_recomendado" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_piloto_paquete_recomendado_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSku} --cantidad={$cantidad} --monto_inicial={$montoInicial} --usuarios={$usuarios}", $timeoutScript),
    "ux_operativa" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_ux_operativa_readiness_readonly.php", $timeoutScript),
    "ticket" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_ticket_formal_readonly.php --folio=POS-20260717-000001", $timeoutScript),
    "docs" => ejecutar($php, $root, "storage\\uat\\uat_ventas_pos_docs_estado_vigente_readonly.php", $timeoutScript),
);

$bloqueos = array();
$avisos = array();
foreach ($checks as $clave => $check) {
    if (empty($check["ok"])) {
        $bloqueos[] = $clave . ": " . $check["mensaje"];
    }
    foreach ($check["avisos"] as $aviso) {
        $avisos[] = $clave . ": " . $aviso;
    }
}

$operacion = valor($checks["operacion_basica"]["json"], "resumen_operativo", array());
$siguiente = $checks["siguiente_piloto"]["json"];
$paquete = $checks["paquete_recomendado"]["json"];
$skuRecomendado = valor($siguiente, "sku_recomendado", array());
$pasos = array(
    "Entrar a http://panel.com.local/ con el usuario real de quien va a cobrar.",
    "Ir a Ventas > Caja y turnos y abrir turno con monto inicial contado.",
    "Ir a Ventas > POS, confirmar operador visible, agregar producto y capturar pago.",
    "Prevalidar; si todo esta correcto, cobrar.",
    "Revisar ticket y entregarlo solo si el cliente lo solicita.",
    "Cerrar turno con el monto contado real, aunque haya diferencia.",
);

$decision = "requiere_revision";
if (empty($bloqueos)) {
    $decision = !empty(valor($operacion, "turno_abierto", false)) && !empty(valor($operacion, "puede_cobrar_ahora", false))
        ? "listo_para_cobrar_ahora"
        : "listo_para_arrancar_al_abrir_turno";
}

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_arranque_local_readonly",
    "read_only" => true,
    "fecha" => date("Y-m-d H:i:s"),
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "decision" => $decision,
    "resumen_operativo" => array(
        "turno_abierto" => !empty(valor($operacion, "turno_abierto", false)),
        "puede_cobrar_ahora" => !empty(valor($operacion, "puede_cobrar_ahora", false)),
        "ticket_configurado" => !empty(valor($operacion, "ticket_configurado", false)),
        "stock_disponible_sku_preferido" => valor($operacion, "stock_disponible_sku", 0),
        "pendientes_inventario" => valor($operacion, "pendientes_inventario", 0),
        "evidencias_caja_pendientes" => valor($operacion, "evidencias_caja_pendientes", 0),
        "ventas_hoy" => valor($operacion, "ventas_hoy", 0),
        "total_ventas_hoy" => valor($operacion, "total_ventas_hoy", 0),
    ),
    "sku_recomendado_para_prueba_limpia" => $skuRecomendado,
    "paquete_autorizacion_sugerido" => valor($paquete, "pasos", array()),
    "pasos_operador" => $pasos,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "checks" => resumenChecks($checks),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_crea_pedido" => true,
        "no_resuelve_pendientes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit(empty($bloqueos) ? 0 : 1);

function ejecutar($php, $root, $script, $timeoutScript)
{
    $cmd = '"' . $php . '" ' . $script;
    $descriptores = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => array("pipe", "w"));
    $proceso = proc_open($cmd, $descriptores, $pipes, $root);
    if (!is_resource($proceso)) {
        return resultado(false, 1, $script, "No se pudo iniciar check", array(), null, false);
    }
    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $salida = "";
    $error = "";
    $inicio = time();
    $timeout = false;
    while (true) {
        $salida .= stream_get_contents($pipes[1]);
        $error .= stream_get_contents($pipes[2]);
        $estado = proc_get_status($proceso);
        if (!$estado["running"]) {
            break;
        }
        if ((time() - $inicio) >= $timeoutScript) {
            $timeout = true;
            proc_terminate($proceso);
            break;
        }
        usleep(100000);
    }
    $salida .= stream_get_contents($pipes[1]);
    $error .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($proceso);
    $json = json_decode(trim($salida), true);
    $ok = !$timeout && is_array($json) && !empty($json["ok"]);
    $avisos = is_array($json) ? valor($json, "avisos", array()) : array_filter(array(trim($error)));
    $mensaje = $timeout ? "Timeout en check" : (is_array($json) ? (valor($json, "decision", valor($json, "resultado", valor($json, "modo", "ok")))) : "Salida no JSON");
    return resultado($ok, $exitCode, $script, $mensaje, $avisos, $json, $timeout);
}

function resultado($ok, $exitCode, $script, $mensaje, $avisos, $json, $timeout)
{
    return array(
        "ok" => $ok,
        "exit_code" => $exitCode,
        "script" => $script,
        "mensaje" => $mensaje,
        "avisos" => is_array($avisos) ? array_values($avisos) : array(),
        "json" => $json,
        "timeout" => $timeout,
    );
}

function resumenChecks($checks)
{
    $salida = array();
    foreach ($checks as $clave => $check) {
        $salida[$clave] = array(
            "ok" => !empty($check["ok"]),
            "mensaje" => $check["mensaje"],
            "timeout" => !empty($check["timeout"]),
            "avisos_total" => count($check["avisos"]),
        );
    }
    return $salida;
}

function parseArgs($argv)
{
    $out = array();
    foreach ($argv as $arg) {
        if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
            continue;
        }
        list($k, $v) = explode("=", substr($arg, 2), 2);
        $out[$k] = trim($v, "\"' ");
    }
    return $out;
}

function entero($args, $key, $default)
{
    return isset($args[$key]) ? (int) $args[$key] : $default;
}

function decimal($args, $key, $default)
{
    return isset($args[$key]) ? (float) str_replace(",", ".", (string) $args[$key]) : $default;
}

function valor($arr, $key, $default = null)
{
    return is_array($arr) && array_key_exists($key, $arr) ? $arr[$key] : $default;
}
