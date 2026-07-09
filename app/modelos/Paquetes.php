<?php

class Paquetes extends CRUD {

    private $string_ids;
    private $id_paquete;
    private $identificador;
    private $identificador_clasificacion;
    private $busqueda;
    private $tabla_paquetes = "ecom_paquetes";
    private $tabla_paquetes_imagenes = "ecom_paquetes_imagenes";
    private $tabla_paquetes_productos = "ecom_paquetes_productos";
    private $tabla_ecom_categorias = "ecom_categorias";
    private $tabla_ecom_productos_categorias = "ecom_productos_categorias";
    private $tabla_paquetes_productos_imagenes = "ecom_paquetes_imagenes";
    private $tabla_proveedores = "erp_proveedores";
    private $tabla_categorias = "ecom_categorias";
    private $tabla_producto_proveedores = "ecom_productos_proveedores";
    private $tabla_producto_categorias = "ecom_productos_categorias";
    private $tabla_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_ecom_productos = "ecom_productos";
    private $tabla_ecom_paquetes_productos = "ecom_paquetes_productos";
    private $recursos_productos = "media/apps/ecommerce/paquete/";

    function paquetes_por_ids_productos() {
        $campos = array(
            "DISTINCT ecomp.id_paquete as id_item",
            "'paquete' as tipo_item",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomc.categoria",
            "ecomc.identificador_categoria",
            "ecomcpc.url_imagen_categoria_catalogo",
            "ecomcpca.url_imagen_portada_clasificacion"
        );

        $this->setColumnas($campos);
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setInnerJoin($this->tabla_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_paquetes_productos . " ecompp ON ecompp.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_producto = ecompp.id_producto");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_categorias ecomcc ON ecomcc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_portadas_catalogo ecomcpca ON ecomcpca.id_clasificacion = ecomcc.id_clasificacion");
        $this->setOrderBy("ecomcpc.url_imagen_categoria_catalogo");
        $this->setWhere("ecompp.id_producto  IN(" . $this->getString_ids() . ")");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("ecomp.existencia > 0");
        return $this->listar();
    }
    
    function paquetes_por_ids_productos_identificador_categoria() {
        $campos = array(
            "DISTINCT ecomp.id_paquete as id_item",
            "'paquete' as tipo_item",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomc.categoria",
            "ecomc.identificador_categoria",
            "ecomcpc.url_imagen_categoria_catalogo",
            "ecomcpca.url_imagen_portada_clasificacion"
        );

        $this->setColumnas($campos);
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setInnerJoin($this->tabla_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_paquetes_productos . " ecompp ON ecompp.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_producto = ecompp.id_producto");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_categorias ecomcc ON ecomcc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones ecomcs ON ecomcs.id_clasificacion = ecomcc.id_clasificacion");
        $this->setInnerJoin("ecom_clasificaciones_portadas_catalogo ecomcpca ON ecomcpca.id_clasificacion = ecomcc.id_clasificacion");
        $this->setOrderBy("ecomcpc.url_imagen_categoria_catalogo");
        $this->setWhere("ecompp.id_producto  IN(" . $this->getString_ids() . ")");
        $this->setAnd("ecomc.identificador_categoria = '".$this->getIdentificador()."'");
        $this->setAnd("ecomcs.identificador = '".$this->getIdentificador_clasificacion()."'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("ecomp.existencia > 0");
        return $this->listar();
    }

    function listar_busqueda_paquetes_imagen() {
        $this->setColumnas(array(
            "ecomp.id_paquete",
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
            "'paquete' as tipo_item"
        ));
        $this->setInnerJoin($this->tabla_paquetes_productos_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
//    $this->setWhere("ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%'");
        $this->setWhere($this->getBusqueda());
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setLimit(6);

        return $this->listar();
    }
    
    function listar_busqueda_paquetes_ids() {
        $this->setColumnas(array(
            "ecomp.id_paquete",
            "'paquete' as tipo_item"
        ));
        $this->setInnerJoin($this->tabla_paquetes_productos_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
//    $this->setWhere("ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%'");
        $this->setWhere($this->getBusqueda());
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setLimit(6);

        return $this->listar();
    }

    public function consultar_id_paquete() {
        $campos = array(
            "ecomp.id_paquete"
        );
        $this->setWhere("ecomp.identificador = '" . $this->getIdentificador() . "'");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_productos . " ecomp");
        return $this->buscarRegistro();
    }

    public function consultar_paquetes() {
        $campos = array(
            "DISTINCT ecomc.categoria",
            "ecomc.identificador_categoria",
            "ecomc.url_portada"
        );
        $this->setAnd("ecomp.estatus = 1");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setInnerJoin($this->tabla_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_paquetes_productos . " ecompp ON ecompp.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_productos . " ecommp ON ecommp.id_producto = ecompp.id_producto");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_producto = ecommp.id_producto");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");

        return $this->listar();
    }

    public function consultar_paquetes_por_categoria() {
        $campos = array(
            "DISTINCT ecomp.id_paquete",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomp.descripcion",
            "'paquete' as tipo_item"
        );
        $this->setWhere("ecomc.identificador_categoria = '" . $this->getIdentificador() . "'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setInnerJoin($this->tabla_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_paquetes_productos . " ecompp ON ecompp.id_paquete = ecomp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_producto = ecompp.id_producto");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecompc.id_categoria");

        return $this->listar();
    }

    public function consultar() {
        $campos = array(
            "ecomp.id_paquete",
            "ecomp.codigo_interno",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomp.descripcion",
            "'paquete' as tipo_item"
        );
        $this->setWhere("ecomp.identificador = '" . $this->getIdentificador() . "'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setInnerJoin($this->tabla_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");

        return $this->buscarRegistro();
    }

    public function consultar_productos_paquete() {
        $this->setColumnas(array(
            "id_paquete",
            "id_producto",
            "cantidad"
        ));
        $this->setWhere("id_paquete = " . $this->getId_paquete());
        $this->setTabla($this->tabla_paquetes_productos);

        return $this->listar();
    }

    public function consultar_paquete() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setWhere("id_paquete = " . $this->getId_paquete());
        $this->setAnd("estatus = 1");
        $this->setAnd("existencia > 0");
        $this->setTabla($this->tabla_paquetes);

        return $this->buscarRegistro();
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

    public function getId_paquete() {
        return $this->id_paquete;
    }

    public function setId_paquete($id_paquete): void {
        $this->id_paquete = $id_paquete;
    }

    public function getString_ids() {
        return $this->string_ids;
    }

    public function setString_ids($string_ids): void {
        $this->string_ids = $string_ids;
    }
    
    public function getIdentificador_clasificacion() {
        return $this->identificador_clasificacion;
    }

    public function setIdentificador_clasificacion($identificador_clasificacion): void {
        $this->identificador_clasificacion = $identificador_clasificacion;
    }


}
