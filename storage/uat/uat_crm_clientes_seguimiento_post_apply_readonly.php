<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: verificar estado de seguimiento CRM despues de aplicar DDL o crear registros.
 * Impacto: lectura de interacciones y tareas CRM.
 * Contrato: read-only; no modifica BD, no crea tareas/interacciones ni notificaciones SYS.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

class UatCrmClientesSeguimientoPostApplyReadonly extends CRUD {
  public function ejecutar($idClienteCrm = 0) {
    $db = $this->getConexion();
    $tablas = array(
      "crm_clientes_interacciones",
      "crm_clientes_tareas"
    );
    $conteos = array();
    foreach ($tablas as $tabla) {
      $conteos[$tabla] = $this->contar($db, $tabla);
    }

    $interacciones = array();
    if ($conteos["crm_clientes_interacciones"] !== null) {
      $interacciones = $this->ultimasInteracciones($db, $idClienteCrm);
    }

    $tareas = array();
    $resumenTareas = array(
      "pendientes" => 0,
      "en_proceso" => 0,
      "cerradas" => 0,
      "canceladas" => 0
    );
    if ($conteos["crm_clientes_tareas"] !== null) {
      $tareas = $this->ultimasTareas($db, $idClienteCrm);
      $resumenTareas = $this->resumenTareas($db, $idClienteCrm);
    }

    return array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Verificacion CRM Seguimiento generada",
      "depurar" => array(
        "id_cliente_crm" => $idClienteCrm,
        "conteos" => $conteos,
        "resumen_tareas" => $resumenTareas,
        "ultimas_interacciones" => $interacciones,
        "ultimas_tareas" => $tareas,
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

  private function ultimasInteracciones($db, $idClienteCrm) {
    $where = "";
    $params = array();
    if ($idClienteCrm > 0) {
      $where = "WHERE i.id_cliente_crm=:cliente";
      $params[":cliente"] = $idClienteCrm;
    }
    $stmt = $db->prepare("SELECT i.id_cliente_interaccion, i.id_cliente_crm, c.codigo_cliente, c.nombre_publico,
          i.tipo, i.canal, i.direccion, i.resultado, i.resumen, i.origen_tipo, i.origen_id, i.fecha_interaccion
        FROM crm_clientes_interacciones i
        LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=i.id_cliente_crm
        " . $where . "
        ORDER BY i.fecha_interaccion DESC, i.id_cliente_interaccion DESC
        LIMIT 10");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function ultimasTareas($db, $idClienteCrm) {
    $where = "";
    $params = array();
    if ($idClienteCrm > 0) {
      $where = "WHERE t.id_cliente_crm=:cliente";
      $params[":cliente"] = $idClienteCrm;
    }
    $stmt = $db->prepare("SELECT t.id_cliente_tarea, t.id_cliente_crm, c.codigo_cliente, c.nombre_publico,
          t.tipo, t.prioridad, t.estatus, t.titulo, t.fecha_vencimiento, t.resultado_cierre,
          t.origen_tipo, t.origen_id, t.fecha_registro, t.fecha_actualizacion
        FROM crm_clientes_tareas t
        LEFT JOIN crm_clientes_maestro c ON c.id_cliente_crm=t.id_cliente_crm
        " . $where . "
        ORDER BY t.id_cliente_tarea DESC
        LIMIT 10");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function resumenTareas($db, $idClienteCrm) {
    $where = "";
    $params = array();
    if ($idClienteCrm > 0) {
      $where = "WHERE id_cliente_crm=:cliente";
      $params[":cliente"] = $idClienteCrm;
    }
    $stmt = $db->prepare("SELECT estatus, COUNT(*) total FROM crm_clientes_tareas " . $where . " GROUP BY estatus");
    $stmt->execute($params);
    $resumen = array(
      "pendientes" => 0,
      "en_proceso" => 0,
      "cerradas" => 0,
      "canceladas" => 0
    );
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $estatus = (string)$fila["estatus"];
      if ($estatus === "pendiente") {
        $resumen["pendientes"] = intval($fila["total"]);
      } elseif ($estatus === "en_proceso") {
        $resumen["en_proceso"] = intval($fila["total"]);
      } elseif ($estatus === "cerrada") {
        $resumen["cerradas"] = intval($fila["total"]);
      } elseif ($estatus === "cancelada") {
        $resumen["canceladas"] = intval($fila["total"]);
      }
    }
    return $resumen;
  }
}

$opciones = getopt("", array("cliente::"));
$idClienteCrm = isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0;

echo json_encode((new UatCrmClientesSeguimientoPostApplyReadonly())->ejecutar($idClienteCrm), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
