<?php

include_once "../app/helpers/PHPExcel-1.8/Classes/PHPExcel.php";

class Utilidad extends Controlador {

    public function __construct() {
        if (!$_SESSION['id_usuario']) {
            header('Location: /autenticacion/login');
            exit;
        }
    }

    public function listas_mayoreo_mostrar() {
        $this->vista("apps/erp/proveedores/listas_mayoreo_mostrar");
    }

    public function usuarios_mayoreo_mostrar() {
        $this->vista("apps/erp/usuarios_mayoreo/usuarios_mayoreo_mostrar");
    }

    public function usuario_mayoreo_listas() {
        $this->vista("apps/erp/usuarios_mayoreo/listas_usuario_mayoreo");
    }

    public function listas_mostrar() {
        $this->vista("apps/erp/proveedores/listas_mostrar");
    }

    public function lista_producto_editar() {
        $this->vista("apps/erp/proveedores/lista_producto_editar");
    }

    public function lista_productos() {
        $this->vista("apps/erp/proveedores/lista_productos");
    }

    public function mostrar_lista_productos() {
        $this->vista("apps/erp/proveedores/listas_mostrar");
    }

    public function editar_pedido() {
        $this->vista('apps/erp/proveedores/pedidos/editar');
    }

    public function nuevo_grupo() {
        $this->vista('apps/erp/utilidad/nuevo_grupo');
    }

    public function mostrar_grupos() {
        $this->vista('apps/erp/utilidad/mostrar_grupos');
    }

    public function utilidad_productos() {
        $this->vista("apps/erp/utilidad/utilidad_productos");
    }

    public function consultar_grupo_productos_utilidad() {
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Utilidades");

        $proveedor->setId_proveedor_pedido($id_lista);
        $response = $proveedor->consultar_productos_grupo_utilidad();

        echo json_encode($response);
    }
    

    public function registrar() {
//    header('Content-Type: application/json');
//    var_dump($_POST);
        $estatus = 0;
        $proveedor_nombre = $_POST["proveedor_nombre"];
        $cuota = $_POST['cuota'];

        $producto = $this->modelo("Proveedores");
        $producto->setProveedor($proveedor_nombre);
        $producto->setCuota($cuota);

        $respuesta = $producto->registrar();

        echo json_encode($respuesta);
    }

    public function actualizar_estatus_lista_mayoreo() {
//        var_dump($_POST);
        $id_lista_mayoreo = $_POST['id_lista_mayoreo'];
        $estatus = $_POST['estatus'];

        $proveedor = $this->modelo("Proveedores");
        $proveedor->setId_lista_mayoreo($id_lista_mayoreo);
        $proveedor->setEstatus($estatus);

        $respuesta = $proveedor->actualizar_estatus_lista_mayoreo();
        echo json_encode($respuesta);
    }

    public function actualizar_estatus_lista_mayoreo_usuario_mayoreo() {
//        var_dump($_POST);
        $id_lista_mayoreo = $_POST['id_lista_mayoreo'];
        $accion = $_POST['accion'];
        $id_usuario = $_POST['id_usuario'];
        $proveedor = $this->modelo("Proveedores");
        $proveedor->setId_lista_mayoreo($id_lista_mayoreo);
        $proveedor->setId_usuario_mayoreo($id_usuario);
        if ($accion == "asignar") {
            $respuesta = $proveedor->asignar_lista_mayoreo_usuario_mayoreo();
        } else if ($accion == "quitar_asignacion") {
            $respuesta = $proveedor->quitar_asignacion_lista_mayoreo_usuario_mayoreo();
        }



        echo json_encode($respuesta);
    }

