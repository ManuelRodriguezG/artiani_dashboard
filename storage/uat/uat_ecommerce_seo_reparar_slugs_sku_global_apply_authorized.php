<?php

/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-14
 * Proposito: reparar publicaciones ecommerce donde un SKU especifico usa titulo/slug del producto global.
 * Impacto: Ecommerce SEO; corrige titulo_publico, slug, url_publica y canonical_url de publicaciones puntuales.
 * Contrato: bloqueado por defecto; requiere --apply=1 y --autorizar=ECOMMERCE_SEO_REPARAR_SLUGS_SKU_GLOBAL. Crea respaldo previo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentosSeoRepair($argv);
$apply = isset($args["apply"]) && (string) $args["apply"] === "1";
$autorizar = isset($args["autorizar"]) ? trim((string) $args["autorizar"]) : "";
$backupModo = isset($args["backup"]) ? trim((string) $args["backup"]) : "dump";
$token = "ECOMMERCE_SEO_REPARAR_SLUGS_SKU_GLOBAL";
$backupDir = "C:\\xampp\\panel_db_backups";
$backupFile = $backupDir . "\\artianicom_sys_panel_" . date("Ymd_His") . "_antes_reparar_slugs_sku_global.sql";
$backupFilasFile = $backupDir . "\\artianicom_sys_panel_" . date("Ymd_His") . "_rollback_reparar_slugs_sku_global_filas.sql";

try {
  $db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8", MYSQLUSER, MYSQLPASS);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $candidatos = candidatosSeoRepair($db);
  $plan = array();
  foreach ($candidatos as $fila) {
    $slugBase = slugSeoRepair($fila["nombre_sku"]);
    $slugFinal = slugUnicoSeoRepair($db, $slugBase, intval($fila["id_publicacion"]), $fila["sku"], intval($fila["id_sku"]));
    $path = "/producto/" . $slugFinal;
    $plan[] = array(
      "id_publicacion" => intval($fila["id_publicacion"]),
      "id_producto_erp" => intval($fila["id_producto_erp"]),
      "id_sku" => intval($fila["id_sku"]),
      "sku" => $fila["sku"],
      "estatus_publicacion" => $fila["estatus_publicacion"],
      "titulo_anterior" => $fila["titulo_publico"],
      "titulo_nuevo" => $fila["nombre_sku"],
      "slug_anterior" => $fila["slug"],
      "slug_nuevo" => $slugFinal,
      "url_anterior" => $fila["url_publica"] ?: "/producto/" . $fila["slug"],
      "url_nueva" => $path,
      "motivo" => "sku_especifico_usaba_slug_global"
    );
  }

  if (!$apply) {
    salidaSeoRepair(true, "Plan read-only de reparacion de slugs SKU/global", array(
      "apply" => false,
      "read_only" => true,
      "total_candidatos" => count($plan),
      "items" => $plan,
      "comando_apply" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_seo_reparar_slugs_sku_global_apply_authorized.php --apply=1 --backup=filas --autorizar=" . $token
    ));
  }

  if ($autorizar !== $token) {
    salidaSeoRepair(false, "Apply bloqueado: falta autorizacion explicita", array(
      "apply" => true,
      "total_candidatos" => count($plan),
      "autorizar_requerido" => $token
    ));
  }

  if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true)) {
    salidaSeoRepair(false, "No se pudo crear directorio de respaldo", array("backup_dir" => $backupDir));
  }
  $respaldo = array("error" => true, "omitido" => $backupModo === "filas");
  $backupUsado = array("tipo" => "mysqldump", "archivo" => $backupFile, "existe" => is_file($backupFile), "bytes" => is_file($backupFile) ? filesize($backupFile) : 0);
  if ($backupModo !== "filas") {
    $respaldo = ejecutarMysqldumpSeoRepair($backupFile);
    $backupUsado = array("tipo" => "mysqldump", "archivo" => $backupFile, "existe" => is_file($backupFile), "bytes" => is_file($backupFile) ? filesize($backupFile) : 0);
  }
  if (!empty($respaldo["error"])) {
    $backupFilas = crearRollbackFilasSeoRepair($backupFilasFile, $candidatos);
    $backupUsado = array("tipo" => "rollback_filas_afectadas", "archivo" => $backupFilasFile, "existe" => is_file($backupFilasFile), "bytes" => is_file($backupFilasFile) ? filesize($backupFilasFile) : 0);
    if (empty($backupFilas["ok"])) {
      salidaSeoRepair(false, "Respaldo fallo; no se ejecuto reparacion", array(
        "backup_mysqldump" => array("archivo" => $backupFile, "existe" => is_file($backupFile), "bytes" => is_file($backupFile) ? filesize($backupFile) : 0),
        "backup_filas" => $backupUsado
      ));
    }
  }

  $db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8", MYSQLUSER, MYSQLPASS);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->beginTransaction();
  $stmt = $db->prepare("UPDATE erp_ecommerce_publicaciones
    SET titulo_publico=:titulo,
        slug=:slug,
        url_publica=:url_publica,
        canonical_url=:canonical_url,
        fecha_actualizacion=NOW(),
        fecha_slug_actualizado=NOW(),
        bloquear_slug_auto=1
    WHERE id_publicacion=:id
    LIMIT 1");
  $actualizadas = 0;
  foreach ($plan as $item) {
    $stmt->execute(array(
      ":titulo" => $item["titulo_nuevo"],
      ":slug" => $item["slug_nuevo"],
      ":url_publica" => $item["url_nueva"],
      ":canonical_url" => "https://artiani.com.mx" . $item["url_nueva"],
      ":id" => intval($item["id_publicacion"])
    ));
    $actualizadas += $stmt->rowCount();
  }
  $db->commit();

  salidaSeoRepair(true, "Slugs SKU/global reparados", array(
    "apply" => true,
    "backup" => $backupUsado,
    "backup_mysqldump_intentado" => array("archivo" => $backupFile, "existe" => is_file($backupFile), "bytes" => is_file($backupFile) ? filesize($backupFile) : 0, "ok" => empty($respaldo["error"])),
    "total_planeados" => count($plan),
    "filas_actualizadas" => $actualizadas,
    "items" => $plan,
    "no_toca" => array("productos_erp", "skus", "inventario", "precios", "redirecciones_seo")
  ));
} catch (Exception $e) {
  if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
    $db->rollBack();
  }
  salidaSeoRepair(false, $e->getMessage(), array("apply" => $apply));
}

