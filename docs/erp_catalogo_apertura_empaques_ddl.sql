-- ERP Catalogo - DDL acotado para Apertura de empaques
-- IA: Codex GPT-5
-- Fecha: 2026-07-28
-- Estado: aplicado el 2026-07-28 con token CATALOGO_APERTURA_EMPAQUES_DDL
-- Respaldo externo: C:\xampp\panel_db_backups\artianilocal_panel_20260728_antes_catalogo_apertura_empaques.sql
-- Alcance: crear solo erp_catalogo_sku_aperturas_empaque

CREATE TABLE IF NOT EXISTS erp_catalogo_sku_aperturas_empaque (
  id_apertura_empaque BIGINT NOT NULL AUTO_INCREMENT,
  id_sku_origen BIGINT NOT NULL,
  id_sku_destino BIGINT NOT NULL,
  factor_conversion DECIMAL(18,6) NOT NULL,
  requiere_unidad_fisica TINYINT(1) NOT NULL DEFAULT 1,
  conserva_lote TINYINT(1) NOT NULL DEFAULT 1,
  conserva_caducidad TINYINT(1) NOT NULL DEFAULT 1,
  permite_merma TINYINT(1) NOT NULL DEFAULT 1,
  merma_porcentaje_default DECIMAL(9,4) NOT NULL DEFAULT 0.0000,
  instrucciones_operativas TEXT NULL,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activo',
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_apertura_empaque),
  UNIQUE KEY idx_catalogo_apertura_origen_destino (id_sku_origen, id_sku_destino),
  KEY idx_catalogo_apertura_destino (id_sku_destino),
  CONSTRAINT fk_catalogo_apertura_origen FOREIGN KEY (id_sku_origen) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_catalogo_apertura_destino FOREIGN KEY (id_sku_destino) REFERENCES erp_catalogo_skus (id_sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;