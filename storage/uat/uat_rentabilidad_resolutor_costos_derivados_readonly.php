<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-19
 * Proposito: validar resolutor read-only de costos directos y derivados en Rentabilidad.
 * Impacto: confirma presentaciones, aperturas y paquetes sin guardar costos en Catalogo.
 * Contrato: no escribe BD, no actualiza precios, no toca Ventas ni Inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

class UatRentabilidadCostosDerivadosReader extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$lector = new UatRentabilidadCostosDerivadosReader();
$db = $lector->db();
$modelo = new RentabilidadErp();
$skus = array("TP-40372", "TP-40372-500GR", "TP-40372-100GR", "NUEC-C20K-GRANEL", "PER-05-01");
$resultados = array();
$fallas = array();

foreach ($skus as $sku) {
    $stmt = $db->prepare("SELECT id_sku FROM erp_catalogo_skus WHERE sku=:sku LIMIT 1");
    $stmt->execute(array(":sku" => $sku));
    $idSku = intval($stmt->fetchColumn());
    if ($idSku <= 0) {
        $fallas[] = array("id" => "COST-DER-UAT-001", "sku" => $sku, "mensaje" => "SKU no encontrado");
        continue;
    }
    $respuesta = $modelo->resolverCostoVigenteSku($idSku, array());
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $resultados[$sku] = array(
        "id_sku" => $idSku,
        "error" => !empty($respuesta["error"]),
        "costo" => isset($depurar["costo"]) ? $depurar["costo"] : null,
        "fuente" => isset($depurar["fuente"]) ? $depurar["fuente"] : null,
        "confianza" => isset($depurar["confianza"]) ? $depurar["confianza"] : null,
        "formula" => isset($depurar["formula"]) ? $depurar["formula"] : null,
        "sku_origen" => isset($depurar["sku_origen"]) ? $depurar["sku_origen"] : null,
        "advertencias" => isset($depurar["advertencias"]) ? $depurar["advertencias"] : array(),
        "rango" => isset($depurar["rango"]) ? $depurar["rango"] : null
    );
}

if (!isset($resultados["TP-40372-500GR"]) || $resultados["TP-40372-500GR"]["fuente"] !== "derivado_presentacion") {
    $fallas[] = array("id" => "COST-DER-UAT-002", "mensaje" => "TP-40372-500GR debe resolverse como derivado_presentacion");
}
if (isset($resultados["TP-40372-500GR"]) && abs(floatval($resultados["TP-40372-500GR"]["costo"]) - 92.133621) > 0.01) {
    $fallas[] = array("id" => "COST-DER-UAT-003", "mensaje" => "Costo derivado de TP-40372-500GR fuera de tolerancia");
}
if (!isset($resultados["NUEC-C20K-GRANEL"]) || $resultados["NUEC-C20K-GRANEL"]["fuente"] !== "sin_costo") {
    $fallas[] = array("id" => "COST-DER-UAT-004", "mensaje" => "Apertura sin costo debe quedar bloqueada como sin_costo");
}
if (!isset($resultados["PER-05-01"]) || $resultados["PER-05-01"]["rango"] === null) {
    $fallas[] = array("id" => "COST-DER-UAT-005", "mensaje" => "Paquete debe devolver rango de costo");
}

$auditoria = $modelo->auditarPendientesCostoDerivado(array("limite" => 50));

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas) && empty($auditoria["error"]),
    "modo" => "rentabilidad_resolutor_costos_derivados_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_precios" => true,
        "no_toca_ventas" => true
    ),
    "fallas" => $fallas,
    "resultados" => $resultados,
    "auditoria_pendientes" => isset($auditoria["depurar"]) ? $auditoria["depurar"]["resumen"] : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
