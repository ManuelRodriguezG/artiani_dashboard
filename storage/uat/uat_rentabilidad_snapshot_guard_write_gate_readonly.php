<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$base = array(
    "q" => "TP-40372",
    "canal" => "menudeo",
    "riesgo" => "",
    "descuento_pct" => "0",
    "gasto_pct" => "18",
    "comision_pct" => "0",
    "margen_objetivo_pct" => "25"
);

$sinAutorizacion = $modelo->guardarSnapshot($base, 0);

$conFraseSinRespaldo = $base;
$conFraseSinRespaldo["confirmar_autorizacion"] = "AUTORIZO GUARDAR SNAPSHOT";
$sinRespaldo = $modelo->guardarSnapshot($conFraseSinRespaldo, 0);

$ok = !empty($sinAutorizacion["error"])
    && !empty($sinRespaldo["error"])
    && $sinAutorizacion["tipo"] === "warning"
    && $sinRespaldo["tipo"] === "warning";

echo json_encode(array(
    "ok" => $ok,
    "write_gate" => array(
        "sin_autorizacion" => array(
            "error" => $sinAutorizacion["error"],
            "tipo" => $sinAutorizacion["tipo"],
            "mensaje" => $sinAutorizacion["mensaje"]
        ),
        "sin_respaldo" => array(
            "error" => $sinRespaldo["error"],
            "tipo" => $sinRespaldo["tipo"],
            "mensaje" => $sinRespaldo["mensaje"]
        )
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

