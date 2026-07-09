<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

class UatRentabilidadRecomendacionesObsoletas extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$base = new UatRentabilidadRecomendacionesObsoletas();
$db = $base->db();
$modelo = new RentabilidadErp();

$stmt = $db->prepare("SELECT id_recomendacion, id_sku, sku, canal, precio_actual_sin_impuesto,
        precio_recomendado_sin_impuesto, motivo, estatus, fecha_registro
    FROM erp_rentabilidad_recomendaciones
    WHERE estatus='pendiente'
    ORDER BY id_recomendacion DESC");
$stmt->execute();

$obsoletas = array();
$vigentes = array();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pendiente) {
    $analisis = $modelo->analizarSkus(array("q" => $pendiente["sku"], "canal" => $pendiente["canal"]));
    $item = null;
    if (empty($analisis["error"]) && !empty($analisis["depurar"]["items"])) {
        foreach ($analisis["depurar"]["items"] as $candidato) {
            if ($candidato["sku"] === $pendiente["sku"]) {
                $item = $candidato;
                break;
            }
        }
    }
    $registro = array(
        "id_recomendacion" => intval($pendiente["id_recomendacion"]),
        "sku" => $pendiente["sku"],
        "canal" => $pendiente["canal"],
        "precio_recomendado_pendiente" => round(floatval($pendiente["precio_recomendado_sin_impuesto"]), 6),
        "motivo_pendiente" => $pendiente["motivo"],
        "riesgo_actual" => $item ? $item["riesgo_clave"] : "no_encontrado",
        "hallazgos_actuales" => $item ? $item["hallazgos"] : array(),
        "costo_actual" => $item ? $item["costo_real_sin_impuesto"] : null,
        "precio_actual" => $item ? $item["precio_escenario_sin_impuesto"] : null,
        "precio_minimo_actual" => $item ? $item["precio_minimo_rentable"] : null
    );
    $requierePrecio = $item && (
        in_array("perdida_estimada", $item["hallazgos"], true)
        || in_array("margen_bajo", $item["hallazgos"], true)
        || in_array("sin_precio", $item["hallazgos"], true)
    );
    if (!$requierePrecio) {
        $obsoletas[] = $registro;
    } else {
        $vigentes[] = $registro;
    }
}

echo json_encode(array(
    "ok" => true,
    "mensaje" => "Auditoria read-only de recomendaciones pendientes",
    "total_pendientes" => count($obsoletas) + count($vigentes),
    "obsoletas" => $obsoletas,
    "vigentes" => $vigentes,
    "siguiente_autorizacion" => "Cancelar recomendaciones obsoletas requiere respaldo y autorizacion explicita."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
