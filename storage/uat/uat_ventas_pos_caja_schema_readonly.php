<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: auditar y generar plan DDL de caja POS completa sin ejecutar cambios.
 * Impacto: prepara autorizacion futura para gastos, retiros, vales, reembolsos y autorizaciones.
 * Contrato: read-only; no modifica estructura.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "caja_schema_readonly",
    "auditoria" => $esquema->auditarCajaCompleta(),
    "plan" => $esquema->planActualizarCajaCompleta(false)
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
