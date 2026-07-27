<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: aplicar allowlist de publicaciones ecommerce por canal solo con token y respaldo.
 * Impacto: habilita productos especificos para un canal/partner sin activar el canal ni generar credenciales.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_CANAL_ALLOWLIST y --respaldo.
 */

$opciones = getopt("", array("autorizar::", "respaldo::", "canal::", "publicaciones::", "skus::", "modo_precio::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$codigoCanal = isset($opciones["canal"]) ? trim((string) $opciones["canal"]) : "partner_mayoreo_001";
$publicacionesTexto = isset($opciones["publicaciones"]) ? trim((string) $opciones["publicaciones"]) : "";
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "";
$modoPrecio = isset($opciones["modo_precio"]) ? trim((string) $opciones["modo_precio"]) : "publico";
$validacion = validarRespaldoAllowlist($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_CANAL_ALLOWLIST" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se aplico allowlist ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_CANAL_ALLOWLIST",
      "respaldo" => "RUTA_O_REFERENCIA",
      "canal" => "CODIGO_CANAL",
      "publicaciones_o_skus" => "IDS"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_allowlist" => true,
      "activa_partner" => false,
      "genera_credenciales" => false,
      "publica_productos" => false,
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

class EcommerceCanalAllowlistApply extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceCanalAllowlistApply())->conexion();
$bloqueos = array();
$aplicadas = array();

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
}
$idsPublicacion = enterosAllowlistApply($publicacionesTexto);
$idsSku = enterosAllowlistApply($skusTexto);
if (empty($idsPublicacion) && empty($idsSku)) {
  $bloqueos[] = "publicaciones_o_skus_requeridos";
}
if (!in_array($modoPrecio, array("publico", "consultar", "mayoreo"), true)) {
  $bloqueos[] = "modo_precio_no_permitido";
}

if (empty($bloqueos)) {
  foreach (array("erp_ecommerce_publicaciones", "erp_ecommerce_canales_api", "erp_ecommerce_canal_publicaciones") as $tabla) {
    if (!tablaExisteAllowlistApply($db, $tabla)) {
      $bloqueos[] = "tabla_pendiente_" . $tabla;
    }
  }
}

if (empty($bloqueos)) {
  $stmtCanal = $db->prepare("SELECT id_canal_api, codigo, tipo_canal, estatus FROM erp_ecommerce_canales_api WHERE codigo=:codigo LIMIT 1");
  $stmtCanal->execute(array(":codigo" => $codigoCanal));
  $canal = $stmtCanal->fetch(PDO::FETCH_ASSOC);
  if (!$canal) {
    $bloqueos[] = "canal_no_encontrado";
  }
}

if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se aplico allowlist por bloqueos.",
    "validacion_respaldo" => $validacion,
    "bloqueos" => $bloqueos,
    "guardrails" => array("no_escribe_bd_si_bloqueos" => true)
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

try {
  $where = array("estatus_publicacion='publicado'");
  if (!empty($idsPublicacion)) {
    $where[] = "id_publicacion IN (" . implode(",", $idsPublicacion) . ")";
  } else {
    $where[] = "id_sku IN (" . implode(",", $idsSku) . ")";
  }
  $publicaciones = $db->query("SELECT id_publicacion, id_sku, slug, titulo_publico FROM erp_ecommerce_publicaciones WHERE " . implode(" AND ", $where))->fetchAll(PDO::FETCH_ASSOC);
  if (empty($publicaciones)) {
    throw new Exception("No hay publicaciones publicadas para aplicar allowlist.");
  }

  $db->beginTransaction();
  $orden = 1;
  foreach ($publicaciones as $pub) {
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_canal_publicaciones
        (id_canal_api, id_publicacion, estatus, precio_modo, orden, destacado, fecha_registro, fecha_actualizacion)
      VALUES
        (:canal, :publicacion, 'activo', :precio_modo, :orden, 0, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        estatus='activo',
        precio_modo=VALUES(precio_modo),
        orden=VALUES(orden),
        fecha_actualizacion=NOW()");
    $stmt->execute(array(
      ":canal" => intval($canal["id_canal_api"]),
      ":publicacion" => intval($pub["id_publicacion"]),
      ":precio_modo" => $modoPrecio,
      ":orden" => $orden++
    ));
    $aplicadas[] = $pub;
  }
  $db->commit();
} catch (Exception $e) {
  if (isset($db) && $db && $db->inTransaction()) {
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
  "mensaje" => "Allowlist ecommerce aplicado",
  "validacion_respaldo" => $validacion,
  "canal" => $canal,
  "publicaciones_aplicadas" => $aplicadas,
  "guardrails" => array(
    "no_activa_partner" => true,
    "no_genera_credenciales" => true,
    "no_publica_productos" => true,
    "no_registra_cotizaciones" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteAllowlistApply($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function enterosAllowlistApply($texto) {
  $salida = array();
  foreach (explode(",", (string) $texto) as $valor) {
    $n = intval(trim($valor));
    if ($n > 0) {
      $salida[] = $n;
    }
  }
  return array_values(array_unique($salida));
}

function validarRespaldoAllowlist($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderAllowlist($respaldo);
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

function respaldoPlaceholderAllowlist($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "[ARCHIVO]") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
