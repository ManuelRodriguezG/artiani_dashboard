<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: inspeccionar ultimos registros reales de Ecommerce Analytics sin escribir BD.
 * Impacto: diagnostico productivo read-only para confirmar si el frontend ya envia eventos.
 * Contrato: solo SELECT; no crea tablas, no modifica eventos y no expone session_id completo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

class EcommerceAnalyticsLatestReadonlyProbe extends EcommerceAnalyticsErp {
  public function db() {
    return $this->getConexion();
  }
}

$analytics = new EcommerceAnalyticsLatestReadonlyProbe();
$contrato = $analytics->contratoFrontend();
$db = $analytics->db();

$eventos = array();
$sesiones = array();

if ($db) {
  $stmt = $db->query("SELECT tipo_evento, canal, ruta, slug, fecha_registro
    FROM erp_ecommerce_analytics_eventos
    ORDER BY id_analytics_evento DESC
    LIMIT 12");
  $eventos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

  $stmt = $db->query("SELECT LEFT(session_id_hash, 12) session_id_hash_corto, canal, primer_ruta, ultimo_ruta, fecha_inicio, fecha_ultima_actividad, eventos_total
    FROM erp_ecommerce_analytics_sesiones
    ORDER BY COALESCE(fecha_ultima_actividad, fecha_inicio) DESC
    LIMIT 12");
  $sesiones = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
}

echo json_encode(array(
  "ok" => $db ? true : false,
  "modo" => "read-only",
  "persistencia" => $contrato["depurar"]["persistencia"] ?? array(),
  "ultimos_eventos" => $eventos,
  "ultimas_sesiones" => $sesiones,
  "diagnostico" => array(
    "si_no_cambia_al_navegar" => "El frontend publico probablemente no esta cargando analytics-tracker-publico.js o apunta a otro endpointBase.",
    "no_expone_session_id_completo" => true,
    "no_escribe_bd" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
