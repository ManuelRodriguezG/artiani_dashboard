<?php

class EcommerceLeadsEsquema extends DBSchema {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: generar plan DDL para carritos, leads e intentos ecommerce sin ejecutarlo por defecto.
   * Impacto: Ecommerce publico/CRM futuro; captura intencion comercial sin crear pedidos, ventas ni inventario.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tablas ni modifica datos.
   */
  public function planActualizarEcommerceLeads($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_leads_carritos", array(
      "`id_carrito_lead` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`estado_carrito` VARCHAR(40) NOT NULL DEFAULT 'activo'",
      "`estatus` VARCHAR(40) NOT NULL DEFAULT 'anonimo_activo'",
      "`etapa_actual` VARCHAR(60) NULL",
      "`ultima_ruta` VARCHAR(255) NULL",
      "`id_cliente_crm` BIGINT NULL",
      "`nombre_contacto` VARCHAR(220) NULL",
      "`telefono_contacto` VARCHAR(80) NULL",
      "`correo_contacto` VARCHAR(220) NULL",
      "`acepta_whatsapp` TINYINT(1) NOT NULL DEFAULT 0",
      "`acepta_politicas` TINYINT(1) NOT NULL DEFAULT 0",
      "`whatsapp_mensaje_generado` TEXT NULL",
      "`whatsapp_url_generada` VARCHAR(700) NULL",
      "`whatsapp_abierto` TINYINT(1) NOT NULL DEFAULT 0",
      "`solicito_facturacion` TINYINT(1) NOT NULL DEFAULT 0",
      "`items_total` INT NOT NULL DEFAULT 0",
      "`piezas_total` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`subtotal_estimado` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`moneda` CHAR(3) NOT NULL DEFAULT 'MXN'",
      "`metadata_json` TEXT NULL",
      "`responsable_seguimiento` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_ultima_actividad` DATETIME NULL",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_carrito_lead`)",
      "UNIQUE KEY `idx_ecom_lead_session_canal` (`session_id_hash`, `canal`)",
      "KEY `idx_ecom_lead_estado_fecha` (`estatus`, `fecha_ultima_actividad`)",
      "KEY `idx_ecom_lead_contacto` (`telefono_contacto`, `correo_contacto`)",
      "KEY `idx_ecom_lead_responsable` (`responsable_seguimiento`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_leads_carrito_items", array(
      "`id_carrito_lead_item` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_carrito_lead` BIGINT NOT NULL",
      "`renglon` INT NOT NULL DEFAULT 1",
      "`id_publicacion` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`slug` VARCHAR(180) NULL",
      "`sku_snapshot` VARCHAR(120) NULL",
      "`nombre_snapshot` VARCHAR(255) NULL",
      "`cantidad` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
      "`precio_unitario_snapshot` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`subtotal_snapshot` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
      "`moneda_snapshot` CHAR(3) NOT NULL DEFAULT 'MXN'",
      "`validacion_publicacion` VARCHAR(50) NOT NULL DEFAULT 'pendiente'",
      "`metadata_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_carrito_lead_item`)",
      "KEY `idx_ecom_lead_item_carrito` (`id_carrito_lead`, `renglon`)",
      "KEY `idx_ecom_lead_item_sku` (`id_sku`, `fecha_registro`)",
      "KEY `idx_ecom_lead_item_publicacion` (`id_publicacion`, `validacion_publicacion`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_leads_eventos", array(
      "`id_lead_evento` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_carrito_lead` BIGINT NULL",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`tipo_evento` VARCHAR(70) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`ruta` VARCHAR(255) NULL",
      "`etapa` VARCHAR(60) NULL",
      "`resultado` VARCHAR(40) NULL",
      "`detalle_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_lead_evento`)",
      "KEY `idx_ecom_lead_evento_carrito` (`id_carrito_lead`, `fecha_registro`)",
      "KEY `idx_ecom_lead_evento_session` (`session_id_hash`, `fecha_registro`)",
      "KEY `idx_ecom_lead_evento_tipo` (`tipo_evento`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_leads_notas", array(
      "`id_lead_nota` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_carrito_lead` BIGINT NOT NULL",
      "`nota` TEXT NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_lead_nota`)",
      "KEY `idx_ecom_lead_nota_carrito` (`id_carrito_lead`, `estatus`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: auditar tablas, columnas e indices del modulo Ecommerce Leads sin ejecutar DDL.
   * Impacto: permite revisar readiness antes de activar captura comercial desde frontend.
   * Contrato: solo lectura.
   */
  public function auditarEcommerceLeads() {
    $auditoria = array();
    $faltantes = 0;
    $columnasFaltantesTotal = 0;
    $indicesFaltantesTotal = 0;
    $columnas = $this->columnasLeads();
    $indices = $this->indicesLeads();
    foreach ($this->tablasLeads() as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $columnasFaltantes = array();
      $indicesFaltantes = array();
      if ($existe) {
        foreach ($columnas[$tabla] as $columna) {
          if (!$this->columnaExiste($tabla, $columna)) { $columnasFaltantes[] = $columna; }
        }
        foreach ($indices[$tabla] as $indice) {
          if (!$this->indiceExiste($tabla, $indice)) { $indicesFaltantes[] = $indice; }
        }
      }
      if (!$existe) { $faltantes++; }
      $columnasFaltantesTotal += count($columnasFaltantes);
      $indicesFaltantesTotal += count($indicesFaltantes);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "columnas_faltantes" => $columnasFaltantes,
        "indices_faltantes" => $indicesFaltantes,
        "impacto" => $existe ? "Disponible para intencion comercial ecommerce." : "Pendiente para carritos/leads ecommerce."
      );
    }

    return array(
      "error" => false,
      "tipo" => ($faltantes > 0 || $columnasFaltantesTotal > 0 || $indicesFaltantesTotal > 0) ? "warning" : "success",
      "mensaje" => ($faltantes > 0 || $columnasFaltantesTotal > 0 || $indicesFaltantesTotal > 0) ? "Esquema Ecommerce Leads pendiente" : "Esquema Ecommerce Leads disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($this->tablasLeads()),
        "tablas_faltantes" => $faltantes,
        "columnas_faltantes_total" => $columnasFaltantesTotal,
        "indices_faltantes_total" => $indicesFaltantesTotal,
        "auditoria" => $auditoria,
        "guardrails" => $this->guardrails()
      )
    );
  }

  private function tablasLeads() {
    return array("erp_ecommerce_leads_carritos", "erp_ecommerce_leads_carrito_items", "erp_ecommerce_leads_eventos", "erp_ecommerce_leads_notas");
  }

  private function columnasLeads() {
    return array(
      "erp_ecommerce_leads_carritos" => array("id_carrito_lead", "session_id_hash", "canal", "estado_carrito", "estatus", "etapa_actual", "ultima_ruta", "id_cliente_crm", "nombre_contacto", "telefono_contacto", "correo_contacto", "acepta_whatsapp", "acepta_politicas", "whatsapp_mensaje_generado", "whatsapp_url_generada", "whatsapp_abierto", "solicito_facturacion", "items_total", "piezas_total", "subtotal_estimado", "moneda", "metadata_json", "responsable_seguimiento", "fecha_registro", "fecha_ultima_actividad", "fecha_actualizacion"),
      "erp_ecommerce_leads_carrito_items" => array("id_carrito_lead_item", "id_carrito_lead", "renglon", "id_publicacion", "id_sku", "slug", "sku_snapshot", "nombre_snapshot", "cantidad", "precio_unitario_snapshot", "subtotal_snapshot", "moneda_snapshot", "validacion_publicacion", "metadata_json", "fecha_registro"),
      "erp_ecommerce_leads_eventos" => array("id_lead_evento", "id_carrito_lead", "session_id_hash", "tipo_evento", "canal", "ruta", "etapa", "resultado", "detalle_json", "fecha_registro", "creado_por"),
      "erp_ecommerce_leads_notas" => array("id_lead_nota", "id_carrito_lead", "nota", "estatus", "fecha_registro", "creado_por")
    );
  }

  private function indicesLeads() {
    return array(
      "erp_ecommerce_leads_carritos" => array("PRIMARY", "idx_ecom_lead_session_canal", "idx_ecom_lead_estado_fecha", "idx_ecom_lead_contacto", "idx_ecom_lead_responsable"),
      "erp_ecommerce_leads_carrito_items" => array("PRIMARY", "idx_ecom_lead_item_carrito", "idx_ecom_lead_item_sku", "idx_ecom_lead_item_publicacion"),
      "erp_ecommerce_leads_eventos" => array("PRIMARY", "idx_ecom_lead_evento_carrito", "idx_ecom_lead_evento_session", "idx_ecom_lead_evento_tipo"),
      "erp_ecommerce_leads_notas" => array("PRIMARY", "idx_ecom_lead_nota_carrito")
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
      "mensaje" => $ejecutar ? "Plan Ecommerce Leads procesado" : "Plan DDL Ecommerce Leads generado sin ejecutar",
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
      "no_crear_pedido" => true,
      "no_crear_venta" => true,
      "no_descuenta_inventario" => true,
      "no_sustituye_analytics" => true,
      "session_id_se_guarda_hash" => true,
      "datos_personales_solo_si_cliente_los_escribe" => true
    );
  }
}
