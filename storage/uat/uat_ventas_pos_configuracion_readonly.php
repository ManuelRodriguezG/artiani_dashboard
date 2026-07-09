<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: auditar configuracion POS sin escribir BD.
 * Impacto: resume cajas, terminales, asignaciones y amarre de usuario a caja antes de CRUD real.
 * Contrato: read-only/dry-run; no crea caja, no crea terminal, no asigna usuarios y no abre turnos.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 0;
$idCaja = 0;
$idTerminal = 0;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_terminal=") === 0) {
        $idTerminal = intval(trim(substr($arg, 14), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$configuracion = $ventas->configuracionPosReadOnly();
$depurar = valor($configuracion, "depurar", array());
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$asignacionActual = valor($depurarAsignacion, "asignacion", array());

if ($idAlmacen <= 0 && intval(valor($asignacionActual, "id_almacen", 0)) > 0) {
    $idAlmacen = intval(valor($asignacionActual, "id_almacen", 0));
}
if ($idCaja <= 0 && intval(valor($asignacionActual, "id_caja", 0)) > 0) {
    $idCaja = intval(valor($asignacionActual, "id_caja", 0));
}
if ($idTerminal <= 0 && intval(valor($asignacionActual, "id_terminal_pos", 0)) > 0) {
    $idTerminal = intval(valor($asignacionActual, "id_terminal_pos", 0));
}
if ($idAlmacen <= 0) {
    $almacenes = valor($depurar, "almacenes", array());
    $idAlmacen = !empty($almacenes) ? intval(valor($almacenes[0], "id_almacen", 0)) : 0;
}
if ($idCaja <= 0) {
    $cajas = valor($depurar, "cajas", array());
    $idCaja = !empty($cajas) ? intval(valor($cajas[0], "id_caja", 0)) : 0;
}

$dryCaja = $ventas->configuracionCajaDryRun(array(
    "id_almacen" => $idAlmacen,
    "codigo" => "CJ-UAT-READONLY",
    "nombre" => "Caja UAT ReadOnly",
    "permite_efectivo" => 1,
    "permite_tarjeta" => 1,
    "permite_transferencia" => 1
));
$dryTerminal = $ventas->configuracionTerminalDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "codigo" => "TERM-UAT-READONLY",
    "nombre" => "Terminal UAT ReadOnly",
    "identificador_terminal" => "uat-readonly"
));
$dryAsignacion = $ventas->configuracionAsignacionDryRun(array(
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_terminal_pos" => $idTerminal,
    "prioridad" => 1
));

$resumen = valor($depurar, "resumen", array());
$hallazgos = array();
if (!empty($depurar["schema_pendiente"])) {
    $hallazgos[] = "Esquema POS de configuracion incompleto";
}
if (intval(valor($resumen, "cajas", 0)) <= 0) {
    $hallazgos[] = "Sin cajas POS configuradas";
}
if (intval(valor($resumen, "asignaciones", 0)) <= 0) {
    $hallazgos[] = "Sin asignaciones usuario/caja";
}
if (empty($depurarAsignacion["asignacion_activa"])) {
    $hallazgos[] = "Usuario sin asignacion POS activa";
}
foreach (array(
    "Caja" => $dryCaja,
    "Terminal" => $dryTerminal,
    "Asignacion" => $dryAsignacion
) as $etiqueta => $respuesta) {
    $bloqueos = valor(valor($respuesta, "depurar", array()), "bloqueos", array());
    if (!empty($bloqueos)) {
        if ($etiqueta === "Asignacion"
            && !empty($depurarAsignacion["asignacion_activa"])
            && count($bloqueos) === 1
            && strpos($bloqueos[0], "Ya existe una asignacion activa") !== false) {
            continue;
        }
        $hallazgos[] = $etiqueta . " dry-run bloqueada: " . implode("; ", $bloqueos);
    }
}

$salida = array(
    "ok" => !valor($configuracion, "error", true),
    "modo" => "ventas_pos_configuracion_readonly",
    "read_only" => true,
    "contexto" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_caja" => $idCaja,
        "id_terminal_pos" => $idTerminal,
        "asignacion_activa" => !empty($depurarAsignacion["asignacion_activa"]),
        "turno_abierto" => !empty(valor($depurarAsignacion, "turno_abierto", array()))
    ),
    "resumen" => array(
        "schema_pendiente" => !empty($depurar["schema_pendiente"]),
        "cajas" => intval(valor($resumen, "cajas", 0)),
        "terminales" => intval(valor($resumen, "terminales", 0)),
        "asignaciones" => intval(valor($resumen, "asignaciones", 0)),
        "turnos_abiertos" => intval(valor($resumen, "turnos_abiertos", 0)),
        "movimientos_recientes" => intval(valor($resumen, "movimientos_recientes", 0))
    ),
    "dry_run" => array(
        "caja_bloqueos" => valor(valor($dryCaja, "depurar", array()), "bloqueos", array()),
        "terminal_bloqueos" => valor(valor($dryTerminal, "depurar", array()), "bloqueos", array()),
        "asignacion_bloqueos" => valor(valor($dryAsignacion, "depurar", array()), "bloqueos", array()),
        "asignacion_existente_ok" => !empty($depurarAsignacion["asignacion_activa"])
    ),
    "hallazgos" => $hallazgos,
    "siguiente_recomendado" => empty($hallazgos)
        ? "Configuracion base lista para preparar CRUD real con autorizacion."
        : "Resolver hallazgos de configuracion antes de activar CRUD real o cierre operativo.",
    "autorizacion_sugerida" => empty($hallazgos)
        ? "AUTORIZO PREPARAR CRUD REAL CONFIGURACION POS usando respaldo UAT POS vigente con token VENTAS_POS_CONFIG_CRUD para UAT POS"
        : "",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_caja" => true,
        "no_crea_terminal" => true,
        "no_asigna_usuario" => true,
        "no_abre_turno" => true
    )
);

if (!$compacto) {
    $salida["detalle"] = array(
        "configuracion" => $configuracion,
        "asignacion" => $asignacion,
        "dry_caja" => $dryCaja,
        "dry_terminal" => $dryTerminal,
        "dry_asignacion" => $dryAsignacion
    );
}

responder($salida);

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
