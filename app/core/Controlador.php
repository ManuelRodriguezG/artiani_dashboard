<?php

//Clase contorlador principal
//Se encarga de poder cargar los modelos y las vistas

class Controlador {

  //Cargar modelo
  public function modelo($modelo) {
    //carga
    require_once '../app/modelos/' . $modelo . '.php';
    //instanciar el modelo
    return new $modelo();
  }

  //Cargar vista
  public function vista($vista, $datos = []) {
    //checar si el archivo vista existe
    
    if (file_exists('../app/vistas/paginas/' . $vista . '.php')) {
      require_once '../app/vistas/paginas/' . $vista . '.php';
    } else {
      //si el archivo de la vista no existe
      die('la vista no existe');
    }
  }

  protected function requerirSesion() {
    return SesionSeguridad::requerirSesion();
  }

  protected function usuarioActualId() {
    return SesionSeguridad::usuarioId();
  }

  protected function requerirPermiso($permiso) {
    $this->requerirSesion();
    $seguridad = $this->modelo("SeguridadPermisos");
    if ($seguridad->usuarioTienePermiso($this->usuarioActualId(), $permiso)) {
      return true;
    }

    if (SesionSeguridad::esPeticionJson()) {
      SesionSeguridad::registrarAuditoria('seguridad', 'permiso_denegado', array(
        'resultado' => 'denegado',
        'mensaje' => $permiso
      ));
      http_response_code(403);
      echo json_encode(array(
        'error' => true,
        'tipo' => 'warning',
        'mensaje' => 'No tienes permiso para realizar esta accion',
        'depurar' => array('permiso' => $permiso)
      ));
      exit;
    }

    http_response_code(403);
    SesionSeguridad::registrarAuditoria('seguridad', 'permiso_denegado', array(
      'resultado' => 'denegado',
      'mensaje' => $permiso
    ));
    die('No tienes permiso para realizar esta accion');
  }

  public function getRealIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
      return $_SERVER['HTTP_CLIENT_IP'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
      return $_SERVER['HTTP_X_FORWARDED_FOR'];

    return $_SERVER['REMOTE_ADDR'];
  }

  /**
   * Funcion que devuelve el Navegador Actual 
   * * */
  public function obtenerNavegadorWeb() {
    $agente = $_SERVER['HTTP_USER_AGENT'];
    $navegador = 'Unknown';
    $platforma = 'Unknown';
    $version = "";
    #Obtenemos la Plataforma
    if (preg_match('/linux/i', $agente)) {
      $platforma = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $agente)) {
      $platforma = 'mac';
    } elseif (preg_match('/windows|win32/i', $agente)) {
      $platforma = 'windows';
    }
    #Obtener el UserAgente
    if (preg_match('/MSIE/i', $agente) && !preg_match('/Opera/i', $agente)) {
      $navegador = 'Internet Explorer';
      $navegador_corto = "MSIE";
    } elseif (preg_match('/Firefox/i', $agente)) {
      $navegador = 'Mozilla Firefox';
      $navegador_corto = "Firefox";
    } elseif (preg_match('/Chrome/i', $agente)) {
      $navegador = 'Google Chrome';
      $navegador_corto = "Chrome";
    } elseif (preg_match('/Safari/i', $agente)) {
      $navegador = 'Apple Safari';
      $navegador_corto = "Safari";
    } elseif (preg_match('/Opera/i', $agente)) {
      $navegador = 'Opera';
      $navegador_corto = "Opera";
    } elseif (preg_match('/Netscape/i', $agente)) {
      $navegador = 'Netscape';
      $navegador_corto = "Netscape";
    }
    #Obtenemos la Version
    $known = array('Version', $navegador_corto, 'other');
    $pattern = '#(?' . join('|', $known) .
            ')[/ ]+(?[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $agente, $matches)) {
      #No se obtiene la version simplemente continua
    }
    $i = is_countable($matches['browser']);
    if ($i != 1) {
      if (strripos($agente, "Version") < strripos($agente, $navegador_corto)) {
        $version = $matches['version'][0];
      } else {
        $version = $matches['version'][1];
      }
    } else {
      $version = $matches['version'][0];
    } /* Verificamos si tenemos Version */ if ($version == null || $version == "") {
      $version = "?";
    } /* Resultado final del Navegador Web que Utilizamos */
    return array(
        'agente' => $agente,
        'nombre' => $navegador,
        'version' => $version,
        'platforma' => $platforma
    );
  }

}

?>	
