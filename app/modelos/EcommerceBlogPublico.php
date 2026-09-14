<?php

class EcommerceBlogPublico extends CRUD {

  private $tiposPermitidos = array("articulo", "guia", "noticia", "video", "inspiracion", "caso_cliente", "recomendacion_producto");

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: entregar manifest publico y administrativo del Blog/CMS comercial.
   * Impacto: CMS Blog y frontend ecommerce; define endpoints, filtros y guardrails sin leer archivos internos.
   * Contrato: GET read-only; no escribe BD y no consulta legacy ecom_*.
   */
  public function manifestPublico($opciones = array()) {
    return $this->respuesta(false, "success", "Manifest Blog/CMS consultado", array(
      "fase" => "blog_cms_v1",
      "tipos_publicacion" => $this->tiposPermitidos,
      "endpoints_publicos" => array(
        "listado_blog" => "/ecommercePublico/blog?pagina=1&limite=12",
        "detalle_blog" => "/ecommercePublico/blog/{slug}",
        "busqueda_global" => "/ecommercePublico/buscar?q={termino}",
        "contenido_producto" => "/ecommercePublico/producto/{slug}/contenido_relacionado",
        "contenido_categoria" => "/ecommercePublico/categoria/{path_slug}/contenido_relacionado",
        "analytics" => "/ecommercePublico/analytics_evento"
      ),
      "parametros_blog" => array("q", "tipo", "categoria_slug", "producto_slug", "pagina", "limite"),
      "frontend" => array(
        "buscador_blog_independiente" => true,
        "buscador_global_compone_fuentes" => true,
        "videos_carga_diferida" => true,
        "no_generar_urls_en_frontend" => true
      ),
      "guardrails" => $this->guardrailsPublicos()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: listar publicaciones publicas del blog con busqueda y filtros propios.
   * Impacto: Frontend ecommerce /blog; entrega solo contenido publicado e indexable.
   * Contrato: GET publico read-only; salida incluye items y paginacion.
   */
  public function blogPublico($filtros = array()) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(false, "warning", "Blog/CMS pendiente de esquema", array(
        "ok" => true,
        "items" => array(),
        "paginacion" => $this->paginacion(1, 12, 0),
        "estado" => "pendiente_esquema",
        "guardrails" => $this->guardrailsPublicos()
      ));
    }

    try {
      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(1, min(36, intval($this->valor($filtros, "limite", 12))));
      $offset = ($pagina - 1) * $limite;
      $q = trim((string) $this->valor($filtros, "q", ""));
      $tipo = $this->tipoNormalizado($this->valor($filtros, "tipo", ""));
      $categoriaSlug = $this->slugPath($this->valor($filtros, "categoria_slug", ""));
      $productoSlug = $this->slugSimple($this->valor($filtros, "producto_slug", ""));

      $where = array("p.estado='publicado'", "(p.fecha_publicacion IS NULL OR p.fecha_publicacion<=NOW())");
      $params = array();
      if ($tipo !== "") {
        $where[] = "p.tipo=:tipo";
        $params[":tipo"] = $tipo;
      }
      if ($q !== "") {
        $where[] = "(p.titulo LIKE :q OR p.extracto LIKE :q OR p.contenido_texto LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      if ($categoriaSlug !== "" && $this->tablaExiste("erp_ecommerce_blog_categorias")) {
        $where[] = "EXISTS (SELECT 1 FROM erp_ecommerce_blog_categorias bc WHERE bc.id_blog_publicacion=p.id_blog_publicacion AND bc.estatus='activo' AND bc.path_slug=:categoria_slug)";
        $params[":categoria_slug"] = $categoriaSlug;
      }
      if ($productoSlug !== "" && $this->tablaExiste("erp_ecommerce_blog_productos") && $this->tablaExiste("erp_ecommerce_publicaciones")) {
        $where[] = "EXISTS (SELECT 1 FROM erp_ecommerce_blog_productos bp INNER JOIN erp_ecommerce_publicaciones ep ON ep.id_publicacion=bp.id_publicacion WHERE bp.id_blog_publicacion=p.id_blog_publicacion AND bp.estatus='activo' AND ep.slug=:producto_slug AND ep.estatus_publicacion='publicado')";
        $params[":producto_slug"] = $productoSlug;
      }

      $sqlWhere = implode(" AND ", $where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) total FROM erp_ecommerce_blog_publicaciones p WHERE " . $sqlWhere);
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());

      $sql = "SELECT p.* FROM erp_ecommerce_blog_publicaciones p
        WHERE " . $sqlWhere . "
        ORDER BY p.destacado DESC, COALESCE(p.fecha_publicacion, p.fecha_registro) DESC, p.orden ASC
        LIMIT " . intval($limite) . " OFFSET " . intval($offset);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearResumenPublicacion($fila);
      }

      return $this->respuesta(false, "success", "Listado de blog consultado", array(
        "ok" => true,
        "items" => $items,
        "paginacion" => $this->paginacion($pagina, $limite, $total),
        "filtros" => array("q" => $q, "tipo" => $tipo, "categoria_slug" => $categoriaSlug, "producto_slug" => $productoSlug),
        "guardrails" => $this->guardrailsPublicos()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("ok" => false, "items" => array(), "guardrails" => $this->guardrailsPublicos()));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: entregar detalle publico de una publicacion de blog por slug actual o slug anterior.
   * Impacto: Frontend ecommerce /blog/{slug}; soporta SEO, relaciones y videos diferidos.
   * Contrato: GET publico read-only; si llega slug anterior devuelve redireccion 301 sugerida.
   */
  public function blogDetallePublico($slug) {
    $db = $this->getConexion();
    $slug = $this->slugSimple($slug);
    if ($slug === "") {
      return $this->respuesta(true, "warning", "Slug de blog requerido", array("ok" => false));
    }
    if (!$db || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(false, "warning", "Blog/CMS pendiente de esquema", array("ok" => false, "estado" => "pendiente_esquema"));
    }
    try {
      $redireccion = $this->buscarRedireccionSlug($slug);
      if ($redireccion) {
        return $this->respuesta(false, "redirect", "Slug anterior de blog", array(
          "ok" => true,
          "redirect" => array("status" => 301, "to" => "/blog/" . $redireccion),
          "guardrails" => $this->guardrailsPublicos()
        ));
      }

      $stmt = $db->prepare("SELECT * FROM erp_ecommerce_blog_publicaciones WHERE slug=:slug AND estado='publicado' AND (fecha_publicacion IS NULL OR fecha_publicacion<=NOW()) LIMIT 1");
      $stmt->execute(array(":slug" => $slug));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(true, "warning", "Publicacion de blog no encontrada", array("ok" => false, "slug" => $slug));
      }
      return $this->respuesta(false, "success", "Publicacion de blog consultada", array(
        "ok" => true,
        "publicacion" => $this->formatearDetallePublicacion($fila),
        "imagenes" => $this->imagenesPublicacion(intval($fila["id_blog_publicacion"])),
        "videos" => $this->videosPublicacion(intval($fila["id_blog_publicacion"])),
        "productos_relacionados" => $this->productosRelacionados(intval($fila["id_blog_publicacion"]), 8),
        "categorias_relacionadas" => $this->categoriasRelacionadas(intval($fila["id_blog_publicacion"])),
        "bloques_interactivos" => $this->bloquesInteractivos(intval($fila["id_blog_publicacion"])),
        "publicaciones_relacionadas" => $this->publicacionesRelacionadas($fila, 4),
        "guardrails" => $this->guardrailsPublicos()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("ok" => false));
    }
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: aportar resultados del blog a una busqueda global compuesta.
   * Impacto: Busqueda ecommerce global; mantiene el blog como proveedor independiente.
   * Contrato: GET read-only; no mezcla reglas de productos ni calcula precios.
   */
  public function buscarBlogPublico($q, $limite = 6) {
    $respuesta = $this->blogPublico(array("q" => $q, "pagina" => 1, "limite" => $limite));
    return $this->valor($respuesta, array("depurar", "items"), array());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: listar guias y videos relacionados a un producto publicado.
   * Impacto: Ficha publica de producto; agrega contenido comercial sin mostrar stock exacto.
   * Contrato: GET publico read-only; relacion por publicacion ecommerce publicada.
   */
  public function contenidoProductoPublico($slug) {
    return $this->contenidoPorRelacion("producto", $this->slugSimple($slug));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: listar contenido editorial relacionado a una categoria publica.
   * Impacto: Landing de categoria; sugiere navegacion comercial y educativa.
   * Contrato: GET publico read-only; relacion por path_slug publicado en CMS Blog.
   */
  public function contenidoCategoriaPublica($pathSlug) {
    return $this->contenidoPorRelacion("categoria", $this->slugPath($pathSlug));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: registrar eventos publicos de lectura/interaccion del blog.
   * Impacto: Analytics ecommerce; mide blog_view, clicks de video, producto, categoria, imagen interactiva y WhatsApp.
   * Contrato: POST publico limitado; si falta esquema responde no persistido sin romper frontend.
   */
  public function registrarAnalyticsEvento($payload, $request = array()) {
    $eventos = array("blog_view", "blog_video_click", "blog_producto_click", "blog_categoria_click", "blog_imagen_interactiva_click", "blog_whatsapp_click");
    $evento = trim((string) $this->valor($payload, "evento", ""));
    if (!in_array($evento, $eventos, true)) {
      return $this->respuesta(true, "warning", "Evento de blog no permitido", array("ok" => false, "eventos_permitidos" => $eventos));
    }
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste("erp_ecommerce_blog_analytics")) {
      return $this->respuesta(false, "info", "Evento recibido sin persistencia activa", array("ok" => true, "persistido" => false, "evento" => $evento));
    }
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_analytics (evento, pagina, referencia_tipo, referencia_slug, detalle_json, ip_hash, user_agent_hash) VALUES (:evento, :pagina, :referencia_tipo, :referencia_slug, :detalle_json, :ip_hash, :user_agent_hash)");
    $stmt->execute(array(
      ":evento" => $evento,
      ":pagina" => substr(trim((string) $this->valor($payload, "pagina", "")), 0, 255),
      ":referencia_tipo" => substr(trim((string) $this->valor($payload, "referencia_tipo", "")), 0, 60),
      ":referencia_slug" => substr($this->slugSimple($this->valor($payload, "referencia_slug", "")), 0, 180),
      ":detalle_json" => json_encode($this->valor($payload, "detalle", array()), JSON_UNESCAPED_UNICODE),
      ":ip_hash" => hash("sha256", (string) $this->valor($request, "ip", "")),
      ":user_agent_hash" => hash("sha256", (string) $this->valor($request, "user_agent", ""))
    ));
    return $this->respuesta(false, "success", "Evento de blog registrado", array("ok" => true, "persistido" => true, "evento" => $evento));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: aportar URLs indexables del blog al sitemap publico.
   * Impacto: SEO ecommerce; incluye /blog y /blog/{slug} sin exponer rutas API.
   * Contrato: read-only; solo publicaciones `publicado` vigentes.
   */
  public function sitemapItemsPublicos($baseUrl = "", $limite = 200) {
    $items = array(array(
      "loc" => rtrim((string) $baseUrl, "/") . "/blog",
      "lastmod" => date("Y-m-d"),
      "changefreq" => "weekly",
      "priority" => "0.7"
    ));
    if (!$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $items;
    }
    $stmt = $this->getConexion()->query("SELECT url_publica, COALESCE(fecha_actualizacion, fecha_publicacion, fecha_registro) lastmod FROM erp_ecommerce_blog_publicaciones WHERE estado='publicado' AND (fecha_publicacion IS NULL OR fecha_publicacion<=NOW()) ORDER BY COALESCE(fecha_actualizacion, fecha_publicacion, fecha_registro) DESC LIMIT " . intval(max(1, min(500, $limite))));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = array(
        "loc" => rtrim((string) $baseUrl, "/") . (string) $fila["url_publica"],
        "lastmod" => $this->fechaPublica($fila["lastmod"]),
        "changefreq" => "monthly",
        "priority" => "0.6"
      );
    }
    return $items;
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: entregar estado administrativo del Blog/CMS.
   * Impacto: CMS Blog; permite revisar esquema, endpoints y guardrails antes de publicar.
   * Contrato: GET protegido desde controlador; no escribe BD.
   */
  public function adminEstado($auditoria, $plan) {
    return $this->respuesta(false, "info", "Estado Blog/CMS consultado", array(
      "fase" => "blog_cms_v1_backend",
      "esquema" => array(
        "auditoria" => $this->valor($auditoria, "depurar", array()),
        "plan" => $this->valor($plan, "depurar", array())
      ),
      "endpoints_admin" => array(
        "estado" => "/cms/blog_admin_estado_erp",
        "listar" => "/cms/blog_admin_listar_erp",
        "consultar" => "/cms/blog_admin_consultar_erp",
        "guardar" => "/cms/blog_publicacion_guardar_erp",
        "estatus" => "/cms/blog_publicacion_estatus_erp"
      ),
      "endpoints_publicos" => $this->valor($this->manifestPublico(), array("depurar", "endpoints_publicos"), array()),
      "guardrails" => $this->guardrailsPublicos()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: guardar borradores o pausados de publicaciones Blog/CMS.
   * Impacto: CMS Blog; persiste contenido editorial sin publicarlo automaticamente.
   * Contrato: POST protegido; requiere esquema aplicado y valida slug, tipo, estado y ALT de portada.
   */
  public function adminGuardar($datos, $usuarioId) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(true, "warning", "Aplica primero el esquema Blog/CMS autorizado", array("ok" => false, "requiere_ddl" => true));
    }
    $id = intval($this->valor($datos, "id_blog_publicacion", 0));
    $tipo = $this->tipoNormalizado($this->valor($datos, "tipo", "articulo"));
    $titulo = trim((string) $this->valor($datos, "titulo", ""));
    $slug = $this->slugSimple($this->valor($datos, "slug", $titulo));
    $estado = trim((string) $this->valor($datos, "estado", "borrador"));
    if ($tipo === "") { $tipo = "articulo"; }
    if (!in_array($estado, array("borrador", "pausado"), true)) { $estado = "borrador"; }
    if ($titulo === "" || $slug === "") {
      return $this->respuesta(true, "warning", "Titulo y slug son obligatorios", array("ok" => false));
    }
    $portada = $this->jsonDesdeEntrada($this->valor($datos, "imagen_portada", array()));
    if (!$this->imagenTieneAlt($portada)) {
      return $this->respuesta(true, "warning", "La imagen de portada requiere texto ALT", array("ok" => false, "campo" => "imagen_portada.alt"));
    }
    $contenidoHtml = (string) $this->valor($datos, "contenido_html", "");
    if (stripos($contenidoHtml, "<script") !== false || stripos($contenidoHtml, "onerror=") !== false || stripos($contenidoHtml, "javascript:") !== false) {
      return $this->respuesta(true, "warning", "Contenido HTML no seguro para blog", array("ok" => false));
    }
    $seo = $this->jsonDesdeEntrada($this->valor($datos, "seo", array()));
    $params = array(
      ":tipo" => $tipo,
      ":titulo" => $titulo,
      ":slug" => $slug,
      ":url_publica" => "/blog/" . $slug,
      ":estado" => $estado,
      ":autor" => substr(trim((string) $this->valor($datos, "autor", "Artiani")), 0, 120),
      ":extracto" => trim((string) $this->valor($datos, "extracto", "")),
      ":contenido_html" => $contenidoHtml,
      ":contenido_texto" => trim(strip_tags($contenidoHtml)),
      ":imagen_portada_json" => json_encode($portada, JSON_UNESCAPED_UNICODE),
      ":seo_json" => json_encode($seo, JSON_UNESCAPED_UNICODE),
      ":orden" => intval($this->valor($datos, "orden", 0)),
      ":destacado" => intval($this->valor($datos, "destacado", 0)) === 1 ? 1 : 0,
      ":fecha_publicacion" => $this->fechaSql($this->valor($datos, "fecha_publicacion", null)),
      ":usuario" => intval($usuarioId)
    );

    if ($id > 0) {
      $anterior = $this->filaPublicacionAdmin($id);
      if (!$anterior) { return $this->respuesta(true, "warning", "Publicacion no encontrada", array("ok" => false)); }
      $stmt = $db->prepare("UPDATE erp_ecommerce_blog_publicaciones SET tipo=:tipo, titulo=:titulo, slug=:slug, url_publica=:url_publica, estado=:estado, autor=:autor, extracto=:extracto, contenido_html=:contenido_html, contenido_texto=:contenido_texto, imagen_portada_json=:imagen_portada_json, seo_json=:seo_json, orden=:orden, destacado=:destacado, fecha_publicacion=:fecha_publicacion, fecha_actualizacion=NOW(), actualizado_por=:usuario WHERE id_blog_publicacion=:id");
      $params[":id"] = $id;
      $stmt->execute($params);
      if ((string) $anterior["slug"] !== $slug && $this->tablaExiste("erp_ecommerce_blog_slugs")) {
        $this->registrarSlugAnterior($id, (string) $anterior["slug"], $slug, intval($usuarioId));
      }
    } else {
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_publicaciones (tipo, titulo, slug, url_publica, estado, autor, extracto, contenido_html, contenido_texto, imagen_portada_json, seo_json, orden, destacado, fecha_publicacion, creado_por, actualizado_por) VALUES (:tipo, :titulo, :slug, :url_publica, :estado, :autor, :extracto, :contenido_html, :contenido_texto, :imagen_portada_json, :seo_json, :orden, :destacado, :fecha_publicacion, :usuario, :usuario)");
      $stmt->execute($params);
      $id = intval($db->lastInsertId());
    }

    $relaciones = $this->sincronizarRelacionesAdmin($id, $datos);

    return $this->respuesta(false, "success", "Publicacion Blog/CMS guardada", array("ok" => true, "id_blog_publicacion" => $id, "slug" => $slug, "estado" => $estado, "publicado_api" => false, "relaciones" => $relaciones));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: publicar, pausar o devolver a borrador una publicacion Blog/CMS.
   * Impacto: CMS Blog y API publica; solo `publicado` queda visible en frontend.
   * Contrato: POST protegido; valida publicabilidad antes de publicar.
   */
  public function adminEstatus($datos, $usuarioId) {
    $db = $this->getConexion();
    if (!$db || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(true, "warning", "Esquema Blog/CMS no disponible", array("ok" => false));
    }
    $id = intval($this->valor($datos, "id_blog_publicacion", 0));
    $estado = trim((string) $this->valor($datos, "estado", ""));
    if ($id <= 0 || !in_array($estado, array("borrador", "pausado", "publicado"), true)) {
      return $this->respuesta(true, "warning", "ID y estado validos son obligatorios", array("ok" => false));
    }
    $fila = $this->filaPublicacionAdmin($id);
    if (!$fila) { return $this->respuesta(true, "warning", "Publicacion no encontrada", array("ok" => false)); }
    if ($estado === "publicado") {
      $bloqueos = $this->bloqueosPublicacion($fila);
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "La publicacion no cumple requisitos para publicar", array("ok" => false, "bloqueos_publicacion" => $bloqueos));
      }
    }
    $stmt = $db->prepare("UPDATE erp_ecommerce_blog_publicaciones SET estado=:estado, fecha_actualizacion=NOW(), actualizado_por=:usuario, publicado_por=CASE WHEN :estado_publicado='publicado' THEN :usuario ELSE publicado_por END, fecha_publicacion=CASE WHEN :estado_publicado2='publicado' AND fecha_publicacion IS NULL THEN NOW() ELSE fecha_publicacion END WHERE id_blog_publicacion=:id");
    $stmt->execute(array(":estado" => $estado, ":usuario" => intval($usuarioId), ":estado_publicado" => $estado, ":estado_publicado2" => $estado, ":id" => $id));
    return $this->respuesta(false, "success", "Estatus de Blog/CMS actualizado", array("ok" => true, "id_blog_publicacion" => $id, "estatus_anterior" => $fila["estado"], "estado" => $estado, "publicado_api" => $estado === "publicado"));
  }

  public function adminListar($filtros = array()) {
    if (!$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(false, "warning", "Blog/CMS pendiente de esquema", array("items" => array(), "paginacion" => $this->paginacion(1, 20, 0)));
    }
    $db = $this->getConexion();
    $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
    $limite = max(1, min(50, intval($this->valor($filtros, "limite", 20))));
    $offset = ($pagina - 1) * $limite;
    $q = trim((string) $this->valor($filtros, "q", ""));
    $estado = trim((string) $this->valor($filtros, "estado", ""));
    $tipo = $this->tipoNormalizado($this->valor($filtros, "tipo", ""));
    $where = array("1=1");
    $params = array();
    if ($q !== "") {
      $where[] = "(titulo LIKE :q OR extracto LIKE :q OR contenido_texto LIKE :q)";
      $params[":q"] = "%" . $q . "%";
    }
    if (in_array($estado, array("borrador", "pausado", "publicado"), true)) {
      $where[] = "estado=:estado";
      $params[":estado"] = $estado;
    }
    if ($tipo !== "") {
      $where[] = "tipo=:tipo";
      $params[":tipo"] = $tipo;
    }
    $sqlWhere = implode(" AND ", $where);
    $stmtTotal = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_blog_publicaciones WHERE " . $sqlWhere);
    $stmtTotal->execute($params);
    $total = intval($stmtTotal->fetchColumn());
    $stmt = $db->prepare("SELECT * FROM erp_ecommerce_blog_publicaciones WHERE " . $sqlWhere . " ORDER BY fecha_actualizacion DESC, fecha_registro DESC LIMIT " . intval($limite) . " OFFSET " . intval($offset));
    $stmt->execute($params);
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = $this->formatearResumenPublicacion($fila);
    }
    return $this->respuesta(false, "success", "Publicaciones Blog/CMS consultadas", array("items" => $items, "paginacion" => $this->paginacion($pagina, $limite, $total), "filtros" => array("q" => $q, "estado" => $estado, "tipo" => $tipo)));
  }

  public function adminConsultar($id) {
    $fila = $this->filaPublicacionAdmin(intval($id));
    if (!$fila) { return $this->respuesta(true, "warning", "Publicacion no encontrada", array("ok" => false)); }
    $id = intval($fila["id_blog_publicacion"]);
    return $this->respuesta(false, "success", "Publicacion Blog/CMS consultada", array(
      "ok" => true,
      "publicacion" => $this->formatearDetallePublicacion($fila),
      "relaciones" => array(
        "imagenes" => $this->imagenesPublicacion($id),
        "videos" => $this->videosPublicacion($id),
        "productos" => $this->productosRelacionados($id, 50),
        "categorias" => $this->categoriasRelacionadas($id),
        "bloques_interactivos" => $this->bloquesInteractivos($id)
      )
    ));
  }

  private function contenidoPorRelacion($tipo, $slug) {
    if ($slug === "" || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) {
      return $this->respuesta(false, "warning", "Contenido relacionado no disponible", array("ok" => true, "items" => array(), "guardrails" => $this->guardrailsPublicos()));
    }
    if ($tipo === "producto" && (!$this->tablaExiste("erp_ecommerce_blog_productos") || !$this->tablaExiste("erp_ecommerce_publicaciones"))) {
      return $this->respuesta(false, "info", "Sin relacion de blog/producto activa", array("ok" => true, "producto" => array("slug" => $slug, "url" => "/producto/" . $slug), "items" => array()));
    }
    if ($tipo === "categoria" && !$this->tablaExiste("erp_ecommerce_blog_categorias")) {
      return $this->respuesta(false, "info", "Sin relacion de blog/categoria activa", array("ok" => true, "categoria" => array("url" => "/categoria/" . $slug), "items" => array()));
    }
    $db = $this->getConexion();
    $params = array(":slug" => $slug);
    $join = $tipo === "producto"
      ? "INNER JOIN erp_ecommerce_blog_productos bp ON bp.id_blog_publicacion=p.id_blog_publicacion AND bp.estatus='activo' INNER JOIN erp_ecommerce_publicaciones ep ON ep.id_publicacion=bp.id_publicacion AND ep.slug=:slug AND ep.estatus_publicacion='publicado'"
      : "INNER JOIN erp_ecommerce_blog_categorias bc ON bc.id_blog_publicacion=p.id_blog_publicacion AND bc.estatus='activo' AND bc.path_slug=:slug";
    $stmt = $db->prepare("SELECT DISTINCT p.* FROM erp_ecommerce_blog_publicaciones p " . $join . " WHERE p.estado='publicado' AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion<=NOW()) ORDER BY COALESCE(p.fecha_publicacion, p.fecha_registro) DESC LIMIT 8");
    $stmt->execute($params);
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) { $items[] = $this->formatearResumenPublicacion($fila); }
    $clave = $tipo === "producto" ? "producto" : "categoria";
    return $this->respuesta(false, "success", "Contenido relacionado consultado", array("ok" => true, $clave => array("slug" => $slug, "url" => "/" . $clave . "/" . $slug), "items" => $items, "guardrails" => $this->guardrailsPublicos()));
  }

  private function formatearResumenPublicacion($fila) {
    $portada = $this->jsonDecode($this->valor($fila, "imagen_portada_json", ""));
    return array(
      "id" => intval($fila["id_blog_publicacion"]),
      "tipo" => (string) $fila["tipo"],
      "titulo" => (string) $fila["titulo"],
      "slug" => (string) $fila["slug"],
      "url" => (string) $fila["url_publica"],
      "estado" => (string) $fila["estado"],
      "fecha_publicacion" => $this->fechaPublica($this->valor($fila, "fecha_publicacion", "")),
      "autor" => (string) $fila["autor"],
      "extracto" => (string) $fila["extracto"],
      "thumbnail" => $this->valor($portada, "thumbnail", $this->valor($portada, "url", "")),
      "imagen_portada" => $portada
    );
  }

  private function formatearDetallePublicacion($fila) {
    $item = $this->formatearResumenPublicacion($fila);
    $seo = $this->jsonDecode($this->valor($fila, "seo_json", ""));
    if (empty($seo)) {
      $seo = array(
        "title" => $fila["titulo"] . " | Artiani",
        "description" => (string) $fila["extracto"],
        "canonical" => (string) $fila["url_publica"],
        "robots" => "index,follow",
        "og_image" => $this->valor($item, array("imagen_portada", "url"), "")
      );
    }
    $item["contenido_html"] = (string) $fila["contenido_html"];
    $item["seo"] = $seo;
    return $item;
  }

  private function imagenesPublicacion($id) {
    if (!$this->tablaExiste("erp_ecommerce_blog_media")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_blog_media WHERE id_blog_publicacion=:id AND estatus='activo' ORDER BY orden ASC, id_blog_media ASC");
    $stmt->execute(array(":id" => $id));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = array("url" => $fila["url"], "alt" => $fila["alt_text"], "caption" => $fila["caption"], "width" => intval($fila["width"]), "height" => intval($fila["height"]), "versiones" => array("desktop" => $fila["url_desktop"], "tablet" => $fila["url_tablet"], "mobile" => $fila["url_mobile"], "thumbnail" => $fila["url_thumbnail"]));
    }
    return $items;
  }

  private function videosPublicacion($id) {
    if (!$this->tablaExiste("erp_ecommerce_blog_videos")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT tipo, titulo, descripcion, thumbnail, embed_url, url_original, posicion, orden FROM erp_ecommerce_blog_videos WHERE id_blog_publicacion=:id AND estatus='activo' ORDER BY orden ASC, id_blog_video ASC");
    $stmt->execute(array(":id" => $id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function productosRelacionados($id, $limite) {
    if (!$this->tablaExiste("erp_ecommerce_blog_productos") || !$this->tablaExiste("erp_ecommerce_publicaciones")) { return array(); }
    $db = $this->getConexion();
    $sql = "SELECT ep.id_publicacion, ep.titulo_publico nombre, ep.slug, COALESCE(ep.url_publica, CONCAT('/producto/', ep.slug)) url, ep.presentacion_publica marca
      FROM erp_ecommerce_blog_productos bp
      INNER JOIN erp_ecommerce_publicaciones ep ON ep.id_publicacion=bp.id_publicacion AND ep.estatus_publicacion='publicado'
      WHERE bp.id_blog_publicacion=:id AND bp.estatus='activo'
      ORDER BY bp.orden ASC, bp.id_blog_producto ASC LIMIT " . intval($limite);
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":id" => $id));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = array("id_publicacion" => intval($fila["id_publicacion"]), "nombre" => $fila["nombre"], "slug" => $fila["slug"], "url" => $fila["url"], "imagen" => "", "precio" => "", "marca" => $fila["marca"]);
    }
    return $items;
  }

  private function categoriasRelacionadas($id) {
    if (!$this->tablaExiste("erp_ecommerce_blog_categorias")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT nombre, path_slug, url_publica url FROM erp_ecommerce_blog_categorias WHERE id_blog_publicacion=:id AND estatus='activo' ORDER BY orden ASC, id_blog_categoria ASC");
    $stmt->execute(array(":id" => $id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function bloquesInteractivos($id) {
    if (!$this->tablaExiste("erp_ecommerce_blog_bloques_interactivos")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT tipo, titulo, imagen_json, puntos_json FROM erp_ecommerce_blog_bloques_interactivos WHERE id_blog_publicacion=:id AND estatus='activo' ORDER BY orden ASC, id_blog_bloque_interactivo ASC");
    $stmt->execute(array(":id" => $id));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = array("tipo" => $fila["tipo"], "titulo" => $fila["titulo"], "imagen" => $this->jsonDecode($fila["imagen_json"]), "puntos" => $this->jsonDecode($fila["puntos_json"]));
    }
    return $items;
  }

  private function publicacionesRelacionadas($fila, $limite) {
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_blog_publicaciones WHERE id_blog_publicacion<>:id AND estado='publicado' AND tipo=:tipo AND (fecha_publicacion IS NULL OR fecha_publicacion<=NOW()) ORDER BY COALESCE(fecha_publicacion, fecha_registro) DESC LIMIT " . intval($limite));
    $stmt->execute(array(":id" => intval($fila["id_blog_publicacion"]), ":tipo" => $fila["tipo"]));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $rel) { $items[] = $this->formatearResumenPublicacion($rel); }
    return $items;
  }

  private function filaPublicacionAdmin($id) {
    if ($id <= 0 || !$this->tablaExiste("erp_ecommerce_blog_publicaciones")) { return null; }
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_blog_publicaciones WHERE id_blog_publicacion=:id LIMIT 1");
    $stmt->execute(array(":id" => $id));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
  }

  private function buscarRedireccionSlug($slug) {
    if (!$this->tablaExiste("erp_ecommerce_blog_slugs")) { return ""; }
    $stmt = $this->getConexion()->prepare("SELECT slug_actual FROM erp_ecommerce_blog_slugs WHERE slug_anterior=:slug AND estatus='activo' LIMIT 1");
    $stmt->execute(array(":slug" => $slug));
    return (string) $stmt->fetchColumn();
  }

  private function registrarSlugAnterior($id, $anterior, $actual, $usuarioId) {
    $stmt = $this->getConexion()->prepare("INSERT INTO erp_ecommerce_blog_slugs (id_blog_publicacion, slug_anterior, slug_actual, registrado_por) VALUES (:id, :anterior, :actual, :usuario) ON DUPLICATE KEY UPDATE slug_actual=VALUES(slug_actual), estatus='activo'");
    $stmt->execute(array(":id" => $id, ":anterior" => $anterior, ":actual" => $actual, ":usuario" => $usuarioId));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-11
   * Proposito: sincronizar relaciones editoriales del blog enviadas por el CMS.
   * Impacto: CMS Blog; guarda media, videos, productos, categorias y bloques interactivos sin tocar catalogo.
   * Contrato: reemplaza relaciones activas del articulo por las recibidas; espera JSON validado por backend.
   */
  private function sincronizarRelacionesAdmin($id, $datos) {
    $resumen = array("imagenes" => 0, "videos" => 0, "productos" => 0, "categorias" => 0, "bloques_interactivos" => 0);
    if ($id <= 0) { return $resumen; }
    $this->sincronizarImagenesAdmin($id, $this->jsonListaEntrada($this->valor($datos, "imagenes_json", null)), $resumen);
    $this->sincronizarVideosAdmin($id, $this->jsonListaEntrada($this->valor($datos, "videos_json", null)), $resumen);
    $this->sincronizarProductosAdmin($id, $this->jsonListaEntrada($this->valor($datos, "productos_json", null)), $resumen);
    $this->sincronizarCategoriasAdmin($id, $this->jsonListaEntrada($this->valor($datos, "categorias_json", null)), $resumen);
    $this->sincronizarBloquesInteractivosAdmin($id, $this->jsonListaEntrada($this->valor($datos, "bloques_interactivos_json", null)), $resumen);
    return $resumen;
  }

  private function sincronizarImagenesAdmin($id, $items, &$resumen) {
    if ($items === null || !$this->tablaExiste("erp_ecommerce_blog_media")) { return; }
    $db = $this->getConexion();
    $db->prepare("UPDATE erp_ecommerce_blog_media SET estatus='pausado' WHERE id_blog_publicacion=:id")->execute(array(":id" => $id));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_media (id_blog_publicacion, rol, url, url_desktop, url_tablet, url_mobile, url_thumbnail, alt_text, caption, width, height, orden, estatus, metadata_json) VALUES (:id, :rol, :url, :desktop, :tablet, :mobile, :thumbnail, :alt, :caption, :width, :height, :orden, 'activo', :metadata)");
    foreach ($items as $i => $item) {
      if (trim((string) $this->valor($item, "url", "")) === "" || trim((string) $this->valor($item, "alt", $this->valor($item, "alt_text", ""))) === "") { continue; }
      $stmt->execute(array(":id" => $id, ":rol" => substr(trim((string) $this->valor($item, "rol", "contenido")), 0, 40), ":url" => trim((string) $this->valor($item, "url", "")), ":desktop" => trim((string) $this->valor($item, "desktop", "")), ":tablet" => trim((string) $this->valor($item, "tablet", "")), ":mobile" => trim((string) $this->valor($item, "mobile", "")), ":thumbnail" => trim((string) $this->valor($item, "thumbnail", "")), ":alt" => trim((string) $this->valor($item, "alt", $this->valor($item, "alt_text", ""))), ":caption" => trim((string) $this->valor($item, "caption", "")), ":width" => intval($this->valor($item, "width", 0)), ":height" => intval($this->valor($item, "height", 0)), ":orden" => intval($this->valor($item, "orden", $i + 1)), ":metadata" => json_encode($this->valor($item, "metadata", array()), JSON_UNESCAPED_UNICODE)));
      $resumen["imagenes"]++;
    }
  }

  private function sincronizarVideosAdmin($id, $items, &$resumen) {
    if ($items === null || !$this->tablaExiste("erp_ecommerce_blog_videos")) { return; }
    $db = $this->getConexion();
    $db->prepare("UPDATE erp_ecommerce_blog_videos SET estatus='pausado' WHERE id_blog_publicacion=:id")->execute(array(":id" => $id));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_videos (id_blog_publicacion, tipo, titulo, descripcion, thumbnail, embed_url, url_original, posicion, orden, estatus) VALUES (:id, :tipo, :titulo, :descripcion, :thumbnail, :embed, :original, :posicion, :orden, 'activo')");
    foreach ($items as $i => $item) {
      if (trim((string) $this->valor($item, "thumbnail", "")) === "" || trim((string) $this->valor($item, "embed_url", "")) === "") { continue; }
      $stmt->execute(array(":id" => $id, ":tipo" => substr($this->slugSimple($this->valor($item, "tipo", "tiktok")), 0, 40), ":titulo" => substr(trim((string) $this->valor($item, "titulo", "Video")), 0, 180), ":descripcion" => substr(trim((string) $this->valor($item, "descripcion", "")), 0, 255), ":thumbnail" => trim((string) $this->valor($item, "thumbnail", "")), ":embed" => trim((string) $this->valor($item, "embed_url", "")), ":original" => trim((string) $this->valor($item, "url_original", "")), ":posicion" => substr(trim((string) $this->valor($item, "posicion", "contenido")), 0, 40), ":orden" => intval($this->valor($item, "orden", $i + 1))));
      $resumen["videos"]++;
    }
  }

  private function sincronizarProductosAdmin($id, $items, &$resumen) {
    if ($items === null || !$this->tablaExiste("erp_ecommerce_blog_productos")) { return; }
    $db = $this->getConexion();
    $db->prepare("UPDATE erp_ecommerce_blog_productos SET estatus='pausado' WHERE id_blog_publicacion=:id")->execute(array(":id" => $id));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_productos (id_blog_publicacion, id_publicacion, orden, estatus) VALUES (:id, :id_publicacion, :orden, 'activo')");
    foreach ($items as $i => $item) {
      $idPublicacion = intval($this->valor($item, "id_publicacion", $item));
      if ($idPublicacion <= 0) { continue; }
      $stmt->execute(array(":id" => $id, ":id_publicacion" => $idPublicacion, ":orden" => intval($this->valor($item, "orden", $i + 1))));
      $resumen["productos"]++;
    }
  }

  private function sincronizarCategoriasAdmin($id, $items, &$resumen) {
    if ($items === null || !$this->tablaExiste("erp_ecommerce_blog_categorias")) { return; }
    $db = $this->getConexion();
    $db->prepare("UPDATE erp_ecommerce_blog_categorias SET estatus='pausado' WHERE id_blog_publicacion=:id")->execute(array(":id" => $id));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_categorias (id_blog_publicacion, id_categoria_erp, nombre, path_slug, url_publica, orden, estatus) VALUES (:id, :id_categoria, :nombre, :slug, :url, :orden, 'activo')");
    foreach ($items as $i => $item) {
      $slug = $this->slugPath($this->valor($item, "path_slug", $this->valor($item, "slug", "")));
      $nombre = trim((string) $this->valor($item, "nombre", ""));
      if ($slug === "" || $nombre === "") { continue; }
      $stmt->execute(array(":id" => $id, ":id_categoria" => intval($this->valor($item, "id_categoria_erp", 0)) ?: null, ":nombre" => substr($nombre, 0, 180), ":slug" => $slug, ":url" => trim((string) $this->valor($item, "url", "/categoria/" . $slug)), ":orden" => intval($this->valor($item, "orden", $i + 1))));
      $resumen["categorias"]++;
    }
  }

  private function sincronizarBloquesInteractivosAdmin($id, $items, &$resumen) {
    if ($items === null || !$this->tablaExiste("erp_ecommerce_blog_bloques_interactivos")) { return; }
    $db = $this->getConexion();
    $db->prepare("UPDATE erp_ecommerce_blog_bloques_interactivos SET estatus='pausado' WHERE id_blog_publicacion=:id")->execute(array(":id" => $id));
    $stmt = $db->prepare("INSERT INTO erp_ecommerce_blog_bloques_interactivos (id_blog_publicacion, tipo, titulo, imagen_json, puntos_json, orden, estatus) VALUES (:id, :tipo, :titulo, :imagen, :puntos, :orden, 'activo')");
    foreach ($items as $i => $item) {
      $imagen = $this->valor($item, "imagen", array());
      $puntos = $this->valor($item, "puntos", array());
      if (!$this->imagenTieneAlt($imagen) || !is_array($puntos) || empty($puntos)) { continue; }
      $stmt->execute(array(":id" => $id, ":tipo" => substr($this->slugSimple($this->valor($item, "tipo", "imagen_productos")), 0, 60), ":titulo" => substr(trim((string) $this->valor($item, "titulo", "")), 0, 180), ":imagen" => json_encode($imagen, JSON_UNESCAPED_UNICODE), ":puntos" => json_encode($puntos, JSON_UNESCAPED_UNICODE), ":orden" => intval($this->valor($item, "orden", $i + 1))));
      $resumen["bloques_interactivos"]++;
    }
  }

  private function jsonListaEntrada($valor) {
    if ($valor === null) { return null; }
    if (is_array($valor)) { return array_values($valor); }
    $valor = trim((string) $valor);
    if ($valor === "") { return array(); }
    $decode = json_decode($valor, true);
    return is_array($decode) ? array_values($decode) : array();
  }

  private function bloqueosPublicacion($fila) {
    $bloqueos = array();
    if (trim((string) $fila["titulo"]) === "") { $bloqueos[] = "titulo_obligatorio"; }
    if (trim((string) $fila["slug"]) === "") { $bloqueos[] = "slug_obligatorio"; }
    if (trim((string) $fila["extracto"]) === "") { $bloqueos[] = "extracto_obligatorio"; }
    if (trim(strip_tags((string) $fila["contenido_html"])) === "") { $bloqueos[] = "contenido_obligatorio"; }
    if (!$this->imagenTieneAlt($this->jsonDecode($fila["imagen_portada_json"]))) { $bloqueos[] = "portada_alt_obligatorio"; }
    return $bloqueos;
  }

  private function tablaExiste($tabla) {
    $db = $this->getConexion();
    if (!$db) { return false; }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function tipoNormalizado($tipo) {
    $tipo = $this->slugSimple($tipo);
    return in_array($tipo, $this->tiposPermitidos, true) ? $tipo : "";
  }

  private function slugSimple($valor) {
    $valor = strtolower(trim((string) $valor));
    $valor = strtr($valor, array("á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u", "ñ" => "n", "ü" => "u"));
    $valor = preg_replace('/[^a-z0-9]+/', '-', $valor);
    return trim((string) $valor, "-");
  }

  private function slugPath($valor) {
    $partes = array();
    foreach (explode("/", (string) $valor) as $parte) {
      $slug = $this->slugSimple($parte);
      if ($slug !== "") { $partes[] = $slug; }
    }
    return implode("/", $partes);
  }

  private function jsonDesdeEntrada($valor) {
    if (is_array($valor)) { return $valor; }
    $decode = json_decode((string) $valor, true);
    return is_array($decode) ? $decode : array();
  }

  private function jsonDecode($valor) {
    $decode = json_decode((string) $valor, true);
    return is_array($decode) ? $decode : array();
  }

  private function imagenTieneAlt($imagen) {
    return is_array($imagen) && trim((string) $this->valor($imagen, "alt", $this->valor($imagen, "alt_text", ""))) !== "";
  }

  private function fechaSql($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") { return null; }
    $ts = strtotime($valor);
    return $ts ? date("Y-m-d H:i:s", $ts) : null;
  }

  private function fechaPublica($valor) {
    $ts = strtotime((string) $valor);
    return $ts ? date("Y-m-d", $ts) : "";
  }

  private function paginacion($pagina, $limite, $total) {
    return array("pagina" => $pagina, "limite" => $limite, "total" => $total, "total_paginas" => $limite > 0 ? intval(ceil($total / $limite)) : 0);
  }

  private function guardrailsPublicos() {
    return array(
      "read_only_publico" => true,
      "solo_publicados" => true,
      "no_borradores" => true,
      "no_usa_ecom_legacy" => true,
      "no_stock_exacto" => true,
      "no_calcula_precio_frontend" => true,
      "videos_carga_diferida" => true,
      "urls_entregadas_por_api" => true
    );
  }

  private function valor($datos, $clave, $default = null) {
    if (is_array($clave)) {
      $actual = $datos;
      foreach ($clave as $parte) {
        if (!is_array($actual) || !array_key_exists($parte, $actual)) { return $default; }
        $actual = $actual[$parte];
      }
      return $actual;
    }
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
