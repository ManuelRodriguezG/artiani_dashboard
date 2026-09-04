<?php

class Atencion extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-04
   * Proposito: abrir el asesor interno de respuestas para prospectos comerciales.
   * Impacto: CRM/Prospectos; ayuda al equipo a orientar necesidades sin inventar stock, precios ni promociones.
   * Contrato: vista read-only; las sugerencias no envian mensajes ni escriben en BD.
   */
  public function index() {
    $this->requerirAlgunPermiso(array("crm.ver", "crm.seguimiento.ver", "ventas.ver", "ventas.operar"));
    $this->vista("apps/erp/atencion/asistente");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-04
   * Proposito: entregar catalogos de intenciones, categorias y reglas de respuesta comercial.
   * Impacto: CRM/Prospectos; permite que la UI funcione como guia operativa uniforme.
   * Contrato: GET autenticado, read-only, salida JSON estandar.
   */
  public function catalogos_erp() {
    $this->requerirAlgunPermiso(array("crm.ver", "crm.seguimiento.ver", "ventas.ver", "ventas.operar"));
    return json_encode($this->modelo("AtencionClienteErp")->catalogos());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-04
   * Proposito: generar una respuesta sugerida para prospectos de WhatsApp, Messenger o Facebook.
   * Impacto: CRM/Prospectos; estandariza tono, preguntas y orientacion sin afirmar disponibilidad real.
   * Contrato: GET autenticado, read-only; recibe filtros y texto del cliente, devuelve sugerencias editables.
   */
  public function respuesta_sugerida_erp() {
    $this->requerirAlgunPermiso(array("crm.ver", "crm.seguimiento.ver", "ventas.ver", "ventas.operar"));
    return json_encode($this->modelo("AtencionClienteErp")->generarRespuesta($_GET));
  }
}
