<?php

class Autenticacion extends Controlador {

  function login() {
    $this->vista('autentication/login');
  }

  function registro() {
    header('Location: /sistema/seguridad');
    exit;
  }

  function registrar_usuario() {
    $this->requerirPermiso("seguridad.administrar");
    http_response_code(410);
    echo json_encode(array(
      "error" => true,
      "tipo" => "warning",
      "mensaje" => "El registro legacy esta deshabilitado. Usa Seguridad > Usuarios y roles > Nuevo usuario.",
      "depurar" => array("codigo" => "registro_legacy_deshabilitado")
    ));
    return;

    $apellido_paterno = isset($_POST['apellido_paterno']) && $_POST['apellido_paterno'] != null ? trim($_POST['apellido_paterno']) : null;
    $apellido_materno = isset($_POST['apellido_materno']) ? trim($_POST['apellido_materno']) : null;
    $nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : null;
    $celular = isset($_POST['celular']) ? trim($_POST['celular']) : 0;
    $contrasenia = isset($_POST['contrasenia']) ? $_POST['contrasenia'] : null;
    $confirmar_contrasenia = isset($_POST['confirmar_contrasenia']) ? $_POST['confirmar_contrasenia'] : null;

    if ($nombres != null && $celular != 0 && $contrasenia != null && $confirmar_contrasenia != null) {
      if ($contrasenia == $confirmar_contrasenia) {
        $registro = $this->modelo("Autenticaciones");
        $registro->setNombres($nombres);
        $registro->setApellido_paterno($apellido_paterno);
        $registro->setApellido_materno($apellido_materno);
        $registro->setCelular($celular);
        $registro->setContrasenia(SesionSeguridad::hashContrasenia($contrasenia));
        $registro->setEstatus(0);

        $respuesta = $registro->crear_registro();
        if ($respuesta['error'] == false) {
          $id_usuario = $respuesta['depurar'];
          $variables_session = [
              "apellido_paterno" => $apellido_paterno,
              "apellido_materno" => $apellido_materno,
              "nombres" => $nombres,
              "celular" => $celular,
              "id_usuario" => $id_usuario
          ];
          $respuesta = [
              'error' => false,
              'tipo' => 'success',
              'mensaje' => 'Registro creado con éxito',
              'depurar' => []
          ];
        } else {
          
        }
      } else {
        $respuesta = [
            'error' => true,
            'tipo' => 'danger',
            'mensaje' => 'La contraseña no coincide',
            'depurar' => []
        ];
      }
    } else {
      $respuesta = [
          'error' => true,
          'tipo' => 'danger',
          'mensaje' => 'Los datos están incompletos',
          'depurar' => []
      ];
    }
    return json_encode($respuesta);
  }

  private function crear_variables_session($data) {
    SesionSeguridad::iniciarSesionUsuario($data);
  }

  function inicio_session() {
    $contrasenia = isset($_POST['contrasenia']) ? $_POST['contrasenia'] : null;
    $celular = isset($_POST['celular']) ? trim($_POST['celular']) : 0;
    if ($contrasenia != null && $celular != 0) {
      $registro = $this->modelo("Autenticaciones");
      $registro->setCelular($celular);
      $respuesta = $registro->consultar_usuario();
      if ($respuesta['error'] == false) {
        $estatus = $respuesta['depurar']['estatus'];
        if ($estatus == 1) {
          $contrasenia_db = $respuesta['depurar']['contrasenia'];
          if (SesionSeguridad::verificarContrasenia($contrasenia, $contrasenia_db)) {
            if (SesionSeguridad::requiereRehash($contrasenia_db)) {
              $registro->actualizar_contrasenia($respuesta['depurar']['id_usuario'], SesionSeguridad::hashContrasenia($contrasenia));
            }
            $variables_session = [
                "apellido_paterno" => $respuesta['depurar']['apellido_paterno'],
                "apellido_materno" => $respuesta['depurar']['apellido_materno'],
                "nombres" => $respuesta['depurar']['nombres'],
                "id_usuario" => $respuesta['depurar']['id_usuario'],
                "id_rango" => isset($respuesta['depurar']['id_rango']) ? $respuesta['depurar']['id_rango'] : null,
                "id_rol" => isset($respuesta['depurar']['id_rol']) ? $respuesta['depurar']['id_rol'] : null
            ];
            $seguridad = $this->modelo("SeguridadPermisos");
            $variables_session = array_merge($variables_session, $seguridad->autorizacionUsuario($respuesta['depurar']['id_usuario']));
            $this->crear_variables_session($variables_session);
            SesionSeguridad::registrarAuditoria('autenticacion', 'inicio_session', array(
              'resultado' => 'ok',
              'mensaje' => 'Inicio de sesion exitoso'
            ));
            $respuesta = [
                'error' => false,
                'tipo' => 'success',
                'mensaje' => 'Inicio de sesion exitoso',
                'depurar' => []
            ];
          } else {
            SesionSeguridad::registrarAuditoria('autenticacion', 'inicio_session', array(
              'resultado' => 'denegado',
              'mensaje' => 'Credenciales incorrectas para celular ' . $celular
            ));
            $respuesta = [
                'error' => true,
                'tipo' => 'danger',
                'mensaje' => 'Usuario o contraseña incorrectos',
                'depurar' => []
            ];
          }
        } else {
          $respuesta = [
              'error' => true,
              'tipo' => 'danger',
              'mensaje' => 'Usuario inactivo, comunicarse con el Jefaso :D',
              'depurar' => []
          ];
        }
      } else {
        $respuesta = [
            'error' => true,
            'tipo' => 'danger',
            'mensaje' => 'Usuario no existe',
            'depurar' => []
        ];
      }
    } else {
      $respuesta = [
          'error' => true,
          'tipo' => 'danger',
          'mensaje' => 'Usuario o contraseña incorrectos',
          'depurar' => []
      ];
    }
    return json_encode($respuesta);
  }

