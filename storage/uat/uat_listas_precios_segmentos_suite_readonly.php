<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: ejecutar suite read-only de preparacion para listas de precios por segmento.
 * Impacto: resume segmentos, candidatos CRM, DDL pendiente, lista, dry-run y resolutor.
 * Contrato: no escribe BD, no ejecuta DDL, no asigna clientes ni listas, no modifica ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$codigoSegmento = isset($argv[2]) ? trim((string) $argv[2]) : "RECURRENTE";
$idSku = isset($argv[3]) ? intval($argv[3]) : 1760;
$idAlmacen = isset($argv[4]) ? intval($argv[4]) : 5;
$idCliente = isset($argv[5]) ? intval($argv[5]) : 0;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/ClientesCrm.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

class LpSegmentosSuiteReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosSuiteReadonlyDb())->db();
$crm = new ClientesCrm();
$listas = new ListasPreciosErp();
$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$auditoria = $esquema->auditarSegmentosListasPrecios();
$plan = $esquema->planActualizarSegmentosListasPrecios(false);
$segmentos = $crm->segmentosCatalogoReadOnly(array("q" => $codigoSegmento, "limite" => 20));
$segmento = buscarPorCodigoSuite($segmentos, $codigoSegmento);
$idSegmento = $segmento ? intval($segmento["id_segmento_crm"]) : 0;
$lista = $idLista > 0 ? $listas->consultarReadOnly($idLista) : null;
$dryVinculo = $listas->asignacionSegmentoDryRun(array(
    "id_segmento_crm" => $idSegmento,
    "id_lista_precio" => $idLista,
    "canal" => "pos",
    "id_almacen" => $idAlmacen,
    "prioridad" => 100,
    "estatus" => "activo"
));
$clientes = clientesCandidatosSuite($db, 10);
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
    "resumen" => array(
        "segmento_existe" => $idSegmento > 0,
        "tabla_puente_existe" => valorSuite($auditoria, array("depurar", "tablas", 3, "existe"), false),
        "lista_consultada" => $lista ? empty($lista["error"]) : false,
        "dryrun_vinculo_sin_bloqueos" => empty(valorSuite($dryVinculo, array("depurar", "bloqueos"), array())),
        "cliente_para_resolutor" => $idCliente > 0,
        "resolutor_ejecutado" => $resolutor !== null
    ),
    "auditoria_segmentos_listas" => $auditoria,
    "plan_sql_sin_ejecutar" => $plan,
    "segmento" => $segmento,
    "segmentos_crm" => $segmentos,
    "clientes_candidatos" => $clientes,
    "lista" => $lista,
    "dryrun_vinculo_segmento_lista" => $dryVinculo,
    "resolutor_pos" => $resolutor,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_crea_segmentos" => true,
        "no_vincula_listas" => true,
        "no_asigna_clientes" => true,
        "no_modifica_ventas_pasadas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function clientesCandidatosSuite($db, $limite) {
    if (!$db) {
        return array();
    }
    $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.id_segmento_default,
            s.codigo codigo_segmento_default
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_segmentos s ON s.id_segmento_crm=c.id_segmento_default
        WHERE c.estatus<>'cancelado'
        ORDER BY c.id_segmento_default IS NULL DESC, c.id_cliente_crm ASC
        LIMIT " . intval($limite));
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPorCodigoSuite($respuesta, $codigo) {
    $segmentos = valorSuite($respuesta, array("depurar", "segmentos"), array());
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function valorSuite($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
