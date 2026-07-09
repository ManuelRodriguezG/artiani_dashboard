<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: auditar y generar plan DDL de reversas POS con componentes financieros para saldo CRM.
 * Impacto: prepara separacion caja/saldo CRM/saldo favor sin modificar estructura ni datos.
 * Contrato: read-only; no aplica DDL, no crea devoluciones, no mueve caja, no mueve CRM ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_reversa_saldo_crm_schema_readonly",
    "read_only" => true,
    "auditoria" => $esquema->auditarReversasSaldoCrmPos(),
    "plan" => $esquema->planActualizarReversasSaldoCrmPos(false),
    "contrato" => array(
        "no_aplica_ddl" => true,
        "no_crea_devoluciones" => true,
        "no_mueve_caja" => true,
        "no_mueve_saldo_crm" => true,
        "no_mueve_inventario" => true
    ),
    "siguiente_paso" => "Si el plan es correcto, solicitar autorizacion VENTAS_POS_REVERSA_SALDO_CRM_DDL con respaldo UAT vigente."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
