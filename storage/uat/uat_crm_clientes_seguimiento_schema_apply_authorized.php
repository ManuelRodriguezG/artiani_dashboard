<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: aplicar DDL CRM Seguimiento solo con token y respaldo externo.
 * Impacto: crea tablas de interacciones y tareas CRM para seguimiento operativo.
 * Contrato: no crea tareas reales, no crea interacciones, no modifica clientes ni toca POS/ventas/ecommerce/legacy.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrmEsquema.php";

class UatCrmClientesSeguimientoSchemaApplyAuthorized extends CRUD {
  const TOKEN = "CRM_CLIENTES_SEGUIMIENTO_DDL";

  private $tablas = array(
    "crm_clientes_interacciones",
    "crm_clientes_tareas"
  );

  public function ejecutar($token, $respaldo) {
    $validacion = $this->validarAutorizacion($token, $respaldo);
    if (!$validacion["ok"]) {
      return array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Autorizacion incompleta; no se ejecuto DDL CRM Seguimiento.",
        "depurar" => array(
          "validacion" => $validacion,
          "alcance" => $this->alcanceSeguro()
        )
      );
    }

    $modelo = new ClientesCrmEsquema();
    $preflight = $this->estadoTablas();
    $respuesta = $modelo->planActualizarSeguimientoClientesCrm(true);
    $postflight = $this->estadoTablas();

    return array(
      "error" => !empty($respuesta["error"]),
      "tipo" => empty($respuesta["error"]) ? "success" : "danger",
      "mensaje" => empty($respuesta["error"]) ? "DDL CRM Seguimiento aplicado." : "No se pudo aplicar DDL CRM Seguimiento.",
      "depurar" => array(
        "respaldo" => $validacion["respaldo"],
        "preflight" => $preflight,
        "respuesta" => $respuesta,
        "postflight" => $postflight,
        "alcance" => $this->alcanceSeguro()
      )
    );
  }

  private function alcanceSeguro() {
    return array(
      "crea_tablas_seguimiento" => $this->tablas,
      "crea_tareas_reales" => false,
      "crea_interacciones_reales" => false,
      "modifica_clientes" => false,
      "crea_notificaciones_sys" => false,
      "toca_pos_ventas_ecommerce_garantias_apartados_devoluciones_legacy" => false
    );
  }

  private function validarAutorizacion($token, $respaldo) {
    $respaldo = trim((string)$respaldo);
    $placeholder = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|\\[RUTA|<ruta|ruta real)/i', $respaldo) === 1;
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $esRutaLocal ? file_exists($respaldo) : null;
    $legible = $esRutaLocal ? ($existe && is_readable($respaldo)) : null;
    $tamano = $esRutaLocal && $existe ? filesize($respaldo) : null;
    $raizProyecto = realpath(__DIR__ . "/../..");
    $realRespaldo = $esRutaLocal && $existe ? realpath($respaldo) : false;
    $dentroProyecto = $raizProyecto && $realRespaldo && stripos($realRespaldo, $raizProyecto) === 0;
    $respaldoOk = strlen($respaldo) >= 8 && !$placeholder && !$dentroProyecto && (!$esRutaLocal || ($existe && $legible && $tamano > 0));

    return array(
      "ok" => $token === self::TOKEN && $respaldoOk,
      "token_ok" => $token === self::TOKEN,
      "respaldo" => array(
        "referencia" => $respaldo,
        "placeholder_detectado" => $placeholder,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $existe,
        "archivo_legible" => $legible,
        "tamano_bytes" => $tamano,
        "dentro_del_proyecto" => $dentroProyecto,
        "ok" => $respaldoOk
      )
    );
  }

  private function estadoTablas() {
    $db = $this->getConexion();
    $estado = array();
    foreach ($this->tablas as $tabla) {
      $existe = $this->tablaExisteLocal($db, $tabla);
      $estado[$tabla] = array(
        "existe" => $existe,
        "filas" => $existe ? $this->contarFilas($db, $tabla) : null
      );
    }
    return $estado;
  }

  private function tablaExisteLocal($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
  }

  private function contarFilas($db, $tabla) {
    $stmt = $db->query("SELECT COUNT(*) FROM `" . str_replace("`", "", $tabla) . "`");
    return intval($stmt->fetchColumn());
  }
}

$opciones = getopt("", array("autorizar::", "respaldo::"));
$token = isset($opciones["autorizar"]) ? trim((string)$opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string)$opciones["respaldo"]) : "";

header("Content-Type: application/json; charset=utf-8");
echo json_encode((new UatCrmClientesSeguimientoSchemaApplyAuthorized())->ejecutar($token, $respaldo), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
