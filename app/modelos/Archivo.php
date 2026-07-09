<?php

class Archivo extends CRUD {

  private $clave;
  private $descripcion;
  private $precio_unitario;
  private $existencia;
  private $total;
  private $tabla_inventario_sicar = "erp_inventario_sicar";

  public function carga_inventario_sicar() {
    $campos = array(
        "clave",
        "descripcion",
        "existencia",
        "total"
    );
    $valores = array(
        $this->getClave(),
        $this->getDescripcion(),
        $this->getExistencia(),
        $this->getTotal()
    );
    $this->setColumnas($campos);
    $this->setColumnasValores($valores);
    $this->setTabla($this->tabla_inventario_sicar);
    return $this->insertar();
  }
  
  public function truncar_tabla(){
    
  }

  public function getClave() {
    return $this->clave;
  }

  public function getDescripcion() {
    return $this->descripcion;
  }

  public function getPrecio_unitario() {
    return $this->precio_unitario;
  }

  public function getExistencia() {
    return $this->existencia;
  }

  public function getTotal() {
    return $this->total;
  }

  public function setClave($clave): void {
    $this->clave = $clave;
  }

  public function setDescripcion($descripcion): void {
    $this->descripcion = $descripcion;
  }

  public function setPrecio_unitario($precio_unitario): void {
    $this->precio_unitario = $precio_unitario;
  }

  public function setExistencia($existencia): void {
    $this->existencia = $existencia;
  }

  public function setTotal($total): void {
    $this->total = $total;
  }

}
