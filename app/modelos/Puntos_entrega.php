<?php

class Puntos_entrega extends CRUD {

  private $tabla_puntos_entrega = "erp_puntos_de_entrega";
  private $tabla_tipos_entrega = "erp_tipos_entrega";
  private $tabla_tipos_entrega_puntos = "erp_tipos_entrega_puntos";
  

  public function puntos_listar() {
    $this->limpiarVariables();
    $this->setColumnas(array(
        "*"
    ));
    $this->setLeftJoin($this->tabla_tipos_entrega_puntos." erptep ON erptep.id_tipo_entrega = erpte.id_tipo_entrega");
    $this->setLeftJoin($this->tabla_puntos_entrega." erppe ON erppe.id_puntos_entrega = erptep.id_punto_entrega");
    $this->setTabla($this->tabla_tipos_entrega." erpte");
    return $this->listar();
  }

}
