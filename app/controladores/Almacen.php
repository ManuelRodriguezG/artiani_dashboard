<?php

class Almacen extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    function index() {
        
    }

    public function mostrar_recepciones() {
        $this->requerirPermiso("almacen.ver");
        $this->vista("apps/erp/almacen/mostrar_recepciones");
    }

    public function recibir($id_recepcion_almacen = 0) {
        $this->requerirPermiso("almacen.recibir");
        $this->vista("apps/erp/almacen/recibir", array(
            "id_recepcion_almacen" => $id_recepcion_almacen
        ));
    }

    public function etiquetado() {
        $this->requerirPermiso("almacen.ver");
        $this->vista("apps/erp/almacen/etiquetado");
    }

    public function preparacion_empaque() {
        $this->requerirPermiso("almacen.ver");
        $this->vista("apps/erp/almacen/preparacion_empaque");
    }

    public function resurtido() {
        $this->requerirPermiso("almacen.ver");
        $this->vista("apps/erp/almacen/resurtido");
    }

    public function configuracion() {
        $this->requerirPermiso("almacen.ubicaciones");
        $this->vista("apps/erp/almacen/configuracion");
    }

    public function consultar_almacenes() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->obtener_almacenes($_GET);
        return json_encode($respuesta);
    }

    public function almacenes_configuracion_erp() {
        $this->requerirPermiso("almacen.ubicaciones");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->consultar_almacenes_configuracion($_GET));
    }

    public function resurtido_stock_bajo_preflight_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->preflight_stock_bajo_resurtido($_GET));
    }

    public function resurtido_listar_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->consultar_resurtidos_readonly($_GET));
    }

    public function resurtido_consultar_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->consultar_resurtido_readonly($_GET));
    }

    public function almacen_configuracion_guardar_erp() {
        $this->requerirPermiso("almacen.ubicaciones");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->guardar_almacen_configuracion($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "almacen_configuracion_guardar_erp", array(
            "entidad" => "erp_almacenes",
            "entidad_id" => isset($respuesta["depurar"]["id_almacen"]) ? intval($respuesta["depurar"]["id_almacen"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function ubicaciones_configuracion_erp() {
        $this->requerirPermiso("almacen.ubicaciones");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->consultar_ubicaciones_configuracion($_GET));
    }

    public function ubicacion_configuracion_guardar_erp() {
        $this->requerirPermiso("almacen.ubicaciones");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->guardar_ubicacion_configuracion($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "ubicacion_configuracion_guardar_erp", array(
            "entidad" => "erp_almacen_ubicaciones",
            "entidad_id" => isset($respuesta["depurar"]["id_ubicacion"]) ? intval($respuesta["depurar"]["id_ubicacion"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function obtener_recepciones() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_recepciones_almacen());
    }

    public function preparacion_presentaciones_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_presentaciones_preparables($_GET));
    }

    public function preparaciones_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_preparaciones($_GET));
    }

    public function preparacion_existencias_base_erp() {
        $this->requerirPermiso("almacen.ver");
        $id_sku_base = isset($_GET["id_sku_base"]) ? $_GET["id_sku_base"] : 0;
        $id_almacen = isset($_GET["id_almacen"]) ? $_GET["id_almacen"] : 0;
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_existencias_base_preparacion($id_sku_base, $id_almacen));
    }

    public function preparacion_guardar_borrador_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo("Almacenes");
        $respuesta = $almacen->guardar_borrador_preparacion($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "preparacion_guardar_borrador_erp", array(
            "entidad" => "erp_almacen_preparaciones",
            "entidad_id" => isset($respuesta["depurar"]["id_preparacion_almacen"]) ? intval($respuesta["depurar"]["id_preparacion_almacen"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function preparacion_confirmar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $id_preparacion = isset($_POST["id_preparacion_almacen"]) ? $_POST["id_preparacion_almacen"] : 0;
        $almacen = $this->modelo("Almacenes");
        $respuesta = $almacen->confirmar_preparacion($id_preparacion, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "preparacion_confirmar_erp", array(
            "entidad" => "erp_almacen_preparaciones",
            "entidad_id" => intval($id_preparacion),
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function consultar_recepcion() {
        $this->requerirPermiso("almacen.ver");
        $id_recepcion_almacen = isset($_POST["id_recepcion_almacen"]) ? $_POST["id_recepcion_almacen"] : 0;
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_recepcion_almacen_completa($id_recepcion_almacen));
    }

    public function guardar_recepcion() {
        $this->requerirPermiso("almacen.recibir");
        $id_recepcion_almacen = isset($_POST["id_recepcion_almacen"]) ? $_POST["id_recepcion_almacen"] : 0;
        $partidas = array();
        if (isset($_POST["partidas"])) {
            $partidas = is_array($_POST["partidas"]) ? $_POST["partidas"] : json_decode($_POST["partidas"], true);
        }

        $id_usuario = isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0;
        $almacen = $this->modelo("Almacenes");
        $respuesta = $almacen->guardar_recepcion_almacen($id_recepcion_almacen, $partidas, $id_usuario);
        SesionSeguridad::registrarAuditoria("almacen", "guardar_recepcion", array(
            "entidad" => "erp_almacen_recepciones",
            "entidad_id" => intval($id_recepcion_almacen),
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function etiquetas_erp() {
        $this->requerirPermiso("almacen.ver");
        return json_encode($this->modelo("InventarioErp")->listarEtiquetas($_GET));
    }

    public function etiqueta_marcar_impresa_erp() {
        $this->requerirPermiso("almacen.recibir");
        $respuesta = $this->modelo("InventarioErp")->marcarEtiquetaImpresa($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "etiqueta_marcar_impresa_erp", array(
            "entidad" => "erp_inventario_unidades",
            "entidad_id" => isset($_POST["id_inventario_unidad"]) ? intval($_POST["id_inventario_unidad"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function etiquetas_marcar_impresas_erp() {
        $this->requerirPermiso("almacen.recibir");
        $respuesta = $this->modelo("InventarioErp")->marcarEtiquetasImpresas($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "etiquetas_marcar_impresas_erp", array(
            "entidad" => "erp_inventario_unidades",
            "entidad_id" => 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function etiqueta_marcar_pegada_erp() {
        $this->requerirPermiso("almacen.recibir");
        $respuesta = $this->modelo("InventarioErp")->marcarEtiquetaPegada($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "etiqueta_marcar_pegada_erp", array(
            "entidad" => "erp_inventario_unidades",
            "entidad_id" => isset($_POST["id_inventario_unidad"]) ? intval($_POST["id_inventario_unidad"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function etiquetas_marcar_pegadas_erp() {
        $this->requerirPermiso("almacen.recibir");
        $respuesta = $this->modelo("InventarioErp")->marcarEtiquetasPegadas($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "etiquetas_marcar_pegadas_erp", array(
            "entidad" => "erp_inventario_unidades",
            "entidad_id" => 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function esquema_auditar_almacen_inventario() {
        $this->requerirPermiso("sistema.soporte");
        $esquema = $this->modelo("AlmacenEsquema");
        return json_encode($esquema->auditarAlmacenInventario());
    }

    public function esquema_actualizar_almacen_inventario() {
        $this->requerirPermiso("sistema.soporte");
        $ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] == 1;
        $esquema = $this->modelo("AlmacenEsquema");
        return json_encode($esquema->planActualizarAlmacenInventario($ejecutar));
    }
}
