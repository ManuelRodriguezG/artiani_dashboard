<?php

class Producto extends Controlador {

//  public 
    private $productos;

    public function __construct() {
        if (!$_SESSION['id_usuario']) {
            header('Location: /autenticacion/login');
            exit;
        }
    }

    public function crear() {
        $this->requerirPermiso("catalogo.editar");
        $this->vista('apps/ecommerce/catalog/agregar-producto');
//    $this->vista('agregar_producto');
    }

    public function consultar_producto_sku() {
        $sku = isset($_POST['sku']) ? $_POST['sku'] : '';
        $respuesta = array(
            "error" => true,
            "tipo" => "warning",
            "mensaje" => "Producto no encontrado",
            "depurar" => array()
        );
        if ($sku) {
            $producto = $this->modelo("Productos");
            $producto->setSku($sku);
            $consulta = $producto->listar_productos_imagen_sku();
            if ($consulta['error'] == false) {
                $respuesta = array(
                    "error" => false,
                    "tipo" => "success",
                    "mensaje" => "Producto encontrado",
                    "depurar" => $consulta['depurar'][0]
                );
            }
        }
        return json_encode($respuesta);
    }

    public function catalogo_pdf() {
        $categoria = $_GET['categoria'] ? $_GET['categoria'] : null;
        $clasificacion = $_GET['clasificacion'] ? $_GET['clasificacion'] : null;
//	var_dump($categoria);
//	var_dump($clasificacion);
        if (!empty($clasificacion) && $categoria == null) {
            $productos = $this->categorias_clasificacion($clasificacion);
        } else if (!empty($categoria) && !empty($clasificacion)) {
            $productos = $this->productos_por_categoria($categoria, $clasificacion);
        }
//        var_dump(json_encode($productos));
        $this->productos = $this->modelo("Productos");
        $arr_ids = array();
        $arr_ids_variantes = array();
        if ($productos['error'] == false) {
            foreach ($productos['depurar'] as $key => $value) {
                if ($value['tipo_item'] == 'producto') {
                    $arr_ids[] = $value['id_item'];
                    $respuesta = $this->consultar_variantes_producto($value['id_item']);
                    if ($respuesta['error'] == false) {
                        $arr_ids_variantes[] = $respuesta['depurar'];
                    }
                }
            }
        }
//        var_dump(implode(',', $arr_ids));
  //      var_dump(json_encode($arr_ids_variantes));
//        //consultar atributos

        $this->productos->setId_producto(implode(',', $arr_ids));
        $resp_atributos = $this->productos->consultar_atributos();
        $arr_resp = array();
        $arr_resp['atributos'] = array(
            'error' => true
        );
//        var_dump(json_encode($resp_atributos));
        if ($resp_atributos['error'] == false) {
            $atributos_ordenados = $this->ordenar_atributos($resp_atributos['depurar']);
            $resp_atributos['depurar'] = $atributos_ordenados;
            $arr_resp['atributos'] = $resp_atributos;
        }
//        var_dump(json_encode($atributos_ordenados));
        if ($productos['error'] == false) {
            $arr_resp['productos'] = $productos;
        }
        //consultar variantes
        $resp_variantes = array(
            'error' => true
        );
//        var_dump(count($arr_ids_variantes));
        if (count($arr_ids_variantes) > 0) {
            $variantes_ordenadas = $this->ordenar_variantes($arr_ids_variantes);
            $resp_variantes['error'] = false;
            $resp_variantes['depurar'] = $variantes_ordenadas;
        }

        $arr_resp['variantes'] = $resp_variantes;
	//var_dump($arr_resp);
//        $producto->setId_producto(implode(',', $arr_ids));
//        $resp_atributos = $producto->consultar_ids_variantes();
//        $productos = $this->consultar_productos_catalogo();
        $this->vista('apps/ecommerce/catalog/catalogo_pdf', $arr_resp);
    }

