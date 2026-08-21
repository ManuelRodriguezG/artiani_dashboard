<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/modelos/ComprasEsquema.php";
$esquema = new ComprasEsquema();
$plan = $esquema->planActualizarSugeridosCompra(true);
$errores = array_values(array_filter($plan, function ($item) {
    return !empty($item["error"]);
}));
$ejecutados = array_values(array_filter($plan, function ($item) {
    return isset($item["depurar"]["ejecutado"]) && $item["depurar"]["ejecutado"] === true;
}));
echo json_encode(array(
    "total" => count($plan),
    "ejecutados" => count($ejecutados),
    "errores" => $errores,
    "ejecutados_detalle" => $ejecutados
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);