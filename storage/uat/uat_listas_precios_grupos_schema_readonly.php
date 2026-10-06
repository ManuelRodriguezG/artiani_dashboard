<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-10-04.
 * Proposito: auditar preparacion de grupos comerciales persistentes para Listas de precios.
 * Impacto: genera diagnostico y SQL propuesto sin crear tablas ni modificar productivo.
 * Contrato: read-only; no ejecuta DDL, no inserta datos, no cambia listas ni precios.
 */

$compacto = in_array("--compact=1", isset($argv) ? $argv : array(), true);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$auditoria = $modelo->gruposComercialesSchemaReadOnly();
$depurar = isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array();

$ddl = array(
    "CREATE TABLE IF NOT EXISTS `erp_comercial_grupos_productos` (
  `id_grupo_producto` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(60) NOT NULL,
  `nombre` VARCHAR(180) NOT NULL,
  `tipo_grupo` ENUM('manual','dinamico','incidencia','sistema') NOT NULL DEFAULT 'manual',
  `descripcion` TEXT NULL,
  `estatus` ENUM('borrador','activo','pausado','cancelado') NOT NULL DEFAULT 'borrador',
  `creado_por` INT NULL,
  `actualizado_por` INT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_grupo_producto`),
  UNIQUE KEY `uk_comercial_grupo_codigo` (`codigo`),
  KEY `idx_comercial_grupo_tipo_estatus` (`tipo_grupo`,`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `erp_comercial_grupos_productos_reglas` (
  `id_grupo_regla` INT NOT NULL AUTO_INCREMENT,
  `id_grupo_producto` INT NOT NULL,
  `tipo_regla` ENUM('categoria','proveedor','marca','sku','producto','margen','incidencia','sin_precio','sin_costo') NOT NULL,
  `operador` ENUM('igual','distinto','contiene','menor_que','mayor_que','entre') NOT NULL DEFAULT 'igual',
  `valor` VARCHAR(180) NOT NULL,
  `valor_hasta` VARCHAR(180) NULL,
  `estatus` ENUM('activo','pausado','cancelado') NOT NULL DEFAULT 'activo',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_grupo_regla`),
  KEY `idx_grupo_regla_grupo` (`id_grupo_producto`,`estatus`),
  KEY `idx_grupo_regla_tipo` (`tipo_regla`,`operador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `erp_comercial_grupos_productos_items` (
  `id_grupo_item` INT NOT NULL AUTO_INCREMENT,
  `id_grupo_producto` INT NOT NULL,
  `id_sku` INT NULL,
  `id_producto_erp` INT NULL,
  `origen_item` ENUM('manual','regla','incidencia','importacion') NOT NULL DEFAULT 'manual',
  `estatus` ENUM('activo','pausado','cancelado') NOT NULL DEFAULT 'activo',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_grupo_item`),
  UNIQUE KEY `uk_grupo_item_sku` (`id_grupo_producto`,`id_sku`),
  KEY `idx_grupo_item_producto` (`id_grupo_producto`,`id_producto_erp`),
  KEY `idx_grupo_item_estatus` (`id_grupo_producto`,`estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    "CREATE TABLE IF NOT EXISTS `erp_comercial_grupos_productos_eventos` (
  `id_evento` INT NOT NULL AUTO_INCREMENT,
  `id_grupo_producto` INT NULL,
  `accion` VARCHAR(80) NOT NULL,
  `entidad` VARCHAR(100) NOT NULL DEFAULT 'erp_comercial_grupos_productos',
  `datos_antes` JSON NULL,
  `datos_despues` JSON NULL,
  `id_usuario` INT NULL,
  `origen` VARCHAR(120) NOT NULL DEFAULT 'erp_listas_precios_grupos',
  `fecha_evento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_evento`),
  KEY `idx_grupo_evento_grupo` (`id_grupo_producto`,`fecha_evento`),
  KEY `idx_grupo_evento_accion` (`accion`,`fecha_evento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$salida = array(
    "ok" => true,
    "modo" => "listas_precios_grupos_schema_readonly",
    "read_only" => true,
    "proyecto_canonico" => "C:\\xampp\\htdocs\\panel_de_control",
    "host" => "http://panel.com.local/",
    "auditoria" => $depurar,
    "ddl_propuesto" => $ddl,
    "ddl_pasos_generados" => count($ddl),
    "siguiente_autorizacion" => "Autorizo generar respaldo externo productivo y aplicar DDL de grupos comerciales de Listas de precios con token VENTAS_LISTAS_PRECIOS_GRUPOS_DDL.",
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_crea_grupos" => true,
        "no_modifica_listas" => true,
        "no_modifica_precios" => true,
        "no_modifica_ventas_pasadas" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "listas_precios_grupos_schema_readonly",
        "read_only" => true,
        "preparado" => isset($depurar["preparado"]) ? $depurar["preparado"] : false,
        "faltantes" => isset($depurar["faltantes"]) && is_array($depurar["faltantes"]) ? count($depurar["faltantes"]) : 0,
        "ddl_pasos_generados" => count($ddl),
        "siguiente_autorizacion" => $salida["siguiente_autorizacion"],
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

