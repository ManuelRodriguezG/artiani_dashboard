<?php

$_SERVER["SERVER_NAME"] = "sys.artiani.com.mx";
require_once dirname(__DIR__, 2) . "/app/config/mysql.php";

$pdo = new PDO("mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE, MYSQLUSER, MYSQLPASS, array(
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
));

$tablas = array();
foreach (array("erp_ecommerce_publicaciones", "erp_catalogo_productos", "erp_catalogo_skus", "erp_catalogo_imagenes", "erp_productos") as $tabla) {
  $stmt = $pdo->prepare("SHOW TABLES LIKE :tabla");
  $stmt->execute(array(":tabla" => $tabla));
  $tablas[$tabla] = $stmt->fetchColumn() !== false;
}

$sql = "SELECT pub.id_publicacion, pub.id_sku, s.sku, p.id_producto_erp,
    COALESCE(img_sku.url_imagen, img_prod.url_imagen) url_imagen,
    CASE WHEN img_sku.url_imagen IS NOT NULL THEN 'sku' WHEN img_prod.url_imagen IS NOT NULL THEN 'producto' ELSE '' END imagen_fuente
  FROM erp_ecommerce_publicaciones pub
  INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
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
  WHERE pub.canal='catalogo_publico'
  ORDER BY pub.id_publicacion ASC LIMIT 12";

echo json_encode(array(
  "base" => MYSQLBASE,
  "tablas" => $tablas,
  "muestras" => $pdo->query($sql)->fetchAll()
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
