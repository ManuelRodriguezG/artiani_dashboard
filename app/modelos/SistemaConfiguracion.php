<?php

class SistemaConfiguracion extends CRUD {

  private $tabla_parametros = "sys_configuracion_parametros";
  private $tabla_historial = "sys_configuracion_historial";

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: exponer un diagnostico saneado de entorno, URLs, BD e impresion POS sin mostrar secretos.
   * Impacto: SYS/Administracion; sirve como punto inicial para operar local/productivo y preparar tickets.
   * Contrato: nunca devuelve MYSQLPASS ni credenciales completas; solo estado, host/base/usuario y recomendaciones.
   */
  public function diagnosticoSistema() {
    $serverName = isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "";
    $https = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
      || (isset($_SERVER["SERVER_PORT"]) && intval($_SERVER["SERVER_PORT"]) === 443);
    $ambiente = $this->clasificarAmbiente($serverName);
    $conexion = $this->diagnosticarConexion();

    return $this->crudResponse(false, "success", "Diagnostico de configuracion generado", array(
      "ambiente" => array(
        "server_name" => $serverName,
        "tipo" => $ambiente,
        "protocolo" => $https ? "https" : "http",
        "ruta_app" => defined("RUTA_APP") ? RUTA_APP : "",
        "ruta_url" => defined("RUTA_URL") ? RUTA_URL : "",
        "timezone" => defined("APP_TIMEZONE") ? APP_TIMEZONE : date_default_timezone_get()
      ),
      "base_datos" => array(
        "host" => defined("MYSQLHOST") ? MYSQLHOST : "",
        "base" => defined("MYSQLBASE") ? MYSQLBASE : "",
        "usuario" => defined("MYSQLUSER") ? $this->enmascararUsuario(MYSQLUSER) : "",
        "conectada" => $conexion["conectada"],
        "version" => $conexion["version"],
        "mensaje" => $conexion["mensaje"]
      ),
      "impresion" => array(
        "estado" => $ambiente === "local" ? "compatible_local" : "requiere_puente_local",
        "recomendacion" => $ambiente === "local"
          ? "La impresion directa de tickets debe resolverse desde esta computadora o una terminal POS local."
          : "En productivo conviene usar un agente/puente local por sucursal para enviar tickets a la impresora instalada."
      ),
      "pendientes" => array(
        "Definir perfiles explicitos de entorno antes de guardar cambios de configuracion.",
        "Separar configuracion sensible de credenciales y no editarla desde la UI general.",
        "Disenar el conector local de impresora de tickets para POS con auditoria y prueba de impresion."
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: listar parametros configurables de SYS con diagnostico operativo.
   * Impacto: Administracion/SYS; alimenta la UI editable de configuracion.
   * Contrato: si el esquema no existe responde lista vacia y bandera `requiere_esquema`.
   */
  public function consultarConfiguracion() {
    $diagnostico = $this->diagnosticoSistema();
    if (!$this->tablaParametrosExiste()) {
      $diagnostico["depurar"]["parametros"] = array();
      $diagnostico["depurar"]["requiere_esquema"] = true;
      return $diagnostico;
    }

    try {
      $db = $this->getConexion();
      $stmt = $db->prepare("SELECT id_configuracion_parametro, grupo, clave, tipo_dato, valor, descripcion, editable_ui, sensible, estatus
                            FROM {$this->tabla_parametros}
                            WHERE estatus=1
                            ORDER BY grupo, clave");
      $stmt->execute();
      $diagnostico["depurar"]["parametros"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $diagnostico["depurar"]["requiere_esquema"] = false;
      return $diagnostico;
    } catch (Exception $e) {
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-23
   * Proposito: guardar parametros SYS editables con historial y auditoria funcional.
   * Impacto: Administracion/SYS; permite configurar entorno operativo e impresion POS.
   * Contrato: solo claves existentes, no sensibles y `editable_ui=1`; no crea parametros arbitrarios desde POST.
   */
  public function guardarConfiguracion($valores, $idUsuario, $motivo = "") {
    if (!$this->tablaParametrosExiste()) {
      return $this->crudResponse(true, "warning", "Primero aplica el esquema de configuracion SYS");
    }
    if (!is_array($valores) || empty($valores)) {
      return $this->crudResponse(true, "warning", "No hay parametros para guardar");
    }

    $db = $this->getConexion();
    try {
      $db->beginTransaction();
      $claves = array_keys($valores);
      $marcadores = implode(",", array_fill(0, count($claves), "?"));
      $stmt = $db->prepare("SELECT id_configuracion_parametro, clave, tipo_dato, valor, editable_ui, sensible
                            FROM {$this->tabla_parametros}
                            WHERE clave IN ({$marcadores}) AND estatus=1
                            FOR UPDATE");
      $stmt->execute($claves);
      $parametros = $stmt->fetchAll(PDO::FETCH_ASSOC);

      $porClave = array();
      foreach ($parametros as $parametro) {
        $porClave[$parametro["clave"]] = $parametro;
      }

      $actualizados = array();
      foreach ($valores as $clave => $valorNuevo) {
        if (!isset($porClave[$clave])) {
          throw new Exception("Parametro no permitido: " . $clave);
        }
        $parametro = $porClave[$clave];
        if (intval($parametro["editable_ui"]) !== 1 || intval($parametro["sensible"]) === 1) {
          throw new Exception("Parametro protegido: " . $clave);
        }
        $valorLimpio = $this->normalizarValor($parametro["tipo_dato"], $valorNuevo);
        $valorAntes = (string) $parametro["valor"];
        if ($valorAntes === $valorLimpio) {
          continue;
        }

        $stmtUpdate = $db->prepare("UPDATE {$this->tabla_parametros}
                                    SET valor=:valor, fecha_actualizacion=CURRENT_TIMESTAMP, id_usuario_actualizacion=:usuario
                                    WHERE id_configuracion_parametro=:id");
        $stmtUpdate->execute(array(
          ":valor" => $valorLimpio,
          ":usuario" => intval($idUsuario),
          ":id" => intval($parametro["id_configuracion_parametro"])
        ));

        $stmtHist = $db->prepare("INSERT INTO {$this->tabla_historial}
          (id_configuracion_parametro, clave, valor_antes, valor_despues, motivo, id_usuario)
          VALUES (:id, :clave, :antes, :despues, :motivo, :usuario)");
        $stmtHist->execute(array(
          ":id" => intval($parametro["id_configuracion_parametro"]),
          ":clave" => $clave,
          ":antes" => $valorAntes,
          ":despues" => $valorLimpio,
          ":motivo" => trim($motivo),
          ":usuario" => intval($idUsuario)
        ));

        $actualizados[] = array("clave" => $clave, "valor_antes" => $valorAntes, "valor_despues" => $valorLimpio);
      }

      $db->commit();
      return $this->crudResponse(false, "success", "Configuracion guardada correctamente", array(
        "total_actualizados" => count($actualizados),
        "actualizados" => $actualizados
      ));
    } catch (Exception $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }
      return $this->crudResponse(true, "danger", $e->getMessage());
    }
  }

  private function clasificarAmbiente($serverName) {
    $serverName = strtolower(trim($serverName));
    if ($serverName === "localhost" || substr($serverName, -6) === ".local") {
      return "local";
    }
    if ($serverName === "") {
      return "sin_server_name";
    }
    return "productivo";
  }

  private function diagnosticarConexion() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return array("conectada" => false, "version" => "", "mensaje" => "No hay conexion PDO disponible");
      }
      $version = $db->query("SELECT VERSION()")->fetchColumn();
      return array("conectada" => true, "version" => $version, "mensaje" => "Conexion activa");
    } catch (Exception $e) {
      return array("conectada" => false, "version" => "", "mensaje" => $e->getMessage());
    }
  }

  private function tablaParametrosExiste() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return false;
      }
      $stmt = $db->prepare("SELECT TABLE_NAME
                            FROM INFORMATION_SCHEMA.TABLES
                            WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla
                            LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $this->tabla_parametros));
      return !empty($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
      return false;
    }
  }

  private function normalizarValor($tipo, $valor) {
    $valor = is_scalar($valor) ? trim((string) $valor) : "";
    if ($tipo === "numero") {
      return (string) max(0, intval($valor));
    }
    if ($tipo === "url" && $valor !== "" && !preg_match('/^https?:\/\//i', $valor)) {
      throw new Exception("La URL debe iniciar con http:// o https://");
    }
    if ($tipo === "opcion") {
      return preg_replace('/[^a-zA-Z0-9_.-]/', '', $valor);
    }
    return mb_substr($valor, 0, 1000, "UTF-8");
  }

  private function enmascararUsuario($usuario) {
    $usuario = trim((string) $usuario);
    if ($usuario === "") {
      return "";
    }
    if (strlen($usuario) <= 2) {
      return str_repeat("*", strlen($usuario));
    }
    return substr($usuario, 0, 2) . str_repeat("*", max(2, strlen($usuario) - 2));
  }
}
