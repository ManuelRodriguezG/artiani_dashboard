<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: validar UI/datos TMS con el servicio UAT ya creado.
 * Impacto: TMS Delivery; confirma que vistas, JS y datos reales de prueba estan alineados.
 * Contrato: read-only; no crea servicios, no cambia estados, no toca Ventas/POS/Garantias/Inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$root = realpath(__DIR__ . "/../..");
$folioUat = "TMS-20260725-212914-255";
$checks = array();

$archivos = array(
  "vista_servicios" => "app/vistas/paginas/apps/tms/servicios.php",
  "vista_operacion" => "app/vistas/paginas/apps/tms/operacion.php",
  "vista_costos" => "app/vistas/paginas/apps/tms/costos.php",
  "vista_reportes" => "app/vistas/paginas/apps/tms/reportes.php",
  "vista_configuracion" => "app/vistas/paginas/apps/tms/configuracion.php",
  "js_servicios" => "public/assets/js/custom/apps/tms/servicios.js",
  "js_operacion" => "public/assets/js/custom/apps/tms/operacion.js",
  "js_costos" => "public/assets/js/custom/apps/tms/costos.js",
  "js_reportes" => "public/assets/js/custom/apps/tms/reportes.js",
  "js_configuracion" => "public/assets/js/custom/apps/tms/configuracion.js"
);

