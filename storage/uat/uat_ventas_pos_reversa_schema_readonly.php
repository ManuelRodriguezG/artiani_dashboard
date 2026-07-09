<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: auditar y generar plan DDL de reversas POS sin ejecutar cambios.
 * Impacto: prepara devoluciones/cancelaciones con reembolso, saldo a favor, caja e inventario.
 * Contrato: read-only; no modifica estructura ni datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/controladores/Ventas.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_reversa_schema_readonly",
    "endpoints" => array(
        "esquema_auditar_reversas_pos" => method_exists("Ventas", "esquema_auditar_reversas_pos"),
        "esquema_actualizar_reversas_pos" => method_exists("Ventas", "esquema_actualizar_reversas_pos"),
        "devolucion_dryrun_erp" => method_exists("Ventas", "devolucion_dryrun_erp")
    ),
    "auditoria" => $esquema->auditarReversasPos(),
    "plan" => $esquema->planActualizarReversasPos(false),
    "siguiente_paso" => "Si el plan es correcto, solicitar autorizacion VENTAS_POS_REVERSA_DDL con respaldo externo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
