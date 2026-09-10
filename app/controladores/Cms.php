<?php

class Cms extends Controlador {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: entrada directa al modulo CMS.
   * Impacto: CMS; abre el mapa de paginas/grupos antes de entrar a vistas dedicadas.
   * Contrato: vista protegida via `frontend_actual`; no edita archivos del frontend.
   */
  public function index() {
    $this->frontend_actual();
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-14
   * Proposito: enrutar paginas editoriales del frontend CMS como /cms/frontend/home.
   * Impacto: CMS frontend; ordena el modulo por pagina real del ecommerce.
   * Contrato: vista protegida; no guarda HTML ni edita archivos del frontend.
   */
  public function frontend($pagina = "home") {
    $pagina = strtolower(trim((string) $pagina));
    if ($pagina === "" || $pagina === "home") {
      $this->frontend_home();
      return;
    }
    if ($pagina === "global") {
      $this->frontend_global();
      return;
    }
    if ($pagina === "navegacion") {
      $this->frontend_navegacion();
      return;
    }
    if ($pagina === "catalogo") {
      $this->frontend_catalogo();
      return;
    }
    if ($pagina === "busqueda") {
      $this->frontend_busqueda();
      return;
    }
    if ($pagina === "categorias") {
      $this->frontend_categorias();
      return;
    }
    if ($pagina === "marcas") {
      $this->frontend_marcas();
      return;
    }
    if ($pagina === "paginas") {
      $this->frontend_paginas();
      return;
    }
    if ($pagina === "politicas") {
      $this->frontend_politicas();
      return;
    }
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_placeholder", array("pagina" => $pagina));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-14
   * Proposito: abrir CMS > Frontend > Home como pantalla operativa principal.
   * Impacto: CMS frontend Home; separa la captura editorial por pagina.
   * Contrato: vista protegida; editor local, sin persistencia real del contrato frontend.
   */
  public function frontend_home() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_home");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Global como pantalla operativa.
   * Impacto: CMS frontend global; prepara datos de negocio, contacto, SEO y navegacion.
   * Contrato: vista protegida; editor local, sin exponer secretos ni escribir BD.
   */
  public function frontend_global() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_global");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Navegacion como pantalla operativa.
   * Impacto: CMS frontend navegacion; prepara menu, topbar, footer y CTAs globales.
   * Contrato: vista protegida; editor local, sin editar archivos frontend ni escribir BD.
   */
  public function frontend_navegacion() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_navegacion");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: abrir CMS > Frontend > Catalogo como pantalla operativa.
   * Impacto: CMS frontend catalogo; prepara encabezado, SEO y estados editoriales sin tocar productos.
   * Contrato: vista protegida; editor local con publicacion controlada a `catalogo.encabezado`.
   */
  public function frontend_catalogo() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_catalogo");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: abrir CMS > Frontend > Busqueda como pantalla operativa.
   * Impacto: CMS frontend; permite administrar sinonimos, reglas y mensajes del buscador publico.
   * Contrato: vista protegida; publica configuracion JSON en `erp_ecommerce_configuracion` sin tocar catalogo, precios ni inventario.
   */
  public function frontend_busqueda() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_busqueda");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Categorias como pantalla operativa.
   * Impacto: CMS frontend categorias; prepara imagenes, SEO, orden y destacados sin modificar catalogo ERP.
   * Contrato: vista protegida; editor local, sin escribir BD ni tocar catalogo/precios/inventario.
   */
  public function frontend_categorias() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_categorias");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Marcas como pantalla operativa.
   * Impacto: CMS frontend marcas; prepara logos, banners, SEO, orden y destacados sin modificar catalogo ERP.
   * Contrato: vista protegida; editor local, sin escribir BD ni tocar precios/inventario.
   */
  public function frontend_marcas() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_marcas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Paginas como pantalla operativa.
   * Impacto: CMS frontend paginas; prepara paginas estaticas publicas sin editar archivos frontend.
   * Contrato: vista protegida; editor local, HTML restringido conceptual, sin escribir BD.
   */
  public function frontend_paginas() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_paginas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: abrir CMS > Frontend > Politicas como pantalla operativa.
   * Impacto: CMS frontend politicas; prepara textos legales/operativos sin editar archivos frontend.
   * Contrato: vista protegida; editor local, sin escribir BD ni publicar cambios reales.
   */
  public function frontend_politicas() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_politicas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: abrir la vista CMS enfocada en el contrato real del frontend actual.
   * Impacto: CMS frontend actual; reemplaza el enfoque generico tipo builder por secciones concretas consumibles por API.
   * Contrato: vista protegida; primera fase read-only/planeacion operativa, no escribe archivos fuera del ERP.
   */
  public function frontend_actual() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_actual");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir el modulo CMS interno separado del modulo Ecommerce.
   * Impacto: CMS; permite administrar contenido headless sin mezclarlo con catalogo, precios, inventario ni publicaciones ecommerce.
   * Contrato: vista protegida por `catalogo.ver`; no escribe BD en esta fase.
   */
  public function contenido() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/contenido");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista dedicada de plantillas CMS.
   * Impacto: CMS; separa la administracion conceptual de plantillas de la edicion de contenido.
   * Contrato: vista protegida; no escribe BD en esta fase.
   */
  public function plantillas() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/plantillas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista dedicada de persistencia CMS.
   * Impacto: CMS; separa plan de tablas, guardrails y endpoints bloqueados de la configuracion de plantillas.
   * Contrato: vista protegida; no ejecuta DDL ni activa escrituras reales.
   */
  public function persistencia() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/persistencia");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista dedicada de slots CMS.
   * Impacto: CMS; permite revisar espacios disponibles por pagina y plantilla sin mezclar editor.
   * Contrato: vista protegida; no escribe BD en esta fase.
   */
  public function slots() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/slots");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista dedicada de media CMS.
   * Impacto: CMS; concentra revision visual de imagenes y alt text en modo preview.
   * Contrato: vista protegida; no sube archivos ni escribe BD en esta fase.
   */
  public function media() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/media");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-19
   * Proposito: entregar preflight read-only para persistencia real de Media CMS.
   * Impacto: CMS media; define carpeta publica, limites, MIME y DDL futuro sin subir archivos.
   * Contrato: GET protegido; no escribe BD, no mueve archivos y no borra fisicos.
   */
  public function media_admin_preflight_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $plan = $this->modelo("EcommercePublicoEsquema")->planActualizarCmsMediaBiblioteca(false);
    return json_encode($this->modelo("EcommerceCatalogoPublico")->mediaAdminPreflightInterno($plan), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-20
   * Proposito: listar Media CMS real cuando exista persistencia autorizada.
   * Impacto: CMS media; prepara migracion desde localStorage a BD sin escribir datos.
   * Contrato: GET protegido; si no hay tabla devuelve lista vacia y estado pendiente.
   */
  public function media_admin_listar_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->mediaAdminListarInterno($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-21
   * Proposito: subir imagenes publicas a la biblioteca Media CMS.
   * Impacto: CMS media; habilita imagenes reutilizables para Home/categorias/marcas sin tocar catalogo, precios ni inventario.
   * Contrato: POST protegido por permiso, CSRF global y auditoria explicita; solo acepta imagenes publicas validadas.
   */
  public function media_admin_subir_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->mediaAdminSubirInterno(
      isset($_FILES["archivo"]) ? $_FILES["archivo"] : array(),
      $_POST,
      $this->usuarioActualId()
    );
    SesionSeguridad::registrarAuditoria("cms", "media_admin_subir_erp", array(
      "id_registro" => isset($respuesta["depurar"]["id_media_archivo"]) ? $respuesta["depurar"]["id_media_archivo"] : null,
      "datos_despues" => array(
        "error" => isset($respuesta["error"]) ? (bool) $respuesta["error"] : true,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "codigo" => isset($respuesta["depurar"]["codigo"]) ? $respuesta["depurar"]["codigo"] : "",
        "url" => isset($respuesta["depurar"]["url"]) ? $respuesta["depurar"]["url"] : ""
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-20
   * Proposito: reservar endpoint futuro para actualizar metadatos Media CMS.
   * Impacto: CMS media; protege alt text, uso y metadatos hasta persistencia autorizada.
   * Contrato: POST protegido; siempre bloqueado en fase actual.
   */
  public function media_admin_actualizar_erp() {
    return json_encode($this->respuestaEscrituraCmsMediaBloqueada("media_admin_actualizar_erp"), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-20
   * Proposito: reservar endpoint futuro para archivar Media CMS.
   * Impacto: CMS media; evita borrado fisico o logico sin referencias y auditoria.
   * Contrato: POST protegido; siempre bloqueado en fase actual.
   */
  public function media_admin_archivar_erp() {
    return json_encode($this->respuestaEscrituraCmsMediaBloqueada("media_admin_archivar_erp"), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-24
   * Proposito: eliminar imagenes de Media CMS no usadas por contenido publicado.
   * Impacto: CMS media; permite limpiar duplicados sin romper banners o paginas activas.
   * Contrato: POST protegido por permiso, CSRF global, auditoria explicita y validacion de ruta publica CMS.
   */
  public function media_admin_eliminar_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->mediaAdminEliminarInterno($_POST, $this->usuarioActualId());
    SesionSeguridad::registrarAuditoria("cms", "media_admin_eliminar_erp", array(
      "id_registro" => isset($_POST["id_media_archivo"]) ? intval($_POST["id_media_archivo"]) : null,
      "datos_despues" => array(
        "error" => isset($respuesta["error"]) ? (bool) $respuesta["error"] : true,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "archivo_eliminado" => isset($respuesta["depurar"]["archivo_eliminado"]) ? (bool) $respuesta["depurar"]["archivo_eliminado"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-20
   * Proposito: reservar endpoint futuro para registrar usos de Media CMS.
   * Impacto: CMS media; prepara trazabilidad de imagenes usadas por Home/categorias/marcas/paginas.
   * Contrato: POST protegido; siempre bloqueado en fase actual.
   */
  public function media_admin_usos_erp() {
    return json_encode($this->respuestaEscrituraCmsMediaBloqueada("media_admin_usos_erp"), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista dedicada de preview JSON/API CMS.
   * Impacto: CMS; separa la revision del contrato API de la captura editorial.
   * Contrato: vista protegida; genera preview read-only.
   */
  public function json() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/json");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista de plantillas frontend administrables por CMS.
   * Impacto: CMS frontend; separa layouts/componentes visuales del contenido editorial.
   * Contrato: vista protegida; read-only y sin editar archivos del frontend.
   */
  public function frontend_plantillas() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_plantillas");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: abrir el constructor visual administrativo de paginas frontend.
   * Impacto: CMS frontend; muestra como se ensamblan plantilla, secciones, componentes y slots sin mezclarlo con captura editorial.
   * Contrato: vista protegida; read-only, no guarda HTML y no edita archivos del frontend.
   */
  public function frontend_constructor() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_constructor");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: abrir la vista de componentes frontend permitidos.
   * Impacto: CMS frontend; define catalogo seguro de componentes, variantes y slots compatibles.
   * Contrato: vista protegida; read-only y sin HTML/CSS/JS libre.
   */
  public function frontend_componentes() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_componentes");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: abrir la vista de activaciones futuras de temas y plantillas frontend.
   * Impacto: CMS frontend; permite revisar que plantilla aplicara por pagina/canal/contexto antes de persistencia real.
   * Contrato: vista protegida; read-only, sin cambiar tema activo ni editar archivos del frontend.
   */
  public function frontend_activaciones() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $this->vista("apps/erp/cms/frontend_activaciones");
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar estado interno del CMS y plan de tablas propuesto.
   * Impacto: CMS ecommerce; prepara autorizacion futura sin ejecutar DDL.
   * Contrato: GET protegido por `catalogo.ver`; read-only.
   */
  public function contenido_admin_estado_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $auditoria = $this->modelo("EcommercePublicoEsquema")->auditarCmsContenido();
    $plan = $this->modelo("EcommercePublicoEsquema")->planActualizarCmsContenido(false);
    return json_encode($this->modelo("EcommerceCatalogoPublico")->contenidoAdminEstadoInterno($auditoria, $plan));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar manifest interno del CMS con plantilla, slots y tipos de bloque.
   * Impacto: CMS ecommerce; alimenta el panel administrativo separado.
   * Contrato: GET protegido por `catalogo.ver`; read-only.
   */
  public function contenido_admin_manifest_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->contenidoAdminManifestInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar previsualizacion interna del JSON de una pagina CMS.
   * Impacto: CMS ecommerce; permite validar contenido antes de persistencia real.
   * Contrato: GET protegido por `catalogo.ver`; read-only.
   */
  public function contenido_admin_pagina_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->contenidoAdminPaginaInterna($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: listar bloques CMS guardados en BD para reutilizarlos en el editor interno.
   * Impacto: CMS contenido; permite recuperar borradores sin publicarlos ni exponerlos a la API publica.
   * Contrato: GET protegido por cms.ver/catalogo.ver; solo lectura.
   */
  public function contenido_admin_bloques_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->contenidoBloquesAdminInterno($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-10
   * Proposito: entregar manifest read-only de plantillas de vista frontend.
   * Impacto: CMS frontend; prepara el contrato que consumira el renderer del ecommerce.
   * Contrato: GET protegido; no lee ni modifica archivos del frontend.
   */
  public function frontend_admin_manifest_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->frontendPlantillasAdminManifestInterno($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: entregar estado read-only del esquema CMS frontend.
   * Impacto: CMS frontend; muestra tablas propuestas para layouts, componentes, plantillas, secciones y activaciones sin ejecutar DDL.
   * Contrato: GET protegido; no edita archivos frontend ni escribe BD.
   */
  public function frontend_admin_estado_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    $esquema = $this->modelo("EcommercePublicoEsquema");
    $auditoria = $esquema->auditarCmsFrontend();
    $plan = $esquema->planActualizarCmsFrontend(false);
    return json_encode(array(
      "error" => false,
      "tipo" => "info",
      "mensaje" => "CMS frontend en modo read-only",
      "depurar" => array(
        "modo" => "readonly",
        "fase" => "cms_frontend_persistencia_diseno",
        "persistencia_real" => false,
        "pantalla" => "/cms/frontend_plantillas",
        "endpoints_admin" => array(
          "estado" => "/cms/frontend_admin_estado_erp",
          "manifest" => "/cms/frontend_admin_manifest_erp"
        ),
        "post_bloqueados" => array(
          array("metodo" => "POST", "ruta" => "/cms/frontend_plantilla_guardar_erp", "estado" => "bloqueado_readonly"),
          array("metodo" => "POST", "ruta" => "/cms/frontend_plantilla_estatus_erp", "estado" => "bloqueado_readonly"),
          array("metodo" => "POST", "ruta" => "/cms/frontend_seccion_guardar_erp", "estado" => "bloqueado_readonly"),
          array("metodo" => "POST", "ruta" => "/cms/frontend_seccion_estatus_erp", "estado" => "bloqueado_readonly")
        ),
        "esquema" => array(
          "auditoria" => isset($auditoria["depurar"]) ? $auditoria["depurar"] : array(),
          "plan" => isset($plan["depurar"]) ? $plan["depurar"] : array()
        ),
        "guardrails" => array(
          "read_only" => true,
          "no_escribe_bd" => true,
          "no_ejecuta_ddl" => true,
          "no_edita_archivos_frontend" => true,
          "no_html_libre" => true,
          "no_css_libre" => true,
          "no_js_libre" => true
        )
      )
    ));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: guardar bloques editoriales CMS como borrador real en BD.
   * Impacto: CMS contenido; habilita persistencia controlada sin publicar slots ni tocar catalogo, precios o inventario.
   * Contrato: POST protegido por cms.editar/catalogo.editar, CSRF global y auditoria explicita.
   */
  public function contenido_bloque_guardar_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->contenidoBloqueGuardarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "contenido_bloque_guardar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "codigo" => isset($depurar["codigo"]) ? $depurar["codigo"] : null,
        "tipo_bloque" => isset($depurar["tipo_bloque"]) ? $depurar["tipo_bloque"] : null,
        "estatus" => isset($depurar["estatus"]) ? $depurar["estatus"] : null,
        "publica_contenido" => false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-12
   * Proposito: cambiar estatus de bloques CMS guardados entre borrador y pausado.
   * Impacto: CMS contenido; permite pausar/reactivar borradores sin publicar contenido real.
   * Contrato: POST protegido por cms.editar/catalogo.editar, CSRF global y auditoria explicita.
   */
  public function contenido_bloque_estatus_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->contenidoBloqueEstatusInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "contenido_bloque_estatus_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "estatus_anterior" => isset($depurar["estatus_anterior"]) ? $depurar["estatus_anterior"] : null
      ),
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "estatus" => isset($depurar["estatus"]) ? $depurar["estatus"] : null,
        "publica_contenido" => false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: guardar publicacion interna de un bloque CMS en un slot/pagina/contexto.
   * Impacto: CMS contenido; arma paginas para preview administrativo sin exponerlas aun en API publica.
   * Contrato: POST protegido por cms.editar/catalogo.editar, CSRF y auditoria explicita; no publica en ecommerce.
   */
  public function contenido_publicacion_guardar_erp() {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->contenidoPublicacionGuardarInterna($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "contenido_publicacion_guardar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "pagina" => isset($depurar["pagina"]) ? $depurar["pagina"] : null,
        "estatus" => isset($depurar["estatus"]) ? $depurar["estatus"] : null,
        "publicado_api" => false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-13
   * Proposito: cambiar estatus de publicaciones CMS colocadas en slots.
   * Impacto: CMS contenido; permite publicar, pausar o devolver a borrador una colocacion interna sin modificar el bloque base.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF global y auditoria explicita.
   */
  public function contenido_publicacion_estatus_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->contenidoPublicacionEstatusInterna($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "contenido_publicacion_estatus_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_antes" => array(
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "estatus_anterior" => isset($depurar["estatus_anterior"]) ? $depurar["estatus_anterior"] : null
      ),
      "datos_despues" => array(
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "pagina" => isset($depurar["pagina"]) ? $depurar["pagina"] : null,
        "estatus" => isset($depurar["estatus"]) ? $depurar["estatus"] : null,
        "publicado_api" => false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-23
   * Proposito: publicar el banner operativo de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste `home_banner` como bloque publicado para que lo lea la API publica.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no toca catalogo, precios ni inventario.
   */
  public function frontend_home_banner_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeBannerPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_banner_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar la franja promocional de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste `home_promo` como bloque publicado para el slot `home.promo`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no toca catalogo, precios ni inventario.
   */
  public function frontend_home_promo_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomePromoPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_promo_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar las categorias destacadas de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste `home_categorias_destacadas` en el slot `home.categorias`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; solo referencia categorias reales.
   */
  public function frontend_home_categorias_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeCategoriasPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_categorias_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar promos visuales de categorias desde CMS Frontend Home.
   * Impacto: CMS contenido; persiste `home_promos_categoria` en el slot `home.promos`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; solo referencia categorias reales.
   */
  public function frontend_home_promos_categoria_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomePromosCategoriaPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_promos_categoria_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar marcas destacadas de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste `home_marcas_destacadas` en el slot `home.marcas`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no crea ni modifica marcas reales.
   */
  public function frontend_home_marcas_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeMarcasPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_marcas_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-31
   * Proposito: previsualizar marcas reales de una categoria para CMS Home.
   * Impacto: CMS Frontend Home; permite ver logo/banner disponible antes de publicar marcas destacadas.
   * Contrato: GET protegido, solo lectura; no modifica marcas, catalogo, precios ni inventario.
   */
  public function frontend_home_marcas_preview_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->frontendHomeMarcasPreviewInterno($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-04
   * Proposito: publicar carrusel de nuevos productos por categoria desde CMS Home.
   * Impacto: CMS/API Home; persiste `productos_carrusel` en `home.productos_carrusel`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar y CSRF; no modifica productos, precios ni inventario.
   */
  public function frontend_home_productos_carrusel_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeProductosCarruselPublicarInterno($_POST, $this->usuarioActualId());
    $this->auditarPublicacionHomeCms("frontend_home_productos_carrusel_publicar_erp", $respuesta);
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-04
   * Proposito: publicar banner editorial de categoria desde CMS Home.
   * Impacto: CMS/API Home; persiste `promo_editorial` en `home.promo_editorial`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar y CSRF; no modifica categorias/productos.
   */
  public function frontend_home_promo_editorial_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomePromoEditorialPublicarInterno($_POST, $this->usuarioActualId());
    $this->auditarPublicacionHomeCms("frontend_home_promo_editorial_publicar_erp", $respuesta);
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-04
   * Proposito: publicar categoria visual con productos desde CMS Home.
   * Impacto: CMS/API Home; persiste `visual_productos` en `home.visual_productos`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar y CSRF; no usa carrusel ni edita catalogo.
   */
  public function frontend_home_visual_productos_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeVisualProductosPublicarInterno($_POST, $this->usuarioActualId());
    $this->auditarPublicacionHomeCms("frontend_home_visual_productos_publicar_erp", $respuesta);
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar Esenciales Artiani desde CMS Frontend Home.
   * Impacto: CMS contenido; persiste `home_esenciales_artiani` en el slot `home.esenciales`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; maximo tres cards editoriales.
   */
  public function frontend_home_esenciales_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeEsencialesPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_esenciales_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar Compra guiada desde CMS Frontend Home.
   * Impacto: CMS contenido; persiste `home_compra_guiada` en el slot `home.compra_guiada`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no edita productos ni taxonomia.
   */
  public function frontend_home_compra_guiada_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeCompraGuiadaPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_compra_guiada_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar la configuracion editorial de la pagina Catalogo.
   * Impacto: CMS contenido; persiste encabezado y estados publicos en `catalogo.encabezado`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no modifica productos ni filtros.
   */
  public function frontend_catalogo_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendCatalogoPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_catalogo_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: entregar al CMS el manifest actual de busqueda inteligente.
   * Impacto: CMS frontend busqueda; permite editar desde la configuracion activa o defaults de codigo.
   * Contrato: solo lectura, protegido por permiso; no escribe BD ni toca catalogo.
   */
  public function frontend_busqueda_manifest_erp() {
    $this->requerirAlgunPermiso(array("cms.ver", "catalogo.ver"));
    return json_encode($this->modelo("EcommerceCatalogoPublico")->busquedaManifestPublica($_GET), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
   * Proposito: publicar configuracion operativa del buscador publico desde CMS.
   * Impacto: API ecommerce publico; actualiza sinonimos, prioridades, reglas y mensajes de busqueda.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no toca productos, precios ni inventario.
   */
  public function frontend_busqueda_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->guardarBusquedaInteligenteConfigInterna($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_busqueda_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "clave" => "busqueda_inteligente_config",
        "fuente" => isset($depurar["fuente"]) ? $depurar["fuente"] : "",
        "sinonimos" => isset($depurar["resumen"]["sinonimos"]) ? $depurar["resumen"]["sinonimos"] : 0,
        "stopwords" => isset($depurar["resumen"]["stopwords"]) ? $depurar["resumen"]["stopwords"] : 0,
        "categorias_probables" => isset($depurar["resumen"]["categorias_probables"]) ? $depurar["resumen"]["categorias_probables"] : 0,
        "publicado_api" => empty($respuesta["error"])
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar productos destacados de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste criterio/referencias de productos en el slot `home.destacados`.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no modifica productos, precios ni inventario.
   */
  public function frontend_home_productos_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeProductosPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_productos_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "modo" => isset($depurar["modo"]) ? $depurar["modo"] : "",
        "referencias_total" => isset($depurar["referencias_total"]) ? $depurar["referencias_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar colecciones de productos de Home desde CMS Frontend.
   * Impacto: CMS contenido; persiste varias vitrinas en `home.destacados` sin reemplazar productos destacados.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; solo envia criterios/referencias.
   */
  public function frontend_home_colecciones_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendHomeColeccionesPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_home_colecciones_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "colecciones_total" => isset($depurar["colecciones_total"]) ? $depurar["colecciones_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar la configuracion global operativa del frontend desde CMS.
   * Impacto: CMS frontend global; persiste marca, contacto, SEO, redes y assets para configuracion_inicial.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; no expone secretos ni toca catalogo.
   */
  public function frontend_global_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendGlobalPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_global_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "codigo" => isset($depurar["codigo"]) ? $depurar["codigo"] : "",
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-30
   * Proposito: publicar el widget WhatsApp multi contacto para el frontend publico.
   * Impacto: CMS frontend global; crea/actualiza bloque `global.whatsapp_chat` consumible por contenido_pagina.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; valida telefonos publicos y no toca catalogo.
   */
  public function frontend_global_whatsapp_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendGlobalWhatsappPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_global_whatsapp_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "id_publicacion_contenido" => isset($depurar["id_publicacion_contenido"]) ? $depurar["id_publicacion_contenido"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : "global.whatsapp_chat",
        "contactos_visibles" => isset($depurar["contactos_visibles"]) ? $depurar["contactos_visibles"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-25
   * Proposito: publicar enriquecimiento visual/SEO de categorias desde CMS Frontend.
   * Impacto: CMS frontend categorias; agrega imagenes y textos publicos sin modificar catalogo, precios ni inventario.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; la categoria real debe existir en ERP/API.
   */
  public function frontend_categorias_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendCategoriasPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_categorias_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "codigo" => isset($depurar["codigo"]) ? $depurar["codigo"] : "",
        "categorias_total" => isset($depurar["categorias_total"]) ? $depurar["categorias_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-28
   * Proposito: publicar enriquecimiento visual/SEO de marcas desde CMS Frontend.
   * Impacto: CMS frontend marcas; agrega logos y banners publicos sin modificar catalogo, precios ni inventario.
   * Contrato: POST protegido por cms.publicar/catalogo.editar, CSRF y auditoria; la marca real debe existir en ERP/API.
   */
  public function frontend_marcas_publicar_erp() {
    $this->requerirAlgunPermiso(array("cms.publicar", "catalogo.editar"));
    $respuesta = $this->modelo("EcommerceCatalogoPublico")->frontendMarcasPublicarInterno($_POST, $this->usuarioActualId());
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", "frontend_marcas_publicar_erp", array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "codigo" => isset($depurar["codigo"]) ? $depurar["codigo"] : "",
        "marcas_total" => isset($depurar["marcas_total"]) ? $depurar["marcas_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
    return json_encode($respuesta, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: declarar contrato futuro para guardar plantillas de vista frontend.
   * Impacto: CMS frontend; reserva el endpoint sin activar persistencia ni editar archivos del ecommerce.
   * Contrato: POST protegido; siempre bloqueado hasta respaldo, DDL y auditoria autorizados.
   */
  public function frontend_plantilla_guardar_erp() {
    return json_encode($this->respuestaEscrituraCmsFrontendBloqueada("frontend_plantilla_guardar_erp"));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: declarar contrato futuro para cambiar estatus o activar plantillas de vista.
   * Impacto: CMS frontend; evita activar layouts reales antes de persistencia autorizada.
   * Contrato: POST protegido; siempre bloqueado en fase read-only.
   */
  public function frontend_plantilla_estatus_erp() {
    return json_encode($this->respuestaEscrituraCmsFrontendBloqueada("frontend_plantilla_estatus_erp"));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: declarar contrato futuro para guardar secciones de una plantilla frontend.
   * Impacto: CMS frontend; prepara mapeos slot-componente-variante sin ejecutar cambios.
   * Contrato: POST protegido; siempre bloqueado en fase read-only.
   */
  public function frontend_seccion_guardar_erp() {
    return json_encode($this->respuestaEscrituraCmsFrontendBloqueada("frontend_seccion_guardar_erp"));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-08-11
   * Proposito: declarar contrato futuro para cambiar estatus u orden de secciones frontend.
   * Impacto: CMS frontend; protege el renderer publico hasta contar con persistencia real.
   * Contrato: POST protegido; siempre bloqueado en fase read-only.
   */
  public function frontend_seccion_estatus_erp() {
    return json_encode($this->respuestaEscrituraCmsFrontendBloqueada("frontend_seccion_estatus_erp"));
  }

  private function respuestaEscrituraCmsBloqueada($endpoint) {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    return array(
      "error" => true,
      "tipo" => "warning",
      "mensaje" => "La persistencia real del CMS aun no esta autorizada. Esta accion queda bloqueada en modo read-only.",
      "depurar" => array(
        "endpoint" => $endpoint,
        "fase" => "cms_readonly",
        "persistencia_real" => false,
        "requiere" => array(
          "respaldo_bd",
          "ddl_autorizado",
          "csrf_activo",
          "auditoria_explicita",
          "sanitizacion_html",
          "politica_media"
        ),
        "guardrails" => array(
          "no_escribe_bd" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true,
          "no_publica_contenido_real" => true
        )
      )
    );
  }

  private function respuestaEscrituraCmsFrontendBloqueada($endpoint) {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    return array(
      "error" => true,
      "tipo" => "warning",
      "mensaje" => "La persistencia real de plantillas frontend aun no esta autorizada. Esta accion queda bloqueada en modo read-only.",
      "depurar" => array(
        "endpoint" => $endpoint,
        "fase" => "cms_frontend_readonly",
        "persistencia_real" => false,
        "requiere" => array(
          "respaldo_bd",
          "ddl_frontend_autorizado",
          "csrf_activo",
          "auditoria_explicita",
          "validacion_componentes",
          "renderer_frontend_implementado"
        ),
        "guardrails" => array(
          "no_escribe_bd" => true,
          "no_edita_archivos_frontend" => true,
          "no_html_libre" => true,
          "no_css_libre" => true,
          "no_js_libre" => true,
          "no_publica_layout_real" => true
        )
      )
    );
  }

  private function respuestaEscrituraCmsMediaBloqueada($endpoint) {
    $this->requerirAlgunPermiso(array("cms.editar", "catalogo.editar"));
    return array(
      "error" => true,
      "tipo" => "warning",
      "mensaje" => "La persistencia real de Media CMS aun no esta autorizada. Esta accion queda bloqueada.",
      "depurar" => array(
        "endpoint" => $endpoint,
        "fase" => "cms_media_readonly_preflight",
        "persistencia_real" => false,
        "requiere" => array(
          "respaldo_bd",
          "ddl_media_autorizado",
          "carpeta_publica_creada",
          "csrf_activo",
          "auditoria_explicita",
          "validacion_mime_extension_peso",
          "hash_sha256",
          "alt_text_obligatorio"
        ),
        "guardrails" => array(
          "no_escribe_bd" => true,
          "no_mueve_archivos" => true,
          "no_borra_fisicos" => true,
          "no_expone_rutas_internas" => true,
          "no_modifica_catalogo" => true,
          "no_modifica_inventario" => true
        )
      )
    );
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-04
   * Proposito: centralizar auditoria de publicaciones CMS Home agregadas por modulos nuevos.
   * Impacto: controlador CMS; registra resultado sin duplicar estructura ni exponer payloads completos.
   * Contrato: no modifica respuesta; solo registra metadatos operativos de la publicacion.
   */
  private function auditarPublicacionHomeCms($accion, $respuesta) {
    $depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    SesionSeguridad::registrarAuditoria("cms", $accion, array(
      "resultado" => empty($respuesta["error"]) ? "ok" : "error",
      "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
      "datos_despues" => array(
        "id_bloque" => isset($depurar["id_bloque"]) ? $depurar["id_bloque"] : null,
        "slot" => isset($depurar["slot"]) ? $depurar["slot"] : null,
        "items_total" => isset($depurar["items_total"]) ? $depurar["items_total"] : 0,
        "publicado_api" => isset($depurar["publicado_api"]) ? $depurar["publicado_api"] : false
      )
    ));
  }
}
