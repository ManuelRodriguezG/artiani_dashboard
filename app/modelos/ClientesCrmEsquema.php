<?php

class ClientesCrmEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: proponer el esquema canonico CRM Clientes sin depender de tablas legacy.
   * Impacto: CRM, POS, Ventas, Ecommerce y Postventa consumiran esta identidad por contrato.
   * Contrato: con $ejecutar=false solo genera SQL; no migra ni toca datos antiguos.
   */
  public function planActualizarClientesCrm($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_maestro", array(
      "`id_cliente_crm` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo_cliente` VARCHAR(60) NOT NULL",
      "`tipo_cliente` VARCHAR(30) NOT NULL DEFAULT 'persona'",
      "`nombre_publico` VARCHAR(220) NOT NULL",
      "`nombre_legal` VARCHAR(255) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`calidad_datos` VARCHAR(30) NOT NULL DEFAULT 'express'",
      "`origen_alta` VARCHAR(40) NOT NULL DEFAULT 'crm'",
      "`id_sucursal_alta` INT NULL",
      "`id_lista_precio_default` INT NULL",
      "`id_segmento_default` BIGINT NULL",
      "`fecha_ultima_compra` DATETIME NULL",
      "`total_compras_historico` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`observaciones_operativas` TEXT NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_crm`)",
      "UNIQUE KEY `idx_crm_cliente_codigo` (`codigo_cliente`)",
      "KEY `idx_crm_cliente_nombre` (`nombre_publico`)",
      "KEY `idx_crm_cliente_estatus` (`estatus`, `calidad_datos`)",
      "KEY `idx_crm_cliente_lista` (`id_lista_precio_default`)",
      "KEY `idx_crm_cliente_segmento` (`id_segmento_default`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_identificadores", array(
      "`id_cliente_identificador` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL",
      "`valor` VARCHAR(220) NOT NULL",
      "`valor_normalizado` VARCHAR(220) NOT NULL",
      "`principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`verificado` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_verificacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_identificador`)",
      "KEY `idx_crm_ident_cliente` (`id_cliente_crm`, `estatus`)",
      "UNIQUE KEY `idx_crm_ident_unico_activo` (`tipo`, `valor_normalizado`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_contactos", array(
      "`id_cliente_contacto` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL",
      "`etiqueta` VARCHAR(80) NULL",
      "`valor` VARCHAR(220) NOT NULL",
      "`valor_normalizado` VARCHAR(220) NULL",
      "`nombre_contacto` VARCHAR(180) NULL",
      "`principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`permite_contacto` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_contacto`)",
      "KEY `idx_crm_contacto_cliente` (`id_cliente_crm`, `estatus`)",
      "KEY `idx_crm_contacto_busqueda` (`tipo`, `valor_normalizado`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_direcciones", array(
      "`id_cliente_direccion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'entrega'",
      "`alias` VARCHAR(120) NULL",
      "`pais` VARCHAR(80) NULL",
      "`estado` VARCHAR(120) NULL",
      "`ciudad` VARCHAR(120) NULL",
      "`municipio` VARCHAR(120) NULL",
      "`colonia` VARCHAR(180) NULL",
      "`calle` VARCHAR(220) NULL",
      "`numero_exterior` VARCHAR(60) NULL",
      "`numero_interior` VARCHAR(60) NULL",
      "`codigo_postal` VARCHAR(20) NULL",
      "`referencias` TEXT NULL",
      "`principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_direccion`)",
      "KEY `idx_crm_direccion_cliente` (`id_cliente_crm`, `tipo`, `estatus`)",
      "KEY `idx_crm_direccion_cp` (`codigo_postal`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_fiscales", array(
      "`id_cliente_fiscal` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`rfc` VARCHAR(20) NOT NULL",
      "`razon_social` VARCHAR(255) NOT NULL",
      "`regimen_fiscal` VARCHAR(120) NULL",
      "`uso_cfdi_default` VARCHAR(120) NULL",
      "`codigo_postal_fiscal` VARCHAR(20) NULL",
      "`principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_fiscal`)",
      "KEY `idx_crm_fiscal_cliente` (`id_cliente_crm`, `estatus`)",
      "KEY `idx_crm_fiscal_rfc` (`rfc`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_segmentos", array(
      "`id_segmento_crm` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(60) NOT NULL",
      "`nombre` VARCHAR(160) NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'comercial'",
      "`descripcion` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_segmento_crm`)",
      "UNIQUE KEY `idx_crm_segmento_codigo` (`codigo`)",
      "KEY `idx_crm_segmento_estatus` (`estatus`, `tipo`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_segmentos_rel", array(
      "`id_cliente_segmento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`id_segmento_crm` BIGINT NOT NULL",
      "`principal` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_fin` DATETIME NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_cliente_segmento`)",
      "KEY `idx_crm_cliente_segmento_cliente` (`id_cliente_crm`, `estatus`)",
      "KEY `idx_crm_cliente_segmento_segmento` (`id_segmento_crm`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_consentimientos", array(
      "`id_cliente_consentimiento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(60) NOT NULL",
      "`otorgado` TINYINT(1) NOT NULL DEFAULT 0",
      "`medio` VARCHAR(60) NULL",
      "`evidencia` VARCHAR(500) NULL",
      "`fecha_consentimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_revocacion` DATETIME NULL",
      "`registrado_por` INT NULL",
      "PRIMARY KEY (`id_cliente_consentimiento`)",
      "KEY `idx_crm_consentimiento_cliente` (`id_cliente_crm`, `tipo`)",
      "KEY `idx_crm_consentimiento_estado` (`tipo`, `otorgado`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_notas", array(
      "`id_cliente_nota` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(50) NOT NULL DEFAULT 'operativa'",
      "`nota` TEXT NOT NULL",
      "`visibilidad` VARCHAR(40) NOT NULL DEFAULT 'interna'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`cancelado_por` INT NULL",
      "`fecha_cancelacion` DATETIME NULL",
      "`motivo_cancelacion` TEXT NULL",
      "PRIMARY KEY (`id_cliente_nota`)",
      "KEY `idx_crm_nota_cliente` (`id_cliente_crm`, `estatus`)",
      "KEY `idx_crm_nota_tipo` (`tipo`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_eventos", array(
      "`id_cliente_evento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo_evento` VARCHAR(80) NOT NULL",
      "`origen_modulo` VARCHAR(60) NULL",
      "`origen_tipo` VARCHAR(80) NULL",
      "`origen_id` VARCHAR(80) NULL",
      "`resumen` VARCHAR(255) NULL",
      "`datos_snapshot` JSON NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cliente_evento`)",
      "KEY `idx_crm_evento_cliente` (`id_cliente_crm`, `tipo_evento`)",
      "KEY `idx_crm_evento_origen` (`origen_modulo`, `origen_tipo`, `origen_id`)",
      "KEY `idx_crm_evento_fecha` (`fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_vinculos_externos", array(
      "`id_cliente_vinculo` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`sistema_origen` VARCHAR(60) NOT NULL",
      "`entidad_origen` VARCHAR(80) NOT NULL",
      "`id_origen` VARCHAR(120) NOT NULL",
      "`confianza` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`datos_snapshot` JSON NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cliente_vinculo`)",
      "UNIQUE KEY `idx_crm_vinculo_origen` (`sistema_origen`, `entidad_origen`, `id_origen`, `estatus`)",
      "KEY `idx_crm_vinculo_cliente` (`id_cliente_crm`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_fusiones", array(
      "`id_cliente_fusion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_destino` BIGINT NOT NULL",
      "`id_cliente_origen` BIGINT NOT NULL",
      "`motivo` TEXT NOT NULL",
      "`datos_origen_snapshot` JSON NULL",
      "`autorizado_por` INT NULL",
      "`fecha_fusion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cliente_fusion`)",
      "KEY `idx_crm_fusion_destino` (`id_cliente_destino`)",
      "KEY `idx_crm_fusion_origen` (`id_cliente_origen`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan CRM Clientes ejecutado" : "Plan CRM Clientes generado sin ejecutar",
      "depurar" => $plan
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: proponer seguimiento operativo CRM sin mezclarlo con DDL base ya aplicado.
   * Impacto: habilita futuras tareas, llamadas, WhatsApp, recordatorios y seguimiento comercial/postventa.
   * Contrato: con $ejecutar=false solo genera SQL; no crea tareas ni modifica clientes.
   */
  public function planActualizarSeguimientoClientesCrm($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_interacciones", array(
      "`id_cliente_interaccion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(50) NOT NULL",
      "`canal` VARCHAR(50) NULL",
      "`direccion` VARCHAR(30) NOT NULL DEFAULT 'saliente'",
      "`resultado` VARCHAR(60) NULL",
      "`resumen` VARCHAR(255) NULL",
      "`detalle` TEXT NULL",
      "`origen_modulo` VARCHAR(60) NULL",
      "`origen_tipo` VARCHAR(80) NULL",
      "`origen_id` VARCHAR(120) NULL",
      "`fecha_interaccion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cliente_interaccion`)",
      "KEY `idx_crm_interaccion_cliente` (`id_cliente_crm`, `fecha_interaccion`)",
      "KEY `idx_crm_interaccion_tipo` (`tipo`, `canal`, `resultado`)",
      "KEY `idx_crm_interaccion_origen` (`origen_modulo`, `origen_tipo`, `origen_id`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_tareas", array(
      "`id_cliente_tarea` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(60) NOT NULL",
      "`prioridad` VARCHAR(30) NOT NULL DEFAULT 'normal'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`titulo` VARCHAR(180) NOT NULL",
      "`descripcion` TEXT NULL",
      "`fecha_programada` DATETIME NULL",
      "`fecha_vencimiento` DATETIME NULL",
      "`fecha_cierre` DATETIME NULL",
      "`resultado_cierre` VARCHAR(80) NULL",
      "`id_usuario_responsable` INT NULL",
      "`origen_modulo` VARCHAR(60) NULL",
      "`origen_tipo` VARCHAR(80) NULL",
      "`origen_id` VARCHAR(120) NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_tarea`)",
      "KEY `idx_crm_tarea_cliente` (`id_cliente_crm`, `estatus`, `fecha_vencimiento`)",
      "KEY `idx_crm_tarea_responsable` (`id_usuario_responsable`, `estatus`, `fecha_vencimiento`)",
      "KEY `idx_crm_tarea_tipo` (`tipo`, `prioridad`, `estatus`)",
      "KEY `idx_crm_tarea_origen` (`origen_modulo`, `origen_tipo`, `origen_id`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan CRM Seguimiento ejecutado" : "Plan CRM Seguimiento generado sin ejecutar",
      "depurar" => $plan
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: proponer condiciones comerciales CRM sin mezclarlas con POS ni ventas.
   * Impacto: prepara preferencias, listas, restricciones, recompensas y credito futuro por cliente.
   * Contrato: con $ejecutar=false solo genera SQL; no modifica clientes ni precios.
   */
  public function planActualizarComercialClientesCrm($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_condiciones", array(
      "`id_cliente_condicion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`id_lista_precio_default` INT NULL",
      "`credito_habilitado` TINYINT(1) NOT NULL DEFAULT 0",
      "`limite_credito` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`dias_credito` INT NOT NULL DEFAULT 0",
      "`permite_recompensas` TINYINT(1) NOT NULL DEFAULT 1",
      "`permite_garantia_extendida` TINYINT(1) NOT NULL DEFAULT 1",
      "`bloqueo_comercial` TINYINT(1) NOT NULL DEFAULT 0",
      "`motivo_bloqueo` TEXT NULL",
      "`preferencias` JSON NULL",
      "`restricciones` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_condicion`)",
      "UNIQUE KEY `idx_crm_condicion_cliente` (`id_cliente_crm`)",
      "KEY `idx_crm_condicion_lista` (`id_lista_precio_default`, `estatus`)",
      "KEY `idx_crm_condicion_credito` (`credito_habilitado`, `estatus`)",
      "KEY `idx_crm_condicion_bloqueo` (`bloqueo_comercial`, `estatus`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan CRM Comercial ejecutado" : "Plan CRM Comercial generado sin ejecutar",
      "depurar" => $plan
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-30
   * Proposito: proponer esquema CRM de recompensas sin depender de POS/Ventas.
   * Impacto: prepara cuentas, puntos y beneficios por cliente canonico.
   * Contrato: con $ejecutar=false solo genera SQL; no otorga puntos ni modifica clientes.
   */
  public function planActualizarRecompensasClientesCrm($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("crm_recompensas_programas", array(
      "`id_programa_recompensa` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(60) NOT NULL",
      "`nombre` VARCHAR(160) NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'puntos'",
      "`reglas` JSON NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_programa_recompensa`)",
      "UNIQUE KEY `idx_crm_recompensa_programa_codigo` (`codigo`)",
      "KEY `idx_crm_recompensa_programa_estatus` (`estatus`, `tipo`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_recompensas_cuentas", array(
      "`id_cliente_recompensa_cuenta` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`id_programa_recompensa` BIGINT NOT NULL",
      "`saldo_puntos` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_monetario_equivalente` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`nivel` VARCHAR(60) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_alta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_recompensa_cuenta`)",
      "UNIQUE KEY `idx_crm_recompensa_cuenta_cliente_programa` (`id_cliente_crm`, `id_programa_recompensa`)",
      "KEY `idx_crm_recompensa_cuenta_estatus` (`estatus`, `nivel`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_recompensas_movimientos", array(
      "`id_cliente_recompensa_movimiento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_recompensa_cuenta` BIGINT NOT NULL",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL",
      "`puntos` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_resultante` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`origen_modulo` VARCHAR(60) NULL",
      "`origen_tipo` VARCHAR(80) NULL",
      "`origen_id` VARCHAR(120) NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`datos_snapshot` JSON NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'aplicado'",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cliente_recompensa_movimiento`)",
      "KEY `idx_crm_recompensa_mov_cuenta` (`id_cliente_recompensa_cuenta`, `fecha_registro`)",
      "KEY `idx_crm_recompensa_mov_cliente` (`id_cliente_crm`, `tipo`, `fecha_registro`)",
      "KEY `idx_crm_recompensa_mov_origen` (`origen_modulo`, `origen_tipo`, `origen_id`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan CRM Recompensas ejecutado" : "Plan CRM Recompensas generado sin ejecutar",
      "depurar" => $plan
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-06
   * Proposito: proponer esquema CRM de saldos/cuenta corriente de clientes.
   * Impacto: habilita saldo favor, cargos, consumos y ajustes monetarios sin mezclarlos con recompensas.
   * Contrato: con $ejecutar=false solo genera SQL; no crea saldos, no mueve caja y no toca POS/Ventas.
   */
  public function planActualizarSaldosClientesCrm($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_saldos_cuentas", array(
      "`id_cliente_saldo_cuenta` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
      "`saldo_disponible` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_retenido` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_total` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`fecha_apertura` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_cliente_saldo_cuenta`)",
      "UNIQUE KEY `idx_crm_saldo_cuenta_cliente_moneda` (`id_cliente_crm`, `moneda`)",
      "KEY `idx_crm_saldo_cuenta_estatus` (`estatus`, `moneda`)",
      "KEY `idx_crm_saldo_cuenta_saldo` (`saldo_total`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("crm_clientes_saldos_movimientos", array(
      "`id_cliente_saldo_movimiento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_saldo_cuenta` BIGINT NOT NULL",
      "`id_cliente_crm` BIGINT NOT NULL",
      "`folio` VARCHAR(60) NOT NULL",
      "`tipo` VARCHAR(50) NOT NULL",
      "`naturaleza` VARCHAR(20) NOT NULL",
      "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
      "`monto` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_anterior` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`saldo_resultante` DECIMAL(18,6) NOT NULL DEFAULT 0",
      "`origen_modulo` VARCHAR(60) NULL",
      "`origen_tipo` VARCHAR(80) NULL",
      "`origen_id` VARCHAR(120) NULL",
      "`referencia_externa` VARCHAR(180) NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`datos_snapshot` JSON NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'aplicado'",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`cancelado_por` INT NULL",
      "`fecha_cancelacion` DATETIME NULL",
      "`motivo_cancelacion` TEXT NULL",
      "PRIMARY KEY (`id_cliente_saldo_movimiento`)",
      "UNIQUE KEY `idx_crm_saldo_mov_folio` (`folio`)",
      "KEY `idx_crm_saldo_mov_cuenta` (`id_cliente_saldo_cuenta`, `fecha_registro`)",
      "KEY `idx_crm_saldo_mov_cliente` (`id_cliente_crm`, `tipo`, `fecha_registro`)",
      "KEY `idx_crm_saldo_mov_origen` (`origen_modulo`, `origen_tipo`, `origen_id`)",
      "KEY `idx_crm_saldo_mov_estatus` (`estatus`, `naturaleza`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan CRM Saldos ejecutado" : "Plan CRM Saldos generado sin ejecutar",
      "depurar" => $plan
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-06-29
   * Proposito: auditar existencia de tablas canonicas y fuentes legacy de clientes.
   * Impacto: permite decidir migracion sin tocar datos.
   * Contrato: solo lectura sobre INFORMATION_SCHEMA.
   */
  public function auditarClientesCrm() {
    $canonicas = array(
      "crm_clientes_maestro",
      "crm_clientes_identificadores",
      "crm_clientes_contactos",
      "crm_clientes_direcciones",
      "crm_clientes_fiscales",
      "crm_clientes_segmentos",
      "crm_clientes_segmentos_rel",
      "crm_clientes_consentimientos",
      "crm_clientes_notas",
      "crm_clientes_eventos",
      "crm_clientes_vinculos_externos",
      "crm_clientes_fusiones",
      "crm_clientes_condiciones",
      "crm_recompensas_programas",
      "crm_clientes_recompensas_cuentas",
      "crm_clientes_recompensas_movimientos",
      "crm_clientes_saldos_cuentas",
      "crm_clientes_saldos_movimientos",
      "crm_clientes_interacciones",
      "crm_clientes_tareas"
    );
    $fuentes = array(
      "crm_clientes",
      "erp_clientes",
      "erp_clientes_identificadores",
      "ecom_pedidos",
      "ecom_clientes"
    );

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Auditoria CRM Clientes generada",
      "depurar" => array(
        "canonicas" => $this->estadoTablas($canonicas),
        "fuentes_detectadas" => $this->estadoTablas($fuentes),
        "ejecuta_ddl" => false,
        "migra_datos" => false
      )
    );
  }

  private function estadoTablas($tablas) {
    $estado = array();
    foreach ($tablas as $tabla) {
      $estado[] = array(
        "tabla" => $tabla,
        "existe" => $this->tablaExiste($tabla)
      );
    }
    return $estado;
  }
}
