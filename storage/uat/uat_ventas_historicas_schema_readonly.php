<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-11.
 * Proposito: auditar y generar plan DDL para carga historica de ventas sin ejecutar cambios.
 * Impacto: prepara ventas pasadas para reportes/evidencia sin afectar caja, turno, kardex ni inventario.
 * Contrato: read-only; no crea columnas, no altera tablas, no registra ventas.
 */

$compacto = in_array("--compact=1", isset($argv) ? $argv : array(), true);

chdir(__DIR__ . "/../..");
require_once "app/iniciador.php";
require_once "app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$auditoria = $esquema->auditarVentasHistoricas();
$plan = $esquema->planActualizarVentasHistoricas(false);

$depurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();
$sql = array();
foreach ($plan as $paso) {
    if (isset($paso["depurar"]["sql"])) {
        $sql[] = $paso["depurar"]["sql"];
    }
}

$salida = array(
    "ok" => true,
    "modo" => "ventas_historicas_schema_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "auditoria" => $depurar,
    "ddl_generado" => $sql,
    "siguiente_autorizacion" => "AUTORIZO APLICAR DDL CARGA HISTORICA VENTAS usando respaldo productivo vigente con token VENTAS_HISTORICAS_DDL confirmacion=\"APLICAR CARGA HISTORICA VENTAS\" para Ventas/POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_columnas" => true,
        "no_altera_tablas" => true,
        "no_registra_ventas" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "venta_historica_usa_afecta_inventario_0" => true,
        "venta_historica_usa_afecta_caja_0" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "ventas_historicas_schema_readonly",
        "read_only" => true,
        "columnas_faltantes" => count(isset($depurar["faltan_columnas"]) && is_array($depurar["faltan_columnas"]) ? $depurar["faltan_columnas"] : array()),
        "indices_faltantes" => count(isset($depurar["faltan_indices"]) && is_array($depurar["faltan_indices"]) ? $depurar["faltan_indices"] : array()),
        "ddl_pasos_generados" => count($sql),
        "siguiente_autorizacion" => $salida["siguiente_autorizacion"],
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
