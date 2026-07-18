<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: validar aceptacion post-apply para listas de precios por segmento CRM.
 * Impacto: confirma que segmento, tabla puente, vinculo, cliente segmentado y resolutor quedaron operativos.
 * Contrato: read-only; no escribe BD, no ejecuta DDL, no modifica listas, CRM, POS ni ventas.
 */

$idLista = isset($argv[1]) ? intval($argv[1]) : 2;
$codigoSegmento = isset($argv[2]) ? trim((string) $argv[2]) : "RECURRENTE";
$idCliente = isset($argv[3]) ? intval($argv[3]) : 2;
$idSku = isset($argv[4]) ? intval($argv[4]) : 1760;
$idAlmacen = isset($argv[5]) ? intval($argv[5]) : 5;
$canal = isset($argv[6]) ? trim((string) $argv[6]) : "pos";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/ListasPreciosErp.php";
require_once "../app/modelos/VentasErp.php";

class LpSegmentosAcceptanceReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosAcceptanceReadonlyDb())->db();
$listas = new ListasPreciosErp();
$ventas = new VentasErp();

$bloqueos = array();
$avisos = array();

$cliente = $db ? clienteAcceptance($db, $idCliente) : null;
$lista = $idLista > 0 ? $listas->consultarReadOnly($idLista) : null;
$segmentos = $listas->segmentosCrmReadOnly(array("q" => $codigoSegmento, "id_lista_precio" => $idLista, "limite" => 20));
$segmento = buscarSegmentoAcceptance($segmentos, $codigoSegmento);
$idSegmento = $segmento ? intval($segmento["id_segmento_crm"]) : 0;
$tablaPuente = $db ? tablaAcceptanceExiste($db, "erp_segmentos_listas_precios") : false;
$vinculos = $db && $tablaPuente && $idSegmento > 0 ? vinculosAcceptance($db, $idLista, $idSegmento, $canal, $idAlmacen) : array();
$asignacionesDirectas = $db ? asignacionesDirectasAcceptance($db, $idCliente) : array();
$segmentosCliente = $db ? segmentosClienteAcceptance($db, $idCliente, $idSegmento) : array();

$dryVinculo = $listas->asignacionSegmentoDryRun(array(
    "id_segmento_crm" => $idSegmento,
    "id_lista_precio" => $idLista,
    "canal" => $canal,
    "id_almacen" => $idAlmacen,
    "prioridad" => 100,
    "estatus" => "activo"
));

$resolutor = null;
if ($idCliente > 0) {
    $resolutor = $ventas->clientePrecioDryRun(array(
        "id_almacen" => $idAlmacen,
        "canal" => $canal,
        "id_cliente" => $idCliente,
        "items" => array(array("id_sku" => $idSku, "cantidad" => 1))
    ));
}

$origen = valorAcceptance($resolutor, array("depurar", "partidas", 0, "regla_precio_origen"), "");
$idListaResuelta = intval(valorAcceptance($resolutor, array("depurar", "partidas", 0, "id_lista_precio"), 0));
$dryVinculoOk = dryrunVinculoAcceptanceOk($dryVinculo, $vinculos);

$checks = array(
    checkAcceptance("conexion_mysql", (bool) $db, "Conexion disponible"),
    checkAcceptance("lista_consultable", $lista && empty($lista["error"]), "Lista UAT consultable"),
    checkAcceptance("segmento_existe", $idSegmento > 0, "Segmento CRM existe"),
    checkAcceptance("tabla_puente_existe", $tablaPuente, "Tabla puente existe"),
    checkAcceptance("vinculo_segmento_lista_activo", count($vinculos) > 0, "Vinculo activo lista/segmento/canal/almacen existe"),
    checkAcceptance("cliente_segmentado", clienteTieneSegmentoAcceptance($cliente, $idSegmento, $segmentosCliente), "Cliente tiene segmento default o relacion activa"),
    checkAcceptance("cliente_sin_lista_directa", empty($asignacionesDirectas), "Cliente no tiene lista directa que tape segmento"),
    checkAcceptance("dryrun_vinculo_sin_bloqueos", $dryVinculoOk, "Dry-run de vinculo queda sin bloqueos o detecta duplicado activo esperado"),
    checkAcceptance("resolutor_lista_segmento", $origen === "lista_segmento_cliente", "Resolutor devuelve lista_segmento_cliente"),
    checkAcceptance("resolutor_lista_correcta", $idListaResuelta === $idLista, "Resolutor devuelve la lista UAT esperada")
);

