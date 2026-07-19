<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: auditar candidatos para expandir el catalogo ecommerce publico despues del primer lote.
 * Impacto: permite decidir si conviene publicar mas SKUs disponibles o solo productos en modo consultar/agotado.
 * Contrato: read-only; no crea publicaciones, no escribe BD, no descuenta inventario y no toca legacy ecom_*.
 */

$opciones = getopt("", array("limite::", "pool::", "solo_disponibles::"));
$limite = isset($opciones["limite"]) ? max(5, min(100, intval($opciones["limite"]))) : 30;
$pool = isset($opciones["pool"]) ? max(100, min(3000, intval($opciones["pool"]))) : 1500;
$soloDisponibles = isset($opciones["solo_disponibles"]) && intval($opciones["solo_disponibles"]) === 1;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

class EcommerceCatalogoPublicoExpansionProbe extends EcommerceCatalogoPublico {
  public function conexionPublicaProbe() {
    return $this->getConexion();
  }
}

$modelo = new EcommerceCatalogoPublicoExpansionProbe();
$db = $modelo->conexionPublicaProbe();
if (!$db) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "read-only",
    "mensaje" => "Conexion MySQL no disponible",
    "bloqueos" => array("conexion_mysql_no_disponible")
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$sql = "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, s.sku,
    COALESCE(s.nombre, p.nombre) nombre,
    m.nombre marca,
    COALESCE(c.ruta, c.nombre) categoria,
    COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base,
    pr.precio, pr.moneda,
    img.url_imagen,
    COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
    COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
    COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
    pub.id_publicacion,
    pub.estatus_publicacion
  FROM erp_catalogo_skus s
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
  LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
  LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
  LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
  LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
  LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
  LEFT JOIN (
    SELECT i.id_producto_erp, i.url_imagen
    FROM erp_catalogo_imagenes i
    INNER JOIN (
      SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
      FROM erp_catalogo_imagenes
      WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
      GROUP BY id_producto_erp
    ) x ON x.id_imagen_erp=i.id_imagen_erp
  ) img ON img.id_producto_erp=p.id_producto_erp
  LEFT JOIN (
    SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
    FROM erp_inventario_existencias
    WHERE estatus_existencia IN ('disponible','agotada')
    GROUP BY id_sku_erp
  ) inv ON inv.id_sku_erp=s.id_sku
  LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.estatus_publicacion IN ('borrador','publicado','pausado')
  WHERE p.estatus='activo'
    AND s.estatus='activo'
    AND pr.id_sku_precio IS NOT NULL
    AND img.url_imagen IS NOT NULL
    AND pc.id_categoria_erp IS NOT NULL
    AND COALESCE(r.permite_venta_fraccionaria, 0)=0
    AND pub.id_publicacion IS NULL
  ORDER BY CASE
      WHEN COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)<>1 THEN 2
      WHEN COALESCE(inv.cantidad_disponible, 0)>3 THEN 0
      WHEN COALESCE(inv.cantidad_disponible, 0)>0 THEN 1
      ELSE 3
    END,
    p.nombre,
    s.sku
  LIMIT " . intval($pool);

$filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$resumenDisponibilidad = array(
  "disponible" => 0,
  "pocas_piezas" => 0,
  "consultar_disponibilidad" => 0,
  "agotado" => 0
);
$resumenMascotas = array();
$resumenNecesidades = array();
$resumenCategorias = array();
$candidatos = array();

