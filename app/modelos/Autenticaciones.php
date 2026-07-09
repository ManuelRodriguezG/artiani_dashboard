<?php

class Autenticaciones extends CRUD {

  private $nombres;
  private $apellido_paterno;
  private $apellido_materno;
  private $celular;
  private $contrasenia;
  private $estatus;
  private $tabla_usuarios = "sys_usuarios";

  public function crear_registro() {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("INSERT INTO {$this->tabla_usuarios}
        (nombres, apellido_paterno, apellido_materno, celular, contrasenia, estatus)
        VALUES (:nombres, :apellido_paterno, :apellido_materno, :celular, :contrasenia, :estatus)");
      $stmt->execute(array(
        ':nombres' => $this->getNombres(),
        ':apellido_paterno' => $this->getApellido_paterno(),
        ':apellido_materno' => $this->getApellido_materno(),
        ':celular' => $this->getCelular(),
        ':contrasenia' => $this->getContrasenia(),
        ':estatus' => $this->getEstatus()
      ));
      return $this->crudResponse(false, "success", "registros creados exitosamente", $db->lastInsertId());
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function consultar_usuario() {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT * FROM {$this->tabla_usuarios} WHERE celular = :celular LIMIT 1");
      $stmt->execute(array(':celular' => $this->getCelular()));
      $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!empty($usuario)) {
        return $this->crudResponse(false, "success", "Registro encontrado correctamente", $usuario);
      }
      return $this->crudResponse(true, "warning", "Busqueda realizada con cero resultados");
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function actualizar_contrasenia($id_usuario, $contrasenia) {
    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("UPDATE {$this->tabla_usuarios} SET contrasenia = :contrasenia WHERE id_usuario = :id_usuario");
      $stmt->execute(array(
        ':contrasenia' => $contrasenia,
        ':id_usuario' => intval($id_usuario)
      ));
      return $this->crudResponse(false, "success", "Contrasenia actualizada correctamente");
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  public function getNombres() {
    return $this->nombres;
  }

  public function getApellido_paterno() {
    return $this->apellido_paterno;
  }

  public function getApellido_materno() {
    return $this->apellido_materno;
  }

  public function getCelular() {
    return $this->celular;
  }

  public function getContrasenia() {
    return $this->contrasenia;
  }

  public function getEstatus() {
    return $this->estatus;
  }

  public function setNombres($nombres): void {
    $this->nombres = $nombres;
  }

  public function setApellido_paterno($apellido_paterno): void {
    $this->apellido_paterno = $apellido_paterno;
  }

  public function setApellido_materno($apellido_materno): void {
    $this->apellido_materno = $apellido_materno;
  }

  public function setCelular($celular): void {
    $this->celular = $celular;
  }

  public function setContrasenia($contrasenia): void {
    $this->contrasenia = $contrasenia;
  }

  public function setEstatus($estatus): void {
    $this->estatus = $estatus;
  }

}
