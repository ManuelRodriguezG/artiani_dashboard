<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
 * Proposito: validar por HTTP los endpoints read-only de politicas y taxonomia del ecommerce publico.
 * Impacto: UAT frontend; confirma que la experiencia cliente puede avanzar sin POST real ni escrituras.
 * Contrato: read-only; no escribe BD, no registra eventos y no toca inventario.
 */

$base = "http://panel.com.local";
foreach ($argv as $arg) {
  if (strpos($arg, "--base=") === 0) {
    $base = rtrim(substr($arg, 7), "/");
  }
}

$urls = array(
  "politicas" => $base . "/ecommercePublico/politicas",
  "politica_facturacion" => $base . "/ecommercePublico/politica/facturacion",
  "taxonomia_mascotas" => $base . "/ecommercePublico/taxonomia_mascotas"
);

$resultados = array();
$ok = true;
foreach ($urls as $clave => $url) {
  $json = @file_get_contents($url);
  $data = json_decode((string) $json, true);
  $jsonOk = is_array($data);
  $endpointOk = $jsonOk && isset($data["error"]) && $data["error"] === false;
  $ok = $ok && $endpointOk;
  $resultados[$clave] = array(
    "url" => $url,
    "ok_json" => $jsonOk,
    "ok_endpoint" => $endpointOk,
    "tipo" => $jsonOk && isset($data["tipo"]) ? $data["tipo"] : null,
    "mensaje" => $jsonOk && isset($data["mensaje"]) ? $data["mensaje"] : null,
    "api_version" => $jsonOk && isset($data["api"]["version"]) ? $data["api"]["version"] : null
  );
}
$posts = array(
  "facturacion_solicitar" => requestExperienciaPost($base . "/ecommercePublico/facturacion_solicitar", array(
    "folio_compra" => "TICKET-123",
    "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente HTTP", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
    "contacto" => array("correo" => "cliente@example.com"),
    "acepta_aviso_privacidad" => true
  )),
  "evento_navegacion" => requestExperienciaPost($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => "sess_http_exp",
    "tipo_evento" => "facturacion_view",
    "ruta" => "/facturacion"
  )),
  "busqueda_registrar" => requestExperienciaPost($base . "/ecommercePublico/busqueda_registrar", array(
    "session_id" => "sess_http_exp",
    "query" => "jaula ave",
    "mascota" => "ave",
    "resultados_total" => 0
  ))
);
foreach ($posts as $post) {
  $ok = $ok && !empty($post["ok_endpoint"]) && !empty($post["preflight"]) && !empty($post["no_escribe_bd"]);
}

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "base" => $base,
  "senal_frontend_experiencia_http" => $ok ? "verde_experiencia_cliente_preflights_readonly" : "revisar_endpoints_experiencia",
  "endpoints" => $resultados,
  "preflights" => $posts,
  "frontend_puede_avanzar" => array(
    "politicas_desde_api" => $ok,
    "pagina_facturacion_con_politica" => $ok,
    "navegacion_mascota_necesidad_desde_api" => $ok,
    "facturacion_preflight" => $ok,
    "tracking_preflight" => $ok,
    "busqueda_preflight" => $ok
  ),
  "frontend_conectar_solo_como_preflight" => array(
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  ),
  "frontend_no_esperar_aun" => array(
    "persistencia_real",
    "factura_automatica",
    "analytics_con_datos_reales"
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestExperienciaPost($url, $body) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "POST",
      "header" => "Accept: application/json\r\nContent-Type: application/json\r\n",
      "content" => json_encode($body),
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $data = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "ok_json" => is_array($data),
    "ok_endpoint" => is_array($data) && isset($data["error"]) && $data["error"] === false,
    "preflight" => is_array($data) && !empty($data["depurar"]["preflight"]),
    "no_escribe_bd" => is_array($data) && !empty($data["depurar"]["no_escribe_bd"]),
    "tipo" => is_array($data) && isset($data["tipo"]) ? $data["tipo"] : null,
    "mensaje" => is_array($data) && isset($data["mensaje"]) ? $data["mensaje"] : null
  );
}
