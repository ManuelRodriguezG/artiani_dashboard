<?php

class Sistema extends Controlador {
  
  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function metodos_pago_listar() {
    $metodos_pago = $this->modelo("Metodos_pago");

    $respuesta = $metodos_pago->metodos_listar();
    echo json_encode($respuesta);
  }

  public function puntos_entrega_listar() {
    $metodos_pago = $this->modelo("Puntos_entrega");

    $respuesta = $metodos_pago->puntos_listar();
    if ($respuesta['error'] == false) {
      $array_tipos_entrega = [];
      foreach ($respuesta['depurar'] as $tipo_entrega) {
        $array_tipos_entrega[$tipo_entrega['tipo_entrega']]['id_tipo_entrega'] = $tipo_entrega['id_tipo_entrega'];
        $array_tipos_entrega[$tipo_entrega['tipo_entrega']]['puntos_entrega'] = $tipo_entrega['puntos_entrega'];
        $array_tipos_entrega[$tipo_entrega['tipo_entrega']]['tipo_entrega'] = $tipo_entrega['tipo_entrega'];
        $array_tipos_entrega[$tipo_entrega['tipo_entrega']]['id_tipo_entrega'] = $tipo_entrega['id_tipo_entrega'];
        $array_tipos_entrega[$tipo_entrega['tipo_entrega']]["default"] = $tipo_entrega['default'];
        if ($tipo_entrega['puntos_entrega'] == 1) {
          $array_tipos_entrega[$tipo_entrega['tipo_entrega']]['puntos'][] = array(
              "id_punto_entrega" => $tipo_entrega['id_punto_entrega'],
              "id_puntos_entrega" => $tipo_entrega['id_puntos_entrega'],
              "pais" => $tipo_entrega['pais'],
              "estado" => $tipo_entrega['estado'],
              "ciudad" => $tipo_entrega['ciudad'],
              "colonia" => $tipo_entrega['colonia'],
              "calle" => $tipo_entrega['calle'],
              "numero_exterior" => $tipo_entrega['numero_exterior'],
              "numero_interior" => $tipo_entrega['numero_interior'],
              "codigo_postal" => $tipo_entrega['codigo_postal'],
              "descripcion" => $tipo_entrega['descripcion'],
              "url_imagen" => $tipo_entrega['url_imagen'],
          );
        }
        $respuesta = array('error' => false, 'tipo' => 'success', 'mensaje' => 'resultados obtenidos con éxito', 'depurar' => $array_tipos_entrega);
      }
    }
    echo json_encode($respuesta);
  }

