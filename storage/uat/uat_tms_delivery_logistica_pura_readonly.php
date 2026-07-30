<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: auditar que TMS use contrato logistico puro en codigo y diagnosticar drift de BD.
 * Impacto: TMS Delivery; detecta lenguaje viejo ligado a ventas/postventa sin modificar datos.
 * Contrato: read-only; no ejecuta ALTER, UPDATE, INSERT ni DELETE.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$root = realpath(__DIR__ . "/../..");
$checks = array();
$pendientesBd = array();

$modelo = new TmsDelivery();
$catalogos = $modelo->catalogosTms();
$depurarCatalogos = isset($catalogos["depurar"]) ? $catalogos["depurar"] : array();
$tipos = valores_catalogo($depurarCatalogos, "tipos_servicio");
$motivos = isset($depurarCatalogos["motivos_logisticos"]) ? $depurarCatalogos["motivos_logisticos"] : array();
$modulos = isset($depurarCatalogos["modulos_solicitantes"]) ? $depurarCatalogos["modulos_solicitantes"] : array();
$contrato = isset($depurarCatalogos["contrato"]) ? $depurarCatalogos["contrato"] : array();

$checks["catalogo_tipos_vigentes"] = check_item(empty(array_intersect($tipos, array("entrega_postventa", "traslado_revision", "visita_revision", "envio_tercero"))) && in_array("entrega_tercero", $tipos, true), "Tipos TMS logisticos vigentes");
$checks["catalogo_motivos_vigentes"] = check_item(empty(array_intersect($motivos, array("venta_inicial", "revision", "cambio_acordado"))) && in_array("servicio_inicial", $motivos, true), "Motivos TMS logisticos vigentes");
$checks["catalogo_modulos_vigentes"] = check_item(!in_array("ventas", $modulos, true) && !in_array("postventa", $modulos, true) && in_array("pos", $modulos, true), "Modulos solicitantes sin ventas/postventa");
$checks["contrato_logistico_puro"] = check_item(isset($contrato["tms_solo_compromiso_logistico"]) && $contrato["tms_solo_compromiso_logistico"] === true, "Contrato TMS solo logistico");

$dryrunValido = $modelo->servicioDesdePosDryRun(array(
  "solicitado_por_tipo" => "solicitud_pos",
  "tipo_servicio" => "entrega_tercero",
  "prioridad" => "normal",
  "estatus_cobro" => "por_cobrar",
  "cliente_nombre_snapshot" => "Cliente Logistica Pura",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion Logistica Pura",
  "detalle" => json_encode(array(array("descripcion_snapshot" => "Paquete logistico", "cantidad" => 1)))
));
$dryrunViejo = $modelo->servicioDesdePosDryRun(array(
  "solicitado_por_tipo" => "pos_venta",
  "tipo_servicio" => "entrega_express",
  "prioridad" => "normal",
  "estatus_cobro" => "por_cobrar",
  "cliente_nombre_snapshot" => "Cliente Origen Viejo",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion Origen Viejo"
));

$checks["dryrun_entrega_tercero"] = check_item(isset($dryrunValido["depurar"]["puede_guardar_futuro"]) && $dryrunValido["depurar"]["puede_guardar_futuro"] === true, "Dry-run acepta entrega_tercero");
$checks["dryrun_bloquea_pos_venta"] = check_item(isset($dryrunViejo["depurar"]["puede_guardar_futuro"]) && $dryrunViejo["depurar"]["puede_guardar_futuro"] === false, "Dry-run bloquea pos_venta como origen TMS");

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$diagnosticoBd = diagnosticar_bd($db, $pendientesBd);

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) && empty($pendientesBd) ? "logistica_pura_completa" : (empty($fallos) ? "logistica_pura_codigo_listo_bd_pendiente" : "logistica_pura_codigo_pendiente"),
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "pendientes_bd" => $pendientesBd,
  "diagnostico_bd" => $diagnosticoBd,
  "dryrun_entrega_tercero" => $dryrunValido,
  "dryrun_origen_viejo" => $dryrunViejo,
  "plan_bd_si_se_autoriza" => array(
    "token" => "TMS_LOGISTICA_PURA_BASE",
    "respaldo_requerido" => "C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_antes_tms_logistica_pura.sql",
    "acciones" => array(
      "Alinear default motivo_logistico a servicio_inicial si la columna conserva otro default.",
      "Actualizar filas TMS con motivo_logistico=venta_inicial a servicio_inicial.",
      "No tocar operaciones comerciales, caja, inventario ni postventa."
    )
  ),
  "reglas" => array(
    "read_only" => true,
    "no_alter" => true,
    "no_update" => true,
    "tms_solo_logistica" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function diagnosticar_bd($db, &$pendientesBd) {
  $diagnostico = array("conexion" => (bool) $db, "columnas" => array(), "valores_viejos" => array());
  if (!$db) {
    $pendientesBd[] = "No hay conexion MySQL para auditar drift TMS";
    return $diagnostico;
  }
  $stmt = $db->prepare("SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'erp_tms_servicios'
      AND COLUMN_NAME IN ('motivo_logistico', 'tipo_servicio', 'solicitado_por_modulo', 'solicitado_por_tipo')");
  $stmt->execute();
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $columna) {
    $diagnostico["columnas"][$columna["COLUMN_NAME"]] = $columna;
  }
  if (isset($diagnostico["columnas"]["motivo_logistico"]) && normalizar_default($diagnostico["columnas"]["motivo_logistico"]["COLUMN_DEFAULT"]) !== "servicio_inicial") {
    $pendientesBd[] = "Default BD motivo_logistico debe alinearse a servicio_inicial";
  }
  foreach (array(
    "motivo_logistico" => array("venta_inicial", "revision", "cambio_acordado"),
    "tipo_servicio" => array("entrega_postventa", "traslado_revision", "visita_revision", "envio_tercero"),
    "solicitado_por_modulo" => array("ventas", "postventa"),
    "solicitado_por_tipo" => array("pos_venta", "pedido_pos", "apartado_pos", "reclamo_postventa")
  ) as $columna => $valores) {
    if (!isset($diagnostico["columnas"][$columna])) {
      continue;
    }
    $placeholders = implode(",", array_fill(0, count($valores), "?"));
    $sql = "SELECT `" . $columna . "` valor, COUNT(*) total FROM erp_tms_servicios WHERE `" . $columna . "` IN (" . $placeholders . ") GROUP BY `" . $columna . "`";
    $stmtValores = $db->prepare($sql);
    $stmtValores->execute($valores);
    $filas = $stmtValores->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($filas)) {
      $diagnostico["valores_viejos"][$columna] = $filas;
      $pendientesBd[] = "Existen valores viejos en " . $columna;
    }
  }
  return $diagnostico;
}

function valores_catalogo($catalogos, $clave) {
  $items = isset($catalogos[$clave]) ? $catalogos[$clave] : array();
  $valores = array();
  foreach ($items as $item) {
    if (is_array($item) && isset($item["valor"])) {
      $valores[] = $item["valor"];
    } elseif (is_string($item)) {
      $valores[] = $item;
    }
  }
  return $valores;
}

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function normalizar_default($valor) {
  return trim((string) $valor, "'\" ");
}
