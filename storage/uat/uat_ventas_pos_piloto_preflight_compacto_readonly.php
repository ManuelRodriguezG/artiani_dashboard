<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: entregar un preflight compacto y operable para decidir si iniciar piloto POS.
 * Impacto: resume el semaforo consolidado en pasos accionables para tienda sin escribir BD.
 * Contrato: read-only; no abre turno, no cobra, no resuelve pendientes, no mueve caja ni inventario.
 */

date_default_timezone_set("America/Mexico_City");

$args = array(
    "--id_usuario=1",
    "--id_almacen=5",
    "--id_caja=2",
    "--id_terminal=2",
    "--id_sku=173",
    "--id_atencion=2",
    "--cantidad=1",
    "--usuarios=1,2,3",
    "--compact=1",
);

foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--") === 0) {
        $nombre = explode("=", $arg, 2)[0];
        $args = array_values(array_filter($args, function ($item) use ($nombre) {
            return strpos($item, $nombre . "=") !== 0;
        }));
        $args[] = $arg;
    }
}

$salida = ejecutar("uat_ventas_pos_salida_operativa_readiness_readonly.php", $args);
$siguientePiloto = ejecutar("uat_ventas_pos_siguiente_piloto_readonly.php", array(
    "--id_usuario=" . valorArg($args, "id_usuario", "1"),
    "--id_almacen=" . valorArg($args, "id_almacen", "5"),
    "--id_sku=" . valorArg($args, "id_sku", "173"),
    "--cantidad=" . valorArg($args, "cantidad", "1"),
    "--monto_inicial=500",
    "--usuarios=" . valorArg($args, "usuarios", "1,2,3"),
));
$bloqueos = valor($salida, "bloqueos", array());
$avisos = valor($salida, "avisos", array());
$condiciones = valor($salida, "condiciones_para_piloto", array());
$resumen = valor($salida, "resumen", array());
$skuRecomendado = valor($siguientePiloto, "sku_recomendado", array());

$pasos = array(
    "1. Entrar a http://panel.com.local/ con usuario propio.",
    "2. Ir a Ventas > Caja/Turnos y abrir turno con conteo inicial real.",
    "3. Ir a Ventas > POS y validar que el operador mostrado sea el usuario correcto.",
    "4. Vender solo productos con existencia disponible durante el primer piloto.",
    "5. Cobrar una venta normal, imprimir/guardar ticket y revisar que aparezca en Reportes POS.",
    "6. Cerrar turno desde Caja/Turnos con el monto contado real; si hay diferencia, documentarla.",
);

$evitar = array(
    "No usar devoluciones reales en el primer piloto.",
    "No usar apartados nuevos en el primer piloto.",
    "No usar descuentos libres sin politica autorizada.",
    "No usar inventario pendiente como operacion normal de tienda.",
);

$recomendacionOperativa = array(
    "decision" => valor($siguientePiloto, "decision", "no_evaluado"),
    "sku_recomendado" => $skuRecomendado,
    "bloqueos" => valor($siguientePiloto, "bloqueos", array()),
    "avisos" => valor($siguientePiloto, "avisos", array()),
    "paso_practico" => !empty($skuRecomendado)
        ? "Para piloto limpio: abrir turno y vender SKU " . valor($skuRecomendado, "id_sku", "-") . " con precio " . valor($skuRecomendado, "precio", "-") . "."
        : "Usar producto con stock disponible o resolver/cargar inventario antes de cobrar.",
);

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_preflight_compacto_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => valor($salida, "decision", "no_listo"),
    "resumen" => array(
        "entorno_canonico_ok" => valor($resumen, "entorno_canonico_ok", false),
        "go_nogo_decision" => valor($resumen, "go_nogo_decision", null),
        "multiusuario_listo" => valor($resumen, "multiusuario_listo", null),
        "bloqueos_total" => count($bloqueos),
        "avisos_total" => count($avisos),
    ),
    "puede_iniciar_piloto_controlado" => empty($bloqueos),
    "condiciones" => $condiciones,
    "siguiente_piloto_recomendado" => $recomendacionOperativa,
    "pasos_piloto" => $pasos,
    "evitar_en_primer_piloto" => $evitar,
    "siguiente_autorizacion_si_se_desea_operar" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario=1 y monto_inicial=500 observaciones=\"Apertura piloto POS con SKU recomendado\"",
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_resuelve_pendientes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit(empty($bloqueos) ? 0 : 1);

function ejecutar($script, $args)
{
    $ruta = __DIR__ . DIRECTORY_SEPARATOR . $script;
    $cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($ruta);
    foreach ($args as $arg) {
        $cmd .= " " . escapeshellarg($arg);
    }
    $lineas = array();
    $codigo = 0;
    exec($cmd, $lineas, $codigo);
    $json = json_decode(implode("\n", $lineas), true);
    if (!is_array($json)) {
        return array(
            "ok" => false,
            "decision" => "no_listo",
            "bloqueos" => array("No se pudo leer salida operativa consolidada"),
            "avisos" => array("exit_code=" . $codigo),
        );
    }
    return $json;
}

function valor($datos, $campo, $default = null)
{
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function valorArg($args, $clave, $default)
{
    foreach ($args as $arg) {
        if (strpos($arg, "--" . $clave . "=") === 0) {
            return substr($arg, strlen("--" . $clave . "="));
        }
    }
    return $default;
}
