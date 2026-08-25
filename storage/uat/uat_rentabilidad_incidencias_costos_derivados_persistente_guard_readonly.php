<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-24
 * Proposito: validar candados de resolucion persistente de incidencias de costo derivado.
 * Impacto: asegura que Rentabilidad no escriba `erp_notificaciones` sin respaldo externo y frase exacta.
 * Contrato: UAT read-only por guard clauses; no modifica Catalogo, Listas, Ventas ni notificaciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$listado = $modelo->listarIncidenciasCostoDerivado(array("limite" => 1));
$items = empty($listado["error"]) && isset($listado["depurar"]["items"]) ? $listado["depurar"]["items"] : array();
$idNotificacion = !empty($items) ? intval($items[0]["id_notificacion"]) : 0;

$sinConfirmacion = $modelo->resolverIncidenciaCostoDerivadoPersistente(array(
    "id_notificacion" => $idNotificacion
), 0);

$sinRespaldo = $modelo->resolverIncidenciaCostoDerivadoPersistente(array(
    "id_notificacion" => $idNotificacion,
    "confirmar_autorizacion" => "AUTORIZO APLICAR RESOLUCION PERSISTENTE DE INCIDENCIAS DE COSTO DERIVADO"
), 0);

$fallas = array();
if (empty($sinConfirmacion["error"])) {
    $fallas[] = array("id" => "COST-INC-GUARD-001", "mensaje" => "Debe rechazar resolucion sin confirmacion exacta");
}
if (empty($sinRespaldo["error"])) {
    $fallas[] = array("id" => "COST-INC-GUARD-002", "mensaje" => "Debe rechazar resolucion sin respaldo externo");
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_incidencias_costos_derivados_persistente_guard_readonly",
    "contrato" => array(
        "solo_lectura_por_guard_clause" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_listas" => true,
        "no_toca_ventas" => true
    ),
    "id_notificacion_probada" => $idNotificacion,
    "fallas" => $fallas,
    "sin_confirmacion" => $sinConfirmacion,
    "sin_respaldo" => $sinRespaldo
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
