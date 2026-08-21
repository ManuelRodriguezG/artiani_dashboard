<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-21.
 * Proposito: validar contrato Media CMS despues de respaldo/DDL con upload real controlado.
 * Impacto: CMS media; protege que solo el alta este activa y que editar/archivar/usos sigan bloqueados.
 * Contrato: UAT read-only; no sube archivos, no crea carpetas, no borra fisicos y no escribe BD.
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
if (empty(valorCmsMedia($preflight, array("depurar", "guardrails", "upload_activo"), false))) { $bloqueos[] = "preflight_media_no_declara_upload_activo"; }
if (empty(valorCmsMedia($preflight, array("depurar", "guardrails", "requiere_csrf_post"), false))) { $bloqueos[] = "preflight_media_no_declara_csrf"; }
if (empty(valorCmsMedia($listado, array("depurar", "guardrails", "no_escribe_bd"), false))) { $bloqueos[] = "listado_media_no_declara_no_escribe"; }
if (strpos((string) $controlador, "media_admin_subir_erp") === false) { $bloqueos[] = "controlador_sin_media_subir"; }
if (strpos((string) $controlador, "mediaAdminSubirInterno") === false) { $bloqueos[] = "controlador_subir_no_llama_modelo"; }
if (strpos((string) $controlador, "SesionSeguridad::registrarAuditoria(\"cms\", \"media_admin_subir_erp\"") === false) { $bloqueos[] = "controlador_subir_sin_auditoria"; }
if (strpos((string) $controlador, "respuestaEscrituraCmsMediaBloqueada") === false) { $bloqueos[] = "controlador_media_no_bloquea_post"; }
if (strpos((string) $modelo, "mediaAdminListarInterno") === false) { $bloqueos[] = "modelo_sin_media_listar"; }
if (strpos((string) $modelo, "mediaAdminSubirInterno") === false) { $bloqueos[] = "modelo_sin_media_subir"; }
if (strpos((string) $modelo, "move_uploaded_file") === false) { $bloqueos[] = "modelo_media_no_mueve_upload"; }
if (strpos((string) $modelo, "hash_file(\"sha256\"") === false) { $bloqueos[] = "modelo_media_sin_hash"; }
if (strpos((string) $modelo, "getimagesize") === false) { $bloqueos[] = "modelo_media_sin_dimensiones"; }
if (strpos((string) $modeloEsquema, "planActualizarCmsMediaBiblioteca") === false) { $bloqueos[] = "esquema_sin_plan_media"; }
if (strpos((string) $vista, "cms_media_preflight") === false) { $bloqueos[] = "vista_media_sin_preflight"; }
if (strpos((string) $vista, "Subir a biblioteca") === false) { $bloqueos[] = "vista_media_sin_upload_activo"; }
if (strpos((string) $js, "cargarListadoServidor") === false) { $bloqueos[] = "js_media_sin_listado_servidor"; }
if (strpos((string) $js, "function subirArchivoServidor") === false) { $bloqueos[] = "js_media_sin_subir_servidor"; }
if (strpos((string) $js, "\"X-CSRF-Token\"") === false) { $bloqueos[] = "js_media_sin_csrf"; }
if (strpos((string) $manual, "/cms/media_admin_subir_erp") === false) { $bloqueos[] = "manual_sin_post_subir"; }

echo json_encode(array(
  "ok" => count($bloqueos) === 0,
  "modo" => "cms_media_upload_activo_controlado",
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
    "upload_activo" => valorCmsMedia($preflight, array("depurar", "guardrails", "upload_activo"), false),
    "requiere_csrf_post" => valorCmsMedia($preflight, array("depurar", "guardrails", "requiere_csrf_post"), false)
  ),
  "listado" => array(
    "modo" => valorCmsMedia($listado, array("depurar", "modo"), ""),
    "persistencia_real" => valorCmsMedia($listado, array("depurar", "persistencia_real"), false)
  ),
  "siguiente_requiere_autorizacion" => array(
    "probar_upload_desde_panel",
    "activar_edicion_metadatos",
    "activar_archivado_seguro",
    "registrar_usos_reales_desde_home_categorias_marcas"
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
