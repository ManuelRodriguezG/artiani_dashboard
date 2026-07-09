<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: consultar correcciones de evidencias de caja POS sin modificar datos.
 * Impacto: permite validar folios de correccion y su relacion con evidencia/movimiento.
 * Contrato: read-only; no resuelve correcciones ni modifica evidencias.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idCorreccion = 0;
$idEvidencia = 0;
$estatus = "";
$limite = 50;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_correccion_evidencia_caja=") === 0) {
        $idCorreccion = intval(trim(substr($arg, 32), "\"' "));
    } elseif (strpos($arg, "--id_evidencia_caja=") === 0) {
        $idEvidencia = intval(trim(substr($arg, 20), "\"' "));
    } elseif (strpos($arg, "--estatus=") === 0) {
        $estatus = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosCajaEvidenciaCorreccionReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosCajaEvidenciaCorreccionReadonlyDb())->db();
$limite = $limite > 0 && $limite <= 200 ? $limite : 50;
$where = array("1=1");
$params = array();

if (!tablaExiste($db, "erp_pos_movimientos_caja_evidencias_correcciones")) {
    responder(array(
        "ok" => false,
        "modo" => "ventas_pos_caja_evidencia_correccion_readonly",
        "read_only" => true,
        "mensaje" => "Tabla de correcciones pendiente",
        "bloqueos" => array("schema_correcciones_pendiente")
    ));
}
if ($folio !== "") {
    $where[] = "corr.folio=:folio";
    $params[":folio"] = $folio;
}
if ($idCorreccion > 0) {
    $where[] = "corr.id_correccion_evidencia_caja=:correccion";
    $params[":correccion"] = $idCorreccion;
}
if ($idEvidencia > 0) {
    $where[] = "corr.id_evidencia_caja=:evidencia";
    $params[":evidencia"] = $idEvidencia;
}
if ($estatus !== "" && $estatus !== "todos") {
    $where[] = "corr.estatus=:estatus";
    $params[":estatus"] = $estatus;
}

$stmt = $db->prepare("SELECT corr.*,
        ev.estatus evidencia_estatus, ev.tipo_evidencia, ev.referencia_externa,
        mc.evidencia_estado movimiento_evidencia_estado, mc.tipo movimiento_tipo,
        mc.categoria movimiento_categoria, mc.monto, mc.referencia movimiento_referencia,
        v.folio folio_venta, d.folio folio_devolucion
    FROM erp_pos_movimientos_caja_evidencias_correcciones corr
    INNER JOIN erp_pos_movimientos_caja_evidencias ev ON ev.id_evidencia_caja=corr.id_evidencia_caja
    INNER JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=corr.id_movimiento_caja
    LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
    LEFT JOIN erp_ventas_devoluciones d ON d.id_movimiento_caja=mc.id_movimiento_caja
    WHERE " . implode(" AND ", $where) . "
    ORDER BY corr.fecha_solicitud DESC, corr.id_correccion_evidencia_caja DESC
    LIMIT " . intval($limite));
$stmt->execute($params);
$correcciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

responder(array(
    "ok" => true,
    "modo" => "ventas_pos_caja_evidencia_correccion_readonly",
    "read_only" => true,
    "filtros" => array(
        "folio" => $folio,
        "id_correccion_evidencia_caja" => $idCorreccion,
        "id_evidencia_caja" => $idEvidencia,
        "estatus" => $estatus,
        "limite" => $limite
    ),
    "total_registros" => count($correcciones),
    "correcciones" => $correcciones,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_resuelve_correccion" => true,
        "no_modifica_evidencia" => true,
        "no_modifica_caja" => true
    )
));

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
