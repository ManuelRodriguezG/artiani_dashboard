<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: comparar baseline de ventas antes/despues de activar listas por segmento.
 * Impacto: detecta si el apply de segmentos altero ventas o detalles historicos.
 * Contrato: read-only; no escribe BD, no ejecuta DDL, no modifica ventas, listas ni clientes.
 */

$esperadoVentasTotal = obtenerArgBaseline("ventas_total", 22);
$esperadoVentasMax = obtenerArgBaseline("ventas_max_id", 25);
$esperadoDetalleTotal = obtenerArgBaseline("detalle_total", 23);
$esperadoDetalleMax = obtenerArgBaseline("detalle_max_id", 26);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class LpSegmentosVentasBaselineCompareDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new LpSegmentosVentasBaselineCompareDb())->db();
$bloqueos = array();

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
}

$actual = array(
    "ventas_total" => $db ? contadorBaseline($db, "erp_ventas") : null,
    "ventas_max_id" => $db ? maxIdBaseline($db, "erp_ventas", "id_venta") : null,
    "detalle_total" => $db ? contadorBaseline($db, "erp_ventas_detalle") : null,
    "detalle_max_id" => $db ? maxIdBaseline($db, "erp_ventas_detalle", "id_venta_detalle") : null
);

$esperado = array(
    "ventas_total" => $esperadoVentasTotal,
    "ventas_max_id" => $esperadoVentasMax,
    "detalle_total" => $esperadoDetalleTotal,
    "detalle_max_id" => $esperadoDetalleMax
);

$checks = array();
foreach ($esperado as $clave => $valorEsperado) {
    $ok = intval($actual[$clave]) === intval($valorEsperado);
    $checks[] = array(
        "id" => $clave,
        "ok" => $ok,
        "esperado" => intval($valorEsperado),
        "actual" => $actual[$clave] === null ? null : intval($actual[$clave])
    );
    if (!$ok) {
        $bloqueos[] = "baseline_cambio_" . $clave;
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "resultado" => empty($bloqueos) ? "BASELINE_VENTAS_INTACTA" : "BASELINE_VENTAS_CAMBIO",
    "esperado" => $esperado,
    "actual" => $actual,
    "checks" => $checks,
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "segmentos_no_debe_crear_ventas" => true,
        "segmentos_no_debe_crear_detalles" => true,
        "segmentos_no_debe_modificar_snapshots_historicos" => true
    ),
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function obtenerArgBaseline($nombre, $default) {
    global $argv;
    $prefijo = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefijo) === 0) {
            return intval(substr($arg, strlen($prefijo)));
        }
    }
    return intval($default);
}

function contadorBaseline($db, $tabla) {
    if (!tablaBaselineExiste($db, $tabla)) {
        return null;
    }
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "``", $tabla) . "`");
    return intval($stmt->fetchColumn());
}

function maxIdBaseline($db, $tabla, $columna) {
    if (!tablaBaselineExiste($db, $tabla)) {
        return null;
    }
    $stmt = $db->query("SELECT MAX(`" . str_replace("`", "``", $columna) . "`) FROM `" . str_replace("`", "``", $tabla) . "`");
    return intval($stmt->fetchColumn());
}

function tablaBaselineExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}
