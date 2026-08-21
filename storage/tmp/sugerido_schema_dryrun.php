<?php
$_SERVER["SERVER_NAME"] = "panel.com.local";
require __DIR__ . "/../../app/iniciador.php";
require __DIR__ . "/../../app/modelos/ComprasEsquema.php";
$esquema = new ComprasEsquema();
$plan = $esquema->planActualizarSugeridosCompra(false);
$errores = array_values(array_filter($plan, function ($item) {
    return !empty($item["error"]);
}));
echo json_encode(array(
    "total" => count($plan),
    "errores" => $errores,
    "primeros" => array_slice($plan, 0, 4)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);