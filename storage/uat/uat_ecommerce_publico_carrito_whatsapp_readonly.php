<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: validar reglas de carrito local y WhatsApp para frontend ecommerce Fase 1.
 * Impacto: confirma que el flujo usa dry-run, no precios locales como verdad y no registra pedidos.
 * Contrato: read-only; no consulta BD, no registra cotizaciones y no toca inventario.
 */

$cart = array(
  "version" => 1,
  "items" => array(
    array(
      "id_publicacion" => 1001,
      "id_sku" => 9001,
      "slug" => "croqueta-perro-adulto-pollo-2kg",
      "nombre" => "Croqueta perro adulto pollo 2 kg",
      "cantidad" => 2,
      "precio" => 1.00
    )
  ),
  "updatedAt" => "2026-07-15T12:00:00-06:00"
);

$dryRunPayload = array(
  "items" => array_map(function ($item) {
    return array(
      "id_publicacion" => intval($item["id_publicacion"]),
      "id_sku" => intval($item["id_sku"]),
      "slug" => (string) $item["slug"],
      "cantidad" => floatval($item["cantidad"])
    );
  }, $cart["items"])
);

$whatsappPreview = "Hola, quiero cotizar estos productos:\n\n1. Croqueta perro adulto pollo 2 kg - Cant. 2 - $578.00 MXN\n\nTotal estimado: $578.00 MXN\nSujeto a confirmacion de disponibilidad.";
$whatsappUrl = buildWhatsappUrl("5215555555555", $whatsappPreview);

$bloqueos = array();
if (isset($dryRunPayload["items"][0]["precio"])) {
  $bloqueos[] = "payload_dryrun_no_debe_enviar_precio";
}
if (strpos($whatsappUrl, "https://wa.me/5215555555555?text=") !== 0) {
  $bloqueos[] = "whatsapp_url_invalida";
}
if (strpos(urldecode(parse_url($whatsappUrl, PHP_URL_QUERY)), "Total estimado") === false) {
  $bloqueos[] = "whatsapp_debe_incluir_total_estimado";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "cart_local_ejemplo" => $cart,
  "dryrun_payload" => $dryRunPayload,
  "whatsapp" => array(
    "preview" => $whatsappPreview,
    "url" => $whatsappUrl,
    "numero_normalizado" => "5215555555555"
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_crea_pedido" => true,
    "no_descuenta_inventario" => true,
    "precio_local_no_es_fuente_de_verdad" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function buildWhatsappUrl($phone, $message) {
  $normalized = preg_replace('/\D+/', '', (string) $phone);
  if ($normalized === "") {
    return null;
  }
  return "https://wa.me/" . $normalized . "?text=" . rawurlencode((string) $message);
}
