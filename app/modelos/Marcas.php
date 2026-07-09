<?php

class Marcas extends CRUD {

    private $nombre;
    private $descripcion;
    private $id_marca;
    private $url_origen;
    private $url_imagen;
    private $tipo_imagen;
    private $url_marca;
    private $identificador;
    private $archivo_portada;
    private $tabla_marcas = "ecom_marcas";
    private $recursos_marcas = "media/apps/ecommerce/marcas/";
    private $identificador_marca;
    private $tabla_marcas_destacadas = "ecom_categorias_destacadas";
    private $tabla_productos = "ecom_productos";
    private $tabla_productos_marcas = "ecom_productos_marcas";
    private $tabla_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_paquetes = "ecom_paquetes";
    private $tabla_paquetes_productos = "ecom_paquetes_productos";
    private $tabla_paquetes_imagenes = "ecom_paquetes_imagenes";
    private $tabla_productos_compra_venta = "ecom_productos_compra_venta";

    public function registrar() {
        $campos = array(
            "marca",
            "descripcion",
            "url_marca",
            "identificador_marca"
        );
        $valores = array(
            $this->getNombre(),
            $this->getDescripcion(),
            $this->getUrl_categoria(),
            $this->getIdentificador()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla($this->tabla_marcas);
        return $this->insertar();
    }

    public function consultar() {
        $campos = array(
            "id_marca",
            "marca",
            "descripcion",
            "url_marca",
            "identificador_marca"
        );
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_marcas);
        return $this->listar();
    }

    public function guardar_imagenes() {
        $urlRecursos = $this->recursos_marcas . $this->getId_categoria() . "/";
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
//            var_dump($archivo_guardado);
//            var_dump($this->getUrl_origen());
//            var_dump($urlDestino);
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
            $return = array('error' => false, 'tipo' => 'success', 'mensaje' => 'El archivo fue guardado con éxito', 'depurar' => RUTA_URL . $urlDestino);
        } else {
            $return = array('error' => true, 'tipo' => 'danger', 'mensaje' => 'El archivo no fue guardado');
        }
        return $return;
    }

    public function consultar_productos_categoria() {
        $campos = array(
            "ecomp.id_producto as id_item",
            "'producto' as tipo_item",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.sku",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomc.categoria",
            "ecomc.identificador_categoria",
            "ecomcpc.url_imagen_categoria_catalogo",
            "ecomcpca.url_imagen_portada_clasificacion"
        );
        $this->setWhere("ecomc.identificador_categoria = '" . $this->getIdentificador_categoria() . "'");
        $this->setAnd("ecomcs.identificador = '".$this->getIdentificador()."'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("(ecomp.existencia > 0 OR ecomp.siempre_disponible = 1)");
        $this->setAnd("ecompcv.solo_en_punto_de_venta = 0");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_productos . " ecomp");
        $this->setInnerJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setInnerJoin($this->tabla_productos_categorias . " ecompc ON ecompc.id_producto = ecomp.id_producto");
        $this->setInnerJoin($this->tabla_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin($this->tabla_productos_compra_venta . " ecompcv ON ecomp.id_producto = ecompcv.id_producto");
        $this->setInnerJoin("ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_categorias ecomcc ON ecomcc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones ecomcs ON ecomcs.id_clasificacion = ecomcc.id_clasificacion");
        $this->setInnerJoin("ecom_clasificaciones_portadas_catalogo ecomcpca ON ecomcpca.id_clasificacion = ecomcc.id_clasificacion");
        $this->setOrderBy("ecomcpc.url_imagen_categoria_catalogo");
        return $this->listar();
    }

    public function consultar_productos_categoria_generales() {
        $campos = array(
            "ecomp.id_producto as id_item",
            "'producto' as tipo_item",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.precio_base",
            "ecomp.sku",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomcpc.url_imagen_categoria_catalogo",
            "ecomcpca.url_imagen_portada_clasificacion"
        );
        $this->setWhere("ecomc.identificador_categoria = '" . $this->getIdentificador_categoria() . "'");
        $this->setAnd("ecomcs.identificador = '".$this->getIdentificador()."'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("(ecomp.existencia > 0 OR ecomp.siempre_disponible = 1)");
//    $this->setAnd("ecompcv.solo_en_punto_de_venta = 1");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_productos . " ecomp");
        $this->setInnerJoin($this->tabla_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setInnerJoin($this->tabla_productos_categorias . " ecompc ON ecompc.id_producto = ecomp.id_producto");
        $this->setInnerJoin($this->tabla_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin($this->tabla_productos_compra_venta . " ecompcv ON ecomp.id_producto = ecompcv.id_producto");
        $this->setInnerJoin("ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_categorias ecomcc ON ecomcc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones ecomcs ON ecomcs.id_clasificacion = ecomcc.id_clasificacion");
        $this->setInnerJoin("ecom_clasificaciones_portadas_catalogo ecomcpca ON ecomcpca.id_clasificacion = ecomcc.id_clasificacion");
        $this->setOrderBy("ecomcpc.url_imagen_categoria_catalogo");

        return $this->listar();
    }

    public function actualizar_imagen() {
        //$tabla
        //$campos
        //$valores
        $campos_registrar = array(
            "url_portada"
        );
        $valores_registrar = array(
            $this->getUrl_imagen()
        );
//    var_dump($campos);
//    var_dump($valores_registrar);
        $this->setTabla($this->tabla_marcas);
        $this->setColumnas($campos_registrar);
        $this->setColumnasValores($valores_registrar);
        $this->setWhere("id_marca = " . $this->getId_categoria());
        $respuesta = $this->update();
        return $respuesta;
    }

    public function getId_categoria() {
        return $this->id_categoria;
    }

    public function getUrl_origen() {
        return $this->url_origen;
    }

    public function getTipo_imagen() {
        return $this->tipo_imagen;
    }

    public function setId_categoria($id_categoria): void {
        $this->id_categoria = $id_categoria;
    }

    public function setUrl_origen($url_origen): void {
        $this->url_origen = $url_origen;
    }

    public function setTipo_imagen($tipo_imagen): void {
        $this->tipo_imagen = $tipo_imagen;
    }

    public function getArchivo_portada() {
        return $this->archivo_portada;
    }

    public function setArchivo_portada($archivo_portada): void {
        $this->archivo_portada = $archivo_portada;
    }

    public function getUrl_imagen() {
        return $this->url_imagen;
    }

    public function setUrl_imagen($url_imagen): void {
        $this->url_imagen = $url_imagen;
    }

    public function getUrl_categoria() {
        return $this->url_categoria;
    }

    public function getIdentificador() {
        return $this->identificador;
    }

    public function setUrl_categoria($url_categoria): void {
        $this->url_categoria = $url_categoria;
    }

    public function setIdentificador($identificador): void {
        $this->identificador = $identificador;
    }

    public function getNombre() {
        return $this->nombre;
    }

    public function getDescripcion() {
        return $this->descripcion;
    }

    public function setNombre($nombre): void {
        $this->nombre = $nombre;
    }

    public function setDescripcion($descripcion): void {
        $this->descripcion = $descripcion;
    }

    public function getIdentificador_categoria() {
        return $this->identificador_categoria;
    }

    public function setIdentificador_categoria($identificador_categoria): void {
        $this->identificador_categoria = $identificador_categoria;
    }
}
