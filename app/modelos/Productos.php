<?php

class Productos extends CRUD {

    public $nombre;
    private $sku;
    private $descripcion;
    private $disponible;
    private $precio_base;
    private $precio_oferta;
    private $precio_visible;
    private $estatus;
    private $codigo_barras_base;
    private $codigo_interno;
    private $existencia;
    private $id_producto;
    private $id_imagen;
    private $id_proveedor;
    private $id_categoria;
    private $id_marca;
    private $id_atributo;
    private $id_tag;
    private $archivo_portada;
    private $url_origen;
    private $url_imagen;
    private $tipo_imagen;
    private $identificador;
    private $minima_existencia;
    private $maxima_existencia;
    private $error;
    private $busqueda;
    private $id_unidad_compra;
    private $id_unidad_venta;
    private $solo__en_punto_de_venta;
    private $factor;
    private $categoria;
    private $clasificacion;
    private $tipo_atributo;
    private $valor_atributo;
    private $unidad_medida_atributo;
    private $descripcion_atributo;
    private $tag;
    private $id_variante;
    private $id_sugerido;
    private $id_complementario;
    private $tipo_etiqueta;
    private $principal;
    private $tipo_producto;
    private $tabla_productos = "ecom_productos";
    private $tabla_proveedores = "erp_proveedores";
    private $tabla_categorias = "ecom_categorias";
    private $tabla_marcas = "ecom_marcas";
    private $tabla_atributos = "ecom_atributos";
    private $tabla_tags = "ecom_tags";
    private $tabla_ecom_productos_variantes = "ecom_productos_variantes";
    private $tabla_ecom_variantes = "ecom_variantes";
    private $tabla_erp_unidad_venta = "erp_unidad_venta";
    private $tabla_producto_proveedores = "ecom_productos_proveedores";
    private $tabla_producto_categorias = "ecom_productos_categorias";
    private $tabla_producto_marcas = "ecom_productos_marcas";
    private $tabla_producto_atributos = "ecom_productos_atributos";
    private $tabla_producto_tags = "ecom_productos_tags";
    private $tabla_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_productos_compra_venta = "ecom_productos_compra_venta";
    private $tabla_producto_sugeridos = "ecom_productos_sugeridos";
    private $recursos_productos = "media/apps/ecommerce/productos/";
    private $tabla_ecom_productos_fiscales = "ecom_productos_fiscales";
    //productos fiscales
    private $id_producto_fiscal;
    private $clave_sat;
    private $clave_unidad_sat;
    private $unidad;
    private $objeto_impuesto;
    private $tipo_impuesto;
    private $porcentaje_iva;
    private $porcentaje_ieps;
    private $incluye_iva;
    private $requiere_factura;
    private $fecha_actualizacion;

    public function consultar_variantes() {
        $campos = array(
            "ecompv.id_producto",
            "ecompv.principal"
        );
        $this->setWhere("ecompv.id_variante =" . $this->getId_variante());
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_ecom_productos_variantes . " ecompv");

        return $this->listar();
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
        $this->setTabla($this->tabla_productos_imagenes);
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

    public function eliminar_imagen_complementaria() {
        $this->setTabla($this->tabla_productos_imagenes);

        $this->setWhere("id_producto_imagen = " . $this->getId_imagen());
        $respuesta = $this->eliminar();
        return $respuesta;
    }

