<?php

class Rentabilidad extends Controlador {

    public function __construct() {
        $this->requerirSesion();
    }

    public function index() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->redirigir("/rentabilidad/skus");
    }

    public function analisis() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/analisis");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir vista enfocada de SKU y escenarios de Rentabilidad.
     * Impacto: navegacion del modulo; reutiliza endpoints existentes sin escribir datos.
     * Contrato: requiere rentabilidad.ver.
     */
    public function skus() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/skus");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir vista enfocada de cierre comercial de Rentabilidad.
     * Impacto: navegacion del modulo; mantiene cierre separado de Catalogo y Ventas.
     * Contrato: requiere rentabilidad.ver.
     */
    public function cierre() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/cierre");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir vista enfocada de aprobaciones internas de Rentabilidad.
     * Impacto: navegacion del modulo; acciones persistentes siguen protegidas por rentabilidad.snapshot.
     * Contrato: requiere rentabilidad.ver.
     */
    public function aprobaciones() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/aprobaciones");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir vista enfocada de calidad de datos para Rentabilidad.
     * Impacto: auditoria de fiscal/XML, datos base, variaciones y presentaciones.
     * Contrato: requiere rentabilidad.ver y consulta read-only.
     */
    public function calidad() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/calidad");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir vista enfocada de historial y vigencia de snapshots.
     * Impacto: trazabilidad de Rentabilidad sin aplicar precios.
     * Contrato: requiere rentabilidad.ver.
     */
    public function historial() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/historial");
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-04
     * Proposito: abrir manual operativo del modulo Rentabilidad.
     * Impacto: capacitacion y UAT del modulo.
     * Contrato: requiere rentabilidad.ver y no ejecuta escrituras.
     */
    public function manual() {
        $this->requerirPermiso("rentabilidad.ver");
        $this->vista("apps/erp/rentabilidad/manual");
    }
    public function escenarios_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->escenariosBase());
    }

    public function escenarios_auditar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarEscenariosComerciales());
    }

    public function analizar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->analizarSkus($_GET));
    }

    public function comparar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->compararEscenariosSku($_GET));
    }

    public function detalle_sku_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->detalleSku($_GET));
    }

    public function matriz_escenarios_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->matrizEscenarios($_GET));
    }

    public function canales_recomendados_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->canalesRecomendados($_GET));
    }

    public function plan_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->planCierreComercial($_GET));
    }

    public function impacto_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->impactoCierreComercial($_GET));
    }

    public function hallazgos_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->hallazgosCierreComercial($_GET));
    }

    public function prioridades_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->prioridadesCierreComercial($_GET));
    }

    public function responsables_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->responsablesCierreComercial($_GET));
    }

    public function checklist_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->checklistCierreComercial($_GET));
    }

    public function autorizaciones_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->autorizacionesCierreComercial($_GET));
    }

    public function precios_objetivo_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preciosObjetivo($_GET));
    }

    public function precios_aprobacion_preflight_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preflightAprobacionPrecios($_GET));
    }

    public function aprobaciones_internas_preflight_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preflightAprobacionesInternas($_GET));
    }

    public function aprobaciones_internas_listar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->listarAprobacionesInternas($_GET));
    }

    public function aprobaciones_autorizacion_paquete_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->paqueteAutorizacionAprobaciones($_GET));
    }

    public function sensibilidad_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->sensibilidadRentabilidad($_GET));
    }

    public function tablero_ejecutivo_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->tableroEjecutivo($_GET));
    }

    public function revision_operativa_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->revisionOperativa($_GET));
    }

    public function workflow_comercial_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->workflowComercial($_GET));
    }

    public function estado_modulo_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->estadoModuloRentabilidad($_GET));
    }

    public function preflight_uso_comercial_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preflightUsoComercial($_GET));
    }

    public function plan_desbloqueo_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->planDesbloqueoComercial($_GET));
    }

    public function auditoria_final_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditoriaFinalModulo($_GET));
    }

    public function recomendaciones_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->recomendacionesOperativas($_GET));
    }

    public function cierre_precios_auditar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarCierrePrecios($_GET));
    }

    public function semaforo_cierre_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->semaforoCierre($_GET));
    }

    public function variaciones_costos_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->variacionesCostos($_GET));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-19
     * Proposito: exponer resolucion read-only de costo vigente por SKU.
     * Impacto: Rentabilidad/Listas pueden consumir costo trazable sin guardar costos en Catalogo.
     * Contrato: requiere rentabilidad.ver; no escribe BD, no toca precios, Inventario ni Ventas.
     */
    public function costo_vigente_sku_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        $idSku = intval(isset($_GET["id_sku"]) ? $_GET["id_sku"] : 0);
        return json_encode($this->modelo("RentabilidadErp")->resolverCostoVigenteSku($idSku, $_GET));
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-19
     * Proposito: auditar pendientes de costo derivado para presentaciones, aperturas y paquetes.
     * Impacto: muestra alertas operativas sin pedir captura de costos en Catalogo.
     * Contrato: requiere rentabilidad.ver; auditoria read-only.
     */
    public function costos_derivados_pendientes_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarPendientesCostoDerivado($_GET));
    }

    public function datos_base_auditar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarDatosBaseCierre($_GET));
    }

    public function fiscal_xml_auditar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarFiscalXmlCierre($_GET));
    }

    public function fiscal_preflight_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preflightFiscalCierre($_GET));
    }

    public function recomendaciones_listar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->listarRecomendaciones($_GET));
    }

    public function recomendaciones_preflight_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->preflightRecomendaciones($_GET));
    }

    public function recomendaciones_guardar_erp() {
        $this->requerirPermiso("rentabilidad.snapshot");
        $respuesta = $this->modelo("RentabilidadErp")->guardarRecomendaciones($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("rentabilidad", "recomendaciones_guardar_erp", array(
            "entidad" => "erp_rentabilidad_recomendaciones",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function aprobacion_interna_guardar_erp() {
        $this->requerirPermiso("rentabilidad.snapshot");
        $respuesta = $this->modelo("RentabilidadErp")->guardarAprobacionInterna($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("rentabilidad", "aprobacion_interna_guardar_erp", array(
            "entidad" => "erp_rentabilidad_aprobaciones_comerciales",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function aprobacion_interna_resolver_erp() {
        $this->requerirPermiso("rentabilidad.snapshot");
        $respuesta = $this->modelo("RentabilidadErp")->resolverAprobacionInterna($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("rentabilidad", "aprobacion_interna_resolver_erp", array(
            "entidad" => "erp_rentabilidad_aprobaciones_comerciales",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function recomendacion_resolver_erp() {
        $this->requerirPermiso("rentabilidad.snapshot");
        $respuesta = $this->modelo("RentabilidadErp")->resolverRecomendacion($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("rentabilidad", "recomendacion_resolver_erp", array(
            "entidad" => "erp_rentabilidad_recomendaciones",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function snapshot_guardar_erp() {
        $this->requerirPermiso("rentabilidad.snapshot");
        $respuesta = $this->modelo("RentabilidadErp")->guardarSnapshot($_POST, $this->usuarioActualId());
        SesionSeguridad::registrarAuditoria("rentabilidad", "snapshot_guardar_erp", array(
            "entidad" => "erp_rentabilidad_snapshots",
            "resultado" => $respuesta["error"] ? "error" : "ok",
            "mensaje" => $respuesta["mensaje"],
            "datos_despues" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : null
        ));
        return json_encode($respuesta);
    }

    public function snapshots_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->listarSnapshots($_GET));
    }

    public function snapshots_vigencia_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarVigenciaSnapshots($_GET));
    }

    public function costos_presentaciones_auditar_erp() {
        $this->requerirPermiso("rentabilidad.ver");
        return json_encode($this->modelo("RentabilidadErp")->auditarCostosPresentaciones($_GET));
    }

    public function esquema_auditar_erp() {
        $this->requerirPermiso("rentabilidad.configurar");
        return json_encode($this->modelo("RentabilidadEsquema")->planActualizarRentabilidad(false));
    }

    public function esquema_aprobaciones_auditar_erp() {
        $this->requerirPermiso("rentabilidad.configurar");
        return json_encode($this->modelo("RentabilidadEsquema")->planAprobacionesComerciales(false));
    }

    private function redirigir($ruta) {
        header("Location: " . $ruta);
        exit;
    }
}
