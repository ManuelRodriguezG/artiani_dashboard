<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: validar endpoints/modelo read-only para listar, consultar y simular resolucion de VRP.
 * Impacto: no actualiza pendiente, venta, catalogo, notificacion ni inventario.
 * Contrato: read-only/dry-run; puede usarse antes de autorizacion real de clasificacion.
 */

$args = isset($argv) ? $argv : array();
$folio = "VRP-20260724-000001";
$idSku = 1760;
foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$modelo = new VentasErp();
$listado = $modelo->ventaRapidaPendientesReadOnly(array("estatus" => "todos", "limite" => 20));
$detalle = $modelo->ventaRapidaPendienteConsultarReadOnly(array("folio" => $folio));
$dryRun = $modelo->ventaRapidaResolucionDryRun(array(
    "folio" => $folio,
    "id_sku" => $idSku,
    "decision_inventario" => "mantener_pendiente_regularizacion",
    "motivo" => "UAT dry-run resolucion venta rapida"
));

$depListado = isset($listado["depurar"]) ? $listado["depurar"] : array();
$depDetalle = isset($detalle["depurar"]) ? $detalle["depurar"] : array();
$depDry = isset($dryRun["depurar"]) ? $dryRun["depurar"] : array();
$bloqueos = array();
foreach (array($listado, $detalle, $dryRun) as $respuesta) {
    if (!empty($respuesta["error"])) {
        $bloqueos[] = isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "respuesta_error";
    }
}
foreach (isset($depDry["bloqueos"]) && is_array($depDry["bloqueos"]) ? $depDry["bloqueos"] : array() as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

$resultado = array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_venta_rapida_resolucion_readonly",
    "entrada" => array("folio" => $folio, "id_sku" => $idSku),
    "listado" => array(
        "error" => !empty($listado["error"]),
        "pendientes_count" => count(isset($depListado["pendientes"]) && is_array($depListado["pendientes"]) ? $depListado["pendientes"] : array()),
        "resumen" => isset($depListado["resumen"]) ? $depListado["resumen"] : array()
    ),
    "detalle" => array(
        "error" => !empty($detalle["error"]),
        "pendiente" => isset($depDetalle["pendiente"]) ? array(
            "folio" => $depDetalle["pendiente"]["folio"],
            "estatus" => $depDetalle["pendiente"]["estatus"],
            "inventario_estado" => $depDetalle["pendiente"]["inventario_estado"],
            "folio_venta" => $depDetalle["pendiente"]["folio_venta"]
        ) : null,
        "eventos_count" => count(isset($depDetalle["eventos"]) && is_array($depDetalle["eventos"]) ? $depDetalle["eventos"] : array())
    ),
    "dry_run" => array(
        "error" => !empty($dryRun["error"]),
        "tipo" => isset($dryRun["tipo"]) ? $dryRun["tipo"] : null,
        "mensaje" => isset($dryRun["mensaje"]) ? $dryRun["mensaje"] : null,
        "bloqueos" => isset($depDry["bloqueos"]) ? $depDry["bloqueos"] : array(),
        "avisos" => isset($depDry["avisos"]) ? $depDry["avisos"] : array(),
        "plan" => isset($depDry["plan"]) ? $depDry["plan"] : array()
    ),
    "contrato" => array(
        "read_only" => true,
        "no_actualiza_vrp" => true,
        "no_actualiza_detalle" => true,
        "no_mueve_kardex" => true,
        "no_cierra_notificacion" => true
    ),
    "siguiente_autorizacion_si_ok" => "AUTORIZO EJECUTAR RESOLUCION REAL VENTA RAPIDA POS usando respaldo UAT POS vigente con token VENTAS_POS_VENTA_RAPIDA_RESOLVER_REAL id_usuario=1 folio=" . $folio . " id_sku=" . $idSku . " decision_inventario=mantener_pendiente_regularizacion confirmacion=\"RESOLVER VENTA RAPIDA POS\" motivo=\"Clasificar producto vendido por venta rapida POS\" para UAT POS/Catalogo/Inventario"
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);