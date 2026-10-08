<?php

class DistribucionApi extends Controlador {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: exponer manifiesto versionado de contratos para el frontend externo de Distribucion.
   * Impacto: API Distribucion; evita que el proyecto consumidor consulte tablas internas del ERP.
   * Contrato: GET/OPTIONS publico; no escribe BD ni expone costos, margenes, proveedores o stock exacto.
   */
  public function contratos() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->contratos());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar configuracion inicial minima para arrancar el frontend Distribucion.
   * Impacto: Frontend Distribucion; centraliza version, canal, sesion publica y labels UI.
   * Contrato: GET/OPTIONS publico; no consulta datos sensibles ni requiere sesion ERP interna.
   */
  public function configuracion_inicial() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->configuracionInicial($this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: enrutar contratos de autenticacion externa sin usar la sesion interna del ERP.
   * Impacto: Clientes Distribucion; registro, login y activacion de contrasenia por token.
   * Contrato: POST /auth/registro, /auth/login, /auth/activar_consultar, /auth/activar_contrasenia y GET/POST /auth/perfil.
   */
  public function auth($accion = "", $subaccion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    if ($accion === "perfil") {
      $contexto = $this->contextoCliente();
      if ($subaccion === "solicitar_cambio") {
        if (!$this->esPostDistribucion()) {
          return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("auth/perfil/solicitar_cambio"));
        }
        return $this->responderApiDistribucion($this->modelo("DistribucionClientesApi")->solicitarCambioPerfil($this->entradaJsonDistribucion(), $contexto));
      }
      if (empty($contexto["autenticado"])) {
        return $this->responderApiDistribucion(array(
          "error" => true,
          "tipo" => "warning",
          "mensaje" => "Sesion Distribucion requerida",
          "depurar" => array(
            "autenticado" => false,
            "token_presente" => !empty($contexto["token_presente"]),
            "permisos" => array(),
            "acciones" => isset($contexto["acciones"]) ? $contexto["acciones"] : array()
          )
        ));
      }
      $perfil = $this->modelo("DistribucionClientesApi")->perfilPorId(intval($contexto["id_cliente_distribucion"]));
      if (!is_array($perfil)) { $perfil = $contexto; }
      return $this->responderApiDistribucion(array(
        "error" => false,
        "tipo" => "success",
        "mensaje" => "Perfil Distribucion consultado",
        "perfil" => $perfil,
        "permisos" => isset($perfil["permisos"]) ? $perfil["permisos"] : (isset($contexto["permisos"]) ? $contexto["permisos"] : array()),
        "acciones" => isset($perfil["acciones"]) ? $perfil["acciones"] : (isset($contexto["acciones"]) ? $contexto["acciones"] : array()),
        "depurar" => array(
          "perfil" => $perfil,
          "permisos" => isset($perfil["permisos"]) ? $perfil["permisos"] : (isset($contexto["permisos"]) ? $contexto["permisos"] : array()),
          "acciones" => isset($perfil["acciones"]) ? $perfil["acciones"] : (isset($contexto["acciones"]) ? $contexto["acciones"] : array())
        )
      ));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("auth/" . $accion));
    }

    $clientes = $this->modelo("DistribucionClientesApi");
    $datos = $this->entradaJsonDistribucion();
    if ($accion === "registro") {
      return $this->responderApiDistribucion($clientes->registrarSolicitud($datos, $this->contextoCliente()));
    }
    if ($accion === "login") {
      return $this->responderApiDistribucion($clientes->login($datos, $this->contextoCliente()));
    }
    if ($accion === "activar_consultar") {
      return $this->responderApiDistribucion($clientes->activacionConsultar($datos));
    }
    if ($accion === "activar_contrasenia") {
      return $this->responderApiDistribucion($clientes->activarContrasenia($datos, $this->contextoCliente()));
    }
    if ($accion === "recuperar") {
      return $this->responderApiDistribucion($clientes->recuperarAcceso($datos, $this->contextoCliente()));
    }
    if ($accion === "reenviar_activacion") {
      return $this->responderApiDistribucion($clientes->reenviarActivacion($datos, $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("auth/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar catalogo comercial visible para Distribucion con datos sanitizados.
   * Impacto: Catalogo Distribucion; separa contrato B2B de ecommerce publico.
   * Contrato: GET/OPTIONS; precio y disponibilidad se resuelven segun contexto, sin stock exacto.
   */
  public function catalogo() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->catalogo($_GET, $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar ficha comercial de producto/SKU para Distribucion.
   * Impacto: Catalogo Distribucion; mantiene fuera campos internos de ERP.
   * Contrato: GET/OPTIONS por slug; no expone costos, proveedores, margenes ni auditoria interna.
   */
  public function producto($slug = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->producto($slug, $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar categorias visibles para canal Distribucion.
   * Impacto: Navegacion Distribucion; evita hardcodear taxonomia en el frontend.
   * Contrato: GET/OPTIONS read-only.
   */
  public function categorias() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->categorias($_GET, $this->contextoCliente()));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-04
   * Proposito: entregar arbol publico de categorias de interes para registro Distribucion.
   * Impacto: Registro Distribucion; permite elegir categorias padre/hijas sin iniciar sesion.
   * Contrato: GET/OPTIONS read-only; no expone costos, margenes, proveedores ni stock.
   */
  public function categorias_interes() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->categoriasInteres($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar marcas visibles para canal Distribucion.
   * Impacto: Navegacion Distribucion; permite filtros sin consultar ERP directo.
   * Contrato: GET/OPTIONS read-only.
   */
  public function marcas() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->marcas($_GET, $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar filtros disponibles para el catalogo Distribucion.
   * Impacto: Frontend Distribucion; soporta UI de busqueda/facetas con contrato estable.
   * Contrato: GET/OPTIONS read-only.
   */
  public function filtros() {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->filtros($_GET, $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver precios comerciales por servidor para una lista de SKUs.
   * Impacto: Precios Distribucion; impide confiar en montos enviados por frontend.
   * Contrato: POST /precios/resolver; no devuelve costos ni margenes.
   */
  public function precios($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    if ($accion !== "resolver") {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("precios/" . $accion));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("precios/resolver"));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->resolverPrecios($this->entradaJsonDistribucion(), $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver disponibilidad confirmada solo cuando el ERP autorice mostrarla.
   * Impacto: Inventario Distribucion; no forma parte del catalogo publico ni de la solicitud inicial.
   * Contrato: POST /disponibilidad/resolver; no aparta ni modifica inventario.
   */
  public function disponibilidad($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    if ($accion !== "resolver") {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("disponibilidad/" . $accion));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("disponibilidad/resolver"));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->resolverDisponibilidad($this->entradaJsonDistribucion(), $this->contextoCliente()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: enrutar historial, borradores y conversion de cotizaciones Distribucion.
   * Impacto: Portal Distribucion; separa cotizaciones de pedidos sin crear ventas ni apartados de inventario.
   * Contrato: GET listar/detalle y POST dryrun/registrar/guardar_borrador/cancelar/duplicar/enviar_pedido.
   */
  public function cotizacion($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $cotizaciones = $this->modelo("DistribucionCotizacionesApi");
    if ($accion === "" || $accion === "listar") {
      return $this->responderApiDistribucion($cotizaciones->cotizacionesCliente($_GET, $this->contextoCliente()));
    }
    if ($accion === "detalle") {
      return $this->responderApiDistribucion($cotizaciones->cotizacionDetalleCliente($_GET, $this->contextoCliente()));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("cotizacion/" . $accion));
    }
    $datos = $this->entradaJsonDistribucion();
    if ($accion === "dryrun") {
      return $this->responderApiDistribucion($cotizaciones->dryRun($datos, $this->contextoCliente()));
    }
    if ($accion === "registrar") {
      return $this->responderApiDistribucion($cotizaciones->registrar($datos, $this->contextoCliente()));
    }
    if ($accion === "guardar_borrador") {
      return $this->responderApiDistribucion($cotizaciones->guardarBorradorCliente($datos, $this->contextoCliente()));
    }
    if ($accion === "cancelar") {
      return $this->responderApiDistribucion($cotizaciones->cancelarCotizacionCliente($datos, $this->contextoCliente()));
    }
    if ($accion === "duplicar") {
      return $this->responderApiDistribucion($cotizaciones->duplicarCotizacionCliente($datos, $this->contextoCliente()));
    }
    if ($accion === "enviar_pedido") {
      return $this->responderApiDistribucion($cotizaciones->enviarCotizacionComoPedido($datos, $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("cotizacion/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: registrar solicitud de pedido Distribucion sin mostrar existencia ni apartar inventario.
   * Impacto: Distribucion; el ERP recibe partidas solicitadas para revision interna de surtido.
   * Contrato: GET listar/detalle y POST registrar/responder autenticados con `distribucion.pedido.preliminar`.
   */
  public function pedido($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $cotizaciones = $this->modelo("DistribucionCotizacionesApi");
    if ($accion === "" || $accion === "listar") {
      return $this->responderApiDistribucion($cotizaciones->pedidosCliente($_GET, $this->contextoCliente()));
    }
    if ($accion === "detalle") {
      return $this->responderApiDistribucion($cotizaciones->pedidoDetalleCliente($_GET, $this->contextoCliente()));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("pedido/" . $accion));
    }
    $datos = $this->entradaJsonDistribucion();
    if ($accion === "registrar") {
      return $this->responderApiDistribucion($cotizaciones->registrarPedidoPreliminar($datos, $this->contextoCliente()));
    }
    if ($accion === "responder") {
      return $this->responderApiDistribucion($cotizaciones->responderPedidoCliente($datos, $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("pedido/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: enrutar surtido habitual del cliente externo Distribucion.
   * Impacto: Distribucion; permite guardar productos de interes sin crear pedido ni tocar inventario ERP.
   * Contrato: GET /surtido/listar y POST /surtido/guardar autenticados con permisos externos.
   */
  public function surtido($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $surtido = $this->modelo("DistribucionClienteSurtidoApi");
    if ($accion === "" || $accion === "listar") {
      return $this->responderApiDistribucion($surtido->surtidoListar($_GET, $this->contextoCliente()));
    }
    if ($accion === "guardar") {
      if (!$this->esPostDistribucion()) {
        return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("surtido/guardar"));
      }
      return $this->responderApiDistribucion($surtido->surtidoGuardar($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("surtido/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: enrutar Mi catalogo del cliente externo Distribucion.
   * Impacto: Distribucion; permite guardar productos de interes como subcatalogo personal sin crear pedido.
   * Contrato: GET /mi_catalogo/listar y POST /mi_catalogo/guardar autenticados con permisos externos.
   */
  public function mi_catalogo($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $catalogoCliente = $this->modelo("DistribucionClienteSurtidoApi");
    if ($accion === "" || $accion === "listar") {
      return $this->responderApiDistribucion($catalogoCliente->miCatalogoListar($_GET, $this->contextoCliente()));
    }
    if ($accion === "guardar") {
      if (!$this->esPostDistribucion()) {
        return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("mi_catalogo/guardar"));
      }
      return $this->responderApiDistribucion($catalogoCliente->miCatalogoGuardar($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("mi_catalogo/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-29
   * Proposito: enrutar inventario declarado y sugerido de resurtido del cliente Distribucion.
   * Impacto: Distribucion; soporta conteo/minimos/maximos sin exponer existencia ERP en catalogo.
   * Contrato: GET listar/sugerido, POST guardar_conteo/pedido_sugerido con permisos externos.
   */
  public function inventario_cliente($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $surtido = $this->modelo("DistribucionClienteSurtidoApi");
    if ($accion === "" || $accion === "listar") {
      return $this->responderApiDistribucion($surtido->inventarioListar($_GET, $this->contextoCliente()));
    }
    if ($accion === "sugerido") {
      return $this->responderApiDistribucion($surtido->sugeridoResurtido($_GET, $this->contextoCliente()));
    }
    if ($accion === "guardar_conteo") {
      if (!$this->esPostDistribucion()) {
        return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("inventario_cliente/guardar_conteo"));
      }
      return $this->responderApiDistribucion($surtido->inventarioGuardarConteo($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    if ($accion === "pedido_sugerido") {
      if (!$this->esPostDistribucion()) {
        return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("inventario_cliente/pedido_sugerido"));
      }
      return $this->responderApiDistribucion($surtido->pedidoDesdeSugerido($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("inventario_cliente/" . $accion));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-10-07
   * Proposito: enrutar notificaciones comerciales del portal externo Distribucion.
   * Impacto: Portal Distribucion; listado, contador, lectura y solicitud de reenvio viven en ERP.
   * Contrato: GET resumen/listar y POST marcar_leida/enviar autenticados por token externo.
   */
  public function notificacion($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    $notificaciones = $this->notificacionesDistribucion();
    if ($accion === "" || $accion === "resumen") {
      return $this->responderApiDistribucion($notificaciones->resumen($this->contextoCliente()));
    }
    if ($accion === "listar") {
      return $this->responderApiDistribucion($notificaciones->listar($_GET, $this->contextoCliente()));
    }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("notificacion/" . $accion));
    }
    if ($accion === "marcar_leida") {
      return $this->responderApiDistribucion($notificaciones->marcarLeida($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    if ($accion === "enviar") {
      return $this->responderApiDistribucion($notificaciones->enviar($this->entradaJsonDistribucion(), $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("notificacion/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: estandarizar headers de la API Distribucion y CORS restringido.
   * Impacto: Seguridad API; solo permite origenes autorizados sin abrir credenciales a cualquier dominio.
   * Contrato: codifica JSON y agrega version/canal; no autentica por si misma.
   */
  private function responderApiDistribucion($respuesta) {
    $origen = isset($_SERVER["HTTP_ORIGIN"]) ? trim((string) $_SERVER["HTTP_ORIGIN"]) : "";
    if (!headers_sent()) {
      header("Content-Type: application/json; charset=utf-8");
      header("X-ERP-Distribucion-API-Version: fase1-2026-09-08");
      header("X-ERP-Distribucion-Canal: distribucion");
      header("Vary: Origin");
      if ($origen !== "" && $this->origenCorsPermitido($origen)) {
        header("Access-Control-Allow-Origin: " . $origen);
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Canal-Comercial");
        header("Access-Control-Max-Age: 600");
      }
    }
    if ($this->esOptionsDistribucion()) {
      return "";
    }
    return json_encode($respuesta);
  }

  private function responderOpcionesDistribucion() {
    return $this->responderApiDistribucion(array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Preflight API Distribucion",
      "api" => array(
        "nombre" => "ERP Distribucion API",
        "version" => "fase1-2026-09-08",
        "canal" => "distribucion",
        "fuente_verdad" => "ERP"
      ),
      "depurar" => array("options" => true)
    ));
  }

  private function esOptionsDistribucion() {
    return isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string) $_SERVER["REQUEST_METHOD"]) === "OPTIONS";
  }

  private function esPostDistribucion() {
    return isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string) $_SERVER["REQUEST_METHOD"]) === "POST";
  }

  private function entradaJsonDistribucion() {
    $raw = file_get_contents("php://input");
    $json = json_decode((string) $raw, true);
    return is_array($json) ? $json : array();
  }

  private function origenCorsPermitido($origen) {
    return in_array($origen, array(
      "http://distribucion.artiani.com.local",
      "https://distribucion.artiani.com.mx"
    ), true);
  }

  private function contextoCliente() {
    return $this->modelo("DistribucionPermisosApi")->contextoDesdeRequest();
  }

  private function notificacionesDistribucion() {
    require_once RUTA_APP . "/modelos/distribucionnotificacionesapi.php";
    return new DistribucionNotificacionesApi();
  }
}
