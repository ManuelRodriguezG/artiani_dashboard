<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: auditar y generar plan DDL del contrato CRM/POS sin ejecutar cambios.
 * Impacto: prepara columnas canonicas para ligar ventas y excepciones comerciales con CRM.
 * Contrato: read-only; no modifica estructura ni datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_crm_contrato_schema_readonly",
    "auditoria" => $esquema->auditarContratoCrmPos(),
    "plan" => $esquema->planActualizarContratoCrmPos(false),
    "siguiente_paso" => "Si el plan es correcto, solicitar autorizacion VENTAS_POS_CRM_CONTRATO_DDL con respaldo externo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
