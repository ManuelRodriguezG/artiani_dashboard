<?php

class NotificacionesEsquema extends DBSchema {

  public function auditarNotificacionesErp() {
    $tablas = array(
      "erp_notificaciones",
      "erp_notificaciones_lecturas"
    );
    $requeridas = array(
      "erp_notificaciones" => array(
        "id_notificacion", "tipo", "modulo_origen", "entidad_origen", "id_entidad_origen",
        "area_responsable", "permiso_requerido", "titulo", "descripcion", "prioridad",
        "estatus", "url_accion", "payload_json", "creado_por", "asignado_a",
        "fecha_vencimiento", "fecha_resolucion", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_notificaciones_lecturas" => array(
        "id_notificacion_lectura", "id_notificacion", "id_usuario", "leida",
        "descartada", "fecha_lectura", "fecha_registro"
      )
    );
    $pendientes = array();

    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($tabla)) {
        $pendientes[] = array(
          "tipo" => "tabla_faltante",
          "tabla" => $tabla,
          "mensaje" => "Falta tabla transversal de notificaciones"
        );
        continue;
      }
      foreach ($requeridas[$tabla] as $columna) {
        if (!$this->columnaExiste($tabla, $columna)) {
          $pendientes[] = array(
            "tipo" => "columna_faltante",
            "tabla" => $tabla,
            "columna" => $columna,
            "mensaje" => "Falta columna requerida"
          );
        }
      }
    }

    return array(
      "error" => false,
      "tipo" => empty($pendientes) ? "success" : "warning",
      "mensaje" => empty($pendientes)
        ? "El esquema de notificaciones esta completo"
        : "Hay pendientes en el esquema de notificaciones",
      "depurar" => array(
        "tiene_pendientes" => !empty($pendientes),
        "pendientes" => $pendientes
      )
    );
  }

  public function planActualizarNotificacionesErp($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_notificaciones", array(
      "`id_notificacion` BIGINT NOT NULL AUTO_INCREMENT",
      "`tipo` VARCHAR(80) NOT NULL",
      "`modulo_origen` VARCHAR(60) NOT NULL",
      "`entidad_origen` VARCHAR(80) NOT NULL",
      "`id_entidad_origen` BIGINT NULL",
      "`area_responsable` VARCHAR(60) NOT NULL",
      "`permiso_requerido` VARCHAR(120) NULL",
      "`titulo` VARCHAR(180) NOT NULL",
      "`descripcion` VARCHAR(700) NULL",
      "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'normal'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`url_accion` VARCHAR(700) NULL",
      "`payload_json` LONGTEXT NULL",
      "`creado_por` INT NULL",
      "`asignado_a` INT NULL",
      "`fecha_vencimiento` DATETIME NULL",
      "`fecha_resolucion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_notificacion`)",
      "KEY `idx_notificaciones_responsable` (`area_responsable`, `estatus`)",
      "KEY `idx_notificaciones_permiso` (`permiso_requerido`, `estatus`)",
      "KEY `idx_notificaciones_origen` (`modulo_origen`, `entidad_origen`, `id_entidad_origen`)",
      "KEY `idx_notificaciones_prioridad` (`prioridad`, `estatus`)",
      "KEY `idx_notificaciones_asignado` (`asignado_a`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "tipo", "VARCHAR(80) NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "modulo_origen", "VARCHAR(60) NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "entidad_origen", "VARCHAR(80) NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "id_entidad_origen", "BIGINT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "area_responsable", "VARCHAR(60) NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "permiso_requerido", "VARCHAR(120) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "titulo", "VARCHAR(180) NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "descripcion", "VARCHAR(700) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "prioridad", "VARCHAR(20) NOT NULL DEFAULT 'normal'", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "estatus", "VARCHAR(30) NOT NULL DEFAULT 'pendiente'", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "url_accion", "VARCHAR(700) NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "payload_json", "LONGTEXT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "creado_por", "INT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "asignado_a", "INT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "fecha_vencimiento", "DATETIME NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "fecha_resolucion", "DATETIME NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones", "idx_notificaciones_responsable", "KEY `idx_notificaciones_responsable` (`area_responsable`, `estatus`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones", "idx_notificaciones_permiso", "KEY `idx_notificaciones_permiso` (`permiso_requerido`, `estatus`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones", "idx_notificaciones_origen", "KEY `idx_notificaciones_origen` (`modulo_origen`, `entidad_origen`, `id_entidad_origen`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones", "idx_notificaciones_prioridad", "KEY `idx_notificaciones_prioridad` (`prioridad`, `estatus`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones", "idx_notificaciones_asignado", "KEY `idx_notificaciones_asignado` (`asignado_a`, `estatus`)", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_notificaciones_lecturas", array(
      "`id_notificacion_lectura` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_notificacion` BIGINT NOT NULL",
      "`id_usuario` INT NOT NULL",
      "`leida` TINYINT(1) NOT NULL DEFAULT 0",
      "`descartada` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_lectura` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_notificacion_lectura`)",
      "UNIQUE KEY `idx_notificacion_usuario` (`id_notificacion`, `id_usuario`)",
      "KEY `idx_notificacion_lectura_usuario` (`id_usuario`, `leida`, `descartada`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "id_notificacion", "BIGINT NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "id_usuario", "INT NOT NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "leida", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "descartada", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "fecha_lectura", "DATETIME NULL", $ejecutar);
    $plan[] = $this->agregarColumnaSiNoExiste("erp_notificaciones_lecturas", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones_lecturas", "idx_notificacion_usuario", "UNIQUE KEY `idx_notificacion_usuario` (`id_notificacion`, `id_usuario`)", $ejecutar);
    $plan[] = $this->agregarIndiceSiNoExiste("erp_notificaciones_lecturas", "idx_notificacion_lectura_usuario", "KEY `idx_notificacion_lectura_usuario` (`id_usuario`, `leida`, `descartada`)", $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan de notificaciones ejecutado" : "Plan de notificaciones generado en dry-run",
      "depurar" => $plan
    );
  }
}
