<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-23.
 * Proposito: ejecutar dry-run de confirmacion POS con partida de venta rapida controlada.
 * Impacto: valida prevalidacion/modelo sin crear venta, pagos, pendiente, caja, SKU ni inventario.
 * Contrato: read-only por contrato del metodo confirmarVentaPosDryRun.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$items = array(array(
    "tipo_partida" => "venta_rapida",
    "origen_partida" => "venta_rapida_controlada",
    "descripcion_manual" => "Producto UAT por clasificar modelo real",
    "descripcion" => "Producto UAT por clasificar modelo real",
    "cantidad" => 1,
    "precio_unitario" => 100,
    "motivo" => "UAT modelo real",
    "controla_inventario" => 1,
));

$pagos = array(array(
    "id_metodo_pago" => 1,
    "metodo_pago" => "efectivo",
    "monto" => 100,
));

$modelo = new VentasErp();
$respuesta = $modelo->confirmarVentaPosDryRun(array(
    "id_almacen" => 5,
    "id_caja" => 2,
    "id_turno_caja" => 0,
    "items" => json_encode($items),
    "pagos" => json_encode($pagos),
    "autorizar_venta_rapida_real" => "VENTAS_POS_VENTA_RAPIDA_REAL_MODELO",
));

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
