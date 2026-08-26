<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once __DIR__ . "/../../app/iniciador.php";
require_once RUTA_APP . "/modelos/EcommerceCatalogoPublico.php";

$idSku = isset($argv[1]) ? intval($argv[1]) : 1868;
$modelo = new EcommerceCatalogoPublico();
$preparar = $modelo->prepararPublicacion(array("id_sku" => $idSku));
$plan = $modelo->planPublicarBorrador(array(
  "id_sku" => $idSku,
  "confirmar_revision" => 1,
  "confirmar_agotado" => 1
));

echo json_encode(array(
  "id_sku" => $idSku,
  "preparar" => array(
    "error" => isset($preparar["error"]) ? $preparar["error"] : null,
    "tipo" => isset($preparar["tipo"]) ? $preparar["tipo"] : null,
    "mensaje" => isset($preparar["mensaje"]) ? $preparar["mensaje"] : null,
    "publicable_fase_1" => isset($preparar["depurar"]["publicable_fase_1"]) ? $preparar["depurar"]["publicable_fase_1"] : null,
    "bloqueos" => isset($preparar["depurar"]["bloqueos_publicacion"]) ? $preparar["depurar"]["bloqueos_publicacion"] : array(),
    "producto" => isset($preparar["depurar"]["producto_vivo_erp"]) ? $preparar["depurar"]["producto_vivo_erp"] : array(),
    "publicacion_actual" => isset($preparar["depurar"]["publicacion_actual"]) ? $preparar["depurar"]["publicacion_actual"] : array()
  ),
  "plan_publicar" => array(
    "error" => isset($plan["error"]) ? $plan["error"] : null,
    "tipo" => isset($plan["tipo"]) ? $plan["tipo"] : null,
    "mensaje" => isset($plan["mensaje"]) ? $plan["mensaje"] : null,
    "bloqueos" => isset($plan["depurar"]["bloqueos_publicacion"]) ? $plan["depurar"]["bloqueos_publicacion"] : array(),
    "publicacion_actual" => isset($plan["depurar"]["publicacion_actual"]) ? $plan["depurar"]["publicacion_actual"] : array(),
    "producto" => isset($plan["depurar"]["producto_vivo_erp"]) ? $plan["depurar"]["producto_vivo_erp"] : array()
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
