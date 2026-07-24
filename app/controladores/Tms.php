<?php

class Tms extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir consola inicial TMS Delivery en modo read-only/dry-run.
   * Impacto: TMS Delivery; separa servicios logisticos de Ventas, productos y garantias.
   * Contrato: vista futura protegida por `tms.ver`; no escribe BD.
   */
  public function servicios() {
    $this->requerirPermiso("tms.ver");
    $this->vista("apps/tms/servicios");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir superficie futura de operacion diaria y rutas TMS.
   * Impacto: TMS Delivery; separa ejecucion logistica de Ventas y Almacen.
   * Contrato: vista protegida por `tms.operar`; no modifica servicios en esta fase.
   */
  public function operacion() {
    $this->requerirPermiso("tms.operar");
    $this->vista("apps/tms/operacion");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir superficie futura de costos, cobros y bonificaciones logisticas TMS.
   * Impacto: TMS Delivery y Finanzas; mantiene costo logistico separado del producto.
   * Contrato: vista protegida por `tms.costos`; no registra costos en esta fase.
   */
  public function costos() {
    $this->requerirPermiso("tms.costos");
    $this->vista("apps/tms/costos");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir superficie futura de indicadores TMS Delivery.
   * Impacto: TMS Delivery; mide entregas sin recalcular ventas ni inventario.
   * Contrato: vista protegida por `tms.reportes`; read-only.
   */
  public function reportes() {
    $this->requerirPermiso("tms.reportes");
    $this->vista("apps/tms/reportes");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: abrir superficie futura de configuracion y autorizaciones logisticas.
   * Impacto: TMS Delivery; prepara reglas de servicio sin crear productos de envio.
   * Contrato: vista protegida por `tms.autorizar`; sin escritura en esta fase.
   */
  public function configuracion() {
    $this->requerirPermiso("tms.autorizar");
    $this->vista("apps/tms/configuracion");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: diagnosticar cobertura tecnica del esquema TMS Delivery.
   * Impacto: TMS Delivery; permite auditar sin crear tablas ni tocar otros modulos.
   * Contrato: read-only; no ejecuta DDL.
   */
  public function esquema_auditar_tms() {
    $this->requerirPermiso("sistema.soporte");
    return json_encode($this->modelo("TmsEsquema")->auditarTmsDelivery());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: generar plan DDL de TMS Delivery sin ejecucion real desde esta fase.
   * Impacto: TMS Delivery; prepara revision de esquema independiente de Ventas.
   * Contrato: dry-run por defecto; ejecutar=1 queda bloqueado hasta definir autorizacion formal.
   */
  public function esquema_plan_tms() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      return json_encode(array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "DDL TMS no habilitado desde este endpoint inicial",
        "depurar" => array(
          "ejecutar" => false,
          "regla" => "Primero revisar docs/erp_tms_delivery_schema_propuesta.sql y preparar autorizacion formal."
        )
      ));
    }
    return json_encode($this->modelo("TmsEsquema")->planActualizarTmsDelivery(false));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: consultar catalogos de trabajo TMS para UI y validaciones.
   * Impacto: TMS Delivery; no depende de Ventas ni de Catalogo.
   * Contrato: read-only.
   */
  public function catalogos_erp() {
    $this->requerirPermiso("tms.ver");
    return json_encode($this->modelo("TmsDelivery")->catalogosTms());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: listar servicios logisticos existentes si el esquema ya fue aplicado.
   * Impacto: TMS Delivery; alimenta bandeja sin modificar estados.
   * Contrato: read-only; si falta esquema devuelve lista vacia controlada.
   */
  public function servicios_listar_erp() {
    $this->requerirPermiso("tms.ver");
    return json_encode($this->modelo("TmsDelivery")->listarServicios($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: validar solicitud de servicio logistico antes de guardado futuro.
   * Impacto: TMS Delivery; fija contrato sin crear ventas, sin cancelar ventas y sin mover inventario.
   * Contrato: dry-run; no escribe BD.
   */
  public function servicio_dryrun_erp() {
    $this->requerirPermiso("tms.crear");
    return json_encode($this->modelo("TmsDelivery")->servicioDryRun($_POST));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: crear servicio logistico TMS cuando el esquema ya exista.
   * Impacto: TMS Delivery; habilita folio independiente sin afectar Ventas, garantias ni inventario.
   * Contrato: POST con permiso `tms.crear`; si falta esquema responde bloqueo controlado y no escribe BD.
   */
  public function servicio_guardar_erp() {
    $this->requerirPermiso("tms.crear");
    $respuesta = $this->modelo("TmsDelivery")->guardarServicio($_POST, $this->usuarioActualId());
    if (isset($respuesta["error"]) && $respuesta["error"] === false) {
      SesionSeguridad::registrarAuditoria("tms", "crear_servicio", array(
        "entidad" => "erp_tms_servicios",
        "entidad_id" => isset($respuesta["depurar"]["id_tms_servicio"]) ? $respuesta["depurar"]["id_tms_servicio"] : null,
        "resultado" => "success",
        "mensaje" => isset($respuesta["depurar"]["folio"]) ? $respuesta["depurar"]["folio"] : "Servicio TMS creado"
      ));
    }
    return json_encode($respuesta);
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-24
   * Proposito: exponer contrato de acciones TMS antes de implementar cambios reales de estado.
   * Impacto: TMS Delivery; ayuda a preparar UI/UAT sin escrituras.
   * Contrato: read-only; no programa, no asigna, no entrega y no cancela servicios.
   */
  public function acciones_contrato_erp() {
    $this->requerirPermiso("tms.ver");
    return json_encode($this->modelo("TmsDelivery")->accionesContratoReadOnly());
  }
}
