<?php

require_once __DIR__ . "/EcommerceVideosErp.php";

class EcommerceVideosCms extends CRUD {

  private $tiposPermitidos = array("demo_producto", "instalacion", "comparativo", "consejo_rapido", "unboxing", "uso_producto", "inspiracion", "faq");
  private $estadosPermitidos = array("borrador", "pausado", "publicado");

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar estado administrativo del modulo Videos/CMS.
   * Impacto: CMS Videos; muestra esquema, endpoints y contrato TikTok sin ejecutar DDL.
   * Contrato: GET protegido desde controlador; solo lectura.
   */
  public function adminEstado() {
    $videos = new EcommerceVideosErp();
    $auditoria = $videos->esquemaAuditarVideos();
    $plan = $videos->esquemaPlanVideos();
    return $this->respuesta(false, "info", "Estado Videos/CMS consultado", array(
      "fase" => "videos_cms_tiktok_v1",
      "esquema" => array(
        "auditoria" => $this->valor($auditoria, "depurar", array()),
        "plan" => $this->valor($plan, "depurar", array())
      ),
      "endpoints_admin" => array(
        "estado" => "/cms/videos_admin_estado_erp",
        "listar" => "/cms/videos_admin_listar_erp",
        "consultar" => "/cms/videos_admin_consultar_erp",
        "guardar" => "/cms/videos_guardar_erp",
        "estatus" => "/cms/videos_estatus_erp"
      ),
      "endpoints_publicos" => array(
        "listado" => "/ecommercePublico/videos",
        "detalle" => "/ecommercePublico/videos/{slug}",
        "producto" => "/ecommercePublico/producto/{slug}/videos",
        "categoria" => "/ecommercePublico/categoria/{path_slug}/videos"
      ),
      "provider" => array(
        "nombre" => "tiktok",
        "guarda_solo_enlace" => true,
        "embed_formato" => "https://www.tiktok.com/player/v1/{post_id}?autoplay=0&description=0",
        "thumbnail_obligatorio" => true
      ),
      "tipos_video" => $this->tiposPermitidos,
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: listar videos desde CMS sin cargar iframes.
   * Impacto: CMS Videos; permite revision editorial por titulo, copy y estado.
   * Contrato: GET protegido; si falta esquema responde vacio con advertencia.
   */
  public function adminListar($filtros = array()) {
    if (!$this->schemaDisponible()) {
      return $this->respuesta(false, "warning", "Videos/CMS pendiente de esquema", array(
        "items" => array(),
        "paginacion" => $this->paginacion(1, 20, 0),
        "requiere_ddl" => true,
        "guardrails" => $this->guardrails()
      ));
    }

    $db = $this->getConexion();
    $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
    $limite = max(1, min(50, intval($this->valor($filtros, "limite", 20))));
    $offset = ($pagina - 1) * $limite;
    $q = trim((string) $this->valor($filtros, "q", ""));
    $estado = trim((string) $this->valor($filtros, "estado", ""));
    $tipo = $this->tipoNormalizado($this->valor($filtros, "tipo_video", $this->valor($filtros, "tipo", "")));

    $where = array("1=1");
    $params = array();
    if ($q !== "") {
      $where[] = "(titulo LIKE :q OR descripcion_corta LIKE :q OR copy_tiktok LIKE :q OR hashtags LIKE :q OR texto_busqueda LIKE :q)";
      $params[":q"] = "%" . $q . "%";
    }
    if (in_array($estado, $this->estadosPermitidos, true)) {
      $where[] = "estado=:estado";
      $params[":estado"] = $estado;
    }
    if ($tipo !== "") {
      $where[] = "tipo_video=:tipo_video";
      $params[":tipo_video"] = $tipo;
    }

    $sqlWhere = implode(" AND ", $where);
    $stmtTotal = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_videos WHERE " . $sqlWhere);
    $stmtTotal->execute($params);
    $total = intval($stmtTotal->fetchColumn());

    $stmt = $db->prepare("SELECT * FROM erp_ecommerce_videos WHERE " . $sqlWhere . " ORDER BY destacado DESC, orden ASC, COALESCE(updated_at, created_at) DESC LIMIT " . intval($limite) . " OFFSET " . intval($offset));
    $stmt->execute($params);
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $items[] = $this->formatearResumenVideo($fila);
    }

    return $this->respuesta(false, "success", "Videos CMS consultados", array(
      "items" => $items,
      "paginacion" => $this->paginacion($pagina, $limite, $total),
      "filtros" => array("q" => $q, "estado" => $estado, "tipo_video" => $tipo),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: consultar un video completo para edicion interna.
   * Impacto: CMS Videos; recupera datos editoriales y relaciones producto/categoria.
   * Contrato: GET protegido; no publica ni carga iframe.
   */
  public function adminConsultar($id) {
    $fila = $this->filaVideoAdmin(intval($id));
    if (!$fila) {
      return $this->respuesta(true, "warning", "Video no encontrado", array("ok" => false));
    }
    $id = intval($fila["id_video"]);
    return $this->respuesta(false, "success", "Video CMS consultado", array(
      "ok" => true,
      "video" => $this->formatearDetalleVideo($fila),
      "relaciones" => array(
        "productos" => $this->productosVideo($id),
        "categorias" => $this->categoriasVideo($id)
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: guardar un video TikTok en estado de trabajo.
   * Impacto: CMS Videos; persiste enlace externo, embed diferido, miniatura y relaciones.
   * Contrato: POST protegido; requiere esquema aplicado y no descarga videos.
   */
  public function adminGuardar($datos, $usuarioId) {
    $db = $this->getConexion();
    if (!$db || !$this->schemaDisponible()) {
      return $this->respuesta(true, "warning", "Aplica primero el esquema Videos/CMS autorizado", array("ok" => false, "requiere_ddl" => true));
    }

    $id = intval($this->valor($datos, "id_video", 0));
    $titulo = trim((string) $this->valor($datos, "titulo", ""));
    $slug = $this->slugSimple($this->valor($datos, "slug", $titulo));
    $tipo = $this->tipoNormalizado($this->valor($datos, "tipo_video", "demo_producto"));
    $estado = trim((string) $this->valor($datos, "estado", "borrador"));
    if (!in_array($estado, array("borrador", "pausado"), true)) { $estado = "borrador"; }
    if ($tipo === "") { $tipo = "demo_producto"; }

    $videoUrl = trim((string) $this->valor($datos, "video_url", ""));
    $tiktok = $this->extraerTikTok($videoUrl);
    $postId = trim((string) $this->valor($datos, "tiktok_post_id", $tiktok["post_id"]));
    $author = trim((string) $this->valor($datos, "tiktok_author", $tiktok["author"]));
    $embedUrl = trim((string) $this->valor($datos, "embed_url", ""));
    if ($embedUrl === "" && $postId !== "") {
      $embedUrl = $this->embedTikTok($postId);
    }

    $thumbnailUrl = trim((string) $this->valor($datos, "thumbnail_url", ""));
    $thumbnailAlt = trim((string) $this->valor($datos, "thumbnail_alt", ""));
    if ($titulo === "" || $slug === "") {
      return $this->respuesta(true, "warning", "Titulo y slug son obligatorios", array("ok" => false));
    }
    if ($postId === "" || $videoUrl === "" || !$this->urlTikTokValida($videoUrl)) {
      return $this->respuesta(true, "warning", "El enlace TikTok es obligatorio y debe ser valido", array("ok" => false, "campo" => "video_url"));
    }
    if ($thumbnailUrl === "" || $thumbnailAlt === "") {
      return $this->respuesta(true, "warning", "La miniatura y su texto ALT son obligatorios", array("ok" => false, "campo" => "thumbnail"));
    }

    $metadata = $this->jsonDesdeEntrada($this->valor($datos, "metadata_json", array()));
    $params = array(
      ":titulo" => $titulo,
      ":slug" => $slug,
      ":descripcion_corta" => trim((string) $this->valor($datos, "descripcion_corta", "")),
      ":descripcion_larga" => trim((string) $this->valor($datos, "descripcion_larga", "")),
      ":tipo_video" => $tipo,
      ":provider" => "tiktok",
      ":tiktok_post_id" => $postId,
      ":tiktok_author" => $author,
      ":video_url" => $videoUrl,
      ":embed_url" => $embedUrl,
      ":copy_tiktok" => trim((string) $this->valor($datos, "copy_tiktok", "")),
      ":hashtags" => $this->normalizarHashtags($this->valor($datos, "hashtags", "")),
      ":texto_busqueda" => trim((string) $this->valor($datos, "texto_busqueda", "")),
      ":thumbnail_url" => $thumbnailUrl,
      ":thumbnail_alt" => $thumbnailAlt,
      ":orientacion" => $this->orientacionNormalizada($this->valor($datos, "orientacion", "vertical")),
      ":duracion_segundos" => max(0, intval($this->valor($datos, "duracion_segundos", 0))),
      ":estado" => $estado,
      ":fecha_publicacion" => $this->fechaSql($this->valor($datos, "fecha_publicacion", null)),
      ":orden" => intval($this->valor($datos, "orden", 0)),
      ":destacado" => intval($this->valor($datos, "destacado", 0)) === 1 ? 1 : 0,
      ":seo_title" => trim((string) $this->valor($datos, "seo_title", "")),
      ":seo_description" => trim((string) $this->valor($datos, "seo_description", "")),
      ":seo_canonical" => trim((string) $this->valor($datos, "seo_canonical", "")),
      ":og_image" => trim((string) $this->valor($datos, "og_image", "")),
      ":metadata_json" => json_encode($metadata, JSON_UNESCAPED_UNICODE),
      ":usuario" => intval($usuarioId)
    );

    try {
      $db->beginTransaction();
      if ($id > 0) {
        if (!$this->filaVideoAdmin($id)) {
          $db->rollBack();
          return $this->respuesta(true, "warning", "Video no encontrado", array("ok" => false));
        }
        $sql = "UPDATE erp_ecommerce_videos SET titulo=:titulo, slug=:slug, descripcion_corta=:descripcion_corta, descripcion_larga=:descripcion_larga, tipo_video=:tipo_video, provider=:provider, tiktok_post_id=:tiktok_post_id, tiktok_author=:tiktok_author, video_url=:video_url, embed_url=:embed_url, copy_tiktok=:copy_tiktok, hashtags=:hashtags, texto_busqueda=:texto_busqueda, thumbnail_url=:thumbnail_url, thumbnail_alt=:thumbnail_alt, orientacion=:orientacion, duracion_segundos=:duracion_segundos, estado=:estado, fecha_publicacion=:fecha_publicacion, orden=:orden, destacado=:destacado, seo_title=:seo_title, seo_description=:seo_description, seo_canonical=:seo_canonical, og_image=:og_image, metadata_json=:metadata_json, updated_at=NOW(), actualizado_por=:usuario WHERE id_video=:id";
        $params[":id"] = $id;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
      } else {
        $sql = "INSERT INTO erp_ecommerce_videos (titulo, slug, descripcion_corta, descripcion_larga, tipo_video, provider, tiktok_post_id, tiktok_author, video_url, embed_url, copy_tiktok, hashtags, texto_busqueda, thumbnail_url, thumbnail_alt, orientacion, duracion_segundos, estado, fecha_publicacion, orden, destacado, seo_title, seo_description, seo_canonical, og_image, metadata_json, creado_por, actualizado_por) VALUES (:titulo, :slug, :descripcion_corta, :descripcion_larga, :tipo_video, :provider, :tiktok_post_id, :tiktok_author, :video_url, :embed_url, :copy_tiktok, :hashtags, :texto_busqueda, :thumbnail_url, :thumbnail_alt, :orientacion, :duracion_segundos, :estado, :fecha_publicacion, :orden, :destacado, :seo_title, :seo_description, :seo_canonical, :og_image, :metadata_json, :usuario, :usuario)";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $id = intval($db->lastInsertId());
      }

      $relaciones = $this->sincronizarRelaciones($id, $datos);
      $db->commit();
    } catch (Exception $e) {
      if ($db->inTransaction()) { $db->rollBack(); }
      return $this->respuesta(true, "danger", $e->getMessage(), array("ok" => false));
    }

    return $this->respuesta(false, "success", "Video CMS guardado", array(
      "ok" => true,
      "id_video" => $id,
      "slug" => $slug,
      "estado" => $estado,
      "provider" => "tiktok",
      "tiktok_post_id" => $postId,
      "embed_url" => $embedUrl,
      "publicado_api" => false,
      "relaciones" => $relaciones
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: cambiar estado editorial de un video TikTok.
   * Impacto: CMS Videos y API publica; solo publicado aparece en endpoints publicos.
   * Contrato: POST protegido; valida requisitos antes de publicar.
   */
  public function adminEstatus($datos, $usuarioId) {
    $db = $this->getConexion();
    if (!$db || !$this->schemaDisponible()) {
      return $this->respuesta(true, "warning", "Esquema Videos/CMS no disponible", array("ok" => false));
    }
    $id = intval($this->valor($datos, "id_video", 0));
    $estado = trim((string) $this->valor($datos, "estado", ""));
    if ($id <= 0 || !in_array($estado, $this->estadosPermitidos, true)) {
      return $this->respuesta(true, "warning", "ID y estado validos son obligatorios", array("ok" => false));
    }
    $fila = $this->filaVideoAdmin($id);
    if (!$fila) { return $this->respuesta(true, "warning", "Video no encontrado", array("ok" => false)); }
    if ($estado === "publicado") {
      $bloqueos = $this->bloqueosPublicacion($fila);
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "El video no cumple requisitos para publicar", array("ok" => false, "bloqueos_publicacion" => $bloqueos));
      }
    }
    $stmt = $db->prepare("UPDATE erp_ecommerce_videos SET estado=:estado, updated_at=NOW(), actualizado_por=:usuario, fecha_publicacion=CASE WHEN :estado_publicado='publicado' AND fecha_publicacion IS NULL THEN NOW() ELSE fecha_publicacion END WHERE id_video=:id");
    $stmt->execute(array(":estado" => $estado, ":usuario" => intval($usuarioId), ":estado_publicado" => $estado, ":id" => $id));
    return $this->respuesta(false, "success", "Estatus de video actualizado", array(
      "ok" => true,
      "id_video" => $id,
      "estatus_anterior" => $fila["estado"],
      "estado" => $estado,
      "publicado_api" => $estado === "publicado"
    ));
  }

  private function sincronizarRelaciones($idVideo, $datos) {
    $resumen = array("productos" => 0, "categorias" => 0);
    $db = $this->getConexion();
    if ($this->tablaExiste("erp_ecommerce_video_producto")) {
      $db->prepare("DELETE FROM erp_ecommerce_video_producto WHERE id_video=:id")->execute(array(":id" => intval($idVideo)));
      $productos = $this->jsonDesdeEntrada($this->valor($datos, "productos_json", array()));
      $principal = $this->jsonDesdeEntrada($this->valor($datos, "producto_principal_json", array()));
      if (!empty($principal)) {
        $principal["producto_principal"] = 1;
        array_unshift($productos, $principal);
      }
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_video_producto (id_video, id_publicacion, id_sku, slug_producto, producto_principal, orden) VALUES (:id_video, :id_publicacion, :id_sku, :slug_producto, :producto_principal, :orden)");
      $orden = 0;
      foreach ($productos as $producto) {
        if (!is_array($producto)) { continue; }
        $slug = $this->slugSimple($this->valor($producto, "slug_producto", $this->valor($producto, "slug", "")));
        $idPublicacion = intval($this->valor($producto, "id_publicacion", 0));
        $idSku = intval($this->valor($producto, "id_sku", 0));
        if ($slug === "" && $idPublicacion <= 0 && $idSku <= 0) { continue; }
        $stmt->execute(array(
          ":id_video" => intval($idVideo),
          ":id_publicacion" => $idPublicacion > 0 ? $idPublicacion : null,
          ":id_sku" => $idSku > 0 ? $idSku : null,
          ":slug_producto" => $slug,
          ":producto_principal" => intval($this->valor($producto, "producto_principal", 0)) === 1 ? 1 : 0,
          ":orden" => intval($this->valor($producto, "orden", $orden))
        ));
        $orden++;
        $resumen["productos"]++;
      }
    }

    if ($this->tablaExiste("erp_ecommerce_video_categoria")) {
      $db->prepare("DELETE FROM erp_ecommerce_video_categoria WHERE id_video=:id")->execute(array(":id" => intval($idVideo)));
      $categorias = $this->jsonDesdeEntrada($this->valor($datos, "categorias_json", array()));
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_video_categoria (id_video, categoria_path_slug, url_categoria, orden) VALUES (:id_video, :categoria_path_slug, :url_categoria, :orden)");
      $orden = 0;
      foreach ($categorias as $categoria) {
        if (!is_array($categoria)) { continue; }
        $path = $this->slugPath($this->valor($categoria, "categoria_path_slug", $this->valor($categoria, "path_slug", "")));
        if ($path === "") { continue; }
        $url = trim((string) $this->valor($categoria, "url_categoria", "/categoria/" . $path));
        $stmt->execute(array(
          ":id_video" => intval($idVideo),
          ":categoria_path_slug" => $path,
          ":url_categoria" => $url,
          ":orden" => intval($this->valor($categoria, "orden", $orden))
        ));
        $orden++;
        $resumen["categorias"]++;
      }
    }
    return $resumen;
  }

  private function filaVideoAdmin($id) {
    if ($id <= 0 || !$this->tablaExiste("erp_ecommerce_videos")) { return null; }
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_videos WHERE id_video=:id LIMIT 1");
    $stmt->execute(array(":id" => intval($id)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function productosVideo($idVideo) {
    if (!$this->tablaExiste("erp_ecommerce_video_producto")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_video_producto WHERE id_video=:id ORDER BY producto_principal DESC, orden ASC, id_video_producto ASC");
    $stmt->execute(array(":id" => intval($idVideo)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function categoriasVideo($idVideo) {
    if (!$this->tablaExiste("erp_ecommerce_video_categoria")) { return array(); }
    $stmt = $this->getConexion()->prepare("SELECT * FROM erp_ecommerce_video_categoria WHERE id_video=:id ORDER BY orden ASC, id_video_categoria ASC");
    $stmt->execute(array(":id" => intval($idVideo)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function formatearResumenVideo($fila) {
    return array(
      "id" => intval($fila["id_video"]),
      "titulo" => (string) $fila["titulo"],
      "slug" => (string) $fila["slug"],
      "url" => "/videos/" . (string) $fila["slug"],
      "tipo_video" => (string) $fila["tipo_video"],
      "estado" => (string) $fila["estado"],
      "descripcion_corta" => (string) $fila["descripcion_corta"],
      "copy_tiktok" => (string) $this->valor($fila, "copy_tiktok", ""),
      "hashtags" => $this->normalizarHashtags($this->valor($fila, "hashtags", "")),
      "thumbnail" => array("url" => (string) $fila["thumbnail_url"], "alt" => (string) $fila["thumbnail_alt"]),
      "video" => array(
        "provider" => "tiktok",
        "tiktok_post_id" => (string) $this->valor($fila, "tiktok_post_id", ""),
        "tiktok_author" => (string) $this->valor($fila, "tiktok_author", ""),
        "url" => (string) $fila["video_url"],
        "embed_url" => (string) $fila["embed_url"]
      ),
      "destacado" => intval($fila["destacado"]) === 1,
      "fecha_publicacion" => $this->fechaPublica($this->valor($fila, "fecha_publicacion", ""))
    );
  }

  private function formatearDetalleVideo($fila) {
    $item = $this->formatearResumenVideo($fila);
    $item["descripcion_larga"] = (string) $this->valor($fila, "descripcion_larga", "");
    $item["texto_busqueda"] = (string) $this->valor($fila, "texto_busqueda", "");
    $item["orientacion"] = (string) $this->valor($fila, "orientacion", "vertical");
    $item["duracion_segundos"] = intval($this->valor($fila, "duracion_segundos", 0));
    $item["orden"] = intval($this->valor($fila, "orden", 0));
    $item["seo"] = array(
      "title" => (string) $this->valor($fila, "seo_title", ""),
      "description" => (string) $this->valor($fila, "seo_description", ""),
      "canonical" => (string) $this->valor($fila, "seo_canonical", ""),
      "og_image" => (string) $this->valor($fila, "og_image", "")
    );
    $item["metadata"] = $this->jsonDesdeEntrada($this->valor($fila, "metadata_json", array()));
    return $item;
  }

  private function bloqueosPublicacion($fila) {
    $bloqueos = array();
    foreach (array("titulo", "slug", "video_url", "embed_url", "thumbnail_url", "thumbnail_alt", "tiktok_post_id") as $campo) {
      if (trim((string) $this->valor($fila, $campo, "")) === "") {
        $bloqueos[] = $campo;
      }
    }
    if (!$this->urlTikTokValida($this->valor($fila, "video_url", ""))) {
      $bloqueos[] = "video_url_tiktok";
    }
    return array_values(array_unique($bloqueos));
  }

  private function extraerTikTok($url) {
    $salida = array("post_id" => "", "author" => "");
    $url = trim((string) $url);
    if ($url === "") { return $salida; }
    if (preg_match('~tiktok\.com/@([^/]+)/video/([0-9]+)~i', $url, $m)) {
      $salida["author"] = $this->slugSimple($m[1]);
      $salida["post_id"] = $m[2];
      return $salida;
    }
    if (preg_match('~/video/([0-9]+)~i', $url, $m)) {
      $salida["post_id"] = $m[1];
      return $salida;
    }
    if (preg_match('~/v/([0-9]+)~i', $url, $m)) {
      $salida["post_id"] = $m[1];
      return $salida;
    }
    return $salida;
  }

  private function embedTikTok($postId) {
    $postId = preg_replace('/[^0-9]/', '', (string) $postId);
    return $postId !== "" ? "https://www.tiktok.com/player/v1/" . $postId . "?autoplay=0&description=0" : "";
  }

  private function urlTikTokValida($url) {
    $url = trim((string) $url);
    return preg_match('~^https://(www\.)?tiktok\.com/.+~i', $url) === 1 || preg_match('~^https://vm\.tiktok\.com/.+~i', $url) === 1 || preg_match('~^https://vt\.tiktok\.com/.+~i', $url) === 1;
  }

  private function tipoNormalizado($tipo) {
    $tipo = $this->slugSimple($tipo);
    return in_array($tipo, $this->tiposPermitidos, true) ? $tipo : "";
  }

  private function orientacionNormalizada($valor) {
    $valor = $this->slugSimple($valor);
    return in_array($valor, array("vertical", "horizontal", "cuadrado"), true) ? $valor : "vertical";
  }

  private function normalizarHashtags($valor) {
    if (is_array($valor)) {
      $partes = $valor;
    } else {
      $partes = preg_split('/[\s,]+/', (string) $valor);
    }
    $limpios = array();
    foreach ($partes as $parte) {
      $tag = trim((string) $parte);
      if ($tag === "") { continue; }
      $tag = ltrim($tag, "#");
      $tag = preg_replace('/[^A-Za-z0-9_]/', '', $tag);
      if ($tag !== "") { $limpios[] = "#" . $tag; }
    }
    return implode(" ", array_values(array_unique($limpios)));
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

  private function slugSimple($valor) {
    $valor = strtolower(trim((string) $valor));
    $valor = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $valor);
    $valor = preg_replace('/[^a-z0-9]+/', "-", $valor);
    return trim($valor, "-");
  }

  private function slugPath($valor) {
    $partes = preg_split('~/+~', trim((string) $valor, "/"));
    $salida = array();
    foreach ($partes as $parte) {
      $slug = $this->slugSimple($parte);
      if ($slug !== "") { $salida[] = $slug; }
    }
    return implode("/", $salida);
  }

  private function jsonDesdeEntrada($valor) {
    if (is_array($valor)) { return $valor; }
    $valor = trim((string) $valor);
    if ($valor === "") { return array(); }
    $json = json_decode($valor, true);
    return is_array($json) ? $json : array();
  }

  private function paginacion($pagina, $limite, $total) {
    return array("pagina" => $pagina, "limite" => $limite, "total" => $total, "total_paginas" => $limite > 0 ? intval(ceil($total / $limite)) : 0);
  }

  private function schemaDisponible() {
    return $this->tablaExiste("erp_ecommerce_videos") && $this->tablaExiste("erp_ecommerce_video_producto") && $this->tablaExiste("erp_ecommerce_video_categoria");
  }

  private function tablaExiste($tabla) {
    $db = $this->getConexion();
    if (!$db) { return false; }
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
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

  private function guardrails() {
    return array(
      "provider_unico_tiktok" => true,
      "no_subir_video_local" => true,
      "no_descargar_video" => true,
      "thumbnail_obligatorio" => true,
      "iframe_solo_en_detalle_y_hasta_click" => true,
      "no_toca_catalogo_precios_inventario" => true,
      "requiere_ddl_autorizado_para_persistir" => true
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "depurar" => $depurar);
  }
}
