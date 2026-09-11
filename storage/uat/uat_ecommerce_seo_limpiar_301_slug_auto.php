<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: limpiar redirecciones 301 generadas por cambios internos de slug durante preparacion local.
 * Impacto: Ecommerce SEO; deja la tabla para redirecciones reales de URLs viejas productivas hacia URLs nuevas.
 * Contrato: por defecto solo planea; con --apply=1 borra exclusivamente tipo `producto_slug` con motivos automaticos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$apply = isset($args["apply"]) && (string) $args["apply"] === "1";
$backup = isset($args["backup"]) ? trim((string) $args["backup"]) : "";
$motivos = array("slug_profesional_pre_lanzamiento", "slug_publico_actualizado");

try {
  $db = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8", MYSQLUSER, MYSQLPASS);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $tabla = tablaExiste($db, "erp_ecommerce_seo_redirecciones");
  if (!$tabla) {
    salida(false, "Tabla SEO de redirecciones no existe", array(
      "base" => MYSQLBASE,
      "tabla" => "erp_ecommerce_seo_redirecciones",
      "apply" => $apply
    ));
  }

  $placeholders = implode(",", array_fill(0, count($motivos), "?"));
  $where = "tipo='producto_slug' AND status_code=301 AND motivo IN (" . $placeholders . ")";

  $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_seo_redirecciones WHERE " . $where);
  $stmt->execute($motivos);
  $total = intval($stmt->fetchColumn());

  $stmt = $db->prepare("SELECT id_redireccion, url_origen, url_destino, tipo, motivo, status_code
    FROM erp_ecommerce_seo_redirecciones
    WHERE " . $where . "
    ORDER BY id_redireccion ASC
    LIMIT 20");
  $stmt->execute($motivos);
  $muestra = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $conteos = array();
  $stmt = $db->query("SELECT tipo, motivo, status_code, COUNT(*) total
    FROM erp_ecommerce_seo_redirecciones
    GROUP BY tipo, motivo, status_code
    ORDER BY total DESC, tipo ASC, motivo ASC
    LIMIT 30");
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $conteos[] = $fila;
  }

  if (!$apply) {
    salida(true, "Plan de limpieza 301 automaticas por slug", array(
      "base" => MYSQLBASE,
      "apply" => false,
      "total_a_borrar" => $total,
      "where_seguro" => $where,
      "motivos_automaticos" => $motivos,
      "muestra" => $muestra,
      "conteos_actuales" => $conteos,
      "no_escribe_bd" => true
    ));
  }

  if ($backup === "" || stripos($backup, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($backup)) {
    salida(false, "Apply bloqueado: respaldo externo valido requerido", array(
      "base" => MYSQLBASE,
      "apply" => true,
      "total_a_borrar" => $total,
      "backup_recibido" => $backup,
      "ruta_requerida" => "C:\\xampp\\panel_db_backups\\[archivo].sql"
    ));
  }

  $db->beginTransaction();
  $stmt = $db->prepare("DELETE FROM erp_ecommerce_seo_redirecciones WHERE " . $where);
  $stmt->execute($motivos);
  $borradas = $stmt->rowCount();
  $db->commit();

  $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_seo_redirecciones WHERE " . $where);
  $stmt->execute($motivos);
  $restantes = intval($stmt->fetchColumn());

  salida(true, "301 automaticas por slug eliminadas", array(
    "base" => MYSQLBASE,
    "apply" => true,
    "backup" => $backup,
    "filas_planeadas" => $total,
    "filas_borradas" => $borradas,
    "restantes" => $restantes,
    "motivos_eliminados" => $motivos,
    "no_toca_redirecciones_manuales" => true,
    "no_toca_decisiones_google_indexadas" => true
  ));
} catch (Exception $e) {
  if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
    $db->rollBack();
  }
  salida(false, $e->getMessage(), array(
    "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
    "apply" => $apply
  ));
}

function tablaExiste($db, $tabla) {
  $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1");
  $stmt->execute(array(MYSQLBASE, $tabla));
  return (bool) $stmt->fetchColumn();
}

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
