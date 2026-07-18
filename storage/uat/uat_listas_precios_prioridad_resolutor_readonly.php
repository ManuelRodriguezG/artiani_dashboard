<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: explicar en read-only que nivel de prioridad del resolutor de precios aplica para un cliente/SKU.
 * Impacto: evita confundir una prueba de segmento cuando una lista directa de cliente gana por diseno.
 * Contrato: no escribe BD, no crea segmentos, no vincula listas, no modifica ventas ni precios.
 */

$idCliente = isset($argv[1]) ? intval($argv[1]) : 1;
$idSku = isset($argv[2]) ? intval($argv[2]) : 1760;
$idAlmacen = isset($argv[3]) ? intval($argv[3]) : 5;
$canal = isset($argv[4]) ? trim((string) $argv[4]) : "pos";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/VentasErp.php";

class LpPrioridadResolverReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpPrioridadResolverReadonlyDb())->db();
$ventas = new VentasErp();
$bloqueos = array();
$avisos = array();

$cliente = $db ? clientePrioridadReadonly($db, $idCliente) : null;
if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}
if ($idCliente <= 0) {
    $avisos[] = "sin_cliente_crm; solo se evaluara canal/general";
}
if ($idCliente > 0 && !$cliente) {
    $bloqueos[] = "cliente_crm_no_encontrado";
}

$asignacionesDirectas = $db && $cliente ? asignacionesClienteReadonly($db, $idCliente) : array();
$segmentosCliente = $db && $cliente ? segmentosClienteReadonly($db, $idCliente) : array();
$asignacionesSegmento = $db && $cliente ? asignacionesSegmentoReadonly($db, intval(valorPrioridad($cliente, "id_segmento_default", 0)), $canal, $idAlmacen) : array();
$listasCanal = $db ? listasCanalReadonly($db, $idSku, $canal, $idAlmacen) : array();
$listasGeneral = $db ? listasGeneralReadonly($db, $idSku) : array();

$resolutor = null;
if (empty($bloqueos)) {
    $resolutor = $ventas->clientePrecioDryRun(array(
        "id_almacen" => $idAlmacen,
        "canal" => $canal,
        "id_cliente" => $idCliente,
        "items" => array(array("id_sku" => $idSku, "cantidad" => 1))
    ));
}

$partida = valorPrioridad($resolutor, array("depurar", "partidas", 0), array());
$origen = valorPrioridad($partida, "regla_precio_origen", "");

