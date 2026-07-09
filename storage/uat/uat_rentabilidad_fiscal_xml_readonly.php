<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatFiscalXmlResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primerConXml = null;
    foreach ($items as $item) {
        if (!empty($item["tiene_sugerencia_xml"])) {
            $primerConXml = array(
                "sku" => $item["sku"],
                "xml" => $item["xml"]
            );
            break;
        }
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "total_fiscal_incompleto" => intval(isset($depurar["total_fiscal_incompleto"]) ? $depurar["total_fiscal_incompleto"] : 0),
        "con_sugerencia_xml" => intval(isset($depurar["con_sugerencia_xml"]) ? $depurar["con_sugerencia_xml"] : 0),
        "sin_sugerencia_xml" => intval(isset($depurar["sin_sugerencia_xml"]) ? $depurar["sin_sugerencia_xml"] : 0),
        "primer_sku" => isset($items[0]["sku"]) ? $items[0]["sku"] : null,
        "primer_con_xml" => $primerConXml
    );
}

$general = $modelo->auditarFiscalXmlCierre(array("limite" => 120));
$tp40372 = $modelo->auditarFiscalXmlCierre(array("q" => "TP-40372", "limite" => 120));
$tp40352 = $modelo->auditarFiscalXmlCierre(array("q" => "TP-40352", "limite" => 120));
$spf1200 = $modelo->auditarFiscalXmlCierre(array("q" => "SPF-1200", "limite" => 120));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]) && empty($spf1200["error"]),
    "general" => uatFiscalXmlResumen($general),
    "tp40372" => uatFiscalXmlResumen($tp40372),
    "tp40352" => uatFiscalXmlResumen($tp40352),
    "spf1200" => uatFiscalXmlResumen($spf1200)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
