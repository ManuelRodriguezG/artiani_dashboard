<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-29.
 * Proposito: validar la capa experiencia cliente ecommerce despues de aplicar DDL o en estado actual.
 * Impacto: confirma tablas, politicas/taxonomia y preflights sin guardar facturacion ni analytics.
 * Contrato: read-only; no escribe BD, no registra solicitudes, no registra eventos y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$schema = new EcommercePublicoEsquema();
$api = new EcommerceCatalogoPublico();

$auditoria = $schema->auditarExperienciaCliente();
$politicas = $api->politicasPublicas();
$taxonomia = $api->taxonomiaMascotasPublica();
$facturacion = $api->facturacionSolicitudPreflight(array(
  "folio_compra" => "TICKET-POSTCHECK",
  "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente Postcheck", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
  "contacto" => array("correo" => "cliente@example.com", "telefono" => "3322068429"),
  "acepta_aviso_privacidad" => true
));
$evento = $api->eventoNavegacionPreflight(array(
  "session_id" => "sess_postcheck_exp",
  "tipo_evento" => "select_necesidad",
  "ruta" => "/catalogo",
  "mascota" => "perro",
  "necesidad" => "alimento"
));
$busqueda = $api->busquedaRegistrarPreflight(array(
  "session_id" => "sess_postcheck_exp",
  "query" => "premios cachorro",
  "mascota" => "perro",
  "necesidad" => "premio",
  "resultados_total" => 0
));

$bloqueos = array();
if (!is_array($auditoria) || !isset($auditoria["depurar"]["tablas_faltantes"])) {
  $bloqueos[] = "auditoria_experiencia_invalida";
}
if (!is_array($politicas["depurar"]["items"] ?? null)) {
  $bloqueos[] = "politicas_no_responden_items";
}
if (!is_array($taxonomia["depurar"]["mascotas"] ?? null) || !is_array($taxonomia["depurar"]["necesidades"] ?? null)) {
  $bloqueos[] = "taxonomia_no_responde_mascotas_necesidades";
}
foreach (array("facturacion" => $facturacion, "evento" => $evento, "busqueda" => $busqueda) as $nombre => $respuesta) {
  if (!empty($respuesta["error"]) || empty($respuesta["depurar"]["preflight"]) || empty($respuesta["depurar"]["no_escribe_bd"])) {
    $bloqueos[] = $nombre . "_preflight_no_ok";
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_experiencia_cliente" => empty($bloqueos) ? "verde_experiencia_cliente_postcheck_readonly" : "revisar_experiencia_cliente",
  "schema" => array(
    "tablas_faltantes" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0),
    "auditoria" => $auditoria["depurar"]["auditoria"] ?? array()
  ),
  "checks" => array(
    "politicas_items" => count($politicas["depurar"]["items"] ?? array()),
    "taxonomia_mascotas" => count($taxonomia["depurar"]["mascotas"] ?? array()),
    "taxonomia_necesidades" => count($taxonomia["depurar"]["necesidades"] ?? array()),
    "facturacion_preflight" => !empty($facturacion["depurar"]["preflight"]),
    "evento_preflight" => !empty($evento["depurar"]["preflight"]),
    "busqueda_preflight" => !empty($busqueda["depurar"]["preflight"])
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_facturacion" => true,
    "no_registra_tracking" => true,
    "no_crea_cliente" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
