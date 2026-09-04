<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: validar guardrails de persistencia SEO/migracion sin usar tokens reales.
 * Impacto: comprueba que importacion y redirecciones quedan bloqueadas sin autorizacion operativa.
 * Contrato: read-only; no ejecuta DDL, no importa URLs y no crea redirecciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();

$planImportacion = $catalogo->seoUrlsViejasImportarPlanInterno(array(
  "urls" => array("/producto-viejo.html", "https://artiani.com.mx/categoria/perros")
));
$planRedireccionValida = $catalogo->seoRedireccionPlanInterno(array(
  "from" => "/producto-viejo.html",
  "to" => "/producto/producto-nuevo",
  "status" => 301,
  "tipo" => "producto"
));
$planRedireccionInterna = $catalogo->seoRedireccionPlanInterno(array(
  "from" => "/producto-viejo.html",
  "to" => "/ecommercePublico/producto/producto-nuevo",
  "status" => 301,
  "tipo" => "producto"
));
$planSyncCanonicas = $catalogo->seoUrlsCanonicasSincronizarPlanInterno(array("limite" => 20));
$guardarSyncSinToken = $catalogo->seoUrlsCanonicasSincronizarAutorizado(array("limite" => 20, "autorizar" => ""));
$guardarSyncTokenInvalido = $catalogo->seoUrlsCanonicasSincronizarAutorizado(array("limite" => 20, "autorizar" => "TOKEN_INVALIDO_READONLY"));
$guardarImportacionSinToken = $catalogo->seoUrlsViejasImportarAutorizado(array(
  "urls" => array("/producto-viejo.html")
), array("autorizar" => ""));
$guardarImportacionTokenInvalido = $catalogo->seoUrlsViejasImportarAutorizado(array(
  "urls" => array("/producto-viejo.html")
), array("autorizar" => "TOKEN_INVALIDO_READONLY"));
$guardarRedireccionSinToken = $catalogo->seoRedireccionGuardarAutorizada(array(
  "from" => "/producto-viejo.html",
  "to" => "/producto/producto-nuevo"
), array("autorizar" => ""));
$guardarRedireccionTokenInvalido = $catalogo->seoRedireccionGuardarAutorizada(array(
  "from" => "/producto-viejo.html",
  "to" => "/producto/producto-nuevo"
), array("autorizar" => "TOKEN_INVALIDO_READONLY"));

$checks = array(
  "plan_importacion_readonly" => empty($planImportacion["error"]) && valorSeoPersistencia($planImportacion, array("depurar", "read_only"), false) === true,
  "plan_redireccion_valida_readonly" => empty($planRedireccionValida["error"]) && valorSeoPersistencia($planRedireccionValida, array("depurar", "valida"), false) === true,
  "plan_redireccion_bloquea_api" => in_array("no_usar_rutas_api", valorSeoPersistencia($planRedireccionInterna, array("depurar", "bloqueos"), array()), true),
  "plan_sync_canonicas_readonly" => empty($planSyncCanonicas["error"]) && valorSeoPersistencia($planSyncCanonicas, array("depurar", "read_only"), false) === true,
  "sync_canonicas_sin_token_bloqueada" => !empty($guardarSyncSinToken["error"]) && valorSeoPersistencia($guardarSyncSinToken, array("depurar", "no_escribe_bd"), false) === true,
  "sync_canonicas_token_invalido_bloqueada" => !empty($guardarSyncTokenInvalido["error"]) && valorSeoPersistencia($guardarSyncTokenInvalido, array("depurar", "no_escribe_bd"), false) === true,
  "importacion_sin_token_bloqueada" => !empty($guardarImportacionSinToken["error"]) && valorSeoPersistencia($guardarImportacionSinToken, array("depurar", "no_escribe_bd"), false) === true,
  "importacion_token_invalido_bloqueada" => !empty($guardarImportacionTokenInvalido["error"]) && valorSeoPersistencia($guardarImportacionTokenInvalido, array("depurar", "no_escribe_bd"), false) === true,
  "redireccion_sin_token_bloqueada" => !empty($guardarRedireccionSinToken["error"]) && valorSeoPersistencia($guardarRedireccionSinToken, array("depurar", "no_escribe_bd"), false) === true,
  "redireccion_token_invalido_bloqueada" => !empty($guardarRedireccionTokenInvalido["error"]) && valorSeoPersistencia($guardarRedireccionTokenInvalido, array("depurar", "no_escribe_bd"), false) === true
);

$bloqueos = array();
foreach ($checks as $check => $ok) {
  if (!$ok) {
    $bloqueos[] = $check;
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "checks" => $checks,
  "bloqueos" => $bloqueos,
  "tokens_requeridos" => array(
    "sync_canonicas" => "ECOMMERCE_SEO_SYNC_URLS_CANONICAS",
    "importacion" => "ECOMMERCE_SEO_IMPORTAR_URLS_VIEJAS",
    "redireccion" => "ECOMMERCE_SEO_GUARDAR_REDIRECCION"
  ),
  "guardrails" => array(
    "no_usa_tokens_reales" => true,
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true
  ),
  "muestras" => array(
    "sync_canonicas_bloqueada" => array(
      "mensaje" => valorSeoPersistencia($guardarSyncSinToken, array("mensaje"), ""),
      "token_requerido" => valorSeoPersistencia($guardarSyncSinToken, array("depurar", "token_requerido"), "")
    ),
    "importacion_bloqueada" => array(
      "mensaje" => valorSeoPersistencia($guardarImportacionSinToken, array("mensaje"), ""),
      "token_requerido" => valorSeoPersistencia($guardarImportacionSinToken, array("depurar", "token_requerido"), "")
    ),
    "redireccion_bloqueada" => array(
      "mensaje" => valorSeoPersistencia($guardarRedireccionSinToken, array("mensaje"), ""),
      "token_requerido" => valorSeoPersistencia($guardarRedireccionSinToken, array("depurar", "token_requerido"), "")
    )
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorSeoPersistencia($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
