<?php

class AlmacenEsquema extends DBSchema {

    public function tablasAlmacenInventario() {
        return array(
            "erp_almacenes",
            "erp_almacen_recepciones",
            "erp_almacen_recepciones_detalle",
            "erp_almacen_recepciones_lotes",
            "erp_almacen_recepciones_incidencias",
            "erp_inventario_movimientos",
            "erp_inventario_existencias",
            "erp_inventario_unidades",
            "erp_almacen_ubicaciones",
            "erp_almacen_preparaciones",
            "erp_almacen_preparacion_consumos",
            "erp_almacen_preparacion_resultados",
            "erp_catalogo_sku_transformaciones",
            "erp_productos_control_inventario"
        );
    }

    public function columnasAlmacenInventario() {
        return array(
            "erp_almacenes" => array(
                "id_almacen",
                "codigo_almacen",
                "almacen",
                "nombre_comercial",
                "pais",
                "ciudad",
                "colonia",
                "codigo_postal",
                "calle",
                "numero_exterior",
                "numero_interior",
                "contacto_recepcion",
                "telefono_recepcion",
                "email_recepcion",
                "estado",
                "municipio",
                "referencias_direccion",
                "estatus",
                "tipo_almacen",
                "permite_recepcion",
                "permite_venta",
                "permite_preparacion",
                "permite_ajustes",
                "es_tecnico",
                "orden",
                "observaciones",
                "fecha_actualizacion"
            ),
            "erp_almacen_recepciones" => array(
                "id_recepcion_almacen",
                "id_orden_compra",
                "id_proveedor",
                "id_almacen",
                "folio",
                "folio_orden_compra",
                "estatus",
                "origen",
                "fecha_alerta",
                "fecha_inicio_recepcion",
                "fecha_cierre_recepcion",
                "recibido_por",
                "observaciones",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_almacen_recepciones_detalle" => array(
                "id_recepcion_detalle",
                "id_recepcion_almacen",
                "id_orden_compra",
                "id_orden_compra_detalle",
                "id_producto",
                "id_sku_erp",
                "id_sku_proveedor",
                "id_unidad_compra",
                "unidad_compra",
                "id_unidad_base",
                "unidad_base",
                "factor_conversion",
                "id_producto_proveedor",
                "id_producto_revision",
                "sku",
                "nombre_producto",
                "unidad",
                "cantidad_ordenada",
                "cantidad_ordenada_base",
                "cantidad_recibida",
                "cantidad_recibida_base",
                "cantidad_pendiente",
                "cantidad_pendiente_base",
                "costo_unitario",
                "costo_unitario_base",
                "estatus",
                "estatus_sku_recepcion",
                "requiere_clasificacion",
                "observaciones",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_almacen_recepciones_lotes" => array(
                "id_recepcion_lote",
                "id_recepcion_almacen",
                "id_recepcion_detalle",
                "id_producto",
                "id_sku_erp",
                "id_almacen",
                "lote",
                "lote_clave",
                "fecha_caducidad",
                "fecha_caducidad_clave",
                "ubicacion_id",
                "ubicacion_clave",
                "ubicacion",
                "cantidad_compra",
                "unidad_compra",
                "cantidad_base",
                "unidad_base",
                "factor_conversion",
                "codigo_recepcion_lote",
                "estatus_lote",
                "dias_alerta_caducidad",
                "dias_minimos_recepcion",
                "requiere_revision",
                "motivo_revision",
                "cantidad",
                "costo_unitario",
                "costo_unitario_base",
                "observaciones",
                "fecha_registro"
            ),
            "erp_almacen_recepciones_incidencias" => array(
                "id_recepcion_incidencia",
                "id_recepcion_almacen",
                "id_recepcion_detalle",
                "id_producto",
                "tipo_incidencia",
                "severidad",
                "cantidad",
                "lote",
                "fecha_caducidad",
                "accion_sugerida",
                "accion_tomada",
                "estatus",
                "observaciones",
                "fecha_registro",
                "fecha_resolucion"
            ),
            "erp_inventario_movimientos" => array(
                "id_movimiento_inventario",
                "id_producto",
                "id_sku_erp",
                "id_almacen",
                "tipo_movimiento",
                "origen_tipo",
                "origen_id",
                "origen_detalle_id",
                "id_recepcion_lote",
                "id_existencia_inventario",
                "codigo_existencia",
                "lote",
                "fecha_caducidad",
                "ubicacion_id",
                "ubicacion",
                "cantidad",
                "costo_unitario",
                "costo_total",
                "existencia_anterior",
                "existencia_nueva",
                "referencia",
                "observaciones",
                "fecha_registro"
            ),
            "erp_inventario_existencias" => array(
                "id_existencia_inventario",
                "id_producto",
                "id_sku_erp",
                "id_almacen",
                "id_almacen_clave",
                "codigo_existencia",
                "lote",
                "lote_clave",
                "fecha_caducidad",
                "fecha_caducidad_clave",
                "ubicacion_id",
                "ubicacion_clave",
                "ubicacion",
                "cantidad",
                "cantidad_apartada",
                "cantidad_disponible",
                "costo_promedio",
                "estatus_existencia",
                "ultimo_movimiento_id",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_inventario_unidades" => array(
                "id_inventario_unidad",
                "codigo_unico",
                "tipo_identidad",
                "serie_fabricante",
                "codigo_etiqueta_interna",
                "id_producto",
                "id_sku_erp",
                "id_recepcion_almacen",
                "id_recepcion_lote",
                "id_existencia_inventario",
                "id_almacen",
                "ubicacion_id",
                "lote",
                "fecha_caducidad",
                "cantidad_base_original",
                "cantidad_base_disponible",
                "unidad_base",
                "estatus",
                "estado_etiqueta",
                "estado_fisico",
                "fecha_impresion",
                "impreso_por",
                "fecha_etiquetado",
                "etiquetado_por",
                "origen_tipo",
                "origen_id",
                "origen_detalle_id",
                "id_preparacion_consumo",
                "id_movimiento_consumo",
                "fecha_consumo",
                "id_venta",
                "id_venta_detalle",
                "fecha_venta",
                "observaciones",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_almacen_ubicaciones" => array(
                "id_ubicacion",
                "id_almacen",
                "id_almacen_clave",
                "codigo_ubicacion",
                "nombre",
                "zona",
                "pasillo",
                "rack",
                "nivel",
                "contenedor",
                "descripcion",
                "estatus",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_almacen_preparaciones" => array(
                "id_preparacion_almacen",
                "folio",
                "id_almacen",
                "id_sku_base",
                "id_sku_presentacion",
                "id_sku_presentacion_regla",
                "id_sku_transformacion",
                "id_existencia_origen",
                "id_unidad_origen",
                "estatus",
                "fecha_preparacion",
                "unidades_preparadas",
                "cantidad_base_consumida",
                "cantidad_origen_consumida",
                "merma_porcentaje",
                "observaciones",
                "creado_por",
                "confirmado_por",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_almacen_preparacion_consumos" => array(
                "id_preparacion_consumo",
                "id_preparacion_almacen",
                "id_existencia_inventario",
                "id_inventario_unidad",
                "cantidad_unidad_antes",
                "cantidad_unidad_despues",
                "estado_unidad_despues",
                "id_sku_base",
                "id_almacen",
                "ubicacion_id",
                "lote",
                "fecha_caducidad",
                "cantidad_consumida",
                "costo_unitario",
                "costo_total",
                "id_movimiento_salida",
                "fecha_registro"
            ),
            "erp_almacen_preparacion_resultados" => array(
                "id_preparacion_resultado",
                "id_preparacion_almacen",
                "id_preparacion_consumo",
                "id_existencia_inventario",
                "id_sku_presentacion",
                "id_almacen",
                "ubicacion_id",
                "lote",
                "fecha_caducidad",
                "unidades_preparadas",
                "factor_salida_base",
                "cantidad_base_equivalente",
                "costo_unitario",
                "costo_total",
                "genera_etiquetas",
                "etiquetas_generadas",
                "id_movimiento_entrada",
                "fecha_registro"
            ),
            "erp_catalogo_sku_transformaciones" => array(
                "id_sku_transformacion",
                "id_sku_origen",
                "id_sku_resultado",
                "cantidad_origen",
                "unidades_resultado",
                "tipo_transformacion",
                "modo_disponibilidad",
                "merma_porcentaje",
                "requiere_empaque",
                "capacidad_diaria",
                "estatus",
                "observaciones",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_productos_control_inventario" => array(
                "id_producto_control_inventario",
                "id_producto",
                "requiere_lote",
                "requiere_caducidad",
                "requiere_codigo_unico",
                "generar_etiqueta_individual",
                "prefijo_codigo_unico",
                "dias_alerta_caducidad",
                "dias_minimos_recepcion",
                "estrategia_salida",
                "permitir_recepcion_sin_lote",
                "permitir_recepcion_caducada",
                "observaciones",
                "fecha_registro",
                "fecha_actualizacion"
            )
        );
    }

