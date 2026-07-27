<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: crear semillas de canales API ecommerce solo con token, respaldo y DDL previo.
 * Impacto: crea/actualiza canal Artiani y partner en borrador; no genera credenciales ni asigna productos.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_CANALES_SEED y --respaldo.
 */

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "artiani_origin::",
  "artiani_prod::",
  "partner_codigo::",
  "partner_origin::",
  "partner_nombre::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$artianiOrigin = isset($opciones["artiani_origin"]) ? trim((string) $opciones["artiani_origin"]) : "http://artiani.com.local";
$artianiProd = isset($opciones["artiani_prod"]) ? trim((string) $opciones["artiani_prod"]) : "https://artiani.com.mx";
$partnerCodigo = isset($opciones["partner_codigo"]) ? trim((string) $opciones["partner_codigo"]) : "partner_mayoreo_001";
$partnerOrigin = isset($opciones["partner_origin"]) ? trim((string) $opciones["partner_origin"]) : "https://partner.example.com";
$partnerNombre = isset($opciones["partner_nombre"]) ? trim((string) $opciones["partner_nombre"]) : "Partner mayoreo 001";
$validacion = validarRespaldoCanalesSeed($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_CANALES_SEED" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se crearon canales API ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_CANALES_SEED",
      "respaldo" => "RUTA_O_REFERENCIA",
      "ddl_previo" => "erp_ecommerce_canales_api"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_canal_artiani" => true,
      "crea_partner_borrador" => true,
      "genera_credenciales" => false,
      "asigna_productos" => false,
      "activa_partner_productivo" => false,
      "habilita_auth_obligatoria" => false,
      "registra_cotizaciones" => false,
      "toca_inventario" => false,
      "toca_ecom_legacy" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceCanalesSeedApply extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceCanalesSeedApply())->conexion();
$bloqueos = array();
$resultados = array();

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
} else {
  foreach (array("erp_ecommerce_canales_api") as $tabla) {
    if (!tablaExisteCanalesSeed($db, $tabla)) {
      $bloqueos[] = "tabla_pendiente_" . $tabla;
    }
  }
}

if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se crearon canales API ecommerce por bloqueos previos.",
    "validacion_respaldo" => $validacion,
    "bloqueos" => $bloqueos,
    "guardrails" => array(
      "no_escribe_bd_si_falta_ddl" => true,
      "no_genera_credenciales" => true,
      "no_activa_partner_productivo" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

$scopes = array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun");
$canales = array(
  array(
    "codigo" => "artiani_web",
    "nombre" => "Artiani ecommerce publico",
    "tipo_canal" => "frontend_propio",
    "estatus" => "activo",
    "url_publica" => $artianiProd,
    "allowed_origins" => array($artianiOrigin, $artianiProd),
    "scopes" => $scopes,
    "politica_precios" => "publico",
    "rate_limit_minuto" => 120,
    "rate_limit_dia" => 10000,
    "observaciones" => "Canal oficial Artiani. Seed autorizado; no genera credenciales."
  ),
  array(
    "codigo" => $partnerCodigo,
    "nombre" => $partnerNombre,
    "tipo_canal" => "partner_mayoreo",
    "estatus" => "borrador",
    "url_publica" => $partnerOrigin,
    "allowed_origins" => array($partnerOrigin),
    "scopes" => $scopes,
    "politica_precios" => "publico_o_consultar",
    "rate_limit_minuto" => 60,
    "rate_limit_dia" => 5000,
    "observaciones" => "Partner en borrador. Requiere allowlist, backend/seguridad y revision antes de activar."
  )
);

try {
  $db->beginTransaction();
  foreach ($canales as $canal) {
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_canales_api
        (codigo, nombre, tipo_canal, estatus, url_publica, allowed_origins, scopes_json, politica_precios, canal_publicacion, puede_ver_precio, puede_ver_disponibilidad, puede_cotizar, puede_registrar_cotizacion, mostrar_stock_exacto, rate_limit_minuto, rate_limit_dia, observaciones, fecha_registro, fecha_actualizacion)
      VALUES
        (:codigo, :nombre, :tipo_canal, :estatus, :url_publica, :allowed_origins, :scopes_json, :politica_precios, 'catalogo_publico', 1, 1, 1, 0, 0, :rate_limit_minuto, :rate_limit_dia, :observaciones, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        nombre=VALUES(nombre),
        tipo_canal=VALUES(tipo_canal),
        estatus=VALUES(estatus),
        url_publica=VALUES(url_publica),
        allowed_origins=VALUES(allowed_origins),
        scopes_json=VALUES(scopes_json),
        politica_precios=VALUES(politica_precios),
        canal_publicacion=VALUES(canal_publicacion),
        puede_ver_precio=VALUES(puede_ver_precio),
        puede_ver_disponibilidad=VALUES(puede_ver_disponibilidad),
        puede_cotizar=VALUES(puede_cotizar),
        puede_registrar_cotizacion=VALUES(puede_registrar_cotizacion),
        mostrar_stock_exacto=VALUES(mostrar_stock_exacto),
        rate_limit_minuto=VALUES(rate_limit_minuto),
        rate_limit_dia=VALUES(rate_limit_dia),
        observaciones=VALUES(observaciones),
        fecha_actualizacion=NOW()");
    $stmt->execute(array(
      ":codigo" => $canal["codigo"],
      ":nombre" => $canal["nombre"],
      ":tipo_canal" => $canal["tipo_canal"],
      ":estatus" => $canal["estatus"],
      ":url_publica" => $canal["url_publica"],
      ":allowed_origins" => implode("\n", $canal["allowed_origins"]),
      ":scopes_json" => json_encode($canal["scopes"], JSON_UNESCAPED_UNICODE),
      ":politica_precios" => $canal["politica_precios"],
      ":rate_limit_minuto" => intval($canal["rate_limit_minuto"]),
      ":rate_limit_dia" => intval($canal["rate_limit_dia"]),
      ":observaciones" => $canal["observaciones"]
    ));
    $stmtConsulta = $db->prepare("SELECT id_canal_api, codigo, nombre, tipo_canal, estatus, url_publica, allowed_origins, scopes_json, politica_precios, rate_limit_minuto, rate_limit_dia FROM erp_ecommerce_canales_api WHERE codigo=:codigo LIMIT 1");
    $stmtConsulta->execute(array(":codigo" => $canal["codigo"]));
    $resultados[] = $stmtConsulta->fetch(PDO::FETCH_ASSOC);
  }
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
  "mensaje" => "Canales API ecommerce sembrados sin credenciales",
  "validacion_respaldo" => $validacion,
  "canales" => $resultados,
  "guardrails" => array(
    "no_genera_credenciales" => true,
    "partner_queda_borrador" => true,
    "no_asigna_productos" => true,
    "no_registra_cotizaciones" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteCanalesSeed($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function validarRespaldoCanalesSeed($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderCanalesSeed($respaldo);
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

function respaldoPlaceholderCanalesSeed($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "[ARCHIVO]") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
