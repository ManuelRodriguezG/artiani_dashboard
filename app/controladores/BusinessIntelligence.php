<?php

class BusinessIntelligence extends Controlador {

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: abrir el primer tablero BI separado para publicidad y temporadas.
   * Impacto: Business Intelligence; no mezcla analytics legacy con Ecommerce / Analytics nuevo.
   * Contrato: vista protegida; solo lectura.
   */
  public function index() {
    return $this->publicidad_temporadas();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: mostrar tablero de demanda historica para decidir publicidad por temporada.
   * Impacto: Business Intelligence; consulta datos agregados sin escribir BD.
   * Contrato: requiere sesion y alguno de los permisos comerciales/gerenciales existentes.
   */
  public function publicidad_temporadas() {
    $this->requerirAlgunPermiso($this->permisosLectura());
    $this->vista("apps/erp/bi/publicidad_temporadas");
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: entregar metricas BI read-only de busquedas y consumibles legacy.
   * Impacto: Business Intelligence; alimenta dashboard comercial sin tocar ventas, inventario ni ecommerce nuevo.
   * Contrato: GET JSON; filtros desde/hasta/limite y respuesta con contrato error/tipo/mensaje/depurar.
   */
  public function publicidad_dashboard_erp() {
    $this->requerirAlgunPermiso($this->permisosLectura());
    return json_encode($this->modelo("BusinessIntelligenceErp")->publicidadTemporadasDashboard($_GET));
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-31
   * Proposito: diagnosticar tablas legacy BI disponibles antes de profundizar reportes.
   * Impacto: Business Intelligence; permite validar productivo/staging sin DDL.
   * Contrato: GET JSON read-only.
   */
  public function diagnostico_erp() {
    $this->requerirAlgunPermiso($this->permisosLectura());
    return json_encode($this->modelo("BusinessIntelligenceErp")->diagnostico());
  }

  private function permisosLectura() {
    return array("reportes.ver", "finanzas.ver", "ventas.ver", "catalogo.ver", "ecommerce.ver");
  }
}
