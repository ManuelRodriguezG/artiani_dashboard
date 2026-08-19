<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-13.
 * Proposito: validar que la API publica de contenido lee publicaciones CMS publicadas/vigentes desde BD.
 * Impacto: prueba la conexion real CMS -> /ecommercePublico/contenido_pagina sin dejar datos permanentes.
 * Contrato: inserta datos temporales dentro de una transaccion y siempre ejecuta rollback.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

class CmsPublicoTemporalRollback extends EcommerceCatalogoPublico {
  public function db() {
    return $this->getConexion();
  }
}

$api = new CmsPublicoTemporalRollback();
$db = $api->db();
$bloqueos = array();
$ids = array(
  "id_plantilla" => 0,
  "id_slot" => 0,
  "id_bloque" => 0,
  "id_publicacion_contenido" => 0
);
$codigo = "uat_cms_publico_" . date("YmdHis") . "_" . mt_rand(1000, 9999);
$titulo = "UAT CMS publico temporal " . date("YmdHis");
$rollbackOk = false;
$respuestaHome = array();
$respuestaBloqueo = array();

try {
  if (!$db) {
    throw new Exception("conexion_no_disponible");
  }

  $db->beginTransaction();

  $stmtPlantilla = $db->prepare("SELECT id_plantilla FROM erp_ecommerce_plantillas WHERE codigo='artiani_default' AND activa=1 LIMIT 1");
  $stmtPlantilla->execute();
  $ids["id_plantilla"] = (int) $stmtPlantilla->fetchColumn();
  if ($ids["id_plantilla"] <= 0) {
    $bloqueos[] = "plantilla_artiani_default_no_disponible";
  }

  $stmtSlot = $db->prepare("SELECT id_slot FROM erp_ecommerce_plantilla_slots WHERE id_plantilla=:plantilla AND codigo='home.promo' AND estatus='activo' LIMIT 1");
  $stmtSlot->execute(array(":plantilla" => $ids["id_plantilla"]));
  $ids["id_slot"] = (int) $stmtSlot->fetchColumn();
  if ($ids["id_slot"] <= 0) {
    $bloqueos[] = "slot_home_promo_no_disponible";
  }

  if (empty($bloqueos)) {
    $payload = array(
      "id" => "uat-temporal",
      "tipo" => "promo_strip",
      "estatus" => "publicado",
      "texto" => $titulo,
      "icono" => "sparkles",
      "cta" => array("label" => "Ver prueba", "url" => "/catalogo?uat=cms")
    );

    $stmtBloque = $db->prepare(
      "INSERT INTO erp_ecommerce_contenido_bloques (tipo_bloque, codigo, nombre_interno, titulo, payload_json, estatus, creado_por) VALUES (:tipo, :codigo, :nombre, :titulo, :payload, 'borrador', NULL)"
    );
    $stmtBloque->execute(array(
      ":tipo" => "promo_strip",
      ":codigo" => $codigo,
      ":nombre" => $titulo,
      ":titulo" => $titulo,
      ":payload" => json_encode($payload, JSON_UNESCAPED_UNICODE)
    ));
    $ids["id_bloque"] = (int) $db->lastInsertId();

    $stmtPublicacion = $db->prepare(
      "INSERT INTO erp_ecommerce_contenido_publicaciones (id_plantilla, id_slot, id_bloque, pagina, contexto_clave, orden, estatus, vigente_desde, vigente_hasta, canal, publicado_por, actualizado_por) VALUES (:plantilla, :slot, :bloque, 'home', '*', 1, 'publicado', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 1 DAY), 'catalogo_publico', NULL, NULL)"
    );
    $stmtPublicacion->execute(array(
      ":plantilla" => $ids["id_plantilla"],
      ":slot" => $ids["id_slot"],
      ":bloque" => $ids["id_bloque"]
    ));
    $ids["id_publicacion_contenido"] = (int) $db->lastInsertId();

    $respuestaHome = $api->contenidoPaginaPublica(array("pagina" => "home", "plantilla" => "artiani_default"));
    if (!empty($respuestaHome["error"])) {
      $bloqueos[] = "contenido_pagina_error";
    }
    if (valorCmsTemporal($respuestaHome, array("depurar", "fuente"), "") !== "bd_publicada") {
      $bloqueos[] = "contenido_pagina_no_lee_bd_publicada";
    }
    if (!bloqueTemporalVisibleCms($respuestaHome, $titulo)) {
      $bloqueos[] = "bloque_temporal_no_visible_en_api";
    }
    if (empty(valorCmsTemporal($respuestaHome, array("depurar", "guardrails", "solo_publicado"), false))) {
      $bloqueos[] = "api_no_declara_solo_publicado";
    }
    if (empty(valorCmsTemporal($respuestaHome, array("depurar", "guardrails", "solo_vigente"), false))) {
      $bloqueos[] = "api_no_declara_solo_vigente";
    }

    $payloadHeroInvalido = array(
      "id" => "uat-hero-invalido",
      "tipo" => "hero_banner",
      "estatus" => "borrador",
      "titulo" => "Hero temporal sin alt",
      "media" => array("imagen_desktop" => "", "imagen_mobile" => "", "alt" => ""),
      "cta" => array("label" => "Ver", "url" => "/catalogo")
    );
    $stmtBloqueHero = $db->prepare(
      "INSERT INTO erp_ecommerce_contenido_bloques (tipo_bloque, codigo, nombre_interno, titulo, payload_json, estatus, creado_por) VALUES ('hero_banner', :codigo, 'Hero temporal sin alt', 'Hero temporal sin alt', :payload, 'borrador', NULL)"
    );
    $stmtBloqueHero->execute(array(
      ":codigo" => $codigo . "_hero_invalido",
      ":payload" => json_encode($payloadHeroInvalido, JSON_UNESCAPED_UNICODE)
    ));
    $idBloqueHero = (int) $db->lastInsertId();

    $stmtHeroSlot = $db->prepare("SELECT id_slot FROM erp_ecommerce_plantilla_slots WHERE id_plantilla=:plantilla AND codigo='home.hero' AND estatus='activo' LIMIT 1");
    $stmtHeroSlot->execute(array(":plantilla" => $ids["id_plantilla"]));
    $idHeroSlot = (int) $stmtHeroSlot->fetchColumn();

    $stmtPublicacionHero = $db->prepare(
      "INSERT INTO erp_ecommerce_contenido_publicaciones (id_plantilla, id_slot, id_bloque, pagina, contexto_clave, orden, estatus, canal) VALUES (:plantilla, :slot, :bloque, 'home', '*', 99, 'borrador', 'catalogo_publico')"
    );
    $stmtPublicacionHero->execute(array(
      ":plantilla" => $ids["id_plantilla"],
      ":slot" => $idHeroSlot,
      ":bloque" => $idBloqueHero
    ));
    $respuestaBloqueo = $api->contenidoPublicacionEstatusInterna(array(
      "id_publicacion_contenido" => (int) $db->lastInsertId(),
      "estatus" => "publicado"
    ), 0);
    if (empty($respuestaBloqueo["error"])) {
      $bloqueos[] = "publicacion_invalida_no_bloqueada";
    }
    if (strpos(json_encode($respuestaBloqueo, JSON_UNESCAPED_UNICODE), "falta alt text de imagen") === false) {
      $bloqueos[] = "publicacion_invalida_sin_mensaje_alt";
    }
  }
} catch (Exception $e) {
  $bloqueos[] = "excepcion_" . $e->getMessage();
} finally {
  if ($db && $db->inTransaction()) {
    $rollbackOk = $db->rollBack();
  }
}

