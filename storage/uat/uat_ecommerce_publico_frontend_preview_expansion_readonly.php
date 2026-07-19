<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: generar preview simulado post-expansion para el frontend con 2 publicados + candidatos reales.
 * Impacto: permite disenar y probar UI de catalogo ampliado antes de crear/publicar borradores.
 * Contrato: read-only; no crea publicaciones, no publica, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::", "skus::", "resumen::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "415,866,386,1138";
$soloResumen = !empty($opciones["resumen"]);

$skus = array();
foreach (explode(",", $skusTexto) as $sku) {
  $idSku = intval(trim($sku));
  if ($idSku > 0) {
    $skus[] = $idSku;
  }
}
$skus = array_values(array_unique($skus));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 60));
$publicados = valorPreviewExpansion($catalogo, array("depurar", "items"), array());

$simulados = array();
$bloqueos = array();
$idTemporal = 900000;
foreach ($skus as $idSku) {
  $preparacion = $api->prepararPublicacion(array("id_sku" => $idSku));
  $producto = valorPreviewExpansion($preparacion, array("depurar", "producto_vivo_erp"), array());
  $sugerida = valorPreviewExpansion($preparacion, array("depurar", "publicacion_sugerida"), array());
  $bloqueosPublicacion = valorPreviewExpansion($preparacion, array("depurar", "bloqueos_publicacion"), array());
  if (!empty($bloqueosPublicacion)) {
    $bloqueos[] = "sku_" . $idSku . "_bloqueos_" . implode("_", $bloqueosPublicacion);
  }

  $simulados[] = array(
    "id_publicacion" => $idTemporal++,
    "id_producto_erp" => intval(valorPreviewExpansion($producto, array("id_producto_erp"), 0)),
    "id_sku" => $idSku,
    "slug" => valorPreviewExpansion($sugerida, array("slug"), ""),
    "sku" => valorPreviewExpansion($producto, array("sku"), ""),
    "nombre" => valorPreviewExpansion($sugerida, array("titulo_publico"), valorPreviewExpansion($producto, array("nombre"), "")),
    "marca" => valorPreviewExpansion($producto, array("marca"), null),
    "categoria" => valorPreviewExpansion($producto, array("categoria"), null),
    "presentacion" => presentacionPreviewExpansion($producto, $sugerida),
    "descripcion" => valorPreviewExpansion($sugerida, array("descripcion_publica"), ""),
    "imagen" => valorPreviewExpansion($producto, array("imagen"), valorPreviewExpansion($producto, array("url_imagen"), null)),
    "precio" => floatval(valorPreviewExpansion($producto, array("precio"), 0)),
    "moneda" => valorPreviewExpansion($producto, array("moneda"), "MXN"),
    "disponibilidad" => valorPreviewExpansion($producto, array("disponibilidad_publica_sugerida"), "consultar_disponibilidad"),
    "mascota_especie" => valorPreviewExpansion($sugerida, array("mascota_especie"), ""),
    "necesidades" => valorPreviewExpansion($sugerida, array("necesidades"), array()),
    "permite_cotizacion" => true,
    "permite_whatsapp" => true,
    "preview_no_publicado" => true
  );
}

