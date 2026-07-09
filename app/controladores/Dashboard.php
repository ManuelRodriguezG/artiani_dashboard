<?php

class Dashboard extends Controlador {

  public function __construct() {
    //echo 'Controlador Pagina cargada Producto';
  }

  public function login() {
    $this->vista("login");
  }

  public function index() {
    $this->vista("dashboard");
  }

  public function products(...$params) {
    var_dump($params);
  }

}

?>