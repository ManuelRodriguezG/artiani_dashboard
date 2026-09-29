<?php

class ArtianiConocimientoEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: auditar tablas canonicas del conocimiento Artiani sin modificarlas.
   * Impacto: Arquitectura Artiani; permite saber si la Enciclopedia ya puede persistir fichas y relaciones.
   * Contrato: read-only; no ejecuta DDL ni inserta semillas.
   */
  public function auditarArtianiConocimiento() {
    $requeridas = array(
      "artiani_especies" => array("id_especie", "slug", "nombre", "grupo", "dificultad", "estatus", "contenido_json"),
      "artiani_especies_productos" => array("id_relacion", "id_especie", "id_sku_erp", "necesidad", "prioridad", "estatus"),
      "artiani_especies_revision" => array("id_revision", "id_especie", "tipo", "estatus", "descripcion")
    );
    $pendientes = array();

    foreach ($requeridas as $tabla => $columnas) {
      if (!$this->tablaExiste($tabla)) {
        $pendientes[] = array("tipo" => "tabla_faltante", "tabla" => $tabla, "mensaje" => "Falta tabla canonica Artiani");
        continue;
      }
      foreach ($columnas as $columna) {
        if (!$this->columnaExiste($tabla, $columna)) {
          $pendientes[] = array("tipo" => "columna_faltante", "tabla" => $tabla, "columna" => $columna, "mensaje" => "Falta columna requerida");
        }
      }
    }

    return array(
      "error" => false,
      "tipo" => empty($pendientes) ? "success" : "warning",
      "mensaje" => empty($pendientes) ? "El esquema Artiani esta completo" : "Hay pendientes en el esquema Artiani",
      "depurar" => array(
        "tiene_pendientes" => !empty($pendientes),
        "pendientes" => $pendientes,
        "nota" => "La pantalla actual funciona con semilla PHP hasta autorizar DDL y migracion de conocimiento."
      )
    );
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: generar plan de tablas para Enciclopedia, productos relacionados y cola de revision.
   * Impacto: Arquitectura Artiani/Catalogo; prepara persistencia sin ejecutar cambios por defecto.
   * Contrato: si `$ejecutar` es false solo devuelve SQL; cualquier ejecucion requiere autorizacion externa.
   */
  public function planActualizarArtianiConocimiento($ejecutar = false) {
    $opciones = "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("artiani_especies", array(
      "`id_especie` INT NOT NULL AUTO_INCREMENT",
      "`slug` VARCHAR(120) NOT NULL",
      "`nombre` VARCHAR(180) NOT NULL",
      "`grupo` VARCHAR(80) NOT NULL",
      "`dificultad` VARCHAR(40) NOT NULL DEFAULT 'media'",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'borrador'",
      "`resumen` VARCHAR(700) NULL",
      "`contenido_json` LONGTEXT NULL",
      "`fuente_revision` VARCHAR(180) NULL",
      "`fecha_revision` DATE NULL",
      "`creado_por` INT NULL",
      "`actualizado_por` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_especie`)",
      "UNIQUE KEY `idx_artiani_especies_slug` (`slug`)",
      "KEY `idx_artiani_especies_grupo` (`grupo`, `estatus`)",
      "KEY `idx_artiani_especies_dificultad` (`dificultad`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("artiani_especies_productos", array(
      "`id_relacion` INT NOT NULL AUTO_INCREMENT",
      "`id_especie` INT NOT NULL",
      "`id_sku_erp` INT NULL",
      "`necesidad` VARCHAR(120) NOT NULL",
      "`prioridad` VARCHAR(30) NOT NULL DEFAULT 'recomendado'",
      "`contexto_uso` VARCHAR(700) NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_relacion`)",
      "KEY `idx_artiani_rel_especie` (`id_especie`, `estatus`)",
      "KEY `idx_artiani_rel_sku` (`id_sku_erp`, `estatus`)",
      "KEY `idx_artiani_rel_necesidad` (`necesidad`, `estatus`)"
    ), $opciones, $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("artiani_especies_revision", array(
      "`id_revision` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_especie` INT NULL",
      "`tipo` VARCHAR(80) NOT NULL",
      "`estatus` VARCHAR(30) NOT NULL DEFAULT 'pendiente'",
      "`descripcion` VARCHAR(700) NOT NULL",
      "`origen` VARCHAR(120) NULL",
      "`asignado_a` INT NULL",
      "`resuelto_por` INT NULL",
      "`fecha_resolucion` DATETIME NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "PRIMARY KEY (`id_revision`)",
      "KEY `idx_artiani_revision_especie` (`id_especie`, `estatus`)",
      "KEY `idx_artiani_revision_tipo` (`tipo`, `estatus`)"
    ), $opciones, $ejecutar);

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Plan Artiani ejecutado" : "Plan Artiani generado en dry-run",
      "depurar" => $plan
    );
  }
}
