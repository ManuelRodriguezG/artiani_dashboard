<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: validar checklist read-only antes de aplicar expansion controlada del catalogo ecommerce.
 * Impacto: evita ejecutar borradores/publicaciones sin respaldo, planes limpios y orden de aplicacion.
 * Contrato: read-only; no escribe BD, no publica, no toca inventario y no usa legacy ecom_*.
 */

$opciones = getopt("", array("base::", "respaldo::", "skus::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$respaldo = isset($opciones["respaldo"])
  ? trim((string) $opciones["respaldo"])
  : "C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql";
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "415,866,386,1138";

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

$modelo = new EcommerceCatalogoPublico();
$validacionRespaldo = validarRespaldoExpansionChecklist($respaldo);
$estado = $modelo->estadoApiPublica();
$catalogo = $modelo->catalogoPublico(array("limite" => 60));
$publicadosActuales = valorExpansionChecklist($catalogo, array("depurar", "items"), array());

$items = array();
$bloqueos = array();
foreach ($skus as $idSku) {
  $preparacion = $modelo->prepararPublicacion(array("id_sku" => $idSku));
  $sugerida = valorExpansionChecklist($preparacion, array("depurar", "publicacion_sugerida"), array());
  $producto = valorExpansionChecklist($preparacion, array("depurar", "producto_vivo_erp"), array());
  $bloqueosPublicacion = valorExpansionChecklist($preparacion, array("depurar", "bloqueos_publicacion"), array());
  $yaPublicado = in_array("publicacion_existente", $bloqueosPublicacion, true);

  $plan = $modelo->planGuardarPublicacion(array(
    "id_sku" => $idSku,
    "estatus_publicacion" => "borrador",
    "slug" => valorExpansionChecklist($sugerida, array("slug"), ""),
    "titulo_publico" => valorExpansionChecklist($sugerida, array("titulo_publico"), ""),
    "mascota_especie" => valorExpansionChecklist($sugerida, array("mascota_especie"), ""),
    "necesidades" => implode(",", valorExpansionChecklist($sugerida, array("necesidades"), array()))
  ));

  $bloqueosPlan = valorExpansionChecklist($plan, array("depurar", "bloqueos"), array());
  $bloqueosPlanPublicacion = valorExpansionChecklist($plan, array("depurar", "bloqueos_publicacion"), array());
  $metadataOk = trim((string) valorExpansionChecklist($sugerida, array("mascota_especie"), "")) !== ""
    && !empty(valorExpansionChecklist($sugerida, array("necesidades"), array()));
  $disponibilidad = (string) valorExpansionChecklist($producto, array("disponibilidad_publica_sugerida"), "");
  $disponibilidadOk = in_array($disponibilidad, array("disponible", "pocas_piezas"), true);
  $listo = empty($bloqueosPlan) && empty($bloqueosPlanPublicacion) && $metadataOk && $disponibilidadOk && !$yaPublicado;

  if (!$listo) {
    $bloqueos[] = "sku_" . $idSku . "_no_listo_para_borrador";
  }

  $items[] = array(
    "id_sku" => $idSku,
    "sku" => valorExpansionChecklist($producto, array("sku"), ""),
    "nombre" => valorExpansionChecklist($producto, array("nombre"), ""),
    "precio" => valorExpansionChecklist($producto, array("precio"), null),
    "disponibilidad_publica_sugerida" => $disponibilidad,
    "mascota_especie" => valorExpansionChecklist($sugerida, array("mascota_especie"), ""),
    "necesidades" => valorExpansionChecklist($sugerida, array("necesidades"), array()),
    "ya_publicado_o_borrador" => $yaPublicado,
    "plan_borrador_ok" => empty($plan["error"]),
    "bloqueos_plan" => $bloqueosPlan,
    "bloqueos_publicacion" => $bloqueosPlanPublicacion,
    "sha256_sql_borrador" => valorExpansionChecklist($plan, array("depurar", "sha256_sql"), ""),
    "comando_borrador_apply" => comandoBorradorExpansionChecklist($idSku, $sugerida, $respaldo),
    "comando_publicar_plan_post_borrador" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_plan_readonly.php --id_sku=" . $idSku . " --confirmar_revision=1",
    "comando_publicar_apply_post_borrador" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=" . argumentoExpansionChecklist($respaldo) . " --id_sku=" . $idSku . " --confirmar_revision=1"
  );
}

if (!$validacionRespaldo["ok"]) {
  $bloqueos[] = "respaldo_externo_no_valido";
}
if (valorExpansionChecklist($estado, array("depurar", "ready"), false) !== true) {
  $bloqueos[] = "api_no_ready";
}
if (valorExpansionChecklist($estado, array("depurar", "schema", "ddl_pendiente"), true) !== false) {
  $bloqueos[] = "ddl_pendiente";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "respaldo" => $validacionRespaldo,
  "estado_actual" => array(
    "ready" => valorExpansionChecklist($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorExpansionChecklist($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas_actuales" => count($publicadosActuales)
  ),
  "expansion" => array(
    "skus_objetivo" => $skus,
    "total_skus" => count($skus),
    "listos_para_borrador" => contarListosExpansionChecklist($items),
    "publicaciones_esperadas_si_se_publican_todos" => count($publicadosActuales) + contarListosExpansionChecklist($items)
  ),
  "items" => $items,
  "orden_apply_si_autorizado" => array(
    "paso_1_guardar_borradores_con_token_ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR",
    "paso_2_revisar_borradores_en_consola_interna",
    "paso_3_publicar_con_token_ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR_y_confirmar_revision_1",
    "paso_4_verificar_green_gate_y_snapshot_frontend"
  ),
  "verificaciones_posteriores" => array(
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=" . $base . " --origin=http://artiani.com.local --min_publicaciones=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=" . $base,
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=" . $base . " --origin=http://artiani.com.local --limite=6 --min_publicaciones=6"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_publica" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true,
    "cotizacion_registrar_sigue_bloqueado" => true
  ),
  "bloqueos" => array_values(array_unique($bloqueos))
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function validarRespaldoExpansionChecklist($respaldo) {
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

function comandoBorradorExpansionChecklist($idSku, $sugerida, $respaldo) {
  return "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php"
    . " --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR"
    . " --respaldo=" . argumentoExpansionChecklist($respaldo)
    . " --id_sku=" . intval($idSku)
    . " --mascota=" . argumentoExpansionChecklist(valorExpansionChecklist($sugerida, array("mascota_especie"), ""))
    . " --necesidades=" . argumentoExpansionChecklist(implode(",", valorExpansionChecklist($sugerida, array("necesidades"), array())));
}

function contarListosExpansionChecklist($items) {
  $total = 0;
  foreach ($items as $item) {
    if (empty($item["bloqueos_plan"]) && empty($item["bloqueos_publicacion"]) && !$item["ya_publicado_o_borrador"]) {
      $total++;
    }
  }
  return $total;
}

function argumentoExpansionChecklist($valor) {
  $valor = (string) $valor;
  if ($valor === "") {
    return "\"\"";
  }
  return "\"" . str_replace("\"", "\\\"", $valor) . "\"";
}

function valorExpansionChecklist($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
