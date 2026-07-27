<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: proponer allowlist de publicaciones ecommerce por canal sin escribir BD.
 * Impacto: evita que partners hereden automaticamente todo el catalogo Artiani.
 * Contrato: read-only; no crea relaciones, no activa partner y no modifica publicaciones.
 */

$opciones = getopt("", array("canal::", "publicaciones::", "skus::", "modo_precio::", "limite::"));
$codigoCanal = isset($opciones["canal"]) ? trim((string) $opciones["canal"]) : "partner_mayoreo_001";
$publicacionesTexto = isset($opciones["publicaciones"]) ? trim((string) $opciones["publicaciones"]) : "";
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "";
$modoPrecio = isset($opciones["modo_precio"]) ? trim((string) $opciones["modo_precio"]) : "publico";
$limite = isset($opciones["limite"]) ? max(1, min(100, intval($opciones["limite"]))) : 24;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class EcommerceCanalAllowlistPlan extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
}

$db = (new EcommerceCanalAllowlistPlan())->conexion();
$bloqueos = array();
$advertencias = array();
$canal = null;
$items = array();

if (!$db) {
  $bloqueos[] = "conexion_mysql_no_disponible";
} else {
  $tablas = array("erp_ecommerce_publicaciones", "erp_ecommerce_canales_api", "erp_ecommerce_canal_publicaciones");
  foreach ($tablas as $tabla) {
    if (!tablaExisteAllowlist($db, $tabla)) {
      $bloqueos[] = "tabla_pendiente_" . $tabla;
    }
  }
}

if (empty($bloqueos)) {
  $stmtCanal = $db->prepare("SELECT id_canal_api, codigo, nombre, tipo_canal, estatus, politica_precios, allowed_origins, scopes_json
    FROM erp_ecommerce_canales_api
    WHERE codigo=:codigo
    LIMIT 1");
  $stmtCanal->execute(array(":codigo" => $codigoCanal));
  $canal = $stmtCanal->fetch(PDO::FETCH_ASSOC);
  if (!$canal) {
    $bloqueos[] = "canal_no_encontrado_" . $codigoCanal;
  }
}

if (empty($bloqueos)) {
  $idsPublicacion = enterosAllowlist($publicacionesTexto);
  $idsSku = enterosAllowlist($skusTexto);
  $where = array("pub.estatus_publicacion='publicado'");
  $params = array();

  if (!empty($idsPublicacion)) {
    $where[] = "pub.id_publicacion IN (" . implode(",", $idsPublicacion) . ")";
  } elseif (!empty($idsSku)) {
    $where[] = "pub.id_sku IN (" . implode(",", $idsSku) . ")";
  } else {
    $advertencias[] = "sin_publicaciones_o_skus_especificos_se_toman_primeras_publicadas";
  }

  $sql = "SELECT pub.id_publicacion, pub.id_sku, pub.slug, pub.titulo_publico, pub.mascota_especie, pub.necesidades_json,
      s.sku,
      cp.id_canal_publicacion, cp.estatus estatus_allowlist
    FROM erp_ecommerce_publicaciones pub
    INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
    LEFT JOIN erp_ecommerce_canal_publicaciones cp ON cp.id_publicacion=pub.id_publicacion AND cp.id_canal_api=:canal
    WHERE " . implode(" AND ", $where) . "
    ORDER BY pub.destacado DESC, pub.orden ASC, pub.titulo_publico ASC
    LIMIT " . intval($limite);
  $params[":canal"] = intval($canal["id_canal_api"]);
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $items[] = array(
      "id_publicacion" => intval($fila["id_publicacion"]),
      "id_sku" => intval($fila["id_sku"]),
      "sku" => $fila["sku"],
      "slug" => $fila["slug"],
      "titulo_publico" => $fila["titulo_publico"],
      "mascota_especie" => $fila["mascota_especie"],
      "necesidades" => json_decode((string) $fila["necesidades_json"], true) ?: array(),
      "ya_en_allowlist" => !empty($fila["id_canal_publicacion"]),
      "estatus_allowlist_actual" => $fila["estatus_allowlist"]
    );
  }
  if (empty($items)) {
    $bloqueos[] = "sin_publicaciones_publicadas_para_allowlist";
  }
}

$sqlPreview = array();
if (empty($bloqueos) && $canal) {
  foreach ($items as $idx => $item) {
    $sqlPreview[] = "INSERT INTO erp_ecommerce_canal_publicaciones (id_canal_api, id_publicacion, estatus, precio_modo, orden, destacado, fecha_registro, fecha_actualizacion) VALUES (" .
      intval($canal["id_canal_api"]) . ", " . intval($item["id_publicacion"]) . ", 'activo', '" . addslashes($modoPrecio) . "', " . intval($idx + 1) . ", 0, NOW(), NOW()) ON DUPLICATE KEY UPDATE estatus='activo', precio_modo=VALUES(precio_modo), orden=VALUES(orden), fecha_actualizacion=NOW();";
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "canal" => $canal,
  "modo_precio" => $modoPrecio,
  "items_total" => count($items),
  "items" => $items,
  "sql_preview_no_ejecutado" => $sqlPreview,
  "advertencias" => $advertencias,
  "bloqueos" => $bloqueos,
  "siguiente_apply_futuro_no_ejecutado" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canal_allowlist_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANAL_ALLOWLIST --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql --canal=" . $codigoCanal . " --publicaciones=IDS --modo_precio=" . $modoPrecio,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_activa_partner" => true,
    "no_genera_credenciales" => true,
    "no_publica_productos" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function tablaExisteAllowlist($db, $tabla) {
  $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function enterosAllowlist($texto) {
  $salida = array();
  foreach (explode(",", (string) $texto) as $valor) {
    $n = intval(trim($valor));
    if ($n > 0) {
      $salida[] = $n;
    }
  }
  return array_values(array_unique($salida));
}
