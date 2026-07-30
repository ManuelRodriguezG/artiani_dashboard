<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-30.
 * Proposito: validar contratos internos del panel de publicaciones ecommerce sin escribir BD.
 * Impacto: cubre busqueda, filtro por estatus y guardas protegidas antes de operar desde UI.
 * Contrato: read-only; no crea, actualiza, publica ni pausa productos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();

$publicados = $api->auditarPublicabilidad(array(
  "limite" => 25,
  "estatus_publicacion" => "publicado"
));
$busqueda = $api->auditarPublicabilidad(array(
  "limite" => 25,
  "q" => "sunny"
));
$bloqueoCuraduria = $api->guardarCuraduriaPublicacionAutorizada(array(
  "id_sku" => 415,
  "titulo_publico" => "Prueba no autorizada"
), array("autorizar" => ""));
$bloqueoLoteBorrador = $api->guardarBorradoresLoteAutorizado(array(
  "id_skus" => "415,866"
), array("autorizar" => ""));
$bloqueoLotePublicar = $api->publicarBorradoresLoteAutorizado(array(
  "id_skus" => "415,866",
  "confirmar_revision" => 1
), array("autorizar" => ""));

$itemsPublicados = valorPanelPublicaciones($publicados, array("depurar", "candidatos"), array());
$itemsBusqueda = valorPanelPublicaciones($busqueda, array("depurar", "candidatos"), array());
$publicadosOk = empty($publicados["error"]) && count($itemsPublicados) >= 6;
$busquedaOk = empty($busqueda["error"]) && count($itemsBusqueda) > 0;
$bloqueoOk = !empty($bloqueoCuraduria["error"]) && valorPanelPublicaciones($bloqueoCuraduria, array("depurar", "bloqueado"), false) === true;
$loteBorradorBloqueadoOk = !empty($bloqueoLoteBorrador["error"]) && valorPanelPublicaciones($bloqueoLoteBorrador, array("depurar", "bloqueado"), false) === true;
$lotePublicarBloqueadoOk = !empty($bloqueoLotePublicar["error"]) && valorPanelPublicaciones($bloqueoLotePublicar, array("depurar", "bloqueado"), false) === true;

echo json_encode(array(
  "ok" => $publicadosOk && $busquedaOk && $bloqueoOk && $loteBorradorBloqueadoOk && $lotePublicarBloqueadoOk,
  "modo" => "read-only",
  "panel_publicaciones" => array(
    "filtro_publicados_ok" => $publicadosOk,
    "publicados_encontrados" => count($itemsPublicados),
    "busqueda_ok" => $busquedaOk,
    "busqueda_items" => count($itemsBusqueda),
    "curaduria_sin_token_bloqueada" => $bloqueoOk,
    "lote_borrador_sin_token_bloqueado" => $loteBorradorBloqueadoOk,
    "lote_publicar_sin_token_bloqueado" => $lotePublicarBloqueadoOk
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_publica" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorPanelPublicaciones($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
