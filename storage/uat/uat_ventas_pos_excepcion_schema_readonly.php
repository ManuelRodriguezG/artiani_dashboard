<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: auditar y generar plan DDL de excepciones comerciales POS sin ejecutar cambios.
 * Impacto: prepara persistencia de precio manual/descuentos con politica, autorizacion y snapshot.
 * Contrato: read-only; no modifica estructura ni datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_excepcion_schema_readonly",
    "auditoria" => $esquema->auditarExcepcionesComerciales(),
    "plan" => $esquema->planActualizarExcepcionesComerciales(false),
    "siguiente_paso" => "Si el plan es correcto, solicitar autorizacion VENTAS_POS_EXCEPCION_DDL con respaldo externo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
