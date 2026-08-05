<?php

class MigracionesBdEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-30
   * Proposito: preparar tablas SYS para controlar paquetes de migracion entre ambientes.
   * Impacto: Administracion/SYS; no migra datos por si mismo y opera en dry-run salvo autorizacion externa.
   * Contrato: `$ejecutar=false` solo genera SQL; `$ejecutar=true` crea tablas tecnicas idempotentes.
   */
  public function planActualizarMigracionesBd($ejecutar = false) {
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_ambientes", array(
      "`id_migracion_ambiente` INT NOT NULL AUTO_INCREMENT",
      "`alias` VARCHAR(80) NOT NULL",
      "`tipo` VARCHAR(30) NOT NULL DEFAULT 'local'",
      "`descripcion` TEXT NULL",
      "`host_publico` VARCHAR(180) NULL",
      "`base_publica` VARCHAR(180) NULL",
      "`config_origen` VARCHAR(80) NOT NULL DEFAULT 'archivo_local'",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_migracion_ambiente`)",
      "UNIQUE KEY `idx_sys_migraciones_ambientes_alias` (`alias`)",
      "KEY `idx_sys_migraciones_ambientes_tipo` (`tipo`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_tablas_politicas", array(
      "`id_migracion_tabla_politica` INT NOT NULL AUTO_INCREMENT",
      "`tabla` VARCHAR(180) NOT NULL",
      "`politica` VARCHAR(40) NOT NULL DEFAULT 'blocked'",
      "`llave_natural` VARCHAR(255) NULL",
      "`incluye_datos` TINYINT(1) NOT NULL DEFAULT 0",
      "`requiere_revision` TINYINT(1) NOT NULL DEFAULT 1",
      "`descripcion` TEXT NULL",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_migracion_tabla_politica`)",
      "UNIQUE KEY `idx_sys_migraciones_tablas_tabla` (`tabla`)",
      "KEY `idx_sys_migraciones_tablas_politica` (`politica`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_paquetes", array(
      "`id_migracion_paquete` BIGINT NOT NULL AUTO_INCREMENT",
      "`codigo` VARCHAR(80) NOT NULL",
      "`ambiente_origen` VARCHAR(80) NOT NULL",
      "`ambiente_destino` VARCHAR(80) NOT NULL",
      "`estatus` VARCHAR(40) NOT NULL DEFAULT 'borrador'",
      "`resumen_json` JSON NULL",
      "`hash_plan` VARCHAR(128) NULL",
      "`ruta_respaldo` TEXT NULL",
      "`id_usuario_creacion` INT NULL",
      "`id_usuario_autorizacion` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_autorizacion` DATETIME NULL",
      "`fecha_aplicacion` DATETIME NULL",
      "PRIMARY KEY (`id_migracion_paquete`)",
      "UNIQUE KEY `idx_sys_migraciones_paquetes_codigo` (`codigo`)",
      "KEY `idx_sys_migraciones_paquetes_estatus` (`estatus`, `fecha_registro`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_paquete_tablas", array(
      "`id_migracion_paquete_tabla` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_migracion_paquete` BIGINT NOT NULL",
      "`tabla` VARCHAR(180) NOT NULL",
      "`politica` VARCHAR(40) NOT NULL DEFAULT 'blocked'",
      "`incluye_datos` TINYINT(1) NOT NULL DEFAULT 0",
      "`llave_natural` VARCHAR(255) NULL",
      "`resumen_json` JSON NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_migracion_paquete_tabla`)",
      "UNIQUE KEY `idx_sys_migraciones_paq_tabla` (`id_migracion_paquete`, `tabla`)",
      "KEY `idx_sys_migraciones_paq_tabla_nombre` (`tabla`)",
      "KEY `idx_sys_migraciones_paq_tabla_politica` (`politica`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_paquete_sql", array(
      "`id_migracion_paquete_sql` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_migracion_paquete` BIGINT NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'ddl'",
      "`tabla` VARCHAR(180) NULL",
      "`politica` VARCHAR(40) NULL",
      "`sql_texto` LONGTEXT NOT NULL",
      "`riesgo` VARCHAR(40) NOT NULL DEFAULT 'medio'",
      "`ejecutado` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_migracion_paquete_sql`)",
      "KEY `idx_sys_migraciones_sql_paquete` (`id_migracion_paquete`, `orden`)",
      "KEY `idx_sys_migraciones_sql_tabla` (`tabla`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_ejecuciones", array(
      "`id_migracion_ejecucion` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_migracion_paquete` BIGINT NULL",
      "`ambiente_destino` VARCHAR(80) NOT NULL",
      "`estatus` VARCHAR(40) NOT NULL DEFAULT 'iniciada'",
      "`ruta_respaldo` TEXT NULL",
      "`mensaje` TEXT NULL",
      "`id_usuario` INT NULL",
      "`fecha_inicio` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_fin` DATETIME NULL",
      "PRIMARY KEY (`id_migracion_ejecucion`)",
      "KEY `idx_sys_migraciones_ejec_paquete` (`id_migracion_paquete`)",
      "KEY `idx_sys_migraciones_ejec_estatus` (`estatus`, `fecha_inicio`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_migraciones_ejecucion_detalle", array(
      "`id_migracion_ejecucion_detalle` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_migracion_ejecucion` BIGINT NOT NULL",
      "`orden` INT NOT NULL DEFAULT 0",
      "`tabla` VARCHAR(180) NULL",
      "`sql_texto` LONGTEXT NULL",
      "`resultado` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
      "`mensaje` TEXT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_migracion_ejecucion_detalle`)",
      "KEY `idx_sys_migraciones_det_ejec` (`id_migracion_ejecucion`, `orden`)",
      "KEY `idx_sys_migraciones_det_tabla` (`tabla`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $resumen = $this->resumirPlan($plan);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Esquema de migraciones BD ejecutado" : "Esquema de migraciones BD generado en dry-run",
      "depurar" => array(
        "resumen" => $resumen,
        "plan" => $plan
      )
    );
  }

  private function resumirPlan($plan) {
    $resumen = array(
      "total" => count($plan),
      "pendientes" => 0,
      "existentes" => 0,
      "ejecutadas" => 0,
      "errores" => 0,
      "tablas" => array(),
      "sql" => array()
    );
    foreach ($plan as $item) {
      if (!empty($item["error"])) {
        $resumen["errores"]++;
      }
      $depurar = isset($item["depurar"]) && is_array($item["depurar"]) ? $item["depurar"] : array();
      $tabla = isset($depurar["tabla"]) ? $depurar["tabla"] : $this->tablaDesdeSql(isset($depurar["sql"]) ? $depurar["sql"] : "");
      if ($tabla !== "") {
        $resumen["tablas"][] = $tabla;
      }
      if (isset($depurar["ejecutado"]) && !empty($depurar["ejecutado"])) {
        $resumen["ejecutadas"]++;
      } elseif (isset($depurar["sql"]) && $depurar["sql"] !== "") {
        $resumen["pendientes"]++;
        $resumen["sql"][] = $depurar["sql"];
      } else {
        $resumen["existentes"]++;
      }
    }
    $resumen["tablas"] = array_values(array_unique($resumen["tablas"]));
    return $resumen;
  }

  private function tablaDesdeSql($sql) {
    if (preg_match('/CREATE\s+TABLE\s+`([^`]+)`/i', (string) $sql, $m)) {
      return $m[1];
    }
    return "";
  }
}
