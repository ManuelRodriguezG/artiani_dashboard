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
$simulacion = $almacenPreflight
    ? $almacenes->simular_solicitud_resurtido_readonly(array("id_almacen_destino" => $almacenPreflight["id_almacen"]))
    : null;
$almacenOrigen = $almacenPreflight ? seleccionarAlmacenOrigen($almacenes, $almacenPreflight["id_almacen"]) : null;
$simulacionOrigenExplicito = ($almacenPreflight && $almacenOrigen)
    ? $almacenes->simular_solicitud_resurtido_readonly(array(
        "id_almacen_destino" => $almacenPreflight["id_almacen"],
        "id_almacen_origen" => $almacenOrigen["id_almacen"]
    ))
    : null;
$resumenTiendas = $almacenes->resumen_resurtido_tiendas_readonly(array());
$validacionSolicitud = $almacenPreflight
    ? $almacenes->validar_solicitud_resurtido_readonly(array("id_almacen_destino" => $almacenPreflight["id_almacen"]))
    : null;
$payloadSolicitud = $almacenPreflight
    ? $almacenes->payload_solicitud_resurtido_readonly(array("id_almacen_destino" => $almacenPreflight["id_almacen"]))
    : null;
$estadosContrato = $almacenes->estados_resurtido_readonly(array());
$transicionPermitida = $almacenes->estados_resurtido_readonly(array("desde" => "preparado", "hacia" => "enviado"));
$transicionBloqueada = $almacenes->estados_resurtido_readonly(array("desde" => "solicitado", "hacia" => "enviado"));
$contratoPrepEnvio = $almacenes->preparacion_envio_resurtido_contrato_readonly(array());
$contratoRecepcion = $almacenes->recepcion_diferencias_resurtido_contrato_readonly(array());
$contratoPoliticas = $almacenes->politicas_alertas_resurtido_contrato_readonly(array());
$guardadoBloqueado = $payloadSolicitud === null ? null : $almacenes->guardar_solicitud_resurtido(array(
    "payload" => json_encode(valor($payloadSolicitud, array("depurar", "payload"), array()))
), 0);
$prepararEnviarPendiente = $almacenes->preparar_enviar_resurtido_pendiente(array("folio" => "RES-UAT-READONLY"), 0);
$recibirPendiente = $almacenes->recibir_resurtido_pendiente(array("folio" => "RES-UAT-READONLY"), 0);

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
if ($simulacion !== null && !empty($simulacion["error"])) {
    $avisos[] = "Simulacion read-only devolvio aviso/error: " . valor($simulacion, array("mensaje"), "sin mensaje");
}
if ($simulacionOrigenExplicito !== null && !empty($simulacionOrigenExplicito["error"])) {
    $avisos[] = "Simulacion con origen explicito devolvio aviso/error: " . valor($simulacionOrigenExplicito, array("mensaje"), "sin mensaje");
}
if (!empty($resumenTiendas["error"])) {
    $avisos[] = "Resumen tiendas read-only devolvio aviso/error: " . valor($resumenTiendas, array("mensaje"), "sin mensaje");
}
if ($validacionSolicitud !== null && !empty($validacionSolicitud["error"])) {
    $avisos[] = "Validacion solicitud read-only devolvio aviso/error: " . valor($validacionSolicitud, array("mensaje"), "sin mensaje");
}
if ($payloadSolicitud !== null && !empty($payloadSolicitud["error"])) {
    $avisos[] = "Payload solicitud read-only devolvio aviso/error: " . valor($payloadSolicitud, array("mensaje"), "sin mensaje");
}
if (!empty($estadosContrato["error"])) {
    $bloqueos[] = "Contrato de estados devolvio error: " . valor($estadosContrato, array("mensaje"), "sin mensaje");
}
if (count(valor($estadosContrato, array("depurar", "estados_encabezado"), array())) < 10) {
    $bloqueos[] = "Contrato de estados incompleto";
}
if (count(valor($estadosContrato, array("depurar", "transiciones"), array())) < 10) {
    $bloqueos[] = "Contrato de transiciones incompleto";
}
if (intval(valor($transicionPermitida, array("depurar", "validacion_transicion", "permitida"), 0)) !== 1) {
    $bloqueos[] = "Transicion preparado -> enviado no aparece permitida";
}
if (intval(valor($transicionBloqueada, array("depurar", "validacion_transicion", "permitida"), 1)) !== 0) {
    $bloqueos[] = "Transicion solicitado -> enviado aparece permitida indebidamente";
}
if (!empty($contratoPrepEnvio["error"])) {
    $bloqueos[] = "Contrato preparacion/envio devolvio error: " . valor($contratoPrepEnvio, array("mensaje"), "sin mensaje");
}
if (intval(valor($contratoPrepEnvio, array("depurar", "preparacion", "no_afecta_inventario"), 0)) !== 1) {
    $bloqueos[] = "Contrato preparacion no declara no_afecta_inventario";
}
if (intval(valor($contratoPrepEnvio, array("depurar", "envio", "afecta_inventario"), 0)) !== 1) {
    $bloqueos[] = "Contrato envio no declara afecta_inventario";
}
if (count(valor($contratoPrepEnvio, array("depurar", "envio", "movimientos_esperados"), array())) < 2) {
    $bloqueos[] = "Contrato envio no define salida y entrada a transito";
}
if (!empty($contratoRecepcion["error"])) {
    $bloqueos[] = "Contrato recepcion/diferencias devolvio error: " . valor($contratoRecepcion, array("mensaje"), "sin mensaje");
}
if (intval(valor($contratoRecepcion, array("depurar", "recepcion", "afecta_inventario"), 0)) !== 1) {
    $bloqueos[] = "Contrato recepcion no declara afecta_inventario";
}
if (count(valor($contratoRecepcion, array("depurar", "movimientos_esperados"), array())) < 2) {
    $bloqueos[] = "Contrato recepcion no define salida de transito y entrada a tienda";
}
if (count(valor($contratoRecepcion, array("depurar", "diferencias"), array())) < 6) {
    $bloqueos[] = "Contrato recepcion no define diferencias minimas";
}
if (!empty($contratoPoliticas["error"])) {
    $bloqueos[] = "Contrato politicas/alertas devolvio error: " . valor($contratoPoliticas, array("mensaje"), "sin mensaje");
}
if (count(valor($contratoPoliticas, array("depurar", "contrato_politica", "campos_obligatorios"), array())) < 6) {
    $bloqueos[] = "Contrato politicas no define campos obligatorios minimos";
}
if (count(valor($contratoPoliticas, array("depurar", "formula"), array())) < 4) {
    $bloqueos[] = "Contrato politicas no define formula de resurtido";
}
if (count(valor($contratoPoliticas, array("depurar", "alertas_futuras", "eventos"), array())) < 4) {
    $bloqueos[] = "Contrato politicas no define eventos de alerta";
}
if ($guardadoBloqueado !== null && !empty($guardadoBloqueado["error"])) {
    $avisos[] = "Guardado bloqueado devolvio error inesperado: " . valor($guardadoBloqueado, array("mensaje"), "sin mensaje");
}
$guardadoSchemaPendiente = $guardadoBloqueado === null ? null : valor($guardadoBloqueado, array("depurar", "schema_pendiente"), null);
if ($guardadoSchemaPendiente !== 1) {
    $bloqueos[] = "Guardado RES-T008 no quedo bloqueado por schema_pendiente";
}
if (intval(valor($prepararEnviarPendiente, array("depurar", "schema_pendiente"), 0)) !== 1) {
    $bloqueos[] = "Preparar/enviar RES-T009 no quedo bloqueado por schema_pendiente";
}
if (intval(valor($prepararEnviarPendiente, array("depurar", "movimientos_generados"), 1)) !== 0) {
    $bloqueos[] = "Preparar/enviar RES-T009 reporto movimientos generados";
}
if (intval(valor($recibirPendiente, array("depurar", "schema_pendiente"), 0)) !== 1) {
    $bloqueos[] = "Recibir RES-T010 no quedo bloqueado por schema_pendiente";
}
if (intval(valor($recibirPendiente, array("depurar", "movimientos_generados"), 1)) !== 0) {
    $bloqueos[] = "Recibir RES-T010 reporto movimientos generados";
}
$payloadContrato = $payloadSolicitud === null ? null : validarPayloadContrato($payloadSolicitud);
if ($payloadContrato !== null && !$payloadContrato["ok"]) {
    foreach ($payloadContrato["fallos"] as $fallo) {
        $bloqueos[] = "Payload contrato: " . $fallo;
    }
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
        "simulacion" => $simulacion === null ? null : resumenRespuesta($simulacion),
        "simulacion_partidas" => $simulacion === null ? null : valor($simulacion, array("depurar", "total_partidas"), null),
        "simulacion_folio_preview" => $simulacion === null ? null : valor($simulacion, array("depurar", "folio_preview"), null),
        "simulacion_partidas_origen_insuficiente" => $simulacion === null ? null : valor($simulacion, array("depurar", "partidas_con_origen_insuficiente"), null),
        "simulacion_total_surtible_origen" => $simulacion === null ? null : valor($simulacion, array("depurar", "cantidad_total_surtible_origen"), null),
        "simulacion_origen_explicito" => $simulacionOrigenExplicito === null ? null : resumenRespuesta($simulacionOrigenExplicito),
        "simulacion_origen_explicito_partidas" => $simulacionOrigenExplicito === null ? null : valor($simulacionOrigenExplicito, array("depurar", "total_partidas"), null),
        "resumen_tiendas" => resumenRespuesta($resumenTiendas),
        "resumen_tiendas_totales" => valor($resumenTiendas, array("depurar", "totales"), null),
        "validacion_solicitud" => $validacionSolicitud === null ? null : resumenRespuesta($validacionSolicitud),
        "validacion_puede_guardar" => $validacionSolicitud === null ? null : valor($validacionSolicitud, array("depurar", "puede_guardar"), null),
        "validacion_bloqueos" => $validacionSolicitud === null ? null : count(valor($validacionSolicitud, array("depurar", "bloqueos"), array())),
        "validacion_advertencias" => $validacionSolicitud === null ? null : count(valor($validacionSolicitud, array("depurar", "advertencias"), array())),
        "payload_solicitud" => $payloadSolicitud === null ? null : resumenRespuesta($payloadSolicitud),
        "payload_puede_enviar_post" => $payloadSolicitud === null ? null : valor($payloadSolicitud, array("depurar", "puede_enviar_post"), null),
        "payload_lineas" => $payloadSolicitud === null ? null : count(valor($payloadSolicitud, array("depurar", "payload", "detalle"), array())),
        "payload_contrato" => $payloadContrato,
        "estados_contrato" => resumenRespuesta($estadosContrato),
        "estados_encabezado" => count(valor($estadosContrato, array("depurar", "estados_encabezado"), array())),
        "estados_detalle" => count(valor($estadosContrato, array("depurar", "estados_detalle"), array())),
        "transiciones" => count(valor($estadosContrato, array("depurar", "transiciones"), array())),
        "transicion_preparado_enviado" => valor($transicionPermitida, array("depurar", "validacion_transicion", "permitida"), null),
        "transicion_solicitado_enviado" => valor($transicionBloqueada, array("depurar", "validacion_transicion", "permitida"), null),
        "contrato_preparacion_envio" => resumenRespuesta($contratoPrepEnvio),
        "preparacion_no_afecta_inventario" => valor($contratoPrepEnvio, array("depurar", "preparacion", "no_afecta_inventario"), null),
        "envio_afecta_inventario" => valor($contratoPrepEnvio, array("depurar", "envio", "afecta_inventario"), null),
        "envio_movimientos_esperados" => count(valor($contratoPrepEnvio, array("depurar", "envio", "movimientos_esperados"), array())),
        "contrato_recepcion_diferencias" => resumenRespuesta($contratoRecepcion),
        "recepcion_afecta_inventario" => valor($contratoRecepcion, array("depurar", "recepcion", "afecta_inventario"), null),
        "recepcion_movimientos_esperados" => count(valor($contratoRecepcion, array("depurar", "movimientos_esperados"), array())),
        "recepcion_diferencias" => count(valor($contratoRecepcion, array("depurar", "diferencias"), array())),
        "recepcion_cierres" => count(valor($contratoRecepcion, array("depurar", "cierres"), array())),
        "contrato_politicas_alertas" => resumenRespuesta($contratoPoliticas),
        "politica_local_disponible" => valor($contratoPoliticas, array("depurar", "politica_local_disponible"), null),
        "politica_campos_obligatorios" => count(valor($contratoPoliticas, array("depurar", "contrato_politica", "campos_obligatorios"), array())),
        "politica_formula_reglas" => count(valor($contratoPoliticas, array("depurar", "formula"), array())),
        "politica_alertas_eventos" => count(valor($contratoPoliticas, array("depurar", "alertas_futuras", "eventos"), array())),
        "guardado_bloqueado" => $guardadoBloqueado === null ? null : resumenRespuesta($guardadoBloqueado),
        "guardado_schema_pendiente" => $guardadoSchemaPendiente,
        "guardado_realizado" => $guardadoBloqueado === null ? null : valor($guardadoBloqueado, array("depurar", "guardado"), null),
        "preparar_enviar_pendiente" => resumenRespuesta($prepararEnviarPendiente),
        "preparar_enviar_schema_pendiente" => valor($prepararEnviarPendiente, array("depurar", "schema_pendiente"), null),
        "preparar_enviar_movimientos" => valor($prepararEnviarPendiente, array("depurar", "movimientos_generados"), null),
        "recibir_pendiente" => resumenRespuesta($recibirPendiente),
        "recibir_schema_pendiente" => valor($recibirPendiente, array("depurar", "schema_pendiente"), null),
        "recibir_movimientos" => valor($recibirPendiente, array("depurar", "movimientos_generados"), null),
        "almacen_preflight" => $almacenPreflight,
        "almacen_origen_explicito" => $almacenOrigen
    ),
    "guardrails" => array(
        "requiere_respaldo_externo_para_ddl" => true,
        "requiere_autorizacion_textual" => true,
        "token_autorizacion_ddl" => "ALMACEN_RESURTIDO_DDL",
        "confirmacion_textual_ddl" => "AUTORIZO DDL RESURTIDO ALMACEN usando respaldo RUTA_O_REFERENCIA",
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

function seleccionarAlmacenOrigen($almacenes, $idDestino) {
    $respuesta = $almacenes->obtener_almacenes(array());
    $items = empty($respuesta["error"]) && is_array(valor($respuesta, array("depurar"), null))
        ? $respuesta["depurar"]
        : array();
    foreach ($items as $item) {
        if (intval($item["id_almacen"]) === intval($idDestino)) {
            continue;
        }
        if (intval($item["permite_preparacion"]) === 1 || in_array($item["tipo_almacen"], array("bodega", "principal"), true)) {
            return array(
                "id_almacen" => intval($item["id_almacen"]),
                "almacen" => $item["almacen"]
            );
        }
    }
    return null;
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

function validarPayloadContrato($respuesta) {
    $fallos = array();
    $payload = valor($respuesta, array("depurar", "payload"), array());
    $encabezado = valor($payload, array("encabezado"), array());
    $detalle = valor($payload, array("detalle"), array());

    foreach (array(
        "tipo_documento",
        "estatus",
        "prioridad",
        "origen_solicitud",
        "id_almacen_solicitante",
        "id_almacen_origen"
    ) as $campo) {
        if (!array_key_exists($campo, $encabezado) || $encabezado[$campo] === "" || $encabezado[$campo] === null) {
            $fallos[] = "encabezado.{$campo} faltante";
        }
    }
    if (!is_array($detalle) || empty($detalle)) {
        $fallos[] = "detalle vacio";
    }
    foreach ($detalle as $idx => $linea) {
        foreach (array(
            "id_sku_erp",
            "sku",
            "nombre_producto",
            "unidad_base",
            "cantidad_solicitada",
            "cantidad_autorizada",
            "estatus",
            "cobertura_origen"
        ) as $campo) {
            if (!array_key_exists($campo, $linea)) {
                $fallos[] = "detalle[{$idx}].{$campo} faltante";
            }
        }
        $cobertura = isset($linea["cobertura_origen"]) && is_array($linea["cobertura_origen"]) ? $linea["cobertura_origen"] : array();
        foreach (array(
            "cantidad_disponible_origen",
            "cantidad_surtible_origen",
            "puede_surtir_origen",
            "estatus_cobertura_origen"
        ) as $campo) {
            if (!array_key_exists($campo, $cobertura)) {
                $fallos[] = "detalle[{$idx}].cobertura_origen.{$campo} faltante";
            }
        }
        if (floatval(valor($linea, array("cantidad_solicitada"), 0)) <= 0) {
            $fallos[] = "detalle[{$idx}].cantidad_solicitada invalida";
        }
    }

    return array(
        "ok" => empty($fallos),
        "fallos" => $fallos,
        "encabezado_campos" => count($encabezado),
        "detalle_lineas" => is_array($detalle) ? count($detalle) : 0
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
