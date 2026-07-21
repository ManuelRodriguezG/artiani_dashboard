<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar filtros operativos de margen en productos de listas de precios.
 * Impacto: confirma que la mesa puede auditar perdida, sin costo y margen configurable sin guardar.
 * Contrato: read-only; consulta catalogo/listas, no ejecuta DDL ni modifica precios o ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$consultas = array(
    "todos" => $modelo->productosParaListaReadOnly(array("id_lista_precio" => $idLista, "solo" => "todos", "limite" => 30, "margen_minimo" => 15)),
    "margen_25" => $modelo->productosParaListaReadOnly(array("id_lista_precio" => $idLista, "solo" => "margen_bajo", "limite" => 30, "margen_minimo" => 25)),
    "perdida" => $modelo->productosParaListaReadOnly(array("id_lista_precio" => $idLista, "solo" => "perdida", "limite" => 30, "margen_minimo" => 15)),
    "sin_costo" => $modelo->productosParaListaReadOnly(array("id_lista_precio" => $idLista, "solo" => "sin_costo", "limite" => 30, "margen_minimo" => 15))
);

$checks = array(
    checkFiltro("todos_responde", empty($consultas["todos"]["error"]), "Consulta base de productos responde"),
    checkFiltro("margen_25_responde", empty($consultas["margen_25"]["error"]), "Filtro margen bajo con umbral 25 responde"),
    checkFiltro("perdida_responde", empty($consultas["perdida"]["error"]), "Filtro perdida responde"),
    checkFiltro("sin_costo_responde", empty($consultas["sin_costo"]["error"]), "Filtro sin costo responde"),
    checkFiltro("umbral_devuelto_si_hay_contrato", !isset($consultas["margen_25"]["depurar"]["filtros"]) || floatval($consultas["margen_25"]["depurar"]["filtros"]["margen_minimo"] ?? 0) === 25.0, "Endpoint conserva margen minimo solicitado cuando devuelve contrato completo"),
    checkFiltro("perdida_filtra_clave", todosConClave($consultas["perdida"], "perdida"), "Filtro perdida devuelve solo riesgo perdida cuando hay resultados"),
    checkFiltro("sin_costo_filtra_clave", todosConClave($consultas["sin_costo"], "sin_costo"), "Filtro sin costo devuelve solo riesgo sin_costo cuando hay resultados")
);

$bloqueos = array();
foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_FILTROS_MARGEN" : "FAIL_FILTROS_MARGEN",
    "parametros" => array("id_lista_precio" => $idLista),
    "totales" => array(
        "todos" => totalConsulta($consultas["todos"]),
        "margen_25" => totalConsulta($consultas["margen_25"]),
        "perdida" => totalConsulta($consultas["perdida"]),
        "sin_costo" => totalConsulta($consultas["sin_costo"])
    ),
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkFiltro($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function totalConsulta($consulta) {
    return intval($consulta["depurar"]["total"] ?? 0);
}

function todosConClave($consulta, $clave) {
    if (!empty($consulta["error"])) {
        return false;
    }
    $productos = $consulta["depurar"]["productos"] ?? array();
    foreach ($productos as $producto) {
        $riesgo = $producto["riesgo_margen"]["clave"] ?? "";
        if ($riesgo !== $clave) {
            return false;
        }
    }
    return true;
}
