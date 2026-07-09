<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: crear cuenta CRM Recompensas solo con token y respaldo externo.
 * Impacto: inserta una cuenta con saldo cero para cliente/programa.
 * Contrato: no crea movimientos ni puntos; bloqueado por defecto.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("autorizar::", "respaldo::", "cliente::", "programa::", "nivel::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_recompensas_cuenta($respaldo);

if ($autorizar !== "CRM_CLIENTES_RECOMPENSAS_CUENTA" || !$validacion["ok"]) {
  echo json_encode(array(
    "error" => true,
    "tipo" => "warning",
    "mensaje" => "No se creo cuenta CRM Recompensas. Falta token o respaldo valido.",
    "depurar" => array(
      "requerido" => array(
        "autorizar" => "CRM_CLIENTES_RECOMPENSAS_CUENTA",
        "respaldo" => "RUTA_O_REFERENCIA"
      ),
      "validacion_respaldo" => $validacion,
      "alcance" => array(
        "crea_cuenta_saldo_cero" => true,
        "crea_movimientos" => false,
        "otorga_puntos" => false,
        "redime_puntos" => false,
        "toca_pos_ventas_ecommerce_legacy" => false
      )
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit(1);
}

$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0,
  "id_programa_recompensa" => isset($opciones["programa"]) ? intval($opciones["programa"]) : 0,
  "nivel" => isset($opciones["nivel"]) ? trim((string)$opciones["nivel"]) : ""
);

echo json_encode((new ClientesCrm())->recompensaCuentaCrearAutorizado($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_respaldo_crm_recompensas_cuenta($respaldo) {
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
