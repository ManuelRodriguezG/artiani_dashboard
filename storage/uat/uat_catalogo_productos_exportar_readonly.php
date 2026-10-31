<?php
/**
 * IA: Codex GPT-5 | Fecha: 2026-10-29
 * Proposito: exportar el catalogo ERP actual en CSV, incluyendo portada productiva cuando exista.
 * Impacto: Catalogo ERP; solo lectura de productos, SKUs, marcas, categorias e imagenes.
 * Contrato: excluye productos fusionados y convierte rutas relativas al host productivo de recursos.
 */

require_once __DIR__ . "/../../app/iniciador.php";

const HOST_RECURSOS_PRODUCTIVO = "https://sys.artiani.com.mx/";
const ARCHIVO_SALIDA = __DIR__ . "/catalogo_productos_actualizado_20261029.csv";

try {
  $pdo = new PDO(
    "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8mb4",
    MYSQLUSER,
    MYSQLPASS,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
  );

  $sql = "SELECT p.id_producto_erp,
      p.codigo_producto,
      p.nombre,
      p.estatus,
      COALESCE(m.nombre, '') AS marca,
      COALESCE(cat.ruta, cat.nombre, '') AS categoria_principal,
      COALESCE(skus.skus, '') AS skus,
      COALESCE(portada.url_imagen, '') AS ruta_portada,
      CASE WHEN portada.id_imagen_erp IS NULL THEN 0 ELSE 1 END AS tiene_portada
    FROM erp_catalogo_productos p
    LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
    LEFT JOIN erp_catalogo_producto_categorias pc
      ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
    LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp
    LEFT JOIN (
      SELECT s.id_producto_erp,
        GROUP_CONCAT(s.sku ORDER BY s.id_sku SEPARATOR ' | ') AS skus
      FROM erp_catalogo_skus s
      WHERE s.estatus<>'fusionado'
      GROUP BY s.id_producto_erp
    ) skus ON skus.id_producto_erp=p.id_producto_erp
    LEFT JOIN erp_catalogo_imagenes portada
      ON portada.id_imagen_erp=(
        SELECT i.id_imagen_erp
        FROM erp_catalogo_imagenes i
        WHERE i.id_producto_erp=p.id_producto_erp
          AND i.tipo_imagen='portada'
          AND i.estatus='activo'
          AND TRIM(COALESCE(i.url_imagen,''))<>''
        ORDER BY i.orden ASC, i.id_imagen_erp ASC
        LIMIT 1
      )
    WHERE p.estatus<>'fusionado'
    ORDER BY p.id_producto_erp ASC";

  $filas = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  $salida = fopen(ARCHIVO_SALIDA, "wb");
  if (!$salida) {
    throw new RuntimeException("No se pudo crear el CSV de salida");
  }

  // BOM para que Excel reconozca correctamente acentos y caracteres UTF-8.
  fwrite($salida, "\xEF\xBB\xBF");
  fputcsv($salida, array(
    "id_producto_erp",
    "codigo_producto",
    "nombre",
    "estatus",
    "marca",
    "categoria_principal",
    "skus_no_fusionados",
    "tiene_portada",
    "ruta_portada_catalogo",
    "url_portada_productiva"
  ));

  $conPortada = 0;
  foreach ($filas as $fila) {
    $ruta = trim((string) $fila["ruta_portada"]);
    $url = $ruta === "" ? "" : HOST_RECURSOS_PRODUCTIVO . ltrim($ruta, "/");
    if ($ruta !== "") {
      $conPortada++;
    }
    fputcsv($salida, array(
      $fila["id_producto_erp"],
      $fila["codigo_producto"],
      $fila["nombre"],
      $fila["estatus"],
      $fila["marca"],
      $fila["categoria_principal"],
      $fila["skus"],
      $ruta === "" ? "no" : "si",
      $ruta,
      $url
    ));
  }
  fclose($salida);

  echo json_encode(array(
    "error" => false,
    "archivo" => ARCHIVO_SALIDA,
    "total_productos" => count($filas),
    "con_portada" => $conPortada,
    "sin_portada" => count($filas) - $conPortada,
    "host_imagenes" => HOST_RECURSOS_PRODUCTIVO
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
  echo json_encode(array(
    "error" => true,
    "mensaje" => $e->getMessage()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(1);
}