foreach ($checks as $check) {
    if (!$check["ok"]) {
        $bloqueos[] = $check["id"];
    }
}
if (!$tablaPuente || !$segmento || empty($vinculos)) {
    $avisos[] = "Antes del apply es normal que falten segmento, tabla puente o vinculo; este script debe pasar despues de ejecutar los pasos autorizados.";
}
if ($origen === "lista_canal_sucursal") {
    $avisos[] = "El cliente sigue cayendo a canal/sucursal; despues del apply debe subir a lista_segmento_cliente.";
}
if ($origen === "lista_cliente") {
    $avisos[] = "El cliente tiene una lista directa ganadora; usar cliente puro o pausar asignacion directa con autorizacion.";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "PASS_POST_APPLY" : "PENDIENTE_O_FAIL",
    "parametros" => array(
        "id_lista_precio" => $idLista,
        "codigo_segmento" => $codigoSegmento,
        "id_segmento_crm" => $idSegmento,
        "id_cliente_crm" => $idCliente,
        "id_sku" => $idSku,
        "id_almacen" => $idAlmacen,
        "canal" => $canal
    ),
    "checks" => $checks,
    "estado" => array(
        "cliente" => $cliente,
        "segmento" => $segmento,
        "lista" => valorAcceptance($lista, array("depurar", "lista"), null),
        "tabla_puente_existe" => $tablaPuente,
        "vinculos_segmento_lista" => $vinculos,
        "segmentos_cliente" => $segmentosCliente,
        "asignaciones_directas_cliente" => $asignacionesDirectas,
        "origen_resolutor" => $origen,
        "id_lista_resuelta" => $idListaResuelta
    ),
    "dryrun_vinculo" => $dryVinculo,
    "resolutor_pos" => $resolutor,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_clientes" => true,
        "no_modifica_listas" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function checkAcceptance($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function clienteAcceptance($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaAcceptanceExiste($db, "crm_clientes_maestro")) {
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

function vinculosAcceptance($db, $idLista, $idSegmento, $canal, $idAlmacen) {
    $stmt = $db->prepare("SELECT sl.*, l.codigo, l.nombre, l.estatus estatus_lista
        FROM erp_segmentos_listas_precios sl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=sl.id_lista_precio
        WHERE sl.id_lista_precio=:lista
          AND sl.id_segmento_crm=:segmento
          AND sl.estatus='activo'
          AND (sl.canal IS NULL OR sl.canal='' OR sl.canal=:canal)
          AND (sl.id_almacen IS NULL OR sl.id_almacen=0 OR sl.id_almacen=:almacen)
        ORDER BY sl.prioridad ASC, sl.id_segmento_lista_precio DESC");
    $stmt->execute(array(":lista" => intval($idLista), ":segmento" => intval($idSegmento), ":canal" => $canal, ":almacen" => intval($idAlmacen)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function segmentosClienteAcceptance($db, $idCliente, $idSegmento) {
    if (intval($idCliente) <= 0 || !tablaAcceptanceExiste($db, "crm_clientes_segmentos_rel") || !tablaAcceptanceExiste($db, "crm_clientes_segmentos")) {
        return array();
    }
    $whereSegmento = intval($idSegmento) > 0 ? " AND r.id_segmento_crm=:segmento" : "";
    $stmt = $db->prepare("SELECT r.id_cliente_segmento, r.id_segmento_crm, r.principal, r.estatus,
            s.codigo, s.nombre, s.estatus estatus_segmento
        FROM crm_clientes_segmentos_rel r
        INNER JOIN crm_clientes_segmentos s ON s.id_segmento_crm=r.id_segmento_crm
        WHERE r.id_cliente_crm=:cliente
          AND r.estatus='activo'
          $whereSegmento
        ORDER BY r.principal DESC, r.id_cliente_segmento DESC");
    $params = array(":cliente" => intval($idCliente));
    if (intval($idSegmento) > 0) {
        $params[":segmento"] = intval($idSegmento);
    }
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function asignacionesDirectasAcceptance($db, $idCliente) {
    if (intval($idCliente) <= 0 || !tablaAcceptanceExiste($db, "erp_clientes_listas_precios") || !columnaAcceptanceExiste($db, "erp_clientes_listas_precios", "id_cliente_crm")) {
        return array();
    }
    $stmt = $db->prepare("SELECT cl.id_cliente_lista_precio, cl.id_lista_precio, cl.prioridad, cl.estatus,
            l.codigo, l.nombre, l.estatus estatus_lista
        FROM erp_clientes_listas_precios cl
        INNER JOIN erp_listas_precios l ON l.id_lista_precio=cl.id_lista_precio
        WHERE cl.id_cliente_crm=:cliente AND cl.estatus='activo'
        ORDER BY cl.prioridad ASC, cl.id_cliente_lista_precio DESC");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function clienteTieneSegmentoAcceptance($cliente, $idSegmento, $segmentosCliente) {
    if (!$cliente || intval($idSegmento) <= 0) {
        return false;
    }
    if (intval(valorAcceptance($cliente, "id_segmento_default", 0)) === intval($idSegmento)) {
        return true;
    }
    return count($segmentosCliente) > 0;
}

function dryrunVinculoAcceptanceOk($dryVinculo, $vinculos) {
    $bloqueos = valorAcceptance($dryVinculo, array("depurar", "bloqueos"), array());
    if (empty($bloqueos)) {
        return true;
    }
    if (empty($vinculos)) {
        return false;
    }
    if (count($bloqueos) !== 1) {
        return false;
    }
    return strpos((string) $bloqueos[0], "Ya existe asignacion activa equivalente") !== false;
}

function buscarSegmentoAcceptance($respuesta, $codigo) {
    $segmentos = valorAcceptance($respuesta, array("depurar", "segmentos"), array());
    foreach ($segmentos as $segmento) {
        if (isset($segmento["codigo"]) && strtoupper((string) $segmento["codigo"]) === strtoupper((string) $codigo)) {
            return $segmento;
        }
    }
    return null;
}

function tablaAcceptanceExiste($db, $tabla) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaAcceptanceExiste($db, $tabla, $columna) {
    if (!$db) {
        return false;
    }
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function valorAcceptance($datos, $ruta, $default = null) {
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
