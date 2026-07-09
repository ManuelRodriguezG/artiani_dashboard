<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar configuracion POS despues de DDL/semillas sin vender ni abrir turno.
 * Impacto: confirma cajas, terminales, asignacion oficial y bloqueos restantes.
 * Contrato: read-only; no crea turnos, ventas, pagos ni movimientos de inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$alcance = "base";
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--alcance=") === 0) {
        $valorAlcance = strtolower(trim(substr($arg, 10), "\"' "));
        $alcance = $valorAlcance === "expandido" ? "expandido" : "base";
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

class UatVentasPosPostConfigDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$dbHelper = new UatVentasPosPostConfigDb();
$db = $dbHelper->db();
$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$auditoria = $esquema->auditarVentasPos($alcance);
$catalogos = $ventas->catalogosPos();
$diagnostico = $ventas->diagnosticoModuloVentas();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));

$conteos = array(
    "cajas" => tablaExiste($db, "erp_pos_cajas") ? contar($db, "erp_pos_cajas") : 0,
    "terminales" => tablaExiste($db, "erp_pos_terminales") ? contar($db, "erp_pos_terminales") : 0,
    "asignaciones_activas" => tablaExiste($db, "erp_pos_usuarios_cajas") ? contar($db, "erp_pos_usuarios_cajas", "estatus='activo'") : 0,
    "turnos_abiertos" => tablaExiste($db, "erp_pos_turnos") ? contar($db, "erp_pos_turnos", "estatus='abierto'") : 0
);

$bloqueos = array();
foreach (tablasPendientes(valor($auditoria, array("depurar"), array())) as $tabla) {
    $bloqueos[] = "Tabla pendiente: " . $tabla;
}
if ($idUsuario <= 0) {
    $bloqueos[] = "Ejecutar con --id_usuario=ID para validar asignacion oficial del cajero";
}
if (empty(valor($asignacion, array("depurar", "asignacion_activa"), false))) {
    foreach (valor($asignacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
}
if ($conteos["turnos_abiertos"] <= 0) {
    $bloqueos[] = "No hay turno abierto; correcto antes de implementar apertura real";
}

echo json_encode(array(
    "ok" => !$auditoria["error"] && !$catalogos["error"] && !$diagnostico["error"] && !$asignacion["error"],
    "modo" => "read-only",
    "alcance" => $alcance,
    "id_usuario" => $idUsuario,
    "conteos" => $conteos,
    "tablas_pendientes" => tablasPendientes(valor($auditoria, array("depurar"), array())),
    "diagnostico_hallazgos" => ids(valor($diagnostico, array("depurar", "hallazgos"), array())),
    "asignacion_actual" => array(
        "mensaje" => $asignacion["mensaje"],
        "schema_pendiente" => valor($asignacion, array("depurar", "schema_pendiente"), null),
        "asignacion_activa" => valor($asignacion, array("depurar", "asignacion_activa"), null),
        "modo_ui" => valor($asignacion, array("depurar", "modo_ui"), ""),
        "bloqueos" => valor($asignacion, array("depurar", "bloqueos"), array())
    ),
    "catalogos" => array(
        "almacenes" => count(valor($catalogos, array("depurar", "almacenes"), array())),
        "cajas" => count(valor($catalogos, array("depurar", "cajas"), array())),
        "turnos_abiertos" => count(valor($catalogos, array("depurar", "turnos_abiertos"), array())),
        "schema_cajas_pendiente" => valor($catalogos, array("depurar", "schema_cajas_pendiente"), null),
        "schema_turnos_pendiente" => valor($catalogos, array("depurar", "schema_turnos_pendiente"), null)
    ),
    "bloqueos_restantes" => array_values(array_unique($bloqueos)),
    "siguiente_paso" => empty($bloqueos)
        ? "Configuracion POS lista para implementar apertura de turno real."
        : "Resolver bloqueos restantes antes de habilitar turno/venta real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function contar($db, $tabla, $where = "1=1") {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "` WHERE " . $where);
    return intval($stmt->fetchColumn());
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function tablasPendientes($tablas) {
    $pendientes = array();
    foreach ($tablas as $tabla) {
        if (empty($tabla["existe"])) {
            $pendientes[] = $tabla["tabla"];
        }
    }
    return $pendientes;
}

function ids($hallazgos) {
    return array_values(array_filter(array_map(function ($item) {
        return isset($item["id"]) ? $item["id"] : null;
    }, $hallazgos)));
}
