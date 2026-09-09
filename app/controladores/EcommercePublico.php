<?php

class EcommercePublico extends Controlador {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer manifiesto de contratos API que consumira el proyecto ecommerce externo.
   * Impacto: Ecommerce publico; documenta rutas, parametros y guardrails sin construir vista en ERP.
   * Contrato: GET publico read-only; no consulta datos sensibles ni escribe BD.
   */
  public function contratos() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->contratosApiPublicos());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer estado/readiness del API ecommerce para el frontend externo.
   * Impacto: Ecommerce publico; permite detectar si esquema, publicaciones y configuracion ya estan disponibles.
   * Contrato: GET publico read-only; no expone datos sensibles ni escribe BD.
   */
  public function estado() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->estadoApiPublica());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-01
   * Proposito: entregar handoff consumible por el frontend externo sin acceder al filesystem del ERP.
   * Impacto: Frontend ecommerce; centraliza estado, endpoints, ejemplos, pruebas recomendadas y no-go.
   * Contrato: GET publico read-only; no expone secretos, no escribe BD y no requiere leer docs locales.
   */
  public function frontend_handoff() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->frontendHandoffPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar configuracion inicial para que frontend arranque ecommerce con una sola llamada clara.
   * Impacto: Ecommerce publico; alias recomendado de bootstrap para evitar confusion con Bootstrap CSS.
   * Contrato: GET publico read-only; no expone secretos ni stock exacto.
   */
  public function configuracion_inicial() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->configuracionInicialPublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: exponer contrato publico del CMS ligero para plantillas, slots y bloques ecommerce.
   * Impacto: Frontend ecommerce; permite renderizar contenido editable futuro sin leer archivos del ERP.
   * Contrato: GET publico read-only; no escribe BD, no expone secretos ni toca catalogo/inventario.
   */
  public function contenido_manifest() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->contenidoManifestPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar estructura editorial de una pagina para que frontend pinte la plantilla seleccionada.
   * Impacto: Frontend ecommerce; habilita home/categorias con banners y colecciones controlables por API.
   * Contrato: GET publico read-only; en esta fase usa contenido default y no persiste cambios.
   */
  public function contenido_pagina() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->contenidoPaginaPublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: exponer alias semantico para que el frontend consuma contenido CMS publicado por pagina.
   * Impacto: Ecommerce publico; alinea contrato CMS/frontend sin duplicar reglas ni leer archivos internos.
   * Contrato: GET publico read-only; delega en contenido_pagina y no escribe BD.
   */
  public function cms_frontend() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->contenidoPaginaPublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: exponer checklist vivo de requerimientos CMS que frontend debe integrar.
   * Impacto: Frontend ecommerce; sincroniza pendientes sin leer documentos internos del ERP.
   * Contrato: GET publico read-only; no escribe BD, no expone secretos ni toca catalogo/inventario.
   */
  public function frontend_requerimientos() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->frontendRequerimientosPublicos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: entregar paquete inicial para que el frontend arranque ecommerce con una sola llamada.
   * Impacto: Ecommerce publico; agrupa readiness, configuracion, filtros, secciones y canales sin escribir BD.
   * Contrato: GET publico read-only; alias legacy de configuracion_inicial.
   */
  public function bootstrap() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->bootstrapPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer contrato publico read-only del catalogo vivo.
   * Impacto: Ecommerce publico; solo devuelve publicaciones aprobadas si existe esquema ecommerce.
   * Contrato: GET publico; no requiere sesion, no escribe BD, no expone stock exacto.
   */
  public function catalogo() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->catalogoPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: exponer busqueda inteligente v1 para interpretar frases naturales simples.
   * Impacto: Ecommerce publico; mejora /buscar/{termino} con sinonimos, intencion y fallback sin cambiar catalogo base.
   * Contrato: GET publico read-only; no registra busquedas, no expone stock exacto y usa solo publicaciones vigentes.
   */
  public function busqueda() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->busquedaInteligentePublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: exponer manifiesto robusto del catalogo para que frontend construya listados sin hardcodear reglas.
   * Impacto: Ecommerce publico; documenta filtros, ordenamientos, limites, endpoints relacionados y guardrails.
   * Contrato: GET publico read-only; no escribe BD, no muestra stock exacto y excluye granel.
   */
  public function catalogo_manifest() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->catalogoManifestPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-06
   * Proposito: exponer checklist de cierre de Fase 2 para el frontend ecommerce externo.
   * Impacto: Frontend ecommerce; centraliza endpoints obligatorios, orden de integracion y criterios para pasar a Fase 3.
   * Contrato: GET publico read-only; no escribe BD, no ejecuta DDL y no toca inventario.
   */
  public function fase_2_checklist() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->fase2ChecklistPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer detalle publico por slug de una publicacion ecommerce.
   * Impacto: Ecommerce publico; prepara ficha de producto sin usar `ecom_*` como fuente.
   * Contrato: GET publico; solo lectura y solo publicaciones con estatus `publicado`.
   */
  public function producto($slug = "") {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->productoPublico($slug));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-08
   * Proposito: exponer redirecciones publicas activas para que el frontend aplique 301 reales.
   * Impacto: Ecommerce SEO; soporta historial de slugs de producto sin exponer rutas internas del ERP.
   * Contrato: GET publico read-only; devuelve solo redirecciones activas y paths publicos.
   */
  public function redirecciones() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoRedireccionesPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer filtros publicos disponibles para catalogo vivo.
   * Impacto: Ecommerce publico; permite UI por mascota/necesidad/marca/categoria.
   * Contrato: GET publico; no requiere sesion y no expone datos internos.
   */
  public function filtros() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->filtrosPublicos());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-15
   * Proposito: exponer arbol publico de categorias para menu, home y landings SEO.
   * Impacto: Frontend ecommerce; evita fallbacks locales y permite rutas limpias /categoria/{slug}.
   * Contrato: GET publico read-only; solo deriva de publicaciones activas, no expone stock exacto ni datos internos.
   */
  public function categorias() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->categoriasPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: exponer marcas publicas con slug estable para landings SEO /marca/{slug}.
   * Impacto: Frontend ecommerce; evita filtrar marcas por texto libre y previene catalogo mezclado.
   * Contrato: GET publico read-only; solo deriva de publicaciones activas, sin costos ni stock exacto.
   */
  public function marcas() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->marcasPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: entregar filtros contextuales con conteos reales para listados publicos.
   * Impacto: Frontend ecommerce; permite facets sin seleccionar filtros que mezclen catalogo completo.
   * Contrato: GET publico read-only; respeta publicaciones, no granel y filtros invalidos vacios.
   */
  public function catalogo_filtros() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->catalogoFiltrosPublicos($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: exponer sugerencias publicas de busqueda para el frontend ecommerce.
   * Impacto: Ecommerce publico; permite buscador con productos, marcas, categorias, mascotas y necesidades sin leer tablas internas.
   * Contrato: GET publico read-only; no registra busquedas, no escribe BD y no expone stock exacto.
   */
  public function busqueda_sugerencias() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->busquedaSugerenciasPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: exponer navegacion publica lista para menus, chips y rutas del ecommerce.
   * Impacto: Frontend ecommerce; evita hardcodear rutas por mascota, necesidad, categoria, marca o disponibilidad.
   * Contrato: GET publico read-only; no escribe BD ni expone informacion sensible.
   */
  public function navegacion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->navegacionPublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: exponer secciones de catalogo listas para home y bloques del frontend.
   * Impacto: Ecommerce publico; permite construir destacados, disponibles y bloques por mascota/necesidad sin hardcodear.
   * Contrato: GET publico read-only; no escribe BD, no descuenta inventario y solo usa publicaciones activas.
   */
  public function secciones() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seccionesPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: exponer politicas publicas base para el frontend ecommerce externo.
   * Impacto: Ecommerce publico; permite construir paginas legales/operativas sin hardcodear el contrato.
   * Contrato: GET publico read-only; no registra aceptaciones ni escribe BD.
   */
  public function politicas() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->politicasPublicas());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: exponer una politica publica por slug/codigo.
   * Impacto: Ecommerce publico; soporta rutas como /politicas/facturacion en el frontend externo.
   * Contrato: GET publico read-only; no escribe BD.
   */
  public function politica($slug = "") {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->politicaPublica($slug));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: exponer taxonomia publica de mascotas y necesidades para navegacion guiada.
   * Impacto: Ecommerce publico; ayuda a construir experiencia especializada para mascotas.
   * Contrato: GET publico read-only; puede devolver defaults seguros si aun no existe tabla.
   */
  public function taxonomia_mascotas() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->taxonomiaMascotasPublica());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer configuracion publica que consumira el frontend ecommerce externo.
   * Impacto: Ecommerce publico; evita hardcodear WhatsApp, moneda y politicas en la web.
   * Contrato: GET publico read-only; solo devuelve claves publicables.
   */
  public function configuracion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->configuracionPublica());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-31
   * Proposito: exponer estado seguro de la capa multi-canal/API para frontend propio y partners.
   * Impacto: Ecommerce publico; permite saber si canales, scopes y autenticacion futura estan pendientes o disponibles.
   * Contrato: GET publico read-only; no expone secretos, no activa autenticacion y no escribe BD.
   */
  public function canales_estado() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->canalesApiEstadoPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-16
   * Proposito: exponer metadatos SEO/descubrimiento para el frontend ecommerce externo.
   * Impacto: Ecommerce publico; permite construir title, description, sitemap, robots y JSON-LD sin consultar BD directa.
   * Contrato: GET publico read-only; no escribe BD, no publica productos y no usa legacy ecom_*.
   */
  public function seo() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: exponer readiness SEO especifico para migracion de URLs del ecommerce publico.
   * Impacto: Frontend ecommerce; permite validar dominio, sitemap, robots, redirecciones y pendientes antes de publicar.
   * Contrato: GET publico read-only; no escribe BD ni usa rutas internas como canonical.
   */
  public function seo_estado() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoEstadoPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: entregar URLs publicas validas y canonicas para sitemap/render SEO.
   * Impacto: Frontend ecommerce; evita indexar endpoints internos `/ecommercePublico/*`.
   * Contrato: GET publico read-only; solo devuelve rutas publicas oficiales.
   */
  public function seo_urls() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoUrlsPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: entregar mapa aprobado de redirecciones 301 para migracion SEO.
   * Impacto: Frontend ecommerce; permite redirigir URLs antiguas antes de renderizar la app.
   * Contrato: GET publico read-only; si no existe tabla devuelve lista vacia y reglas fallback.
   */
  public function seo_redirecciones() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoRedireccionesPublicas($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: entregar sitemap estructurado usando solo URLs publicas indexables.
   * Impacto: Frontend ecommerce; genera `/sitemap.xml` sin rutas ERP/API.
   * Contrato: GET publico read-only; no escribe archivos.
   */
  public function seo_sitemap() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoSitemapPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: entregar contenido sugerido de robots.txt para frontend publico.
   * Impacto: Frontend ecommerce; centraliza Allow/Sitemap desde configuracion ERP.
   * Contrato: GET publico read-only; no escribe archivos.
   */
  public function seo_robots() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->seoRobotsPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer disponibilidad publica simple de una publicacion o SKU publicado.
   * Impacto: Ecommerce publico/Inventario; traduce stock interno a estados simples.
   * Contrato: GET publico; no muestra cantidades exactas ni descuenta inventario.
   */
  public function disponibilidad() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->disponibilidadPublica($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: validar un carrito/cotizacion del ecommerce externo sin guardar BD.
   * Impacto: Ecommerce publico; recalcula precios y disponibilidad contra publicaciones vivas del ERP.
   * Contrato: POST publico dry-run; no registra cotizacion, no aparta inventario, no cobra.
   */
  public function cotizacion_dryrun() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica(array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Usa POST para validar cotizacion dry-run",
        "api" => array(
          "nombre" => "ERP Ecommerce Publico",
          "version" => "fase1-2026-07-12",
          "modo" => "catalogo_vivo_readonly",
          "fuente_verdad" => "ERP",
          "moneda_default" => "MXN"
        ),
        "depurar" => array("dry_run" => true, "no_escribe_bd" => true)
      ));
    }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->cotizacionDryRun($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar carrito/contacto antes de abrir WhatsApp o activar registro futuro.
   * Impacto: Ecommerce publico; entrega folio preliminar y guardrails sin persistir cotizacion.
   * Contrato: POST publico preflight; no escribe BD, no aparta inventario y no crea pedido.
   */
  public function cotizacion_preflight() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica(array(
        "error" => true,
        "tipo" => "warning",
        "mensaje" => "Usa POST para preflight de cotizacion",
        "api" => array(
          "nombre" => "ERP Ecommerce Publico",
          "version" => "fase1-2026-07-12",
          "modo" => "catalogo_vivo_readonly",
          "fuente_verdad" => "ERP",
          "moneda_default" => "MXN"
        ),
        "depurar" => array("preflight" => true, "no_escribe_bd" => true)
      ));
    }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->cotizacionPreflight($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: reservar contrato futuro para registrar cotizacion ecommerce real.
   * Impacto: Ecommerce publico; evita que el frontend invente un POST distinto cuando se active persistencia.
   * Contrato: POST publico bloqueado por defecto; no escribe BD hasta autorizar esquema, firma y seguimiento.
   */
  public function cotizacion_registrar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->cotizacionRegistrarBloqueada($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar solicitud publica de facturacion por folio sin registrar datos fiscales.
   * Impacto: Ecommerce publico; permite al frontend construir formulario fiscal con contrato estable.
   * Contrato: POST publico preflight; no escribe BD, no emite factura y no vincula cliente.
   */
  public function facturacion_solicitar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("facturacion_preflight"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->facturacionSolicitudPreflight($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: exponer contrato publico vivo para Ecommerce / Analytics.
   * Impacto: Frontend ecommerce; evita leer docs/archivos internos del ERP y fija payloads anonimos.
   * Contrato: GET publico read-only; no escribe BD ni expone datos sensibles.
   */
  public function analytics_contrato() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceAnalyticsErp")->contratoFrontend());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir sesion anonima de analytics para validar o persistir segun bandera operativa.
   * Impacto: prepara tracking seguro por session hash sin cliente, checkout, ventas ni inventario.
   * Contrato: POST publico; sin bandera activa se mantiene en preflight y bloquea datos personales detectables.
   */
  public function analytics_sesion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("analytics_sesion_preflight"));
    }
    $analytics = $this->modelo("EcommerceAnalyticsErp");
    $datos = $this->entradaJsonPublica();
    if ($analytics->trackingPublicoActivo()) {
      return $this->responderApiPublica($analytics->registrarSesionAutorizada($datos, array("autorizar" => "ECOMMERCE_ANALYTICS_TRACKING")));
    }
    return $this->responderApiPublica($analytics->sesionPreflight($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir evento anonimo de navegacion para validar o persistir segun bandera operativa.
   * Impacto: Ecommerce publico; prepara analitica de mascotas, productos y conversion a WhatsApp.
   * Contrato: POST publico; sin bandera activa se mantiene en preflight y no acepta datos personales en tracking.
   */
  public function evento_navegacion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("evento_navegacion_preflight"));
    }
    $analytics = $this->modelo("EcommerceAnalyticsErp");
    $datos = $this->entradaJsonPublica();
    if ($analytics->trackingPublicoActivo()) {
      return $this->responderApiPublica($analytics->registrarEventoAutorizado($datos, array("autorizar" => "ECOMMERCE_ANALYTICS_TRACKING")));
    }
    return $this->responderApiPublica($analytics->eventoPreflight($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir busqueda anonima ecommerce para validar o persistir segun bandera operativa.
   * Impacto: Ecommerce publico; prepara aprendizaje de demanda, faltantes y necesidades por mascota.
   * Contrato: POST publico; sin bandera activa se mantiene en preflight y no guarda datos personales.
   */
  public function busqueda_registrar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("busqueda_preflight"));
    }
    $analytics = $this->modelo("EcommerceAnalyticsErp");
    $datos = $this->entradaJsonPublica();
    if ($analytics->trackingPublicoActivo()) {
      return $this->responderApiPublica($analytics->registrarBusquedaAutorizada($datos, array("autorizar" => "ECOMMERCE_ANALYTICS_TRACKING")));
    }
    return $this->responderApiPublica($analytics->busquedaPreflight($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir conversion anonima ecommerce para validar o persistir segun bandera operativa.
   * Impacto: prepara embudo visita-producto-cotizacion-dryrun-preflight-WhatsApp sin checkout ni ventas.
   * Contrato: POST publico; sin bandera activa se mantiene en preflight, no crea checkout ni toca inventario.
   */
  public function analytics_conversion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("analytics_conversion_preflight"));
    }
    $analytics = $this->modelo("EcommerceAnalyticsErp");
    $datos = $this->entradaJsonPublica();
    if ($analytics->trackingPublicoActivo()) {
      return $this->responderApiPublica($analytics->registrarConversionAutorizada($datos, array("autorizar" => "ECOMMERCE_ANALYTICS_TRACKING")));
    }
    return $this->responderApiPublica($analytics->conversionPreflight($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: exponer contrato publico para carritos/leads ecommerce.
   * Impacto: Frontend Artiani; separa intencion comercial de analytics anonimo y de ventas reales.
   * Contrato: GET publico read-only; no escribe BD ni expone datos internos.
   */
  public function leads_contrato() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->contratoFrontend());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir snapshot comercial del carrito ecommerce para seguimiento futuro.
   * Impacto: Ecommerce Leads; prepara persistencia por session_id_hash sin crear pedido ni tocar inventario.
   * Contrato: POST publico; si persistencia no esta activa responde preflight sin guardar.
   */
  public function carrito_sincronizar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!$this->esPostPublico()) {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("carrito_sincronizar"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->carritoSincronizar($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir evento comercial puntual de carrito ecommerce.
   * Impacto: Ecommerce Leads; registra etapas como vista de carrito, cambio de cantidad o abandono estimado.
   * Contrato: POST publico; no sustituye analytics y no acepta datos personales fuera de contacto.
   */
  public function carrito_evento() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!$this->esPostPublico()) {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("carrito_evento"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->carritoEvento($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: capturar intento de pedido o apertura de WhatsApp con snapshot comercial.
   * Impacto: Ecommerce Leads; conserva que queria comprar el cliente aunque borre/no envie el mensaje.
   * Contrato: POST publico; no crea pedido, venta, cotizacion real ni descuenta inventario.
   */
  public function intento_pedido() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!$this->esPostPublico()) {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("intento_pedido"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->intentoPedido($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: enlazar carrito anonimo con contacto escrito explicitamente por el cliente.
   * Impacto: Ecommerce Leads/CRM futuro; no inventa cliente y conserva privacidad por session_id_hash.
   * Contrato: POST publico; no crea cliente CRM automaticamente.
   */
  public function contacto_registrar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!$this->esPostPublico()) {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("contacto_registrar"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->contactoRegistrar($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: recibir solicitud de facturacion ecommerce ligada a session_id/carrito.
   * Impacto: Ecommerce Leads; prepara seguimiento fiscal sin emitir factura ni registrar venta.
   * Contrato: POST publico; no factura y no crea cliente automaticamente.
   */
  public function facturacion_solicitud_registrar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!$this->esPostPublico()) {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("facturacion_solicitud_registrar"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceLeadsErp")->facturacionSolicitudRegistrar($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: estandarizar headers de API publica sin abrir CORS por defecto.
   * Impacto: Ecommerce publico externo; permite versionado y CORS restringido cuando exista configuracion.
   * Contrato: no autentica ni escribe; solo agrega headers seguros y codifica JSON.
   */
  private function responderApiPublica($respuesta) {
    $modelo = $this->modelo("EcommerceCatalogoPublico");
    $origen = isset($_SERVER["HTTP_ORIGIN"]) ? trim((string) $_SERVER["HTTP_ORIGIN"]) : "";
    if (!headers_sent()) {
      header("Content-Type: application/json; charset=utf-8");
      header("X-ERP-Ecommerce-API-Version: fase1-2026-07-12");
      header("X-ERP-Ecommerce-Mode: catalogo-vivo-readonly");
      header("Vary: Origin");
      if ($origen !== "" && $modelo->origenCorsPermitido($origen)) {
        header("Access-Control-Allow-Origin: " . $origen);
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, X-Ecommerce-Api-Key, X-Ecommerce-Signature");
        header("Access-Control-Max-Age: 600");
      }
    }
    if (isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string) $_SERVER["REQUEST_METHOD"]) === "OPTIONS") {
      return "";
    }
    return json_encode($respuesta);
  }

  private function responderOpcionesPublicas() {
    return $this->responderApiPublica(array(
      "error" => false,
      "tipo" => "success",
      "mensaje" => "Preflight ecommerce publico",
      "api" => array(
        "nombre" => "ERP Ecommerce Publico",
        "version" => "fase1-2026-07-12",
        "modo" => "catalogo_vivo_readonly",
        "fuente_verdad" => "ERP",
        "moneda_default" => "MXN"
      ),
      "depurar" => array("options" => true)
    ));
  }

  private function esOptionsPublicas() {
    return isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string) $_SERVER["REQUEST_METHOD"]) === "OPTIONS";
  }

  private function esPostPublico() {
    return isset($_SERVER["REQUEST_METHOD"]) && strtoupper((string) $_SERVER["REQUEST_METHOD"]) === "POST";
  }

  private function entradaJsonPublica() {
    $raw = file_get_contents("php://input");
    $json = json_decode((string) $raw, true);
    return is_array($json) ? $json : array();
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: abrir la consola interna read-only de publicaciones ecommerce.
   * Impacto: permite revisar publicabilidad antes de autorizar DDL o exponer catalogo publico.
   * Contrato: vista protegida por `catalogo.ver`; no publica productos ni escribe BD.
   */
  public function publicaciones() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/publicaciones");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: abrir panel operativo para gobernar que se muestra en el ecommerce Artiani.
   * Impacto: Ecommerce publico; permite administrar visibilidad, estatus y curaduria sin tocar inventario.
   * Contrato: vista protegida por `catalogo.ver`; las escrituras requieren `catalogo.editar`, CSRF y token interno.
   */
  public function control() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/control");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: abrir bandeja interna read-only de cotizaciones ecommerce.
   * Impacto: permite revisar seguimiento futuro sin activar registro, pedidos ni ventas.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD.
   */
  public function cotizaciones() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/cotizaciones");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-31
   * Proposito: abrir bandeja interna de carritos/leads ecommerce.
   * Impacto: seguimiento comercial de intenciones capturadas desde frontend sin crear pedidos ni ventas.
   * Contrato: vista protegida por `catalogo.ver`; las acciones actuales son planes read-only.
   */
  public function leads() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/leads");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: abrir dashboard interno read-only de Ecommerce / Analytics.
   * Impacto: permite revisar navegacion, busquedas y embudo sin exponer datos personales ni stock exacto.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD.
   */
  public function analytics() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/analytics");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-05
   * Proposito: abrir vista interna read-only del flujo de navegacion por sesion anonima.
   * Impacto: permite revisar recorridos ecommerce sin exponer PII, session_id completo, ventas ni inventario.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD.
   */
  public function analytics_flujo() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/analytics_flujo");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: abrir consola interna de SEO/migracion URLs del ecommerce publico.
   * Impacto: Ecommerce SEO; concentra URLs canonicas, redirecciones, sitemap, robots y DDL pendiente.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD ni aplica migraciones.
   */
  public function seo_migracion() {
    $this->requerirPermiso("catalogo.ver");
    $this->vista("apps/erp/ecommerce/seo_migracion");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: entregar dashboard interno read-only para preparar migracion SEO.
   * Impacto: Ecommerce SEO; permite revisar estado, URLs, redirecciones, sitemap y robots desde ERP.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function seo_dashboard_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->seoDashboardInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: preparar importacion de URLs viejas sin guardar BD.
   * Impacto: Ecommerce SEO; normaliza URLs, detecta tipo probable y sugiere destinos publicos.
   * Contrato: POST protegido por `catalogo.ver`; read-only, no inserta URLs ni redirecciones.
   */
  public function seo_urls_viejas_importar_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceCatalogoPublico")->seoUrlsViejasImportarPlanInterno($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-05
   * Proposito: entregar bandeja read-only de revision de URLs viejas rastreadas.
   * Impacto: Ecommerce SEO; permite aprobar, validar o descartar candidatos antes de importar/crear 301.
   * Contrato: GET protegido por `catalogo.ver`; lee ultimo reporte local y no escribe BD.
   */
  public function seo_urls_viejas_revision_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->seoUrlsViejasRevisionInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: validar propuesta de redireccion SEO antes de persistirla.
   * Impacto: Ecommerce SEO; evita destinos internos, ciclos simples y status no permitidos.
   * Contrato: POST protegido por `catalogo.ver`; read-only, devuelve SQL sugerido sin ejecutar.
   */
  public function seo_redireccion_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceCatalogoPublico")->seoRedireccionPlanInterno($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: preparar sincronizacion de URLs canonicas SEO sin guardar BD.
   * Impacto: Ecommerce SEO; compara rutas publicas actuales contra snapshot persistente.
   * Contrato: POST protegido por `catalogo.ver`; read-only, devuelve SQL preview sin ejecutar.
   */
  public function seo_urls_sincronizar_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceCatalogoPublico")->seoUrlsCanonicasSincronizarPlanInterno($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: persistir snapshot de URLs canonicas SEO con autorizacion operativa.
   * Impacto: Ecommerce SEO; llena/actualiza `erp_ecommerce_seo_urls` para monitoreo y auditoria.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y tabla SEO aplicada.
   */
  public function seo_urls_sincronizar_erp() {
    $this->requerirPermiso("catalogo.editar");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->seoUrlsCanonicasSincronizarAutorizado($datos);
    SesionSeguridad::registrarAuditoria("ecommerce_seo", "urls_canonicas_sincronizar", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("limite" => isset($datos["limite"]) ? intval($datos["limite"]) : 0),
      "datos_despues" => array(
        "total_insertadas" => isset($respuesta["depurar"]["total_insertadas"]) ? intval($respuesta["depurar"]["total_insertadas"]) : 0,
        "total_actualizadas" => isset($respuesta["depurar"]["total_actualizadas"]) ? intval($respuesta["depurar"]["total_actualizadas"]) : 0,
        "total_omitidas" => isset($respuesta["depurar"]["total_omitidas"]) ? intval($respuesta["depurar"]["total_omitidas"]) : 0
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: importar URLs viejas SEO con autorizacion operativa.
   * Impacto: Ecommerce SEO; crea/actualiza pendientes de mapeo sin crear redirecciones automaticamente.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y tablas SEO aplicadas.
   */
  public function seo_urls_viejas_importar_erp() {
    $this->requerirPermiso("catalogo.editar");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->seoUrlsViejasImportarAutorizado($datos, array(
      "autorizar" => isset($datos["autorizar"]) ? $datos["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_seo", "urls_viejas_importar", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("total_recibidas" => isset($respuesta["depurar"]["total_recibidas"]) ? intval($respuesta["depurar"]["total_recibidas"]) : 0),
      "datos_despues" => array(
        "total_insertadas" => isset($respuesta["depurar"]["total_insertadas"]) ? intval($respuesta["depurar"]["total_insertadas"]) : 0,
        "total_actualizadas" => isset($respuesta["depurar"]["total_actualizadas"]) ? intval($respuesta["depurar"]["total_actualizadas"]) : 0,
        "total_omitidas" => isset($respuesta["depurar"]["total_omitidas"]) ? intval($respuesta["depurar"]["total_omitidas"]) : 0
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: guardar una redireccion SEO aprobada.
   * Impacto: Ecommerce SEO; alimenta `/seo_redirecciones` para que frontend aplique 301.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y tablas SEO aplicadas.
   */
  public function seo_redireccion_guardar_erp() {
    $this->requerirPermiso("catalogo.editar");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->seoRedireccionGuardarAutorizada($datos, array(
      "autorizar" => isset($datos["autorizar"]) ? $datos["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_seo", "redireccion_guardar", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("from" => isset($datos["from"]) ? (string) $datos["from"] : ""),
      "datos_despues" => array("redireccion" => isset($respuesta["depurar"]["redireccion"]) ? $respuesta["depurar"]["redireccion"] : array())
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: auditar SKUs candidatos para publicacion ecommerce sin escribir datos.
   * Impacto: Ecommerce publico/Catalogo ERP; prepara decisiones de publicacion con permiso interno.
   * Contrato: GET protegido por `catalogo.ver`; no crea publicaciones, cotizaciones ni movimientos.
   */
  public function publicaciones_auditar_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->auditarPublicabilidad($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-24
   * Proposito: devolver IDs de todos los SKUs que coinciden con los filtros actuales para seleccion masiva.
   * Impacto: Ecommerce publico/publicaciones; facilita lotes grandes sin seleccionar pagina por pagina.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura, no publica ni guarda cambios.
   */
  public function publicaciones_ids_filtrados_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->idsPublicabilidadFiltrada($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: mostrar readiness interno para arrancar frontend ecommerce externo con mocks o datos reales.
   * Impacto: Ecommerce publico; concentra bloqueos de DDL, CORS, WhatsApp y publicaciones sin escribir BD.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function publicaciones_readiness_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->readinessFrontendInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: reportar avance formal de la Fase 1 de gobierno de publicaciones ecommerce.
   * Impacto: Ecommerce publico; permite saber desde el panel que falta antes de pasar a la Fase 2.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura, no escribe BD ni cambia publicaciones.
   */
  public function publicaciones_fase_estado_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->fasePublicacionesEstadoInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: preparar una propuesta read-only de publicacion ecommerce para un SKU ERP.
   * Impacto: Ecommerce publico/Catalogo ERP; permite revisar slug, textos y metadata antes de guardar.
   * Contrato: GET protegido por `catalogo.ver`; no inserta ni actualiza publicaciones.
   */
  public function publicaciones_preparar_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->prepararPublicacion($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: generar plan read-only de guardado de publicacion ecommerce.
   * Impacto: permite revisar SQL, bloqueos y normalizacion antes de habilitar escrituras reales.
   * Contrato: endpoint interno protegido; no inserta ni actualiza publicaciones.
   */
  public function publicaciones_plan_guardado_erp() {
    $this->requerirPermiso("catalogo.editar");
    $datos = !empty($_POST) ? $_POST : $_GET;
    return json_encode($this->modelo("EcommerceCatalogoPublico")->planGuardarPublicacion($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: revisar plan interno de persistencia para una cotizacion ecommerce.
   * Impacto: Ecommerce publico/CRM futuro; prepara folio, snapshots y evento sin escribir BD.
   * Contrato: POST protegido por `catalogo.ver`; read-only, no registra cotizacion ni mueve inventario.
   */
  public function cotizacion_registro_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceCatalogoPublico")->cotizacionRegistroPersistenciaPlan($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: listar bandeja interna read-only de cotizaciones ecommerce.
   * Impacto: seguimiento operativo futuro sin convertir a pedido/venta ni tocar inventario.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function cotizaciones_bandeja_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->cotizacionesBandejaInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: consultar detalle interno read-only de una cotizacion ecommerce.
   * Impacto: prepara seguimiento y conversion manual futura sin registrar movimientos.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function cotizacion_detalle_erp($folio = "") {
    $this->requerirPermiso("catalogo.ver");
    $filtros = $_GET;
    if ($folio !== "") {
      $filtros["folio"] = $folio;
    }
    return json_encode($this->modelo("EcommerceCatalogoPublico")->cotizacionDetalleInterna($filtros));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: planear acciones internas futuras sobre una cotizacion ecommerce.
   * Impacto: define seguimiento, descarte y conversion manual sin ejecutar cambios.
   * Contrato: POST protegido por `catalogo.ver`; read-only, no cambia estatus ni crea documentos.
   */
  public function cotizacion_accion_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceCatalogoPublico")->cotizacionAccionPlanInterna($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: consultar inteligencia cliente ecommerce en modo interno read-only.
   * Impacto: prepara analisis de busquedas, navegacion y solicitudes de facturacion.
   * Contrato: GET protegido por `catalogo.ver`; no registra eventos, no guarda solicitudes ni toca inventario.
   */
  public function inteligencia_cliente_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->inteligenciaClienteInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: consultar dashboard interno read-only del modulo Ecommerce / Analytics.
   * Impacto: concentra metricas de navegacion, busqueda y conversion sin tocar ventas ni inventario.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function analytics_dashboard_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsErp")->dashboardInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-05
   * Proposito: entregar timeline read-only de navegacion por sesion anonima.
   * Impacto: soporta analisis operativo de recorridos sin tocar ventas, inventario ni datos personales.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function analytics_flujo_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsErp")->flujoSesionesInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: revisar readiness de persistencia Ecommerce / Analytics sin activarla.
   * Impacto: permite ver esquema, token requerido y orden de activacion antes de registrar tracking real.
   * Contrato: GET protegido por `catalogo.ver`; no escribe BD.
   */
  public function analytics_persistencia_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsErp")->persistenciaPlanInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: planear recalc de resumen diario Ecommerce / Analytics sin ejecutarlo.
   * Impacto: prepara performance del dashboard sin activar jobs ni escrituras.
   * Contrato: GET protegido por `catalogo.ver`; no escribe BD.
   */
  public function analytics_resumen_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsErp")->resumenDiarioPlanInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-05
   * Proposito: planear retencion de datos crudos Ecommerce / Analytics sin borrar registros.
   * Impacto: prepara politica de privacidad y purga futura conservando resumen diario.
   * Contrato: GET protegido por `catalogo.ver`; no escribe BD.
   */
  public function analytics_retencion_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsErp")->retencionPlanInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: listar bandeja interna de carritos/leads ecommerce.
   * Impacto: seguimiento comercial desde ERP sin consultar analytics anonimo ni crear ventas.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function carritos_dashboard_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceLeadsErp")->dashboardInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: consultar detalle interno de carrito/lead ecommerce.
   * Impacto: muestra snapshot comercial, eventos y notas sin crear pedido ni venta.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function carrito_detalle_erp($id = "") {
    $this->requerirPermiso("catalogo.ver");
    $filtros = $_GET;
    if ($id !== "") { $filtros["id_carrito_lead"] = $id; }
    return json_encode($this->modelo("EcommerceLeadsErp")->detalleInterno($filtros));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-05
   * Proposito: listar productos agregados en sesiones/leads ecommerce.
   * Impacto: seguimiento comercial por producto sin consultar analytics ni crear ventas.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function productos_leads_erp() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceLeadsErp")->productosInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: planear accion interna sobre carrito/lead ecommerce.
   * Impacto: prepara seguimiento, conversion, descarte o nota sin ejecutar cambios.
   * Contrato: POST protegido por `catalogo.ver`; read-only, no cambia estatus ni crea documentos.
   */
  public function carrito_accion_plan_erp() {
    $this->requerirPermiso("catalogo.ver");
    $datos = !empty($_POST) ? $_POST : $this->entradaJsonPublica();
    return json_encode($this->modelo("EcommerceLeadsErp")->accionPlanInterna($datos));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-13
   * Proposito: guardar una publicacion ecommerce como borrador con autorizacion operativa.
   * Impacto: activa curaduria interna posterior al DDL sin publicar automaticamente ni mover inventario.
   * Contrato: POST protegido por `catalogo.editar`; requiere token y registra auditoria explicita.
   */
  public function publicaciones_guardar_borrador_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->guardarPublicacionBorradorAutorizada($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_guardar_borrador", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("id_sku" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : 0),
      "datos_despues" => array(
        "id_publicacion" => isset($respuesta["depurar"]["publicacion"]["id_publicacion"]) ? intval($respuesta["depurar"]["publicacion"]["id_publicacion"]) : null,
        "estatus" => isset($respuesta["depurar"]["publicacion"]["estatus_publicacion"]) ? $respuesta["depurar"]["publicacion"]["estatus_publicacion"] : null
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: guardar curaduria de una publicacion ecommerce existente sin cambiar su estatus.
   * Impacto: permite corregir titulo, slug, mascota, necesidades y descripcion desde panel.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y auditoria explicita.
   */
  public function publicaciones_guardar_curaduria_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->guardarCuraduriaPublicacionAutorizada($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_guardar_curaduria", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array(
        "id_publicacion" => isset($_POST["id_publicacion"]) ? intval($_POST["id_publicacion"]) : 0,
        "id_sku" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : 0
      ),
      "datos_despues" => array(
        "id_publicacion" => isset($respuesta["depurar"]["publicacion"]["id_publicacion"]) ? intval($respuesta["depurar"]["publicacion"]["id_publicacion"]) : null,
        "estatus" => isset($respuesta["depurar"]["publicacion"]["estatus_publicacion"]) ? $respuesta["depurar"]["publicacion"]["estatus_publicacion"] : null
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: publicar desde el panel un borrador ecommerce previamente revisado.
   * Impacto: expone el SKU en el API publico sin tocar inventario, precios ERP ni legacy `ecom_*`.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, revision confirmada y auditoria explicita.
   */
  public function publicaciones_publicar_borrador_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->publicarBorradorAutorizado($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_publicar_borrador", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array(
        "id_publicacion" => isset($_POST["id_publicacion"]) ? intval($_POST["id_publicacion"]) : 0,
        "id_sku" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : 0
      ),
      "datos_despues" => array(
        "id_publicacion" => isset($respuesta["depurar"]["publicacion"]["id_publicacion"]) ? intval($respuesta["depurar"]["publicacion"]["id_publicacion"]) : null,
        "estatus" => isset($respuesta["depurar"]["publicacion"]["estatus_publicacion"]) ? $respuesta["depurar"]["publicacion"]["estatus_publicacion"] : null
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: cambiar estatus de una publicacion ecommerce desde gobierno interno.
   * Impacto: permite pausar/reactivar/publicar productos en Artiani sin tocar Catalogo ERP ni inventario.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y auditoria explicita.
   */
  public function publicaciones_estatus_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->cambiarEstatusPublicacionAutorizado($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_estatus", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array(
        "id_publicacion" => isset($_POST["id_publicacion"]) ? intval($_POST["id_publicacion"]) : 0,
        "id_sku" => isset($_POST["id_sku"]) ? intval($_POST["id_sku"]) : 0
      ),
      "datos_despues" => array(
        "estatus" => isset($respuesta["depurar"]["estatus_publicacion"]) ? $respuesta["depurar"]["estatus_publicacion"] : null
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: cambiar estatus de publicaciones ecommerce seleccionadas por lote.
   * Impacto: facilita deshabilitar/reactivar grupos curados desde panel sin tocar inventario.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y auditoria explicita.
   */
  public function publicaciones_lote_estatus_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->cambiarEstatusLoteAutorizado($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_lote_estatus", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("id_skus" => isset($_POST["id_skus"]) ? (string) $_POST["id_skus"] : ""),
      "datos_despues" => array(
        "estatus" => isset($_POST["estatus_publicacion"]) ? (string) $_POST["estatus_publicacion"] : "",
        "total_solicitado" => isset($respuesta["depurar"]["total_solicitado"]) ? intval($respuesta["depurar"]["total_solicitado"]) : 0,
        "total_ok" => isset($respuesta["depurar"]["total_ok"]) ? intval($respuesta["depurar"]["total_ok"]) : 0,
        "total_error" => isset($respuesta["depurar"]["total_error"]) ? intval($respuesta["depurar"]["total_error"]) : 0,
        "resultado_lote" => isset($respuesta["depurar"]["resultado_lote"]) ? (string) $respuesta["depurar"]["resultado_lote"] : "",
        "confirmar_agotado" => isset($respuesta["depurar"]["confirmar_agotado"]) ? (bool) $respuesta["depurar"]["confirmar_agotado"] : false,
        "agotados_permitidos_por_politica_lote" => isset($respuesta["depurar"]["agotados_permitidos_por_politica_lote"]) ? (bool) $respuesta["depurar"]["agotados_permitidos_por_politica_lote"] : false
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: guardar borradores ecommerce por lote desde productos seleccionados en panel.
   * Impacto: acelera expansion controlada del catalogo sin publicar automaticamente.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno y auditoria explicita.
   */
  public function publicaciones_lote_borrador_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->guardarBorradoresLoteAutorizado($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_lote_borrador", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("id_skus" => isset($_POST["id_skus"]) ? (string) $_POST["id_skus"] : ""),
      "datos_despues" => array(
        "total_solicitado" => isset($respuesta["depurar"]["total_solicitado"]) ? intval($respuesta["depurar"]["total_solicitado"]) : 0,
        "total_ok" => isset($respuesta["depurar"]["total_ok"]) ? intval($respuesta["depurar"]["total_ok"]) : 0,
        "total_error" => isset($respuesta["depurar"]["total_error"]) ? intval($respuesta["depurar"]["total_error"]) : 0,
        "resultado_lote" => isset($respuesta["depurar"]["resultado_lote"]) ? (string) $respuesta["depurar"]["resultado_lote"] : "",
        "confirmar_agotado" => isset($respuesta["depurar"]["confirmar_agotado"]) ? (bool) $respuesta["depurar"]["confirmar_agotado"] : false,
        "agotados_permitidos_por_politica_lote" => isset($respuesta["depurar"]["agotados_permitidos_por_politica_lote"]) ? (bool) $respuesta["depurar"]["agotados_permitidos_por_politica_lote"] : false
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: aplicar configuracion de visibilidad ecommerce a SKUs seleccionados por lote.
   * Impacto: acelera curaduria masiva sin tocar Catalogo ERP, inventario, precios base ni legacy ecom_*.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, CSRF y auditoria explicita.
   */
  public function publicaciones_lote_configuracion_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->aplicarConfiguracionLoteAutorizada($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_lote_configuracion", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("id_skus" => isset($_POST["id_skus"]) ? (string) $_POST["id_skus"] : ""),
      "datos_despues" => array(
        "campos" => isset($respuesta["depurar"]["campos_aplicados"]) ? $respuesta["depurar"]["campos_aplicados"] : array(),
        "total_ok" => isset($respuesta["depurar"]["total_ok"]) ? intval($respuesta["depurar"]["total_ok"]) : 0,
        "total_error" => isset($respuesta["depurar"]["total_error"]) ? intval($respuesta["depurar"]["total_error"]) : 0
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-30
   * Proposito: publicar por lote borradores ecommerce seleccionados en panel.
   * Impacto: expone multiples publicaciones al API publico sin tocar inventario ni Catalogo ERP.
   * Contrato: POST protegido por `catalogo.editar`; requiere token interno, revision confirmada y auditoria explicita.
   */
  public function publicaciones_lote_publicar_erp() {
    $this->requerirPermiso("catalogo.editar");
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->publicarBorradoresLoteAutorizado($_POST, array(
      "autorizar" => isset($_POST["autorizar"]) ? $_POST["autorizar"] : ""
    ));
    SesionSeguridad::registrarAuditoria("ecommerce_publico", "publicacion_lote_publicar", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array("id_skus" => isset($_POST["id_skus"]) ? (string) $_POST["id_skus"] : ""),
      "datos_despues" => array(
        "total_ok" => isset($respuesta["depurar"]["total_ok"]) ? intval($respuesta["depurar"]["total_ok"]) : 0,
        "total_error" => isset($respuesta["depurar"]["total_error"]) ? intval($respuesta["depurar"]["total_error"]) : 0
      )
    ));
    return json_encode($respuesta);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: reservar contrato interno para guardar publicaciones ecommerce reales.
   * Impacto: Ecommerce publico/Catalogo ERP; evita publicar SKUs sin DDL y autorizacion operativa.
   * Contrato: POST protegido por `catalogo.editar`; bloqueado por defecto, no escribe BD en esta fase.
   */
  public function publicaciones_guardar_erp() {
    $this->requerirPermiso("catalogo.editar");
    return json_encode($this->modelo("EcommerceCatalogoPublico")->guardarPublicacionBloqueada($_POST));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: auditar existencia de tablas ecommerce publico Fase 1.
   * Impacto: permite revisar readiness de publicaciones/cotizaciones sin ejecutar DDL.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_ecommerce_publico() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->auditarEcommercePublico());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: generar plan DDL de ecommerce publico Fase 1 sin ejecutarlo.
   * Impacto: prepara autorizacion futura con respaldo externo.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_ecommerce_publico() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->planActualizarEcommercePublico(false));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: auditar tablas SEO/migracion de URLs sin ejecutar DDL.
   * Impacto: Ecommerce SEO; permite revisar readiness de redirecciones, URLs viejas y 404.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_seo_migracion() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->auditarSeoMigracion());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-03
   * Proposito: generar plan DDL read-only para SEO/migracion de URLs.
   * Impacto: Ecommerce SEO; prepara configuracion, URLs canonicas, redirecciones, URLs viejas y 404.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_seo_migracion() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->planActualizarSeoMigracion(false));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-25
   * Proposito: auditar capa futura de canales/API keys para frontend propio y partners.
   * Impacto: permite planear seguridad multi-canal sin activar autenticacion ni generar secretos.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_canales_api() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->auditarCanalesApi());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-25
   * Proposito: generar plan DDL read-only para canales, credenciales y logs API ecommerce.
   * Impacto: prepara tokens para partners sin tocar frontend actual ni aplicar DDL.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_canales_api() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->planActualizarCanalesApi(false));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: auditar capa futura de politicas, facturacion y analitica ecommerce.
   * Impacto: permite planear experiencia cliente sin activar escrituras publicas.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_experiencia_cliente() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->auditarExperienciaCliente());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
   * Proposito: generar plan DDL read-only para politicas, facturacion, analitica y taxonomia.
   * Impacto: prepara pantallas frontend y panel ERP sin aplicar DDL.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_experiencia_cliente() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommercePublicoEsquema")->planActualizarExperienciaCliente(false));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: auditar el esquema dedicado de Ecommerce / Analytics sin ejecutar DDL.
   * Impacto: revisa readiness de sesiones, eventos, busquedas, conversiones y resumen diario.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_analytics() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsEsquema")->auditarEcommerceAnalytics());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-04
   * Proposito: generar plan DDL del modulo Ecommerce / Analytics sin ejecutarlo.
   * Impacto: prepara autorizacion futura con respaldo externo y token apply_authorized.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_analytics() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceAnalyticsEsquema")->planActualizarEcommerceAnalytics(false));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: auditar esquema de Ecommerce Leads/Carritos sin ejecutar DDL.
   * Impacto: revisa readiness para seguimiento comercial del ecommerce publico.
   * Contrato: GET protegido por `catalogo.ver`; solo lectura.
   */
  public function esquema_auditar_leads() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceLeadsEsquema")->auditarEcommerceLeads());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: generar plan DDL de Ecommerce Leads/Carritos sin ejecutarlo.
   * Impacto: prepara tablas de snapshot comercial, eventos y notas con respaldo/autorizacion futura.
   * Contrato: GET protegido por `catalogo.ver`; no ejecuta DDL.
   */
  public function esquema_plan_leads() {
    $this->requerirPermiso("catalogo.ver");
    return json_encode($this->modelo("EcommerceLeadsEsquema")->planActualizarEcommerceLeads(false));
  }
}
