<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: normalizar slugs ecommerce existentes con regla profesional y conservar redirecciones 301.
 * Impacto: actualiza `erp_ecommerce_publicaciones.slug/url_publica/canonical_url` y registra historial en redirecciones.
 * Contrato: modo plan read-only por defecto; apply requiere respaldo externo y token `ECOMMERCE_PUBLICACIONES_SLUGS_PROFESIONALES`.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$modo = isset($args["modo"]) ? strtolower($args["modo"]) : "plan";
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$base = isset($args["base"]) ? rtrim(trim($args["base"]), "/") : "https://artiani.com.mx";
$tokenEsperado = "ECOMMERCE_PUBLICACIONES_SLUGS_PROFESIONALES";
$apply = $modo === "apply";

if ($apply && $token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($apply && ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0)) {
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

$sql = "SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug, pub.titulo_publico,
    pub.presentacion_publica, s.sku, COALESCE(s.nombre, p.nombre) nombre_sku, p.nombre nombre_producto,
    COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
  LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
  LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
  WHERE pub.canal='catalogo_publico'
  ORDER BY pub.id_publicacion ASC";
$filas = $pdo->query($sql)->fetchAll();

$usados = array();
$plan = array();
foreach ($filas as $fila) {
  $titulo = trim((string) $fila["titulo_publico"]);
  if ($titulo === "") { $titulo = trim((string) ($fila["nombre_sku"] ?: $fila["nombre_producto"])); }
  $presentacion = trim((string) ($fila["presentacion_publica"] ?: $fila["presentacion_base"]));
  $tituloSlug = textoProductoParaSlug($titulo);
  $baseSlug = slugificar(trim($tituloSlug . " " . presentacionParaSlug($presentacion, $tituloSlug)));
  $slugNuevo = slugUnico($baseSlug, $fila, $usados);
  $slugActual = slugificar($fila["slug"]);
  $usados[$slugNuevo] = intval($fila["id_publicacion"]);
  $cambia = $slugActual !== "" && $slugActual !== $slugNuevo;
  $plan[] = array(
    "id_publicacion" => intval($fila["id_publicacion"]),
    "id_producto_erp" => intval($fila["id_producto_erp"]),
    "id_sku" => intval($fila["id_sku"]),
    "sku" => (string) $fila["sku"],
    "titulo_publico" => $titulo,
    "slug_anterior" => $slugActual,
    "slug_nuevo" => $slugNuevo,
    "url_publica" => "/producto/" . $slugNuevo,
    "canonical_url" => $base . "/producto/" . $slugNuevo,
    "cambia" => $cambia
  );
}

$cambios = array_values(array_filter($plan, function($item) { return !empty($item["cambia"]); }));
$resumen = array(
  "total_publicaciones" => count($plan),
  "cambios_slug" => count($cambios),
  "sin_cambio_slug" => count($plan) - count($cambios),
  "ejemplos" => array_slice($cambios, 0, 20)
);

if (!$apply) {
  salida(true, "Plan de slugs profesionales generado sin ejecutar", array(
    "modo" => "plan",
    "read_only" => true,
    "base" => MYSQLBASE,
    "resumen" => $resumen,
    "guardrails" => array("no_escribe_bd" => true)
  ));
}

$pdo->beginTransaction();
try {
  $stmtPub = $pdo->prepare("UPDATE erp_ecommerce_publicaciones
    SET slug=:slug, url_publica=:url_publica, canonical_url=:canonical_url, fecha_slug_actualizado=NOW(), fecha_actualizacion=NOW()
    WHERE id_publicacion=:id_publicacion LIMIT 1");
  $stmtRedir = $pdo->prepare("INSERT INTO erp_ecommerce_seo_redirecciones
      (url_origen, url_destino, from_slug, to_slug, tipo_entidad, id_entidad, id_publicacion, status_code, tipo, motivo, activo, revisado, fecha_registro, fecha_actualizacion)
    VALUES
      (:from, :to, :from_slug, :to_slug, 'producto', :id_entidad, :id_publicacion, 301, 'producto_slug', 'slug_profesional_pre_lanzamiento', 1, 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE
      url_destino=VALUES(url_destino), from_slug=VALUES(from_slug), to_slug=VALUES(to_slug), tipo_entidad='producto',
      id_entidad=VALUES(id_entidad), id_publicacion=VALUES(id_publicacion), status_code=301, tipo='producto_slug',
      motivo='slug_profesional_pre_lanzamiento', activo=1, revisado=1, fecha_actualizacion=NOW()");
  $actualizadas = 0;
  $redirecciones = 0;
  foreach ($plan as $item) {
    $stmtPub->execute(array(
      ":slug" => $item["slug_nuevo"],
      ":url_publica" => $item["url_publica"],
      ":canonical_url" => $item["canonical_url"],
      ":id_publicacion" => $item["id_publicacion"]
    ));
    $actualizadas += $stmtPub->rowCount();
    if (!empty($item["cambia"])) {
      $stmtRedir->execute(array(
        ":from" => "/producto/" . $item["slug_anterior"],
        ":to" => "/producto/" . $item["slug_nuevo"],
        ":from_slug" => $item["slug_anterior"],
        ":to_slug" => $item["slug_nuevo"],
        ":id_entidad" => $item["id_producto_erp"],
        ":id_publicacion" => $item["id_publicacion"]
      ));
      $redirecciones++;
    }
  }
  $pdo->commit();
  salida(true, "Slugs profesionales ecommerce aplicados", array(
    "modo" => "apply_authorized",
    "base" => MYSQLBASE,
    "respaldo" => $respaldo,
    "resumen" => $resumen,
    "filas_publicacion_actualizadas" => $actualizadas,
    "redirecciones_slug_registradas" => $redirecciones,
    "guardrails" => array(
      "conserva_301_slug_anterior" => true,
      "no_cambia_titulo" => true,
      "no_toca_inventario" => true
    )
  ));
} catch (Exception $e) {
  $pdo->rollBack();
  salida(false, $e->getMessage(), array("modo" => "apply_authorized", "ejecutado" => false));
}

function slugUnico($slugBase, $fila, &$usados) {
  $slugBase = $slugBase !== "" ? $slugBase : "producto-" . intval($fila["id_publicacion"]);
  $slug = $slugBase;
  if (!isset($usados[$slug])) { return $slug; }
  $sku = slugificar($fila["sku"]);
  $slug = $slugBase . ($sku !== "" && $sku !== "producto" ? "-" . $sku : "-" . intval($fila["id_publicacion"]));
  $contador = 2;
  while (isset($usados[$slug])) {
    $slug = $slugBase . "-" . intval($fila["id_publicacion"]) . "-" . $contador;
    $contador++;
  }
  return $slug;
}

function presentacionParaSlug($presentacion, $titulo) {
  $texto = strtolower(normalizarTexto($presentacion));
  $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, "UTF-8");
  $texto = preg_replace('/\b(c\/u|cu|pzas?|pza|pz|pieza?s?|unidad(?:es)?|unid(?:ad)?\.?)\b/i', ' ', $texto);
  $texto = preg_replace('/\b(\d+(?:\.\d+)?)\s*(kilogramos?|kgs?|kg)\b/i', '$1kg', $texto);
  $texto = preg_replace('/\b(\d+(?:\.\d+)?)\s*(gramos?|grs?|gr|g)\b/i', '$1g', $texto);
  $texto = preg_replace('/\b(\d+(?:\.\d+)?)\s*(mililitros?|mls?|ml)\b/i', '$1ml', $texto);
  $texto = preg_replace('/\b(\d+(?:\.\d+)?)\s*(litros?|lts?|lt|l)\b/i', '$1l', $texto);
  $texto = trim(preg_replace('/\s+/', ' ', $texto));
  if ($texto === "" || preg_match('/^\d+$/', $texto)) { return ""; }
  if (preg_match('/^(g|gr|kg|ml|l|lt|lts|cm|m)$/i', $texto)) { return ""; }
  $slugPresentacion = slugificar($texto);
  $slugTitulo = slugificar($titulo);
  if ($slugPresentacion === "" || $slugPresentacion === "producto") { return ""; }
  if ($slugTitulo !== "" && strpos("-" . $slugTitulo . "-", "-" . $slugPresentacion . "-") !== false) { return ""; }
  return $slugPresentacion;
}

function textoProductoParaSlug($texto) {
  $texto = strtolower(normalizarTexto($texto));
  $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, "UTF-8");
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(kilogramos?|kgs?|kg)\b/i', '$1kg', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(gramos?|grs?|gr|g)\b/i', '$1g', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(mililitros?|mls?|ml)\b/i', '$1ml', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(litros?|lts?|lt|l)\b/i', '$1l', $texto);
  $texto = str_replace(",", ".", $texto);
  return trim(preg_replace('/\s+/', ' ', $texto));
}

function slugificar($texto) {
  $texto = strtolower(normalizarTexto($texto));
  $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, "UTF-8");
  $transliterado = @iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
  if ($transliterado !== false) { $texto = strtolower($transliterado); }
  $texto = str_replace(array("&", "+"), " y ", $texto);
  $texto = preg_replace('/[\'"`´]+/', '', $texto);
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  $texto = trim($texto, '-');
  return substr($texto !== "" ? $texto : "producto", 0, 170);
}

function normalizarTexto($texto) {
  $buscar = array('á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','Ã¡','Ã©','Ã­','Ã³','Ãº','Ã¼','Ã±');
  $reemplazar = array('a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','u','n');
  return str_replace($buscar, $reemplazar, (string) $texto);
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
