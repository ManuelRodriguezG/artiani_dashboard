<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: aplicar migracion legacy CRM solo bajo token y respaldo externo.
 * Impacto: crea clientes CRM canonicos con vinculo externo auditable.
 * Contrato: escribe BD solo con --autorizar=CRM_CLIENTES_MIGRACION_LEGACY y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

function validar_respaldo_crm_migracion($respaldo) {
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
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "offset::",
  "limite::"
));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$offset = isset($opciones["offset"]) ? intval($opciones["offset"]) : 0;
$limite = isset($opciones["limite"]) ? intval($opciones["limite"]) : 25;
$validacion = validar_respaldo_crm_migracion($respaldo);

if ($autorizar !== "CRM_CLIENTES_MIGRACION_LEGACY" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se migro legacy CRM. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "CRM_CLIENTES_MIGRACION_LEGACY",
      "respaldo" => "RUTA_O_REFERENCIA",
      "offset" => $offset,
      "limite" => $limite
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "migra_solo_migrable_borrador" => true,
      "crea_clientes_crm" => true,
      "crea_identificadores" => true,
      "crea_vinculos_externos" => true,
      "crea_eventos" => true,
      "modifica_legacy" => false,
      "fusiona_duplicados" => false,
      "toca_ventas_pos_ecommerce" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$modelo = new ClientesCrm();
$respuesta = $modelo->migracionLegacyAplicarAutorizado($offset, $limite);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