function candidatosSeoRepair($db) {
  $sql = "SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug, pub.url_publica, pub.canonical_url,
      pub.titulo_publico, pub.estatus_publicacion, s.sku, s.nombre nombre_sku, p.nombre nombre_producto
    FROM erp_ecommerce_publicaciones pub
    INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
    INNER JOIN (
      SELECT id_producto_erp, COUNT(*) total_publicaciones_producto
      FROM erp_ecommerce_publicaciones
      WHERE canal='catalogo_publico' AND estatus_publicacion IN ('publicado','borrador','pausado')
      GROUP BY id_producto_erp
      HAVING COUNT(*) > 1
    ) t ON t.id_producto_erp=pub.id_producto_erp
    WHERE pub.canal='catalogo_publico'
      AND pub.estatus_publicacion IN ('publicado','borrador','pausado')
    ORDER BY pub.id_producto_erp ASC, pub.id_publicacion ASC";
  $filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $salida = array();
  foreach ($filas as $fila) {
    $slugProducto = slugSeoRepair($fila["nombre_producto"]);
    $slugPublicacion = slugSeoRepair($fila["slug"]);
    if ($slugProducto === "" || $slugPublicacion !== $slugProducto) { continue; }
    $simSkuProducto = similitudSeoRepair($fila["nombre_sku"], $fila["nombre_producto"]);
    $simTituloProducto = similitudSeoRepair($fila["titulo_publico"], $fila["nombre_producto"]);
    if ($simSkuProducto < 58 && $simTituloProducto >= 82) {
      $salida[] = $fila;
    }
  }
  return $salida;
}

