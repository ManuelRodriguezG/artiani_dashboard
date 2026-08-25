<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-24
 * Proposito: validar costo derivado de presentacion cuyo SKU destino ya declara factor propio.
 * Impacto: evita subcostear presentaciones con receta factor 1 cuando el SKU representa multiples unidades base.
 * Contrato: solo lectura; no modifica Catalogo, Inventario, Listas, Ventas ni notificaciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class UatAliTebrioReader extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$lector = new UatAliTebrioReader();
$db = $lector->db();
$modelo = new RentabilidadErp();
$skuCodigo = "ALI-TEBRIO1K-1000P";

$sku = consultarUno($db, "SELECT s.id_sku, s.sku, s.nombre, s.id_producto_erp, p.nombre producto,
        s.id_unidad_base, u.codigo unidad, s.factor_unidad_base, s.tipo_inventario, s.estatus
    FROM erp_catalogo_skus s
    LEFT JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
    LEFT JOIN erp_catalogo_unidades u ON u.id_unidad=s.id_unidad_base
    WHERE s.sku=:sku
    LIMIT 1", array(":sku" => $skuCodigo));

$idSku = $sku ? intval($sku["id_sku"]) : 0;
$presentacion = $idSku > 0 ? consultarUno($db, "SELECT pr.*, base.sku sku_base, base.factor_unidad_base factor_base,
        pres.sku sku_presentacion, pres.factor_unidad_base factor_presentacion
    FROM erp_catalogo_sku_presentaciones pr
    INNER JOIN erp_catalogo_skus base ON base.id_sku=pr.id_sku_base
    INNER JOIN erp_catalogo_skus pres ON pres.id_sku=pr.id_sku_presentacion
    WHERE pr.id_sku_presentacion=:sku AND pr.estatus='activa'
    ORDER BY pr.id_sku_presentacion_regla DESC
    LIMIT 1", array(":sku" => $idSku)) : null;

$resolucion = $idSku > 0 ? $modelo->resolverCostoVigenteSku($idSku, array("tipo" => "presentacion")) : null;
$detalle = $resolucion && isset($resolucion["depurar"]) ? $resolucion["depurar"] : array();
$esperado = 81.538462;

$fallas = array();
if (!$sku) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-001", "mensaje" => "SKU presentacion no encontrado");
}
if ($sku && abs(floatval($sku["factor_unidad_base"]) - 1000) > 0.0001) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-002", "mensaje" => "El SKU destino debe tener factor_unidad_base 1000");
}
if ($sku && !$presentacion) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-003", "mensaje" => "SKU sin regla activa de presentacion");
}
if ($presentacion && abs(floatval($presentacion["factor_salida_base"]) - 1) > 0.0001) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-004", "mensaje" => "La prueba espera receta con factor_salida_base 1");
}
if (abs(floatval(isset($detalle["costo"]) ? $detalle["costo"] : 0) - $esperado) > 0.01) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-005", "mensaje" => "El costo debe usar factor efectivo 1000: 530 / 6500 * 1000");
}
if (abs(floatval(isset($detalle["factor_usado"]) ? $detalle["factor_usado"] : 0) - 1000) > 0.0001) {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-006", "mensaje" => "El factor usado debe ser 1000, no 1");
}
if ((isset($detalle["fuente"]) ? $detalle["fuente"] : "") !== "derivado_presentacion") {
    $fallas[] = array("id" => "COST-ALI-TEBRIO-007", "mensaje" => "Debe resolverse como derivado_presentacion");
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "rentabilidad_ali_tebrio1k_1000p_presentacion_readonly",
    "sku_buscado" => $skuCodigo,
    "esperado" => array(
        "formula" => "530 / 6500 * 1000",
        "costo" => $esperado,
        "factor_efectivo" => 1000
    ),
    "fallas" => $fallas,
    "sku" => $sku,
    "presentacion" => $presentacion,
    "resolucion" => $resolucion,
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_listas" => true,
        "no_toca_ventas" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function consultarUno($db, $sql, $params = array()) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}
