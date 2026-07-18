<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: encontrar clientes CRM aptos para UAT puro de lista por segmento.
 * Impacto: evita usar clientes con lista directa/default que tapen `lista_segmento_cliente`.
 * Contrato: read-only; no asigna segmentos, no cambia clientes, listas, POS ni ventas.
 */

$codigoSegmento = isset($argv[1]) ? trim((string) $argv[1]) : "RECURRENTE";
$limite = isset($argv[2]) ? intval($argv[2]) : 20;
$limite = $limite > 0 && $limite <= 100 ? $limite : 20;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ClientesCrm.php";

class LpClientePuroSegmentoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpClientePuroSegmentoReadonlyDb())->db();
$crm = new ClientesCrm();
$bloqueos = array();
$avisos = array();
$segmentos = $crm->segmentosCatalogoReadOnly(array("q" => $codigoSegmento, "limite" => 20));
$segmento = buscarSegmentoClientePuro($segmentos, $codigoSegmento);

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}
if (!tablaClientePuroExiste($db, "crm_clientes_maestro")) {
    $bloqueos[] = "tabla_crm_clientes_maestro_pendiente";
}
if (!tablaClientePuroExiste($db, "erp_clientes_listas_precios")) {
    $avisos[] = "tabla_erp_clientes_listas_precios_no_existe; todos los clientes se consideran sin lista directa";
}
if (!$segmento) {
    $avisos[] = "segmento_" . strtoupper($codigoSegmento) . "_no_existe_todavia; candidatos sirven para elegir cliente, pero no para apply inmediato";
}

$clientes = empty($bloqueos) ? clientesPurosSegmento($db, $limite) : array();
$top = count($clientes) ? $clientes[0] : null;

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "codigo_segmento" => $codigoSegmento,
    "segmento" => $segmento,
    "cliente_recomendado" => $top,
    "clientes_puros_candidatos" => $clientes,
    "criterio" => array(
        "cliente_activo" => true,
        "sin_lista_directa_activa" => true,
        "sin_lista_default_crm" => true,
        "preferir_sin_segmento_default" => true,
        "uso" => "Asignar a segmento UAT para probar regla_precio_origen=lista_segmento_cliente"
    ),
    "comando_uat_si_se_autoriza" => $top ? "C:\\xampp\\php\\php.exe storage\\uat\\uat_crm_cliente_segmento_apply_authorized.php --autorizar=CRM_CLIENTES_SEGMENTO --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql --id_cliente_crm=" . intval($top["id_cliente_crm"]) . " --codigo_segmento=" . $codigoSegmento . " --principal=1 --actualizar_default=1" : null,
    "comando_validacion_prioridad" => $top ? "C:\\xampp\\php\\php.exe storage\\uat\\uat_listas_precios_prioridad_resolutor_readonly.php " . intval($top["id_cliente_crm"]) . " 1760 5 pos" : null,
    "avisos" => $avisos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_asigna_segmentos" => true,
        "no_modifica_clientes" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function clientesPurosSegmento($db, $limite) {
    $tieneListasCliente = tablaClientePuroExiste($db, "erp_clientes_listas_precios") && columnaClientePuroExiste($db, "erp_clientes_listas_precios", "id_cliente_crm");
    $joinLista = $tieneListasCliente
        ? "LEFT JOIN erp_clientes_listas_precios cl ON cl.id_cliente_crm=c.id_cliente_crm AND cl.estatus='activo'"
        : "";
    $selectLista = $tieneListasCliente
        ? "COUNT(cl.id_cliente_lista_precio) listas_directas_activas"
        : "0 listas_directas_activas";
    $whereSinLista = $tieneListasCliente ? "HAVING listas_directas_activas=0" : "";

    $sql = "SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus,
            c.id_lista_precio_default, c.id_segmento_default,
            s.codigo codigo_segmento_default, s.nombre nombre_segmento_default,
            $selectLista
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_segmentos s ON s.id_segmento_crm=c.id_segmento_default
        $joinLista
        WHERE c.estatus='activo'
          AND (c.id_lista_precio_default IS NULL OR c.id_lista_precio_default=0)
        GROUP BY c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus,
            c.id_lista_precio_default, c.id_segmento_default, s.codigo, s.nombre
        $whereSinLista
        ORDER BY c.id_segmento_default IS NULL DESC, c.id_cliente_crm ASC
        LIMIT " . intval($limite);
    $stmt = $db->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarSegmentoClientePuro($respuesta, $codigo) {
    $segmentos = isset($respuesta["depurar"]["segmentos"]) && is_array($respuesta["depurar"]["segmentos"]) ? $respuesta["depurar"]["segmentos"] : array();
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function tablaClientePuroExiste($db, $tabla) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaClientePuroExiste($db, $tabla, $columna) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}
