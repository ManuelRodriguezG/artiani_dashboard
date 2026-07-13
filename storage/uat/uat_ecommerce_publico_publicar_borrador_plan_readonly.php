<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: generar plan read-only para pasar una publicacion ecommerce de borrador a publicado.
 * Impacto: valida revision, disponibilidad y SQL antes de exponer productos al frontend.
 * Contrato: no ejecuta SQL, no publica SKUs y no toca inventario.
 */

$opciones = getopt("", array(
  "id_publicacion::",
  "id_sku::",
  "confirmar_revision::",
  "confirmar_agotado::"
));

$datos = array(
  "id_publicacion" => isset($opciones["id_publicacion"]) ? intval($opciones["id_publicacion"]) : 0,
  "id_sku" => isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 0,
  "confirmar_revision" => isset($opciones["confirmar_revision"]) ? intval($opciones["confirmar_revision"]) : 0,
  "confirmar_agotado" => isset($opciones["confirmar_agotado"]) ? intval($opciones["confirmar_agotado"]) : 0
);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$plan = $modelo->planPublicarBorrador($datos);

echo json_encode(array(
  "ok" => empty($plan["error"]),
  "modo" => "read-only",
  "entrada" => $datos,
  "plan" => $plan,
  "guardrails" => array(
    "no_ejecuta_sql" => true,
    "no_publica_sku" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  ),
  "siguiente_paso" => "Publicar solo con apply autorizado, revision confirmada y politica de agotados resuelta."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
