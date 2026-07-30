<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-27
 * Proposito: validar si la base Fase 1 del ecommerce publico esta suficientemente cimentada para avanzar frontend basico.
 * Impacto: separa readiness local/funcional de la salida productiva; evita publicar antes de tiempo.
 * Contrato: read-only; no escribe BD, no publica SKUs, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::", "min_publicadas::", "min_preview::", "skus_preview::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? rtrim(trim((string) $opciones["origin"]), "/") : "http://artiani.com.local";
$minPublicadas = isset($opciones["min_publicadas"]) ? max(1, intval($opciones["min_publicadas"])) : 2;
$minPreview = isset($opciones["min_preview"]) ? max($minPublicadas, intval($opciones["min_preview"])) : 6;
$skusPreviewTexto = isset($opciones["skus_preview"]) ? trim((string) $opciones["skus_preview"]) : "415,866,386,1138";

$skusPreview = array();
foreach (explode(",", $skusPreviewTexto) as $sku) {
  $idSku = intval(trim($sku));
  if ($idSku > 0) {
    $skusPreview[] = $idSku;
  }
}
$skusPreview = array_values(array_unique($skusPreview));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$contratos = $api->contratosApiPublicos();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$seo = $api->seoPublico();
$filtros = $api->filtrosPublicos();
$politicas = $api->politicasPublicas();
$taxonomia = $api->taxonomiaMascotasPublica();
$catalogo = $api->catalogoPublico(array("limite" => max(24, $minPublicadas)));
$items = valorBaseCimentada($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = !empty($primerItem) ? $api->cotizacionDryRun(array(
  "items" => array(array(
    "id_publicacion" => intval(valorBaseCimentada($primerItem, array("id_publicacion"), 0)),
    "cantidad" => 1
  )),
  "contacto" => array("nombre" => "Validacion base", "telefono" => "", "mensaje" => "")
)) : array();
$preflight = !empty($primerItem) ? $api->cotizacionPreflight(array(
  "items" => array(array(
    "id_publicacion" => intval(valorBaseCimentada($primerItem, array("id_publicacion"), 0)),
    "cantidad" => 1
  )),
  "contacto" => array("nombre" => "Validacion base", "telefono" => "3322068429", "mensaje" => ""),
  "acepta_contacto_whatsapp" => true,
  "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp")
)) : array();
$registroBloqueado = $api->cotizacionRegistrarBloqueada(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1))));

$previewListos = 0;
$previewBloqueos = array();
$previewCurado = array("aplicado" => false, "id_sku" => null, "senal" => null);
foreach ($skusPreview as $idSku) {
  $preparacion = $api->prepararPublicacion(array("id_sku" => $idSku));
  $bloqueosPublicacion = valorBaseCimentada($preparacion, array("depurar", "bloqueos_publicacion"), array());
  $tituloPublico = valorBaseCimentada($preparacion, array("depurar", "publicacion_sugerida", "titulo_publico"), "");
  if (textoSospechosoBaseCimentada($tituloPublico)) {
    $bloqueosPublicacion[] = "validar_texto_publico";
  }
  if (empty($bloqueosPublicacion)) {
    $previewListos++;
  } else {
    $previewBloqueos[] = array("id_sku" => $idSku, "bloqueos" => $bloqueosPublicacion);
  }
}

$endpoints = valorBaseCimentada($contratos, array("depurar", "endpoints_publicos"), array());
$publicadas = intval(valorBaseCimentada($estado, array("depurar", "publicaciones", "total_publicadas"), count($items)));
$curado1138 = validarSku1138CuradoBaseCimentada($api, $skusPreview, $previewBloqueos);
if (!empty($curado1138["ok"])) {
  $previewListos++;
  $previewBloqueos = quitarSkuBloqueosBaseCimentada($previewBloqueos, 1138);
  $previewCurado = array(
    "aplicado" => true,
    "id_sku" => 1138,
    "senal" => "verde_titulo_publico_curado",
    "titulo_publico" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm"
  );
}
$previewTotal = $publicadas + $previewListos;
$config = valorBaseCimentada($configuracion, array("depurar", "configuracion"), array());
$bloqueos = array();

