<?php

class Compras extends CRUD {

    private $tabla_erp_ordenes_de_compra = "erp_ordenes_de_compra";
    private $tabla_erp_proveedores = "erp_proveedores";
    private $tabla_erp_ordenes_de_compra_productos = "erp_ordenes_de_compra_productos";
    private $tabla_erp_compras_solicitudes = "erp_compras_solicitudes";
    private $tabla_erp_compras_solicitudes_detalle = "erp_compras_solicitudes_detalle";
    private $tabla_erp_compras_ordenes = "erp_compras_ordenes";
    private $tabla_erp_compras_ordenes_detalle = "erp_compras_ordenes_detalle";
    private $tabla_erp_compras_ordenes_pagos = "erp_compras_ordenes_pagos";
    private $tabla_erp_compras_ordenes_notas_credito = "erp_compras_ordenes_notas_credito";
    private $tabla_erp_compras_ordenes_adjuntos = "erp_compras_ordenes_adjuntos";
    private $tabla_erp_compras_ordenes_productos_atencion = "erp_compras_ordenes_productos_atencion";
    private $tabla_erp_proveedores_listas_productos_revision = "erp_proveedores_listas_productos_revision";
    private $tabla_ecom_productos_revision = "ecom_productos_revision";
    private $tabla_erp_proveedores_listas_productos = "erp_proveedores_listas_productos";
    //solicitudes compra
    private $id_proveedor;
    private $folio;
    private $estatus;
    private $observaciones_solicitud;
    private $fecha_solicitud;
    private $fecha_aprobacion;
    //solicitudes_Detalle
    private $id_solicitud;
    private $id_producto;
    private $cantidad;
    private $costo_estimado;
    private $subtotal;
    private $observaciones_producto;
    //orden de compra
    private $impuestos;
    private $total;
    private $fecha_orden;
    private $fecha_estimada;
    //orden de compra detalle
    private $id_orden_compra;
    private $nombre_producto;
    private $costo_unitario;
    private $porcentaje_impuesto;
    private $cantidad_recibida;
    private $sku;
    private $descuento;

    public function cambio_estatus_solicitud() {
        $this->setColumnas(array(
            "estatus"
        ));
        $this->setColumnasValores(array(
            $this->getEstatus()
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes);
        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->update();
    }

    public function cambio_estatus_solicitud_aprobada() {
        $this->setColumnas(array(
            "estatus",
            "fecha_aprobacion"
        ));
        $this->setColumnasValores(array(
            $this->getEstatus(),
            $this->getFecha_aprobacion()
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes);
        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->update();
    }

    public function consultar_productos_lista() {
        $this->setColumnas(array(
            "erpplp.codigo",
            "erpplp.producto",
            "erpplp.cantidad",
            "erpplp.precio"
        ));

        $this->setTabla($this->tabla_erp_ordenes_de_compra_productos . " erpplp");
        $this->setWhere("id_orden_de_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function consultar_detalle_solicitud_compra() {
        $this->setColumnas(array(
            "erpplp.sku as codigo",
            "erpplp.nombre as producto",
            "erpcsd.cantidad",
            "erpcsd.costo_estimado as precio"
        ));

        $this->setTabla($this->tabla_erp_compras_solicitudes_detalle . " erpcsd");
        $this->setLeftJoin($this->tabla_erp_proveedores_listas_productos . " erpplp ON erpplp.id_producto = erpcsd.id_producto");

        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->listar();
    }

    public function consulta_ordenes_de_compras() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erpco.id_orden_compra",
            "erpco.folio",
            "erpco.id_proveedor",
            "erpco.id_solicitud",
            "erpco.total",
            "erpco.estatus",
            "erpco.fecha_orden",
            "erpco.fecha_entrega_estimada",
            "erpco.observaciones",
            "erpp.proveedor"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_compras_ordenes . " erpco");
        $this->setInnerJoin($this->tabla_erp_proveedores." erpp ON erpp.id_proveedor = erpco.id_proveedor");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consulta_solicitudes_de_compra() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "erpcs.id_solicitud",
            "erpcs.folio",
            "erpcs.estatus",
            "erpcs.observaciones",
            "erpcs.fecha_solicitud",
            "erpcs.fecha_aprobacion",
            "erpp.proveedor"
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_compras_solicitudes . " erpcs");
        $this->setInnerJoin($this->tabla_erp_proveedores . " erpp ON erpp.id_proveedor = erpcs.id_proveedor");
//        $this->setInnerJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = erpoc.id_proveedor");
        $respuesta = $this->listar();
        return $respuesta;
    }

    public function consultar_folio_solicitud() {
        $campos_registrar = array(
            "folio"
        );

//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setTabla($this->tabla_erp_compras_solicitudes);
        $this->setOrderBy("id_solicitud DESC");
        $this->setLimit(1);
        $respuesta = $this->listar();
        return $respuesta;
    }
    
    public function consultar_compra_orden() {
        $this->setColumnas(array(
            "erpcs.id_orden_compra",
            "erpcs.folio",
            "erpcs.id_proveedor",
            "erpcs.id_solicitud",
            "erpcs.subtotal",
            "erpcs.impuestos",
            "erpcs.total",
            "erpcs.estatus",
            "erpcs.fecha_orden",
            "erpcs.fecha_entrega_estimada",
            "erpcs.observaciones",
            "erpcs.folio_proveedor",
            "erpcs.id_almacen_destino",
            "erpcs.solicitante",
            "erpcs.contacto_recepcion",
            "erpcs.telefono_recepcion",
            "erpcs.direccion_entrega",
            "erpcs.descuento_global_productos",
            "erpcs.saldo_pendiente",
            "erpp.proveedor"
        ));

        $this->setTabla($this->tabla_erp_compras_ordenes." erpcs");
        $this->setInnerJoin($this->tabla_erp_proveedores." erpp ON erpp.id_proveedor = erpcs.id_proveedor");
        $this->setWhere("erpcs.id_orden_compra = " . $this->getId_orden_compra());
        return $this->buscarRegistro();
    }
    