    private function ordenar_variantes($atributos) {
//        var_dump($atributos);
        $arr_variantes = array();
        $arr_ids_productos = array();
        $arr_ids_principales = array();
        $arr_productos_variantes = array();

        $producto_principal = 0;
        foreach ($atributos as $key => $value) {
//            var_dump(!in_array($value['tipo_atributo'], $arr_tipos_atributos));
//            var_dump("<br>");
//            var_dump($value);
//            var_dump("<br>");
            foreach ($value as $keyv => $valuev) {

                if ($valuev['principal'] == 1) {
                    $producto_principal = $valuev['id_producto'];
                    $arr_ids_principales[] = $producto_principal;
                } else {
//                    var_dump("<br>");
//                    var_dump($valuev['id_producto']);
//                    var_dump("<br>");
                    $arr_productos_variantes[$valuev['id_producto']] = array(
                        'url_imagen' => $valuev['url_imagen']
                    );
                }
            }
            $arr_variantes[$producto_principal] = $arr_productos_variantes;
            $arr_productos_variantes = array();
            $producto_principal = 0;
        }
//        var_dump(json_encode($arr_variantes));
        $respuesta = array('variantes' => $arr_variantes, 'ids_principales' => $arr_ids_principales);
//        var_dump(json_encode($arr_atributos));
        return $respuesta;
    }

    private function ordenar_atributos($atributos) {
//        var_dump($atributos);
        $arr_atributos = array();
        $arr_tipos_atributos = array();
        $arr_ids_atributos = array();
        foreach ($atributos as $key => $value) {
//            var_dump(!in_array($value['tipo_atributo'], $arr_tipos_atributos));
            if (!in_array($value['tipo_atributo'], $arr_tipos_atributos)) {
                if (!in_array($value['id_producto'], $arr_ids_atributos)) {
                    $arr_ids_atributos[] = $value['id_producto'];
                }
                $arr_tipos_atributos[] = $value['tipo_atributo'];
                $arr_atributos[$value['id_producto']][$value['tipo_atributo']][] = array(
                    'valor_atributo' => $value['valor_atributo'],
                    'unidad_medida_atributo' => $value['unidad_medida_atributo'],
                    'descripcion_atributo' => $value['descripcion_atributo']
                );
            } else {
                if (!in_array($value['id_producto'], $arr_ids_atributos)) {
                    $arr_ids_atributos[] = $value['id_producto'];
                }
                $arr_atributos[$value['id_producto']][$value['tipo_atributo']][] = array(
                    'valor_atributo' => $value['valor_atributo'],
                    'unidad_medida_atributo' => $value['unidad_medida_atributo'],
                    'descripcion_atributo' => $value['descripcion_atributo']
                );
            }
        }
        $respuesta = array(
            'atributos' => $arr_atributos,
            'ids' => $arr_ids_atributos
        );
//        var_dump(json_encode($arr_atributos));
        return $respuesta;
    }

    public function consultar_productos_catalogo() {

        $producto = $this->modelo("Productos");

        $respuesta = $producto->listar_productos_imagen();
        return ($respuesta);
    }

