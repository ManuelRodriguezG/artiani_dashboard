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
    $this->abrirSeccion("resumen", "/distribucionadmin/administracion");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-01
   * Proposito: abrir una pagina operativa separada del modulo Distribucion.
   * Impacto: UI ERP Distribucion; evita concentrar todas las areas dentro de Administracion.
   * Contrato: vista protegida por `distribucion.ver`; no escribe por si misma.
   */
  private function abrirSeccion($seccion, $rutaCanonica) {
    $this->requerirPermiso("distribucion.ver");
    $secciones = array("resumen", "solicitudes", "clientes", "pedidos", "mi_catalogo", "inventarios", "sugeridos", "productos", "demanda");
    if (!in_array($seccion, $secciones, true)) {
      $seccion = "resumen";
    }
    if ($seccion === "pedidos") {
      $this->requerirPermiso("distribucion.cotizaciones.ver");
    }
    if ($seccion === "productos") {
      $this->requerirPermiso("distribucion.editar");
    }
    $vistas = array(
      "resumen" => "resumen",
      "solicitudes" => "solicitudes",
      "clientes" => "clientes",
      "pedidos" => "pedidos",
      "mi_catalogo" => "mi_catalogo",
      "inventarios" => "inventarios",
      "sugeridos" => "sugeridos",
      "productos" => "productos",
      "demanda" => "demanda"
    );
    $this->vista("apps/erp/distribucion/" . $vistas[$seccion], array(
      "modulo" => "distribucion",
      "seccion_activa" => $seccion,
      "ruta_canonica" => $rutaCanonica
    ));
  }

  public function panel_resumen() {
    $this->abrirSeccion("resumen", "/distribucionadmin/panel_resumen");
  }

  public function panel_solicitudes() {
    $this->abrirSeccion("solicitudes", "/distribucionadmin/panel_solicitudes");
  }

  public function panel_clientes() {
    $this->abrirSeccion("clientes", "/distribucionadmin/panel_clientes");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: abrir ficha operativa enfocada para atender un cliente Distribucion.
   * Impacto: UX ERP Distribucion; evita administrar acciones profundas desde modales en la bandeja.
   * Contrato: vista protegida por `distribucion.ver`; no escribe por si misma.
   */
  public function cliente($idCliente = 0) {
    $this->abrirClienteSeccion("cliente", intval($idCliente), "Atender cliente");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: abrir configuracion dedicada de listas/sublistas comerciales por cliente.
   * Impacto: UX ERP Distribucion; permite manejar varias listas por cliente y productos habilitados por lista.
   * Contrato: vista protegida por `distribucion.asignar_precios`; no escribe por si misma.
   */
  public function cliente_listas($idCliente = 0) {
    $this->requerirPermiso("distribucion.asignar_precios");
    $this->abrirClienteSeccion("cliente_listas", intval($idCliente), "Listas del cliente");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: abrir preferencias de categorias de un cliente en pantalla dedicada.
   * Impacto: UX ERP Distribucion; separa editar intereses de la asignacion de productos por lista.
   * Contrato: vista protegida por `distribucion.editar`; no escribe por si misma.
   */
  public function cliente_categorias($idCliente = 0) {
    $this->requerirPermiso("distribucion.editar");
    $this->abrirClienteSeccion("cliente_categorias", intval($idCliente), "Preferencias del cliente");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: abrir permisos comerciales de un cliente en pantalla dedicada.
   * Impacto: UX ERP Distribucion; reemplaza edicion profunda en modal por flujo enfocado.
   * Contrato: vista protegida por `distribucion.editar`; no escribe por si misma.
   */
  public function cliente_permisos($idCliente = 0) {
    $this->requerirPermiso("distribucion.editar");
    $this->abrirClienteSeccion("cliente_permisos", intval($idCliente), "Permisos del cliente");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: abrir configuracion logistica del cliente en pantalla dedicada.
   * Impacto: UX ERP Distribucion; permite revisar direccion/metodo sin modal.
   * Contrato: vista protegida por `distribucion.editar`; no escribe por si misma.
   */
  public function cliente_entrega($idCliente = 0) {
    $this->requerirPermiso("distribucion.editar");
    $this->abrirClienteSeccion("cliente_entrega", intval($idCliente), "Entrega del cliente");
  }

  private function abrirClienteSeccion($vista, $idCliente, $titulo) {
    $this->requerirPermiso("distribucion.ver");
    $permitidas = array("cliente", "cliente_listas", "cliente_categorias", "cliente_permisos", "cliente_entrega");
    if (!in_array($vista, $permitidas, true)) {
      $vista = "cliente";
    }
    $this->vista("apps/erp/distribucion/" . $vista, array(
      "modulo" => "distribucion",
      "seccion_activa" => "clientes",
      "ruta_canonica" => "/distribucionadmin/" . $vista . "/" . intval($idCliente),
      "id_cliente_distribucion" => intval($idCliente),
      "titulo_cliente_distribucion" => $titulo
    ));
  }

  public function panel_pedidos() {
    $this->abrirSeccion("pedidos", "/distribucionadmin/panel_pedidos");
  }

  public function panel_mi_catalogo() {
    $this->abrirSeccion("mi_catalogo", "/distribucionadmin/panel_mi_catalogo");
  }

  public function panel_inventarios() {
    $this->abrirSeccion("inventarios", "/distribucionadmin/panel_inventarios");
  }

  public function panel_sugeridos() {
    $this->abrirSeccion("sugeridos", "/distribucionadmin/panel_sugeridos");
  }

  public function panel_productos() {
    $this->abrirSeccion("productos", "/distribucionadmin/panel_productos");
  }

  public function panel_demanda() {
    $this->abrirSeccion("demanda", "/distribucionadmin/panel_demanda");
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
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: consultar resumen operativo interno de Distribucion.
   * Impacto: Dashboard ERP Distribucion; consolida alertas sin exponer datos al frontend externo.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function resumen() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("distribucionanaliticainterna")->resumenInterno($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: consultar analitica interna de demanda Distribucion.
   * Impacto: ERP Distribucion; ayuda a anticipar compras, publicaciones y seguimiento comercial.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function demanda() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("distribucionanaliticainterna")->demandaInterna($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: entregar catalogos internos para filtros de Distribucion.
   * Impacto: UI ERP Distribucion; evita hardcodear marcas, categorias y proveedores.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function catalogos_filtros() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("distribucionanaliticainterna")->catalogosInternos());
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
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: consultar ficha completa interna de cliente Distribucion.
   * Impacto: UX ERP Distribucion; alimenta paginas dedicadas de atencion.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_detalle() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->clienteDetalleInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-11
   * Proposito: consultar auditoria interna de accesos/activaciones de un cliente Distribucion.
   * Impacto: Admin ERP Distribucion; permite trazabilidad operativa sin exponer tokens ni contrasenas.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_auditoria() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->auditoriaClienteInterna($_GET));
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
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: consultar listas asignadas a un cliente Distribucion.
   * Impacto: Admin ERP Distribucion; separa listas y sublistas de la bandeja general de clientes.
   * Contrato: GET protegido por `distribucion.asignar_precios`; read-only.
   */
  public function cliente_listas_asignadas() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->listasClienteInternas($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: consultar productos de una lista de precios y su habilitacion para el cliente.
   * Impacto: Admin ERP Distribucion; permite crear sublistas por cliente sin reglas genericas.
   * Contrato: GET protegido por `distribucion.asignar_precios`; read-only.
   */
  public function cliente_lista_productos() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->productosListaClienteInternos($_GET));
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
   * Proposito: listar SKUs ERP publicables en canal Distribucion.
   * Impacto: Admin ERP Distribucion; alimenta publicacion permanente con datos reales del catalogo.
   * Contrato: GET protegido por `distribucion.editar`; read-only.
   */
  public function skus_publicables() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionCatalogoApi")->skusPublicablesInternos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: publicar/reactivar SKU ERP en canal Distribucion.
   * Impacto: Catalogo Distribucion; habilita visibilidad externa permanente.
   * Contrato: POST protegido por `distribucion.editar`; valida precio activo y audita.
   */
  public function publicar_sku() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionCatalogoApi")->publicarSkuInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: desactivar SKU del canal Distribucion sin borrar historial.
   * Impacto: Catalogo Distribucion; retira visibilidad externa de forma auditada.
   * Contrato: POST protegido por `distribucion.editar`; baja logica del vinculo.
   */
  public function desactivar_sku() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionCatalogoApi")->desactivarSkuInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: aprobar cliente Distribucion con escritura auditada.
   * Impacto: Admin ERP Distribucion; crea/activa cliente sin asignar listas/permisos automaticamente.
   * Contrato: POST protegido por `distribucion.aprobar_clientes`; registra auditoria propia.
   */
  public function cliente_aprobar() {
    $this->requerirPermiso("distribucion.aprobar_clientes");
    if (intval(isset($_POST["id_lista_precio"]) ? $_POST["id_lista_precio"] : 0) > 0) {
      $this->requerirPermiso("distribucion.asignar_precios");
    }
    $permisos = isset($_POST["permisos"]) ? trim((string) $_POST["permisos"]) : "";
    if ($permisos !== "" && $permisos !== "[]" && $permisos !== "null") {
      $this->requerirPermiso("distribucion.editar");
    }
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: generar link manual de activacion de contrasenia para cliente aprobado.
   * Impacto: Admin ERP Distribucion; permite reenviar WhatsApp/correo manual despues de recargar la vista.
   * Contrato: POST protegido por `distribucion.aprobar_clientes`; revoca activaciones anteriores activas.
   */
  public function cliente_activacion_link() {
    $this->requerirPermiso("distribucion.aprobar_clientes");
    return json_encode($this->modelo("DistribucionClientesApi")->clienteActivacionLinkInterno($_POST, $this->usuarioActualId()));
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
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: guardar una lista comercial asignada a cliente Distribucion sin desactivar otras listas.
   * Impacto: Admin ERP Distribucion; soporta lista base, express y sublistas por producto.
   * Contrato: POST protegido por `distribucion.asignar_precios`; escritura auditada.
   */
  public function cliente_lista_guardar() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->listaClienteGuardarInterna($_POST, $this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-08
   * Proposito: guardar productos habilitados dentro de una lista asignada a cliente.
   * Impacto: Admin ERP Distribucion; permite sublistas de precios sin usar reglas de catalogo.
   * Contrato: POST protegido por `distribucion.asignar_precios`; escritura auditada.
   */
  public function cliente_lista_productos_guardar() {
    $this->requerirPermiso("distribucion.asignar_precios");
    return json_encode($this->modelo("DistribucionClientesApi")->listaClienteProductosGuardarInterna($_POST, $this->usuarioActualId()));
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
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: configurar preferencias logisticas del cliente externo Distribucion.
   * Impacto: Admin ERP Distribucion; define envio/recoger y costo default sin tocar pedidos existentes.
   * Contrato: POST protegido por `distribucion.editar`.
   */
  public function cliente_entrega_configurar() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionClientesApi")->entregaClientePlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: configurar modo e intereses de catalogo visible por cliente Distribucion.
   * Impacto: Admin ERP Distribucion; permite abrir catalogo general o restringido sin tocar catalogo global.
   * Contrato: POST protegido por `distribucion.editar`.
   */
  public function cliente_catalogo_preferencias() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionClientesApi")->catalogoPreferenciasPlanInterno($_POST, $this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: listar reglas de catalogo personalizado por cliente Distribucion.
   * Impacto: Admin ERP Distribucion; muestra categorias/SKUs/marcas permitidos u ocultos por cliente.
   * Contrato: GET protegido por `distribucion.ver`.
   */
  public function cliente_catalogo_reglas() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClientesApi")->catalogoReglasInternas($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: guardar una regla de visibilidad de catalogo para un cliente Distribucion.
   * Impacto: Admin ERP Distribucion; permite habilitar u ocultar productos sin afectar otros clientes.
   * Contrato: POST protegido por `distribucion.editar`.
   */
  public function cliente_catalogo_regla_guardar() {
    $this->requerirPermiso("distribucion.editar");
    return json_encode($this->modelo("DistribucionClientesApi")->catalogoReglaGuardarInterna($_POST, $this->usuarioActualId()));
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
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: registrar revision interna de una partida de pedido/cotizacion Distribucion.
   * Impacto: Seguimiento Distribucion; confirma cantidades sin apartar inventario ni crear venta/pedido ERP.
   * Contrato: POST protegido por `distribucion.cotizaciones.gestionar`; requiere columnas de revision planificadas.
   */
  public function cotizacion_item_revision() {
    $this->requerirPermiso("distribucion.cotizaciones.gestionar");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->revisionPartidaInterna($_POST, $this->usuarioActualId()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-30
   * Proposito: guardar costo/opciones de entrega y responder pedido Distribucion al cliente.
   * Impacto: Admin ERP Distribucion; deja el pedido listo para aceptacion del cliente sin crear venta ni apartar inventario.
   * Contrato: POST protegido por `distribucion.cotizaciones.gestionar`.
   */
  public function cotizacion_entrega_guardar() {
    $this->requerirPermiso("distribucion.cotizaciones.gestionar");
    return json_encode($this->modelo("DistribucionCotizacionesApi")->configurarEntregaInterna($_POST, $this->usuarioActualId()));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar Mi catalogo de clientes Distribucion.
   * Impacto: Admin ERP Distribucion; permite revisar productos de interes sin entrar al portal externo.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_surtidos() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClienteSurtidoApi")->surtidosInternos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: alias interno para consultar Mi catalogo de clientes Distribucion.
   * Impacto: Admin ERP Distribucion; usa nomenclatura comercial vigente sin romper ruta anterior.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_mi_catalogo() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClienteSurtidoApi")->surtidosInternos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar inventario declarado por clientes Distribucion.
   * Impacto: Admin ERP Distribucion; muestra conteos, minimos y maximos para seguimiento comercial.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_inventarios() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClienteSurtidoApi")->inventariosInternos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: consultar sugeridos de resurtido por cliente Distribucion.
   * Impacto: Admin ERP Distribucion; permite anticipar necesidades antes de recibir pedido formal.
   * Contrato: GET protegido por `distribucion.ver`; read-only.
   */
  public function cliente_sugeridos() {
    $this->requerirPermiso("distribucion.ver");
    return json_encode($this->modelo("DistribucionClienteSurtidoApi")->sugeridosInternos($_GET));
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
