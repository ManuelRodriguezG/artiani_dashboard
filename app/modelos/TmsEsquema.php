<?php

class TmsEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar las tablas propias del modulo TMS/Delivery.
   * Impacto: TMS Delivery; evita que Ventas, Garantias o Catalogo sean duenos del servicio logistico.
   * Contrato: solo devuelve nombres de tablas; no consulta ni modifica BD.
   */
  public function tablasTms() {
    return array(
      "erp_tms_servicios",
      "erp_tms_servicios_detalle",
      "erp_tms_servicios_costos",
      "erp_tms_eventos",
      "erp_tms_evidencias"
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: auditar estructura minima de TMS antes de habilitar servicios logisticos.
   * Impacto: TMS Delivery; no crea tablas, no toca ventas, no mueve inventario y no decide garantias.
   * Contrato: read-only sobre INFORMATION_SCHEMA; respuesta JSON estandar del proyecto.
   */
  public function auditarTmsDelivery() {
    $requeridas = $this->columnasRequeridas();
    $pendientes = array();

    foreach ($this->tablasTms() as $tabla) {
      if (!$this->tablaExiste($tabla)) {
        $pendientes[] = array(
          "tipo" => "tabla_faltante",
          "tabla" => $tabla,
          "mensaje" => "Falta tabla requerida para TMS Delivery"
        );
        continue;
      }

      foreach ($requeridas[$tabla] as $columna) {
        if (!$this->columnaExiste($tabla, $columna)) {
          $pendientes[] = array(
            "tipo" => "columna_faltante",
            "tabla" => $tabla,
            "columna" => $columna,
            "mensaje" => "Falta columna requerida para TMS Delivery"
          );
        }
      }
    }

    return array(
      "error" => false,
      "tipo" => empty($pendientes) ? "success" : "warning",
      "mensaje" => empty($pendientes)
        ? "El esquema de TMS Delivery esta completo"
        : "Hay pendientes en el esquema de TMS Delivery",
      "depurar" => array(
        "tiene_pendientes" => !empty($pendientes),
        "pendientes" => $pendientes,
        "tablas" => $this->tablasTms(),
        "regla" => "Auditoria read-only; no ejecuta DDL."
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: generar plan DDL para TMS Delivery con opcion de ejecucion controlada futura.
   * Impacto: TMS Delivery; crea estructura logistica independiente y no agrega FK obligatoria hacia Ventas.
   * Contrato: ejecutar=false genera SQL sin aplicar; ejecutar=true requiere respaldo externo y autorizacion desde controlador futuro.
   */
  public function planActualizarTmsDelivery($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("erp_tms_servicios", array(
      "`id_tms_servicio` INT NOT NULL AUTO_INCREMENT",
      "`folio` VARCHAR(30) NOT NULL",
      "`solicitado_por_modulo` VARCHAR(30) NOT NULL DEFAULT 'manual'",
      "`solicitado_por_tipo` VARCHAR(40) NOT NULL DEFAULT 'solicitud_manual'",
      "`solicitado_por_id` INT NULL",
      "`referencia_externa` VARCHAR(80) NULL",
      "`motivo_logistico` VARCHAR(40) NOT NULL DEFAULT 'venta_inicial'",
      "`id_cliente_crm` INT NULL",
      "`id_direccion_crm` INT NULL",
      "`cliente_nombre_snapshot` VARCHAR(180) NULL",
      "`cliente_contacto_snapshot` VARCHAR(120) NULL",
      "`direccion_snapshot` TEXT NULL",
      "`zona_snapshot` VARCHAR(120) NULL",
      "`tipo_servicio` VARCHAR(40) NOT NULL DEFAULT 'entrega_local'",
      "`estatus_servicio` VARCHAR(40) NOT NULL DEFAULT 'solicitada'",
      "`estatus_cobro` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
      "`resultado_logistico` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
      "`prioridad` VARCHAR(20) NOT NULL DEFAULT 'normal'",
      "`fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_programada` DATE NULL",
      "`ventana_inicio` TIME NULL",
      "`ventana_fin` TIME NULL",
      "`fecha_salida` DATETIME NULL",
      "`fecha_cierre` DATETIME NULL",
      "`creado_por` INT NULL",
      "`responsable_asignado` INT NULL",
      "`observaciones` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_tms_servicio`)",
      "UNIQUE KEY `idx_tms_servicios_folio` (`folio`)",
      "KEY `idx_tms_servicios_estado` (`estatus_servicio`, `resultado_logistico`)",
      "KEY `idx_tms_servicios_cobro` (`estatus_cobro`)",
      "KEY `idx_tms_servicios_programacion` (`fecha_programada`, `ventana_inicio`)",
      "KEY `idx_tms_servicios_cliente` (`id_cliente_crm`)",
      "KEY `idx_tms_servicios_responsable` (`responsable_asignado`)",
      "KEY `idx_tms_servicios_origen` (`solicitado_por_modulo`, `solicitado_por_tipo`, `solicitado_por_id`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_tms_servicios_detalle", array(
      "`id_tms_servicio_detalle` INT NOT NULL AUTO_INCREMENT",
      "`id_tms_servicio` INT NOT NULL",
      "`referencia_item_origen` VARCHAR(80) NULL",
      "`id_sku_erp` INT NULL",
      "`id_inventario_unidad` INT NULL",
      "`cantidad` DECIMAL(12,4) NOT NULL DEFAULT 1.0000",
      "`descripcion_snapshot` VARCHAR(255) NOT NULL",
      "`requiere_cuidado_especial` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus_preparacion` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
      "`observaciones` TEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_tms_servicio_detalle`)",
      "KEY `idx_tms_detalle_servicio` (`id_tms_servicio`)",
      "KEY `idx_tms_detalle_sku` (`id_sku_erp`)",
      "KEY `idx_tms_detalle_unidad` (`id_inventario_unidad`)",
      "CONSTRAINT `fk_tms_detalle_servicio` FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_tms_servicios_costos", array(
      "`id_tms_servicio_costo` INT NOT NULL AUTO_INCREMENT",
      "`id_tms_servicio` INT NOT NULL",
      "`precio_cobrado` DECIMAL(12,2) NOT NULL DEFAULT 0.00",
      "`costo_estimado` DECIMAL(12,2) NOT NULL DEFAULT 0.00",
      "`costo_real` DECIMAL(12,2) NULL",
      "`metodo_cobro` VARCHAR(40) NOT NULL DEFAULT 'no_aplica'",
      "`motivo_bonificacion` VARCHAR(180) NULL",
      "`autorizado_por` INT NULL",
      "`datos_snapshot` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_tms_servicio_costo`)",
      "UNIQUE KEY `idx_tms_costo_servicio` (`id_tms_servicio`)",
      "KEY `idx_tms_costo_autorizado` (`autorizado_por`)",
      "CONSTRAINT `fk_tms_costo_servicio` FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_tms_eventos", array(
      "`id_tms_evento` INT NOT NULL AUTO_INCREMENT",
      "`id_tms_servicio` INT NOT NULL",
      "`tipo_evento` VARCHAR(60) NOT NULL",
      "`estatus_anterior` VARCHAR(40) NULL",
      "`estatus_nuevo` VARCHAR(40) NULL",
      "`resultado_anterior` VARCHAR(40) NULL",
      "`resultado_nuevo` VARCHAR(40) NULL",
      "`comentario` TEXT NULL",
      "`latitud` DECIMAL(10,7) NULL",
      "`longitud` DECIMAL(10,7) NULL",
      "`payload_json` LONGTEXT NULL",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_tms_evento`)",
      "KEY `idx_tms_eventos_servicio` (`id_tms_servicio`)",
      "KEY `idx_tms_eventos_tipo` (`tipo_evento`)",
      "KEY `idx_tms_eventos_fecha` (`fecha_registro`)",
      "CONSTRAINT `fk_tms_eventos_servicio` FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("erp_tms_evidencias", array(
      "`id_tms_evidencia` INT NOT NULL AUTO_INCREMENT",
      "`id_tms_servicio` INT NOT NULL",
      "`tipo_evidencia` VARCHAR(40) NOT NULL DEFAULT 'nota'",
      "`ruta` VARCHAR(255) NULL",
      "`nombre_original` VARCHAR(180) NULL",
      "`descripcion` TEXT NULL",
      "`payload_json` LONGTEXT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
      "`creado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_cancelacion` DATETIME NULL",
      "PRIMARY KEY (`id_tms_evidencia`)",
      "KEY `idx_tms_evidencias_servicio` (`id_tms_servicio`)",
      "KEY `idx_tms_evidencias_tipo` (`tipo_evidencia`)",
      "CONSTRAINT `fk_tms_evidencias_servicio` FOREIGN KEY (`id_tms_servicio`) REFERENCES `erp_tms_servicios` (`id_tms_servicio`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan de TMS Delivery ejecutado" : "Plan de TMS Delivery generado en dry-run",
      "depurar" => array(
        "ejecutar" => $ejecutar,
        "tablas" => $this->tablasTms(),
        "plan" => $plan,
        "resumen" => $this->resumenPlan($plan),
        "reglas" => array(
          "Dry-run: no crea tablas ni modifica BD cuando ejecutar=false.",
          "TMS no es submodulo de Ventas.",
          "TMS no cancela ventas ni decide garantias.",
          "TMS no mueve inventario por si mismo.",
          "Cualquier ejecucion real requiere respaldo externo y autorizacion explicita."
        )
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: definir columnas minimas que debe auditar el esquema de TMS Delivery.
   * Impacto: TMS Delivery; mantiene el contrato independiente sin abrir dump pesado.
   * Contrato: estructura interna para auditoria read-only.
   */
  private function columnasRequeridas() {
    return array(
      "erp_tms_servicios" => array(
        "id_tms_servicio", "folio", "solicitado_por_modulo", "solicitado_por_tipo",
        "solicitado_por_id", "referencia_externa", "motivo_logistico", "id_cliente_crm",
        "id_direccion_crm", "cliente_nombre_snapshot", "cliente_contacto_snapshot",
        "direccion_snapshot", "zona_snapshot", "tipo_servicio", "estatus_servicio",
        "estatus_cobro", "resultado_logistico", "prioridad", "fecha_solicitud",
        "fecha_programada", "ventana_inicio", "ventana_fin", "fecha_salida",
        "fecha_cierre", "creado_por", "responsable_asignado", "observaciones",
        "estatus", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_tms_servicios_detalle" => array(
        "id_tms_servicio_detalle", "id_tms_servicio", "referencia_item_origen",
        "id_sku_erp", "id_inventario_unidad", "cantidad", "descripcion_snapshot",
        "requiere_cuidado_especial", "estatus_preparacion", "observaciones",
        "estatus", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_tms_servicios_costos" => array(
        "id_tms_servicio_costo", "id_tms_servicio", "precio_cobrado", "costo_estimado",
        "costo_real", "metodo_cobro", "motivo_bonificacion", "autorizado_por",
        "datos_snapshot", "estatus", "fecha_registro", "fecha_actualizacion"
      ),
      "erp_tms_eventos" => array(
        "id_tms_evento", "id_tms_servicio", "tipo_evento", "estatus_anterior",
        "estatus_nuevo", "resultado_anterior", "resultado_nuevo", "comentario",
        "latitud", "longitud", "payload_json", "creado_por", "fecha_registro"
      ),
      "erp_tms_evidencias" => array(
        "id_tms_evidencia", "id_tms_servicio", "tipo_evidencia", "ruta",
        "nombre_original", "descripcion", "payload_json", "estatus", "creado_por",
        "fecha_registro", "fecha_cancelacion"
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: resumir el plan DDL generado para decidir si procede autorizacion futura.
   * Impacto: TMS Delivery; facilita revisar faltantes sin ejecutar cambios.
   * Contrato: recibe respuestas de DBSchema y devuelve contadores.
   */
  private function resumenPlan($plan) {
    $resumen = array("total" => count($plan), "existentes" => 0, "pendientes" => 0, "ejecutadas" => 0, "errores" => 0);
    foreach ($plan as $item) {
      if (!empty($item["error"])) {
        $resumen["errores"]++;
        continue;
      }
      $depurar = isset($item["depurar"]) && is_array($item["depurar"]) ? $item["depurar"] : array();
      if (isset($depurar["ejecutado"]) && $depurar["ejecutado"] === true) {
        $resumen["ejecutadas"]++;
      } elseif (isset($depurar["ejecutado"]) && $depurar["ejecutado"] === false && !isset($depurar["sql"])) {
        $resumen["existentes"]++;
      } else {
        $resumen["pendientes"]++;
      }
    }
    return $resumen;
  }
}
