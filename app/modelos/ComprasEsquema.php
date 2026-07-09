<?php

class ComprasEsquema extends DBSchema {

    public function planActualizarOrdenCompra($ejecutar = false) {
        $plan = array();

        $plan[] = $this->modificarColumna("erp_compras_solicitudes", "estatus", "ENUM('borrador','pendiente','aprobada','rechazada','orden_generada','cancelada') NOT NULL DEFAULT 'borrador'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "fecha_requerida", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "id_almacen_destino", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "prioridad", "VARCHAR(20) NOT NULL DEFAULT 'normal'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "solicitado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "aprobado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "fecha_aprobacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "fecha_cancelacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "subtotal_estimado", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_estatus", "KEY `idx_solicitud_estatus` (`estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_proveedor", "KEY `idx_solicitud_proveedor` (`id_proveedor`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_fecha_solicitud", "KEY `idx_solicitud_fecha_solicitud` (`fecha_solicitud`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_fecha_requerida", "KEY `idx_solicitud_fecha_requerida` (`fecha_requerida`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_almacen_destino", "KEY `idx_solicitud_almacen_destino` (`id_almacen_destino`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes", "idx_solicitud_solicitante", "KEY `idx_solicitud_solicitado_por` (`solicitado_por`)", $ejecutar);

        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes_detalle", "id_sku_erp", "BIGINT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes_detalle", "id_sku_proveedor", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes_detalle", "sku", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_solicitudes_detalle", "nombre_producto", "VARCHAR(255) NULL", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_solicitudes_detalle", "cantidad", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_solicitudes_detalle", "costo_estimado", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_solicitudes_detalle", "subtotal", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes_detalle", "idx_solicitud_detalle_sku", "KEY `idx_solicitud_detalle_sku` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_solicitudes_detalle", "idx_solicitud_detalle_solicitud", "KEY `idx_solicitud_detalle_solicitud` (`id_solicitud`)", $ejecutar);

        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "folio_proveedor", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "id_almacen_destino", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "solicitante", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "contacto_recepcion", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "telefono_recepcion", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "direccion_entrega", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "descuento_global_productos", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "saldo_pendiente", "DECIMAL(12,2) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "moneda", "VARCHAR(10) NOT NULL DEFAULT 'MXN'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "tipo_cambio", "DECIMAL(18,6) NOT NULL DEFAULT 1", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "creado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "enviado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "fecha_envio", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes", "origen", "VARCHAR(30) NOT NULL DEFAULT 'solicitud'", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes", "idx_orden_solicitud", "KEY `idx_orden_solicitud` (`id_solicitud`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes", "idx_orden_estatus", "KEY `idx_orden_estatus` (`estatus`)", $ejecutar);

        // id_producto representa el producto maestro ERP; id_sku_erp identifica la unidad comprable.
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "id_producto_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "id_solicitud_detalle", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "id_sku_proveedor", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "unidad", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "cantidad", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "costo_unitario", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "costo_unitario_incluye_impuesto", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "porcentaje_impuesto", "DECIMAL(10,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "subtotal", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "descuento", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "total", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->modificarColumna("erp_compras_ordenes_detalle", "cantidad_recibida", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "costo_compra", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "costo_antes_impuesto", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "precio_venta", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "margen_actual", "DECIMAL(10,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "utilidad_actual", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "precio_sugerido", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "margen_nuevo", "DECIMAL(10,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "utilidad_nueva", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "datos_fiscales_json", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_detalle", "evidencia_costo_json", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_detalle", "idx_orden_detalle_solicitud", "KEY `idx_orden_detalle_solicitud` (`id_solicitud_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_detalle", "idx_orden_detalle_sku_proveedor", "KEY `idx_orden_detalle_sku_proveedor` (`id_sku_proveedor`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_ordenes_pagos", array(
            "`id_pago_orden` INT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`metodo_pago` VARCHAR(80) NOT NULL",
            "`estado_pago` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`referencia` VARCHAR(150) NULL",
            "`monto` DECIMAL(12,2) NOT NULL DEFAULT 0",
            "`fecha_pago` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`cancelado_por` INT NULL",
            "`fecha_cancelacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_pago_orden`)",
            "KEY `idx_pagos_orden` (`id_orden_compra`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_pagos", "fecha_pago", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_pagos", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_pagos", "creado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_pagos", "cancelado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_pagos", "fecha_cancelacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_pagos", "idx_pagos_orden_estado", "KEY `idx_pagos_orden_estado` (`id_orden_compra`, `estado_pago`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_ordenes_notas_credito", array(
            "`id_nota_credito_orden` INT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`referencia` VARCHAR(150) NULL",
            "`monto` DECIMAL(12,2) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
            "`fecha_aplicacion` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`cancelado_por` INT NULL",
            "`fecha_cancelacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_nota_credito_orden`)",
            "KEY `idx_notas_orden` (`id_orden_compra`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "estatus", "VARCHAR(30) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "fecha_aplicacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "creado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "cancelado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_notas_credito", "fecha_cancelacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_notas_credito", "idx_notas_orden_estatus", "KEY `idx_notas_orden_estatus` (`id_orden_compra`, `estatus`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_ordenes_adjuntos", array(
            "`id_adjunto_orden` INT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`tipo_documento` VARCHAR(80) NOT NULL",
            "`referencia` VARCHAR(150) NULL",
            "`archivo_nombre` VARCHAR(255) NOT NULL",
            "`archivo_ruta` VARCHAR(255) NULL",
            "`archivo_tipo` VARCHAR(120) NULL",
            "`archivo_tamano` INT NULL",
            "`archivo_hash` CHAR(64) NULL",
            "`observaciones` TEXT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`creado_por` INT NULL",
            "`cancelado_por` INT NULL",
            "`fecha_cancelacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_adjunto_orden`)",
            "KEY `idx_adjuntos_orden` (`id_orden_compra`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_adjuntos", "archivo_hash", "CHAR(64) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_adjuntos", "estatus", "VARCHAR(30) NOT NULL DEFAULT 'activo'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_adjuntos", "creado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_adjuntos", "cancelado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_adjuntos", "fecha_cancelacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_adjuntos", "idx_adjuntos_orden_estatus", "KEY `idx_adjuntos_orden_estatus` (`id_orden_compra`, `estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_adjuntos", "idx_adjuntos_orden_hash", "KEY `idx_adjuntos_orden_hash` (`id_orden_compra`, `archivo_hash`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_documentos_fiscales", array(
            "`id_documento_fiscal` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`uuid` VARCHAR(50) NULL",
            "`version_cfdi` VARCHAR(10) NULL",
            "`serie` VARCHAR(50) NULL",
            "`folio` VARCHAR(80) NULL",
            "`fecha_emision` DATETIME NULL",
            "`rfc_emisor` VARCHAR(20) NULL",
            "`nombre_emisor` VARCHAR(255) NULL",
            "`rfc_receptor` VARCHAR(20) NULL",
            "`nombre_receptor` VARCHAR(255) NULL",
            "`moneda` VARCHAR(10) NULL",
            "`tipo_cambio` DECIMAL(18,6) NOT NULL DEFAULT 1",
            "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`archivo_nombre` VARCHAR(255) NOT NULL",
            "`archivo_ruta` VARCHAR(255) NOT NULL",
            "`archivo_hash` CHAR(64) NOT NULL",
            "`estatus_conciliacion` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_documento_fiscal`)",
            "UNIQUE KEY `idx_documento_fiscal_uuid` (`uuid`)",
            "UNIQUE KEY `idx_documento_fiscal_hash` (`archivo_hash`)",
            "KEY `idx_documento_fiscal_orden` (`id_orden_compra`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_documentos_fiscales_conceptos", array(
            "`id_documento_concepto` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_documento_fiscal` BIGINT NOT NULL",
            "`id_orden_detalle` INT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_sku_proveedor` BIGINT NULL",
            "`no_identificacion` VARCHAR(150) NULL",
            "`descripcion` VARCHAR(500) NOT NULL",
            "`clave_producto_sat` VARCHAR(20) NULL",
            "`clave_unidad_sat` VARCHAR(20) NULL",
            "`unidad` VARCHAR(80) NULL",
            "`objeto_impuesto` VARCHAR(10) NULL",
            "`cantidad` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`valor_unitario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`importe` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`iva_porcentaje` DECIMAL(8,4) NOT NULL DEFAULT 0",
            "`ieps_porcentaje` DECIMAL(8,4) NOT NULL DEFAULT 0",
            "`resultado_conciliacion` VARCHAR(30) NOT NULL DEFAULT 'sin_coincidencia'",
            "`resuelto_por` INT NULL",
            "`fecha_resolucion` DATETIME NULL",
            "`observaciones_conciliacion` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_documento_concepto`)",
            "KEY `idx_documento_concepto_documento` (`id_documento_fiscal`)",
            "KEY `idx_documento_concepto_sku` (`id_sku_erp`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_documentos_fiscales_conceptos", "id_sku_proveedor", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_documentos_fiscales_conceptos", "resuelto_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_documentos_fiscales_conceptos", "fecha_resolucion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_documentos_fiscales_conceptos", "observaciones_conciliacion", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_documentos_fiscales_conceptos", "idx_documento_concepto_detalle", "KEY `idx_documento_concepto_detalle` (`id_orden_detalle`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_compras_ordenes_productos_atencion", array(
            "`id_producto_atencion` INT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`id_solicitud` INT NULL",
            "`id_proveedor` INT NULL",
            "`id_producto` INT NULL",
            "`id_producto_proveedor` INT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_sku_proveedor` BIGINT NULL",
            "`id_orden_detalle` INT NULL",
            "`id_documento_fiscal` BIGINT NULL",
            "`sku` VARCHAR(150) NULL",
            "`nombre_producto` VARCHAR(255) NOT NULL",
            "`cantidad_solicitada` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_comprada` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`motivo` VARCHAR(80) NOT NULL DEFAULT 'no_incluido_xml'",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_producto_atencion`)",
            "KEY `idx_atencion_orden` (`id_orden_compra`)",
            "KEY `idx_atencion_proveedor` (`id_proveedor`)",
            "KEY `idx_atencion_sku` (`sku`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_orden_compra", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_solicitud", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_producto", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_producto_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_sku_erp", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_sku_proveedor", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_orden_detalle", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "id_documento_fiscal", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "sku", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "nombre_producto", "VARCHAR(255) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "cantidad_solicitada", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "cantidad_comprada", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "motivo", "VARCHAR(80) NOT NULL DEFAULT 'no_incluido_xml'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_compras_ordenes_productos_atencion", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_productos_atencion", "idx_atencion_orden_detalle", "KEY `idx_atencion_orden_detalle` (`id_orden_compra`, `id_orden_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_compras_ordenes_productos_atencion", "idx_atencion_sku_erp", "KEY `idx_atencion_sku_erp` (`id_sku_erp`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_proveedores_listas_productos_revision", array(
            "`id_revision_lista_producto` INT NOT NULL AUTO_INCREMENT",
            "`id_proveedor` INT NULL",
            "`id_orden_compra` INT NULL",
            "`id_producto` INT NULL",
            "`sku` VARCHAR(150) NULL",
            "`nombre_producto` VARCHAR(255) NOT NULL",
            "`motivo` VARCHAR(80) NOT NULL DEFAULT 'no_existe_lista_proveedor'",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_revision_lista_producto`)",
            "KEY `idx_revision_lista_proveedor` (`id_proveedor`)",
            "KEY `idx_revision_lista_sku` (`sku`)",
            "KEY `idx_revision_lista_orden` (`id_orden_compra`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "id_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "id_orden_compra", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "id_producto", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "sku", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "nombre_producto", "VARCHAR(255) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "motivo", "VARCHAR(80) NOT NULL DEFAULT 'no_existe_lista_proveedor'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_proveedores_listas_productos_revision", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de compras ejecutado" : "Plan de compras generado en dry-run",
            "depurar" => $plan
        );
    }
}
