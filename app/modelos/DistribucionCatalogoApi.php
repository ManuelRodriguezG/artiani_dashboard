<?php

class DistribucionCatalogoApi extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar manifiesto versionado del contrato API Distribucion.
   * Impacto: Frontend Distribucion; define endpoints, reglas y guardrails sin duplicar logica ERP.
   * Contrato: read-only; no consulta datos sensibles ni escribe BD.
   */
  public function contratos() {
    require_once RUTA_APP . "/modelos/DistribucionPermisosApi.php";
    $permisos = new DistribucionPermisosApi();
    return $this->respuesta(false, "success", "Contratos API Distribucion", array(
      "api" => $this->apiMeta(),
      "base_path" => "/DistribucionApi",
      "origenes_cors" => array("http://distribucion.artiani.com.local", "https://distribucion.artiani.com.mx"),
      "tipos_cliente" => array("publico", "registrado", "revendedor", "mayorista", "distribuidor_autorizado", "administrador_interno"),
      "estatus_cliente" => array("pendiente", "en_revision", "aprobado", "rechazado", "suspendido"),
      "permisos_comerciales" => $permisos->permisosComerciales(),
      "disponibilidad_estados" => $this->estadosDisponibilidad(),
      "reglas_precio" => array(
        "orden" => array("lista_asignada", "publico_autorizado", "mayoreo_erp", "solicitar_precio"),
        "sin_permiso" => array("visible" => false, "mensaje" => "Solicitar precio"),
        "no_exponer" => array("costos", "margenes", "proveedores", "costo_promedio", "utilidad")
      ),
      "endpoints" => $this->endpointsContrato(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar bootstrap minimo para el frontend Distribucion.
   * Impacto: Frontend Distribucion; permite iniciar UI sin hardcodear version, canal o labels.
   * Contrato: read-only; perfil publico hasta integrar tokens externos.
   */
  public function configuracionInicial($contexto = array()) {
    return $this->respuesta(false, "success", "Configuracion inicial Distribucion lista", array(
      "api" => $this->apiMeta(),
      "sesion" => array(
        "autenticado" => !empty($contexto["autenticado"]),
        "tipo_cliente" => $this->valor($contexto, "tipo_cliente", "publico"),
        "estatus" => $this->valor($contexto, "estatus", null),
        "permisos" => $this->valor($contexto, "permisos", array()),
        "acciones" => $this->valor($contexto, "acciones", array())
      ),
      "ui" => array(
        "precio_oculto_label" => "Solicitar precio",
        "disponibilidad_default" => "consultar_disponibilidad",
        "moneda_default" => "MXN"
      ),
      "contratos" => array(
        "manifest" => "/DistribucionApi/contratos",
        "catalogo" => "/DistribucionApi/catalogo",
        "producto" => "/DistribucionApi/producto/{slug}",
        "cotizacion_dryrun" => "/DistribucionApi/cotizacion/dryrun",
        "cotizacion_registrar" => "/DistribucionApi/cotizacion/registrar"
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar listado de catalogo Distribucion sin reutilizar endpoints ecommerce como contrato B2B.
   * Impacto: Catalogo Distribucion; mantiene contrato consumible mientras se conectan servicios internos ERP.
   * Contrato: GET read-only; devuelve lista vacia segura si canal/listas no estan configurados.
   */
  public function catalogo($filtros = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->catalogoCanal("distribucion", $filtros);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar ficha comercial Distribucion por slug con salida segura.
   * Impacto: Catalogo Distribucion; evita exponer campos internos mientras se autoriza visibilidad.
   * Contrato: GET read-only; sin costos, proveedores, margenes ni stock exacto.
   */
  public function producto($slug, $contexto = array()) {
    $slug = $this->limpiarSlug($slug);
    if ($slug === "") {
      return $this->respuesta(true, "warning", "Slug de producto requerido", array("item" => null));
    }
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->productoCanal("distribucion", $slug);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar categorias visibles para Distribucion.
   * Impacto: Navegacion Distribucion; mantiene contrato separado de tablas internas.
   * Contrato: GET read-only.
   */
  public function categorias($filtros = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->categoriasCanal("distribucion");
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar marcas visibles para Distribucion.
   * Impacto: Navegacion Distribucion; mantiene contrato separado de tablas internas.
   * Contrato: GET read-only.
   */
  public function marcas($filtros = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->marcasCanal("distribucion");
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: reservar filtros comerciales para Distribucion.
   * Impacto: Frontend Distribucion; entrega estructura esperada sin hardcodear reglas.
   * Contrato: GET read-only.
   */
  public function filtros($filtros = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $servicio = new CatalogoCanalesErp();
    $categorias = $servicio->categoriasCanal("distribucion");
    $marcas = $servicio->marcasCanal("distribucion");
    return $this->respuesta(false, "success", "Filtros Distribucion consultados", array(
      "configurado" => $this->valor($this->valor($categorias, "depurar", array()), "configurado", false) && $this->valor($this->valor($marcas, "depurar", array()), "configurado", false),
      "categorias" => $this->valor($this->valor($categorias, "depurar", array()), "items", array()),
      "marcas" => $this->valor($this->valor($marcas, "depurar", array()), "items", array()),
      "atributos" => array(),
      "disponibilidad" => $this->estadosDisponibilidad(),
      "ordenamientos" => array("relevancia", "nombre", "marca"),
      "sesion" => $this->sesionSalida($contexto),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver contrato de precios sin aceptar montos enviados por frontend.
   * Impacto: Precios Distribucion; bloquea precios numericos hasta integrar listas autorizadas ERP.
   * Contrato: POST; devuelve precio oculto por linea si no existe permiso/lista verificada.
   */
  public function resolverPrecios($datos = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/PreciosCanalesErp.php";
    $respuesta = (new PreciosCanalesErp())->resolverPreciosCanal("distribucion", $this->valor($datos, "items", array()), $contexto);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: resolver contrato de disponibilidad sin exponer stock exacto.
   * Impacto: Inventario Distribucion; bloquea cantidades y devuelve estado comercial seguro.
   * Contrato: POST; no aparta, no crea movimiento y no modifica inventario.
   */
  public function resolverDisponibilidad($datos = array(), $contexto = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->disponibilidadCanal("distribucion", $this->valor($datos, "items", array()));
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: responder error controlado para rutas inexistentes del contrato Distribucion.
   * Impacto: API Distribucion; evita respuestas HTML o errores internos para frontend.
   * Contrato: devuelve JSON con endpoint solicitado.
   */
  public function endpointNoEncontrado($endpoint) {
    return $this->respuesta(true, "warning", "Endpoint Distribucion no encontrado", array("endpoint" => $endpoint));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: responder error controlado cuando un endpoint requiere POST.
   * Impacto: API Distribucion; mantiene contrato JSON estable.
   * Contrato: devuelve JSON con endpoint solicitado.
   */
  public function metodoPostRequerido($endpoint) {
    return $this->respuesta(true, "warning", "Este endpoint requiere POST", array("endpoint" => $endpoint));
  }

  private function apiMeta() {
    return array(
      "nombre" => "ERP Distribucion API",
      "version" => "fase1-2026-09-08",
      "canal" => "distribucion",
      "fuente_verdad" => "ERP"
    );
  }

  private function endpointsContrato() {
    return array(
      array("metodo" => "GET", "ruta" => "/DistribucionApi/contratos"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/configuracion_inicial"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/auth/registro"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/auth/login"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/catalogo"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/producto/{slug}"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/categorias"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/marcas"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/filtros"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/precios/resolver"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/disponibilidad/resolver"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/dryrun"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/registrar")
    );
  }

  private function guardrails() {
    return array(
      "erp_es_fuente_de_verdad" => true,
      "distribucion_es_consumidor" => true,
      "no_consultar_tablas_erp_desde_frontend" => true,
      "no_costos" => true,
      "no_margenes" => true,
      "no_proveedores" => true,
      "no_stock_exacto_mvp" => true,
      "no_crea_venta" => true,
      "no_crea_pedido_automatico" => true,
      "no_aparta_inventario" => true
    );
  }

  private function estadosDisponibilidad() {
    return array("disponible", "pocas_piezas", "bajo_pedido", "consultar_disponibilidad", "no_disponible");
  }

  private function sesionSalida($contexto) {
    return array(
      "autenticado" => !empty($contexto["autenticado"]),
      "tipo_cliente" => $this->valor($contexto, "tipo_cliente", "publico"),
      "estatus" => $this->valor($contexto, "estatus", null),
      "permisos" => $this->valor($contexto, "permisos", array()),
      "acciones" => $this->valor($contexto, "acciones", array())
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }

  private function conSesionYGuardrails($respuesta, $contexto) {
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      $respuesta["depurar"] = array();
    }
    $respuesta["depurar"]["sesion"] = $this->sesionSalida($contexto);
    $respuesta["depurar"]["guardrails"] = $this->guardrails();
    return $respuesta;
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function limpiarSlug($slug) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) $slug));
  }

  private function itemsNormalizados($items) {
    $items = is_array($items) ? array_slice($items, 0, 50) : array();
    $salida = array();
    foreach ($items as $item) {
      if (!is_array($item)) { continue; }
      $idSku = intval($this->valor($item, "id_sku", 0));
      if ($idSku <= 0) { continue; }
      $salida[] = array(
        "id_sku" => $idSku,
        "cantidad" => max(0.001, min(9999, floatval($this->valor($item, "cantidad", 1))))
      );
    }
    return $salida;
  }
}
