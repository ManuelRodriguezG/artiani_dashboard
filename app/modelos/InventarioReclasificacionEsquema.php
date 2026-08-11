<?php

class InventarioReclasificacionEsquema extends DBSchema {

    public function auditarInventarioReclasificacion() {
        $tablas = array(
            "erp_catalogo_sku_reclasificaciones" => array(
                "id_sku_reclasificacion", "id_sku_origen", "id_sku_destino", "tipo_reclasificacion",
                "conserva_lote", "conserva_caducidad", "conserva_costo", "permite_unidad_fisica",
                "requiere_autorizacion", "estatus", "observaciones", "creado_por", "actualizado_por",
                "fecha_registro", "fecha_actualizacion"
            ),
            "erp_inventario_reclasificaciones" => array(
                "id_reclasificacion_inventario", "folio", "id_almacen", "estatus", "motivo",
                "evidencia_resumen", "costo_politica", "requiere_autorizacion", "autorizado_por",
                "creado_por", "confirmado_por", "fecha_reclasificacion", "fecha_registro", "fecha_actualizacion"
            ),
            "erp_inventario_reclasificaciones_detalle" => array(
                "id_reclasificacion_detalle", "id_reclasificacion_inventario", "id_sku_reclasificacion",
                "id_sku_origen", "id_sku_destino", "id_existencia_origen", "id_existencia_destino",
                "id_unidad_origen", "id_unidad_destino", "id_almacen", "ubicacion_id", "lote",
                "fecha_caducidad", "cantidad", "costo_unitario_origen", "costo_unitario_destino",
                "costo_total_origen", "costo_total_destino", "costo_diferencia", "id_movimiento_salida",
                "id_movimiento_entrada", "estado_unidad_origen_despues", "observaciones", "fecha_registro"
            ),
            "erp_inventario_reclasificacion_adjuntos" => array(
                "id_reclasificacion_adjunto", "id_reclasificacion_inventario", "tipo_adjunto",
                "nombre_archivo", "ruta_archivo", "mime_type", "tamano_bytes", "estatus",
                "subido_por", "fecha_registro"
            )
        );
        $pendientes = array();
        foreach ($tablas as $tabla => $columnas) {
            if (!$this->tablaExiste($tabla)) {
                $pendientes[] = array("tipo" => "tabla_faltante", "tabla" => $tabla, "mensaje" => "Falta tabla de reclasificacion");
                continue;
            }
            foreach ($columnas as $columna) {
                if (!$this->columnaExiste($tabla, $columna)) {
                    $pendientes[] = array("tipo" => "columna_faltante", "tabla" => $tabla, "columna" => $columna, "mensaje" => "Falta columna requerida");
                }
            }
        }
        return array(
            "error" => false,
            "tipo" => empty($pendientes) ? "success" : "warning",
            "mensaje" => empty($pendientes) ? "Esquema de reclasificacion completo" : "Hay pendientes en esquema de reclasificacion",
            "depurar" => array("tiene_pendientes" => !empty($pendientes), "pendientes" => $pendientes)
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-08
     * Proposito: declarar el esquema de Inventario > Reclasificacion sin mezclarlo con Almacen.
     * Impacto: prepara Catalogo de reglas, encabezados, detalle de movimientos dobles y evidencia.
     * Contrato: dry-run por defecto; no aplicar sin respaldo externo y autorizacion textual.
     */
    public function planActualizarInventarioReclasificacion($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();

        $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_reclasificaciones", array(
            "`id_sku_reclasificacion` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_sku_origen` BIGINT NOT NULL",
            "`id_sku_destino` BIGINT NOT NULL",
            "`tipo_reclasificacion` VARCHAR(40) NOT NULL DEFAULT 'clasificacion_interna'",
            "`conserva_lote` TINYINT(1) NOT NULL DEFAULT 1",
            "`conserva_caducidad` TINYINT(1) NOT NULL DEFAULT 1",
            "`conserva_costo` TINYINT(1) NOT NULL DEFAULT 1",
            "`permite_unidad_fisica` TINYINT(1) NOT NULL DEFAULT 1",
            "`requiere_autorizacion` TINYINT(1) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`actualizado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_sku_reclasificacion`)",
            "UNIQUE KEY `uk_catalogo_reclas_sku` (`id_sku_origen`, `id_sku_destino`)",
            "KEY `idx_catalogo_reclas_origen` (`id_sku_origen`)",
            "KEY `idx_catalogo_reclas_destino` (`id_sku_destino`)",
            "KEY `idx_catalogo_reclas_estatus` (`estatus`)",
            "CONSTRAINT `fk_catalogo_reclas_origen` FOREIGN KEY (`id_sku_origen`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_catalogo_reclas_destino` FOREIGN KEY (`id_sku_destino`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_reclasificaciones", array(
            "`id_reclasificacion_inventario` INT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(60) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'confirmada'",
            "`motivo` TEXT NOT NULL",
            "`evidencia_resumen` TEXT NULL",
            "`costo_politica` VARCHAR(40) NOT NULL DEFAULT 'conservar_origen'",
            "`requiere_autorizacion` TINYINT(1) NOT NULL DEFAULT 0",
            "`autorizado_por` INT NULL",
            "`creado_por` INT NULL",
            "`confirmado_por` INT NULL",
            "`fecha_reclasificacion` DATETIME NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_reclasificacion_inventario`)",
            "UNIQUE KEY `uk_inv_reclas_folio` (`folio`)",
            "KEY `idx_inv_reclas_almacen` (`id_almacen`)",
            "KEY `idx_inv_reclas_estatus` (`estatus`)",
            "KEY `idx_inv_reclas_fecha` (`fecha_reclasificacion`)",
            "CONSTRAINT `fk_inv_reclas_almacen` FOREIGN KEY (`id_almacen`) REFERENCES `erp_almacenes` (`id_almacen`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_reclasificaciones_detalle", array(
            "`id_reclasificacion_detalle` INT NOT NULL AUTO_INCREMENT",
            "`id_reclasificacion_inventario` INT NOT NULL",
            "`id_sku_reclasificacion` BIGINT NULL",
            "`id_sku_origen` BIGINT NOT NULL",
            "`id_sku_destino` BIGINT NOT NULL",
            "`id_existencia_origen` INT NOT NULL",
            "`id_existencia_destino` INT NULL",
            "`id_unidad_origen` INT NULL",
            "`id_unidad_destino` INT NULL",
            "`id_almacen` INT NOT NULL",
            "`ubicacion_id` INT NULL",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`cantidad` DECIMAL(18,6) NOT NULL",
            "`costo_unitario_origen` DECIMAL(12,4) NOT NULL DEFAULT 0.0000",
            "`costo_unitario_destino` DECIMAL(12,4) NOT NULL DEFAULT 0.0000",
            "`costo_total_origen` DECIMAL(12,4) NOT NULL DEFAULT 0.0000",
            "`costo_total_destino` DECIMAL(12,4) NOT NULL DEFAULT 0.0000",
            "`costo_diferencia` DECIMAL(12,4) NOT NULL DEFAULT 0.0000",
            "`id_movimiento_salida` INT NULL",
            "`id_movimiento_entrada` INT NULL",
            "`estado_unidad_origen_despues` VARCHAR(30) NULL",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_reclasificacion_detalle`)",
            "KEY `idx_inv_reclas_det_reclas` (`id_reclasificacion_inventario`)",
            "KEY `idx_inv_reclas_det_regla` (`id_sku_reclasificacion`)",
            "KEY `idx_inv_reclas_det_origen` (`id_sku_origen`)",
            "KEY `idx_inv_reclas_det_destino` (`id_sku_destino`)",
            "KEY `idx_inv_reclas_det_exist_origen` (`id_existencia_origen`)",
            "KEY `idx_inv_reclas_det_exist_destino` (`id_existencia_destino`)",
            "KEY `idx_inv_reclas_det_unidad_origen` (`id_unidad_origen`)",
            "KEY `idx_inv_reclas_det_unidad_destino` (`id_unidad_destino`)",
            "KEY `idx_inv_reclas_det_mov_salida` (`id_movimiento_salida`)",
            "KEY `idx_inv_reclas_det_mov_entrada` (`id_movimiento_entrada`)",
            "CONSTRAINT `fk_inv_reclas_det_reclas` FOREIGN KEY (`id_reclasificacion_inventario`) REFERENCES `erp_inventario_reclasificaciones` (`id_reclasificacion_inventario`)",
            "CONSTRAINT `fk_inv_reclas_det_regla` FOREIGN KEY (`id_sku_reclasificacion`) REFERENCES `erp_catalogo_sku_reclasificaciones` (`id_sku_reclasificacion`)",
            "CONSTRAINT `fk_inv_reclas_det_sku_origen` FOREIGN KEY (`id_sku_origen`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_inv_reclas_det_sku_destino` FOREIGN KEY (`id_sku_destino`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_inv_reclas_det_exist_origen` FOREIGN KEY (`id_existencia_origen`) REFERENCES `erp_inventario_existencias` (`id_existencia_inventario`)",
            "CONSTRAINT `fk_inv_reclas_det_exist_destino` FOREIGN KEY (`id_existencia_destino`) REFERENCES `erp_inventario_existencias` (`id_existencia_inventario`)",
            "CONSTRAINT `fk_inv_reclas_det_unidad_origen` FOREIGN KEY (`id_unidad_origen`) REFERENCES `erp_inventario_unidades` (`id_inventario_unidad`)",
            "CONSTRAINT `fk_inv_reclas_det_unidad_destino` FOREIGN KEY (`id_unidad_destino`) REFERENCES `erp_inventario_unidades` (`id_inventario_unidad`)",
            "CONSTRAINT `fk_inv_reclas_det_mov_salida` FOREIGN KEY (`id_movimiento_salida`) REFERENCES `erp_inventario_movimientos` (`id_movimiento_inventario`)",
            "CONSTRAINT `fk_inv_reclas_det_mov_entrada` FOREIGN KEY (`id_movimiento_entrada`) REFERENCES `erp_inventario_movimientos` (`id_movimiento_inventario`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_reclasificacion_adjuntos", array(
            "`id_reclasificacion_adjunto` INT NOT NULL AUTO_INCREMENT",
            "`id_reclasificacion_inventario` INT NOT NULL",
            "`tipo_adjunto` VARCHAR(40) NOT NULL DEFAULT 'evidencia'",
            "`nombre_archivo` VARCHAR(255) NOT NULL",
            "`ruta_archivo` VARCHAR(500) NOT NULL",
            "`mime_type` VARCHAR(120) NULL",
            "`tamano_bytes` BIGINT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activo'",
            "`subido_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_reclasificacion_adjunto`)",
            "KEY `idx_inv_reclas_adj_reclas` (`id_reclasificacion_inventario`)",
            "KEY `idx_inv_reclas_adj_estatus` (`estatus`)",
            "CONSTRAINT `fk_inv_reclas_adj_reclas` FOREIGN KEY (`id_reclasificacion_inventario`) REFERENCES `erp_inventario_reclasificaciones` (`id_reclasificacion_inventario`)"
        ), $opciones, $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de reclasificacion ejecutado" : "Plan de reclasificacion generado en dry-run",
            "depurar" => $plan
        );
    }
}
