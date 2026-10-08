<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-10-07
 * Proposito: documento imprimible de Sugerido de compra para surtido/revision operativa.
 * Impacto: Compras/Sugerido; solo presenta informacion guardada, no modifica BD ni inventario.
 * Contrato: el navegador puede imprimir o guardar como PDF; costos se muestran solo con `?costos=1`.
 * Actualizacion IA: Codex GPT-5 | Fecha: 2026-10-07
 * Impacto: permite seleccionar columnas visibles antes de imprimir, sin guardar configuracion ni tocar BD.
 */
$sugerido = isset($datos["sugerido"]) && is_array($datos["sugerido"]) ? $datos["sugerido"] : array();
$detalle = isset($datos["detalle"]) && is_array($datos["detalle"]) ? $datos["detalle"] : array();
$idSugerido = isset($datos["id_sugerido_compra"]) ? intval($datos["id_sugerido_compra"]) : 0;
$errorImprimir = isset($datos["error_imprimir"]) ? trim((string) $datos["error_imprimir"]) : "";
$mostrarCostos = !empty($datos["mostrar_costos"]);
$folio = isset($sugerido["folio"]) ? trim((string) $sugerido["folio"]) : "";
$proveedor = isset($sugerido["proveedor"]) ? trim((string) $sugerido["proveedor"]) : "";
$estatus = isset($sugerido["estatus"]) ? trim((string) $sugerido["estatus"]) : "";
$observaciones = isset($sugerido["observaciones"]) ? trim((string) $sugerido["observaciones"]) : "";
$fechaRegistro = isset($sugerido["fecha_registro"]) ? trim((string) $sugerido["fecha_registro"]) : "";
$fechaActualizacion = isset($sugerido["fecha_actualizacion"]) ? trim((string) $sugerido["fecha_actualizacion"]) : "";
$totalSolicitar = 0;
$totalSugerido = 0;
$totalExistencia = 0;
$totalCompra = 0;
$totalInventario = 0;
$totalVenta = 0;
foreach ($detalle as $item) {
    $cantidadSolicitar = floatval($item["cantidad_solicitar"] ?? 0);
    $cantidadSugerida = floatval($item["cantidad_sugerida"] ?? 0);
    $existencia = floatval($item["existencia_revisada"] ?? 0);
    $costo = floatval($item["costo_estimado"] ?? 0);
    $precioVenta = floatval($item["precio_venta_estimado"] ?? 0);
    $totalSolicitar += $cantidadSolicitar;
    $totalSugerido += $cantidadSugerida;
    $totalExistencia += $existencia;
    $totalCompra += $cantidadSolicitar * $costo;
    $totalInventario += $existencia * $costo;
    $totalVenta += $cantidadSolicitar * $precioVenta;
}
$urlBase = "/compra/sugerido_imprimir_erp/" . $idSugerido;
$urlVolver = $idSugerido > 0 ? "/compra/ver_sugerido_compra/" . $idSugerido : "/compra/mostrar_sugeridos_compra";
function sugerido_imprimir_img($url) {
    $url = trim((string) $url);
    if ($url === "") {
        return "";
    }
    if (preg_match("/^(https?:)?\/\//i", $url) || strpos($url, "/") === 0 || strpos($url, "data:") === 0) {
        return $url;
    }
    return "/" . $url;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sugerido de compra <?= htmlspecialchars($folio ?: ("#" . $idSugerido)) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin: 22px; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 18px; }
        .toolbar a { text-decoration: none; color: #334155; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; }
        .toolbar a.btn-print { background: #0d6efd; color: #fff; border-color: #0d6efd; }
        .toolbar a.btn-costos { background: #f8fafc; }
        .print-options { border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; background: #fff; }
        .print-options-title { color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .print-options-grid { display: flex; flex-wrap: wrap; gap: 8px 16px; align-items: center; }
        .print-options label { display: inline-flex; gap: 6px; align-items: center; color: #334155; font-size: 13px; }
        .is-hidden-column { display: none !important; }
        .document-header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #334155; padding-bottom: 14px; margin-bottom: 16px; }
        h1 { font-size: 1.35rem; margin: 0 0 4px 0; }
        .subtitle, .muted { color: #64748b; }
        .meta { text-align: right; color: #475569; font-size: 0.9rem; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #f1f5f9; font-weight: 700; }
        .info { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px 18px; margin-bottom: 14px; font-size: 0.94rem; }
        .label { color: #64748b; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 12px; margin-bottom: 14px; }
        .grid th, .grid td { border: 1px solid #e2e8f0; padding: 7px; text-align: left; vertical-align: middle; }
        .grid th { background: #f8fafc; font-size: 11px; text-transform: uppercase; letter-spacing: 0.02em; }
        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .nowrap { white-space: nowrap; }
        .product { display: flex; gap: 8px; align-items: center; min-width: 240px; }
        .thumb { width: 42px; height: 42px; border: 1px solid #e2e8f0; border-radius: 6px; object-fit: cover; background: #f8fafc; flex: 0 0 auto; }
        .thumb-empty { width: 42px; height: 42px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; color: #94a3b8; display: flex; align-items: center; justify-content: center; font-size: 11px; flex: 0 0 auto; }
        .small { font-size: 0.8rem; color: #64748b; }
        .totales { display: flex; justify-content: flex-end; margin-top: 8px; }
        .totales .box { width: 330px; }
        .box .line { display: flex; justify-content: space-between; gap: 20px; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        .box .line:last-child { font-weight: 700; border-bottom: 0; border-top: 2px solid #94a3b8; margin-top: 6px; padding-top: 9px; }
        .firma { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 32px; }
        .firma div { border-top: 1px solid #94a3b8; text-align: center; color: #64748b; padding-top: 8px; }
        @media print {
            .toolbar, .print-options { display: none; }
            body { margin: 10px; font-size: 12px; }
            .grid th, .grid td { padding: 5px; }
            .thumb, .thumb-empty { width: 34px; height: 34px; }
            .document-header { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Sugerido de compra</h1>
            <div class="subtitle">Documento operativo para surtido o revision fisica, sin afectar inventario.</div>
        </div>
        <div>
            <a href="<?= htmlspecialchars($urlVolver) ?>">Volver</a>
            <a class="btn-costos" href="<?= htmlspecialchars($urlBase . ($mostrarCostos ? "" : "?costos=1")) ?>"><?= $mostrarCostos ? "Sin costos" : "Con costos" ?></a>
            <a class="btn-print" href="#" onclick="window.print(); return false;">Imprimir / PDF</a>
        </div>
    </div>

    <div class="print-options">
        <div class="print-options-title">Columnas a imprimir</div>
        <div class="print-options-grid">
            <label><input type="checkbox" data-col-toggle="numero" checked> #</label>
            <label><input type="checkbox" data-col-toggle="sku" checked> SKU proveedor</label>
            <label><input type="checkbox" data-col-toggle="producto" checked> Producto</label>
            <label><input type="checkbox" data-col-toggle="minimo" checked> Min</label>
            <label><input type="checkbox" data-col-toggle="maximo" checked> Max</label>
            <label><input type="checkbox" data-col-toggle="existencia" checked> Existencia</label>
            <label><input type="checkbox" data-col-toggle="sugerido" checked> Sugerido</label>
            <label><input type="checkbox" data-col-toggle="surtir" checked> A surtir</label>
            <?php if ($mostrarCostos): ?>
                <label><input type="checkbox" data-col-toggle="costo" checked> Costo</label>
                <label><input type="checkbox" data-col-toggle="importe" checked> Importe</label>
            <?php endif; ?>
            <label><input type="checkbox" data-col-toggle="venta-precio" checked> Precio venta</label>
            <label><input type="checkbox" data-col-toggle="venta-valor" checked> Valor venta</label>
            <label><input type="checkbox" data-col-toggle="observaciones" checked> Observaciones</label>
        </div>
    </div>

    <?php if ($errorImprimir): ?>
        <p class="muted"><?= htmlspecialchars($errorImprimir) ?></p>
    <?php elseif (empty($sugerido)): ?>
        <p class="muted">Sugerido de compra no disponible.</p>
    <?php else: ?>
        <div class="document-header">
            <div>
                <h1>Sugerido de compra</h1>
                <div class="muted">Usar como guia de surtido/revision. No modifica inventario ni kardex.</div>
            </div>
            <div class="meta">
                <div><strong><?= htmlspecialchars($folio ?: "SUG-PENDIENTE") ?></strong></div>
                <div>Generado: <?= htmlspecialchars(date("Y-m-d H:i")) ?></div>
                <div>Estado: <span class="status"><?= htmlspecialchars($estatus ?: "-") ?></span></div>
            </div>
        </div>

        <div class="info">
            <div><span class="label">Folio:</span> <strong><?= htmlspecialchars($folio ?: "-") ?></strong></div>
            <div><span class="label">Proveedor:</span> <?= htmlspecialchars($proveedor ?: "-") ?></div>
            <div><span class="label">Fecha registro:</span> <?= htmlspecialchars($fechaRegistro ?: "-") ?></div>
            <div><span class="label">Ultima actualizacion:</span> <?= htmlspecialchars($fechaActualizacion ?: "-") ?></div>
            <div><span class="label">Partidas:</span> <?= count($detalle) ?></div>
            <div><span class="label">Cantidad a surtir:</span> <?= htmlspecialchars(number_format($totalSolicitar, 6, ".", ",")) ?></div>
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <th class="text-center col-numero">#</th>
                    <th class="col-sku">SKU proveedor</th>
                    <th class="col-producto">Producto</th>
                    <th class="text-end col-minimo">Min</th>
                    <th class="text-end col-maximo">Max</th>
                    <th class="text-end col-existencia">Existencia</th>
                    <th class="text-end col-sugerido">Sugerido</th>
                    <th class="text-end col-surtir">A surtir</th>
                    <?php if ($mostrarCostos): ?>
                        <th class="text-end col-costo">Costo</th>
                        <th class="text-end col-importe">Importe</th>
                    <?php endif; ?>
                    <th class="text-end col-venta-precio">Precio venta</th>
                    <th class="text-end col-venta-valor">Valor venta</th>
                    <th class="col-observaciones">Obs.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalle)): ?>
                    <tr><td colspan="<?= $mostrarCostos ? 13 : 11 ?>" class="text-center muted">Sin partidas</td></tr>
                <?php else: ?>
                    <?php foreach ($detalle as $i => $d): ?>
                        <?php
                        $cantidadSolicitar = floatval($d["cantidad_solicitar"] ?? 0);
                        $costo = floatval($d["costo_estimado"] ?? 0);
                        $precioVenta = floatval($d["precio_venta_estimado"] ?? 0);
                        $img = sugerido_imprimir_img($d["imagen_portada"] ?? "");
                        ?>
                        <tr>
                            <td class="text-center nowrap col-numero"><?= intval($i + 1) ?></td>
                            <td class="nowrap col-sku">
                                <strong><?= htmlspecialchars($d["sku_proveedor"] ?? "-") ?></strong>
                                <div class="small">ERP: <?= htmlspecialchars($d["sku_erp"] ?? "-") ?></div>
                            </td>
                            <td class="col-producto">
                                <div class="product">
                                    <?php if ($img !== ""): ?>
                                        <img class="thumb" src="<?= htmlspecialchars($img) ?>" alt="">
                                    <?php else: ?>
                                        <div class="thumb-empty">IMG</div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($d["nombre_proveedor"] ?? ($d["nombre_erp"] ?? "-")) ?></strong>
                                        <div class="small"><?= htmlspecialchars($d["unidad_compra"] ?? "") ?> | factor <?= htmlspecialchars(number_format(floatval($d["factor_conversion"] ?? 1), 6, ".", ",")) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end nowrap col-minimo"><?= htmlspecialchars(number_format(floatval($d["stock_minimo"] ?? 0), 2, ".", ",")) ?></td>
                            <td class="text-end nowrap col-maximo"><?= ($d["stock_maximo"] ?? null) === null ? "-" : htmlspecialchars(number_format(floatval($d["stock_maximo"]), 2, ".", ",")) ?></td>
                            <td class="text-end nowrap col-existencia"><?= htmlspecialchars(number_format(floatval($d["existencia_revisada"] ?? 0), 6, ".", ",")) ?></td>
                            <td class="text-end nowrap col-sugerido"><?= htmlspecialchars(number_format(floatval($d["cantidad_sugerida"] ?? 0), 6, ".", ",")) ?></td>
                            <td class="text-end nowrap col-surtir"><strong><?= htmlspecialchars(number_format($cantidadSolicitar, 6, ".", ",")) ?></strong></td>
                            <?php if ($mostrarCostos): ?>
                                <td class="text-end nowrap col-costo">$<?= htmlspecialchars(number_format($costo, 2, ".", ",")) ?></td>
                                <td class="text-end nowrap col-importe">$<?= htmlspecialchars(number_format($cantidadSolicitar * $costo, 2, ".", ",")) ?></td>
                            <?php endif; ?>
                            <td class="text-end nowrap col-venta-precio">$<?= htmlspecialchars(number_format($precioVenta, 2, ".", ",")) ?></td>
                            <td class="text-end nowrap col-venta-valor">$<?= htmlspecialchars(number_format($cantidadSolicitar * $precioVenta, 2, ".", ",")) ?></td>
                            <td class="col-observaciones"><?= htmlspecialchars($d["observaciones"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totales">
            <div class="box">
                <div class="line"><span>Cantidad revisada</span><span><?= htmlspecialchars(number_format($totalExistencia, 6, ".", ",")) ?></span></div>
                <div class="line"><span>Cantidad sugerida</span><span><?= htmlspecialchars(number_format($totalSugerido, 6, ".", ",")) ?></span></div>
                <div class="line"><span>Total a surtir</span><span><?= htmlspecialchars(number_format($totalSolicitar, 6, ".", ",")) ?></span></div>
                <?php if ($mostrarCostos): ?>
                    <div class="line"><span>Inventario revisado estimado</span><span>$<?= htmlspecialchars(number_format($totalInventario, 2, ".", ",")) ?></span></div>
                    <div class="line"><span>Compra sugerida estimada</span><span>$<?= htmlspecialchars(number_format($totalCompra, 2, ".", ",")) ?></span></div>
                <?php endif; ?>
                <div class="line"><span>Venta aproximada</span><span>$<?= htmlspecialchars(number_format($totalVenta, 2, ".", ",")) ?></span></div>
            </div>
        </div>

        <?php if ($observaciones !== ""): ?>
            <p class="muted">Observaciones: <?= htmlspecialchars($observaciones) ?></p>
        <?php endif; ?>

        <div class="firma">
            <div>Reviso / preparo</div>
            <div>Recibio indicaciones</div>
        </div>
    <?php endif; ?>
    <script>
        (function () {
            function aplicarColumna(nombre, visible) {
                document.querySelectorAll(".col-" + nombre).forEach(function (nodo) {
                    nodo.classList.toggle("is-hidden-column", !visible);
                });
            }
            document.querySelectorAll("[data-col-toggle]").forEach(function (input) {
                input.addEventListener("change", function () {
                    aplicarColumna(input.getAttribute("data-col-toggle"), input.checked);
                });
            });
        })();
    </script>
</body>
</html>
