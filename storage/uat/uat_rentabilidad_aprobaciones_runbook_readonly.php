<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$schema = $esquema->planAprobacionesComerciales(false);
$suite = $rentabilidad->paqueteAutorizacionAprobaciones(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 20));
$resSchema = isset($schema["depurar"]["resumen"]) ? $schema["depurar"]["resumen"] : array();
$resSuite = isset($suite["depurar"]["resumen"]) ? $suite["depurar"]["resumen"] : array();

$pendientes = intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0);
$existentes = intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0);
$schemaDisponible = intval(isset($resSuite["schema_disponible"]) ? $resSuite["schema_disponible"] : 0);
$estado = $schemaDisponible ? "schema_ya_disponible" : "pendiente_autorizacion";

$runbook = array(
    array(
        "orden" => 1,
        "fase" => "precheck",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php",
        "criterio_ok" => "Debe devolver ok=true y estado_general=listo_para_solicitar_autorizacion."
    ),
    array(
        "orden" => 2,
        "fase" => "respaldo",
        "comando" => "Generar respaldo externo de BD y conservar ruta/referencia.",
        "criterio_ok" => "La referencia debe ser externa al cambio y quedar disponible para rollback."
    ),
    array(
        "orden" => 3,
        "fase" => "validar_respaldo",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_respaldo_preflight_readonly.php --respaldo=\"RUTA_O_REFERENCIA\"",
        "criterio_ok" => "Debe devolver ok=true antes de ejecutar el aplicador."
    ),
    array(
        "orden" => 4,
        "fase" => "aplicar_esquema",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA APROBACIONES INTERNAS\"",
        "criterio_ok" => "Debe ejecutar 2 tablas o reportarlas existentes sin errores."
    ),
    array(
        "orden" => 5,
        "fase" => "post_schema",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_post_schema_readonly.php",
        "criterio_ok" => "Debe devolver schema_aplicado_validado_readonly."
    ),
    array(
        "orden" => 6,
        "fase" => "lifecycle_readonly",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobacion_interna_lifecycle.php",
        "criterio_ok" => "Debe consultar preflight/listado sin crear aprobaciones."
    ),
    array(
        "orden" => 7,
        "fase" => "suite_general",
        "comando" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_suite_readonly.php",
        "criterio_ok" => "Debe devolver ok=true y conservar uso_comercial bloqueado hasta liberar fiscal/comercial."
    )
);

$ok = empty($schema["error"])
    && empty($suite["error"])
    && count($runbook) === 7
    && ($schemaDisponible || $pendientes >= 1);

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado" => $estado,
    "schema" => array(
        "disponible" => $schemaDisponible,
        "existentes" => $existentes,
        "pendientes" => $pendientes
    ),
    "runbook" => $runbook,
    "rollback" => array(
        "criterio" => "Si el aplicador o post-schema reporta error, no crear aprobaciones y restaurar desde respaldo externo.",
        "alcance" => "Rollback de BD completo o retiro controlado de tablas nuevas solo si no existen registros productivos.",
        "validacion_despues_rollback" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_aprobaciones_autorizacion_suite_readonly.php"
    ),
    "restricciones" => array(
        "Este runbook no ejecuta comandos y no escribe BD.",
        "No aplica precios, no toca Catalogo, Ventas, ecommerce, Pedidos, Mayoreo ni Inventario.",
        "La autorizacion de esquema no autoriza crear o resolver aprobaciones reales."
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
