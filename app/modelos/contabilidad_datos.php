<?php

class ContabilidadDatos extends CRUD {

  private $dbContabilidad = null;

  public function __construct() {
    $this->dbContabilidad = $this->crearConexion();
    $this->asegurarTabla();
  }

  private function conexion() {
    return $this->dbContabilidad;
  }

  private function crearConexion() {
    try {
      $db = new PDO(
        "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE,
        MYSQLUSER,
        MYSQLPASS,
        array(
          PDO::ATTR_PERSISTENT => false,
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_TIMEOUT => 10
        )
      );
      $db->exec("SET NAMES utf8mb4");
      return $db;
    } catch (Throwable $e) {
      return null;
    }
  }

  private function asegurarTabla() {
    $db = $this->conexion();
    if (!$db) {
      return;
    }
    $tablas = array();
    $tablas[] = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierres` (
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
    $tablas[] = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierre_estados` (
      `id_detalle` BIGINT NOT NULL AUTO_INCREMENT,
      `id_cierre` BIGINT NOT NULL,
      `id_usuario` INT NOT NULL,
      `clave_registro` VARCHAR(190) NOT NULL,
      `nombre` VARCHAR(255) NULL,
      `periodo` CHAR(7) NULL,
      `datos_json` LONGTEXT NOT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_detalle`),
      KEY `idx_contabilidad_estados_cierre` (`id_cierre`),
      KEY `idx_contabilidad_estados_periodo` (`periodo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $tablas[] = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierre_movimientos` (
      `id_detalle` BIGINT NOT NULL AUTO_INCREMENT,
      `id_cierre` BIGINT NOT NULL,
      `id_usuario` INT NOT NULL,
      `clave_registro` VARCHAR(190) NOT NULL,
      `fecha` DATE NULL,
      `cuenta` VARCHAR(180) NULL,
      `tipo_movimiento` VARCHAR(80) NULL,
      `actividad` VARCHAR(80) NULL,
      `monto` DECIMAL(18,6) NULL,
      `datos_json` LONGTEXT NOT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_detalle`),
      KEY `idx_contabilidad_mov_cierre` (`id_cierre`),
      KEY `idx_contabilidad_mov_fecha` (`fecha`),
      KEY `idx_contabilidad_mov_cuenta` (`cuenta`),
      KEY `idx_contabilidad_mov_tipo` (`tipo_movimiento`),
      KEY `idx_contabilidad_mov_actividad` (`actividad`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $tablas[] = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierre_cfdis` (
      `id_detalle` BIGINT NOT NULL AUTO_INCREMENT,
      `id_cierre` BIGINT NOT NULL,
      `id_usuario` INT NOT NULL,
      `clave_registro` VARCHAR(190) NOT NULL,
      `uuid` VARCHAR(80) NULL,
      `fecha` DATE NULL,
      `emisor` VARCHAR(255) NULL,
      `categoria` VARCHAR(100) NULL,
      `actividad` VARCHAR(80) NULL,
      `cuenta_pago` VARCHAR(180) NULL,
      `total` DECIMAL(18,6) NULL,
      `tratamiento` VARCHAR(80) NULL,
      `estatus_relacion` VARCHAR(80) NULL,
      `datos_json` LONGTEXT NOT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_detalle`),
      KEY `idx_contabilidad_cfdis_cierre` (`id_cierre`),
      KEY `idx_contabilidad_cfdis_uuid` (`uuid`),
      KEY `idx_contabilidad_cfdis_fecha` (`fecha`),
      KEY `idx_contabilidad_cfdis_categoria` (`categoria`),
      KEY `idx_contabilidad_cfdis_cuenta` (`cuenta_pago`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $tablas[] = "CREATE TABLE IF NOT EXISTS `erp_contabilidad_cierre_auxiliares` (
      `id_detalle` BIGINT NOT NULL AUTO_INCREMENT,
      `id_cierre` BIGINT NOT NULL,
      `id_usuario` INT NOT NULL,
      `clave_registro` VARCHAR(190) NOT NULL,
      `clave_cfdi` VARCHAR(80) NULL,
      `fecha` DATE NULL,
      `cuenta` VARCHAR(180) NULL,
      `monto` DECIMAL(18,6) NULL,
      `datos_json` LONGTEXT NOT NULL,
      `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_detalle`),
      KEY `idx_contabilidad_aux_cierre` (`id_cierre`),
      KEY `idx_contabilidad_aux_cfdi` (`clave_cfdi`),
      KEY `idx_contabilidad_aux_fecha` (`fecha`),
      KEY `idx_contabilidad_aux_cuenta` (`cuenta`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    try {
      foreach ($tablas as $sql) {
        $db->exec($sql);
      }
      $this->migrarDetallesExistentes($db);
    } catch (Exception $e) {
      // Las respuestas de las operaciones informan la falla de conexion/DDL.
    }
  }

  private function migrarDetallesExistentes($db) {
    $filas = $db->query("SELECT id_cierre, id_usuario, datos_json FROM erp_contabilidad_cierres")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($filas as $fila) {
      $datos = json_decode($fila["datos_json"], true);
      if (!is_array($datos)) {
        continue;
      }
      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_contabilidad_cierre_movimientos WHERE id_cierre = :id_cierre");
      $stmt->execute(array(":id_cierre" => intval($fila["id_cierre"])));
      $hayMovimientos = intval($stmt->fetchColumn()) > 0;
      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_contabilidad_cierre_cfdis WHERE id_cierre = :id_cierre");
      $stmt->execute(array(":id_cierre" => intval($fila["id_cierre"])));
      $hayCfdis = intval($stmt->fetchColumn()) > 0;
      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_contabilidad_cierre_estados WHERE id_cierre = :id_cierre");
      $stmt->execute(array(":id_cierre" => intval($fila["id_cierre"])));
      $hayEstados = intval($stmt->fetchColumn()) > 0;
      if (!$hayMovimientos || !$hayCfdis || !$hayEstados) {
        $this->sincronizarDetalles($db, intval($fila["id_cierre"]), intval($fila["id_usuario"]), $datos);
      }
    }
  }

  public function listarCierres($idUsuario) {
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

  public function consultarCierre($idUsuario, $idCierre) {
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

  public function guardarCierre($idUsuario, $datos) {
    $db = $this->conexion();
    if (!$db || !is_array($datos)) {
      return $this->respuesta(true, "danger", "No hay datos validos para guardar", array());
    }
    $periodo = isset($datos["periodo"]) ? trim((string) $datos["periodo"]) : "";
    if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
      return $this->respuesta(true, "warning", "El cierre necesita un periodo mensual valido", array());
    }
    $datos["periodo"] = $periodo;
    $datosJson = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($datosJson === false) {
      return $this->respuesta(true, "danger", "No pude preparar la informacion del cierre", array());
    }
    try {
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO `erp_contabilidad_cierres` (id_usuario, periodo, cuenta_base, datos_json)
        VALUES (:id_usuario, :periodo, :cuenta_base, :datos_json)
        ON DUPLICATE KEY UPDATE cuenta_base = VALUES(cuenta_base), datos_json = VALUES(datos_json), fecha_actualizacion = CURRENT_TIMESTAMP");
      $stmt->execute(array(
        ":id_usuario" => intval($idUsuario),
        ":periodo" => $periodo,
        ":cuenta_base" => isset($datos["cuenta_base"]) ? (string) $datos["cuenta_base"] : "",
        ":datos_json" => $datosJson
      ));
      $stmt = $db->prepare("SELECT id_cierre FROM `erp_contabilidad_cierres`
        WHERE id_usuario = :id_usuario AND periodo = :periodo LIMIT 1");
      $stmt->execute(array(":id_usuario" => intval($idUsuario), ":periodo" => $periodo));
      $idCierre = intval($stmt->fetchColumn());
      if (!$idCierre) {
        throw new Exception("No pude obtener el identificador del cierre guardado");
      }
      $this->sincronizarDetalles($db, $idCierre, intval($idUsuario), $datos);
      $db->commit();
      $stmt = $db->prepare("SELECT id_cierre AS id, fecha_actualizacion AS actualizado FROM `erp_contabilidad_cierres`
        WHERE id_usuario = :id_usuario AND periodo = :periodo LIMIT 1");
      $stmt->execute(array(":id_usuario" => intval($idUsuario), ":periodo" => $periodo));
      return $this->respuesta(false, "success", "Cierre y detalle guardados en la base de datos", $stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", "No pude guardar el cierre contable", array("detalle" => $e->getMessage()));
    }
  }

  public function reconstruirDetalles($idCierre) {
    $db = $this->conexion();
    if (!$db) {
      return $this->respuesta(true, "danger", "No hay conexion con la base de datos", array());
    }
    try {
      $stmt = $db->prepare("SELECT id_usuario, datos_json FROM erp_contabilidad_cierres WHERE id_cierre = :id LIMIT 1");
      $stmt->execute(array(":id" => intval($idCierre)));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(true, "warning", "No encontre el cierre solicitado", array());
      }
      $datos = json_decode($fila["datos_json"], true);
      if (!is_array($datos)) {
        return $this->respuesta(true, "danger", "El cierre guardado no tiene un formato valido", array());
      }
      $db->beginTransaction();
      $this->sincronizarDetalles($db, intval($idCierre), intval($fila["id_usuario"]), $datos);
      $db->commit();
      return $this->respuesta(false, "success", "Detalle reconstruido", array(
        "estados" => isset($datos["estados_cuenta"]) && is_array($datos["estados_cuenta"]) ? count($datos["estados_cuenta"]) : 0,
        "movimientos" => isset($datos["movimientos"]) && is_array($datos["movimientos"]) ? count($datos["movimientos"]) : 0,
        "cfdis" => isset($datos["cfdis"]) && is_array($datos["cfdis"]) ? count($datos["cfdis"]) : 0
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", "No pude reconstruir el detalle", array("detalle" => $e->getMessage()));
    }
  }

  /**
   * Version IA: GPT-5 | Fecha: 2026-11-07
   * Proposito: persistir el detalle operativo del cierre en tablas consultables,
   * conservando tambien el JSON completo como respaldo de compatibilidad.
   */
  private function sincronizarDetalles($db, $idCierre, $idUsuario, $datos) {
    $tablas = array(
      "erp_contabilidad_cierre_estados",
      "erp_contabilidad_cierre_movimientos",
      "erp_contabilidad_cierre_cfdis",
      "erp_contabilidad_cierre_auxiliares"
    );
    foreach ($tablas as $tabla) {
      $stmt = $db->prepare("DELETE FROM `" . $tabla . "` WHERE id_cierre = :id_cierre");
      $stmt->execute(array(":id_cierre" => intval($idCierre)));
    }

    $insertEstado = $db->prepare("INSERT INTO erp_contabilidad_cierre_estados
      (id_cierre,id_usuario,clave_registro,nombre,periodo,datos_json)
      VALUES (:id_cierre,:id_usuario,:clave_registro,:nombre,:periodo,:datos_json)");
    foreach ((isset($datos["estados_cuenta"]) && is_array($datos["estados_cuenta"]) ? $datos["estados_cuenta"] : array()) as $indice => $estado) {
      if (!is_array($estado)) { continue; }
      $json = json_encode($estado, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
      $insertEstado->execute(array(
        ":id_cierre" => intval($idCierre), ":id_usuario" => intval($idUsuario),
        ":clave_registro" => $this->claveRegistro($estado, "estado-" . $indice),
        ":nombre" => $this->texto($estado, array("nombre", "archivo", "cuenta")),
        ":periodo" => $this->texto($estado, array("periodo", "mes")), ":datos_json" => $json
      ));
    }

    $insertMovimiento = $db->prepare("INSERT INTO erp_contabilidad_cierre_movimientos
      (id_cierre,id_usuario,clave_registro,fecha,cuenta,tipo_movimiento,actividad,monto,datos_json)
      VALUES (:id_cierre,:id_usuario,:clave_registro,:fecha,:cuenta,:tipo_movimiento,:actividad,:monto,:datos_json)");
    $insertAuxiliar = $db->prepare("INSERT INTO erp_contabilidad_cierre_auxiliares
      (id_cierre,id_usuario,clave_registro,clave_cfdi,fecha,cuenta,monto,datos_json)
      VALUES (:id_cierre,:id_usuario,:clave_registro,:clave_cfdi,:fecha,:cuenta,:monto,:datos_json)");
    foreach ((isset($datos["movimientos"]) && is_array($datos["movimientos"]) ? $datos["movimientos"] : array()) as $indice => $movimiento) {
      if (!is_array($movimiento)) { continue; }
      $json = json_encode($movimiento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
      $fecha = $this->fechaSql($this->texto($movimiento, array("fecha")));
      $cuenta = $this->texto($movimiento, array("cuenta"));
      $monto = $this->numero($movimiento, array("monto", "importe", "total"));
      $clave = $this->claveRegistro($movimiento, "movimiento-" . $indice);
      $insertMovimiento->execute(array(
        ":id_cierre" => intval($idCierre), ":id_usuario" => intval($idUsuario), ":clave_registro" => $clave,
        ":fecha" => $fecha, ":cuenta" => $cuenta, ":tipo_movimiento" => $this->texto($movimiento, array("movimiento", "tipo_movimiento", "tipo")),
        ":actividad" => $this->texto($movimiento, array("actividad", "categoria")), ":monto" => $monto, ":datos_json" => $json
      ));
      if ($this->esAuxiliar($movimiento)) {
        $insertAuxiliar->execute(array(
          ":id_cierre" => intval($idCierre), ":id_usuario" => intval($idUsuario), ":clave_registro" => $clave,
          ":clave_cfdi" => $this->texto($movimiento, array("cfdi_uuid", "cfdi")), ":fecha" => $fecha,
          ":cuenta" => $cuenta, ":monto" => $monto, ":datos_json" => $json
        ));
      }
    }

    $insertCfdi = $db->prepare("INSERT INTO erp_contabilidad_cierre_cfdis
      (id_cierre,id_usuario,clave_registro,uuid,fecha,emisor,categoria,actividad,cuenta_pago,total,tratamiento,estatus_relacion,datos_json)
      VALUES (:id_cierre,:id_usuario,:clave_registro,:uuid,:fecha,:emisor,:categoria,:actividad,:cuenta_pago,:total,:tratamiento,:estatus_relacion,:datos_json)");
    foreach ((isset($datos["cfdis"]) && is_array($datos["cfdis"]) ? $datos["cfdis"] : array()) as $indice => $cfdi) {
      if (!is_array($cfdi)) { continue; }
      $json = json_encode($cfdi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
      $insertCfdi->execute(array(
        ":id_cierre" => intval($idCierre), ":id_usuario" => intval($idUsuario),
        ":clave_registro" => $this->claveRegistro($cfdi, "cfdi-" . $indice),
        ":uuid" => $this->texto($cfdi, array("uuid", "folio")), ":fecha" => $this->fechaSql($this->texto($cfdi, array("fecha", "pago_fecha"))),
        ":emisor" => $this->texto($cfdi, array("emisor", "rfc_emisor")), ":categoria" => $this->texto($cfdi, array("categoria", "clasificacion")),
        ":actividad" => $this->texto($cfdi, array("actividad")), ":cuenta_pago" => $this->texto($cfdi, array("cuenta_pago")),
        ":total" => $this->numero($cfdi, array("pago_monto", "total")), ":tratamiento" => $this->texto($cfdi, array("tratamiento")),
        ":estatus_relacion" => $this->texto($cfdi, array("estatus_relacion", "relacion")), ":datos_json" => $json
      ));
    }
  }

  private function claveRegistro($dato, $alternativa) {
    foreach (array("id", "uuid", "clave", "folio") as $campo) {
      if (isset($dato[$campo]) && trim((string) $dato[$campo]) !== "") { return substr(trim((string) $dato[$campo]), 0, 190); }
    }
    return substr($alternativa, 0, 190);
  }

  private function texto($dato, $campos) {
    foreach ($campos as $campo) {
      if (isset($dato[$campo]) && trim((string) $dato[$campo]) !== "") { return trim((string) $dato[$campo]); }
    }
    return "";
  }

  private function numero($dato, $campos) {
    foreach ($campos as $campo) {
      if (isset($dato[$campo]) && $dato[$campo] !== "") { return is_numeric($dato[$campo]) ? (float) $dato[$campo] : null; }
    }
    return null;
  }

  private function fechaSql($fecha) {
    $fecha = trim((string) $fecha);
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $fecha, $m)) { return $m[1]; }
    return null;
  }

  private function esAuxiliar($dato) {
    return isset($dato["origen"]) && $dato["origen"] === "cfdi_auxiliar";
  }

  public function eliminarCierre($idUsuario, $idCierre) {
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
