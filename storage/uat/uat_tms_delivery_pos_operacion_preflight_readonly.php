<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: preflight read-only para operar el servicio TMS creado desde POS.
 * Impacto: valida folio POS/TMS antes de aplicar estados logisticos y evidencia.
 * Contrato: read-only; no modifica TMS, POS, Ventas, caja, inventario ni garantias.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$referencia = argumento("--referencia");
$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

if (!$db) {
  responder(false, "pos_tms_operacion_preflight_sin_conexion", array("conexion" => false));
}

$servicio = buscar_servicio_pos_tms($db, $referencia);
$checks = array(
  "servicio_pos_tms_existe" => (bool) $servicio,
  "token_documentado" => true,
  "respaldo_requerido" => true,
  "read_only" => true
);

if ($servicio) {
  $checks["origen_pos"] = $servicio["solicitado_por_modulo"] === "pos";
  $checks["tipo_solicitud_pos"] = $servicio["solicitado_por_tipo"] === "solicitud_pos";
  $checks["motivo_logistico_puro"] = $servicio["motivo_logistico"] === "servicio_inicial";
  $checks["estatus_operable"] = in_array($servicio["estatus_servicio"], array("solicitada", "programada", "lista_para_salida", "en_ruta", "entregada"), true);
  $checks["sin_estado_comercial"] = strpos((string) $servicio["solicitado_por_tipo"], "venta") === false;
}

$fallos = array_keys(array_filter($checks, function ($ok) {
  return !$ok;
}));

responder(empty($fallos), empty($fallos) ? "pos_tms_operacion_preflight_listo" : "pos_tms_operacion_preflight_bloqueado", array(
  "servicio" => $servicio,
  "checks" => $checks,
  "fallos" => $fallos,
  "token_futuro" => "TMS_POS_OPERACION_UAT",
  "respaldo_requerido" => "C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_HHmmss_antes_tms_pos_operacion.sql",
  "comando_autorizado_futuro" => $servicio ? "C:\\xampp\\php\\php.exe storage\\uat\\uat_tms_delivery_pos_operacion_apply_authorized.php --autorizar=TMS_POS_OPERACION_UAT --respaldo=C:\\xampp\\panel_db_backups\\[RESPALDO].sql --referencia=" . $servicio["referencia_externa"] : null,
  "reglas" => array(
    "read_only" => true,
    "solo_tms" => true,
    "no_toca_ventas" => true,
    "no_toca_caja" => true,
    "no_toca_inventario" => true,
    "no_toca_garantias" => true,
    "rastreo_publico_cliente" => false
  )
));

function buscar_servicio_pos_tms($db, $referencia) {
  $sql = "SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
      referencia_externa, motivo_logistico, tipo_servicio, estatus_servicio, estatus_cobro,
      resultado_logistico, cliente_nombre_snapshot, cliente_contacto_snapshot, direccion_snapshot,
      zona_snapshot, fecha_programada, ventana_inicio, ventana_fin
    FROM erp_tms_servicios
    WHERE referencia_externa LIKE 'POS-SOL-UAT-%'";
  $params = array();
  if ($referencia !== "") {
    $sql .= " AND referencia_externa=:referencia";
    $params[":referencia"] = $referencia;
  }
  $sql .= " ORDER BY id_tms_servicio DESC LIMIT 1";
  $stmt = $db->prepare($sql);
  $stmt->execute($params);
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function responder($ok, $estado, $depurar) {
  echo json_encode(array(
    "ok" => (bool) $ok,
    "modo" => "read-only",
    "estado" => $estado,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit($ok ? 0 : 1);
}

function argumento($prefijo) {
  global $argv;
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo . "=") === 0) {
      return trim(substr($arg, strlen($prefijo) + 1), "\"' ");
    }
  }
  return "";
}
