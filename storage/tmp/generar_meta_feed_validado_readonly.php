<?php

/**
 * Documentacion IA: Codex GPT-6 | Fecha: 2026-10-23.
 * Proposito: generar una muestra TSV de Meta desde Catalogo ERP productivo sin escrituras.
 * Impacto: crea archivos locales de revision; no modifica catalogo, precios, inventario ni ecommerce.
 * Contrato: usa publicaciones ecommerce, productos/SKU ERP, precios vigentes e imagenes validas por HTTP.
 */

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
require_once dirname(__DIR__, 2) . "/app/config/mysql.php";

$salidaDir = dirname(__DIR__) . "/tmp";
$tsvPath = $salidaDir . "/meta_catalog_feed_validado_20261023.tsv";
$auditoriaPath = $salidaDir . "/meta_catalog_feed_validado_auditoria_20261023.csv";
$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$imagenAuditPath = $salidaDir . "/audit_meta_image_urls_readonly.json";
$imagenAudit = json_decode((string) @file_get_contents($imagenAuditPath), true);
$imagenes = array();
foreach ((array) ($imagenAudit["resultados"] ?? array()) as $item) {
  $imagenes[(int) $item["id_publicacion"]] = $item;
}

$sql = "SELECT pub.id_publicacion, pub.id_sku, pub.slug, pub.titulo_publico, pub.descripcion_publica,
    pub.mostrar_precio, pub.mostrar_disponibilidad,
    s.sku, s.nombre nombre_sku, s.tipo_inventario, p.nombre nombre_producto,
    m.nombre marca, COALESCE(c.ruta, c.nombre, '') categoria,
    COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
    COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
    (SELECT d.precio
      FROM erp_listas_precios_detalle d
      INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
      WHERE d.id_sku=s.id_sku AND d.estatus='activo' AND d.precio>0
        AND COALESCE(NULLIF(d.moneda,''),'MXN')='MXN' AND l.estatus='activa'
        AND LOWER(COALESCE(l.codigo,'')) NOT LIKE '%mayoreo%'
        AND LOWER(COALESCE(l.nombre,'')) NOT LIKE '%mayoreo%'
        AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
        AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
        AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
        AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
      ORDER BY l.prioridad ASC, d.id_lista_precio_detalle DESC LIMIT 1) precio,
    (SELECT COALESCE(NULLIF(d.moneda,''),'MXN')
      FROM erp_listas_precios_detalle d
      INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
      WHERE d.id_sku=s.id_sku AND d.estatus='activo' AND d.precio>0
        AND COALESCE(NULLIF(d.moneda,''),'MXN')='MXN' AND l.estatus='activa'
        AND LOWER(COALESCE(l.codigo,'')) NOT LIKE '%mayoreo%'
        AND LOWER(COALESCE(l.nombre,'')) NOT LIKE '%mayoreo%'
        AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
        AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
        AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
        AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
      ORDER BY l.prioridad ASC, d.id_lista_precio_detalle DESC LIMIT 1) moneda
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku AND s.estatus='activo'
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp AND p.estatus='activo'
  LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
  LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
  LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
  LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
  LEFT JOIN (SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible FROM erp_inventario_existencias WHERE estatus_existencia IN ('disponible','agotada') GROUP BY id_sku_erp) inv ON inv.id_sku_erp=s.id_sku
  WHERE pub.canal='catalogo_publico' AND pub.estatus_publicacion='publicado'
  ORDER BY pub.orden ASC, pub.id_publicacion ASC";

$headers = array("id", "title", "description", "availability", "condition", "price", "link", "image_link", "brand", "google_product_category", "fb_product_category", "quantity_to_sell_on_facebook");
$feedRows = array($headers);
$auditRows = array(array("id_publicacion", "sku", "resultado", "motivo", "url_imagen_origen", "url_imagen_feed", "url_producto"));

