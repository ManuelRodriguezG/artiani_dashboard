<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-20.
 * Proposito: validar que una venta POS real UAT guardo snapshot de lista de precios en detalle.
 * Impacto: confirma que el resolutor backend llego a persistencia historica sin recalcular ventas pasadas.
 * Contrato: read-only; no modifica venta, pagos, inventario, listas, CRM ni ecommerce.
 *
 * Uso:
 * php storage/uat/uat_listas_precios_pos_venta_snapshot_readonly.php --folio=POS-... --id_lista_precio=2 --id_sku=1760 --origen_esperado=lista_segmento_cliente
 */

$params = argumentosSnapshot($argv);
$folio = trim((string) valorSnapshot($params, "folio", ""));
$idVenta = intval(valorSnapshot($params, "id_venta", 0));
$idListaEsperada = intval(valorSnapshot($params, "id_lista_precio", 0));
$idSkuEsperado = intval(valorSnapshot($params, "id_sku", 0));
$origenEsperado = trim((string) valorSnapshot($params, "origen_esperado", ""));

chdir(__DIR__ . "/../../public");
require_once "../app/config/mysql.php";

$db = null;
$dbError = "";
try {
    $db = new PDO("mysql:host=" . MYSQLHOST . ";dbname=" . MYSQLBASE . ";charset=utf8", MYSQLUSER, MYSQLPASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

$venta = $db ? buscarVentaSnapshot($db, $idVenta, $folio) : null;
$detalles = $venta ? detallesVentaSnapshot($db, intval($venta["id_venta"])) : array();
$detallesFiltrados = $idSkuEsperado > 0
    ? array_values(array_filter($detalles, function ($detalle) use ($idSkuEsperado) {
        return intval(valorSnapshot($detalle, "id_sku", 0)) === $idSkuEsperado || intval(valorSnapshot($detalle, "id_sku_erp", 0)) === $idSkuEsperado;
    }))
    : $detalles;

$checks = array(
    checkSnapshot("conexion_mysql", (bool) $db, "Conexion MySQL disponible"),
    checkSnapshot("referencia_venta", $idVenta > 0 || $folio !== "", "Se indico folio o id_venta"),
    checkSnapshot("venta_existe", (bool) $venta, "Venta consultable"),
    checkSnapshot("detalle_existe", count($detallesFiltrados) > 0, "Existe al menos una partida a validar"),
    checkSnapshot("snapshot_presente", detallesCumplenSnapshot($detallesFiltrados), "Partidas tienen id_lista_precio/lista_precio_snapshot/regla_precio_origen"),
    checkSnapshot("lista_esperada", $idListaEsperada <= 0 || detallesCumplenLista($detallesFiltrados, $idListaEsperada), "Lista esperada coincide si se indico"),
    checkSnapshot("origen_esperado", $origenEsperado === "" || detallesCumplenOrigen($detallesFiltrados, $origenEsperado), "Origen esperado coincide si se indico"),
    checkSnapshot("precio_persistido", detallesPrecioValido($detallesFiltrados), "Precio base/aplicado/unitario persistido mayor a cero")
);

$fallos = array_values(array_filter($checks, function ($check) {
    return !$check["ok"];
}));

echo json_encode(array(
    "ok" => empty($fallos),
    "modo" => "read-only",
    "resultado" => empty($fallos) ? "PASS_POS_VENTA_SNAPSHOT_LISTAS_PRECIOS" : "FAIL_POS_VENTA_SNAPSHOT_LISTAS_PRECIOS",
    "parametros" => array(
        "folio" => $folio,
        "id_venta" => $idVenta,
        "id_lista_precio" => $idListaEsperada,
        "id_sku" => $idSkuEsperado,
        "origen_esperado" => $origenEsperado
    ),
    "venta" => $venta,
    "detalles_validados" => array_map("detalleSnapshotResumen", $detallesFiltrados),
    "checks" => $checks,
    "fallos" => $fallos,
    "conexion_error" => $db ? "" : $dbError,
    "guardrails" => array(
        "no_escribe_bd" => true,
        "no_ejecuta_ddl" => true,
        "no_modifica_venta" => true,
        "no_mueve_inventario" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($fallos) ? 0 : 1);

function argumentosSnapshot($argv) {
    $params = array();
    foreach (array_slice($argv, 1) as $arg) {
        if (strpos($arg, "--") === 0 && strpos($arg, "=") !== false) {
            $partes = explode("=", substr($arg, 2), 2);
            $params[$partes[0]] = $partes[1];
        }
    }
    return $params;
}

function valorSnapshot($array, $key, $default = null) {
    return is_array($array) && array_key_exists($key, $array) ? $array[$key] : $default;
}

function checkSnapshot($id, $ok, $descripcion) {
    return array("id" => $id, "ok" => (bool) $ok, "descripcion" => $descripcion);
}

function buscarVentaSnapshot($db, $idVenta, $folio) {
    if (!tablaSnapshotExiste($db, "erp_ventas")) {
        return null;
    }
    $where = intval($idVenta) > 0 ? "id_venta=:ref" : "folio=:ref";
    $ref = intval($idVenta) > 0 ? intval($idVenta) : trim((string) $folio);
    if ($ref === "" || $ref === 0) {
        return null;
    }
    $stmt = $db->prepare("SELECT id_venta, folio, canal, estatus, id_almacen, id_cliente_crm, total, fecha_venta
        FROM erp_ventas
        WHERE $where
        LIMIT 1");
    $stmt->execute(array(":ref" => $ref));
    $venta = $stmt->fetch();
    return $venta ?: null;
}

function detallesVentaSnapshot($db, $idVenta) {
    if (!tablaSnapshotExiste($db, "erp_ventas_detalle") || intval($idVenta) <= 0) {
        return array();
    }
    $campoSku = columnaSnapshotExiste($db, "erp_ventas_detalle", "id_sku") ? "id_sku" : "id_sku_erp";
    $stmt = $db->prepare("SELECT id_venta_detalle, id_venta, renglon, $campoSku id_sku, $campoSku id_sku_erp, sku, descripcion,
            cantidad_venta, precio_unitario, precio_base, precio_aplicado, id_lista_precio,
            lista_precio_snapshot, regla_precio_origen, subtotal, total, estatus
        FROM erp_ventas_detalle
        WHERE id_venta=:venta
        ORDER BY renglon ASC, id_venta_detalle ASC");
    $stmt->execute(array(":venta" => intval($idVenta)));
    return $stmt->fetchAll();
}

function tablaSnapshotExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    return (bool) $stmt->fetch();
}

function columnaSnapshotExiste($db, $tabla, $columna) {
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetch();
}

function detallesCumplenSnapshot($detalles) {
    if (empty($detalles)) {
        return false;
    }
    foreach ($detalles as $detalle) {
        if (intval(valorSnapshot($detalle, "id_lista_precio", 0)) <= 0) {
            return false;
        }
        if (trim((string) valorSnapshot($detalle, "lista_precio_snapshot", "")) === "") {
            return false;
        }
        if (trim((string) valorSnapshot($detalle, "regla_precio_origen", "")) === "") {
            return false;
        }
    }
    return true;
}

function detallesCumplenLista($detalles, $idListaEsperada) {
    if (empty($detalles)) {
        return false;
    }
    foreach ($detalles as $detalle) {
        if (intval(valorSnapshot($detalle, "id_lista_precio", 0)) !== intval($idListaEsperada)) {
            return false;
        }
    }
    return true;
}

function detallesCumplenOrigen($detalles, $origenEsperado) {
    if (empty($detalles)) {
        return false;
    }
    foreach ($detalles as $detalle) {
        if (trim((string) valorSnapshot($detalle, "regla_precio_origen", "")) !== $origenEsperado) {
            return false;
        }
    }
    return true;
}

function detallesPrecioValido($detalles) {
    if (empty($detalles)) {
        return false;
    }
    foreach ($detalles as $detalle) {
        $precio = max(
            floatval(valorSnapshot($detalle, "precio_aplicado", 0)),
            floatval(valorSnapshot($detalle, "precio_unitario", 0)),
            floatval(valorSnapshot($detalle, "precio_base", 0))
        );
        if ($precio <= 0) {
            return false;
        }
    }
    return true;
}

function detalleSnapshotResumen($detalle) {
    return array(
        "id_venta_detalle" => intval(valorSnapshot($detalle, "id_venta_detalle", 0)),
        "id_sku" => intval(valorSnapshot($detalle, "id_sku", 0)),
        "sku" => valorSnapshot($detalle, "sku", ""),
        "precio_unitario" => floatval(valorSnapshot($detalle, "precio_unitario", 0)),
        "precio_base" => floatval(valorSnapshot($detalle, "precio_base", 0)),
        "precio_aplicado" => floatval(valorSnapshot($detalle, "precio_aplicado", 0)),
        "id_lista_precio" => intval(valorSnapshot($detalle, "id_lista_precio", 0)),
        "lista_precio_snapshot" => valorSnapshot($detalle, "lista_precio_snapshot", ""),
        "regla_precio_origen" => valorSnapshot($detalle, "regla_precio_origen", ""),
        "total" => floatval(valorSnapshot($detalle, "total", 0))
    );
}
