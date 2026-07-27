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

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "base" => $base,
  "senal_frontend_experiencia_http" => $ok ? "verde_politicas_taxonomia_readonly" : "revisar_endpoints_experiencia",
  "endpoints" => $resultados,
  "frontend_puede_avanzar" => array(
    "politicas_desde_api" => $ok,
    "pagina_facturacion_con_politica" => $ok,
    "navegacion_mascota_necesidad_desde_api" => $ok
  ),
  "frontend_no_conectar_aun" => array(
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
