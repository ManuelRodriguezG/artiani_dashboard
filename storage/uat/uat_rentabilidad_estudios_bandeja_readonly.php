<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: validar bandeja read-only de estudios de rentabilidad.
 * Impacto: confirma que la vista puede listar estudios o reportar esquema pendiente sin escribir BD.
 * Contrato: no aplica DDL, no guarda estudios, no modifica Listas ni Catalogo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";
require_once "../app/modelos/RentabilidadEsquema.php";

$modelo = new RentabilidadErp();
$esquema = new RentabilidadEsquema();

$listado = $modelo->listarEstudiosRentabilidad(array("limite" => 20));
$categorias = $modelo->categoriasEstudioRentabilidad(array("limite" => 50));
$schemaDry = $esquema->planEstudiosRentabilidad(false);

$fallas = array();
if (!empty($listado["error"])) {
    $fallas[] = array("id" => "COST-EST-LIST-001", "mensaje" => $listado["mensaje"]);
}
if (!empty($categorias["error"])) {
    $fallas[] = array("id" => "COST-EST-LIST-002", "mensaje" => $categorias["mensaje"]);
}
if (!empty($schemaDry["error"])) {
    $fallas[] = array("id" => "COST-EST-LIST-003", "mensaje" => $schemaDry["mensaje"]);
}
if (!isset($listado["depurar"]["items"]) || !array_key_exists("schema_pendiente", $listado["depurar"])) {
    $fallas[] = array("id" => "COST-EST-LIST-004", "mensaje" => "El listado debe devolver items y bandera schema_pendiente");
}
if (empty($schemaDry["depurar"]["tablas"]) || !in_array("erp_rentabilidad_estudios", $schemaDry["depurar"]["tablas"], true)) {
    $fallas[] = array("id" => "COST-EST-LIST-005", "mensaje" => "El dry-run debe incluir tabla de estudios");
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_estudios_bandeja_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_aplica_schema" => true,
        "no_actualiza_precios" => true
    ),
    "fallas" => $fallas,
    "listado" => array(
        "schema_pendiente" => isset($listado["depurar"]["schema_pendiente"]) ? $listado["depurar"]["schema_pendiente"] : null,
        "total" => isset($listado["depurar"]["resumen"]["total"]) ? $listado["depurar"]["resumen"]["total"] : null
    ),
    "categorias_total" => isset($categorias["depurar"]["items"]) ? count($categorias["depurar"]["items"]) : null,
    "schema_resumen" => isset($schemaDry["depurar"]["resumen"]) ? $schemaDry["depurar"]["resumen"] : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
