-- ERP Garantias - DDL propuesto
-- Documentacion IA: Codex GPT-5
-- Fecha: 2026-06-27
-- Estado: PROPUESTA; NO EJECUTAR sin respaldo externo y autorizacion explicita.
--
-- Objetivo:
-- - Crear estructura formal para politicas, reglas, snapshot de venta,
--   reclamos, eventos, adjuntos y seguimiento con proveedor.
-- - No reemplaza devoluciones de Ventas; las referencia cuando la resolucion
--   comercial lo requiera.
-- - No mueve inventario; Almacen/Inventario debe ejecutar movimientos fisicos.

CREATE TABLE IF NOT EXISTS `erp_garantias_politicas` (
  `id_garantia_politica` BIGINT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(60) NOT NULL,
  `nombre` VARCHAR(180) NOT NULL,
  `descripcion` TEXT NULL,
  `tipo_garantia` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia',
  `duracion_valor` INT NOT NULL DEFAULT 0,
  `unidad_duracion` VARCHAR(20) NOT NULL DEFAULT 'dias',
  `coberturas_json` TEXT NULL,
  `requisitos_json` TEXT NULL,
  `exclusiones_json` TEXT NULL,
  `requiere_ticket` TINYINT(1) NOT NULL DEFAULT 1,
  `requiere_cliente` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_serie` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_lote` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_empaque` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_diagnostico` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_fotos` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_autorizacion_supervisor` TINYINT(1) NOT NULL DEFAULT 0,
  `requiere_validacion_proveedor` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_cambio` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_reparacion` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_devolucion_dinero` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_nota_credito` TINYINT(1) NOT NULL DEFAULT 0,
  `permite_envio_proveedor` TINYINT(1) NOT NULL DEFAULT 0,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activa',
  `creado_por` INT NULL,
  `actualizado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_garantia_politica`),
  UNIQUE KEY `idx_garantia_politica_codigo` (`codigo`),
  KEY `idx_garantia_politica_tipo_estatus` (`tipo_garantia`, `estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_garantias_politicas_reglas` (
  `id_regla_garantia` BIGINT NOT NULL AUTO_INCREMENT,
  `id_garantia_politica` BIGINT NOT NULL,
  `ambito` VARCHAR(30) NOT NULL,
  `id_referencia` BIGINT NOT NULL DEFAULT 0,
  `prioridad` INT NOT NULL DEFAULT 100,
  `canal` VARCHAR(40) NULL,
  `id_almacen` INT NULL,
  `vigencia_desde` DATE NULL,
  `vigencia_hasta` DATE NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activa',
  `observaciones` TEXT NULL,
  `creado_por` INT NULL,
  `actualizado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_regla_garantia`),
  KEY `idx_garantia_regla_politica` (`id_garantia_politica`, `estatus`),
  KEY `idx_garantia_regla_resolver` (`ambito`, `id_referencia`, `estatus`, `prioridad`),
  KEY `idx_garantia_regla_canal_almacen` (`canal`, `id_almacen`, `estatus`),
  CONSTRAINT `fk_garantia_regla_politica`
    FOREIGN KEY (`id_garantia_politica`) REFERENCES `erp_garantias_politicas` (`id_garantia_politica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_ventas_detalle_garantias` (
  `id_venta_detalle_garantia` BIGINT NOT NULL AUTO_INCREMENT,
  `id_venta` BIGINT NOT NULL,
  `id_venta_detalle` BIGINT NOT NULL,
  `id_producto_erp` BIGINT NULL,
  `id_sku_erp` BIGINT NULL,
  `id_garantia_politica` BIGINT NULL,
  `id_regla_garantia` BIGINT NULL,
  `tipo_garantia_snapshot` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia',
  `nombre_politica_snapshot` VARCHAR(180) NULL,
  `duracion_valor_snapshot` INT NOT NULL DEFAULT 0,
  `unidad_duracion_snapshot` VARCHAR(20) NOT NULL DEFAULT 'dias',
  `coberturas_snapshot` TEXT NULL,
  `requisitos_snapshot` TEXT NULL,
  `exclusiones_snapshot` TEXT NULL,
  `resumen_ticket` VARCHAR(500) NULL,
  `fecha_inicio` DATE NULL,
  `fecha_vencimiento` DATE NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'vigente',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_venta_detalle_garantia`),
  UNIQUE KEY `idx_venta_detalle_garantia_detalle` (`id_venta_detalle`),
  KEY `idx_venta_detalle_garantia_venta` (`id_venta`, `estatus`),
  KEY `idx_venta_detalle_garantia_sku` (`id_sku_erp`, `estatus`),
  KEY `idx_venta_detalle_garantia_vencimiento` (`fecha_vencimiento`, `estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_garantias_reclamos` (
  `id_reclamo_garantia` BIGINT NOT NULL AUTO_INCREMENT,
  `folio` VARCHAR(40) NOT NULL,
  `id_venta` BIGINT NULL,
  `id_venta_detalle` BIGINT NULL,
  `id_venta_detalle_garantia` BIGINT NULL,
  `id_cliente` BIGINT NULL,
  `id_producto_erp` BIGINT NULL,
  `id_sku_erp` BIGINT NULL,
  `id_inventario_unidad` BIGINT NULL,
  `id_garantia_politica` BIGINT NULL,
  `id_devolucion` BIGINT NULL,
  `id_proveedor` BIGINT NULL,
  `tipo_garantia` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia',
  `motivo` VARCHAR(180) NULL,
  `descripcion` TEXT NULL,
  `diagnostico` TEXT NULL,
  `elegibilidad` VARCHAR(30) NOT NULL DEFAULT 'pendiente',
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador',
  `decision` VARCHAR(40) NULL,
  `decision_inventario` VARCHAR(40) NULL,
  `fecha_venta` DATETIME NULL,
  `fecha_vencimiento` DATE NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` DATETIME NULL,
  `creado_por` INT NULL,
  `autorizado_por` INT NULL,
  `resuelto_por` INT NULL,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_reclamo_garantia`),
  UNIQUE KEY `idx_garantia_reclamo_folio` (`folio`),
  KEY `idx_garantia_reclamo_venta` (`id_venta`, `id_venta_detalle`),
  KEY `idx_garantia_reclamo_sku` (`id_sku_erp`, `estatus`),
  KEY `idx_garantia_reclamo_unidad` (`id_inventario_unidad`),
  KEY `idx_garantia_reclamo_cliente` (`id_cliente`, `estatus`),
  KEY `idx_garantia_reclamo_proveedor` (`id_proveedor`, `estatus`),
  KEY `idx_garantia_reclamo_estatus_fecha` (`estatus`, `fecha_registro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_garantias_reclamos_eventos` (
  `id_evento_garantia` BIGINT NOT NULL AUTO_INCREMENT,
  `id_reclamo_garantia` BIGINT NOT NULL,
  `tipo_evento` VARCHAR(40) NOT NULL,
  `estatus_anterior` VARCHAR(30) NULL,
  `estatus_nuevo` VARCHAR(30) NULL,
  `decision` VARCHAR(40) NULL,
  `comentario` TEXT NULL,
  `datos_json` TEXT NULL,
  `creado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evento_garantia`),
  KEY `idx_garantia_evento_reclamo` (`id_reclamo_garantia`, `fecha_registro`),
  KEY `idx_garantia_evento_tipo` (`tipo_evento`, `fecha_registro`),
  CONSTRAINT `fk_garantia_evento_reclamo`
    FOREIGN KEY (`id_reclamo_garantia`) REFERENCES `erp_garantias_reclamos` (`id_reclamo_garantia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_garantias_adjuntos` (
  `id_adjunto_garantia` BIGINT NOT NULL AUTO_INCREMENT,
  `id_reclamo_garantia` BIGINT NOT NULL,
  `tipo_adjunto` VARCHAR(40) NOT NULL DEFAULT 'evidencia',
  `ruta` VARCHAR(500) NOT NULL,
  `nombre_original` VARCHAR(255) NULL,
  `mime_type` VARCHAR(120) NULL,
  `tamano_bytes` BIGINT NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'activo',
  `creado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto_garantia`),
  KEY `idx_garantia_adjunto_reclamo` (`id_reclamo_garantia`, `estatus`),
  CONSTRAINT `fk_garantia_adjunto_reclamo`
    FOREIGN KEY (`id_reclamo_garantia`) REFERENCES `erp_garantias_reclamos` (`id_reclamo_garantia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `erp_garantias_proveedor_seguimiento` (
  `id_garantia_proveedor` BIGINT NOT NULL AUTO_INCREMENT,
  `id_reclamo_garantia` BIGINT NOT NULL,
  `id_proveedor` BIGINT NOT NULL,
  `folio_proveedor` VARCHAR(120) NULL,
  `estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente_envio',
  `decision_proveedor` VARCHAR(40) NULL,
  `fecha_envio` DATETIME NULL,
  `fecha_respuesta` DATETIME NULL,
  `observaciones` TEXT NULL,
  `creado_por` INT NULL,
  `actualizado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_garantia_proveedor`),
  KEY `idx_garantia_proveedor_reclamo` (`id_reclamo_garantia`, `estatus`),
  KEY `idx_garantia_proveedor_proveedor` (`id_proveedor`, `estatus`, `fecha_registro`),
  CONSTRAINT `fk_garantia_proveedor_reclamo`
    FOREIGN KEY (`id_reclamo_garantia`) REFERENCES `erp_garantias_reclamos` (`id_reclamo_garantia`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permisos propuestos para SeguridadEsquema::permisosBaseERP()
-- NO insertar manualmente hasta autorizar semillas:
-- garantias.ver
-- garantias.politicas
-- garantias.reclamos.crear
-- garantias.reclamos.resolver
-- garantias.autorizar
-- garantias.adjuntos
-- garantias.reportes
