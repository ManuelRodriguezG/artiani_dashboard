<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: operar con autorizacion el servicio TMS creado desde POS.
 * Impacto: cambia solo estados TMS y registra evidencia textual; no toca Ventas, caja, inventario ni garantias.
 * Contrato: requiere --autorizar=TMS_POS_OPERACION_UAT, --respaldo valido y referencia POS-SOL-UAT.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$token = argumento("--autorizar");
$respaldo = argumento("--respaldo");
$referencia = argumento("--referencia");
$tokenEsperado = "TMS_POS_OPERACION_UAT";
$validacionRespaldo = validar_respaldo_operacion($respaldo);

if ($token !== $tokenEsperado || !$validacionRespaldo["ok"] || $referencia === "") {
  responder(false, "pos_tms_operacion_bloqueada", array(
    "mensaje" => "Falta token, respaldo valido o referencia POS/TMS.",
    "token_requerido" => $tokenEsperado,
    "referencia" => $referencia,
    "validacion_respaldo" => $validacionRespaldo,
    "alcance" => alcance_operacion()
  ));
}

$servicio = buscar_servicio_por_referencia($referencia);
if (!$servicio) {
  responder(false, "pos_tms_operacion_sin_servicio", array(
    "referencia" => $referencia,
    "validacion_respaldo" => $validacionRespaldo,
    "alcance" => alcance_operacion()
  ));
}

$modelo = new TmsDelivery();
$idServicio = intval($servicio["id_tms_servicio"]);
$pasos = array();

if ($servicio["estatus_servicio"] !== "entregada") {
  if (!in_array($servicio["estatus_servicio"], array("lista_para_salida", "en_ruta"), true)) {
    $pasos[] = ejecutar_accion($modelo, $idServicio, array(
      "accion" => "marcar_lista_salida",
      "comentario" => "UAT POS/TMS: paquete listo para salida."
    ));
  }

  $servicioActual = buscar_servicio_por_referencia($referencia);
  if ($servicioActual && $servicioActual["estatus_servicio"] !== "en_ruta") {
    $pasos[] = ejecutar_accion($modelo, $idServicio, array(
      "accion" => "iniciar_ruta",
      "comentario" => "UAT POS/TMS: ruta iniciada."
    ));
  }

  $pasos[] = ejecutar_accion($modelo, $idServicio, array(
    "accion" => "entregar",
    "resultado_logistico" => "completa",
    "comentario" => "UAT POS/TMS: entrega completada con evidencia textual."
  ));
}

foreach ($pasos as $paso) {
  if (!empty($paso["respuesta"]["error"])) {
    responder(false, "pos_tms_operacion_error", array(
      "servicio_inicial" => $servicio,
      "pasos" => $pasos,
      "validacion_respaldo" => $validacionRespaldo,
      "alcance" => alcance_operacion()
    ));
  }
}

$evidencia = $modelo->registrarEvidencia(array(
  "id_tms_servicio" => $idServicio,
  "tipo_evidencia" => "nota",
  "descripcion" => "Evidencia UAT POS/TMS: servicio logistico entregado sin tocar ventas, caja, inventario ni garantias.",
  "capturado_desde" => "uat_pos_operacion"
), 0);

$postcheck = postcheck_operacion($idServicio);
responder(!empty($postcheck["ok"]) && empty($evidencia["error"]), "pos_tms_operacion_uat_completa", array(
  "servicio_inicial" => $servicio,
  "pasos" => $pasos,
  "evidencia" => $evidencia,
  "postcheck" => $postcheck,
  "validacion_respaldo" => $validacionRespaldo,
  "alcance" => alcance_operacion()
));

function ejecutar_accion($modelo, $idServicio, $datos) {
  $payload = array_merge(array("id_tms_servicio" => $idServicio), $datos);
  return array("accion" => $datos["accion"], "respuesta" => $modelo->aplicarAccionServicio($payload, 0));
}

function buscar_servicio_por_referencia($referencia) {
  $db = conexion();
  $stmt = $db->prepare("SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
      referencia_externa, motivo_logistico, tipo_servicio, estatus_servicio, estatus_cobro,
      resultado_logistico
    FROM erp_tms_servicios
    WHERE referencia_externa=:referencia
    LIMIT 1");
  $stmt->execute(array(":referencia" => $referencia));
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function postcheck_operacion($idServicio) {
  $db = conexion();
  $stmt = $db->prepare("SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
      referencia_externa, motivo_logistico, estatus_servicio, resultado_logistico, fecha_cierre
    FROM erp_tms_servicios WHERE id_tms_servicio=:id LIMIT 1");
  $stmt->execute(array(":id" => $idServicio));
  $servicio = $stmt->fetch(PDO::FETCH_ASSOC);

  $conteos = array();
  foreach (array("eventos" => "erp_tms_eventos", "evidencias" => "erp_tms_evidencias") as $clave => $tabla) {
    $filtro = $clave === "evidencias" ? " AND estatus='activa'" : "";
    $stmtConteo = $db->prepare("SELECT COUNT(*) total FROM {$tabla} WHERE id_tms_servicio=:id{$filtro}");
    $stmtConteo->execute(array(":id" => $idServicio));
    $conteos[$clave] = intval($stmtConteo->fetch(PDO::FETCH_ASSOC)["total"]);
  }

  $checks = array(
    "servicio_existe" => (bool) $servicio,
    "origen_pos" => $servicio && $servicio["solicitado_por_modulo"] === "pos",
    "tipo_solicitud_pos" => $servicio && $servicio["solicitado_por_tipo"] === "solicitud_pos",
    "motivo_logistico_puro" => $servicio && $servicio["motivo_logistico"] === "servicio_inicial",
    "entregado" => $servicio && $servicio["estatus_servicio"] === "entregada",
    "resultado_completo" => $servicio && $servicio["resultado_logistico"] === "completa",
    "evento_operacion" => $conteos["eventos"] >= 4,
    "evidencia_creada" => $conteos["evidencias"] >= 1
  );
  $fallos = array_keys(array_filter($checks, function ($ok) {
    return !$ok;
  }));
  return array("ok" => empty($fallos), "servicio" => $servicio, "conteos" => $conteos, "checks" => $checks, "fallos" => $fallos);
}

function conexion() {
  return (new class extends CRUD {
    public function conexionInterna() {
      return $this->getConexion();
    }
  })->conexionInterna();
}

function alcance_operacion() {
  return array(
    "actualiza_servicio_tms" => true,
    "crea_eventos_tms" => true,
    "crea_evidencia_tms" => true,
    "toca_ventas" => false,
    "toca_caja" => false,
    "toca_inventario" => false,
    "toca_garantias" => false,
    "rastreo_publico_cliente" => false
  );
}

function validar_respaldo_operacion($respaldo) {
  $respaldo = trim((string) $respaldo);
  $placeholder = $respaldo === "" || stripos($respaldo, "YYYYMMDD") !== false || stripos($respaldo, "RESPALDO") !== false;
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

function responder($ok, $estado, $depurar) {
  echo json_encode(array(
    "ok" => (bool) $ok,
    "modo" => $ok ? "apply-authorized" : "bloqueado",
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
