<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: validar estudios temporales de rentabilidad por grupo de SKUs.
 * Impacto: confirma que Rentabilidad analiza grupos separados sin guardar reportes ni modificar precios.
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

$busqueda = null;
$analisis = null;
$ids = array();
if ($listaElegida) {
    $busqueda = $modelo->buscarSkusEstudioRentabilidad(array(
        "id_lista_precio" => intval($listaElegida["id_lista_precio"]),
        "q" => "",
        "limite" => 8
    ));
    foreach (isset($busqueda["depurar"]["items"]) ? $busqueda["depurar"]["items"] : array() as $item) {
        $ids[] = intval($item["id_sku"]);
        if (count($ids) >= 3) {
            break;
        }
    }
    if (!empty($ids)) {
        $analisis = $modelo->analizarEstudioTemporal(array(
            "id_lista_precio" => intval($listaElegida["id_lista_precio"]),
            "nombre" => "UAT estudio temporal",
            "objetivo" => "revision_margen",
            "ids_sku" => implode(",", $ids),
            "gasto_pct" => 8,
            "comision_pct" => 0,
            "margen_objetivo_pct" => 20,
            "ajuste_pct" => 0,
            "limite" => 20
        ));
    }
}

$fallas = array();
if (!empty($listas["error"])) {
    $fallas[] = array("id" => "COST-EST-UAT-001", "mensaje" => $listas["mensaje"]);
}
if ($listaElegida && !empty($busqueda["error"])) {
    $fallas[] = array("id" => "COST-EST-UAT-002", "mensaje" => $busqueda["mensaje"]);
}
if ($listaElegida && empty($ids)) {
    $fallas[] = array("id" => "COST-EST-UAT-003", "mensaje" => "La lista elegida no devolvio SKUs para estudio temporal");
}
if (!empty($ids) && !empty($analisis["error"])) {
    $fallas[] = array("id" => "COST-EST-UAT-004", "mensaje" => $analisis["mensaje"]);
}
if (!empty($ids) && empty($analisis["depurar"]["estudio"]["modo"])) {
    $fallas[] = array("id" => "COST-EST-UAT-005", "mensaje" => "El analisis debe devolver encabezado de estudio");
}
if (!empty($ids) && !array_key_exists("items", $analisis["depurar"])) {
    $fallas[] = array("id" => "COST-EST-UAT-006", "mensaje" => "El analisis debe devolver items");
}
if (!empty($analisis["depurar"]["items"])) {
    $item = $analisis["depurar"]["items"][0];
    foreach (array("precio_lista_con_impuesto", "costo_real_sin_impuesto", "margen_bruto_pct", "utilidad_estimada", "siguiente_paso") as $campo) {
        if (!array_key_exists($campo, $item)) {
            $fallas[] = array("id" => "COST-EST-UAT-007", "mensaje" => "Falta campo en item de estudio", "campo" => $campo);
        }
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_estudios_temporal_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_guarda_estudios" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_listas" => true,
        "no_toca_ventas" => true
    ),
    "fallas" => $fallas,
    "lista_elegida" => $listaElegida,
    "ids_sku_probados" => $ids,
    "busqueda_total" => isset($busqueda["depurar"]["items"]) ? count($busqueda["depurar"]["items"]) : 0,
    "analisis_resumen" => isset($analisis["depurar"]["resumen"]) ? $analisis["depurar"]["resumen"] : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
