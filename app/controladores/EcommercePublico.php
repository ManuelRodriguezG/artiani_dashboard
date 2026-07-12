<?php

class EcommercePublico extends Controlador {

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer manifiesto de contratos API que consumira el proyecto ecommerce externo.
   * Impacto: Ecommerce publico; documenta rutas, parametros y guardrails sin construir vista en ERP.
   * Contrato: GET publico read-only; no consulta datos sensibles ni escribe BD.
   */
  public function contratos() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->contratosApiPublicos());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer estado/readiness del API ecommerce para el frontend externo.
   * Impacto: Ecommerce publico; permite detectar si esquema, publicaciones y configuracion ya estan disponibles.
   * Contrato: GET publico read-only; no expone datos sensibles ni escribe BD.
   */
  public function estado() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->estadoApiPublica());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer contrato publico read-only del catalogo vivo.
   * Impacto: Ecommerce publico; solo devuelve publicaciones aprobadas si existe esquema ecommerce.
   * Contrato: GET publico; no requiere sesion, no escribe BD, no expone stock exacto.
   */
  public function catalogo() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->catalogoPublico($_GET));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer detalle publico por slug de una publicacion ecommerce.
   * Impacto: Ecommerce publico; prepara ficha de producto sin usar `ecom_*` como fuente.
   * Contrato: GET publico; solo lectura y solo publicaciones con estatus `publicado`.
   */
  public function producto($slug = "") {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->productoPublico($slug));
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer filtros publicos disponibles para catalogo vivo.
   * Impacto: Ecommerce publico; permite UI por mascota/necesidad/marca/categoria.
   * Contrato: GET publico; no requiere sesion y no expone datos internos.
   */
  public function filtros() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->filtrosPublicos());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-12
   * Proposito: exponer configuracion publica que consumira el frontend ecommerce externo.
   * Impacto: Ecommerce publico; evita hardcodear WhatsApp, moneda y politicas en la web.
   * Contrato: GET publico read-only; solo devuelve claves publicables.
   */
  public function configuracion() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->configuracionPublica());
  }

  /**
   * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-11
   * Proposito: exponer disponibilidad publica simple de una publicacion o SKU publicado.
   * Impacto: Ecommerce publico/Inventario; traduce stock interno a estados simples.
   * Contrato: GET publico; no muestra cantidades exactas ni descuenta inventario.
   */
  public function disponibilidad() {
    return json_encode($this->modelo("EcommerceCatalogoPublico")->disponibilidadPublica($_GET));
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
