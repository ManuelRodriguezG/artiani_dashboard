<?php

class Contabilidad extends Controlador {

  public function __construct() {
    $this->requerirSesion();
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-08-29
   * Proposito: abrir el tablero mensual de conciliacion contable sin persistir datos.
   * Impacto: Finanzas/Contabilidad; ayuda a preparar informacion bancaria y CFDI para el contador.
   * Contrato: requiere finanzas.ver; el MVP procesa archivos en navegador y exporta CSV/JSON.
   */
  public function cierre_mensual() {
    $this->requerirPermiso("finanzas.ver");
    $this->vista("apps/erp/contabilidad/cierre_mensual");
  }
}
