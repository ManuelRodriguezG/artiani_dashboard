-- ERP Almacen - Transformaciones/Reempaque de presentaciones
-- Estado: propuesta, NO ejecutar sin respaldo externo y autorizacion.
-- Objetivo: soportar preparacion desde una existencia fisica origen especifica,
-- incluyendo reempaque presentacion -> presentacion.

CREATE TABLE IF NOT EXISTS erp_catalogo_sku_transformaciones (
  id_sku_transformacion BIGINT NOT NULL AUTO_INCREMENT,
  id_sku_origen BIGINT NOT NULL,
  id_sku_resultado BIGINT NOT NULL,
  cantidad_origen DECIMAL(18,6) NOT NULL,
  unidades_resultado INT NOT NULL,
  tipo_transformacion VARCHAR(40) NOT NULL DEFAULT 'reempaque',
  modo_disponibilidad VARCHAR(30) NOT NULL DEFAULT 'preparada',
  merma_porcentaje DECIMAL(9,4) NOT NULL DEFAULT 0.0000,
  requiere_empaque TINYINT(1) NOT NULL DEFAULT 1,
  capacidad_diaria DECIMAL(18,6) NULL,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activa',
  observaciones TEXT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_sku_transformacion),
  KEY idx_sku_transformacion_origen (id_sku_origen),
  KEY idx_sku_transformacion_resultado (id_sku_resultado),
  KEY idx_sku_transformacion_estatus (estatus),
  KEY idx_sku_transformacion_tipo (tipo_transformacion),
  CONSTRAINT fk_sku_transformacion_origen FOREIGN KEY (id_sku_origen) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_sku_transformacion_resultado FOREIGN KEY (id_sku_resultado) REFERENCES erp_catalogo_skus (id_sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE erp_almacen_preparaciones
  ADD COLUMN id_sku_transformacion BIGINT NULL AFTER id_sku_presentacion_regla,
  ADD COLUMN id_existencia_origen INT NULL AFTER id_sku_transformacion,
  ADD COLUMN cantidad_origen_consumida DECIMAL(18,6) NULL AFTER cantidad_base_consumida,
  ADD KEY idx_almacen_preparacion_transformacion (id_sku_transformacion),
  ADD KEY idx_almacen_preparacion_existencia_origen (id_existencia_origen);

-- FK opcionales para aplicar con revision del esquema local:
-- ALTER TABLE erp_almacen_preparaciones
--   ADD CONSTRAINT fk_almacen_preparacion_transformacion
--   FOREIGN KEY (id_sku_transformacion) REFERENCES erp_catalogo_sku_transformaciones (id_sku_transformacion),
--   ADD CONSTRAINT fk_almacen_preparacion_existencia_origen
--   FOREIGN KEY (id_existencia_origen) REFERENCES erp_inventario_existencias (id_existencia_inventario);

-- Migracion conceptual sugerida de reglas existentes:
-- TP-40372 -> TP-40372-25GR: cantidad_origen=0.025000, unidades_resultado=1.
-- TP-40372 -> TP-40372-50GR: cantidad_origen=0.050000, unidades_resultado=1.
-- TP-40372 -> TP-40372-100GR: cantidad_origen=0.100000, unidades_resultado=1.
-- TP-40372 -> TP-40372-500GR: cantidad_origen=0.500000, unidades_resultado=1.
-- TP-40372-500GR -> TP-40372-100GR: cantidad_origen=1.000000, unidades_resultado=5.
