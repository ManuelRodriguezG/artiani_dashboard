<?php
/**
 * Version IA: GPT-5 Codex
 * Fecha: 2026-07-12
 * Proposito: normalizar estatus de existencias ERP cuando el saldo y el estatus no coinciden.
 * Impacto: actualiza solo `estatus_existencia` y `fecha_actualizacion`; no modifica cantidades ni crea kardex.
 * Contrato: requiere token, respaldo vigente y confirmacion textual. Usar despues de auditoria read-only.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatInventarioEstatusExistenciasApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

function uatArgApply($nombre, $default = null) {
    global $argv;
    $prefix = "--" . $nombre . "=";
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function uatOutApply($payload) {
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

try {
    $token = trim((string) uatArgApply("token", ""));
    $respaldo = trim((string) uatArgApply("respaldo", ""));
    $confirmacion = trim((string) uatArgApply("confirmacion", ""));
    $idAlmacen = intval(uatArgApply("id_almacen", 0));
    $idSku = intval(uatArgApply("id_sku", 0));

    if ($token !== "INVENTARIO_ESTATUS_EXISTENCIAS_NORMALIZAR") {
        throw new Exception("Token invalido para normalizar estatus de existencias");
    }
    if ($respaldo === "") {
        throw new Exception("Respaldo vigente obligatorio");
    }
    if ($confirmacion !== "NORMALIZAR ESTATUS") {
        throw new Exception("Confirmacion textual requerida: NORMALIZAR ESTATUS");
    }

    $db = new UatInventarioEstatusExistenciasApplyDb();
    $pdo = $db->db();

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

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id_existencia_inventario, codigo_existencia, id_almacen, id_sku_erp,
            cantidad, cantidad_disponible, cantidad_apartada, estatus_existencia
        FROM erp_inventario_existencias
        WHERE {$whereSql}
          AND (
            (cantidad > 0 AND cantidad_disponible > 0 AND estatus_existencia = 'agotada')
            OR (cantidad <= 0 AND cantidad_disponible <= 0 AND cantidad_apartada <= 0 AND estatus_existencia <> 'agotada')
          )
        ORDER BY id_existencia_inventario
        FOR UPDATE");
    $stmt->execute($params);
    $antes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $disponibles = 0;
    $agotadas = 0;
    foreach ($antes as $row) {
        $id = intval($row["id_existencia_inventario"]);
        $nuevo = (floatval($row["cantidad"]) > 0 && floatval($row["cantidad_disponible"]) > 0) ? "disponible" : "agotada";
        if ($nuevo === "disponible") {
            $disponibles++;
        } else {
            $agotadas++;
        }
        $upd = $pdo->prepare("UPDATE erp_inventario_existencias
            SET estatus_existencia=:estatus, fecha_actualizacion=NOW()
            WHERE id_existencia_inventario=:id");
        $upd->execute(array(":estatus" => $nuevo, ":id" => $id));
    }

    $ids = array_map(function ($row) {
        return intval($row["id_existencia_inventario"]);
    }, $antes);
    $despues = array();
    if (!empty($ids)) {
        $in = implode(",", array_fill(0, count($ids), "?"));
        $stmt = $pdo->prepare("SELECT id_existencia_inventario, codigo_existencia, id_almacen, id_sku_erp,
                cantidad, cantidad_disponible, cantidad_apartada, estatus_existencia, fecha_actualizacion
            FROM erp_inventario_existencias
            WHERE id_existencia_inventario IN ({$in})
            ORDER BY id_existencia_inventario");
        $stmt->execute($ids);
        $despues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $pdo->commit();
    uatOutApply(array(
        "ok" => true,
        "aplicado" => true,
        "token" => $token,
        "respaldo" => $respaldo,
        "filtros" => array("id_almacen" => $idAlmacen, "id_sku" => $idSku),
        "actualizados_total" => count($antes),
        "actualizados_a_disponible" => $disponibles,
        "actualizados_a_agotada" => $agotadas,
        "antes" => $antes,
        "despues" => $despues,
        "nota" => "No se modificaron cantidades ni se creo kardex; solo se normalizo estatus derivado del saldo."
    ));
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    uatOutApply(array(
        "ok" => false,
        "aplicado" => false,
        "mensaje" => $e->getMessage()
    ));
}
