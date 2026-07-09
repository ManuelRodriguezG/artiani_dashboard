<?php

class Clientes extends Controlador {

    public function __construct() {
        if (!$_SESSION['id_usuario']) {
            header('Location: /autenticacion/login');
            exit;
        }
    }

    function index() {
        
    }

    function cargar_fotos() {
        $this->vista('apps/ecommerce/customers/cargar_fotos');
    }

    public function actualizar_portada() {
//    var_dump($_FILES);

        $producto = $this->modelo("Cliente");
//    var_dump($_FILES['portada']);
        $producto->setUrl_origen($_FILES['img']['tmp_name']);
        $extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        $fecha = new DateTime();
        $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
        //guardar archivo
        $response = $producto->guardar_imagenes();

        echo json_encode($response);
    }

    public function guardar_productos_imagen() {
        $estatus = 0;

        $url_imagen = $_POST['url_imagen'] ? $_POST['url_imagen'] : null;
        $titulo = $_POST['titulo'] ? $_POST['titulo'] : null;
        $variantes = $_POST["variantes"];
        //Variantes
        if (is_countable($variantes) && sizeof($variantes) > 0) {
            $producto = $this->modelo("Cliente");
            //verificar si ya existe alguna variante 
            $identificador = $this->crear_identificador($titulo);
            $producto->setUrl_imagen_compra($url_imagen);
            $producto->setTitulo($titulo);
            $producto->setIdentificador($identificador);
            $respuesta = $producto->registrar_imagen_compra();
            if ($respuesta['error'] == false) {
                $id_imagen = $respuesta['depurar'];
                foreach ($variantes as $key_v) {
                    $id_producto = $key_v;
                    $producto->setId_producto($id_producto);
                    $producto->setId_imagen_compra($id_imagen);

                    //registrar variantes
                    $respuesta = $producto->registrar_producto_imagen_compra();
//               
                }
            }

            //registrar variantes con id variante existente
            //guardar ids variantes si habia registrados
            //eliminar variantes productos
            //regresar a la normalidad los productos variantes anteriores
            //registrar variantes productos nuevas
            //actualizar tipos de productos
//      var_dump($producto->eliminar_categorias_producto());
        } else {
            
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
        return strtolower($cadena);
    }
}
