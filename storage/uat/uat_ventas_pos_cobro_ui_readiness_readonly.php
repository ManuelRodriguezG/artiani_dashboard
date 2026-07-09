<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: validar si el cobro real desde POS UI puede exponerse sin escribir BD.
 * Impacto: confirma contrato de usuario, caja, turno, pagos, stock, ticket, garantia y excepcion comercial.
 * Contrato: read-only; no crea venta, no registra pagos, no mueve caja, no descuenta inventario y no consume folios.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idSkuSimple = 1760;
$cantidadSimple = 1;
$precioSimple = 295;
$pagoSimple = 295;
$itemsArg = "";
$pagosArg = "";
$folioExcepcion = "";
$identificadorCliente = "";
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSkuSimple = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidadSimple = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precioSimple = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pagoSimple = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--items=") === 0) {
        $itemsArg = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--pagos=") === 0) {
        $pagosArg = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--folio_excepcion=") === 0) {
        $folioExcepcion = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--identificador_cliente=") === 0) {
        $identificadorCliente = trim(substr($arg, 24), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/controladores/Ventas.php";

class VentasPosCobroUiReadinessDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new VentasPosCobroUiReadinessDb();
$db = $ventas->db();
$bloqueos = array();
$avisos = array();

$respaldoValidado = validarRespaldo($respaldo);
if (!$respaldoValidado["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}

$endpointRealExiste = method_exists("Ventas", "pos_confirmar_erp");
$endpointsSoporte = array(
    "pos_carrito_prevalidar_erp" => method_exists("Ventas", "pos_carrito_prevalidar_erp"),
    "pos_confirmar_dryrun_erp" => method_exists("Ventas", "pos_confirmar_dryrun_erp"),
    "pos_excepcion_consumo_dryrun_erp" => method_exists("Ventas", "pos_excepcion_consumo_dryrun_erp"),
    "ticket_preview_dryrun_erp" => method_exists("Ventas", "ticket_preview_dryrun_erp")
);
foreach ($endpointsSoporte as $metodo => $existe) {
    if (!$existe) {
        $bloqueos[] = "Falta endpoint de soporte Ventas." . $metodo;
    }
}
if (!$endpointRealExiste) {
    $avisos[] = "Endpoint real /ventas/pos_confirmar_erp aun no expuesto; requiere autorizacion fuerte antes de implementarlo.";
}

$tablasCriticas = array(
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_pagos",
    "erp_ventas_detalle_inventario",
    "erp_pos_movimientos_caja",
    "erp_pos_turnos",
    "erp_inventario_movimientos",
    "erp_inventario_existencias",
    "erp_inventario_unidades",
    "erp_ventas_detalle_garantias"
);
$tablasEstado = array();
$conteosAntes = array();
foreach ($tablasCriticas as $tabla) {
    $existe = tablaExiste($db, $tabla);
    $tablasEstado[$tabla] = $existe;
    $conteosAntes[$tabla] = $existe ? contar($db, $tabla) : null;
    if (!$existe) {
        $bloqueos[] = "Falta tabla critica " . $tabla;
    }
}

$permisoOperar = usuarioTienePermiso($db, $idUsuario, "ventas.operar");
if (!$permisoOperar) {
    $bloqueos[] = "Usuario sin permiso ventas.operar";
}

$items = $itemsArg !== "" ? $itemsArg : json_encode(array(array(
    "id_sku" => $idSkuSimple > 0 ? $idSkuSimple : 1760,
    "cantidad" => $cantidadSimple > 0 ? $cantidadSimple : 1,
    "precio_unitario" => $precioSimple > 0 ? $precioSimple : 295,
    "modo_salida" => "existencia_agregada"
)));
$pagos = $pagosArg !== "" ? $pagosArg : json_encode(array(array(
    "id_metodo_pago" => 1,
    "monto" => $pagoSimple > 0 ? $pagoSimple : ($precioSimple * $cantidadSimple),
    "referencia" => "UAT-POS-UI"
)));

if (!jsonEsValido($items)) {
    $bloqueos[] = "Parametro --items no es JSON valido";
}
if (!jsonEsValido($pagos)) {
    $bloqueos[] = "Parametro --pagos no es JSON valido";
}

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turno = valor($depurarAsignacion, array("turno_abierto"), array());
foreach (valor($asignacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
if (empty($datosAsignacion)) {
    $bloqueos[] = "No hay asignacion POS activa para el usuario";
}
if (empty($turno)) {
    $bloqueos[] = "No hay turno abierto para la caja asignada";
}

$datosVenta = array(
    "id_almacen" => intval(valor($datosAsignacion, array("id_almacen"), 0)),
    "id_caja" => intval(valor($datosAsignacion, array("id_caja"), 0)),
    "id_turno_caja" => intval(valor($turno, array("id_turno_caja"), 0)),
    "canal" => "pos",
    "identificador_cliente" => $identificadorCliente,
    "items" => $items,
    "pagos" => $pagos,
    "exigir_pago_completo" => 1
);

$prevalidacion = $ventas->prevalidarCarritoPos($datosVenta);
$confirmacion = $ventas->confirmarVentaPosDryRun($datosVenta);
$ticketPreview = $ventas->ticketPreviewDryRun($datosVenta);
foreach (valor($prevalidacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
foreach (valor($confirmacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
foreach (valor($ticketPreview, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

$consumoExcepcion = null;
if ($folioExcepcion !== "") {
    $datosExcepcion = $datosVenta;
    $datosExcepcion["folio_excepcion"] = $folioExcepcion;
    $datosExcepcion["id_usuario"] = $idUsuario;
    $consumoExcepcion = $ventas->excepcionComercialConsumoDryRun($datosExcepcion);
    foreach (valor($consumoExcepcion, array("depurar", "bloqueos"), array()) as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
}

$conteosDespues = array();
foreach ($tablasCriticas as $tabla) {
    $conteosDespues[$tabla] = tablaExiste($db, $tabla) ? contar($db, $tabla) : null;
    if ($conteosAntes[$tabla] !== $conteosDespues[$tabla]) {
        $bloqueos[] = "Read-only violado: cambio conteo en " . $tabla;
    }
}

responder(array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "ventas_pos_cobro_ui_readiness_readonly",
    "read_only" => true,
    "endpoint_real" => array(
        "ruta_propuesta" => "/ventas/pos_confirmar_erp",
        "controlador_metodo_existe" => $endpointRealExiste,
        "requiere_autorizacion_fuerte_para_exponer" => !$endpointRealExiste,
        "permiso_base_propuesto" => "ventas.operar",
        "requiere_csrf_post" => true,
        "requiere_auditoria_explicita" => true
    ),
    "endpoints_soporte" => $endpointsSoporte,
    "respaldo" => $respaldoValidado,
    "usuario" => array(
        "id_usuario" => $idUsuario,
        "ventas.operar" => $permisoOperar
    ),
    "contexto_pos" => array(
        "asignacion_activa" => !empty($datosAsignacion),
        "turno_abierto" => !empty($turno),
        "id_almacen" => $datosVenta["id_almacen"],
        "id_caja" => $datosVenta["id_caja"],
        "id_turno_caja" => $datosVenta["id_turno_caja"]
    ),
    "schema" => array(
        "tablas" => $tablasEstado
    ),
    "payload_probado" => $datosVenta,
    "prevalidacion" => resumenRespuesta($prevalidacion),
    "confirmacion_dryrun" => resumenRespuesta($confirmacion),
    "ticket_preview" => resumenRespuesta($ticketPreview),
    "excepcion_comercial" => $consumoExcepcion ? resumenRespuesta($consumoExcepcion) : null,
    "conteos_antes" => $conteosAntes,
    "conteos_despues" => $conteosDespues,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "siguiente_paso" => empty(array_unique($bloqueos))
        ? "Ejecutar UAT de cobro real desde POS UI y validar post-venta, ticket, kardex y cierre."
        : "Resolver bloqueos antes de ejecutar cobro real en POS UI."
));

function validarRespaldo($respaldo) {
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => trim((string) $respaldo) !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false,
        "tamano_bytes" => $existe ? filesize($respaldo) : null
    );
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SHOW TABLES LIKE :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
}

function contar($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
}

function usuarioTienePermiso($db, $idUsuario, $permiso) {
    $stmt = $db->prepare("SELECT COUNT(*)
        FROM sys_usuarios_roles ur
        INNER JOIN sys_roles r ON r.id_rol=ur.id_rol AND r.estatus=1
        INNER JOIN sys_roles_permisos rp ON rp.id_rol=r.id_rol
        INNER JOIN sys_permisos p ON p.id_permiso=rp.id_permiso AND p.estatus=1
        WHERE ur.id_usuario=:usuario AND ur.estatus=1 AND p.permiso=:permiso");
    $stmt->execute(array(":usuario" => intval($idUsuario), ":permiso" => $permiso));
    return intval($stmt->fetchColumn()) > 0;
}

function jsonEsValido($valor) {
    if (!is_string($valor) || trim($valor) === "") {
        return false;
    }
    json_decode($valor, true);
    return json_last_error() === JSON_ERROR_NONE;
}

function resumenRespuesta($respuesta) {
    return array(
        "error" => !empty($respuesta["error"]),
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : null,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null,
        "bloqueos" => valor($respuesta, array("depurar", "bloqueos"), array()),
        "totales" => valor($respuesta, array("depurar", "totales"), array()),
        "contrato" => valor($respuesta, array("depurar", "contrato_confirmacion"), valor($respuesta, array("depurar", "contrato"), array()))
    );
}

function valor($origen, $ruta, $default = null) {
    $actual = $origen;
    foreach ($ruta as $clave) {
        if (!is_array($actual) || !array_key_exists($clave, $actual)) {
            return $default;
        }
        $actual = $actual[$clave];
    }
    return $actual;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
