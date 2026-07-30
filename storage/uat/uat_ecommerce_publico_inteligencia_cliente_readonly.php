<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-29.
 * Proposito: validar contrato interno read-only de inteligencia cliente ecommerce.
 * Impacto: prepara panel ERP para busquedas, navegacion, conversion y facturacion sin escribir BD.
 * Contrato: read-only; no registra eventos, no crea solicitudes y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$respuesta = $api->inteligenciaClienteInterna(array("limite" => 10));
$dep = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

$bloqueos = array();
foreach (array("resumen", "busquedas_frecuentes", "busquedas_sin_resultado", "mascotas_consultadas", "necesidades_consultadas", "conversion_whatsapp", "facturacion_por_estatus", "guardrails") as $key) {
  if (!array_key_exists($key, $dep)) {
    $bloqueos[] = "falta_" . $key;
  }
}
if (($dep["guardrails"]["no_escribe_bd"] ?? false) !== true) {
  $bloqueos[] = "debe_indicar_no_escribe_bd";
}
if (($dep["guardrails"]["no_expone_datos_personales"] ?? false) !== true) {
  $bloqueos[] = "debe_indicar_no_expone_datos_personales";
}

echo json_encode(array(
  "ok" => empty($bloqueos) && empty($respuesta["error"]),
  "modo" => "read-only",
  "senal_inteligencia_cliente" => empty($bloqueos) && empty($respuesta["error"]) ? "verde_inteligencia_cliente_readonly" : "revisar_inteligencia_cliente",
  "endpoint_interno" => "GET /ecommercePublico/inteligencia_cliente_erp",
  "configurado" => $dep["configurado"] ?? false,
  "tablas" => $dep["tablas"] ?? array(),
  "resumen" => $dep["resumen"] ?? array(),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_tracking" => true,
    "no_registra_facturacion" => true,
    "no_expone_datos_personales" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
