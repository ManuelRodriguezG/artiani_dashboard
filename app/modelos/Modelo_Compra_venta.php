<?php

class Modelo_Compra_venta extends CRUD {
  
  private $tabla_erp_unidades_compra = "erp_unidades_compra";
  private $tabla_erp_unidad_venta = "erp_unidad_venta";
  private $tabla_erp_unidades_compra_venta = "erp_unidades_compra_venta";


  public function obtener_unidades_compra_venta() {
    $campos = array(
        "erpucv.id_unidad_compra",
        "erpuc.unidad_compra",
        "erpuc.abreviatura as abreviatura_compra",
        "erpucv.id_unidad_venta",
        "erpuv.unidad_venta",
        "erpuv.abreviatura as abreviatura_venta"
    );
    $this->setColumnas($campos);
    $this->setTabla($this->tabla_erp_unidades_compra_venta." erpucv");
    $this->setInnerJoin("erp_unidades_compra erpuc ON erpuc.id_unidad_compra = erpucv.id_unidad_compra");
    $this->setInnerJoin("erp_unidad_venta erpuv ON erpuv.id_unidad_venta = erpucv.id_unidad_venta");
    return $this->listar();
  }

}
