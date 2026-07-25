<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: consultar configuracion efectiva de ticket POS por almacen/caja/terminal.
 * Impacto: valida herencia global antes de conectar UI/impresora; no escribe BD ni imprime.
 * Contrato: read-only.
 */

$args = isset($argv) ? $argv : array();
$scope = array("id_almacen" => 0, "id_caja" => 0, "id_terminal_pos" => 0);
$compacto = in_array("--compact=1", $args, true);
foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $scope["id_almacen"] = intval(substr($arg, 13));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $scope["id_caja"] = intval(substr($arg, 10));
    } elseif (strpos($arg, "--id_terminal_pos=") === 0) {
        $scope["id_terminal_pos"] = intval(substr($arg, 18));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$respuesta = $modelo->ticketConfiguracionEfectivaReadOnly($scope);
$config = isset($respuesta["depurar"]["configuracion"]) ? $respuesta["depurar"]["configuracion"] : array();
$salida = array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_ticket_config_efectiva_readonly",
    "read_only" => true,
    "scope" => $scope,
    "configuracion" => $config,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_configura_impresora" => true,
        "no_imprime" => true
    )
);
if ($compacto) {
    $salida = array(
        "ok" => $salida["ok"],
        "modo" => $salida["modo"],
        "read_only" => true,
        "nombre_comercial" => isset($config["nombre_comercial"]) ? $config["nombre_comercial"] : null,
        "ticket_ancho_mm" => isset($config["ticket_ancho_mm"]) ? $config["ticket_ancho_mm"] : null,
        "ticket_columnas" => isset($config["ticket_columnas"]) ? $config["ticket_columnas"] : null,
        "impresion_modo" => isset($config["impresion_modo"]) ? $config["impresion_modo"] : null,
        "origen_configuracion" => isset($config["origen_configuracion"]) ? $config["origen_configuracion"] : null,
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);