    public function registrar_compra_venta() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_producto",
            "id_unidad_compra",
            "id_unidad_venta",
            "solo_en_punto_de_venta",
            "factor",
            "fch_r"
        );
        $valores_registrar = array(
            $this->getId_producto(),
            $this->getId_unidad_compra(),
            $this->getId_unidad_venta(),
            $this->getSolo_en_punto_de_venta(),
            $this->getFactor(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_productos_compra_venta);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    //costos historial
    public function actualizar_precio_por_sku() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "precio_base",
            "fch_m"
        );
        $valores_registrar = array(
            $this->getPrecio_base(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setWhere("sku = '" . $this->getSku() . "'");
        $this->setTabla($this->tabla_productos);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function actualizar_tipo_producto() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "tipo",
            "fch_m"
        );
        $valores_registrar = array(
            $this->getTipo_producto(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_productos);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function actualizar_compra_venta() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "id_unidad_compra",
            "id_unidad_venta",
            "solo_en_punto_de_venta",
            "factor",
            "fch_m"
        );
        $valores_registrar = array(
            $this->getId_unidad_compra(),
            $this->getId_unidad_venta(),
            $this->getSolo_en_punto_de_venta(),
            $this->getFactor(),
            DATE_NOW
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_productos_compra_venta);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function registrar() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "nombre",
            "sku",
            "descripcion",
            "siempre_disponible",
            "precio_base",
            "estatus",
            "codigo_barras",
            "existencia",
            "identificador",
            "minima_existencia",
            "maxima_existencia"
        );
        $valores_registrar = array(
            $this->nombre,
            $this->sku,
            $this->descripcion,
            $this->disponible,
            $this->precio_base,
            $this->estatus,
            $this->codigo_barras_base,
            $this->existencia,
            $this->identificador,
            $this->getMinima_existencia(),
            $this->getMaxima_existencia()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setTabla($this->tabla_productos);
        $respuesta = $this->insertar();
        return $respuesta;
    }

    public function consultar_atributos() {
        $campos = array(
            "ecompa.id_producto",
            "ecoma.id_atributo",
            "ecoma.tipo_atributo",
            "ecoma.valor_atributo",
            "ecoma.unidad_medida_atributo",
            "ecoma.descripcion_atributo"
        );
        $this->setWhere("ecompa.id_producto IN (" . $this->getId_producto() . ")");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_producto_atributos . " ecompa");
        $this->setInnerJoin($this->tabla_atributos . " ecoma ON ecoma.id_atributo = ecompa.id_atributo");

        return $this->listar();
    }

    public function actualizar() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "nombre",
            "sku",
            "descripcion",
            "siempre_disponible",
            "precio_base",
            "precio_oferta",
            "estatus",
            "existencia",
            "minima_existencia",
            "maxima_existencia",
            "codigo_barras",
            "codigo_interno",
            "identificador"
        );
        $valores_registrar = array(
            $this->nombre,
            $this->sku,
            $this->descripcion,
            $this->disponible,
            $this->precio_base,
            $this->precio_oferta,
            $this->estatus,
            $this->existencia,
            $this->minima_existencia,
            $this->maxima_existencia,
            $this->codigo_barras_base,
            $this->codigo_interno,
            $this->identificador
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_productos);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function actualizar_existencia() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "existencia"
        );
        $valores_registrar = array(
            $this->getExistencia()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setWhere("codigo_barras = '" . $this->getCodigo_barras_base() . "'");
        $this->setTabla($this->tabla_productos);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $respuesta = $this->update();
        return $respuesta;
    }

    public function consultar_registro_codigo_barras() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_productos);
        $this->setWhere("codigo_barras = '" . $this->getCodigo_barras_base() . "'");
        return $this->buscarRegistro();
    }

    public function consultar_compra_venta() {
        $this->setColumnas(array(
            "id_unidad_compra",
            "id_unidad_venta",
            "solo_en_punto_de_venta",
            "factor"
        ));
        $this->setTabla($this->tabla_productos_compra_venta);
        $this->setWhere("id_producto = '" . $this->getId_producto() . "'");
        return $this->buscarRegistro();
    }

    //buscarRegistro($campo, $tabla, $elemento, $busqueda, $and = null)
    public function consultar() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_productos);

        return $this->buscarRegistro();
    }

    //Se llama en compra
    public function consultar_producto_fiscal_sku() {

        $this->setColumnas(array(
            "ecompf.incluye_iva",
            "ecompf.porcentaje_iva",
            "ecompf.tipo_impuesto"
        ));
        $this->setTabla($this->tabla_productos." ecomp");
        $this->setInnerJoin($this->tabla_ecom_productos_fiscales . " ecompf ON ecompf.id_producto = ecomp.id_producto");
        $this->setWhere("ecomp.sku = " . '"' . $this->getSku() . '"');
        return $this->buscarRegistro();
    }

    public function consultar_producto_fiscal_id_producto() {
        $this->setColumnas(array(
            "id_producto_fiscal",
            "id_producto",
            "clave_sat",
            "clave_unidad_sat",
            "unidad",
            "objeto_impuesto",
            "tipo_impuesto",
            "porcentaje_iva",
            "porcentaje_ieps",
            "incluye_iva",
            "requiere_factura"
        ));
        $this->setTabla($this->tabla_ecom_productos_fiscales);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->buscarRegistro();
    }

    public function guardar_producto_fiscal_compra($datos_fiscales) {
        if (!$this->getId_producto()) {
            return array("error" => true, "tipo" => "warning", "mensaje" => "Falta producto para guardar datos fiscales", "depurar" => $datos_fiscales);
        }

        if ($this->datosFiscalesCompraVacios($datos_fiscales)) {
            return array("error" => false, "tipo" => "info", "mensaje" => "Datos fiscales vacios; no se actualiza informacion fiscal del producto", "depurar" => $datos_fiscales);
        }

        $objeto_impuesto = $this->valorFiscal($datos_fiscales, "objeto_impuesto", "");
        $porcentaje_iva = $this->valorFiscal($datos_fiscales, "porcentaje_iva", 0);
        $tipo_impuesto = $this->normalizarTipoImpuestoFiscalCompra(
            $this->valorFiscal($datos_fiscales, "tipo_impuesto", ""),
            $porcentaje_iva,
            $objeto_impuesto
        );

        $this->setClave_sat($this->valorFiscal($datos_fiscales, "clave_sat", ""));
        $this->setClave_unidad_sat($this->valorFiscal($datos_fiscales, "clave_unidad_sat", ""));
        $this->setUnidad($this->valorFiscal($datos_fiscales, "unidad", ""));
        $this->setObjeto_impuesto($objeto_impuesto);
        $this->setTipo_impuesto($tipo_impuesto);
        $this->setPorcentaje_iva($porcentaje_iva);
        $this->setPorcentaje_ieps($this->valorFiscal($datos_fiscales, "porcentaje_ieps", 0));
        $this->setIncluye_iva($this->valorFiscal($datos_fiscales, "incluye_iva", 1));
        $this->setRequiere_factura($this->valorFiscal($datos_fiscales, "requiere_factura", 1));

        $fiscal_actual = $this->consultar_producto_fiscal_id_producto();
        if ($fiscal_actual['error'] == false) {
            return $this->actualizar_producto_fiscal_compra();
        }
        return $this->registrar_producto_fiscal_compra();
    }

    private function registrar_producto_fiscal_compra() {
        $this->setColumnas($this->columnasProductoFiscalCompra(true));
        $this->setColumnasValores($this->valoresProductoFiscalCompra(true));
        $this->setTabla($this->tabla_ecom_productos_fiscales);
        return $this->insertar();
    }

    private function actualizar_producto_fiscal_compra() {
        $this->setColumnas($this->columnasProductoFiscalCompra(false));
        $this->setColumnasValores($this->valoresProductoFiscalCompra(false));
        $this->setTabla($this->tabla_ecom_productos_fiscales);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->update();
    }

    private function columnasProductoFiscalCompra($incluye_id_producto) {
        $columnas = array();
        if ($incluye_id_producto) {
            $columnas[] = "id_producto";
        }
        return array_merge($columnas, array(
            "clave_sat",
            "clave_unidad_sat",
            "unidad",
            "objeto_impuesto",
            "tipo_impuesto",
            "porcentaje_iva",
            "porcentaje_ieps",
            "incluye_iva",
            "requiere_factura",
            "fecha_actualizacion"
        ));
    }

    private function valoresProductoFiscalCompra($incluye_id_producto) {
        $valores = array();
        if ($incluye_id_producto) {
            $valores[] = $this->getId_producto();
        }
        return array_merge($valores, array(
            $this->getClave_sat(),
            $this->getClave_unidad_sat(),
            $this->getUnidad(),
            $this->getObjeto_impuesto(),
            $this->getTipo_impuesto(),
            $this->getPorcentaje_iva(),
            $this->getPorcentaje_ieps(),
            $this->getIncluye_iva(),
            $this->getRequiere_factura(),
            DATE_NOW
        ));
    }

    private function valorFiscal($array, $key, $default = null) {
        return isset($array[$key]) && $array[$key] !== "" ? $array[$key] : $default;
    }

    private function normalizarTipoImpuestoFiscalCompra($tipo_impuesto, $porcentaje_iva, $objeto_impuesto) {
        $tipo = strtolower(trim((string) $tipo_impuesto));
        if ($tipo !== "") {
            if ($tipo === "iva" || $tipo === "ieps") {
                return "gravado";
            }
            if ($tipo === "exento") {
                return "tasa_0";
            }
            return $tipo;
        }

        $objeto = trim((string) $objeto_impuesto);
        if ($objeto === "01" || $objeto === "04") {
            return "tasa_0";
        }

        if ($porcentaje_iva !== null && $porcentaje_iva !== "" && floatval($porcentaje_iva) > 0) {
            return "gravado";
        }

        if ($porcentaje_iva !== null && $porcentaje_iva !== "") {
            return "tasa_0";
        }

        return "";
    }

    private function datosFiscalesCompraVacios($datos_fiscales) {
        if (!is_array($datos_fiscales)) {
            return true;
        }

        $campos = array(
            "clave_sat",
            "clave_unidad_sat",
            "unidad",
            "objeto_impuesto",
            "tipo_impuesto",
            "porcentaje_iva"
        );

        foreach ($campos as $campo) {
            if (isset($datos_fiscales[$campo]) && $datos_fiscales[$campo] !== "" && $datos_fiscales[$campo] !== null) {
                return false;
            }
        }

        return true;
    }

    //consultar producto
    public function consultar_producto($columnas, $where) {

        $this->setColumnas($columnas);
        $this->setTabla($this->tabla_productos);
        $this->where($where);
        return $this->buscarRegistro();
    }

    function listar_productos_imagen_sku() {
        $this->setColumnas(array(
            "ecomp.id_producto",
            "ecomp.codigo_barras",
            "ecomp.codigo_interno",
            "ecomp.sku",
            "ecomp.existencia",
            "ecomp.nombre",
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

        return $this->listar();
    }

    public function actualizar_categorias_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_categorias);
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

    public function actualizar_sugeridos_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_sugeridos);
        $columnas = array(
            "id_producto",
            "id_sugerido"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_sugerido()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function actualizar_marcas_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_marcas);
        $columnas = array(
            "id_producto",
            "id_marca"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_marca()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_variante() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_ecom_variantes);
        $columnas = array(
            "id_principal"
        );
        $valores = array(
            $this->getPrincipal()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_variante_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_ecom_productos_variantes);
        $columnas = array(
            "id_variante",
            "id_producto",
            "principal"
        );
        $valores = array(
            $this->getId_variante(),
            $this->getId_producto(),
            $this->getPrincipal()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_atributo_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_atributos);
        $columnas = array(
            "id_producto",
            "id_atributo"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_atributo()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_tag_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_tags);
        $columnas = array(
            "id_producto",
            "id_tag"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_tag()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_etiqueta_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla("ecom_productos_etiquetas");
        $columnas = array(
            "id_producto",
            "id_etiqueta",
            "tipo_etiqueta"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_tag(),
            $this->getTipo_etiqueta()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_sugerido() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla("ecom_productos_sugeridos");
        $columnas = array(
            "id_producto",
            "id_producto_sugerido"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_sugerido()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_complementario() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla("ecom_productos_complementarios");
        $columnas = array(
            "id_producto",
            "id_producto_complementario"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_complementario()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_atributo() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_atributos);
        $columnas = array(
            "tipo_atributo",
            "valor_atributo",
            "unidad_medida_atributo",
            "descripcion_atributo"
        );
        $valores = array(
            $this->getTipo_atributo(),
            $this->getValor_atributo(),
            $this->getUnidad_medida_atributo(),
            $this->getDescripcion_atributo()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_tag() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_tags);
        $columnas = array(
            "tag"
        );
        $valores = array(
            $this->getTag()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function registrar_etiqueta() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla("ecom_etiquetas");
        $columnas = array(
            "etiqueta"
        );
        $valores = array(
            $this->getTag()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function eliminar_categorias_producto() {
        $this->setTabla($this->tabla_producto_categorias);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_sugeridos_producto() {
        $this->setTabla($this->tabla_producto_sugeridos);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_marcas_producto() {
        $this->setTabla($this->tabla_producto_marcas);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_atributos_producto() {
        $this->setTabla($this->tabla_producto_atributos);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_variantes_producto() {
        $this->setTabla($this->tabla_ecom_productos_variantes);
        $this->setWhere("id_variante = " . $this->getId_variante());
        return $this->eliminar();
    }

    public function eliminar_tags_producto() {
        $this->setTabla($this->tabla_producto_tags);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_sugeridos() {
        $this->setTabla("ecom_productos_sugeridos");
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_complementarios() {
        $this->setTabla("ecom_productos_complementarios");
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function eliminar_etiquetas_productos() {
        $this->setTabla("ecom_productos_etiquetas");
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function actualizar_proveedores_producto() {
//    var_dump($this->eliminar_proveedores_producto());
        $this->setTabla($this->tabla_producto_proveedores);
        $columnas = array(
            "id_producto",
            "id_proveedor"
        );
        $valores = array(
            $this->getId_producto(),
            $this->getId_proveedor()
        );
        $this->setColumnas($columnas);
        $this->setColumnasValores($valores);
        return $this->insertar();
    }

    public function eliminar_proveedores_producto() {
        $this->setTabla($this->tabla_producto_proveedores);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->eliminar();
    }

    public function listar_imagenes() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_productos_imagenes);
        $this->setWhere("id_producto = " . $this->getId_producto());
        return $this->listar();
    }

    public function consultar_imagen() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setAnd("tipo_imagen = " . $this->getTipo_imagen());
        $this->setTabla($this->tabla_productos);

        return $this->buscarRegistro();
    }

    public function buscar_existencia_atributo() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("tipo_atributo = '" . $this->getTipo_atributo() . "'");
        $this->setAnd("valor_atributo = '" . $this->getValor_atributo() . "'");
        $this->setAnd("unidad_medida_atributo = '" . $this->getUnidad_medida_atributo() . "'");
        $this->setAnd("descripcion_atributo = '" . $this->getDescripcion_atributo() . "'");
        $this->setTabla($this->tabla_atributos);

        return $this->buscarRegistro();
    }

    public function buscar_existencia_tag() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("tag = '" . $this->getTag() . "'");
        $this->setTabla($this->tabla_tags);

        return $this->buscarRegistro();
    }

    public function buscar_existencia_etiqueta() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("etiqueta = '" . $this->getTag() . "'");
        $this->setTabla("ecom_etiquetas");

        return $this->buscarRegistro();
    }

    public function listar_categorias_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_producto_categorias);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_categorias . " erpc ON erpc.id_categoria= " . $this->tabla_producto_categorias . ".id_categoria");
        return $this->listar();
    }

    public function listar_sugeridos_producto() {
        $this->limpiarVariables();
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
            "ecomp.estatus",
            "ecompi.tipo_imagen",
            "ecompi.url_imagen",
            "'producto' as tipo_item"
        ));
        $this->setTabla($this->tabla_producto_sugeridos . " ecomps");
        $this->setWhere("ecomps.id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_productos . " ecomp ON ecomp.id_producto = ecomps.id_sugerido");
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomps.id_sugerido");
        return $this->listar();
    }

    public function listar_marcas_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_producto_marcas);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_marcas . " erpc ON erpc.id_marca = " . $this->tabla_producto_marcas . ".id_marca");
        return $this->listar();
    }

    public function listar_atributos_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_producto_atributos);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_atributos . " erpc ON erpc.id_atributo = " . $this->tabla_producto_atributos . ".id_atributo");
        return $this->listar();
    }

    public function listar_tags_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_producto_tags);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_tags . " erpc ON erpc.id_tag = " . $this->tabla_producto_tags . ".id_tag");
        return $this->listar();
    }

    public function obtener_id_variante() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_ecom_productos_variantes . " ecompv");
        $this->setWhere("ecompv.id_producto = " . $this->getId_producto());
        $this->setInnerJoin($this->tabla_ecom_variantes . " erpc ON erpc.id_variante = ecompv.id_variante");
        return $this->listar();
    }

    public function listar_variantes_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_ecom_productos_variantes . " ecompv");
        $this->setWhere("ecompv.id_variante = " . $this->getId_variante());
        $this->setInnerJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecompv.id_producto");
        return $this->listar();
    }

    public function listar_complementos_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "ecompv.id_producto",
            "ecompv.id_producto_complementario"
        ));
        $this->setTabla("ecom_productos_complementarios ecompv");
        $this->setWhere("ecompv.id_producto = " . $this->getId_producto());
        return $this->listar();
    }

    public function lista_sugeridos_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "ecompv.id_producto",
            "ecompv.id_producto_sugerido"
        ));
        $this->setTabla("ecom_productos_sugeridos ecompv");
        $this->setWhere("ecompv.id_producto = " . $this->getId_producto());
        return $this->listar();
    }

    public function listar_proveedores_producto() {
        $this->limpiarVariables();
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_producto_proveedores);
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setLeftJoin($this->tabla_proveedores . " erpp ON erpp.id_proveedor = " . $this->tabla_producto_proveedores . ".id_proveedor");
        return $this->listar();
    }

    public function consultar_para_editar() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("id_producto = " . $this->getId_producto());
        $this->setTabla($this->tabla_productos);

        return $this->buscarRegistro();
    }

    public function consultar_para_editar_productos() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("id_producto IN (" . $this->getId_producto() . ")");
        $this->setTabla($this->tabla_productos);

        return $this->listar();
    }

    public function listar_productos() {
        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_productos);

        return $this->listar();
    }

    function listar_busqueda_productos_imagen_catalogo() {
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
            "ecomp.estatus",
            "ecompi.tipo_imagen",
            "ecompi.url_imagen",
            "'producto' as tipo_item",
            "ecompcv.factor",
            "ecompcv.id_unidad_venta",
            "ecompcv.solo_en_punto_de_venta",
            "erpuv.unidad_venta",
            "erpuv.abreviatura"
        ));
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_productos_compra_venta . " ecompcv ON ecompcv.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        if (!empty($this->getCategoria())) {
            $this->setInnerJoin($this->tabla_ecom_ . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        } else if (!empty($this->getClasificacion())) {
            $this->setInnerJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        }
        $this->setWhere("ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%'");
        $this->setTabla($this->tabla_productos . " ecomp");

        return $this->listar();
    }

    function listar_busqueda_productos_imagen() {
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
            "ecomp.estatus",
            "ecompi.tipo_imagen",
            "ecompi.url_imagen",
            "'producto' as tipo_item",
            "ecompcv.factor",
            "ecompcv.id_unidad_venta",
            "ecompcv.solo_en_punto_de_venta",
            "erpuv.unidad_venta",
            "erpuv.abreviatura"
        ));
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_productos_compra_venta . " ecompcv ON ecompcv.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        $this->setWhere("ecomp.id_producto = " . $this->getId_producto() . "");
        $this->setTabla($this->tabla_productos . " ecomp");

        return $this->listar();
    }

    function listar_busqueda_ids_productos() {
        $this->setColumnas(array(
            "ecomp.id_producto as id_item",
            "'producto' as tipo_item"
        ));
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_productos_compra_venta . " ecompcv ON ecompcv.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        $this->setWhere("ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%'");
        $this->setTabla($this->tabla_productos . " ecomp");

        return $this->listar();
    }

    public function listar_productos_imagen() {
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
            "ecomp.estatus",
            "ecompi.tipo_imagen",
            "ecompi.url_imagen",
            "'producto' as tipo_item",
            "ecompcv.factor",
            "ecompcv.id_unidad_venta",
            "ecompcv.solo_en_punto_de_venta",
            "erpuv.unidad_venta",
            "erpuv.abreviatura"
        ));
        $this->setLeftJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_productos_compra_venta . " ecompcv ON ecompcv.id_producto = ecomp.id_producto");
        $this->setLeftJoin($this->tabla_erp_unidad_venta . " erpuv ON erpuv.id_unidad_venta = ecompcv.id_unidad_venta");
        $this->setTabla($this->tabla_productos . " ecomp");
        $this->setWhere("ecompi.tipo_imagen = 'portada'");
        return $this->listar();
    }

    public function getId_unidad_compra() {
        return $this->unidad_compra;
    }

    public function getId_unidad_venta() {
        return $this->unidad_venta;
    }

    public function getSolo_en_punto_de_venta() {
        return $this->solo_punto_de_venta;
    }

    public function getFactor() {
        return $this->factor;
    }

    public function setId_unidad_compra($unidad_compra): void {
        $this->unidad_compra = $unidad_compra;
    }

    public function setId_unidad_venta($unidad_venta): void {
        $this->unidad_venta = $unidad_venta;
    }

    public function setSolo_en_punto_de_venta($solo_punto_de_venta): void {
        $this->solo_punto_de_venta = $solo_punto_de_venta;
    }

    public function setFactor($factor): void {
        $this->factor = $factor;
    }

    public function getBusqueda() {
        return $this->busqueda;
    }

    public function setBusqueda($busqueda): void {
        $this->busqueda = $busqueda;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }

    public function getId_categoria() {
        return $this->id_categoria;
    }

    public function setId_categoria($id_categoria): void {
        $this->id_categoria = $id_categoria;
    }

    public function getId_imagen() {
        return $this->id_imagen;
    }

    public function setId_imagen($id_imagen): void {
        $this->id_imagen = $id_imagen;
    }

    public function getUrl_imagen() {
        return $this->url_imagen;
    }

    public function getTipo_imagen() {
        return $this->tipo_imagen;
    }

    public function setUrl_imagen($url_imagen): void {
        $this->url_imagen = $url_imagen;
    }

    public function setTipo_imagen($tipo_imagen): void {
        $this->tipo_imagen = $tipo_imagen;
    }

    public function getUrl_origen() {
        return $this->url_origen;
    }

    public function setUrl_origen($url_origen): void {
        $this->url_origen = $url_origen;
    }

    public function getArchivo_portada() {
        return $this->archivo_portada;
    }

    public function setArchivo_portada($archivo_portada): void {
        $this->archivo_portada = $archivo_portada;
    }

    public function getCodigo_interno() {
        return $this->codigo_interno;
    }

    public function setCodigo_interno($codigo_interno): void {
        $this->codigo_interno = $codigo_interno;
    }

    public function getId_proveedor() {
        return $this->id_proveedor;
    }

    public function setId_proveedor($id_proveedor): void {
        $this->id_proveedor = $id_proveedor;
    }

    public function getId_producto() {
        return $this->id_producto;
    }

    public function setId_producto($id_producto): void {
        $this->id_producto = $id_producto;
    }

    public function getExistencia() {
        return $this->existencia;
    }

    public function setExistencia($existencia): void {
        $this->existencia = $existencia;
    }

    public function getSku() {
        return $this->sku;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setSku($sku): void {
        $this->sku = $sku;
    }

    public function setDescripcion($descripcion): void {
        $this->descripcion = $descripcion;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getDisponible() {
        return $this->disponible;
    }

    public function getPrecio_base() {
        return $this->precio_base;
    }

    public function getPrecio_visible() {
        return $this->precio_visible;
    }

    public function getEstatus() {
        return $this->estatus;
    }

    public function getCodigo_barras_base() {
        return $this->codigo_barras_base;
    }

    public function setNombre($nombre): void {
        $this->nombre = $nombre;
    }

    public function setDisponible($disponible): void {
        $this->disponible = $disponible;
    }

    public function setPrecio_base($precio_base): void {
        $this->precio_base = $precio_base;
    }

    public function setPrecio_visible($precio_visible): void {
        $this->precio_visible = $precio_visible;
    }

    public function setEstatus($estatus): void {
        $this->estatus = $estatus;
    }

    public function setCodigo_barras_base($codigo_barras_base): void {
        $this->codigo_barras_base = $codigo_barras_base;
    }

    public function getCategoria() {
        return $this->categoria;
    }

    public function getClasificacion() {
        return $this->clasificacion;
    }

    public function setCategoria($categoria): void {
        $this->categoria = $categoria;
    }

    public function setClasificacion($clasificacion): void {
        $this->clasificacion = $clasificacion;
    }

    public function getMinima_existencia() {
        return $this->minima_existencia;
    }

    public function getMaxima_existencia() {
        return $this->maxima_existencia;
    }

    public function setMinima_existencia($minima_existencia): void {
        $this->minima_existencia = $minima_existencia;
    }

    public function setMaxima_existencia($maxima_existencia): void {
        $this->maxima_existencia = $maxima_existencia;
    }

    public function getId_marca() {
        return $this->id_marca;
    }

    public function setId_marca($id_marca): void {
        $this->id_marca = $id_marca;
    }

    public function getTipo_atributo() {
        return $this->tipo_atributo;
    }

    public function getValor_atributo() {
        return $this->valor_atributo;
    }

    public function getUnidad_medida_atributo() {
        return $this->unidad_medida_atributo;
    }

    public function getDescripcion_atributo() {
        return $this->descripcion_atributo;
    }

    public function setTipo_atributo($tipo_atributo): void {
        $this->tipo_atributo = $tipo_atributo;
    }

    public function setValor_atributo($valor_atributo): void {
        $this->valor_atributo = $valor_atributo;
    }

    public function setUnidad_medida_atributo($unidad_medida_atributo): void {
        $this->unidad_medida_atributo = $unidad_medida_atributo;
    }

    public function setDescripcion_atributo($descripcion_atributo): void {
        $this->descripcion_atributo = $descripcion_atributo;
    }

    public function getId_atributo() {
        return $this->id_atributo;
    }

    public function setId_atributo($id_atributo): void {
        $this->id_atributo = $id_atributo;
    }

    public function getId_tag() {
        return $this->id_tag;
    }

    public function getTag() {
        return $this->tag;
    }

    public function setId_tag($id_tag): void {
        $this->id_tag = $id_tag;
    }

    public function setTag($tag): void {
        $this->tag = $tag;
    }

    public function getId_variante() {
        return $this->id_variante;
    }

    public function setId_variante($id_variante): void {
        $this->id_variante = $id_variante;
    }

    public function getPrincipal() {
        return $this->principal;
    }

    public function getTipo_producto() {
        return $this->tipo_producto;
    }

    public function setPrincipal($principal): void {
        $this->principal = $principal;
    }

    public function setTipo_producto($tipo_producto): void {
        $this->tipo_producto = $tipo_producto;
    }

    public function getId_sugerido() {
        return $this->id_sugerido;
    }

    public function setId_sugerido($id_sugerido): void {
        $this->id_sugerido = $id_sugerido;
    }

    public function getId_complementario() {
        return $this->id_complementario;
    }

    public function setId_complementario($id_complementario): void {
        $this->id_complementario = $id_complementario;
    }

    public function getTipo_etiqueta() {
        return $this->tipo_etiqueta;
    }

    public function setTipo_etiqueta($tipo_etiqueta): void {
        $this->tipo_etiqueta = $tipo_etiqueta;
    }

    public function getPrecio_oferta() {
        return $this->precio_oferta;
    }

    public function setPrecio_oferta($precio_oferta): void {
        $this->precio_oferta = $precio_oferta;
    }

    public function getClave_sat() {
        return $this->clave_sat;
    }

    public function getClave_unidad_sat() {
        return $this->clave_unidad_sat;
    }

    public function getUnidad() {
        return $this->unidad;
    }

    public function getObjeto_impuesto() {
        return $this->objeto_impuesto;
    }

    public function getTipo_impuesto() {
        return $this->tipo_impuesto;
    }

    public function getPorcentaje_iva() {
        return $this->porcentaje_iva;
    }

    public function getPorcentaje_ieps() {
        return $this->porcentaje_ieps;
    }

    public function getIncluye_iva() {
        return $this->incluye_iva;
    }

    public function getRequiere_factura() {
        return $this->requiere_factura;
    }

    public function getFecha_actualizacion() {
        return $this->fecha_actualizacion;
    }

    public function setClave_sat($clave_sat): void {
        $this->clave_sat = $clave_sat;
    }

    public function setClave_unidad_sat($clave_unidad_sat): void {
        $this->clave_unidad_sat = $clave_unidad_sat;
    }

    public function setUnidad($unidad): void {
        $this->unidad = $unidad;
    }

    public function setObjeto_impuesto($objeto_impuesto): void {
        $this->objeto_impuesto = $objeto_impuesto;
    }

    public function setTipo_impuesto($tipo_impuesto): void {
        $this->tipo_impuesto = $tipo_impuesto;
    }

    public function setPorcentaje_iva($porcentaje_iva): void {
        $this->porcentaje_iva = $porcentaje_iva;
    }

    public function setPorcentaje_ieps($porcentaje_ieps): void {
        $this->porcentaje_ieps = $porcentaje_ieps;
    }

    public function setIncluye_iva($incluye_iva): void {
        $this->incluye_iva = $incluye_iva;
    }

    public function setRequiere_factura($requiere_factura): void {
        $this->requiere_factura = $requiere_factura;
    }

    public function setFecha_actualizacion($fecha_actualizacion): void {
        $this->fecha_actualizacion = $fecha_actualizacion;
    }
}
