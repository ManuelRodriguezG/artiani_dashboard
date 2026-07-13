<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: auditar ventas POS con partidas sin snapshot de garantia y proponer backfill controlado.
 * Impacto: solo lectura; no crea snapshots ni modifica ventas historicas.
 * Contrato: read-only; el backfill real requiere autorizacion explicita por folio.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$limite = 20;
foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = max(1, min(100, intval(trim(substr($arg, 9), "\"' "))));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/GarantiasErp.php";

class UatVentasGarantiaSnapshotBackfillDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasGarantiaSnapshotBackfillDb();
$garantias = new GarantiasErp();
$db = $ventas->db();

$where = "v.canal='pos'";
$params = array();
if ($folio !== "") {
    $where .= " AND v.folio=:folio";
    $params[":folio"] = $folio;
}

$sql = "SELECT v.id_venta, v.folio, v.id_almacen, v.id_caja, v.id_turno_caja, v.estatus,
        d.id_venta_detalle, d.id_producto_erp, d.id_sku_erp, d.sku, d.descripcion,
        d.cantidad_venta, d.total,
        g.id_venta_detalle_garantia, g.resumen_ticket, g.tipo_garantia_snapshot
    FROM erp_ventas v
    INNER JOIN erp_ventas_detalle d ON d.id_venta=v.id_venta
    LEFT JOIN erp_ventas_detalle_garantias g ON g.id_venta_detalle=d.id_venta_detalle
    WHERE {$where}
    ORDER BY v.id_venta DESC, d.renglon ASC
    LIMIT " . intval($limite);
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$faltantes = array();
$existentes = array();
foreach ($rows as $row) {
    if (intval($row["id_venta_detalle_garantia"]) > 0) {
        $existentes[] = $row;
        continue;
    }
    $resuelto = $garantias->resolverGarantiaSku(array(
        "id_sku_erp" => intval($row["id_sku_erp"]),
        "canal" => "pos",
        "id_almacen" => intval($row["id_almacen"]),
        "fecha" => date("Y-m-d")
    ));
    $depurar = isset($resuelto["depurar"]) && is_array($resuelto["depurar"]) ? $resuelto["depurar"] : array();
    $snapshot = isset($depurar["snapshot_sugerido"]) && is_array($depurar["snapshot_sugerido"]) ? $depurar["snapshot_sugerido"] : array();
    $faltantes[] = array(
        "venta" => array(
            "id_venta" => intval($row["id_venta"]),
            "folio" => $row["folio"],
            "estatus" => $row["estatus"],
            "id_almacen" => intval($row["id_almacen"]),
            "id_turno_caja" => intval($row["id_turno_caja"])
        ),
        "detalle" => array(
            "id_venta_detalle" => intval($row["id_venta_detalle"]),
            "id_producto_erp" => intval($row["id_producto_erp"]),
            "id_sku_erp" => intval($row["id_sku_erp"]),
            "sku" => $row["sku"],
            "descripcion" => $row["descripcion"],
            "cantidad_venta" => floatval($row["cantidad_venta"]),
            "total" => floatval($row["total"])
        ),
        "garantia_resuelta" => array(
            "mensaje" => isset($resuelto["mensaje"]) ? $resuelto["mensaje"] : "",
            "tipo" => isset($resuelto["tipo"]) ? $resuelto["tipo"] : "",
            "politica" => isset($depurar["politica"]) ? $depurar["politica"] : null,
            "snapshot_sugerido" => $snapshot,
            "alertas" => isset($depurar["alertas"]) ? $depurar["alertas"] : array()
        )
    );
}

$autorizacion = $folio !== "" && !empty($faltantes)
    ? "AUTORIZO BACKFILL SNAPSHOT GARANTIA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_GARANTIA_SNAPSHOT_BACKFILL id_usuario=1 folio={$folio} confirmacion=\"BACKFILL GARANTIA POS\" motivo=\"UAT completar snapshot garantia historico POS\""
    : null;

echo json_encode(array(
    "ok" => true,
    "modo" => "ventas_pos_garantia_snapshot_backfill_readonly",
    "read_only" => true,
    "folio" => $folio,
    "total_revisado" => count($rows),
    "total_faltantes" => count($faltantes),
    "total_existentes" => count($existentes),
    "faltantes" => $faltantes,
    "existentes" => $existentes,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_modifica_ventas_historicas" => true,
        "backfill_requiere_autorizacion" => true
    ),
    "autorizacion_sugerida" => $autorizacion
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

