<?php

class Prospecto extends CRUD {

    private $id_prospecto;
    private $tabla_crm_prospectos = "crm_prospectos";
    private $tabla_crm_prospectos_carritos = "crm_prospectos_carritos";

    function consultar_carrito_prospecto() {
        $columnas = array(
            "crmpc.codigo_carrito",
            "crmpc.fch_r",
            "crmp.nombres",
            "crmp.celular"
        );
        $this->setWhere("crmpc.id_prospecto = '" . $this->getId_prospecto() . "'");
        $this->setInnerJoin($this->tabla_crm_prospectos . " crmp ON crmp.id_prospecto = crmpc.id_prospecto");
        $this->setTabla($this->tabla_crm_prospectos_carritos . " crmpc");
        $this->setColumnas($columnas);
        return $this->buscarRegistro();
    }

    public function getId_prospecto() {
        return $this->id_prospecto;
    }
    
    public function setId_prospecto($id_prospecto): void {
        $this->id_prospecto = $id_prospecto;
    }
}
