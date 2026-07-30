<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: ejecutar UAT real autorizado POS -> TMS como solicitud logistica pura.
 * Impacto: crea un servicio TMS de prueba con origen POS; no toca Ventas, caja, inventario ni garantias.
 * Contrato: bloqueado por defecto; requiere --autorizar=TMS_POS_REAL_BASE y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$token = argumento("--autorizar");
$respaldo = argumento("--respaldo");
$tokenEsperado = "TMS_POS_REAL_BASE";
$validacionRespaldo = validar_respaldo_pos_tms($respaldo);

if ($token !== $tokenEsperado || !$validacionRespaldo["ok"]) {
  responder_bloqueado("No se ejecuto UAT real POS -> TMS. Falta token o respaldo externo valido.", array(
    "token_requerido" => $tokenEsperado,
    "validacion_respaldo" => $validacionRespaldo,
    "alcance" => alcance_pos_tms()
  ));
}

$esquema = new TmsEsquema();
$auditoria = $esquema->auditarTmsDelivery();
if (!isset($auditoria["depurar"]["tiene_pendientes"]) || $auditoria["depurar"]["tiene_pendientes"] !== false) {
  responder_bloqueado("Esquema TMS pendiente; no se ejecuta UAT real POS -> TMS.", array(
    "auditoria" => $auditoria,
    "alcance" => alcance_pos_tms()
  ));
}

$modelo = new TmsDelivery();
$referencia = "POS-SOL-UAT-" . date("Ymd-His");
$payload = array(
  "solicitado_por_modulo" => "pos",
  "solicitado_por_tipo" => "solicitud_pos",
  "referencia_externa" => $referencia,
  "motivo_logistico" => "servicio_inicial",
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "precio_cobrado" => "75.00",
  "costo_estimado" => "35.00",
  "metodo_cobro" => "efectivo",
  "cliente_nombre_snapshot" => "Cliente UAT POS TMS",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion UAT POS TMS",
  "zona_snapshot" => "Zona UAT POS",
  "fecha_programada" => date("Y-m-d", strtotime("+1 day")),
  "ventana_inicio" => "10:00",
  "ventana_fin" => "12:00",
  "observaciones" => "UAT POS -> TMS: solicitud logistica pura; no toca operaciones comerciales.",
  "detalle" => json_encode(array(
    array(
      "referencia_item_origen" => "POS-UAT-RENGLON-1",
      "cantidad" => 1,
      "descripcion_snapshot" => "Paquete logistico capturado desde POS",
      "requiere_cuidado_especial" => 1,
      "observaciones" => "Snapshot logistico de prueba"
    )
  ))
);

$dryrun = $modelo->servicioDesdePosDryRun($payload);
if (!isset($dryrun["depurar"]["puede_guardar_futuro"]) || $dryrun["depurar"]["puede_guardar_futuro"] !== true) {
  salida_pos_tms(false, $validacionRespaldo, $payload, $dryrun, null, "Dry-run POS -> TMS bloqueado; no se creo servicio.");
}

$crear = $modelo->guardarServicio($payload, 0);
if (!isset($crear["error"]) || $crear["error"] !== false) {
  salida_pos_tms(false, $validacionRespaldo, $payload, $dryrun, $crear, "No se pudo crear servicio TMS desde payload POS.");
}

$idServicio = intval($crear["depurar"]["id_tms_servicio"]);
$verificacion = verificar_servicio_pos_tms($idServicio, $referencia);
$ok = $verificacion["ok"] === true;

salida_pos_tms($ok, $validacionRespaldo, $payload, $dryrun, $crear, $ok ? "UAT real POS -> TMS completado." : "UAT real POS -> TMS creo servicio, pero fallo verificacion.", $verificacion);

