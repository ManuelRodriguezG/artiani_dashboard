<?php

class VentasErpEsquema extends DBSchema {

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: preparar el esquema ERP nuevo para Ventas/POS/Pedidos sin depender de tablas legacy ecommerce.
     * Impacto: genera un plan DDL auditable; no ejecuta cambios salvo que el controlador/modelo lo invoque con autorizacion.
     * Contrato: con $ejecutar=false solo devuelve SQL propuesto y estado de tablas existentes.
     */
    public function planActualizarVentasPos($ejecutar = false, $alcance = "base") {
        $plan = array();
        $alcance = $alcance === "base" ? "base" : "expandido";

        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_cajas", array(
            "`id_caja` INT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(40) NOT NULL",
            "`nombre` VARCHAR(120) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`permite_efectivo` TINYINT(1) NOT NULL DEFAULT 1",
            "`permite_tarjeta` TINYINT(1) NOT NULL DEFAULT 1",
            "`permite_transferencia` TINYINT(1) NOT NULL DEFAULT 1",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_caja`)",
            "UNIQUE KEY `idx_pos_caja_codigo` (`codigo`)",
            "KEY `idx_pos_caja_almacen` (`id_almacen`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_terminales", array(
            "`id_terminal_pos` INT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(60) NOT NULL",
            "`nombre` VARCHAR(150) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_caja` INT NULL",
            "`identificador_terminal` VARCHAR(180) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_terminal_pos`)",
            "UNIQUE KEY `idx_pos_terminal_codigo` (`codigo`)",
            "KEY `idx_pos_terminal_almacen` (`id_almacen`, `estatus`)",
            "KEY `idx_pos_terminal_caja` (`id_caja`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_usuarios_cajas", array(
            "`id_usuario_caja` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_usuario` INT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_caja` INT NOT NULL",
            "`id_terminal_pos` INT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`prioridad` INT NOT NULL DEFAULT 1",
            "`fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_fin` DATETIME NULL",
            "`creado_por` INT NULL",
            "`observaciones` TEXT NULL",
            "PRIMARY KEY (`id_usuario_caja`)",
            "KEY `idx_pos_usuario_caja_usuario` (`id_usuario`, `estatus`)",
            "KEY `idx_pos_usuario_caja_almacen` (`id_almacen`, `id_caja`, `estatus`)",
            "KEY `idx_pos_usuario_caja_terminal` (`id_terminal_pos`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_turnos", array(
            "`id_turno_caja` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_caja` INT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_usuario_apertura` INT NULL",
            "`id_usuario_cierre` INT NULL",
            "`monto_inicial` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`monto_esperado` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`monto_contado` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`diferencia` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'abierto'",
            "`fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_cierre` DATETIME NULL",
            "`observaciones_apertura` TEXT NULL",
            "`observaciones_cierre` TEXT NULL",
            "PRIMARY KEY (`id_turno_caja`)",
            "UNIQUE KEY `idx_pos_turno_folio` (`folio`)",
            "KEY `idx_pos_turno_caja_estado` (`id_caja`, `estatus`)",
            "KEY `idx_pos_turno_almacen_fecha` (`id_almacen`, `fecha_apertura`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_movimientos_caja", array(
            "`id_movimiento_caja` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_turno_caja` BIGINT NOT NULL",
            "`tipo` VARCHAR(20) NOT NULL",
            "`motivo` VARCHAR(120) NOT NULL",
            "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`referencia` VARCHAR(150) NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_movimiento_caja`)",
            "KEY `idx_pos_movimiento_turno` (`id_turno_caja`, `tipo`)",
            "KEY `idx_pos_movimiento_fecha` (`fecha_registro`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas", array(
            "`id_venta` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`canal` VARCHAR(30) NOT NULL DEFAULT 'pos'",
            "`tipo_documento` VARCHAR(30) NOT NULL DEFAULT 'venta'",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`id_almacen` INT NOT NULL",
            "`id_caja` INT NULL",
            "`id_turno_caja` BIGINT NULL",
            "`id_cliente` INT NULL",
            "`cliente_nombre_publico` VARCHAR(255) NULL",
            "`cliente_identificador_publico` VARCHAR(180) NULL",
            "`id_lista_precio` INT NULL",
            "`lista_precio_nombre_snapshot` VARCHAR(150) NULL",
            "`segmento_cliente_snapshot` VARCHAR(150) NULL",
            "`beneficios_snapshot` TEXT NULL",
            "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
            "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`impuestos_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`pagado_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`saldo_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`anticipo_minimo` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`fecha_vencimiento` DATETIME NULL",
            "`politica_apartado_snapshot` TEXT NULL",
            "`fecha_venta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_entrega_compromiso` DATETIME NULL",
            "`creado_por` INT NULL",
            "`cancelado_por` INT NULL",
            "`motivo_cancelacion` TEXT NULL",
            "`fecha_cancelacion` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_venta`)",
            "UNIQUE KEY `idx_ventas_folio` (`folio`)",
            "KEY `idx_ventas_canal_estado_fecha` (`canal`, `estatus`, `fecha_venta`)",
            "KEY `idx_ventas_almacen_fecha` (`id_almacen`, `fecha_venta`)",
            "KEY `idx_ventas_cliente_fecha` (`id_cliente`, `fecha_venta`)",
            "KEY `idx_ventas_lista_precio` (`id_lista_precio`, `fecha_venta`)",
            "KEY `idx_ventas_turno` (`id_turno_caja`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_detalle", array(
            "`id_venta_detalle` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_venta` BIGINT NOT NULL",
            "`renglon` INT NOT NULL DEFAULT 1",
            "`id_producto_erp` BIGINT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`sku` VARCHAR(150) NULL",
            "`descripcion` VARCHAR(500) NOT NULL",
            "`tipo_partida` VARCHAR(30) NOT NULL DEFAULT 'producto'",
            "`controla_inventario` TINYINT(1) NOT NULL DEFAULT 0",
            "`modo_salida` VARCHAR(40) NOT NULL DEFAULT 'sin_inventario'",
            "`cantidad_venta` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_venta` VARCHAR(40) NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_base` VARCHAR(40) NULL",
            "`precio_unitario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_unitario_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_aplicado` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`id_lista_precio` INT NULL",
            "`lista_precio_snapshot` VARCHAR(150) NULL",
            "`id_promocion` INT NULL",
            "`regla_precio_origen` VARCHAR(80) NULL",
            "`autorizacion_precio` VARCHAR(150) NULL",
            "`descuento` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`impuestos` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_venta_detalle`)",
            "KEY `idx_ventas_detalle_venta` (`id_venta`, `renglon`)",
            "KEY `idx_ventas_detalle_sku` (`id_sku_erp`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_detalle_inventario", array(
            "`id_venta_detalle_inventario` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_venta` BIGINT NOT NULL",
            "`id_venta_detalle` BIGINT NOT NULL",
            "`id_existencia_inventario` BIGINT NULL",
            "`id_inventario_unidad` BIGINT NULL",
            "`id_reserva_inventario` BIGINT NULL",
            "`id_movimiento_inventario` BIGINT NULL",
            "`id_almacen` INT NOT NULL",
            "`lote` VARCHAR(120) NULL",
            "`fecha_caducidad` DATE NULL",
            "`ubicacion_id` INT NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_unidad_antes` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_unidad_despues` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estado_unidad_despues` VARCHAR(30) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'asignada'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_venta_detalle_inventario`)",
            "KEY `idx_venta_inv_venta` (`id_venta`)",
            "KEY `idx_venta_inv_detalle` (`id_venta_detalle`)",
            "KEY `idx_venta_inv_existencia` (`id_existencia_inventario`)",
            "KEY `idx_venta_inv_unidad` (`id_inventario_unidad`)",
            "KEY `idx_venta_inv_reserva` (`id_reserva_inventario`)",
            "KEY `idx_venta_inv_movimiento` (`id_movimiento_inventario`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_pagos", array(
            "`id_venta_pago` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_venta` BIGINT NOT NULL",
            "`id_caja` INT NULL",
            "`id_turno_caja` BIGINT NULL",
            "`id_movimiento_caja` BIGINT NULL",
            "`id_metodo_pago` INT NULL",
            "`metodo_pago` VARCHAR(80) NOT NULL",
            "`tipo_pago` VARCHAR(30) NOT NULL DEFAULT 'pago'",
            "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
            "`referencia` VARCHAR(150) NULL",
            "`autorizacion` VARCHAR(150) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'registrado'",
            "`fecha_pago` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`creado_por` INT NULL",
            "`cancelado_por` INT NULL",
            "`motivo_cancelacion` TEXT NULL",
            "`fecha_cancelacion` DATETIME NULL",
            "PRIMARY KEY (`id_venta_pago`)",
            "KEY `idx_ventas_pagos_venta` (`id_venta`, `estatus`)",
            "KEY `idx_ventas_pagos_tipo` (`id_venta`, `tipo_pago`, `estatus`)",
            "KEY `idx_ventas_pagos_metodo` (`id_metodo_pago`, `fecha_pago`)",
            "KEY `idx_ventas_pagos_caja` (`id_caja`, `fecha_pago`)",
            "KEY `idx_ventas_pagos_turno` (`id_turno_caja`, `estatus`)",
            "KEY `idx_ventas_pagos_movimiento_caja` (`id_movimiento_caja`)"
        ), $opciones, $ejecutar);

        if ($alcance === "expandido") {
        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_eventos", array(
            "`id_venta_evento` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_venta` BIGINT NOT NULL",
            "`folio` VARCHAR(40) NULL",
            "`tipo_evento` VARCHAR(50) NOT NULL",
            "`estatus_anterior` VARCHAR(30) NULL",
            "`estatus_nuevo` VARCHAR(30) NULL",
            "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`referencia` VARCHAR(150) NULL",
            "`datos_snapshot` TEXT NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_venta_evento`)",
            "KEY `idx_ventas_eventos_venta` (`id_venta`, `tipo_evento`)",
            "KEY `idx_ventas_eventos_fecha` (`fecha_registro`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_politicas_apartado", array(
            "`id_politica_apartado` INT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(50) NOT NULL",
            "`nombre` VARCHAR(150) NOT NULL",
            "`porcentaje_anticipo_minimo` DECIMAL(9,6) NOT NULL DEFAULT 0",
            "`monto_anticipo_minimo` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`dias_vigencia` INT NOT NULL DEFAULT 0",
            "`permite_abonos` TINYINT(1) NOT NULL DEFAULT 1",
            "`permite_entrega_sin_liquidar` TINYINT(1) NOT NULL DEFAULT 0",
            "`politica_cancelacion` TEXT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_politica_apartado`)",
            "UNIQUE KEY `idx_apartado_politica_codigo` (`codigo`)",
            "KEY `idx_apartado_politica_estado` (`estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_clientes", array(
            "`id_cliente` INT NOT NULL AUTO_INCREMENT",
            "`codigo_cliente` VARCHAR(50) NOT NULL",
            "`tipo_cliente` VARCHAR(30) NOT NULL DEFAULT 'persona'",
            "`nombre_publico` VARCHAR(180) NOT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`calidad_datos` VARCHAR(30) NOT NULL DEFAULT 'express'",
            "`id_lista_precio_default` INT NULL",
            "`id_segmento_default` INT NULL",
            "`creado_desde` VARCHAR(30) NOT NULL DEFAULT 'pos'",
            "`id_sucursal_alta` INT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_cliente`)",
            "UNIQUE KEY `idx_clientes_codigo` (`codigo_cliente`)",
            "KEY `idx_clientes_nombre` (`nombre_publico`)",
            "KEY `idx_clientes_estado` (`estatus`, `calidad_datos`)",
            "KEY `idx_clientes_lista` (`id_lista_precio_default`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_clientes_identificadores", array(
            "`id_cliente_identificador` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_cliente` INT NOT NULL",
            "`tipo` VARCHAR(30) NOT NULL",
            "`valor` VARCHAR(180) NOT NULL",
            "`valor_normalizado` VARCHAR(180) NOT NULL",
            "`principal` TINYINT(1) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_cliente_identificador`)",
            "KEY `idx_cliente_identificador_cliente` (`id_cliente`, `estatus`)",
            "KEY `idx_cliente_identificador_busqueda` (`tipo`, `valor_normalizado`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_listas_precios", array(
            "`id_lista_precio` INT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(50) NOT NULL",
            "`nombre` VARCHAR(150) NOT NULL",
            "`canal` VARCHAR(30) NULL",
            "`id_almacen` INT NULL",
            "`prioridad` INT NOT NULL DEFAULT 100",
            "`fecha_inicio` DATETIME NULL",
            "`fecha_fin` DATETIME NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_lista_precio`)",
            "UNIQUE KEY `idx_lista_precio_codigo` (`codigo`)",
            "KEY `idx_lista_precio_estado` (`estatus`, `prioridad`)",
            "KEY `idx_lista_precio_canal_almacen` (`canal`, `id_almacen`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_listas_precios_detalle", array(
            "`id_lista_precio_detalle` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_lista_precio` INT NOT NULL",
            "`id_sku` BIGINT NULL",
            "`id_producto_erp` BIGINT NULL",
            "`precio` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
            "`fecha_inicio` DATETIME NULL",
            "`fecha_fin` DATETIME NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_lista_precio_detalle`)",
            "KEY `idx_lista_detalle_lista` (`id_lista_precio`, `estatus`)",
            "KEY `idx_lista_detalle_sku` (`id_sku`, `estatus`)",
            "KEY `idx_lista_detalle_producto` (`id_producto_erp`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_clientes_listas_precios", array(
            "`id_cliente_lista_precio` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_cliente` INT NULL",
            "`id_cliente_crm` BIGINT NULL",
            "`id_lista_precio` INT NOT NULL",
            "`prioridad` INT NOT NULL DEFAULT 1",
            "`fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_fin` DATETIME NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`creado_por` INT NULL",
            "`observaciones` TEXT NULL",
            "PRIMARY KEY (`id_cliente_lista_precio`)",
            "KEY `idx_cliente_lista_cliente` (`id_cliente`, `estatus`, `prioridad`)",
            "KEY `idx_cliente_lista_cliente_crm` (`id_cliente_crm`, `estatus`, `prioridad`)",
            "KEY `idx_cliente_lista_lista` (`id_lista_precio`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_atenciones", array(
            "`id_atencion_pos` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio_temporal` VARCHAR(50) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_caja` INT NULL",
            "`id_terminal_pos` INT NULL",
            "`id_turno_caja` BIGINT NULL",
            "`id_usuario` INT NULL",
            "`id_cliente` INT NULL",
            "`cliente_nombre_publico` VARCHAR(255) NULL",
            "`cliente_identificador_publico` VARCHAR(180) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'abierta'",
            "`origen` VARCHAR(30) NOT NULL DEFAULT 'pos'",
            "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`impuestos_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`pagos_temporales_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_expiracion` DATETIME NULL",
            "`fecha_conversion` DATETIME NULL",
            "`id_venta_convertida` BIGINT NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_atencion_pos`)",
            "UNIQUE KEY `idx_pos_atencion_folio` (`folio_temporal`)",
            "KEY `idx_pos_atencion_turno` (`id_turno_caja`, `estatus`)",
            "KEY `idx_pos_atencion_terminal` (`id_terminal_pos`, `estatus`)",
            "KEY `idx_pos_atencion_usuario` (`id_usuario`, `estatus`, `fecha_apertura`)",
            "KEY `idx_pos_atencion_cliente` (`id_cliente`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_atenciones_detalle", array(
            "`id_atencion_detalle` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_atencion_pos` BIGINT NOT NULL",
            "`renglon` INT NOT NULL DEFAULT 1",
            "`id_producto_erp` BIGINT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`sku` VARCHAR(150) NULL",
            "`descripcion` VARCHAR(500) NOT NULL",
            "`controla_inventario` TINYINT(1) NOT NULL DEFAULT 0",
            "`modo_salida` VARCHAR(40) NOT NULL DEFAULT 'sin_inventario'",
            "`cantidad_venta` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_venta` VARCHAR(40) NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_base` VARCHAR(40) NULL",
            "`precio_unitario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`impuestos` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`datos_snapshot` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_atencion_detalle`)",
            "KEY `idx_pos_atencion_detalle_atencion` (`id_atencion_pos`, `renglon`)",
            "KEY `idx_pos_atencion_detalle_sku` (`id_sku_erp`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_atenciones_pagos_temporales", array(
            "`id_atencion_pago_temporal` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_atencion_pos` BIGINT NOT NULL",
            "`id_metodo_pago` INT NULL",
            "`metodo_pago` VARCHAR(80) NOT NULL",
            "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
            "`referencia` VARCHAR(150) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'capturado'",
            "`datos_snapshot` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_atencion_pago_temporal`)",
            "KEY `idx_pos_atencion_pago_atencion` (`id_atencion_pos`, `estatus`)",
            "KEY `idx_pos_atencion_pago_metodo` (`id_metodo_pago`, `fecha_registro`)"
        ), $opciones, $ejecutar);
        }

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_devoluciones", array(
            "`id_devolucion` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_venta` BIGINT NOT NULL",
            "`tipo` VARCHAR(30) NOT NULL DEFAULT 'devolucion'",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`motivo` VARCHAR(180) NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_devolucion`)",
            "UNIQUE KEY `idx_ventas_devolucion_folio` (`folio`)",
            "KEY `idx_ventas_devolucion_venta` (`id_venta`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_devoluciones_detalle", array(
            "`id_devolucion_detalle` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_devolucion` BIGINT NOT NULL",
            "`id_venta` BIGINT NOT NULL",
            "`id_venta_detalle` BIGINT NOT NULL",
            "`id_movimiento_inventario_origen` BIGINT NULL",
            "`id_movimiento_inventario_devolucion` BIGINT NULL",
            "`id_inventario_unidad` BIGINT NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`decision_inventario` VARCHAR(40) NOT NULL DEFAULT 'cuarentena'",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_devolucion_detalle`)",
            "KEY `idx_devolucion_detalle_devolucion` (`id_devolucion`)",
            "KEY `idx_devolucion_detalle_venta` (`id_venta`, `id_venta_detalle`)",
            "KEY `idx_devolucion_detalle_unidad` (`id_inventario_unidad`)"
        ), $opciones, $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: evolucionar caja POS base hacia caja completa sin tocar ventas/inventario.
     * Impacto: agrega estatus, autorizacion, responsable, evidencia y relaciones para gastos/retiros/vales/reembolsos.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde controlador/script.
     */
    public function planActualizarCajaCompleta($ejecutar = false) {
        $plan = array();
        $tabla = "erp_pos_movimientos_caja";

        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "categoria", "VARCHAR(50) NULL AFTER `tipo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "estatus", "VARCHAR(30) NOT NULL DEFAULT 'registrado' AFTER `monto`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "id_caja", "INT NULL AFTER `id_turno_caja`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "id_almacen", "INT NULL AFTER `id_caja`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "id_venta", "BIGINT NULL AFTER `referencia`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "id_proveedor", "INT NULL AFTER `id_venta`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "responsable", "VARCHAR(180) NULL AFTER `id_proveedor`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "requiere_autorizacion", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `responsable`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "autorizado_por", "INT NULL AFTER `requiere_autorizacion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "fecha_autorizacion", "DATETIME NULL AFTER `autorizado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "requiere_evidencia", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `fecha_autorizacion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "evidencia_estado", "VARCHAR(30) NULL AFTER `requiere_evidencia`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "evidencia_ruta", "VARCHAR(500) NULL AFTER `evidencia_estado`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "cancelado_por", "INT NULL AFTER `creado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "fecha_cancelacion", "DATETIME NULL AFTER `cancelado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "motivo_cancelacion", "TEXT NULL AFTER `fecha_cancelacion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste($tabla, "fecha_actualizacion", "DATETIME NULL AFTER `fecha_registro`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste($tabla, "idx_pos_movimiento_caja_estado", "KEY `idx_pos_movimiento_caja_estado` (`id_caja`, `estatus`, `fecha_registro`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste($tabla, "idx_pos_movimiento_categoria", "KEY `idx_pos_movimiento_categoria` (`categoria`, `estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste($tabla, "idx_pos_movimiento_venta", "KEY `idx_pos_movimiento_venta` (`id_venta`)", $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: auditar columnas e indices requeridos para caja POS completa.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea ni modifica estructura.
     */
    public function auditarCajaCompleta() {
        $tabla = "erp_pos_movimientos_caja";
        $columnas = array(
            "categoria",
            "estatus",
            "id_caja",
            "id_almacen",
            "id_venta",
            "id_proveedor",
            "responsable",
            "requiere_autorizacion",
            "autorizado_por",
            "fecha_autorizacion",
            "requiere_evidencia",
            "evidencia_estado",
            "evidencia_ruta",
            "cancelado_por",
            "fecha_cancelacion",
            "motivo_cancelacion",
            "fecha_actualizacion"
        );
        $indices = array(
            "idx_pos_movimiento_caja_estado",
            "idx_pos_movimiento_categoria",
            "idx_pos_movimiento_venta"
        );
        $resultado = array(
            "tabla" => $tabla,
            "existe" => $this->tablaExiste($tabla),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($columnas as $columna) {
            $resultado["columnas"][] = array(
                "columna" => $columna,
                "existe" => $this->columnaExiste($tabla, $columna)
            );
        }
        foreach ($indices as $indice) {
            $resultado["indices"][] = array(
                "indice" => $indice,
                "existe" => $this->indiceExiste($tabla, $indice)
            );
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de Caja POS completa generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: preparar tabla formal de evidencias/adjuntos para movimientos sensibles de caja POS.
     * Impacto: permite adjuntar, revisar, aprobar o rechazar comprobantes de reembolsos/gastos sin alterar el movimiento original.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde script/controlador.
     */
    public function planActualizarEvidenciasCajaPos($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();
        $plan[] = $this->crearTablaSiNoExiste("erp_pos_movimientos_caja_evidencias", array(
            "`id_evidencia_caja` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_movimiento_caja` BIGINT NOT NULL",
            "`tipo_evidencia` VARCHAR(60) NOT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'recibida'",
            "`titulo` VARCHAR(180) NULL",
            "`descripcion` TEXT NULL",
            "`archivo_ruta` VARCHAR(500) NULL",
            "`archivo_nombre` VARCHAR(255) NULL",
            "`archivo_mime` VARCHAR(120) NULL",
            "`archivo_tamano` BIGINT NULL",
            "`archivo_hash` VARCHAR(128) NULL",
            "`referencia_externa` VARCHAR(180) NULL",
            "`datos_snapshot` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`revisado_por` INT NULL",
            "`fecha_revision` DATETIME NULL",
            "`motivo_rechazo` TEXT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_evidencia_caja`)",
            "KEY `idx_caja_evidencia_movimiento` (`id_movimiento_caja`, `estatus`)",
            "KEY `idx_caja_evidencia_estado` (`estatus`, `fecha_registro`)",
            "KEY `idx_caja_evidencia_hash` (`archivo_hash`)"
        ), $opciones, $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_pos_movimientos_caja", "idx_pos_movimiento_evidencia", "KEY `idx_pos_movimiento_evidencia` (`requiere_evidencia`, `evidencia_estado`, `fecha_registro`)", $ejecutar);
        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: auditar tabla e indices requeridos para evidencias de caja POS.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarEvidenciasCajaPos() {
        $resultado = array(
            "tablas" => array(),
            "columnas" => array(),
            "indices" => array()
        );
        $tablas = array(
            "erp_pos_movimientos_caja",
            "erp_pos_movimientos_caja_evidencias"
        );
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        $columnas = array(
            "erp_pos_movimientos_caja_evidencias" => array(
                "id_evidencia_caja",
                "id_movimiento_caja",
                "tipo_evidencia",
                "estatus",
                "archivo_ruta",
                "archivo_hash",
                "referencia_externa",
                "datos_snapshot",
                "creado_por",
                "revisado_por",
                "motivo_rechazo"
            )
        );
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        $indices = array(
            "erp_pos_movimientos_caja_evidencias" => array(
                "idx_caja_evidencia_movimiento",
                "idx_caja_evidencia_estado",
                "idx_caja_evidencia_hash"
            ),
            "erp_pos_movimientos_caja" => array(
                "idx_pos_movimiento_evidencia"
            )
        );
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de evidencias de caja POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: preparar tabla de solicitudes de correccion para evidencias de caja ya aprobadas.
     * Impacto: evita editar evidencias historicas y agrega trazabilidad de correccion con motivo, folio y resolucion.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde script/controlador.
     */
    public function planActualizarCorreccionesEvidenciasCajaPos($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();
        $plan[] = $this->crearTablaSiNoExiste("erp_pos_movimientos_caja_evidencias_correcciones", array(
            "`id_correccion_evidencia_caja` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_evidencia_caja` BIGINT NOT NULL",
            "`id_movimiento_caja` BIGINT NOT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'solicitada'",
            "`tipo_correccion` VARCHAR(60) NOT NULL DEFAULT 'reemplazo_evidencia'",
            "`motivo` TEXT NOT NULL",
            "`evidencia_estado_anterior` VARCHAR(30) NULL",
            "`datos_snapshot` TEXT NULL",
            "`solicitado_por` INT NOT NULL",
            "`fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`resuelto_por` INT NULL",
            "`fecha_resolucion` DATETIME NULL",
            "`decision` VARCHAR(30) NULL",
            "`motivo_resolucion` TEXT NULL",
            "`id_evidencia_caja_nueva` BIGINT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_correccion_evidencia_caja`)",
            "UNIQUE KEY `idx_caja_evidencia_corr_folio` (`folio`)",
            "KEY `idx_caja_evidencia_corr_evidencia` (`id_evidencia_caja`, `estatus`)",
            "KEY `idx_caja_evidencia_corr_movimiento` (`id_movimiento_caja`, `estatus`)",
            "KEY `idx_caja_evidencia_corr_estado` (`estatus`, `fecha_solicitud`)"
        ), $opciones, $ejecutar);
        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: auditar tabla requerida para correcciones de evidencias de caja POS aprobadas.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarCorreccionesEvidenciasCajaPos() {
        $resultado = array(
            "tablas" => array(),
            "columnas" => array(),
            "indices" => array()
        );
        $tablas = array(
            "erp_pos_movimientos_caja_evidencias",
            "erp_pos_movimientos_caja_evidencias_correcciones"
        );
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        $columnas = array(
            "erp_pos_movimientos_caja_evidencias_correcciones" => array(
                "id_correccion_evidencia_caja",
                "folio",
                "id_evidencia_caja",
                "id_movimiento_caja",
                "estatus",
                "tipo_correccion",
                "motivo",
                "evidencia_estado_anterior",
                "datos_snapshot",
                "solicitado_por",
                "resuelto_por",
                "decision",
                "id_evidencia_caja_nueva"
            )
        );
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        $indices = array(
            "erp_pos_movimientos_caja_evidencias_correcciones" => array(
                "idx_caja_evidencia_corr_folio",
                "idx_caja_evidencia_corr_evidencia",
                "idx_caja_evidencia_corr_movimiento",
                "idx_caja_evidencia_corr_estado"
            )
        );
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de correcciones de evidencias de caja POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-27.
     * Proposito: auditar tablas requeridas para atenciones compartidas POS.
     * Impacto: prepara el salto de cuentas locales a cuentas persistentes multiusuario.
     * Contrato: solo lectura sobre INFORMATION_SCHEMA.
     */
    public function auditarAtencionesPos() {
        $tablas = array(
            "erp_pos_atenciones",
            "erp_pos_atenciones_detalle",
            "erp_pos_atenciones_pagos_temporales"
        );
        $resultado = array();
        foreach ($tablas as $tabla) {
            $resultado[] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de atenciones POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: preparar persistencia formal de precio manual y descuentos POS.
     * Impacto: agrega politicas y excepciones comerciales trazables sin mezclar reglas en el navegador.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde script/controlador.
     */
    public function planActualizarExcepcionesComerciales($ejecutar = false) {
        $plan = array();
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_politicas_comerciales", array(
            "`id_politica_comercial` INT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(60) NOT NULL",
            "`nombre` VARCHAR(180) NOT NULL",
            "`tipo_excepcion` VARCHAR(40) NOT NULL",
            "`canal` VARCHAR(30) NULL",
            "`id_almacen` INT NULL",
            "`descuento_max_porcentaje` DECIMAL(9,6) NOT NULL DEFAULT 0",
            "`descuento_max_monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`margen_minimo_porcentaje` DECIMAL(9,6) NOT NULL DEFAULT 0",
            "`requiere_autorizacion` TINYINT(1) NOT NULL DEFAULT 1",
            "`permiso_requerido` VARCHAR(180) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`fecha_inicio` DATETIME NULL",
            "`fecha_fin` DATETIME NULL",
            "`creado_por` INT NULL",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_politica_comercial`)",
            "UNIQUE KEY `idx_ventas_politica_comercial_codigo` (`codigo`)",
            "KEY `idx_ventas_politica_comercial_tipo` (`tipo_excepcion`, `estatus`)",
            "KEY `idx_ventas_politica_comercial_canal` (`canal`, `id_almacen`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_excepciones_comerciales", array(
            "`id_excepcion_comercial` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(50) NOT NULL",
            "`id_venta` BIGINT NULL",
            "`id_venta_detalle` BIGINT NULL",
            "`id_politica_comercial` INT NULL",
            "`id_cliente` INT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`tipo_excepcion` VARCHAR(40) NOT NULL",
            "`alcance` VARCHAR(30) NOT NULL DEFAULT 'partida'",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'solicitada'",
            "`precio_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_lista` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_solicitado` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_aplicado` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento_monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento_porcentaje` DECIMAL(9,6) NOT NULL DEFAULT 0",
            "`descuento_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`subtotal_antes` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`total_despues` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`margen_estimado_porcentaje` DECIMAL(9,6) NULL",
            "`motivo` TEXT NOT NULL",
            "`autorizacion_codigo` VARCHAR(150) NULL",
            "`solicitado_por` INT NULL",
            "`autorizado_por` INT NULL",
            "`aplicado_por` INT NULL",
            "`fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_autorizacion` DATETIME NULL",
            "`fecha_aplicacion` DATETIME NULL",
            "`datos_snapshot` TEXT NULL",
            "`observaciones` TEXT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_excepcion_comercial`)",
            "UNIQUE KEY `idx_ventas_excepcion_folio` (`folio`)",
            "KEY `idx_ventas_excepcion_venta` (`id_venta`, `estatus`)",
            "KEY `idx_ventas_excepcion_detalle` (`id_venta_detalle`)",
            "KEY `idx_ventas_excepcion_tipo` (`tipo_excepcion`, `estatus`)",
            "KEY `idx_ventas_excepcion_cliente` (`id_cliente`, `fecha_solicitud`)",
            "KEY `idx_ventas_excepcion_sku` (`id_sku_erp`, `fecha_solicitud`)",
            "KEY `idx_ventas_excepcion_autorizador` (`autorizado_por`, `fecha_autorizacion`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "descuento_motivo", "TEXT NULL AFTER `descuento_total`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "id_excepcion_comercial_general", "BIGINT NULL AFTER `descuento_motivo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "autorizado_comercial_por", "INT NULL AFTER `id_excepcion_comercial_general`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "fecha_autorizacion_comercial", "DATETIME NULL AFTER `autorizado_comercial_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "id_excepcion_comercial", "BIGINT NULL AFTER `autorizacion_precio`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "tipo_excepcion_comercial", "VARCHAR(40) NULL AFTER `id_excepcion_comercial`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "motivo_excepcion_comercial", "TEXT NULL AFTER `tipo_excepcion_comercial`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "autorizado_comercial_por", "INT NULL AFTER `motivo_excepcion_comercial`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "fecha_autorizacion_comercial", "DATETIME NULL AFTER `autorizado_comercial_por`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas", "idx_ventas_excepcion_general", "KEY `idx_ventas_excepcion_general` (`id_excepcion_comercial_general`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_detalle", "idx_ventas_detalle_excepcion", "KEY `idx_ventas_detalle_excepcion` (`id_excepcion_comercial`)", $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: auditar estructura requerida para excepciones comerciales POS.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarExcepcionesComerciales() {
        $tablas = array(
            "erp_ventas_politicas_comerciales",
            "erp_ventas_excepciones_comerciales"
        );
        $columnas = array(
            "erp_ventas" => array(
                "descuento_motivo",
                "id_excepcion_comercial_general",
                "autorizado_comercial_por",
                "fecha_autorizacion_comercial"
            ),
            "erp_ventas_detalle" => array(
                "id_excepcion_comercial",
                "tipo_excepcion_comercial",
                "motivo_excepcion_comercial",
                "autorizado_comercial_por",
                "fecha_autorizacion_comercial"
            )
        );
        $indices = array(
            "erp_ventas" => array("idx_ventas_excepcion_general"),
            "erp_ventas_detalle" => array("idx_ventas_detalle_excepcion")
        );
        $resultado = array(
            "tablas" => array(),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de excepciones comerciales generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: preparar reversas reales POS con reembolso, decision financiera, caja e inventario trazable.
     * Impacto: extiende devoluciones base sin ejecutar movimientos; la escritura real queda para un aplicador transaccional posterior.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde script/controlador.
     */
    public function planActualizarReversasPos($ejecutar = false) {
        $plan = array();

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "id_caja", "INT NULL AFTER `id_venta`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "id_almacen", "INT NULL AFTER `id_caja`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "id_turno_caja", "BIGINT NULL AFTER `id_almacen`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "id_movimiento_caja", "BIGINT NULL AFTER `id_turno_caja`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "decision_financiera", "VARCHAR(40) NOT NULL DEFAULT 'sin_reembolso' AFTER `motivo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "monto_reembolso", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `decision_financiera`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "monto_saldo_favor", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `monto_reembolso`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "autorizado_por", "INT NULL AFTER `creado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "fecha_autorizacion", "DATETIME NULL AFTER `autorizado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "aplicado_por", "INT NULL AFTER `fecha_autorizacion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "fecha_aplicacion", "DATETIME NULL AFTER `aplicado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "datos_snapshot", "TEXT NULL AFTER `observaciones`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "id_existencia_inventario", "BIGINT NULL AFTER `id_movimiento_inventario_devolucion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "id_almacen_destino", "INT NULL AFTER `id_inventario_unidad`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "importe_reembolso", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `cantidad_base`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "datos_snapshot", "TEXT NULL AFTER `estatus`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones", "idx_ventas_devolucion_caja", "KEY `idx_ventas_devolucion_caja` (`id_turno_caja`, `id_caja`, `estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones", "idx_ventas_devolucion_mov_caja", "KEY `idx_ventas_devolucion_mov_caja` (`id_movimiento_caja`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_existencia", "KEY `idx_devolucion_detalle_existencia` (`id_existencia_inventario`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_mov_inv", "KEY `idx_devolucion_detalle_mov_inv` (`id_movimiento_inventario_devolucion`)", $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: auditar estructura requerida para ejecutar devoluciones/cancelaciones POS reales.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no mueve caja, ventas ni inventario.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarReversasPos() {
        $tablas = array(
            "erp_ventas",
            "erp_ventas_detalle",
            "erp_ventas_pagos",
            "erp_pos_movimientos_caja",
            "erp_pos_turnos",
            "erp_inventario_existencias",
            "erp_inventario_movimientos",
            "erp_ventas_devoluciones",
            "erp_ventas_devoluciones_detalle"
        );
        $columnas = array(
            "erp_ventas_devoluciones" => array(
                "id_caja",
                "id_almacen",
                "id_turno_caja",
                "id_movimiento_caja",
                "decision_financiera",
                "monto_reembolso",
                "monto_saldo_favor",
                "autorizado_por",
                "fecha_autorizacion",
                "aplicado_por",
                "fecha_aplicacion",
                "datos_snapshot"
            ),
            "erp_ventas_devoluciones_detalle" => array(
                "id_existencia_inventario",
                "id_almacen_destino",
                "importe_reembolso",
                "datos_snapshot"
            )
        );
        $indices = array(
            "erp_ventas_devoluciones" => array(
                "idx_ventas_devolucion_folio",
                "idx_ventas_devolucion_venta",
                "idx_ventas_devolucion_caja",
                "idx_ventas_devolucion_mov_caja"
            ),
            "erp_ventas_devoluciones_detalle" => array(
                "idx_devolucion_detalle_devolucion",
                "idx_devolucion_detalle_venta",
                "idx_devolucion_detalle_unidad",
                "idx_devolucion_detalle_existencia",
                "idx_devolucion_detalle_mov_inv"
            )
        );
        $resultado = array(
            "tablas" => array(),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de reversas POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-07.
     * Proposito: preparar componentes financieros para reversas POS con pagos mixtos caja + saldo CRM.
     * Impacto: permite separar lo que sale de caja, lo que vuelve a saldo CRM y lo que queda como saldo favor trazable.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa y no crea devoluciones reales.
     */
    public function planActualizarReversasSaldoCrmPos($ejecutar = false) {
        $plan = array();
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "id_cliente_crm", "BIGINT NULL AFTER `id_venta`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "monto_reintegro_saldo_crm", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `monto_saldo_favor`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones", "monto_no_caja", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `monto_reintegro_saldo_crm`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones", "idx_ventas_devolucion_cliente_crm", "KEY `idx_ventas_devolucion_cliente_crm` (`id_cliente_crm`, `estatus`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_devoluciones_finanzas", array(
            "`id_devolucion_finanza` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(50) NOT NULL",
            "`id_devolucion` BIGINT NOT NULL",
            "`id_venta` BIGINT NOT NULL",
            "`id_cliente_crm` BIGINT NULL",
            "`id_caja` INT NULL",
            "`id_turno_caja` BIGINT NULL",
            "`tipo_componente` VARCHAR(50) NOT NULL",
            "`naturaleza` VARCHAR(30) NOT NULL DEFAULT 'egreso'",
            "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`moneda` VARCHAR(3) NOT NULL DEFAULT 'MXN'",
            "`id_movimiento_caja` BIGINT NULL",
            "`id_cliente_saldo_cuenta` BIGINT NULL",
            "`id_cliente_saldo_movimiento` BIGINT NULL",
            "`referencia_origen` VARCHAR(80) NULL",
            "`referencia_externa` VARCHAR(80) NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`motivo` TEXT NULL",
            "`datos_snapshot` TEXT NULL",
            "`creado_por` INT NULL",
            "`aplicado_por` INT NULL",
            "`fecha_aplicacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_devolucion_finanza`)",
            "UNIQUE KEY `idx_devolucion_finanza_folio` (`folio`)",
            "KEY `idx_devolucion_finanza_devolucion` (`id_devolucion`, `estatus`)",
            "KEY `idx_devolucion_finanza_venta` (`id_venta`, `tipo_componente`)",
            "KEY `idx_devolucion_finanza_cliente` (`id_cliente_crm`, `estatus`)",
            "KEY `idx_devolucion_finanza_caja` (`id_turno_caja`, `id_caja`, `estatus`)",
            "KEY `idx_devolucion_finanza_mov_caja` (`id_movimiento_caja`)",
            "KEY `idx_devolucion_finanza_saldo` (`id_cliente_saldo_movimiento`)"
        ), $opciones, $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-07.
     * Proposito: auditar estructura de componentes financieros para reversas POS con saldo CRM.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no modifica caja, CRM, ventas ni inventario.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarReversasSaldoCrmPos() {
        $tablas = array(
            "erp_ventas_devoluciones",
            "erp_ventas_devoluciones_finanzas",
            "erp_ventas",
            "erp_ventas_pagos",
            "crm_clientes_saldos_cuentas",
            "crm_clientes_saldos_movimientos"
        );
        $columnas = array(
            "erp_ventas_devoluciones" => array(
                "id_cliente_crm",
                "monto_reintegro_saldo_crm",
                "monto_no_caja"
            ),
            "erp_ventas_devoluciones_finanzas" => array(
                "id_devolucion_finanza",
                "folio",
                "id_devolucion",
                "id_venta",
                "id_cliente_crm",
                "tipo_componente",
                "monto",
                "id_movimiento_caja",
                "id_cliente_saldo_cuenta",
                "id_cliente_saldo_movimiento",
                "estatus",
                "datos_snapshot"
            )
        );
        $indices = array(
            "erp_ventas_devoluciones" => array(
                "idx_ventas_devolucion_cliente_crm"
            ),
            "erp_ventas_devoluciones_finanzas" => array(
                "idx_devolucion_finanza_folio",
                "idx_devolucion_finanza_devolucion",
                "idx_devolucion_finanza_venta",
                "idx_devolucion_finanza_cliente",
                "idx_devolucion_finanza_caja",
                "idx_devolucion_finanza_mov_caja",
                "idx_devolucion_finanza_saldo"
            )
        );
        $resultado = array(
            "tablas" => array(),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de reversas POS con saldo CRM generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: preparar estructura para inspeccion fisica de productos devueltos en POS.
     * Impacto: permite registrar en una fase posterior si una devolucion queda en cuarentena, reintegra, merma o garantia.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa y no mueve inventario.
     */
    public function planActualizarInspeccionFisicaDevolucionesPos($ejecutar = false) {
        $plan = array();
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "inspeccion_estado", "VARCHAR(40) NOT NULL DEFAULT 'pendiente' AFTER `decision_inventario`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "id_inspeccion_fisica", "BIGINT NULL AFTER `inspeccion_estado`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "fecha_inspeccion_fisica", "DATETIME NULL AFTER `id_inspeccion_fisica`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_inspeccion", "KEY `idx_devolucion_detalle_inspeccion` (`inspeccion_estado`, `decision_inventario`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_id_inspeccion", "KEY `idx_devolucion_detalle_id_inspeccion` (`id_inspeccion_fisica`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_devoluciones_inspecciones", array(
            "`id_inspeccion_fisica` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(50) NOT NULL",
            "`id_devolucion` BIGINT NOT NULL",
            "`id_devolucion_detalle` BIGINT NOT NULL",
            "`id_venta` BIGINT NOT NULL",
            "`id_venta_detalle` BIGINT NOT NULL",
            "`id_almacen_origen` INT NULL",
            "`id_almacen_destino` INT NULL",
            "`id_existencia_inventario` BIGINT NULL",
            "`id_inventario_unidad` BIGINT NULL",
            "`id_movimiento_inventario` BIGINT NULL",
            "`id_reclamo_garantia` BIGINT NULL",
            "`decision_fisica` VARCHAR(50) NOT NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'registrada'",
            "`condicion_producto` VARCHAR(80) NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`motivo` TEXT NULL",
            "`diagnostico` TEXT NULL",
            "`evidencia_snapshot` TEXT NULL",
            "`datos_snapshot` TEXT NULL",
            "`inspeccionado_por` INT NULL",
            "`autorizado_por` INT NULL",
            "`fecha_inspeccion` DATETIME NULL",
            "`fecha_autorizacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_inspeccion_fisica`)",
            "UNIQUE KEY `idx_devolucion_inspeccion_folio` (`folio`)",
            "KEY `idx_devolucion_inspeccion_detalle` (`id_devolucion_detalle`, `estatus`)",
            "KEY `idx_devolucion_inspeccion_devolucion` (`id_devolucion`, `estatus`)",
            "KEY `idx_devolucion_inspeccion_decision` (`decision_fisica`, `estatus`)",
            "KEY `idx_devolucion_inspeccion_mov_inv` (`id_movimiento_inventario`)",
            "KEY `idx_devolucion_inspeccion_garantia` (`id_reclamo_garantia`)"
        ), $opciones, $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-09.
     * Proposito: preparar DDL para resolver destino final de partidas POS en cuarentena confirmada.
     * Impacto: permite cerrar cuarentena hacia reintegro, merma, garantia o reparacion sin perder trazabilidad.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa y no mueve inventario.
     */
    public function planActualizarDestinoFinalCuarentenaPos($ejecutar = false) {
        $plan = array();

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "destino_final", "VARCHAR(50) NULL AFTER `fecha_inspeccion_fisica`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "fecha_destino_final", "DATETIME NULL AFTER `destino_final`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "resuelto_por", "INT NULL AFTER `fecha_destino_final`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "motivo_destino_final", "TEXT NULL AFTER `resuelto_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_detalle", "id_movimiento_inventario_destino_final", "BIGINT NULL AFTER `id_movimiento_inventario_devolucion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_inspecciones", "destino_final", "VARCHAR(50) NULL AFTER `decision_fisica`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_inspecciones", "fecha_resolucion_destino", "DATETIME NULL AFTER `fecha_autorizacion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_devoluciones_inspecciones", "resuelto_por", "INT NULL AFTER `autorizado_por`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_destino_final", "KEY `idx_devolucion_detalle_destino_final` (`destino_final`, `inspeccion_estado`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_detalle", "idx_devolucion_detalle_mov_destino", "KEY `idx_devolucion_detalle_mov_destino` (`id_movimiento_inventario_destino_final`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_devoluciones_inspecciones", "idx_devolucion_inspeccion_destino", "KEY `idx_devolucion_inspeccion_destino` (`destino_final`, `estatus`)", $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-30.
     * Proposito: auditar estructura para inspeccion fisica de devoluciones POS.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no mueve inventario ni modifica devoluciones.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarInspeccionFisicaDevolucionesPos() {
        $tablas = array(
            "erp_ventas_devoluciones",
            "erp_ventas_devoluciones_detalle",
            "erp_ventas_devoluciones_inspecciones",
            "erp_inventario_existencias",
            "erp_inventario_movimientos"
        );
        $columnas = array(
            "erp_ventas_devoluciones_detalle" => array(
                "decision_inventario",
                "inspeccion_estado",
                "id_inspeccion_fisica",
                "fecha_inspeccion_fisica",
                "id_movimiento_inventario_devolucion"
            ),
            "erp_ventas_devoluciones_inspecciones" => array(
                "folio",
                "id_devolucion",
                "id_devolucion_detalle",
                "decision_fisica",
                "estatus",
                "id_movimiento_inventario",
                "id_reclamo_garantia",
                "datos_snapshot"
            )
        );
        $indices = array(
            "erp_ventas_devoluciones_detalle" => array(
                "idx_devolucion_detalle_inspeccion",
                "idx_devolucion_detalle_id_inspeccion"
            ),
            "erp_ventas_devoluciones_inspecciones" => array(
                "idx_devolucion_inspeccion_folio",
                "idx_devolucion_inspeccion_detalle",
                "idx_devolucion_inspeccion_devolucion",
                "idx_devolucion_inspeccion_decision",
                "idx_devolucion_inspeccion_mov_inv",
                "idx_devolucion_inspeccion_garantia"
            )
        );
        $resultado = array("tablas" => array(), "columnas" => array(), "indices" => array());
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla));
        }
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de inspeccion fisica de devoluciones POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-09.
     * Proposito: auditar estructura para destino final de cuarentena POS.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no mueve inventario ni cierra devoluciones.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarDestinoFinalCuarentenaPos() {
        $tablas = array(
            "erp_ventas_devoluciones_detalle",
            "erp_ventas_devoluciones_inspecciones",
            "erp_inventario_existencias",
            "erp_inventario_movimientos"
        );
        $columnas = array(
            "erp_ventas_devoluciones_detalle" => array(
                "inspeccion_estado",
                "id_inspeccion_fisica",
                "destino_final",
                "fecha_destino_final",
                "resuelto_por",
                "motivo_destino_final",
                "id_movimiento_inventario_destino_final"
            ),
            "erp_ventas_devoluciones_inspecciones" => array(
                "destino_final",
                "fecha_resolucion_destino",
                "resuelto_por",
                "id_movimiento_inventario"
            )
        );
        $indices = array(
            "erp_ventas_devoluciones_detalle" => array(
                "idx_devolucion_detalle_destino_final",
                "idx_devolucion_detalle_mov_destino"
            ),
            "erp_ventas_devoluciones_inspecciones" => array(
                "idx_devolucion_inspeccion_destino"
            )
        );
        $resultado = array("tablas" => array(), "columnas" => array(), "indices" => array());
        foreach ($tablas as $tabla) {
            $resultado["tablas"][] = array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla));
        }
        foreach ($columnas as $tabla => $cols) {
            foreach ($cols as $columna) {
                $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
            }
        }
        foreach ($indices as $tabla => $idxs) {
            foreach ($idxs as $indice) {
                $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
            }
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de destino final de cuarentena POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: preparar columnas canonicas CRM para que POS/Ventas no dependan de `erp_clientes` legacy.
     * Impacto: conserva snapshot historico del cliente CRM en venta y excepcion comercial.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa desde script/controlador.
     */
    public function planActualizarContratoCrmPos($ejecutar = false) {
        $plan = array();

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "id_cliente_crm", "BIGINT NULL AFTER `id_cliente`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "cliente_codigo_snapshot", "VARCHAR(80) NULL AFTER `cliente_nombre_publico`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "cliente_origen_snapshot", "VARCHAR(40) NULL AFTER `cliente_identificador_publico`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "cliente_snapshot", "TEXT NULL AFTER `cliente_origen_snapshot`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_excepciones_comerciales", "id_cliente_crm", "BIGINT NULL AFTER `id_cliente`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_excepciones_comerciales", "cliente_codigo_snapshot", "VARCHAR(80) NULL AFTER `id_cliente_crm`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_excepciones_comerciales", "cliente_nombre_snapshot", "VARCHAR(255) NULL AFTER `cliente_codigo_snapshot`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_excepciones_comerciales", "cliente_identificador_snapshot", "VARCHAR(180) NULL AFTER `cliente_nombre_snapshot`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_excepciones_comerciales", "cliente_origen_snapshot", "VARCHAR(40) NULL AFTER `cliente_identificador_snapshot`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas", "idx_ventas_cliente_crm_fecha", "KEY `idx_ventas_cliente_crm_fecha` (`id_cliente_crm`, `fecha_venta`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_excepciones_comerciales", "idx_ventas_excepcion_cliente_crm", "KEY `idx_ventas_excepcion_cliente_crm` (`id_cliente_crm`, `fecha_solicitud`)", $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-06-29.
     * Proposito: auditar columnas canonicas CRM requeridas por POS/Ventas.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarContratoCrmPos() {
        $columnas = array(
            "erp_ventas" => array(
                "id_cliente_crm",
                "cliente_codigo_snapshot",
                "cliente_origen_snapshot",
                "cliente_snapshot"
            ),
            "erp_ventas_excepciones_comerciales" => array(
                "id_cliente_crm",
                "cliente_codigo_snapshot",
                "cliente_nombre_snapshot",
                "cliente_identificador_snapshot",
                "cliente_origen_snapshot"
            )
        );
        $indices = array(
            "erp_ventas" => array("idx_ventas_cliente_crm_fecha"),
            "erp_ventas_excepciones_comerciales" => array("idx_ventas_excepcion_cliente_crm")
        );
        $resultado = array(
            "tablas" => array(
                array("tabla" => "erp_ventas", "existe" => $this->tablaExiste("erp_ventas")),
                array("tabla" => "erp_ventas_excepciones_comerciales", "existe" => $this->tablaExiste("erp_ventas_excepciones_comerciales")),
                array("tabla" => "crm_clientes_maestro", "existe" => $this->tablaExiste("crm_clientes_maestro")),
                array("tabla" => "crm_clientes_identificadores", "existe" => $this->tablaExiste("crm_clientes_identificadores"))
            ),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($columnas as $tabla => $listaColumnas) {
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array(
                    "tabla" => $tabla,
                    "columna" => $columna,
                    "existe" => $this->columnaExiste($tabla, $columna)
                );
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array(
                    "tabla" => $tabla,
                    "indice" => $indice,
                    "existe" => $this->indiceExiste($tabla, $indice)
                );
            }
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de contrato CRM/POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: preparar expediente formal para revisar faltantes/sobrantes de cierre POS.
     * Impacto: crea una tabla de seguimiento sin modificar turnos, movimientos de caja ni ventas.
     * Contrato: con $ejecutar=false solo devuelve SQL propuesto; con true requiere autorizacion externa.
     */
    public function planActualizarRevisionDiferenciasCajaPos($ejecutar = false) {
        $plan = array();
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_turnos_diferencias_revision", array(
            "`id_diferencia_revision` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(50) NOT NULL",
            "`id_turno_caja` BIGINT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_caja` INT NOT NULL",
            "`tipo_diferencia` VARCHAR(30) NOT NULL",
            "`monto_diferencia` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente_revision'",
            "`motivo` VARCHAR(250) NULL",
            "`diagnostico` TEXT NULL",
            "`decision` VARCHAR(60) NULL",
            "`evidencia_referencia` VARCHAR(250) NULL",
            "`responsable_revision` VARCHAR(180) NULL",
            "`solicitado_por` INT NULL",
            "`resuelto_por` INT NULL",
            "`fecha_revision` DATETIME NULL",
            "`fecha_resolucion` DATETIME NULL",
            "`datos_snapshot` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_diferencia_revision`)",
            "UNIQUE KEY `idx_pos_dif_revision_folio` (`folio`)",
            "UNIQUE KEY `idx_pos_dif_revision_turno` (`id_turno_caja`)",
            "KEY `idx_pos_dif_revision_estado` (`estatus`, `fecha_registro`)",
            "KEY `idx_pos_dif_revision_caja` (`id_almacen`, `id_caja`, `estatus`)",
            "KEY `idx_pos_dif_revision_tipo` (`tipo_diferencia`, `estatus`)"
        ), $opciones, $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-03.
     * Proposito: auditar si existe estructura formal para revisar diferencias de caja POS.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarRevisionDiferenciasCajaPos() {
        $tabla = "erp_pos_turnos_diferencias_revision";
        $columnas = array(
            "id_diferencia_revision",
            "folio",
            "id_turno_caja",
            "id_almacen",
            "id_caja",
            "tipo_diferencia",
            "monto_diferencia",
            "estatus",
            "motivo",
            "diagnostico",
            "decision",
            "evidencia_referencia",
            "responsable_revision",
            "solicitado_por",
            "resuelto_por",
            "fecha_revision",
            "fecha_resolucion",
            "datos_snapshot",
            "fecha_registro",
            "fecha_actualizacion"
        );
        $indices = array(
            "idx_pos_dif_revision_folio",
            "idx_pos_dif_revision_turno",
            "idx_pos_dif_revision_estado",
            "idx_pos_dif_revision_caja",
            "idx_pos_dif_revision_tipo"
        );
        $resultado = array(
            "tablas" => array(array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla))),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($columnas as $columna) {
            $resultado["columnas"][] = array(
                "tabla" => $tabla,
                "columna" => $columna,
                "existe" => $this->columnaExiste($tabla, $columna)
            );
        }
        foreach ($indices as $indice) {
            $resultado["indices"][] = array(
                "tabla" => $tabla,
                "indice" => $indice,
                "existe" => $this->indiceExiste($tabla, $indice)
            );
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de revision de diferencias caja POS generada",
            "depurar" => $resultado
        );
    }



    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: preparar politicas POS por sucursal/SKU para permitir inventario pendiente sin activar reglas globales de ecommerce.
     * Impacto: define limites, permisos y vigencia para ventas POS con faltante; no crea ventas ni pendientes.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa.
     */
    public function planActualizarPoliticasInventarioPendientePos($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();
        $plan[] = $this->crearTablaSiNoExiste("erp_pos_politicas_venta_inventario", array(
            "`id_politica_inventario_pos` BIGINT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(60) NOT NULL",
            "`nombre` VARCHAR(180) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`familia` VARCHAR(120) NULL",
            "`canal` VARCHAR(30) NOT NULL DEFAULT 'pos'",
            "`permite_inventario_pendiente` TINYINT(1) NOT NULL DEFAULT 0",
            "`cantidad_maxima_pendiente` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`monto_maximo` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`requiere_autorizacion` TINYINT(1) NOT NULL DEFAULT 1",
            "`permiso_requerido` VARCHAR(120) NULL",
            "`motivo_obligatorio` TINYINT(1) NOT NULL DEFAULT 1",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`fecha_inicio` DATETIME NULL",
            "`fecha_fin` DATETIME NULL",
            "`creado_por` INT NULL",
            "`autorizado_por` INT NULL",
            "`fecha_autorizacion` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`datos_snapshot` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_politica_inventario_pos`)",
            "UNIQUE KEY `idx_pos_inv_pol_codigo` (`codigo`)",
            "KEY `idx_pos_inv_pol_almacen_sku` (`id_almacen`, `id_sku_erp`, `estatus`)",
            "KEY `idx_pos_inv_pol_canal` (`canal`, `estatus`, `fecha_inicio`, `fecha_fin`)",
            "KEY `idx_pos_inv_pol_permiso` (`permiso_requerido`, `estatus`)"
        ), $opciones, $ejecutar);
        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: auditar politicas POS para inventario pendiente por sucursal/SKU.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarPoliticasInventarioPendientePos() {
        $tabla = "erp_pos_politicas_venta_inventario";
        $columnas = array(
            "id_politica_inventario_pos", "codigo", "nombre", "id_almacen", "id_sku_erp", "familia", "canal",
            "permite_inventario_pendiente", "cantidad_maxima_pendiente", "monto_maximo", "requiere_autorizacion",
            "permiso_requerido", "motivo_obligatorio", "estatus", "fecha_inicio", "fecha_fin", "creado_por",
            "autorizado_por", "fecha_autorizacion", "observaciones", "datos_snapshot", "fecha_registro", "fecha_actualizacion"
        );
        $indices = array("idx_pos_inv_pol_codigo", "idx_pos_inv_pol_almacen_sku", "idx_pos_inv_pol_canal", "idx_pos_inv_pol_permiso");
        $resultado = array("tablas" => array(array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla))), "columnas" => array(), "indices" => array());
        foreach ($columnas as $columna) {
            $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
        }
        foreach ($indices as $indice) {
            $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de politicas inventario pendiente POS generada",
            "depurar" => $resultado
        );
    }
    /**
     * Documentacion IA: Codex GPT-5, 2026-07-11.
     * Proposito: preparar estructura para ventas POS con inventario pendiente controlado.
     * Impacto: permite vender con faltante autorizado y abrir pendiente operativo para mini inventario por SKU/sucursal.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa y no corrige inventario por si mismo.
     */
    public function planActualizarInventarioPendientePos($ejecutar = false) {
        $plan = array();
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "inventario_validacion_estado", "VARCHAR(40) NOT NULL DEFAULT 'normal' AFTER `estatus`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas", "inventario_pendiente_total", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `saldo_total`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas", "idx_ventas_inv_validacion", "KEY `idx_ventas_inv_validacion` (`inventario_validacion_estado`, `id_almacen`, `fecha_venta`)", $ejecutar);

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "inventario_estado", "VARCHAR(40) NOT NULL DEFAULT 'normal' AFTER `modo_salida`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "permite_inventario_pendiente", "TINYINT(1) NOT NULL DEFAULT 0 AFTER `inventario_estado`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "cantidad_inventario_pendiente", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `cantidad_base`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle", "id_inventario_pendiente", "BIGINT NULL AFTER `cantidad_inventario_pendiente`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_detalle", "idx_ventas_detalle_inv_estado", "KEY `idx_ventas_detalle_inv_estado` (`inventario_estado`, `id_sku_erp`, `estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_detalle", "idx_ventas_detalle_inv_pendiente", "KEY `idx_ventas_detalle_inv_pendiente` (`id_inventario_pendiente`)", $ejecutar);

        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle_inventario", "tipo_asignacion", "VARCHAR(40) NOT NULL DEFAULT 'existencia' AFTER `id_movimiento_inventario`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle_inventario", "cantidad_pendiente_validacion", "DECIMAL(18,6) NOT NULL DEFAULT 0 AFTER `cantidad_base`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_ventas_detalle_inventario", "id_inventario_pendiente", "BIGINT NULL AFTER `cantidad_pendiente_validacion`", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_detalle_inventario", "idx_venta_inv_tipo", "KEY `idx_venta_inv_tipo` (`tipo_asignacion`, `id_almacen`, `fecha_registro`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_ventas_detalle_inventario", "idx_venta_inv_pendiente", "KEY `idx_venta_inv_pendiente` (`id_inventario_pendiente`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_inventario_pendientes", array(
            "`id_inventario_pendiente` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(50) NOT NULL",
            "`id_venta` BIGINT NOT NULL",
            "`id_venta_detalle` BIGINT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_sku_erp` BIGINT NOT NULL",
            "`sku` VARCHAR(150) NULL",
            "`descripcion` VARCHAR(500) NULL",
            "`cantidad_vendida` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_cubierta` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_pendiente` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_base` VARCHAR(40) NULL",
            "`precio_unitario_snapshot` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente_revision'",
            "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'alta'",
            "`origen` VARCHAR(40) NOT NULL DEFAULT 'pos_venta'",
            "`id_notificacion` BIGINT NULL",
            "`politica_snapshot` TEXT NULL",
            "`datos_snapshot` TEXT NULL",
            "`creado_por` INT NULL",
            "`asignado_a` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_revision` DATETIME NULL",
            "`revisado_por` INT NULL",
            "`cantidad_fisica_validada` DECIMAL(18,6) NULL",
            "`cantidad_ajuste_requerida` DECIMAL(18,6) NULL",
            "`id_movimiento_ajuste` BIGINT NULL",
            "`motivo_revision` TEXT NULL",
            "`fecha_resolucion` DATETIME NULL",
            "PRIMARY KEY (`id_inventario_pendiente`)",
            "UNIQUE KEY `idx_pos_inv_pend_folio` (`folio`)",
            "KEY `idx_pos_inv_pend_venta` (`id_venta`, `id_venta_detalle`)",
            "KEY `idx_pos_inv_pend_sku` (`id_almacen`, `id_sku_erp`, `estatus`)",
            "KEY `idx_pos_inv_pend_estado` (`estatus`, `prioridad`, `fecha_registro`)",
            "KEY `idx_pos_inv_pend_notificacion` (`id_notificacion`)",
            "KEY `idx_pos_inv_pend_mov_ajuste` (`id_movimiento_ajuste`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_pos_inventario_pendientes_eventos", array(
            "`id_evento_inventario_pendiente` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_inventario_pendiente` BIGINT NOT NULL",
            "`tipo_evento` VARCHAR(60) NOT NULL",
            "`estatus_anterior` VARCHAR(40) NULL",
            "`estatus_nuevo` VARCHAR(40) NULL",
            "`cantidad` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`referencia` VARCHAR(180) NULL",
            "`observaciones` TEXT NULL",
            "`datos_snapshot` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_evento_inventario_pendiente`)",
            "KEY `idx_pos_inv_pend_evt_pendiente` (`id_inventario_pendiente`, `fecha_registro`)",
            "KEY `idx_pos_inv_pend_evt_tipo` (`tipo_evento`, `fecha_registro`)"
        ), $opciones, $ejecutar);

        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-11.
     * Proposito: auditar estructura para venta POS con inventario pendiente.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no modifica ventas, inventario ni notificaciones.
     * Contrato: no crea tablas, columnas ni indices.
     */
    public function auditarInventarioPendientePos() {
        $columnas = array(
            "erp_ventas" => array("inventario_validacion_estado", "inventario_pendiente_total"),
            "erp_ventas_detalle" => array("inventario_estado", "permite_inventario_pendiente", "cantidad_inventario_pendiente", "id_inventario_pendiente"),
            "erp_ventas_detalle_inventario" => array("tipo_asignacion", "cantidad_pendiente_validacion", "id_inventario_pendiente"),
            "erp_pos_inventario_pendientes" => array("id_inventario_pendiente", "folio", "id_venta", "id_venta_detalle", "id_almacen", "id_sku_erp", "cantidad_pendiente", "estatus", "id_notificacion", "id_movimiento_ajuste"),
            "erp_pos_inventario_pendientes_eventos" => array("id_evento_inventario_pendiente", "id_inventario_pendiente", "tipo_evento", "estatus_nuevo")
        );
        $indices = array(
            "erp_ventas" => array("idx_ventas_inv_validacion"),
            "erp_ventas_detalle" => array("idx_ventas_detalle_inv_estado", "idx_ventas_detalle_inv_pendiente"),
            "erp_ventas_detalle_inventario" => array("idx_venta_inv_tipo", "idx_venta_inv_pendiente"),
            "erp_pos_inventario_pendientes" => array("idx_pos_inv_pend_folio", "idx_pos_inv_pend_venta", "idx_pos_inv_pend_sku", "idx_pos_inv_pend_estado", "idx_pos_inv_pend_notificacion", "idx_pos_inv_pend_mov_ajuste"),
            "erp_pos_inventario_pendientes_eventos" => array("idx_pos_inv_pend_evt_pendiente", "idx_pos_inv_pend_evt_tipo")
        );
        $resultado = array("tablas" => array(), "columnas" => array(), "indices" => array());
        foreach ($columnas as $tabla => $listaColumnas) {
            $resultado["tablas"][] = array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla));
            foreach ($listaColumnas as $columna) {
                $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
            }
        }
        foreach ($indices as $tabla => $listaIndices) {
            foreach ($listaIndices as $indice) {
                $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
            }
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de inventario pendiente POS generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: preparar el contrato canonico CRM para asignaciones de listas de precios.
     * Impacto: Ventas/Listas de precios; no crea listas ni cambia precios por si mismo.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa previa.
     */
    public function planActualizarListasPreciosCrm($ejecutar = false) {
        $plan = array();
        $plan[] = $this->agregarColumnaSiNoExiste("erp_clientes_listas_precios", "id_cliente_crm", "BIGINT NULL AFTER `id_cliente`", $ejecutar);
        $plan[] = $this->modificarColumna("erp_clientes_listas_precios", "id_cliente", "INT NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_clientes_listas_precios", "idx_cliente_lista_cliente_crm", "KEY `idx_cliente_lista_cliente_crm` (`id_cliente_crm`, `estatus`, `prioridad`)", $ejecutar);
        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: auditar si la asignacion cliente-lista ya soporta CRM canonico.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no modifica listas, clientes ni ventas.
     * Contrato: devuelve columnas/indices necesarios para resolver prioridad por cliente CRM.
     */
    public function auditarListasPreciosCrm() {
        $tabla = "erp_clientes_listas_precios";
        $columnas = array("id_cliente_lista_precio", "id_cliente", "id_cliente_crm", "id_lista_precio", "prioridad", "fecha_inicio", "fecha_fin", "estatus");
        $indices = array("idx_cliente_lista_cliente", "idx_cliente_lista_cliente_crm", "idx_cliente_lista_lista");
        $resultado = array(
            "tablas" => array(array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla))),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($columnas as $columna) {
            $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
        }
        foreach ($indices as $indice) {
            $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria CRM/listas de precios generada",
            "depurar" => $resultado
        );
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: preparar auditoria comercial profunda para futuros cambios de listas de precios.
     * Impacto: permite registrar antes/despues, motivo, usuario y entidad afectada sin modificar ventas pasadas.
     * Contrato: con $ejecutar=false solo genera SQL; con true requiere autorizacion externa y respaldo.
     */
    public function planActualizarAuditoriaListasPrecios($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();
        $plan[] = $this->crearTablaSiNoExiste("erp_listas_precios_eventos", array(
            "`id_evento_lista_precio` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_lista_precio` INT NULL",
            "`id_lista_precio_detalle` BIGINT NULL",
            "`id_cliente_lista_precio` BIGINT NULL",
            "`entidad` VARCHAR(60) NOT NULL",
            "`entidad_id` VARCHAR(80) NULL",
            "`accion` VARCHAR(80) NOT NULL",
            "`tipo_evento` VARCHAR(80) NOT NULL DEFAULT 'operacion'",
            "`resultado` VARCHAR(40) NOT NULL DEFAULT 'ok'",
            "`resumen` VARCHAR(255) NULL",
            "`motivo` TEXT NULL",
            "`datos_antes` TEXT NULL",
            "`datos_despues` TEXT NULL",
            "`origen` VARCHAR(60) NOT NULL DEFAULT 'erp_ventas_listas_precios'",
            "`ip` VARCHAR(80) NULL",
            "`user_agent` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_evento_lista_precio`)",
            "KEY `idx_lp_evt_lista` (`id_lista_precio`, `fecha_registro`)",
            "KEY `idx_lp_evt_detalle` (`id_lista_precio_detalle`, `fecha_registro`)",
            "KEY `idx_lp_evt_cliente_lista` (`id_cliente_lista_precio`, `fecha_registro`)",
            "KEY `idx_lp_evt_entidad` (`entidad`, `entidad_id`, `fecha_registro`)",
            "KEY `idx_lp_evt_accion` (`accion`, `resultado`, `fecha_registro`)",
            "KEY `idx_lp_evt_usuario` (`creado_por`, `fecha_registro`)"
        ), $opciones, $ejecutar);
        return $plan;
    }

    /**
     * Documentacion IA: Codex GPT-5, 2026-07-12.
     * Proposito: auditar estructura de eventos comerciales de listas de precios.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA; no crea auditorias ni cambia precios.
     * Contrato: devuelve cobertura de tabla, columnas e indices requeridos para trazabilidad.
     */
    public function auditarAuditoriaListasPrecios() {
        $tabla = "erp_listas_precios_eventos";
        $columnas = array(
            "id_evento_lista_precio", "id_lista_precio", "id_lista_precio_detalle", "id_cliente_lista_precio",
            "entidad", "entidad_id", "accion", "tipo_evento", "resultado", "resumen", "motivo",
            "datos_antes", "datos_despues", "origen", "ip", "user_agent", "creado_por", "fecha_registro"
        );
        $indices = array(
            "idx_lp_evt_lista", "idx_lp_evt_detalle", "idx_lp_evt_cliente_lista",
            "idx_lp_evt_entidad", "idx_lp_evt_accion", "idx_lp_evt_usuario"
        );
        $resultado = array(
            "tablas" => array(array("tabla" => $tabla, "existe" => $this->tablaExiste($tabla))),
            "columnas" => array(),
            "indices" => array()
        );
        foreach ($columnas as $columna) {
            $resultado["columnas"][] = array("tabla" => $tabla, "columna" => $columna, "existe" => $this->columnaExiste($tabla, $columna));
        }
        foreach ($indices as $indice) {
            $resultado["indices"][] = array("tabla" => $tabla, "indice" => $indice, "existe" => $this->indiceExiste($tabla, $indice));
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de eventos de listas de precios generada",
            "depurar" => $resultado
        );
    }
    /**
     * Documentacion IA: Codex GPT-5, 2026-06-26.
     * Proposito: resumir cobertura de tablas del diseno POS para auditoria previa.
     * Impacto: solo lectura sobre INFORMATION_SCHEMA.
     * Contrato: no crea ni modifica estructura.
     */
    public function auditarVentasPos($alcance = "base") {
        $alcance = $alcance === "base" ? "base" : "expandido";
        $tablas = array(
            "erp_pos_cajas",
            "erp_pos_terminales",
            "erp_pos_usuarios_cajas",
            "erp_pos_turnos",
            "erp_pos_movimientos_caja",
            "erp_ventas",
            "erp_ventas_detalle",
            "erp_ventas_detalle_inventario",
            "erp_ventas_pagos",
            "erp_ventas_devoluciones",
            "erp_ventas_devoluciones_detalle"
        );

        if ($alcance === "expandido") {
            $tablas = array_merge($tablas, array(
            "erp_ventas_eventos",
            "erp_ventas_politicas_apartado",
            "erp_clientes",
            "erp_clientes_identificadores",
            "erp_listas_precios",
            "erp_listas_precios_detalle",
            "erp_clientes_listas_precios",
            "erp_pos_atenciones",
            "erp_pos_atenciones_detalle",
            "erp_pos_atenciones_pagos_temporales"
            ));
        }

        $resultado = array();
        foreach ($tablas as $tabla) {
            $resultado[] = array(
                "tabla" => $tabla,
                "existe" => $this->tablaExiste($tabla)
            );
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Auditoria de esquema Ventas/POS generada",
            "depurar" => $resultado,
            "alcance" => $alcance
        );
    }
}
