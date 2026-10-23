<?php
/**
 * Analisis read-only de SKUs activos no publicados en Ecommerce.
 * No modifica publicaciones, catalogo, precios, inventario ni slugs.
 */
require __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/EcommerceCatalogoPublico.php";

class AnalisisEcommerceNoPublicados extends EcommerceCatalogoPublico {
  public function dbPublica() { return $this->getConexion(); }
  public function auditarPublico($filtros) { return $this->auditarPublicabilidad($filtros); }
}

function contarValor(&$mapa, $clave) {
  $clave = trim((string) $clave);
  if ($clave === "") { $clave = "sin_valor"; }
  if (!isset($mapa[$clave])) { $mapa[$clave] = 0; }
  $mapa[$clave]++;
}

function recogerAuditoria($modelo, $estatus) {
  $salida = array();
  $pagina = 1;
  do {
    $respuesta = $modelo->auditarPublico(array(
      "estatus_publicacion" => $estatus,
      "limite" => 500,
      "pagina" => $pagina
    ));
    if (!empty($respuesta["error"])) {
      throw new Exception((string) ($respuesta["mensaje"] ?? "Error de auditoria"));
    }
    $dep = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($dep["candidatos"]) && is_array($dep["candidatos"]) ? $dep["candidatos"] : array();
    foreach ($items as $item) {
      $idSku = intval($item["id_sku"] ?? 0);
      if ($idSku > 0) { $salida[$idSku] = $item; }
    }
    $p = isset($dep["paginacion"]) && is_array($dep["paginacion"]) ? $dep["paginacion"] : array();
    $totalPaginas = max(1, intval($p["total_paginas"] ?? 1));
    $pagina++;
  } while ($pagina <= $totalPaginas);
  return $salida;
}

$modelo = new AnalisisEcommerceNoPublicados();
$db = $modelo->dbPublica();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$fechaBd = (string) $db->query("SELECT CURDATE()")->fetchColumn();

$porEstado = array(
  "sin_publicacion" => recogerAuditoria($modelo, "sin_publicacion"),
  "borrador" => recogerAuditoria($modelo, "borrador"),
  "pausado" => recogerAuditoria($modelo, "pausado")
);

$items = array();
foreach ($porEstado as $estadoSolicitado => $filas) {
  foreach ($filas as $idSku => $fila) {
    $idPublicacion = intval($fila["id_publicacion"] ?? 0);
    $estado = trim((string) ($fila["estatus_publicacion"] ?? ""));
    if ($estado === "") { $estado = "sin_publicacion"; }
    $bloqueos = isset($fila["bloqueos_publicacion"]) && is_array($fila["bloqueos_publicacion"])
      ? array_values(array_unique($fila["bloqueos_publicacion"])) : array();
    $alertas = isset($fila["alertas_editoriales"]) && is_array($fila["alertas_editoriales"])
      ? array_values(array_unique($fila["alertas_editoriales"])) : array();
    $items[$idSku] = array(
      "id_sku" => intval($idSku),
      "id_producto_erp" => intval($fila["id_producto_erp"] ?? 0),
      "id_publicacion" => $idPublicacion > 0 ? $idPublicacion : null,
      "estado_publicacion" => $estado,
      "sku" => (string) ($fila["sku"] ?? ""),
      "codigo_producto" => (string) ($fila["codigo_producto"] ?? ""),
      "nombre_sku" => (string) ($fila["nombre_sku_raw"] ?? ""),
      "nombre_producto" => (string) ($fila["nombre_producto_raw"] ?? ""),
      "marca" => (string) ($fila["marca"] ?? ""),
      "categoria" => (string) ($fila["categoria"] ?? ""),
      "precio" => isset($fila["precio"]) && $fila["precio"] !== null ? (float) $fila["precio"] : null,
      "lista_precio" => (string) ($fila["lista_nombre"] ?? ""),
      "imagen" => (string) ($fila["url_imagen"] ?? ""),
      "imagen_fuente" => (string) ($fila["imagen_fuente"] ?? ""),
      "existencia_disponible" => isset($fila["existencia_disponible"]) ? (float) $fila["existencia_disponible"] : 0,
      "permite_venta_fraccionaria" => intval($fila["permite_venta_fraccionaria"] ?? 0) === 1,
      "bloqueos" => $bloqueos,
      "alertas_editoriales" => $alertas,
      "fecha_producto" => null,
      "fecha_sku" => null,
      "fecha_publicacion_registro" => null,
      "fecha_publicacion_actualizacion" => null,
      "fecha_publicacion" => null
    );
  }
}

