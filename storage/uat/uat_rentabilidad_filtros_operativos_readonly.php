<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatRentabilidadOkAnalisis($respuesta, $validador) {
    if (!empty($respuesta["error"])) {
        return false;
    }
    $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
    foreach ($items as $item) {
        if (!$validador($item)) {
            return false;
        }
    }
    return true;
}

function uatRentabilidadPrimerSku($respuesta) {
    $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
    return isset($items[0]["sku"]) ? $items[0]["sku"] : null;
}

$conStock = $modelo->analizarSkus(array("stock" => "con_stock", "canal" => "menudeo"));
$sinStock = $modelo->analizarSkus(array("stock" => "sin_stock", "canal" => "menudeo"));
$inventarioPromedio = $modelo->analizarSkus(array("origen_costo" => "inventario_promedio", "canal" => "menudeo"));
$subirPrecio = $modelo->revisionOperativa(array("accion" => "subir_precio", "canal" => "menudeo"));
$tp40372ConStock = $modelo->analizarSkus(array("q" => "TP-40372", "stock" => "con_stock", "canal" => "menudeo"));
$tp40352ConStock = $modelo->analizarSkus(array("q" => "TP-40352", "stock" => "con_stock", "canal" => "menudeo"));
$matrizConStock = $modelo->matrizEscenarios(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));
$preciosSubir = $modelo->preciosObjetivo(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));
$datosBaseFiscal = $modelo->auditarDatosBaseCierre(array("accion" => "completar_fiscal", "canal" => "menudeo", "limite" => 120));
$fiscalXmlConStock = $modelo->auditarFiscalXmlCierre(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));

$revisionGrupos = isset($subirPrecio["depurar"]["grupos"]) ? $subirPrecio["depurar"]["grupos"] : array();
$datosBaseGrupos = isset($datosBaseFiscal["depurar"]["grupos"]) ? $datosBaseFiscal["depurar"]["grupos"] : array();

$resultado = array(
    "ok" => true,
    "analisis_con_stock" => array(
        "ok" => uatRentabilidadOkAnalisis($conStock, function ($item) { return floatval($item["inventario"]["disponible_total"]) > 0; }),
        "total" => intval(isset($conStock["depurar"]["resumen"]["skus"]) ? $conStock["depurar"]["resumen"]["skus"] : 0),
        "primer_sku" => uatRentabilidadPrimerSku($conStock)
    ),
    "analisis_sin_stock" => array(
        "ok" => uatRentabilidadOkAnalisis($sinStock, function ($item) { return floatval($item["inventario"]["disponible_total"]) <= 0; }),
        "total" => intval(isset($sinStock["depurar"]["resumen"]["skus"]) ? $sinStock["depurar"]["resumen"]["skus"] : 0),
        "primer_sku" => uatRentabilidadPrimerSku($sinStock)
    ),
    "origen_inventario_promedio" => array(
        "ok" => uatRentabilidadOkAnalisis($inventarioPromedio, function ($item) { return $item["origen_costo"] === "inventario_promedio"; }),
        "total" => intval(isset($inventarioPromedio["depurar"]["resumen"]["skus"]) ? $inventarioPromedio["depurar"]["resumen"]["skus"] : 0),
        "primer_sku" => uatRentabilidadPrimerSku($inventarioPromedio)
    ),
    "revision_subir_precio" => array(
        "ok" => empty($subirPrecio["error"]),
        "evaluados" => intval(isset($subirPrecio["depurar"]["total_skus_evaluados"]) ? $subirPrecio["depurar"]["total_skus_evaluados"] : 0),
        "subir_precio" => intval(isset($revisionGrupos["subir_precio"]["total"]) ? $revisionGrupos["subir_precio"]["total"] : 0)
    ),
    "tp40372_con_stock" => array(
        "ok" => uatRentabilidadOkAnalisis($tp40372ConStock, function ($item) { return floatval($item["inventario"]["disponible_total"]) > 0; }),
        "total" => intval(isset($tp40372ConStock["depurar"]["resumen"]["skus"]) ? $tp40372ConStock["depurar"]["resumen"]["skus"] : 0)
    ),
    "tp40352_con_stock" => array(
        "ok" => empty($tp40352ConStock["error"]),
        "total" => intval(isset($tp40352ConStock["depurar"]["resumen"]["skus"]) ? $tp40352ConStock["depurar"]["resumen"]["skus"] : 0)
    ),
    "matriz_con_stock" => array(
        "ok" => empty($matrizConStock["error"]),
        "evaluados" => intval(isset($matrizConStock["depurar"]["total_skus_evaluados"]) ? $matrizConStock["depurar"]["total_skus_evaluados"] : 0)
    ),
    "precios_subir" => array(
        "ok" => empty($preciosSubir["error"]),
        "resumen" => isset($preciosSubir["depurar"]["resumen"]) ? $preciosSubir["depurar"]["resumen"] : array()
    ),
    "datos_base_fiscal" => array(
        "ok" => empty($datosBaseFiscal["error"]),
        "evaluados" => intval(isset($datosBaseFiscal["depurar"]["total_skus_evaluados"]) ? $datosBaseFiscal["depurar"]["total_skus_evaluados"] : 0),
        "fiscal" => intval(isset($datosBaseGrupos["fiscal"]["total"]) ? $datosBaseGrupos["fiscal"]["total"] : 0)
    ),
    "fiscal_xml_con_stock" => array(
        "ok" => empty($fiscalXmlConStock["error"]),
        "total" => intval(isset($fiscalXmlConStock["depurar"]["total_fiscal_incompleto"]) ? $fiscalXmlConStock["depurar"]["total_fiscal_incompleto"] : 0)
    )
);

foreach ($resultado as $clave => $bloque) {
    if ($clave !== "ok" && empty($bloque["ok"])) {
        $resultado["ok"] = false;
    }
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
