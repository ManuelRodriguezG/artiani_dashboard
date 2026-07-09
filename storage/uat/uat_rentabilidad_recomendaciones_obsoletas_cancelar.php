<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

class UatRentabilidadRecomendacionesObsoletasCancelar extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$execute = in_array("--execute", $argv, true);
$base = new UatRentabilidadRecomendacionesObsoletasCancelar();
$db = $base->db();
$rentabilidad = new RentabilidadErp();

try {
    $stmt = $db->prepare("SELECT id_recomendacion, sku, canal, motivo
        FROM erp_rentabilidad_recomendaciones
        WHERE estatus='pendiente'
        ORDER BY id_recomendacion DESC");
    $stmt->execute();

    $obsoletas = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $pendiente) {
        $analisis = $rentabilidad->analizarSkus(array("q" => $pendiente["sku"], "canal" => $pendiente["canal"]));
        $item = null;
        if (empty($analisis["error"]) && !empty($analisis["depurar"]["items"])) {
            foreach ($analisis["depurar"]["items"] as $candidato) {
                if ($candidato["sku"] === $pendiente["sku"]) {
                    $item = $candidato;
                    break;
                }
            }
        }
        $requierePrecio = $item && (
            in_array("perdida_estimada", $item["hallazgos"], true)
            || in_array("margen_bajo", $item["hallazgos"], true)
            || in_array("sin_precio", $item["hallazgos"], true)
        );
        if (!$requierePrecio) {
            $obsoletas[] = array(
                "id_recomendacion" => intval($pendiente["id_recomendacion"]),
                "sku" => $pendiente["sku"],
                "canal" => $pendiente["canal"],
                "motivo_anterior" => $pendiente["motivo"],
                "riesgo_actual" => $item ? $item["riesgo_clave"] : "no_encontrado",
                "hallazgos_actuales" => $item ? $item["hallazgos"] : array()
            );
        }
    }

    $actualizadas = 0;
    if ($execute && !empty($obsoletas)) {
        $db->beginTransaction();
        $update = $db->prepare("UPDATE erp_rentabilidad_recomendaciones
            SET estatus='cancelada',
                comentario=CONCAT(COALESCE(comentario,''), :comentario),
                fecha_resolucion=NOW()
            WHERE id_recomendacion=:id AND estatus='pendiente'");
        foreach ($obsoletas as $item) {
            $update->execute(array(
                ":comentario" => "\nCancelacion tecnica: recomendacion obsoleta tras recalcular costo/rentabilidad. Riesgo actual: " . $item["riesgo_actual"] . ".",
                ":id" => $item["id_recomendacion"]
            ));
            $actualizadas += $update->rowCount();
        }
        $db->commit();
    }

    echo json_encode(array(
        "ok" => true,
        "execute" => $execute,
        "obsoletas_detectadas" => count($obsoletas),
        "actualizadas" => $actualizadas,
        "items" => $obsoletas
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Exception $e) {
    if ($execute && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(array(
        "ok" => false,
        "execute" => $execute,
        "mensaje" => $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}
