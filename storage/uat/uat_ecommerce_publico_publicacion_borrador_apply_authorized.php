<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: guardar una publicacion ecommerce como borrador solo con autorizacion explicita.
 * Impacto: crea/actualiza curaduria en `erp_ecommerce_publicaciones`; no publica automaticamente ni toca inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar, --respaldo e --id_sku.
 */

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "id_sku::",
  "slug::",
  "titulo::",
  "mascota::",
  "necesidades::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$idSku = isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 0;
$validacion = validarRespaldoPublicacionBorrador($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR" || !$validacion["ok"] || $idSku <= 0) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se guardo publicacion ecommerce. Falta token, respaldo valido o id_sku.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR",
      "respaldo" => "RUTA_O_REFERENCIA",
      "id_sku" => "SKU ERP publicable"
    ),
    "validacion_respaldo" => $validacion,
    "guardrails" => array(
      "no_publica_automaticamente" => true,
      "estatus_forzado" => "borrador",
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true,
      "requiere_ddl_previo" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$datos = array(
  "id_sku" => $idSku,
  "autorizar" => $autorizar,
  "estatus_publicacion" => "borrador"
);
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

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->guardarPublicacionBorradorAutorizada($datos, array("autorizar" => $autorizar));

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "validacion_respaldo" => $validacion,
  "entrada" => $datos,
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldoPublicacionBorrador($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}
