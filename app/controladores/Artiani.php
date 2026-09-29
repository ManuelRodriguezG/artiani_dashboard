<?php

class Artiani extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: abrir la Enciclopedia de especies del modulo Artiani.
   * Impacto: Conocimiento/Catalogo/Atencion; inicia la base oficial para asesorar clientes, capacitar personal y preparar fichas publicas.
   * Contrato: vista protegida por `artiani.conocimiento.ver`; no escribe BD.
   */
  public function index() {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.ver", "catalogo.ver", "crm.ver", "ventas.ver"));
    $this->vista("apps/erp/artiani/enciclopedia");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: alias operativo para abrir explicitamente la Enciclopedia de especies.
   * Impacto: Conocimiento Artiani; facilita menu y enlaces internos sin duplicar vista.
   * Contrato: GET protegido; no escribe BD.
   */
  public function enciclopedia() {
    $this->index();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: abrir una ficha independiente de especie para revisar todos los detalles sin saturar el listado.
   * Impacto: Enciclopedia Artiani; mejora flujo operativo de consulta y deja ruta estable para enlaces internos/web.
   * Contrato: GET protegido, read-only; recibe slug como parametro de ruta.
   */
  public function especie($slug = "") {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.ver", "catalogo.ver", "crm.ver", "ventas.ver"));
    $this->vista("apps/erp/artiani/especie", array("slug" => trim((string) $slug)));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-29
   * Proposito: abrir formulario separado para capturar una nueva ficha Artiani.
   * Impacto: Enciclopedia Artiani; prepara alta estructurada antes de autorizar persistencia en BD.
   * Contrato: vista protegida por permiso de edicion o catalogo; no guarda datos en esta etapa.
   */
  public function especie_nueva() {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.editar", "catalogo.editar"));
    $this->vista("apps/erp/artiani/especie_formulario", array("modo" => "nuevo"));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: listar especies y resumenes de la Enciclopedia Artiani.
   * Impacto: UI Enciclopedia; entrega conocimiento base estructurado sin exponer costos ni stock.
   * Contrato: GET autenticado, read-only; acepta q, grupo, dificultad y limite.
   */
  public function especies_listar_erp() {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.ver", "catalogo.ver", "crm.ver", "ventas.ver"));
    return json_encode($this->modelo("ArtianiConocimientoErp")->listarEspecies($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: consultar una ficha completa de especie para asesorar con precision operativa.
   * Impacto: Atencion/Capacitacion; centraliza cuidados, preguntas clave, riesgos y productos relacionados sugeridos.
   * Contrato: GET autenticado, read-only; recibe `slug`.
   */
  public function especie_consultar_erp() {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.ver", "catalogo.ver", "crm.ver", "ventas.ver"));
    $slug = isset($_GET["slug"]) ? $_GET["slug"] : "";
    return json_encode($this->modelo("ArtianiConocimientoErp")->consultarEspecie($slug));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: entregar catalogos de filtros y taxonomias iniciales del conocimiento Artiani.
   * Impacto: UI Enciclopedia; evita valores hardcodeados dispersos en JS.
   * Contrato: GET autenticado, read-only.
   */
  public function catalogos_erp() {
    $this->requerirAlgunPermiso(array("artiani.conocimiento.ver", "catalogo.ver", "crm.ver", "ventas.ver"));
    return json_encode($this->modelo("ArtianiConocimientoErp")->catalogos());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: auditar si existen las tablas canonicas planeadas para conocimiento Artiani.
   * Impacto: Arquitectura; prepara evolucion a BD sin ejecutar DDL ni insertar datos.
   * Contrato: GET protegido por soporte/configuracion o conocimiento; read-only.
   */
  public function esquema_auditar_erp() {
    $this->requerirAlgunPermiso(array("sistema.soporte", "configuracion.administrar", "artiani.conocimiento.ver"));
    return json_encode($this->modelo("ArtianiConocimientoEsquema")->auditarArtianiConocimiento());
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-09-28
   * Proposito: generar el plan dry-run de tablas para Enciclopedia, relaciones y futuras fichas de productos.
   * Impacto: Arquitectura Artiani; permite revisar DDL antes de pedir autorizacion explicita.
   * Contrato: GET protegido; siempre dry-run, no ejecuta DDL.
   */
  public function esquema_plan_erp() {
    $this->requerirAlgunPermiso(array("sistema.soporte", "configuracion.administrar"));
    return json_encode($this->modelo("ArtianiConocimientoEsquema")->planActualizarArtianiConocimiento(false));
  }
}
