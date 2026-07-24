<?php

class SistemaConfiguracionEsquema extends DBSchema {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: crear tablas SYS para parametros configurables e historial de cambios.
   * Impacto: Administracion/SYS; habilita configuracion persistente sin guardar secretos de conexion.
   * Contrato: dry-run por defecto; con `$ejecutar=true` solo crea tablas acotadas e idempotentes.
   */
  public function planActualizarSistemaConfiguracion($ejecutar = false) {
    $plan = array();

    $plan[] = $this->crearTablaSiNoExiste("sys_configuracion_parametros", array(
      "`id_configuracion_parametro` INT NOT NULL AUTO_INCREMENT",
      "`grupo` VARCHAR(80) NOT NULL",
      "`clave` VARCHAR(120) NOT NULL",
      "`tipo_dato` VARCHAR(30) NOT NULL DEFAULT 'texto'",
      "`valor` TEXT NULL",
      "`descripcion` TEXT NULL",
      "`editable_ui` TINYINT(1) NOT NULL DEFAULT 1",
      "`sensible` TINYINT(1) NOT NULL DEFAULT 0",
      "`estatus` TINYINT(1) NOT NULL DEFAULT 1",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "`fecha_actualizacion` DATETIME NULL",
      "`id_usuario_actualizacion` INT NULL",
      "PRIMARY KEY (`id_configuracion_parametro`)",
      "UNIQUE KEY `idx_sys_config_param_clave` (`clave`)",
      "KEY `idx_sys_config_param_grupo` (`grupo`, `estatus`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan[] = $this->crearTablaSiNoExiste("sys_configuracion_historial", array(
      "`id_configuracion_historial` BIGINT NOT NULL AUTO_INCREMENT",
      "`id_configuracion_parametro` INT NULL",
      "`clave` VARCHAR(120) NOT NULL",
      "`valor_antes` TEXT NULL",
      "`valor_despues` TEXT NULL",
      "`motivo` TEXT NULL",
      "`id_usuario` INT NULL",
      "`fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
      "PRIMARY KEY (`id_configuracion_historial`)",
      "KEY `idx_sys_config_hist_clave` (`clave`, `fecha_registro`)",
      "KEY `idx_sys_config_hist_usuario` (`id_usuario`)"
    ), "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci", $ejecutar);

    $plan = array_merge($plan, $this->semillasConfiguracion($ejecutar));

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => $ejecutar ? "Esquema de configuracion SYS ejecutado" : "Esquema de configuracion SYS generado en dry-run",
      "depurar" => $plan
    );
  }

  private function semillasConfiguracion($ejecutar) {
    $semillas = array(
      array("grupo" => "entorno", "clave" => "sistema.ambiente_operativo", "tipo_dato" => "opcion", "valor" => "local", "descripcion" => "Ambiente operativo esperado para esta instalacion"),
      array("grupo" => "entorno", "clave" => "sistema.url_local", "tipo_dato" => "url", "valor" => "http://panel.com.local/", "descripcion" => "URL local canonica para pruebas y terminales internas"),
      array("grupo" => "entorno", "clave" => "sistema.url_productiva", "tipo_dato" => "url", "valor" => "", "descripcion" => "URL productiva esperada cuando el sistema salga a operacion formal"),
      array("grupo" => "entorno", "clave" => "sistema.base_datos_objetivo", "tipo_dato" => "texto", "valor" => defined("MYSQLBASE") ? MYSQLBASE : "", "descripcion" => "Base de datos objetivo documentada; no cambia la conexion activa por si sola"),
      array("grupo" => "impresion", "clave" => "pos.impresion.modo", "tipo_dato" => "opcion", "valor" => "puente_local", "descripcion" => "Modo de impresion de tickets POS"),
      array("grupo" => "impresion", "clave" => "pos.impresion.nombre_impresora", "tipo_dato" => "texto", "valor" => "", "descripcion" => "Nombre de la impresora instalada en Windows o terminal POS"),
      array("grupo" => "impresion", "clave" => "pos.impresion.puente_url", "tipo_dato" => "url", "valor" => "http://127.0.0.1:9123", "descripcion" => "URL local sugerida para el puente/agente de impresion"),
      array("grupo" => "impresion", "clave" => "pos.impresion.ancho_ticket", "tipo_dato" => "numero", "valor" => "80", "descripcion" => "Ancho del ticket en milimetros"),
      array("grupo" => "impresion", "clave" => "pos.impresion.copias", "tipo_dato" => "numero", "valor" => "1", "descripcion" => "Copias por ticket")
    );

    $plan = array();
    foreach ($semillas as $semilla) {
      $sql = "INSERT INTO sys_configuracion_parametros (grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus)
              VALUES (" . $this->sqlTexto($semilla["grupo"]) . ", " . $this->sqlTexto($semilla["clave"]) . ", " . $this->sqlTexto($semilla["tipo_dato"]) . ", " . $this->sqlTexto($semilla["valor"]) . ", " . $this->sqlTexto($semilla["descripcion"]) . ", 1, 0, 1)
              ON DUPLICATE KEY UPDATE grupo=VALUES(grupo), tipo_dato=VALUES(tipo_dato), descripcion=VALUES(descripcion), editable_ui=VALUES(editable_ui), sensible=VALUES(sensible), estatus=VALUES(estatus), fecha_actualizacion=CURRENT_TIMESTAMP;";
      $plan[] = $this->ejecutarSemilla($sql, $ejecutar);
    }
    return $plan;
  }

  private function ejecutarSemilla($sql, $ejecutar) {
    if (!$ejecutar) {
      return array("error" => false, "tipo" => "info", "mensaje" => "SQL de semilla generado sin ejecutar", "depurar" => array("sql" => $sql, "ejecutado" => false));
    }
    try {
      $db = $this->conectar();
      $stmt = $db->prepare($sql);
      $stmt->execute();
      return array("error" => false, "tipo" => "success", "mensaje" => "SQL de semilla ejecutado correctamente", "depurar" => array("sql" => $sql, "ejecutado" => true));
    } catch (Exception $e) {
      return array("error" => true, "tipo" => "danger", "mensaje" => $e->getMessage(), "depurar" => $sql);
    }
  }

  private function sqlTexto($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
  }
}
