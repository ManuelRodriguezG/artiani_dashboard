<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$general = $modelo->auditarCierrePrecios(array("limite" => 120));
$tp40372 = $modelo->auditarCierrePrecios(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->auditarCierrePrecios(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));

function uatCierreResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $bloqueos = array();
    foreach (isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array() as $item) {
        if (intval($item["total"]) > 0) {
            $bloqueos[] = array(
                "id" => $item["id"],
                "titulo" => $item["titulo"],
                "total" => intval($item["total"]),
                "primer_sku" => isset($item["skus"][0]["sku"]) ? $item["skus"][0]["sku"] : null
            );
        }
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "estado" => isset($depurar["estado"]) ? $depurar["estado"] : null,
        "bloqueos_duros" => isset($depurar["bloqueos_duros"]) ? intval($depurar["bloqueos_duros"]) : null,
        "alertas" => isset($depurar["alertas"]) ? intval($depurar["alertas"]) : null,
        "skus" => isset($depurar["resumen"]["skus"]) ? intval($depurar["resumen"]["skus"]) : null,
        "presentaciones_alertas" => isset($depurar["presentaciones"]["alertas"]) ? intval($depurar["presentaciones"]["alertas"]) : null,
        "snapshots_desfasados" => isset($depurar["snapshots"]["desfasados"]) ? intval($depurar["snapshots"]["desfasados"]) : null,
        "recomendaciones_pendientes" => isset($depurar["recomendaciones_pendientes"]) ? intval($depurar["recomendaciones_pendientes"]) : null,
        "bloqueos" => $bloqueos
    );
}

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatCierreResumen($general),
    "tp40372" => uatCierreResumen($tp40372),
    "tp40352" => uatCierreResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
