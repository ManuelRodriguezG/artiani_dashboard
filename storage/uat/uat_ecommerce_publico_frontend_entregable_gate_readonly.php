<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar en una sola salida si el ERP esta listo para entregar al frontend ecommerce externo.
 * Impacto: resume API real, CORS, WhatsApp, catalogo, dry-run y preview de expansion sin escribir BD.
 * Contrato: read-only; no crea publicaciones, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::", "skus_preview::", "min_publicadas::", "min_preview::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$skusPreviewTexto = isset($opciones["skus_preview"]) ? trim((string) $opciones["skus_preview"]) : "415,866,386,1138";
$minPublicadas = isset($opciones["min_publicadas"]) ? max(1, intval($opciones["min_publicadas"])) : 2;
$minPreview = isset($opciones["min_preview"]) ? max($minPublicadas, intval($opciones["min_preview"])) : 6;

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
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$politicas = $api->politicasPublicas();
$taxonomia = $api->taxonomiaMascotasPublica();
$catalogo = $api->catalogoPublico(array("limite" => max($minPublicadas, 24)));
$items = valorEntregableFrontend($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
$preflight = array();

if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorEntregableFrontend($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
  ));
  $preflight = $api->cotizacionPreflight(array(
    "items" => array(array(
      "id_publicacion" => intval(valorEntregableFrontend($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    )),
    "contacto" => array("nombre" => "Cliente entregable", "telefono" => "3322068429"),
    "acepta_contacto_whatsapp" => true,
    "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp")
  ));
}

$previewListos = 0;
$previewBloqueos = array();
$previewCurado = array("aplicado" => false, "id_sku" => null, "senal" => null);
foreach ($skusPreview as $idSku) {
  $preparacion = $api->prepararPublicacion(array("id_sku" => $idSku));
  $bloqueosPublicacion = valorEntregableFrontend($preparacion, array("depurar", "bloqueos_publicacion"), array());
  $tituloPublico = valorEntregableFrontend($preparacion, array("depurar", "publicacion_sugerida", "titulo_publico"), "");
  if (textoSospechosoEntregableFrontend($tituloPublico)) {
    $bloqueosPublicacion[] = "validar_texto_publico";
  }
  $bloqueosReales = array_values(array_filter($bloqueosPublicacion, function($bloqueo) {
    return $bloqueo !== "publicacion_existente";
  }));
  if (empty($bloqueosPublicacion)) {
    $previewListos++;
  } elseif (!empty($bloqueosReales)) {
    $previewBloqueos[] = array(
      "id_sku" => $idSku,
      "bloqueos" => $bloqueosReales
    );
  }
}

$publicadas = intval(valorEntregableFrontend($estado, array("depurar", "publicaciones", "total_publicadas"), count($items)));
$curado1138 = validarSku1138CuradoEntregableFrontend($api, $skusPreview, $previewBloqueos);
if (!empty($curado1138["ok"])) {
  $previewListos++;
  $previewBloqueos = quitarSkuBloqueosEntregableFrontend($previewBloqueos, 1138);
  $previewCurado = array(
    "aplicado" => true,
    "id_sku" => 1138,
    "senal" => "verde_titulo_publico_curado",
    "titulo_publico" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm"
  );
}
$previewTotal = $publicadas + $previewListos;
$bloqueos = array();