$idsSku = array_keys($items);
if (!empty($idsSku)) {
  $placeholders = implode(",", array_fill(0, count($idsSku), "?"));
  $sql = "SELECT s.id_sku, s.fecha_registro fecha_sku, s.fecha_actualizacion fecha_sku_actualizacion,
      p.fecha_registro fecha_producto, p.fecha_actualizacion fecha_producto_actualizacion,
      pub.fecha_registro fecha_publicacion_registro, pub.fecha_actualizacion fecha_publicacion_actualizacion,
      pub.fecha_publicacion
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.canal='catalogo_publico'
    WHERE s.id_sku IN (" . $placeholders . ")";
  $stmt = $db->prepare($sql);
  $stmt->execute(array_values($idsSku));
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $id = intval($row["id_sku"]);
    if (!isset($items[$id])) { continue; }
    $items[$id]["fecha_producto"] = $row["fecha_producto"];
    $items[$id]["fecha_sku"] = $row["fecha_sku"];
    $items[$id]["fecha_publicacion_registro"] = $row["fecha_publicacion_registro"];
    $items[$id]["fecha_publicacion_actualizacion"] = $row["fecha_publicacion_actualizacion"];
    $items[$id]["fecha_publicacion"] = $row["fecha_publicacion"];
  }
}

$resumen = array(
  "fecha_base_bd" => $fechaBd,
  "total_no_publicados" => count($items),
  "por_estado" => array("sin_publicacion" => 0, "borrador" => 0, "pausado" => 0),
  "por_bloqueo" => array(),
  "por_alerta_editorial" => array(),
  "por_marca" => array(),
  "por_categoria" => array(),
  "recientes" => array("ultimos_7_dias" => 0, "ultimos_30_dias" => 0, "ultimos_90_dias" => 0, "anteriores" => 0),
  "sin_precio" => 0,
  "sin_imagen" => 0,
  "con_stock" => 0,
  "sin_stock" => 0,
  "posible_granel" => 0
);
$tsBase = strtotime($fechaBd . " 23:59:59");
$ts7 = strtotime("-7 days", $tsBase);
$ts30 = strtotime("-30 days", $tsBase);
$ts90 = strtotime("-90 days", $tsBase);

foreach ($items as &$item) {
  contarValor($resumen["por_estado"], $item["estado_publicacion"]);
  foreach ($item["bloqueos"] as $bloqueo) { contarValor($resumen["por_bloqueo"], $bloqueo); }
  foreach ($item["alertas_editoriales"] as $alerta) { contarValor($resumen["por_alerta_editorial"], $alerta); }
  contarValor($resumen["por_marca"], $item["marca"]);
  contarValor($resumen["por_categoria"], $item["categoria"]);
  if ($item["precio"] === null || $item["precio"] <= 0) { $resumen["sin_precio"]++; }
  if (trim($item["imagen"]) === "") { $resumen["sin_imagen"]++; }
  if ($item["existencia_disponible"] > 0) { $resumen["con_stock"]++; } else { $resumen["sin_stock"]++; }
  if ($item["permite_venta_fraccionaria"] || in_array("posible_granel_textual", $item["bloqueos"], true)) { $resumen["posible_granel"]++; }
  $fechaReferencia = $item["fecha_sku"] ?: $item["fecha_producto"];
  $ts = $fechaReferencia ? strtotime($fechaReferencia) : false;
  if ($ts !== false && $ts >= $ts7) { $item["clasificacion_antiguedad"] = "nuevo_7_dias"; $resumen["recientes"]["ultimos_7_dias"]++; }
  elseif ($ts !== false && $ts >= $ts30) { $item["clasificacion_antiguedad"] = "nuevo_30_dias"; $resumen["recientes"]["ultimos_30_dias"]++; }
  elseif ($ts !== false && $ts >= $ts90) { $item["clasificacion_antiguedad"] = "nuevo_90_dias"; $resumen["recientes"]["ultimos_90_dias"]++; }
  else { $item["clasificacion_antiguedad"] = "anterior_90_dias"; $resumen["recientes"]["anteriores"]++; }
  $item["fecha_referencia_alta"] = $fechaReferencia;
}
unset($item);

