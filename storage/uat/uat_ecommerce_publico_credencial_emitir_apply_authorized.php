<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: emitir credencial API ecommerce solo con token, respaldo, canal y gestion de secreto.
 * Impacto: crea API key y opcional secreto HMAC cifrado; no activa partner ni asigna productos.
 * Contrato: bloqueado por defecto; muestra el secreto solo una vez si se ejecuta autorizado.
 */

$opciones = getopt("", array("autorizar::", "respaldo::", "canal::", "modo::", "scopes::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$codigoCanal = isset($opciones["canal"]) ? trim((string) $opciones["canal"]) : "partner_mayoreo_001";
$modoCredencial = isset($opciones["modo"]) ? trim((string) $opciones["modo"]) : "hmac";
$scopesTexto = isset($opciones["scopes"]) ? trim((string) $opciones["scopes"]) : "catalogo:leer,producto:leer,filtros:leer,disponibilidad:leer,cotizacion:dryrun";
$validacion = validarRespaldoCredencial($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_CREDENCIAL_EMITIR" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se emitio credencial ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_CREDENCIAL_EMITIR",
      "respaldo" => "RUTA_O_REFERENCIA",
      "canal" => "CODIGO_CANAL"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "emite_api_key" => true,
      "emite_secret_hmac_si_modo_hmac" => true,
      "activa_partner" => false,
      "asigna_productos" => false,
      "habilita_cotizacion_registrar" => false,
      "toca_inventario" => false,
      "toca_ecom_legacy" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceCredencialApply extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceCredencialApply())->conexion();
$bloqueos = array();
$secretEncryptionKey = trim((string) getenv("ECOMMERCE_API_SECRET_ENCRYPTION_KEY"));
$scopes = normalizarScopesCredencialApply($scopesTexto);

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
}
if (!in_array($modoCredencial, array("hmac", "identificador_readonly"), true)) {
  $bloqueos[] = "modo_no_permitido";
}
if ($modoCredencial === "hmac" && $secretEncryptionKey === "") {
  $bloqueos[] = "ECOMMERCE_API_SECRET_ENCRYPTION_KEY_requerida";
}
if ($modoCredencial === "hmac" && !function_exists("openssl_encrypt")) {
  $bloqueos[] = "openssl_encrypt_no_disponible";
}
if (empty($scopes)) {
  $bloqueos[] = "scopes_validos_requeridos";
}

if (empty($bloqueos)) {
  foreach (array("erp_ecommerce_canales_api", "erp_ecommerce_api_credenciales") as $tabla) {
    if (!tablaExisteCredencialApply($db, $tabla)) {
      $bloqueos[] = "tabla_pendiente_" . $tabla;
    }
  }
}

if (empty($bloqueos)) {
  $stmt = $db->prepare("SELECT id_canal_api, codigo, nombre, tipo_canal, estatus FROM erp_ecommerce_canales_api WHERE codigo=:codigo LIMIT 1");
  $stmt->execute(array(":codigo" => $codigoCanal));
  $canal = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$canal) {
    $bloqueos[] = "canal_no_encontrado";
  }
}

if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se emitio credencial por bloqueos.",
    "validacion_respaldo" => $validacion,
    "bloqueos" => $bloqueos,
    "guardrails" => array("no_escribe_bd_si_bloqueos" => true, "no_genera_secret_si_bloqueos" => true)
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

$apiKey = "ak_" . bin2hex(random_bytes(6)) . "_" . bin2hex(random_bytes(18));
$apiSecret = $modoCredencial === "hmac" ? "sk_" . bin2hex(random_bytes(32)) : "";
$prefix = substr($apiKey, 0, 24);
$secretEncrypted = null;
$secretVersion = null;
if ($modoCredencial === "hmac") {
  $secretVersion = "env_v1";
  $secretEncrypted = cifrarSecretoCredencial($apiSecret, $secretEncryptionKey);
}

try {
  $db->beginTransaction();
  $stmt = $db->prepare("INSERT INTO erp_ecommerce_api_credenciales
      (id_canal_api, api_key_prefix, api_key_hash, api_secret_hash, api_secret_encrypted, api_secret_version, algoritmo_firma, scopes_json, estatus, fecha_emision)
    VALUES
      (:canal, :prefix, :key_hash, :secret_hash, :secret_encrypted, :secret_version, :algoritmo, :scopes, 'activo', NOW())");
  $stmt->execute(array(
    ":canal" => intval($canal["id_canal_api"]),
    ":prefix" => $prefix,
    ":key_hash" => hash("sha256", $apiKey),
    ":secret_hash" => $apiSecret !== "" ? hash("sha256", $apiSecret) : null,
    ":secret_encrypted" => $secretEncrypted,
    ":secret_version" => $secretVersion,
    ":algoritmo" => $modoCredencial === "hmac" ? "hmac_sha256" : "none",
    ":scopes" => json_encode($scopes, JSON_UNESCAPED_UNICODE)
  ));
  $idCredencial = intval($db->lastInsertId());
  $db->commit();
} catch (Exception $e) {
  if ($db && $db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array(
    "ok" => false,
    "modo" => "error",
    "mensaje" => $e->getMessage(),
    "validacion_respaldo" => $validacion,
    "guardrails" => array("rollback" => true)
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

echo json_encode(array(
  "ok" => true,
  "modo" => "apply_authorized",
  "mensaje" => "Credencial ecommerce emitida. Guarda estos valores ahora; el secreto no debe mostrarse otra vez.",
  "validacion_respaldo" => $validacion,
  "canal" => $canal,
  "credencial" => array(
    "id_credencial_api" => $idCredencial,
    "api_key" => $apiKey,
    "api_secret" => $apiSecret,
    "api_key_prefix" => $prefix,
    "modo" => $modoCredencial,
    "scopes" => $scopes
  ),
  "guardrails" => array(
    "mostrar_secret_una_sola_vez" => true,
    "no_pegar_secret_en_javascript" => true,
    "no_activa_partner" => true,
    "no_asigna_productos" => true,
    "no_habilita_cotizacion_registrar" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function cifrarSecretoCredencial($secreto, $keyMaterial) {
  $key = hash("sha256", $keyMaterial, true);
  $iv = random_bytes(12);
  $tag = "";
  $ciphertext = openssl_encrypt($secreto, "aes-256-gcm", $key, OPENSSL_RAW_DATA, $iv, $tag);
  if ($ciphertext === false) {
    throw new Exception("No se pudo cifrar api_secret.");
  }
  return base64_encode(json_encode(array(
    "alg" => "aes-256-gcm",
    "iv" => base64_encode($iv),
    "tag" => base64_encode($tag),
    "ct" => base64_encode($ciphertext)
  )));
}

function tablaExisteCredencialApply($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function normalizarScopesCredencialApply($texto) {
  $permitidos = array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun", "cotizacion:registrar");
  $salida = array();
  foreach (explode(",", (string) $texto) as $scope) {
    $scope = trim($scope);
    if ($scope !== "" && in_array($scope, $permitidos, true) && !in_array($scope, $salida, true)) {
      $salida[] = $scope;
    }
  }
  return $salida;
}

function validarRespaldoCredencial($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderCredencial($respaldo);
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

function respaldoPlaceholderCredencial($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "[ARCHIVO]") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
