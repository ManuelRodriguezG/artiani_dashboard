<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-05
 * Proposito: validar endpoints/model methods usados por cada vista separada de Rentabilidad.
 * Impacto: UAT read-only para navegacion operativa de Costos/Rentabilidad.
 * Contrato: consulta modelo de Rentabilidad; no escribe BD, no aplica precios, no toca Inventario/Ventas/ecommerce.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$filtrosBase = array(
    "q" => isset($argv[1]) ? trim($argv[1]) : "TP-40372",
    "canal" => "menudeo",
    "riesgo" => "",
    "accion" => "",
    "stock" => "",
    "origen_costo" => "",
    "proveedor" => "",
    "descuento_pct" => "",
    "gasto_pct" => "",
    "comision_pct" => "",
    "margen_objetivo_pct" => "",
    "limite" => 5
);

$vistas = array(
    "analisis" => array(
        array("id" => "analizar", "metodo" => "analizarSkus", "args" => array($filtrosBase)),
        array("id" => "tablero", "metodo" => "tableroEjecutivo", "args" => array($filtrosBase)),
        array("id" => "estado_modulo", "metodo" => "estadoModuloRentabilidad", "args" => array($filtrosBase)),
        array("id" => "uso_comercial", "metodo" => "preflightUsoComercial", "args" => array($filtrosBase)),
        array("id" => "desbloqueo", "metodo" => "planDesbloqueoComercial", "args" => array($filtrosBase)),
        array("id" => "auditoria_final", "metodo" => "auditoriaFinalModulo", "args" => array($filtrosBase)),
        array("id" => "recomendaciones", "metodo" => "recomendacionesOperativas", "args" => array($filtrosBase))
    ),
    "skus" => array(
        array("id" => "escenarios_auditar", "metodo" => "auditarEscenariosComerciales", "args" => array()),
        array("id" => "matriz", "metodo" => "matrizEscenarios", "args" => array($filtrosBase)),
        array("id" => "canales", "metodo" => "canalesRecomendados", "args" => array($filtrosBase)),
        array("id" => "precios_objetivo", "metodo" => "preciosObjetivo", "args" => array($filtrosBase)),
        array("id" => "sensibilidad", "metodo" => "sensibilidadRentabilidad", "args" => array($filtrosBase)),
        array("id" => "analizar", "metodo" => "analizarSkus", "args" => array($filtrosBase))
    ),
    "cierre" => array(
        array("id" => "plan", "metodo" => "planCierreComercial", "args" => array($filtrosBase)),
        array("id" => "impacto", "metodo" => "impactoCierreComercial", "args" => array($filtrosBase)),
        array("id" => "hallazgos", "metodo" => "hallazgosCierreComercial", "args" => array($filtrosBase)),
        array("id" => "prioridades", "metodo" => "prioridadesCierreComercial", "args" => array($filtrosBase)),
        array("id" => "responsables", "metodo" => "responsablesCierreComercial", "args" => array($filtrosBase)),
        array("id" => "checklist", "metodo" => "checklistCierreComercial", "args" => array($filtrosBase)),
        array("id" => "autorizaciones", "metodo" => "autorizacionesCierreComercial", "args" => array($filtrosBase)),
        array("id" => "recomendaciones_preflight", "metodo" => "preflightRecomendaciones", "args" => array($filtrosBase)),
        array("id" => "recomendaciones_persistentes", "metodo" => "listarRecomendaciones", "args" => array($filtrosBase))
    ),
    "aprobaciones" => array(
        array("id" => "precios_aprobacion", "metodo" => "preflightAprobacionPrecios", "args" => array($filtrosBase)),
        array("id" => "aprobaciones_internas", "metodo" => "preflightAprobacionesInternas", "args" => array($filtrosBase)),
        array("id" => "aprobaciones_autorizacion", "metodo" => "paqueteAutorizacionAprobaciones", "args" => array($filtrosBase)),
        array("id" => "aprobaciones_persistentes", "metodo" => "listarAprobacionesInternas", "args" => array($filtrosBase))
    ),
    "calidad" => array(
        array("id" => "cierre", "metodo" => "auditarCierrePrecios", "args" => array($filtrosBase)),
        array("id" => "semaforo", "metodo" => "semaforoCierre", "args" => array($filtrosBase)),
        array("id" => "variaciones", "metodo" => "variacionesCostos", "args" => array($filtrosBase)),
        array("id" => "datos_base", "metodo" => "auditarDatosBaseCierre", "args" => array($filtrosBase)),
        array("id" => "fiscal_xml", "metodo" => "auditarFiscalXmlCierre", "args" => array($filtrosBase)),
        array("id" => "fiscal_preflight", "metodo" => "preflightFiscalCierre", "args" => array($filtrosBase)),
        array("id" => "workflow", "metodo" => "workflowComercial", "args" => array($filtrosBase)),
        array("id" => "presentaciones", "metodo" => "auditarCostosPresentaciones", "args" => array($filtrosBase))
    ),
    "historial" => array(
        array("id" => "snapshots", "metodo" => "listarSnapshots", "args" => array($filtrosBase)),
        array("id" => "snapshots_vigencia", "metodo" => "auditarVigenciaSnapshots", "args" => array($filtrosBase))
    )
);

