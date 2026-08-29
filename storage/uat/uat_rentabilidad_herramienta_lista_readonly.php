<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-28
 * Proposito: validar herramienta read-only de rentabilidad por lista de precios real.
 * Impacto: confirma que Rentabilidad analiza Listas con impuestos y costo vigente sin aplicar precios.
 * Contrato: no escribe BD, no modifica Catalogo, Listas, Inventario ni Ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$listas = $modelo->listasPrecioRentabilidad(array("limite" => 50));
$itemsLista = empty($listas["error"]) && isset($listas["depurar"]["items"]) ? $listas["depurar"]["items"] : array();
$listaElegida = null;
foreach ($itemsLista as $lista) {
    if (intval(isset($lista["detalles_activos"]) ? $lista["detalles_activos"] : 0) > 0) {
        $listaElegida = $lista;
        break;
    }
}

$analisis = null;
if ($listaElegida) {
    $analisis = $modelo->analizarListaPrecios(array(
        "id_lista_precio" => intval($listaElegida["id_lista_precio"]),
        "gasto_pct" => 8,
        "comision_pct" => 0,
        "margen_objetivo_pct" => 20,
        "ajuste_pct" => -10,
        "limite" => 25
    ));
}

$fallas = array();
if (!empty($listas["error"])) {
    $fallas[] = array("id" => "COST-LP-UAT-001", "mensaje" => $listas["mensaje"]);
}
if ($listaElegida && !empty($analisis["error"])) {
    $fallas[] = array("id" => "COST-LP-UAT-002", "mensaje" => $analisis["mensaje"]);
}
if ($listaElegida && empty($analisis["depurar"]["lista"]["id_lista_precio"])) {
    $fallas[] = array("id" => "COST-LP-UAT-003", "mensaje" => "El analisis debe devolver encabezado de lista");
}
if ($listaElegida && !array_key_exists("items", $analisis["depurar"])) {
    $fallas[] = array("id" => "COST-LP-UAT-004", "mensaje" => "El analisis debe devolver items");
}
if ($listaElegida && !empty($analisis["depurar"]["items"])) {
    $item = $analisis["depurar"]["items"][0];
    foreach (array("precio_lista_con_impuesto", "precio_lista_sin_impuesto", "costo_real_sin_impuesto", "margen_bruto_pct", "utilidad_estimada", "accion_sugerida") as $campo) {
        if (!array_key_exists($campo, $item)) {
            $fallas[] = array("id" => "COST-LP-UAT-005", "mensaje" => "Falta campo en item de rentabilidad por lista", "campo" => $campo);
        }
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_herramienta_lista_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_listas" => true,
        "no_toca_ventas" => true
    ),
    "fallas" => $fallas,
    "lista_elegida" => $listaElegida,
    "listas" => $listas,
    "analisis" => $analisis
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
