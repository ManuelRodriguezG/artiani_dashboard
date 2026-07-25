<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: verificar una resolucion real VRP ya aplicada sin intentar resolver otra vez.
 * Impacto: valida pendiente, detalle, eventos, notificaciones y ausencia de kardex.
 * Contrato: read-only; no escribe BD.
 */
$args = isset($argv) ? $argv : array();
$folio = "VRP-20260724-000001";
foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) { $folio = trim(substr($arg, 8), "\"' "); }
}
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
class UatVrpResolverPostcheckDb extends CRUD { public function db() { return $this->getConexion(); } }
$db = (new UatVrpResolverPostcheckDb())->db();

$stmt = $db->prepare("SELECT * FROM erp_pos_venta_rapida_pendientes WHERE folio=:folio LIMIT 1");
$stmt->execute(array(":folio" => $folio));
$pendiente = $stmt->fetch(PDO::FETCH_ASSOC);
$detalle = null; $eventos = array(); $notificaciones = array(); $kardex = 0;
if ($pendiente) {
    $stmt = $db->prepare("SELECT * FROM erp_ventas_detalle WHERE id_venta_detalle=:detalle LIMIT 1");
    $stmt->execute(array(":detalle" => intval($pendiente["id_venta_detalle"])));
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT tipo_evento, estatus_anterior, estatus_nuevo, id_sku_erp, creado_por FROM erp_pos_venta_rapida_eventos WHERE id_venta_rapida_pendiente=:pendiente ORDER BY id_venta_rapida_evento ASC");
    $stmt->execute(array(":pendiente" => intval($pendiente["id_venta_rapida_pendiente"])));
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT id_notificacion, tipo, area_responsable, estatus, titulo FROM erp_notificaciones WHERE entidad_origen='erp_pos_venta_rapida_pendientes' AND id_entidad_origen=:pendiente ORDER BY id_notificacion ASC");
    $stmt->execute(array(":pendiente" => intval($pendiente["id_venta_rapida_pendiente"])));
    $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_inventario_movimientos WHERE referencia IN (:venta, :vrp)");
    $stmt->execute(array(":venta" => $pendiente["folio_venta"], ":vrp" => $pendiente["folio"]));
    $kardex = intval($stmt->fetchColumn());
}
$bloqueos = array();
if (!$pendiente) { $bloqueos[] = "pendiente_no_encontrado"; }
if ($pendiente && $pendiente["estatus"] !== "clasificado") { $bloqueos[] = "pendiente_no_clasificado"; }
if ($pendiente && intval($pendiente["id_sku_erp_resuelto"]) <= 0) { $bloqueos[] = "sku_resuelto_faltante"; }
if (!$detalle || intval($detalle["id_sku_erp"]) <= 0) { $bloqueos[] = "detalle_sku_faltante"; }
if ($kardex !== 0) { $bloqueos[] = "kardex_no_esperado"; }
$tiposEventos = array_map(function ($e) { return $e["tipo_evento"]; }, $eventos);
if (!in_array("clasificado_sku", $tiposEventos, true)) { $bloqueos[] = "evento_clasificado_faltante"; }
$inventarioPendiente = false;
foreach ($notificaciones as $notificacion) {
    if ($notificacion["tipo"] === "regularizacion_inventario_pos_vrp" && $notificacion["estatus"] === "pendiente") { $inventarioPendiente = true; }
}
if (!$inventarioPendiente) { $bloqueos[] = "notificacion_inventario_pendiente_faltante"; }

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_pos_venta_rapida_resolver_postcheck_readonly",
    "folio" => $folio,
    "bloqueos" => $bloqueos,
    "pendiente" => $pendiente ? array(
        "folio" => $pendiente["folio"],
        "folio_venta" => $pendiente["folio_venta"],
        "estatus" => $pendiente["estatus"],
        "inventario_estado" => $pendiente["inventario_estado"],
        "id_sku_erp_resuelto" => intval($pendiente["id_sku_erp_resuelto"]),
        "id_producto_erp_resuelto" => intval($pendiente["id_producto_erp_resuelto"]),
        "resuelto_por" => intval($pendiente["resuelto_por"])
    ) : null,
    "detalle" => $detalle ? array(
        "id_venta_detalle" => intval($detalle["id_venta_detalle"]),
        "sku" => $detalle["sku"],
        "id_sku_erp" => intval($detalle["id_sku_erp"]),
        "modo_salida" => $detalle["modo_salida"],
        "inventario_estado" => $detalle["inventario_estado"],
        "precio_unitario" => $detalle["precio_unitario"],
        "subtotal" => $detalle["subtotal"]
    ) : null,
    "eventos" => $eventos,
    "notificaciones" => $notificaciones,
    "kardex_count" => $kardex,
    "contrato" => array("read_only" => true, "no_mueve_kardex" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);