<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-13
 * Proposito: validar la prioridad read-only del costo vigente usado por Rentabilidad.
 * Impacto: UAT por SKU para costo, margen, utilidad y origen de evidencia.
 * Contrato: no escribe BD, no actualiza costos, no toca Inventario ni Ventas/ecommerce.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$skuPrueba = isset($argv[1]) ? trim($argv[1]) : "TP-40372";
$modelo = new RentabilidadErp();
$respuesta = $modelo->analizarSkus(array(
    "q" => $skuPrueba,
    "canal" => "menudeo",
    "limite" => 20
));
$presentacionesRespuesta = $modelo->auditarCostosPresentaciones(array(
    "q" => $skuPrueba,
    "limite" => 20
));

$fallas = array();
$item = null;
if (!empty($respuesta["error"])) {
    $fallas[] = array("id" => "COST-VIG-000", "mensaje" => $respuesta["mensaje"]);
} else {
    foreach (isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array() as $fila) {
        if (strcasecmp($fila["sku"], $skuPrueba) === 0) {
            $item = $fila;
            break;
        }
    }
    if (!$item) {
        $fallas[] = array("id" => "COST-VIG-001", "mensaje" => "SKU no encontrado en analisis de rentabilidad");
    }
}

$presentaciones = array();
if (!empty($presentacionesRespuesta["error"])) {
    $fallas[] = array("id" => "COST-VIG-004", "mensaje" => $presentacionesRespuesta["mensaje"]);
} else {
    $presentaciones = isset($presentacionesRespuesta["depurar"]["items"]) ? $presentacionesRespuesta["depurar"]["items"] : array();
}

$prioridad = array(
    "compras_promedio" => isset($item["compras"]["costo_promedio"]) ? $item["compras"]["costo_promedio"] : null,
    "compra_ultima" => isset($item["compras"]["ultimo_costo"]) ? $item["compras"]["ultimo_costo"] : null,
    "xml_ultimo" => isset($item["xml"]["ultimo_costo"]) ? $item["xml"]["ultimo_costo"] : null,
    "proveedor_relacion" => isset($item["proveedor"]["costo_ultimo"]) ? $item["proveedor"]["costo_ultimo"] : null,
    "inventario_promedio" => isset($item["inventario"]["costo_promedio"]) ? $item["inventario"]["costo_promedio"] : null
);

$origenEsperado = "sin_costo";
$costoEsperado = 0;
if ($item) {
    foreach ($prioridad as $origen => $costo) {
        if ($costo !== null && floatval($costo) > 0) {
            $origenEsperado = $origen;
            $costoEsperado = floatval($costo);
            break;
        }
    }
    if ($origenEsperado === "sin_costo" && floatval($item["costo_real_sin_impuesto"]) > 0) {
        $origenEsperado = "catalogo_referencia";
        $costoEsperado = floatval($item["costo_real_sin_impuesto"]);
    }

    if ($item["origen_costo"] !== $origenEsperado) {
        $fallas[] = array(
            "id" => "COST-VIG-002",
            "mensaje" => "Origen de costo no respeta prioridad",
            "esperado" => $origenEsperado,
            "actual" => $item["origen_costo"]
        );
    }
    if ($costoEsperado > 0 && abs(floatval($item["costo_real_sin_impuesto"]) - $costoEsperado) > 0.01) {
        $fallas[] = array(
            "id" => "COST-VIG-003",
            "mensaje" => "Costo vigente no coincide con la fuente prioritaria",
            "esperado" => round($costoEsperado, 6),
            "actual" => $item["costo_real_sin_impuesto"]
        );
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_costo_vigente_readonly",
    "sku_prueba" => $skuPrueba,
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_actualiza_costos" => true,
        "no_inventario" => true,
        "no_ventas_ecommerce" => true
    ),
    "prioridad" => array_merge(array_keys($prioridad), array("catalogo_referencia", "sin_costo")),
    "fallas" => $fallas,
    "evidencia" => $item ? array(
        "sku" => $item["sku"],
        "producto" => $item["producto"],
        "costo_vigente" => $item["costo_real_sin_impuesto"],
        "origen_costo" => $item["origen_costo"],
        "origen_esperado" => $origenEsperado,
        "compras" => $item["compras"],
        "xml" => $item["xml"],
        "proveedor" => $item["proveedor"],
        "inventario" => $item["inventario"],
        "presentaciones" => array_slice($presentaciones, 0, 10)
    ) : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
