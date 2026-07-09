<?php

class SeguridadPermisos extends CRUD {

  private $tabla_roles = "sys_roles";
  private $tabla_permisos = "sys_permisos";
  private $tabla_roles_permisos = "sys_roles_permisos";
  private $tabla_usuarios_roles = "sys_usuarios_roles";
  private $tabla_auditoria_eventos = "sys_auditoria_eventos";
  private $tabla_usuarios = "sys_usuarios";
  private $columnasUsuariosCache = null;

  private function columnasUsuariosDisponibles() {
    if ($this->columnasUsuariosCache !== null) {
      return $this->columnasUsuariosCache;
    }
    $this->columnasUsuariosCache = array();
    try {
      $stmt = $this->getConexion()->query("SHOW COLUMNS FROM {$this->tabla_usuarios}");
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $columna) {
        $this->columnasUsuariosCache[$columna["Field"]] = true;
      }
    } catch (Exception $e) {
      $this->columnasUsuariosCache = array();
    }
    return $this->columnasUsuariosCache;
  }

  private function usuarioTieneColumna($columna) {
    $columnas = $this->columnasUsuariosDisponibles();
    return isset($columnas[$columna]);
  }

  public function listarRoles() {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT sr.id_rol, sr.rol, sr.descripcion, sr.estatus,
                            COUNT(srp.id_permiso) AS total_permisos
                            FROM {$this->tabla_roles} sr
                            LEFT JOIN {$this->tabla_roles_permisos} srp ON srp.id_rol=sr.id_rol
                            GROUP BY sr.id_rol, sr.rol, sr.descripcion, sr.estatus
                            ORDER BY sr.rol");
      $stmt->execute();
      return $this->crudResponse(false, "success", "Roles consultados correctamente", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function consultarPermisosRol($id_rol) {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT id_rol, rol, descripcion, estatus
                            FROM {$this->tabla_roles} WHERE id_rol=:id");
      $stmt->execute(array(":id" => intval($id_rol)));
      $rol = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$rol) {
        return $this->crudResponse(true, "warning", "Rol no encontrado");
      }
      $stmt = $db->prepare("SELECT sp.id_permiso, sp.modulo, sp.accion, sp.permiso,
                            sp.descripcion, sp.estatus,
                            CASE WHEN srp.id_rol_permiso IS NULL THEN 0 ELSE 1 END asignado
                            FROM {$this->tabla_permisos} sp
                            LEFT JOIN {$this->tabla_roles_permisos} srp
                              ON srp.id_permiso=sp.id_permiso AND srp.id_rol=:rol
                            WHERE sp.estatus=1
                            ORDER BY sp.modulo, sp.permiso");
      $stmt->execute(array(":rol" => intval($id_rol)));
      return $this->crudResponse(false, "success", "Permisos del rol consultados", array(
        "rol" => $rol,
        "permisos" => $stmt->fetchAll(PDO::FETCH_ASSOC)
      ));
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function guardarPermisosRol($id_rol, $permisos) {
    $id_rol = intval($id_rol);
    $permisos = is_array($permisos) ? array_values(array_unique(array_map("intval", $permisos))) : array();
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT rol FROM {$this->tabla_roles}
                            WHERE id_rol=:id AND estatus=1 FOR UPDATE");
      $stmt->execute(array(":id" => $id_rol));
      $rol = $stmt->fetchColumn();
      if (!$rol) {
        throw new Exception("Rol no encontrado o inactivo");
      }
      $stmt = $db->prepare("SELECT sp.id_permiso, sp.permiso
                            FROM {$this->tabla_roles_permisos} srp
                            INNER JOIN {$this->tabla_permisos} sp ON sp.id_permiso=srp.id_permiso
                            WHERE srp.id_rol=:rol
                            ORDER BY sp.permiso");
      $stmt->execute(array(":rol" => $id_rol));
      $permisosAntes = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($rol === "administrador_erp") {
        $stmt = $db->query("SELECT id_permiso, permiso FROM {$this->tabla_permisos}
                            WHERE permiso IN ('seguridad.ver','seguridad.administrar')");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $protegido) {
          if (!in_array(intval($protegido["id_permiso"]), $permisos, true)) {
            throw new Exception("El rol administrador_erp debe conservar los permisos de seguridad");
          }
        }
      }
      if (!empty($permisos)) {
        $marcadores = implode(",", array_fill(0, count($permisos), "?"));
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$this->tabla_permisos}
                              WHERE estatus=1 AND id_permiso IN ({$marcadores})");
        $stmt->execute($permisos);
        if (intval($stmt->fetchColumn()) !== count($permisos)) {
          throw new Exception("La seleccion contiene permisos no validos");
        }
      }
      $db->prepare("DELETE FROM {$this->tabla_roles_permisos} WHERE id_rol=:rol")
        ->execute(array(":rol" => $id_rol));
      $stmt = $db->prepare("INSERT INTO {$this->tabla_roles_permisos} (id_rol,id_permiso)
                            VALUES (:rol,:permiso)");
      foreach ($permisos as $id_permiso) {
        $stmt->execute(array(":rol" => $id_rol, ":permiso" => $id_permiso));
      }
      $db->commit();
      return $this->crudResponse(false, "success", "Permisos del rol actualizados", array(
        "id_rol" => $id_rol,
        "rol" => $rol,
        "total_permisos" => count($permisos),
        "permisos_antes" => $permisosAntes,
        "permisos_despues" => $permisos
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function listarUsuariosRoles() {
    try {
      $db = $this->getConexion();
      $camposPerfil = array("alias", "correo", "telefono", "nombre_mostrar", "area_departamento", "puesto", "telefono_secundario", "notas_admin");
      $selectPerfil = array();
      $groupPerfil = array();
      foreach ($camposPerfil as $campo) {
        if ($this->usuarioTieneColumna($campo)) {
          $selectPerfil[] = "su.{$campo}";
          $groupPerfil[] = "su.{$campo}";
        } else {
          $selectPerfil[] = "NULL AS {$campo}";
        }
      }
      $sql = "SELECT su.id_usuario,
                     su.nombres,
                     su.apellido_paterno,
                     su.apellido_materno,
                     su.celular,
                     su.estatus,
                     " . implode(",\n                     ", $selectPerfil) . ",
                     GROUP_CONCAT(sr.rol ORDER BY sr.rol SEPARATOR ', ') AS roles,
                     GROUP_CONCAT(CONCAT(sr.id_rol, ':', sr.rol) ORDER BY sr.rol SEPARATOR '|') AS roles_detalle
              FROM {$this->tabla_usuarios} su
              LEFT JOIN {$this->tabla_usuarios_roles} sur ON sur.id_usuario = su.id_usuario AND sur.estatus = 1
              LEFT JOIN {$this->tabla_roles} sr ON sr.id_rol = sur.id_rol AND sr.estatus = 1
              GROUP BY su.id_usuario, su.nombres, su.apellido_paterno, su.apellido_materno, su.celular, su.estatus" . (count($groupPerfil) > 0 ? ", " . implode(", ", $groupPerfil) : "") . "
              ORDER BY su.id_usuario";
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return $this->crudResponse(false, "success", "Usuarios y roles consultados correctamente", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function crearUsuarioInterno($datos) {
    $nombres = isset($datos["nombres"]) ? trim($datos["nombres"]) : "";
    $apellidoPaterno = isset($datos["apellido_paterno"]) ? trim($datos["apellido_paterno"]) : "";
    $apellidoMaterno = isset($datos["apellido_materno"]) ? trim($datos["apellido_materno"]) : "";
    $celular = isset($datos["celular"]) ? trim($datos["celular"]) : "";
    $contraseniaHash = isset($datos["contrasenia_hash"]) ? $datos["contrasenia_hash"] : "";
    $idRol = isset($datos["id_rol"]) ? intval($datos["id_rol"]) : 0;
    $estatus = isset($datos["estatus"]) ? intval($datos["estatus"]) : 1;
    $perfil = array(
      "alias" => isset($datos["alias"]) ? trim($datos["alias"]) : "",
      "correo" => isset($datos["correo"]) ? trim($datos["correo"]) : "",
      "telefono" => isset($datos["telefono"]) ? trim($datos["telefono"]) : "",
      "nombre_mostrar" => isset($datos["nombre_mostrar"]) ? trim($datos["nombre_mostrar"]) : "",
      "area_departamento" => isset($datos["area_departamento"]) ? trim($datos["area_departamento"]) : "",
      "puesto" => isset($datos["puesto"]) ? trim($datos["puesto"]) : "",
      "telefono_secundario" => isset($datos["telefono_secundario"]) ? trim($datos["telefono_secundario"]) : "",
      "notas_admin" => isset($datos["notas_admin"]) ? trim($datos["notas_admin"]) : ""
    );

    if ($nombres === "" || $celular === "" || $contraseniaHash === "") {
      return $this->crudResponse(true, "warning", "Faltan datos obligatorios del usuario");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();

      $stmt = $db->prepare("SELECT id_usuario FROM {$this->tabla_usuarios} WHERE celular=:celular LIMIT 1");
      $stmt->execute(array(":celular" => $celular));
      if ($stmt->fetchColumn()) {
        throw new Exception("Ya existe un usuario con ese celular");
      }

      foreach (array("alias", "correo") as $campoUnico) {
        if ($this->usuarioTieneColumna($campoUnico) && $perfil[$campoUnico] !== "") {
          $stmt = $db->prepare("SELECT id_usuario FROM {$this->tabla_usuarios} WHERE {$campoUnico}=:valor LIMIT 1");
          $stmt->execute(array(":valor" => $perfil[$campoUnico]));
          if ($stmt->fetchColumn()) {
            throw new Exception("Ya existe un usuario con ese " . ($campoUnico === "alias" ? "usuario" : "correo"));
          }
        }
      }

      if ($idRol > 0) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$this->tabla_roles} WHERE id_rol=:rol AND estatus=1");
        $stmt->execute(array(":rol" => $idRol));
        if (intval($stmt->fetchColumn()) <= 0) {
          throw new Exception("El rol inicial no existe o esta inactivo");
        }
      }

      $columnasInsert = array("nombres", "apellido_paterno", "apellido_materno", "celular", "contrasenia", "estatus");
      $paramsInsert = array(
        ":nombres" => $nombres,
        ":apellido_paterno" => $apellidoPaterno,
        ":apellido_materno" => $apellidoMaterno,
        ":celular" => $celular,
        ":contrasenia" => $contraseniaHash,
        ":estatus" => $estatus
      );
      foreach ($perfil as $campo => $valor) {
        if ($this->usuarioTieneColumna($campo)) {
          $columnasInsert[] = $campo;
          $paramsInsert[":" . $campo] = $valor;
        }
      }
      $marcadoresInsert = array_map(function ($campo) { return ":" . $campo; }, $columnasInsert);
      $stmt = $db->prepare("INSERT INTO {$this->tabla_usuarios}
        (" . implode(", ", $columnasInsert) . ")
        VALUES (" . implode(", ", $marcadoresInsert) . ")");
      $stmt->execute($paramsInsert);
      $idUsuario = intval($db->lastInsertId());

      if ($idRol > 0) {
        $stmt = $db->prepare("INSERT INTO {$this->tabla_usuarios_roles} (id_usuario, id_rol, estatus)
          VALUES (:id_usuario, :id_rol, 1)
          ON DUPLICATE KEY UPDATE estatus=1, fecha_actualizacion=CURRENT_TIMESTAMP");
        $stmt->execute(array(":id_usuario" => $idUsuario, ":id_rol" => $idRol));
      }

      $db->commit();
      return $this->crudResponse(false, "success", "Usuario creado correctamente", array(
        "id_usuario" => $idUsuario,
        "id_rol" => $idRol,
        "estatus" => $estatus,
        "perfil" => $perfil
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function actualizarUsuarioInterno($id_usuario, $datos) {
    $id_usuario = intval($id_usuario);
    $nombres = isset($datos["nombres"]) ? trim($datos["nombres"]) : "";
    $apellidoPaterno = isset($datos["apellido_paterno"]) ? trim($datos["apellido_paterno"]) : "";
    $apellidoMaterno = isset($datos["apellido_materno"]) ? trim($datos["apellido_materno"]) : "";
    $celular = isset($datos["celular"]) ? trim($datos["celular"]) : "";
    $estatus = isset($datos["estatus"]) ? intval($datos["estatus"]) : 1;
    $perfil = array(
      "alias" => isset($datos["alias"]) ? trim($datos["alias"]) : "",
      "correo" => isset($datos["correo"]) ? trim($datos["correo"]) : "",
      "telefono" => isset($datos["telefono"]) ? trim($datos["telefono"]) : "",
      "nombre_mostrar" => isset($datos["nombre_mostrar"]) ? trim($datos["nombre_mostrar"]) : "",
      "area_departamento" => isset($datos["area_departamento"]) ? trim($datos["area_departamento"]) : "",
      "puesto" => isset($datos["puesto"]) ? trim($datos["puesto"]) : "",
      "telefono_secundario" => isset($datos["telefono_secundario"]) ? trim($datos["telefono_secundario"]) : "",
      "notas_admin" => isset($datos["notas_admin"]) ? trim($datos["notas_admin"]) : ""
    );

    if ($id_usuario <= 0 || $nombres === "" || $celular === "") {
      return $this->crudResponse(true, "warning", "Faltan datos obligatorios del usuario");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();

      $camposPerfilSelect = array();
      foreach (array_keys($perfil) as $campo) {
        if ($this->usuarioTieneColumna($campo)) {
          $camposPerfilSelect[] = $campo;
        }
      }
      $stmt = $db->prepare("SELECT id_usuario, nombres, apellido_paterno, apellido_materno, celular, estatus" . (count($camposPerfilSelect) > 0 ? ", " . implode(", ", $camposPerfilSelect) : "") . "
                            FROM {$this->tabla_usuarios}
                            WHERE id_usuario=:id FOR UPDATE");
      $stmt->execute(array(":id" => $id_usuario));
      $antes = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$antes) {
        throw new Exception("Usuario no encontrado");
      }

      $stmt = $db->prepare("SELECT id_usuario FROM {$this->tabla_usuarios}
                            WHERE celular=:celular AND id_usuario<>:id LIMIT 1");
      $stmt->execute(array(":celular" => $celular, ":id" => $id_usuario));
      if ($stmt->fetchColumn()) {
        throw new Exception("Ya existe otro usuario con ese celular");
      }

      foreach (array("alias", "correo") as $campoUnico) {
        if ($this->usuarioTieneColumna($campoUnico) && $perfil[$campoUnico] !== "") {
          $stmt = $db->prepare("SELECT id_usuario FROM {$this->tabla_usuarios}
                                WHERE {$campoUnico}=:valor AND id_usuario<>:id LIMIT 1");
          $stmt->execute(array(":valor" => $perfil[$campoUnico], ":id" => $id_usuario));
          if ($stmt->fetchColumn()) {
            throw new Exception("Ya existe otro usuario con ese " . ($campoUnico === "alias" ? "usuario" : "correo"));
          }
        }
      }

      if ($estatus !== 1) {
        $stmt = $db->prepare("SELECT COUNT(*)
                              FROM {$this->tabla_usuarios_roles} sur
                              INNER JOIN {$this->tabla_roles} sr
                                ON sr.id_rol=sur.id_rol AND sr.estatus=1
                              WHERE sur.id_usuario=:usuario
                                AND sur.estatus=1
                                AND sr.rol='administrador_erp'");
        $stmt->execute(array(":usuario" => $id_usuario));
        if (intval($stmt->fetchColumn()) > 0) {
          $stmt = $db->prepare("SELECT COUNT(DISTINCT sur.id_usuario)
                                FROM {$this->tabla_usuarios_roles} sur
                                INNER JOIN {$this->tabla_roles} sr
                                  ON sr.id_rol=sur.id_rol AND sr.estatus=1
                                INNER JOIN {$this->tabla_usuarios} su
                                  ON su.id_usuario=sur.id_usuario AND su.estatus=1
                                WHERE sur.estatus=1 AND sr.rol='administrador_erp'");
          $stmt->execute();
          if (intval($stmt->fetchColumn()) <= 1) {
            throw new Exception("Debe conservarse al menos un usuario administrador activo");
          }
        }
      }

      $sets = array(
        "nombres=:nombres",
        "apellido_paterno=:apellido_paterno",
        "apellido_materno=:apellido_materno",
        "celular=:celular",
        "estatus=:estatus"
      );
      $paramsUpdate = array(
        ":nombres" => $nombres,
        ":apellido_paterno" => $apellidoPaterno,
        ":apellido_materno" => $apellidoMaterno,
        ":celular" => $celular,
        ":estatus" => $estatus,
        ":id" => $id_usuario
      );
      foreach ($perfil as $campo => $valor) {
        if ($this->usuarioTieneColumna($campo)) {
          $sets[] = "{$campo}=:" . $campo;
          $paramsUpdate[":" . $campo] = $valor;
        }
      }
      $stmt = $db->prepare("UPDATE {$this->tabla_usuarios}
                            SET " . implode(", ", $sets) . "
                            WHERE id_usuario=:id");
      $stmt->execute($paramsUpdate);

      $db->commit();
      return $this->crudResponse(false, "success", "Usuario actualizado correctamente", array(
        "id_usuario" => $id_usuario,
        "datos_antes" => $antes,
        "datos_despues" => array_merge(array(
          "nombres" => $nombres,
          "apellido_paterno" => $apellidoPaterno,
          "apellido_materno" => $apellidoMaterno,
          "celular" => $celular,
          "estatus" => $estatus
        ), $perfil)
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function asignarRolUsuario($id_usuario, $id_rol) {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO {$this->tabla_usuarios_roles} (id_usuario, id_rol, estatus)
                            VALUES (:id_usuario, :id_rol, 1)
                            ON DUPLICATE KEY UPDATE estatus = 1, fecha_actualizacion = CURRENT_TIMESTAMP");
      $stmt->execute(array(
        ':id_usuario' => intval($id_usuario),
        ':id_rol' => intval($id_rol)
      ));
      return $this->crudResponse(false, "success", "Rol asignado correctamente");
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function quitarRolUsuario($id_usuario, $id_rol) {
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("SELECT rol FROM {$this->tabla_roles} WHERE id_rol=:id FOR UPDATE");
      $stmt->execute(array(":id" => intval($id_rol)));
      $rol = $stmt->fetchColumn();
      if ($rol === "administrador_erp") {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT sur.id_usuario)
                              FROM {$this->tabla_usuarios_roles} sur
                              INNER JOIN {$this->tabla_usuarios} su
                                ON su.id_usuario=sur.id_usuario AND su.estatus=1
                              WHERE sur.id_rol=:rol AND sur.estatus=1");
        $stmt->execute(array(":rol" => intval($id_rol)));
        if (intval($stmt->fetchColumn()) <= 1) {
          throw new Exception("Debe conservarse al menos un usuario administrador activo");
        }
      }
      $stmt = $db->prepare("UPDATE {$this->tabla_usuarios_roles}
                            SET estatus = 0, fecha_actualizacion = CURRENT_TIMESTAMP
                            WHERE id_usuario = :id_usuario AND id_rol = :id_rol");
      $stmt->execute(array(
        ':id_usuario' => intval($id_usuario),
        ':id_rol' => intval($id_rol)
      ));
      $db->commit();
      return $this->crudResponse(false, "success", "Rol retirado correctamente");
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function actualizarEstatusUsuario($id_usuario, $estatus) {
    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      if (intval($estatus) !== 1) {
        $stmt = $db->prepare("SELECT COUNT(*)
                              FROM {$this->tabla_usuarios_roles} sur
                              INNER JOIN {$this->tabla_roles} sr
                                ON sr.id_rol=sur.id_rol AND sr.estatus=1
                              WHERE sur.id_usuario=:usuario
                                AND sur.estatus=1
                                AND sr.rol='administrador_erp'");
        $stmt->execute(array(":usuario" => intval($id_usuario)));
        if (intval($stmt->fetchColumn()) > 0) {
          $stmt = $db->prepare("SELECT COUNT(DISTINCT sur.id_usuario)
                                FROM {$this->tabla_usuarios_roles} sur
                                INNER JOIN {$this->tabla_roles} sr
                                  ON sr.id_rol=sur.id_rol AND sr.estatus=1
                                INNER JOIN {$this->tabla_usuarios} su
                                  ON su.id_usuario=sur.id_usuario AND su.estatus=1
                                WHERE sur.estatus=1 AND sr.rol='administrador_erp'");
          $stmt->execute();
          if (intval($stmt->fetchColumn()) <= 1) {
            throw new Exception("Debe conservarse al menos un usuario administrador activo");
          }
        }
      }
      $stmt = $db->prepare("UPDATE {$this->tabla_usuarios}
                            SET estatus = :estatus
                            WHERE id_usuario = :id_usuario");
      $stmt->execute(array(
        ':estatus' => intval($estatus) === 1 ? 1 : 0,
        ':id_usuario' => intval($id_usuario)
      ));
      $db->commit();
      return $this->crudResponse(false, "success", "Estatus de usuario actualizado correctamente");
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function usuarioTienePermiso($id_usuario, $permiso) {
    if (defined('PROGRAMADOR') && isset($_SESSION['id_rango']) && intval($_SESSION['id_rango']) === intval(PROGRAMADOR)) {
      return true;
    }

    try {
      $db = $this->getConexion();
      $sql = "SELECT sp.id_permiso
              FROM {$this->tabla_usuarios_roles} sur
              INNER JOIN {$this->tabla_roles} sr ON sr.id_rol = sur.id_rol AND sr.estatus = 1
              INNER JOIN {$this->tabla_roles_permisos} srp ON srp.id_rol = sr.id_rol
              INNER JOIN {$this->tabla_permisos} sp ON sp.id_permiso = srp.id_permiso AND sp.estatus = 1
              WHERE sur.id_usuario = :id_usuario
                AND sur.estatus = 1
                AND sp.permiso = :permiso
              LIMIT 1";
      $stmt = $db->prepare($sql);
      $stmt->execute(array(
        ':id_usuario' => intval($id_usuario),
        ':permiso' => $permiso
      ));
      return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return false;
    }
  }

  public function autorizacionUsuario($id_usuario) {
    try {
      $db = $this->getConexion();
      $sql = "SELECT DISTINCT sr.rol, sp.permiso
              FROM {$this->tabla_usuarios_roles} sur
              INNER JOIN {$this->tabla_roles} sr ON sr.id_rol = sur.id_rol AND sr.estatus = 1
              LEFT JOIN {$this->tabla_roles_permisos} srp ON srp.id_rol = sr.id_rol
              LEFT JOIN {$this->tabla_permisos} sp ON sp.id_permiso = srp.id_permiso AND sp.estatus = 1
              WHERE sur.id_usuario = :id_usuario AND sur.estatus = 1
              ORDER BY sr.rol, sp.permiso";
      $stmt = $db->prepare($sql);
      $stmt->execute(array(':id_usuario' => intval($id_usuario)));

      $roles = array();
      $permisos = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        if (!empty($fila['rol'])) {
          $roles[$fila['rol']] = true;
        }
        if (!empty($fila['permiso'])) {
          $permisos[$fila['permiso']] = true;
        }
      }

      return array(
        'roles' => array_keys($roles),
        'permisos' => array_keys($permisos)
      );
    } catch (Exception $e) {
      return array('roles' => array(), 'permisos' => array());
    }
  }

  public function registrarEvento($evento) {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO {$this->tabla_auditoria_eventos}
        (id_usuario, modulo, accion, entidad, entidad_id, resultado, ip, user_agent, datos_antes, datos_despues, mensaje)
        VALUES (:id_usuario, :modulo, :accion, :entidad, :entidad_id, :resultado, :ip, :user_agent, :datos_antes, :datos_despues, :mensaje)");
      $stmt->execute(array(
        ':id_usuario' => isset($evento['id_usuario']) ? intval($evento['id_usuario']) : null,
        ':modulo' => isset($evento['modulo']) ? $evento['modulo'] : 'sistema',
        ':accion' => isset($evento['accion']) ? $evento['accion'] : 'evento',
        ':entidad' => isset($evento['entidad']) ? $evento['entidad'] : null,
        ':entidad_id' => isset($evento['entidad_id']) ? $evento['entidad_id'] : null,
        ':resultado' => isset($evento['resultado']) ? $evento['resultado'] : 'ok',
        ':ip' => isset($evento['ip']) ? $evento['ip'] : null,
        ':user_agent' => isset($evento['user_agent']) ? $evento['user_agent'] : null,
        ':datos_antes' => isset($evento['datos_antes']) ? json_encode($evento['datos_antes'], JSON_UNESCAPED_UNICODE) : null,
        ':datos_despues' => isset($evento['datos_despues']) ? json_encode($evento['datos_despues'], JSON_UNESCAPED_UNICODE) : null,
        ':mensaje' => isset($evento['mensaje']) ? $evento['mensaje'] : null
      ));
      return $this->crudResponse(false, "success", "Evento de auditoria registrado", $db->lastInsertId());
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function listarAuditoria($limite = 100, $filtros = array()) {
    try {
      $db = $this->getConexion();
      $limite = max(1, min(500, intval($limite)));
      $where = array();
      $params = array();

      if (!empty($filtros['modulo'])) {
        $where[] = "sae.modulo = :modulo";
        $params[':modulo'] = $filtros['modulo'];
      }
      if (!empty($filtros['accion'])) {
        $where[] = "sae.accion LIKE :accion";
        $params[':accion'] = "%" . $filtros['accion'] . "%";
      }
      if (!empty($filtros['resultado'])) {
        $where[] = "sae.resultado = :resultado";
        $params[':resultado'] = $filtros['resultado'];
      }
      if (!empty($filtros['usuario'])) {
        $where[] = "(CONCAT_WS(' ', su.nombres, su.apellido_paterno, su.apellido_materno) LIKE :usuario OR sae.id_usuario = :usuario_id)";
        $params[':usuario'] = "%" . $filtros['usuario'] . "%";
        $params[':usuario_id'] = intval($filtros['usuario']);
      }
      if (!empty($filtros['fecha_desde'])) {
        $where[] = "sae.fecha_registro >= :fecha_desde";
        $params[':fecha_desde'] = $filtros['fecha_desde'] . " 00:00:00";
      }
      if (!empty($filtros['fecha_hasta'])) {
        $where[] = "sae.fecha_registro <= :fecha_hasta";
        $params[':fecha_hasta'] = $filtros['fecha_hasta'] . " 23:59:59";
      }

      $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";
      $sql = "SELECT sae.id_auditoria_evento,
                     sae.id_usuario,
                     CONCAT_WS(' ', su.nombres, su.apellido_paterno) AS usuario,
                     sae.modulo,
                     sae.accion,
                     sae.entidad,
                     sae.entidad_id,
                     sae.resultado,
                     sae.ip,
                     sae.mensaje,
                     sae.fecha_registro
              FROM {$this->tabla_auditoria_eventos} sae
              LEFT JOIN {$this->tabla_usuarios} su ON su.id_usuario = sae.id_usuario
              {$whereSql}
              ORDER BY sae.id_auditoria_evento DESC
              LIMIT {$limite}";
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      return $this->crudResponse(false, "success", "Auditoria consultada correctamente", $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }
}
