<?php

class Inventario extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    public function index() {
        $this->requerirPermiso("inventario.ver");
        $this->redirigir("/inventario/productos_existencias");
    }

    public function productos_existencias() {
        $this->requerirPermiso("inventario.ver");
        $this->vista("apps/erp/inventarios/existencias");
    }

    public function inicial() {
        $this->requerirPermiso("inventario.ajustar");
        $this->vista("apps/erp/inventarios/operacion", array("modo" => "ajuste"));
    }

    public function ajuste() {
        $this->requerirPermiso("inventario.ajustar");
        $this->redirigir("/inventario/inicial");
    }

    public function transpaso() {
        $this->requerirPermiso("inventario.traspasar");
        $this->vista("apps/erp/inventarios/operacion", array("modo" => "traspaso"));
    }

    public function mostrar() {
        $this->requerirPermiso("inventario.ver");
        $this->redirigir("/inventario/productos_existencias#kardex");
    }

    public function transpasos() {
        $this->requerirPermiso("inventario.traspasar");
        $this->redirigir("/inventario/productos_existencias#kardex");
    }

    public function conteos() {
        $this->requerirPermiso("inventario.conteo");
        $this->vista("apps/erp/inventarios/conteos");
    }

    public function reservas() {
        $this->requerirPermiso("inventario.ajustar");
        $this->vista("apps/erp/inventarios/reservas");
    }

    public function editar() {
        $this->requerirPermiso("inventario.ajustar");
        $this->redirigir("/inventario/inicial");
    }

    public function editar_transpaso() {
        $this->requerirPermiso("inventario.traspasar");
        $this->redirigir("/inventario/transpaso");
    }

    public function buscar_skus_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->buscarSkus(
            isset($_GET["q"]) ? $_GET["q"] : "",
            isset($_GET["id_almacen"]) ? intval($_GET["id_almacen"]) : 0
        ));
    }

    public function catalogos_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->catalogos());
    }

    public function existencias_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->listarExistencias($_GET));
    }

    public function movimientos_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->listarMovimientos($_GET));
    }

    public function unidades_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->listarEtiquetas($_GET));
    }

    public function trazabilidad_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->consultarTrazabilidad($_GET));
    }

    public function diagnostico_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->diagnosticoOperativo($_GET));
    }

    public function valuacion_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->valuacionInventario($_GET));
    }

    public function conteos_listar_erp() {
        $this->requerirPermiso("inventario.conteo");
        return json_encode($this->modelo("InventarioErp")->listarConteos($_GET));
    }

    public function conteo_crear_erp() {
        $this->requerirPermiso("inventario.conteo");
        $respuesta = $this->modelo("InventarioErp")->crearConteo($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("conteo_crear_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function conteo_consultar_erp() {
        $this->requerirPermiso("inventario.conteo");
        return json_encode($this->modelo("InventarioErp")->consultarConteo($_GET));
    }

    public function conteo_capturar_erp() {
        $this->requerirPermiso("inventario.conteo");
        $respuesta = $this->modelo("InventarioErp")->capturarConteo($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("conteo_capturar_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function conteo_preview_cierre_erp() {
        $this->requerirPermiso("inventario.conteo");
        return json_encode($this->modelo("InventarioErp")->previewCerrarConteo($_GET));
    }

    public function conteo_cerrar_erp() {
        $this->requerirPermiso("inventario.conteo");
        $respuesta = $this->modelo("InventarioErp")->cerrarConteo($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("conteo_cerrar_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function reservas_listar_erp() {
        $this->requerirPermiso("inventario.ver");
        return json_encode($this->modelo("InventarioErp")->listarReservas($_GET));
    }

    public function reserva_crear_erp() {
        $this->requerirPermiso("inventario.ajustar");
        $respuesta = $this->modelo("InventarioErp")->crearReserva($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("reserva_crear_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function reserva_liberar_erp() {
        $this->requerirPermiso("inventario.ajustar");
        $respuesta = $this->modelo("InventarioErp")->liberarReserva($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("reserva_liberar_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function ajustar_erp() {
        $this->requerirPermiso("inventario.ajustar");
        $respuesta = $this->modelo("InventarioErp")->aplicarAjuste($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("ajustar_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function traspasar_erp() {
        $this->requerirPermiso("inventario.traspasar");
        $respuesta = $this->modelo("InventarioErp")->aplicarTraspaso($_POST, $this->usuarioActualId());
        $this->auditarMovimiento("traspasar_erp", $respuesta);
        return json_encode($respuesta);
    }

    public function consultar() { return $this->legadoDeshabilitado(); }
    public function consultar_busqueda() { return $this->legadoDeshabilitado(); }
    public function consultar_transpasos() { return $this->legadoDeshabilitado(); }
    public function registrar() { return $this->legadoDeshabilitado(); }
    public function registrar_transpaso() { return $this->legadoDeshabilitado(); }
    public function actualizar_afectar() { return $this->legadoDeshabilitado(); }
    public function actualizar_afectar_transpaso() { return $this->legadoDeshabilitado(); }
    public function afectar() { return $this->legadoDeshabilitado(); }
    public function afectar_transpaso() { return $this->legadoDeshabilitado(); }
    public function consulta_completa_transpaso() { return $this->legadoDeshabilitado(); }
    public function consulta_completa() { return $this->legadoDeshabilitado(); }
    public function actualizar() { return $this->legadoDeshabilitado(); }
    public function actualizar_transpaso() { return $this->legadoDeshabilitado(); }

    private function legadoDeshabilitado() {
        http_response_code(409);
        return json_encode(array(
            "error" => true,
            "tipo" => "warning",
            "mensaje" => "El inventario temporal fue deshabilitado. Usa recepciones, ajustes o traspasos ERP.",
            "depurar" => array()
        ));
    }

    private function auditarMovimiento($accion, $respuesta) {
        SesionSeguridad::registrarAuditoria("inventario", $accion, array(
            "entidad" => "erp_inventario_movimientos",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
    }

    private function redirigir($ruta) {
        header("Location: " . $ruta);
        exit;
    }
}
