<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: listar candidatos de SKU con existencia disponible para UAT POS.
 * Impacto: solo lectura; no reserva, no descuenta y no modifica caja ni inventario.
 * Contrato: recibe --id_almacen=ID y devuelve SKUs con disponibilidad positiva.
 */

$idAlmacen = 0;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosStockCandidatosDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosStockCandidatosDb())->db();
if (!$db || $idAlmacen <= 0) {
    echo json_encode(array(
        "ok" => false,
        "modo" => "read-only",
        "mensaje" => "Indica --id_almacen=ID con conexion disponible."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$stmt = $db->prepare("SELECT
        e.id_existencia_inventario,
        e.id_sku_erp,
        s.sku,
        COALESCE(s.nombre, '') descripcion,
        e.id_almacen_clave id_almacen,
        e.codigo_existencia,
        e.lote,
        e.fecha_caducidad,
        e.cantidad,
        e.cantidad_disponible,
        e.cantidad_apartada,
        e.estatus_existencia
    FROM erp_inventario_existencias e
    LEFT JOIN erp_catalogo_skus s ON s.id_sku=e.id_sku_erp
    WHERE e.id_almacen_clave=:almacen
      AND e.cantidad_disponible > 0
      AND e.estatus_existencia='disponible'
    ORDER BY e.cantidad_disponible DESC, e.id_existencia_inventario ASC
    LIMIT 20");
$stmt->execute(array(":almacen" => $idAlmacen));
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "id_almacen" => $idAlmacen,
    "total" => count($items),
    "items" => $items,
    "siguiente_paso" => count($items) > 0
        ? "Usar un id_sku_erp candidato en preflight de venta POS."
        : "Preparar inventario UAT o traspaso autorizado antes de venta real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
