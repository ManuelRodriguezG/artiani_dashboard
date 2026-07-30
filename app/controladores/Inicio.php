<?php

class Inicio extends Controlador {

  public function __construct() {
    if (!$_SESSION['id_usuario']) {
      header('Location: /autenticacion/login');
      exit;
    }
  }

  public function quienes_somos() {
    var_dump('quienes somos');
  }

  public function prueba() {
    var_dump("prueba");
  }

  public function index() {
    $this->vista('apps/erp/resumen/index');
  }

  /**
   * IA: Codex GPT-5
   * Fecha: 2026-07-29
   * Proposito: entregar el resumen operativo inicial del ERP para la primera pantalla del panel.
   * Impacto: Inicio/Resumen; consulta informacion read-only visible segun permisos sin modificar modulos operativos.
   * Contrato: GET autenticado, salida JSON con contrato ERP estandar.
   */
  public function resumen_erp() {
    $modelo = $this->modelo("ResumenErp");
    echo json_encode($modelo->consultar(
      $this->usuarioActualId(),
      isset($_SESSION["permisos"]) ? $_SESSION["permisos"] : array()
    ));
  }

}
