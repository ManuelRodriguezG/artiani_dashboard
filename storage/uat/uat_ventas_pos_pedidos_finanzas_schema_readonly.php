<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: auditar esquema propuesto para decisiones financieras de pedidos/apartados cancelados.
 * Impacto: solo lee INFORMATION_SCHEMA y emite DDL sugerido; no modifica BD.
 * Contrato: read-only; no crea tablas, columnas ni indices.
 */

$compacto = in_array("--compact=1", isset($argv) ? $argv : array(), true) || in_array("--compacto=1", isset($argv) ? $argv : array(), true);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPedidosFinanzasSchemaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPedidosFinanzasSchemaDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedidos_finanzas_schema_readonly",
        "read_only" => true,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contrato()
    ));
}

$tabla = "erp_ventas_pedidos_decisiones_financieras";
$columnas = array(
    "id_decision_financiera",
    "folio",
    "id_venta",
    "folio_venta",
    "tipo_documento",
    "id_cliente_crm",
    "cliente_snapshot",
    "decision",
    "monto_base",
    "monto_saldo_favor",
    "monto_reembolso",
    "monto_penalizacion",
    "id_turno_caja",
    "id_caja",
    "id_almacen",
    "id_movimiento_caja",
    "id_venta_pago",
    "id_saldo_cliente_movimiento",
    "estatus",
    "motivo",
    "evidencia_referencia",
    "datos_snapshot",
    "solicitado_por",
    "autorizado_por",
    "aplicado_por",
    "fecha_solicitud",
    "fecha_autorizacion",
    "fecha_aplicacion",
    "fecha_actualizacion"
);
$indices = array(
    "PRIMARY",
    "idx_ped_fin_folio",
    "idx_ped_fin_venta_unica",
    "idx_ped_fin_estado",
    "idx_ped_fin_caja",
    "idx_ped_fin_cliente",
    "idx_ped_fin_mov_caja"
);

$tablaExiste = tablaExiste($db, $tabla);
$auditoria = array(
    "tabla" => array("nombre" => $tabla, "existe" => $tablaExiste),
    "columnas" => array(),
    "indices" => array()
);

foreach ($columnas as $columna) {
    $auditoria["columnas"][] = array(
        "columna" => $columna,
        "existe" => $tablaExiste && columnaExiste($db, $tabla, $columna)
    );
}
foreach ($indices as $indice) {
    $auditoria["indices"][] = array(
        "indice" => $indice,
        "existe" => $tablaExiste && indiceExiste($db, $tabla, $indice)
    );
}

$faltantes = array(
    "tabla" => !$tablaExiste,
    "columnas" => array_values(array_filter($auditoria["columnas"], function ($item) { return !$item["existe"]; })),
    "indices" => array_values(array_filter($auditoria["indices"], function ($item) { return !$item["existe"]; }))
);

$salida = array(
    "ok" => true,
    "modo" => "pedidos_finanzas_schema_readonly",
    "read_only" => true,
    "auditoria" => $auditoria,
    "faltantes" => $faltantes,
    "ddl_sugerido" => ddlSugerido(),
    "hallazgos" => hallazgos($faltantes),
    "contrato" => contrato()
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "pedidos_finanzas_schema_readonly",
        "read_only" => true,
        "tabla_existe" => $tablaExiste,
        "columnas_faltantes" => count($faltantes["columnas"]),
        "indices_faltantes" => count($faltantes["indices"]),
        "hallazgos" => $salida["hallazgos"],
        "contrato" => contrato()
    );
}

responder($salida);

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

function hallazgos($faltantes) {
    $hallazgos = array();
    if ($faltantes["tabla"]) {
        $hallazgos[] = "Falta tabla erp_ventas_pedidos_decisiones_financieras.";
    }
    if (!$faltantes["tabla"] && !empty($faltantes["columnas"])) {
        $hallazgos[] = "Faltan columnas en tabla de decisiones financieras.";
    }
    if (!$faltantes["tabla"] && !empty($faltantes["indices"])) {
        $hallazgos[] = "Faltan indices en tabla de decisiones financieras.";
    }
    return $hallazgos;
}

function ddlSugerido() {
    return "CREATE TABLE IF NOT EXISTS `erp_ventas_pedidos_decisiones_financieras` (\n"
        . "  `id_decision_financiera` BIGINT NOT NULL AUTO_INCREMENT,\n"
        . "  `folio` VARCHAR(50) NOT NULL,\n"
        . "  `id_venta` BIGINT NOT NULL,\n"
        . "  `folio_venta` VARCHAR(40) NOT NULL,\n"
        . "  `tipo_documento` VARCHAR(30) NOT NULL,\n"
        . "  `id_cliente_crm` BIGINT NULL,\n"
        . "  `cliente_snapshot` TEXT NULL,\n"
        . "  `decision` VARCHAR(40) NOT NULL,\n"
        . "  `monto_base` DECIMAL(18,6) NOT NULL DEFAULT 0,\n"
        . "  `monto_saldo_favor` DECIMAL(18,6) NOT NULL DEFAULT 0,\n"
        . "  `monto_reembolso` DECIMAL(18,6) NOT NULL DEFAULT 0,\n"
        . "  `monto_penalizacion` DECIMAL(18,6) NOT NULL DEFAULT 0,\n"
        . "  `id_turno_caja` BIGINT NULL,\n"
        . "  `id_caja` INT NULL,\n"
        . "  `id_almacen` INT NULL,\n"
        . "  `id_movimiento_caja` BIGINT NULL,\n"
        . "  `id_venta_pago` BIGINT NULL,\n"
        . "  `id_saldo_cliente_movimiento` BIGINT NULL,\n"
        . "  `estatus` VARCHAR(40) NOT NULL DEFAULT 'pendiente',\n"
        . "  `motivo` TEXT NULL,\n"
        . "  `evidencia_referencia` VARCHAR(250) NULL,\n"
        . "  `datos_snapshot` TEXT NULL,\n"
        . "  `solicitado_por` INT NULL,\n"
        . "  `autorizado_por` INT NULL,\n"
        . "  `aplicado_por` INT NULL,\n"
        . "  `fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n"
        . "  `fecha_autorizacion` DATETIME NULL,\n"
        . "  `fecha_aplicacion` DATETIME NULL,\n"
        . "  `fecha_actualizacion` DATETIME NULL,\n"
        . "  PRIMARY KEY (`id_decision_financiera`),\n"
        . "  UNIQUE KEY `idx_ped_fin_folio` (`folio`),\n"
        . "  UNIQUE KEY `idx_ped_fin_venta_unica` (`id_venta`),\n"
        . "  KEY `idx_ped_fin_estado` (`estatus`, `decision`, `fecha_solicitud`),\n"
        . "  KEY `idx_ped_fin_caja` (`id_turno_caja`, `id_caja`, `estatus`),\n"
        . "  KEY `idx_ped_fin_cliente` (`id_cliente_crm`, `estatus`),\n"
        . "  KEY `idx_ped_fin_mov_caja` (`id_movimiento_caja`)\n"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
}

function contrato() {
    return array(
        "no_escribe_bd" => true,
        "no_crea_tablas" => true,
        "no_altera_tablas" => true,
        "no_resuelve_decision" => true,
        "no_mueve_caja" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
