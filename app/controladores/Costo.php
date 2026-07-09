<?php

include_once "../app/helpers/PHPExcel-1.8/Classes/PHPExcel.php";

class Costo extends Controlador {

    public function __construct() {
        if (!$_SESSION['id_usuario']) {
            header('Location: /autenticacion/login');
            exit;
        }
    }

    //vistas
    public function mostrar_costos() {
        $this->vista('apps/erp/costos/mostrar_costos');
    }

    //acciones
    public function consultar_acciones_costos() {
        $inventario = $this->modelo("Costos");
        $respuesta_acciones = $inventario->consultar_acciones_costos();

        //var_dump($respuesta_acciones);
        return json_encode($respuesta_acciones);
    }

    public function generar_accion_historial_costo() {
//        var_dump($_POST['accion']);
        $accion = $_POST['accion'];
        $requiere_revision = 0;
        $respuesta = array("error" => true);
        $accion_costo = $this->modelo("Costos");
        $id_historial = $_POST['id_historial'];
        if ($accion == 'afectar') {
            $id_proveedor = $_POST['id_proveedor'];
            $nuevo_precio = $_POST['nuevo_precio'];
            $sku = $_POST['sku'];
            $producto = $this->modelo("Productos");
            $producto->setSku($sku);
            $producto->setPrecio_base($nuevo_precio);
            $respuesta = $producto->actualizar_precio_por_sku();
            if ($respuesta['error'] == false) {
                $accion_costo->setId_historial($id_historial);
                $accion_costo->setRequiere_revision($requiere_revision);
                $respuesta = $accion_costo->quitar_revision_historial_costo();
            }
        } else if ($accion == 'quitar_revision') {
            $accion_costo->setId_historial($id_historial);
            $accion_costo->setRequiere_revision($requiere_revision);
            $respuesta = $accion_costo->quitar_revision_historial_costo();
        }
        return json_encode($respuesta);
    }
}
