<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: generar plan read-only de guardado de una publicacion ecommerce como borrador.
 * Impacto: valida SKU, curaduria, SQL y bloqueos antes de habilitar escrituras reales.
 * Contrato: no ejecuta SQL, no crea publicaciones, no publica SKUs y no toca inventario.
 */

$opciones = getopt("", array(
  "id_sku::",
  "slug::",
  "titulo::",
  "mascota::",
  "necesidades::",
  "estatus::"
));

$idSku = isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 1291;
$datos = array("id_sku" => $idSku);
if (isset($opciones["slug"])) {
  $datos["slug"] = trim((string) $opciones["slug"]);
}
if (isset($opciones["titulo"])) {
  $datos["titulo_publico"] = trim((string) $opciones["titulo"]);
}
if (isset($opciones["mascota"])) {
  $datos["mascota_especie"] = trim((string) $opciones["mascota"]);
}
if (isset($opciones["necesidades"])) {
  $datos["necesidades"] = trim((string) $opciones["necesidades"]);
}
if (isset($opciones["estatus"])) {
  $datos["estatus_publicacion"] = trim((string) $opciones["estatus"]);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$plan = $modelo->planGuardarPublicacion($datos);

echo json_encode(array(
  "ok" => empty($plan["error"]),
  "modo" => "read-only",
  "entrada" => $datos,
  "plan" => $plan,
  "guardrails" => array(
    "no_ejecuta_sql" => true,
    "no_crea_publicacion" => true,
    "no_publica_sku" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  ),
  "siguiente_paso" => "Revisar bloqueos_publicacion y SQL. Guardado real solo despues de DDL, permiso, auditoria y autorizacion."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
