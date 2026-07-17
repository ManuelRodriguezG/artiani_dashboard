<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: consolidar preflight read-only para autorizacion del esquema base Ventas/POS/Pedidos.
 * Impacto: valida respaldo, paquete DDL base, semillas propuestas y guardrails sin escribir BD.
 * Contrato: no ejecuta DDL, no inserta semillas, no abre turnos y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "";
$idUsuario = 0;
foreach ($args as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$validacionRespaldo = validarRespaldo($respaldo);
$planDdlBase = $esquema->planActualizarVentasPos(false, "base");
$planDdlExpandido = $esquema->planActualizarVentasPos(false, "expandido");
$planCajas = $ventas->planCajasInicialesPos();
$planTerminales = $ventas->planAsignacionTerminalPos(array(
    "id_usuario" => $idUsuario,
    "usuario_nombre" => $idUsuario > 0 ? "Usuario POS " . $idUsuario : "Usuario POS por asignar"
));
$postConfig = $esquema->auditarVentasPos("base");
$tablasBasePendientes = tablasPendientes(valor($postConfig, array("depurar"), array()));
$schemaBaseAplicado = empty($tablasBasePendientes);

$ddlBase = extraerSql($planDdlBase);
$ddlExpandido = extraerSql($planDdlExpandido);
$propuestasCajas = valor($planCajas, array("depurar", "propuestas"), array());
$propuestasTerminales = valor($planTerminales, array("depurar", "propuestas"), array());
$asignacionesSinUsuario = array();
foreach ($propuestasTerminales as $propuesta) {
    $idAsignado = intval(valor($propuesta, array("asignacion_sugerida", "id_usuario"), 0));
    if ($idAsignado <= 0) {
        $asignacionesSinUsuario[] = valor($propuesta, array("codigo_almacen"), "sin_codigo");
    }
}

$bloqueos = array();
if (!$validacionRespaldo["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}
if ($idUsuario <= 0) {
    $bloqueos[] = "Falta --id_usuario=ID para sembrar asignaciones POS";
}
if (!empty($asignacionesSinUsuario)) {
    $bloqueos[] = "Hay asignaciones sin usuario real: " . implode(", ", $asignacionesSinUsuario);
}
if (count($ddlBase) !== 11 && !$schemaBaseAplicado) {
    $bloqueos[] = "DDL base esperado 11 tablas, recibido " . count($ddlBase);
}
if (count($ddlExpandido) !== 21 && !$schemaBaseAplicado) {
    $bloqueos[] = "DDL expandido esperado 21 tablas, recibido " . count($ddlExpandido);
}
if (count($propuestasCajas) <= 0) {
    $bloqueos[] = "No hay cajas propuestas; revisar almacenes con permite_venta=1";
}
if (count($propuestasTerminales) <= 0) {
    $bloqueos[] = "No hay terminales/asignaciones propuestas";
}

echo json_encode(array(
    "ok" => empty($bloqueos)
        && !$planCajas["error"]
        && !$planTerminales["error"]
        && !$postConfig["error"],
    "modo" => "read-only",
    "alcance_recomendado" => "base",
    "id_usuario" => $idUsuario,
    "respaldo" => $validacionRespaldo,
    "ddl" => array(
        "base_total" => count($ddlBase),
        "expandido_total" => count($ddlExpandido),
        "schema_base_aplicado" => $schemaBaseAplicado,
        "base_no_incluye_clientes" => !contieneTabla($ddlBase, "erp_clientes"),
        "base_no_incluye_atenciones" => !contieneTabla($ddlBase, "erp_pos_atenciones"),
        "expandido_incluye_clientes" => contieneTabla($ddlExpandido, "erp_clientes"),
        "expandido_incluye_atenciones" => contieneTabla($ddlExpandido, "erp_pos_atenciones")
    ),
    "semillas" => array(
        "cajas_total" => count($propuestasCajas),
        "terminales_total" => count($propuestasTerminales),
        "asignaciones_total" => count($propuestasTerminales),
        "asignaciones_sin_usuario" => $asignacionesSinUsuario
    ),
    "auditoria_base" => array(
        "tablas_pendientes" => $tablasBasePendientes
    ),
    "guardrails" => array(
        "token_base" => "VENTAS_POS_DDL_BASE",
        "token_expandido" => "VENTAS_POS_DDL_EXPANDIDO",
        "token_legacy_bloqueado" => "VENTAS_POS_DDL"
    ),
    "bloqueos" => $bloqueos,
    "siguiente_paso" => empty($bloqueos)
        ? ($schemaBaseAplicado ? "Esquema base ya aplicado; no solicitar DDL base nuevamente." : "Listo para solicitar autorizacion textual del esquema base.")
        : "Resolver bloqueos antes de autorizar esquema base."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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
        "referencia" => $respaldo,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function extraerSql($plan) {
    $sql = array();
    foreach ($plan as $paso) {
        $sentencia = valor($paso, array("depurar", "sql"), "");
        if ($sentencia !== "") {
            $sql[] = $sentencia;
        }
    }
    return $sql;
}

function contieneTabla($sqls, $tabla) {
    foreach ($sqls as $sql) {
        if (strpos($sql, "`" . $tabla . "`") !== false) {
            return true;
        }
    }
    return false;
}

function tablasPendientes($tablas) {
    $pendientes = array();
    foreach ($tablas as $tabla) {
        if (empty($tabla["existe"])) {
            $pendientes[] = $tabla["tabla"];
        }
    }
    return $pendientes;
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
