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
      "acciones_perfil" => $permisos->accionesPermitidas($permisos->permisosComerciales()),
      "codigos_login" => array("credenciales_invalidas", "cuenta_pendiente", "cuenta_en_revision", "cuenta_rechazada", "cuenta_suspendida", "requiere_activacion", "contrasenia_no_creada", "sin_permisos_comerciales"),
      "disponibilidad_estados" => $this->estadosDisponibilidad(),
      "registro" => array(
        "campos_requeridos" => array("nombre", "nombre_negocio", "correo", "telefono", "ciudad", "estado", "tipo_negocio"),
        "codigos" => array("solicitud_recibida", "solicitud_incompleta", "campos_fiscales_requeridos", "solicitud_existente", "cliente_existente", "solicitud_rechazada", "cuenta_suspendida"),
        "duplicados" => array(
          "correo_es_clave_operativa" => true,
          "no_crea_folio_nuevo_si_existe_solicitud" => true,
          "no_actualiza_solicitud_existente_sin_regla_explicita" => true
        ),
        "tipos_negocio" => array("venta_internet", "veterinaria", "petshop", "acuario", "acuario_petshop", "estetica_canina", "criador", "vendedor_mercado", "vendedor_ambulante", "otro"),
        "facturacion_requerida_si_requiere_factura" => array("facturacion.rfc", "facturacion.razon_social", "facturacion.regimen_fiscal", "facturacion.uso_cfdi", "facturacion.codigo_postal_fiscal", "facturacion.correo_facturacion"),
        "categorias_interes" => array(
          "seleccion_multiple" => true,
          "seleccion_padre_incluye_descendientes" => true,
          "campos" => array("id_categoria_erp", "nombre", "ruta", "incluye_descendientes")
        )
      ),
      "flujo_pedido" => array(
        "catalogo_no_muestra_existencia" => true,
        "ruta" => "/DistribucionApi/pedido/registrar",
        "estatus_inicial" => "pedido_solicitado",
        "revision_erp_requerida" => true,
        "cliente_acepta_respuesta_erp" => true,
        "cotizaciones_y_pedidos_separados" => true,
        "pedido_adicional_crea_documento_nuevo" => true,
        "metodos_entrega" => array("por_definir", "envio", "recoger_tienda"),
        "facturacion" => array(
          "captura_frontend" => true,
          "no_genera_factura_automaticamente" => true,
          "campos" => array("requiere_factura", "facturacion.rfc", "facturacion.razon_social", "facturacion.regimen_fiscal", "facturacion.uso_cfdi", "facturacion.codigo_postal_fiscal", "facturacion.correo_facturacion", "facturacion.comentarios_facturacion")
        )
      ),
      "catalogo_personalizado" => array(
        "captura_intereses_registro" => true,
        "categorias_interes_endpoint" => "/DistribucionApi/categorias_interes",
        "seleccion_categoria_padre_incluye_descendientes" => true,
        "modos_cliente" => array("general", "personalizado"),
        "reglas_admin" => array("permitir", "ocultar"),
        "alcances" => array("categoria", "marca", "sku"),
        "ocultar_sku_bloquea_precio_cotizacion_pedido" => true
      ),
      "mi_cuenta" => array(
        "perfil_estructurado" => true,
        "cambios_contacto_aplicacion_directa" => array("telefono", "whatsapp", "correo_alterno", "contacto_principal"),
        "cambios_sensibles_requieren_revision" => array("empresa", "nombre_negocio", "tipo_negocio", "direccion", "rfc", "datos_fiscales"),
        "frontend_no_puede_cambiar" => array("id_lista_precio", "permisos", "tipo_cliente", "estatus")
      ),
      "reglas_precio" => array(
        "orden" => array("lista_asignada", "publico_autorizado", "mayoreo_erp", "solicitar_precio"),
        "sin_permiso" => array("visible" => false, "mensaje" => "Solicitar precio"),
        "no_exponer" => array("costos", "margenes", "proveedores", "costo_promedio", "utilidad")
      ),
      "endpoints" => $this->endpointsContrato(),
      "payloads" => array(
        "registro" => $this->payloadRegistro(),
        "pedido_facturacion" => $this->payloadFacturacionPedido()
      ),
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
        "categorias_interes" => "/DistribucionApi/categorias_interes",
        "perfil" => "/DistribucionApi/auth/perfil",
        "perfil_solicitar_cambio" => "/DistribucionApi/auth/perfil/solicitar_cambio",
        "recuperar_acceso" => "/DistribucionApi/auth/recuperar",
        "reenviar_activacion" => "/DistribucionApi/auth/reenviar_activacion",
        "cotizacion_dryrun" => "/DistribucionApi/cotizacion/dryrun",
        "cotizacion_registrar" => "/DistribucionApi/cotizacion/registrar",
        "cotizacion_listar" => "/DistribucionApi/cotizacion/listar",
        "cotizacion_detalle" => "/DistribucionApi/cotizacion/detalle?id_cotizacion_distribucion={id}",
        "cotizacion_guardar_borrador" => "/DistribucionApi/cotizacion/guardar_borrador",
        "cotizacion_cancelar" => "/DistribucionApi/cotizacion/cancelar",
        "cotizacion_duplicar" => "/DistribucionApi/cotizacion/duplicar",
        "cotizacion_enviar_pedido" => "/DistribucionApi/cotizacion/enviar_pedido",
        "pedido_registrar" => "/DistribucionApi/pedido/registrar",
        "pedido_listar" => "/DistribucionApi/pedido/listar",
        "pedido_detalle" => "/DistribucionApi/pedido/detalle?id_cotizacion_distribucion={id}",
        "pedido_responder" => "/DistribucionApi/pedido/responder",
        "pedido_facturacion_payload" => $this->payloadFacturacionPedido(),
        "mi_catalogo_listar" => "/DistribucionApi/mi_catalogo/listar",
        "mi_catalogo_guardar" => "/DistribucionApi/mi_catalogo/guardar",
        "inventario_cliente_listar" => "/DistribucionApi/inventario_cliente/listar",
        "inventario_cliente_guardar_conteo" => "/DistribucionApi/inventario_cliente/guardar_conteo",
        "inventario_cliente_sugerido" => "/DistribucionApi/inventario_cliente/sugerido",
        "inventario_cliente_pedido_sugerido" => "/DistribucionApi/inventario_cliente/pedido_sugerido"
      ),
      "endpoints_internos_erp" => $this->endpointsInternosErp(),
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
    $respuesta = (new CatalogoCanalesErp())->catalogoCanal("distribucion", $filtros, $contexto);
    $respuesta = $this->anexarPreciosCatalogo($respuesta, $contexto);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-10
   * Proposito: reservar ficha comercial Distribucion por slug con salida segura.
   * Impacto: Catalogo Distribucion; evita exponer detalle sin permiso comercial externo.
   * Contrato: GET read-only; requiere `distribucion.catalogo.ver` para navegar el catalogo publicado.
   */
  public function producto($slug, $contexto = array()) {
    $permiso = $this->requierePermiso($contexto, "distribucion.catalogo.ver", "No tienes permiso para ver detalle de productos");
    if ($permiso) { return $permiso; }
    $slug = $this->limpiarSlug($slug);
    if ($slug === "") {
      return $this->respuesta(true, "warning", "Slug de producto requerido", array("item" => null));
    }
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->productoCanal("distribucion", $slug, $contexto);
    $respuesta = $this->anexarPreciosCatalogo($respuesta, $contexto);
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
    $respuesta = (new CatalogoCanalesErp())->categoriasCanal("distribucion", $contexto);
    return $this->conSesionYGuardrails($respuesta, $contexto);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-10-04
   * Proposito: entregar jerarquia publica de categorias para intereses comerciales de prospectos.
   * Impacto: Registro Distribucion; permite seleccion multiple y categorias padre con descendientes.
   * Contrato: GET publico read-only; no requiere sesion externa.
   */
  public function categoriasInteres($filtros = array()) {
    require_once RUTA_APP . "/modelos/CatalogoCanalesErp.php";
    $respuesta = (new CatalogoCanalesErp())->categoriasCanal("distribucion", array());
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      $respuesta["depurar"] = array();
    }
    $respuesta["depurar"]["seleccion_multiple"] = true;
    $respuesta["depurar"]["seleccion_padre_incluye_descendientes"] = true;
    $respuesta["depurar"]["payload_recomendado"] = array(
      "categorias_interes" => array(
        array(
          "id_categoria_erp" => 0,
          "nombre" => "Categoria",
          "ruta" => "Categoria / Subcategoria",
          "incluye_descendientes" => true
        )
      )
    );
    $items = $this->valor($respuesta["depurar"], "items", array());
    $jerarquia = $this->valor($respuesta["depurar"], "jerarquia", array());
    $respuesta["mensaje"] = "Categorias de interes disponibles.";
    $respuesta["seleccion_padre_incluye_descendientes"] = true;
    $respuesta["items"] = $items;
    $respuesta["jerarquia"] = $jerarquia;
    return $respuesta;
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
    $respuesta = (new CatalogoCanalesErp())->marcasCanal("distribucion", $contexto);
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
    $categorias = $servicio->categoriasCanal("distribucion", $contexto);
    $marcas = $servicio->marcasCanal("distribucion", $contexto);
    return $this->respuesta(false, "success", "Filtros Distribucion consultados", array(
      "configurado" => $this->valor($this->valor($categorias, "depurar", array()), "configurado", false) && $this->valor($this->valor($marcas, "depurar", array()), "configurado", false),
      "categorias" => $this->valor($this->valor($categorias, "depurar", array()), "items", array()),
      "categorias_jerarquia" => $this->valor($this->valor($categorias, "depurar", array()), "jerarquia", array()),
      "seleccion_categoria_padre_incluye_descendientes" => true,
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
    $respuesta = (new CatalogoCanalesErp())->disponibilidadCanal("distribucion", $this->valor($datos, "items", array()), $contexto);
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
      $limite = max(1, min(300, intval($this->valor($filtros, "limite", 30))));
      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $offset = ($pagina - 1) * $limite;
      $q = trim((string) $this->valor($filtros, "q", ""));
      $idMarca = intval($this->valor($filtros, "id_marca_erp", $this->valor($filtros, "marca", 0)));
      $idCategoria = intval($this->valor($filtros, "id_categoria_erp", $this->valor($filtros, "categoria", 0)));
      $idProveedor = intval($this->valor($filtros, "id_proveedor", $this->valor($filtros, "proveedor", 0)));
      $canal = trim((string) $this->valor($filtros, "canal_estatus", ""));
      $precioFiltro = trim((string) $this->valor($filtros, "precio", ""));
      $imagenFiltro = trim((string) $this->valor($filtros, "imagen", ""));
      $descripcionFiltro = trim((string) $this->valor($filtros, "descripcion", ""));
      $fichaFiltro = trim((string) $this->valor($filtros, "ficha", ""));
      $estadoProducto = trim((string) $this->valor($filtros, "estatus_producto", ""));
      $soloConPrecio = intval($this->valor($filtros, "solo_con_precio", 0)) === 1;
      $where = array("p.estatus='activo'", "s.estatus='activo'");
      $params = array();
      if ($q !== "") {
        $where[] = "(p.nombre LIKE :q OR s.nombre LIKE :q OR s.sku LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($estadoProducto !== "") {
        $where[] = "p.estatus=:estatus_producto";
        $params[":estatus_producto"] = $estadoProducto;
      }
      if ($idMarca > 0) {
        $where[] = "p.id_marca_erp=:marca";
        $params[":marca"] = $idMarca;
      }
      if ($idCategoria > 0 && $this->tablaExiste($db, "erp_catalogo_producto_categorias")) {
        if ($this->tablaExiste($db, "erp_catalogo_categorias")) {
          $where[] = "EXISTS (
            SELECT 1
            FROM erp_catalogo_producto_categorias pcf
            INNER JOIN erp_catalogo_categorias cf ON cf.id_categoria_erp=pcf.id_categoria_erp
            INNER JOIN erp_catalogo_categorias cb ON cb.id_categoria_erp=:categoria_base
            WHERE pcf.id_producto_erp=p.id_producto_erp
              AND (
                pcf.id_categoria_erp=:categoria_exacta
                OR cf.id_categoria_padre=:categoria_padre
                OR (TRIM(COALESCE(cb.ruta,''))<>'' AND (
                  cf.ruta LIKE CONCAT(cb.ruta, ' / %')
                  OR cf.ruta LIKE CONCAT(cb.ruta, '/%')
                  OR cf.ruta LIKE CONCAT(cb.ruta, ' > %')
                ))
              )
          )";
          $params[":categoria_base"] = $idCategoria;
          $params[":categoria_exacta"] = $idCategoria;
          $params[":categoria_padre"] = $idCategoria;
        } else {
          $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_producto_categorias pcf WHERE pcf.id_producto_erp=p.id_producto_erp AND pcf.id_categoria_erp=:categoria)";
          $params[":categoria"] = $idCategoria;
        }
      }
      if ($idProveedor > 0 && $this->tablaExiste($db, "erp_catalogo_sku_proveedores")) {
        $where[] = "EXISTS (SELECT 1 FROM erp_catalogo_sku_proveedores spf WHERE spf.id_sku=s.id_sku AND spf.id_proveedor=:proveedor AND spf.estatus='activo')";
        $params[":proveedor"] = $idProveedor;
      }
      if ($canal === "publicado") {
        $where[] = "cv.id_canal_vinculo IS NOT NULL AND cv.estatus='activo' AND cv.sincronizar_catalogo=1";
      } elseif ($canal === "no_publicado") {
        $where[] = "(cv.id_canal_vinculo IS NULL OR cv.estatus<>'activo' OR cv.sincronizar_catalogo<>1)";
      } elseif ($canal !== "") {
        $where[] = "cv.estatus=:canal_estatus";
        $params[":canal_estatus"] = $canal;
      }
      $precioSql = "EXISTS (
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
      if (($soloConPrecio || $precioFiltro === "con_precio") && $this->tablaExiste($db, "erp_listas_precios") && $this->tablaExiste($db, "erp_listas_precios_detalle")) {
        $where[] = $precioSql;
      } elseif ($precioFiltro === "sin_precio" && $this->tablaExiste($db, "erp_listas_precios") && $this->tablaExiste($db, "erp_listas_precios_detalle")) {
        $where[] = "NOT " . $precioSql;
      }
      $imagenSql = "EXISTS (SELECT 1 FROM erp_catalogo_imagenes imgx WHERE (imgx.id_sku=s.id_sku OR imgx.id_producto_erp=p.id_producto_erp) AND imgx.estatus='activo' AND TRIM(COALESCE(imgx.url_imagen,''))<>'')";
      if ($imagenFiltro === "con_imagen" && $this->tablaExiste($db, "erp_catalogo_imagenes")) {
        $where[] = $imagenSql;
      } elseif ($imagenFiltro === "sin_imagen" && $this->tablaExiste($db, "erp_catalogo_imagenes")) {
        $where[] = "NOT " . $imagenSql;
      }
      if ($descripcionFiltro === "con_descripcion") {
        $where[] = "TRIM(COALESCE(p.descripcion,''))<>''";
      } elseif ($descripcionFiltro === "sin_descripcion") {
        $where[] = "TRIM(COALESCE(p.descripcion,''))=''";
      }
      $puedeEvaluarFicha = $this->tablaExiste($db, "erp_catalogo_imagenes") && $this->tablaExiste($db, "erp_listas_precios") && $this->tablaExiste($db, "erp_listas_precios_detalle");
      if ($fichaFiltro === "completa" && $puedeEvaluarFicha) {
        $where[] = "TRIM(COALESCE(p.descripcion,''))<>'' AND " . $precioSql . " AND " . $imagenSql;
      } elseif ($fichaFiltro === "incompleta" && $puedeEvaluarFicha) {
        $where[] = "(TRIM(COALESCE(p.descripcion,''))='' OR NOT " . $precioSql . " OR NOT " . $imagenSql . ")";
      }
      $joinMarca = $this->tablaExiste($db, "erp_catalogo_marcas") ? "LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp" : "LEFT JOIN (SELECT NULL nombre) m ON 1=0";
      $joinCategoria = $this->tablaExiste($db, "erp_catalogo_producto_categorias") && $this->tablaExiste($db, "erp_catalogo_categorias") ? "LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1 LEFT JOIN erp_catalogo_categorias cat ON cat.id_categoria_erp=pc.id_categoria_erp" : "LEFT JOIN (SELECT NULL ruta, NULL nombre) cat ON 1=0";
      $joinProveedor = $this->tablaExiste($db, "erp_catalogo_sku_proveedores") && $this->tablaExiste($db, "erp_proveedores") ? "LEFT JOIN erp_catalogo_sku_proveedores sp ON sp.id_sku=s.id_sku AND sp.estatus='activo' AND sp.es_preferido=1 LEFT JOIN erp_proveedores pr ON pr.id_proveedor=sp.id_proveedor" : "LEFT JOIN (SELECT NULL proveedor) pr ON 1=0";
      $selectPrecio = $this->tablaExiste($db, "erp_listas_precios") && $this->tablaExiste($db, "erp_listas_precios_detalle") ? "CASE WHEN " . $precioSql . " THEN 1 ELSE 0 END" : "0";
      $selectImagen = $this->tablaExiste($db, "erp_catalogo_imagenes") ? "CASE WHEN " . $imagenSql . " THEN 1 ELSE 0 END" : "0";
      $stmtTotal = $db->prepare("SELECT COUNT(DISTINCT s.id_sku)
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_canales_vinculos cv ON cv.id_sku=s.id_sku AND cv.canal='distribucion'
        WHERE " . implode(" AND ", $where));
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());
      $totalPaginas = max(1, (int) ceil($total / $limite));

      $stmt = $db->prepare("SELECT p.id_producto_erp, p.nombre producto, p.descripcion, s.id_sku, s.sku, s.nombre sku_nombre,
          m.nombre marca, COALESCE(cat.ruta, cat.nombre) categoria, pr.proveedor proveedor_principal,
          " . $selectPrecio . " tiene_precio, " . $selectImagen . " tiene_imagen,
          CASE WHEN TRIM(COALESCE(p.descripcion,''))<>'' THEN 1 ELSE 0 END tiene_descripcion,
          cv.id_canal_vinculo, cv.id_externo, cv.estatus canal_estatus, cv.sincronizar_catalogo, cv.sincronizar_precio, cv.sincronizar_existencia
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_canales_vinculos cv ON cv.id_sku=s.id_sku AND cv.canal='distribucion'
        " . $joinMarca . "
        " . $joinCategoria . "
        " . $joinProveedor . "
        WHERE " . implode(" AND ", $where) . "
        ORDER BY CASE WHEN cv.id_canal_vinculo IS NULL THEN 1 ELSE 0 END, p.nombre ASC, s.sku ASC
        LIMIT " . intval($limite) . " OFFSET " . intval($offset));
      $stmt->execute($params);
      return $this->respuesta(false, "success", "SKUs publicables consultados", array(
        "configurado" => true,
        "items" => $stmt->fetchAll(PDO::FETCH_ASSOC),
        "paginacion" => array(
          "pagina" => $pagina,
          "limite" => $limite,
          "total" => $total,
          "total_paginas" => $totalPaginas,
          "offset" => $offset,
          "tiene_anterior" => $pagina > 1,
          "tiene_siguiente" => $pagina < $totalPaginas
        )
      ));
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
      array("metodo" => "POST", "ruta" => "/DistribucionApi/auth/recuperar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/auth/reenviar_activacion"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/auth/perfil"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/auth/perfil/solicitar_cambio"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/catalogo"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/producto/{slug}"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/categorias"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/categorias_interes"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/marcas"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/filtros"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/precios/resolver"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/disponibilidad/resolver"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/dryrun"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/registrar"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/cotizacion/listar"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/cotizacion/detalle?id_cotizacion_distribucion={id}"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/guardar_borrador"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/cancelar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/duplicar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/cotizacion/enviar_pedido"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/pedido/registrar"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/pedido/listar"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/pedido/detalle?id_cotizacion_distribucion={id}"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/pedido/responder"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/mi_catalogo/listar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/mi_catalogo/guardar"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/inventario_cliente/listar"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/inventario_cliente/guardar_conteo"),
      array("metodo" => "GET", "ruta" => "/DistribucionApi/inventario_cliente/sugerido"),
      array("metodo" => "POST", "ruta" => "/DistribucionApi/inventario_cliente/pedido_sugerido")
    );
  }

  private function endpointsInternosErp() {
    return array(
      "solo_panel_erp" => true,
      "no_usar_desde_frontend_externo" => true,
      "rutas" => array(
        array("metodo" => "POST", "ruta" => "/DistribucionAdmin/cliente_catalogo_preferencias"),
        array("metodo" => "GET", "ruta" => "/DistribucionAdmin/cliente_catalogo_reglas?id_cliente_distribucion={id}"),
        array("metodo" => "POST", "ruta" => "/DistribucionAdmin/cliente_catalogo_regla_guardar")
      )
    );
  }

  private function payloadFacturacionPedido() {
    return array(
      "requiere_factura" => 1,
      "facturacion" => array(
        "rfc" => "RFC",
        "razon_social" => "Razon social",
        "regimen_fiscal" => "Regimen fiscal SAT",
        "uso_cfdi" => "G03",
        "codigo_postal_fiscal" => "00000",
        "correo_facturacion" => "facturas@dominio.com",
        "comentarios_facturacion" => "Notas fiscales del cliente"
      )
    );
  }

  private function payloadRegistro() {
    return array(
      "nombre" => "Nombre del contacto",
      "nombre_negocio" => "Nombre comercial",
      "empresa" => "Nombre comercial",
      "correo" => "cliente@dominio.com",
      "telefono" => "5555555555",
      "whatsapp" => "5555555555",
      "rfc" => "RFCOPCIONAL",
      "ciudad" => "Guadalajara",
      "estado" => "Jalisco",
      "tipo_interes" => "mayorista",
      "tipo_negocio" => "petshop",
      "calle" => "Calle",
      "numero_exterior" => "123",
      "numero_interior" => "",
      "colonia" => "Colonia",
      "codigo_postal" => "00000",
      "referencias" => "Zona o local",
      "requiere_factura" => true,
      "facturacion" => array(
        "rfc" => "RFC123456XXX",
        "razon_social" => "RAZON SOCIAL",
        "regimen_fiscal" => "601",
        "uso_cfdi" => "G03",
        "codigo_postal_fiscal" => "00000",
        "correo_facturacion" => "facturas@dominio.com",
        "comentarios_facturacion" => "Notas fiscales"
      ),
      "categorias_interes" => array(
        array(
          "id_categoria_erp" => 10,
          "nombre" => "Acuario y peces",
          "ruta" => "Mascotas / Acuario y peces",
          "incluye_descendientes" => true
        )
      ),
      "intereses_comerciales" => "Productos o marcas de interes",
      "mensaje" => "Comentarios adicionales"
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
      "cliente_debe_aceptar_confirmacion" => true,
      "factura_no_automatica" => true,
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
    $respuesta = array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
    if (is_array($depurar)) {
      foreach ($depurar as $clave => $valor) {
        if (!array_key_exists($clave, $respuesta)) {
          $respuesta[$clave] = $valor;
        }
      }
    }
    return $respuesta;
  }

  private function conSesionYGuardrails($respuesta, $contexto) {
    $respuesta = $this->aplicarContextoComercialSalida($respuesta, $contexto);
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      $respuesta["depurar"] = array();
    }
    $respuesta["depurar"]["sesion"] = $this->sesionSalida($contexto);
    $respuesta["depurar"]["guardrails"] = $this->guardrails();
    return $respuesta;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: completar catalogo y detalle con precios resueltos desde listas ERP autorizadas.
   * Impacto: API Distribucion; evita que el catalogo muestre siempre "Solicitar precio" cuando el cliente ya tiene permiso/lista.
   * Contrato: read-only; reutiliza PreciosCanalesErp y no expone costos, margenes, proveedores ni stock.
   */
  private function anexarPreciosCatalogo($respuesta, $contexto) {
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      return $respuesta;
    }
    if ($this->requiereAlgunPermiso($contexto, array("distribucion.precio.ver_publico", "distribucion.precio.ver_mayoreo", "distribucion.precio.ver_lista_asignada"), "No tienes permiso para ver precios Distribucion")) {
      return $respuesta;
    }

    $itemsCatalogo = array();
    if (isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"])) {
      foreach ($respuesta["depurar"]["items"] as $item) {
        if (is_array($item) && intval($this->valor($item, "id_sku", 0)) > 0) {
          $itemsCatalogo[] = array("id_sku" => intval($item["id_sku"]), "cantidad" => 1);
        }
      }
    }
    if (isset($respuesta["depurar"]["item"]) && is_array($respuesta["depurar"]["item"]) && intval($this->valor($respuesta["depurar"]["item"], "id_sku", 0)) > 0) {
      $itemsCatalogo[] = array("id_sku" => intval($respuesta["depurar"]["item"]["id_sku"]), "cantidad" => 1);
    }
    if (empty($itemsCatalogo)) {
      return $respuesta;
    }

    require_once RUTA_APP . "/modelos/PreciosCanalesErp.php";
    $precios = (new PreciosCanalesErp())->resolverPreciosCanal("distribucion", $itemsCatalogo, $contexto);
    $depurarPrecios = $this->valor($precios, "depurar", array());
    $itemsPrecios = $this->valor($depurarPrecios, "items", array());
    if (!is_array($itemsPrecios) || empty($itemsPrecios)) {
      return $respuesta;
    }

    $mapaPrecios = array();
    foreach ($itemsPrecios as $itemPrecio) {
      if (!is_array($itemPrecio)) { continue; }
      $idSku = intval($this->valor($itemPrecio, "id_sku", 0));
      $precio = $this->valor($itemPrecio, "precio", array());
      if ($idSku > 0 && is_array($precio)) {
        $mapaPrecios[$idSku] = $precio;
      }
    }
    if (empty($mapaPrecios)) {
      return $respuesta;
    }

    if (isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"])) {
      foreach ($respuesta["depurar"]["items"] as $indice => $item) {
        $idSku = is_array($item) ? intval($this->valor($item, "id_sku", 0)) : 0;
        if ($idSku > 0 && isset($mapaPrecios[$idSku])) {
          $respuesta["depurar"]["items"][$indice]["precio"] = $mapaPrecios[$idSku];
        }
      }
    }
    if (isset($respuesta["depurar"]["item"]) && is_array($respuesta["depurar"]["item"])) {
      $idSku = intval($this->valor($respuesta["depurar"]["item"], "id_sku", 0));
      if ($idSku > 0 && isset($mapaPrecios[$idSku])) {
        $respuesta["depurar"]["item"]["precio"] = $mapaPrecios[$idSku];
      }
    }

    return $respuesta;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: reescribir acciones comerciales de items de catalogo con el perfil externo vigente.
   * Impacto: API Distribucion; evita que el catalogo base exponga botones de precio/cotizacion/disponibilidad no autorizados.
   * Contrato: no calcula precios ni inventario; solo normaliza banderas UI a partir de `contexto.acciones`.
   */
  private function aplicarContextoComercialSalida($respuesta, $contexto) {
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      return $respuesta;
    }
    if (isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"])) {
      foreach ($respuesta["depurar"]["items"] as $indice => $item) {
        if (is_array($item)) {
          $respuesta["depurar"]["items"][$indice] = $this->aplicarContextoComercialItem($item, $contexto);
        }
      }
    }
    if (isset($respuesta["depurar"]["item"]) && is_array($respuesta["depurar"]["item"])) {
      $respuesta["depurar"]["item"] = $this->aplicarContextoComercialItem($respuesta["depurar"]["item"], $contexto);
    }
    return $respuesta;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: mantener navegacion de catalogo autorizada sin abrir precio, cotizacion o disponibilidad por accidente.
   * Impacto: API Distribucion; clientes con solo `distribucion.catalogo.ver` pueden ver productos publicados, pero no precios ni cotizacion.
   * Contrato: respeta `acciones` ya resueltas por DistribucionPermisosApi.
   */
  private function aplicarContextoComercialItem($item, $contexto) {
    $acciones = $this->valor($contexto, "acciones", array());
    $acciones = is_array($acciones) ? $acciones : array();
    $verPrecio = !empty($acciones["ver_precio"]);
    $verDisponibilidad = !empty($acciones["ver_disponibilidad"]);

    $item["acciones"] = array(
      "ver_detalle" => !empty($acciones["ver_detalle"]) || !empty($acciones["ver_catalogo"]),
      "solicitar_precio" => !empty($contexto["autenticado"]) && !$verPrecio,
      "agregar_cotizacion" => !empty($acciones["agregar_cotizacion"]),
      "pedido_preliminar" => !empty($acciones["pedido_preliminar"]),
      "descargar_catalogo" => !empty($acciones["descargar_catalogo"])
    );

    if (!$verPrecio) {
      $item["precio"] = array(
        "visible" => false,
        "tipo" => "sin_permiso",
        "moneda" => "MXN",
        "monto" => null,
        "mensaje" => "Solicitar precio"
      );
    }
    if (!$verDisponibilidad) {
      $item["disponibilidad"] = array(
        "visible" => false,
        "estado" => "consultar_disponibilidad",
        "mensaje" => "Consultar disponibilidad"
      );
    }
    return $item;
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
