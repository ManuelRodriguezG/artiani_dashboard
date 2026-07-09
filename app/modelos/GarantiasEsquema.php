<?php

class GarantiasEsquema extends DBSchema {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: listar las tablas requeridas para el dominio formal de garantias.
     * Impacto: Garantias ERP; sirve a auditorias y planes DDL sin consultar reglas de negocio en UI.
     * Contrato: solo devuelve nombres; no consulta ni modifica BD.
     */
    public function tablasGarantias() {
        return array(
            "erp_garantias_politicas",
            "erp_garantias_politicas_reglas",
            "erp_ventas_detalle_garantias",
            "erp_garantias_reclamos",
            "erp_garantias_reclamos_eventos",
            "erp_garantias_adjuntos",
            "erp_garantias_proveedor_seguimiento"
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: auditar estructura minima de garantias antes de habilitar politicas, snapshots o reclamos.
     * Impacto: Garantias ERP; no crea tablas ni columnas.
     * Contrato: read-only sobre INFORMATION_SCHEMA; respuesta JSON estandar.
     */
    public function auditarGarantiasErp() {
        $requeridas = $this->columnasRequeridas();
        $pendientes = array();

        foreach ($this->tablasGarantias() as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                $pendientes[] = array(
                    "tipo" => "tabla_faltante",
                    "tabla" => $tabla,
                    "mensaje" => "Falta tabla requerida para Garantias ERP"
                );
                continue;
            }

            foreach ($requeridas[$tabla] as $columna) {
                if (!$this->columnaExiste($tabla, $columna)) {
                    $pendientes[] = array(
                        "tipo" => "columna_faltante",
                        "tabla" => $tabla,
                        "columna" => $columna,
                        "mensaje" => "Falta columna requerida para Garantias ERP"
                    );
                }
            }
        }

        return array(
            "error" => false,
            "tipo" => empty($pendientes) ? "success" : "warning",
            "mensaje" => empty($pendientes)
                ? "El esquema de Garantias ERP esta completo"
                : "Hay pendientes en el esquema de Garantias ERP",
            "depurar" => array(
                "tiene_pendientes" => !empty($pendientes),
                "pendientes" => $pendientes,
                "tablas" => $this->tablasGarantias(),
                "regla" => "Auditoria read-only; no ejecuta DDL."
            )
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: generar plan DDL para Garantias ERP con opcion de ejecucion controlada.
     * Impacto: Garantias ERP, Ventas snapshot, Proveedores seguimiento; no afecta inventario directamente.
     * Contrato: ejecutar=false genera SQL sin aplicar; ejecutar=true requiere respaldo externo y autorizacion desde controlador.
     */
    public function planActualizarGarantiasErp($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_politicas", array(
            "`id_garantia_politica` BIGINT NOT NULL AUTO_INCREMENT",
            "`codigo` VARCHAR(60) NOT NULL",
            "`nombre` VARCHAR(180) NOT NULL",
            "`descripcion` TEXT NULL",
            "`tipo_garantia` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia'",
            "`duracion_valor` INT NOT NULL DEFAULT 0",
            "`unidad_duracion` VARCHAR(20) NOT NULL DEFAULT 'dias'",
            "`coberturas_json` TEXT NULL",
            "`requisitos_json` TEXT NULL",
            "`exclusiones_json` TEXT NULL",
            "`requiere_ticket` TINYINT(1) NOT NULL DEFAULT 1",
            "`requiere_cliente` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_serie` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_lote` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_empaque` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_diagnostico` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_fotos` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_autorizacion_supervisor` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_validacion_proveedor` TINYINT(1) NOT NULL DEFAULT 0",
            "`permite_cambio` TINYINT(1) NOT NULL DEFAULT 0",
            "`permite_reparacion` TINYINT(1) NOT NULL DEFAULT 0",
            "`permite_devolucion_dinero` TINYINT(1) NOT NULL DEFAULT 0",
            "`permite_nota_credito` TINYINT(1) NOT NULL DEFAULT 0",
            "`permite_envio_proveedor` TINYINT(1) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`creado_por` INT NULL",
            "`actualizado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_garantia_politica`)",
            "UNIQUE KEY `idx_garantia_politica_codigo` (`codigo`)",
            "KEY `idx_garantia_politica_tipo_estatus` (`tipo_garantia`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_politicas_reglas", array(
            "`id_regla_garantia` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_garantia_politica` BIGINT NOT NULL",
            "`ambito` VARCHAR(30) NOT NULL",
            "`id_referencia` BIGINT NOT NULL DEFAULT 0",
            "`prioridad` INT NOT NULL DEFAULT 100",
            "`canal` VARCHAR(40) NULL",
            "`id_almacen` INT NULL",
            "`vigencia_desde` DATE NULL",
            "`vigencia_hasta` DATE NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`actualizado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_regla_garantia`)",
            "KEY `idx_garantia_regla_politica` (`id_garantia_politica`, `estatus`)",
            "KEY `idx_garantia_regla_resolver` (`ambito`, `id_referencia`, `estatus`, `prioridad`)",
            "KEY `idx_garantia_regla_canal_almacen` (`canal`, `id_almacen`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_ventas_detalle_garantias", array(
            "`id_venta_detalle_garantia` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_venta` BIGINT NOT NULL",
            "`id_venta_detalle` BIGINT NOT NULL",
            "`id_producto_erp` BIGINT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_garantia_politica` BIGINT NULL",
            "`id_regla_garantia` BIGINT NULL",
            "`tipo_garantia_snapshot` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia'",
            "`nombre_politica_snapshot` VARCHAR(180) NULL",
            "`duracion_valor_snapshot` INT NOT NULL DEFAULT 0",
            "`unidad_duracion_snapshot` VARCHAR(20) NOT NULL DEFAULT 'dias'",
            "`coberturas_snapshot` TEXT NULL",
            "`requisitos_snapshot` TEXT NULL",
            "`exclusiones_snapshot` TEXT NULL",
            "`resumen_ticket` VARCHAR(500) NULL",
            "`fecha_inicio` DATE NULL",
            "`fecha_vencimiento` DATE NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'vigente'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_venta_detalle_garantia`)",
            "UNIQUE KEY `idx_venta_detalle_garantia_detalle` (`id_venta_detalle`)",
            "KEY `idx_venta_detalle_garantia_venta` (`id_venta`, `estatus`)",
            "KEY `idx_venta_detalle_garantia_sku` (`id_sku_erp`, `estatus`)",
            "KEY `idx_venta_detalle_garantia_vencimiento` (`fecha_vencimiento`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_reclamos", array(
            "`id_reclamo_garantia` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_venta` BIGINT NULL",
            "`id_venta_detalle` BIGINT NULL",
            "`id_venta_detalle_garantia` BIGINT NULL",
            "`id_cliente` BIGINT NULL",
            "`id_producto_erp` BIGINT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_inventario_unidad` BIGINT NULL",
            "`id_garantia_politica` BIGINT NULL",
            "`id_devolucion` BIGINT NULL",
            "`id_proveedor` BIGINT NULL",
            "`tipo_garantia` VARCHAR(40) NOT NULL DEFAULT 'sin_garantia'",
            "`motivo` VARCHAR(180) NULL",
            "`descripcion` TEXT NULL",
            "`diagnostico` TEXT NULL",
            "`elegibilidad` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`decision` VARCHAR(40) NULL",
            "`decision_inventario` VARCHAR(40) NULL",
            "`fecha_venta` DATETIME NULL",
            "`fecha_vencimiento` DATE NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_resolucion` DATETIME NULL",
            "`creado_por` INT NULL",
            "`autorizado_por` INT NULL",
            "`resuelto_por` INT NULL",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_reclamo_garantia`)",
            "UNIQUE KEY `idx_garantia_reclamo_folio` (`folio`)",
            "KEY `idx_garantia_reclamo_venta` (`id_venta`, `id_venta_detalle`)",
            "KEY `idx_garantia_reclamo_sku` (`id_sku_erp`, `estatus`)",
            "KEY `idx_garantia_reclamo_unidad` (`id_inventario_unidad`)",
            "KEY `idx_garantia_reclamo_cliente` (`id_cliente`, `estatus`)",
            "KEY `idx_garantia_reclamo_proveedor` (`id_proveedor`, `estatus`)",
            "KEY `idx_garantia_reclamo_estatus_fecha` (`estatus`, `fecha_registro`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_reclamos_eventos", array(
            "`id_evento_garantia` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_reclamo_garantia` BIGINT NOT NULL",
            "`tipo_evento` VARCHAR(40) NOT NULL",
            "`estatus_anterior` VARCHAR(30) NULL",
            "`estatus_nuevo` VARCHAR(30) NULL",
            "`decision` VARCHAR(40) NULL",
            "`comentario` TEXT NULL",
            "`datos_json` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_evento_garantia`)",
            "KEY `idx_garantia_evento_reclamo` (`id_reclamo_garantia`, `fecha_registro`)",
            "KEY `idx_garantia_evento_tipo` (`tipo_evento`, `fecha_registro`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_adjuntos", array(
            "`id_adjunto_garantia` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_reclamo_garantia` BIGINT NOT NULL",
            "`tipo_adjunto` VARCHAR(40) NOT NULL DEFAULT 'evidencia'",
            "`ruta` VARCHAR(500) NOT NULL",
            "`nombre_original` VARCHAR(255) NULL",
            "`mime_type` VARCHAR(120) NULL",
            "`tamano_bytes` BIGINT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_adjunto_garantia`)",
            "KEY `idx_garantia_adjunto_reclamo` (`id_reclamo_garantia`, `estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_garantias_proveedor_seguimiento", array(
            "`id_garantia_proveedor` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_reclamo_garantia` BIGINT NOT NULL",
            "`id_proveedor` BIGINT NOT NULL",
            "`folio_proveedor` VARCHAR(120) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente_envio'",
            "`decision_proveedor` VARCHAR(40) NULL",
            "`fecha_envio` DATETIME NULL",
            "`fecha_respuesta` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`actualizado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_garantia_proveedor`)",
            "KEY `idx_garantia_proveedor_reclamo` (`id_reclamo_garantia`, `estatus`)",
            "KEY `idx_garantia_proveedor_proveedor` (`id_proveedor`, `estatus`, `fecha_registro`)"
        ), $opciones, $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de Garantias ERP ejecutado" : "Plan de Garantias ERP generado en dry-run",
            "depurar" => array(
                "ejecutar" => $ejecutar,
                "tablas" => $this->tablasGarantias(),
                "plan" => $plan,
                "resumen" => $this->resumenPlan($plan),
                "reglas" => array(
                    "Dry-run: no crea tablas ni modifica BD cuando ejecutar=false.",
                    "Garantias no reemplaza devoluciones de Ventas.",
                    "Garantias no mueve inventario; Almacen/Inventario conserva ese contrato.",
                    "Cualquier ejecucion real requiere respaldo externo y autorizacion explicita."
                )
            )
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: definir columnas minimas que debe auditar el esquema de garantias.
     * Impacto: Garantias ERP; mantiene auditable la cobertura sin abrir el dump completo.
     * Contrato: estructura interna para auditoria read-only.
     */
    private function columnasRequeridas() {
        return array(
            "erp_garantias_politicas" => array(
                "id_garantia_politica", "codigo", "nombre", "tipo_garantia", "duracion_valor",
                "unidad_duracion", "coberturas_json", "requisitos_json", "exclusiones_json",
                "estatus", "fecha_registro", "fecha_actualizacion"
            ),
            "erp_garantias_politicas_reglas" => array(
                "id_regla_garantia", "id_garantia_politica", "ambito", "id_referencia",
                "prioridad", "canal", "id_almacen", "vigencia_desde", "vigencia_hasta", "estatus"
            ),
            "erp_ventas_detalle_garantias" => array(
                "id_venta_detalle_garantia", "id_venta", "id_venta_detalle", "id_sku_erp",
                "id_garantia_politica", "tipo_garantia_snapshot", "fecha_inicio",
                "fecha_vencimiento", "estatus"
            ),
            "erp_garantias_reclamos" => array(
                "id_reclamo_garantia", "folio", "id_venta", "id_venta_detalle",
                "id_venta_detalle_garantia", "id_cliente", "id_sku_erp", "id_inventario_unidad",
                "estatus", "decision", "fecha_registro", "fecha_resolucion"
            ),
            "erp_garantias_reclamos_eventos" => array(
                "id_evento_garantia", "id_reclamo_garantia", "tipo_evento", "comentario",
                "creado_por", "fecha_registro"
            ),
            "erp_garantias_adjuntos" => array(
                "id_adjunto_garantia", "id_reclamo_garantia", "tipo_adjunto", "ruta",
                "nombre_original", "estatus", "creado_por", "fecha_registro"
            ),
            "erp_garantias_proveedor_seguimiento" => array(
                "id_garantia_proveedor", "id_reclamo_garantia", "id_proveedor",
                "folio_proveedor", "estatus", "decision_proveedor", "fecha_registro"
            )
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-27
     * Proposito: resumir el plan DDL generado para saber si hay pendientes antes de pedir autorizacion.
     * Impacto: Garantias ERP; mejora el cierre de auditoria sin exponer SQL completo en UI.
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
