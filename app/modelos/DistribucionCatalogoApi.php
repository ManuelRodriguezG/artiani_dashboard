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
      "flujo_pedido" => array(
        "catalogo_no_muestra_existencia" => true,
        "ruta" => "/DistribucionApi/pedido/registrar",
        "estatus_inicial" => "pedido_solicitado",
        "revision_erp_requerida" => true
      ),
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: reservar listado de catalogo Distribucion sin reutilizar endpoints ecommerce como contrato B2B.
   * Impacto: Catalogo Distribucion; respeta visibilidad asignada desde ERP por cliente externo.
   * Contrato: GET read-only; requiere permiso `distribucion.catalogo.ver`.
   */
  public function catalogo($filtros = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver", "No tienes permiso para ver el catalogo Distribucion");
    if ($permiso) { return $permiso; }
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->catalogoCanal("distribucion", $filtros);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: reservar ficha comercial Distribucion por slug con salida segura.
   * Impacto: Catalogo Distribucion; evita exponer detalle sin permiso comercial externo.
   * Contrato: GET read-only; requiere `distribucion.catalogo.ver_detalle`.
   */
  public function producto($slug, $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver_detalle", "No tienes permiso para ver detalle de productos");
    if ($permiso) { return $permiso; }
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
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver", "No tienes permiso para ver categorias Distribucion");
    if ($permiso) { return $permiso; }
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
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver", "No tienes permiso para ver marcas Distribucion");
    if ($permiso) { return $permiso; }
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
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver", "No tienes permiso para ver filtros Distribucion");
    if ($permiso) { return $permiso; }
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: resolver contrato de precios sin aceptar montos enviados por frontend.
   * Impacto: Precios Distribucion; bloquea precios numericos si el ERP no asigno permiso.
   * Contrato: POST; requiere algun permiso de precio comercial externo.
   */
  public function resolverPrecios($datos = array(), $contexto = array()) {
    $permiso = $this->requiereAlgunPermiso($contexto, array("distribucion.precio.ver_publico", "distribucion.precio.ver_mayoreo", "distribucion.precio.ver_lista_asignada"), "No tienes permiso para ver precios Distribucion");
    if ($permiso) { return $permiso; }
    require_once RUTA_APP . "/modelos/PreciosCanalesErp.php";
    $respuesta = (new PreciosCanalesErp())->resolverPreciosCanal("distribucion", $this->valor($datos, "items", array()), $contexto);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: resolver contrato de disponibilidad sin exponer stock exacto.
   * Impacto: Inventario Distribucion; entrega disponibilidad solo con permiso externo.
   * Contrato: POST; requiere `distribucion.inventario.ver_disponibilidad`.
   */
  public function resolverDisponibilidad($datos = array(), $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.inventario.ver_disponibilidad", "No tienes permiso para ver disponibilidad Distribucion");
    if ($permiso) { return $permiso; }
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->disponibilidadCanal("distribucion", $this->valor($datos, "items", array()));
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: listar SKUs ERP candidatos para publicacion permanente en Distribucion.
   * Impacto: Admin ERP Distribucion; permite elegir productos reales sin consultar costos ni proveedores.
   * Contrato: read-only; solo SKUs activos con producto activo y precio vigente opcionalmente filtrable.
   */
  public function skusPublicablesInternos($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_catalogo_skus") || !$this->tablaExiste($db, "erp_catalogo_productos") || !$this->tablaExiste($db, "erp_catalogo_canales_vinculos")) {
        return $this->respuesta(false, "warning", "Catalogo ERP no disponible", array("configurado" => false, "items" => array()));
      }
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 30))));
      $q = trim((string) $this->valor($filtros, "q", ""));
      $soloConPrecio = intval($this->valor($filtros, "solo_con_precio", 1)) === 1;
      $where = array("p.estatus='activo'", "s.estatus='activo'");
      $params = array();
      if ($q !== "") {
        $where[] = "(p.nombre LIKE :q OR s.nombre LIKE :q OR s.sku LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($soloConPrecio && $this->tablaExiste($db, "erp_listas_precios") && $this->tablaExiste($db, "erp_listas_precios_detalle")) {
        $where[] = "EXISTS (
          SELECT 1
          FROM erp_listas_precios_detalle d
          INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
          WHERE d.id_sku=s.id_sku
            AND d.estatus='activo'
            AND d.precio>0
            AND l.estatus='activa'
            AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
            AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
            AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
            AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
        )";
      }
      $stmt = $db->prepare("SELECT p.id_producto_erp, p.nombre producto, s.id_sku, s.sku, s.nombre sku_nombre,
          cv.id_canal_vinculo, cv.id_externo, cv.estatus canal_estatus, cv.sincronizar_catalogo, cv.sincronizar_precio, cv.sincronizar_existencia
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_canales_vinculos cv ON cv.id_sku=s.id_sku AND cv.canal='distribucion'
        WHERE " . implode(" AND ", $where) . "
        ORDER BY CASE WHEN cv.id_canal_vinculo IS NULL THEN 1 ELSE 0 END, p.nombre ASC, s.sku ASC
        LIMIT " . intval($limite));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "SKUs publicables consultados", array("configurado" => true, "items" => $stmt->fetchAll(PDO::FETCH_ASSOC)));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron consultar SKUs publicables", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: publicar o reactivar un SKU ERP en el canal Distribucion.
   * Impacto: Catalogo Distribucion; habilita visibilidad permanente del SKU en API externa.
   * Contrato: escritura auditada; no cambia catalogo maestro, precios, costos ni inventario.
   */
  public function publicarSkuInterno($datos = array(), $idUsuario = null) {
    $idSku = intval($this->valor($datos, "id_sku", 0));
    if ($idSku <= 0) {
      return $this->respuesta(true, "warning", "SKU requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaPublicacionOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de publicacion no disponible", array("configurado" => false));
    }
    try {
      $sku = $this->skuPublicable($db, $idSku);
      if (!$sku) {
        return $this->respuesta(true, "warning", "SKU no publicable para Distribucion");
      }
      if (!$this->skuTienePrecioActivo($db, $idSku)) {
        return $this->respuesta(true, "warning", "SKU sin precio activo en listas ERP");
      }
      $slug = $this->idExternoUnico($db, $this->valor($datos, "id_externo", ""), $sku);
      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_catalogo_canales_vinculos
        (id_producto_erp, id_sku, canal, id_externo, sku_externo, sincronizar_catalogo, sincronizar_precio, sincronizar_existencia, estatus, fecha_registro, fecha_actualizacion)
        VALUES (:producto, :sku, 'distribucion', :externo, :sku_externo, 1, 1, 1, 'activo', NOW(), NOW())
        ON DUPLICATE KEY UPDATE id_producto_erp=VALUES(id_producto_erp), id_sku=VALUES(id_sku), sku_externo=VALUES(sku_externo),
          sincronizar_catalogo=1, sincronizar_precio=1, sincronizar_existencia=1, estatus='activo', fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":producto" => intval($sku["id_producto_erp"]),
        ":sku" => $idSku,
        ":externo" => $slug,
        ":sku_externo" => $sku["sku"]
      ));
      $stmtVinculo = $db->prepare("SELECT id_canal_vinculo FROM erp_catalogo_canales_vinculos WHERE canal='distribucion' AND id_externo=:externo LIMIT 1");
      $stmtVinculo->execute(array(":externo" => $slug));
      $idVinculo = intval($stmtVinculo->fetchColumn());
      $this->registrarAuditoria($db, "catalogo_canal", $idVinculo, "publicar_sku", "ok", "SKU publicado en Distribucion", array(
        "id_producto_erp" => intval($sku["id_producto_erp"]),
        "id_sku" => $idSku,
        "id_externo" => $slug
      ), $idUsuario);
      $db->commit();
      return $this->respuesta(false, "success", "SKU publicado en Distribucion", array(
        "ejecutado" => true,
        "id_canal_vinculo" => $idVinculo,
        "id_producto_erp" => intval($sku["id_producto_erp"]),
        "id_sku" => $idSku,
        "id_externo" => $slug
      ));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo publicar SKU en Distribucion", array("detalle" => "error_controlado"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: desactivar publicacion de SKU en Distribucion sin borrar el vinculo.
   * Impacto: Catalogo Distribucion; retira visibilidad externa conservando trazabilidad.
   * Contrato: escritura auditada; no toca producto/SKU maestro ni listas.
   */
  public function desactivarSkuInterno($datos = array(), $idUsuario = null) {
    $idVinculo = intval($this->valor($datos, "id_canal_vinculo", 0));
    $idSku = intval($this->valor($datos, "id_sku", 0));
    if ($idVinculo <= 0 && $idSku <= 0) {
      return $this->respuesta(true, "warning", "Vinculo o SKU requerido");
    }
    $db = $this->getConexion();
    if (!$this->esquemaPublicacionOperativo($db)) {
      return $this->respuesta(true, "warning", "Esquema de publicacion no disponible", array("configurado" => false));
    }
    try {
      $where = $idVinculo > 0 ? "id_canal_vinculo=:id" : "id_sku=:sku AND canal='distribucion'";
      $params = $idVinculo > 0 ? array(":id" => $idVinculo) : array(":sku" => $idSku);
      $db->beginTransaction();
      $db->prepare("UPDATE erp_catalogo_canales_vinculos SET estatus='inactivo', fecha_actualizacion=NOW() WHERE " . $where)->execute($params);
      $this->registrarAuditoria($db, "catalogo_canal", $idVinculo ?: $idSku, "desactivar_sku", "ok", "SKU desactivado en Distribucion", array(
        "id_canal_vinculo" => $idVinculo ?: null,
        "id_sku" => $idSku ?: null
      ), $idUsuario);
      $db->commit();
      return $this->respuesta(false, "success", "SKU desactivado en Distribucion", array("ejecutado" => true, "id_canal_vinculo" => $idVinculo ?: null, "id_sku" => $idSku ?: null));
    } catch (Exception $e) {
      if ($db && $db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", "No se pudo desactivar SKU en Distribucion", array("detalle" => "error_controlado"));
    }
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
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/registrar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/pedido/registrar")
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
      "catalogo_no_muestra_existencia" => true,
      "pedido_requiere_revision_surtido" => true,
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

  private function requierePermiso($contexto, $permiso, $mensaje) {
    if ($this->tienePermisoContexto($contexto, $permiso)) {
      return null;
    }
    return $this->conSesionYGuardrails($this->respuesta(true, "warning", $mensaje, array(
      "requiere_autenticacion" => empty($contexto["autenticado"]),
      "requiere_permiso" => $permiso
    )), $contexto);
  }

  private function requiereAlgunPermiso($contexto, $permisos, $mensaje) {
    foreach ($permisos as $permiso) {
      if ($this->tienePermisoContexto($contexto, $permiso)) {
        return null;
      }
    }
    return $this->conSesionYGuardrails($this->respuesta(true, "warning", $mensaje, array(
      "requiere_autenticacion" => empty($contexto["autenticado"]),
      "requiere_permiso" => $permisos
    )), $contexto);
  }

  private function tienePermisoContexto($contexto, $permiso) {
    $permisos = $this->valor($contexto, "permisos", array());
    return is_array($permisos) && in_array($permiso, $permisos, true);
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function limpiarSlug($slug) {
    return preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) $slug));
  }

  private function esquemaPublicacionOperativo($db) {
    return $db
      && $this->tablaExiste($db, "erp_catalogo_skus")
      && $this->tablaExiste($db, "erp_catalogo_productos")
      && $this->tablaExiste($db, "erp_catalogo_canales_vinculos")
      && $this->tablaExiste($db, "erp_distribucion_auditoria");
  }

  private function tablaExiste($db, $tabla) {
    try {
      $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function skuPublicable($db, $idSku) {
    $stmt = $db->prepare("SELECT p.id_producto_erp, p.nombre producto, s.id_sku, s.sku, COALESCE(NULLIF(s.nombre,''), p.nombre) nombre_sku
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      WHERE s.id_sku=:sku AND s.estatus='activo' AND p.estatus='activo'
      LIMIT 1");
    $stmt->execute(array(":sku" => intval($idSku)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function skuTienePrecioActivo($db, $idSku) {
    if (!$this->tablaExiste($db, "erp_listas_precios") || !$this->tablaExiste($db, "erp_listas_precios_detalle")) {
      return false;
    }
    $stmt = $db->prepare("SELECT 1
      FROM erp_listas_precios_detalle d
      INNER JOIN erp_listas_precios l ON l.id_lista_precio=d.id_lista_precio
      WHERE d.id_sku=:sku
        AND d.estatus='activo'
        AND d.precio>0
        AND l.estatus='activa'
        AND (d.fecha_inicio IS NULL OR d.fecha_inicio<=NOW())
        AND (d.fecha_fin IS NULL OR d.fecha_fin>=NOW())
        AND (l.fecha_inicio IS NULL OR l.fecha_inicio<=NOW())
        AND (l.fecha_fin IS NULL OR l.fecha_fin>=NOW())
      LIMIT 1");
    $stmt->execute(array(":sku" => intval($idSku)));
    return (bool) $stmt->fetchColumn();
  }

  private function idExternoUnico($db, $idExterno, $sku) {
    $base = $this->limpiarSlug($idExterno);
    if ($base === "") {
      $base = $this->slugificar($this->valor($sku, "nombre_sku", "") . "-" . $this->valor($sku, "sku", ""));
    }
    if ($base === "") {
      $base = "sku-" . intval($sku["id_sku"]);
    }
    $slug = $base;
    $i = 2;
    while ($this->slugExisteOtroSku($db, $slug, intval($sku["id_sku"]))) {
      $slug = $base . "-" . $i;
      $i++;
    }
    return $slug;
  }

  private function slugExisteOtroSku($db, $slug, $idSku) {
    $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_canales_vinculos WHERE canal='distribucion' AND id_externo=:slug AND id_sku<>:sku LIMIT 1");
    $stmt->execute(array(":slug" => $slug, ":sku" => intval($idSku)));
    return (bool) $stmt->fetchColumn();
  }

  private function slugificar($texto) {
    $texto = strtolower(trim((string) $texto));
    $texto = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $texto);
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');
    return substr($texto, 0, 150);
  }

  private function registrarAuditoria($db, $entidad, $idEntidad, $accion, $resultado, $mensaje, $detalle, $idUsuario) {
    $stmt = $db->prepare("INSERT INTO erp_distribucion_auditoria
      (entidad, id_entidad, accion, resultado, mensaje, detalle_json, id_usuario_erp, fecha_registro)
      VALUES (:entidad, :id_entidad, :accion, :resultado, :mensaje, :detalle, :usuario, NOW())");
    $stmt->execute(array(
      ":entidad" => $entidad,
      ":id_entidad" => $idEntidad,
      ":accion" => $accion,
      ":resultado" => $resultado,
      ":mensaje" => $mensaje,
      ":detalle" => json_encode($detalle),
      ":usuario" => $idUsuario
    ));
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
