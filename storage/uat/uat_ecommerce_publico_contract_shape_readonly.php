<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: validar llaves minimas de contratos ecommerce publico para frontend externo.
 * Impacto: detecta cambios de shape antes de romper catalogo, filtros, ficha o carrito.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no registra cotizaciones y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();

$respuestas = array(
  "contratos" => $modelo->contratosApiPublicos(),
  "estado" => $modelo->estadoApiPublica(),
  "configuracion" => $modelo->configuracionPublica(),
  "seo" => $modelo->seoPublico(),
  "filtros" => $modelo->filtrosPublicos(),
  "catalogo" => $modelo->catalogoPublico(array("limite" => 3)),
  "producto" => $modelo->productoPublico("slug-de-prueba-no-publicado"),
  "disponibilidad" => $modelo->disponibilidadPublica(array("slug" => "slug-de-prueba-no-publicado")),
  "cotizacion_dryrun" => $modelo->cotizacionDryRun(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1)))),
  "cotizacion_registrar" => $modelo->cotizacionRegistrarBloqueada(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1))))
);

$bloqueos = array();
foreach ($respuestas as $nombre => $respuesta) {
  validarWrapper($nombre, $respuesta, $bloqueos);
}

validarRutas($respuestas["contratos"], $bloqueos);
validarEstado($respuestas["estado"], $bloqueos);
validarConfiguracion($respuestas["configuracion"], $bloqueos);
validarSeo($respuestas["seo"], $bloqueos);
validarFiltros($respuestas["filtros"], $bloqueos);
validarCatalogo($respuestas["catalogo"], $bloqueos);
validarProducto($respuestas["producto"], $bloqueos);
validarDisponibilidad($respuestas["disponibilidad"], $bloqueos);
validarDryRun($respuestas["cotizacion_dryrun"], $bloqueos);
validarRegistroBloqueado($respuestas["cotizacion_registrar"], $bloqueos);

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "shape" => array(
    "wrappers_validados" => array_keys($respuestas),
    "endpoints_publicos_esperados" => 9,
    "item_catalogo_keys" => itemCatalogoKeys()
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_registra_cotizaciones" => true,
    "no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarWrapper($nombre, $respuesta, &$bloqueos) {
  foreach (array("error", "tipo", "mensaje", "api", "depurar") as $key) {
    if (!is_array($respuesta) || !array_key_exists($key, $respuesta)) {
      $bloqueos[] = $nombre . "_falta_wrapper_" . $key;
    }
  }
  if (valorShape($respuesta, array("api", "version"), "") !== "fase1-2026-07-12") {
    $bloqueos[] = $nombre . "_api_version_invalida";
  }
  if (valorShape($respuesta, array("api", "fuente_verdad"), "") !== "ERP") {
    $bloqueos[] = $nombre . "_fuente_verdad_invalida";
  }
}

function validarRutas($respuesta, &$bloqueos) {
  $endpoints = valorShape($respuesta, array("depurar", "endpoints_publicos"), array());
  $rutas = array();
  foreach ($endpoints as $endpoint) {
    $rutas[] = isset($endpoint["ruta"]) ? $endpoint["ruta"] : "";
  }
  foreach (array(
    "/ecommercePublico/estado",
    "/ecommercePublico/catalogo",
    "/ecommercePublico/producto/{slug}",
    "/ecommercePublico/filtros",
    "/ecommercePublico/configuracion",
    "/ecommercePublico/seo",
    "/ecommercePublico/disponibilidad",
    "/ecommercePublico/cotizacion_dryrun",
    "/ecommercePublico/cotizacion_registrar"
  ) as $ruta) {
    if (!in_array($ruta, $rutas, true)) {
      $bloqueos[] = "contratos_falta_ruta_" . $ruta;
    }
  }
}

