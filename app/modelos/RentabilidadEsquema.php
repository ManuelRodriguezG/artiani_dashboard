<?php

class RentabilidadEsquema extends DBSchema {

    public function tablasRentabilidad() {
        return array(
            "erp_rentabilidad_escenarios",
            "erp_rentabilidad_snapshots",
            "erp_rentabilidad_snapshot_detalle",
            "erp_rentabilidad_recomendaciones",
            "erp_rentabilidad_aprobaciones_comerciales",
            "erp_rentabilidad_aprobaciones_bitacora"
        );
    }

    public function planActualizarRentabilidad($ejecutar = false) {
        $plan = array();

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_escenarios", array(
            "`id_escenario` INT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`clave` VARCHAR(60) NOT NULL",
            "`nombre` VARCHAR(120) NOT NULL",
            "`canal` ENUM('menudeo','mayoreo','alianza','liquidacion','otro') NOT NULL DEFAULT 'menudeo'",
            "`descuento_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`gasto_operativo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`comision_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`margen_objetivo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`descripcion` TEXT NULL",
            "`estatus` ENUM('borrador','activo','inactivo') NOT NULL DEFAULT 'borrador'",
            "`creado_por` INT NULL",
            "`fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_escenario`)",
            "UNIQUE KEY `idx_erp_rentabilidad_escenarios_clave` (`clave`)",
            "KEY `idx_erp_rentabilidad_escenarios_canal` (`canal`, `estatus`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_snapshots", array(
            "`id_snapshot` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_escenario` INT UNSIGNED NULL",
            "`canal` VARCHAR(40) NOT NULL",
            "`descuento_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`gasto_operativo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`comision_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`margen_objetivo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`filtros_json` JSON NULL",
            "`resumen_json` JSON NULL",
            "`estatus` ENUM('borrador','cerrado','cancelado') NOT NULL DEFAULT 'borrador'",
            "`creado_por` INT NULL",
            "`fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_snapshot`)",
            "UNIQUE KEY `idx_erp_rentabilidad_snapshots_folio` (`folio`)",
            "KEY `idx_erp_rentabilidad_snapshots_escenario` (`id_escenario`)",
            "KEY `idx_erp_rentabilidad_snapshots_fecha` (`fecha_registro`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_snapshot_detalle", array(
            "`id_snapshot_detalle` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`id_snapshot` BIGINT UNSIGNED NOT NULL",
            "`id_sku` INT NOT NULL",
            "`sku` VARCHAR(120) NOT NULL",
            "`producto` VARCHAR(255) NOT NULL",
            "`costo_real_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`origen_costo` VARCHAR(40) NOT NULL",
            "`precio_venta_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_escenario_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`margen_bruto_pct` DECIMAL(9,4) NULL",
            "`utilidad_bruta` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`gastos_estimados` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`utilidad_estimada` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`utilidad_estimada_pct` DECIMAL(9,4) NULL",
            "`precio_minimo_rentable` DECIMAL(18,6) NULL",
            "`cantidad_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`disponible_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`valor_inventario` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`riesgo_clave` VARCHAR(40) NOT NULL",
            "`riesgo_tipo` VARCHAR(20) NOT NULL",
            "`hallazgos_json` JSON NULL",
            "`evidencia_json` JSON NULL",
            "`recomendacion` TEXT NULL",
            "PRIMARY KEY (`id_snapshot_detalle`)",
            "KEY `idx_erp_rentabilidad_detalle_snapshot` (`id_snapshot`)",
            "KEY `idx_erp_rentabilidad_detalle_sku` (`id_sku`)",
            "KEY `idx_erp_rentabilidad_detalle_riesgo` (`riesgo_clave`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_recomendaciones", array(
            "`id_recomendacion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`id_snapshot_detalle` BIGINT UNSIGNED NULL",
            "`id_sku` INT NOT NULL",
            "`sku` VARCHAR(120) NOT NULL",
            "`canal` VARCHAR(40) NOT NULL",
            "`precio_actual_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_recomendado_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`motivo` VARCHAR(120) NOT NULL",
            "`estatus` ENUM('pendiente','aprobada','rechazada','aplicada','cancelada') NOT NULL DEFAULT 'pendiente'",
            "`comentario` TEXT NULL",
            "`creado_por` INT NULL",
            "`resuelto_por` INT NULL",
            "`fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_resolucion` DATETIME NULL",
            "PRIMARY KEY (`id_recomendacion`)",
            "KEY `idx_erp_rentabilidad_recomendaciones_sku` (`id_sku`, `estatus`)",
            "KEY `idx_erp_rentabilidad_recomendaciones_snapshot` (`id_snapshot_detalle`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de rentabilidad ejecutado" : "Plan de rentabilidad generado en dry-run",
            "depurar" => array(
                "ejecutar" => $ejecutar,
                "tablas" => $this->tablasRentabilidad(),
                "plan" => $plan,
                "resumen" => $this->resumenPlan($plan)
            )
        );
    }

    public function planAprobacionesComerciales($ejecutar = false) {
        $plan = array();

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_aprobaciones_comerciales", array(
            "`id_aprobacion` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NOT NULL",
            "`id_snapshot_detalle` BIGINT UNSIGNED NULL",
            "`id_recomendacion` BIGINT UNSIGNED NULL",
            "`id_sku` INT NOT NULL",
            "`sku` VARCHAR(120) NOT NULL",
            "`producto` VARCHAR(255) NOT NULL",
            "`canal` ENUM('menudeo','mayoreo','alianza','otro') NOT NULL DEFAULT 'menudeo'",
            "`costo_real_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`origen_costo` VARCHAR(40) NOT NULL",
            "`precio_actual_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`precio_minimo_rentable` DECIMAL(18,6) NULL",
            "`precio_aprobado_sin_impuesto` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`margen_bruto_pct` DECIMAL(9,4) NULL",
            "`utilidad_estimada` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`descuento_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`gasto_operativo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`comision_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`margen_objetivo_pct` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`dictamen_json` JSON NULL",
            "`evidencia_json` JSON NULL",
            "`bloqueos_json` JSON NULL",
            "`alertas_json` JSON NULL",
            "`estatus` ENUM('pendiente','aprobada','rechazada','cancelada','obsoleta','requiere_revision') NOT NULL DEFAULT 'pendiente'",
            "`comentario` TEXT NULL",
            "`respaldo_externo_ref` VARCHAR(255) NULL",
            "`creado_por` INT NULL",
            "`aprobado_por` INT NULL",
            "`fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_aprobacion` DATETIME NULL",
            "`fecha_revision` DATETIME NULL",
            "PRIMARY KEY (`id_aprobacion`)",
            "UNIQUE KEY `idx_erp_rentabilidad_aprobaciones_folio` (`folio`)",
            "KEY `idx_erp_rentabilidad_aprobaciones_sku` (`id_sku`, `estatus`)",
            "KEY `idx_erp_rentabilidad_aprobaciones_canal` (`canal`, `estatus`)",
            "KEY `idx_erp_rentabilidad_aprobaciones_snapshot` (`id_snapshot_detalle`)",
            "KEY `idx_erp_rentabilidad_aprobaciones_recomendacion` (`id_recomendacion`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_rentabilidad_aprobaciones_bitacora", array(
            "`id_bitacora` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT",
            "`id_aprobacion` BIGINT UNSIGNED NOT NULL",
            "`accion` ENUM('crear','aprobar','rechazar','cancelar','marcar_obsoleta','marcar_revision') NOT NULL",
            "`estatus_anterior` VARCHAR(40) NULL",
            "`estatus_nuevo` VARCHAR(40) NOT NULL",
            "`comentario` TEXT NULL",
            "`datos_antes_json` JSON NULL",
            "`datos_despues_json` JSON NULL",
            "`respaldo_externo_ref` VARCHAR(255) NULL",
            "`usuario_id` INT NULL",
            "`fecha_registro` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_bitacora`)",
            "KEY `idx_erp_rentabilidad_aprob_bit_aprobacion` (`id_aprobacion`)",
            "KEY `idx_erp_rentabilidad_aprob_bit_accion` (`accion`, `fecha_registro`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de aprobaciones comerciales ejecutado" : "Plan de aprobaciones comerciales generado en dry-run",
            "depurar" => array(
                "ejecutar" => $ejecutar,
                "tablas" => array(
                    "erp_rentabilidad_aprobaciones_comerciales",
                    "erp_rentabilidad_aprobaciones_bitacora"
                ),
                "plan" => $plan,
                "resumen" => $this->resumenPlan($plan),
                "reglas" => array(
                    "Dry-run: no crea tablas ni modifica BD cuando ejecutar=false.",
                    "La aprobacion comercial es evidencia interna; no aplica precios a Catalogo, Ventas, ecommerce ni Pedidos.",
                    "Cualquier ejecucion real requiere respaldo externo y autorizacion explicita."
                )
            )
        );
    }

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
            } else if (isset($depurar["ejecutado"]) && $depurar["ejecutado"] === false && !isset($depurar["sql"])) {
                $resumen["existentes"]++;
            } else {
                $resumen["pendientes"]++;
            }
        }
        return $resumen;
    }
}
