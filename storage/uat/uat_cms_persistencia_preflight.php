<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-12.
 * Proposito: validar preflight de persistencia real CMS antes de respaldo, DDL y escrituras.
 * Impacto: CMS ecommerce; confirma plan completo de 11 tablas sin ejecutar DDL ni tocar datos.
 * Contrato: read-only; no crea tablas, no inserta datos, no cambia endpoints publicos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$cms = new EcommerceCatalogoPublico();
$esquema = new EcommercePublicoEsquema();

$auditoriaContenido = $esquema->auditarCmsContenido();
$planContenido = $esquema->planActualizarCmsContenido(false);
$auditoriaFrontend = $esquema->auditarCmsFrontend();
$planFrontend = $esquema->planActualizarCmsFrontend(false);
$estadoContenido = $cms->contenidoAdminEstadoInterno($auditoriaContenido, $planContenido);
$manifestFrontend = $cms->frontendPlantillasAdminManifestInterno();

$docsRespaldo = file_get_contents("../docs/erp_respaldo_bd_estandar.md");
$manualCms = file_get_contents("../docs/erp_cms_manual_uso.md");

$bloqueos = array();

if (!empty($planContenido["error"])) { $bloqueos[] = "plan_contenido_error"; }
if (!empty($planFrontend["error"])) { $bloqueos[] = "plan_frontend_error"; }
if ((int) valorCmsPreflight($planContenido, array("depurar", "ddl_total"), 0) !== 5) { $bloqueos[] = "plan_contenido_no_declara_5_tablas"; }
if ((int) valorCmsPreflight($planFrontend, array("depurar", "ddl_total"), 0) !== 6) { $bloqueos[] = "plan_frontend_no_declara_6_tablas"; }
if (valorCmsPreflight($planContenido, array("depurar", "read_only"), false) !== true) { $bloqueos[] = "plan_contenido_no_readonly"; }
if (valorCmsPreflight($planFrontend, array("depurar", "read_only"), false) !== true) { $bloqueos[] = "plan_frontend_no_readonly"; }
if (valorCmsPreflight($estadoContenido, array("depurar", "persistencia_real"), true) !== false) { $bloqueos[] = "persistencia_real_activa_antes_de_autorizacion"; }
if ((int) valorCmsPreflight($auditoriaContenido, array("depurar", "tablas_total"), 0) !== 5) { $bloqueos[] = "auditoria_contenido_no_declara_5_tablas"; }
if ((int) valorCmsPreflight($auditoriaFrontend, array("depurar", "tablas_total"), 0) !== 6) { $bloqueos[] = "auditoria_frontend_no_declara_6_tablas"; }
if (count(valorCmsPreflight($manifestFrontend, array("depurar", "activaciones"), array())) < 3) { $bloqueos[] = "manifest_frontend_sin_activaciones"; }
if (valorCmsPreflight($manifestFrontend, array("depurar", "tema_activo", "codigo"), "") !== "wokiee_artiani") { $bloqueos[] = "manifest_frontend_sin_tema_activo"; }
if (strpos((string) $docsRespaldo, "C:\\xampp\\panel_db_backups") === false) { $bloqueos[] = "doc_respaldo_sin_ruta_estandar"; }
if (strpos((string) $docsRespaldo, "No ejecutar DDL ni scripts `apply_authorized` sin respaldo externo") === false) { $bloqueos[] = "doc_respaldo_sin_guardrail_ddl"; }
if (strpos((string) $manualCms, "Planes DDL read-only") === false) { $bloqueos[] = "manual_sin_preflight_persistencia"; }
if (strpos((string) $manualCms, "erp_ecommerce_frontend_plantilla_activas") === false) { $bloqueos[] = "manual_sin_activaciones_frontend"; }

$ddlContenido = (int) valorCmsPreflight($planContenido, array("depurar", "ddl_total"), 0);
$ddlFrontend = (int) valorCmsPreflight($planFrontend, array("depurar", "ddl_total"), 0);
$ok = empty($bloqueos);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "preflight_readonly",
  "senal_persistencia" => $ok ? "cms_persistencia_lista_para_respaldo_y_autorizacion" : "cms_persistencia_preflight_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "ddl" => array(
    "contenido" => $ddlContenido,
    "frontend" => $ddlFrontend,
    "total" => $ddlContenido + $ddlFrontend,
    "ejecutado" => false
  ),
  "auditoria" => array(
    "contenido_tablas_total" => valorCmsPreflight($auditoriaContenido, array("depurar", "tablas_total"), 0),
    "contenido_tablas_faltantes" => valorCmsPreflight($auditoriaContenido, array("depurar", "tablas_faltantes"), 0),
    "frontend_tablas_total" => valorCmsPreflight($auditoriaFrontend, array("depurar", "tablas_total"), 0),
    "frontend_tablas_faltantes" => valorCmsPreflight($auditoriaFrontend, array("depurar", "tablas_faltantes"), 0)
  ),
  "respaldo_requerido" => array(
    "ruta_estandar" => "C:\\xampp\\panel_db_backups",
    "documento" => "docs/erp_respaldo_bd_estandar.md",
    "requerido_antes_de_ddl" => true
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true,
    "no_edita_archivos_frontend" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorCmsPreflight($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
