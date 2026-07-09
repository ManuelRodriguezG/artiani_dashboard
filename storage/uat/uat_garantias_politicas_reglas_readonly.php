<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: ejecutar una suite UAT read-only para politicas, reglas, cobertura y snapshots de Garantias ERP.
 * Impacto: Garantias/Catalogo/Ventas; confirma contratos de consulta y dry-run sin escribir datos.
 * Contrato: no crea politicas, no crea reglas, no cambia estatus, no crea ventas, no guarda snapshots y no mueve inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/GarantiasErp.php";

class UatGarantiasPoliticasReglasReadonly extends GarantiasErp {
    /**
     * IA: Codex GPT-5
     * Fecha: 2026-06-29
     * Proposito: obtener candidatos minimos para pruebas read-only sin depender de IDs fijos.
     * Impacto: Garantias UAT; usa solo SELECT y tolera ambientes sin datos de prueba.
     * Contrato: no modifica BD.
     */
    public function candidatos() {
        $disponibilidad = $this->disponibilidadEsquema();
        if (!$disponibilidad["disponible"]) {
            return array(
                "politica" => null,
                "sku" => null,
                "regla" => null,
                "faltantes" => $disponibilidad["faltantes"]
            );
        }

        $db = $this->getConexion();
        $politica = $db->query("SELECT * FROM erp_garantias_politicas WHERE estatus='activa' ORDER BY id_garantia_politica ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $sku = $db->query("SELECT s.id_sku, s.sku, s.nombre FROM erp_catalogo_skus s WHERE s.estatus='activo' ORDER BY s.id_sku ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $regla = $db->query("SELECT * FROM erp_garantias_politicas_reglas WHERE estatus='activa' ORDER BY prioridad ASC, id_regla_garantia ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        return array(
            "politica" => $politica ?: null,
            "sku" => $sku ?: null,
            "regla" => $regla ?: null,
            "faltantes" => array()
        );
    }
}

$modelo = new UatGarantiasPoliticasReglasReadonly();
$fecha = date("Y-m-d");
$candidatos = $modelo->candidatos();
$politica = $candidatos["politica"];
$sku = $candidatos["sku"];
$regla = $candidatos["regla"];
$idPolitica = $politica ? intval($politica["id_garantia_politica"]) : 0;
$idSku = $sku ? intval($sku["id_sku"]) : 0;

$referencias = array();
foreach (array("sku", "producto", "categoria", "marca", "proveedor") as $ambito) {
    $referencias[$ambito] = $modelo->buscarReferenciasRegla(array(
        "ambito" => $ambito,
        "q" => $sku ? (string) $sku["sku"] : "__SIN_CANDIDATO__"
    ));
}

$reglaDryRunValida = array(
    "error" => false,
    "tipo" => "warning",
    "mensaje" => "Sin politica o SKU activo candidato para dry-run valido",
    "depurar" => array(
        "dry_run" => true,
        "bloqueos" => array("sin_candidato_politica_o_sku")
    )
);

$impactoDryRun = array(
    "error" => false,
    "tipo" => "warning",
    "mensaje" => "Sin politica o SKU activo candidato para impacto",
    "depurar" => array(
        "dry_run" => true,
        "bloqueos" => array("sin_candidato_politica_o_sku")
    )
);

if ($idPolitica > 0 && $idSku > 0) {
    $reglaDryRunValida = $modelo->politicaReglaDryRun(array(
        "id_garantia_politica" => $idPolitica,
        "ambito" => "sku",
        "id_referencia" => $idSku,
        "prioridad" => 9999,
        "canal" => "uat_readonly"
    ));
    $impactoDryRun = $modelo->previsualizarImpactoRegla(array(
        "id_garantia_politica" => $idPolitica,
        "ambito" => "sku",
        "id_referencia" => $idSku,
        "prioridad" => 9999,
        "canal" => "uat_readonly"
    ));
}

$respuesta = array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Suite UAT read-only de Garantias ERP ejecutada",
    "depurar" => array(
        "fecha" => $fecha,
        "disponibilidad" => $modelo->disponibilidadEsquema(),
        "candidatos" => $candidatos,
        "politicas" => $modelo->listarPoliticas(),
        "reglas_activas" => $modelo->listarReglasPoliticas(array("estatus" => "activa")),
        "cobertura" => $modelo->auditarCoberturaSkus(),
        "referencias_por_ambito" => $referencias,
        "regla_dryrun_valida_o_bloqueada" => $reglaDryRunValida,
        "regla_dryrun_referencia_invalida" => $modelo->politicaReglaDryRun(array(
            "id_garantia_politica" => $idPolitica > 0 ? $idPolitica : 1,
            "ambito" => "sku",
            "id_referencia" => 999999999,
            "prioridad" => 9998,
            "canal" => "uat_readonly"
        )),
        "impacto_dryrun" => $impactoDryRun,
        "resolver_sku" => $idSku > 0 ? $modelo->resolverGarantiaSku(array(
            "id_sku_erp" => $idSku,
            "canal" => "pos",
            "fecha" => $fecha
        )) : null,
        "snapshot_dryrun" => $idSku > 0 ? $modelo->ventaSnapshotDryRun(array(
            "items" => array(array("id_sku_erp" => $idSku)),
            "canal" => "pos",
            "fecha" => $fecha
        )) : null,
        "contrato" => array(
            "read_only" => true,
            "no_crea_politicas" => true,
            "no_crea_reglas" => true,
            "no_cambia_estatus" => true,
            "no_crea_ventas" => true,
            "no_guarda_snapshots" => true,
            "no_mueve_inventario" => true
        )
    )
);

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