if (count($endpoints) < 16) { $bloqueos[] = "contratos_menos_de_16_endpoints"; }
if (!valorBaseCimentada($estado, array("depurar", "ready"), false)) { $bloqueos[] = "api_no_ready"; }
if (valorBaseCimentada($estado, array("depurar", "schema", "ddl_pendiente"), true)) { $bloqueos[] = "ddl_pendiente"; }
if ($publicadas < $minPublicadas) { $bloqueos[] = "publicadas_menor_a_minimo_" . $minPublicadas; }
if (!$api->origenCorsPermitido($origin)) { $bloqueos[] = "cors_local_no_permitido"; }
if (trim((string) valorBaseCimentada($config, array("whatsapp_numero_principal"), "")) === "") { $bloqueos[] = "whatsapp_no_configurado"; }
if ((string) valorBaseCimentada($config, array("mostrar_stock_exacto"), "1") !== "0") { $bloqueos[] = "stock_exacto_visible"; }
if (!is_array(valorBaseCimentada($seo, array("depurar", "meta"), null))) { $bloqueos[] = "seo_meta_no_ok"; }
if (!is_array(valorBaseCimentada($filtros, array("depurar", "mascotas"), null))) { $bloqueos[] = "filtros_mascotas_no_ok"; }
if (!is_array(valorBaseCimentada($politicas, array("depurar", "items"), null))) { $bloqueos[] = "politicas_no_ok"; }
if (!is_array(valorBaseCimentada($taxonomia, array("depurar", "mascotas"), null)) || !is_array(valorBaseCimentada($taxonomia, array("depurar", "necesidades"), null))) { $bloqueos[] = "taxonomia_no_ok"; }
if (empty($items)) { $bloqueos[] = "catalogo_sin_items"; }
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorBaseCimentada($dryrun, array("depurar", "lineas"), array()))) { $bloqueos[] = "cotizacion_dryrun_no_ok"; }
if (empty($preflight) || !empty($preflight["error"]) || valorBaseCimentada($preflight, array("depurar", "preflight"), false) !== true || valorBaseCimentada($preflight, array("depurar", "listo_para_whatsapp"), false) !== true) { $bloqueos[] = "cotizacion_preflight_no_ok"; }
if (valorBaseCimentada($registroBloqueado, array("depurar", "bloqueado"), false) !== true) { $bloqueos[] = "cotizacion_registrar_no_bloqueado"; }
if ($previewTotal < $minPreview) { $bloqueos[] = "preview_menor_a_minimo_" . $minPreview; }
if (!empty($previewBloqueos)) { $bloqueos[] = "preview_con_bloqueos"; }

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_base_ecommerce" => empty($bloqueos) ? "verde_base_cimentada_frontend_basico" : "amarillo_base_con_pendientes",
  "base_api" => $base . "/ecommercePublico",
  "origin_local" => $origin,
  "checks" => array(
    "endpoints_total" => count($endpoints),
    "api_ready" => valorBaseCimentada($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorBaseCimentada($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => $publicadas,
    "min_publicadas" => $minPublicadas,
    "cors_local_permitido" => $api->origenCorsPermitido($origin),
    "whatsapp_configurado" => trim((string) valorBaseCimentada($config, array("whatsapp_numero_principal"), "")) !== "",
    "seo_ok" => is_array(valorBaseCimentada($seo, array("depurar", "meta"), null)),
    "filtros_ok" => is_array(valorBaseCimentada($filtros, array("depurar", "mascotas"), null)),
    "politicas_ok" => is_array(valorBaseCimentada($politicas, array("depurar", "items"), null)),
    "taxonomia_ok" => is_array(valorBaseCimentada($taxonomia, array("depurar", "mascotas"), null)),
    "catalogo_items" => count($items),
    "cotizacion_dryrun_ok" => !empty($dryrun) && empty($dryrun["error"]),
    "cotizacion_preflight_ok" => !empty($preflight) && empty($preflight["error"]) && valorBaseCimentada($preflight, array("depurar", "listo_para_whatsapp"), false) === true,
    "cotizacion_registrar_bloqueado" => valorBaseCimentada($registroBloqueado, array("depurar", "bloqueado"), false) === true,
    "preview_total" => $previewTotal,
    "min_preview" => $minPreview,
    "preview_curado" => $previewCurado
  ),
  "frontend_puede_trabajar" => array(
    "catalogo_real_basico" => empty($bloqueos),
    "grid_6_tarjetas_con_preview" => $previewTotal >= $minPreview && empty($previewBloqueos),
    "politicas_facturacion_ui" => is_array(valorBaseCimentada($politicas, array("depurar", "items"), null)),
    "navegacion_mascota_necesidad" => is_array(valorBaseCimentada($taxonomia, array("depurar", "mascotas"), null)),
    "carrito_whatsapp_dryrun" => !empty($dryrun) && empty($dryrun["error"]),
    "carrito_whatsapp_preflight" => !empty($preflight) && empty($preflight["error"]) && valorBaseCimentada($preflight, array("depurar", "listo_para_whatsapp"), false) === true
  ),
  "no_es_productivo" => true,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_publica_skus" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "produccion_se_evalua_en_frontend_productivo_gate" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorBaseCimentada($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function textoSospechosoBaseCimentada($texto) {
  $texto = (string) $texto;
  return strpos($texto, chr(239) . chr(191) . chr(189)) !== false
    || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $texto) === 1;
}

function validarSku1138CuradoBaseCimentada($api, $skusPreview, $previewBloqueos) {
  if (!in_array(1138, $skusPreview, true) || !skuTieneBloqueoBaseCimentada($previewBloqueos, 1138, "validar_texto_publico")) {
    return array("ok" => false);
  }
  $plan = $api->planGuardarPublicacion(array(
    "id_sku" => 1138,
    "titulo_publico" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm",
    "mascota_especie" => "ave",
    "necesidades" => "habitat",
    "estatus_publicacion" => "borrador"
  ));
  $bloqueos = valorBaseCimentada($plan, array("depurar", "bloqueos_publicacion"), array());
  $titulo = valorBaseCimentada($plan, array("depurar", "publicacion_normalizada", "titulo_publico"), "");
  if (textoSospechosoBaseCimentada($titulo)) {
    $bloqueos[] = "validar_texto_publico";
  }
  return array("ok" => empty($plan["error"]) && empty($bloqueos), "bloqueos" => array_values(array_unique($bloqueos)));
}

function skuTieneBloqueoBaseCimentada($previewBloqueos, $idSku, $bloqueoBuscado) {
  foreach ($previewBloqueos as $item) {
    if (intval(valorBaseCimentada($item, array("id_sku"), 0)) === intval($idSku)
      && in_array($bloqueoBuscado, valorBaseCimentada($item, array("bloqueos"), array()), true)) {
      return true;
    }
  }
  return false;
}

function quitarSkuBloqueosBaseCimentada($previewBloqueos, $idSku) {
  $filtrados = array();
  foreach ($previewBloqueos as $item) {
    if (intval(valorBaseCimentada($item, array("id_sku"), 0)) !== intval($idSku)) {
      $filtrados[] = $item;
    }
  }
  return $filtrados;
}
