<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-18.
 * Proposito: aplicar DDL acotado para modo de inventario por caja POS.
 * Impacto: agrega metadatos de operacion en `erp_pos_cajas`; no toca ventas, kardex, stock, pagos ni turnos.
 * Contrato: BLOQUEADO por defecto; requiere --autorizar=VENTAS_POS_MODO_INVENTARIO_PILOTO_DDL y respaldo valido.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_MODO_INVENTARIO_PILOTO_DDL" || !$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de modo inventario POS. Falta autorizacion explicita o respaldo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_MODO_INVENTARIO_PILOTO_DDL",
            "--respaldo=RUTA_O_REFERENCIA"
        ),
        "validacion_respaldo" => $validacionRespaldo
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$schema = new DBSchema();
$antes = auditoriaModoInventarioCaja($schema);
$plan = array();
$plan[] = $schema->agregarColumnaSiNoExiste("erp_pos_cajas", "afectar_inventario", "TINYINT(1) NOT NULL DEFAULT 1 AFTER `permite_transferencia`", true);
$plan[] = $schema->agregarColumnaSiNoExiste("erp_pos_cajas", "modo_operacion_inventario", "VARCHAR(40) NOT NULL DEFAULT 'normal' AFTER `afectar_inventario`", true);
$plan[] = $schema->agregarColumnaSiNoExiste("erp_pos_cajas", "generar_alertas_inventario", "TINYINT(1) NOT NULL DEFAULT 1 AFTER `modo_operacion_inventario`", true);
$plan[] = $schema->agregarIndiceSiNoExiste("erp_pos_cajas", "idx_pos_caja_modo_inv", "KEY `idx_pos_caja_modo_inv` (`afectar_inventario`, `modo_operacion_inventario`, `estatus`)", true);
$despues = auditoriaModoInventarioCaja($schema);

responder(array(
    "ok" => true,
    "modo" => "ddl_modo_inventario_pos_aplicado",
    "respaldo_ref" => $respaldo,
    "antes" => $antes,
    "plan" => $plan,
    "despues" => $despues,
    "siguiente_paso" => "Configurar la caja POS como normal o piloto sin afectar inventario desde Ventas > Configuracion POS > Caja."
));

function auditoriaModoInventarioCaja($schema) {
    return array(
        "tabla_erp_pos_cajas" => $schema->tablaExiste("erp_pos_cajas"),
        "columnas" => array(
            "afectar_inventario" => $schema->columnaExiste("erp_pos_cajas", "afectar_inventario"),
            "modo_operacion_inventario" => $schema->columnaExiste("erp_pos_cajas", "modo_operacion_inventario"),
            "generar_alertas_inventario" => $schema->columnaExiste("erp_pos_cajas", "generar_alertas_inventario")
        ),
        "indices" => array(
            "idx_pos_caja_modo_inv" => $schema->indiceExiste("erp_pos_cajas", "idx_pos_caja_modo_inv")
        )
    );
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

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
