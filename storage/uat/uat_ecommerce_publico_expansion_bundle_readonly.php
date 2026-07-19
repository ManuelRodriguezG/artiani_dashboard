<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: concentrar compuerta read-only de expansion ecommerce publico de 2 a 6 productos.
 * Impacto: permite ver en una sola salida estado actual, readiness de expansion y validacion futura.
 * Contrato: read-only; no crea borradores, no publica, no registra cotizaciones, no toca inventario ni legacy ecom_*.
 */

$opciones = getopt("", array("base::", "origin::", "respaldo::", "skus::", "min_actual::", "min_objetivo::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$respaldo = isset($opciones["respaldo"])
  ? trim((string) $opciones["respaldo"])
  : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql";
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "415,866,386,1138";
$minActual = isset($opciones["min_actual"]) ? max(1, intval($opciones["min_actual"])) : 2;
$minObjetivo = isset($opciones["min_objetivo"]) ? max($minActual, intval($opciones["min_objetivo"])) : 6;

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
$itemsCatalogo = valorExpansionBundle($catalogo, array("depurar", "items"), array());
$primerItem = !empty($itemsCatalogo) ? $itemsCatalogo[0] : array();
$dryrun = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorExpansionBundle($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    )),
    "contacto" => array("nombre" => "Expansion bundle read-only")
  ));
}

