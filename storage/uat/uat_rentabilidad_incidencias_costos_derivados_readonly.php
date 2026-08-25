<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-23
 * Proposito: validar bandeja read-only de incidencias de costo derivado desde Catalogo hacia Rentabilidad.
 * Impacto: confirma que Rentabilidad consume `erp_notificaciones` sin modificar Catalogo, Listas ni Ventas.
 * Contrato: solo lectura; no cambia estatus ni payload de incidencias.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$listado = $modelo->listarIncidenciasCostoDerivado(array("limite" => 20));
$items = empty($listado["error"]) && isset($listado["depurar"]["items"]) ? $listado["depurar"]["items"] : array();
$preResolucion = null;

if (!empty($items)) {
    $preResolucion = $modelo->preResolverIncidenciaCostoDerivado(array(
        "id_notificacion" => intval($items[0]["id_notificacion"])
    ));
}

$fallas = array();
if (!empty($listado["error"])) {
    $fallas[] = array("id" => "COST-INC-UAT-001", "mensaje" => $listado["mensaje"]);
}
if ($preResolucion !== null && !empty($preResolucion["error"])) {
    $fallas[] = array("id" => "COST-INC-UAT-002", "mensaje" => $preResolucion["mensaje"]);
}
if ($preResolucion !== null && empty($preResolucion["depurar"]["dry_run"])) {
    $fallas[] = array("id" => "COST-INC-UAT-003", "mensaje" => "La pre-resolucion debe ser dry-run");
}
if ($preResolucion !== null && tieneAdvertenciaCatalogo($items[0]) && ($preResolucion["depurar"]["responsable_bloqueo"] ?? "") !== "catalogo") {
    $fallas[] = array("id" => "COST-INC-UAT-004", "mensaje" => "Incidencia con advertencia CAT-DER debe proponer bloqueo para Catalogo");
}
foreach ($items as $item) {
    if (!array_key_exists("costo_resolucion", $item)) {
        $fallas[] = array("id" => "COST-INC-UAT-005", "mensaje" => "La bandeja debe incluir preview de costo por incidencia", "sku" => $item["sku_derivado"] ?? "");
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_incidencias_costos_derivados_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_listas" => true,
        "no_toca_ventas" => true
    ),
    "fallas" => $fallas,
    "listado" => $listado,
    "pre_resolucion_primera_incidencia" => $preResolucion
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tieneAdvertenciaCatalogo($item) {
    $payload = isset($item["payload"]) && is_array($item["payload"]) ? $item["payload"] : array();
    $advertencias = isset($payload["advertencias_configuracion"]) && is_array($payload["advertencias_configuracion"]) ? $payload["advertencias_configuracion"] : array();
    foreach ($advertencias as $advertencia) {
        $codigo = isset($advertencia["codigo"]) ? $advertencia["codigo"] : "";
        if (strpos($codigo, "CAT-DER-") === 0) {
            return true;
        }
    }
    return false;
}
