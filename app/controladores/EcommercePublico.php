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
}
