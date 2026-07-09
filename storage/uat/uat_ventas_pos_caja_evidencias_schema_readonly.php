<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: auditar DDL requerido para evidencias/adjuntos de caja POS sin escribir BD.
 * Impacto: identifica si falta tabla, columnas o indices para comprobantes de reembolsos/gastos.
 * Contrato: read-only.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
responder(array(
    "ok" => true,
    "modo" => "ventas_pos_caja_evidencias_schema_readonly",
    "read_only" => true,
    "auditoria" => $esquema->auditarEvidenciasCajaPos(),
    "plan" => $esquema->planActualizarEvidenciasCajaPos(false),
    "siguiente_autorizacion" => "AUTORIZO APLICAR DDL EVIDENCIAS CAJA POS usando respaldo C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql con token VENTAS_POS_CAJA_EVIDENCIAS_DDL para UAT POS"
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
