-- ERP Ventas/POS - Propuesta DDL Configuracion Ticket
-- Proyecto canonico: C:\xampp\htdocs\panel_de_control
-- Host canonico: http://panel.com.local/
-- Generado: 2026-07-24
-- Contrato: propuesta para revision; no ejecutar sin autorizacion APPLY.

CREATE TABLE `erp_empresa_configuracion` (
`id_empresa_configuracion` INT NOT NULL AUTO_INCREMENT,
`clave_empresa` VARCHAR(60) NOT NULL DEFAULT 'principal',
`nombre_comercial` VARCHAR(180) NOT NULL,
`razon_social` VARCHAR(220) NULL,
`rfc` VARCHAR(20) NULL,
`regimen_fiscal` VARCHAR(120) NULL,
`direccion_fiscal` TEXT NULL,
`telefono` VARCHAR(60) NULL,
`whatsapp` VARCHAR(60) NULL,
`email` VARCHAR(180) NULL,
`sitio_web` VARCHAR(220) NULL,
`logo_url` VARCHAR(500) NULL,
`leyenda_ticket_general` VARCHAR(500) NULL,
`leyenda_no_fiscal` VARCHAR(300) NULL,
`leyenda_devoluciones` VARCHAR(500) NULL,
`leyenda_garantias` VARCHAR(500) NULL,
`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa',
`creado_por` INT NULL,
`actualizado_por` INT NULL,
`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`fecha_actualizacion` DATETIME NULL,
PRIMARY KEY (`id_empresa_configuracion`),
UNIQUE KEY `idx_empresa_config_clave` (`clave_empresa`),
KEY `idx_empresa_config_estatus` (`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `erp_pos_ticket_configuracion` (
`id_ticket_configuracion` INT NOT NULL AUTO_INCREMENT,
`id_empresa_configuracion` INT NULL,
`id_almacen` INT NULL,
`id_caja` INT NULL,
`id_terminal_pos` INT NULL,
`nombre_configuracion` VARCHAR(150) NOT NULL DEFAULT 'Ticket POS principal',
`prioridad` INT NOT NULL DEFAULT 100,
`ticket_ancho_mm` VARCHAR(20) NOT NULL DEFAULT '80',
`ticket_columnas` INT NOT NULL DEFAULT 42,
`fuente` VARCHAR(40) NOT NULL DEFAULT 'monospace',
`mostrar_logo` TINYINT(1) NOT NULL DEFAULT 0,
`logo_modo` VARCHAR(30) NOT NULL DEFAULT 'texto',
`impresion_modo` VARCHAR(30) NOT NULL DEFAULT 'navegador',
`impresora_nombre_windows` VARCHAR(180) NULL,
`corte_automatico` TINYINT(1) NOT NULL DEFAULT 0,
`abrir_cajon` TINYINT(1) NOT NULL DEFAULT 0,
`copias_venta` INT NOT NULL DEFAULT 1,
`copias_devolucion` INT NOT NULL DEFAULT 1,
`margen_mm` DECIMAL(9,2) NOT NULL DEFAULT 0,
`qr_ticket` TINYINT(1) NOT NULL DEFAULT 0,
`mensaje_sucursal` VARCHAR(500) NULL,
`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa',
`creado_por` INT NULL,
`actualizado_por` INT NULL,
`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`fecha_actualizacion` DATETIME NULL,
PRIMARY KEY (`id_ticket_configuracion`),
KEY `idx_pos_ticket_empresa` (`id_empresa_configuracion`, `estatus`),
KEY `idx_pos_ticket_scope` (`id_almacen`, `id_caja`, `id_terminal_pos`, `estatus`, `prioridad`),
KEY `idx_pos_ticket_modo` (`impresion_modo`, `ticket_ancho_mm`, `estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `erp_ventas` ADD COLUMN `ticket_config_snapshot` TEXT NULL AFTER `cliente_snapshot`;
ALTER TABLE `erp_ventas` ADD KEY `idx_ventas_ticket_fecha` (`id_almacen`, `id_caja`, `fecha_venta`);