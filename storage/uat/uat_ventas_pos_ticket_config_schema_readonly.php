<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: auditar DDL de configuracion formal de ticket POS.
 * Impacto: informa faltantes de empresa/ticket/snapshot; no inserta empresa ni altera ventas.
 * Contrato: read-only; no crea tablas, no altera columnas, no configura impresoras y no imprime tickets.
 */

$compacto = in_array("--compact=1", isset($argv) ? $argv : array(), true);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$modelo = new VentasErpEsquema();
$auditoria = $modelo->auditarTicketConfigPos();
$plan = $modelo->planActualizarTicketConfigPos(false);

$depurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();
$faltantes = array(
    "tablas" => array(),
    "columnas" => array(),
    "indices" => array()
);
foreach (array("tablas", "columnas", "indices") as $grupo) {
    foreach (isset($depurar[$grupo]) && is_array($depurar[$grupo]) ? $depurar[$grupo] : array() as $item) {
        if (empty($item["existe"])) {
            $faltantes[$grupo][] = $item;
        }
    }
}

$sql = array();
foreach ($plan as $paso) {
    if (isset($paso["depurar"]["sql"])) {
        $sql[] = $paso["depurar"]["sql"];
    }
}

$salida = array(
    "ok" => true,
    "modo" => "ventas_pos_ticket_config_schema_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "auditoria" => $depurar,
    "faltantes" => $faltantes,
    "ddl_generado" => $sql,
    "siguiente_autorizacion" => "AUTORIZO APLICAR DDL CONFIGURACION TICKET POS usando respaldo UAT POS vigente con token VENTAS_POS_TICKET_CONFIG_DDL confirmacion=\"APLICAR CONFIG TICKET POS\" para UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_tablas" => true,
        "no_altera_tablas" => true,
        "no_crea_venta" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_configura_impresora" => true,
        "no_imprime" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "ventas_pos_ticket_config_schema_readonly",
        "read_only" => true,
        "tablas_faltantes" => count($faltantes["tablas"]),
        "columnas_faltantes" => count($faltantes["columnas"]),
        "indices_faltantes" => count($faltantes["indices"]),
        "ddl_pasos_generados" => count($sql),
        "siguiente_autorizacion" => $salida["siguiente_autorizacion"],
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);