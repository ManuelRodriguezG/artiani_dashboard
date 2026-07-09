<?php

class Dashboard extends Controlador {

  public function __construct() {
    //echo 'Controlador Pagina cargada';
  }

  public function quienes_somos() {
    var_dump('quienes somos');
  }

  public function login() {
    $this->vista('login', '');
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

    $this->vista('login', '');
  }

}
