<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: validar por folio una solicitud de resurtido creada en RES-T008.
 * Impacto: verifica encabezado/detalle documental y ausencia de preparacion/envio/recepcion antes de RES-T009.
 * Contrato: read-only; no modifica solicitud, no mueve inventario, no aparta stock.
 */

$opciones = getopt("", array("folio::", "id::"));
$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 0;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";

$modelo = new Almacenes();
$consulta = $modelo->consultar_resurtido_readonly(array(
    "id_resurtido_almacen" => $id,
    "folio" => $folio
));

$fallos = array();
$avisos = array();
$depurar = empty($consulta["error"]) && isset($consulta["depurar"]) ? $consulta["depurar"] : array();

if (!empty($consulta["error"])) {
    $fallos[] = isset($consulta["mensaje"]) ? $consulta["mensaje"] : "Consulta de folio fallo";
}
if (intval(valor($depurar, array("schema_pendiente"), 0)) === 1) {
    $avisos[] = "Esquema pendiente; el folio solo podra validarse despues del DDL";
}

$encabezado = valor($depurar, array("encabezado"), null);
$detalle = valor($depurar, array("detalle"), array());
$preparacion = valor($depurar, array("preparacion"), array());
$envios = valor($depurar, array("envios"), array());
$recepciones = valor($depurar, array("recepciones"), array());
$diferencias = valor($depurar, array("diferencias"), array());

if ($encabezado !== null) {
    foreach (array("id_resurtido_almacen", "folio", "estatus", "id_almacen_solicitante", "id_almacen_origen") as $campo) {
        if (!array_key_exists($campo, $encabezado) || $encabezado[$campo] === "" || $encabezado[$campo] === null) {
            $fallos[] = "encabezado.{$campo} faltante";
        }
    }
    if (!in_array($encabezado["estatus"], array("borrador", "solicitado", "autorizado", "rechazado", "cancelado"), true)) {
        $avisos[] = "Estatus documental inesperado para RES-T008: " . $encabezado["estatus"];
    }
}

if ($encabezado !== null && empty($detalle)) {
    $fallos[] = "Detalle vacio para folio";
}
foreach ($detalle as $idx => $linea) {
    foreach (array("id_resurtido_detalle", "id_sku_erp", "cantidad_solicitada", "estatus") as $campo) {
        if (!array_key_exists($campo, $linea) || $linea[$campo] === "" || $linea[$campo] === null) {
            $fallos[] = "detalle[{$idx}].{$campo} faltante";
        }
    }
    if (floatval(valor($linea, array("cantidad_solicitada"), 0)) <= 0) {
        $fallos[] = "detalle[{$idx}].cantidad_solicitada invalida";
    }
}

if (!empty($preparacion)) {
    $fallos[] = "RES-T008 no debe tener preparacion registrada";
}
if (!empty($envios)) {
    $fallos[] = "RES-T008 no debe tener envios registrados";
}
if (!empty($recepciones)) {
    $fallos[] = "RES-T008 no debe tener recepciones registradas";
}
if (!empty($diferencias)) {
    $avisos[] = "Existen diferencias antes de RES-T010; revisar si fueron cargadas manualmente";
}

echo json_encode(array(
    "ok" => empty($fallos),
    "modo" => "almacen_resurtido_folio_readonly",
    "read_only" => true,
    "filtros" => array(
        "folio" => $folio,
        "id_resurtido_almacen" => $id
    ),
    "consulta" => resumenRespuesta($consulta),
    "resumen" => array(
        "encabezado_existe" => $encabezado !== null,
        "detalle_lineas" => is_array($detalle) ? count($detalle) : 0,
        "preparacion_lineas" => is_array($preparacion) ? count($preparacion) : 0,
        "envios" => is_array($envios) ? count($envios) : 0,
        "recepciones" => is_array($recepciones) ? count($recepciones) : 0,
        "diferencias" => is_array($diferencias) ? count($diferencias) : 0
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_mueve_kardex" => true,
        "no_aparta_stock" => true,
        "espera_solo_encabezado_detalle_res_t008" => true
    ),
    "fallos" => $fallos,
    "avisos" => $avisos
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($fallos) ? 0 : 1);

function resumenRespuesta($respuesta) {
    return array(
        "error" => valor($respuesta, array("error"), null),
        "tipo" => valor($respuesta, array("tipo"), null),
        "mensaje" => valor($respuesta, array("mensaje"), null),
        "schema_pendiente" => valor($respuesta, array("depurar", "schema_pendiente"), null)
    );
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