    public function actualizar_pedido() {

        $id_proveedor = $_POST['id_proveedor'];
        $proveedor = $_POST['proveedor'];
        $id_lista_proveedor = $_POST['id_lista_proveedor'];
        $nombre_inventario = $_POST['nombre_inventario'];
        $comentario = $_POST['comentario'];
        $elementos = $_POST['productos'];
        $total = $_POST['total'];
        $id_pedido = $_POST['id'];

        $inventario = $this->modelo("Proveedores");
        $inventario->setId_proveedor_pedido($id_pedido);

        $inventario->setProveedor($proveedor);
        $inventario->setId_proveedor($id_proveedor);
        $inventario->setId_lista_proveedor($id_lista_proveedor);
        $inventario->setComentario($comentario);
        $inventario->setEstatus(1);
        $inventario->setTitulo($nombre_inventario);
        $inventario->setTotal($total);

        $respuesta = $inventario->actualizar_pedido();
//    $id_pedido = $respuesta['depurar'];
//        var_dump($elementos);
        if (sizeof($elementos) > 0) {
            $inventario->setId_proveedor_pedido($id_pedido);
            $respuesta_eliminar_productos = $inventario->eliminar_elementos_pedido();
//            var_dump($respuesta_eliminar_productos);
            if ($respuesta_eliminar_productos['error'] == false) {
                foreach ($elementos as $key => $values) {
                    $id_elemento = $values['id_producto'];
                    $cantidad = $values['cantidad'];
//          $precio = $values['precio'];
//          $importe = $cantidad * $precio;
//          $portada = $values['portada'];
//          $nombre = $values['producto'];
//                    $tipo = $values['tipo_item'];
                    //pedido
                    $inventario->setId_proveedor_pedido($id_pedido);
                    $inventario->setCantidad($cantidad);
                    $inventario->setId_elemento($id_elemento);
                    $respuesta = $inventario->registrar_elementos_pedido();
                    if ($respuesta['error'] == true) {
                        $errores_productos_pedido[] = $respuesta['error'];
                    }
                }
            }
        }

        //productos pedido
        return json_encode($respuesta);
    }

    public function consulta_completa_pedido() {
        $id_pedido = $_POST['id_pedido'] && $_POST['id_pedido'] != null ? $_POST['id_pedido'] : 0;
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Datos incorrectos",
            'depurar' => []
        ];
        //
        if ($id_pedido != 0) {
            $pedido = array();
            //pedido
            $inventario = $this->modelo("Proveedores");
            $inventario->setId_proveedor_pedido($id_pedido);
            $respuesta = $inventario->consultar_pedido();
//            var_dump($respuesta);
            if ($respuesta['error'] == false) {
                //consultar productos inventario
                $info_inventario = $respuesta['depurar'];
                $id_lista_proveedor = $respuesta['depurar']['id_lista_proveedor'];
//                var_dump($id_lista_proveedor);
                $pedido['pedido'] = $info_inventario;
                //productos inventario
                $ids_productos_pedido = array();
                $arreglo_productos_por_id = array();
                $ids_tipo_producto = array();
                $inventario->setId_proveedor_pedido($id_pedido);
                $respuesta = $inventario->consultar_elementos_inventario();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $productos_pedido = $respuesta['depurar'];
                    foreach ($productos_pedido as $key => $value) {
                        if (!in_array($value['id_elemento'], $arreglo_productos_por_id)) {
                            $ids_tipo_producto[$value['id_elemento']] = $value['tipo'];
                            $ids_productos_pedido[] = $value['id_elemento'];
                            $arreglo_productos_por_id[$value['id_elemento']] = $value;
                        }
                    }
//          $respuesta = $this->consultar_productos_para_editar($productos);
//          if ($respuesta['error'] == false) {
//            $productos = $respuesta['depurar'];
                    $pedido['arr_ids'] = $ids_productos_pedido;
                    $pedido['productos_pedido'] = $productos_pedido;
//          } else {
//            
//          }
                }

                //productos 
                $productos_resp = array();
//                $productos = $this->modelo("Productos");
                $inventario->setId_lista_proveedor($id_lista_proveedor);
                $respuesta = $inventario->listar_productos_proveedor();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $productos_resp = $respuesta['depurar'];
                    $pedido['productos'] = $productos_resp;
                }

