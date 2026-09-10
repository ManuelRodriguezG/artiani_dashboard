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
   * Impacto: Clientes Distribucion; reserva registro/login seguros para clientes externos.
   * Contrato: POST /auth/registro y POST /auth/login; OPTIONS responde preflight.
   */
  public function auth($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
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
   * Proposito: enrutar dry-run y registro de cotizaciones Distribucion.
   * Impacto: Cotizaciones Distribucion; valida sin crear pedidos, ventas ni apartados de inventario.
   * Contrato: POST /cotizacion/dryrun o /cotizacion/registrar; recalculo futuro siempre en ERP.
   */
  public function cotizacion($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("cotizacion/" . $accion));
    }

    $cotizaciones = $this->modelo("DistribucionCotizacionesApi");
    $datos = $this->entradaJsonDistribucion();
    if ($accion === "dryrun") {
      return $this->responderApiDistribucion($cotizaciones->dryRun($datos, $this->contextoCliente()));
    }
    if ($accion === "registrar") {
      return $this->responderApiDistribucion($cotizaciones->registrar($datos, $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("cotizacion/" . $accion));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: registrar solicitud de pedido Distribucion sin mostrar existencia ni apartar inventario.
   * Impacto: Distribucion; el ERP recibe partidas solicitadas para revision interna de surtido.
   * Contrato: POST /pedido/registrar autenticado con `distribucion.pedido.preliminar`.
   */
  public function pedido($accion = "") {
    if ($this->esOptionsDistribucion()) { return $this->responderOpcionesDistribucion(); }
    if (!$this->esPostDistribucion()) {
      return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->metodoPostRequerido("pedido/" . $accion));
    }
    $cotizaciones = $this->modelo("DistribucionCotizacionesApi");
    $datos = $this->entradaJsonDistribucion();
    if ($accion === "registrar") {
      return $this->responderApiDistribucion($cotizaciones->registrarPedidoPreliminar($datos, $this->contextoCliente()));
    }
    return $this->responderApiDistribucion($this->modelo("DistribucionCatalogoApi")->endpointNoEncontrado("pedido/" . $accion));
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
}
