<?php

/* Controladores */
include_once 'Productos.php';

class Panel extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function quienes_somos() {
    var_dump('quienes somos');
  }

  public function productos(...$params) {
//    var_dump($params);
    $accion = $params[0];
    switch ($accion) {
      case 'crear':
        $this->productos = new Productos;
        $this->productos->crear_producto();
        break;

      default:
        break;
    }
  }

  public function index() {

    //var_dump($tours['respuesta']);
    //método de la página inicial
    //$this->pageIndex = $this->modelo('Tour');
//var_dump('tours');
    //$datos = $this->pageIndex->buildIndex('ES');
    //$datos = [
    //	'titulo'=>'Bienvenido al HOME',
    //	
    //];

    $this->vista('dashboard', $datos);
  }

}
