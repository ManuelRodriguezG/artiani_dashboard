<?php

class Links extends CRUD {

  private $nombre;
  private $medio_contacto;
  private $numero_contacto;
  private $codigo;
  private $asesor;
  private $tabla_sys_links = "sys_links";

  public function registrar_link() {
    $campos = array(
        "asesor",
        "nombre",
        "medio_contacto",
        "numero_contacto",
        "codigo"
    );
    $valores = array(
        $this->getAsesor(),
        $this->getNombre(),
        $this->getMedio_contacto(),
        $this->getNumero_contacto(),
        $this->getCodigo()
    );
    $this->setTabla($this->tabla_sys_links);
    $this->setColumnas($campos);
    $this->setColumnasValores($valores);
    return $this->insertar();
  }

  public function getAsesor() {
    return $this->asesor;
  }

  public function setAsesor($asesor): void {
    $this->asesor = $asesor;
  }

  public function getNombre() {
    return $this->nombre;
  }

  public function getMedio_contacto() {
    return $this->medio_contacto;
  }

  public function getNumero_contacto() {
    return $this->numero_contacto;
  }

  public function getCodigo() {
    return $this->codigo;
  }

  public function setNombre($nombre): void {
    $this->nombre = $nombre;
  }

  public function setMedio_contacto($medio_contacto): void {
    $this->medio_contacto = $medio_contacto;
  }

  public function setNumero_contacto($numero_contacto): void {
    $this->numero_contacto = $numero_contacto;
  }

  public function setCodigo($codigo): void {
    $this->codigo = $codigo;
  }

}
