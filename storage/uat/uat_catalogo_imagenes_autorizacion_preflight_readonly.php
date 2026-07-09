<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: preparar autorizacion read-only para DDL de imagenes de marcas/categorias de Catalogo.
 * Impacto: consulta esquema y valida referencia de respaldo sin ejecutar DDL ni escribir BD.
 * Contrato: opcionalmente recibe --respaldo=RUTA_O_REFERENCIA y siempre responde JSON.
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

$esquema = new CatalogoErpEsquema();
$auditoria = $esquema->auditarCatalogoErp();
$plan = $esquema->planActualizarCatalogoErp(false);
$pasosPlan = isset($plan["depurar"]) && is_array($plan["depurar"]) ? $plan["depurar"] : (is_array($plan) ? $plan : array());
$tablasImagenes = array("erp_catalogo_marca_imagenes", "erp_catalogo_categoria_imagenes");
$estadoTablas = array();
$pendientesImagenes = 0;

foreach ($tablasImagenes as $tabla) {
  $detalle = isset($auditoria["depurar"]["auditoria"][$tabla]) ? $auditoria["depurar"]["auditoria"][$tabla] : array();
  $existeTabla = !empty($detalle["existe"]);
  $estadoTablas[$tabla] = array(
    "existe" => $existeTabla,
    "severidad" => isset($detalle["severidad"]) ? $detalle["severidad"] : "desconocida",
    "columnas_faltantes" => isset($detalle["faltantes"]["columnas"]) ? array_keys($detalle["faltantes"]["columnas"]) : array(),
    "indices_faltantes" => isset($detalle["faltantes"]["indices"]) ? array_keys($detalle["faltantes"]["indices"]) : array()
  );
  if (!$existeTabla || !empty($estadoTablas[$tabla]["columnas_faltantes"]) || !empty($estadoTablas[$tabla]["indices_faltantes"])) {
    $pendientesImagenes++;
  }
}

$ddlImagenes = array();
foreach ($pasosPlan as $paso) {
  $depurar = isset($paso["depurar"]) ? $paso["depurar"] : array();
  $tabla = isset($depurar["tabla"]) ? $depurar["tabla"] : "";
  $sql = isset($depurar["sql"]) ? $depurar["sql"] : "";
  if ($tabla === "" && $sql !== "") {
    foreach ($tablasImagenes as $tablaImagen) {
      if (strpos($sql, "`" . $tablaImagen . "`") !== false || strpos($sql, $tablaImagen) !== false) {
        $tabla = $tablaImagen;
        break;
      }
    }
  }
  if (in_array($tabla, $tablasImagenes, true)) {
    $ddlImagenes[] = array(
      "tabla" => $tabla,
      "tipo" => isset($paso["tipo"]) ? $paso["tipo"] : "",
      "mensaje" => isset($paso["mensaje"]) ? $paso["mensaje"] : "",
      "ejecutado" => isset($depurar["ejecutado"]) ? $depurar["ejecutado"] : false,
      "sql" => $sql !== "" ? $sql : null
    );
  }
}

if (empty($ddlImagenes) && $pendientesImagenes > 0) {
  foreach ($tablasImagenes as $tabla) {
    $ddlImagenes[] = array(
      "tabla" => $tabla,
      "tipo" => "warning",
      "mensaje" => "La auditoria marca la tabla como pendiente; el SQL no fue detectado en el plan filtrado.",
      "ejecutado" => false,
      "sql" => null
    );
  }
}

$okAuditoria = empty($auditoria["error"]);
$okPlan = empty($plan["error"]) && is_array($pasosPlan);
$ok = $okAuditoria && $okPlan && $respaldoOk;

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "modulo" => "catalogo_imagenes_marcas_categorias",
  "token_requerido" => "CATALOGO_IMAGENES_MARCAS_CATEGORIAS",
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
    "plan_ok" => $okPlan,
    "resumen_auditoria" => isset($auditoria["depurar"]["resumen"]) ? $auditoria["depurar"]["resumen"] : array(),
    "pendientes_imagenes" => $pendientesImagenes,
    "tablas" => $estadoTablas,
    "ddl_plan_imagenes" => $ddlImagenes
  ),
  "documentos" => array(
    "solicitud" => "docs/erp_catalogo_imagenes_marcas_categorias_solicitud_autorizacion.md",
    "runbook" => "docs/erp_catalogo_imagenes_marcas_categorias_runbook_aplicacion.md",
    "avance" => "docs/erp_catalogo_avance.md"
  ),
  "siguiente_paso" => $ok
    ? ($pendientesImagenes > 0
      ? "La autorizacion puede usar el token y respaldo indicados; aplicar DDL solo con storage/uat/uat_catalogo_imagenes_schema_apply_authorized.php."
      : "Imagenes de marcas/categorias ya no tienen pendientes de esquema; continuar con prueba funcional de CRUD.")
    : "Completa respaldo externo valido y revisa que auditoria/plan de Catalogo no reporten errores antes de autorizar.",
  "reglas" => array(
    "Este preflight no ejecuta DDL.",
    "Este preflight no crea registros ni archivos.",
    "Aplicar el DDL no migra imagenes historicas ni toca Ventas, Compras, Almacen o Inventario.",
    "No usar el endpoint general de esquema para este alcance porque tambien contiene paquetes pendientes."
  )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
