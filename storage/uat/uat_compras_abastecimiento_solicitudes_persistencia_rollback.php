<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: verificar persistencia de evidencia de abastecimiento en solicitudes con rollback.
 * Impacto: escribe dentro de una transaccion y revierte; no deja solicitud ni detalle de prueba.
 * Contrato: requiere que erp_compras_solicitudes_detalle.evidencia_costo_json exista.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";

class UatComprasAbastecimientoPersistenciaDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatComprasAbastecimientoPersistenciaDb())->db();

try {
    $relacion = $db->query("SELECT sp.id_proveedor, sp.id_sku, sp.id_sku_proveedor,
            COALESCE(NULLIF(TRIM(sp.sku_proveedor), ''), s.sku) sku,
            s.nombre
        FROM erp_catalogo_sku_proveedores sp
        INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku
        WHERE sp.estatus='activo' AND s.estatus='activo'
        ORDER BY sp.id_sku_proveedor DESC
        LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$relacion) {
        throw new Exception("No hay relacion proveedor-SKU activa para probar");
    }

    $evidencia = array(
        "origen" => "uat_rollback_abastecimiento_solicitudes",
        "id_proveedor" => intval($relacion["id_proveedor"]),
        "id_sku_erp" => intval($relacion["id_sku"]),
        "id_sku_proveedor" => intval($relacion["id_sku_proveedor"]),
        "sku_erp" => (string) $relacion["sku"],
        "costo_capturado" => 1.23,
        "criterio" => "prueba_transaccional_rollback"
    );

    $db->beginTransaction();
    $db->prepare("INSERT INTO erp_compras_solicitudes
        (id_proveedor, folio, estatus, observaciones, fecha_solicitud, prioridad, subtotal_estimado)
        VALUES (:proveedor, 'UAT-ROLLBACK', 'borrador', 'UAT rollback abastecimiento', NOW(), 'normal', 1.23)")
        ->execute(array(":proveedor" => intval($relacion["id_proveedor"])));
    $idSolicitud = intval($db->lastInsertId());

    $db->prepare("INSERT INTO erp_compras_solicitudes_detalle
        (id_solicitud, id_sku_erp, id_sku_proveedor, sku, nombre_producto, cantidad,
        costo_estimado, subtotal, observaciones, evidencia_costo_json)
        VALUES (:solicitud, :sku, :relacion, :sku_texto, :nombre, 1, 1.23, 1.23,
        'UAT rollback', :evidencia)")
        ->execute(array(
            ":solicitud" => $idSolicitud,
            ":sku" => intval($relacion["id_sku"]),
            ":relacion" => intval($relacion["id_sku_proveedor"]),
            ":sku_texto" => (string) $relacion["sku"],
            ":nombre" => (string) $relacion["nombre"],
            ":evidencia" => json_encode($evidencia, JSON_UNESCAPED_UNICODE)
        ));

    $stmt = $db->prepare("SELECT evidencia_costo_json
        FROM erp_compras_solicitudes_detalle
        WHERE id_solicitud=:solicitud
        LIMIT 1");
    $stmt->execute(array(":solicitud" => $idSolicitud));
    $leido = $stmt->fetchColumn();
    $db->rollBack();

    $jsonLeido = json_decode($leido, true);
    responder(array(
        "ok" => is_array($jsonLeido) && isset($jsonLeido["id_sku_proveedor"]),
        "modo" => "rollback",
        "mensaje" => "Persistencia transaccional de evidencia validada y revertida",
        "id_solicitud_temporal" => $idSolicitud,
        "rollback_ejecutado" => true,
        "id_sku_proveedor_probado" => intval($relacion["id_sku_proveedor"]),
        "evidencia_leida" => $jsonLeido
    ), 0);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage(),
        "rollback_ejecutado" => true
    ), 1);
}

function responder($payload, $codigo) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit($codigo);
}
