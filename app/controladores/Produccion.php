<?php

class Produccion extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: abrir el primer flujo de fabricacion ligera para peceras sin mezclarlo con Compras.
     * Impacto: Produccion/Fabricacion; no escribe en BD ni afecta inventario.
     * Contrato: requiere permiso transicional de ERP hasta sembrar permisos `produccion.*`.
     */
    public function index() {
        $this->peceras();
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: mostrar calculadora operativa de cortes de vidrio para pedidos a proveedor.
     * Impacto: permite preparar hoja de cortes imprimible/exportable sin crear ordenes de compra.
     * Contrato: acceso permitido con compras.ver, catalogo.ver o inventario.ver durante fase MVP.
     */
    public function peceras() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "inventario.ver"));
        $this->vista("apps/erp/produccion/peceras");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: listar perfiles/borradores locales de peceras en una vista separada.
     * Impacto: Produccion/Peceras; facilita consultar y editar perfiles guardados sin entrar al calculador a ciegas.
     * Contrato: MVP basado en localStorage hasta implementar persistencia en BD.
     */
    public function peceras_perfiles() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "inventario.ver"));
        $this->vista("apps/erp/produccion/peceras_perfiles");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: armar pedidos de vidrio con varios perfiles de peceras y estimar hojas necesarias.
     * Impacto: Produccion/Peceras; agrupa cortes antes de Compras/proveedor.
     * Contrato: calculo local por area/merma; no ejecuta optimizacion de nesting ni escribe en BD.
     */
    public function peceras_pedido_vidrio() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "inventario.ver"));
        $this->vista("apps/erp/produccion/peceras_pedido_vidrio");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: auditar estructura futura de Produccion/Peceras sin ejecutar DDL.
     * Impacto: soporte tecnico del modulo Produccion; read-only sobre INFORMATION_SCHEMA.
     * Contrato: requiere permiso tecnico y devuelve JSON estandar.
     */
    public function esquema_auditar_peceras_erp() {
        $this->requerirAlgunPermiso(array("sistema.soporte", "migraciones.ver"));
        return json_encode($this->modelo("ProduccionEsquema")->auditarPecerasErp());
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-03
     * Proposito: generar plan DDL dry-run para persistencia de peceras.
     * Impacto: Produccion/Fabricacion; no ejecuta cambios de esquema.
     * Contrato: siempre usa ejecutar=false; la aplicacion real requiere otro flujo con respaldo y autorizacion.
     */
    public function esquema_plan_peceras_erp() {
        $this->requerirAlgunPermiso(array("sistema.soporte", "migraciones.ver"));
        return json_encode($this->modelo("ProduccionEsquema")->planActualizarPecerasErp(false));
    }
}

?>
