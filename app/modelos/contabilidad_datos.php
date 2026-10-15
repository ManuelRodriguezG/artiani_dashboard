<?php

class ContabilidadDatos extends CRUD {

  private $tabla = "erp_contabilidad_cierres";

  public function __construct() {
    parent::__construct();
    $this->asegurarTabla();
  }

  private function conexion() {
    return $this->getConexion();
  }

  private function asegurarTabla() {
    $db = $this->conexion();
    if (!$db) {
      return;
    }
    $sql = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierres` (
      `id_cierre` BIGINT NOT NULL AUTO_INCREMENT,
      `id_usuario` INT NOT NULL,
      `periodo` CHAR(7) NOT NULL,
      `cuenta_base` VARCHAR(180) NULL,
      `datos_json` LONGTEXT NOT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_cierre`),
      UNIQUE KEY `idx_erp_contabilidad_usuario_periodo` (`id_usuario`, `periodo`),
      KEY `idx_erp_contabilidad_periodo` (`periodo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    try {
      $db->exec($sql);
    } catch (Exception $e) {
      // Las respuestas de las operaciones informan la falla de conexion/DDL.
    }
  }

  public function listar($idUsuario) {
    $db = $this->conexion();
    if (!$db) {
      return $this->respuesta(true, "danger", "No hay conexion con la base de datos", array());
    }
    try {
      $stmt = $db->prepare("SELECT id_cierre AS id, periodo, cuenta_base, datos_json, fecha_actualizacion AS actualizado
        FROM `erp_contabilidad_cierres` WHERE id_usuario = :id_usuario
        ORDER BY periodo DESC, fecha_actualizacion DESC");
      $stmt->execute(array(":id_usuario" => intval($idUsuario)));
      $cierres = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $datos = json_decode($fila["datos_json"], true);
        $movimientos = is_array($datos) && !empty($datos["movimientos"]) && is_array($datos["movimientos"]) ? $datos["movimientos"] : array();
        $cuentas = array();
        foreach ($movimientos as $movimiento) {
          $cuenta = isset($movimiento["cuenta"]) ? trim((string) $movimiento["cuenta"]) : "Cuenta sin nombre";
          $cuentas[$cuenta] = true;
        }
        $cierres[] = array(
          "id" => intval($fila["id"]),
          "periodo" => $fila["periodo"],
          "cuenta_base" => $fila["cuenta_base"],
          "actualizado" => $fila["actualizado"],
          "movimientos_total" => count($movimientos),
          "cuentas_total" => count($cuentas),
          "cfdis_total" => is_array($datos) && !empty($datos["cfdis"]) && is_array($datos["cfdis"]) ? count($datos["cfdis"]) : 0
        );
      }
      return $this->respuesta(false, "success", "Cierres consultados", array("cierres" => $cierres));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No pude consultar los cierres contables", array("detalle" => $e->getMessage()));
    }
  }

  public function consultar($idUsuario, $idCierre) {
    $db = $this->conexion();
    if (!$db) {
      return $this->respuesta(true, "danger", "No hay conexion con la base de datos", array());
    }
    try {
      $stmt = $db->prepare("SELECT id_cierre AS id, periodo, cuenta_base, datos_json, fecha_actualizacion AS actualizado
        FROM `erp_contabilidad_cierres` WHERE id_usuario = :id_usuario AND id_cierre = :id LIMIT 1");
      $stmt->execute(array(":id_usuario" => intval($idUsuario), ":id" => intval($idCierre)));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(true, "warning", "No encontre el cierre solicitado", array());
      }
      $datos = json_decode($fila["datos_json"], true);
      if (!is_array($datos)) {
        return $this->respuesta(true, "danger", "El cierre guardado no tiene un formato valido", array());
      }
      $datos["id_cierre_bd"] = intval($fila["id"]);
      $datos["actualizado"] = $fila["actualizado"];
      return $this->respuesta(false, "success", "Cierre consultado", array("cierre" => $datos));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No pude abrir el cierre contable", array("detalle" => $e->getMessage()));
    }
  }

  public function guardar($idUsuario, $datos) {
    $db = $this->conexion();
    if (!$db || !is_array($datos)) {
      return $this->respuesta(true, "danger", "No hay datos validos para guardar", array());
    }
    $periodo = isset($datos["periodo"]) ? trim((string) $datos["periodo"]) : "";
    if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
      return $this->respuesta(true, "warning", "El cierre necesita un periodo mensual valido", array());
    }
    $datos["periodo"] = $periodo;
    $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($datosJson === false) {
      return $this->respuesta(true, "danger", "No pude preparar la informacion del cierre", array());
    }
    try {
      $stmt = $db->prepare("INSERT INTO `erp_contabilidad_cierres` (id_usuario, periodo, cuenta_base, datos_json)
        VALUES (:id_usuario, :periodo, :cuenta_base, :datos_json)
        ON DUPLICATE KEY UPDATE cuenta_base = VALUES(cuenta_base), datos_json = VALUES(datos_json), fecha_actualizacion = CURRENT_TIMESTAMP");
      $stmt->execute(array(
        ":id_usuario" => intval($idUsuario),
        ":periodo" => $periodo,
        ":cuenta_base" => isset($datos["cuenta_base"]) ? (string) $datos["cuenta_base"] : "",
        ":datos_json" => $datosJson
      ));
      $stmt = $db->prepare("SELECT id_cierre AS id, fecha_actualizacion AS actualizado FROM `erp_contabilidad_cierres`
        WHERE id_usuario = :id_usuario AND periodo = :periodo LIMIT 1");
      $stmt->execute(array(":id_usuario" => intval($idUsuario), ":periodo" => $periodo));
      return $this->respuesta(false, "success", "Cierre guardado en la base de datos", $stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No pude guardar el cierre contable", array("detalle" => $e->getMessage()));
    }
  }

  public function eliminar($idUsuario, $idCierre) {
    $db = $this->conexion();
    if (!$db) {
      return $this->respuesta(true, "danger", "No hay conexion con la base de datos", array());
    }
    try {
      $stmt = $db->prepare("DELETE FROM `erp_contabilidad_cierres` WHERE id_usuario = :id_usuario AND id_cierre = :id");
      $stmt->execute(array(":id_usuario" => intval($idUsuario), ":id" => intval($idCierre)));
      return $this->respuesta(false, "success", "Cierre eliminado de la base de datos", array("afectados" => $stmt->rowCount()));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No pude eliminar el cierre contable", array("detalle" => $e->getMessage()));
    }
  }

  private function respuesta($error, $tipo, $mensaje, $depurar) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
