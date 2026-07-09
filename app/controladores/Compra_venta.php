<?php

class Compra_venta extends Controlador {

  public function __construct() {
    
  }

  public function consultar_unidades_compra_venta() {
    $return = [
        'error' => true,
        'tipo' => "danger",
        'mensaje' => "No hay información disponible",
        'depurar' => []
    ];
    $compra_venta = $this->modelo("Modelo_Compra_venta");
//    var_dump($compra_venta->obtener_unidades_compra_venta());
    $respuesta = $compra_venta->obtener_unidades_compra_venta();
//    var_dump($respuesta);
    if ($respuesta['error'] == false) {
      $unidades_compra = array();
      $unidades_venta = array();
      $unidades_compra_venta = array();
      foreach ($respuesta['depurar'] as $key => $value) {
        if (!in_array($value['id_unidad_compra'], $unidades_compra)) {
          $unidades_compra_venta[$value['id_unidad_compra']] = array(
              "abreviatura" => $value['abreviatura_compra'],
              "unidad_compra" => $value['unidad_compra'],
              "id_unidad_compra" => $value['id_unidad_compra'],
              "unidades_venta" => array(
                  $value['id_unidad_venta'] => array(
                      "abreviatura" => $value['abreviatura_venta'],
                      "unidad_venta" => $value['unidad_venta'],
                      "id_unidad_venta" => $value['id_unidad_venta']
                  )
              )
          );
          $unidades_compra[] = $value['id_unidad_compra'];
        } else {
          $unidades_compra_venta[$value['id_unidad_compra']]['unidades_venta'][$value['id_unidad_venta']] = array(
              "abreviatura" => $value['abreviatura_venta'],
              "unidad_venta" => $value['unidad_venta'],
              "id_unidad_venta" => $value['id_unidad_venta']
          );
        }
      }
//      var_dump(json_encode($unidades_compra_venta));
      $return = [
          'error' => false,
          'tipo' => "success",
          'mensaje' => "Datos obtenidos correctamente",
          'depurar' => $unidades_compra_venta
      ];
    }
    return json_encode($return);
  }

}
