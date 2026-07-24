-- ERP Ventas/POS - Venta rapida controlada
-- Proyecto canonico: C:\xampp\htdocs\panel_de_control
-- Host canonico: http://panel.com.local/
-- Generado: 2026-07-23
-- Contrato: DDL propuesto; no ejecutar sin respaldo externo y autorizacion explicita.

ALTER TABLE `erp_ventas_detalle`
  ADD COLUMN `origen_partida` VARCHAR(40) NOT NULL DEFAULT 'catalogo' AFTER `tipo_partida`;

ALTER TABLE `erp_ventas_detalle`
  ADD COLUMN `id_venta_rapida_pendiente` BIGINT NULL AFTER `origen_partida`;

ALTER TABLE `erp_ventas_detalle`
  ADD COLUMN `descripcion_manual_snapshot` TEXT NULL AFTER `descripcion`;

ALTER TABLE `erp_ventas_detalle`
  ADD COLUMN `datos_catalogo_pendiente` TEXT NULL AFTER `descripcion_manual_snapshot`;

ALTER TABLE `erp_ventas_detalle`
  ADD COLUMN `inventario_regularizacion_estado` VARCHAR(40) NULL AFTER `datos_catalogo_pendiente`;

ALTER TABLE `erp_ventas_detalle`
  ADD KEY `idx_ventas_detalle_origen_partida` (`origen_partida`, `estatus`);

ALTER TABLE `erp_ventas_detalle`
  ADD KEY `idx_ventas_detalle_venta_rapida` (`id_venta_rapida_pendiente`);

CREATE TABLE IF NOT EXISTS `erp_pos_venta_rapida_pendientes` (
  `id_venta_rapida_pendiente` BIGINT NOT NULL AUTO_INCREMENT,
  `folio` VARCHAR(50) NOT NULL,
  `id_venta` BIGINT NOT NULL,
  `id_venta_detalle` BIGINT NOT NULL,
  `folio_venta` VARCHAR(40) NOT NULL,
  `id_almacen` INT NOT NULL,
  `id_caja` INT NULL,
  `id_turno_caja` BIGINT NULL,
  `id_usuario_operador` INT NULL,
  `id_cliente_crm` BIGINT NULL,
  `cliente_snapshot` TEXT NULL,
  `descripcion_manual` TEXT NOT NULL,
  `cantidad` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `precio_unitario` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `total` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `codigo_barras` VARCHAR(180) NULL,
  `categoria_provisional` VARCHAR(180) NULL,
  `marca_provisional` VARCHAR(180) NULL,
  `proveedor_provisional` VARCHAR(180) NULL,
  `controla_inventario` TINYINT(1) NOT NULL DEFAULT 1,
  `inventario_estado` VARCHAR(40) NOT NULL DEFAULT 'pendiente_regularizacion',
  `id_sku_erp_resuelto` BIGINT NULL,
  `id_producto_erp_resuelto` BIGINT NULL,
  `id_proveedor_resuelto` INT NULL,
  `estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente_catalogo',
  `prioridad` VARCHAR(30) NOT NULL DEFAULT 'normal',
  `motivo` TEXT NULL,
  `observaciones_pos` TEXT NULL,
  `observaciones_resolucion` TEXT NULL,
  `datos_snapshot` TEXT NULL,
  `creado_por` INT NULL,
  `resuelto_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` DATETIME NULL,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_venta_rapida_pendiente`),
  UNIQUE KEY `idx_pos_vr_folio` (`folio`),
  KEY `idx_pos_vr_venta_detalle` (`id_venta`, `id_venta_detalle`),
  KEY `idx_pos_vr_estado` (`estatus`, `prioridad`, `fecha_registro`),
  KEY `idx_pos_vr_almacen` (`id_almacen`, `estatus`, `fecha_registro`),
  KEY `idx_pos_vr_operador` (`id_usuario_operador`, `fecha_registro`),
  KEY `idx_pos_vr_sku_resuelto` (`id_sku_erp_resuelto`, `estatus`),
  KEY `idx_pos_vr_codigo` (`codigo_barras`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `erp_pos_venta_rapida_eventos` (
  `id_venta_rapida_evento` BIGINT NOT NULL AUTO_INCREMENT,
  `id_venta_rapida_pendiente` BIGINT NOT NULL,
  `folio_pendiente` VARCHAR(50) NOT NULL,
  `tipo_evento` VARCHAR(80) NOT NULL,
  `estatus_anterior` VARCHAR(40) NULL,
  `estatus_nuevo` VARCHAR(40) NULL,
  `id_sku_erp` BIGINT NULL,
  `resumen` VARCHAR(255) NULL,
  `motivo` TEXT NULL,
  `datos_snapshot` TEXT NULL,
  `creado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta_rapida_evento`),
  KEY `idx_pos_vr_evt_pendiente` (`id_venta_rapida_pendiente`, `fecha_registro`),
  KEY `idx_pos_vr_evt_tipo` (`tipo_evento`, `fecha_registro`),
  KEY `idx_pos_vr_evt_sku` (`id_sku_erp`, `fecha_registro`),
  KEY `idx_pos_vr_evt_usuario` (`creado_por`, `fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