$validacionRespaldo = validarRespaldoExpansionBundle($respaldo);
$publicadas = intval(valorExpansionBundle($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$ddlOk = valorExpansionBundle($estado, array("depurar", "schema", "ddl_pendiente"), true) === false;
$whatsappOk = trim((string) valorExpansionBundle($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "";
$corsOk = $api->origenCorsPermitido($origin);
$dryrunOk = !empty($dryrun) && empty($dryrun["error"]) && !empty(valorExpansionBundle($dryrun, array("depurar", "lineas"), array()));

$itemsExpansion = array();
$bloqueosExpansion = array();
foreach ($skus as $idSku) {
  $preparacion = $api->prepararPublicacion(array("id_sku" => $idSku));
  $sugerida = valorExpansionBundle($preparacion, array("depurar", "publicacion_sugerida"), array());
  $producto = valorExpansionBundle($preparacion, array("depurar", "producto_vivo_erp"), array());
  $plan = $api->planGuardarPublicacion(array(
    "id_sku" => $idSku,
    "estatus_publicacion" => "borrador",
    "slug" => valorExpansionBundle($sugerida, array("slug"), ""),
    "titulo_publico" => valorExpansionBundle($sugerida, array("titulo_publico"), ""),
    "mascota_especie" => valorExpansionBundle($sugerida, array("mascota_especie"), ""),
    "necesidades" => implode(",", valorExpansionBundle($sugerida, array("necesidades"), array()))
  ));

  $bloqueosPlan = valorExpansionBundle($plan, array("depurar", "bloqueos"), array());
  $bloqueosPublicacion = valorExpansionBundle($plan, array("depurar", "bloqueos_publicacion"), array());
  $disponibilidad = (string) valorExpansionBundle($producto, array("disponibilidad_publica_sugerida"), "");
  $metadataOk = trim((string) valorExpansionBundle($sugerida, array("mascota_especie"), "")) !== ""
    && !empty(valorExpansionBundle($sugerida, array("necesidades"), array()));
  $disponibilidadOk = in_array($disponibilidad, array("disponible", "pocas_piezas"), true);
  $yaExiste = in_array("publicacion_existente", $bloqueosPublicacion, true);
  $listo = empty($bloqueosPlan) && empty($bloqueosPublicacion) && $metadataOk && $disponibilidadOk && !$yaExiste;
  if (!$listo) {
    $bloqueosExpansion[] = "sku_" . $idSku . "_no_listo";
  }

  $itemsExpansion[] = array(
    "id_sku" => $idSku,
    "sku" => valorExpansionBundle($producto, array("sku"), ""),
    "nombre" => valorExpansionBundle($producto, array("nombre"), ""),
    "precio" => valorExpansionBundle($producto, array("precio"), null),
    "disponibilidad" => $disponibilidad,
    "mascota" => valorExpansionBundle($sugerida, array("mascota_especie"), ""),
    "necesidades" => valorExpansionBundle($sugerida, array("necesidades"), array()),
    "listo_para_borrador" => $listo,
    "sha256_sql_borrador" => valorExpansionBundle($plan, array("depurar", "sha256_sql"), "")
  );
}

$listos = contarListosExpansionBundle($itemsExpansion);
$bloqueosActual = array();
if (!$ddlOk) { $bloqueosActual[] = "ddl_pendiente"; }
if (!$whatsappOk) { $bloqueosActual[] = "whatsapp_no_configurado"; }
if (!$corsOk) { $bloqueosActual[] = "cors_no_permite_origin"; }
if ($publicadas < $minActual) { $bloqueosActual[] = "publicaciones_menor_a_minimo_actual_" . $minActual; }
if (!$dryrunOk) { $bloqueosActual[] = "dryrun_no_valido"; }

$bloqueosObjetivo = array();
if ($publicadas + $listos < $minObjetivo) {
  $bloqueosObjetivo[] = "expansion_no_alcanza_minimo_objetivo_" . $minObjetivo;
}
if (!$validacionRespaldo["ok"]) {
  $bloqueosObjetivo[] = "respaldo_no_valido";
}
if (!empty($bloqueosExpansion)) {
  $bloqueosObjetivo = array_merge($bloqueosObjetivo, $bloqueosExpansion);
}

echo json_encode(array(
  "ok" => empty($bloqueosActual) && empty($bloqueosObjetivo),
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "origin" => $origin,
  "senal_actual" => empty($bloqueosActual) ? "verde_datos_reales" : "revisar_actual",
  "senal_expansion" => empty($bloqueosObjetivo) ? "lista_para_autorizacion" : "revisar_expansion",
  "actual" => array(
    "publicadas" => $publicadas,
    "min_actual" => $minActual,
    "ddl_ok" => $ddlOk,
    "whatsapp_ok" => $whatsappOk,
    "cors_ok" => $corsOk,
    "dryrun_ok" => $dryrunOk,
    "bloqueos" => $bloqueosActual
  ),
  "expansion" => array(
    "skus" => $skus,
    "listos_para_borrador" => $listos,
    "min_objetivo" => $minObjetivo,
    "publicaciones_estimadas_post_expansion" => $publicadas + $listos,
    "respaldo" => $validacionRespaldo,
    "items" => $itemsExpansion,
    "bloqueos" => array_values(array_unique($bloqueosObjetivo))
  ),
  "comandos" => array(
    "checklist_apply" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=" . $base . " --respaldo=" . argumentoExpansionBundle($respaldo) . " --skus=" . implode(",", $skus),
    "post_apply_min_actual" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=" . $base . " --origin=" . $origin . " --min_publicaciones=" . $minActual,
    "post_apply_min_objetivo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=" . $base . " --origin=" . $origin . " --min_publicaciones=" . $minObjetivo,
    "snapshot_min_objetivo" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=" . $base . " --origin=" . $origin . " --limite=" . min(10, $minObjetivo) . " --min_publicaciones=" . $minObjetivo
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_borradores" => true,
    "no_publica" => true,
    "no_registra_cotizaciones" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function contarListosExpansionBundle($items) {
  $total = 0;
  foreach ($items as $item) {
    if (!empty($item["listo_para_borrador"])) {
      $total++;
    }
  }
  return $total;
}

function validarRespaldoExpansionBundle($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = strtoupper($respaldo) === "" || strpos(strtoupper($respaldo), "RUTA_O_REFERENCIA") !== false || strpos(strtoupper($respaldo), "PLACEHOLDER") !== false;
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function argumentoExpansionBundle($valor) {
  return "\"" . str_replace("\"", "\\\"", (string) $valor) . "\"";
}

function valorExpansionBundle($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
