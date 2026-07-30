<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: postcheck read-only para UAT real POS -> TMS.
 * Impacto: valida un servicio TMS creado desde POS sin tocar Ventas, caja, inventario ni garantias.
 * Contrato: read-only; acepta --referencia opcional o toma el ultimo POS-SOL-UAT-*.
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
  responder(false, "pos_tms_postcheck_sin_conexion", array("conexion" => false));
}

$servicio = buscar_servicio_pos_tms($db, $referencia);
if (!$servicio) {
  responder(true, "pos_tms_postcheck_pendiente_uat_real", array(
    "referencia_solicitada" => $referencia,
    "mensaje" => "Aun no hay servicio POS-SOL-UAT para validar.",
    "no_escritura_bd" => true
  ));
}

$idServicio = intval($servicio["id_tms_servicio"]);
$conteos = conteos_tms($db, $idServicio);
$checks = array(
  "origen_pos" => $servicio["solicitado_por_modulo"] === "pos",
  "tipo_solicitud_pos" => $servicio["solicitado_por_tipo"] === "solicitud_pos",
  "referencia_uat_pos" => strpos((string) $servicio["referencia_externa"], "POS-SOL-UAT-") === 0,
  "motivo_logistico_puro" => $servicio["motivo_logistico"] === "servicio_inicial",
  "tipo_logistico_valido" => in_array($servicio["tipo_servicio"], array("entrega_local", "entrega_express", "entrega_programada", "recoleccion_cliente", "entrega_tercero"), true),
  "estatus_tms_controlado" => in_array($servicio["estatus_servicio"], array("solicitada", "programada", "preparacion", "lista_salida", "en_ruta", "entregada", "no_entregada", "reprogramada", "pendiente_cliente", "cancelada"), true),
  "detalle_creado" => $conteos["detalle"] > 0,
  "costo_creado" => $conteos["costos"] > 0,
  "evento_creado" => $conteos["eventos"] > 0
);
$fallos = array_keys(array_filter($checks, function ($ok) {
  return !$ok;
}));

responder(empty($fallos), empty($fallos) ? "pos_tms_postcheck_completo" : "pos_tms_postcheck_con_fallos", array(
  "servicio" => $servicio,
  "conteos" => $conteos,
  "checks" => $checks,
  "fallos" => $fallos,
  "reglas" => array(
    "read_only" => true,
    "no_toca_ventas" => true,
    "no_toca_caja" => true,
    "no_toca_inventario" => true,
    "no_toca_garantias" => true,
    "rastreo_publico_cliente" => false
  )
));

function buscar_servicio_pos_tms($db, $referencia) {
  if ($referencia !== "") {
    $stmt = $db->prepare("SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
        referencia_externa, motivo_logistico, tipo_servicio, estatus_servicio, estatus_cobro,
        resultado_logistico, cliente_nombre_snapshot, cliente_contacto_snapshot,
        direccion_snapshot, zona_snapshot, fecha_programada, ventana_inicio, ventana_fin
      FROM erp_tms_servicios
      WHERE referencia_externa=:referencia
      LIMIT 1");
    $stmt->execute(array(":referencia" => $referencia));
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
  }

  $stmt = $db->query("SELECT id_tms_servicio, folio, solicitado_por_modulo, solicitado_por_tipo,
      referencia_externa, motivo_logistico, tipo_servicio, estatus_servicio, estatus_cobro,
      resultado_logistico, cliente_nombre_snapshot, cliente_contacto_snapshot,
      direccion_snapshot, zona_snapshot, fecha_programada, ventana_inicio, ventana_fin
    FROM erp_tms_servicios
    WHERE referencia_externa LIKE 'POS-SOL-UAT-%'
    ORDER BY id_tms_servicio DESC
    LIMIT 1");
  return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function conteos_tms($db, $idServicio) {
  $tablas = array(
    "detalle" => "erp_tms_servicios_detalle",
    "costos" => "erp_tms_servicios_costos",
    "eventos" => "erp_tms_eventos",
    "evidencias" => "erp_tms_evidencias"
  );
  $conteos = array();
  foreach ($tablas as $clave => $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) total FROM {$tabla} WHERE id_tms_servicio=:id AND estatus='activo'");
    $stmt->execute(array(":id" => $idServicio));
    $conteos[$clave] = intval($stmt->fetch(PDO::FETCH_ASSOC)["total"]);
  }
  return $conteos;
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
