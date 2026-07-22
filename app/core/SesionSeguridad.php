<?php

class SesionSeguridad {

  public static function usuarioId() {
    return isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 0;
  }

  public static function autenticado() {
    return self::usuarioId() > 0;
  }

  public static function sesionExpirada() {
    if (!self::autenticado()) {
      return true;
    }
    $ultimaActividad = isset($_SESSION['ultima_actividad']) ? intval($_SESSION['ultima_actividad']) : 0;
    return $ultimaActividad > 0 && (time() - $ultimaActividad) > SESSION_TIMEOUT_SECONDS;
  }

  public static function tocarActividad() {
    $_SESSION['ultima_actividad'] = time();
  }

  public static function usuarioReautenticacionId() {
    if (self::usuarioId() > 0) {
      return self::usuarioId();
    }
    return isset($_SESSION['usuario_reautenticar_id']) ? intval($_SESSION['usuario_reautenticar_id']) : 0;
  }

  public static function expirarSesion() {
    $idUsuario = self::usuarioId();
    $_SESSION = array('usuario_reautenticar_id' => $idUsuario);
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  public static function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
  }

  public static function renovarCsrfToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
  }

  public static function validarCsrf() {
    $token = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : (isset($_POST['_csrf']) ? $_POST['_csrf'] : '');
    return is_string($token) && $token !== '' && hash_equals(self::csrfToken(), $token);
  }

  public static function requerirCsrf() {
    if (self::validarCsrf()) {
      return true;
    }

    self::registrarAuditoria('seguridad', 'csrf_invalido', array(
      'resultado' => 'denegado',
      'mensaje' => 'Solicitud POST rechazada por token CSRF invalido'
    ));
    http_response_code(419);
    echo json_encode(array(
      'error' => true,
      'tipo' => 'danger',
      'mensaje' => 'La solicitud de seguridad expiro. Recarga la pagina antes de continuar.',
      'depurar' => array('codigo' => 'csrf_invalido')
    ));
    exit;
  }

  public static function esPeticionJson() {
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    $requestedWith = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) : '';
    return $requestedWith === 'xmlhttprequest' || strpos($accept, 'application/json') !== false;
  }

  public static function requerirSesion() {
    if (self::autenticado() && self::sesionExpirada()) {
      self::expirarSesion();
    }

    if (self::autenticado()) {
      self::tocarActividad();
      return true;
    }

    if (self::esPeticionJson()) {
      http_response_code(401);
      echo json_encode(array(
        'error' => true,
        'tipo' => 'warning',
        'mensaje' => 'Sesion expirada o no iniciada',
        'depurar' => array('redirigir' => '/autenticacion/login')
      ));
      exit;
    }

    header('Location: /autenticacion/login');
    exit;
  }

  public static function iniciarSesionUsuario($usuario) {
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }

    $_SESSION['apellido_paterno'] = isset($usuario['apellido_paterno']) ? $usuario['apellido_paterno'] : null;
    $_SESSION['apellido_materno'] = isset($usuario['apellido_materno']) ? $usuario['apellido_materno'] : null;
    $_SESSION['nombres'] = isset($usuario['nombres']) ? $usuario['nombres'] : null;
    $_SESSION['alias'] = isset($usuario['alias']) ? $usuario['alias'] : null;
    $_SESSION['correo'] = isset($usuario['correo']) ? $usuario['correo'] : null;
    $_SESSION['nombre_mostrar'] = isset($usuario['nombre_mostrar']) ? $usuario['nombre_mostrar'] : null;
    $_SESSION['area_departamento'] = isset($usuario['area_departamento']) ? $usuario['area_departamento'] : null;
    $_SESSION['puesto'] = isset($usuario['puesto']) ? $usuario['puesto'] : null;
    $_SESSION['id_usuario'] = isset($usuario['id_usuario']) ? intval($usuario['id_usuario']) : 0;
    unset($_SESSION['usuario_reautenticar_id']);
    $_SESSION['roles'] = isset($usuario['roles']) && is_array($usuario['roles']) ? $usuario['roles'] : array();
    $_SESSION['permisos'] = isset($usuario['permisos']) && is_array($usuario['permisos']) ? $usuario['permisos'] : array();
    self::renovarCsrfToken();
    self::tocarActividad();

    if (isset($usuario['id_rango'])) {
      $_SESSION['id_rango'] = intval($usuario['id_rango']);
    }
    if (isset($usuario['id_rol'])) {
      $_SESSION['id_rol'] = intval($usuario['id_rol']);
    }
  }

  public static function cerrarSesion() {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
  }

  public static function hashContrasenia($contrasenia) {
    return password_hash($contrasenia, PASSWORD_DEFAULT);
  }

  public static function verificarContrasenia($contrasenia, $hashGuardado) {
    if (!is_string($hashGuardado) || $hashGuardado === '') {
      return false;
    }

    $info = password_get_info($hashGuardado);
    if (isset($info['algoName']) && $info['algoName'] !== 'unknown') {
      return password_verify($contrasenia, $hashGuardado);
    }

    return hash_equals($hashGuardado, hash('sha256', $contrasenia));
  }

  public static function requiereRehash($hashGuardado) {
    if (!is_string($hashGuardado) || $hashGuardado === '') {
      return false;
    }
    $info = password_get_info($hashGuardado);
    return !isset($info['algoName']) || $info['algoName'] === 'unknown' || password_needs_rehash($hashGuardado, PASSWORD_DEFAULT);
  }

  public static function tienePermiso($permiso) {
    return isset($_SESSION['permisos']) && in_array($permiso, $_SESSION['permisos'], true);
  }

  public static function registrarAuditoria($modulo, $accion, $opciones = array()) {
    try {
      require_once '../app/modelos/SeguridadPermisos.php';
      $seguridad = new SeguridadPermisos();
      return $seguridad->registrarEvento(array_merge(array(
        'id_usuario' => self::usuarioId() ?: null,
        'modulo' => $modulo,
        'accion' => $accion,
        'resultado' => 'ok',
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null
      ), $opciones));
    } catch (Throwable $e) {
      return null;
    }
  }
}