    public function productos_por_categoria($categoria, $clasificacion) {
        $identificador_categoria = $categoria;
        $identificador_clasificacion = $clasificacion;
        $categoria = $this->modelo("Categorias");

        $categoria->setIdentificador($identificador_clasificacion);
        $categoria->setIdentificador_categoria($identificador_categoria);
        $respuesta = $categoria->consultar_productos_categoria();
//      var_dump("<br>");
        if ($respuesta['error'] == false) {
            $productos = $respuesta['depurar'];
            ////
            //consultar productos generales 
            $categoria->setIdentificador($identificador_clasificacion);
            $respuesta = $categoria->consultar_productos_categoria_generales();
//        var_dump($respuesta);
//        var_dump("<br>");
            if ($respuesta['error'] == false) {
                $productos_generales = $respuesta['depurar'];
                $ids_productos = array();
                foreach ($productos_generales as $key => $value) {
                    $ids_productos[] = $value['id_item'];
                }
                $paquetes = $this->modelo("Paquetes");
                $string_ids = implode(",", $ids_productos);
                $paquetes->setString_ids($string_ids);
                $paquetes->setIdentificador($identificador_categoria);
                $paquetes->setIdentificador_clasificacion($identificador_clasificacion);
                $respuesta = $paquetes->paquetes_por_ids_productos_identificador_categoria();
//          var_dump($respuesta);
//          var_dump("<br>");
                if ($respuesta['error'] == false) {
                    $productos_array = array();
                    $productos_array = array_merge($respuesta['depurar'], $productos);
                    $nuevos_productos = array();
                    foreach ($productos_array as $key => $value) {
                        if ($value['identificador_categoria'] == $identificador_categoria) {
                            $categorias[] = $value['categoria'];
                            $nuevos_productos[] = $value;
                        }
                    }
                    array_multisort($categorias, SORT_ASC, $nuevos_productos);
//            var_dump($respuesta);
//            var_dump("<br>");
                    $respuesta = [
                        'error' => false,
                        'tipo' => "success",
                        'mensaje' => "Productos encontrados con éxito",
                        'depurar' => $nuevos_productos
                    ];
                } else {
                    $respuesta = [
                        'error' => false,
                        'tipo' => "success",
                        'mensaje' => "Productos encontrados con éxito",
                        'depurar' => $productos
                    ];
                }
            } else {
                $respuesta = [
                    'error' => false,
                    'tipo' => "success",
                    'mensaje' => "Productos encontrados con éxito",
                    'depurar' => $productos
                ];
            }
            /////
        } else {
            //consultar productos generales 
            $categoria->setIdentificador($identificador_clasificacion);
            $respuesta = $categoria->consultar_productos_categoria_generales();
            if ($respuesta['error'] == false) {
                $productos = $respuesta['depurar'];
                $ids_productos = array();
                foreach ($productos as $key => $value) {
                    $ids_productos[] = $value['id_item'];
                }
                $paquetes = $this->modelo("Paquetes");
                $string_ids = implode(",", $ids_productos);
                $paquetes->setString_ids($string_ids);
                $paquetes->setIdentificador($identificador_categoria);
                $paquetes->setIdentificador_clasificacion($identificador_clasificacion);
                $respuesta = $paquetes->paquetes_por_ids_productos_identificador_categoria();
                if ($respuesta['error'] == false) {
                    $productos_array = array();
                    $productos_array = array_merge($respuesta['depurar'], $productos);
                    $nuevos_productos = array();
                    foreach ($productos_array as $key => $value) {
                        if ($value['identificador_categoria'] == $identificador_categoria) {
                            $categorias[] = $value['categoria'];
                            $nuevos_productos[] = $value;
                        }
                    }
                    array_multisort($categorias, SORT_ASC, $nuevos_productos);
                    $respuesta = [
                        'error' => false,
                        'tipo' => "success",
                        'mensaje' => "Productos encontrados con éxito",
                        'depurar' => $nuevos_productos
                    ];
                } else {
                    $respuesta = [
                        'error' => false,
                        'tipo' => "success",
                        'mensaje' => "Productos encontrados con éxito",
                        'depurar' => $productos
                    ];
                }
            } else {
                $respuesta = [
                    'error' => true,
                    'tipo' => "warning",
                    'mensaje' => "No se encontraron productos para esta categoria",
                    'depurar' => []
                ];
            }
        }
//      var_dump($array_productos_categorias);
//    var_dump($respuesta);

        return ($respuesta);
    }

    public function categorias_clasificacion($clasificacion) {
        $identificador_clasificacion = $clasificacion;
        $clasificacion = $this->modelo("Clasificaciones");
        $clasificacion->setIdentificador_clasificacion($identificador_clasificacion);
        $resp = $clasificacion->consultar_productos_por_clasificacion();
//        var_dump($resp);
        if ($resp['error'] == false) {
            $productos_generales = $resp['depurar'];

            $resp = [
                'error' => false,
                'tipo' => "success",
                'mensaje' => "Categorias encontradas con éxito",
                'depurar' => $productos_generales
            ];
        } else {
            $resp = [
                'error' => true,
                'tipo' => "warning",
                'mensaje' => "Sin categorias",
                'depurar' => []
            ];
        }
        return ($resp);
    }

