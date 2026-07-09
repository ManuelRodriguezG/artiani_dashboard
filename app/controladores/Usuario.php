<?php

class Usuario extends Controlador {

    function index() {
        
    }

    public function mayoreo_mostrar() {
        $this->vista("apps/erp/usuarios_mayoreo/usuario_mayoreo_editar");
    }
    
    public function consultar_informacion_usuario(){
        $id_usuario_mayoreo = $_POST['id_usuario_mayoreo'];
        
        $usuario = $this->modelo("Usuarios");
        //informacion usuario mayoreo
        $usuario->setId_usuario($id_usuario_mayoreo);
        $respuesta = $usuario->consultar_informacion_usuario_mayoreo();
        //informacion negocio
        //informacion envio
//        var_dump($respuesta);
        echo json_encode($respuesta);
    }
    
    public function actualizar_informacion_mayoreo(){
        
    }
}
