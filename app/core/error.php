<?php

function error($error, $estado, $accion, $respuesta) {

  $response = [
      'error' => $error,
      'estado' => $estado,
      'descripcion' => $accion,
      'respuesta' => $respuesta
  ];
  return $response;
}

/* Estructura del json */
/*
  {
  ["error"]=> bool(false)

  ["estado"]=> string(7) "success"

  ["descripcion"]=> string(21) "Listado correctamente"

  ["respuesta"]=>array(1) {

  [0]=>array(2) {

  ["ip"]=> string(13) "77.111.247.23"

  ["country"]=> string(6) "Sweden"

  }

  }

  }
 */
?>