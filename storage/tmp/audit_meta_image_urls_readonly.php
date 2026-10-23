<?php

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
require_once dirname(__DIR__, 2) . "/app/config/mysql.php";

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$sql = "SELECT pub.id_publicacion, pub.id_sku, s.sku, p.id_producto_erp,
    COALESCE(img_sku.url_imagen, img_prod.url_imagen) url_imagen
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku AND s.estatus='activo'
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp AND p.estatus='activo'
  LEFT JOIN erp_catalogo_imagenes img_sku ON img_sku.id_imagen_erp=(
    SELECT i.id_imagen_erp FROM erp_catalogo_imagenes i
    WHERE i.id_producto_erp=p.id_producto_erp AND i.id_sku=s.id_sku AND i.estatus='activo'
      AND TRIM(COALESCE(i.url_imagen,''))<>''
    ORDER BY CASE WHEN i.tipo_imagen='principal' THEN 0 ELSE 1 END, i.orden ASC, i.id_imagen_erp ASC LIMIT 1
  )
  LEFT JOIN erp_catalogo_imagenes img_prod ON img_prod.id_imagen_erp=(
    SELECT i.id_imagen_erp FROM erp_catalogo_imagenes i
    WHERE i.id_producto_erp=p.id_producto_erp AND (i.id_sku IS NULL OR i.id_sku=0) AND i.estatus='activo'
      AND TRIM(COALESCE(i.url_imagen,''))<>''
    ORDER BY CASE WHEN i.tipo_imagen='principal' THEN 0 ELSE 1 END, i.orden ASC, i.id_imagen_erp ASC LIMIT 1
  )
  WHERE pub.canal='catalogo_publico' AND pub.estatus_publicacion='publicado'
  ORDER BY pub.id_publicacion ASC";

$rows = $pdo->query($sql)->fetchAll();
$items = array();
$invalidSource = 0;
foreach ($rows as $row) {
  $source = trim((string) $row["url_imagen"]);
  if ($source === "") {
    $invalidSource++;
    continue;
  }
  if (preg_match('/^https?:\/\//i', $source)) {
    $url = $source;
  } else {
    $url = "https://sys.artiani.com.mx/" . ltrim($source, "/");
  }
  $items[] = array(
    "id_publicacion" => intval($row["id_publicacion"]),
    "id_sku" => intval($row["id_sku"]),
    "sku" => (string) $row["sku"],
    "id_producto_erp" => intval($row["id_producto_erp"]),
    "source_url" => $source,
    "candidate_url" => $url
  );
}

$resultados = array();
$cola = $items;
$activos = array();
$limiteConcurrente = 20;
$procesados = 0;
while (!empty($cola) || !empty($activos)) {
  while (count($activos) < $limiteConcurrente && !empty($cola)) {
    $item = array_shift($cola);
    $ch = curl_init($item["candidate_url"]);
    curl_setopt_array($ch, array(
      CURLOPT_NOBODY => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 4,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 8,
      CURLOPT_TIMEOUT => 15,
      CURLOPT_USERAGENT => "Artiani-Meta-Feed-Audit/1.0"
    ));
    curl_multi_add_handle($multi = isset($multi) ? $multi : curl_multi_init(), $ch);
    $activos[(int) $ch] = array("handle" => $ch, "item" => $item);
  }
  if (!isset($multi)) { break; }
  do { $estado = curl_multi_exec($multi, $enCurso); } while ($estado === CURLM_CALL_MULTI_PERFORM);
  if ($enCurso) { curl_multi_select($multi, 1.0); }
  foreach ($activos as $clave => $activo) {
    $ch = $activo["handle"];
    $info = curl_getinfo($ch);
    if ($info["http_code"] === 0 && $enCurso) { continue; }
    $item = $activo["item"];
    $item["http_code"] = intval($info["http_code"]);
    $item["content_type"] = (string) ($info["content_type"] ?? "");
    $item["content_length"] = intval($info["download_content_length"] ?? 0);
    $item["curl_error"] = curl_error($ch);
    $item["ok"] = $item["http_code"] >= 200 && $item["http_code"] < 400 && stripos($item["content_type"], "image/") === 0;
    $resultados[] = $item;
    curl_multi_remove_handle($multi, $ch);
    curl_close($ch);
    unset($activos[$clave]);
    $procesados++;
  }
}
if (isset($multi)) { curl_multi_close($multi); }

$fallos = array_values(array_filter($resultados, function ($item) { return empty($item["ok"]); }));
echo json_encode(array(
  "base" => MYSQLBASE,
  "total_publicaciones" => count($rows),
  "sin_imagen_origen" => $invalidSource,
  "total_urls_auditadas" => count($resultados),
  "urls_ok" => count($resultados) - count($fallos),
  "urls_fallidas" => count($fallos),
  "fallos_muestra" => array_slice($fallos, 0, 50),
  "resultados" => $resultados
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
