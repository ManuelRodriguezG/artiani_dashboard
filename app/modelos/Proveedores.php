<?php

class Proveedores extends CRUD {

    private $tabla_proveedores = "erp_proveedores";
    private $tabla_categorias = "ecom_categorias";
    private $tabla_proveedores_listas = "erp_proveedores_listas";
    private $tabla_proveedores_listas_productos = "erp_proveedores_listas_productos";
    private $tabla_proveedores_listas_productos_imagenes = "erp_proveedores_listas_productos_imagenes";
    private $tabla_proveedores_listas_productos_categorias = "erp_proveedores_listas_productos_categorias";
    private $tabla_erp_ordenes_de_compra_productos = "erp_ordenes_de_compra_productos";
    private $tabla_erp_ordenes_de_compra = "erp_ordenes_de_compra";
    private $tabla_erp_proveedores_pedidos = "erp_proveedores_pedidos";
    private $tabla_erp_proveedores_pedidos_elementos = "erp_proveedores_pedidos_elementos";
    private $tabla_erp_listas_mayoreo = "erp_listas_mayoreo";
    private $tabla_erp_listas_mayoreo_productos = "erp_listas_mayoreo_productos";
    private $tabla_erp_listas_mayoreo_tipos = "erp_listas_mayoreo_tipos";
    private $tabla_erp_proveedores_listas_productos_historial_costos = "erp_proveedores_listas_productos_historial_costos";
    private $id_tipo_lista_mayoreo;
    private $id_lista_mayoreo;
    private $id_producto;
    private $identificador;
    private $id_categoria;
    private $tabla_sys_usuarios_mayoreo = "sys_usuarios_mayoreo";
    private $tabla_sys_usuarios_mayoreo_informacion_negocio = "sys_usuarios_mayoreo_informacion_negocio";
    private $tabla_erp_usuarios_mayoreo_listas_mayoreo = "erp_usuarios_mayoreo_listas_mayoreo";
    private $id_usuario_mayoreo;
    //productos
    private $tabla_productos = "ecom_productos";
    private $tabla_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_productos_compra_venta = "ecom_productos_compra_venta";
    private $tabla_erp_unidad_venta = "erp_unidad_venta";
    /*
     * Productos - proveedores
     * 
     * id_producto_proveedor
     * id_producto
     * id_proveedor
     */
    private $id_producto_proveedor;
    private $id_proveedor;
    private $lista;
    private $estatus;
    private $marca;
    private $sku;
    private $existencias;
    private $nombre;
    private $costo;
    private $precio_sugerido;
    private $piezas_por_caja;
    private $rotacion;
    private $id_lista_proveedor;
    private $url_origen;
    private $archivo_portada;
    private $tipo_imagen;
    private $url_imagen;
    private $codigo_interno;
    private $descripcion;
    private $codigo_barras_base;
    private $id_orden_de_compra;
    private $porcentaje_impuesto;
    private $precio_sin_impuestos;
    private $utilidad_bruta;
    private $incluye_impuesto;
    private $busqueda;
    private $proveedor;
    private $cuota;
    private $total;
    private $id_elemento;
    private $id_proveedor_pedido;
    private $tipo_elemento;
    private $comentario;
    private $titulo;
    private $cantidad;
    //historial costos
    private $costo_anterior;
    private $diferencia_costo;
    private $porcentaje_cambio;
    private $precio_actual;

    public function registrar() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "proveedor",
            "cuota"
        );
        $valores_registrar = array(
            $this->getProveedor(),
            $this->getCuota()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_proveedores);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function auditoriaDryRunErp() {
        try {
            $db = $this->getConexion();
            $tablas_revisar = array(
                "erp_proveedores",
                "erp_proveedores_listas",
                "erp_proveedores_listas_productos",
                "erp_catalogo_skus",
                "erp_catalogo_sku_proveedores",
                "erp_catalogo_unidades",
                "erp_catalogo_sku_codigos",
                "erp_catalogo_sku_impuestos"
            );
            $tablas = array();

            foreach ($tablas_revisar as $tabla) {
                $tablas[$tabla] = $this->tablaExisteAuditoriaProveedores($db, $tabla);
            }

            $hallazgos = array();
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "proveedores_total",
                "Registros en maestro legado erp_proveedores.",
                "SELECT COUNT(*) FROM erp_proveedores",
                $tablas,
                array("erp_proveedores")
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "proveedores_sin_nombre",
                "Proveedores sin nombre capturado.",
                "SELECT COUNT(*) FROM erp_proveedores WHERE TRIM(COALESCE(proveedor, '')) = ''",
                $tablas,
                array("erp_proveedores"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "listas_total",
                "Listas legacy de proveedor registradas.",
                "SELECT COUNT(*) FROM erp_proveedores_listas",
                $tablas,
                array("erp_proveedores_listas")
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "listas_sin_proveedor",
                "Listas sin proveedor valido relacionado.",
                "SELECT COUNT(*) FROM erp_proveedores_listas l LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor WHERE l.id_proveedor IS NULL OR p.id_proveedor IS NULL",
                $tablas,
                array("erp_proveedores_listas", "erp_proveedores"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "renglones_lista_total",
                "Productos/renglones en listas legacy de proveedor.",
                "SELECT COUNT(*) FROM erp_proveedores_listas_productos",
                $tablas,
                array("erp_proveedores_listas_productos")
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "renglones_lista_sin_costo",
                "Renglones de lista legacy sin costo positivo.",
                "SELECT COUNT(*) FROM erp_proveedores_listas_productos WHERE COALESCE(costo, 0) <= 0",
                $tablas,
                array("erp_proveedores_listas_productos"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "renglones_lista_sin_sku_y_nombre",
                "Renglones de lista legacy sin SKU ni nombre.",
                "SELECT COUNT(*) FROM erp_proveedores_listas_productos WHERE TRIM(COALESCE(sku, '')) = '' AND TRIM(COALESCE(nombre, '')) = ''",
                $tablas,
                array("erp_proveedores_listas_productos"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "renglones_lista_sin_match_sku_erp",
                "Renglones legacy con SKU proveedor que no empatan contra SKU ERP exacto.",
                "SELECT COUNT(*) FROM erp_proveedores_listas_productos lp WHERE TRIM(COALESCE(lp.sku, '')) <> '' AND NOT EXISTS (SELECT 1 FROM erp_catalogo_skus s WHERE UPPER(TRIM(s.sku)) = UPPER(TRIM(lp.sku)) AND s.estatus <> 'fusionado')",
                $tablas,
                array("erp_proveedores_listas_productos", "erp_catalogo_skus"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_total",
                "Relaciones ERP SKU-proveedor existentes.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores",
                $tablas,
                array("erp_catalogo_sku_proveedores")
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_sin_costo",
                "Relaciones ERP SKU-proveedor sin costo ultimo positivo.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores WHERE COALESCE(costo_ultimo, 0) <= 0",
                $tablas,
                array("erp_catalogo_sku_proveedores"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_sin_unidad",
                "Relaciones ERP SKU-proveedor sin unidad de compra valida.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = sp.id_unidad_compra WHERE sp.id_unidad_compra IS NULL OR sp.id_unidad_compra <= 0 OR u.id_unidad IS NULL",
                $tablas,
                array("erp_catalogo_sku_proveedores", "erp_catalogo_unidades"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_sin_factor",
                "Relaciones ERP SKU-proveedor sin factor de conversion positivo.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores WHERE COALESCE(factor_conversion, 0) <= 0",
                $tablas,
                array("erp_catalogo_sku_proveedores"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_con_sku_invalido",
                "Relaciones ERP SKU-proveedor con SKU faltante o no activo.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp LEFT JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku WHERE s.id_sku IS NULL OR s.estatus <> 'activo'",
                $tablas,
                array("erp_catalogo_sku_proveedores", "erp_catalogo_skus"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "relaciones_sku_proveedor_con_proveedor_invalido",
                "Relaciones ERP SKU-proveedor con proveedor faltante.",
                "SELECT COUNT(*) FROM erp_catalogo_sku_proveedores sp LEFT JOIN erp_proveedores p ON p.id_proveedor = sp.id_proveedor WHERE p.id_proveedor IS NULL",
                $tablas,
                array("erp_catalogo_sku_proveedores", "erp_proveedores"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "skus_comprables_sin_codigo_principal",
                "SKUs activos con proveedor activo pero sin codigo principal activo.",
                "SELECT COUNT(*) FROM erp_catalogo_skus s WHERE s.estatus = 'activo' AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku = s.id_sku AND sp.estatus = 'activo') AND NOT EXISTS (SELECT 1 FROM erp_catalogo_sku_codigos c WHERE c.id_sku = s.id_sku AND c.es_principal = 1 AND c.estatus = 'activo')",
                $tablas,
                array("erp_catalogo_skus", "erp_catalogo_sku_proveedores", "erp_catalogo_sku_codigos"),
                "warning"
            );
            $this->agregarConteoAuditoriaProveedores(
                $db,
                $hallazgos,
                "skus_comprables_con_fiscal_incompleto",
                "SKUs activos con proveedor activo y datos fiscales incompletos para compras.",
                "SELECT COUNT(*) FROM erp_catalogo_skus s LEFT JOIN erp_catalogo_sku_impuestos i ON i.id_sku = s.id_sku WHERE s.estatus = 'activo' AND EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores sp WHERE sp.id_sku = s.id_sku AND sp.estatus = 'activo') AND (i.id_sku IS NULL OR TRIM(COALESCE(i.clave_producto_sat, '')) = '' OR TRIM(COALESCE(i.clave_unidad_sat, '')) = '' OR TRIM(COALESCE(i.objeto_impuesto, '')) = '' OR i.iva_porcentaje IS NULL OR i.ieps_porcentaje IS NULL OR i.incluye_impuestos IS NULL)",
                $tablas,
                array("erp_catalogo_skus", "erp_catalogo_sku_proveedores", "erp_catalogo_sku_impuestos"),
                "warning"
            );

            return $this->respuestaAuditoriaProveedores(false, "success", "Auditoria dry-run de Proveedores consultada.", array(
                "sin_escrituras" => true,
                "tablas" => $tablas,
                "hallazgos" => $hallazgos,
                "muestras_listas_legacy" => $this->muestrasListasLegacyProveedores($db, $tablas),
                "muestras_hallazgos" => $this->muestrasHallazgosAuditoriaProveedores($db, $tablas),
                "permisos_roles" => $this->auditoriaPermisosRolesProveedorErp($db),
                "productivo_sql" => $productivoSql = $this->auditarProductivoSqlProveedores(),
                "staging_migracion" => $stagingMigracion = $this->resumenStagingMigracionProveedor($db),
                "preflight_migracion" => $this->preflightMigracionCompletaProveedores($productivoSql, $stagingMigracion),
                "limitaciones_contrato" => array(
                    "El dry-run no crea incidencias ni modifica Catalogo.",
                    "No define reglas de negocio nuevas para moneda, vigencia, evidencias ni proveedor preferido.",
                    "Los permisos proveedores.* ya existen para el modulo nuevo; endpoints legacy se migraran gradualmente."
                )
            ));
        } catch (Exception $ex) {
            return $this->respuestaAuditoriaProveedores(true, "danger", "No fue posible consultar la auditoria dry-run de Proveedores.", array(
                "error_tecnico" => $ex->getMessage()
            ));
        }
    }

    private function resumenStagingMigracionProveedor($db) {
        try {
            if (!$this->tablaExisteAuditoriaProveedores($db, "erp_proveedores_migracion_staging")) {
                return array("disponible" => false, "lotes" => array(), "resumen" => array(), "muestras_revision" => array());
            }
            $stmt = $db->prepare("SELECT lote, COUNT(*) total, MIN(fecha_registro) primera_fecha, MAX(fecha_registro) ultima_fecha
                FROM erp_proveedores_migracion_staging
                GROUP BY lote
                ORDER BY MAX(fecha_registro) DESC
                LIMIT 10");
            $stmt->execute();
            $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT lote, tipo_registro, accion_propuesta, estado_revision, COUNT(*) total
                FROM erp_proveedores_migracion_staging
                GROUP BY lote, tipo_registro, accion_propuesta, estado_revision
                ORDER BY lote DESC, tipo_registro, accion_propuesta, estado_revision
                LIMIT 100");
            $stmt->execute();
            $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("SELECT lote, tipo_registro, id_origen, referencia, accion_propuesta, estado_revision, motivo_revision
                FROM erp_proveedores_migracion_staging
                WHERE accion_propuesta LIKE 'revisar%'
                  AND estado_revision = 'pendiente'
                ORDER BY fecha_registro DESC, id_staging DESC
                LIMIT 20");
            $stmt->execute();
            return array(
                "disponible" => true,
                "sin_escrituras" => true,
                "lotes" => $lotes,
                "resumen" => $resumen,
                "muestras_revision" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch (Exception $e) {
            return array("disponible" => false, "error" => $e->getMessage(), "lotes" => array(), "resumen" => array(), "muestras_revision" => array());
        }
    }

    private function preflightMigracionCompletaProveedores($productivoSql, $stagingMigracion) {
        $archivos = isset($productivoSql["archivos"]) ? $productivoSql["archivos"] : array();
        $preview = isset($productivoSql["preview_migracion"]) ? $productivoSql["preview_migracion"] : array();
        $resumenPreview = isset($preview["resumen"]) ? $preview["resumen"] : array();
        $resumenSql = isset($productivoSql["resumen"]) ? $productivoSql["resumen"] : array();
        $advertencias = isset($productivoSql["advertencias"]) ? $productivoSql["advertencias"] : array();

        $faltanArchivos = array();
        foreach (array("proveedores", "listas", "renglones") as $clave) {
            if (empty($archivos[$clave]["existe"])) {
                $faltanArchivos[] = $clave;
            }
        }

        $pendientesRevision = intval(isset($resumenPreview["proveedores_revisar"]) ? $resumenPreview["proveedores_revisar"] : 0)
            + intval(isset($resumenPreview["listas_revisar"]) ? $resumenPreview["listas_revisar"] : 0)
            + intval(isset($resumenPreview["renglones_revisar"]) ? $resumenPreview["renglones_revisar"] : 0);
        $yaMigrado = intval(isset($resumenPreview["listas_existentes"]) ? $resumenPreview["listas_existentes"] : 0)
            + intval(isset($resumenPreview["renglones_existentes"]) ? $resumenPreview["renglones_existentes"] : 0);
        $candidatos = intval(isset($resumenPreview["proveedores_crear"]) ? $resumenPreview["proveedores_crear"] : 0)
            + intval(isset($resumenPreview["listas_crear"]) ? $resumenPreview["listas_crear"] : 0)
            + intval(isset($resumenPreview["renglones_crear"]) ? $resumenPreview["renglones_crear"] : 0);

        $riesgos = array();
        if (!empty($faltanArchivos)) {
            $riesgos[] = "Faltan archivos productivo: " . implode(", ", $faltanArchivos) . ".";
        }
        if ($pendientesRevision > 0) {
            $riesgos[] = "Hay " . $pendientesRevision . " registros que requieren revision antes de convertir o corregir.";
        }
        if (intval(isset($resumenPreview["renglones_sin_costo"]) ? $resumenPreview["renglones_sin_costo"] : 0) > 0) {
            $riesgos[] = "Existen renglones sin costo positivo; deben conservarse como evidencia pendiente o resolverse manualmente.";
        }
        if (intval(isset($resumenPreview["renglones_sin_match_sku"]) ? $resumenPreview["renglones_sin_match_sku"] : 0) > 0) {
            $riesgos[] = "Hay renglones sin match exacto con SKU ERP; no deben crear relaciones automaticas.";
        }
        if (!$stagingMigracion || empty($stagingMigracion["disponible"])) {
            $riesgos[] = "No se puede revisar staging; antes de migrar por lotes debe existir tabla staging auditable.";
        }
        foreach ($advertencias as $advertencia) {
            $riesgos[] = $advertencia;
        }

        $pasos = array(
            array(
                "paso" => "Respaldar base de datos fuera del proyecto",
                "estado" => "requiere_autorizacion",
                "detalle" => "Debe ejecutarse antes de cualquier lote nuevo."
            ),
            array(
                "paso" => "Revisar dry-run productivo",
                "estado" => empty($faltanArchivos) ? "listo" : "bloqueado",
                "detalle" => "Valida existencia de archivos y conteos de proveedores/listas/renglones."
            ),
            array(
                "paso" => "Cargar lote staging",
                "estado" => "requiere_autorizacion",
                "detalle" => "Es escritura controlada; no toca tablas oficiales."
            ),
            array(
                "paso" => "Convertir staging a ERP oficial",
                "estado" => "requiere_autorizacion",
                "detalle" => "Solo proveedores/listas borrador/renglones evidencia; sin costos vigentes ni relaciones."
            ),
            array(
                "paso" => "Resolver pendientes post-migracion",
                "estado" => $pendientesRevision > 0 ? "pendiente_revision" : "listo",
                "detalle" => "Se atiende con matching, edicion controlada, incidencia a Catalogo o descarte con motivo."
            )
        );

        $estado = empty($faltanArchivos) ? "preparado_para_autorizacion" : "bloqueado_por_archivos";
        if ($candidatos <= 0 && $yaMigrado > 0 && $pendientesRevision === 0) {
            $estado = "sin_candidatos_nuevos";
            $riesgos[] = "El preview no muestra candidatos nuevos; revisar si el lote productivo ya fue migrado.";
        }

        return array(
            "sin_escrituras" => true,
            "estado" => $estado,
            "resumen" => array(
                "proveedores_sql" => intval(isset($resumenSql["proveedores"]) ? $resumenSql["proveedores"] : 0),
                "listas_sql" => intval(isset($resumenSql["listas"]) ? $resumenSql["listas"] : 0),
                "renglones_sql" => intval(isset($resumenSql["renglones"]) ? $resumenSql["renglones"] : 0),
                "candidatos_nuevos" => $candidatos,
                "ya_migrados_o_existentes" => $yaMigrado,
                "requieren_revision" => $pendientesRevision
            ),
            "riesgos" => $riesgos,
            "pasos" => $pasos,
            "autorizacion_requerida" => array(
                "respaldo_externo",
                "carga_staging_lote",
                "conversion_staging_erp",
                "correccion_o_descarte_en_lote"
            )
        );
    }

    private function agregarConteoAuditoriaProveedores($db, &$hallazgos, $clave, $descripcion, $sql, $tablas, $requeridas, $severidad = "info") {
        if (!$this->tablasDisponiblesAuditoriaProveedores($tablas, $requeridas)) {
            $hallazgos[$clave] = array(
                "severidad" => "warning",
                "descripcion" => $descripcion,
                "conteo" => null,
                "estado" => "omitido",
                "motivo" => "Falta una o mas tablas requeridas: " . implode(", ", $requeridas)
            );
            return;
        }

        $sentencia = $db->prepare($sql);
        $sentencia->execute();
        $hallazgos[$clave] = array(
            "severidad" => $severidad,
            "descripcion" => $descripcion,
            "conteo" => intval($sentencia->fetchColumn()),
            "estado" => "consultado"
        );
    }

    private function muestrasListasLegacyProveedores($db, $tablas) {
        $muestras = array(
            "listas_con_renglones" => array(),
            "listas_sin_proveedor" => array(),
            "renglones_sin_costo" => array(),
            "renglones_sin_match_sku" => array()
        );

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores", "erp_proveedores_listas_productos"))) {
            $stmt = $db->prepare("SELECT
                    l.id_lista_proveedor,
                    l.id_proveedor,
                    p.proveedor,
                    l.lista,
                    l.estatus,
                    COUNT(lp.id_producto) AS renglones,
                    SUM(CASE WHEN COALESCE(lp.costo, 0) > 0 THEN 1 ELSE 0 END) AS renglones_con_costo
                FROM erp_proveedores_listas l
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                LEFT JOIN erp_proveedores_listas_productos lp ON lp.id_lista_proveedor = l.id_lista_proveedor
                GROUP BY l.id_lista_proveedor, l.id_proveedor, p.proveedor, l.lista, l.estatus
                HAVING renglones > 0
                ORDER BY renglones DESC, l.id_lista_proveedor DESC
                LIMIT 10");
            $stmt->execute();
            $muestras["listas_con_renglones"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores"))) {
            $stmt = $db->prepare("SELECT l.id_lista_proveedor, l.id_proveedor, l.lista, l.estatus
                FROM erp_proveedores_listas l
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE l.id_proveedor IS NULL OR p.id_proveedor IS NULL
                ORDER BY l.id_lista_proveedor DESC
                LIMIT 10");
            $stmt->execute();
            $muestras["listas_sin_proveedor"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores", "erp_proveedores_listas_productos"))) {
            $stmt = $db->prepare("SELECT
                    lp.id_producto,
                    lp.id_lista_proveedor,
                    l.lista,
                    p.proveedor,
                    lp.sku,
                    lp.nombre,
                    lp.costo
                FROM erp_proveedores_listas_productos lp
                LEFT JOIN erp_proveedores_listas l ON l.id_lista_proveedor = lp.id_lista_proveedor
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE COALESCE(lp.costo, 0) <= 0
                ORDER BY lp.id_producto DESC
                LIMIT 10");
            $stmt->execute();
            $muestras["renglones_sin_costo"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores", "erp_proveedores_listas_productos", "erp_catalogo_skus"))) {
            $stmt = $db->prepare("SELECT
                    lp.id_producto,
                    lp.id_lista_proveedor,
                    l.lista,
                    p.proveedor,
                    lp.sku,
                    lp.nombre,
                    lp.costo
                FROM erp_proveedores_listas_productos lp
                LEFT JOIN erp_proveedores_listas l ON l.id_lista_proveedor = lp.id_lista_proveedor
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE TRIM(COALESCE(lp.sku, '')) <> ''
                  AND NOT EXISTS (
                    SELECT 1
                    FROM erp_catalogo_skus s
                    WHERE UPPER(TRIM(s.sku)) = UPPER(TRIM(lp.sku))
                      AND s.estatus <> 'fusionado'
                  )
                ORDER BY lp.id_producto DESC
                LIMIT 10");
            $stmt->execute();
            $muestras["renglones_sin_match_sku"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $muestras;
    }

    private function auditoriaPermisosRolesProveedorErp($db) {
        $tablas = array(
            "sys_roles" => $this->tablaExisteAuditoriaProveedores($db, "sys_roles"),
            "sys_permisos" => $this->tablaExisteAuditoriaProveedores($db, "sys_permisos"),
            "sys_roles_permisos" => $this->tablaExisteAuditoriaProveedores($db, "sys_roles_permisos")
        );
        if (!$this->tablasDisponiblesAuditoriaProveedores($tablas, array("sys_roles", "sys_permisos", "sys_roles_permisos"))) {
            return array(
                "disponible" => false,
                "sin_escrituras" => true,
                "motivo" => "Faltan tablas de seguridad para auditar roles/permisos.",
                "tablas" => $tablas
            );
        }

        try {
            if (!class_exists("SeguridadEsquema")) {
                require_once __DIR__ . "/SeguridadEsquema.php";
            }
            $seguridad = new SeguridadEsquema();
            $permisosBase = array();
            foreach ($seguridad->permisosBaseERP() as $permiso) {
                if (strpos($permiso["permiso"], "proveedores.") === 0) {
                    $permisosBase[$permiso["permiso"]] = $permiso;
                }
            }
            $esperadoPorRol = array();
            foreach ($seguridad->permisosPorRolBaseERP() as $rol => $permisos) {
                $esperadoPorRol[$rol] = array_values(array_filter($permisos, function ($permiso) {
                    return strpos($permiso, "proveedores.") === 0;
                }));
            }

            $stmt = $db->prepare("SELECT permiso, descripcion, estatus
                FROM sys_permisos
                WHERE permiso LIKE 'proveedores.%'
                ORDER BY permiso");
            $stmt->execute();
            $permisosBd = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $permisosBd[$fila["permiso"]] = $fila;
            }

            $stmt = $db->prepare("SELECT
                    sr.rol,
                    sr.descripcion,
                    sr.estatus,
                    GROUP_CONCAT(sp.permiso ORDER BY sp.permiso SEPARATOR ',') AS permisos
                FROM sys_roles sr
                LEFT JOIN sys_roles_permisos srp ON srp.id_rol = sr.id_rol
                LEFT JOIN sys_permisos sp ON sp.id_permiso = srp.id_permiso
                    AND sp.estatus = 1
                    AND sp.permiso LIKE 'proveedores.%'
                GROUP BY sr.id_rol, sr.rol, sr.descripcion, sr.estatus
                ORDER BY sr.rol");
            $stmt->execute();

            $roles = array();
            $faltantes = array();
            $sobrantes = array();
            $sensibles = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $rol = $fila["rol"];
                $asignados = array_values(array_filter(explode(",", (string) $fila["permisos"])));
                $esperados = isset($esperadoPorRol[$rol]) ? $esperadoPorRol[$rol] : array();
                $faltanRol = array_values(array_diff($esperados, $asignados));
                $sobranRol = array_values(array_diff($asignados, $esperados));
                if (!empty($faltanRol)) {
                    $faltantes[$rol] = $faltanRol;
                }
                if (!empty($sobranRol)) {
                    $sobrantes[$rol] = $sobranRol;
                }
                $permisosSensibles = array_values(array_intersect($asignados, array(
                    "proveedores.documentos_sensibles",
                    "proveedores.autorizar",
                    "proveedores.costos"
                )));
                if (!empty($permisosSensibles)) {
                    $sensibles[$rol] = $permisosSensibles;
                }
                $roles[] = array(
                    "rol" => $rol,
                    "descripcion" => $fila["descripcion"],
                    "estatus" => intval($fila["estatus"]),
                    "permisos_asignados" => $asignados,
                    "permisos_esperados" => $esperados,
                    "faltantes" => $faltanRol,
                    "sobrantes" => $sobranRol
                );
            }

            $permisosFaltantesBd = array_values(array_diff(array_keys($permisosBase), array_keys($permisosBd)));
            return array(
                "disponible" => true,
                "sin_escrituras" => true,
                "resumen" => array(
                    "permisos_base" => count($permisosBase),
                    "permisos_bd" => count($permisosBd),
                    "permisos_faltantes_bd" => count($permisosFaltantesBd),
                    "roles_revisados" => count($roles),
                    "roles_con_faltantes" => count($faltantes),
                    "roles_con_permisos_extra" => count($sobrantes)
                ),
                "permisos_faltantes_bd" => $permisosFaltantesBd,
                "faltantes_por_rol" => $faltantes,
                "permisos_extra_por_rol" => $sobrantes,
                "roles_con_permisos_sensibles" => $sensibles,
                "roles" => $roles
            );
        } catch (Exception $e) {
            return array(
                "disponible" => false,
                "sin_escrituras" => true,
                "motivo" => $e->getMessage(),
                "tablas" => $tablas
            );
        }
    }

    private function muestrasHallazgosAuditoriaProveedores($db, $tablas) {
        $muestras = array();

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores"))) {
            $muestras["proveedores_sin_nombre"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    id_proveedor,
                    proveedor,
                    '' AS lista,
                    '' AS sku,
                    '' AS nombre,
                    CONCAT('Proveedor ', id_proveedor) AS referencia,
                    'Sin nombre capturado' AS descripcion_muestra,
                    COALESCE(estatus, '') AS dato
                FROM erp_proveedores
                WHERE TRIM(COALESCE(proveedor, '')) = ''
                ORDER BY id_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores"))) {
            $muestras["listas_sin_proveedor"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    l.id_lista_proveedor,
                    l.id_proveedor,
                    '' AS proveedor,
                    l.lista,
                    '' AS sku,
                    '' AS nombre,
                    CONCAT('Lista ', l.id_lista_proveedor) AS referencia,
                    'Lista sin proveedor relacionado' AS descripcion_muestra,
                    COALESCE(l.estatus, '') AS dato
                FROM erp_proveedores_listas l
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE l.id_proveedor IS NULL OR p.id_proveedor IS NULL
                ORDER BY l.id_lista_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores", "erp_proveedores_listas_productos"))) {
            $muestras["renglones_lista_sin_costo"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    lp.id_producto,
                    lp.id_lista_proveedor,
                    l.lista,
                    p.proveedor,
                    lp.sku,
                    lp.nombre,
                    CONCAT('Renglon ', lp.id_producto) AS referencia,
                    'Costo no positivo' AS descripcion_muestra,
                    COALESCE(lp.costo, 0) AS dato
                FROM erp_proveedores_listas_productos lp
                LEFT JOIN erp_proveedores_listas l ON l.id_lista_proveedor = lp.id_lista_proveedor
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE COALESCE(lp.costo, 0) <= 0
                ORDER BY lp.id_producto DESC
                LIMIT 10");

            $muestras["renglones_lista_sin_sku_y_nombre"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    lp.id_producto,
                    lp.id_lista_proveedor,
                    l.lista,
                    p.proveedor,
                    lp.sku,
                    lp.nombre,
                    CONCAT('Renglon ', lp.id_producto) AS referencia,
                    'Sin SKU ni nombre' AS descripcion_muestra,
                    COALESCE(lp.costo, 0) AS dato
                FROM erp_proveedores_listas_productos lp
                LEFT JOIN erp_proveedores_listas l ON l.id_lista_proveedor = lp.id_lista_proveedor
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE TRIM(COALESCE(lp.sku, '')) = ''
                  AND TRIM(COALESCE(lp.nombre, '')) = ''
                ORDER BY lp.id_producto DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_proveedores_listas", "erp_proveedores", "erp_proveedores_listas_productos", "erp_catalogo_skus"))) {
            $muestras["renglones_lista_sin_match_sku_erp"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    lp.id_producto,
                    lp.id_lista_proveedor,
                    l.lista,
                    p.proveedor,
                    lp.sku,
                    lp.nombre,
                    CONCAT('Renglon ', lp.id_producto) AS referencia,
                    'SKU proveedor sin match exacto ERP' AS descripcion_muestra,
                    COALESCE(lp.costo, 0) AS dato
                FROM erp_proveedores_listas_productos lp
                LEFT JOIN erp_proveedores_listas l ON l.id_lista_proveedor = lp.id_lista_proveedor
                LEFT JOIN erp_proveedores p ON p.id_proveedor = l.id_proveedor
                WHERE TRIM(COALESCE(lp.sku, '')) <> ''
                  AND NOT EXISTS (
                    SELECT 1
                    FROM erp_catalogo_skus s
                    WHERE UPPER(TRIM(s.sku)) = UPPER(TRIM(lp.sku))
                      AND s.estatus <> 'fusionado'
                  )
                ORDER BY lp.id_producto DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_sku_proveedores"))) {
            $muestras["relaciones_sku_proveedor_sin_costo"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor AS sku,
                    '' AS nombre,
                    CONCAT('Relacion ', sp.id_sku_proveedor) AS referencia,
                    'Relacion sin costo ultimo positivo' AS descripcion_muestra,
                    COALESCE(sp.costo_ultimo, 0) AS dato
                FROM erp_catalogo_sku_proveedores sp
                WHERE COALESCE(sp.costo_ultimo, 0) <= 0
                ORDER BY sp.id_sku_proveedor DESC
                LIMIT 10");

            $muestras["relaciones_sku_proveedor_sin_factor"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor AS sku,
                    '' AS nombre,
                    CONCAT('Relacion ', sp.id_sku_proveedor) AS referencia,
                    'Relacion sin factor positivo' AS descripcion_muestra,
                    COALESCE(sp.factor_conversion, 0) AS dato
                FROM erp_catalogo_sku_proveedores sp
                WHERE COALESCE(sp.factor_conversion, 0) <= 0
                ORDER BY sp.id_sku_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_sku_proveedores", "erp_catalogo_unidades"))) {
            $muestras["relaciones_sku_proveedor_sin_unidad"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor AS sku,
                    '' AS nombre,
                    CONCAT('Relacion ', sp.id_sku_proveedor) AS referencia,
                    'Relacion sin unidad de compra valida' AS descripcion_muestra,
                    COALESCE(sp.id_unidad_compra, 0) AS dato
                FROM erp_catalogo_sku_proveedores sp
                LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = sp.id_unidad_compra
                WHERE sp.id_unidad_compra IS NULL OR sp.id_unidad_compra <= 0 OR u.id_unidad IS NULL
                ORDER BY sp.id_sku_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_sku_proveedores", "erp_catalogo_skus"))) {
            $muestras["relaciones_sku_proveedor_con_sku_invalido"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor AS sku,
                    COALESCE(s.nombre, '') AS nombre,
                    CONCAT('Relacion ', sp.id_sku_proveedor) AS referencia,
                    'Relacion con SKU faltante o no activo' AS descripcion_muestra,
                    COALESCE(s.estatus, 'sin_sku') AS dato
                FROM erp_catalogo_sku_proveedores sp
                LEFT JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku
                WHERE s.id_sku IS NULL OR s.estatus <> 'activo'
                ORDER BY sp.id_sku_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_sku_proveedores", "erp_proveedores"))) {
            $muestras["relaciones_sku_proveedor_con_proveedor_invalido"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor AS sku,
                    '' AS nombre,
                    CONCAT('Relacion ', sp.id_sku_proveedor) AS referencia,
                    'Relacion con proveedor faltante' AS descripcion_muestra,
                    COALESCE(sp.estatus, '') AS dato
                FROM erp_catalogo_sku_proveedores sp
                LEFT JOIN erp_proveedores p ON p.id_proveedor = sp.id_proveedor
                WHERE p.id_proveedor IS NULL
                ORDER BY sp.id_sku_proveedor DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_skus", "erp_catalogo_sku_proveedores", "erp_catalogo_sku_codigos"))) {
            $muestras["skus_comprables_sin_codigo_principal"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    s.id_sku,
                    s.sku,
                    s.nombre,
                    CONCAT('SKU ', s.id_sku) AS referencia,
                    'SKU comprable sin codigo principal activo' AS descripcion_muestra,
                    s.estatus AS dato
                FROM erp_catalogo_skus s
                WHERE s.estatus = 'activo'
                  AND EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_proveedores sp
                    WHERE sp.id_sku = s.id_sku AND sp.estatus = 'activo'
                  )
                  AND NOT EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_codigos c
                    WHERE c.id_sku = s.id_sku AND c.es_principal = 1 AND c.estatus = 'activo'
                  )
                ORDER BY s.id_sku DESC
                LIMIT 10");
        }

        if ($this->tablasDisponiblesAuditoriaProveedores($tablas, array("erp_catalogo_skus", "erp_catalogo_sku_proveedores", "erp_catalogo_sku_impuestos"))) {
            $muestras["skus_comprables_con_fiscal_incompleto"] = $this->muestrasAuditoriaConsultaProveedores($db, "SELECT
                    s.id_sku,
                    s.sku,
                    s.nombre,
                    CONCAT('SKU ', s.id_sku) AS referencia,
                    'SKU comprable con fiscal incompleto' AS descripcion_muestra,
                    COALESCE(i.clave_producto_sat, '') AS dato
                FROM erp_catalogo_skus s
                LEFT JOIN erp_catalogo_sku_impuestos i ON i.id_sku = s.id_sku
                WHERE s.estatus = 'activo'
                  AND EXISTS (
                    SELECT 1 FROM erp_catalogo_sku_proveedores sp
                    WHERE sp.id_sku = s.id_sku AND sp.estatus = 'activo'
                  )
                  AND (
                    i.id_sku IS NULL
                    OR TRIM(COALESCE(i.clave_producto_sat, '')) = ''
                    OR TRIM(COALESCE(i.clave_unidad_sat, '')) = ''
                    OR TRIM(COALESCE(i.objeto_impuesto, '')) = ''
                    OR i.iva_porcentaje IS NULL
                    OR i.ieps_porcentaje IS NULL
                    OR i.incluye_impuestos IS NULL
                  )
                ORDER BY s.id_sku DESC
                LIMIT 10");
        }

        return $muestras;
    }

    private function muestrasAuditoriaConsultaProveedores($db, $sql) {
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return array(array(
                "referencia" => "Error muestra",
                "descripcion_muestra" => $e->getMessage(),
                "dato" => "omitido"
            ));
        }
    }

    private function tablasDisponiblesAuditoriaProveedores($tablas, $requeridas) {
        foreach ($requeridas as $tabla) {
            if (!isset($tablas[$tabla]) || !$tablas[$tabla]) {
                return false;
            }
        }

        return true;
    }

    private function tablaExisteAuditoriaProveedores($db, $tabla) {
        $base = defined("MYSQLBASE") ? MYSQLBASE : "";
        if ($base === "") {
            $base = $db->query("SELECT DATABASE()")->fetchColumn();
        }

        $sentencia = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
        $sentencia->execute(array($base, $tabla));
        return intval($sentencia->fetchColumn()) > 0;
    }

    private function respuestaAuditoriaProveedores($error, $tipo, $mensaje, $depurar = array()) {
        return array(
            "error" => $error,
            "tipo" => $tipo,
            "mensaje" => $mensaje,
            "depurar" => $depurar
        );
    }

    private function consultarSeccionProveedorErp($db, $tabla, $id_proveedor, $orden, $limite) {
        $permitidas = array(
            "erp_proveedores_fiscales" => true,
            "erp_proveedores_contactos" => true,
            "erp_proveedores_condiciones" => true,
            "erp_proveedores_documentos" => true,
            "erp_proveedores_listas_erp" => true
        );
        if (!isset($permitidas[$tabla])) {
            return array();
        }

        $limite = max(1, min(100, intval($limite)));
        $sql = "SELECT * FROM `$tabla` WHERE id_proveedor = :id_proveedor ORDER BY $orden LIMIT $limite";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":id_proveedor" => intval($id_proveedor)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function consultarDocumentosProveedorErp($db, $id_proveedor, $incluir_sensibles) {
        $sql = "SELECT * FROM erp_proveedores_documentos WHERE id_proveedor = :id_proveedor";
        if (!$incluir_sensibles) {
            $sql .= " AND (nivel_sensibilidad IS NULL OR LOWER(nivel_sensibilidad) NOT IN ('sensible', 'confidencial', 'financiero', 'financiera', 'bancario', 'bancaria'))";
        }
        $sql .= " ORDER BY id_documento_proveedor DESC LIMIT 50";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(":id_proveedor" => intval($id_proveedor)));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function resumenCostosProveedorErp($db, $id_proveedor) {
        $stmt = $db->prepare("SELECT
                COUNT(*) AS costos_total,
                COUNT(DISTINCT id_sku) AS skus_total,
                MIN(vigencia_desde) AS primera_vigencia,
                MAX(vigencia_hasta) AS ultima_vigencia
            FROM erp_proveedores_sku_costos
            WHERE id_proveedor = :id_proveedor");
        $stmt->execute(array(":id_proveedor" => intval($id_proveedor)));
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resumen ? $resumen : array(
            "costos_total" => 0,
            "skus_total" => 0,
            "primera_vigencia" => null,
            "ultima_vigencia" => null
        );
    }

    private function auditarProductivoSqlProveedores() {
        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . "productivo";
        $archivos = array(
            "proveedores" => $base . DIRECTORY_SEPARATOR . "erp_proveedores.sql",
            "listas" => $base . DIRECTORY_SEPARATOR . "erp_proveedores_listas.sql",
            "renglones" => $base . DIRECTORY_SEPARATOR . "erp_proveedores_listas_productos.sql"
        );
        $resultado = array(
            "sin_escrituras" => true,
            "directorio" => "db/productivo",
            "archivos" => array(),
            "resumen" => array(
                "proveedores" => 0,
                "listas" => 0,
                "renglones" => 0,
                "renglones_sin_costo" => 0,
                "renglones_sin_sku_y_nombre" => 0,
                "renglones_sin_codigo" => 0
            ),
            "muestras" => array(
                "proveedores" => array(),
                "listas" => array(),
                "renglones" => array()
            ),
            "advertencias" => array()
        );

        foreach ($archivos as $clave => $ruta) {
            $resultado["archivos"][$clave] = array(
                "ruta" => "db/productivo/" . basename($ruta),
                "existe" => is_file($ruta),
                "tamano" => is_file($ruta) ? filesize($ruta) : 0
            );
            if (!is_file($ruta)) {
                $resultado["advertencias"][] = "No existe " . $resultado["archivos"][$clave]["ruta"];
            }
        }

        if ($resultado["archivos"]["proveedores"]["existe"]) {
            $this->auditarProductivoSqlArchivo($archivos["proveedores"], "erp_proveedores", function ($valores) use (&$resultado) {
                $resultado["resumen"]["proveedores"]++;
                if (count($resultado["muestras"]["proveedores"]) < 8) {
                    $resultado["muestras"]["proveedores"][] = array(
                        "id_proveedor" => $this->valorSqlDumpProveedor($valores, 0),
                        "proveedor" => $this->valorSqlDumpProveedor($valores, 1),
                        "cuota" => $this->valorSqlDumpProveedor($valores, 2)
                    );
                }
            });
        }

        if ($resultado["archivos"]["listas"]["existe"]) {
            $this->auditarProductivoSqlArchivo($archivos["listas"], "erp_proveedores_listas", function ($valores) use (&$resultado) {
                $resultado["resumen"]["listas"]++;
                if (count($resultado["muestras"]["listas"]) < 8) {
                    $resultado["muestras"]["listas"][] = array(
                        "id_lista_proveedor" => $this->valorSqlDumpProveedor($valores, 0),
                        "id_proveedor" => $this->valorSqlDumpProveedor($valores, 1),
                        "lista" => $this->valorSqlDumpProveedor($valores, 2),
                        "estatus" => $this->valorSqlDumpProveedor($valores, 3),
                        "fecha" => $this->valorSqlDumpProveedor($valores, 4)
                    );
                }
            });
        }

        if ($resultado["archivos"]["renglones"]["existe"]) {
            $this->auditarProductivoSqlArchivo($archivos["renglones"], "erp_proveedores_listas_productos", function ($valores) use (&$resultado) {
                $resultado["resumen"]["renglones"]++;
                $sku = trim((string) $this->valorSqlDumpProveedor($valores, 5));
                $nombre = trim((string) $this->valorSqlDumpProveedor($valores, 7));
                $codigoBarras = trim((string) $this->valorSqlDumpProveedor($valores, 3));
                $codigoInterno = trim((string) $this->valorSqlDumpProveedor($valores, 4));
                $costo = floatval($this->valorSqlDumpProveedor($valores, 9));
                if ($costo <= 0) {
                    $resultado["resumen"]["renglones_sin_costo"]++;
                }
                if ($sku === "" && $nombre === "") {
                    $resultado["resumen"]["renglones_sin_sku_y_nombre"]++;
                }
                if ($sku === "" && $codigoBarras === "" && $codigoInterno === "") {
                    $resultado["resumen"]["renglones_sin_codigo"]++;
                }
                if (count($resultado["muestras"]["renglones"]) < 12) {
                    $resultado["muestras"]["renglones"][] = array(
                        "id_producto" => $this->valorSqlDumpProveedor($valores, 0),
                        "id_lista_proveedor" => $this->valorSqlDumpProveedor($valores, 1),
                        "marca" => $this->valorSqlDumpProveedor($valores, 2),
                        "sku" => $sku,
                        "nombre" => $nombre,
                        "costo" => $costo,
                        "piezas_por_caja" => $this->valorSqlDumpProveedor($valores, 11)
                    );
                }
            });
        }

        $resultado["preview_migracion"] = $this->previewMigracionProductivoSqlProveedores($archivos, $resultado["archivos"]);
        $resultado["advertencias"][] = "Auditoria de archivos SQL solamente; no ejecuta imports ni valida contra Catalogo.";
        return $resultado;
    }

    private function previewMigracionProductivoSqlProveedores($archivos, $metadata) {
        $db = $this->getConexion();
        $proveedoresSql = array();
        $listasSql = array();
        $renglonesSql = array();

        if (!empty($metadata["proveedores"]["existe"])) {
            $this->auditarProductivoSqlArchivo($archivos["proveedores"], "erp_proveedores", function ($valores) use (&$proveedoresSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $proveedoresSql[$id] = array(
                        "id_proveedor" => $id,
                        "proveedor" => trim((string) $this->valorSqlDumpProveedor($valores, 1)),
                        "cuota" => $this->valorSqlDumpProveedor($valores, 2)
                    );
                }
            });
        }

        if (!empty($metadata["listas"]["existe"])) {
            $this->auditarProductivoSqlArchivo($archivos["listas"], "erp_proveedores_listas", function ($valores) use (&$listasSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $listasSql[$id] = array(
                        "id_lista_proveedor" => $id,
                        "id_proveedor" => intval($this->valorSqlDumpProveedor($valores, 1)),
                        "lista" => trim((string) $this->valorSqlDumpProveedor($valores, 2)),
                        "estatus" => $this->valorSqlDumpProveedor($valores, 3),
                        "fecha" => $this->valorSqlDumpProveedor($valores, 4)
                    );
                }
            });
        }

        if (!empty($metadata["renglones"]["existe"])) {
            $this->auditarProductivoSqlArchivo($archivos["renglones"], "erp_proveedores_listas_productos", function ($valores) use (&$renglonesSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $renglonesSql[$id] = array(
                        "id_producto" => $id,
                        "id_lista_proveedor" => intval($this->valorSqlDumpProveedor($valores, 1)),
                        "marca" => trim((string) $this->valorSqlDumpProveedor($valores, 2)),
                        "codigo_barras" => trim((string) $this->valorSqlDumpProveedor($valores, 3)),
                        "codigo_interno" => trim((string) $this->valorSqlDumpProveedor($valores, 4)),
                        "sku" => trim((string) $this->valorSqlDumpProveedor($valores, 5)),
                        "nombre" => trim((string) $this->valorSqlDumpProveedor($valores, 7)),
                        "costo" => floatval($this->valorSqlDumpProveedor($valores, 9)),
                        "piezas_por_caja" => trim((string) $this->valorSqlDumpProveedor($valores, 11)),
                        "incluye_impuesto" => $this->valorSqlDumpProveedor($valores, 16),
                        "estatus" => $this->valorSqlDumpProveedor($valores, 17)
                    );
                }
            });
        }

        $proveedoresActuales = $this->mapaProveedoresActualesMigracion($db);
        $listasMigradas = $this->mapaIdsMigradosProveedorErp($db, "erp_proveedores_listas_erp", "id_lista_legacy");
        $renglonesMigrados = $this->mapaIdsMigradosProveedorErp($db, "erp_proveedores_listas_detalle_erp", "id_producto_legacy");
        $skusExactos = $this->mapaSkusExactosMigracion($db, $renglonesSql);

        $resumen = array(
            "proveedores_crear" => 0,
            "proveedores_actualizar_o_conservar" => 0,
            "proveedores_revisar" => 0,
            "listas_crear" => 0,
            "listas_existentes" => 0,
            "listas_revisar" => 0,
            "renglones_crear" => 0,
            "renglones_existentes" => 0,
            "renglones_revisar" => 0,
            "renglones_con_match_sku" => 0,
            "renglones_sin_match_sku" => 0,
            "renglones_sin_costo" => 0
        );
        $muestras = array("proveedores" => array(), "listas" => array(), "renglones" => array());

        foreach ($proveedoresSql as $proveedor) {
            $nombreKey = $this->normalizarClaveMigracionProveedor($proveedor["proveedor"]);
            if (isset($proveedoresActuales["por_id"][$proveedor["id_proveedor"]])) {
                $accion = "conservar_actualizar";
                $resumen["proveedores_actualizar_o_conservar"]++;
            } elseif ($nombreKey !== "" && isset($proveedoresActuales["por_nombre"][$nombreKey])) {
                $accion = "revisar_nombre_existente";
                $resumen["proveedores_revisar"]++;
            } else {
                $accion = "crear";
                $resumen["proveedores_crear"]++;
            }
            if (count($muestras["proveedores"]) < 8) {
                $muestras["proveedores"][] = array(
                    "id_origen" => $proveedor["id_proveedor"],
                    "referencia" => $proveedor["proveedor"],
                    "accion" => $accion
                );
            }
        }

        foreach ($listasSql as $lista) {
            if (isset($listasMigradas[$lista["id_lista_proveedor"]])) {
                $accion = "existente";
                $resumen["listas_existentes"]++;
            } elseif (!isset($proveedoresSql[$lista["id_proveedor"]])) {
                $accion = "revisar_proveedor_origen";
                $resumen["listas_revisar"]++;
            } else {
                $accion = "crear_borrador";
                $resumen["listas_crear"]++;
            }
            if (count($muestras["listas"]) < 8) {
                $muestras["listas"][] = array(
                    "id_origen" => $lista["id_lista_proveedor"],
                    "referencia" => "Proveedor " . $lista["id_proveedor"] . " | " . $lista["lista"],
                    "accion" => $accion
                );
            }
        }

        foreach ($renglonesSql as $renglon) {
            $skuKey = $this->normalizarClaveMigracionProveedor($renglon["sku"]);
            $tieneMatch = $skuKey !== "" && isset($skusExactos[$skuKey]);
            if ($tieneMatch) {
                $resumen["renglones_con_match_sku"]++;
            } else {
                $resumen["renglones_sin_match_sku"]++;
            }
            if ($renglon["costo"] <= 0) {
                $resumen["renglones_sin_costo"]++;
            }
            if (isset($renglonesMigrados[$renglon["id_producto"]])) {
                $accion = "existente";
                $resumen["renglones_existentes"]++;
            } elseif (!isset($listasSql[$renglon["id_lista_proveedor"]]) || $renglon["costo"] <= 0 || (!$tieneMatch && $renglon["sku"] === "" && $renglon["codigo_barras"] === "" && $renglon["codigo_interno"] === "")) {
                $accion = "revisar";
                $resumen["renglones_revisar"]++;
            } else {
                $accion = "crear_evidencia";
                $resumen["renglones_crear"]++;
            }
            if (count($muestras["renglones"]) < 12) {
                $muestras["renglones"][] = array(
                    "id_origen" => $renglon["id_producto"],
                    "referencia" => $renglon["sku"] ?: $renglon["codigo_barras"] ?: $renglon["codigo_interno"] ?: "-",
                    "descripcion" => $renglon["nombre"],
                    "accion" => $accion,
                    "match_sku" => $tieneMatch ? "si" : "no",
                    "costo" => $renglon["costo"]
                );
            }
        }

        return array(
            "sin_escrituras" => true,
            "politica" => "Vista previa; no importa datos, no aplica costos ni crea relaciones.",
            "resumen" => $resumen,
            "muestras" => $muestras
        );
    }

    private function mapaProveedoresActualesMigracion($db) {
        $mapa = array("por_id" => array(), "por_nombre" => array());
        $stmt = $db->query("SELECT id_proveedor, proveedor FROM erp_proveedores");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $id = intval($fila["id_proveedor"]);
            $nombre = $this->normalizarClaveMigracionProveedor($fila["proveedor"]);
            $mapa["por_id"][$id] = true;
            if ($nombre !== "") {
                $mapa["por_nombre"][$nombre] = $id;
            }
        }
        return $mapa;
    }

    private function mapaIdsMigradosProveedorErp($db, $tabla, $columna) {
        $mapa = array();
        try {
            $stmt = $db->query("SELECT `" . $columna . "` FROM `" . $tabla . "` WHERE `" . $columna . "` IS NOT NULL AND `" . $columna . "` > 0");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $mapa[intval($fila[$columna])] = true;
            }
        } catch (Exception $e) {
            return array();
        }
        return $mapa;
    }

    private function mapaSkusExactosMigracion($db, $renglones) {
        $skus = array();
        foreach ($renglones as $renglon) {
            $sku = $this->normalizarClaveMigracionProveedor($renglon["sku"]);
            if ($sku !== "") {
                $skus[$sku] = true;
            }
        }
        $claves = array_keys($skus);
        $mapa = array();
        foreach (array_chunk($claves, 500) as $chunk) {
            $placeholders = implode(",", array_fill(0, count($chunk), "?"));
            $stmt = $db->prepare("SELECT sku FROM erp_catalogo_skus WHERE UPPER(TRIM(sku)) IN (" . $placeholders . ") AND estatus <> 'fusionado'");
            $stmt->execute($chunk);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $mapa[$this->normalizarClaveMigracionProveedor($fila["sku"])] = true;
            }
        }
        return $mapa;
    }

    private function normalizarClaveMigracionProveedor($valor) {
        return strtoupper(trim((string) $valor));
    }

    public function cargarStagingProductivoSqlProveedores($idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . "productivo";
            $archivos = array(
                "proveedores" => $base . DIRECTORY_SEPARATOR . "erp_proveedores.sql",
                "listas" => $base . DIRECTORY_SEPARATOR . "erp_proveedores_listas.sql",
                "renglones" => $base . DIRECTORY_SEPARATOR . "erp_proveedores_listas_productos.sql"
            );
            foreach ($archivos as $ruta) {
                if (!is_file($ruta)) {
                    return array("error" => true, "tipo" => "warning", "mensaje" => "Falta archivo productivo: " . basename($ruta), "depurar" => null);
                }
            }

            $proveedoresSql = array();
            $listasSql = array();
            $renglonesSql = array();
            $this->auditarProductivoSqlArchivo($archivos["proveedores"], "erp_proveedores", function ($valores) use (&$proveedoresSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $proveedoresSql[$id] = array(
                        "id_proveedor" => $id,
                        "proveedor" => trim((string) $this->valorSqlDumpProveedor($valores, 1)),
                        "cuota" => $this->valorSqlDumpProveedor($valores, 2)
                    );
                }
            });
            $this->auditarProductivoSqlArchivo($archivos["listas"], "erp_proveedores_listas", function ($valores) use (&$listasSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $listasSql[$id] = array(
                        "id_lista_proveedor" => $id,
                        "id_proveedor" => intval($this->valorSqlDumpProveedor($valores, 1)),
                        "lista" => trim((string) $this->valorSqlDumpProveedor($valores, 2)),
                        "estatus" => $this->valorSqlDumpProveedor($valores, 3),
                        "fecha" => $this->valorSqlDumpProveedor($valores, 4)
                    );
                }
            });
            $this->auditarProductivoSqlArchivo($archivos["renglones"], "erp_proveedores_listas_productos", function ($valores) use (&$renglonesSql) {
                $id = intval($this->valorSqlDumpProveedor($valores, 0));
                if ($id > 0) {
                    $renglonesSql[$id] = array(
                        "id_producto" => $id,
                        "id_lista_proveedor" => intval($this->valorSqlDumpProveedor($valores, 1)),
                        "marca" => trim((string) $this->valorSqlDumpProveedor($valores, 2)),
                        "codigo_barras" => trim((string) $this->valorSqlDumpProveedor($valores, 3)),
                        "codigo_interno" => trim((string) $this->valorSqlDumpProveedor($valores, 4)),
                        "sku" => trim((string) $this->valorSqlDumpProveedor($valores, 5)),
                        "existencia" => $this->valorSqlDumpProveedor($valores, 6),
                        "nombre" => trim((string) $this->valorSqlDumpProveedor($valores, 7)),
                        "descripcion" => $this->valorSqlDumpProveedor($valores, 8),
                        "costo" => floatval($this->valorSqlDumpProveedor($valores, 9)),
                        "precio_sugerido" => $this->valorSqlDumpProveedor($valores, 10),
                        "piezas_por_caja" => trim((string) $this->valorSqlDumpProveedor($valores, 11)),
                        "rotacion" => $this->valorSqlDumpProveedor($valores, 12),
                        "porcentaje_impuesto" => $this->valorSqlDumpProveedor($valores, 13),
                        "precio_sin_impuestos" => $this->valorSqlDumpProveedor($valores, 14),
                        "porcentaje_utilidad_bruta" => $this->valorSqlDumpProveedor($valores, 15),
                        "incluye_impuesto" => $this->valorSqlDumpProveedor($valores, 16),
                        "estatus" => $this->valorSqlDumpProveedor($valores, 17)
                    );
                }
            });

            $proveedoresActuales = $this->mapaProveedoresActualesMigracion($db);
            $listasMigradas = $this->mapaIdsMigradosProveedorErp($db, "erp_proveedores_listas_erp", "id_lista_legacy");
            $renglonesMigrados = $this->mapaIdsMigradosProveedorErp($db, "erp_proveedores_listas_detalle_erp", "id_producto_legacy");
            $skusExactos = $this->mapaSkusExactosMigracion($db, $renglonesSql);
            $lote = "productivo_sql_" . date("Ymd_His");

            $db->beginTransaction();
            $insert = $db->prepare("INSERT INTO erp_proveedores_migracion_staging
                (lote, fuente, tipo_registro, id_origen, id_padre_origen, referencia, payload_json, hash_origen, accion_propuesta, estado_revision, motivo_revision, destino_tipo, creado_por, fecha_registro)
                VALUES (:lote, :fuente, :tipo_registro, :id_origen, :id_padre_origen, :referencia, :payload_json, :hash_origen, :accion_propuesta, 'pendiente', :motivo_revision, :destino_tipo, :creado_por, NOW())");

            $conteos = array("proveedores" => 0, "listas" => 0, "renglones" => 0, "revisar" => 0);
            foreach ($proveedoresSql as $proveedor) {
                $nombreKey = $this->normalizarClaveMigracionProveedor($proveedor["proveedor"]);
                if (isset($proveedoresActuales["por_id"][$proveedor["id_proveedor"]])) {
                    $accion = "conservar_actualizar";
                    $motivo = "Existe proveedor con mismo id.";
                } elseif ($nombreKey !== "" && isset($proveedoresActuales["por_nombre"][$nombreKey])) {
                    $accion = "revisar_nombre_existente";
                    $motivo = "Existe proveedor con nombre similar y otro id.";
                    $conteos["revisar"]++;
                } else {
                    $accion = "crear";
                    $motivo = "";
                }
                $this->insertarStagingMigracionProveedor($insert, $lote, "db/productivo/erp_proveedores.sql", "proveedor", $proveedor["id_proveedor"], null, $proveedor["proveedor"], $proveedor, $accion, $motivo, "erp_proveedores", $idUsuario);
                $conteos["proveedores"]++;
            }

            foreach ($listasSql as $lista) {
                if (isset($listasMigradas[$lista["id_lista_proveedor"]])) {
                    $accion = "existente";
                    $motivo = "La lista legacy ya existe en ERP.";
                } elseif (!isset($proveedoresSql[$lista["id_proveedor"]])) {
                    $accion = "revisar_proveedor_origen";
                    $motivo = "No se encontro proveedor origen en el SQL.";
                    $conteos["revisar"]++;
                } else {
                    $accion = "crear_borrador";
                    $motivo = "";
                }
                $referencia = "Proveedor " . $lista["id_proveedor"] . " | " . $lista["lista"];
                $this->insertarStagingMigracionProveedor($insert, $lote, "db/productivo/erp_proveedores_listas.sql", "lista", $lista["id_lista_proveedor"], $lista["id_proveedor"], $referencia, $lista, $accion, $motivo, "erp_proveedores_listas_erp", $idUsuario);
                $conteos["listas"]++;
            }

            foreach ($renglonesSql as $renglon) {
                $skuKey = $this->normalizarClaveMigracionProveedor($renglon["sku"]);
                $tieneMatch = $skuKey !== "" && isset($skusExactos[$skuKey]);
                if (isset($renglonesMigrados[$renglon["id_producto"]])) {
                    $accion = "existente";
                    $motivo = "El renglon legacy ya existe en ERP.";
                } elseif (!isset($listasSql[$renglon["id_lista_proveedor"]])) {
                    $accion = "revisar";
                    $motivo = "No se encontro lista origen en el SQL.";
                    $conteos["revisar"]++;
                } elseif ($renglon["costo"] <= 0) {
                    $accion = "revisar";
                    $motivo = "Costo no positivo.";
                    $conteos["revisar"]++;
                } elseif (!$tieneMatch && $renglon["sku"] === "" && $renglon["codigo_barras"] === "" && $renglon["codigo_interno"] === "") {
                    $accion = "revisar";
                    $motivo = "Sin identificador util para matching.";
                    $conteos["revisar"]++;
                } else {
                    $accion = "crear_evidencia";
                    $motivo = $tieneMatch ? "SKU ERP exacto candidato." : "Sin match exacto; entra como evidencia.";
                }
                $referencia = $renglon["sku"] ?: $renglon["codigo_barras"] ?: $renglon["codigo_interno"] ?: $renglon["nombre"];
                $this->insertarStagingMigracionProveedor($insert, $lote, "db/productivo/erp_proveedores_listas_productos.sql", "renglon", $renglon["id_producto"], $renglon["id_lista_proveedor"], $referencia, $renglon, $accion, $motivo, "erp_proveedores_listas_detalle_erp", $idUsuario);
                $conteos["renglones"]++;
            }

            $db->commit();
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Lote staging cargado desde productivo SQL",
                "depurar" => array(
                    "lote" => $lote,
                    "conteos" => $conteos,
                    "sin_tablas_oficiales" => true
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function insertarStagingMigracionProveedor($insert, $lote, $fuente, $tipo, $idOrigen, $idPadreOrigen, $referencia, $payload, $accion, $motivo, $destinoTipo, $idUsuario) {
        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $insert->execute(array(
            ":lote" => $lote,
            ":fuente" => $fuente,
            ":tipo_registro" => $tipo,
            ":id_origen" => $idOrigen,
            ":id_padre_origen" => $idPadreOrigen,
            ":referencia" => $referencia,
            ":payload_json" => $payloadJson,
            ":hash_origen" => hash("sha256", $fuente . "|" . $tipo . "|" . $idOrigen . "|" . $payloadJson),
            ":accion_propuesta" => $accion,
            ":motivo_revision" => $motivo,
            ":destino_tipo" => $destinoTipo,
            ":creado_por" => intval($idUsuario) ?: null
        ));
    }

    public function convertirLoteStagingProductivoErp($lote = "", $idUsuario = 0) {
        $db = $this->getConexion();
        try {
            $lote = trim((string) $lote);
            if ($lote === "") {
                $stmtLote = $db->query("SELECT lote FROM erp_proveedores_migracion_staging WHERE fuente LIKE 'db/productivo/%' ORDER BY fecha_registro DESC, id_staging DESC LIMIT 1");
                $lote = (string) $stmtLote->fetchColumn();
            }
            if ($lote === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No hay lote staging para convertir", "depurar" => null);
            }

            $registros = $this->registrosStagingPorLoteProveedor($db, $lote);
            if (empty($registros["proveedor"]) && empty($registros["lista"]) && empty($registros["renglon"])) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "El lote staging no tiene registros pendientes", "depurar" => array("lote" => $lote));
            }

            $db->beginTransaction();
            $conteos = array(
                "proveedores_perfil" => 0,
                "listas_creadas" => 0,
                "listas_existentes" => 0,
                "renglones_creados" => 0,
                "renglones_existentes" => 0,
                "renglones_pendientes_revision" => 0
            );
            $listasMap = array();
            $skuCache = array();

            $updateProveedor = $db->prepare("INSERT INTO erp_proveedores
                (id_proveedor, proveedor, cuota, estatus_erp, origen_erp, fecha_actualizacion)
                VALUES (:id_proveedor, :proveedor, :cuota, 'revision', 'productivo_sql', NOW())
                ON DUPLICATE KEY UPDATE
                    proveedor = COALESCE(NULLIF(proveedor, ''), VALUES(proveedor)),
                    cuota = COALESCE(NULLIF(cuota, ''), VALUES(cuota)),
                    origen_erp = COALESCE(NULLIF(origen_erp, ''), 'productivo_sql'),
                    fecha_actualizacion = NOW()");
            $upsertPerfil = $db->prepare("INSERT INTO erp_proveedores_perfil
                (id_proveedor, nombre_comercial, nombre_corto, codigo_proveedor_erp, origen, notas, creado_por, fecha_registro, fecha_actualizacion)
                VALUES (:id_proveedor, :nombre_comercial, :nombre_corto, :codigo_proveedor_erp, 'productivo_sql', :notas, :creado_por, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    nombre_comercial = COALESCE(NULLIF(nombre_comercial, ''), VALUES(nombre_comercial)),
                    nombre_corto = COALESCE(NULLIF(nombre_corto, ''), VALUES(nombre_corto)),
                    codigo_proveedor_erp = COALESCE(NULLIF(codigo_proveedor_erp, ''), VALUES(codigo_proveedor_erp)),
                    origen = COALESCE(NULLIF(origen, ''), 'productivo_sql'),
                    notas = COALESCE(NULLIF(notas, ''), VALUES(notas)),
                    fecha_actualizacion = NOW()");

            foreach ($registros["proveedor"] as $staging) {
                $payload = $this->payloadStagingProveedor($staging);
                $idProveedor = intval(isset($payload["id_proveedor"]) ? $payload["id_proveedor"] : $staging["id_origen"]);
                if ($idProveedor <= 0 || strpos((string) $staging["accion_propuesta"], "revisar") === 0) {
                    $conteos["renglones_pendientes_revision"]++;
                    continue;
                }
                $nombre = $this->textoCortoProveedorMigracion(isset($payload["proveedor"]) ? $payload["proveedor"] : $staging["referencia"], 255);
                $updateProveedor->execute(array(
                    ":id_proveedor" => $idProveedor,
                    ":proveedor" => $nombre,
                    ":cuota" => $this->textoCortoProveedorMigracion(isset($payload["cuota"]) ? $payload["cuota"] : null, 255)
                ));
                $upsertPerfil->execute(array(
                    ":id_proveedor" => $idProveedor,
                    ":nombre_comercial" => $nombre,
                    ":nombre_corto" => $this->textoCortoProveedorMigracion($nombre, 150),
                    ":codigo_proveedor_erp" => "LEG-" . $idProveedor,
                    ":notas" => "Migrado/controlado desde db/productivo. Cuota legacy: " . (isset($payload["cuota"]) ? (string) $payload["cuota"] : ""),
                    ":creado_por" => intval($idUsuario) ?: null
                ));
                $this->marcarStagingProveedor($db, intval($staging["id_staging"]), "aplicado", $idProveedor, "erp_proveedores");
                $conteos["proveedores_perfil"]++;
            }

            $insertLista = $db->prepare("INSERT INTO erp_proveedores_listas_erp
                (id_proveedor, id_lista_legacy, nombre_lista, version_lista, origen, moneda, fecha_emision, estatus, observaciones, cargado_por, fecha_registro, fecha_actualizacion)
                VALUES (:id_proveedor, :id_lista_legacy, :nombre_lista, :version_lista, 'productivo_sql', NULL, :fecha_emision, 'borrador', :observaciones, :cargado_por, NOW(), NOW())");
            foreach ($registros["lista"] as $staging) {
                $payload = $this->payloadStagingProveedor($staging);
                $idLegacy = intval(isset($payload["id_lista_proveedor"]) ? $payload["id_lista_proveedor"] : $staging["id_origen"]);
                $idProveedor = intval(isset($payload["id_proveedor"]) ? $payload["id_proveedor"] : $staging["id_padre_origen"]);
                if ($idLegacy <= 0 || $idProveedor <= 0 || strpos((string) $staging["accion_propuesta"], "revisar") === 0) {
                    $conteos["renglones_pendientes_revision"]++;
                    continue;
                }
                $idListaExistente = $this->consultarIdListaErpPorLegacy($db, $idLegacy);
                if ($idListaExistente > 0) {
                    $listasMap[$idLegacy] = $idListaExistente;
                    $this->marcarStagingProveedor($db, intval($staging["id_staging"]), "aplicado", $idListaExistente, "erp_proveedores_listas_erp");
                    $conteos["listas_existentes"]++;
                    continue;
                }
                $insertLista->execute(array(
                    ":id_proveedor" => $idProveedor,
                    ":id_lista_legacy" => $idLegacy,
                    ":nombre_lista" => $this->textoCortoProveedorMigracion(isset($payload["lista"]) ? $payload["lista"] : $staging["referencia"], 180),
                    ":version_lista" => "LEG-" . $idLegacy,
                    ":fecha_emision" => $this->fechaSqlProveedor(isset($payload["fecha"]) ? $payload["fecha"] : null),
                    ":observaciones" => "Lista legacy importada como borrador. Estatus origen: " . (isset($payload["estatus"]) ? (string) $payload["estatus"] : ""),
                    ":cargado_por" => intval($idUsuario) ?: null
                ));
                $idLista = intval($db->lastInsertId());
                $listasMap[$idLegacy] = $idLista;
                $this->marcarStagingProveedor($db, intval($staging["id_staging"]), "aplicado", $idLista, "erp_proveedores_listas_erp");
                $conteos["listas_creadas"]++;
            }

            $insertRenglon = $db->prepare("INSERT INTO erp_proveedores_listas_detalle_erp
                (id_lista_proveedor_erp, id_producto_legacy, id_sku, sku_proveedor, codigo_barras, codigo_interno, marca_proveedor, descripcion_proveedor,
                 unidad_compra_texto, costo, moneda, costo_incluye_impuestos, existencia_reportada, estado_match, criterio_match, observaciones, fecha_registro, fecha_actualizacion)
                VALUES
                (:id_lista_proveedor_erp, :id_producto_legacy, :id_sku, :sku_proveedor, :codigo_barras, :codigo_interno, :marca_proveedor, :descripcion_proveedor,
                 :unidad_compra_texto, :costo, NULL, :costo_incluye_impuestos, :existencia_reportada, :estado_match, :criterio_match, :observaciones, NOW(), NOW())");
            foreach ($registros["renglon"] as $staging) {
                $accion = (string) $staging["accion_propuesta"];
                if (strpos($accion, "revisar") === 0) {
                    $conteos["renglones_pendientes_revision"]++;
                    continue;
                }
                $payload = $this->payloadStagingProveedor($staging);
                $idLegacy = intval(isset($payload["id_producto"]) ? $payload["id_producto"] : $staging["id_origen"]);
                $idListaLegacy = intval(isset($payload["id_lista_proveedor"]) ? $payload["id_lista_proveedor"] : $staging["id_padre_origen"]);
                if ($idLegacy <= 0 || $idListaLegacy <= 0) {
                    $conteos["renglones_pendientes_revision"]++;
                    continue;
                }
                $idDetalleExistente = $this->consultarIdDetalleErpPorLegacy($db, $idLegacy);
                if ($idDetalleExistente > 0) {
                    $this->marcarStagingProveedor($db, intval($staging["id_staging"]), "aplicado", $idDetalleExistente, "erp_proveedores_listas_detalle_erp");
                    $conteos["renglones_existentes"]++;
                    continue;
                }
                if (!isset($listasMap[$idListaLegacy])) {
                    $listasMap[$idListaLegacy] = $this->consultarIdListaErpPorLegacy($db, $idListaLegacy);
                }
                $idListaErp = intval($listasMap[$idListaLegacy]);
                if ($idListaErp <= 0) {
                    $conteos["renglones_pendientes_revision"]++;
                    continue;
                }

                $skuProveedor = $this->textoCortoProveedorMigracion(isset($payload["sku"]) ? $payload["sku"] : null, 120);
                $idSku = $this->consultarIdSkuExactoMigracionProveedor($db, $skuProveedor, $skuCache);
                $estadoMatch = $idSku > 0 ? "match_exacto_pendiente" : "sin_match";
                $criterioMatch = $idSku > 0 ? "sku_erp_exacto" : "sin_match_inicial";
                $observaciones = "Importado desde productivo como evidencia. Precio sugerido: " . (isset($payload["precio_sugerido"]) ? (string) $payload["precio_sugerido"] : "");
                $insertRenglon->execute(array(
                    ":id_lista_proveedor_erp" => $idListaErp,
                    ":id_producto_legacy" => $idLegacy,
                    ":id_sku" => $idSku > 0 ? $idSku : null,
                    ":sku_proveedor" => $skuProveedor,
                    ":codigo_barras" => $this->textoCortoProveedorMigracion(isset($payload["codigo_barras"]) ? $payload["codigo_barras"] : null, 120),
                    ":codigo_interno" => $this->textoCortoProveedorMigracion(isset($payload["codigo_interno"]) ? $payload["codigo_interno"] : null, 120),
                    ":marca_proveedor" => $this->textoCortoProveedorMigracion(isset($payload["marca"]) ? $payload["marca"] : null, 160),
                    ":descripcion_proveedor" => $this->textoCortoProveedorMigracion(isset($payload["nombre"]) ? $payload["nombre"] : (isset($payload["descripcion"]) ? $payload["descripcion"] : null), 2000),
                    ":unidad_compra_texto" => $this->textoCortoProveedorMigracion(isset($payload["piezas_por_caja"]) ? "Piezas por caja: " . $payload["piezas_por_caja"] : null, 80),
                    ":costo" => $this->decimalNuloMigracionProveedor(isset($payload["costo"]) ? $payload["costo"] : null),
                    ":costo_incluye_impuestos" => $this->enteroNuloMigracionProveedor(isset($payload["incluye_impuesto"]) ? $payload["incluye_impuesto"] : null),
                    ":existencia_reportada" => $this->decimalNuloMigracionProveedor(isset($payload["existencia"]) ? $payload["existencia"] : null),
                    ":estado_match" => $estadoMatch,
                    ":criterio_match" => $criterioMatch,
                    ":observaciones" => $observaciones
                ));
                $idDetalle = intval($db->lastInsertId());
                $this->marcarStagingProveedor($db, intval($staging["id_staging"]), "aplicado", $idDetalle, "erp_proveedores_listas_detalle_erp");
                $conteos["renglones_creados"]++;
            }

            $db->commit();
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Lote staging convertido a tablas ERP oficiales",
                "depurar" => array(
                    "lote" => $lote,
                    "conteos" => $conteos,
                    "reglas" => array(
                        "Listas importadas en estatus borrador.",
                        "Renglones importados como evidencia.",
                        "No se aplicaron costos vigentes.",
                        "No se crearon relaciones proveedor-SKU automaticas.",
                        "No se actualizo costo_referencia."
                    )
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function registrosStagingPorLoteProveedor($db, $lote) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_migracion_staging WHERE lote = :lote AND estado_revision = 'pendiente' ORDER BY FIELD(tipo_registro, 'proveedor', 'lista', 'renglon'), id_origen ASC");
        $stmt->execute(array(":lote" => $lote));
        $registros = array("proveedor" => array(), "lista" => array(), "renglon" => array());
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $tipo = isset($fila["tipo_registro"]) ? $fila["tipo_registro"] : "";
            if (isset($registros[$tipo])) {
                $registros[$tipo][] = $fila;
            }
        }
        return $registros;
    }

    private function payloadStagingProveedor($staging) {
        $payload = json_decode(isset($staging["payload_json"]) ? (string) $staging["payload_json"] : "{}", true);
        return is_array($payload) ? $payload : array();
    }

    private function consultarIdListaErpPorLegacy($db, $idLegacy) {
        $stmt = $db->prepare("SELECT id_lista_proveedor_erp FROM erp_proveedores_listas_erp WHERE id_lista_legacy = :id_lista_legacy LIMIT 1");
        $stmt->execute(array(":id_lista_legacy" => intval($idLegacy)));
        return intval($stmt->fetchColumn());
    }

    private function consultarIdDetalleErpPorLegacy($db, $idLegacy) {
        $stmt = $db->prepare("SELECT id_lista_detalle_erp FROM erp_proveedores_listas_detalle_erp WHERE id_producto_legacy = :id_producto_legacy LIMIT 1");
        $stmt->execute(array(":id_producto_legacy" => intval($idLegacy)));
        return intval($stmt->fetchColumn());
    }

    private function consultarIdSkuExactoMigracionProveedor($db, $sku, &$cache) {
        $clave = $this->normalizarClaveMigracionProveedor($sku);
        if ($clave === "") {
            return 0;
        }
        if (isset($cache[$clave])) {
            return intval($cache[$clave]);
        }
        $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE UPPER(TRIM(sku)) = :sku AND estatus <> 'fusionado' ORDER BY estatus = 'activo' DESC, id_sku DESC LIMIT 1");
        $stmt->execute(array(":sku" => $clave));
        $cache[$clave] = intval($stmt->fetchColumn());
        return intval($cache[$clave]);
    }

    private function marcarStagingProveedor($db, $idStaging, $estado, $idDestino, $destinoTipo) {
        $stmt = $db->prepare("UPDATE erp_proveedores_migracion_staging
            SET estado_revision = :estado_revision,
                id_destino = :id_destino,
                destino_tipo = :destino_tipo,
                fecha_actualizacion = NOW()
            WHERE id_staging = :id_staging");
        $stmt->execute(array(
            ":estado_revision" => $estado,
            ":id_destino" => intval($idDestino) ?: null,
            ":destino_tipo" => $destinoTipo,
            ":id_staging" => intval($idStaging)
        ));
    }

    private function fechaSqlProveedor($valor) {
        $valor = trim((string) $valor);
        if ($valor === "" || strtoupper($valor) === "NULL") {
            return null;
        }
        $fecha = substr($valor, 0, 10);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? $fecha : null;
    }

    private function textoCortoProveedorMigracion($valor, $maximo) {
        $valor = trim((string) $valor);
        if ($valor === "" || strtoupper($valor) === "NULL") {
            return null;
        }
        return substr($valor, 0, intval($maximo));
    }

    private function enteroNuloMigracionProveedor($valor) {
        if ($valor === null || trim((string) $valor) === "" || strtoupper(trim((string) $valor)) === "NULL") {
            return null;
        }
        return intval($valor);
    }

    private function decimalNuloMigracionProveedor($valor) {
        if ($valor === null || trim((string) $valor) === "" || strtoupper(trim((string) $valor)) === "NULL") {
            return null;
        }
        return floatval($valor);
    }

    private function auditarProductivoSqlArchivo($ruta, $tabla, $callback) {
        $handle = fopen($ruta, "r");
        if (!$handle) {
            return;
        }
        $prefijo = "INSERT INTO `" . $tabla . "` VALUES ";
        while (($linea = fgets($handle)) !== false) {
            $linea = trim($linea);
            if (strpos($linea, $prefijo) !== 0) {
                continue;
            }
            $valores = $this->parsearValoresInsertSqlProveedor($linea, $prefijo);
            if (!empty($valores)) {
                $callback($valores);
            }
        }
        fclose($handle);
    }

    private function parsearValoresInsertSqlProveedor($linea, $prefijo) {
        $contenido = substr($linea, strlen($prefijo));
        $contenido = rtrim($contenido, ";");
        if (substr($contenido, 0, 1) === "(" && substr($contenido, -1) === ")") {
            $contenido = substr($contenido, 1, -1);
        }
        return str_getcsv($contenido, ",", "'", "\\");
    }

    private function valorSqlDumpProveedor($valores, $indice) {
        if (!isset($valores[$indice])) {
            return null;
        }
        $valor = $valores[$indice];
        if ($valor === "NULL") {
            return null;
        }
        return $valor;
    }

    public function consultarCostosProveedorErp($filtros = array()) {
        try {
            $idProveedor = isset($filtros["id_proveedor"]) ? intval($filtros["id_proveedor"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $db = $this->getConexion();
            $limite = isset($filtros["limite"]) ? intval($filtros["limite"]) : 50;
            $limite = max(1, min(200, $limite));
            $where = array("c.id_proveedor = :id_proveedor");
            $params = array(":id_proveedor" => $idProveedor);

            if (!empty($filtros["id_sku"])) {
                $where[] = "c.id_sku = :id_sku";
                $params[":id_sku"] = intval($filtros["id_sku"]);
            }
            if (!empty($filtros["id_sku_proveedor"])) {
                $where[] = "c.id_sku_proveedor = :id_sku_proveedor";
                $params[":id_sku_proveedor"] = intval($filtros["id_sku_proveedor"]);
            }
            if (!empty($filtros["id_lista_proveedor_erp"])) {
                $where[] = "c.id_lista_proveedor_erp = :id_lista_proveedor_erp";
                $params[":id_lista_proveedor_erp"] = intval($filtros["id_lista_proveedor_erp"]);
            }
            if (!empty($filtros["estatus"])) {
                $where[] = "c.estatus = :estatus";
                $params[":estatus"] = trim((string) $filtros["estatus"]);
            }

            $sql = "SELECT
                    c.*,
                    s.sku AS sku_erp,
                    s.nombre AS sku_nombre,
                    sp.sku_proveedor,
                    l.nombre_lista,
                    l.version_lista,
                    d.sku_proveedor AS lista_sku_proveedor,
                    d.descripcion_proveedor AS lista_descripcion,
                    doc.tipo_documento,
                    doc.archivo_nombre
                FROM erp_proveedores_sku_costos c
                LEFT JOIN erp_catalogo_skus s ON s.id_sku = c.id_sku
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor = c.id_sku_proveedor
                LEFT JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = c.id_lista_proveedor_erp
                LEFT JOIN erp_proveedores_listas_detalle_erp d ON d.id_lista_detalle_erp = c.id_lista_detalle_erp
                LEFT JOIN erp_proveedores_documentos doc ON doc.id_documento_proveedor = c.id_documento_proveedor
                WHERE " . implode(" AND ", $where) . "
                ORDER BY
                    CASE c.estatus WHEN 'vigente' THEN 1 WHEN 'propuesto' THEN 2 WHEN 'historico' THEN 3 ELSE 4 END,
                    c.vigencia_desde DESC,
                    c.id_costo_proveedor_sku DESC
                LIMIT :limite";
            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Costos proveedor-SKU consultados",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "registros" => $registros,
                    "limite" => $limite,
                    "politica" => array(
                        "fuente_verdad" => "erp_proveedores_sku_costos",
                        "costo_ultimo" => "referencia_operativa_no_historica",
                        "costo_referencia" => "requiere_autorizacion_posterior"
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function skusComprablesPorProveedorErp($filtros = array()) {
        try {
            $idProveedor = isset($filtros["id_proveedor"]) ? intval($filtros["id_proveedor"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $termino = isset($filtros["termino"]) ? trim((string) $filtros["termino"]) : "";
            $limite = isset($filtros["limite"]) ? intval($filtros["limite"]) : 40;
            $limite = max(1, min(100, $limite));

            $db = $this->getConexion();
            $where = array(
                "sp.id_proveedor = :id_proveedor",
                "sp.estatus = 'activo'",
                "s.estatus = 'activo'"
            );
            $params = array(":id_proveedor" => $idProveedor);
            if ($termino !== "") {
                $where[] = "(s.sku LIKE :termino OR s.nombre LIKE :termino OR sp.sku_proveedor LIKE :termino
                    OR EXISTS (
                        SELECT 1
                        FROM erp_catalogo_sku_codigos cod
                        WHERE cod.id_sku = s.id_sku
                          AND cod.estatus = 'activo'
                          AND cod.codigo LIKE :termino
                    ))";
                $params[":termino"] = "%" . $termino . "%";
            }

            $sql = "SELECT
                    sp.id_sku_proveedor,
                    sp.id_proveedor,
                    sp.id_sku,
                    sp.sku_proveedor,
                    sp.id_unidad_compra,
                    uc.abreviatura AS unidad_compra,
                    sp.factor_conversion,
                    sp.costo_ultimo,
                    sp.cantidad_minima,
                    sp.dias_entrega,
                    sp.es_preferido,
                    sp.estatus AS estatus_relacion,
                    s.id_producto_erp,
                    s.sku AS sku_erp,
                    s.nombre AS nombre_sku,
                    (
                        SELECT img.url_imagen
                        FROM erp_catalogo_imagenes img
                        WHERE img.estatus = 'activo'
                          AND (img.id_sku = s.id_sku OR (img.id_sku IS NULL AND img.id_producto_erp = s.id_producto_erp))
                        ORDER BY
                          CASE WHEN img.id_sku = s.id_sku THEN 0 ELSE 1 END,
                          img.orden ASC,
                          img.id_imagen_erp ASC
                        LIMIT 1
                    ) AS url_imagen,
                    s.id_unidad_base,
                    ub.abreviatura AS unidad_base,
                    s.costo_referencia,
                    cv.id_costo_proveedor_sku,
                    cv.costo AS costo_vigente,
                    cv.moneda AS moneda_costo,
                    cv.costo_incluye_impuestos,
                    cv.vigencia_desde,
                    cv.vigencia_hasta,
                    cv.origen AS origen_costo,
                    cv.id_lista_proveedor_erp,
                    cv.id_lista_detalle_erp,
                    CASE WHEN COALESCE(imp.iva_porcentaje, 0) > 0 AND imp.iva_porcentaje <= 1
                        THEN imp.iva_porcentaje * 100 ELSE COALESCE(imp.iva_porcentaje, 0) END AS iva_porcentaje,
                    imp.clave_producto_sat,
                    imp.clave_unidad_sat,
                    imp.objeto_impuesto,
                    imp.ieps_porcentaje,
                    imp.incluye_impuestos AS sku_incluye_impuestos,
                    CASE WHEN cp.codigo IS NULL THEN 0 ELSE 1 END AS tiene_codigo_principal,
                    cp.codigo AS codigo_principal,
                    CASE
                        WHEN imp.id_sku IS NULL THEN 0
                        WHEN TRIM(COALESCE(imp.clave_producto_sat, '')) = '' THEN 0
                        WHEN TRIM(COALESCE(imp.clave_unidad_sat, '')) = '' THEN 0
                        WHEN TRIM(COALESCE(imp.objeto_impuesto, '')) = '' THEN 0
                        WHEN imp.iva_porcentaje IS NULL THEN 0
                        WHEN imp.ieps_porcentaje IS NULL THEN 0
                        WHEN imp.incluye_impuestos IS NULL THEN 0
                        ELSE 1
                    END AS fiscal_completo
                FROM erp_catalogo_sku_proveedores sp
                INNER JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku
                LEFT JOIN erp_catalogo_unidades uc ON uc.id_unidad = sp.id_unidad_compra
                LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad = s.id_unidad_base
                LEFT JOIN erp_proveedores_sku_costos cv ON cv.id_costo_proveedor_sku = (
                    SELECT MAX(c2.id_costo_proveedor_sku)
                    FROM erp_proveedores_sku_costos c2
                    WHERE c2.id_proveedor = sp.id_proveedor
                      AND c2.id_sku = sp.id_sku
                      AND c2.id_sku_proveedor = sp.id_sku_proveedor
                      AND c2.estatus = 'vigente'
                )
                LEFT JOIN erp_catalogo_sku_codigos cp ON cp.id_sku = s.id_sku
                    AND cp.es_principal = 1
                    AND cp.estatus = 'activo'
                LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku = s.id_sku
                WHERE " . implode(" AND ", $where) . "
                ORDER BY sp.es_preferido DESC, s.nombre ASC, sp.id_sku_proveedor DESC
                LIMIT :limite";
            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
            $stmt->execute();
            $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "SKUs comprables por proveedor consultados",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "id_proveedor" => $idProveedor,
                    "termino" => $termino,
                    "limite" => $limite,
                    "registros" => $registros,
                    "contrato" => array(
                        "fuente_relacion" => "erp_catalogo_sku_proveedores",
                        "fuente_costo" => "erp_proveedores_sku_costos vigente; costo_ultimo como respaldo operativo",
                        "uso_compras" => "sugerencia para captura; la orden/solicitud debe guardar snapshot"
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    /**
     * Modulo: ERP Compras
     * Funcion: skusComprablesParaComprasErp
     * Documentacion IA: Codex GPT-5
     * Fecha: 2026-06-15
     * Descripcion: Consulta catalogo de SKUs comprables del proveedor para compras/solicitudes usando ERP como fuente base.
     * Permisos: segun contexto de llamada.
     * Tablas afectadas: erp_catalogo_sku_proveedores, erp_catalogo_skus, erp_catalogo_unidades,
     *                  erp_catalogo_imagenes, erp_catalogo_sku_impuestos, erp_catalogo_sku_impuestos_erp.
     * Reglas:
     * - Prioriza ERP; el fallback legacy se ejecuta solo si se habilita explicitamente.
     * - Mantiene trazabilidad de advertencias operativas sin romper el flujo de captura.
     */
    public function skusComprablesParaComprasErp($idProveedor, $termino, $contexto = "solicitudes", $incluirFallbackLegacy = false) {
        try {
            $idProveedor = intval($idProveedor);
            $termino = trim((string) $termino);
            if ($idProveedor <= 0 || strlen($termino) < 2) {
                return array("error" => false, "tipo" => "success", "mensaje" => "Selecciona proveedor y escribe dos caracteres", "depurar" => array());
            }
            $limite = 40;

            $respuesta = $this->skusComprablesPorProveedorErp(array(
                "id_proveedor" => $idProveedor,
                "termino" => $termino,
                "limite" => 40
            ));
            if (!empty($respuesta["error"])) {
                return $respuesta;
            }

            $registros = isset($respuesta["depurar"]["registros"]) ? $respuesta["depurar"]["registros"] : array();
            $contexto = $contexto === "ordenes" ? "ordenes" : "solicitudes";
            $salida = array();
            foreach ($registros as $fila) {
                $costo = $this->decimalComprasProveedorErp(isset($fila["costo_vigente"]) ? $fila["costo_vigente"] : null);
                $tieneCostoVigente = $costo !== null && $costo > 0;
                $fuenteCosto = $tieneCostoVigente ? "historial_vigente" : "respaldo_costo_ultimo";
                if ($costo === null) {
                    $costo = $this->decimalComprasProveedorErp(isset($fila["costo_ultimo"]) ? $fila["costo_ultimo"] : null);
                }
                $unidadCompra = $this->unidadComprasProveedorErp($fila);
                $factor = $this->decimalComprasProveedorErp(isset($fila["factor_conversion"]) ? $fila["factor_conversion"] : null);
                $fiscalCompleto = intval(isset($fila["fiscal_completo"]) ? $fila["fiscal_completo"] : 0) === 1;
                $advertencias = $this->advertenciasComprasProveedorErp($fila, $costo, $tieneCostoVigente, $unidadCompra, $factor, $fiscalCompleto);

                $item = array(
                    "id_sku" => intval(isset($fila["id_sku"]) ? $fila["id_sku"] : 0),
                    "id_producto_erp" => intval(isset($fila["id_producto_erp"]) ? $fila["id_producto_erp"] : 0),
                    "sku" => isset($fila["sku_erp"]) ? $fila["sku_erp"] : "",
                    "nombre" => isset($fila["nombre_sku"]) ? $fila["nombre_sku"] : "",
                    "url_imagen" => isset($fila["url_imagen"]) ? $fila["url_imagen"] : "",
                    "unidad" => $unidadCompra,
                    "id_sku_proveedor" => intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0),
                    "sku_proveedor" => isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "",
                    "costo_ultimo" => $costo === null ? 0 : $costo,
                    "factor_conversion" => isset($fila["factor_conversion"]) ? $fila["factor_conversion"] : null,
                    "cantidad_minima" => isset($fila["cantidad_minima"]) ? $fila["cantidad_minima"] : null,
                    "es_preferido" => isset($fila["es_preferido"]) ? $fila["es_preferido"] : 0,
                    "costo_vigente" => isset($fila["costo_vigente"]) ? $fila["costo_vigente"] : null,
                    "costo_incluye_impuestos" => isset($fila["costo_incluye_impuestos"]) ? $fila["costo_incluye_impuestos"] : null,
                    "moneda_costo" => isset($fila["moneda_costo"]) ? $fila["moneda_costo"] : null,
                    "fuente_costo" => $fuenteCosto,
                    "origen_costo" => isset($fila["origen_costo"]) ? $fila["origen_costo"] : "",
                    "vigencia_desde" => isset($fila["vigencia_desde"]) ? $fila["vigencia_desde"] : null,
                    "vigencia_hasta" => isset($fila["vigencia_hasta"]) ? $fila["vigencia_hasta"] : null,
                    "id_costo_proveedor_sku" => intval(isset($fila["id_costo_proveedor_sku"]) ? $fila["id_costo_proveedor_sku"] : 0),
                    "id_lista_proveedor_erp" => intval(isset($fila["id_lista_proveedor_erp"]) ? $fila["id_lista_proveedor_erp"] : 0),
                    "fiscal_completo" => $fiscalCompleto ? 1 : 0,
                    "unidad_factor_ok" => ($unidadCompra !== "" && $factor !== null && $factor > 0) ? 1 : 0,
                    "tiene_costo_vigente" => $tieneCostoVigente ? 1 : 0,
                    "advertencias_operativas" => $advertencias
                );
                if ($contexto === "ordenes") {
                    $item["iva_porcentaje"] = $this->decimalComprasProveedorErp(isset($fila["iva_porcentaje"]) ? $fila["iva_porcentaje"] : null);
                    $item["clave_producto_sat"] = isset($fila["clave_producto_sat"]) ? $fila["clave_producto_sat"] : "";
                    $item["clave_unidad_sat"] = isset($fila["clave_unidad_sat"]) ? $fila["clave_unidad_sat"] : "";
                    $item["objeto_impuesto"] = isset($fila["objeto_impuesto"]) ? $fila["objeto_impuesto"] : "";
                    $item["ieps_porcentaje"] = $this->decimalComprasProveedorErp(isset($fila["ieps_porcentaje"]) ? $fila["ieps_porcentaje"] : null);
                    $item["incluye_impuestos"] = isset($fila["sku_incluye_impuestos"]) ? $fila["sku_incluye_impuestos"] : null;
                }
                $salida[] = $item;
            }

            $faltantesPor = max(0, $limite - count($salida));
            if ($incluirFallbackLegacy && $faltantesPor > 0) {
                $excluirSkus = array();
                $excluirSkuProveedor = array();
                foreach ($registros as $fila) {
                    $skuBase = $this->normalizarTextoIndiceProveedorErp(isset($fila["sku"]) ? $fila["sku"] : (isset($fila["sku_erp"]) ? $fila["sku_erp"] : ""));
                    if ($skuBase !== "") {
                        $excluirSkus[$skuBase] = true;
                    }
                    $skuProveedorBase = $this->normalizarTextoIndiceProveedorErp(isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "");
                    if ($skuProveedorBase !== "") {
                        $excluirSkuProveedor[$skuProveedorBase] = true;
                    }
                }
                $listaSinRelacionar = $this->skusNoRelacionadosDesdeListasProveedoresErp(
                    $idProveedor,
                    $termino,
                    $excluirSkus,
                    $excluirSkuProveedor,
                    $faltantesPor
                );
                if (is_array($listaSinRelacionar)) {
                    $salida = array_merge($salida, $listaSinRelacionar);
                }
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => $incluirFallbackLegacy
                    ? count($salida) . " SKU consultados desde contrato y listas de proveedor"
                    : count($salida) . " SKU consultados desde contrato ERP",
                "depurar" => $salida
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array());
        }
    }

    public function compararContratoComprasErp($filtros = array()) {
        try {
            $idProveedor = isset($filtros["id_proveedor"]) ? intval($filtros["id_proveedor"]) : 0;
            $termino = isset($filtros["termino"]) ? trim((string) $filtros["termino"]) : "";
            if ($idProveedor <= 0 || strlen($termino) < 2) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Selecciona proveedor y escribe al menos dos caracteres", "depurar" => null);
            }

            $db = $this->getConexion();
            $nuevo = $this->skusComprablesPorProveedorErp(array(
                "id_proveedor" => $idProveedor,
                "termino" => $termino,
                "limite" => 40
            ));
            if ($nuevo["error"]) {
                return $nuevo;
            }

            $solicitudes = $this->buscarSkusContratoActualSolicitudes($db, $idProveedor, $termino);
            $ordenes = $this->buscarSkusContratoActualOrdenes($db, $idProveedor, $termino);
            $nuevoRegistros = isset($nuevo["depurar"]["registros"]) ? $nuevo["depurar"]["registros"] : array();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Comparativo de contrato Compras-Proveedores consultado",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "id_proveedor" => $idProveedor,
                    "termino" => $termino,
                    "resumen" => array(
                        "proveedores_contrato" => count($nuevoRegistros),
                        "solicitudes_actual" => count($solicitudes),
                        "ordenes_actual" => count($ordenes),
                        "diferencias_solicitudes" => $this->compararIdsSkuProveedor($nuevoRegistros, $solicitudes),
                        "diferencias_ordenes" => $this->compararIdsSkuProveedor($nuevoRegistros, $ordenes)
                    ),
                    "proveedores_contrato" => $nuevoRegistros,
                    "solicitudes_actual" => $solicitudes,
                    "ordenes_actual" => $ordenes,
                    "limites" => array(
                        "No reemplaza busquedas de Compras.",
                        "No modifica solicitudes ni ordenes.",
                        "No escribe datos."
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function buscarSkusContratoActualSolicitudes($db, $idProveedor, $termino) {
        $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku, s.nombre,
                u.abreviatura unidad, sp.id_sku_proveedor, sp.sku_proveedor,
                sp.costo_ultimo, sp.factor_conversion, sp.cantidad_minima, sp.es_preferido
            FROM erp_catalogo_sku_proveedores sp
            INNER JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = s.id_unidad_base
            WHERE sp.id_proveedor = :proveedor
              AND sp.estatus = 'activo'
              AND s.estatus = 'activo'
              AND (s.sku LIKE :termino OR s.nombre LIKE :termino OR sp.sku_proveedor LIKE :termino
                OR EXISTS (
                    SELECT 1
                    FROM erp_catalogo_sku_codigos cod
                    WHERE cod.id_sku = s.id_sku
                      AND cod.estatus = 'activo'
                      AND cod.codigo LIKE :termino
                ))
            ORDER BY sp.es_preferido DESC, s.nombre
            LIMIT 40");
        $stmt->execute(array(":proveedor" => intval($idProveedor), ":termino" => "%" . $termino . "%"));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function buscarSkusContratoActualOrdenes($db, $idProveedor, $termino) {
        $stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre,
                u.abreviatura unidad, sp.id_sku_proveedor, sp.sku_proveedor,
                sp.costo_ultimo, sp.cantidad_minima,
                CASE WHEN COALESCE(i.iva_porcentaje, 0) > 0 AND i.iva_porcentaje <= 1
                    THEN i.iva_porcentaje * 100 ELSE COALESCE(i.iva_porcentaje, 0) END iva_porcentaje
            FROM erp_catalogo_sku_proveedores sp
            INNER JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = s.id_unidad_base
            LEFT JOIN erp_catalogo_sku_impuestos i ON i.id_sku = s.id_sku
            WHERE sp.id_proveedor = :proveedor
              AND sp.estatus = 'activo'
              AND s.estatus = 'activo'
              AND (s.sku LIKE :termino OR s.nombre LIKE :termino OR sp.sku_proveedor LIKE :termino
                OR EXISTS (
                    SELECT 1
                    FROM erp_catalogo_sku_codigos cod
                    WHERE cod.id_sku = s.id_sku
                      AND cod.estatus = 'activo'
                      AND cod.codigo LIKE :termino
                ))
            ORDER BY sp.es_preferido DESC, s.nombre
            LIMIT 40");
        $stmt->execute(array(":proveedor" => intval($idProveedor), ":termino" => "%" . $termino . "%"));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function skusNoRelacionadosDesdeListasProveedoresErp($idProveedor, $termino, $excluirSkus, $excluirSkuProveedor, $limite = 40) {
        $limite = max(1, min(80, intval($limite)));
        if ($limite <= 0) {
            return array();
        }
        $db = $this->getConexion();
        $terminoDb = "%" . trim((string) $termino) . "%";
        $stmt = $db->prepare("SELECT
                p.id_producto,
                p.id_lista_proveedor,
                p.sku,
                p.nombre,
                p.descripcion,
                p.costo,
                p.precio_sugerido,
                p.piezas_por_caja,
                p.porcentaje_impuesto,
                p.precio_sin_impuestos,
                p.porcentaje_utilidad_bruta,
                p.incluye_impuesto,
                p.estatus
            FROM erp_proveedores_listas_productos p
            INNER JOIN erp_proveedores_listas l ON l.id_lista_proveedor = p.id_lista_proveedor
            WHERE l.id_proveedor = :id_proveedor
              AND l.estatus = 1
              AND p.estatus = 1
              AND (p.sku LIKE :termino OR p.nombre LIKE :termino OR p.descripcion LIKE :termino OR p.marca LIKE :termino)
            ORDER BY p.nombre ASC, p.sku ASC
            LIMIT :limite");
        $stmt->bindValue(":id_proveedor", intval($idProveedor));
        $stmt->bindValue(":termino", $terminoDb);
        $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
        $stmt->execute();
        $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $salida = array();
        $vistos = array();
        foreach ($registros as $fila) {
            $sku = trim((string) (isset($fila["sku"]) ? $fila["sku"] : ""));
            $nombre = trim((string) (isset($fila["nombre"]) ? $fila["nombre"] : ""));
            $skuIdx = $this->normalizarTextoIndiceProveedorErp($sku);
            $nombreIdx = $this->normalizarTextoIndiceProveedorErp($nombre);
            if ($skuIdx !== "" && (isset($excluirSkus[$skuIdx]) || isset($excluirSkuProveedor[$skuIdx]))) {
                continue;
            }
            if ($nombreIdx !== "" && isset($excluirSkus[$nombreIdx])) {
                continue;
            }
            $llave = $skuIdx !== "" ? "sku:" . $skuIdx : ($nombreIdx !== "" ? "nombre:" . $nombreIdx : "sin:" . intval($fila["id_producto"]));
            if (isset($vistos[$llave])) {
                continue;
            }
            $vistos[$llave] = true;

            $costo = $this->decimalComprasProveedorErp(isset($fila["precio_sin_impuestos"]) ? $fila["precio_sin_impuestos"] : null);
            if ($costo === null) {
                $costo = $this->decimalComprasProveedorErp(isset($fila["costo"]) ? $fila["costo"] : null);
            }
            $impuesto = $this->decimalComprasProveedorErp(isset($fila["porcentaje_impuesto"]) ? $fila["porcentaje_impuesto"] : null);
            if ($impuesto === null) {
                $impuesto = 0;
            }
            $incluyeImpuesto = isset($fila["incluye_impuesto"]) ? (intval($fila["incluye_impuesto"]) === 1) : 1;

            $salida[] = array(
                "id_sku" => 0,
                "id_producto_erp" => 0,
                "sku" => $sku,
                "nombre" => $nombre !== "" ? $nombre : (isset($fila["descripcion"]) ? trim((string) $fila["descripcion"]) : ""),
                "unidad" => "Pza",
                "id_sku_proveedor" => 0,
                "sku_proveedor" => $sku,
                "costo_ultimo" => $costo === null ? 0 : $costo,
                "factor_conversion" => isset($fila["piezas_por_caja"]) ? $fila["piezas_por_caja"] : null,
                "cantidad_minima" => 1,
                "es_preferido" => 0,
                "costo_vigente" => null,
                "moneda_costo" => "MXN",
                "fuente_costo" => "lista_proveedor",
                "origen_costo" => "erp_proveedores_listas_productos",
                "vigencia_desde" => null,
                "vigencia_hasta" => null,
                "id_costo_proveedor_sku" => 0,
                "id_lista_proveedor_erp" => intval(isset($fila["id_lista_proveedor"]) ? $fila["id_lista_proveedor"] : 0),
                "fiscal_completo" => 0,
                "unidad_factor_ok" => 0,
                "tiene_costo_vigente" => $costo !== null && $costo > 0 ? 1 : 0,
                "advertencias_operativas" => array(),
                "producto_registrado" => 0,
                "requiere_revision" => 1,
                "iva_porcentaje" => $impuesto,
                "clave_producto_sat" => "",
                "clave_unidad_sat" => "",
                "objeto_impuesto" => "",
                "ieps_porcentaje" => 0,
                "incluye_impuestos" => $incluyeImpuesto ? 1 : 0
            );
        }

        return $salida;
    }

    private function compararIdsSkuProveedor($nuevo, $actual) {
        $idsNuevo = array();
        $idsActual = array();
        foreach ($nuevo as $fila) {
            $id = intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0);
            if ($id > 0) {
                $idsNuevo[$id] = true;
            }
        }
        foreach ($actual as $fila) {
            $id = intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0);
            if ($id > 0) {
                $idsActual[$id] = true;
            }
        }
        return array(
            "solo_en_proveedores" => array_values(array_diff(array_keys($idsNuevo), array_keys($idsActual))),
            "solo_en_actual" => array_values(array_diff(array_keys($idsActual), array_keys($idsNuevo)))
        );
    }

    private function unidadComprasProveedorErp($fila) {
        $unidadCompra = isset($fila["unidad_compra"]) ? trim((string) $fila["unidad_compra"]) : "";
        if ($unidadCompra !== "") {
            return $unidadCompra;
        }
        return isset($fila["unidad_base"]) ? trim((string) $fila["unidad_base"]) : "";
    }

    private function normalizarTextoIndiceProveedorErp($valor) {
        $valor = trim((string) $valor);
        if ($valor === "") {
            return "";
        }
        return strtolower($valor);
    }

    private function decimalComprasProveedorErp($valor) {
        if ($valor === null || $valor === "") {
            return null;
        }
        return floatval($valor);
    }

    private function advertenciasComprasProveedorErp($fila, $costo, $tieneCostoVigente, $unidadCompra, $factor, $fiscalCompleto) {
        $advertencias = array();
        if (!$tieneCostoVigente) {
            $advertencias[] = array(
                "codigo" => "sin_costo_vigente",
                "nivel" => "warning",
                "mensaje" => $costo !== null && $costo > 0 ? "Sin costo vigente; usando ultimo costo" : "Sin costo vigente"
            );
        }
        if ($unidadCompra === "" || $factor === null || $factor <= 0) {
            $advertencias[] = array(
                "codigo" => "unidad_factor_incompleto",
                "nivel" => "warning",
                "mensaje" => "Unidad o factor pendiente"
            );
        }
        if (!$fiscalCompleto) {
            $advertencias[] = array(
                "codigo" => "fiscal_incompleto",
                "nivel" => "warning",
                "mensaje" => "Fiscal pendiente"
            );
        }
        if (intval(isset($fila["tiene_codigo_principal"]) ? $fila["tiene_codigo_principal"] : 0) <= 0) {
            $advertencias[] = array(
                "codigo" => "sin_codigo_principal",
                "nivel" => "info",
                "mensaje" => "Sin codigo principal"
            );
        }
        return $advertencias;
    }

    private function textoProveedorErp($datos, $clave, $maximo) {
        $valor = isset($datos[$clave]) ? trim((string) $datos[$clave]) : "";
        if ($valor === "") {
            return "";
        }
        return substr($valor, 0, intval($maximo));
    }

    private function consultarFiscalProveedorErp($db, $idFiscal, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_fiscales WHERE id_proveedor_fiscal = :id_fiscal AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_fiscal" => intval($idFiscal),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarContactoProveedorErp($db, $idContacto, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_contactos WHERE id_contacto_proveedor = :id_contacto AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_contacto" => intval($idContacto),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarCondicionProveedorErp($db, $idCondicion, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_condiciones WHERE id_condicion_proveedor = :id_condicion AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_condicion" => intval($idCondicion),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarDocumentoProveedorErp($db, $idDocumento, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_documentos WHERE id_documento_proveedor = :id_documento AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_documento" => intval($idDocumento),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarListaProveedorErp($db, $idLista, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_listas_erp WHERE id_lista_proveedor_erp = :id_lista AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_lista" => intval($idLista),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function estatusListaProveedorPermitidosErp() {
        return array("borrador", "cargada", "en_validacion", "conciliacion", "validada", "aplicada", "historica", "cancelada");
    }

    private function normalizarEstatusListaProveedorErp($estatus, $default = "borrador") {
        $estatus = strtolower(trim((string) $estatus));
        $estatus = str_replace(array(" ", "-"), "_", $estatus);
        return in_array($estatus, $this->estatusListaProveedorPermitidosErp(), true) ? $estatus : $default;
    }

    private function consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor) {
        $stmt = $db->prepare("SELECT d.*
            FROM erp_proveedores_listas_detalle_erp d
            INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp
            WHERE d.id_lista_detalle_erp = :id_detalle
              AND d.id_lista_proveedor_erp = :id_lista
              AND l.id_proveedor = :id_proveedor
            LIMIT 1");
        $stmt->execute(array(
            ":id_detalle" => intval($idDetalle),
            ":id_lista" => intval($idLista),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    public function nivelDocumentoProveedorEsSensible($nivel) {
        $nivel = strtolower(trim((string) $nivel));
        return in_array($nivel, array("sensible", "confidencial", "financiero", "financiera", "bancario", "bancaria"), true);
    }

    public function documentoProveedorRequierePermisoSensible($id_proveedor, $id_documento_proveedor, $nivel_nuevo) {
        if ($this->nivelDocumentoProveedorEsSensible($nivel_nuevo)) {
            return true;
        }

        $idDocumento = intval($id_documento_proveedor);
        if ($idDocumento <= 0) {
            return false;
        }

        try {
            $db = $this->getConexion();
            $documento = $this->consultarDocumentoProveedorErp($db, $idDocumento, intval($id_proveedor));
            return $documento ? $this->nivelDocumentoProveedorEsSensible(isset($documento["nivel_sensibilidad"]) ? $documento["nivel_sensibilidad"] : "") : false;
        } catch (Exception $e) {
            return true;
        }
    }

    public function documentoProveedorEsSensiblePorId($id_proveedor, $id_documento_proveedor) {
        $idDocumento = intval($id_documento_proveedor);
        if ($idDocumento <= 0) {
            return false;
        }

        try {
            $db = $this->getConexion();
            $documento = $this->consultarDocumentoProveedorErp($db, $idDocumento, intval($id_proveedor));
            return $documento ? $this->nivelDocumentoProveedorEsSensible(isset($documento["nivel_sensibilidad"]) ? $documento["nivel_sensibilidad"] : "") : false;
        } catch (Exception $e) {
            return true;
        }
    }

    private function valorNuloProveedorErp($valor) {
        return $valor === "" ? null : $valor;
    }

    private function fechaProveedorErp($datos, $clave) {
        $valor = isset($datos[$clave]) ? trim((string) $datos[$clave]) : "";
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ? $valor : null;
    }

    private function enteroNuloProveedorErp($datos, $clave) {
        return isset($datos[$clave]) && trim((string) $datos[$clave]) !== "" ? intval($datos[$clave]) : null;
    }

    private function decimalNuloProveedorErp($datos, $clave) {
        return isset($datos[$clave]) && trim((string) $datos[$clave]) !== "" ? floatval($datos[$clave]) : null;
    }

    private function validarArchivoProveedorErp($archivo) {
        if (!is_array($archivo) || !isset($archivo["error"]) || intval($archivo["error"]) !== UPLOAD_ERR_OK) {
            throw new Exception("Selecciona un archivo valido");
        }
        if (empty($archivo["tmp_name"]) || !is_uploaded_file($archivo["tmp_name"])) {
            throw new Exception("La carga del archivo no es valida");
        }
        $tamano = intval(isset($archivo["size"]) ? $archivo["size"] : 0);
        if ($tamano <= 0 || $tamano > 20971520) {
            throw new Exception("El archivo debe pesar entre 1 byte y 20 MB");
        }
    }

    private function detectarMimeProveedorErp($ruta) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($ruta);
    }

    private function validarMimeProveedorErp($mime) {
        $permitidos = array(
            "application/pdf",
            "text/plain",
            "text/csv",
            "application/csv",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/zip",
            "application/x-zip-compressed"
        );
        if (!in_array($mime, $permitidos, true)) {
            throw new Exception("Tipo de archivo no permitido: " . $mime);
        }
    }

    private function extensionSeguraProveedorErp($nombre, $mime) {
        $mapa = array(
            "application/pdf" => "pdf",
            "text/plain" => "txt",
            "text/csv" => "csv",
            "application/csv" => "csv",
            "application/vnd.ms-excel" => "xls",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" => "xlsx",
            "application/zip" => "zip",
            "application/x-zip-compressed" => "zip"
        );
        $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if (isset($mapa[$mime])) {
            return $mapa[$mime];
        }
        return preg_replace("/[^a-z0-9]/", "", $extension);
    }

    private function directorioListaProveedorErp($idProveedor, $idLista) {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . "storage" .
            DIRECTORY_SEPARATOR . "erp" . DIRECTORY_SEPARATOR . "proveedores" .
            DIRECTORY_SEPARATOR . intval($idProveedor) . DIRECTORY_SEPARATOR . "listas" .
            DIRECTORY_SEPARATOR . intval($idLista);
    }

    private function resolverRutaArchivoProveedorErp($rutaRelativa) {
        $rutaRelativa = str_replace("\\", "/", trim((string) $rutaRelativa));
        $prefijo = "storage/erp/proveedores/";
        if (strpos($rutaRelativa, $prefijo) !== 0 || strpos($rutaRelativa, "..") !== false) {
            return "";
        }
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $rutaRelativa);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: limitar dinamicamente la vista previa para listas grandes sin saturar el navegador.
     * Impacto: Proveedores; solo afecta lectura de preview, no importacion ni persistencia.
     */
    private function limiteFilasPreviewListaProveedorErp($limite) {
        $limite = intval($limite);
        if ($limite <= 0) {
            return 200;
        }
        return max(50, min($limite, 1000));
    }

    private function leerPreviewArchivoListaProveedorErp($ruta, $mime, $extension, $limiteFilas = 200) {
        $limiteFilas = $this->limiteFilasPreviewListaProveedorErp($limiteFilas);
        if (in_array($extension, array("csv", "txt"), true) || in_array($mime, array("text/csv", "application/csv", "text/plain"), true)) {
            return $this->leerPreviewCsvListaProveedorErp($ruta, $limiteFilas);
        }
        if ($extension === "xlsx" || $mime === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
            return $this->leerPreviewXlsxListaProveedorErp($ruta, $limiteFilas, 40);
        }
        if ($extension === "xls" || $mime === "application/vnd.ms-excel") {
            return array(
                "parseable" => false,
                "tipo_preview" => "excel_legado",
                "hoja" => null,
                "encabezados" => array(),
                "filas" => array(),
                "total_muestra" => 0,
                "mensaje" => "El formato XLS legado se conserva como evidencia. Para vista previa e importacion automatica convierte el archivo a XLSX o CSV."
            );
        }
        return array(
            "parseable" => false,
            "tipo_preview" => "evidencia",
            "hoja" => null,
            "encabezados" => array(),
            "filas" => array(),
            "total_muestra" => 0,
            "mensaje" => "Este tipo de archivo queda como evidencia; la lectura de columnas no esta disponible en esta etapa."
        );
    }

    private function leerPreviewCsvListaProveedorErp($ruta, $maxFilas) {
        $handle = fopen($ruta, "r");
        if (!$handle) {
            throw new Exception("No fue posible abrir el archivo");
        }
        $filas = array();
        while (($fila = fgetcsv($handle, 0, ",")) !== false && count($filas) < $maxFilas) {
            if (count($fila) === 1 && strpos((string) $fila[0], ";") !== false) {
                $fila = str_getcsv((string) $fila[0], ";");
            }
            $filas[] = array_map(function ($valor) {
                return trim((string) $valor);
            }, $fila);
        }
        fclose($handle);
        return $this->normalizarPreviewFilasListaProveedorErp("csv", null, $filas);
    }

    private function leerPreviewXlsxListaProveedorErp($ruta, $maxFilas, $maxColumnas) {
        $lectura = $this->leerFilasXlsxLigeroProveedorErp($ruta, $maxFilas, $maxColumnas);
        return $this->normalizarPreviewFilasListaProveedorErp("excel", $lectura["hoja"], $lectura["filas"]);
    }

    private function leerFilasXlsxLigeroProveedorErp($ruta, $maxFilas, $maxColumnas) {
        if (!class_exists("ZipArchive")) {
            throw new Exception("ZipArchive no esta disponible para leer XLSX");
        }
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) {
            throw new Exception("No fue posible abrir el XLSX");
        }

        $strings = $this->leerSharedStringsXlsxProveedorErp($zip);
        $hoja = $this->resolverPrimeraHojaXlsxProveedorErp($zip);
        $xmlHoja = $zip->getFromName($hoja["ruta"]);
        $zip->close();
        if ($xmlHoja === false) {
            throw new Exception("No fue posible leer la primera hoja del XLSX");
        }

        $sx = @simplexml_load_string($xmlHoja);
        if (!$sx) {
            throw new Exception("La primera hoja del XLSX no tiene XML valido");
        }

        $filas = array();
        foreach ($sx->sheetData->row as $row) {
            if (count($filas) >= intval($maxFilas)) {
                break;
            }
            $fila = array();
            foreach ($row->c as $celda) {
                $atributos = $celda->attributes();
                $referencia = isset($atributos["r"]) ? (string) $atributos["r"] : "";
                $indice = $this->indiceColumnaXlsxProveedorErp($referencia);
                if ($indice < 0 || $indice >= intval($maxColumnas)) {
                    continue;
                }
                $tipo = isset($atributos["t"]) ? (string) $atributos["t"] : "";
                $fila[$indice] = $this->valorCeldaXlsxProveedorErp($celda, $tipo, $strings);
            }
            if (!empty($fila)) {
                $maxIndice = min(max(array_keys($fila)), intval($maxColumnas) - 1);
                $normalizada = array();
                for ($i = 0; $i <= $maxIndice; $i++) {
                    $normalizada[] = isset($fila[$i]) ? trim((string) $fila[$i]) : "";
                }
                $filas[] = $normalizada;
            }
        }

        return array("hoja" => $hoja["nombre"], "filas" => $filas);
    }

    private function leerSharedStringsXlsxProveedorErp($zip) {
        $xml = $zip->getFromName("xl/sharedStrings.xml");
        if ($xml === false) {
            return array();
        }
        $sx = @simplexml_load_string($xml);
        if (!$sx) {
            return array();
        }
        $strings = array();
        foreach ($sx->si as $si) {
            $partes = array();
            if (isset($si->t)) {
                $partes[] = (string) $si->t;
            }
            if (isset($si->r)) {
                foreach ($si->r as $run) {
                    if (isset($run->t)) {
                        $partes[] = (string) $run->t;
                    }
                }
            }
            $strings[] = implode("", $partes);
        }
        return $strings;
    }

    private function resolverPrimeraHojaXlsxProveedorErp($zip) {
        $fallback = array("nombre" => "Hoja 1", "ruta" => "xl/worksheets/sheet1.xml");
        $workbookXml = $zip->getFromName("xl/workbook.xml");
        $relsXml = $zip->getFromName("xl/_rels/workbook.xml.rels");
        if ($workbookXml === false || $relsXml === false) {
            return $fallback;
        }
        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);
        if (!$workbook || !$rels || !isset($workbook->sheets->sheet[0])) {
            return $fallback;
        }

        $sheet = $workbook->sheets->sheet[0];
        $attrs = $sheet->attributes();
        $attrsRel = $sheet->attributes("http://schemas.openxmlformats.org/officeDocument/2006/relationships");
        $id = isset($attrsRel["id"]) ? (string) $attrsRel["id"] : "";
        $nombre = isset($attrs["name"]) ? (string) $attrs["name"] : "Hoja 1";
        $target = "";
        foreach ($rels->Relationship as $rel) {
            $relAttrs = $rel->attributes();
            if (isset($relAttrs["Id"]) && (string) $relAttrs["Id"] === $id) {
                $target = isset($relAttrs["Target"]) ? (string) $relAttrs["Target"] : "";
                break;
            }
        }
        if ($target === "") {
            return array("nombre" => $nombre, "ruta" => $fallback["ruta"]);
        }
        $ruta = strpos($target, "/") === 0 ? ltrim($target, "/") : "xl/" . ltrim($target, "/");
        return array("nombre" => $nombre, "ruta" => $ruta);
    }

    private function indiceColumnaXlsxProveedorErp($referencia) {
        if (!preg_match("/^([A-Z]+)/i", (string) $referencia, $coincide)) {
            return -1;
        }
        $letras = strtoupper($coincide[1]);
        $indice = 0;
        for ($i = 0; $i < strlen($letras); $i++) {
            $indice = ($indice * 26) + (ord($letras[$i]) - 64);
        }
        return $indice - 1;
    }

    private function valorCeldaXlsxProveedorErp($celda, $tipo, $strings) {
        if ($tipo === "s") {
            $indice = isset($celda->v) ? intval((string) $celda->v) : -1;
            return $indice >= 0 && isset($strings[$indice]) ? $strings[$indice] : "";
        }
        if ($tipo === "inlineStr") {
            if (isset($celda->is->t)) {
                return (string) $celda->is->t;
            }
            $partes = array();
            if (isset($celda->is->r)) {
                foreach ($celda->is->r as $run) {
                    if (isset($run->t)) {
                        $partes[] = (string) $run->t;
                    }
                }
            }
            return implode("", $partes);
        }
        return isset($celda->v) ? (string) $celda->v : "";
    }

    private function normalizarPreviewFilasListaProveedorErp($tipo, $hoja, $filas) {
        $encabezados = array();
        $datos = array();
        if (!empty($filas)) {
            $indiceEncabezado = $this->detectarIndiceEncabezadoListaProveedorErp($filas);
            $encabezados = isset($filas[$indiceEncabezado]) ? $filas[$indiceEncabezado] : array_shift($filas);
            $filas = array_slice($filas, $indiceEncabezado + 1);
        }
        foreach ($filas as $fila) {
            $datos[] = array_slice($fila, 0, max(count($encabezados), count($fila)));
        }
        return array(
            "parseable" => true,
            "tipo_preview" => $tipo,
            "hoja" => $hoja,
            "encabezados" => $encabezados,
            "filas" => $datos,
            "total_muestra" => count($datos),
            "fila_encabezado" => isset($indiceEncabezado) ? $indiceEncabezado + 1 : 1,
            "mensaje" => "Vista previa generada desde la primera hoja o archivo plano."
        );
    }

    private function detectarIndiceEncabezadoListaProveedorErp($filas) {
        $mejorIndice = 0;
        $mejorPuntaje = -1;
        $limite = min(count($filas), 15);
        $palabras = array("sku", "modelo", "codigo", "clave", "descripcion", "producto", "nombre", "costo", "precio", "marca", "unidad", "piezas", "existencia", "stock");
        for ($i = 0; $i < $limite; $i++) {
            $fila = isset($filas[$i]) ? $filas[$i] : array();
            $noVacios = 0;
            $puntaje = 0;
            foreach ($fila as $celda) {
                $texto = $this->normalizarTextoMapeoProveedorErp($celda);
                if ($texto !== "") {
                    $noVacios++;
                }
                foreach ($palabras as $palabra) {
                    if ($texto !== "" && strpos($texto, $palabra) !== false) {
                        $puntaje += 3;
                        break;
                    }
                }
            }
            $puntaje += $noVacios;
            if ($puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejorIndice = $i;
            }
        }
        return $mejorIndice;
    }

    private function sugerirMapeoColumnasListaProveedorErp($encabezados) {
        $campos = array(
            "sku_proveedor" => array("sku", "clave", "codigo proveedor", "codigo_proveedor", "modelo"),
            "codigo_barras" => array("codigo barras", "codigo de barras", "ean", "upc", "barcode"),
            "codigo_interno" => array("codigo interno", "codigo", "clave interna", "id"),
            "marca_proveedor" => array("marca", "brand"),
            "descripcion_proveedor" => array("descripcion", "descripcion proveedor", "producto", "nombre", "articulo"),
            "costo" => array("costo", "precio", "precio neto", "precio compra", "precio unitario"),
            "moneda" => array("moneda", "currency"),
            "unidad_compra_texto" => array("unidad", "presentacion", "empaque"),
            "factor_conversion" => array("factor", "piezas por caja", "piezas", "contenido"),
            "costo_incluye_impuestos" => array("incluye impuesto", "iva incluido", "impuestos"),
            "existencia_reportada" => array("existencia", "stock", "disponible")
        );
        $resultado = array();
        foreach ($campos as $campo => $alias) {
            $resultado[$campo] = null;
            foreach ($encabezados as $indice => $encabezado) {
                $normalizado = $this->normalizarTextoMapeoProveedorErp($encabezado);
                foreach ($alias as $palabra) {
                    if ($normalizado !== "" && strpos($normalizado, $this->normalizarTextoMapeoProveedorErp($palabra)) !== false) {
                        $resultado[$campo] = array("indice" => $indice, "columna" => $encabezado);
                        break 2;
                    }
                }
            }
        }
        return $resultado;
    }

    private function normalizarTextoMapeoProveedorErp($texto) {
        $texto = strtolower(trim((string) $texto));
        $buscar = array("á", "é", "í", "ó", "ú", "ñ");
        $reemplazar = array("a", "e", "i", "o", "u", "n");
        return str_replace($buscar, $reemplazar, $texto);
    }

    private function leerFilasImportacionListaProveedorErp($ruta, $mime, $extension, $maxFilas) {
        if (in_array($extension, array("csv", "txt"), true) || in_array($mime, array("text/csv", "application/csv", "text/plain"), true)) {
            $handle = fopen($ruta, "r");
            if (!$handle) {
                throw new Exception("No fue posible abrir el archivo");
            }
            $filas = array();
            while (($fila = fgetcsv($handle, 0, ",")) !== false && count($filas) <= $maxFilas) {
                if (count($fila) === 1 && strpos((string) $fila[0], ";") !== false) {
                    $fila = str_getcsv((string) $fila[0], ";");
                }
                $filas[] = array_map(function ($valor) {
                    return trim((string) $valor);
                }, $fila);
            }
            fclose($handle);
            return $this->normalizarPreviewFilasListaProveedorErp("csv", null, $filas);
        }
        if ($extension === "xlsx" || $mime === "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet") {
            $lectura = $this->leerFilasXlsxLigeroProveedorErp($ruta, intval($maxFilas) + 20, 80);
            return $this->normalizarPreviewFilasListaProveedorErp("excel", $lectura["hoja"], $lectura["filas"]);
        }
        if ($extension === "xls" || $mime === "application/vnd.ms-excel") {
            return array(
                "parseable" => false,
                "tipo_preview" => "excel_legado",
                "filas" => array(),
                "encabezados" => array(),
                "mensaje" => "El formato XLS legado se conserva como evidencia. Para importacion automatica convierte el archivo a XLSX o CSV."
            );
        }
        return array("parseable" => false, "filas" => array(), "encabezados" => array());
    }

    private function mapeoListaTieneIdentidadProveedorErp($mapeo) {
        foreach (array("descripcion_proveedor", "sku_proveedor", "codigo_barras", "codigo_interno") as $campo) {
            if (isset($mapeo[$campo]) && trim((string) $mapeo[$campo]) !== "" && intval($mapeo[$campo]) >= 0) {
                return true;
            }
        }
        return false;
    }

    private function normalizarFilaImportacionListaProveedorErp($fila, $mapeo, $monedaLista) {
        $campos = array("sku_proveedor", "codigo_barras", "codigo_interno", "marca_proveedor", "descripcion_proveedor", "unidad_compra_texto", "factor_conversion", "costo", "moneda", "costo_incluye_impuestos", "existencia_reportada");
        $normalizado = array();
        foreach ($campos as $campo) {
            $indice = isset($mapeo[$campo]) && $mapeo[$campo] !== "" ? intval($mapeo[$campo]) : -1;
            $normalizado[$campo] = $indice >= 0 && isset($fila[$indice]) ? trim((string) $fila[$indice]) : "";
        }
        if ($normalizado["moneda"] === "" && trim((string) $monedaLista) !== "") {
            $normalizado["moneda"] = $monedaLista;
        }
        $normalizado["costo"] = $this->normalizarDecimalImportacionProveedorErp($normalizado["costo"]);
        $normalizado["factor_conversion"] = $this->normalizarDecimalImportacionProveedorErp($normalizado["factor_conversion"]);
        $normalizado["existencia_reportada"] = $this->normalizarDecimalImportacionProveedorErp($normalizado["existencia_reportada"]);
        return $normalizado;
    }

    private function filaImportacionTieneContenidoProveedorErp($fila) {
        foreach (array("sku_proveedor", "codigo_barras", "codigo_interno", "marca_proveedor", "descripcion_proveedor", "costo") as $campo) {
            if (isset($fila[$campo]) && trim((string) $fila[$campo]) !== "") {
                return true;
            }
        }
        return false;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: detectar renglones ya importados al recargar una lista de proveedor.
     * Impacto: Proveedores; permite anexar productos nuevos sin borrar ni sobrescribir detalle existente.
     * Contrato: devuelve claves fuertes normalizadas; descripcion solo se usa si no hay identificadores.
     */
    private function clavesUnicasFilaListaProveedorErp($fila) {
        $claves = array();
        $campos = array(
            "sku_proveedor" => "sku",
            "codigo_barras" => "barcode",
            "codigo_interno" => "interno"
        );
        foreach ($campos as $campo => $prefijo) {
            $valor = isset($fila[$campo]) ? $this->normalizarClaveComparacionProveedorErp($fila[$campo]) : "";
            if ($valor !== "") {
                $claves[] = $prefijo . ":" . $valor;
                $claves[] = "identificador:" . $valor;
            }
        }
        if (!empty($claves)) {
            return array_values(array_unique($claves));
        }
        $descripcion = isset($fila["descripcion_proveedor"]) ? $this->normalizarClaveComparacionProveedorErp($fila["descripcion_proveedor"]) : "";
        return $descripcion !== "" ? array("descripcion:" . $descripcion) : array();
    }

    private function normalizarClaveComparacionProveedorErp($valor) {
        $valor = strtolower(trim((string) $valor));
        $buscar = array("Ã¡", "Ã©", "Ã­", "Ã³", "Ãº", "Ã±");
        $reemplazar = array("a", "e", "i", "o", "u", "n");
        $valor = str_replace($buscar, $reemplazar, $valor);
        return preg_replace("/\s+/", " ", $valor);
    }

    private function cargarClavesExistentesListaProveedorErp($db, $idLista) {
        $stmt = $db->prepare("SELECT *
            FROM erp_proveedores_listas_detalle_erp
            WHERE id_lista_proveedor_erp = :id_lista");
        $stmt->execute(array(":id_lista" => intval($idLista)));
        $claves = array();
        while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
            foreach ($this->clavesUnicasFilaListaProveedorErp($fila) as $clave) {
                $claves[$clave] = $fila;
            }
        }
        return $claves;
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: decidir si una recarga de lista debe actualizar datos variables de un renglon existente.
     * Impacto: Proveedores; evita duplicados y permite corregir costos/moneda/impuestos desde archivo actualizado.
     * Contrato: no cambia matching, relaciones SKU proveedor, costos vigentes ni costo_referencia.
     */
    private function cambiosImportacionRenglonExistenteProveedorErp($existente, $normalizado) {
        $campos = array(
            "marca_proveedor" => "texto",
            "descripcion_proveedor" => "texto",
            "unidad_compra_texto" => "texto",
            "factor_conversion" => "decimal",
            "costo" => "decimal",
            "moneda" => "texto",
            "costo_incluye_impuestos" => "booleano",
            "existencia_reportada" => "decimal"
        );
        $cambios = array();
        foreach ($campos as $campo => $tipo) {
            $nuevo = isset($normalizado[$campo]) ? $normalizado[$campo] : null;
            if ($nuevo === null || trim((string) $nuevo) === "") {
                continue;
            }
            if ($tipo === "booleano") {
                $nuevo = $this->booleanoImportacionProveedorErp($nuevo);
                if ($nuevo === null) {
                    continue;
                }
                $actual = isset($existente[$campo]) && $existente[$campo] !== null && trim((string) $existente[$campo]) !== "" ? intval($existente[$campo]) : null;
            } elseif ($tipo === "decimal") {
                $nuevo = floatval($nuevo);
                $actual = isset($existente[$campo]) && $existente[$campo] !== null && trim((string) $existente[$campo]) !== "" ? floatval($existente[$campo]) : null;
                if ($actual !== null && abs($actual - $nuevo) < 0.000001) {
                    continue;
                }
            } else {
                $nuevo = $campo === "moneda" ? strtoupper($this->limitarTextoProveedorErp($nuevo, 10)) : $this->limitarTextoProveedorErp($nuevo, $campo === "descripcion_proveedor" ? 5000 : 160);
                $actual = isset($existente[$campo]) ? trim((string) $existente[$campo]) : "";
                if ($campo === "moneda") {
                    $actual = strtoupper($actual);
                }
            }
            if ($actual !== $nuevo) {
                $cambios[$campo] = $nuevo;
            }
        }
        return $cambios;
    }

    private function filaImportacionEsNotaProveedorErp($fila) {
        $texto = $this->normalizarTextoMapeoProveedorErp(implode(" ", array(
            isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "",
            isset($fila["codigo_barras"]) ? $fila["codigo_barras"] : "",
            isset($fila["codigo_interno"]) ? $fila["codigo_interno"] : "",
            isset($fila["descripcion_proveedor"]) ? $fila["descripcion_proveedor"] : ""
        )));
        if ($texto === "") {
            return false;
        }
        $sinCosto = floatval(isset($fila["costo"]) ? $fila["costo"] : 0) <= 0;
        $patrones = array(
            "precios sujetos",
            "sujeto a cambio",
            "sin previo aviso",
            "lista de precios",
            "vigencia",
            "condiciones",
            "nota:"
        );
        foreach ($patrones as $patron) {
            if (strpos($texto, $patron) !== false && $sinCosto) {
                return true;
            }
        }
        return false;
    }

    private function normalizarDecimalImportacionProveedorErp($valor) {
        $valor = trim((string) $valor);
        if ($valor === "") {
            return "";
        }
        $valor = str_replace(array("$", ",", " "), "", $valor);
        return is_numeric($valor) ? (string) floatval($valor) : "";
    }

    private function decimalValorNuloProveedorErp($valor) {
        return trim((string) $valor) === "" ? null : floatval($valor);
    }

    private function booleanoImportacionProveedorErp($valor) {
        $valor = strtolower(trim((string) $valor));
        if ($valor === "") {
            return null;
        }
        if (in_array($valor, array("1", "si", "sí", "true", "incluye", "incluido"), true)) {
            return 1;
        }
        if (in_array($valor, array("0", "no", "false", "sin incluir"), true)) {
            return 0;
        }
        return is_numeric($valor) ? intval($valor) : null;
    }

    private function limitarTextoProveedorErp($valor, $maximo) {
        return substr(trim((string) $valor), 0, intval($maximo));
    }

    public function consultar_productos_pedido_lista() {
        $this->setColumnas(array(
            "erpplp.sku",
            "erpplp.nombre",
            "erpppe.cantidad",
            "erpplp.costo"
        ));

        $this->setTabla($this->tabla_erp_proveedores_pedidos_elementos . " erpppe");
        $this->setInnerJoin($this->tabla_proveedores_listas_productos . " erpplp ON erpppe.id_elemento = erpplp.id_producto");
        $this->setWhere("id_proveedor_pedido = " . $this->getId_proveedor_pedido());
        return $this->listar();
    }

    public function eliminar_elementos_pedido() {
        $this->setWhere('id_proveedor_pedido = ' . $this->getId_proveedor_pedido());
        $this->setTabla($this->tabla_erp_proveedores_pedidos_elementos);
        return $this->eliminar();
    }

    public function actualizar_pedido() {
        $campos = array(
            "proveedor",
            "id_proveedor",
            "id_lista_proveedor",
            "comentario",
            "fch_m",
            "estatus",
            "titulo",
            "total"
        );
        $valores = array(
            $this->getProveedor(),
            $this->getId_proveedor(),
            $this->getId_lista_proveedor(),
            $this->getComentario(),
            DATE_NOW,
            $this->getEstatus(),
            $this->getTitulo(),
            $this->getTotal()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setWhere('id_pedido = ' . $this->getId_proveedor_pedido());
        $this->setTabla($this->tabla_erp_proveedores_pedidos);
        return $this->update();
    }

    function listar_proveedor_productos() {
        $this->setColumnas(array(
            "ecomp.id_producto",
            "ecomp.nombre",
            "ecomp.descripcion",
            "ecomp.costo",
            "ecomp.sku",
            "ecomp.id_lista_proveedor"
        ));
        $this->setWhere("ecomp.id_lista_proveedor");
        $this->setTabla($this->tabla_proveedores_listas_productos . " ecomp");

        return $this->listar();
    }

    public function consultar_pedido() {
        $campos = array(
            "id_pedido",
            "titulo",
            "id_proveedor",
            "id_lista_proveedor",
            "proveedor",
            "comentario",
            "fch_r",
            "estatus"
        );
        $this->setColumnas($campos);
        $this->setWhere("id_pedido = " . $this->getId_proveedor_pedido());
        $this->setTabla($this->tabla_erp_proveedores_pedidos);
        return $this->buscarRegistro();
    }

    public function consultar_elementos_inventario() {
        $campos = array(
            "erpaie.id_proveedor_pedido",
            "erpaie.id_elemento",
            "erpaie.tipo_elemento",
            "erpaie.cantidad"
        );

        $this->setColumnas($campos);
        $this->setWhere("id_proveedor_pedido = " . $this->getId_proveedor_pedido());
        $this->setTabla($this->tabla_erp_proveedores_pedidos_elementos . " erpaie");
//    $this->setInnerJoin($this->tabla_ecom_productos . " ecomp ON ecomp.id_producto = ecompp.id_producto");
//    $this->setLeftJoin($this->tabla_ecom_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        return $this->listar();
    }

    function registrar_inventario() {
        $campos = array(
            "proveedor",
            "id_proveedor",
            "id_lista_proveedor",
            "comentario",
            "fch_r",
            "estatus",
            "titulo",
            "total"
        );
        $valores = array(
            $this->getProveedor(),
            $this->getId_proveedor(),
            $this->getId_lista_proveedor(),
            $this->getComentario(),
            DATE_NOW,
            $this->getEstatus(),
            $this->getTitulo(),
            $this->getTotal()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_erp_proveedores_pedidos);
        return $this->insertar();
    }

    public function consultar_lista() {
//    SELECT ecomp.id_pedido,ecomp.total,ecomp.estatus, crmc.nombres FROM ecom_pedidos ecomp
//LEFT JOIN crm_clientes crmc ON crmc.id_cliente = ecomp.id_cliente
        $this->setColumnas(array(
            "id_pedido",
            'titulo',
            "proveedor",
            "id_proveedor",
            "id_lista_proveedor",
            "comentario",
            "fch_r",
            "estatus",
            "titulo",
            "total"
        ));
        $this->setTabla($this->tabla_erp_proveedores_pedidos);
        $this->setOrderBy("id_pedido");
        $this->setAscDesc("DESC");
        return $this->listar();
    }

    function registrar_elementos_pedido() {
        $campos = array(
            "id_proveedor_pedido",
            "id_elemento",
            "tipo_elemento",
            "cantidad"
        );
        $valores = array(
            $this->getId_proveedor_pedido(),
            $this->getId_elemento(),
            "producto",
            $this->getCantidad()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_erp_proveedores_pedidos_elementos);
        return $this->insertar();
    }

    public function consultar() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores);

        return $this->buscarRegistro();
    }
    //TODO: intentar arreglar imagenes sino quitar
    function listar_busqueda_productos() {
        $this->setColumnas(array(
            "ecomplp.id_producto",
            "ecomplp.nombre",
            "ecomplp.descripcion",
            "ecomplp.costo",
            "ecomplp.piezas_por_caja",
            "ecomplp.sku",
            "ecomplp.id_lista_proveedor",
            "ecomp.id_producto as id_producto_ecom",
            "ecompi.url_imagen"
        ));
        $this->setWhere("(ecomplp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomplp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomplp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomplp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomplp.descripcion LIKE '%" . $this->getBusqueda() . "%')");
        $this->setAnd('ecomplp.id_lista_proveedor = ' . $this->getId_lista_proveedor());
        $this->setLeftJoin("ecom_productos ecomp ON ecomp.sku = ecomplp.sku");
        $this->setLeftJoin("ecom_productos_imagenes ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setTabla($this->tabla_proveedores_listas_productos . " ecomplp");
        $respuesta = $this->listar();
        $productos = $respuesta['error'] == false && is_array($respuesta['depurar']) ? $respuesta['depurar'] : array();

        $db = $this->getConexion();
        $stmt = $db->prepare("SELECT
            -s.id_sku AS id_producto,
            s.nombre,
            p.descripcion,
            COALESCE(sp.costo_ultimo, s.costo_referencia, 0) AS costo,
            sp.factor_conversion AS piezas_por_caja,
            s.sku,
            l.id_lista_proveedor,
            0 AS id_producto_ecom,
            s.id_sku AS id_sku_erp,
            '' AS url_imagen
          FROM erp_catalogo_sku_proveedores sp
          INNER JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku AND s.estatus = 'activo'
          INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp AND p.estatus = 'activo'
          INNER JOIN erp_proveedores_listas l ON l.id_proveedor = sp.id_proveedor AND l.id_lista_proveedor = :lista
          WHERE sp.estatus = 'activo'
            AND (s.sku LIKE :busqueda OR s.nombre LIKE :busqueda OR p.nombre LIKE :busqueda OR sp.sku_proveedor LIKE :busqueda)
          ORDER BY sp.es_preferido DESC, s.nombre
          LIMIT 50");
        $stmt->execute(array(
            ":lista" => intval($this->getId_lista_proveedor()),
            ":busqueda" => "%" . $this->getBusqueda() . "%"
        ));
        $skusExistentes = array();
        foreach ($productos as $producto) {
            $skusExistentes[strtolower(trim($producto['sku']))] = true;
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $productoErp) {
            if (!isset($skusExistentes[strtolower(trim($productoErp['sku']))])) {
                $productos[] = $productoErp;
            }
        }
        return array("error" => false, "tipo" => "success", "mensaje" => "Productos consultados", "depurar" => $productos);
    }

    public function listar_productos_proveedor() {
        $this->setColumnas(array(
            "ecomp.id_producto",
            "ecomp.nombre",
            "ecomp.descripcion",
            "ecomp.costo",
            "ecomp.sku",
            "ecomp.id_lista_proveedor"
        ));

        $this->setWhere('ecomp.id_lista_proveedor = ' . $this->getId_lista_proveedor());
        $this->setTabla($this->tabla_proveedores_listas_productos . " ecomp");

        return $this->listar();
    }

    public function listar_proveedores() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores);

        return $this->listar();
    }

    public function listarProveedoresErp($filtros = array()) {
        try {
            $db = $this->getConexion();
            $pagina = isset($filtros["pagina"]) ? max(1, intval($filtros["pagina"])) : 1;
            $limite = isset($filtros["limite"]) ? intval($filtros["limite"]) : 25;
            $limite = max(1, min(100, $limite));
            $offset = ($pagina - 1) * $limite;
            $where = array("1 = 1");
            $params = array();

            if (!empty($filtros["busqueda"])) {
                $where[] = "(p.proveedor LIKE :busqueda OR pf.nombre_comercial LIKE :busqueda OR pf.nombre_corto LIKE :busqueda OR pf.codigo_proveedor_erp LIKE :busqueda OR f.rfc LIKE :busqueda OR f.razon_social LIKE :busqueda)";
                $params[":busqueda"] = "%" . trim($filtros["busqueda"]) . "%";
            }
            if (!empty($filtros["estatus_erp"])) {
                $where[] = "p.estatus_erp = :estatus_erp";
                $params[":estatus_erp"] = trim($filtros["estatus_erp"]);
            }
            if (!empty($filtros["tipo_proveedor"])) {
                $where[] = "pf.tipo_proveedor = :tipo_proveedor";
                $params[":tipo_proveedor"] = trim($filtros["tipo_proveedor"]);
            }

            $whereSql = implode(" AND ", $where);
            $sqlBase = "FROM erp_proveedores p
                LEFT JOIN erp_proveedores_perfil pf ON pf.id_proveedor = p.id_proveedor
                LEFT JOIN erp_proveedores_fiscales f ON f.id_proveedor = p.id_proveedor
                  AND f.id_proveedor_fiscal = (
                    SELECT MAX(f2.id_proveedor_fiscal)
                    FROM erp_proveedores_fiscales f2
                    WHERE f2.id_proveedor = p.id_proveedor
                  )
                WHERE $whereSql";

            $stmtTotal = $db->prepare("SELECT COUNT(*) $sqlBase");
            $stmtTotal->execute($params);
            $total = intval($stmtTotal->fetchColumn());

            $sql = "SELECT
                    p.id_proveedor,
                    p.proveedor,
                    p.cuota,
                    p.estatus_erp,
                    p.origen_erp,
                    p.fecha_actualizacion,
                    pf.nombre_comercial,
                    pf.nombre_corto,
                    pf.codigo_proveedor_erp,
                    pf.tipo_proveedor,
                    pf.clasificacion_operativa,
                    pf.responsable_interno_id,
                    f.rfc,
                    f.razon_social,
                    f.estatus AS estatus_fiscal,
                    (
                      SELECT COUNT(*)
                      FROM erp_proveedores_contactos c
                      WHERE c.id_proveedor = p.id_proveedor
                    ) AS contactos_total,
                    (
                      SELECT COUNT(*)
                      FROM erp_proveedores_listas_erp l
                      WHERE l.id_proveedor = p.id_proveedor
                    ) AS listas_erp_total
                $sqlBase
                ORDER BY COALESCE(NULLIF(pf.nombre_comercial, ''), NULLIF(p.proveedor, ''), p.id_proveedor) ASC
                LIMIT :limite OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(":limite", $limite, PDO::PARAM_INT);
            $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Proveedores ERP consultados",
                "depurar" => array(
                    "registros" => $stmt->fetchAll(PDO::FETCH_ASSOC),
                    "paginacion" => array(
                        "pagina" => $pagina,
                        "limite" => $limite,
                        "total" => $total
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function consultarProveedorErp($id_proveedor, $incluir_sensibles = false) {
        try {
            $id_proveedor = intval($id_proveedor);
            if ($id_proveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT
                    p.*,
                    pf.nombre_comercial,
                    pf.nombre_corto,
                    pf.codigo_proveedor_erp,
                    pf.tipo_proveedor,
                    pf.clasificacion_operativa,
                    pf.origen AS origen_perfil,
                    pf.responsable_interno_id,
                    pf.notas,
                    pf.creado_por,
                    pf.revisado_por,
                    pf.autorizado_por,
                    pf.fecha_revision,
                    pf.fecha_autorizacion,
                    pf.fecha_registro AS perfil_fecha_registro,
                    pf.fecha_actualizacion AS perfil_fecha_actualizacion
                FROM erp_proveedores p
                LEFT JOIN erp_proveedores_perfil pf ON pf.id_proveedor = p.id_proveedor
                WHERE p.id_proveedor = :id_proveedor
                LIMIT 1");
            $stmt->execute(array(":id_proveedor" => $id_proveedor));
            $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$proveedor) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Proveedor ERP consultado",
                "depurar" => array(
                    "proveedor" => $proveedor,
                    "fiscales" => $this->consultarSeccionProveedorErp($db, "erp_proveedores_fiscales", $id_proveedor, "id_proveedor_fiscal DESC", 20),
                    "contactos" => $this->consultarSeccionProveedorErp($db, "erp_proveedores_contactos", $id_proveedor, "es_principal DESC, prioridad ASC, id_contacto_proveedor DESC", 50),
                    "condiciones" => $this->consultarSeccionProveedorErp($db, "erp_proveedores_condiciones", $id_proveedor, "vigencia_desde DESC, id_condicion_proveedor DESC", 20),
                    "documentos" => $this->consultarDocumentosProveedorErp($db, $id_proveedor, $incluir_sensibles),
                    "listas" => $this->consultarSeccionProveedorErp($db, "erp_proveedores_listas_erp", $id_proveedor, "id_lista_proveedor_erp DESC", 50),
                    "costos_resumen" => $this->resumenCostosProveedorErp($db, $id_proveedor),
                    "preparacion" => $this->preparacionProveedorErp($db, $id_proveedor)
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function preparacionProveedorErp($db, $idProveedor) {
        $conteos = array(
            "fiscales" => $this->contarProveedorErp($db, "erp_proveedores_fiscales", "id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "contactos" => $this->contarProveedorErp($db, "erp_proveedores_contactos", "id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "condiciones" => $this->contarProveedorErp($db, "erp_proveedores_condiciones", "id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "documentos" => $this->contarProveedorErp($db, "erp_proveedores_documentos", "id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "listas" => $this->contarProveedorErp($db, "erp_proveedores_listas_erp", "id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "listas_borrador" => $this->contarProveedorErp($db, "erp_proveedores_listas_erp", "id_proveedor = :id_proveedor AND estatus = 'borrador'", array(":id_proveedor" => $idProveedor)),
            "listas_cargadas" => $this->contarProveedorErp($db, "erp_proveedores_listas_erp", "id_proveedor = :id_proveedor AND estatus IN ('cargada','en_validacion','conciliacion','validada','aplicada')", array(":id_proveedor" => $idProveedor)),
            "listas_validadas" => $this->contarProveedorErp($db, "erp_proveedores_listas_erp", "id_proveedor = :id_proveedor AND estatus IN ('validada','aplicada')", array(":id_proveedor" => $idProveedor)),
            "listas_aplicadas" => $this->contarProveedorErp($db, "erp_proveedores_listas_erp", "id_proveedor = :id_proveedor AND estatus = 'aplicada'", array(":id_proveedor" => $idProveedor)),
            "renglones" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor", array(":id_proveedor" => $idProveedor)),
            "renglones_sin_match" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND (d.id_sku IS NULL OR d.id_sku <= 0 OR d.estado_match IN ('sin_match','ambiguo'))", array(":id_proveedor" => $idProveedor)),
            "renglones_sin_costo" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND COALESCE(d.costo, 0) <= 0", array(":id_proveedor" => $idProveedor)),
            "renglones_sin_moneda" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND (d.moneda IS NULL OR TRIM(d.moneda) = '')", array(":id_proveedor" => $idProveedor)),
            "renglones_relacionados" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND d.id_sku IS NOT NULL AND d.id_sku > 0", array(":id_proveedor" => $idProveedor)),
            "renglones_operativos" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND (COALESCE(d.id_sku, 0) > 0 OR COALESCE(d.id_sku_proveedor, 0) > 0 OR d.estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado'))", array(":id_proveedor" => $idProveedor)),
            "renglones_operativos_sin_costo" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND (COALESCE(d.id_sku, 0) > 0 OR COALESCE(d.id_sku_proveedor, 0) > 0 OR d.estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) AND COALESCE(d.costo, 0) <= 0", array(":id_proveedor" => $idProveedor)),
            "renglones_operativos_sin_moneda" => $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp d INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND (COALESCE(d.id_sku, 0) > 0 OR COALESCE(d.id_sku_proveedor, 0) > 0 OR d.estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) AND (d.moneda IS NULL OR TRIM(d.moneda) = '')", array(":id_proveedor" => $idProveedor)),
            "renglones_costos_aplicados" => $this->contarProveedorErp($db, "erp_proveedores_sku_costos c INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = c.id_lista_proveedor_erp", "l.id_proveedor = :id_proveedor AND c.id_lista_detalle_erp IS NOT NULL AND c.id_lista_detalle_erp > 0 AND c.estatus = 'vigente'", array(":id_proveedor" => $idProveedor)),
            "relaciones_sku" => $this->contarProveedorErp($db, "erp_catalogo_sku_proveedores", "id_proveedor = :id_proveedor AND estatus = 'activo'", array(":id_proveedor" => $idProveedor)),
            "costos_vigentes" => $this->contarProveedorErp($db, "erp_proveedores_sku_costos", "id_proveedor = :id_proveedor AND estatus = 'vigente'", array(":id_proveedor" => $idProveedor)),
            "incidencias_pendientes" => $this->contarIncidenciasPendientesProveedorErp($db, $idProveedor)
        );

        $items = array(
            $this->itemPreparacionProveedorErp("Datos fiscales", $conteos["fiscales"] > 0, $conteos["fiscales"], "Registrar al menos un RFC/razon social cuando aplique."),
            $this->itemPreparacionProveedorErp("Contactos", $conteos["contactos"] > 0, $conteos["contactos"], "Registrar contactos por area para compras, cobranza o soporte."),
            $this->itemPreparacionProveedorErp("Condiciones", $conteos["condiciones"] > 0, $conteos["condiciones"], "Registrar moneda, credito, minimo o condiciones logisticas disponibles."),
            $this->itemPreparacionProveedorErp("Documentos metadata", $conteos["documentos"] > 0, $conteos["documentos"], "Registrar evidencia documental; archivo fisico queda para fase posterior."),
            $this->itemPreparacionProveedorErp("Listas versionadas", $conteos["listas"] > 0, $conteos["listas"], "Crear al menos una lista ERP para reutilizar precios y SKUs proveedor."),
            $this->itemPreparacionProveedorErp("Listas cargadas", $conteos["listas_cargadas"] > 0, $conteos["listas_cargadas"], "Cargar renglones desde archivo o captura antes de validar la lista."),
            $this->itemPreparacionProveedorErp("Listas validadas", $conteos["listas_validadas"] > 0, $conteos["listas_validadas"], "Validar cuando los renglones tengan identidad, costo y moneda suficiente."),
            $this->itemPreparacionProveedorErp("Listas aplicadas", $conteos["listas_aplicadas"] > 0, $conteos["listas_aplicadas"], "Aplicar relaciones o costos autorizados para que Compras los pueda usar."),
            $this->itemPreparacionProveedorErp("Renglones de lista", $conteos["renglones"] > 0, $conteos["renglones"], "Capturar renglones o migrarlos de forma controlada desde productivo legacy."),
            $this->itemPreparacionProveedorErp("Renglones operativos", $conteos["renglones_operativos"] > 0, $conteos["renglones_operativos"], "Seleccionar SKU ERP en los renglones confiables; no todos los productos del proveedor tienen que relacionarse."),
            $this->itemPreparacionProveedorErp("Matching informativo", true, $conteos["renglones_sin_match"], "Los renglones sin match quedan como evidencia y no bloquean la lista si no se usaran para Compras."),
            $this->itemPreparacionProveedorErp("Costos operativos", $conteos["renglones_operativos_sin_costo"] <= 0, $conteos["renglones_operativos_sin_costo"], "Completar costo solo en renglones seleccionados como referencia operativa."),
            $this->itemPreparacionProveedorErp("Moneda operativa", $conteos["renglones_operativos_sin_moneda"] <= 0, $conteos["renglones_operativos_sin_moneda"], "Completar moneda solo en renglones seleccionados como referencia operativa."),
            $this->itemPreparacionProveedorErp("Costos aplicados desde lista", $conteos["renglones_costos_aplicados"] > 0, $conteos["renglones_costos_aplicados"], "Aplicar costos individuales solo cuando el renglon sea confiable."),
            $this->itemPreparacionProveedorErp("Relaciones proveedor-SKU", $conteos["relaciones_sku"] > 0, $conteos["relaciones_sku"], "Aplicar relaciones confiables para que Compras pueda sugerir SKUs."),
            $this->itemPreparacionProveedorErp("Costos vigentes", $conteos["costos_vigentes"] > 0, $conteos["costos_vigentes"], "Aplicar costo individual vigente para SKUs confiables."),
            $this->itemPreparacionProveedorErp("Incidencias pendientes", $conteos["incidencias_pendientes"] <= 0, $conteos["incidencias_pendientes"], "Revisar pendientes enviados a Catalogo.")
        );

        $ok = 0;
        foreach ($items as $item) {
            if ($item["ok"]) {
                $ok++;
            }
        }
        $total = count($items);
        $porcentaje = $total > 0 ? round(($ok / $total) * 100) : 0;
        $estado = $porcentaje >= 85 ? "listo_pruebas" : ($porcentaje >= 55 ? "en_preparacion" : "incompleto");

        return array(
            "sin_escrituras" => true,
            "estado" => $estado,
            "porcentaje" => $porcentaje,
            "ok" => $ok,
            "total" => $total,
            "conteos" => $conteos,
            "items" => $items
        );
    }

    private function contarProveedorErp($db, $tabla, $where, $params) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM " . $tabla . " WHERE " . $where);
        $stmt->execute($params);
        return intval($stmt->fetchColumn());
    }

    private function contarIncidenciasPendientesProveedorErp($db, $idProveedor) {
        $stmt = $db->prepare("SELECT COUNT(*)
            FROM erp_catalogo_incidencias_calidad
            WHERE origen = 'proveedores'
              AND estatus NOT IN ('resuelta', 'descartada')
              AND (
                detalle_json LIKE :id_proveedor_json
                OR detalle_json LIKE :id_proveedor_texto
              )");
        $stmt->execute(array(
            ":id_proveedor_json" => '%"id_proveedor":' . intval($idProveedor) . '%',
            ":id_proveedor_texto" => '%"id_proveedor":"' . intval($idProveedor) . '"%'
        ));
        return intval($stmt->fetchColumn());
    }

    private function itemPreparacionProveedorErp($titulo, $ok, $valor, $accion) {
        return array(
            "titulo" => $titulo,
            "ok" => $ok ? true : false,
            "valor" => intval($valor),
            "accion" => $accion
        );
    }

    public function guardarGeneralesProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $proveedor = $this->textoProveedorErp($datos, "proveedor", 255);
            $nombreComercial = $this->textoProveedorErp($datos, "nombre_comercial", 255);
            $nombreCorto = $this->textoProveedorErp($datos, "nombre_corto", 150);
            $codigo = $this->textoProveedorErp($datos, "codigo_proveedor_erp", 80);
            $tipo = $this->textoProveedorErp($datos, "tipo_proveedor", 80);
            $clasificacion = $this->textoProveedorErp($datos, "clasificacion_operativa", 80);
            $origen = $this->textoProveedorErp($datos, "origen", 40);
            $responsable = isset($datos["responsable_interno_id"]) && trim($datos["responsable_interno_id"]) !== "" ? intval($datos["responsable_interno_id"]) : null;
            $notas = $this->textoProveedorErp($datos, "notas", 5000);

            if ($proveedor === "" && $nombreComercial === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura nombre de proveedor o nombre comercial", "depurar" => null);
            }
            if ($proveedor === "") {
                $proveedor = $nombreComercial;
            }
            if ($nombreComercial === "") {
                $nombreComercial = $proveedor;
            }

            $antes = $idProveedor > 0 ? $this->consultarProveedorErp($idProveedor) : null;
            if ($idProveedor > 0 && (!is_array($antes) || $antes["error"])) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $db->beginTransaction();
            if ($idProveedor > 0) {
                $stmt = $db->prepare("UPDATE erp_proveedores
                    SET proveedor = :proveedor,
                        origen_erp = :origen_erp,
                        fecha_actualizacion = NOW()
                    WHERE id_proveedor = :id_proveedor");
                $stmt->execute(array(
                    ":proveedor" => $proveedor,
                    ":origen_erp" => $origen !== "" ? $origen : null,
                    ":id_proveedor" => $idProveedor
                ));
            } else {
                $stmt = $db->prepare("INSERT INTO erp_proveedores (proveedor, origen_erp, fecha_actualizacion)
                    VALUES (:proveedor, :origen_erp, NOW())");
                $stmt->execute(array(
                    ":proveedor" => $proveedor,
                    ":origen_erp" => $origen !== "" ? $origen : null
                ));
                $idProveedor = intval($db->lastInsertId());
            }

            $stmtPerfil = $db->prepare("INSERT INTO erp_proveedores_perfil
                (id_proveedor, nombre_comercial, nombre_corto, codigo_proveedor_erp, tipo_proveedor, clasificacion_operativa, origen, responsable_interno_id, notas, creado_por, fecha_registro, fecha_actualizacion)
                VALUES
                (:id_proveedor, :nombre_comercial, :nombre_corto, :codigo_proveedor_erp, :tipo_proveedor, :clasificacion_operativa, :origen, :responsable_interno_id, :notas, :creado_por, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                  nombre_comercial = VALUES(nombre_comercial),
                  nombre_corto = VALUES(nombre_corto),
                  codigo_proveedor_erp = VALUES(codigo_proveedor_erp),
                  tipo_proveedor = VALUES(tipo_proveedor),
                  clasificacion_operativa = VALUES(clasificacion_operativa),
                  origen = VALUES(origen),
                  responsable_interno_id = VALUES(responsable_interno_id),
                  notas = VALUES(notas),
                  fecha_actualizacion = NOW()");
            $stmtPerfil->execute(array(
                ":id_proveedor" => $idProveedor,
                ":nombre_comercial" => $nombreComercial,
                ":nombre_corto" => $nombreCorto !== "" ? $nombreCorto : null,
                ":codigo_proveedor_erp" => $codigo !== "" ? $codigo : null,
                ":tipo_proveedor" => $tipo !== "" ? $tipo : null,
                ":clasificacion_operativa" => $clasificacion !== "" ? $clasificacion : null,
                ":origen" => $origen !== "" ? $origen : null,
                ":responsable_interno_id" => $responsable,
                ":notas" => $notas !== "" ? $notas : null,
                ":creado_por" => intval($id_usuario)
            ));

            $db->commit();
            $despues = $this->consultarProveedorErp($idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Datos generales del proveedor guardados",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "accion" => $antes ? "actualizar" : "crear",
                    "antes" => $antes && !$antes["error"] ? $antes["depurar"]["proveedor"] : null,
                    "despues" => !$despues["error"] ? $despues["depurar"]["proveedor"] : null
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function cambiarEstatusProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $estatus = isset($datos["estatus_erp"]) ? trim((string) $datos["estatus_erp"]) : "";
            $motivo = $this->textoProveedorErp($datos, "motivo", 1000);
            $permitidos = array("prospecto", "en_revision", "activo_compras", "suspendido", "bloqueado", "inactivo");
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }
            if (!in_array($estatus, $permitidos, true)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Estatus ERP no permitido", "depurar" => null);
            }
            if (in_array($estatus, array("suspendido", "bloqueado", "inactivo"), true) && $motivo === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura motivo para suspender, bloquear o inactivar proveedor", "depurar" => null);
            }

            $db->beginTransaction();
            $stmt = $db->prepare("SELECT * FROM erp_proveedores WHERE id_proveedor=:id_proveedor FOR UPDATE");
            $stmt->execute(array(":id_proveedor" => $idProveedor));
            $antes = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$antes) {
                throw new Exception("Proveedor no encontrado");
            }
            $stmt = $db->prepare("UPDATE erp_proveedores
                SET estatus_erp=:estatus_erp,
                    fecha_actualizacion=NOW()
                WHERE id_proveedor=:id_proveedor");
            $stmt->execute(array(
                ":estatus_erp" => $estatus,
                ":id_proveedor" => $idProveedor
            ));
            $stmt = $db->prepare("SELECT * FROM erp_proveedores WHERE id_proveedor=:id_proveedor");
            $stmt->execute(array(":id_proveedor" => $idProveedor));
            $despues = $stmt->fetch(PDO::FETCH_ASSOC);
            $db->commit();

            return array(
                "error" => false,
                "tipo" => in_array($estatus, array("suspendido", "bloqueado", "inactivo"), true) ? "warning" : "success",
                "mensaje" => "Estatus ERP del proveedor actualizado",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "estatus_erp" => $estatus,
                    "motivo" => $motivo,
                    "antes" => $antes,
                    "despues" => $despues,
                    "politica" => array(
                        "bloquea_envio_orden" => in_array($estatus, array("suspendido", "bloqueado", "inactivo"), true),
                        "no_bloquea_por_si_solo" => in_array($estatus, array("prospecto", "en_revision", "activo_compras"), true)
                    )
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarFiscalProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idFiscal = isset($datos["id_proveedor_fiscal"]) ? intval($datos["id_proveedor_fiscal"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $proveedor = $this->consultarProveedorErp($idProveedor);
            if ($proveedor["error"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $rfc = strtoupper($this->textoProveedorErp($datos, "rfc", 20));
            $razonSocial = $this->textoProveedorErp($datos, "razon_social", 255);
            if ($rfc === "" && $razonSocial === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura RFC o razon social fiscal", "depurar" => null);
            }

            $antes = $idFiscal > 0 ? $this->consultarFiscalProveedorErp($db, $idFiscal, $idProveedor) : null;
            if ($idFiscal > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Registro fiscal no encontrado", "depurar" => null);
            }

            $valores = array(
                ":id_proveedor" => $idProveedor,
                ":rfc" => $rfc !== "" ? $rfc : null,
                ":razon_social" => $razonSocial !== "" ? $razonSocial : null,
                ":regimen_fiscal" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "regimen_fiscal", 120)),
                ":codigo_postal_fiscal" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "codigo_postal_fiscal", 10)),
                ":pais" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "pais", 80)),
                ":estado" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estado", 120)),
                ":municipio" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "municipio", 120)),
                ":colonia" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "colonia", 160)),
                ":calle" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "calle", 160)),
                ":numero_exterior" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "numero_exterior", 40)),
                ":numero_interior" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "numero_interior", 40)),
                ":domicilio_fiscal" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "domicilio_fiscal", 5000)),
                ":uso_cfdi_preferido" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "uso_cfdi_preferido", 20)),
                ":fecha_constancia" => $this->fechaProveedorErp($datos, "fecha_constancia"),
                ":vigencia_desde" => $this->fechaProveedorErp($datos, "vigencia_desde"),
                ":vigencia_hasta" => $this->fechaProveedorErp($datos, "vigencia_hasta"),
                ":estatus" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estatus", 40))
            );

            if ($idFiscal > 0) {
                $valores[":id_proveedor_fiscal"] = $idFiscal;
                $stmt = $db->prepare("UPDATE erp_proveedores_fiscales SET
                    rfc = :rfc,
                    razon_social = :razon_social,
                    regimen_fiscal = :regimen_fiscal,
                    codigo_postal_fiscal = :codigo_postal_fiscal,
                    pais = :pais,
                    estado = :estado,
                    municipio = :municipio,
                    colonia = :colonia,
                    calle = :calle,
                    numero_exterior = :numero_exterior,
                    numero_interior = :numero_interior,
                    domicilio_fiscal = :domicilio_fiscal,
                    uso_cfdi_preferido = :uso_cfdi_preferido,
                    fecha_constancia = :fecha_constancia,
                    vigencia_desde = :vigencia_desde,
                    vigencia_hasta = :vigencia_hasta,
                    estatus = :estatus,
                    fecha_actualizacion = NOW()
                    WHERE id_proveedor_fiscal = :id_proveedor_fiscal AND id_proveedor = :id_proveedor");
                $stmt->execute($valores);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_proveedores_fiscales
                    (id_proveedor, rfc, razon_social, regimen_fiscal, codigo_postal_fiscal, pais, estado, municipio, colonia, calle, numero_exterior, numero_interior, domicilio_fiscal, uso_cfdi_preferido, fecha_constancia, vigencia_desde, vigencia_hasta, estatus, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :rfc, :razon_social, :regimen_fiscal, :codigo_postal_fiscal, :pais, :estado, :municipio, :colonia, :calle, :numero_exterior, :numero_interior, :domicilio_fiscal, :uso_cfdi_preferido, :fecha_constancia, :vigencia_desde, :vigencia_hasta, :estatus, NOW(), NOW())");
                $stmt->execute($valores);
                $idFiscal = intval($db->lastInsertId());
            }

            $despues = $this->consultarFiscalProveedorErp($db, $idFiscal, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Datos fiscales del proveedor guardados",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_proveedor_fiscal" => $idFiscal,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarContactoProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idContacto = isset($datos["id_contacto_proveedor"]) ? intval($datos["id_contacto_proveedor"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $proveedor = $this->consultarProveedorErp($idProveedor);
            if ($proveedor["error"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $nombre = $this->textoProveedorErp($datos, "nombre", 180);
            $area = $this->textoProveedorErp($datos, "area", 80);
            $correo = $this->textoProveedorErp($datos, "correo", 180);
            $telefono = $this->textoProveedorErp($datos, "telefono", 60);
            $celular = $this->textoProveedorErp($datos, "celular", 60);
            if ($nombre === "" && $correo === "" && $telefono === "" && $celular === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura nombre o algun medio de contacto", "depurar" => null);
            }

            $antes = $idContacto > 0 ? $this->consultarContactoProveedorErp($db, $idContacto, $idProveedor) : null;
            if ($idContacto > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Contacto no encontrado", "depurar" => null);
            }

            $valores = array(
                ":id_proveedor" => $idProveedor,
                ":area" => $this->valorNuloProveedorErp($area),
                ":nombre" => $this->valorNuloProveedorErp($nombre),
                ":puesto" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "puesto", 120)),
                ":correo" => $this->valorNuloProveedorErp($correo),
                ":telefono" => $this->valorNuloProveedorErp($telefono),
                ":extension" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "extension", 20)),
                ":celular" => $this->valorNuloProveedorErp($celular),
                ":whatsapp" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "whatsapp", 60)),
                ":recibe_ordenes_compra" => isset($datos["recibe_ordenes_compra"]) ? 1 : 0,
                ":recibe_notificaciones" => isset($datos["recibe_notificaciones"]) ? 1 : 0,
                ":es_principal" => isset($datos["es_principal"]) ? 1 : 0,
                ":prioridad" => isset($datos["prioridad"]) && trim($datos["prioridad"]) !== "" ? intval($datos["prioridad"]) : null,
                ":observaciones" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "observaciones", 5000)),
                ":estatus" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estatus", 40))
            );

            if ($idContacto > 0) {
                $valores[":id_contacto_proveedor"] = $idContacto;
                $stmt = $db->prepare("UPDATE erp_proveedores_contactos SET
                    area = :area,
                    nombre = :nombre,
                    puesto = :puesto,
                    correo = :correo,
                    telefono = :telefono,
                    extension = :extension,
                    celular = :celular,
                    whatsapp = :whatsapp,
                    recibe_ordenes_compra = :recibe_ordenes_compra,
                    recibe_notificaciones = :recibe_notificaciones,
                    es_principal = :es_principal,
                    prioridad = :prioridad,
                    observaciones = :observaciones,
                    estatus = :estatus,
                    fecha_actualizacion = NOW()
                    WHERE id_contacto_proveedor = :id_contacto_proveedor AND id_proveedor = :id_proveedor");
                $stmt->execute($valores);
            } else {
                $valores[":creado_por"] = intval($id_usuario);
                $stmt = $db->prepare("INSERT INTO erp_proveedores_contactos
                    (id_proveedor, area, nombre, puesto, correo, telefono, extension, celular, whatsapp, recibe_ordenes_compra, recibe_notificaciones, es_principal, prioridad, observaciones, estatus, creado_por, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :area, :nombre, :puesto, :correo, :telefono, :extension, :celular, :whatsapp, :recibe_ordenes_compra, :recibe_notificaciones, :es_principal, :prioridad, :observaciones, :estatus, :creado_por, NOW(), NOW())");
                $stmt->execute($valores);
                $idContacto = intval($db->lastInsertId());
            }

            $despues = $this->consultarContactoProveedorErp($db, $idContacto, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Contacto del proveedor guardado",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_contacto_proveedor" => $idContacto,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarCondicionProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idCondicion = isset($datos["id_condicion_proveedor"]) ? intval($datos["id_condicion_proveedor"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $proveedor = $this->consultarProveedorErp($idProveedor);
            if ($proveedor["error"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $antes = $idCondicion > 0 ? $this->consultarCondicionProveedorErp($db, $idCondicion, $idProveedor) : null;
            if ($idCondicion > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Condicion no encontrada", "depurar" => null);
            }

            $valores = array(
                ":id_proveedor" => $idProveedor,
                ":moneda_preferida" => $this->valorNuloProveedorErp(strtoupper($this->textoProveedorErp($datos, "moneda_preferida", 10))),
                ":requiere_orden_compra" => isset($datos["requiere_orden_compra"]) ? 1 : 0,
                ":forma_pago_preferida" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "forma_pago_preferida", 80)),
                ":metodo_pago_preferido" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "metodo_pago_preferido", 80)),
                ":dias_credito" => $this->enteroNuloProveedorErp($datos, "dias_credito"),
                ":limite_credito" => $this->decimalNuloProveedorErp($datos, "limite_credito"),
                ":minimo_compra" => $this->decimalNuloProveedorErp($datos, "minimo_compra"),
                ":minimo_unidades" => $this->decimalNuloProveedorErp($datos, "minimo_unidades"),
                ":tiempo_entrega_dias" => $this->enteroNuloProveedorErp($datos, "tiempo_entrega_dias"),
                ":dias_surtido" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "dias_surtido", 120)),
                ":tipo_flete" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "tipo_flete", 80)),
                ":cobertura_entrega" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "cobertura_entrega", 5000)),
                ":condiciones_pago" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "condiciones_pago", 5000)),
                ":condiciones_logisticas" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "condiciones_logisticas", 5000)),
                ":restricciones_operativas" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "restricciones_operativas", 5000)),
                ":observaciones" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "observaciones", 5000)),
                ":vigencia_desde" => $this->fechaProveedorErp($datos, "vigencia_desde"),
                ":vigencia_hasta" => $this->fechaProveedorErp($datos, "vigencia_hasta"),
                ":estatus" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estatus", 40))
            );

            if ($idCondicion > 0) {
                $valores[":id_condicion_proveedor"] = $idCondicion;
                $stmt = $db->prepare("UPDATE erp_proveedores_condiciones SET
                    moneda_preferida = :moneda_preferida,
                    requiere_orden_compra = :requiere_orden_compra,
                    forma_pago_preferida = :forma_pago_preferida,
                    metodo_pago_preferido = :metodo_pago_preferido,
                    dias_credito = :dias_credito,
                    limite_credito = :limite_credito,
                    minimo_compra = :minimo_compra,
                    minimo_unidades = :minimo_unidades,
                    tiempo_entrega_dias = :tiempo_entrega_dias,
                    dias_surtido = :dias_surtido,
                    tipo_flete = :tipo_flete,
                    cobertura_entrega = :cobertura_entrega,
                    condiciones_pago = :condiciones_pago,
                    condiciones_logisticas = :condiciones_logisticas,
                    restricciones_operativas = :restricciones_operativas,
                    observaciones = :observaciones,
                    vigencia_desde = :vigencia_desde,
                    vigencia_hasta = :vigencia_hasta,
                    estatus = :estatus,
                    fecha_actualizacion = NOW()
                    WHERE id_condicion_proveedor = :id_condicion_proveedor AND id_proveedor = :id_proveedor");
                $stmt->execute($valores);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_proveedores_condiciones
                    (id_proveedor, moneda_preferida, requiere_orden_compra, forma_pago_preferida, metodo_pago_preferido, dias_credito, limite_credito, minimo_compra, minimo_unidades, tiempo_entrega_dias, dias_surtido, tipo_flete, cobertura_entrega, condiciones_pago, condiciones_logisticas, restricciones_operativas, observaciones, vigencia_desde, vigencia_hasta, estatus, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :moneda_preferida, :requiere_orden_compra, :forma_pago_preferida, :metodo_pago_preferido, :dias_credito, :limite_credito, :minimo_compra, :minimo_unidades, :tiempo_entrega_dias, :dias_surtido, :tipo_flete, :cobertura_entrega, :condiciones_pago, :condiciones_logisticas, :restricciones_operativas, :observaciones, :vigencia_desde, :vigencia_hasta, :estatus, NOW(), NOW())");
                $stmt->execute($valores);
                $idCondicion = intval($db->lastInsertId());
            }

            $despues = $this->consultarCondicionProveedorErp($db, $idCondicion, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Condiciones del proveedor guardadas",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_condicion_proveedor" => $idCondicion,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarDocumentoProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idDocumento = isset($datos["id_documento_proveedor"]) ? intval($datos["id_documento_proveedor"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $proveedor = $this->consultarProveedorErp($idProveedor, true);
            if ($proveedor["error"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $tipoDocumento = $this->textoProveedorErp($datos, "tipo_documento", 80);
            $referencia = $this->textoProveedorErp($datos, "referencia", 255);
            $archivoNombre = $this->textoProveedorErp($datos, "archivo_nombre", 255);
            if ($tipoDocumento === "" && $referencia === "" && $archivoNombre === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura tipo, referencia o nombre de archivo", "depurar" => null);
            }

            $metadatos = $this->textoProveedorErp($datos, "metadatos_json", 5000);
            if ($metadatos !== "" && json_decode($metadatos, true) === null && json_last_error() !== JSON_ERROR_NONE) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Metadatos JSON invalido", "depurar" => null);
            }

            $antes = $idDocumento > 0 ? $this->consultarDocumentoProveedorErp($db, $idDocumento, $idProveedor) : null;
            if ($idDocumento > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Documento no encontrado", "depurar" => null);
            }

            $valores = array(
                ":id_proveedor" => $idProveedor,
                ":tipo_documento" => $this->valorNuloProveedorErp($tipoDocumento),
                ":nivel_sensibilidad" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "nivel_sensibilidad", 40)),
                ":entidad_origen" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "entidad_origen", 80)),
                ":id_referencia" => $this->enteroNuloProveedorErp($datos, "id_referencia"),
                ":referencia_tipo" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "referencia_tipo", 80)),
                ":referencia" => $this->valorNuloProveedorErp($referencia),
                ":archivo_nombre" => $this->valorNuloProveedorErp($archivoNombre),
                ":archivo_tipo" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "archivo_tipo", 120)),
                ":archivo_tamano" => $this->enteroNuloProveedorErp($datos, "archivo_tamano"),
                ":archivo_hash" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "archivo_hash", 128)),
                ":metadatos_json" => $this->valorNuloProveedorErp($metadatos),
                ":vigencia_desde" => $this->fechaProveedorErp($datos, "vigencia_desde"),
                ":vigencia_hasta" => $this->fechaProveedorErp($datos, "vigencia_hasta"),
                ":estatus" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estatus", 40))
            );

            if ($idDocumento > 0) {
                $valores[":id_documento_proveedor"] = $idDocumento;
                $stmt = $db->prepare("UPDATE erp_proveedores_documentos SET
                    tipo_documento = :tipo_documento,
                    nivel_sensibilidad = :nivel_sensibilidad,
                    entidad_origen = :entidad_origen,
                    id_referencia = :id_referencia,
                    referencia_tipo = :referencia_tipo,
                    referencia = :referencia,
                    archivo_nombre = :archivo_nombre,
                    archivo_tipo = :archivo_tipo,
                    archivo_tamano = :archivo_tamano,
                    archivo_hash = :archivo_hash,
                    metadatos_json = :metadatos_json,
                    vigencia_desde = :vigencia_desde,
                    vigencia_hasta = :vigencia_hasta,
                    estatus = :estatus,
                    fecha_actualizacion = NOW()
                    WHERE id_documento_proveedor = :id_documento_proveedor AND id_proveedor = :id_proveedor");
                $stmt->execute($valores);
            } else {
                $valores[":creado_por"] = intval($id_usuario);
                $stmt = $db->prepare("INSERT INTO erp_proveedores_documentos
                    (id_proveedor, tipo_documento, nivel_sensibilidad, entidad_origen, id_referencia, referencia_tipo, referencia, archivo_nombre, archivo_tipo, archivo_tamano, archivo_hash, metadatos_json, vigencia_desde, vigencia_hasta, estatus, creado_por, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :tipo_documento, :nivel_sensibilidad, :entidad_origen, :id_referencia, :referencia_tipo, :referencia, :archivo_nombre, :archivo_tipo, :archivo_tamano, :archivo_hash, :metadatos_json, :vigencia_desde, :vigencia_hasta, :estatus, :creado_por, NOW(), NOW())");
                $stmt->execute($valores);
                $idDocumento = intval($db->lastInsertId());
            }

            $despues = $this->consultarDocumentoProveedorErp($db, $idDocumento, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Documento del proveedor guardado",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_documento_proveedor" => $idDocumento,
                    "es_sensible" => $this->nivelDocumentoProveedorEsSensible(isset($despues["nivel_sensibilidad"]) ? $despues["nivel_sensibilidad"] : ""),
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarListaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $proveedor = $this->consultarProveedorErp($idProveedor, true);
            if ($proveedor["error"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor no encontrado", "depurar" => null);
            }

            $nombreLista = $this->textoProveedorErp($datos, "nombre_lista", 180);
            $versionLista = $this->textoProveedorErp($datos, "version_lista", 80);
            if ($nombreLista === "" && $versionLista === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura nombre o version de lista", "depurar" => null);
            }

            $idDocumento = $this->enteroNuloProveedorErp($datos, "id_documento_proveedor");
            if ($idDocumento !== null && !$this->consultarDocumentoProveedorErp($db, $idDocumento, $idProveedor)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Documento de evidencia no encontrado para este proveedor", "depurar" => null);
            }

            $antes = $idLista > 0 ? $this->consultarListaProveedorErp($db, $idLista, $idProveedor) : null;
            if ($idLista > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $estatus = isset($datos["estatus"]) ? $this->normalizarEstatusListaProveedorErp($datos["estatus"], $idLista > 0 && $antes ? $antes["estatus"] : "borrador") : ($idLista > 0 && $antes ? $this->normalizarEstatusListaProveedorErp($antes["estatus"], "borrador") : "borrador");

            $valores = array(
                ":id_proveedor" => $idProveedor,
                ":id_lista_legacy" => $this->enteroNuloProveedorErp($datos, "id_lista_legacy"),
                ":nombre_lista" => $this->valorNuloProveedorErp($nombreLista),
                ":version_lista" => $this->valorNuloProveedorErp($versionLista),
                ":origen" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "origen", 40)),
                ":moneda" => $this->valorNuloProveedorErp(strtoupper($this->textoProveedorErp($datos, "moneda", 10))),
                ":vigencia_desde" => $this->fechaProveedorErp($datos, "vigencia_desde"),
                ":vigencia_hasta" => $this->fechaProveedorErp($datos, "vigencia_hasta"),
                ":fecha_emision" => $this->fechaProveedorErp($datos, "fecha_emision"),
                ":id_documento_proveedor" => $idDocumento,
                ":estatus" => $estatus,
                ":observaciones" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "observaciones", 5000))
            );

            if ($idLista > 0) {
                $valores[":id_lista_proveedor_erp"] = $idLista;
                $stmt = $db->prepare("UPDATE erp_proveedores_listas_erp SET
                    id_lista_legacy = :id_lista_legacy,
                    nombre_lista = :nombre_lista,
                    version_lista = :version_lista,
                    origen = :origen,
                    moneda = :moneda,
                    vigencia_desde = :vigencia_desde,
                    vigencia_hasta = :vigencia_hasta,
                    fecha_emision = :fecha_emision,
                    id_documento_proveedor = :id_documento_proveedor,
                    estatus = :estatus,
                    observaciones = :observaciones,
                    fecha_actualizacion = NOW()
                    WHERE id_lista_proveedor_erp = :id_lista_proveedor_erp AND id_proveedor = :id_proveedor");
                $stmt->execute($valores);
            } else {
                $valores[":cargado_por"] = intval($id_usuario);
                $stmt = $db->prepare("INSERT INTO erp_proveedores_listas_erp
                    (id_proveedor, id_lista_legacy, nombre_lista, version_lista, origen, moneda, vigencia_desde, vigencia_hasta, fecha_emision, id_documento_proveedor, estatus, observaciones, cargado_por, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :id_lista_legacy, :nombre_lista, :version_lista, :origen, :moneda, :vigencia_desde, :vigencia_hasta, :fecha_emision, :id_documento_proveedor, :estatus, :observaciones, :cargado_por, NOW(), NOW())");
                $stmt->execute($valores);
                $idLista = intval($db->lastInsertId());
            }

            $despues = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Lista ERP del proveedor guardada",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function subirArchivoListaProveedorErp($datos, $archivo, $id_usuario) {
        $db = $this->getConexion();
        $rutaAbsoluta = "";
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor o lista invalida", "depurar" => null);
            }
            $this->validarArchivoProveedorErp($archivo);
            $mime = $this->detectarMimeProveedorErp($archivo["tmp_name"]);
            $this->validarMimeProveedorErp($mime);
            $hash = hash_file("sha256", $archivo["tmp_name"]);
            if (!$hash) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No fue posible verificar el archivo", "depurar" => null);
            }

            $db->beginTransaction();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                throw new Exception("Lista no encontrada para el proveedor");
            }

            $stmtDuplicado = $db->prepare("SELECT id_documento_proveedor
                FROM erp_proveedores_documentos
                WHERE id_proveedor = :id_proveedor
                  AND referencia_tipo = 'erp_proveedores_listas_erp'
                  AND id_referencia = :id_lista
                  AND archivo_hash = :hash
                  AND COALESCE(estatus, 'activo') <> 'cancelado'
                LIMIT 1");
            $stmtDuplicado->execute(array(
                ":id_proveedor" => $idProveedor,
                ":id_lista" => $idLista,
                ":hash" => $hash
            ));
            if ($stmtDuplicado->fetchColumn()) {
                throw new Exception("Este archivo ya esta ligado a la lista");
            }

            $extension = $this->extensionSeguraProveedorErp($archivo["name"], $mime);
            $directorio = $this->directorioListaProveedorErp($idProveedor, $idLista);
            if (!is_dir($directorio) && !mkdir($directorio, 0770, true) && !is_dir($directorio)) {
                throw new Exception("No fue posible preparar el almacenamiento");
            }
            $nombreInterno = bin2hex(random_bytes(20)) . ($extension ? "." . $extension : "");
            $rutaAbsoluta = $directorio . DIRECTORY_SEPARATOR . $nombreInterno;
            if (!move_uploaded_file($archivo["tmp_name"], $rutaAbsoluta)) {
                throw new Exception("No fue posible guardar el archivo");
            }

            $rutaRelativa = "storage/erp/proveedores/" . $idProveedor . "/listas/" . $idLista . "/" . $nombreInterno;
            $metadatos = array(
                "origen" => "archivo_original_lista",
                "nombre_lista" => isset($lista["nombre_lista"]) ? $lista["nombre_lista"] : null,
                "version_lista" => isset($lista["version_lista"]) ? $lista["version_lista"] : null,
                "pendiente_vista_previa" => true,
                "pendiente_mapeo_columnas" => true
            );
            $stmt = $db->prepare("INSERT INTO erp_proveedores_documentos
                (id_proveedor, tipo_documento, nivel_sensibilidad, entidad_origen, id_referencia, referencia_tipo, referencia,
                 archivo_nombre, archivo_ruta, archivo_tipo, archivo_tamano, archivo_hash, metadatos_json,
                 vigencia_desde, vigencia_hasta, estatus, creado_por, fecha_registro, fecha_actualizacion)
                VALUES
                (:id_proveedor, 'lista_proveedor_original', 'operativo', 'proveedores', :id_lista, 'erp_proveedores_listas_erp', :referencia,
                 :archivo_nombre, :archivo_ruta, :archivo_tipo, :archivo_tamano, :archivo_hash, :metadatos_json,
                 :vigencia_desde, :vigencia_hasta, 'activo', :creado_por, NOW(), NOW())");
            $stmt->execute(array(
                ":id_proveedor" => $idProveedor,
                ":id_lista" => $idLista,
                ":referencia" => $this->textoCortoProveedorMigracion(isset($lista["nombre_lista"]) ? $lista["nombre_lista"] : "Lista proveedor", 180),
                ":archivo_nombre" => substr(basename($archivo["name"]), 0, 255),
                ":archivo_ruta" => $rutaRelativa,
                ":archivo_tipo" => $mime,
                ":archivo_tamano" => intval($archivo["size"]),
                ":archivo_hash" => $hash,
                ":metadatos_json" => json_encode($metadatos, JSON_UNESCAPED_UNICODE),
                ":vigencia_desde" => isset($lista["vigencia_desde"]) ? $lista["vigencia_desde"] : null,
                ":vigencia_hasta" => isset($lista["vigencia_hasta"]) ? $lista["vigencia_hasta"] : null,
                ":creado_por" => intval($id_usuario) ?: null
            ));
            $idDocumento = intval($db->lastInsertId());

            $stmtLista = $db->prepare("UPDATE erp_proveedores_listas_erp
                SET id_documento_proveedor = :id_documento,
                    fecha_actualizacion = NOW()
                WHERE id_lista_proveedor_erp = :id_lista
                  AND id_proveedor = :id_proveedor");
            $stmtLista->execute(array(
                ":id_documento" => $idDocumento,
                ":id_lista" => $idLista,
                ":id_proveedor" => $idProveedor
            ));

            $stmtLista = $db->prepare("UPDATE erp_proveedores_listas_erp
                SET estatus = CASE
                        WHEN estatus IS NULL OR estatus = '' OR estatus IN ('borrador', 'en_validacion') THEN 'cargada'
                        ELSE estatus
                    END,
                    fecha_actualizacion = NOW()
                WHERE id_lista_proveedor_erp = :id_lista
                  AND id_proveedor = :id_proveedor");
            $stmtLista->execute(array(":id_lista" => $idLista, ":id_proveedor" => $idProveedor));
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Archivo original de lista guardado como evidencia",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_documento_proveedor" => $idDocumento,
                    "archivo_nombre" => basename($archivo["name"]),
                    "archivo_tipo" => $mime,
                    "archivo_tamano" => intval($archivo["size"]),
                    "siguiente" => "vista_previa_mapeo_columnas"
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ($rutaAbsoluta && is_file($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function previewArchivoListaProveedorErp($id_proveedor, $id_lista_proveedor_erp, $limite_preview = 200) {
        try {
            $db = $this->getConexion();
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            $limitePreview = $this->limiteFilasPreviewListaProveedorErp($limite_preview);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor o lista invalida", "depurar" => null);
            }
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista no encontrada para el proveedor", "depurar" => null);
            }
            $idDocumento = intval(isset($lista["id_documento_proveedor"]) ? $lista["id_documento_proveedor"] : 0);
            if ($idDocumento <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "La lista no tiene archivo original ligado", "depurar" => null);
            }
            $documento = $this->consultarDocumentoProveedorErp($db, $idDocumento, $idProveedor);
            if (!$documento || empty($documento["archivo_ruta"])) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Documento de lista no disponible", "depurar" => null);
            }
            $ruta = $this->resolverRutaArchivoProveedorErp($documento["archivo_ruta"]);
            if ($ruta === "" || !is_file($ruta)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "El archivo fisico no esta disponible", "depurar" => null);
            }

            $mime = isset($documento["archivo_tipo"]) ? (string) $documento["archivo_tipo"] : "";
            $extension = strtolower(pathinfo(isset($documento["archivo_nombre"]) ? $documento["archivo_nombre"] : $ruta, PATHINFO_EXTENSION));
            $preview = $this->leerPreviewArchivoListaProveedorErp($ruta, $mime, $extension, $limitePreview);
            $mapeo = $this->sugerirMapeoColumnasListaProveedorErp($preview["encabezados"]);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Vista previa de lista consultada",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_documento_proveedor" => $idDocumento,
                    "archivo" => array(
                        "nombre" => isset($documento["archivo_nombre"]) ? $documento["archivo_nombre"] : null,
                        "tipo" => $mime,
                        "tamano" => isset($documento["archivo_tamano"]) ? intval($documento["archivo_tamano"]) : null
                    ),
                    "preview" => $preview,
                    "mapeo_sugerido" => $mapeo,
                    "limites" => array(
                        "Vista previa limitada a " . $limitePreview . " renglones para no saturar el navegador.",
                        "Solo muestra filas.",
                        "No importa renglones.",
                        "No aplica costos ni relaciones.",
                        "PDF/ZIP se conservan como evidencia; no se parsean en esta etapa."
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function importarArchivoListaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $mapeoJson = isset($datos["mapeo_json"]) ? (string) $datos["mapeo_json"] : "";
            $mapeo = json_decode($mapeoJson, true);
            if ($idProveedor <= 0 || $idLista <= 0 || !is_array($mapeo)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Datos de importacion invalidos", "depurar" => null);
            }
            if (!$this->mapeoListaTieneIdentidadProveedorErp($mapeo)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Mapea al menos descripcion o un identificador del producto", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista || intval(isset($lista["id_documento_proveedor"]) ? $lista["id_documento_proveedor"] : 0) <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista o archivo original no disponible", "depurar" => null);
            }
            $existentes = $this->contarProveedorErp($db, "erp_proveedores_listas_detalle_erp", "id_lista_proveedor_erp = :id_lista", array(":id_lista" => $idLista));

            $documento = $this->consultarDocumentoProveedorErp($db, intval($lista["id_documento_proveedor"]), $idProveedor);
            $ruta = $documento ? $this->resolverRutaArchivoProveedorErp($documento["archivo_ruta"]) : "";
            if ($ruta === "" || !is_file($ruta)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "El archivo fisico no esta disponible", "depurar" => null);
            }

            $extension = strtolower(pathinfo(isset($documento["archivo_nombre"]) ? $documento["archivo_nombre"] : $ruta, PATHINFO_EXTENSION));
            $lectura = $this->leerFilasImportacionListaProveedorErp($ruta, isset($documento["archivo_tipo"]) ? $documento["archivo_tipo"] : "", $extension, 50000);
            if (!$lectura["parseable"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Este archivo no se puede importar automaticamente", "depurar" => $lectura);
            }

            $db->beginTransaction();
            $clavesExistentes = $this->cargarClavesExistentesListaProveedorErp($db, $idLista);
            $insert = $db->prepare("INSERT INTO erp_proveedores_listas_detalle_erp
                (id_lista_proveedor_erp, sku_proveedor, codigo_barras, codigo_interno, marca_proveedor, descripcion_proveedor,
                 unidad_compra_texto, factor_conversion, costo, moneda, costo_incluye_impuestos, existencia_reportada,
                 estado_match, criterio_match, observaciones, fecha_registro, fecha_actualizacion)
                VALUES
                (:id_lista, :sku_proveedor, :codigo_barras, :codigo_interno, :marca_proveedor, :descripcion_proveedor,
                 :unidad_compra_texto, :factor_conversion, :costo, :moneda, :costo_incluye_impuestos, :existencia_reportada,
                 'sin_match', 'importacion_lista_proveedor', :observaciones, NOW(), NOW())");
            $conteos = array("leidos" => 0, "importados" => 0, "actualizados" => 0, "omitidos_vacios" => 0, "omitidos_notas" => 0, "omitidos_existentes" => 0, "sin_cambios" => 0, "sin_costo" => 0, "sin_moneda" => 0);
            foreach ($lectura["filas"] as $fila) {
                $conteos["leidos"]++;
                $normalizado = $this->normalizarFilaImportacionListaProveedorErp($fila, $mapeo, isset($lista["moneda"]) ? $lista["moneda"] : "");
                if (!$this->filaImportacionTieneContenidoProveedorErp($normalizado)) {
                    $conteos["omitidos_vacios"]++;
                    continue;
                }
                if ($this->filaImportacionEsNotaProveedorErp($normalizado)) {
                    $conteos["omitidos_notas"]++;
                    continue;
                }
                $clavesFila = $this->clavesUnicasFilaListaProveedorErp($normalizado);
                $renglonExistente = null;
                foreach ($clavesFila as $claveFila) {
                    if (isset($clavesExistentes[$claveFila])) {
                        $renglonExistente = $clavesExistentes[$claveFila];
                        break;
                    }
                }
                if ($renglonExistente) {
                    $cambios = $this->cambiosImportacionRenglonExistenteProveedorErp($renglonExistente, $normalizado);
                    if (empty($cambios)) {
                        $conteos["omitidos_existentes"]++;
                        $conteos["sin_cambios"]++;
                        continue;
                    }
                    $sets = array();
                    $paramsUpdate = array(
                        ":id_detalle" => intval($renglonExistente["id_lista_detalle_erp"]),
                        ":id_lista" => $idLista
                    );
                    foreach ($cambios as $campoCambio => $valorCambio) {
                        $paramCambio = ":" . $campoCambio;
                        $sets[] = $campoCambio . " = " . $paramCambio;
                        $paramsUpdate[$paramCambio] = $valorCambio;
                    }
                    $stmtUpdateExistente = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp
                        SET " . implode(", ", $sets) . ",
                            observaciones = CONCAT(COALESCE(observaciones, ''), CASE WHEN COALESCE(observaciones, '') = '' THEN '' ELSE '\n' END, 'Datos actualizados desde recarga de lista.'),
                            fecha_actualizacion = NOW()
                        WHERE id_lista_detalle_erp = :id_detalle
                          AND id_lista_proveedor_erp = :id_lista");
                    $stmtUpdateExistente->execute($paramsUpdate);
                    $actualizado = $this->consultarListaDetalleProveedorErp($db, intval($renglonExistente["id_lista_detalle_erp"]), $idLista, $idProveedor);
                    if ($actualizado) {
                        foreach ($this->clavesUnicasFilaListaProveedorErp($actualizado) as $claveActualizada) {
                            $clavesExistentes[$claveActualizada] = $actualizado;
                        }
                    }
                    $conteos["actualizados"]++;
                    continue;
                }
                if (floatval(isset($normalizado["costo"]) ? $normalizado["costo"] : 0) <= 0) {
                    $conteos["sin_costo"]++;
                }
                if (trim((string) (isset($normalizado["moneda"]) ? $normalizado["moneda"] : "")) === "") {
                    $conteos["sin_moneda"]++;
                }
                $insert->execute(array(
                    ":id_lista" => $idLista,
                    ":sku_proveedor" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["sku_proveedor"], 120)),
                    ":codigo_barras" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["codigo_barras"], 120)),
                    ":codigo_interno" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["codigo_interno"], 120)),
                    ":marca_proveedor" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["marca_proveedor"], 160)),
                    ":descripcion_proveedor" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["descripcion_proveedor"], 5000)),
                    ":unidad_compra_texto" => $this->valorNuloProveedorErp($this->limitarTextoProveedorErp($normalizado["unidad_compra_texto"], 80)),
                    ":factor_conversion" => $this->decimalValorNuloProveedorErp($normalizado["factor_conversion"]),
                    ":costo" => $this->decimalValorNuloProveedorErp($normalizado["costo"]),
                    ":moneda" => $this->valorNuloProveedorErp(strtoupper($this->limitarTextoProveedorErp($normalizado["moneda"], 10))),
                    ":costo_incluye_impuestos" => $this->booleanoImportacionProveedorErp($normalizado["costo_incluye_impuestos"]),
                    ":existencia_reportada" => $this->decimalValorNuloProveedorErp($normalizado["existencia_reportada"]),
                    ":observaciones" => "Importado desde archivo original de lista. Pendiente matching y validacion."
                ));
                $nuevoRenglon = $this->consultarListaDetalleProveedorErp($db, intval($db->lastInsertId()), $idLista, $idProveedor);
                foreach ($clavesFila as $claveFila) {
                    $clavesExistentes[$claveFila] = $nuevoRenglon ? $nuevoRenglon : array(
                        "id_lista_detalle_erp" => 0,
                        "sku_proveedor" => $normalizado["sku_proveedor"],
                        "codigo_barras" => $normalizado["codigo_barras"],
                        "codigo_interno" => $normalizado["codigo_interno"],
                        "descripcion_proveedor" => $normalizado["descripcion_proveedor"]
                    );
                }
                $conteos["importados"]++;
            }
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => $existentes > 0 ? "Archivo revisado; se anexaron nuevos y se actualizaron existentes con cambios" : "Renglones importados en borrador",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "renglones_existentes_antes" => $existentes,
                    "conteos" => $conteos,
                    "reglas" => array(
                        "Estado match inicial: sin_match.",
                        "Si la lista ya tenia detalle, se omitieron renglones existentes sin cambios.",
                        "Si un renglon existente trae costo, moneda, impuestos, unidad o existencia distinta, se actualiza el detalle de lista.",
                        "No se aplicaron relaciones proveedor-SKU.",
                        "No se aplicaron costos vigentes.",
                        "No se actualizo costo_referencia."
                    )
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function eliminarListaDetalleErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0 || $idDetalle <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon invalido", "depurar" => null);
            }
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }
            $antes = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            if (!$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista no encontrado", "depurar" => null);
            }
            if (intval(isset($antes["id_sku_proveedor"]) ? $antes["id_sku_proveedor"] : 0) > 0 || in_array((string) $antes["estado_match"], array("relacion_aplicada", "costo_aplicado"), true)) {
                return array(
                    "error" => true,
                    "tipo" => "warning",
                    "mensaje" => "Este renglon ya tiene relacion aplicada; edita o cancela el flujo relacionado antes de eliminarlo",
                    "depurar" => array("id_lista_detalle_erp" => $idDetalle, "antes" => $antes)
                );
            }

            $stmtCostos = $db->prepare("SELECT COUNT(*) FROM erp_proveedores_sku_costos WHERE id_lista_detalle_erp = :id_detalle");
            $stmtCostos->execute(array(":id_detalle" => $idDetalle));
            if (intval($stmtCostos->fetchColumn()) > 0) {
                return array(
                    "error" => true,
                    "tipo" => "warning",
                    "mensaje" => "Este renglon ya tiene costos ligados; no se puede eliminar desde limpieza de lista",
                    "depurar" => array("id_lista_detalle_erp" => $idDetalle, "antes" => $antes)
                );
            }

            $db->beginTransaction();
            $stmt = $db->prepare("DELETE FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_detalle_erp = :id_detalle
                  AND id_lista_proveedor_erp = :id_lista");
            $stmt->execute(array(":id_detalle" => $idDetalle, ":id_lista" => $idLista));
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Renglon eliminado de la lista",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "antes" => $antes,
                    "eliminado_por" => intval($id_usuario) ?: null
                )
            );
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function cambiarEstatusListaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $estatus = $this->normalizarEstatusListaProveedorErp(isset($datos["estatus"]) ? $datos["estatus"] : "", "");
            if ($idProveedor <= 0 || $idLista <= 0 || $estatus === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Estatus de lista invalido", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $revision = $this->resumenValidacionListaProveedorErp($db, $idLista);
            if ($estatus === "validada" && !$revision["puede_validar"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "La lista necesita al menos un renglon operativo con identidad, costo y moneda para validarse", "depurar" => array("revision" => $revision));
            }
            if ($estatus === "aplicada" && !$revision["puede_aplicar"]) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "La lista aun no tiene relaciones o costos aplicados", "depurar" => array("revision" => $revision));
            }

            $antes = $lista;
            $camposExtra = "";
            $params = array(
                ":estatus" => $estatus,
                ":id_lista" => $idLista,
                ":id_proveedor" => $idProveedor
            );
            if ($estatus === "validada" || $estatus === "aplicada") {
                $camposExtra = ", validado_por = :validado_por, fecha_validacion = COALESCE(fecha_validacion, NOW())";
                $params[":validado_por"] = intval($id_usuario) ?: null;
            }

            $stmt = $db->prepare("UPDATE erp_proveedores_listas_erp
                SET estatus = :estatus,
                    fecha_actualizacion = NOW()
                    " . $camposExtra . "
                WHERE id_lista_proveedor_erp = :id_lista
                  AND id_proveedor = :id_proveedor");
            $stmt->execute($params);
            $despues = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Estatus de lista actualizado",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "estatus" => $estatus,
                    "revision" => $revision,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Throwable $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function resumenValidacionListaProveedorErp($db, $idLista) {
        $stmt = $db->prepare("SELECT
                COUNT(*) total,
                SUM(CASE WHEN COALESCE(sku_proveedor, '') = '' AND COALESCE(codigo_barras, '') = '' AND COALESCE(codigo_interno, '') = '' AND COALESCE(descripcion_proveedor, '') = '' THEN 1 ELSE 0 END) sin_identidad,
                SUM(CASE WHEN COALESCE(costo, 0) <= 0 THEN 1 ELSE 0 END) sin_costo,
                SUM(CASE WHEN COALESCE(moneda, '') = '' THEN 1 ELSE 0 END) sin_moneda,
                SUM(CASE WHEN COALESCE(id_sku, 0) > 0 OR COALESCE(id_sku_proveedor, 0) > 0 OR estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado') THEN 1 ELSE 0 END) operativos,
                SUM(CASE WHEN (COALESCE(id_sku, 0) > 0 OR COALESCE(id_sku_proveedor, 0) > 0 OR estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) AND COALESCE(sku_proveedor, '') = '' AND COALESCE(codigo_barras, '') = '' AND COALESCE(codigo_interno, '') = '' AND COALESCE(descripcion_proveedor, '') = '' THEN 1 ELSE 0 END) operativos_sin_identidad,
                SUM(CASE WHEN (COALESCE(id_sku, 0) > 0 OR COALESCE(id_sku_proveedor, 0) > 0 OR estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) AND COALESCE(costo, 0) <= 0 THEN 1 ELSE 0 END) operativos_sin_costo,
                SUM(CASE WHEN (COALESCE(id_sku, 0) > 0 OR COALESCE(id_sku_proveedor, 0) > 0 OR estado_match IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) AND COALESCE(moneda, '') = '' THEN 1 ELSE 0 END) operativos_sin_moneda,
                SUM(CASE WHEN NOT (COALESCE(id_sku, 0) > 0 OR COALESCE(id_sku_proveedor, 0) > 0 OR COALESCE(estado_match, '') IN ('match_seleccionado','relacionado','relacion_aplicada','costo_aplicado')) THEN 1 ELSE 0 END) sin_match_informativo,
                SUM(CASE WHEN COALESCE(id_sku_proveedor, 0) > 0 OR estado_match IN ('relacion_aplicada', 'costo_aplicado') THEN 1 ELSE 0 END) relaciones_aplicadas
            FROM erp_proveedores_listas_detalle_erp
            WHERE id_lista_proveedor_erp = :id_lista");
        $stmt->execute(array(":id_lista" => intval($idLista)));
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$resumen) {
            $resumen = array("total" => 0, "sin_identidad" => 0, "sin_costo" => 0, "sin_moneda" => 0, "operativos" => 0, "operativos_sin_identidad" => 0, "operativos_sin_costo" => 0, "operativos_sin_moneda" => 0, "sin_match_informativo" => 0, "relaciones_aplicadas" => 0);
        }
        $stmtCostos = $db->prepare("SELECT COUNT(*) FROM erp_proveedores_sku_costos WHERE id_lista_proveedor_erp = :id_lista");
        $stmtCostos->execute(array(":id_lista" => intval($idLista)));
        $costos = intval($stmtCostos->fetchColumn());
        $total = intval($resumen["total"]);
        $sinIdentidad = intval($resumen["sin_identidad"]);
        $sinCosto = intval($resumen["sin_costo"]);
        $sinMoneda = intval($resumen["sin_moneda"]);
        $operativos = intval($resumen["operativos"]);
        $operativosSinIdentidad = intval($resumen["operativos_sin_identidad"]);
        $operativosSinCosto = intval($resumen["operativos_sin_costo"]);
        $operativosSinMoneda = intval($resumen["operativos_sin_moneda"]);
        $sinMatchInformativo = intval($resumen["sin_match_informativo"]);
        $relaciones = intval($resumen["relaciones_aplicadas"]);
        return array(
            "total" => $total,
            "sin_identidad" => $sinIdentidad,
            "sin_costo" => $sinCosto,
            "sin_moneda" => $sinMoneda,
            "operativos" => $operativos,
            "operativos_sin_identidad" => $operativosSinIdentidad,
            "operativos_sin_costo" => $operativosSinCosto,
            "operativos_sin_moneda" => $operativosSinMoneda,
            "sin_match_informativo" => $sinMatchInformativo,
            "relaciones_aplicadas" => $relaciones,
            "costos_aplicados" => $costos,
            "puede_validar" => $total > 0 && $operativos > 0 && $operativosSinIdentidad === 0 && $operativosSinCosto === 0 && $operativosSinMoneda === 0,
            "puede_aplicar" => $total > 0 && ($relaciones > 0 || $costos > 0)
        );
    }

    public function consultarListaDetalleErp($id_proveedor, $id_lista_proveedor_erp) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT *
                FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_proveedor_erp = :id_lista
                ORDER BY id_lista_detalle_erp ASC
                LIMIT 500");
            $stmt->execute(array(":id_lista" => $idLista));
            $detalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Detalle de lista ERP consultado",
                "depurar" => array(
                    "lista" => $lista,
                    "detalle" => $detalle,
                    "total" => count($detalle),
                    "revision" => $this->resumenValidacionListaProveedorErp($db, $idLista)
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function catalogosListaDetalleErp() {
        try {
            $db = $this->getConexion();
            $unidades = $db->query("SELECT id_unidad, codigo, nombre, abreviatura, tipo_magnitud, decimales_permitidos
                FROM erp_catalogo_unidades
                WHERE estatus = 'activa'
                ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Catalogos de lista proveedor consultados",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "unidades" => $unidades
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array("unidades" => array()));
        }
    }

    public function buscarSkusErpParaLista($termino) {
        try {
            $termino = trim((string) $termino);
            if (strlen($termino) < 2) {
                return array("error" => false, "tipo" => "success", "mensaje" => "Escribe al menos dos caracteres", "depurar" => array());
            }
            $db = $this->getConexion();
            $stmt = $db->prepare("SELECT
                    s.id_sku,
                    s.id_producto_erp,
                    s.sku,
                    s.nombre,
                    s.estatus,
                    s.id_unidad_base,
                    u.abreviatura AS unidad,
                    u.nombre AS unidad_nombre,
                    cod.codigo AS codigo_principal
                FROM erp_catalogo_skus s
                LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = s.id_unidad_base
                LEFT JOIN erp_catalogo_sku_codigos cod ON cod.id_sku = s.id_sku
                    AND cod.es_principal = 1
                    AND cod.estatus = 'activo'
                WHERE s.estatus <> 'fusionado'
                  AND (
                    s.sku LIKE :termino
                    OR s.nombre LIKE :termino
                    OR cod.codigo LIKE :termino
                  )
                ORDER BY s.estatus = 'activo' DESC, s.nombre ASC, s.id_sku DESC
                LIMIT 25");
            $stmt->execute(array(":termino" => "%" . $termino . "%"));
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "SKUs ERP consultados",
                "depurar" => $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => array());
        }
    }

    public function guardarListaDetalleErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $skuProveedor = $this->textoProveedorErp($datos, "sku_proveedor", 120);
            $codigoBarras = $this->textoProveedorErp($datos, "codigo_barras", 120);
            $codigoInterno = $this->textoProveedorErp($datos, "codigo_interno", 120);
            $descripcion = $this->textoProveedorErp($datos, "descripcion_proveedor", 5000);
            if ($skuProveedor === "" && $codigoBarras === "" && $codigoInterno === "" && $descripcion === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura SKU, codigo o descripcion del renglon", "depurar" => null);
            }

            $antes = $idDetalle > 0 ? $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor) : null;
            if ($idDetalle > 0 && !$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista no encontrado", "depurar" => null);
            }

            $valores = array(
                ":id_lista_proveedor_erp" => $idLista,
                ":id_producto_legacy" => $this->enteroNuloProveedorErp($datos, "id_producto_legacy"),
                ":id_sku" => $this->enteroNuloProveedorErp($datos, "id_sku"),
                ":id_sku_proveedor" => $this->enteroNuloProveedorErp($datos, "id_sku_proveedor"),
                ":sku_proveedor" => $this->valorNuloProveedorErp($skuProveedor),
                ":codigo_barras" => $this->valorNuloProveedorErp($codigoBarras),
                ":codigo_interno" => $this->valorNuloProveedorErp($codigoInterno),
                ":marca_proveedor" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "marca_proveedor", 160)),
                ":descripcion_proveedor" => $this->valorNuloProveedorErp($descripcion),
                ":unidad_compra_texto" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "unidad_compra_texto", 80)),
                ":id_unidad_compra" => $this->enteroNuloProveedorErp($datos, "id_unidad_compra"),
                ":factor_conversion" => $this->decimalNuloProveedorErp($datos, "factor_conversion"),
                ":cantidad_minima" => $this->decimalNuloProveedorErp($datos, "cantidad_minima"),
                ":costo" => $this->decimalNuloProveedorErp($datos, "costo"),
                ":moneda" => $this->valorNuloProveedorErp(strtoupper($this->textoProveedorErp($datos, "moneda", 10))),
                ":costo_incluye_impuestos" => isset($datos["costo_incluye_impuestos"]) && trim((string) $datos["costo_incluye_impuestos"]) !== "" ? intval($datos["costo_incluye_impuestos"]) : null,
                ":existencia_reportada" => $this->decimalNuloProveedorErp($datos, "existencia_reportada"),
                ":estado_match" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "estado_match", 40)),
                ":criterio_match" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "criterio_match", 120)),
                ":observaciones" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "observaciones", 5000))
            );

            if ($idDetalle > 0) {
                $valores[":id_lista_detalle_erp"] = $idDetalle;
                $stmt = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                    id_producto_legacy = :id_producto_legacy,
                    id_sku = :id_sku,
                    id_sku_proveedor = :id_sku_proveedor,
                    sku_proveedor = :sku_proveedor,
                    codigo_barras = :codigo_barras,
                    codigo_interno = :codigo_interno,
                    marca_proveedor = :marca_proveedor,
                    descripcion_proveedor = :descripcion_proveedor,
                    unidad_compra_texto = :unidad_compra_texto,
                    id_unidad_compra = :id_unidad_compra,
                    factor_conversion = :factor_conversion,
                    cantidad_minima = :cantidad_minima,
                    costo = :costo,
                    moneda = :moneda,
                    costo_incluye_impuestos = :costo_incluye_impuestos,
                    existencia_reportada = :existencia_reportada,
                    estado_match = :estado_match,
                    criterio_match = :criterio_match,
                    observaciones = :observaciones,
                    fecha_actualizacion = NOW()
                    WHERE id_lista_detalle_erp = :id_lista_detalle_erp AND id_lista_proveedor_erp = :id_lista_proveedor_erp");
                $stmt->execute($valores);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_proveedores_listas_detalle_erp
                    (id_lista_proveedor_erp, id_producto_legacy, id_sku, id_sku_proveedor, sku_proveedor, codigo_barras, codigo_interno, marca_proveedor, descripcion_proveedor, unidad_compra_texto, id_unidad_compra, factor_conversion, cantidad_minima, costo, moneda, costo_incluye_impuestos, existencia_reportada, estado_match, criterio_match, observaciones, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_lista_proveedor_erp, :id_producto_legacy, :id_sku, :id_sku_proveedor, :sku_proveedor, :codigo_barras, :codigo_interno, :marca_proveedor, :descripcion_proveedor, :unidad_compra_texto, :id_unidad_compra, :factor_conversion, :cantidad_minima, :costo, :moneda, :costo_incluye_impuestos, :existencia_reportada, :estado_match, :criterio_match, :observaciones, NOW(), NOW())");
                $stmt->execute($valores);
                $idDetalle = intval($db->lastInsertId());
            }

            $despues = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Renglon de lista ERP guardado",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: completar datos de compra en varios renglones de lista sin aplicar relacion ni costo.
     * Impacto: Proveedores; prepara renglones para lote de relaciones conservando matching y evidencias.
     * Contrato: actualiza solo IDs enviados de la misma lista/proveedor; puede llenar solo vacios o sobrescribir campos autorizados.
     */
    public function completarCompraListaDetalleLoteErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idsJson = isset($datos["ids_json"]) ? (string) $datos["ids_json"] : "[]";
            $ids = json_decode($idsJson, true);
            if ($idProveedor <= 0 || $idLista <= 0 || !is_array($ids) || empty($ids)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Selecciona renglones validos para completar compra", "depurar" => null);
            }
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $ids = array_values(array_unique(array_filter(array_map("intval", $ids), function ($id) {
                return $id > 0;
            })));
            if (empty($ids) || count($ids) > 1000) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "El lote debe tener entre 1 y 1000 renglones", "depurar" => array("total_ids" => count($ids)));
            }

            $campos = array();
            $params = array(":id_lista" => $idLista);
            $soloVacios = !isset($datos["sobrescribir"]) || intval($datos["sobrescribir"]) !== 1;
            $valoresPermitidos = array(
                "unidad_compra_texto" => array("tipo" => "texto", "max" => 80),
                "id_unidad_compra" => array("tipo" => "entero"),
                "factor_conversion" => array("tipo" => "decimal"),
                "cantidad_minima" => array("tipo" => "decimal"),
                "moneda" => array("tipo" => "moneda"),
                "costo_incluye_impuestos" => array("tipo" => "booleano"),
                "existencia_reportada" => array("tipo" => "decimal")
            );

            foreach ($valoresPermitidos as $campo => $config) {
                if (!array_key_exists($campo, $datos) || trim((string) $datos[$campo]) === "") {
                    continue;
                }
                $param = ":" . $campo;
                if ($config["tipo"] === "texto") {
                    $params[$param] = $this->textoProveedorErp($datos, $campo, $config["max"]);
                } elseif ($config["tipo"] === "entero") {
                    $valor = intval($datos[$campo]);
                    if ($valor <= 0) {
                        continue;
                    }
                    $params[$param] = $valor;
                } elseif ($config["tipo"] === "decimal") {
                    $valor = floatval($datos[$campo]);
                    if ($valor < 0) {
                        continue;
                    }
                    $params[$param] = $valor;
                } elseif ($config["tipo"] === "moneda") {
                    $params[$param] = strtoupper($this->textoProveedorErp($datos, $campo, 10));
                } elseif ($config["tipo"] === "booleano") {
                    $valor = intval($datos[$campo]);
                    if (!in_array($valor, array(0, 1), true)) {
                        continue;
                    }
                    $params[$param] = $valor;
                }

                $condicionVacio = $campo === "id_unidad_compra"
                    ? "(id_unidad_compra IS NULL OR id_unidad_compra <= 0)"
                    : ($campo === "factor_conversion" || $campo === "cantidad_minima" || $campo === "existencia_reportada"
                        ? "(" . $campo . " IS NULL OR " . $campo . " <= 0)"
                        : "(" . $campo . " IS NULL OR TRIM(CAST(" . $campo . " AS CHAR)) = '')");
                $campos[] = $campo . " = " . ($soloVacios ? "CASE WHEN " . $condicionVacio . " THEN " . $param . " ELSE " . $campo . " END" : $param);
            }

            if (empty($campos)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura al menos un dato de compra para aplicar", "depurar" => null);
            }

            $placeholders = array();
            foreach ($ids as $i => $idDetalle) {
                $key = ":id_" . $i;
                $placeholders[] = $key;
                $params[$key] = $idDetalle;
            }

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp
                SET " . implode(", ", $campos) . ",
                    observaciones = CONCAT(COALESCE(observaciones, ''), CASE WHEN COALESCE(observaciones, '') = '' THEN '' ELSE '\n' END, 'Datos de compra completados en lote.'),
                    fecha_actualizacion = NOW()
                WHERE id_lista_proveedor_erp = :id_lista
                  AND id_lista_detalle_erp IN (" . implode(",", $placeholders) . ")");
            $stmt->execute($params);
            $actualizados = $stmt->rowCount();
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Datos de compra completados en lote",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "solicitados" => count($ids),
                    "actualizados" => $actualizados,
                    "solo_campos_vacios" => $soloVacios,
                    "campos" => array_keys($params),
                    "usuario" => intval($id_usuario) ?: null
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function matchingListaDryRunErp($id_proveedor, $id_lista_proveedor_erp) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $detalle = $this->consultarListaDetalleErp($idProveedor, $idLista);
            if ($detalle["error"]) {
                return $detalle;
            }

            $propuestas = array();
            $resumen = array(
                "total" => 0,
                "relacionado" => 0,
                "match_exacto_pendiente" => 0,
                "match_posible" => 0,
                "ambiguo" => 0,
                "sin_match" => 0
            );

            foreach ($detalle["depurar"]["detalle"] as $renglon) {
                $resultado = $this->matchingRenglonListaDryRunErp($db, $idProveedor, $renglon);
                $estado = $resultado["estado_match"];
                if (!isset($resumen[$estado])) {
                    $resumen[$estado] = 0;
                }
                $resumen[$estado]++;
                $resumen["total"]++;
                $propuestas[] = $resultado;
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Matching dry-run consultado",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "lista" => $lista,
                    "resumen" => $resumen,
                    "propuestas" => $propuestas
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function guardarDecisionMatchingListaErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0 || $idDetalle <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Decision de matching invalida", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $antes = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            if (!$antes) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista no encontrado", "depurar" => null);
            }

            $estado = $this->textoProveedorErp($datos, "estado_match", 40);
            if ($estado === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura estado de matching", "depurar" => null);
            }
            if (!in_array($estado, array("match_seleccionado", "ambiguo", "sin_match", "rechazado"), true)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Estado de matching no permitido", "depurar" => array("estado_match" => $estado));
            }

            $idSku = $this->enteroNuloProveedorErp($datos, "id_sku");
            $idSkuProveedor = $this->enteroNuloProveedorErp($datos, "id_sku_proveedor");
            if ($idSku !== null && !$this->skuErpExiste($db, $idSku)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "SKU ERP no encontrado", "depurar" => null);
            }
            if ($idSkuProveedor !== null && !$this->skuProveedorExisteParaProveedor($db, $idSkuProveedor, $idProveedor)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Relacion SKU proveedor no encontrada para este proveedor", "depurar" => null);
            }

            $observaciones = $this->textoProveedorErp($datos, "observaciones", 5000);
            if (in_array($estado, array("ambiguo", "sin_match", "rechazado"), true) && trim($observaciones) === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura motivo para esta decision de matching", "depurar" => array("estado_match" => $estado));
            }
            $observacionesPrevias = isset($antes["observaciones"]) ? trim((string) $antes["observaciones"]) : "";
            $observacionesFinales = $observaciones;
            if ($observacionesPrevias !== "" && $observaciones !== "" && $observacionesPrevias !== $observaciones) {
                $observacionesFinales = $observacionesPrevias . "\n" . $observaciones;
            } elseif ($observaciones === "") {
                $observacionesFinales = $observacionesPrevias;
            }

            $stmt = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                id_sku = :id_sku,
                id_sku_proveedor = :id_sku_proveedor,
                estado_match = :estado_match,
                criterio_match = :criterio_match,
                observaciones = :observaciones,
                fecha_actualizacion = NOW()
                WHERE id_lista_detalle_erp = :id_detalle AND id_lista_proveedor_erp = :id_lista");
            $stmt->execute(array(
                ":id_sku" => $idSku,
                ":id_sku_proveedor" => $idSkuProveedor,
                ":estado_match" => $estado,
                ":criterio_match" => $this->valorNuloProveedorErp($this->textoProveedorErp($datos, "criterio_match", 120)),
                ":observaciones" => $this->valorNuloProveedorErp($observacionesFinales),
                ":id_detalle" => $idDetalle,
                ":id_lista" => $idLista
            ));

            $despues = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Decision de matching guardada",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-16
     * Proposito: guardar decisiones de matching en lote solo para candidatos confiables recalculados en servidor.
     * Impacto: Proveedores ERP; agiliza listas grandes sin crear relaciones proveedor-SKU ni tocar costos.
     * Contrato: solo acepta un candidato para `relacionado` o `match_exacto_pendiente`; excluye ambiguos, posibles por nombre y renglones ya seleccionados/aplicados.
     */
    public function seleccionarMatchingMasivoListaErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida para matching masivo", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $detalle = $this->consultarListaDetalleErp($idProveedor, $idLista);
            if ($detalle["error"]) {
                return $detalle;
            }

            $aplicables = array();
            $excluidos = array();
            foreach ($detalle["depurar"]["detalle"] as $renglon) {
                $resultado = $this->matchingRenglonListaDryRunErp($db, $idProveedor, $renglon);
                $aplicabilidad = $this->evaluarMatchingMasivoAplicableErp($renglon, $resultado);
                if ($aplicabilidad["aplicable"]) {
                    $aplicables[] = array("renglon" => $renglon, "resultado" => $resultado, "candidato" => $aplicabilidad["candidato"]);
                } else {
                    $excluidos[] = $aplicabilidad["excluido"];
                }
            }

            if (empty($aplicables)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No hay matches confiables para seleccionar en lote", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "excluidos" => $excluidos
                ));
            }

            $db->beginTransaction();
            $stmt = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                id_sku = :id_sku,
                id_sku_proveedor = :id_sku_proveedor,
                estado_match = 'match_seleccionado',
                criterio_match = :criterio_match,
                observaciones = :observaciones,
                fecha_actualizacion = NOW()
                WHERE id_lista_detalle_erp = :id_detalle
                  AND id_lista_proveedor_erp = :id_lista");

            $seleccionados = array();
            foreach ($aplicables as $item) {
                $renglon = $item["renglon"];
                $resultado = $item["resultado"];
                $candidato = $item["candidato"];
                $observacionesPrevias = isset($renglon["observaciones"]) ? trim((string) $renglon["observaciones"]) : "";
                $observacion = "Matching masivo: candidato confiable por " . (isset($resultado["criterio_match"]) ? $resultado["criterio_match"] : "criterio seguro");
                $observacionesFinales = $observacionesPrevias !== "" ? $observacionesPrevias . "\n" . $observacion : $observacion;
                $stmt->execute(array(
                    ":id_sku" => intval($candidato["id_sku"]),
                    ":id_sku_proveedor" => intval(isset($candidato["id_sku_proveedor"]) ? $candidato["id_sku_proveedor"] : 0) > 0 ? intval($candidato["id_sku_proveedor"]) : null,
                    ":criterio_match" => $this->valorNuloProveedorErp(isset($resultado["criterio_match"]) ? $resultado["criterio_match"] : ""),
                    ":observaciones" => $this->valorNuloProveedorErp($observacionesFinales),
                    ":id_detalle" => intval($resultado["id_lista_detalle_erp"]),
                    ":id_lista" => $idLista
                ));
                $seleccionados[] = array(
                    "id_lista_detalle_erp" => intval($resultado["id_lista_detalle_erp"]),
                    "id_sku" => intval($candidato["id_sku"]),
                    "id_sku_proveedor" => intval(isset($candidato["id_sku_proveedor"]) ? $candidato["id_sku_proveedor"] : 0) ?: null,
                    "criterio_match" => isset($resultado["criterio_match"]) ? $resultado["criterio_match"] : ""
                );
            }
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Matching masivo seleccionado para " . count($seleccionados) . " renglon(es)",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "seleccionados" => count($seleccionados),
                    "excluidos" => count($excluidos),
                    "renglones" => $seleccionados,
                    "excluidos_detalle" => $excluidos
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-16
     * Proposito: clasificar si una propuesta de matching puede seleccionarse de forma masiva sin revision humana.
     * Impacto: protege Proveedores/Catalogo evitando que matches ambiguos o posibles por nombre se guarden automaticamente.
     * Contrato: devuelve `aplicable` con candidato unico o `excluido` con motivo auditable.
     */
    private function evaluarMatchingMasivoAplicableErp($renglon, $resultado) {
        $idDetalle = intval(isset($resultado["id_lista_detalle_erp"]) ? $resultado["id_lista_detalle_erp"] : 0);
        $estadoActual = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        $estado = strtolower(trim((string) (isset($resultado["estado_match"]) ? $resultado["estado_match"] : "")));
        $criterio = strtolower(trim((string) (isset($resultado["criterio_match"]) ? $resultado["criterio_match"] : "")));
        $candidatos = isset($resultado["candidatos"]) && is_array($resultado["candidatos"]) ? $resultado["candidatos"] : array();
        $base = array(
            "id_lista_detalle_erp" => $idDetalle,
            "estado_match" => $estado,
            "criterio_match" => $criterio
        );

        if (in_array($estadoActual, array("match_seleccionado", "relacion_aplicada", "costo_aplicado"), true)) {
            return array("aplicable" => false, "excluido" => $base + array("motivo" => "ya_seleccionado_o_aplicado"));
        }
        if (count($candidatos) !== 1) {
            return array("aplicable" => false, "excluido" => $base + array("motivo" => "sin_candidato_unico"));
        }
        if (!in_array($estado, array("relacionado", "match_exacto_pendiente"), true)) {
            return array("aplicable" => false, "excluido" => $base + array("motivo" => "estado_no_masivo"));
        }
        if (!in_array($criterio, array("relacion_activa_proveedor_sku", "incidencia_catalogo_sku_temporal", "sku_o_codigo_exacto"), true)) {
            return array("aplicable" => false, "excluido" => $base + array("motivo" => "criterio_no_masivo"));
        }

        $candidato = $candidatos[0];
        if (intval(isset($candidato["id_sku"]) ? $candidato["id_sku"] : 0) <= 0) {
            return array("aplicable" => false, "excluido" => $base + array("motivo" => "sku_invalido"));
        }
        return array("aplicable" => true, "candidato" => $candidato);
    }

    private function skuErpExiste($db, $idSku) {
        $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE id_sku = :id_sku AND estatus <> 'fusionado' LIMIT 1");
        $stmt->execute(array(":id_sku" => intval($idSku)));
        return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    }

    private function skuProveedorExisteParaProveedor($db, $idSkuProveedor, $idProveedor) {
        $stmt = $db->prepare("SELECT id_sku_proveedor FROM erp_catalogo_sku_proveedores WHERE id_sku_proveedor = :id_sku_proveedor AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_sku_proveedor" => intval($idSkuProveedor),
            ":id_proveedor" => intval($idProveedor)
        ));
        return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    }

    private function consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor) {
        if (intval($idSkuProveedor) > 0) {
            $stmt = $db->prepare("SELECT * FROM erp_catalogo_sku_proveedores WHERE id_sku_proveedor = :id_sku_proveedor AND id_proveedor = :id_proveedor LIMIT 1");
            $stmt->execute(array(
                ":id_sku_proveedor" => intval($idSkuProveedor),
                ":id_proveedor" => intval($idProveedor)
            ));
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fila) {
                return $fila;
            }
        }

        $stmt = $db->prepare("SELECT * FROM erp_catalogo_sku_proveedores WHERE id_sku = :id_sku AND id_proveedor = :id_proveedor LIMIT 1");
        $stmt->execute(array(
            ":id_sku" => intval($idSku),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarCostoProveedorSkuPorDetalleErp($db, $idDetalle, $idProveedor) {
        $stmt = $db->prepare("SELECT * FROM erp_proveedores_sku_costos
            WHERE id_lista_detalle_erp = :id_detalle AND id_proveedor = :id_proveedor
            ORDER BY id_costo_proveedor_sku DESC
            LIMIT 1");
        $stmt->execute(array(
            ":id_detalle" => intval($idDetalle),
            ":id_proveedor" => intval($idProveedor)
        ));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    public function aplicarRelacionSkuProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0 || $idDetalle <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista invalido", "depurar" => null);
            }

            $renglon = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            if (!$renglon) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista no encontrado", "depurar" => null);
            }

            $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
            if ($idSku <= 0 || !$this->skuErpExiste($db, $idSku)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Selecciona un SKU ERP valido antes de aplicar la relacion", "depurar" => null);
            }

            $idUnidad = intval(isset($renglon["id_unidad_compra"]) ? $renglon["id_unidad_compra"] : 0);
            $factor = floatval(isset($renglon["factor_conversion"]) ? $renglon["factor_conversion"] : 0);
            $cantidadMinima = floatval(isset($renglon["cantidad_minima"]) ? $renglon["cantidad_minima"] : 0);
            if ($idUnidad <= 0 || $factor <= 0 || $cantidadMinima <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Completa unidad, factor y cantidad minima antes de aplicar la relacion", "depurar" => null);
            }

            $idSkuProveedor = intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0);
            $antes = $this->consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor);
            if ($antes && intval($antes["id_sku"]) !== $idSku) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "La relacion SKU-proveedor seleccionada pertenece a otro SKU ERP", "depurar" => array(
                    "id_sku_renglon" => $idSku,
                    "id_sku_relacion" => intval($antes["id_sku"]),
                    "id_sku_proveedor" => intval($antes["id_sku_proveedor"])
                ));
            }
            $skuProveedor = $this->valorNuloProveedorErp($this->textoProveedorErp($renglon, "sku_proveedor", 120));

            $db->beginTransaction();
            if ($antes) {
                $stmt = $db->prepare("UPDATE erp_catalogo_sku_proveedores SET
                    sku_proveedor = :sku_proveedor,
                    id_unidad_compra = :id_unidad_compra,
                    factor_conversion = :factor_conversion,
                    cantidad_minima = :cantidad_minima,
                    estatus = 'activo',
                    fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id_sku_proveedor = :id_sku_proveedor");
                $stmt->execute(array(
                    ":sku_proveedor" => $skuProveedor,
                    ":id_unidad_compra" => $idUnidad,
                    ":factor_conversion" => $factor,
                    ":cantidad_minima" => $cantidadMinima,
                    ":id_sku_proveedor" => intval($antes["id_sku_proveedor"])
                ));
                $idSkuProveedor = intval($antes["id_sku_proveedor"]);
            } else {
                $stmt = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
                    (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
                    VALUES
                    (:id_sku, :id_proveedor, :sku_proveedor, :id_unidad_compra, :factor_conversion, 0, :cantidad_minima, 0, 0, 'activo')");
                $stmt->execute(array(
                    ":id_sku" => $idSku,
                    ":id_proveedor" => $idProveedor,
                    ":sku_proveedor" => $skuProveedor,
                    ":id_unidad_compra" => $idUnidad,
                    ":factor_conversion" => $factor,
                    ":cantidad_minima" => $cantidadMinima
                ));
                $idSkuProveedor = intval($db->lastInsertId());
            }

            $stmtDetalle = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                id_sku = :id_sku,
                id_sku_proveedor = :id_sku_proveedor,
                estado_match = 'relacion_aplicada',
                criterio_match = 'relacion_sku_proveedor_aplicada',
                fecha_actualizacion = NOW()
                WHERE id_lista_detalle_erp = :id_detalle AND id_lista_proveedor_erp = :id_lista");
            $stmtDetalle->execute(array(
                ":id_sku" => $idSku,
                ":id_sku_proveedor" => $idSkuProveedor,
                ":id_detalle" => $idDetalle,
                ":id_lista" => $idLista
            ));

            $notificacionesResueltas = $this->resolverNotificacionMatchingCatalogoProveedor($db, $idProveedor, $idLista, $idDetalle, $idSku);
            $incidenciasResueltas = $this->resolverIncidenciaCatalogoProveedorPorRelacion($db, $idProveedor, $idLista, $idDetalle, $idSku, $idSkuProveedor, $id_usuario);
            $despues = $this->consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor);
            $despuesRenglon = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Relacion SKU-proveedor aplicada",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_sku" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "notificaciones_resueltas" => $notificacionesResueltas,
                    "incidencias_catalogo_resueltas" => $incidenciasResueltas,
                    "antes" => $antes,
                    "despues" => $despues,
                    "renglon_despues" => $despuesRenglon
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function previewRelacionesSkuProveedorLoteErp($id_proveedor, $id_lista_proveedor_erp) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT *
                FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_proveedor_erp = :id_lista
                ORDER BY id_lista_detalle_erp ASC
                LIMIT 1000");
            $stmt->execute(array(":id_lista" => $idLista));
            $renglones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $incluidos = array();
            $excluidos = array();
            $resumen = array(
                "total" => count($renglones),
                "incluidos" => 0,
                "actualizar_relacion" => 0,
                "crear_relacion" => 0,
                "excluidos" => 0,
                "sin_sku" => 0,
                "sku_invalido" => 0,
                "estado_no_seleccionado" => 0,
                "compra_incompleta" => 0,
                "ya_relacionado" => 0,
                "relacion_conflictiva" => 0
            );

            foreach ($renglones as $renglon) {
                $evaluacion = $this->evaluarRenglonRelacionSkuProveedorPreviewErp($db, $renglon, $idProveedor);
                if ($evaluacion["aplicable"]) {
                    $incluidos[] = $evaluacion["item"];
                    $resumen["incluidos"]++;
                    $resumen[$evaluacion["accion"] === "actualizar" ? "actualizar_relacion" : "crear_relacion"]++;
                } else {
                    $excluidos[] = $evaluacion["item"];
                    $resumen["excluidos"]++;
                    if (isset($resumen[$evaluacion["motivo"]])) {
                        $resumen[$evaluacion["motivo"]]++;
                    }
                }
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Vista previa de relaciones calculada",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "lista" => $lista,
                    "resumen" => $resumen,
                    "incluidos" => $incluidos,
                    "excluidos" => $excluidos
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function evaluarRenglonRelacionSkuProveedorPreviewErp($db, $renglon, $idProveedor) {
        $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
        $idSkuProveedor = intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0);
        $estado = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        $idUnidad = intval(isset($renglon["id_unidad_compra"]) ? $renglon["id_unidad_compra"] : 0);
        $factor = floatval(isset($renglon["factor_conversion"]) ? $renglon["factor_conversion"] : 0);
        $cantidadMinima = floatval(isset($renglon["cantidad_minima"]) ? $renglon["cantidad_minima"] : 0);
        $base = array(
            "id_lista_detalle_erp" => intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0),
            "sku_proveedor" => isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "",
            "codigo_barras" => isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "",
            "codigo_interno" => isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : "",
            "descripcion_proveedor" => isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : "",
            "id_sku" => $idSku,
            "id_sku_proveedor" => $idSkuProveedor,
            "estado_match" => $estado,
            "id_unidad_compra" => $idUnidad,
            "factor_conversion" => $factor,
            "cantidad_minima" => $cantidadMinima
        );

        if ($idSku <= 0) {
            return array("aplicable" => false, "motivo" => "sin_sku", "item" => $base + array("motivo" => "sin_sku", "detalle" => "No tiene SKU ERP seleccionado."));
        }
        if (!$this->skuErpExiste($db, $idSku)) {
            return array("aplicable" => false, "motivo" => "sku_invalido", "item" => $base + array("motivo" => "sku_invalido", "detalle" => "El SKU ERP seleccionado no existe o no esta disponible."));
        }
        if ($idSkuProveedor > 0 || in_array($estado, array("relacion_aplicada", "costo_aplicado"), true)) {
            return array("aplicable" => false, "motivo" => "ya_relacionado", "item" => $base + array("motivo" => "ya_relacionado", "detalle" => "El renglon ya tiene relacion aplicada."));
        }
        if ($estado !== "match_seleccionado") {
            return array("aplicable" => false, "motivo" => "estado_no_seleccionado", "item" => $base + array("motivo" => "estado_no_seleccionado", "detalle" => "Primero debe elegirse un candidato de matching."));
        }
        if ($idUnidad <= 0 || $factor <= 0 || $cantidadMinima <= 0) {
            return array("aplicable" => false, "motivo" => "compra_incompleta", "item" => $base + array("motivo" => "compra_incompleta", "detalle" => "Falta unidad, factor o cantidad minima."));
        }

        $relacion = $this->consultarRelacionSkuProveedorErp($db, 0, $idSku, $idProveedor);
        if ($relacion && intval($relacion["id_sku"]) !== $idSku) {
            return array("aplicable" => false, "motivo" => "relacion_conflictiva", "item" => $base + array("motivo" => "relacion_conflictiva", "detalle" => "Existe una relacion proveedor-SKU conflictiva."));
        }

        $accion = $relacion ? "actualizar" : "crear";
        return array(
            "aplicable" => true,
            "accion" => $accion,
            "item" => $base + array(
                "accion" => $accion,
                "detalle" => $accion === "actualizar" ? "Actualizaria la relacion existente con unidad/factor/cantidad." : "Crearia una relacion proveedor-SKU nueva.",
                "id_sku_proveedor_existente" => $relacion ? intval($relacion["id_sku_proveedor"]) : 0
            )
        );
    }

    public function aplicarRelacionesSkuProveedorLoteErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT *
                FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_proveedor_erp = :id_lista
                ORDER BY id_lista_detalle_erp ASC
                LIMIT 1000");
            $stmt->execute(array(":id_lista" => $idLista));
            $renglones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $aplicables = array();
            $excluidos = array();
            foreach ($renglones as $renglon) {
                $evaluacion = $this->evaluarRenglonRelacionSkuProveedorPreviewErp($db, $renglon, $idProveedor);
                if ($evaluacion["aplicable"]) {
                    $aplicables[] = array("renglon" => $renglon, "evaluacion" => $evaluacion);
                } else {
                    $excluidos[] = $evaluacion["item"];
                }
            }

            if (count($aplicables) <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No hay renglones listos para aplicar relacion en lote", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "excluidos" => count($excluidos)
                ));
            }

            $db->beginTransaction();
            $aplicados = array();
            $creados = 0;
            $actualizados = 0;
            $notificacionesResueltas = 0;
            $incidenciasResueltas = 0;

            foreach ($aplicables as $item) {
                $renglon = $item["renglon"];
                $idDetalle = intval($renglon["id_lista_detalle_erp"]);
                $idSku = intval($renglon["id_sku"]);
                $idUnidad = intval($renglon["id_unidad_compra"]);
                $factor = floatval($renglon["factor_conversion"]);
                $cantidadMinima = floatval($renglon["cantidad_minima"]);
                $skuProveedor = $this->valorNuloProveedorErp($this->textoProveedorErp($renglon, "sku_proveedor", 120));
                $antes = $this->consultarRelacionSkuProveedorErp($db, 0, $idSku, $idProveedor);

                if ($antes) {
                    $stmtUpdate = $db->prepare("UPDATE erp_catalogo_sku_proveedores SET
                        sku_proveedor = :sku_proveedor,
                        id_unidad_compra = :id_unidad_compra,
                        factor_conversion = :factor_conversion,
                        cantidad_minima = :cantidad_minima,
                        estatus = 'activo',
                        fecha_actualizacion = CURRENT_TIMESTAMP
                        WHERE id_sku_proveedor = :id_sku_proveedor");
                    $stmtUpdate->execute(array(
                        ":sku_proveedor" => $skuProveedor,
                        ":id_unidad_compra" => $idUnidad,
                        ":factor_conversion" => $factor,
                        ":cantidad_minima" => $cantidadMinima,
                        ":id_sku_proveedor" => intval($antes["id_sku_proveedor"])
                    ));
                    $idSkuProveedor = intval($antes["id_sku_proveedor"]);
                    $actualizados++;
                    $accion = "actualizar";
                } else {
                    $stmtInsert = $db->prepare("INSERT INTO erp_catalogo_sku_proveedores
                        (id_sku, id_proveedor, sku_proveedor, id_unidad_compra, factor_conversion, costo_ultimo, cantidad_minima, dias_entrega, es_preferido, estatus)
                        VALUES
                        (:id_sku, :id_proveedor, :sku_proveedor, :id_unidad_compra, :factor_conversion, 0, :cantidad_minima, 0, 0, 'activo')");
                    $stmtInsert->execute(array(
                        ":id_sku" => $idSku,
                        ":id_proveedor" => $idProveedor,
                        ":sku_proveedor" => $skuProveedor,
                        ":id_unidad_compra" => $idUnidad,
                        ":factor_conversion" => $factor,
                        ":cantidad_minima" => $cantidadMinima
                    ));
                    $idSkuProveedor = intval($db->lastInsertId());
                    $creados++;
                    $accion = "crear";
                }

                $stmtDetalle = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                    id_sku = :id_sku,
                    id_sku_proveedor = :id_sku_proveedor,
                    estado_match = 'relacion_aplicada',
                    criterio_match = 'relacion_sku_proveedor_lote_aplicada',
                    fecha_actualizacion = NOW()
                    WHERE id_lista_detalle_erp = :id_detalle AND id_lista_proveedor_erp = :id_lista");
                $stmtDetalle->execute(array(
                    ":id_sku" => $idSku,
                    ":id_sku_proveedor" => $idSkuProveedor,
                    ":id_detalle" => $idDetalle,
                    ":id_lista" => $idLista
                ));

                $notificacionesResueltas += $this->resolverNotificacionMatchingCatalogoProveedor($db, $idProveedor, $idLista, $idDetalle, $idSku);
                $incidenciasResueltas += $this->resolverIncidenciaCatalogoProveedorPorRelacion($db, $idProveedor, $idLista, $idDetalle, $idSku, $idSkuProveedor, $id_usuario);
                $aplicados[] = array(
                    "id_lista_detalle_erp" => $idDetalle,
                    "id_sku" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "accion" => $accion
                );
            }

            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Relaciones proveedor-SKU aplicadas en lote",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "aplicados" => count($aplicados),
                    "creados" => $creados,
                    "actualizados" => $actualizados,
                    "notificaciones_resueltas" => $notificacionesResueltas,
                    "incidencias_catalogo_resueltas" => $incidenciasResueltas,
                    "excluidos" => count($excluidos),
                    "renglones" => $aplicados
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function previewCostosProveedorLoteErp($id_proveedor, $id_lista_proveedor_erp) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT *
                FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_proveedor_erp = :id_lista
                ORDER BY id_lista_detalle_erp ASC
                LIMIT 1000");
            $stmt->execute(array(":id_lista" => $idLista));
            $renglones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $incluidos = array();
            $excluidos = array();
            $resumen = array(
                "total" => count($renglones),
                "incluidos" => 0,
                "crear_costo" => 0,
                "actualizar_costo" => 0,
                "excluidos" => 0,
                "sin_relacion" => 0,
                "relacion_invalida" => 0,
                "relacion_inactiva" => 0,
                "costo_incompleto" => 0,
                "unidad_factor_incompleto" => 0,
                "impuestos_sin_definir" => 0,
                "estado_no_aplicable" => 0
            );

            foreach ($renglones as $renglon) {
                $evaluacion = $this->evaluarRenglonCostoProveedorPreviewErp($db, $renglon, $idProveedor);
                if ($evaluacion["aplicable"]) {
                    $incluidos[] = $evaluacion["item"];
                    $resumen["incluidos"]++;
                    $resumen[$evaluacion["accion"] === "actualizar" ? "actualizar_costo" : "crear_costo"]++;
                } else {
                    $excluidos[] = $evaluacion["item"];
                    $resumen["excluidos"]++;
                    if (isset($resumen[$evaluacion["motivo"]])) {
                        $resumen[$evaluacion["motivo"]]++;
                    }
                }
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Vista previa de costos calculada",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "sin_costo_referencia" => true,
                    "lista" => $lista,
                    "resumen" => $resumen,
                    "incluidos" => $incluidos,
                    "excluidos" => $excluidos
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function evaluarRenglonCostoProveedorPreviewErp($db, $renglon, $idProveedor) {
        $idDetalle = intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0);
        $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
        $idSkuProveedor = intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0);
        $estado = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        $costo = floatval(isset($renglon["costo"]) ? $renglon["costo"] : 0);
        $moneda = strtoupper($this->textoProveedorErp($renglon, "moneda", 10));
        $idUnidad = intval(isset($renglon["id_unidad_compra"]) ? $renglon["id_unidad_compra"] : 0);
        $factor = floatval(isset($renglon["factor_conversion"]) ? $renglon["factor_conversion"] : 0);
        $incluyeImpuestos = isset($renglon["costo_incluye_impuestos"]) && $renglon["costo_incluye_impuestos"] !== null && trim((string) $renglon["costo_incluye_impuestos"]) !== "" ? intval($renglon["costo_incluye_impuestos"]) : null;
        $base = array(
            "id_lista_detalle_erp" => $idDetalle,
            "sku_proveedor" => isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "",
            "codigo_barras" => isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "",
            "codigo_interno" => isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : "",
            "descripcion_proveedor" => isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : "",
            "id_sku" => $idSku,
            "id_sku_proveedor" => $idSkuProveedor,
            "estado_match" => $estado,
            "costo" => $costo,
            "moneda" => $moneda,
            "id_unidad_compra" => $idUnidad,
            "factor_conversion" => $factor,
            "costo_incluye_impuestos" => $incluyeImpuestos
        );

        if ($idSku <= 0 || $idSkuProveedor <= 0) {
            return array("aplicable" => false, "motivo" => "sin_relacion", "item" => $base + array("motivo" => "sin_relacion", "detalle" => "Primero debe existir relacion proveedor-SKU aplicada."));
        }
        if (!in_array($estado, array("relacion_aplicada", "costo_aplicado"), true)) {
            return array("aplicable" => false, "motivo" => "estado_no_aplicable", "item" => $base + array("motivo" => "estado_no_aplicable", "detalle" => "El renglon debe tener relacion aplicada antes de costo."));
        }
        if ($costo <= 0 || $moneda === "") {
            return array("aplicable" => false, "motivo" => "costo_incompleto", "item" => $base + array("motivo" => "costo_incompleto", "detalle" => "Falta costo positivo o moneda."));
        }
        if ($idUnidad <= 0 || $factor <= 0) {
            return array("aplicable" => false, "motivo" => "unidad_factor_incompleto", "item" => $base + array("motivo" => "unidad_factor_incompleto", "detalle" => "Falta unidad de compra o factor."));
        }
        if ($incluyeImpuestos === null || !in_array($incluyeImpuestos, array(0, 1), true)) {
            return array("aplicable" => false, "motivo" => "impuestos_sin_definir", "item" => $base + array("motivo" => "impuestos_sin_definir", "detalle" => "Debe indicarse si el costo incluye impuestos."));
        }

        $relacion = $this->consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor);
        if (!$relacion || intval($relacion["id_sku"]) !== $idSku) {
            return array("aplicable" => false, "motivo" => "relacion_invalida", "item" => $base + array("motivo" => "relacion_invalida", "detalle" => "La relacion proveedor-SKU no corresponde al SKU del renglon."));
        }
        if (isset($relacion["estatus"]) && $relacion["estatus"] !== "" && strtolower((string) $relacion["estatus"]) !== "activo") {
            return array("aplicable" => false, "motivo" => "relacion_inactiva", "item" => $base + array("motivo" => "relacion_inactiva", "detalle" => "La relacion proveedor-SKU no esta activa."));
        }

        $costoExistente = $this->consultarCostoProveedorSkuPorDetalleErp($db, $idDetalle, $idProveedor);
        $accion = $costoExistente ? "actualizar" : "crear";
        return array(
            "aplicable" => true,
            "accion" => $accion,
            "item" => $base + array(
                "accion" => $accion,
                "detalle" => $accion === "actualizar" ? "Actualizaria el costo vigente de este renglon." : "Crearia costo vigente desde lista proveedor.",
                "id_costo_proveedor_sku_existente" => $costoExistente ? intval($costoExistente["id_costo_proveedor_sku"]) : 0
            )
        );
    }

    public function aplicarCostosProveedorLoteErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT *
                FROM erp_proveedores_listas_detalle_erp
                WHERE id_lista_proveedor_erp = :id_lista
                ORDER BY id_lista_detalle_erp ASC
                LIMIT 1000");
            $stmt->execute(array(":id_lista" => $idLista));
            $renglones = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $aplicables = array();
            $excluidos = array();
            foreach ($renglones as $renglon) {
                $evaluacion = $this->evaluarRenglonCostoProveedorPreviewErp($db, $renglon, $idProveedor);
                if ($evaluacion["aplicable"]) {
                    $aplicables[] = array("renglon" => $renglon, "evaluacion" => $evaluacion);
                } else {
                    $excluidos[] = $evaluacion["item"];
                }
            }

            if (count($aplicables) <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No hay renglones listos para aplicar costo en lote", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "excluidos" => count($excluidos)
                ));
            }

            $vigenciaDesde = isset($lista["vigencia_desde"]) ? $lista["vigencia_desde"] : null;
            $vigenciaHasta = isset($lista["vigencia_hasta"]) ? $lista["vigencia_hasta"] : null;
            $idDocumento = isset($lista["id_documento_proveedor"]) ? intval($lista["id_documento_proveedor"]) : 0;

            $db->beginTransaction();
            $aplicados = array();
            $creados = 0;
            $actualizados = 0;

            $stmtHistorico = $db->prepare("UPDATE erp_proveedores_sku_costos
                SET estatus = 'historico', fecha_actualizacion = NOW()
                WHERE id_proveedor = :id_proveedor
                  AND id_sku = :id_sku
                  AND estatus = 'vigente'
                  AND (:id_costo_actual_cero = 0 OR id_costo_proveedor_sku <> :id_costo_actual)");
            $stmtUpdateCosto = $db->prepare("UPDATE erp_proveedores_sku_costos SET
                id_sku = :id_sku,
                id_sku_proveedor = :id_sku_proveedor,
                id_lista_proveedor_erp = :id_lista,
                costo = :costo,
                moneda = :moneda,
                id_unidad_compra = :id_unidad_compra,
                factor_conversion = :factor_conversion,
                costo_incluye_impuestos = :costo_incluye_impuestos,
                vigencia_desde = :vigencia_desde,
                vigencia_hasta = :vigencia_hasta,
                origen = 'lista_proveedor',
                id_documento_proveedor = :id_documento_proveedor,
                estatus = 'vigente',
                autorizado_por = :autorizado_por,
                fecha_autorizacion = NOW(),
                fecha_actualizacion = NOW()
                WHERE id_costo_proveedor_sku = :id_costo");
            $stmtInsertCosto = $db->prepare("INSERT INTO erp_proveedores_sku_costos
                (id_proveedor, id_sku, id_sku_proveedor, id_lista_proveedor_erp, id_lista_detalle_erp, costo, moneda, id_unidad_compra, factor_conversion, costo_incluye_impuestos, vigencia_desde, vigencia_hasta, origen, id_documento_proveedor, estatus, autorizado_por, fecha_autorizacion, fecha_registro, fecha_actualizacion)
                VALUES
                (:id_proveedor, :id_sku, :id_sku_proveedor, :id_lista, :id_detalle, :costo, :moneda, :id_unidad_compra, :factor_conversion, :costo_incluye_impuestos, :vigencia_desde, :vigencia_hasta, 'lista_proveedor', :id_documento_proveedor, 'vigente', :autorizado_por, NOW(), NOW(), NOW())");
            $stmtRelacion = $db->prepare("UPDATE erp_catalogo_sku_proveedores
                SET costo_ultimo = :costo_ultimo, fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id_sku_proveedor = :id_sku_proveedor AND id_proveedor = :id_proveedor AND id_sku = :id_sku");
            $stmtDetalle = $db->prepare("UPDATE erp_proveedores_listas_detalle_erp SET
                estado_match = 'costo_aplicado',
                criterio_match = 'costo_proveedor_lote_aplicado',
                fecha_actualizacion = NOW()
                WHERE id_lista_detalle_erp = :id_detalle AND id_lista_proveedor_erp = :id_lista");

            foreach ($aplicables as $item) {
                $renglon = $item["renglon"];
                $idDetalle = intval($renglon["id_lista_detalle_erp"]);
                $idSku = intval($renglon["id_sku"]);
                $idSkuProveedor = intval($renglon["id_sku_proveedor"]);
                $costo = floatval($renglon["costo"]);
                $moneda = strtoupper($this->textoProveedorErp($renglon, "moneda", 10));
                $idUnidad = intval($renglon["id_unidad_compra"]);
                $factor = floatval($renglon["factor_conversion"]);
                $incluyeImpuestos = intval($renglon["costo_incluye_impuestos"]);
                $antesCosto = $this->consultarCostoProveedorSkuPorDetalleErp($db, $idDetalle, $idProveedor);

                $stmtHistorico->execute(array(
                    ":id_proveedor" => $idProveedor,
                    ":id_sku" => $idSku,
                    ":id_costo_actual_cero" => $antesCosto ? intval($antesCosto["id_costo_proveedor_sku"]) : 0,
                    ":id_costo_actual" => $antesCosto ? intval($antesCosto["id_costo_proveedor_sku"]) : 0
                ));

                if ($antesCosto) {
                    $stmtUpdateCosto->execute(array(
                        ":id_sku" => $idSku,
                        ":id_sku_proveedor" => $idSkuProveedor,
                        ":id_lista" => $idLista,
                        ":costo" => $costo,
                        ":moneda" => $moneda,
                        ":id_unidad_compra" => $idUnidad,
                        ":factor_conversion" => $factor,
                        ":costo_incluye_impuestos" => $incluyeImpuestos,
                        ":vigencia_desde" => $vigenciaDesde,
                        ":vigencia_hasta" => $vigenciaHasta,
                        ":id_documento_proveedor" => $idDocumento > 0 ? $idDocumento : null,
                        ":autorizado_por" => intval($id_usuario),
                        ":id_costo" => intval($antesCosto["id_costo_proveedor_sku"])
                    ));
                    $idCosto = intval($antesCosto["id_costo_proveedor_sku"]);
                    $actualizados++;
                    $accion = "actualizar";
                } else {
                    $stmtInsertCosto->execute(array(
                        ":id_proveedor" => $idProveedor,
                        ":id_sku" => $idSku,
                        ":id_sku_proveedor" => $idSkuProveedor,
                        ":id_lista" => $idLista,
                        ":id_detalle" => $idDetalle,
                        ":costo" => $costo,
                        ":moneda" => $moneda,
                        ":id_unidad_compra" => $idUnidad,
                        ":factor_conversion" => $factor,
                        ":costo_incluye_impuestos" => $incluyeImpuestos,
                        ":vigencia_desde" => $vigenciaDesde,
                        ":vigencia_hasta" => $vigenciaHasta,
                        ":id_documento_proveedor" => $idDocumento > 0 ? $idDocumento : null,
                        ":autorizado_por" => intval($id_usuario)
                    ));
                    $idCosto = intval($db->lastInsertId());
                    $creados++;
                    $accion = "crear";
                }

                $stmtRelacion->execute(array(
                    ":costo_ultimo" => $costo,
                    ":id_sku_proveedor" => $idSkuProveedor,
                    ":id_proveedor" => $idProveedor,
                    ":id_sku" => $idSku
                ));
                $stmtDetalle->execute(array(
                    ":id_detalle" => $idDetalle,
                    ":id_lista" => $idLista
                ));

                $aplicados[] = array(
                    "id_lista_detalle_erp" => $idDetalle,
                    "id_sku" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "id_costo_proveedor_sku" => $idCosto,
                    "costo" => $costo,
                    "moneda" => $moneda,
                    "accion" => $accion
                );
            }

            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Costos proveedor-SKU aplicados en lote",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "aplicados" => count($aplicados),
                    "creados" => $creados,
                    "actualizados" => $actualizados,
                    "excluidos" => count($excluidos),
                    "sin_costo_referencia" => true,
                    "renglones" => $aplicados
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function previewCostoReferenciaListaProveedorErp($id_proveedor, $id_lista_proveedor_erp) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista_proveedor_erp);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista ERP no encontrada", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT
                    c.id_costo_proveedor_sku,
                    c.id_lista_detalle_erp,
                    c.id_sku,
                    c.id_sku_proveedor,
                    c.costo costo_proveedor,
                    c.moneda,
                    c.vigencia_desde,
                    c.vigencia_hasta,
                    s.sku sku_erp,
                    s.nombre sku_nombre,
                    s.costo_referencia,
                    sp.sku_proveedor,
                    sp.es_preferido,
                    d.descripcion_proveedor
                FROM erp_proveedores_sku_costos c
                INNER JOIN (
                    SELECT id_sku, MAX(id_costo_proveedor_sku) id_costo_proveedor_sku
                    FROM erp_proveedores_sku_costos
                    WHERE id_proveedor = :id_proveedor
                      AND id_lista_proveedor_erp = :id_lista
                      AND estatus = 'vigente'
                    GROUP BY id_sku
                ) ult ON ult.id_costo_proveedor_sku = c.id_costo_proveedor_sku
                INNER JOIN erp_catalogo_skus s ON s.id_sku = c.id_sku AND s.estatus <> 'fusionado'
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor = c.id_sku_proveedor
                LEFT JOIN erp_proveedores_listas_detalle_erp d ON d.id_lista_detalle_erp = c.id_lista_detalle_erp
                ORDER BY sp.es_preferido DESC, s.sku ASC
                LIMIT 1000");
            $stmt->execute(array(
                ":id_proveedor" => $idProveedor,
                ":id_lista" => $idLista
            ));

            $propuestas = array();
            $resumen = array(
                "total" => 0,
                "sin_costo_referencia" => 0,
                "sin_cambio" => 0,
                "con_cambio" => 0,
                "cambio_mayor_10" => 0,
                "moneda_no_mxn" => 0,
                "proveedor_preferido" => 0,
                "fuente_compra_real" => 0,
                "fuente_proveedor_vigente" => 0,
                "elegibles_aplicar" => 0,
                "bloqueados_aplicar" => 0
            );

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $actual = floatval(isset($fila["costo_referencia"]) ? $fila["costo_referencia"] : 0);
                $costoProveedor = floatval(isset($fila["costo_proveedor"]) ? $fila["costo_proveedor"] : 0);
                $compraReal = $this->ultimoCostoCompraRealSkuErp($db, intval($fila["id_sku"]));
                $fuenteSugerida = $compraReal ? "compra_real" : "proveedor_vigente";
                $propuesto = $compraReal ? floatval($compraReal["costo_unitario_mxn"]) : $costoProveedor;
                $delta = $propuesto - $actual;
                $deltaPct = $actual > 0 ? ($delta / $actual) * 100 : null;
                $monedaProveedor = strtoupper(trim((string) (isset($fila["moneda"]) ? $fila["moneda"] : "")));
                $moneda = $compraReal ? "MXN" : $monedaProveedor;
                $advertencias = array();
                $accion = "revisar_cambio";

                if ($actual <= 0) {
                    $accion = "llenar_costo_referencia";
                    $advertencias[] = "SKU sin costo_referencia actual";
                    $resumen["sin_costo_referencia"]++;
                } elseif (abs($delta) < 0.000001) {
                    $accion = "sin_cambio";
                    $resumen["sin_cambio"]++;
                } else {
                    $resumen["con_cambio"]++;
                }

                if ($deltaPct !== null && abs($deltaPct) >= 10) {
                    $advertencias[] = "Cambio mayor o igual a 10%";
                    $resumen["cambio_mayor_10"]++;
                }
                if ($moneda !== "" && $moneda !== "MXN") {
                    $advertencias[] = "Moneda distinta a MXN; requiere politica de conversion antes de aplicar";
                    $resumen["moneda_no_mxn"]++;
                }
                if ($fuenteSugerida === "compra_real") {
                    $resumen["fuente_compra_real"]++;
                } else {
                    $resumen["fuente_proveedor_vigente"]++;
                    $advertencias[] = "Sin compra/recepcion real detectada; usa costo proveedor como referencia inicial";
                }
                if (intval(isset($fila["es_preferido"]) ? $fila["es_preferido"] : 0) === 1) {
                    $resumen["proveedor_preferido"]++;
                } else {
                    $advertencias[] = "Proveedor no marcado como preferido para este SKU";
                }

                $aplicabilidad = $this->evaluarAplicabilidadCostoReferenciaErp($actual, $propuesto, $moneda, $fuenteSugerida);
                if ($aplicabilidad["puede_aplicar"]) {
                    $resumen["elegibles_aplicar"] = isset($resumen["elegibles_aplicar"]) ? $resumen["elegibles_aplicar"] + 1 : 1;
                } else {
                    $resumen["bloqueados_aplicar"] = isset($resumen["bloqueados_aplicar"]) ? $resumen["bloqueados_aplicar"] + 1 : 1;
                    $advertencias[] = $aplicabilidad["motivo"];
                }

                $propuestas[] = array(
                    "id_sku" => intval($fila["id_sku"]),
                    "sku_erp" => isset($fila["sku_erp"]) ? $fila["sku_erp"] : "",
                    "sku_nombre" => isset($fila["sku_nombre"]) ? $fila["sku_nombre"] : "",
                    "id_sku_proveedor" => intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0),
                    "sku_proveedor" => isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "",
                    "descripcion_proveedor" => isset($fila["descripcion_proveedor"]) ? $fila["descripcion_proveedor"] : "",
                    "id_costo_proveedor_sku" => intval($fila["id_costo_proveedor_sku"]),
                    "costo_referencia_actual" => $actual,
                    "costo_proveedor" => $costoProveedor,
                    "costo_propuesto" => $propuesto,
                    "moneda" => $moneda,
                    "moneda_proveedor" => $monedaProveedor,
                    "fuente_sugerida" => $fuenteSugerida,
                    "compra_real" => $compraReal,
                    "delta" => $delta,
                    "delta_pct" => $deltaPct,
                    "es_preferido" => intval(isset($fila["es_preferido"]) ? $fila["es_preferido"] : 0),
                    "accion_sugerida" => $accion,
                    "puede_aplicar_costo_referencia" => $aplicabilidad["puede_aplicar"],
                    "motivo_no_aplicable" => $aplicabilidad["puede_aplicar"] ? "" : $aplicabilidad["motivo"],
                    "advertencias" => $advertencias
                );
                $resumen["total"]++;
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Preview de costo_referencia consultado",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "lista" => $lista,
                    "resumen" => $resumen,
                    "propuestas" => $propuestas,
                    "politica_recomendada" => array(
                        "Priorizar costo real comprado/recibido cuando exista.",
                        "Si no hay compra real, usar costo vigente de proveedor solo como referencia inicial.",
                        "No aplicar automaticamente desde cualquier lista.",
                        "Revisar proveedor preferido, moneda y variacion antes de autorizar.",
                        "Mantener historial de proveedor como fuente de verdad; costo_referencia es referencia de Catalogo."
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function aplicarCostoReferenciaListaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista invalida", "depurar" => null);
            }

            $preview = $this->previewCostoReferenciaListaProveedorErp($idProveedor, $idLista);
            if (!empty($preview["error"])) {
                return $preview;
            }
            $propuestas = isset($preview["depurar"]["propuestas"]) && is_array($preview["depurar"]["propuestas"]) ? $preview["depurar"]["propuestas"] : array();
            $aplicables = array();
            $excluidos = array();
            foreach ($propuestas as $propuesta) {
                if (!empty($propuesta["puede_aplicar_costo_referencia"])) {
                    $aplicables[] = $propuesta;
                } else {
                    $excluidos[] = array(
                        "id_sku" => intval(isset($propuesta["id_sku"]) ? $propuesta["id_sku"] : 0),
                        "sku_erp" => isset($propuesta["sku_erp"]) ? $propuesta["sku_erp"] : "",
                        "motivo" => isset($propuesta["motivo_no_aplicable"]) ? $propuesta["motivo_no_aplicable"] : "No elegible"
                    );
                }
            }
            if (count($aplicables) <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No hay propuestas elegibles para aplicar costo_referencia", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "excluidos" => $excluidos
                ));
            }

            $db->beginTransaction();
            $stmtSku = $db->prepare("SELECT id_sku, sku, nombre, costo_referencia FROM erp_catalogo_skus WHERE id_sku=:id_sku AND estatus <> 'fusionado' FOR UPDATE");
            $stmtUpdate = $db->prepare("UPDATE erp_catalogo_skus SET costo_referencia=:costo, fecha_actualizacion=CURRENT_TIMESTAMP WHERE id_sku=:id_sku");
            $aplicados = array();
            foreach ($aplicables as $propuesta) {
                $idSku = intval($propuesta["id_sku"]);
                $costo = floatval($propuesta["costo_propuesto"]);
                $moneda = strtoupper(trim((string) (isset($propuesta["moneda"]) ? $propuesta["moneda"] : "")));
                $fuente = isset($propuesta["fuente_sugerida"]) ? (string) $propuesta["fuente_sugerida"] : "";
                $actualPreview = floatval(isset($propuesta["costo_referencia_actual"]) ? $propuesta["costo_referencia_actual"] : 0);
                $aplicabilidad = $this->evaluarAplicabilidadCostoReferenciaErp($actualPreview, $costo, $moneda, $fuente);
                if (!$aplicabilidad["puede_aplicar"]) {
                    $excluidos[] = array("id_sku" => $idSku, "sku_erp" => isset($propuesta["sku_erp"]) ? $propuesta["sku_erp"] : "", "motivo" => $aplicabilidad["motivo"]);
                    continue;
                }

                $stmtSku->execute(array(":id_sku" => $idSku));
                $antes = $stmtSku->fetch(PDO::FETCH_ASSOC);
                if (!$antes) {
                    $excluidos[] = array("id_sku" => $idSku, "sku_erp" => isset($propuesta["sku_erp"]) ? $propuesta["sku_erp"] : "", "motivo" => "SKU ERP no encontrado");
                    continue;
                }
                $costoActualBloqueado = floatval(isset($antes["costo_referencia"]) ? $antes["costo_referencia"] : 0);
                $aplicabilidadActual = $this->evaluarAplicabilidadCostoReferenciaErp($costoActualBloqueado, $costo, $moneda, $fuente);
                if (!$aplicabilidadActual["puede_aplicar"]) {
                    $excluidos[] = array("id_sku" => $idSku, "sku_erp" => isset($propuesta["sku_erp"]) ? $propuesta["sku_erp"] : "", "motivo" => $aplicabilidadActual["motivo"]);
                    continue;
                }

                $stmtUpdate->execute(array(":costo" => $costo, ":id_sku" => $idSku));
                $aplicados[] = array(
                    "id_sku" => $idSku,
                    "sku_erp" => isset($antes["sku"]) ? $antes["sku"] : "",
                    "costo_anterior" => $costoActualBloqueado,
                    "costo_nuevo" => $costo,
                    "moneda" => $moneda,
                    "fuente_sugerida" => $fuente,
                    "id_costo_proveedor_sku" => intval(isset($propuesta["id_costo_proveedor_sku"]) ? $propuesta["id_costo_proveedor_sku"] : 0),
                    "id_orden_compra" => isset($propuesta["compra_real"]["id_orden_compra"]) ? intval($propuesta["compra_real"]["id_orden_compra"]) : 0
                );
            }

            if (count($aplicados) <= 0) {
                $db->rollBack();
                return array("error" => true, "tipo" => "warning", "mensaje" => "No se aplico ningun costo_referencia", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "excluidos" => $excluidos
                ));
            }

            $db->commit();
            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Costo_referencia aplicado con reglas controladas",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "aplicados" => count($aplicados),
                    "excluidos" => count($excluidos),
                    "usuario" => intval($id_usuario),
                    "reglas" => array(
                        "Compra real recibida puede actualizar costo_referencia en MXN.",
                        "Proveedor vigente solo llena costo_referencia cuando el SKU no tenia costo.",
                        "Compra real en moneda extranjera usa el tipo de cambio guardado en la orden para proponer costo_referencia en MXN.",
                        "Costo proveedor vigente en moneda distinta de MXN queda bloqueado hasta definir conversion."
                    ),
                    "renglones" => $aplicados,
                    "excluidos_detalle" => $excluidos
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function evaluarAplicabilidadCostoReferenciaErp($costo_actual, $costo_propuesto, $moneda, $fuente) {
        $actual = floatval($costo_actual);
        $propuesto = floatval($costo_propuesto);
        $monedaNormalizada = strtoupper(trim((string) $moneda));
        if ($propuesto <= 0) {
            return array("puede_aplicar" => false, "motivo" => "Costo propuesto invalido");
        }
        if ($monedaNormalizada !== "MXN") {
            return array("puede_aplicar" => false, "motivo" => "Moneda distinta a MXN");
        }
        if ($fuente === "compra_real") {
            return array("puede_aplicar" => true, "motivo" => "");
        }
        if ($fuente === "proveedor_vigente" && $actual <= 0) {
            return array("puede_aplicar" => true, "motivo" => "");
        }
        return array("puede_aplicar" => false, "motivo" => "Costo de proveedor vigente no reemplaza costo_referencia existente sin revision manual");
    }

    private function ultimoCostoCompraRealSkuErp($db, $id_sku) {
        $idSku = intval($id_sku);
        if ($idSku <= 0) {
            return null;
        }
        try {
            $stmt = $db->prepare("SELECT
                    d.id_detalle,
                    d.id_orden_compra,
                    d.id_sku_erp id_sku,
                    d.id_sku_proveedor,
                    d.costo_unitario,
                    d.cantidad_recibida,
                    o.id_proveedor,
                    o.moneda,
                    o.tipo_cambio,
                    o.estatus,
                    o.fecha_orden,
                    o.fecha_actualizacion
                FROM erp_compras_ordenes_detalle d
                INNER JOIN erp_compras_ordenes o ON o.id_orden_compra = d.id_orden_compra
                WHERE d.id_sku_erp = :id_sku
                  AND COALESCE(d.cantidad_recibida, 0) > 0
                  AND COALESCE(d.costo_unitario, 0) > 0
                  AND COALESCE(o.estatus, '') <> 'cancelada'
                ORDER BY COALESCE(o.fecha_actualizacion, o.fecha_orden) DESC, d.id_detalle DESC
                LIMIT 1");
            $stmt->execute(array(":id_sku" => $idSku));
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$fila) {
                return null;
            }
            return array(
                "id_detalle" => intval($fila["id_detalle"]),
                "id_orden_compra" => intval($fila["id_orden_compra"]),
                "id_sku" => intval($fila["id_sku"]),
                "id_sku_proveedor" => intval(isset($fila["id_sku_proveedor"]) ? $fila["id_sku_proveedor"] : 0),
                "id_proveedor" => intval(isset($fila["id_proveedor"]) ? $fila["id_proveedor"] : 0),
                "costo_unitario" => floatval($fila["costo_unitario"]),
                "costo_unitario_mxn" => $this->costoOrdenProveedorMxnErp($fila),
                "cantidad_recibida" => floatval($fila["cantidad_recibida"]),
                "moneda" => isset($fila["moneda"]) ? $fila["moneda"] : "",
                "tipo_cambio" => floatval(isset($fila["tipo_cambio"]) ? $fila["tipo_cambio"] : 1),
                "estatus" => isset($fila["estatus"]) ? $fila["estatus"] : "",
                "fecha_orden" => isset($fila["fecha_orden"]) ? $fila["fecha_orden"] : "",
                "fecha_actualizacion" => isset($fila["fecha_actualizacion"]) ? $fila["fecha_actualizacion"] : ""
            );
        } catch (Exception $e) {
            return null;
        }
    }

    private function costoOrdenProveedorMxnErp($fila) {
        $costo = floatval(isset($fila["costo_unitario"]) ? $fila["costo_unitario"] : 0);
        $moneda = strtoupper(trim((string) (isset($fila["moneda"]) ? $fila["moneda"] : "MXN")));
        $tipoCambio = floatval(isset($fila["tipo_cambio"]) ? $fila["tipo_cambio"] : 1);
        if ($moneda !== "MXN") {
            if ($tipoCambio <= 0) {
                return 0;
            }
            $costo = $costo * $tipoCambio;
        }
        return round($costo, 6);
    }

    public function aplicarCostoProveedorSkuErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            if ($idProveedor <= 0 || $idLista <= 0 || $idDetalle <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista invalido", "depurar" => null);
            }

            $renglon = $this->consultarListaDetalleProveedorErp($db, $idDetalle, $idLista, $idProveedor);
            if (!$renglon) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Renglon de lista no encontrado", "depurar" => null);
            }

            $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
            $idSkuProveedor = intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0);
            $costo = floatval(isset($renglon["costo"]) ? $renglon["costo"] : 0);
            $moneda = strtoupper($this->textoProveedorErp($renglon, "moneda", 10));
            $idUnidad = intval(isset($renglon["id_unidad_compra"]) ? $renglon["id_unidad_compra"] : 0);
            $factor = floatval(isset($renglon["factor_conversion"]) ? $renglon["factor_conversion"] : 0);
            $incluyeImpuestos = isset($renglon["costo_incluye_impuestos"]) && $renglon["costo_incluye_impuestos"] !== null && trim((string) $renglon["costo_incluye_impuestos"]) !== "" ? intval($renglon["costo_incluye_impuestos"]) : null;

            if ($idSku <= 0 || $idSkuProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Aplica primero la relacion SKU-proveedor", "depurar" => null);
            }
            if ($costo <= 0 || $moneda === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura costo positivo y moneda antes de aplicar", "depurar" => null);
            }
            if ($idUnidad <= 0 || $factor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Completa unidad y factor antes de aplicar costo", "depurar" => null);
            }
            if ($incluyeImpuestos === null || !in_array($incluyeImpuestos, array(0, 1), true)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Define si el costo incluye impuestos antes de aplicar", "depurar" => null);
            }

            $relacionAntes = $this->consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor);
            if (!$relacionAntes || intval($relacionAntes["id_sku"]) !== $idSku) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Relacion SKU-proveedor no valida para este renglon", "depurar" => null);
            }
            if (isset($relacionAntes["estatus"]) && $relacionAntes["estatus"] !== "" && strtolower((string) $relacionAntes["estatus"]) !== "activo") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "La relacion SKU-proveedor no esta activa", "depurar" => null);
            }

            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            $antesCosto = $this->consultarCostoProveedorSkuPorDetalleErp($db, $idDetalle, $idProveedor);
            $vigenciaDesde = $lista && isset($lista["vigencia_desde"]) ? $lista["vigencia_desde"] : null;
            $vigenciaHasta = $lista && isset($lista["vigencia_hasta"]) ? $lista["vigencia_hasta"] : null;
            $idDocumento = $lista && isset($lista["id_documento_proveedor"]) ? intval($lista["id_documento_proveedor"]) : 0;

            $db->beginTransaction();

            $stmtHistorico = $db->prepare("UPDATE erp_proveedores_sku_costos
                SET estatus = 'historico', fecha_actualizacion = NOW()
                WHERE id_proveedor = :id_proveedor
                  AND id_sku = :id_sku
                  AND estatus = 'vigente'
                  AND (:id_costo_actual_cero = 0 OR id_costo_proveedor_sku <> :id_costo_actual)");
            $stmtHistorico->execute(array(
                ":id_proveedor" => $idProveedor,
                ":id_sku" => $idSku,
                ":id_costo_actual_cero" => $antesCosto ? intval($antesCosto["id_costo_proveedor_sku"]) : 0,
                ":id_costo_actual" => $antesCosto ? intval($antesCosto["id_costo_proveedor_sku"]) : 0
            ));

            if ($antesCosto) {
                $stmtCosto = $db->prepare("UPDATE erp_proveedores_sku_costos SET
                    id_sku = :id_sku,
                    id_sku_proveedor = :id_sku_proveedor,
                    id_lista_proveedor_erp = :id_lista,
                    costo = :costo,
                    moneda = :moneda,
                    id_unidad_compra = :id_unidad_compra,
                    factor_conversion = :factor_conversion,
                    costo_incluye_impuestos = :costo_incluye_impuestos,
                    vigencia_desde = :vigencia_desde,
                    vigencia_hasta = :vigencia_hasta,
                    origen = 'lista_proveedor',
                    id_documento_proveedor = :id_documento_proveedor,
                    estatus = 'vigente',
                    autorizado_por = :autorizado_por,
                    fecha_autorizacion = NOW(),
                    fecha_actualizacion = NOW()
                    WHERE id_costo_proveedor_sku = :id_costo");
                $stmtCosto->execute(array(
                    ":id_sku" => $idSku,
                    ":id_sku_proveedor" => $idSkuProveedor,
                    ":id_lista" => $idLista,
                    ":costo" => $costo,
                    ":moneda" => $moneda,
                    ":id_unidad_compra" => $idUnidad,
                    ":factor_conversion" => $factor,
                    ":costo_incluye_impuestos" => $incluyeImpuestos,
                    ":vigencia_desde" => $vigenciaDesde,
                    ":vigencia_hasta" => $vigenciaHasta,
                    ":id_documento_proveedor" => $idDocumento > 0 ? $idDocumento : null,
                    ":autorizado_por" => intval($id_usuario),
                    ":id_costo" => intval($antesCosto["id_costo_proveedor_sku"])
                ));
                $idCosto = intval($antesCosto["id_costo_proveedor_sku"]);
            } else {
                $stmtCosto = $db->prepare("INSERT INTO erp_proveedores_sku_costos
                    (id_proveedor, id_sku, id_sku_proveedor, id_lista_proveedor_erp, id_lista_detalle_erp, costo, moneda, id_unidad_compra, factor_conversion, costo_incluye_impuestos, vigencia_desde, vigencia_hasta, origen, id_documento_proveedor, estatus, autorizado_por, fecha_autorizacion, fecha_registro, fecha_actualizacion)
                    VALUES
                    (:id_proveedor, :id_sku, :id_sku_proveedor, :id_lista, :id_detalle, :costo, :moneda, :id_unidad_compra, :factor_conversion, :costo_incluye_impuestos, :vigencia_desde, :vigencia_hasta, 'lista_proveedor', :id_documento_proveedor, 'vigente', :autorizado_por, NOW(), NOW(), NOW())");
                $stmtCosto->execute(array(
                    ":id_proveedor" => $idProveedor,
                    ":id_sku" => $idSku,
                    ":id_sku_proveedor" => $idSkuProveedor,
                    ":id_lista" => $idLista,
                    ":id_detalle" => $idDetalle,
                    ":costo" => $costo,
                    ":moneda" => $moneda,
                    ":id_unidad_compra" => $idUnidad,
                    ":factor_conversion" => $factor,
                    ":costo_incluye_impuestos" => $incluyeImpuestos,
                    ":vigencia_desde" => $vigenciaDesde,
                    ":vigencia_hasta" => $vigenciaHasta,
                    ":id_documento_proveedor" => $idDocumento > 0 ? $idDocumento : null,
                    ":autorizado_por" => intval($id_usuario)
                ));
                $idCosto = intval($db->lastInsertId());
            }

            $stmtRelacion = $db->prepare("UPDATE erp_catalogo_sku_proveedores
                SET costo_ultimo = :costo_ultimo, fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id_sku_proveedor = :id_sku_proveedor AND id_proveedor = :id_proveedor AND id_sku = :id_sku");
            $stmtRelacion->execute(array(
                ":costo_ultimo" => $costo,
                ":id_sku_proveedor" => $idSkuProveedor,
                ":id_proveedor" => $idProveedor,
                ":id_sku" => $idSku
            ));

            $despuesCosto = $this->consultarCostoProveedorSkuPorDetalleErp($db, $idDetalle, $idProveedor);
            $relacionDespues = $this->consultarRelacionSkuProveedorErp($db, $idSkuProveedor, $idSku, $idProveedor);
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Costo proveedor-SKU aplicado como vigente",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_sku" => $idSku,
                    "id_sku_proveedor" => $idSkuProveedor,
                    "id_costo_proveedor_sku" => $idCosto,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "antes" => array("costo" => $antesCosto, "relacion" => $relacionAntes),
                    "despues" => array("costo" => $despuesCosto, "relacion" => $relacionDespues)
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function incidenciasListaDryRunErp($id_proveedor, $id_lista) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista);
            if ($idProveedor <= 0 || $idLista <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista o proveedor invalido", "depurar" => null);
            }

            $db = $this->getConexion();
            $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
            if (!$lista) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Lista no encontrada para el proveedor", "depurar" => null);
            }

            $stmt = $db->prepare("SELECT
                    d.*,
                    s.sku AS sku_erp,
                    s.nombre AS sku_nombre,
                    s.id_producto_erp,
                    p.nombre AS producto_nombre,
                    sp.estatus AS relacion_estatus,
                    sp.costo_ultimo,
                    EXISTS (
                        SELECT 1
                        FROM erp_catalogo_sku_codigos c
                        WHERE c.id_sku = d.id_sku
                          AND c.es_principal = 1
                          AND c.estatus = 'activo'
                    ) AS tiene_codigo_principal,
                    imp.clave_producto_sat,
                    imp.clave_unidad_sat,
                    imp.objeto_impuesto,
                    imp.iva_porcentaje,
                    imp.ieps_porcentaje,
                    imp.incluye_impuestos AS sku_incluye_impuestos
                FROM erp_proveedores_listas_detalle_erp d
                INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp
                LEFT JOIN erp_catalogo_skus s ON s.id_sku = d.id_sku
                LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
                LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor = d.id_sku_proveedor
                LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku = d.id_sku
                WHERE d.id_lista_proveedor_erp = :id_lista
                  AND l.id_proveedor = :id_proveedor
                ORDER BY d.id_lista_detalle_erp DESC
                LIMIT 500");
            $stmt->execute(array(
                ":id_lista" => $idLista,
                ":id_proveedor" => $idProveedor
            ));

            $propuestas = array();
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $renglon) {
                $propuestas = array_merge($propuestas, $this->propuestasIncidenciaRenglonProveedor($idProveedor, $lista, $renglon));
            }

            $resumen = array("total" => count($propuestas), "por_tipo" => array(), "por_severidad" => array());
            foreach ($propuestas as $propuesta) {
                $tipo = $propuesta["tipo_incidencia"];
                $severidad = $propuesta["severidad"];
                $resumen["por_tipo"][$tipo] = isset($resumen["por_tipo"][$tipo]) ? $resumen["por_tipo"][$tipo] + 1 : 1;
                $resumen["por_severidad"][$severidad] = isset($resumen["por_severidad"][$severidad]) ? $resumen["por_severidad"][$severidad] + 1 : 1;
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Dry-run de incidencias de Proveedores consultado",
                "depurar" => array(
                    "sin_escrituras" => true,
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "resumen" => $resumen,
                    "propuestas" => $propuestas,
                    "limites" => array(
                        "No crea incidencias reales.",
                        "No modifica Catalogo.",
                        "No bloquea Compras.",
                        "No resuelve ni descarta pendientes."
                    )
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function crearIncidenciaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $idDetalle = isset($datos["id_lista_detalle_erp"]) ? intval($datos["id_lista_detalle_erp"]) : 0;
            $tipo = isset($datos["tipo_incidencia"]) ? trim((string) $datos["tipo_incidencia"]) : "";
            if ($idProveedor <= 0 || $idLista <= 0 || $idDetalle <= 0 || $tipo === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Incidencia invalida", "depurar" => null);
            }

            $dryRun = $this->incidenciasListaDryRunErp($idProveedor, $idLista);
            if ($dryRun["error"]) {
                return $dryRun;
            }

            $propuesta = null;
            foreach ($dryRun["depurar"]["propuestas"] as $item) {
                if (intval($item["id_referencia"]) === $idDetalle && $item["tipo_incidencia"] === $tipo) {
                    $propuesta = $item;
                    break;
                }
            }
            if (!$propuesta) {
                $propuesta = $this->propuestaIncidenciaDirectaRenglonProveedor($db, $idProveedor, $idLista, $idDetalle, $tipo);
            }
            if (!$propuesta) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "No se pudo generar una incidencia valida para este renglon", "depurar" => null);
            }

            $antes = $this->consultarIncidenciaCatalogoPorHuella($db, $propuesta["huella"]);
            $stmt = $db->prepare("INSERT INTO erp_catalogo_incidencias_calidad
                (huella, tipo_incidencia, entidad_tipo, id_producto_erp, id_sku, id_referencia, referencia_tipo,
                 origen, severidad, titulo, descripcion, detalle_json, evidencia_json, propuesta_json, estatus, creado_por)
                VALUES
                (:huella, :tipo_incidencia, :entidad_tipo, :id_producto_erp, :id_sku, :id_referencia, :referencia_tipo,
                 'proveedores', :severidad, :titulo, :descripcion, :detalle_json, :evidencia_json, :propuesta_json, 'pendiente', :creado_por)
                ON DUPLICATE KEY UPDATE
                    severidad = VALUES(severidad),
                    titulo = VALUES(titulo),
                    descripcion = VALUES(descripcion),
                    detalle_json = VALUES(detalle_json),
                    evidencia_json = VALUES(evidencia_json),
                    propuesta_json = VALUES(propuesta_json),
                    estatus = IF(estatus IN ('resuelta','descartada'), estatus, 'pendiente'),
                    fecha_actualizacion = CURRENT_TIMESTAMP");
            $stmt->execute(array(
                ":huella" => $propuesta["huella"],
                ":tipo_incidencia" => $propuesta["tipo_incidencia"],
                ":entidad_tipo" => $propuesta["entidad_tipo"],
                ":id_producto_erp" => !empty($propuesta["id_producto_erp"]) ? intval($propuesta["id_producto_erp"]) : null,
                ":id_sku" => !empty($propuesta["id_sku"]) ? intval($propuesta["id_sku"]) : null,
                ":id_referencia" => intval($propuesta["id_referencia"]),
                ":referencia_tipo" => $propuesta["referencia_tipo"],
                ":severidad" => $propuesta["severidad"],
                ":titulo" => $propuesta["titulo"],
                ":descripcion" => $propuesta["descripcion"],
                ":detalle_json" => json_encode($propuesta["detalle"], JSON_UNESCAPED_UNICODE),
                ":evidencia_json" => json_encode($propuesta["evidencia"], JSON_UNESCAPED_UNICODE),
                ":propuesta_json" => json_encode($propuesta["propuesta"], JSON_UNESCAPED_UNICODE),
                ":creado_por" => intval($id_usuario) ?: null
            ));
            $despues = $this->consultarIncidenciaCatalogoPorHuella($db, $propuesta["huella"]);
            if (!$despues || intval(isset($despues["id_incidencia_calidad"]) ? $despues["id_incidencia_calidad"] : 0) <= 0) {
                return array("error" => true, "tipo" => "danger", "mensaje" => "No se pudo confirmar la incidencia en Catalogo", "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "huella" => $propuesta["huella"]
                ));
            }
            $idNotificacion = $this->registrarNotificacionIncidenciaCatalogoProveedor($db, $propuesta, $despues, $idProveedor, $idLista, $idDetalle, $id_usuario);

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => $antes ? "Incidencia de Catalogo actualizada desde Proveedores" : "Incidencia de Catalogo creada desde Proveedores",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "id_lista_detalle_erp" => $idDetalle,
                    "id_incidencia_calidad" => isset($despues["id_incidencia_calidad"]) ? intval($despues["id_incidencia_calidad"]) : null,
                    "id_notificacion" => $idNotificacion,
                    "huella" => $propuesta["huella"],
                    "antes" => $antes,
                    "despues" => $despues
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function listarIncidenciasProveedorErp($id_proveedor, $id_lista = 0) {
        try {
            $idProveedor = intval($id_proveedor);
            $idLista = intval($id_lista);
            if ($idProveedor <= 0) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Proveedor invalido", "depurar" => null);
            }

            $db = $this->getConexion();
            $params = array(":id_proveedor" => $idProveedor);
            $whereLista = "";
            if ($idLista > 0) {
                $whereLista = " AND l.id_lista_proveedor_erp = :id_lista";
                $params[":id_lista"] = $idLista;
            }

            $stmt = $db->prepare("SELECT
                    i.*,
                    d.id_lista_detalle_erp,
                    d.sku_proveedor,
                    d.codigo_barras,
                    d.codigo_interno,
                    d.descripcion_proveedor,
                    d.costo,
                    d.moneda,
                    l.id_lista_proveedor_erp,
                    l.nombre_lista,
                    s.sku AS sku_erp,
                    s.nombre AS sku_nombre
                FROM erp_catalogo_incidencias_calidad i
                LEFT JOIN erp_proveedores_listas_detalle_erp d
                    ON i.referencia_tipo = 'erp_proveedores_listas_detalle_erp'
                   AND i.id_referencia = d.id_lista_detalle_erp
                LEFT JOIN erp_proveedores_listas_erp l
                    ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp
                LEFT JOIN erp_catalogo_skus s
                    ON s.id_sku = i.id_sku
                WHERE i.origen = 'proveedores'
                  AND (
                    l.id_proveedor = :id_proveedor
                    OR i.detalle_json LIKE :id_proveedor_json
                    OR i.detalle_json LIKE :id_proveedor_texto
                  )
                  " . $whereLista . "
                ORDER BY FIELD(i.estatus, 'pendiente', 'en_revision', 'bloqueada', 'resuelta', 'descartada'),
                         COALESCE(i.fecha_actualizacion, i.fecha_registro) DESC
                LIMIT 500");
            $params[":id_proveedor_json"] = '%"id_proveedor":' . $idProveedor . '%';
            $params[":id_proveedor_texto"] = '%"id_proveedor":"' . $idProveedor . '"%';
            $stmt->execute($params);
            $incidencias = array();
            $resumen = array("total" => 0, "abiertas" => 0, "cerradas" => 0, "por_estatus" => array(), "por_severidad" => array());
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
                $incidencia = $this->normalizarIncidenciaProveedorErp($fila);
                $incidencias[] = $incidencia;
                $estatus = isset($incidencia["estatus"]) ? $incidencia["estatus"] : "";
                $severidad = isset($incidencia["severidad"]) ? $incidencia["severidad"] : "";
                $resumen["total"]++;
                if (in_array($estatus, array("resuelta", "descartada"), true)) {
                    $resumen["cerradas"]++;
                } else {
                    $resumen["abiertas"]++;
                }
                $resumen["por_estatus"][$estatus] = isset($resumen["por_estatus"][$estatus]) ? $resumen["por_estatus"][$estatus] + 1 : 1;
                $resumen["por_severidad"][$severidad] = isset($resumen["por_severidad"][$severidad]) ? $resumen["por_severidad"][$severidad] + 1 : 1;
            }

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Incidencias reales de Proveedores consultadas",
                "depurar" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "resumen" => $resumen,
                    "incidencias" => $incidencias
                )
            );
        } catch (Exception $e) {
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    public function resolverIncidenciaProveedorErp($datos, $id_usuario) {
        $db = $this->getConexion();
        try {
            $idIncidencia = isset($datos["id_incidencia_calidad"]) ? intval($datos["id_incidencia_calidad"]) : 0;
            $idProveedor = isset($datos["id_proveedor"]) ? intval($datos["id_proveedor"]) : 0;
            $idLista = isset($datos["id_lista_proveedor_erp"]) ? intval($datos["id_lista_proveedor_erp"]) : 0;
            $estatus = isset($datos["estatus"]) ? trim((string) $datos["estatus"]) : "";
            $motivo = isset($datos["motivo"]) ? trim((string) $datos["motivo"]) : "";
            if ($idIncidencia <= 0 || $idProveedor <= 0 || !in_array($estatus, array("resuelta", "descartada"), true)) {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Incidencia o estatus invalido", "depurar" => null);
            }
            if ($motivo === "") {
                return array("error" => true, "tipo" => "warning", "mensaje" => "Captura el motivo de la decision", "depurar" => null);
            }

            $db->beginTransaction();
            $stmt = $db->prepare("SELECT
                    i.*,
                    d.id_lista_detalle_erp,
                    l.id_proveedor,
                    l.id_lista_proveedor_erp
                FROM erp_catalogo_incidencias_calidad i
                LEFT JOIN erp_proveedores_listas_detalle_erp d
                    ON i.referencia_tipo = 'erp_proveedores_listas_detalle_erp'
                   AND i.id_referencia = d.id_lista_detalle_erp
                LEFT JOIN erp_proveedores_listas_erp l
                    ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp
                WHERE i.id_incidencia_calidad = :id
                FOR UPDATE");
            $stmt->execute(array(":id" => $idIncidencia));
            $actual = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$actual) {
                throw new Exception("Incidencia no encontrada");
            }
            if ((string) $actual["origen"] !== "proveedores") {
                throw new Exception("La incidencia no pertenece al flujo de Proveedores");
            }
            if (in_array((string) $actual["estatus"], array("resuelta", "descartada"), true)) {
                throw new Exception("La incidencia ya esta cerrada");
            }
            if (!$this->incidenciaProveedorPerteneceAlContextoErp($actual, $idProveedor, $idLista)) {
                throw new Exception("La incidencia no corresponde al proveedor o lista indicada");
            }

            $resolucion = array(
                "estatus_anterior" => $actual["estatus"],
                "estatus_nuevo" => $estatus,
                "motivo" => $motivo,
                "usuario_id" => intval($id_usuario) ?: null,
                "fecha" => date("c"),
                "origen_resolucion" => "proveedores",
                "detalle" => array(
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista ?: null,
                    "id_referencia" => isset($actual["id_referencia"]) ? intval($actual["id_referencia"]) : null,
                    "referencia_tipo" => isset($actual["referencia_tipo"]) ? $actual["referencia_tipo"] : null
                )
            );
            $stmt = $db->prepare("UPDATE erp_catalogo_incidencias_calidad SET
                    estatus = :estatus,
                    resolucion_json = :resolucion_json,
                    resuelto_por = :resuelto_por,
                    fecha_resolucion = CURRENT_TIMESTAMP,
                    fecha_actualizacion = CURRENT_TIMESTAMP
                WHERE id_incidencia_calidad = :id");
            $stmt->execute(array(
                ":estatus" => $estatus,
                ":resolucion_json" => json_encode($resolucion, JSON_UNESCAPED_UNICODE),
                ":resuelto_por" => intval($id_usuario) ?: null,
                ":id" => $idIncidencia
            ));
            $despues = $this->consultarIncidenciaCatalogoPorId($db, $idIncidencia);
            $db->commit();

            return array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => $estatus === "resuelta" ? "Incidencia marcada como resuelta" : "Incidencia descartada con motivo",
                "depurar" => array(
                    "id_incidencia_calidad" => $idIncidencia,
                    "id_proveedor" => $idProveedor,
                    "id_lista_proveedor_erp" => $idLista,
                    "antes" => array(
                        "estatus" => $actual["estatus"],
                        "resolucion_json" => $actual["resolucion_json"]
                    ),
                    "despues" => array(
                        "estatus" => isset($despues["estatus"]) ? $despues["estatus"] : $estatus,
                        "resolucion_json" => isset($despues["resolucion_json"]) ? $despues["resolucion_json"] : null
                    )
                )
            );
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => null);
        }
    }

    private function consultarIncidenciaCatalogoPorHuella($db, $huella) {
        $stmt = $db->prepare("SELECT * FROM erp_catalogo_incidencias_calidad WHERE huella = :huella LIMIT 1");
        $stmt->execute(array(":huella" => $huella));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function consultarIncidenciaCatalogoPorId($db, $idIncidencia) {
        $stmt = $db->prepare("SELECT * FROM erp_catalogo_incidencias_calidad WHERE id_incidencia_calidad = :id LIMIT 1");
        $stmt->execute(array(":id" => intval($idIncidencia)));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ? $fila : null;
    }

    private function propuestaIncidenciaDirectaRenglonProveedor($db, $idProveedor, $idLista, $idDetalle, $tipo) {
        $lista = $this->consultarListaProveedorErp($db, $idLista, $idProveedor);
        if (!$lista) {
            return null;
        }
        $stmt = $db->prepare("SELECT
                d.*,
                s.sku AS sku_erp,
                s.nombre AS sku_nombre,
                s.id_producto_erp,
                p.nombre AS producto_nombre,
                sp.estatus AS relacion_estatus,
                sp.costo_ultimo,
                EXISTS (
                    SELECT 1
                    FROM erp_catalogo_sku_codigos c
                    WHERE c.id_sku = d.id_sku
                      AND c.es_principal = 1
                      AND c.estatus = 'activo'
                ) AS tiene_codigo_principal,
                imp.clave_producto_sat,
                imp.clave_unidad_sat,
                imp.objeto_impuesto,
                imp.iva_porcentaje,
                imp.ieps_porcentaje,
                imp.incluye_impuestos AS sku_incluye_impuestos
            FROM erp_proveedores_listas_detalle_erp d
            INNER JOIN erp_proveedores_listas_erp l ON l.id_lista_proveedor_erp = d.id_lista_proveedor_erp
            LEFT JOIN erp_catalogo_skus s ON s.id_sku = d.id_sku
            LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku_proveedor = d.id_sku_proveedor
            LEFT JOIN erp_catalogo_sku_impuestos imp ON imp.id_sku = d.id_sku
            WHERE d.id_lista_detalle_erp = :id_detalle
              AND d.id_lista_proveedor_erp = :id_lista
              AND l.id_proveedor = :id_proveedor
            LIMIT 1");
        $stmt->execute(array(
            ":id_detalle" => intval($idDetalle),
            ":id_lista" => intval($idLista),
            ":id_proveedor" => intval($idProveedor)
        ));
        $renglon = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$renglon) {
            return null;
        }

        $permitidos = array(
            "proveedor_sku_sin_match" => array("alta", "Producto proveedor sin SKU ERP confiable", "Catalogo debe revisar si existe o crear un producto/SKU temporal antes del matching.", "solicitar_revision_o_alta_temporal_catalogo"),
            "proveedor_match_ambiguo" => array("media", "Renglon con match ambiguo", "Revisar candidatos antes de crear relacion proveedor-SKU.", "resolver_match_ambiguo")
        );
        if (!isset($permitidos[$tipo])) {
            return null;
        }

        $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
        $estadoMatch = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        if ($tipo === "proveedor_sku_sin_match" && $idSku > 0 && $estadoMatch !== "sin_match") {
            return null;
        }
        if ($tipo === "proveedor_match_ambiguo" && $estadoMatch !== "ambiguo") {
            return null;
        }

        $cfg = $permitidos[$tipo];
        return $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, $tipo, $cfg[0], $cfg[1], $cfg[2], $cfg[3]);
    }

    private function resolverNotificacionMatchingCatalogoProveedor($db, $idProveedor, $idLista, $idDetalle, $idSku) {
        try {
            $stmt = $db->prepare("UPDATE erp_notificaciones
                SET estatus='resuelta', fecha_resolucion=NOW(), fecha_actualizacion=NOW()
                WHERE tipo='catalogo_sku_temporal_creado_proveedor_matching'
                  AND estatus IN ('pendiente','en_revision','bloqueada')
                  AND payload_json LIKE :id_proveedor
                  AND payload_json LIKE :id_lista
                  AND payload_json LIKE :id_detalle
                  AND payload_json LIKE :id_sku");
            $stmt->execute(array(
                ":id_proveedor" => '%"id_proveedor":' . intval($idProveedor) . '%',
                ":id_lista" => '%"id_lista_proveedor_erp":' . intval($idLista) . '%',
                ":id_detalle" => '%"id_lista_detalle_erp":' . intval($idDetalle) . '%',
                ":id_sku" => '%"id_sku":' . intval($idSku) . '%'
            ));
            return intval($stmt->rowCount());
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: cerrar la incidencia de Catalogo creada desde Proveedores cuando ya existe relacion proveedor-SKU aplicada.
     * Impacto: Proveedores/Catalogo; elimina alertas abiertas solo despues del cierre operativo real del matching.
     * Contrato: resuelve incidencias `proveedor_sku_sin_match` abiertas para el renglon y SKU indicados.
     */
    private function resolverIncidenciaCatalogoProveedorPorRelacion($db, $idProveedor, $idLista, $idDetalle, $idSku, $idSkuProveedor, $idUsuario) {
        try {
            $resolucion = array(
                "accion" => "proveedor_sku_relacionado",
                "id_proveedor" => intval($idProveedor),
                "id_lista_proveedor_erp" => intval($idLista),
                "id_lista_detalle_erp" => intval($idDetalle),
                "id_sku" => intval($idSku),
                "id_sku_proveedor" => intval($idSkuProveedor),
                "usuario_id" => intval($idUsuario) ?: null,
                "fecha" => date("c"),
                "nota" => "Relacion proveedor-SKU aplicada desde Proveedores; se cierra la incidencia de Catalogo."
            );
            $stmt = $db->prepare("UPDATE erp_catalogo_incidencias_calidad
                SET estatus='resuelta',
                    resolucion_json=:resolucion,
                    resuelto_por=:usuario,
                    fecha_resolucion=NOW(),
                    fecha_actualizacion=NOW()
                WHERE origen='proveedores'
                  AND tipo_incidencia='proveedor_sku_sin_match'
                  AND referencia_tipo='erp_proveedores_listas_detalle_erp'
                  AND id_referencia=:id_detalle
                  AND id_sku=:id_sku
                  AND estatus IN ('pendiente','en_revision','bloqueada')");
            $stmt->execute(array(
                ":resolucion" => json_encode($resolucion, JSON_UNESCAPED_UNICODE),
                ":usuario" => intval($idUsuario) ?: null,
                ":id_detalle" => intval($idDetalle),
                ":id_sku" => intval($idSku)
            ));
            return intval($stmt->rowCount());
        } catch (Exception $e) {
            return 0;
        }
    }

    private function registrarNotificacionIncidenciaCatalogoProveedor($db, $propuesta, $incidencia, $idProveedor, $idLista, $idDetalle, $idUsuario) {
        try {
            if (!is_array($incidencia) || intval(isset($incidencia["id_incidencia_calidad"]) ? $incidencia["id_incidencia_calidad"] : 0) <= 0) {
                return 0;
            }
            require_once __DIR__ . "/NotificacionesErp.php";
            $notificaciones = new NotificacionesErp();
            $evidencia = isset($propuesta["evidencia"]) && is_array($propuesta["evidencia"]) ? $propuesta["evidencia"] : array();
            $renglon = isset($evidencia["renglon"]) && is_array($evidencia["renglon"]) ? $evidencia["renglon"] : array();
            $skuProveedor = trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : ""));
            $descripcion = trim((string) (isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : ""));
            $huella = isset($propuesta["huella"]) ? trim((string) $propuesta["huella"]) : "";
            $prioridad = isset($propuesta["severidad"]) && in_array($propuesta["severidad"], array("bloqueante", "alta"), true) ? "alta" : "normal";

            return $notificaciones->guardarOperativaEnConexion($db, array(
                "tipo" => "proveedor_producto_pendiente_alta_catalogo",
                "modulo_origen" => "proveedores",
                "entidad_origen" => "erp_catalogo_incidencias_calidad",
                "id_entidad_origen" => intval($incidencia["id_incidencia_calidad"]),
                "area_responsable" => "catalogo",
                "permiso_requerido" => "catalogo.editar",
                "titulo" => "Producto proveedor pendiente de alta en Catalogo",
                "descripcion" => "Proveedor reporta producto sin SKU ERP confiable" . ($skuProveedor !== "" ? ": " . $skuProveedor : "") . ($descripcion !== "" ? " - " . $descripcion : ""),
                "prioridad" => $prioridad,
                "url_accion" => "/catalogoerp",
                "payload_json" => array(
                    "huella" => $huella,
                    "id_incidencia_calidad" => intval($incidencia["id_incidencia_calidad"]),
                    "id_proveedor" => intval($idProveedor),
                    "id_lista_proveedor_erp" => intval($idLista),
                    "id_lista_detalle_erp" => intval($idDetalle),
                    "tipo_incidencia" => isset($propuesta["tipo_incidencia"]) ? $propuesta["tipo_incidencia"] : "",
                    "sku_proveedor" => $skuProveedor,
                    "descripcion_proveedor" => $descripcion,
                    "accion_sugerida" => isset($propuesta["propuesta"]["accion"]) ? $propuesta["propuesta"]["accion"] : "crear_o_relacionar_sku_erp"
                ),
                "creado_por" => intval($idUsuario) ?: null
            ));
        } catch (Exception $e) {
            return 0;
        }
    }

    private function normalizarIncidenciaProveedorErp($fila) {
        $detalle = $this->jsonProveedorErp(isset($fila["detalle_json"]) ? $fila["detalle_json"] : "");
        $evidencia = $this->jsonProveedorErp(isset($fila["evidencia_json"]) ? $fila["evidencia_json"] : "");
        $propuesta = $this->jsonProveedorErp(isset($fila["propuesta_json"]) ? $fila["propuesta_json"] : "");
        $resolucion = $this->jsonProveedorErp(isset($fila["resolucion_json"]) ? $fila["resolucion_json"] : "");
        return array(
            "id_incidencia_calidad" => intval($fila["id_incidencia_calidad"]),
            "huella" => isset($fila["huella"]) ? $fila["huella"] : "",
            "tipo_incidencia" => isset($fila["tipo_incidencia"]) ? $fila["tipo_incidencia"] : "",
            "entidad_tipo" => isset($fila["entidad_tipo"]) ? $fila["entidad_tipo"] : "",
            "id_producto_erp" => isset($fila["id_producto_erp"]) ? intval($fila["id_producto_erp"]) : 0,
            "id_sku" => isset($fila["id_sku"]) ? intval($fila["id_sku"]) : 0,
            "id_referencia" => isset($fila["id_referencia"]) ? intval($fila["id_referencia"]) : 0,
            "referencia_tipo" => isset($fila["referencia_tipo"]) ? $fila["referencia_tipo"] : "",
            "origen" => isset($fila["origen"]) ? $fila["origen"] : "",
            "severidad" => isset($fila["severidad"]) ? $fila["severidad"] : "",
            "titulo" => isset($fila["titulo"]) ? $fila["titulo"] : "",
            "descripcion" => isset($fila["descripcion"]) ? $fila["descripcion"] : "",
            "estatus" => isset($fila["estatus"]) ? $fila["estatus"] : "",
            "detalle" => $detalle,
            "evidencia" => $evidencia,
            "propuesta" => $propuesta,
            "resolucion" => $resolucion,
            "fecha_registro" => isset($fila["fecha_registro"]) ? $fila["fecha_registro"] : null,
            "fecha_actualizacion" => isset($fila["fecha_actualizacion"]) ? $fila["fecha_actualizacion"] : null,
            "fecha_resolucion" => isset($fila["fecha_resolucion"]) ? $fila["fecha_resolucion"] : null,
            "referencia" => array(
                "id_lista_detalle_erp" => isset($fila["id_lista_detalle_erp"]) ? intval($fila["id_lista_detalle_erp"]) : intval(isset($fila["id_referencia"]) ? $fila["id_referencia"] : 0),
                "id_lista_proveedor_erp" => isset($fila["id_lista_proveedor_erp"]) ? intval($fila["id_lista_proveedor_erp"]) : 0,
                "nombre_lista" => isset($fila["nombre_lista"]) ? $fila["nombre_lista"] : "",
                "sku_proveedor" => isset($fila["sku_proveedor"]) ? $fila["sku_proveedor"] : "",
                "codigo_barras" => isset($fila["codigo_barras"]) ? $fila["codigo_barras"] : "",
                "codigo_interno" => isset($fila["codigo_interno"]) ? $fila["codigo_interno"] : "",
                "descripcion_proveedor" => isset($fila["descripcion_proveedor"]) ? $fila["descripcion_proveedor"] : "",
                "costo" => isset($fila["costo"]) ? $fila["costo"] : null,
                "moneda" => isset($fila["moneda"]) ? $fila["moneda"] : "",
                "sku_erp" => isset($fila["sku_erp"]) ? $fila["sku_erp"] : "",
                "sku_nombre" => isset($fila["sku_nombre"]) ? $fila["sku_nombre"] : ""
            )
        );
    }

    private function jsonProveedorErp($json) {
        if (!is_string($json) || trim($json) === "") {
            return array();
        }
        $datos = json_decode($json, true);
        return is_array($datos) ? $datos : array();
    }

    private function incidenciaProveedorPerteneceAlContextoErp($incidencia, $idProveedor, $idLista) {
        $idProveedorReal = intval(isset($incidencia["id_proveedor"]) ? $incidencia["id_proveedor"] : 0);
        $idListaReal = intval(isset($incidencia["id_lista_proveedor_erp"]) ? $incidencia["id_lista_proveedor_erp"] : 0);
        if ($idProveedorReal > 0) {
            return $idProveedorReal === intval($idProveedor) && ($idLista <= 0 || $idListaReal === intval($idLista));
        }
        $detalle = $this->jsonProveedorErp(isset($incidencia["detalle_json"]) ? $incidencia["detalle_json"] : "");
        $idProveedorDetalle = intval(isset($detalle["id_proveedor"]) ? $detalle["id_proveedor"] : 0);
        $idListaDetalle = intval(isset($detalle["id_lista_proveedor_erp"]) ? $detalle["id_lista_proveedor_erp"] : 0);
        return $idProveedorDetalle === intval($idProveedor) && ($idLista <= 0 || $idListaDetalle === intval($idLista));
    }

    private function propuestasIncidenciaRenglonProveedor($idProveedor, $lista, $renglon) {
        $propuestas = array();
        $idDetalle = intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0);
        $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
        $estadoMatch = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        $esOperativo = $this->esRenglonOperativoProveedorErp($renglon);

        if ($idSku <= 0 || $estadoMatch === "sin_match") {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_sku_sin_match", "alta", "Producto proveedor sin SKU ERP confiable", "Si se quiere evaluar para compra, Catalogo debe revisar si existe o crear un producto/SKU temporal antes del matching.", "solicitar_revision_o_alta_temporal_catalogo");
            return $propuestas;
        }
        if ($estadoMatch === "ambiguo") {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_match_ambiguo", "media", "Renglon con match ambiguo", "Revisar candidatos antes de crear relacion proveedor-SKU.", "resolver_match_ambiguo");
            return $propuestas;
        }
        if (!$esOperativo) {
            return $propuestas;
        }
        if (intval(isset($renglon["id_unidad_compra"]) ? $renglon["id_unidad_compra"] : 0) <= 0 || floatval(isset($renglon["factor_conversion"]) ? $renglon["factor_conversion"] : 0) <= 0) {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_unidad_factor_dudoso", "alta", "Unidad o factor de compra incompleto", "Validar unidad de compra y factor antes de aplicar relacion o costo.", "validar_unidad_factor");
        }
        if (floatval(isset($renglon["costo"]) ? $renglon["costo"] : 0) <= 0 || trim((string) (isset($renglon["moneda"]) ? $renglon["moneda"] : "")) === "" || !isset($renglon["costo_incluye_impuestos"]) || $renglon["costo_incluye_impuestos"] === null || trim((string) $renglon["costo_incluye_impuestos"]) === "") {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_costo_dudoso", "media", "Costo de proveedor incompleto", "Revisar costo, moneda y bandera de impuestos antes de autorizar.", "revisar_costo_y_moneda");
        }
        if ($idSku > 0 && intval(isset($renglon["tiene_codigo_principal"]) ? $renglon["tiene_codigo_principal"] : 0) <= 0) {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_sku_sin_codigo_confiable", "media", "SKU ERP sin codigo principal activo", "Catalogo debe capturar codigo principal para busqueda y conciliacion.", "capturar_codigo_principal");
        }
        if ($idSku > 0 && (
            trim((string) (isset($renglon["clave_producto_sat"]) ? $renglon["clave_producto_sat"] : "")) === "" ||
            trim((string) (isset($renglon["clave_unidad_sat"]) ? $renglon["clave_unidad_sat"] : "")) === "" ||
            trim((string) (isset($renglon["objeto_impuesto"]) ? $renglon["objeto_impuesto"] : "")) === "" ||
            !isset($renglon["iva_porcentaje"]) || $renglon["iva_porcentaje"] === null ||
            !isset($renglon["ieps_porcentaje"]) || $renglon["ieps_porcentaje"] === null ||
            !isset($renglon["sku_incluye_impuestos"]) || $renglon["sku_incluye_impuestos"] === null
        )) {
            $propuestas[] = $this->propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, "proveedor_sku_fiscal_incompleto", "bloqueante", "SKU ERP con fiscal incompleto", "Catalogo debe completar datos fiscales antes de usarlo como comprable confiable.", "capturar_fiscal_sku");
        }

        return $propuestas;
    }

    private function esRenglonOperativoProveedorErp($renglon) {
        $estadoMatch = strtolower(trim((string) (isset($renglon["estado_match"]) ? $renglon["estado_match"] : "")));
        return intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0) > 0
            || intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0) > 0
            || in_array($estadoMatch, array("match_seleccionado", "relacionado", "relacion_aplicada", "costo_aplicado"), true);
    }

    private function propuestaIncidenciaProveedor($idProveedor, $lista, $renglon, $tipo, $severidad, $titulo, $descripcion, $accion) {
        $idDetalle = intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0);
        $idSku = intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0);
        $idProducto = intval(isset($renglon["id_producto_erp"]) ? $renglon["id_producto_erp"] : 0);
        $huellaBase = "proveedores|" . $tipo . "|erp_proveedores_listas_detalle_erp|" . $idDetalle . "|" . $idSku;
        return array(
            "huella_base" => $huellaBase,
            "huella" => hash("sha256", $huellaBase),
            "tipo_incidencia" => $tipo,
            "entidad_tipo" => $idSku > 0 ? "sku" : "proveedor_lista_renglon",
            "id_producto_erp" => $idProducto ?: null,
            "id_sku" => $idSku ?: null,
            "id_referencia" => $idDetalle,
            "referencia_tipo" => "erp_proveedores_listas_detalle_erp",
            "origen" => "proveedores",
            "severidad" => $severidad,
            "titulo" => $titulo,
            "descripcion" => $descripcion,
            "detalle" => array(
                "id_proveedor" => intval($idProveedor),
                "id_lista_proveedor_erp" => intval(isset($lista["id_lista_proveedor_erp"]) ? $lista["id_lista_proveedor_erp"] : 0),
                "estado_match" => isset($renglon["estado_match"]) ? $renglon["estado_match"] : null,
                "criterio_match" => isset($renglon["criterio_match"]) ? $renglon["criterio_match"] : null,
                "accion_sugerida" => $accion
            ),
            "evidencia" => array(
                "lista" => array(
                    "nombre_lista" => isset($lista["nombre_lista"]) ? $lista["nombre_lista"] : null,
                    "version_lista" => isset($lista["version_lista"]) ? $lista["version_lista"] : null,
                    "vigencia_desde" => isset($lista["vigencia_desde"]) ? $lista["vigencia_desde"] : null,
                    "vigencia_hasta" => isset($lista["vigencia_hasta"]) ? $lista["vigencia_hasta"] : null
                ),
                "renglon" => array(
                    "id_lista_detalle_erp" => $idDetalle,
                    "sku_proveedor" => isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : null,
                    "codigo_barras" => isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : null,
                    "codigo_interno" => isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : null,
                    "descripcion_proveedor" => isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : null,
                    "costo" => isset($renglon["costo"]) ? $renglon["costo"] : null,
                    "moneda" => isset($renglon["moneda"]) ? $renglon["moneda"] : null,
                    "id_sku" => $idSku ?: null,
                    "sku_erp" => isset($renglon["sku_erp"]) ? $renglon["sku_erp"] : null,
                    "sku_nombre" => isset($renglon["sku_nombre"]) ? $renglon["sku_nombre"] : null
                )
            ),
            "propuesta" => array("accion" => $accion)
        );
    }

    private function matchingRenglonListaDryRunErp($db, $idProveedor, $renglon) {
        $relaciones = $this->candidatosRelacionProveedorSkuErp($db, $idProveedor, $renglon);
        if (count($relaciones) > 0) {
            return $this->respuestaMatchingRenglonErp($renglon, "relacionado", "relacion_activa_proveedor_sku", $relaciones);
        }

        $incidenciaSku = $this->candidatosSkuDesdeIncidenciaCatalogoProveedor($db, $renglon);
        if (count($incidenciaSku) > 0) {
            return $this->respuestaMatchingRenglonErp($renglon, "match_exacto_pendiente", "incidencia_catalogo_sku_temporal", $incidenciaSku);
        }

        $exactos = $this->candidatosSkuExactoErp($db, $renglon);
        if (count($exactos) === 1) {
            return $this->respuestaMatchingRenglonErp($renglon, "match_exacto_pendiente", "sku_o_codigo_exacto", $exactos);
        }
        if (count($exactos) > 1) {
            return $this->respuestaMatchingRenglonErp($renglon, "ambiguo", "multiples_matches_exactos", $exactos);
        }

        $posibles = $this->candidatosSkuPosibleErp($db, $renglon);
        if (count($posibles) > 0) {
            return $this->respuestaMatchingRenglonErp($renglon, count($posibles) > 1 ? "ambiguo" : "match_posible", "nombre_o_descripcion_posible", $posibles);
        }

        return $this->respuestaMatchingRenglonErp($renglon, "sin_match", "sin_candidato_confiable", array());
    }

    private function candidatosRelacionProveedorSkuErp($db, $idProveedor, $renglon) {
        $stmt = $db->prepare("SELECT
                s.id_sku,
                s.sku,
                s.nombre,
                sp.id_sku_proveedor,
                sp.sku_proveedor,
                sp.estatus,
                'relacion_activa_proveedor_sku' AS criterio
            FROM erp_catalogo_sku_proveedores sp
            INNER JOIN erp_catalogo_skus s ON s.id_sku = sp.id_sku AND s.estatus <> 'fusionado'
            WHERE sp.id_proveedor = :id_proveedor
              AND sp.estatus = 'activo'
              AND (
                (:id_sku_proveedor_cmp > 0 AND sp.id_sku_proveedor = :id_sku_proveedor_val)
                OR (:id_sku_cmp > 0 AND sp.id_sku = :id_sku_val)
                OR (:sku_proveedor_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:sku_proveedor_val)))
                OR (:codigo_barras_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:codigo_barras_val)))
                OR (:codigo_interno_cmp <> '' AND LOWER(TRIM(sp.sku_proveedor)) = LOWER(TRIM(:codigo_interno_val)))
              )
            ORDER BY sp.es_preferido DESC, sp.id_sku_proveedor DESC
            LIMIT 5");
        $stmt->execute(array(
            ":id_proveedor" => intval($idProveedor),
            ":id_sku_proveedor_cmp" => intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0),
            ":id_sku_proveedor_val" => intval(isset($renglon["id_sku_proveedor"]) ? $renglon["id_sku_proveedor"] : 0),
            ":id_sku_cmp" => intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0),
            ":id_sku_val" => intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0),
            ":sku_proveedor_cmp" => trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "")),
            ":sku_proveedor_val" => trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "")),
            ":codigo_barras_cmp" => trim((string) (isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "")),
            ":codigo_barras_val" => trim((string) (isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "")),
            ":codigo_interno_cmp" => trim((string) (isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : "")),
            ":codigo_interno_val" => trim((string) (isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : ""))
        ));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function candidatosSkuDesdeIncidenciaCatalogoProveedor($db, $renglon) {
        $idDetalle = intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0);
        if ($idDetalle <= 0) {
            return array();
        }
        $stmt = $db->prepare("SELECT
                s.id_sku,
                s.sku,
                s.nombre,
                NULL AS id_sku_proveedor,
                NULL AS sku_proveedor,
                s.estatus,
                'incidencia_catalogo_sku_temporal' AS criterio,
                i.id_incidencia_calidad
            FROM erp_catalogo_incidencias_calidad i
            INNER JOIN erp_catalogo_skus s ON s.id_sku = i.id_sku AND s.estatus <> 'fusionado'
            WHERE i.origen = 'proveedores'
              AND i.tipo_incidencia = 'proveedor_sku_sin_match'
              AND i.referencia_tipo = 'erp_proveedores_listas_detalle_erp'
              AND i.id_referencia = :id_detalle
              AND i.estatus IN ('pendiente', 'en_revision', 'bloqueada')
            ORDER BY FIELD(i.estatus, 'en_revision', 'pendiente', 'bloqueada'), i.id_incidencia_calidad DESC
            LIMIT 5");
        $stmt->execute(array(":id_detalle" => $idDetalle));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-20
     * Proposito: detectar matches exactos comparando identificadores del proveedor contra SKU ERP y codigos alternos.
     * Impacto: Proveedores/Catalogo; mejora matching sin crear relaciones ni modificar datos.
     * Contrato: solo devuelve candidatos; la aplicacion de relacion sigue en flujo autorizado.
     */
    private function candidatosSkuExactoErp($db, $renglon) {
        $stmt = $db->prepare("SELECT DISTINCT
                s.id_sku,
                s.sku,
                s.nombre,
                NULL AS id_sku_proveedor,
                NULL AS sku_proveedor,
                s.estatus,
                CASE
                  WHEN LOWER(TRIM(s.sku)) IN (
                      LOWER(TRIM(:sku_proveedor_case)),
                      LOWER(TRIM(:codigo_barras_case)),
                      LOWER(TRIM(:codigo_interno_case))
                  ) THEN 'sku_erp_exacto'
                  ELSE 'codigo_sku_exacto'
                END AS criterio
            FROM erp_catalogo_skus s
            LEFT JOIN erp_catalogo_sku_codigos c ON c.id_sku = s.id_sku AND c.estatus = 'activo'
            WHERE s.estatus <> 'fusionado'
              AND (
                (:id_sku_cmp > 0 AND s.id_sku = :id_sku_val)
                OR (:sku_proveedor_cmp <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:sku_proveedor_val)))
                OR (:sku_proveedor_cmp <> '' AND LOWER(TRIM(c.codigo)) = LOWER(TRIM(:sku_proveedor_val)))
                OR (:codigo_barras_cmp <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:codigo_barras_val)))
                OR (:codigo_barras_cmp <> '' AND LOWER(TRIM(c.codigo)) = LOWER(TRIM(:codigo_barras_val)))
                OR (:codigo_interno_cmp <> '' AND LOWER(TRIM(s.sku)) = LOWER(TRIM(:codigo_interno_val)))
                OR (:codigo_interno_cmp <> '' AND LOWER(TRIM(c.codigo)) = LOWER(TRIM(:codigo_interno_val)))
              )
            ORDER BY s.estatus = 'activo' DESC, s.id_sku DESC
            LIMIT 5");
        $stmt->execute(array(
            ":id_sku_cmp" => intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0),
            ":id_sku_val" => intval(isset($renglon["id_sku"]) ? $renglon["id_sku"] : 0),
            ":sku_proveedor_case" => trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "")),
            ":codigo_barras_case" => trim((string) (isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "")),
            ":codigo_interno_case" => trim((string) (isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : "")),
            ":sku_proveedor_cmp" => trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "")),
            ":sku_proveedor_val" => trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : "")),
            ":codigo_barras_cmp" => trim((string) (isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "")),
            ":codigo_barras_val" => trim((string) (isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : "")),
            ":codigo_interno_cmp" => trim((string) (isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : "")),
            ":codigo_interno_val" => trim((string) (isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : ""))
        ));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function candidatosSkuPosibleErp($db, $renglon) {
        $texto = trim((string) (isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : ""));
        if ($texto === "") {
            $texto = trim((string) (isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : ""));
        }
        $palabras = preg_split('/\s+/', $texto);
        $palabras = array_values(array_filter($palabras, function ($palabra) {
            return strlen($palabra) >= 4;
        }));
        if (empty($palabras)) {
            return array();
        }

        $busqueda = "%" . $palabras[0] . "%";
        $stmt = $db->prepare("SELECT
                s.id_sku,
                s.sku,
                s.nombre,
                NULL AS id_sku_proveedor,
                NULL AS sku_proveedor,
                s.estatus,
                'nombre_sku_posible' AS criterio
            FROM erp_catalogo_skus s
            WHERE s.estatus <> 'fusionado'
              AND (s.nombre LIKE :busqueda OR s.sku LIKE :busqueda)
            ORDER BY s.estatus = 'activo' DESC, s.id_sku DESC
            LIMIT 5");
        $stmt->execute(array(":busqueda" => $busqueda));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function respuestaMatchingRenglonErp($renglon, $estado, $criterio, $candidatos) {
        return array(
            "id_lista_detalle_erp" => intval(isset($renglon["id_lista_detalle_erp"]) ? $renglon["id_lista_detalle_erp"] : 0),
            "sku_proveedor" => isset($renglon["sku_proveedor"]) ? $renglon["sku_proveedor"] : null,
            "codigo_barras" => isset($renglon["codigo_barras"]) ? $renglon["codigo_barras"] : null,
            "codigo_interno" => isset($renglon["codigo_interno"]) ? $renglon["codigo_interno"] : null,
            "descripcion_proveedor" => isset($renglon["descripcion_proveedor"]) ? $renglon["descripcion_proveedor"] : null,
            "costo" => isset($renglon["costo"]) ? $renglon["costo"] : null,
            "moneda" => isset($renglon["moneda"]) ? $renglon["moneda"] : null,
            "estado_match_actual" => isset($renglon["estado_match"]) ? $renglon["estado_match"] : null,
            "estado_match" => $estado,
            "criterio_match" => $criterio,
            "candidatos" => $candidatos
        );
    }

    public function registro_lista_proveedor() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_proveedor",
            "lista",
            "estatus",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_proveedor(),
            $this->getLista(),
            $this->getEstatus(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_proveedores_listas);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function registro_lista_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_proveedor",
            "id_tipo_lista_mayoreo",
            "estatus",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_proveedor(),
            $this->getId_tipo_lista_mayoreo(),
            $this->getEstatus(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function consulta_listas_proveedores() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erppl.id_lista_proveedor",
            "erppl.id_proveedor",
            "erppl.lista",
            "erpp.proveedor"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_proveedores_listas . " erppl");
        $this->setInnerJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = erppl.id_proveedor");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_listas_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erppl.id_lista_mayoreo",
            "erppl.id_proveedor",
            "erppl.id_tipo_lista_mayoreo",
            "erpp.proveedor",
            "erppl.estatus",
            "erplmt.tipo_lista_mayoreo"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo . " erppl");
        $this->setInnerJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = erppl.id_proveedor");
        $this->setInnerJoin($this->tabla_erp_listas_mayoreo_tipos . " erplmt ON erplmt.id_tipo_lista_mayoreo = erppl.id_tipo_lista_mayoreo");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_usuarios_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "sysum.id_usuario",
            "sysum.nombres",
            "sysum.apellido_materno",
            "sysum.apellido_paterno",
            "sysuin.nombre_negocio",
            "sysum.estatus"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_sys_usuarios_mayoreo . " sysum");
        $this->setInnerJoin($this->tabla_sys_usuarios_mayoreo_informacion_negocio . " sysuin ON sysuin.id_usuario_negocio = sysum.id_usuario");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_listas_asignadas_usuario_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erpumlm.id_lista_mayoreo"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_usuarios_mayoreo_listas_mayoreo . " erpumlm");
        $this->setWhere("id_usuario_mayoreo = " . $this->getId_usuario_mayoreo());
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_listas_usuario_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erplm.id_lista_mayoreo",
            "erplmt.tipo_lista_mayoreo",
            "erpp.proveedor"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo . " erplm");
        $this->setInnerJoin($this->tabla_erp_listas_mayoreo_tipos . " erplmt ON erplmt.id_tipo_lista_mayoreo = erplm.id_tipo_lista_mayoreo");
        $this->setInnerJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = erplm.id_proveedor");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_listas_mayoreo_usuario() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erplm.id_lista_mayoreo",
            "erplmt.tipo_lista_mayoreo",
            "erpp.proveedor"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_usuarios_mayoreo_listas_mayoreo . " erpumlm");
        $this->setInnerJoin($this->tabla_erp_listas_mayoreo_tipos . " erplmt ON erplmt.id_tipo_lista_mayoreo = erplm.id_tipo_lista_mayoreo");
        $this->setInnerJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = erplm.id_proveedor");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consultar_productos_lista() {
        $this->setColumnas(array(
            "erpplp.id_producto",
            "erpplp.sku",
            "erpplp.existencia",
            "erpplp.precio_sugerido",
            "erpplp.piezas_por_caja",
            "erpplp.rotacion",
            "erpplp.nombre",
            "erpplp.costo",
            "erpplp.estatus",
            "erpplpi.tipo_imagen",
            "erpplpi.url_imagen",
            "'producto' as tipo_item"
        ));
        $this->setLeftJoin($this->tabla_proveedores_listas_productos_imagenes . " erpplpi ON erpplpi.id_producto = erpplp.id_producto");
        $this->setTabla($this->tabla_proveedores_listas_productos . " erpplp");
        $this->setWhere("id_lista_proveedor = " . $this->getId_lista_proveedor());
        return $this->listar();
    }

    public function consulta_lista_proveedor() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_lista_proveedor"
        );
        $valores_registrar = array(
            $this->getId_proveedor(),
            $this->getLista(),
            $this->getEstatus(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_proveedores_listas);
        $this->setWhere("id_proveedor = " . $this->getId_proveedor());
        $this->setAnd("lista = '" . $this->getLista() . "'");
        $respuesta = $this->buscarRegistro();
        return $respuesta;
    }

    public function consulta_lista_mayoreo() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_lista_mayoreo"
        );

//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo);
        $this->setWhere("id_proveedor = " . $this->getId_proveedor());
        $this->setAnd("id_tipo_lista_mayoreo = " . $this->getId_tipo_lista_mayoreo());
        $respuesta = $this->buscarRegistro();
        return $respuesta;
    }

    public function consultar_para_editar() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_proveedores_listas_productos);

        return $this->buscarRegistro();
    }

    public function registrar_imagen() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_producto",
            "tipo_imagen",
            "url_imagen"
        );
        $valores_registrar = array(
            $this->getId_producto(),
            $this->getTipo_imagen(),
            $this->getUrl_imagen()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setTabla($this->tabla_proveedores_listas_productos_imagenes);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function eliminar_imagen() {
        $this->setTabla($this->tabla_productos_imagenes);
        $this->setAnd("tipo_imagen = '" . $this->getTipo_imagen() . "'");
        $this->setWhere("id_producto = " . $this->getId_producto());
        $respuesta = $this->eliminar();
    }

    public function guardar_imagenes() {
        $urlRecursos = $this->recursos_productos . $this->getId_producto() . "/";
        $urlDestino = $urlRecursos . $this->getArchivo_portada();
        $archivo_guardado = false;
//    if (!file_exists($urlDestino)) {
        //validar directorio
        $urlOrigen = $this->getUrl_origen();
        if (is_dir($urlRecursos)) {
            $archivo_guardado = move_uploaded_file($this->getUrl_origen(), $urlDestino);
        } else {
//      var_dump($urlRecursos);
            $archivo_guardado = mkdir($urlRecursos, 0777, true);
            $archivo_guardado = move_uploaded_file($this->getUrl_origen(), $urlDestino);
//      var_dump($this->getUrl_origen());
//      var_dump($urlDestino);
//      var_dump($archivo_guardado);
        }
//    }
//    } else {
//      //Archivo no existe
//      $return = error(true, 'danger', 'El archivo de origen no existe');
//    }
        if ($archivo_guardado == true) {
            $return = array('error' => false, 'tipo' => 'success', 'mensaje' => 'El archivo fue guardado con éxito', 'depurar' => $urlDestino);
        } else {
            $return = array('error' => true, 'tipo' => 'danger', 'mensaje' => 'El archivo no fue guardado');
        }
        return $return;
    }

    public function registrar_producto_orden_compra() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_orden_de_compra",
            "id_producto",
            "producto",
            "codigo",
            "cantidad",
            "precio",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_orden_de_compra(),
            $this->getId_producto(),
            $this->getNombre(),
            $this->getSku(),
            $this->getExistencias(),
            $this->getCosto(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_ordenes_de_compra_productos);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function registrar_orden_compra() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_proveedor",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_proveedor(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_ordenes_de_compra);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function listar_categorias_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores_listas_productos_categorias);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_categorias . " erpc ON erpc.id_categoria = " . $this->tabla_proveedores_listas_productos_categorias . ".id_categoria");
        return $this->listar();
    }

    public function listar_imagenes() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores_listas_productos_imagenes);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->listar();
    }

    public function actualizar_lista_producto() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "nombre",
            "descripcion",
            "estatus",
            "existencia",
            "codigo_barras",
            "codigo_interno",
            "identificador",
            "piezas_por_caja",
            "rotacion",
            "porcentaje_impuesto",
            "precio_sin_impuestos",
            "porcentaje_utilidad_bruta",
            "incluye_impuesto"
        );
        $valores_registrar = array(
            $this->getNombre(),
            $this->getDescripcion(),
            $this->getEstatus(),
            $this->getExistencias(),
            $this->getCodigo_barras_base(),
            $this->getCodigo_interno(),
            $this->getIdentificador(),
            $this->getPiezas_por_caja(),
            $this->getRotacion(),
            $this->getPorcentaje_impuesto(),
            $this->getPrecio_sin_impuestos(),
            $this->getUtilidad_bruta(),
            $this->getIncluye_impuesto()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_proveedores_listas_productos);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function actualizar_categorias_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_proveedores_listas_productos_categorias);
        $columnas = array(
            "id_producto",
            "id_categoria"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_categoria()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function actualizar_estatus_lista_mayoreo() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_erp_listas_mayoreo);
        $columnas = array(
            "estatus"
        );
        $valores = array(
            $this->getEstatus()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        $this->setWhere("id_lista_mayoreo = " . $this->getId_lista_mayoreo());
        return $this->update();
    }

    public function asignar_lista_mayoreo_usuario_mayoreo() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_erp_usuarios_mayoreo_listas_mayoreo);
        $columnas = array(
            "id_usuario_mayoreo",
            "id_lista_mayoreo"
        );
        $valores = array(
            $this->getId_usuario_mayoreo(),
            $this->getId_lista_mayoreo()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);

        return $this->insertar();
    }

    public function quitar_asignacion_lista_mayoreo_usuario_mayoreo() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_erp_usuarios_mayoreo_listas_mayoreo);
        $this->setWhere("id_usuario_mayoreo = " . $this->getId_usuario_mayoreo());
        $this->setAnd("id_lista_mayoreo = " . $this->getId_lista_mayoreo());
        return $this->eliminar();
    }

    public function eliminar_categorias_producto() {
        $this->setTabla($this->tabla_proveedores_listas_productos_categorias);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function registrar_producto_lista() {
        $campos_registrar = array(
            "id_lista_proveedor",
            "marca",
            "sku",
            "existencia",
            "nombre",
            "costo",
            "estatus",
            "precio_sugerido",
            "piezas_por_caja",
            "rotacion",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_lista_proveedor(),
            $this->getMarca(),
            $this->getSku(),
            $this->getExistencias(),
            $this->getNombre(),
            $this->getCosto(),
            $this->getEstatus(),
            $this->getPrecio_sugerido(),
            $this->getPiezas_por_caja(),
            $this->getRotacion(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_proveedores_listas_productos);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    //historial costos
    public function consultar_costo_producto_lista() {
        $this->setColumnas(array(
            "costo"
        ));
        $this->setTabla($this->tabla_proveedores_listas_productos);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setAnd('id_lista_proveedor = ' . $this->getId_lista_proveedor());
        return $this->buscarRegistro();
    }

    public function registrar_producto_lista_mayoreo() {
        $campos_registrar = array(
            "id_lista_mayoreo",
            "sku",
            "precio",
            "estatus",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_lista_proveedor(),
            $this->getSku(),
            $this->getCosto(),
            $this->getEstatus(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo_productos);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function actualizar_producto_lista() {
        $campos_registrar = array(
            "marca",
            "existencia",
            "nombre",
            "costo",
            "precio_sugerido",
            "piezas_por_caja",
            "rotacion",
            "fch_m"
        );
        $valores_registrar = array(
            $this->getMarca(),
            $this->getExistencias(),
            $this->getNombre(),
            $this->getCosto(),
            $this->getPrecio_sugerido(),
            $this->getPiezas_por_caja(),
            $this->getRotacion(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_proveedores_listas_productos);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setAnd("id_lista_proveedor = " . $this->getId_lista_proveedor());
        $respuesta = $this->update();
        return $respuesta;
    }

    //consultar productos por imagen
    function listar_productos_imagen_sku() {
        $this->setColumnas(array(
            "ecomp.id_producto",
            "ecomp.codigo_barras",
            "ecomp.codigo_interno",
            "ecomp.sku",
            "ecomp.existencia",
            "ecomp.nombre",
            "ecomp.descripcion",
            "ecomp.siempre_disponible",
            "ecomp.precio_base",
            "ecomp.estatus", "ecompi.tipo_imagen",
            "ecompi.url_imagen", "'producto' as tipo_item", "ecompcv.factor", "ecompcv.id_unidad_venta", "ecompcv.solo_en_punto_de_venta",
            "erpuv.unidad_venta", "erpuv.abreviatura"));
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_productos_compra_venta . " ecompcv ON ecompcv.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        $this->setWhere("ecomp.sku = '" . $this->getSku() . "'");
        $this->setTabla($this->tabla_productos . " ecomp");

        return $this->buscarRegistro();
    }

    ///TODO
    public function registrar_historial_costo_producto_lista() {
        $campos_registrar = array(
            "id_proveedor",
            "sku",
            "costo_anterior",
            "costo_nuevo",
            "precio_sugerido",
            "precio_actual",
            "diferencia", //calcular
            "porcentaje_cambio", //calcular
            "fch_m"
        );
        $valores_registrar = array(
            $this->getId_proveedor(),
            $this->getSku(),
            $this->getCosto_anterior(),
            $this->getCosto(),
            $this->getPrecio_sugerido(),
            $this->getPrecio_actual(),
            $this->getDiferencia_costo(),
            $this->getPorcentaje_cambio(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_proveedores_listas_productos_historial_costos);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function actualizar_producto_lista_mayoreo() {
        $campos_registrar = array(
            "precio",
            "fch_m"
        );
        $valores_registrar = array(
            $this->getCosto(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_erp_listas_mayoreo_productos);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setAnd("id_lista_mayoreo = " . $this->getId_lista_proveedor());
        $respuesta = $this->update();
        return $respuesta;
    }

    public function consultar_producto_lista() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores_listas_productos);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setAnd('id_lista_proveedor = ' . $this->getId_lista_proveedor());
        return $this->buscarRegistro();
    }

    public function consultar_producto_lista_mayoreo() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_erp_listas_mayoreo_productos);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setAnd('id_lista_mayoreo = ' . $this->getId_lista_proveedor());
        return $this->buscarRegistro();
    }

    /*
     * Setters y Getters
     */

    public function getId_producto() {
        return $this->id_producto;
    }

    public function getId_producto_proveedor() {
        return $this->id_producto_proveedor;
    }

    public function getId_proveedor() {
        return $this->id_proveedor;
    }

    public function setId_producto($id_producto): void {
        $this->id_producto = $id_producto;
    }

    public function setId_producto_proveedor($id_producto_proveedor): void {
        $this->id_producto_proveedor = $id_producto_proveedor;
    }

    public function setId_proveedor($id_proveedor): void {
        $this->id_proveedor = $id_proveedor;
    }

    public function getLista() {
        return $this->lista;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function setLista($lista): void {
        $this->lista = $lista;
    }

    public function setEstatus($estatus): void {
        $this->estatus = $estatus;
    }

    public function getMarca() {
        return $this->marca;
    }

    public function getSku() {
        return $this->sku;
    }

    public function getExistencias() {
        return $this->existencias;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getCosto() {
        return $this->costo;
    }

    public function getPrecio_sugerido() {
        return $this->precio_sugerido;
    }

    public function getPiezas_por_caja() {
        return $this->piezas_por_caja;
    }

    public function getRotacion() {
        return $this->rotacion;
    }

    public function setMarca($marca): void {
        $this->marca = $marca;
    }

    public function setSku($sku): void {
        $this->sku = $sku;
    }

    public function setExistencias($existencias): void {
        $this->existencias = $existencias;
    }

    public function setNombre($nombre): void {
        $this->nombre = $nombre;
    }

    public function setCosto($costo): void {
        $this->costo = $costo;
    }

    public function setPrecio_sugerido($precio_sugerido): void {
        $this->precio_sugerido = $precio_sugerido;
    }

    public function setPiezas_por_caja($piezas_por_caja): void {
        $this->piezas_por_caja = $piezas_por_caja;
    }

    public function setRotacion($rotacion): void {
        $this->rotacion = $rotacion;
    }

    public function getId_lista_proveedor() {
        return $this->id_lista_proveedor;
    }

    public function setId_lista_proveedor($id_lista_proveedor): void {
        $this->id_lista_proveedor = $id_lista_proveedor;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function getId_categoria() {
        return $this->id_categoria;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }

    public function setId_categoria($id_categoria): void {
        $this->id_categoria = $id_categoria;
    }

    public function getUrl_origen() {
        return $this->url_origen;
    }

    public function getArchivo_portada() {
        return $this->archivo_portada;
    }

    public function setUrl_origen($url_origen): void {
        $this->url_origen = $url_origen;
    }

    public function setArchivo_portada($archivo_portada): void {
        $this->archivo_portada = $archivo_portada;
    }

    public function getTipo_imagen() {
        return $this->tipo_imagen;
    }

    public function getUrl_imagen() {
        return $this->url_imagen;
    }

    public function getCodigo_interno() {
        return $this->codigo_interno;
    }

    public function setTipo_imagen($tipo_imagen): void {
        $this->tipo_imagen = $tipo_imagen;
    }

    public function setUrl_imagen($url_imagen): void {
        $this->url_imagen = $url_imagen;
    }

    public function setCodigo_interno($codigo_interno): void {
        $this->codigo_interno = $codigo_interno;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setDescripcion($descripcion): void {
        $this->descripcion = $descripcion;
    }

    public function getCodigo_barras_base() {
        return $this->codigo_barras_base;
    }

    public function setCodigo_barras_base($codigo_barras_base): void {
        $this->codigo_barras_base = $codigo_barras_base;
    }

    public function getId_orden_de_compra() {
        return $this->id_orden_de_compra;
    }

    public function setId_orden_de_compra($id_orden_de_compra): void {
        $this->id_orden_de_compra = $id_orden_de_compra;
    }

    public function getPorcentaje_impuesto() {
        return $this->porcentaje_impuesto;
    }

    public function getPrecio_sin_impuestos() {
        return $this->precio_sin_impuestos;
    }

    public function getUtilidad_bruta() {
        return $this->utilidad_bruta;
    }

    public function getIncluye_impuesto() {
        return $this->incluye_impuesto;
    }

    public function setPorcentaje_impuesto($porcentaje_impuesto): void {
        $this->porcentaje_impuesto = $porcentaje_impuesto;
    }

    public function setPrecio_sin_impuestos($precio_sin_impuestos): void {
        $this->precio_sin_impuestos = $precio_sin_impuestos;
    }

    public function setUtilidad_bruta($utilidad_bruta): void {
        $this->utilidad_bruta = $utilidad_bruta;
    }

    public function setIncluye_impuesto($incluye_impuesto): void {
        $this->incluye_impuesto = $incluye_impuesto;
    }

    public function getBusqueda() {
        return $this->busqueda;
    }

    public function setBusqueda($busqueda): void {
        $this->busqueda = $busqueda;
    }

    public function getProveedor() {
        return $this->proveedor;
    }

    public function getTotal() {
        return $this->total;
    }

    public function setProveedor($proveedor): void {
        $this->proveedor = $proveedor;
    }

    public function setTotal($total): void {
        $this->total = $total;
    }

    public function getId_elemento() {
        return $this->id_elemento;
    }

    public function getId_proveedor_pedido() {
        return $this->id_proveedor_pedido;
    }

    public function getTipo_elemento() {
        return $this->tipo_elemento;
    }

    public function setId_elemento($id_elemento): void {
        $this->id_elemento = $id_elemento;
    }

    public function setId_proveedor_pedido($id_proveedor_pedido): void {
        $this->id_proveedor_pedido = $id_proveedor_pedido;
    }

    public function setTipo_elemento($tipo_elemento): void {
        $this->tipo_elemento = $tipo_elemento;
    }

    public function getComentario() {
        return $this->comentario;
    }

    public function setComentario($comentario): void {
        $this->comentario = $comentario;
    }

    public function getTitulo() {
        return $this->titulo;
    }

    public function setTitulo($titulo): void {
        $this->titulo = $titulo;
    }

    public function getCantidad() {
        return $this->cantidad;
    }

    public function setCantidad($cantidad): void {
        $this->cantidad = $cantidad;
    }

    public function getCuota() {
        return $this->cuota;
    }

    public function setCuota($cuota): void {
        $this->cuota = $cuota;
    }

    public function getId_tipo_lista_mayoreo() {
        return $this->id_tipo_lista_mayoreo;
    }

    public function setId_tipo_lista_mayoreo($id_tipo_lista_mayoreo): void {
        $this->id_tipo_lista_mayoreo = $id_tipo_lista_mayoreo;
    }

    public function getId_lista_mayoreo() {
        return $this->id_lista_mayoreo;
    }

    public function setId_lista_mayoreo($id_lista_mayoreo): void {
        $this->id_lista_mayoreo = $id_lista_mayoreo;
    }

    public function getId_usuario_mayoreo() {
        return $this->id_usuario_mayoreo;
    }

    public function setId_usuario_mayoreo($id_usuario_mayoreo): void {
        $this->id_usuario_mayoreo = $id_usuario_mayoreo;
    }

    public function getCosto_anterior() {
        return $this->costo_anterior;
    }

    public function getDiferencia_costo() {
        return $this->diferencia_costo;
    }

    public function getPorcentaje_cambio() {
        return $this->porcentaje_cambio;
    }

    public function setCosto_anterior($costo_nuevo): void {
        $this->costo_anterior = $costo_nuevo;
    }

    public function setDiferencia_costo($diferencia_costo): void {
        $this->diferencia_costo = $diferencia_costo;
    }

    public function setPorcentaje_cambio($porcentaje_cambio): void {
        $this->porcentaje_cambio = $porcentaje_cambio;
    }

    public function getPrecio_actual() {
        return $this->precio_actual;
    }

    public function setPrecio_actual($precio_actual): void {
        $this->precio_actual = $precio_actual;
    }
}
