<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: aplicar DDL acotado de Catalogo > Catalogos comerciales persistentes solo con autorizacion explicita.
 * Impacto: crea tablas para guardar catalogos comerciales, items y eventos; no publica enlaces ni genera exportaciones.
 * Contrato: BLOQUEADO por defecto; requiere token CATALOGO_COMERCIAL_PERSISTENCIA_DDL, confirmacion exacta y respaldo externo valido.
 */

$opciones = getopt("", array("autorizar::", "confirmacion::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$confirmacion = isset($opciones["confirmacion"]) ? trim((string) $opciones["confirmacion"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$token = "CATALOGO_COMERCIAL_PERSISTENCIA_DDL";
$frase = "APLICAR CATALOGOS COMERCIALES";
$validacionRespaldo = validarRespaldoCatalogoComercial($respaldo);

if ($autorizar !== $token || $confirmacion !== $frase || !$validacionRespaldo["ok"]) {
  responder(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se aplico DDL de Catalogos comerciales. Falta token, confirmacion exacta o respaldo externo valido.",
    "requerido" => array(
      "--autorizar=" . $token,
      "--confirmacion=\"" . $frase . "\"",
      "--respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_YYYYMMDD_antes_catalogos_comerciales.sql"
    ),
    "validacion_respaldo" => $validacionRespaldo,
    "alcance" => contratoCatalogoComercial(false)
  ), 1);
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/CatalogoErpEsquema.php";

$esquema = new CatalogoErpEsquema();
$antes = $esquema->auditarCatalogoErp();
$plan = $esquema->planActualizarCatalogosComerciales(true);
$despues = $esquema->auditarCatalogoErp();

responder(array(
  "ok" => !$plan["error"],
  "modo" => "catalogo_comercial_persistencia_schema_apply_authorized",
  "respaldo_ref" => $respaldo,
  "validacion_respaldo" => $validacionRespaldo,
  "auditoria_antes" => resumenCatalogoComercial($antes),
  "plan" => filtrarPlanCatalogoComercial($plan),
  "auditoria_despues" => resumenCatalogoComercial($despues),
  "alcance" => contratoCatalogoComercial(true),
  "siguiente_paso" => "Implementar modelo/endpoints de guardado real y mantener import/export JSON como herramienta auxiliar."
), $plan["error"] ? 1 : 0);

function validarRespaldoCatalogoComercial($respaldo) {
  $respaldo = trim((string) $respaldo);
  $normalizado = str_replace("/", "\\", strtolower($respaldo));
  $proyecto = strtolower(realpath(__DIR__ . "/../.."));
  $proyecto = $proyecto ? str_replace("/", "\\", $proyecto) : "";
  $esRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;

  if ($respaldo === "") {
    return array("ok" => false, "tipo" => "vacio", "mensaje" => "Indica una ruta o referencia de respaldo externo.");
  }

  if ($proyecto !== "" && strpos($normalizado, $proyecto) === 0) {
    return array("ok" => false, "tipo" => "interno", "mensaje" => "El respaldo debe estar fuera del proyecto.", "ruta" => $respaldo);
  }

  if ($esRuta) {
    return array(
      "ok" => is_file($respaldo) && is_readable($respaldo) && filesize($respaldo) > 0,
      "tipo" => "archivo",
      "ruta" => $respaldo,
      "existe" => is_file($respaldo),
      "legible" => is_file($respaldo) && is_readable($respaldo),
      "tamano" => is_file($respaldo) ? filesize($respaldo) : null
    );
  }

  return array(
    "ok" => strlen($respaldo) >= 8,
    "tipo" => "referencia",
    "referencia" => $respaldo
  );
}

function contratoCatalogoComercial($aplica) {
  return array(
    "aplica_ddl" => $aplica,
    "crea_tablas" => array(
      "erp_catalogo_comercial_catalogos",
      "erp_catalogo_comercial_items",
      "erp_catalogo_comercial_eventos"
    ),
    "no_publica_enlaces" => true,
    "no_genera_exportaciones" => true,
    "no_toca_ventas" => true,
    "no_toca_inventario" => true,
    "no_toca_ecommerce" => true,
    "no_migra_borradores_locales" => true
  );
}

function resumenCatalogoComercial($auditoria) {
  $objetivo = contratoCatalogoComercial(false)["crea_tablas"];
  $tablas = isset($auditoria["depurar"]["auditoria"]) ? $auditoria["depurar"]["auditoria"] : array();
  $resumen = array();
  foreach ($objetivo as $tabla) {
    $item = isset($tablas[$tabla]) ? $tablas[$tabla] : array();
    $faltantes = isset($item["faltantes"]) ? $item["faltantes"] : array();
    $resumen[$tabla] = array(
      "existe" => !empty($item["existe"]),
      "columnas_faltantes" => isset($faltantes["columnas"]) ? count($faltantes["columnas"]) : null,
      "indices_faltantes" => isset($faltantes["indices"]) ? count($faltantes["indices"]) : null
    );
  }
  return $resumen;
}

function filtrarPlanCatalogoComercial($plan) {
  $objetivo = contratoCatalogoComercial(false)["crea_tablas"];
  $pasos = isset($plan["depurar"]) && is_array($plan["depurar"]) ? $plan["depurar"] : array();
  $filtrados = array();
  foreach ($pasos as $paso) {
    $texto = json_encode($paso);
    foreach ($objetivo as $tabla) {
      if (strpos($texto, $tabla) !== false) {
        $filtrados[] = $paso;
        break;
      }
    }
  }
  return array(
    "error" => !empty($plan["error"]),
    "mensaje" => isset($plan["mensaje"]) ? $plan["mensaje"] : "",
    "pasos_catalogo_comercial" => $filtrados
  );
}

function responder($datos, $codigoSalida) {
  echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($codigoSalida);
}