foreach ($filas as $fila) {
  $disponibilidad = disponibilidadPublicaExpansion($fila);
  $resumenDisponibilidad[$disponibilidad]++;
  if ($soloDisponibles && !in_array($disponibilidad, array("disponible", "pocas_piezas"), true)) {
    continue;
  }

  $metadata = inferirMetadataExpansion($fila);
  sumarResumenExpansion($resumenMascotas, $metadata["mascota_especie"] !== "" ? $metadata["mascota_especie"] : "sin_inferir");
  foreach ($metadata["necesidades"] as $necesidad) {
    sumarResumenExpansion($resumenNecesidades, $necesidad);
  }
  sumarResumenExpansion($resumenCategorias, trim((string) $fila["categoria"]) !== "" ? (string) $fila["categoria"] : "sin_categoria");

  if (count($candidatos) < $limite) {
    $candidatos[] = array(
      "id_producto_erp" => intval($fila["id_producto_erp"]),
      "id_sku" => intval($fila["id_sku"]),
      "sku" => (string) $fila["sku"],
      "nombre" => (string) $fila["nombre"],
      "marca" => $fila["marca"],
      "categoria" => $fila["categoria"],
      "presentacion" => presentacionPublicaExpansion($fila),
      "precio" => floatval($fila["precio"]),
      "moneda" => (string) $fila["moneda"],
      "disponibilidad_publica_sugerida" => $disponibilidad,
      "mascota_especie_sugerida" => $metadata["mascota_especie"],
      "necesidades_sugeridas" => $metadata["necesidades"],
      "slug_sugerido" => slugExpansion($fila["nombre"] . " " . presentacionPublicaExpansion($fila) . " " . $fila["sku"]),
      "publicar_como" => in_array($disponibilidad, array("disponible", "pocas_piezas"), true) ? "borrador_recomendado" : "requiere_decision_agotado_o_consultar",
      "revision_negocio" => revisionNegocioExpansion($fila, $disponibilidad, $metadata)
    );
  }
}

ksort($resumenMascotas);
ksort($resumenNecesidades);
ksort($resumenCategorias);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "limite" => $limite,
  "pool" => $pool,
  "solo_disponibles" => $soloDisponibles,
  "resumen" => array(
    "candidatos_evaluados_sin_publicacion" => count($filas),
    "disponibilidad" => $resumenDisponibilidad,
    "candidatos_entregados" => count($candidatos),
    "mascotas" => $resumenMascotas,
    "necesidades" => $resumenNecesidades,
    "categorias_top" => array_slice($resumenCategorias, 0, 20, true)
  ),
  "decision_operativa" => array(
    "hay_mas_disponibles_para_publicar" => ($resumenDisponibilidad["disponible"] + $resumenDisponibilidad["pocas_piezas"]) > 0,
    "si_no_hay_disponibles" => "No publicar mas como disponible; elegir entre pausar expansion, publicar agotados como agotado, o publicar algunos como consultar disponibilidad.",
    "no_mostrar_stock_exacto" => true,
    "no_descuenta_inventario" => true
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_mueve_inventario" => true,
    "no_toca_ecom_legacy" => true
  ),
  "candidatos" => $candidatos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function disponibilidadPublicaExpansion($fila) {
  if (intval($fila["controla_inventario"]) !== 1) {
    return "consultar_disponibilidad";
  }
  $disponible = floatval($fila["existencia_disponible"]);
  if ($disponible <= 0) {
    return "agotado";
  }
  if ($disponible <= 3) {
    return "pocas_piezas";
  }
  return "disponible";
}

function presentacionPublicaExpansion($fila) {
  $presentacion = trim((string) $fila["presentacion_base"]);
  $genericas = array("g", "gr", "gramo", "gramos", "kg", "ml", "l", "lt", "pz", "pza", "pieza", "piezas");
  if ($presentacion !== "" && !in_array(strtolower($presentacion), $genericas, true)) {
    return $presentacion;
  }
  $texto = trim((string) $fila["nombre"] . " " . $fila["sku"]);
  if (preg_match('/\b(\d+(?:[.,]\d+)?)\s*(kg|kilos?|g|gr|gramos?|ml|pza|pz|piezas?)\b/i', $texto, $m)
    || preg_match('/\b(\d+(?:[.,]\d+)?)\s*(l|lt|litros?|lts)\b(?!\s*\/\s*hr)/i', (string) $fila["nombre"], $m)) {
    $cantidad = str_replace(",", ".", $m[1]);
    $unidad = strtolower($m[2]);
    $unidad = in_array($unidad, array("g", "gr", "gramo", "gramos"), true) ? "gr" : $unidad;
    $unidad = in_array($unidad, array("kilo", "kilos"), true) ? "kg" : $unidad;
    $unidad = in_array($unidad, array("l", "lt", "litro", "litros", "lts"), true) ? "lt" : $unidad;
    $unidad = in_array($unidad, array("pz", "pza", "pieza", "piezas"), true) ? "pz" : $unidad;
    return $cantidad . " " . $unidad;
  }
  return $presentacion;
}

