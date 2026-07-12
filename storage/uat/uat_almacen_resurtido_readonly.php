<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: prevalidar Almacen > Resurtido antes de aplicar DDL o habilitar escrituras.
 * Impacto: revisa esquema, endpoints de modelo read-only, vista/JS y guardrails de autorizacion.
 * Contrato: no ejecuta DDL, no crea solicitudes, no mueve inventario y no modifica stock.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/Almacenes.php";
require_once "../app/modelos/AlmacenEsquema.php";

$almacenes = new Almacenes();
$esquema = new AlmacenEsquema();

$auditoria = $esquema->auditarAlmacenInventario();
$plan = $esquema->planActualizarAlmacenInventario(false);
$listado = $almacenes->consultar_resurtidos_readonly(array());
$detalle = $almacenes->consultar_resurtido_readonly(array("id_resurtido_almacen" => 1));
$almacenPreflight = seleccionarAlmacenPreflight($almacenes);
$stockBajo = $almacenPreflight
    ? $almacenes->preflight_stock_bajo_resurtido(array("id_almacen" => $almacenPreflight["id_almacen"], "solo_bajos" => 1))
    : null;

$bloqueos = array();
$avisos = array();

if (!archivoExiste("../app/vistas/paginas/apps/erp/almacen/resurtido.php")) {
    $bloqueos[] = "Falta vista app/vistas/paginas/apps/erp/almacen/resurtido.php";
}
if (!archivoExiste("../public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js")) {
    $bloqueos[] = "Falta JS public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js";
}
if (!empty($listado["error"])) {
    $bloqueos[] = "Listado read-only devolvio error: " . valor($listado, array("mensaje"), "sin mensaje");
}
if (!empty($detalle["error"]) && valor($detalle, array("tipo"), "") !== "warning") {
    $bloqueos[] = "Detalle read-only devolvio error inesperado: " . valor($detalle, array("mensaje"), "sin mensaje");
}
if ($stockBajo !== null && !empty($stockBajo["error"])) {
    $avisos[] = "Stock bajo read-only devolvio aviso/error: " . valor($stockBajo, array("mensaje"), "sin mensaje");
}
if ($stockBajo === null) {
    $avisos[] = "No se encontro almacen activo para preflight de stock bajo";
}

$resurtidoSchema = resumenResurtidoAuditoria($auditoria);
foreach ($resurtidoSchema as $tabla => $estado) {
    if (empty($estado["existe"])) {
        $avisos[] = "Tabla pendiente: {$tabla}";
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_resurtido_readonly",
    "read_only" => true,
    "schema" => array(
        "resurtido" => $resurtidoSchema,
        "plan_sin_ejecutar_total" => count($plan),
        "auditoria_tipo" => valor($auditoria, array("tipo"), null),
        "auditoria_pendientes" => valor($auditoria, array("depurar", "tiene_pendientes"), null)
    ),
    "modulo" => array(
        "vista_existe" => archivoExiste("../app/vistas/paginas/apps/erp/almacen/resurtido.php"),
        "js_existe" => archivoExiste("../public/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js"),
        "listado" => resumenRespuesta($listado),
        "detalle" => resumenRespuesta($detalle),
        "stock_bajo" => $stockBajo === null ? null : resumenRespuesta($stockBajo),
        "almacen_preflight" => $almacenPreflight
    ),
    "guardrails" => array(
        "requiere_respaldo_externo_para_ddl" => true,
        "requiere_autorizacion_textual" => true,
        "token_autorizacion_ddl" => "AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA",
        "no_escribe_bd" => true,
        "no_mueve_kardex" => true,
        "no_modifica_etiquetas" => true,
        "no_toca_pos_ecommerce" => true
    ),
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "siguiente_paso" => empty($bloqueos)
        ? "Listo para revisar UI/read-only; DDL sigue bloqueado hasta respaldo externo y autorizacion textual."
        : "Resolver bloqueos antes de solicitar autorizacion de DDL."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function seleccionarAlmacenPreflight($almacenes) {
    $respuesta = $almacenes->obtener_almacenes(array("permite_venta" => 1));
    $items = empty($respuesta["error"]) && is_array(valor($respuesta, array("depurar"), null))
        ? $respuesta["depurar"]
        : array();
    if (empty($items)) {
        $respuesta = $almacenes->obtener_almacenes(array());
        $items = empty($respuesta["error"]) && is_array(valor($respuesta, array("depurar"), null))
            ? $respuesta["depurar"]
            : array();
    }
    return empty($items) ? null : array(
        "id_almacen" => intval($items[0]["id_almacen"]),
        "almacen" => $items[0]["almacen"]
    );
}

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
        $resultado[$tabla] = array(
            "existe" => false,
            "columnas_faltantes" => null,
            "indices_faltantes" => null,
            "fks_faltantes" => null
        );
    }
    foreach ($tablas as $tabla => $item) {
        if (!array_key_exists($tabla, $resultado)) {
            continue;
        }
        $resultado[$tabla] = array(
            "existe" => !empty($item["existe"]),
            "columnas_faltantes" => count(valor($item, array("columnas_faltantes"), array())),
            "indices_faltantes" => count(valor($item, array("indices_faltantes"), array())),
            "fks_faltantes" => count(valor($item, array("fks_faltantes"), array()))
        );
    }
    return $resultado;
}

function resumenRespuesta($respuesta) {
    return array(
        "error" => valor($respuesta, array("error"), null),
        "tipo" => valor($respuesta, array("tipo"), null),
        "mensaje" => valor($respuesta, array("mensaje"), null),
        "schema_pendiente" => valor($respuesta, array("depurar", "schema_pendiente"), null),
        "total" => valor($respuesta, array("depurar", "total"), null)
    );
}

function archivoExiste($ruta) {
    return file_exists($ruta) && is_file($ruta);
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
