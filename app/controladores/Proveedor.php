<?php

include_once "../app/helpers/PHPExcel-1.8/Classes/PHPExcel.php";

class Proveedor extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    public function auditoria_dry_run_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        echo json_encode($this->modelo("Proveedores")->auditoriaDryRunErp());
    }

    public function auditoria_exportar_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        $formato = isset($_GET["formato"]) ? strtolower(trim((string) $_GET["formato"])) : "json";
        $respuesta = $this->modelo("Proveedores")->auditoriaDryRunErp();
        $fecha = date("Ymd_His");
        if ($formato === "csv") {
            $this->descargarAuditoriaProveedoresCsv($respuesta, $fecha);
        }
        $this->descargarAuditoriaProveedoresJson($respuesta, $fecha);
    }

    public function esquema_auditar_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        echo json_encode($this->modelo("ProveedoresEsquema")->auditarProveedoresErp());
    }

    public function esquema_actualizar_erp() {
        $this->requerirPermiso("sistema.soporte");
        $ejecutar = isset($_POST["ejecutar"]) && $_POST["ejecutar"] == 1;
        echo json_encode($this->modelo("ProveedoresEsquema")->planActualizarProveedoresErp($ejecutar));
    }

    public function auditoria_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        $this->vista("apps/erp/proveedores/auditoria");
    }

    private function descargarAuditoriaProveedoresJson($respuesta, $fecha) {
        header("X-Content-Type-Options: nosniff");
        header("Content-Type: application/json; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"proveedores_auditoria_" . $fecha . ".json\"");
        echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    private function descargarAuditoriaProveedoresCsv($respuesta, $fecha) {
        header("X-Content-Type-Options: nosniff");
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=\"proveedores_auditoria_hallazgos_" . $fecha . ".csv\"");
        $out = fopen("php://output", "w");
        fputcsv($out, array("clave", "severidad", "estado", "conteo", "descripcion", "motivo"));
        $hallazgos = isset($respuesta["depurar"]["hallazgos"]) && is_array($respuesta["depurar"]["hallazgos"])
            ? $respuesta["depurar"]["hallazgos"] : array();
        foreach ($hallazgos as $clave => $item) {
            fputcsv($out, array(
                $clave,
                isset($item["severidad"]) ? $item["severidad"] : "",
                isset($item["estado"]) ? $item["estado"] : "",
                array_key_exists("conteo", $item) ? $item["conteo"] : "",
                isset($item["descripcion"]) ? $item["descripcion"] : "",
                isset($item["motivo"]) ? $item["motivo"] : ""
            ));
        }
        fclose($out);
        exit;
    }

    public function mostrar_proveedores_erp() {
        $this->requerirPermiso("proveedores.ver");
        $this->vista("apps/erp/proveedores/listado_erp");
    }

    public function proveedores_listar_erp() {
        $this->requerirPermiso("proveedores.ver");
        $filtros = array(
            "busqueda" => isset($_REQUEST["busqueda"]) ? $_REQUEST["busqueda"] : "",
            "estatus_erp" => isset($_REQUEST["estatus_erp"]) ? $_REQUEST["estatus_erp"] : "",
            "tipo_proveedor" => isset($_REQUEST["tipo_proveedor"]) ? $_REQUEST["tipo_proveedor"] : "",
            "pagina" => isset($_REQUEST["pagina"]) ? $_REQUEST["pagina"] : 1,
            "limite" => isset($_REQUEST["limite"]) ? $_REQUEST["limite"] : 25
        );
        echo json_encode($this->modelo("Proveedores")->listarProveedoresErp($filtros));
    }

    public function proveedor_consultar_erp() {
        $this->requerirPermiso("proveedores.ver");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $incluir_sensibles = $this->modelo("SeguridadPermisos")->usuarioTienePermiso($this->usuarioActualId(), "proveedores.documentos_sensibles");
        $respuesta = $this->modelo("Proveedores")->consultarProveedorErp($id_proveedor, $incluir_sensibles);
        if ($incluir_sensibles && !$respuesta["error"] && $this->respuestaIncluyeDocumentosSensibles($respuesta)) {
            SesionSeguridad::registrarAuditoria("proveedores", "proveedor_documento_sensible_consultar", array(
                "entidad" => "erp_proveedores",
                "entidad_id" => intval($id_proveedor),
                "resultado" => "ok",
                "mensaje" => "Consulta de ficha con documentos sensibles",
                "datos_despues" => array("id_proveedor" => intval($id_proveedor))
            ));
        }
        echo json_encode($respuesta);
    }

    public function skus_comprables_por_proveedor_erp() {
        $this->requerirPermiso("proveedores.ver");
        $filtros = array(
            "id_proveedor" => isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0,
            "termino" => isset($_REQUEST["termino"]) ? $_REQUEST["termino"] : (isset($_REQUEST["q"]) ? $_REQUEST["q"] : ""),
            "limite" => isset($_REQUEST["limite"]) ? $_REQUEST["limite"] : 40
        );
        echo json_encode($this->modelo("Proveedores")->skusComprablesPorProveedorErp($filtros));
    }

    public function compras_contrato_comparar_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        $filtros = array(
            "id_proveedor" => isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0,
            "termino" => isset($_REQUEST["termino"]) ? $_REQUEST["termino"] : (isset($_REQUEST["q"]) ? $_REQUEST["q"] : "")
        );
        echo json_encode($this->modelo("Proveedores")->compararContratoComprasErp($filtros));
    }

    public function proveedor_generales_guardar_erp() {
        $id_proveedor = isset($_POST["id_proveedor"]) ? intval($_POST["id_proveedor"]) : 0;
        $this->requerirPermiso($id_proveedor > 0 ? "proveedores.editar" : "proveedores.crear");
        $respuesta = $this->modelo("Proveedores")->guardarGeneralesProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", $id_proveedor > 0 ? "proveedor_editar_generales" : "proveedor_crear", array(
            "entidad" => "erp_proveedores",
            "entidad_id" => isset($respuesta["depurar"]["id_proveedor"]) ? intval($respuesta["depurar"]["id_proveedor"]) : ($id_proveedor ?: null),
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_estatus_erp() {
        $this->requerirPermiso("proveedores.autorizar");
        $respuesta = $this->modelo("Proveedores")->cambiarEstatusProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_estatus_cambiar", array(
            "entidad" => "erp_proveedores",
            "entidad_id" => isset($respuesta["depurar"]["id_proveedor"]) ? intval($respuesta["depurar"]["id_proveedor"]) : (isset($_POST["id_proveedor"]) ? intval($_POST["id_proveedor"]) : null),
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? array(
                "proveedor" => $respuesta["depurar"]["despues"],
                "motivo" => isset($respuesta["depurar"]["motivo"]) ? $respuesta["depurar"]["motivo"] : "",
                "politica" => isset($respuesta["depurar"]["politica"]) ? $respuesta["depurar"]["politica"] : null
            ) : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_fiscal_guardar_erp() {
        $this->requerirPermiso("proveedores.fiscales");
        $respuesta = $this->modelo("Proveedores")->guardarFiscalProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_fiscal_guardar", array(
            "entidad" => "erp_proveedores_fiscales",
            "entidad_id" => isset($respuesta["depurar"]["id_proveedor_fiscal"]) ? intval($respuesta["depurar"]["id_proveedor_fiscal"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_contacto_guardar_erp() {
        $this->requerirPermiso("proveedores.contactos");
        $respuesta = $this->modelo("Proveedores")->guardarContactoProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_contacto_guardar", array(
            "entidad" => "erp_proveedores_contactos",
            "entidad_id" => isset($respuesta["depurar"]["id_contacto_proveedor"]) ? intval($respuesta["depurar"]["id_contacto_proveedor"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_condicion_guardar_erp() {
        $this->requerirPermiso("proveedores.condiciones");
        $respuesta = $this->modelo("Proveedores")->guardarCondicionProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_condicion_guardar", array(
            "entidad" => "erp_proveedores_condiciones",
            "entidad_id" => isset($respuesta["depurar"]["id_condicion_proveedor"]) ? intval($respuesta["depurar"]["id_condicion_proveedor"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_documento_guardar_erp() {
        $this->requerirPermiso("proveedores.documentos");
        $proveedores = $this->modelo("Proveedores");
        $id_proveedor = isset($_POST["id_proveedor"]) ? intval($_POST["id_proveedor"]) : 0;
        $id_documento = isset($_POST["id_documento_proveedor"]) ? intval($_POST["id_documento_proveedor"]) : 0;
        $nivel = isset($_POST["nivel_sensibilidad"]) ? $_POST["nivel_sensibilidad"] : "";
        if ($proveedores->documentoProveedorRequierePermisoSensible($id_proveedor, $id_documento, $nivel)) {
            $this->requerirPermiso("proveedores.documentos_sensibles");
        }
        $respuesta = $proveedores->guardarDocumentoProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_documento_guardar", array(
            "entidad" => "erp_proveedores_documentos",
            "entidad_id" => isset($respuesta["depurar"]["id_documento_proveedor"]) ? intval($respuesta["depurar"]["id_documento_proveedor"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_guardar_erp() {
        $this->requerirPermiso("proveedores.listas");
        $proveedores = $this->modelo("Proveedores");
        $id_proveedor = isset($_POST["id_proveedor"]) ? intval($_POST["id_proveedor"]) : 0;
        $id_documento = isset($_POST["id_documento_proveedor"]) ? intval($_POST["id_documento_proveedor"]) : 0;
        if ($id_documento > 0 && $proveedores->documentoProveedorEsSensiblePorId($id_proveedor, $id_documento)) {
            $this->requerirPermiso("proveedores.documentos_sensibles");
        }
        $respuesta = $proveedores->guardarListaProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_guardar", array(
            "entidad" => "erp_proveedores_listas_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_archivo_subir_erp() {
        $this->requerirPermiso("proveedores.listas");
        $this->requerirPermiso("proveedores.documentos");
        $respuesta = $this->modelo("Proveedores")->subirArchivoListaProveedorErp(
            $_POST,
            isset($_FILES["archivo_lista"]) ? $_FILES["archivo_lista"] : array(),
            $this->usuarioActualId()
        );
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_archivo_subir", array(
            "entidad" => "erp_proveedores_documentos",
            "entidad_id" => isset($respuesta["depurar"]["id_documento_proveedor"]) ? intval($respuesta["depurar"]["id_documento_proveedor"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => null,
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_archivo_preview_erp() {
        $this->requerirPermiso("proveedores.listas");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->previewArchivoListaProveedorErp($id_proveedor, $id_lista));
    }

    public function proveedor_lista_archivo_importar_erp() {
        $this->requerirPermiso("proveedores.listas");
        $respuesta = $this->modelo("Proveedores")->importarArchivoListaProveedorErp($_POST, $this->usuarioActualId());
        if (!is_array($respuesta) || !isset($respuesta["error"])) {
            $respuesta = array("error" => true, "tipo" => "danger", "mensaje" => "Respuesta invalida al importar la lista", "depurar" => null);
        }
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_archivo_importar", array(
            "entidad" => "erp_proveedores_listas_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => null,
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_detalle_erp() {
        $this->requerirPermiso("proveedores.listas");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->consultarListaDetalleErp($id_proveedor, $id_lista));
    }

    public function proveedor_lista_catalogos_erp() {
        $this->requerirPermiso("proveedores.listas");
        echo json_encode($this->modelo("Proveedores")->catalogosListaDetalleErp());
    }

    public function proveedor_buscar_skus_erp() {
        $this->requerirPermiso("proveedores.listas");
        $termino = isset($_GET["q"]) ? $_GET["q"] : "";
        echo json_encode($this->modelo("Proveedores")->buscarSkusErpParaLista($termino));
    }

    public function proveedor_lista_detalle_guardar_erp() {
        $this->requerirPermiso("proveedores.listas");
        $respuesta = $this->modelo("Proveedores")->guardarListaDetalleErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_detalle_guardar", array(
            "entidad" => "erp_proveedores_listas_detalle_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_detalle_erp"]) ? intval($respuesta["depurar"]["id_lista_detalle_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_detalle_eliminar_erp() {
        $this->requerirPermiso("proveedores.listas");
        $respuesta = $this->modelo("Proveedores")->eliminarListaDetalleErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_detalle_eliminar", array(
            "entidad" => "erp_proveedores_listas_detalle_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_detalle_erp"]) ? intval($respuesta["depurar"]["id_lista_detalle_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_estatus_erp() {
        $this->requerirPermiso("proveedores.autorizar");
        $respuesta = $this->modelo("Proveedores")->cambiarEstatusListaProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_lista_estatus", array(
            "entidad" => "erp_proveedores_listas_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_lista_matching_dry_run_erp() {
        $this->requerirPermiso("proveedores.matching");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->matchingListaDryRunErp($id_proveedor, $id_lista));
    }

    public function proveedor_lista_matching_decidir_erp() {
        $this->requerirPermiso("proveedores.matching");
        $respuesta = $this->modelo("Proveedores")->guardarDecisionMatchingListaErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_matching_decidir", array(
            "entidad" => "erp_proveedores_listas_detalle_erp",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_detalle_erp"]) ? intval($respuesta["depurar"]["id_lista_detalle_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_sku_relacion_aplicar_erp() {
        $this->requerirPermiso("proveedores.matching");
        $respuesta = $this->modelo("Proveedores")->aplicarRelacionSkuProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_sku_relacion_aplicar", array(
            "entidad" => "erp_catalogo_sku_proveedores",
            "entidad_id" => isset($respuesta["depurar"]["id_sku_proveedor"]) ? intval($respuesta["depurar"]["id_sku_proveedor"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_sku_relaciones_lote_preview_erp() {
        $this->requerirPermiso("proveedores.matching");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->previewRelacionesSkuProveedorLoteErp($id_proveedor, $id_lista));
    }

    public function proveedor_sku_relaciones_lote_aplicar_erp() {
        $this->requerirPermiso("proveedores.matching");
        $respuesta = $this->modelo("Proveedores")->aplicarRelacionesSkuProveedorLoteErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_sku_relaciones_lote_aplicar", array(
            "entidad" => "erp_catalogo_sku_proveedores",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => null,
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_costos_erp() {
        $this->requerirPermiso("proveedores.costos");
        $filtros = array(
            "id_proveedor" => isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0,
            "id_sku" => isset($_REQUEST["id_sku"]) ? $_REQUEST["id_sku"] : 0,
            "id_sku_proveedor" => isset($_REQUEST["id_sku_proveedor"]) ? $_REQUEST["id_sku_proveedor"] : 0,
            "id_lista_proveedor_erp" => isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0,
            "estatus" => isset($_REQUEST["estatus"]) ? $_REQUEST["estatus"] : "",
            "limite" => isset($_REQUEST["limite"]) ? $_REQUEST["limite"] : 50
        );
        echo json_encode($this->modelo("Proveedores")->consultarCostosProveedorErp($filtros));
    }

    public function proveedor_costos_lote_preview_erp() {
        $this->requerirPermiso("proveedores.costos");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->previewCostosProveedorLoteErp($id_proveedor, $id_lista));
    }

    public function proveedor_costos_lote_aplicar_erp() {
        $this->requerirPermiso("proveedores.costos");
        $respuesta = $this->modelo("Proveedores")->aplicarCostosProveedorLoteErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_costos_lote_aplicar", array(
            "entidad" => "erp_proveedores_sku_costos",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => null,
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_costo_referencia_preview_erp() {
        $this->requerirPermiso("proveedores.costos");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->previewCostoReferenciaListaProveedorErp($id_proveedor, $id_lista));
    }

    public function proveedor_costo_referencia_aplicar_erp() {
        $this->requerirPermiso("proveedores.costos");
        $respuesta = $this->modelo("Proveedores")->aplicarCostoReferenciaListaProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_costo_referencia_aplicar", array(
            "entidad" => "erp_catalogo_skus",
            "entidad_id" => isset($respuesta["depurar"]["id_lista_proveedor_erp"]) ? intval($respuesta["depurar"]["id_lista_proveedor_erp"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => null,
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_costo_aplicar_erp() {
        $this->requerirPermiso("proveedores.costos");
        $respuesta = $this->modelo("Proveedores")->aplicarCostoProveedorSkuErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_costo_aplicar", array(
            "entidad" => "erp_proveedores_sku_costos",
            "entidad_id" => isset($respuesta["depurar"]["id_costo_proveedor_sku"]) ? intval($respuesta["depurar"]["id_costo_proveedor_sku"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_incidencias_dry_run_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->incidenciasListaDryRunErp($id_proveedor, $id_lista));
    }

    public function proveedor_incidencia_crear_erp() {
        $this->requerirPermiso("proveedores.autorizar");
        $respuesta = $this->modelo("Proveedores")->crearIncidenciaProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_incidencia_crear", array(
            "entidad" => "erp_catalogo_incidencias_calidad",
            "entidad_id" => isset($respuesta["depurar"]["id_incidencia_calidad"]) ? intval($respuesta["depurar"]["id_incidencia_calidad"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    public function proveedor_incidencias_listar_erp() {
        $this->requerirPermiso("proveedores.auditoria");
        $id_proveedor = isset($_REQUEST["id_proveedor"]) ? $_REQUEST["id_proveedor"] : 0;
        $id_lista = isset($_REQUEST["id_lista_proveedor_erp"]) ? $_REQUEST["id_lista_proveedor_erp"] : 0;
        echo json_encode($this->modelo("Proveedores")->listarIncidenciasProveedorErp($id_proveedor, $id_lista));
    }

    public function proveedor_incidencia_resolver_erp() {
        $this->requerirPermiso("proveedores.autorizar");
        $respuesta = $this->modelo("Proveedores")->resolverIncidenciaProveedorErp($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("proveedores", "proveedor_incidencia_resolver", array(
            "entidad" => "erp_catalogo_incidencias_calidad",
            "entidad_id" => isset($respuesta["depurar"]["id_incidencia_calidad"]) ? intval($respuesta["depurar"]["id_incidencia_calidad"]) : null,
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_antes" => isset($respuesta["depurar"]["antes"]) ? $respuesta["depurar"]["antes"] : null,
            "datos_despues" => isset($respuesta["depurar"]["despues"]) ? $respuesta["depurar"]["despues"] : null
        ));
        echo json_encode($respuesta);
    }

    private function respuestaIncluyeDocumentosSensibles($respuesta) {
        if (!isset($respuesta["depurar"]["documentos"]) || !is_array($respuesta["depurar"]["documentos"])) {
            return false;
        }
        $proveedores = $this->modelo("Proveedores");
        foreach ($respuesta["depurar"]["documentos"] as $documento) {
            if ($proveedores->nivelDocumentoProveedorEsSensible(isset($documento["nivel_sensibilidad"]) ? $documento["nivel_sensibilidad"] : "")) {
                return true;
            }
        }
        return false;
    }

    public function crear() {
        $this->requerirPermiso("compras.crear");
        $this->vista('apps/erp/proveedores/agregar_proveedor');
//    $this->vista('agregar_producto');
    }

    public function listas_mayoreo_mostrar() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/proveedores/listas_mayoreo_mostrar");
    }

    public function usuarios_mayoreo_mostrar() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/usuarios_mayoreo/usuarios_mayoreo_mostrar");
    }

    public function usuario_mayoreo_listas() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/usuarios_mayoreo/listas_usuario_mayoreo");
    }

    public function listas_mostrar() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/proveedores/listas_mostrar");
    }

    public function lista_producto_editar() {
        $this->requerirPermiso("compras.editar");
        $this->vista("apps/erp/proveedores/lista_producto_editar");
    }

    public function lista_productos() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/proveedores/lista_productos");
    }

    public function mostrar_lista_productos() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/proveedores/listas_mostrar");
    }

    public function editar_pedido() {
        $this->requerirPermiso("compras.editar");
        $this->vista('apps/erp/proveedores/pedidos/editar');
    }

    public function nuevo_pedido() {
        $this->requerirPermiso("compras.crear");
        $this->vista('apps/erp/proveedores/pedidos/crear');
    }

    public function mostrar_pedidos() {
        $this->requerirPermiso("compras.ver");
        $this->vista('apps/erp/proveedores/pedidos/mostrar');
    }

    public function pedido_productos() {
        $this->requerirPermiso("compras.ver");
        $this->vista("apps/erp/proveedores/pedidos/pedido_productos");
    }

    public function consultar_pedido_productos_lista() {
        $this->requerirPermiso("compras.ver");
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Proveedores");

        $proveedor->setId_proveedor_pedido($id_lista);
        $response = $proveedor->consultar_productos_pedido_lista();

        echo json_encode($response);
    }

    public function registrar() {
        $this->requerirPermiso("compras.crear");
//    header('Content-Type: application/json');
//    var_dump($_POST);
        $estatus = 0;
        $proveedor_nombre = $_POST["proveedor_nombre"];
        $cuota = $_POST['cuota'];

        $producto = $this->modelo("Proveedores");
        $producto->setProveedor($proveedor_nombre);
        $producto->setCuota($cuota);

        $respuesta = $producto->registrar();

        echo json_encode($respuesta);
    }

    public function actualizar_estatus_lista_mayoreo() {
        $this->requerirPermiso("compras.editar");
//        var_dump($_POST);
        $id_lista_mayoreo = $_POST['id_lista_mayoreo'];
        $estatus = $_POST['estatus'];

        $proveedor = $this->modelo("Proveedores");
        $proveedor->setId_lista_mayoreo($id_lista_mayoreo);
        $proveedor->setEstatus($estatus);

        $respuesta = $proveedor->actualizar_estatus_lista_mayoreo();
        echo json_encode($respuesta);
    }

    public function actualizar_estatus_lista_mayoreo_usuario_mayoreo() {
        $this->requerirPermiso("compras.editar");
//        var_dump($_POST);
        $id_lista_mayoreo = $_POST['id_lista_mayoreo'];
        $accion = $_POST['accion'];
        $id_usuario = $_POST['id_usuario'];
        $proveedor = $this->modelo("Proveedores");
        $proveedor->setId_lista_mayoreo($id_lista_mayoreo);
        $proveedor->setId_usuario_mayoreo($id_usuario);
        if ($accion == "asignar") {
            $respuesta = $proveedor->asignar_lista_mayoreo_usuario_mayoreo();
        } else if ($accion == "quitar_asignacion") {
            $respuesta = $proveedor->quitar_asignacion_lista_mayoreo_usuario_mayoreo();
        }



        echo json_encode($respuesta);
    }

    public function actualizar_pedido() {
        $this->requerirPermiso("compras.editar");

        $id_proveedor = $_POST['id_proveedor'];
        $proveedor = $_POST['proveedor'];
        $id_lista_proveedor = $_POST['id_lista_proveedor'];
        $nombre_inventario = $_POST['nombre_inventario'];
        $comentario = $_POST['comentario'];
        $elementos = $_POST['productos'];
        $total = $_POST['total'];
        $id_pedido = $_POST['id'];

        $inventario = $this->modelo("Proveedores");
        $inventario->setId_proveedor_pedido($id_pedido);

        $inventario->setProveedor($proveedor);
        $inventario->setId_proveedor($id_proveedor);
        $inventario->setId_lista_proveedor($id_lista_proveedor);
        $inventario->setComentario($comentario);
        $inventario->setEstatus(1);
        $inventario->setTitulo($nombre_inventario);
        $inventario->setTotal($total);

        $respuesta = $inventario->actualizar_pedido();
//    $id_pedido = $respuesta['depurar'];
//        var_dump($elementos);
        if (sizeof($elementos) > 0) {
            $inventario->setId_proveedor_pedido($id_pedido);
            $respuesta_eliminar_productos = $inventario->eliminar_elementos_pedido();
//            var_dump($respuesta_eliminar_productos);
            if ($respuesta_eliminar_productos['error'] == false) {
                foreach ($elementos as $key => $values) {
                    $id_elemento = $values['id_producto'];
                    $cantidad = $values['cantidad'];
//          $precio = $values['precio'];
//          $importe = $cantidad * $precio;
//          $portada = $values['portada'];
//          $nombre = $values['producto'];
//                    $tipo = $values['tipo_item'];
                    //pedido
                    $inventario->setId_proveedor_pedido($id_pedido);
                    $inventario->setCantidad($cantidad);
                    $inventario->setId_elemento($id_elemento);
                    $respuesta = $inventario->registrar_elementos_pedido();
                    if ($respuesta['error'] == true) {
                        $errores_productos_pedido[] = $respuesta['error'];
                    }
                }
            }
        }

        //productos pedido
        return json_encode($respuesta);
    }

    public function consulta_completa_pedido() {
        $this->requerirPermiso("compras.ver");
        $id_pedido = $_POST['id_pedido'] && $_POST['id_pedido'] != null ? $_POST['id_pedido'] : 0;
        $respuesta = [
            'error' => true,
            'tipo' => "danger",
            'mensaje' => "Datos incorrectos",
            'depurar' => []
        ];
        //
        if ($id_pedido != 0) {
            $pedido = array();
            //pedido
            $inventario = $this->modelo("Proveedores");
            $inventario->setId_proveedor_pedido($id_pedido);
            $respuesta = $inventario->consultar_pedido();
//            var_dump($respuesta);
            if ($respuesta['error'] == false) {
                //consultar productos inventario
                $info_inventario = $respuesta['depurar'];
                $id_lista_proveedor = $respuesta['depurar']['id_lista_proveedor'];
//                var_dump($id_lista_proveedor);
                $pedido['pedido'] = $info_inventario;
                //productos inventario
                $ids_productos_pedido = array();
                $arreglo_productos_por_id = array();
                $ids_tipo_producto = array();
                $inventario->setId_proveedor_pedido($id_pedido);
                $respuesta = $inventario->consultar_elementos_inventario();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $productos_pedido = $respuesta['depurar'];
                    foreach ($productos_pedido as $key => $value) {
                        if (!in_array($value['id_elemento'], $arreglo_productos_por_id)) {
                            $ids_tipo_producto[$value['id_elemento']] = $value['tipo'];
                            $ids_productos_pedido[] = $value['id_elemento'];
                            $arreglo_productos_por_id[$value['id_elemento']] = $value;
                        }
                    }
//          $respuesta = $this->consultar_productos_para_editar($productos);
//          if ($respuesta['error'] == false) {
//            $productos = $respuesta['depurar'];
                    $pedido['arr_ids'] = $ids_productos_pedido;
                    $pedido['productos_pedido'] = $productos_pedido;
//          } else {
//            
//          }
                }

                //productos 
                $productos_resp = array();
//                $productos = $this->modelo("Productos");
                $inventario->setId_lista_proveedor($id_lista_proveedor);
                $respuesta = $inventario->listar_productos_proveedor();
//                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $productos_resp = $respuesta['depurar'];
                    $pedido['productos'] = $productos_resp;
                }

                foreach ($productos_resp as $key => $value) {
//            var_dump($productos_resp[$key]);
//            var_dump($productos_resp[$key]['tipo_item'] == "producto" && in_array($productos_resp[$key]['id_producto'], $ids_productos_pedido));
//            var_dump($arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['id_tipo_elemento']);
//                    var_dump($ids_productos_pedido);
//                    var_dump($productos_resp[$key]['id_producto']);
                    if (in_array($productos_resp[$key]['id_producto'], $ids_productos_pedido)) {
//                  var_dump($productos_resp[$key]);
                        $productos_resp[$key]['cantidad'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['cantidad'];
                        $productos_resp[$key]['descuento'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['descuento'];
                        $productos_resp[$key]['id_pedido'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['id_elemento'];
                        $productos_resp[$key]['precio'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['precio'];
                        $productos_resp[$key]['subtotal'] = $arreglo_productos_por_id[$productos_resp[$key]['id_producto']]['subtotal'];
                    }
                }
                $pedido['productos'] = $productos_resp;
//var_dump($productos_resp);
                $respuesta = [
                    'error' => false,
                    'tipo' => "success",
                    'mensaje' => "Pedido encontrado",
                    'depurar' => $pedido
                ];
            } else {
                $respuesta = [
                    'error' => true,
                    'tipo' => "danger",
                    'mensaje' => "Pedido no encontrado",
                    'depurar' => []
                ];
            }
        } else {
            $respuesta = [
                'error' => true,
                'tipo' => "danger",
                'mensaje' => "Pedido inválido",
                'depurar' => []
            ];
        }
        return json_encode($respuesta);
    }

    public function consultar_producto_editar() {
        $this->requerirPermiso("compras.editar");
        $id_producto = $_POST['id_producto'];

        $this->productos = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->consultar_para_editar();

        $categorias = $this->consultar_categorias_producto($id_producto);
        $this->productos->setId_producto($id_producto);

        $imagenes = $this->productos->listar_imagenes();

        $respuesta_info_producto = array(
            "info" => $respuesta,
            "categorias" => $categorias,
            "imagenes" => $imagenes
        );
        $response = [
            'error' => false,
            'tipo' => "success",
            'mensaje' => "respuesta generada con éxito",
            'depurar' => $respuesta_info_producto
        ];
        echo json_encode($response);
    }

    public function actualizar_lista_producto() {
        $this->requerirPermiso("compras.editar");
        $estatus = 0;

        $id_producto = $_POST['id_producto'] ? $_POST['id_producto'] : 0;
        $producto_nombre = $_POST["producto_nombre"] ? $_POST["producto_nombre"] : null;
        $producto_descripcion = $_POST['descripcion'] ? $_POST['descripcion'] : null;
        $producto_costo = $_POST["costo"] ? $_POST["costo"] : 0;
        $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : null;
        $producto_sku = $_POST["sku"] ? $_POST["sku"] : null;
        $producto_codigo_barras = $_POST["codigo_barras"] ? $_POST["codigo_barras"] : null;
        $producto_existencia = $_POST["existencia"] ? $_POST["existencia"] : 0;
        $producto_codigo_interno = $_POST["codigo_interno"] ? $_POST["codigo_interno"] : 0;
        $producto_piezas_por_caja = $_POST["piezas_por_caja"] ? $_POST["piezas_por_caja"] : 0;
        $producto_rotacion = $_POST["rotacion"] ? $_POST["rotacion"] : 0;

        $porcentaje_impuesto = $_POST["porcentaje_impuesto"] ? $_POST["porcentaje_impuesto"] : 0;
        $precio_sin_impuestos = $_POST["precio_sin_impuestos"] ? $_POST["precio_sin_impuestos"] : 0;
        $utilidad_bruta = $_POST["utlidad_bruta"] ? $_POST["utlidad_bruta"] : 0;
        $incluye_impuesto = $_POST["incluye_impuesto"] ? $_POST["incluye_impuesto"] : 0;

        $identificador = "";

        $proveedores = $_POST["proveedores"];
        $categorias = $_POST["categorias"];
        if (($id_producto && $id_producto != 0 && $producto_nombre && $producto_descripcion)) {
            $estatus = 1;
            $identificador = $this->crear_identificador($producto_nombre);
        }

        $producto = $this->modelo("Proveedores");
        $producto->setIdentificador($identificador);
        $producto->setId_producto($id_producto);
        $producto->setNombre($producto_nombre);
        $producto->setCodigo_interno($producto_codigo_interno);
        $producto->setDescripcion($producto_descripcion);
        $producto->setCosto($producto_precio_base);
        $producto->setEstatus($estatus);
        $producto->setExistencias($producto_cantidad);
        $producto->setCodigo_barras_base($producto_codigo_barras);
        $producto->setPiezas_por_caja($producto_piezas_por_caja);
        $producto->setRotacion($producto_rotacion);
        $producto->setPorcentaje_impuesto($porcentaje_impuesto);
        $producto->setPrecio_sin_impuestos($precio_sin_impuestos);
        $producto->setUtilidad_bruta($utilidad_bruta);
        $producto->setIncluye_impuesto($incluye_impuesto);

        $respuesta = $producto->actualizar_lista_producto();

        if (is_countable($categorias) && sizeof($categorias) > 0) {
//      var_dump($producto->eliminar_categorias_producto());
            $producto->eliminar_categorias_producto();
            foreach ($categorias as $key_c => $value_c) {
                $producto->setId_producto($id_producto);
                $producto->setId_categoria($value_c);
                $producto->actualizar_categorias_producto();
            }
        }




        echo json_encode($respuesta);
    }

    public function actualizar_portada() {
        $this->requerirPermiso("compras.editar");
//    var_dump($_FILES);

        $id_producto = $_POST['id_producto'];
        $tipo_imagen = $_POST['tipo_imagen'];

        $producto = $this->modelo("Proveedores");
//    var_dump($_FILES['portada']);
        $producto->setId_producto($_POST['id_producto']);
        $producto->setUrl_origen($_FILES['portada']['tmp_name']);
        $extension = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
        $fecha = new DateTime();
        $producto->setArchivo_portada($fecha->getTimestamp() . "." . $extension);
        //guardar archivo
        $response = $producto->guardar_imagenes();

        if ($response['error'] == false) {
            $url_imagen = $response['depurar'];
            //eliminar imagen
            $producto->setId_producto($id_producto);
            $producto->setTipo_imagen($tipo_imagen);
            $response = $producto->eliminar_imagen();
            if ($response['error'] == false) {
                //insertar
                $producto->setUrl_imagen($url_imagen);
                $producto->setTipo_imagen($tipo_imagen);
                $response = $producto->registrar_imagen();
            }
        }
        echo json_encode($response);
    }

    private function crear_identificador($cadena) {

        //Reemplazamos espacios por _
        $cadena = str_replace(
                array(' ', "/"),
                array('-', ""),
                $cadena
        );
        //Reemplazamos la A y a
        $cadena = str_replace(
                array('Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'),
                array('A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'),
                $cadena
        );

        //Reemplazamos la E y e
        $cadena = str_replace(
                array('É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'),
                array('E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'),
                $cadena);

        //Reemplazamos la I y i
        $cadena = str_replace(
                array('Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'),
                array('I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'),
                $cadena);

        //Reemplazamos la O y o
        $cadena = str_replace(
                array('Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'),
                array('O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'),
                $cadena);

        //Reemplazamos la U y u
        $cadena = str_replace(
                array('Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'),
                array('U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'),
                $cadena);

        //Reemplazamos la N, n, C y c
        $cadena = str_replace(
                array('Ñ', 'ñ', 'Ç', 'ç'),
                array('N', 'n', 'C', 'c'),
                $cadena
        );
        return strtolower($cadena);
    }

    private function consultar_categorias_producto($id_producto) {
//    $proveedores = $this->modelo("Proveedores");
        $this->productos->setId_producto($id_producto);
        $respuesta = $this->productos->listar_categorias_producto();
        return $respuesta;
    }

    public function listas_consultar() {
        $this->requerirPermiso("compras.ver");
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_proveedores();
        $respuesta_final = array(
            "error" => false,
            "mensaje" => "Listado correcto",
            "depurar" => array()
        );
        if ($respuesta['error'] == false) {
            $respuesta_final['depurar']['proveedores'] = $respuesta['depurar'];
        }

        //consultar folio solicitud
        $solicitud = $this->modelo("Compras");
        $respuesta_solicitud = $solicitud->consultar_folio_solicitud();
//        var_dump($respuesta_solicitud);
        if ($respuesta_solicitud['error'] == true && $respuesta_solicitud['tipo'] == "warning") {
            $respuesta_final['depurar']['folio'] = 1;
        } else {
            $respuesta_final['depurar']['folio'] = $respuesta_solicitud['depurar'][0]['folio'] + 1;
        }
        return json_encode($respuesta_final);
    }

    public function listas_mayoreo_consultar() {
        $this->requerirPermiso("compras.ver");
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_mayoreo();
        return json_encode($respuesta);
    }

    public function usuarios_mayoreo_consultar() {
        $this->requerirPermiso("compras.ver");
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_usuarios_mayoreo();
        return json_encode($respuesta);
    }

    public function listas_usuario_mayoreo_consultar() {
        $this->requerirPermiso("compras.ver");
//        var_dump($_POST);
//        var_dump($_SESSION);
        $id_usuario = $_POST['id_lista_mayoreo'];

        $lista_mayoreo = array('error' == true);
        $lista_usuario = array();

        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consulta_listas_usuario_mayoreo();

        if ($respuesta['error'] == false) {
            $lista_mayoreo = $respuesta;
        }

        //consultar listas usuario
        $inventario->setId_usuario_mayoreo($id_usuario);
        $respuesta = $inventario->consulta_listas_asignadas_usuario_mayoreo();
        if ($respuesta['error'] == false) {
            $array = $this->arreglo_id_listas($respuesta['depurar']);
            $lista_usuario = $array;
        }


        $resultados = array(
            'listas_mayoreo' => $lista_mayoreo,
            'listas_usuario' => $lista_usuario
        );
        return json_encode($resultados);
    }

    private function arreglo_id_listas($data) {
        $array = array();
        $length = count($data) - 1;
        for ($row = 0; $row <= $length; $row++) {
            $array[] = $data[$row]['id_lista_mayoreo'];
        }
        return $array;
    }

    public function consultar_productos_proveedor_busqueda() {
        $this->requerirPermiso("compras.ver");
//    var_dump($_POST['busqueda']);
        $busqueda = $_POST['busqueda'];
        $id_lista_proveedor = $_POST['id_lista_proveedor'];
        if ($busqueda) {
            $productos = $this->modelo("Proveedores");
            $productos->setBusqueda($busqueda);
            $productos->setId_lista_proveedor($id_lista_proveedor);
            $respuesta = $productos->listar_busqueda_productos();
            if ($respuesta['error'] == false) {
                $response = [
                    'error' => false,
                    'tipo' => "success",
                    'mensaje' => "respuesta generada con éxito",
                    'depurar' => $respuesta['depurar']
                ];
            } else {
                $response = [
                    'error' => true,
                    'tipo' => "warning",
                    'mensaje' => "Consulta con cero resultador",
                    'depurar' => []
                ];
            }
        } else {
            $response = [
                'error' => true,
                'tipo' => "warning",
                'mensaje' => "Consulta con cero resultador",
                'depurar' => []
            ];
        }
        return json_encode($response);
    }

    public function consultar_pedidos() {
        $this->requerirPermiso("compras.ver");
        $inventario = $this->modelo("Proveedores");
        $respuesta = $inventario->consultar_lista();
        return json_encode($respuesta);
    }

    public function registrar_pedido() {
        $this->requerirPermiso("compras.crear");
        //info_registrar solicitud compra
        $id_proveedor = $_POST['id_proveedor'];
        $proveedor = $_POST['proveedor'];
        $id_lista_proveedor = $_POST['id_lista_proveedor'];
        $nombre_inventario = $_POST['nombre_inventario'];
        $comentario = $_POST['comentario'];
        $elementos = $_POST['productos'];
        $total = $_POST['total'];

        $inventario = $this->modelo("Proveedores");

        $inventario->setProveedor($proveedor);
        $inventario->setId_proveedor($id_proveedor);
        $inventario->setId_lista_proveedor($id_lista_proveedor);
        $inventario->setComentario($comentario);
        $inventario->setEstatus(1);
        $inventario->setTitulo($nombre_inventario);
        $inventario->setTotal($total);
        $respuesta = $inventario->registrar_inventario();

        $errores_productos_pedido = array();
        if ($respuesta['error'] == false) {
            $id_pedido = $respuesta['depurar'];
            foreach ($elementos as $key => $values) {
                $id_elemento = $values['id_producto'];
                $cantidad = $values['cantidad'];
//          $precio = $values['precio'];
//          $importe = $cantidad * $precio;
//          $portada = $values['portada'];
//          $nombre = $values['producto'];
                //pedido
                $inventario->setId_proveedor_pedido($id_pedido);
                $inventario->setCantidad($cantidad);
                $inventario->setId_elemento($id_elemento);
                $respuesta = $inventario->registrar_elementos_pedido();
                if ($respuesta['error'] == true) {
                    $errores_productos_pedido[] = $respuesta['error'];
                }
            }
        }

        return json_encode($respuesta);

//      $inventario->registrar_elementos_inventario();
    }

    public function consultar_productos_lista() {
        $this->requerirPermiso("compras.ver");
        $id_lista = $_POST['id_lista'];

        $proveedor = $this->modelo("Proveedores");

        $proveedor->setId_lista_proveedor($id_lista);
        $response = $proveedor->consultar_productos_lista();

        echo json_encode($response);
    }

    public function listar() {
        $this->requerirPermiso("compras.ver");
        $producto = $this->modelo("Proveedores");

        $respuesta = $producto->listar_proveedores();
        echo json_encode($respuesta);
    }

    public function cargar_lista() {
        $this->requerirPermiso("compras.editar");
        $this->vista("apps/erp/proveedores/listas_cargar");
    }

    public function cargar_lista_mayoreo() {
        $this->requerirPermiso("compras.editar");
        $this->vista("apps/erp/proveedores/listas_mayoreo_cargar");
    }

    public function consultar() {
        $this->requerirPermiso("compras.ver");
    }

    public function generar_orden_de_compra() {
        $this->requerirPermiso("compras.crear");
        $productos = $_POST['productos'];
//        var_dump($productos);
        $length = sizeof($productos) - 1;
//        var_dump($length);

        $id_proveedor = 0;

        $proveedor = $this->modelo("Proveedores");

        $proveedor->setId_proveedor($id_proveedor);
        $respuesta = $proveedor->registrar_orden_compra();
        if ($respuesta['error'] == false) {
            $id_orden_de_compra = $respuesta['depurar'];

            for ($row = 0; $row <= $length; $row++) {
//                var_dump($productos[$row]);
                $nombre = $productos[$row]['producto'];
                $sku = $productos[$row]['codigo'];
                $costo = $productos[$row]['precio'];
                $cantidad = $productos[$row]['cantidad'];
                $id = $productos[$row]['id'];

                $proveedor->setId_orden_de_compra($id_orden_de_compra);
                $proveedor->setNombre($nombre);
                $proveedor->setSku($sku);
                $proveedor->setExistencias($cantidad);
                $proveedor->setCosto($costo);
                $proveedor->setId_producto($id);
//                var_dump($proveedor->getId_producto());
                $respuesta = $proveedor->registrar_producto_orden_compra();
            }
        }
        return json_encode($respuesta);
    }

    public function registrar_lista() {
        $this->requerirPermiso("compras.editar");
//        var_dump($_FILES);
        $fileTmpPath = $_FILES["file"]["tmp_name"];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
//    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc');
//    if (in_array($fileExtension, $allowedfileExtensions)) {
//      
//    }
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = "docs/erp/proveedores/" . $newFileName;

        $e = move_uploaded_file($fileTmpPath, $dest_path);
//        var_dump($dest_path);
//        var_dump($newFileName);
//        var_dump($fileTmpPath);
//        var_dump($e);

        $inputFileType = PHPExcel_IOFactory::identify($dest_path);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($dest_path);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
//        var_dump($highestRow);
//        $archivo = $this->modelo("Proveedores");
//        $producto = $this->modelo("Productos");
        //A Codigo
        //B Producto
        //C precio
        //D existencia
        //E Marca
        //F Productos por caja
        //G precio sugerido
        //H rotacion
//    var_dump($highestRow);
        $id_proveedor = 0;
        $id_lista_proveedor = 0;
        $proveedor_lista = $this->modelo("Proveedores");
        $registrada = 0;
        for ($row = 1; $row <= $highestRow; $row++) {

            if ($row == 1) {
                $id_proveedor = $sheet->getCell("A" . $row)->getValue();
                $tipo_lista = $sheet->getCell("B" . $row)->getValue();
                $proveedor_lista->setId_proveedor($id_proveedor);
                $proveedor_lista->setLista($tipo_lista);
                $respuesta = $proveedor_lista->consulta_lista_proveedor();
                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    $registrada = 1;
                    $id_lista_proveedor = $respuesta['depurar']['id_lista_proveedor'];
                } else {
                    $proveedor_lista->setId_proveedor($id_proveedor);
                    $proveedor_lista->setLista($tipo_lista);
                    $proveedor_lista->setEstatus(1);
                    $respuesta = $proveedor_lista->registro_lista_proveedor();
                    if ($respuesta['error'] == false) {
                        $id_lista_proveedor = $respuesta['depurar'];
                    }
                }
            } else if ($row >= 3) {
                $codigo = $sheet->getCell("A" . $row)->getValue();
                if (!empty($codigo)) {
                    $producto = $sheet->getCell("B" . $row)->getValue();
                    $precio = $sheet->getCell("C" . $row)->getValue();
                    $existencia = $sheet->getCell("D" . $row)->getValue();
                    $marca = $sheet->getCell("E" . $row)->getValue();
                    $piezas_por_caja = $sheet->getCell("F" . $row)->getValue();
                    $precio_sugerido = $sheet->getCell("G" . $row)->getValue() ? $sheet->getCell("G" . $row)->getValue() : 0;
                    $rotacion = $sheet->getCell("H" . $row)->getValue();

                    var_dump($respuesta);
                    //registrar producto semilla
                    var_dump($sheet->getCell("A" . $row)->getValue());
                    $proveedor_lista->setSku($codigo);
                    $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                    $respuesta_registro_producto = $proveedor_lista->consultar_producto_lista();

                    var_dump($respuesta_registro_producto);

                    if ($respuesta_registro_producto['error'] == true) {
                        //registrar producto
                        $proveedor_lista->setNombre($producto);
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setMarca($marca);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);
                        $proveedor_lista->setRotacion($rotacion);
                        $proveedor_lista->setPrecio_sugerido($precio_sugerido);
                        $proveedor_lista->setPiezas_por_caja($piezas_por_caja);
                        $proveedor_lista->setExistencias($existencia);
                        $respuesta_producto = $proveedor_lista->registrar_producto_lista();
                        var_dump($respuesta_producto);
                    } else {
                        $costo_anterior = $respuesta_registro_producto['depurar']['costo'];
                        //TODO consultar costo anterior antes de actualizar
//                        $respuesta_costo_anterior = $proveedor_lista->consultar_costo_producto_lista();
//                        var_dump("obtención costo anterior");
//                        var_dump($respuesta_costo_anterior);
//                        $costo_anterior = 0;
//                        var_dump($respuesta_costo_anterior['error'] == false);
//                        if ($respuesta_costo_anterior['error'] == false) {
//                            $costo_anterior = $respuesta_costo_anterior['depurar']['costo'];
//                        }
                        var_dump('costo anterior:' . $costo_anterior);
                        //actualizar producto existencia
                        $proveedor_lista->setNombre($producto);
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setMarca($marca);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);
                        $proveedor_lista->setRotacion($rotacion);
                        $proveedor_lista->setPrecio_sugerido($precio_sugerido);
                        $proveedor_lista->setPiezas_por_caja($piezas_por_caja);
                        $proveedor_lista->setExistencias($existencia);
                        //$respuesta_producto = $proveedor_lista->actualizar_producto_lista();
//                        var_dump("actualizar existencia");
//                        var_dump($respuesta_producto);
                        var_dump("son diferentes" . $costo_anterior != $precio);
                        if ($costo_anterior != $precio) {
                            //consultar si el sku esta registrado en ecom_productos
                            $producto_sku = $this->modelo("Productos");
                            $producto_sku->setSku($codigo);

                            $respuesta_consultar_producto = $producto_sku->listar_productos_imagen_sku();
                            var_dump("productosku");
                            var_dump($respuesta_consultar_producto);
                            if ($respuesta_consultar_producto['error'] == false) {
                                var_dump("entro producto si esta en uso");
                                var_dump($respuesta_consultar_producto['depurar']);
                                var_dump("Registrar historial costo");
                                $precio_actual = $respuesta_consultar_producto['depurar'][0]['precio_base'];
                                var_dump("precio_base:" . $precio_actual);
                                var_dump($precio);
                                var_dump($costo_anterior);
                                var_dump($precio - $costo_anterior);
                                $diferencia_costo = $precio - $costo_anterior;
                                $diferencia_positiva = $diferencia_costo < 0 ? $diferencia_costo * -1 : $diferencia_costo;
                                $porcentaje_diferencia = $diferencia_positiva / $precio;
                                $proveedor_lista->setCosto_anterior($costo_anterior);
                                $proveedor_lista->setDiferencia_costo($diferencia_costo);
                                $proveedor_lista->setPorcentaje_cambio($porcentaje_diferencia);
                                $proveedor_lista->setPrecio_actual($precio_actual);
                                //TODO una vez actualizado el costo nuevo registrar el historial de costos
                                $respuesta_registro_historial_costo = $proveedor_lista->registrar_historial_costo_producto_lista();
                                var_dump($respuesta_registro_historial_costo);
                                //si todo sale bien, actualizar lista proveedor
                            }
                            $respuesta = $proveedor_lista->actualizar_producto_lista();
                        }
                    }
                }
            }
        }
        unlink($dest_path);
        echo json_encode($respuesta);
    }

    public function registrar_lista_mayoreo() {
        $this->requerirPermiso("compras.editar");
//        var_dump($_FILES);
        $fileTmpPath = $_FILES["file"]["tmp_name"];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileType = $_FILES['file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
//    $allowedfileExtensions = array('jpg', 'gif', 'png', 'zip', 'txt', 'xls', 'doc');
//    if (in_array($fileExtension, $allowedfileExtensions)) {
//      
//    }
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $dest_path = "docs/erp/proveedores/" . $newFileName;

        $e = move_uploaded_file($fileTmpPath, $dest_path);
//        var_dump($dest_path);
//        var_dump($newFileName);
//        var_dump($fileTmpPath);
//        var_dump($e);

        $inputFileType = PHPExcel_IOFactory::identify($dest_path);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($dest_path);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
//        var_dump($highestRow);
//        $archivo = $this->modelo("Proveedores");
//        $producto = $this->modelo("Productos");
        //A Codigo
        //B Producto
        //C precio
        //D existencia
        //E Marca
        //F Productos por caja
        //G precio sugerido
        //H rotacion
//    var_dump($highestRow);
        $id_proveedor = 0;
        $id_lista_proveedor = 0;
        $proveedor_lista = $this->modelo("Proveedores");
        $registrada = 0;
        for ($row = 1; $row <= $highestRow; $row++) {

            if ($row == 1) {

                $id_proveedor = $sheet->getCell("A" . $row)->getValue();

                $tipo_lista = $sheet->getCell("B" . $row)->getValue();

                $proveedor_lista->setId_proveedor($id_proveedor);
                $proveedor_lista->setId_tipo_lista_mayoreo($tipo_lista);

                $respuesta = $proveedor_lista->consulta_lista_mayoreo();
                var_dump($respuesta);
                if ($respuesta['error'] == false) {
                    //obtener id_lista_mayoreo
                    $registrada = 1;
                    $id_lista_proveedor = $respuesta['depurar']['id_lista_mayoreo'];
                } else {
                    $proveedor_lista->setId_proveedor($id_proveedor);
                    $proveedor_lista->setLista($tipo_lista);
                    $proveedor_lista->setEstatus(0);
                    $respuesta = $proveedor_lista->registro_lista_mayoreo();
                    if ($respuesta['error'] == false) {
                        $id_lista_proveedor = $respuesta['depurar'];
                    }
                }
            } else if ($row >= 3) {
                $codigo = $sheet->getCell("A" . $row)->getValue();

                if (!empty($codigo)) {
                    $precio = $sheet->getCell("C" . $row)->getValue();
                    //registrar producto semilla
                    $proveedor_lista->setSku($codigo);
                    $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);

                    $respuesta_registro_producto = $proveedor_lista->consultar_producto_lista_mayoreo();
                    var_dump($respuesta_registro_producto);
                    if ($respuesta_registro_producto['error'] == true) {
                        //registrar producto
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);

                        $respuesta_producto = $proveedor_lista->registrar_producto_lista_mayoreo();
                        var_dump($respuesta_producto);
                    } else {
                        //actualizar producto existencia
                        $proveedor_lista->setSku($codigo);
                        $proveedor_lista->setId_lista_proveedor($id_lista_proveedor);
                        $proveedor_lista->setCosto($precio);
                        $proveedor_lista->setEstatus(1);

                        $respuesta_producto = $proveedor_lista->actualizar_producto_lista_mayoreo();
                        var_dump("actualizar existencia");
                        var_dump($respuesta_producto);
                    }
                }
            }
        }
        unlink($dest_path);
        echo json_encode($respuesta);
    }
}
