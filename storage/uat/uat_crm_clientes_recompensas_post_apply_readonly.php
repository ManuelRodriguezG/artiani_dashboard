<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: verificar estado de recompensas CRM despues de DDL/programa.
 * Impacto: lectura de programas, cuentas y movimientos de recompensas.
 * Contrato: read-only; no modifica BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCrmClientesRecompensasPostApplyReadonly extends CRUD {
  public function ejecutar() {
    $db = $this->getConexion();
    $tablas = array(
      "crm_recompensas_programas",
      "crm_clientes_recompensas_cuentas",
      "crm_clientes_recompensas_movimientos"
    );
    $conteos = array();
    foreach ($tablas as $tabla) {
      $conteos[$tabla] = $this->contar($db, $tabla);
    }

    $programas = array();
    if ($conteos["crm_recompensas_programas"] !== null) {
      $stmt = $db->query("SELECT id_programa_recompensa, codigo, nombre, tipo, estatus, fecha_registro FROM crm_recompensas_programas ORDER BY id_programa_recompensa");
      $programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $cuentas = array();
    if ($conteos["crm_clientes_recompensas_cuentas"] !== null) {
      $stmt = $db->query("SELECT id_cliente_recompensa_cuenta, id_cliente_crm, id_programa_recompensa, saldo_puntos, saldo_monetario_equivalente, nivel, estatus, fecha_alta, fecha_actualizacion FROM crm_clientes_recompensas_cuentas ORDER BY id_cliente_recompensa_cuenta");
      $cuentas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    $movimientos = array();
    if ($conteos["crm_clientes_recompensas_movimientos"] !== null) {
      $stmt = $db->query("SELECT id_cliente_recompensa_movimiento, id_cliente_recompensa_cuenta, id_cliente_crm, tipo, puntos, saldo_resultante, origen_modulo, origen_tipo, origen_id, estatus, fecha_registro FROM crm_clientes_recompensas_movimientos ORDER BY id_cliente_recompensa_movimiento DESC LIMIT 10");
      $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Verificacion CRM Recompensas generada",
      "depurar" => array(
        "conteos" => $conteos,
        "programas" => $programas,
        "cuentas" => $cuentas,
        "ultimos_movimientos" => $movimientos,
        "no_escribe_bd" => true
      )
    );
  }

  private function contar($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return null;
    }
    try {
      return intval($db->query("SELECT COUNT(*) FROM `" . $tabla . "`")->fetchColumn());
    } catch (Exception $e) {
      return null;
    }
  }
}

echo json_encode((new UatCrmClientesRecompensasPostApplyReadonly())->ejecutar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