  function estado_session() {
    if (SesionSeguridad::autenticado() && !SesionSeguridad::sesionExpirada()) {
      return json_encode(array('error' => false, 'tipo' => 'success', 'mensaje' => 'Sesion activa'));
    }
    http_response_code(401);
    return json_encode(array('error' => true, 'tipo' => 'warning', 'mensaje' => 'Sesion expirada'));
  }

  function reautenticar_session() {
    $contrasenia = isset($_POST['contrasenia']) ? $_POST['contrasenia'] : null;
    $celular = isset($_POST['celular']) ? trim($_POST['celular']) : 0;
    $idUsuarioEsperado = SesionSeguridad::usuarioReautenticacionId();
    if ($contrasenia == null || $celular == 0) {
      return json_encode(array('error' => true, 'tipo' => 'warning', 'mensaje' => 'Ingresa celular y contrasena'));
    }

    $registro = $this->modelo("Autenticaciones");
    $registro->setCelular($celular);
    $respuesta = $registro->consultar_usuario();
    if ($respuesta['error'] == true || intval($respuesta['depurar']['estatus']) !== 1) {
      return json_encode(array('error' => true, 'tipo' => 'danger', 'mensaje' => 'Usuario no disponible'));
    }

    $usuario = $respuesta['depurar'];
    if (!SesionSeguridad::verificarContrasenia($contrasenia, $usuario['contrasenia'])) {
      return json_encode(array('error' => true, 'tipo' => 'danger', 'mensaje' => 'Usuario o contrasena incorrectos'));
    }
    if ($idUsuarioEsperado > 0 && intval($usuario['id_usuario']) !== $idUsuarioEsperado) {
      return json_encode(array('error' => true, 'tipo' => 'danger', 'mensaje' => 'Reactiva la sesion con el mismo usuario'));
    }

    if (SesionSeguridad::requiereRehash($usuario['contrasenia'])) {
      $registro->actualizar_contrasenia($usuario['id_usuario'], SesionSeguridad::hashContrasenia($contrasenia));
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $autorizacion = $seguridad->autorizacionUsuario($usuario['id_usuario']);
    $this->crear_variables_session(array_merge($usuario, $autorizacion));
    SesionSeguridad::registrarAuditoria('autenticacion', 'reautenticar_session', array(
      'resultado' => 'ok',
      'mensaje' => 'Sesion reactivada'
    ));
    return json_encode(array(
      'error' => false,
      'tipo' => 'success',
      'mensaje' => 'Sesion reactivada',
      'depurar' => array('csrf_token' => SesionSeguridad::csrfToken())
    ));
  }

  function cerrar_session() {
    SesionSeguridad::registrarAuditoria('autenticacion', 'cerrar_session', array(
      'resultado' => 'ok',
      'mensaje' => 'Cierre de sesion'
    ));
    SesionSeguridad::cerrarSesion();
    header('Location: /autenticacion/login');
    exit;
  }

}
