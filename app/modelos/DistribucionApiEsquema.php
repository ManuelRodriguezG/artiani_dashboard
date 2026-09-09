<?php

class DistribucionApiEsquema extends DBSchema {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar clase de esquema para la API Distribucion sin ejecutar DDL automatico.
   * Impacto: API Distribucion; prepara auditoria futura de clientes, permisos, tokens y cotizaciones.
   * Contrato: solo plan/readiness; no crea tablas desde constructor.
   */
  public function entidadesPrevistas() {
    return array(
      "erp_distribucion_clientes",
      "erp_distribucion_solicitudes",
      "erp_distribucion_cliente_permisos",
      "erp_distribucion_cliente_listas",
      "erp_distribucion_cotizaciones",
      "erp_distribucion_cotizacion_items",
      "erp_distribucion_tokens",
      "erp_distribucion_auditoria"
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: auditar entidades requeridas para clientes externos, tokens, permisos y cotizaciones Distribucion.
   * Impacto: API Distribucion; permite revisar readiness antes de autorizar cualquier DDL.
   * Contrato: read-only sobre INFORMATION_SCHEMA; no crea tablas ni modifica datos.
   */
  public function auditarDistribucionApi() {
    $tablas = array();
    $faltantes = array();
    foreach ($this->entidadesPrevistas() as $tabla) {
      $existe = $this->tablaExiste($tabla);
      $tablas[$tabla] = array(
        "existe" => $existe,
        "impacto" => $this->impactoTabla($tabla)
      );
      if (!$existe) { $faltantes[] = $tabla; }
    }

    return $this->respuesta(false, empty($faltantes) ? "success" : "warning", empty($faltantes) ? "Esquema Distribucion API disponible" : "Esquema Distribucion API pendiente", array(
      "ready" => empty($faltantes),
      "faltantes" => $faltantes,
      "tablas" => $tablas,
      "guardrails" => array(
        "solo_auditoria" => true,
        "no_ejecuta_ddl" => true,
        "requiere_respaldo_para_aplicar" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: generar plan DDL para entidades propias de Distribucion API sin ejecutarlo por defecto.
   * Impacto: API Distribucion; prepara persistencia futura de solicitudes, clientes externos, tokens y cotizaciones.
   * Contrato: `$ejecutar=false` genera SQL; aplicar requiere autorizacion operativa y respaldo externo.
   */
  public function planActualizarDistribucionApi($ejecutar = false) {
    $plan = array();
    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_clientes", array(
      "`id_cliente_distribucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_crm` BIGINT NULL",
      "`nombre` VARCHAR(160) NOT NULL",
      "`empresa` VARCHAR(180) NULL",
      "`correo` VARCHAR(180) NOT NULL",
      "`telefono` VARCHAR(40) NULL",
      "`tipo_cliente` VARCHAR(40) NOT NULL DEFAULT 'registrado'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`contrasenia_hash` VARCHAR(255) NULL",
      "`id_lista_precio` INT NULL",
      "`fecha_aprobacion` DATETIME NULL",
      "`fecha_ultimo_login` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_distribucion`)",
      "UNIQUE KEY `idx_dist_cliente_correo` (`correo`)",
      "KEY `idx_dist_cliente_estatus` (`estatus`, `tipo_cliente`)",
      "KEY `idx_dist_cliente_lista` (`id_lista_precio`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_solicitudes", array(
      "`id_solicitud_distribucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`folio` VARCHAR(40) NOT NULL",
      "`nombre` VARCHAR(160) NOT NULL",
      "`empresa` VARCHAR(180) NULL",
      "`correo` VARCHAR(180) NOT NULL",
      "`telefono` VARCHAR(40) NULL",
      "`tipo_interes` VARCHAR(40) NOT NULL DEFAULT 'registrado'",
      "`mensaje` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`id_cliente_distribucion` BIGINT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_solicitud_distribucion`)",
      "UNIQUE KEY `idx_dist_solicitud_folio` (`folio`)",
      "KEY `idx_dist_solicitud_correo` (`correo`, `estatus`)",
      "KEY `idx_dist_solicitud_estatus` (`estatus`, `fecha_registro`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_cliente_permisos", array(
      "`id_cliente_permiso` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_distribucion` BIGINT NOT NULL",
      "`permiso` VARCHAR(120) NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_permiso`)",
      "UNIQUE KEY `idx_dist_cliente_permiso` (`id_cliente_distribucion`, `permiso`)",
      "KEY `idx_dist_permiso_estatus` (`permiso`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_cliente_listas", array(
      "`id_cliente_lista` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_distribucion` BIGINT NOT NULL",
      "`id_lista_precio` INT NOT NULL",
      "`prioridad` INT NOT NULL DEFAULT 1",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_inicio` DATETIME NULL",
      "`fecha_fin` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cliente_lista`)",
      "KEY `idx_dist_cliente_lista_cliente` (`id_cliente_distribucion`, `estatus`, `prioridad`)",
      "KEY `idx_dist_cliente_lista_lista` (`id_lista_precio`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_tokens", array(
      "`id_token_distribucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cliente_distribucion` BIGINT NOT NULL",
      "`token_hash` VARCHAR(255) NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`ip_creacion` VARCHAR(80) NULL",
      "`user_agent` VARCHAR(255) NULL",
      "`fecha_expiracion` DATETIME NOT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_ultimo_uso` DATETIME NULL",
      "PRIMARY KEY (`id_token_distribucion`)",
      "UNIQUE KEY `idx_dist_token_hash` (`token_hash`)",
      "KEY `idx_dist_token_cliente` (`id_cliente_distribucion`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_cotizaciones", array(
      "`id_cotizacion_distribucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`folio` VARCHAR(40) NOT NULL",
      "`id_cliente_distribucion` BIGINT NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'recibida'",
      "`moneda` VARCHAR(10) NOT NULL DEFAULT 'MXN'",
      "`subtotal` DECIMAL(18,6) NULL",
      "`total_estimado` DECIMAL(18,6) NULL",
      "`comentarios` TEXT NULL",
      "`snapshot_json` LONGTEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_cotizacion_distribucion`)",
      "UNIQUE KEY `idx_dist_cotizacion_folio` (`folio`)",
      "KEY `idx_dist_cotizacion_cliente` (`id_cliente_distribucion`, `estatus`)",
      "KEY `idx_dist_cotizacion_estatus` (`estatus`, `fecha_registro`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_cotizacion_items", array(
      "`id_cotizacion_item` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_cotizacion_distribucion` BIGINT NOT NULL",
      "`id_sku` BIGINT NOT NULL",
      "`sku_snapshot` VARCHAR(150) NULL",
      "`nombre_snapshot` VARCHAR(255) NULL",
      "`cantidad` DECIMAL(18,6) NOT NULL DEFAULT 1",
      "`precio_unitario_snapshot` DECIMAL(18,6) NULL",
      "`subtotal_snapshot` DECIMAL(18,6) NULL",
      "`disponibilidad_snapshot` VARCHAR(40) NULL",
      "`snapshot_json` LONGTEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_cotizacion_item`)",
      "KEY `idx_dist_cot_item_cotizacion` (`id_cotizacion_distribucion`)",
      "KEY `idx_dist_cot_item_sku` (`id_sku`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_distribucion_auditoria", array(
      "`id_auditoria_distribucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`entidad` VARCHAR(80) NOT NULL",
      "`id_entidad` BIGINT NULL",
      "`accion` VARCHAR(80) NOT NULL",
      "`resultado` VARCHAR(30) NOT NULL DEFAULT 'ok'",
      "`mensaje` VARCHAR(255) NULL",
      "`detalle_json` LONGTEXT NULL",
      "`id_usuario_erp` INT NULL",
      "`id_cliente_distribucion` BIGINT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_auditoria_distribucion`)",
      "KEY `idx_dist_auditoria_entidad` (`entidad`, `id_entidad`)",
      "KEY `idx_dist_auditoria_fecha` (`fecha_registro`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

    return $this->respuesta(false, $ejecutar ? "success" : "info", $ejecutar ? "Plan Distribucion API ejecutado" : "Plan Distribucion API generado sin ejecutar", array(
      "ejecutado" => (bool) $ejecutar,
      "plan" => $plan,
      "requiere_autorizacion" => !$ejecutar,
      "token_sugerido" => "DISTRIBUCION_API_DDL",
      "respaldo_requerido" => "C:\\xampp\\panel_db_backups"
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: generar/aplicar solo permisos internos ERP de Distribucion.
   * Impacto: Seguridad ERP; habilita proteccion de DistribucionAdmin sin ejecutar todo el plan global de seguridad.
   * Contrato: idempotente; inserta permisos y asigna a roles base autorizados, no retira permisos existentes.
   */
  public function planPermisosInternosDistribucion($ejecutar = false) {
    $permisos = array(
      array("modulo" => "distribucion", "accion" => "ver", "permiso" => "distribucion.ver", "descripcion" => "Consultar consola interna de Distribucion"),
      array("modulo" => "distribucion", "accion" => "editar", "permiso" => "distribucion.editar", "descripcion" => "Editar perfil comercial externo y permisos de Distribucion"),
      array("modulo" => "distribucion", "accion" => "aprobar_clientes", "permiso" => "distribucion.aprobar_clientes", "descripcion" => "Aprobar, rechazar o suspender clientes externos de Distribucion"),
      array("modulo" => "distribucion", "accion" => "asignar_precios", "permiso" => "distribucion.asignar_precios", "descripcion" => "Asignar listas de precios a clientes externos de Distribucion"),
      array("modulo" => "distribucion", "accion" => "cotizaciones_ver", "permiso" => "distribucion.cotizaciones.ver", "descripcion" => "Consultar cotizaciones recibidas por Distribucion"),
      array("modulo" => "distribucion", "accion" => "cotizaciones_gestionar", "permiso" => "distribucion.cotizaciones.gestionar", "descripcion" => "Gestionar seguimiento interno de cotizaciones Distribucion sin convertir automaticamente")
    );
    $roles = array(
      "direccion" => array("distribucion.ver", "distribucion.editar", "distribucion.aprobar_clientes", "distribucion.asignar_precios", "distribucion.cotizaciones.ver", "distribucion.cotizaciones.gestionar"),
      "administrador_erp" => array("distribucion.ver", "distribucion.editar", "distribucion.aprobar_clientes", "distribucion.asignar_precios", "distribucion.cotizaciones.ver", "distribucion.cotizaciones.gestionar"),
      "ventas" => array("distribucion.ver", "distribucion.cotizaciones.ver"),
      "ecommerce" => array("distribucion.ver", "distribucion.cotizaciones.ver"),
      "auditor" => array("distribucion.ver", "distribucion.cotizaciones.ver")
    );
    $plan = array();
    foreach ($permisos as $permiso) {
      $sql = "INSERT INTO sys_permisos (modulo, accion, permiso, descripcion, estatus)
        VALUES (" . $this->sqlTexto($permiso["modulo"]) . ", " . $this->sqlTexto($permiso["accion"]) . ", " . $this->sqlTexto($permiso["permiso"]) . ", " . $this->sqlTexto($permiso["descripcion"]) . ", 1)
        ON DUPLICATE KEY UPDATE modulo=VALUES(modulo), accion=VALUES(accion), descripcion=VALUES(descripcion), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP;";
      $plan[] = $this->ejecutarSqlControlado($sql, $ejecutar);
    }
    foreach ($roles as $rol => $permisosRol) {
      foreach ($permisosRol as $permiso) {
        $sql = "INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso)
          SELECT sr.id_rol, sp.id_permiso
          FROM sys_roles sr
          INNER JOIN sys_permisos sp ON sp.permiso=" . $this->sqlTexto($permiso) . "
          WHERE sr.rol=" . $this->sqlTexto($rol) . ";";
        $plan[] = $this->ejecutarSqlControlado($sql, $ejecutar);
      }
    }
    return $this->respuesta(false, $ejecutar ? "success" : "info", $ejecutar ? "Permisos internos Distribucion aplicados" : "Permisos internos Distribucion generados sin ejecutar", array(
      "ejecutado" => (bool) $ejecutar,
      "plan" => $plan
    ));
  }

  private function impactoTabla($tabla) {
    $impactos = array(
      "erp_distribucion_clientes" => "Perfil externo B2B, estatus, tipo y lista asignada.",
      "erp_distribucion_solicitudes" => "Solicitudes de acceso comercial antes de aprobacion.",
      "erp_distribucion_cliente_permisos" => "Permisos granulares externos por cliente.",
      "erp_distribucion_cliente_listas" => "Historial/asignacion de listas de precio por cliente externo.",
      "erp_distribucion_cotizaciones" => "Encabezado de solicitudes de cotizacion Distribucion.",
      "erp_distribucion_cotizacion_items" => "Snapshot comercial de partidas cotizadas.",
      "erp_distribucion_tokens" => "Tokens externos separados de sesion ERP interna.",
      "erp_distribucion_auditoria" => "Trazabilidad de aprobaciones, login y cotizaciones externas."
    );
    return isset($impactos[$tabla]) ? $impactos[$tabla] : "Entidad Distribucion API.";
  }

  private function ejecutarSqlControlado($sql, $ejecutar) {
    if (!$ejecutar) {
      return $this->respuesta(false, "info", "SQL generado sin ejecutar", array("sql" => $sql, "ejecutado" => false));
    }
    try {
      $db = $this->conectar();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return $this->respuesta(false, "success", "SQL ejecutado correctamente", array("ejecutado" => true));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo ejecutar SQL controlado", array("ejecutado" => false));
    }
  }

  private function sqlTexto($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
