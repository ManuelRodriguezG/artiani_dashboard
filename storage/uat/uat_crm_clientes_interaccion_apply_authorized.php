<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: crear una interaccion CRM solo con token, respaldo externo y datos concretos.
 * Impacto: inserta historial operativo del cliente y evento auditable si la tabla existe.
 * Contrato: no modifica datos maestros, no crea/cierra tareas, no crea notificaciones SYS ni toca POS/ventas/ecommerce/legacy.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "autorizar::",
  "respaldo::",
  "cliente::",
  "tipo::",
  "canal::",
  "direccion::",
  "resultado::",
  "resumen::",
  "detalle::",
  "fecha_interaccion::",
  "origen_tipo::",
  "origen_id::",
  "id_usuario::"
));

$autorizar = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0,
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "contacto",
  "canal" => isset($opciones["canal"]) ? trim((string)$opciones["canal"]) : "whatsapp",
  "direccion" => isset($opciones["direccion"]) ? trim((string)$opciones["direccion"]) : "saliente",
  "resultado" => isset($opciones["resultado"]) ? trim((string)$opciones["resultado"]) : "registrado",
  "resumen" => isset($opciones["resumen"]) ? trim((string)$opciones["resumen"]) : "",
  "detalle" => isset($opciones["detalle"]) ? trim((string)$opciones["detalle"]) : "",
  "fecha_interaccion" => isset($opciones["fecha_interaccion"]) ? trim((string)$opciones["fecha_interaccion"]) : "",
  "origen_tipo" => isset($opciones["origen_tipo"]) ? trim((string)$opciones["origen_tipo"]) : "uat_manual",
  "origen_id" => isset($opciones["origen_id"]) ? trim((string)$opciones["origen_id"]) : "",
  "id_usuario" => isset($opciones["id_usuario"]) ? intval($opciones["id_usuario"]) : 0
);

$validacion = validar_respaldo_crm_interaccion($respaldo);
$validacionDatos = validar_datos_concretos_crm_interaccion($datos);

if ($autorizar !== "CRM_CLIENTES_INTERACCION" || !$validacion["ok"] || !$validacionDatos["ok"]) {
  echo json_encode(array(
    "error" => true,
    "tipo" => "warning",
    "mensaje" => "No se creo interaccion CRM. Falta token, respaldo valido o datos concretos.",
    "depurar" => array(
      "requerido" => array(
        "autorizar" => "CRM_CLIENTES_INTERACCION",
        "respaldo" => "RUTA_O_REFERENCIA_EXTERNA",
        "cliente" => "ID_CLIENTE_CRM",
        "tipo" => "contacto|seguimiento|postventa|comercial|garantia|apartado|devolucion|calidad_datos|otro",
        "canal" => "whatsapp|telefono|correo|presencial|sistema|otro",
        "resumen" => "texto concreto"
      ),
      "validacion_respaldo" => $validacion,
      "validacion_datos" => $validacionDatos,
      "alcance" => alcance_crm_interaccion()
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

echo json_encode((new ClientesCrm())->interaccionCrearAutorizada($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_datos_concretos_crm_interaccion($datos) {
  $placeholder = false;
  foreach (array("tipo", "canal", "direccion", "resultado", "resumen", "detalle", "origen_tipo", "origen_id") as $campo) {
    $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
    if ($valor !== "" && preg_match('/\\[[A-Z_]+\\]|<[^>]+>|PENDIENTE|POR_DEFINIR/i', $valor)) {
      $placeholder = true;
    }
  }

  $bloqueos = array();
  if (intval($datos["id_cliente_crm"]) <= 0) {
    $bloqueos[] = "cliente CRM invalido o no indicado";
  }
  if (trim((string)$datos["resumen"]) === "" || strlen(trim((string)$datos["resumen"])) < 5) {
    $bloqueos[] = "resumen ausente o demasiado corto";
  }
  if ($placeholder) {
    $bloqueos[] = "se detectaron placeholders en datos de interaccion";
  }

  return array(
    "ok" => empty($bloqueos),
    "bloqueos" => $bloqueos,
    "placeholder_detectado" => $placeholder
  );
}

function alcance_crm_interaccion() {
  return array(
    "crea_interaccion" => true,
    "crea_evento_si_existe_tabla" => true,
    "modifica_cliente" => false,
    "crea_cierra_tareas" => false,
    "crea_notificaciones_sys" => false,
    "toca_pos_ventas_ecommerce_garantias_apartados_devoluciones_legacy" => false
  );
}

function validar_respaldo_crm_interaccion($respaldo) {
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