    public function consultar_compra_orden_detalle() {
        $columnas_detalle = array(
            "erpcod.id_producto",
            "erpcod.id_sku_erp",
            "COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto, 0) as id_producto_ecom",
            "COALESCE(NULLIF(erpcod.id_producto_proveedor, 0), IF(ecomp_direct.id_producto IS NULL, erpcod.id_producto, 0)) as id_producto_proveedor",
            "erpcod.sku",
            "erpcod.nombre_producto",
            "erpcod.cantidad",
            "erpcod.costo_unitario",
            "erpcod.porcentaje_impuesto",
            "erpcod.subtotal",
            "erpcod.descuento",
            "erpcod.total",
            "erpcod.cantidad_recibida",
            "erpcod.costo_antes_impuesto"
        );

        if ($this->columnas_detalle_comerciales_existen()) {
            $columnas_detalle = array_merge($columnas_detalle, array(
                "COALESCE(NULLIF(erpcod.costo_compra, 0), erpcod.costo_unitario) as costo_compra",
                "erpcod.precio_venta",
                "erpcod.margen_actual",
                "erpcod.utilidad_actual",
                "erpcod.precio_sugerido",
                "erpcod.margen_nuevo",
                "erpcod.utilidad_nueva"
            ));
        } else {
            $columnas_detalle = array_merge($columnas_detalle, array(
                "erpcod.costo_unitario as costo_compra",
                "0 as precio_venta",
                "0 as margen_actual",
                "0 as utilidad_actual",
                "0 as precio_sugerido",
                "0 as margen_nuevo",
                "0 as utilidad_nueva"
            ));
        }

        $columnas_detalle = array_merge($columnas_detalle, array(
            "COALESCE(NULLIF(NULLIF(erpcod.datos_fiscales_json, ''), '{}'), IF(ecompf.id_producto_fiscal IS NULL, NULL, JSON_OBJECT('clave_sat', ecompf.clave_sat, 'clave_unidad_sat', ecompf.clave_unidad_sat, 'unidad', ecompf.unidad, 'objeto_impuesto', ecompf.objeto_impuesto, 'tipo_impuesto', ecompf.tipo_impuesto, 'porcentaje_iva', ecompf.porcentaje_iva, 'porcentaje_ieps', ecompf.porcentaje_ieps, 'incluye_iva', ecompf.incluye_iva, 'requiere_factura', ecompf.requiere_factura))) as datos_fiscales_json",
            "IF(ecompf.id_producto_fiscal IS NULL, NULL, JSON_OBJECT('clave_sat', ecompf.clave_sat, 'clave_unidad_sat', ecompf.clave_unidad_sat, 'unidad', ecompf.unidad, 'objeto_impuesto', ecompf.objeto_impuesto, 'tipo_impuesto', ecompf.tipo_impuesto, 'porcentaje_iva', ecompf.porcentaje_iva, 'porcentaje_ieps', ecompf.porcentaje_ieps, 'incluye_iva', ecompf.incluye_iva, 'requiere_factura', ecompf.requiere_factura)) as datos_fiscales_catalogo_json",
            "ecompi.url_imagen",
            "COALESCE(erpsku.sku, erpplp.sku, erpcod.sku, ecomp_direct.sku, ecomp_sku.sku) as sku",
            "COALESCE(erpsku.nombre, erpplp.nombre, erpcod.nombre_producto, ecomp_direct.nombre, ecomp_sku.nombre) as nombre",
            "erpplp.piezas_por_caja",
            "COALESCE(ecomp_direct.precio_base, ecomp_sku.precio_base, 0) as precio_base",
            "COALESCE(ecompr.tipo_item, IF(erpcod.id_sku_erp IS NOT NULL OR COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto, 0) > 0, 'producto', 'producto_nuevo')) as tipo_item",
            "IF(erpcod.id_sku_erp IS NOT NULL OR COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto, 0) > 0, 1, 0) as producto_registrado",
            "IF(erpcod.id_sku_erp IS NOT NULL, 0, COALESCE(ecompr.requiere_revision, IF(COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto, 0) = 0, 1, 0))) as requiere_revision"
        ));
        $this->setColumnas($columnas_detalle);

        $this->setTabla($this->tabla_erp_compras_ordenes_detalle . " erpcod");
        $this->setLeftJoin("erp_compras_ordenes erpcs ON erpcs.id_orden_compra = erpcod.id_orden_compra");
        $this->setLeftJoin("erp_proveedores_listas erppl ON erppl.id_proveedor = erpcs.id_proveedor");
        $this->setLeftJoin("ecom_productos ecomp_direct ON ecomp_direct.id_producto = erpcod.id_producto");
        $this->setLeftJoin("erp_proveedores_listas_productos erpplp ON erpplp.id_producto = COALESCE(NULLIF(erpcod.id_producto_proveedor, 0), IF(ecomp_direct.id_producto IS NULL, erpcod.id_producto, 0))");
        $this->setLeftJoin("ecom_productos ecomp_sku ON ecomp_sku.sku = COALESCE(erpcod.sku, erpplp.sku)");
        $this->setLeftJoin("erp_catalogo_skus erpsku ON erpsku.id_sku = erpcod.id_sku_erp");
        $this->setLeftJoin("ecom_productos_imagenes ecompi ON ecompi.id_producto = COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto)");
        $this->setLeftJoin("ecom_productos_fiscales ecompf ON ecompf.id_producto = COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto)");
        $this->setLeftJoin("ecom_productos_revision ecompr ON ecompr.tipo_origen = 'orden_compra' AND ecompr.id_origen = erpcod.id_orden_compra AND (ecompr.id_producto = COALESCE(ecomp_direct.id_producto, ecomp_sku.id_producto) OR ecompr.sku = erpcod.sku)");
        $this->setWhere("erpcod.id_orden_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function consultar_compra_solicitud() {
        $this->setColumnas(array(
            "id_solicitud",
            "id_proveedor",
            "folio",
            "estatus",
            "observaciones",
            "fecha_solicitud"
        ));

        $this->setTabla($this->tabla_erp_compras_solicitudes);
        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->buscarRegistro();
    }

    private function columnas_detalle_comerciales_existen() {
        $campos = array(
            "costo_compra",
            "precio_venta",
            "margen_actual",
            "utilidad_actual",
            "precio_sugerido",
            "margen_nuevo",
            "utilidad_nueva"
        );
        $campos_sql = "'" . implode("','", $campos) . "'";
        $respuesta = $this->freeQuery("SHOW COLUMNS FROM " . $this->tabla_erp_compras_ordenes_detalle . " WHERE Field IN (" . $campos_sql . ")");
        return $respuesta['error'] == false && is_array($respuesta['depurar']) && count($respuesta['depurar']) === count($campos);
    }

    public function consultar_compra_solicitud_detalle() {
        $this->setColumnas(array(
            "erpcsd.id_producto",
            "erpcsd.cantidad",
            "erpcsd.costo_estimado",
            "erpcsd.subtotal",
            "erpcsd.observaciones",
            "ecompi.url_imagen",
            "erpplp.sku",
            "erpplp.nombre",
            "erpplp.piezas_por_caja"
        ));

        $this->setTabla($this->tabla_erp_compras_solicitudes_detalle . " erpcsd");
        $this->setLeftJoin("erp_compras_solicitudes erpcs ON erpcs.id_solicitud = erpcsd.id_solicitud");
        $this->setLeftJoin("erp_proveedores_listas erppl ON erppl.id_proveedor = erpcs.id_proveedor");
        $this->setLeftJoin("erp_proveedores_listas_productos erpplp ON erpplp.id_producto = erpcsd.id_producto");
        $this->setLeftJoin("ecom_productos ecomp ON ecomp.sku = erpplp.sku");
        $this->setLeftJoin("ecom_productos_imagenes ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setWhere("erpcsd.id_solicitud = " . $this->getId_solicitud());
        return $this->listar();
    }

    public function registrar_orden_compra() {
        $this->setColumnas(array(
            "id_proveedor",
            "id_solicitud",
            "subtotal",
            "impuestos",
            "total",
            "fecha_orden"
        ));
        $this->setColumnasValores(array(
            $this->getId_proveedor(),
            $this->getId_solicitud(),
            $this->getSubtotal(),
            $this->getImpuestos(),
            $this->getTotal(),
            $this->getFecha_orden()
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes);
        return $this->insertar();
    }

    public function actualizar_desglose_orden_compra() {
        $this->setColumnas(array(
            "subtotal",
            "impuestos",
            "total"
        ));
        $this->setColumnasValores(array(
            $this->getSubtotal(),
            $this->getImpuestos(),
            $this->getTotal()
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->update();
    }

    public function registrar_orden_compra_detalle() {
        $this->setColumnas(array(
            "id_orden_compra",
            "id_producto",
            "sku",
            "nombre_producto",
            "cantidad",
            "costo_unitario",
            "porcentaje_impuesto",
            "subtotal",
            "descuento",
            "total"
        ));
        $this->setColumnasValores(array(
            $this->getId_orden_compra(),
            $this->getId_producto(),
            $this->getSku(),
            $this->getNombre_producto(),
            $this->getCantidad(),
            $this->getCosto_unitario(),
            $this->getPorcentaje_impuesto(),
            $this->getSubtotal(),
            $this->getDescuento(),
            $this->getTotal()
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_detalle);
        return $this->insertar();
    }

    public function actualizar_orden_compra_desde_edicion($orden) {
        $this->setColumnas(array(
            "id_proveedor",
            "estatus",
            "fecha_orden",
            "fecha_entrega_estimada",
            "observaciones",
            "subtotal",
            "impuestos",
            "total",
            "folio_proveedor",
            "id_almacen_destino",
            "solicitante",
            "contacto_recepcion",
            "telefono_recepcion",
            "direccion_entrega",
            "descuento_global_productos",
            "saldo_pendiente"
        ));
        $this->setColumnasValores(array(
            $this->valor($orden, "id_proveedor", 0),
            $this->valor($orden, "estatus_orden", "borrador"),
            $this->valor($orden, "fecha_orden", null),
            $this->valor($orden, "fecha_recepcion", null),
            $this->valor($orden, "comentario", ""),
            $this->valor($orden, "subtotal", 0),
            $this->valor($orden, "impuestos", 0),
            $this->valor($orden, "total", 0),
            $this->valor($orden, "folio_factura_compra", ""),
            $this->valor($orden, "id_almacen_destino", 0),
            $this->valor($orden, "solicitante", ""),
            $this->valor($orden, "contacto_recepcion", ""),
            $this->valor($orden, "telefono_recepcion", ""),
            $this->valor($orden, "direccion_entrega", ""),
            $this->valor($orden, "descuento_global_productos", 0),
            $this->valor($orden, "saldo_pendiente", 0)
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes);
        $this->setWhere("id_orden_compra = " . $this->valor($orden, "id_orden_compra", 0));
        return $this->update();
    }

    public function registrar_orden_compra_completa($orden) {
        $this->setColumnas(array(
            "id_proveedor",
            "id_solicitud",
            "estatus",
            "fecha_orden",
            "fecha_entrega_estimada",
            "observaciones",
            "subtotal",
            "impuestos",
            "total",
            "folio_proveedor",
            "id_almacen_destino",
            "solicitante",
            "contacto_recepcion",
            "telefono_recepcion",
            "direccion_entrega",
            "descuento_global_productos",
            "saldo_pendiente"
        ));
        $this->setColumnasValores(array(
            $this->valor($orden, "id_proveedor", 0),
            $this->valor($orden, "id_solicitud", 0),
            $this->valor($orden, "estatus_orden", "borrador"),
            $this->valor($orden, "fecha_orden", date("Y-m-d")),
            $this->valor($orden, "fecha_recepcion", null),
            $this->valor($orden, "comentario", ""),
            $this->valor($orden, "subtotal", 0),
            $this->valor($orden, "impuestos", 0),
            $this->valor($orden, "total", 0),
            $this->valor($orden, "folio_factura_compra", ""),
            $this->valor($orden, "id_almacen_destino", 0),
            $this->valor($orden, "solicitante", ""),
            $this->valor($orden, "contacto_recepcion", ""),
            $this->valor($orden, "telefono_recepcion", ""),
            $this->valor($orden, "direccion_entrega", ""),
            $this->valor($orden, "descuento_global_productos", 0),
            $this->valor($orden, "saldo_pendiente", 0)
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes);
        return $this->insertar();
    }

    public function eliminar_registros_orden_compra_detalle() {
        $this->setTabla($this->tabla_erp_compras_ordenes_detalle);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function registrar_orden_compra_detalle_desde_edicion($id_orden_compra, $producto) {
        $datos_fiscales = json_encode($this->valor($producto, "datos_fiscales", array()), JSON_UNESCAPED_UNICODE);
        $ids_producto = $this->resolver_ids_producto_compra($producto);
        $margen_nuevo = $this->valor($producto, "margen_nuevo", $this->valor($producto, "porcentaje_nuevo", 0));
        $this->setColumnas(array(
            "id_orden_compra",
            "id_producto",
            "id_sku_erp",
            "sku",
            "nombre_producto",
            "cantidad",
            "costo_unitario",
            "porcentaje_impuesto",
            "subtotal",
            "descuento",
            "total",
            "id_producto_proveedor",
            "costo_compra",
            "costo_antes_impuesto",
            "precio_venta",
            "margen_actual",
            "utilidad_actual",
            "precio_sugerido",
            "margen_nuevo",
            "utilidad_nueva",
            "datos_fiscales_json"
        ));
        $this->setColumnasValores(array(
            $id_orden_compra,
            $ids_producto['id_producto_ecom'],
            $ids_producto['id_sku_erp'],
            $this->valor($producto, "sku", ""),
            $this->valor($producto, "nombre_producto", ""),
            $this->valor($producto, "cantidad", 0),
            $this->valor($producto, "precio", 0),
            $this->valor($producto, "porcentaje_impuesto", 0),
            $this->valor($producto, "subtotal", 0),
            $this->valor($producto, "descuento", 0),
            $this->valor($producto, "total", 0),
            $ids_producto['id_producto_proveedor'],
            $this->valor($producto, "costo_compra", $this->valor($producto, "precio", 0)),
            $this->valor($producto, "costo_antes_impuesto", 0),
            $this->valor($producto, "precio_venta", 0),
            $this->valor($producto, "margen_actual", 0),
            $this->valor($producto, "utilidad_actual", 0),
            $this->valor($producto, "precio_sugerido", 0),
            $margen_nuevo,
            $this->valor($producto, "utilidad_nueva", $this->valor($producto, "ganancia_nueva", 0)),
            $datos_fiscales
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_detalle);
        return $this->insertar();
    }

    private function resolver_ids_producto_compra($producto) {
        $id_producto_ecom = (int) $this->valor($producto, "id_producto_ecom", $this->valor($producto, "id_producto", 0));
        $id_producto_proveedor = (int) $this->valor($producto, "id_producto_proveedor", 0);
        $id_sku_erp = (int) $this->valor($producto, "id_sku_erp", 0);
        $sku = trim($this->valor($producto, "sku", ""));

        if ($sku !== "") {
            if ($id_sku_erp <= 0) {
                $producto_erp = $this->buscar_producto_erp_sku($sku);
                if ($producto_erp['error'] == false) {
                    $id_sku_erp = (int) $producto_erp['depurar']['id_sku'];
                }
            }
            if ($id_producto_ecom <= 0) {
                $producto_ecom = $this->buscar_producto_ecom_sku($sku);
                if ($producto_ecom['error'] == false) {
                    $id_producto_ecom = (int) $producto_ecom['depurar']['id_producto'];
                }
            }
            if ($id_producto_proveedor <= 0) {
                $producto_proveedor = $this->buscar_producto_proveedor_sku($sku);
                if ($producto_proveedor['error'] == false) {
                    $id_producto_proveedor = (int) $producto_proveedor['depurar']['id_producto'];
                }
            }
        }

        return array(
            "id_producto_ecom" => $id_producto_ecom,
            "id_producto_proveedor" => $id_producto_proveedor,
            "id_sku_erp" => $id_sku_erp
        );
    }

    public function resolver_id_producto_ecom_compra($producto) {
        $ids_producto = $this->resolver_ids_producto_compra($producto);
        return $ids_producto['id_producto_ecom'];
    }

    private function buscar_producto_ecom_sku($sku) {
        $this->setColumnas(array("id_producto"));
        $this->setTabla("ecom_productos");
        $this->setWhere("sku = '" . addslashes($sku) . "'");
        return $this->buscarRegistro();
    }

    private function buscar_producto_erp_sku($sku) {
        $this->setColumnas(array("id_sku"));
        $this->setTabla("erp_catalogo_skus");
        $this->setWhere("sku = '" . addslashes($sku) . "'");
        return $this->buscarRegistro();
    }

    private function buscar_producto_proveedor_sku($sku) {
        $this->setColumnas(array("id_producto"));
        $this->setTabla($this->tabla_erp_proveedores_listas_productos);
        $this->setWhere("sku = '" . addslashes($sku) . "'");
        return $this->buscarRegistro();
    }

    private function existe_revision_producto_catalogo($sku, $id_proveedor) {
        $this->setColumnas(array("id_producto_revision"));
        $this->setTabla($this->tabla_ecom_productos_revision);
        $this->setWhere("sku = '" . addslashes($sku) . "' AND id_proveedor = " . intval($id_proveedor) . " AND estatus = 'pendiente'");
        $respuesta = $this->buscarRegistro();
        return $respuesta['error'] == false;
    }

    private function existe_revision_producto_lista_proveedor($sku, $id_proveedor) {
        $this->setColumnas(array("id_revision_lista_producto"));
        $this->setTabla($this->tabla_erp_proveedores_listas_productos_revision);
        $this->setWhere("sku = '" . addslashes($sku) . "' AND id_proveedor = " . intval($id_proveedor) . " AND estatus = 'pendiente'");
        $respuesta = $this->buscarRegistro();
        return $respuesta['error'] == false;
    }

    private function existe_relacion_sku_proveedor_erp($id_sku, $id_proveedor) {
        if (intval($id_sku) <= 0 || intval($id_proveedor) <= 0) {
            return false;
        }
        $this->setColumnas(array("id_sku_proveedor"));
        $this->setTabla("erp_catalogo_sku_proveedores");
        $this->setWhere("id_sku = " . intval($id_sku) . " AND id_proveedor = " . intval($id_proveedor) . " AND estatus = 'activo'");
        $respuesta = $this->buscarRegistro();
        return $respuesta['error'] == false;
    }

    public function enriquecer_productos_compra($skus, $id_proveedor) {
        $productos = array();
        foreach ($skus as $sku) {
            $sku = trim($sku);
            if ($sku === "") {
                continue;
            }
            $productos[$sku] = $this->enriquecer_producto_compra_sku($sku, $id_proveedor);
        }
        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Productos enriquecidos correctamente",
            "depurar" => $productos
        );
    }

    private function enriquecer_producto_compra_sku($sku, $id_proveedor) {
        $db = $this->getConexion();
        $proveedor_erp = intval($id_proveedor);
        $stmt_erp = $db->prepare("SELECT
                s.id_sku AS id_sku_erp,
                s.sku,
                s.nombre AS nombre_sku,
                p.nombre AS nombre_producto_erp,
                s.costo_referencia,
                COALESCE(pr.precio, 0) AS precio_venta_erp,
                sp.id_sku_proveedor,
                sp.sku_proveedor,
                sp.costo_ultimo,
                sp.factor_conversion,
                sp.cantidad_minima,
                sp.dias_entrega,
                u.nombre AS unidad_compra
            FROM erp_catalogo_skus s
            INNER JOIN erp_catalogo_productos p ON p.id_producto_erp = s.id_producto_erp
            LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku = s.id_sku
                AND pr.lista_precio = 'general' AND pr.moneda = 'MXN' AND pr.estatus = 'activo'
            LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku = s.id_sku
                AND sp.estatus = 'activo'
                AND (" . $proveedor_erp . " = 0 OR sp.id_proveedor = " . $proveedor_erp . ")
            LEFT JOIN erp_catalogo_unidades u ON u.id_unidad = sp.id_unidad_compra
            WHERE s.sku = :sku AND s.estatus = 'activo'
            ORDER BY sp.es_preferido DESC, sp.id_sku_proveedor ASC
            LIMIT 1");
        $stmt_erp->execute(array(":sku" => $sku));
        $erp = $stmt_erp->fetch(PDO::FETCH_ASSOC);
        if (!$erp) {
            $erp = array();
        }

        $this->setColumnas(array(
            "ecomp.id_producto as id_producto_ecom",
            "ecomp.sku",
            "ecomp.nombre",
            "ecomp.precio_base",
            "ecompi.url_imagen"
        ));
        $this->setTabla("ecom_productos ecomp");
        $this->setLeftJoin("ecom_productos_imagenes ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setWhere("ecomp.sku = '" . addslashes($sku) . "'");
        $producto_ecom = $this->buscarRegistro();

        $this->setColumnas(array(
            "erpplp.id_producto as id_producto_proveedor",
            "erpplp.sku",
            "erpplp.nombre as nombre_proveedor",
            "erpplp.precio as costo",
            "erpplp.piezas_por_caja"
        ));
        $this->setTabla($this->tabla_erp_proveedores_listas_productos . " erpplp");
        $this->setLeftJoin("erp_proveedores_listas erppl ON erppl.id_lista = erpplp.id_lista");
        $where_proveedor = $id_proveedor ? " AND erppl.id_proveedor = " . intval($id_proveedor) : "";
        $this->setWhere("erpplp.sku = '" . addslashes($sku) . "'" . $where_proveedor);
        $producto_proveedor = $this->buscarRegistro();

        $ecom = $producto_ecom['error'] == false ? $producto_ecom['depurar'] : array();
        $proveedor = $producto_proveedor['error'] == false ? $producto_proveedor['depurar'] : array();
        $precio_actual = !empty($erp['precio_venta_erp']) ? (float) $erp['precio_venta_erp'] : (isset($ecom['precio_base']) ? (float) $ecom['precio_base'] : 0);
        $costo_proveedor = !empty($erp['id_sku_proveedor']) ? (float) $erp['costo_ultimo'] : (isset($proveedor['costo']) ? (float) $proveedor['costo'] : (!empty($erp['costo_referencia']) ? (float) $erp['costo_referencia'] : 0));
        $utilidad_actual = $precio_actual - $costo_proveedor;
        $margen_actual = $precio_actual > 0 ? ($utilidad_actual / $precio_actual) : 0;

        return array(
            "sku" => $sku,
            "id_sku_erp" => isset($erp['id_sku_erp']) ? $erp['id_sku_erp'] : 0,
            "id_producto_ecom" => isset($ecom['id_producto_ecom']) ? $ecom['id_producto_ecom'] : 0,
            "id_producto_proveedor" => isset($proveedor['id_producto_proveedor']) ? $proveedor['id_producto_proveedor'] : 0,
            "producto_registrado" => (!empty($erp['id_sku_erp']) || !empty($ecom['id_producto_ecom'])) ? 1 : 0,
            "producto_ecom_registrado" => !empty($ecom['id_producto_ecom']) ? 1 : 0,
            "producto_en_lista_proveedor" => (!empty($erp['id_sku_proveedor']) || !empty($proveedor['id_producto_proveedor'])) ? 1 : 0,
            "producto_erp_registrado" => !empty($erp['id_sku_erp']) ? 1 : 0,
            "producto_erp_proveedor_vinculado" => !empty($erp['id_sku_proveedor']) ? 1 : 0,
            "nombre" => isset($erp['nombre_sku']) ? $erp['nombre_sku'] : (isset($ecom['nombre']) ? $ecom['nombre'] : (isset($proveedor['nombre_proveedor']) ? $proveedor['nombre_proveedor'] : "")),
            "nombre_proveedor" => isset($proveedor['nombre_proveedor']) ? $proveedor['nombre_proveedor'] : "",
            "url_imagen" => isset($ecom['url_imagen']) ? $ecom['url_imagen'] : "",
            "precio_venta" => $precio_actual,
            "costo_proveedor_actual" => $costo_proveedor,
            "costo" => $costo_proveedor,
            "piezas_por_caja" => isset($proveedor['piezas_por_caja']) ? $proveedor['piezas_por_caja'] : 0,
            "factor_conversion" => isset($erp['factor_conversion']) ? $erp['factor_conversion'] : 1,
            "cantidad_minima" => isset($erp['cantidad_minima']) ? $erp['cantidad_minima'] : 1,
            "dias_entrega" => isset($erp['dias_entrega']) ? $erp['dias_entrega'] : 0,
            "unidad_compra" => isset($erp['unidad_compra']) ? $erp['unidad_compra'] : "",
            "margen_actual" => $margen_actual,
            "utilidad_actual" => $utilidad_actual,
            "porcentaje_anterior" => $margen_actual,
            "ganancia_anterior" => $utilidad_actual
        );
    }

    public function eliminar_pagos_orden_compra() {
        $this->setTabla($this->tabla_erp_compras_ordenes_pagos);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function registrar_pago_orden_compra($id_orden_compra, $pago) {
        $this->setColumnas(array("id_orden_compra", "metodo_pago", "estado_pago", "referencia", "monto"));
        $this->setColumnasValores(array(
            $id_orden_compra,
            $this->valor($pago, "metodo_pago", ""),
            $this->valor($pago, "estado_pago", "pendiente"),
            $this->valor($pago, "referencia", ""),
            $this->valor($pago, "monto", 0)
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_pagos);
        return $this->insertar();
    }

    public function eliminar_notas_credito_orden_compra() {
        $this->setTabla($this->tabla_erp_compras_ordenes_notas_credito);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function registrar_nota_credito_orden_compra($id_orden_compra, $nota_credito) {
        $this->setColumnas(array("id_orden_compra", "referencia", "monto"));
        $this->setColumnasValores(array(
            $id_orden_compra,
            $this->valor($nota_credito, "referencia", ""),
            $this->valor($nota_credito, "monto", 0)
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_notas_credito);
        return $this->insertar();
    }

    public function eliminar_adjuntos_orden_compra() {
        $this->setTabla($this->tabla_erp_compras_ordenes_adjuntos);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function registrar_adjunto_orden_compra($id_orden_compra, $adjunto) {
        $this->setColumnas(array("id_orden_compra", "tipo_documento", "referencia", "archivo_nombre", "archivo_ruta", "archivo_tipo", "archivo_tamano", "observaciones"));
        $this->setColumnasValores(array(
            $id_orden_compra,
            $this->valor($adjunto, "tipo_documento", ""),
            $this->valor($adjunto, "referencia", ""),
            $this->valor($adjunto, "archivo_nombre", ""),
            $this->valor($adjunto, "archivo_ruta", ""),
            $this->valor($adjunto, "archivo_tipo", ""),
            $this->valor($adjunto, "archivo_tamano", 0),
            $this->valor($adjunto, "observaciones", $this->valor($adjunto, "observacion", ""))
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_adjuntos);
        return $this->insertar();
    }

    public function registrar_producto_pendiente_compra($id_orden_compra, $producto) {
        $ids_producto = $this->resolver_ids_producto_compra($producto);
        if ($ids_producto['id_sku_erp'] > 0) {
            return array("error" => false, "tipo" => "info", "mensaje" => "El producto ya existe en el catalogo ERP", "depurar" => null);
        }
        $sku = $this->valor($producto, "sku", "");
        if ($sku !== "" && $this->existe_revision_producto_catalogo($sku, $this->getId_proveedor())) {
            return array("error" => false, "tipo" => "info", "mensaje" => "El producto ya existe en revision de catalogo", "depurar" => null);
        }
        $datos_fiscales = json_encode($this->valor($producto, "datos_fiscales", array()), JSON_UNESCAPED_UNICODE);
        $this->setColumnas(array(
            "id_producto",
            "id_producto_proveedor",
            "id_proveedor",
            "modulo_origen",
            "tipo_origen",
            "id_origen",
            "sku",
            "nombre_producto",
            "tipo_item",
            "producto_registrado",
            "requiere_revision",
            "datos_fiscales_json",
            "estatus"
        ));
        $this->setColumnasValores(array(
            $ids_producto['id_producto_ecom'],
            $ids_producto['id_producto_proveedor'],
            $this->getId_proveedor(),
            "compras",
            "orden_compra",
            $id_orden_compra,
            $this->valor($producto, "sku", ""),
            $this->valor($producto, "nombre_producto", ""),
            $this->valor($producto, "tipo_item", "producto_nuevo"),
            $this->valor($producto, "producto_registrado", 0),
            $this->valor($producto, "requiere_revision", 1),
            $datos_fiscales,
            "pendiente"
        ));
        $this->setTabla($this->tabla_ecom_productos_revision);
        return $this->insertar();
    }

    public function registrar_producto_lista_proveedor_revision_compra($id_orden_compra, $producto) {
        $ids_producto = $this->resolver_ids_producto_compra($producto);
        if ($ids_producto['id_producto_proveedor'] > 0 || $this->existe_relacion_sku_proveedor_erp($ids_producto['id_sku_erp'], $this->getId_proveedor())) {
            return array("error" => false, "tipo" => "info", "mensaje" => "El producto ya existe en lista de proveedor", "depurar" => null);
        }

        $sku = $this->valor($producto, "sku", "");
        if ($sku === "" || $this->existe_revision_producto_lista_proveedor($sku, $this->getId_proveedor())) {
            return array("error" => false, "tipo" => "info", "mensaje" => "El producto ya existe en revision de lista proveedor", "depurar" => null);
        }

        $this->setColumnas(array(
            "id_proveedor",
            "id_orden_compra",
            "id_producto",
            "sku",
            "nombre_producto",
            "motivo",
            "estatus",
            "observaciones"
        ));
        $this->setColumnasValores(array(
            $this->getId_proveedor(),
            $id_orden_compra,
            $ids_producto['id_producto_ecom'],
            $sku,
            $this->valor($producto, "nombre_producto", $this->valor($producto, "nombre", "")),
            "no_existe_lista_proveedor",
            "pendiente",
            "Producto comprado o cargado por XML que no existe en la lista del proveedor."
        ));
        $this->setTabla($this->tabla_erp_proveedores_listas_productos_revision);
        return $this->insertar();
    }

    public function eliminar_productos_pendientes_orden_compra() {
        $this->setTabla($this->tabla_ecom_productos_revision);
        $this->setWhere("tipo_origen = 'orden_compra' AND id_origen = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function eliminar_productos_atencion_orden_compra() {
        $this->setTabla($this->tabla_erp_compras_ordenes_productos_atencion);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->eliminar();
    }

    public function registrar_producto_atencion_orden_compra($id_orden_compra, $producto) {
        $this->setColumnas(array(
            "id_orden_compra",
            "id_solicitud",
            "id_proveedor",
            "id_producto",
            "id_producto_proveedor",
            "sku",
            "nombre_producto",
            "cantidad_solicitada",
            "cantidad_comprada",
            "motivo",
            "estatus",
            "observaciones"
        ));
        $this->setColumnasValores(array(
            $id_orden_compra,
            $this->valor($producto, "id_solicitud", 0),
            $this->getId_proveedor(),
            $this->valor($producto, "id_producto", 0),
            $this->valor($producto, "id_producto_proveedor", 0),
            $this->valor($producto, "sku", ""),
            $this->valor($producto, "nombre_producto", ""),
            $this->valor($producto, "cantidad_solicitada", $this->valor($producto, "cantidad", 0)),
            $this->valor($producto, "cantidad_comprada", 0),
            $this->valor($producto, "motivo", "no_incluido_xml"),
            $this->valor($producto, "estatus", "pendiente"),
            $this->valor($producto, "observaciones", "")
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_productos_atencion);
        return $this->insertar();
    }

    public function consultar_productos_atencion_orden_compra() {
        $this->setColumnas(array(
            "id_producto_atencion",
            "id_orden_compra",
            "id_solicitud",
            "id_proveedor",
            "id_producto",
            "id_producto_proveedor",
            "sku",
            "nombre_producto",
            "cantidad_solicitada",
            "cantidad_comprada",
            "motivo",
            "estatus",
            "observaciones"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_productos_atencion);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function consultar_pagos_orden_compra() {
        $this->setColumnas(array(
            "id_pago_orden",
            "metodo_pago",
            "estado_pago",
            "referencia",
            "monto",
            "fecha_registro"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_pagos);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function consultar_notas_credito_orden_compra() {
        $this->setColumnas(array(
            "id_nota_credito_orden",
            "referencia",
            "monto",
            "fecha_registro"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_notas_credito);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function consultar_adjuntos_orden_compra() {
        $this->setColumnas(array(
            "id_adjunto_orden",
            "tipo_documento",
            "referencia",
            "archivo_nombre",
            "archivo_ruta",
            "archivo_tipo",
            "archivo_tamano",
            "observaciones",
            "fecha_registro"
        ));
        $this->setTabla($this->tabla_erp_compras_ordenes_adjuntos);
        $this->setWhere("id_orden_compra = " . $this->getId_orden_compra());
        return $this->listar();
    }

    public function actualizar_solicitud_compra() {
        $this->setColumnas(array(
            "id_proveedor",
            "folio",
            "estatus",
            "observaciones"
        ));
        $this->setColumnasValores(array(
            $this->getId_proveedor(),
            $this->getFolio(),
            $this->getEstatus(),
            $this->getObservaciones_solicitud()
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes);
        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->update();
    }

    public function registrar_solicitud_compra() {
        $this->setColumnas(array(
            "id_proveedor",
            "folio",
            "estatus",
            "observaciones",
            "fecha_solicitud"
        ));
        $this->setColumnasValores(array(
            $this->getId_proveedor(),
            $this->getFolio(),
            $this->getEstatus(),
            $this->getObservaciones_solicitud(),
            $this->getFecha_solicitud()
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes);
        return $this->insertar();
    }

    public function registrar_solicitud_compra_detalle() {
        $this->setColumnas(array(
            "id_solicitud",
            "id_producto",
            "cantidad",
            "costo_estimado",
            "subtotal",
            "observaciones"
        ));
        $this->setColumnasValores(array(
            $this->getId_solicitud(),
            $this->getId_producto(),
            $this->getCantidad(),
            $this->getCosto_estimado(),
            $this->getSubtotal(),
            $this->getObservaciones_producto()
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes_detalle);
        return $this->insertar();
    }

    public function eliminar_registros_solicitud_compra_detalle() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_erp_compras_solicitudes_detalle);
        $this->setWhere("id_solicitud = " . $this->getId_solicitud());
        return $this->eliminar();
    }

    public function getId_orden_compra() {
        return $this->id_orde_compra;
    }

    public function setId_orden_compra($id_orde_compra): void {
        $this->id_orde_compra = $id_orde_compra;
    }

    public function getId_proveedor() {
        return $this->id_proveedor;
    }

    public function getFolio() {
        return $this->folio;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function getObservaciones_solicitud() {
        return $this->observaciones_solicitud;
    }

    public function getFecha_solicitud() {
        return $this->fecha_solicitud;
    }

    public function getFecha_aprobacion() {
        return $this->fecha_aprobacion;
    }

    public function getId_solicitud() {
        return $this->id_solicitud;
    }

    public function getId_producto() {
        return $this->id_producto;
    }

    public function getCantidad() {
        return $this->cantidad;
    }

    public function getCosto_estimado() {
        return $this->costo_estimado;
    }

    public function getSubtotal() {
        return $this->subtotal;
    }

    public function getObservaciones_producto() {
        return $this->observaciones_producto;
    }

    public function setId_proveedor($id_proveedor): void {
        $this->id_proveedor = $id_proveedor;
    }

    public function setFolio($folio): void {
        $this->folio = $folio;
    }

    public function setEstatus($estatus): void {
        $this->estatus = $estatus;
    }

    public function setObservaciones_solicitud($observaciones_solicitud): void {
        $this->observaciones_solicitud = $observaciones_solicitud;
    }

    public function setFecha_solicitud($fecha_solicitud): void {
        $this->fecha_solicitud = $fecha_solicitud;
    }

    public function setFecha_aprobacion($fecha_aprobacion): void {
        $this->fecha_aprobacion = $fecha_aprobacion;
    }

    public function setId_solicitud($id_solicitud): void {
        $this->id_solicitud = $id_solicitud;
    }

    public function setId_producto($id_producto): void {
        $this->id_producto = $id_producto;
    }

    public function setCantidad($cantidad): void {
        $this->cantidad = $cantidad;
    }

    public function setCosto_estimado($costo_estimado): void {
        $this->costo_estimado = $costo_estimado;
    }

    public function setSubtotal($subtotal): void {
        $this->subtotal = $subtotal;
    }

    public function setObservaciones_producto($observaciones_producto): void {
        $this->observaciones_producto = $observaciones_producto;
    }

    public function getImpuestos() {
        return $this->impuestos;
    }

    public function getTotal() {
        return $this->total;
    }

    public function getFecha_orden() {
        return $this->fecha_orden;
    }

    public function getFecha_estimada() {
        return $this->fecha_estimada;
    }

    public function setImpuestos($impuestos): void {
        $this->impuestos = $impuestos;
    }

    public function setTotal($total): void {
        $this->total = $total;
    }

    public function setFecha_orden($fecha_orden): void {
        $this->fecha_orden = $fecha_orden;
    }

    public function setFecha_estimada($fecha_estimada): void {
        $this->fecha_estimada = $fecha_estimada;
    }

    public function getNombre_producto() {
        return $this->nombre_producto;
    }

    public function getCosto_unitario() {
        return $this->costo_unitario;
    }

    public function getPorcentaje_impuesto() {
        return $this->porcentaje_impuesto;
    }

    public function getCantidad_recibida() {
        return $this->cantidad_recibida;
    }

    public function setNombre_producto($nombre_producto): void {
        $this->nombre_producto = $nombre_producto;
    }

    public function setCosto_unitario($costo_unitario): void {
        $this->costo_unitario = $costo_unitario;
    }

    public function setPorcentaje_impuesto($porcentaje_impuesto): void {
        $this->porcentaje_impuesto = $porcentaje_impuesto;
    }

    public function setCantidad_recibida($cantidad_recibida): void {
        $this->cantidad_recibida = $cantidad_recibida;
    }

    public function getSku() {
        return $this->sku;
    }

    public function setSku($sku): void {
        $this->sku = $sku;
    }

    public function getDescuento() {
        return $this->descuento;
    }

    public function setDescuento($descuento): void {
        $this->descuento = $descuento;
    }

    private function valor($array, $key, $default = null) {
        return isset($array[$key]) ? $array[$key] : $default;
    }
}