function validarEstado($respuesta, &$bloqueos) {
  foreach (array(
    array("depurar", "ready"),
    array("depurar", "schema", "ddl_pendiente"),
    array("depurar", "publicaciones", "total_publicadas"),
    array("depurar", "publicaciones", "catalogo_publico_vacio"),
    array("depurar", "seguridad", "post_dryrun_disponible"),
    array("depurar", "guardrails", "no_checkout")
  ) as $ruta) {
    if (valorShape($respuesta, $ruta, "__missing__") === "__missing__") {
      $bloqueos[] = "estado_falta_" . implode(".", $ruta);
    }
  }
}

function validarConfiguracion($respuesta, &$bloqueos) {
  $config = valorShape($respuesta, array("depurar", "configuracion"), array());
  foreach (array("moneda_default", "whatsapp_numero_principal", "whatsapp_mensaje_base", "cotizacion_habilitada", "mostrar_stock_exacto", "modo_sin_stock", "texto_total_estimado", "url_sitio_publico") as $key) {
    if (!array_key_exists($key, $config)) {
      $bloqueos[] = "configuracion_falta_" . $key;
    }
  }
  if (isset($config["mostrar_stock_exacto"]) && (string) $config["mostrar_stock_exacto"] !== "0") {
    $bloqueos[] = "configuracion_mostrar_stock_exacto_debe_ser_0";
  }
}

function validarSeo($respuesta, &$bloqueos) {
  foreach (array("meta", "robots", "sitemap", "json_ld") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "seo_falta_array_" . $key;
    }
  }
  if (valorShape($respuesta, array("depurar", "guardrails", "no_muestra_stock_exacto"), false) !== true) {
    $bloqueos[] = "seo_debe_indicar_no_stock_exacto";
  }
}

function validarFiltros($respuesta, &$bloqueos) {
  foreach (array("mascotas", "necesidades", "marcas", "categorias") as $key) {
    if (!is_array(valorShape($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = "filtros_falta_array_" . $key;
    }
  }
}

function validarCatalogo($respuesta, &$bloqueos) {
  if (!is_array(valorShape($respuesta, array("depurar", "items"), null))) {
    $bloqueos[] = "catalogo_items_debe_ser_array";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "paginacion"), null))) {
    $bloqueos[] = "catalogo_paginacion_debe_ser_array";
  }
}

function validarProducto($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "item"), "__missing__") === "__missing__") {
    $bloqueos[] = "producto_falta_item";
  }
}

function validarDisponibilidad($respuesta, &$bloqueos) {
  $estado = valorShape($respuesta, array("depurar", "disponibilidad"), "");
  if (!in_array($estado, array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado"), true)) {
    $bloqueos[] = "disponibilidad_estado_invalido";
  }
  if (valorShape($respuesta, array("depurar", "mostrar_cantidad_exacta"), false) === true) {
    $bloqueos[] = "disponibilidad_no_debe_mostrar_cantidad_exacta";
  }
}

function validarDryRun($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "dry_run"), false) !== true) {
    $bloqueos[] = "dryrun_falta_flag_dry_run";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = "dryrun_debe_indicar_no_escribe_bd";
  }
  if (!is_array(valorShape($respuesta, array("depurar", "lineas"), null))) {
    $bloqueos[] = "dryrun_lineas_debe_ser_array";
  }
}

function validarRegistroBloqueado($respuesta, &$bloqueos) {
  if (valorShape($respuesta, array("depurar", "bloqueado"), false) !== true) {
    $bloqueos[] = "cotizacion_registrar_debe_seguir_bloqueado";
  }
  if (valorShape($respuesta, array("depurar", "no_escribe_bd"), false) !== true) {
    $bloqueos[] = "cotizacion_registrar_debe_indicar_no_escribe_bd";
  }
}

function itemCatalogoKeys() {
  return array("id_publicacion", "id_producto_erp", "id_sku", "slug", "sku", "nombre", "marca", "categoria", "presentacion", "descripcion", "imagen", "precio", "moneda", "disponibilidad", "mascota_especie", "necesidades", "permite_cotizacion", "permite_whatsapp");
}

function valorShape($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
