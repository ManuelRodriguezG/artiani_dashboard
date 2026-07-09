<?php

class Ventas extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function index() {
    
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: conservar compatibilidad de ruta `crear` apuntando al POS ERP nuevo.
   * Impacto: evita abrir la captura legacy ecommerce desde el modulo Ventas.
   * Contrato: no cobra ni descuenta; delega a la vista POS en modo prevalidacion.
   */
  public function crear() {
    $this->requerirPermiso("ventas.operar");
    $this->vista("apps/erp/ventas/pos");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: abrir el POS ERP nuevo en modo preventa/prevalidacion.
   * Impacto: separa el flujo ERP de ventas del POS legacy ecommerce hasta terminar auditoria y autorizacion de esquema.
   * Contrato: la vista no cobra ni descuenta inventario; solo consume endpoints read-only/prevalidacion.
   */
  public function pos() {
    $this->requerirPermiso("ventas.operar");
    $this->vista("apps/erp/ventas/pos");
  }

  public function editar() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/listado");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: abrir el tablero ERP de ventas en lugar del listado ecommerce antiguo.
   * Impacto: separa la operacion nueva de `ecom_pedidos` hasta auditar migracion.
   * Contrato: vista read-only mientras falte esquema ERP autorizado.
   */
  public function mostrar() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/listado");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: absorber enlaces legacy de detalle hacia el tablero ERP.
   * Impacto: evita pantallas antiguas incompletas mientras se diseña detalle de venta/pedido.
   * Contrato: no consulta ni modifica ventas legacy.
   */
  public function detalles() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/listado");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-02.
   * Proposito: abrir detalle read-only de una venta ERP por folio/id.
   * Impacto: concentra ticket, pagos, garantias y trazabilidad sin mezclar POS ni legacy.
   * Contrato: la vista solo consulta endpoints read-only; no cancela, cobra ni mueve inventario.
   */
  public function venta_detalle() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/venta_detalle");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: abrir reportes gerenciales POS/Ventas en modo read-only.
   * Impacto: permite revisar caja, diferencias y operacion sin mover inventario ni dinero.
   * Contrato: vista de consulta; acciones correctivas quedan fuera de esta fase.
   */
  public function reportes() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/reportes");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: abrir el modulo ERP dedicado de pedidos y apartados sin usar ecommerce legacy.
   * Impacto: separa pedidos/abonos/reservas de la venta POS inmediata.
   * Contrato: vista operativa; prevalidacion primero y acciones reales confirmadas por endpoints ERP.
   */
  public function pedidos() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/pedidos");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: abrir modulo dedicado de devoluciones/cancelaciones POS.
   * Impacto: separa reversas, reembolsos y decisiones fisicas del tablero de ventas.
   * Contrato: vista inicial dry-run/read-only; devoluciones reales requieren autorizacion y permisos.
   */
  public function devoluciones() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/devoluciones");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: abrir modulo dedicado de turnos/corte de caja POS fuera de la pantalla de cobro.
   * Impacto: separa administracion de caja del POS operativo; no cierra turnos por si mismo.
   * Contrato: vista read-only/dry-run hasta autorizacion explicita de escrituras reales.
   */
  public function caja_turnos() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/caja_turnos");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-02.
   * Proposito: abrir modulo dedicado de movimientos de caja POS fuera de la pantalla de cobro.
   * Impacto: separa gastos, retiros, entradas y vales de la venta rapida.
   * Contrato: vista dry-run/read-only; no registra movimientos reales sin autorizacion posterior.
   */
  public function caja_movimientos() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/caja_movimientos");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-02.
   * Proposito: abrir modulo dedicado de evidencias de caja POS.
   * Impacto: permite seguimiento de comprobantes sensibles sin mezclarlo con POS mostrador.
   * Contrato: vista read-only inicial; revisar/corregir evidencias queda en flujo autorizado.
   */
  public function caja_evidencias() {
    $this->requerirPermiso("ventas.ver");
    $this->vista("apps/erp/ventas/caja_evidencias");
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: abrir configuracion POS de tiendas, cajas, terminales y asignaciones.
   * Impacto: prepara CRUD formal sin mezclar configuracion con venta en mostrador.
   * Contrato: vista read-only; altas/ediciones reales quedan pendientes de DDL/permisos/autorizacion.
   */
  public function pos_configuracion() {
    $this->requerirPermiso("ventas.pos_config.ver");
    $this->vista("apps/erp/ventas/pos_configuracion");
  }

  public function pos_catalogos_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->catalogosPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: consultar configuracion operativa POS para pantallas dedicadas de Caja/Configuracion.
   * Impacto: expone cajas, terminales, asignaciones, turnos y movimientos recientes sin escribir BD.
   * Contrato: read-only protegido por `ventas.ver`.
   */
  public function pos_configuracion_resumen_erp() {
    $this->requerirPermiso("ventas.pos_config.ver");
    return json_encode($this->modelo("VentasErp")->configuracionPosReadOnly());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: validar alta/edicion futura de caja POS sin escribir datos.
   * Impacto: prepara CRUD formal de Configuracion POS con reglas auditables.
   * Contrato: dry-run protegido por `ventas.ver`; no crea ni actualiza cajas.
   */
  public function pos_configuracion_caja_dryrun_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->configuracionCajaDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: validar alta/edicion futura de terminal POS sin escribir datos.
   * Impacto: prepara amarre terminal/tienda/caja antes de permitir ventas reales.
   * Contrato: dry-run protegido por `ventas.ver`; no crea ni actualiza terminales.
   */
  public function pos_configuracion_terminal_dryrun_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->configuracionTerminalDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-01.
   * Proposito: validar asignacion futura usuario/caja/terminal sin escribir datos.
   * Impacto: evita POS con selector libre y prepara operacion por usuario/caja oficial.
   * Contrato: dry-run protegido por `ventas.ver`; no crea ni modifica asignaciones.
   */
  public function pos_configuracion_asignacion_dryrun_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->configuracionAsignacionDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: guardar caja POS real desde flujo autorizado de configuracion.
   * Impacto: crea/edita caja sin abrir turno ni mover caja.
   * Contrato: POST protegido; la UI productiva no debe invocarlo hasta permisos finos/UAT.
   */
  public function pos_configuracion_caja_guardar_erp() {
    $this->requerirPermisoConfiguracionPosGuardar();
    $respuesta = $this->modelo("VentasErp")->configuracionCajaGuardarReal($_POST, $this->usuarioActualId());
    $this->auditarConfiguracionPos("caja_guardar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: guardar terminal POS real desde flujo autorizado de configuracion.
   * Impacto: crea/edita terminal sin abrir turno ni mover caja.
   * Contrato: POST protegido; la UI productiva no debe invocarlo hasta permisos finos/UAT.
   */
  public function pos_configuracion_terminal_guardar_erp() {
    $this->requerirPermisoConfiguracionPosGuardar();
    $respuesta = $this->modelo("VentasErp")->configuracionTerminalGuardarReal($_POST, $this->usuarioActualId());
    $this->auditarConfiguracionPos("terminal_guardar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: guardar asignacion usuario/caja/terminal real.
   * Impacto: amarra POS a tienda/caja/terminal oficial sin abrir turnos.
   * Contrato: POST protegido; la UI productiva no debe invocarlo hasta permisos finos/UAT.
   */
  public function pos_configuracion_asignacion_guardar_erp() {
    $this->requerirPermiso("ventas.pos_config.asignar_usuario");
    $respuesta = $this->modelo("VentasErp")->configuracionAsignacionGuardarReal($_POST, $this->usuarioActualId());
    $this->auditarConfiguracionPos("asignacion_guardar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: desactivar caja, terminal o asignacion POS con baja logica.
   * Impacto: protege historial operativo y bloquea desactivacion si hay turno abierto.
   * Contrato: POST protegido; requiere motivo.
   */
  public function pos_configuracion_desactivar_erp() {
    $this->requerirPermiso("ventas.pos_config.desactivar");
    $respuesta = $this->modelo("VentasErp")->configuracionPosDesactivarReal($_POST, $this->usuarioActualId());
    $this->auditarConfiguracionPos("desactivar", $respuesta);
    return json_encode($respuesta);
  }

  public function pos_buscar_skus_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->buscarSkusPos($_GET));
  }

  public function pos_disponibilidad_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->disponibilidadSku($_GET));
  }

  public function pos_unidad_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->disponibilidadUnidad($_GET));
  }

  public function pos_carrito_prevalidar_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->prevalidarCarritoPos($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular confirmacion POS sin escribir venta, pagos ni inventario.
   * Impacto: permite validar contrato completo antes de autorizar DDL/transacciones.
   * Contrato: endpoint dry-run; siempre bloquea si falta esquema o hay bloqueos operativos.
   */
  public function pos_confirmar_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->confirmarVentaPosDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-29.
   * Proposito: confirmar cobro real POS desde la UI autorizada.
   * Impacto: crea venta, pagos, caja, kardex, trazabilidad y snapshots; puede consumir excepcion comercial autorizada.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo recalcula todo en backend.
   */
  public function pos_confirmar_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    $respuesta = $this->modelo("VentasErp")->confirmarVentaPosReal($_POST);
    $this->auditarCobroPos("confirmar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: consultar ticket formal de devolucion/cancelacion POS aplicada.
   * Impacto: permite reimpresion/validacion read-only sin tocar caja, inventario ni venta original.
   * Contrato: GET protegido por `ventas.ver`; no escribe BD.
   */
  public function pos_ticket_devolucion_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->ticketDevolucionFormalReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular pedido/apartado con reserva sin escribir inventario.
   * Impacto: prepara Pedidos ERP separados de ecommerce legacy.
   * Contrato: dry-run; valida contrato de reserva pero no aparta stock.
   */
  public function pedido_reserva_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->pedidoReservaDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-05.
   * Proposito: crear pedido/apartado POS real con reserva y anticipo opcional.
   * Impacto: escribe venta ERP, reservas, caja/pago y eventos; no descuenta kardex hasta entrega.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo recalcula en backend.
   */
  public function pedido_guardar_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->pedidoGuardarReal($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular resolucion de cliente/lista/precio sin escribir datos.
   * Impacto: prepara POS para clientes, listas especiales y snapshots de precio.
   * Contrato: dry-run; no crea cliente, no aplica descuentos y no modifica venta.
   */
  public function pos_cliente_precio_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->clientePrecioDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-28.
   * Proposito: simular excepciones comerciales POS sin aplicar precio manual ni descuentos.
   * Impacto: prepara autorizaciones de precio/descuento con motivo y snapshot sin escribir venta.
   * Contrato: dry-run; el navegador no decide precio final ni margen autorizado.
   */
  public function pos_excepcion_comercial_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->excepcionComercialDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-29.
   * Proposito: registrar desde POS un folio real de excepcion comercial autorizada.
   * Impacto: escribe solo la autorizacion comercial; no crea venta, no mueve caja y no descuenta inventario.
   * Contrato: requiere operador y autorizador con permiso; el backend recalcula precio/descuento antes de insertar.
   */
  public function pos_excepcion_comercial_registrar_erp() {
    $this->requerirPermiso("ventas.operar");
    $this->requerirPermiso("ventas.autorizar_excepcion_comercial");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    $_POST["solicitado_por"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    if (empty($_POST["autorizado_por"])) {
      $_POST["autorizado_por"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    }
    $_POST["observaciones"] = "POS UI excepcion comercial autorizada sin venta";
    $respuesta = $this->modelo("VentasErp")->registrarExcepcionComercialAutorizada($_POST);
    $this->auditarExcepcionComercialPos("registrar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-29.
   * Proposito: simular consumo de un folio de excepcion comercial autorizado en POS.
   * Impacto: permite validar precio manual/descuento contra carrito, caja y stock sin cobrar ni mover inventario.
   * Contrato: read-only; no aplica excepcion, no crea venta y no escribe caja/kardex.
   */
  public function pos_excepcion_consumo_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->excepcionComercialConsumoDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: simular alta rapida de cliente POS sin crear registros.
   * Impacto: prepara clientes robustos con identificador unico, duplicados y snapshot futuro de venta.
   * Contrato: dry-run; no escribe BD ni mezcla clientes legacy/ecommerce.
   */
  public function pos_cliente_alta_rapida_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    $this->requerirPermiso("crm.crear");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0;
    $_POST["origen_alta"] = "pos";
    return json_encode($this->modelo("ClientesCrm")->altaRapidaDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular abono de apartado/pedido sin registrar pago ni caja.
   * Impacto: prepara pagos parciales ligados a folio, caja, turno y saldo.
   * Contrato: dry-run; no crea abonos, no reduce saldo y no mueve inventario.
   */
  public function apartado_abono_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->apartadoAbonoDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-05.
   * Proposito: registrar abono/liquidacion real de pedido/apartado POS.
   * Impacto: escribe pago, movimiento de caja, saldo y evento; no mueve inventario.
   * Contrato: POST con CSRF, sesion y `ventas.operar`.
   */
  public function apartado_abono_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->apartadoAbonoReal($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-05.
   * Proposito: entregar pedido/apartado liquidado consumiendo reservas.
   * Impacto: genera kardex, trazabilidad y cambia estatus a entregado.
   * Contrato: POST con CSRF, sesion y `ventas.operar`.
   */
  public function pedido_entregar_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->pedidoEntregarReal($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-05.
   * Proposito: cancelar pedido/apartado POS no entregado y liberar reservas.
   * Impacto: devuelve disponibilidad apartada; pagos quedan para decision financiera posterior.
   * Contrato: POST con CSRF, sesion y `ventas.operar`.
   */
  public function pedido_cancelar_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->pedidoCancelarReal($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: consultar readiness consolidado POS sin ejecutar acciones reales.
   * Impacto: valida turno, ticket, pedidos/apartados y devoluciones antes de autorizar escrituras.
   * Contrato: POST protegido por `ventas.ver`; no cierra turno, no reserva, no abona y no mueve inventario.
   */
  public function pos_readiness_readonly_erp() {
    $this->requerirPermiso("ventas.ver");
    $_POST["id_usuario"] = isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : intval($_POST["id_usuario"] ?? 0);
    return json_encode($this->modelo("VentasErp")->readinessPosReadOnly($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: entregar KPIs read-only para el tablero Ventas ERP.
   * Impacto: no mezcla `ecom_pedidos`; reporta esquema pendiente si faltan tablas nuevas.
   * Contrato: respuesta JSON estandar del ERP.
   */
  public function ventas_resumen_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->resumenVentasModulo($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: listar ventas/pedidos ERP para el tablero nuevo.
   * Impacto: prepara la operacion sin reusar tablas ecommerce legacy.
   * Contrato: respuesta JSON estandar; no escribe BD.
   */
  public function ventas_listar_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->listarVentasErp($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: consultar reporte de caja POS con diferencias por turno.
   * Impacto: supervision gerencial sin escrituras.
   * Contrato: GET protegido por `ventas.ver`; no cierra turnos ni resuelve diferencias.
   */
  public function reportes_caja_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->reporteCajaPosReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: consultar faltantes/sobrantes de caja pendientes de seguimiento.
   * Impacto: prepara revision formal sin resolver diferencias ni mover dinero.
   * Contrato: GET protegido por `ventas.ver`; read-only.
   */
  public function reportes_diferencias_caja_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->diferenciasCajaPendientesReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: resolver desde UI un expediente formal de diferencia de caja POS.
   * Impacto: cambia solo el seguimiento administrativo; no toca turno, caja, ventas ni inventario.
   * Contrato: POST con CSRF, sesion y `ventas.caja_diferencias.resolver`.
   */
  public function reportes_diferencia_caja_resolver_erp() {
    $this->requerirPermisoCajaDiferenciasResolver();
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    $respuesta = $this->modelo("VentasErp")->resolverRevisionDiferenciaCajaPosReal($datos);
    $this->auditarDiferenciaCajaPos("resolver", $respuesta);
    return json_encode($respuesta);
  }

  private function auditarExcepcionComercialPos($accion, $respuesta) {
    SesionSeguridad::registrarAuditoria("ventas", "excepcion_comercial_" . $accion, array(
      "entidad" => "erp_ventas_excepciones_comerciales",
      "entidad_id" => isset($respuesta["depurar"]["id_excepcion_comercial"]) ? $respuesta["depurar"]["id_excepcion_comercial"] : null,
      "resultado" => !empty($respuesta["error"]) ? "error" : (isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" ? "ok" : "warning"),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
  }

  private function auditarCobroPos($accion, $respuesta) {
    SesionSeguridad::registrarAuditoria("ventas", "pos_cobro_" . $accion, array(
      "entidad" => "erp_ventas",
      "entidad_id" => isset($respuesta["depurar"]["id_venta"]) ? $respuesta["depurar"]["id_venta"] : null,
      "resultado" => !empty($respuesta["error"]) ? "error" : (isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" ? "ok" : "warning"),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
    ));
  }

  private function auditarConfiguracionPos($accion, $respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $entidadId = null;
    if (isset($depurar["id_caja"])) {
      $entidadId = $depurar["id_caja"];
    } elseif (isset($depurar["id_terminal_pos"])) {
      $entidadId = $depurar["id_terminal_pos"];
    } elseif (isset($depurar["id_usuario_caja"])) {
      $entidadId = $depurar["id_usuario_caja"];
    } elseif (isset($depurar["id"])) {
      $entidadId = $depurar["id"];
    }
    SesionSeguridad::registrarAuditoria("ventas", "pos_configuracion_" . $accion, array(
      "entidad" => "erp_pos_configuracion",
      "entidad_id" => $entidadId,
      "resultado" => !empty($respuesta["error"]) ? "error" : (isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" ? "ok" : "warning"),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => $depurar
    ));
  }

  private function auditarDiferenciaCajaPos($accion, $respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("ventas", "pos_diferencia_caja_" . $accion, array(
      "entidad" => "erp_pos_turnos_diferencias_revision",
      "entidad_id" => isset($depurar["id_diferencia_revision"]) ? $depurar["id_diferencia_revision"] : null,
      "resultado" => !empty($respuesta["error"]) ? "error" : (isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" ? "ok" : "warning"),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => $depurar
    ));
  }

  private function auditarCierreTurnoPos($accion, $respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("ventas", "pos_turno_" . $accion, array(
      "entidad" => "erp_pos_turnos",
      "entidad_id" => isset($depurar["id_turno_caja"]) ? $depurar["id_turno_caja"] : null,
      "resultado" => !empty($respuesta["error"]) ? "error" : (isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" ? "ok" : "warning"),
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => $depurar
    ));
  }

  private function requerirPermisoCajaDiferenciasResolver() {
    $this->requerirPermiso("ventas.caja_diferencias.resolver");
    return true;
  }

  private function requerirPermisoConfiguracionPosGuardar() {
    $id = 0;
    if (isset($_POST["id_caja"])) {
      $id = intval($_POST["id_caja"]);
    } elseif (isset($_POST["id_terminal_pos"])) {
      $id = intval($_POST["id_terminal_pos"]);
    }
    $this->requerirPermiso($id > 0 ? "ventas.pos_config.editar" : "ventas.pos_config.crear");
    return true;
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: diagnosticar cobertura tecnica del modulo Ventas/POS/Pedidos ERP.
   * Impacto: ayuda a decidir el siguiente paso sin tocar BD.
   * Contrato: read-only; no crea tablas ni consulta ventas legacy como fuente operativa.
   */
  public function diagnostico_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->diagnosticoModuloVentas());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: proponer cajas iniciales por tienda sin escribir datos.
   * Impacto: prepara configuracion POS multi-sucursal antes de autorizar DDL/seed.
   * Contrato: dry-run read-only; no crea cajas ni turnos.
   */
  public function cajas_plan_inicial_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->planCajasInicialesPos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: proponer asignacion persistente usuario/terminal/caja sin escribir datos.
   * Impacto: prepara POS para abrir ligado a sucursal/caja del operador.
   * Contrato: dry-run read-only; no crea terminales ni asignaciones.
   */
  public function terminal_plan_asignacion_erp() {
    $this->requerirPermiso("ventas.ver");
    $datos = $_GET;
    $datos["id_usuario"] = $this->usuarioActualId();
    $datos["usuario_nombre"] = trim((isset($_SESSION["nombres"]) ? $_SESSION["nombres"] : "") . " " . (isset($_SESSION["apellido_paterno"]) ? $_SESSION["apellido_paterno"] : "") . " " . (isset($_SESSION["apellido_materno"]) ? $_SESSION["apellido_materno"] : ""));
    return json_encode($this->modelo("VentasErp")->planAsignacionTerminalPos($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: consultar la asignacion activa usuario/terminal/caja para abrir POS sin selector libre.
   * Impacto: cuando exista esquema POS, la UI podra quedar amarrada automaticamente a tienda/caja del operador.
   * Contrato: read-only; no crea terminales, no crea cajas y no modifica turnos.
   */
  public function terminal_asignacion_actual_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->asignacionActualTerminalPos(array(
        "id_usuario" => $this->usuarioActualId()
    )));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular apertura de turno de caja sin escribir datos.
   * Impacto: prepara control de caja antes de autorizar transacciones POS.
   * Contrato: dry-run; no crea turnos ni movimientos de caja.
   */
  public function turno_apertura_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->aperturaTurnoDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular cierre de turno de caja sin escribir datos.
   * Impacto: prepara corte de caja y diferencias antes de autorizar transacciones POS.
   * Contrato: dry-run; no cierra turno ni crea movimientos.
   */
  public function turno_cierre_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->cierreTurnoDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-04.
   * Proposito: cerrar turno POS real desde Caja/Turnos con confirmacion explicita.
   * Impacto: actualiza `erp_pos_turnos`; no crea ventas, pagos ni movimientos de inventario.
   * Contrato: POST con CSRF, sesion, `ventas.operar`, confirmacion `CERRAR TURNO` y turno abierto asignado.
   */
  public function turno_cierre_real_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    $respuesta = $this->modelo("VentasErp")->cerrarTurnoRealPos($datos);
    $this->auditarCierreTurnoPos("cerrar", $respuesta);
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-04.
   * Proposito: consultar corte formal imprimible de turno POS sin modificar caja.
   * Impacto: permite reimprimir cortes y auditar turnos cerrados desde Caja/Turnos.
   * Contrato: read-only; requiere `ventas.ver`.
   */
  public function corte_turno_readonly_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->corteTurnoFormalReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: simular movimientos de caja no venta sin registrar dinero real.
   * Impacto: prepara gastos, retiros, entradas, vales y reembolsos con reglas de corte.
   * Contrato: dry-run; no inserta movimientos ni modifica turno.
   */
  public function caja_movimiento_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->movimientoCajaDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: consultar movimientos de caja con evidencia pendiente.
   * Impacto: permite seguimiento operativo de reembolsos/gastos sin modificar caja ni adjuntos.
   * Contrato: read-only; requiere `ventas.ver`.
   */
  public function caja_evidencias_pendientes_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->evidenciasCajaPendientesReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: consultar detalle de evidencias capturadas para movimientos sensibles de caja POS.
   * Impacto: permite revisar comprobantes antes de aprobar/rechazar sin modificar caja.
   * Contrato: read-only; requiere `ventas.ver`.
   */
  public function caja_evidencias_detalle_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->evidenciasCajaDetalleReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: aprobar o rechazar evidencia de caja POS recibida.
   * Impacto: cambia estado de evidencia/movimiento sin tocar importes, turnos, pagos ni inventario.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo exige permiso fino o compatibilidad temporal.
   */
  public function caja_evidencia_revisar_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->revisarEvidenciaCajaPosReal($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: solicitar correccion formal de evidencia de caja ya aprobada.
   * Impacto: crea folio de correccion sin editar evidencia historica ni mover caja/inventario.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo exige permiso fino.
   */
  public function caja_evidencia_correccion_solicitar_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->solicitarCorreccionEvidenciaCajaPosReal($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: registrar evidencia correctiva para un folio de correccion abierto.
   * Impacto: agrega evidencia nueva sin editar la original ni modificar caja/inventario.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo exige permiso fino.
   */
  public function caja_evidencia_correccion_evidencia_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->registrarEvidenciaCorrectivaCajaPosReal($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: resolver correccion de evidencia de caja POS en revision.
   * Impacto: cierra el folio de correccion y marca evidencia correctiva sin tocar caja/inventario.
   * Contrato: POST con CSRF, sesion y `ventas.operar`; el modelo exige permiso fino.
   */
  public function caja_evidencia_correccion_resolver_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->resolverCorreccionEvidenciaCajaPosReal($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: simular que una cuenta local POS se convierta en atencion compartida.
   * Impacto: prepara flujo multiusuario/multidispositivo sin crear ventas ni reservas.
   * Contrato: dry-run; no inserta atenciones ni detalle.
   */
  public function atencion_persistente_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    $datos = $_POST;
    $datos["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->atencionPersistenteDryRun($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: consultar bandeja read-only de atenciones compartidas.
   * Impacto: prepara caja para tomar/cobrar cuentas levantadas por vendedores.
   * Contrato: read-only; no bloquea ni convierte atenciones.
   */
  public function atenciones_bandeja_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->atencionesBandejaDryRun($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: generar vista previa de ticket POS sin confirmar venta.
   * Impacto: valida contenido operativo antes de folios reales e impresion.
   * Contrato: dry-run; no genera folio fiscal/venta ni descuenta inventario.
   */
  public function ticket_preview_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->ticketPreviewDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: generar ticket formal read-only desde una venta POS confirmada.
   * Impacto: permite imprimir/reimprimir folios ERP con precio, caja, turno, pagos e inventario sin tocar ecommerce legacy.
   * Contrato: no escribe BD, no recalcula venta historica y reporta garantia pendiente si no existe snapshot.
   */
  public function ticket_venta_readonly_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->ticketVentaFormalReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-07.
   * Proposito: consultar saldo monetario CRM disponible para POS sin mover caja ni ledger.
   * Impacto: permite que el cajero vea si puede usar saldo cliente antes de cobrar.
   * Contrato: GET read-only con sesion y permiso `ventas.operar`.
   */
  public function cliente_saldo_crm_readonly_erp() {
    $this->requerirPermiso("ventas.operar");
    return json_encode($this->modelo("VentasErp")->clienteSaldoCrmReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: simular cancelacion/devolucion sin afectar venta ni inventario.
   * Impacto: prepara reversas controladas con trazabilidad y decision de inventario.
   * Contrato: dry-run; no cancela ventas, no crea devoluciones y no mueve kardex.
   */
  public function devolucion_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->devolucionDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: consultar devoluciones POS pendientes de decision fisica de inventario/almacen.
   * Impacto: permite seguimiento de cuarentena, merma o reintegro sin mover stock.
   * Contrato: read-only; requiere `ventas.ver`.
   */
  public function devoluciones_inventario_pendientes_erp() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErp")->devolucionesInventarioPendientesReadOnly($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: simular decision fisica sobre una partida devuelta sin mover inventario.
   * Impacto: prepara inspeccion de cuarentena/reintegro/merma/garantia antes de autorizacion real.
   * Contrato: dry-run; no crea inspeccion, kardex ni cambios de devolucion.
   */
  public function devolucion_inspeccion_fisica_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->inspeccionFisicaDevolucionDryRun($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-08.
   * Proposito: registrar desde UI una inspeccion fisica documental de devolucion POS.
   * Impacto: cierra cuarentena documental sin reintegrar inventario, sin merma, sin garantia y sin kardex en esta fase.
   * Contrato: POST con CSRF y `ventas.operar`; el modelo solo permite `mantener_cuarentena` en esta etapa.
   */
  public function devolucion_inspeccion_fisica_registrar_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->registrarInspeccionFisicaDevolucionPosReal($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-09.
   * Proposito: prevalidar destino final de una devolucion ya confirmada en cuarentena.
   * Impacto: prepara reintegro, merma, garantia o reparacion sin escribir BD ni mover inventario.
   * Contrato: dry-run/read-only; requiere `ventas.operar`.
   */
  public function devolucion_destino_final_dryrun_erp() {
    $this->requerirPermiso("ventas.operar");
    $_POST["id_usuario"] = $this->usuarioActualId();
    return json_encode($this->modelo("VentasErp")->destinoFinalCuarentenaDevolucionDryRun($_POST));
  }

  public function esquema_auditar_ventas_pos() {
    $this->requerirPermiso("ventas.ver");
    $alcance = isset($_REQUEST["alcance"]) && $_REQUEST["alcance"] === "expandido" ? "expandido" : "base";
    return json_encode($this->modelo("VentasErpEsquema")->auditarVentasPos($alcance));
  }

  public function esquema_actualizar_ventas_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    $alcance = isset($_POST["alcance"]) && $_POST["alcance"] === "expandido" ? "expandido" : "base";
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $tokenEsperado = $alcance === "expandido" ? "VENTAS_POS_DDL_EXPANDIDO" : "VENTAS_POS_DDL_BASE";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== $tokenEsperado || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "alcance" => $alcance,
            "requerido" => array(
              "autorizar" => $tokenEsperado,
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "No ejecutar sin respaldo externo verificado.",
              "No ejecutar sin autorizacion textual del dueno.",
              "Preferir scripts UAT autorizados para aplicar DDL."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarVentasPos($ejecutar, $alcance));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: auditar columnas de caja completa sin ejecutar DDL.
   * Impacto: prepara gastos, retiros, vales, reembolsos y autorizaciones de caja.
   * Contrato: read-only.
   */
  public function esquema_auditar_caja_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarCajaCompleta());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: auditar tablas de atenciones compartidas POS sin ejecutar DDL.
   * Impacto: prepara cuentas persistentes multiusuario sin tocar BD.
   * Contrato: read-only.
   */
  public function esquema_auditar_atenciones_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarAtencionesPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-29.
   * Proposito: auditar estructura necesaria para devoluciones/cancelaciones POS reales.
   * Impacto: prepara reversas con reembolso, caja, inventario y trazabilidad sin ejecutar DDL.
   * Contrato: read-only; no crea tablas, columnas ni movimientos.
   */
  public function esquema_auditar_reversas_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarReversasPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-29.
   * Proposito: generar o aplicar DDL de reversas POS con guardrail explicito.
   * Impacto: extiende devoluciones base para reembolso, saldo a favor, caja e inventario; no ejecuta reversa real.
   * Contrato: solo ejecuta con `VENTAS_POS_REVERSA_DDL` y respaldo externo valido.
   */
  public function esquema_actualizar_reversas_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_REVERSA_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de reversas POS. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_REVERSA_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "No ejecutar sin respaldo externo verificado.",
              "No ejecutar sin autorizacion textual del dueno.",
              "No ejecutar devoluciones reales desde este endpoint; solo prepara estructura."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarReversasPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: auditar estructura para inspeccion fisica de devoluciones POS.
   * Impacto: prepara cierre de cuarentena/reintegro/merma/garantia sin mover inventario.
   * Contrato: read-only; no crea tablas, columnas ni movimientos.
   */
  public function esquema_auditar_inspeccion_fisica_devoluciones_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarInspeccionFisicaDevolucionesPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: generar o aplicar DDL de inspeccion fisica de devoluciones POS.
   * Impacto: crea estructura de seguimiento fisico; no resuelve partidas ni mueve inventario.
   * Contrato: solo ejecuta con `VENTAS_POS_DEVOLUCIONES_FISICAS_DDL` y respaldo externo valido.
   */
  public function esquema_actualizar_inspeccion_fisica_devoluciones_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_DEVOLUCIONES_FISICAS_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de inspeccion fisica. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_DEVOLUCIONES_FISICAS_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "No ejecutar sin respaldo externo verificado.",
              "No resolver partidas desde este endpoint.",
              "No mover inventario desde este endpoint."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarInspeccionFisicaDevolucionesPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-09.
   * Proposito: auditar estructura para destino final de cuarentena POS.
   * Impacto: prepara reintegro, merma, garantia o reparacion sin ejecutar DDL.
   * Contrato: read-only; no cierra cuarentenas ni mueve inventario.
   */
  public function esquema_auditar_destino_final_cuarentena_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarDestinoFinalCuarentenaPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-09.
   * Proposito: generar o aplicar DDL para destino final de cuarentena POS.
   * Impacto: agrega trazabilidad estructural; no reintegra, no merma, no crea garantia y no mueve inventario.
   * Contrato: solo ejecuta con `VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL` y respaldo externo valido.
   */
  public function esquema_actualizar_destino_final_cuarentena_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de destino final de cuarentena. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "No ejecutar sin respaldo externo verificado.",
              "No resolver partidas desde este endpoint.",
              "No mover inventario desde este endpoint."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarDestinoFinalCuarentenaPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-27.
   * Proposito: generar o aplicar DDL de caja POS completa con guardrail explicito.
   * Impacto: extiende `erp_pos_movimientos_caja`; no toca ventas, inventario ni ecommerce.
   * Contrato: solo ejecuta con `VENTAS_POS_CAJA_DDL` y respaldo valido.
   */
  public function esquema_actualizar_caja_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_CAJA_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de caja. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_CAJA_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo,
            "reglas" => array(
              "No ejecutar sin respaldo externo verificado.",
              "No ejecutar sin autorizacion textual del dueno.",
              "No mezclar con DDL base o expandido de Ventas/POS."
            )
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarCajaCompleta($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: auditar estructura de evidencias/adjuntos de caja POS.
   * Impacto: prepara comprobantes de reembolsos/gastos sin ejecutar DDL.
   * Contrato: read-only.
   */
  public function esquema_auditar_evidencias_caja_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarEvidenciasCajaPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: generar o aplicar DDL de evidencias de caja POS con guardrail explicito.
   * Impacto: crea tabla de adjuntos/evidencias; no adjunta archivos ni modifica movimientos.
   * Contrato: solo ejecuta con `VENTAS_POS_CAJA_EVIDENCIAS_DDL` y respaldo valido.
   */
  public function esquema_actualizar_evidencias_caja_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIAS_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de evidencias de caja. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_CAJA_EVIDENCIAS_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarEvidenciasCajaPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: auditar estructura de correcciones para evidencias de caja aprobadas.
   * Impacto: prepara flujo formal de correccion sin editar evidencias historicas.
   * Contrato: read-only.
   */
  public function esquema_auditar_correcciones_evidencias_caja_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarCorreccionesEvidenciasCajaPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-30.
   * Proposito: generar o aplicar DDL de correcciones de evidencias de caja POS con guardrail explicito.
   * Impacto: crea tabla de solicitudes de correccion; no cambia evidencias existentes ni movimientos de caja.
   * Contrato: solo ejecuta con `VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL` y respaldo valido.
   */
  public function esquema_actualizar_correcciones_evidencias_caja_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de correcciones de evidencias de caja. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarCorreccionesEvidenciasCajaPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: auditar estructura de revision formal para diferencias de caja POS.
   * Impacto: prepara seguimiento de faltantes/sobrantes sin ejecutar DDL.
   * Contrato: read-only.
   */
  public function esquema_auditar_revision_diferencias_caja_pos() {
    $this->requerirPermiso("ventas.ver");
    return json_encode($this->modelo("VentasErpEsquema")->auditarRevisionDiferenciasCajaPos());
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-07-03.
   * Proposito: generar o aplicar DDL para revision formal de diferencias de caja POS.
   * Impacto: crea expediente administrativo de faltantes/sobrantes; no modifica turnos ni mueve caja.
   * Contrato: solo ejecuta con `VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL` y respaldo valido.
   */
  public function esquema_actualizar_revision_diferencias_caja_pos() {
    $this->requerirPermiso("sistema.soporte");
    $ejecutar = isset($_POST["ejecutar"]) && intval($_POST["ejecutar"]) === 1;
    if ($ejecutar) {
      $autorizar = isset($_POST["autorizar"]) ? trim((string) $_POST["autorizar"]) : "";
      $respaldo = isset($_POST["respaldo"]) ? trim((string) $_POST["respaldo"]) : "";
      $validacionRespaldo = $this->validarRespaldoVentasPos($respaldo);
      if ($autorizar !== "VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL" || !$validacionRespaldo["ok"]) {
        return json_encode(array(
          "error" => true,
          "tipo" => "danger",
          "mensaje" => "No se ejecuto DDL de revision de diferencias de caja. Falta autorizacion explicita o respaldo valido.",
          "depurar" => array(
            "requerido" => array(
              "autorizar" => "VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL",
              "respaldo" => "RUTA_O_REFERENCIA"
            ),
            "validacion_respaldo" => $validacionRespaldo
          )
        ));
      }
    }
    return json_encode($this->modelo("VentasErpEsquema")->planActualizarRevisionDiferenciasCajaPos($ejecutar));
  }

  /**
   * Documentacion IA: Codex GPT-5, 2026-06-26.
   * Proposito: validar referencia de respaldo antes de permitir DDL desde endpoint web.
   * Impacto: alinea el guardrail del controlador con los scripts UAT autorizados.
   * Contrato: no crea respaldo, no escribe BD y no expone credenciales.
   */
  private function validarRespaldoVentasPos($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
      $existe = file_exists($respaldo);
      $legible = $existe && is_readable($respaldo);
      $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
      "ok" => $okReferencia && $okRuta,
      "referencia_presente" => $okReferencia,
      "parece_ruta_local" => $esRutaLocal,
      "archivo_existe" => $esRutaLocal ? $existe : null,
      "archivo_legible" => $esRutaLocal ? $legible : null,
      "tamano_bytes" => $tamano
    );
  }

  public function estatus_pedido() {
    $ventas = $this->modelo("Venta");
    $estatus_ventas = $ventas->obtener_estatus();
    return json_encode($estatus_ventas);
  }

  public function validar_datos_registro_cliente() {
    
  }

  public function registrar_cliente() {
    
  }

  public function actualizar() {

    //Cliente
    $id_cliente = $_POST['cliente']['id_cliente'];
    $cliente_nombres = $_POST['cliente']['nombre'];
    $cliente_apellido_paterno = $_POST['cliente']['apellido_paterno'];
    $cliente_apellido_materno = $_POST['cliente']['apellido_materno'];
    $cliente_contacto_1 = $_POST['cliente']['contacto_1'];
    $cliente_contacto_2 = $_POST['cliente']['contacto_2'];
    $cliente = $this->modelo("Cliente");
    $cliente->setNombres($cliente_nombres);
    $cliente->setApellido_materno($cliente_apellido_materno);
    $cliente->setApellido_paterno($cliente_apellido_paterno);
    $cliente->setCorreo("");
    $cliente->setContacto1($cliente_contacto_1);
    $cliente->setContacto2($cliente_contacto_2);
    if ($id_cliente == 0 && $cliente_contacto_1 != '0000000000') {
      $respuesta_registro_cliente = $cliente->registrar_cliente();
      if ($respuesta_registro_cliente['error'] == false) {
        $id_cliente = $respuesta_registro_cliente['depurar'];
      }
    } else {
      $respuesta_consulta_cliente = $cliente->consultar_cliente();
      if ($respuesta_consulta_cliente['error'] == false) {
        $id_cliente = $respuesta_consulta_cliente['depurar']['id_cliente'];
        $cliente->setId_cliente($id_cliente);
        $respuesta_actualizar_cliente = $cliente->actualizar_cliente();
      } else {
        $respuesta_registro_cliente = $cliente->registrar_cliente();
        if ($respuesta_registro_cliente['error'] == false) {
          $id_cliente = $respuesta_registro_cliente['depurar'];
        }
      }
    }

//    var_dump($respuesta_consulta_cliente);
//    var_dump($id_cliente);
//    $respuesta = $cliente->actualizar_cliente();
//    var_dump($respuesta);
    $datos_facturacion_default = array(
        "uso_cfdi" => "Sin efectos fiscales",
        "rfc" => "XXAXX",
        "regimen_fiscal" => "Sin obligaciones fiscales"
    );
    //Facturacion
    $id_datos_facturacion = $_POST['cliente']['datos_facturacion']['id_datos_facturacion'];
    $facturacion_facturar = $_POST['cliente']['datos_facturacion']['facturar'];
    $facturacion_uso_cfdi = $_POST['cliente']['datos_facturacion']['uso_cfdi'];
    $facturacion_rfc = $_POST['cliente']['datos_facturacion']['rfc'];
    $facturacion_razon_social = $_POST['cliente']['datos_facturacion']['rfc'];
    $facturacion_regimen_fiscal = $_POST['cliente']['datos_facturacion']['regimen_fiscal'];
    $facturacion_estado = $_POST['cliente']['datos_facturacion']['estado'];
    $facturacion_ciudad = $_POST['cliente']['datos_facturacion']['ciudad'];
    $facturacion_colonia = $_POST['cliente']['datos_facturacion']['colonia'];
    $facturacion_calle = $_POST['cliente']['datos_facturacion']['calle'];
    $facturacion_numero_exterior = $_POST['cliente']['datos_facturacion']['numero_exterior'];
    $facturacion_interior = $_POST['cliente']['datos_facturacion']['numero_interior'];
    $facturacion_codigo_postal = $_POST['cliente']['datos_facturacion']['codigo_postal'];
    //Envio
    $id_datos_envio = $_POST['cliente']['datos_envio']['id_datos_envio'];
    $fecha = $_POST['cliente']['datos_envio']['fecha'];
    $envio_estado = $_POST['cliente']['datos_envio']['estado'];
    $envio_ciudad = $_POST['cliente']['datos_envio']['ciudad'];
    $envio_colonia = $_POST['cliente']['datos_envio']['colonia'];
    $envio_calle = $_POST['cliente']['datos_envio']['calle'];
    $envio_numero_exterior = $_POST['cliente']['datos_envio']['numero_exterior'];
    $envio_numero_interior = $_POST['cliente']['datos_envio']['numero_interior'];
    $envio_codigo_postal = $_POST['cliente']['datos_envio']['codigo_postal'];
    $envio_referencias = $_POST['cliente']['datos_envio']['referencias'];

    //Pedido
    //totales
    $id_pedido = $_POST['pedido']['id_pedido'];
    $pedido_subtotal = $_POST['pedido']['subtotal'];
    $pedido_descuento = $_POST['pedido']['descuento'];
    $pedido_envio = $_POST['pedido']['envio'];
    $pedido_iva = $_POST['pedido']['iva'];
    $pedido_total = $_POST['pedido']['total'];
    //productos
    $pedido_productos = $_POST['pedido']['productos'];
    //tipos entrega
    $id_tipo_entrega = $_POST['tipo_entrega']['id_tipo_entrega'];
    $tipo_entrega = $_POST['tipo_entrega']['tipo_entrega'];
    $puntos_entrega = $_POST['tipo_entrega']['puntos_entrega'];
    //horario entrega
    $fecha_entrega = $_POST['horario_entrega']['fecha_entrega'];
    $hora_entrega = $_POST['horario_entrega']['hora_entrega'];
    $turno_horario = $_POST['horario_entrega']['turno_horario'];
    //pagos
    $pagos = $_POST['pagos'];

    $venta = $this->modelo("Venta");
    //datos envio
    $venta->setFecha_entrega($fecha);
    $venta->setId_datos_envio($id_datos_envio);
    $venta->setEnvio_estado($envio_estado);
    $venta->setEnvio_ciudad($envio_ciudad);
    $venta->setEnvio_colonia($envio_colonia);
    $venta->setEnvio_calle($envio_calle);
    $venta->setEnvio_numero_exterior($envio_numero_exterior);
    $venta->setEnvio_numero_interior($envio_numero_interior);
    $venta->setEnvio_referencias($envio_referencias);
    $venta->setEnvio_codigo_postal($envio_codigo_postal);

    $respuesta = $venta->actualizar_datos_envio();
//    $id_datos_envio = $respuesta['depurar'];
    var_dump($respuesta);
    //datos facturacion
    $venta->setId_datos_facturacion($id_datos_facturacion);
    $venta->setRazon_social($facturacion_razon_social);
    $venta->setRfc($facturacion_rfc);
    $venta->setRegimen_fiscal($facturacion_regimen_fiscal);
    $venta->setUso_cfdi($facturacion_uso_cfdi);
    $venta->setFacturacion_estado($envio_estado);
    $venta->setFacturacion_ciudad($envio_ciudad);
    $venta->setFacturacion_colonia($envio_colonia);
    $venta->setFacturacion_calle($envio_calle);
    $venta->setFacturacion_numero_exterior($envio_numero_exterior);
    $venta->setFacturacion_numero_interior($envio_numero_interior);
    $venta->setFacturacion_referencias($envio_referencias);
    $venta->setFacturacion_codigo_postal($envio_codigo_postal);

    $respuesta = $venta->actualizar_datos_facturacion();
    $id_datos_facturacion = $respuesta['depurar'];
    var_dump($respuesta);
    //pedido
    $agente = $this->obtenerNavegadorWeb();
    $venta->setId_pedido($id_pedido);
    $venta->setIp($this->getRealIP());
    $venta->setNavegador($agente ? $agente['agente'] : null);
    $venta->setId_cliente($id_cliente);
    $venta->setDescuento($pedido_descuento);
    $venta->setEnvio($pedido_envio);
    $venta->setIva($pedido_iva);
    $venta->setSubtotal($pedido_subtotal);
    $venta->setTotal($pedido_total);

    $estatus_pedido = $this->procesar_pagos($pedido_subtotal, $pedido_envio, $pedido_descuento, $pagos);
//    var_dump("estatus_pedido: " . $estatus_pedido);
    $venta->setEstatus($estatus_pedido);

    $respuesta = $venta->actualizar_pedido();
    var_dump($respuesta);
//    $id_pedido = $respuesta['depurar'];
    //pagos
    $errores_pagos = array();
    if (sizeof($pagos) > 0) {
      $venta->setId_pedido($id_pedido);
      $respuesta_eliminar_pagos = $venta->eliminar_pagos();
      var_dump($respuesta_eliminar_pagos);
      if ($respuesta_eliminar_pagos['error'] == false) {
        foreach ($pagos as $pago) {
          $venta->setId_pedido($id_pedido);
          $venta->setId_metodo_pago($pago['metodo']);
          $venta->setCantidad_pago($pago['cantidad']);
          $respuesta = $venta->registrar_pagos();
//        var_dump($respuesta);
          if ($respuesta['error'] == false) {
            $errores_pagos[] = $respuesta;
          }
        }
      }
    }
    var_dump(sizeof($pedido_productos));
    if (sizeof($pedido_productos) > 0) {
      $venta->setId_pedido($id_pedido);
      $respuesta_eliminar_productos = $venta->eliminar_productos();
      var_dump($respuesta_eliminar_productos);
      if ($respuesta_eliminar_productos['error'] == false) {
        foreach ($pedido_productos as $key => $values) {
          $id_producto = $values['id_producto'];
          $cantidad = $values['cantidad'];
          $precio = $values['precio'];
          $importe = $cantidad * $precio;
          $portada = $values['portada'];
          $nombre = $values['producto'];
          $tipo = $values['tipo_item'];
          //pedido
          $venta->setId_pedido($id_pedido);
          $venta->setId_producto($id_producto);
          $venta->setProducto($nombre);
          $venta->setCantidad($cantidad);
          $venta->setPrecio($precio);
          $venta->setDescuento(0);
          $venta->setIva(0);
          $venta->setItem_tipo($tipo);
          $venta->setSubtotal($importe);
          $respuesta = $venta->registrar_productos();
          if ($respuesta['error'] == true) {
            $errores_productos_pedido[] = "";
          }
        }
      }
    }

    var_dump($errores_pagos);
    //productos pedido
  }

  public function registrar() {

    //Cliente
    $cliente_nombres = $_POST['cliente']['nombre'];
    $cliente_apellido_paterno = $_POST['cliente']['apellido_paterno'];
    $cliente_apellido_materno = $_POST['cliente']['apellido_materno'];
    $cliente_contacto_1 = $_POST['cliente']['contacto_1'];
    $cliente_contacto_2 = $_POST['cliente']['contacto_2'];
    $cliente = $this->modelo("Cliente");
    $cliente->setNombres($cliente_nombres);
    $cliente->setApellido_materno($cliente_apellido_materno);
    $cliente->setApellido_paterno($cliente_apellido_paterno);
    $cliente->setCorreo("");
    $cliente->setContacto1($cliente_contacto_1);
    $cliente->setContacto2($cliente_contacto_2);
    //validar si ya existe
    $respuesta_cliente = $cliente->consultar_cliente();
    $id_cliente = 0;
    if ($respuesta_cliente['error'] == false) {
      $id_cliente = $respuesta_cliente['depurar']['id_cliente'];
    } else {
      $respuesta_cliente = $cliente->registrar_cliente();
      $id_cliente = $respuesta_cliente['depurar'];
    }

    $datos_facturacion_default = array(
        "uso_cfdi" => "Sin efectos fiscales",
        "rfc" => "XXAXX",
        "regimen_fiscal" => "Sin obligaciones fiscales"
    );
    //Facturacion
    $facturacion_facturar = $_POST['cliente']['datos_facturacion']['facturar'];
    $facturacion_uso_cfdi = $_POST['cliente']['datos_facturacion']['uso_cfdi'];
    $facturacion_rfc = $_POST['cliente']['datos_facturacion']['rfc'];
    $facturacion_razon_social = $_POST['cliente']['datos_facturacion']['razon_social'];
    $facturacion_regimen_fiscal = $_POST['cliente']['datos_facturacion']['regimen_fiscal'];
    $facturacion_estado = $_POST['cliente']['datos_facturacion']['estado'];
    $facturacion_ciudad = $_POST['cliente']['datos_facturacion']['ciudad'];
    $facturacion_colonia = $_POST['cliente']['datos_facturacion']['colonia'];
    $facturacion_calle = $_POST['cliente']['datos_facturacion']['calle'];
    $facturacion_numero_exterior = $_POST['cliente']['datos_facturacion']['numero_exterior'];
    $facturacion_interior = $_POST['cliente']['datos_facturacion']['numero_interior'];
    $facturacion_codigo_postal = $_POST['cliente']['datos_facturacion']['codigo_postal'];
    //Envio
    $envio_estado = $_POST['cliente']['datos_envio']['estado'];
    $envio_ciudad = $_POST['cliente']['datos_envio']['ciudad'];
    $envio_colonia = $_POST['cliente']['datos_envio']['colonia'];
    $envio_calle = $_POST['cliente']['datos_envio']['calle'];
    $envio_numero_exterior = $_POST['cliente']['datos_envio']['numero_exterior'];
    $envio_numero_interior = $_POST['cliente']['datos_envio']['numero_interior'];
    $envio_codigo_postal = $_POST['cliente']['datos_envio']['codigo_postal'];
    $envio_referencias = $_POST['cliente']['datos_envio']['referencias'];
    $fecha_entrega = $_POST['cliente']['datos_envio']['horario_entrega']['fecha_entrega'];
    $hora_inicial = $_POST['cliente']['datos_envio']['horario_entrega']['hora_inicial_entrega'];
    $hora_final = $_POST['cliente']['datos_envio']['horario_entrega']['hora_final_entrega'];
    //Pedido
    //totales
    $pedido_subtotal = $_POST['pedido']['subtotal'];
    $pedido_descuento = $_POST['pedido']['descuento'];
    $pedido_envio = $_POST['pedido']['envio'];
    $pedido_iva = $_POST['pedido']['iva'];
    $pedido_total = $_POST['pedido']['total'];
    $pedido_estatus = $_POST['pedido']['estatus'];
    //productos
    $pedido_productos = $_POST['pedido']['productos'];
    //tipos entrega
    $id_tipo_entrega = $_POST['tipo_entrega']['id_tipo_entrega'];
    $tipo_entrega = $_POST['tipo_entrega']['tipo_entrega'];
    $puntos_entrega = $_POST['tipo_entrega']['puntos_entrega'];
    //horario entrega
    $fecha_entrega = $_POST['cliente']['datos_envio']['horario_entrega']['fecha_entrega'];
    $hora_entrega_inicial = $_POST['cliente']['datos_envio']['horario_entrega']['hora_inicial_entrega'];
    $hora_entrega_final = $_POST['cliente']['datos_envio']['horario_entrega']['hora_final_entrega'];
    $turno_horario = $_POST['cliente']['datos_envio']['horario_entrega']['turno_horario'];
    //pagos
    $pagos = $_POST['pagos'];

    $venta = $this->modelo("Venta");
    $venta->setFecha_entrega($fecha_entrega);
    $venta->setHora_inicial($hora_entrega_inicial);
    $venta->setHora_final($hora_entrega_final);
    $venta->setEnvio_estado($envio_estado);
    $venta->setEnvio_ciudad($envio_ciudad);
    $venta->setEnvio_colonia($envio_colonia);
    $venta->setEnvio_calle($envio_calle);
    $venta->setEnvio_numero_exterior($envio_numero_exterior);
    $venta->setEnvio_numero_interior($envio_numero_interior);
    $venta->setEnvio_referencias($envio_referencias);
    $venta->setEnvio_codigo_postal($envio_codigo_postal);
    //datos envio
    $respuesta = $venta->registrar_datos_envio();
    $id_datos_envio = $respuesta['depurar'];

    $venta->setRazon_social($facturacion_razon_social);
    $venta->setRfc($facturacion_rfc);
    $venta->setRegimen_fiscal($facturacion_regimen_fiscal);
    $venta->setUso_cfdi($facturacion_uso_cfdi);
    $venta->setFacturacion_estado($envio_estado);
    $venta->setFacturacion_ciudad($envio_ciudad);
    $venta->setFacturacion_colonia($envio_colonia);
    $venta->setFacturacion_calle($envio_calle);
    $venta->setFacturacion_numero_exterior($envio_numero_exterior);
    $venta->setFacturacion_numero_interior($envio_numero_interior);
    $venta->setFacturacion_referencias($envio_referencias);
    $venta->setFacturacion_codigo_postal($envio_codigo_postal);
    //datos facturacion
    $respuesta = $venta->registrar_datos_facturacion();
    $id_datos_facturacion = $respuesta['depurar'];
    var_dump($respuesta);

    $agente = $this->obtenerNavegadorWeb();
    $venta->setIp($this->getRealIP());
    $venta->setNavegador($agente ? $agente['agente'] : null);
    $venta->setId_cliente($id_cliente);
    $venta->setId_datos_envio($id_datos_envio);
    $venta->setId_datos_facturacion($id_datos_facturacion);
    $venta->setDescuento($pedido_descuento);
    $venta->setEnvio($pedido_envio);
    $venta->setIva($pedido_iva);
    $venta->setSubtotal($pedido_subtotal);
    $venta->setTotal($pedido_total);

//    $estatus_pedido = $this->procesar_pagos($pedido_subtotal, $pedido_envio, $pedido_descuento, $pagos);
//    var_dump("estatus_pedido: " . $estatus_pedido);
    $venta->setEstatus($pedido_estatus);
    //pedido
    $respuesta = $venta->registrar_pedido();
    var_dump($respuesta);
    $id_pedido = $respuesta['depurar'];

    //pedido estatus
    $estatus_pedido = $this->registrar_estatus_pedido($venta, $pedido_estatus, $id_pedido);

    //pagos
    $errores_pagos = array();
    $total_pagos = 0;
    if (sizeof($pagos) > 0) {
      foreach ($pagos as $pago) {
        $venta->setId_pedido($id_pedido);
        $venta->setId_metodo_pago($pago['metodo']);
        $venta->setCantidad_pago($pago['cantidad']);
        $total_pagos .= $pago['cantidad'];
        $respuesta = $venta->registrar_pagos();
        var_dump($respuesta);
        if ($respuesta['error'] == false) {
          $errores_pagos[] = $respuesta;
        }
      }
    }

    //pedido estatus
    if ($total_pagos == $pedido_total) {
      $estatus_pedido = $this->registrar_estatus_pedido($venta, 4, $id_pedido);
    }

    foreach ($pedido_productos as $key => $values) {
      $id_producto = $values['id_producto'];
      $cantidad = $values['cantidad'];
      $precio = $values['precio'];
      $importe = $cantidad * $precio;
      $portada = $values['portada'];
      $nombre = $values['producto'];
      $tipo = $values['tipo_item'];
      //pedido
      $venta->setId_pedido($id_pedido);
      $venta->setId_producto($id_producto);
      $venta->setProducto($nombre);
      $venta->setCantidad($cantidad);
      $venta->setPrecio($precio);
      $venta->setDescuento(0);
      $venta->setIva(0);
      $venta->setItem_tipo($tipo);
      $venta->setSubtotal($importe);
      $respuesta = $venta->registrar_productos();
      if ($respuesta['error'] == true) {
        $errores_productos_pedido[] = "";
      }
    }
    var_dump($errores_pagos);
    //productos pedido
  }

  public function registrar_estatus_pedido($venta, $pedido_estatus, $id_pedido) {

    $venta->setId_estatus_pedido($pedido_estatus);
    $venta->setId_pedido($id_pedido);
    $respuesta = $venta->consultar_existencia_estatus_pedido();
    if ($respuesta['error'] == false) {
      //
    } else {
      $respuesta = $venta->insertar_estatus_pedido();
    }
  }

  public function consultar() {
    $venta = $this->modelo("Venta");
    $respuesta = $venta->consultar_lista_ventas();
    return json_encode($respuesta);
  }

  public function consulta_completa() {
    $id_pedido = $_POST['id_pedido'] && $_POST['id_pedido'] != null ? $_POST['id_pedido'] : 0;
    $respuesta = [
        'error' => true,
        'tipo' => "danger",
        'mensaje' => "Datos incorrectos",
        'depurar' => []
    ];
    //
    if ($id_pedido != 0) {
      $pedido = array();
      //pedido
      $venta = $this->modelo("Venta");
      $venta->setId_pedido($id_pedido);
      $respuesta = $venta->consultar_pedido();
      if ($respuesta['error'] == false) {
        $info_pedido = $respuesta['depurar'];
        $id_cliente = $info_pedido['id_cliente'];
        $id_datos_envio = $info_pedido['id_datos_envio'];
        $id_datos_facturacion = $info_pedido['id_datos_facturacion'];
        $pedido['pedido'] = $info_pedido;
        //cliente
        $venta->setId_cliente($id_cliente);
        $respuesta = $venta->consultar_cliente();
        if ($respuesta['error'] == false) {
          $info_cliente = $respuesta['depurar'];
          $pedido['cliente'] = $info_cliente;
        }

        //datos envio
        $venta->setId_datos_envio($id_datos_envio);
        $respuesta = $venta->consultar_datos_envio();
        if ($respuesta['error'] == false) {
          $datos_envio = $respuesta['depurar'];
          $fecha_y_hora = $datos_envio['fecha_entrega'];
          $separar = (explode(" ", $fecha_y_hora));
          $fecha = $separar[0];
          $hora = $separar[1];
          $datos_envio['fecha'] = $fecha;
          $datos_envio['hora'] = $hora;
          $pedido['datos_envio'] = $datos_envio;
        }
        //datos facturacion
        $venta->setId_datos_facturacion($id_datos_facturacion);
        $respuesta = $venta->consultar_datos_facturacion();
        if ($respuesta['error'] == false) {
          $datos_facturacion = $respuesta['depurar'];
          $pedido['datos_facturacion'] = $datos_facturacion;
        }
        //pagos
        $venta->setId_pedido($id_pedido);
        $respuesta = $venta->consultar_pagos();
        if ($respuesta['error'] == false) {
          $pagos = $respuesta['depurar'];
          $pedido['pagos'] = $pagos;
        }

        //productos pedido
        $ids_productos_pedido = array();
        $arreglo_productos_por_id = array();
        $ids_tipo_producto = array();
        $venta->setId_pedido($id_pedido);
        $respuesta = $venta->consultar_productos();
//        var_dump($respuesta);
        if ($respuesta['error'] == false) {
          $productos_pedido = $respuesta['depurar'];
          foreach ($productos_pedido as $key => $value) {
            if (!in_array($value['id_producto'], $arreglo_productos_por_id)) {
              $ids_tipo_producto[$value['id_producto']] = $value['tipo'];
              $ids_productos_pedido[] = $value['id_producto'];
              $arreglo_productos_por_id[$value['id_producto']] = $value;
            }
          }
//          $respuesta = $this->consultar_productos_para_editar($productos);
//          if ($respuesta['error'] == false) {
//            $productos = $respuesta['depurar'];
          $pedido['arr_ids'] = $ids_productos_pedido;
          $pedido['productos_pedido'] = $productos_pedido;
//          } else {
//            
//          }
        }

        //productos 
        $productos_resp = array();
        $productos = $this->modelo("Productos");
        $respuesta = $productos->listar_productos_imagen();
        if ($respuesta['error'] == false) {
          $productos_resp = $respuesta['depurar'];
          $pedido['productos'] = $productos_resp;
        }

        //paquetes 
        $paquete = $this->modelo("Paquete");
        $paquetes = array();
        $respuesta = $paquete->listar_paquetes_imagen();
        if ($respuesta['error'] == false) {
          $paquetes = $respuesta['depurar'];
          $pedido['paquetes'] = $paquetes;
        }
//        var_dump($ids_productos_pedido);
        $items = array_merge($paquetes, $productos_resp);
        foreach ($items as $key => $value) {
          if ($items[$key]['tipo_item'] == "paquete" && in_array($items[$key]['id_paquete'], $ids_productos_pedido)) {
            if ($arreglo_productos_por_id[$items[$key]['id_paquete']]['tipo'] == 'paquete') {
              $items[$key]['cantidad'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['cantidad'];
              $items[$key]['descuento'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['descuento'];
              $items[$key]['id_pedido'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['id_pedido'];
              $items[$key]['precio'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['precio'];
              $items[$key]['subtotal'] = $arreglo_productos_por_id[$items[$key]['id_paquete']]['subtotal'];
            }
          } else if ($items[$key]['tipo_item'] == "producto" && in_array($items[$key]['id_producto'], $ids_productos_pedido)) {
            if ($arreglo_productos_por_id[$items[$key]['id_producto']]['tipo'] == 'producto') {
              $items[$key]['cantidad'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['cantidad'];
              $items[$key]['descuento'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['descuento'];
              $items[$key]['id_pedido'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['id_pedido'];
              $items[$key]['precio'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['precio'];
              $items[$key]['subtotal'] = $arreglo_productos_por_id[$items[$key]['id_producto']]['subtotal'];
            }
          }
        }

        $pedido['productos'] = $items;

        $respuesta = [
            'error' => false,
            'tipo' => "success",
            'mensaje' => "Pedido encontrado",
            'depurar' => $pedido
        ];
      } else {
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Pedido no encontrado",
            'depurar' => []
        ];
      }
    } else {
      $respuesta = [
          'error' => true,
          'tipo' => "danger",
          'mensaje' => "Pedido inválido",
          'depurar' => []
      ];
    }
    return json_encode($respuesta);
  }

  private function consultar_productos_para_editar($productos) {
    $array_ids = array();
    $length = sizeof($productos) - 1;
    foreach ($productos as $key => $value) {
//      var_dump($value);
      $array_ids[] = $value['id_producto'];
    }
    $string_ids = implode(",", $array_ids);
    $productos = $this->modelo("Productos");
    $productos->setId_producto($string_ids);
    $respuesta = $productos->consultar_para_editar_productos();
//    var_dump($respuesta);
    if ($respuesta['error'] == false) {
      $respuesta = [
          'error' => false,
          'tipo' => "success",
          'mensaje' => "Productos encontrados",
          'depurar' => $respuesta['depurar']
      ];
    } else {
      $respuesta = [
          'error' => true,
          'tipo' => "danger",
          'mensaje' => "Productos no encontrados",
          'depurar' => []
      ];
    }
//    var_dump($respuesta);
    return $respuesta;
  }

  private function procesar_pagos($productos_total, $pedido_envio, $pedido_descuento, $pagos) {
    $total_pagos = 0;
    foreach ($pagos as $pago) {
      $total_pago += intval($pago['cantidad']);
    }
    var_dump($total_pago);
    var_dump($productos_total);
    var_dump("pedido_envio: " . $pedido_envio);
    var_dump("pedido_descuento: " . $pedido_descuento);
    $total_venta = intval($productos_total) + intval($pedido_envio) - intval($pedido_descuento);
    var_dump("total_pago: " . $total_pago);
    var_dump("productos_total: " . $total_venta);
    if ($total_pago == $total_venta) {
      return "pedido_pagado";
    } else {
      return "pedido_nuevo";
    }
  }

}
