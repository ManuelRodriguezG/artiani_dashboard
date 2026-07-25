<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: diagnosticar preparacion de configuracion de ticket POS sin escribir BD.
 * Impacto: identifica tablas/configuracion faltante para datos negocio, formato e impresion.
 * Contrato: read-only; no crea tablas, no actualiza tickets ni toca impresoras.
 */
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
class UatTicketConfigDb extends CRUD { public function db() { return $this->getConexion(); } }
$db = (new UatTicketConfigDb())->db();
$tablas = array(
    "sys_configuracion_parametros",
    "erp_almacenes",
    "erp_sucursales",
    "erp_pos_cajas",
    "erp_pos_terminales",
    "erp_pos_ticket_configuracion",
    "erp_empresa_configuracion"
);
$estadoTablas = array();
foreach ($tablas as $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    $estadoTablas[$tabla] = (bool) $stmt->fetchColumn();
}
$checks = array(
    "ticket_readonly_endpoint" => metodoExiste("VentasErp", "ticketVentaFormalReadOnly"),
    "ticket_formateador" => archivoContiene("../app/modelos/VentasErp.php", "function formatearTicketVenta"),
    "impresion_navegador" => archivoContiene("assets/js/custom/apps/erp/ventas/venta_detalle.js", "ventana.print"),
    "config_ticket_dedicada" => $estadoTablas["erp_pos_ticket_configuracion"],
    "config_empresa_dedicada" => $estadoTablas["erp_empresa_configuracion"],
    "config_pos_terminales" => $estadoTablas["erp_pos_terminales"],
    "config_cajas" => $estadoTablas["erp_pos_cajas"]
);
$bloqueos = array();
if (!$checks["ticket_readonly_endpoint"] || !$checks["ticket_formateador"]) { $bloqueos[] = "ticket_formal_base_incompleto"; }
if (!$checks["config_ticket_dedicada"]) { $bloqueos[] = "falta_tabla_configuracion_ticket"; }
if (!$checks["config_empresa_dedicada"]) { $bloqueos[] = "falta_configuracion_empresa_formal"; }
$avisos = array();
if ($checks["impresion_navegador"]) { $avisos[] = "Impresion actual usa navegador/window.print; suficiente para piloto, no para corte automatico/cajon."; }
$avisos[] = "58 mm recomendado con 32 columnas; 80 mm recomendado con 42/48 columnas.";
$avisos[] = "Logo bitmap en ticket termico requiere preview y posiblemente bridge ESC/POS; para piloto puede usarse texto de marca.";

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_ticket_config_readiness_readonly",
    "tablas" => $estadoTablas,
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "decision" => empty($bloqueos) ? "ticket_configurado" : "requiere_ddl_configuracion_ticket",
    "siguiente_autorizacion" => "AUTORIZO PREPARAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL para UAT POS",
    "contrato" => array("read_only" => true, "no_escribe_bd" => true, "no_toca_impresoras" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

function archivoContiene($ruta, $texto) {
    $real = realpath(__DIR__ . "/../../public/" . $ruta);
    return $real && is_file($real) && strpos(file_get_contents($real), $texto) !== false;
}
function metodoExiste($clase, $metodo) {
    require_once "../app/modelos/VentasErp.php";
    return method_exists($clase, $metodo);
}