    public function indicesAlmacenInventario() {
        return array(
            "erp_almacenes" => array(
                "uk_erp_almacenes_codigo",
                "idx_erp_almacenes_estatus_tipo",
                "idx_erp_almacenes_orden"
            ),
            "erp_almacen_recepciones" => array(
                "idx_recepcion_orden_compra",
                "idx_recepcion_estatus",
                "idx_recepcion_proveedor",
                "idx_recepcion_almacen"
            ),
            "erp_almacen_recepciones_detalle" => array(
                "idx_recepcion_detalle_recepcion",
                "idx_recepcion_detalle_orden",
                "idx_recepcion_detalle_oc_detalle",
                "idx_recepcion_detalle_producto",
                "idx_recepcion_detalle_sku_erp",
                "idx_recepcion_detalle_sku_proveedor",
                "idx_recepcion_detalle_estatus"
            ),
            "erp_almacen_recepciones_lotes" => array(
                "idx_recepcion_lote_recepcion",
                "idx_recepcion_lote_detalle",
                "idx_recepcion_lote_almacen",
                "idx_recepcion_lote_producto",
                "idx_recepcion_lote_sku_erp",
                "idx_recepcion_lote_caducidad",
                "idx_recepcion_lote_codigo"
            ),
            "erp_almacen_recepciones_incidencias" => array(
                "idx_recepcion_incidencia_recepcion",
                "idx_recepcion_incidencia_detalle",
                "idx_recepcion_incidencia_producto",
                "idx_recepcion_incidencia_estatus"
            ),
            "erp_inventario_movimientos" => array(
                "idx_inventario_mov_producto",
                "idx_inventario_mov_sku_erp",
                "idx_inventario_mov_almacen",
                "idx_inventario_mov_tipo",
                "idx_inventario_mov_origen",
                "idx_inventario_mov_fecha",
                "idx_inventario_mov_codigo_existencia",
                "idx_inventario_mov_existencia",
                "idx_inventario_mov_recepcion_lote"
            ),
            "erp_inventario_existencias" => array(
                "idx_existencia_producto_lote_ubicacion",
                "idx_existencia_producto",
                "idx_inventario_existencia_sku_erp",
                "idx_existencia_almacen",
                "idx_existencia_almacen_clave",
                "idx_existencia_caducidad",
                "idx_existencia_codigo"
            ),
            "erp_inventario_unidades" => array(
                "idx_inventario_unidad_codigo",
                "idx_inventario_unidad_producto",
                "idx_inventario_unidad_estatus",
                "idx_inventario_unidad_venta",
                "idx_inventario_unidad_lote",
                "idx_inventario_unidad_ubicacion",
                "idx_inventario_unidad_sku_erp",
                "idx_inventario_unidad_serie_fabricante",
                "idx_inventario_unidad_etiqueta_interna",
                "idx_inventario_unidad_origen",
                "idx_inventario_unidad_estado_etiqueta",
                "idx_inventario_unidad_estado_fisico",
                "idx_inventario_unidad_preparacion_consumo",
                "uk_inventario_unidad_serie_fabricante",
                "uk_inventario_unidad_etiqueta_interna"
            ),
            "erp_almacen_ubicaciones" => array(
                "idx_ubicacion_almacen_codigo",
                "idx_ubicacion_almacen",
                "idx_ubicacion_estatus"
            ),
            "erp_almacen_preparaciones" => array(
                "uk_almacen_preparacion_folio",
                "idx_almacen_preparacion_almacen",
                "idx_almacen_preparacion_sku_base",
                "idx_almacen_preparacion_sku_presentacion",
                "idx_almacen_preparacion_transformacion",
                "idx_almacen_preparacion_existencia_origen",
                "idx_almacen_preparacion_unidad_origen",
                "idx_almacen_preparacion_estatus",
                "idx_almacen_preparacion_fecha"
            ),
            "erp_almacen_preparacion_consumos" => array(
                "idx_prep_consumo_preparacion",
                "idx_prep_consumo_existencia",
                "idx_prep_consumo_unidad",
                "idx_prep_consumo_sku_base",
                "idx_prep_consumo_almacen",
                "idx_prep_consumo_lote",
                "idx_prep_consumo_movimiento"
            ),
            "erp_almacen_preparacion_resultados" => array(
                "idx_prep_resultado_preparacion",
                "idx_prep_resultado_consumo",
                "idx_prep_resultado_existencia",
                "idx_prep_resultado_sku_presentacion",
                "idx_prep_resultado_almacen",
                "idx_prep_resultado_lote",
                "idx_prep_resultado_movimiento"
            ),
            "erp_catalogo_sku_transformaciones" => array(
                "idx_sku_transformacion_origen",
                "idx_sku_transformacion_resultado",
                "idx_sku_transformacion_estatus",
                "idx_sku_transformacion_tipo"
            ),
            "erp_productos_control_inventario" => array(
                "idx_producto_control_producto"
            )
        );
    }

