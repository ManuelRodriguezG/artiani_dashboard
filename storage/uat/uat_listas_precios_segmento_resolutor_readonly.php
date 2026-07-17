<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar en read-only la cadena lista/segmento/resolutor POS.
 * Impacto: confirma que el backend puede resolver `lista_segmento_cliente` cuando existan datos.
 * Contrato: no crea segmentos, no vincula listas, no asigna clientes y no modifica ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$codigoSegmento = isset($argv[2]) ? trim((string) $argv[2]) : "RECURRENTE";
$idSku = isset($argv[3]) ? intval($argv[3]) : 1760;
$idAlmacen = isset($argv[4]) ? intval($argv[4]) : 5;
$idCliente = isset($argv[5]) ? intval($argv[5]) : 0;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";

$listas = new ListasPreciosErp();
$ventas = new VentasErp();
$segmentos = $listas->segmentosCrmReadOnly(array("q" => $codigoSegmento, "id_lista_precio" => $idLista, "limite" => 20));
$segmento = buscarSegmentoReadonly($segmentos, $codigoSegmento);
$idSegmento = $segmento ? intval($segmento["id_segmento_crm"]) : 0;
$dryAsignacion = $listas->asignacionSegmentoDryRun(array(
    "id_segmento_crm" => $idSegmento,
    "id_lista_precio" => $idLista,
    "canal" => "pos",
    "id_almacen" => $idAlmacen,
    "prioridad" => 100,
    "estatus" => "activo"
));

$resolutor = null;
if ($idCliente > 0) {
    $resolutor = $ventas->clientePrecioDryRun(array(
        "id_almacen" => $idAlmacen,
        "canal" => "pos",
        "id_cliente" => $idCliente,
        "items" => array(array("id_sku" => $idSku, "cantidad" => 1))
    ));
}

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "id_segmento_crm" => $idSegmento,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "id_cliente" => $idCliente
    ),
    "segmento" => $segmento,
    "segmentos_crm" => $segmentos,
    "dryrun_asignacion_segmento" => $dryAsignacion,
    "resolutor_pos" => $resolutor,
    "esperado_post_apply" => array(
        "segmento_existente" => $idSegmento > 0,
        "tabla_puente_existente" => valorReadonly($segmentos, array("depurar", "schema", "segmentos_listas"), false),
        "dryrun_asignacion_sin_bloqueos" => empty(valorReadonly($dryAsignacion, array("depurar", "bloqueos"), array())),
        "origen_esperado_con_cliente_segmentado" => "lista_segmento_cliente"
    ),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_crea_segmentos" => true,
        "no_vincula_listas" => true,
        "no_asigna_clientes" => true,
        "no_modifica_ventas_pasadas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function buscarSegmentoReadonly($respuesta, $codigo) {
    $segmentos = valorReadonly($respuesta, array("depurar", "segmentos"), array());
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function valorReadonly($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
