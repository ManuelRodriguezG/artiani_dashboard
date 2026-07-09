<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-01.
 * Proposito: consultar ultimas ventas POS para localizar folio UAT sin escribir BD.
 * Impacto: solo lectura; no crea ventas, pagos, caja ni kardex.
 * Contrato: read-only.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosUltimasDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosUltimasDb())->db();
$limite = 10;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--limite=") === 0) {
        $limite = max(1, min(50, intval(trim(substr($arg, 9), "\"' "))));
    }
}

$columnas = columnas($db, "erp_ventas");
$select = array();
foreach (array(
    "id_venta", "folio", "id_turno_caja", "id_caja", "id_almacen",
    "id_cliente_crm", "cliente_codigo_snapshot", "cliente_snapshot",
    "subtotal", "descuento_total", "total", "pagado_total", "saldo",
    "estatus", "fecha_registro"
) as $columna) {
    if (in_array($columna, $columnas, true)) {
        $select[] = "v.`" . $columna . "`";
    }
}
$sql = "SELECT " . implode(", ", $select) . ", COUNT(d.id_venta_detalle) partidas
    FROM erp_ventas v
    LEFT JOIN erp_ventas_detalle d ON d.id_venta=v.id_venta
    GROUP BY " . implode(", ", $select) . "
    ORDER BY v.id_venta DESC
    LIMIT " . intval($limite);

echo json_encode(array(
    "ok" => true,
    "modo" => "read-only",
    "ventas" => $db->query($sql)->fetchAll(PDO::FETCH_ASSOC)
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function columnas($db, $tabla) {
    $stmt = $db->query("SHOW COLUMNS FROM `" . str_replace("`", "", $tabla) . "`");
    return array_map(function ($item) {
        return $item["Field"];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));
}
