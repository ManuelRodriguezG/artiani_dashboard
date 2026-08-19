<?php

class EcommercePublicoEsquema extends DBSchema {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: generar el plan DDL del CMS ecommerce de contenido sin ejecutarlo por defecto.
   * Impacto: Ecommerce CMS; prepara plantillas, slots, bloques, publicaciones y media sin tocar catalogo, precios ni inventario.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tablas ni modifica datos.
   */
  public function planActualizarCmsContenido($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_plantillas", array(
      "`id_plantilla` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(80) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`version_plantilla` VARCHAR(40) NOT NULL DEFAULT '1.0.0'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`activa` TINYINT(1) NOT NULL DEFAULT 0",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_plantilla`)",
      "UNIQUE KEY `idx_ecom_cms_plantilla_codigo` (`codigo`)",
      "KEY `idx_ecom_cms_plantilla_estado` (`estatus`, `activa`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_plantilla_slots", array(
      "`id_slot` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_plantilla` BIGINT NOT NULL",
      "`codigo` VARCHAR(120) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`pagina` VARCHAR(60) NOT NULL",
      "`tipos_bloque_json` TEXT NOT NULL",
      "`max_bloques` INT NOT NULL DEFAULT 1",
      "`requerido` TINYINT(1) NOT NULL DEFAULT 0",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_slot`)",
      "UNIQUE KEY `idx_ecom_cms_slot_unico` (`id_plantilla`, `codigo`)",
      "KEY `idx_ecom_cms_slot_pagina` (`pagina`, `estatus`, `orden`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_contenido_bloques", array(
      "`id_bloque` BIGINT NOT NULL AUTO_INCREMENT",
      "`tipo_bloque` VARCHAR(60) NOT NULL",
      "`codigo` VARCHAR(120) NULL",
      "`nombre_interno` VARCHAR(180) NOT NULL",
      "`titulo` VARCHAR(255) NULL",
      "`payload_json` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_bloque`)",
      "UNIQUE KEY `idx_ecom_cms_bloque_codigo` (`codigo`)",
      "KEY `idx_ecom_cms_bloque_tipo_estado` (`tipo_bloque`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_contenido_publicaciones", array(
      "`id_publicacion_contenido` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_plantilla` BIGINT NOT NULL",
      "`id_slot` BIGINT NOT NULL",
      "`id_bloque` BIGINT NOT NULL",
      "`pagina` VARCHAR(60) NOT NULL",
      "`contexto_clave` VARCHAR(120) NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`vigente_desde` DATETIME NULL",
      "`vigente_hasta` DATETIME NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'catalogo_publico'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`publicado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_publicacion_contenido`)",
      "KEY `idx_ecom_cms_pub_render` (`id_plantilla`, `pagina`, `contexto_clave`, `estatus`, `orden`)",
      "KEY `idx_ecom_cms_pub_slot` (`id_slot`, `estatus`, `orden`)",
      "KEY `idx_ecom_cms_pub_bloque` (`id_bloque`, `estatus`)",
      "KEY `idx_ecom_cms_pub_vigencia` (`estatus`, `vigente_desde`, `vigente_hasta`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_contenido_media", array(
      "`id_media` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_bloque` BIGINT NOT NULL",
      "`rol` VARCHAR(40) NOT NULL DEFAULT 'principal'",
      "`url_desktop` VARCHAR(500) NULL",
      "`url_mobile` VARCHAR(500) NULL",
      "`alt_text` VARCHAR(255) NOT NULL",
      "`metadata_json` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_media`)",
      "KEY `idx_ecom_cms_media_bloque` (`id_bloque`, `estatus`, `rol`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: auditar existencia de tablas CMS ecommerce de contenido sin ejecutar DDL.
   * Impacto: panel CMS ecommerce; permite ver faltantes antes de autorizar persistencia real.
   * Contrato: solo lectura.
   */
  public function auditarCmsContenido() {
    $tablas = $this->tablasCmsContenido();
    $auditoria = array();
    $faltantes = 0;
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "impacto" => $existe ? "Disponible para CMS ecommerce." : "Pendiente para persistencia real de contenido ecommerce."
      );
      if (!$existe) { $faltantes++; }
    }
    return array(
      "error" => false,
      "tipo" => $faltantes > 0 ? "warning" : "success",
      "mensaje" => $faltantes > 0 ? "Esquema CMS ecommerce pendiente" : "Esquema CMS ecommerce disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($tablas),
        "tablas_faltantes" => $faltantes,
        "auditoria" => $auditoria,
        "no_toca_catalogo" => true,
        "no_toca_inventario" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: proponer persistencia de biblioteca media CMS sin ejecutarla por defecto.
   * Impacto: CMS media; separa archivos reutilizables de bloques/contenido.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea carpetas ni tablas.
   */
  public function planActualizarCmsMediaBiblioteca($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_media_archivos", array(
      "`id_media_archivo` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(120) NOT NULL",
      "`nombre_original` VARCHAR(255) NOT NULL",
      "`nombre_archivo` VARCHAR(255) NOT NULL",
      "`ruta_publica` VARCHAR(500) NOT NULL",
      "`mime` VARCHAR(120) NOT NULL",
      "`extension` VARCHAR(20) NOT NULL",
      "`bytes` BIGINT NOT NULL DEFAULT 0",
      "`ancho` INT NULL",
      "`alto` INT NULL",
      "`hash_sha256` CHAR(64) NOT NULL",
      "`alt_text` VARCHAR(255) NOT NULL",
      "`uso_sugerido` VARCHAR(60) NOT NULL DEFAULT 'general'",
      "`tipo_sugerido` VARCHAR(60) NOT NULL DEFAULT 'editorial'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`metadata_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_media_archivo`)",
      "UNIQUE KEY `idx_ecom_media_archivo_codigo` (`codigo`)",
      "UNIQUE KEY `idx_ecom_media_archivo_hash` (`hash_sha256`)",
      "KEY `idx_ecom_media_archivo_uso` (`uso_sugerido`, `tipo_sugerido`, `estatus`)",
      "KEY `idx_ecom_media_archivo_estado` (`estatus`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_media_usos", array(
      "`id_media_uso` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_media_archivo` BIGINT NOT NULL",
      "`entidad_tipo` VARCHAR(60) NOT NULL",
      "`entidad_clave` VARCHAR(120) NOT NULL",
      "`campo` VARCHAR(80) NOT NULL",
      "`rol` VARCHAR(60) NOT NULL DEFAULT 'principal'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "PRIMARY KEY (`id_media_uso`)",
      "KEY `idx_ecom_media_uso_archivo` (`id_media_archivo`, `estatus`)",
      "KEY `idx_ecom_media_uso_entidad` (`entidad_tipo`, `entidad_clave`, `campo`, `estatus`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: generar el plan DDL de plantillas frontend administrables por CMS sin ejecutarlo por defecto.
   * Impacto: CMS frontend; prepara layouts, componentes, plantillas de vista, secciones y activaciones sin editar archivos del ecommerce.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no crea tablas ni modifica datos.
   */
  public function planActualizarCmsFrontend($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_temas", array(
      "`id_tema` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(100) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`proveedor` VARCHAR(120) NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`version_tema` VARCHAR(40) NOT NULL DEFAULT '1.0.0'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`activo` TINYINT(1) NOT NULL DEFAULT 0",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_tema`)",
      "UNIQUE KEY `idx_ecom_front_tema_codigo` (`codigo`)",
      "KEY `idx_ecom_front_tema_estado` (`estatus`, `activo`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_layouts", array(
      "`id_layout` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_tema` BIGINT NOT NULL",
      "`codigo` VARCHAR(100) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`version_layout` VARCHAR(40) NOT NULL DEFAULT '1.0.0'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_layout`)",
      "UNIQUE KEY `idx_ecom_front_layout_codigo` (`codigo`)",
      "KEY `idx_ecom_front_layout_tema` (`id_tema`, `estatus`)",
      "KEY `idx_ecom_front_layout_estado` (`estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_componentes", array(
      "`id_componente` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_tema` BIGINT NOT NULL",
      "`codigo` VARCHAR(100) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`descripcion` VARCHAR(255) NULL",
      "`bloques_permitidos_json` TEXT NOT NULL",
      "`variantes_json` TEXT NOT NULL",
      "`slots_compatibles_json` TEXT NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_componente`)",
      "UNIQUE KEY `idx_ecom_front_componente_codigo` (`codigo`)",
      "KEY `idx_ecom_front_componente_tema` (`id_tema`, `estatus`)",
      "KEY `idx_ecom_front_componente_estado` (`estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_plantillas", array(
      "`id_plantilla_vista` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_tema` BIGINT NOT NULL",
      "`id_layout` BIGINT NOT NULL",
      "`codigo` VARCHAR(100) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`pagina` VARCHAR(60) NOT NULL",
      "`version_plantilla` VARCHAR(40) NOT NULL DEFAULT '1.0.0'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_plantilla_vista`)",
      "UNIQUE KEY `idx_ecom_front_plantilla_codigo` (`codigo`)",
      "KEY `idx_ecom_front_plantilla_tema` (`id_tema`, `estatus`)",
      "KEY `idx_ecom_front_plantilla_pagina` (`pagina`, `estatus`)",
      "KEY `idx_ecom_front_plantilla_layout` (`id_layout`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_plantilla_secciones", array(
      "`id_seccion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_plantilla_vista` BIGINT NOT NULL",
      "`id_componente` BIGINT NOT NULL",
      "`slot_codigo` VARCHAR(120) NOT NULL",
      "`variante` VARCHAR(80) NOT NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`config_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_seccion`)",
      "KEY `idx_ecom_front_seccion_plantilla` (`id_plantilla_vista`, `estatus`, `orden`)",
      "KEY `idx_ecom_front_seccion_componente` (`id_componente`, `estatus`)",
      "KEY `idx_ecom_front_seccion_slot` (`slot_codigo`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_frontend_plantilla_activas", array(
      "`id_plantilla_activa` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_plantilla_vista` BIGINT NOT NULL",
      "`pagina` VARCHAR(60) NOT NULL",
      "`canal` VARCHAR(50) NOT NULL DEFAULT 'catalogo_publico'",
      "`contexto_clave` VARCHAR(120) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`vigente_desde` DATETIME NULL",
      "`vigente_hasta` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`activado_por` INT NULL",
      "PRIMARY KEY (`id_plantilla_activa`)",
      "KEY `idx_ecom_front_activa_render` (`pagina`, `canal`, `contexto_clave`, `estatus`)",
      "KEY `idx_ecom_front_activa_plantilla` (`id_plantilla_vista`, `estatus`)",
      "KEY `idx_ecom_front_activa_vigencia` (`estatus`, `vigente_desde`, `vigente_hasta`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: auditar existencia de tablas CMS frontend sin ejecutar DDL.
   * Impacto: permite preparar persistencia de plantillas visuales antes de conectar el renderer publico.
   * Contrato: solo lectura.
   */
  public function auditarCmsFrontend() {
    $tablas = $this->tablasCmsFrontend();
    $auditoria = array();
    $faltantes = 0;
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "impacto" => $existe ? "Disponible para CMS frontend." : "Pendiente para persistencia real de plantillas frontend."
      );
      if (!$existe) { $faltantes++; }
    }
    return array(
      "error" => false,
      "tipo" => $faltantes > 0 ? "warning" : "success",
      "mensaje" => $faltantes > 0 ? "Esquema CMS frontend pendiente" : "Esquema CMS frontend disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($tablas),
        "tablas_faltantes" => $faltantes,
        "auditoria" => $auditoria,
        "no_edita_archivos_frontend" => true,
        "no_habilita_codigo_libre" => true
      )
    );
  }

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
      "`api_secret_encrypted` TEXT NULL",
      "`api_secret_version` VARCHAR(40) NULL",
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

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: generar plan DDL para politicas, facturacion, analitica y navegacion guiada ecommerce.
   * Impacto: prepara experiencia cliente y panel de inteligencia comercial sin activar escrituras publicas.
   * Contrato: con $ejecutar=false solo devuelve SQL propuesto; no registra eventos ni solicitudes.
   */
  public function planActualizarExperienciaCliente($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_politicas", array(
      "`id_politica` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(80) NOT NULL",
      "`tipo` VARCHAR(50) NOT NULL",
      "`titulo` VARCHAR(180) NOT NULL",
      "`slug` VARCHAR(160) NOT NULL",
      "`contenido_html` MEDIUMTEXT NULL",
      "`resumen_publico` TEXT NULL",
      "`version` VARCHAR(40) NOT NULL DEFAULT '1.0'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`requiere_aceptacion` TINYINT(1) NOT NULL DEFAULT 0",
      "`orden` INT NOT NULL DEFAULT 0",
      "`fecha_publicacion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_politica`)",
      "UNIQUE KEY `idx_ecom_politica_codigo` (`codigo`)",
      "UNIQUE KEY `idx_ecom_politica_slug` (`slug`)",
      "KEY `idx_ecom_politica_tipo_estado` (`tipo`, `estatus`, `orden`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_facturacion_solicitudes", array(
      "`id_facturacion_solicitud` BIGINT NOT NULL AUTO_INCREMENT",
      "`folio_solicitud` VARCHAR(50) NOT NULL",
      "`folio_compra` VARCHAR(80) NOT NULL",
      "`origen` VARCHAR(50) NOT NULL DEFAULT 'web_publica'",
      "`id_canal_api` BIGINT NULL",
      "`id_cliente_crm` BIGINT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'nueva'",
      "`rfc` VARCHAR(20) NOT NULL",
      "`razon_social` VARCHAR(255) NOT NULL",
      "`regimen_fiscal` VARCHAR(20) NULL",
      "`uso_cfdi` VARCHAR(20) NULL",
      "`codigo_postal_fiscal` VARCHAR(12) NULL",
      "`correo_facturacion` VARCHAR(220) NULL",
      "`telefono_contacto` VARCHAR(80) NULL",
      "`importe_reportado` DECIMAL(18,6) NULL",
      "`fecha_compra_reportada` DATE NULL",
      "`ticket_archivo_url` VARCHAR(255) NULL",
      "`notas_cliente` TEXT NULL",
      "`datos_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`revisado_por` INT NULL",
      "PRIMARY KEY (`id_facturacion_solicitud`)",
      "UNIQUE KEY `idx_ecom_fact_folio_solicitud` (`folio_solicitud`)",
      "KEY `idx_ecom_fact_folio_compra` (`folio_compra`, `estatus`)",
      "KEY `idx_ecom_fact_rfc_fecha` (`rfc`, `fecha_registro`)",
      "KEY `idx_ecom_fact_estado_fecha` (`estatus`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_eventos_navegacion", array(
      "`id_evento_navegacion` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`id_canal_api` BIGINT NULL",
      "`tipo_evento` VARCHAR(60) NOT NULL",
      "`ruta` VARCHAR(255) NULL",
      "`referer` VARCHAR(255) NULL",
      "`id_publicacion` BIGINT NULL",
      "`id_sku` BIGINT NULL",
      "`mascota_especie` VARCHAR(80) NULL",
      "`necesidad` VARCHAR(80) NULL",
      "`metadata_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_evento_navegacion`)",
      "KEY `idx_ecom_nav_session_fecha` (`session_id_hash`, `fecha_registro`)",
      "KEY `idx_ecom_nav_tipo_fecha` (`tipo_evento`, `fecha_registro`)",
      "KEY `idx_ecom_nav_mascota_necesidad` (`mascota_especie`, `necesidad`, `fecha_registro`)",
      "KEY `idx_ecom_nav_publicacion` (`id_publicacion`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_busquedas", array(
      "`id_busqueda` BIGINT NOT NULL AUTO_INCREMENT",
      "`session_id_hash` VARCHAR(120) NOT NULL",
      "`id_canal_api` BIGINT NULL",
      "`termino` VARCHAR(255) NOT NULL",
      "`termino_normalizado` VARCHAR(255) NOT NULL",
      "`mascota_especie` VARCHAR(80) NULL",
      "`necesidad` VARCHAR(80) NULL",
      "`resultados_total` INT NULL",
      "`sin_resultados` TINYINT(1) NOT NULL DEFAULT 0",
      "`filtros_json` TEXT NULL",
      "`ip_hash` VARCHAR(120) NULL",
      "`user_agent_hash` VARCHAR(120) NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_busqueda`)",
      "KEY `idx_ecom_busq_termino_fecha` (`termino_normalizado`, `fecha_registro`)",
      "KEY `idx_ecom_busq_mascota_necesidad` (`mascota_especie`, `necesidad`, `fecha_registro`)",
      "KEY `idx_ecom_busq_sin_resultados` (`sin_resultados`, `fecha_registro`)",
      "KEY `idx_ecom_busq_session` (`session_id_hash`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_ecommerce_taxonomia_mascotas", array(
      "`id_taxonomia_mascota` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(80) NOT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'especie'",
      "`parent_codigo` VARCHAR(80) NULL",
      "`nombre` VARCHAR(160) NOT NULL",
      "`descripcion_publica` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`orden` INT NOT NULL DEFAULT 0",
      "`metadata_json` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`actualizado_por` INT NULL",
      "PRIMARY KEY (`id_taxonomia_mascota`)",
      "UNIQUE KEY `idx_ecom_tax_mascota_codigo` (`codigo`)",
      "KEY `idx_ecom_tax_mascota_tipo_estado` (`tipo`, `estatus`, `orden`)",
      "KEY `idx_ecom_tax_mascota_parent` (`parent_codigo`, `estatus`)"
    ), $opciones, $ejecutar);

    return $this->respuestaPlan($plan, $ejecutar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: auditar si existen tablas de experiencia cliente ecommerce.
   * Impacto: permite coordinar frontend, facturacion y panel analitico sin ejecutar DDL.
   * Contrato: solo lectura.
   */
  public function auditarExperienciaCliente() {
    $tablas = $this->tablasExperienciaCliente();
    $auditoria = array();
    $faltantes = 0;
    foreach ($tablas as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $auditoria[$tabla] = array(
        "existe" => $existe,
        "impacto" => $existe ? "Disponible para experiencia cliente ecommerce." : "Falta para politicas, facturacion, analitica o navegacion guiada."
      );
      if (!$existe) {
        $faltantes++;
      }
    }

    return array(
      "error" => false,
      "tipo" => $faltantes > 0 ? "warning" : "success",
      "mensaje" => $faltantes > 0 ? "Capa experiencia cliente ecommerce pendiente" : "Capa experiencia cliente ecommerce disponible",
      "depurar" => array(
        "read_only" => true,
        "tablas_total" => count($tablas),
        "tablas_faltantes" => $faltantes,
        "auditoria" => $auditoria,
        "no_registra_eventos" => true,
        "no_recibe_datos_fiscales" => true
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

  private function tablasExperienciaCliente() {
    return array(
      "erp_ecommerce_politicas",
      "erp_ecommerce_facturacion_solicitudes",
      "erp_ecommerce_eventos_navegacion",
      "erp_ecommerce_busquedas",
      "erp_ecommerce_taxonomia_mascotas"
    );
  }

  private function tablasCmsContenido() {
    return array(
      "erp_ecommerce_plantillas",
      "erp_ecommerce_plantilla_slots",
      "erp_ecommerce_contenido_bloques",
      "erp_ecommerce_contenido_publicaciones",
      "erp_ecommerce_contenido_media"
    );
  }

  private function tablasCmsFrontend() {
    return array(
      "erp_ecommerce_frontend_temas",
      "erp_ecommerce_frontend_layouts",
      "erp_ecommerce_frontend_componentes",
      "erp_ecommerce_frontend_plantillas",
      "erp_ecommerce_frontend_plantilla_secciones",
      "erp_ecommerce_frontend_plantilla_activas"
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
