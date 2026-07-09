<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: lista pendientes de esquema de Catalogo ERP antes de cualquier DDL.
 * Impacto: Catalogo ERP; evita aplicar un plan completo creyendo que solo contiene imagenes.
 * Contrato: solo lectura; no ejecuta planActualizarCatalogoErp(true).
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/CatalogoErpEsquema.php";

$esquema = new CatalogoErpEsquema();
$auditoria = $esquema->auditarCatalogoErp();
$detalle = isset($auditoria["depurar"]["auditoria"]) ? $auditoria["depurar"]["auditoria"] : array();
$faltantes = array();
foreach ($detalle as $tabla => $info) {
  if (empty($info["existe"])) {
    $faltantes[$tabla] = array(
      "severidad" => isset($info["severidad"]) ? $info["severidad"] : "",
      "impacto" => isset($info["impacto"]) ? $info["impacto"] : ""
    );
    continue;
  }
  $faltas = isset($info["faltantes"]) ? $info["faltantes"] : array();
  if (!empty($faltas["columnas"]) || !empty($faltas["indices"]) || !empty($faltas["indices_columnas"])) {
    $faltantes[$tabla] = array(
      "severidad" => isset($info["severidad"]) ? $info["severidad"] : "",
      "columnas_faltantes" => isset($faltas["columnas"]) ? array_keys($faltas["columnas"]) : array(),
      "indices_faltantes" => isset($faltas["indices"]) ? array_keys($faltas["indices"]) : array(),
      "indices_columnas" => isset($faltas["indices_columnas"]) ? array_keys($faltas["indices_columnas"]) : array()
    );
  }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
  "resumen" => isset($auditoria["depurar"]["resumen"]) ? $auditoria["depurar"]["resumen"] : array(),
  "faltantes" => $faltantes,
  "nota" => "Solo lectura. Usar para decidir si se requiere DDL completo o DDL acotado."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
