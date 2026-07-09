<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-27
 * Proposito: validar politicas base de Garantias ERP despues de aplicarlas.
 * Impacto: Garantias ERP; verifica lectura, resolver y snapshot dry-run sin escribir BD.
 * Contrato: read-only/dry-run; no crea politicas, reglas, ventas, reclamos ni movimientos de inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/GarantiasErp.php";

$modelo = new GarantiasErp();
$idSkuUat = 7;
$fecha = "2026-06-27";

$respuesta = array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Validacion post-aplicacion de politicas base de Garantias ERP",
    "depurar" => array(
        "politicas" => $modelo->listarPoliticas(),
        "reglas" => $modelo->listarReglasPoliticas(array("estatus" => "activa")),
        "buscar_sku" => $modelo->buscarSkus(array("q" => "ART.10198")),
        "buscar_referencia_sku" => $modelo->buscarReferenciasRegla(array("ambito" => "sku", "q" => "ART.10198")),
        "buscar_referencia_categoria" => $modelo->buscarReferenciasRegla(array("ambito" => "categoria", "q" => "Catalogo")),
        "cobertura" => $modelo->auditarCoberturaSkus(),
        "politica_configurable_dryrun" => $modelo->politicaDryRun(array(
            "codigo" => "GAR_PRUEBA_CONFIGURABLE",
            "nombre" => "Garantia configurable de prueba",
            "tipo_garantia" => "garantia_tienda",
            "duracion_valor" => 15,
            "unidad_duracion" => "dias",
            "requiere_ticket" => 1,
            "requiere_diagnostico" => 1,
            "permite_cambio" => 1
        )),
        "regla_dryrun" => $modelo->politicaReglaDryRun(array(
            "id_garantia_politica" => 2,
            "ambito" => "sku",
            "id_referencia" => 7,
            "prioridad" => 10,
            "canal" => "pos"
        )),
        "regla_impacto_categoria_dryrun" => $modelo->previsualizarImpactoRegla(array(
            "id_garantia_politica" => 2,
            "ambito" => "categoria",
            "id_referencia" => 89,
            "prioridad" => 50,
            "canal" => "pos"
        )),
        "resolver_sku_uat" => $modelo->resolverGarantiaSku(array(
            "id_sku_erp" => $idSkuUat,
            "canal" => "pos",
            "fecha" => $fecha
        )),
        "snapshot_dryrun" => $modelo->ventaSnapshotDryRun(array(
            "items" => array(array("id_sku_erp" => $idSkuUat)),
            "canal" => "pos",
            "fecha" => $fecha
        )),
        "contrato" => array(
            "read_only" => true,
            "no_crea_politicas" => true,
            "no_crea_reglas" => true,
            "no_crea_ventas" => true,
            "no_crea_reclamos" => true,
            "no_mueve_inventario" => true
        )
    )
);

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
