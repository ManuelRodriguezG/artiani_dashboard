<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-16.
 * Proposito: validar preparacion de listas de precios por segmento CRM sin escribir BD.
 * Impacto: entrega auditoria, SQL planeado sin ejecutar, segmentos candidatos y dry-run de asignacion.
 * Contrato: read-only; no crea DDL, no asigna listas, no modifica CRM, POS ni ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/modelos/ListasPreciosErp.php";

$idLista = isset($argv[1]) ? intval($argv[1]) : 0;
$idSegmento = isset($argv[2]) ? intval($argv[2]) : 0;

$esquema = new VentasErpEsquema();
$listas = new ListasPreciosErp();

$auditoria = $esquema->auditarSegmentosListasPrecios();
$plan = $esquema->planActualizarSegmentosListasPrecios(false);
$consultaLista = $idLista > 0 ? $listas->consultarReadOnly($idLista) : array(
    "error" => false,
    "tipo" => "info",
    "mensaje" => "Sin lista para consultar",
    "depurar" => array("asignaciones_segmentos" => array(), "schema" => array())
);
$segmentos = $listas->segmentosCrmReadOnly(array("id_lista_precio" => $idLista, "limite" => 12));
$segmentoCandidato = $idSegmento;

if ($segmentoCandidato <= 0) {
    $items = valorLpSegReadonly($segmentos, array("depurar", "segmentos"), array());
    if (!empty($items) && isset($items[0]["id_segmento_crm"])) {
        $segmentoCandidato = intval($items[0]["id_segmento_crm"]);
    }
}

$dryRun = $listas->asignacionSegmentoDryRun(array(
    "id_segmento_crm" => $segmentoCandidato,
    "id_lista_precio" => $idLista,
    "canal" => "pos",
    "id_almacen" => 5,
    "prioridad" => 100,
    "estatus" => "activo"
));

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "id_segmento_crm" => $segmentoCandidato
    ),
    "auditoria" => $auditoria,
    "plan_sql_sin_ejecutar" => $plan,
    "consulta_lista" => array(
        "error" => isset($consultaLista["error"]) ? $consultaLista["error"] : null,
        "mensaje" => isset($consultaLista["mensaje"]) ? $consultaLista["mensaje"] : "",
        "schema_segmentos_listas" => valorLpSegReadonly($consultaLista, array("depurar", "schema", "segmentos_listas"), false),
        "asignaciones_segmentos" => valorLpSegReadonly($consultaLista, array("depurar", "asignaciones_segmentos"), array())
    ),
    "segmentos_crm" => $segmentos,
    "asignacion_segmento_dryrun" => $dryRun,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_crea_segmentos" => true,
        "no_asigna_listas" => true,
        "no_modifica_ventas_pasadas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valorLpSegReadonly($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
