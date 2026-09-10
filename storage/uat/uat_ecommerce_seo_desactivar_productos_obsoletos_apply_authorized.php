<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: desactivar del snapshot SEO URLs de producto que ya no son canonicas en publicaciones.
 * Impacto: evita que sitemap/reportes usen rutas obsoletas; no borra registros ni redirecciones.
 * Contrato: apply_authorized; requiere respaldo externo y token `ECOMMERCE_SEO_SYNC_URLS_CANONICAS`.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$tokenEsperado = "ECOMMERCE_SEO_SYNC_URLS_CANONICAS";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array("ejecutado" => false, "token_requerido" => $tokenEsperado));
}
if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array("ejecutado" => false, "respaldo_recibido" => $respaldo));
}

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$antes = resumen($pdo);
$stmt = $pdo->prepare("UPDATE erp_ecommerce_seo_urls seo
  LEFT JOIN erp_ecommerce_publicaciones pub
    ON pub.canal='catalogo_publico' AND pub.url_publica=seo.path
  SET seo.activo=0, seo.indexable=0, seo.fecha_actualizacion=NOW()
  WHERE seo.tipo='producto'
    AND seo.activo=1
    AND pub.id_publicacion IS NULL");
$stmt->execute();
$despues = resumen($pdo);

salida(true, "URLs de producto obsoletas desactivadas del snapshot SEO", array(
  "ejecutado" => true,
  "base" => MYSQLBASE,
  "respaldo" => $respaldo,
  "filas_afectadas" => $stmt->rowCount(),
  "antes" => $antes,
  "despues" => $despues,
  "guardrails" => array(
    "no_borra_registros" => true,
    "no_borra_redirecciones" => true,
    "solo_snapshot_seo_urls" => true
  )
));

function resumen($pdo) {
  return $pdo->query("SELECT
      SUM(CASE WHEN tipo='producto' THEN 1 ELSE 0 END) producto_total,
      SUM(CASE WHEN tipo='producto' AND activo=1 THEN 1 ELSE 0 END) producto_activas,
      SUM(CASE WHEN tipo='producto' AND indexable=1 THEN 1 ELSE 0 END) producto_indexables
    FROM erp_ecommerce_seo_urls")->fetch();
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
  echo json_encode(array("ok" => $ok, "mensaje" => $mensaje, "depurar" => $depurar), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
