<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-14.
 * Proposito: diagnosticar incidencias y notificaciones de una venta POS cancelada.
 * Impacto: solo lectura; no modifica ventas, incidencias, notificaciones, caja ni inventario.
 * Contrato: recibe --folio=... y devuelve evidencia JSON acotada al folio solicitado.
 */

chdir(__DIR__ . "/../..");
require_once "app/iniciador.php";

$folio = "";
foreach ($argv as $argumento) {
    if (strpos($argumento, "--folio=") === 0) {
        $folio = trim(substr($argumento, 8));
    }
}

$salida = array(
    "ok" => false,
    "modo" => "diagnostico_cancelacion_folio_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_borrar" => true,
        "no_actualizar" => true,
        "no_mover_inventario" => true,
        "no_mover_caja" => true
    ),
    "folio_consultado" => $folio,
    "conexion" => array(
        "base" => defined("MYSQLBASE") ? MYSQLBASE : null,
        "host" => defined("MYSQLHOST") ? MYSQLHOST : null
    )
);

function consulta($pdo, $sql, $parametros = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tablaExisteReadonly($pdo, $tabla) {
    $filas = consulta($pdo, "SELECT COUNT(*) AS total
        FROM information_schema.tables
        WHERE table_schema=DATABASE() AND table_name=:tabla", array(":tabla" => $tabla));
    return !empty($filas) && intval($filas[0]["total"]) > 0;
}

function columnaExisteReadonly($pdo, $tabla, $columna) {
    $filas = consulta($pdo, "SELECT COUNT(*) AS total
        FROM information_schema.columns
        WHERE table_schema=DATABASE() AND table_name=:tabla AND column_name=:columna", array(
            ":tabla" => $tabla,
            ":columna" => $columna
        ));
    return !empty($filas) && intval($filas[0]["total"]) > 0;
}

try {
    if ($folio === "") {
        throw new RuntimeException("Falta --folio=POS-...");
    }

    $pdo = new PDO(
        "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE,
        MYSQLUSER,
        MYSQLPASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4", PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );

    $ventas = consulta($pdo, "SELECT id_venta, folio, estatus, fecha_venta, fecha_cancelacion,
            cancelado_por, motivo_cancelacion, id_caja, id_almacen, id_turno_caja,
            total, pagado_total, inventario_validacion_estado, inventario_pendiente_total
        FROM erp_ventas WHERE folio=:folio", array(":folio" => $folio));
    $salida["venta"] = !empty($ventas) ? $ventas[0] : null;

    $idVenta = !empty($ventas) ? intval($ventas[0]["id_venta"]) : 0;
    $salida["detalles"] = $idVenta > 0 ? consulta($pdo, "SELECT id_venta_detalle, id_sku_erp, sku,
            descripcion, tipo_partida, origen_partida, estatus, cantidad_base, total,
            id_almacen, id_inventario_pendiente
        FROM erp_ventas_detalle WHERE id_venta=:venta ORDER BY id_venta_detalle", array(":venta" => $idVenta)) : array();

    $salida["inventario_pendientes"] = array();
    if ($idVenta > 0 && tablaExisteReadonly($pdo, "erp_pos_inventario_pendientes")) {
        $salida["inventario_pendientes"] = consulta($pdo, "SELECT id_inventario_pendiente, folio,
                id_venta, id_venta_detalle, id_almacen, sku, descripcion, cantidad_pendiente,
                estatus, id_notificacion, fecha_registro, fecha_revision, fecha_resolucion,
                motivo_revision
            FROM erp_pos_inventario_pendientes WHERE id_venta=:venta
            ORDER BY id_inventario_pendiente", array(":venta" => $idVenta));
    }

    $salida["venta_rapida_pendientes"] = array();
    if ($idVenta > 0 && tablaExisteReadonly($pdo, "erp_pos_venta_rapida_pendientes")) {
        $salida["venta_rapida_pendientes"] = consulta($pdo, "SELECT id_venta_rapida_pendiente, folio,
                id_venta, id_venta_detalle, descripcion_manual, estatus, id_sku_erp_resuelto,
                fecha_registro, fecha_resolucion, fecha_actualizacion
            FROM erp_pos_venta_rapida_pendientes WHERE id_venta=:venta
            ORDER BY id_venta_rapida_pendiente", array(":venta" => $idVenta));
    }

    $salida["incidencias_catalogo"] = array();
    if ($idVenta > 0 && tablaExisteReadonly($pdo, "erp_catalogo_incidencias_calidad")) {
        $salida["incidencias_catalogo"] = consulta($pdo, "SELECT id_incidencia_calidad, origen,
                tipo_incidencia, referencia_tipo, id_referencia, estatus, fecha_registro,
                fecha_resolucion, fecha_actualizacion
            FROM erp_catalogo_incidencias_calidad
            WHERE (referencia_tipo='erp_pos_venta_rapida_pendientes'
                AND id_referencia IN (SELECT id_venta_rapida_pendiente
                    FROM erp_pos_venta_rapida_pendientes WHERE id_venta=:venta))
            ORDER BY id_incidencia_calidad", array(":venta" => $idVenta));
    }

    $salida["notificaciones"] = array();
    if (tablaExisteReadonly($pdo, "erp_notificaciones")) {
        $condiciones = array("titulo LIKE :folio", "url_accion LIKE :folio");
        if (columnaExisteReadonly($pdo, "erp_notificaciones", "datos_json")) {
            $condiciones[] = "datos_json LIKE :folio";
        }
        $sql = "SELECT id_notificacion, tipo, modulo_origen, entidad_origen, id_entidad_origen,
                area_responsable, titulo, estatus, fecha_registro, fecha_resolucion,
                fecha_actualizacion, url_accion
            FROM erp_notificaciones WHERE " . implode(" OR ", $condiciones) . "
            ORDER BY id_notificacion DESC";
        $salida["notificaciones"] = consulta($pdo, $sql, array(":folio" => "%" . $folio . "%"));
    }

    $salida["ok"] = true;
} catch (Throwable $e) {
    $salida["error"] = $e->getMessage();
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
