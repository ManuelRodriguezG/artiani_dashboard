<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$filtros = array("q" => "TP-40372", "canal" => "menudeo", "limite" => 20);

$schemaDry = $esquema->planAprobacionesComerciales(false);
$paquete = $rentabilidad->paqueteAutorizacionAprobaciones($filtros);
$postSchema = array();
$preflight = $rentabilidad->preflightAprobacionesInternas($filtros);
$listado = $rentabilidad->listarAprobacionesInternas($filtros);
$sinFraseCrear = $rentabilidad->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);
$schemaPendienteCrear = $rentabilidad->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "confirmar_autorizacion" => "AUTORIZO CREAR APROBACION INTERNA",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$resSchema = isset($schemaDry["depurar"]["resumen"]) ? $schemaDry["depurar"]["resumen"] : array();
$resPaquete = isset($paquete["depurar"]["resumen"]) ? $paquete["depurar"]["resumen"] : array();
$resPreflight = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
$resListado = isset($listado["depurar"]["resumen"]) ? $listado["depurar"]["resumen"] : array();

$schemaDisponible = intval(isset($resPaquete["schema_disponible"]) ? $resPaquete["schema_disponible"] : 0);
$schemaPendiente = intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0);
$schemaExistente = intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0);

$postSchema["estado_esperado_actual"] = $schemaDisponible ? "schema_aplicado_validado_readonly" : "pendiente_autorizacion_esquema";
$postSchema["comando_validacion"] = "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_post_schema_readonly.php";

$checks = array(
    "schema_dryrun" => array(
        "ok" => empty($schemaDry["error"]) && intval(isset($resSchema["total"]) ? $resSchema["total"] : 0) === 2,
        "estado" => $schemaDisponible ? "aplicado" : "pendiente",
        "detalle" => "existentes " . $schemaExistente . ", pendientes " . $schemaPendiente
    ),
    "paquete_autorizacion" => array(
        "ok" => empty($paquete["error"]) && isset($resPaquete["estado"]),
        "estado" => isset($resPaquete["estado"]) ? $resPaquete["estado"] : "",
        "detalle" => "creables " . intval(isset($resPaquete["aprobaciones_creables"]) ? $resPaquete["aprobaciones_creables"] : 0)
            . ", bloqueadas " . intval(isset($resPaquete["aprobaciones_bloqueadas"]) ? $resPaquete["aprobaciones_bloqueadas"] : 0)
    ),
    "preflight_funcional" => array(
        "ok" => empty($preflight["error"]) && intval(isset($resPreflight["evaluados"]) ? $resPreflight["evaluados"] : 0) > 0,
        "estado" => $schemaDisponible ? "schema_disponible" : "schema_pendiente",
        "detalle" => "evaluados " . intval(isset($resPreflight["evaluados"]) ? $resPreflight["evaluados"] : 0)
            . ", creables " . intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0)
    ),
    "listado_persistente" => array(
        "ok" => empty($listado["error"]),
        "estado" => $schemaDisponible ? "consultable" : "pendiente_schema",
        "detalle" => "schema_disponible " . intval(isset($resListado["schema_disponible"]) ? $resListado["schema_disponible"] : 0)
            . ", total " . intval(isset($resListado["total"]) ? $resListado["total"] : 0)
    ),
    "candados_escritura" => array(
        "ok" => !empty($sinFraseCrear["error"]) && !empty($schemaPendienteCrear["error"]),
        "estado" => "activos",
        "detalle" => "sin frase: " . (isset($sinFraseCrear["mensaje"]) ? $sinFraseCrear["mensaje"] : "")
    )
);

$ok = true;
foreach ($checks as $check) {
    if (empty($check["ok"])) {
        $ok = false;
    }
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado_general" => $schemaDisponible ? "validar_post_schema_y_lifecycle" : "listo_para_solicitar_autorizacion",
    "checks" => $checks,
    "resumen" => array(
        "schema_disponible" => $schemaDisponible,
        "schema_existente" => $schemaExistente,
        "schema_pendiente" => $schemaPendiente,
        "paquete_estado" => isset($resPaquete["estado"]) ? $resPaquete["estado"] : "",
        "uso_comercial" => isset($resPaquete["uso_comercial"]) ? $resPaquete["uso_comercial"] : "",
        "post_schema_estado_esperado_actual" => $postSchema["estado_esperado_actual"]
    ),
    "autorizacion_requerida" => array(
        "respaldo_externo" => "Obligatorio antes de ejecutar DDL.",
        "frase" => "AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS\""
    ),
    "validacion_posterior" => array(
        $postSchema["comando_validacion"],
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobacion_interna_lifecycle.php",
        "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_suite_readonly.php"
    ),
    "reglas" => array(
        "Esta suite no ejecuta DDL y no crea aprobaciones.",
        "El resultado listo_para_solicitar_autorizacion no reemplaza respaldo externo ni frase exacta.",
        "Despues del esquema, crear/resolver aprobaciones conserva sus propios candados."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

