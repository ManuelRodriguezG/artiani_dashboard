<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: proponer un lote inicial de publicaciones ecommerce sin escribir BD.
 * Impacto: ayuda a elegir SKUs para Fase 1 con precio, imagen, categoria y sin granel.
 * Contrato: read-only; no crea publicaciones ni toca inventario.
 */

$args = isset($argv) ? $argv : array();
$limite = 30;
$soloDisponibles = false;
foreach ($args as $arg) {
  if (strpos($arg, "--limite=") === 0) {
    $limite = max(5, min(100, intval(trim(substr($arg, 9), "\"' "))));
  }
  $prefijoSoloDisponibles = "--solo_disponibles=";
  if (strpos($arg, $prefijoSoloDisponibles) === 0) {
    $soloDisponibles = intval(trim(substr($arg, strlen($prefijoSoloDisponibles)), "\"' ")) === 1;
  }
}
$poolLimite = min(500, max(200, $limite * 10));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();
$auditoria = $modelo->auditarPublicabilidad(array(
  "limite" => $poolLimite,
  "solo_publicables" => 1
));

$candidatos = valor($auditoria, array("depurar", "candidatos"), array());
$evaluados = array();
$lote = array();
$resumenMascotas = array();
$resumenNecesidades = array();
$resumenCategorias = array();
$descartesDiversidad = 0;

foreach ($candidatos as $fila) {
  $preparacion = $modelo->prepararPublicacion(array("id_sku" => intval($fila["id_sku"])));
  $sugerida = valor($preparacion, array("depurar", "publicacion_sugerida"), array());
  $producto = valor($preparacion, array("depurar", "producto_vivo_erp"), array());
  if ($soloDisponibles) {
    $disponibilidad = strtolower(trim((string) valor($producto, array("disponibilidad_publica_sugerida"), "")));
    if (!in_array($disponibilidad, array("disponible", "pocas_piezas"), true)) {
      continue;
    }
  }
  $mascota = trim((string) valor($sugerida, array("mascota_especie"), ""));
  $necesidades = valor($sugerida, array("necesidades"), array());
  $categoria = trim((string) valor($producto, array("categoria"), ""));

  $evaluados[] = array(
    "puntaje_prioridad" => calcularPuntajePrioridad($producto, $sugerida, $mascota, $necesidades),
    "categoria_diversidad" => claveDiversidad($categoria),
    "mascota_diversidad" => $mascota !== "" ? $mascota : "sin_inferir",
    "necesidad_diversidad" => !empty($necesidades) ? (string) reset($necesidades) : "sin_inferir",
    "producto" => $producto,
    "sugerida" => $sugerida,
    "preparacion" => $preparacion
  );
}

usort($evaluados, function($a, $b) {
  if ($a["puntaje_prioridad"] === $b["puntaje_prioridad"]) {
    return strcmp(
      (string) valor($a, array("producto", "nombre"), ""),
      (string) valor($b, array("producto", "nombre"), "")
    );
  }
  return $a["puntaje_prioridad"] < $b["puntaje_prioridad"] ? 1 : -1;
});

$topeMascota = max(3, (int) ceil($limite * 0.45));
$topeCategoria = max(2, (int) ceil($limite * 0.30));
$topeNecesidad = max(3, (int) ceil($limite * 0.50));
$conteoMascota = array();
$conteoCategoria = array();
$conteoNecesidad = array();

foreach ($evaluados as $evaluado) {
  if (count($lote) >= $limite) {
    break;
  }

  $mascotaClave = $evaluado["mascota_diversidad"];
  $categoriaClave = $evaluado["categoria_diversidad"];
  $necesidadClave = $evaluado["necesidad_diversidad"];
  $conteoM = isset($conteoMascota[$mascotaClave]) ? $conteoMascota[$mascotaClave] : 0;
  $conteoC = isset($conteoCategoria[$categoriaClave]) ? $conteoCategoria[$categoriaClave] : 0;
  $conteoN = isset($conteoNecesidad[$necesidadClave]) ? $conteoNecesidad[$necesidadClave] : 0;

  if ($conteoM >= $topeMascota || $conteoC >= $topeCategoria || $conteoN >= $topeNecesidad) {
    $descartesDiversidad++;
    continue;
  }

  $producto = $evaluado["producto"];
  $sugerida = $evaluado["sugerida"];
  $preparacion = $evaluado["preparacion"];
  $mascota = trim((string) valor($sugerida, array("mascota_especie"), ""));
  $necesidades = valor($sugerida, array("necesidades"), array());
  $categoria = trim((string) valor($producto, array("categoria"), ""));

  $conteoMascota[$mascotaClave] = $conteoM + 1;
  $conteoCategoria[$categoriaClave] = $conteoC + 1;
  $conteoNecesidad[$necesidadClave] = $conteoN + 1;

  if ($mascota !== "") {
    if (!isset($resumenMascotas[$mascota])) {
      $resumenMascotas[$mascota] = 0;
    }
    $resumenMascotas[$mascota]++;
  }
  foreach ($necesidades as $necesidad) {
    if (!isset($resumenNecesidades[$necesidad])) {
      $resumenNecesidades[$necesidad] = 0;
    }
    $resumenNecesidades[$necesidad]++;
  }
  if ($categoria !== "") {
    if (!isset($resumenCategorias[$categoria])) {
      $resumenCategorias[$categoria] = 0;
    }
    $resumenCategorias[$categoria]++;
  }

  $lote[] = construirItemLote($evaluado, $producto, $sugerida, $preparacion, $mascota, $necesidades);
}