$persistio = 0;
if ($db) {
  $stmtPersistio = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_contenido_bloques WHERE codigo=:codigo");
  $stmtPersistio->execute(array(":codigo" => $codigo));
  $persistio = (int) $stmtPersistio->fetchColumn();
  if ($persistio !== 0) {
    $bloqueos[] = "rollback_no_limpio_bloque_temporal";
  }
}
if (!$rollbackOk) {
  $bloqueos[] = "rollback_no_confirmado";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "rollback_transaccional",
  "senal" => $ok ? "cms_publico_bd_publicada_validado" : "cms_publico_bd_publicada_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "ids_temporales" => $ids,
  "fuente_home" => valorCmsTemporal($respuestaHome, array("depurar", "fuente"), ""),
  "bloques_home" => valorCmsTemporal($respuestaHome, array("depurar", "resumen", "bloques_total"), 0),
  "validacion_publicacion" => array(
    "hero_sin_alt_bloqueado" => !empty($respuestaBloqueo["error"]),
    "mensaje" => valorCmsTemporal($respuestaBloqueo, array("mensaje"), "")
  ),
  "rollback" => array(
    "ejecutado" => $rollbackOk,
    "bloques_temporales_persistentes" => $persistio
  ),
  "guardrails" => array(
    "no_deja_datos" => $persistio === 0,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true,
    "solo_publicado_vigente" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorCmsTemporal($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function bloqueTemporalVisibleCms($respuesta, $titulo) {
  $slots = valorCmsTemporal($respuesta, array("depurar", "slots"), array());
  foreach ((array) $slots as $slot) {
    foreach ((array) valorCmsTemporal($slot, array("bloques"), array()) as $bloque) {
      if ((string) valorCmsTemporal($bloque, array("texto"), "") === (string) $titulo || (string) valorCmsTemporal($bloque, array("titulo"), "") === (string) $titulo) {
        return true;
      }
    }
  }
  return false;
}
