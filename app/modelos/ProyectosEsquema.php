<?php

class ProyectosEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar las tablas propias del modulo Proyectos/Tareas.
   * Impacto: Proyectos ERP; separa planeacion operativa de docs, auditoria y notificaciones.
   * Contrato: solo devuelve nombres; no consulta ni modifica BD.
   */
  public function tablasProyectos() {
    return array(
      "erp_proyectos",
      "erp_proyecto_objetivos",
      "erp_proyecto_tareas",
      "erp_proyecto_comentarios",
      "erp_proyecto_adjuntos",
      "erp_proyecto_eventos"
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: auditar estructura minima del modulo antes de habilitar uso real.
   * Impacto: Proyectos ERP; no crea tablas ni toca tareas de otros modulos.
   * Contrato: read-only sobre INFORMATION_SCHEMA.
   */
  public function auditarProyectosErp() {
    $requeridas = $this->columnasRequeridas();
    $pendientes = array();

    foreach ($this->tablasProyectos() as $tabla) {
      if (!$this->tablaExiste($tabla)) {
        $pendientes[] = array(
          "tipo" => "tabla_faltante",
          "tabla" => $tabla,
          "mensaje" => "Falta tabla requerida para Proyectos"
        );
        continue;
      }

      foreach ($requeridas[$tabla] as $columna) {
        if (!$this->columnaExiste($tabla, $columna)) {
          $pendientes[] = array(
            "tipo" => "columna_faltante",
            "tabla" => $tabla,
            "columna" => $columna,
            "mensaje" => "Falta columna requerida para Proyectos"
          );
        }
      }
    }

    return array(
      "error" => false,
      "tipo" => empty($pendientes) ? "success" : "warning",
      "mensaje" => empty($pendientes)
        ? "El esquema de Proyectos esta completo"
        : "Hay pendientes en el esquema de Proyectos",
      "depurar" => array(
        "tiene_pendientes" => !empty($pendientes),
        "pendientes" => $pendientes,
        "tablas" => $this->tablasProyectos(),
        "regla" => "Auditoria read-only; no ejecuta DDL."
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: generar/aplicar DDL del modulo Proyectos con ejecucion controlada.
   * Impacto: Proyectos ERP; crea estructura vacia sin precargar avances de otros modulos.
   * Contrato: ejecutar=false genera SQL; ejecutar=true requiere respaldo externo y autorizacion del dueno.
   */
  public function planActualizarProyectosErp($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_proyectos", array(
      "`id_proyecto` INT NOT NULL AUTO_INCREMENT",
      "`folio` VARCHAR(40) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`descripcion` TEXT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'operacion_negocio'",
      "`modulo_relacionado` VARCHAR(60) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'normal'",
      "`id_responsable` INT NULL",
      "`creado_por` INT NULL",
      "`fecha_inicio` DATE NULL",
      "`fecha_objetivo` DATE NULL",
      "`fecha_cierre` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_proyecto`)",
      "UNIQUE KEY `idx_proyectos_folio` (`folio`)",
      "KEY `idx_proyectos_estatus` (`estatus`, `prioridad`)",
      "KEY `idx_proyectos_responsable` (`id_responsable`, `estatus`)",
      "KEY `idx_proyectos_modulo` (`modulo_relacionado`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_proyecto_objetivos", array(
      "`id_objetivo` INT NOT NULL AUTO_INCREMENT",
      "`id_proyecto` INT NOT NULL",
      "`titulo` VARCHAR(180) NOT NULL",
      "`descripcion` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'normal'",
      "`orden` INT NOT NULL DEFAULT 0",
      "`fecha_objetivo` DATE NULL",
      "`fecha_cierre` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_objetivo`)",
      "KEY `idx_proyecto_objetivos_proyecto` (`id_proyecto`, `estatus`)",
      "KEY `idx_proyecto_objetivos_prioridad` (`prioridad`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_proyecto_tareas", array(
      "`id_tarea` INT NOT NULL AUTO_INCREMENT",
      "`id_proyecto` INT NOT NULL",
      "`id_objetivo` INT NULL",
      "`titulo` VARCHAR(180) NOT NULL",
      "`descripcion` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'normal'",
      "`id_responsable` INT NULL",
      "`area_responsable` VARCHAR(80) NULL",
      "`modulo_relacionado` VARCHAR(60) NULL",
      "`origen` VARCHAR(60) NOT NULL DEFAULT 'manual'",
      "`url_contexto` VARCHAR(700) NULL",
      "`requiere_autorizacion` TINYINT(1) NOT NULL DEFAULT 0",
      "`fecha_vencimiento` DATE NULL",
      "`fecha_cierre` DATETIME NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_tarea`)",
      "KEY `idx_proyecto_tareas_proyecto` (`id_proyecto`, `estatus`)",
      "KEY `idx_proyecto_tareas_objetivo` (`id_objetivo`, `estatus`)",
      "KEY `idx_proyecto_tareas_responsable` (`id_responsable`, `estatus`)",
      "KEY `idx_proyecto_tareas_modulo` (`modulo_relacionado`, `estatus`)",
      "KEY `idx_proyecto_tareas_vencimiento` (`fecha_vencimiento`, `estatus`)",
      "KEY `idx_proyecto_tareas_prioridad` (`prioridad`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_proyecto_comentarios", array(
      "`id_comentario` INT NOT NULL AUTO_INCREMENT",
      "`id_proyecto` INT NOT NULL",
      "`id_tarea` INT NULL",
      "`tipo` VARCHAR(40) NOT NULL DEFAULT 'comentario'",
      "`comentario` TEXT NOT NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_comentario`)",
      "KEY `idx_proyecto_comentarios_proyecto` (`id_proyecto`, `fecha_registro`)",
      "KEY `idx_proyecto_comentarios_tarea` (`id_tarea`, `fecha_registro`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_proyecto_adjuntos", array(
      "`id_adjunto` INT NOT NULL AUTO_INCREMENT",
      "`id_proyecto` INT NOT NULL",
      "`id_tarea` INT NULL",
      "`nombre_original` VARCHAR(220) NOT NULL",
      "`ruta_archivo` VARCHAR(700) NOT NULL",
      "`tipo_mime` VARCHAR(120) NULL",
      "`tamano_bytes` BIGINT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_adjunto`)",
      "KEY `idx_proyecto_adjuntos_proyecto` (`id_proyecto`, `estatus`)",
      "KEY `idx_proyecto_adjuntos_tarea` (`id_tarea`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_proyecto_eventos", array(
      "`id_evento` INT NOT NULL AUTO_INCREMENT",
      "`id_proyecto` INT NOT NULL",
      "`id_tarea` INT NULL",
      "`tipo` VARCHAR(60) NOT NULL",
      "`descripcion` TEXT NULL",
      "`datos_json` LONGTEXT NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_evento`)",
      "KEY `idx_proyecto_eventos_proyecto` (`id_proyecto`, `fecha_registro`)",
      "KEY `idx_proyecto_eventos_tarea` (`id_tarea`, `fecha_registro`)",
      "KEY `idx_proyecto_eventos_tipo` (`tipo`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan de Proyectos ejecutado" : "Plan de Proyectos generado en dry-run",
      "depurar" => array(
        "ejecutar" => $ejecutar,
        "tablas" => $this->tablasProyectos(),
        "plan" => $plan,
        "resumen" => $this->resumenPlan($plan),
        "reglas" => array(
          "El esquema se crea vacio; no precarga tareas ni avances de otros modulos.",
          "Proyectos organiza trabajo; no reemplaza documentos vivos ni auditoria SYS.",
          "Las tareas pueden generar notificaciones persistentes cuando tengan responsable o area.",
          "Cualquier ejecucion real requiere respaldo externo y autorizacion explicita."
        )
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: definir columnas minimas auditables de Proyectos.
   * Impacto: Proyectos ERP; mantiene contrato estable para UI y endpoints.
   */
  private function columnasRequeridas() {
    return array(
      "erp_proyectos" => array(
        "id_proyecto", "folio", "nombre", "descripcion", "tipo", "modulo_relacionado",
        "estatus", "prioridad", "id_responsable", "creado_por", "fecha_inicio",
        "fecha_objetivo", "fecha_cierre", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_proyecto_objetivos" => array(
        "id_objetivo", "id_proyecto", "titulo", "descripcion", "estatus", "prioridad",
        "orden", "fecha_objetivo", "fecha_cierre", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_proyecto_tareas" => array(
        "id_tarea", "id_proyecto", "id_objetivo", "titulo", "descripcion", "estatus",
        "prioridad", "id_responsable", "area_responsable", "modulo_relacionado", "origen",
        "url_contexto", "requiere_autorizacion", "fecha_vencimiento", "fecha_cierre",
        "creado_por", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_proyecto_comentarios" => array(
        "id_comentario", "id_proyecto", "id_tarea", "tipo", "comentario", "creado_por",
        "fecha_registro"
      ),
      "erp_proyecto_adjuntos" => array(
        "id_adjunto", "id_proyecto", "id_tarea", "nombre_original", "ruta_archivo",
        "tipo_mime", "tamano_bytes", "estatus", "creado_por", "fecha_registro"
      ),
      "erp_proyecto_eventos" => array(
        "id_evento", "id_proyecto", "id_tarea", "tipo", "descripcion", "datos_json",
        "creado_por", "fecha_registro"
      )
    );
  }

  private function resumenPlan($plan) {
    $resumen = array("total" => count($plan), "errores" => 0, "ejecutados" => 0, "pendientes" => 0);
    foreach ($plan as $paso) {
      if (!empty($paso["error"])) {
        $resumen["errores"]++;
      }
      if (!empty($paso["depurar"]["ejecutado"])) {
        $resumen["ejecutados"]++;
      }
      if (isset($paso["depurar"]["ejecutado"]) && $paso["depurar"]["ejecutado"] === false && $paso["tipo"] === "info") {
        $resumen["pendientes"]++;
      }
    }
    return $resumen;
  }
}
