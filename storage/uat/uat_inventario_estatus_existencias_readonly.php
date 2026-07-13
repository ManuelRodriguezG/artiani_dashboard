<?php
/**
 * Version IA: GPT-5 Codex
 * Fecha: 2026-07-12
 * Proposito: auditar inconsistencias entre cantidades y estatus de existencias ERP sin escribir BD.
 * Impacto: lectura operativa para UAT POS/Inventario; no normaliza estatus, no crea kardex, no modifica cantidades.
 * Contrato: read-only. La normalizacion real requiere autorizacion externa, respaldo vigente y script/apply separado.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatInventarioEstatusExistenciasReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatArg($nombre, $default = null) {
    global $argv;
    $prefix = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function uatOut($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

try {
    $db = new UatInventarioEstatusExistenciasReadonlyDb();
    $pdo = $db->db();
    $idAlmacen = intval(uatArg("id_almacen", 0));
    $idSku = intval(uatArg("id_sku", 0));
    $limit = intval(uatArg("limit", 50));
    if ($limit <= 0 || $limit > 200) {
        $limit = 50;
    }

    $tablas = $pdo->query("SHOW TABLES LIKE 'erp_inventario_existencias'")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tablas)) {
        uatOut(array(
            "ok" => false,
            "read_only" => true,
            "mensaje" => "No existe tabla erp_inventario_existencias"
        ));
        exit(0);
    }

    $where = array("1=1");
    $params = array();
    if ($idAlmacen > 0) {
        $where[] = "id_almacen = :id_almacen";
        $params[":id_almacen"] = $idAlmacen;
    }
    if ($idSku > 0) {
        $where[] = "id_sku_erp = :id_sku";
        $params[":id_sku"] = $idSku;
    }
    $whereSql = implode(" AND ", $where);

    $casos = array(
        "disponible_marcado_agotado" => "cantidad > 0 AND cantidad_disponible > 0 AND estatus_existencia = 'agotada'",
        "sin_saldo_no_agotado" => "cantidad <= 0 AND cantidad_disponible <= 0 AND cantidad_apartada <= 0 AND estatus_existencia <> 'agotada'",
        "disponible_negativo" => "cantidad_disponible < 0",
        "apartado_mayor_cantidad" => "cantidad_apartada > cantidad"
    );
    $conteos = array();
    $muestras = array();

    foreach ($casos as $clave => $condicion) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM erp_inventario_existencias WHERE {$whereSql} AND {$condicion}");
        $stmt->execute($params);
        $conteos[$clave] = intval($stmt->fetchColumn());

        $stmt = $pdo->prepare("SELECT id_existencia_inventario, codigo_existencia, id_almacen, id_sku_erp,
                cantidad, cantidad_disponible, cantidad_apartada, estatus_existencia, ultimo_movimiento_id, fecha_actualizacion
            FROM erp_inventario_existencias
            WHERE {$whereSql} AND {$condicion}
            ORDER BY fecha_actualizacion DESC, id_existencia_inventario DESC
            LIMIT {$limit}");
        $stmt->execute($params);
        $muestras[$clave] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $total = array_sum($conteos);
    uatOut(array(
        "ok" => true,
        "read_only" => true,
        "script" => basename(__FILE__),
        "filtros" => array(
            "id_almacen" => $idAlmacen,
            "id_sku" => $idSku,
            "limit" => $limit
        ),
        "total_inconsistencias" => $total,
        "conteos" => $conteos,
        "muestras" => $muestras,
        "siguiente_autorizacion_sugerida" => $total > 0
            ? "AUTORIZO NORMALIZAR ESTATUS EXISTENCIAS INVENTARIO UAT usando respaldo UAT POS vigente con token INVENTARIO_ESTATUS_EXISTENCIAS_NORMALIZAR confirmacion=\"NORMALIZAR ESTATUS\""
            : null
    ));
} catch (Throwable $e) {
    uatOut(array(
        "ok" => false,
        "read_only" => true,
        "mensaje" => $e->getMessage()
    ));
}
