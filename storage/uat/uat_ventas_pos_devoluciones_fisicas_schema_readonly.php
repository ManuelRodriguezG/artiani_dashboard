<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: auditar y generar plan DDL de inspeccion fisica de devoluciones POS sin ejecutar cambios.
 * Impacto: prepara cierre de cuarentena/reintegro/merma/garantia con trazabilidad.
 * Contrato: read-only; no modifica estructura, no resuelve partidas y no mueve inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/controladores/Ventas.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_devoluciones_fisicas_schema_readonly",
    "endpoints" => array(
        "esquema_auditar_inspeccion_fisica_devoluciones_pos" => method_exists("Ventas", "esquema_auditar_inspeccion_fisica_devoluciones_pos"),
        "esquema_actualizar_inspeccion_fisica_devoluciones_pos" => method_exists("Ventas", "esquema_actualizar_inspeccion_fisica_devoluciones_pos"),
        "devoluciones_inventario_pendientes_erp" => method_exists("Ventas", "devoluciones_inventario_pendientes_erp")
    ),
    "auditoria" => $esquema->auditarInspeccionFisicaDevolucionesPos(),
    "plan" => $esquema->planActualizarInspeccionFisicaDevolucionesPos(false),
    "siguiente_paso" => "Si el plan es correcto, aplicar con token VENTAS_POS_DEVOLUCIONES_FISICAS_DDL y respaldo externo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
