<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: validar el paquete de autorizacion/UAT de Almacen > Resurtido sin ejecutar DDL ni escrituras.
 * Impacto: prepara paso controlado desde modo read-only hacia DDL y primer folio RES-*.
 * Contrato: read-only; no aplica DDL, no crea solicitudes, no mueve inventario y no toca POS/ecommerce.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/AlmacenEsquema.php";

$raiz = realpath(__DIR__ . "/../..");
$archivos = array(
    "sql_resurtido" => "docs/erp_almacen_resurtido_traspasos_schema_propuesta.sql",
    "runbook" => "docs/erp_almacen_resurtido_schema_runbook_aplicacion.md",
    "reversa" => "docs/erp_almacen_resurtido_schema_plan_reversa.md",
    "solicitud_autorizacion" => "docs/erp_almacen_resurtido_schema_solicitud_autorizacion.md",
    "uat_readonly" => "storage/uat/uat_almacen_resurtido_readonly.php",
    "uat_apply_authorized" => "storage/uat/uat_almacen_resurtido_schema_apply_authorized.php",
    "uat_guardar_authorized" => "storage/uat/uat_almacen_resurtido_guardar_authorized.php",
    "uat_folio_readonly" => "storage/uat/uat_almacen_resurtido_folio_readonly.php"
);
$estadoArchivos = array();
$bloqueos = array();
$avisos = array();
foreach ($archivos as $clave => $relativo) {
    $path = realpath($raiz . DIRECTORY_SEPARATOR . $relativo);
    $ok = $path && strpos($path, $raiz) === 0 && is_file($path);
    $estadoArchivos[$clave] = array(
        "relativo" => $relativo,
        "existe" => $ok,
        "tamano_bytes" => $ok ? filesize($path) : null
    );
    if (!$ok) {
        $bloqueos[] = "Falta archivo requerido: {$relativo}";
    }
}

$esquema = new AlmacenEsquema();
$auditoria = $esquema->auditarAlmacenInventario();
$resurtidoSchema = resumenResurtidoAuditoria($auditoria);
$tablasPendientes = array();
foreach ($resurtidoSchema as $tabla => $estado) {
    if (empty($estado["existe"])) {
        $tablasPendientes[] = $tabla;
    }
}
if (empty($tablasPendientes)) {
    $avisos[] = "Las tablas de resurtido ya existen; no aplicar DDL sin revisar si el entorno ya fue migrado.";
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_autorizacion_preflight",
    "read_only" => true,
    "archivos" => $estadoArchivos,
    "schema_resurtido" => $resurtidoSchema,
    "tablas_pendientes" => $tablasPendientes,
    "tokens" => array(
        "ddl_autorizar" => "ALMACEN_RESURTIDO_DDL",
        "ddl_confirmacion" => "AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA",
        "guardar_autorizar" => "ALMACEN_RESURTIDO_GUARDAR_UAT",
        "guardar_confirmacion" => "AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA"
    ),
    "secuencia_autorizada" => array(
        "1_preflight_readonly" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_readonly.php",
        "2_aplicar_ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_schema_apply_authorized.php --autorizar=ALMACEN_RESURTIDO_DDL --confirmacion=\"AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA\" --respaldo=RUTA_O_REFERENCIA_RESPALDO",
        "3_validar_post_ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_readonly.php",
        "4_crear_folio_uat" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_guardar_authorized.php --autorizar=ALMACEN_RESURTIDO_GUARDAR_UAT --confirmacion=\"AUTORIZO UAT GUARDAR RESURTIDO usando respaldo RUTA_O_REFERENCIA\" --respaldo=RUTA_O_REFERENCIA_RESPALDO --destino=4 --origen=3",
        "5_validar_folio" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_almacen_resurtido_folio_readonly.php --folio=RES-YYYYMMDD-####"
    ),
    "guardrails" => array(
        "no_ejecuta_ddl" => true,
        "no_escribe_bd" => true,
        "no_mueve_kardex" => true,
        "no_modifica_etiquetas" => true,
        "no_toca_pos_ecommerce" => true,
        "requiere_respaldo_externo" => true
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "Paquete listo para autorizacion humana con respaldo externo; no se ejecuto nada."
        : "Resolver bloqueos antes de autorizar DDL o UAT real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function resumenResurtidoAuditoria($auditoria) {
    $objetivo = array(
        "erp_inventario_politicas_almacen_sku",
        "erp_almacen_resurtidos",
        "erp_almacen_resurtido_detalle",
        "erp_almacen_resurtido_preparacion",
        "erp_almacen_resurtido_envios",
        "erp_almacen_resurtido_recepciones",
        "erp_almacen_resurtido_diferencias"
    );
    $resultado = array();
    $tablas = valor($auditoria, array("depurar", "auditoria"), array());
    foreach ($objetivo as $tabla) {
        $item = isset($tablas[$tabla]) ? $tablas[$tabla] : array();
        $resultado[$tabla] = array(
            "existe" => !empty($item["existe"]),
            "columnas_faltantes" => count(valor($item, array("columnas_faltantes"), array())),
            "indices_faltantes" => count(valor($item, array("indices_faltantes"), array())),
            "fks_faltantes" => count(valor($item, array("fks_faltantes"), array()))
        );
    }
    return $resultado;
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}
