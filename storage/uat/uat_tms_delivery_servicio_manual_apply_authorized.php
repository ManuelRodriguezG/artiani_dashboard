<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: ejecutar UAT autorizado de servicio manual TMS despues de permisos y DDL.
 * Impacto: crea un servicio TMS de prueba, eventos y evidencia; no toca Ventas/Garantias/Inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar=TMS_UAT_SERVICIO_MANUAL y --respaldo valido.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_tms_uat($respaldo);

if ($autorizar !== "TMS_UAT_SERVICIO_MANUAL" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto UAT de servicio manual TMS. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "TMS_UAT_SERVICIO_MANUAL",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_servicio_tms_prueba" => true,
      "crea_eventos_tms" => true,
      "crea_evidencia_tms" => true,
      "toca_ventas" => false,
      "toca_pos" => false,
      "toca_inventario" => false,
      "toca_garantias" => false,
      "sincroniza_permisos" => false,
      "ejecuta_ddl" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$modelo = new TmsDelivery();
$usuarioUat = 0;
$fecha = date("Y-m-d", strtotime("+1 day"));
$payload = array(
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "precio_cobrado" => "75.00",
  "costo_estimado" => "35.00",
  "metodo_cobro" => "efectivo",
  "solicitado_por_modulo" => "manual",
  "solicitado_por_tipo" => "uat_servicio_manual",
  "referencia_externa" => "UAT-TMS-" . date("Ymd-His"),
  "motivo_logistico" => "venta_inicial",
  "cliente_nombre_snapshot" => "Cliente UAT TMS",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion UAT TMS sin relacion a venta",
  "zona_snapshot" => "Zona UAT",
  "fecha_programada" => $fecha,
  "ventana_inicio" => "10:00",
  "ventana_fin" => "12:00",
  "observaciones" => "UAT manual TMS; no vincula venta, garantia ni inventario.",
  "detalle" => json_encode(array(array(
    "referencia_item_origen" => "UAT-PAQUETE-1",
    "cantidad" => 1,
    "descripcion_snapshot" => "Paquete de prueba logistica TMS",
    "requiere_cuidado_especial" => 1,
    "observaciones" => "Articulo fragil de prueba"
  )))
);

$pasos = array();
$crear = $modelo->guardarServicio($payload, $usuarioUat);
$pasos[] = array("paso" => "crear_servicio", "respuesta" => $crear);
if (!empty($crear["error"])) {
  salida_uat(false, $validacion, $pasos, "No se pudo crear servicio TMS de prueba.");
}

$idServicio = intval($crear["depurar"]["id_tms_servicio"]);
$acciones = array(
  array("accion" => "marcar_lista_salida"),
  array("accion" => "iniciar_ruta"),
  array("accion" => "entregar", "resultado_logistico" => "completa", "comentario" => "Entrega UAT completada con evidencia textual.")
);

foreach ($acciones as $accion) {
  $datosAccion = array_merge(array("id_tms_servicio" => $idServicio), $accion);
  $respuesta = $modelo->aplicarAccionServicio($datosAccion, $usuarioUat);
  $pasos[] = array("paso" => "accion_" . $accion["accion"], "respuesta" => $respuesta);
  if (!empty($respuesta["error"])) {
    salida_uat(false, $validacion, $pasos, "Fallo accion TMS: " . $accion["accion"]);
  }
}

$evidencia = $modelo->registrarEvidencia(array(
  "id_tms_servicio" => $idServicio,
  "tipo_evidencia" => "nota",
  "descripcion" => "Evidencia textual UAT: entrega completada sin tocar venta, garantia o inventario.",
  "capturado_desde" => "uat_manual"
), $usuarioUat);
$pasos[] = array("paso" => "registrar_evidencia", "respuesta" => $evidencia);
if (!empty($evidencia["error"])) {
  salida_uat(false, $validacion, $pasos, "Fallo registro de evidencia TMS.");
}

salida_uat(true, $validacion, $pasos, "UAT servicio manual TMS completado.");

function salida_uat($ok, $validacion, $pasos, $mensaje) {
  $ultimo = end($pasos);
  echo json_encode(array(
    "ok" => (bool) $ok,
    "modo" => "tms_uat_servicio_manual_apply_authorized",
    "mensaje" => $mensaje,
    "validacion_respaldo" => $validacion,
    "pasos_total" => count($pasos),
    "ultimo_paso" => $ultimo ?: null,
    "pasos" => $pasos,
    "reglas" => array(
      "crea_solo_tms" => true,
      "no_modifica_ventas" => true,
      "no_decide_garantias" => true,
      "no_mueve_inventario" => true
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

function validar_respaldo_tms_uat($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldo_placeholder_tms_uat($respaldo);
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldo_placeholder_tms_uat($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
