<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: validar por HTTP la persistencia publica real de Ecommerce Leads / Carritos.
 * Impacto: registra leads UAT; no crea pedidos, ventas, cotizaciones reales ni inventario.
 * Contrato: requiere esquema aplicado y ECOMMERCE_LEADS_PUBLICO=true.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$sessionId = "sess_http_leads_real_" . date("Ymd_His");

$pruebas = array(
  "leads_contrato" => requestLeadsHttp($base . "/ecommercePublico/leads_contrato"),
  "carrito_sincronizar" => requestLeadsHttp($base . "/ecommercePublico/carrito_sincronizar", payloadCarrito($sessionId)),
  "intento_pedido" => requestLeadsHttp($base . "/ecommercePublico/intento_pedido", payloadWhatsapp($sessionId)),
  "pii_fuera_contacto" => requestLeadsHttp($base . "/ecommercePublico/carrito_evento", array(
    "session_id" => $sessionId . "_pii",
    "tipo_evento" => "cart_view",
    "telefono" => "3322068429",
    "items" => array(array("id_sku" => 456, "cantidad" => 1))
  ))
);

$bloqueos = array();
$contrato = $pruebas["leads_contrato"]["depurar"] ?? array();
if (($contrato["estado"] ?? "") !== "persistencia_publica_activa") {
  $bloqueos[] = "contrato_no_indica_persistencia_activa";
}
foreach (array("carrito_sincronizar", "intento_pedido") as $clave) {
  if (empty($pruebas[$clave]["json_valido"])) { $bloqueos[] = $clave . "_no_json"; }
  if (!empty($pruebas[$clave]["error"])) { $bloqueos[] = $clave . "_error"; }
  if (empty($pruebas[$clave]["escribe_bd"])) { $bloqueos[] = $clave . "_no_escribio_bd"; }
}
if (!in_array("datos_personales_fuera_de_contacto", $pruebas["pii_fuera_contacto"]["bloqueos_payload"], true)) {
  $bloqueos[] = "pii_fuera_contacto_debe_bloquearse";
}
if (empty($pruebas["pii_fuera_contacto"]["no_escribe_bd"])) {
  $bloqueos[] = "pii_no_debe_escribir_bd";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "persistencia-publica",
  "base" => $base,
  "session_id_uat" => $sessionId,
  "senal_frontend_leads_http" => empty($bloqueos) ? "verde_persistencia_publica_leads" : "revisar_leads_persistencia",
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "registra_leads" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_descuenta_inventario" => true,
    "pii_solo_contacto" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestLeadsHttp($url, $body = null) {
  $method = $body === null ? "GET" : "POST";
  $headers = "Accept: application/json\r\n";
  $content = null;
  if ($body !== null) {
    $content = json_encode($body);
    $headers .= "Content-Type: application/json\r\n";
  }
  $context = stream_context_create(array(
    "http" => array(
      "method" => $method,
      "header" => $headers,
      "content" => $content,
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  $depurar = is_array($json) && isset($json["depurar"]) && is_array($json["depurar"]) ? $json["depurar"] : array();
  return array(
    "url" => $url,
    "method" => $method,
    "json_valido" => is_array($json),
    "error" => is_array($json) ? (bool) ($json["error"] ?? true) : true,
    "tipo" => is_array($json) ? ($json["tipo"] ?? "") : "",
    "mensaje" => is_array($json) ? ($json["mensaje"] ?? "") : "",
    "preflight" => !empty($depurar["preflight"]),
    "no_escribe_bd" => !empty($depurar["no_escribe_bd"]),
    "escribe_bd" => !empty($depurar["escribe_bd"]),
    "id_carrito_lead" => intval($depurar["id_carrito_lead"] ?? 0),
    "bloqueos_payload" => isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "depurar" => $depurar,
    "raw_inicio" => substr((string) $raw, 0, 140)
  );
}

function payloadCarrito($sessionId) {
  return array(
    "session_id" => $sessionId,
    "canal" => "web_publica",
    "ruta" => "/carrito",
    "items" => array(array(
      "id_publicacion" => 123,
      "id_sku" => 456,
      "slug" => "alimento-x",
      "sku" => "ABC-123",
      "nombre" => "Producto ejemplo",
      "cantidad" => 2,
      "precio_unitario" => 150,
      "subtotal" => 300
    )),
    "totales" => array("items_total" => 1, "piezas_total" => 2, "subtotal" => 300),
    "metadata" => array("origen" => "uat_persistencia_publica")
  );
}

function payloadWhatsapp($sessionId) {
  $payload = payloadCarrito($sessionId);
  $payload["tipo_intento"] = "open_whatsapp";
  $payload["contacto"] = array("nombre" => "Cliente UAT", "telefono" => "3322068429", "mensaje" => "", "acepta_whatsapp" => true, "acepta_politicas" => true);
  $payload["whatsapp"] = array("mensaje_generado" => "Hola, quiero cotizar estos productos...", "url_generada" => "https://wa.me/523322068429", "abierto" => true);
  return $payload;
}
