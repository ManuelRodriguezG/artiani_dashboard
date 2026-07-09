<?php

class Paquete extends CRUD {

  public $nombre;
  private $sku;
  private $descripcion;
  private $disponible;
  private $precio_base;
  private $precio_visible;
  private $estatus;
  private $codigo_barras_base;
  private $codigo_interno;
  private $existencia;
  private $id_producto;
  private $id_imagen;
  private $id_paquete;
  private $id_proveedor;
  private $id_categoria;
  private $archivo_portada;
  private $url_origen;
  private $url_imagen;
  private $tipo_imagen;
  private $busqueda;
  private $identificador;
  private $cantidad_producto_paquete;
  private $error;
  private $tabla_paquetes = "ecom_paquetes";
  private $tabla_paquetes_productos = "ecom_paquetes_productos";
  private $tabla_paquetes_productos_imagenes = "ecom_paquetes_imagenes";
  private $tabla_proveedores = "erp_proveedores";
  private $tabla_categorias = "ecom_categorias";
  private $tabla_producto_proveedores = "ecom_productos_proveedores";
  private $tabla_producto_categorias = "ecom_productos_categorias";
  private $tabla_productos_imagenes = "ecom_productos_imagenes";
  private $recursos_productos = "media/apps/ecommerce/paquete/";

  public function guardar_imagenes() {
    $urlRecursos = $this->recursos_productos . $this->getId_paquete() . "/";
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
        "id_paquete",
        "tipo_imagen",
        "url_imagen"
    );
    $valores_registrar = array(
        $this->getId_paquete(),
        $this->getTipo_imagen(),
        $this->getUrl_imagen()
    );
//    var_dump($campos);
//    var_dump($valores_registrar);
    $this->setTabla($this->tabla_paquetes_productos_imagenes);
    $this->setColumnas($campos_registrar);
    $this->setColumnasValores($valores_registrar);
    $respuesta = $this->insertar();
    return $respuesta;
  }

  public function eliminar_imagen() {
    $this->setTabla($this->tabla_paquetes_productos_imagenes);
    $this->setAnd("tipo_imagen = '" . $this->getTipo_imagen() . "'");
    $this->setWhere("id_paquete = " . $this->getId_paquete());
    $respuesta = $this->eliminar();
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
        "codigo_interno",
        "existencia",
        "identificador"
    );
    $valores_registrar = array(
        $this->nombre,
        $this->sku,
        $this->descripcion,
        $this->disponible,
        $this->precio_base,
        $this->estatus,
        $this->codigo_barras_base,
        $this->codigo_interno,
        $this->existencia,
        $this->identificador
    );
//    var_dump($campos);
//    var_dump($valores_registrar);
    $this->setColumnas($campos_registrar);
    $this->setColumnasValores($valores_registrar);
    $this->setTabla($this->tabla_paquetes);
    $respuesta = $this->insertar();
    return $respuesta;
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
        "estatus",
        "existencia",
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
        $this->estatus,
        $this->existencia,
        $this->codigo_barras_base,
        $this->codigo_interno,
        $this->identificador
    );
//    var_dump($campos);
//    var_dump($valores_registrar);
    $this->setWhere("id_paquete = " . $this->getId_paquete());
    $this->setTabla($this->tabla_paquetes);
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

  //buscarRegistro($campo, $tabla, $elemento, $busqueda, $and = null)
  public function consultar() {

    $this->setColumnas(array(
        "*"
    ));
    $this->setTabla($this->tabla_productos);

    return $this->buscarRegistro();
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
    $this->setLeftJoin($this->tabla_paquetes_productos_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
    $this->setWhere("ecomp.id_paquete = " . $this->getId_paquete());
    $this->setTabla($this->tabla_paquetes . " ecomp");

    return $this->listar();
  }

  public function registrar_producto_paquete() {
    $this->setTabla($this->tabla_paquetes_productos);
    $columnas = array(
        "id_producto",
        "id_paquete",
        "cantidad"
    );
    $valores = array(
        $this->getId_producto(),
        $this->getId_paquete(),
        $this->getCantidad_producto_paquete()
    );
    $this->setColumnas($columnas);
    $this->setColumnasValores($valores);
    return $this->insertar();
  }

  public function eliminar_productos_paquete() {
    $this->setTabla($this->tabla_paquetes_productos);
    $this->setWhere("id_paquete = " . $this->getId_paquete());
    return $this->eliminar();
  }

  public function eliminar_categorias_producto() {
    $this->setTabla($this->tabla_producto_categorias);
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
    $this->setTabla($this->tabla_paquetes_productos_imagenes);
    $this->setWhere("id_paquete = " . $this->getId_paquete());
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
    $this->setWhere("id_paquete = " . $this->getId_paquete());
    $this->setTabla($this->tabla_paquetes);

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

  public function listar_paquete_productos() {
    $this->setColumnas(array(
        "*"
    ));
    $this->setTabla($this->tabla_paquetes_productos);
    $this->setWhere("id_paquete = " . $this->getId_paquete());
    return $this->listar();
  }

  public function listar_paquetes_imagen() {
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
    $this->setLeftJoin($this->tabla_paquetes_productos_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
    $this->setTabla($this->tabla_paquetes . " ecomp");

    return $this->listar();
  }
  
  function listar_busqueda_paquetes_ids() {
        $this->setColumnas(array(
            "ecomp.id_paquete as id_item",
            "'paquete' as tipo_item"
        ));
        $this->setInnerJoin($this->tabla_paquetes_productos_imagenes . " ecompi ON ecompi.id_paquete = ecomp.id_paquete");
//    $this->setWhere("ecomp.codigo_barras LIKE '%" . $this->getBusqueda() . "%' OR ecomp.codigo_interno LIKE '%" . $this->getBusqueda() . "%' OR ecomp.sku LIKE '%" . $this->getBusqueda() . "%' OR ecomp.nombre LIKE '%" . $this->getBusqueda() . "%' OR ecomp.descripcion LIKE '%" . $this->getBusqueda() . "%'");
        $this->setWhere($this->getBusqueda());
        $this->setTabla($this->tabla_paquetes . " ecomp");
        $this->setLimit(6);

        return $this->listar();
    }

  public function getBusqueda() {
    return $this->busqueda;
  }

  public function setBusqueda($busqueda): void {
    $this->busqueda = $busqueda;
  }

  public function getCantidad_producto_paquete() {
    return $this->cantidad_producto_paquete;
  }

  public function setCantidad_producto_paquete($cantidad_producto_paquete): void {
    $this->cantidad_producto_paquete = $cantidad_producto_paquete;
  }

  public function getId_paquete() {
    return $this->id_paquete;
  }

  public function setId_paquete($id_paquete): void {
    $this->id_paquete = $id_paquete;
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

}
