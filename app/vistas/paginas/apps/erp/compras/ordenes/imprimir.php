<?php
$orden = isset($datos["orden"]) && is_array($datos["orden"]) ? $datos["orden"] : array();
$detalle = isset($datos["detalle"]) && is_array($datos["detalle"]) ? $datos["detalle"] : array();
$idOrden = isset($datos["id_orden_compra"]) ? intval($datos["id_orden_compra"]) : 0;
$errorImprimir = isset($datos["error_imprimir"]) ? trim((string)$datos["error_imprimir"]) : "";
$plantilla = isset($datos["plantilla"]) && is_array($datos["plantilla"]) ? $datos["plantilla"] : array();
$esProveedor = (isset($plantilla["audiencia"]) ? $plantilla["audiencia"] : "interna") === "proveedor";
$mostrarLogo = intval($plantilla["mostrar_logo"] ?? 1) === 1;
$logoRuta = trim((string)($plantilla["logo_ruta"] ?? ""));
$mostrarCostos = intval($plantilla["mostrar_costos"] ?? 1) === 1;
$mostrarTotales = intval($plantilla["mostrar_totales"] ?? 1) === 1;
$mostrarSkuErp = intval($plantilla["mostrar_sku_erp"] ?? 1) === 1;
$mostrarSkuProveedor = intval($plantilla["mostrar_sku_proveedor"] ?? 1) === 1;
$mostrarNombreErp = intval($plantilla["mostrar_nombre_erp"] ?? 1) === 1;
$mostrarNombreProveedor = intval($plantilla["mostrar_nombre_proveedor"] ?? 1) === 1;
$mostrarObservacionesInternas = intval($plantilla["mostrar_observaciones_internas"] ?? 1) === 1;
$mostrarObservacionesPublicas = intval($plantilla["mostrar_observaciones_publicas"] ?? 1) === 1;
$tituloDocumento = $esProveedor ? "Orden de compra para proveedor" : "Orden de compra interna";
$folio = isset($orden["folio"]) ? $orden["folio"] : "";
$folioProveedor = isset($orden["folio_proveedor"]) ? $orden["folio_proveedor"] : "";
$proveedor = isset($orden["proveedor"]) ? $orden["proveedor"] : "";
$almacen = isset($orden["almacen"]) ? $orden["almacen"] : "";
$estatus = isset($orden["estatus"]) ? $orden["estatus"] : "";
$fechaOrden = isset($orden["fecha_orden"]) ? $orden["fecha_orden"] : "";
$fechaEntrega = isset($orden["fecha_entrega_estimada"]) ? $orden["fecha_entrega_estimada"] : "";
$observaciones = isset($orden["observaciones"]) ? $orden["observaciones"] : "";
$subtotal = floatval($orden["subtotal"] ?? 0);
$impuestos = floatval($orden["impuestos"] ?? 0);
$total = floatval($orden["total"] ?? 0);
$colspanDetalle = 1 + ($mostrarSkuProveedor ? 1 : 0) + ($mostrarSkuErp ? 1 : 0) +
    ($mostrarNombreProveedor ? 1 : 0) + ($mostrarNombreErp ? 1 : 0) +
    ($mostrarCostos ? 4 : 0) + (($mostrarObservacionesInternas || $mostrarObservacionesPublicas) ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($tituloDocumento) ?> <?= htmlspecialchars($folio ?: ("#".$idOrden)) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; margin: 24px; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 20px; }
        .toolbar a { text-decoration: none; color: #334155; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .toolbar a.btn-print { background: #0d6efd; color: white; border-color: #0d6efd; }
        .document-header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #334155; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { display: flex; gap: 12px; align-items: center; }
        .brand-mark { width: 48px; height: 48px; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #334155; object-fit: contain; }
        .doc-meta { text-align: right; font-size: 0.9rem; color: #475569; }
        h1 { font-size: 1.4rem; margin: 0 0 4px 0; }
        .subtitle, .muted { color: #64748b; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 16px; }
        .grid th, .grid td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        .grid th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: 0.02em; }
        .text-end { text-align: right; }
        .row { display: grid; grid-template-columns: 220px 1fr; gap: 6px 16px; }
        .label { color: #64748b; }
        .totales { margin-top: 8px; display: flex; justify-content: flex-end; }
        .totales .box { width: 280px; }
        .box .line { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        .box .line:last-child { font-weight: 700; border-bottom: 0; border-top: 2px solid #94a3b8; margin-top: 6px; padding-top: 10px; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #f1f5f9; font-weight: 700; }
        @media print { .toolbar { display: none; } body { margin: 10px; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1><?= htmlspecialchars($tituloDocumento) ?></h1>
            <div class="subtitle"><?= htmlspecialchars($esProveedor ? "Formato para compartir con proveedor" : "Formato formal interno") ?></div>
        </div>
        <div>
            <a href="/compra/ver_orden_compra/<?= $idOrden ?>">Volver</a>
            <a href="/compra/orden_imprimir_erp/<?= $idOrden ?>?plantilla=orden_compra_interna">Interna</a>
            <a href="/compra/orden_imprimir_erp/<?= $idOrden ?>?plantilla=orden_compra_proveedor">Proveedor</a>
            <a class="btn-print" href="#" onclick="window.print(); return false;">Imprimir</a>
        </div>
    </div>

    <?php if ($errorImprimir): ?>
        <p class="muted"><?= htmlspecialchars($errorImprimir) ?></p>
    <?php else: ?>
        <div class="document-header">
            <div class="brand">
                <?php if ($mostrarLogo && $logoRuta !== ""): ?>
                    <img class="brand-mark" src="<?= htmlspecialchars($logoRuta) ?>" alt="Logo">
                <?php elseif ($mostrarLogo): ?>
                    <div class="brand-mark">ERP</div>
                <?php endif; ?>
                <div>
                    <h1><?= htmlspecialchars($tituloDocumento) ?></h1>
                    <div class="muted"><?= htmlspecialchars($esProveedor ? "Documento operativo para proveedor" : "Documento operativo de Compras") ?></div>
                </div>
            </div>
            <div class="doc-meta">
                <div><strong><?= htmlspecialchars($folio ?: "OC-PENDIENTE") ?></strong></div>
                <div>Generado: <?= htmlspecialchars(date("Y-m-d H:i")) ?></div>
                <?php if (!$esProveedor): ?>
                    <div>Estado: <span class="status"><?= htmlspecialchars(ucfirst((string)$estatus)) ?></span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div><span class="label">Folio:</span> <strong><?= htmlspecialchars($folio ?: "OC-PENDIENTE") ?></strong></div>
            <?php if (!$esProveedor): ?>
                <div><span class="label">Estatus:</span> <strong><?= htmlspecialchars(ucfirst((string)$estatus)) ?></strong></div>
                <div><span class="label">Solicitud:</span> <?= htmlspecialchars($orden["folio_solicitud"] ?? "-") ?></div>
            <?php endif; ?>
            <div><span class="label">Proveedor:</span> <?= htmlspecialchars($proveedor) ?></div>
            <div><span class="label">Folio proveedor:</span> <?= htmlspecialchars($folioProveedor ?: "-") ?></div>
            <div><span class="label">Fecha orden:</span> <?= htmlspecialchars($fechaOrden ?: "-") ?></div>
            <div><span class="label">Entrega estimada:</span> <?= htmlspecialchars($fechaEntrega ?: "-") ?></div>
            <?php if (!$esProveedor): ?>
                <div><span class="label">Almacen destino:</span> <?= htmlspecialchars($almacen ?: "-") ?></div>
            <?php endif; ?>
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <?php if ($mostrarSkuProveedor): ?><th>SKU proveedor</th><?php endif; ?>
                    <?php if ($mostrarSkuErp): ?><th>SKU ERP</th><?php endif; ?>
                    <?php if ($mostrarNombreProveedor): ?><th>Producto proveedor</th><?php endif; ?>
                    <?php if ($mostrarNombreErp): ?><th>Producto ERP</th><?php endif; ?>
                    <th class="text-end">Cantidad</th>
                    <?php if ($mostrarCostos): ?>
                        <th class="text-end">Costo</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Descuento</th>
                        <th class="text-end">Total</th>
                    <?php endif; ?>
                    <?php if ($mostrarObservacionesInternas || $mostrarObservacionesPublicas): ?><th>Observaciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalle)): ?>
                    <tr><td colspan="<?= intval($colspanDetalle) ?>">Sin partidas</td></tr>
                <?php else: ?>
                    <?php foreach ($detalle as $d): ?>
                        <?php
                        $skuProveedor = $d["sku_proveedor"] ?? ($d["sku"] ?? "");
                        $skuErp = $d["sku"] ?? "";
                        $nombreProveedor = $d["nombre_producto"] ?? ($d["nombre_sku"] ?? "");
                        $nombreErp = $d["nombre_sku"] ?? "";
                        ?>
                        <tr>
                            <?php if ($mostrarSkuProveedor): ?><td><?= htmlspecialchars($skuProveedor) ?></td><?php endif; ?>
                            <?php if ($mostrarSkuErp): ?><td><?= htmlspecialchars($skuErp ?: "-") ?></td><?php endif; ?>
                            <?php if ($mostrarNombreProveedor): ?><td><?= htmlspecialchars($nombreProveedor) ?></td><?php endif; ?>
                            <?php if ($mostrarNombreErp): ?><td><?= htmlspecialchars($nombreErp ?: "-") ?></td><?php endif; ?>
                            <td class="text-end"><?= htmlspecialchars(number_format((float)($d["cantidad"] ?? 0), 4, ".", ",")) ?></td>
                            <?php if ($mostrarCostos): ?>
                                <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["costo_unitario"] ?? 0), 2, ".", ",")) ?></td>
                                <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["subtotal"] ?? 0), 2, ".", ",")) ?></td>
                                <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["descuento"] ?? 0), 2, ".", ",")) ?></td>
                                <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["total"] ?? 0), 2, ".", ",")) ?></td>
                            <?php endif; ?>
                            <?php if ($mostrarObservacionesInternas || $mostrarObservacionesPublicas): ?>
                                <td><?= htmlspecialchars($d["observaciones"] ?? "") ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($mostrarTotales): ?>
            <div class="totales">
                <div class="box">
                    <div class="line"><span>Subtotal</span><span>$<?= htmlspecialchars(number_format($subtotal, 2, ".", ",")) ?></span></div>
                    <div class="line"><span>Impuestos</span><span>$<?= htmlspecialchars(number_format($impuestos, 2, ".", ",")) ?></span></div>
                    <div class="line"><span>Total</span><span>$<?= htmlspecialchars(number_format($total, 2, ".", ",")) ?></span></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($mostrarObservacionesInternas): ?>
            <p class="muted">Observaciones: <?= htmlspecialchars($observaciones !== "" ? $observaciones : "Sin observaciones") ?></p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
