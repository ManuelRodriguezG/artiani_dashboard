<?php

class DistribucionAdmin extends Controlador {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: mostrar consola interna para clientes y cotizaciones Distribucion.
   * Impacto: Admin ERP Distribucion; centraliza operaciones protegidas sin exponerlas al frontend externo.
   * Contrato: vista protegida por `distribucion.ver`; los POST siguen usando CSRF/permisos puntuales.
   */
  public function index() {
    $this->administracion();
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: abrir bandeja operativa Distribucion dentro del ERP.
   * Impacto: Admin ERP Distribucion; permite aprobar clientes, asignar permisos/listas y revisar cotizaciones.
   * Contrato: vista; no escribe por si misma.
   */
  public function administracion() {
    $this->requerirPermiso("distribucion.ver");
    $this->vista("apps/erp/distribucion/administracion", array(
      "modulo" => "distribucion",
      "ruta_canonica" => "/DistribucionAdmin/administracion"
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar solicitudes externas de acceso comercial Distribucion.
   * Impacto: Admin ERP Distribucion; permite bandeja interna sin exponer datos al frontend externo.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function solicitudes() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->solicitudesInternas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar detalle interno de una solicitud Distribucion.
   * Impacto: Admin ERP Distribucion; prepara aprobacion/rechazo con contexto seguro.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function solicitud_detalle() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->solicitudDetalleInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar clientes externos Distribucion para administracion interna.
   * Impacto: Admin ERP Distribucion; alimenta asignacion de tipo, lista y permisos.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function clientes() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->clientesInternos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar listas de precio ERP activas para asignacion a clientes Distribucion.
   * Impacto: Admin ERP Distribucion; evita capturar IDs a ciegas en la UI.
   * Contrato: GET protegido por `distribucion.asignar_precios`; read-only sobre listas ERP.
   */
  public function listas_precios() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->listasPrecioInternas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: devolver permisos externos reconocidos por contrato.
   * Impacto: Admin ERP Distribucion; mantiene UI sincronizada con `DistribucionPermisosApi`.
   * Contrato: GET protegido por `distribucion.editar`; read-only.
   */
  public function permisos_comerciales() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode(array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Permisos comerciales consultados",
      "depurar" => array("items" => $this->modelo("DistribucionPermisosApi")->permisosComerciales())
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: aprobar cliente Distribucion con escritura auditada.
   * Impacto: Admin ERP Distribucion; crea/activa cliente sin asignar listas/permisos automaticamente.
   * Contrato: POST protegido por `distribucion.aprobar_clientes`; registra auditoria propia.
   */
  public function cliente_aprobar() {
    $this->requerirPermiso("distribucion.aprobar_clientes");
    return json_encode($this->modelo("DistribucionClientesApi")->clienteAprobarPlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: rechazar solicitud/cliente Distribucion sin borrar historial.
   * Impacto: Admin ERP Distribucion; conserva trazabilidad comercial.
   * Contrato: POST protegido por `distribucion.aprobar_clientes`; baja logica auditada.
   */
  public function cliente_rechazar() {
    $this->requerirPermiso("distribucion.aprobar_clientes");
    return json_encode($this->modelo("DistribucionClientesApi")->clienteEstatusPlanInterno($_POST, "rechazado", $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: suspender cliente Distribucion.
   * Impacto: Admin ERP Distribucion; bloquea acceso comercial sin eliminar perfil.
   * Contrato: POST protegido por `distribucion.aprobar_clientes`; revoca tokens activos.
   */
  public function cliente_suspendir() {
    $this->requerirPermiso("distribucion.aprobar_clientes");
    return json_encode($this->modelo("DistribucionClientesApi")->clienteEstatusPlanInterno($_POST, "suspendido", $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: guardar cambio de tipo comercial externo.
   * Impacto: Admin ERP Distribucion; separa tipo de cliente de permisos granulares.
   * Contrato: POST protegido por `distribucion.editar`; no asigna permisos por si solo.
   */
  public function asignar_tipo_cliente() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionClientesApi")->tipoClientePlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: asignar lista de precio ERP a cliente externo.
   * Impacto: Admin ERP Distribucion; evita que el frontend decida precios.
   * Contrato: POST protegido por `distribucion.asignar_precios`; requiere lista ERP activa.
   */
  public function asignar_lista_precio() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->listaPrecioPlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: guardar permisos comerciales externos.
   * Impacto: Admin ERP Distribucion; mantiene acciones frontend derivadas de permisos.
   * Contrato: POST protegido por `distribucion.editar`; valida codigos permitidos.
   */
  public function asignar_permisos() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionClientesApi")->permisosPlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar cotizaciones recibidas desde Distribucion.
   * Impacto: Admin ERP Distribucion; prepara seguimiento interno sin convertir a venta.
   * Contrato: GET protegido por `distribucion.cotizaciones.ver`; read-only.
   */
  public function cotizaciones() {
    $this->requerirPermiso("distribucion.cotizaciones.ver");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->cotizacionesInternas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: consultar detalle interno de cotizacion Distribucion.
   * Impacto: Admin ERP Distribucion; muestra snapshot comercial sin tocar inventario.
   * Contrato: GET protegido por `distribucion.cotizaciones.ver`; read-only.
   */
  public function cotizacion_detalle() {
    $this->requerirPermiso("distribucion.cotizaciones.ver");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->cotizacionDetalleInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: ejecutar accion interna sobre cotizacion Distribucion.
   * Impacto: Admin ERP Distribucion; permite seguimiento sin convertir automaticamente.
   * Contrato: POST protegido por `distribucion.cotizaciones.gestionar`; no crea venta/pedido.
   */
  public function cotizacion_accion_plan() {
    $this->requerirPermiso("distribucion.cotizaciones.gestionar");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->cotizacionAccionPlanInterna($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar plan de conversion futura de cotizacion a documento ERP.
   * Impacto: Admin ERP Distribucion; bloquea conversion automatica en MVP.
   * Contrato: POST protegido por `distribucion.cotizaciones.gestionar`; devuelve guardrails.
   */
  public function cotizacion_convertir_plan() {
    $this->requerirPermiso("distribucion.cotizaciones.gestionar");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->cotizacionConvertirPlanInterna($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: auditar esquema Distribucion API desde ERP interno.
   * Impacto: Admin ERP Distribucion; muestra readiness antes de DDL.
   * Contrato: GET protegido por `sistema.soporte`; read-only.
   */
  public function esquema_auditar() {
    $this->requerirPermiso("sistema.soporte");
    return json_encode($this->modelo("DistribucionApiEsquema")->auditarDistribucionApi());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: generar plan DDL Distribucion API sin ejecutarlo por defecto.
   * Impacto: Admin ERP Distribucion; prepara respaldo/autorizacion futura.
   * Contrato: GET protegido por `sistema.soporte`; `ejecutar` queda desactivado en este endpoint.
   */
  public function esquema_plan() {
    $this->requerirPermiso("sistema.soporte");
    return json_encode($this->modelo("DistribucionApiEsquema")->planActualizarDistribucionApi(false));
  }
}
