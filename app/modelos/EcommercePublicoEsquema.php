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

  private function tablasEcommercePublico() {
    return array(
      "erp_ecommerce_publicaciones",
      "erp_ecommerce_configuracion",
      "erp_ecommerce_cotizaciones",
      "erp_ecommerce_cotizaciones_detalle",
      "erp_ecommerce_cotizaciones_eventos"
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
