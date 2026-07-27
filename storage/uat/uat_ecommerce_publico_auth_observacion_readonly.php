<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: simular validacion de headers API ecommerce por canal en modo observacion.
 * Impacto: prepara auth multi-canal sin bloquear frontend Artiani ni requerir HMAC todavia.
 * Contrato: read-only; no escribe logs, no consulta secretos reales si faltan tablas y no bloquea requests.
 */

$opciones = getopt("", array(
  "method::",
  "path::",
  "query::",
  "body::",
  "origin::",
  "api_key::",
  "timestamp::",
  "nonce::",
  "signature::"
));

$method = strtoupper(trim((string) valorAuthObs($opciones, "method", "GET")));
$path = trim((string) valorAuthObs($opciones, "path", "/ecommercePublico/catalogo"));
$query = trim((string) valorAuthObs($opciones, "query", "limite=2"));
$body = (string) valorAuthObs($opciones, "body", "");
$origin = trim((string) valorAuthObs($opciones, "origin", "http://artiani.com.local"));
$apiKey = trim((string) valorAuthObs($opciones, "api_key", ""));
$timestamp = trim((string) valorAuthObs($opciones, "timestamp", ""));
$nonce = trim((string) valorAuthObs($opciones, "nonce", ""));
$signature = trim((string) valorAuthObs($opciones, "signature", ""));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceAuthObservacion extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceAuthObservacion())->conexion();
$tablas = array(
  "erp_ecommerce_canales_api",
  "erp_ecommerce_api_credenciales",
  "erp_ecommerce_api_nonces",
  "erp_ecommerce_api_logs"
);
$tablasEstado = array();
$bloqueosProductivo = array();
$observaciones = array();
$canalDetectado = null;
$credencialDetectada = null;

if (!$db) {
  $bloqueosProductivo[] = "conexion_mysql_no_disponible";
} else {
  foreach ($tablas as $tabla) {
    $existe = tablaExisteAuthObs($db, $tabla);
    $tablasEstado[$tabla] = $existe;
    if (!$existe) {
      $bloqueosProductivo[] = "tabla_pendiente_" . $tabla;
    }
  }
}

$headersPresentes = array(
  "origin" => $origin !== "",
  "api_key" => $apiKey !== "",
  "timestamp" => $timestamp !== "",
  "nonce" => $nonce !== "",
  "signature" => $signature !== ""
);

if ($apiKey === "") {
  $observaciones[] = "sin_api_key_modo_publico_actual";
}
if ($apiKey !== "" && strlen($apiKey) < 16) {
  $observaciones[] = "api_key_formato_corto";
}
if ($signature !== "" && ($timestamp === "" || $nonce === "")) {
  $observaciones[] = "signature_sin_timestamp_o_nonce";
}

$queryNormalizado = normalizarQueryAuthObs($query);
$bodyHash = hash("sha256", $body);
$baseCanonica = implode("\n", array($method, $path, $queryNormalizado, $bodyHash, $timestamp, $nonce));

if ($db && empty(array_filter($tablasEstado, function($existe) { return !$existe; })) && $apiKey !== "") {
  $prefix = substr($apiKey, 0, 24);
  $stmt = $db->prepare("SELECT cred.id_credencial_api, cred.id_canal_api, cred.api_key_prefix, cred.algoritmo_firma, cred.scopes_json, cred.estatus credencial_estatus,
      canal.codigo, canal.nombre, canal.tipo_canal, canal.estatus canal_estatus, canal.allowed_origins, canal.scopes_json canal_scopes
    FROM erp_ecommerce_api_credenciales cred
    INNER JOIN erp_ecommerce_canales_api canal ON canal.id_canal_api=cred.id_canal_api
    WHERE cred.api_key_prefix=:prefix AND cred.api_key_hash=:hash
    LIMIT 1");
  $stmt->execute(array(":prefix" => $prefix, ":hash" => hash("sha256", $apiKey)));
  $credencialDetectada = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if ($credencialDetectada) {
    $canalDetectado = array(
      "id_canal_api" => intval($credencialDetectada["id_canal_api"]),
      "codigo" => $credencialDetectada["codigo"],
      "nombre" => $credencialDetectada["nombre"],
      "tipo_canal" => $credencialDetectada["tipo_canal"],
      "estatus" => $credencialDetectada["canal_estatus"]
    );
  } else {
    $observaciones[] = "api_key_no_encontrada";
  }
}

$decisionObservada = "permitir_por_fase_actual";
if ($apiKey !== "" && !$credencialDetectada && empty($bloqueosProductivo)) {
  $decisionObservada = "bloquear_si_auth_obligatoria";
}
if ($credencialDetectada && (string) $credencialDetectada["canal_estatus"] !== "activo") {
  $decisionObservada = "bloquear_si_auth_obligatoria_por_canal_no_activo";
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only_observacion",
  "decision_observada" => $decisionObservada,
  "auth_obligatoria_actual" => false,
  "no_bloquea_frontend_artiani" => true,
  "request" => array(
    "method" => $method,
    "path" => $path,
    "query_string_normalizado" => $queryNormalizado,
    "origin" => $origin,
    "body_sha256_hex" => $bodyHash
  ),
  "headers_presentes" => $headersPresentes,
  "base_canonica" => $baseCanonica,
  "schema" => array(
    "tablas_estado" => $tablasEstado,
    "bloqueos_para_auth_productiva" => $bloqueosProductivo
  ),
  "canal_detectado" => $canalDetectado,
  "credencial_detectada" => $credencialDetectada ? array(
    "id_credencial_api" => intval($credencialDetectada["id_credencial_api"]),
    "api_key_prefix" => $credencialDetectada["api_key_prefix"],
    "algoritmo_firma" => $credencialDetectada["algoritmo_firma"],
    "estatus" => $credencialDetectada["credencial_estatus"],
    "scopes" => json_decode((string) $credencialDetectada["scopes_json"], true) ?: array()
  ) : null,
  "observaciones" => $observaciones,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_logs" => true,
    "no_consulta_secret_encrypted" => true,
    "no_valida_hmac_real_todavia" => true,
    "no_exige_api_key" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteAuthObs($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function normalizarQueryAuthObs($query) {
  $query = ltrim(trim((string) $query), "?");
  if ($query === "") {
    return "";
  }
  parse_str($query, $pares);
  ksort($pares);
  return http_build_query($pares, "", "&", PHP_QUERY_RFC3986);
}

function valorAuthObs($datos, $clave, $default = null) {
  return array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}
