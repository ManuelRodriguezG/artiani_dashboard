<?php

class Link extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function seguimiento() {
    $this->vista("apps/crm/customers/links");
  }

  public function crear() {
    $asesor = $_POST['asesor'];
    $nombre_cliente = $_POST['nombre_cliente'];
    $medio_contacto = $_POST['medio_contacto'];
    $numero_contacto = $_POST['numero_contacto'];
    $codigo = $this->codigo_unico_usuario();
    $link = $this->modelo("Links");

    $link->setAsesor($asesor);
    $link->setNombre($nombre_cliente);
    $link->setMedio_contacto($medio_contacto);
    $link->setNumero_contacto($numero_contacto);
    $link->setCodigo($codigo);
    $respuesta = $link->registrar_link();
    if ($respuesta['error'] == false) {
      $respuesta = array(
          'error' => false,
          'tipo' => "success",
          'mensaje' => "Link generado con éxito",
          'depurar' => array(
              "link" => RUTA_URL_FRONT . "link/" . $codigo
      ));
    } else {
      $respuesta = array(
          'error' => true,
          'tipo' => "danger",
          'mensaje' => "Error al registrar el link",
          'depurar' => $respuesta);
    }
    return json_encode($respuesta);
  }

  private function codigo_unico_usuario() {
    if (function_exists('random_bytes')) {
      $salt = bin2hex(random_bytes(5));
    } else if (function_exists('mcrypt_create_iv')) {
      $salt = bin2hex(mcrypt_create_iv(5, MCRYPT_DEV_URANDOM));
    } else if (function_exists('openssl_random_pseudo_bytes')) {
      $salt = bin2hex(openssl_random_pseudo_bytes(5));
    }
    return $salt;
  }

}
