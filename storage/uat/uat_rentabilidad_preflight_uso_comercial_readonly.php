<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$casos = array(
    "general" => array("canal" => "menudeo", "limite" => 120),
    "tp40372" => array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120)
);

$salida = array();
foreach ($casos as $clave => $filtros) {
    $respuesta = $modelo->preflightUsoComercial($filtros);
    $resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
    $destinos = isset($respuesta["depurar"]["destinos"]) ? $respuesta["depurar"]["destinos"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "estado_general" => isset($resumen["estado_general"]) ? $resumen["estado_general"] : null,
        "resumen" => $resumen,
        "catalogo_precios" => isset($destinos["catalogo_precios"]) ? $destinos["catalogo_precios"] : null,
        "menudeo" => isset($destinos["menudeo"]) ? array(
            "estado" => $destinos["menudeo"]["estado"],
            "listos" => $destinos["menudeo"]["listos"],
            "bloqueados" => $destinos["menudeo"]["bloqueados"]
        ) : null,
        "mayoreo_pedidos" => isset($destinos["mayoreo_pedidos"]) ? array(
            "estado" => $destinos["mayoreo_pedidos"]["estado"],
            "listos" => $destinos["mayoreo_pedidos"]["listos"],
            "bloqueados" => $destinos["mayoreo_pedidos"]["bloqueados"]
        ) : null,
        "catalogo_fiscal" => isset($destinos["catalogo_fiscal"]) ? $destinos["catalogo_fiscal"] : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"]) || $caso["estado_general"] !== "bloqueado") {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["destinos"]) < 6) {
    $ok = false;
}
if ($salida["general"]["catalogo_precios"]["estado"] !== "bloqueado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "preflight_uso_comercial" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

