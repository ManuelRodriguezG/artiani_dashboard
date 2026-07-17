<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: listar candidatos CRM para UAT de cliente segmentado sin escribir BD.
 * Impacto: ayuda a elegir cliente y segmento antes de probar listas de precios por segmento.
 * Contrato: read-only; no asigna segmentos, no modifica clientes, listas ni ventas.
 */

$codigoSegmento = isset($argv[1]) ? trim((string) $argv[1]) : "RECURRENTE";
$limite = isset($argv[2]) ? intval($argv[2]) : 10;
$limite = $limite > 0 && $limite <= 50 ? $limite : 10;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ClientesCrm.php";

class CrmClienteSegmentoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new ClientesCrm();
$db = (new CrmClienteSegmentoReadonlyDb())->db();
$segmentos = $modelo->segmentosCatalogoReadOnly(array("q" => $codigoSegmento, "limite" => 20));
$segmento = buscarSegmentoCandidato($segmentos, $codigoSegmento);
$clientes = array();

if ($db) {
    $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.id_segmento_default,
            s.codigo codigo_segmento_default, s.nombre nombre_segmento_default
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_segmentos s ON s.id_segmento_crm=c.id_segmento_default
        WHERE c.estatus<>'cancelado'
        ORDER BY c.id_segmento_default IS NULL DESC, c.id_cliente_crm ASC
        LIMIT " . intval($limite));
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "codigo_segmento" => $codigoSegmento,
    "segmento" => $segmento,
    "clientes_candidatos" => $clientes,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_asigna_segmentos" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function buscarSegmentoCandidato($respuesta, $codigo) {
    $segmentos = isset($respuesta["depurar"]["segmentos"]) && is_array($respuesta["depurar"]["segmentos"]) ? $respuesta["depurar"]["segmentos"] : array();
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}
