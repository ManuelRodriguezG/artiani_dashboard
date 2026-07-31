<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: verificar readiness operativo CRM Clientes/Seguimiento sin escribir BD.
 * Impacto: consolida ficha, calidad, dry-run de edicion, interaccion y tarea antes de UAT fuerte.
 * Contrato: read-only/dry-run; no modifica clientes, tareas, interacciones, eventos, POS, ventas ni ecommerce.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

class UatCrmOperativoReadinessReadonly extends CRUD {
  public function ejecutar($idClienteCrm) {
    $modelo = new ClientesCrm();
    $antes = $this->conteosOperativos();

    $ficha = $modelo->consultarFicha($idClienteCrm);
    $depurarFicha = isset($ficha["depurar"]) && is_array($ficha["depurar"]) ? $ficha["depurar"] : array();
    $cliente = isset($depurarFicha["cliente"]) && is_array($depurarFicha["cliente"]) ? $depurarFicha["cliente"] : array();

    $dryRunBasico = $modelo->fichaBasicaGuardarDryRun(array(
      "id_cliente_crm" => $idClienteCrm,
      "nombre_publico" => isset($cliente["nombre_publico"]) ? $cliente["nombre_publico"] : "Cliente CRM UAT",
      "tipo_cliente" => isset($cliente["tipo_cliente"]) ? $cliente["tipo_cliente"] : "persona",
      "estatus" => isset($cliente["estatus"]) ? $cliente["estatus"] : "activo",
      "observaciones_operativas" => "Readiness CRM operativo sin escritura"
    ));

    $dryRunInteraccion = $modelo->interaccionDryRun(array(
      "id_cliente_crm" => $idClienteCrm,
      "tipo" => "seguimiento",
      "canal" => "whatsapp",
      "direccion" => "saliente",
      "resultado" => "registrado",
      "resumen" => "Readiness CRM seguimiento sin escritura",
      "detalle" => "Validacion dry-run consolidada",
      "origen_tipo" => "uat_crm_operativo_readiness",
      "origen_id" => (string) $idClienteCrm
    ));

    $dryRunTarea = $modelo->tareaSeguimientoDryRun(array(
      "id_cliente_crm" => $idClienteCrm,
      "tipo" => "calidad_datos",
      "prioridad" => "normal",
      "titulo" => "Readiness CRM tarea sin escritura",
      "descripcion" => "Validacion dry-run consolidada",
      "fecha_vencimiento" => date("Y-m-d", strtotime("+7 days")),
      "origen_tipo" => "uat_crm_operativo_readiness",
      "origen_id" => (string) $idClienteCrm
    ));

    $tareaExistente = $this->primeraTareaAbierta($idClienteCrm);
    $dryRunEstatus = null;
    if ($tareaExistente) {
      $dryRunEstatus = $modelo->tareaEstatusDryRun(array(
        "id_cliente_tarea" => intval($tareaExistente["id_cliente_tarea"]),
        "estatus" => "en_proceso",
        "resultado_cierre" => "Readiness CRM sin escritura",
        "nota" => "Validacion dry-run consolidada"
      ));
    }

    $despues = $this->conteosOperativos();
    $sinEscritura = $antes === $despues;
    $pendientes = array();

    if (!empty($ficha["error"])) {
      $pendientes[] = "Ficha CRM no consultable";
    }
    if (!$this->puedeGuardar($dryRunBasico)) {
      $pendientes[] = "Dry-run basico no queda listo para apply";
    }
    if (!$this->puedeGuardar($dryRunInteraccion)) {
      $pendientes[] = "Dry-run de interaccion no queda listo para apply";
    }
    if (!$this->puedeGuardar($dryRunTarea)) {
      $pendientes[] = "Dry-run de tarea no queda listo para apply";
    }
    if (!$sinEscritura) {
      $pendientes[] = "Los conteos cambiaron durante el readiness; revisar porque debia ser read-only";
    }

    return array(
      "ok" => empty($pendientes),
      "modo" => "read-only/dry-run",
      "id_cliente_crm" => $idClienteCrm,
      "conteos_antes" => $antes,
      "conteos_despues" => $despues,
      "sin_escritura_detectada" => $sinEscritura,
      "ficha" => array(
        "ok" => empty($ficha["error"]),
        "mensaje" => isset($ficha["mensaje"]) ? $ficha["mensaje"] : "",
        "codigo_cliente" => isset($cliente["codigo_cliente"]) ? $cliente["codigo_cliente"] : null,
        "nombre_publico" => isset($cliente["nombre_publico"]) ? $cliente["nombre_publico"] : null,
        "calidad_operativa" => isset($depurarFicha["calidad_operativa"]) ? $depurarFicha["calidad_operativa"] : array()
      ),
      "dry_runs" => array(
        "basico" => $this->resumenDryRun($dryRunBasico),
        "interaccion" => $this->resumenDryRun($dryRunInteraccion),
        "tarea" => $this->resumenDryRun($dryRunTarea),
        "estatus_tarea" => $dryRunEstatus ? $this->resumenDryRun($dryRunEstatus) : array(
          "omitido" => true,
          "motivo" => "No existe tarea abierta para validar cambio de estatus sin crear registros"
        )
      ),
      "pendientes" => $pendientes,
      "contrato" => array(
        "no_escribe_bd" => true,
        "no_crea_interacciones" => true,
        "no_crea_tareas" => true,
        "no_modifica_clientes" => true,
        "apply_real_requiere_token_respaldo" => true
      )
    );
  }

