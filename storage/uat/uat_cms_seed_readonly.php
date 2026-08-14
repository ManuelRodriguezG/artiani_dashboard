<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-12.
 * Proposito: validar estructura base CMS ecommerce despues de aplicar DDL.
 * Impacto: confirma semilla estructural en BD permitiendo bloques y publicaciones internas sin modificar API publica.
 * Contrato: read-only; no inserta, no actualiza y no elimina datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class CmsSeedReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$db = (new CmsSeedReadonlyDb())->db();
$bloqueos = array();

$conteos = array(
  "plantillas_contenido" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_plantillas WHERE codigo = 'artiani_default' AND activa = 1"),
  "slots_contenido" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_plantilla_slots s INNER JOIN erp_ecommerce_plantillas p ON p.id_plantilla = s.id_plantilla WHERE p.codigo = 'artiani_default' AND s.estatus = 'activo'"),
  "temas" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_temas WHERE codigo = 'wokiee_artiani' AND activo = 1"),
  "layouts" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_layouts l INNER JOIN erp_ecommerce_frontend_temas t ON t.id_tema = l.id_tema WHERE t.codigo = 'wokiee_artiani' AND l.estatus = 'publicado'"),
  "componentes" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_componentes c INNER JOIN erp_ecommerce_frontend_temas t ON t.id_tema = c.id_tema WHERE t.codigo = 'wokiee_artiani' AND c.estatus = 'activo'"),
  "plantillas_vista" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_plantillas p INNER JOIN erp_ecommerce_frontend_temas t ON t.id_tema = p.id_tema WHERE t.codigo = 'wokiee_artiani' AND p.estatus = 'publicado'"),
  "secciones" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_plantilla_secciones s INNER JOIN erp_ecommerce_frontend_plantillas p ON p.id_plantilla_vista = s.id_plantilla_vista WHERE p.codigo IN ('wokiee_home_default', 'wokiee_categoria_default', 'wokiee_catalogo_default') AND s.estatus = 'activo'"),
  "activaciones" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_frontend_plantilla_activas WHERE canal = 'catalogo_publico' AND estatus = 'activa'"),
  "bloques_borrador" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_contenido_bloques WHERE estatus IN ('borrador', 'pausado')"),
  "bloques_publicados_sin_publicacion" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_contenido_bloques WHERE estatus = 'publicado'"),
  "publicaciones_borrador" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_contenido_publicaciones WHERE estatus IN ('borrador', 'pausado')"),
  "publicaciones_publicadas" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_contenido_publicaciones WHERE estatus = 'publicado'"),
  "media_contenido" => contarCmsSeed($db, "SELECT COUNT(*) FROM erp_ecommerce_contenido_media")
);

if ($conteos["plantillas_contenido"] !== 1) { $bloqueos[] = "plantilla_artiani_default_no_activa"; }
if ($conteos["slots_contenido"] !== 7) { $bloqueos[] = "slots_contenido_no_son_7"; }
if ($conteos["temas"] !== 1) { $bloqueos[] = "tema_wokiee_no_activo"; }
if ($conteos["layouts"] !== 3) { $bloqueos[] = "layouts_no_son_3"; }
if ($conteos["componentes"] !== 6) { $bloqueos[] = "componentes_no_son_6"; }
if ($conteos["plantillas_vista"] !== 3) { $bloqueos[] = "plantillas_vista_no_son_3"; }
if ($conteos["secciones"] !== 7) { $bloqueos[] = "secciones_no_son_7"; }
if ($conteos["activaciones"] < 3) { $bloqueos[] = "activaciones_menores_a_3"; }
if ($conteos["bloques_publicados_sin_publicacion"] !== 0) { $bloqueos[] = "bloques_publicados_sin_flujo_publicacion"; }
if ($conteos["media_contenido"] !== 0) { $bloqueos[] = "semilla_creo_media_contenido"; }

$ok = empty($bloqueos);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "seed_estructura_con_contenido_interno",
  "senal_seed" => $ok ? "cms_seed_base_verificado" : "cms_seed_base_incompleto",
  "bloqueos" => $bloqueos,
  "conteos" => $conteos,
  "guardrails" => array(
    "read_only" => false,
    "no_escribe_bd" => true,
    "permite_bloques_borrador" => true,
    "permite_publicaciones_internas" => true,
    "api_publica_sigue_fallback" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function contarCmsSeed($db, $sql) {
  $stmt = $db->query($sql);
  return (int) $stmt->fetchColumn();
}
