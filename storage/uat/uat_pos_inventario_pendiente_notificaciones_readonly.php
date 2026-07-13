<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar flujo POS con inventario pendiente y notificaciones antes de una UAT real.
 * Impacto: revisa tablas, contratos, pendientes, notificaciones y dry-run sin escribir BD.
 * Contrato: read-only; no abre turno, no vende, no crea pendientes, no crea/cierra notificaciones y no mueve inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/InventarioErp.php";

class UatPosInventarioPendienteReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$args = isset($argv) ? $argv : array();
$idAlmacen = intval(argValor($args, "--id_almacen", 5));
$idSku = intval(argValor($args, "--id_sku", 1760));
$cantidad = floatval(argValor($args, "--cantidad", 1));
$motivo = argValor($args, "--motivo", "Preflight POS inventario pendiente");

$dbHelper = new UatPosInventarioPendienteReadonlyDb();
$db = $dbHelper->db();
$ventas = new VentasErp();
$inventario = new InventarioErp();

$tablasRequeridas = array(
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_detalle_inventario",
    "erp_pos_inventario_pendientes",
    "erp_pos_inventario_pendientes_eventos",
    "erp_pos_politicas_venta_inventario",
    "erp_notificaciones",
    "erp_notificaciones_lecturas",
    "erp_inventario_existencias",
    "erp_inventario_movimientos"
);

$columnasRequeridas = array(
    "erp_ventas" => array("inventario_validacion_estado", "inventario_pendiente_total"),
    "erp_ventas_detalle" => array("inventario_estado", "permite_inventario_pendiente", "cantidad_inventario_pendiente", "id_inventario_pendiente"),
    "erp_ventas_detalle_inventario" => array("tipo_asignacion", "cantidad_pendiente_validacion", "id_inventario_pendiente"),
    "erp_pos_inventario_pendientes" => array("folio", "estatus", "cantidad_fisica_validada", "id_movimiento_ajuste"),
    "erp_notificaciones" => array("tipo", "area_responsable", "permiso_requerido", "estatus", "url_accion", "payload_json")
);

$schema = array(
    "tablas" => array(),
    "columnas" => array()
);
$bloqueos = array();
$avisos = array();

foreach ($tablasRequeridas as $tabla) {
    $existe = tablaExiste($db, $tabla);
    $schema["tablas"][$tabla] = $existe;
    if (!$existe) {
        $bloqueos[] = "Falta tabla requerida: " . $tabla;
    }
}

foreach ($columnasRequeridas as $tabla => $columnas) {
    foreach ($columnas as $columna) {
        $existe = columnaExiste($db, $tabla, $columna);
        $schema["columnas"][$tabla . "." . $columna] = $existe;
        if (!$existe) {
            $bloqueos[] = "Falta columna requerida: " . $tabla . "." . $columna;
        }
    }
}

$dryRunVenta = $ventas->ventaInventarioPendienteDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_sku" => $idSku,
    "cantidad" => $cantidad,
    "canal" => "pos",
    "motivo" => $motivo
));
$pendientes = consultarPendientes($db);
$notificaciones = consultarNotificaciones($db);
$politicas = consultarPoliticas($db, $idAlmacen, $idSku);
$disponible = consultarDisponible($db, $idAlmacen, $idSku);
$estatusExistencias = consultarEstatusExistencias($db, $idAlmacen, $idSku);
$expedienteEjemplo = null;
if (!empty($pendientes["ultimos"])) {
    $expedienteEjemplo = $inventario->consultarPendientePosInventario(array(
        "folio" => $pendientes["ultimos"][0]["folio"]
    ));
}

if (!method_exists($ventas, "ventaInventarioPendienteReal")) {
    $bloqueos[] = "Falta metodo VentasErp::ventaInventarioPendienteReal";
}
if (!method_exists($inventario, "resolverPendientePosInventarioReal")) {
    $bloqueos[] = "Falta metodo InventarioErp::resolverPendientePosInventarioReal";
}
if (!method_exists($inventario, "resolucionPendientePosInventarioDryRun")) {
    $bloqueos[] = "Falta metodo InventarioErp::resolucionPendientePosInventarioDryRun";
}
if (empty($politicas["activas"])) {
    $avisos[] = "No hay politica activa especifica para almacen/SKU; la UAT real podria bloquearse si falta politica general aplicable";
}
if (intval($pendientes["abiertos"]) === 0) {
    $avisos[] = "No hay pendientes abiertos; para probar UI completa se requiere generar un nuevo PINV";
}
if (intval($notificaciones["abiertas"]) > 0 && intval($pendientes["abiertos"]) === 0) {
    $avisos[] = "Hay notificaciones POS pendientes sin pendientes abiertos; revisar cierre de alertas";
}
if (!empty($estatusExistencias["inconsistencias"])) {
    $avisos[] = "Hay existencias con saldo disponible pero estatus agotada; revisar normalizacion de estatus antes de UAT visual";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "pos_inventario_pendiente_notificaciones_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad
    ),
    "schema" => $schema,
    "datos_actuales" => array(
        "disponible" => $disponible,
        "estatus_existencias" => $estatusExistencias,
        "politicas" => $politicas,
        "pendientes" => $pendientes,
        "notificaciones" => $notificaciones
    ),
    "contratos" => array(
        "venta_real_crea_notificacion" => true,
        "resolucion_real_cierra_notificacion" => true,
        "resolucion_backend_exige_confirmacion" => "RESOLVER PENDIENTE",
        "url_accion_notificacion" => "/inventario/productos_existencias#pendientes-pos",
        "no_ecommerce" => true,
        "no_mueve_inventario_en_preflight" => true
    ),
    "dry_run_venta" => $dryRunVenta,
    "expediente_ejemplo" => $expedienteEjemplo,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para solicitar UAT real: abrir turno, generar venta POS con inventario pendiente, validar notificacion, dry-run y resolver."
        : "Resolver bloqueos antes de solicitar UAT real."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function argValor($args, $clave, $default) {
    foreach ($args as $arg) {
        if (strpos($arg, $clave . "=") === 0) {
            return trim(substr($arg, strlen($clave) + 1), "\"' ");
        }
    }
    return $default;
}

