<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-16.
 * Proposito: validar que una URL vieja pueda redirigirse manualmente a /contacto.
 * Impacto: read-only; reproduce el plan SEO sin guardar la redireccion.
 * Contrato: no escribe BD, no crea redirecciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$from = isset($argv[1]) ? trim((string) $argv[1]) : "/marca/proveedor-descontinuado";
$to = isset($argv[2]) ? trim((string) $argv[2]) : "contacto";

$catalogo = new EcommerceCatalogoPublico();
$respuesta = $catalogo->seoRedireccionPlanInterno(array(
  "from" => $from,
  "to" => $to,
  "status" => 301,
  "tipo" => "manual",
  "motivo" => "marca_o_proveedor_temporalmente_no_manejado"
));

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
