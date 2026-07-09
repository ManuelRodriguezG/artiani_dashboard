<?php
//include '../config/mysql.php';
include 'error.php';
/**
 * Esta clase requiere la funcion error del archivo error.php
 *  */
abstract class MySqlDB {

  protected $datahost;
  private $error;
  private $tipo_de_base = 'mysql';

  protected function conectar() {
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    );
    try {
      return $this->datahost = new PDO($this->tipo_de_base . ':host=' . MYSQLHOST . ';dbname=' . MYSQLBASE, MYSQLUSER, MYSQLPASS, $options);
    } catch (PDOException $e) {
         file_put_contents('pruebaCRUD.log',$e);
      return $this->datahost->error = $e->getMessage();
    }
  }

}

class CRUD extends MySqlDB {

  private $MySql;
  private $stmt;
  private $error = 0;
  private $alert = '';

  public function __construct() {
    $this->MySql = parent::conectar();
    return $this->MySql;
  }

  public function insert($tabla, $campos = array(), $valores = array()) {
    $error = 0;
    $alert = '';
    $noCampos = count($campos);
    $noValores = count($valores);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;
    $lastInsertId = 0;

    if ($noCampos == $noValores) {
      foreach ($campos as $campo) {
        $noColumns++;
        ($noColumns <= ($noCampos - 1)) ? $columns .= "$campo," : $columns .= "$campo";
      }
      foreach ($valores as $valor) {
        $noValues++;
        ($noValues <= ($noValores - 1)) ? $values .= "'$valor'," : $values .= "'$valor'";
      }
      $query = "INSERT INTO $tabla ($columns) VALUES ($values);";
      file_put_contents("insercionVisit.log",$query.PHP_EOL,FILE_APPEND);
      try {
        $this->stmt = $this->MySql->prepare($query);
        $this->stmt->execute();
        $lastInsertId = $this->MySql->lastInsertId();
        $return = error(false, "success", "registros creados exitosamente", $lastInsertId);
      } catch (Exception $e) {
        $s_mensaje = $e->getMessage();
        $return = error(true, "danger", $s_mensaje, $query);
      }
    } else {
      $s_mensaje = "la cantidad de campos o parametros no coincide";
      $return = error(true, "danger", $s_mensaje, null);
    }
    return $return;
  }

  public function update($tabla, $campos = array(), $valores = array(),$and = NULL) {
    $error = 0;
    $alert = '';
    $noCampos = count($campos);
    $noValores = count($valores);
    $values = NULL;
    $columns = NULL;
    $noValues = 0;
    $noColumns = 0;
    if($and == NULL){
        $and = '';
    }

    if ($noCampos == $noValores) {
      if ($noCampos >= 2 && $noValores >= 2) {
        $idCampo = end($campos);
        $idValor = end($valores);
        $queryUpdate = array_combine($campos, $valores);
        array_pop($queryUpdate);
        foreach ($queryUpdate as $llave => $valor) {
          $noValues++;
          if ($noValues <= ($noValores - 2)) {
            $values .= "$llave = '$valor', ";
          } else {
            $values .= "$llave = '$valor'";
          }
        }
        $query = "UPDATE $tabla SET $values  WHERE $idCampo = '$idValor' $and";
      //  file_put_contents('insrt.log',json_encode($query).PHP_EOL,FILE_APPEND);
        try {
          $this->stmt = $this->MySql->prepare($query);
          $this->stmt->execute();
          $return = error(false, "Success", "Registro actualzado correctamente", null);
        } catch (Exception $e) {
          $s_mensaje = $e->getMessage();
          $return = error(true, "danger", $s_mensaje, $query);
        }
      } else {
        $s_mensaje = "Datos insuficientes para realizar la operación";
        $return = error(true, "danger", $s_mensaje, $query);
      }
    } else {
      $s_mensaje = "la cantidad de campos o parametros no coincide";
      $return = error(true, "danger", $s_mensaje, $query);
    }
    return $return;
  }

