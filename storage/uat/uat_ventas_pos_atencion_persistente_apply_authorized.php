<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: crear una atencion POS persistente UAT solo con autorizacion explicita.
 * Impacto: valida cuentas compartidas multiusuario sin crear venta, pagos, reserva ni movimiento de inventario.
 * Contrato: BLOQUEADO por defecto; requiere respaldo, usuario y token VENTAS_POS_ATENCION_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$telefono = "5550000000";
$cliente = "Cliente UAT POS";
foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_ATENCION_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idSku <= 0 || $cantidad <= 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se creo atencion. Falta autorizacion, respaldo valido, usuario o partida.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_ATENCION_REAL",
            "--respaldo=RUTA_O_REFERENCIA",
            "--id_usuario=ID",
            "--id_sku=ID",
            "--cantidad=N"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "reglas" => array(
            "No crea venta.",
            "No registra pago.",
            "No reserva ni descuenta inventario.",
            "Caja debe revalidar stock/precio al cobrar."
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

$datos = array(
    "id_usuario" => $idUsuario,
    "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "id_terminal_pos" => intval(valor($datosAsignacion, "id_terminal_pos", 0)),
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $telefono,
    "origen" => "pos_uat",
    "estatus" => "lista_para_cobro",
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    )))
);

$resultado = $ventas->crearAtencionPersistente($datos);
echo json_encode(array(
    "ok" => empty($resultado["error"]) && $resultado["tipo"] === "success",
    "modo" => "atencion_persistente_apply",
    "respaldo_ref" => $respaldo,
    "asignacion" => array(
        "id_almacen" => $datos["id_almacen"],
        "id_caja" => $datos["id_caja"],
        "id_turno_caja" => $datos["id_turno_caja"],
        "id_terminal_pos" => $datos["id_terminal_pos"]
    ),
    "resultado" => $resultado,
    "siguiente_paso" => "Consultar bandeja y validar que caja ve la atencion sin movimiento de inventario."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function validarRespaldo($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}
