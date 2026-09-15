<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-14.
 * Proposito: diagnosticar por que un SKU ecommerce puede verse desactualizado en publicaciones/slugs.
 * Impacto: read-only; consulta catalogo ERP, publicacion ecommerce y patrones similares sin modificar BD.
 * Contrato: no ejecuta DDL, no actualiza publicaciones, no crea redirecciones.
 */

$opciones = getopt("", array("sku::", "limite::"));
$sku = strtoupper(trim(isset($opciones["sku"]) ? (string) $opciones["sku"] : "PERA-2020"));
$limite = max(1, min(100, intval(isset($opciones["limite"]) ? $opciones["limite"] : 25)));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$tablas = array(
  "publicaciones" => tablaExiste($pdo, "erp_ecommerce_publicaciones"),
  "skus" => tablaExiste($pdo, "erp_catalogo_skus"),
  "productos" => tablaExiste($pdo, "erp_catalogo_productos")
);

if (!$tablas["publicaciones"] || !$tablas["skus"] || !$tablas["productos"]) {
  salida(false, "Faltan tablas requeridas", array("tablas" => $tablas));
}

$stmt = $pdo->prepare("SELECT pub.id_publicacion, pub.id_producto_erp id_producto_publicacion, pub.id_sku id_sku_publicacion,
    pub.canal, pub.estatus_publicacion, pub.slug, pub.url_publica, pub.canonical_url,
    pub.titulo_publico, pub.presentacion_publica, pub.descripcion_publica,
    pub.fecha_registro fecha_publicacion_registro, pub.fecha_actualizacion fecha_publicacion_actualizacion,
    pub.fecha_slug_actualizado, pub.bloquear_slug_auto,
    s.id_sku, s.id_producto_erp, s.sku, s.nombre nombre_sku, s.estatus estatus_sku,
    p.codigo_producto, p.nombre nombre_producto, p.estatus estatus_producto
  FROM erp_catalogo_skus s
  LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.canal='catalogo_publico'
  WHERE UPPER(s.sku)=:sku
  ORDER BY pub.id_publicacion DESC");
$stmt->execute(array(":sku" => $sku));
$filas = $stmt->fetchAll();

$diagnostico = array();
foreach ($filas as $fila) {
  $slugActual = slugProductoProfesional(trim((string) $fila["titulo_publico"]));
  if ($slugActual === "") { $slugActual = slugificar(trim((string) $fila["nombre_sku"])); }
  $urlEsperada = $fila["slug"] !== null && trim((string) $fila["slug"]) !== "" ? "/producto/" . trim((string) $fila["slug"]) : "";
  $canonicalEsperada = $urlEsperada !== "" ? "https://artiani.com.mx" . $urlEsperada : "";
  $senales = array();
  if (trim((string) $fila["titulo_publico"]) !== "" && trim((string) $fila["nombre_sku"]) !== "" && trim((string) $fila["titulo_publico"]) !== trim((string) $fila["nombre_sku"])) {
    $senales[] = "titulo_publico_difiere_de_nombre_sku";
  }
  if (trim((string) $fila["slug"]) !== "" && $slugActual !== "" && trim((string) $fila["slug"]) !== $slugActual) {
    $senales[] = "slug_no_deriva_del_titulo_publico_actual";
  }
  if ($urlEsperada !== "" && trim((string) $fila["url_publica"]) !== "" && trim((string) $fila["url_publica"]) !== $urlEsperada) {
    $senales[] = "url_publica_desfasada_vs_slug";
  }
  if ($canonicalEsperada !== "" && trim((string) $fila["canonical_url"]) !== "" && trim((string) $fila["canonical_url"]) !== $canonicalEsperada) {
    $senales[] = "canonical_desfasada_vs_slug";
  }
  if (intval($fila["bloquear_slug_auto"]) === 1) {
    $senales[] = "slug_auto_bloqueado";
  }
  if (empty($fila["id_publicacion"])) {
    $senales[] = "sin_publicacion_ecommerce";
  }
  $diagnostico[] = array(
    "id_sku" => intval($fila["id_sku"]),
    "sku" => $fila["sku"],
    "nombre_sku" => $fila["nombre_sku"],
    "nombre_producto" => $fila["nombre_producto"],
    "estatus_sku" => $fila["estatus_sku"],
    "estatus_producto" => $fila["estatus_producto"],
    "publicacion" => array(
      "id_publicacion" => $fila["id_publicacion"] !== null ? intval($fila["id_publicacion"]) : null,
      "estatus_publicacion" => $fila["estatus_publicacion"],
      "titulo_publico" => $fila["titulo_publico"],
      "slug" => $fila["slug"],
      "url_publica" => $fila["url_publica"],
      "canonical_url" => $fila["canonical_url"],
      "fecha_actualizacion" => $fila["fecha_publicacion_actualizacion"],
      "fecha_slug_actualizado" => $fila["fecha_slug_actualizado"],
      "bloquear_slug_auto" => intval($fila["bloquear_slug_auto"])
    ),
    "esperado_si_se_sincroniza" => array(
      "slug_desde_titulo_publico" => $slugActual,
      "url_desde_slug_guardado" => $urlEsperada,
      "canonical_desde_slug_guardado" => $canonicalEsperada
    ),
    "senales" => $senales
  );
}

$similares = array();
$resumenPatrones = array();
if (!empty($filas)) {
  $resumenPatrones = $pdo->query("SELECT
      COUNT(*) total_publicaciones,
      SUM(CASE WHEN COALESCE(pub.bloquear_slug_auto, 0)=1 THEN 1 ELSE 0 END) slug_auto_bloqueado,
      SUM(CASE WHEN pub.titulo_publico IS NOT NULL AND s.nombre IS NOT NULL AND TRIM(pub.titulo_publico)<>'' AND TRIM(s.nombre)<>'' AND TRIM(pub.titulo_publico)<>TRIM(s.nombre) THEN 1 ELSE 0 END) titulo_publico_difiere_nombre_sku,
      SUM(CASE WHEN pub.slug IS NOT NULL AND TRIM(pub.slug)<>'' AND pub.url_publica IS NOT NULL AND TRIM(pub.url_publica)<>'' AND TRIM(pub.url_publica)<>CONCAT('/producto/', pub.slug) THEN 1 ELSE 0 END) url_publica_desfasada_vs_slug,
      SUM(CASE WHEN pub.slug IS NOT NULL AND TRIM(pub.slug)<>'' AND pub.canonical_url IS NOT NULL AND TRIM(pub.canonical_url)<>'' AND TRIM(pub.canonical_url)<>CONCAT('https://artiani.com.mx/producto/', pub.slug) THEN 1 ELSE 0 END) canonical_desfasada_vs_slug
    FROM erp_ecommerce_publicaciones pub
    INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
    WHERE pub.canal='catalogo_publico'")->fetch();

  $sql = "SELECT pub.id_publicacion, s.sku, s.nombre nombre_sku, pub.titulo_publico, pub.slug,
      pub.url_publica, pub.canonical_url, pub.fecha_actualizacion, pub.fecha_slug_actualizado, pub.bloquear_slug_auto
    FROM erp_ecommerce_publicaciones pub
    INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
    WHERE pub.canal='catalogo_publico'
      AND (
        (pub.titulo_publico IS NOT NULL AND s.nombre IS NOT NULL AND TRIM(pub.titulo_publico)<>'' AND TRIM(s.nombre)<>'' AND TRIM(pub.titulo_publico)<>TRIM(s.nombre))
        OR (pub.slug IS NOT NULL AND TRIM(pub.slug)<>'' AND pub.url_publica IS NOT NULL AND TRIM(pub.url_publica)<>'' AND TRIM(pub.url_publica)<>CONCAT('/producto/', pub.slug))
        OR (pub.slug IS NOT NULL AND TRIM(pub.slug)<>'' AND pub.canonical_url IS NOT NULL AND TRIM(pub.canonical_url)<>'' AND TRIM(pub.canonical_url)<>CONCAT('https://artiani.com.mx/producto/', pub.slug))
      )
    ORDER BY pub.fecha_actualizacion ASC, pub.id_publicacion ASC
    LIMIT " . intval($limite);
  $similares = $pdo->query($sql)->fetchAll();
}

salida(true, "Diagnostico read-only de SKU ecommerce", array(
  "sku_consultado" => $sku,
  "encontrado" => count($filas) > 0,
  "diagnostico" => $diagnostico,
  "resumen_patrones_catalogo_publico" => $resumenPatrones,
  "similares_potenciales_muestra" => $similares,
  "guardrails" => array(
    "read_only" => true,
    "no_actualiza_slug" => true,
    "no_crea_redirecciones" => true
  )
));

function tablaExiste($pdo, $tabla) {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla");
  $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
  return intval($stmt->fetchColumn()) > 0;
}

function slugificar($texto) {
  $texto = trim((string) $texto);
  if ($texto === "") { return ""; }
  $texto = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
  $texto = strtolower((string) $texto);
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  $texto = trim((string) $texto, '-');
  return preg_replace('/-+/', '-', $texto);
}

function slugProductoProfesional($texto) {
  $texto = strtolower(normalizarTextoPlano($texto));
  $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, "UTF-8");
  $texto = preg_replace('/\b(c\/u|cu|pzas?|pza|pz|pieza?s?|unidad(?:es)?|unid(?:ad)?\.?)\b/i', ' ', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(kilogramos?|kgs?|kg)\b/i', '$1kg', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(gramos?|grs?|gr|g)\b/i', '$1g', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(mililitros?|mls?|ml)\b/i', '$1ml', $texto);
  $texto = preg_replace('/\b(\d+(?:[\.,]\d+)?)\s*(litros?|lts?|lt|l)\b/i', '$1l', $texto);
  $texto = str_replace(",", ".", $texto);
  return slugificar(trim(preg_replace('/\s+/', ' ', $texto)));
}

function normalizarTextoPlano($texto) {
  $buscar = array('á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','Ã¡','Ã©','Ã­','Ã³','Ãº','Ã¼','Ã±','Ã','Ã‰','Ã','Ã“','Ãš','Ãœ','Ã‘','ÃƒÂ¡','ÃƒÂ©','ÃƒÂ­','ÃƒÂ³','ÃƒÂº','ÃƒÂ¼','ÃƒÂ±');
  $reemplazar = array('a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','u','n','A','E','I','O','U','U','N','a','e','i','o','u','u','n');
  return str_replace($buscar, $reemplazar, (string) $texto);
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