if (count($lote) < $limite) {
  foreach ($evaluados as $evaluado) {
    if (count($lote) >= $limite) {
      break;
    }
    $idSkuEvaluado = intval(valor($evaluado, array("producto", "id_sku"), 0));
    if (loteContieneSku($lote, $idSkuEvaluado)) {
      continue;
    }

    $producto = $evaluado["producto"];
    $sugerida = $evaluado["sugerida"];
    $preparacion = $evaluado["preparacion"];
    $mascota = trim((string) valor($sugerida, array("mascota_especie"), ""));
    $necesidades = valor($sugerida, array("necesidades"), array());
    $categoria = trim((string) valor($producto, array("categoria"), ""));

    sumarResumen($resumenMascotas, $mascota);
    foreach ($necesidades as $necesidad) {
      sumarResumen($resumenNecesidades, $necesidad);
    }
    sumarResumen($resumenCategorias, $categoria);

    $lote[] = construirItemLote($evaluado, $producto, $sugerida, $preparacion, $mascota, $necesidades);
  }
}

usort($lote, function($a, $b) {
  if ($a["puntaje_prioridad"] === $b["puntaje_prioridad"]) {
    return strcmp((string) $a["nombre"], (string) $b["nombre"]);
  }
  return $a["puntaje_prioridad"] < $b["puntaje_prioridad"] ? 1 : -1;
});

ksort($resumenMascotas);
ksort($resumenNecesidades);
ksort($resumenCategorias);

