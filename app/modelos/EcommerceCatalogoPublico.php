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
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/cotizacion_dryrun",
          "descripcion" => "Valida/recalcula un carrito sin guardar cotizacion ni afectar inventario.",
          "estado" => "dry-run",
          "body" => array(
            "items" => array(
              array("id_publicacion" => "int opcional", "slug" => "string opcional", "id_sku" => "int opcional", "cantidad" => "decimal > 0")
            ),
            "contacto" => array("nombre" => "string opcional", "telefono" => "string opcional", "mensaje" => "string opcional"),
            "utm" => "object opcional"
          ),
          "respuesta_depurar" => array("dry_run", "lineas", "totales", "bloqueos", "whatsapp_preview")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/cotizacion_registrar",
          "descripcion" => "Contrato futuro para registrar cotizacion real; bloqueado en Fase 1 hasta autorizar persistencia.",
          "estado" => "bloqueado",
          "body" => array(
            "items" => array(
              array("id_publicacion" => "int opcional", "slug" => "string opcional", "id_sku" => "int opcional", "cantidad" => "decimal > 0")
            ),
            "contacto" => array("nombre" => "string requerido al activar", "telefono" => "string requerido al activar", "mensaje" => "string opcional"),
            "utm" => "object opcional",
            "acepta_contacto_whatsapp" => "bool requerido al activar"
          ),
          "requisitos_activacion" => array("DDL erp_ecommerce_* aplicado", "API key/firma activa", "rate limit", "politica de seguimiento definida")
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
        "post_dryrun_sin_persistencia" => true,
        "post_registro_bloqueado" => true,
        "no_stock_exacto" => true,
        "no_costos" => true,
        "no_proveedores" => true,
        "no_lotes_ubicaciones" => true,
        "no_checkout" => true,
        "no_descuenta_inventario" => true,
        "no_usa_ecom_legacy_como_fuente" => true
      ),
      "autenticacion_futura" => $this->contratoAutenticacionFutura(),
      "seguridad_futura" => array(
        "cors" => "Permitir solo dominios configurados en cors_origenes_permitidos.",
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
          "autenticacion_activa" => false,
          "autenticacion_modo_futuro" => "api_key_hmac",
          "rate_limit_pendiente_para_post" => true,
          "post_publicos_habilitados" => false,
          "post_dryrun_disponible" => true,
          "post_registro_bloqueado" => true
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: validar si un Origin puede recibir CORS para el API ecommerce publico.
   * Impacto: Seguridad API; CORS queda cerrado por defecto hasta configurar origenes permitidos.
   * Contrato: solo lectura; acepta coincidencia exacta con `cors_origenes_permitidos`.
   */
  public function origenCorsPermitido($origen) {
    $origen = trim((string) $origen);
    if ($origen === "") {
      return false;
    }
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_configuracion")) {
        return false;
      }
      $stmt = $db->prepare("SELECT valor FROM erp_ecommerce_configuracion WHERE clave='cors_origenes_permitidos' AND estatus='activo' LIMIT 1");
      $stmt->execute();
      $valor = trim((string) $stmt->fetchColumn());
      if ($valor === "") {
        return false;
      }
      $permitidos = preg_split('/[\r\n,]+/', $valor);
      foreach ($permitidos as $permitido) {
        if (rtrim(trim((string) $permitido), "/") === rtrim($origen, "/")) {
          return true;
        }
      }
    } catch (Exception $e) {
      return false;
    }
    return false;
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: preparar la configuracion inicial del canal ecommerce publico sin escribir BD.
   * Impacto: deja revisables WhatsApp, CORS, moneda y textos antes de activar datos reales.
   * Contrato: read-only; devuelve SQL sugerido, valores actuales y bloqueos operativos.
   */
  public function planConfiguracionInicial($valores = array()) {
    try {
      $db = $this->getConexion();
      $defaults = $this->configuracionPublicaDefault();
      $propuestos = $defaults;
      foreach ($valores as $clave => $valor) {
        if (array_key_exists($clave, $propuestos)) {
          $propuestos[$clave] = trim((string) $valor);
        }
      }

      $existeTabla = $db && $this->tablaExiste($db, "erp_ecommerce_configuracion");
      $actuales = array();
      if ($existeTabla) {
        $claves = array_keys($defaults);
        $placeholders = implode(",", array_fill(0, count($claves), "?"));
        $stmt = $db->prepare("SELECT clave, valor, estatus FROM erp_ecommerce_configuracion WHERE clave IN (" . $placeholders . ")");
        $stmt->execute($claves);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $actuales[(string) $fila["clave"]] = array(
            "valor" => (string) $fila["valor"],
            "estatus" => (string) $fila["estatus"]
          );
        }
      }

      $sql = array();
      foreach ($propuestos as $clave => $valor) {
        $sql[] = "INSERT INTO `erp_ecommerce_configuracion` (`clave`, `valor`, `descripcion`, `estatus`, `fecha_registro`, `fecha_actualizacion`) VALUES (" .
          $this->sqlQuote($clave) . ", " .
          $this->sqlQuote($valor) . ", " .
          $this->sqlQuote($this->descripcionConfiguracionPublica($clave)) . ", 'activo', NOW(), NOW()) " .
          "ON DUPLICATE KEY UPDATE `valor`=VALUES(`valor`), `descripcion`=VALUES(`descripcion`), `estatus`='activo', `fecha_actualizacion`=NOW();";
      }

      $bloqueos = array();
      if (!$existeTabla) {
        $bloqueos[] = "tabla_erp_ecommerce_configuracion_pendiente";
      }
      if (trim((string) $propuestos["whatsapp_numero_principal"]) === "") {
        $bloqueos[] = "whatsapp_numero_principal_requerido_para_datos_reales";
      }
      if (trim((string) $propuestos["cors_origenes_permitidos"]) === "") {
        $bloqueos[] = "cors_origenes_permitidos_requerido_para_frontend_externo";
      }

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Plan de configuracion ecommerce generado sin ejecutar", array(
        "read_only" => true,
        "tabla_configuracion_existe" => $existeTabla,
        "actuales" => $actuales,
        "propuestos" => $propuestos,
        "sql_total" => count($sql),
        "sha256_sql" => hash("sha256", implode("\n\n", $sql)),
        "sql" => $sql,
        "bloqueos_datos_reales" => $bloqueos,
        "guardrails" => array(
          "no_escribe_bd" => true,
          "no_expone_secretos" => true,
          "no_usa_access_control_allow_origin_wildcard" => strpos((string) $propuestos["cors_origenes_permitidos"], "*") === false,
          "no_toca_ecom_legacy" => true,
          "no_mueve_inventario" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: guardar configuracion publica ecommerce solo con autorizacion explicita.
   * Impacto: activa WhatsApp/CORS/url del canal publico sin exponer secretos ni tocar inventario.
   * Contrato: escribe BD solo con token `ECOMMERCE_PUBLICO_CONFIGURACION_FASE1`.
   */
  public function guardarConfiguracionInicialAutorizada($valores = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($valores, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_CONFIGURACION_FASE1") {
      return $this->respuesta(true, "warning", "Guardado de configuracion ecommerce bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_CONFIGURACION_FASE1"
      ));
    }

    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
      }

      $plan = $this->planConfiguracionInicial($valores);
      $depurar = $this->valor($plan, array("depurar"), array());
      $propuestos = $this->valor($depurar, array("propuestos"), array());
      $bloqueos = $this->valor($depurar, array("bloqueos_datos_reales"), array());
      $cors = trim((string) $this->valor($propuestos, "cors_origenes_permitidos", ""));
      if (strpos($cors, "*") !== false) {
        $bloqueos[] = "cors_no_puede_usar_wildcard";
      }
      if (in_array("tabla_erp_ecommerce_configuracion_pendiente", $bloqueos, true)) {
        return $this->respuesta(true, "warning", "No se guardo configuracion porque falta DDL", array(
          "no_escribe_bd" => true,
          "bloqueos_datos_reales" => array_values(array_unique($bloqueos)),
          "plan" => $plan
        ));
      }
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "No se guardo configuracion por bloqueos", array(
          "no_escribe_bd" => true,
          "bloqueos_datos_reales" => array_values(array_unique($bloqueos)),
          "plan" => $plan
        ));
      }

      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_configuracion
          (clave, valor, descripcion, estatus, fecha_registro, fecha_actualizacion)
        VALUES
          (:clave, :valor, :descripcion, 'activo', NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          valor=VALUES(valor),
          descripcion=VALUES(descripcion),
          estatus='activo',
          fecha_actualizacion=NOW()");
      foreach ($propuestos as $clave => $valor) {
        $stmt->execute(array(
          ":clave" => (string) $clave,
          ":valor" => (string) $valor,
          ":descripcion" => $this->descripcionConfiguracionPublica($clave)
        ));
      }
      $db->commit();

      return $this->respuesta(false, "success", "Configuracion ecommerce guardada", array(
        "escribe_bd" => true,
        "claves_guardadas" => array_keys($propuestos),
        "sha256_sql_plan" => $this->valor($depurar, "sha256_sql", ""),
        "guardrails" => array(
          "no_expone_secretos" => true,
          "cors_sin_wildcard" => true,
          "no_toca_ecom_legacy" => true,
          "no_mueve_inventario" => true
        )
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: recalcular un carrito ecommerce sin persistirlo.
   * Impacto: Ecommerce publico; prepara contrato de cotizacion WhatsApp sin crear pedido, venta ni apartado.
   * Contrato: no escribe BD, no descuenta inventario, no acepta precios del frontend como verdad.
   */
  public function cotizacionDryRun($datos = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Cotizacion dry-run aun no configurada", array(
          "configurado" => false,
          "dry_run" => true,
          "no_escribe_bd" => true,
          "lineas" => array(),
          "totales" => array("subtotal_estimado" => 0, "total_estimado" => 0, "moneda" => "MXN"),
          "bloqueos" => array("esquema_publicaciones_pendiente")
        ));
      }

      $items = isset($datos["items"]) && is_array($datos["items"]) ? $datos["items"] : array();
      if (empty($items)) {
        return $this->respuesta(true, "warning", "Agrega productos para validar la cotizacion", array(
          "dry_run" => true,
          "no_escribe_bd" => true,
          "lineas" => array(),
          "bloqueos" => array("items_vacios")
        ));
      }

      $items = array_slice($items, 0, 50);
      $lineas = array();
      $bloqueos = array();
      $subtotal = 0.0;
      foreach ($items as $index => $item) {
        $cantidad = max(0, min(999, floatval($this->valor($item, "cantidad", 1))));
        if ($cantidad <= 0) {
          $bloqueos[] = "cantidad_invalida_linea_" . ($index + 1);
          continue;
        }
        $publicacion = $this->consultarPublicacionParaCotizacion($db, $item);
        if (!$publicacion) {
          $bloqueos[] = "publicacion_no_disponible_linea_" . ($index + 1);
          continue;
        }
        $precio = floatval($publicacion["precio"]);
        $subtotalLinea = round($precio * $cantidad, 6);
        $subtotal += $subtotalLinea;
        $disponibilidad = $this->disponibilidadPublicaSugerida($publicacion);
        $lineas[] = array(
          "renglon" => count($lineas) + 1,
          "id_publicacion" => intval($publicacion["id_publicacion"]),
          "id_producto_erp" => intval($publicacion["id_producto_erp"]),
          "id_sku" => intval($publicacion["id_sku"]),
          "slug" => $publicacion["slug"],
          "sku" => $publicacion["sku"],
          "nombre" => $publicacion["titulo_publico"] ?: $publicacion["nombre_sku"],
          "presentacion" => $publicacion["presentacion_publica"],
          "precio_unitario" => $precio,
          "moneda" => $publicacion["moneda"] ?: "MXN",
          "cantidad" => $cantidad,
          "subtotal" => $subtotalLinea,
          "disponibilidad" => $disponibilidad,
          "permite_cotizacion" => intval($publicacion["permite_cotizacion"]) === 1
        );
        if (intval($publicacion["permite_cotizacion"]) !== 1) {
          $bloqueos[] = "cotizacion_no_permitida_linea_" . count($lineas);
        }
      }

      $total = round($subtotal, 6);
      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Cotizacion dry-run validada" : "Cotizacion dry-run con observaciones", array(
        "configurado" => true,
        "dry_run" => true,
        "no_escribe_bd" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "lineas" => $lineas,
        "totales" => array(
          "subtotal_estimado" => $total,
          "total_estimado" => $total,
          "moneda" => "MXN",
          "texto" => "Total estimado sujeto a confirmacion"
        ),
        "bloqueos" => $bloqueos,
        "whatsapp_preview" => $this->mensajeWhatsAppPreview($lineas, $total)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("dry_run" => true, "no_escribe_bd" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: responder el contrato futuro de registro de cotizacion sin persistir.
   * Impacto: Ecommerce publico; previene escrituras antes de DDL/autenticacion/rate limit.
   * Contrato: siempre bloqueado en esta fase; no escribe BD, no mueve inventario, no crea pedido.
   */
  public function cotizacionRegistrarBloqueada($datos = array()) {
    return $this->respuesta(true, "warning", "Registro de cotizacion ecommerce bloqueado en Fase 1", array(
      "bloqueado" => true,
      "dry_run_disponible" => true,
      "endpoint_dry_run" => "/ecommercePublico/cotizacion_dryrun",
      "no_escribe_bd" => true,
      "no_descuenta_inventario" => true,
      "no_crea_pedido" => true,
      "requisitos_activacion" => array(
        "aplicar_ddl_erp_ecommerce_con_respaldo",
        "activar_api_key_o_firma_hmac",
        "definir_rate_limit_y_captcha_si_aplica",
        "definir_politica_de_contacto_y_seguimiento_crm",
        "configurar_whatsapp_numero_principal"
      ),
      "body_recibido_resumen" => array(
        "items_total" => isset($datos["items"]) && is_array($datos["items"]) ? count($datos["items"]) : 0,
        "contacto_presente" => isset($datos["contacto"]) && is_array($datos["contacto"])
      )
    ));
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

      $limite = max(10, min(500, intval($this->valor($filtros, "limite", 50))));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: resumir readiness interno para decidir si el frontend externo puede arrancar con mocks o datos reales.
   * Impacto: Ecommerce publico; concentra bloqueos operativos sin ejecutar DDL ni publicar productos.
   * Contrato: solo lectura; no escribe BD, no expone stock exacto y no usa legacy `ecom_*` como fuente.
   */
  public function readinessFrontendInterna($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array(
          "senal_frontend" => "rojo_sin_conexion",
          "puede_iniciar_frontend_mock" => false,
          "puede_integrar_datos_reales" => false,
          "bloqueos_datos_reales" => array("conexion_mysql_no_disponible")
        ));
      }

      $tablas = array(
        "erp_ecommerce_publicaciones",
        "erp_ecommerce_configuracion",
        "erp_ecommerce_cotizaciones",
        "erp_ecommerce_cotizaciones_detalle",
        "erp_ecommerce_cotizaciones_eventos"
      );
      $tablasEstado = array();
      $tablasFaltantes = array();
      foreach ($tablas as $tabla) {
        $existe = $this->tablaExiste($db, $tabla);
        $tablasEstado[$tabla] = $existe;
        if (!$existe) {
          $tablasFaltantes[] = $tabla;
        }
      }

      $resumen = $this->resumenPublicabilidad($db);
      $publicaciones = array("borrador" => 0, "publicado" => 0, "pausado" => 0);
      if (!empty($tablasEstado["erp_ecommerce_publicaciones"])) {
        $stmt = $db->query("SELECT estatus_publicacion, COUNT(*) total FROM erp_ecommerce_publicaciones GROUP BY estatus_publicacion");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $estatus = (string) $fila["estatus_publicacion"];
          $publicaciones[$estatus] = intval($fila["total"]);
        }
      }

      $configPublica = $this->configuracionPublicaDefault();
      $configPersistida = false;
      if (!empty($tablasEstado["erp_ecommerce_configuracion"])) {
        $configPersistida = true;
        $stmt = $db->prepare("SELECT clave, valor FROM erp_ecommerce_configuracion WHERE estatus='activo'");
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $clave = (string) $fila["clave"];
          if (array_key_exists($clave, $configPublica)) {
            $configPublica[$clave] = (string) $fila["valor"];
          }
        }
      }

      $bloqueos = array();
      if (!empty($tablasFaltantes)) {
        $bloqueos[] = "ddl_ecommerce_publico_pendiente";
      }
      if (intval($publicaciones["publicado"]) <= 0) {
        $bloqueos[] = "sin_publicaciones_activas";
      }
      if (trim((string) $configPublica["whatsapp_numero_principal"]) === "") {
        $bloqueos[] = "whatsapp_no_configurado";
      }
      if (trim((string) $configPublica["cors_origenes_permitidos"]) === "") {
        $bloqueos[] = "cors_origenes_permitidos_no_configurado";
      }

      $puedeMock = true;
      $puedeReal = empty($bloqueos);
      $senal = $puedeReal ? "verde_datos_reales" : "amarillo_mock_contratos";
      $baseUrl = trim((string) $this->valor($opciones, "base_url", "http://panel.com.local"));

      return $this->respuesta(false, $puedeReal ? "success" : "warning", $puedeReal ? "Frontend listo para datos reales" : "Frontend puede iniciar con mocks y contratos", array(
        "senal_frontend" => $senal,
        "puede_iniciar_frontend_mock" => $puedeMock,
        "puede_integrar_datos_reales" => $puedeReal,
        "base_api_recomendada" => rtrim($baseUrl, "/") . "/ecommercePublico",
        "bloqueos_datos_reales" => array_values(array_unique($bloqueos)),
        "schema" => array(
          "tablas_estado" => $tablasEstado,
          "tablas_faltantes" => $tablasFaltantes,
          "ddl_pendiente" => !empty($tablasFaltantes)
        ),
        "publicaciones" => array(
          "total_publicadas" => intval($publicaciones["publicado"]),
          "total_borradores" => intval($publicaciones["borrador"]),
          "total_pausadas" => intval($publicaciones["pausado"]),
          "skus_publicables_fase_1" => intval($this->valor($resumen, "skus_publicables_fase_1", 0))
        ),
        "configuracion" => array(
          "persistida" => $configPersistida,
          "whatsapp_configurado" => trim((string) $configPublica["whatsapp_numero_principal"]) !== "",
          "cors_configurado" => trim((string) $configPublica["cors_origenes_permitidos"]) !== "",
          "url_sitio_publico" => $configPublica["url_sitio_publico"]
        ),
        "contratos" => array(
          "estado" => "/ecommercePublico/estado",
          "catalogo" => "/ecommercePublico/catalogo",
          "filtros" => "/ecommercePublico/filtros",
          "configuracion" => "/ecommercePublico/configuracion",
          "disponibilidad" => "/ecommercePublico/disponibilidad",
          "cotizacion_dryrun" => "/ecommercePublico/cotizacion_dryrun"
        ),
        "comandos_readonly" => array(
          "readiness_frontend" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_readiness_readonly.php",
          "bundle_activacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_activacion_bundle_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --lote=8",
          "secuencia_activacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_secuencia_activacion_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --id_sku=ID_SKU",
          "green_gate" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local"
        ),
        "comandos_apply_autorizados" => array(
          "nota" => "No ejecutar sin respaldo externo y autorizacion explicita.",
          "ddl" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_schema_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 --respaldo=RUTA_O_REFERENCIA",
          "configuracion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_configuracion_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CONFIGURACION_FASE1 --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND",
          "borrador" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU",
          "publicar_borrador" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicar_borrador_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR --respaldo=RUTA_O_REFERENCIA --id_sku=ID_SKU --confirmar_revision=1"
        ),
        "siguientes_pasos" => $puedeReal ? array(
          "iniciar_frontend_con_datos_reales",
          "validar_whatsapp_en_dispositivo",
          "monitorear_cotizacion_dryrun"
        ) : array(
          "iniciar_frontend_con_mocks_y_cliente_api",
          "aplicar_ddl_solo_con_respaldo_y_token",
          "configurar_whatsapp_y_cors",
          "crear_borradores_y_publicar_lote_inicial"
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "senal_frontend" => "rojo_error_readiness",
        "puede_iniciar_frontend_mock" => false,
        "puede_integrar_datos_reales" => false
      ));
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

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: reservar el contrato interno de guardado de publicaciones sin persistir.
   * Impacto: Ecommerce publico/Catalogo ERP; impide crear publicaciones hasta aplicar DDL y confirmar politica.
   * Contrato: siempre bloqueado en esta fase; no inserta ni actualiza `erp_ecommerce_publicaciones`.
   */
  public function guardarPublicacionBloqueada($datos = array()) {
    $idSku = intval($this->valor($datos, "id_sku", 0));
    $estatus = trim((string) $this->valor($datos, "estatus_publicacion", "borrador"));
    $plan = $idSku > 0 ? $this->planGuardarPublicacion($datos) : array();
    return $this->respuesta(true, "warning", "Guardado de publicacion ecommerce bloqueado en Fase 1", array(
      "bloqueado" => true,
      "no_escribe_bd" => true,
      "no_crea_publicacion" => true,
      "no_publica_sku" => true,
      "id_sku_recibido" => $idSku,
      "estatus_solicitado" => $estatus,
      "endpoint_preparacion" => "/ecommercePublico/publicaciones_preparar_erp?id_sku=" . $idSku,
      "plan_readonly" => $plan,
      "requisitos_activacion" => array(
        "aplicar_ddl_erp_ecommerce_con_respaldo",
        "validar_pantalla_readonly_de_publicaciones",
        "confirmar_politica_de_publicacion_por_sku",
        "definir_si_agotados_se_muestran_o_se_ocultan",
        "mantener_permiso_catalogo_editar",
        "registrar_auditoria_explicita_al_guardar"
      ),
      "campos_esperados_futuros" => array(
        "id_sku",
        "estatus_publicacion",
        "slug",
        "titulo_publico",
        "descripcion_publica",
        "presentacion_publica",
        "mascota_especie",
        "necesidades",
        "destacado",
        "orden",
        "permite_cotizacion",
        "permite_whatsapp",
        "mostrar_precio",
        "mostrar_disponibilidad"
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: generar el plan SQL de guardado de una publicacion ecommerce sin ejecutarlo.
   * Impacto: prepara curaduria interna como borrador sin publicar automaticamente ni tocar inventario.
   * Contrato: read-only; valida SKU, normaliza campos y devuelve SQL sugerido sin insertar/actualizar.
   */
  public function planGuardarPublicacion($datos = array()) {
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

      $preparacion = $this->prepararPublicacion(array("id_sku" => $idSku));
      $sugerida = $this->valor($preparacion, array("depurar", "publicacion_sugerida"), array());
      $bloqueos = $this->bloqueosPublicacion($fila);
      $tablaExiste = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
      if (!$tablaExiste) {
        $bloqueos[] = "tabla_erp_ecommerce_publicaciones_pendiente";
      }

      $estatusSolicitado = trim((string) $this->valor($datos, "estatus_publicacion", $this->valor($sugerida, "estatus_publicacion", "borrador")));
      $estatus = $estatusSolicitado === "borrador" ? "borrador" : "borrador";
      if ($estatusSolicitado !== "" && $estatusSolicitado !== "borrador") {
        $bloqueos[] = "fase1_solo_planifica_borrador_no_publicado";
      }

      $necesidades = $this->normalizarNecesidadesPublicacion($this->valor($datos, "necesidades", $this->valor($sugerida, "necesidades", array())));
      $publicacion = array(
        "id_producto_erp" => intval($fila["id_producto_erp"]),
        "id_sku" => intval($fila["id_sku"]),
        "canal" => "catalogo_publico",
        "estatus_publicacion" => $estatus,
        "slug" => $this->slugificar($this->valor($datos, "slug", $this->valor($sugerida, "slug", ""))),
        "titulo_publico" => trim((string) $this->valor($datos, "titulo_publico", $this->valor($sugerida, "titulo_publico", $fila["nombre_publico"]))),
        "descripcion_publica" => trim((string) $this->valor($datos, "descripcion_publica", $this->valor($sugerida, "descripcion_publica", ""))),
        "presentacion_publica" => trim((string) $this->valor($datos, "presentacion_publica", $this->valor($sugerida, "presentacion_publica", $fila["presentacion_base"]))),
        "mascota_especie" => trim((string) $this->valor($datos, "mascota_especie", $this->valor($sugerida, "mascota_especie", ""))),
        "necesidades" => $necesidades,
        "orden" => intval($this->valor($datos, "orden", $this->valor($sugerida, "orden", 0))),
        "destacado" => $this->booleanoPublicacion($this->valor($datos, "destacado", $this->valor($sugerida, "destacado", 0))),
        "permite_cotizacion" => $this->booleanoPublicacion($this->valor($datos, "permite_cotizacion", $this->valor($sugerida, "permite_cotizacion", 1))),
        "permite_whatsapp" => $this->booleanoPublicacion($this->valor($datos, "permite_whatsapp", $this->valor($sugerida, "permite_whatsapp", 1))),
        "mostrar_precio" => $this->booleanoPublicacion($this->valor($datos, "mostrar_precio", $this->valor($sugerida, "mostrar_precio", 1))),
        "mostrar_disponibilidad" => $this->booleanoPublicacion($this->valor($datos, "mostrar_disponibilidad", $this->valor($sugerida, "mostrar_disponibilidad", 1)))
      );

      if ($publicacion["slug"] === "") {
        $bloqueos[] = "slug_requerido";
      }
      if ($publicacion["titulo_publico"] === "") {
        $bloqueos[] = "titulo_publico_requerido";
      }
      if ($tablaExiste) {
        $conflicto = $this->conflictoSlugPublicacion($db, $publicacion["slug"], $idSku);
        if ($conflicto) {
          $bloqueos[] = "slug_ya_usado_por_otro_sku";
        }
      }

      $sql = $this->sqlUpsertPublicacion($publicacion);

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Plan de publicacion ecommerce generado sin ejecutar", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_publica_automaticamente" => true,
        "tabla_publicaciones_existe" => $tablaExiste,
        "publicable_fase_1" => empty($this->bloqueosPublicacion($fila)),
        "bloqueos_publicacion" => array_values(array_unique($bloqueos)),
        "producto_vivo_erp" => array(
          "id_producto_erp" => intval($fila["id_producto_erp"]),
          "id_sku" => intval($fila["id_sku"]),
          "sku" => $fila["sku"],
          "nombre" => $fila["nombre_publico"],
          "marca" => $fila["marca"],
          "categoria" => $fila["categoria"],
          "precio" => floatval($fila["precio"]),
          "moneda" => $fila["moneda"] ?: "MXN",
          "disponibilidad_publica_sugerida" => $this->disponibilidadPublicaSugerida($fila)
        ),
        "publicacion_normalizada" => $publicacion,
        "sql_total" => 1,
        "sha256_sql" => hash("sha256", $sql),
        "sql" => array($sql),
        "guardrails" => array(
          "estatus_forzado_borrador" => true,
          "no_toca_inventario" => true,
          "no_toca_ecom_legacy" => true,
          "no_modifica_precio_imagen_catalogo" => true,
          "precio_imagen_se_leen_vivos_desde_erp" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: guardar una publicacion ecommerce como borrador solo con autorizacion explicita.
   * Impacto: crea/actualiza curaduria publica sin publicar automaticamente, sin mover inventario y sin tocar precios/imagenes ERP.
   * Contrato: escribe BD solo con token `ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR`; fuerza estatus borrador.
   */
  public function guardarPublicacionBorradorAutorizada($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR") {
      return $this->respuesta(true, "warning", "Guardado de publicacion borrador bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR"
      ));
    }

    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
      }

      $plan = $this->planGuardarPublicacion($datos);
      $depurarPlan = $this->valor($plan, array("depurar"), array());
      $bloqueos = $this->valor($depurarPlan, array("bloqueos_publicacion"), array());
      $publicacion = $this->valor($depurarPlan, array("publicacion_normalizada"), array());

      if (!empty($bloqueos) || empty($publicacion)) {
        return $this->respuesta(true, "warning", "No se guardo publicacion por bloqueos de validacion", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => $bloqueos,
          "plan" => $plan
        ));
      }

      $db->beginTransaction();
      $stmt = $db->prepare("INSERT INTO erp_ecommerce_publicaciones
          (id_producto_erp, id_sku, canal, estatus_publicacion, slug, titulo_publico, descripcion_publica, presentacion_publica, mascota_especie, necesidades_json, orden, destacado, permite_cotizacion, permite_whatsapp, mostrar_precio, mostrar_disponibilidad, fecha_publicacion, fecha_registro, fecha_actualizacion)
        VALUES
          (:id_producto, :id_sku, :canal, 'borrador', :slug, :titulo, :descripcion, :presentacion, :mascota, :necesidades, :orden, :destacado, :permite_cotizacion, :permite_whatsapp, :mostrar_precio, :mostrar_disponibilidad, NULL, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          estatus_publicacion='borrador',
          slug=VALUES(slug),
          titulo_publico=VALUES(titulo_publico),
          descripcion_publica=VALUES(descripcion_publica),
          presentacion_publica=VALUES(presentacion_publica),
          mascota_especie=VALUES(mascota_especie),
          necesidades_json=VALUES(necesidades_json),
          orden=VALUES(orden),
          destacado=VALUES(destacado),
          permite_cotizacion=VALUES(permite_cotizacion),
          permite_whatsapp=VALUES(permite_whatsapp),
          mostrar_precio=VALUES(mostrar_precio),
          mostrar_disponibilidad=VALUES(mostrar_disponibilidad),
          fecha_actualizacion=NOW()");
      $stmt->execute(array(
        ":id_producto" => intval($publicacion["id_producto_erp"]),
        ":id_sku" => intval($publicacion["id_sku"]),
        ":canal" => "catalogo_publico",
        ":slug" => (string) $publicacion["slug"],
        ":titulo" => (string) $publicacion["titulo_publico"],
        ":descripcion" => (string) $publicacion["descripcion_publica"],
        ":presentacion" => (string) $publicacion["presentacion_publica"],
        ":mascota" => (string) $publicacion["mascota_especie"],
        ":necesidades" => json_encode($publicacion["necesidades"], JSON_UNESCAPED_UNICODE),
        ":orden" => intval($publicacion["orden"]),
        ":destacado" => intval($publicacion["destacado"]),
        ":permite_cotizacion" => intval($publicacion["permite_cotizacion"]),
        ":permite_whatsapp" => intval($publicacion["permite_whatsapp"]),
        ":mostrar_precio" => intval($publicacion["mostrar_precio"]),
        ":mostrar_disponibilidad" => intval($publicacion["mostrar_disponibilidad"])
      ));

      $stmtConsulta = $db->prepare("SELECT id_publicacion, id_producto_erp, id_sku, canal, estatus_publicacion, slug, titulo_publico
        FROM erp_ecommerce_publicaciones
        WHERE id_sku=:sku AND canal='catalogo_publico'
        LIMIT 1");
      $stmtConsulta->execute(array(":sku" => intval($publicacion["id_sku"])));
      $guardada = $stmtConsulta->fetch(PDO::FETCH_ASSOC);
      $db->commit();

      return $this->respuesta(false, "success", "Publicacion ecommerce guardada como borrador", array(
        "escribe_bd" => true,
        "estatus_forzado" => "borrador",
        "no_publica_automaticamente" => true,
        "no_toca_inventario" => true,
        "no_toca_ecom_legacy" => true,
        "publicacion" => $guardada,
        "plan_sha256_sql" => $this->valor($depurarPlan, "sha256_sql", "")
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: preparar la publicacion de un borrador ecommerce sin ejecutarla.
   * Impacto: fuerza revision previa antes de exponer un SKU al catalogo publico.
   * Contrato: read-only; no cambia estatus y no toca inventario.
   */
  public function planPublicarBorrador($datos = array()) {
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("read_only" => true));
      }
      $idPublicacion = intval($this->valor($datos, "id_publicacion", 0));
      $idSku = intval($this->valor($datos, "id_sku", 0));
      $bloqueos = array();
      $tablaExiste = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
      if (!$tablaExiste) {
        $bloqueos[] = "tabla_erp_ecommerce_publicaciones_pendiente";
      }
      if ($idPublicacion <= 0 && $idSku <= 0) {
        $bloqueos[] = "id_publicacion_o_id_sku_requerido";
      }

      $publicacion = array();
      if ($tablaExiste && ($idPublicacion > 0 || $idSku > 0)) {
        $where = $idPublicacion > 0 ? "id_publicacion=:id" : "id_sku=:sku AND canal='catalogo_publico'";
        $stmt = $db->prepare("SELECT * FROM erp_ecommerce_publicaciones WHERE " . $where . " LIMIT 1");
        $stmt->execute($idPublicacion > 0 ? array(":id" => $idPublicacion) : array(":sku" => $idSku));
        $publicacion = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
        if (empty($publicacion)) {
          $bloqueos[] = "publicacion_borrador_no_encontrada";
        }
      }

      if (!empty($publicacion)) {
        if ((string) $publicacion["estatus_publicacion"] !== "borrador") {
          $bloqueos[] = "solo_borrador_puede_publicarse";
        }
        if (trim((string) $publicacion["slug"]) === "") {
          $bloqueos[] = "slug_requerido";
        }
        if (trim((string) $publicacion["titulo_publico"]) === "") {
          $bloqueos[] = "titulo_publico_requerido";
        }
        $idSku = intval($publicacion["id_sku"]);
      }

      $candidato = $idSku > 0 ? $this->consultarCandidatoPorSku($db, $idSku) : null;
      if ($candidato) {
        $bloqueos = array_merge($bloqueos, $this->bloqueosPublicacion($candidato));
        $disponibilidad = $this->disponibilidadPublicaSugerida($candidato);
        if ($disponibilidad === "agotado" && intval($this->valor($datos, "confirmar_agotado", 0)) !== 1) {
          $bloqueos[] = "sku_agotado_requiere_confirmar_agotado";
        }
      } elseif ($idSku > 0) {
        $bloqueos[] = "sku_no_encontrado_o_inactivo";
      }

      if (intval($this->valor($datos, "confirmar_revision", 0)) !== 1) {
        $bloqueos[] = "confirmar_revision_requerido";
      }

      $sql = $idPublicacion > 0
        ? "UPDATE `erp_ecommerce_publicaciones` SET `estatus_publicacion`='publicado', `fecha_publicacion`=COALESCE(`fecha_publicacion`, NOW()), `fecha_actualizacion`=NOW() WHERE `id_publicacion`=" . intval($idPublicacion) . " AND `estatus_publicacion`='borrador' LIMIT 1;"
        : "UPDATE `erp_ecommerce_publicaciones` SET `estatus_publicacion`='publicado', `fecha_publicacion`=COALESCE(`fecha_publicacion`, NOW()), `fecha_actualizacion`=NOW() WHERE `id_sku`=" . intval($idSku) . " AND `canal`='catalogo_publico' AND `estatus_publicacion`='borrador' LIMIT 1;";

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", "Plan de publicacion de borrador generado sin ejecutar", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "tabla_publicaciones_existe" => $tablaExiste,
        "bloqueos_publicacion" => array_values(array_unique($bloqueos)),
        "publicacion_actual" => $publicacion,
        "producto_vivo_erp" => $candidato ? array(
          "id_sku" => intval($candidato["id_sku"]),
          "sku" => $candidato["sku"],
          "nombre" => $candidato["nombre_publico"],
          "marca" => $candidato["marca"],
          "categoria" => $candidato["categoria"],
          "precio" => floatval($candidato["precio"]),
          "moneda" => $candidato["moneda"] ?: "MXN",
          "disponibilidad_publica_sugerida" => $this->disponibilidadPublicaSugerida($candidato)
        ) : array(),
        "sql_total" => 1,
        "sha256_sql" => hash("sha256", $sql),
        "sql" => array($sql),
        "guardrails" => array(
          "requiere_confirmar_revision" => true,
          "requiere_confirmar_agotado_si_aplica" => true,
          "no_toca_inventario" => true,
          "no_toca_ecom_legacy" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: publicar un borrador ecommerce solo con autorizacion explicita.
   * Impacto: expone el SKU en catalogo publico; no descuenta inventario ni modifica catalogo ERP.
   * Contrato: escribe BD solo con token `ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR`.
   */
  public function publicarBorradorAutorizado($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR") {
      return $this->respuesta(true, "warning", "Publicacion de borrador bloqueada", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR"
      ));
    }

    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
      }
      $plan = $this->planPublicarBorrador($datos);
      $bloqueos = $this->valor($plan, array("depurar", "bloqueos_publicacion"), array());
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "No se publico borrador por bloqueos", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => $bloqueos,
          "plan" => $plan
        ));
      }

      $idPublicacion = intval($this->valor($datos, "id_publicacion", 0));
      $idSku = intval($this->valor($datos, "id_sku", 0));
      $db->beginTransaction();
      if ($idPublicacion > 0) {
        $stmt = $db->prepare("UPDATE erp_ecommerce_publicaciones
          SET estatus_publicacion='publicado', fecha_publicacion=COALESCE(fecha_publicacion, NOW()), fecha_actualizacion=NOW()
          WHERE id_publicacion=:id AND estatus_publicacion='borrador'
          LIMIT 1");
        $stmt->execute(array(":id" => $idPublicacion));
      } else {
        $stmt = $db->prepare("UPDATE erp_ecommerce_publicaciones
          SET estatus_publicacion='publicado', fecha_publicacion=COALESCE(fecha_publicacion, NOW()), fecha_actualizacion=NOW()
          WHERE id_sku=:sku AND canal='catalogo_publico' AND estatus_publicacion='borrador'
          LIMIT 1");
        $stmt->execute(array(":sku" => $idSku));
      }
      $afectados = $stmt->rowCount();
      $consulta = $db->prepare("SELECT id_publicacion, id_producto_erp, id_sku, canal, estatus_publicacion, slug, titulo_publico, fecha_publicacion
        FROM erp_ecommerce_publicaciones
        WHERE " . ($idPublicacion > 0 ? "id_publicacion=:id" : "id_sku=:sku AND canal='catalogo_publico'") . "
        LIMIT 1");
      $consulta->execute($idPublicacion > 0 ? array(":id" => $idPublicacion) : array(":sku" => $idSku));
      $publicacion = $consulta->fetch(PDO::FETCH_ASSOC);
      $db->commit();

      return $this->respuesta($afectados <= 0, $afectados > 0 ? "success" : "warning", $afectados > 0 ? "Borrador ecommerce publicado" : "No se encontro borrador para publicar", array(
        "escribe_bd" => $afectados > 0,
        "filas_afectadas" => $afectados,
        "publicacion" => $publicacion,
        "no_toca_inventario" => true,
        "no_toca_ecom_legacy" => true,
        "plan_sha256_sql" => $this->valor($plan, array("depurar", "sha256_sql"), "")
      ));
    } catch (Exception $e) {
      if (isset($db) && $db && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
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
      "cors_origenes_permitidos" => "",
      "cotizacion_habilitada" => "1",
      "mostrar_stock_exacto" => "0",
      "modo_sin_stock" => "consultar",
      "texto_total_estimado" => "Total estimado sujeto a confirmacion",
      "url_sitio_publico" => ""
    );
  }

  private function descripcionConfiguracionPublica($clave) {
    $descripciones = array(
      "moneda_default" => "Moneda visible por defecto del catalogo publico.",
      "whatsapp_numero_principal" => "Numero WhatsApp receptor de cotizaciones publicas.",
      "whatsapp_mensaje_base" => "Texto inicial para mensaje de cotizacion WhatsApp.",
      "cors_origenes_permitidos" => "Origenes externos permitidos para consumir API publica.",
      "cotizacion_habilitada" => "Permite cotizacion dry-run desde frontend.",
      "mostrar_stock_exacto" => "Debe mantenerse 0 en Fase 1.",
      "modo_sin_stock" => "Politica publica cuando no hay disponibilidad clara.",
      "texto_total_estimado" => "Leyenda para totales calculados sin confirmacion.",
      "url_sitio_publico" => "URL del proyecto ecommerce externo."
    );
    return isset($descripciones[$clave]) ? $descripciones[$clave] : "Configuracion publica ecommerce.";
  }

  private function sqlQuote($valor) {
    return "'" . str_replace("'", "''", (string) $valor) . "'";
  }

  private function consultarPublicacionParaCotizacion($db, $item) {
    $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'");
    $params = array();
    $idPublicacion = intval($this->valor($item, "id_publicacion", 0));
    $idSku = intval($this->valor($item, "id_sku", 0));
    $slug = trim((string) $this->valor($item, "slug", ""));
    if ($idPublicacion > 0) {
      $where[] = "pub.id_publicacion=:publicacion";
      $params[":publicacion"] = $idPublicacion;
    } elseif ($slug !== "") {
      $where[] = "pub.slug=:slug";
      $params[":slug"] = $slug;
    } elseif ($idSku > 0) {
      $where[] = "pub.id_sku=:sku";
      $params[":sku"] = $idSku;
    } else {
      return null;
    }
    $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " LIMIT 1");
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
  }

  private function mensajeWhatsAppPreview($lineas, $total) {
    if (empty($lineas)) {
      return "";
    }
    $mensaje = array("Hola, quiero cotizar estos productos:", "");
    foreach ($lineas as $linea) {
      $mensaje[] = $linea["renglon"] . ". " . $linea["nombre"] . " - Cant. " . $linea["cantidad"] . " - $" . number_format($linea["subtotal"], 2) . " " . $linea["moneda"];
    }
    $mensaje[] = "";
    $mensaje[] = "Total estimado: $" . number_format($total, 2) . " MXN";
    $mensaje[] = "Sujeto a confirmacion de disponibilidad.";
    return implode("\n", $mensaje);
  }

  private function contratoAutenticacionFutura() {
    return array(
      "estado_actual" => "no_requerida_en_fase1_readonly",
      "modo_recomendado" => "api_key_hmac_sha256",
      "headers" => array(
        "X-Ecommerce-Api-Key" => "Identificador publico del canal ecommerce. No es secreto.",
        "X-Ecommerce-Timestamp" => "Unix timestamp en segundos.",
        "X-Ecommerce-Nonce" => "Valor unico por request para reducir replay.",
        "X-Ecommerce-Signature" => "HMAC-SHA256 en hex/base64 segun se defina al activar."
      ),
      "firma_canonica" => array(
        "linea_1" => "HTTP_METHOD",
        "linea_2" => "REQUEST_PATH",
        "linea_3" => "QUERY_STRING_ORDENADO",
        "linea_4" => "X_ECOMMERCE_TIMESTAMP",
        "linea_5" => "X_ECOMMERCE_NONCE",
        "linea_6" => "SHA256_BODY_HEX"
      ),
      "configuracion_privada_recomendada" => array(
        "api_key_publica" => "Guardar identificador del canal.",
        "api_secret_hash_o_cifrado" => "No exponer por endpoint publico.",
        "api_firma_requerida" => "0 en Fase 1; 1 antes de exponer POST o dominios publicos.",
        "api_tolerancia_reloj_segundos" => "Default recomendado 300."
      ),
      "guardrails" => array(
        "no_exponer_secretos" => true,
        "no_requerir_en_local_readonly" => true,
        "requerir_antes_de_post_publicos" => true,
        "registrar_fallos_sin_loggear_secretos" => true
      )
    );
  }

  private function normalizarNecesidadesPublicacion($valor) {
    if (is_string($valor)) {
      $decodificado = json_decode($valor, true);
      if (is_array($decodificado)) {
        $valor = $decodificado;
      } else {
        $valor = preg_split('/[\r\n,]+/', $valor);
      }
    }
    if (!is_array($valor)) {
      return array();
    }
    $permitidas = array("alimento", "premio", "higiene", "salud", "paseo", "habitat", "juguete", "estetica");
    $limpias = array();
    foreach ($valor as $necesidad) {
      $n = strtolower(trim((string) $necesidad));
      if ($n !== "" && in_array($n, $permitidas, true) && !in_array($n, $limpias, true)) {
        $limpias[] = $n;
      }
    }
    return $limpias;
  }

  private function booleanoPublicacion($valor) {
    if (is_bool($valor)) {
      return $valor ? 1 : 0;
    }
    $valor = strtolower(trim((string) $valor));
    return in_array($valor, array("1", "true", "si", "on", "yes"), true) ? 1 : 0;
  }

  private function conflictoSlugPublicacion($db, $slug, $idSku) {
    if ($slug === "") {
      return false;
    }
    $stmt = $db->prepare("SELECT id_publicacion, id_sku FROM erp_ecommerce_publicaciones WHERE slug=:slug AND id_sku<>:sku LIMIT 1");
    $stmt->execute(array(":slug" => $slug, ":sku" => intval($idSku)));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
  }

  private function sqlUpsertPublicacion($publicacion) {
    $necesidadesJson = json_encode($publicacion["necesidades"], JSON_UNESCAPED_UNICODE);
    return "INSERT INTO `erp_ecommerce_publicaciones` " .
      "(`id_producto_erp`, `id_sku`, `canal`, `estatus_publicacion`, `slug`, `titulo_publico`, `descripcion_publica`, `presentacion_publica`, `mascota_especie`, `necesidades_json`, `orden`, `destacado`, `permite_cotizacion`, `permite_whatsapp`, `mostrar_precio`, `mostrar_disponibilidad`, `fecha_publicacion`, `fecha_registro`, `fecha_actualizacion`) VALUES (" .
      intval($publicacion["id_producto_erp"]) . ", " .
      intval($publicacion["id_sku"]) . ", " .
      $this->sqlQuote($publicacion["canal"]) . ", " .
      $this->sqlQuote($publicacion["estatus_publicacion"]) . ", " .
      $this->sqlQuote($publicacion["slug"]) . ", " .
      $this->sqlQuote($publicacion["titulo_publico"]) . ", " .
      $this->sqlQuote($publicacion["descripcion_publica"]) . ", " .
      $this->sqlQuote($publicacion["presentacion_publica"]) . ", " .
      $this->sqlQuote($publicacion["mascota_especie"]) . ", " .
      $this->sqlQuote($necesidadesJson) . ", " .
      intval($publicacion["orden"]) . ", " .
      intval($publicacion["destacado"]) . ", " .
      intval($publicacion["permite_cotizacion"]) . ", " .
      intval($publicacion["permite_whatsapp"]) . ", " .
      intval($publicacion["mostrar_precio"]) . ", " .
      intval($publicacion["mostrar_disponibilidad"]) . ", " .
      "NULL, NOW(), NOW()) ON DUPLICATE KEY UPDATE " .
      "`estatus_publicacion`='borrador', " .
      "`slug`=VALUES(`slug`), " .
      "`titulo_publico`=VALUES(`titulo_publico`), " .
      "`descripcion_publica`=VALUES(`descripcion_publica`), " .
      "`presentacion_publica`=VALUES(`presentacion_publica`), " .
      "`mascota_especie`=VALUES(`mascota_especie`), " .
      "`necesidades_json`=VALUES(`necesidades_json`), " .
      "`orden`=VALUES(`orden`), " .
      "`destacado`=VALUES(`destacado`), " .
      "`permite_cotizacion`=VALUES(`permite_cotizacion`), " .
      "`permite_whatsapp`=VALUES(`permite_whatsapp`), " .
      "`mostrar_precio`=VALUES(`mostrar_precio`), " .
      "`mostrar_disponibilidad`=VALUES(`mostrar_disponibilidad`), " .
      "`fecha_actualizacion`=NOW();";
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
    if (is_array($clave)) {
      $actual = $datos;
      foreach ($clave as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
          return $default;
        }
        $actual = $actual[$segmento];
      }
      return $actual;
    }
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
