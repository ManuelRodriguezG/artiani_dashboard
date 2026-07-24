-- ERP TMS Delivery - DDL propuesto inicial
-- Documentacion IA: Codex GPT-5
-- Fecha: 2026-07-24
-- Estado: propuesta documental; no ejecutar sin respaldo externo y autorizacion explicita.
--
-- Objetivo:
-- Crear tablas propias para servicios logisticos independientes.
--
-- Regla:
-- TMS no confirma ventas, no cancela ventas, no mueve inventario y no decide garantias.
-- Los campos solicitado_por_* son referencia opcional al modulo que pidio el servicio.

CREATE TABLE IF NOT EXISTS `erp_tms_servicios` (
  `id_tms_servicio` INT NOT NULL AUTO_INCREMENT,
  `folio` VARCHAR(30) NOT NULL,
  `solicitado_por_modulo` VARCHAR(30) NOT NULL DEFAULT 'manual',
  `solicitado_por_tipo` VARCHAR(40) NOT NULL DEFAULT 'solicitud_manual',
  `solicitado_por_id` INT NULL,
  `referencia_externa` VARCHAR(80) NULL,
  `motivo_logistico` ENUM(
    'venta_inicial',
    'entrega_adicional',
    'recoleccion',
    'revision',
    'cambio_acordado',
    'cortesia_autorizada',
    'otro'
  ) NOT NULL DEFAULT 'venta_inicial',
  `id_cliente_crm` INT NULL,
  `id_direccion_crm` INT NULL,
  `cliente_nombre_snapshot` VARCHAR(180) NULL,
  `cliente_contacto_snapshot` VARCHAR(120) NULL,
  `direccion_snapshot` TEXT NULL,
  `zona_snapshot` VARCHAR(120) NULL,
  `tipo_servicio` ENUM(
    'entrega_local',
    'entrega_express',
    'entrega_programada',
    'recoleccion_cliente',
    'entrega_postventa',
    'traslado_revision',
    'visita_revision',
    'envio_tercero'
  ) NOT NULL DEFAULT 'entrega_local',
  `estatus_servicio` ENUM(
    'cotizada',
    'solicitada',
    'programada',
    'preparando',
    'lista_para_salida',
    'en_ruta',
    'entregada',
    'no_entregada',
    'reprogramada',
    'pendiente_cliente',
    'cancelada'
  ) NOT NULL DEFAULT 'solicitada',
  `estatus_cobro` ENUM(
    'incluida_cortesia',
    'cobrada',
    'por_cobrar',
    'pendiente',
    'bonificada'
  ) NOT NULL DEFAULT 'pendiente',
  `resultado_logistico` ENUM(
    'pendiente',
    'completa',
    'parcial',
    'sin_entrega',
    'cliente_recogera',
    'nuevo_intento_requerido',
    'cerrada_sin_entrega'
  ) NOT NULL DEFAULT 'pendiente',
  `prioridad` ENUM('normal', 'express', 'urgente') NOT NULL DEFAULT 'normal',
  `fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_programada` DATE NULL,
  `ventana_inicio` TIME NULL,
  `ventana_fin` TIME NULL,
  `fecha_salida` DATETIME NULL,
  `fecha_cierre` DATETIME NULL,
  `creado_por` INT NULL,
  `responsable_asignado` INT NULL,
  `observaciones` TEXT NULL,
  `estatus` ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_tms_servicio`),
  UNIQUE KEY `idx_tms_servicios_folio` (`folio`),
  KEY `idx_tms_servicios_estado` (`estatus_servicio`, `resultado_logistico`),
  KEY `idx_tms_servicios_cobro` (`estatus_cobro`),
  KEY `idx_tms_servicios_programacion` (`fecha_programada`, `ventana_inicio`),
  KEY `idx_tms_servicios_cliente` (`id_cliente_crm`),
  KEY `idx_tms_servicios_responsable` (`responsable_asignado`),
  KEY `idx_tms_servicios_origen` (`solicitado_por_modulo`, `solicitado_por_tipo`, `solicitado_por_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_tms_servicios_detalle` (
  `id_tms_servicio_detalle` INT NOT NULL AUTO_INCREMENT,
  `id_tms_servicio` INT NOT NULL,
  `referencia_item_origen` VARCHAR(80) NULL,
  `id_sku_erp` INT NULL,
  `id_inventario_unidad` INT NULL,
  `cantidad` DECIMAL(12,4) NOT NULL DEFAULT 1.0000,
  `descripcion_snapshot` VARCHAR(255) NOT NULL,
  `requiere_cuidado_especial` TINYINT(1) NOT NULL DEFAULT 0,
  `estatus_preparacion` ENUM('pendiente', 'preparado', 'parcial', 'no_aplica', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `observaciones` TEXT NULL,
  `estatus` ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_tms_servicio_detalle`),
  KEY `idx_tms_detalle_servicio` (`id_tms_servicio`),
  KEY `idx_tms_detalle_sku` (`id_sku_erp`),
  KEY `idx_tms_detalle_unidad` (`id_inventario_unidad`),
  CONSTRAINT `fk_tms_detalle_servicio`
    FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_tms_servicios_costos` (
  `id_tms_servicio_costo` INT NOT NULL AUTO_INCREMENT,
  `id_tms_servicio` INT NOT NULL,
  `precio_cobrado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `costo_estimado` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `costo_real` DECIMAL(12,2) NULL,
  `metodo_cobro` ENUM('efectivo', 'tarjeta', 'transferencia', 'plataforma', 'saldo_cliente', 'no_aplica', 'otro') NOT NULL DEFAULT 'no_aplica',
  `motivo_bonificacion` VARCHAR(180) NULL,
  `autorizado_por` INT NULL,
  `datos_snapshot` JSON NULL,
  `estatus` ENUM('activo', 'cancelado') NOT NULL DEFAULT 'activo',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL,
  PRIMARY KEY (`id_tms_servicio_costo`),
  UNIQUE KEY `idx_tms_costo_servicio` (`id_tms_servicio`),
  KEY `idx_tms_costo_autorizado` (`autorizado_por`),
  CONSTRAINT `fk_tms_costo_servicio`
    FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_tms_eventos` (
  `id_tms_evento` INT NOT NULL AUTO_INCREMENT,
  `id_tms_servicio` INT NOT NULL,
  `tipo_evento` VARCHAR(60) NOT NULL,
  `estatus_anterior` VARCHAR(40) NULL,
  `estatus_nuevo` VARCHAR(40) NULL,
  `resultado_anterior` VARCHAR(40) NULL,
  `resultado_nuevo` VARCHAR(40) NULL,
  `comentario` TEXT NULL,
  `latitud` DECIMAL(10,7) NULL,
  `longitud` DECIMAL(10,7) NULL,
  `payload_json` JSON NULL,
  `creado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tms_evento`),
  KEY `idx_tms_eventos_servicio` (`id_tms_servicio`),
  KEY `idx_tms_eventos_tipo` (`tipo_evento`),
  KEY `idx_tms_eventos_fecha` (`fecha_registro`),
  CONSTRAINT `fk_tms_eventos_servicio`
    FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `erp_tms_evidencias` (
  `id_tms_evidencia` INT NOT NULL AUTO_INCREMENT,
  `id_tms_servicio` INT NOT NULL,
  `tipo_evidencia` ENUM('foto', 'firma', 'nota', 'comprobante', 'ubicacion', 'chat_snapshot', 'otro') NOT NULL DEFAULT 'nota',
  `ruta` VARCHAR(255) NULL,
  `nombre_original` VARCHAR(180) NULL,
  `descripcion` TEXT NULL,
  `payload_json` JSON NULL,
  `estatus` ENUM('activa', 'cancelada') NOT NULL DEFAULT 'activa',
  `creado_por` INT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_cancelacion` DATETIME NULL,
  PRIMARY KEY (`id_tms_evidencia`),
  KEY `idx_tms_evidencias_servicio` (`id_tms_servicio`),
  KEY `idx_tms_evidencias_tipo` (`tipo_evidencia`),
  CONSTRAINT `fk_tms_evidencias_servicio`
    FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