if (!empty($asignacionesDirectas) && $origen !== "lista_cliente") {
    $avisos[] = "hay_lista_directa_cliente_pero_no_gano; revisar vigencia/canal/almacen/SKU";
}
if (!empty($asignacionesDirectas) && !empty($asignacionesSegmento)) {
    $avisos[] = "la_lista_directa_cliente_debe_ganar_sobre_segmento; para probar segmento puro usa cliente sin asignacion directa o pausa la asignacion directa con autorizacion";
}
if (empty($asignacionesDirectas) && !empty($asignacionesSegmento) && $origen !== "lista_segmento_cliente") {
    $avisos[] = "hay_vinculo_segmento_candidato_pero_no_gano; revisar lista activa, detalle SKU, vigencia, canal, almacen y prioridad";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "parametros" => array(
        "id_cliente_crm" => $idCliente,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "canal" => $canal
    ),
    "cliente" => $cliente,
    "jerarquia_resolutor_v1" => array(
        array("orden" => 1, "origen" => "excepcion_manual_autorizada", "estado" => "fuera_de_este_uat"),
        array("orden" => 2, "origen" => "lista_cliente", "candidatos" => count($asignacionesDirectas)),
        array("orden" => 3, "origen" => "lista_cliente_default", "id_lista_precio_default" => intval(valorPrioridad($cliente, "id_lista_precio_default", 0))),
        array("orden" => 4, "origen" => "lista_segmento_cliente", "id_segmento_default" => intval(valorPrioridad($cliente, "id_segmento_default", 0)), "candidatos" => count($asignacionesSegmento)),
        array("orden" => 5, "origen" => "lista_canal_sucursal", "candidatos" => count($listasCanal)),
        array("orden" => 6, "origen" => "lista_general_erp", "candidatos" => count($listasGeneral)),
        array("orden" => 7, "origen" => "catalogo_general", "estado" => "fallback")
    ),
    "candidatos" => array(
        "listas_directas_cliente" => $asignacionesDirectas,
        "segmentos_cliente" => $segmentosCliente,
        "listas_por_segmento" => $asignacionesSegmento,
        "listas_canal_almacen" => $listasCanal,
        "listas_generales" => $listasGeneral
    ),
    "resolutor_pos" => $resolutor,
    "origen_obtenido" => $origen,
    "interpretacion" => interpretacionPrioridad($origen, $asignacionesDirectas, $asignacionesSegmento),
    "avisos" => $avisos,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_crea_segmentos" => true,
        "no_vincula_listas" => true,
        "no_asigna_clientes" => true,
        "no_modifica_ventas_pasadas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function clientePrioridadReadonly($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaPrioridadExiste($db, "crm_clientes_maestro")) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, id_lista_precio_default, id_segmento_default
        FROM crm_clientes_maestro
        WHERE id_cliente_crm=:cliente
        LIMIT 1");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function asignacionesClienteReadonly($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaPrioridadExiste($db, "erp_clientes_listas_precios") || !columnaPrioridadExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
        return array();
    }
    $stmt = $db->prepare("SELECT cl.id_cliente_lista_precio, cl.id_lista_precio, cl.prioridad, cl.fecha_inicio, cl.fecha_fin, cl.estatus,
            l.codigo, l.nombre, l.canal, l.id_almacen, l.estatus estatus_lista
        FROM erp_clientes_listas_precios cl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=cl.id_lista_precio
        WHERE cl.id_cliente_crm=:cliente
          AND cl.estatus='activo'
        ORDER BY cl.prioridad ASC, cl.id_cliente_lista_precio DESC
        LIMIT 20");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function segmentosClienteReadonly($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaPrioridadExiste($db, "crm_clientes_segmentos_rel") || !tablaPrioridadExiste($db, "crm_clientes_segmentos")) {
        return array();
    }
    $stmt = $db->prepare("SELECT r.id_cliente_segmento, r.id_segmento_crm, r.principal, r.estatus,
            s.codigo, s.nombre, s.tipo, s.estatus estatus_segmento
        FROM crm_clientes_segmentos_rel r
        INNER JOIN crm_clientes_segmentos s ON s.id_segmento_crm=r.id_segmento_crm
        WHERE r.id_cliente_crm=:cliente
        ORDER BY r.principal DESC, r.id_cliente_segmento DESC
        LIMIT 20");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function asignacionesSegmentoReadonly($db, $idSegmento, $canal, $idAlmacen) {
    if (intval($idSegmento) <= 0 || !tablaPrioridadExiste($db, "erp_segmentos_listas_precios")) {
        return array();
    }
    $stmt = $db->prepare("SELECT sl.id_segmento_lista_precio, sl.id_lista_precio, sl.canal, sl.id_almacen, sl.prioridad, sl.fecha_inicio, sl.fecha_fin, sl.estatus,
            l.codigo, l.nombre, l.estatus estatus_lista
        FROM erp_segmentos_listas_precios sl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=sl.id_lista_precio
        WHERE sl.id_segmento_crm=:segmento
          AND sl.estatus='activo'
          AND (sl.canal IS NULL OR sl.canal='' OR sl.canal=:canal)
          AND (sl.id_almacen IS NULL OR sl.id_almacen=0 OR sl.id_almacen=:almacen)
        ORDER BY sl.prioridad ASC, sl.id_segmento_lista_precio DESC
        LIMIT 20");
    $stmt->execute(array(":segmento" => intval($idSegmento), ":canal" => $canal, ":almacen" => intval($idAlmacen)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listasCanalReadonly($db, $idSku, $canal, $idAlmacen) {
    if (!tablaPrioridadExiste($db, "erp_listas_precios") || !tablaPrioridadExiste($db, "erp_listas_precios_detalle")) {
        return array();
    }
    $stmt = $db->prepare("SELECT l.id_lista_precio, l.codigo, l.nombre, l.canal, l.id_almacen, l.prioridad, d.precio
        FROM erp_listas_precios l
        INNER JOIN erp_listas_precios_detalle d ON d.id_lista_precio=l.id_lista_precio
        WHERE l.estatus='activa'
          AND d.estatus='activo'
          AND d.id_sku=:sku
          AND l.canal=:canal
          AND l.id_almacen=:almacen
        ORDER BY l.prioridad ASC, d.id_lista_precio_detalle DESC
        LIMIT 20");
    $stmt->execute(array(":sku" => intval($idSku), ":canal" => $canal, ":almacen" => intval($idAlmacen)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function listasGeneralReadonly($db, $idSku) {
    if (!tablaPrioridadExiste($db, "erp_listas_precios") || !tablaPrioridadExiste($db, "erp_listas_precios_detalle")) {
        return array();
    }
    $stmt = $db->prepare("SELECT l.id_lista_precio, l.codigo, l.nombre, l.canal, l.id_almacen, l.prioridad, d.precio
        FROM erp_listas_precios l
        INNER JOIN erp_listas_precios_detalle d ON d.id_lista_precio=l.id_lista_precio
        WHERE l.estatus='activa'
          AND d.estatus='activo'
          AND d.id_sku=:sku
          AND (l.canal IS NULL OR l.canal='')
          AND (l.id_almacen IS NULL OR l.id_almacen=0)
        ORDER BY l.prioridad ASC, d.id_lista_precio_detalle DESC
        LIMIT 20");
    $stmt->execute(array(":sku" => intval($idSku)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function interpretacionPrioridad($origen, $asignacionesDirectas, $asignacionesSegmento) {
    if ($origen === "lista_cliente") {
        return "Gano lista directa de cliente; esto es correcto y tiene prioridad sobre segmento.";
    }
    if ($origen === "lista_segmento_cliente") {
        return "Gano lista por segmento CRM; prueba de segmento exitosa.";
    }
    if (!empty($asignacionesDirectas)) {
        return "Hay lista directa de cliente, pero no gano; revisar si la lista tiene SKU, vigencia y alcance validos.";
    }
    if (!empty($asignacionesSegmento)) {
        return "Hay lista por segmento candidata, pero no gano; revisar detalle SKU, lista activa, vigencia y alcance.";
    }
    return "No hay candidato de cliente/segmento ganador; el resolutor cayo al siguiente nivel disponible.";
}

function tablaPrioridadExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaPrioridadExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function valorPrioridad($datos, $ruta, $default = null) {
    if (!is_array($ruta)) {
        return is_array($datos) && array_key_exists($ruta, $datos) ? $datos[$ruta] : $default;
    }
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
