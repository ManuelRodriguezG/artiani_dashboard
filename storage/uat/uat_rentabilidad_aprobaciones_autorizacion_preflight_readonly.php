<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$schemaDry = $esquema->planAprobacionesComerciales(false);
$preflight = $rentabilidad->preflightAprobacionesInternas(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 20));
$listado = $rentabilidad->listarAprobacionesInternas(array("q" => "TP-40372", "limite" => 30));

$sinFrase = $rentabilidad->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$conFraseSchemaPendiente = $rentabilidad->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "confirmar_autorizacion" => "AUTORIZO CREAR APROBACION INTERNA",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$resSchema = isset($schemaDry["depurar"]["resumen"]) ? $schemaDry["depurar"]["resumen"] : array();
$resPreflight = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
$resListado = isset($listado["depurar"]["resumen"]) ? $listado["depurar"]["resumen"] : array();

$ok = empty($schemaDry["error"])
    && empty($preflight["error"])
    && empty($listado["error"])
    && !empty($sinFrase["error"])
    && !empty($conFraseSchemaPendiente["error"])
    && intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0) === 2
    && intval(isset($resListado["schema_disponible"]) ? $resListado["schema_disponible"] : -1) === 0;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado" => array(
        "schema_pendiente" => intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0),
        "schema_existente" => intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0),
        "aprobaciones_creables_actuales" => intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0),
        "aprobaciones_bloqueadas_actuales" => intval(isset($resPreflight["bloqueados"]) ? $resPreflight["bloqueados"] : 0),
        "listado_schema_disponible" => intval(isset($resListado["schema_disponible"]) ? $resListado["schema_disponible"] : 0),
        "candado_sin_frase" => $sinFrase["mensaje"],
        "candado_schema_pendiente" => $conFraseSchemaPendiente["mensaje"]
    ),
    "requisitos_para_autorizar" => array(
        "respaldo_externo" => "Crear respaldo externo de BD antes de aplicar esquema.",
        "frase_esquema" => "AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS",
        "comando_aplicacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS\"",
        "validacion_posterior" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_dryrun.php",
        "uat_lifecycle" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobacion_interna_lifecycle.php"
    ),
    "reglas" => array(
        "Este preflight no ejecuta DDL y no escribe aprobaciones.",
        "Aplicar esquema no aplica precios a Catalogo, Ventas, ecommerce, Pedidos ni Mayoreo.",
        "Crear/resolver aprobaciones despues del esquema mantiene sus propios candados de frase y respaldo."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
