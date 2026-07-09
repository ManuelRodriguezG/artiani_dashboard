<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: cambiar estatus de una tarea CRM solo con token, respaldo externo y datos concretos.
 * Impacto: completa ciclo de vida de seguimiento sin modificar la ficha del cliente.
 * Contrato: no crea tareas, no crea interacciones, no crea notificaciones SYS ni toca POS/ventas/ecommerce/legacy.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "tarea::",
  "estatus::",
  "resultado::",
  "nota::",
  "id_usuario::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
$datos = array(
  "id_cliente_tarea" => isset($opciones["tarea"]) ? intval($opciones["tarea"]) : 0,
  "estatus" => isset($opciones["estatus"]) ? trim((string)$opciones["estatus"]) : "",
  "resultado_cierre" => isset($opciones["resultado"]) ? trim((string)$opciones["resultado"]) : "",
  "nota" => isset($opciones["nota"]) ? trim((string)$opciones["nota"]) : "",
  "id_usuario" => isset($opciones["id_usuario"]) ? intval($opciones["id_usuario"]) : 0
);

$validacion = validar_respaldo_crm_tarea_estatus($respaldo);
$validacionDatos = validar_datos_concretos_crm_tarea_estatus($datos);

if ($autorizar !== "CRM_CLIENTES_TAREA_ESTATUS" || !$validacion["ok"] || !$validacionDatos["ok"]) {
  echo json_encode(array(
    "error" => true,
    "tipo" => "warning",
    "mensaje" => "No se actualizo tarea CRM. Falta token, respaldo valido o datos concretos.",
    "depurar" => array(
      "requerido" => array(
        "autorizar" => "CRM_CLIENTES_TAREA_ESTATUS",
        "respaldo" => "RUTA_O_REFERENCIA_EXTERNA",
        "tarea" => "ID_CLIENTE_TAREA",
        "estatus" => "en_proceso|cerrada|cancelada",
        "resultado" => "texto concreto si cierra o cancela"
      ),
      "validacion_respaldo" => $validacion,
      "validacion_datos" => $validacionDatos,
      "alcance" => alcance_crm_tarea_estatus()
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

echo json_encode((new ClientesCrm())->tareaEstatusAutorizado($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_datos_concretos_crm_tarea_estatus($datos) {
  $placeholder = false;
  foreach (array("estatus", "resultado_cierre", "nota") as $campo) {
    $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
    if ($valor !== "" && preg_match('/\\[[A-Z_]+\\]|<[^>]+>|PENDIENTE|POR_DEFINIR/i', $valor)) {
      $placeholder = true;
    }
  }

  $bloqueos = array();
  if (intval($datos["id_cliente_tarea"]) <= 0) {
    $bloqueos[] = "tarea CRM invalida o no indicada";
  }
  if (!in_array((string)$datos["estatus"], array("en_proceso", "cerrada", "cancelada"), true)) {
    $bloqueos[] = "estatus destino invalido";
  }
  if (in_array((string)$datos["estatus"], array("cerrada", "cancelada"), true) && trim((string)$datos["resultado_cierre"]) === "") {
    $bloqueos[] = "resultado requerido para cerrar o cancelar";
  }
  if ($placeholder) {
    $bloqueos[] = "se detectaron placeholders en datos de estatus";
  }

  return array(
    "ok" => empty($bloqueos),
    "bloqueos" => $bloqueos,
    "placeholder_detectado" => $placeholder
  );
}

function alcance_crm_tarea_estatus() {
  return array(
    "actualiza_tarea" => true,
    "crea_evento_si_existe_tabla" => true,
    "modifica_cliente" => false,
    "crea_tareas" => false,
    "crea_interacciones_automaticas" => false,
    "crea_notificaciones_sys" => false,
    "toca_pos_ventas_ecommerce_garantias_apartados_devoluciones_legacy" => false
  );
}

function validar_respaldo_crm_tarea_estatus($respaldo) {
  $respaldo = trim((string)$respaldo);
  $placeholder = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|\\[RUTA|<ruta|ruta real)/i', $respaldo) === 1;
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = $esRutaLocal ? file_exists($respaldo) : null;
  $legible = $esRutaLocal ? ($existe && is_readable($respaldo)) : null;
  $tamano = $esRutaLocal && $existe ? filesize($respaldo) : null;
  $raizProyecto = realpath(__DIR__ . "/../..");
  $realRespaldo = $esRutaLocal && $existe ? realpath($respaldo) : false;
  $dentroProyecto = $raizProyecto && $realRespaldo && stripos($realRespaldo, $raizProyecto) === 0;
  $respaldoOk = strlen($respaldo) >= 8 && !$placeholder && !$dentroProyecto && (!$esRutaLocal || ($existe && $legible && $tamano > 0));

  return array(
    "ok" => $respaldoOk,
    "referencia_presente" => strlen($respaldo) >= 8,
    "placeholder_detectado" => $placeholder,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $existe,
    "archivo_legible" => $legible,
    "tamano_bytes" => $tamano,
    "dentro_del_proyecto" => $dentroProyecto
  );
}
