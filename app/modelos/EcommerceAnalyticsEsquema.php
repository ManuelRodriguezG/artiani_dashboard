<?php

class EcommerceAnalyticsEsquema extends DBSchema {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: generar el plan DDL del modulo Ecommerce / Analytics sin ejecutarlo por defecto.
   * Impacto: prepara sesiones, eventos, busquedas, conversiones y resumen diario anonimos sin tocar ventas ni inventario.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tablas ni migra datos.
   */
  public function planActualizarEcommerceAnalytics($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_analytics_sesiones", array(
      "`id_analytics_sesion` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`primer_ruta` VARCHAR(255) NULL",
      "`ultimo_ruta` VARCHAR(255) NULL",
      "`referrer` VARCHAR(255) NULL",
      "`utm_source` VARCHAR(120) NULL",
      "`utm_medium` VARCHAR(120) NULL",
      "`utm_campaign` VARCHAR(160) NULL",
      "`dispositivo_aproximado` VARCHAR(40) NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_ultima_actividad` DATETIME NULL",
      "`eventos_total` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "PRIMARY KEY (`id_analytics_sesion`)",
      "UNIQUE KEY `idx_ecom_an_sesion_hash` (`session_id_hash`)",
      "KEY `idx_ecom_an_sesion_fecha` (`fecha_inicio`, `canal`)",
      "KEY `idx_ecom_an_sesion_utm` (`utm_source`, `utm_medium`, `utm_campaign`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_analytics_eventos", array(
      "`id_analytics_evento` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`tipo_evento` VARCHAR(60) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`ruta` VARCHAR(255) NULL",
      "`referrer` VARCHAR(255) NULL",
      "`utm_source` VARCHAR(120) NULL",
      "`utm_medium` VARCHAR(120) NULL",
      "`utm_campaign` VARCHAR(160) NULL",
      "`dispositivo_aproximado` VARCHAR(40) NULL",
      "`mascota` VARCHAR(80) NULL",
      "`necesidad` VARCHAR(80) NULL",
      "`id_publicacion` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`slug` VARCHAR(180) NULL",
      "`metadata_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_analytics_evento`)",
      "KEY `idx_ecom_an_evento_fecha` (`fecha_registro`, `tipo_evento`)",
      "KEY `idx_ecom_an_evento_session` (`session_id_hash`, `fecha_registro`)",
      "KEY `idx_ecom_an_evento_ruta` (`ruta`, `fecha_registro`)",
      "KEY `idx_ecom_an_evento_producto` (`id_publicacion`, `id_sku`, `tipo_evento`, `fecha_registro`)",
      "KEY `idx_ecom_an_evento_mascota` (`mascota`, `necesidad`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_analytics_busquedas", array(
      "`id_analytics_busqueda` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`query` VARCHAR(255) NOT NULL",
      "`query_normalizada` VARCHAR(255) NOT NULL",
      "`ruta` VARCHAR(255) NULL",
      "`mascota` VARCHAR(80) NULL",
      "`necesidad` VARCHAR(80) NULL",
      "`resultados_total` INT NOT NULL DEFAULT 0",
      "`sin_resultados` TINYINT(1) NOT NULL DEFAULT 0",
      "`filtros_json` TEXT NULL",
      "`metadata_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_analytics_busqueda`)",
      "KEY `idx_ecom_an_busq_query` (`query_normalizada`, `fecha_registro`)",
      "KEY `idx_ecom_an_busq_sin_resultados` (`sin_resultados`, `fecha_registro`)",
      "KEY `idx_ecom_an_busq_mascota` (`mascota`, `necesidad`, `fecha_registro`)",
      "KEY `idx_ecom_an_busq_session` (`session_id_hash`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_analytics_conversiones", array(
      "`id_analytics_conversion` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`tipo_conversion` VARCHAR(60) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`id_publicacion` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`slug` VARCHAR(180) NULL",
      "`ruta_origen` VARCHAR(255) NULL",
      "`etapa_origen` VARCHAR(60) NULL",
      "`metadata_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_analytics_conversion`)",
      "KEY `idx_ecom_an_conv_fecha` (`fecha_registro`, `tipo_conversion`)",
      "KEY `idx_ecom_an_conv_session` (`session_id_hash`, `fecha_registro`)",
      "KEY `idx_ecom_an_conv_producto` (`id_publicacion`, `id_sku`, `tipo_conversion`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_analytics_resumen_diario", array(
      "`id_analytics_resumen` BIGINT NOT NULL AUTO_INCREMENT",
      "`fecha` DATE NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`sesiones_total` INT NOT NULL DEFAULT 0",
      "`eventos_total` INT NOT NULL DEFAULT 0",
      "`page_views` INT NOT NULL DEFAULT 0",
      "`productos_vistos` INT NOT NULL DEFAULT 0",
      "`busquedas_total` INT NOT NULL DEFAULT 0",
      "`busquedas_sin_resultados` INT NOT NULL DEFAULT 0",
      "`add_to_quote_total` INT NOT NULL DEFAULT 0",
      "`dryrun_total` INT NOT NULL DEFAULT 0",
      "`preflight_total` INT NOT NULL DEFAULT 0",
      "`whatsapp_total` INT NOT NULL DEFAULT 0",
      "`facturacion_view_total` INT NOT NULL DEFAULT 0",
      "`facturacion_submit_total` INT NOT NULL DEFAULT 0",
      "`metadata_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_analytics_resumen`)",
      "UNIQUE KEY `idx_ecom_an_resumen_fecha_canal` (`fecha`, `canal`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: auditar si existe la capa Ecommerce / Analytics dedicada.
   * Impacto: permite revisar readiness sin ejecutar DDL ni activar tracking real.
   * Contrato: solo lectura.
   */
  public function auditarEcommerceAnalytics() {
    $auditoria = array();
    $faltantes = 0;
    $columnasFaltantesTotal = 0;
    $indicesFaltantesTotal = 0;
    $columnasEsperadas = $this->columnasAnalytics();
    $indicesEsperados = $this->indicesAnalytics();
    foreach ($this->tablasAnalytics() as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $columnasFaltantes = array();
      $indicesFaltantes = array();
      if ($existe) {
        foreach ($columnasEsperadas[$tabla] as $columna) {
          if (!$this->columnaExiste($tabla, $columna)) { $columnasFaltantes[] = $columna; }
        }
        foreach ($indicesEsperados[$tabla] as $indice) {
          if (!$this->indiceExiste($tabla, $indice)) { $indicesFaltantes[] = $indice; }
        }
      }
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "columnas_faltantes" => $columnasFaltantes,
        "indices_faltantes" => $indicesFaltantes,
        "impacto" => $existe ? "Disponible para Ecommerce / Analytics." : "Falta para persistencia analytics anonima."
      );
      if (!$existe) { $faltantes++; }
      $columnasFaltantesTotal += count($columnasFaltantes);
      $indicesFaltantesTotal += count($indicesFaltantes);
    }

    return array(
      "error" => false,
      "tipo" => ($faltantes > 0 || $columnasFaltantesTotal > 0 || $indicesFaltantesTotal > 0) ? "warning" : "success",
      "mensaje" => ($faltantes > 0 || $columnasFaltantesTotal > 0 || $indicesFaltantesTotal > 0) ? "Esquema Ecommerce / Analytics pendiente" : "Esquema Ecommerce / Analytics disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($this->tablasAnalytics()),
        "tablas_faltantes" => $faltantes,
        "columnas_faltantes_total" => $columnasFaltantesTotal,
        "indices_faltantes_total" => $indicesFaltantesTotal,
        "auditoria" => $auditoria,
        "guardrails" => $this->guardrails()
      )
    );
  }

  private function tablasAnalytics() {
    return array(
      "erp_ecommerce_analytics_sesiones",
      "erp_ecommerce_analytics_eventos",
      "erp_ecommerce_analytics_busquedas",
      "erp_ecommerce_analytics_conversiones",
      "erp_ecommerce_analytics_resumen_diario"
    );
  }

  private function columnasAnalytics() {
    return array(
      "erp_ecommerce_analytics_sesiones" => array("id_analytics_sesion", "session_id_hash", "canal", "primer_ruta", "ultimo_ruta", "referrer", "utm_source", "utm_medium", "utm_campaign", "dispositivo_aproximado", "ip_hash", "user_agent_hash", "fecha_inicio", "fecha_ultima_actividad", "eventos_total", "estatus"),
      "erp_ecommerce_analytics_eventos" => array("id_analytics_evento", "session_id_hash", "tipo_evento", "canal", "ruta", "referrer", "utm_source", "utm_medium", "utm_campaign", "dispositivo_aproximado", "mascota", "necesidad", "id_publicacion", "id_sku", "slug", "metadata_json", "ip_hash", "user_agent_hash", "fecha_registro"),
      "erp_ecommerce_analytics_busquedas" => array("id_analytics_busqueda", "session_id_hash", "canal", "query", "query_normalizada", "ruta", "mascota", "necesidad", "resultados_total", "sin_resultados", "filtros_json", "metadata_json", "ip_hash", "user_agent_hash", "fecha_registro"),
      "erp_ecommerce_analytics_conversiones" => array("id_analytics_conversion", "session_id_hash", "tipo_conversion", "canal", "id_publicacion", "id_sku", "slug", "ruta_origen", "etapa_origen", "metadata_json", "fecha_registro"),
      "erp_ecommerce_analytics_resumen_diario" => array("id_analytics_resumen", "fecha", "canal", "sesiones_total", "eventos_total", "page_views", "productos_vistos", "busquedas_total", "busquedas_sin_resultados", "add_to_quote_total", "dryrun_total", "preflight_total", "whatsapp_total", "facturacion_view_total", "facturacion_submit_total", "metadata_json", "fecha_registro", "fecha_actualizacion")
    );
  }

  private function indicesAnalytics() {
    return array(
      "erp_ecommerce_analytics_sesiones" => array("PRIMARY", "idx_ecom_an_sesion_hash", "idx_ecom_an_sesion_fecha", "idx_ecom_an_sesion_utm"),
      "erp_ecommerce_analytics_eventos" => array("PRIMARY", "idx_ecom_an_evento_fecha", "idx_ecom_an_evento_session", "idx_ecom_an_evento_ruta", "idx_ecom_an_evento_producto", "idx_ecom_an_evento_mascota"),
      "erp_ecommerce_analytics_busquedas" => array("PRIMARY", "idx_ecom_an_busq_query", "idx_ecom_an_busq_sin_resultados", "idx_ecom_an_busq_mascota", "idx_ecom_an_busq_session"),
      "erp_ecommerce_analytics_conversiones" => array("PRIMARY", "idx_ecom_an_conv_fecha", "idx_ecom_an_conv_session", "idx_ecom_an_conv_producto"),
      "erp_ecommerce_analytics_resumen_diario" => array("PRIMARY", "idx_ecom_an_resumen_fecha_canal")
    );
  }

  private function respuestaPlan($plan, $ejecutar) {
    $pendientes = 0;
    $errores = 0;
    foreach ($plan as $item) {
      if (!empty($item["error"])) { $errores++; }
      $depurar = isset($item["depurar"]) && is_array($item["depurar"]) ? $item["depurar"] : array();
      if (isset($depurar["sql"]) && empty($depurar["ejecutado"])) { $pendientes++; }
    }
    return array(
      "error" => $errores > 0,
      "tipo" => $errores > 0 ? "warning" : ($pendientes > 0 ? "info" : "success"),
      "mensaje" => $ejecutar ? "Plan Ecommerce / Analytics procesado" : "Plan DDL Ecommerce / Analytics generado sin ejecutar",
      "depurar" => array(
        "ejecutar" => $ejecutar,
        "read_only" => !$ejecutar,
        "ddl_total" => count($plan),
        "ddl_pendientes" => $pendientes,
        "errores" => $errores,
        "plan" => $plan,
        "guardrails" => $this->guardrails()
      )
    );
  }

  private function guardrails() {
    return array(
      "no_ejecutar_sin_autorizacion" => true,
      "no_guardar_datos_personales" => true,
      "no_mostrar_stock_exacto" => true,
      "no_tocar_ventas" => true,
      "no_tocar_inventario" => true,
      "no_usar_ecom_legacy_como_fuente" => true
    );
  }
}
