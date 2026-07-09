<?php

require_once __DIR__ . '/CRUD.php';

class DBSchema extends MySqlDB {

  private $MySql;

  public function __construct() {
    $this->MySql = parent::conectar();
  }

  public function listarTablas() {
    $sql = "SELECT TABLE_NAME AS tabla
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :base
            ORDER BY TABLE_NAME";
    return $this->consulta($sql, array(':base' => MYSQLBASE));
  }

  public function tablaExiste($tabla) {
    if (!$this->identificadorValido($tabla)) {
      return false;
    }
    $sql = "SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla
            LIMIT 1";
    $respuesta = $this->consulta($sql, array(':base' => MYSQLBASE, ':tabla' => $tabla));
    return $respuesta['error'] === false && !empty($respuesta['depurar']);
  }

  public function describirTabla($tabla) {
    if (!$this->identificadorValido($tabla)) {
      return $this->respuesta(true, 'danger', 'Nombre de tabla invalido', array('tabla' => $tabla));
    }
    $sql = "SELECT COLUMN_NAME AS columna,
                   COLUMN_TYPE AS tipo,
                   IS_NULLABLE AS permite_null,
                   COLUMN_DEFAULT AS valor_default,
                   COLUMN_KEY AS llave,
                   EXTRA AS extra
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla
            ORDER BY ORDINAL_POSITION";
    return $this->consulta($sql, array(':base' => MYSQLBASE, ':tabla' => $tabla));
  }

  public function columnaExiste($tabla, $columna) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($columna)) {
      return false;
    }
    $sql = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla AND COLUMN_NAME = :columna
            LIMIT 1";
    $respuesta = $this->consulta($sql, array(':base' => MYSQLBASE, ':tabla' => $tabla, ':columna' => $columna));
    return $respuesta['error'] === false && !empty($respuesta['depurar']);
  }

  public function indiceExiste($tabla, $indice) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($indice)) {
      return false;
    }
    $sql = "SELECT INDEX_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla AND INDEX_NAME = :indice
            LIMIT 1";
    $respuesta = $this->consulta($sql, array(':base' => MYSQLBASE, ':tabla' => $tabla, ':indice' => $indice));
    return $respuesta['error'] === false && !empty($respuesta['depurar']);
  }

  public function crearTablaSiNoExiste($tabla, $columnasSql, $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", $ejecutar = false) {
    if (!$this->identificadorValido($tabla)) {
      return $this->respuesta(true, 'danger', 'Nombre de tabla invalido', array('tabla' => $tabla));
    }
    if ($this->tablaExiste($tabla)) {
      return $this->respuesta(false, 'info', 'La tabla ya existe', array('tabla' => $tabla, 'ejecutado' => false));
    }
    $sql = "CREATE TABLE `$tabla` (\n" . implode(",\n", $columnasSql) . "\n) $opciones;";
    return $this->ejecutarDDL($sql, $ejecutar);
  }

  public function agregarColumnaSiNoExiste($tabla, $columna, $definicionSql, $ejecutar = false) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($columna)) {
      return $this->respuesta(true, 'danger', 'Nombre de tabla o columna invalido', array('tabla' => $tabla, 'columna' => $columna));
    }
    if (!$this->tablaExiste($tabla)) {
      return $this->respuesta(true, 'warning', 'La tabla no existe', array('tabla' => $tabla));
    }
    if ($this->columnaExiste($tabla, $columna)) {
      return $this->respuesta(false, 'info', 'La columna ya existe', array('tabla' => $tabla, 'columna' => $columna, 'ejecutado' => false));
    }
    $sql = "ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicionSql;";
    return $this->ejecutarDDL($sql, $ejecutar);
  }

  public function agregarIndiceSiNoExiste($tabla, $indice, $definicionSql, $ejecutar = false) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($indice)) {
      return $this->respuesta(true, 'danger', 'Nombre de tabla o indice invalido', array('tabla' => $tabla, 'indice' => $indice));
    }
    if (!$this->tablaExiste($tabla)) {
      return $this->respuesta(true, 'warning', 'La tabla no existe', array('tabla' => $tabla));
    }
    if ($this->indiceExiste($tabla, $indice)) {
      return $this->respuesta(false, 'info', 'El indice ya existe', array('tabla' => $tabla, 'indice' => $indice, 'ejecutado' => false));
    }
    $sql = "ALTER TABLE `$tabla` ADD $definicionSql;";
    return $this->ejecutarDDL($sql, $ejecutar);
  }

  public function columnasIndice($tabla, $indice) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($indice)) {
      return array();
    }
    $sql = "SELECT COLUMN_NAME AS columna
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = :base AND TABLE_NAME = :tabla AND INDEX_NAME = :indice
            ORDER BY SEQ_IN_INDEX";
    $respuesta = $this->consulta($sql, array(':base' => MYSQLBASE, ':tabla' => $tabla, ':indice' => $indice));
    if ($respuesta['error'] || !is_array($respuesta['depurar'])) {
      return array();
    }
    return array_map(function ($fila) {
      return $fila['columna'];
    }, $respuesta['depurar']);
  }

  public function modificarColumna($tabla, $columna, $definicionSql, $ejecutar = false) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($columna) || !$this->tablaExiste($tabla) || !$this->columnaExiste($tabla, $columna)) {
      return $this->respuesta(true, 'warning', 'No se puede modificar la columna', array('tabla' => $tabla, 'columna' => $columna));
    }
    return $this->ejecutarDDL("ALTER TABLE `$tabla` MODIFY COLUMN `$columna` $definicionSql;", $ejecutar);
  }

  public function reemplazarIndice($tabla, $indice, $definicionSql, $ejecutar = false) {
    if (!$this->identificadorValido($tabla) || !$this->identificadorValido($indice) || !$this->tablaExiste($tabla) || !$this->indiceExiste($tabla, $indice)) {
      return $this->respuesta(true, 'warning', 'No se puede reemplazar el indice', array('tabla' => $tabla, 'indice' => $indice));
    }
    return $this->ejecutarDDL("ALTER TABLE `$tabla` DROP INDEX `$indice`, ADD $definicionSql;", $ejecutar);
  }

  private function consulta($sql, $params = array()) {
    if (!$this->MySql) {
      return $this->respuesta(true, 'danger', 'Conexion MySQL no disponible', $sql);
    }
    try {
      $stmt = $this->MySql->prepare($sql);
      $stmt->execute($params);
      return $this->respuesta(false, 'success', 'Consulta ejecutada correctamente', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return $this->respuesta(true, 'danger', $e->getMessage(), $sql);
    }
  }

  private function ejecutarDDL($sql, $ejecutar) {
    if (!$ejecutar) {
      return $this->respuesta(false, 'info', 'SQL generado sin ejecutar', array('sql' => $sql, 'ejecutado' => false));
    }
    if (!$this->MySql) {
      return $this->respuesta(true, 'danger', 'Conexion MySQL no disponible', array('sql' => $sql, 'ejecutado' => false));
    }
    try {
      $stmt = $this->MySql->prepare($sql);
      $stmt->execute();
      return $this->respuesta(false, 'success', 'DDL ejecutado correctamente', array('sql' => $sql, 'ejecutado' => true));
    } catch (Exception $e) {
      return $this->respuesta(true, 'danger', $e->getMessage(), $sql);
    }
  }

  private function identificadorValido($identificador) {
    return is_string($identificador) && preg_match('/^[a-zA-Z0-9_]+$/', $identificador);
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = null) {
    return array('error' => $error, 'tipo' => $tipo, 'mensaje' => $mensaje, 'depurar' => $depurar);
  }
}
