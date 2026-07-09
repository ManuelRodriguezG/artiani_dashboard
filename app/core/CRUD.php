<?php

//include '../configuracion/alertas/AlertasSitema.php';
/**
 * Esta clase requiere la funcion error del archivo error.php
 * @author Israel Perez Villegas <iperez@bigbang.com.mx>
 *  */
abstract class MySqlDB {

  protected $datahost = array('error');
  private $error;
  private $tipo_de_base = 'mysql';

  protected function conectar() {
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    );
    try {
      return $this->datahost = new PDO($this->tipo_de_base . ':host=' . MYSQLHOST . ';dbname=' . MYSQLBASE, MYSQLUSER, MYSQLPASS, $options);
    } catch (PDOException $e) {
      $this->error = $e->getMessage();
      return null;
    }
  }

}

/**
 *
 * @author Israel Perez Villegas <iperez@bigbang.com.mx>
 * */
class CRUD extends MySqlDB {

  private $MySql;
  private $stmt;
  private $error = 0;
  private $alert = '';
  public $columnas = '*';
  public $columnasValores = '';
  private $updateColumnas = array();
  private $updateColumnasValores = array();
  public $tabla = '';
  public $where = '';
  public $and = '';
  public $join = '';
  public $innerJoin = '';
  public $leftJoin = '';
  public $rightJoin = '';
  public $orderBy = '';
  public $AscDesc = '';
  public $limit = '';

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function __construct() {
    $this->MySql = parent::conectar();
    return $this->MySql;
  }

