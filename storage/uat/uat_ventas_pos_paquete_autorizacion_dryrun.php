<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: generar paquete dry-run de autorizacion para Ventas/POS/Pedidos.
 * Impacto: lista DDL propuesto y semillas de cajas sin ejecutar cambios.
 * Contrato: read-only; no ejecuta DDL, no inserta datos y no modifica inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

$args = isset($argv) ? $argv : array();
$idUsuarioDefault = 0;
$alcance = "base";
$usuariosPorAlmacen = array();
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuarioDefault = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--usuario_almacen=") === 0) {
        $valor = trim(substr($arg, 18), "\"' ");
        $partes = explode(":", $valor, 2);
        if (count($partes) === 2) {
            $usuariosPorAlmacen[strtoupper(trim($partes[0]))] = intval($partes[1]);
        }
    }
    if (strpos($arg, "--alcance=") === 0) {
        $valorAlcance = strtolower(trim(substr($arg, 10), "\"' "));
        $alcance = $valorAlcance === "base" ? "base" : "expandido";
    }
}

$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$planDdl = $esquema->planActualizarVentasPos(false, $alcance);
$planCajas = $ventas->planCajasInicialesPos();
$planTerminales = $ventas->planAsignacionTerminalPos(array(
    "id_usuario" => $idUsuarioDefault,
    "usuario_nombre" => $idUsuarioDefault > 0 ? "Usuario POS " . $idUsuarioDefault : "Usuario POS por asignar"
));
$diagnostico = $ventas->diagnosticoModuloVentas();

$ddl = array();
foreach ($planDdl as $paso) {
    $sql = isset($paso["depurar"]["sql"]) ? $paso["depurar"]["sql"] : null;
    if ($sql) {
        $ddl[] = $sql;
    }
}

$semillas = array();
foreach (valor($planCajas, array("depurar", "propuestas"), array()) as $propuesta) {
    if (empty($propuesta["crear"])) {
        continue;
    }
    $caja = $propuesta["caja_sugerida"];
    $semillas[] = "INSERT INTO `erp_pos_cajas` (`codigo`, `nombre`, `id_almacen`, `estatus`, `permite_efectivo`, `permite_tarjeta`, `permite_transferencia`) VALUES ("
        . sqlQuote($caja["codigo"]) . ", "
        . sqlQuote($caja["nombre"]) . ", "
        . intval($caja["id_almacen"]) . ", "
        . sqlQuote($caja["estatus"]) . ", "
        . intval($caja["permite_efectivo"]) . ", "
        . intval($caja["permite_tarjeta"]) . ", "
        . intval($caja["permite_transferencia"]) . ");";
}

$semillasTerminales = array();
$semillasAsignaciones = array();
foreach (valor($planTerminales, array("depurar", "propuestas"), array()) as $propuesta) {
    $terminal = $propuesta["terminal_sugerida"];
    $asignacion = $propuesta["asignacion_sugerida"];
    $codigoAlmacen = strtoupper((string) $propuesta["codigo_almacen"]);
    $idUsuarioAsignacion = isset($usuariosPorAlmacen[$codigoAlmacen])
        ? intval($usuariosPorAlmacen[$codigoAlmacen])
        : intval($asignacion["id_usuario"]);
    $semillasTerminales[] = "INSERT INTO `erp_pos_terminales` (`codigo`, `nombre`, `id_almacen`, `id_caja`, `estatus`) SELECT "
        . sqlQuote($terminal["codigo"]) . ", "
        . sqlQuote($terminal["nombre"]) . ", "
        . intval($terminal["id_almacen"]) . ", "
        . "`id_caja`, "
        . sqlQuote($terminal["estatus"]) . " FROM `erp_pos_cajas` WHERE `codigo` = "
        . sqlQuote($terminal["caja_codigo"]) . " LIMIT 1;";
    $semillasAsignaciones[] = "INSERT INTO `erp_pos_usuarios_cajas` (`id_usuario`, `id_almacen`, `id_caja`, `id_terminal_pos`, `estatus`, `prioridad`) SELECT "
        . $idUsuarioAsignacion . ", "
        . intval($asignacion["id_almacen"]) . ", "
        . "c.`id_caja`, "
        . "t.`id_terminal_pos`, "
        . sqlQuote($asignacion["estatus"]) . ", "
        . intval($asignacion["prioridad"])
        . " FROM `erp_pos_cajas` c LEFT JOIN `erp_pos_terminales` t ON t.`codigo` = "
        . sqlQuote($asignacion["terminal_codigo"])
        . " WHERE c.`codigo` = " . sqlQuote($asignacion["caja_codigo"]) . " LIMIT 1;";
}

$asignacionesListas = !empty($semillasAsignaciones) && !contieneUsuarioCero($semillasAsignaciones);

$salida = array(
    "ok" => !$planCajas["error"] && !$planTerminales["error"] && !$diagnostico["error"],
    "modo" => "dry-run",
    "alcance" => $alcance,
    "advertencia" => "No ejecutar sin respaldo externo y autorizacion explicita.",
    "listo_para_autorizacion_configuracion" => $asignacionesListas,
    "diagnostico_hallazgos" => ids(valor($diagnostico, array("depurar", "hallazgos"), array())),
    "ddl_total" => count($ddl),
    "ddl" => $ddl,
    "seed_cajas_total" => count($semillas),
    "seed_cajas" => $semillas,
    "seed_terminales_total" => count($semillasTerminales),
    "seed_terminales" => $semillasTerminales,
    "seed_asignaciones_total" => count($semillasAsignaciones),
    "seed_asignaciones" => $semillasAsignaciones,
    "nota_asignaciones" => $asignacionesListas
        ? "Las semillas de asignacion tienen usuario ERP definido."
        : "Falta usuario ERP real. Usa --id_usuario=ID para un usuario comun o --usuario_almacen=CODIGO:ID por sucursal.",
    "orden_recomendado" => array(
        "1. Verificar respaldo externo",
        "2. Ejecutar DDL de tablas Ventas/POS",
        "3. Ejecutar seed de cajas iniciales",
        "4. Ejecutar seed de terminales",
        "5. Ejecutar seed de asignacion usuario/caja/terminal",
        "6. Abrir turno UAT",
        "7. Ejecutar UAT de venta/reserva/devolucion"
    )
);

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function sqlQuote($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
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

function ids($hallazgos) {
    return array_values(array_filter(array_map(function ($item) {
        return isset($item["id"]) ? $item["id"] : null;
    }, $hallazgos)));
}

function contieneUsuarioCero($sentencias) {
    foreach ($sentencias as $sql) {
        if (strpos($sql, "SELECT 0,") !== false) {
            return true;
        }
    }
    return false;
}