if (valorEntregableFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueos[] = "ddl_pendiente";
}
if (!valorEntregableFrontend($estado, array("depurar", "ready"), false)) {
  $bloqueos[] = "api_no_ready";
}
if ($publicadas < $minPublicadas) {
  $bloqueos[] = "publicadas_menor_a_minimo_" . $minPublicadas;
}
if (trim((string) valorEntregableFrontend($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if (!$api->origenCorsPermitido($origin)) {
  $bloqueos[] = "cors_origin_no_permitido";
}
if (!empty($politicas["error"]) || !is_array(valorEntregableFrontend($politicas, array("depurar", "items"), null))) {
  $bloqueos[] = "politicas_no_ok";
}
if (!empty($taxonomia["error"]) || !is_array(valorEntregableFrontend($taxonomia, array("depurar", "mascotas"), null)) || !is_array(valorEntregableFrontend($taxonomia, array("depurar", "necesidades"), null))) {
  $bloqueos[] = "taxonomia_mascotas_no_ok";
}
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorEntregableFrontend($dryrun, array("depurar", "lineas"), array()))) {
  $bloqueos[] = "cotizacion_dryrun_no_ok";
}
if (empty($preflight) || !empty($preflight["error"]) || valorEntregableFrontend($preflight, array("depurar", "preflight"), false) !== true || valorEntregableFrontend($preflight, array("depurar", "listo_para_whatsapp"), false) !== true) {
  $bloqueos[] = "cotizacion_preflight_no_ok";
}
if ($previewTotal < $minPreview) {
  $bloqueos[] = "preview_menor_a_minimo_" . $minPreview;
}
if (!empty($previewBloqueos)) {
  $bloqueos[] = "preview_con_bloqueos";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_entregable_frontend" => empty($bloqueos) ? "verde_entregable_frontend" : "amarillo_revisar_bloqueos",
  "base_api" => $base . "/ecommercePublico",
  "origin_frontend" => $origin,
  "estado_actual" => array(
    "ready" => valorEntregableFrontend($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorEntregableFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => $publicadas,
    "min_publicadas" => $minPublicadas
  ),
  "integracion" => array(
    "cors_origin_permitido" => $api->origenCorsPermitido($origin),
    "whatsapp_configurado" => trim((string) valorEntregableFrontend($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "politicas_ok" => empty($politicas["error"]) && is_array(valorEntregableFrontend($politicas, array("depurar", "items"), null)),
    "taxonomia_mascotas_ok" => empty($taxonomia["error"]) && is_array(valorEntregableFrontend($taxonomia, array("depurar", "mascotas"), null)) && is_array(valorEntregableFrontend($taxonomia, array("depurar", "necesidades"), null)),
    "catalogo_tiene_items" => count($items) > 0,
    "cotizacion_dryrun_ok" => !empty($dryrun) && empty($dryrun["error"]),
    "cotizacion_preflight_ok" => !empty($preflight) && empty($preflight["error"]) && valorEntregableFrontend($preflight, array("depurar", "listo_para_whatsapp"), false) === true
  ),
  "preview_expansion" => array(
    "skus_revisados" => $skusPreview,
    "candidatos_listos" => $previewListos,
    "publicaciones_preview_total" => $previewTotal,
    "min_preview" => $minPreview,
    "preview_curado" => $previewCurado,
    "bloqueos" => $previewBloqueos
  ),
  "comandos_siguientes" => array(
    "snapshot_real_actual" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=" . $base . " --origin=" . $origin . " --limite=" . $minPublicadas,
    "preview_6_tarjetas_normal" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=" . $base . " --origin=" . $origin . " --skus=" . implode(",", $skusPreview) . " --resumen=1",
    "preview_6_tarjetas_curado" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_curada_6_readonly.php --base=" . $base . " --origin=" . $origin . " --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql",
    "paquete_frontend" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_package_readonly.php --base=" . $base
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  ),
  "bloqueos" => $bloqueos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorEntregableFrontend($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function textoSospechosoEntregableFrontend($texto) {
  $texto = (string) $texto;
  return strpos($texto, chr(239) . chr(191) . chr(189)) !== false
    || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $texto) === 1;
}

function validarSku1138CuradoEntregableFrontend($api, $skusPreview, $previewBloqueos) {
  if (!in_array(1138, $skusPreview, true) || !skuTieneBloqueoEntregableFrontend($previewBloqueos, 1138, "validar_texto_publico")) {
    return array("ok" => false);
  }
  $plan = $api->planGuardarPublicacion(array(
    "id_sku" => 1138,
    "titulo_publico" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm",
    "mascota_especie" => "ave",
    "necesidades" => "habitat",
    "estatus_publicacion" => "borrador"
  ));
  $bloqueos = valorEntregableFrontend($plan, array("depurar", "bloqueos_publicacion"), array());
  $titulo = valorEntregableFrontend($plan, array("depurar", "publicacion_normalizada", "titulo_publico"), "");
  if (textoSospechosoEntregableFrontend($titulo)) {
    $bloqueos[] = "validar_texto_publico";
  }
  return array("ok" => empty($plan["error"]) && empty($bloqueos), "bloqueos" => array_values(array_unique($bloqueos)));
}

function skuTieneBloqueoEntregableFrontend($previewBloqueos, $idSku, $bloqueoBuscado) {
  foreach ($previewBloqueos as $item) {
    if (intval(valorEntregableFrontend($item, array("id_sku"), 0)) === intval($idSku)
      && in_array($bloqueoBuscado, valorEntregableFrontend($item, array("bloqueos"), array()), true)) {
      return true;
    }
  }
  return false;
}

function quitarSkuBloqueosEntregableFrontend($previewBloqueos, $idSku) {
  $filtrados = array();
  foreach ($previewBloqueos as $item) {
    if (intval(valorEntregableFrontend($item, array("id_sku"), 0)) !== intval($idSku)) {
      $filtrados[] = $item;
    }
  }
  return $filtrados;
}