echo json_encode(array(
  "ok" => empty($auditoria["error"]),
  "modo" => "read-only",
  "limite" => $limite,
  "solo_disponibles" => $soloDisponibles,
  "pool_evaluado" => count($evaluados),
  "total_lote" => count($lote),
  "auditoria" => array(
    "error" => isset($auditoria["error"]) ? (bool) $auditoria["error"] : null,
    "tipo" => isset($auditoria["tipo"]) ? $auditoria["tipo"] : "",
    "mensaje" => isset($auditoria["mensaje"]) ? $auditoria["mensaje"] : "",
    "candidatos_total" => count($candidatos)
  ),
  "resumen" => array(
    "skus_publicables_fase_1" => valor($auditoria, array("depurar", "resumen", "skus_publicables_fase_1"), 0),
    "mascotas_inferidas" => $resumenMascotas,
    "necesidades_inferidas" => $resumenNecesidades,
    "categorias" => $resumenCategorias,
    "descartes_por_diversidad" => $descartesDiversidad
  ),
  "criterios_priorizacion" => array(
    "preferir_perro_y_gato_sin_excluir_otras_especies",
    "preferir_alimento_higiene_salud_premio_y_productos_de_rotacion",
    "preferir_disponible_o_pocas_piezas_sin_mostrar_stock_exacto",
    "preferir_productos_con_marca_precio_e_imagen_aptos_para_fase_1",
    "limitar_concentracion_por_mascota_categoria_y_necesidad"
  ),
  "advertencias_revision" => advertenciasRevision($lote),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_mueve_inventario" => true,
    "no_toca_ecom_legacy" => true,
    "estatus_sugerido" => "borrador"
  ),
  "lote_sugerido" => $lote,
  "siguiente_paso" => "Revisar lote, ajustar mascota/necesidades y convertir a borrador solo despues de DDL autorizado."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valor($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function construirItemLote($evaluado, $producto, $sugerida, $preparacion, $mascota, $necesidades) {
  return array(
    "puntaje_prioridad" => $evaluado["puntaje_prioridad"],
    "id_producto_erp" => intval(valor($producto, array("id_producto_erp"), 0)),
    "id_sku" => intval(valor($producto, array("id_sku"), 0)),
    "sku" => valor($producto, array("sku"), ""),
    "nombre" => valor($producto, array("nombre"), ""),
    "marca" => valor($producto, array("marca"), ""),
    "categoria" => valor($producto, array("categoria"), ""),
    "presentacion" => valor($sugerida, array("presentacion_publica"), ""),
    "precio" => valor($producto, array("precio"), 0),
    "moneda" => valor($producto, array("moneda"), "MXN"),
    "disponibilidad_publica_sugerida" => valor($producto, array("disponibilidad_publica_sugerida"), ""),
    "slug_sugerido" => valor($sugerida, array("slug"), ""),
    "mascota_especie_sugerida" => $mascota,
    "necesidades_sugeridas" => $necesidades,
    "estatus_publicacion_sugerido" => "borrador",
    "publicable_fase_1" => valor($preparacion, array("depurar", "publicable_fase_1"), false),
    "bloqueos" => valor($preparacion, array("depurar", "bloqueos_publicacion"), array())
  );
}

function loteContieneSku($lote, $idSku) {
  foreach ($lote as $item) {
    if (intval(valor($item, array("id_sku"), 0)) === $idSku) {
      return true;
    }
  }
  return false;
}

function sumarResumen(&$resumen, $clave) {
  $clave = trim((string) $clave);
  if ($clave === "") {
    return;
  }
  if (!isset($resumen[$clave])) {
    $resumen[$clave] = 0;
  }
  $resumen[$clave]++;
}

function advertenciasRevision($lote) {
  $advertencias = array();
  $agotados = 0;
  $sinMascota = 0;
  foreach ($lote as $item) {
    if (valor($item, array("disponibilidad_publica_sugerida"), "") === "agotado") {
      $agotados++;
    }
    if (trim((string) valor($item, array("mascota_especie_sugerida"), "")) === "") {
      $sinMascota++;
    }
  }
  if ($agotados > 0) {
    $advertencias[] = "hay_" . $agotados . "_sku_agotados_revisar_si_publicar_como_consultar_o_sustituir";
  }
  if ($sinMascota > 0) {
    $advertencias[] = "hay_" . $sinMascota . "_sku_sin_mascota_inferida_revisar_taxonomia";
  }
  return $advertencias;
}

function calcularPuntajePrioridad($producto, $sugerida, $mascota, $necesidades) {
  $puntaje = 0;
  $mascota = strtolower(trim((string) $mascota));
  $disponibilidad = strtolower(trim((string) valor($producto, array("disponibilidad_publica_sugerida"), "")));
  $marca = trim((string) valor($producto, array("marca"), ""));
  $categoria = strtolower(trim((string) valor($producto, array("categoria"), "")));
  $nombre = strtolower(trim((string) valor($producto, array("nombre"), "")));
  $precio = floatval(valor($producto, array("precio"), 0));
  $presentacion = trim((string) valor($sugerida, array("presentacion_publica"), ""));
  $slug = trim((string) valor($sugerida, array("slug"), ""));

  if ($mascota === "perro" || $mascota === "gato") {
    $puntaje += 35;
  } elseif (in_array($mascota, array("pez", "ave", "roedor", "reptil"), true)) {
    $puntaje += 18;
  } elseif ($mascota !== "") {
    $puntaje += 8;
  }

  foreach ($necesidades as $necesidad) {
    $n = strtolower(trim((string) $necesidad));
    if (in_array($n, array("alimento", "higiene", "salud", "premio"), true)) {
      $puntaje += 18;
    } elseif (in_array($n, array("paseo", "juguete", "habitat"), true)) {
      $puntaje += 10;
    }
  }

  if ($disponibilidad === "disponible") {
    $puntaje += 45;
  } elseif ($disponibilidad === "pocas_piezas") {
    $puntaje += 30;
  } elseif ($disponibilidad === "consultar_disponibilidad") {
    $puntaje += 12;
  } elseif ($disponibilidad === "agotado") {
    $puntaje -= 45;
  }

  if ($marca !== "") {
    $puntaje += 8;
  }
  if ($precio > 0) {
    $puntaje += 10;
    if ($precio <= 2500) {
      $puntaje += 5;
    }
  }
  if ($presentacion !== "") {
    $puntaje += 5;
  }
  if ($slug !== "") {
    $puntaje += 4;
  }
  if (strpos($categoria . " " . $nombre, "granel") !== false) {
    $puntaje -= 30;
  }
  if (strpos($categoria, "acuario") !== false || strpos($nombre, "pecera") !== false) {
    $puntaje -= 5;
  }

  return $puntaje;
}

function claveDiversidad($valor) {
  $valor = strtolower(trim((string) $valor));
  if ($valor === "") {
    return "sin_categoria";
  }
  $partes = preg_split("/[>\\/|-]+/", $valor);
  $clave = trim((string) reset($partes));
  return $clave !== "" ? $clave : "sin_categoria";
}