$items = array_merge($publicados, $simulados);
$filtros = construirFiltrosPreviewExpansion($items);
$dryrunPreview = construirDryrunPreviewExpansion($items);
$respuesta = array(
  "ok" => empty($bloqueos),
  "modo" => "preview_expansion_readonly",
  "advertencia" => "Preview simulado con candidatos reales. No sustituye /ecommercePublico/catalogo hasta publicar borradores.",
  "base_api" => $base . "/ecommercePublico",
  "origin" => $origin,
  "estado" => array(
    "ready" => valorPreviewExpansion($estado, array("depurar", "ready"), false),
    "publicadas_actuales" => count($publicados),
    "publicaciones_preview_total" => count($items),
    "preview_incluye_no_publicados" => count($simulados)
  ),
  "configuracion_publica" => array(
    "whatsapp_configurado" => trim((string) valorPreviewExpansion($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "cors_origin_permitido" => $api->origenCorsPermitido($origin),
    "mostrar_stock_exacto" => false
  ),
  "catalogo_preview" => array(
    "items" => $items,
    "paginacion" => array(
      "pagina" => 1,
      "limite" => count($items),
      "total" => count($items)
    )
  ),
  "filtros_preview" => $filtros,
  "dryrun_preview_simulado" => $dryrunPreview,
  "validaciones_reales_post_publicacion" => array(
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=" . $base . " --origin=" . $origin . " --min_publicaciones=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=" . $base . " --origin=" . $origin . " --limite=6 --min_publicaciones=6"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_publica" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "no_expone_stock_exacto" => true,
    "no_toca_ecom_legacy" => true
  ),
  "bloqueos" => $bloqueos
);

if ($soloResumen) {
  $respuesta = array(
    "ok" => $respuesta["ok"],
    "modo" => $respuesta["modo"],
    "base_api" => $respuesta["base_api"],
    "origin" => $respuesta["origin"],
    "publicadas_actuales" => $respuesta["estado"]["publicadas_actuales"],
    "preview_incluye_no_publicados" => $respuesta["estado"]["preview_incluye_no_publicados"],
    "publicaciones_preview_total" => $respuesta["estado"]["publicaciones_preview_total"],
    "cors_origin_permitido" => $respuesta["configuracion_publica"]["cors_origin_permitido"],
    "whatsapp_configurado" => $respuesta["configuracion_publica"]["whatsapp_configurado"],
    "mascotas" => $respuesta["filtros_preview"]["mascotas"],
    "necesidades" => $respuesta["filtros_preview"]["necesidades"],
    "dryrun_lineas" => count($respuesta["dryrun_preview_simulado"]["lineas"]),
    "dryrun_total_estimado" => $respuesta["dryrun_preview_simulado"]["totales"]["total_estimado"],
    "bloqueos" => $respuesta["bloqueos"],
    "nota" => "Resumen read-only para validar que el frontend puede disenar con 6 tarjetas antes de publicar expansion."
  );
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function construirFiltrosPreviewExpansion($items) {
  $mascotas = array();
  $necesidades = array();
  $marcas = array();
  $categorias = array();
  foreach ($items as $item) {
    sumarFiltroPreviewExpansion($mascotas, valorPreviewExpansion($item, array("mascota_especie"), ""));
    foreach (valorPreviewExpansion($item, array("necesidades"), array()) as $necesidad) {
      sumarFiltroPreviewExpansion($necesidades, $necesidad);
    }
    sumarFiltroPreviewExpansion($marcas, valorPreviewExpansion($item, array("marca"), ""));
    sumarFiltroPreviewExpansion($categorias, valorPreviewExpansion($item, array("categoria"), ""));
  }
  return array(
    "mascotas" => filtrosListaPreviewExpansion($mascotas),
    "necesidades" => filtrosListaPreviewExpansion($necesidades),
    "marcas" => filtrosListaPreviewExpansion($marcas),
    "categorias" => filtrosListaPreviewExpansion($categorias)
  );
}

function construirDryrunPreviewExpansion($items) {
  $lineas = array();
  $total = 0;
  $renglon = 1;
  foreach ($items as $item) {
    if (!empty($item["permite_cotizacion"])) {
      $subtotal = floatval(valorPreviewExpansion($item, array("precio"), 0));
      $total += $subtotal;
      $lineas[] = array(
        "renglon" => $renglon++,
        "id_publicacion" => valorPreviewExpansion($item, array("id_publicacion"), 0),
        "id_sku" => valorPreviewExpansion($item, array("id_sku"), 0),
        "sku" => valorPreviewExpansion($item, array("sku"), ""),
        "nombre" => valorPreviewExpansion($item, array("nombre"), ""),
        "presentacion" => valorPreviewExpansion($item, array("presentacion"), ""),
        "precio_unitario" => $subtotal,
        "cantidad" => 1,
        "subtotal" => $subtotal,
        "disponibilidad" => valorPreviewExpansion($item, array("disponibilidad"), "")
      );
    }
  }
  return array(
    "advertencia" => "Simulado para UI. El dry-run real solo funcionara con publicaciones reales.",
    "lineas" => $lineas,
    "totales" => array(
      "subtotal_estimado" => $total,
      "total_estimado" => $total,
      "moneda" => "MXN",
      "texto" => "Total estimado sujeto a confirmacion"
    )
  );
}

function presentacionPreviewExpansion($producto, $sugerida) {
  $presentacion = trim((string) valorPreviewExpansion($sugerida, array("presentacion_publica"), ""));
  if ($presentacion !== "") {
    return $presentacion;
  }
  return "pza";
}

function sumarFiltroPreviewExpansion(&$resumen, $valor) {
  $valor = trim((string) $valor);
  if ($valor === "") {
    return;
  }
  if (!isset($resumen[$valor])) {
    $resumen[$valor] = 0;
  }
  $resumen[$valor]++;
}

function filtrosListaPreviewExpansion($resumen) {
  ksort($resumen);
  $salida = array();
  foreach ($resumen as $valor => $total) {
    $salida[] = array("valor" => $valor, "etiqueta" => $valor, "total" => $total);
  }
  return $salida;
}

function valorPreviewExpansion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
