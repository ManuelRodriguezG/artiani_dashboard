-- ERP Inventario - Conteos fisicos y reservas basicas
-- Propuesta de esquema. NO ejecutar sin respaldo externo y autorizacion.
-- Fecha: 2026-06-22

CREATE TABLE IF NOT EXISTS erp_inventario_conteos (
  id_conteo_inventario BIGINT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(60) NOT NULL,
  id_almacen INT NOT NULL,
  ubicacion_id INT NULL,
  tipo_conteo VARCHAR(30) NOT NULL DEFAULT 'ciclico',
  estatus VARCHAR(30) NOT NULL DEFAULT 'borrador',
  fecha_programada DATE NULL,
  fecha_inicio DATETIME NULL,
  fecha_cierre DATETIME NULL,
  creado_por INT NULL,
  validado_por INT NULL,
  cerrado_por INT NULL,
  referencia_ajuste VARCHAR(150) NULL,
  observaciones TEXT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_conteo_inventario),
  UNIQUE KEY uk_inv_conteo_folio (folio),
  KEY idx_inv_conteo_almacen (id_almacen),
  KEY idx_inv_conteo_ubicacion (ubicacion_id),
  KEY idx_inv_conteo_estatus (estatus),
  KEY idx_inv_conteo_fecha (fecha_programada, fecha_inicio, fecha_cierre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_inventario_conteos_detalle (
  id_conteo_detalle BIGINT NOT NULL AUTO_INCREMENT,
  id_conteo_inventario BIGINT NOT NULL,
  id_existencia_inventario INT NULL,
  codigo_existencia VARCHAR(120) NULL,
  id_producto INT NOT NULL,
  id_sku_erp BIGINT NOT NULL,
  id_almacen INT NOT NULL,
  ubicacion_id INT NULL,
  ubicacion VARCHAR(150) NULL,
  lote VARCHAR(150) NULL,
  fecha_caducidad DATE NULL,
  cantidad_sistema DECIMAL(18,6) NOT NULL DEFAULT 0,
  cantidad_fisica DECIMAL(18,6) NULL,
  diferencia DECIMAL(18,6) NOT NULL DEFAULT 0,
  costo_promedio DECIMAL(18,6) NOT NULL DEFAULT 0,
  costo_diferencia DECIMAL(18,6) NOT NULL DEFAULT 0,
  motivo_diferencia VARCHAR(60) NULL,
  estatus VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  contado_por INT NULL,
  fecha_conteo DATETIME NULL,
  id_movimiento_inventario INT NULL,
  observaciones TEXT NULL,
  fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_conteo_detalle),
  KEY idx_inv_conteo_det_conteo (id_conteo_inventario),
  KEY idx_inv_conteo_det_existencia (id_existencia_inventario),
  KEY idx_inv_conteo_det_sku (id_sku_erp),
  KEY idx_inv_conteo_det_almacen (id_almacen),
  KEY idx_inv_conteo_det_estatus (estatus),
  KEY idx_inv_conteo_det_mov (id_movimiento_inventario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS erp_inventario_reservas (
  id_reserva_inventario BIGINT NOT NULL AUTO_INCREMENT,
  folio VARCHAR(80) NOT NULL,
  origen_tipo VARCHAR(60) NOT NULL,
  origen_id BIGINT NULL,
  origen_detalle_id BIGINT NULL,
  id_existencia_inventario INT NOT NULL,
  codigo_existencia VARCHAR(120) NULL,
  id_producto INT NOT NULL,
  id_sku_erp BIGINT NOT NULL,
  id_almacen INT NOT NULL,
  ubicacion_id INT NULL,
  lote VARCHAR(150) NULL,
  fecha_caducidad DATE NULL,
  cantidad_reservada DECIMAL(18,6) NOT NULL,
  cantidad_consumida DECIMAL(18,6) NOT NULL DEFAULT 0,
  cantidad_liberada DECIMAL(18,6) NOT NULL DEFAULT 0,
  estatus VARCHAR(30) NOT NULL DEFAULT 'activa',
  fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_vencimiento DATETIME NULL,
  fecha_cierre DATETIME NULL,
  creado_por INT NULL,
  cerrado_por INT NULL,
  observaciones TEXT NULL,
  fecha_actualizacion DATETIME NULL,
  PRIMARY KEY (id_reserva_inventario),
  UNIQUE KEY uk_inv_reserva_folio (folio),
  KEY idx_inv_reserva_origen (origen_tipo, origen_id, origen_detalle_id),
  KEY idx_inv_reserva_existencia (id_existencia_inventario),
  KEY idx_inv_reserva_sku (id_sku_erp),
  KEY idx_inv_reserva_almacen (id_almacen),
  KEY idx_inv_reserva_estatus (estatus),
  KEY idx_inv_reserva_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- FKs propuestas para fase de aplicacion:
-- ALTER TABLE erp_inventario_conteos
--   ADD CONSTRAINT fk_inv_conteo_almacen FOREIGN KEY (id_almacen) REFERENCES erp_almacenes (id_almacen);
--
-- ALTER TABLE erp_inventario_conteos_detalle
--   ADD CONSTRAINT fk_inv_conteo_det_conteo FOREIGN KEY (id_conteo_inventario) REFERENCES erp_inventario_conteos (id_conteo_inventario),
--   ADD CONSTRAINT fk_inv_conteo_det_existencia FOREIGN KEY (id_existencia_inventario) REFERENCES erp_inventario_existencias (id_existencia_inventario);
--
-- ALTER TABLE erp_inventario_reservas
--   ADD CONSTRAINT fk_inv_reserva_existencia FOREIGN KEY (id_existencia_inventario) REFERENCES erp_inventario_existencias (id_existencia_inventario);
