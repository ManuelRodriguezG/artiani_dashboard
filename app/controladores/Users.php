<?php

class Users extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function create_account() {
    var_dump('create_account');
    var_dump($_POST);
    //$this->vista("login");
    $this->vista('login', '');
  }

  public function index() {
    $this->vista("dashboard");
  }

  public function products(...$params) {
    var_dump($params);
  }

}

?>