<?php

class Prospectos extends Controlador {

    function carritos() {
        $this->vista("apps/crm/prospectos/prospectos_carritos");
    }

    function editar_carrito() {
        $this->vista("apps/crm/prospectos/prospectos_editar_carrito");
    }

    function carritos_consultar() {
        $prospectos = $this->modelo("Modelo_Prospectos");
        $respuesta = $prospectos->consultar_lista_prospectos_carritos();
        return json_encode($respuesta);
    }
    
    public function mostrar_pedidos() {
        $this->vista('apps/erp/proveedores/pedidos/mostrar');
    }

    public function consulta_completa_prospecto() {
        $id_prospecto = $_POST['id_pedido'] && $_POST['id_pedido'] != null ? $_POST['id_pedido'] : 0;
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Datos incorrectos",
            'depurar' => []
        ];
        //
        if ($id_prospecto != 0) {

            //codigo carrito
            $prospecto = $this->modelo("Prospecto");

            $prospecto->setId_prospecto($id_prospecto);
            $respuesta = $prospecto->consultar_carrito_prospecto();
//            var_dump($respuesta);
            if ($respuesta['error'] == false) {
                $codigo_carrito = $respuesta['depurar']['codigo_carrito'];
                $nombres = $respuesta['depurar']['nombres'];
                $celular = $respuesta['depurar']['celular'];
                $pedido['cliente'] = array(
                    'nombres' => $nombres,
                    "contacto1" => $celular
                );
                $carrito = $this->modelo('Carritos');

                $carrito->setCodigo($codigo_carrito);

                $respuesta = $carrito->consultar_items_carrito();
//                var_dump($respuesta);
                $productos_pedido = $respuesta['depurar'];
                $ids_productos_pedido = array();
                $arreglo_productos_por_id = array();
                $ids_tipo_producto = array();
                foreach ($productos_pedido as $key => $value) {
                    if (!in_array($value['identificador'], $arreglo_productos_por_id)) {
                        $ids_tipo_producto[$value['identificador']] = $value['tipo'];
                        $ids_productos_pedido[] = $value['identificador'];
                        $arreglo_productos_por_id[$value['identificador']] = $value;
                    }
                }
//                var_dump($ids_productos_pedido);
                //productos 
                $productos_resp = array();
                $productos = $this->modelo("Productos");
                $respuesta = $productos->listar_productos_imagen();
                if ($respuesta['error'] == false) {
                    $productos_resp = $respuesta['depurar'];
                    $pedido['productos'] = $productos_resp;
                }

                //paquetes 
                $paquete = $this->modelo("Paquete");
                $paquetes = array();
                $respuesta = $paquete->listar_paquetes_imagen();
                if ($respuesta['error'] == false) {
                    $paquetes = $respuesta['depurar'];
                    $pedido['paquetes'] = $paquetes;
                }
//        var_dump($arreglo_productos_por_id);
                $items = array_merge($paquetes, $productos_resp);
                foreach ($items as $key => $value) {
                    if ($items[$key]['tipo_item'] == "paquete" && in_array($items[$key]['id_paquete'], $ids_productos_pedido)) {
                        if ($arreglo_productos_por_id[$items[$key]['id_paquete']]['tipo'] == 'paquete') {
                            $items[$key]['cantidad'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['cantidad'];
                            $items[$key]['descuento'] = 0;
                            $items[$key]['id_pedido'] = $id_prospecto;
                            $items[$key]['precio'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['precio'];
                            $items[$key]['subtotal'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['subtotal'];
                        }
                    } else if ($items[$key]['tipo_item'] == "producto" && in_array($items[$key]['id_producto'], $ids_productos_pedido)) {
                        if ($arreglo_productos_por_id[$items[$key]['id_producto']]['tipo'] == 'producto') {
                            $items[$key]['cantidad'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['cantidad'];
                            $items[$key]['descuento'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['descuento'];
                            $items[$key]['id_pedido'] = $id_prospecto;
                            $items[$key]['precio'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['precio'];
                            $items[$key]['subtotal'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['subtotal'];
                        }
                    }
                }

                $pedido['productos'] = $items;
            }




            $respuesta = [
                'error' => false,
                'tipo' => "success",
                'mensaje' => "Pedido encontrado",
                'depurar' => $pedido
            ];
        } else {
            $respuesta = [
                'error' => true,
                'tipo' => "danger",
                'mensaje' => "Pedido inválido",
                'depurar' => []
            ];
        }
        return json_encode($respuesta);
    }
}
