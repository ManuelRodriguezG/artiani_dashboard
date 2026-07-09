<?php

class Modelo_Prospectos extends CRUD {
  
  
  
  private $tabla_crm_prospectos = "crm_prospectos";
  private $tabla_crm_prospectos_carritos = "crm_prospectos_carritos";

  function consultar_lista_prospectos_carritos() {
    $this->setColumnas(array(
        "crmp.nombres",
        "crmp.celular",
        "crmp.telefono",
        "crmp.apellido_materno",
        "crmp.apellido_paterno",
        "crmp.correo",
        "crmp.alias",
        "crmp.id_prospecto",
        "crmp.celular",
        "crmpc.fch_r"
    ));
    $this->setTabla($this->tabla_crm_prospectos . " crmp");
    $this->setInnerJoin($this->tabla_crm_prospectos_carritos . " crmpc ON crmpc.id_prospecto = crmp.id_prospecto");
    $this->setOrderBy("crmp.id_prospecto");
    $this->setAscDesc("DESC");
    return $this->listar();
  }

}
