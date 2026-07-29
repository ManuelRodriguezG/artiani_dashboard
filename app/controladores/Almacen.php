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

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: expone la vista independiente para abrir empaques cerrados y habilitar piezas internas.
     * Impacto: Almacen/Apertura de empaques; separa este flujo de Preparacion/Empaque y Resurtido.
     * Contrato: requiere permiso de lectura de Almacen; las escrituras se hacen por endpoints POST separados.
     */
    public function apertura_empaques() {
        $this->requerirPermiso("almacen.ver");
        $this->vista("apps/erp/almacen/apertura_empaques");
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

    public function resurtido_simular_solicitud_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->simular_solicitud_resurtido_readonly($_GET));
    }

    public function resurtido_resumen_tiendas_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->resumen_resurtido_tiendas_readonly($_GET));
    }

    public function resurtido_validar_solicitud_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->validar_solicitud_resurtido_readonly($_GET));
    }

    public function resurtido_payload_solicitud_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->payload_solicitud_resurtido_readonly($_GET));
    }

    public function resurtido_estados_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->estados_resurtido_readonly($_GET));
    }

    public function resurtido_preparacion_envio_contrato_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->preparacion_envio_resurtido_contrato_readonly($_GET));
    }

    public function resurtido_plan_preparacion_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->plan_preparacion_resurtido_readonly($_GET));
    }

    public function resurtido_payload_preparacion_envio_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->payload_preparacion_envio_resurtido_readonly($_GET));
    }

    public function resurtido_recepcion_diferencias_contrato_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->recepcion_diferencias_resurtido_contrato_readonly($_GET));
    }

    public function resurtido_politicas_alertas_contrato_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->politicas_alertas_resurtido_contrato_readonly($_GET));
    }

    public function resurtido_acciones_contrato_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->acciones_resurtido_contrato_readonly($_GET));
    }

    public function resurtido_guardar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->guardar_solicitud_resurtido($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "resurtido_guardar_erp", array(
            "entidad" => "erp_almacen_resurtidos",
            "entidad_id" => isset($respuesta["depurar"]["id_resurtido_almacen"]) ? intval($respuesta["depurar"]["id_resurtido_almacen"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function resurtido_preparar_enviar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->preparar_enviar_resurtido_pendiente($_POST, $this->usuarioActualId()));
    }

    public function resurtido_recibir_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo('Almacenes');
        return json_encode($almacen->recibir_resurtido_pendiente($_POST, $this->usuarioActualId()));
    }

    public function resurtido_autorizar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->autorizar_resurtido_pendiente($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "resurtido_autorizar_erp", array(
            "entidad" => "erp_almacen_resurtidos",
            "entidad_id" => isset($respuesta["depurar"]["id_resurtido_almacen"]) ? intval($respuesta["depurar"]["id_resurtido_almacen"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function resurtido_cancelar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->cancelar_resurtido_pendiente($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "resurtido_cancelar_erp", array(
            "entidad" => "erp_almacen_resurtidos",
            "entidad_id" => isset($respuesta["depurar"]["id_resurtido_almacen"]) ? intval($respuesta["depurar"]["id_resurtido_almacen"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function resurtido_politica_guardar_erp() {
        $this->requerirPermiso("almacen.ubicaciones");
        $almacen = $this->modelo('Almacenes');
        $respuesta = $almacen->guardar_politica_resurtido_pendiente($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "resurtido_politica_guardar_erp", array(
            "entidad" => "erp_inventario_politicas_almacen_sku",
            "entidad_id" => isset($respuesta["depurar"]["id_politica_almacen_sku"]) ? intval($respuesta["depurar"]["id_politica_almacen_sku"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
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

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: consulta SKUs cerrados configurados para apertura de empaques.
     * Impacto: Almacen/Apertura de empaques; endpoint de lectura para preparar borradores sin afectar inventario.
     */
    public function apertura_skus_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_skus_apertura_empaque($_GET));
    }


    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-28
     * Proposito: consulta la configuracion de apertura definida en Catalogo para un SKU cerrado.
     * Impacto: Almacen/Apertura de empaques; acepta `id_apertura_catalogo` y conserva compatibilidad temporal con `id_paquete`.
     */
    public function apertura_receta_erp() {
        $this->requerirPermiso("almacen.ver");
        $id_apertura = isset($_GET["id_apertura_catalogo"]) ? $_GET["id_apertura_catalogo"] : (isset($_GET["id_paquete"]) ? $_GET["id_paquete"] : 0);
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_receta_apertura_empaque($id_apertura));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: consulta existencias fisicas disponibles para apertura en una ubicacion autorizada.
     * Impacto: Almacen/Apertura de empaques; evita abrir SKUs teoricos sin stock.
     */
    public function apertura_existencias_erp() {
        $this->requerirPermiso("almacen.ver");
        $id_sku_origen = isset($_GET["id_sku_origen"]) ? $_GET["id_sku_origen"] : 0;
        $id_almacen = isset($_GET["id_almacen"]) ? $_GET["id_almacen"] : 0;
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_existencias_apertura_empaque($id_sku_origen, $id_almacen));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: lista folios de apertura de empaques.
     * Impacto: Almacen/Apertura de empaques; bandeja de seguimiento y confirmacion.
     */
    public function aperturas_empaque_erp() {
        $this->requerirPermiso("almacen.ver");
        $almacen = $this->modelo("Almacenes");
        return json_encode($almacen->consultar_aperturas_empaque($_GET));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: guarda borradores de apertura de empaques sin afectar inventario.
     * Impacto: Almacen/Apertura de empaques; prepara folios APE para confirmar despues con kardex.
     * Contrato: requiere permiso `almacen.recibir`; el modelo valida almacen habilitado, receta y existencia fisica origen.
     */
    public function apertura_guardar_borrador_erp() {
        $this->requerirPermiso("almacen.recibir");
        $almacen = $this->modelo("Almacenes");
        $respuesta = $almacen->guardar_borrador_apertura_empaque($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "apertura_guardar_borrador_erp", array(
            "entidad" => "erp_almacen_aperturas_empaque",
            "entidad_id" => isset($respuesta["depurar"]["id_apertura_empaque"]) ? intval($respuesta["depurar"]["id_apertura_empaque"]) : 0,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-07-25
     * Proposito: confirma una apertura de empaque y genera movimientos de inventario.
     * Impacto: Almacen/Inventario; consume una unidad cerrada y crea entradas por piezas internas bajo el mismo folio.
     * Contrato: requiere permiso `almacen.recibir`; solo confirma aperturas en borrador.
     */
    public function apertura_confirmar_erp() {
        $this->requerirPermiso("almacen.recibir");
        $id_apertura = isset($_POST["id_apertura_empaque"]) ? $_POST["id_apertura_empaque"] : 0;
        $almacen = $this->modelo("Almacenes");
        $respuesta = $almacen->confirmar_apertura_empaque($id_apertura, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("almacen", "apertura_confirmar_erp", array(
            "entidad" => "erp_almacen_aperturas_empaque",
            "entidad_id" => intval($id_apertura),
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
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
