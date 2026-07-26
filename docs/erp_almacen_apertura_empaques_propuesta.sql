-- ERP Almacen - Apertura de empaques
-- Fecha: 2026-07-25
-- Estado: aplicado el 2026-07-25 con respaldo externo documentado en erp_almacen_apertura_empaques_diseno.md.
-- Objetivo: soportar apertura multi-salida de una unidad cerrada hacia piezas/SKUs internos.

ALTER TABLE erp_almacenes
  ADD COLUMN permite_apertura_empaque TINYINT(1) NOT NULL DEFAULT 0 AFTER permite_preparacion;

CREATE TABLE erp_almacen_aperturas_empaque (
  id_apertura_empaque INT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(60) NOT NULL,
  id_almacen INT NOT NULL,
  id_sku_origen BIGINT NOT NULL,
  id_existencia_origen INT NOT NULL,
  id_unidad_origen INT NULL,
  id_paquete BIGINT NULL,
  cantidad_origen DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  cantidad_resultado_total DECIMAL(18,6) NOT NULL DEFAULT 0.000000,
  lote VARCHAR(150) NULL,
  fecha_caducidad DATE NULL,
  ubicacion_id INT NULL,
  costo_unitario_origen DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  costo_total_origen DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  id_movimiento_salida INT NULL,
  estatus VARCHAR(30) NOT NULL DEFAULT 'borrador',
  observaciones TEXT NULL,
  creado_por INT NULL,
  confirmado_por INT NULL,
  fecha_apertura DATETIME NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_apertura_empaque),
  UNIQUE KEY uk_almacen_apertura_folio (folio),
  KEY idx_almacen_apertura_almacen (id_almacen),
  KEY idx_almacen_apertura_sku_origen (id_sku_origen),
  KEY idx_almacen_apertura_existencia (id_existencia_origen),
  KEY idx_almacen_apertura_unidad (id_unidad_origen),
  KEY idx_almacen_apertura_paquete (id_paquete),
  KEY idx_almacen_apertura_lote (lote, fecha_caducidad),
  KEY idx_almacen_apertura_movimiento (id_movimiento_salida),
  KEY idx_almacen_apertura_estatus (estatus),
  CONSTRAINT fk_almacen_apertura_almacen FOREIGN KEY (id_almacen) REFERENCES erp_almacenes (id_almacen),
  CONSTRAINT fk_almacen_apertura_sku_origen FOREIGN KEY (id_sku_origen) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_almacen_apertura_existencia FOREIGN KEY (id_existencia_origen) REFERENCES erp_inventario_existencias (id_existencia_inventario),
  CONSTRAINT fk_almacen_apertura_paquete FOREIGN KEY (id_paquete) REFERENCES erp_catalogo_sku_paquetes (id_paquete)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE erp_almacen_apertura_resultados (
  id_apertura_resultado INT NOT NULL AUTO_INCREMENT,
  id_apertura_empaque INT NOT NULL,
  orden_resultado INT NOT NULL DEFAULT 0,
  id_sku_resultado BIGINT NOT NULL,
  id_componente BIGINT NULL,
  id_existencia_inventario INT NULL,
  id_almacen INT NOT NULL,
  ubicacion_id INT NULL,
  lote VARCHAR(150) NULL,
  fecha_caducidad DATE NULL,
  cantidad_esperada DECIMAL(18,6) NULL,
  cantidad_real DECIMAL(18,6) NOT NULL,
  costo_unitario DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  costo_total DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
  id_movimiento_entrada INT NULL,
  etiquetas_generadas INT NOT NULL DEFAULT 0,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_apertura_resultado),
  KEY idx_apertura_resultado_apertura (id_apertura_empaque),
  KEY idx_apertura_resultado_sku (id_sku_resultado),
  KEY idx_apertura_resultado_componente (id_componente),
  KEY idx_apertura_resultado_existencia (id_existencia_inventario),
  KEY idx_apertura_resultado_almacen (id_almacen),
  KEY idx_apertura_resultado_lote (lote, fecha_caducidad),
  KEY idx_apertura_resultado_movimiento (id_movimiento_entrada),
  CONSTRAINT fk_apertura_resultado_apertura FOREIGN KEY (id_apertura_empaque) REFERENCES erp_almacen_aperturas_empaque (id_apertura_empaque),
  CONSTRAINT fk_apertura_resultado_sku FOREIGN KEY (id_sku_resultado) REFERENCES erp_catalogo_skus (id_sku),
  CONSTRAINT fk_apertura_resultado_componente FOREIGN KEY (id_componente) REFERENCES erp_catalogo_sku_paquete_componentes (id_componente),
  CONSTRAINT fk_apertura_resultado_existencia FOREIGN KEY (id_existencia_inventario) REFERENCES erp_inventario_existencias (id_existencia_inventario),
  CONSTRAINT fk_apertura_resultado_almacen FOREIGN KEY (id_almacen) REFERENCES erp_almacenes (id_almacen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
