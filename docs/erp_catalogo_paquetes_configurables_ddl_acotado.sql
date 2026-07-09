-- ERP Catalogo - DDL acotado para paquetes configurables
-- Documentacion IA: Codex GPT-5
-- Fecha: 2026-06-29
-- Estado: PROPUESTA NO EJECUTADA
-- Regla: no aplicar sin respaldo externo de BD y autorizacion explicita.
-- Alcance: crear solo tablas de paquetes; no modifica recepcion variable ni otros modulos.

CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquetes` (
  `id_paquete` BIGINT NOT NULL AUTO_INCREMENT,
  `id_sku_paquete` BIGINT NOT NULL,
  `tipo_paquete` VARCHAR(30) NOT NULL DEFAULT 'simple',
  `modo_disponibilidad` VARCHAR(30) NOT NULL DEFAULT 'por_componentes',
  `permite_configuracion_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_desarmar` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_armado_almacen` TINYINT(1) NOT NULL DEFAULT 0,
  `observaciones` TEXT NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
  `creado_por` INT NULL,
  `actualizado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_paquete`),
  UNIQUE KEY `idx_catalogo_paquete_sku` (`id_sku_paquete`),
  KEY `idx_catalogo_paquete_tipo` (`tipo_paquete`),
  KEY `idx_catalogo_paquete_estatus` (`estatus`),
  CONSTRAINT `fk_catalogo_paquete_sku`
    FOREIGN KEY (`id_sku_paquete`) REFERENCES `erp_catalogo_skus` (`id_sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_componentes` (
  `id_componente` BIGINT NOT NULL AUTO_INCREMENT,
  `id_paquete` BIGINT NOT NULL,
  `id_sku_componente` BIGINT NOT NULL,
  `cantidad` DECIMAL(18,6) NOT NULL,
  `id_unidad` INT NULL,
  `factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  `orden` INT NOT NULL DEFAULT 0,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_componente`),
  KEY `idx_catalogo_paquete_componente_paquete` (`id_paquete`),
  KEY `idx_catalogo_paquete_componente_sku` (`id_sku_componente`),
  CONSTRAINT `fk_catalogo_paquete_componente_paquete`
    FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`),
  CONSTRAINT `fk_catalogo_paquete_componente_sku`
    FOREIGN KEY (`id_sku_componente`) REFERENCES `erp_catalogo_skus` (`id_sku`),
  CONSTRAINT `fk_catalogo_paquete_componente_unidad`
    FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_grupos` (
  `id_grupo` BIGINT NOT NULL AUTO_INCREMENT,
  `id_paquete` BIGINT NOT NULL,
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `min_selecciones` INT NOT NULL DEFAULT 1,
  `max_selecciones` INT NOT NULL DEFAULT 1,
  `modo_cantidad` VARCHAR(30) NOT NULL DEFAULT 'cantidad_fija',
  `cantidad_total_grupo` DECIMAL(18,6) NULL,
  `obligatorio` TINYINT(1) NOT NULL DEFAULT 1,
  `orden` INT NOT NULL DEFAULT 0,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_grupo`),
  UNIQUE KEY `idx_catalogo_paquete_grupo_codigo` (`id_paquete`, `codigo`),
  KEY `idx_catalogo_paquete_grupo_paquete` (`id_paquete`),
  CONSTRAINT `fk_catalogo_paquete_grupo_paquete`
    FOREIGN KEY (`id_paquete`) REFERENCES `erp_catalogo_sku_paquetes` (`id_paquete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `erp_catalogo_sku_paquete_grupo_opciones` (
  `id_opcion` BIGINT NOT NULL AUTO_INCREMENT,
  `id_grupo` BIGINT NOT NULL,
  `id_sku_opcion` BIGINT NOT NULL,
  `cantidad_default` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  `cantidad_minima` DECIMAL(18,6) NULL,
  `cantidad_maxima` DECIMAL(18,6) NULL,
  `id_unidad` INT NULL,
  `factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000,
  `permite_cantidad_editable` TINYINT(1) NOT NULL DEFAULT 0,
  `orden` INT NOT NULL DEFAULT 0,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_opcion`),
  KEY `idx_catalogo_paquete_opcion_grupo` (`id_grupo`),
  KEY `idx_catalogo_paquete_opcion_sku` (`id_sku_opcion`),
  CONSTRAINT `fk_catalogo_paquete_opcion_grupo`
    FOREIGN KEY (`id_grupo`) REFERENCES `erp_catalogo_sku_paquete_grupos` (`id_grupo`),
  CONSTRAINT `fk_catalogo_paquete_opcion_sku`
    FOREIGN KEY (`id_sku_opcion`) REFERENCES `erp_catalogo_skus` (`id_sku`),
  CONSTRAINT `fk_catalogo_paquete_opcion_unidad`
    FOREIGN KEY (`id_unidad`) REFERENCES `erp_catalogo_unidades` (`id_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
