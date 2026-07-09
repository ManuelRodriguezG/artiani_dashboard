<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-06
 * Proposito: consultar saldos monetarios CRM de un cliente sin modificar datos.
 * Impacto: valida saldo favor disponible para POS/CRM antes de construir UI o consumo.
 * Contrato: read-only; no crea cuentas, no crea movimientos y no cambia saldos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("id::", "limite::"));
$idClienteCrm = isset($opciones["id"]) ? intval($opciones["id"]) : 0;
$limite = isset($opciones["limite"]) ? max(1, min(50, intval($opciones["limite"]))) : 10;

class UatCrmClientesSaldosClienteReadonly extends CRUD {
  public function consultar($idClienteCrm, $limite) {
    $db = $this->getConexion();
    if (!$db) {
      return array("ok" => false, "mensaje" => "Conexion BD no disponible");
    }
    if ($idClienteCrm <= 0) {
      return array("ok" => false, "mensaje" => "Indica --id=ID_CLIENTE_CRM");
    }
    foreach (array("crm_clientes_maestro", "crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos") as $tabla) {
      if (!$this->tablaExisteLocal($db, $tabla)) {
        return array("ok" => false, "mensaje" => "Falta tabla " . $tabla);
      }
    }

    $cliente = $this->uno($db, "SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, calidad_datos
      FROM crm_clientes_maestro
      WHERE id_cliente_crm=:cliente
      LIMIT 1", array(":cliente" => $idClienteCrm));
    if (!$cliente) {
      return array("ok" => false, "mensaje" => "Cliente CRM no encontrado");
    }

    $cuentas = $this->todos($db, "SELECT id_cliente_saldo_cuenta, id_cliente_crm, moneda,
        saldo_disponible, saldo_retenido, saldo_total, estatus, fecha_apertura, fecha_actualizacion
      FROM crm_clientes_saldos_cuentas
      WHERE id_cliente_crm=:cliente
      ORDER BY moneda ASC", array(":cliente" => $idClienteCrm));

    $movimientos = $this->todos($db, "SELECT id_cliente_saldo_movimiento, id_cliente_saldo_cuenta,
        id_cliente_crm, folio, tipo, naturaleza, moneda, monto, saldo_anterior, saldo_resultante,
        origen_modulo, origen_tipo, origen_id, referencia_externa, descripcion, estatus, fecha_registro
      FROM crm_clientes_saldos_movimientos
      WHERE id_cliente_crm=:cliente
      ORDER BY id_cliente_saldo_movimiento DESC
      LIMIT " . intval($limite), array(":cliente" => $idClienteCrm));

    return array(
      "ok" => true,
      "modo" => "crm_cliente_saldos_readonly",
      "read_only" => true,
      "cliente" => $cliente,
      "cuentas" => $cuentas,
      "movimientos" => $movimientos,
      "resumen" => array(
        "cuentas" => count($cuentas),
        "movimientos" => count($movimientos),
        "saldo_total_mxn" => $this->sumarMoneda($cuentas, "MXN")
      ),
      "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_cuentas" => true,
        "no_crea_movimientos" => true,
        "no_cambia_saldos" => true
      )
    );
  }

  private function tablaExisteLocal($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
  }

  private function uno($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function todos($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function sumarMoneda($cuentas, $moneda) {
    $total = 0;
    foreach ($cuentas as $cuenta) {
      if ((string) $cuenta["moneda"] === $moneda) {
        $total += floatval($cuenta["saldo_total"]);
      }
    }
    return round($total, 6);
  }
}

echo json_encode((new UatCrmClientesSaldosClienteReadonly())->consultar($idClienteCrm, $limite), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
