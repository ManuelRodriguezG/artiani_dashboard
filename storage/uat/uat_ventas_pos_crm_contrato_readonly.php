<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: auditar el contrato cliente CRM/POS para ventas y excepciones comerciales sin escribir BD.
 * Impacto: detecta si un identificador vive en CRM canonico, POS UAT viejo o excepcion comercial.
 * Contrato: read-only; no crea clientes, no actualiza excepciones, no toca ventas, caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$identificador = "5550000000";
$idSku = 1760;
$idAlmacen = 5;
$folioExcepcion = "";
foreach ($args as $arg) {
    if (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio_excepcion=") === 0) {
        $folioExcepcion = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--folio=") === 0) {
        $folioExcepcion = trim(substr($arg, 8), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosCrmContratoReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosCrmContratoReadonlyDb())->db();
$normalizado = normalizarIdentificadorUat($identificador);
$hallazgos = array();
$siguientesPasos = array();
$tablas = array(
    "crm_clientes_maestro" => tablaExiste($db, "crm_clientes_maestro"),
    "crm_clientes_identificadores" => tablaExiste($db, "crm_clientes_identificadores"),
    "erp_clientes" => tablaExiste($db, "erp_clientes"),
    "erp_clientes_identificadores" => tablaExiste($db, "erp_clientes_identificadores"),
    "erp_ventas_excepciones_comerciales" => tablaExiste($db, "erp_ventas_excepciones_comerciales")
);

$crm = array();
if ($tablas["crm_clientes_maestro"] && $tablas["crm_clientes_identificadores"] && $normalizado["valor_normalizado"] !== "") {
    $stmt = $db->prepare("SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
            i.tipo, i.valor, i.valor_normalizado
        FROM crm_clientes_identificadores i
        INNER JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
        WHERE i.valor_normalizado=:valor AND i.estatus='activo'
        ORDER BY i.principal DESC, c.id_cliente_crm ASC");
    $stmt->execute(array(":valor" => $normalizado["valor_normalizado"]));
    $crm = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$erp = array();
if ($tablas["erp_clientes"] && $tablas["erp_clientes_identificadores"] && $normalizado["valor_normalizado"] !== "") {
    $stmt = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico, c.estatus, c.calidad_datos,
            i.tipo, i.valor, i.valor_normalizado
        FROM erp_clientes_identificadores i
        INNER JOIN erp_clientes c ON c.id_cliente=i.id_cliente
        WHERE i.valor_normalizado=:valor AND i.estatus='activo'
        ORDER BY i.principal DESC, c.id_cliente ASC");
    $stmt->execute(array(":valor" => $normalizado["valor_normalizado"]));
    $erp = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$excepcion = null;
if ($folioExcepcion !== "" && $tablas["erp_ventas_excepciones_comerciales"]) {
    $stmt = $db->prepare("SELECT e.id_excepcion_comercial, e.folio, e.estatus, e.id_cliente,
            e.id_venta, e.id_venta_detalle, e.datos_snapshot,
            c.codigo_cliente erp_codigo_cliente, c.nombre_publico erp_nombre_publico
        FROM erp_ventas_excepciones_comerciales e
        LEFT JOIN erp_clientes c ON c.id_cliente=e.id_cliente
        WHERE e.folio=:folio
        LIMIT 1");
    $stmt->execute(array(":folio" => $folioExcepcion));
    $excepcion = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($excepcion) {
        $snapshot = json_decode((string) $excepcion["datos_snapshot"], true);
        $clienteSnapshot = is_array($snapshot) && isset($snapshot["cliente"]) && is_array($snapshot["cliente"]) ? $snapshot["cliente"] : array();
        $excepcion["cliente_snapshot"] = array(
            "id_cliente" => isset($clienteSnapshot["id_cliente"]) ? intval($clienteSnapshot["id_cliente"]) : 0,
            "id_cliente_crm" => isset($clienteSnapshot["id_cliente_crm"]) ? intval($clienteSnapshot["id_cliente_crm"]) : 0,
            "origen_cliente" => isset($clienteSnapshot["origen_cliente"]) ? $clienteSnapshot["origen_cliente"] : "",
            "nombre_publico" => isset($clienteSnapshot["nombre_publico"]) ? $clienteSnapshot["nombre_publico"] : "",
            "identificador" => isset($clienteSnapshot["identificador"]) ? $clienteSnapshot["identificador"] : ""
        );
        unset($excepcion["datos_snapshot"]);
    }
}

$ventas = new VentasErp();
$items = json_encode(array(array("id_sku" => $idSku, "cantidad" => 1, "modo_salida" => "existencia_agregada", "precio_unitario" => 0)));
$resolverVentas = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "items" => $items
));

if (empty($crm) && !empty($erp)) {
    $hallazgos[] = "El identificador existe en erp_clientes POS/UAT, pero no en CRM canonico";
    $siguientesPasos[] = "Crear vinculo/migracion controlada de cliente POS UAT hacia CRM o usar un cliente CRM canonico existente";
}
if (!empty($crm) && !empty($erp)) {
    $hallazgos[] = "El identificador existe en CRM canonico y en POS/UAT; requiere confirmar si son la misma persona antes de fusionar/vincular";
}
if (!empty($crm) && empty($erp)) {
    $hallazgos[] = "El identificador existe solo en CRM canonico; POS debe usar id_cliente_crm y snapshot, no id_cliente legacy";
}
if (empty($crm) && empty($erp) && $normalizado["valor_normalizado"] !== "") {
    $hallazgos[] = "El identificador no existe en CRM canonico ni en POS/UAT";
    $siguientesPasos[] = "Usar alta express CRM autorizada si el cliente debe quedar identificado";
}
if ($excepcion && intval($excepcion["id_cliente"]) <= 0 && intval($excepcion["cliente_snapshot"]["id_cliente_crm"]) > 0) {
    $hallazgos[] = "La excepcion conserva cliente CRM en snapshot, pero no tiene columna canonica para ligarlo";
    $siguientesPasos[] = "Agregar columnas id_cliente_crm/snapshot_cliente a excepciones y ventas antes de depender de cliente en POS";
}
if ($excepcion && intval($excepcion["id_cliente"]) <= 0 && intval($excepcion["cliente_snapshot"]["id_cliente_crm"]) <= 0) {
    $hallazgos[] = "La excepcion no quedo ligada a cliente ERP ni CRM";
}

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_crm_contrato_readonly",
    "identificador" => $identificador,
    "normalizado" => $normalizado,
    "tablas" => $tablas,
    "crm_canonico" => array(
        "total" => count($crm),
        "coincidencias" => $crm
    ),
    "pos_uat_legacy" => array(
        "total" => count($erp),
        "coincidencias" => $erp
    ),
    "excepcion" => $excepcion,
    "resolver_ventas_cliente_precio" => array(
        "tipo" => isset($resolverVentas["tipo"]) ? $resolverVentas["tipo"] : "",
        "mensaje" => isset($resolverVentas["mensaje"]) ? $resolverVentas["mensaje"] : "",
        "cliente" => isset($resolverVentas["depurar"]["cliente"]) ? $resolverVentas["depurar"]["cliente"] : array(),
        "bloqueos" => isset($resolverVentas["depurar"]["bloqueos"]) ? $resolverVentas["depurar"]["bloqueos"] : array()
    ),
    "hallazgos" => array_values(array_unique($hallazgos)),
    "siguientes_pasos" => array_values(array_unique($siguientesPasos)),
    "no_escribe_bd" => true
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function normalizarIdentificadorUat($identificador) {
    $identificador = trim((string) $identificador);
    $soloDigitos = preg_replace('/\D+/', '', $identificador);
    if ($soloDigitos !== "" && strlen($soloDigitos) >= 7) {
        return array("tipo" => "telefono", "valor_normalizado" => $soloDigitos);
    }
    if (filter_var($identificador, FILTER_VALIDATE_EMAIL)) {
        return array("tipo" => "correo", "valor_normalizado" => strtolower($identificador));
    }
    return array("tipo" => "codigo", "valor_normalizado" => strtoupper($identificador));
}