  public function delete($tabla, $campo, $valor) {
    $error = 0;
    $alert = '';
    if (!empty($campo) && !empty($valor)) {
      $query = "DELETE FROM $tabla WHERE $campo='$valor';";
      try {
        $this->stmt = $this->MySql->prepare($query);
        $this->stmt->execute();
        $return = error(false, 'success', 'Registro eliminado correctamente', null);
      } catch (Exception $e) {
        $alert = $e->getMessage();
        $return = error(true, 'danger', $alert, $query);
      }
    } else {
      $alert = "Datos insuficientes para realizar la operación";
      $return = error(true, 'danger', $alert, $query);
    }
    return $return;
  }

  public function listar($campos, $tabla, $where = NULL, $orderBy = NULL, $AscDesc = NULL, $limit = NULL) {
    $order = !empty($orderBy) ? 'ORDER BY ' . $orderBy . ' ' . $AscDesc : NULL;
    $limit = !empty($limit) ? 'LIMIT ' . $limit : NULL;
    $query = "SELECT $campos FROM $tabla $where $order $limit";
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $listar = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
      if (empty($listar)) {
        $return = error(false, 'success', 'El listado se ejecutó correctamente pero retornó vacío.', $query);
      } else {
        $return = error(false, 'success', 'Listado correctamente', $listar);
      }
    } catch (Exception $e) {
      $alert = $e->getMessage();
      $return = error(true, 'danger', $alert, $query);
    }
    return $return;
  }

  public function buscarRegistro($campo, $tabla, $elemento, $busqueda, $and = null) {
    $aBusqueda = addslashes($busqueda);
    $query = "SELECT $campo FROM $tabla WHERE $elemento = '$aBusqueda' $and";
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $buscar = $this->stmt->fetch(PDO::FETCH_ASSOC);
      if (!empty($buscar)) {
        $return = error(false, 'success', 'Registro encontrado correctamente', $buscar);
      } else {
        $return = error(true, 'warning', 'Busqueda realizada con cero resultados', null);
      }
    } catch (Exception $e) {
      $alert = $e->getMessage();
      $return = error(true, 'danger', $alert, $query);
    }
    return $return;
  }

  public function maxId($campo, $tabla) {
    $query = "SELECT MAX($campo) AS maxid FROM $tabla;";
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $maxID = $this->stmt->fetch(PDO::FETCH_ASSOC);
      $return = error(false, 'success', 'MaxID', $maxID);
    } catch (Exception $e) {
      $alert = $e->getMessage();
      $return = error(true, 'danger', $alert, $query);
    }
    return $return;
  }

  public function freeQuery($query) {
    try {
      $this->stmt = $this->MySql->prepare($query);
      $this->stmt->execute();
      $freeQuery = $this->stmt->fetchAll(PDO::FETCH_ASSOC);
      
    } catch (Exception $e) {
        $err='error';
        $s_mensaje = $e->getMessage();
      if (empty($freeQuery)) {
        $freeQuery = error(false, 'success', 'empty', $query);
      } else {
        $freeQuery = error(false, 'success', 'Listado correctamente', $listar);
      }
    }
   
    
     if (!isset($err)) {
        if (empty($freeQuery)) {
          $freeQuery = error(false, 'success', 'empty', $query);
        } else {
          $freeQuery = error(false, 'success', 'Listado correctamente', $freeQuery);
        }
    }
    //file_put_contents('insrt.log',json_encode($freeQuery).PHP_EOL,FILE_APPEND);
    return $freeQuery;
  }
  
  public function estructuraFunciones(){
      $code = '
        public function insert($tabla, $campos = array(), $valores = array())<br>
        public function update($tabla, $campos = array(), $valores = array(),$and = NULL)<br>
        public function delete($tabla, $campo, $valor)<br>
        public function listar($campos, $tabla, $where = NULL, $orderBy = NULL, $AscDesc = NULL, $limit = NULL)<br>
        public function buscarRegistro($campo, $tabla, $elemento, $busqueda, $and = null)<br>
        public function maxId($campo, $tabla)<br>
        public function freeQuery($query)';
        return $code;
  }

}
