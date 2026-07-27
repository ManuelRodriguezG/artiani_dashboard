<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: planear emision de credencial API ecommerce por canal sin generar secretos.
 * Impacto: prepara API key/HMAC para Artiani o partner respetando backend/secretos.
 * Contrato: read-only; no crea credenciales, no genera secretos y no escribe BD.
 */

$opciones = getopt("", array("canal::", "modo::", "scopes::"));
$codigoCanal = isset($opciones["canal"]) ? trim((string) $opciones["canal"]) : "partner_mayoreo_001";
$modo = isset($opciones["modo"]) ? trim((string) $opciones["modo"]) : "hmac";
$scopesTexto = isset($opciones["scopes"]) ? trim((string) $opciones["scopes"]) : "catalogo:leer,producto:leer,filtros:leer,disponibilidad:leer,cotizacion:dryrun";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceCredencialPlan extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceCredencialPlan())->conexion();
$bloqueos = array();
$advertencias = array();
$canal = null;
$scopes = normalizarScopesCredencial($scopesTexto);
$encryptionKeyDisponible = getenv("ECOMMERCE_API_SECRET_ENCRYPTION_KEY") !== false && trim((string) getenv("ECOMMERCE_API_SECRET_ENCRYPTION_KEY")) !== "";

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
} else {
  foreach (array("erp_ecommerce_canales_api", "erp_ecommerce_api_credenciales") as $tabla) {
    if (!tablaExisteCredencial($db, $tabla)) {
      $bloqueos[] = "tabla_pendiente_" . $tabla;
    }
  }
}

if (empty($bloqueos)) {
  $stmt = $db->prepare("SELECT id_canal_api, codigo, nombre, tipo_canal, estatus, scopes_json, allowed_origins
    FROM erp_ecommerce_canales_api
    WHERE codigo=:codigo
    LIMIT 1");
  $stmt->execute(array(":codigo" => $codigoCanal));
  $canal = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$canal) {
    $bloqueos[] = "canal_no_encontrado_" . $codigoCanal;
  }
}

if ($modo === "hmac" && !$encryptionKeyDisponible) {
  $advertencias[] = "hmac_requiere_ECOMMERCE_API_SECRET_ENCRYPTION_KEY_para_apply_real";
}
if (!in_array($modo, array("hmac", "identificador_readonly"), true)) {
  $bloqueos[] = "modo_no_permitido";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "canal" => $canal,
  "credencial_solicitada" => array(
    "modo" => $modo,
    "scopes" => $scopes,
    "requiere_backend_partner" => $modo === "hmac",
    "secret_visible_una_sola_vez" => $modo === "hmac",
    "no_pegar_secret_en_javascript" => true
  ),
  "key_management" => array(
    "env_requerida" => "ECOMMERCE_API_SECRET_ENCRYPTION_KEY",
    "disponible_en_entorno_actual" => $encryptionKeyDisponible,
    "nota" => "Sin llave de cifrado no se debe emitir secreto HMAC real."
  ),
  "siguiente_apply_futuro_no_ejecutado" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_credencial_emitir_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CREDENCIAL_EMITIR --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql --canal=" . $codigoCanal . " --modo=" . $modo,
  "advertencias" => $advertencias,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_genera_secretos" => true,
    "no_activa_partner" => true,
    "no_habilita_cotizacion_registrar" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteCredencial($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function normalizarScopesCredencial($texto) {
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