    public function fksAlmacenInventario() {
        return array(
            "erp_almacen_recepciones" => array(
                "fk_alm_rec_oc",
                "fk_alm_rec_almacen"
            ),
            "erp_almacen_recepciones_detalle" => array(
                "fk_alm_rec_det_rec",
                "fk_alm_rec_det_oc_det",
                "fk_alm_rec_det_sku"
            ),
            "erp_almacen_recepciones_lotes" => array(
                "fk_alm_rec_lote_rec",
                "fk_alm_rec_lote_det",
                "fk_alm_rec_lote_sku",
                "fk_alm_rec_lote_almacen"
            ),
            "erp_inventario_existencias" => array(
                "fk_inv_exist_sku",
                "fk_inv_exist_almacen"
            ),
            "erp_inventario_movimientos" => array(
                "fk_inv_mov_sku",
                "fk_inv_mov_exist",
                "fk_inv_mov_lote",
                "fk_inv_mov_almacen"
            ),
            "erp_almacen_ubicaciones" => array(
                "fk_ubicacion_almacen_clave"
            ),
            "erp_inventario_unidades" => array(
                "fk_inv_unidad_sku"
            ),
            "erp_almacen_preparaciones" => array(
                "fk_almacen_preparacion_sku_base",
                "fk_almacen_preparacion_sku_presentacion",
                "fk_almacen_preparacion_regla"
            ),
            "erp_almacen_preparacion_consumos" => array(
                "fk_prep_consumo_preparacion",
                "fk_prep_consumo_existencia",
                "fk_prep_consumo_sku_base"
            ),
            "erp_almacen_preparacion_resultados" => array(
                "fk_prep_resultado_preparacion",
                "fk_prep_resultado_consumo",
                "fk_prep_resultado_existencia",
                "fk_prep_resultado_sku_presentacion"
            )
        );
    }

    public function auditarAlmacenInventario() {
        $auditoria = array();
        $tiene_pendientes = false;
        $columnas_esperadas = $this->columnasAlmacenInventario();
        $indices_esperados = $this->indicesAlmacenInventario();
        $fks_esperadas = $this->fksAlmacenInventario();

        foreach ($this->tablasAlmacenInventario() as $tabla) {
            $existe_tabla = $this->tablaExiste($tabla);
            $columnas_actuales = array();
            $columnas_faltantes = array();
            $indices_faltantes = array();
            $fks_faltantes = array();

            if ($existe_tabla) {
                $descripcion = $this->describirTabla($tabla);
                if ($descripcion["error"] == false && is_array($descripcion["depurar"])) {
                    foreach ($descripcion["depurar"] as $columna) {
                        $columnas_actuales[$columna["columna"]] = $columna;
                    }
                }

                foreach ($columnas_esperadas[$tabla] as $columna) {
                    if (!isset($columnas_actuales[$columna])) {
                        $columnas_faltantes[] = $columna;
                    }
                }

                foreach ($indices_esperados[$tabla] as $indice) {
                    if (!$this->indiceExiste($tabla, $indice)) {
                        $indices_faltantes[] = $indice;
                    }
                }

                if (isset($fks_esperadas[$tabla])) {
                    foreach ($fks_esperadas[$tabla] as $fk) {
                        if (!$this->fkExiste($tabla, $fk)) {
                            $fks_faltantes[] = $fk;
                        }
                    }
                }
            } else {
                $columnas_faltantes = $columnas_esperadas[$tabla];
                $indices_faltantes = $indices_esperados[$tabla];
                $fks_faltantes = isset($fks_esperadas[$tabla]) ? $fks_esperadas[$tabla] : array();
            }

            if (!$existe_tabla || !empty($columnas_faltantes) || !empty($indices_faltantes) || !empty($fks_faltantes)) {
                $tiene_pendientes = true;
            }

            $auditoria[$tabla] = array(
                "existe" => $existe_tabla,
                "columnas_faltantes" => $columnas_faltantes,
                "indices_faltantes" => $indices_faltantes,
                "fks_faltantes" => $fks_faltantes,
                "columnas_actuales" => array_values($columnas_actuales)
            );
        }

        return array(
            "error" => false,
            "tipo" => $tiene_pendientes ? "warning" : "success",
            "mensaje" => $tiene_pendientes ? "Hay pendientes en el esquema de almacen e inventario" : "El esquema de almacen e inventario esta completo",
            "depurar" => array(
                "tiene_pendientes" => $tiene_pendientes,
                "auditoria" => $auditoria,
                "plan" => $this->planActualizarAlmacenInventario(false)
            )
        );
    }

    private function fkExiste($tabla, $fk) {
        if (!is_string($tabla) || !is_string($fk) || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $fk)) {
            return false;
        }

