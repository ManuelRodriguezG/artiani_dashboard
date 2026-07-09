<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: crear programa CRM Recompensas solo con token y respaldo externo.
 * Impacto: inserta un programa en crm_recompensas_programas.
 * Contrato: no crea cuentas, movimientos ni puntos; bloqueado por defecto.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("autorizar::", "respaldo::", "codigo::", "nombre::", "tipo::", "estatus::", "reglas::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_recompensas($respaldo);

if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_PROGRAMA" || !$validacion["ok"]) {
  echo json_encode(array(
    "error" => true,
    "tipo" => "warning",
    "mensaje" => "No se creo programa CRM Recompensas. Falta token o respaldo valido.",
    "depurar" => array(
      "requerido" => array(
        "autorizar" => "CRM_CLIENTES_RECOMPENSAS_PROGRAMA",
        "respaldo" => "RUTA_O_REFERENCIA"
      ),
      "validacion_respaldo" => $validacion,
      "alcance" => array(
        "crea_programa" => true,
        "crea_cuentas" => false,
        "crea_movimientos" => false,
        "otorga_puntos" => false,
        "toca_pos_ventas_ecommerce_legacy" => false
      )
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$datos = array(
  "codigo" => isset($opciones["codigo"]) ? trim((string)$opciones["codigo"]) : "",
  "nombre" => isset($opciones["nombre"]) ? trim((string)$opciones["nombre"]) : "",
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "puntos",
  "estatus" => isset($opciones["estatus"]) ? trim((string)$opciones["estatus"]) : "activo",
  "reglas" => isset($opciones["reglas"]) ? (string)$opciones["reglas"] : ""
);

echo json_encode((new ClientesCrm())->recompensaProgramaCrearAutorizado($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_respaldo_crm_recompensas($respaldo) {
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
