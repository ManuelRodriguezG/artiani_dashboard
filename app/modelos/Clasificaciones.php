<?php

class Clasificaciones extends CRUD {

    private $identificador_clasificacion;
    private $identificador_categoria;
    private $tabla_ecom_clasificaciones = "ecom_clasificaciones";
    private $tabla_ecom_clasificaciones_categorias = "ecom_clasificaciones_categorias";
    private $tabla_ecom_categorias = "ecom_categorias";
    private $tabla_ecom_productos_categorias = "ecom_productos_categorias";
    private $tabla_ecom_productos = "ecom_productos";
    private $tabla_ecom_productos_imagenes = "ecom_productos_imagenes";
    private $tabla_ecom_paquetes_productos = "ecom_paquetes_productos";
    private $tabla_ecom_paquetes = "ecom_paquetes";
    private $tabla_ecom_paquetes_imagenes = "ecom_paquetes_imagenes";
    private $tabla_productos_compra_venta = "ecom_productos_compra_venta";

    public function consultar_categorias_por_clasificacion() {
        $campos = array(
            "ecomc.categoria",
            "ecomc.url_portada",
            "ecomc.url_categoria",
            "RAND() AS random"
        );
        $this->setWhere("ecomcc.id_clasificacion = (SELECT ecomcc.id_clasificacion FROM ecom_categorias ecomc INNER JOIN ecom_clasificaciones_categorias ecomcc ON ecomcc.id_categoria = ecomc.id_categoria WHERE ecomc.identificador_categoria = '" . $this->getIdentificador_categoria() . "' limit 1)");
        $this->setAnd("ecomc.id_categoria != (SELECT ecomc.id_categoria FROM ecom_categorias ecomc WHERE ecomc.identificador_categoria = '" . $this->getIdentificador_categoria() . "')");
        $this->setColumnas($campos);
        $this->setOrderBy("random");
        $this->setTabla($this->tabla_ecom_clasificaciones_categorias . " ecomcc");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecomcc.id_categoria ");

        return $this->listar();
    }

    public function consultar_productos_por_clasificacion() {
        $campos = array(
            "ecomp.id_producto AS id_item",
            "'producto' AS tipo_item",
            "ecomp.codigo_interno",
            "ecomc.identificador_categoria",
            "ecomc.url_categoria",
            "ecomc.url_portada",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomc.categoria",
            "ecomcpc.url_imagen_categoria_catalogo",
            "ecomcpca.url_imagen_portada_clasificacion"
        );
        $this->setWhere("ecomcs.identificador = '" . $this->getIdentificador_clasificacion() . "'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("(ecomp.existencia > 0 OR ecomp.siempre_disponible = 1)");
        $this->setOrderBy("ecomc.categoria ASC");
        $this->setAnd("ecompcv.solo_en_punto_de_venta = 0");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_ecom_clasificaciones . " ecomcs");
        $this->setInnerJoin($this->tabla_ecom_clasificaciones_categorias . " ecomcc ON ecomcc.id_clasificacion = ecomcs.id_clasificacion");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecomcc.id_categoria");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_categoria = ecomc.id_categoria");
        $this->setInnerJoin($this->tabla_ecom_productos . " ecomp ON ecomp.id_producto = ecompc.id_producto");
        $this->setInnerJoin($this->tabla_ecom_productos_imagenes . " ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setInnerJoin($this->tabla_productos_compra_venta . " ecompcv ON ecomp.id_producto = ecompcv.id_producto");
        $this->setInnerJoin("ecom_categoria_producto_catalogo ecomcpc ON ecomcpc.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_categorias ecomcca ON ecomcca.id_categoria = ecompc.id_categoria");
        $this->setInnerJoin("ecom_clasificaciones_portadas_catalogo ecomcpca ON ecomcpca.id_clasificacion = ecomcc.id_clasificacion");
        $this->setOrderBy("ecomcpc.url_imagen_categoria_catalogo");
        return $this->listar();
    }

    public function consultar_paquetes_por_clasificacion() {
        $campos = array(
            "DISTINCT ecomp.id_paquete AS id_item",
            "'paquete' AS tipo_item",
            "ecomp.codigo_interno",
            "ecomc.identificador_categoria",
            "ecomc.url_categoria",
            "ecomc.url_portada",
            "ecomp.nombre",
            "ecomp.precio_base",
            "CONCAT('" . RUTA_RECURSOS_IMG . "',ecompi.url_imagen) as url_imagen",
            "ecomp.identificador",
            "ecomc.categoria"
        );
        $this->setWhere("ecomcs.identificador = '" . $this->getIdentificador_clasificacion() . "'");
        $this->setAnd("ecomp.estatus = 1");
        $this->setAnd("(ecomp.existencia > 0 OR ecomp.siempre_disponible = 1)");
        $this->setOrderBy("ecomc.categoria ASC");

        $this->setColumnas($campos);
        $this->setTabla($this->tabla_ecom_clasificaciones . " ecomcs");
        $this->setInnerJoin($this->tabla_ecom_clasificaciones_categorias . " ecomcc ON ecomcc.id_clasificacion = ecomcs.id_clasificacion");
        $this->setInnerJoin($this->tabla_ecom_categorias . " ecomc ON ecomc.id_categoria = ecomcc.id_categoria");
        $this->setInnerJoin($this->tabla_ecom_productos_categorias . " ecompc ON ecompc.id_categoria = ecomc.id_categoria");
        $this->setInnerJoin($this->tabla_ecom_paquetes_productos . " ecompp ON ecompp.id_producto = ecompc.id_producto");
        $this->setInnerJoin($this->tabla_ecom_paquetes . " ecomp ON ecomp.id_paquete = ecompp.id_paquete");
        $this->setInnerJoin($this->tabla_ecom_paquetes_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");

        return $this->listar();
    }

    public function consultar_id_clasificacion_por_identificador() {
        $campos = array(
            "ecomcs.id_clasificacion",
        );
        $this->setWhere("ecomcs.identificador = '" . $this->getIdentificador_clasificacion() . "'");
        $this->setColumnas($campos);
        $this->setTabla($this->tabla_ecom_clasificaciones . " ecomcs");

        return $this->buscarRegistro();
    }

    public function getIdentificador_categoria() {
        return $this->identificador_categoria;
    }

    public function setIdentificador_categoria($identificador_categoria): void {
        $this->identificador_categoria = $identificador_categoria;
    }

    public function getIdentificador_clasificacion() {
        return $this->identificador_clasificacion;
    }

    public function setIdentificador_clasificacion($identificador_clasificacion): void {
        $this->identificador_clasificacion = $identificador_clasificacion;
    }
}
