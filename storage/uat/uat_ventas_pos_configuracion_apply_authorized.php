<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: ejecutar CRUD real de Configuracion POS solo con autorizacion explicita.
 * Impacto: puede crear/editar cajas, terminales, asignaciones o desactivar configuracion POS.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_CONFIG_CRUD y respaldo vigente.
 */

$args = isset($argv) ? $argv : array();
$datos = array();
foreach ($args as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) {
        continue;
    }
    $partes = explode("=", substr($arg, 2), 2);
    $datos[$partes[0]] = trim($partes[1], "\"' ");
}

$autorizar = isset($datos["autorizar"]) ? $datos["autorizar"] : "";
$respaldo = isset($datos["respaldo"]) ? resolverRespaldo($datos["respaldo"]) : "";
$accion = isset($datos["accion"]) ? $datos["accion"] : "";
$idUsuario = isset($datos["id_usuario"]) ? intval($datos["id_usuario"]) : 0;
$validacionRespaldo = validarRespaldo($respaldo);

if ($autorizar !== "VENTAS_POS_CONFIG_CRUD" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || !in_array($accion, array("caja", "terminal", "asignacion", "desactivar"), true)) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se ejecuto Configuracion POS. Falta autorizacion, respaldo, usuario o accion valida.",
        "acciones_validas" => array("caja", "terminal", "asignacion", "desactivar"),
        "requerido" => array(
            "--autorizar=VENTAS_POS_CONFIG_CRUD",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--accion=caja|terminal|asignacion|desactivar"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => array(
            "bloqueado_por_defecto" => true,
            "no_abre_turno" => true,
            "no_mueve_caja" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$payload = array(
    "id_caja" => intval(valor($datos, "id_caja", 0)),
    "id_terminal_pos" => intval(valor($datos, "id_terminal_pos", 0)),
    "id_usuario_caja" => intval(valor($datos, "id_usuario_caja", 0)),
    "id_usuario" => intval(valor($datos, "id_usuario_asignado", $idUsuario)),
    "id_almacen" => intval(valor($datos, "id_almacen", 0)),
    "codigo" => valor($datos, "codigo", ""),
    "nombre" => valor($datos, "nombre", ""),
    "id_caja" => intval(valor($datos, "id_caja", 0)),
    "identificador_terminal" => valor($datos, "identificador_terminal", ""),
    "id_terminal_pos" => intval(valor($datos, "id_terminal_pos", 0)),
    "permite_efectivo" => intval(valor($datos, "permite_efectivo", 1)),
    "permite_tarjeta" => intval(valor($datos, "permite_tarjeta", 1)),
    "permite_transferencia" => intval(valor($datos, "permite_transferencia", 1)),
    "prioridad" => intval(valor($datos, "prioridad", 1)),
    "observaciones" => valor($datos, "observaciones", "UAT Configuracion POS autorizada")
);

if ($accion === "caja") {
    $respuesta = $ventas->configuracionCajaGuardarReal($payload, $idUsuario);
} elseif ($accion === "terminal") {
    $respuesta = $ventas->configuracionTerminalGuardarReal($payload, $idUsuario);
} elseif ($accion === "asignacion") {
    $respuesta = $ventas->configuracionAsignacionGuardarReal($payload, $idUsuario);
} else {
    $respuesta = $ventas->configuracionPosDesactivarReal(array(
        "tipo" => valor($datos, "tipo", ""),
        "id" => intval(valor($datos, "id", 0)),
        "motivo" => valor($datos, "motivo", "")
    ), $idUsuario);
}

echo json_encode(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_configuracion_apply_authorized",
    "accion" => $accion,
    "respaldo_ref" => $respaldo,
    "id_usuario" => $idUsuario,
    "respuesta" => $respuesta,
    "contrato" => array(
        "escritura_autorizada" => true,
        "no_abre_turno" => true,
        "no_mueve_caja" => true,
        "no_crea_venta" => true,
        "no_mueve_inventario" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function resolverRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente" || $respaldo === "respaldo UAT POS vigente") {
        return "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
    }
    return $respaldo;
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $pareceRuta ? file_exists($respaldo) : false;
    return array(
        "ok" => $respaldo !== "" && (!$pareceRuta || ($existe && is_readable($respaldo))),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $existe,
        "archivo_legible" => $existe ? is_readable($respaldo) : false
    );
}
