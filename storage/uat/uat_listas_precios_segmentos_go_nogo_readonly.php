<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: consolidar go/no-go read-only para activar listas de precios por segmentos CRM.
 * Impacto: resume respaldo, cliente puro, lista, segmento, tabla puente y prioridad esperada antes de ejecutar applies.
 * Contrato: no escribe BD, no ejecuta DDL, no invoca scripts apply_authorized.
 */

$respaldo = isset($argv[1]) ? trim((string) $argv[1]) : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260717_000533_antes_listas_precios_segmentos.sql";
$idLista = isset($argv[2]) ? intval($argv[2]) : 2;
$codigoSegmento = isset($argv[3]) ? trim((string) $argv[3]) : "RECURRENTE";
$idCliente = isset($argv[4]) ? intval($argv[4]) : 2;
$idSku = isset($argv[5]) ? intval($argv[5]) : 1760;
$idAlmacen = isset($argv[6]) ? intval($argv[6]) : 5;
$canal = isset($argv[7]) ? trim((string) $argv[7]) : "pos";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ClientesCrm.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";

class LpSegmentosGoNoGoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosGoNoGoReadonlyDb())->db();
$crm = new ClientesCrm();
$listas = new ListasPreciosErp();
$ventas = new VentasErp();

$checks = array();
$bloqueos = array();
$avisos = array();

$validacionRespaldo = validarRespaldoGoNoGo($respaldo);
$checks[] = checkGoNoGo("respaldo_externo", !empty($validacionRespaldo["ok"]), "Respaldo externo legible antes de cualquier escritura");
if (empty($validacionRespaldo["ok"])) {
    $bloqueos[] = "respaldo_externo_invalido";
}

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}
$checks[] = checkGoNoGo("conexion_mysql", (bool) $db, "Conexion disponible para preflight read-only");

$cliente = $db ? clienteGoNoGo($db, $idCliente) : null;
$lista = $idLista > 0 ? $listas->consultarReadOnly($idLista) : null;
$segmentos = $crm->segmentosCatalogoReadOnly(array("q" => $codigoSegmento, "limite" => 20));
$segmento = buscarSegmentoGoNoGo($segmentos, $codigoSegmento);
$tablaPuente = $db ? tablaGoNoGoExiste($db, "erp_segmentos_listas_precios") : false;
$asignacionesDirectas = $db ? asignacionesDirectasGoNoGo($db, $idCliente) : array();
$clientePuro = $cliente && empty($asignacionesDirectas) && intval(valorGoNoGo($cliente, "id_lista_precio_default", 0)) === 0;

$checks[] = checkGoNoGo("lista_uat", $lista && empty($lista["error"]), "Lista de precios UAT consultable");
$checks[] = checkGoNoGo("cliente_uat_puro", $clientePuro, "Cliente activo sin lista directa ni lista default");
$checks[] = checkGoNoGo("segmento_crm", $segmento !== null, "Segmento CRM requerido para apply de segmento");
$checks[] = checkGoNoGo("tabla_puente", $tablaPuente, "Tabla puente segmento/lista");

if (!$cliente) {
    $bloqueos[] = "cliente_uat_no_existe";
} elseif (!$clientePuro) {
    $bloqueos[] = "cliente_uat_no_es_puro_para_segmento";
}
if (!$lista || !empty($lista["error"])) {
    $bloqueos[] = "lista_uat_no_consultable";
}
if (!$segmento) {
    $avisos[] = "segmento_" . strtoupper($codigoSegmento) . "_pendiente; el paso 1 de apply debe sembrarlo";
}
if (!$tablaPuente) {
    $avisos[] = "tabla_puente_pendiente; el paso 2 de apply debe crearla";
}

$resolutor = null;
if ($cliente && $lista && empty($lista["error"])) {
    $resolutor = $ventas->clientePrecioDryRun(array(
        "id_almacen" => $idAlmacen,
        "canal" => $canal,
        "id_cliente" => $idCliente,
        "items" => array(array("id_sku" => $idSku, "cantidad" => 1))
    ));
}
$origenActual = valorGoNoGo($resolutor, array("depurar", "partidas", 0, "regla_precio_origen"), "");
$checks[] = checkGoNoGo("resolutor_previo", $origenActual !== "", "Resolutor backend responde en dry-run");
if ($origenActual === "lista_cliente") {
    $bloqueos[] = "resolutor_tapado_por_lista_directa";
}
if ($origenActual === "lista_canal_sucursal") {
    $avisos[] = "estado_previo_correcto_para_cliente_puro; despues del apply debe cambiar a lista_segmento_cliente";
}

$goParaPedirAutorizacion = empty($bloqueos)
    && !empty($validacionRespaldo["ok"])
    && $clientePuro
    && $lista
    && empty($lista["error"]);

echo json_encode(array(
    "ok" => $goParaPedirAutorizacion,
    "modo" => "read-only",
    "decision" => $goParaPedirAutorizacion ? "GO_PARA_PEDIR_AUTORIZACION" : "NO_GO",
    "parametros" => array(
        "respaldo" => $respaldo,
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "id_cliente_crm" => $idCliente,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "canal" => $canal
    ),
    "checks" => $checks,
    "estado_actual" => array(
        "respaldo" => $validacionRespaldo,
        "cliente" => $cliente,
        "cliente_puro_para_segmento" => $clientePuro,
        "asignaciones_directas_cliente" => $asignacionesDirectas,
        "lista" => valorGoNoGo($lista, array("depurar", "lista"), null),
        "segmento" => $segmento,
        "tabla_puente_existe" => $tablaPuente,
        "origen_resolutor_actual" => $origenActual
    ),
    "esperado_post_apply" => array(
        "segmento_existe" => true,
        "tabla_puente_existe" => true,
        "cliente_id_segmento_default" => strtoupper($codigoSegmento),
        "origen_resolutor" => "lista_segmento_cliente"
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_ejecuta_apply" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkGoNoGo($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function clienteGoNoGo($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaGoNoGoExiste($db, "crm_clientes_maestro")) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, id_lista_precio_default, id_segmento_default
        FROM crm_clientes_maestro
        WHERE id_cliente_crm=:cliente AND estatus='activo'
        LIMIT 1");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function asignacionesDirectasGoNoGo($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaGoNoGoExiste($db, "erp_clientes_listas_precios") || !columnaGoNoGoExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
        return array();
    }
    $stmt = $db->prepare("SELECT cl.id_cliente_lista_precio, cl.id_lista_precio, cl.prioridad, cl.estatus,
            l.codigo, l.nombre, l.canal, l.id_almacen, l.estatus estatus_lista
        FROM erp_clientes_listas_precios cl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=cl.id_lista_precio
        WHERE cl.id_cliente_crm=:cliente AND cl.estatus='activo'
        ORDER BY cl.prioridad ASC, cl.id_cliente_lista_precio DESC
        LIMIT 20");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarSegmentoGoNoGo($respuesta, $codigo) {
    $segmentos = valorGoNoGo($respuesta, array("depurar", "segmentos"), array());
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function tablaGoNoGoExiste($db, $tabla) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaGoNoGoExiste($db, $tabla, $columna) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function validarRespaldoGoNoGo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function valorGoNoGo($datos, $ruta, $default = null) {
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