  public function esquema_actualizar_seguridad() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] == 1;
    $esquema = $this->modelo("SeguridadEsquema");
    echo json_encode($esquema->planActualizarSeguridad($ejecutar));
  }

  public function esquema_auditar_catalogo_erp() {
    $this->requerirPermiso("sistema.soporte");
    $esquema = $this->modelo("CatalogoErpEsquema");
    echo json_encode($esquema->auditarCatalogoErp());
  }

  public function esquema_actualizar_catalogo_erp() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && $_POST["ejecutar"] == 1;
    $esquema = $this->modelo("CatalogoErpEsquema");
    echo json_encode($esquema->planActualizarCatalogoErp($ejecutar));
  }

  public function esquema_auditar_notificaciones_erp() {
    $this->requerirPermiso("sistema.soporte");
    $esquema = $this->modelo("NotificacionesEsquema");
    echo json_encode($esquema->auditarNotificacionesErp());
  }

  public function esquema_actualizar_notificaciones_erp() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && $_POST["ejecutar"] == 1;
    $esquema = $this->modelo("NotificacionesEsquema");
    echo json_encode($esquema->planActualizarNotificacionesErp($ejecutar));
  }

  public function notificaciones_resumen_erp() {
    $this->requerirPermiso("notificaciones.ver");
    $modelo = $this->modelo("NotificacionesErp");
    echo json_encode($modelo->resumenUsuario(
      $this->usuarioActualId(),
      isset($_SESSION["permisos"]) ? $_SESSION["permisos"] : array()
    ));
  }

  public function notificaciones_listar_erp() {
    $this->requerirPermiso("notificaciones.ver");
    $modelo = $this->modelo("NotificacionesErp");
    echo json_encode($modelo->listarUsuario(
      $this->usuarioActualId(),
      isset($_SESSION["permisos"]) ? $_SESSION["permisos"] : array(),
      $_GET
    ));
  }

  public function notificacion_marcar_leida_erp() {
    $this->requerirPermiso("notificaciones.ver");
    $modelo = $this->modelo("NotificacionesErp");
    echo json_encode($modelo->marcarLeida(
      $this->usuarioActualId(),
      isset($_POST["id_notificacion"]) ? $_POST["id_notificacion"] : 0
    ));
  }

  public function notificaciones() {
    $this->requerirPermiso("notificaciones.ver");
    $this->vista("apps/erp/notificaciones/listado");
  }

  public function seguridad() {
    $this->requerirPermiso("seguridad.ver");
    $this->vista("apps/erp/seguridad/usuarios_roles");
  }

  public function seguridad_roles_listar() {
    $this->requerirPermiso("seguridad.ver");
    $seguridad = $this->modelo("SeguridadPermisos");
    echo json_encode($seguridad->listarRoles());
  }

  public function seguridad_rol_permisos_consultar() {
    $this->requerirPermiso("seguridad.ver");
    $seguridad = $this->modelo("SeguridadPermisos");
    echo json_encode($seguridad->consultarPermisosRol(
      isset($_GET["id_rol"]) ? intval($_GET["id_rol"]) : 0
    ));
  }

  public function seguridad_rol_permisos_guardar() {
    $this->requerirPermiso("seguridad.administrar");
    $id_rol = isset($_POST["id_rol"]) ? intval($_POST["id_rol"]) : 0;
    $permisos = isset($_POST["permisos"]) ? $_POST["permisos"] : array();
    if (is_string($permisos)) {
      $permisos = json_decode($permisos, true);
    }
    if ($id_rol <= 0 || !is_array($permisos)) {
      echo json_encode(array("error" => true, "tipo" => "warning",
        "mensaje" => "Falta rol o la seleccion de permisos no es valida"));
      return;
    }
    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->guardarPermisosRol($id_rol, $permisos);
    if ($respuesta["error"] == false) {
      $autorizacion = $seguridad->autorizacionUsuario($this->usuarioActualId());
      $_SESSION["roles"] = $autorizacion["roles"];
      $_SESSION["permisos"] = $autorizacion["permisos"];
    }
    SesionSeguridad::registrarAuditoria("seguridad", "actualizar_permisos_rol", array(
      "entidad" => "rol",
      "entidad_id" => $id_rol,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_antes" => isset($respuesta["depurar"]["permisos_antes"])
        ? $respuesta["depurar"]["permisos_antes"] : null,
      "datos_despues" => isset($respuesta["depurar"]["permisos_despues"])
        ? $respuesta["depurar"]["permisos_despues"] : array("permisos" => $permisos),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  public function seguridad_usuarios_roles_listar() {
    $this->requerirPermiso("seguridad.ver");
    $seguridad = $this->modelo("SeguridadPermisos");
    echo json_encode($seguridad->listarUsuariosRoles());
  }

  public function seguridad_auditoria_listar() {
    $this->requerirPermiso("auditoria.ver");
    $limite = isset($_GET["limite"]) ? intval($_GET["limite"]) : 100;
    $filtros = array(
      "modulo" => isset($_GET["modulo"]) ? trim($_GET["modulo"]) : "",
      "accion" => isset($_GET["accion"]) ? trim($_GET["accion"]) : "",
      "resultado" => isset($_GET["resultado"]) ? trim($_GET["resultado"]) : "",
      "usuario" => isset($_GET["usuario"]) ? trim($_GET["usuario"]) : "",
      "fecha_desde" => isset($_GET["fecha_desde"]) ? trim($_GET["fecha_desde"]) : "",
      "fecha_hasta" => isset($_GET["fecha_hasta"]) ? trim($_GET["fecha_hasta"]) : ""
    );
    $seguridad = $this->modelo("SeguridadPermisos");
    echo json_encode($seguridad->listarAuditoria($limite, $filtros));
  }

  public function seguridad_usuario_crear() {
    $this->requerirPermiso("seguridad.administrar");

    $nombres = isset($_POST["nombres"]) ? trim($_POST["nombres"]) : "";
    $apellidoPaterno = isset($_POST["apellido_paterno"]) ? trim($_POST["apellido_paterno"]) : "";
    $apellidoMaterno = isset($_POST["apellido_materno"]) ? trim($_POST["apellido_materno"]) : "";
    $celular = isset($_POST["celular"]) ? trim($_POST["celular"]) : "";
    $alias = isset($_POST["alias"]) ? trim($_POST["alias"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $telefono = isset($_POST["telefono"]) ? trim($_POST["telefono"]) : "";
    $nombreMostrar = isset($_POST["nombre_mostrar"]) ? trim($_POST["nombre_mostrar"]) : "";
    $areaDepartamento = isset($_POST["area_departamento"]) ? trim($_POST["area_departamento"]) : "";
    $puesto = isset($_POST["puesto"]) ? trim($_POST["puesto"]) : "";
    $telefonoSecundario = isset($_POST["telefono_secundario"]) ? trim($_POST["telefono_secundario"]) : "";
    $notasAdmin = isset($_POST["notas_admin"]) ? trim($_POST["notas_admin"]) : "";
    $contrasenia = isset($_POST["contrasenia"]) ? $_POST["contrasenia"] : "";
    $confirmarContrasenia = isset($_POST["confirmar_contrasenia"]) ? $_POST["confirmar_contrasenia"] : "";
    $idRol = isset($_POST["id_rol"]) ? intval($_POST["id_rol"]) : 0;

    if ($nombres === "" || $celular === "" || $contrasenia === "") {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "Nombre, celular y contrasena son obligatorios"));
      return;
    }
    if ($contrasenia !== $confirmarContrasenia) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "La contrasena no coincide"));
      return;
    }
    if (strlen($contrasenia) < 8) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "La contrasena debe tener al menos 8 caracteres"));
      return;
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->crearUsuarioInterno(array(
      "nombres" => $nombres,
      "apellido_paterno" => $apellidoPaterno,
      "apellido_materno" => $apellidoMaterno,
      "celular" => $celular,
      "alias" => $alias,
      "correo" => $correo,
      "telefono" => $telefono,
      "nombre_mostrar" => $nombreMostrar,
      "area_departamento" => $areaDepartamento,
      "puesto" => $puesto,
      "telefono_secundario" => $telefonoSecundario,
      "notas_admin" => $notasAdmin,
      "contrasenia_hash" => SesionSeguridad::hashContrasenia($contrasenia),
      "id_rol" => $idRol,
      "estatus" => 1
    ));

    SesionSeguridad::registrarAuditoria("seguridad", "crear_usuario", array(
      "entidad" => "usuario",
      "entidad_id" => isset($respuesta["depurar"]["id_usuario"]) ? $respuesta["depurar"]["id_usuario"] : null,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => array(
        "nombres" => $nombres,
        "apellido_paterno" => $apellidoPaterno,
        "apellido_materno" => $apellidoMaterno,
        "celular" => $celular,
        "alias" => $alias,
        "correo" => $correo,
        "telefono" => $telefono,
        "nombre_mostrar" => $nombreMostrar,
        "area_departamento" => $areaDepartamento,
        "puesto" => $puesto,
        "telefono_secundario" => $telefonoSecundario,
        "notas_admin" => $notasAdmin,
        "id_rol" => $idRol,
        "estatus" => 1
      ),
      "mensaje" => $respuesta["mensaje"]
    ));

    echo json_encode($respuesta);
  }

  public function seguridad_usuario_editar() {
    $this->requerirPermiso("seguridad.administrar");

    $idUsuario = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;
    $nombres = isset($_POST["nombres"]) ? trim($_POST["nombres"]) : "";
    $apellidoPaterno = isset($_POST["apellido_paterno"]) ? trim($_POST["apellido_paterno"]) : "";
    $apellidoMaterno = isset($_POST["apellido_materno"]) ? trim($_POST["apellido_materno"]) : "";
    $celular = isset($_POST["celular"]) ? trim($_POST["celular"]) : "";
    $alias = isset($_POST["alias"]) ? trim($_POST["alias"]) : "";
    $correo = isset($_POST["correo"]) ? trim($_POST["correo"]) : "";
    $telefono = isset($_POST["telefono"]) ? trim($_POST["telefono"]) : "";
    $nombreMostrar = isset($_POST["nombre_mostrar"]) ? trim($_POST["nombre_mostrar"]) : "";
    $areaDepartamento = isset($_POST["area_departamento"]) ? trim($_POST["area_departamento"]) : "";
    $puesto = isset($_POST["puesto"]) ? trim($_POST["puesto"]) : "";
    $telefonoSecundario = isset($_POST["telefono_secundario"]) ? trim($_POST["telefono_secundario"]) : "";
    $notasAdmin = isset($_POST["notas_admin"]) ? trim($_POST["notas_admin"]) : "";
    $estatus = isset($_POST["estatus"]) ? intval($_POST["estatus"]) : 1;

    if ($idUsuario <= 0 || $nombres === "" || $celular === "") {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "Usuario, nombre y celular son obligatorios"));
      return;
    }
    if ($idUsuario === $this->usuarioActualId() && $estatus !== 1) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "No puedes desactivar tu propio usuario"));
      return;
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->actualizarUsuarioInterno($idUsuario, array(
      "nombres" => $nombres,
      "apellido_paterno" => $apellidoPaterno,
      "apellido_materno" => $apellidoMaterno,
      "celular" => $celular,
      "alias" => $alias,
      "correo" => $correo,
      "telefono" => $telefono,
      "nombre_mostrar" => $nombreMostrar,
      "area_departamento" => $areaDepartamento,
      "puesto" => $puesto,
      "telefono_secundario" => $telefonoSecundario,
      "notas_admin" => $notasAdmin,
      "estatus" => $estatus
    ));

    if ($respuesta["error"] == false && $idUsuario === $this->usuarioActualId()) {
      $_SESSION["nombres"] = $nombres;
      $_SESSION["apellido_paterno"] = $apellidoPaterno;
      $_SESSION["apellido_materno"] = $apellidoMaterno;
    }

    SesionSeguridad::registrarAuditoria("seguridad", "editar_usuario", array(
      "entidad" => "usuario",
      "entidad_id" => $idUsuario,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_antes" => isset($respuesta["depurar"]["datos_antes"]) ? $respuesta["depurar"]["datos_antes"] : null,
      "datos_despues" => isset($respuesta["depurar"]["datos_despues"]) ? $respuesta["depurar"]["datos_despues"] : array(
        "nombres" => $nombres,
        "apellido_paterno" => $apellidoPaterno,
        "apellido_materno" => $apellidoMaterno,
        "celular" => $celular,
        "alias" => $alias,
        "correo" => $correo,
        "telefono" => $telefono,
        "nombre_mostrar" => $nombreMostrar,
        "area_departamento" => $areaDepartamento,
        "puesto" => $puesto,
        "telefono_secundario" => $telefonoSecundario,
        "notas_admin" => $notasAdmin,
        "estatus" => $estatus
      ),
      "mensaje" => $respuesta["mensaje"]
    ));

    echo json_encode($respuesta);
  }

  public function seguridad_usuario_rol_asignar() {
    $this->requerirPermiso("seguridad.administrar");
    $id_usuario = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;
    $id_rol = isset($_POST["id_rol"]) ? intval($_POST["id_rol"]) : 0;

    if ($id_usuario <= 0 || $id_rol <= 0) {
      echo json_encode(array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Falta usuario o rol para asignar",
        "depurar" => array("id_usuario" => $id_usuario, "id_rol" => $id_rol)
      ));
      return;
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->asignarRolUsuario($id_usuario, $id_rol);
    if ($respuesta["error"] == false && $id_usuario === $this->usuarioActualId()) {
      $autorizacion = $seguridad->autorizacionUsuario($id_usuario);
      $_SESSION["roles"] = $autorizacion["roles"];
      $_SESSION["permisos"] = $autorizacion["permisos"];
    }
    SesionSeguridad::registrarAuditoria("seguridad", "asignar_rol", array(
      "entidad" => "usuario",
      "entidad_id" => $id_usuario,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => array("id_rol" => $id_rol),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  public function seguridad_usuario_rol_quitar() {
    $this->requerirPermiso("seguridad.administrar");
    $id_usuario = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;
    $id_rol = isset($_POST["id_rol"]) ? intval($_POST["id_rol"]) : 0;

    if ($id_usuario <= 0 || $id_rol <= 0) {
      echo json_encode(array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Falta usuario o rol para retirar",
        "depurar" => array("id_usuario" => $id_usuario, "id_rol" => $id_rol)
      ));
      return;
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->quitarRolUsuario($id_usuario, $id_rol);
    if ($respuesta["error"] == false && $id_usuario === $this->usuarioActualId()) {
      $autorizacion = $seguridad->autorizacionUsuario($id_usuario);
      $_SESSION["roles"] = $autorizacion["roles"];
      $_SESSION["permisos"] = $autorizacion["permisos"];
    }
    SesionSeguridad::registrarAuditoria("seguridad", "quitar_rol", array(
      "entidad" => "usuario",
      "entidad_id" => $id_usuario,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_antes" => array("id_rol" => $id_rol),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

  public function seguridad_usuario_estatus() {
    $this->requerirPermiso("seguridad.administrar");
    $id_usuario = isset($_POST["id_usuario"]) ? intval($_POST["id_usuario"]) : 0;
    $estatus = isset($_POST["estatus"]) ? intval($_POST["estatus"]) : 0;

    if ($id_usuario <= 0) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "Falta usuario"));
      return;
    }
    if ($id_usuario === $this->usuarioActualId() && $estatus !== 1) {
      echo json_encode(array("error" => true, "tipo" => "warning", "mensaje" => "No puedes desactivar tu propio usuario"));
      return;
    }

    $seguridad = $this->modelo("SeguridadPermisos");
    $respuesta = $seguridad->actualizarEstatusUsuario($id_usuario, $estatus);
    SesionSeguridad::registrarAuditoria("seguridad", "actualizar_estatus_usuario", array(
      "entidad" => "usuario",
      "entidad_id" => $id_usuario,
      "resultado" => $respuesta["error"] ? "error" : "ok",
      "datos_despues" => array("estatus" => $estatus),
      "mensaje" => $respuesta["mensaje"]
    ));
    echo json_encode($respuesta);
  }

}
