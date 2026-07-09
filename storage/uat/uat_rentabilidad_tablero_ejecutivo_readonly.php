<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatTableroResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "canal" => isset($depurar["escenario"]["canal"]) ? $depurar["escenario"]["canal"] : null,
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "metricas" => isset($depurar["metricas"]) ? $depurar["metricas"] : array(),
        "primer_perdida" => isset($depurar["perdidas"][0]["sku"]) ? array(
            "sku" => $depurar["perdidas"][0]["sku"],
            "utilidad" => $depurar["perdidas"][0]["utilidad"]
        ) : null,
        "primer_oportunidad" => isset($depurar["oportunidades"][0]["sku"]) ? array(
            "sku" => $depurar["oportunidades"][0]["sku"],
            "utilidad" => $depurar["oportunidades"][0]["utilidad"]
        ) : null,
        "primer_inventario_riesgo" => isset($depurar["inventario_riesgo"][0]["sku"]) ? array(
            "sku" => $depurar["inventario_riesgo"][0]["sku"],
            "valor_inventario" => $depurar["inventario_riesgo"][0]["valor_inventario"]
        ) : null,
        "primer_accion_precio" => isset($depurar["acciones_precio"][0]["sku"]) ? array(
            "sku" => $depurar["acciones_precio"][0]["sku"],
            "delta" => $depurar["acciones_precio"][0]["delta"]
        ) : null
    );
}

$general = $modelo->tableroEjecutivo(array("canal" => "menudeo"));
$tp40372 = $modelo->tableroEjecutivo(array("q" => "TP-40372", "canal" => "menudeo"));
$tp40352 = $modelo->tableroEjecutivo(array("q" => "TP-40352", "canal" => "menudeo"));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatTableroResumen($general),
    "tp40372" => uatTableroResumen($tp40372),
    "tp40352" => uatTableroResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
