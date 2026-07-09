<?php

class Compra extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    public function index() {
        
    }

    public function ticket() {
        $this->vista("apps/ecommerce/sales/ticket");
    }

    public function crear() {
        $this->vista('apps/erp/compras/crear');
    }

    public function listar() {
        $this->vista("apps/erp/compras/listar");
    }

    public function solicitud_compra_nueva() {
        $this->requerirPermiso("compras.crear");
        $this->vista("apps/erp/compras/solicitudes/formulario", array(
            "id_solicitud" => 0,
            "modo" => "editar",
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar")
        ));
    }

    public function mostrar_solicitud($id = 0) {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/compras/solicitudes/formulario", array(
            "id_solicitud" => intval($id),
            "modo" => "ver",
            "puede_editar" => $this->usuarioPuede("compras.editar"),
            "puede_crear" => $this->usuarioPuede("compras.crear"),
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar")
        ));
    }

    public function solicitud_imprimir_erp($id = 0) {
        $this->requerirPermiso("compras.ver");
        $idSolicitud = intval($id);
        $respuesta = $this->modelo("SolicitudesCompraErp")->consultar($idSolicitud);
        if ($respuesta["error"]) {
            $this->vista("apps/erp/compras/solicitudes/imprimir", array(
                "error_imprimir" => $respuesta["mensaje"],
                "id_solicitud" => $idSolicitud
            ));
            return;
        }
        $this->vista("apps/erp/compras/solicitudes/imprimir", array(
            "id_solicitud" => $idSolicitud,
            "solicitud" => $respuesta["depurar"]["solicitud"],
            "detalle" => $respuesta["depurar"]["detalle"],
            "orden_relacionada" => isset($respuesta["depurar"]["orden_relacionada"])
                ? $respuesta["depurar"]["orden_relacionada"] : null
        ));
    }

    public function editar_solicitud($id = 0) {
        $this->requerirPermiso("compras.editar");
        $this->vista("apps/erp/compras/solicitudes/formulario", array(
            "id_solicitud" => intval($id),
            "modo" => "editar",
            "puede_editar" => $this->usuarioPuede("compras.editar"),
            "puede_crear" => $this->usuarioPuede("compras.crear"),
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar")
        ));
    }

    public function compra_productos() {
        $this->vista("apps/erp/compras/compra_productos");
    }

    public function mostrar_solicitudes() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/compras/solicitudes/listado", array(
            "puede_editar" => $this->usuarioPuede("compras.editar"),
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar"),
            "puede_crear" => $this->usuarioPuede("compras.crear")
        ));
    }

    public function solicitudes_catalogos_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("SolicitudesCompraErp")->catalogos());
    }

    public function solicitudes_buscar_skus_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("SolicitudesCompraErp")->buscarSkus(
            isset($_GET["id_proveedor"]) ? $_GET["id_proveedor"] : 0,
            isset($_GET["q"]) ? $_GET["q"] : ""
        ));
    }

    public function solicitudes_listar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("SolicitudesCompraErp")->listar($_GET));
    }

    public function solicitud_consultar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("SolicitudesCompraErp")->consultar(
            isset($_GET["id_solicitud"]) ? $_GET["id_solicitud"] : 0
        ));
    }

    public function solicitud_guardar_erp() {
        $idSolicitud = isset($_POST["id_solicitud"]) ? intval($_POST["id_solicitud"]) : 0;
        $this->requerirPermiso($idSolicitud > 0 ? "compras.editar" : "compras.crear");
        $respuesta = $this->modelo("SolicitudesCompraErp")->guardar(
            $_POST,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarSolicitudErp("guardar", $respuesta);
        return json_encode($respuesta);
    }

    public function solicitud_estatus_erp() {
        $estatus = isset($_POST["estatus"]) ? trim($_POST["estatus"]) : "";
        if (in_array($estatus, array("rechazada", "cancelada"), true)) {
            $this->requerirPermiso($estatus === "cancelada" ? "compras.cancelar" : "compras.aprobar");
        } else {
            $this->requerirPermiso("compras.aprobar");
        }
        $respuesta = $this->modelo("SolicitudesCompraErp")->cambiarEstatus(
            isset($_POST["id_solicitud"]) ? $_POST["id_solicitud"] : 0,
            $estatus,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0,
            isset($_POST["motivo"]) ? $_POST["motivo"] : ""
        );
        $this->auditarSolicitudErp("cambiar_estatus", $respuesta);
        return json_encode($respuesta);
    }

    private function auditarSolicitudErp($accion, $respuesta) {
        SesionSeguridad::registrarAuditoria("compras", "solicitud_" . $accion, array(
            "entidad" => "erp_compras_solicitudes",
            "entidad_id" => isset($respuesta["depurar"]["id_solicitud"]) ? $respuesta["depurar"]["id_solicitud"] : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
    }

    public function mostrar_compra_ordenes() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/compras/ordenes/listado", array(
            "puede_editar" => $this->usuarioPuede("compras.editar"),
            "puede_seguimiento" => true
        ));
    }

    public function editar_orden_compra($id = 0) {
        $this->requerirPermiso("compras.editar");
        $this->vista("apps/erp/compras/ordenes/formulario", array(
            "id_orden_compra" => intval($id),
            "modo" => "editar",
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar"),
            "puede_ver_finanzas" => $this->usuarioPuede("finanzas.ver"),
            "puede_operar_finanzas" => $this->usuarioPuede("finanzas.operar"),
            "puede_gestionar_adjuntos" => $this->usuarioPuede("compras.adjuntos")
        ));
    }

    public function ver_orden_compra($id = 0) {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/compras/ordenes/formulario", array(
            "id_orden_compra" => intval($id),
            "modo" => "ver",
            "puede_aprobar" => false,
            "puede_cancelar" => false,
            "puede_ver_finanzas" => $this->usuarioPuede("finanzas.ver"),
            "puede_operar_finanzas" => false,
            "puede_gestionar_adjuntos" => false
        ));
    }

    public function seguimiento_orden_compra($id = 0) {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/compras/ordenes/formulario", array(
            "id_orden_compra" => intval($id),
            "modo" => "seguimiento",
            "puede_aprobar" => false,
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar"),
            "puede_ver_finanzas" => $this->usuarioPuede("finanzas.ver"),
            "puede_operar_finanzas" => $this->usuarioPuede("finanzas.operar"),
            "puede_gestionar_adjuntos" => $this->usuarioPuede("compras.adjuntos")
        ));
    }

    public function crear_orden_compra() {
        $this->requerirPermiso("compras.crear");
        $this->vista("apps/erp/compras/ordenes/formulario", array(
            "id_orden_compra" => 0,
            "modo" => "editar",
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar"),
            "puede_ver_finanzas" => $this->usuarioPuede("finanzas.ver"),
            "puede_operar_finanzas" => $this->usuarioPuede("finanzas.operar"),
            "puede_gestionar_adjuntos" => $this->usuarioPuede("compras.adjuntos")
        ));
    }

    public function nueva_orden_compra() {
        $this->requerirPermiso("compras.crear");
        $this->vista("apps/erp/compras/ordenes/formulario", array(
            "id_orden_compra" => 0,
            "modo" => "editar",
            "puede_aprobar" => $this->usuarioPuede("compras.aprobar"),
            "puede_cancelar" => $this->usuarioPuede("compras.cancelar"),
            "puede_ver_finanzas" => $this->usuarioPuede("finanzas.ver"),
            "puede_operar_finanzas" => $this->usuarioPuede("finanzas.operar"),
            "puede_gestionar_adjuntos" => $this->usuarioPuede("compras.adjuntos")
        ));
    }

    public function ordenes_catalogos_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("OrdenesCompraErp")->catalogos());
    }

    public function ordenes_listar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("OrdenesCompraErp")->listar($_GET));
    }

    public function orden_buscar_skus_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("OrdenesCompraErp")->buscarSkus(
            isset($_GET["id_proveedor"]) ? $_GET["id_proveedor"] : 0,
            isset($_GET["q"]) ? $_GET["q"] : ""
        ));
    }

    public function orden_generar_desde_solicitud_erp() {
        $this->requerirPermiso("compras.crear");
        $respuesta = $this->modelo("OrdenesCompraErp")->generarDesdeSolicitud(
            isset($_POST["id_solicitud"]) ? $_POST["id_solicitud"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("generar_desde_solicitud", $respuesta);
        return json_encode($respuesta);
    }

    /**
     * Modulo: ERP Compras
     * Funcion: orden_diferencias_solicitud_erp
     * Documentacion IA: Codex GPT-5
     * Fecha: 2026-06-15
     * Descripcion: Compara una orden con su solicitud de origen y devuelve faltantes,
     * adicionales y diferencias de costo/cantidad para trazabilidad de compra.
     * Permisos: compras.ver
     * Tablas afectadas: erp_compras_ordenes, erp_compras_solicitudes_detalle, erp_compras_ordenes_detalle
     * Reglas: Solo aplica cuando la orden ya tiene id_solicitud.
     */
    public function orden_diferencias_solicitud_erp() {
        $this->requerirPermiso("compras.ver");
        $respuesta = $this->modelo("OrdenesCompraErp")->compararConSolicitud(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0,
            isset($_GET["id_solicitud"]) ? $_GET["id_solicitud"] : 0
        );
        $this->auditarOrdenErp("comparar_con_solicitud", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_consultar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("OrdenesCompraErp")->consultar(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0
        ));
    }

    public function orden_guardar_erp() {
        $idOrden = isset($_POST["id_orden_compra"]) ? intval($_POST["id_orden_compra"]) : 0;
        $this->requerirPermiso($idOrden > 0 ? "compras.editar" : "compras.crear");
        if (isset($_POST["estatus"]) && $_POST["estatus"] === "enviada") {
            $this->requerirPermiso("compras.aprobar");
        }
        $respuesta = $this->modelo("OrdenesCompraErp")->guardar(
            $_POST,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        if (!$respuesta["error"] && !empty($respuesta["depurar"]["id_orden_compra"])) {
            $eventosDescuentoMasivo = $this->normalizarEventosDescuentoMasivo(
                isset($_POST["descuento_masivo_eventos"]) ? $_POST["descuento_masivo_eventos"] : ""
            );
            if (!empty($eventosDescuentoMasivo)) {
                $this->auditarOrdenErp("descuento_masivo", array(
                    "error" => false,
                    "mensaje" => "Descuento masivo registrado en guardado de orden",
                    "depurar" => array(
                        "id_orden_compra" => $respuesta["depurar"]["id_orden_compra"],
                        "eventos" => $eventosDescuentoMasivo
                    )
                ));
            }
            $respuesta["depurar"]["finanzas"] = $this->modelo("PagosCompraErp")
                ->recalcularSaldo($respuesta["depurar"]["id_orden_compra"]);
            if ($respuesta["depurar"]["finanzas"]["error"]) {
                $respuesta["tipo"] = "warning";
                $respuesta["mensaje"] .= ". El saldo financiero requiere revision";
            }
            $respuesta["depurar"]["pendientes_xml"] = $this->modelo("ComprasXmlErp")
                ->sincronizarPendientes($respuesta["depurar"]["id_orden_compra"]);
            if ($respuesta["depurar"]["pendientes_xml"]["error"]) {
                $respuesta["tipo"] = "warning";
                $respuesta["mensaje"] .= ". Los pendientes XML requieren revision";
            }
        }
        if (!$respuesta["error"] && isset($respuesta["depurar"]["estatus"]) &&
            $respuesta["depurar"]["estatus"] === "enviada") {
            $advertenciasOperativas = isset($respuesta["depurar"]["advertencias_operativas"]) &&
                is_array($respuesta["depurar"]["advertencias_operativas"])
                ? $respuesta["depurar"]["advertencias_operativas"] : array();
            if (!empty($advertenciasOperativas)) {
                $respuesta["tipo"] = "warning";
                $respuesta["mensaje"] .= ". La orden tiene advertencias operativas";
                $this->auditarOrdenErp("advertencias_operativas", array(
                    "error" => false,
                    "mensaje" => "Orden enviada con advertencias operativas",
                    "depurar" => array(
                        "id_orden_compra" => $respuesta["depurar"]["id_orden_compra"],
                        "advertencias_operativas" => $advertenciasOperativas
                    )
                ));
            }
            $respuesta["depurar"]["recepcion_almacen"] = $this->preparar_recepcion_almacen_si_enviada(
                $respuesta["depurar"]["id_orden_compra"]
            );
            if ($respuesta["depurar"]["recepcion_almacen"]["error"]) {
                $respuesta["tipo"] = "warning";
                $respuesta["mensaje"] .= ". La recepcion requiere revision";
            }
            $respuesta["depurar"]["costos_consolidados"] = $this->modelo("OrdenesCompraErp")->cerrarCostos(
                $respuesta["depurar"]["id_orden_compra"],
                isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
            );
            if ($respuesta["depurar"]["costos_consolidados"]["error"]) {
                $respuesta["tipo"] = "warning";
                $respuesta["mensaje"] .= ". Los costos requieren revision";
            }
            $this->auditarOrdenErp("consolidar_costos", $respuesta["depurar"]["costos_consolidados"]);
        }
        $this->auditarOrdenErp("guardar", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_finanzas_consultar_erp() {
        $this->requerirPermiso("finanzas.ver");
        return json_encode($this->modelo("PagosCompraErp")->consultar(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0
        ));
    }

    public function orden_pago_registrar_erp() {
        $this->requerirPermiso("finanzas.operar");
        $respuesta = $this->modelo("PagosCompraErp")->registrarPago(
            $_POST,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("registrar_pago", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_pago_cancelar_erp() {
        $this->requerirPermiso("finanzas.operar");
        $respuesta = $this->modelo("PagosCompraErp")->cancelarPago(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["id_pago_orden"]) ? $_POST["id_pago_orden"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("cancelar_pago", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_nota_credito_registrar_erp() {
        $this->requerirPermiso("finanzas.operar");
        $respuesta = $this->modelo("PagosCompraErp")->registrarNotaCredito(
            $_POST,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("registrar_nota_credito", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_nota_credito_cancelar_erp() {
        $this->requerirPermiso("finanzas.operar");
        $respuesta = $this->modelo("PagosCompraErp")->cancelarNotaCredito(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["id_nota_credito_orden"]) ? $_POST["id_nota_credito_orden"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("cancelar_nota_credito", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_adjuntos_listar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("AdjuntosCompraErp")->listarAdjuntos(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0
        ));
    }

    public function orden_adjunto_subir_erp() {
        $this->requerirPermiso("compras.adjuntos");
        $respuesta = $this->modelo("AdjuntosCompraErp")->guardar(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_FILES["archivo"]) ? $_FILES["archivo"] : array(),
            $_POST,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("subir_adjunto", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_adjunto_cancelar_erp() {
        $this->requerirPermiso("compras.adjuntos");
        $respuesta = $this->modelo("AdjuntosCompraErp")->cancelar(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["id_adjunto_orden"]) ? $_POST["id_adjunto_orden"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("cancelar_adjunto", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_adjunto_archivo_erp() {
        $this->requerirPermiso("compras.ver");
        $respuesta = $this->modelo("AdjuntosCompraErp")->obtenerArchivo(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0,
            isset($_GET["id_adjunto_orden"]) ? $_GET["id_adjunto_orden"] : 0
        );
        if ($respuesta["error"]) {
            http_response_code(404);
            header("Content-Type: text/plain; charset=UTF-8");
            echo $respuesta["mensaje"];
            exit;
        }
        $archivo = $respuesta["depurar"];
        $descargar = isset($_GET["descargar"]) && $_GET["descargar"] == 1;
        $nombre = str_replace(array("\r", "\n", '"'), "", basename($archivo["archivo_nombre"]));
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header("X-Content-Type-Options: nosniff");
        header("Content-Type: " . $archivo["archivo_tipo"]);
        header("Content-Length: " . filesize($archivo["ruta_absoluta"]));
        header("Content-Disposition: " . ($descargar ? "attachment" : "inline") .
            "; filename=\"" . $nombre . "\"; filename*=UTF-8''" . rawurlencode($nombre));
        readfile($archivo["ruta_absoluta"]);
        exit;
    }

    public function orden_cancelar_erp() {
        $this->requerirPermiso("compras.cancelar");
        $respuesta = $this->modelo("OrdenesCompraErp")->cancelar(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("cancelar", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_xml_importar_erp() {
        $this->requerirPermiso("compras.editar");
        $respuesta = $this->modelo("ComprasXmlErp")->importar(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_FILES["archivo_xml"]) ? $_FILES["archivo_xml"] : array(),
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("importar_xml", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_xml_parse_erp() {
        // [Codex: v2026.06.08] Parseo de XML para carga masiva en órdenes nuevas (sin persistir documento).
        $this->requerirPermiso("compras.crear");
        $respuesta = $this->modelo("ComprasXmlErp")->parsear(
            isset($_FILES["archivo_xml"]) ? $_FILES["archivo_xml"] : array(),
            isset($_POST["id_proveedor"]) ? $_POST["id_proveedor"] : 0
        );
        return json_encode($respuesta);
    }

    public function orden_xml_listar_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("ComprasXmlErp")->listarDocumentos(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0
        ));
    }

    public function orden_xml_conciliacion_erp() {
        $this->requerirPermiso("compras.ver");
        return json_encode($this->modelo("ComprasXmlErp")->consultarConciliacion(
            isset($_GET["id_orden_compra"]) ? $_GET["id_orden_compra"] : 0
        ));
    }

    public function orden_xml_resolver_concepto_erp() {
        $this->requerirPermiso("compras.editar");
        $respuesta = $this->modelo("ComprasXmlErp")->resolverConcepto(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["id_documento_concepto"]) ? $_POST["id_documento_concepto"] : 0,
            isset($_POST["id_orden_detalle"]) ? $_POST["id_orden_detalle"] : 0,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("resolver_concepto_xml", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_xml_mover_conceptos_erp() {
        $this->requerirPermiso("compras.editar");
        $asignaciones = isset($_POST["asignaciones_json"]) ? $_POST["asignaciones_json"] : null;
        if (is_array($_POST["asignaciones"] ?? null)) {
            $asignaciones = $_POST["asignaciones"];
        } elseif (is_string($asignaciones)) {
            $tmp = json_decode($asignaciones, true);
            if (is_array($tmp)) {
                $asignaciones = $tmp;
            }
        }
        if (!is_array($asignaciones)) {
            $asignaciones = array();
        }
        $respuesta = $this->modelo("ComprasXmlErp")->moverConceptos(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["conceptos"]) ? $_POST["conceptos"] : array(),
            $asignaciones,
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("mover_conceptos_xml", $respuesta);
        return json_encode($respuesta);
    }

    public function orden_xml_descartar_conceptos_erp() {
        $this->requerirPermiso("compras.editar");
        $respuesta = $this->modelo("ComprasXmlErp")->descartarConceptos(
            isset($_POST["id_orden_compra"]) ? $_POST["id_orden_compra"] : 0,
            isset($_POST["conceptos"]) ? $_POST["conceptos"] : array(),
            isset($_SESSION["id_usuario"]) ? $_SESSION["id_usuario"] : 0
        );
        $this->auditarOrdenErp("descartar_conceptos_xml", $respuesta);
        return json_encode($respuesta);
    }

    private function auditarOrdenErp($accion, $respuesta) {
        SesionSeguridad::registrarAuditoria("compras", "orden_" . $accion, array(
            "entidad" => "erp_compras_ordenes",
            "entidad_id" => isset($respuesta["depurar"]["id_orden_compra"]) ? $respuesta["depurar"]["id_orden_compra"] : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
    }

    private function normalizarEventosDescuentoMasivo($json) {
        if (!is_string($json) || trim($json) === "") {
            return array();
        }
        $eventos = json_decode($json, true);
        if (!is_array($eventos)) {
            return array();
        }
        $normalizados = array();
        foreach ($eventos as $evento) {
            if (!is_array($evento)) {
                continue;
            }
            $partidas = isset($evento["partidas"]) && is_array($evento["partidas"])
                ? array_slice($evento["partidas"], 0, 250) : array();
            $normalizados[] = array(
                "fecha_cliente" => isset($evento["fecha_cliente"]) ? substr((string) $evento["fecha_cliente"], 0, 40) : "",
                "porcentaje" => round(floatval(isset($evento["porcentaje"]) ? $evento["porcentaje"] : 0), 6),
                "motivo" => isset($evento["motivo"]) ? mb_substr(trim((string) $evento["motivo"]), 0, 255) : "",
                "partidas" => $partidas
            );
            if (count($normalizados) >= 25) {
                break;
            }
        }
        return $normalizados;
    }

    private function usuarioPuede($permiso) {
        return $this->modelo("SeguridadPermisos")->usuarioTienePermiso(
            $this->usuarioActualId(),
            $permiso
        );
    }

    public function esquema_actualizar_orden_compra() {
        $this->requerirPermiso("sistema.soporte");
        $ejecutar = isset($_POST['ejecutar']) && $_POST['ejecutar'] == 1;
        $esquema = $this->modelo("ComprasEsquema");
        return json_encode($esquema->planActualizarOrdenCompra($ejecutar));
    }

    public function enriquecer_productos_compra() {
        $this->requerirPermiso("compras.ver");
        $skus = isset($_POST['skus']) ? $_POST['skus'] : array();
        $id_proveedor = isset($_POST['id_proveedor']) ? $_POST['id_proveedor'] : 0;
        if (!is_array($skus)) {
            $skus = array($skus);
        }
        $compra = $this->modelo("Compras");
        return json_encode($compra->enriquecer_productos_compra($skus, $id_proveedor));
    }

    public function actualizar_orden_compra() {
        return $this->ordenLegadaDeshabilitada();
        $this->requerirPermiso("compras.crear");
        $respuesta = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al actualizar orden de compra",
            "depurar" => array()
        );

        $validacion_esquema = $this->validar_esquema_actualizar_orden_compra();
        if ($validacion_esquema['error'] == true) {
            return json_encode($validacion_esquema);
        }

        $id_orden_compra = isset($_POST['id_orden_compra']) ? $_POST['id_orden_compra'] : (isset($_REQUEST['id_orden_compra']) ? $_REQUEST['id_orden_compra'] : 0);
        $productos = isset($_POST['productos']) ? $_POST['productos'] : array();
        $pagos = isset($_POST['pagos']) ? $_POST['pagos'] : array();
        $adjuntos = isset($_POST['adjuntos']) ? $_POST['adjuntos'] : array();
        $adjuntos_conservar = isset($_POST['adjuntos_conservar']) ? $_POST['adjuntos_conservar'] : array();
        $productos_atencion = isset($_POST['productos_atencion']) ? $_POST['productos_atencion'] : array();
        $nota_credito = isset($_POST['nota_credito']) ? $_POST['nota_credito'] : array();

        if (!$id_orden_compra || empty($productos)) {
            $respuesta['tipo'] = "warning";
            $respuesta['mensaje'] = "Faltan datos para actualizar la orden de compra";
            return json_encode($respuesta);
        }

        $compra = $this->modelo("Compras");
        $compra->setId_orden_compra($id_orden_compra);
        $id_proveedor_orden = isset($_POST['id_proveedor']) ? $_POST['id_proveedor'] : 0;
        if (!$id_proveedor_orden) {
            $orden_actual = $compra->consultar_compra_orden();
            if ($orden_actual['error'] == false && !empty($orden_actual['depurar']['id_proveedor'])) {
                $id_proveedor_orden = $orden_actual['depurar']['id_proveedor'];
                $_POST['id_proveedor'] = $id_proveedor_orden;
            }
        }
        $compra->setId_proveedor($id_proveedor_orden);

        $orden = $_POST;
        $orden['subtotal'] = isset($_POST['subtotal']) ? $_POST['subtotal'] : 0;
        $orden['impuestos'] = isset($_POST['impuestos']) ? $_POST['impuestos'] : 0;

        $actualizar_orden = $compra->actualizar_orden_compra_desde_edicion($orden);
        if ($actualizar_orden['error'] == true) {
            return json_encode($actualizar_orden);
        }

        $compra->setId_orden_compra($id_orden_compra);
        $adjuntos_actuales = $compra->consultar_adjuntos_orden_compra();
        if ($adjuntos_actuales['error'] == false) {
            $this->eliminar_archivos_adjuntos_no_conservados($adjuntos_actuales['depurar'], $adjuntos_conservar);
        }
        $adjuntos_subidos = $this->procesar_archivos_adjuntos_orden_compra($id_orden_compra);
        $adjuntos = array_merge($adjuntos_conservar, $adjuntos_subidos);

        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_registros_orden_compra_detalle();
        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_pagos_orden_compra();
        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_notas_credito_orden_compra();
        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_adjuntos_orden_compra();
        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_productos_pendientes_orden_compra();
        $compra->setId_orden_compra($id_orden_compra);
        $compra->eliminar_productos_atencion_orden_compra();

        $producto_catalogo = $this->modelo("Productos");
        $datos_fiscales_guardados = 0;
        $datos_fiscales_omitidos = array();
        foreach ($productos as $producto) {
            $guardar_producto = $compra->registrar_orden_compra_detalle_desde_edicion($id_orden_compra, $producto);
            if ($guardar_producto['error'] == true) {
                return json_encode($guardar_producto);
            }
            $id_producto_catalogo = !empty($producto['id_producto_ecom']) ? $producto['id_producto_ecom'] : (!empty($producto['id_producto']) ? $producto['id_producto'] : 0);
            if (!empty($id_producto_catalogo) && !empty($producto['datos_fiscales'])) {
                $producto_catalogo->setId_producto($id_producto_catalogo);
                $guardar_fiscal = $producto_catalogo->guardar_producto_fiscal_compra($producto['datos_fiscales']);
                if ($guardar_fiscal['error'] == false) {
                    $datos_fiscales_guardados++;
                } else {
                    $datos_fiscales_omitidos[] = array(
                        "id_producto" => $id_producto_catalogo,
                        "respuesta" => $guardar_fiscal
                    );
                }
            }
            if (isset($producto['requiere_revision']) && $producto['requiere_revision'] == 1) {
                $compra->registrar_producto_pendiente_compra($id_orden_compra, $producto);
            }
            $compra->registrar_producto_lista_proveedor_revision_compra($id_orden_compra, $producto);
        }

        foreach ($pagos as $pago) {
            $guardar_pago = $compra->registrar_pago_orden_compra($id_orden_compra, $pago);
            if ($guardar_pago['error'] == true) {
                return json_encode($guardar_pago);
            }
        }

        foreach ($productos_atencion as $producto_atencion) {
            $guardar_atencion = $compra->registrar_producto_atencion_orden_compra($id_orden_compra, $producto_atencion);
            if ($guardar_atencion['error'] == true) {
                return json_encode($guardar_atencion);
            }
        }

        if (!empty($nota_credito) && isset($nota_credito['monto']) && (float) $nota_credito['monto'] > 0) {
            $guardar_nota = $compra->registrar_nota_credito_orden_compra($id_orden_compra, $nota_credito);
            if ($guardar_nota['error'] == true) {
                return json_encode($guardar_nota);
            }
        }

        foreach ($adjuntos as $adjunto) {
            $guardar_adjunto = $compra->registrar_adjunto_orden_compra($id_orden_compra, $adjunto);
            if ($guardar_adjunto['error'] == true) {
                return json_encode($guardar_adjunto);
            }
        }

        $recepcion_almacen = $this->preparar_recepcion_almacen_si_enviada($id_orden_compra);
        $respuesta_recepcion = $this->respuesta_guardado_con_recepcion_almacen(
            "Orden de compra actualizada con exito",
            $id_orden_compra,
            isset($_POST['estatus_orden']) ? $_POST['estatus_orden'] : "",
            $recepcion_almacen
        );

        $respuesta = array(
            "error" => $respuesta_recepcion['error'],
            "tipo" => $respuesta_recepcion['tipo'],
            "mensaje" => $respuesta_recepcion['mensaje'],
            "depurar" => array(
                "id_orden_compra" => $id_orden_compra,
                "datos_fiscales_guardados" => $datos_fiscales_guardados,
                "datos_fiscales_omitidos" => $datos_fiscales_omitidos,
                "adjuntos_conservados" => count($adjuntos_conservar),
                "adjuntos_subidos" => count($adjuntos_subidos),
                "adjuntos_registrados" => count($adjuntos),
                "archivos_recibidos" => isset($_FILES['archivos_adjuntos']['name']) ? count($_FILES['archivos_adjuntos']['name']) : 0,
                "recepcion_almacen" => $recepcion_almacen,
                "adjuntos_subidos_debug" => $adjuntos_subidos
            )
        );
        return json_encode($respuesta);
    }

    public function registrar_orden_compra_completa() {
        return $this->ordenLegadaDeshabilitada();
        $this->requerirPermiso("compras.crear");
        $respuesta = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al registrar orden de compra",
            "depurar" => array()
        );

        $validacion_esquema = $this->validar_esquema_actualizar_orden_compra();
        if ($validacion_esquema['error'] == true) {
            return json_encode($validacion_esquema);
        }

        $productos = isset($_POST['productos']) ? $_POST['productos'] : array();
        if (empty($_POST['id_proveedor']) || empty($productos)) {
            $respuesta['tipo'] = "warning";
            $respuesta['mensaje'] = "Selecciona proveedor y agrega productos para registrar la orden de compra";
            return json_encode($respuesta);
        }

        $compra = $this->modelo("Compras");
        $compra->setId_proveedor($_POST['id_proveedor']);
        $registrar_orden = $compra->registrar_orden_compra_completa($_POST);
        if ($registrar_orden['error'] == true) {
            return json_encode($registrar_orden);
        }

        $id_orden_compra = $registrar_orden['depurar'];
        $respuesta_guardado = $this->guardar_contenido_orden_compra_crear($id_orden_compra, $compra);
        return json_encode($respuesta_guardado);
    }

    public function preparar_recepcion_orden_compra() {
        $this->requerirPermiso("compras.aprobar");
        $id_orden_compra = isset($_POST['id_orden_compra']) ? $_POST['id_orden_compra'] : (isset($_REQUEST['id_orden_compra']) ? $_REQUEST['id_orden_compra'] : 0);
        if (!$id_orden_compra) {
            return json_encode(array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => "Falta orden de compra para preparar recepcion de almacen",
                "depurar" => array()
            ));
        }

        return json_encode($this->preparar_recepcion_almacen_si_enviada($id_orden_compra));
    }

    private function procesar_archivos_adjuntos_orden_compra($id_orden_compra) {
        $adjuntos = array();
        if (empty($_FILES['archivos_adjuntos'])) {
            return $adjuntos;
        }

        $directorio_relativo = "uploads/erp/compras/ordenes/" . intval($id_orden_compra) . "/";
        $directorio_absoluto = rtrim(str_replace("\\", "/", dirname(__DIR__, 2) . "/public/" . $directorio_relativo), "/") . "/";
        if (!is_dir($directorio_absoluto)) {
            mkdir($directorio_absoluto, 0775, true);
        }

        $firmas_archivos = array();
        foreach ($_FILES['archivos_adjuntos']['name'] as $indice => $nombre_original) {
            if ($_FILES['archivos_adjuntos']['error'][$indice] !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp_name = $_FILES['archivos_adjuntos']['tmp_name'][$indice];
            $tipo_archivo = $_FILES['archivos_adjuntos']['type'][$indice];
            $tamano_archivo = $_FILES['archivos_adjuntos']['size'][$indice];
            $firma_archivo = $nombre_original . "|" . $tamano_archivo;
            if (isset($firmas_archivos[$firma_archivo])) {
                continue;
            }
            $firmas_archivos[$firma_archivo] = true;
            $nombre_limpio = $this->limpiar_nombre_archivo($nombre_original);
            $nombre_final = date("YmdHis") . "_" . $indice . "_" . $nombre_limpio;
            $ruta_destino = $directorio_absoluto . $nombre_final;

            if (!move_uploaded_file($tmp_name, $ruta_destino)) {
                continue;
            }

            $meta = isset($_POST['archivos_adjuntos_meta'][$indice]) ? $_POST['archivos_adjuntos_meta'][$indice] : array();
            if (empty($meta) && isset($_POST['adjuntos'][$indice])) {
                $meta = $_POST['adjuntos'][$indice];
            }
            $observaciones_adjunto = "";
            if (isset($meta['observaciones'])) {
                $observaciones_adjunto = $meta['observaciones'];
            } elseif (isset($meta['observacion'])) {
                $observaciones_adjunto = $meta['observacion'];
            } elseif (isset($meta['comentario'])) {
                $observaciones_adjunto = $meta['comentario'];
            } elseif (isset($meta['nota'])) {
                $observaciones_adjunto = $meta['nota'];
            }
            $adjuntos[] = array(
                "tipo_documento" => isset($meta['tipo_documento']) ? $meta['tipo_documento'] : "adjunto",
                "referencia" => isset($meta['referencia']) ? $meta['referencia'] : "",
                "archivo_nombre" => $nombre_original,
                "archivo_ruta" => $directorio_relativo . $nombre_final,
                "archivo_tipo" => $tipo_archivo,
                "archivo_tamano" => $tamano_archivo,
                "observaciones" => $observaciones_adjunto,
                "meta_recibida" => $meta
            );
        }

        return $adjuntos;
    }

    private function guardar_contenido_orden_compra_crear($id_orden_compra, $compra) {
        $productos = isset($_POST['productos']) ? $_POST['productos'] : array();
        $pagos = isset($_POST['pagos']) ? $_POST['pagos'] : array();
        $nota_credito = isset($_POST['nota_credito']) ? $_POST['nota_credito'] : array();
        $productos_atencion = isset($_POST['productos_atencion']) ? $_POST['productos_atencion'] : array();
        $datos_fiscales_guardados = 0;
        $datos_fiscales_omitidos = array();

        $producto_catalogo = $this->modelo("Productos");

        foreach ($productos as $producto) {
            $guardar_producto = $compra->registrar_orden_compra_detalle_desde_edicion($id_orden_compra, $producto);
            if ($guardar_producto['error'] == true) {
                return $guardar_producto;
            }

            $id_producto_catalogo = !empty($producto['id_producto_ecom']) ? $producto['id_producto_ecom'] : (!empty($producto['id_producto']) ? $producto['id_producto'] : 0);
            if (!empty($id_producto_catalogo) && !empty($producto['datos_fiscales'])) {
                $producto_catalogo->setId_producto($id_producto_catalogo);
                $guardar_fiscal = $producto_catalogo->guardar_producto_fiscal_compra($producto['datos_fiscales']);
                if ($guardar_fiscal['error'] == false) {
                    $datos_fiscales_guardados++;
                } else {
                    $datos_fiscales_omitidos[] = array(
                        "id_producto" => $id_producto_catalogo,
                        "respuesta" => $guardar_fiscal
                    );
                }
            }

            if (isset($producto['requiere_revision']) && $producto['requiere_revision'] == 1) {
                $compra->registrar_producto_pendiente_compra($id_orden_compra, $producto);
            }
            $compra->registrar_producto_lista_proveedor_revision_compra($id_orden_compra, $producto);
        }

        foreach ($pagos as $pago) {
            $guardar_pago = $compra->registrar_pago_orden_compra($id_orden_compra, $pago);
            if ($guardar_pago['error'] == true) {
                return $guardar_pago;
            }
        }

        if (!empty($nota_credito) && isset($nota_credito['monto']) && (float) $nota_credito['monto'] > 0) {
            $guardar_nota = $compra->registrar_nota_credito_orden_compra($id_orden_compra, $nota_credito);
            if ($guardar_nota['error'] == true) {
                return $guardar_nota;
            }
        }

        foreach ($productos_atencion as $producto_atencion) {
            $guardar_atencion = $compra->registrar_producto_atencion_orden_compra($id_orden_compra, $producto_atencion);
            if ($guardar_atencion['error'] == true) {
                return $guardar_atencion;
            }
        }

        $adjuntos_subidos = $this->procesar_archivos_adjuntos_orden_compra($id_orden_compra);
        foreach ($adjuntos_subidos as $adjunto) {
            $guardar_adjunto = $compra->registrar_adjunto_orden_compra($id_orden_compra, $adjunto);
            if ($guardar_adjunto['error'] == true) {
                return $guardar_adjunto;
            }
        }

        $recepcion_almacen = $this->preparar_recepcion_almacen_si_enviada($id_orden_compra);
        $respuesta_recepcion = $this->respuesta_guardado_con_recepcion_almacen(
            "Orden de compra registrada con exito",
            $id_orden_compra,
            isset($_POST['estatus_orden']) ? $_POST['estatus_orden'] : "",
            $recepcion_almacen
        );

        return array(
            "error" => $respuesta_recepcion['error'],
            "tipo" => $respuesta_recepcion['tipo'],
            "mensaje" => $respuesta_recepcion['mensaje'],
            "depurar" => array(
                "id_orden_compra" => $id_orden_compra,
                "datos_fiscales_guardados" => $datos_fiscales_guardados,
                "datos_fiscales_omitidos" => $datos_fiscales_omitidos,
                "archivos_recibidos" => isset($_FILES['archivos_adjuntos']['name']) ? count($_FILES['archivos_adjuntos']['name']) : 0,
                "adjuntos_subidos" => count($adjuntos_subidos),
                "adjuntos_registrados" => count($adjuntos_subidos),
                "recepcion_almacen" => $recepcion_almacen
            )
        );
    }

    private function preparar_recepcion_almacen_si_enviada($id_orden_compra) {
        $validacion_recepcion = $this->validar_esquema_recepcion_almacen();
        if ($validacion_recepcion['error'] == true) {
            return $validacion_recepcion;
        }

        $almacen = $this->modelo("Almacenes");
        return $almacen->preparar_recepcion_desde_orden_compra($id_orden_compra);
    }

    private function respuesta_guardado_con_recepcion_almacen($mensaje_base, $id_orden_compra, $estatus_orden, $recepcion_almacen) {
        $estatus = strtolower(trim((string) $estatus_orden));
        if ($estatus === "enviada" && (!is_array($recepcion_almacen) || !empty($recepcion_almacen['error']))) {
            return array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => $mensaje_base . ", pero no se pudo preparar la recepcion de almacen: " . (is_array($recepcion_almacen) && isset($recepcion_almacen['mensaje']) ? $recepcion_almacen['mensaje'] : "sin detalle de respuesta")
            );
        }

        return array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => $mensaje_base
        );
    }

    private function validar_esquema_recepcion_almacen() {
        $esquema = $this->modelo("AlmacenEsquema");
        $tablas = array(
            "erp_almacen_recepciones",
            "erp_almacen_recepciones_detalle"
        );
        foreach ($tablas as $tabla) {
            if (!$esquema->tablaExiste($tabla)) {
                return array(
                    "error" => true,
                    "tipo" => "warning",
                    "mensaje" => "Falta preparar el esquema de almacen antes de crear la recepcion",
                    "depurar" => $esquema->planActualizarAlmacenInventario(false)
                );
            }
        }

        $columnas = array(
            "erp_almacen_recepciones" => array("id_orden_compra", "id_proveedor", "id_almacen", "folio", "folio_orden_compra", "estatus", "origen", "fecha_alerta", "observaciones"),
            "erp_almacen_recepciones_detalle" => array("id_recepcion_almacen", "id_orden_compra", "id_orden_compra_detalle", "id_producto", "id_sku_erp", "id_producto_proveedor", "sku", "nombre_producto", "unidad", "cantidad_ordenada", "cantidad_recibida", "cantidad_pendiente", "costo_unitario", "estatus")
        );
        foreach ($columnas as $tabla => $campos) {
            foreach ($campos as $campo) {
                if (!$esquema->columnaExiste($tabla, $campo)) {
                    return array(
                        "error" => true,
                        "tipo" => "warning",
                        "mensaje" => "Falta preparar el esquema de almacen antes de crear la recepcion",
                        "depurar" => $esquema->planActualizarAlmacenInventario(false)
                    );
                }
            }
        }

        return array("error" => false);
    }

    public function subir_adjuntos_orden_compra() {
        $this->requerirPermiso("compras.adjuntos");
        $id_orden_compra = isset($_POST['id_orden_compra']) ? $_POST['id_orden_compra'] : 0;
        if (!$id_orden_compra) {
            return json_encode(array(
                "error" => true,
                "tipo" => "warning",
                "mensaje" => "Falta orden de compra para subir adjuntos",
                "depurar" => array()
            ));
        }

        $compra = $this->modelo("Compras");
        $adjuntos_subidos = $this->procesar_archivos_adjuntos_orden_compra($id_orden_compra);
        foreach ($adjuntos_subidos as $adjunto) {
            $compra->registrar_adjunto_orden_compra($id_orden_compra, $adjunto);
        }

        return json_encode(array(
            "error" => false,
            "tipo" => "success",
            "mensaje" => "Adjuntos guardados correctamente",
            "depurar" => array(
                "id_orden_compra" => $id_orden_compra,
                "archivos_recibidos" => isset($_FILES['archivos_adjuntos']['name']) ? count($_FILES['archivos_adjuntos']['name']) : 0,
                "adjuntos_subidos" => count($adjuntos_subidos)
            )
        ));
    }

    private function eliminar_archivos_adjuntos_no_conservados($adjuntos_actuales, $adjuntos_conservar) {
        $ids_conservar = array();
        foreach ($adjuntos_conservar as $adjunto) {
            if (!empty($adjunto['id_adjunto_orden'])) {
                $ids_conservar[] = (int) $adjunto['id_adjunto_orden'];
            }
        }

        foreach ($adjuntos_actuales as $adjunto_actual) {
            $id_adjunto = isset($adjunto_actual['id_adjunto_orden']) ? (int) $adjunto_actual['id_adjunto_orden'] : 0;
            if ($id_adjunto && in_array($id_adjunto, $ids_conservar)) {
                continue;
            }
            if (empty($adjunto_actual['archivo_ruta'])) {
                continue;
            }
            $ruta = str_replace("\\", "/", $adjunto_actual['archivo_ruta']);
            $ruta_absoluta = str_replace("\\", "/", dirname(__DIR__, 2) . "/public/" . ltrim($ruta, "/"));
            $base_public = str_replace("\\", "/", dirname(__DIR__, 2) . "/public/uploads/erp/compras/ordenes/");
            if (strpos($ruta_absoluta, $base_public) === 0 && is_file($ruta_absoluta)) {
                unlink($ruta_absoluta);
            }
        }
    }

    private function limpiar_nombre_archivo($nombre_archivo) {
        $nombre_archivo = basename($nombre_archivo);
        $nombre_archivo = preg_replace('/[^A-Za-z0-9._-]/', '_', $nombre_archivo);
        return $nombre_archivo ? $nombre_archivo : "adjunto";
    }

    public function consultar_orden_de_compra() {
        $this->requerirPermiso("compras.ver");
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        if (!$id) {
            return json_encode(array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Nueva orden de compra",
                "depurar" => array(
                    "orden_compra" => array(
                        "id_orden_compra" => "",
                        "folio" => "",
                        "id_proveedor" => "",
                        "id_solicitud" => "",
                        "subtotal" => 0,
                        "impuestos" => 0,
                        "total" => 0,
                        "estatus" => "borrador",
                        "fecha_orden" => date("Y-m-d"),
                        "fecha_entrega_estimada" => "",
                        "observaciones" => ""
                    ),
                    "orden_compra_detalle" => array(),
                    "pagos" => array(),
                    "notas_credito" => array(),
                    "adjuntos" => array(),
                    "productos_atencion" => array()
                )
            ));
        }
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_orden_compra($id);
        $respuesta_solicitud = $solicitud->consultar_compra_orden();
        //var_dump($respuesta_solicitud);
        $respuesta = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al realizar la peticion",
            "depurar"
        );
        if ($respuesta_solicitud['error'] == false) {
            $respuesta = array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Consulta orden de compra con éxito",
                "depurar" => array(
                    "orden_compra" => $respuesta_solicitud['depurar']
                )
            );
            if (!empty($respuesta_solicitud['depurar']['id_solicitud'])) {
                $solicitud->setId_solicitud($respuesta_solicitud['depurar']['id_solicitud']);
                $respuesta_compra_solicitud = $solicitud->consultar_compra_solicitud();
                if ($respuesta_compra_solicitud['error'] == false) {
                    $respuesta['depurar']['solicitud_compra'] = $respuesta_compra_solicitud['depurar'];
                }
            }
            $solicitud->setId_orden_compra($id);
            $respuesta_pagos = $solicitud->consultar_pagos_orden_compra();
            $respuesta['depurar']['pagos'] = $respuesta_pagos['error'] == false ? $respuesta_pagos['depurar'] : array();

            $solicitud->setId_orden_compra($id);
            $respuesta_notas_credito = $solicitud->consultar_notas_credito_orden_compra();
            $respuesta['depurar']['notas_credito'] = $respuesta_notas_credito['error'] == false ? $respuesta_notas_credito['depurar'] : array();

            $solicitud->setId_orden_compra($id);
            $respuesta_adjuntos = $solicitud->consultar_adjuntos_orden_compra();
            $respuesta['depurar']['adjuntos'] = $respuesta_adjuntos['error'] == false ? $respuesta_adjuntos['depurar'] : array();

            $solicitud->setId_orden_compra($id);
            $respuesta_productos_atencion = $solicitud->consultar_productos_atencion_orden_compra();
            $respuesta['depurar']['productos_atencion'] = $respuesta_productos_atencion['error'] == false ? $respuesta_productos_atencion['depurar'] : array();

            //consultar_detalle
            $solicitud->setId_orden_compra($id);
            $respuesta_solicitud_detalle = $solicitud->consultar_compra_orden_detalle();
            //consultar precio producto
            //COSTO UNITARIO (costo proveedor ya esta en detalle)
            //PORCENTAJE IMPUESTO (Ya esta en detalle)
            //COSTO ANTES DE IMPUESTO (calcular con el porcentaje de impuesto)
            //PRECIO ACTUAL EL REGISTRADO EN LA PAGINA
            //PORCENTAJE ANTERIOR (CALCULAR CON EL COSTO PROVEEDOR Y EL PRECIO ACTUAL
            //GANANCIA ANTERIOR CALCULAR CON EL COSTO ACTUAL Y EL PRECIO ACTUAL
            //COSTO DE COMPRA SERÁ EL NUEVO PRECIO FACTURA, MIENTRAS COLOCAR EL ACTUAL
            //PRECIO SUGERIDO VACIO O CALCULAR CON EL MISMO PORCENTAJE ANTERIOR
            //PORCENTAJE SUGERIDO EL MISMO ANTERIOR
            //GANANCIA NUEVA CON EL NUEVO PRECIO SUGERIDO
//            var_dump($respuesta_solicitud_detalle);
            if ($respuesta_solicitud_detalle['error'] == false) {
                foreach ($respuesta_solicitud_detalle['depurar'] as $indice => $producto) {
                    //precio_base (precio actual publico)
//                    var_dump($producto);
                    $costo_actual = isset($producto['costo_compra']) && (float) $producto['costo_compra'] > 0 ? (float) $producto['costo_compra'] : (float) $producto['costo_unitario'];
                    $porcentaje_impuesto = (float) $producto['porcentaje_impuesto'];
                    if ($porcentaje_impuesto > 1) {
                        $porcentaje_impuesto = $porcentaje_impuesto / 100;
                    }
                    $costo_antes_impuestos = isset($producto['costo_antes_impuesto']) && (float) $producto['costo_antes_impuesto'] > 0
                        ? (float) $producto['costo_antes_impuesto']
                        : ($porcentaje_impuesto > 0 ? $costo_actual / (1 + $porcentaje_impuesto) : $costo_actual);
                    $costo_compra = $costo_actual;
                    $precio_actual = isset($producto['precio_venta']) && (float) $producto['precio_venta'] > 0 ? (float) $producto['precio_venta'] : ($producto['precio_base'] ? (float) $producto['precio_base'] : 0);
                    $porcentaje_anterior = isset($producto['margen_actual']) && (float) $producto['margen_actual'] != 0 ? (float) $producto['margen_actual'] : ($precio_actual > 0 ? ($precio_actual - $costo_actual) / $precio_actual : 0);
                    $ganancia_anterior = isset($producto['utilidad_actual']) && (float) $producto['utilidad_actual'] != 0 ? (float) $producto['utilidad_actual'] : ($precio_actual > 0 ? $precio_actual - $costo_actual : 0);
                    $precio_sugerido = isset($producto['precio_sugerido']) && (float) $producto['precio_sugerido'] > 0 ? (float) $producto['precio_sugerido'] : ($precio_actual > 0 && (1 - $porcentaje_anterior) > 0 ? $costo_compra / (1 - $porcentaje_anterior) : $costo_compra);
                    $porcentaje_sugerido = isset($producto['margen_nuevo']) && (float) $producto['margen_nuevo'] != 0 ? (float) $producto['margen_nuevo'] : ($precio_sugerido > 0 ? ($precio_sugerido - $costo_compra) / $precio_sugerido : 0);
                    $ganancia_nueva = isset($producto['utilidad_nueva']) && (float) $producto['utilidad_nueva'] != 0 ? (float) $producto['utilidad_nueva'] : ($precio_sugerido - $costo_compra);
                    $respuesta_solicitud_detalle['depurar'][$indice]['porcentaje_impuesto'] = $porcentaje_impuesto;
                    $respuesta_solicitud_detalle['depurar'][$indice]['costo_antes_impuesto'] = round($costo_antes_impuestos, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['costo_compra'] = round($costo_compra, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['subtotal'] = isset($producto['subtotal']) ? round((float) $producto['subtotal'], 2) : $respuesta_solicitud_detalle['depurar'][$indice]['subtotal'];
                    $respuesta_solicitud_detalle['depurar'][$indice]['total'] = isset($producto['total']) ? round((float) $producto['total'], 2) : $respuesta_solicitud_detalle['depurar'][$indice]['total'];
                    $respuesta_solicitud_detalle['depurar'][$indice]['precio_venta'] = round($precio_actual, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['margen_actual'] = round($porcentaje_anterior, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['utilidad_actual'] = round($ganancia_anterior, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['precio_sugerido'] = round($precio_sugerido, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['porcentaje_nuevo'] = round($porcentaje_sugerido, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['margen_nuevo'] = round($porcentaje_sugerido, 2);
                    $respuesta_solicitud_detalle['depurar'][$indice]['utilidad_nueva'] = round($ganancia_nueva, 2);
                    $datos_fiscales_json = isset($producto['datos_fiscales_json']) ? $producto['datos_fiscales_json'] : "";
                    $datos_fiscales = !empty($datos_fiscales_json) ? json_decode($datos_fiscales_json, true) : array();
                    if (!is_array($datos_fiscales)) {
                        $datos_fiscales = array();
                    }
                    $datos_fiscales_catalogo_json = isset($producto['datos_fiscales_catalogo_json']) ? $producto['datos_fiscales_catalogo_json'] : "";
                    $datos_fiscales_catalogo = !empty($datos_fiscales_catalogo_json) ? json_decode($datos_fiscales_catalogo_json, true) : array();
                    if (!is_array($datos_fiscales_catalogo)) {
                        $datos_fiscales_catalogo = array();
                    }
                    $fiscal_detalle_completo = !empty($datos_fiscales['clave_sat']) && !empty($datos_fiscales['clave_unidad_sat']) && !empty($datos_fiscales['objeto_impuesto']) && !empty($datos_fiscales['tipo_impuesto']);
                    if (!$fiscal_detalle_completo && !empty($datos_fiscales_catalogo)) {
                        $datos_fiscales = $datos_fiscales_catalogo;
                    }
                    $id_producto_catalogo = isset($producto['id_producto_ecom']) ? $producto['id_producto_ecom'] : (isset($producto['id_producto']) ? $producto['id_producto'] : 0);
                    $fiscal_completo = !empty($datos_fiscales['clave_sat']) && !empty($datos_fiscales['clave_unidad_sat']) && !empty($datos_fiscales['objeto_impuesto']) && !empty($datos_fiscales['tipo_impuesto']);
                    $respuesta_solicitud_detalle['depurar'][$indice]['datos_fiscales'] = $datos_fiscales;
                    $respuesta_solicitud_detalle['depurar'][$indice]['producto_registrado'] = $id_producto_catalogo > 0 ? 1 : 0;
                    $respuesta_solicitud_detalle['depurar'][$indice]['requiere_revision'] = $id_producto_catalogo > 0 && $fiscal_completo ? 0 : 1;
                    $respuesta_solicitud_detalle['depurar'][$indice]['tipo_item'] = $id_producto_catalogo > 0 ? "producto" : "producto_nuevo";
                }
                $respuesta['depurar']['orden_compra_detalle'] = $respuesta_solicitud_detalle['depurar'];
            } else {
                $respuesta['tipo'] = "warning";
                $respuesta['mensaje'] = "Consulta solicitud de compra con éxito, falto detalle de solicitud";
            }
        }
        return json_encode($respuesta);
    }

    private function validar_esquema_actualizar_orden_compra() {
        $esquema = $this->modelo("ComprasEsquema");
        $tablas = array(
            "erp_compras_ordenes",
            "erp_compras_ordenes_detalle",
            "erp_compras_ordenes_pagos",
            "erp_compras_ordenes_notas_credito",
            "erp_compras_ordenes_adjuntos",
            "erp_compras_ordenes_productos_atencion",
            "erp_proveedores_listas_productos_revision",
            "erp_compras_documentos_fiscales",
            "erp_compras_documentos_fiscales_conceptos"
        );
        foreach ($tablas as $tabla) {
            if (!$esquema->tablaExiste($tabla)) {
                return array(
                    "error" => true,
                    "tipo" => "warning",
                    "mensaje" => "Falta preparar el esquema de compras antes de actualizar ordenes",
                    "depurar" => $esquema->planActualizarOrdenCompra(false)
                );
            }
        }

        $columnas = array(
            "erp_compras_ordenes" => array("folio_proveedor", "id_almacen_destino", "solicitante", "contacto_recepcion", "telefono_recepcion", "direccion_entrega", "descuento_global_productos", "saldo_pendiente"),
            "erp_compras_ordenes_detalle" => array("id_producto_proveedor", "costo_compra", "costo_antes_impuesto", "precio_venta", "margen_actual", "utilidad_actual", "precio_sugerido", "margen_nuevo", "utilidad_nueva", "datos_fiscales_json"),
            "erp_compras_ordenes_productos_atencion" => array("id_orden_compra", "id_proveedor", "sku", "nombre_producto", "cantidad_solicitada", "cantidad_comprada", "motivo", "estatus"),
            "erp_proveedores_listas_productos_revision" => array("id_proveedor", "id_orden_compra", "id_producto", "sku", "nombre_producto", "motivo", "estatus"),
            "erp_compras_documentos_fiscales" => array("id_orden_compra", "uuid", "archivo_hash", "estatus_conciliacion"),
            "erp_compras_documentos_fiscales_conceptos" => array("id_documento_fiscal", "id_orden_detalle", "id_sku_erp", "resultado_conciliacion")
        );
        foreach ($columnas as $tabla => $campos) {
            foreach ($campos as $campo) {
                if (!$esquema->columnaExiste($tabla, $campo)) {
                    return array(
                        "error" => true,
                        "tipo" => "warning",
                        "mensaje" => "Falta preparar el esquema de compras antes de actualizar ordenes",
                        "depurar" => $esquema->planActualizarOrdenCompra(false)
                    );
                }
            }
        }

        return array("error" => false);
    }

    public function consultar_solicitud() {
        $id = $_POST['id'];
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_solicitud($id);
        $respuesta_solicitud = $solicitud->consultar_compra_solicitud();
        $respuesta = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al realizar la peticion",
            "depurar"
        );
        if ($respuesta_solicitud['error'] == false) {
            $respuesta = array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Consulta solicitud de compra con éxito",
                "depurar" => array(
                    "solicitud_compra" => $respuesta_solicitud['depurar']
                )
            );
            //consultar_detalle
            $solicitud->setId_solicitud($id);
            $respuesta_solicitud_detalle = $solicitud->consultar_compra_solicitud_detalle();
            if ($respuesta_solicitud_detalle['error'] == false) {
                $respuesta['depurar']['solicitud_detalle'] = $respuesta_solicitud_detalle['depurar'];
            } else {
                $respuesta['tipo'] = "warning";
                $respuesta['mensaje'] = "Consulta solicitud de compra con éxito, falto detalle de solicitud";
            }
        }
        return json_encode($respuesta);
    }

    public function consultar_solicitudes_de_compra() {
        $solicitudes_compra = $this->modelo("Compras");
        $respuesta = $solicitudes_compra->consulta_solicitudes_de_compra();
        return json_encode($respuesta);
    }

    public function obtener_ordenes_de_compras() {
        $inventario = $this->modelo("Compras");
        $respuesta = $inventario->consulta_ordenes_de_compras();
        return json_encode($respuesta);
    }

    public function consultar_productos_lista() {
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Compras");

        $proveedor->setId_orden_compra($id_lista);
        $response = $proveedor->consultar_productos_lista();

        echo json_encode($response);
    }

    public function consultar_solicitud_compra_detalle() {
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Compras");

        $proveedor->setId_solicitud($id_lista);
        $response = $proveedor->consultar_detalle_solicitud_compra();

        echo json_encode($response);
    }

    public function cambio_estatus_solicitud_de_compra() {
        return $this->solicitudLegadaDeshabilitada();
        $id_solicitud = $_POST['id_solicitud'];
        $estatus = $_POST['estatus'];
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_solicitud($id_solicitud);
        $solicitud->setEstatus($estatus);

        //IMPORTANTE: NO GENERAR CAMBIO DE ESTATUS SINO SE PUEDE REGISTRAR LA ORDEN DE COMPRA
        //TODO: evaluar el estatus, si es orden_generada, proceder a generar la orden de compra
        if ($estatus == "orden_generada") {
            //generar orden de compra
            $respuesta_orden_compra = $this->generar_orden_de_compra($id_solicitud);
            if ($respuesta_orden_compra['error'] == false) {
                $respuesta = $solicitud->cambio_estatus_solicitud();
            } else {
                //eliminar orden de compra
                $respuesta = array(
                    "error" => true,
                    "tipo" => "danger",
                    "mensaje" => "Error al registrar orden de compra",
                    "depurar"
                );
            }
        } elseif ($estatus == "aprobada") {
            $solicitud->setFecha_aprobacion(DATE_NOW);
            $respuesta = $solicitud->cambio_estatus_solicitud_aprobada();
            //actualizar fecha_aprobada IMPORTANTE TERMINAR CAMBIO DE ESTATUS
        }
        return json_encode($respuesta);
    }

    public function generar_orden_de_compra($id_solicitud) {
        return $this->ordenLegadaDeshabilitada();
        $respuesta = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al realizar la peticion",
            "depurar"
        );
        /* IMPORTANTE AL MOMENTO DE GENERAR RECEPCIONES O TENER UN ESTATUS EN DONDE PUEDA YO TENER LA INFORMACION FISCAL GUARDARLA O ACTUALIZARLA */
        //consultar_solicitud
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_solicitud($id_solicitud);
        $respuesta_solicitud = $solicitud->consultar_compra_solicitud();
//        var_dump($respuesta_solicitud);
        if ($respuesta_solicitud['error'] == false) {
            $respuesta = array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "solicitud compra consultada con éxito",
                "depurar"
            );
            //consultar solicitud detalle
            $respuesta_solicitud_detalle = $solicitud->consultar_compra_solicitud_detalle();
            if ($respuesta_solicitud_detalle['error'] == false) {
                //registrar_orden de compra
                $id_proveedor = $respuesta_solicitud['depurar']['id_proveedor'];
                $solicitud->setId_proveedor($id_proveedor);
                $solicitud->setId_solicitud($id_solicitud);
                $solicitud->setSubtotal(0);
                $solicitud->setImpuestos(0);
                $solicitud->setTotal(0);
                $solicitud->setFecha_orden(DATE_NOW);
                $respuesta_orden_compra = $solicitud->registrar_orden_compra();
                if ($respuesta_orden_compra['error'] == false) {
                    $respuesta = array(
                        "error" => false,
                        "tipo" => "success",
                        "mensaje" => "solicitud compra creada con éxito",
                        "depurar"
                    );
                    $id_orden_compra = $respuesta_orden_compra['depurar'];
                    //buscar en tabla de impuestos sino agregar impuestos fijos genericos
                    //calcular subtotal, total, impuestos
                    //informar si hubo error
                    $solicitud_detalle = $respuesta_solicitud_detalle['depurar'];
//                var_dump($solicitud_detalle);
                    //productos ya calculados
                    $subtotal = 0;
                    $total = 0;
                    $impuestos = 0;
                    $array_impuestos = array(
                        "incluye_iva" => 1,
                        "porcentaje_iva" => 0.16,
                        "tipo_impuesto" => "gravado"
                    );
                    $producto = $this->modelo("Productos");
                    $error = false;
                    foreach ($solicitud_detalle as $indice => $producto_detalle) {
                        if ($error == false) {
                            //consultar_impuestos
                            $producto_impuestos = $producto_detalle;
                            $producto->setSku($producto_impuestos['sku']);
                            $producto_fiscal = $producto->consultar_producto_fiscal_sku();
//                    var_dump($producto_fiscal);
                            if ($producto_fiscal['error'] == false) {
                                //si tiene datos fiscales registrados

                                $producto_impuestos['incluye_iva'] = $producto_fiscal['depurar'][0]['incluye_iva'];
                                $producto_impuestos['porcentaje_iva'] = $producto_fiscal['depurar'][0]['porcentaje_iva'];
                                $producto_impuestos['tipo_impuesto'] = $producto_fiscal['depurar'][0]['tipo_impuesto'];
                            } else {
                                $producto_impuestos['incluye_iva'] = $array_impuestos['incluye_iva'];
                                $producto_impuestos['porcentaje_iva'] = $array_impuestos['porcentaje_iva'];
                                $producto_impuestos['tipo_impuesto'] = $array_impuestos['tipo_impuesto'];
                            }
                            $respuesta_calculo_impuestos = $this->calcular_impuestos_producto($producto_impuestos, $subtotal, $total, $impuestos);
                            $subtotal = $respuesta_calculo_impuestos['subtotal'];
                            $total = $respuesta_calculo_impuestos['total'];
                            $impuestos = $respuesta_calculo_impuestos['impuestos'];
                            $producto_impuestos = $respuesta_calculo_impuestos['producto'];
                            //registrar detalle orden compra
                            $solicitud->setId_orden_compra($id_orden_compra);
                            $solicitud->setId_producto($producto_impuestos['id_producto']);
                            $solicitud->setSku($producto_impuestos['sku']);
                            $solicitud->setNombre_producto($producto_impuestos['nombre']);
                            $solicitud->setCantidad($producto_impuestos['cantidad']);
                            $solicitud->setCosto_unitario($producto_impuestos['costo_estimado']);
                            $solicitud->setPorcentaje_impuesto($producto_impuestos['porcentaje_iva']);
                            $solicitud->setSubtotal($producto_impuestos['subtotal']);
                            $solicitud->setTotal($producto_impuestos['total']);
                            $solicitud->setDescuento(0);
                            $respuesta_orden_compra_detalle = $solicitud->registrar_orden_compra_detalle();
//                            var_dump($respuesta_orden_compra_detalle);
                            if ($respuesta_orden_compra_detalle['error'] == true) {
                                $error = true;
                            }
                        }
                    }
                    if ($error == false) {
                        //actualizar_orden de compra con subtotal, total e impuestos
//                        var_dump("subtotal_final" . $subtotal);
//                        var_dump("impuestos_final" . $impuestos);
//                        var_dump("total_final" . $total);
                        $solicitud->setSubtotal($subtotal);
                        $solicitud->setImpuestos($impuestos);
                        $solicitud->setTotal($total);
                        $solicitud->actualizar_desglose_orden_compra();
                    } else {
                        //eliminar orden compra detalle
                    }
                } else {
                    $respuesta = array(
                        "error" => true,
                        "tipo" => "danger",
                        "mensaje" => "Error al registrar solicitud compra",
                        "depurar"
                    );
                }
            } else {
                //error
                $respuesta = array(
                    "error" => true,
                    "tipo" => "warning",
                    "mensaje" => "Error al solicitar solicitud detalle, no se registro la orden de compra",
                    "depurar"
                );
                //eliminar orden de compra
            }
        }

        //
        //Como ayuda agregar a observaciones como Nueva solicitud generada
        //obtener 
        return $respuesta;
    }

    public function calcular_impuestos_producto($producto, $subtotal, $total, $impuestos) {
        $costo_antes_de_iva = 0;
        $costo_con_iva = 0;
        $producto_cantidad = intval($producto['cantidad']);
        $porcentaje_impuesto = $producto['porcentaje_iva'];
        $incluye_iva = $producto['incluye_iva'];
        if ($producto["tipo_impuesto"] == "gravado") {
            if ($incluye_iva == 1) {
                $costo_con_iva = floatval($producto['costo_estimado']);
                $costo_antes_de_iva = floatval($producto['costo_estimado']) / (1 + $porcentaje_impuesto);
                $subtotal += $costo_antes_de_iva * $producto_cantidad;
                $total += $costo_con_iva * $producto_cantidad;
                $impuestos += ($costo_con_iva * $producto_cantidad) - ($costo_antes_de_iva * $producto_cantidad);
                $producto['subtotal'] = $costo_antes_de_iva * $producto_cantidad;
                $producto['total'] = $costo_con_iva * $producto_cantidad;
            } else {
                $costo_antes_de_iva = floatval($producto['costo_estimado']);
                $costo_con_iva = floatval($producto['costo_estimado']) * (1 + $porcentaje_impuesto);
                $subtotal += $costo_con_iva * $producto_cantidad;
                $total += $costo_con_iva * $producto_cantidad;
                $impuestos += ($costo_con_iva * $producto_cantidad) - ($costo_antes_de_iva * $producto_cantidad);
                $producto['subtotal'] = $costo_antes_de_iva * $producto_cantidad;
                $producto['total'] = $costo_con_iva * $producto_cantidad;
            }
        } else if ($producto["tipo_impuesto"] == "tasa_0") {
            $costo_con_iva = floatval($producto['costo_estimado']);
            $subtotal += $costo_con_iva * $producto_cantidad;
            $total += $subtotal;
            $producto['subtotal'] = $costo_con_iva * $producto_cantidad;
            $producto['total'] = $costo_con_iva * $producto_cantidad;
        }
//        var_dump("subtotal_impuestos" . $subtotal);
//        var_dump("impuestos_impuestos" . $impuestos);
//        var_dump("total_impuestos" . $total);
        return array(
            "producto" => $producto,
            "subtotal" => $subtotal,
            "total" => $total,
            "impuestos" => $impuestos
        );
    }

    public function actualizar_solicitud() {
        return $this->solicitudLegadaDeshabilitada();
        //info_registrar solicitud compra
        $respuesta_final = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al realizar la peticion",
            "depurar"
        );
        $id_solicitud = $_POST['id_solicitud'];
        $id_proveedor = $_POST['id_proveedor'];
        $folio = $_POST['folio'];
        //pendiente folio
        //estatus inicial = pendiente (pendiente, aprobada, rechazada, orden_generada, cancelada);
        $estatus_inicial = "pendiente";
        $observaciones = $_POST['comentario'];
        //registrar solicitud
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_proveedor($id_proveedor);
        $solicitud->setFolio($folio);
        $solicitud->setEstatus($estatus_inicial);
        $solicitud->setObservaciones_solicitud($observaciones);
        $solicitud->setId_solicitud($id_solicitud);
        $respuesta_solicitud = $solicitud->actualizar_solicitud_compra();

        if ($respuesta_solicitud['error'] == false) {

            //registrar detalle solicitud
            $respuesta_final = array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Solicitud actualizada con éxito",
                "depurar"
            );
            //eliminar productos solicitud detalle
            $solicitud->setId_solicitud($id_solicitud);
            $respuesta_eliminar_detalle = $solicitud->eliminar_registros_solicitud_compra_detalle();
//            var_dump($respuesta_eliminar_detalle);
            if ($respuesta_eliminar_detalle['error'] == false) {
                $elementos = $_POST['productos'];
                $error = false;
                foreach ($elementos as $elemento) {
                    if ($error == false) {
                        $solicitud->setId_solicitud($id_solicitud);
                        $solicitud->setid_producto($elemento['id_producto']);
                        $solicitud->setCantidad($elemento['cantidad']);
                        $solicitud->setCosto_estimado($elemento['precio']);
                        $solicitud->setSubtotal($elemento['subtotal']);
                        $solicitud->setObservaciones_producto($elemento['observaciones']);
                        $respuesta_producto = $solicitud->registrar_solicitud_compra_detalle();
                        $error = $respuesta_producto['error'];
                    }
                }
                if ($error == true) {
                    //eliminar todo solicitud detalle para esa solicitud
                    $solicitud->setId_solicitud($id_solicitud);
                    $solicitud->eliminar_registros_solicitud_compra_detalle();
                    $respuesta_final = array(
                        "error" => true,
                        "tipo" => "danger",
                        "mensaje" => "Error al actualizar solicitud compra detalle",
                        "depurar"
                    );
                }
            } else {
                //error al eliminar registros detalle
                $respuesta_final = array(
                    "error" => true,
                    "tipo" => "danger",
                    "mensaje" => "Error al eliminar detalle solicitud compra detalle",
                    "depurar"
                );
            }
        }





        return json_encode($respuesta_final);

//      $inventario->registrar_elementos_inventario();
    }

    public function registrar_solicitud() {
        return $this->solicitudLegadaDeshabilitada();
        //info_registrar solicitud compra
        $respuesta_final = array(
            "error" => true,
            "tipo" => "danger",
            "mensaje" => "Error al realizar la peticion",
            "depurar"
        );
        $id_proveedor = $_POST['id_proveedor'];
        $folio = $_POST['folio'];
        //pendiente folio
        //estatus inicial = pendiente (pendiente, aprobada, rechazada, orden_generada, cancelada);
        $estatus_inicial = "pendiente";
        $observaciones = $_POST['comentario'];
        //registrar solicitud
        $solicitud = $this->modelo("Compras");
        $solicitud->setId_proveedor($id_proveedor);
        $solicitud->setFolio($folio);
        $solicitud->setEstatus($estatus_inicial);
        $solicitud->setObservaciones_solicitud($observaciones);
        $solicitud->setFecha_solicitud(DATE_NOW);
        $respuesta_solicitud = $solicitud->registrar_solicitud_compra();

        if ($respuesta_solicitud['error'] == false) {
            $id_solicitud = $respuesta_solicitud['depurar'];
            //registrar detalle solicitud
            $respuesta_final = array(
                "error" => false,
                "tipo" => "success",
                "mensaje" => "Solicitud registrada con éxito",
                "depurar"
            );

            $elementos = $_POST['productos'];
            $error = false;
            foreach ($elementos as $elemento) {
                if ($error == false) {
                    $solicitud->setId_solicitud($id_solicitud);
                    $solicitud->setid_producto($elemento['id_producto']);
                    $solicitud->setCantidad($elemento['cantidad']);
                    $solicitud->setCosto_estimado($elemento['precio']);
                    $solicitud->setSubtotal($elemento['subtotal']);
                    $solicitud->setObservaciones_producto($elemento['observaciones']);
                    $respuesta_producto = $solicitud->registrar_solicitud_compra_detalle();
                    $error = $respuesta_producto['error'];
                }
            }
            if ($error == true) {
                //eliminar todo solicitud detalle para esa solicitud
                $solicitud->setId_solicitud($id_solicitud);
                $solicitud->eliminar_registros_solicitud_compra_detalle();
                $respuesta_final = array(
                    "error" => true,
                    "tipo" => "danger",
                    "mensaje" => "Error al registrar solicitud compra detalle",
                    "depurar"
                );
            }
        }





        return json_encode($respuesta_final);

//      $inventario->registrar_elementos_inventario();
    }
    private function solicitudLegadaDeshabilitada() {
        http_response_code(409);
        return json_encode(array(
            "error" => true,
            "tipo" => "warning",
            "mensaje" => "El flujo temporal de solicitudes fue reemplazado por Solicitudes ERP.",
            "depurar" => array()
        ));
    }

    private function ordenLegadaDeshabilitada() {
        http_response_code(409);
        return json_encode(array(
            "error" => true,
            "tipo" => "warning",
            "mensaje" => "El flujo temporal de ordenes fue reemplazado por Ordenes de compra ERP.",
            "depurar" => array()
        ));
    }
}
