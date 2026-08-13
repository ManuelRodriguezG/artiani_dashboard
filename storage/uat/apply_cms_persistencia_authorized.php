<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-12.
 * Proposito: aplicar DDL autorizado de persistencia CMS ecommerce despues de respaldo externo validado.
 * Impacto: crea tablas CMS Contenido y CMS Frontend; no inserta datos, no activa endpoints POST y no cambia API publica.
 * Contrato: requiere --respaldo=RUTA existente y mayor a 0 bytes; aborta sin respaldo.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$opciones = getopt("", array("respaldo:"));
$respaldo = isset($opciones["respaldo"]) ? (string) $opciones["respaldo"] : "";
$bloqueos = array();

if ($respaldo === "") {
  $bloqueos[] = "respaldo_requerido";
} elseif (!file_exists($respaldo)) {
  $bloqueos[] = "respaldo_no_existe";
} elseif ((int) filesize($respaldo) <= 0) {
  $bloqueos[] = "respaldo_vacio";
}

if (!empty($bloqueos)) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "apply_authorized",
    "bloqueos" => $bloqueos,
    "ddl_ejecutado" => false,
    "guardrails" => array(
      "requiere_respaldo_externo" => true,
      "no_inserta_datos" => true,
      "no_activa_post" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(2);
}

$esquema = new EcommercePublicoEsquema();
$planContenido = $esquema->planActualizarCmsContenido(true);
$planFrontend = $esquema->planActualizarCmsFrontend(true);
$auditoriaContenido = $esquema->auditarCmsContenido();
$auditoriaFrontend = $esquema->auditarCmsFrontend();

$errores = array();
if (!empty($planContenido["error"])) { $errores[] = "plan_contenido_error"; }
if (!empty($planFrontend["error"])) { $errores[] = "plan_frontend_error"; }
if ((int) valorCmsApply($auditoriaContenido, array("depurar", "tablas_faltantes"), 99) !== 0) { $errores[] = "contenido_tablas_faltantes"; }
if ((int) valorCmsApply($auditoriaFrontend, array("depurar", "tablas_faltantes"), 99) !== 0) { $errores[] = "frontend_tablas_faltantes"; }

$ok = empty($errores);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "apply_authorized",
  "senal_persistencia" => $ok ? "cms_persistencia_ddl_aplicado" : "cms_persistencia_ddl_con_observaciones",
  "errores" => $errores,
  "respaldo" => array(
    "ruta" => $respaldo,
    "tamano_bytes" => filesize($respaldo)
  ),
  "ddl" => array(
    "contenido_total" => valorCmsApply($planContenido, array("depurar", "ddl_total"), 0),
    "frontend_total" => valorCmsApply($planFrontend, array("depurar", "ddl_total"), 0),
    "contenido_faltantes" => valorCmsApply($auditoriaContenido, array("depurar", "tablas_faltantes"), null),
    "frontend_faltantes" => valorCmsApply($auditoriaFrontend, array("depurar", "tablas_faltantes"), null)
  ),
  "guardrails" => array(
    "no_inserta_datos" => true,
    "no_activa_endpoints_post" => true,
    "no_cambia_api_publica" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit($ok ? 0 : 1);

function valorCmsApply($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
