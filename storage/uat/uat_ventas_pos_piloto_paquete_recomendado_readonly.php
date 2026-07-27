<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: preparar paquete operativo de piloto POS con SKU recomendado y stock disponible.
 * Impacto: evita cargar stock UAT innecesario cuando ya existe un SKU vendible para prueba controlada.
 * Contrato: read-only; no abre turno, no cobra, no cierra turno, no mueve caja y no mueve inventario.
 */

date_default_timezone_set("America/Mexico_City");

$args = parseArgs($argv);
$idUsuario = entero($args, "id_usuario", 1);
$idAlmacen = entero($args, "id_almacen", 5);
$idSkuPreferido = entero($args, "id_sku", 173);
$cantidad = decimal($args, "cantidad", 1);
$montoInicial = decimal($args, "monto_inicial", 500);
$cliente = texto($args, "cliente", "Cliente piloto POS");
$usuarios = texto($args, "usuarios", "1,2,3");

$siguiente = ejecutar("uat_ventas_pos_siguiente_piloto_readonly.php", array(
    "--id_usuario={$idUsuario}",
    "--id_almacen={$idAlmacen}",
    "--id_sku={$idSkuPreferido}",
    "--cantidad={$cantidad}",
    "--monto_inicial={$montoInicial}",
    "--usuarios={$usuarios}",
));

$sku = valor($siguiente, "sku_recomendado", array());
$bloqueos = array();
if (empty($sku) || empty($sku["id_sku"])) {
    $bloqueos[] = "No hay SKU recomendado con stock/precio para piloto limpio.";
}

$idSkuVenta = enteroArray($sku, "id_sku", 0);
$precio = decimalArray($sku, "precio", 0);
$total = redondear($cantidad * $precio);
$montoContadoSugerido = redondear($montoInicial + $total);

$pasos = array();
if (!$bloqueos) {
    $pasos[] = array(
        "orden" => 1,
        "codigo" => "abrir_turno",
        "descripcion" => "Abrir turno en Caja y turnos con conteo inicial real.",
        "autorizacion_humana" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura piloto POS con SKU recomendado\"",
    );
    $pasos[] = array(
        "orden" => 2,
        "codigo" => "venta_piloto_recomendada",
        "descripcion" => "Cobrar venta normal con SKU recomendado, stock disponible y precio vigente.",
        "autorizacion_humana" => "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_sku={$idSkuVenta} cantidad={$cantidad} precio={$precio} pago={$total} cliente=\"{$cliente}\"",
    );
    $pasos[] = array(
        "orden" => 3,
        "codigo" => "cerrar_turno",
        "descripcion" => "Cerrar turno con monto contado real; puede diferir y quedar a revision.",
        "autorizacion_humana" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado={$montoContadoSugerido} observaciones=\"Cierre piloto POS SKU {$idSkuVenta}\"",
    );
}

$avisos = valor($siguiente, "avisos", array());
$avisos[] = "Si el monto contado real no es {$montoContadoSugerido}, capturar el monto real; la diferencia queda visible para revision.";
$avisos[] = "Este paquete no crea ni corrige pendientes; solo prepara un piloto limpio con SKU disponible.";

$respuesta = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_piloto_paquete_recomendado_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "panel.com.local",
    "decision" => empty($bloqueos) ? "paquete_recomendado_preparado" : "paquete_recomendado_no_disponible",
    "sku_preferido" => valor($siguiente, "sku_preferido", array()),
    "sku_recomendado" => $sku,
    "resumen" => array(
        "pasos_total" => count($pasos),
        "bloqueos_total" => count($bloqueos),
        "monto_inicial" => $montoInicial,
        "total_venta" => $total,
        "monto_contado_sugerido" => $montoContadoSugerido,
    ),
    "pasos" => $pasos,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "postchecks" => array(
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_operacion_basica_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSkuVenta} --usuarios={$usuarios} --compact=1",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_piloto_postcheck_compacto_readonly.php --id_usuario={$idUsuario} --id_almacen={$idAlmacen} --id_sku={$idSkuVenta}",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_cierre_ampliado_readonly.php --compact=1 --timeout_script=12",
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_cobra" => true,
        "no_cierra_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_crea_ni_corrige_pendientes" => true,
    ),
);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
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
        return array("ok" => false, "bloqueos" => array("No se pudo leer siguiente piloto recomendado"), "exit_code" => $codigo);
    }
    return $json;
}

function parseArgs($argv)
{
    $out = array();
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, "--") !== 0) {
            continue;
        }
        $partes = explode("=", substr($arg, 2), 2);
        $out[$partes[0]] = isset($partes[1]) ? trim($partes[1], "\"' ") : true;
    }
    return $out;
}

function entero($args, $clave, $default) { return isset($args[$clave]) ? (int) $args[$clave] : $default; }
function decimal($args, $clave, $default) { return isset($args[$clave]) ? (float) $args[$clave] : $default; }
function texto($args, $clave, $default) { return isset($args[$clave]) ? (string) $args[$clave] : $default; }
function valor($datos, $campo, $default = null) { return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default; }
function enteroArray($datos, $campo, $default) { return isset($datos[$campo]) ? (int) $datos[$campo] : $default; }
function decimalArray($datos, $campo, $default) { return isset($datos[$campo]) ? (float) $datos[$campo] : $default; }
function redondear($valor) { return rtrim(rtrim(number_format((float) $valor, 2, ".", ""), "0"), "."); }
