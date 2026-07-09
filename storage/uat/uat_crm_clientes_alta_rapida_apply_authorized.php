<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: crear un cliente express CRM solo con autorizacion y respaldo.
 * Impacto: permite validar escritura minima de CRM Clientes sin migrar legacy/POS/ecommerce.
 * Contrato: escribe BD solo con --autorizar=CRM_CLIENTES_ALTA_EXPRESS y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

function validar_respaldo_crm_alta($respaldo) {
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
  "nombre::",
  "identificador::",
  "almacen::",
  "consentimiento::"
));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_alta($respaldo);

if ($autorizar !== "CRM_CLIENTES_ALTA_EXPRESS" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se creo cliente CRM. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "CRM_CLIENTES_ALTA_EXPRESS",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_un_cliente_express_crm" => true,
      "crea_identificador_principal" => true,
      "crea_evento_alta_express" => true,
      "migra_legacy" => false,
      "toca_ventas" => false,
      "toca_pos_legacy" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$datos = array(
  "nombre_publico" => isset($opciones["nombre"]) ? $opciones["nombre"] : "",
  "identificador" => isset($opciones["identificador"]) ? $opciones["identificador"] : "",
  "id_almacen" => isset($opciones["almacen"]) ? intval($opciones["almacen"]) : 0,
  "consentimiento_contacto" => isset($opciones["consentimiento"]) ? intval($opciones["consentimiento"]) : 0,
  "origen_alta" => "pos_uat"
);

$modelo = new ClientesCrm();
$respuesta = $modelo->altaRapidaCrearAutorizado($datos);
echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
