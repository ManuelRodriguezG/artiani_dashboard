<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: preparar paquete read-only para convertir candidatos de expansion ecommerce en borradores.
 * Impacto: deja revisables metadatos, bloqueos y comandos autorizados sin escribir BD.
 * Contrato: read-only; no crea publicaciones, no publica, no toca inventario y no usa legacy ecom_*.
 */

$opciones = getopt("", array("skus::", "base::", "respaldo::"));
$skusTexto = isset($opciones["skus"]) ? trim((string) $opciones["skus"]) : "415,866,386,1138";
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "RUTA_O_REFERENCIA_RESPALDO_EXTERNO";

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
$items = array();
$bloqueosGlobales = array();

foreach ($skus as $idSku) {
  $preparacion = $modelo->prepararPublicacion(array("id_sku" => $idSku));
  $sugerida = valorPaqueteExpansion($preparacion, array("depurar", "publicacion_sugerida"), array());
  $producto = valorPaqueteExpansion($preparacion, array("depurar", "producto_vivo_erp"), array());
  $plan = $modelo->planGuardarPublicacion(array(
    "id_sku" => $idSku,
    "estatus_publicacion" => "borrador",
    "slug" => valorPaqueteExpansion($sugerida, array("slug"), ""),
    "titulo_publico" => valorPaqueteExpansion($sugerida, array("titulo_publico"), ""),
    "mascota_especie" => valorPaqueteExpansion($sugerida, array("mascota_especie"), ""),
    "necesidades" => implode(",", valorPaqueteExpansion($sugerida, array("necesidades"), array()))
  ));

  $bloqueos = valorPaqueteExpansion($plan, array("depurar", "bloqueos"), array());
  $bloqueosPublicacion = valorPaqueteExpansion($plan, array("depurar", "bloqueos_publicacion"), array());
  $requiereRevision = array();
  if (trim((string) valorPaqueteExpansion($sugerida, array("mascota_especie"), "")) === "") {
    $requiereRevision[] = "validar_mascota";
  }
  if (empty(valorPaqueteExpansion($sugerida, array("necesidades"), array()))) {
    $requiereRevision[] = "validar_necesidad";
  }
  if (valorPaqueteExpansion($producto, array("disponibilidad_publica_sugerida"), "") === "agotado") {
    $requiereRevision[] = "no_publicar_como_disponible";
  }

  if (!empty($bloqueos) || !empty($bloqueosPublicacion) || !empty($requiereRevision)) {
    $bloqueosGlobales[] = "sku_" . $idSku . "_requiere_revision";
  }

  $items[] = array(
    "id_sku" => $idSku,
    "sku" => valorPaqueteExpansion($producto, array("sku"), ""),
    "nombre" => valorPaqueteExpansion($producto, array("nombre"), ""),
    "marca" => valorPaqueteExpansion($producto, array("marca"), ""),
    "categoria" => valorPaqueteExpansion($producto, array("categoria"), ""),
    "presentacion" => valorPaqueteExpansion($sugerida, array("presentacion_publica"), ""),
    "precio" => valorPaqueteExpansion($producto, array("precio"), null),
    "disponibilidad_publica_sugerida" => valorPaqueteExpansion($producto, array("disponibilidad_publica_sugerida"), ""),
    "metadata_sugerida" => array(
      "slug" => valorPaqueteExpansion($sugerida, array("slug"), ""),
      "titulo_publico" => valorPaqueteExpansion($sugerida, array("titulo_publico"), ""),
      "mascota_especie" => valorPaqueteExpansion($sugerida, array("mascota_especie"), ""),
      "necesidades" => valorPaqueteExpansion($sugerida, array("necesidades"), array())
    ),
    "plan_guardado_readonly" => array(
      "ok" => empty($plan["error"]),
      "tipo" => valorPaqueteExpansion($plan, array("tipo"), ""),
      "mensaje" => valorPaqueteExpansion($plan, array("mensaje"), ""),
      "bloqueos" => $bloqueos,
      "bloqueos_publicacion" => $bloqueosPublicacion,
      "requiere_revision" => $requiereRevision,
      "sha256_sql" => valorPaqueteExpansion($plan, array("depurar", "sha256_sql"), "")
    ),
    "comando_plan" => comandoPlanExpansion($idSku, $sugerida),
    "comando_apply_autorizado" => comandoApplyExpansion($idSku, $sugerida, $respaldo)
  );
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "skus" => $skus,
  "resumen" => array(
    "total_skus" => count($skus),
    "listos_sin_revision" => contarListosExpansion($items),
    "requieren_revision" => count($skus) - contarListosExpansion($items)
  ),
  "items" => $items,
  "bloqueos_revision" => array_values(array_unique($bloqueosGlobales)),
  "orden_recomendado" => array(
    "revisar_metadata",
    "confirmar_respaldo_externo",
    "guardar_borradores_con_apply_authorized_si_el_dueno_lo_autoriza",
    "publicar_borradores_en_otro_paso_con_revision_visual"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_publica" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function comandoPlanExpansion($idSku, $sugerida) {
  return "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_plan_readonly.php --id_sku=" . intval($idSku)
    . " --mascota=" . argumentoExpansion(valorPaqueteExpansion($sugerida, array("mascota_especie"), ""))
    . " --necesidades=" . argumentoExpansion(implode(",", valorPaqueteExpansion($sugerida, array("necesidades"), array())));
}

function comandoApplyExpansion($idSku, $sugerida, $respaldo) {
  return "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php"
    . " --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR"
    . " --respaldo=" . argumentoExpansion($respaldo)
    . " --id_sku=" . intval($idSku)
    . " --mascota=" . argumentoExpansion(valorPaqueteExpansion($sugerida, array("mascota_especie"), ""))
    . " --necesidades=" . argumentoExpansion(implode(",", valorPaqueteExpansion($sugerida, array("necesidades"), array())));
}

function contarListosExpansion($items) {
  $total = 0;
  foreach ($items as $item) {
    if (empty(valorPaqueteExpansion($item, array("plan_guardado_readonly", "bloqueos"), array()))
      && empty(valorPaqueteExpansion($item, array("plan_guardado_readonly", "bloqueos_publicacion"), array()))
      && empty(valorPaqueteExpansion($item, array("plan_guardado_readonly", "requiere_revision"), array()))) {
      $total++;
    }
  }
  return $total;
}

function argumentoExpansion($valor) {
  $valor = (string) $valor;
  if ($valor === "") {
    return "\"\"";
  }
  return "\"" . str_replace("\"", "\\\"", $valor) . "\"";
}

function valorPaqueteExpansion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
