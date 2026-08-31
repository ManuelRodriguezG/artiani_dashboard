<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-30.
 * Proposito: validar por HTTP los endpoints publicos de Ecommerce Leads / Carritos.
 * Impacto: confirma contrato consumible por frontend sin DDL ni persistencia real.
 * Contrato: read-only; no crea pedidos, ventas ni inventario.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$pruebas = array(
  "leads_contrato" => requestLeadsHttp($base . "/ecommercePublico/leads_contrato"),
  "carrito_sincronizar" => requestLeadsHttp($base . "/ecommercePublico/carrito_sincronizar", payloadCarritoAnonimo()),
  "carrito_evento" => requestLeadsHttp($base . "/ecommercePublico/carrito_evento", array(
    "session_id" => "sess_http_leads_001",
    "tipo_evento" => "cart_view",
    "ruta" => "/carrito",
    "items" => array(array("id_publicacion" => 123, "id_sku" => 456, "slug" => "alimento-x", "cantidad" => 1, "precio_unitario" => 150))
  )),
  "intento_pedido" => requestLeadsHttp($base . "/ecommercePublico/intento_pedido", payloadWhatsapp()),
  "contacto_registrar" => requestLeadsHttp($base . "/ecommercePublico/contacto_registrar", payloadContacto()),
  "facturacion_solicitud_registrar" => requestLeadsHttp($base . "/ecommercePublico/facturacion_solicitud_registrar", array(
    "session_id" => "sess_http_leads_fact_001",
    "ruta" => "/facturacion",
    "contacto" => array("nombre" => "Cliente", "telefono" => "3322068429", "acepta_politicas" => true)
  )),
  "pii_fuera_contacto" => requestLeadsHttp($base . "/ecommercePublico/carrito_evento", array(
    "session_id" => "sess_http_leads_pii_001",
    "tipo_evento" => "cart_view",
    "telefono" => "3322068429",
    "items" => array(array("id_sku" => 456, "cantidad" => 1))
  ))
);

$bloqueos = array();
foreach (array("leads_contrato", "carrito_sincronizar", "carrito_evento", "intento_pedido", "contacto_registrar", "facturacion_solicitud_registrar") as $clave) {
  if (empty($pruebas[$clave]["json_valido"])) { $bloqueos[] = $clave . "_no_json"; }
  if (!empty($pruebas[$clave]["error"])) { $bloqueos[] = $clave . "_error"; }
}
foreach (array("carrito_sincronizar", "carrito_evento", "intento_pedido", "contacto_registrar", "facturacion_solicitud_registrar") as $clave) {
  if (empty($pruebas[$clave]["no_escribe_bd"])) { $bloqueos[] = $clave . "_debe_no_escribir_bd"; }
}
if (!in_array("datos_personales_fuera_de_contacto", $pruebas["pii_fuera_contacto"]["bloqueos_payload"], true)) {
  $bloqueos[] = "pii_fuera_contacto_debe_bloquearse";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base" => $base,
  "senal_frontend_leads_http" => empty($bloqueos) ? "verde_preflight_sin_persistencia" : "revisar_leads_http",
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_descuenta_inventario" => true,
    "no_sustituye_analytics" => true
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
    "estatus" => $depurar["lead_normalizado"]["estatus"] ?? "",
    "bloqueos_payload" => isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "raw_inicio" => substr((string) $raw, 0, 140)
  );
}

function payloadCarritoAnonimo() {
  return array(
    "session_id" => "sess_http_leads_001",
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
    "metadata" => array("origen" => "cart_update")
  );
}

function payloadWhatsapp() {
  $payload = payloadCarritoAnonimo();
  $payload["session_id"] = "sess_http_leads_whatsapp_001";
  $payload["tipo_intento"] = "open_whatsapp";
  $payload["contacto"] = array("nombre" => "Cliente", "telefono" => "3322068429", "mensaje" => "", "acepta_whatsapp" => true, "acepta_politicas" => true);
  $payload["whatsapp"] = array("mensaje_generado" => "Hola, quiero cotizar estos productos...", "url_generada" => "https://wa.me/523322068429", "abierto" => true);
  return $payload;
}

function payloadContacto() {
  return array(
    "session_id" => "sess_http_leads_contacto_001",
    "canal" => "web_publica",
    "ruta" => "/contacto",
    "contacto" => array("nombre" => "Cliente", "telefono" => "3322068429", "mensaje" => "Quiero informacion", "acepta_whatsapp" => true, "acepta_politicas" => true)
  );
}
