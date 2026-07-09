<?php

class Busqueda extends Controlador {

    function consulta(...$query) {
        $datos = array(
            "busqueda" => $query[0]
        );
        $this->vista('apps/ecommerce/catalog/busqueda', $datos);
    }

    private function obtener_parametros_busqueda($pathname) {
        $arreglo = array_filter(explode("/", substr($pathname, 1)));
//    var_dump($arreglo);

        return $arreglo[2];
    }

    public function obtener_palabras_busqueda($cadena) {

        //Reemplazamos espacios por _
//    $cadena = str_replace(
//            array(' '),
//            array('-'),
//            $cadena
//    );
//        var_dump($cadena);
//        $cadena = strtolower($cadena);
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
        $cadena = str_replace(
                array(' la ', ' las ', ' lo ', ' los ', ' el ', ' ellos ', ' esto ', ' para ', ' por ', ' una ', ' un ', ' unos ', ' unas ', ' be ', ' de '),
                array(' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '),
                $cadena
        );
//        var_dump($cadena);
        return explode(' ', $cadena);
    }

    function consultar_busqueda() {
        $busqueda = $_POST['busqueda'];
//    $query = $this->obtener_parametros_busqueda($busqueda);
//    var_dump($busqueda);
        if ($busqueda) {
            //obtener palabras busqueda
            $palabras_busqueda = $this->obtener_palabras_busqueda($busqueda);
//            var_dump($palabras_busqueda);
            if (count($palabras_busqueda) > 0) {
                $response = $this->busqueda_palabras($palabras_busqueda);
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

    function busqueda_palabras($palabras) {
        $productos = $this->modelo("Productos");
        $productos_array = array();
        $resultados = null;
        foreach ($palabras as $key => $value) {
            $productos->setBusqueda($value);
            $respuesta = $productos->listar_busqueda_ids_productos();
            if ($respuesta['error'] == false) {
                //procesar resultados
                $resultados = $this->procesar_resultados($respuesta['depurar'], $resultados);

                $paquetes = $this->modelo("Paquete");
                $paquetes->setBusqueda($value);
                $respuesta_paquetes = $paquetes->listar_busqueda_paquetes_ids();
                $respuesta_busqueda = $respuesta_paquetes['depurar'];
                if ($respuesta_paquetes['error'] == false) {
                    $resultados = $this->procesar_resultados($respuesta_paquetes['depurar'], $resultados);
//                    $respuesta_busqueda_paquetes = $respuesta_paquetes['depurar'];
//                    $respuesta_busqueda = array_merge($respuesta_busqueda, $respuesta_busqueda_paquetes);
                }
//                $productos_array[] = $respuesta_busqueda;
//                $response = [
//                    'error' => false,
//                    'tipo' => "success",
//                    'mensaje' => "respuesta generada con éxito",
//                    'depurar' => $respuesta_busqueda
//                ];
            } else {
                $paquetes = $this->modelo("Paquete");
                $paquetes->setBusqueda($value);
                $respuesta_paquetes = $paquetes->listar_busqueda_paquetes_ids();
                if ($respuesta_paquetes['error'] == false) {
                    $resultados = $this->procesar_resultados($respuesta_paquetes['depurar'], $resultados);
//                    $respuesta_busqueda_paquetes = $respuesta_paquetes['depurar'];
//                    $respuesta_busqueda = $respuesta_busqueda_paquetes;
//                    $response = [
//                        'error' => false,
//                        'tipo' => "success",
//                        'mensaje' => "respuesta generada con éxito",
//                        'depurar' => $respuesta_busqueda
//                    ];
                    $productos_array[] = $respuesta_busqueda;
                } else {
                    $response = [
                        'error' => true,
                        'tipo' => "warning",
                        'mensaje' => "Consulta con cero resultador",
                        'depurar' => []
                    ];
                }
            }
        }
//        var_dump($resultados);
        if ($resultados != null) {
            $ids = $this->obtener_ids_ordenados($resultados['resultados']);
//            var_dump(count($ids));
            if (count($ids) > 0) {
                $respuesta = $this->busqueda_items($ids);
//                var_dump($respuesta);
                $response = [
                        'error' => false,
                        'tipo' => "success",
                        'mensaje' => "busqueda obtenida con éxito",
                        'depurar' => $respuesta
                    ];
            }
        }else{
            $response = [
                        'error' => true,
                        'tipo' => "warning",
                        'mensaje' => "Consulta con cero resultador",
                        'depurar' => []
                    ];
        }
//        var_dump($response);
        return ($response);
//        var_dump(json_encode($productos_array));
    }

    private function busqueda_items($ids) {
        $respuesta = null;
        $resultados = array();
        $productos = $this->modelo("Productos");
        $paquetes = $this->modelo("Paquete");
//        var_dump($ids);
        foreach ($ids as $key => $value) {
//            $ids = implode(',', $value);
            if ($value['tipo_item'] == 'producto') {
                $productos = $this->modelo("Productos");
                $productos->setId_producto($value['id_item']);
                $respuesta = $productos->listar_busqueda_productos_imagen();
//                var_dump($respuesta);
                if($respuesta['error'] == false){
                    $resultados[] = $respuesta['depurar'][0];
                }
            } else if ($value['tipo_item'] == 'paquete') {
                $paquetes->setId_producto($value['id_item']);
                $respuesta = $paquetes->listar_busqueda_paquetes_imagen();
//                var_dump($respuesta);
                if($respuesta['error'] == false){
                    $resultados[] = $respuesta['depurar'][0];
                }
            }
        }
//        var_dump(json_encode($resultados));
        return $resultados;
    }

    private function obtener_ids_ordenados($array) {
        $ordenados = array();
        foreach ($array as $key => $value) {
            $arr_item = explode('-', $key);
            $tipo_item = $arr_item[0];
            $id_item = $arr_item[1];
//            $ordenados[$tipo_item][] = $id_item;
            $ordenados[] = array(
                'tipo_item' => $tipo_item,
                'id_item' => $id_item
            );
        }
        return $ordenados;
    }

    private function procesar_resultados($resultados, $resultados_anteriores = null) {
//        var_dump($resultados_anteriores);
        //array(
        //'producto-234' => array(
        //  'recurrencia' => 2
        //)
        //),
        //'paquete-234 => array(
        //  'recurrencia' => 5
        //)
        //
        $resultados_items = $resultados_anteriores == null ? array() : $resultados_anteriores['resultados'];
        $items = $resultados_anteriores == null ? array() : $resultados_anteriores['items'];
        ;
        foreach ($resultados as $key => $value) {
            $identificador = $value['tipo_item'] . '-' . $value['id_item'];
            if (!in_array($identificador, $items)) {
                $items[] = $identificador;
                $resultados_items[$identificador] = 1;
            } else {
                $recurrencia = $resultados_items[$identificador] + 1;
                $resultados_items[$identificador] = $recurrencia;
            }
        }
//        var_dump($resultados_items);
//        $fruitArrayObject = new ArrayObject($resultados_items);
//        $fruitArrayObject->asort();

        arsort($resultados_items);
//        var_dump("<br>");
//        var_dump($resultados_items);
//        var_dump("<br>");
        return array(
            'resultados' => $resultados_items,
            'items' => $items
        );
    }
}
