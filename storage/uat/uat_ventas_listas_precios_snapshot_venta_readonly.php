<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: validar snapshot de lista/precio en una venta POS UAT por folio.
 * Impacto: confirma que la venta historica conserva precio, lista y origen aplicados aunque cambie la lista.
 * Contrato: read-only; no modifica ventas, listas, inventario ni pagos.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idSku = 1760;
$codigoLista = "LP-UAT-BORRADOR-01";
$precioEsperado = 315.00;
$precioListaActualEsperado = null;
$origenEsperado = "lista_cliente";

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--codigo_lista=") === 0) {
        $codigoLista = strtoupper(trim(substr($arg, 15), "\"' "));
    } elseif (strpos($arg, "--precio_esperado=") === 0) {
        $precioEsperado = round(floatval(trim(substr($arg, 19), "\"' ")), 6);
    } elseif (strpos($arg, "--precio_lista_actual_esperado=") === 0) {
        $precioListaActualEsperado = round(floatval(trim(substr($arg, 31), "\"' ")), 6);
    } elseif (strpos($arg, "--origen_esperado=") === 0) {
        $origenEsperado = trim(substr($arg, 18), "\"' ");
    }
}

if ($folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "ventas_listas_precios_snapshot_venta_readonly",
        "read_only" => true,
        "mensaje" => "Indica --folio=FOLIO_VENTA_UAT para validar snapshot.",
        "entrada_esperada" => array(
            "id_sku" => $idSku,
            "codigo_lista" => $codigoLista,
            "precio_esperado" => $precioEsperado,
            "precio_lista_actual_esperado" => $precioListaActualEsperado,
            "origen_esperado" => $origenEsperado
        ),
        "contrato" => array(
            "no_escribe_bd" => true,
            "folio_obligatorio" => true
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/CRUD.php";
require_once "../app/modelos/VentasErp.php";

class VentasErpSnapshotListasReadonly extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpSnapshotListasReadonly();
$db = $ventas->conexionUat();
$venta = buscarVenta($db, $folio);
$detalle = $venta ? buscarDetalleVentaSku($db, intval($venta["id_venta"]), $idSku) : null;
$lista = buscarListaPorCodigo($db, $codigoLista);
$idListaEsperada = intval(valor($lista, "id_lista_precio", 0));
$detalleListaActual = $idListaEsperada > 0 ? buscarDetalleListaSku($db, $idListaEsperada, $idSku) : null;

$bloqueos = array();
if (!$venta) {
    $bloqueos[] = "No existe venta con folio indicado";
}
if (!$detalle) {
    $bloqueos[] = "No existe detalle de venta para SKU esperado";
}
if ($idListaEsperada <= 0) {
    $bloqueos[] = "No existe lista esperada por codigo";
}
if ($detalle) {
    if (intval(valor($detalle, "id_lista_precio", 0)) !== $idListaEsperada) {
        $bloqueos[] = "El detalle no guardo id_lista_precio esperado";
    }
    if (trim((string) valor($detalle, "lista_precio_snapshot", "")) === "") {
        $bloqueos[] = "El detalle no guardo lista_precio_snapshot";
    }
    if (valor($detalle, "regla_precio_origen", "") !== $origenEsperado) {
        $bloqueos[] = "El detalle no guardo regla_precio_origen esperada";
    }
    if (abs(floatval(valor($detalle, "precio_aplicado", 0)) - $precioEsperado) > 0.0001) {
        $bloqueos[] = "El detalle no guardo precio_aplicado esperado";
    }
}

$precioListaActual = $detalleListaActual ? floatval(valor($detalleListaActual, "precio", 0)) : null;
$precioVentaSnapshot = $detalle ? floatval(valor($detalle, "precio_aplicado", 0)) : null;
if ($precioListaActualEsperado !== null && $precioListaActual !== null && abs($precioListaActual - $precioListaActualEsperado) > 0.0001) {
    $bloqueos[] = "La lista actual no tiene el precio posterior esperado";
}

responder(array(
    "ok" => empty($bloqueos),
    "modo" => "ventas_listas_precios_snapshot_venta_readonly",
    "read_only" => true,
    "entrada" => array(
        "folio" => $folio,
        "id_sku" => $idSku,
        "codigo_lista" => $codigoLista,
        "id_lista_precio_esperada" => $idListaEsperada,
        "precio_esperado" => $precioEsperado,
        "precio_lista_actual_esperado" => $precioListaActualEsperado,
        "origen_esperado" => $origenEsperado
    ),
    "venta" => $venta,
    "detalle_venta" => $detalle,
    "lista_actual" => $lista,
    "detalle_lista_actual" => $detalleListaActual,
    "comparacion_snapshot" => array(
        "precio_venta_snapshot" => $precioVentaSnapshot,
        "precio_lista_actual" => $precioListaActual,
        "precio_lista_actual_esperado" => $precioListaActualEsperado,
        "snapshot_independiente_de_lista_actual" => $detalle && $detalleListaActual
            ? abs($precioVentaSnapshot - $precioListaActual) > 0.0001 || abs($precioVentaSnapshot - $precioEsperado) <= 0.0001
            : null
    ),
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "valida_detalle_no_encabezado" => true,
        "snapshot_detalle_es_fuente_historica" => true,
        "cambio_posterior_lista_no_debe_modificar_venta" => true
    ),
    "siguiente_paso" => empty($bloqueos)
        ? "Snapshot de venta validado; documentar folio UAT y cambio posterior de precio si aplica."
        : "Completar venta UAT real o revisar folio/SKU/lista esperados."
));

function buscarVenta($db, $folio) {
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas
        WHERE folio=:folio
        LIMIT 1");
    $stmt->execute(array(":folio" => $folio));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarDetalleVentaSku($db, $idVenta, $idSku) {
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas_detalle
        WHERE id_venta=:venta AND id_sku_erp=:sku
        ORDER BY id_venta_detalle DESC
        LIMIT 1");
    $stmt->execute(array(":venta" => intval($idVenta), ":sku" => intval($idSku)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarListaPorCodigo($db, $codigo) {
    $stmt = $db->prepare("SELECT *
        FROM erp_listas_precios
        WHERE codigo=:codigo
        LIMIT 1");
    $stmt->execute(array(":codigo" => $codigo));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function buscarDetalleListaSku($db, $idLista, $idSku) {
    $stmt = $db->prepare("SELECT *
        FROM erp_listas_precios_detalle
        WHERE id_lista_precio=:lista AND id_sku=:sku
        ORDER BY id_lista_precio_detalle DESC
        LIMIT 1");
    $stmt->execute(array(":lista" => intval($idLista), ":sku" => intval($idSku)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ? $fila : null;
}

function valor($datos, $clave, $default = null) {
    return is_array($datos) && array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(!empty($payload["ok"]) ? 0 : 1);
}
