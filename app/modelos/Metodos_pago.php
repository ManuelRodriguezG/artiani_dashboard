<?php

class Metodos_pago extends CRUD {

  private $tabla_metodos_pago = "erp_metodos_pago";

  public function metodos_listar() {
    $this->limpiarVariables();
    $this->setColumnas(array(
        "*"
    ));
    $this->setTabla($this->tabla_metodos_pago);

    return $this->listar();
  }

}
