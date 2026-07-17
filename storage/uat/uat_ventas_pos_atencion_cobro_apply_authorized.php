<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: ejecutar UAT real de cobro/conversion de una atencion POS multiusuario.
 * Impacto: si pasa prevalidacion, crea venta POS, pagos, caja, kardex/salidas, garantia y marca la atencion como convertida.
 * Contrato: BLOQUEADO por defecto; requiere respaldo/referencia vigente, id_usuario, id_atencion y token VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idAtencion = 0;
$pago = 0;
$idMetodoPago = 1;
$referenciaPago = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--id_metodo_pago=") === 0) {
        $idMetodoPago = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--referencia_pago=") === 0) {
        $referenciaPago = trim(substr($arg, 18), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idAtencion <= 0 || $pago <= 0) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se cobro atencion POS. Falta autorizacion explicita, respaldo valido, usuario, atencion o pago.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_ATENCION_TOMAR_COBRAR_REAL",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--id_atencion=ID",
            "--pago=MONTO"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "alcance" => array(
            "crea_venta" => true,
            "mueve_caja" => true,
            "mueve_inventario_si_hay_asignaciones" => true,
            "convierte_atencion" => true,
            "usa_backend_pos_real" => true
        )
    ));
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/GarantiasErp.php";

$ventas = new VentasErp();
$detalle = $ventas->atencionDetalleReadOnly(array("id_atencion" => $idAtencion));
if (!empty($detalle["error"])) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "preflight",
        "mensaje" => "No se encontro detalle valido de la atencion.",
        "detalle" => $detalle
    ));
}

$depurarDetalle = valor($detalle, "depurar", array());
$atencion = valor($depurarDetalle, "atencion", array());
$partidas = valor($depurarDetalle, "partidas", array());

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

$items = array();
foreach ($partidas as $partida) {
    $items[] = array(
        "id_sku" => intval(valor($partida, "id_sku", 0)),
        "cantidad" => floatval(valor($partida, "cantidad", 0)),
        "precio_unitario" => floatval(valor($partida, "precio_unitario", 0)),
        "modo_salida" => valor($partida, "modo_salida", "existencia_agregada")
    );
}

$payload = array(
    "id_usuario" => $idUsuario,
    "id_atencion" => $idAtencion,
    "id_almacen" => intval(valor($atencion, "id_almacen", valor($datosAsignacion, "id_almacen", 0))),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "canal" => "pos",
    "tipo_documento" => "venta",
    "cliente_nombre_publico" => valor($atencion, "cliente_nombre_publico", ""),
    "identificador_cliente" => valor($atencion, "cliente_identificador_publico", ""),
    "items" => json_encode($items),
    "pagos" => json_encode(array(array(
        "id_metodo_pago" => $idMetodoPago > 0 ? $idMetodoPago : 1,
        "monto" => $pago,
        "referencia" => $referenciaPago !== "" ? $referenciaPago : "UAT-ATENCION-" . $idAtencion
    ))),
    "exigir_pago_completo" => 1
);

$dryRun = $ventas->confirmarVentaPosDryRun($payload);
$bloqueos = array();
foreach (valor(valor($dryRun, "depurar", array()), "bloqueos", array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
if (intval($payload["id_caja"]) <= 0 || intval($payload["id_turno_caja"]) <= 0) {
    $bloqueos[] = "No hay turno/caja abierta para el usuario";
}
if (!empty(array_unique($bloqueos))) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "preflight",
        "mensaje" => "No se ejecuto cobro real de atencion; preflight con bloqueos.",
        "bloqueos" => array_values(array_unique($bloqueos)),
        "detalle" => $detalle,
        "asignacion" => array(
            "id_almacen" => $payload["id_almacen"],
            "id_caja" => $payload["id_caja"],
            "id_turno_caja" => $payload["id_turno_caja"]
        ),
        "dry_run" => $dryRun
    ));
}

$resultado = $ventas->confirmarVentaPosReal($payload);
responder(array(
    "ok" => empty($resultado["error"]) && valor($resultado, "tipo", "") === "success",
    "modo" => "ventas_pos_atencion_cobro_apply_authorized",
    "host" => "panel.com.local",
    "respaldo_ref" => $respaldo,
    "id_atencion" => $idAtencion,
    "payload_resumen" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $payload["id_almacen"],
        "id_caja" => $payload["id_caja"],
        "id_turno_caja" => $payload["id_turno_caja"],
        "partidas" => count($items),
        "pago" => $pago
    ),
    "resultado" => $resultado,
    "siguiente_paso" => "Validar que la atencion quedo convertida, revisar ticket, caja, kardex y cerrar turno UAT."
));

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $pareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $pareceRuta) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    return array(
        "ok" => $respaldo !== "" && strlen($respaldo) >= 8 && (!$pareceRuta || ($existe && $legible && $tamano !== null && $tamano > 0)),
        "referencia" => $respaldo,
        "parece_ruta_local" => $pareceRuta,
        "archivo_existe" => $pareceRuta ? $existe : null,
        "archivo_legible" => $pareceRuta ? $legible : null,
        "tamano" => $tamano
    );
}

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($payload["ok"]) ? 1 : 0);
}