                foreach ($productos_resp as $key => $value) {
//            var_dump($productos_resp[$key]);
//            var_dump($productos_resp[$key]['tipo_item'] == "producto" && in_array($productos_resp[$key]['id_producto'], $ids_productos_pedido));
//            var_dump($arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['id_tipo_elemento']);
//                    var_dump($ids_productos_pedido);
//                    var_dump($productos_resp[$key]['id_producto']);
                    if (in_array($productos_resp[$key]['id_producto'], $ids_productos_pedido)) {
//                  var_dump($productos_resp[$key]);
                        $productos_resp[$key]['cantidad'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['cantidad'];
                        $productos_resp[$key]['descuento'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['descuento'];
                        $productos_resp[$key]['id_pedido'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['id_elemento'];
                        $productos_resp[$key]['precio'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['precio'];
                        $productos_resp[$key]['subtotal'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['subtotal'];
                    }
                }
                $pedido['productos'] = $productos_resp;
//var_dump($productos_resp);
                $respuesta = [
                    'error' => false,
                    'tipo' => "success",
                    'mensaje' => "Pedido encontrado",
                    'depurar' => $pedido
                ];
            } else {
                $respuesta = [
                    'error' => true,
                    'tipo' => "danger",
                    'mensaje' => "Pedido no encontrado",
                    'depurar' => []
                ];
            }
        } else {
            $respuesta = [
                'error' => true,
                'tipo' => "danger",
                'mensaje' => "Pedido inválido",
                'depurar' => []
            ];
        }
        return json_encode($respuesta);
    }

    public function consultar_producto_editar() {
        $id_producto = $_POST['id_producto'];

        $this->productos = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->consultar_para_editar();

        $categorias = $this->consultar_categorias_producto($id_producto);
        $this->productos->setId_producto($id_producto);

        $imagenes = $this->productos->listar_imagenes();

        $respuesta_info_producto = array(
            "info" => $respuesta,
            "categorias" => $categorias,
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

    public function actualizar_lista_producto() {
        $estatus = 0;

        $id_producto = $_POST['id_producto'] ? $_POST['id_producto'] : 0;
        $producto_nombre = $_POST["producto_nombre"] ? $_POST["producto_nombre"] : null;
        $producto_descripcion = $_POST['descripcion'] ? $_POST['descripcion'] : null;
        $producto_costo = $_POST["costo"] ? $_POST["costo"] : 0;
        $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : null;
        $producto_sku = $_POST["sku"] ? $_POST["sku"] : null;
        $producto_codigo_barras = $_POST["codigo_barras"] ? $_POST["codigo_barras"] : null;
        $producto_existencia = $_POST["existencia"] ? $_POST["existencia"] : 0;
        $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : 0;
        $producto_piezas_por_caja = $_POST["piezas_por_caja"] ? $_POST["piezas_por_caja"] : 0;
        $producto_rotacion = $_POST["rotacion"] ? $_POST["rotacion"] : 0;

        $porcentaje_impuesto = $_POST["porcentaje_impuesto"] ? $_POST["porcentaje_impuesto"] : 0;
        $precio_sin_impuestos = $_POST["precio_sin_impuestos"] ? $_POST["precio_sin_impuestos"] : 0;
        $utilidad_bruta = $_POST["utlidad_bruta"] ? $_POST["utlidad_bruta"] : 0;
        $incluye_impuesto = $_POST["incluye_impuesto"] ? $_POST["incluye_impuesto"] : 0;

        $identificador = "";

        $proveedores = $_POST["proveedores"];
        $categorias = $_POST["categorias"];
        if (($id_producto && $id_producto != 0 && $producto_nombre && $producto_descripcion)) {
            $estatus = 1;
            $identificador = $this->crear_identificador($producto_nombre);
        }

        $producto = $this->modelo("Proveedores");
        $producto->setIdentificador($identificador);
        $producto->setId_producto($id_producto);
        $producto->setNombre($producto_nombre);
        $producto->setCodigo_interno($producto_codigo_interno);
        $producto->setDescripcion($producto_descripcion);
        $producto->setCosto($producto_precio_base);
        $producto->setEstatus($estatus);
        $producto->setExistencias($producto_cantidad);
        $producto->setCodigo_barras_base($producto_codigo_barras);
        $producto->setPiezas_por_caja($producto_piezas_por_caja);
        $producto->setRotacion($producto_rotacion);
        $producto->setPorcentaje_impuesto($porcentaje_impuesto);
        $producto->setPrecio_sin_impuestos($precio_sin_impuestos);
        $producto->setUtilidad_bruta($utilidad_bruta);
        $producto->setIncluye_impuesto($incluye_impuesto);

        $respuesta = $producto->actualizar_lista_producto();

        if (is_countable($categorias) && sizeof($categorias) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_categorias_producto();
            foreach ($categorias as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_categoria($value_c);
                $producto->actualizar_categorias_producto();
            }
        }




        echo json_encode($respuesta);
    }

    public function actualizar_portada() {
//    var_dump($_FILES);

        $id_producto = $_POST['id_producto'];
        $tipo_imagen = $_POST['tipo_imagen'];

        $producto = $this->modelo("Proveedores");
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

    public function crear_identificador($cadena) {

        //Reemplazamos espacios por _
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
        return strtolower($cadena);
    }

    public function consultar_categorias_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_categorias_producto();
        return $respuesta;
    }

    public function listas_consultar() {
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_proveedores();
        return json_encode($respuesta);
    }

    public function listas_mayoreo_consultar() {
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_mayoreo();
        return json_encode($respuesta);
    }

    public function usuarios_mayoreo_consultar() {
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_usuarios_mayoreo();
        return json_encode($respuesta);
    }

    public function listas_usuario_mayoreo_consultar() {
//        var_dump($_POST);
//        var_dump($_SESSION);
        $id_usuario = $_POST['id_lista_mayoreo'];

        $lista_mayoreo = array('error' == true);
        $lista_usuario = array();

        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_usuario_mayoreo();

        if ($respuesta['error'] == false) {
            $lista_mayoreo = $respuesta;
        }

        //consultar listas usuario
        $inventario->setId_usuario_mayoreo($id_usuario);
        $respuesta = $inventario->consulta_listas_asignadas_usuario_mayoreo();
        if ($respuesta['error'] == false) {
            $array = $this->arreglo_id_listas($respuesta['depurar']);
            $lista_usuario = $array;
        }


        $resultados = array(
            'listas_mayoreo' => $lista_mayoreo,
            'listas_usuario' => $lista_usuario
        );
        return json_encode($resultados);
    }

    public function arreglo_id_listas($data) {
        $array = array();
        $length = count($data)-1;
        for ($row = 0; $row <= $length; $row++) {
            $array[] = $data[$row]['id_lista_mayoreo'];
        }
        return $array;
    }
    
    public function registrar_grupo_utilidad() {
       
        $nombre_grupo_utilidad = $_POST['nombre_grupo_utilidad'];
        $comentario = $_POST['comentario'];
        $elementos = $_POST['productos'];
        $porcentaje_total = $_POST['porcentaje_total'];
        $inventario = $this->modelo("Utilidades");

        $inventario->setComentario($comentario);
        $inventario->setTitulo($nombre_grupo_utilidad);
        $inventario->setPorcentaje_total($porcentaje_total);
        
        $respuesta = $inventario->registrar_grupo_utilidad();
        $errores_productos_pedido = array();
        if ($respuesta['error'] == false) {
            $id_pedido = $respuesta['depurar'];
            foreach ($elementos as $key => $values) {
                $id_elemento = $values['id_producto'];
                $cantidad = $values['cantidad'];
                $costo = $values['costo'];
                $precio = $values['precio'];

                //pedido

                $inventario->setId_utilidad_grupo_elemento($id_pedido);
                $inventario->setId_producto($id_elemento);
                $inventario->setCosto($costo);
                $inventario->setPrecio($precio);
                $respuesta = $inventario->registrar_elementos_pedido();
                if ($respuesta['error'] == true) {
                    $errores_productos_pedido[] = $respuesta['error'];
                }
            }
        }

        return json_encode($respuesta);

//      $inventario->registrar_elementos_inventario();
    }

    public function consultar_productos_utilidad_busqueda() {
//    var_dump($_POST['busqueda']);
        $busqueda = $_POST['busqueda'];
        if ($busqueda) {
            $productos = $this->modelo("Utilidades");
            $productos->setBusqueda($busqueda);
            $respuesta = $productos->listar_busqueda_productos();
            if ($respuesta['error'] == false) {
                $response = [
                    'error' => false,
                    'tipo' => "success",
                    'mensaje' => "respuesta generada con éxito",
                    'depurar' => $respuesta['depurar']
                ];
            } else {
                $response = [
                    'error' => true,
                    'tipo' => "warning",
                    'mensaje' => "Consulta con cero resultador",
                    'depurar' => []
                ];
            }
        } else {
            $response = [
                'error' => true,
                'tipo' => "warning",
                'mensaje' => "Consulta con cero resultador",
                'depurar' => []
            ];
        }
        return json_encode($response);
    }

    public function consultar_pedidos() {
        $inventario = $this->modelo("Utilidades");
        $respuesta = $inventario->consultar_lista();
        return json_encode($respuesta);
    }

    public function registrar_pedido() {
        $id_proveedor = $_POST['id_proveedor'];
        $proveedor = $_POST['proveedor'];
        $id_lista_proveedor = $_POST['id_lista_proveedor'];
        $nombre_inventario = $_POST['nombre_inventario'];
        $comentario = $_POST['comentario'];
        $elementos = $_POST['productos'];
        $total = $_POST['total'];

        $inventario = $this->modelo("Proveedores");

        $inventario->setProveedor($proveedor);
        $inventario->setId_proveedor($id_proveedor);
        $inventario->setId_lista_proveedor($id_lista_proveedor);
        $inventario->setComentario($comentario);
        $inventario->setEstatus(1);
        $inventario->setTitulo($nombre_inventario);
        $inventario->setTotal($total);
        $respuesta = $inventario->registrar_inventario();

        $errores_productos_pedido = array();
        if ($respuesta['error'] == false) {
            $id_pedido = $respuesta['depurar'];
            foreach ($elementos as $key => $values) {
                $id_elemento = $values['id_producto'];
                $cantidad = $values['cantidad'];
//          $precio = $values['precio'];
//          $importe = $cantidad * $precio;
//          $portada = $values['portada'];
//          $nombre = $values['producto'];
                //pedido
                $inventario->setId_proveedor_pedido($id_pedido);
                $inventario->setCantidad($cantidad);
                $inventario->setId_elemento($id_elemento);
                $respuesta = $inventario->registrar_elementos_pedido();
                if ($respuesta['error'] == true) {
                    $errores_productos_pedido[] = $respuesta['error'];
                }
            }
        }

        return json_encode($respuesta);

//      $inventario->registrar_elementos_inventario();
    }

    public function consultar_productos_lista() {
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Proveedores");

        $proveedor->setId_lista_proveedor($id_lista);
        $response = $proveedor->consultar_productos_lista();

        echo json_encode($response);
    }

    public function listar() {
        $producto = $this->modelo("Proveedores");

        $respuesta = $producto->listar_proveedores();
        echo json_encode($respuesta);
    }

    public function cargar_lista() {
        $this->vista("apps/erp/proveedores/listas_cargar");
    }

    public function cargar_lista_mayoreo() {
        $this->vista("apps/erp/proveedores/listas_mayoreo_cargar");
    }

    public function consultar() {
        
    }

    public function generar_orden_de_compra() {
        $productos = $_POST['productos'];
//        var_dump($productos);
        $length = sizeof($productos) - 1;
//        var_dump($length);

        $id_proveedor = 0;

        $proveedor = $this->modelo("Proveedores");

        $proveedor->setId_proveedor($id_proveedor);
        $respuesta = $proveedor->registrar_orden_compra();
        if ($respuesta['error'] == false) {
            $id_orden_de_compra = $respuesta['depurar'];

            for ($row = 0; $row <= $length; $row++) {
//                var_dump($productos[$row]);
                $nombre = $productos[$row]['producto'];
                $sku = $productos[$row]['codigo'];
                $costo = $productos[$row]['precio'];
                $cantidad = $productos[$row]['cantidad'];
                $id = $productos[$row]['id'];

                $proveedor->setId_orden_de_compra($id_orden_de_compra);
                $proveedor->setNombre($nombre);
                $proveedor->setSku($sku);
                $proveedor->setExistencias($cantidad);
                $proveedor->setCosto($costo);
                $proveedor->setId_producto($id);
//                var_dump($proveedor->getId_producto());
                $respuesta = $proveedor->registrar_producto_orden_compra();
            }
        }
        return json_encode($respuesta);
    }

    public function registrar_lista() {
//        var_dump($_FILES);
        $fileTmpPath = $_FILES["file"]["tmp_name"];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
//    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc');
//    if (in_array($fileExtension, $allowedfileExtensions)) {
//      
//    }
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = "docs/erp/proveedores/" . $newFileName;

        $e = move_uploaded_file($fileTmpPath, $dest_path);
//        var_dump($dest_path);
//        var_dump($newFileName);
//        var_dump($fileTmpPath);
//        var_dump($e);

        $inputFileType = PHPExcel_IOFactory::identify($dest_path);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($dest_path);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
//        var_dump($highestRow);
//        $archivo = $this->modelo("Proveedores");
//        $producto = $this->modelo("Productos");
        //A Codigo
        //B Producto
        //C precio
        //D existencia
        //E Marca
        //F Productos por caja
        //G precio sugerido
        //H rotacion
//    var_dump($highestRow);
        $id_proveedor = 0;
        $id_lista_proveedor = 0;
        $proveedor_lista = $this->modelo("Proveedores");
        $registrada = 0;
        for ($row = 1; $row <= $highestRow; $row++) {

            if ($row == 1) {
                $id_proveedor = $sheet->getCell("A" . $row)->getValue();
                $tipo_lista = $sheet->getCell("B" . $row)->getValue();
                $proveedor_lista->setId_proveedor($id_proveedor);
                $proveedor_lista->setLista($tipo_lista);
                $respuesta = $proveedor_lista->consulta_lista_proveedor();
                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $registrada = 1;
                    $id_lista_proveedor = $respuesta['depurar']['id_lista_proveedor'];
                } else {
                    $proveedor_lista->setId_proveedor($id_proveedor);
                    $proveedor_lista->setLista($tipo_lista);
                    $proveedor_lista->setEstatus(1);
                    $respuesta = $proveedor_lista->registro_lista_proveedor();
                    if ($respuesta['error'] == false) {
                        $id_lista_proveedor = $respuesta['depurar'];
                    }
                }
            } else if ($row >= 3) {
                $codigo = $sheet->getCell("A" . $row)->getValue();
                if (!empty($codigo)) {
                    $producto = $sheet->getCell("B" . $row)->getValue();
                    $precio = $sheet->getCell("C" . $row)->getValue();
                    $existencia = $sheet->getCell("D" . $row)->getValue();
                    $marca = $sheet->getCell("E" . $row)->getValue();
                    $piezas_por_caja = $sheet->getCell("F" . $row)->getValue();
                    $precio_sugerido = $sheet->getCell("G" . $row)->getValue();
                    $rotacion = $sheet->getCell("H" . $row)->getValue();

                    var_dump($respuesta);
                    //registrar producto semilla
                    var_dump($sheet->getCell("A" . $row)->getValue());
                    $proveedor_lista->setSku($codigo);
                    $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                    $respuesta_registro_producto = $proveedor_lista->consultar_producto_lista();
                    var_dump($respuesta_registro_producto);
                    if ($respuesta_registro_producto['error'] == true) {
                        //registrar producto
                        $proveedor_lista->setNombre($producto);
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setMarca($marca);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);
                        $proveedor_lista->setRotacion($rotacion);
                        $proveedor_lista->setPrecio_sugerido($precio_sugerido);
                        $proveedor_lista->setPiezas_por_caja($piezas_por_caja);
                        $proveedor_lista->setExistencias($existencia);
                        $respuesta_producto = $proveedor_lista->registrar_producto_lista();
                        var_dump($respuesta_producto);
                    } else {
                        //actualizar producto existencia
                        $proveedor_lista->setNombre($producto);
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setMarca($marca);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);
                        $proveedor_lista->setRotacion($rotacion);
                        $proveedor_lista->setPrecio_sugerido($precio_sugerido);
                        $proveedor_lista->setPiezas_por_caja($piezas_por_caja);
                        $proveedor_lista->setExistencias($existencia);
                        $respuesta_producto = $proveedor_lista->actualizar_producto_lista();
                        var_dump("actualizar existencia");
                        var_dump($respuesta_producto);
                    }
                }
            }
        }
        unlink($dest_path);
        echo json_encode($respuesta);
    }

    public function registrar_lista_mayoreo() {
//        var_dump($_FILES);
        $fileTmpPath = $_FILES["file"]["tmp_name"];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
//    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc');
//    if (in_array($fileExtension, $allowedfileExtensions)) {
//      
//    }
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = "docs/erp/proveedores/" . $newFileName;

        $e = move_uploaded_file($fileTmpPath, $dest_path);
//        var_dump($dest_path);
//        var_dump($newFileName);
//        var_dump($fileTmpPath);
//        var_dump($e);

        $inputFileType = PHPExcel_IOFactory::identify($dest_path);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($dest_path);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
//        var_dump($highestRow);
//        $archivo = $this->modelo("Proveedores");
//        $producto = $this->modelo("Productos");
        //A Codigo
        //B Producto
        //C precio
        //D existencia
        //E Marca
        //F Productos por caja
        //G precio sugerido
        //H rotacion
//    var_dump($highestRow);
        $id_proveedor = 0;
        $id_lista_proveedor = 0;
        $proveedor_lista = $this->modelo("Proveedores");
        $registrada = 0;
        for ($row = 1; $row <= $highestRow; $row++) {

            if ($row == 1) {

                $id_proveedor = $sheet->getCell("A" . $row)->getValue();

                $tipo_lista = $sheet->getCell("B" . $row)->getValue();

                $proveedor_lista->setId_proveedor($id_proveedor);
                $proveedor_lista->setId_tipo_lista_mayoreo($tipo_lista);

                $respuesta = $proveedor_lista->consulta_lista_mayoreo();
                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    //obtener id_lista_mayoreo
                    $registrada = 1;
                    $id_lista_proveedor = $respuesta['depurar']['id_lista_mayoreo'];
                } else {
                    $proveedor_lista->setId_proveedor($id_proveedor);
                    $proveedor_lista->setLista($tipo_lista);
                    $proveedor_lista->setEstatus(0);
                    $respuesta = $proveedor_lista->registro_lista_mayoreo();
                    if ($respuesta['error'] == false) {
                        $id_lista_proveedor = $respuesta['depurar'];
                    }
                }
            } else if ($row >= 3) {
                $codigo = $sheet->getCell("A" . $row)->getValue();

                if (!empty($codigo)) {
                    $precio = $sheet->getCell("C" . $row)->getValue();
                    //registrar producto semilla
                    $proveedor_lista->setSku($codigo);
                    $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);

                    $respuesta_registro_producto = $proveedor_lista->consultar_producto_lista_mayoreo();
                    var_dump($respuesta_registro_producto);
                    if ($respuesta_registro_producto['error'] == true) {
                        //registrar producto
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);

                        $respuesta_producto = $proveedor_lista->registrar_producto_lista_mayoreo();
                        var_dump($respuesta_producto);
                    } else {
                        //actualizar producto existencia
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);

                        $respuesta_producto = $proveedor_lista->actualizar_producto_lista_mayoreo();
                        var_dump("actualizar existencia");
                        var_dump($respuesta_producto);
                    }
                }
            }
        }
        unlink($dest_path);
        echo json_encode($respuesta);
    }
}
