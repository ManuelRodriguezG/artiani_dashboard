<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: preparar autorizacion read-only para DDL acotado de paquetes configurables.
 * Impacto: consulta esquema de Catalogo ERP y valida referencia de respaldo sin ejecutar DDL ni escribir BD.
 * Contrato: recibe opcionalmente --respaldo=RUTA_O_REFERENCIA y responde JSON.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/CatalogoErpEsquema.php";

$args = isset($argv) ? $argv : array();
$respaldo = "";
foreach ($args as $arg) {
  if (strpos($arg, "--respaldo=") === 0) {
    $respaldo = trim(substr($arg, 11), "\"' ");
  }
}

$esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
$existe = false;
$legible = false;
$tamano = null;
$modificado = null;

if ($respaldo !== "" && $esRutaLocal) {
  $existe = file_exists($respaldo);
  $legible = $existe && is_readable($respaldo);
  $tamano = $existe ? filesize($respaldo) : null;
  $modificado = $existe ? date("Y-m-d H:i:s", filemtime($respaldo)) : null;
}

$placeholderRespaldo = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|<ruta|ruta real)/i', $respaldo) === 1;
$okReferencia = strlen($respaldo) >= 8 && !$placeholderRespaldo;
$okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
$respaldoOk = $okReferencia && $okRuta;

$tablasPaquetes = array(
  "erp_catalogo_sku_paquetes",
  "erp_catalogo_sku_paquete_componentes",
  "erp_catalogo_sku_paquete_grupos",
  "erp_catalogo_sku_paquete_grupo_opciones"
);

$esquema = new CatalogoErpEsquema();
$auditoria = $esquema->auditarCatalogoErp();
$detalle = isset($auditoria["depurar"]["auditoria"]) ? $auditoria["depurar"]["auditoria"] : array();
$resumen = isset($auditoria["depurar"]["resumen"]) ? $auditoria["depurar"]["resumen"] : array();
$estadoTablas = array();
$faltantesDetectados = array();

foreach ($detalle as $tabla => $info) {
  $faltanColumnas = isset($info["faltantes"]["columnas"]) ? array_keys($info["faltantes"]["columnas"]) : array();
  $faltanIndices = isset($info["faltantes"]["indices"]) ? array_keys($info["faltantes"]["indices"]) : array();
  $faltanIndicesColumnas = isset($info["faltantes"]["indices_columnas"]) ? array_keys($info["faltantes"]["indices_columnas"]) : array();
  $existeTabla = !empty($info["existe"]);
  if (!$existeTabla || !empty($faltanColumnas) || !empty($faltanIndices) || !empty($faltanIndicesColumnas)) {
    $faltantesDetectados[$tabla] = array(
      "existe" => $existeTabla,
      "columnas_faltantes" => $faltanColumnas,
      "indices_faltantes" => $faltanIndices,
      "indices_columnas_distintas" => $faltanIndicesColumnas
    );
  }
}

foreach ($tablasPaquetes as $tabla) {
  $info = isset($detalle[$tabla]) ? $detalle[$tabla] : array();
  $estadoTablas[$tabla] = array(
    "existe" => !empty($info["existe"]),
    "severidad" => isset($info["severidad"]) ? $info["severidad"] : "desconocida",
    "impacto" => isset($info["impacto"]) ? $info["impacto"] : ""
  );
}

$sinFaltantes = empty($faltantesDetectados);
$faltantesEsperados = array_keys($faltantesDetectados) === $tablasPaquetes;
$resumenEsperado = intval($resumen["tablas_faltantes"] ?? -1) === 4
  && intval($resumen["columnas_faltantes"] ?? -1) === 0
  && intval($resumen["indices_faltantes"] ?? -1) === 0
  && intval($resumen["indices_con_columnas_distintas"] ?? -1) === 0;
$resumenCompleto = intval($resumen["tablas_faltantes"] ?? -1) === 0
  && intval($resumen["columnas_faltantes"] ?? -1) === 0
  && intval($resumen["indices_faltantes"] ?? -1) === 0
  && intval($resumen["indices_con_columnas_distintas"] ?? -1) === 0;
$okAuditoria = empty($auditoria["error"]);
$ok = $okAuditoria && (($faltantesEsperados && $resumenEsperado) || ($sinFaltantes && $resumenCompleto)) && $respaldoOk;

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "modulo" => "catalogo_paquetes_configurables",
  "token_requerido" => "CATALOGO_PAQUETES_CONFIGURABLES_DDL",
  "respaldo" => array(
    "referencia" => $respaldo,
    "referencia_presente" => $okReferencia,
    "placeholder_detectado" => $placeholderRespaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "modificado" => $modificado,
    "ok" => $respaldoOk
  ),
  "estado_esquema" => array(
    "auditoria_ok" => $okAuditoria,
    "resumen" => $resumen,
    "sin_faltantes" => $sinFaltantes,
    "faltantes_esperados" => $faltantesEsperados,
    "resumen_esperado" => $resumenEsperado,
    "resumen_completo" => $resumenCompleto,
    "tablas_paquetes" => $estadoTablas,
    "faltantes_detectados" => $faltantesDetectados
  ),
  "documentos" => array(
    "solicitud" => "docs/erp_catalogo_paquetes_configurables_solicitud_autorizacion.md",
    "runbook" => "docs/erp_catalogo_paquetes_runbook_aplicacion.md",
    "ddl_acotado" => "docs/erp_catalogo_paquetes_configurables_ddl_acotado.sql",
    "avance" => "docs/erp_catalogo_avance.md"
  ),
  "siguiente_paso" => $ok
    ? ($sinFaltantes
      ? "Paquetes configurables ya no tienen pendientes de esquema; continuar con prueba funcional de Catalogo."
      : "Puede solicitarse autorizacion con token CATALOGO_PAQUETES_CONFIGURABLES_DDL y ejecutar solo el DDL acotado de paquetes.")
    : "No autorizar todavia: confirma respaldo externo real y que los faltantes sean exactamente las 4 tablas de paquetes.",
  "reglas" => array(
    "Este preflight no ejecuta DDL.",
    "Este preflight no crea paquetes ni migra legacy.",
    "No agregar columnas de recepcion variable en este apply.",
    "No tocar Ventas, Almacen, Inventario, Compras ni ecommerce."
  )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