  private function conteosOperativos() {
    $db = $this->getConexion();
    return array(
      "crm_clientes_maestro" => $this->contarTablaSegura($db, "crm_clientes_maestro"),
      "crm_clientes_eventos" => $this->contarTablaSegura($db, "crm_clientes_eventos"),
      "crm_clientes_interacciones" => $this->contarTablaSegura($db, "crm_clientes_interacciones"),
      "crm_clientes_tareas" => $this->contarTablaSegura($db, "crm_clientes_tareas")
    );
  }

  private function contarTablaSegura($db, $tabla) {
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return null;
    }
    try {
      return intval($db->query("SELECT COUNT(*) FROM `" . $tabla . "`")->fetchColumn());
    } catch (Exception $e) {
      return null;
    }
  }

  private function primeraTareaAbierta($idClienteCrm) {
    $db = $this->getConexion();
    if (!$db) {
      return null;
    }
    try {
      $stmt = $db->prepare("SELECT id_cliente_tarea
          FROM crm_clientes_tareas
          WHERE id_cliente_crm=:cliente AND estatus IN ('pendiente','en_proceso')
          ORDER BY id_cliente_tarea ASC
          LIMIT 1");
      $stmt->execute(array(":cliente" => intval($idClienteCrm)));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      return $fila ?: null;
    } catch (Exception $e) {
      return null;
    }
  }

  private function puedeGuardar($respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    return empty($respuesta["error"]) && !empty($depurar["puede_guardar"]);
  }

  private function resumenDryRun($respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    return array(
      "ok" => empty($respuesta["error"]),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "puede_guardar" => !empty($depurar["puede_guardar"]),
      "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
      "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
      "requiere_autorizacion_apply" => !empty($depurar["requiere_autorizacion_apply"]),
      "no_escribe_bd" => !empty($depurar["no_escribe_bd"]) || !empty($depurar["no_crea_interaccion"]) || !empty($depurar["no_crea_tarea"]) || !empty($depurar["no_modifica_tarea"])
    );
  }
}

$opciones = getopt("", array("cliente::"));
$idClienteCrm = isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 1;

echo json_encode((new UatCrmOperativoReadinessReadonly())->ejecutar($idClienteCrm), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
