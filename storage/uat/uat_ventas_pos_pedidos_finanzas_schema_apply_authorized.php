<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: aplicar DDL de decisiones financieras para pedidos/apartados POS cancelados.
 * Impacto: crea `erp_ventas_pedidos_decisiones_financieras` si no existe.
 * Contrato: bloqueado por token; no resuelve decisiones, no mueve caja, no crea saldos y no toca inventario.
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
if ($autorizar !== "VENTAS_POS_PEDIDOS_FINANZAS_DDL" || !$validacionRespaldo["ok"]) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de decisiones financieras de pedidos POS.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_PEDIDOS_FINANZAS_DDL",
            "--respaldo=RUTA_O_REFERENCIA"
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPedidosFinanzasSchemaApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPedidosFinanzasSchemaApplyDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Conexion BD no disponible.",
        "contrato" => contrato(false)
    ));
}

$antes = auditar($db);
$ddl = ddlTabla();
$plan = array();

try {
    $db->exec($ddl);
    $plan[] = array(
        "accion" => "crear_tabla_si_no_existe",
        "tabla" => "erp_ventas_pedidos_decisiones_financieras",
        "ejecutado" => true,
        "error" => false
    );
} catch (Exception $e) {
    $plan[] = array(
        "accion" => "crear_tabla_si_no_existe",
        "tabla" => "erp_ventas_pedidos_decisiones_financieras",
        "ejecutado" => false,
        "error" => true,
        "mensaje" => $e->getMessage()
    );
}

$despues = auditar($db);

responder(array(
    "ok" => !hayErrores($plan) && !empty($despues["tabla"]["existe"]),
    "modo" => "pedidos_finanzas_schema_apply_authorized",
    "respaldo_ref" => $respaldo,
    "validacion_respaldo" => $validacionRespaldo,
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "contrato" => contrato(true),
    "siguiente_paso" => "Crear dry-run de solicitud/aplicacion de decision financiera; no resolver dinero hasta nueva autorizacion."
));

function auditar($db) {
    $tabla = "erp_ventas_pedidos_decisiones_financieras";
    $columnas = array(
        "id_decision_financiera", "folio", "id_venta", "folio_venta", "tipo_documento",
        "id_cliente_crm", "cliente_snapshot", "decision", "monto_base", "monto_saldo_favor",
        "monto_reembolso", "monto_penalizacion", "id_turno_caja", "id_caja", "id_almacen",
        "id_movimiento_caja", "id_venta_pago", "id_saldo_cliente_movimiento", "estatus",
        "motivo", "evidencia_referencia", "datos_snapshot", "solicitado_por", "autorizado_por",
        "aplicado_por", "fecha_solicitud", "fecha_autorizacion", "fecha_aplicacion", "fecha_actualizacion"
    );
    $indices = array(
        "PRIMARY", "idx_ped_fin_folio", "idx_ped_fin_venta_unica", "idx_ped_fin_estado",
        "idx_ped_fin_caja", "idx_ped_fin_cliente", "idx_ped_fin_mov_caja"
    );
    $existe = tablaExiste($db, $tabla);
    $resultado = array("tabla" => array("nombre" => $tabla, "existe" => $existe), "columnas" => array(), "indices" => array());
    foreach ($columnas as $columna) {
        $resultado["columnas"][] = array("columna" => $columna, "existe" => $existe && columnaExiste($db, $tabla, $columna));
    }
    foreach ($indices as $indice) {
        $resultado["indices"][] = array("indice" => $indice, "existe" => $existe && indiceExiste($db, $tabla, $indice));
    }
    return $resultado;
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function columnaExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna");
    $stmt->execute(array(":tabla" => $tabla, ":columna" => $columna));
    return intval($stmt->fetchColumn()) > 0;
}

function indiceExiste($db, $tabla, $indice) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla AND INDEX_NAME=:indice");
    $stmt->execute(array(":tabla" => $tabla, ":indice" => $indice));
    return intval($stmt->fetchColumn()) > 0;
}

function ddlTabla() {
    return "CREATE TABLE IF NOT EXISTS `erp_ventas_pedidos_decisiones_financieras` (
        `id_decision_financiera` BIGINT NOT NULL AUTO_INCREMENT,
        `folio` VARCHAR(50) NOT NULL,
        `id_venta` BIGINT NOT NULL,
        `folio_venta` VARCHAR(40) NOT NULL,
        `tipo_documento` VARCHAR(30) NOT NULL,
        `id_cliente_crm` BIGINT NULL,
        `cliente_snapshot` TEXT NULL,
        `decision` VARCHAR(40) NOT NULL,
        `monto_base` DECIMAL(18,6) NOT NULL DEFAULT 0,
        `monto_saldo_favor` DECIMAL(18,6) NOT NULL DEFAULT 0,
        `monto_reembolso` DECIMAL(18,6) NOT NULL DEFAULT 0,
        `monto_penalizacion` DECIMAL(18,6) NOT NULL DEFAULT 0,
        `id_turno_caja` BIGINT NULL,
        `id_caja` INT NULL,
        `id_almacen` INT NULL,
        `id_movimiento_caja` BIGINT NULL,
        `id_venta_pago` BIGINT NULL,
        `id_saldo_cliente_movimiento` BIGINT NULL,
        `estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente',
        `motivo` TEXT NULL,
        `evidencia_referencia` VARCHAR(250) NULL,
        `datos_snapshot` TEXT NULL,
        `solicitado_por` INT NULL,
        `autorizado_por` INT NULL,
        `aplicado_por` INT NULL,
        `fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `fecha_autorizacion` DATETIME NULL,
        `fecha_aplicacion` DATETIME NULL,
        `fecha_actualizacion` DATETIME NULL,
        PRIMARY KEY (`id_decision_financiera`),
        UNIQUE KEY `idx_ped_fin_folio` (`folio`),
        UNIQUE KEY `idx_ped_fin_venta_unica` (`id_venta`),
        KEY `idx_ped_fin_estado` (`estatus`, `decision`, `fecha_solicitud`),
        KEY `idx_ped_fin_caja` (`id_turno_caja`, `id_caja`, `estatus`),
        KEY `idx_ped_fin_cliente` (`id_cliente_crm`, `estatus`),
        KEY `idx_ped_fin_mov_caja` (`id_movimiento_caja`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
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

function contrato($aplicoDdl) {
    return array(
        "aplico_ddl" => $aplicoDdl,
        "no_resuelve_decision" => true,
        "no_mueve_caja" => true,
        "no_genera_saldo_favor" => true,
        "no_penaliza" => true,
        "no_mueve_inventario" => true
    );
}

function hayErrores($plan) {
    foreach ($plan as $paso) {
        if (!empty($paso["error"])) {
            return true;
        }
    }
    return false;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
