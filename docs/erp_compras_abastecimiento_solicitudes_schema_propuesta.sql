-- IA: Codex GPT-5
-- Fecha: 2026-07-29
-- Proposito: habilitar persistencia de evidencia de abastecimiento en solicitudes de compra.
-- Impacto: agrega solo una columna nullable; no modifica datos existentes ni flujos de ordenes.
-- Contrato: ejecutar solo con respaldo externo conforme a docs/erp_respaldo_bd_estandar.md.

ALTER TABLE `erp_compras_solicitudes_detalle`
  ADD COLUMN `evidencia_costo_json` TEXT NULL;
