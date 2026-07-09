<?php
$solicitud = isset($datos["solicitud"]) && is_array($datos["solicitud"]) ? $datos["solicitud"] : array();
$detalle = isset($datos["detalle"]) && is_array($datos["detalle"]) ? $datos["detalle"] : array();
$ordenRelacionada = isset($datos["orden_relacionada"]) && is_array($datos["orden_relacionada"]) ? $datos["orden_relacionada"] : null;
$idSolicitud = isset($datos["id_solicitud"]) ? intval($datos["id_solicitud"]) : 0;
$errorImprimir = isset($datos["error_imprimir"]) ? trim((string)$datos["error_imprimir"]) : "";

$folio = isset($solicitud["folio"]) ? $solicitud["folio"] : "";
$fechaSolicitud = isset($solicitud["fecha_solicitud"]) ? $solicitud["fecha_solicitud"] : "";
$proveedor = isset($solicitud["proveedor"]) ? $solicitud["proveedor"] : "";
$solicitante = isset($solicitud["solicitante_nombre"]) ? $solicitud["solicitante_nombre"] : "";
$solicitanteArea = isset($solicitud["solicitante_area"]) ? $solicitud["solicitante_area"] : "";
$almacen = isset($solicitud["almacen"]) ? $solicitud["almacen"] : "";
$estatus = isset($solicitud["estatus"]) ? $solicitud["estatus"] : "";
$prioridad = isset($solicitud["prioridad"]) ? $solicitud["prioridad"] : "";
$fechaRequerida = isset($solicitud["fecha_requerida"]) ? $solicitud["fecha_requerida"] : "";
$observaciones = isset($solicitud["observaciones"]) ? $solicitud["observaciones"] : "";
$subtotal = isset($solicitud["subtotal_estimado"]) ? floatval($solicitud["subtotal_estimado"]) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitud de compra <?= htmlspecialchars($folio ?: ("#".$idSolicitud)) ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #1e293b; margin: 24px; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 20px; }
        .toolbar a { text-decoration: none; color: #334155; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .toolbar a.btn-print { background: #0d6efd; color: white; border-color: #0d6efd; }
        .document-header { display: flex; justify-content: space-between; gap: 20px; border-bottom: 2px solid #334155; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { display: flex; gap: 12px; align-items: center; }
        .brand-mark { width: 48px; height: 48px; border: 1px solid #cbd5e1; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #334155; }
        .doc-meta { text-align: right; font-size: 0.9rem; color: #475569; }
        h1 { font-size: 1.4rem; margin: 0 0 4px 0; }
        h2 { font-size: 1rem; margin: 20px 0 8px 0; }
        .subtitle { color: #64748b; margin-bottom: 14px; }
        .grid { width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 16px; }
        .grid th, .grid td { border: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        .grid th { background: #f8fafc; font-size: 12px; text-transform: uppercase; letter-spacing: 0.02em; }
        .text-end { text-align: right; }
        .row { display: grid; grid-template-columns: 220px 1fr; gap: 6px 16px; }
        .label { color: #64748b; }
        .muted { color: #64748b; font-size: 0.95rem; }
        .totales { margin-top: 8px; display: flex; justify-content: flex-end; }
        .totales .box { width: 260px; }
        .box .line { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #e2e8f0; }
        .box .line:last-child { font-weight: 700; border-bottom: 0; border-top: 2px solid #94a3b8; margin-top: 6px; padding-top: 10px; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #f1f5f9; font-weight: 700; }
        .approval { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; margin-top: 28px; }
        .signature { border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center; color: #64748b; min-height: 42px; }
        @media print {
            .toolbar { display: none; }
            body { margin: 10px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>Solicitud de compra</h1>
            <div class="subtitle">Formato formal de impresión</div>
        </div>
        <div>
            <a href="/compra/mostrar_solicitud/<?= $idSolicitud ?>">Volver</a>
            <a class="btn-print" href="#" onclick="window.print(); return false;">Imprimir</a>
        </div>
    </div>

    <?php if ($errorImprimir): ?>
        <p class="muted"><?= htmlspecialchars($errorImprimir) ?></p>
    <?php else: ?>
        <div class="document-header">
            <div class="brand">
                <div class="brand-mark">ERP</div>
                <div>
                    <h1>Solicitud de compra</h1>
                    <div class="muted">Documento operativo de Compras</div>
                </div>
            </div>
            <div class="doc-meta">
                <div><strong><?= htmlspecialchars($folio ?: "SC-PENDIENTE") ?></strong></div>
                <div>Generado: <?= htmlspecialchars(date("Y-m-d H:i")) ?></div>
                <div>Estado: <span class="status"><?= htmlspecialchars(ucfirst((string)$estatus)) ?></span></div>
            </div>
        </div>

        <div class="row">
            <div><span class="label">Folio:</span> <strong><?= htmlspecialchars($folio ?: "SC-PENDIENTE") ?></strong></div>
            <div><span class="label">Estatus:</span> <strong><?= htmlspecialchars(ucfirst((string)$estatus)) ?></strong></div>
            <div><span class="label">Solicitante:</span> <?= htmlspecialchars($solicitante ?: "-") ?></div>
            <div><span class="label">Area:</span> <?= htmlspecialchars($solicitanteArea ?: "-") ?></div>
            <div><span class="label">Proveedor:</span> <?= htmlspecialchars($proveedor) ?></div>
            <div><span class="label">Almacen destino:</span> <?= htmlspecialchars($almacen ?: "-") ?></div>
            <div><span class="label">Fecha solicitud:</span> <?= htmlspecialchars($fechaSolicitud ?: "-") ?></div>
            <div><span class="label">Fecha requerida:</span> <?= htmlspecialchars($fechaRequerida ?: "-") ?></div>
            <div><span class="label">Prioridad:</span> <?= htmlspecialchars($prioridad) ?></div>
        </div>

        <table class="grid">
            <thead>
                <tr>
                    <th style="width:120px;">SKU</th>
                    <th>Producto</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Costo estimado</th>
                    <th class="text-end">Subtotal</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalle)): ?>
                    <tr>
                        <td colspan="6">Sin partidas</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($detalle as $d): ?>
                        <?php
                        $sku = $d["sku"] ?? ($d["sku_proveedor"] ?? "");
                        $nombre = $d["nombre"] ?? ($d["nombre_producto"] ?? "");
                        $esNuevo = intval($d["id_sku_erp"] ?? 0) <= 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($sku) ?></td>
                            <td>
                                <?= htmlspecialchars($nombre) ?>
                                <?= $esNuevo ? "<br><span class=\"muted\">Producto propuesto / pendiente de catalogo</span>" : "" ?>
                            </td>
                            <td class="text-end"><?= htmlspecialchars(number_format((float)($d["cantidad"] ?? 0), 4, ".", ",")) ?></td>
                            <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["costo_estimado"] ?? 0), 2, ".", ",")) ?></td>
                            <td class="text-end">$<?= htmlspecialchars(number_format((float)($d["subtotal"] ?? 0), 2, ".", ",")) ?></td>
                            <td><?= htmlspecialchars($d["observaciones"] ?? "") ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="totales">
            <div class="box">
                <div class="line">
                    <span>Partidas</span>
                    <span><?= count($detalle) ?></span>
                </div>
                <div class="line">
                    <span>Total estimado</span>
                    <span>$<?= htmlspecialchars(number_format($subtotal, 2, ".", ",")) ?></span>
                </div>
            </div>
        </div>

        <p class="muted">Observaciones: <?= htmlspecialchars($observaciones !== "" ? $observaciones : "Sin observaciones") ?></p>

        <?php if ($ordenRelacionada): ?>
            <h2>Orden relacionada</h2>
            <table class="grid">
                <thead>
                    <tr>
                        <th>Folio orden</th>
                        <th>Folio proveedor</th>
                        <th>Estado</th>
                        <th class="text-end">Partidas</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= htmlspecialchars($ordenRelacionada["folio"] ?? "-") ?></td>
                        <td><?= htmlspecialchars($ordenRelacionada["folio_proveedor"] ?? "-") ?></td>
                        <td><?= htmlspecialchars($ordenRelacionada["estatus"] ?? "-") ?></td>
                        <td class="text-end"><?= htmlspecialchars(number_format((float)($ordenRelacionada["total_partidas"] ?? 0), 0, ".", ",")) ?></td>
                        <td class="text-end">$<?= htmlspecialchars(number_format((float)($ordenRelacionada["total"] ?? 0), 2, ".", ",")) ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="approval">
            <div class="signature">Solicita</div>
            <div class="signature">Autoriza</div>
            <div class="signature">Recibe compras</div>
        </div>
    <?php endif; ?>
</body>
</html>