function tablaExiste($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
        return false;
    }
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function columnaExiste($db, $tabla, $columna) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
        return false;
    }
    $stmt = $db->prepare("SHOW COLUMNS FROM `$tabla` LIKE :columna");
    $stmt->execute(array(":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function consultarDisponible($db, $idAlmacen, $idSku) {
    if (!tablaExiste($db, "erp_inventario_existencias")) {
        return array("cantidad" => 0, "disponible" => 0);
    }
    $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad),0) cantidad, COALESCE(SUM(cantidad_disponible),0) disponible
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=:almacen AND id_sku_erp=:sku");
    $stmt->execute(array(":almacen" => intval($idAlmacen), ":sku" => intval($idSku)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function consultarEstatusExistencias($db, $idAlmacen, $idSku) {
    if (!tablaExiste($db, "erp_inventario_existencias")) {
        return array("inconsistencias" => array());
    }
    $stmt = $db->prepare("SELECT id_existencia_inventario, codigo_existencia, cantidad,
            cantidad_disponible, cantidad_apartada, estatus_existencia, ultimo_movimiento_id
        FROM erp_inventario_existencias
        WHERE id_almacen_clave=:almacen
          AND id_sku_erp=:sku
          AND (cantidad<>0 OR cantidad_disponible<>0 OR cantidad_apartada<>0)
        ORDER BY id_existencia_inventario ASC");
    $stmt->execute(array(":almacen" => intval($idAlmacen), ":sku" => intval($idSku)));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $inconsistencias = array();
    foreach ($items as $item) {
        if (floatval($item["cantidad_disponible"]) > 0 && trim((string) $item["estatus_existencia"]) === "agotada") {
            $inconsistencias[] = $item;
        }
    }
    return array(
        "items" => $items,
        "inconsistencias" => $inconsistencias
    );
}

function consultarPoliticas($db, $idAlmacen, $idSku) {
    if (!tablaExiste($db, "erp_pos_politicas_venta_inventario")) {
        return array("activas" => array(), "total_activas" => 0);
    }
    $stmt = $db->prepare("SELECT id_politica_inventario_pos, codigo, id_almacen, id_sku_erp, canal,
            cantidad_maxima_pendiente, monto_maximo, estatus
        FROM erp_pos_politicas_venta_inventario
        WHERE estatus='activa'
          AND permite_inventario_pendiente=1
          AND id_almacen=:almacen
          AND (id_sku_erp IS NULL OR id_sku_erp=:sku)
        ORDER BY id_sku_erp DESC, id_politica_inventario_pos DESC
        LIMIT 10");
    $stmt->execute(array(":almacen" => intval($idAlmacen), ":sku" => intval($idSku)));
    $activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return array("activas" => $activas, "total_activas" => count($activas));
}

function consultarPendientes($db) {
    if (!tablaExiste($db, "erp_pos_inventario_pendientes")) {
        return array("abiertos" => 0, "resueltos" => 0, "ultimos" => array());
    }
    $stmt = $db->query("SELECT
        SUM(CASE WHEN estatus IN ('pendiente_revision','en_revision') THEN 1 ELSE 0 END) abiertos,
        SUM(CASE WHEN estatus='resuelto' THEN 1 ELSE 0 END) resueltos
        FROM erp_pos_inventario_pendientes");
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $db->query("SELECT folio, estatus, id_almacen, id_sku_erp, cantidad_pendiente, fecha_registro, fecha_resolucion
        FROM erp_pos_inventario_pendientes
        ORDER BY id_inventario_pendiente DESC
        LIMIT 5");
    return array(
        "abiertos" => intval(isset($resumen["abiertos"]) ? $resumen["abiertos"] : 0),
        "resueltos" => intval(isset($resumen["resueltos"]) ? $resumen["resueltos"] : 0),
        "ultimos" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

function consultarNotificaciones($db) {
    if (!tablaExiste($db, "erp_notificaciones")) {
        return array("abiertas" => 0, "resueltas" => 0, "ultimas" => array());
    }
    $stmt = $db->query("SELECT
        SUM(CASE WHEN estatus IN ('pendiente','en_revision','bloqueada') THEN 1 ELSE 0 END) abiertas,
        SUM(CASE WHEN estatus='resuelta' THEN 1 ELSE 0 END) resueltas
        FROM erp_notificaciones
        WHERE tipo='pos_venta_inventario_pendiente'");
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $db->query("SELECT id_notificacion, tipo, estatus, prioridad, url_accion, fecha_registro, fecha_resolucion
        FROM erp_notificaciones
        WHERE tipo='pos_venta_inventario_pendiente'
        ORDER BY id_notificacion DESC
        LIMIT 5");
    return array(
        "abiertas" => intval(isset($resumen["abiertas"]) ? $resumen["abiertas"] : 0),
        "resueltas" => intval(isset($resumen["resueltas"]) ? $resumen["resueltas"] : 0),
        "ultimas" => $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}
