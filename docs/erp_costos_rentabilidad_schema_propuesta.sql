-- ERP Costos/Rentabilidad - esquema propuesto, NO ejecutado
-- Fecha: 2026-06-23
-- Requiere respaldo externo y autorizacion explicita antes de aplicarse.

CREATE TABLE IF NOT EXISTS `erp_rentabilidad_escenarios` (
  `id_escenario` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `clave` VARCHAR(60) NOT NULL,
  `nombre` VARCHAR(120) NOT NULL,
  `canal` ENUM('menudeo','mayoreo','alianza','liquidacion','otro') NOT NULL DEFAULT 'menudeo',
  `descuento_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `gasto_operativo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `comision_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `margen_objetivo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `descripcion` TEXT NULL,
  `estatus` ENUM('borrador','activo','inactivo') NOT NULL DEFAULT 'borrador',
  `creado_por` INT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_escenario`),
  UNIQUE KEY `idx_erp_rentabilidad_escenarios_clave` (`clave`),
  KEY `idx_erp_rentabilidad_escenarios_canal` (`canal`, `estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_rentabilidad_snapshots` (
  `id_snapshot` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `folio` VARCHAR(40) NOT NULL,
  `id_escenario` INT UNSIGNED NULL,
  `canal` VARCHAR(40) NOT NULL,
  `descuento_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `gasto_operativo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `comision_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `margen_objetivo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0,
  `filtros_json` JSON NULL,
  `resumen_json` JSON NULL,
  `estatus` ENUM('borrador','cerrado','cancelado') NOT NULL DEFAULT 'borrador',
  `creado_por` INT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_snapshot`),
  UNIQUE KEY `idx_erp_rentabilidad_snapshots_folio` (`folio`),
  KEY `idx_erp_rentabilidad_snapshots_escenario` (`id_escenario`),
  KEY `idx_erp_rentabilidad_snapshots_fecha` (`fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_rentabilidad_snapshot_detalle` (
  `id_snapshot_detalle` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_snapshot` BIGINT UNSIGNED NOT NULL,
  `id_sku` INT NOT NULL,
  `sku` VARCHAR(120) NOT NULL,
  `producto` VARCHAR(255) NOT NULL,
  `costo_real_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `origen_costo` VARCHAR(40) NOT NULL,
  `precio_venta_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `precio_escenario_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `margen_bruto_pct` DECIMAL(9,4) NULL,
  `utilidad_bruta` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `gastos_estimados` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `utilidad_estimada` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `utilidad_estimada_pct` DECIMAL(9,4) NULL,
  `precio_minimo_rentable` DECIMAL(18,6) NULL,
  `cantidad_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `disponible_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `valor_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `riesgo_clave` VARCHAR(40) NOT NULL,
  `riesgo_tipo` VARCHAR(20) NOT NULL,
  `hallazgos_json` JSON NULL,
  `evidencia_json` JSON NULL,
  `recomendacion` TEXT NULL,
  PRIMARY KEY (`id_snapshot_detalle`),
  KEY `idx_erp_rentabilidad_detalle_snapshot` (`id_snapshot`),
  KEY `idx_erp_rentabilidad_detalle_sku` (`id_sku`),
  KEY `idx_erp_rentabilidad_detalle_riesgo` (`riesgo_clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_rentabilidad_recomendaciones` (
  `id_recomendacion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_snapshot_detalle` BIGINT UNSIGNED NULL,
  `id_sku` INT NOT NULL,
  `sku` VARCHAR(120) NOT NULL,
  `canal` VARCHAR(40) NOT NULL,
  `precio_actual_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `precio_recomendado_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0,
  `motivo` VARCHAR(120) NOT NULL,
  `estatus` ENUM('pendiente','aprobada','rechazada','aplicada','cancelada') NOT NULL DEFAULT 'pendiente',
  `comentario` TEXT NULL,
  `creado_por` INT NULL,
  `resuelto_por` INT NULL,
  `fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` DATETIME NULL,
  PRIMARY KEY (`id_recomendacion`),
  KEY `idx_erp_rentabilidad_recomendaciones_sku` (`id_sku`, `estatus`),
  KEY `idx_erp_rentabilidad_recomendaciones_snapshot` (`id_snapshot_detalle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
