<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: alinear BD TMS a logistica pura con autorizacion explicita.
 * Impacto: TMS Delivery; actualiza solo default/datos de motivo_logistico viejo.
 * Contrato: requiere token TMS_LOGISTICA_PURA_BASE y respaldo externo valido; no toca POS/Ventas, caja, inventario ni postventa.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$token = argumento("--autorizar");
$respaldo = argumento("--respaldo");
$tokenEsperado = "TMS_LOGISTICA_PURA_BASE";

if ($token !== $tokenEsperado) {
  responder_bloqueado("Token invalido o ausente", array("token_requerido" => $tokenEsperado));
}
if ($respaldo === "" || !file_exists($respaldo) || !is_readable($respaldo)) {
  responder_bloqueado("Respaldo externo requerido no existe o no es legible", array("respaldo" => $respaldo));
}
if (stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0) {
  responder_bloqueado("El respaldo debe estar en C:\\xampp\\panel_db_backups", array("respaldo" => $respaldo));
}

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

if (!$db) {
  responder_bloqueado("No hay conexion MySQL", array("conexion" => false));
}

$resultado = array(
  "default_anterior" => null,
  "default_actualizado" => false,
  "filas_venta_inicial_antes" => 0,
  "filas_actualizadas" => 0,
  "filas_venta_inicial_despues" => 0
);

try {
  $stmtColumna = $db->prepare("SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erp_tms_servicios'
      AND COLUMN_NAME = 'motivo_logistico'
    LIMIT 1");
  $stmtColumna->execute();
  $columna = $stmtColumna->fetch(PDO::FETCH_ASSOC);
  if (!$columna) {
    throw new Exception("No existe erp_tms_servicios.motivo_logistico");
  }
  $resultado["default_anterior"] = $columna["COLUMN_DEFAULT"];

  $stmtConteo = $db->query("SELECT COUNT(*) total FROM erp_tms_servicios WHERE motivo_logistico='venta_inicial'");
  $resultado["filas_venta_inicial_antes"] = intval($stmtConteo->fetch(PDO::FETCH_ASSOC)["total"]);

  if (normalizar_default($columna["COLUMN_DEFAULT"]) !== "servicio_inicial") {
    $db->exec("ALTER TABLE erp_tms_servicios ALTER motivo_logistico SET DEFAULT 'servicio_inicial'");
    $resultado["default_actualizado"] = true;
  }

  $db->beginTransaction();
  $stmtUpdate = $db->prepare("UPDATE erp_tms_servicios
    SET motivo_logistico='servicio_inicial', fecha_actualizacion=CURRENT_TIMESTAMP
    WHERE motivo_logistico='venta_inicial'");
  $stmtUpdate->execute();
  $resultado["filas_actualizadas"] = intval($stmtUpdate->rowCount());

  $stmtDespues = $db->query("SELECT COUNT(*) total FROM erp_tms_servicios WHERE motivo_logistico='venta_inicial'");
  $resultado["filas_venta_inicial_despues"] = intval($stmtDespues->fetch(PDO::FETCH_ASSOC)["total"]);

  $db->commit();
} catch (Exception $e) {
  if ($db && $db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array(
    "ok" => false,
    "modo" => "apply-authorized",
    "estado" => "tms_logistica_pura_error",
    "mensaje" => $e->getMessage(),
    "resultado" => $resultado
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

echo json_encode(array(
  "ok" => true,
  "modo" => "apply-authorized",
  "estado" => "tms_logistica_pura_bd_alineada",
  "respaldo" => $respaldo,
  "resultado" => $resultado,
  "reglas" => array(
    "no_toca_pos_ventas" => true,
    "no_toca_caja" => true,
    "no_toca_inventario" => true,
    "no_toca_postventa" => true,
    "solo_tms_motivo_logistico" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

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
    "modo" => "apply-authorized",
    "estado" => "bloqueado",
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

function normalizar_default($valor) {
  return trim((string) $valor, "'\" ");
}
