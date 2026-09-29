<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: runbook read-only para activar estudios persistentes de rentabilidad.
 * Impacto: ordena precheck, respaldo, aplicacion y postcheck sin ejecutar comandos.
 * Contrato: no escribe BD, no aplica DDL y no guarda estudios.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$schema = $esquema->planEstudiosRentabilidad(false);
$listado = $rentabilidad->listarEstudiosRentabilidad(array("limite" => 20));
$resSchema = isset($schema["depurar"]["resumen"]) ? $schema["depurar"]["resumen"] : array();
$schemaPendiente = !empty($listado["depurar"]["schema_pendiente"]);

$runbook = array(
    array(
        "orden" => 1,
        "fase" => "precheck",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_autorizacion_preflight_readonly.php",
        "criterio_ok" => "Debe devolver ok=true y mostrar tablas pendientes/existentes."
    ),
    array(
        "orden" => 2,
        "fase" => "respaldo",
        "comando" => "Generar respaldo externo de BD y conservar ruta/referencia.",
        "criterio_ok" => "La referencia debe estar disponible antes de ejecutar el aplicador."
    ),
    array(
        "orden" => 3,
        "fase" => "aplicar_esquema",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA ESTUDIOS RENTABILIDAD\"",
        "criterio_ok" => "Debe ejecutar 2 tablas o reportarlas existentes sin errores."
    ),
    array(
        "orden" => 4,
        "fase" => "post_schema",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_bandeja_readonly.php",
        "criterio_ok" => "Debe devolver ok=true y schema_pendiente=false cuando el esquema ya exista."
    ),
    array(
        "orden" => 5,
        "fase" => "uat_calculo",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_temporal_readonly.php",
        "criterio_ok" => "Debe seguir calculando grupos de SKUs en read-only."
    )
);

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($schema["error"]) && empty($listado["error"]),
    "modo" => "read-only",
    "estado" => $schemaPendiente ? "pendiente_autorizacion" : "schema_ya_disponible",
    "schema" => array(
        "existentes" => intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0),
        "pendientes" => intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0),
        "ejecutadas" => intval(isset($resSchema["ejecutadas"]) ? $resSchema["ejecutadas"] : 0)
    ),
    "runbook" => $runbook,
    "rollback" => array(
        "criterio" => "Si el aplicador o postcheck reporta error, no guardar estudios y restaurar desde respaldo externo.",
        "alcance" => "Rollback de BD completo o retiro controlado de tablas nuevas solo si no existen estudios productivos.",
        "validacion_despues_rollback" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_autorizacion_preflight_readonly.php"
    ),
    "restricciones" => array(
        "Este runbook no ejecuta comandos.",
        "No aplica precios, no toca Catalogo, Ventas, ecommerce, Pedidos ni Inventario.",
        "La autorizacion de esquema no autoriza cambios de precios ni alertas persistentes hacia Listas."
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
