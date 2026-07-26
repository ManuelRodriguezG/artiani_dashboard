<?php

class EcommercePublicoEsquema extends DBSchema {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: generar el plan DDL del catalogo publico ecommerce sin ejecutarlo por defecto.
   * Impacto: Ecommerce publico; prepara publicaciones, cotizaciones, eventos y configuracion sin tocar `ecom_*`.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tablas ni modifica datos.
   */
  public function planActualizarEcommercePublico($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_publicaciones", array(
      "`id_publicacion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_sku` BIGINT NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'catalogo_publico'",
      "`estatus_publicacion` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`slug` VARCHAR(180) NOT NULL",
      "`titulo_publico` VARCHAR(255) NOT NULL",
      "`descripcion_publica` TEXT NULL",
      "`presentacion_publica` VARCHAR(180) NULL",
      "`mascota_especie` VARCHAR(80) NULL",
      "`necesidades_json` TEXT NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`destacado` TINYINT(1) NOT NULL DEFAULT 0",
      "`permite_cotizacion` TINYINT(1) NOT NULL DEFAULT 1",
      "`permite_whatsapp` TINYINT(1) NOT NULL DEFAULT 1",
      "`mostrar_precio` TINYINT(1) NOT NULL DEFAULT 1",
      "`mostrar_disponibilidad` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_publicacion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_publicacion`)",
      "UNIQUE KEY `idx_ecom_publicacion_slug` (`slug`)",
      "UNIQUE KEY `idx_ecom_publicacion_sku_canal` (`id_sku`, `canal`)",
      "KEY `idx_ecom_publicacion_producto` (`id_producto_erp`, `estatus_publicacion`)",
      "KEY `idx_ecom_publicacion_canal_estado` (`canal`, `estatus_publicacion`, `orden`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_configuracion", array(
      "`id_configuracion` BIGINT NOT NULL AUTO_INCREMENT",
      "`clave` VARCHAR(100) NOT NULL",
      "`valor` TEXT NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_configuracion`)",
      "UNIQUE KEY `idx_ecom_config_clave` (`clave`)",
      "KEY `idx_ecom_config_estado` (`estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_cotizaciones", array(
      "`id_cotizacion` BIGINT NOT NULL AUTO_INCREMENT",
      "`folio` VARCHAR(50) NOT NULL",
      "`origen` VARCHAR(40) NOT NULL DEFAULT 'web_publica'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`id_cliente_crm` BIGINT NULL",
      "`nombre_contacto` VARCHAR(220) NULL",
      "`telefono_contacto` VARCHAR(60) NULL",
      "`correo_contacto` VARCHAR(220) NULL",
      "`canal_contacto_preferido` VARCHAR(40) NULL",
      "`mensaje_cliente` TEXT NULL",
      "`moneda` CHAR(3) NOT NULL DEFAULT 'MXN'",
      "`subtotal_estimado` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`total_estimado` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`utm_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_expiracion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cotizacion`)",
      "UNIQUE KEY `idx_ecom_cotizacion_folio` (`folio`)",
      "KEY `idx_ecom_cotizacion_estado_fecha` (`estatus`, `fecha_registro`)",
      "KEY `idx_ecom_cotizacion_cliente` (`id_cliente_crm`, `fecha_registro`)",
      "KEY `idx_ecom_cotizacion_contacto` (`telefono_contacto`, `correo_contacto`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_cotizaciones_detalle", array(
      "`id_cotizacion_detalle` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cotizacion` BIGINT NOT NULL",
      "`renglon` INT NOT NULL DEFAULT 1",
      "`id_publicacion` BIGINT NULL",
      "`id_producto_erp` BIGINT NOT NULL",
      "`id_sku` BIGINT NOT NULL",
      "`sku_snapshot` VARCHAR(150) NOT NULL",
      "`nombre_snapshot` VARCHAR(500) NOT NULL",
      "`presentacion_snapshot` VARCHAR(180) NULL",
      "`precio_snapshot` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`moneda_snapshot` CHAR(3) NOT NULL DEFAULT 'MXN'",
      "`cantidad` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`disponibilidad_snapshot` VARCHAR(40) NULL",
      "`subtotal` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cotizacion_detalle`)",
      "KEY `idx_ecom_cot_det_cotizacion` (`id_cotizacion`, `renglon`)",
      "KEY `idx_ecom_cot_det_sku` (`id_sku`, `estatus`)",
      "KEY `idx_ecom_cot_det_publicacion` (`id_publicacion`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_cotizaciones_eventos", array(
      "`id_cotizacion_evento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cotizacion` BIGINT NOT NULL",
      "`tipo_evento` VARCHAR(60) NOT NULL",
      "`canal` VARCHAR(40) NULL",
      "`resultado` VARCHAR(40) NULL",
      "`detalle_json` TEXT NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cotizacion_evento`)",
      "KEY `idx_ecom_cot_evento_cotizacion` (`id_cotizacion`, `fecha_registro`)",
      "KEY `idx_ecom_cot_evento_tipo` (`tipo_evento`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: auditar si el esquema minimo de ecommerce publico existe.
   * Impacto: permite ver faltantes sin ejecutar DDL.
   * Contrato: solo lectura.
   */
  public function auditarEcommercePublico() {
    $tablas = $this->tablasEcommercePublico();
    $auditoria = array();
    $faltantes = 0;
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "impacto" => $existe ? "Disponible para la Fase 1 de ecommerce publico." : "Falta para operar publicaciones/cotizaciones del catalogo vivo."
      );
      if (!$existe) {
        $faltantes++;
      }
    }

    return array(
      "error" => false,
      "tipo" => $faltantes > 0 ? "warning" : "success",
      "mensaje" => $faltantes > 0 ? "Esquema ecommerce publico pendiente" : "Esquema ecommerce publico disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($tablas),
        "tablas_faltantes" => $faltantes,
        "auditoria" => $auditoria,
        "no_toca_ecom_legacy" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-25
   * Proposito: generar plan DDL para canales/API keys de ecommerce sin ejecutarlo.
   * Impacto: prepara consumo seguro por frontend propio y partners mayoristas sin romper Fase 1.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tokens ni modifica permisos.
   */
  public function planActualizarCanalesApi($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_canales_api", array(
      "`id_canal_api` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(80) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`tipo_canal` VARCHAR(40) NOT NULL DEFAULT 'frontend_propio'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`razon_social` VARCHAR(220) NULL",
      "`contacto_nombre` VARCHAR(180) NULL",
      "`contacto_email` VARCHAR(180) NULL",
      "`contacto_telefono` VARCHAR(80) NULL",
      "`url_publica` VARCHAR(255) NULL",
      "`allowed_origins` TEXT NULL",
      "`allowed_ips` TEXT NULL",
      "`scopes_json` TEXT NULL",
      "`politica_precios` VARCHAR(50) NOT NULL DEFAULT 'publico'",
      "`canal_publicacion` VARCHAR(50) NOT NULL DEFAULT 'catalogo_publico'",
      "`puede_ver_precio` TINYINT(1) NOT NULL DEFAULT 1",
      "`puede_ver_disponibilidad` TINYINT(1) NOT NULL DEFAULT 1",
      "`puede_cotizar` TINYINT(1) NOT NULL DEFAULT 1",
      "`puede_registrar_cotizacion` TINYINT(1) NOT NULL DEFAULT 0",
      "`mostrar_stock_exacto` TINYINT(1) NOT NULL DEFAULT 0",
      "`rate_limit_minuto` INT NOT NULL DEFAULT 60",
      "`rate_limit_dia` INT NOT NULL DEFAULT 5000",
      "`observaciones` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_canal_api`)",
      "UNIQUE KEY `idx_ecom_canal_api_codigo` (`codigo`)",
      "KEY `idx_ecom_canal_api_tipo_estado` (`tipo_canal`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_api_credenciales", array(
      "`id_credencial_api` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_canal_api` BIGINT NOT NULL",
      "`api_key_prefix` VARCHAR(32) NOT NULL",
      "`api_key_hash` VARCHAR(255) NOT NULL",
      "`api_secret_hash` VARCHAR(255) NULL",
      "`algoritmo_firma` VARCHAR(40) NOT NULL DEFAULT 'hmac_sha256'",
      "`scopes_json` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_emision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_expiracion` DATETIME NULL",
      "`fecha_ultimo_uso` DATETIME NULL",
      "`rotada_por` BIGINT NULL",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_credencial_api`)",
      "KEY `idx_ecom_api_cred_canal` (`id_canal_api`, `estatus`)",
      "KEY `idx_ecom_api_cred_prefix` (`api_key_prefix`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_canal_publicaciones", array(
      "`id_canal_publicacion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_canal_api` BIGINT NOT NULL",
      "`id_publicacion` BIGINT NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`precio_modo` VARCHAR(40) NOT NULL DEFAULT 'publico'",
      "`id_lista_precio` BIGINT NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`destacado` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_canal_publicacion`)",
      "UNIQUE KEY `idx_ecom_canal_pub_unico` (`id_canal_api`, `id_publicacion`)",
      "KEY `idx_ecom_canal_pub_estado` (`id_canal_api`, `estatus`, `orden`)",
      "KEY `idx_ecom_canal_pub_publicacion` (`id_publicacion`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_api_nonces", array(
      "`id_nonce_api` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_canal_api` BIGINT NOT NULL",
      "`nonce_hash` VARCHAR(255) NOT NULL",
      "`timestamp_cliente` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_expiracion` DATETIME NOT NULL",
      "PRIMARY KEY (`id_nonce_api`)",
      "UNIQUE KEY `idx_ecom_api_nonce_unico` (`id_canal_api`, `nonce_hash`)",
      "KEY `idx_ecom_api_nonce_expira` (`fecha_expiracion`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_api_logs", array(
      "`id_api_log` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_canal_api` BIGINT NULL",
      "`api_key_prefix` VARCHAR(32) NULL",
      "`request_id` VARCHAR(80) NULL",
      "`metodo` VARCHAR(10) NOT NULL",
      "`endpoint` VARCHAR(255) NOT NULL",
      "`http_status` INT NULL",
      "`resultado` VARCHAR(40) NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`detalle_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_api_log`)",
      "KEY `idx_ecom_api_log_canal_fecha` (`id_canal_api`, `fecha_registro`)",
      "KEY `idx_ecom_api_log_endpoint_fecha` (`endpoint`, `fecha_registro`)",
      "KEY `idx_ecom_api_log_prefix` (`api_key_prefix`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-25
   * Proposito: auditar si existe la capa de canales/API keys ecommerce.
   * Impacto: permite avanzar partner API sin ejecutar DDL ni exponer tokens.
   * Contrato: solo lectura.
   */
  public function auditarCanalesApi() {
    $tablas = $this->tablasCanalesApi();
    $auditoria = array();
    $faltantes = 0;
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "impacto" => $existe ? "Disponible para seguridad multi-canal ecommerce." : "Falta para tokens, partners, scopes, rate limit o auditoria API."
      );
      if (!$existe) {
        $faltantes++;
      }
    }

    return array(
      "error" => false,
      "tipo" => $faltantes > 0 ? "warning" : "success",
      "mensaje" => $faltantes > 0 ? "Capa canales/API ecommerce pendiente" : "Capa canales/API ecommerce disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($tablas),
        "tablas_faltantes" => $faltantes,
        "auditoria" => $auditoria,
        "no_genera_secretos" => true,
        "no_rompe_frontend_actual" => true
      )
    );
  }

  private function tablasEcommercePublico() {
    return array(
      "erp_ecommerce_publicaciones",
      "erp_ecommerce_configuracion",
      "erp_ecommerce_cotizaciones",
      "erp_ecommerce_cotizaciones_detalle",
      "erp_ecommerce_cotizaciones_eventos"
    );
  }

  private function tablasCanalesApi() {
    return array(
      "erp_ecommerce_canales_api",
      "erp_ecommerce_api_credenciales",
      "erp_ecommerce_canal_publicaciones",
      "erp_ecommerce_api_nonces",
      "erp_ecommerce_api_logs"
    );
  }

  private function respuestaPlan($plan, $ejecutar) {
    $pendientes = 0;
    $errores = 0;
    foreach ($plan as $item) {
      if (!empty($item["error"])) {
        $errores++;
      }
      $depurar = isset($item["depurar"]) && is_array($item["depurar"]) ? $item["depurar"] : array();
      if (isset($depurar["sql"]) && empty($depurar["ejecutado"])) {
        $pendientes++;
      }
    }
    return array(
      "error" => $errores > 0,
      "tipo" => $errores > 0 ? "warning" : ($pendientes > 0 ? "info" : "success"),
      "mensaje" => $ejecutar ? "Plan de ecommerce publico procesado" : "Plan DDL ecommerce publico generado sin ejecutar",
      "depurar" => array(
        "ejecutar" => $ejecutar,
        "read_only" => !$ejecutar,
        "ddl_total" => count($plan),
        "ddl_pendientes" => $pendientes,
        "errores" => $errores,
        "plan" => $plan,
        "guardrail" => array(
          "no_ejecutar_sin_autorizacion" => true,
          "no_tocar_ecom_legacy" => true,
          "no_mover_inventario" => true,
          "no_crear_cotizaciones_reales" => true
        )
      )
    );
  }
}
