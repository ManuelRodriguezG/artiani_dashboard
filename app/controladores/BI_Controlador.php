<?php

class BI_Controlador extends Controlador {

    public function index() {
        
    }

    public function consultar_productos_visitados() {

        $producto = $this->modelo("BI_Modelo");

        $respuesta = $producto->listar_productos_visitados();
//        var_dump($respuesta);
        return json_encode($respuesta);
    }
}
