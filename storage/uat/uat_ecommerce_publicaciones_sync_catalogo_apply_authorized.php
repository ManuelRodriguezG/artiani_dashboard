<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-14.
 * Proposito: sincronizar publicaciones ecommerce con el nombre actual del catalogo ERP y regenerar slugs/canonicals.
 * Impacto: actualiza `erp_ecommerce_publicaciones` para que titulo publico, slug, url_publica y canonical_url queden congruentes con catalogo.
 * Contrato: modo plan read-only por defecto; apply requiere token, genera rollback externo y NO crea redirecciones internas de slug.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$args = argumentos($argv);
$modo = isset($args["modo"]) ? strtolower($args["modo"]) : "plan";
$token = isset($args["autorizar"]) ? trim((string) $args["autorizar"]) : "";
$base = isset($args["base"]) ? rtrim(trim((string) $args["base"]), "/") : "https://artiani.com.mx";
$limiteEjemplos = max(1, min(50, intval(isset($args["ejemplos"]) ? $args["ejemplos"] : 20)));
$tokenEsperado = "ECOMMERCE_PUBLICACIONES_SYNC_CATALOGO";
$apply = $modo === "apply";

if ($apply && $token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

validarTablas($pdo);

$filas = $pdo->query("SELECT pub.id_publicacion, pub.id_producto_erp id_producto_publicacion, pub.id_sku,
    pub.canal, pub.estatus_publicacion, pub.slug, pub.url_publica, pub.canonical_url,
    pub.titulo_publico, pub.presentacion_publica, pub.fecha_actualizacion,
    s.id_producto_erp id_producto_catalogo, s.sku, s.nombre nombre_sku, s.estatus estatus_sku,
    p.nombre nombre_producto, p.estatus estatus_producto,
    COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
  LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
  LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
  WHERE pub.canal='catalogo_publico'
  ORDER BY pub.id_publicacion ASC")->fetchAll();

$usados = array();
$plan = array();
foreach ($filas as $fila) {
  $tituloNuevo = trim((string) $fila["nombre_sku"]);
  if ($tituloNuevo === "") { $tituloNuevo = trim((string) $fila["nombre_producto"]); }
  if ($tituloNuevo === "") { $tituloNuevo = trim((string) $fila["titulo_publico"]); }
  $tituloNuevo = limpiarTituloCatalogo($tituloNuevo);

  $presentacion = trim((string) ($fila["presentacion_publica"] ?: $fila["presentacion_base"]));
  $tituloSlug = textoProductoParaSlug($tituloNuevo);
  $slugBase = slugificar(trim($tituloSlug . " " . presentacionParaSlug($presentacion, $tituloSlug)));
  $slugNuevo = slugUnico($slugBase, $fila, $usados);
  $usados[$slugNuevo] = intval($fila["id_publicacion"]);

  $urlNueva = "/producto/" . $slugNuevo;
  $canonicalNueva = $base . $urlNueva;
  $slugActual = slugificar($fila["slug"]);
  $tituloActual = trim((string) $fila["titulo_publico"]);
  $urlActual = trim((string) $fila["url_publica"]);
  $canonicalActual = trim((string) $fila["canonical_url"]);
  $idProductoActual = intval($fila["id_producto_publicacion"]);
  $idProductoNuevo = intval($fila["id_producto_catalogo"]);

  $cambios = array();
  if ($tituloActual !== $tituloNuevo) { $cambios[] = "titulo_publico"; }
  if ($slugActual !== $slugNuevo) { $cambios[] = "slug"; }
  if ($urlActual !== $urlNueva) { $cambios[] = "url_publica"; }
  if ($canonicalActual !== $canonicalNueva) { $cambios[] = "canonical_url"; }
  if ($idProductoNuevo > 0 && $idProductoActual !== $idProductoNuevo) { $cambios[] = "id_producto_erp"; }

  $plan[] = array(
    "id_publicacion" => intval($fila["id_publicacion"]),
    "id_sku" => intval($fila["id_sku"]),
    "sku" => (string) $fila["sku"],
    "estatus_publicacion" => (string) $fila["estatus_publicacion"],
    "estatus_sku" => (string) $fila["estatus_sku"],
    "estatus_producto" => (string) $fila["estatus_producto"],
    "id_producto_anterior" => $idProductoActual,
    "id_producto_nuevo" => $idProductoNuevo,
    "titulo_anterior" => $tituloActual,
    "titulo_nuevo" => $tituloNuevo,
    "slug_anterior" => $slugActual,
    "slug_nuevo" => $slugNuevo,
    "url_anterior" => $urlActual,
    "url_nueva" => $urlNueva,
    "canonical_anterior" => $canonicalActual,
    "canonical_nueva" => $canonicalNueva,
    "cambios" => $cambios,
    "cambia" => !empty($cambios)
  );
}

$cambios = array_values(array_filter($plan, function($item) { return !empty($item["cambia"]); }));
$resumen = resumenPlan($plan, $cambios, $limiteEjemplos);

if (!$apply) {
  salida(true, "Plan de sincronizacion catalogo -> ecommerce generado sin ejecutar", array(
    "modo" => "plan",
    "read_only" => true,
    "base" => MYSQLBASE,
    "base_canonical" => $base,
    "resumen" => $resumen,
    "guardrails" => array(
      "no_escribe_bd" => true,
      "no_crea_redirecciones" => true,
      "actualiza_solo_publicaciones_ecommerce" => true
    )
  ));
}

$rollback = crearRollback($pdo, $cambios);
$pdo->beginTransaction();
try {
  $stmt = $pdo->prepare("UPDATE erp_ecommerce_publicaciones
    SET id_producto_erp=:id_producto_erp,
      titulo_publico=:titulo_publico,
      slug=:slug,
      url_publica=:url_publica,
      canonical_url=:canonical_url,
      fecha_slug_actualizado=CASE WHEN slug<>:slug_cmp THEN NOW() ELSE fecha_slug_actualizado END,
      bloquear_slug_auto=1,
      fecha_actualizacion=NOW()
    WHERE id_publicacion=:id_publicacion
    LIMIT 1");
  $filasAfectadas = 0;
  foreach ($cambios as $item) {
    $stmt->execute(array(
      ":id_producto_erp" => intval($item["id_producto_nuevo"] > 0 ? $item["id_producto_nuevo"] : $item["id_producto_anterior"]),
      ":titulo_publico" => $item["titulo_nuevo"],
      ":slug" => $item["slug_nuevo"],
      ":slug_cmp" => $item["slug_nuevo"],
      ":url_publica" => $item["url_nueva"],
      ":canonical_url" => $item["canonical_nueva"],
      ":id_publicacion" => intval($item["id_publicacion"])
    ));
    $filasAfectadas += $stmt->rowCount();
  }
  $pdo->commit();
  salida(true, "Publicaciones ecommerce sincronizadas con catalogo", array(
    "modo" => "apply_authorized",
    "base" => MYSQLBASE,
    "base_canonical" => $base,
    "rollback" => $rollback,
    "resumen" => $resumen,
    "filas_publicacion_actualizadas" => $filasAfectadas,
    "guardrails" => array(
      "no_crea_redirecciones" => true,
      "no_toca_inventario" => true,
      "no_toca_precios" => true,
      "no_toca_ecom_legacy" => true,
      "rollback_externo" => true
    )
  ));
} catch (Exception $e) {
  if ($pdo->inTransaction()) { $pdo->rollBack(); }
  salida(false, $e->getMessage(), array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "rollback" => $rollback
  ));
}

function resumenPlan($plan, $cambios, $limiteEjemplos) {
  $porCampo = array(
    "titulo_publico" => 0,
    "slug" => 0,
    "url_publica" => 0,
    "canonical_url" => 0,
    "id_producto_erp" => 0
  );
  foreach ($cambios as $item) {
    foreach ($item["cambios"] as $campo) {
      if (!isset($porCampo[$campo])) { $porCampo[$campo] = 0; }
      $porCampo[$campo]++;
    }
  }
  return array(
    "total_publicaciones" => count($plan),
    "total_a_actualizar" => count($cambios),
    "sin_cambio" => count($plan) - count($cambios),
    "cambios_por_campo" => $porCampo,
    "ejemplos" => array_slice($cambios, 0, $limiteEjemplos)
  );
}

function crearRollback($pdo, $cambios) {
  $dir = "C:\\xampp\\panel_db_backups";
  if (!is_dir($dir)) { mkdir($dir, 0775, true); }
  $archivo = $dir . "\\artianicom_sys_panel_" . date("Ymd_His") . "_rollback_ecommerce_publicaciones_sync_catalogo.sql";
  $lineas = array();
  $lineas[] = "-- Rollback generado por uat_ecommerce_publicaciones_sync_catalogo_apply_authorized.php";
  $lineas[] = "-- Fecha: " . date("Y-m-d H:i:s");
  $lineas[] = "START TRANSACTION;";
  $stmt = $pdo->prepare("SELECT id_publicacion, id_producto_erp, titulo_publico, slug, url_publica, canonical_url, fecha_slug_actualizado, bloquear_slug_auto, fecha_actualizacion
    FROM erp_ecommerce_publicaciones
    WHERE id_publicacion=:id_publicacion
    LIMIT 1");
  foreach ($cambios as $item) {
    $stmt->execute(array(":id_publicacion" => intval($item["id_publicacion"])));
    $row = $stmt->fetch();
    if (!$row) { continue; }
    $lineas[] = "UPDATE `erp_ecommerce_publicaciones` SET " .
      "`id_producto_erp`=" . sqlValor($row["id_producto_erp"]) . ", " .
      "`titulo_publico`=" . sqlValor($row["titulo_publico"]) . ", " .
      "`slug`=" . sqlValor($row["slug"]) . ", " .
      "`url_publica`=" . sqlValor($row["url_publica"]) . ", " .
      "`canonical_url`=" . sqlValor($row["canonical_url"]) . ", " .
      "`fecha_slug_actualizado`=" . sqlValor($row["fecha_slug_actualizado"]) . ", " .
      "`bloquear_slug_auto`=" . sqlValor($row["bloquear_slug_auto"]) . ", " .
      "`fecha_actualizacion`=" . sqlValor($row["fecha_actualizacion"]) .
      " WHERE `id_publicacion`=" . intval($row["id_publicacion"]) . " LIMIT 1;";
  }
  $lineas[] = "COMMIT;";
  file_put_contents($archivo, implode(PHP_EOL, $lineas) . PHP_EOL);
  return $archivo;
}

function validarTablas($pdo) {
  foreach (array("erp_ecommerce_publicaciones", "erp_catalogo_skus", "erp_catalogo_productos") as $tabla) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    if (intval($stmt->fetchColumn()) <= 0) {
      salida(false, "Falta tabla requerida", array("tabla" => $tabla));
    }
  }
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

function limpiarTituloCatalogo($texto) {
  $texto = trim((string) $texto);
  $texto = preg_replace('/(\d)\s*�\s*(\d)/u', '$1 x $2', $texto);
  return trim(preg_replace('/\s+/', ' ', $texto));
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
  $texto = preg_replace('/\b(c\/u|cu|pzas?|pza|pz|pieza?s?|unidad(?:es)?|unid(?:ad)?\.?)\b/i', ' ', $texto);
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
  $texto = preg_replace('/[\'"`Â´]+/', '', $texto);
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  $texto = trim($texto, '-');
  return substr($texto !== "" ? $texto : "producto", 0, 170);
}

function normalizarTexto($texto) {
  $buscar = array('á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','Ã¡','Ã©','Ã­','Ã³','Ãº','Ã¼','Ã±','Ã','Ã‰','Ã','Ã“','Ãš','Ãœ','Ã‘','ÃƒÂ¡','ÃƒÂ©','ÃƒÂ­','ÃƒÂ³','ÃƒÂº','ÃƒÂ¼','ÃƒÂ±');
  $reemplazar = array('a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','u','n');
  return str_replace($buscar, $reemplazar, (string) $texto);
}

function sqlValor($valor) {
  if ($valor === null) { return "NULL"; }
  if (is_int($valor) || is_float($valor) || (is_string($valor) && preg_match('/^-?\d+(?:\.\d+)?$/', $valor))) {
    return (string) $valor;
  }
  return "'" . str_replace("'", "''", (string) $valor) . "'";
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
