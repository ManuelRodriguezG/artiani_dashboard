<?php

class Utilidades extends CRUD {

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
    private $nombre_grupo_utilidad;
    private $porcentaje_total;
    private $id_tipo_lista_mayoreo;
    private $id_lista_mayoreo;
    private $id_producto;
    private $identificador;
    private $id_categoria;
    private $tabla_sys_usuarios_mayoreo = "sys_usuarios_mayoreo";
    private $tabla_sys_usuarios_mayoreo_informacion_negocio = "sys_usuarios_mayoreo_informacion_negocio";
    private $tabla_erp_usuarios_mayoreo_listas_mayoreo = "erp_usuarios_mayoreo_listas_mayoreo";
    private $id_usuario_mayoreo;

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
    private $id_utilidad_grupo_elemento;
    private $producto;
    private $precio;

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

    public function consultar_productos_grupo_utilidad() {
        $this->setColumnas(array(
            "erpug.porcentaje_utilidad",
            "ecomp.sku",
            "ecomp.nombre",
            "erpuge.costo",
            "erpuge.precio",
            "erpp.proveedor",
            "erpplp.costo/1.16 as costo_actualizado",
            "ecomp.precio_base/1.16 as precio_actualizado"
        ));

        $this->setTabla("erp_utilidad_grupos erpug");
        $this->setLeftJoin("erp_utilidad_grupos_elementos erpuge ON erpuge.id_utilidad_grupo = erpug.id_utilidad_grupo");
        $this->setLeftJoin("ecom_productos ecomp ON ecomp.id_producto = erpuge.id_elemento");
        $this->setLeftJoin("ecom_productos_proveedores ecompp ON ecompp.id_producto = ecomp.id_producto");
        $this->setLeftJoin("erp_proveedores_listas erppl ON erppl.id_proveedor = ecompp.id_proveedor");
        $this->setLeftJoin("erp_proveedores_listas_productos erpplp ON erpplp.id_lista_proveedor = erppl.id_lista_proveedor");
        $this->setLeftJoin("erp_proveedores erpp ON erpp.id_proveedor = ecompp.id_proveedor");
        $this->setWhere("erpug.id_utilidad_grupo = " . $this->getId_proveedor_pedido());
        $this->setAnd("erpplp.sku = ecomp.sku");
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

    function listar_busqueda_productos() {
        $this->setColumnas(array(
            "DISTINCT ecomp.id_producto",
            "ecomp.nombre",
            "ecomp.precio_base/1.16 as precio_base",
            "ecomp.sku",
            "ecomp.tipo",
            "ecompi.url_imagen",
            "erpplp.costo/1.16 as costo"
        ));
        $this->setWhere("(ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%')");
        $this->setTabla("ecom_productos ecomp");
        $this->setLeftJoin("ecom_productos_imagenes ecompi ON ecompi.id_producto = ecomp.id_producto");
        $this->setLeftJoin("ecom_productos_proveedores ecompp ON ecompp.id_producto = ecomp.id_producto");
        $this->setLeftJoin("erp_proveedores_listas erppl ON erppl.id_proveedor = ecompp.id_proveedor");
        $this->setLeftJoin("erp_proveedores_listas_productos erpplp ON erpplp.id_lista_proveedor = erppl.id_lista_proveedor");
        $this->setAnd("erpplp.sku = ecomp.sku");
        return $this->listar();
    }

    function registrar_grupo_utilidad() {
        $campos = array(
            "porcentaje_utilidad",
            "comentario",
            "fch_r",
            "estatus",
            "titulo"
        );
        $valores = array(
            $this->getPorcentaje_total(),
            $this->getComentario(),
            DATE_NOW,
            $this->getEstatus(1),
            $this->getTitulo()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla("erp_utilidad_grupos");
        return $this->insertar();
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
            "id_utilidad_grupo",
            'titulo',
            "porcentaje_utilidad",
            "fch_r"
        ));
        $this->setTabla("erp_utilidad_grupos");
        $this->setOrderBy("id_utilidad_grupo");
        $this->setAscDesc("DESC");
        return $this->listar();
    }

    function registrar_elementos_pedido() {
        $campos = array(
            "id_utilidad_grupo",
            "id_elemento",
            "costo",
            "precio"
        );
        $valores = array(
            $this->getId_utilidad_grupo_elemento(),
            $this->getId_producto(),
            $this->getCosto(),
            $this->getPrecio()
        );
        $this->setColumnas($campos);
        $this->setColumnasValores($valores);
        $this->setTabla("erp_utilidad_grupos_elementos");
        return $this->insertar();
    }

    public function consultar() {

        $this->setColumnas(array(
            "*"
        ));
        $this->setTabla($this->tabla_proveedores);

        return $this->buscarRegistro();
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

    public function getNombre_grupo_utilidad() {
        return $this->nombre_grupo_utilidad;
    }

    public function getPorcentaje_total() {
        return $this->porcentaje_total;
    }

    public function setNombre_grupo_utilidad($nombre_grupo_utilidad): void {
        $this->nombre_grupo_utilidad = $nombre_grupo_utilidad;
    }

    public function setPorcentaje_total($porcentaje_total): void {
        $this->porcentaje_total = $porcentaje_total;
    }

    public function getId_utilidad_grupo_elemento() {
        return $this->id_utilidad_grupo_elemento;
    }

    public function getProducto() {
        return $this->producto;
    }

    public function getPrecio() {
        return $this->precio;
    }

    public function setId_utilidad_grupo_elemento($id_utilidad_grupo_elemento): void {
        $this->id_utilidad_grupo_elemento = $id_utilidad_grupo_elemento;
    }

    public function setProducto($producto): void {
        $this->producto = $producto;
    }

    public function setPrecio($precio): void {
        $this->precio = $precio;
    }
}
