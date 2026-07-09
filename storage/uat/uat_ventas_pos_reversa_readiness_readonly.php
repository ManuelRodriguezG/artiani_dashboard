<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: validar readiness de una devolucion/cancelacion POS real sin escribir BD.
 * Impacto: combina esquema, venta original, cantidad, motivo, decision financiera, caja y contrato de inventario.
 * Contrato: read-only; no crea devolucion, no reembolsa, no mueve inventario y no actualiza ventas.
 */

$args = isset($argv) ? $argv : array();
$folio = "POS-20260629-000003";
$idVenta = 0;
$idUsuario = 1;
$idDetalle = 8;
$cantidad = 1;
$tipo = "devolucion";
$motivo = "UAT readiness reversa POS";
$decisionInventario = "cuarentena";
$decisionFinanciera = "reembolso_caja";
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_venta_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--decision_inventario=") === 0) {
        $decisionInventario = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--decision_financiera=") === 0) {
        $decisionFinanciera = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";
require_once "../app/controladores/Ventas.php";

class VentasPosReversaReadinessDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new VentasPosReversaReadinessDb();
$esquema = new VentasErpEsquema();
$db = $ventas->db();
$bloqueos = array();
$avisos = array();

$respaldoValidado = validarRespaldo($respaldo);
if (!$respaldoValidado["ok"]) {
    $bloqueos[] = "Respaldo externo no valido o no legible";
}

$endpoints = array(
    "devolucion_dryrun_erp" => method_exists("Ventas", "devolucion_dryrun_erp"),
    "esquema_auditar_reversas_pos" => method_exists("Ventas", "esquema_auditar_reversas_pos"),
    "esquema_actualizar_reversas_pos" => method_exists("Ventas", "esquema_actualizar_reversas_pos")
);
foreach ($endpoints as $metodo => $existe) {
    if (!$existe) {
        $bloqueos[] = "Falta endpoint Ventas." . $metodo;
    }
}

$auditoria = $esquema->auditarReversasPos();
$faltantes = faltantesAuditoria($auditoria);
if (!empty($faltantes)) {
    $bloqueos[] = "Falta aplicar DDL reversas POS antes de reversa real";
}
$siguientePaso = !empty($faltantes)
    ? "DDL: AUTORIZO APLICAR DDL REVERSAS POS usando respaldo [RUTA] con token VENTAS_POS_REVERSA_DDL para UAT POS"
    : "Implementar/aplicar reversa real POS con autorizacion especifica; este readiness no escribe BD.";

$items = array();
if ($idDetalle > 0 || $cantidad > 0) {
    $items[] = array(
        "id_venta_detalle" => $idDetalle,
        "cantidad_base" => $cantidad
    );
}
$dryRun = $ventas->devolucionDryRun(array(
    "folio" => $folio,
    "id_venta" => $idVenta,
    "id_usuario" => $idUsuario,
    "tipo" => $tipo,
    "motivo" => $motivo,
    "decision_inventario" => $decisionInventario,
    "items" => json_encode($items)
));
foreach (valor($dryRun, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
foreach (valor($dryRun, array("depurar", "avisos"), array()) as $aviso) {
    $avisos[] = $aviso;
}

if (!in_array($decisionFinanciera, array("reembolso_caja", "saldo_favor", "cambio_producto", "sin_reembolso"), true)) {
    $bloqueos[] = "Decision financiera invalida";
}
if ($decisionFinanciera === "reembolso_caja") {
    $ventaDryRun = valor($dryRun, array("depurar", "venta"), array());
    $movCaja = $ventas->movimientoCajaDryRun(array(
        "id_usuario" => $idUsuario,
        "id_almacen" => intval(valor($ventaDryRun, array("id_almacen"), 0)),
        "id_caja" => intval(valor($ventaDryRun, array("id_caja"), 0)),
        "id_turno_caja" => intval(valor($ventaDryRun, array("id_turno_caja"), 0)),
        "tipo_movimiento" => "reembolso_cliente",
        "monto" => valor($dryRun, array("depurar", "totales", "reembolso_estimado"), 0),
        "referencia" => $folio,
        "motivo" => "Reembolso por " . $tipo . " POS",
        "responsable" => "UAT POS",
        "observaciones" => $motivo
    ));
    foreach (valor($movCaja, array("depurar", "bloqueos"), array()) as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
} else {
    $movCaja = null;
}

responder(array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "ventas_pos_reversa_readiness_readonly",
    "read_only" => true,
    "respaldo" => $respaldoValidado,
    "endpoints" => $endpoints,
    "schema" => array(
        "auditoria" => $auditoria,
        "faltantes" => $faltantes,
        "plan" => $esquema->planActualizarReversasPos(false)
    ),
    "payload" => array(
        "folio" => $folio,
        "id_venta" => $idVenta,
        "id_usuario" => $idUsuario,
        "tipo" => $tipo,
        "motivo" => $motivo,
        "decision_inventario" => $decisionInventario,
        "decision_financiera" => $decisionFinanciera,
        "items" => $items
    ),
    "dry_run_reversa" => resumenRespuesta($dryRun),
    "dry_run_caja" => $movCaja ? resumenRespuesta($movCaja) : null,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "siguiente_paso" => $siguientePaso
));

function faltantesAuditoria($auditoria) {
    $faltantes = array();
    $depurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();
    foreach (array("tablas", "columnas", "indices") as $grupo) {
        foreach (isset($depurar[$grupo]) ? $depurar[$grupo] : array() as $item) {
            if (empty($item["existe"])) {
                $faltantes[] = $item;
            }
        }
    }
    return $faltantes;
}

function resumenRespuesta($respuesta) {
    return array(
        "error" => isset($respuesta["error"]) ? $respuesta["error"] : null,
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : null,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null,
        "bloqueos" => valor($respuesta, array("depurar", "bloqueos"), array()),
        "avisos" => valor($respuesta, array("depurar", "avisos"), array()),
        "totales" => valor($respuesta, array("depurar", "totales"), null)
    );
}

function validarRespaldo($ruta) {
    return array(
        "ruta" => $ruta,
        "ok" => is_string($ruta) && $ruta !== "" && is_file($ruta),
        "existe" => is_string($ruta) && $ruta !== "" ? is_file($ruta) : false
    );
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

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
