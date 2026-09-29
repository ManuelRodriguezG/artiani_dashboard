<?php

class EcommerceVideosErp extends CRUD {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar listado publico de videos ecommerce.
   * Impacto: Frontend ecommerce; habilita /videos con thumbnails y CTAs sin iframe inicial.
   * Contrato: GET read-only; usa fixture controlado mientras no exista tabla real.
   */
  public function videosPublicos($params = array()) {
    $pagina = isset($params["pagina"]) ? max(1, intval($params["pagina"])) : 1;
    $limite = isset($params["limite"]) ? max(1, min(24, intval($params["limite"]))) : 12;
    $tipo = isset($params["tipo"]) ? trim((string) $params["tipo"]) : "";
    $categoria = isset($params["categoria"]) ? trim((string) $params["categoria"]) : "";
    $destacado = isset($params["destacado"]) ? trim((string) $params["destacado"]) : "";
    $offset = ($pagina - 1) * $limite;

    if ($this->schemaCompletoVideos()) {
      $filtros = array("tipo" => $tipo, "categoria" => $categoria, "destacado" => $destacado);
      $itemsPagina = $this->videosDb($filtros, $limite, $offset);
      $total = $this->videosDbTotal($filtros);
      return $this->respuesta(false, "success", "Videos ecommerce consultados", array(
        "ok" => true,
        "fuente" => "bd_videos",
        "configurado" => true,
        "items" => array_map(array($this, "cardVideo"), $itemsPagina),
        "paginacion" => $this->paginacion($pagina, $limite, $total, "/ecommercePublico/videos"),
        "frontend" => array(
          "ruta_publica" => "/videos",
          "no_cargar_iframe_en_listado" => true,
          "thumbnail_obligatorio" => true,
          "cta_principal" => "Ver video"
        ),
        "guardrails" => $this->guardrails()
      ));
    }

    $items = $this->filtrarItems($this->fixtureVideos(), array(
      "tipo" => $tipo,
      "categoria" => $categoria,
      "destacado" => $destacado
    ));

    $total = count($items);
    $itemsPagina = array_slice($items, $offset, $limite);

    return $this->respuesta(false, "success", "Videos ecommerce consultados", array(
      "ok" => true,
      "fuente" => "fixture_contrato",
      "configurado" => $this->tablaExiste("erp_ecommerce_videos"),
      "items" => array_map(array($this, "cardVideo"), $itemsPagina),
      "paginacion" => $this->paginacion($pagina, $limite, $total, "/ecommercePublico/videos"),
      "frontend" => array(
        "ruta_publica" => "/videos",
        "no_cargar_iframe_en_listado" => true,
        "thumbnail_obligatorio" => true,
        "cta_principal" => "Ver video"
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar detalle publico de un video ecommerce.
   * Impacto: Frontend ecommerce; habilita /videos/{slug} con producto principal, relacionados y SEO.
   * Contrato: GET read-only; el player se carga hasta accion del usuario.
   */
  public function videoDetallePublico($slug) {
    $slug = $this->slugSeguro($slug);
    if ($this->schemaCompletoVideos()) {
      $itemDb = $this->videoDbPorSlug($slug);
      if ($itemDb) {
        return $this->respuesta(false, "success", "Video ecommerce consultado", array(
          "ok" => true,
          "fuente" => "bd_videos",
          "configurado" => true,
          "item" => $itemDb,
          "frontend" => array(
            "ruta_publica" => "/videos/" . $slug,
            "cargar_player_hasta_click" => true,
            "usar_thumbnail_como_placeholder" => true,
            "cta_whatsapp_contextual" => true
          ),
          "guardrails" => $this->guardrails()
        ));
      }
    }

    foreach ($this->fixtureVideos() as $item) {
      if ($item["slug"] === $slug) {
        return $this->respuesta(false, "success", "Video ecommerce consultado", array(
          "ok" => true,
          "fuente" => "fixture_contrato",
          "configurado" => $this->tablaExiste("erp_ecommerce_videos"),
          "item" => $item,
          "frontend" => array(
            "ruta_publica" => "/videos/" . $slug,
            "cargar_player_hasta_click" => true,
            "usar_thumbnail_como_placeholder" => true,
            "cta_whatsapp_contextual" => true
          ),
          "guardrails" => $this->guardrails()
        ));
      }
    }

    return $this->respuesta(true, "warning", "Video no encontrado", array(
      "ok" => false,
      "slug" => $slug,
      "items" => array(),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar videos relacionados a una ficha de producto.
   * Impacto: Frontend ecommerce; habilita seccion "Videos de este producto" en /producto/{slug}.
   * Contrato: GET read-only; devuelve cards compactas sin iframe.
   */
  public function videosProductoPublico($slugProducto, $params = array()) {
    $slugProducto = $this->slugSeguro($slugProducto);
    $limite = isset($params["limite"]) ? max(1, min(12, intval($params["limite"]))) : 6;
    if ($this->schemaCompletoVideos()) {
      $itemsDb = $this->videosDbPorProducto($slugProducto, $limite);
      return $this->respuesta(false, "success", "Videos del producto consultados", array(
        "ok" => true,
        "fuente" => "bd_videos",
        "configurado" => true,
        "slug_producto" => $slugProducto,
        "items" => array_map(array($this, "cardVideo"), $itemsDb),
        "frontend" => array(
          "seccion_titulo" => "Videos de este producto",
          "usar_cards_compactas" => true,
          "no_cargar_iframe" => true,
          "link_detalle_video" => true
        ),
        "guardrails" => $this->guardrails()
      ));
    }

    $items = array();
    foreach ($this->fixtureVideos() as $item) {
      $principal = isset($item["producto_principal"]["slug"]) ? $item["producto_principal"]["slug"] : "";
      if ($principal === $slugProducto) {
        $items[] = $item;
      }
    }

    return $this->respuesta(false, "success", "Videos del producto consultados", array(
      "ok" => true,
      "fuente" => "fixture_contrato",
      "configurado" => $this->tablaExiste("erp_ecommerce_videos"),
      "slug_producto" => $slugProducto,
      "items" => array_map(array($this, "cardVideo"), array_slice($items, 0, $limite)),
      "frontend" => array(
        "seccion_titulo" => "Videos de este producto",
        "usar_cards_compactas" => true,
        "no_cargar_iframe" => true,
        "link_detalle_video" => true
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar videos relacionados a una categoria publica.
   * Impacto: Frontend ecommerce; prepara seccion futura "Videos de esta categoria".
   * Contrato: GET read-only; categoria se usa como navegacion visual, no como perfil de mascota.
   */
  public function videosCategoriaPublica($pathSlug, $params = array()) {
    $pathSlug = trim((string) $pathSlug, "/");
    $limite = isset($params["limite"]) ? max(1, min(12, intval($params["limite"]))) : 6;
    if ($this->schemaCompletoVideos()) {
      $itemsDb = $this->videosDbPorCategoria($pathSlug, $limite);
      return $this->respuesta(false, "success", "Videos de categoria consultados", array(
        "ok" => true,
        "fuente" => "bd_videos",
        "configurado" => true,
        "categoria_path_slug" => $pathSlug,
        "items" => array_map(array($this, "cardVideo"), $itemsDb),
        "frontend" => array(
          "seccion_titulo" => "Videos de esta categoria",
          "fase" => "posterior",
          "usar_url_categoria_api" => true,
          "no_personalizacion_mascotas" => true
        ),
        "guardrails" => $this->guardrails()
      ));
    }

    $items = array();

    foreach ($this->fixtureVideos() as $item) {
      foreach ($item["categorias_relacionadas"] as $categoria) {
        if (isset($categoria["path_slug"]) && $categoria["path_slug"] === $pathSlug) {
          $items[] = $item;
          break;
        }
      }
    }

    return $this->respuesta(false, "success", "Videos de categoria consultados", array(
      "ok" => true,
      "fuente" => "fixture_contrato",
      "configurado" => $this->tablaExiste("erp_ecommerce_videos"),
      "categoria_path_slug" => $pathSlug,
      "items" => array_map(array($this, "cardVideo"), array_slice($items, 0, $limite)),
      "frontend" => array(
        "seccion_titulo" => "Videos de esta categoria",
        "fase" => "posterior",
        "usar_url_categoria_api" => true,
        "no_personalizacion_mascotas" => true
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: exponer manifest publico del modulo de videos.
   * Impacto: Frontend ecommerce; centraliza endpoints, tipos y eventos sin leer docs internos.
   * Contrato: GET read-only; no escribe BD.
   */
  public function videosManifestPublico($params = array()) {
    return $this->respuesta(false, "success", "Manifest videos ecommerce", array(
      "api" => $this->apiMeta(),
      "rutas_publicas_frontend" => array("/videos", "/videos/{slug}", "/producto/{slug}", "/categoria/{path_slug}"),
      "endpoints" => array(
        array("metodo" => "GET", "ruta" => "/ecommercePublico/videos", "uso" => "Listado publico de videos."),
        array("metodo" => "GET", "ruta" => "/ecommercePublico/videos/{slug}", "uso" => "Detalle publico de video."),
        array("metodo" => "GET", "ruta" => "/ecommercePublico/producto/{slug}/videos", "uso" => "Videos relacionados a producto."),
        array("metodo" => "GET", "ruta" => "/ecommercePublico/categoria/{path_slug}/videos", "uso" => "Videos relacionados a categoria.")
      ),
      "tipos_video" => $this->tiposVideo(),
      "analytics_eventos" => array("video_view", "video_play", "video_producto_click", "video_add_to_cart", "video_categoria_click", "video_whatsapp_click"),
      "frontend" => array(
        "listados_solo_thumbnail" => true,
        "detalle_carga_player_hasta_click" => true,
        "usar_cards_producto_existentes" => true,
        "seo_video_object" => true
      ),
      "guardrails" => $this->guardrails()
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: validar eventos publicos del modulo Videos.
   * Impacto: Frontend ecommerce; permite instrumentar video_view/play/clicks sin requerir persistencia inmediata.
   * Contrato: POST publico limitado; no escribe BD mientras no exista tabla de analytics autorizada.
   */
  public function registrarAnalyticsEvento($payload, $request = array()) {
    $eventos = array("video_view", "video_play", "video_producto_click", "video_add_to_cart", "video_categoria_click", "video_whatsapp_click");
    $evento = trim((string) ($payload["evento"] ?? ""));
    if (!in_array($evento, $eventos, true)) {
      return $this->respuesta(true, "warning", "Evento de video no permitido", array("ok" => false, "eventos_permitidos" => $eventos));
    }
    return $this->respuesta(false, "info", "Evento de video recibido sin persistencia activa", array(
      "ok" => true,
      "persistido" => false,
      "evento" => $evento,
      "referencia_tipo" => trim((string) ($payload["referencia_tipo"] ?? "video")),
      "referencia_slug" => $this->slugSeguro($payload["referencia_slug"] ?? ""),
      "requiere_schema_analytics" => true,
      "guardrails" => array(
        "no_escribe_bd" => true,
        "no_datos_personales" => true,
        "no_ventas" => true,
        "no_inventario" => true
      )
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: auditar disponibilidad de esquema de videos sin aplicar DDL.
   * Impacto: Ecommerce videos; permite preparar activacion futura con respaldo y autorizacion.
   * Contrato: solo lectura sobre INFORMATION_SCHEMA.
   */
  public function esquemaAuditarVideos() {
    $requerido = $this->schemaRequeridoVideos();
    $tablas = array();
    $tablasFaltantes = 0;
    $columnasFaltantesTotal = 0;
    $indicesFaltantesTotal = 0;

    foreach ($requerido as $tabla => $definicion) {
      $existe = $this->tablaExiste($tabla);
      $columnasFaltantes = array();
      $indicesFaltantes = array();

      if ($existe) {
        foreach ($definicion["columnas"] as $columna) {
          if (!$this->columnaExiste($tabla, $columna)) {
            $columnasFaltantes[] = $columna;
          }
        }
        foreach ($definicion["indices"] as $indice) {
          if (!$this->indiceExiste($tabla, $indice)) {
            $indicesFaltantes[] = $indice;
          }
        }
      } else {
        $tablasFaltantes++;
        $columnasFaltantes = $definicion["columnas"];
        $indicesFaltantes = $definicion["indices"];
      }

      $columnasFaltantesTotal += count($columnasFaltantes);
      $indicesFaltantesTotal += count($indicesFaltantes);
      $tablas[$tabla] = array(
        "existe" => $existe,
        "columnas_requeridas" => $definicion["columnas"],
        "columnas_faltantes" => $columnasFaltantes,
        "indices_requeridos" => $definicion["indices"],
        "indices_faltantes" => $indicesFaltantes
      );
    }

    return $this->respuesta(false, "success", "Auditoria de esquema videos ecommerce", array(
      "ok" => true,
      "tablas" => $tablas,
      "tablas_faltantes" => $tablasFaltantes,
      "columnas_faltantes_total" => $columnasFaltantesTotal,
      "indices_faltantes_total" => $indicesFaltantesTotal,
      "completo" => $tablasFaltantes === 0 && $columnasFaltantesTotal === 0 && $indicesFaltantesTotal === 0,
      "no_ejecuta_ddl" => true,
      "requiere_autorizacion_para_apply" => true
    ));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: planear esquema de videos ecommerce sin ejecutarlo.
   * Impacto: Ecommerce videos; documenta DDL futuro y bloquea cambios no autorizados.
   * Contrato: read-only; devuelve SQL sugerido, no escribe BD.
   */
  public function esquemaPlanVideos() {
    $auditoria = $this->esquemaAuditarVideos();
    $sql = $this->ddlPlanVideos();
    return $this->respuesta(false, "info", "Plan de esquema videos ecommerce", array(
      "ok" => true,
      "no_ejecuta_ddl" => true,
      "requiere_respaldo_externo" => true,
      "token_sugerido" => "ECOMMERCE_VIDEOS_DDL",
      "auditoria" => $auditoria["depurar"],
      "sql_plan" => $sql,
      "orden_aplicacion" => array_keys($sql),
      "guardrails" => array(
        "no_ejecutar_desde_chat_sin_autorizacion" => true,
        "requiere_respaldo_en_C_xampp_panel_db_backups" => true,
        "no_crea_ventas" => true,
        "no_toca_inventario" => true,
        "no_toca_publicaciones_existentes" => true
      ),
      "documento" => "docs/erp_ecommerce_videos_plan.md"
    ));
  }

  private function schemaRequeridoVideos() {
    return array(
      "erp_ecommerce_videos" => array(
        "columnas" => array(
          "id_video", "titulo", "slug", "descripcion_corta", "descripcion_larga", "tipo_video", "provider", "tiktok_post_id", "tiktok_author", "video_url", "embed_url", "copy_tiktok", "hashtags", "texto_busqueda", "thumbnail_url", "thumbnail_alt", "orientacion", "duracion_segundos", "estado", "fecha_publicacion", "orden", "destacado", "seo_title", "seo_description", "seo_canonical", "og_image", "metadata_json", "created_at", "updated_at", "creado_por", "actualizado_por"
        ),
        "indices" => array("PRIMARY", "uq_erp_ecommerce_videos_slug", "idx_erp_ecommerce_videos_estado_fecha", "idx_erp_ecommerce_videos_tipo", "idx_erp_ecommerce_videos_destacado", "idx_erp_ecommerce_videos_tiktok_post")
      ),
      "erp_ecommerce_video_producto" => array(
        "columnas" => array("id_video_producto", "id_video", "id_publicacion", "id_sku", "slug_producto", "producto_principal", "orden", "created_at"),
        "indices" => array("PRIMARY", "idx_erp_ecommerce_video_producto_video", "idx_erp_ecommerce_video_producto_publicacion", "idx_erp_ecommerce_video_producto_sku", "idx_erp_ecommerce_video_producto_slug", "idx_erp_ecommerce_video_producto_principal")
      ),
      "erp_ecommerce_video_categoria" => array(
        "columnas" => array("id_video_categoria", "id_video", "categoria_path_slug", "url_categoria", "orden", "created_at"),
        "indices" => array("PRIMARY", "idx_erp_ecommerce_video_categoria_video", "idx_erp_ecommerce_video_categoria_path")
      )
    );
  }

  private function ddlPlanVideos() {
    return array(
      "erp_ecommerce_videos" => "CREATE TABLE `erp_ecommerce_videos` (\n" .
        "  `id_video` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
        "  `titulo` VARCHAR(180) NOT NULL,\n" .
        "  `slug` VARCHAR(220) NOT NULL,\n" .
        "  `descripcion_corta` VARCHAR(300) NOT NULL DEFAULT '',\n" .
        "  `descripcion_larga` TEXT NULL,\n" .
        "  `tipo_video` VARCHAR(40) NOT NULL,\n" .
        "  `provider` VARCHAR(40) NOT NULL DEFAULT 'tiktok',\n" .
        "  `tiktok_post_id` VARCHAR(80) NOT NULL DEFAULT '',\n" .
        "  `tiktok_author` VARCHAR(120) NOT NULL DEFAULT '',\n" .
        "  `video_url` VARCHAR(500) NOT NULL DEFAULT '',\n" .
        "  `embed_url` VARCHAR(500) NOT NULL DEFAULT '',\n" .
        "  `copy_tiktok` TEXT NULL,\n" .
        "  `hashtags` VARCHAR(500) NOT NULL DEFAULT '',\n" .
        "  `texto_busqueda` TEXT NULL,\n" .
        "  `thumbnail_url` VARCHAR(500) NOT NULL DEFAULT '',\n" .
        "  `thumbnail_alt` VARCHAR(220) NOT NULL DEFAULT '',\n" .
        "  `orientacion` VARCHAR(20) NOT NULL DEFAULT 'vertical',\n" .
        "  `duracion_segundos` INT UNSIGNED NOT NULL DEFAULT 0,\n" .
        "  `estado` VARCHAR(20) NOT NULL DEFAULT 'borrador',\n" .
        "  `fecha_publicacion` DATETIME NULL,\n" .
        "  `orden` INT NOT NULL DEFAULT 0,\n" .
        "  `destacado` TINYINT(1) NOT NULL DEFAULT 0,\n" .
        "  `seo_title` VARCHAR(220) NOT NULL DEFAULT '',\n" .
        "  `seo_description` VARCHAR(320) NOT NULL DEFAULT '',\n" .
        "  `seo_canonical` VARCHAR(260) NOT NULL DEFAULT '',\n" .
        "  `og_image` VARCHAR(500) NOT NULL DEFAULT '',\n" .
        "  `metadata_json` LONGTEXT NULL,\n" .
        "  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
        "  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,\n" .
        "  `creado_por` INT NULL,\n" .
        "  `actualizado_por` INT NULL,\n" .
        "  PRIMARY KEY (`id_video`),\n" .
        "  UNIQUE KEY `uq_erp_ecommerce_videos_slug` (`slug`),\n" .
        "  KEY `idx_erp_ecommerce_videos_estado_fecha` (`estado`, `fecha_publicacion`),\n" .
        "  KEY `idx_erp_ecommerce_videos_tipo` (`tipo_video`),\n" .
        "  KEY `idx_erp_ecommerce_videos_destacado` (`destacado`, `orden`),\n" .
        "  KEY `idx_erp_ecommerce_videos_tiktok_post` (`tiktok_post_id`)\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
      "erp_ecommerce_video_producto" => "CREATE TABLE `erp_ecommerce_video_producto` (\n" .
        "  `id_video_producto` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
        "  `id_video` INT UNSIGNED NOT NULL,\n" .
        "  `id_publicacion` INT NULL,\n" .
        "  `id_sku` INT NULL,\n" .
        "  `slug_producto` VARCHAR(220) NOT NULL DEFAULT '',\n" .
        "  `producto_principal` TINYINT(1) NOT NULL DEFAULT 0,\n" .
        "  `orden` INT NOT NULL DEFAULT 0,\n" .
        "  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
        "  PRIMARY KEY (`id_video_producto`),\n" .
        "  KEY `idx_erp_ecommerce_video_producto_video` (`id_video`),\n" .
        "  KEY `idx_erp_ecommerce_video_producto_publicacion` (`id_publicacion`),\n" .
        "  KEY `idx_erp_ecommerce_video_producto_sku` (`id_sku`),\n" .
        "  KEY `idx_erp_ecommerce_video_producto_slug` (`slug_producto`),\n" .
        "  KEY `idx_erp_ecommerce_video_producto_principal` (`id_video`, `producto_principal`),\n" .
        "  CONSTRAINT `fk_erp_ecommerce_video_producto_video` FOREIGN KEY (`id_video`) REFERENCES `erp_ecommerce_videos` (`id_video`) ON DELETE CASCADE\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
      "erp_ecommerce_video_categoria" => "CREATE TABLE `erp_ecommerce_video_categoria` (\n" .
        "  `id_video_categoria` INT UNSIGNED NOT NULL AUTO_INCREMENT,\n" .
        "  `id_video` INT UNSIGNED NOT NULL,\n" .
        "  `categoria_path_slug` VARCHAR(260) NOT NULL DEFAULT '',\n" .
        "  `url_categoria` VARCHAR(300) NOT NULL DEFAULT '',\n" .
        "  `orden` INT NOT NULL DEFAULT 0,\n" .
        "  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
        "  PRIMARY KEY (`id_video_categoria`),\n" .
        "  KEY `idx_erp_ecommerce_video_categoria_video` (`id_video`),\n" .
        "  KEY `idx_erp_ecommerce_video_categoria_path` (`categoria_path_slug`),\n" .
        "  CONSTRAINT `fk_erp_ecommerce_video_categoria_video` FOREIGN KEY (`id_video`) REFERENCES `erp_ecommerce_videos` (`id_video`) ON DELETE CASCADE\n" .
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
  }

  private function schemaCompletoVideos() {
    $auditoria = $this->esquemaAuditarVideos();
    return !empty($auditoria["depurar"]["completo"]);
  }

  private function videosDb($filtros, $limite, $offset) {
    $db = $this->getConexion();
    if (!$db) { return array(); }
    $where = array("v.estado='publicado'");
    $params = array(":limite" => intval($limite), ":offset" => intval($offset));
    $this->aplicarFiltrosVideosDb($where, $params, $filtros);

    $sql = "SELECT v.* FROM erp_ecommerce_videos v WHERE " . implode(" AND ", $where) .
      " ORDER BY v.destacado DESC, v.orden ASC, v.fecha_publicacion DESC, v.id_video DESC LIMIT :limite OFFSET :offset";
    $stmt = $db->prepare($sql);
    foreach ($params as $clave => $valor) {
      $stmt->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $items = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $items[] = $this->videoDesdeFilaDb($fila);
    }
    return $items;
  }

  private function videosDbTotal($filtros) {
    $db = $this->getConexion();
    if (!$db) { return 0; }
    $where = array("v.estado='publicado'");
    $params = array();
    $this->aplicarFiltrosVideosDb($where, $params, $filtros);
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_videos v WHERE " . implode(" AND ", $where));
    foreach ($params as $clave => $valor) {
      $stmt->bindValue($clave, $valor, is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    return intval($stmt->fetchColumn());
  }

  private function aplicarFiltrosVideosDb(&$where, &$params, $filtros) {
    if (($filtros["tipo"] ?? "") !== "") {
      $where[] = "v.tipo_video=:tipo_video";
      $params[":tipo_video"] = $filtros["tipo"];
    }
    if (($filtros["destacado"] ?? "") === "1") {
      $where[] = "v.destacado=1";
    }
    if (($filtros["categoria"] ?? "") !== "") {
      $where[] = "EXISTS (SELECT 1 FROM erp_ecommerce_video_categoria vc WHERE vc.id_video=v.id_video AND vc.categoria_path_slug=:categoria_path)";
      $params[":categoria_path"] = $filtros["categoria"];
    }
  }

  private function videoDbPorSlug($slug) {
    $db = $this->getConexion();
    if (!$db || $slug === "") { return null; }
    $stmt = $db->prepare("SELECT * FROM erp_ecommerce_videos WHERE slug=:slug AND estado='publicado' LIMIT 1");
    $stmt->execute(array(":slug" => $slug));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $this->videoDesdeFilaDb($fila, true) : null;
  }

  private function videosDbPorProducto($slugProducto, $limite) {
    $db = $this->getConexion();
    if (!$db || $slugProducto === "") { return array(); }
    $stmt = $db->prepare("SELECT DISTINCT v.*
      FROM erp_ecommerce_videos v
      INNER JOIN erp_ecommerce_video_producto vp ON vp.id_video=v.id_video
      WHERE v.estado='publicado' AND vp.slug_producto=:slug
      ORDER BY vp.producto_principal DESC, vp.orden ASC, v.destacado DESC, v.orden ASC, v.fecha_publicacion DESC
      LIMIT :limite");
    $stmt->bindValue(":slug", $slugProducto, PDO::PARAM_STR);
    $stmt->bindValue(":limite", intval($limite), PDO::PARAM_INT);
    $stmt->execute();
    $items = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $items[] = $this->videoDesdeFilaDb($fila);
    }
    return $items;
  }

  private function videosDbPorCategoria($pathSlug, $limite) {
    $db = $this->getConexion();
    if (!$db || $pathSlug === "") { return array(); }
    $stmt = $db->prepare("SELECT DISTINCT v.*
      FROM erp_ecommerce_videos v
      INNER JOIN erp_ecommerce_video_categoria vc ON vc.id_video=v.id_video
      WHERE v.estado='publicado' AND vc.categoria_path_slug=:path
      ORDER BY vc.orden ASC, v.destacado DESC, v.orden ASC, v.fecha_publicacion DESC
      LIMIT :limite");
    $stmt->bindValue(":path", $pathSlug, PDO::PARAM_STR);
    $stmt->bindValue(":limite", intval($limite), PDO::PARAM_INT);
    $stmt->execute();
    $items = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $items[] = $this->videoDesdeFilaDb($fila);
    }
    return $items;
  }

  private function videoDesdeFilaDb($fila, $detalle = false) {
    $idVideo = intval($fila["id_video"]);
    $productoPrincipal = $this->productoPrincipalVideoDb($idVideo);
    return array(
      "id" => $idVideo,
      "titulo" => (string) $fila["titulo"],
      "slug" => (string) $fila["slug"],
      "tipo_video" => (string) $fila["tipo_video"],
      "descripcion_corta" => (string) $fila["descripcion_corta"],
      "descripcion_larga" => (string) ($fila["descripcion_larga"] ?? ""),
      "copy_tiktok" => (string) ($fila["copy_tiktok"] ?? ""),
      "hashtags" => $this->hashtagsDesdeTexto((string) ($fila["hashtags"] ?? "")),
      "texto_busqueda" => (string) ($fila["texto_busqueda"] ?? ""),
      "thumbnail" => array("url" => (string) $fila["thumbnail_url"], "alt" => (string) $fila["thumbnail_alt"]),
      "video" => array(
        "provider" => (string) $fila["provider"],
        "tiktok_post_id" => (string) ($fila["tiktok_post_id"] ?? ""),
        "tiktok_author" => (string) ($fila["tiktok_author"] ?? ""),
        "url" => (string) $fila["video_url"],
        "embed_url" => (string) $fila["embed_url"],
        "duracion_segundos" => intval($fila["duracion_segundos"]),
        "orientacion" => (string) $fila["orientacion"]
      ),
      "producto_principal" => $productoPrincipal,
      "productos_relacionados" => $detalle ? $this->productosRelacionadosVideoDb($idVideo) : array(),
      "categorias_relacionadas" => $this->categoriasVideoDb($idVideo),
      "videos_relacionados" => $detalle ? $this->videosRelacionadosDb($idVideo, $fila["slug"]) : array(),
      "destacado" => intval($fila["destacado"]) === 1,
      "seo" => array(
        "title" => (string) ($fila["seo_title"] ?: ($fila["titulo"] . " | Artiani")),
        "description" => (string) ($fila["seo_description"] ?: $fila["descripcion_corta"]),
        "canonical" => (string) ($fila["seo_canonical"] ?: ("/videos/" . $fila["slug"])),
        "robots" => "index,follow",
        "og_image" => (string) ($fila["og_image"] ?: $fila["thumbnail_url"])
      )
    );
  }

  private function productoPrincipalVideoDb($idVideo) {
    $productos = $this->productosVideoDb($idVideo, true);
    return !empty($productos) ? $productos[0] : array();
  }

  private function productosRelacionadosVideoDb($idVideo) {
    return $this->productosVideoDb($idVideo, false);
  }

  private function productosVideoDb($idVideo, $soloPrincipal) {
    $db = $this->getConexion();
    if (!$db) { return array(); }
    $joinPublicaciones = $this->tablaExiste("erp_ecommerce_publicaciones");
    $sql = "SELECT vp.id_publicacion, vp.id_sku, vp.slug_producto, vp.producto_principal, vp.orden" .
      ($joinPublicaciones ? ", pub.slug slug_publicacion, pub.titulo_publico" : ", NULL slug_publicacion, NULL titulo_publico") .
      " FROM erp_ecommerce_video_producto vp " .
      ($joinPublicaciones ? "LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_publicacion=vp.id_publicacion" : "") .
      " WHERE vp.id_video=:id" . ($soloPrincipal ? " AND vp.producto_principal=1" : "") .
      " ORDER BY vp.producto_principal DESC, vp.orden ASC, vp.id_video_producto ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":id" => intval($idVideo)));
    $items = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $slug = trim((string) ($fila["slug_publicacion"] ?: $fila["slug_producto"]));
      $items[] = array(
        "id_publicacion" => intval($fila["id_publicacion"]),
        "id_sku" => intval($fila["id_sku"]),
        "slug" => $slug,
        "titulo" => trim((string) ($fila["titulo_publico"] ?: $slug)),
        "url" => $slug !== "" ? "/producto/" . $slug : "",
        "producto_principal" => intval($fila["producto_principal"]) === 1
      );
    }
    return $items;
  }

  private function categoriasVideoDb($idVideo) {
    $db = $this->getConexion();
    if (!$db) { return array(); }
    $stmt = $db->prepare("SELECT categoria_path_slug, url_categoria, orden FROM erp_ecommerce_video_categoria WHERE id_video=:id ORDER BY orden ASC, id_video_categoria ASC");
    $stmt->execute(array(":id" => intval($idVideo)));
    $items = array();
    while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $items[] = array(
        "nombre" => (string) $fila["categoria_path_slug"],
        "path_slug" => (string) $fila["categoria_path_slug"],
        "url_categoria" => (string) $fila["url_categoria"]
      );
    }
    return $items;
  }

  private function videosRelacionadosDb($idVideo, $slugActual) {
    $db = $this->getConexion();
    if (!$db) { return array(); }
    $stmt = $db->prepare("SELECT slug FROM erp_ecommerce_videos WHERE estado='publicado' AND id_video<>:id ORDER BY destacado DESC, orden ASC, fecha_publicacion DESC LIMIT 6");
    $stmt->execute(array(":id" => intval($idVideo)));
    $items = array();
    while ($slug = $stmt->fetchColumn()) {
      if ($slug !== $slugActual) {
        $items[] = (string) $slug;
      }
    }
    return $items;
  }

  private function fixtureVideos() {
    $baseThumb = "https://artiani.com.mx/assets/fixtures/videos/";
    $baseThumbLocal = "/assets/fixtures/videos/";
    return array(
      array(
        "id" => 100,
        "titulo" => "Video TikTok de prueba - articulos para animales",
        "slug" => "tiktok-articulos-para-animales-7526057146073566471",
        "tipo_video" => "demo_producto",
        "descripcion_corta" => "Video real de TikTok cargado como primera prueba para el frontend.",
        "descripcion_larga" => "Registro temporal para validar el modulo publico de videos con un enlace TikTok real. La miniatura es local de prueba y debe reemplazarse por una miniatura comercial final antes de publicar en produccion.",
        "copy_tiktok" => "Video compartido desde la cuenta articulos_para_animales para validar el flujo de videos Artiani con carga diferida del player TikTok.",
        "hashtags" => array("mascotas", "articulosparamascotas", "tiktok", "artiani"),
        "texto_busqueda" => "tiktok articulos para animales mascotas prueba frontend videos artiani demo producto",
        "thumbnail" => array("url" => $baseThumbLocal . "tiktok-articulos-para-animales-7526057146073566471.svg", "alt" => "Miniatura temporal de video TikTok de prueba"),
        "video" => array("provider" => "tiktok", "tiktok_post_id" => "7526057146073566471", "tiktok_author" => "articulos_para_animales", "url" => "https://www.tiktok.com/@articulos_para_animales/video/7526057146073566471", "embed_url" => "https://www.tiktok.com/player/v1/7526057146073566471?autoplay=0&description=0", "duracion_segundos" => 0, "orientacion" => "vertical"),
        "producto_principal" => array(),
        "productos_relacionados" => array(),
        "categorias_relacionadas" => array(array("nombre" => "Videos de prueba", "path_slug" => "videos/pruebas", "url_categoria" => "/categoria/videos/pruebas")),
        "videos_relacionados" => array("filtro-cascada-sunny-shf-600-funcionamiento"),
        "destacado" => true,
        "seo" => array("title" => "Video TikTok de prueba | Artiani", "description" => "Video real de TikTok para validar el modulo publico de videos Artiani.", "canonical" => "/videos/tiktok-articulos-para-animales-7526057146073566471", "robots" => "noindex,follow", "og_image" => $baseThumbLocal . "tiktok-articulos-para-animales-7526057146073566471.svg")
      ),
      array(
        "id" => 1,
        "titulo" => "Filtro de cascada SUNNY SHF-600 en funcionamiento",
        "slug" => "filtro-cascada-sunny-shf-600-funcionamiento",
        "tipo_video" => "demo_producto",
        "descripcion_corta" => "Vista rapida del flujo y montaje del filtro.",
        "descripcion_larga" => "Demo para mostrar flujo, tamano y montaje basico antes de comprar.",
        "copy_tiktok" => "Mira el flujo real del filtro de cascada SUNNY SHF-600 antes de comprarlo. Ideal para revisar movimiento de agua y montaje.",
        "hashtags" => array("acuario", "filtrodeacuario", "artiani", "mascotas"),
        "texto_busqueda" => "filtro cascada sunny shf 600 funcionamiento flujo real acuario instalacion montaje",
        "thumbnail" => array("url" => $baseThumb . "filtro-cascada-sunny-shf-600.jpg", "alt" => "Filtro de cascada funcionando"),
        "video" => array("provider" => "tiktok", "tiktok_post_id" => "7400000000000000001", "tiktok_author" => "artiani", "url" => "https://www.tiktok.com/@artiani/video/7400000000000000001", "embed_url" => "https://www.tiktok.com/player/v1/7400000000000000001?autoplay=0&description=0", "duracion_segundos" => 45, "orientacion" => "vertical"),
        "producto_principal" => array("id_publicacion" => 1, "id_sku" => 415, "slug" => "filtro-cascada-sunny-shf-600", "titulo" => "Filtro de cascada SUNNY SHF-600", "url" => "/producto/filtro-cascada-sunny-shf-600"),
        "productos_relacionados" => array(),
        "categorias_relacionadas" => array(array("nombre" => "Filtros para acuario", "path_slug" => "peces/filtracion/filtros", "url_categoria" => "/categoria/peces/filtracion/filtros")),
        "videos_relacionados" => array("como-instalar-filtro-cascada-acuario"),
        "destacado" => true,
        "seo" => array("title" => "Filtro de cascada SUNNY SHF-600 en funcionamiento | Artiani", "description" => "Mira el flujo y montaje del filtro SUNNY SHF-600.", "canonical" => "/videos/filtro-cascada-sunny-shf-600-funcionamiento", "robots" => "index,follow", "og_image" => $baseThumb . "filtro-cascada-sunny-shf-600.jpg")
      ),
      array(
        "id" => 2,
        "titulo" => "Como instalar un filtro de cascada en acuario",
        "slug" => "como-instalar-filtro-cascada-acuario",
        "tipo_video" => "instalacion",
        "descripcion_corta" => "Pasos rapidos para colocar el filtro y revisar el flujo.",
        "descripcion_larga" => "Guia corta para ubicar el filtro, cargar material filtrante y confirmar que el flujo sea correcto.",
        "copy_tiktok" => "Instala tu filtro de cascada en pocos pasos y revisa que el flujo quede correcto.",
        "hashtags" => array("acuario", "instalacion", "filtrodeacuario", "artiani"),
        "texto_busqueda" => "como instalar filtro cascada acuario pasos flujo material filtrante",
        "thumbnail" => array("url" => $baseThumb . "instalar-filtro-cascada.jpg", "alt" => "Instalacion de filtro de cascada"),
        "video" => array("provider" => "tiktok", "tiktok_post_id" => "7400000000000000002", "tiktok_author" => "artiani", "url" => "https://www.tiktok.com/@artiani/video/7400000000000000002", "embed_url" => "https://www.tiktok.com/player/v1/7400000000000000002?autoplay=0&description=0", "duracion_segundos" => 38, "orientacion" => "vertical"),
        "producto_principal" => array("id_publicacion" => 1, "id_sku" => 415, "slug" => "filtro-cascada-sunny-shf-600", "titulo" => "Filtro de cascada SUNNY SHF-600", "url" => "/producto/filtro-cascada-sunny-shf-600"),
        "productos_relacionados" => array(),
        "categorias_relacionadas" => array(array("nombre" => "Filtros para acuario", "path_slug" => "peces/filtracion/filtros", "url_categoria" => "/categoria/peces/filtracion/filtros")),
        "videos_relacionados" => array("filtro-cascada-sunny-shf-600-funcionamiento"),
        "destacado" => false,
        "seo" => array("title" => "Como instalar un filtro de cascada | Artiani", "description" => "Aprende a colocar un filtro de cascada para acuario.", "canonical" => "/videos/como-instalar-filtro-cascada-acuario", "robots" => "index,follow", "og_image" => $baseThumb . "instalar-filtro-cascada.jpg")
      ),
      array(
        "id" => 3,
        "titulo" => "Tamano real de alimento para peces",
        "slug" => "tamano-real-alimento-peces",
        "tipo_video" => "consejo_rapido",
        "descripcion_corta" => "Mira el tamano del granulo antes de elegir alimento.",
        "descripcion_larga" => "Video corto para comparar textura y tamano de granulo segun el tipo de pez.",
        "copy_tiktok" => "Antes de comprar alimento para peces, revisa el tamano real del granulo.",
        "hashtags" => array("peces", "alimentopeces", "acuario", "artiani"),
        "texto_busqueda" => "alimento peces tamano real granulo textura acuario comida peces",
        "thumbnail" => array("url" => $baseThumb . "alimento-peces-tamano-real.jpg", "alt" => "Tamano real de alimento para peces"),
        "video" => array("provider" => "tiktok", "tiktok_post_id" => "7400000000000000003", "tiktok_author" => "artiani", "url" => "https://www.tiktok.com/@artiani/video/7400000000000000003", "embed_url" => "https://www.tiktok.com/player/v1/7400000000000000003?autoplay=0&description=0", "duracion_segundos" => 22, "orientacion" => "vertical"),
        "producto_principal" => array("id_publicacion" => 2, "id_sku" => 1759, "slug" => "alimento-churro-blanco-peces-100-gr", "titulo" => "Alimento churro blanco para peces 100 gr", "url" => "/producto/alimento-churro-blanco-peces-100-gr"),
        "productos_relacionados" => array(),
        "categorias_relacionadas" => array(array("nombre" => "Alimento para peces", "path_slug" => "peces/alimento", "url_categoria" => "/categoria/peces/alimento")),
        "videos_relacionados" => array(),
        "destacado" => true,
        "seo" => array("title" => "Tamano real de alimento para peces | Artiani", "description" => "Mira textura y tamano del alimento para peces antes de comprar.", "canonical" => "/videos/tamano-real-alimento-peces", "robots" => "index,follow", "og_image" => $baseThumb . "alimento-peces-tamano-real.jpg")
      )
    );
  }

  private function filtrarItems($items, $filtros) {
    $filtrados = array();
    foreach ($items as $item) {
      if ($filtros["tipo"] !== "" && $item["tipo_video"] !== $filtros["tipo"]) { continue; }
      if ($filtros["destacado"] === "1" && empty($item["destacado"])) { continue; }
      if ($filtros["categoria"] !== "" && !$this->itemTieneCategoria($item, $filtros["categoria"])) { continue; }
      $filtrados[] = $item;
    }
    return $filtrados;
  }

  private function itemTieneCategoria($item, $categoriaSlug) {
    foreach ($item["categorias_relacionadas"] as $categoria) {
      if (isset($categoria["path_slug"]) && $categoria["path_slug"] === $categoriaSlug) {
        return true;
      }
    }
    return false;
  }

  private function hashtagsDesdeTexto($texto) {
    $texto = trim((string) $texto);
    if ($texto === "") {
      return array();
    }
    $partes = preg_split('/[\s,]+/', $texto);
    $hashtags = array();
    foreach ($partes as $parte) {
      $limpia = trim((string) $parte);
      if ($limpia === "") { continue; }
      $hashtags[] = ltrim($limpia, "#");
    }
    return $hashtags;
  }

  private function cardVideo($item) {
    return array(
      "id" => $item["id"],
      "titulo" => $item["titulo"],
      "slug" => $item["slug"],
      "tipo_video" => $item["tipo_video"],
      "descripcion_corta" => $item["descripcion_corta"],
      "thumbnail" => $item["thumbnail"],
      "producto_principal" => $item["producto_principal"],
      "url" => "/videos/" . $item["slug"],
      "ctas" => array(
        "ver_video" => array("label" => "Ver video", "url" => "/videos/" . $item["slug"]),
        "ver_producto" => !empty($item["producto_principal"]) ? array("label" => "Ver producto", "url" => $item["producto_principal"]["url"]) : null
      ),
      "guardrails" => array("no_iframe" => true, "thumbnail_obligatorio" => true)
    );
  }

  private function tiposVideo() {
    return array("demo_producto", "instalacion", "uso", "comparativa", "unboxing", "mantenimiento", "consejo_rapido");
  }

  private function guardrails() {
    return array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "frontend_no_lee_tablas" => true,
      "producto_base_comercial" => true,
      "sin_mascotas_registradas" => true,
      "sin_personalizacion_usuario" => true,
      "listados_solo_thumbnail" => true,
      "player_solo_con_click" => true
    );
  }

  private function paginacion($pagina, $limite, $total, $endpoint) {
    $totalPaginas = $limite > 0 ? (int) ceil($total / $limite) : 1;
    return array(
      "pagina" => $pagina,
      "limite" => $limite,
      "total" => $total,
      "total_paginas" => max(1, $totalPaginas),
      "pagina_anterior" => $pagina > 1 ? $pagina - 1 : null,
      "pagina_siguiente" => $pagina < $totalPaginas ? $pagina + 1 : null,
      "endpoint" => $endpoint
    );
  }

  private function slugSeguro($slug) {
    return trim(strtolower(preg_replace('/[^a-zA-Z0-9\-\/]/', '', (string) $slug)), "/");
  }

  private function tablaExiste($tabla) {
    $db = $this->getConexion();
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
      return false;
    }
    try {
      $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function columnaExiste($tabla, $columna) {
    $db = $this->getConexion();
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
      return false;
    }
    try {
      $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function indiceExiste($tabla, $indice) {
    $db = $this->getConexion();
    if (!$db || !preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $indice)) {
      return false;
    }
    try {
      $stmt = $db->prepare("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND INDEX_NAME=:indice LIMIT 1");
      $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":indice" => $indice));
      return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      return false;
    }
  }

  private function apiMeta() {
    return array(
      "nombre" => "ERP Ecommerce Videos",
      "version" => "fase0-videos-2026-09-28",
      "modo" => "videos_readonly_fixture",
      "fuente_verdad" => "ERP",
      "moneda_default" => "MXN"
    );
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array(
      "error" => (bool) $error,
      "tipo" => $tipo,
      "mensaje" => $mensaje,
      "api" => $this->apiMeta(),
      "depurar" => $depurar
    );
  }
}
