<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: guardar configuracion publica ecommerce solo con autorizacion explicita.
 * Impacto: activa WhatsApp, CORS, URL y textos visibles del canal publico; no toca inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 y --respaldo.
 */

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "whatsapp::",
  "cors::",
  "url::",
  "moneda::",
  "mensaje::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoConfigEcommerce($respaldo);

$valores = array();
if (isset($opciones["whatsapp"])) {
  $valores["whatsapp_numero_principal"] = trim((string) $opciones["whatsapp"]);
}
if (isset($opciones["cors"])) {
  $valores["cors_origenes_permitidos"] = trim((string) $opciones["cors"]);
}
if (isset($opciones["url"])) {
  $valores["url_sitio_publico"] = trim((string) $opciones["url"]);
}
if (isset($opciones["moneda"])) {
  $valores["moneda_default"] = trim((string) $opciones["moneda"]);
}
if (isset($opciones["mensaje"])) {
  $valores["whatsapp_mensaje_base"] = trim((string) $opciones["mensaje"]);
}

if ($autorizar !== "ECOMMERCE_PUBLICO_CONFIGURACION_FASE1" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se guardo configuracion ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_CONFIGURACION_FASE1",
      "respaldo" => "RUTA_O_REFERENCIA",
      "whatsapp" => "NUMERO_WHATSAPP",
      "cors" => "ORIGEN_FRONTEND",
      "url" => "URL_FRONTEND"
    ),
    "validacion_respaldo" => $validacion,
    "guardrails" => array(
      "no_expone_secretos" => true,
      "no_permite_cors_wildcard" => true,
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true,
      "requiere_ddl_previo" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$respuesta = $modelo->guardarConfiguracionInicialAutorizada($valores, array("autorizar" => $autorizar));

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "validacion_respaldo" => $validacion,
  "entrada" => array(
    "whatsapp" => isset($valores["whatsapp_numero_principal"]) ? $valores["whatsapp_numero_principal"] : "",
    "cors" => isset($valores["cors_origenes_permitidos"]) ? $valores["cors_origenes_permitidos"] : "",
    "url" => isset($valores["url_sitio_publico"]) ? $valores["url_sitio_publico"] : ""
  ),
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldoConfigEcommerce($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderConfigEcommerce($respaldo);
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldoPlaceholderConfigEcommerce($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
