<?php

class Operacion extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-28
     * Proposito: abrir Mini inventario operativo como modulo puente sin afectar inventario oficial.
     * Impacto: Operacion/Mini inventario; solo lectura de catalogo y captura temporal en navegador.
     * Contrato: permite acceso a usuarios con permisos actuales de compras, catalogo, almacen o inventario.
     */
    public function mini_inventario() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "almacen.ver", "inventario.ver"));
        $this->vista("apps/erp/operacion/mini_inventario", array(
            "puede_compras" => $this->usuarioTieneOperacionPermiso("compras.ver"),
            "puede_catalogo" => $this->usuarioTieneOperacionPermiso("catalogo.ver"),
            "puede_almacen" => $this->usuarioTieneOperacionPermiso("almacen.ver"),
            "puede_inventario" => $this->usuarioTieneOperacionPermiso("inventario.ver")
        ));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-09-28
     * Proposito: listar mini inventarios operativos locales antes de abrir edicion.
     * Impacto: Operacion/Mini inventario; no consulta ni escribe inventario oficial.
     * Contrato: bandeja local en navegador, puente temporal previo a persistencia formal en BD.
     */
    public function mini_inventarios() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "almacen.ver", "inventario.ver"));
        $this->vista("apps/erp/operacion/mini_inventarios", array());
    }

    public function mini_inventario_catalogos_erp() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "almacen.ver", "inventario.ver"));
        return json_encode($this->modelo("OperacionMiniInventarioErp")->catalogos());
    }

    public function mini_inventario_productos_erp() {
        $this->requerirAlgunPermiso(array("compras.ver", "catalogo.ver", "almacen.ver", "inventario.ver"));
        return json_encode($this->modelo("OperacionMiniInventarioErp")->productosCatalogoVenta($_GET));
    }

    private function usuarioTieneOperacionPermiso($permiso) {
        $seguridad = $this->modelo("SeguridadPermisos");
        return $seguridad->usuarioTienePermiso($this->usuarioActualId(), $permiso);
    }
}
