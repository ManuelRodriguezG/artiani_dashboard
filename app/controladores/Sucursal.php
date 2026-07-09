<?php

class Sucursal extends Controlador {

    function index() {
        
    }

    public function consultar_sucursales() {
        $almacen = $this->modelo('Sucursales');
        $respuesta = $almacen->obtener_sucursales();
        return json_encode($respuesta);
    }

    function alta() {
        $this->vista('apps/erp/sucursales/alta');
    }

    public function registrar_imagen() {
//    var_dump($_FILES);

        $producto = $this->modelo("Sucursales");
//    var_dump($_FILES['portada']);
        $producto->setUrl_origen($_FILES['img']['tmp_name']);
        $extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
        $fecha = new DateTime();
        $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
        //guardar archivo
        $response = $producto->guardar_imagenes();

        echo json_encode($response);
    }

    public function alta_sucursal() {
        $estatus = 0;

        $url_imagen = $_POST['url_imagen'] ? $_POST['url_imagen'] : null;
        $nombre = $_POST['nombre'] ? $_POST['nombre'] : null;
        $pais = $_POST['pais'] ? $_POST['pais'] : null;
        $ciudad = $_POST['ciudad'] ? $_POST['ciudad'] : null;
        $colonia = $_POST['colonia'] ? $_POST['colonia'] : null;
        $codigo_postal = $_POST['codigo_postal'] ? $_POST['codigo_postal'] : null;
        $calle = $_POST['calle'] ? $_POST['calle'] : null;
        $numero_exterior = $_POST['numero_exterior'] ? $_POST['numero_exterior'] : null;
        $numero_interior = $_POST['numero_interior'] ? $_POST['numero_interior'] : null;
        $incrustado = $_POST['incrustado'] ? $_POST['incrustado'] : null; 

        //Variantes
        if (isset($nombre)) {
            $producto = $this->modelo("Sucursales");
            //verificar si ya existe alguna variante 
            $identificador = $this->crear_identificador($nombre);
            $producto->setIdentificador($identificador);
            $producto->setNombre($nombre);
            $producto->setPais($pais);
            $producto->setCiudad($ciudad);
            $producto->setColonia($colonia);
            $producto->setCodigo_postal($codigo_postal);
            $producto->setCalle($calle);
            $producto->setNumero_exterior($numero_exterior);
            $producto->setNumero_interior($numero_interior);
            $producto->setUrl_imagen($url_imagen);
            $producto->setIncrustado($incrustado);
            $respuesta = $producto->registrar_imagen_compra();
        } else {
            $return = array('error' => true, 'tipo' => 'warning', 'mensaje' => 'Faltan datos para poder realizar la solicitud');
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