arsort($resumen["por_bloqueo"]);
arsort($resumen["por_alerta_editorial"]);
arsort($resumen["por_marca"]);
arsort($resumen["por_categoria"]);

usort($items, function($a, $b) {
  $orden = array("nuevo_7_dias" => 0, "nuevo_30_dias" => 1, "nuevo_90_dias" => 2, "anterior_90_dias" => 3);
  $aa = $orden[$a["clasificacion_antiguedad"]] ?? 9;
  $bb = $orden[$b["clasificacion_antiguedad"]] ?? 9;
  if ($aa !== $bb) { return $aa <=> $bb; }
  $ta = $a["fecha_referencia_alta"] ? strtotime($a["fecha_referencia_alta"]) : 0;
  $tb = $b["fecha_referencia_alta"] ? strtotime($b["fecha_referencia_alta"]) : 0;
  if ($ta !== $tb) { return $tb <=> $ta; }
  return strcmp($a["sku"], $b["sku"]);
});

$salida = array(
  "ok" => true,
  "read_only" => true,
  "fuente" => "auditarPublicabilidad / Catalogo ERP",
  "criterio" => "productos y SKUs activos sin estatus publicado: sin_publicacion, borrador o pausado",
  "ventanas_nuevo" => array("7_dias" => $fechaBd . " menos 7 dias", "30_dias" => $fechaBd . " menos 30 dias", "90_dias" => $fechaBd . " menos 90 dias"),
  "resumen" => $resumen,
  "items" => array_values($items),
  "guardrails" => array("no_escribe_bd" => true, "no_publica" => true, "no_cambia_precios" => true, "no_cambia_inventario" => true, "no_cambia_slugs" => true)
);
$base = __DIR__ . "/ecommerce_productos_no_publicados_" . str_replace("-", "", $fechaBd);
file_put_contents($base . ".json", json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
$fp = fopen($base . ".csv", "wb");
fputcsv($fp, array("id_sku","sku","nombre_sku","nombre_producto","estado_publicacion","clasificacion_antiguedad","fecha_referencia_alta","fecha_producto","fecha_sku","marca","categoria","precio","lista_precio","tiene_imagen","existencia_disponible","permite_venta_fraccionaria","bloqueos","alertas_editoriales"));
foreach ($items as $item) {
  fputcsv($fp, array($item["id_sku"], $item["sku"], $item["nombre_sku"], $item["nombre_producto"], $item["estado_publicacion"], $item["clasificacion_antiguedad"], $item["fecha_referencia_alta"], $item["fecha_producto"], $item["fecha_sku"], $item["marca"], $item["categoria"], $item["precio"], $item["lista_precio"], trim($item["imagen"]) !== "" ? 1 : 0, $item["existencia_disponible"], $item["permite_venta_fraccionaria"] ? 1 : 0, implode("|", $item["bloqueos"]), implode("|", $item["alertas_editoriales"])));
}
fclose($fp);
echo json_encode(array("ok" => true, "fecha_base_bd" => $fechaBd, "total" => count($items), "resumen" => $resumen, "json" => $base . ".json", "csv" => $base . ".csv"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
