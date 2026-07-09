<?php

class Paquetes extends Controlador {

  function index() {
    $this->vista("apps/ecommerce/catalog/paquetes");
  }

  function nuevo() {
    $this->vista("apps/ecommerce/catalog/paquete-nuevo");
  }

  function editar() {
    $this->vista('apps/ecommerce/catalog/paquete-editar');
  }

  public function crear_identificador($cadena) {

    //Reemplazamos espacios por _
    $cadena = str_replace(
            array(' '),
            array('_'),
            $cadena
    );
    //Reemplazamos la A y a
    $cadena = str_replace(
            array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
            array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
            $cadena
    );

    //Reemplazamos la E y e
    $cadena = str_replace(
            array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
            array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
            $cadena);

    //Reemplazamos la I y i
    $cadena = str_replace(
            array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
            array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
            $cadena);

    //Reemplazamos la O y o
    $cadena = str_replace(
            array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
            array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
            $cadena);

    //Reemplazamos la U y u
    $cadena = str_replace(
            array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
            array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
            $cadena);

    //Reemplazamos la N, n, C y c
    $cadena = str_replace(
            array('Ñ', 'ñ', 'Ç', 'ç'),
            array('N', 'n', 'C', 'c'),
            $cadena
    );
    return $cadena;
  }

  public function actualizar_portada() {
//    var_dump($_FILES);

    $id_paquete = $_POST['id_paquete'];
    $tipo_imagen = $_POST['tipo_imagen'];

    $producto = $this->modelo("Paquete");
//    var_dump($_FILES['portada']);
    $producto->setId_producto($_POST['id_producto']);
    $producto->setUrl_origen($_FILES['portada']['tmp_name']);
    $extension = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
    $fecha = new DateTime();
    $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
    //guardar archivo
    $response = $producto->guardar_imagenes();

    if ($response['error'] == false) {
      $url_imagen = $response['depurar'];
      //eliminar imagen
      $producto->setId_paquete($id_paquete);
      $producto->setTipo_imagen($tipo_imagen);
      $response = $producto->eliminar_imagen();
      if ($response['error'] == false) {
        //insertar
        $producto->setUrl_imagen($url_imagen);
        $producto->setTipo_imagen($tipo_imagen);
        $response = $producto->registrar_imagen();
      }
    }
    echo json_encode($response);
  }

  function registrar() {
    $producto_nombre = $_POST["paquete_nombre"];
    $producto_descripcion = $_POST['descripcion'];
    $producto_siempre_disponible = $_POST["siempre_disponible"];
    $producto_precio_base = $_POST["precio_base"];
    $producto_codigo_unico = $_POST["codigo_unico"];
    $producto_codigo_barras = $_POST["codigo_barras"];
    $producto_codigo_interno = $_POST["codigo_interno"];
    $producto_cantidad = $_POST["cantidad"];
    $productos = $_POST['productos'];
    $identificador = $this->crear_identificador($producto_nombre);
//    $producto = $this->modelo("Productos");
//    var_dump($producto);
    $paquete = $this->modelo("Paquete");
    $existencia = $this->existencia($productos);
    $paquete->setIdentificador($identificador);
    $paquete->setNombre($producto_nombre);
    $paquete->setSku($producto_codigo_unico);
    $paquete->setCodigo_interno($producto_codigo_interno);
    $paquete->setDescripcion($producto_descripcion);
//    var_dump($producto->getNombre());
    $paquete->setDisponible($producto_siempre_disponible);
    $paquete->setPrecio_base($producto_precio_base);
    $paquete->setEstatus(1);
    $paquete->setExistencia($existencia);
    $paquete->setCodigo_barras_base($producto_codigo_barras);
    $respuesta = $paquete->registrar();
    if ($respuesta['error'] == false) {
      $errores_productos_paquete = array();
      $id_paquete = $respuesta['depurar'];
      foreach ($productos as $key => $value) {
        $paquete->setId_producto($value['id_producto']);
        $paquete->setId_paquete($id_paquete);
        $paquete->setCantidad_producto_paquete($value['cantidad']);
        $respuesta = $paquete->registrar_producto_paquete();
        if ($respuesta['error'] == true) {
          $errores_productos_paquete[] = $respuesta['mensaje'];
        }
      }

      if (sizeof($errores_productos_paquete) > 0) {
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Error al registrar los productos",
            'depurar' => []
        ];
      } else {
        $respuesta = [
            'error' => false,
            'tipo' => "success",
            'mensaje' => "Paquete registrado con éxito",
            'depurar' => $id_paquete
        ];
      }
    }