        $db = parent::conectar();
        $stmt = $db->prepare("SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = :base
              AND TABLE_NAME = :tabla
              AND CONSTRAINT_NAME = :fk
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            LIMIT 1");
        $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":fk" => $fk));

        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function planActualizarAlmacenInventario($ejecutar = false) {
        $plan = array();

        // Bandeja de recepciones para almacen. Se crea una recepcion pendiente
        // cuando una orden de compra cambia a estatus enviada.
        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_recepciones", array(
            "`id_recepcion_almacen` INT NOT NULL AUTO_INCREMENT",
            "`id_orden_compra` INT NOT NULL",
            "`id_proveedor` INT NULL",
            "`id_almacen` INT NULL",
            "`folio` VARCHAR(80) NULL",
            "`folio_orden_compra` VARCHAR(80) NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`origen` VARCHAR(40) NOT NULL DEFAULT 'orden_compra'",
            "`fecha_alerta` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_inicio_recepcion` DATETIME NULL",
            "`fecha_cierre_recepcion` DATETIME NULL",
            "`recibido_por` INT NULL",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_recepcion_almacen`)",
            "UNIQUE KEY `idx_recepcion_orden_compra` (`id_orden_compra`)",
            "KEY `idx_recepcion_estatus` (`estatus`)",
            "KEY `idx_recepcion_proveedor` (`id_proveedor`)",
            "KEY `idx_recepcion_almacen` (`id_almacen`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "id_orden_compra", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "id_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "folio", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "folio_orden_compra", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "origen", "VARCHAR(40) NOT NULL DEFAULT 'orden_compra'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "fecha_alerta", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "fecha_inicio_recepcion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "fecha_cierre_recepcion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "recibido_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones", "idx_recepcion_orden_compra", "UNIQUE KEY `idx_recepcion_orden_compra` (`id_orden_compra`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones", "idx_recepcion_estatus", "KEY `idx_recepcion_estatus` (`estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones", "idx_recepcion_proveedor", "KEY `idx_recepcion_proveedor` (`id_proveedor`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones", "idx_recepcion_almacen", "KEY `idx_recepcion_almacen` (`id_almacen`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_recepciones_detalle", array(
            "`id_recepcion_detalle` INT NOT NULL AUTO_INCREMENT",
            "`id_recepcion_almacen` INT NOT NULL",
            "`id_orden_compra` INT NOT NULL",
            "`id_orden_compra_detalle` INT NULL",
            "`id_producto` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_sku_proveedor` BIGINT NULL",
            "`id_unidad_compra` INT NULL",
            "`unidad_compra` VARCHAR(40) NULL",
            "`id_unidad_base` INT NULL",
            "`unidad_base` VARCHAR(40) NULL",
            "`factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
            "`id_producto_proveedor` INT NULL",
            "`id_producto_revision` INT NULL",
            "`sku` VARCHAR(150) NULL",
            "`nombre_producto` VARCHAR(255) NULL",
            "`unidad` VARCHAR(80) NULL",
            "`cantidad_ordenada` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_ordenada_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_recibida` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_recibida_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_pendiente` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_pendiente_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_unitario_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`estatus_sku_recepcion` VARCHAR(40) NULL",
            "`requiere_clasificacion` TINYINT(1) NOT NULL DEFAULT 0",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_recepcion_detalle`)",
            "KEY `idx_recepcion_detalle_recepcion` (`id_recepcion_almacen`)",
            "KEY `idx_recepcion_detalle_orden` (`id_orden_compra`)",
            "KEY `idx_recepcion_detalle_producto` (`id_producto`)",
            "KEY `idx_recepcion_detalle_sku_erp` (`id_sku_erp`)",
            "KEY `idx_recepcion_detalle_estatus` (`estatus`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_recepcion_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_orden_compra", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_orden_compra_detalle", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_sku_erp", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_sku_proveedor", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_unidad_compra", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "unidad_compra", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_unidad_base", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "unidad_base", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "factor_conversion", "DECIMAL(18,6) NOT NULL DEFAULT 1.000000", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_producto_proveedor", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "id_producto_revision", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "sku", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "nombre_producto", "VARCHAR(255) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "unidad", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_ordenada", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_ordenada_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_recibida", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_recibida_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_pendiente", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "cantidad_pendiente_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "costo_unitario", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "costo_unitario_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "estatus_sku_recepcion", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "requiere_clasificacion", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_detalle", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_recepcion", "KEY `idx_recepcion_detalle_recepcion` (`id_recepcion_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_orden", "KEY `idx_recepcion_detalle_orden` (`id_orden_compra`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_oc_detalle", "KEY `idx_recepcion_detalle_oc_detalle` (`id_orden_compra_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_producto", "KEY `idx_recepcion_detalle_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_sku_erp", "KEY `idx_recepcion_detalle_sku_erp` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_sku_proveedor", "KEY `idx_recepcion_detalle_sku_proveedor` (`id_sku_proveedor`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_detalle", "idx_recepcion_detalle_estatus", "KEY `idx_recepcion_detalle_estatus` (`estatus`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_recepciones_lotes", array(
            "`id_recepcion_lote` INT NOT NULL AUTO_INCREMENT",
            "`id_recepcion_almacen` INT NOT NULL",
            "`id_recepcion_detalle` INT NOT NULL",
            "`id_producto` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_almacen` INT NULL",
            "`lote` VARCHAR(150) NULL",
            "`lote_clave` VARCHAR(150) NOT NULL DEFAULT ''",
            "`fecha_caducidad` DATE NULL",
            "`fecha_caducidad_clave` DATE NOT NULL DEFAULT '1000-01-01'",
            "`ubicacion_id` INT NULL",
            "`ubicacion_clave` INT NOT NULL DEFAULT 0",
            "`ubicacion` VARCHAR(150) NULL",
            "`cantidad_compra` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_compra` VARCHAR(40) NULL",
            "`cantidad_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`unidad_base` VARCHAR(40) NULL",
            "`factor_conversion` DECIMAL(18,6) NOT NULL DEFAULT 1.000000",
            "`codigo_recepcion_lote` VARCHAR(120) NULL",
            "`estatus_lote` VARCHAR(40) NOT NULL DEFAULT 'pendiente_revision'",
            "`dias_alerta_caducidad` INT NOT NULL DEFAULT 90",
            "`dias_minimos_recepcion` INT NOT NULL DEFAULT 30",
            "`requiere_revision` TINYINT(1) NOT NULL DEFAULT 0",
            "`motivo_revision` VARCHAR(120) NULL",
            "`cantidad` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_unitario_base` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_recepcion_lote`)",
            "KEY `idx_recepcion_lote_recepcion` (`id_recepcion_almacen`)",
            "KEY `idx_recepcion_lote_detalle` (`id_recepcion_detalle`)",
            "KEY `idx_recepcion_lote_producto` (`id_producto`)",
            "KEY `idx_recepcion_lote_sku_erp` (`id_sku_erp`)",
            "KEY `idx_recepcion_lote_caducidad` (`fecha_caducidad`)",
            "KEY `idx_recepcion_lote_codigo` (`codigo_recepcion_lote`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "id_recepcion_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "id_recepcion_detalle", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "id_sku_erp", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "lote_clave", "VARCHAR(150) NOT NULL DEFAULT ''", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "fecha_caducidad_clave", "DATE NOT NULL DEFAULT '1000-01-01'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "ubicacion_clave", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "ubicacion", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "cantidad_compra", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "unidad_compra", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "cantidad_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "unidad_base", "VARCHAR(40) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "factor_conversion", "DECIMAL(18,6) NOT NULL DEFAULT 1.000000", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "codigo_recepcion_lote", "VARCHAR(120) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "estatus_lote", "VARCHAR(40) NOT NULL DEFAULT 'pendiente_revision'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "dias_alerta_caducidad", "INT NOT NULL DEFAULT 90", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "dias_minimos_recepcion", "INT NOT NULL DEFAULT 30", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "requiere_revision", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "motivo_revision", "VARCHAR(120) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "cantidad", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "costo_unitario", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "costo_unitario_base", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_lotes", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_recepcion", "KEY `idx_recepcion_lote_recepcion` (`id_recepcion_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_detalle", "KEY `idx_recepcion_lote_detalle` (`id_recepcion_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_almacen", "KEY `idx_recepcion_lote_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_producto", "KEY `idx_recepcion_lote_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_sku_erp", "KEY `idx_recepcion_lote_sku_erp` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_caducidad", "KEY `idx_recepcion_lote_caducidad` (`fecha_caducidad`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_lotes", "idx_recepcion_lote_codigo", "KEY `idx_recepcion_lote_codigo` (`codigo_recepcion_lote`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_recepciones_incidencias", array(
            "`id_recepcion_incidencia` INT NOT NULL AUTO_INCREMENT",
            "`id_recepcion_almacen` INT NOT NULL",
            "`id_recepcion_detalle` INT NULL",
            "`id_producto` INT NULL",
            "`tipo_incidencia` VARCHAR(60) NOT NULL",
            "`severidad` VARCHAR(30) NOT NULL DEFAULT 'media'",
            "`cantidad` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`accion_sugerida` VARCHAR(80) NULL",
            "`accion_tomada` VARCHAR(80) NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_resolucion` DATETIME NULL",
            "PRIMARY KEY (`id_recepcion_incidencia`)",
            "KEY `idx_recepcion_incidencia_recepcion` (`id_recepcion_almacen`)",
            "KEY `idx_recepcion_incidencia_detalle` (`id_recepcion_detalle`)",
            "KEY `idx_recepcion_incidencia_producto` (`id_producto`)",
            "KEY `idx_recepcion_incidencia_estatus` (`estatus`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "id_recepcion_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "id_recepcion_detalle", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "id_producto", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "tipo_incidencia", "VARCHAR(60) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "severidad", "VARCHAR(30) NOT NULL DEFAULT 'media'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "cantidad", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "accion_sugerida", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "accion_tomada", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'pendiente'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_recepciones_incidencias", "fecha_resolucion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_incidencias", "idx_recepcion_incidencia_recepcion", "KEY `idx_recepcion_incidencia_recepcion` (`id_recepcion_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_incidencias", "idx_recepcion_incidencia_detalle", "KEY `idx_recepcion_incidencia_detalle` (`id_recepcion_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_incidencias", "idx_recepcion_incidencia_producto", "KEY `idx_recepcion_incidencia_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_recepciones_incidencias", "idx_recepcion_incidencia_estatus", "KEY `idx_recepcion_incidencia_estatus` (`estatus`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_movimientos", array(
            "`id_movimiento_inventario` INT NOT NULL AUTO_INCREMENT",
            "`id_producto` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_almacen` INT NULL",
            "`tipo_movimiento` VARCHAR(40) NOT NULL",
            "`origen_tipo` VARCHAR(60) NOT NULL",
            "`origen_id` INT NULL",
            "`origen_detalle_id` INT NULL",
            "`id_recepcion_lote` INT NULL",
            "`id_existencia_inventario` INT NULL",
            "`codigo_existencia` VARCHAR(120) NULL",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`ubicacion_id` INT NULL",
            "`ubicacion` VARCHAR(150) NULL",
            "`cantidad` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_total` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`existencia_anterior` DECIMAL(12,4) NULL",
            "`existencia_nueva` DECIMAL(12,4) NULL",
            "`referencia` VARCHAR(150) NULL",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_movimiento_inventario`)",
            "KEY `idx_inventario_mov_producto` (`id_producto`)",
            "KEY `idx_inventario_mov_sku_erp` (`id_sku_erp`)",
            "KEY `idx_inventario_mov_almacen` (`id_almacen`)",
            "KEY `idx_inventario_mov_tipo` (`tipo_movimiento`)",
            "KEY `idx_inventario_mov_origen` (`origen_tipo`, `origen_id`)",
            "KEY `idx_inventario_mov_fecha` (`fecha_registro`)",
            "KEY `idx_inventario_mov_codigo_existencia` (`codigo_existencia`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "id_sku_erp", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "tipo_movimiento", "VARCHAR(40) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "origen_tipo", "VARCHAR(60) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "origen_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "origen_detalle_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "id_recepcion_lote", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "id_existencia_inventario", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "codigo_existencia", "VARCHAR(120) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "ubicacion", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "cantidad", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "costo_unitario", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "costo_total", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "existencia_anterior", "DECIMAL(12,4) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "existencia_nueva", "DECIMAL(12,4) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "referencia", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_movimientos", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_producto", "KEY `idx_inventario_mov_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_sku_erp", "KEY `idx_inventario_mov_sku_erp` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_almacen", "KEY `idx_inventario_mov_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_tipo", "KEY `idx_inventario_mov_tipo` (`tipo_movimiento`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_origen", "KEY `idx_inventario_mov_origen` (`origen_tipo`, `origen_id`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_fecha", "KEY `idx_inventario_mov_fecha` (`fecha_registro`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_codigo_existencia", "KEY `idx_inventario_mov_codigo_existencia` (`codigo_existencia`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_existencia", "KEY `idx_inventario_mov_existencia` (`id_existencia_inventario`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_movimientos", "idx_inventario_mov_recepcion_lote", "KEY `idx_inventario_mov_recepcion_lote` (`id_recepcion_lote`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_existencias", array(
            "`id_existencia_inventario` INT NOT NULL AUTO_INCREMENT",
            "`id_producto` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_almacen` INT NULL",
            "`id_almacen_clave` INT NOT NULL DEFAULT 0",
            "`codigo_existencia` VARCHAR(120) NULL",
            "`lote` VARCHAR(150) NULL",
            "`lote_clave` VARCHAR(150) NOT NULL DEFAULT ''",
            "`fecha_caducidad` DATE NULL",
            "`fecha_caducidad_clave` DATE NOT NULL DEFAULT '1000-01-01'",
            "`ubicacion_id` INT NULL",
            "`ubicacion_clave` INT NOT NULL DEFAULT 0",
            "`ubicacion` VARCHAR(150) NULL",
            "`cantidad` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_apartada` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_disponible` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_promedio` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`estatus_existencia` VARCHAR(40) NOT NULL DEFAULT 'disponible'",
            "`ultimo_movimiento_id` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_existencia_inventario`)",
            "UNIQUE KEY `idx_existencia_producto_lote_ubicacion` (`id_producto`, `id_sku_erp`, `id_almacen_clave`, `lote_clave`, `fecha_caducidad_clave`, `ubicacion_clave`)",
            "KEY `idx_existencia_producto` (`id_producto`)",
            "KEY `idx_inventario_existencia_sku_erp` (`id_sku_erp`)",
            "KEY `idx_existencia_almacen` (`id_almacen`)",
            "KEY `idx_existencia_caducidad` (`fecha_caducidad`)",
            "KEY `idx_existencia_codigo` (`codigo_existencia`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "id_sku_erp", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "id_almacen_clave", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "codigo_existencia", "VARCHAR(120) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "lote_clave", "VARCHAR(150) NOT NULL DEFAULT ''", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "fecha_caducidad_clave", "DATE NOT NULL DEFAULT '1000-01-01'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "ubicacion_clave", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "ubicacion", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "cantidad", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "cantidad_apartada", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "cantidad_disponible", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "costo_promedio", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "estatus_existencia", "VARCHAR(40) NOT NULL DEFAULT 'disponible'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "ultimo_movimiento_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_existencias", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_producto_lote_ubicacion", "UNIQUE KEY `idx_existencia_producto_lote_ubicacion` (`id_producto`, `id_sku_erp`, `id_almacen_clave`, `lote_clave`, `fecha_caducidad_clave`, `ubicacion_clave`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_producto", "KEY `idx_existencia_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_inventario_existencia_sku_erp", "KEY `idx_inventario_existencia_sku_erp` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_almacen", "KEY `idx_existencia_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_almacen_clave", "KEY `idx_existencia_almacen_clave` (`id_almacen_clave`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_caducidad", "KEY `idx_existencia_caducidad` (`fecha_caducidad`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_existencias", "idx_existencia_codigo", "KEY `idx_existencia_codigo` (`codigo_existencia`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_inventario_unidades", array(
            "`id_inventario_unidad` INT NOT NULL AUTO_INCREMENT",
            "`codigo_unico` VARCHAR(120) NOT NULL",
            "`tipo_identidad` VARCHAR(30) NOT NULL DEFAULT 'etiqueta_interna'",
            "`serie_fabricante` VARCHAR(120) NULL",
            "`codigo_etiqueta_interna` VARCHAR(120) NULL",
            "`id_producto` INT NOT NULL",
            "`id_sku_erp` BIGINT NULL",
            "`id_recepcion_almacen` INT NULL",
            "`id_recepcion_lote` INT NULL",
            "`id_existencia_inventario` INT NULL",
            "`id_almacen` INT NULL",
            "`ubicacion_id` INT NULL",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`cantidad_base_original` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
            "`cantidad_base_disponible` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
            "`unidad_base` VARCHAR(40) NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'disponible'",
            "`estado_etiqueta` VARCHAR(30) NOT NULL DEFAULT 'pendiente_impresion'",
            "`estado_fisico` VARCHAR(30) NOT NULL DEFAULT 'cerrada'",
            "`fecha_impresion` DATETIME NULL",
            "`impreso_por` INT NULL",
            "`fecha_etiquetado` DATETIME NULL",
            "`etiquetado_por` INT NULL",
            "`origen_tipo` VARCHAR(50) NULL",
            "`origen_id` INT NULL",
            "`origen_detalle_id` INT NULL",
            "`id_preparacion_consumo` INT NULL",
            "`id_movimiento_consumo` INT NULL",
            "`fecha_consumo` DATETIME NULL",
            "`id_venta` INT NULL",
            "`id_venta_detalle` INT NULL",
            "`fecha_venta` DATETIME NULL",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_inventario_unidad`)",
            "UNIQUE KEY `idx_inventario_unidad_codigo` (`codigo_unico`)",
            "KEY `idx_inventario_unidad_producto` (`id_producto`)",
            "KEY `idx_inventario_unidad_estatus` (`estatus`)",
            "KEY `idx_inventario_unidad_venta` (`id_venta`, `id_venta_detalle`)",
            "KEY `idx_inventario_unidad_lote` (`id_recepcion_lote`)",
            "KEY `idx_inventario_unidad_ubicacion` (`ubicacion_id`)",
            "KEY `idx_inventario_unidad_sku_erp` (`id_sku_erp`)",
            "KEY `idx_inventario_unidad_serie_fabricante` (`serie_fabricante`)",
            "KEY `idx_inventario_unidad_etiqueta_interna` (`codigo_etiqueta_interna`)",
            "KEY `idx_inventario_unidad_origen` (`origen_tipo`, `origen_id`, `origen_detalle_id`)",
            "KEY `idx_inventario_unidad_estado_etiqueta` (`estado_etiqueta`)",
            "KEY `idx_inventario_unidad_estado_fisico` (`estado_fisico`)",
            "KEY `idx_inventario_unidad_preparacion_consumo` (`id_preparacion_consumo`)",
            "UNIQUE KEY `uk_inventario_unidad_serie_fabricante` (`serie_fabricante`)",
            "UNIQUE KEY `uk_inventario_unidad_etiqueta_interna` (`codigo_etiqueta_interna`)",
            "CONSTRAINT `fk_inv_unidad_sku` FOREIGN KEY (`id_sku_erp`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "codigo_unico", "VARCHAR(120) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "tipo_identidad", "VARCHAR(30) NOT NULL DEFAULT 'etiqueta_interna' AFTER `codigo_unico`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "serie_fabricante", "VARCHAR(120) NULL AFTER `tipo_identidad`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "codigo_etiqueta_interna", "VARCHAR(120) NULL AFTER `serie_fabricante`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_sku_erp", "BIGINT NULL AFTER `id_producto`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_recepcion_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_recepcion_lote", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_existencia_inventario", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "cantidad_base_original", "DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER `fecha_caducidad`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "cantidad_base_disponible", "DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER `cantidad_base_original`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "unidad_base", "VARCHAR(40) NULL AFTER `cantidad_base_disponible`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'disponible'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "estado_etiqueta", "VARCHAR(30) NOT NULL DEFAULT 'pendiente_impresion' AFTER `estatus`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "estado_fisico", "VARCHAR(30) NOT NULL DEFAULT 'cerrada' AFTER `estado_etiqueta`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_impresion", "DATETIME NULL AFTER `estado_fisico`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "impreso_por", "INT NULL AFTER `fecha_impresion`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_etiquetado", "DATETIME NULL AFTER `impreso_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "etiquetado_por", "INT NULL AFTER `fecha_etiquetado`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "origen_tipo", "VARCHAR(50) NULL AFTER `etiquetado_por`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "origen_id", "INT NULL AFTER `origen_tipo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "origen_detalle_id", "INT NULL AFTER `origen_id`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_preparacion_consumo", "INT NULL AFTER `origen_detalle_id`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_movimiento_consumo", "INT NULL AFTER `id_preparacion_consumo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_consumo", "DATETIME NULL AFTER `id_movimiento_consumo`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_venta", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "id_venta_detalle", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_venta", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_inventario_unidades", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_codigo", "UNIQUE KEY `idx_inventario_unidad_codigo` (`codigo_unico`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_producto", "KEY `idx_inventario_unidad_producto` (`id_producto`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_estatus", "KEY `idx_inventario_unidad_estatus` (`estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_venta", "KEY `idx_inventario_unidad_venta` (`id_venta`, `id_venta_detalle`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_lote", "KEY `idx_inventario_unidad_lote` (`id_recepcion_lote`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_ubicacion", "KEY `idx_inventario_unidad_ubicacion` (`ubicacion_id`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_sku_erp", "KEY `idx_inventario_unidad_sku_erp` (`id_sku_erp`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_serie_fabricante", "KEY `idx_inventario_unidad_serie_fabricante` (`serie_fabricante`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_etiqueta_interna", "KEY `idx_inventario_unidad_etiqueta_interna` (`codigo_etiqueta_interna`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_origen", "KEY `idx_inventario_unidad_origen` (`origen_tipo`, `origen_id`, `origen_detalle_id`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_estado_etiqueta", "KEY `idx_inventario_unidad_estado_etiqueta` (`estado_etiqueta`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_estado_fisico", "KEY `idx_inventario_unidad_estado_fisico` (`estado_fisico`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "idx_inventario_unidad_preparacion_consumo", "KEY `idx_inventario_unidad_preparacion_consumo` (`id_preparacion_consumo`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "uk_inventario_unidad_serie_fabricante", "UNIQUE KEY `uk_inventario_unidad_serie_fabricante` (`serie_fabricante`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_inventario_unidades", "uk_inventario_unidad_etiqueta_interna", "UNIQUE KEY `uk_inventario_unidad_etiqueta_interna` (`codigo_etiqueta_interna`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_ubicaciones", array(
            "`id_ubicacion` INT NOT NULL AUTO_INCREMENT",
            "`id_almacen` INT NULL",
            "`id_almacen_clave` INT NOT NULL DEFAULT 0",
            "`codigo_ubicacion` VARCHAR(80) NOT NULL",
            "`nombre` VARCHAR(150) NULL",
            "`zona` VARCHAR(80) NULL",
            "`pasillo` VARCHAR(80) NULL",
            "`rack` VARCHAR(80) NULL",
            "`nivel` VARCHAR(80) NULL",
            "`contenedor` VARCHAR(80) NULL",
            "`descripcion` TEXT NULL",
            "`estatus` VARCHAR(40) NOT NULL DEFAULT 'activa'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_ubicacion`)",
            "UNIQUE KEY `idx_ubicacion_almacen_codigo` (`id_almacen_clave`, `codigo_ubicacion`)",
            "KEY `idx_ubicacion_almacen` (`id_almacen`)",
            "KEY `idx_ubicacion_estatus` (`estatus`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "id_almacen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "id_almacen_clave", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "codigo_ubicacion", "VARCHAR(80) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "nombre", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "zona", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "pasillo", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "rack", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "nivel", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "contenedor", "VARCHAR(80) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "descripcion", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "estatus", "VARCHAR(40) NOT NULL DEFAULT 'activa'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_ubicaciones", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_ubicaciones", "idx_ubicacion_almacen_codigo", "UNIQUE KEY `idx_ubicacion_almacen_codigo` (`id_almacen_clave`, `codigo_ubicacion`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_ubicaciones", "idx_ubicacion_almacen", "KEY `idx_ubicacion_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_ubicaciones", "idx_ubicacion_estatus", "KEY `idx_ubicacion_estatus` (`estatus`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_catalogo_sku_transformaciones", array(
            "`id_sku_transformacion` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_sku_origen` BIGINT NOT NULL",
            "`id_sku_resultado` BIGINT NOT NULL",
            "`cantidad_origen` DECIMAL(18,6) NOT NULL",
            "`unidades_resultado` INT NOT NULL",
            "`tipo_transformacion` VARCHAR(40) NOT NULL DEFAULT 'reempaque'",
            "`modo_disponibilidad` VARCHAR(30) NOT NULL DEFAULT 'preparada'",
            "`merma_porcentaje` DECIMAL(9,4) NOT NULL DEFAULT 0",
            "`requiere_empaque` TINYINT(1) NOT NULL DEFAULT 1",
            "`capacidad_diaria` DECIMAL(18,6) NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_sku_transformacion`)",
            "KEY `idx_sku_transformacion_origen` (`id_sku_origen`)",
            "KEY `idx_sku_transformacion_resultado` (`id_sku_resultado`)",
            "KEY `idx_sku_transformacion_estatus` (`estatus`)",
            "KEY `idx_sku_transformacion_tipo` (`tipo_transformacion`)",
            "CONSTRAINT `fk_sku_transformacion_origen` FOREIGN KEY (`id_sku_origen`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_sku_transformacion_resultado` FOREIGN KEY (`id_sku_resultado`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "id_sku_origen", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "id_sku_resultado", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "cantidad_origen", "DECIMAL(18,6) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "unidades_resultado", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "tipo_transformacion", "VARCHAR(40) NOT NULL DEFAULT 'reempaque'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "modo_disponibilidad", "VARCHAR(30) NOT NULL DEFAULT 'preparada'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "merma_porcentaje", "DECIMAL(9,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "requiere_empaque", "TINYINT(1) NOT NULL DEFAULT 1", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "capacidad_diaria", "DECIMAL(18,6) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "estatus", "VARCHAR(30) NOT NULL DEFAULT 'activa'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_catalogo_sku_transformaciones", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_catalogo_sku_transformaciones", "idx_sku_transformacion_origen", "KEY `idx_sku_transformacion_origen` (`id_sku_origen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_catalogo_sku_transformaciones", "idx_sku_transformacion_resultado", "KEY `idx_sku_transformacion_resultado` (`id_sku_resultado`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_catalogo_sku_transformaciones", "idx_sku_transformacion_estatus", "KEY `idx_sku_transformacion_estatus` (`estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_catalogo_sku_transformaciones", "idx_sku_transformacion_tipo", "KEY `idx_sku_transformacion_tipo` (`tipo_transformacion`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_preparaciones", array(
            "`id_preparacion_almacen` INT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(60) NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`id_sku_base` BIGINT NOT NULL",
            "`id_sku_presentacion` BIGINT NOT NULL",
            "`id_sku_presentacion_regla` BIGINT NULL",
            "`id_sku_transformacion` BIGINT NULL",
            "`id_existencia_origen` INT NULL",
            "`id_unidad_origen` INT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`fecha_preparacion` DATETIME NULL",
            "`unidades_preparadas` INT NOT NULL DEFAULT 0",
            "`cantidad_base_consumida` DECIMAL(18,6) NOT NULL DEFAULT 0",
            "`cantidad_origen_consumida` DECIMAL(18,6) NULL",
            "`merma_porcentaje` DECIMAL(8,4) NOT NULL DEFAULT 0",
            "`observaciones` TEXT NULL",
            "`creado_por` INT NULL",
            "`confirmado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_preparacion_almacen`)",
            "UNIQUE KEY `uk_almacen_preparacion_folio` (`folio`)",
            "KEY `idx_almacen_preparacion_almacen` (`id_almacen`)",
            "KEY `idx_almacen_preparacion_sku_base` (`id_sku_base`)",
            "KEY `idx_almacen_preparacion_sku_presentacion` (`id_sku_presentacion`)",
            "KEY `idx_almacen_preparacion_transformacion` (`id_sku_transformacion`)",
            "KEY `idx_almacen_preparacion_existencia_origen` (`id_existencia_origen`)",
            "KEY `idx_almacen_preparacion_unidad_origen` (`id_unidad_origen`)",
            "KEY `idx_almacen_preparacion_estatus` (`estatus`)",
            "KEY `idx_almacen_preparacion_fecha` (`fecha_preparacion`)",
            "CONSTRAINT `fk_almacen_preparacion_sku_base` FOREIGN KEY (`id_sku_base`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_almacen_preparacion_sku_presentacion` FOREIGN KEY (`id_sku_presentacion`) REFERENCES `erp_catalogo_skus` (`id_sku`)",
            "CONSTRAINT `fk_almacen_preparacion_regla` FOREIGN KEY (`id_sku_presentacion_regla`) REFERENCES `erp_catalogo_sku_presentaciones` (`id_sku_presentacion_regla`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "folio", "VARCHAR(60) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_sku_base", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_sku_presentacion", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_sku_presentacion_regla", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_sku_transformacion", "BIGINT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_existencia_origen", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "id_unidad_origen", "INT NULL AFTER `id_existencia_origen`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "estatus", "VARCHAR(30) NOT NULL DEFAULT 'borrador'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "fecha_preparacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "unidades_preparadas", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "cantidad_base_consumida", "DECIMAL(18,6) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "cantidad_origen_consumida", "DECIMAL(18,6) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "merma_porcentaje", "DECIMAL(8,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "creado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "confirmado_por", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparaciones", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "uk_almacen_preparacion_folio", "UNIQUE KEY `uk_almacen_preparacion_folio` (`folio`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_almacen", "KEY `idx_almacen_preparacion_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_sku_base", "KEY `idx_almacen_preparacion_sku_base` (`id_sku_base`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_sku_presentacion", "KEY `idx_almacen_preparacion_sku_presentacion` (`id_sku_presentacion`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_transformacion", "KEY `idx_almacen_preparacion_transformacion` (`id_sku_transformacion`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_existencia_origen", "KEY `idx_almacen_preparacion_existencia_origen` (`id_existencia_origen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_unidad_origen", "KEY `idx_almacen_preparacion_unidad_origen` (`id_unidad_origen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_estatus", "KEY `idx_almacen_preparacion_estatus` (`estatus`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparaciones", "idx_almacen_preparacion_fecha", "KEY `idx_almacen_preparacion_fecha` (`fecha_preparacion`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_preparacion_consumos", array(
            "`id_preparacion_consumo` INT NOT NULL AUTO_INCREMENT",
            "`id_preparacion_almacen` INT NOT NULL",
            "`id_existencia_inventario` INT NOT NULL",
            "`id_inventario_unidad` INT NULL",
            "`cantidad_unidad_antes` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
            "`cantidad_unidad_despues` DECIMAL(18,6) NOT NULL DEFAULT 0.000000",
            "`estado_unidad_despues` VARCHAR(30) NULL",
            "`id_sku_base` BIGINT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`ubicacion_id` INT NULL",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`cantidad_consumida` DECIMAL(18,6) NOT NULL",
            "`costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_total` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`id_movimiento_salida` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_preparacion_consumo`)",
            "KEY `idx_prep_consumo_preparacion` (`id_preparacion_almacen`)",
            "KEY `idx_prep_consumo_existencia` (`id_existencia_inventario`)",
            "KEY `idx_prep_consumo_unidad` (`id_inventario_unidad`)",
            "KEY `idx_prep_consumo_sku_base` (`id_sku_base`)",
            "KEY `idx_prep_consumo_almacen` (`id_almacen`)",
            "KEY `idx_prep_consumo_lote` (`lote`, `fecha_caducidad`)",
            "KEY `idx_prep_consumo_movimiento` (`id_movimiento_salida`)",
            "CONSTRAINT `fk_prep_consumo_preparacion` FOREIGN KEY (`id_preparacion_almacen`) REFERENCES `erp_almacen_preparaciones` (`id_preparacion_almacen`)",
            "CONSTRAINT `fk_prep_consumo_existencia` FOREIGN KEY (`id_existencia_inventario`) REFERENCES `erp_inventario_existencias` (`id_existencia_inventario`)",
            "CONSTRAINT `fk_prep_consumo_sku_base` FOREIGN KEY (`id_sku_base`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_preparacion_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_existencia_inventario", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_inventario_unidad", "INT NULL AFTER `id_existencia_inventario`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "cantidad_unidad_antes", "DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER `id_inventario_unidad`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "cantidad_unidad_despues", "DECIMAL(18,6) NOT NULL DEFAULT 0.000000 AFTER `cantidad_unidad_antes`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "estado_unidad_despues", "VARCHAR(30) NULL AFTER `cantidad_unidad_despues`", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_sku_base", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "cantidad_consumida", "DECIMAL(18,6) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "costo_unitario", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "costo_total", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "id_movimiento_salida", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_consumos", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_preparacion", "KEY `idx_prep_consumo_preparacion` (`id_preparacion_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_existencia", "KEY `idx_prep_consumo_existencia` (`id_existencia_inventario`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_unidad", "KEY `idx_prep_consumo_unidad` (`id_inventario_unidad`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_sku_base", "KEY `idx_prep_consumo_sku_base` (`id_sku_base`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_almacen", "KEY `idx_prep_consumo_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_lote", "KEY `idx_prep_consumo_lote` (`lote`, `fecha_caducidad`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_consumos", "idx_prep_consumo_movimiento", "KEY `idx_prep_consumo_movimiento` (`id_movimiento_salida`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_almacen_preparacion_resultados", array(
            "`id_preparacion_resultado` INT NOT NULL AUTO_INCREMENT",
            "`id_preparacion_almacen` INT NOT NULL",
            "`id_preparacion_consumo` INT NULL",
            "`id_existencia_inventario` INT NOT NULL",
            "`id_sku_presentacion` BIGINT NOT NULL",
            "`id_almacen` INT NOT NULL",
            "`ubicacion_id` INT NULL",
            "`lote` VARCHAR(150) NULL",
            "`fecha_caducidad` DATE NULL",
            "`unidades_preparadas` INT NOT NULL",
            "`factor_salida_base` DECIMAL(18,6) NOT NULL",
            "`cantidad_base_equivalente` DECIMAL(18,6) NOT NULL",
            "`costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_total` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`genera_etiquetas` TINYINT(1) NOT NULL DEFAULT 0",
            "`etiquetas_generadas` INT NOT NULL DEFAULT 0",
            "`id_movimiento_entrada` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_preparacion_resultado`)",
            "KEY `idx_prep_resultado_preparacion` (`id_preparacion_almacen`)",
            "KEY `idx_prep_resultado_consumo` (`id_preparacion_consumo`)",
            "KEY `idx_prep_resultado_existencia` (`id_existencia_inventario`)",
            "KEY `idx_prep_resultado_sku_presentacion` (`id_sku_presentacion`)",
            "KEY `idx_prep_resultado_almacen` (`id_almacen`)",
            "KEY `idx_prep_resultado_lote` (`lote`, `fecha_caducidad`)",
            "KEY `idx_prep_resultado_movimiento` (`id_movimiento_entrada`)",
            "CONSTRAINT `fk_prep_resultado_preparacion` FOREIGN KEY (`id_preparacion_almacen`) REFERENCES `erp_almacen_preparaciones` (`id_preparacion_almacen`)",
            "CONSTRAINT `fk_prep_resultado_consumo` FOREIGN KEY (`id_preparacion_consumo`) REFERENCES `erp_almacen_preparacion_consumos` (`id_preparacion_consumo`)",
            "CONSTRAINT `fk_prep_resultado_existencia` FOREIGN KEY (`id_existencia_inventario`) REFERENCES `erp_inventario_existencias` (`id_existencia_inventario`)",
            "CONSTRAINT `fk_prep_resultado_sku_presentacion` FOREIGN KEY (`id_sku_presentacion`) REFERENCES `erp_catalogo_skus` (`id_sku`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_preparacion_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_preparacion_consumo", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_existencia_inventario", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_sku_presentacion", "BIGINT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_almacen", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "ubicacion_id", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "lote", "VARCHAR(150) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "fecha_caducidad", "DATE NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "unidades_preparadas", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "factor_salida_base", "DECIMAL(18,6) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "cantidad_base_equivalente", "DECIMAL(18,6) NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "costo_unitario", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "costo_total", "DECIMAL(12,4) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "genera_etiquetas", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "etiquetas_generadas", "INT NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "id_movimiento_entrada", "INT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_almacen_preparacion_resultados", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_preparacion", "KEY `idx_prep_resultado_preparacion` (`id_preparacion_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_consumo", "KEY `idx_prep_resultado_consumo` (`id_preparacion_consumo`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_existencia", "KEY `idx_prep_resultado_existencia` (`id_existencia_inventario`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_sku_presentacion", "KEY `idx_prep_resultado_sku_presentacion` (`id_sku_presentacion`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_almacen", "KEY `idx_prep_resultado_almacen` (`id_almacen`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_lote", "KEY `idx_prep_resultado_lote` (`lote`, `fecha_caducidad`)", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_almacen_preparacion_resultados", "idx_prep_resultado_movimiento", "KEY `idx_prep_resultado_movimiento` (`id_movimiento_entrada`)", $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_productos_control_inventario", array(
            "`id_producto_control_inventario` INT NOT NULL AUTO_INCREMENT",
            "`id_producto` INT NOT NULL",
            "`requiere_lote` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_caducidad` TINYINT(1) NOT NULL DEFAULT 0",
            "`requiere_codigo_unico` TINYINT(1) NOT NULL DEFAULT 0",
            "`generar_etiqueta_individual` TINYINT(1) NOT NULL DEFAULT 0",
            "`prefijo_codigo_unico` VARCHAR(30) NULL",
            "`dias_alerta_caducidad` INT NOT NULL DEFAULT 90",
            "`dias_minimos_recepcion` INT NOT NULL DEFAULT 30",
            "`estrategia_salida` VARCHAR(20) NOT NULL DEFAULT 'FEFO'",
            "`permitir_recepcion_sin_lote` TINYINT(1) NOT NULL DEFAULT 1",
            "`permitir_recepcion_caducada` TINYINT(1) NOT NULL DEFAULT 0",
            "`observaciones` TEXT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_producto_control_inventario`)",
            "UNIQUE KEY `idx_producto_control_producto` (`id_producto`)"
        ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "id_producto", "INT NOT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "requiere_lote", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "requiere_caducidad", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "requiere_codigo_unico", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "generar_etiqueta_individual", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "prefijo_codigo_unico", "VARCHAR(30) NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "dias_alerta_caducidad", "INT NOT NULL DEFAULT 90", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "dias_minimos_recepcion", "INT NOT NULL DEFAULT 30", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "estrategia_salida", "VARCHAR(20) NOT NULL DEFAULT 'FEFO'", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "permitir_recepcion_sin_lote", "TINYINT(1) NOT NULL DEFAULT 1", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "permitir_recepcion_caducada", "TINYINT(1) NOT NULL DEFAULT 0", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "observaciones", "TEXT NULL", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "fecha_registro", "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP", $ejecutar);
        $plan[] = $this->agregarColumnaSiNoExiste("erp_productos_control_inventario", "fecha_actualizacion", "DATETIME NULL", $ejecutar);
        $plan[] = $this->agregarIndiceSiNoExiste("erp_productos_control_inventario", "idx_producto_control_producto", "UNIQUE KEY `idx_producto_control_producto` (`id_producto`)", $ejecutar);

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $ejecutar ? "Plan de almacen e inventario ejecutado" : "Plan de almacen e inventario generado en dry-run",
            "depurar" => $plan
        );
    }
}
