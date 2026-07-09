<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: actualizar datos basicos de ficha CRM solo con autorizacion y respaldo.
 * Impacto: permite corregir identidad operativa con evento de auditoria.
 * Contrato: escribe BD solo con --autorizar=CRM_CLIENTES_FICHA_BASICA y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

function validar_respaldo_crm_ficha($respaldo) {
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
  "id::",
  "nombre::",
  "tipo::",
  "estatus::",
  "observaciones::"
));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_ficha($respaldo);

if ($autorizar !== "CRM_CLIENTES_FICHA_BASICA" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se actualizo ficha CRM. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "CRM_CLIENTES_FICHA_BASICA",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "actualiza_cliente_basico" => true,
      "crea_evento_edicion" => true,
      "modifica_identificadores" => false,
      "modifica_contactos" => false,
      "migra_legacy" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$modelo = new ClientesCrm();
$respuesta = $modelo->fichaBasicaGuardarAutorizado(array(
  "id_cliente_crm" => isset($opciones["id"]) ? intval($opciones["id"]) : 0,
  "nombre_publico" => isset($opciones["nombre"]) ? $opciones["nombre"] : "",
  "tipo_cliente" => isset($opciones["tipo"]) ? $opciones["tipo"] : "persona",
  "estatus" => isset($opciones["estatus"]) ? $opciones["estatus"] : "activo",
  "observaciones_operativas" => isset($opciones["observaciones"]) ? $opciones["observaciones"] : ""
));

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
