<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-23
 * Proposito: auditar costo derivado de granel NUAN-ARMG25K-GRANEL desde receta de apertura.
 * Contrato: solo lectura; no modifica Catalogo, Inventario, Listas ni Ventas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class UatNuanArmg25kReader extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$skuCodigo = isset($argv[1]) && trim($argv[1]) !== "" ? trim($argv[1]) : "NUAN-ARMG25K-GRANEL";
$lector = new UatNuanArmg25kReader();
$db = $lector->db();
$modelo = new RentabilidadErp();
$fallas = array();

$stmt = $db->prepare("SELECT s.id_sku, s.sku, s.nombre, s.factor_unidad_base, s.id_unidad_base, s.tipo_inventario,
        p.nombre producto,
        COALESCE(u.codigo, u.abreviatura, u.nombre, '') unidad
    FROM erp_catalogo_skus s
    INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
    WHERE s.sku=:sku
    LIMIT 1");
$stmt->execute(array(":sku" => $skuCodigo));
$sku = $stmt->fetch(PDO::FETCH_ASSOC);

$recetas = array();
$resolucion = null;
$origen = null;
if ($sku) {
    $stmt = $db->prepare("SELECT ae.*, ori.sku sku_origen, ori.nombre nombre_origen, ori.factor_unidad_base factor_origen,
            COALESCE(u.codigo, u.abreviatura, u.nombre, '') unidad_origen
        FROM erp_catalogo_sku_aperturas_empaque ae
        INNER JOIN erp_catalogo_skus ori ON ori.id_sku=ae.id_sku_origen
        LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=ori.id_unidad_base
        WHERE ae.id_sku_destino=:sku
        ORDER BY ae.id_apertura_empaque DESC");
    $stmt->execute(array(":sku" => intval($sku["id_sku"])));
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $resolucion = $modelo->resolverCostoVigenteSku(intval($sku["id_sku"]), array("tipo" => "apertura_empaque"));
    if (!empty($recetas)) {
        $origen = $modelo->resolverCostoVigenteSku(intval($recetas[0]["id_sku_origen"]), array());
    }
}

$depurar = isset($resolucion["depurar"]) && is_array($resolucion["depurar"]) ? $resolucion["depurar"] : array();
if (!$sku) {
    $fallas[] = array("id" => "COST-NUAN-001", "mensaje" => "SKU NUAN-ARMG25K-GRANEL no encontrado");
} elseif (empty($recetas)) {
    $fallas[] = array("id" => "COST-NUAN-002", "mensaje" => "SKU granel sin receta de apertura en Catalogo");
} else {
    if (($depurar["fuente"] ?? null) !== "derivado_apertura_receta") {
        $fallas[] = array("id" => "COST-NUAN-003", "mensaje" => "El costo debe resolverse como derivado_apertura_receta");
    }
    if (abs(floatval($depurar["factor_usado"] ?? 0) - 25) > 0.0001) {
        $fallas[] = array("id" => "COST-NUAN-004", "mensaje" => "El factor usado debe ser 25 kg utiles, no 1 costal");
    }
    if (abs(floatval($depurar["costo"] ?? 0) - 37.4) > 0.01) {
        $fallas[] = array("id" => "COST-NUAN-005", "mensaje" => "El costo granel esperado es 935 / 25 = 37.4");
    }
    if (!contieneAdvertenciaNuan($depurar, "COST-DER-013")) {
        $fallas[] = array("id" => "COST-NUAN-006", "mensaje" => "Debe advertir que el factor fue inferido desde SKU granel/origen");
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_nuan_armg25k_granel_readonly",
    "sku_buscado" => $skuCodigo,
    "fallas" => $fallas,
    "sku" => $sku,
    "recetas_apertura" => $recetas,
    "costo_origen" => $origen,
    "resolucion" => $resolucion,
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_modifica_precios" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function contieneAdvertenciaNuan($resolucion, $id) {
    $advertencias = isset($resolucion["advertencias"]) && is_array($resolucion["advertencias"]) ? $resolucion["advertencias"] : array();
    foreach ($advertencias as $advertencia) {
        if (isset($advertencia["id"]) && $advertencia["id"] === $id) {
            return true;
        }
    }
    return false;
}
