<?php

class EcommerceCatalogoPublico extends CRUD {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: entregar manifiesto versionado de contratos para el frontend ecommerce externo.
   * Impacto: Ecommerce publico; evita que el proyecto web consuma tablas internas o legacy `ecom_*`.
   * Contrato: solo lectura; describe endpoints, parametros, estados y guardrails.
   */
  public function contratosApiPublicos() {
    return $this->respuesta(false, "success", "Contratos API ecommerce publico", array(
      "api" => $this->apiMeta(),
      "base_path" => "/ecommercePublico",
      "arquitectura" => array(
        "erp_es_fuente_de_verdad" => true,
        "ecommerce_es_proyecto_externo" => true,
        "erp_no_renderiza_tienda_publica" => true,
        "ecom_legacy_no_es_fuente" => true
      ),
      "endpoints_publicos" => array(
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/estado",
          "descripcion" => "Readiness del API ecommerce: esquema, publicaciones, configuracion y guardrails.",
          "respuesta_depurar" => array("ready", "schema", "publicaciones", "configuracion", "seguridad")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/catalogo",
          "descripcion" => "Lista publicaciones ecommerce aprobadas con datos vivos desde ERP.",
          "parametros" => array(
            "q" => "Texto libre opcional.",
            "mascota" => "perro|gato|ave|pez|reptil|roedor|otra; opcional.",
            "necesidad" => "alimento|premio|higiene|salud|paseo|habitat|juguete|estetica; opcional.",
            "marca" => "ID marca ERP opcional.",
            "categoria" => "ID categoria ERP opcional.",
            "pagina" => "Pagina, default 1.",
            "limite" => "1-60, default 24."
          ),
          "respuesta_depurar" => array("configurado", "items", "paginacion", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/producto/{slug}",
          "descripcion" => "Detalle publico de una publicacion con estatus publicado.",
          "respuesta_depurar" => array("item", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/filtros",
          "descripcion" => "Filtros disponibles derivados de publicaciones vigentes.",
          "respuesta_depurar" => array("mascotas", "necesidades", "marcas", "categorias")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/configuracion",
          "descripcion" => "Configuracion publica del canal: moneda, WhatsApp, cotizacion y politicas visibles.",
          "respuesta_depurar" => array("configurado", "configuracion")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/disponibilidad",
          "descripcion" => "Disponibilidad publica simple por id_sku o slug.",
          "parametros" => array("id_sku" => "Opcional si se envia slug.", "slug" => "Opcional si se envia id_sku."),
          "estados" => $this->estadosDisponibilidadPublica()
        )
      ),
      "item_catalogo" => array(
        "id_publicacion" => "int",
        "id_producto_erp" => "int",
        "id_sku" => "int",
        "slug" => "string",
        "sku" => "string",
        "nombre" => "string",
        "marca" => "string|null",
        "categoria" => "string|null",
        "presentacion" => "string|null",
        "descripcion" => "string|null",
        "imagen" => "string|null",
        "precio" => "decimal|null",
        "moneda" => "MXN|null",
        "disponibilidad" => implode("|", $this->estadosDisponibilidadPublica()),
        "mascota_especie" => "string|null",
        "necesidades" => "string[]",
        "permite_cotizacion" => "bool",
        "permite_whatsapp" => "bool"
      ),
      "guardrails" => array(
        "solo_get_readonly" => true,
        "no_stock_exacto" => true,
        "no_costos" => true,
        "no_proveedores" => true,
        "no_lotes_ubicaciones" => true,
        "no_checkout" => true,
        "no_descuenta_inventario" => true,
        "no_usa_ecom_legacy_como_fuente" => true
      ),
      "seguridad_futura" => array(
        "cors" => "Permitir solo dominios del ecommerce externo en produccion.",
        "api_key_o_firma" => "Recomendado antes de publicar endpoints fuera del mismo dominio.",
        "rate_limit" => "Requerido antes de exponer POST de cotizaciones.",
        "captcha" => "Recomendado para formularios publicos de cotizacion."
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: reportar readiness publico del API ecommerce para integracion externa.
   * Impacto: Ecommerce publico; ayuda al frontend separado a distinguir API vivo, esquema pendiente y catalogo vacio.
   * Contrato: solo lectura; no expone stock, costos, proveedores ni tablas internas.
   */
  public function estadoApiPublica() {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array(
          "ready" => false,
          "schema" => array("disponible" => false),
          "publicaciones" => array("total_publicadas" => 0),
          "configuracion" => array("disponible" => false)
        ));
      }

      $tienePublicaciones = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
      $tieneConfiguracion = $this->tablaExiste($db, "erp_ecommerce_configuracion");
      $totalPublicadas = 0;
      $totalPublicables = 0;
      if ($tienePublicaciones) {
        $totalPublicadas = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_publicaciones WHERE estatus_publicacion='publicado'")->fetchColumn());
      }
      $resumen = $this->resumenPublicabilidad($db);
      $totalPublicables = intval(isset($resumen["skus_publicables_fase_1"]) ? $resumen["skus_publicables_fase_1"] : 0);

      return $this->respuesta(false, "success", "Estado API ecommerce consultado", array(
        "ready" => $tienePublicaciones && $tieneConfiguracion,
        "modo" => "catalogo_vivo_readonly",
        "schema" => array(
          "publicaciones_disponible" => $tienePublicaciones,
          "configuracion_disponible" => $tieneConfiguracion,
          "ddl_pendiente" => !$tienePublicaciones || !$tieneConfiguracion
        ),
        "publicaciones" => array(
          "total_publicadas" => $totalPublicadas,
          "skus_publicables_fase_1" => $totalPublicables,
          "catalogo_publico_vacio" => $totalPublicadas <= 0
        ),
        "configuracion" => array(
          "disponible" => $tieneConfiguracion,
          "usa_defaults" => !$tieneConfiguracion
        ),
        "seguridad" => array(
          "cors_restringido_pendiente" => true,
          "api_key_o_firma_pendiente" => true,
          "rate_limit_pendiente_para_post" => true,
          "post_publicos_habilitados" => false
        ),
        "guardrails" => array(
          "solo_readonly" => true,
          "no_checkout" => true,
          "no_descuenta_inventario" => true,
          "no_ecom_legacy_fuente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("ready" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: listar publicaciones ecommerce aprobadas para el sitio publico.
   * Impacto: Ecommerce publico; entrega imagen, precio, marca, categoria, mascota/necesidad y disponibilidad simple.
   * Contrato: solo lectura; si el esquema no existe responde lista vacia y `configurado=false`.
   */
  public function catalogoPublico($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("items" => array()));
      }
      if (!$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Catalogo publico ecommerce aun no configurado", array(
          "configurado" => false,
          "items" => array(),
          "paginacion" => array("pagina" => 1, "limite" => 24, "total" => 0),
          "guardrail" => "No se leen tablas legacy ecom_* como fuente publica."
        ));
      }

      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(1, min(60, intval($this->valor($filtros, "limite", 24))));
      $offset = ($pagina - 1) * $limite;
      $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'");
      $params = array();

      $q = trim((string) $this->valor($filtros, "q", ""));
      if ($q !== "") {
        $where[] = "(pub.titulo_publico LIKE :q OR p.nombre LIKE :q OR s.nombre LIKE :q OR s.sku LIKE :q OR m.nombre LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      $mascota = $this->limpiarFiltroPublico($this->valor($filtros, "mascota", ""));
      if ($mascota !== "") {
        $where[] = "pub.mascota_especie = :mascota";
        $params[":mascota"] = $mascota;
      }
      $necesidad = $this->limpiarFiltroPublico($this->valor($filtros, "necesidad", ""));
      if ($necesidad !== "") {
        $where[] = "pub.necesidades_json LIKE :necesidad";
        $params[":necesidad"] = "%\"" . $necesidad . "\"%";
      }
      $marca = intval($this->valor($filtros, "marca", 0));
      if ($marca > 0) {
        $where[] = "p.id_marca_erp = :marca";
        $params[":marca"] = $marca;
      }
      $categoria = intval($this->valor($filtros, "categoria", 0));
      if ($categoria > 0) {
        $where[] = "pc.id_categoria_erp = :categoria";
        $params[":categoria"] = $categoria;
      }

      $sqlBase = $this->sqlPublicacionesBase($where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM (" . $sqlBase . ") t");
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());

      $sql = $sqlBase . " ORDER BY pub.destacado DESC, pub.orden ASC, pub.titulo_publico ASC LIMIT " . intval($limite) . " OFFSET " . intval($offset);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearPublicacion($fila);
      }

      return $this->respuesta(false, "success", "Catalogo publico consultado", array(
        "configurado" => true,
        "items" => $items,
        "paginacion" => array("pagina" => $pagina, "limite" => $limite, "total" => $total),
        "guardrails" => array(
          "solo_publicados" => true,
          "no_stock_exacto" => true,
          "no_ecom_legacy_fuente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: consultar una publicacion ecommerce por slug para ficha publica.
   * Impacto: Ecommerce publico; evita exponer productos no publicados.
   * Contrato: solo lectura; no muestra cantidades exactas.
   */
  public function productoPublico($slug) {
    try {
      $db = $this->getConexion();
      $slug = trim((string) $slug);
      if (!$db || $slug === "" || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Producto publico no disponible", array("item" => null));
      }
      $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'", "pub.slug=:slug");
      $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " LIMIT 1");
      $stmt->execute(array(":slug" => $slug));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(false, "info", "Producto publico no encontrado", array("item" => null));
      }
      return $this->respuesta(false, "success", "Producto publico consultado", array(
        "item" => $this->formatearPublicacion($fila),
        "guardrails" => array("solo_publicado" => true, "no_stock_exacto" => true)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("item" => null));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: consultar filtros disponibles del catalogo publico publicado.
   * Impacto: Ecommerce publico; soporta navegacion por mascota, necesidad, marca y categoria.
   * Contrato: solo lectura; si no hay esquema devuelve filtros vacios.
   */
  public function filtrosPublicos() {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Filtros ecommerce aun no configurados", array(
          "mascotas" => array(),
          "necesidades" => array(),
          "marcas" => array(),
          "categorias" => array()
        ));
      }
      $baseWhere = "pub.estatus_publicacion='publicado' AND p.estatus='activo' AND s.estatus='activo'";
      $mascotas = $db->query("SELECT pub.mascota_especie valor, pub.mascota_especie etiqueta, COUNT(*) total
        FROM erp_ecommerce_publicaciones pub
        INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
        WHERE " . $baseWhere . " AND TRIM(COALESCE(pub.mascota_especie,''))<>''
        GROUP BY pub.mascota_especie ORDER BY pub.mascota_especie")->fetchAll(PDO::FETCH_ASSOC);
      $marcas = $db->query("SELECT m.id_marca_erp id, m.nombre etiqueta, COUNT(*) total
        FROM erp_ecommerce_publicaciones pub
        INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
        INNER JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
        WHERE " . $baseWhere . "
        GROUP BY m.id_marca_erp, m.nombre ORDER BY m.nombre")->fetchAll(PDO::FETCH_ASSOC);
      $categorias = $db->query("SELECT c.id_categoria_erp id, COALESCE(c.ruta, c.nombre) etiqueta, COUNT(*) total
        FROM erp_ecommerce_publicaciones pub
        INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
        INNER JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
        INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
        WHERE " . $baseWhere . "
        GROUP BY c.id_categoria_erp, c.ruta, c.nombre ORDER BY etiqueta")->fetchAll(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Filtros ecommerce consultados", array(
        "mascotas" => $mascotas,
        "necesidades" => $this->necesidadesPublicas($db),
        "marcas" => $marcas,
        "categorias" => $categorias
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: entregar configuracion publica consumible por el frontend ecommerce externo.
   * Impacto: Ecommerce publico; centraliza WhatsApp, moneda y politicas sin hardcodear en la web.
   * Contrato: solo lectura; si no existe tabla de configuracion devuelve defaults seguros y `configurado=false`.
   */
  public function configuracionPublica() {
    try {
      $db = $this->getConexion();
      $defaults = $this->configuracionPublicaDefault();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_configuracion")) {
        return $this->respuesta(false, "info", "Configuracion ecommerce aun no persistida", array(
          "configurado" => false,
          "configuracion" => $defaults,
          "guardrails" => array("sin_whatsapp_hardcodeado" => true, "solo_claves_publicas" => true)
        ));
      }

      $clavesPublicas = array_keys($defaults);
      $placeholders = implode(",", array_fill(0, count($clavesPublicas), "?"));
      $stmt = $db->prepare("SELECT clave, valor FROM erp_ecommerce_configuracion WHERE estatus='activo' AND clave IN (" . $placeholders . ")");
      $stmt->execute($clavesPublicas);
      $config = $defaults;
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $clave = (string) $fila["clave"];
        if (array_key_exists($clave, $config)) {
          $config[$clave] = $fila["valor"];
        }
      }

      return $this->respuesta(false, "success", "Configuracion ecommerce consultada", array(
        "configurado" => true,
        "configuracion" => $config,
        "guardrails" => array("solo_claves_publicas" => true, "no_expone_secretos" => true)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("configurado" => false, "configuracion" => $this->configuracionPublicaDefault()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: traducir existencia interna de un SKU publicado a disponibilidad publica simple.
   * Impacto: Ecommerce publico/Inventario; reduce riesgo operativo al no mostrar cantidades exactas.
   * Contrato: solo lectura; no reserva ni descuenta inventario.
   */
  public function disponibilidadPublica($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Disponibilidad ecommerce aun no configurada", array("disponibilidad" => "consultar_disponibilidad"));
      }
      $idSku = intval($this->valor($filtros, "id_sku", 0));
      $slug = trim((string) $this->valor($filtros, "slug", ""));
      if ($idSku <= 0 && $slug === "") {
        return $this->respuesta(true, "warning", "Indica SKU o slug publicado", array("disponibilidad" => "consultar_disponibilidad"));
      }
      $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'");
      $params = array();
      if ($idSku > 0) {
        $where[] = "pub.id_sku=:sku";
        $params[":sku"] = $idSku;
      } else {
        $where[] = "pub.slug=:slug";
        $params[":slug"] = $slug;
      }
      $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " LIMIT 1");
      $stmt->execute($params);
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(false, "info", "SKU no publicado", array("disponibilidad" => "consultar_disponibilidad"));
      }
      return $this->respuesta(false, "success", "Disponibilidad publica consultada", array(
        "id_sku" => intval($fila["id_sku"]),
        "slug" => $fila["slug"],
        "disponibilidad" => $this->disponibilidadPublicaSugerida($fila),
        "mostrar_cantidad_exacta" => false
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("disponibilidad" => "consultar_disponibilidad"));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: auditar SKUs ERP candidatos para publicacion ecommerce sin crear publicaciones.
   * Impacto: Ecommerce publico/Catalogo ERP; ayuda a decidir que puede salir al catalogo vivo sin usar `ecom_*` como fuente nueva.
   * Contrato: solo lectura; no crea publicaciones, no toca inventario, no registra cotizaciones.
   */
  public function auditarPublicabilidad($filtros = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("read_only" => true));
      }

      $limite = max(10, min(200, intval($this->valor($filtros, "limite", 50))));
      $soloBloqueados = intval($this->valor($filtros, "solo_bloqueados", 0)) === 1;
      $soloPublicables = intval($this->valor($filtros, "solo_publicables", 0)) === 1;

      $resumen = $this->resumenPublicabilidad($db);
      $candidatos = $this->listarCandidatosPublicacion($db, $limite, $soloBloqueados, $soloPublicables);

      return $this->respuesta(false, "success", "Auditoria ecommerce publica consultada", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_usa_ecom_como_fuente" => true,
        "resumen" => $resumen,
        "candidatos" => $candidatos,
        "criterios_fase_1" => array(
          "producto_activo" => true,
          "sku_activo" => true,
          "precio_general_activo" => true,
          "imagen_activa" => true,
          "categoria_principal" => true,
          "marca_recomendada" => true,
          "bloquear_fraccionarios_granel" => true,
          "no_requiere_existencia_para_publicar" => true,
          "disponibilidad_publica_no_muestra_cantidad" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: generar una propuesta de publicacion ecommerce para un SKU sin escribir BD.
   * Impacto: Ecommerce publico; aterriza curaduria de mascota/necesidad/slug antes de crear registros.
   * Contrato: solo lectura; no crea publicaciones, no usa `ecom_*` como fuente y no toca inventario.
   */
  public function prepararPublicacion($datos = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("read_only" => true));
      }
      $idSku = intval($this->valor($datos, "id_sku", 0));
      if ($idSku <= 0) {
        return $this->respuesta(true, "warning", "Selecciona un SKU ERP", array("read_only" => true));
      }

      $fila = $this->consultarCandidatoPorSku($db, $idSku);
      if (!$fila) {
        return $this->respuesta(true, "warning", "SKU no encontrado o inactivo", array("read_only" => true, "id_sku" => $idSku));
      }

      $bloqueos = $this->bloqueosPublicacion($fila);
      $metadata = $this->inferirMetadataMascotas($fila);
      $titulo = trim((string) $fila["nombre_publico"]);
      $presentacion = trim((string) $fila["presentacion_base"]);
      $slugBase = $titulo . " " . $presentacion . " " . $fila["sku"];

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Propuesta de publicacion preparada" : "Propuesta preparada con bloqueos", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "publicable_fase_1" => empty($bloqueos),
        "bloqueos_publicacion" => $bloqueos,
        "producto_vivo_erp" => array(
          "id_producto_erp" => intval($fila["id_producto_erp"]),
          "id_sku" => intval($fila["id_sku"]),
          "sku" => $fila["sku"],
          "nombre" => $fila["nombre_publico"],
          "marca" => $fila["marca"],
          "categoria" => $fila["categoria"],
          "presentacion_base" => $fila["presentacion_base"],
          "imagen" => $fila["url_imagen"],
          "precio" => floatval($fila["precio"]),
          "moneda" => $fila["moneda"] ?: "MXN",
          "disponibilidad_publica_sugerida" => $this->disponibilidadPublicaSugerida($fila)
        ),
        "publicacion_sugerida" => array(
          "canal" => "catalogo_publico",
          "estatus_publicacion" => "borrador",
          "slug" => $this->slugificar($slugBase),
          "titulo_publico" => $titulo,
          "descripcion_publica" => "",
          "presentacion_publica" => $presentacion,
          "mascota_especie" => $metadata["mascota_especie"],
          "necesidades" => $metadata["necesidades"],
          "destacado" => 0,
          "orden" => 0,
          "permite_cotizacion" => 1,
          "permite_whatsapp" => 1,
          "mostrar_precio" => 1,
          "mostrar_disponibilidad" => 1
        ),
        "flujo" => array(
          "fuente_viva" => "Catalogo ERP/Inventario ERP",
          "publicacion_es_curaduria" => true,
          "precios_e_imagenes_se_reflejan_desde_erp" => true,
          "snapshot_solo_en_cotizacion" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true));
    }
  }

  private function resumenPublicabilidad($db) {
    $joinPublicaciones = $this->tablaExiste($db, "erp_ecommerce_publicaciones")
      ? "LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.estatus_publicacion IN ('borrador','publicado','pausado')"
      : "";
    $publicadosExpr = $joinPublicaciones !== "" ? "SUM(CASE WHEN pub.id_publicacion IS NOT NULL THEN 1 ELSE 0 END)" : "0";
    $sql = "SELECT
        COUNT(*) skus_total,
        SUM(p.estatus='activo' AND s.estatus='activo') skus_activos_producto_activo,
        SUM(CASE WHEN pr.id_sku_precio IS NOT NULL THEN 1 ELSE 0 END) skus_con_precio,
        SUM(CASE WHEN img.id_imagen_erp IS NOT NULL THEN 1 ELSE 0 END) skus_con_imagen,
        SUM(CASE WHEN pc.id_categoria_erp IS NOT NULL THEN 1 ELSE 0 END) skus_con_categoria,
        SUM(CASE WHEN p.id_marca_erp IS NOT NULL THEN 1 ELSE 0 END) skus_con_marca,
        SUM(CASE WHEN COALESCE(r.permite_venta_fraccionaria, 0)=1 THEN 1 ELSE 0 END) skus_fraccionarios,
        " . $publicadosExpr . " skus_ya_publicados,
        SUM(CASE WHEN p.estatus='activo'
              AND s.estatus='activo'
              AND pr.id_sku_precio IS NOT NULL
              AND img.id_imagen_erp IS NOT NULL
              AND pc.id_categoria_erp IS NOT NULL
              AND COALESCE(r.permite_venta_fraccionaria, 0)=0
            THEN 1 ELSE 0 END) skus_publicables_fase_1
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
      LEFT JOIN (
        SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
        FROM erp_catalogo_imagenes
        WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
        GROUP BY id_producto_erp
      ) img ON img.id_producto_erp=p.id_producto_erp
      " . $joinPublicaciones;
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC);
  }

  private function listarCandidatosPublicacion($db, $limite, $soloBloqueados, $soloPublicables) {
    $tienePublicaciones = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
    $where = array("p.estatus='activo'", "s.estatus='activo'");
    if ($soloPublicables) {
      $where[] = "pr.id_sku_precio IS NOT NULL";
      $where[] = "img.url_imagen IS NOT NULL";
      $where[] = "pc.id_categoria_erp IS NOT NULL";
      $where[] = "COALESCE(r.permite_venta_fraccionaria, 0)=0";
    }
    if ($soloBloqueados) {
      $where[] = "(pr.id_sku_precio IS NULL OR img.url_imagen IS NULL OR pc.id_categoria_erp IS NULL OR COALESCE(r.permite_venta_fraccionaria, 0)=1)";
    }

    $sql = "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, s.sku,
        COALESCE(s.nombre, p.nombre) nombre_publico,
        m.nombre marca,
        COALESCE(c.ruta, c.nombre) categoria,
        COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base,
        pr.precio, pr.moneda,
        img.url_imagen,
        COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
        COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
        " . ($tienePublicaciones ? "pub.id_publicacion, pub.estatus_publicacion" : "NULL id_publicacion, NULL estatus_publicacion") . "
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
      LEFT JOIN (
        SELECT i.id_producto_erp, i.url_imagen
        FROM erp_catalogo_imagenes i
        INNER JOIN (
          SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
          FROM erp_catalogo_imagenes
          WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
          GROUP BY id_producto_erp
        ) x ON x.id_imagen_erp=i.id_imagen_erp
      ) img ON img.id_producto_erp=p.id_producto_erp
      LEFT JOIN (
        SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
        FROM erp_inventario_existencias
        WHERE estatus_existencia IN ('disponible','agotada')
        GROUP BY id_sku_erp
      ) inv ON inv.id_sku_erp=s.id_sku
      " . ($tienePublicaciones ? "LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.estatus_publicacion IN ('borrador','publicado','pausado')" : "") . "
      WHERE " . implode(" AND ", $where) . "
      ORDER BY CASE WHEN pr.id_sku_precio IS NOT NULL AND img.url_imagen IS NOT NULL AND pc.id_categoria_erp IS NOT NULL AND COALESCE(r.permite_venta_fraccionaria,0)=0 THEN 0 ELSE 1 END,
        p.nombre, s.sku
      LIMIT " . intval($limite);
    $filas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($filas as &$fila) {
      $bloqueos = $this->bloqueosPublicacion($fila);
      $fila["publicable_fase_1"] = empty($bloqueos) ? 1 : 0;
      $fila["bloqueos_publicacion"] = $bloqueos;
      $fila["disponibilidad_publica_sugerida"] = $this->disponibilidadPublicaSugerida($fila);
    }
    unset($fila);
    return $filas;
  }

  private function consultarCandidatoPorSku($db, $idSku) {
    $tienePublicaciones = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
    $sql = "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, s.sku,
        COALESCE(s.nombre, p.nombre) nombre_publico,
        m.nombre marca,
        COALESCE(c.ruta, c.nombre) categoria,
        COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base,
        pr.precio, pr.moneda,
        img.url_imagen,
        COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
        COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
        " . ($tienePublicaciones ? "pub.id_publicacion, pub.estatus_publicacion" : "NULL id_publicacion, NULL estatus_publicacion") . "
      FROM erp_catalogo_skus s
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
      LEFT JOIN (
        SELECT i.id_producto_erp, i.url_imagen
        FROM erp_catalogo_imagenes i
        INNER JOIN (
          SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
          FROM erp_catalogo_imagenes
          WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
          GROUP BY id_producto_erp
        ) x ON x.id_imagen_erp=i.id_imagen_erp
      ) img ON img.id_producto_erp=p.id_producto_erp
      LEFT JOIN (
        SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
        FROM erp_inventario_existencias
        WHERE estatus_existencia IN ('disponible','agotada')
        GROUP BY id_sku_erp
      ) inv ON inv.id_sku_erp=s.id_sku
      " . ($tienePublicaciones ? "LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.estatus_publicacion IN ('borrador','publicado','pausado')" : "") . "
      WHERE p.estatus='activo' AND s.estatus='activo' AND s.id_sku=:sku
      LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":sku" => $idSku));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function bloqueosPublicacion($fila) {
    $bloqueos = array();
    if (empty($fila["precio"]) || floatval($fila["precio"]) <= 0) {
      $bloqueos[] = "precio_general_faltante";
    }
    if (trim((string) $fila["url_imagen"]) === "") {
      $bloqueos[] = "imagen_faltante";
    }
    if (trim((string) $fila["categoria"]) === "") {
      $bloqueos[] = "categoria_principal_faltante";
    }
    if (intval($fila["permite_venta_fraccionaria"]) === 1) {
      $bloqueos[] = "venta_fraccionaria_bloqueada_fase_1";
    }
    if (!empty($fila["id_publicacion"])) {
      $bloqueos[] = "publicacion_existente";
    }
    return $bloqueos;
  }

  private function disponibilidadPublicaSugerida($fila) {
    if (intval($fila["controla_inventario"]) !== 1) {
      return "consultar_disponibilidad";
    }
    $disponible = floatval($fila["existencia_disponible"]);
    if ($disponible <= 0) {
      return "agotado";
    }
    if ($disponible <= 3) {
      return "pocas_piezas";
    }
    return "disponible";
  }

  private function inferirMetadataMascotas($fila) {
    $texto = strtolower($this->normalizarTextoPlano(trim((string) $fila["nombre_publico"] . " " . $fila["categoria"])));
    $mascota = "";
    $mapaMascotas = array(
      "perro" => array("perro", "canino", "cachorro"),
      "gato" => array("gato", "felino", "gatito"),
      "ave" => array("ave", "pajaro", "perico", "canario"),
      "pez" => array("pez", "peces", "acuario"),
      "reptil" => array("reptil", "tortuga", "iguana"),
      "roedor" => array("roedor", "hamster", "conejo", "cuyo")
    );
    foreach ($mapaMascotas as $clave => $palabras) {
      foreach ($palabras as $palabra) {
        if (strpos($texto, $palabra) !== false) {
          $mascota = $clave;
          break 2;
        }
      }
    }

    $necesidades = array();
    $mapaNecesidades = array(
      "alimento" => array("alimento", "croqueta", "comida", "lata", "dieta"),
      "premio" => array("premio", "snack", "treat", "galleta"),
      "higiene" => array("higiene", "arena", "shampoo", "limpieza", "sanitario"),
      "salud" => array("salud", "vitamina", "suplemento", "medicina", "antipulgas"),
      "paseo" => array("paseo", "collar", "correa", "pechera"),
      "habitat" => array("habitat", "cama", "jaula", "casa", "pecera", "acuario"),
      "juguete" => array("juguete", "pelota", "mordedera"),
      "estetica" => array("estetica", "cepillo", "corte", "perfume")
    );
    foreach ($mapaNecesidades as $clave => $palabras) {
      foreach ($palabras as $palabra) {
        if (strpos($texto, $palabra) !== false) {
          $necesidades[] = $clave;
          break;
        }
      }
    }

    return array(
      "mascota_especie" => $mascota,
      "necesidades" => array_values(array_unique($necesidades))
    );
  }

  private function slugificar($texto) {
    $texto = strtolower($this->normalizarTextoPlano($texto));
    $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
    $texto = trim($texto, '-');
    return substr($texto !== "" ? $texto : "producto", 0, 170);
  }

  private function normalizarTextoPlano($texto) {
    $buscar = array('á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ');
    $reemplazar = array('a','e','i','o','u','u','n','A','E','I','O','U','U','N');
    return str_replace($buscar, $reemplazar, (string) $texto);
  }

  private function sqlPublicacionesBase($where) {
    return "SELECT pub.id_publicacion, pub.id_producto_erp, pub.id_sku, pub.slug,
        pub.titulo_publico, pub.descripcion_publica, pub.presentacion_publica,
        pub.mascota_especie, pub.necesidades_json, pub.destacado,
        pub.permite_cotizacion, pub.permite_whatsapp, pub.mostrar_precio, pub.mostrar_disponibilidad,
        s.sku, COALESCE(s.nombre, p.nombre) nombre_sku, p.nombre nombre_producto,
        m.nombre marca, pc.id_categoria_erp, COALESCE(c.ruta, c.nombre) categoria,
        pr.precio, pr.moneda,
        img.url_imagen,
        COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
        COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible
      FROM erp_ecommerce_publicaciones pub
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp AND p.id_producto_erp=s.id_producto_erp
      LEFT JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      LEFT JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      LEFT JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      LEFT JOIN erp_catalogo_sku_precios pr ON pr.id_sku=s.id_sku AND pr.lista_precio='general' AND pr.moneda='MXN' AND pr.estatus='activo' AND pr.precio>0
      LEFT JOIN (
        SELECT i.id_producto_erp, i.url_imagen
        FROM erp_catalogo_imagenes i
        INNER JOIN (
          SELECT id_producto_erp, MIN(id_imagen_erp) id_imagen_erp
          FROM erp_catalogo_imagenes
          WHERE estatus='activo' AND TRIM(COALESCE(url_imagen,''))<>''
          GROUP BY id_producto_erp
        ) x ON x.id_imagen_erp=i.id_imagen_erp
      ) img ON img.id_producto_erp=p.id_producto_erp
      LEFT JOIN (
        SELECT id_sku_erp, SUM(cantidad_disponible) cantidad_disponible
        FROM erp_inventario_existencias
        WHERE estatus_existencia IN ('disponible','agotada')
        GROUP BY id_sku_erp
      ) inv ON inv.id_sku_erp=s.id_sku
      WHERE " . implode(" AND ", $where);
  }

  private function formatearPublicacion($fila) {
    $mostrarPrecio = intval($fila["mostrar_precio"]) === 1;
    $mostrarDisponibilidad = intval($fila["mostrar_disponibilidad"]) === 1;
    return array(
      "id_publicacion" => intval($fila["id_publicacion"]),
      "id_producto_erp" => intval($fila["id_producto_erp"]),
      "id_sku" => intval($fila["id_sku"]),
      "slug" => $fila["slug"],
      "sku" => $fila["sku"],
      "nombre" => $fila["titulo_publico"] ?: $fila["nombre_sku"],
      "marca" => $fila["marca"],
      "categoria" => $fila["categoria"],
      "presentacion" => $fila["presentacion_publica"],
      "descripcion" => $fila["descripcion_publica"],
      "imagen" => $fila["url_imagen"],
      "precio" => $mostrarPrecio ? floatval($fila["precio"]) : null,
      "moneda" => $mostrarPrecio ? ($fila["moneda"] ?: "MXN") : null,
      "disponibilidad" => $mostrarDisponibilidad ? $this->disponibilidadPublicaSugerida($fila) : "consultar_disponibilidad",
      "mascota_especie" => $fila["mascota_especie"],
      "necesidades" => $this->decodificarJsonLista($fila["necesidades_json"]),
      "permite_cotizacion" => intval($fila["permite_cotizacion"]) === 1,
      "permite_whatsapp" => intval($fila["permite_whatsapp"]) === 1
    );
  }

  private function necesidadesPublicas($db) {
    $filas = $db->query("SELECT necesidades_json
      FROM erp_ecommerce_publicaciones pub
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
      WHERE pub.estatus_publicacion='publicado' AND p.estatus='activo' AND s.estatus='activo'
        AND TRIM(COALESCE(pub.necesidades_json,''))<>''")->fetchAll(PDO::FETCH_ASSOC);
    $conteo = array();
    foreach ($filas as $fila) {
      foreach ($this->decodificarJsonLista($fila["necesidades_json"]) as $valor) {
        if (!isset($conteo[$valor])) {
          $conteo[$valor] = 0;
        }
        $conteo[$valor]++;
      }
    }
    ksort($conteo);
    $salida = array();
    foreach ($conteo as $valor => $total) {
      $salida[] = array("valor" => $valor, "etiqueta" => $valor, "total" => $total);
    }
    return $salida;
  }

  private function decodificarJsonLista($json) {
    $datos = json_decode((string) $json, true);
    if (!is_array($datos)) {
      return array();
    }
    $salida = array();
    foreach ($datos as $valor) {
      $valor = $this->limpiarFiltroPublico($valor);
      if ($valor !== "") {
        $salida[] = $valor;
      }
    }
    return array_values(array_unique($salida));
  }

  private function limpiarFiltroPublico($valor) {
    $valor = strtolower(trim((string) $valor));
    $valor = preg_replace('/[^a-z0-9_\-]/', '', $valor);
    return substr($valor, 0, 60);
  }

  private function configuracionPublicaDefault() {
    return array(
      "moneda_default" => "MXN",
      "whatsapp_numero_principal" => "",
      "whatsapp_mensaje_base" => "Hola, quiero cotizar estos productos:",
      "cotizacion_habilitada" => "1",
      "mostrar_stock_exacto" => "0",
      "modo_sin_stock" => "consultar",
      "texto_total_estimado" => "Total estimado sujeto a confirmacion",
      "url_sitio_publico" => ""
    );
  }

  private function tablaExiste($db, $tabla) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabla)) {
      return false;
    }
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetchColumn();
  }

  private function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
  }

  private function respuesta($error, $tipo, $mensaje, $depurar = array()) {
    return array("error" => $error, "tipo" => $tipo, "mensaje" => $mensaje, "api" => $this->apiMeta(), "depurar" => $depurar);
  }

  private function apiMeta() {
    return array(
      "nombre" => "ERP Ecommerce Publico",
      "version" => "fase1-2026-07-12",
      "modo" => "catalogo_vivo_readonly",
      "fuente_verdad" => "ERP",
      "moneda_default" => "MXN"
    );
  }

  private function estadosDisponibilidadPublica() {
    return array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado");
  }
}