  protected function getConexion() {
    return $this->MySql;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setOrderBy($orderBy): void {

    $this->orderBy = (!empty($orderBy)) ? 'ORDER BY ' . $orderBy . ' ' . $AscDesc : NULL;
    ;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getOrderBy() {
    return $this->orderBy;
  }

  /**
   * @param string $AscDesc Acendente o decendente ASC o DESC
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setAscDesc($AscDesc): void {
    $this->AscDesc = $AscDesc;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getAscDesc() {
    return $this->AscDesc;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getLimit() {
    return $this->limit;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setLimit($limit): void {
    $limit = (!empty($limit)) ? 'LIMIT ' . $limit : NULL;
    $this->limit = $limit;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getColumnas() {
    $columnas = str_replace("\r\n\t", '', $this->columnas);
    $columnas = str_replace("\n\t", '', $columnas);
    return $columnas;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getColumnasValores() {
    return $this->setColumnasValores;
  }

  public function getUpdateColumnas() {
    return $this->updateColumnas;
  }

  public function getUpdateColumnasValores() {
    return $this->updateColumnasValores;
  }

  public function setUpdateColumnas($updateColumnas): void {
    $this->updateColumnas = $updateColumnas;
  }

  public function setUpdateColumnasValores($updateColumnasValores): void {
    $this->updateColumnasValores = $updateColumnasValores;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * @param string $columnas String de columnas separadas por coma
   * */
  public function setColumnas($columnas): void {
    $this->columnas = $columnas;
  }

  /**
   * Esta funcion permite asignar valores a las columnas para ser usados en la 
   * funcion <insertar()>
   * 
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * @param string $valores Valores de las columnas a insertar
   * */
  public function setColumnasValores($valores): void {
    $this->setColumnasValores = $valores;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getTabla() {
    return $this->tabla;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setTabla($tabla): void {
    $this->tabla = $tabla;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getWhere() {
    return $this->where;
  }

  public function setWhere($where): void {
    $this->where = ' WHERE ' . $where;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getAnd() {
    return $this->and;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getJoin() {

    return $this->join;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setAnd($and): void {
    $and = (!empty($this->getAnd())) ? $this->getAnd() . ' AND ' . $and : ' AND ' . $and;
    $this->and = $and;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setJoin($join): void {
//    $join = (!empty($this->join)) ? $this->getJoin() . ' ' . $join : $join;
    $this->join = $join;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getInnerJoin() {
    return $this->innerJoin;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setInnerJoin($innerJoin): void {
    $innerJoin = (!empty($this->getJoin())) ? $this->getJoin() . ' INNER JOIN ' . $innerJoin : ' INNER JOIN ' . $innerJoin;
    $this->setJoin($innerJoin);
//    $this->innerJoin = $innerJoin;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getLeftJoin() {
    return $this->leftJoin;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setLeftJoin($leftJoin): void {
    $leftJoin = (!empty($this->getJoin())) ? $this->getJoin() . ' LEFT JOIN ' . $leftJoin : ' LEFT JOIN ' . $leftJoin;
//    $this->setJoin($leftJoin);
//    var_dump($leftJoin);
//    var_dump($this->getLeftJoin());
    $this->setJoin($leftJoin);
//    var_dump($this->getJoin());
//    $this->leftJoin = $leftJoin;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function getRightJoin() {
    return $this->rightJoin;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function setRightJoin($rightJoin): void {
    $rightJoin = (!empty($this->getJoin())) ? $this->getJoin() . ' RIGHT JOIN ' . $rightJoin : ' RIGHT JOIN ' . $rightJoin;
//    $this->setJoin($rightJoin);
    $this->setJoin($rightJoin);
//    $this->rightJoin = $rightJoin;
  }

  /**
   * Funcion para formatear la respuesta del crud
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function crudResponse($b_error, $s_tipo, $s_mensaje, $a_depurar = null) {
    $return = array('error' => $b_error, 'tipo' => $s_tipo, 'mensaje' => $s_mensaje, 'depurar' => $a_depurar);
    return $return;
  }

  /**
   * Funcion que permite insertar registros en la tabla espesificada
   * @param string $tabla Nombre de la tabla en la que se va a insertar
   * @param array $campos Campos a insertar en formato de array
   * @param array $valores Valores de los campos a insertar
   * @return array Array en formato de la funcion $this->crudResponse();
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * @date 2020-12-15
   * @since version 2.0 2020-12-16
   */
  public function insertar() {
//    $error = 0;
//    $alert = '';
//    $values = NULL;
//    $columns = NULL;
//    $noValues = 0;
//    $noColumns = 0;
//    $lastInsertId = 0;
//    $tabla = $this->getTabla();
//    $columnas = $this->getColumnas();
//    $columnasValores = $this->getColumnasValores();
//    $query = "INSERT INTO $tabla ($columnas) VALUES ($columnasValores);";
//    try {
//      $this->stmt = $this->MySql->prepare($query);
//      $this->stmt->execute();
//      $lastInsertId = $this->MySql->lastInsertId();
//      $return = $this->crudResponse(false, "success", "registros creados exitosamente", $lastInsertId);
//    } catch (Exception $e) {
//      $s_mensaje = $e->getMessage();
//      $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
//    } finally {
//      $this->limpiarVariables();
//    }
//    return $return;
    //////////////////////////////////////
    $error = 0;
    $alert = '';
    $columnas = $this->getColumnas();
    $valores = $this->getColumnasValores();
    $noCampos = sizeof($columnas);
    $noValores = sizeof($valores);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;
    $values = "";
    $fields = "";

    if ($noCampos == $noValores) {
      if ($noCampos >= 1 && $noValores >= 1) {

        $queryValores = $valores;
        foreach ($queryValores as $llave => $valor) {
          $noValues++;
          if ($noValues <= ($noValores - 1)) {
            $values .= "'$valor', ";
          } else {
            $values .= "'$valor'";
          }
        }
        $queryCampos = $columnas;
        foreach ($queryCampos as $key => $value) {
          $noColumns++;
          if ($noColumns <= ($noCampos - 1)) {
            $fields .= "$value, ";
          } else {
            $fields .= "$value";
          }
        }
        $query = "INSERT INTO {$this->getTabla()} ($fields) VALUES ($values);";
        try {
          $this->stmt = $this->MySql->prepare($query);
          $this->stmt->execute();
          $lastInsertId = $this->MySql->lastInsertId();
          $return = $this->crudResponse(false, "success", "registros creados exitosamente", $lastInsertId);
        } catch (Exception $e) {
          $s_mensaje = $e->getMessage();
          $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
        } finally {
          $this->limpiarVariables();
        }
      } else {
        $s_mensaje = "Datos insuficientes para realizar la operación $noValores";
        $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
      }
    } else {
      $s_mensaje = "MYSQLCRUD: La cantidad de campos o parametros no coincide. $query";
      $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
    }
    return $return;
  }

  /**
   * Funcion que permite editar los campos de una tabla
   * @param string $tabla Nomnre de la tabla donde se va a editar
   * @param array $columnas Se debe enviar el id al final del arreglo para editar
   * @param array $valores Se debe enviar valor del id al final del arreglo para editar
   * @return array Array en formato de la funcion $this->crudResponse();
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function update() {
    $error = 0;
    $alert = '';
    $columnas = $this->getColumnas();
    $valores = $this->getColumnasValores();
    $noCampos = sizeof($columnas);
    $noValores = sizeof($valores);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;

    if ($noCampos == $noValores) {
      if ($noCampos >= 1 && $noValores >= 1) {

        $queryUpdate = array_combine($columnas, $valores);
        foreach ($queryUpdate as $llave => $valor) {
          $noValues++;
          if ($noValues <= ($noValores - 1)) {
            $values .= "$llave = '$valor', ";
          } else {
            $values .= "$llave = '$valor'";
          }
        }
        $query = "UPDATE {$this->getTabla()} SET $values" . $this->getWhere() . " " . $this->getAnd();
//        var_dump($query);
        try {
          $this->stmt = $this->MySql->prepare($query);
          $this->stmt->execute();
          $return = $this->crudResponse(false, "success", "Registro actualizado correctamente");
        } catch (Exception $e) {
          $s_mensaje = $e->getMessage();
          $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
        } finally {
          $this->limpiarVariables();
        }
      } else {
        $s_mensaje = "Datos insuficientes para realizar la operación $noValores";
        $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
      }
    } else {
      $s_mensaje = "MYSQLCRUD: La cantidad de campos o parametros no coincide. $query";
      $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
    }
    return $return;
  }

  /**
   * Esta funcion permite eliminar registros de la base de datos
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function eliminar() {
    $error = 0;
    $alert = '';
    $tabla = $this->getTabla();
    $where = $this->getWhere();
    $and = $this->getAnd();

    $query = "DELETE FROM $tabla $where $and";
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $return = $this->crudResponse(false, 'success', 'Registro eliminado correctamente', null);
    } catch (Exception $e) {
      $alert = $e->getMessage();
      $return = $this->crudResponse(true, 'danger', $alert, $query);
    } finally {
      $this->limpiarVariables();
    }

    return $return;
  }

  /**
   * Está función permite seleccionar multiples registros de la base de datos
   * @param string $campos Campos separados por coma [,]
   * @param string $tabla Tabla de la base de datos
   * @return array Array en formato de respuesta del tipo $this->crudResponse();
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function listar() {
//    $campos = $this->getColumnas();
//    $tabla = $this->getTabla();
//    $join = $this->getJoin();
//    $where = $this->getWhere();
//    $and = $this->getAnd();
//    $order = $this->getOrderBy();
//    $AscDesc = $this->getAscDesc();
//    $limit = $this->getLimit();
//    $query = "SELECT $campos FROM $tabla $join $where $and $order $AscDesc $limit";
//    try {
//      $this->stmt = $this->MySql->prepare($query);
//      $this->stmt->execute();
//      $listar = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
//      $this->limpiarVariables();
//      if (empty($listar)) {
//        $return = $this->crudResponse(true, 'warning', 'El listado se ejecutó correctamente pero retornó vacío.', $query);
//      } else {
//        $return = $this->crudResponse(false, 'success', 'Listado ejecutado correctamente', $listar);
//      }
//    } catch (Exception $e) {
//      $alert = $e->getMessage();
//      $return = $this->crudResponse(true, 'danger', $alert, $query);
//    } finally {
//      $this->limpiarVariables();
//    }
//    return $return;
    /////////////////////////////////
    $error = 0;
    $alert = '';
    $columnas = $this->getColumnas();
    $noCampos = sizeof($columnas);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;
    $fields = "";

    $tabla = $this->getTabla();
    $join = $this->getJoin();
    $where = $this->getWhere();
    $and = $this->getAnd();

    $order = $this->getOrderBy();
    $AscDesc = $this->getAscDesc();
    $limit = $this->getLimit();

    if ($noCampos >= 1) {

      $queryColumnas = $columnas;
      foreach ($queryColumnas as $llave => $valor) {
        $noValues++;
        if ($noValues <= ($noCampos - 1)) {
          $fields .= "$valor, ";
        } else {
          $fields .= "$valor";
        }
      }
      $query = "SELECT $fields FROM $tabla $join $where $and $order $AscDesc $limit";
//      var_dump($query);
//      file_put_contents("query_listar.txt", $query.PHP_EOL, FILE_APPEND);
      try {
        $this->stmt = $this->MySql->prepare($query);
        $this->stmt->execute();
        $listar = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->limpiarVariables();
        if (empty($listar)) {
          $return = $this->crudResponse(true, 'warning', 'El listado se ejecutó correctamente pero retornó vacío.', $query);
        } else {
          $return = $this->crudResponse(false, 'success', 'Listado ejecutado correctamente', $listar);
        }
      } catch (Exception $e) {
        $s_mensaje = $e->getMessage();
        $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
      } finally {
        $this->limpiarVariables();
      }
    } else {
      $s_mensaje = "Datos insuficientes para realizar la operación $noValores";
      $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
    }

    return $return;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function buscarRegistro() {
//    $campos = $this->getColumnas();
//    $tabla = $this->getTabla();
//    $join = $this->getJoin();
//    $where = $this->getWhere();
//    $and = $this->getAnd();
//
//    $query = "SELECT $campos FROM $tabla $join $where $and";
//    try {
//      $this->stmt = $this->MySql->prepare($query);
//      $this->stmt->execute();
//      $buscar = $this->stmt->fetch(PDO::FETCH_ASSOC);
//      if (!empty($buscar)) {
//        $return = $this->crudResponse(false, 'success', 'Registro encontrado correctamente', $buscar);
//      } else {
//        $return = $this->crudResponse(true, 'warning', 'Busqueda realizada con cero resultados', $query);
//      }
//    } catch (Exception $e) {
//      $alert = $e->getMessage();
//      $return = $this->crudResponse(true, 'danger', $alert, $query);
//    } finally {
//      $this->limpiarVariables();
//    }
//    return $return;
    //////////////////////////
    $error = 0;
    $alert = '';
    $columnas = $this->getColumnas();
    $noCampos = sizeof($columnas);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;
    $fields = "";

    $tabla = $this->getTabla();
    $join = $this->getJoin();
    $where = $this->getWhere();
    $and = $this->getAnd();

    if ($noCampos >= 1) {

      $queryColumnas = $columnas;
      foreach ($queryColumnas as $llave => $valor) {
        $noValues++;

        if ($noValues <= ($noCampos - 1)) {
          $fields .= "$valor, ";
        } else {
          $fields .= "$valor";
        }
      }
      $query = "SELECT $fields FROM $tabla $join $where $and";
      try {
        $this->stmt = $this->MySql->prepare($query);
        $this->stmt->execute();
        $buscar = $this->stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($buscar)) {
          $return = $this->crudResponse(false, 'success', 'Registro encontrado correctamente', $buscar);
        } else {
          $return = $this->crudResponse(true, 'warning', 'Busqueda realizada con cero resultados', $query);
        }
      } catch (Exception $e) {
        $s_mensaje = $e->getMessage();
        $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
      } finally {
        $this->limpiarVariables();
      }
    } else {
      $s_mensaje = "Datos insuficientes para realizar la operación $noValores";
      $return = $this->crudResponse(true, "danger", $s_mensaje, $query);
    }

    return $return;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function maxId($campo, $tabla) {
    $query = "SELECT MAX($campo) AS maxid FROM $tabla;";
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $maxID = $this->stmt->fetch(PDO::FETCH_ASSOC);
      $return = $this->crudResponse(false, 'success', 'MaxID', $maxID);
    } catch (Exception $e) {
      $alert = $e->getMessage();
      $return = $this->crudResponse(true, 'danger', $alert, $query);
    }
    return $return;
  }

  /**
   *
   * @author Israel Perez Villegas <iperez@bigbang.com.mx>
   * */
  public function freeQuery($query) {
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $freeQuery = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      if (empty($freeQuery)) {
        $freeQuery = $this->crudResponse(false, 'success', 'El listado se ejecutó correctamente pero retornó vacío.', $query);
      } else {
        $freeQuery = $this->crudResponse(false, 'success', 'Listado correctamente', $listar);
      }
    } finally {
      $this->limpiarVariables();
    }
    $freeQuery = $this->crudResponse(false, 'success', 'FreeQuery ejecutado correctamente', $freeQuery);
    return $freeQuery;
  }

  public function limpiarVariables() {
    $this->columnas = '';
    $this->columnasValores = '';
    $this->tabla = '';
    $this->where = '';
    $this->and = '';
    $this->join = '';
    $this->innerJoin = '';
    $this->leftJoin = '';
    $this->rightJoin = '';
    $this->orderBy = '';
    $this->AscDesc = '';
    $this->limit = '';
  }

}
