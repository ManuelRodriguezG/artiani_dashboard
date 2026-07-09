<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$schema = $esquema->planAprobacionesComerciales(false);
$paquete = $rentabilidad->paqueteAutorizacionAprobaciones(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 20));
$preflight = $rentabilidad->preflightAprobacionesInternas(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 20));
$listado = $rentabilidad->listarAprobacionesInternas(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 30));

$writeGateCrear = $rentabilidad->guardarAprobacionInterna(array(
    "sku" => "TP-40372",
    "canal" => "menudeo",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$resSchema = isset($schema["depurar"]["resumen"]) ? $schema["depurar"]["resumen"] : array();
$resPaquete = isset($paquete["depurar"]["resumen"]) ? $paquete["depurar"]["resumen"] : array();
$resPreflight = isset($preflight["depurar"]["resumen"]) ? $preflight["depurar"]["resumen"] : array();
$resListado = isset($listado["depurar"]["resumen"]) ? $listado["depurar"]["resumen"] : array();

$schemaDisponible = intval(isset($resPaquete["schema_disponible"]) ? $resPaquete["schema_disponible"] : 0);
$pendientes = intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0);
$existentes = intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0);
$estado = $schemaDisponible ? "schema_aplicado_validado_readonly" : "pendiente_autorizacion_esquema";

$ok = empty($schema["error"])
    && empty($paquete["error"])
    && empty($preflight["error"])
    && empty($listado["error"])
    && !empty($writeGateCrear["error"])
    && ($schemaDisponible ? ($existentes >= 2 && $pendientes === 0) : ($pendientes >= 1));

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado" => $estado,
    "schema" => array(
        "disponible" => $schemaDisponible,
        "existentes" => $existentes,
        "pendientes" => $pendientes,
        "errores" => intval(isset($resSchema["errores"]) ? $resSchema["errores"] : 0)
    ),
    "paquete" => array(
        "estado" => isset($resPaquete["estado"]) ? $resPaquete["estado"] : "",
        "creables" => intval(isset($resPaquete["aprobaciones_creables"]) ? $resPaquete["aprobaciones_creables"] : 0),
        "bloqueadas" => intval(isset($resPaquete["aprobaciones_bloqueadas"]) ? $resPaquete["aprobaciones_bloqueadas"] : 0),
        "uso_comercial" => isset($resPaquete["uso_comercial"]) ? $resPaquete["uso_comercial"] : ""
    ),
    "preflight" => array(
        "evaluados" => intval(isset($resPreflight["evaluados"]) ? $resPreflight["evaluados"] : 0),
        "schema_disponible" => intval(isset($resPreflight["schema_disponible"]) ? $resPreflight["schema_disponible"] : 0),
        "creables" => intval(isset($resPreflight["creables"]) ? $resPreflight["creables"] : 0),
        "bloqueados" => intval(isset($resPreflight["bloqueados"]) ? $resPreflight["bloqueados"] : 0)
    ),
    "listado" => array(
        "schema_disponible" => intval(isset($resListado["schema_disponible"]) ? $resListado["schema_disponible"] : 0),
        "total" => intval(isset($resListado["total"]) ? $resListado["total"] : 0)
    ),
    "candado_escritura" => array(
        "error" => !empty($writeGateCrear["error"]),
        "mensaje" => isset($writeGateCrear["mensaje"]) ? $writeGateCrear["mensaje"] : ""
    ),
    "reglas" => array(
        "Este UAT no ejecuta DDL y no crea aprobaciones.",
        "Antes del esquema debe quedar en pendiente_autorizacion_esquema.",
        "Despues del esquema debe quedar en schema_aplicado_validado_readonly antes de crear aprobaciones reales."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

