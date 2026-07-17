<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-17.
 * Proposito: evaluar si una reversa tecnica de DDL segmentos/listas podria considerarse sin ejecutar DROP.
 * Impacto: protege asignaciones reales de listas por segmento.
 * Contrato: read-only; no borra tablas, no modifica CRM, listas, POS ni ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class ListasSegmentosReversaReadOnly extends CRUD {
    public function conexion() {
        return $this->getConexion();
    }
}

$db = (new ListasSegmentosReversaReadOnly())->conexion();
$tabla = "erp_segmentos_listas_precios";
$detalle = array();
$bloqueos = array();
$advertencias = array();
$dropSql = array("DROP TABLE IF EXISTS `erp_segmentos_listas_precios`;");

if (!$db) {
    $bloqueos[] = "conexion_mysql_no_disponible";
} else {
    $existe = tablaExisteLpSegReversa($db, $tabla);
    $filas = null;
    $activas = null;
    if ($existe) {
        $filas = contarFilasLpSegReversa($db, $tabla, "1=1");
        $activas = contarFilasLpSegReversa($db, $tabla, "estatus='activo'");
        if ($filas > 0) {
            $bloqueos[] = "tabla_con_datos_" . $tabla;
        }
        if ($activas > 0) {
            $bloqueos[] = "existen_vinculos_segmento_lista_activos";
        }
    } else {
        $advertencias[] = "tabla_no_existe_" . $tabla;
    }
    $detalle[$tabla] = array(
        "existe" => $existe,
        "filas" => $filas,
        "vinculos_activos" => $activas,
        "vacia" => $existe ? $filas === 0 : null
    );
}

$reversaAplica = !empty($detalle[$tabla]["existe"]);

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "read-only",
    "etapa" => $reversaAplica ? (empty($bloqueos) ? "reversa_tecnica_considerable" : "reversa_bloqueada_por_datos") : "reversa_no_aplica_sin_tabla",
    "puede_considerar_reversa_tecnica" => $reversaAplica && empty($bloqueos),
    "detalle" => $detalle,
    "bloqueos" => $bloqueos,
    "advertencias" => $advertencias,
    "sql_reversa_no_ejecutado" => $dropSql,
    "condiciones" => array(
        "requiere_respaldo_externo" => true,
        "requiere_autorizacion_explicita_posterior" => true,
        "solo_si_tabla_vacia" => true,
        "no_usar_si_hay_vinculos_reales" => true,
        "preferir_restaurar_respaldo_si_hubo_operacion" => true
    ),
    "guardrails" => array(
        "no_ejecuta_drop" => true,
        "no_escribe_bd" => true,
        "no_toca_crm" => true,
        "no_toca_listas" => true,
        "no_toca_pos_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteLpSegReversa($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function contarFilasLpSegReversa($db, $tabla, $where) {
    $tablaSegura = str_replace("`", "``", $tabla);
    $stmt = $db->query("SELECT COUNT(*) FROM `" . $tablaSegura . "` WHERE " . $where);
    return intval($stmt->fetchColumn());
}
