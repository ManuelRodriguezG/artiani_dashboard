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
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar evento anonimo de navegacion sin guardarlo.
   * Impacto: Ecommerce publico; prepara analitica de mascotas, productos y conversion a WhatsApp.
   * Contrato: POST publico preflight; no escribe BD y no acepta datos personales en tracking.
   */
  public function evento_navegacion() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("evento_navegacion_preflight"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->eventoNavegacionPreflight($this->entradaJsonPublica()));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-29
   * Proposito: validar busqueda anonima ecommerce sin persistirla.
   * Impacto: Ecommerce publico; prepara aprendizaje de demanda, faltantes y necesidades por mascota.
   * Contrato: POST publico preflight; no escribe BD y no guarda datos personales.
   */
  public function busqueda_registrar() {
    if ($this->esOptionsPublicas()) { return $this->responderOpcionesPublicas(); }
    if (!isset($_SERVER["REQUEST_METHOD"]) || strtoupper((string) $_SERVER["REQUEST_METHOD"]) !== "POST") {
      return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->metodoPostRequerido("busqueda_preflight"));
    }
    return $this->responderApiPublica($this->modelo("EcommerceCatalogoPublico")->busquedaRegistrarPreflight($this->entradaJsonPublica()));
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
        "total_ok" => isset($respuesta["depurar"]["total_ok"]) ? intval($respuesta["depurar"]["total_ok"]) : 0,
        "total_error" => isset($respuesta["depurar"]["total_error"]) ? intval($respuesta["depurar"]["total_error"]) : 0
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
}
