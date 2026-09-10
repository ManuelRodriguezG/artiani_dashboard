<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: poblar columnas URL/canonical de publicaciones ecommerce desde el slug persistido.
 * Impacto: deja `erp_ecommerce_publicaciones` lista para frontend/SEO sin recalcular slugs ni crear redirecciones.
 * Contrato: apply_authorized; requiere respaldo externo y token `ECOMMERCE_PUBLICACIONES_URLS_CANONICAS`.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$base = isset($args["base"]) ? rtrim(trim($args["base"]), "/") : "https://artiani.com.mx";
$tokenEsperado = "ECOMMERCE_PUBLICACIONES_URLS_CANONICAS";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array(
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo
  ));
}

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

validarColumna($pdo, "erp_ecommerce_publicaciones", "slug");
validarColumna($pdo, "erp_ecommerce_publicaciones", "url_publica");
validarColumna($pdo, "erp_ecommerce_publicaciones", "canonical_url");

$antes = resumen($pdo);
$sql = "UPDATE erp_ecommerce_publicaciones
  SET url_publica=CONCAT('/producto/', slug),
      canonical_url=CONCAT(:base, '/producto/', slug)
  WHERE slug IS NOT NULL
    AND TRIM(slug)<>''
    AND (
      url_publica IS NULL OR url_publica='' OR url_publica<>CONCAT('/producto/', slug)
      OR canonical_url IS NULL OR canonical_url='' OR canonical_url<>CONCAT(:base2, '/producto/', slug)
    )";
$stmt = $pdo->prepare($sql);
$stmt->execute(array(":base" => $base, ":base2" => $base));
$despues = resumen($pdo);

salida(true, "URLs publicas/canonicas de publicaciones ecommerce actualizadas", array(
  "ejecutado" => true,
  "base_datos" => MYSQLBASE,
  "base_canonical" => $base,
  "respaldo" => $respaldo,
  "filas_afectadas" => $stmt->rowCount(),
  "antes" => $antes,
  "despues" => $despues,
  "guardrails" => array(
    "no_cambia_slug" => true,
    "no_cambia_titulo" => true,
    "no_crea_redirecciones" => true,
    "no_toca_inventario" => true
  )
));

function resumen($pdo) {
  $stmt = $pdo->query("SELECT COUNT(*) total,
      SUM(CASE WHEN slug IS NOT NULL AND TRIM(slug)<>'' THEN 1 ELSE 0 END) con_slug,
      SUM(CASE WHEN url_publica IS NULL OR url_publica='' THEN 1 ELSE 0 END) sin_url_publica,
      SUM(CASE WHEN canonical_url IS NULL OR canonical_url='' THEN 1 ELSE 0 END) sin_canonical
    FROM erp_ecommerce_publicaciones");
  return $stmt->fetch();
}

function validarColumna($pdo, $tabla, $columna) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
  $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
  if (intval($stmt->fetchColumn()) <= 0) {
    salida(false, "Falta columna requerida", array(
      "ejecutado" => false,
      "tabla" => $tabla,
      "columna" => $columna
    ));
  }
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