$resultados = array();
$fallas = array();
foreach ($vistas as $vista => $endpoints) {
    $resultados[$vista] = array();
    foreach ($endpoints as $endpoint) {
        $resultado = ejecutar($modelo, $endpoint);
        $resultados[$vista][] = $resultado;
        if (!$resultado["ok"]) {
            $fallas[] = array("vista" => $vista, "endpoint" => $endpoint["id"], "mensaje" => $resultado["mensaje"]);
        }
    }
}

echo json_encode(array(
    "ok" => count($fallas) === 0,
    "modo" => "rentabilidad_endpoints_vistas_readonly",
    "fecha" => date("Y-m-d H:i:s"),
    "sku_prueba" => $filtrosBase["q"],
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_aplica_precios" => true,
        "no_inventario" => true,
        "no_ventas_ecommerce" => true
    ),
    "total_endpoints" => contarEndpoints($vistas),
    "fallas" => $fallas,
    "vistas" => $resultados
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function ejecutar($modelo, $endpoint) {
    $inicio = microtime(true);
    $metodo = $endpoint["metodo"];
    if (!method_exists($modelo, $metodo)) {
        return array("id" => $endpoint["id"], "metodo" => $metodo, "ok" => false, "mensaje" => "Metodo no existe", "ms" => 0);
    }
    try {
        $respuesta = call_user_func_array(array($modelo, $metodo), $endpoint["args"]);
        $ok = is_array($respuesta) && isset($respuesta["error"]) && !$respuesta["error"];
        return array(
            "id" => $endpoint["id"],
            "metodo" => $metodo,
            "ok" => $ok,
            "tipo" => is_array($respuesta) && isset($respuesta["tipo"]) ? $respuesta["tipo"] : null,
            "mensaje" => is_array($respuesta) && isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "Respuesta sin contrato esperado",
            "ms" => round((microtime(true) - $inicio) * 1000, 2),
            "resumen" => resumenRespuesta($respuesta)
        );
    } catch (Throwable $e) {
        return array("id" => $endpoint["id"], "metodo" => $metodo, "ok" => false, "mensaje" => $e->getMessage(), "ms" => round((microtime(true) - $inicio) * 1000, 2));
    }
}

function resumenRespuesta($respuesta) {
    if (!is_array($respuesta) || !isset($respuesta["depurar"]) || !is_array($respuesta["depurar"])) {
        return array();
    }
    $depurar = $respuesta["depurar"];
    $resumen = array();
    foreach (array("total", "evaluados", "items", "pendientes", "aprobables", "bloqueados", "recomendaciones", "snapshots") as $clave) {
        if (isset($depurar[$clave])) {
            $resumen[$clave] = is_array($depurar[$clave]) ? count($depurar[$clave]) : $depurar[$clave];
        }
    }
    return $resumen;
}

function contarEndpoints($vistas) {
    $total = 0;
    foreach ($vistas as $endpoints) { $total += count($endpoints); }
    return $total;
}