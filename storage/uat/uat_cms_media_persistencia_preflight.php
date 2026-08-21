<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-20.
 * Proposito: validar preflight de persistencia Media CMS antes de respaldo/DDL/upload real.
 * Impacto: CMS media; protege que la fase siga read-only hasta autorizacion explicita.
 * Contrato: no ejecuta DDL, no crea carpetas, no mueve archivos y no escribe BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$cms = new EcommerceCatalogoPublico();
$esquema = new EcommercePublicoEsquema();
$plan = $esquema->planActualizarCmsMediaBiblioteca(false);
$preflight = $cms->mediaAdminPreflightInterno($plan);
$listado = $cms->mediaAdminListarInterno(array("limite" => 5));
$controlador = file_get_contents("../app/controladores/Cms.php");
$modelo = file_get_contents("../app/modelos/EcommerceCatalogoPublico.php");
$modeloEsquema = file_get_contents("../app/modelos/EcommercePublicoEsquema.php");
$vista = file_get_contents("../app/vistas/paginas/apps/erp/cms/media.php");
$js = file_get_contents("../public/assets/js/custom/apps/erp/cms/media.js");
$manual = file_get_contents("../docs/erp_cms_manual_uso.md");

$bloqueos = array();

if (!empty($plan["error"])) { $bloqueos[] = "plan_media_error"; }
if (!empty($preflight["error"])) { $bloqueos[] = "preflight_media_error"; }
if (!empty($listado["error"])) { $bloqueos[] = "listado_media_error"; }
if (valorCmsMedia($plan, array("depurar", "read_only"), false) !== true) { $bloqueos[] = "plan_media_no_readonly"; }
if (valorCmsMedia($plan, array("depurar", "ddl_total"), 0) < 2) { $bloqueos[] = "plan_media_ddl_incompleto"; }
if (valorCmsMedia($preflight, array("depurar", "carpeta_publica_propuesta"), "") !== "/assets/media/cms/ecommerce") { $bloqueos[] = "preflight_media_carpeta_incorrecta"; }
if (valorCmsMedia($preflight, array("depurar", "tabla_archivos"), "") !== "erp_ecommerce_media_archivos") { $bloqueos[] = "preflight_media_sin_tabla_archivos"; }
if (valorCmsMedia($preflight, array("depurar", "tabla_usos"), "") !== "erp_ecommerce_media_usos") { $bloqueos[] = "preflight_media_sin_tabla_usos"; }
if (valorCmsMedia($preflight, array("depurar", "limites", "max_bytes"), 0) !== 2097152) { $bloqueos[] = "preflight_media_limite_incorrecto"; }
if (empty(valorCmsMedia($preflight, array("depurar", "guardrails", "no_mueve_archivos"), false))) { $bloqueos[] = "preflight_media_no_declara_no_mueve"; }
if (empty(valorCmsMedia($preflight, array("depurar", "guardrails", "requiere_respaldo_antes_ddl"), false))) { $bloqueos[] = "preflight_media_no_pide_respaldo"; }
if (empty(valorCmsMedia($listado, array("depurar", "guardrails", "no_escribe_bd"), false))) { $bloqueos[] = "listado_media_no_declara_no_escribe"; }
if (strpos((string) $controlador, "media_admin_subir_erp") === false) { $bloqueos[] = "controlador_sin_media_subir"; }
if (strpos((string) $controlador, "respuestaEscrituraCmsMediaBloqueada") === false) { $bloqueos[] = "controlador_media_no_bloquea_post"; }
if (strpos((string) $modelo, "mediaAdminListarInterno") === false) { $bloqueos[] = "modelo_sin_media_listar"; }
if (strpos((string) $modeloEsquema, "planActualizarCmsMediaBiblioteca") === false) { $bloqueos[] = "esquema_sin_plan_media"; }
if (strpos((string) $vista, "cms_media_preflight") === false) { $bloqueos[] = "vista_media_sin_preflight"; }
if (strpos((string) $js, "cargarListadoServidor") === false) { $bloqueos[] = "js_media_sin_listado_servidor"; }
if (strpos((string) $manual, "/cms/media_admin_subir_erp") === false) { $bloqueos[] = "manual_sin_post_subir_bloqueado"; }

echo json_encode(array(
  "ok" => count($bloqueos) === 0,
  "modo" => "cms_media_preflight_readonly",
  "bloqueos" => $bloqueos,
  "plan" => array(
    "read_only" => valorCmsMedia($plan, array("depurar", "read_only"), null),
    "ddl_total" => valorCmsMedia($plan, array("depurar", "ddl_total"), 0)
  ),
  "preflight" => array(
    "carpeta_publica" => valorCmsMedia($preflight, array("depurar", "carpeta_publica_propuesta"), ""),
    "tabla_archivos" => valorCmsMedia($preflight, array("depurar", "tabla_archivos"), ""),
    "tabla_usos" => valorCmsMedia($preflight, array("depurar", "tabla_usos"), ""),
    "max_bytes" => valorCmsMedia($preflight, array("depurar", "limites", "max_bytes"), 0),
    "no_mueve_archivos" => valorCmsMedia($preflight, array("depurar", "guardrails", "no_mueve_archivos"), false)
  ),
  "listado" => array(
    "modo" => valorCmsMedia($listado, array("depurar", "modo"), ""),
    "persistencia_real" => valorCmsMedia($listado, array("depurar", "persistencia_real"), false)
  ),
  "siguiente_requiere_autorizacion" => array(
    "respaldo_bd",
    "aplicar_ddl_media",
    "crear_carpeta_publica",
    "activar_post_upload"
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorCmsMedia($origen, $ruta, $default = null) {
  $actual = $origen;
  foreach ((array) $ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