function inferirMetadataExpansion($fila) {
  $texto = strtolower(normalizarExpansion(trim((string) $fila["nombre"] . " " . $fila["categoria"])));
  $mascota = "";
  $mapaMascotas = array(
    "perro" => array("perro", "canino", "cachorro"),
    "gato" => array("gato", "felino", "gatito"),
    "ave" => array("ave", "pajaro", "perico", "canario"),
    "pez" => array("pez", "peces", "acuario", "filtro", "filtracion", "oxigenacion", "actinia"),
    "reptil" => array("reptil", "tortuga", "iguana"),
    "roedor" => array("roedor", "hamster", "conejo", "cuyo")
  );
  foreach ($mapaMascotas as $clave => $palabras) {
    foreach ($palabras as $palabra) {
      if (strpos($texto, $palabra) !== false) {
        $mascota = $clave;
        break 2;
      }
    }
  }

  $necesidades = array();
  $mapaNecesidades = array(
    "alimento" => array("alimento", "croqueta", "comida", "lata", "dieta"),
    "premio" => array("premio", "snack", "treat", "galleta"),
    "higiene" => array("higiene", "arena", "shampoo", "limpieza", "sanitario"),
    "salud" => array("salud", "vitamina", "suplemento", "medicina", "antipulgas"),
    "paseo" => array("paseo", "collar", "correa", "pechera"),
    "habitat" => array("habitat", "cama", "jaula", "casa", "pecera", "acuario", "filtro", "filtracion", "oxigenacion", "iluminacion", "lampara", "actinia", "cascada", "canastilla"),
    "juguete" => array("juguete", "pelota", "mordedera"),
    "estetica" => array("estetica", "cepillo", "corte", "perfume")
  );
  foreach ($mapaNecesidades as $clave => $palabras) {
    foreach ($palabras as $palabra) {
      if (strpos($texto, $palabra) !== false) {
        $necesidades[] = $clave;
        break;
      }
    }
  }

  return array("mascota_especie" => $mascota, "necesidades" => array_values(array_unique($necesidades)));
}

function revisionNegocioExpansion($fila, $disponibilidad, $metadata) {
  $revision = array();
  if ($disponibilidad === "agotado") {
    $revision[] = "decidir_si_publicar_como_agotado_o_no_publicar";
  }
  if ($metadata["mascota_especie"] === "") {
    $revision[] = "validar_mascota";
  }
  if (empty($metadata["necesidades"])) {
    $revision[] = "validar_necesidad";
  }
  if (trim((string) $fila["marca"]) === "") {
    $revision[] = "marca_faltante_en_catalogo";
  }
  return $revision;
}

function sumarResumenExpansion(&$resumen, $clave) {
  $clave = trim((string) $clave);
  if ($clave === "") {
    return;
  }
  if (!isset($resumen[$clave])) {
    $resumen[$clave] = 0;
  }
  $resumen[$clave]++;
}

function slugExpansion($texto) {
  $texto = strtolower(normalizarExpansion($texto));
  $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
  return trim(substr($texto, 0, 170), "-");
}

function normalizarExpansion($texto) {
  $buscar = array('Ã¡','Ã©','Ã­','Ã³','Ãº','Ã¼','Ã±','Ã','Ã‰','Ã','Ã“','Ãš','Ãœ','Ã‘', 'á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ');
  $reemplazar = array('a','e','i','o','u','u','n','A','E','I','O','U','U','N', 'a', 'e', 'i', 'o', 'u', 'u', 'n');
  return str_replace($buscar, $reemplazar, (string) $texto);
}