function verificar_servicio_pos_tms($idServicio, $referencia) {
  $db = (new class extends CRUD {
    public function conexion() {
      return $this->getConexion();
    }
  })->conexion();
  if (!$db) {
    return array("ok" => false, "mensaje" => "Sin conexion para verificar servicio");
  }

  $stmt = $db->prepare("SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
      referencia_externa, motivo_logistico, tipo_servicio, estatus_servicio, estatus_cobro,
      resultado_logistico
    FROM erp_tms_servicios
    WHERE id_tms_servicio=:id AND referencia_externa=:referencia
    LIMIT 1");
  $stmt->execute(array(":id" => $idServicio, ":referencia" => $referencia));
  $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$servicio) {
    return array("ok" => false, "mensaje" => "Servicio TMS no encontrado despues de crearlo");
  }

  $stmtDetalle = $db->prepare("SELECT COUNT(*) total FROM erp_tms_servicios_detalle WHERE id_tms_servicio=:id AND estatus='activo'");
  $stmtDetalle->execute(array(":id" => $idServicio));
  $detalle = intval($stmtDetalle->fetch(PDO::FETCH_ASSOC)["total"]);

  $stmtCosto = $db->prepare("SELECT COUNT(*) total FROM erp_tms_servicios_costos WHERE id_tms_servicio=:id AND estatus='activo'");
  $stmtCosto->execute(array(":id" => $idServicio));
  $costos = intval($stmtCosto->fetch(PDO::FETCH_ASSOC)["total"]);

  $stmtEventos = $db->prepare("SELECT COUNT(*) total FROM erp_tms_eventos WHERE id_tms_servicio=:id AND estatus='activo'");
  $stmtEventos->execute(array(":id" => $idServicio));
  $eventos = intval($stmtEventos->fetch(PDO::FETCH_ASSOC)["total"]);

  $checks = array(
    "origen_pos" => $servicio["solicitado_por_modulo"] === "pos",
    "tipo_solicitud_pos" => $servicio["solicitado_por_tipo"] === "solicitud_pos",
    "motivo_logistico_puro" => $servicio["motivo_logistico"] === "servicio_inicial",
    "referencia_operativa" => $servicio["referencia_externa"] === $referencia,
    "estatus_inicial_tms" => $servicio["estatus_servicio"] === "solicitada",
    "resultado_pendiente" => $servicio["resultado_logistico"] === "pendiente",
    "detalle_creado" => $detalle > 0,
    "costo_creado" => $costos > 0,
    "evento_inicial_creado" => $eventos > 0
  );
  $fallos = array_keys(array_filter($checks, function ($ok) {
    return !$ok;
  }));

  return array(
    "ok" => empty($fallos),
    "servicio" => $servicio,
    "conteos" => array("detalle" => $detalle, "costos" => $costos, "eventos" => $eventos),
    "checks" => $checks,
    "fallos" => $fallos
  );
}

function salida_pos_tms($ok, $validacionRespaldo, $payload, $dryrun, $crear, $mensaje, $verificacion = null) {
  echo json_encode(array(
    "ok" => (bool) $ok,
    "modo" => "apply-authorized",
    "estado" => $ok ? "pos_tms_uat_real_completo" : "pos_tms_uat_real_error",
    "mensaje" => $mensaje,
    "validacion_respaldo" => $validacionRespaldo,
    "payload_base" => array(
      "solicitado_por_modulo" => $payload["solicitado_por_modulo"],
      "solicitado_por_tipo" => $payload["solicitado_por_tipo"],
      "referencia_externa" => $payload["referencia_externa"],
      "motivo_logistico" => $payload["motivo_logistico"],
      "tipo_servicio" => $payload["tipo_servicio"],
      "estatus_cobro" => $payload["estatus_cobro"],
      "precio_cobrado" => $payload["precio_cobrado"]
    ),
    "dryrun" => $dryrun,
    "crear" => $crear,
    "verificacion" => $verificacion,
    "alcance" => alcance_pos_tms()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit($ok ? 0 : 1);
}

function alcance_pos_tms() {
  return array(
    "crea_servicio_tms" => true,
    "crea_detalle_tms" => true,
    "crea_costo_tms" => true,
    "crea_evento_tms" => true,
    "toca_ventas" => false,
    "toca_caja" => false,
    "toca_inventario" => false,
    "toca_garantias" => false,
    "rastreo_publico_cliente" => false
  );
}

function validar_respaldo_pos_tms($respaldo) {
  $respaldo = trim((string) $respaldo);
  $placeholder = respaldo_placeholder_pos_tms($respaldo);
  $enRutaEstandar = stripos($respaldo, "C:\\xampp\\panel_db_backups\\") === 0;
  $existe = $respaldo !== "" && file_exists($respaldo);
  $legible = $existe && is_readable($respaldo);
  $tamano = $existe ? filesize($respaldo) : null;

  return array(
    "ok" => !$placeholder && $enRutaEstandar && $existe && $legible && $tamano !== null && $tamano > 0,
    "referencia" => $respaldo,
    "en_ruta_estandar" => $enRutaEstandar,
    "archivo_existe" => $existe,
    "archivo_legible" => $legible,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldo_placeholder_pos_tms($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "YYYYMMDD") !== false
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
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

function responder_bloqueado($mensaje, $depurar = array()) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "estado" => "pos_tms_uat_real_bloqueado",
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}