function slugUnicoSeoRepair($db, $slugBase, $idPublicacion, $sku, $idSku) {
  $slugBase = $slugBase !== "" ? $slugBase : "producto-" . intval($idSku);
  $candidatos = array($slugBase);
  $skuSlug = slugSeoRepair($sku);
  if ($skuSlug !== "" && strpos("-" . $slugBase . "-", "-" . $skuSlug . "-") === false) {
    $candidatos[] = substr($slugBase . "-" . $skuSlug, 0, 180);
  }
  $candidatos[] = substr($slugBase . "-" . intval($idSku), 0, 180);
  $stmt = $db->prepare("SELECT id_publicacion FROM erp_ecommerce_publicaciones WHERE slug=:slug AND id_publicacion<>:id LIMIT 1");
  foreach ($candidatos as $slug) {
    $stmt->execute(array(":slug" => $slug, ":id" => intval($idPublicacion)));
    if (!$stmt->fetchColumn()) { return $slug; }
  }
  return substr($slugBase, 0, 160) . "-" . intval($idPublicacion);
}

function similitudSeoRepair($a, $b) {
  $a = slugSeoRepair($a);
  $b = slugSeoRepair($b);
  if ($a === "" || $b === "") { return 0; }
  similar_text($a, $b, $pct);
  return $pct;
}

function slugSeoRepair($texto) {
  $texto = strtolower(trim((string) $texto));
  $transliterado = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
  if ($transliterado !== false) { $texto = strtolower($transliterado); }
  $texto = str_replace(array("&", "+"), " y ", $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(kilogramos?|kgs?|kg)\b/i', '$1kg', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(gramos?|grs?|gr|g)\b/i', '$1g', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(mililitros?|mls?|ml)\b/i', '$1ml', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(litros?|lts?|lt|l)\b/i', '$1l', $texto);
  $texto = preg_replace('/[\'"`´]+/', '', $texto);
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  return substr(trim($texto, "-"), 0, 170);
}

function ejecutarMysqldumpSeoRepair($backupFile) {
  $mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
  if (!is_file($mysqldump)) { return array("error" => true); }
  $cmd = escapeshellarg($mysqldump)
    . " --host=" . escapeshellarg(MYSQLHOST)
    . " --port=" . escapeshellarg(MYSQLPORT)
    . " --user=" . escapeshellarg(MYSQLUSER)
    . " --password=" . escapeshellarg(MYSQLPASS)
    . " --single-transaction --routines --triggers "
    . escapeshellarg(MYSQLBASE);
  $descriptor = array(0 => array("pipe", "r"), 1 => array("file", $backupFile, "w"), 2 => array("pipe", "w"));
  $process = proc_open($cmd, $descriptor, $pipes);
  if (!is_resource($process)) { return array("error" => true); }
  fclose($pipes[0]);
  $stderr = stream_get_contents($pipes[2]);
  fclose($pipes[2]);
  $code = proc_close($process);
  return array("error" => $code !== 0 || !is_file($backupFile) || filesize($backupFile) <= 0, "code" => $code, "stderr" => $stderr);
}

function crearRollbackFilasSeoRepair($archivo, $filas) {
  $sql = array();
  $sql[] = "-- Rollback puntual generado antes de reparar slugs SKU/global";
  $sql[] = "-- Fecha: " . date("c");
  $sql[] = "START TRANSACTION;";
  foreach ($filas as $fila) {
    $sql[] = "UPDATE `erp_ecommerce_publicaciones` SET "
      . "`titulo_publico`=" . sqlQuoteSeoRepair($fila["titulo_publico"]) . ", "
      . "`slug`=" . sqlQuoteSeoRepair($fila["slug"]) . ", "
      . "`url_publica`=" . sqlQuoteSeoRepair($fila["url_publica"]) . ", "
      . "`canonical_url`=" . sqlQuoteSeoRepair($fila["canonical_url"]) . ", "
      . "`fecha_actualizacion`=`fecha_actualizacion` "
      . "WHERE `id_publicacion`=" . intval($fila["id_publicacion"]) . " LIMIT 1;";
  }
  $sql[] = "COMMIT;";
  $contenido = implode(PHP_EOL, $sql) . PHP_EOL;
  $ok = file_put_contents($archivo, $contenido) !== false && is_file($archivo) && filesize($archivo) > 0;
  return array("ok" => $ok, "archivo" => $archivo, "bytes" => is_file($archivo) ? filesize($archivo) : 0);
}

function sqlQuoteSeoRepair($valor) {
  if ($valor === null) { return "NULL"; }
  return "'" . str_replace(array("\\", "'"), array("\\\\", "\\'"), (string) $valor) . "'";
}

function argumentosSeoRepair($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function salidaSeoRepair($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
