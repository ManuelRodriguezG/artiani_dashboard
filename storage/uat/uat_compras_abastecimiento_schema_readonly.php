<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: auditar en dry-run el soporte de evidencia de abastecimiento en solicitudes de compra.
 * Impacto: solo lectura; no ejecuta DDL ni modifica datos.
 * Contrato: resume si el plan de Compras contempla erp_compras_solicitudes_detalle.evidencia_costo_json.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/ComprasEsquema.php";

$esquema = new ComprasEsquema();
$plan = $esquema->planActualizarOrdenCompra(false);
$items = isset($plan["depurar"]) && is_array($plan["depurar"]) ? $plan["depurar"] : array();
$objetivo = "erp_compras_solicitudes_detalle.evidencia_costo_json";
$coincidencias = array();

foreach ($items as $item) {
    $texto = json_encode($item, JSON_UNESCAPED_UNICODE);
    if (strpos($texto, "erp_compras_solicitudes_detalle") !== false &&
        strpos($texto, "evidencia_costo_json") !== false) {
        $coincidencias[] = $item;
    }
}

responder(array(
    "ok" => isset($plan["error"]) ? !$plan["error"] : false,
    "modo" => "readonly",
    "mensaje" => "Dry-run de esquema de Compras consultado",
    "objetivo" => $objetivo,
    "plan_total" => count($items),
    "objetivo_en_plan" => count($coincidencias) > 0,
    "coincidencias" => $coincidencias,
    "no_ejecuta_ddl" => true
));

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
