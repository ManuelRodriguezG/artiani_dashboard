<?php

class ProduccionEsquema extends DBSchema {

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: listar tablas requeridas para persistir proyectos de peceras y pedidos tecnicos de vidrio.
     * Impacto: Produccion/Fabricacion; prepara persistencia sin mezclar Compras ni Inventario.
     * Contrato: solo devuelve nombres de tablas; no consulta ni modifica BD.
     */
    public function tablasPeceras() {
        return array(
            "erp_produccion_peceras",
            "erp_produccion_peceras_piezas",
            "erp_produccion_peceras_eventos"
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: declarar columnas minimas del MVP persistente de peceras.
     * Impacto: Produccion/Fabricacion; sirve a auditoria y planes DDL.
     * Contrato: no ejecuta SQL; define estructura esperada por tabla.
     */
    public function columnasPeceras() {
        return array(
            "erp_produccion_peceras" => array(
                "id_pecera",
                "folio",
                "nombre",
                "id_proveedor",
                "proveedor_snapshot",
                "largo_cm",
                "fondo_cm",
                "alto_cm",
                "espesor_mm",
                "cantidad_peceras",
                "tipo_base",
                "holgura_mm",
                "costo_m2_estimado",
                "costo_corte_estimado",
                "area_total_m2",
                "piezas_total",
                "costo_total_estimado",
                "volumen_litros",
                "estatus",
                "origen",
                "id_solicitud_compra",
                "id_orden_compra",
                "observaciones",
                "payload_json",
                "creado_por",
                "actualizado_por",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_produccion_peceras_piezas" => array(
                "id_pecera_pieza",
                "id_pecera",
                "orden",
                "nombre",
                "largo_cm",
                "ancho_cm",
                "espesor_mm",
                "cantidad_por_pecera",
                "cantidad_total",
                "area_m2",
                "acabado",
                "observaciones",
                "estatus",
                "fecha_registro",
                "fecha_actualizacion"
            ),
            "erp_produccion_peceras_eventos" => array(
                "id_pecera_evento",
                "id_pecera",
                "tipo_evento",
                "estatus_anterior",
                "estatus_nuevo",
                "comentario",
                "datos_json",
                "creado_por",
                "fecha_registro"
            )
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: auditar estructura de Produccion/Peceras sin ejecutar DDL.
     * Impacto: Produccion/Fabricacion; permite saber si el esquema ya existe.
     * Contrato: read-only sobre INFORMATION_SCHEMA; respuesta JSON estandar.
     */
    public function auditarPecerasErp() {
        $columnas = $this->columnasPeceras();
        $pendientes = array();

        foreach ($this->tablasPeceras() as $tabla) {
            if (!$this->tablaExiste($tabla)) {
                $pendientes[] = array(
                    "tipo" => "tabla_faltante",
                    "tabla" => $tabla,
                    "mensaje" => "Falta tabla requerida para Produccion/Peceras"
                );
                continue;
            }
            foreach ($columnas[$tabla] as $columna) {
                if (!$this->columnaExiste($tabla, $columna)) {
                    $pendientes[] = array(
                        "tipo" => "columna_faltante",
                        "tabla" => $tabla,
                        "columna" => $columna,
                        "mensaje" => "Falta columna requerida para Produccion/Peceras"
                    );
                }
            }
        }

        return array(
            "error" => false,
            "tipo" => empty($pendientes) ? "success" : "warning",
            "mensaje" => empty($pendientes)
                ? "El esquema de Produccion/Peceras esta completo"
                : "Hay pendientes en el esquema de Produccion/Peceras",
            "depurar" => array(
                "tiene_pendientes" => !empty($pendientes),
                "pendientes" => $pendientes,
                "tablas" => $this->tablasPeceras(),
                "regla" => "Auditoria read-only; no ejecuta DDL."
            )
        );
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: generar plan DDL para persistencia futura de peceras.
     * Impacto: Produccion/Fabricacion, Compras futura y trazabilidad de pedidos de vidrio.
     * Contrato: ejecutar=false genera SQL sin aplicar; ejecutar=true queda reservado para autorizacion explicita y respaldo.
     */
    public function planActualizarPecerasErp($ejecutar = false) {
        $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        $plan = array();

        $plan[] = $this->crearTablaSiNoExiste("erp_produccion_peceras", array(
            "`id_pecera` BIGINT NOT NULL AUTO_INCREMENT",
            "`folio` VARCHAR(40) NULL",
            "`nombre` VARCHAR(160) NOT NULL",
            "`id_proveedor` BIGINT NULL",
            "`proveedor_snapshot` VARCHAR(180) NULL",
            "`largo_cm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`fondo_cm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`alto_cm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`espesor_mm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_peceras` INT NOT NULL DEFAULT 1",
            "`tipo_base` VARCHAR(30) NOT NULL DEFAULT 'interior'",
            "`holgura_mm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`costo_m2_estimado` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`costo_corte_estimado` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`area_total_m2` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`piezas_total` INT NOT NULL DEFAULT 0",
            "`costo_total_estimado` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`volumen_litros` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
            "`origen` VARCHAR(40) NOT NULL DEFAULT 'calculadora'",
            "`id_solicitud_compra` BIGINT NULL",
            "`id_orden_compra` BIGINT NULL",
            "`observaciones` TEXT NULL",
            "`payload_json` TEXT NULL",
            "`creado_por` INT NULL",
            "`actualizado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_pecera`)",
            "UNIQUE KEY `idx_produccion_pecera_folio` (`folio`)",
            "KEY `idx_produccion_pecera_estatus` (`estatus`, `fecha_registro`)",
            "KEY `idx_produccion_pecera_proveedor` (`id_proveedor`, `estatus`)",
            "KEY `idx_produccion_pecera_solicitud` (`id_solicitud_compra`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_produccion_peceras_piezas", array(
            "`id_pecera_pieza` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_pecera` BIGINT NOT NULL",
            "`orden` INT NOT NULL DEFAULT 0",
            "`nombre` VARCHAR(120) NOT NULL",
            "`largo_cm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`ancho_cm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`espesor_mm` DECIMAL(12,4) NOT NULL DEFAULT 0",
            "`cantidad_por_pecera` INT NOT NULL DEFAULT 1",
            "`cantidad_total` INT NOT NULL DEFAULT 1",
            "`area_m2` DECIMAL(14,6) NOT NULL DEFAULT 0",
            "`acabado` VARCHAR(40) NOT NULL DEFAULT 'sin_pulir'",
            "`observaciones` TEXT NULL",
            "`estatus` VARCHAR(30) NOT NULL DEFAULT 'activa'",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "`fecha_actualizacion` DATETIME NULL",
            "PRIMARY KEY (`id_pecera_pieza`)",
            "KEY `idx_produccion_pecera_pieza_pecera` (`id_pecera`, `orden`)",
            "KEY `idx_produccion_pecera_pieza_estatus` (`estatus`)"
        ), $opciones, $ejecutar);

        $plan[] = $this->crearTablaSiNoExiste("erp_produccion_peceras_eventos", array(
            "`id_pecera_evento` BIGINT NOT NULL AUTO_INCREMENT",
            "`id_pecera` BIGINT NOT NULL",
            "`tipo_evento` VARCHAR(60) NOT NULL",
            "`estatus_anterior` VARCHAR(30) NULL",
            "`estatus_nuevo` VARCHAR(30) NULL",
            "`comentario` TEXT NULL",
            "`datos_json` TEXT NULL",
            "`creado_por` INT NULL",
            "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
            "PRIMARY KEY (`id_pecera_evento`)",
            "KEY `idx_produccion_pecera_evento_pecera` (`id_pecera`, `fecha_registro`)",
            "KEY `idx_produccion_pecera_evento_tipo` (`tipo_evento`, `fecha_registro`)"
        ), $opciones, $ejecutar);

        return array(
            "error" => false,
            "tipo" => "info",
            "mensaje" => $ejecutar ? "Plan de Produccion/Peceras ejecutado" : "Plan de Produccion/Peceras generado sin ejecutar",
            "depurar" => array(
                "ejecutado" => (bool) $ejecutar,
                "plan" => $plan,
                "advertencia" => "No ejecutar DDL sin respaldo externo y autorizacion explicita del dueno."
            )
        );
    }
}

?>
