<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-28
 * Proposito: validar que una URL detectada por Analytics con srsltid se trate como incidencia SEO manual.
 * Impacto: Ecommerce SEO/Analytics; evita redirecciones automaticas y confirma redaccion de tracking.
 * Contrato: UAT read-only; no escribe BD, no crea reglas 301 ni modifica analytics.
 */

require_once __DIR__ . '/../../app/iniciador.php';
require_once __DIR__ . '/../../app/core/CRUD.php';
require_once __DIR__ . '/../../app/modelos/EcommerceCatalogoPublico.php';
require_once __DIR__ . '/../../app/modelos/EcommerceAnalyticsErp.php';

$modelo = new EcommerceCatalogoPublico();
$ref = new ReflectionClass($modelo);
$metodo = $ref->getMethod('seoIncidenciaAnalyticsDesdeRuta');
$metodo->setAccessible(true);
$analytics = new EcommerceAnalyticsErp();
$refAnalytics = new ReflectionClass($analytics);
$metodoAnalytics = $refAnalytics->getMethod('incidenciaSeoAnalyticsDesdeRuta');
$metodoAnalytics->setAccessible(true);

$fila = array(
  "ruta" => "/producto/Pez-cebra-verde-neon/PEZC-02?srsltid=AU7gw4X0EgRxllRfoMHqqmdGX_aOnsy_pcUG7y26L_mzkL6VpyR8pNxQ",
  "referrer" => "",
  "fuente_evento" => "page_view",
  "fuente_analytics" => "eventos",
  "total" => 1,
  "primera_fecha" => "2026-09-28 00:00:00",
  "ultima_fecha" => "2026-09-28 00:00:00"
);

$item = $metodo->invoke($modelo, $fila, array(), array());
$itemAnalytics = $metodoAnalytics->invoke($analytics, $fila["ruta"]);

$ok = is_array($item)
  && ($item["origen"] ?? "") === "analytics"
  && ($item["accion_sugerida"] ?? "") === "revisar_manual"
  && ($item["url_destino_sugerida"] ?? "") === ""
  && strpos((string) ($item["path_original"] ?? ""), "srsltid=__redacted__") !== false
  && strpos((string) ($item["path_original"] ?? ""), "AU7gw4X0") === false
  && in_array("srsltid", $item["tracking_params_detectados"] ?? array(), true)
  && in_array("producto_con_segmentos_extra", explode(", ", (string) ($item["motivo"] ?? "")), true)
  && is_array($itemAnalytics)
  && ($itemAnalytics["path_original"] ?? "") === ($item["path_original"] ?? "")
  && in_array("srsltid", $itemAnalytics["tracking_params_detectados"] ?? array(), true)
  && in_array("producto_con_segmentos_extra", $itemAnalytics["motivos"] ?? array(), true);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(array(
  "ok" => $ok,
  "senal_seo" => $ok ? "analytics_genera_incidencia_manual_srsltid" : "revisar_incidencia_analytics_srsltid",
  "item" => $item,
  "item_analytics_runtime" => $itemAnalytics,
  "guardrails" => array(
    "read_only" => true,
    "no_escribe_bd" => true,
    "no_crea_redireccion" => true,
    "sin_destino_automatico" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit($ok ? 0 : 1);
