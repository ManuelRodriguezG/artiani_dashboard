<?php

class Categoria extends Controlador {

  private $uri_categoria = "/producto/categoria/";

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function index() {
    
  }

  public function crear() {
    $this->requerirPermiso("catalogo.editar");
    $this->vista("apps/ecommerce/catalog/agregar-categoria");
  }

  public function mostrar() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/ecommerce/catalog/categorias");
  }

  public function actualizar_portada() {
//    var_dump($_FILES);

    $id_producto = $_POST['id_categoria'];
    $tipo_imagen = $_POST['tipo_imagen'];

    $producto = $this->modelo("Categorias");
//    var_dump($_FILES['portada']);
    $producto->setId_categoria($_POST['id_categoria']);
    $producto->setUrl_origen($_FILES['portada']['tmp_name']);
    $extension = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
    $fecha = new DateTime();
    $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
    //guardar archivo
    $response = $producto->guardar_imagenes();

    if ($response['error'] == false) {
      $url_imagen = $response['depurar'];
      //actualizar
      $producto->setUrl_imagen($url_imagen);
      $producto->setTipo_imagen($tipo_imagen);
      $response = $producto->actualizar_imagen();
    }
    echo json_encode($response);
  }

  public function registrar() {
    $categoria_nombre = $_POST["categoria"];
    $descripcion = $_POST["descripcion"];
    $identificador = $this->crear_identificador($categoria_nombre);
    $url_categoria = $this->uri_categoria . $identificador;

    $categoria = $this->modelo("Categorias");

    $categoria->setUrl_categoria($url_categoria);
    $categoria->setIdentificador($identificador);
    $categoria->setNombre($categoria_nombre);
    $categoria->setDescripcion($descripcion);

    $respuesta = $categoria->registrar();
    echo json_encode($respuesta);
  }

  public function crear_identificador($cadena) {

    //Reemplazamos espacios por _
    $cadena = str_replace(
            array(' '),
            array('-'),
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

  public function consultar() {
    $categoria = $this->modelo("Categorias");
    $respuesta = $categoria->consultar();
    echo json_encode($respuesta);
  }

}