    echo json_encode($respuesta);
  }

  private function existencia($productos) {
    $arr_existencias = array();
    $arr_productos = array();
    $existencia = 0;
    $existencia_mas_baja = 0;
    $length_productos = sizeof($productos);
    foreach ($productos as $key => $value) {
      if ($value['existencia'] == 0) {
        $existencia_mas_baja = 0;
        break;
      } else {
        $existencia_producto = $value['existencia']/$value['cantidad'];
        if ($existencia_mas_baja == 0) {
          $existencia_mas_baja = $existencia_producto;
        } else {
          if ($existencia_producto < $existencia_mas_baja) {
            $existencia_mas_baja = $existencia_producto;
          }
        }
      }
    }
    $existencia = intval($existencia_mas_baja);
    return $existencia;
  }

  public function actualizar() {
    $estatus = 0;

    $id_paquete = $_POST['id_paquete'] ? $_POST['id_paquete'] : 0;
    $producto_nombre = $_POST["producto_nombre"] ? $_POST["producto_nombre"] : null;
    $producto_descripcion = $_POST['descripcion'] ? $_POST['descripcion'] : null;
    $producto_siempre_disponible = $_POST["siempre_disponible"] ? $_POST["siempre_disponible"] : 0;
    $producto_precio_base = $_POST["precio_base"] ? $_POST["precio_base"] : 0;
    $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : null;
    $producto_codigo_unico = $_POST["codigo_unico"] ? $_POST["codigo_unico"] : null;
    $producto_codigo_barras = $_POST["codigo_barras"] ? $_POST["codigo_barras"] : null;
    $producto_cantidad = $_POST["cantidad"] ? $_POST["cantidad"] : 0;
    $productos_paquete = $_POST["productos"] ? $_POST["productos"] : null;

    $identificador = "";

//    $proveedores = $_POST["proveedores"];
//    $categorias = $_POST["categorias"];
    if (($id_paquete && $id_paquete != 0 && $producto_nombre && $producto_descripcion && $producto_precio_base && $producto_precio_base > 0 && $producto_codigo_interno && $producto_codigo_unico)) {
      $estatus = 1;
      $identificador = $this->crear_identificador($producto_nombre);
    }

    $paquete = $this->modelo("Paquete");
    $paquete->setIdentificador($identificador);
    $paquete->setId_paquete($id_paquete);
    $paquete->setNombre($producto_nombre);
    $paquete->setSku($producto_codigo_unico);
    $paquete->setCodigo_interno($producto_codigo_interno);
    $paquete->setDescripcion($producto_descripcion);
    $paquete->setDisponible($producto_siempre_disponible);
    $paquete->setPrecio_base($producto_precio_base);
    $paquete->setEstatus($estatus);
    $existencia = $this->existencia($productos_paquete);
    $paquete->setExistencia($existencia);
    $paquete->setCodigo_barras_base($producto_codigo_barras);

    $respuesta = $paquete->actualizar();
    if ($respuesta['error'] == false) {
      $errores_productos_paquete = array();
//      $id_paquete = $respuesta['depurar'];
      $paquete->setId_paquete($id_paquete);
      $respuesta = $paquete->eliminar_productos_paquete();
      foreach ($productos_paquete as $key => $value) {
        if ($respuesta['error'] == false) {
          $paquete->setId_producto($value['id_producto']);
          $paquete->setId_paquete($id_paquete);
          $paquete->setCantidad_producto_paquete($value['cantidad']);
          $respuesta = $paquete->registrar_producto_paquete();
          if ($respuesta['error'] == true) {
            $errores_productos_paquete[] = $respuesta['mensaje'];
          }
        }
      }

      if (sizeof($errores_productos_paquete) > 0) {
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Error al registrar los productos",
            'depurar' => []
        ];
      } else {
        $respuesta = [
            'error' => false,
            'tipo' => "success",
            'mensaje' => "Paquete registrado con éxito",
            'depurar' => $id_paquete
        ];
      }
    }
