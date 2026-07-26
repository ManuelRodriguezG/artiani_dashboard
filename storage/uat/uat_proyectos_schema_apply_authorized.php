<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: aplicar DDL Proyectos solo con autorizacion explicita.
 * Impacto: crea tablas `erp_proyecto*` vacias; no crea proyectos ni tareas reales.
 * Contrato: bloqueado por defecto; requiere --autorizar=PROYECTOS_DDL_BASE y --respaldo valido.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_proyectos_schema($respaldo);

if ($autorizar !== "PROYECTOS_DDL_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL Proyectos. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "PROYECTOS_DDL_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_tablas_erp_proyecto" => true,
      "crea_proyectos_reales" => false,
      "crea_tareas_reales" => false,
      "importa_pendientes_modulos" => false,
      "sincroniza_permisos" => false,
      "toca_ventas" => false,
      "toca_inventario" => false,
      "toca_compras" => false,
      "toca_catalogo" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/ProyectosEsquema.php";

$modelo = new ProyectosEsquema();
$respuesta = $modelo->planActualizarProyectosErp(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "proyectos_schema_apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "validacion_respaldo" => $validacion,
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_respaldo_proyectos_schema($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldo_placeholder_proyectos_schema($respaldo);
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

function respaldo_placeholder_proyectos_schema($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