foreach ($archivos as $clave => $relativa) {
  $checks["archivo_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$checks = array_merge($checks, validar_ids($root));
$checks = array_merge($checks, validar_endpoints_js($root));

$modelo = new TmsDelivery();
$listado = $modelo->listarServicios(array("limite" => 50));
$reportes = $modelo->resumenReportes(array());
$evidencias = $modelo->listarEvidencias(1);
$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();
$conteos = conteos_tms($db);

$servicios = isset($listado["depurar"]["servicios"]) ? $listado["depurar"]["servicios"] : array();
$servicioUat = buscar_por_folio($servicios, $folioUat);
$kpis = isset($reportes["depurar"]["kpis"]) ? $reportes["depurar"]["kpis"] : array();
$evidenciasLista = isset($evidencias["depurar"]["evidencias"]) ? $evidencias["depurar"]["evidencias"] : array();

$checks["datos_schema_disponible"] = check_item(empty($listado["depurar"]["schema_pendiente"]) && empty($reportes["depurar"]["schema_pendiente"]), "Listado y reportes sin schema_pendiente");
$checks["datos_folio_uat_visible"] = check_item((bool) $servicioUat, $folioUat);
$checks["datos_folio_entregado"] = check_item($servicioUat && $servicioUat["estatus_servicio"] === "entregada", "Servicio UAT entregado");
$checks["datos_resultado_completo"] = check_item($servicioUat && $servicioUat["resultado_logistico"] === "completa", "Resultado logistico completo");
$checks["datos_reportes_total"] = check_item(isset($kpis["total"]) && intval($kpis["total"]) >= 1, "KPI total >= 1");
$checks["datos_reportes_completas"] = check_item(isset($kpis["completas"]) && intval($kpis["completas"]) >= 1, "KPI completas >= 1");
$checks["datos_reportes_ingresos"] = check_item(isset($kpis["ingresos_logisticos"]) && floatval($kpis["ingresos_logisticos"]) >= 75, "Ingresos logisticos >= 75");
$checks["datos_evidencia_uat"] = check_item(count($evidenciasLista) >= 1, "Evidencia UAT visible");
$checks["conteos_servicio"] = check_item(isset($conteos["erp_tms_servicios"]) && $conteos["erp_tms_servicios"] >= 1, "Servicios TMS >= 1");
$checks["conteos_eventos"] = check_item(isset($conteos["erp_tms_eventos"]) && $conteos["erp_tms_eventos"] >= 5, "Eventos TMS >= 5");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "ui_tms_datos_listos" : "ui_tms_datos_pendientes",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "folio_uat" => $folioUat,
  "servicio_uat" => $servicioUat,
  "kpis" => $kpis,
  "conteos" => $conteos,
  "reglas" => array(
    "read_only" => true,
    "no_modifica_ventas" => true,
    "no_toca_pos" => true,
    "no_decide_garantias" => true,
    "no_mueve_inventario" => true
  ),
  "siguiente_paso" => "Abrir UI TMS y POS en navegador para validar flujo visual; creacion real POS -> TMS requiere UAT/autorizacion separada."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_ids($root) {
  $mapa = array(
    "servicios" => array(
      "archivo" => "app/vistas/paginas/apps/tms/servicios.php",
      "ids" => array("tms_kpi_total", "tms_kpi_ruta", "tms_servicios_body", "tms_form_dryrun", "tms_guardar_servicio")
    ),
    "operacion" => array(
      "archivo" => "app/vistas/paginas/apps/tms/operacion.php",
      "ids" => array("tms_op_programadas", "tms_op_listas", "tms_op_ruta", "tms_op_incidencias", "tms_operacion_body")
    ),
    "costos" => array(
      "archivo" => "app/vistas/paginas/apps/tms/costos.php",
      "ids" => array("tms_costos_ingresos", "tms_costos_real", "tms_costos_bonificado", "tms_costos_margen")
    ),
    "reportes" => array(
      "archivo" => "app/vistas/paginas/apps/tms/reportes.php",
      "ids" => array("tms_rep_total", "tms_rep_completas", "tms_rep_express", "tms_rep_ingresos")
    ),
    "configuracion" => array(
      "archivo" => "app/vistas/paginas/apps/tms/configuracion.php",
      "ids" => array("tms_config_tipos", "tms_config_cobros", "tms_config_contrato", "tms_config_acciones")
    )
  );
  $checks = array();
  foreach ($mapa as $clave => $config) {
    $contenido = contenido($root . "/" . $config["archivo"]);
    foreach ($config["ids"] as $id) {
      $checks["ui_" . $clave . "_" . $id] = check_item(strpos($contenido, 'id="' . $id . '"') !== false, $config["archivo"] . " #" . $id);
    }
  }
  return $checks;
}

function validar_endpoints_js($root) {
  $mapa = array(
    "servicios_listar" => array("public/assets/js/custom/apps/tms/servicios.js", "/tms/servicios_listar_erp"),
    "servicios_guardar" => array("public/assets/js/custom/apps/tms/servicios.js", "/tms/servicio_guardar_erp"),
    "operacion_listar" => array("public/assets/js/custom/apps/tms/operacion.js", "/tms/servicios_listar_erp"),
    "costos_reportes" => array("public/assets/js/custom/apps/tms/costos.js", "/tms/reportes_resumen_erp"),
    "reportes_resumen" => array("public/assets/js/custom/apps/tms/reportes.js", "/tms/reportes_resumen_erp"),
    "config_catalogos" => array("public/assets/js/custom/apps/tms/configuracion.js", "/tms/catalogos_erp"),
    "config_acciones" => array("public/assets/js/custom/apps/tms/configuracion.js", "/tms/acciones_contrato_erp")
  );
  $checks = array();
  foreach ($mapa as $clave => $config) {
    $contenido = contenido($root . "/" . $config[0]);
    $checks["js_endpoint_" . $clave] = check_item(strpos($contenido, $config[1]) !== false, $config[0] . " " . $config[1]);
  }
  return $checks;
}

function buscar_por_folio($servicios, $folio) {
  foreach ($servicios as $servicio) {
    if (isset($servicio["folio"]) && $servicio["folio"] === $folio) {
      return $servicio;
    }
  }
  return null;
}

function conteos_tms($db) {
  $tablas = array("erp_tms_servicios", "erp_tms_servicios_detalle", "erp_tms_servicios_costos", "erp_tms_eventos", "erp_tms_evidencias");
  $conteos = array();
  if (!$db) {
    return $conteos;
  }
  foreach ($tablas as $tabla) {
    $stmt = $db->query("SELECT COUNT(*) total FROM `" . $tabla . "`");
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $conteos[$tabla] = $fila ? intval($fila["total"]) : 0;
  }
  return $conteos;
}

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