    public function registrar() {
//    header('Content-Type: application/json');
//    var_dump($_POST);
        $estatus = 0;
        $producto_nombre = $_POST["producto_nombre"];
        $producto_descripcion = $_POST['descripcion'];
        $producto_siempre_disponible = $_POST["siempre_disponible"];
        $producto_precio_base = $_POST["precio_base"];
        $producto_codigo_unico = $_POST["codigo_unico"];
        $producto_codigo_barras = $_POST["codigo_barras"];
        $producto_existencia = $_POST["existencia"];
        $producto_minima_existencia = $_POST["minima_existencia"];
        $producto_maxima_existencia = $_POST["maxima_existencia"];
        $proveedores = $_POST["proveedores"];
        $categorias = $_POST["categorias"];
        if ($producto_nombre && $producto_descripcion && $producto_precio_base && $producto_precio_base > 0 && $producto_codigo_unico) {
            $estatus = 1;
            $identificador = $this->crear_identificador($producto_nombre);
        }
//    $producto = $this->modelo("Productos");
//    var_dump($producto);
        $producto = $this->modelo("Productos");
        $producto->setIdentificador($identificador);
        $producto->setNombre($producto_nombre);
        $producto->setSku($producto_codigo_unico);
        $producto->setDescripcion($producto_descripcion);
//    var_dump($producto->getNombre());
        $producto->setDisponible($producto_siempre_disponible);
        $producto->setPrecio_base($producto_precio_base);
        $producto->setEstatus($estatus);
        $producto->setExistencia($producto_existencia);
        $producto->setMaxima_existencia($producto_maxima_existencia);
        $producto->setMinima_existencia($producto_minima_existencia);
        $producto->setCodigo_barras_base($producto_codigo_barras);

        $respuesta = $producto->registrar();
        if ($respuesta['error'] == false) {
            $id_producto = $respuesta['depurar'];
            if (is_countable($proveedores) && sizeof($proveedores) > 0) {
                $producto->eliminar_proveedores_producto();
                foreach ($proveedores as $key => $value) {
                    $producto->setId_producto($id_producto);
                    $producto->setId_proveedor($value);
                    $producto->actualizar_proveedores_producto();
                }
            }

            if (is_countable($categorias) && sizeof($categorias) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
                $producto->eliminar_categorias_producto();
                foreach ($categorias as $key_c => $value_c) {
                    $producto->setId_producto($id_producto);
                    $producto->setId_categoria($value_c);
                    $producto->actualizar_categorias_producto();
                }
            }

            //compra venta
            $unidad_compra = $_POST['compra_venta']["unidad_compra"];
            $unidad_venta = $_POST['compra_venta']["unidad_venta"];
            $solo_en_punto_de_venta = $_POST['compra_venta']["solo_en_punto_de_venta"];
            $factor = $_POST['compra_venta']["factor"];

            $producto->setId_producto($id_producto);
            $producto->setId_unidad_compra($unidad_compra);
            $producto->setId_unidad_venta($unidad_venta);
            $producto->setSolo_en_punto_de_venta($solo_en_punto_de_venta);
            $producto->setFactor($factor);

            $resp_compra_venta = $producto->registrar_compra_venta();
        }
        echo json_encode($respuesta);
    }

