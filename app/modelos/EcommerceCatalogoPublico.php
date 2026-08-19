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
        "ecom_legacy_no_es_fuente" => true,
        "api_multi_canal_en_diseno" => true
      ),
      "canales_api" => array(
        "estado" => "diseno_readonly",
        "frontend_propio" => array(
          "codigo_sugerido" => "artiani_web",
          "uso" => "sitio oficial Artiani",
          "origenes" => array("http://artiani.com.local", "https://artiani.com.mx"),
          "scopes_fase_1" => array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun")
        ),
        "partner_mayoreo" => array(
          "codigo_sugerido" => "partner_mayoreo_001",
          "uso" => "aliado autorizado que muestra catalogo y genera oportunidades",
          "requiere_backend_para_secretos" => true,
          "no_pegar_secret_en_javascript" => true,
          "scopes_fase_1" => array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun")
        ),
        "documento" => "Referencia interna ERP; el frontend externo debe consumir /ecommercePublico/frontend_handoff y /ecommercePublico/contratos."
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
          "ruta" => "/ecommercePublico/frontend_handoff",
          "descripcion" => "Handoff vivo para frontend externo: base URL, endpoints, orden de integracion, ejemplos HTTP, contratos UI y reglas no-go sin leer archivos del ERP.",
          "parametros" => array("limite" => "1-6 productos ejemplo, default 2."),
          "respuesta_depurar" => array("estado_actual", "variables_env_frontend", "endpoints_para_consumir", "orden_recomendado_integracion", "pruebas_con_api", "contratos_ui", "ejemplos", "no_usar")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/configuracion_inicial",
          "descripcion" => "Endpoint recomendado de arranque: estado, configuracion, filtros, navegacion, secciones, politicas y canales.",
          "parametros" => array("limite_secciones" => "1-12, default 6."),
          "respuesta_depurar" => array("ready", "estado", "configuracion", "filtros", "navegacion", "secciones", "politicas", "canales", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/bootstrap",
          "descripcion" => "Alias legacy de configuracion_inicial; se mantiene por compatibilidad, pero frontend nuevo debe usar configuracion_inicial.",
          "parametros" => array("limite_secciones" => "1-12, default 6."),
          "respuesta_depurar" => array("ready", "estado", "configuracion", "filtros", "navegacion", "secciones", "politicas", "canales", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/contenido_manifest",
          "descripcion" => "Manifest del CMS ligero: plantillas, slots, tipos de bloque y paginas soportadas.",
          "parametros" => array("plantilla" => "artiani_default por defecto."),
          "respuesta_depurar" => array("cms", "plantilla_activa", "plantillas", "tipos_bloque", "paginas_soportadas", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/contenido_pagina",
          "descripcion" => "Estructura editorial de una pagina para renderizar banners, colecciones y bloques desde frontend.",
          "parametros" => array("pagina" => "home|categoria|catalogo.", "plantilla" => "artiani_default por defecto.", "categoria" => "slug/codigo cuando pagina=categoria."),
          "respuesta_depurar" => array("pagina", "plantilla", "fuente", "slots", "resumen", "links", "guardrails")
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
            "disponibilidad" => "disponible|pocas_piezas|consultar_disponibilidad|agotado; opcional.",
            "destacado" => "1 para solo destacados.",
            "orden" => "relevancia|nombre|precio_asc|precio_desc|recientes.",
            "pagina" => "Pagina, default 1.",
            "limite" => "1-60, default 24."
          ),
          "respuesta_depurar" => array("configurado", "items", "paginacion", "frontend", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/catalogo_manifest",
          "descripcion" => "Manifiesto robusto de catalogo para frontend: filtros, ordenamientos, limites, ejemplos, endpoints relacionados y guardrails.",
          "parametros" => array("limite_preview" => "1-6 productos ejemplo, default 3."),
          "respuesta_depurar" => array("fase", "estado_catalogo", "parametros_soportados", "ordenamientos", "endpoints_relacionados", "ejemplos", "preview", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/fase_2_checklist",
          "descripcion" => "Checklist de cierre de Fase 2 para frontend externo: endpoints obligatorios, orden de integracion, escenarios y criterios para pasar a Fase 3.",
          "respuesta_depurar" => array("fase", "estado", "endpoints_obligatorios", "orden_integracion", "escenarios_prueba", "criterios_pase_fase_3", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/producto/{slug}",
          "descripcion" => "Detalle publico de una publicacion con estatus publicado, variantes, relacionados y SEO basico.",
          "respuesta_depurar" => array("item", "variantes", "relacionados", "breadcrumbs", "seo", "acciones", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/filtros",
          "descripcion" => "Filtros disponibles derivados de publicaciones vigentes.",
          "respuesta_depurar" => array("mascotas", "necesidades", "marcas", "categorias", "disponibilidad", "fase_2")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/categorias",
          "descripcion" => "Arbol publico de categorias para mega menu, home y landings SEO con rutas /categoria/{slug}.",
          "respuesta_depurar" => array("items", "arbol", "resumen", "frontend", "cms_pendiente", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/marcas",
          "descripcion" => "Marcas publicas con slug estable para landings SEO /marca/{slug}.",
          "respuesta_depurar" => array("items", "resumen", "frontend", "cms_pendiente", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/catalogo_filtros",
          "descripcion" => "Facets contextuales de catalogo con conteos reales segun filtros activos.",
          "respuesta_depurar" => array("aplicados", "resultado_actual", "categorias", "marcas", "precios", "ordenamientos", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/busqueda_sugerencias",
          "descripcion" => "Sugerencias publicas para buscador: productos, marcas, categorias, mascotas y necesidades.",
          "parametros" => array("q" => "Texto opcional.", "limite" => "1-12 por grupo, default 6."),
          "respuesta_depurar" => array("q", "grupos", "resumen", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/navegacion",
          "descripcion" => "Navegacion publica lista para menus, chips y rutas por mascota, necesidad, categoria, marca y disponibilidad.",
          "parametros" => array("limite" => "1-30 por grupo, default 12."),
          "respuesta_depurar" => array("primaria", "mascotas", "necesidades", "categorias", "categorias_arbol", "marcas", "disponibilidad", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/secciones",
          "descripcion" => "Bloques de catalogo listos para home/frontend: destacados, disponibles, mascotas y necesidades.",
          "parametros" => array(
            "limite" => "1-12 por seccion, default 6.",
            "incluir_vacias" => "1 para devolver secciones sin items; default 0."
          ),
          "respuesta_depurar" => array("configurado", "secciones", "fase_2", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/politicas",
          "descripcion" => "Politicas publicas base para terminos, privacidad, WhatsApp, disponibilidad, facturacion y tracking.",
          "respuesta_depurar" => array("configurado", "items", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/politica/{slug}",
          "descripcion" => "Detalle de una politica publica por codigo/slug.",
          "respuesta_depurar" => array("item", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/taxonomia_mascotas",
          "descripcion" => "Taxonomia publica para navegar por mascota y necesidad.",
          "respuesta_depurar" => array("configurado", "mascotas", "necesidades", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/configuracion",
          "descripcion" => "Configuracion publica del canal: moneda, WhatsApp, cotizacion y politicas visibles.",
          "respuesta_depurar" => array("configurado", "configuracion")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/canales_estado",
          "descripcion" => "Estado publico seguro de la capa multi-canal/API para Artiani y partners.",
          "respuesta_depurar" => array("configurado", "modo", "tablas", "canales", "autenticacion", "activacion", "guardrails")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/seo",
          "descripcion" => "Metadatos SEO/descubrimiento para que el frontend genere title, description, sitemap, robots, rutas y JSON-LD.",
          "parametros" => array("limite" => "1-200 productos, default 100."),
          "respuesta_depurar" => array("configurado", "meta", "robots", "sitemap", "sitemap_xml_sugerido", "rutas", "json_ld", "fase_2", "resumen")
        ),
        array(
          "metodo" => "GET",
          "ruta" => "/ecommercePublico/disponibilidad",
          "descripcion" => "Disponibilidad publica simple por id_sku o slug.",
          "parametros" => array("id_sku" => "Opcional si se envia slug.", "slug" => "Opcional si se envia id_sku."),
          "estados" => $this->estadosDisponibilidadPublica(),
          "respuesta_depurar" => array("disponibilidad", "mostrar_cantidad_exacta=false", "frontend", "fase_2")
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
          "respuesta_depurar" => array("dry_run", "lineas", "totales", "resumen", "advertencias", "bloqueos", "whatsapp_preview", "frontend", "fase_2")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/cotizacion_preflight",
          "descripcion" => "Valida carrito, contacto y consentimiento antes de abrir WhatsApp o preparar registro futuro.",
          "estado" => "preflight-readonly",
          "body" => array(
            "items" => array(
              array("id_publicacion" => "int opcional", "slug" => "string opcional", "id_sku" => "int opcional", "cantidad" => "decimal > 0")
            ),
            "contacto" => array("nombre" => "string recomendado", "telefono" => "string recomendado", "correo" => "string opcional", "mensaje" => "string opcional", "acepta_whatsapp" => "bool recomendado", "acepta_politicas" => "bool|string[] recomendado"),
            "utm" => "object opcional",
            "acepta_contacto_whatsapp" => "bool legacy compatible en raiz",
            "politicas_aceptadas" => "string[] legacy compatible en raiz"
          ),
          "respuesta_depurar" => array("preflight", "folio_preliminar", "listo_para_whatsapp", "listo_para_registro_futuro", "contacto", "validacion_contacto", "consentimiento", "cta", "dry_run", "fase_2")
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
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/facturacion_solicitar",
          "descripcion" => "Preflight de solicitud de factura por folio; valida forma y consentimiento sin guardar datos fiscales.",
          "estado" => "preflight-readonly",
          "body" => array(
            "folio_compra" => "string requerido",
            "fecha_compra" => "YYYY-MM-DD opcional",
            "importe" => "decimal opcional",
            "datos_fiscales" => array("rfc" => "string requerido futuro", "razon_social" => "string requerido futuro", "regimen_fiscal" => "string", "uso_cfdi" => "string", "codigo_postal" => "string"),
            "contacto" => array("correo" => "string requerido futuro", "telefono" => "string opcional"),
            "acepta_aviso_privacidad" => "bool requerido para activar persistencia"
          ),
          "respuesta_depurar" => array("preflight", "folio_solicitud_preliminar", "listo_para_registro_futuro", "bloqueos", "sql_plan")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/analytics_sesion",
          "descripcion" => "Preflight de sesion anonima analytics; devuelve session_id_hash planeado y bloquea datos personales.",
          "estado" => "preflight-readonly",
          "body" => array(
            "session_id" => "string anonimo requerido",
            "canal" => "web_publica|partner opcional",
            "ruta" => "string opcional",
            "referrer" => "string opcional",
            "utm_source" => "string opcional",
            "utm_medium" => "string opcional",
            "utm_campaign" => "string opcional",
            "dispositivo" => "desktop|mobile|tablet opcional",
            "metadata" => "object opcional sin datos personales"
          ),
          "respuesta_depurar" => array("preflight", "sesion_normalizada", "datos_personales_detectados", "bloqueos", "sql_plan")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/evento_navegacion",
          "descripcion" => "Preflight de evento anonimo de navegacion; valida analytics sin guardar datos personales.",
          "estado" => "preflight-readonly",
          "body" => array(
            "session_id" => "string anonimo requerido futuro",
            "tipo_evento" => "page_view|view_product|search|select_mascota|select_necesidad|add_to_quote|remove_from_quote|quote_dryrun|quote_preflight|open_whatsapp|facturacion_view|facturacion_submit",
            "ruta" => "string opcional",
            "referrer" => "string opcional",
            "utm_source" => "string opcional",
            "utm_medium" => "string opcional",
            "utm_campaign" => "string opcional",
            "mascota" => "string opcional",
            "necesidad" => "string opcional",
            "id_publicacion" => "int opcional",
            "id_sku" => "int opcional",
            "slug" => "string opcional",
            "metadata" => "object opcional sin datos personales"
          ),
          "respuesta_depurar" => array("preflight", "evento_normalizado", "datos_personales_detectados", "bloqueos", "sql_plan")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/busqueda_registrar",
          "descripcion" => "Preflight de busqueda anonima; prepara historial de demanda, sin resultados y mascotas/necesidades.",
          "estado" => "preflight-readonly",
          "body" => array(
            "session_id" => "string anonimo requerido futuro",
            "query" => "string requerido",
            "mascota" => "string opcional",
            "necesidad" => "string opcional",
            "resultados_total" => "int opcional",
            "filtros" => "object opcional sin datos personales"
          ),
          "respuesta_depurar" => array("preflight", "busqueda_normalizada", "sin_resultados", "bloqueos", "sql_plan")
        ),
        array(
          "metodo" => "POST",
          "ruta" => "/ecommercePublico/analytics_conversion",
          "descripcion" => "Preflight de conversion anonima; prepara embudo add-to-quote, dry-run, preflight y WhatsApp sin checkout ni venta.",
          "estado" => "preflight-readonly",
          "body" => array(
            "session_id" => "string anonimo requerido",
            "tipo_conversion" => "add_to_quote|remove_from_quote|quote_dryrun|quote_preflight|open_whatsapp|facturacion_submit",
            "ruta" => "string opcional",
            "id_publicacion" => "int opcional",
            "id_sku" => "int opcional",
            "slug" => "string opcional",
            "metadata" => "object opcional sin datos personales"
          ),
          "respuesta_depurar" => array("preflight", "conversion_normalizada", "datos_personales_detectados", "bloqueos", "sql_plan")
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
        "mascota_especie" => "string|null legacy principal",
        "mascotas" => "string[]",
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
        "no_granel" => true,
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-01
   * Proposito: entregar al proyecto frontend externo un paquete de integracion consumible por API.
   * Impacto: Ecommerce publico; evita que frontend lea documentos o archivos internos de panel_de_control.
   * Contrato: solo lectura; compone estado, contratos, ejemplos y guardrails sin escribir BD ni exponer secretos.
   */
  public function frontendHandoffPublico($opciones = array()) {
    try {
      $limite = max(1, min(6, intval($this->valor($opciones, "limite", 2))));
      $baseUrl = trim((string) $this->valor($opciones, "base_url", "http://panel.com.local"));
      $baseUrl = rtrim($baseUrl === "" ? "http://panel.com.local" : $baseUrl, "/");
      $basePath = "/ecommercePublico";
      $baseApi = $baseUrl . $basePath;

      $readiness = $this->readinessFrontendInterna(array("base_url" => $baseUrl));
      $contratos = $this->contratosApiPublicos();
      $bootstrap = $this->bootstrapPublico(array("limite_secciones" => min(6, $limite)));
      $manifest = $this->catalogoManifestPublico(array("limite_preview" => min(3, $limite)));
      $checklist = $this->fase2ChecklistPublico();
      $catalogo = $this->catalogoPublico(array("limite" => $limite));
      $categorias = $this->categoriasPublicas(array("limite" => 200));
      $seo = $this->seoPublico(array("limite" => min(20, max(6, $limite))));
      $items = $this->valor($catalogo, array("depurar", "items"), array());
      $primerItem = !empty($items) && is_array($items[0]) ? $items[0] : null;
      $slugEjemplo = is_array($primerItem) ? (string) $this->valor($primerItem, "slug", "") : "";
      $idPublicacionEjemplo = is_array($primerItem) ? intval($this->valor($primerItem, "id_publicacion", 0)) : 0;
      $idSkuEjemplo = is_array($primerItem) ? intval($this->valor($primerItem, "id_sku", 0)) : 0;

      $producto = $slugEjemplo !== "" ? $this->productoPublico($slugEjemplo) : null;
      $disponibilidad = $slugEjemplo !== "" ? $this->disponibilidadPublica(array("slug" => $slugEjemplo)) : null;
      $payloadCotizacion = array(
        "items" => array(array(
          "slug" => $slugEjemplo !== "" ? $slugEjemplo : "slug-producto-publicado",
          "id_publicacion" => $idPublicacionEjemplo > 0 ? $idPublicacionEjemplo : null,
          "id_sku" => $idSkuEjemplo > 0 ? $idSkuEjemplo : null,
          "cantidad" => 1
        )),
        "contacto" => array("nombre" => "Cliente demo", "telefono" => "3322068429"),
        "acepta_contacto_whatsapp" => true,
        "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp")
      );
      $dryrun = $this->cotizacionDryRun($payloadCotizacion);
      $preflight = $this->cotizacionPreflight($payloadCotizacion);

      return $this->respuesta(false, "success", "Handoff frontend ecommerce disponible por API", array(
        "estado_actual" => array(
          "senal_frontend" => $this->valor($readiness, array("depurar", "senal_frontend"), "amarillo_mock_contratos"),
          "puede_iniciar_frontend_mock" => (bool) $this->valor($readiness, array("depurar", "puede_iniciar_frontend_mock"), true),
          "puede_integrar_datos_reales" => (bool) $this->valor($readiness, array("depurar", "puede_integrar_datos_reales"), false),
          "bloqueos_datos_reales" => $this->valor($readiness, array("depurar", "bloqueos_datos_reales"), array()),
          "publicaciones" => $this->valor($readiness, array("depurar", "publicaciones"), array())
        ),
        "variables_env_frontend" => array(
          "VITE_ERP_API_BASE_URL" => $baseUrl,
          "VITE_ERP_ECOMMERCE_BASE_PATH" => $basePath,
          "VITE_ERP_ECOMMERCE_API_VERSION" => "fase1-2026-07-12"
        ),
        "endpoints_para_consumir" => $this->endpointsFrontendHandoff(),
        "orden_recomendado_integracion" => array(
          "GET /ecommercePublico/frontend_handoff",
          "GET /ecommercePublico/configuracion_inicial",
          "GET /ecommercePublico/contenido_manifest",
          "GET /ecommercePublico/contenido_pagina?pagina=home",
          "GET /ecommercePublico/catalogo_manifest",
          "GET /ecommercePublico/fase_2_checklist",
          "GET /ecommercePublico/filtros",
          "GET /ecommercePublico/categorias",
          "GET /ecommercePublico/navegacion",
          "GET /ecommercePublico/catalogo?limite=24",
          "GET /ecommercePublico/producto/{slug}",
          "GET /ecommercePublico/disponibilidad?slug={slug}",
          "GET /ecommercePublico/seo",
          "POST /ecommercePublico/cotizacion_dryrun",
          "POST /ecommercePublico/cotizacion_preflight"
        ),
        "pruebas_con_api" => $this->pruebasFrontendHandoff($baseApi, $slugEjemplo, $payloadCotizacion),
        "contratos_ui" => array(
          "configuracion_inicial" => array("depurar.ready", "depurar.fase_2.primer_render", "depurar.guardrails"),
          "contenido_manifest" => array("depurar.plantillas", "depurar.tipos_bloque", "depurar.guardrails"),
          "contenido_pagina" => array("depurar.slots", "depurar.resumen", "depurar.guardrails"),
          "catalogo_manifest" => array("depurar.parametros_soportados", "depurar.ordenamientos", "depurar.endpoints_relacionados", "depurar.guardrails"),
          "fase_2_checklist" => array("depurar.endpoints_obligatorios", "depurar.orden_integracion", "depurar.escenarios_prueba", "depurar.criterios_pase_fase_3"),
          "catalogo" => array("depurar.items", "depurar.paginacion", "depurar.frontend", "depurar.fase_2", "depurar.guardrails"),
          "categorias" => array("depurar.items", "depurar.arbol", "depurar.resumen", "depurar.frontend", "depurar.guardrails"),
          "filtros" => array("depurar.fase_2.facetados", "depurar.fase_2.totales", "depurar.fase_2.guardrails"),
          "navegacion" => array("depurar.fase_2.chips_home", "depurar.fase_2.links", "depurar.fase_2.guardrails"),
          "secciones" => array("depurar.secciones[].frontend", "depurar.secciones[].url_catalogo", "depurar.fase_2"),
          "producto" => array("depurar.item", "depurar.variantes", "depurar.relacionados", "depurar.breadcrumbs", "depurar.seo", "depurar.acciones", "depurar.fase_2"),
          "seo" => array("depurar.rutas", "depurar.sitemap_xml_sugerido", "depurar.fase_2.canonical", "depurar.fase_2.json_ld"),
          "disponibilidad" => array("depurar.disponibilidad", "depurar.frontend.badge", "depurar.frontend.cta", "depurar.frontend.mostrar_stock_exacto=false", "depurar.fase_2"),
          "cotizacion_dryrun" => array("depurar.lineas", "depurar.totales", "depurar.bloqueos", "depurar.advertencias", "depurar.frontend", "depurar.fase_2"),
          "cotizacion_preflight" => array("depurar.cta", "depurar.whatsapp", "depurar.frontend", "depurar.listo_para_whatsapp", "depurar.fase_2")
        ),
        "ejemplos" => array(
          "catalogo_manifest" => $this->resumenRespuestaFrontendHandoff($manifest, array("fase", "estado_catalogo", "ordenamientos", "endpoints_relacionados", "guardrails")),
          "contenido_manifest" => $this->resumenRespuestaFrontendHandoff($this->contenidoManifestPublico(), array("cms", "plantilla_activa", "plantillas", "tipos_bloque", "guardrails")),
          "contenido_pagina_home" => $this->resumenRespuestaFrontendHandoff($this->contenidoPaginaPublica(array("pagina" => "home")), array("pagina", "plantilla", "slots", "resumen", "guardrails")),
          "fase_2_checklist" => $this->resumenRespuestaFrontendHandoff($checklist, array("estado", "resumen", "endpoints_obligatorios", "criterios_pase_fase_3")),
          "catalogo" => $this->resumenRespuestaFrontendHandoff($catalogo, array("items", "paginacion", "frontend")),
          "categorias" => $this->resumenRespuestaFrontendHandoff($categorias, array("items", "arbol", "resumen", "frontend")),
          "producto" => $this->resumenRespuestaFrontendHandoff($producto, array("item", "variantes", "relacionados", "breadcrumbs", "seo", "acciones")),
          "seo" => $this->resumenRespuestaFrontendHandoff($seo, array("meta", "rutas", "fase_2", "resumen")),
          "disponibilidad" => $this->resumenRespuestaFrontendHandoff($disponibilidad, array("disponibilidad", "frontend", "fase_2")),
          "cotizacion_dryrun" => $this->resumenRespuestaFrontendHandoff($dryrun, array("lineas", "totales", "bloqueos", "advertencias", "frontend", "fase_2")),
          "cotizacion_preflight" => $this->resumenRespuestaFrontendHandoff($preflight, array("listo_para_whatsapp", "cta", "frontend", "fase_2"))
        ),
        "fase_2" => array(
          "fase" => "fase_2_api_catalogo_robusta",
          "estado" => "handoff_consolidado",
          "frontend_puede_consumir_sin_docs" => true,
          "bloques_listos" => array("configuracion_inicial", "contenido_manifest", "contenido_pagina", "catalogo_manifest", "fase_2_checklist", "catalogo", "producto", "filtros", "categorias", "navegacion", "secciones", "busqueda_sugerencias", "seo"),
          "guardrails" => array(
            "no_requiere_filesystem" => true,
            "no_granel" => true,
            "no_stock_exacto" => true,
            "no_ecom_legacy_fuente" => true
          )
        ),
        "no_usar" => array(
          "No leer docs/*.md ni archivos de panel_de_control desde el proyecto frontend.",
          "No consultar tablas internas del ERP ni legacy ecom_*.",
          "No usar costos, proveedores, lotes, ubicaciones ni stock exacto.",
          "No calcular precios finales en frontend; usar cotizacion_dryrun antes de WhatsApp.",
          "No usar /ecommercePublico/cotizacion_registrar en Fase 1; sigue bloqueado.",
          "No crear checkout, pagos o apartado de inventario desde frontend."
        ),
        "guardrails" => array(
          "read_only" => true,
          "no_expone_secretos" => true,
          "no_requiere_filesystem" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "frontend_es_proyecto_externo" => true,
          "erp_es_fuente_de_verdad" => true
        ),
        "contratos_fuente" => array(
          "contratos_api" => $this->valor($contratos, array("depurar", "base_path"), $basePath),
          "configuracion_inicial_disponible" => !empty($bootstrap) && empty($bootstrap["error"]),
          "bootstrap_alias_legacy_disponible" => true,
          "catalogo_manifest_disponible" => !empty($manifest) && empty($manifest["error"]),
          "fase_2_checklist_disponible" => !empty($checklist) && empty($checklist["error"]),
          "seo_disponible" => !empty($seo) && empty($seo["error"]),
          "slug_ejemplo" => $slugEjemplo
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "estado_actual" => array("senal_frontend" => "rojo_error_handoff"),
        "guardrails" => array("read_only" => true, "no_requiere_filesystem" => true)
      ));
    }
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
          "schema" => array(
            "disponible" => false,
            "ddl_pendiente" => true
          ),
          "publicaciones" => array(
            "total_publicadas" => 0,
            "skus_publicables_fase_1" => 0,
            "catalogo_publico_vacio" => true
          ),
          "configuracion" => array("disponible" => false),
          "seguridad" => array(
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar la configuracion inicial con nombre claro para frontend ecommerce.
   * Impacto: evita confundir el endpoint de arranque con Bootstrap CSS; conserva bootstrap como alias legacy.
   * Contrato: read-only; no escribe BD, no expone secretos y no muestra stock exacto.
   */
  public function configuracionInicialPublica($opciones = array()) {
    $respuesta = $this->bootstrapPublico($opciones);
    $contenidoHome = $this->contenidoPaginaPublica(array("pagina" => "home", "plantilla" => "artiani_default"));
    $depurarHome = $this->valor($contenidoHome, "depurar", array());
    $respuesta["mensaje"] = !empty($respuesta["error"])
      ? (isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "Configuracion inicial ecommerce con observaciones")
      : "Configuracion inicial ecommerce lista";
    if (!isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
      $respuesta["depurar"] = array();
    }
    $respuesta["depurar"]["endpoint_principal"] = "/ecommercePublico/configuracion_inicial";
    $respuesta["depurar"]["alias_legacy"] = "/ecommercePublico/bootstrap";
    $respuesta["depurar"]["contenido"] = array(
      "manifest" => "/ecommercePublico/contenido_manifest",
      "home" => "/ecommercePublico/contenido_pagina?pagina=home&plantilla=artiani_default",
      "categoria" => "/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug_categoria}",
      "estado" => $this->valor($depurarHome, "fuente", "default_readonly"),
      "panel_pendiente" => $this->valor($depurarHome, "fuente", "default_readonly") !== "bd_publicada",
      "fallback_default_si_no_hay_publicado" => true
    );
    $respuesta["depurar"]["contenido_inicial"] = array(
      "home" => array(
        "pagina" => $this->valor($depurarHome, "pagina", "home"),
        "plantilla" => $this->valor($depurarHome, "plantilla", "artiani_default"),
        "plantilla_vista" => $this->valor($depurarHome, "plantilla_vista", array()),
        "slots" => $this->valor($depurarHome, "slots", array()),
        "resumen" => $this->valor($depurarHome, "resumen", array()),
        "fuente" => $this->valor($depurarHome, "fuente", "default_readonly")
      ),
      "guardrails" => array(
        "read_only" => true,
        "default_hasta_contenido_publicado" => $this->valor($depurarHome, "fuente", "default_readonly") !== "bd_publicada",
        "frontend_renderiza_plantilla_vista" => true
      )
    );
    return $respuesta;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: publicar el manifiesto del CMS ligero para plantillas, slots y bloques.
   * Impacto: Frontend ecommerce; permite implementar plantillas sin hardcodear estructura editorial.
   * Contrato: read-only; no escribe BD ni lee archivos de plantilla del proyecto frontend.
   */
  public function contenidoManifestPublico($opciones = array()) {
    $manifestBd = $this->contenidoManifestPublicoDesdeBd($opciones);
    if ($manifestBd !== null) {
      return $this->respuesta(false, "success", "Manifest de contenido ecommerce disponible desde BD publicada", $manifestBd);
    }

    $plantilla = $this->limpiarFiltroPublico($this->valor($opciones, "plantilla", "artiani_default"));
    if ($plantilla === "") { $plantilla = "artiani_default"; }
    return $this->respuesta(false, "success", "Manifest de contenido ecommerce disponible", array(
      "cms" => array(
        "fase" => "fase_7_contenido_cms_ligero_readonly",
        "estado" => "contrato_default_sin_persistencia",
        "headless" => true,
        "panel_pendiente" => true,
        "endpoint_principal" => "/ecommercePublico/contenido_pagina"
      ),
      "plantilla_activa" => $plantilla,
      "tema_visual_activo" => array(
        "codigo" => "wokiee_artiani",
        "nombre" => "Wokiee Artiani",
        "proveedor" => "ThemeForest/Wokiee",
        "estado" => "readonly_inicial",
        "puede_cambiar_en_futuro" => true
      ),
      "plantillas" => array(
        array(
          "codigo" => "artiani_default",
          "nombre" => "Artiani default",
          "descripcion" => "Plantilla base para home, catalogo y categorias del ecommerce Artiani.",
          "slots" => $this->contenidoSlotsDefault()
        )
      ),
      "tipos_bloque" => $this->contenidoTiposBloqueDefault(),
      "plantillas_vista" => array(
        $this->plantillaVistaPaginaDefault("home"),
        $this->plantillaVistaPaginaDefault("categoria"),
        $this->plantillaVistaPaginaDefault("catalogo")
      ),
      "componentes_frontend" => $this->componentesFrontendDefault(),
      "paginas_soportadas" => array(
        array("codigo" => "home", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=home&plantilla=" . $plantilla),
        array("codigo" => "categoria", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug_categoria}&plantilla=" . $plantilla),
        array("codigo" => "catalogo", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=catalogo&plantilla=" . $plantilla)
      ),
      "parametros" => array(
        "pagina" => "home|categoria|catalogo",
        "plantilla" => "artiani_default por defecto",
        "categoria" => "slug/codigo de categoria cuando pagina=categoria"
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_modifica_inventario" => true,
        "frontend_renderiza_plantilla" => true,
        "frontend_renderiza_plantilla_vista" => true,
        "erp_entrega_contenido_json" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar bloques editoriales de una pagina ecommerce para render headless.
   * Impacto: Frontend ecommerce; habilita home/categorias con banners, colecciones y CTAs por API.
   * Contrato: read-only; devuelve contenido default hasta activar tablas/panel de captura.
   */
  public function contenidoPaginaPublica($opciones = array()) {
    $paginaBd = $this->contenidoPaginaPublicaDesdeBd($opciones);
    if ($paginaBd !== null) {
      return $this->respuesta(false, "success", "Contenido de pagina ecommerce disponible desde BD publicada", $paginaBd);
    }

    $pagina = $this->limpiarFiltroPublico($this->valor($opciones, "pagina", "home"));
    $plantilla = $this->limpiarFiltroPublico($this->valor($opciones, "plantilla", "artiani_default"));
    $categoria = $this->limpiarFiltroPublico($this->valor($opciones, "categoria", ""));
    if ($pagina === "") { $pagina = "home"; }
    if ($plantilla === "") { $plantilla = "artiani_default"; }
    $permitidas = array("home", "categoria", "catalogo");
    if (!in_array($pagina, $permitidas, true)) {
      $pagina = "home";
    }
    $slots = $this->contenidoSlotsPaginaDefault($pagina, $categoria);
    return $this->respuesta(false, "success", "Contenido de pagina ecommerce disponible", array(
      "pagina" => $pagina,
      "plantilla" => $plantilla,
      "categoria" => $categoria,
      "fuente" => "default_readonly",
      "editable_desde_panel" => false,
      "panel_pendiente" => true,
      "version_contenido" => "default-2026-08-10",
      "plantilla_vista" => $this->plantillaVistaPaginaDefault($pagina),
      "slots" => $slots,
      "resumen" => array(
        "slots_total" => count($slots),
        "bloques_total" => $this->contarBloquesContenido($slots),
        "tiene_hero" => $this->contenidoTieneSlot($slots, "home.hero") || $this->contenidoTieneSlot($slots, "categoria.banner"),
        "requiere_imagenes_reales_panel" => true
      ),
      "links" => array(
        "manifest" => "/ecommercePublico/contenido_manifest?plantilla=" . $plantilla,
        "configuracion_inicial" => "/ecommercePublico/configuracion_inicial",
        "catalogo" => "/ecommercePublico/catalogo",
        "secciones" => "/ecommercePublico/secciones"
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_modifica_inventario" => true,
        "no_checkout" => true,
        "plantilla_vista_readonly" => true,
        "imagenes_reales_pendientes_panel" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: resumir estado interno del CMS ecommerce para el panel ERP.
   * Impacto: Ecommerce CMS; combina auditoria y plan DDL sin ejecutar cambios ni tocar catalogo/inventario.
   * Contrato: read-only; recibe respuestas de esquema y devuelve payload operativo para UI.
   */
  public function contenidoAdminEstadoInterno($auditoriaEsquema, $planEsquema) {
    $manifest = $this->contenidoManifestPublico();
    $home = $this->contenidoPaginaPublica(array("pagina" => "home"));
    $guardrails = $this->contenidoGuardrailsAdmin();
    $guardrails["read_only"] = false;
    $guardrails["no_escribe_bd"] = false;
    $guardrails["persistencia_contenido_interna"] = true;
    $guardrails["publicaciones_slot_publicables"] = true;
    $guardrails["api_publica_sigue_fallback_hasta_conectar_bd"] = true;

    return $this->respuesta(false, "info", "CMS ecommerce con persistencia interna de contenido", array(
      "modo" => "admin_contenido_interno",
      "fase" => "cms_contenido_publicaciones_internas_bd",
      "persistencia_real" => true,
      "persistencia_alcance" => "bloques_y_publicaciones_internas",
      "pantalla" => "/cms/contenido",
      "endpoints_admin" => array(
        "estado" => "/cms/contenido_admin_estado_erp",
        "manifest" => "/cms/contenido_admin_manifest_erp",
        "pagina" => "/cms/contenido_admin_pagina_erp",
        "guardar_bloque" => "/cms/contenido_bloque_guardar_erp",
        "guardar_publicacion" => "/cms/contenido_publicacion_guardar_erp",
        "estatus_publicacion" => "/cms/contenido_publicacion_estatus_erp"
      ),
      "endpoints_publicos" => array(
        "manifest" => "/ecommercePublico/contenido_manifest",
        "pagina_home" => "/ecommercePublico/contenido_pagina?pagina=home",
        "configuracion_inicial" => "/ecommercePublico/configuracion_inicial",
        "bootstrap_alias_legacy" => "/ecommercePublico/bootstrap"
      ),
      "resumen" => array(
        "plantilla_activa" => $this->valor($manifest, array("depurar", "plantilla_activa"), "artiani_default"),
        "slots_total" => count($this->valor($manifest, array("depurar", "plantillas", 0, "slots"), array())),
        "tipos_bloque_total" => count($this->valor($manifest, array("depurar", "tipos_bloque"), array())),
        "bloques_default_home" => $this->valor($home, array("depurar", "resumen", "bloques_total"), 0)
      ),
      "esquema" => array(
        "auditoria" => $this->valor($auditoriaEsquema, "depurar", array()),
        "plan" => $this->valor($planEsquema, "depurar", array())
      ),
      "guardrails" => $guardrails
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar manifest interno del CMS con metadatos de administracion.
   * Impacto: Ecommerce CMS; alimenta la vista interna sin activar escrituras.
   * Contrato: read-only; no consulta archivos de plantilla ni modifica BD.
   */
  public function contenidoAdminManifestInterno($opciones = array()) {
    $manifestBd = $this->contenidoAdminManifestDesdeBd($opciones);
    if ($manifestBd !== null) {
      return $this->respuesta(false, "success", "Manifest interno CMS ecommerce disponible desde BD", $manifestBd);
    }

    $manifest = $this->contenidoManifestPublico($opciones);
    $depurar = $this->valor($manifest, "depurar", array());
    $depurar["admin"] = array(
      "modo" => "readonly",
      "puede_guardar" => false,
      "puede_publicar" => false,
      "vista" => "/cms/contenido",
      "pendiente_persistencia" => true
    );
    $depurar["guardrails"] = array_merge($this->valor($depurar, "guardrails", array()), $this->contenidoGuardrailsAdmin());
    return $this->respuesta(false, "success", "Manifest interno CMS ecommerce disponible", $depurar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar previsualizacion admin de una pagina CMS ecommerce.
   * Impacto: Ecommerce CMS; muestra el mismo contrato que consumira frontend con avisos de modo read-only.
   * Contrato: read-only; usa contenido default hasta que se autorice persistencia real.
   */
  public function contenidoAdminPaginaInterna($opciones = array()) {
    $paginaBd = $this->contenidoAdminPaginaDesdeBd($opciones);
    if ($paginaBd !== null) {
      return $this->respuesta(false, "success", "Previsualizacion CMS ecommerce disponible desde publicaciones BD internas", $paginaBd);
    }

    $pagina = $this->contenidoPaginaPublica($opciones);
    $depurar = $this->valor($pagina, "depurar", array());
    $depurar["admin"] = array(
      "modo" => "readonly",
      "previsualizacion_json" => true,
      "fuente_actual" => $this->valor($depurar, "fuente", "default_readonly"),
      "pendiente_persistencia" => true
    );
    $depurar["guardrails"] = array_merge($this->valor($depurar, "guardrails", array()), $this->contenidoGuardrailsAdmin());
    return $this->respuesta(false, "success", "Previsualizacion CMS ecommerce disponible", $depurar);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: listar bloques CMS editoriales guardados en BD para el panel interno.
   * Impacto: CMS contenido; habilita reutilizar borradores sin crear publicaciones ni cambiar API publica.
   * Contrato: read-only; filtra por tipo/estatus y devuelve payload seguro para editor.
   */
  public function contenidoBloquesAdminInterno($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return $this->respuesta(true, "warning", "El esquema CMS contenido no esta disponible para listar bloques.", array(
          "items" => array(),
          "persistencia_real" => false
        ));
      }

      $tipo = $this->limpiarCodigoCms($this->valor($opciones, "tipo_bloque", $this->valor($opciones, "tipo", "")), 60);
      $estatus = $this->limpiarCodigoCms($this->valor($opciones, "estatus", ""), 30);
      $limite = max(1, min(100, intval($this->valor($opciones, "limite", 50))));

      $where = array("estatus IN ('borrador','pausado')");
      $params = array();
      if ($tipo !== "") {
        $where[] = "tipo_bloque=:tipo";
        $params[":tipo"] = $tipo;
      }
      if (in_array($estatus, array("borrador", "pausado"), true)) {
        $where[] = "estatus=:estatus";
        $params[":estatus"] = $estatus;
      }

      $stmt = $db->prepare(
        "SELECT id_bloque, tipo_bloque, codigo, nombre_interno, titulo, payload_json, estatus, fecha_registro, fecha_actualizacion " .
        "FROM erp_ecommerce_contenido_bloques WHERE " . implode(" AND ", $where) . " ORDER BY COALESCE(fecha_actualizacion, fecha_registro) DESC, id_bloque DESC LIMIT " . $limite
      );
      $stmt->execute($params);

      $items = array();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $payload = $this->jsonArray($row["payload_json"]);
        unset($payload["_cms_guardrails"]);
        $payload["id"] = "bd-" . (int) $row["id_bloque"];
        $payload["id_bloque"] = (int) $row["id_bloque"];
        $payload["codigo"] = (string) $row["codigo"];
        $payload["tipo"] = (string) $row["tipo_bloque"];
        $payload["estatus"] = (string) $row["estatus"];
        if (!isset($payload["titulo"]) || trim((string) $payload["titulo"]) === "") {
          $payload["titulo"] = (string) $row["titulo"];
        }
        $items[] = array(
          "id_bloque" => (int) $row["id_bloque"],
          "codigo" => (string) $row["codigo"],
          "tipo_bloque" => (string) $row["tipo_bloque"],
          "nombre_interno" => (string) $row["nombre_interno"],
          "titulo" => (string) $row["titulo"],
          "estatus" => (string) $row["estatus"],
          "fecha_registro" => (string) $row["fecha_registro"],
          "fecha_actualizacion" => (string) $row["fecha_actualizacion"],
          "payload" => $payload
        );
      }

      return $this->respuesta(false, "success", "Bloques CMS guardados disponibles.", array(
        "items" => $items,
        "total" => count($items),
        "filtros" => array("tipo_bloque" => $tipo, "estatus" => $estatus, "limite" => $limite),
        "persistencia_real" => true,
        "publica_contenido" => false,
        "guardrails" => array(
          "solo_lectura" => true,
          "solo_borrador_pausado" => true,
          "no_crea_publicacion" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true,
          "api_publica_sigue_fallback" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudieron listar los bloques CMS.", array(
        "error_tecnico" => $e->getMessage(),
        "items" => array()
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: guardar bloques editoriales CMS en BD como borrador o pausado.
   * Impacto: CMS contenido; habilita primera escritura real sin publicar slots ni modificar catalogo, precios, inventario o publicaciones de producto.
   * Contrato: requiere tablas CMS creadas; valida tipo, payload JSON y HTML basico; no publica contenido real.
   */
  public function contenidoBloqueGuardarInterno($datos, $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return $this->respuesta(true, "warning", "El esquema CMS contenido no esta disponible para guardar bloques.", array(
          "persistencia_real" => false,
          "requiere" => array("respaldo_bd", "ddl_cms_contenido_autorizado")
        ));
      }

      $idBloque = intval($this->valor($datos, "id_bloque", 0));
      $tipo = $this->limpiarCodigoCms($this->valor($datos, "tipo_bloque", $this->valor($datos, "tipo", "")), 60);
      $tiposPermitidos = $this->contenidoTiposBloqueCodigos();
      if ($tipo === "" || !in_array($tipo, $tiposPermitidos, true)) {
        return $this->respuesta(true, "warning", "Tipo de bloque CMS no permitido.", array(
          "tipo_bloque" => $tipo,
          "tipos_permitidos" => $tiposPermitidos
        ));
      }

      $nombreInterno = trim((string) $this->valor($datos, "nombre_interno", ""));
      $titulo = trim((string) $this->valor($datos, "titulo", ""));
      if ($nombreInterno === "") {
        $nombreInterno = $titulo !== "" ? $titulo : $tipo . " CMS";
      }
      $nombreInterno = substr($nombreInterno, 0, 180);
      $titulo = substr($titulo, 0, 255);

      $estatusSolicitado = $this->limpiarCodigoCms($this->valor($datos, "estatus", "borrador"), 30);
      $estatus = in_array($estatusSolicitado, array("borrador", "pausado"), true) ? $estatusSolicitado : "borrador";

      $payloadRaw = (string) $this->valor($datos, "payload_json", $this->valor($datos, "payload", "{}"));
      if (strlen($payloadRaw) > 1024 * 1024) {
        return $this->respuesta(true, "warning", "El payload del bloque CMS es demasiado grande.", array("limite_bytes" => 1024 * 1024));
      }
      $payload = json_decode($payloadRaw, true);
      if (!is_array($payload)) {
        return $this->respuesta(true, "warning", "El payload_json del bloque CMS no es JSON valido.", array("json_error" => json_last_error_msg()));
      }
      if (!$this->cmsPayloadSeguroParaGuardar($tipo, $payload)) {
        return $this->respuesta(true, "warning", "El contenido HTML no cumple las reglas de seguridad del CMS.", array(
          "tipo_bloque" => $tipo,
          "bloqueado" => array("script", "event_handlers_inline", "javascript_url")
        ));
      }

      $payload["_cms_guardrails"] = array(
        "guardado_desde" => "erp_cms_contenido",
        "estatus_solicitado" => $estatusSolicitado,
        "estatus_guardado" => $estatus,
        "no_publica_contenido" => true,
        "no_modifica_catalogo" => true,
        "no_modifica_precios" => true,
        "no_modifica_inventario" => true
      );
      $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
      if ($payloadJson === false) {
        return $this->respuesta(true, "warning", "No se pudo serializar el payload CMS.", array("json_error" => json_last_error_msg()));
      }

      $codigo = $this->limpiarCodigoCms($this->valor($datos, "codigo", ""), 120);
      if ($codigo === "") {
        $codigo = $this->codigoBloqueCmsUnico($db, $tipo, $titulo !== "" ? $titulo : $nombreInterno, $idBloque);
      } elseif ($this->cmsCodigoBloqueExiste($db, $codigo, $idBloque)) {
        return $this->respuesta(true, "warning", "Ya existe un bloque CMS con ese codigo.", array("codigo" => $codigo));
      }

      $db->beginTransaction();
      if ($idBloque > 0) {
        $stmtExiste = $db->prepare("SELECT id_bloque FROM erp_ecommerce_contenido_bloques WHERE id_bloque=:id LIMIT 1");
        $stmtExiste->execute(array(":id" => $idBloque));
        if (!$stmtExiste->fetchColumn()) {
          $db->rollBack();
          return $this->respuesta(true, "warning", "No existe el bloque CMS que intentas actualizar.", array("id_bloque" => $idBloque));
        }

        $stmt = $db->prepare(
          "UPDATE erp_ecommerce_contenido_bloques SET tipo_bloque=:tipo, codigo=:codigo, nombre_interno=:nombre, titulo=:titulo, payload_json=:payload, estatus=:estatus, fecha_actualizacion=NOW(), actualizado_por=:usuario WHERE id_bloque=:id"
        );
        $stmt->execute(array(
          ":tipo" => $tipo,
          ":codigo" => $codigo,
          ":nombre" => $nombreInterno,
          ":titulo" => $titulo,
          ":payload" => $payloadJson,
          ":estatus" => $estatus,
          ":usuario" => intval($idUsuario) ?: null,
          ":id" => $idBloque
        ));
      } else {
        $stmt = $db->prepare(
          "INSERT INTO erp_ecommerce_contenido_bloques (tipo_bloque, codigo, nombre_interno, titulo, payload_json, estatus, creado_por, actualizado_por) VALUES (:tipo, :codigo, :nombre, :titulo, :payload, :estatus, :creado_por, :actualizado_por)"
        );
        $stmt->execute(array(
          ":tipo" => $tipo,
          ":codigo" => $codigo,
          ":nombre" => $nombreInterno,
          ":titulo" => $titulo,
          ":payload" => $payloadJson,
          ":estatus" => $estatus,
          ":creado_por" => intval($idUsuario) ?: null,
          ":actualizado_por" => intval($idUsuario) ?: null
        ));
        $idBloque = (int) $db->lastInsertId();
      }
      $db->commit();

      return $this->respuesta(false, "success", "Bloque CMS guardado como borrador controlado.", array(
        "id_bloque" => $idBloque,
        "codigo" => $codigo,
        "tipo_bloque" => $tipo,
        "estatus" => $estatus,
        "estatus_solicitado" => $estatusSolicitado,
        "persistencia_real" => true,
        "publicado" => false,
        "siguiente_paso" => "crear_publicacion_slot_con_vigencia",
        "guardrails" => array(
          "solo_tabla_bloques" => true,
          "no_crea_publicacion" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_precios" => true,
          "no_modifica_inventario" => true,
          "api_publica_sigue_sin_leer_bloques_borrador" => true
        )
      ));
    } catch (Exception $e) {
      if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
      }
      return $this->respuesta(true, "danger", "No se pudo guardar el bloque CMS.", array(
        "error_tecnico" => $e->getMessage(),
        "no_modifica_catalogo" => true,
        "no_modifica_inventario" => true
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: actualizar estatus operativo de un bloque CMS sin publicarlo.
   * Impacto: CMS contenido; permite pausar/reactivar bloques guardados sin tocar publicaciones, catalogo, precios ni inventario.
   * Contrato: solo acepta `borrador` o `pausado`; `publicado` queda reservado para publicaciones por slot.
   */
  public function contenidoBloqueEstatusInterno($datos, $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return $this->respuesta(true, "warning", "El esquema CMS contenido no esta disponible para cambiar estatus.", array(
          "persistencia_real" => false
        ));
      }

      $idBloque = intval($this->valor($datos, "id_bloque", 0));
      $estatus = $this->limpiarCodigoCms($this->valor($datos, "estatus", ""), 30);
      if ($idBloque <= 0) {
        return $this->respuesta(true, "warning", "Indica el bloque CMS que quieres actualizar.", array("id_bloque" => $idBloque));
      }
      if (!in_array($estatus, array("borrador", "pausado"), true)) {
        return $this->respuesta(true, "warning", "El estatus de bloque permitido es borrador o pausado.", array(
          "estatus_solicitado" => $estatus,
          "publicado_requiere" => "contenido_publicacion_guardar_erp"
        ));
      }

      $stmt = $db->prepare("SELECT id_bloque, tipo_bloque, codigo, estatus FROM erp_ecommerce_contenido_bloques WHERE id_bloque=:id LIMIT 1");
      $stmt->execute(array(":id" => $idBloque));
      $bloque = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$bloque) {
        return $this->respuesta(true, "warning", "No existe el bloque CMS solicitado.", array("id_bloque" => $idBloque));
      }

      $estatusAnterior = (string) $bloque["estatus"];
      $stmtUpdate = $db->prepare("UPDATE erp_ecommerce_contenido_bloques SET estatus=:estatus, fecha_actualizacion=NOW(), actualizado_por=:usuario WHERE id_bloque=:id");
      $stmtUpdate->execute(array(
        ":estatus" => $estatus,
        ":usuario" => intval($idUsuario) ?: null,
        ":id" => $idBloque
      ));

      return $this->respuesta(false, "success", "Estatus de bloque CMS actualizado.", array(
        "id_bloque" => $idBloque,
        "codigo" => (string) $bloque["codigo"],
        "tipo_bloque" => (string) $bloque["tipo_bloque"],
        "estatus_anterior" => $estatusAnterior,
        "estatus" => $estatus,
        "persistencia_real" => true,
        "publicado" => false,
        "guardrails" => array(
          "solo_borrador_pausado" => true,
          "no_crea_publicacion" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_precios" => true,
          "no_modifica_inventario" => true,
          "api_publica_sigue_sin_leer_bloques_borrador" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo actualizar el estatus del bloque CMS.", array(
        "error_tecnico" => $e->getMessage(),
        "no_modifica_catalogo" => true,
        "no_modifica_inventario" => true
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: colocar un bloque CMS guardado en un slot/pagina como publicacion interna borrador.
   * Impacto: CMS contenido; permite armar paginas desde BD para preview administrativo sin cambiar la API publica.
   * Contrato: solo guarda publicaciones `borrador` o `pausado`; publicar real queda reservado para fase posterior.
   */
  public function contenidoPublicacionGuardarInterna($datos, $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return $this->respuesta(true, "warning", "El esquema CMS contenido no esta disponible para guardar publicaciones.", array("persistencia_real" => false));
      }

      $idBloque = intval($this->valor($datos, "id_bloque", 0));
      $idPublicacion = intval($this->valor($datos, "id_publicacion_contenido", $this->valor($datos, "id_publicacion", 0)));
      $plantillaCodigo = $this->limpiarCodigoCms($this->valor($datos, "plantilla", "artiani_default"), 80);
      if ($plantillaCodigo === "") { $plantillaCodigo = "artiani_default"; }
      $slotCodigo = $this->limpiarCodigoCms($this->valor($datos, "slot", $this->valor($datos, "slot_codigo", "")), 120);
      $pagina = $this->limpiarCodigoCms($this->valor($datos, "pagina", "home"), 60);
      if ($pagina === "") { $pagina = "home"; }
      $contexto = $this->cmsContextoContenido($pagina, $this->valor($datos, "contexto_clave", $this->valor($datos, "categoria", "")));
      $estatusSolicitado = $this->limpiarCodigoCms($this->valor($datos, "estatus", "borrador"), 30);
      $estatus = in_array($estatusSolicitado, array("borrador", "pausado"), true) ? $estatusSolicitado : "borrador";
      $orden = intval($this->valor($datos, "orden", 0));
      $canal = "catalogo_publico";
      $desde = $this->cmsFechaSql($this->valor($datos, "vigente_desde", $this->valor($datos, "desde", "")));
      $hasta = $this->cmsFechaSql($this->valor($datos, "vigente_hasta", $this->valor($datos, "hasta", "")));

      if ($idBloque <= 0 || $slotCodigo === "") {
        return $this->respuesta(true, "warning", "Indica bloque y slot para guardar la publicacion CMS.", array("id_bloque" => $idBloque, "slot" => $slotCodigo));
      }

      $stmtPlantilla = $db->prepare("SELECT id_plantilla, codigo FROM erp_ecommerce_plantillas WHERE codigo=:codigo AND activa=1 LIMIT 1");
      $stmtPlantilla->execute(array(":codigo" => $plantillaCodigo));
      $plantilla = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
      if (!$plantilla) {
        return $this->respuesta(true, "warning", "No existe plantilla CMS activa para publicar contenido.", array("plantilla" => $plantillaCodigo));
      }

      $stmtSlot = $db->prepare("SELECT * FROM erp_ecommerce_plantilla_slots WHERE id_plantilla=:plantilla AND codigo=:codigo AND estatus='activo' LIMIT 1");
      $stmtSlot->execute(array(":plantilla" => (int) $plantilla["id_plantilla"], ":codigo" => $slotCodigo));
      $slot = $stmtSlot->fetch(PDO::FETCH_ASSOC);
      if (!$slot) {
        return $this->respuesta(true, "warning", "El slot CMS no existe en la plantilla activa.", array("slot" => $slotCodigo));
      }
      if ((string) $slot["pagina"] !== $pagina) {
        return $this->respuesta(true, "warning", "El slot no pertenece a la pagina indicada.", array("slot_pagina" => $slot["pagina"], "pagina" => $pagina));
      }

      $stmtBloque = $db->prepare("SELECT id_bloque, tipo_bloque, codigo, estatus FROM erp_ecommerce_contenido_bloques WHERE id_bloque=:id LIMIT 1");
      $stmtBloque->execute(array(":id" => $idBloque));
      $bloque = $stmtBloque->fetch(PDO::FETCH_ASSOC);
      if (!$bloque) {
        return $this->respuesta(true, "warning", "No existe el bloque CMS para publicar.", array("id_bloque" => $idBloque));
      }
      $tiposSlot = $this->jsonArray($slot["tipos_bloque_json"]);
      if (!in_array((string) $bloque["tipo_bloque"], $tiposSlot, true)) {
        return $this->respuesta(true, "warning", "El tipo de bloque no es compatible con el slot.", array(
          "tipo_bloque" => (string) $bloque["tipo_bloque"],
          "slot" => $slotCodigo,
          "tipos_permitidos" => $tiposSlot
        ));
      }

      if ($orden <= 0) {
        $orden = $this->cmsSiguienteOrdenPublicacion($db, (int) $plantilla["id_plantilla"], (int) $slot["id_slot"], $pagina, $contexto, $canal);
      }

      $max = intval($slot["max_bloques"]);
      if ($max > 0 && $this->cmsConteoPublicacionesSlot($db, (int) $plantilla["id_plantilla"], (int) $slot["id_slot"], $pagina, $contexto, $canal, $idPublicacion) >= $max) {
        return $this->respuesta(true, "warning", "El slot ya alcanzo el maximo de bloques permitidos.", array("slot" => $slotCodigo, "max_bloques" => $max));
      }

      if ($idPublicacion <= 0) {
        $idPublicacion = $this->cmsPublicacionExistente($db, (int) $plantilla["id_plantilla"], (int) $slot["id_slot"], $idBloque, $pagina, $contexto, $canal);
      }

      if ($idPublicacion > 0) {
        $stmt = $db->prepare(
          "UPDATE erp_ecommerce_contenido_publicaciones SET orden=:orden, estatus=:estatus, vigente_desde=:desde, vigente_hasta=:hasta, fecha_actualizacion=NOW(), actualizado_por=:usuario WHERE id_publicacion_contenido=:id"
        );
        $stmt->execute(array(":orden" => $orden, ":estatus" => $estatus, ":desde" => $desde, ":hasta" => $hasta, ":usuario" => intval($idUsuario) ?: null, ":id" => $idPublicacion));
      } else {
        $stmt = $db->prepare(
          "INSERT INTO erp_ecommerce_contenido_publicaciones (id_plantilla, id_slot, id_bloque, pagina, contexto_clave, orden, estatus, vigente_desde, vigente_hasta, canal, actualizado_por) VALUES (:plantilla, :slot, :bloque, :pagina, :contexto, :orden, :estatus, :desde, :hasta, :canal, :usuario)"
        );
        $stmt->execute(array(
          ":plantilla" => (int) $plantilla["id_plantilla"],
          ":slot" => (int) $slot["id_slot"],
          ":bloque" => $idBloque,
          ":pagina" => $pagina,
          ":contexto" => $contexto,
          ":orden" => $orden,
          ":estatus" => $estatus,
          ":desde" => $desde,
          ":hasta" => $hasta,
          ":canal" => $canal,
          ":usuario" => intval($idUsuario) ?: null
        ));
        $idPublicacion = (int) $db->lastInsertId();
      }

      return $this->respuesta(false, "success", "Publicacion CMS interna guardada como borrador.", array(
        "id_publicacion_contenido" => $idPublicacion,
        "id_bloque" => $idBloque,
        "slot" => $slotCodigo,
        "pagina" => $pagina,
        "contexto_clave" => $contexto,
        "orden" => $orden,
        "estatus" => $estatus,
        "estatus_solicitado" => $estatusSolicitado,
        "publicado_api" => false,
        "guardrails" => array(
          "preview_admin_bd" => true,
          "no_api_publica" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo guardar la publicacion CMS.", array("error_tecnico" => $e->getMessage()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: cambiar estatus de una publicacion CMS interna colocada en slot.
   * Impacto: CMS contenido; permite flujo editorial revisar -> publicar/pausar sin tocar catalogo, precios, inventario ni bloque base.
   * Contrato: acepta borrador, pausado o publicado; solo marca la publicacion, la API publica se conecta en una fase separada.
   */
  public function contenidoPublicacionEstatusInterna($datos, $idUsuario = 0) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return $this->respuesta(true, "warning", "El esquema CMS contenido no esta disponible para cambiar publicaciones.", array("persistencia_real" => false));
      }

      $idPublicacion = intval($this->valor($datos, "id_publicacion_contenido", $this->valor($datos, "id_publicacion", 0)));
      $estatus = $this->limpiarCodigoCms($this->valor($datos, "estatus", ""), 30);
      if ($idPublicacion <= 0 || !in_array($estatus, array("borrador", "pausado", "publicado"), true)) {
        return $this->respuesta(true, "warning", "Indica publicacion y estatus valido.", array(
          "id_publicacion_contenido" => $idPublicacion,
          "estatus" => $estatus,
          "estatus_permitidos" => array("borrador", "pausado", "publicado")
        ));
      }

      $stmt = $db->prepare(
        "SELECT pub.id_publicacion_contenido, pub.id_bloque, pub.pagina, pub.contexto_clave, pub.orden, pub.estatus, pub.vigente_desde, pub.vigente_hasta, s.codigo AS slot_codigo, s.tipos_bloque_json, b.estatus AS estatus_bloque, b.tipo_bloque, b.titulo, b.payload_json " .
        "FROM erp_ecommerce_contenido_publicaciones pub " .
        "INNER JOIN erp_ecommerce_plantilla_slots s ON s.id_slot=pub.id_slot " .
        "INNER JOIN erp_ecommerce_contenido_bloques b ON b.id_bloque=pub.id_bloque " .
        "WHERE pub.id_publicacion_contenido=:id LIMIT 1"
      );
      $stmt->execute(array(":id" => $idPublicacion));
      $publicacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$publicacion) {
        return $this->respuesta(true, "warning", "No existe la publicacion CMS indicada.", array("id_publicacion_contenido" => $idPublicacion));
      }

      if ($estatus === "publicado" && (string) $publicacion["estatus_bloque"] === "pausado") {
        return $this->respuesta(true, "warning", "No puedes publicar una colocacion cuyo bloque base esta pausado.", array(
          "id_publicacion_contenido" => $idPublicacion,
          "id_bloque" => (int) $publicacion["id_bloque"],
          "estatus_bloque" => (string) $publicacion["estatus_bloque"]
        ));
      }

      if ($estatus === "publicado") {
        $validacion = $this->cmsValidarPublicacionAntesDePublicar($publicacion);
        if (!empty($validacion["errores"])) {
          return $this->respuesta(true, "warning", "La publicacion CMS no cumple las reglas para publicarse.", array(
            "id_publicacion_contenido" => $idPublicacion,
            "id_bloque" => (int) $publicacion["id_bloque"],
            "slot" => (string) $publicacion["slot_codigo"],
            "tipo_bloque" => (string) $publicacion["tipo_bloque"],
            "bloqueos_publicacion" => $validacion["errores"],
            "alertas_publicacion" => $validacion["alertas"],
            "guardrails" => array(
              "validacion_server_side" => true,
              "no_publica_con_errores" => true,
              "no_modifica_bloque_base" => true
            )
          ));
        }
      }

      $estatusAnterior = (string) $publicacion["estatus"];
      $stmtActualizar = $db->prepare(
        "UPDATE erp_ecommerce_contenido_publicaciones SET estatus=:estatus, fecha_actualizacion=NOW(), actualizado_por=:usuario, publicado_por=CASE WHEN :estatus_publicado='publicado' THEN :usuario_publica ELSE publicado_por END WHERE id_publicacion_contenido=:id"
      );
      $stmtActualizar->execute(array(
        ":estatus" => $estatus,
        ":usuario" => intval($idUsuario) ?: null,
        ":estatus_publicado" => $estatus,
        ":usuario_publica" => intval($idUsuario) ?: null,
        ":id" => $idPublicacion
      ));

      return $this->respuesta(false, "success", "Estatus de publicacion CMS actualizado.", array(
        "id_publicacion_contenido" => $idPublicacion,
        "id_bloque" => (int) $publicacion["id_bloque"],
        "slot" => (string) $publicacion["slot_codigo"],
        "pagina" => (string) $publicacion["pagina"],
        "contexto_clave" => (string) $publicacion["contexto_clave"],
        "orden" => (int) $publicacion["orden"],
        "estatus_anterior" => $estatusAnterior,
        "estatus" => $estatus,
        "publicado_api" => false,
        "guardrails" => array(
          "solo_publicacion_slot" => true,
          "no_modifica_bloque_base" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_precios" => true,
          "no_modifica_inventario" => true,
          "api_publica_pendiente_de_conexion_bd" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", "No se pudo actualizar la publicacion CMS.", array("error_tecnico" => $e->getMessage()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar manifest interno de plantillas de vista frontend para CMS.
   * Impacto: CMS frontend; define layouts, componentes, variantes y mapeos slot->componente sin editar archivos.
   * Contrato: read-only; el frontend debe renderizar solo componentes predefinidos.
   */
  public function frontendPlantillasAdminManifestInterno($opciones = array()) {
    $manifestBd = $this->frontendPlantillasAdminManifestDesdeBd();
    if ($manifestBd !== null) {
      return $this->respuesta(false, "success", "Manifest frontend CMS disponible desde BD", $manifestBd);
    }

    $temaActivo = array(
      "codigo" => "wokiee_artiani",
      "nombre" => "Wokiee Artiani",
      "proveedor" => "ThemeForest/Wokiee",
      "estatus" => "activo_readonly",
      "descripcion" => "Primer tema visual conectado al CMS. No limita el sistema a Wokiee; otros temas podran registrarse con sus propios layouts y componentes."
    );
    $componentes = array(
      array(
        "codigo" => "HeroSlider",
        "nombre" => "Hero slider",
        "bloques_permitidos" => array("hero_banner", "category_banner"),
        "variantes" => array("full_width", "boxed", "split"),
        "slots_compatibles" => array("home.hero", "categoria.banner")
      ),
      array(
        "codigo" => "PromoStrip",
        "nombre" => "Tira promocional",
        "bloques_permitidos" => array("promo_strip"),
        "variantes" => array("single", "stacked", "compact"),
        "slots_compatibles" => array("home.promo", "catalogo.encabezado")
      ),
      array(
        "codigo" => "CategoryGrid",
        "nombre" => "Grid de categorias",
        "bloques_permitidos" => array("image_card_grid"),
        "variantes" => array("cards_3", "cards_4", "mosaic"),
        "slots_compatibles" => array("home.categorias")
      ),
      array(
        "codigo" => "ProductCarousel",
        "nombre" => "Carrusel de productos",
        "bloques_permitidos" => array("product_collection"),
        "variantes" => array("compact_cards", "wide_cards", "simple_row"),
        "slots_compatibles" => array("home.destacados", "categoria.productos")
      ),
      array(
        "codigo" => "ImageCardGrid",
        "nombre" => "Cards con imagen",
        "bloques_permitidos" => array("image_card_grid"),
        "variantes" => array("two_columns", "three_columns", "editorial"),
        "slots_compatibles" => array("home.categorias", "home.promo")
      ),
      array(
        "codigo" => "SafeHtmlBlock",
        "nombre" => "Contenido HTML seguro",
        "bloques_permitidos" => array("content_html_safe"),
        "variantes" => array("narrow", "wide", "accordion"),
        "slots_compatibles" => array("catalogo.encabezado", "home.promo")
      )
    );

    $plantillas = array(
      array(
        "codigo" => "wokiee_home_default",
        "nombre" => "Wokiee home default",
        "pagina" => "home",
        "layout" => "storefront_wokiee_v1",
        "estatus" => "borrador_readonly",
        "secciones" => array(
          array("slot" => "home.hero", "componente" => "HeroSlider", "variante" => "full_width", "orden" => 1),
          array("slot" => "home.promo", "componente" => "PromoStrip", "variante" => "compact", "orden" => 2),
          array("slot" => "home.categorias", "componente" => "CategoryGrid", "variante" => "cards_4", "orden" => 3),
          array("slot" => "home.destacados", "componente" => "ProductCarousel", "variante" => "compact_cards", "orden" => 4)
        )
      ),
      array(
        "codigo" => "wokiee_categoria_default",
        "nombre" => "Wokiee categoria default",
        "pagina" => "categoria",
        "layout" => "category_wokiee_v1",
        "estatus" => "borrador_readonly",
        "secciones" => array(
          array("slot" => "categoria.banner", "componente" => "HeroSlider", "variante" => "boxed", "orden" => 1),
          array("slot" => "categoria.productos", "componente" => "ProductCarousel", "variante" => "wide_cards", "orden" => 2)
        )
      ),
      array(
        "codigo" => "wokiee_catalogo_default",
        "nombre" => "Wokiee catalogo default",
        "pagina" => "catalogo",
        "layout" => "catalog_wokiee_v1",
        "estatus" => "borrador_readonly",
        "secciones" => array(
          array("slot" => "catalogo.encabezado", "componente" => "SafeHtmlBlock", "variante" => "wide", "orden" => 1)
        )
      )
    );

    return $this->respuesta(false, "success", "Manifest frontend CMS disponible", array(
      "modo" => "readonly",
      "fase" => "cms_frontend_plantillas_diseno_inicial",
      "tema_activo" => $temaActivo,
      "temas_disponibles" => array($temaActivo),
      "plantilla_activa_home" => "wokiee_home_default",
      "activaciones" => array(
        array(
          "pagina" => "home",
          "canal" => "catalogo_publico",
          "contexto_clave" => "*",
          "tema" => "wokiee_artiani",
          "plantilla_vista" => "wokiee_home_default",
          "estatus" => "activa_readonly",
          "vigencia" => "sin_vigencia",
          "endpoint_publico" => "/ecommercePublico/contenido_pagina?pagina=home"
        ),
        array(
          "pagina" => "categoria",
          "canal" => "catalogo_publico",
          "contexto_clave" => "{slug_categoria}",
          "tema" => "wokiee_artiani",
          "plantilla_vista" => "wokiee_categoria_default",
          "estatus" => "activa_readonly",
          "vigencia" => "sin_vigencia",
          "endpoint_publico" => "/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug_categoria}"
        ),
        array(
          "pagina" => "catalogo",
          "canal" => "catalogo_publico",
          "contexto_clave" => "*",
          "tema" => "wokiee_artiani",
          "plantilla_vista" => "wokiee_catalogo_default",
          "estatus" => "activa_readonly",
          "vigencia" => "sin_vigencia",
          "endpoint_publico" => "/ecommercePublico/contenido_pagina?pagina=catalogo"
        )
      ),
      "layouts" => array("storefront_wokiee_v1", "category_wokiee_v1", "catalog_wokiee_v1"),
      "componentes" => $componentes,
      "plantillas_vista" => $plantillas,
      "renderer_frontend" => array(
        "consume_desde" => "/ecommercePublico/configuracion_inicial",
        "pagina" => "/ecommercePublico/contenido_pagina?pagina=home",
        "contrato" => "plantilla_vista + contenido.slots",
        "mapa_componentes_requerido" => true
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_edita_archivos_frontend" => true,
        "no_html_libre" => true,
        "no_css_libre" => true,
        "no_js_libre" => true,
        "frontend_renderiza_componentes_predefinidos" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: entregar un paquete inicial estable para el frontend ecommerce.
   * Impacto: reduce llamadas iniciales y centraliza readiness, configuracion, filtros, secciones y canales.
   * Contrato: read-only; no escribe BD, no expone secretos y no muestra stock exacto.
   */
  public function bootstrapPublico($opciones = array()) {
    try {
      $limiteSecciones = max(1, min(12, intval($this->valor($opciones, "limite_secciones", 6))));
      $estado = $this->estadoApiPublica();
      $configuracion = $this->configuracionPublica();
      $filtros = $this->filtrosPublicos();
      $navegacion = $this->navegacionPublica(array("limite" => 12));
      $secciones = $this->seccionesPublicas(array("limite" => $limiteSecciones));
      $politicas = $this->politicasPublicas();
      $canales = $this->canalesApiEstadoPublico();
      $ready = !empty($this->valor($estado, array("depurar", "ready"), false))
        && empty($estado["error"])
        && empty($configuracion["error"])
        && empty($filtros["error"])
        && empty($secciones["error"]);

      return $this->respuesta(false, $ready ? "success" : "info", $ready ? "Bootstrap ecommerce listo" : "Bootstrap ecommerce con observaciones", array(
        "ready" => $ready,
        "estado" => $this->valor($estado, "depurar", array()),
        "configuracion" => $this->valor($configuracion, "depurar", array()),
        "filtros" => $this->valor($filtros, "depurar", array()),
        "navegacion" => $this->valor($navegacion, "depurar", array()),
        "secciones" => $this->valor($secciones, "depurar", array()),
        "politicas" => $this->valor($politicas, "depurar", array()),
        "canales" => $this->valor($canales, "depurar", array()),
        "frontend" => array(
          "limite_secciones" => $limiteSecciones,
          "puede_renderizar_catalogo_real" => $ready,
          "usar_catalogo_para_paginacion" => "/ecommercePublico/catalogo",
          "usar_producto_para_detalle" => "/ecommercePublico/producto/{slug}",
          "usar_preflight_para_carrito" => "/ecommercePublico/cotizacion_preflight"
        ),
        "fase_2" => $this->fase2BootstrapPublico($ready, $limiteSecciones, $estado, $filtros, $navegacion, $secciones),
        "guardrails" => array(
          "read_only" => true,
          "no_expone_secretos" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_descuenta_inventario" => true,
          "no_registra_cotizacion" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "ready" => false,
        "guardrails" => array("read_only" => true, "no_expone_secretos" => true)
      ));
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
        $filtrosAplicadosFallback = array(
          "q" => "",
          "mascota" => "",
          "necesidad" => "",
          "marca" => 0,
          "categoria" => 0,
          "disponibilidad" => "",
          "destacado" => false,
          "orden" => "relevancia",
          "limite" => 24
        );
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array(
          "configurado" => false,
          "items" => array(),
          "paginacion" => array("pagina" => 1, "limite" => 24, "total" => 0),
          "filtros_aplicados" => $filtrosAplicadosFallback,
          "ordenamientos_disponibles" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"),
          "frontend" => $this->frontendCatalogoPublico(1, 24, 0, 0, $filtrosAplicadosFallback),
          "fase_2" => $this->fase2CatalogoPublico(1, 24, 0, $filtrosAplicadosFallback),
          "guardrails" => array(
            "no_usa_ecom_legacy_como_fuente" => true,
            "no_muestra_stock_exacto" => true,
            "no_granel" => true,
            "no_descuenta_inventario" => true
          )
        ));
      }
      if (!$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        $filtrosAplicadosFallback = array(
          "q" => "",
          "mascota" => "",
          "necesidad" => "",
          "marca" => 0,
          "categoria" => 0,
          "disponibilidad" => "",
          "destacado" => false,
          "orden" => "relevancia",
          "limite" => 24
        );
        return $this->respuesta(false, "info", "Catalogo publico ecommerce aun no configurado", array(
          "configurado" => false,
          "items" => array(),
          "paginacion" => array("pagina" => 1, "limite" => 24, "total" => 0),
          "filtros_aplicados" => $filtrosAplicadosFallback,
          "ordenamientos_disponibles" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"),
          "frontend" => $this->frontendCatalogoPublico(1, 24, 0, 0, $filtrosAplicadosFallback),
          "fase_2" => $this->fase2CatalogoPublico(1, 24, 0, $filtrosAplicadosFallback),
          "guardrails" => array("no_ecom_legacy_fuente" => true, "no_granel" => true, "no_stock_exacto" => true),
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
        $where[] = "(pub.mascota_especie=:mascota OR FIND_IN_SET(:mascota_set, REPLACE(pub.mascota_especie, ' ', ''))>0)";
        $params[":mascota"] = $mascota;
        $params[":mascota_set"] = $mascota;
      }
      $necesidad = $this->limpiarFiltroPublico($this->valor($filtros, "necesidad", ""));
      if ($necesidad !== "") {
        $where[] = "pub.necesidades_json LIKE :necesidad";
        $params[":necesidad"] = "%\"" . $necesidad . "\"%";
      }
      $marcaRaw = $this->valor($filtros, "marca_id", $this->valor($filtros, "marca", 0));
      $marca = intval($marcaRaw);
      $marcaSlug = $this->limpiarFiltroPublico($this->valor($filtros, "marca_slug", is_numeric($marcaRaw) ? "" : $marcaRaw));
      $marcaIdFiltro = $this->marcaIdFiltroPublico($db, $marca, $marcaSlug);
      if ($marcaIdFiltro > 0) {
        $where[] = "p.id_marca_erp = :marca";
        $params[":marca"] = $marcaIdFiltro;
      } elseif ($marca > 0 || $marcaSlug !== "") {
        $where[] = "p.id_marca_erp = -1";
      }
      $categoriaRaw = $this->valor($filtros, "categoria_id", $this->valor($filtros, "categoria", 0));
      $categoria = intval($categoriaRaw);
      $categoriaSlug = $this->limpiarFiltroPublico($this->valor($filtros, "categoria_slug", is_numeric($categoriaRaw) ? "" : $categoriaRaw));
      $incluirHijos = intval($this->valor($filtros, "incluir_hijos", 0)) === 1;
      $categoriaIds = $this->categoriaIdsFiltroPublico($db, $categoria, $categoriaSlug, $incluirHijos);
      if (!empty($categoriaIds)) {
        $where[] = "pc.id_categoria_erp IN (" . implode(",", array_map("intval", $categoriaIds)) . ")";
      } elseif ($categoria > 0 || $categoriaSlug !== "") {
        $where[] = "pc.id_categoria_erp = -1";
      }
      $disponibilidad = trim((string) $this->valor($filtros, "disponibilidad", ""));
      $this->agregarFiltroDisponibilidadPublica($where, $disponibilidad);
      if (intval($this->valor($filtros, "destacado", 0)) === 1) {
        $where[] = "pub.destacado=1";
      }
      $orden = trim((string) $this->valor($filtros, "orden", "relevancia"));
      $ordenSql = $this->ordenCatalogoPublicoSql($orden);

      $sqlBase = $this->sqlPublicacionesBase($where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM (" . $sqlBase . ") t");
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());

      $sql = $sqlBase . " ORDER BY " . $ordenSql . " LIMIT " . intval($limite) . " OFFSET " . intval($offset);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearPublicacion($fila);
      }

      $filtrosAplicados = array(
        "q" => $q,
        "mascota" => $mascota,
        "necesidad" => $necesidad,
        "marca" => $marcaIdFiltro,
        "marca_id" => $marcaIdFiltro,
        "marca_slug" => $marcaSlug,
        "categoria" => $categoria,
        "categoria_id" => $categoria,
        "categoria_slug" => $categoriaSlug,
        "incluir_hijos" => $incluirHijos,
        "disponibilidad" => in_array($disponibilidad, $this->estadosDisponibilidadPublica(), true) ? $disponibilidad : "",
        "destacado" => intval($this->valor($filtros, "destacado", 0)) === 1,
        "orden" => $this->ordenCatalogoPublicoNormalizado($orden),
        "limite" => $limite
      );

      return $this->respuesta(false, "success", "Catalogo publico consultado", array(
        "configurado" => true,
        "items" => $items,
        "paginacion" => array("pagina" => $pagina, "limite" => $limite, "total" => $total),
        "filtros_aplicados" => $filtrosAplicados,
        "ordenamientos_disponibles" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"),
        "frontend" => $this->frontendCatalogoPublico($pagina, $limite, $total, count($items), $filtrosAplicados),
        "fase_2" => $this->fase2CatalogoPublico($pagina, $limite, $total, $filtrosAplicados),
        "guardrails" => array(
          "solo_publicados" => true,
          "no_stock_exacto" => true,
          "no_granel" => true,
          "no_ecom_legacy_fuente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: entregar manifiesto de catalogo robusto para que frontend no hardcodee filtros ni reglas.
   * Impacto: Frontend ecommerce; inicia Fase 2 con contrato estable de busqueda/listado/navegacion.
   * Contrato: solo lectura; no expone stock exacto, no incluye granel y no consulta legacy ecom_* como fuente.
   */
  public function catalogoManifestPublico($opciones = array()) {
    try {
      $limitePreview = max(1, min(6, intval($this->valor($opciones, "limite_preview", 3))));
      $estado = $this->estadoApiPublica();
      $filtros = $this->filtrosPublicos();
      $navegacion = $this->navegacionPublica(array("limite" => 12));
      $catalogo = $this->catalogoPublico(array("limite" => $limitePreview));
      $catalogoVacio = $this->catalogoPublico(array("q" => "__sin_resultados_catalogo_frontend__", "limite" => $limitePreview));
      $items = $this->valor($catalogo, array("depurar", "items"), array());
      $slugEjemplo = !empty($items) ? (string) $this->valor($items[0], "slug", "") : "";

      return $this->respuesta(false, "success", "Manifest de catalogo ecommerce consultado", array(
        "fase" => "fase_2_api_catalogo_robusta",
        "estado_catalogo" => array(
          "ready" => (bool) $this->valor($estado, array("depurar", "ready"), false),
          "total_publicadas" => intval($this->valor($estado, array("depurar", "publicaciones", "total_publicadas"), 0)),
          "catalogo_publico_vacio" => (bool) $this->valor($estado, array("depurar", "publicaciones", "catalogo_publico_vacio"), true),
          "slug_ejemplo" => $slugEjemplo
        ),
        "parametros_soportados" => array(
          "q" => array("tipo" => "string", "uso" => "Busqueda por texto libre."),
          "mascota" => array("tipo" => "string", "fuente" => "/ecommercePublico/filtros depurar.mascotas"),
          "necesidad" => array("tipo" => "string", "fuente" => "/ecommercePublico/filtros depurar.necesidades"),
          "marca" => array("tipo" => "int|string compatible", "fuente" => "/ecommercePublico/marcas depurar.items"),
          "marca_id" => array("tipo" => "int", "fuente" => "/ecommercePublico/marcas depurar.items.id"),
          "marca_slug" => array("tipo" => "string", "fuente" => "/ecommercePublico/marcas depurar.items.slug_publico"),
          "categoria" => array("tipo" => "int|string compatible", "fuente" => "/ecommercePublico/categorias depurar.items"),
          "categoria_id" => array("tipo" => "int", "fuente" => "/ecommercePublico/categorias depurar.items.id"),
          "categoria_slug" => array("tipo" => "string", "fuente" => "/ecommercePublico/categorias depurar.items.slug_publico"),
          "incluir_hijos" => array("tipo" => "bool", "valores" => array("1"), "uso" => "Incluye productos de subcategorias para landings madre."),
          "disponibilidad" => array("tipo" => "enum", "valores" => $this->estadosDisponibilidadPublica()),
          "destacado" => array("tipo" => "bool", "valores" => array("1")),
          "orden" => array("tipo" => "enum", "valores" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes")),
          "pagina" => array("tipo" => "int", "min" => 1, "default" => 1),
          "limite" => array("tipo" => "int", "min" => 1, "max" => 60, "default" => 24)
        ),
        "ordenamientos" => array(
          array("valor" => "relevancia", "label" => "Relevancia"),
          array("valor" => "nombre", "label" => "Nombre"),
          array("valor" => "precio_asc", "label" => "Precio menor a mayor"),
          array("valor" => "precio_desc", "label" => "Precio mayor a menor"),
          array("valor" => "recientes", "label" => "Recientes")
        ),
        "endpoints_relacionados" => array(
          "handoff" => "/ecommercePublico/frontend_handoff",
          "configuracion_inicial" => "/ecommercePublico/configuracion_inicial",
          "contenido_manifest" => "/ecommercePublico/contenido_manifest",
          "contenido_pagina_home" => "/ecommercePublico/contenido_pagina?pagina=home",
          "bootstrap_alias_legacy" => "/ecommercePublico/bootstrap",
          "fase_2_checklist" => "/ecommercePublico/fase_2_checklist",
          "catalogo" => "/ecommercePublico/catalogo",
          "producto" => "/ecommercePublico/producto/{slug}",
          "disponibilidad" => "/ecommercePublico/disponibilidad?slug={slug}",
          "filtros" => "/ecommercePublico/filtros",
          "categorias" => "/ecommercePublico/categorias",
          "marcas" => "/ecommercePublico/marcas",
          "catalogo_filtros" => "/ecommercePublico/catalogo_filtros",
          "navegacion" => "/ecommercePublico/navegacion",
          "secciones" => "/ecommercePublico/secciones",
          "busqueda_sugerencias" => "/ecommercePublico/busqueda_sugerencias"
        ),
        "ejemplos" => array(
          "primeros_productos" => "/ecommercePublico/catalogo?limite=24",
          "buscar" => "/ecommercePublico/catalogo?q=alimento&limite=24",
          "categoria_slug" => "/ecommercePublico/catalogo?categoria_slug=aves&incluir_hijos=1&limite=24",
          "marca_slug" => "/ecommercePublico/catalogo?marca_slug=tropical&limite=24",
          "facets_contextuales" => "/ecommercePublico/catalogo_filtros?categoria_slug=aves&incluir_hijos=1",
          "filtrar_disponibles" => "/ecommercePublico/catalogo?disponibilidad=disponible&orden=precio_asc&limite=24",
          "estado_vacio" => "/ecommercePublico/catalogo?q=__sin_resultados_catalogo_frontend__&limite=3",
          "checklist_fase_2" => "/ecommercePublico/fase_2_checklist",
          "producto_detalle" => $slugEjemplo !== "" ? "/ecommercePublico/producto/" . $slugEjemplo : "/ecommercePublico/producto/{slug}"
        ),
        "preview" => array(
          "catalogo" => $this->resumenRespuestaFrontendHandoff($catalogo, array("items", "paginacion", "frontend")),
          "estado_vacio" => $this->resumenRespuestaFrontendHandoff($catalogoVacio, array("items", "paginacion", "frontend")),
          "filtros" => $this->resumenRespuestaFrontendHandoff($filtros, array("mascotas", "necesidades", "marcas", "categorias", "disponibilidad")),
          "navegacion" => $this->resumenRespuestaFrontendHandoff($navegacion, array("primaria", "mascotas", "necesidades", "categorias", "categorias_arbol", "marcas", "disponibilidad")),
          "categorias" => $this->resumenRespuestaFrontendHandoff($this->categoriasPublicas(array()), array("items", "arbol", "resumen"))
        ),
        "guardrails" => array(
          "solo_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "precio_es_estimado" => true,
          "cotizacion_requiere_dryrun" => true,
          "no_checkout" => true,
          "no_toca_ecom_legacy" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "fase" => "fase_2_api_catalogo_robusta",
        "guardrails" => array("read_only" => true, "no_granel" => true)
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-06
   * Proposito: entregar checklist de cierre de Fase 2 para que frontend externo integre sin leer docs internas.
   * Impacto: Ecommerce publico; define endpoints obligatorios, escenarios, guardrails y criterios para pasar a Fase 3.
   * Contrato: solo lectura; no escribe BD, no ejecuta DDL, no registra cotizaciones y no toca inventario.
   */
  public function fase2ChecklistPublico($opciones = array()) {
    try {
      $estado = $this->estadoApiPublica();
      $publicadas = intval($this->valor($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
      $ready = !empty($estado["depurar"]["ready"]);
      $endpoints = array(
        array("grupo" => "arranque", "metodo" => "GET", "ruta" => "/ecommercePublico/frontend_handoff", "obligatorio" => true, "uso_frontend" => "Punto de entrada para descubrir estado, ejemplos y pruebas."),
        array("grupo" => "arranque", "metodo" => "GET", "ruta" => "/ecommercePublico/configuracion_inicial", "obligatorio" => true, "uso_frontend" => "Primer render de home/catalogo con nombre claro."),
        array("grupo" => "arranque", "metodo" => "GET", "ruta" => "/ecommercePublico/bootstrap", "obligatorio" => false, "uso_frontend" => "Alias legacy de configuracion_inicial."),
        array("grupo" => "contenido", "metodo" => "GET", "ruta" => "/ecommercePublico/contenido_manifest", "obligatorio" => true, "uso_frontend" => "Plantillas, slots y tipos de bloque del CMS ligero."),
        array("grupo" => "contenido", "metodo" => "GET", "ruta" => "/ecommercePublico/contenido_pagina", "obligatorio" => true, "uso_frontend" => "Banners, colecciones y estructura editorial por pagina."),
        array("grupo" => "catalogo", "metodo" => "GET", "ruta" => "/ecommercePublico/catalogo_manifest", "obligatorio" => true, "uso_frontend" => "Parametros, ordenamientos, previews y reglas."),
        array("grupo" => "catalogo", "metodo" => "GET", "ruta" => "/ecommercePublico/catalogo", "obligatorio" => true, "uso_frontend" => "Listado paginado y filtros activos."),
        array("grupo" => "catalogo", "metodo" => "GET", "ruta" => "/ecommercePublico/filtros", "obligatorio" => true, "uso_frontend" => "Facetas para filtros UI."),
        array("grupo" => "catalogo", "metodo" => "GET", "ruta" => "/ecommercePublico/navegacion", "obligatorio" => true, "uso_frontend" => "Menus, chips y rutas explorables."),
        array("grupo" => "catalogo", "metodo" => "GET", "ruta" => "/ecommercePublico/secciones", "obligatorio" => true, "uso_frontend" => "Bloques de home y colecciones."),
        array("grupo" => "busqueda", "metodo" => "GET", "ruta" => "/ecommercePublico/busqueda_sugerencias", "obligatorio" => true, "uso_frontend" => "Autocomplete y busqueda guiada."),
        array("grupo" => "producto", "metodo" => "GET", "ruta" => "/ecommercePublico/producto/{slug}", "obligatorio" => true, "uso_frontend" => "Ficha publica con relacionados, breadcrumbs, acciones y SEO."),
        array("grupo" => "producto", "metodo" => "GET", "ruta" => "/ecommercePublico/disponibilidad", "obligatorio" => true, "uso_frontend" => "Estado publico sin stock exacto."),
        array("grupo" => "carrito", "metodo" => "POST", "ruta" => "/ecommercePublico/cotizacion_dryrun", "obligatorio" => true, "uso_frontend" => "Validar carrito antes de contacto."),
        array("grupo" => "carrito", "metodo" => "POST", "ruta" => "/ecommercePublico/cotizacion_preflight", "obligatorio" => true, "uso_frontend" => "Confirmar datos y generar CTA WhatsApp."),
        array("grupo" => "seo", "metodo" => "GET", "ruta" => "/ecommercePublico/seo", "obligatorio" => true, "uso_frontend" => "Sitemap, canonical, robots y JSON-LD."),
        array("grupo" => "legal", "metodo" => "GET", "ruta" => "/ecommercePublico/politicas", "obligatorio" => true, "uso_frontend" => "Politicas visibles y textos operativos."),
        array("grupo" => "futuro", "metodo" => "GET", "ruta" => "/ecommercePublico/canales_estado", "obligatorio" => false, "uso_frontend" => "Estado de canales y partners futuros.")
      );
      $escenarios = array(
        array("codigo" => "configuracion_inicial_home", "endpoint" => "GET /ecommercePublico/configuracion_inicial?limite_secciones=3", "esperado" => "ready=true, depurar.fase_2.primer_render y guardrails."),
        array("codigo" => "contenido_manifest", "endpoint" => "GET /ecommercePublico/contenido_manifest", "esperado" => "plantillas, slots, tipos_bloque y read_only=true."),
        array("codigo" => "contenido_home", "endpoint" => "GET /ecommercePublico/contenido_pagina?pagina=home", "esperado" => "slots home.hero, home.categorias, home.destacados y fuentes dinamicas de catalogo."),
        array("codigo" => "catalogo_base", "endpoint" => "GET /ecommercePublico/catalogo?limite=3", "esperado" => "items, paginacion, frontend y fase_2."),
        array("codigo" => "catalogo_filtro_disponible", "endpoint" => "GET /ecommercePublico/catalogo?disponibilidad=disponible&orden=precio_asc&limite=3", "esperado" => "filtro activo disponibilidad y precio estimado."),
        array("codigo" => "catalogo_sin_resultados", "endpoint" => "GET /ecommercePublico/catalogo?q=__sin_resultados_catalogo_frontend__&limite=3", "esperado" => "frontend.estado_vacio.mostrar=true."),
        array("codigo" => "producto_real", "endpoint" => "GET /ecommercePublico/producto/{slug_publicado}", "esperado" => "item, breadcrumbs, relacionados, acciones y fase_2."),
        array("codigo" => "disponibilidad_real", "endpoint" => "GET /ecommercePublico/disponibilidad?slug={slug_publicado}", "esperado" => "mostrar_cantidad_exacta=false y fase_2.guardrails.no_stock_exacto=true."),
        array("codigo" => "carrito_dryrun", "endpoint" => "POST /ecommercePublico/cotizacion_dryrun", "esperado" => "no_escribe_bd=true, no_descuenta_inventario=true y fase_2.flujo.endpoint_siguiente."),
        array("codigo" => "cotizacion_preflight", "endpoint" => "POST /ecommercePublico/cotizacion_preflight", "esperado" => "folio_no_persistido=true, CTA WhatsApp si aplica y fase_2.embudo."),
        array("codigo" => "seo_runtime", "endpoint" => "GET /ecommercePublico/seo?limite=20", "esperado" => "rutas, sitemap_xml_sugerido, canonical y no_granel=true.")
      );
      $criterios = array(
        "green_gate_ok" => $ready && $publicadas > 0,
        "publicaciones_reales_minimas" => $publicadas >= 6,
        "frontend_no_lee_filesystem" => true,
        "catalogo_fase_2_expuesto" => true,
        "seo_fase_2_expuesto" => true,
        "carrito_preflight_sin_persistencia" => true,
        "cotizacion_registrar_sigue_bloqueado" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "sin_checkout_pago_online" => true
      );
      $puedeCerrar = !in_array(false, $criterios, true);

      return $this->respuesta(false, $puedeCerrar ? "success" : "warning", $puedeCerrar ? "Checklist Fase 2 listo para revision frontend" : "Checklist Fase 2 con pendientes", array(
        "fase" => "fase_2_api_catalogo_robusta",
        "estado" => $puedeCerrar ? "lista_para_revision_frontend" : "en_progreso",
        "puede_pasar_a_fase_3" => $puedeCerrar,
        "fase_3_siguiente" => "carrito_cotizacion_avanzada",
        "resumen" => array(
          "endpoints_obligatorios_total" => count(array_filter($endpoints, function($endpoint) { return !empty($endpoint["obligatorio"]); })),
          "escenarios_prueba_total" => count($escenarios),
          "publicaciones_reales" => $publicadas,
          "senal_frontend" => $this->valor($estado, array("depurar", "senal_frontend"), "")
        ),
        "endpoints_obligatorios" => $endpoints,
        "orden_integracion" => array(
          "frontend_handoff",
          "bootstrap",
          "catalogo_manifest",
          "filtros_navegacion_secciones",
          "catalogo_listado",
          "producto_detalle",
          "disponibilidad",
          "cotizacion_dryrun",
          "cotizacion_preflight",
          "seo"
        ),
        "escenarios_prueba" => $escenarios,
        "criterios_pase_fase_3" => $criterios,
        "pendientes" => $puedeCerrar ? array("revision_operativa_frontend_externo") : array("resolver_criterios_falsos"),
        "guardrails" => array(
          "read_only" => true,
          "no_requiere_filesystem" => true,
          "no_escribe_bd" => true,
          "no_ejecuta_ddl" => true,
          "no_registra_cotizacion" => true,
          "no_descuenta_inventario" => true,
          "no_stock_exacto" => true,
          "no_granel" => true,
          "no_ecom_legacy_fuente" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "fase" => "fase_2_api_catalogo_robusta",
        "estado" => "error_checklist",
        "guardrails" => array("read_only" => true, "no_escribe_bd" => true)
      ));
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
        return $this->respuesta(false, "info", "Producto publico no disponible", array(
          "item" => null,
          "variantes" => array(),
          "relacionados" => array(),
          "breadcrumbs" => array(),
          "seo" => null
        ));
      }
      $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'", "pub.slug=:slug");
      $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " LIMIT 1");
      $stmt->execute(array(":slug" => $slug));
      $fila = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$fila) {
        return $this->respuesta(false, "info", "Producto publico no encontrado", array(
          "item" => null,
          "variantes" => array(),
          "relacionados" => array(),
          "breadcrumbs" => array(),
          "seo" => null
        ));
      }
      $item = $this->formatearPublicacion($fila);
      $variantes = $this->variantesProductoPublico($db, $fila);
      $relacionados = $this->relacionadosProductoPublico($db, $fila);
      $breadcrumbs = $this->breadcrumbsProductoPublico($item);
      $seo = $this->seoProductoPublico($item);
      $acciones = array(
        "puede_cotizar" => !empty($item["permite_cotizacion"]),
        "puede_whatsapp" => !empty($item["permite_whatsapp"]),
        "mostrar_precio" => $item["precio"] !== null,
        "mostrar_disponibilidad" => $item["disponibilidad"] !== "consultar_disponibilidad"
      );
      return $this->respuesta(false, "success", "Producto publico consultado", array(
        "item" => $item,
        "variantes" => $variantes,
        "relacionados" => $relacionados,
        "breadcrumbs" => $breadcrumbs,
        "seo" => $seo,
        "acciones" => $acciones,
        "fase_2" => $this->fase2ProductoPublico($item, $variantes, $relacionados, $breadcrumbs, $seo, $acciones),
        "guardrails" => array(
          "solo_publicado" => true,
          "solo_relacionados_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_descuenta_inventario" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "item" => null,
        "variantes" => array(),
        "relacionados" => array()
      ));
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
        $gruposVacios = array(
          "mascotas" => array(),
          "necesidades" => array(),
          "marcas" => array(),
          "categorias" => array(),
          "disponibilidad" => array()
        );
        return $this->respuesta(false, "info", "Filtros ecommerce aun no configurados", array(
          "mascotas" => array(),
          "necesidades" => array(),
          "marcas" => array(),
          "categorias" => array(),
          "disponibilidad" => array(),
          "fase_2" => $this->fase2FiltrosPublicos($gruposVacios)
        ));
      }
      $baseWhere = "pub.estatus_publicacion='publicado' AND p.estatus='activo' AND s.estatus='activo'";
      $mascotasFilas = $db->query("SELECT pub.mascota_especie valor
        FROM erp_ecommerce_publicaciones pub
        INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
        WHERE " . $baseWhere . " AND TRIM(COALESCE(pub.mascota_especie,''))<>''
        ORDER BY pub.mascota_especie")->fetchAll(PDO::FETCH_ASSOC);
      $mascotas = $this->agruparMascotasFiltro($mascotasFilas);
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
      $disponibilidad = array();
      $where = array("pub.estatus_publicacion='publicado'", "p.estatus='activo'", "s.estatus='activo'");
      $stmtDisponibilidad = $db->query($this->sqlPublicacionesBase($where));
      foreach ($stmtDisponibilidad->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $estado = $this->disponibilidadPublicaSugerida($fila);
        if (!isset($disponibilidad[$estado])) {
          $disponibilidad[$estado] = array("valor" => $estado, "etiqueta" => $this->etiquetaDisponibilidadPublica($estado), "total" => 0);
        }
        $disponibilidad[$estado]["total"]++;
      }
      $disponibilidadOrdenada = array();
      foreach ($this->estadosDisponibilidadPublica() as $estado) {
        if (isset($disponibilidad[$estado])) {
          $disponibilidadOrdenada[] = $disponibilidad[$estado];
        }
      }
      $necesidades = $this->necesidadesPublicas($db);
      $gruposFiltros = array(
        "mascotas" => $mascotas,
        "necesidades" => $necesidades,
        "marcas" => $marcas,
        "categorias" => $categorias,
        "disponibilidad" => $disponibilidadOrdenada
      );

      return $this->respuesta(false, "success", "Filtros ecommerce consultados", array(
        "mascotas" => $mascotas,
        "necesidades" => $necesidades,
        "marcas" => $marcas,
        "categorias" => $categorias,
        "disponibilidad" => $disponibilidadOrdenada,
        "fase_2" => $this->fase2FiltrosPublicos($gruposFiltros)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage());
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-15
   * Proposito: publicar categorias jerarquicas para navegacion, mega menu y landings SEO.
   * Impacto: Frontend Artiani; habilita /categoria/{slug} sin leer tablas internas ni depender de fallbacks.
   * Contrato: read-only; deriva totales de publicaciones activas, excluye granel y no muestra stock exacto.
   */
  public function categoriasPublicas($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones") || !$this->tablaExiste($db, "erp_catalogo_categorias")) {
        return $this->respuesta(false, "info", "Categorias ecommerce aun no configuradas", array(
          "configurado" => false,
          "items" => array(),
          "arbol" => array(),
          "resumen" => array("total_categorias" => 0, "total_raices" => 0),
          "guardrails" => array("read_only" => true, "solo_publicados" => true, "no_granel" => true)
        ));
      }

      $items = $this->categoriasPublicasItems($db);
      $arbol = $this->categoriasPublicasArbol($items);
      return $this->respuesta(false, "success", "Categorias ecommerce consultadas", array(
        "configurado" => true,
        "items" => array_values($items),
        "arbol" => $arbol,
        "resumen" => array(
          "total_categorias" => count($items),
          "total_raices" => count($arbol),
          "solo_con_productos_publicables" => true
        ),
        "frontend" => array(
          "ruta_categoria" => "/categoria/{slug_publico}",
          "catalogo_por_slug" => "/ecommercePublico/catalogo?categoria_slug={slug_publico}&incluir_hijos=1&limite=24",
          "usar_arbol_para_mega_menu" => true,
          "usar_items_para_busqueda_y_sitemap" => true
        ),
        "cms_pendiente" => array(
          "imagenes_y_textos_editables" => true,
          "campos" => array("imagen_menu", "imagen_card", "imagen_banner", "descripcion_corta", "seo_title", "seo_description", "orden", "destacado_home")
        ),
        "guardrails" => array(
          "read_only" => true,
          "solo_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_expone_costos" => true,
          "no_lee_ecom_legacy" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "items" => array(),
        "arbol" => array(),
        "guardrails" => array("read_only" => true)
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: publicar marcas con slug estable para landings SEO y filtros seguros.
   * Impacto: Frontend Artiani; habilita /marca/{slug} sin usar texto libre como filtro.
   * Contrato: read-only; deriva totales de publicaciones activas, excluye granel y no muestra stock exacto.
   */
  public function marcasPublicas($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones") || !$this->tablaExiste($db, "erp_catalogo_marcas")) {
        return $this->respuesta(false, "info", "Marcas ecommerce aun no configuradas", array(
          "configurado" => false,
          "items" => array(),
          "resumen" => array("total_marcas" => 0),
          "guardrails" => array("read_only" => true, "solo_publicados" => true, "no_granel" => true)
        ));
      }
      $items = $this->marcasPublicasItems($db);
      return $this->respuesta(false, "success", "Marcas ecommerce consultadas", array(
        "configurado" => true,
        "items" => array_values($items),
        "resumen" => array(
          "total_marcas" => count($items),
          "solo_con_productos_publicables" => true
        ),
        "frontend" => array(
          "ruta_marca" => "/marca/{slug_publico}",
          "catalogo_por_slug" => "/ecommercePublico/catalogo?marca_slug={slug_publico}&limite=24",
          "usar_items_para_landings_y_sitemap" => true
        ),
        "cms_pendiente" => array(
          "imagenes_y_textos_editables" => true,
          "campos" => array("logo", "imagen_banner", "descripcion_corta", "seo_title", "seo_description", "orden", "destacada_home")
        ),
        "guardrails" => array(
          "read_only" => true,
          "solo_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_expone_costos" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: entregar facets contextuales con conteos reales para el catalogo publico.
   * Impacto: Frontend ecommerce; evita filtros que regresen catalogo completo o productos mezclados.
   * Contrato: read-only; usa el mismo resolver que catalogoPublico y respeta filtros invalidos como vacios.
   */
  public function catalogoFiltrosPublicos($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Filtros inteligentes ecommerce aun no configurados", array(
          "configurado" => false,
          "aplicados" => array(),
          "categorias" => array(),
          "marcas" => array(),
          "precios" => array("min" => null, "max" => null),
          "ordenamientos" => $this->ordenamientosCatalogoPublico()
        ));
      }
      $base = $this->normalizarFiltrosCatalogoPublico($db, $opciones);
      $catalogoBase = $this->catalogoPublico(array_merge($base, array("limite" => 1)));
      $categorias = array();
      foreach ($this->categoriasPublicasItems($db) as $cat) {
        $f = $base;
        $f["categoria_id"] = intval($cat["id"]);
        $f["categoria_slug"] = "";
        $f["incluir_hijos"] = intval($this->valor($base, "incluir_hijos", 0));
        $total = intval($this->valor($this->catalogoPublico(array_merge($f, array("limite" => 1))), array("depurar", "paginacion", "total"), 0));
        $activo = intval($this->valor($base, "categoria_id", 0)) === intval($cat["id"]) || $this->valor($base, "categoria_slug", "") === $cat["slug_publico"];
        $categorias[] = array(
          "id" => intval($cat["id"]),
          "nombre" => $cat["nombre"],
          "nombre_completo" => $cat["nombre_completo"],
          "slug" => $cat["slug_publico"],
          "path_slug" => $this->valor($cat, "path_slug", $cat["slug_publico"]),
          "total" => $total,
          "activo" => $activo,
          "disabled" => $total <= 0 && !$activo
        );
      }
      $marcas = array();
      foreach ($this->marcasPublicasItems($db) as $marca) {
        $f = $base;
        $f["marca_id"] = intval($marca["id"]);
        $f["marca_slug"] = "";
        $total = intval($this->valor($this->catalogoPublico(array_merge($f, array("limite" => 1))), array("depurar", "paginacion", "total"), 0));
        $activo = intval($this->valor($base, "marca_id", 0)) === intval($marca["id"]) || $this->valor($base, "marca_slug", "") === $marca["slug_publico"];
        $marcas[] = array(
          "id" => intval($marca["id"]),
          "nombre" => $marca["nombre"],
          "slug" => $marca["slug_publico"],
          "total" => $total,
          "activo" => $activo,
          "disabled" => $total <= 0 && !$activo
        );
      }
      $precios = $this->rangoPreciosCatalogoPublico($catalogoBase);
      return $this->respuesta(false, "success", "Filtros inteligentes ecommerce consultados", array(
        "configurado" => true,
        "aplicados" => array(
          "q" => $this->valor($base, "q", ""),
          "categoria_id" => intval($this->valor($base, "categoria_id", 0)) ?: null,
          "categoria_slug" => $this->valor($base, "categoria_slug", ""),
          "marca_id" => intval($this->valor($base, "marca_id", 0)) ?: null,
          "marca_slug" => $this->valor($base, "marca_slug", ""),
          "mascota" => $this->valor($base, "mascota", ""),
          "necesidad" => $this->valor($base, "necesidad", "")
        ),
        "resultado_actual" => array(
          "total" => intval($this->valor($catalogoBase, array("depurar", "paginacion", "total"), 0)),
          "hay_resultados" => intval($this->valor($catalogoBase, array("depurar", "paginacion", "total"), 0)) > 0
        ),
        "categorias" => $categorias,
        "marcas" => $marcas,
        "precios" => $precios,
        "ordenamientos" => $this->ordenamientosCatalogoPublico(),
        "guardrails" => array(
          "read_only" => true,
          "solo_publicados" => true,
          "no_granel" => true,
          "filtro_invalido_devuelve_vacio" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("categorias" => array(), "marcas" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: entregar sugerencias publicas para buscador ecommerce.
   * Impacto: Frontend publico; permite autocompletar productos, marcas, categorias, mascotas y necesidades.
   * Contrato: solo lectura; no registra busquedas, no expone stock exacto y solo usa publicaciones vigentes.
   */
  public function busquedaSugerenciasPublicas($opciones = array()) {
    try {
      $q = trim((string) $this->valor($opciones, "q", ""));
      $limite = max(1, min(12, intval($this->valor($opciones, "limite", 6))));
      $catalogo = $this->catalogoPublico(array(
        "q" => $q,
        "limite" => $limite,
        "orden" => "relevancia"
      ));
      $filtros = $this->filtrosPublicos();
      $productos = array();
      foreach ($this->valor($catalogo, array("depurar", "items"), array()) as $item) {
        $productos[] = array(
          "tipo" => "producto",
          "label" => $this->valor($item, "nombre", ""),
          "subtitulo" => trim((string) $this->valor($item, "marca", "") . " " . (string) $this->valor($item, "presentacion", "")),
          "valor" => $this->valor($item, "slug", ""),
          "url" => "/ecommercePublico/producto/" . $this->valor($item, "slug", ""),
          "imagen" => $this->valor($item, "imagen", null),
          "precio" => $this->valor($item, "precio", null),
          "moneda" => $this->valor($item, "moneda", null),
          "disponibilidad" => $this->valor($item, "disponibilidad", "consultar_disponibilidad")
        );
      }

      $depFiltros = $this->valor($filtros, "depurar", array());
      $grupos = array(
        "productos" => $productos,
        "marcas" => $this->filtrarSugerenciasTaxonomia($this->valor($depFiltros, "marcas", array()), $q, $limite, "marca"),
        "categorias" => $this->filtrarSugerenciasTaxonomia($this->valor($depFiltros, "categorias", array()), $q, $limite, "categoria"),
        "mascotas" => $this->filtrarSugerenciasTaxonomia($this->valor($depFiltros, "mascotas", array()), $q, $limite, "mascota"),
        "necesidades" => $this->filtrarSugerenciasTaxonomia($this->valor($depFiltros, "necesidades", array()), $q, $limite, "necesidad")
      );
      $total = 0;
      foreach ($grupos as $items) {
        $total += count($items);
      }

      return $this->respuesta(false, "success", "Sugerencias ecommerce consultadas", array(
        "q" => $q,
        "configurado" => !empty($this->valor($catalogo, array("depurar", "configurado"), false)),
        "grupos" => $grupos,
        "resumen" => array(
          "total_sugerencias" => $total,
          "productos" => count($grupos["productos"]),
          "marcas" => count($grupos["marcas"]),
          "categorias" => count($grupos["categorias"]),
          "mascotas" => count($grupos["mascotas"]),
          "necesidades" => count($grupos["necesidades"]),
          "sin_resultados" => $total === 0
        ),
        "frontend" => array(
          "registrar_busqueda_futura" => "/ecommercePublico/busqueda_registrar",
          "usar_catalogo_para_resultados" => "/ecommercePublico/catalogo?q=" . rawurlencode($q),
          "min_caracteres_recomendado" => 2
        ),
        "fase_2" => $this->fase2BusquedaSugerenciasPublicas($q, $limite, $grupos, $total),
        "guardrails" => array(
          "read_only" => true,
          "no_registra_busqueda" => true,
          "solo_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_expone_costos" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "q" => "",
        "grupos" => array(),
        "fase_2" => $this->fase2BusquedaSugerenciasPublicas("", 6, array(), 0),
        "guardrails" => array("read_only" => true, "no_registra_busqueda" => true)
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: entregar navegacion publica para menus y chips del ecommerce.
   * Impacto: Frontend; centraliza rutas por mascota, necesidad, categoria, marca y disponibilidad.
   * Contrato: read-only; no escribe BD y solo se deriva de filtros publicados.
   */
  public function navegacionPublica($opciones = array()) {
    try {
      $limite = max(1, min(30, intval($this->valor($opciones, "limite", 12))));
      $filtros = $this->filtrosPublicos();
      $dep = $this->valor($filtros, "depurar", array());
      $categorias = $this->categoriasPublicas(array("limite" => 200));
      $depCategorias = $this->valor($categorias, "depurar", array());
      $navegacion = array(
        "primaria" => array(
          array("codigo" => "inicio", "label" => "Inicio", "url" => "/", "tipo" => "ruta"),
          array("codigo" => "catalogo", "label" => "Catalogo", "url" => "/catalogo", "tipo" => "ruta"),
          array("codigo" => "cotizacion", "label" => "Cotizacion", "url" => "/cotizacion", "tipo" => "ruta"),
          array("codigo" => "politicas", "label" => "Politicas", "url" => "/politicas", "tipo" => "ruta")
        ),
        "mascotas" => $this->itemsNavegacionDesdeFiltros($this->valor($dep, "mascotas", array()), "mascota", "mascota", $limite),
        "necesidades" => $this->itemsNavegacionDesdeFiltros($this->valor($dep, "necesidades", array()), "necesidad", "necesidad", $limite),
        "categorias" => $this->itemsNavegacionDesdeFiltros($this->valor($dep, "categorias", array()), "categoria", "categoria", $limite),
        "categorias_arbol" => $this->valor($depCategorias, "arbol", array()),
        "marcas" => $this->itemsNavegacionDesdeFiltros($this->valor($dep, "marcas", array()), "marca", "marca", $limite),
        "disponibilidad" => $this->itemsNavegacionDesdeFiltros($this->valor($dep, "disponibilidad", array()), "disponibilidad", "disponibilidad", $limite)
      );
      $total = 0;
      foreach ($navegacion as $grupo => $items) {
        $total += count($items);
      }
      return $this->respuesta(false, "success", "Navegacion ecommerce consultada", array(
        "configurado" => !empty($this->valor($filtros, array("depurar", "mascotas"), array())) || !empty($this->valor($filtros, array("depurar", "categorias"), array())),
        "limite" => $limite,
        "primaria" => $navegacion["primaria"],
        "mascotas" => $navegacion["mascotas"],
        "necesidades" => $navegacion["necesidades"],
        "categorias" => $navegacion["categorias"],
        "categorias_arbol" => $navegacion["categorias_arbol"],
        "marcas" => $navegacion["marcas"],
        "disponibilidad" => $navegacion["disponibilidad"],
        "resumen" => array(
          "total_items" => $total,
          "mascotas" => count($navegacion["mascotas"]),
          "necesidades" => count($navegacion["necesidades"]),
          "categorias" => count($navegacion["categorias"]),
          "categorias_arbol_raices" => count($navegacion["categorias_arbol"]),
          "marcas" => count($navegacion["marcas"]),
          "disponibilidad" => count($navegacion["disponibilidad"])
        ),
        "fase_2" => $this->fase2NavegacionPublica($navegacion, $limite),
        "guardrails" => array(
          "read_only" => true,
          "solo_derivado_de_publicaciones" => true,
          "no_granel" => true,
          "no_escribe_bd" => true,
          "no_expone_secretos" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "configurado" => false,
        "guardrails" => array("read_only" => true)
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: entregar bloques de catalogo preparados para home y secciones del frontend ecommerce.
   * Impacto: Frontend publico; reduce hardcodeo de destacados, disponibilidad, mascotas y necesidades.
   * Contrato: solo lectura; se alimenta de `catalogoPublico` y respeta publicaciones activas.
   */
  public function seccionesPublicas($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return $this->respuesta(false, "info", "Secciones ecommerce aun no configuradas", array(
          "configurado" => false,
          "secciones" => array(),
          "limite_por_seccion" => 6,
          "fase_2" => $this->fase2SeccionesPublicas(array(), 6),
          "guardrails" => array("solo_publicados" => true, "no_stock_exacto" => true, "no_granel" => true)
        ));
      }
      $limite = max(1, min(12, intval($this->valor($opciones, "limite", 6))));
      $incluirVacias = intval($this->valor($opciones, "incluir_vacias", 0)) === 1;
      $secciones = array();
      $definiciones = array(
        array("codigo" => "destacados", "titulo" => "Destacados", "tipo" => "curaduria", "params" => array("destacado" => 1, "orden" => "relevancia")),
        array("codigo" => "recientes", "titulo" => "Recien agregados", "tipo" => "catalogo", "params" => array("orden" => "recientes")),
        array("codigo" => "disponibles", "titulo" => "Disponibles", "tipo" => "disponibilidad", "params" => array("disponibilidad" => "disponible", "orden" => "relevancia")),
        array("codigo" => "pocas_piezas", "titulo" => "Pocas piezas", "tipo" => "disponibilidad", "params" => array("disponibilidad" => "pocas_piezas", "orden" => "relevancia"))
      );
      foreach ($definiciones as $definicion) {
        $this->agregarSeccionPublica($secciones, $definicion, $limite, $incluirVacias);
      }

      $filtros = $this->filtrosPublicos();
      foreach (array_slice($this->valor($filtros, array("depurar", "mascotas"), array()), 0, 6) as $mascota) {
        $valor = $this->limpiarFiltroPublico($this->valor($mascota, "valor", ""));
        if ($valor === "") { continue; }
        $this->agregarSeccionPublica($secciones, array(
          "codigo" => "mascota_" . $valor,
          "titulo" => "Para " . $this->etiquetaTaxonomiaPublica($valor),
          "tipo" => "mascota",
          "params" => array("mascota" => $valor, "orden" => "relevancia")
        ), $limite, $incluirVacias);
      }
      foreach (array_slice($this->valor($filtros, array("depurar", "necesidades"), array()), 0, 8) as $necesidad) {
        $valor = $this->limpiarFiltroPublico($this->valor($necesidad, "valor", ""));
        if ($valor === "") { continue; }
        $this->agregarSeccionPublica($secciones, array(
          "codigo" => "necesidad_" . $valor,
          "titulo" => $this->etiquetaTaxonomiaPublica($valor),
          "tipo" => "necesidad",
          "params" => array("necesidad" => $valor, "orden" => "relevancia")
        ), $limite, $incluirVacias);
      }

      return $this->respuesta(false, "success", "Secciones ecommerce consultadas", array(
        "configurado" => true,
        "secciones" => $secciones,
        "limite_por_seccion" => $limite,
        "fase_2" => $this->fase2SeccionesPublicas($secciones, $limite),
        "guardrails" => array(
          "solo_publicados" => true,
          "no_granel" => true,
          "no_stock_exacto" => true,
          "no_ecom_legacy_fuente" => true,
          "no_descuenta_inventario" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("secciones" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: entregar politicas publicas minimas para el ecommerce de mascotas.
   * Impacto: Frontend ecommerce; permite construir paginas de confianza y facturacion sin duplicar reglas.
   * Contrato: solo lectura; si la tabla futura no existe devuelve defaults operativos.
   */
  public function politicasPublicas() {
    try {
      $db = $this->getConexion();
      if ($db && $this->tablaExiste($db, "erp_ecommerce_politicas")) {
        $stmt = $db->query("SELECT codigo, tipo, titulo, resumen_publico resumen, contenido_html contenido, version, requiere_aceptacion, fecha_publicacion fecha_vigencia
          FROM erp_ecommerce_politicas
          WHERE estatus='publicado'
          ORDER BY orden ASC, titulo ASC");
        $items = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $items[] = $this->formatearPoliticaPublica($fila);
        }
        return $this->respuesta(false, "success", "Politicas ecommerce consultadas", array(
          "configurado" => true,
          "items" => $items,
          "guardrails" => $this->guardrailsPoliticasPublicas()
        ));
      }
      return $this->respuesta(false, "info", "Politicas ecommerce con defaults de Fase 1", array(
        "configurado" => false,
        "items" => $this->politicasPublicasDefault(),
        "guardrails" => $this->guardrailsPoliticasPublicas()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array()));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: consultar una politica publica por codigo/slug para rutas del frontend.
   * Impacto: Frontend ecommerce; soporta paginas especificas como facturacion y aviso de privacidad.
   * Contrato: solo lectura; no registra aceptaciones ni datos personales.
   */
  public function politicaPublica($slug) {
    $slug = $this->limpiarFiltroPublico($slug);
    if ($slug === "") {
      return $this->respuesta(true, "warning", "Politica no especificada", array("item" => null));
    }
    $politicas = $this->politicasPublicas();
    $items = isset($politicas["depurar"]["items"]) && is_array($politicas["depurar"]["items"]) ? $politicas["depurar"]["items"] : array();
    foreach ($items as $item) {
      if (isset($item["codigo"]) && $item["codigo"] === $slug) {
        return $this->respuesta(false, "success", "Politica ecommerce consultada", array(
          "item" => $item,
          "guardrails" => $this->guardrailsPoliticasPublicas()
        ));
      }
    }
    return $this->respuesta(false, "info", "Politica ecommerce no encontrada", array(
      "item" => null,
      "codigo" => $slug
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: entregar taxonomia publica para navegacion por mascota y necesidad.
   * Impacto: Ecommerce mascotas; permite que el sitio se sienta especializado desde Fase 1.
   * Contrato: solo lectura; no depende de clientes registrados ni mascotas guardadas.
   */
  public function taxonomiaMascotasPublica() {
    try {
      $db = $this->getConexion();
      if ($db && $this->tablaExiste($db, "erp_ecommerce_taxonomia_mascotas")) {
        $stmt = $db->query("SELECT codigo, tipo, parent_codigo, nombre, descripcion_publica descripcion, NULL icono, orden
          FROM erp_ecommerce_taxonomia_mascotas
          WHERE estatus='activo'
          ORDER BY tipo ASC, parent_codigo ASC, orden ASC, nombre ASC");
        $mascotas = array();
        $necesidades = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $item = $this->formatearTaxonomiaMascota($fila);
          if ($item["tipo"] === "necesidad") {
            $necesidades[] = $item;
          } else {
            $mascotas[] = $item;
          }
        }
        return $this->respuesta(false, "success", "Taxonomia ecommerce consultada", array(
          "configurado" => true,
          "mascotas" => $mascotas,
          "necesidades" => $necesidades,
          "guardrails" => $this->guardrailsTaxonomiaMascotas()
        ));
      }
      $defaults = $this->taxonomiaMascotasDefault();
      return $this->respuesta(false, "info", "Taxonomia ecommerce con defaults de Fase 1", array(
        "configurado" => false,
        "mascotas" => $defaults["mascotas"],
        "necesidades" => $defaults["necesidades"],
        "guardrails" => $this->guardrailsTaxonomiaMascotas()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("mascotas" => array(), "necesidades" => array()));
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: informar estado seguro de la capa multi-canal/API sin exponer secretos.
   * Impacto: Frontend/partners; permite planear integraciones sin activar autenticacion obligatoria.
   * Contrato: read-only; no escribe BD, no genera credenciales y no lista secrets.
   */
  public function canalesApiEstadoPublico($filtros = array()) {
    try {
      $db = $this->getConexion();
      $tablas = array(
        "erp_ecommerce_canales_api",
        "erp_ecommerce_api_credenciales",
        "erp_ecommerce_canal_publicaciones",
        "erp_ecommerce_api_nonces",
        "erp_ecommerce_api_logs"
      );
      $estadoTablas = array();
      $faltantes = array();
      foreach ($tablas as $tabla) {
        $existe = $db ? $this->tablaExiste($db, $tabla) : false;
        $estadoTablas[$tabla] = array("existe" => $existe);
        if (!$existe) {
          $faltantes[] = "tabla_pendiente_" . $tabla;
        }
      }

      $canales = array("total" => 0, "activos" => 0, "borrador" => 0, "suspendidos" => 0, "items" => array());
      $credenciales = array("activas" => 0, "vencidas_o_inactivas" => 0, "secretos_expuestos" => false);
      $allowlist = array("total_relaciones_activas" => 0);
      if ($db && empty($faltantes)) {
        $stmt = $db->query("SELECT id_canal_api, codigo, nombre, tipo_canal, estatus, url_publica, scopes_json, politica_precios,
            puede_ver_precio, puede_ver_disponibilidad, puede_cotizar, puede_registrar_cotizacion, mostrar_stock_exacto,
            rate_limit_minuto, rate_limit_dia
          FROM erp_ecommerce_canales_api
          ORDER BY tipo_canal ASC, codigo ASC");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
          $estatus = trim((string) $this->valor($fila, "estatus", ""));
          $canales["total"]++;
          if ($estatus === "activo") { $canales["activos"]++; }
          if ($estatus === "borrador") { $canales["borrador"]++; }
          if (in_array($estatus, array("suspendido", "inactivo"), true)) { $canales["suspendidos"]++; }
          $canales["items"][] = array(
            "codigo" => $this->valor($fila, "codigo", ""),
            "nombre" => $this->valor($fila, "nombre", ""),
            "tipo_canal" => $this->valor($fila, "tipo_canal", ""),
            "estatus" => $estatus,
            "url_publica" => $this->valor($fila, "url_publica", ""),
            "scopes" => $this->decodificarJsonLista($this->valor($fila, "scopes_json", "")),
            "politica_precios" => $this->valor($fila, "politica_precios", "publico"),
            "permisos_publicos" => array(
              "puede_ver_precio" => intval($this->valor($fila, "puede_ver_precio", 0)) === 1,
              "puede_ver_disponibilidad" => intval($this->valor($fila, "puede_ver_disponibilidad", 0)) === 1,
              "puede_cotizar" => intval($this->valor($fila, "puede_cotizar", 0)) === 1,
              "puede_registrar_cotizacion" => intval($this->valor($fila, "puede_registrar_cotizacion", 0)) === 1,
              "mostrar_stock_exacto" => intval($this->valor($fila, "mostrar_stock_exacto", 0)) === 1
            ),
            "rate_limit" => array(
              "minuto" => intval($this->valor($fila, "rate_limit_minuto", 0)),
              "dia" => intval($this->valor($fila, "rate_limit_dia", 0))
            )
          );
        }
        $credenciales["activas"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_api_credenciales WHERE estatus='activo'")->fetchColumn());
        $credenciales["vencidas_o_inactivas"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_api_credenciales WHERE estatus<>'activo'")->fetchColumn());
        $allowlist["total_relaciones_activas"] = intval($db->query("SELECT COUNT(*) FROM erp_ecommerce_canal_publicaciones WHERE estatus='activo'")->fetchColumn());
      }

      $configurado = $db && empty($faltantes);
      return $this->respuesta(false, $configurado ? "success" : "info", $configurado ? "Capa canales/API ecommerce disponible" : "Capa canales/API ecommerce en diseno", array(
        "configurado" => $configurado,
        "modo" => $configurado ? "multi_canal_readonly_disponible" : "multi_canal_diseno_readonly",
        "tablas" => $estadoTablas,
        "canales" => $canales,
        "credenciales" => $credenciales,
        "allowlist" => $allowlist,
        "scopes_fase_1" => array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun"),
        "autenticacion" => $this->contratoAutenticacionFutura(),
        "activacion" => array(
          "estado_actual" => $configurado ? "ddl_canales_disponible_validar_seed" : "ddl_canales_pendiente",
          "siguientes_pasos" => $configurado
            ? array("validar_seed_artiani_web", "crear_partner_borrador", "configurar_allowlist", "planear_credencial_sin_exponer_secretos", "mantener_observacion_antes_de_bloqueo")
            : array("aplicar_ddl_canales_con_respaldo_y_autorizacion", "sembrar_artiani_web", "sembrar_partner_borrador", "definir_allowlist", "emitir_credencial_solo_si_hay_backend"),
          "registro_cotizacion_real_bloqueado" => true
        ),
        "bloqueos" => $faltantes,
        "guardrails" => array(
          "read_only" => true,
          "no_genera_secretos" => true,
          "no_expone_api_secret" => true,
          "no_activa_auth_obligatoria" => true,
          "no_modifica_cors" => true,
          "no_cambia_publicaciones" => true,
          "no_registra_cotizaciones" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "configurado" => false,
        "guardrails" => array("read_only" => true, "no_genera_secretos" => true)
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-16
   * Proposito: entregar metadatos SEO/descubrimiento para el proyecto ecommerce externo.
   * Impacto: frontend publico; evita inventar titles, sitemap, robots y JSON-LD fuera del contrato ERP.
   * Contrato: solo lectura; no escribe BD, no expone stock exacto y no usa legacy `ecom_*`.
   */
  public function seoPublico($opciones = array()) {
    try {
      $db = $this->getConexion();
      $configResp = $this->configuracionPublica();
      $config = $this->valor($configResp, array("depurar", "configuracion"), $this->configuracionPublicaDefault());
      $urlSitio = rtrim(trim((string) $this->valor($config, "url_sitio_publico", "")), "/");
      $limite = max(1, min(200, intval($this->valor($opciones, "limite", 100))));

      $meta = array(
        "site_name" => "Catalogo mascotas",
        "title_default" => "Catalogo de productos para mascotas",
        "description_default" => "Consulta productos para tus mascotas, disponibilidad publica y cotiza por WhatsApp.",
        "og_type_catalogo" => "website",
        "og_type_producto" => "product",
        "canonical_base" => $urlSitio,
        "robots_default" => "index,follow"
      );

      $sitemap = array(
        "base_url_configurada" => $urlSitio,
        "rutas_estaticas" => array(
          array("path" => "/", "priority" => "1.0", "changefreq" => "daily"),
          array("path" => "/catalogo", "priority" => "0.9", "changefreq" => "daily"),
          array("path" => "/cotizacion", "priority" => "0.3", "changefreq" => "weekly")
        ),
        "productos" => array(),
        "filtros" => array("mascotas" => array(), "necesidades" => array(), "categorias" => array(), "marcas" => array(), "disponibilidad" => array())
      );

      if ($db && $this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        $catalogo = $this->catalogoPublico(array("limite" => $limite));
        foreach ($this->valor($catalogo, array("depurar", "items"), array()) as $item) {
          $sitemap["productos"][] = array(
            "slug" => $this->valor($item, "slug", ""),
            "path" => "/ecommercePublico/producto/" . $this->valor($item, "slug", ""),
            "title" => $this->valor($item, "nombre", ""),
            "description" => trim((string) $this->valor($item, "descripcion", "")),
            "image" => $this->valor($item, "imagen", ""),
            "priority" => "0.8",
            "changefreq" => "daily"
          );
        }
        $filtros = $this->filtrosPublicos();
        foreach ($this->valor($filtros, array("depurar", "mascotas"), array()) as $fila) {
          $valor = $this->valor($fila, "valor", "");
          if ($valor !== "") {
            $sitemap["filtros"]["mascotas"][] = array("valor" => $valor, "path" => "/ecommercePublico/catalogo?mascota=" . rawurlencode($valor), "title" => "Productos para " . $this->etiquetaTaxonomiaPublica($valor), "priority" => "0.6", "changefreq" => "weekly");
          }
        }
        foreach ($this->valor($filtros, array("depurar", "necesidades"), array()) as $fila) {
          $valor = $this->valor($fila, "valor", "");
          if ($valor !== "") {
            $sitemap["filtros"]["necesidades"][] = array("valor" => $valor, "path" => "/ecommercePublico/catalogo?necesidad=" . rawurlencode($valor), "title" => $this->etiquetaTaxonomiaPublica($valor), "priority" => "0.6", "changefreq" => "weekly");
          }
        }
        foreach ($this->valor($filtros, array("depurar", "categorias"), array()) as $fila) {
          $valor = $this->valor($fila, "id", "");
          if ($valor !== "") {
            $sitemap["filtros"]["categorias"][] = array("valor" => $valor, "path" => "/ecommercePublico/catalogo?categoria=" . rawurlencode($valor), "title" => $this->valor($fila, "etiqueta", "Categoria"), "priority" => "0.6", "changefreq" => "weekly");
          }
        }
        foreach ($this->valor($filtros, array("depurar", "marcas"), array()) as $fila) {
          $valor = $this->valor($fila, "id", "");
          if ($valor !== "") {
            $sitemap["filtros"]["marcas"][] = array("valor" => $valor, "path" => "/ecommercePublico/catalogo?marca=" . rawurlencode($valor), "title" => $this->valor($fila, "etiqueta", "Marca"), "priority" => "0.5", "changefreq" => "weekly");
          }
        }
        foreach ($this->valor($filtros, array("depurar", "disponibilidad"), array()) as $fila) {
          $valor = $this->valor($fila, "valor", "");
          if ($valor !== "") {
            $sitemap["filtros"]["disponibilidad"][] = array("valor" => $valor, "path" => "/ecommercePublico/catalogo?disponibilidad=" . rawurlencode($valor), "title" => $this->valor($fila, "etiqueta", $valor), "priority" => "0.4", "changefreq" => "daily");
          }
        }
      }

      $rutas = $this->rutasSeoPublicas($sitemap);

      return $this->respuesta(false, "success", "SEO ecommerce publico consultado", array(
        "configurado" => $db && $this->tablaExiste($db, "erp_ecommerce_publicaciones"),
        "meta" => $meta,
        "robots" => array(
          "permitir_indexacion" => true,
          "robots_txt_sugerido" => $urlSitio !== ""
            ? "User-agent: *\nAllow: /\nSitemap: " . $urlSitio . "/sitemap.xml"
            : "User-agent: *\nAllow: /",
          "noindex_si_catalogo_vacio" => true
        ),
        "sitemap" => $sitemap,
        "sitemap_xml_sugerido" => $this->sitemapXmlSugerido($rutas, $urlSitio),
        "rutas" => $rutas,
        "json_ld" => array(
          "organization" => array(
            "@context" => "https://schema.org",
            "@type" => "PetStore",
            "name" => $meta["site_name"],
            "url" => $urlSitio
          ),
          "product_contract" => array(
            "@context" => "https://schema.org",
            "@type" => "Product",
            "name" => "item.nombre",
            "image" => "item.imagen",
            "description" => "item.descripcion",
            "sku" => "item.sku",
            "brand" => "item.marca",
            "offers" => array(
              "@type" => "Offer",
              "price" => "item.precio",
              "priceCurrency" => "item.moneda",
              "availability" => "mapear item.disponibilidad"
            )
          )
        ),
        "resumen" => array(
          "rutas_total" => count($rutas),
          "productos" => count($sitemap["productos"]),
          "mascotas" => count($sitemap["filtros"]["mascotas"]),
          "necesidades" => count($sitemap["filtros"]["necesidades"]),
          "categorias" => count($sitemap["filtros"]["categorias"]),
          "marcas" => count($sitemap["filtros"]["marcas"]),
          "disponibilidad" => count($sitemap["filtros"]["disponibilidad"])
        ),
        "fase_2" => $this->fase2SeoPublico($meta, $sitemap, $rutas, $urlSitio),
        "guardrails" => array(
          "frontend_genera_archivos_seo" => true,
          "no_escribe_bd" => true,
          "no_usa_ecom_legacy" => true,
          "no_granel" => true,
          "no_muestra_stock_exacto" => true
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("configurado" => false));
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
        return $this->respuesta(false, "info", "Disponibilidad ecommerce aun no configurada", array(
          "disponibilidad" => "consultar_disponibilidad",
          "mostrar_cantidad_exacta" => false,
          "frontend" => $this->frontendDisponibilidadPublica("consultar_disponibilidad", false),
          "fase_2" => $this->fase2DisponibilidadPublica("consultar_disponibilidad", false, $this->frontendDisponibilidadPublica("consultar_disponibilidad", false))
        ));
      }
      $idSku = intval($this->valor($filtros, "id_sku", 0));
      $slug = trim((string) $this->valor($filtros, "slug", ""));
      if ($idSku <= 0 && $slug === "") {
        return $this->respuesta(true, "warning", "Indica SKU o slug publicado", array(
          "disponibilidad" => "consultar_disponibilidad",
          "mostrar_cantidad_exacta" => false,
          "frontend" => $this->frontendDisponibilidadPublica("consultar_disponibilidad", false),
          "fase_2" => $this->fase2DisponibilidadPublica("consultar_disponibilidad", false, $this->frontendDisponibilidadPublica("consultar_disponibilidad", false))
        ));
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
        return $this->respuesta(false, "info", "SKU no publicado", array(
          "disponibilidad" => "consultar_disponibilidad",
          "mostrar_cantidad_exacta" => false,
          "frontend" => $this->frontendDisponibilidadPublica("consultar_disponibilidad", false),
          "fase_2" => $this->fase2DisponibilidadPublica("consultar_disponibilidad", false, $this->frontendDisponibilidadPublica("consultar_disponibilidad", false))
        ));
      }
      $disponibilidad = $this->disponibilidadPublicaSugerida($fila);
      $permiteCotizacion = intval($fila["permite_cotizacion"]) === 1;
      $frontend = $this->frontendDisponibilidadPublica($disponibilidad, $permiteCotizacion);
      return $this->respuesta(false, "success", "Disponibilidad publica consultada", array(
        "id_sku" => intval($fila["id_sku"]),
        "slug" => $fila["slug"],
        "disponibilidad" => $disponibilidad,
        "mostrar_cantidad_exacta" => false,
        "permite_cotizacion" => $permiteCotizacion,
        "frontend" => $frontend,
        "fase_2" => $this->fase2DisponibilidadPublica($disponibilidad, $permiteCotizacion, $frontend, $fila)
      ));
    } catch (Exception $e) {
      $frontend = $this->frontendDisponibilidadPublica("consultar_disponibilidad", false);
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "disponibilidad" => "consultar_disponibilidad",
        "frontend" => $frontend,
        "fase_2" => $this->fase2DisponibilidadPublica("consultar_disponibilidad", false, $frontend)
      ));
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
          "bloqueos" => array("esquema_publicaciones_pendiente"),
          "frontend" => $this->frontendCotizacionDryRun(array(), array("total_estimado" => 0, "moneda" => "MXN"), array("esquema_publicaciones_pendiente"), array()),
          "fase_2" => $this->fase2CotizacionDryRunPublica(array(), array("total_estimado" => 0, "moneda" => "MXN"), array("esquema_publicaciones_pendiente"), array(), array())
        ));
      }

      $items = isset($datos["items"]) && is_array($datos["items"]) ? $datos["items"] : array();
      if (empty($items)) {
        return $this->respuesta(true, "warning", "Agrega productos para validar la cotizacion", array(
          "dry_run" => true,
          "no_escribe_bd" => true,
          "lineas" => array(),
          "bloqueos" => array("items_vacios"),
          "frontend" => $this->frontendCotizacionDryRun(array(), array("total_estimado" => 0, "moneda" => "MXN"), array("items_vacios"), array()),
          "fase_2" => $this->fase2CotizacionDryRunPublica(array(), array("total_estimado" => 0, "moneda" => "MXN"), array("items_vacios"), array(), array())
        ));
      }

      $items = array_slice($items, 0, 50);
      $lineas = array();
      $bloqueos = array();
      $advertencias = array();
      $disponibilidadResumen = array();
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
        if (!isset($disponibilidadResumen[$disponibilidad])) {
          $disponibilidadResumen[$disponibilidad] = 0;
        }
        $disponibilidadResumen[$disponibilidad]++;
        if ($disponibilidad === "agotado") {
          $advertencias[] = "linea_" . ($index + 1) . "_agotada_sujeta_a_confirmacion";
        } elseif ($disponibilidad === "pocas_piezas") {
          $advertencias[] = "linea_" . ($index + 1) . "_pocas_piezas";
        } elseif ($disponibilidad === "consultar_disponibilidad") {
          $advertencias[] = "linea_" . ($index + 1) . "_consultar_disponibilidad";
        }
        $lineas[] = array(
          "renglon" => count($lineas) + 1,
          "id_publicacion" => intval($publicacion["id_publicacion"]),
          "id_producto_erp" => intval($publicacion["id_producto_erp"]),
          "id_sku" => intval($publicacion["id_sku"]),
          "slug" => $publicacion["slug"],
          "sku" => $publicacion["sku"],
          "nombre" => $publicacion["titulo_publico"] ?: $publicacion["nombre_sku"],
          "presentacion" => $this->presentacionPublicaSalida($publicacion),
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
      $bloqueosFinal = array_values(array_unique($bloqueos));
      $advertenciasFinal = array_values(array_unique($advertencias));
      $totales = array(
        "subtotal_estimado" => $total,
        "total_estimado" => $total,
        "moneda" => "MXN",
        "texto" => "Total estimado sujeto a confirmacion"
      );
      return $this->respuesta(false, empty($bloqueosFinal) ? "success" : "warning", empty($bloqueosFinal) ? "Cotizacion dry-run validada" : "Cotizacion dry-run con observaciones", array(
        "configurado" => true,
        "dry_run" => true,
        "no_escribe_bd" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "lineas" => $lineas,
        "totales" => $totales,
        "resumen" => array(
          "items_recibidos" => count($items),
          "lineas_validas" => count($lineas),
          "cantidad_total" => $this->sumarCantidadLineasCotizacion($lineas),
          "disponibilidad" => $this->resumenDisponibilidadCotizacion($disponibilidadResumen),
          "requiere_confirmacion_operativa" => !empty($advertenciasFinal) || !empty($bloqueosFinal)
        ),
        "advertencias" => $advertenciasFinal,
        "bloqueos" => $bloqueosFinal,
        "whatsapp_preview" => $this->mensajeWhatsAppPreview($lineas, $total),
        "frontend" => $this->frontendCotizacionDryRun($lineas, $totales, $bloqueosFinal, $advertenciasFinal),
        "fase_2" => $this->fase2CotizacionDryRunPublica($lineas, $totales, $bloqueosFinal, $advertenciasFinal, $disponibilidadResumen)
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "dry_run" => true,
        "no_escribe_bd" => true,
        "fase_2" => $this->fase2CotizacionDryRunPublica(array(), array("total_estimado" => 0, "moneda" => "MXN"), array("error_dryrun"), array(), array())
      ));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar payload completo de cotizacion antes de WhatsApp o registro futuro.
   * Impacto: Frontend ecommerce; permite UX de confirmacion sin escribir BD ni generar pedido.
   * Contrato: read-only; usa dry-run interno, no guarda cotizacion, no aparta inventario.
   */
  public function cotizacionPreflight($datos = array()) {
    $dryRun = $this->cotizacionDryRun($datos);
    $dryDepurar = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
    $lineas = $this->valor($dryDepurar, "lineas", array());
    $totales = $this->valor($dryDepurar, "totales", array("total_estimado" => 0, "moneda" => "MXN"));
    $bloqueos = $this->valor($dryDepurar, "bloqueos", array());
    if (!is_array($bloqueos)) {
      $bloqueos = array();
    }

    $contactoRaw = $this->valor($datos, "contacto", array());
    $contacto = $this->normalizarContactoCotizacion($contactoRaw);
    $validacionContacto = $this->validacionContactoCotizacion($contacto);
    $aceptaWhatsapp = $this->booleanoCotizacion($this->valor($contactoRaw, "acepta_whatsapp", $this->valor($datos, "acepta_contacto_whatsapp", false)));
    $politicasAceptadas = $this->politicasAceptadasCotizacion($datos, $contactoRaw);
    $consentimiento = $this->consentimientoCotizacion($aceptaWhatsapp, $politicasAceptadas);
    $utm = $this->normalizarUtmCotizacion($this->valor($datos, "utm", array()));

    $advertencias = array();
    if ($contacto["nombre"] === "") {
      $advertencias[] = "contacto_nombre_recomendado";
    }
    if ($contacto["telefono"] === "") {
      $advertencias[] = "contacto_telefono_recomendado";
    }
    if (!$aceptaWhatsapp) {
      $advertencias[] = "aceptacion_whatsapp_recomendada";
    }
    if (empty($politicasAceptadas)) {
      $advertencias[] = "politicas_aceptadas_no_informadas";
    }
    if (empty($lineas)) {
      $bloqueos[] = "sin_lineas_validas";
    }
    foreach ($validacionContacto["advertencias"] as $advertenciaContacto) {
      $advertencias[] = $advertenciaContacto;
    }

    $configuracion = $this->configuracionPublica();
    $config = $this->valor($configuracion, array("depurar", "configuracion"), array());
    $numeroWhatsapp = preg_replace('/\D+/', '', (string) $this->valor($config, "whatsapp_numero_principal", ""));
    $mensaje = $this->valor($dryDepurar, "whatsapp_preview", "");
    $folioPreliminar = $this->folioPreliminarCotizacion($datos, $lineas, $contacto);
    if ($folioPreliminar !== "" && $mensaje !== "") {
      $mensaje .= "\n\nReferencia preliminar: " . $folioPreliminar;
    }

    $listoWhatsapp = empty($dryRun["error"]) && empty($bloqueos) && !empty($lineas) && $numeroWhatsapp !== "";
    $listoRegistroFuturo = $listoWhatsapp
      && !empty($validacionContacto["valido_para_registro_futuro"])
      && !empty($consentimiento["listo_para_registro_futuro"]);
    $whatsappUrl = $numeroWhatsapp !== "" && $mensaje !== "" ? "https://wa.me/" . $numeroWhatsapp . "?text=" . rawurlencode($mensaje) : "";

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Preflight de cotizacion validado" : "Preflight de cotizacion con observaciones", array(
      "preflight" => true,
      "no_escribe_bd" => true,
      "no_descuenta_inventario" => true,
      "no_crea_pedido" => true,
      "folio_preliminar" => $folioPreliminar,
      "folio_no_persistido" => true,
      "listo_para_whatsapp" => $listoWhatsapp,
      "listo_para_registro_futuro" => $listoRegistroFuturo,
      "contacto" => $contacto,
      "validacion_contacto" => $validacionContacto,
      "acepta_contacto_whatsapp" => $aceptaWhatsapp,
      "consentimiento" => $consentimiento,
      "politicas_aceptadas" => $politicasAceptadas,
      "utm" => $utm,
      "dry_run" => $dryDepurar,
      "whatsapp" => array(
        "numero_configurado" => $numeroWhatsapp !== "",
        "numero" => $numeroWhatsapp,
        "mensaje" => $mensaje,
        "url" => $whatsappUrl
      ),
      "cta" => array(
        "tipo" => $listoWhatsapp ? "whatsapp" : "corregir_datos",
        "label" => $listoWhatsapp ? "Enviar por WhatsApp" : "Revisar cotizacion",
        "url" => $whatsappUrl,
        "disabled" => !$listoWhatsapp,
        "motivos_disabled" => $listoWhatsapp ? array() : array_values(array_unique(array_merge($bloqueos, $numeroWhatsapp === "" ? array("whatsapp_no_configurado") : array())))
      ),
      "frontend" => array(
        "pasos_sugeridos" => array("carrito", "datos_contacto", "confirmacion", "whatsapp"),
        "contacto_es_recomendado_para_whatsapp" => true,
        "contacto_requerido_para_registro_futuro" => true,
        "registro_real_bloqueado_fase_1" => true,
        "mostrar_total_estimado" => true,
        "mostrar_leyenda_confirmacion" => true
      ),
      "fase_2" => $this->fase2CotizacionPreflightPublica($listoWhatsapp, $listoRegistroFuturo, $lineas, $totales, $bloqueos, $advertencias, $validacionContacto, $consentimiento, $whatsappUrl),
      "advertencias" => array_values(array_unique($advertencias)),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "persistencia_futura" => array(
        "endpoint_reservado" => "/ecommercePublico/cotizacion_registrar",
        "tabla_encabezado" => "erp_ecommerce_cotizaciones",
        "tabla_detalle" => "erp_ecommerce_cotizaciones_detalle",
        "tabla_eventos" => "erp_ecommerce_cotizaciones_eventos",
        "estatus_inicial_sugerido" => "recibida_whatsapp",
        "no_inventario" => true,
        "no_pedido_confirmado" => true
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: planear la persistencia futura de una cotizacion ecommerce sin ejecutarla.
   * Impacto: Ecommerce publico; define folio real, snapshots y eventos para bandeja interna posterior.
   * Contrato: read-only; no inserta encabezado, detalle ni eventos; no toca inventario ni pedidos.
   */
  public function cotizacionRegistroPersistenciaPlan($datos = array()) {
    try {
      $db = $this->getConexion();
      $preflight = $this->cotizacionPreflight($datos);
      $dep = isset($preflight["depurar"]) && is_array($preflight["depurar"]) ? $preflight["depurar"] : array();
      $dry = $this->valor($dep, "dry_run", array());
      $lineas = $this->valor($dry, "lineas", array());
      $totales = $this->valor($dry, "totales", array());
      $contacto = $this->valor($dep, "contacto", array());
      $utm = $this->valor($dep, "utm", array());
      $bloqueos = $this->valor($dep, "bloqueos", array());
      if (!is_array($bloqueos)) { $bloqueos = array(); }

      $tablas = array(
        "erp_ecommerce_cotizaciones",
        "erp_ecommerce_cotizaciones_detalle",
        "erp_ecommerce_cotizaciones_eventos"
      );
      $tablasEstado = array();
      foreach ($tablas as $tabla) {
        $tablasEstado[$tabla] = $db ? $this->tablaExiste($db, $tabla) : false;
        if (!$tablasEstado[$tabla]) {
          $bloqueos[] = "tabla_faltante_" . $tabla;
        }
      }
      if (empty($dep["listo_para_registro_futuro"])) {
        $bloqueos[] = "preflight_no_listo_para_registro_futuro";
      }

      $folio = $this->folioCotizacionPlaneado($db);
      $subtotal = floatval($this->valor($totales, "subtotal_estimado", 0));
      $total = floatval($this->valor($totales, "total_estimado", $subtotal));
      $eventoDetalle = array(
        "preflight_folio" => $this->valor($dep, "folio_preliminar", ""),
        "whatsapp_url_generada" => trim((string) $this->valor($dep, array("whatsapp", "url"), "")) !== "",
        "politicas_aceptadas" => $this->valor($dep, "politicas_aceptadas", array()),
        "advertencias" => $this->valor($dep, "advertencias", array())
      );

      $sqlEncabezado = "INSERT INTO `erp_ecommerce_cotizaciones` " .
        "(`folio`, `origen`, `estatus`, `nombre_contacto`, `telefono_contacto`, `correo_contacto`, `canal_contacto_preferido`, `mensaje_cliente`, `moneda`, `subtotal_estimado`, `total_estimado`, `utm_json`, `ip_hash`, `user_agent_hash`, `fecha_expiracion`, `fecha_registro`, `fecha_actualizacion`) VALUES (" .
        $this->sqlQuote($folio) . ", 'web_publica', 'recibida_whatsapp', " .
        $this->sqlQuote($this->valor($contacto, "nombre", "")) . ", " .
        $this->sqlQuote($this->valor($contacto, "telefono", "")) . ", " .
        $this->sqlQuote($this->valor($contacto, "correo", "")) . ", " .
        $this->sqlQuote($this->valor($contacto, "canal_preferido", "whatsapp")) . ", " .
        $this->sqlQuote($this->valor($contacto, "mensaje", "")) . ", 'MXN', " .
        number_format($subtotal, 6, ".", "") . ", " .
        number_format($total, 6, ".", "") . ", " .
        $this->sqlQuote(json_encode($utm, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ", " .
        "'HASH_IP_EN_BACKEND', 'HASH_USER_AGENT_EN_BACKEND', DATE_ADD(NOW(), INTERVAL 7 DAY), NOW(), NOW());";

      $sqlDetalle = array();
      foreach ($lineas as $linea) {
        $sqlDetalle[] = "INSERT INTO `erp_ecommerce_cotizaciones_detalle` " .
          "(`id_cotizacion`, `renglon`, `id_publicacion`, `id_producto_erp`, `id_sku`, `sku_snapshot`, `nombre_snapshot`, `presentacion_snapshot`, `precio_snapshot`, `moneda_snapshot`, `cantidad`, `disponibilidad_snapshot`, `subtotal`, `estatus`, `fecha_registro`) VALUES " .
          "(@id_cotizacion, " . intval($this->valor($linea, "renglon", 1)) . ", " .
          intval($this->valor($linea, "id_publicacion", 0)) . ", " .
          intval($this->valor($linea, "id_producto_erp", 0)) . ", " .
          intval($this->valor($linea, "id_sku", 0)) . ", " .
          $this->sqlQuote($this->valor($linea, "sku", "")) . ", " .
          $this->sqlQuote($this->valor($linea, "nombre", "")) . ", " .
          $this->sqlQuote($this->valor($linea, "presentacion", "")) . ", " .
          number_format(floatval($this->valor($linea, "precio_unitario", 0)), 6, ".", "") . ", " .
          $this->sqlQuote($this->valor($linea, "moneda", "MXN")) . ", " .
          number_format(floatval($this->valor($linea, "cantidad", 0)), 6, ".", "") . ", " .
          $this->sqlQuote($this->valor($linea, "disponibilidad", "consultar_disponibilidad")) . ", " .
          number_format(floatval($this->valor($linea, "subtotal", 0)), 6, ".", "") . ", 'activa', NOW());";
      }

      $sqlEvento = "INSERT INTO `erp_ecommerce_cotizaciones_eventos` " .
        "(`id_cotizacion`, `tipo_evento`, `canal`, `resultado`, `detalle_json`, `creado_por`, `fecha_registro`) VALUES " .
        "(@id_cotizacion, 'recibida_whatsapp', 'web_publica', 'pendiente_seguimiento', " .
        $this->sqlQuote(json_encode($eventoDetalle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ", NULL, NOW());";

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Plan de registro de cotizacion listo" : "Plan de registro de cotizacion con bloqueos", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_registra_cotizacion" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "folio_planeado" => $folio,
        "folio_planeado_no_reservado" => true,
        "preflight" => $dep,
        "tablas" => $tablasEstado,
        "snapshot" => array(
          "encabezado" => array(
            "folio" => $folio,
            "origen" => "web_publica",
            "estatus" => "recibida_whatsapp",
            "contacto" => $contacto,
            "subtotal_estimado" => $subtotal,
            "total_estimado" => $total,
            "moneda" => "MXN"
          ),
          "detalle" => $lineas,
          "evento_inicial" => array(
            "tipo_evento" => "recibida_whatsapp",
            "resultado" => "pendiente_seguimiento"
          )
        ),
        "sql_plan" => array(
          "transaccion" => true,
          "encabezado" => $sqlEncabezado,
          "capturar_id" => "SET @id_cotizacion = LAST_INSERT_ID();",
          "detalle" => $sqlDetalle,
          "evento" => $sqlEvento
        ),
        "siguiente_fase" => array(
          "mantener_cotizacion_registrar_bloqueado_hasta_autorizacion" => true,
          "crear_bandeja_interna_cotizaciones" => true,
          "definir_conversion_manual_a_pedido_o_venta" => true,
          "vincular_crm_por_telefono_sin_crear_cliente_automatico" => true
        ),
        "bloqueos" => array_values(array_unique($bloqueos))
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true, "no_escribe_bd" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: listar cotizaciones ecommerce para bandeja interna de seguimiento.
   * Impacto: prepara operacion post-WhatsApp sin crear pedidos, ventas ni movimientos.
   * Contrato: read-only; si no hay tablas responde lista vacia y bloqueos explicitos.
   */
  public function cotizacionesBandejaInterna($filtros = array()) {
    try {
      $db = $this->getConexion();
      $tablas = $this->tablasCotizacionesDisponibles($db);
      if (!$db || !$tablas["erp_ecommerce_cotizaciones"]) {
        return $this->respuesta(false, "info", "Bandeja de cotizaciones ecommerce aun sin tabla", array(
          "configurado" => false,
          "items" => array(),
          "resumen" => $this->resumenBandejaCotizaciones(array()),
          "bloqueos" => array("tabla_erp_ecommerce_cotizaciones_pendiente"),
          "guardrails" => $this->guardrailsBandejaCotizaciones()
        ));
      }

      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $limite = max(1, min(100, intval($this->valor($filtros, "limite", 25))));
      $offset = ($pagina - 1) * $limite;
      $where = array("1=1");
      $params = array();

      $estatus = $this->limpiarFiltroPublico($this->valor($filtros, "estatus", ""));
      if ($estatus !== "") {
        $where[] = "c.estatus=:estatus";
        $params[":estatus"] = $estatus;
      }
      $q = trim((string) $this->valor($filtros, "q", ""));
      if ($q !== "") {
        $where[] = "(c.folio LIKE :q OR c.nombre_contacto LIKE :q OR c.telefono_contacto LIKE :q OR c.correo_contacto LIKE :q)";
        $params[":q"] = "%" . $q . "%";
      }
      $desde = trim((string) $this->valor($filtros, "desde", ""));
      if ($desde !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
        $where[] = "DATE(c.fecha_registro) >= :desde";
        $params[":desde"] = $desde;
      }
      $hasta = trim((string) $this->valor($filtros, "hasta", ""));
      if ($hasta !== "" && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
        $where[] = "DATE(c.fecha_registro) <= :hasta";
        $params[":hasta"] = $hasta;
      }

      $sqlWhere = implode(" AND ", $where);
      $stmtTotal = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_cotizaciones c WHERE " . $sqlWhere);
      $stmtTotal->execute($params);
      $total = intval($stmtTotal->fetchColumn());

      $sql = "SELECT c.id_cotizacion, c.folio, c.origen, c.estatus, c.id_cliente_crm,
          c.nombre_contacto, c.telefono_contacto, c.correo_contacto, c.canal_contacto_preferido,
          c.moneda, c.subtotal_estimado, c.total_estimado, c.fecha_expiracion, c.fecha_registro, c.fecha_actualizacion,
          COUNT(d.id_cotizacion_detalle) partidas,
          MAX(e.fecha_registro) fecha_ultimo_evento
        FROM erp_ecommerce_cotizaciones c
        LEFT JOIN erp_ecommerce_cotizaciones_detalle d ON d.id_cotizacion=c.id_cotizacion AND d.estatus='activa'
        LEFT JOIN erp_ecommerce_cotizaciones_eventos e ON e.id_cotizacion=c.id_cotizacion
        WHERE " . $sqlWhere . "
        GROUP BY c.id_cotizacion, c.folio, c.origen, c.estatus, c.id_cliente_crm, c.nombre_contacto,
          c.telefono_contacto, c.correo_contacto, c.canal_contacto_preferido, c.moneda, c.subtotal_estimado,
          c.total_estimado, c.fecha_expiracion, c.fecha_registro, c.fecha_actualizacion
        ORDER BY c.fecha_registro DESC, c.id_cotizacion DESC
        LIMIT " . intval($limite) . " OFFSET " . intval($offset);
      $stmt = $db->prepare($sql);
      $stmt->execute($params);
      $items = array();
      foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $items[] = $this->formatearCotizacionBandeja($fila);
      }

      return $this->respuesta(false, "success", "Bandeja de cotizaciones ecommerce consultada", array(
        "configurado" => true,
        "items" => $items,
        "resumen" => $this->resumenBandejaCotizaciones($items),
        "paginacion" => array("pagina" => $pagina, "limite" => $limite, "total" => $total),
        "filtros" => array("estatus" => $estatus, "q" => $q, "desde" => $desde, "hasta" => $hasta),
        "tablas" => $tablas,
        "guardrails" => $this->guardrailsBandejaCotizaciones()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("items" => array(), "read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: consultar detalle read-only de una cotizacion ecommerce.
   * Impacto: muestra snapshots y eventos para seguimiento sin convertir automaticamente.
   * Contrato: read-only; no escribe BD, no cambia estatus y no crea pedidos.
   */
  public function cotizacionDetalleInterna($filtros = array()) {
    try {
      $db = $this->getConexion();
      $tablas = $this->tablasCotizacionesDisponibles($db);
      if (!$db || !$tablas["erp_ecommerce_cotizaciones"]) {
        return $this->respuesta(false, "info", "Detalle de cotizacion ecommerce aun sin tabla", array(
          "configurado" => false,
          "item" => null,
          "detalle" => array(),
          "eventos" => array(),
          "bloqueos" => array("tabla_erp_ecommerce_cotizaciones_pendiente"),
          "guardrails" => $this->guardrailsBandejaCotizaciones()
        ));
      }
      $id = intval($this->valor($filtros, "id_cotizacion", 0));
      $folio = trim((string) $this->valor($filtros, "folio", ""));
      if ($id <= 0 && $folio === "") {
        return $this->respuesta(true, "warning", "Indica folio o id_cotizacion", array(
          "configurado" => true,
          "item" => null,
          "detalle" => array(),
          "eventos" => array(),
          "guardrails" => $this->guardrailsBandejaCotizaciones()
        ));
      }
      $where = $id > 0 ? "c.id_cotizacion=:valor" : "c.folio=:valor";
      $stmt = $db->prepare("SELECT c.* FROM erp_ecommerce_cotizaciones c WHERE " . $where . " LIMIT 1");
      $stmt->execute(array(":valor" => $id > 0 ? $id : $folio));
      $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$cotizacion) {
        return $this->respuesta(false, "info", "Cotizacion ecommerce no encontrada", array(
          "configurado" => true,
          "item" => null,
          "detalle" => array(),
          "eventos" => array(),
          "guardrails" => $this->guardrailsBandejaCotizaciones()
        ));
      }
      $detalle = array();
      if ($tablas["erp_ecommerce_cotizaciones_detalle"]) {
        $stmtDetalle = $db->prepare("SELECT * FROM erp_ecommerce_cotizaciones_detalle WHERE id_cotizacion=:id ORDER BY renglon ASC, id_cotizacion_detalle ASC");
        $stmtDetalle->execute(array(":id" => intval($cotizacion["id_cotizacion"])));
        $detalle = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
      }
      $eventos = array();
      if ($tablas["erp_ecommerce_cotizaciones_eventos"]) {
        $stmtEventos = $db->prepare("SELECT * FROM erp_ecommerce_cotizaciones_eventos WHERE id_cotizacion=:id ORDER BY fecha_registro ASC, id_cotizacion_evento ASC");
        $stmtEventos->execute(array(":id" => intval($cotizacion["id_cotizacion"])));
        $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
      }

      return $this->respuesta(false, "success", "Detalle de cotizacion ecommerce consultado", array(
        "configurado" => true,
        "item" => $this->formatearCotizacionBandeja($cotizacion),
        "detalle" => $detalle,
        "eventos" => $eventos,
        "conversion_manual_futura" => array(
          "puede_preparar_pedido" => true,
          "puede_preparar_venta_pos" => true,
          "requiere_revision_humana" => true,
          "no_automatizar_checkout" => true
        ),
        "guardrails" => $this->guardrailsBandejaCotizaciones()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("item" => null, "read_only" => true));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: planear acciones futuras de seguimiento/conversion para cotizaciones ecommerce.
   * Impacto: prepara reglas operativas sin cambiar estatus, crear pedidos/ventas ni tocar inventario.
   * Contrato: read-only; genera plan y SQL ilustrativo, no lo ejecuta.
   */
  public function cotizacionAccionPlanInterna($datos = array()) {
    try {
      $db = $this->getConexion();
      $accion = $this->limpiarFiltroPublico($this->valor($datos, "accion", ""));
      $folio = trim((string) $this->valor($datos, "folio", ""));
      $idCotizacion = intval($this->valor($datos, "id_cotizacion", 0));
      $motivo = trim(substr((string) $this->valor($datos, "motivo", ""), 0, 500));
      $accionesPermitidas = array("marcar_seguimiento", "descartar", "preparar_pedido_manual", "preparar_venta_pos_manual");
      $bloqueos = array();

      if (!in_array($accion, $accionesPermitidas, true)) {
        $bloqueos[] = "accion_no_permitida";
      }
      if ($idCotizacion <= 0 && $folio === "") {
        $bloqueos[] = "folio_o_id_cotizacion_requerido";
      }
      if (in_array($accion, array("descartar", "preparar_pedido_manual", "preparar_venta_pos_manual"), true) && $motivo === "") {
        $bloqueos[] = "motivo_requerido";
      }

      $detalle = null;
      if ($idCotizacion > 0 || $folio !== "") {
        $detalle = $this->cotizacionDetalleInterna($idCotizacion > 0 ? array("id_cotizacion" => $idCotizacion) : array("folio" => $folio));
        if (!is_array($this->valor($detalle, array("depurar", "item"), null))) {
          $bloqueos[] = "cotizacion_no_encontrada_o_no_disponible";
        }
      }

      $item = is_array($detalle) ? $this->valor($detalle, array("depurar", "item"), array()) : array();
      $idPlaneado = intval($this->valor($item, "id_cotizacion", $idCotizacion));
      $folioPlaneado = trim((string) $this->valor($item, "folio", $folio));
      $estatusActual = trim((string) $this->valor($item, "estatus", ""));
      $estatusDestino = $this->estatusDestinoAccionCotizacion($accion);
      $evento = $this->eventoAccionCotizacion($accion);

      $sql = array();
      if (empty($bloqueos)) {
        $sql[] = "UPDATE `erp_ecommerce_cotizaciones` SET `estatus`=" . $this->sqlQuote($estatusDestino) . ", `fecha_actualizacion`=NOW() WHERE `id_cotizacion`=" . intval($idPlaneado) . " LIMIT 1;";
        $sql[] = "INSERT INTO `erp_ecommerce_cotizaciones_eventos` (`id_cotizacion`, `tipo_evento`, `canal`, `resultado`, `detalle_json`, `creado_por`, `fecha_registro`) VALUES (" .
          intval($idPlaneado) . ", " . $this->sqlQuote($evento) . ", 'erp_interno', 'plan_readonly', " .
          $this->sqlQuote(json_encode(array("motivo" => $motivo, "estatus_anterior" => $estatusActual, "estatus_destino" => $estatusDestino), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ", NULL, NOW());";
      }

      return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Plan de accion de cotizacion listo" : "Plan de accion con bloqueos", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_cambia_estatus" => true,
        "no_crea_pedido" => true,
        "no_crea_venta" => true,
        "no_descuenta_inventario" => true,
        "accion" => $accion,
        "acciones_permitidas" => $accionesPermitidas,
        "cotizacion" => array(
          "id_cotizacion" => $idPlaneado > 0 ? $idPlaneado : null,
          "folio" => $folioPlaneado,
          "estatus_actual" => $estatusActual,
          "estatus_destino_planeado" => $estatusDestino
        ),
        "evento_planeado" => array(
          "tipo_evento" => $evento,
          "canal" => "erp_interno",
          "resultado" => "plan_readonly",
          "motivo" => $motivo
        ),
        "conversion_manual" => array(
          "requiere_revision_humana" => in_array($accion, array("preparar_pedido_manual", "preparar_venta_pos_manual"), true),
          "pedido_real_no_creado" => true,
          "venta_real_no_creada" => true,
          "inventario_no_afectado" => true,
          "siguiente_modulo" => $accion === "preparar_pedido_manual" ? "Pedidos/Ventas" : ($accion === "preparar_venta_pos_manual" ? "POS/Ventas" : null)
        ),
        "sql_plan" => $sql,
        "bloqueos" => array_values(array_unique($bloqueos)),
        "guardrails" => $this->guardrailsBandejaCotizaciones()
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true, "no_escribe_bd" => true));
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
      "preflight_disponible" => true,
      "endpoint_preflight" => "/ecommercePublico/cotizacion_preflight",
      "endpoint_plan_interno" => "/ecommercePublico/cotizacion_registro_plan_erp",
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: responder error funcional estandar para endpoints publicos POST.
   * Impacto: Ecommerce publico; estabiliza respuestas ante integraciones frontend/partner.
   * Contrato: no escribe BD ni consulta informacion sensible.
   */
  public function metodoPostRequerido($flujo = "post_publico") {
    return $this->respuesta(true, "warning", "Usa POST para este endpoint ecommerce", array(
      "flujo" => $this->limpiarFiltroPublico($flujo),
      "metodo_requerido" => "POST",
      "no_escribe_bd" => true
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar solicitud publica de factura sin registrar datos fiscales.
   * Impacto: Ecommerce publico/facturacion futura; prepara bandeja interna para contador sin emitir CFDI.
   * Contrato: preflight read-only; no escribe BD, no emite factura, no crea cliente.
   */
  public function facturacionSolicitudPreflight($datos = array()) {
    $folioCompra = strtoupper(substr(preg_replace('/[^A-Z0-9\-_]/', '', strtoupper(trim((string) $this->valor($datos, "folio_compra", "")))), 0, 60));
    $fechaCompra = trim((string) $this->valor($datos, "fecha_compra", ""));
    $importe = $this->valor($datos, "importe", null);
    $fiscales = is_array($this->valor($datos, "datos_fiscales", array())) ? $this->valor($datos, "datos_fiscales", array()) : array();
    $contacto = is_array($this->valor($datos, "contacto", array())) ? $this->valor($datos, "contacto", array()) : array();
    $rfc = strtoupper(substr(preg_replace('/[^A-Z0-9&Ñ]/u', '', strtoupper(trim((string) $this->valor($fiscales, "rfc", "")))), 0, 13));
    $razonSocial = substr(trim((string) $this->valor($fiscales, "razon_social", "")), 0, 180);
    $regimenFiscal = substr(trim((string) $this->valor($fiscales, "regimen_fiscal", "")), 0, 80);
    $usoCfdi = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string) $this->valor($fiscales, "uso_cfdi", "")))), 0, 10));
    $codigoPostal = substr(preg_replace('/[^0-9]/', '', trim((string) $this->valor($fiscales, "codigo_postal", ""))), 0, 5);
    $correo = strtolower(substr(trim((string) $this->valor($contacto, "correo", "")), 0, 160));
    $telefono = substr(preg_replace('/[^0-9]/', '', trim((string) $this->valor($contacto, "telefono", ""))), 0, 20);
    $notas = substr(trim((string) $this->valor($datos, "notas", "")), 0, 500);
    $aceptaAviso = $this->valor($datos, "acepta_aviso_privacidad", false) === true || (string) $this->valor($datos, "acepta_aviso_privacidad", "") === "1";
    $bloqueos = array();
    $advertencias = array("persistencia_desactivada_en_fase_1");

    if ($folioCompra === "") { $bloqueos[] = "folio_compra_requerido"; }
    if ($rfc === "") { $bloqueos[] = "rfc_requerido_para_registro_futuro"; }
    if ($razonSocial === "") { $bloqueos[] = "razon_social_requerida_para_registro_futuro"; }
    if ($correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) { $bloqueos[] = "correo_valido_requerido_para_registro_futuro"; }
    if (!$aceptaAviso) { $bloqueos[] = "acepta_aviso_privacidad_requerido_para_registro_futuro"; }
    if ($fechaCompra !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaCompra)) { $advertencias[] = "fecha_compra_formato_recomendado_yyyy_mm_dd"; }

    $folioPreliminar = "FAC-PRE-" . date("Ymd") . "-" . strtoupper(substr(sha1($folioCompra . "|" . $rfc . "|" . $correo), 0, 8));
    $sql = empty($bloqueos) ? array(
      "INSERT INTO `erp_ecommerce_facturacion_solicitudes` (`folio_solicitud`, `folio_compra`, `fecha_compra`, `importe`, `rfc`, `razon_social`, `regimen_fiscal`, `uso_cfdi`, `codigo_postal`, `correo`, `telefono`, `notas`, `estatus`, `fecha_registro`) VALUES (...)",
      "INSERT INTO `erp_ecommerce_eventos_navegacion` (`tipo_evento`, `resultado`, `metadata_json`, `fecha_registro`) VALUES ('facturacion_submit', 'plan_readonly', ..., NOW())"
    ) : array();

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Solicitud de facturacion validada sin guardar" : "Solicitud de facturacion con datos pendientes", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_emite_factura" => true,
      "no_crea_cliente" => true,
      "folio_solicitud_preliminar" => $folioPreliminar,
      "folio_no_persistido" => true,
      "listo_para_registro_futuro" => empty($bloqueos),
      "solicitud_normalizada" => array(
        "folio_compra" => $folioCompra,
        "fecha_compra" => $fechaCompra,
        "importe" => $importe === null || $importe === "" ? null : round(floatval($importe), 2),
        "datos_fiscales" => array(
          "rfc" => $rfc,
          "razon_social" => $razonSocial,
          "regimen_fiscal" => $regimenFiscal,
          "uso_cfdi" => $usoCfdi,
          "codigo_postal" => $codigoPostal
        ),
        "contacto" => array("correo" => $correo, "telefono" => $telefono),
        "notas" => $notas
      ),
      "estatus_futuro_inicial" => "nueva",
      "bloqueos" => array_values(array_unique($bloqueos)),
      "advertencias" => array_values(array_unique($advertencias)),
      "sql_plan" => $sql,
      "guardrails" => $this->guardrailsExperienciaClientePreflight()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar evento anonimo de navegacion sin persistirlo.
   * Impacto: Ecommerce publico/analytics; prepara inteligencia cliente sin datos personales.
   * Contrato: preflight read-only; no escribe BD y bloquea metadata sensible detectable.
   */
  public function eventoNavegacionPreflight($datos = array()) {
    $permitidos = array("page_view", "select_mascota", "select_necesidad", "search", "view_product", "add_to_quote", "open_whatsapp", "facturacion_view", "facturacion_submit");
    $tipo = $this->limpiarFiltroPublico($this->valor($datos, "tipo_evento", ""));
    $sessionId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) $this->valor($datos, "session_id", ""))), 0, 80);
    $metadata = is_array($this->valor($datos, "metadata", array())) ? $this->valor($datos, "metadata", array()) : array();
    $bloqueos = array();
    if (!in_array($tipo, $permitidos, true)) { $bloqueos[] = "tipo_evento_no_permitido"; }
    if ($sessionId === "") { $bloqueos[] = "session_id_anonimo_requerido_para_registro_futuro"; }
    $datosPersonales = $this->detectarDatosPersonalesTracking($metadata);
    if (!empty($datosPersonales)) { $bloqueos[] = "metadata_no_debe_incluir_datos_personales"; }

    $evento = array(
      "session_id" => $sessionId,
      "tipo_evento" => $tipo,
      "canal" => $this->limpiarFiltroPublico($this->valor($datos, "canal", "web")),
      "ruta" => substr(trim((string) $this->valor($datos, "ruta", "")), 0, 200),
      "mascota" => $this->limpiarFiltroPublico($this->valor($datos, "mascota", "")),
      "necesidad" => $this->limpiarFiltroPublico($this->valor($datos, "necesidad", "")),
      "id_publicacion" => intval($this->valor($datos, "id_publicacion", 0)),
      "id_sku" => intval($this->valor($datos, "id_sku", 0)),
      "metadata" => $this->limpiarMetadataTracking($metadata)
    );

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Evento de navegacion validado sin guardar" : "Evento de navegacion con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_registra_tracking" => true,
      "evento_normalizado" => $evento,
      "datos_personales_detectados" => $datosPersonales,
      "listo_para_registro_futuro" => empty($bloqueos),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? array("INSERT INTO `erp_ecommerce_eventos_navegacion` (`session_id_hash`, `tipo_evento`, `canal`, `ruta`, `mascota`, `necesidad`, `id_publicacion`, `id_sku`, `metadata_json`, `fecha_registro`) VALUES (...)") : array(),
      "guardrails" => $this->guardrailsExperienciaClientePreflight()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar busqueda ecommerce anonima sin persistirla.
   * Impacto: Ecommerce publico; prepara analitica de demanda y faltantes por mascota/necesidad.
   * Contrato: preflight read-only; no escribe BD y no guarda datos personales.
   */
  public function busquedaRegistrarPreflight($datos = array()) {
    $query = substr(trim((string) $this->valor($datos, "query", "")), 0, 120);
    $sessionId = substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', trim((string) $this->valor($datos, "session_id", ""))), 0, 80);
    $filtros = is_array($this->valor($datos, "filtros", array())) ? $this->valor($datos, "filtros", array()) : array();
    $bloqueos = array();
    if ($query === "") { $bloqueos[] = "query_requerido"; }
    if ($sessionId === "") { $bloqueos[] = "session_id_anonimo_requerido_para_registro_futuro"; }
    $datosPersonales = $this->detectarDatosPersonalesTracking(array_merge(array("query" => $query), $filtros));
    if (!empty($datosPersonales)) { $bloqueos[] = "busqueda_no_debe_incluir_datos_personales"; }
    $resultadosTotal = max(0, intval($this->valor($datos, "resultados_total", 0)));
    $busqueda = array(
      "session_id" => $sessionId,
      "query" => $query,
      "query_normalizada" => strtolower($query),
      "canal" => $this->limpiarFiltroPublico($this->valor($datos, "canal", "web")),
      "mascota" => $this->limpiarFiltroPublico($this->valor($datos, "mascota", "")),
      "necesidad" => $this->limpiarFiltroPublico($this->valor($datos, "necesidad", "")),
      "resultados_total" => $resultadosTotal,
      "sin_resultados" => $resultadosTotal <= 0,
      "filtros" => $this->limpiarMetadataTracking($filtros)
    );

    return $this->respuesta(false, empty($bloqueos) ? "success" : "warning", empty($bloqueos) ? "Busqueda ecommerce validada sin guardar" : "Busqueda ecommerce con bloqueos", array(
      "preflight" => true,
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_registra_busqueda" => true,
      "busqueda_normalizada" => $busqueda,
      "datos_personales_detectados" => $datosPersonales,
      "listo_para_registro_futuro" => empty($bloqueos),
      "bloqueos" => array_values(array_unique($bloqueos)),
      "sql_plan" => empty($bloqueos) ? array("INSERT INTO `erp_ecommerce_busquedas` (`session_id_hash`, `query`, `query_normalizada`, `canal`, `mascota`, `necesidad`, `resultados_total`, `sin_resultados`, `filtros_json`, `fecha_registro`) VALUES (...)") : array(),
      "guardrails" => $this->guardrailsExperienciaClientePreflight()
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: entregar tablero interno read-only de inteligencia cliente ecommerce.
   * Impacto: permite analizar demanda por busquedas, mascotas, necesidades y facturacion sin exponer datos publicos.
   * Contrato: solo lectura; no registra eventos, no guarda solicitudes y no toca inventario.
   */
  public function inteligenciaClienteInterna($filtros = array()) {
    try {
      $db = $this->getConexion();
      $desde = trim((string) $this->valor($filtros, "desde", date("Y-m-d", strtotime("-30 days"))));
      $hasta = trim((string) $this->valor($filtros, "hasta", date("Y-m-d")));
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) { $desde = date("Y-m-d", strtotime("-30 days")); }
      if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) { $hasta = date("Y-m-d"); }
      $limite = max(5, min(50, intval($this->valor($filtros, "limite", 10))));
      $tablas = array(
        "busquedas" => $db ? $this->tablaExiste($db, "erp_ecommerce_busquedas") : false,
        "eventos" => $db ? $this->tablaExiste($db, "erp_ecommerce_eventos_navegacion") : false,
        "facturacion" => $db ? $this->tablaExiste($db, "erp_ecommerce_facturacion_solicitudes") : false
      );
      $depurar = array(
        "read_only" => true,
        "configurado" => in_array(true, $tablas, true),
        "rango" => array("desde" => $desde, "hasta" => $hasta, "limite" => $limite),
        "tablas" => $tablas,
        "resumen" => array(
          "busquedas_total" => 0,
          "busquedas_sin_resultado" => 0,
          "eventos_total" => 0,
          "solicitudes_facturacion_total" => 0
        ),
        "busquedas_frecuentes" => array(),
        "busquedas_sin_resultado" => array(),
        "mascotas_consultadas" => array(),
        "necesidades_consultadas" => array(),
        "productos_vistos" => array(),
        "productos_agregados_cotizacion" => array(),
        "conversion_whatsapp" => array("open_whatsapp" => 0, "add_to_quote" => 0, "ratio_estimado" => null),
        "facturacion_por_estatus" => array(),
        "guardrails" => array(
          "no_escribe_bd" => true,
          "no_expone_datos_personales" => true,
          "no_muestra_ip_user_agent" => true,
          "no_toca_inventario" => true
        )
      );
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", $depurar);
      }
      $inicio = $desde . " 00:00:00";
      $fin = $hasta . " 23:59:59";

      if ($tablas["busquedas"]) {
        $stmt = $db->prepare("SELECT COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultado
          FROM erp_ecommerce_busquedas
          WHERE fecha_registro BETWEEN :inicio AND :fin");
        $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $depurar["resumen"]["busquedas_total"] = intval($this->valor($fila, "total", 0));
        $depurar["resumen"]["busquedas_sin_resultado"] = intval($this->valor($fila, "sin_resultado", 0));
        $depurar["busquedas_frecuentes"] = $this->consultaTopBusquedas($db, $inicio, $fin, $limite, false);
        $depurar["busquedas_sin_resultado"] = $this->consultaTopBusquedas($db, $inicio, $fin, $limite, true);
      }

      if ($tablas["eventos"]) {
        $stmt = $db->prepare("SELECT COUNT(*) total FROM erp_ecommerce_eventos_navegacion WHERE fecha_registro BETWEEN :inicio AND :fin");
        $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
        $depurar["resumen"]["eventos_total"] = intval($stmt->fetchColumn());
        $depurar["mascotas_consultadas"] = $this->consultaTopEventosCampo($db, "mascota_especie", $inicio, $fin, $limite);
        $depurar["necesidades_consultadas"] = $this->consultaTopEventosCampo($db, "necesidad", $inicio, $fin, $limite);
        $depurar["productos_vistos"] = $this->consultaTopEventosProducto($db, "view_product", $inicio, $fin, $limite);
        $depurar["productos_agregados_cotizacion"] = $this->consultaTopEventosProducto($db, "add_to_quote", $inicio, $fin, $limite);
        $depurar["conversion_whatsapp"] = $this->consultaConversionWhatsapp($db, $inicio, $fin);
      }

      if ($tablas["facturacion"]) {
        $stmt = $db->prepare("SELECT COUNT(*) total FROM erp_ecommerce_facturacion_solicitudes WHERE fecha_registro BETWEEN :inicio AND :fin");
        $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
        $depurar["resumen"]["solicitudes_facturacion_total"] = intval($stmt->fetchColumn());
        $stmt = $db->prepare("SELECT estatus, COUNT(*) total
          FROM erp_ecommerce_facturacion_solicitudes
          WHERE fecha_registro BETWEEN :inicio AND :fin
          GROUP BY estatus
          ORDER BY total DESC, estatus ASC");
        $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
        $depurar["facturacion_por_estatus"] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      return $this->respuesta(false, "success", "Inteligencia cliente ecommerce consultada", $depurar);
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("read_only" => true, "no_escribe_bd" => true));
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
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array(
          "read_only" => true,
          "no_escribe_bd" => true,
          "resumen" => array(
            "skus_total" => 0,
            "skus_publicables_fase_1" => 0
          ),
          "candidatos" => array(),
          "guardrails" => array(
            "no_crea_publicaciones" => true,
            "no_mueve_inventario" => true,
            "no_toca_ecom_legacy" => true
          )
        ));
      }

      $limite = max(10, min(500, intval($this->valor($filtros, "limite", 50))));
      $pagina = max(1, intval($this->valor($filtros, "pagina", 1)));
      $offset = ($pagina - 1) * $limite;
      $soloBloqueados = intval($this->valor($filtros, "solo_bloqueados", 0)) === 1;
      $soloPublicables = intval($this->valor($filtros, "solo_publicables", 0)) === 1;
      $busqueda = trim((string) $this->valor($filtros, "q", ""));
      $estatusPublicacion = trim((string) $this->valor($filtros, "estatus_publicacion", ""));
      $disponibilidad = trim((string) $this->valor($filtros, "disponibilidad", ""));
      $categoriaTexto = trim((string) $this->valor($filtros, "categoria_texto", ""));
      $mascota = $this->limpiarFiltroPublico($this->valor($filtros, "mascota", ""));
      $necesidad = $this->limpiarFiltroPublico($this->valor($filtros, "necesidad", ""));
      $granel = trim((string) $this->valor($filtros, "granel", ""));

      $resumen = $this->resumenPublicabilidad($db);
      $paginacion = array();
      $candidatos = $this->listarCandidatosPublicacion($db, $limite, $soloBloqueados, $soloPublicables, $busqueda, $estatusPublicacion, array(
        "disponibilidad" => $disponibilidad,
        "categoria_texto" => $categoriaTexto,
        "mascota" => $mascota,
        "necesidad" => $necesidad,
        "granel" => $granel
      ), $offset, $paginacion);
      $total = intval($this->valor($paginacion, "total", 0));
      $totalPaginas = max(1, intval(ceil($total / max(1, $limite))));

      return $this->respuesta(false, "success", "Auditoria ecommerce publica consultada", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_usa_ecom_como_fuente" => true,
        "resumen" => $resumen,
        "paginacion" => array(
          "pagina" => $pagina,
          "limite" => $limite,
          "offset" => $offset,
          "total" => $total,
          "total_paginas" => $totalPaginas,
          "tiene_anterior" => $pagina > 1,
          "tiene_siguiente" => $pagina < $totalPaginas
        ),
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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: resumir avance de la Fase 1 ecommerce/publicaciones con criterios de salida claros.
   * Impacto: Panel ecommerce; evita avanzar a catalogo robusto sin gobierno minimo de publicaciones.
   * Contrato: solo lectura; no escribe BD, no publica productos, no toca inventario ni legacy ecom_*.
   */
  public function fasePublicacionesEstadoInterna($filtros = array()) {
    try {
      $readiness = $this->readinessFrontendInterna(array(
        "base_url" => $this->valor($filtros, "base_url", "http://panel.com.local")
      ));
      $auditoria = $this->auditarPublicabilidad(array("limite" => 25));
      $publicados = $this->auditarPublicabilidad(array("limite" => 25, "estatus_publicacion" => "publicado"));
      $borradores = $this->auditarPublicabilidad(array("limite" => 25, "estatus_publicacion" => "borrador"));
      $granel = $this->auditarPublicabilidad(array("limite" => 10, "granel" => "1"));

      $pubs = $this->valor($readiness, array("depurar", "publicaciones"), array());
      $publicadas = intval($this->valor($pubs, "total_publicadas", 0));
      $publicables = intval($this->valor($pubs, "skus_publicables_fase_1", 0));
      $senal = (string) $this->valor($readiness, array("depurar", "senal_frontend"), "amarillo_mock_contratos");
      $publicacionesGranelActivas = $this->contarPublicacionesGranelActivas();

      $criterios = array(
        "panel_control_disponible" => true,
        "panel_publicaciones_disponible" => true,
        "auditoria_publicabilidad_disponible" => empty($auditoria["error"]),
        "filtros_gobierno_disponibles" => true,
        "acciones_lote_disponibles" => true,
        "escrituras_protegidas_por_permiso_token_auditoria" => true,
        "api_publica_verde_datos_reales" => $senal === "verde_datos_reales",
        "minimo_publicaciones_reales" => $publicadas >= 6,
        "catalogo_publicables_detectados" => $publicables > 0,
        "granel_filtrable" => empty($granel["error"]),
        "sin_granel_publicado" => $publicacionesGranelActivas === 0
      );
      $pendientes = array();
      foreach ($criterios as $clave => $ok) {
        if (!$ok) {
          $pendientes[] = $clave;
        }
      }

      $faseLista = empty($pendientes);
      return $this->respuesta(false, $faseLista ? "success" : "warning", $faseLista ? "Fase 1 lista para cierre operativo" : "Fase 1 en progreso", array(
        "fase" => "fase_1_publicaciones_control",
        "estado" => $faseLista ? "lista_para_cierre_operativo" : "en_progreso",
        "puede_pasar_a_fase_2" => $faseLista,
        "fase_2_siguiente" => "api_catalogo_robusta",
        "criterios_salida" => $criterios,
        "pendientes_para_cierre" => $pendientes,
        "metricas" => array(
          "senal_frontend" => $senal,
          "publicadas" => $publicadas,
          "borradores" => intval($this->valor($pubs, "total_borradores", 0)),
          "pausadas" => intval($this->valor($pubs, "total_pausadas", 0)),
          "skus_publicables_fase_1" => $publicables,
          "muestra_publicados" => count($this->valor($publicados, array("depurar", "candidatos"), array())),
          "muestra_borradores" => count($this->valor($borradores, array("depurar", "candidatos"), array())),
          "muestra_granel" => count($this->valor($granel, array("depurar", "candidatos"), array())),
          "publicaciones_granel_activas" => $publicacionesGranelActivas
        ),
        "capacidades_ya_disponibles" => array(
          "buscar_por_sku_nombre_marca_categoria",
          "filtrar_por_estatus_publicacion",
          "filtrar_por_disponibilidad_publica",
          "filtrar_o_excluir_granel",
          "guardar_borrador_autorizado",
          "editar_curaduria_autorizada",
          "publicar_pausar_reactivar",
          "acciones_por_lote",
          "readiness_frontend",
          "auditoria_readonly"
        ),
        "guardrails" => array(
          "no_toca_inventario" => true,
          "no_toca_legacy_ecom" => true,
          "no_modifica_precio_imagen_catalogo" => true,
          "no_publicar_granel" => true,
          "stock_exacto_no_publico" => true,
          "escrituras_requieren_catalogo_editar_token_csrf_auditoria" => true
        ),
        "que_sigue" => $faseLista ? array(
          "cerrar_validacion_operativa_de_fase_1_en_panel",
          "documentar_decision_de_pasar_a_fase_2",
          "iniciar_fase_2_api_catalogo_robusta"
        ) : array(
          "resolver_pendientes_para_cierre",
          "validar_panel_con_usuario_operativo",
          "repetir_uat_panel_publicaciones_readonly"
        )
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array(
        "fase" => "fase_1_publicaciones_control",
        "estado" => "error",
        "read_only" => true
      ));
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
          "seo" => "/ecommercePublico/seo",
          "disponibilidad" => "/ecommercePublico/disponibilidad",
          "cotizacion_dryrun" => "/ecommercePublico/cotizacion_dryrun"
        ),
        "comandos_readonly" => array(
          "salud_entorno" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local",
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
      $publicacionActual = array(
        "id_publicacion" => isset($fila["id_publicacion"]) ? intval($fila["id_publicacion"]) : 0,
        "estatus_publicacion" => isset($fila["estatus_publicacion"]) ? (string) $fila["estatus_publicacion"] : "",
        "slug" => isset($fila["slug_publicacion"]) ? (string) $fila["slug_publicacion"] : "",
        "titulo_publico" => isset($fila["titulo_publico_publicacion"]) ? (string) $fila["titulo_publico_publicacion"] : "",
        "descripcion_publica" => isset($fila["descripcion_publica_publicacion"]) ? (string) $fila["descripcion_publica_publicacion"] : "",
        "presentacion_publica" => isset($fila["presentacion_publica_publicacion"]) ? (string) $fila["presentacion_publica_publicacion"] : "",
        "mascota_especie" => isset($fila["mascota_especie_publicacion"]) ? $this->normalizarMascotasPublicacion($fila["mascota_especie_publicacion"]) : "",
        "necesidades" => isset($fila["necesidades_json_publicacion"]) ? $this->normalizarNecesidadesPublicacion($fila["necesidades_json_publicacion"]) : array(),
        "destacado" => isset($fila["destacado_publicacion"]) ? intval($fila["destacado_publicacion"]) : 0,
        "orden" => isset($fila["orden_publicacion"]) ? intval($fila["orden_publicacion"]) : 0,
        "permite_cotizacion" => isset($fila["permite_cotizacion_publicacion"]) ? intval($fila["permite_cotizacion_publicacion"]) : 1,
        "permite_whatsapp" => isset($fila["permite_whatsapp_publicacion"]) ? intval($fila["permite_whatsapp_publicacion"]) : 1,
        "mostrar_precio" => isset($fila["mostrar_precio_publicacion"]) ? intval($fila["mostrar_precio_publicacion"]) : 1,
        "mostrar_disponibilidad" => isset($fila["mostrar_disponibilidad_publicacion"]) ? intval($fila["mostrar_disponibilidad_publicacion"]) : 1
      );
      $metadata = $this->inferirMetadataMascotas($fila);
      $taxonomiaPublicacion = $this->taxonomiaPublicacionControlada();
      $titulo = trim((string) $fila["nombre_publico"]);
      $descripcionCatalogo = $this->descripcionCatalogoParaEcommerce($fila);
      $presentacion = trim((string) $fila["presentacion_base"]);
      $slugBase = $titulo . " " . $presentacion . " " . $fila["sku"];
      $necesidadesSugeridas = $metadata["necesidades"];
      if ($publicacionActual["id_publicacion"] > 0 && !empty($publicacionActual["necesidades"])) {
        $necesidadesSugeridas = $publicacionActual["necesidades"];
      }
      $necesidadesSugeridas = $this->normalizarNecesidadesPublicacion($necesidadesSugeridas);

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
          "disponibilidad_publica_sugerida" => $this->disponibilidadPublicaSugerida($fila),
          "id_publicacion" => isset($fila["id_publicacion"]) ? intval($fila["id_publicacion"]) : 0,
          "estatus_publicacion" => isset($fila["estatus_publicacion"]) ? (string) $fila["estatus_publicacion"] : ""
        ),
        "publicacion_actual" => $publicacionActual,
        "publicacion_sugerida" => array(
          "canal" => "catalogo_publico",
          "estatus_publicacion" => "borrador",
          "slug" => $publicacionActual["slug"] !== "" ? $publicacionActual["slug"] : $this->slugificar($slugBase),
          "titulo_publico" => $publicacionActual["titulo_publico"] !== "" ? $publicacionActual["titulo_publico"] : $titulo,
          "descripcion_publica" => $publicacionActual["descripcion_publica"] !== "" ? $publicacionActual["descripcion_publica"] : $descripcionCatalogo,
          "presentacion_publica" => $publicacionActual["presentacion_publica"] !== "" ? $publicacionActual["presentacion_publica"] : $presentacion,
          "mascota_especie" => $publicacionActual["mascota_especie"] !== "" ? $publicacionActual["mascota_especie"] : $metadata["mascota_especie"],
          "necesidades" => $necesidadesSugeridas,
          "destacado" => $publicacionActual["destacado"],
          "orden" => $publicacionActual["orden"],
          "permite_cotizacion" => $publicacionActual["permite_cotizacion"],
          "permite_whatsapp" => $publicacionActual["permite_whatsapp"],
          "mostrar_precio" => $publicacionActual["mostrar_precio"],
          "mostrar_disponibilidad" => $publicacionActual["mostrar_disponibilidad"]
        ),
        "taxonomia_publicacion" => $taxonomiaPublicacion,
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
      $estatusActual = isset($fila["estatus_publicacion"]) ? (string) $fila["estatus_publicacion"] : "";
      if (!empty($fila["id_publicacion"]) && $estatusActual === "borrador") {
        $bloqueos = array_values(array_filter($bloqueos, function($bloqueo) {
          return $bloqueo !== "publicacion_existente";
        }));
      } elseif (!empty($fila["id_publicacion"])) {
        $bloqueos[] = "publicacion_existente_no_borrador";
      }
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
      $mascota = $this->normalizarMascotasPublicacion($this->valor($datos, "mascota_especie", $this->valor($sugerida, "mascota_especie", "")));
      $descripcionPublica = trim((string) $this->valor($datos, "descripcion_publica", $this->valor($sugerida, "descripcion_publica", "")));
      if ($descripcionPublica === "") {
        $descripcionPublica = $this->descripcionCatalogoParaEcommerce($fila);
      }
      $publicacion = array(
        "id_producto_erp" => intval($fila["id_producto_erp"]),
        "id_sku" => intval($fila["id_sku"]),
        "canal" => "catalogo_publico",
        "estatus_publicacion" => $estatus,
        "slug" => $this->slugificar($this->valor($datos, "slug", $this->valor($sugerida, "slug", ""))),
        "titulo_publico" => trim((string) $this->valor($datos, "titulo_publico", $this->valor($sugerida, "titulo_publico", $fila["nombre_publico"]))),
        "descripcion_publica" => $descripcionPublica,
        "presentacion_publica" => trim((string) $this->valor($datos, "presentacion_publica", $this->valor($sugerida, "presentacion_publica", $fila["presentacion_base"]))),
        "mascota_especie" => $mascota,
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

      $bloqueosFisicos = array_values(array_filter($this->bloqueosPublicacion($fila), function($bloqueo) {
        return $bloqueo !== "publicacion_existente";
      }));
      $bloqueosPublicacion = array_values(array_unique($bloqueos));
      $bloqueosQuePermitenBorrador = array(
        "precio_general_faltante",
        "imagen_faltante",
        "categoria_principal_faltante",
        "venta_fraccionaria_bloqueada_fase_1"
      );
      $bloqueosValidacion = array_values(array_filter($bloqueosPublicacion, function($bloqueo) use ($bloqueosQuePermitenBorrador) {
        return !in_array($bloqueo, $bloqueosQuePermitenBorrador, true);
      }));

      return $this->respuesta(false, empty($bloqueosValidacion) && empty($bloqueosFisicos) ? "success" : "warning", "Plan de publicacion ecommerce generado sin ejecutar", array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_publica_automaticamente" => true,
        "tabla_publicaciones_existe" => $tablaExiste,
        "publicable_fase_1" => empty($bloqueosFisicos),
        "borrador_permitido" => empty($bloqueosValidacion),
        "bloqueos_publicacion" => $bloqueosPublicacion,
        "bloqueos_validacion_borrador" => $bloqueosValidacion,
        "bloqueos_publicabilidad" => $bloqueosFisicos,
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
      $bloqueos = $this->valor($depurarPlan, array("bloqueos_validacion_borrador"), $this->valor($depurarPlan, array("bloqueos_publicacion"), array()));
      $publicacion = $this->valor($depurarPlan, array("publicacion_normalizada"), array());

      if (!empty($bloqueos) || empty($publicacion)) {
        return $this->respuesta(true, "warning", "No se guardo publicacion por bloqueos de validacion", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => $bloqueos,
          "bloqueos_publicabilidad" => $this->valor($depurarPlan, array("bloqueos_publicabilidad"), array()),
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
        "publicable_fase_1" => (bool) $this->valor($depurarPlan, "publicable_fase_1", false),
        "bloqueos_publicabilidad" => $this->valor($depurarPlan, array("bloqueos_publicabilidad"), array()),
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
        if (!empty($publicacion)) {
          $bloqueos = array_values(array_filter($bloqueos, function($bloqueo) {
            return $bloqueo !== "publicacion_existente";
          }));
        }
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

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: guardar borradores ecommerce para un lote de SKUs seleccionados en panel.
   * Impacto: acelera expansion inicial sin publicar automaticamente ni tocar inventario.
   * Contrato: escribe solo curaduria en `erp_ecommerce_publicaciones`; requiere token de lote.
   */
  public function guardarBorradoresLoteAutorizado($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_LOTE_BORRADOR") {
      return $this->respuesta(true, "warning", "Guardado de lote borrador bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_LOTE_BORRADOR"
      ));
    }
    $skus = $this->normalizarIdsSkuLote($this->valor($datos, "id_skus", array()));
    if (empty($skus)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un SKU", array("no_escribe_bd" => true));
    }
    $resultados = array();
    $ok = 0;
    $error = 0;
    foreach ($skus as $idSku) {
      $respuesta = $this->guardarPublicacionBorradorAutorizada(array(
        "id_sku" => $idSku,
        "estatus_publicacion" => "borrador"
      ), array("autorizar" => "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR"));
      if (empty($respuesta["error"])) {
        $ok++;
      } else {
        $error++;
      }
      $resultados[] = array(
        "id_sku" => $idSku,
        "ok" => empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : "",
        "bloqueos" => $this->valor($respuesta, array("depurar", "bloqueos_publicacion"), array()),
        "bloqueos_publicabilidad" => $this->valor($respuesta, array("depurar", "bloqueos_publicabilidad"), array()),
        "publicacion" => $this->valor($respuesta, array("depurar", "publicacion"), array())
      );
    }
    return $this->respuesta($ok === 0, $ok > 0 ? "success" : "warning", "Lote de borradores procesado", array(
      "escribe_bd" => $ok > 0,
      "id_skus" => $skus,
      "total_ok" => $ok,
      "total_error" => $error,
      "resultados" => $resultados,
      "no_publica_automaticamente" => true,
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: aplicar campos de configuracion/visibilidad ecommerce a un lote de SKUs.
   * Impacto: acelera configuracion masiva de publicaciones sin cambiar inventario, precios ERP ni estatus publicado.
   * Contrato: requiere token; solo aplica campos explicitamente enviados y puede crear borrador si no existe publicacion.
   */
  public function aplicarConfiguracionLoteAutorizada($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_LOTE_CONFIGURACION") {
      return $this->respuesta(true, "warning", "Configuracion masiva bloqueada", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_LOTE_CONFIGURACION"
      ));
    }

    $skus = $this->normalizarIdsSkuLote($this->valor($datos, "id_skus", array()));
    if (empty($skus)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un SKU", array("no_escribe_bd" => true));
    }

    $camposPermitidos = array("mostrar_precio", "mostrar_disponibilidad", "permite_cotizacion", "permite_whatsapp", "destacado");
    $configuracion = array();
    foreach ($camposPermitidos as $campo) {
      if (array_key_exists($campo, $datos) && trim((string) $datos[$campo]) !== "") {
        $configuracion[$campo] = $this->booleanoPublicacion($datos[$campo]);
      }
    }
    $crearBorrador = intval($this->valor($datos, "crear_borrador_si_no_existe", 1)) === 1;
    if (empty($configuracion) && !$crearBorrador) {
      return $this->respuesta(true, "warning", "Selecciona al menos una configuracion para aplicar", array("no_escribe_bd" => true));
    }

    $ok = 0;
    $error = 0;
    $resultados = array();
    foreach ($skus as $idSku) {
      $preparacion = $this->prepararPublicacion(array("id_sku" => $idSku));
      if (!empty($preparacion["error"])) {
        $error++;
        $resultados[] = array(
          "id_sku" => $idSku,
          "ok" => false,
          "mensaje" => isset($preparacion["mensaje"]) ? $preparacion["mensaje"] : "",
          "bloqueos" => $this->valor($preparacion, array("depurar", "bloqueos_publicacion"), array())
        );
        continue;
      }

      $actual = $this->valor($preparacion, array("depurar", "publicacion_actual"), array());
      $idPublicacion = intval($this->valor($actual, "id_publicacion", 0));
      $payload = array_merge(array("id_sku" => $idSku), $configuracion);
      if ($idPublicacion > 0) {
        $payload["id_publicacion"] = $idPublicacion;
        $respuesta = $this->guardarCuraduriaPublicacionAutorizada($payload, array("autorizar" => "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA"));
      } elseif ($crearBorrador) {
        $respuesta = $this->guardarPublicacionBorradorAutorizada($payload, array("autorizar" => "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR"));
      } else {
        $respuesta = $this->respuesta(true, "warning", "Publicacion no existe para aplicar configuracion", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => array("publicacion_no_existe")
        ));
      }

      if (empty($respuesta["error"])) {
        $ok++;
      } else {
        $error++;
      }
      $resultados[] = array(
        "id_sku" => $idSku,
        "ok" => empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : "",
        "id_publicacion" => $idPublicacion,
        "bloqueos" => $this->valor($respuesta, array("depurar", "bloqueos_publicacion"), array()),
        "bloqueos_publicabilidad" => $this->valor($respuesta, array("depurar", "bloqueos_publicabilidad"), array()),
        "publicacion" => $this->valor($respuesta, array("depurar", "publicacion"), array())
      );
    }

    return $this->respuesta($ok === 0, $ok > 0 ? "success" : "warning", "Configuracion masiva ecommerce procesada", array(
      "escribe_bd" => $ok > 0,
      "id_skus" => $skus,
      "campos_aplicados" => $configuracion,
      "crear_borrador_si_no_existe" => $crearBorrador,
      "total_ok" => $ok,
      "total_error" => $error,
      "resultados" => $resultados,
      "no_publica_automaticamente" => true,
      "no_toca_inventario" => true,
      "no_toca_catalogo_erp" => true,
      "no_toca_ecom_legacy" => true
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30; actualizado 2026-08-11
   * Proposito: publicar un lote de borradores ecommerce seleccionados en panel.
   * Impacto: expone publicaciones aprobadas al API publico manteniendo inventario intacto.
   * Contrato: escribe solo estatus/fecha de publicacion; requiere token de lote, confirmacion de revision y confirmacion explicita si hay agotados.
   */
  public function publicarBorradoresLoteAutorizado($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_LOTE_PUBLICAR") {
      return $this->respuesta(true, "warning", "Publicacion de lote bloqueada", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_LOTE_PUBLICAR"
      ));
    }
    if (intval($this->valor($datos, "confirmar_revision", 0)) !== 1) {
      return $this->respuesta(true, "warning", "Confirma revision antes de publicar lote", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "bloqueos_publicacion" => array("confirmar_revision_requerido")
      ));
    }
    $skus = $this->normalizarIdsSkuLote($this->valor($datos, "id_skus", array()));
    if (empty($skus)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un SKU", array("no_escribe_bd" => true));
    }
    $resultados = array();
    $ok = 0;
    $error = 0;
    foreach ($skus as $idSku) {
      $respuesta = $this->publicarBorradorAutorizado(array(
        "id_sku" => $idSku,
        "confirmar_revision" => 1,
        "confirmar_agotado" => intval($this->valor($datos, "confirmar_agotado", 0))
      ), array("autorizar" => "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR"));
      if (empty($respuesta["error"])) {
        $ok++;
      } else {
        $error++;
      }
      $resultados[] = array(
        "id_sku" => $idSku,
        "ok" => empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : "",
        "bloqueos" => $this->valor($respuesta, array("depurar", "bloqueos_publicacion"), array()),
        "publicacion" => $this->valor($respuesta, array("depurar", "publicacion"), array())
      );
    }
    return $this->respuesta($ok === 0, $ok > 0 ? "success" : "warning", "Lote de publicaciones procesado", array(
      "escribe_bd" => $ok > 0,
      "id_skus" => $skus,
      "total_ok" => $ok,
      "total_error" => $error,
      "resultados" => $resultados,
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: actualizar curaduria ecommerce existente sin cambiar su estatus publicado/borrador.
   * Impacto: permite corregir fichas publicas desde panel manteniendo Catalogo ERP como fuente viva.
   * Contrato: escribe solo `erp_ecommerce_publicaciones`; no toca precios, imagenes, inventario ni legacy `ecom_*`.
   */
  public function guardarCuraduriaPublicacionAutorizada($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA") {
      return $this->respuesta(true, "warning", "Guardado de curaduria bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA"
      ));
    }

    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
      }

      $idPublicacion = intval($this->valor($datos, "id_publicacion", 0));
      $idSku = intval($this->valor($datos, "id_sku", 0));
      if ($idPublicacion <= 0 && $idSku <= 0) {
        return $this->respuesta(true, "warning", "Selecciona una publicacion ecommerce", array("no_escribe_bd" => true));
      }

      $stmt = $db->prepare("SELECT * FROM erp_ecommerce_publicaciones WHERE " . ($idPublicacion > 0 ? "id_publicacion=:id" : "id_sku=:sku AND canal='catalogo_publico'") . " LIMIT 1");
      $stmt->execute($idPublicacion > 0 ? array(":id" => $idPublicacion) : array(":sku" => $idSku));
      $actual = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$actual) {
        return $this->respuesta(true, "warning", "Publicacion ecommerce no encontrada", array("no_escribe_bd" => true));
      }
      $idSku = intval($actual["id_sku"]);
      $estatusActual = (string) $actual["estatus_publicacion"];
      if (!in_array($estatusActual, array("borrador", "publicado", "pausado"), true)) {
        return $this->respuesta(true, "warning", "Estatus de publicacion no editable", array(
          "no_escribe_bd" => true,
          "estatus_publicacion" => $estatusActual
        ));
      }

      $fila = $this->consultarCandidatoPorSku($db, $idSku);
      if (!$fila) {
        return $this->respuesta(true, "warning", "SKU no encontrado o inactivo", array("no_escribe_bd" => true));
      }
      $bloqueos = array_values(array_filter($this->bloqueosPublicacion($fila), function($bloqueo) {
        return $bloqueo !== "publicacion_existente";
      }));

      $slug = $this->slugificar($this->valor($datos, "slug", $actual["slug"]));
      $titulo = trim((string) $this->valor($datos, "titulo_publico", $actual["titulo_publico"]));
      $descripcion = trim((string) $this->valor($datos, "descripcion_publica", $actual["descripcion_publica"]));
      if ($descripcion === "") {
        $descripcion = $this->descripcionCatalogoParaEcommerce($fila);
      }
      $presentacion = trim((string) $this->valor($datos, "presentacion_publica", $actual["presentacion_publica"]));
      $mascota = $this->normalizarMascotasPublicacion($this->valor($datos, "mascota_especie", $actual["mascota_especie"]));
      $necesidades = $this->normalizarNecesidadesPublicacion($this->valor($datos, "necesidades", $actual["necesidades_json"]));

      if ($slug === "") { $bloqueos[] = "slug_requerido"; }
      if ($titulo === "") { $bloqueos[] = "titulo_publico_requerido"; }
      if ($this->conflictoSlugPublicacion($db, $slug, $idSku)) { $bloqueos[] = "slug_ya_usado_por_otro_sku"; }
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "No se guardo curaduria por bloqueos de validacion", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => array_values(array_unique($bloqueos))
        ));
      }

      $stmtUpdate = $db->prepare("UPDATE erp_ecommerce_publicaciones
        SET slug=:slug,
          titulo_publico=:titulo,
          descripcion_publica=:descripcion,
          presentacion_publica=:presentacion,
          mascota_especie=:mascota,
          necesidades_json=:necesidades,
          destacado=:destacado,
          orden=:orden,
          permite_cotizacion=:permite_cotizacion,
          permite_whatsapp=:permite_whatsapp,
          mostrar_precio=:mostrar_precio,
          mostrar_disponibilidad=:mostrar_disponibilidad,
          fecha_actualizacion=NOW()
        WHERE id_publicacion=:id
        LIMIT 1");
      $stmtUpdate->execute(array(
        ":slug" => $slug,
        ":titulo" => $titulo,
        ":descripcion" => $descripcion,
        ":presentacion" => $presentacion,
        ":mascota" => $mascota,
        ":necesidades" => json_encode($necesidades, JSON_UNESCAPED_UNICODE),
        ":destacado" => $this->booleanoPublicacion($this->valor($datos, "destacado", $actual["destacado"])),
        ":orden" => intval($this->valor($datos, "orden", $actual["orden"])),
        ":permite_cotizacion" => $this->booleanoPublicacion($this->valor($datos, "permite_cotizacion", $actual["permite_cotizacion"])),
        ":permite_whatsapp" => $this->booleanoPublicacion($this->valor($datos, "permite_whatsapp", $actual["permite_whatsapp"])),
        ":mostrar_precio" => $this->booleanoPublicacion($this->valor($datos, "mostrar_precio", $actual["mostrar_precio"])),
        ":mostrar_disponibilidad" => $this->booleanoPublicacion($this->valor($datos, "mostrar_disponibilidad", $actual["mostrar_disponibilidad"])),
        ":id" => intval($actual["id_publicacion"])
      ));

      $consulta = $db->prepare("SELECT id_publicacion, id_producto_erp, id_sku, canal, estatus_publicacion, slug, titulo_publico
        FROM erp_ecommerce_publicaciones
        WHERE id_publicacion=:id
        LIMIT 1");
      $consulta->execute(array(":id" => intval($actual["id_publicacion"])));
      $publicacion = $consulta->fetch(PDO::FETCH_ASSOC);

      return $this->respuesta(false, "success", "Curaduria ecommerce guardada", array(
        "escribe_bd" => true,
        "estatus_preservado" => $estatusActual,
        "publicacion" => $publicacion,
        "no_toca_inventario" => true,
        "no_toca_ecom_legacy" => true,
        "precio_imagen_se_leen_vivos_desde_erp" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: cambiar el estatus operativo de una publicacion ecommerce desde el panel de control.
   * Impacto: permite pausar/reactivar publicaciones de Artiani sin tocar Catalogo ERP, inventario ni legacy `ecom_*`.
   * Contrato: escribe solo `estatus_publicacion`; requiere token interno y confirmacion para publicar agotados.
   */
  public function cambiarEstatusPublicacionAutorizado($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_GOBIERNO_ESTATUS") {
      return $this->respuesta(true, "warning", "Cambio de estatus ecommerce bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_GOBIERNO_ESTATUS"
      ));
    }
    try {
      $db = $this->getConexion();
      if (!$db) {
        return $this->respuesta(true, "warning", "Conexion MySQL no disponible", array("no_escribe_bd" => true));
      }
      $idPublicacion = intval($this->valor($datos, "id_publicacion", 0));
      $idSku = intval($this->valor($datos, "id_sku", 0));
      $estatus = trim((string) $this->valor($datos, "estatus_publicacion", ""));
      if (!in_array($estatus, array("borrador", "publicado", "pausado"), true)) {
        return $this->respuesta(true, "warning", "Estatus ecommerce no permitido", array("no_escribe_bd" => true));
      }
      if ($idPublicacion <= 0 && $idSku <= 0) {
        return $this->respuesta(true, "warning", "Selecciona una publicacion ecommerce", array("no_escribe_bd" => true));
      }

      $stmt = $db->prepare("SELECT id_publicacion, id_sku, estatus_publicacion FROM erp_ecommerce_publicaciones WHERE " . ($idPublicacion > 0 ? "id_publicacion=:id" : "id_sku=:sku AND canal='catalogo_publico'") . " LIMIT 1");
      $stmt->execute($idPublicacion > 0 ? array(":id" => $idPublicacion) : array(":sku" => $idSku));
      $actual = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$actual) {
        return $this->respuesta(true, "warning", "Publicacion ecommerce no encontrada", array("no_escribe_bd" => true));
      }
      $idSku = intval($actual["id_sku"]);
      $candidato = $this->consultarCandidatoPorSku($db, $idSku);
      $bloqueos = array();
      if ($estatus === "publicado") {
        if (!$candidato) {
          $bloqueos[] = "sku_no_encontrado_o_inactivo";
        } else {
          $bloqueos = array_values(array_filter($this->bloqueosPublicacion($candidato), function($bloqueo) {
            return $bloqueo !== "publicacion_existente";
          }));
          if ($this->disponibilidadPublicaSugerida($candidato) === "agotado" && intval($this->valor($datos, "confirmar_agotado", 0)) !== 1) {
            $bloqueos[] = "sku_agotado_requiere_confirmar_agotado";
          }
        }
      }
      if (!empty($bloqueos)) {
        return $this->respuesta(true, "warning", "No se cambio estatus por bloqueos", array(
          "no_escribe_bd" => true,
          "bloqueos_publicacion" => array_values(array_unique($bloqueos))
        ));
      }

      $stmtUpdate = $db->prepare("UPDATE erp_ecommerce_publicaciones
        SET estatus_publicacion=:estatus,
          fecha_publicacion=CASE WHEN :estatus_pub='publicado' THEN COALESCE(fecha_publicacion, NOW()) ELSE fecha_publicacion END,
          fecha_actualizacion=NOW()
        WHERE id_publicacion=:id
        LIMIT 1");
      $stmtUpdate->execute(array(
        ":estatus" => $estatus,
        ":estatus_pub" => $estatus,
        ":id" => intval($actual["id_publicacion"])
      ));
      return $this->respuesta(false, "success", "Estatus ecommerce actualizado", array(
        "escribe_bd" => true,
        "id_publicacion" => intval($actual["id_publicacion"]),
        "id_sku" => $idSku,
        "estatus_anterior" => (string) $actual["estatus_publicacion"],
        "estatus_publicacion" => $estatus,
        "no_toca_inventario" => true,
        "no_toca_ecom_legacy" => true
      ));
    } catch (Exception $e) {
      return $this->respuesta(true, "danger", $e->getMessage(), array("escribe_bd" => false));
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: aplicar un estatus ecommerce a varios SKUs seleccionados desde el panel.
   * Impacto: permite deshabilitar/reactivar grupos por busqueda, categoria o seleccion manual.
   * Contrato: requiere token; no crea publicaciones nuevas, no toca inventario y no usa legacy `ecom_*`.
   */
  public function cambiarEstatusLoteAutorizado($datos = array(), $opciones = array()) {
    $token = trim((string) $this->valor($opciones, "autorizar", $this->valor($datos, "autorizar", "")));
    if ($token !== "ECOMMERCE_PUBLICO_LOTE_ESTATUS") {
      return $this->respuesta(true, "warning", "Cambio de estatus por lote bloqueado", array(
        "bloqueado" => true,
        "no_escribe_bd" => true,
        "token_requerido" => "ECOMMERCE_PUBLICO_LOTE_ESTATUS"
      ));
    }
    $skus = $this->normalizarIdsSkuLote($this->valor($datos, "id_skus", array()));
    if (empty($skus)) {
      return $this->respuesta(true, "warning", "Selecciona al menos un SKU", array("no_escribe_bd" => true));
    }
    $estatus = trim((string) $this->valor($datos, "estatus_publicacion", ""));
    $ok = 0;
    $error = 0;
    $resultados = array();
    foreach ($skus as $idSku) {
      $respuesta = $this->cambiarEstatusPublicacionAutorizado(array(
        "id_sku" => $idSku,
        "estatus_publicacion" => $estatus,
        "confirmar_agotado" => intval($this->valor($datos, "confirmar_agotado", 0))
      ), array("autorizar" => "ECOMMERCE_PUBLICO_GOBIERNO_ESTATUS"));
      if (empty($respuesta["error"])) {
        $ok++;
      } else {
        $error++;
      }
      $resultados[] = array(
        "id_sku" => $idSku,
        "ok" => empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "bloqueos" => $this->valor($respuesta, array("depurar", "bloqueos_publicacion"), array())
      );
    }
    return $this->respuesta($ok === 0, $ok > 0 ? "success" : "warning", "Lote de estatus ecommerce procesado", array(
      "escribe_bd" => $ok > 0,
      "estatus_publicacion" => $estatus,
      "total_ok" => $ok,
      "total_error" => $error,
      "resultados" => $resultados,
      "no_toca_inventario" => true,
      "no_toca_ecom_legacy" => true
    ));
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

  private function contarPublicacionesGranelActivas() {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablaExiste($db, "erp_ecommerce_publicaciones")) {
        return 0;
      }
      $stmt = $db->query("SELECT COUNT(*)
        FROM erp_ecommerce_publicaciones pub
        INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
        WHERE pub.estatus_publicacion='publicado'
          AND COALESCE(r.permite_venta_fraccionaria, 0)=1");
      return intval($stmt->fetchColumn());
    } catch (Exception $e) {
      return 0;
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: listar candidatos de publicaciones con paginacion real y total filtrado para UX de lotes.
   * Impacto: Ecommerce publico/publicaciones; no escribe BD ni altera criterios de publicabilidad.
   * Contrato: respeta filtros existentes y devuelve metadata por referencia cuando se solicita.
   */
  private function listarCandidatosPublicacion($db, $limite, $soloBloqueados, $soloPublicables, $busqueda = "", $estatusPublicacion = "", $filtrosExtra = array(), $offset = 0, &$paginacion = null) {
    $tienePublicaciones = $this->tablaExiste($db, "erp_ecommerce_publicaciones");
    $where = array("p.estatus='activo'", "s.estatus='activo'");
    $params = array();
    if ($soloPublicables) {
      $where[] = "pr.id_sku_precio IS NOT NULL";
      $where[] = "img.url_imagen IS NOT NULL";
      $where[] = "pc.id_categoria_erp IS NOT NULL";
      $where[] = "COALESCE(r.permite_venta_fraccionaria, 0)=0";
    }
    if ($soloBloqueados) {
      $where[] = "(pr.id_sku_precio IS NULL OR img.url_imagen IS NULL OR pc.id_categoria_erp IS NULL OR COALESCE(r.permite_venta_fraccionaria, 0)=1)";
    }
    if ($busqueda !== "") {
      $where[] = "(p.nombre LIKE :q OR s.nombre LIKE :q OR s.sku LIKE :q OR p.codigo_producto LIKE :q OR m.nombre LIKE :q OR c.nombre LIKE :q OR c.ruta LIKE :q)";
      $params[":q"] = "%" . $busqueda . "%";
    }
    $categoriaTexto = trim((string) $this->valor($filtrosExtra, "categoria_texto", ""));
    if ($categoriaTexto !== "") {
      $where[] = "(c.nombre LIKE :categoria_texto OR c.ruta LIKE :categoria_texto)";
      $params[":categoria_texto"] = "%" . $categoriaTexto . "%";
    }
    $mascota = $this->limpiarFiltroPublico($this->valor($filtrosExtra, "mascota", ""));
    if ($mascota !== "" && $tienePublicaciones) {
      $where[] = "(pub.mascota_especie=:mascota OR FIND_IN_SET(:mascota_set, REPLACE(pub.mascota_especie, ' ', ''))>0)";
      $params[":mascota"] = $mascota;
      $params[":mascota_set"] = $mascota;
    }
    $necesidad = $this->limpiarFiltroPublico($this->valor($filtrosExtra, "necesidad", ""));
    if ($necesidad !== "" && $tienePublicaciones) {
      $where[] = "pub.necesidades_json LIKE :necesidad";
      $params[":necesidad"] = "%\"" . $necesidad . "\"%";
    }
    $granel = trim((string) $this->valor($filtrosExtra, "granel", ""));
    if ($granel === "1") {
      $where[] = "COALESCE(r.permite_venta_fraccionaria, 0)=1";
    } elseif ($granel === "0") {
      $where[] = "COALESCE(r.permite_venta_fraccionaria, 0)=0";
    }
    $disponibilidad = trim((string) $this->valor($filtrosExtra, "disponibilidad", ""));
    if ($disponibilidad === "disponible") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)>3";
    } elseif ($disponibilidad === "pocas_piezas") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)>0";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)<=3";
    } elseif ($disponibilidad === "agotado") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)<=0";
    } elseif ($disponibilidad === "consultar_disponibilidad") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=0";
    }
    if ($estatusPublicacion !== "" && $tienePublicaciones) {
      if ($estatusPublicacion === "sin_publicacion") {
        $where[] = "pub.id_publicacion IS NULL";
      } elseif (in_array($estatusPublicacion, array("borrador", "publicado", "pausado"), true)) {
        $where[] = "pub.estatus_publicacion=:estatus_publicacion";
        $params[":estatus_publicacion"] = $estatusPublicacion;
      }
    }

    $joins = "FROM erp_catalogo_skus s
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
      " . ($tienePublicaciones ? "LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.estatus_publicacion IN ('borrador','publicado','pausado')" : "");

    $countSql = "SELECT COUNT(DISTINCT s.id_sku) " . $joins . "
      WHERE " . implode(" AND ", $where);
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    if (is_array($paginacion)) {
      $paginacion["total"] = intval($countStmt->fetchColumn());
      $paginacion["offset"] = max(0, intval($offset));
    }

    $sql = "SELECT p.id_producto_erp, s.id_sku, p.codigo_producto, s.sku,
        COALESCE(s.nombre, p.nombre) nombre_publico,
        m.nombre marca,
        COALESCE(c.ruta, c.nombre) categoria,
        COALESCE(NULLIF(r.unidad_venta_label, ''), u.abreviatura, u.codigo, '') presentacion_base,
        p.descripcion descripcion_catalogo,
        pr.precio, pr.moneda,
        img.url_imagen,
        COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
        COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
        " . ($tienePublicaciones ? "pub.id_publicacion, pub.estatus_publicacion, pub.slug slug_publicacion, pub.titulo_publico titulo_publico_publicacion, pub.descripcion_publica descripcion_publica_publicacion, pub.presentacion_publica presentacion_publica_publicacion, pub.mascota_especie mascota_especie_publicacion, pub.necesidades_json necesidades_json_publicacion, pub.destacado destacado_publicacion, pub.orden orden_publicacion, pub.permite_cotizacion permite_cotizacion_publicacion, pub.permite_whatsapp permite_whatsapp_publicacion, pub.mostrar_precio mostrar_precio_publicacion, pub.mostrar_disponibilidad mostrar_disponibilidad_publicacion" : "NULL id_publicacion, NULL estatus_publicacion, NULL slug_publicacion, NULL titulo_publico_publicacion, NULL descripcion_publica_publicacion, NULL presentacion_publica_publicacion, NULL mascota_especie_publicacion, NULL necesidades_json_publicacion, NULL destacado_publicacion, NULL orden_publicacion, NULL permite_cotizacion_publicacion, NULL permite_whatsapp_publicacion, NULL mostrar_precio_publicacion, NULL mostrar_disponibilidad_publicacion") . "
      " . $joins . "
      WHERE " . implode(" AND ", $where) . "
      ORDER BY CASE WHEN pr.id_sku_precio IS NOT NULL AND img.url_imagen IS NOT NULL AND pc.id_categoria_erp IS NOT NULL AND COALESCE(r.permite_venta_fraccionaria,0)=0 THEN 0 ELSE 1 END,
        p.nombre, s.sku
      LIMIT " . intval($limite) . " OFFSET " . max(0, intval($offset));
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        p.descripcion descripcion_catalogo,
        pr.precio, pr.moneda,
        img.url_imagen,
        COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
        COALESCE(r.permite_venta_fraccionaria, 0) permite_venta_fraccionaria,
        COALESCE(inv.cantidad_disponible, 0) existencia_disponible,
        " . ($tienePublicaciones ? "pub.id_publicacion, pub.estatus_publicacion, pub.slug slug_publicacion, pub.titulo_publico titulo_publico_publicacion, pub.descripcion_publica descripcion_publica_publicacion, pub.presentacion_publica presentacion_publica_publicacion, pub.mascota_especie mascota_especie_publicacion, pub.necesidades_json necesidades_json_publicacion, pub.destacado destacado_publicacion, pub.orden orden_publicacion, pub.permite_cotizacion permite_cotizacion_publicacion, pub.permite_whatsapp permite_whatsapp_publicacion, pub.mostrar_precio mostrar_precio_publicacion, pub.mostrar_disponibilidad mostrar_disponibilidad_publicacion" : "NULL id_publicacion, NULL estatus_publicacion, NULL slug_publicacion, NULL titulo_publico_publicacion, NULL descripcion_publica_publicacion, NULL presentacion_publica_publicacion, NULL mascota_especie_publicacion, NULL necesidades_json_publicacion, NULL destacado_publicacion, NULL orden_publicacion, NULL permite_cotizacion_publicacion, NULL permite_whatsapp_publicacion, NULL mostrar_precio_publicacion, NULL mostrar_disponibilidad_publicacion") . "
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

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: reutilizar la descripcion existente del Catalogo ERP como texto temporal de ecommerce.
   * Impacto: evita fichas publicas vacias mientras se redacta curaduria especifica para ecommerce.
   */
  private function descripcionCatalogoParaEcommerce($fila) {
    return trim((string) $this->valor($fila, "descripcion_catalogo", $this->valor($fila, "descripcion", "")));
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
      "pez" => array("pez", "peces", "acuario", "filtro", "filtracion", "oxigenacion", "actinia"),
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
      "habitat" => array("habitat", "cama", "jaula", "casa", "pecera", "acuario", "filtro", "filtracion", "oxigenacion", "iluminacion", "lampara", "actinia", "cascada", "canastilla"),
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
        s.sku, COALESCE(s.nombre, p.nombre) nombre_sku, p.nombre nombre_producto, p.descripcion descripcion_catalogo,
        m.id_marca_erp, m.nombre marca, pc.id_categoria_erp, c.nombre categoria_nombre, COALESCE(c.ruta, c.nombre) categoria,
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
      WHERE " . implode(" AND ", $where) . "
        AND COALESCE(r.permite_venta_fraccionaria, 0)=0";
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
      "marca_obj" => array(
        "id" => intval($this->valor($fila, "id_marca_erp", 0)),
        "nombre" => $this->valor($fila, "marca", ""),
        "slug" => $this->slugificar($this->valor($fila, "marca", ""))
      ),
      "categoria_obj" => array(
        "id" => intval($this->valor($fila, "id_categoria_erp", 0)),
        "nombre" => $this->valor($fila, "categoria_nombre", ""),
        "nombre_completo" => $this->valor($fila, "categoria", ""),
        "slug" => $this->slugificar($this->valor($fila, "categoria_nombre", $this->valor($fila, "categoria", ""))),
        "path_slug" => implode("/", array_map(array($this, "slugificar"), $this->partesRutaCategoriaPublica($this->valor($fila, "categoria", ""))))
      ),
      "presentacion" => $this->presentacionPublicaSalida($fila),
      "descripcion" => trim((string) $fila["descripcion_publica"]) !== "" ? $fila["descripcion_publica"] : $this->descripcionCatalogoParaEcommerce($fila),
      "imagen" => $fila["url_imagen"],
      "precio" => $mostrarPrecio ? floatval($fila["precio"]) : null,
      "moneda" => $mostrarPrecio ? ($fila["moneda"] ?: "MXN") : null,
      "disponibilidad" => $mostrarDisponibilidad ? $this->disponibilidadPublicaSugerida($fila) : "consultar_disponibilidad",
      "mascota_especie" => $this->mascotaPrincipalPublicacion($fila["mascota_especie"]),
      "mascotas" => $this->decodificarMascotasPublicacion($fila["mascota_especie"]),
      "necesidades" => $this->decodificarJsonLista($fila["necesidades_json"]),
      "permite_cotizacion" => intval($fila["permite_cotizacion"]) === 1,
      "permite_whatsapp" => intval($fila["permite_whatsapp"]) === 1
    );
  }

  private function presentacionPublicaSalida($fila) {
    $presentacion = trim((string) $this->valor($fila, "presentacion_publica", ""));
    if ($presentacion !== "" && !in_array(strtolower($presentacion), array("g", "gr", "kg", "ml", "l", "lt"), true)) {
      return $presentacion;
    }
    $texto = trim((string) $this->valor($fila, "titulo_publico", ""));
    if ($texto === "") {
      $texto = trim((string) $this->valor($fila, "nombre_sku", $this->valor($fila, "nombre_publico", "")));
    }
    if (preg_match('/(\d+(?:[\\.,]\d+)?)\s*(kg|kilo|kilos|gr|g|gramo|gramos|ml|l|lt|litro|litros|pza|pzas|pieza|piezas)\b/i', $texto, $m)) {
      $cantidad = str_replace(",", ".", $m[1]);
      $unidad = strtolower($m[2]);
      if (in_array($unidad, array("g", "gr", "gramo", "gramos"), true)) { $unidad = "gr"; }
      if (in_array($unidad, array("kilo", "kilos"), true)) { $unidad = "kg"; }
      if (in_array($unidad, array("l", "lt", "litro", "litros"), true)) { $unidad = "lt"; }
      if (in_array($unidad, array("pza", "pzas", "pieza", "piezas"), true)) { $unidad = "pzas"; }
      return trim($cantidad . " " . $unidad);
    }
    return $presentacion;
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

  private function variantesProductoPublico($db, $fila, $limite = 12) {
    $where = array(
      "pub.estatus_publicacion='publicado'",
      "p.estatus='activo'",
      "s.estatus='activo'",
      "pub.id_producto_erp=:producto",
      "pub.id_publicacion<>:publicacion"
    );
    $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " ORDER BY pr.precio ASC, pub.orden ASC, pub.titulo_publico ASC LIMIT " . intval($limite));
    $stmt->execute(array(
      ":producto" => intval($this->valor($fila, "id_producto_erp", 0)),
      ":publicacion" => intval($this->valor($fila, "id_publicacion", 0))
    ));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $variante) {
      $items[] = $this->formatearPublicacion($variante);
    }
    return $items;
  }

  private function relacionadosProductoPublico($db, $fila, $limite = 8) {
    $condiciones = array();
    $params = array(
      ":publicacion" => intval($this->valor($fila, "id_publicacion", 0)),
      ":producto" => intval($this->valor($fila, "id_producto_erp", 0))
    );
    $categoria = intval($this->valor($fila, "id_categoria_erp", 0));
    if ($categoria > 0) {
      $condiciones[] = "pc.id_categoria_erp=:categoria";
      $params[":categoria"] = $categoria;
    }
    $mascotas = $this->decodificarMascotasPublicacion($this->valor($fila, "mascota_especie", ""));
    foreach (array_slice($mascotas, 0, 4) as $i => $mascota) {
      $claveMascota = ":mascota" . $i;
      $claveMascotaSet = ":mascota_set" . $i;
      $condiciones[] = "(pub.mascota_especie=" . $claveMascota . " OR FIND_IN_SET(" . $claveMascotaSet . ", REPLACE(pub.mascota_especie, ' ', ''))>0)";
      $params[$claveMascota] = $mascota;
      $params[$claveMascotaSet] = $mascota;
    }
    $necesidades = array_slice($this->decodificarJsonLista($this->valor($fila, "necesidades_json", "")), 0, 4);
    foreach ($necesidades as $i => $necesidad) {
      $clave = ":necesidad" . $i;
      $condiciones[] = "pub.necesidades_json LIKE " . $clave;
      $params[$clave] = "%\"" . $necesidad . "\"%";
    }
    if (empty($condiciones)) {
      return array();
    }

    $where = array(
      "pub.estatus_publicacion='publicado'",
      "p.estatus='activo'",
      "s.estatus='activo'",
      "pub.id_publicacion<>:publicacion",
      "pub.id_producto_erp<>:producto",
      "(" . implode(" OR ", $condiciones) . ")"
    );
    $ordenScore = array();
    if ($categoria > 0) {
      $ordenScore[] = "CASE WHEN pc.id_categoria_erp=:categoria THEN 4 ELSE 0 END";
    }
    foreach (array_slice($mascotas, 0, 4) as $i => $mascota) {
      $ordenScore[] = "CASE WHEN pub.mascota_especie=:mascota" . $i . " OR FIND_IN_SET(:mascota_set" . $i . ", REPLACE(pub.mascota_especie, ' ', ''))>0 THEN 3 ELSE 0 END";
    }
    foreach ($necesidades as $i => $necesidad) {
      $ordenScore[] = "CASE WHEN pub.necesidades_json LIKE :necesidad" . $i . " THEN 2 ELSE 0 END";
    }
    $score = empty($ordenScore) ? "0" : implode(" + ", $ordenScore);
    $stmt = $db->prepare($this->sqlPublicacionesBase($where) . " ORDER BY (" . $score . ") DESC, pub.destacado DESC, pub.orden ASC, pub.titulo_publico ASC LIMIT " . intval($limite));
    $stmt->execute($params);
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $relacionado) {
      $items[] = $this->formatearPublicacion($relacionado);
    }
    return $items;
  }

  private function breadcrumbsProductoPublico($item) {
    $breadcrumbs = array(
      array("etiqueta" => "Inicio", "path" => "/"),
      array("etiqueta" => "Catalogo", "path" => "/catalogo")
    );
    $categoria = trim((string) $this->valor($item, "categoria", ""));
    if ($categoria !== "") {
      $partes = array_values(array_filter(array_map("trim", explode("/", $categoria))));
      $acumulado = "";
      foreach ($partes as $parte) {
        $acumulado = trim($acumulado . "/" . $this->slugificar($parte), "/");
        $breadcrumbs[] = array("etiqueta" => $parte, "path" => "/catalogo/categoria/" . $acumulado);
      }
    }
    $breadcrumbs[] = array("etiqueta" => $this->valor($item, "nombre", "Producto"), "path" => "/producto/" . $this->valor($item, "slug", ""));
    return $breadcrumbs;
  }

  private function seoProductoPublico($item) {
    $nombre = trim((string) $this->valor($item, "nombre", "Producto Artiani"));
    $descripcion = trim((string) $this->valor($item, "descripcion", ""));
    if ($descripcion === "") {
      $partes = array_filter(array(
        $nombre,
        $this->valor($item, "marca", ""),
        $this->valor($item, "presentacion", ""),
        $this->valor($item, "categoria", "")
      ));
      $descripcion = implode(" | ", $partes);
    }
    $precio = $this->valor($item, "precio", null);
    return array(
      "title" => substr($nombre . " | Artiani", 0, 90),
      "description" => substr($descripcion, 0, 160),
      "canonical_path" => "/producto/" . $this->valor($item, "slug", ""),
      "image" => $this->valor($item, "imagen", null),
      "json_ld" => array(
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $nombre,
        "sku" => $this->valor($item, "sku", ""),
        "brand" => $this->valor($item, "marca", ""),
        "image" => $this->valor($item, "imagen", null),
        "description" => substr($descripcion, 0, 300),
        "offers" => array(
          "@type" => "Offer",
          "priceCurrency" => $this->valor($item, "moneda", "MXN"),
          "price" => $precio,
          "availability" => $this->schemaOrgDisponibilidad($this->valor($item, "disponibilidad", "consultar_disponibilidad"))
        )
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: entregar metadatos UI de Fase 2 para ficha de producto sin que frontend hardcodee reglas.
   * Impacto: Ecommerce publico; estabiliza CTA, secciones, SEO y navegacion de detalle.
   * Contrato: no consulta BD, no escribe datos, no muestra stock exacto y mantiene guardrail no_granel.
   */
  private function fase2ProductoPublico($item, $variantes, $relacionados, $breadcrumbs, $seo, $acciones) {
    $slug = (string) $this->valor($item, "slug", "");
    $disponibilidad = (string) $this->valor($item, "disponibilidad", "consultar_disponibilidad");
    $puedeCotizar = !empty($acciones["puede_cotizar"]);
    $frontendDisponibilidad = $this->frontendDisponibilidadPublica($disponibilidad, $puedeCotizar);

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "url_actual" => "/ecommercePublico/producto/" . $slug,
      "links" => array(
        "self" => "/ecommercePublico/producto/" . $slug,
        "catalogo" => "/ecommercePublico/catalogo",
        "disponibilidad" => "/ecommercePublico/disponibilidad?slug=" . rawurlencode($slug),
        "cotizacion_dryrun" => "/ecommercePublico/cotizacion_dryrun",
        "cotizacion_preflight" => "/ecommercePublico/cotizacion_preflight"
      ),
      "resumen_ui" => array(
        "titulo" => $this->valor($item, "nombre", ""),
        "subtitulo" => implode(" | ", array_filter(array(
          $this->valor($item, "marca", ""),
          $this->valor($item, "presentacion", ""),
          $this->valor($item, "categoria", "")
        ))),
        "mostrar_precio" => !empty($acciones["mostrar_precio"]),
        "mostrar_variantes" => count($variantes) > 1,
        "mostrar_relacionados" => count($relacionados) > 0,
        "mostrar_breadcrumbs" => count($breadcrumbs) > 1,
        "variantes_total" => count($variantes),
        "relacionados_total" => count($relacionados)
      ),
      "cta" => array(
        "principal" => array(
          "label" => $puedeCotizar ? "Agregar a cotizacion" : "Consultar disponibilidad",
          "endpoint" => "/ecommercePublico/cotizacion_dryrun",
          "habilitado" => $puedeCotizar
        ),
        "secundaria" => array(
          "label" => "Consultar por WhatsApp",
          "endpoint_siguiente" => "/ecommercePublico/cotizacion_preflight",
          "habilitado" => !empty($acciones["puede_whatsapp"])
        )
      ),
      "disponibilidad_ui" => $frontendDisponibilidad,
      "seo" => array(
        "title" => $this->valor($seo, "title", ""),
        "description" => $this->valor($seo, "description", ""),
        "canonical_path" => $this->valor($seo, "canonical_path", "")
      ),
      "guardrails" => array(
        "solo_publicado" => true,
        "solo_relacionados_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "precio_es_estimado" => true,
        "cotizacion_requiere_dryrun" => true,
        "no_checkout" => true
      )
    );
  }

  private function schemaOrgDisponibilidad($disponibilidad) {
    if ($disponibilidad === "disponible") {
      return "https://schema.org/InStock";
    }
    if ($disponibilidad === "pocas_piezas") {
      return "https://schema.org/LimitedAvailability";
    }
    if ($disponibilidad === "agotado") {
      return "https://schema.org/OutOfStock";
    }
    return "https://schema.org/PreOrder";
  }

  private function rutasSeoPublicas($sitemap) {
    $rutas = array();
    foreach ($this->valor($sitemap, "rutas_estaticas", array()) as $ruta) {
      $rutas[] = array(
        "tipo" => "estatica",
        "path" => $this->valor($ruta, "path", "/"),
        "title" => $this->valor($ruta, "title", ""),
        "priority" => $this->valor($ruta, "priority", "0.5"),
        "changefreq" => $this->valor($ruta, "changefreq", "weekly")
      );
    }
    foreach ($this->valor($sitemap, "productos", array()) as $producto) {
      $rutas[] = array(
        "tipo" => "producto",
        "path" => $this->valor($producto, "path", ""),
        "title" => $this->valor($producto, "title", ""),
        "priority" => $this->valor($producto, "priority", "0.8"),
        "changefreq" => $this->valor($producto, "changefreq", "daily")
      );
    }
    foreach ($this->valor($sitemap, "filtros", array()) as $grupo => $items) {
      foreach ((array) $items as $item) {
        $rutas[] = array(
          "tipo" => "filtro_" . $grupo,
          "path" => $this->valor($item, "path", ""),
          "title" => $this->valor($item, "title", $this->valor($item, "valor", "")),
          "priority" => $this->valor($item, "priority", "0.5"),
          "changefreq" => $this->valor($item, "changefreq", "weekly")
        );
      }
    }
    $limpias = array();
    $vistos = array();
    foreach ($rutas as $ruta) {
      $path = trim((string) $this->valor($ruta, "path", ""));
      if ($path === "" || isset($vistos[$path])) {
        continue;
      }
      $vistos[$path] = true;
      $limpias[] = $ruta;
    }
    return $limpias;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: entregar metadatos de Fase 2 para sitemap, robots, canonical y rutas SEO guiadas por API.
   * Impacto: Frontend ecommerce; evita hardcodear generacion SEO y conserva rutas derivadas del catalogo ERP.
   * Contrato: no consulta BD, no escribe archivos y mantiene guardrail no_granel.
   */
  private function fase2SeoPublico($meta, $sitemap, $rutas, $urlSitio) {
    $conteosPorTipo = array();
    foreach ((array) $rutas as $ruta) {
      $tipo = (string) $this->valor($ruta, "tipo", "otro");
      if (!isset($conteosPorTipo[$tipo])) {
        $conteosPorTipo[$tipo] = 0;
      }
      $conteosPorTipo[$tipo]++;
    }

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "base_url" => $urlSitio,
      "archivos_sugeridos" => array(
        "sitemap_xml" => "/sitemap.xml",
        "robots_txt" => "/robots.txt",
        "metadata_runtime" => "/ecommercePublico/seo"
      ),
      "rutas" => array(
        "total" => count($rutas),
        "por_tipo" => $conteosPorTipo,
        "primeras" => array_slice($rutas, 0, 12)
      ),
      "canonical" => array(
        "base" => $this->valor($meta, "canonical_base", ""),
        "catalogo" => $this->canonicalSeoPublico($urlSitio, "/ecommercePublico/catalogo"),
        "producto_pattern" => $this->canonicalSeoPublico($urlSitio, "/ecommercePublico/producto/{slug}"),
        "filtro_pattern" => $this->canonicalSeoPublico($urlSitio, "/ecommercePublico/catalogo?{parametro}={valor}")
      ),
      "json_ld" => array(
        "organization_type" => "PetStore",
        "product_type" => "Product",
        "usar_item_producto_para_json_ld" => true,
        "mapear_disponibilidad_schema_org" => true
      ),
      "ui" => array(
        "frontend_genera_title_description" => true,
        "frontend_genera_canonical" => true,
        "frontend_genera_open_graph" => true,
        "frontend_genera_json_ld" => true,
        "noindex_si_catalogo_vacio" => true
      ),
      "guardrails" => array(
        "frontend_genera_archivos_seo" => true,
        "no_escribe_archivos" => true,
        "solo_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "no_ecom_legacy_fuente" => true
      )
    );
  }

  private function canonicalSeoPublico($urlSitio, $path) {
    $urlSitio = rtrim(trim((string) $urlSitio), "/");
    $path = trim((string) $path);
    if ($path === "") {
      return $urlSitio;
    }
    if ($urlSitio === "") {
      return $path;
    }
    return $urlSitio . (strpos($path, "/") === 0 ? $path : "/" . $path);
  }

  private function sitemapXmlSugerido($rutas, $urlSitio) {
    $urlSitio = rtrim(trim((string) $urlSitio), "/");
    if ($urlSitio === "" || empty($rutas)) {
      return "";
    }
    $lineas = array('<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
    foreach ($rutas as $ruta) {
      $path = trim((string) $this->valor($ruta, "path", ""));
      if ($path === "") {
        continue;
      }
      $lineas[] = "  <url>";
      $lineas[] = "    <loc>" . htmlspecialchars($urlSitio . $path, ENT_XML1, "UTF-8") . "</loc>";
      $lineas[] = "    <changefreq>" . htmlspecialchars($this->valor($ruta, "changefreq", "weekly"), ENT_XML1, "UTF-8") . "</changefreq>";
      $lineas[] = "    <priority>" . htmlspecialchars($this->valor($ruta, "priority", "0.5"), ENT_XML1, "UTF-8") . "</priority>";
      $lineas[] = "  </url>";
    }
    $lineas[] = "</urlset>";
    return implode("\n", $lineas);
  }

  private function agregarSeccionPublica(&$secciones, $definicion, $limite, $incluirVacias) {
    $params = $this->valor($definicion, "params", array());
    $params["limite"] = $limite;
    $catalogo = $this->catalogoPublico($params);
    $items = $this->valor($catalogo, array("depurar", "items"), array());
    $total = intval($this->valor($catalogo, array("depurar", "paginacion", "total"), 0));
    if (!$incluirVacias && empty($items)) {
      return;
    }
    $secciones[] = array(
      "codigo" => $this->valor($definicion, "codigo", ""),
      "titulo" => $this->valor($definicion, "titulo", ""),
      "tipo" => $this->valor($definicion, "tipo", "catalogo"),
      "total" => $total,
      "items" => $items,
      "params_catalogo" => $params,
      "url_catalogo" => "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($params, 1, $limite),
      "frontend" => $this->frontendSeccionPublica($definicion, $items, $total, $limite),
      "guardrails" => array(
        "solo_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "precio_es_estimado" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: entregar metadatos de Fase 2 para secciones de home y colecciones sin hardcodear layout.
   * Impacto: Ecommerce publico; estabiliza bloques de descubrimiento, links, vacios y CTA por seccion.
   * Contrato: no consulta BD, no escribe datos y mantiene guardrail no_granel.
   */
  private function fase2SeccionesPublicas($secciones, $limite) {
    $orden = array();
    $links = array();
    foreach ((array) $secciones as $seccion) {
      $codigo = (string) $this->valor($seccion, "codigo", "");
      if ($codigo === "") {
        continue;
      }
      $orden[] = $codigo;
      $links[$codigo] = $this->valor($seccion, "url_catalogo", "/ecommercePublico/catalogo");
    }

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "limite_por_seccion" => intval($limite),
      "secciones_total" => count($secciones),
      "orden_render" => $orden,
      "links_catalogo" => $links,
      "ui" => array(
        "mostrar_titulo_seccion" => true,
        "mostrar_ver_todo" => true,
        "mostrar_estado_vacio_por_seccion" => true,
        "usar_items_como_cards_producto" => true,
        "max_items_mobile" => min(6, intval($limite)),
        "max_items_desktop" => intval($limite)
      ),
      "guardrails" => array(
        "solo_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "no_ecom_legacy_fuente" => true,
        "no_descuenta_inventario" => true
      )
    );
  }

  private function fase2BootstrapPublico($ready, $limiteSecciones, $estado, $filtros, $navegacion, $secciones) {
    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "ready_frontend" => (bool) $ready,
      "primer_render" => array(
        "configuracion_inicial" => "/ecommercePublico/configuracion_inicial",
        "estado" => "/ecommercePublico/estado",
        "configuracion" => "/ecommercePublico/configuracion",
        "catalogo_manifest" => "/ecommercePublico/catalogo_manifest",
        "filtros" => "/ecommercePublico/filtros",
        "navegacion" => "/ecommercePublico/navegacion",
        "secciones" => "/ecommercePublico/secciones",
        "catalogo" => "/ecommercePublico/catalogo",
        "producto" => "/ecommercePublico/producto/{slug}"
      ),
      "resumen" => array(
        "api_ready" => (bool) $this->valor($estado, array("depurar", "ready"), false),
        "filtros_fase_2" => $this->valor($filtros, array("depurar", "fase_2", "fase"), "") === "fase_2_api_catalogo_robusta",
        "navegacion_fase_2" => $this->valor($navegacion, array("depurar", "fase_2", "fase"), "") === "fase_2_api_catalogo_robusta",
        "secciones_fase_2" => $this->valor($secciones, array("depurar", "fase_2", "fase"), "") === "fase_2_api_catalogo_robusta",
        "secciones_total" => count($this->valor($secciones, array("depurar", "secciones"), array())),
        "limite_secciones" => intval($limiteSecciones)
      ),
      "ui" => array(
        "puede_renderizar_home" => (bool) $ready,
        "usar_configuracion_inicial_para_home" => true,
        "bootstrap_es_alias_legacy" => true,
        "usar_catalogo_para_paginacion" => true,
        "usar_manifest_para_descubrir_contrato" => true,
        "mostrar_estado_carga_si_ready_false" => true
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "no_checkout" => true,
        "no_registra_cotizacion" => true
      )
    );
  }

  private function frontendSeccionPublica($definicion, $items, $total, $limite) {
    $codigo = (string) $this->valor($definicion, "codigo", "");
    $titulo = (string) $this->valor($definicion, "titulo", "");
    return array(
      "codigo" => $codigo,
      "titulo" => $titulo,
      "hay_resultados" => intval($total) > 0,
      "items_en_bloque" => count($items),
      "mostrar_ver_todo" => intval($total) > intval($limite),
      "cta_ver_todo" => array(
        "label" => "Ver todo",
        "url" => "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($this->valor($definicion, "params", array()), 1, $limite)
      ),
      "estado_vacio" => array(
        "mostrar" => intval($total) <= 0,
        "titulo" => "Sin productos para " . strtolower($titulo),
        "accion_principal" => array("label" => "Ver catalogo", "url" => "/ecommercePublico/catalogo")
      )
    );
  }

  private function agregarFiltroDisponibilidadPublica(&$where, $disponibilidad) {
    if ($disponibilidad === "disponible") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)>3";
    } elseif ($disponibilidad === "pocas_piezas") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)>0";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)<=3";
    } elseif ($disponibilidad === "agotado") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=1";
      $where[] = "COALESCE(inv.cantidad_disponible, 0)<=0";
    } elseif ($disponibilidad === "consultar_disponibilidad") {
      $where[] = "COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END)=0";
    }
  }

  private function categoriaIdsFiltroPublico($db, $categoriaId, $categoriaSlug, $incluirHijos) {
    $categoriaId = intval($categoriaId);
    $categoriaSlug = $this->limpiarFiltroPublico($categoriaSlug);
    if ($categoriaId <= 0 && $categoriaSlug === "") {
      return array();
    }
    $items = $this->categoriasPublicasItems($db);
    $objetivo = null;
    foreach ($items as $item) {
      if ($categoriaId > 0 && intval($item["id"]) === $categoriaId) {
        $objetivo = $item;
        break;
      }
      if ($categoriaSlug !== "" && $item["slug_publico"] === $categoriaSlug) {
        $objetivo = $item;
        break;
      }
    }
    if (!$objetivo) {
      return array();
    }
    $ids = array(intval($objetivo["id"]));
    if ($incluirHijos) {
      $ids = array_merge($ids, $this->categoriaDescendientesPublicos($items, intval($objetivo["id"])));
    }
    return array_values(array_unique(array_filter(array_map("intval", $ids))));
  }

  private function marcaIdFiltroPublico($db, $marcaId, $marcaSlug) {
    $marcaId = intval($marcaId);
    $marcaSlug = $this->limpiarFiltroPublico($marcaSlug);
    if ($marcaId <= 0 && $marcaSlug === "") {
      return 0;
    }
    foreach ($this->marcasPublicasItems($db) as $item) {
      if ($marcaId > 0 && intval($item["id"]) === $marcaId) {
        return intval($item["id"]);
      }
      if ($marcaSlug !== "" && $item["slug_publico"] === $marcaSlug) {
        return intval($item["id"]);
      }
    }
    return 0;
  }

  private function normalizarFiltrosCatalogoPublico($db, $opciones) {
    $marcaRaw = $this->valor($opciones, "marca_id", $this->valor($opciones, "marca", 0));
    $marcaId = intval($marcaRaw);
    $marcaSlug = $this->limpiarFiltroPublico($this->valor($opciones, "marca_slug", is_numeric($marcaRaw) ? "" : $marcaRaw));
    $marcaFiltro = $this->marcaIdFiltroPublico($db, $marcaId, $marcaSlug);
    $categoriaId = intval($this->valor($opciones, "categoria_id", $this->valor($opciones, "categoria", 0)));
    $categoriaSlug = $this->limpiarFiltroPublico($this->valor($opciones, "categoria_slug", ""));
    return array(
      "q" => trim((string) $this->valor($opciones, "q", "")),
      "mascota" => $this->limpiarFiltroPublico($this->valor($opciones, "mascota", "")),
      "necesidad" => $this->limpiarFiltroPublico($this->valor($opciones, "necesidad", "")),
      "marca_id" => $marcaFiltro > 0 ? $marcaFiltro : $marcaId,
      "marca_slug" => $marcaSlug,
      "categoria_id" => $categoriaId,
      "categoria_slug" => $categoriaSlug,
      "incluir_hijos" => intval($this->valor($opciones, "incluir_hijos", 0)) === 1 ? 1 : 0,
      "disponibilidad" => trim((string) $this->valor($opciones, "disponibilidad", "")),
      "destacado" => intval($this->valor($opciones, "destacado", 0)) === 1 ? 1 : 0,
      "orden" => $this->ordenCatalogoPublicoNormalizado($this->valor($opciones, "orden", "relevancia"))
    );
  }

  private function marcasPublicasItems($db) {
    $items = array();
    $stmt = $db->query("SELECT m.id_marca_erp id, m.nombre nombre, COUNT(DISTINCT pub.id_publicacion) total
      FROM erp_ecommerce_publicaciones pub
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp AND p.id_producto_erp=s.id_producto_erp
      INNER JOIN erp_catalogo_marcas m ON m.id_marca_erp=p.id_marca_erp
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE pub.estatus_publicacion='publicado'
        AND p.estatus='activo'
        AND s.estatus='activo'
        AND TRIM(COALESCE(m.nombre,''))<>''
        AND COALESCE(r.permite_venta_fraccionaria, 0)=0
      GROUP BY m.id_marca_erp, m.nombre
      ORDER BY m.nombre");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $nombre = trim((string) $this->valor($fila, "nombre", ""));
      $slug = $this->slugPublicoUnico($this->slugificar($nombre), intval($fila["id"]), $items);
      $items[intval($fila["id"])] = array(
        "id" => intval($fila["id"]),
        "nombre" => $nombre,
        "slug_publico" => $slug,
        "total_productos" => intval($fila["total"]),
        "logo" => null,
        "imagen_banner" => null,
        "visible_frontend" => true,
        "orden" => 100,
        "descripcion_corta" => "",
        "seo_title" => $nombre . " | Artiani",
        "seo_description" => "Encuentra productos " . strtolower($nombre) . " en Artiani.",
        "url" => "/marca/" . $slug,
        "api_catalogo" => "/ecommercePublico/catalogo?marca_slug=" . rawurlencode($slug) . "&limite=24"
      );
    }
    return $items;
  }

  private function rangoPreciosCatalogoPublico($catalogo) {
    $items = $this->valor($catalogo, array("depurar", "items"), array());
    $min = null;
    $max = null;
    foreach ($items as $item) {
      if ($this->valor($item, "precio", null) === null) {
        continue;
      }
      $precio = floatval($item["precio"]);
      $min = $min === null ? $precio : min($min, $precio);
      $max = $max === null ? $precio : max($max, $precio);
    }
    return array("min" => $min, "max" => $max);
  }

  private function ordenamientosCatalogoPublico() {
    return array(
      array("value" => "relevancia", "label" => "Relevancia"),
      array("value" => "nombre", "label" => "Nombre"),
      array("value" => "precio_asc", "label" => "Precio menor a mayor"),
      array("value" => "precio_desc", "label" => "Precio mayor a menor"),
      array("value" => "recientes", "label" => "Recientes")
    );
  }

  private function categoriasPublicasItems($db) {
    $parentCol = $this->primeraColumnaExistente($db, "erp_catalogo_categorias", array("parent_id", "id_categoria_padre", "id_padre", "categoria_padre_id"));
    $nivelCol = $this->primeraColumnaExistente($db, "erp_catalogo_categorias", array("nivel", "profundidad"));
    $ordenCol = $this->primeraColumnaExistente($db, "erp_catalogo_categorias", array("orden", "orden_visual", "posicion"));
    $descripcionCol = $this->primeraColumnaExistente($db, "erp_catalogo_categorias", array("descripcion", "descripcion_corta"));
    $select = array(
      "c.id_categoria_erp id",
      "c.nombre nombre",
      "COALESCE(c.ruta, c.nombre) ruta",
      ($parentCol ? "c.`" . $parentCol . "` parent_id_real" : "NULL parent_id_real"),
      ($nivelCol ? "c.`" . $nivelCol . "` nivel_real" : "NULL nivel_real"),
      ($ordenCol ? "c.`" . $ordenCol . "` orden_real" : "NULL orden_real"),
      ($descripcionCol ? "c.`" . $descripcionCol . "` descripcion_real" : "NULL descripcion_real"),
      "COUNT(DISTINCT pub.id_publicacion) total_directo"
    );
    $sql = "SELECT " . implode(", ", $select) . "
      FROM erp_ecommerce_publicaciones pub
      INNER JOIN erp_catalogo_skus s ON s.id_sku=pub.id_sku
      INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=pub.id_producto_erp
      INNER JOIN erp_catalogo_producto_categorias pc ON pc.id_producto_erp=p.id_producto_erp AND pc.es_principal=1
      INNER JOIN erp_catalogo_categorias c ON c.id_categoria_erp=pc.id_categoria_erp
      LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
      WHERE pub.estatus_publicacion='publicado'
        AND p.estatus='activo'
        AND s.estatus='activo'
        AND COALESCE(r.permite_venta_fraccionaria, 0)=0
      GROUP BY c.id_categoria_erp, c.nombre, c.ruta" .
        ($parentCol ? ", c.`" . $parentCol . "`" : "") .
        ($nivelCol ? ", c.`" . $nivelCol . "`" : "") .
        ($ordenCol ? ", c.`" . $ordenCol . "`" : "") .
        ($descripcionCol ? ", c.`" . $descripcionCol . "`" : "") . "
      ORDER BY COALESCE(c.ruta, c.nombre), c.nombre";
    $filasDirectas = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $totalesDirectos = array();
    $rutasConProductos = array();
    foreach ($filasDirectas as $filaDirecta) {
      $totalesDirectos[intval($filaDirecta["id"])] = intval($filaDirecta["total_directo"]);
      $rutasConProductos[] = implode(" / ", $this->partesRutaCategoriaPublica($this->valor($filaDirecta, "ruta", $this->valor($filaDirecta, "nombre", ""))));
    }

    $selectCategorias = array(
      "c.id_categoria_erp id",
      "c.nombre nombre",
      "COALESCE(c.ruta, c.nombre) ruta",
      ($parentCol ? "c.`" . $parentCol . "` parent_id_real" : "NULL parent_id_real"),
      ($nivelCol ? "c.`" . $nivelCol . "` nivel_real" : "NULL nivel_real"),
      ($ordenCol ? "c.`" . $ordenCol . "` orden_real" : "NULL orden_real"),
      ($descripcionCol ? "c.`" . $descripcionCol . "` descripcion_real" : "NULL descripcion_real"),
      "0 total_directo"
    );
    $whereCategorias = $this->columnaExisteLocal($db, "erp_catalogo_categorias", "estatus")
      ? "WHERE COALESCE(c.estatus, '') NOT IN ('inactivo','inactiva','eliminado','eliminada','oculto','oculta')"
      : "";
    $filas = $db->query("SELECT " . implode(", ", $selectCategorias) . "
      FROM erp_catalogo_categorias c
      " . $whereCategorias . "
      ORDER BY COALESCE(c.ruta, c.nombre), c.nombre")->fetchAll(PDO::FETCH_ASSOC);
    $items = array();
    $porRutaSlug = array();
    foreach ($filas as $fila) {
      $ruta = trim((string) $this->valor($fila, "ruta", $this->valor($fila, "nombre", "")));
      $partes = $this->partesRutaCategoriaPublica($ruta);
      $nombre = trim((string) $this->valor($fila, "nombre", ""));
      if ($nombre === "" && !empty($partes)) {
        $nombre = end($partes);
      }
      $rutaNormalizada = implode(" / ", $partes ?: array($nombre));
      $totalDirecto = isset($totalesDirectos[intval($fila["id"])]) ? intval($totalesDirectos[intval($fila["id"])]) : 0;
      if ($totalDirecto <= 0 && !$this->categoriaEsAncestroRutaPublica($rutaNormalizada, $rutasConProductos)) {
        continue;
      }
      $slugBase = $this->slugificar($nombre);
      $slug = $this->slugPublicoUnico($slugBase, intval($fila["id"]), $items);
      $pathSlug = implode("/", array_map(array($this, "slugificar"), $partes ?: array($nombre)));
      $nivel = $this->valor($fila, "nivel_real", null);
      $nivel = $nivel !== null && $nivel !== "" ? intval($nivel) : max(0, count($partes) - 1);
      $item = array(
        "id" => intval($fila["id"]),
        "parent_id" => $this->valor($fila, "parent_id_real", null) !== null && $this->valor($fila, "parent_id_real", "") !== "" ? intval($fila["parent_id_real"]) : null,
        "nombre" => $nombre,
        "nombre_completo" => $rutaNormalizada,
        "slug_publico" => $slug,
        "path_slug" => $pathSlug,
        "nivel" => $nivel,
        "orden" => $this->valor($fila, "orden_real", null) !== null && $this->valor($fila, "orden_real", "") !== "" ? intval($fila["orden_real"]) : 100,
        "total_productos" => $totalDirecto,
        "total_productos_directos" => $totalDirecto,
        "visible_frontend" => true,
        "imagen_menu" => null,
        "imagen_card" => null,
        "imagen_banner" => null,
        "descripcion_corta" => trim((string) $this->valor($fila, "descripcion_real", "")),
        "seo_title" => $nombre . " | Artiani",
        "seo_description" => "Encuentra " . strtolower($nombre) . " en Artiani.",
        "url" => "/categoria/" . $slug,
        "api_catalogo" => "/ecommercePublico/catalogo?categoria_slug=" . rawurlencode($slug) . "&incluir_hijos=1&limite=24",
        "_ruta_slug" => $this->slugificar(implode(" ", $partes ?: array($nombre))),
        "_parent_resuelto" => false
      );
      $items[$item["id"]] = $item;
      $porRutaSlug[$item["_ruta_slug"]] = $item["id"];
    }

    foreach ($items as $id => $item) {
      if ($item["parent_id"] !== null && isset($items[$item["parent_id"]])) {
        $items[$id]["_parent_resuelto"] = true;
        continue;
      }
      $partes = explode(" / ", $item["nombre_completo"]);
      array_pop($partes);
      if (!empty($partes)) {
        $parentKey = $this->slugificar(implode(" ", $partes));
        if (isset($porRutaSlug[$parentKey])) {
          $items[$id]["parent_id"] = $porRutaSlug[$parentKey];
          $items[$id]["_parent_resuelto"] = true;
        }
      }
    }

    foreach (array_keys($items) as $id) {
      $items[$id]["total_productos"] = $items[$id]["total_productos_directos"] + count($this->categoriaDescendientesPublicos($items, $id, true));
      unset($items[$id]["_ruta_slug"], $items[$id]["_parent_resuelto"]);
    }
    return $items;
  }

  private function categoriasPublicasArbol($items) {
    $nodos = array();
    foreach ($items as $id => $item) {
      $item["children"] = array();
      $nodos[$id] = $item;
    }
    $raices = array();
    foreach ($nodos as $id => &$nodo) {
      $parent = $nodo["parent_id"];
      if ($parent !== null && isset($nodos[$parent])) {
        $nodos[$parent]["children"][] = &$nodo;
      } else {
        $raices[] = &$nodo;
      }
    }
    unset($nodo);
    $ordenar = function (&$lista) use (&$ordenar) {
      usort($lista, function ($a, $b) {
        if (intval($a["orden"]) === intval($b["orden"])) {
          return strcmp((string) $a["nombre"], (string) $b["nombre"]);
        }
        return intval($a["orden"]) - intval($b["orden"]);
      });
      foreach ($lista as &$item) {
        if (!empty($item["children"])) {
          $ordenar($item["children"]);
        }
      }
      unset($item);
    };
    $ordenar($raices);
    return $raices;
  }

  private function categoriaDescendientesPublicos($items, $idCategoria, $contarProductos = false) {
    $salida = array();
    foreach ($items as $id => $item) {
      if (intval($item["parent_id"]) === intval($idCategoria)) {
        if ($contarProductos) {
          for ($i = 0; $i < intval($item["total_productos_directos"]); $i++) { $salida[] = intval($id); }
        } else {
          $salida[] = intval($id);
        }
        $salida = array_merge($salida, $this->categoriaDescendientesPublicos($items, $id, $contarProductos));
      }
    }
    return $salida;
  }

  private function partesRutaCategoriaPublica($ruta) {
    $partes = preg_split('/[\/>]+/', (string) $ruta);
    $limpias = array();
    foreach ($partes as $parte) {
      $parte = trim($parte);
      if ($parte !== "") { $limpias[] = $parte; }
    }
    return $limpias;
  }

  private function categoriaEsAncestroRutaPublica($ruta, $rutasConProductos) {
    $ruta = trim((string) $ruta);
    if ($ruta === "") {
      return false;
    }
    foreach ((array) $rutasConProductos as $rutaProducto) {
      $rutaProducto = trim((string) $rutaProducto);
      if ($rutaProducto === $ruta || strpos($rutaProducto, $ruta . " / ") === 0) {
        return true;
      }
    }
    return false;
  }

  private function slugPublicoUnico($slugBase, $id, $items) {
    $slugBase = $slugBase !== "" ? $slugBase : "categoria-" . intval($id);
    $slug = $slugBase;
    foreach ($items as $item) {
      if ($item["slug_publico"] === $slug) {
        $slug = $slugBase . "-" . intval($id);
        break;
      }
    }
    return $slug;
  }

  private function primeraColumnaExistente($db, $tabla, $columnas) {
    foreach ((array) $columnas as $columna) {
      if ($this->columnaExisteLocal($db, $tabla, $columna)) {
        return $columna;
      }
    }
    return null;
  }

  private function columnaExisteLocal($db, $tabla, $columna) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $columna)) {
      return false;
    }
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetchColumn();
  }

  private function frontendCatalogoPublico($pagina, $limite, $total, $itemsPagina, $filtrosAplicados) {
    $pagina = max(1, intval($pagina));
    $limite = max(1, intval($limite));
    $total = max(0, intval($total));
    $itemsPagina = max(0, intval($itemsPagina));
    $totalPaginas = $total > 0 ? intval(ceil($total / $limite)) : 0;
    $desde = $itemsPagina > 0 ? (($pagina - 1) * $limite) + 1 : 0;
    $hasta = $itemsPagina > 0 ? min($total, $desde + $itemsPagina - 1) : 0;
    $filtrosActivos = array();
    foreach ($filtrosAplicados as $clave => $valor) {
      if (in_array($clave, array("orden", "limite"), true)) {
        continue;
      }
      if (is_bool($valor) && $valor) {
        $filtrosActivos[] = $clave;
      } elseif (!is_bool($valor) && trim((string) $valor) !== "" && (string) $valor !== "0") {
        $filtrosActivos[] = $clave;
      }
    }

    return array(
      "hay_resultados" => $total > 0,
      "items_en_pagina" => $itemsPagina,
      "total_paginas" => $totalPaginas,
      "pagina_anterior" => $pagina > 1 ? $pagina - 1 : null,
      "pagina_siguiente" => ($totalPaginas > 0 && $pagina < $totalPaginas) ? $pagina + 1 : null,
      "rango_visible" => array(
        "desde" => $desde,
        "hasta" => $hasta,
        "total" => $total,
        "texto" => $total > 0 ? "Mostrando " . $desde . "-" . $hasta . " de " . $total : "Sin productos publicados para estos filtros"
      ),
      "filtros_activos" => $filtrosActivos,
      "filtros_activos_total" => count($filtrosActivos),
      "estado_vacio" => array(
        "mostrar" => $total <= 0,
        "titulo" => "No encontramos productos con esos filtros",
        "accion_principal" => array(
          "label" => "Limpiar filtros",
          "url" => "/catalogo"
        )
      ),
      "guardrails_ui" => array(
        "no_mostrar_stock_exacto" => true,
        "precio_es_estimado" => true,
        "cotizacion_requiere_dryrun" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: entregar metadatos de Fase 2 para que frontend construya listados, chips y links sin hardcodear.
   * Impacto: API catalogo robusta; centraliza rutas de paginacion, ordenamientos y limpieza de filtros.
   * Contrato: no consulta BD, no escribe datos y mantiene guardrail no_granel.
   */
  private function fase2CatalogoPublico($pagina, $limite, $total, $filtrosAplicados) {
    $pagina = max(1, intval($pagina));
    $limite = max(1, intval($limite));
    $total = max(0, intval($total));
    $totalPaginas = $total > 0 ? intval(ceil($total / $limite)) : 0;
    $queryActual = $this->queryCatalogoPublico($filtrosAplicados, $pagina, $limite);
    $chips = $this->chipsFiltrosCatalogoPublico($filtrosAplicados);

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "url_actual" => "/ecommercePublico/catalogo" . ($queryActual !== "" ? "?" . $queryActual : ""),
      "url_limpiar_filtros" => "/ecommercePublico/catalogo?limite=" . intval($limite),
      "chips_filtros" => $chips,
      "chips_total" => count($chips),
      "orden_actual" => $this->valor($filtrosAplicados, "orden", "relevancia"),
      "links" => array(
        "self" => "/ecommercePublico/catalogo" . ($queryActual !== "" ? "?" . $queryActual : ""),
        "manifest" => "/ecommercePublico/catalogo_manifest",
        "filtros" => "/ecommercePublico/filtros",
        "navegacion" => "/ecommercePublico/navegacion",
        "secciones" => "/ecommercePublico/secciones",
        "primera" => "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($filtrosAplicados, 1, $limite),
        "anterior" => $pagina > 1 ? "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($filtrosAplicados, $pagina - 1, $limite) : null,
        "siguiente" => ($totalPaginas > 0 && $pagina < $totalPaginas) ? "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($filtrosAplicados, $pagina + 1, $limite) : null,
        "ultima" => $totalPaginas > 0 ? "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($filtrosAplicados, $totalPaginas, $limite) : null
      ),
      "ui" => array(
        "mostrar_filtros" => true,
        "mostrar_ordenamientos" => true,
        "mostrar_paginacion" => $totalPaginas > 1,
        "mostrar_limpiar_filtros" => count($chips) > 0,
        "max_limite" => 60
      ),
      "guardrails" => array(
        "no_granel" => true,
        "no_stock_exacto" => true,
        "precio_es_estimado" => true,
        "cotizacion_requiere_dryrun" => true
      )
    );
  }

  private function queryCatalogoPublico($filtrosAplicados, $pagina, $limite) {
    $query = array();
    foreach (array("q", "mascota", "necesidad", "marca", "categoria", "disponibilidad", "orden") as $clave) {
      $valor = $this->valor($filtrosAplicados, $clave, "");
      if ($clave === "orden" && $valor === "relevancia") {
        continue;
      }
      if ($valor === "" || $valor === 0 || $valor === "0" || $valor === null) {
        continue;
      }
      $query[$clave] = $valor;
    }
    if (!empty($filtrosAplicados["destacado"])) {
      $query["destacado"] = 1;
    }
    $query["pagina"] = max(1, intval($pagina));
    $query["limite"] = max(1, min(60, intval($limite)));
    return http_build_query($query);
  }

  private function chipsFiltrosCatalogoPublico($filtrosAplicados) {
    $chips = array();
    $labels = array(
      "q" => "Busqueda",
      "mascota" => "Mascota",
      "necesidad" => "Necesidad",
      "marca" => "Marca",
      "categoria" => "Categoria",
      "disponibilidad" => "Disponibilidad",
      "destacado" => "Destacados"
    );
    foreach ($labels as $clave => $label) {
      $valor = $this->valor($filtrosAplicados, $clave, "");
      $activo = is_bool($valor) ? $valor : ($valor !== "" && $valor !== 0 && $valor !== "0" && $valor !== null);
      if (!$activo) {
        continue;
      }
      $chips[] = array(
        "clave" => $clave,
        "label" => $label,
        "valor" => is_bool($valor) ? "1" : (string) $valor,
        "texto" => is_bool($valor) ? $label : $label . ": " . (string) $valor,
        "url_remover" => "/ecommercePublico/catalogo?" . $this->queryCatalogoPublico($this->filtrosSinClaveCatalogo($filtrosAplicados, $clave), 1, intval($this->valor($filtrosAplicados, "limite", 24)))
      );
    }
    return $chips;
  }

  private function filtrosSinClaveCatalogo($filtrosAplicados, $clave) {
    $salida = $filtrosAplicados;
    if (array_key_exists($clave, $salida)) {
      $salida[$clave] = is_bool($salida[$clave]) ? false : "";
    }
    return $salida;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-01
   * Proposito: traducir disponibilidad publica a decisiones UI sin exponer stock exacto.
   * Impacto: Frontend ecommerce; estabiliza badges, CTA y copy operativo en tarjetas y ficha.
   * Contrato: no consulta BD, no descuenta inventario y no revela cantidades internas.
   */
  private function frontendDisponibilidadPublica($disponibilidad, $permiteCotizacion) {
    $disponibilidad = trim((string) $disponibilidad);
    $permitirCta = !empty($permiteCotizacion);
    $mapa = array(
      "disponible" => array(
        "badge" => array("label" => "Disponible", "tono" => "success"),
        "mensaje" => "Disponible para cotizacion.",
        "cta" => array("label" => "Agregar a cotizacion", "habilitado" => $permitirCta)
      ),
      "pocas_piezas" => array(
        "badge" => array("label" => "Pocas piezas", "tono" => "warning"),
        "mensaje" => "Pocas piezas, sujeto a confirmacion.",
        "cta" => array("label" => "Consultar disponibilidad", "habilitado" => $permitirCta)
      ),
      "agotado" => array(
        "badge" => array("label" => "Agotado", "tono" => "muted"),
        "mensaje" => "Agotado, podemos revisar alternativa o proxima llegada.",
        "cta" => array("label" => "Consultar alternativa", "habilitado" => $permitirCta)
      ),
      "consultar_disponibilidad" => array(
        "badge" => array("label" => "Consultar", "tono" => "info"),
        "mensaje" => "Disponibilidad sujeta a confirmacion.",
        "cta" => array("label" => "Consultar disponibilidad", "habilitado" => $permitirCta)
      )
    );
    $ui = isset($mapa[$disponibilidad]) ? $mapa[$disponibilidad] : $mapa["consultar_disponibilidad"];
    $ui["estado"] = isset($mapa[$disponibilidad]) ? $disponibilidad : "consultar_disponibilidad";
    $ui["mostrar_stock_exacto"] = false;
    $ui["precio_es_estimado"] = true;
    $ui["requiere_dryrun_antes_de_whatsapp"] = true;
    $ui["cta"]["accion"] = "cotizacion_dryrun";
    if (!$permitirCta) {
      $ui["cta"]["motivo_disabled"] = "cotizacion_no_permitida";
    }
    return $ui;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-01
   * Proposito: resumir el resultado de cotizacion dry-run en decisiones listas para UI.
   * Impacto: Frontend ecommerce; evita que carrito, totales y CTA de WhatsApp dupliquen reglas del ERP.
   * Contrato: no escribe BD, no crea pedido, no descuenta inventario y no acepta precios del frontend.
   */
  private function frontendCotizacionDryRun($lineas, $totales, $bloqueos, $advertencias) {
    $lineas = is_array($lineas) ? $lineas : array();
    $bloqueos = is_array($bloqueos) ? array_values(array_unique($bloqueos)) : array();
    $advertencias = is_array($advertencias) ? array_values(array_unique($advertencias)) : array();
    $lineasTotal = count($lineas);
    $puedeContinuar = $lineasTotal > 0 && empty($bloqueos);
    $estado = "listo";
    $mensaje = "Carrito validado. Continua con tus datos para WhatsApp.";
    if ($lineasTotal <= 0) {
      $estado = "vacio";
      $mensaje = "Agrega productos para validar la cotizacion.";
    } elseif (!empty($bloqueos)) {
      $estado = "bloqueado";
      $mensaje = "Revisa las observaciones antes de continuar.";
    } elseif (!empty($advertencias)) {
      $estado = "observaciones";
      $mensaje = "Hay disponibilidad sujeta a confirmacion.";
    }

    return array(
      "estado" => $estado,
      "mensaje" => $mensaje,
      "lineas_total" => $lineasTotal,
      "bloqueos_total" => count($bloqueos),
      "advertencias_total" => count($advertencias),
      "puede_continuar_preflight" => $puedeContinuar,
      "mostrar_total_estimado" => true,
      "mostrar_whatsapp_preview" => $lineasTotal > 0,
      "max_items" => 50,
      "max_cantidad_por_linea" => 999,
      "precio_es_estimado" => true,
      "permitir_continuar_con_pocas_piezas" => true,
      "permitir_continuar_con_agotado" => true,
      "mensaje_confirmacion" => "Disponibilidad y total sujetos a confirmacion por Artiani.",
      "total_estimado_texto" => "$" . number_format(floatval($this->valor($totales, "total_estimado", 0)), 2) . " " . ((string) $this->valor($totales, "moneda", "MXN")),
      "cta_principal" => array(
        "label" => $puedeContinuar ? "Continuar a WhatsApp" : "Revisar carrito",
        "endpoint_siguiente" => "/ecommercePublico/cotizacion_preflight",
        "habilitado" => $puedeContinuar,
        "motivos_disabled" => $puedeContinuar ? array() : $bloqueos
      ),
      "guardrails_ui" => array(
        "no_registra_cotizacion" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "no_usar_precio_local_como_total" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-06
   * Proposito: entregar metadatos de Fase 2 para disponibilidad publica y CTA de cotizacion.
   * Impacto: Frontend ecommerce; evita interpretar stock interno y centraliza decisiones de carrito.
   * Contrato: no consulta BD, no revela cantidades exactas, no aparta ni descuenta inventario.
   */
  private function fase2DisponibilidadPublica($disponibilidad, $permiteCotizacion, $frontend, $fila = null) {
    $disponibilidad = in_array($disponibilidad, $this->estadosDisponibilidadPublica(), true) ? $disponibilidad : "consultar_disponibilidad";
    $requiereConfirmacion = $disponibilidad !== "disponible";
    $slug = is_array($fila) ? (string) $this->valor($fila, "slug", "") : "";
    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "estado" => $disponibilidad,
      "puede_agregar_cotizacion" => !empty($permiteCotizacion),
      "requiere_confirmacion_operativa" => $requiereConfirmacion,
      "nivel_confianza" => $disponibilidad === "disponible" ? "alta" : ($disponibilidad === "agotado" ? "baja" : "media"),
      "links" => array(
        "producto" => $slug !== "" ? "/ecommercePublico/producto/" . rawurlencode($slug) : "/ecommercePublico/producto/{slug}",
        "disponibilidad" => $slug !== "" ? "/ecommercePublico/disponibilidad?slug=" . rawurlencode($slug) : "/ecommercePublico/disponibilidad?slug={slug}",
        "dryrun" => "/ecommercePublico/cotizacion_dryrun",
        "preflight" => "/ecommercePublico/cotizacion_preflight"
      ),
      "ui" => array(
        "badge" => $this->valor($frontend, "badge", array()),
        "mensaje" => $this->valor($frontend, "mensaje", ""),
        "cta" => $this->valor($frontend, "cta", array()),
        "mostrar_alerta_confirmacion" => $requiereConfirmacion,
        "permitir_en_carrito" => !empty($permiteCotizacion),
        "siguiente_accion" => "cotizacion_dryrun"
      ),
      "guardrails" => array(
        "no_stock_exacto" => true,
        "no_reserva_inventario" => true,
        "no_descuenta_inventario" => true,
        "precio_es_estimado" => true,
        "no_granel" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-06
   * Proposito: resumir la Fase 2 del carrito dry-run con pasos, CTA y limites para frontend.
   * Impacto: Ecommerce publico; prepara UI de carrito sin persistencia real ni calculos locales de precio.
   * Contrato: no escribe BD, no crea pedido, no descuenta inventario y mantiene no_granel.
   */
  private function fase2CotizacionDryRunPublica($lineas, $totales, $bloqueos, $advertencias, $disponibilidadResumen) {
    $lineas = is_array($lineas) ? $lineas : array();
    $bloqueos = is_array($bloqueos) ? array_values(array_unique($bloqueos)) : array();
    $advertencias = is_array($advertencias) ? array_values(array_unique($advertencias)) : array();
    $lineasTotal = count($lineas);
    $estado = $lineasTotal <= 0 ? "vacio" : (empty($bloqueos) ? (empty($advertencias) ? "listo" : "observaciones") : "bloqueado");
    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "estado" => $estado,
      "resumen_carrito" => array(
        "lineas_total" => $lineasTotal,
        "cantidad_total" => $this->sumarCantidadLineasCotizacion($lineas),
        "bloqueos_total" => count($bloqueos),
        "advertencias_total" => count($advertencias),
        "disponibilidad" => $this->resumenDisponibilidadCotizacion($disponibilidadResumen),
        "total_estimado" => floatval($this->valor($totales, "total_estimado", 0)),
        "moneda" => (string) $this->valor($totales, "moneda", "MXN")
      ),
      "flujo" => array(
        "paso_actual" => "carrito",
        "siguiente_paso" => empty($bloqueos) && $lineasTotal > 0 ? "cotizacion_preflight" : "corregir_carrito",
        "endpoint_siguiente" => "/ecommercePublico/cotizacion_preflight",
        "puede_continuar" => empty($bloqueos) && $lineasTotal > 0
      ),
      "ui" => array(
        "mostrar_lineas" => true,
        "mostrar_total_estimado" => true,
        "mostrar_whatsapp_preview" => $lineasTotal > 0,
        "mostrar_observaciones" => !empty($bloqueos) || !empty($advertencias),
        "permitir_eliminar_lineas" => true,
        "permitir_editar_cantidad" => true
      ),
      "limites" => array(
        "max_items" => 50,
        "max_cantidad_por_linea" => 999,
        "min_cantidad_por_linea" => 0.000001
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_escribe_bd" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "no_usar_precio_local_como_total" => true,
        "no_granel" => true
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-06
   * Proposito: entregar metadatos de Fase 2 para confirmar cotizacion antes de WhatsApp/persistencia futura.
   * Impacto: Frontend ecommerce; estabiliza embudo carrito-contacto-confirmacion-WhatsApp.
   * Contrato: no escribe BD, no envia WhatsApp, no registra cotizacion y no aparta inventario.
   */
  private function fase2CotizacionPreflightPublica($listoWhatsapp, $listoRegistroFuturo, $lineas, $totales, $bloqueos, $advertencias, $validacionContacto, $consentimiento, $whatsappUrl) {
    $lineas = is_array($lineas) ? $lineas : array();
    $bloqueos = is_array($bloqueos) ? array_values(array_unique($bloqueos)) : array();
    $advertencias = is_array($advertencias) ? array_values(array_unique($advertencias)) : array();
    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "estado" => !empty($listoWhatsapp) ? "listo_para_whatsapp" : "requiere_revision",
      "embudo" => array(
        "paso_actual" => "confirmacion",
        "pasos" => array("carrito", "datos_contacto", "confirmacion", "whatsapp"),
        "siguiente_accion" => !empty($listoWhatsapp) ? "abrir_whatsapp" : "corregir_datos",
        "puede_abrir_whatsapp" => !empty($listoWhatsapp),
        "puede_registro_futuro" => !empty($listoRegistroFuturo)
      ),
      "resumen" => array(
        "lineas_total" => count($lineas),
        "total_estimado" => floatval($this->valor($totales, "total_estimado", 0)),
        "moneda" => (string) $this->valor($totales, "moneda", "MXN"),
        "bloqueos_total" => count($bloqueos),
        "advertencias_total" => count($advertencias)
      ),
      "contacto" => array(
        "valido_para_whatsapp" => !empty($validacionContacto["valido_para_whatsapp"]),
        "valido_para_registro_futuro" => !empty($validacionContacto["valido_para_registro_futuro"]),
        "consentimiento_registro_futuro" => !empty($consentimiento["listo_para_registro_futuro"])
      ),
      "cta" => array(
        "tipo" => !empty($listoWhatsapp) ? "whatsapp" : "corregir_datos",
        "label" => !empty($listoWhatsapp) ? "Enviar por WhatsApp" : "Revisar cotizacion",
        "url" => (string) $whatsappUrl,
        "disabled" => empty($listoWhatsapp)
      ),
      "guardrails" => array(
        "preflight" => true,
        "no_escribe_bd" => true,
        "no_envia_whatsapp_servidor" => true,
        "no_registra_cotizacion" => true,
        "no_descuenta_inventario" => true,
        "no_crea_pedido" => true,
        "no_granel" => true
      )
    );
  }

  private function ordenCatalogoPublicoNormalizado($orden) {
    $orden = trim((string) $orden);
    return in_array($orden, array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"), true) ? $orden : "relevancia";
  }

  private function ordenCatalogoPublicoSql($orden) {
    $orden = $this->ordenCatalogoPublicoNormalizado($orden);
    if ($orden === "nombre") {
      return "pub.titulo_publico ASC, pub.orden ASC";
    }
    if ($orden === "precio_asc") {
      return "pr.precio ASC, pub.titulo_publico ASC";
    }
    if ($orden === "precio_desc") {
      return "pr.precio DESC, pub.titulo_publico ASC";
    }
    if ($orden === "recientes") {
      return "COALESCE(pub.fecha_publicacion, pub.fecha_registro) DESC, pub.titulo_publico ASC";
    }
    return "pub.destacado DESC, pub.orden ASC, pub.titulo_publico ASC";
  }

  private function etiquetaDisponibilidadPublica($estado) {
    $mapa = array(
      "disponible" => "Disponible",
      "pocas_piezas" => "Pocas piezas",
      "consultar_disponibilidad" => "Consultar disponibilidad",
      "agotado" => "Agotado"
    );
    return isset($mapa[$estado]) ? $mapa[$estado] : $estado;
  }

  private function etiquetaTaxonomiaPublica($valor) {
    $mapa = array(
      "perro" => "perros",
      "gato" => "gatos",
      "pez" => "peces",
      "ave" => "aves",
      "reptil" => "reptiles",
      "roedor" => "roedores",
      "alimento" => "Alimento",
      "premio" => "Premios",
      "higiene" => "Higiene",
      "salud" => "Salud",
      "paseo" => "Paseo",
      "habitat" => "Habitat",
      "juguete" => "Juguetes",
      "estetica" => "Estetica"
    );
    return isset($mapa[$valor]) ? $mapa[$valor] : ucfirst(str_replace("_", " ", $valor));
  }

  private function filtrarSugerenciasTaxonomia($items, $q, $limite, $tipo) {
    $salida = array();
    $qNormalizado = strtolower($this->normalizarTextoPlano($q));
    foreach ((array) $items as $item) {
      $label = trim((string) $this->valor($item, "etiqueta", $this->valor($item, "nombre", $this->valor($item, "valor", ""))));
      $valor = trim((string) $this->valor($item, "valor", $this->valor($item, "id", $label)));
      if ($label === "" && $valor === "") {
        continue;
      }
      $texto = strtolower($this->normalizarTextoPlano($label . " " . $valor));
      if ($qNormalizado !== "" && strpos($texto, $qNormalizado) === false) {
        continue;
      }
      $path = "/ecommercePublico/catalogo";
      if ($tipo === "mascota") {
        $path .= "?mascota=" . rawurlencode($valor);
      } elseif ($tipo === "necesidad") {
        $path .= "?necesidad=" . rawurlencode($valor);
      } elseif ($tipo === "marca") {
        $path .= "?marca=" . rawurlencode($valor);
      } elseif ($tipo === "categoria") {
        $path .= "?categoria=" . rawurlencode($valor);
      }
      $salida[] = array(
        "tipo" => $tipo,
        "label" => $label,
        "valor" => $valor,
        "url" => $path,
        "total" => intval($this->valor($item, "total", 0))
      );
      if (count($salida) >= $limite) {
        break;
      }
    }
    return $salida;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: entregar metadatos de Fase 2 para autocompletado y estado vacio del buscador.
   * Impacto: Frontend ecommerce; evita hardcodear grupos, rutas, minimos y CTAs de busqueda.
   * Contrato: no registra busquedas, no consulta BD y mantiene resultados derivados de publicaciones no granel.
   */
  private function fase2BusquedaSugerenciasPublicas($q, $limite, $grupos, $total) {
    $q = trim((string) $q);
    $orden = array("productos", "marcas", "categorias", "mascotas", "necesidades");
    $totales = array();
    foreach ($orden as $grupo) {
      $totales[$grupo] = count($this->valor($grupos, $grupo, array()));
    }
    $sinResultados = intval($total) <= 0;

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "q" => $q,
      "limite_por_grupo" => intval($limite),
      "orden_grupos" => $orden,
      "totales_grupo" => $totales,
      "links" => array(
        "catalogo_resultados" => "/ecommercePublico/catalogo?q=" . rawurlencode($q),
        "registrar_busqueda_preflight" => "/ecommercePublico/busqueda_registrar",
        "filtros" => "/ecommercePublico/filtros",
        "catalogo_manifest" => "/ecommercePublico/catalogo_manifest"
      ),
      "ui" => array(
        "mostrar_autocomplete" => strlen($q) >= 2,
        "min_caracteres_recomendado" => 2,
        "mostrar_grupos_vacios" => false,
        "mostrar_contadores" => true,
        "mostrar_precio_en_producto" => true,
        "mostrar_disponibilidad_en_producto" => true,
        "max_items_mobile_por_grupo" => min(4, intval($limite)),
        "max_items_desktop_por_grupo" => intval($limite)
      ),
      "estado_vacio" => array(
        "mostrar" => $sinResultados,
        "titulo" => $q === "" ? "Empieza a buscar productos" : "No encontramos sugerencias",
        "mensaje" => $q === "" ? "Escribe al menos 2 caracteres para ver sugerencias." : "Prueba con otro nombre, marca, categoria o necesidad.",
        "cta" => array("label" => "Ver catalogo", "url" => "/ecommercePublico/catalogo")
      ),
      "guardrails" => array(
        "read_only" => true,
        "no_registra_busqueda" => true,
        "solo_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "precio_es_estimado" => true
      )
    );
  }

  private function itemsNavegacionDesdeFiltros($items, $tipo, $parametro, $limite) {
    $salida = array();
    foreach ((array) $items as $item) {
      $label = trim((string) $this->valor($item, "etiqueta", $this->valor($item, "nombre", $this->valor($item, "valor", ""))));
      $valor = trim((string) $this->valor($item, "valor", $this->valor($item, "id", $label)));
      if ($label === "" && $valor === "") {
        continue;
      }
      $salida[] = array(
        "codigo" => $this->slugificar($tipo . "-" . $valor),
        "tipo" => $tipo,
        "label" => $label,
        "valor" => $valor,
        "url" => "/catalogo?" . rawurlencode($parametro) . "=" . rawurlencode($valor),
        "total" => intval($this->valor($item, "total", 0))
      );
      if (count($salida) >= $limite) {
        break;
      }
    }
    return $salida;
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: entregar metadatos de Fase 2 para filtros facetados sin hardcodear rutas en frontend.
   * Impacto: Ecommerce publico; estabiliza chips, contadores, urls y reglas visuales de filtros.
   * Contrato: no consulta BD, no escribe datos y mantiene catalogo publico sin granel.
   */
  private function fase2FiltrosPublicos($grupos) {
    $facetados = array();
    $totales = array();
    foreach (array("mascotas", "necesidades", "marcas", "categorias", "disponibilidad") as $grupo) {
      $parametro = $this->parametroFiltroPublico($grupo);
      $facetados[$grupo] = $this->facetasFiltroPublico($this->valor($grupos, $grupo, array()), $grupo, $parametro);
      $totales[$grupo] = count($facetados[$grupo]);
    }

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "url_catalogo_base" => "/ecommercePublico/catalogo",
      "facetados" => $facetados,
      "totales" => $totales,
      "ui" => array(
        "mostrar_contadores" => true,
        "mostrar_chips_removibles" => true,
        "mostrar_limpiar_filtros" => true,
        "max_grupos_visibles_desktop" => 5,
        "max_grupos_visibles_mobile" => 3
      ),
      "guardrails" => array(
        "solo_publicados" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "no_ecom_legacy_fuente" => true
      )
    );
  }

  private function fase2NavegacionPublica($navegacion, $limite) {
    $grupos = array("mascotas", "necesidades", "categorias", "marcas", "disponibilidad");
    $resumen = array();
    $chipsHome = array();
    foreach ($grupos as $grupo) {
      $items = $this->valor($navegacion, $grupo, array());
      $resumen[$grupo] = count($items);
      foreach (array_slice($items, 0, 4) as $item) {
        $chipsHome[] = array(
          "grupo" => $grupo,
          "label" => $this->valor($item, "label", ""),
          "url" => $this->valor($item, "url", ""),
          "total" => intval($this->valor($item, "total", 0))
        );
      }
    }

    return array(
      "fase" => "fase_2_api_catalogo_robusta",
      "limite_por_grupo" => intval($limite),
      "resumen_grupos" => $resumen,
      "chips_home" => array_slice($chipsHome, 0, 12),
      "links" => array(
        "catalogo" => "/ecommercePublico/catalogo",
        "filtros" => "/ecommercePublico/filtros",
        "secciones" => "/ecommercePublico/secciones",
        "manifest" => "/ecommercePublico/catalogo_manifest"
      ),
      "ui" => array(
        "usar_primaria_para_header" => true,
        "usar_chips_home_para_exploracion" => true,
        "mostrar_contadores" => true,
        "colapsar_grupos_largos" => true
      ),
      "guardrails" => array(
        "solo_derivado_de_publicaciones" => true,
        "no_granel" => true,
        "no_stock_exacto" => true,
        "no_expone_secretos" => true
      )
    );
  }

  private function facetasFiltroPublico($items, $grupo, $parametro) {
    $salida = array();
    foreach ((array) $items as $item) {
      $label = trim((string) $this->valor($item, "etiqueta", $this->valor($item, "nombre", $this->valor($item, "valor", ""))));
      $valor = trim((string) $this->valor($item, "valor", $this->valor($item, "id", $label)));
      if ($label === "" && $valor === "") {
        continue;
      }
      $url = "/ecommercePublico/catalogo?" . rawurlencode($parametro) . "=" . rawurlencode($valor);
      $salida[] = array(
        "grupo" => $grupo,
        "parametro" => $parametro,
        "label" => $label,
        "valor" => $valor,
        "total" => intval($this->valor($item, "total", 0)),
        "url" => $url,
        "chip" => array(
          "texto" => $label,
          "url_aplicar" => $url,
          "url_remover" => "/ecommercePublico/catalogo",
          "contador" => intval($this->valor($item, "total", 0))
        )
      );
    }
    return $salida;
  }

  private function parametroFiltroPublico($grupo) {
    $mapa = array(
      "mascotas" => "mascota",
      "necesidades" => "necesidad",
      "marcas" => "marca",
      "categorias" => "categoria",
      "disponibilidad" => "disponibilidad"
    );
    return $this->valor($mapa, $grupo, $grupo);
  }

  private function politicasPublicasDefault() {
    return array(
      array(
        "codigo" => "terminos-condiciones",
        "tipo" => "legal",
        "titulo" => "Terminos y condiciones",
        "resumen" => "El sitio funciona como catalogo y cotizador. La venta se confirma por WhatsApp, POS o pedidos internos.",
        "contenido" => "Los productos, precios y disponibilidad se muestran como informacion publica del ERP. En esta fase no hay checkout ni pago online; toda cotizacion queda sujeta a confirmacion operativa.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      ),
      array(
        "codigo" => "aviso-privacidad",
        "tipo" => "legal",
        "titulo" => "Aviso de privacidad",
        "resumen" => "Los datos enviados por formularios se usaran para atender cotizaciones, facturacion y seguimiento solicitado por el cliente.",
        "contenido" => "No se deben guardar datos fiscales, telefono o correo en eventos anonimos de navegacion. Las solicitudes con datos personales requieren uso operativo interno y resguardo conforme a la politica de privacidad vigente.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      ),
      array(
        "codigo" => "cotizacion-whatsapp",
        "tipo" => "operativa",
        "titulo" => "Cotizacion por WhatsApp",
        "resumen" => "El carrito genera una cotizacion estimada y abre WhatsApp para seguimiento.",
        "contenido" => "El carrito web no descuenta inventario ni confirma pedido. Antes de abrir WhatsApp, el ERP recalcula precios y disponibilidad con cotizacion dry-run.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      ),
      array(
        "codigo" => "precios-disponibilidad",
        "tipo" => "operativa",
        "titulo" => "Precios y disponibilidad",
        "resumen" => "Los precios y disponibilidad son informativos y pueden requerir confirmacion.",
        "contenido" => "El sitio no muestra stock exacto. La disponibilidad publica usa estados simples: disponible, pocas piezas, consultar disponibilidad y agotado.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      ),
      array(
        "codigo" => "facturacion",
        "tipo" => "fiscal",
        "titulo" => "Solicitud de factura",
        "resumen" => "El cliente podra solicitar factura con su folio de compra para revision interna.",
        "contenido" => "La web no emite facturas automaticamente. El cliente captura folio y datos fiscales; el ERP registra la solicitud para revision del equipo interno o contador.",
        "version" => "fase1",
        "requiere_aceptacion" => true
      ),
      array(
        "codigo" => "cambios-devoluciones",
        "tipo" => "operativa",
        "titulo" => "Cambios y devoluciones",
        "resumen" => "Las solicitudes de cambio o devolucion se revisan segun producto, estado y comprobante.",
        "contenido" => "La politica final debe ajustarse a las reglas internas del negocio. En Fase 1 la web solo debe mostrar informacion y canal de contacto.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      ),
      array(
        "codigo" => "cookies-tracking",
        "tipo" => "privacidad",
        "titulo" => "Cookies y mejora de busqueda",
        "resumen" => "El sitio podra registrar busquedas y navegacion anonima para mejorar catalogo y recomendaciones.",
        "contenido" => "El tracking debe separar eventos anonimos de datos personales. Busquedas sin resultado, mascotas seleccionadas y productos vistos ayudan a decidir que publicar y recomendar.",
        "version" => "fase1",
        "requiere_aceptacion" => false
      )
    );
  }

  private function formatearPoliticaPublica($fila) {
    return array(
      "codigo" => $this->limpiarFiltroPublico($this->valor($fila, "codigo", "")),
      "tipo" => $this->limpiarFiltroPublico($this->valor($fila, "tipo", "")),
      "titulo" => trim((string) $this->valor($fila, "titulo", "")),
      "resumen" => trim((string) $this->valor($fila, "resumen", "")),
      "contenido" => trim((string) $this->valor($fila, "contenido", "")),
      "version" => trim((string) $this->valor($fila, "version", "")),
      "requiere_aceptacion" => intval($this->valor($fila, "requiere_aceptacion", 0)) === 1,
      "fecha_vigencia" => $this->valor($fila, "fecha_vigencia", null)
    );
  }

  private function guardrailsPoliticasPublicas() {
    return array(
      "no_checkout" => true,
      "no_factura_automatica" => true,
      "no_pedido_confirmado" => true,
      "precios_sujetos_a_confirmacion" => true,
      "no_stock_exacto" => true,
      "tracking_anonimo_separado_de_datos_personales" => true
    );
  }

  private function taxonomiaMascotasDefault() {
    return array(
      "mascotas" => array(
        array("codigo" => "perro", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Perro", "descripcion" => "Productos para perros.", "icono" => "dog", "orden" => 10),
        array("codigo" => "gato", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Gato", "descripcion" => "Productos para gatos.", "icono" => "cat", "orden" => 20),
        array("codigo" => "pez", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Pez", "descripcion" => "Productos para acuario y peces.", "icono" => "fish", "orden" => 30),
        array("codigo" => "ave", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Ave", "descripcion" => "Productos para aves.", "icono" => "bird", "orden" => 40),
        array("codigo" => "reptil", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Reptil", "descripcion" => "Productos para reptiles.", "icono" => "shell", "orden" => 50),
        array("codigo" => "roedor", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Roedor", "descripcion" => "Productos para hamsters, cuyos, conejos y similares.", "icono" => "circle", "orden" => 60),
        array("codigo" => "otra", "tipo" => "especie", "parent_codigo" => null, "nombre" => "Otra mascota", "descripcion" => "Productos para otras mascotas.", "icono" => "paw-print", "orden" => 90)
      ),
      "necesidades" => array(
        array("codigo" => "alimento", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Alimento", "descripcion" => "Alimentos, dietas y comida diaria.", "icono" => "bowl", "orden" => 10),
        array("codigo" => "premio", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Premios", "descripcion" => "Snacks, premios y recompensas.", "icono" => "badge", "orden" => 20),
        array("codigo" => "higiene", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Higiene", "descripcion" => "Limpieza, sanitarios y cuidado.", "icono" => "sparkles", "orden" => 30),
        array("codigo" => "salud", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Salud", "descripcion" => "Suplementos y apoyo al bienestar.", "icono" => "heart-pulse", "orden" => 40),
        array("codigo" => "paseo", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Paseo", "descripcion" => "Correas, collares, pecheras y accesorios.", "icono" => "footprints", "orden" => 50),
        array("codigo" => "habitat", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Habitat", "descripcion" => "Casas, peceras, jaulas, camas y entorno.", "icono" => "home", "orden" => 60),
        array("codigo" => "juguete", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Juguetes", "descripcion" => "Juego, ejercicio y entretenimiento.", "icono" => "toy-brick", "orden" => 70),
        array("codigo" => "estetica", "tipo" => "necesidad", "parent_codigo" => null, "nombre" => "Estetica", "descripcion" => "Cepillos, shampoos, perfumes y cuidado estetico.", "icono" => "scissors", "orden" => 80)
      )
    );
  }

  private function formatearTaxonomiaMascota($fila) {
    return array(
      "codigo" => $this->limpiarFiltroPublico($this->valor($fila, "codigo", "")),
      "tipo" => $this->limpiarFiltroPublico($this->valor($fila, "tipo", "especie")),
      "parent_codigo" => $this->valor($fila, "parent_codigo", null),
      "nombre" => trim((string) $this->valor($fila, "nombre", "")),
      "descripcion" => trim((string) $this->valor($fila, "descripcion", "")),
      "icono" => trim((string) $this->valor($fila, "icono", "")),
      "orden" => intval($this->valor($fila, "orden", 0))
    );
  }

  private function guardrailsTaxonomiaMascotas() {
    return array(
      "no_requiere_cliente_registrado" => true,
      "no_requiere_mascotas_guardadas" => true,
      "compatible_con_filtros_catalogo" => true,
      "prepara_recomendaciones_futuras" => true
    );
  }

  private function guardrailsExperienciaClientePreflight() {
    return array(
      "no_escribe_bd" => true,
      "no_checkout" => true,
      "no_factura_automatica" => true,
      "no_registra_datos_personales_en_tracking" => true,
      "no_descuenta_inventario" => true,
      "requiere_autorizacion_para_persistencia" => true,
      "requiere_rate_limit_para_post_productivo" => true
    );
  }

  private function detectarDatosPersonalesTracking($datos) {
    $detectados = array();
    $clavesSensibles = array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion", "codigo_postal", "cp", "calle", "colonia");
    $this->detectarDatosPersonalesTrackingRec($datos, "", $clavesSensibles, $detectados);
    return array_values(array_unique($detectados));
  }

  private function detectarDatosPersonalesTrackingRec($datos, $prefijo, $clavesSensibles, &$detectados) {
    if (!is_array($datos)) {
      $valor = trim((string) $datos);
      if ($valor !== "" && preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $valor)) {
        $detectados[] = $prefijo !== "" ? $prefijo . ":correo" : "correo";
      }
      if ($valor !== "" && preg_match('/(?:\D|^)(\d{10})(?:\D|$)/', $valor)) {
        $detectados[] = $prefijo !== "" ? $prefijo . ":telefono" : "telefono";
      }
      if ($valor !== "" && preg_match('/\b[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}\b/u', strtoupper($valor))) {
        $detectados[] = $prefijo !== "" ? $prefijo . ":rfc" : "rfc";
      }
      return;
    }
    foreach ($datos as $clave => $valor) {
      $claveLimpia = strtolower(trim((string) $clave));
      $ruta = $prefijo === "" ? $claveLimpia : $prefijo . "." . $claveLimpia;
      if (in_array($claveLimpia, $clavesSensibles, true)) {
        $detectados[] = $ruta;
      }
      $this->detectarDatosPersonalesTrackingRec($valor, $ruta, $clavesSensibles, $detectados);
    }
  }

  private function limpiarMetadataTracking($datos, $nivel = 0) {
    if (!is_array($datos) || $nivel > 2) {
      return is_scalar($datos) ? substr(trim((string) $datos), 0, 160) : null;
    }
    $salida = array();
    foreach ($datos as $clave => $valor) {
      $claveLimpia = $this->limpiarFiltroPublico($clave);
      if ($claveLimpia === "" || in_array($claveLimpia, array("nombre", "telefono", "celular", "correo", "email", "rfc", "razon_social", "direccion"), true)) {
        continue;
      }
      if (is_array($valor)) {
        $salida[$claveLimpia] = $this->limpiarMetadataTracking($valor, $nivel + 1);
      } elseif (is_scalar($valor)) {
        $salida[$claveLimpia] = substr(trim((string) $valor), 0, 160);
      }
      if (count($salida) >= 30) {
        break;
      }
    }
    return $salida;
  }

  private function consultaTopBusquedas($db, $inicio, $fin, $limite, $soloSinResultado) {
    $sql = "SELECT termino_normalizado termino, COUNT(*) total, SUM(CASE WHEN sin_resultados=1 THEN 1 ELSE 0 END) sin_resultados, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_busquedas
      WHERE fecha_registro BETWEEN :inicio AND :fin";
    if ($soloSinResultado) {
      $sql .= " AND sin_resultados=1";
    }
    $sql .= " GROUP BY termino_normalizado ORDER BY total DESC, termino_normalizado ASC LIMIT " . intval($limite);
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultaTopEventosCampo($db, $campo, $inicio, $fin, $limite) {
    $permitidos = array("mascota_especie", "necesidad");
    if (!in_array($campo, $permitidos, true)) {
      return array();
    }
    $sql = "SELECT " . $campo . " valor, COUNT(*) total, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_eventos_navegacion
      WHERE fecha_registro BETWEEN :inicio AND :fin
        AND TRIM(COALESCE(" . $campo . ",''))<>''
      GROUP BY " . $campo . "
      ORDER BY total DESC, valor ASC
      LIMIT " . intval($limite);
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultaTopEventosProducto($db, $tipoEvento, $inicio, $fin, $limite) {
    $tipoEvento = $this->limpiarFiltroPublico($tipoEvento);
    $sql = "SELECT id_publicacion, id_sku, COUNT(*) total, MAX(fecha_registro) ultima_fecha
      FROM erp_ecommerce_eventos_navegacion
      WHERE fecha_registro BETWEEN :inicio AND :fin
        AND tipo_evento=:tipo
        AND (id_publicacion IS NOT NULL OR id_sku IS NOT NULL)
      GROUP BY id_publicacion, id_sku
      ORDER BY total DESC, id_publicacion ASC
      LIMIT " . intval($limite);
    $stmt = $db->prepare($sql);
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin, ":tipo" => $tipoEvento));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  private function consultaConversionWhatsapp($db, $inicio, $fin) {
    $stmt = $db->prepare("SELECT tipo_evento, COUNT(*) total
      FROM erp_ecommerce_eventos_navegacion
      WHERE fecha_registro BETWEEN :inicio AND :fin
        AND tipo_evento IN ('add_to_quote','open_whatsapp')
      GROUP BY tipo_evento");
    $stmt->execute(array(":inicio" => $inicio, ":fin" => $fin));
    $totales = array("add_to_quote" => 0, "open_whatsapp" => 0);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
      $tipo = (string) $this->valor($fila, "tipo_evento", "");
      if (array_key_exists($tipo, $totales)) {
        $totales[$tipo] = intval($this->valor($fila, "total", 0));
      }
    }
    return array(
      "add_to_quote" => $totales["add_to_quote"],
      "open_whatsapp" => $totales["open_whatsapp"],
      "ratio_estimado" => $totales["add_to_quote"] > 0 ? round($totales["open_whatsapp"] / $totales["add_to_quote"], 4) : null
    );
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

  private function normalizarContactoCotizacion($contacto) {
    if (!is_array($contacto)) {
      $contacto = array();
    }
    return array(
      "nombre" => trim(substr((string) $this->valor($contacto, "nombre", ""), 0, 220)),
      "telefono" => preg_replace('/[^\d+]/', '', substr((string) $this->valor($contacto, "telefono", ""), 0, 60)),
      "correo" => trim(substr((string) $this->valor($contacto, "correo", ""), 0, 220)),
      "canal_preferido" => $this->limpiarFiltroPublico($this->valor($contacto, "canal_preferido", "whatsapp")),
      "mensaje" => trim(substr((string) $this->valor($contacto, "mensaje", ""), 0, 1000))
    );
  }

  private function validacionContactoCotizacion($contacto) {
    $advertencias = array();
    $errores = array();
    $nombre = trim((string) $this->valor($contacto, "nombre", ""));
    $telefono = trim((string) $this->valor($contacto, "telefono", ""));
    $correo = trim((string) $this->valor($contacto, "correo", ""));
    if ($nombre !== "" && strlen($nombre) < 2) {
      $advertencias[] = "contacto_nombre_muy_corto";
    }
    if ($telefono !== "" && strlen(preg_replace('/\D+/', '', $telefono)) < 10) {
      $advertencias[] = "contacto_telefono_incompleto";
    }
    if ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
      $advertencias[] = "contacto_correo_invalido";
    }
    return array(
      "valido_para_whatsapp" => empty($errores),
      "valido_para_registro_futuro" => $nombre !== "" && $telefono !== "" && empty($errores),
      "campos" => array(
        "nombre" => array("presente" => $nombre !== "", "requerido_futuro" => true),
        "telefono" => array("presente" => $telefono !== "", "requerido_futuro" => true),
        "correo" => array("presente" => $correo !== "", "requerido_futuro" => false)
      ),
      "advertencias" => array_values(array_unique($advertencias)),
      "errores" => array_values(array_unique($errores))
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: aceptar consentimientos enviados por frontend dentro de contacto o en raiz legacy.
   * Impacto: Ecommerce publico; elimina advertencias falsas de preflight sin habilitar persistencia real.
   */
  private function booleanoCotizacion($valor) {
    if (is_array($valor)) {
      return false;
    }
    if ($valor === true || $valor === 1) {
      return true;
    }
    if ($valor === false || $valor === null || $valor === 0) {
      return false;
    }
    return in_array(strtolower(trim((string) $valor)), array("1", "true", "si", "yes", "on", "acepto", "aceptado"), true);
  }

  private function politicasAceptadasCotizacion($datos, $contactoRaw) {
    $valor = $this->valor($contactoRaw, "acepta_politicas", $this->valor($contactoRaw, "politicas_aceptadas", $this->valor($datos, "politicas_aceptadas", array())));
    if ($this->booleanoCotizacion($valor)) {
      return array("aviso_privacidad", "terminos_cotizacion");
    }
    return $this->normalizarListaTextoPublica($valor);
  }

  private function consentimientoCotizacion($aceptaWhatsapp, $politicasAceptadas) {
    $politicas = is_array($politicasAceptadas) ? $politicasAceptadas : array();
    return array(
      "acepta_contacto_whatsapp" => !empty($aceptaWhatsapp),
      "politicas_aceptadas" => $politicas,
      "aviso_privacidad_aceptado" => in_array("aviso_privacidad", $politicas, true),
      "terminos_cotizacion_aceptados" => in_array("terminos_cotizacion", $politicas, true) || in_array("terminos", $politicas, true),
      "requerido_para_registro_futuro" => array("acepta_contacto_whatsapp", "aviso_privacidad"),
      "listo_para_registro_futuro" => !empty($aceptaWhatsapp) && in_array("aviso_privacidad", $politicas, true)
    );
  }

  private function sumarCantidadLineasCotizacion($lineas) {
    $total = 0.0;
    foreach ($lineas as $linea) {
      $total += floatval($this->valor($linea, "cantidad", 0));
    }
    return $total;
  }

  private function resumenDisponibilidadCotizacion($conteo) {
    $salida = array();
    foreach ($this->estadosDisponibilidadPublica() as $estado) {
      $salida[] = array(
        "valor" => $estado,
        "etiqueta" => $this->etiquetaDisponibilidadPublica($estado),
        "total" => intval($this->valor($conteo, $estado, 0))
      );
    }
    return $salida;
  }

  private function normalizarUtmCotizacion($utm) {
    if (!is_array($utm)) {
      return array();
    }
    $limpio = array();
    foreach (array("source", "medium", "campaign", "term", "content", "referrer") as $clave) {
      $valor = trim(substr((string) $this->valor($utm, $clave, ""), 0, 180));
      if ($valor !== "") {
        $limpio[$clave] = $valor;
      }
    }
    return $limpio;
  }

  private function normalizarListaTextoPublica($valor) {
    if (is_string($valor)) {
      $valor = preg_split('/[\r\n,]+/', $valor);
    }
    if (!is_array($valor)) {
      return array();
    }
    $limpios = array();
    foreach ($valor as $item) {
      $texto = $this->limpiarFiltroPublico($item);
      if ($texto !== "" && !in_array($texto, $limpios, true)) {
        $limpios[] = $texto;
      }
    }
    return $limpios;
  }

  private function folioPreliminarCotizacion($datos, $lineas, $contacto) {
    if (empty($lineas)) {
      return "";
    }
    $base = json_encode(array(
      "items" => $this->valor($datos, "items", array()),
      "contacto" => array("telefono" => $this->valor($contacto, "telefono", "")),
      "fecha" => date("Y-m-d")
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return "PRE-" . date("Ymd") . "-" . strtoupper(substr(hash("sha256", $base), 0, 8));
  }

  private function folioCotizacionPlaneado($db) {
    $fecha = date("Ymd");
    $siguiente = 1;
    if ($db && $this->tablaExiste($db, "erp_ecommerce_cotizaciones")) {
      $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_cotizaciones WHERE folio LIKE :folio");
      $stmt->execute(array(":folio" => "ECOM-" . $fecha . "-%"));
      $siguiente = intval($stmt->fetchColumn()) + 1;
    }
    return "ECOM-" . $fecha . "-" . str_pad((string) $siguiente, 6, "0", STR_PAD_LEFT);
  }

  private function tablasCotizacionesDisponibles($db) {
    $tablas = array(
      "erp_ecommerce_cotizaciones" => false,
      "erp_ecommerce_cotizaciones_detalle" => false,
      "erp_ecommerce_cotizaciones_eventos" => false
    );
    if (!$db) {
      return $tablas;
    }
    foreach (array_keys($tablas) as $tabla) {
      $tablas[$tabla] = $this->tablaExiste($db, $tabla);
    }
    return $tablas;
  }

  private function formatearCotizacionBandeja($fila) {
    return array(
      "id_cotizacion" => intval($this->valor($fila, "id_cotizacion", 0)),
      "folio" => trim((string) $this->valor($fila, "folio", "")),
      "origen" => trim((string) $this->valor($fila, "origen", "")),
      "estatus" => trim((string) $this->valor($fila, "estatus", "")),
      "id_cliente_crm" => $this->valor($fila, "id_cliente_crm", null) !== null ? intval($this->valor($fila, "id_cliente_crm", 0)) : null,
      "nombre_contacto" => trim((string) $this->valor($fila, "nombre_contacto", "")),
      "telefono_contacto" => trim((string) $this->valor($fila, "telefono_contacto", "")),
      "correo_contacto" => trim((string) $this->valor($fila, "correo_contacto", "")),
      "canal_contacto_preferido" => trim((string) $this->valor($fila, "canal_contacto_preferido", "")),
      "moneda" => trim((string) $this->valor($fila, "moneda", "MXN")),
      "subtotal_estimado" => round(floatval($this->valor($fila, "subtotal_estimado", 0)), 6),
      "total_estimado" => round(floatval($this->valor($fila, "total_estimado", 0)), 6),
      "partidas" => intval($this->valor($fila, "partidas", 0)),
      "fecha_expiracion" => $this->valor($fila, "fecha_expiracion", null),
      "fecha_registro" => $this->valor($fila, "fecha_registro", null),
      "fecha_actualizacion" => $this->valor($fila, "fecha_actualizacion", null),
      "fecha_ultimo_evento" => $this->valor($fila, "fecha_ultimo_evento", null),
      "acciones_futuras" => array("ver_detalle", "marcar_en_seguimiento", "preparar_pedido_manual", "descartar")
    );
  }

  private function resumenBandejaCotizaciones($items) {
    $resumen = array(
      "total_en_pagina" => count($items),
      "nuevas" => 0,
      "en_seguimiento" => 0,
      "convertidas" => 0,
      "descartadas" => 0
    );
    foreach ($items as $item) {
      $estatus = $this->valor($item, "estatus", "");
      if (in_array($estatus, array("recibida_whatsapp", "nueva", "borrador"), true)) { $resumen["nuevas"]++; }
      if (in_array($estatus, array("seguimiento", "en_seguimiento"), true)) { $resumen["en_seguimiento"]++; }
      if (in_array($estatus, array("convertida_pedido", "convertida_venta"), true)) { $resumen["convertidas"]++; }
      if (in_array($estatus, array("descartada", "cancelada"), true)) { $resumen["descartadas"]++; }
    }
    return $resumen;
  }

  private function guardrailsBandejaCotizaciones() {
    return array(
      "read_only" => true,
      "no_cambia_estatus" => true,
      "no_crea_pedido" => true,
      "no_crea_venta" => true,
      "no_descuenta_inventario" => true,
      "conversion_manual_futura" => true,
      "no_cliente_crm_automatico" => true
    );
  }

  private function estatusDestinoAccionCotizacion($accion) {
    $mapa = array(
      "marcar_seguimiento" => "en_seguimiento",
      "descartar" => "descartada",
      "preparar_pedido_manual" => "preparando_pedido",
      "preparar_venta_pos_manual" => "preparando_venta_pos"
    );
    return isset($mapa[$accion]) ? $mapa[$accion] : "";
  }

  private function eventoAccionCotizacion($accion) {
    $mapa = array(
      "marcar_seguimiento" => "seguimiento_iniciado",
      "descartar" => "descartada",
      "preparar_pedido_manual" => "preparacion_pedido_manual",
      "preparar_venta_pos_manual" => "preparacion_venta_pos_manual"
    );
    return isset($mapa[$accion]) ? $mapa[$accion] : "";
  }

  private function contratoAutenticacionFutura() {
    return array(
      "estado_actual" => "no_requerida_en_fase1_readonly",
      "modo_recomendado" => "api_key_hmac_sha256",
      "headers" => array(
        "X-Ecommerce-Api-Key" => "Identificador publico del canal ecommerce. No es secreto.",
        "X-Ecommerce-Timestamp" => "Fecha/hora ISO-8601 UTC; ventana recomendada 5 minutos.",
        "X-Ecommerce-Nonce" => "Valor unico por request para reducir replay.",
        "X-Ecommerce-Signature" => "HMAC-SHA256 en hex sobre la base canonica."
      ),
      "firma_canonica" => array(
        "linea_1" => "HTTP_METHOD",
        "linea_2" => "REQUEST_PATH",
        "linea_3" => "QUERY_STRING_NORMALIZADO",
        "linea_4" => "SHA256_BODY_HEX",
        "linea_5" => "X_ECOMMERCE_TIMESTAMP",
        "linea_6" => "X_ECOMMERCE_NONCE"
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

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: permitir una o varias mascotas controladas en una publicacion ecommerce.
   * Impacto: conserva filtros publicos estables sin requerir DDL inmediato para tabla relacional.
   */
  private function normalizarMascotasPublicacion($valor) {
    $mascotas = $this->decodificarMascotasPublicacion($valor);
    return implode(",", $mascotas);
  }

  private function decodificarMascotasPublicacion($valor) {
    if (is_string($valor)) {
      $decodificado = json_decode($valor, true);
      if (is_array($decodificado)) {
        $valor = $decodificado;
      } else {
        $valor = preg_split('/[\r\n,]+/', $valor);
      }
    }
    if (!is_array($valor)) {
      $valor = array($valor);
    }
    $permitidas = array("perro", "gato", "pez", "ave", "reptil", "roedor", "otra");
    $limpias = array();
    foreach ($valor as $mascota) {
      $m = $this->limpiarFiltroPublico($mascota);
      if ($m === "") { continue; }
      if (!in_array($m, $permitidas, true)) {
        $m = "otra";
      }
      if (!in_array($m, $limpias, true)) {
        $limpias[] = $m;
      }
      if (count($limpias) >= 4) {
        break;
      }
    }
    return $limpias;
  }

  private function agruparMascotasFiltro($filas) {
    $defaults = $this->taxonomiaMascotasDefault();
    $etiquetas = array();
    foreach ($defaults["mascotas"] as $mascota) {
      $etiquetas[$mascota["codigo"]] = $mascota["nombre"];
    }
    $conteo = array();
    foreach ((array) $filas as $fila) {
      foreach ($this->decodificarMascotasPublicacion($this->valor($fila, "valor", "")) as $mascota) {
        if (!isset($conteo[$mascota])) {
          $conteo[$mascota] = 0;
        }
        $conteo[$mascota]++;
      }
    }
    $salida = array();
    foreach ($conteo as $valor => $total) {
      $salida[] = array(
        "valor" => $valor,
        "etiqueta" => isset($etiquetas[$valor]) ? $etiquetas[$valor] : $valor,
        "total" => $total
      );
    }
    usort($salida, function($a, $b) {
      return strcmp($a["etiqueta"], $b["etiqueta"]);
    });
    return $salida;
  }

  private function mascotaPrincipalPublicacion($valor) {
    $mascotas = $this->decodificarMascotasPublicacion($valor);
    if (empty($mascotas)) {
      return "";
    }
    return $mascotas[0];
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: exponer al panel interno listas controladas para clasificar publicaciones ecommerce.
   * Impacto: reemplaza inputs abiertos por select/checkboxes sin crear tablas nuevas.
   */
  private function taxonomiaPublicacionControlada() {
    $defaults = $this->taxonomiaMascotasDefault();
    return array(
      "mascotas" => $defaults["mascotas"],
      "necesidades" => $defaults["necesidades"],
      "guardrails" => array(
        "mascota_no_es_texto_libre" => true,
        "mascota_permite_multiple" => true,
        "necesidades_no_son_texto_libre" => true,
        "categorias_vienen_del_catalogo_erp" => true,
        "presentacion_publica_es_texto_comercial_opcional" => true
      )
    );
  }

  private function normalizarIdsSkuLote($valor) {
    if (is_string($valor)) {
      $decodificado = json_decode($valor, true);
      $valor = is_array($decodificado) ? $decodificado : preg_split('/[\r\n,]+/', $valor);
    }
    if (!is_array($valor)) {
      return array();
    }
    $ids = array();
    foreach ($valor as $id) {
      $idSku = intval($id);
      if ($idSku > 0 && !in_array($idSku, $ids, true)) {
        $ids[] = $idSku;
      }
      if (count($ids) >= 100) {
        break;
      }
    }
    return $ids;
  }

  private function booleanoPublicacion($valor) {
    if (is_bool($valor)) {
      return $valor ? 1 : 0;
    }
    $valor = strtolower(trim((string) $valor));
    return in_array($valor, array("1", "true", "si", "on", "yes"), true) ? 1 : 0;
  }

  private function jsonArray($valor) {
    if (is_array($valor)) {
      return $valor;
    }
    $decodificado = json_decode((string) $valor, true);
    return is_array($decodificado) ? $decodificado : array();
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

  private function contenidoAdminPaginaDesdeBd($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return null;
      }

      $pagina = $this->limpiarCodigoCms($this->valor($opciones, "pagina", "home"), 60);
      if ($pagina === "") { $pagina = "home"; }
      $plantillaCodigo = $this->limpiarCodigoCms($this->valor($opciones, "plantilla", "artiani_default"), 80);
      if ($plantillaCodigo === "") { $plantillaCodigo = "artiani_default"; }
      $contexto = $this->cmsContextoContenido($pagina, $this->valor($opciones, "contexto_clave", $this->valor($opciones, "categoria", "")));

      $stmtPlantilla = $db->prepare("SELECT id_plantilla, codigo FROM erp_ecommerce_plantillas WHERE codigo=:codigo AND activa=1 LIMIT 1");
      $stmtPlantilla->execute(array(":codigo" => $plantillaCodigo));
      $plantilla = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
      if (!$plantilla) {
        return null;
      }

      $slotsDef = $this->cmsContenidoSlotsDesdeBd($db, (int) $plantilla["id_plantilla"]);
      $slots = array();
      $bloquesTotal = 0;
      foreach ($slotsDef as $slotDef) {
        if ((string) $slotDef["pagina"] !== $pagina) {
          continue;
        }
        $bloques = $this->cmsBloquesPublicadosAdminSlot($db, (int) $plantilla["id_plantilla"], (string) $slotDef["codigo"], $pagina, $contexto);
        $bloquesTotal += count($bloques);
        $slots[] = array(
          "slot" => (string) $slotDef["codigo"],
          "nombre" => (string) $slotDef["nombre"],
          "contexto" => $contexto,
          "bloques" => $bloques
        );
      }

      if ($bloquesTotal <= 0) {
        return null;
      }

      $publicaDefault = $this->contenidoPaginaPublica($opciones);
      $defaultDepurar = $this->valor($publicaDefault, "depurar", array());
      $plantillaVista = $this->valor($defaultDepurar, "plantilla_vista", $this->plantillaVistaPaginaDefault($pagina));

      return array(
        "pagina" => $pagina,
        "plantilla" => $plantillaCodigo,
        "contexto" => $contexto,
        "fuente" => "bd_admin_publicaciones",
        "version_contenido" => "admin-bd-" . date("Ymd"),
        "plantilla_vista" => $plantillaVista,
        "slots" => $slots,
        "resumen" => array(
          "slots_total" => count($slots),
          "bloques_total" => $bloquesTotal,
          "tiene_hero" => $this->contenidoTieneSlot($slots, "home.hero") || $this->contenidoTieneSlot($slots, "categoria.banner"),
          "api_publica_conectada" => false
        ),
        "links" => $this->valor($defaultDepurar, "links", array()),
        "admin" => array(
          "modo" => "preview_bd_interno",
          "previsualizacion_json" => true,
          "fuente_actual" => "bd_admin_publicaciones",
          "publica_api" => false
        ),
        "guardrails" => array_merge($this->contenidoGuardrailsAdmin(), array(
          "read_only" => false,
          "no_escribe_bd" => false,
          "solo_preview_admin" => true,
          "api_publica_sigue_fallback_hasta_publicar" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true
        ))
      );
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: leer pagina CMS publicada desde BD para la API publica ecommerce.
   * Impacto: Frontend ecommerce; permite renderizar home/categorias/catalogo con contenido publicado sin leer archivos ERP.
   * Contrato: solo lectura; devuelve null para fallback default si no hay publicaciones publicadas/vigentes.
   */
  private function contenidoPaginaPublicaDesdeBd($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return null;
      }

      $pagina = $this->limpiarCodigoCms($this->valor($opciones, "pagina", "home"), 60);
      if ($pagina === "") { $pagina = "home"; }
      if (!in_array($pagina, array("home", "categoria", "catalogo"), true)) {
        $pagina = "home";
      }
      $plantillaCodigo = $this->limpiarCodigoCms($this->valor($opciones, "plantilla", "artiani_default"), 80);
      if ($plantillaCodigo === "") { $plantillaCodigo = "artiani_default"; }
      $categoria = $this->limpiarFiltroPublico($this->valor($opciones, "categoria", ""));
      $contexto = $this->cmsContextoContenido($pagina, $this->valor($opciones, "contexto_clave", $categoria));

      $stmtPlantilla = $db->prepare("SELECT id_plantilla, codigo FROM erp_ecommerce_plantillas WHERE codigo=:codigo AND activa=1 LIMIT 1");
      $stmtPlantilla->execute(array(":codigo" => $plantillaCodigo));
      $plantilla = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
      if (!$plantilla) {
        return null;
      }

      $slotsDef = $this->cmsContenidoSlotsDesdeBd($db, (int) $plantilla["id_plantilla"]);
      $slots = array();
      $bloquesTotal = 0;
      foreach ($slotsDef as $slotDef) {
        if ((string) $slotDef["pagina"] !== $pagina) {
          continue;
        }
        $bloques = $this->cmsBloquesPublicosSlot($db, (int) $plantilla["id_plantilla"], (string) $slotDef["codigo"], $pagina, $contexto);
        $bloquesTotal += count($bloques);
        $slots[] = array(
          "slot" => (string) $slotDef["codigo"],
          "nombre" => (string) $slotDef["nombre"],
          "contexto" => $contexto,
          "bloques" => $bloques
        );
      }

      if ($bloquesTotal <= 0) {
        return null;
      }

      return array(
        "pagina" => $pagina,
        "plantilla" => $plantillaCodigo,
        "categoria" => $categoria,
        "contexto" => $contexto,
        "fuente" => "bd_publicada",
        "editable_desde_panel" => true,
        "panel_pendiente" => false,
        "version_contenido" => "bd-publicada-" . date("Ymd"),
        "plantilla_vista" => $this->plantillaVistaPublicaDesdeBd($pagina, $contexto),
        "slots" => $slots,
        "resumen" => array(
          "slots_total" => count($slots),
          "bloques_total" => $bloquesTotal,
          "tiene_hero" => $this->contenidoTieneSlot($slots, "home.hero") || $this->contenidoTieneSlot($slots, "categoria.banner"),
          "contenido_publicado_bd" => true
        ),
        "links" => array(
          "manifest" => "/ecommercePublico/contenido_manifest?plantilla=" . $plantillaCodigo,
          "configuracion_inicial" => "/ecommercePublico/configuracion_inicial",
          "catalogo" => "/ecommercePublico/catalogo",
          "secciones" => "/ecommercePublico/secciones"
        ),
        "guardrails" => array(
          "read_only" => true,
          "no_escribe_bd" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true,
          "no_checkout" => true,
          "solo_publicado" => true,
          "solo_vigente" => true,
          "fallback_default_si_no_hay_publicado" => true,
          "frontend_renderiza_plantilla_vista" => true
        )
      );
    } catch (Exception $e) {
      return null;
    }
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: leer manifest publico CMS desde estructura BD cuando existe semilla valida.
   * Impacto: Frontend ecommerce; publica slots, tipos y componentes permitidos sin metadatos internos de administracion.
   * Contrato: solo lectura; no revela borradores ni endpoints internos `/cms/*`.
   */
  private function contenidoManifestPublicoDesdeBd($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return null;
      }

      $plantillaCodigo = $this->limpiarCodigoCms($this->valor($opciones, "plantilla", "artiani_default"), 80);
      if ($plantillaCodigo === "") { $plantillaCodigo = "artiani_default"; }
      $stmtPlantilla = $db->prepare("SELECT * FROM erp_ecommerce_plantillas WHERE codigo=:codigo AND activa=1 LIMIT 1");
      $stmtPlantilla->execute(array(":codigo" => $plantillaCodigo));
      $plantilla = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
      if (!$plantilla) {
        return null;
      }

      $slots = $this->cmsContenidoSlotsDesdeBd($db, (int) $plantilla["id_plantilla"]);
      if (empty($slots)) {
        return null;
      }
      $frontend = $this->frontendPlantillasAdminManifestDesdeBd();
      $plantillasVista = $frontend !== null ? $this->valor($frontend, "plantillas_vista", array()) : array(
        $this->plantillaVistaPaginaDefault("home"),
        $this->plantillaVistaPaginaDefault("categoria"),
        $this->plantillaVistaPaginaDefault("catalogo")
      );
      $componentesFrontend = $frontend !== null ? $this->valor($frontend, "componentes", array()) : $this->componentesFrontendDefault();
      $temaActivo = $frontend !== null ? $this->valor($frontend, "tema_activo", array()) : array(
        "codigo" => "wokiee_artiani",
        "nombre" => "Wokiee Artiani",
        "proveedor" => "ThemeForest/Wokiee",
        "estado" => "fallback_default"
      );

      return array(
        "cms" => array(
          "fase" => "fase_10_cms_contenido_publicado_bd",
          "estado" => "estructura_bd_con_lectura_publica",
          "headless" => true,
          "panel_pendiente" => false,
          "endpoint_principal" => "/ecommercePublico/contenido_pagina"
        ),
        "plantilla_activa" => $plantillaCodigo,
        "tema_visual_activo" => $temaActivo,
        "plantillas" => array(
          array(
            "codigo" => (string) $plantilla["codigo"],
            "nombre" => (string) $plantilla["nombre"],
            "descripcion" => (string) $plantilla["descripcion"],
            "version" => (string) $plantilla["version_plantilla"],
            "estatus" => (string) $plantilla["estatus"],
            "fuente" => "bd_publicada",
            "slots" => $slots
          )
        ),
        "tipos_bloque" => $this->contenidoTiposBloqueDefault(),
        "plantillas_vista" => $plantillasVista,
        "componentes_frontend" => $componentesFrontend,
        "paginas_soportadas" => array(
          array("codigo" => "home", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=home&plantilla=" . $plantillaCodigo),
          array("codigo" => "categoria", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug_categoria}&plantilla=" . $plantillaCodigo),
          array("codigo" => "catalogo", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=catalogo&plantilla=" . $plantillaCodigo)
        ),
        "parametros" => array(
          "pagina" => "home|categoria|catalogo",
          "plantilla" => "artiani_default por defecto",
          "categoria" => "slug/codigo de categoria cuando pagina=categoria"
        ),
        "guardrails" => array(
          "read_only" => true,
          "no_escribe_bd" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true,
          "frontend_renderiza_plantilla" => true,
          "frontend_renderiza_plantilla_vista" => true,
          "erp_entrega_contenido_json" => true,
          "solo_publicado_vigente_en_pagina" => true,
          "no_expone_endpoints_admin" => true
        )
      );
    } catch (Exception $e) {
      return null;
    }
  }

  private function cmsBloquesPublicadosAdminSlot($db, $idPlantilla, $slotCodigo, $pagina, $contexto) {
    $stmt = $db->prepare(
      "SELECT pub.id_publicacion_contenido, pub.orden, pub.estatus AS estatus_publicacion, pub.vigente_desde, pub.vigente_hasta, pub.contexto_clave, b.id_bloque, b.tipo_bloque, b.codigo, b.titulo, b.payload_json, b.estatus AS estatus_bloque " .
      "FROM erp_ecommerce_contenido_publicaciones pub " .
      "INNER JOIN erp_ecommerce_plantilla_slots s ON s.id_slot=pub.id_slot " .
      "INNER JOIN erp_ecommerce_contenido_bloques b ON b.id_bloque=pub.id_bloque " .
      "WHERE pub.id_plantilla=:plantilla AND s.codigo=:slot AND pub.pagina=:pagina AND pub.contexto_clave=:contexto AND pub.estatus IN ('borrador','pausado','publicado') " .
      "ORDER BY pub.orden ASC, pub.id_publicacion_contenido ASC"
    );
    $stmt->execute(array(":plantilla" => (int) $idPlantilla, ":slot" => $slotCodigo, ":pagina" => $pagina, ":contexto" => $contexto));
    $bloques = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $payload = $this->jsonArray($row["payload_json"]);
      unset($payload["_cms_guardrails"]);
      $payload["id"] = "bd-" . (int) $row["id_bloque"];
      $payload["id_bloque"] = (int) $row["id_bloque"];
      $payload["id_publicacion_contenido"] = (int) $row["id_publicacion_contenido"];
      $payload["codigo"] = (string) $row["codigo"];
      $payload["tipo"] = (string) $row["tipo_bloque"];
      $payload["estatus"] = (string) $row["estatus_publicacion"];
      if (!isset($payload["titulo"]) || trim((string) $payload["titulo"]) === "") {
        $payload["titulo"] = (string) $row["titulo"];
      }
      $payload["vigencia"] = array("desde" => (string) $row["vigente_desde"], "hasta" => (string) $row["vigente_hasta"]);
      $payload["publicacion"] = array(
        "id_publicacion_contenido" => (int) $row["id_publicacion_contenido"],
        "orden" => (int) $row["orden"],
        "estatus" => (string) $row["estatus_publicacion"],
        "contexto_clave" => (string) $row["contexto_clave"],
        "fuente" => "bd_admin_publicaciones",
        "publica_api" => false
      );
      $bloques[] = $payload;
    }
    return $bloques;
  }

  private function cmsBloquesPublicosSlot($db, $idPlantilla, $slotCodigo, $pagina, $contexto) {
    $stmt = $db->prepare(
      "SELECT pub.id_publicacion_contenido, pub.orden, pub.estatus AS estatus_publicacion, pub.vigente_desde, pub.vigente_hasta, pub.contexto_clave, b.id_bloque, b.tipo_bloque, b.codigo, b.titulo, b.payload_json, b.estatus AS estatus_bloque " .
      "FROM erp_ecommerce_contenido_publicaciones pub " .
      "INNER JOIN erp_ecommerce_plantilla_slots s ON s.id_slot=pub.id_slot " .
      "INNER JOIN erp_ecommerce_contenido_bloques b ON b.id_bloque=pub.id_bloque " .
      "WHERE pub.id_plantilla=:plantilla AND s.codigo=:slot AND pub.pagina=:pagina AND pub.contexto_clave=:contexto AND pub.estatus='publicado' AND b.estatus<>'pausado' " .
      "AND (pub.vigente_desde IS NULL OR pub.vigente_desde<=NOW()) AND (pub.vigente_hasta IS NULL OR pub.vigente_hasta>=NOW()) " .
      "ORDER BY pub.orden ASC, pub.id_publicacion_contenido ASC"
    );
    $stmt->execute(array(":plantilla" => (int) $idPlantilla, ":slot" => $slotCodigo, ":pagina" => $pagina, ":contexto" => $contexto));
    $bloques = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $payload = $this->jsonArray($row["payload_json"]);
      unset($payload["_cms_guardrails"]);
      $payload["id"] = "cms-" . (int) $row["id_publicacion_contenido"];
      $payload["codigo"] = (string) $row["codigo"];
      $payload["tipo"] = (string) $row["tipo_bloque"];
      $payload["estatus"] = "publicado";
      if (!isset($payload["titulo"]) || trim((string) $payload["titulo"]) === "") {
        $payload["titulo"] = (string) $row["titulo"];
      }
      $payload["vigencia"] = array("desde" => (string) $row["vigente_desde"], "hasta" => (string) $row["vigente_hasta"]);
      $payload["publicacion"] = array(
        "orden" => (int) $row["orden"],
        "estatus" => "publicado",
        "contexto_clave" => (string) $row["contexto_clave"],
        "fuente" => "bd_publicada",
        "publica_api" => true
      );
      $bloques[] = $payload;
    }
    return $bloques;
  }

  private function plantillaVistaPublicaDesdeBd($pagina, $contexto) {
    $frontend = $this->frontendPlantillasAdminManifestDesdeBd();
    if ($frontend === null) {
      return $this->plantillaVistaPaginaDefault($pagina);
    }
    $activaciones = $this->valor($frontend, "activaciones", array());
    $codigo = $this->cmsFrontendPlantillaActivaPorPagina($activaciones, $pagina, $this->valor($this->plantillaVistaPaginaDefault($pagina), "codigo", ""));
    foreach ((array) $this->valor($frontend, "plantillas_vista", array()) as $plantilla) {
      if ((string) $this->valor($plantilla, "codigo", "") === (string) $codigo) {
        return $plantilla;
      }
    }
    return $this->plantillaVistaPaginaDefault($pagina);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: leer la estructura CMS de contenido desde BD para el panel interno.
   * Impacto: habilita transicion a persistencia real sin cambiar todavia la API publica ni activar POST.
   * Contrato: read-only; si la semilla no existe devuelve null para usar fallback default.
   */
  private function contenidoAdminManifestDesdeBd($opciones = array()) {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsContenidoDisponibles($db)) {
        return null;
      }

      $plantillaCodigo = $this->limpiarFiltroPublico($this->valor($opciones, "plantilla", "artiani_default"));
      if ($plantillaCodigo === "") { $plantillaCodigo = "artiani_default"; }

      $stmtPlantilla = $db->prepare("SELECT * FROM erp_ecommerce_plantillas WHERE codigo=:codigo AND activa=1 LIMIT 1");
      $stmtPlantilla->execute(array(":codigo" => $plantillaCodigo));
      $plantilla = $stmtPlantilla->fetch(PDO::FETCH_ASSOC);
      if (!$plantilla) {
        return null;
      }

      $slots = $this->cmsContenidoSlotsDesdeBd($db, (int) $plantilla["id_plantilla"]);
      if (empty($slots)) {
        return null;
      }

      $frontend = $this->frontendPlantillasAdminManifestDesdeBd();
      $plantillasVista = $frontend !== null ? $this->valor($frontend, "plantillas_vista", array()) : array(
        $this->plantillaVistaPaginaDefault("home"),
        $this->plantillaVistaPaginaDefault("categoria"),
        $this->plantillaVistaPaginaDefault("catalogo")
      );
      $componentesFrontend = $frontend !== null ? $this->valor($frontend, "componentes", array()) : $this->componentesFrontendDefault();
      $temaActivo = $frontend !== null ? $this->valor($frontend, "tema_activo", array()) : array(
        "codigo" => "wokiee_artiani",
        "nombre" => "Wokiee Artiani",
        "proveedor" => "ThemeForest/Wokiee",
        "estado" => "fallback_default"
      );

      $depurar = array(
        "cms" => array(
          "fase" => "fase_9_cms_contenido_bd_interno",
          "estado" => "estructura_bd_seed_con_bloques_y_publicaciones_internas",
          "headless" => true,
          "panel_pendiente" => false,
          "endpoint_principal" => "/ecommercePublico/contenido_pagina"
        ),
        "plantilla_activa" => $plantillaCodigo,
        "tema_visual_activo" => $temaActivo,
        "plantillas" => array(
          array(
            "codigo" => (string) $plantilla["codigo"],
            "nombre" => (string) $plantilla["nombre"],
            "descripcion" => (string) $plantilla["descripcion"],
            "version" => (string) $plantilla["version_plantilla"],
            "estatus" => (string) $plantilla["estatus"],
            "fuente" => "bd_seed",
            "slots" => $slots
          )
        ),
        "tipos_bloque" => $this->contenidoTiposBloqueDefault(),
        "plantillas_vista" => $plantillasVista,
        "componentes_frontend" => $componentesFrontend,
        "paginas_soportadas" => array(
          array("codigo" => "home", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=home&plantilla=" . $plantillaCodigo),
          array("codigo" => "categoria", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=categoria&categoria={slug_categoria}&plantilla=" . $plantillaCodigo),
          array("codigo" => "catalogo", "endpoint" => "/ecommercePublico/contenido_pagina?pagina=catalogo&plantilla=" . $plantillaCodigo)
        ),
        "parametros" => array(
          "pagina" => "home|categoria|catalogo",
          "plantilla" => "artiani_default por defecto",
          "categoria" => "slug/codigo de categoria cuando pagina=categoria"
        ),
        "admin" => array(
          "modo" => "persistencia_contenido_interna",
          "fuente_estructura" => "bd_seed",
          "puede_guardar" => true,
          "puede_guardar_bloques" => true,
          "puede_colocar_slots" => true,
          "puede_publicar" => true,
          "vista" => "/cms/contenido",
          "pendiente_persistencia" => false,
          "pendiente_endpoints_post" => array("media", "frontend")
        ),
        "guardrails" => array_merge($this->contenidoGuardrailsAdmin(), array(
          "read_only" => false,
          "no_escribe_bd" => false,
          "persistencia_contenido_interna" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true,
          "publicaciones_internas_controladas" => true,
          "frontend_renderiza_plantilla" => true,
          "frontend_renderiza_plantilla_vista" => true,
          "erp_entrega_contenido_json" => true,
          "api_publica_sigue_fallback_hasta_contenido_publicado" => true
        ))
      );

      return $depurar;
    } catch (Exception $e) {
      return null;
    }
  }

  private function cmsContenidoSlotsDesdeBd($db, $idPlantilla) {
    $stmt = $db->prepare("SELECT codigo, nombre, pagina, tipos_bloque_json, max_bloques, requerido, orden, estatus FROM erp_ecommerce_plantilla_slots WHERE id_plantilla=:id AND estatus='activo' ORDER BY pagina ASC, orden ASC, id_slot ASC");
    $stmt->execute(array(":id" => (int) $idPlantilla));
    $slots = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $slots[] = array(
        "codigo" => (string) $row["codigo"],
        "nombre" => (string) $row["nombre"],
        "pagina" => (string) $row["pagina"],
        "tipos" => $this->jsonArray($row["tipos_bloque_json"]),
        "max_bloques" => (int) $row["max_bloques"],
        "requerido" => ((int) $row["requerido"]) === 1,
        "orden" => (int) $row["orden"],
        "estatus" => (string) $row["estatus"],
        "fuente" => "bd_seed"
      );
    }
    return $slots;
  }

  private function tablasCmsContenidoDisponibles($db) {
    $tablas = array(
      "erp_ecommerce_plantillas",
      "erp_ecommerce_plantilla_slots",
      "erp_ecommerce_contenido_bloques",
      "erp_ecommerce_contenido_publicaciones",
      "erp_ecommerce_contenido_media"
    );
    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) {
        return false;
      }
    }
    return true;
  }

  private function contenidoTiposBloqueCodigos() {
    $codigos = array();
    foreach ($this->contenidoTiposBloqueDefault() as $tipo) {
      $codigo = isset($tipo["tipo"]) ? (string) $tipo["tipo"] : "";
      if ($codigo !== "") {
        $codigos[] = $codigo;
      }
    }
    return $codigos;
  }

  private function limpiarCodigoCms($valor, $limite = 120) {
    $valor = strtolower(trim((string) $valor));
    $valor = preg_replace('/[^a-z0-9_\.\-]/', '_', $valor);
    $valor = preg_replace('/_+/', '_', $valor);
    $valor = trim($valor, '_.-');
    return substr($valor, 0, (int) $limite);
  }

  private function cmsPayloadSeguroParaGuardar($tipo, $payload) {
    if ($tipo !== "content_html_safe") {
      return true;
    }
    $html = (string) $this->valor($payload, "contenido_html", $this->valor($payload, "payload_local", ""));
    return !$this->cmsTextoTieneHtmlPeligroso($html);
  }

  private function cmsTextoTieneHtmlPeligroso($texto) {
    $texto = strtolower((string) $texto);
    if (strpos($texto, "<script") !== false || strpos($texto, "</script") !== false) {
      return true;
    }
    if (preg_match('/\son[a-z0-9_-]+\s*=/i', $texto)) {
      return true;
    }
    if (strpos($texto, "javascript:") !== false) {
      return true;
    }
    return false;
  }

  private function cmsValidarPublicacionAntesDePublicar($publicacion) {
    $errores = array();
    $alertas = array();
    $tipo = (string) $this->valor($publicacion, "tipo_bloque", "");
    $slot = (string) $this->valor($publicacion, "slot_codigo", "");
    $payload = $this->jsonArray($this->valor($publicacion, "payload_json", "{}"));
    $tiposSlot = $this->jsonArray($this->valor($publicacion, "tipos_bloque_json", "[]"));
    $prefijo = $slot . " (" . ($tipo !== "" ? $tipo : "sin_tipo") . ")";

    if ($tipo === "" || !in_array($tipo, $this->contenidoTiposBloqueCodigos(), true)) {
      $errores[] = $prefijo . ": tipo de bloque no permitido.";
    }
    if (!empty($tiposSlot) && !in_array($tipo, $tiposSlot, true)) {
      $errores[] = $prefijo . ": tipo no compatible con el slot.";
    }

    $titulo = trim((string) $this->valor($payload, "titulo", $this->valor($publicacion, "titulo", "")));
    $texto = trim((string) $this->valor($payload, "texto", $this->valor($payload, "subtitulo", "")));
    if ($titulo === "" && $texto === "") {
      $errores[] = $prefijo . ": falta titulo o texto principal.";
    }

    $desde = $this->valor($publicacion, "vigente_desde", null);
    $hasta = $this->valor($publicacion, "vigente_hasta", null);
    if ($desde && $hasta && strtotime((string) $desde) > strtotime((string) $hasta)) {
      $errores[] = $prefijo . ": vigencia hasta es menor que vigencia desde.";
    }

    if (in_array($tipo, array("hero_banner", "category_banner"), true)) {
      $alt = trim((string) $this->valor($payload, array("media", "alt"), ""));
      $desktop = trim((string) $this->valor($payload, array("media", "imagen_desktop"), ""));
      $mobile = trim((string) $this->valor($payload, array("media", "imagen_mobile"), ""));
      if ($alt === "") {
        $errores[] = $prefijo . ": falta alt text de imagen.";
      }
      if ($desktop === "") {
        $alertas[] = $prefijo . ": falta imagen desktop real.";
      }
      if ($mobile === "") {
        $alertas[] = $prefijo . ": falta imagen mobile.";
      }
    }

    if ($tipo === "product_collection") {
      $endpoint = trim((string) $this->valor($payload, array("source", "endpoint"), ""));
      if ($endpoint === "") {
        $errores[] = $prefijo . ": falta endpoint source.";
      }
      if ($endpoint !== "" && strpos($endpoint, "/ecommercePublico/") !== 0) {
        $alertas[] = $prefijo . ": el endpoint source no inicia con /ecommercePublico/.";
      }
    }

    if ($tipo === "content_html_safe") {
      $html = (string) $this->valor($payload, "contenido_html", $this->valor($payload, "payload_local", ""));
      if ($this->cmsTextoTieneHtmlPeligroso($html)) {
        $errores[] = $prefijo . ": contiene HTML no permitido.";
      }
    }

    if ($tipo === "image_card_grid") {
      $items = $this->valor($payload, "items", array());
      if (!is_array($items) || count($items) <= 0) {
        $errores[] = $prefijo . ": falta al menos una card.";
      }
    }

    return array("errores" => array_values(array_unique($errores)), "alertas" => array_values(array_unique($alertas)));
  }

  private function codigoBloqueCmsUnico($db, $tipo, $textoBase, $idBloque = 0) {
    $base = $this->limpiarCodigoCms($tipo . "_" . $textoBase, 90);
    if ($base === "") {
      $base = "bloque_cms";
    }
    $codigo = $base;
    $contador = 1;
    while ($this->cmsCodigoBloqueExiste($db, $codigo, $idBloque)) {
      $contador++;
      $codigo = substr($base, 0, 90) . "_" . date("YmdHis") . "_" . $contador;
      if ($contador > 20) {
        $codigo = substr($base, 0, 80) . "_" . uniqid();
        break;
      }
    }
    return substr($codigo, 0, 120);
  }

  private function cmsCodigoBloqueExiste($db, $codigo, $idBloque = 0) {
    if ($codigo === "") {
      return false;
    }
    $stmt = $db->prepare("SELECT id_bloque FROM erp_ecommerce_contenido_bloques WHERE codigo=:codigo AND id_bloque<>:id LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo, ":id" => intval($idBloque)));
    return (bool) $stmt->fetchColumn();
  }

  private function cmsContextoContenido($pagina, $contexto) {
    $contexto = $this->limpiarCodigoCms($contexto, 120);
    if ($pagina === "categoria") {
      return $contexto !== "" ? $contexto : "peces";
    }
    return "*";
  }

  private function cmsFechaSql($valor) {
    $valor = trim((string) $valor);
    if ($valor === "") {
      return null;
    }
    $valor = str_replace("T", " ", $valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $valor)) {
      $valor .= ":00";
    }
    return preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $valor) ? $valor : null;
  }

  private function cmsSiguienteOrdenPublicacion($db, $idPlantilla, $idSlot, $pagina, $contexto, $canal) {
    $stmt = $db->prepare("SELECT COALESCE(MAX(orden), 0) + 1 FROM erp_ecommerce_contenido_publicaciones WHERE id_plantilla=:plantilla AND id_slot=:slot AND pagina=:pagina AND contexto_clave=:contexto AND canal=:canal");
    $stmt->execute(array(":plantilla" => (int) $idPlantilla, ":slot" => (int) $idSlot, ":pagina" => $pagina, ":contexto" => $contexto, ":canal" => $canal));
    return max(1, (int) $stmt->fetchColumn());
  }

  private function cmsConteoPublicacionesSlot($db, $idPlantilla, $idSlot, $pagina, $contexto, $canal, $exceptoId = 0) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ecommerce_contenido_publicaciones WHERE id_plantilla=:plantilla AND id_slot=:slot AND pagina=:pagina AND contexto_clave=:contexto AND canal=:canal AND estatus IN ('borrador','pausado','publicado') AND id_publicacion_contenido<>:excepto");
    $stmt->execute(array(":plantilla" => (int) $idPlantilla, ":slot" => (int) $idSlot, ":pagina" => $pagina, ":contexto" => $contexto, ":canal" => $canal, ":excepto" => (int) $exceptoId));
    return (int) $stmt->fetchColumn();
  }

  private function cmsPublicacionExistente($db, $idPlantilla, $idSlot, $idBloque, $pagina, $contexto, $canal) {
    $stmt = $db->prepare("SELECT id_publicacion_contenido FROM erp_ecommerce_contenido_publicaciones WHERE id_plantilla=:plantilla AND id_slot=:slot AND id_bloque=:bloque AND pagina=:pagina AND contexto_clave=:contexto AND canal=:canal LIMIT 1");
    $stmt->execute(array(":plantilla" => (int) $idPlantilla, ":slot" => (int) $idSlot, ":bloque" => (int) $idBloque, ":pagina" => $pagina, ":contexto" => $contexto, ":canal" => $canal));
    return (int) $stmt->fetchColumn();
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: leer plantillas visuales CMS frontend desde BD para el builder administrativo.
   * Impacto: permite administrar temas/plantillas como datos sin editar archivos del frontend.
   * Contrato: read-only; si la estructura no esta disponible devuelve null para fallback default.
   */
  private function frontendPlantillasAdminManifestDesdeBd() {
    try {
      $db = $this->getConexion();
      if (!$db || !$this->tablasCmsFrontendDisponibles($db)) {
        return null;
      }

      $stmtTema = $db->query("SELECT * FROM erp_ecommerce_frontend_temas WHERE activo=1 AND estatus IN ('publicado','activo') ORDER BY id_tema ASC LIMIT 1");
      $tema = $stmtTema ? $stmtTema->fetch(PDO::FETCH_ASSOC) : false;
      if (!$tema) {
        return null;
      }

      $temaActivo = $this->cmsFrontendTemaPayload($tema, "activo_bd");
      $temasDisponibles = $this->cmsFrontendTemasDesdeBd($db);
      $layouts = $this->cmsFrontendLayoutsDesdeBd($db, (int) $tema["id_tema"]);
      $componentes = $this->cmsFrontendComponentesDesdeBd($db, (int) $tema["id_tema"]);
      $plantillas = $this->cmsFrontendPlantillasDesdeBd($db, (int) $tema["id_tema"]);
      $activaciones = $this->cmsFrontendActivacionesDesdeBd($db);

      if (empty($layouts) || empty($componentes) || empty($plantillas) || empty($activaciones)) {
        return null;
      }

      return array(
        "modo" => "readonly",
        "fase" => "cms_frontend_plantillas_bd_seed",
        "fuente_estructura" => "bd_seed",
        "tema_activo" => $temaActivo,
        "temas_disponibles" => $temasDisponibles,
        "plantilla_activa_home" => $this->cmsFrontendPlantillaActivaPorPagina($activaciones, "home", "wokiee_home_default"),
        "activaciones" => $activaciones,
        "layouts" => $layouts,
        "componentes" => $componentes,
        "plantillas_vista" => $plantillas,
        "renderer_frontend" => array(
          "consume_desde" => "/ecommercePublico/configuracion_inicial",
          "pagina" => "/ecommercePublico/contenido_pagina?pagina=home",
          "contrato" => "plantilla_vista + contenido.slots",
          "mapa_componentes_requerido" => true,
          "estructura_administrable_desde_bd" => true
        ),
        "guardrails" => array(
          "read_only" => true,
          "no_edita_archivos_frontend" => true,
          "no_html_libre" => true,
          "no_css_libre" => true,
          "no_js_libre" => true,
          "frontend_renderiza_componentes_predefinidos" => true,
          "api_publica_sigue_fallback_hasta_contenido_publicado" => true
        )
      );
    } catch (Exception $e) {
      return null;
    }
  }

  private function cmsFrontendTemaPayload($tema, $estatusAdmin) {
    return array(
      "codigo" => (string) $tema["codigo"],
      "nombre" => (string) $tema["nombre"],
      "proveedor" => (string) $tema["proveedor"],
      "estatus" => $estatusAdmin,
      "descripcion" => (string) $tema["descripcion"],
      "version" => (string) $tema["version_tema"],
      "config" => $this->jsonArray($tema["config_json"])
    );
  }

  private function cmsFrontendTemasDesdeBd($db) {
    $stmt = $db->query("SELECT * FROM erp_ecommerce_frontend_temas WHERE estatus IN ('publicado','activo') ORDER BY activo DESC, nombre ASC");
    $temas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $temas[] = $this->cmsFrontendTemaPayload($row, ((int) $row["activo"] === 1 ? "activo_bd" : "disponible_bd"));
    }
    return $temas;
  }

  private function cmsFrontendLayoutsDesdeBd($db, $idTema) {
    $stmt = $db->prepare("SELECT codigo FROM erp_ecommerce_frontend_layouts WHERE id_tema=:id_tema AND estatus IN ('publicado','activo') ORDER BY codigo ASC");
    $stmt->execute(array(":id_tema" => (int) $idTema));
    $layouts = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $layouts[] = (string) $row["codigo"];
    }
    return $layouts;
  }

  private function cmsFrontendComponentesDesdeBd($db, $idTema) {
    $stmt = $db->prepare("SELECT codigo, nombre, bloques_permitidos_json, variantes_json, slots_compatibles_json FROM erp_ecommerce_frontend_componentes WHERE id_tema=:id_tema AND estatus='activo' ORDER BY codigo ASC");
    $stmt->execute(array(":id_tema" => (int) $idTema));
    $componentes = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $componentes[] = array(
        "codigo" => (string) $row["codigo"],
        "nombre" => (string) $row["nombre"],
        "bloques_permitidos" => $this->jsonArray($row["bloques_permitidos_json"]),
        "variantes" => $this->jsonArray($row["variantes_json"]),
        "slots_compatibles" => $this->jsonArray($row["slots_compatibles_json"]),
        "fuente" => "bd_seed"
      );
    }
    return $componentes;
  }

  private function cmsFrontendPlantillasDesdeBd($db, $idTema) {
    $stmt = $db->prepare(
      "SELECT p.id_plantilla_vista, p.codigo, p.nombre, p.pagina, p.version_plantilla, p.estatus, l.codigo AS layout_codigo " .
      "FROM erp_ecommerce_frontend_plantillas p " .
      "INNER JOIN erp_ecommerce_frontend_layouts l ON l.id_layout=p.id_layout " .
      "WHERE p.id_tema=:id_tema AND p.estatus IN ('publicado','activo') " .
      "ORDER BY p.pagina ASC, p.codigo ASC"
    );
    $stmt->execute(array(":id_tema" => (int) $idTema));
    $plantillas = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $plantillas[] = array(
        "codigo" => (string) $row["codigo"],
        "nombre" => (string) $row["nombre"],
        "pagina" => (string) $row["pagina"],
        "layout" => (string) $row["layout_codigo"],
        "estatus" => "publicado_bd_readonly",
        "version" => (string) $row["version_plantilla"],
        "fuente" => "bd_seed",
        "secciones" => $this->cmsFrontendSeccionesDesdeBd($db, (int) $row["id_plantilla_vista"])
      );
    }
    return $plantillas;
  }

  private function cmsFrontendSeccionesDesdeBd($db, $idPlantillaVista) {
    $stmt = $db->prepare(
      "SELECT s.slot_codigo, s.variante, s.orden, c.codigo AS componente_codigo " .
      "FROM erp_ecommerce_frontend_plantilla_secciones s " .
      "INNER JOIN erp_ecommerce_frontend_componentes c ON c.id_componente=s.id_componente " .
      "WHERE s.id_plantilla_vista=:id_plantilla_vista AND s.estatus='activo' " .
      "ORDER BY s.orden ASC, s.id_seccion ASC"
    );
    $stmt->execute(array(":id_plantilla_vista" => (int) $idPlantillaVista));
    $secciones = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $secciones[] = array(
        "slot" => (string) $row["slot_codigo"],
        "componente" => (string) $row["componente_codigo"],
        "variante" => (string) $row["variante"],
        "orden" => (int) $row["orden"],
        "fuente" => "bd_seed"
      );
    }
    return $secciones;
  }

  private function cmsFrontendActivacionesDesdeBd($db) {
    $stmt = $db->query(
      "SELECT a.pagina, a.canal, a.contexto_clave, a.estatus, a.vigente_desde, a.vigente_hasta, p.codigo AS plantilla_codigo, t.codigo AS tema_codigo " .
      "FROM erp_ecommerce_frontend_plantilla_activas a " .
      "INNER JOIN erp_ecommerce_frontend_plantillas p ON p.id_plantilla_vista=a.id_plantilla_vista " .
      "INNER JOIN erp_ecommerce_frontend_temas t ON t.id_tema=p.id_tema " .
      "WHERE a.estatus='activa' " .
      "ORDER BY a.pagina ASC, a.contexto_clave ASC"
    );
    $activaciones = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $pagina = (string) $row["pagina"];
      $contexto = trim((string) $row["contexto_clave"]);
      $activaciones[] = array(
        "pagina" => $pagina,
        "canal" => (string) $row["canal"],
        "contexto_clave" => $contexto !== "" ? $contexto : "*",
        "tema" => (string) $row["tema_codigo"],
        "plantilla_vista" => (string) $row["plantilla_codigo"],
        "estatus" => "activa_bd_readonly",
        "vigencia" => ($row["vigente_desde"] || $row["vigente_hasta"]) ? array("desde" => $row["vigente_desde"], "hasta" => $row["vigente_hasta"]) : "sin_vigencia",
        "endpoint_publico" => $this->cmsFrontendEndpointPublico($pagina, $contexto)
      );
    }
    return $activaciones;
  }

  private function cmsFrontendPlantillaActivaPorPagina($activaciones, $pagina, $default) {
    foreach ((array) $activaciones as $activacion) {
      if ($this->valor($activacion, "pagina", "") === $pagina) {
        return $this->valor($activacion, "plantilla_vista", $default);
      }
    }
    return $default;
  }

  private function cmsFrontendEndpointPublico($pagina, $contexto) {
    if ($pagina === "categoria") {
      $categoria = trim((string) $contexto) !== "" && $contexto !== "*" ? rawurlencode((string) $contexto) : "{slug_categoria}";
      return "/ecommercePublico/contenido_pagina?pagina=categoria&categoria=" . $categoria;
    }
    return "/ecommercePublico/contenido_pagina?pagina=" . rawurlencode((string) $pagina);
  }

  private function tablasCmsFrontendDisponibles($db) {
    $tablas = array(
      "erp_ecommerce_frontend_temas",
      "erp_ecommerce_frontend_layouts",
      "erp_ecommerce_frontend_componentes",
      "erp_ecommerce_frontend_plantillas",
      "erp_ecommerce_frontend_plantilla_secciones",
      "erp_ecommerce_frontend_plantilla_activas"
    );
    foreach ($tablas as $tabla) {
      if (!$this->tablaExiste($db, $tabla)) {
        return false;
      }
    }
    return true;
  }

  private function contenidoTiposBloqueDefault() {
    return array(
      array(
        "tipo" => "hero_banner",
        "nombre" => "Banner principal",
        "uso" => "Hero de home o landing.",
        "campos" => array("titulo", "subtitulo", "imagen_desktop", "imagen_mobile", "alt", "cta_label", "cta_url")
      ),
      array(
        "tipo" => "category_banner",
        "nombre" => "Banner de categoria",
        "uso" => "Encabezado de una categoria o coleccion.",
        "campos" => array("titulo", "subtitulo", "categoria", "imagen_desktop", "imagen_mobile", "cta_label", "cta_url")
      ),
      array(
        "tipo" => "product_collection",
        "nombre" => "Coleccion de productos",
        "uso" => "Carrusel/listado dinamico desde catalogo publico.",
        "campos" => array("titulo", "source.tipo", "source.endpoint", "limite", "cta_label", "cta_url")
      ),
      array(
        "tipo" => "promo_strip",
        "nombre" => "Franja promocional",
        "uso" => "Aviso corto de servicio, promocion o informacion operativa.",
        "campos" => array("texto", "icono", "cta_label", "cta_url")
      ),
      array(
        "tipo" => "image_card_grid",
        "nombre" => "Cuadricula de tarjetas",
        "uso" => "Tarjetas con imagen para categorias, mascotas o necesidades.",
        "campos" => array("titulo", "items[].titulo", "items[].imagen", "items[].url")
      ),
      array(
        "tipo" => "content_html_safe",
        "nombre" => "Contenido editorial seguro",
        "uso" => "Texto informativo sanitizado; no ejecutar scripts.",
        "campos" => array("titulo", "contenido_html", "cta_label", "cta_url")
      )
    );
  }

  private function contenidoSlotsDefault() {
    return array(
      array("codigo" => "home.hero", "nombre" => "Hero principal home", "pagina" => "home", "tipos" => array("hero_banner"), "max_bloques" => 1, "requerido" => true),
      array("codigo" => "home.promo", "nombre" => "Franja informativa home", "pagina" => "home", "tipos" => array("promo_strip"), "max_bloques" => 2, "requerido" => false),
      array("codigo" => "home.categorias", "nombre" => "Categorias destacadas", "pagina" => "home", "tipos" => array("image_card_grid"), "max_bloques" => 1, "requerido" => false),
      array("codigo" => "home.destacados", "nombre" => "Productos destacados", "pagina" => "home", "tipos" => array("product_collection"), "max_bloques" => 3, "requerido" => false),
      array("codigo" => "categoria.banner", "nombre" => "Banner de categoria", "pagina" => "categoria", "tipos" => array("category_banner"), "max_bloques" => 1, "requerido" => false),
      array("codigo" => "categoria.productos", "nombre" => "Productos por categoria", "pagina" => "categoria", "tipos" => array("product_collection"), "max_bloques" => 2, "requerido" => true),
      array("codigo" => "catalogo.encabezado", "nombre" => "Encabezado de catalogo", "pagina" => "catalogo", "tipos" => array("content_html_safe", "promo_strip"), "max_bloques" => 2, "requerido" => false)
    );
  }

  private function contenidoGuardrailsAdmin() {
    return array(
      "read_only" => true,
      "no_escribe_bd" => true,
      "no_ejecuta_ddl" => true,
      "no_modifica_catalogo" => true,
      "no_modifica_precios" => true,
      "no_modifica_inventario" => true,
      "no_modifica_publicaciones_producto" => true,
      "frontend_no_lee_archivos_erp" => true,
      "configuracion_inicial_endpoint_recomendado" => true,
      "bootstrap_alias_legacy" => true
    );
  }

  private function contenidoSlotsPaginaDefault($pagina, $categoria) {
    if ($pagina === "categoria") {
      $categoriaLabel = $categoria !== "" ? ucwords(str_replace(array("-", "_"), " ", $categoria)) : "Categoria";
      $endpointCategoria = ctype_digit((string) $categoria)
        ? "/ecommercePublico/catalogo?categoria=" . rawurlencode($categoria) . "&limite=12"
        : "/ecommercePublico/catalogo?q=" . rawurlencode($categoriaLabel) . "&limite=12";
      return array(
        array(
          "slot" => "categoria.banner",
          "nombre" => "Banner de categoria",
          "bloques" => array($this->bloqueCategoryBannerDefault($categoria, $categoriaLabel))
        ),
        array(
          "slot" => "categoria.productos",
          "nombre" => "Productos por categoria",
          "bloques" => array($this->bloqueProductCollectionDefault(
            "categoria-productos",
            "Productos para " . $categoriaLabel,
            $endpointCategoria,
            "/catalogo?categoria=" . rawurlencode($categoria)
          ))
        )
      );
    }
    if ($pagina === "catalogo") {
      return array(
        array(
          "slot" => "catalogo.encabezado",
          "nombre" => "Encabezado de catalogo",
          "bloques" => array(
            array(
              "id" => "catalogo-intro-default",
              "tipo" => "content_html_safe",
              "estatus" => "publicado_default",
              "titulo" => "Catalogo Artiani",
              "contenido_html" => "<p>Explora productos publicados desde el ERP. Precios y disponibilidad se confirman antes de enviar por WhatsApp.</p>",
              "cta" => array("label" => "Ver productos", "url" => "/catalogo")
            )
          )
        )
      );
    }
    return array(
      array(
        "slot" => "home.hero",
        "nombre" => "Hero principal home",
        "bloques" => array($this->bloqueHeroDefault())
      ),
      array(
        "slot" => "home.promo",
        "nombre" => "Franja informativa home",
        "bloques" => array(
          array(
            "id" => "home-promo-whatsapp-default",
            "tipo" => "promo_strip",
            "estatus" => "publicado_default",
            "texto" => "Cotiza por WhatsApp con precios y disponibilidad validados desde el ERP.",
            "icono" => "message-circle",
            "cta" => array("label" => "Ir al catalogo", "url" => "/catalogo")
          )
        )
      ),
      array(
        "slot" => "home.categorias",
        "nombre" => "Categorias destacadas",
        "bloques" => array($this->bloqueCategoriasDefault())
      ),
      array(
        "slot" => "home.destacados",
        "nombre" => "Productos destacados",
        "bloques" => array(
          $this->bloqueProductCollectionDefault("home-destacados", "Destacados", "/ecommercePublico/catalogo?destacado=1&limite=8", "/catalogo?destacado=1"),
          $this->bloqueProductCollectionDefault("home-disponibles", "Disponibles ahora", "/ecommercePublico/catalogo?disponibilidad=disponible&limite=8", "/catalogo?disponibilidad=disponible")
        )
      )
    );
  }

  private function plantillaVistaPaginaDefault($pagina) {
    $plantillas = array(
      "home" => array(
        "codigo" => "wokiee_home_default",
        "nombre" => "Wokiee home default",
        "pagina" => "home",
        "layout" => "storefront_wokiee_v1",
        "version" => "readonly-2026-08-11",
        "fuente" => "default_readonly",
        "secciones" => array(
          array("slot" => "home.hero", "componente" => "HeroSlider", "variante" => "full_width", "orden" => 1),
          array("slot" => "home.promo", "componente" => "PromoStrip", "variante" => "compact", "orden" => 2),
          array("slot" => "home.categorias", "componente" => "CategoryGrid", "variante" => "cards_4", "orden" => 3),
          array("slot" => "home.destacados", "componente" => "ProductCarousel", "variante" => "compact_cards", "orden" => 4)
        )
      ),
      "categoria" => array(
        "codigo" => "wokiee_categoria_default",
        "nombre" => "Wokiee categoria default",
        "pagina" => "categoria",
        "layout" => "category_wokiee_v1",
        "version" => "readonly-2026-08-11",
        "fuente" => "default_readonly",
        "secciones" => array(
          array("slot" => "categoria.banner", "componente" => "HeroSlider", "variante" => "boxed", "orden" => 1),
          array("slot" => "categoria.productos", "componente" => "ProductCarousel", "variante" => "wide_cards", "orden" => 2)
        )
      ),
      "catalogo" => array(
        "codigo" => "wokiee_catalogo_default",
        "nombre" => "Wokiee catalogo default",
        "pagina" => "catalogo",
        "layout" => "catalog_wokiee_v1",
        "version" => "readonly-2026-08-11",
        "fuente" => "default_readonly",
        "secciones" => array(
          array("slot" => "catalogo.encabezado", "componente" => "SafeHtmlBlock", "variante" => "wide", "orden" => 1)
        )
      )
    );
    return isset($plantillas[$pagina]) ? $plantillas[$pagina] : $plantillas["home"];
  }

  private function componentesFrontendDefault() {
    return array(
      array("codigo" => "HeroSlider", "bloques_permitidos" => array("hero_banner", "category_banner"), "variantes" => array("full_width", "boxed", "split")),
      array("codigo" => "PromoStrip", "bloques_permitidos" => array("promo_strip"), "variantes" => array("single", "stacked", "compact")),
      array("codigo" => "CategoryGrid", "bloques_permitidos" => array("image_card_grid"), "variantes" => array("cards_3", "cards_4", "mosaic")),
      array("codigo" => "ProductCarousel", "bloques_permitidos" => array("product_collection"), "variantes" => array("compact_cards", "wide_cards", "simple_row")),
      array("codigo" => "ImageCardGrid", "bloques_permitidos" => array("image_card_grid"), "variantes" => array("two_columns", "three_columns", "editorial")),
      array("codigo" => "SafeHtmlBlock", "bloques_permitidos" => array("content_html_safe"), "variantes" => array("narrow", "wide", "accordion"))
    );
  }

  private function bloqueHeroDefault() {
    return array(
      "id" => "home-hero-default",
      "tipo" => "hero_banner",
      "estatus" => "publicado_default",
      "titulo" => "Todo para tus mascotas",
      "subtitulo" => "Alimento, habitats, accesorios y cuidado especializado con catalogo vivo desde Artiani.",
      "media" => array(
        "imagen_desktop" => "",
        "imagen_mobile" => "",
        "alt" => "Productos para mascotas Artiani",
        "estado" => "pendiente_panel"
      ),
      "cta" => array("label" => "Ver catalogo", "url" => "/catalogo"),
      "guardrails" => array("requiere_imagen_real_panel" => true)
    );
  }

  private function bloqueCategoryBannerDefault($categoria, $categoriaLabel) {
    return array(
      "id" => "categoria-banner-" . ($categoria !== "" ? $categoria : "default"),
      "tipo" => "category_banner",
      "estatus" => "publicado_default",
      "categoria" => $categoria,
      "titulo" => $categoriaLabel,
      "subtitulo" => "Productos publicados y validados desde el catalogo Artiani.",
      "media" => array(
        "imagen_desktop" => "",
        "imagen_mobile" => "",
        "alt" => "Categoria " . $categoriaLabel,
        "estado" => "pendiente_panel"
      ),
      "cta" => array("label" => "Ver productos", "url" => "/catalogo" . ($categoria !== "" ? "?categoria=" . rawurlencode($categoria) : "")),
      "guardrails" => array("requiere_imagen_real_panel" => true)
    );
  }

  private function bloqueProductCollectionDefault($id, $titulo, $endpoint, $url) {
    return array(
      "id" => $id,
      "tipo" => "product_collection",
      "estatus" => "publicado_default",
      "titulo" => $titulo,
      "source" => array(
        "tipo" => "catalogo_dinamico",
        "endpoint" => $endpoint,
        "metodo" => "GET"
      ),
      "limite" => 8,
      "cta" => array("label" => "Ver todo", "url" => $url),
      "frontend" => array("render" => "product_carousel", "usar_items_desde_source" => true)
    );
  }

  private function bloqueCategoriasDefault() {
    return array(
      "id" => "home-categorias-default",
      "tipo" => "image_card_grid",
      "estatus" => "publicado_default",
      "titulo" => "Compra por categoria",
      "items" => array(
        array("titulo" => "Peces", "url" => "/catalogo?mascota=pez", "imagen" => "", "alt" => "Productos para peces"),
        array("titulo" => "Perros", "url" => "/catalogo?mascota=perro", "imagen" => "", "alt" => "Productos para perros"),
        array("titulo" => "Gatos", "url" => "/catalogo?mascota=gato", "imagen" => "", "alt" => "Productos para gatos"),
        array("titulo" => "Habitat", "url" => "/catalogo?necesidad=habitat", "imagen" => "", "alt" => "Habitats y accesorios")
      ),
      "frontend" => array("render" => "image_card_grid", "columnas_desktop" => 4, "columnas_mobile" => 2),
      "guardrails" => array("imagenes_reales_pendientes_panel" => true)
    );
  }

  private function contarBloquesContenido($slots) {
    $total = 0;
    foreach ((array) $slots as $slot) {
      $total += count($this->valor($slot, "bloques", array()));
    }
    return $total;
  }

  private function contenidoTieneSlot($slots, $codigo) {
    foreach ((array) $slots as $slot) {
      if ((string) $this->valor($slot, "slot", "") === $codigo) {
        return true;
      }
    }
    return false;
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

  private function endpointsFrontendHandoff() {
    return array(
      array("metodo" => "GET", "ruta" => "/ecommercePublico/frontend_handoff", "uso_frontend" => "Punto de partida para descubrir estado, ejemplos y pruebas HTTP."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/contratos", "uso_frontend" => "Manifest completo de endpoints y guardrails."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/estado", "uso_frontend" => "Readiness para datos reales o mocks."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/configuracion_inicial", "uso_frontend" => "Primer render: configuracion, filtros, navegacion, secciones y politicas."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/bootstrap", "uso_frontend" => "Alias legacy de configuracion_inicial."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/contenido_manifest", "uso_frontend" => "Plantillas, slots y tipos de bloque para CMS ligero."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/contenido_pagina", "uso_frontend" => "Banners, colecciones y bloques editoriales por pagina."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/configuracion", "uso_frontend" => "Moneda, WhatsApp y banderas publicas."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/seo", "uso_frontend" => "Rutas, sitemap, robots y JSON-LD sugerido."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/filtros", "uso_frontend" => "Filtros de catalogo vigentes."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/busqueda_sugerencias", "uso_frontend" => "Autocompletado de busqueda."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/navegacion", "uso_frontend" => "Menus y chips por mascota, necesidad, categoria, marca y disponibilidad."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/secciones", "uso_frontend" => "Bloques para home y carruseles."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/politicas", "uso_frontend" => "Textos publicos legales/operativos."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/politica/{slug}", "uso_frontend" => "Detalle de politica."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/taxonomia_mascotas", "uso_frontend" => "Navegacion especializada por mascotas y necesidades."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/catalogo", "uso_frontend" => "Listado paginado de productos publicados."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/catalogo_manifest", "uso_frontend" => "Descubrir filtros, ordenamientos, limites y reglas de catalogo robusto."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/fase_2_checklist", "uso_frontend" => "Checklist de cierre Fase 2, escenarios y criterios para pasar a Fase 3."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/producto/{slug}", "uso_frontend" => "Ficha publica de producto."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/disponibilidad", "uso_frontend" => "Estado publico sin stock exacto."),
      array("metodo" => "GET", "ruta" => "/ecommercePublico/canales_estado", "uso_frontend" => "Estado de canal propio y partners."),
      array("metodo" => "POST", "ruta" => "/ecommercePublico/cotizacion_dryrun", "uso_frontend" => "Validar carrito sin persistir."),
      array("metodo" => "POST", "ruta" => "/ecommercePublico/cotizacion_preflight", "uso_frontend" => "Validar contacto/WhatsApp antes de abrir CTA."),
      array("metodo" => "POST", "ruta" => "/ecommercePublico/facturacion_solicitar", "uso_frontend" => "Preflight de factura sin persistir."),
      array("metodo" => "POST", "ruta" => "/ecommercePublico/evento_navegacion", "uso_frontend" => "Preflight analytics anonimo sin persistir."),
      array("metodo" => "POST", "ruta" => "/ecommercePublico/busqueda_registrar", "uso_frontend" => "Preflight busqueda anonima sin persistir.")
    );
  }

  private function pruebasFrontendHandoff($baseApi, $slugEjemplo, $payloadCotizacion) {
    $slug = $slugEjemplo !== "" ? $slugEjemplo : "{slug-publicado}";
    return array(
      array("nombre" => "estado", "metodo" => "GET", "url" => $baseApi . "/estado"),
      array("nombre" => "contratos", "metodo" => "GET", "url" => $baseApi . "/contratos"),
      array("nombre" => "configuracion_inicial", "metodo" => "GET", "url" => $baseApi . "/configuracion_inicial?limite_secciones=6"),
      array("nombre" => "contenido_manifest", "metodo" => "GET", "url" => $baseApi . "/contenido_manifest"),
      array("nombre" => "contenido_pagina_home", "metodo" => "GET", "url" => $baseApi . "/contenido_pagina?pagina=home"),
      array("nombre" => "fase_2_checklist", "metodo" => "GET", "url" => $baseApi . "/fase_2_checklist"),
      array("nombre" => "catalogo", "metodo" => "GET", "url" => $baseApi . "/catalogo?limite=3"),
      array("nombre" => "catalogo_sin_resultados", "metodo" => "GET", "url" => $baseApi . "/catalogo?q=__sin_resultados_catalogo_frontend__&limite=3"),
      array("nombre" => "producto", "metodo" => "GET", "url" => $baseApi . "/producto/" . rawurlencode($slug)),
      array("nombre" => "disponibilidad", "metodo" => "GET", "url" => $baseApi . "/disponibilidad?slug=" . rawurlencode($slug)),
      array("nombre" => "cotizacion_dryrun", "metodo" => "POST", "url" => $baseApi . "/cotizacion_dryrun", "body" => array("items" => $payloadCotizacion["items"])),
      array("nombre" => "cotizacion_preflight", "metodo" => "POST", "url" => $baseApi . "/cotizacion_preflight", "body" => $payloadCotizacion)
    );
  }

  private function resumenRespuestaFrontendHandoff($respuesta, $keys) {
    if (!is_array($respuesta)) {
      return null;
    }
    $depurar = $this->valor($respuesta, "depurar", array());
    $salida = array(
      "tipo" => $this->valor($respuesta, "tipo", ""),
      "mensaje" => $this->valor($respuesta, "mensaje", "")
    );
    foreach ($keys as $key) {
      $salida[$key] = $this->valor($depurar, $key, null);
    }
    return $salida;
  }
}
