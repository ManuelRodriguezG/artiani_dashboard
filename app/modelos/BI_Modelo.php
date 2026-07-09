<?php

class BI_Modelo extends CRUD {

    public function index() {
        
    }

    public function listar_productos_visitados() {
        $this->setColumnas(array(
            "COUNT( ecomp.id_producto ) AS cantidad",
            "bisc.tipo",
            "ecomp.nombre",
            "ecomp.id_producto"
        ));
        $this->setInnerJoin("ecom_productos ecomp ON ecomp.id_producto = bisc.identificador");
       
        $this->setTabla("bi_seguimiento_consumibles bisc");
        $this->setWhere("bisc.tipo = 'producto'");
        $this->setAnd("(bisc.fch_r BETWEEN date_add(NOW(), INTERVAL -7 DAY) AND NOW()) GROUP BY ecomp.id_producto ORDER BY cantidad DESC limit 50");
        return $this->listar();
    }
}
