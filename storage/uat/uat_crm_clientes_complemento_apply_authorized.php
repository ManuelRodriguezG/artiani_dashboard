<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: crear complemento CRM solo con autorizacion y respaldo.
 * Impacto: permite agregar contacto, direccion, fiscal o nota con evento.
 * Contrato: escribe BD solo con --autorizar=CRM_CLIENTES_COMPLEMENTO y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

function validar_respaldo_crm_complemento($respaldo) {
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
  return array("ok" => $okReferencia && $okRuta, "referencia_presente" => $okReferencia, "parece_ruta_local" => $esRutaLocal, "archivo_existe" => $esRutaLocal ? $existe : null, "archivo_legible" => $esRutaLocal ? $legible : null, "tamano_bytes" => $tamano);
}

$opciones = getopt("", array("autorizar::", "respaldo::", "id::", "tipo_complemento::", "tipo::", "valor::", "nombre::", "rfc::", "razon::", "calle::", "cp::", "nota::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_complemento($respaldo);
if ($autorizar !== "CRM_CLIENTES_COMPLEMENTO" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se creo complemento CRM. Falta token o respaldo valido.",
    "requerido" => array("autorizar" => "CRM_CLIENTES_COMPLEMENTO", "respaldo" => "RUTA_O_REFERENCIA"),
    "validacion_respaldo" => $validacion,
    "alcance" => array("crea_un_complemento" => true, "crea_evento" => true, "migra_legacy" => false, "toca_ventas" => false)
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$tipoComplemento = isset($opciones["tipo_complemento"]) ? $opciones["tipo_complemento"] : "";
$datos = array(
  "id_cliente_crm" => isset($opciones["id"]) ? intval($opciones["id"]) : 0,
  "tipo" => isset($opciones["tipo"]) ? $opciones["tipo"] : "",
  "valor" => isset($opciones["valor"]) ? $opciones["valor"] : "",
  "nombre_contacto" => isset($opciones["nombre"]) ? $opciones["nombre"] : "",
  "rfc" => isset($opciones["rfc"]) ? $opciones["rfc"] : "",
  "razon_social" => isset($opciones["razon"]) ? $opciones["razon"] : "",
  "calle" => isset($opciones["calle"]) ? $opciones["calle"] : "",
  "codigo_postal" => isset($opciones["cp"]) ? $opciones["cp"] : "",
  "nota" => isset($opciones["nota"]) ? $opciones["nota"] : ""
);
$modelo = new ClientesCrm();
$respuesta = $modelo->complementoGuardarAutorizado($tipoComplemento, $datos);
echo json_encode(array("ok" => empty($respuesta["error"]), "modo" => "apply_authorized", "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "", "respuesta" => $respuesta), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
