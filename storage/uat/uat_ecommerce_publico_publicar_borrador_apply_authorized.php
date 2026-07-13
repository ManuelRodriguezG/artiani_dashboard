<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: publicar un borrador ecommerce solo con autorizacion explicita.
 * Impacto: expone un SKU al catalogo publico; no toca inventario ni legacy `ecom_*`.
 * Contrato: bloqueado por defecto; requiere token, respaldo y confirmacion de revision.
 */

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "id_publicacion::",
  "id_sku::",
  "confirmar_revision::",
  "confirmar_agotado::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoPublicarBorrador($respaldo);
$datos = array(
  "id_publicacion" => isset($opciones["id_publicacion"]) ? intval($opciones["id_publicacion"]) : 0,
  "id_sku" => isset($opciones["id_sku"]) ? intval($opciones["id_sku"]) : 0,
  "confirmar_revision" => isset($opciones["confirmar_revision"]) ? intval($opciones["confirmar_revision"]) : 0,
  "confirmar_agotado" => isset($opciones["confirmar_agotado"]) ? intval($opciones["confirmar_agotado"]) : 0
);

if ($autorizar !== "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR" || !$validacion["ok"] || ($datos["id_publicacion"] <= 0 && $datos["id_sku"] <= 0)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se publico borrador ecommerce. Falta token, respaldo valido o identificador.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR",
      "respaldo" => "RUTA_O_REFERENCIA",
      "id_publicacion_o_id_sku" => "Publicacion borrador existente",
      "confirmar_revision" => "1"
    ),
    "validacion_respaldo" => $validacion,
    "guardrails" => array(
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true,
      "no_publica_sin_revision" => true,
      "requiere_ddl_previo" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->publicarBorradorAutorizado($datos, array("autorizar" => $autorizar));

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "validacion_respaldo" => $validacion,
  "entrada" => $datos,
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldoPublicarBorrador($respaldo) {
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
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}