    public function crear_identificador($cadena) {

        //Reemplazamos espacios por -
        $cadena = str_replace(
                array(' ', "/"),
                array('-', ""),
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

    public function editar() {
        $this->vista('apps/ecommerce/catalog/editar-producto');
    }

    public function actualizar_portada() {
//    var_dump($_FILES);

        $id_producto = $_POST['id_producto'];
        $tipo_imagen = $_POST['tipo_imagen'];

        $producto = $this->modelo("Productos");
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
            $producto->setId_producto($id_producto);
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

    public function eliminar_complementarias() {
        $id_imagen = $_POST['id_imagen'];
        $ruta_imagen = $_POST['ruta_imagen'];
        $producto = $this->modelo("Productos");
        $producto->setId_imagen($id_imagen);
        $response = $producto->eliminar_imagen_complementaria();
        if ($response['error'] == false) {
            unlink($ruta_imagen);
        }
        echo json_encode($response);
    }

    public function imagenes_complementarias() {


        $id_producto = $_POST['id_producto'];
        $tipo_imagen = $_POST['tipo_imagen'];

        $producto = $this->modelo("Productos");
//    var_dump($_FILES['portada']);
        $producto->setId_producto($_POST['id_producto']);
        $producto->setUrl_origen($_FILES['file']['tmp_name']);
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $fecha = new DateTime();
        $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
        //guardar archivo
        $response = $producto->guardar_imagenes();

        if ($response['error'] == false) {
            $url_imagen = $response['depurar'];
            //insertar
            $producto->setUrl_imagen($url_imagen);
            $producto->setTipo_imagen($tipo_imagen);
            $response = $producto->registrar_imagen();
            $response = [
                'error' => false,
                'tipo' => "success",
                'mensaje' => "respuesta generada con éxito",
                'depurar' => array(
                    'id_registro' => $response['depurar'],
                    'url_imagen' => $url_imagen
                )
            ];
        }
        echo json_encode($response);
    }

    public function actualizar() {
        $estatus = 0;

        $id_producto = $_POST['id_producto'] ? $_POST['id_producto'] : 0;
        $producto_nombre = $_POST["producto_nombre"] ? $_POST["producto_nombre"] : null;
        $producto_descripcion = $_POST['descripcion'] ? $_POST['descripcion'] : null;
        $producto_siempre_disponible = $_POST["siempre_disponible"] ? $_POST["siempre_disponible"] : 0;
        $producto_precio_base = $_POST["precio_base"] ? $_POST["precio_base"] : 0;
        $producto_precio_oferta = $_POST["precio_oferta"] ? $_POST["precio_oferta"] : 0;
        $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : null;
        $producto_codigo_unico = $_POST["codigo_unico"] ? $_POST["codigo_unico"] : null;
        $producto_codigo_barras = $_POST["codigo_barras"] ? $_POST["codigo_barras"] : null;
        $producto_cantidad = $_POST["cantidad"] ? $_POST["cantidad"] : 0;
        $producto_minima_existencia = $_POST["minima_existencia"];
        $producto_maxima_existencia = $_POST["maxima_existencia"];

        $identificador = "";

        $proveedores = $_POST["proveedores"];
        $categorias = $_POST["categorias"];
        $marcas = $_POST["marcas"];
        $tags = $_POST["tags"];
        $etiquetas = $_POST["etiquetas"];
        $atributos = $_POST["atributos"];
        $variantes = $_POST["variantes"];
        $sugeridos = $_POST["sugeridos"];
        $complementarios = $_POST["complementarios"];
        $id_variante = $_POST["id_variante"] ? $_POST["id_variante"] : 0;

        $productos_sugeridos = $_POST["productos_sugeridos"] ? $_POST["productos_sugeridos"] : null;

        if (($id_producto && $id_producto != 0 && $producto_nombre && $producto_descripcion && $producto_precio_base && $producto_precio_base > 0 && $producto_codigo_interno && $producto_codigo_unico)) {
            $estatus = 1;
            $identificador = $this->crear_identificador($producto_nombre);
        }

        $producto = $this->modelo("Productos");
        $producto->setIdentificador($identificador);
        $producto->setId_producto($id_producto);
        $producto->setNombre($producto_nombre);
        $producto->setSku($producto_codigo_unico);
        $producto->setCodigo_interno($producto_codigo_interno);
        $producto->setDescripcion($producto_descripcion);
        $producto->setDisponible($producto_siempre_disponible);
        $producto->setPrecio_base($producto_precio_base);
        $producto->setPrecio_oferta($producto_precio_oferta);
        $producto->setEstatus($estatus);
        $producto->setExistencia($producto_cantidad);
        $producto->setMaxima_existencia($producto_maxima_existencia);
        $producto->setMinima_existencia($producto_minima_existencia);
        $producto->setCodigo_barras_base($producto_codigo_barras);

        $respuesta = $producto->actualizar();
        if (is_countable($proveedores) && sizeof($proveedores) > 0) {
            $producto->eliminar_proveedores_producto();
            foreach ($proveedores as $key => $value) {
                $producto->setId_producto($id_producto);
                $producto->setId_proveedor($value);
                $producto->actualizar_proveedores_producto();
            }
        }
        //Productos sugeridos
        if (is_countable($productos_sugeridos) && sizeof($productos_sugeridos) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_sugeridos_producto();
            foreach ($productos_sugeridos as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_sugerido($value_c['id_producto']);
                $producto->actualizar_sugeridos_producto();
            }
        }

        //Categorias
        if (is_countable($categorias) && sizeof($categorias) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_categorias_producto();
            foreach ($categorias as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_categoria($value_c);
                $producto->actualizar_categorias_producto();
            }
        }
        //Marcas
        if (is_countable($marcas) && sizeof($marcas) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_marcas_producto();
            foreach ($marcas as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_marca($value_c);
                $producto->actualizar_marcas_producto();
            }
        }
        //Atributos
//        var_dump($atributos);
        if (is_countable($atributos) && sizeof($atributos) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_atributos_producto();
            foreach ($atributos as $key_c => $value_c) {
                $tipo_atributo = $value_c['tipo_atributo'];
                $valor_atributo = $value_c['valor_atributo'];
                $unidad_medida = $value_c['unidad_medida'];
                $descripcion = $value_c['descripcion'];

                $producto->setTipo_atributo($tipo_atributo);
                $producto->setValor_atributo($valor_atributo);
                $producto->setUnidad_medida_atributo($unidad_medida);
                $producto->setDescripcion_atributo($descripcion);
                $respuesta = $producto->buscar_existencia_atributo();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    //atributo encontrado
                    //registrar producto atributo
//                    var_dump($respuesta);
                    $id_producto_atributo = $respuesta['depurar']['id_atributo'];
                    $producto->setId_atributo($id_producto_atributo);
                    $respuesta = $producto->registrar_atributo_producto();
//                    var_dump($respuesta);
                } else {
                    //atributo no encontrado
                    //registrar atributo
                    $producto->setTipo_atributo($tipo_atributo);
                    $producto->setValor_atributo($valor_atributo);
                    $producto->setUnidad_medida_atributo($unidad_medida);
                    $producto->setDescripcion_atributo($descripcion);
                    $respuesta = $producto->registrar_atributo();
                    $id_atributo = $respuesta['depurar'];
//                    var_dump($respuesta);
                    //registrar producto atributo
                    $producto->setId_atributo($id_atributo);
                    $respuesta = $producto->registrar_atributo_producto();
//                    var_dump($respuesta);
                }
            }
        } else {
            $producto->eliminar_atributos_producto();
        }
        //Variantes
        if (is_countable($variantes) && sizeof($variantes) > 0) {

            //verificar si ya existe alguna variante 
            $producto->setId_variante($id_variante);
            $respuesta = $producto->consultar_variantes();
            if ($respuesta['error'] == false) {
                $respuesta_variantes = $respuesta['depurar'];
                //existen variantes
                //regresar productos variantes a tipo 'producto'
                foreach ($respuesta_variantes as $key_rv => $value_rv) {
                    $id_producto = $value_rv['id_producto'];
                    $producto->setId_producto($id_producto);
                    $producto->setTipo_producto('producto');
                    $producto->actualizar_tipo_producto();
                }
                $producto->setId_variante($id_variante);
                $respuesta = $producto->eliminar_variantes_producto();
                if ($respuesta['error'] == false) {
                    //registrar variantes con id variante existente
                    foreach ($variantes as $key_v => $value_v) {
                        $id_producto = $value_v['id_variante'];
                        $principal = $value_v['principal'];
                        $producto->setId_variante($id_variante);
                        $producto->setId_producto($id_producto);
                        $producto->setPrincipal($principal);
                        //registrar variantes
                        $respuesta = $producto->registrar_variante_producto();
//                var_dump($respuesta);
                        if ($respuesta['error'] == false && $principal == 0) {
                            //actualizar tipo producto 'variante'
                            $producto->setId_producto($id_producto);
                            $producto->setTipo_producto('variante');
                            $producto->actualizar_tipo_producto();
                        } else if ($respuesta['error'] == false && $principal == 1) {
                            //actualizar tipo producto 'producto'
                            $producto->setId_producto($id_producto);
                            $producto->setTipo_producto('producto');
                            $producto->actualizar_tipo_producto();
                        }
                    }
                }
            } else {
                //no existen variantes
                //registrar variante
                $producto->setPrincipal("0");
                $respuesta = $producto->registrar_variante();
                if ($respuesta['error'] == false) {
                    $id_variante = $respuesta['depurar'];
                    foreach ($variantes as $key_v => $value_v) {
                        $id_producto = $value_v['id_variante'];
                        $principal = $value_v['principal'];
                        $producto->setId_variante($id_variante);
                        $producto->setId_producto($id_producto);
                        $producto->setPrincipal($principal);
                        //registrar variantes
                        $respuesta = $producto->registrar_variante_producto();
//                var_dump($respuesta);
                        if ($respuesta['error'] == false && $principal == 0) {
                            //actualizar tipo producto 'variante'
                            $producto->setId_producto($id_producto);
                            $producto->setTipo_producto('variante');
                            $producto->actualizar_tipo_producto();
                        } else if ($respuesta['error'] == false && $principal == 1) {
                            //actualizar tipo producto 'producto'
                            $producto->setId_producto($id_producto);
                            $producto->setTipo_producto('producto');
                            $producto->actualizar_tipo_producto();
                        }
                    }
                }
            }
            //guardar ids variantes si habia registrados
            //eliminar variantes productos
            //regresar a la normalidad los productos variantes anteriores
            //registrar variantes productos nuevas
            //actualizar tipos de productos
//      var_dump($producto->eliminar_categorias_producto());
        } else {
            
        }
        //sugeridos
        if (is_countable($sugeridos) && sizeof($sugeridos) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_sugeridos();
            foreach ($sugeridos as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_sugerido($value_c['sugerido']);
                $respuesta = $producto->registrar_sugerido();
            }
        } else {
            $producto->eliminar_sugeridos();
        }
        //complementarios
        if (is_countable($complementarios) && sizeof($complementarios) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_complementarios();
            foreach ($complementarios as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_complementario($value_c['complemento']);
                $respuesta = $producto->registrar_complementario();
            }
        } else {
            $producto->eliminar_complementarios();
        }

        //Etiquetas
        if (is_countable($etiquetas) && sizeof($etiquetas) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_etiquetas_productos();
            foreach ($etiquetas as $key_c => $value_c) {
                $producto->setTag($value_c['etiqueta']);
                $respuesta = $producto->buscar_existencia_etiqueta();
                if ($respuesta['error'] == false) {
                    //tag encontrado
                    //registrar producto tag
//                    var_dump($respuesta);
                    $id_tag = $respuesta['depurar']['id_etiqueta'];
                    $producto->setId_tag($id_tag['etiqueta']);
                    $producto->setTipo_etiqueta($id_tag['tipo_etiqueta']);
                    $producto->setId_producto($id_producto);
                    $respuesta = $producto->registrar_etiqueta_producto();
                } else {
                    //tag no encontrado
                    //registrar tag
                    $producto->setTag($value_c['etiqueta']);

                    $respuesta = $producto->registrar_etiqueta();
                    $id_tag = $respuesta['depurar'];
//                    var_dump($respuesta);
                    //registrar producto atributo
                    $producto->setId_producto($id_producto);
                    $producto->setId_tag($id_tag);
                    $producto->setTipo_etiqueta($value_c['tipo_etiqueta']);
                    $respuesta = $producto->registrar_etiqueta_producto();
                }
            }
        } else {
            $producto->eliminar_etiquetas_productos();
        }

        //Tags
        if (is_countable($tags) && sizeof($tags) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_tags_producto();
            foreach ($tags as $key_c => $value_c) {

                $producto->setTag($value_c);
                $respuesta = $producto->buscar_existencia_tag();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    //tag encontrado
                    //registrar producto tag
//                    var_dump($respuesta);
                    $id_tag = $respuesta['depurar']['id_tag'];
                    $producto->setId_tag($id_tag);
                    $producto->registrar_tag_producto();
                } else {
                    //tag no encontrado
                    //registrar tag
                    $producto->setTag($value_c);

                    $respuesta = $producto->registrar_tag();
                    $id_tag = $respuesta['depurar'];
//                    var_dump($respuesta);
                    //registrar producto atributo
                    $producto->setId_tag($id_tag);
                    $producto->registrar_tag_producto();
                }
            }
        } else {
            $producto->eliminar_tags_producto();
        }
        //compra venta
        $unidad_compra = $_POST['compra_venta']["unidad_compra"];
        $unidad_venta = $_POST['compra_venta']["unidad_venta"];
        $solo_en_punto_de_venta = $_POST['compra_venta']["solo_en_punto_de_venta"];
        $factor = $_POST['compra_venta']["factor"];

        $producto->setId_producto($id_producto);
        $producto->setId_unidad_compra($unidad_compra);
        $producto->setId_unidad_venta($unidad_venta);
        $producto->setSolo_en_punto_de_venta($solo_en_punto_de_venta);
        $producto->setFactor($factor);
        $resp_consulta_compra_venta = $producto->consultar_compra_venta();
        if ($resp_consulta_compra_venta['error'] == false) {
            $producto->actualizar_compra_venta();
        } else {
            $producto->registrar_compra_venta();
        }




        echo json_encode($respuesta);
    }

    public function mostrar() {
        
    }

    public function consultar_proveedores_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_proveedores_producto();
        return $respuesta;
    }

