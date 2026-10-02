<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-29.
 * Proposito: diagnosticar volumen y calidad de Ecommerce Analytics sin escribir BD.
 * Impacto: ayuda a detectar sesiones anonimas inestables, rafagas de page views y eventos duplicados.
 * Contrato: solo SELECT; no modifica esquema, sesiones, eventos, busquedas ni conversiones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

class EcommerceAnalyticsDiagnosticoReadonlyProbe extends EcommerceAnalyticsErp {
  public function db() {
    return $this->getConexion();
  }
}

function q($db, $sql, $params = array()) {
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function escalar($db, $sql, $params = array()) {
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetchColumn();
}

$probe = new EcommerceAnalyticsDiagnosticoReadonlyProbe();
$db = $probe->db();
$desde = date("Y-m-d H:i:s", strtotime("-24 hours"));
$hasta = date("Y-m-d H:i:s");

$salida = array(
  "ok" => $db ? true : false,
  "modo" => "read-only",
  "rango" => array("desde" => $desde, "hasta" => $hasta),
  "no_escribe_bd" => true
);

if ($db) {
  $sesionesTotal = intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :desde AND :hasta", array(":desde" => $desde, ":hasta" => $hasta)));
  $sesionesUnEvento = intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :desde AND :hasta AND eventos_total<=1", array(":desde" => $desde, ":hasta" => $hasta)));
  $eventosTotal = intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta", array(":desde" => $desde, ":hasta" => $hasta)));
  $sessionesDistintasEventos = intval(escalar($db, "SELECT COUNT(DISTINCT session_id_hash) FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta", array(":desde" => $desde, ":hasta" => $hasta)));

  $salida["resumen_24h"] = array(
    "sesiones_total" => $sesionesTotal,
    "sesiones_un_evento" => $sesionesUnEvento,
    "sesiones_un_evento_pct" => $sesionesTotal > 0 ? round(($sesionesUnEvento / $sesionesTotal) * 100, 2) : 0,
    "eventos_total" => $eventosTotal,
    "sesiones_distintas_en_eventos" => $sessionesDistintasEventos,
    "eventos_por_sesion_eventos" => $sessionesDistintasEventos > 0 ? round($eventosTotal / $sessionesDistintasEventos, 2) : 0
  );

  $salida["eventos_por_tipo_24h"] = q($db, "SELECT tipo_evento, COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta GROUP BY tipo_evento ORDER BY total DESC", array(":desde" => $desde, ":hasta" => $hasta));
  $salida["rafagas_por_minuto_top"] = q($db, "SELECT DATE_FORMAT(fecha_registro, '%Y-%m-%d %H:%i') minuto, COUNT(*) eventos, COUNT(DISTINCT session_id_hash) sesiones FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m-%d %H:%i') ORDER BY eventos DESC LIMIT 15", array(":desde" => $desde, ":hasta" => $hasta));
  $salida["sesiones_con_mas_eventos_24h"] = q($db, "SELECT LEFT(session_id_hash, 12) session_key, eventos_total, primer_ruta, ultimo_ruta, fecha_inicio, fecha_ultima_actividad FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :desde AND :hasta ORDER BY eventos_total DESC, COALESCE(fecha_ultima_actividad, fecha_inicio) DESC LIMIT 15", array(":desde" => $desde, ":hasta" => $hasta));
  $salida["rutas_pageview_top_24h"] = q($db, "SELECT ruta, COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta AND tipo_evento='page_view' GROUP BY ruta ORDER BY total DESC LIMIT 15", array(":desde" => $desde, ":hasta" => $hasta));
  $salida["productos_vistos_top_24h"] = q($db, "SELECT slug, id_publicacion, id_sku, COUNT(*) total, COUNT(DISTINCT session_id_hash) sesiones FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta AND tipo_evento='view_product' GROUP BY slug, id_publicacion, id_sku ORDER BY total DESC LIMIT 15", array(":desde" => $desde, ":hasta" => $hasta));
  $salida["calidad_columnas_hash"] = array(
    "sesiones_ip_hash_poblado" => intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :desde AND :hasta AND TRIM(COALESCE(ip_hash,''))<>''", array(":desde" => $desde, ":hasta" => $hasta))),
    "sesiones_user_agent_hash_poblado" => intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_sesiones WHERE COALESCE(fecha_ultima_actividad, fecha_inicio) BETWEEN :desde AND :hasta AND TRIM(COALESCE(user_agent_hash,''))<>''", array(":desde" => $desde, ":hasta" => $hasta))),
    "eventos_ip_hash_poblado" => intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta AND TRIM(COALESCE(ip_hash,''))<>''", array(":desde" => $desde, ":hasta" => $hasta))),
    "eventos_user_agent_hash_poblado" => intval(escalar($db, "SELECT COUNT(*) FROM erp_ecommerce_analytics_eventos WHERE fecha_registro BETWEEN :desde AND :hasta AND TRIM(COALESCE(user_agent_hash,''))<>''", array(":desde" => $desde, ":hasta" => $hasta)))
  );
  $salida["diagnostico"] = array(
    "sesiones_un_evento_alto" => $sesionesTotal > 0 && ($sesionesUnEvento / max(1, $sesionesTotal)) >= 0.65,
    "lectura" => "Si sesiones_un_evento_pct es alto, el contador de sesiones esta inflado por session_id inestable, navegadores sin localStorage persistente, crawlers o pruebas automatizadas.",
    "siguiente_revision_frontend" => "Confirmar que localStorage['artiani_ecommerce_session_id'] permanece igual al navegar y que no se carga el SDK/adaptador dos veces."
  );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
