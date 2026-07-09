<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: auditar/planear esquema de correcciones para evidencias de caja POS aprobadas.
 * Impacto: solo lectura; no crea tabla ni modifica evidencias existentes.
 * Contrato: read-only.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$auditoria = $esquema->auditarCorreccionesEvidenciasCajaPos();
$plan = $esquema->planActualizarCorreccionesEvidenciasCajaPos(false);

echo json_encode(array(
    "ok" => empty($auditoria["error"]),
    "modo" => "ventas_pos_caja_evidencias_correccion_schema_readonly",
    "read_only" => true,
    "auditoria" => $auditoria,
    "plan" => $plan,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_modifica_evidencias" => true,
        "no_modifica_caja" => true,
        "no_mueve_dinero" => true,
        "no_mueve_inventario" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
