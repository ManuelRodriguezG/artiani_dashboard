<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-28
 * Proposito: preparar paquete read-only de autorizacion para esquema de estudios de rentabilidad.
 * Impacto: muestra pendientes, candados y comando futuro sin ejecutar DDL.
 * Contrato: solo lectura; no aplica esquema y no guarda estudios.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";
require_once "../app/modelos/RentabilidadErp.php";

$esquema = new RentabilidadEsquema();
$rentabilidad = new RentabilidadErp();

$schemaDry = $esquema->planEstudiosRentabilidad(false);
$listado = $rentabilidad->listarEstudiosRentabilidad(array("limite" => 20));
$sinFrase = $rentabilidad->guardarEstudioRentabilidad(array(
    "nombre" => "UAT sin autorizacion",
    "id_lista_precio" => 3,
    "ids_sku" => "1",
    "respaldo_externo_ref" => "uat-readonly-respaldo"
), 0);

$resSchema = isset($schemaDry["depurar"]["resumen"]) ? $schemaDry["depurar"]["resumen"] : array();
$resListado = isset($listado["depurar"]["resumen"]) ? $listado["depurar"]["resumen"] : array();

$ok = empty($schemaDry["error"])
    && empty($listado["error"])
    && !empty($sinFrase["error"])
    && intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0) >= 0
    && array_key_exists("schema_pendiente", isset($listado["depurar"]) ? $listado["depurar"] : array());

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado" => array(
        "schema_pendiente" => !empty($listado["depurar"]["schema_pendiente"]),
        "tablas_pendientes" => intval(isset($resSchema["pendientes"]) ? $resSchema["pendientes"] : 0),
        "tablas_existentes" => intval(isset($resSchema["existentes"]) ? $resSchema["existentes"] : 0),
        "estudios_listados" => intval(isset($resListado["total"]) ? $resListado["total"] : 0),
        "candado_sin_frase" => $sinFrase["mensaje"]
    ),
    "requisitos_para_autorizar" => array(
        "respaldo_externo" => "Crear respaldo externo de BD en C:\\xampp\\panel_db_backups o referencia equivalente antes de aplicar esquema.",
        "frase_esquema" => "AUTORIZO APLICAR ESQUEMA ESTUDIOS RENTABILIDAD",
        "comando_aplicacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_schema_apply_authorized.php --execute --respaldo=RUTA_O_REFERENCIA --confirmar=\"AUTORIZO APLICAR ESQUEMA ESTUDIOS RENTABILIDAD\"",
        "validacion_posterior" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_rentabilidad_estudios_bandeja_readonly.php"
    ),
    "reglas" => array(
        "Este preflight no ejecuta DDL.",
        "Aplicar esquema no guarda estudios automaticamente.",
        "Guardar estudios despues del esquema mantiene permiso rentabilidad.snapshot, CSRF, respaldo y frase propia."
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
