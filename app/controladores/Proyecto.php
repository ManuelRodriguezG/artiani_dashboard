<?php

class Proyecto extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir la bandeja operativa de proyectos y tareas.
   * Impacto: Proyectos transversal; no precarga avances de otros modulos.
   * Contrato: vista protegida por `proyectos.ver`.
   */
  public function index() {
    $this->requerirPermiso("proyectos.ver");
    $this->vista("apps/proyectos/listado");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-25
   * Proposito: abrir una vista enfocada en un proyecto y sus tareas.
   * Impacto: Proyectos transversal; separa bandeja general de trabajo detallado.
   * Contrato: vista protegida por `proyectos.ver`; no modifica BD.
   */
  public function detalle($idProyecto = 0) {
    $this->requerirPermiso("proyectos.ver");
    $idProyecto = intval($idProyecto) > 0 ? intval($idProyecto) : (isset($_GET["id_proyecto"]) ? intval($_GET["id_proyecto"]) : 0);
    $this->vista("apps/proyectos/detalle", array("id_proyecto" => $idProyecto));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: auditar el esquema de Proyectos sin aplicar DDL.
   * Impacto: Proyectos transversal; diagnostico read-only.
   * Contrato: requiere `sistema.soporte`; no modifica BD.
   */
  public function esquema_auditar_proyectos_erp() {
    $this->requerirPermiso("sistema.soporte");
    return json_encode($this->modelo("ProyectosEsquema")->auditarProyectosErp());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: generar o aplicar plan DDL del modulo Proyectos.
   * Impacto: Proyectos transversal; crea estructura vacia cuando se autorice ejecutar.
   * Contrato: `ejecutar=1` aplica DDL; requiere respaldo externo/autorizacion del dueno.
   */
  public function esquema_actualizar_proyectos_erp() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    return json_encode($this->modelo("ProyectosEsquema")->planActualizarProyectosErp($ejecutar));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar catalogos de filtros y formularios de Proyectos.
   * Impacto: Proyectos transversal; read-only.
   * Contrato: requiere `proyectos.ver`.
   */
  public function catalogos_erp() {
    $this->requerirPermiso("proyectos.ver");
    return json_encode($this->modelo("ProyectosErp")->catalogosProyectos());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-25
   * Proposito: consultar usuarios activos disponibles para asignar proyectos y tareas.
   * Impacto: Proyectos transversal; no crea usuarios ni asigna roles.
   * Contrato: requiere `proyectos.ver`; read-only.
   */
  public function usuarios_asignables_erp() {
    $this->requerirPermiso("proyectos.ver");
    return json_encode($this->modelo("ProyectosErp")->listarUsuariosAsignables());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar KPIs operativos del modulo.
   * Impacto: Proyectos transversal; read-only.
   * Contrato: requiere `proyectos.ver`.
   */
  public function resumen_erp() {
    $this->requerirPermiso("proyectos.ver");
    return json_encode($this->modelo("ProyectosErp")->resumenProyectos($this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar proyectos con filtros basicos.
   * Impacto: Proyectos transversal; read-only.
   * Contrato: requiere `proyectos.ver`.
   */
  public function proyectos_listar_erp() {
    $this->requerirPermiso("proyectos.ver");
    return json_encode($this->modelo("ProyectosErp")->listarProyectos($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar tareas operativas y bandeja personal.
   * Impacto: Proyectos transversal; read-only.
   * Contrato: requiere `proyectos.ver`.
   */
  public function tareas_listar_erp() {
    $this->requerirPermiso("proyectos.ver");
    return json_encode($this->modelo("ProyectosErp")->listarTareas($_GET, $this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar detalle de proyecto, tareas y actividad.
   * Impacto: Proyectos transversal; read-only.
   * Contrato: requiere `proyectos.ver` e `id_proyecto`.
   */
  public function proyecto_consultar_erp() {
    $this->requerirPermiso("proyectos.ver");
    $idProyecto = isset($_GET["id_proyecto"]) ? intval($_GET["id_proyecto"]) : 0;
    return json_encode($this->modelo("ProyectosErp")->consultarProyecto($idProyecto));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear o actualizar un proyecto operativo.
   * Impacto: Proyectos transversal; no modifica otros modulos ni crea tareas automaticas.
   * Contrato: POST protegido por `proyectos.crear` o `proyectos.editar`.
   */
  public function proyecto_guardar_erp() {
    $idProyecto = isset($_POST["id_proyecto"]) ? intval($_POST["id_proyecto"]) : 0;
    $this->requerirPermiso($idProyecto > 0 ? "proyectos.editar" : "proyectos.crear");
    if (isset($_POST["id_responsable"]) && intval($_POST["id_responsable"]) > 0) {
      $this->requerirPermiso("proyectos.asignar");
    }
    $respuesta = $this->modelo("ProyectosErp")->guardarProyecto($_POST, $this->usuarioActualId());
    $this->auditarSiOk("proyectos", $idProyecto > 0 ? "actualizar_proyecto" : "crear_proyecto", "erp_proyectos", $respuesta, "id_proyecto");
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear o actualizar una tarea accionable.
   * Impacto: Proyectos transversal y Notificaciones; no modifica el modulo relacionado.
   * Contrato: POST protegido por `proyectos.crear` o `proyectos.editar`.
   */
  public function tarea_guardar_erp() {
    $idTarea = isset($_POST["id_tarea"]) ? intval($_POST["id_tarea"]) : 0;
    $this->requerirPermiso($idTarea > 0 ? "proyectos.editar" : "proyectos.crear");
    if (isset($_POST["id_responsable"]) && intval($_POST["id_responsable"]) > 0) {
      $this->requerirPermiso("proyectos.asignar");
    }
    $respuesta = $this->modelo("ProyectosErp")->guardarTarea($_POST, $this->usuarioActualId());
    $this->auditarSiOk("proyectos", $idTarea > 0 ? "actualizar_tarea" : "crear_tarea", "erp_proyecto_tareas", $respuesta, "id_tarea");
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: cambiar estado operativo de una tarea.
   * Impacto: Proyectos transversal y Notificaciones; resuelve pendientes al cerrar tarea.
   * Contrato: POST protegido por `proyectos.cerrar`.
   */
  public function tarea_estatus_erp() {
    $this->requerirPermiso("proyectos.cerrar");
    $respuesta = $this->modelo("ProyectosErp")->cambiarEstatusTarea($_POST, $this->usuarioActualId());
    $this->auditarSiOk("proyectos", "estatus_tarea", "erp_proyecto_tareas", $respuesta, "id_tarea");
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: registrar comentario en proyecto o tarea.
   * Impacto: Proyectos transversal; no cambia estado ni documentos vivos automaticamente.
   * Contrato: POST protegido por `proyectos.editar`.
   */
  public function comentario_guardar_erp() {
    $this->requerirPermiso("proyectos.editar");
    $respuesta = $this->modelo("ProyectosErp")->registrarComentario($_POST, $this->usuarioActualId());
    $this->auditarSiOk("proyectos", "comentario", "erp_proyecto_comentarios", $respuesta, "id_comentario");
    return json_encode($respuesta);
  }

  private function auditarSiOk($modulo, $accion, $entidad, $respuesta, $campoId) {
    if (isset($respuesta["error"]) && $respuesta["error"] === false) {
      SesionSeguridad::registrarAuditoria($modulo, $accion, array(
        "entidad" => $entidad,
        "entidad_id" => isset($respuesta["depurar"][$campoId]) ? $respuesta["depurar"][$campoId] : null,
        "resultado" => "success",
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : $accion
      ));
    }
  }
}