function metaValue($value) {
  return trim(str_replace(array("\t", "\r", "\n"), " ", (string) $value));
}
function metaCategory($name, $category) {
  $text = strtolower(iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $name . " " . $category));
  $rules = array(
    array("/(alimento|comida).*(pez|acuario)/", "Animals & Pet Supplies > Pet Supplies > Fish Supplies > Fish Food"),
    array("/filtro.*acuario|acuario.*filtro/", "Animals & Pet Supplies > Pet Supplies > Fish Supplies > Aquarium Filters"),
    array("/grava|sustrato.*acuario|arena.*acuario/", "Animals & Pet Supplies > Pet Supplies > Fish Supplies > Aquarium Gravel & Substrates"),
    array("/adorno.*acuario|decoracion.*acuario|castillo.*acuario/", "Animals & Pet Supplies > Pet Supplies > Fish Supplies > Aquarium Decor"),
    array("/calentador|termostato.*acuario/", "Animals & Pet Supplies > Pet Supplies > Fish Supplies > Aquarium Temperature Controllers"),
    array("/(alimento|croqueta).*(perro)/", "Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Food"),
    array("/juguete.*perro|perro.*juguete/", "Animals & Pet Supplies > Pet Supplies > Dog Supplies > Dog Toys"),
    array("/(alimento|croqueta).*(gato)/", "Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Food"),
    array("/juguete.*gato|gato.*juguete/", "Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Toys"),
    array("/arena.*gato|arenero|gato.*arena/", "Animals & Pet Supplies > Pet Supplies > Cat Supplies > Cat Litter"),
    array("/transportadora|transportador|carrier|kennel/", "Animals & Pet Supplies > Pet Supplies > Pet Carriers & Crates"),
    array("/cepillo|peine|rasuradora|grooming|estetica/", "Animals & Pet Supplies > Pet Supplies > Pet Grooming Supplies")
  );
  foreach ($rules as $rule) { if (preg_match($rule[0], $text)) { return $rule[1]; } }
  return "";
}

$rows = $pdo->query($sql)->fetchAll();
foreach ($rows as $row) {
  $id = (int) $row["id_publicacion"];
  $nombre = trim((string) ($row["titulo_publico"] ?: $row["nombre_sku"] ?: $row["nombre_producto"]));
  $descripcion = trim(html_entity_decode(strip_tags((string) $row["descripcion_publica"]), ENT_QUOTES | ENT_HTML5, "UTF-8"));
  $marca = trim((string) $row["marca"]);
  $precio = (float) $row["precio"];
  $imagenAuditada = $imagenes[$id] ?? null;
  $urlImagen = $imagenAuditada["candidate_url"] ?? "";
  $urlProducto = "https://artiani.com.mx/producto/" . ltrim((string) $row["slug"], "/");
  $motivo = "";
  if (!$imagenAuditada) { $motivo = "sin_resultado_de_auditoria_imagen"; }
  elseif (empty($imagenAuditada["ok"])) { $motivo = "imagen_no_accesible_o_no_es_imagen"; }
  elseif ($nombre === "") { $motivo = "sin_titulo"; }
  elseif ($descripcion === "") { $motivo = "sin_descripcion"; }
  elseif ($marca === "") { $motivo = "sin_marca"; }
  elseif ($precio <= 0) { $motivo = "sin_precio_vigente"; }
  elseif (trim((string) $row["slug"]) === "") { $motivo = "sin_slug_publico"; }
  if ($motivo !== "") {
    $auditRows[] = array($id, (string) $row["sku"], "excluido", $motivo, (string) ($imagenAuditada["source_url"] ?? ""), $urlImagen, $urlProducto);
    continue;
  }
  $availability = "in stock";
  $feedRows[] = array(
    metaValue($row["sku"]), metaValue($nombre), metaValue($descripcion), $availability, "new",
    number_format($precio, 2, ".", "") . " MXN", $urlProducto, $urlImagen, metaValue($marca), metaCategory($nombre, $row["categoria"]), "", ""
  );
  $auditRows[] = array($id, (string) $row["sku"], "incluido", "", (string) ($imagenAuditada["source_url"] ?? ""), $urlImagen, $urlProducto);
}

$tsv = fopen($tsvPath, "wb");
foreach ($feedRows as $row) { fwrite($tsv, implode("\t", array_map("metaValue", $row)) . "\n"); }
fclose($tsv);
$csv = fopen($auditoriaPath, "wb");
fwrite($csv, "\xEF\xBB\xBF");
foreach ($auditRows as $row) { fputcsv($csv, $row); }
fclose($csv);

$incluidos = count($feedRows) - 1;
echo json_encode(array(
  "base" => MYSQLBASE,
  "publicaciones_revisadas" => count($rows),
  "incluidos" => $incluidos,
  "excluidos" => count($auditRows) - 1 - $incluidos,
  "tsv" => $tsvPath,
  "auditoria" => $auditoriaPath
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