    public function consultar_categorias_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_categorias_producto();
        return $respuesta;
    }

    public function consultar_sugeridos_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_sugeridos_producto();
        return $respuesta;
    }

    public function consultar_marcas_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_marcas_producto();
        return $respuesta;
    }

    public function consultar_atributos_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_atributos_producto();
        return $respuesta;
    }

    public function consultar_tags_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_tags_producto();
        return $respuesta;
    }

    public function consultar_variantes_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        //consultar id variante 
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->obtener_id_variante();
        if ($respuesta['error'] == false) {
            $id_variante = $respuesta['depurar'][0]['id_variante'];
            $this->productos->setId_variante($id_variante);
            $respuesta = $this->productos->listar_variantes_producto();
        }
        return $respuesta;
    }

    public function consultar_complementos_producto($id_producto) {

        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_complementos_producto();
        return $respuesta;
    }

    public function consultar_lista_sugeridos_producto($id_producto) {
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->lista_sugeridos_producto();
        return $respuesta;
    }

    public function consultar_producto_editar() {
        $id_producto = $_POST['id_producto'];

        $this->productos = $this->modelo("Productos");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->consultar_para_editar();

        $proveedores = $this->consultar_proveedores_producto($id_producto);
        $categorias = $this->consultar_categorias_producto($id_producto);
        $marcas = $this->consultar_marcas_producto($id_producto);
        $atributos = $this->consultar_atributos_producto($id_producto);
        $tags = $this->consultar_tags_producto($id_producto);
        $variantes = $this->consultar_variantes_producto($id_producto);
        $complementos = $this->consultar_complementos_producto($id_producto);
        $sugeridos = $this->consultar_lista_sugeridos_producto($id_producto);
        $this->productos->setId_producto($id_producto);

        $compra_venta = $this->productos->consultar_compra_venta();

        $imagenes = $this->productos->listar_imagenes();

        $respuesta_info_producto = array(
            "info" => $respuesta,
            "proveedores" => $proveedores,
            "compra_venta" => $compra_venta,
            "categorias" => $categorias,
            "sugeridos" => $sugeridos,
            "complementos" => $complementos,
            "marcas" => $marcas,
            "atributos" => $atributos,
            "tags" => $tags,
            "variantes" => $variantes,
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

    public function consultar() {

        $producto = $this->modelo("Productos");

        $respuesta = $producto->consultar();
        echo json_encode($respuesta);
    }

    public function listar() {
        $producto = $this->modelo("Productos");

        $respuesta = $producto->listar_productos_imagen();
        echo json_encode($respuesta);
    }

    public function catalogo() {
        $this->requerirPermiso("catalogo.ver");
        $this->vista('apps/ecommerce/catalog/productos');
    }

    public function index() {
        $this->vista('apps/ecommerce/catalog/productos');
    }
}

?>
