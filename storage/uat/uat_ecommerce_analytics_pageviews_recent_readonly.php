<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-06.
 * Proposito: extraer los page views recientes de Ecommerce Analytics sin escribir BD.
 * Impacto: entrega historial anonimo para planeacion de contenido.
 * Contrato: solo SELECT; no expone session_id completo ni datos personales.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class EcommerceAnalyticsPageviewsRecentReadonlyProbe extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$probe = new EcommerceAnalyticsPageviewsRecentReadonlyProbe();
$db = $probe->db();
$items = array();
$limite = isset($argv[1]) ? max(1, min(500, intval($argv[1]))) : 50;
$modoPlano = isset($argv[2]) && $argv[2] === "urls";

if ($db) {
  $stmt = $db->prepare("SELECT fecha_registro, LEFT(session_id_hash, 12) session_key, ruta, dispositivo_aproximado
    FROM erp_ecommerce_analytics_eventos
    WHERE tipo_evento='page_view'
      AND fecha_registro BETWEEN :inicio AND :fin
    ORDER BY fecha_registro DESC, id_analytics_evento DESC
    LIMIT " . intval($limite));
  $stmt->execute(array(
    ":inicio" => date("Y-m-d H:i:s", strtotime("-30 days")),
    ":fin" => date("Y-m-d H:i:s")
  ));
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($modoPlano) {
  foreach ($items as $item) {
    echo (isset($item["ruta"]) ? $item["ruta"] : "") . PHP_EOL;
  }
  exit;
}

echo json_encode(array(
  "ok" => $db ? true : false,
  "modo" => "read-only",
  "limite" => $limite,
  "items" => $items,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_expone_session_id_completo" => true,
    "no_pii" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
