<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-30.
 * Proposito: validar contrato, esquema y preflights locales de Ecommerce Leads / Carritos.
 * Impacto: confirma captura de intencion comercial sin DDL, pedidos, ventas ni inventario.
 * Contrato: read-only; no ejecuta DDL ni persiste leads.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommerceLeadsEsquema.php";
require_once "../app/modelos/EcommerceLeadsErp.php";

$schema = new EcommerceLeadsEsquema();
$leads = new EcommerceLeadsErp();

$auditoria = $schema->auditarEcommerceLeads();
$plan = $schema->planActualizarEcommerceLeads(false);
$contrato = $leads->contratoFrontend();
$carrito = $leads->carritoSincronizar(payloadCarritoAnonimo());
$whatsapp = $leads->intentoPedido(payloadWhatsapp());
$contacto = $leads->contactoRegistrar(payloadContacto());
$pii = $leads->carritoEvento(array(
  "session_id" => "sess_leads_pii_001",
  "tipo_evento" => "cart_view",
  "telefono" => "3322068429",
  "items" => array(array("id_sku" => 456, "cantidad" => 1))
));

$bloqueos = array();
if (!empty($contrato["error"])) { $bloqueos[] = "contrato_error"; }
if (!empty($auditoria["error"])) { $bloqueos[] = "auditoria_error"; }
if (empty($plan["depurar"]["read_only"])) { $bloqueos[] = "plan_debe_ser_readonly"; }
if (empty($carrito["depurar"]["no_escribe_bd"])) { $bloqueos[] = "carrito_debe_no_escribir_bd"; }
if (($carrito["depurar"]["lead_normalizado"]["totales"]["subtotal"] ?? 0) <= 0) { $bloqueos[] = "carrito_subtotal_no_normalizado"; }
if (empty($carrito["depurar"]["lead_normalizado"]["validacion_items"])) { $bloqueos[] = "carrito_sin_resumen_validacion_items"; }
if (($carrito["depurar"]["lead_normalizado"]["items"][0]["validacion_publicacion"] ?? "") === "") { $bloqueos[] = "item_sin_validacion_publicacion"; }
if (($whatsapp["depurar"]["lead_normalizado"]["estatus"] ?? "") !== "whatsapp_abierto") { $bloqueos[] = "whatsapp_debe_marcar_abierto"; }
if (!empty($contacto["depurar"]["lead_normalizado"]["pii_fuera_contacto"])) { $bloqueos[] = "contacto_explicito_no_debe_bloquearse"; }
if (!in_array("datos_personales_fuera_de_contacto", $pii["depurar"]["bloqueos"] ?? array(), true)) { $bloqueos[] = "pii_fuera_contacto_debe_bloquearse"; }

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_frontend_leads" => empty($bloqueos) ? "puede_preparar_integracion_preflight" : "revisar_leads",
  "schema" => array(
    "tablas_faltantes" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0),
    "ddl_pendiente" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0) > 0,
    "plan_readonly" => $plan["depurar"]["plan"] ?? array()
  ),
  "preflights" => array(
    "carrito_anonimo" => resumenPreflight($carrito),
    "whatsapp" => resumenPreflight($whatsapp),
    "contacto" => resumenPreflight($contacto),
    "pii_fuera_contacto_bloqueada" => in_array("datos_personales_fuera_de_contacto", $pii["depurar"]["bloqueos"] ?? array(), true)
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_descuenta_inventario" => true,
    "no_sustituye_analytics" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function payloadCarritoAnonimo() {
  return array(
    "session_id" => "sess_leads_cart_001",
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
  $payload["session_id"] = "sess_leads_whatsapp_001";
  $payload["tipo_intento"] = "open_whatsapp";
  $payload["contacto"] = array("nombre" => "Cliente", "telefono" => "3322068429", "mensaje" => "", "acepta_whatsapp" => true, "acepta_politicas" => true);
  $payload["whatsapp"] = array("mensaje_generado" => "Hola, quiero cotizar estos productos...", "url_generada" => "https://wa.me/523322068429", "abierto" => true);
  return $payload;
}

function payloadContacto() {
  return array(
    "session_id" => "sess_leads_contacto_001",
    "canal" => "web_publica",
    "ruta" => "/contacto",
    "contacto" => array("nombre" => "Cliente", "telefono" => "3322068429", "mensaje" => "Quiero informacion", "acepta_whatsapp" => true, "acepta_politicas" => true)
  );
}

function resumenPreflight($respuesta) {
  $dep = $respuesta["depurar"] ?? array();
  return array(
    "ok" => empty($respuesta["error"]),
    "tipo" => $respuesta["tipo"] ?? "",
    "no_escribe_bd" => !empty($dep["no_escribe_bd"]),
    "estatus" => $dep["lead_normalizado"]["estatus"] ?? "",
    "validacion_items" => $dep["lead_normalizado"]["validacion_items"] ?? array(),
    "bloqueos" => $dep["bloqueos"] ?? array()
  );
}
