<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: crear una tarea CRM solo con token, respaldo externo y datos concretos.
 * Impacto: inserta seguimiento operativo y evento auditable si la tabla existe.
 * Contrato: no modifica clientes, no crea notificaciones SYS, no toca POS/ventas/ecommerce/legacy.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "cliente::",
  "tipo::",
  "prioridad::",
  "titulo::",
  "descripcion::",
  "fecha_vencimiento::",
  "responsable::",
  "origen_tipo::",
  "origen_id::",
  "id_usuario::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0,
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "calidad_datos",
  "prioridad" => isset($opciones["prioridad"]) ? trim((string)$opciones["prioridad"]) : "normal",
  "titulo" => isset($opciones["titulo"]) ? trim((string)$opciones["titulo"]) : "",
  "descripcion" => isset($opciones["descripcion"]) ? trim((string)$opciones["descripcion"]) : "",
  "fecha_vencimiento" => isset($opciones["fecha_vencimiento"]) ? trim((string)$opciones["fecha_vencimiento"]) : "",
  "id_usuario_responsable" => isset($opciones["responsable"]) ? intval($opciones["responsable"]) : 0,
  "origen_tipo" => isset($opciones["origen_tipo"]) ? trim((string)$opciones["origen_tipo"]) : "uat_manual",
  "origen_id" => isset($opciones["origen_id"]) ? trim((string)$opciones["origen_id"]) : "",
  "id_usuario" => isset($opciones["id_usuario"]) ? intval($opciones["id_usuario"]) : 0
);

$validacion = validar_respaldo_crm_tarea($respaldo);
$validacionDatos = validar_datos_concretos_crm_tarea($datos);

if ($autorizar !== "CRM_CLIENTES_TAREA" || !$validacion["ok"] || !$validacionDatos["ok"]) {
  echo json_encode(array(
    "error" => true,
    "tipo" => "warning",
    "mensaje" => "No se creo tarea CRM. Falta token, respaldo valido o datos concretos.",
    "depurar" => array(
      "requerido" => array(
        "autorizar" => "CRM_CLIENTES_TAREA",
        "respaldo" => "RUTA_O_REFERENCIA_EXTERNA",
        "cliente" => "ID_CLIENTE_CRM",
        "tipo" => "calidad_datos|contacto|consentimiento|postventa|comercial|garantia|apartado|devolucion|otro",
        "prioridad" => "baja|normal|alta|urgente",
        "titulo" => "texto concreto"
      ),
      "validacion_respaldo" => $validacion,
      "validacion_datos" => $validacionDatos,
      "alcance" => alcance_crm_tarea()
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

echo json_encode((new ClientesCrm())->tareaSeguimientoCrearAutorizado($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_datos_concretos_crm_tarea($datos) {
  $placeholder = false;
  foreach (array("tipo", "prioridad", "titulo", "descripcion", "origen_tipo", "origen_id") as $campo) {
    $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
    if ($valor !== "" && preg_match('/\\[[A-Z_]+\\]|<[^>]+>|PENDIENTE|POR_DEFINIR/i', $valor)) {
      $placeholder = true;
    }
  }

  $bloqueos = array();
  if (intval($datos["id_cliente_crm"]) <= 0) {
    $bloqueos[] = "cliente CRM invalido o no indicado";
  }
  if (trim((string)$datos["titulo"]) === "" || strlen(trim((string)$datos["titulo"])) < 5) {
    $bloqueos[] = "titulo ausente o demasiado corto";
  }
  if ($placeholder) {
    $bloqueos[] = "se detectaron placeholders en datos de tarea";
  }

  return array(
    "ok" => empty($bloqueos),
    "bloqueos" => $bloqueos,
    "placeholder_detectado" => $placeholder
  );
}

function alcance_crm_tarea() {
  return array(
    "crea_tarea" => true,
    "crea_evento_si_existe_tabla" => true,
    "modifica_cliente" => false,
    "crea_notificaciones_sys" => false,
    "crea_interacciones_automaticas" => false,
    "toca_pos_ventas_ecommerce_garantias_apartados_devoluciones_legacy" => false
  );
}

function validar_respaldo_crm_tarea($respaldo) {
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
