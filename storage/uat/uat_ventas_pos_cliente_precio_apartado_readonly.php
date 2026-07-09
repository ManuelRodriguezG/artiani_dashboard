<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar contratos read-only de cliente/precio y abonos de apartado.
 * Impacto: prepara POS robusto sin crear clientes, listas, ventas, pagos ni reservas.
 * Contrato: no escribe BD; solo invoca dry-runs del modelo VentasErp.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 4;
$idSku = 1113;
$identificador = "5550000000";
foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$items = json_encode(array(array(
    "id_sku" => $idSku,
    "cantidad" => 1,
    "modo_salida" => "existencia_agregada"
)));

$clientePrecio = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "items" => $items
));

$abono = $ventas->apartadoAbonoDryRun(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "folio" => "APT-UAT-000001",
    "monto_abono" => 100,
    "id_metodo_pago" => 1,
    "referencia" => "UAT"
));

$siguientePaso = "Autorizar flujo real de alta/abonos o ejecutar dry-run con caja y turno validos.";
if (!empty($clientePrecio["depurar"]["schema_clientes_pendiente"]) || !empty($clientePrecio["depurar"]["schema_listas_precios_pendiente"])) {
    $siguientePaso = "Crear semillas UAT de clientes/listas/politicas o autorizar flujo real de alta/abonos.";
}

echo json_encode(array(
    "ok" => !$clientePrecio["error"] && !$abono["error"],
    "modo" => "read-only",
    "cliente_precio" => $clientePrecio,
    "abono_apartado" => $abono,
    "siguiente_paso" => $siguientePaso
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