//    if (is_countable($proveedores) && sizeof($proveedores) > 0) {
//      $producto->eliminar_proveedores_producto();
//      foreach ($proveedores as $key => $value) {
//        $producto->setId_producto($id_producto);
//        $producto->setId_proveedor($value);
//        $producto->actualizar_proveedores_producto();
//      }
//    }
//    if (is_countable($categorias) && sizeof($categorias) > 0) {
////      var_dump($producto->eliminar_categorias_producto());
//      $producto->eliminar_categorias_producto();
//      foreach ($categorias as $key_c => $value_c) {
//        $producto->setId_producto($id_producto);
//        $producto->setId_categoria($value_c);
//        $producto->actualizar_categorias_producto();
//      }
//    }


    echo json_encode($respuesta);
  }

  public function listar() {
    $producto = $this->modelo("Paquete");

    $respuesta = $producto->listar_paquetes_imagen();
    echo json_encode($respuesta);
  }

  public function consultar_paquete_editar() {
    $id_paquete = $_POST['id_paquete'];

    $paquetes = $this->modelo("Paquete");
    $paquetes->setId_paquete($id_paquete);
    $respuesta = $paquetes->consultar_para_editar();

//    $proveedores = $this->consultar_proveedores_producto($id_paquete);
//    $categorias = $this->consultar_categorias_producto($id_paquete);
    $paquetes->setId_paquete($id_paquete);

    $imagenes = $paquetes->listar_imagenes();

    //productos 
    $productos = $this->modelo("Productos");
    $respuesta_productos = $productos->listar_productos_imagen();

    if ($respuesta_productos['error'] == false) {
      $productos_lista = $respuesta['depurar'];
      $paquetes->setId_paquete($id_paquete);
      //productos paquete
      $respuesta_paquetes = $paquetes->listar_paquete_productos();
//      var_dump(json_encode($respuesta_paquetes));
      if ($respuesta_paquetes['error'] == false) {
        $arrIdsProductosPaquete = array();
        $cantidad_productos_paquetes = array();
        //arreglo productos paquete
        foreach ($respuesta_paquetes['depurar'] as $keyP => $valP) {
          $arrIdsProductosPaquete[] = $valP['id_producto'];
          $cantidad_productos_paquetes[$valP['id_producto']] = $valP['cantidad'];
        }
//        var_dump(json_encode($cantidad_productos_paquetes));
        $productos_lista = $respuesta_productos['depurar'];
        foreach ($productos_lista as $key => $value) {
          if (in_array($value['id_producto'], $arrIdsProductosPaquete)) {
            $productos_lista[$key]['paquete'] = 1;
            $productos_lista[$key]['cantidad'] = $cantidad_productos_paquetes[$value['id_producto']];
          } else {
            $productos_lista[$key]['paquete'] = 0;
          }
        }
      }
    }

//    file_put_contents("productos_paquete.json", json_encode($respuesta_paquetes) . PHP_EOL, FILE_APPEND);
//    file_put_contents("productosp.json", json_encode($respuesta_lp) . PHP_EOL, FILE_APPEND);
//    file_put_contents("productos_lista.json", json_encode($productos_lista) . PHP_EOL, FILE_APPEND);

    $respuesta_info_producto = array(
        "info" => $respuesta,
//        "proveedores" => $proveedores,
//        "categorias" => $categorias,
        "productos" => $productos_lista,
        "imagenes" => $imagenes
    );
    $response = [
        'error' => false,
        'tipo' => "success",
        'mensaje' => "respuesta generada con éxito",
        'depurar' => $respuesta_info_producto
    ];
    echo json_encode($response);
  }

}
