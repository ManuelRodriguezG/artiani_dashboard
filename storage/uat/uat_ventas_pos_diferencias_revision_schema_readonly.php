<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: auditar DDL de revision formal de diferencias de caja POS sin escribir BD.
 * Impacto: confirma tabla, columnas e indices para seguimiento de faltantes/sobrantes.
 * Contrato: read-only.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_diferencias_revision_schema_readonly",
    "read_only" => true,
    "auditoria" => $esquema->auditarRevisionDiferenciasCajaPos(),
    "plan" => $esquema->planActualizarRevisionDiferenciasCajaPos(false),
    "siguiente_autorizacion" => "AUTORIZO APLICAR DDL REVISION DIFERENCIAS CAJA POS usando respaldo UAT POS vigente con token VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL para UAT POS",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_tabla" => true,
        "no_modifica_turnos" => true,
        "no_mueve_caja" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
