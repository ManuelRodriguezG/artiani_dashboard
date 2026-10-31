<?php

declare(strict_types=1);

$xmlPath = $argv[1] ?? 'C:\\Users\\aleja\\Downloads\\GCI130520U20FA     64185.xml';
$outputPath = $argv[2] ?? __DIR__ . '\\..\\..\\output\\pdf\\factura_prueba_xml_larga_64185.pdf';

require_once __DIR__ . '\\..\\..\\app\\helpers\\PDF\\autoload.inc.php';

function nodeAttr(?DOMElement $node, string $name, string $fallback = '-'): string {
    if (!$node) {
        return $fallback;
    }
    $value = trim((string) $node->getAttribute($name));
    return $value !== '' ? $value : $fallback;
}

function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function money(string $value, string $currency): string {
    return number_format((float) $value, 2, '.', ',') . ' ' . ($currency !== '' ? $currency : 'MXN');
}

function firstNode(DOMXPath $xpath, string $query): ?DOMElement {
    $nodes = $xpath->query($query);
    return $nodes && $nodes->length ? $nodes->item(0) : null;
}

if (!is_file($xmlPath)) {
    fwrite(STDERR, "No se encontro el XML: {$xmlPath}\n");
    exit(1);
}

$document = new DOMDocument();
$document->preserveWhiteSpace = false;
$document->formatOutput = false;
if (!$document->load($xmlPath)) {
    fwrite(STDERR, "No se pudo leer el XML\n");
    exit(1);
}

$xpath = new DOMXPath($document);
$root = $document->documentElement;
$emisor = firstNode($xpath, '/*[local-name()="Comprobante"]/*[local-name()="Emisor"]');
$receptor = firstNode($xpath, '/*[local-name()="Comprobante"]/*[local-name()="Receptor"]');
$timbre = firstNode($xpath, '//*[local-name()="TimbreFiscalDigital"]');
$impuestos = firstNode($xpath, '/*[local-name()="Comprobante"]/*[local-name()="Impuestos"]');
$conceptos = $xpath->query('/*[local-name()="Comprobante"]/*[local-name()="Conceptos"]/*[local-name()="Concepto"]');

$currency = nodeAttr($root, 'Moneda', 'MXN');
$folio = trim(nodeAttr($root, 'Serie', '') . ' ' . nodeAttr($root, 'Folio', ''));
$folio = $folio !== '' ? $folio : 'Sin folio';
$logoPath = realpath(__DIR__ . '\\..\\..\\public\\assets\\media\\logos\\artiani-logo-pdf.jpg');
$logoData = $logoPath ? 'data:image/jpeg;base64,' . base64_encode((string) file_get_contents($logoPath)) : '';
$logoMarkup = $logoData !== '' ? '<img src="' . $logoData . '" alt="Artiani">' : '<b>ARTIANI</b>';

$rows = '';
foreach ($conceptos as $concepto) {
    $rows .= '<tr>'
        . '<td class="right">' . esc(nodeAttr($concepto, 'Cantidad', '0')) . '</td>'
        . '<td>' . esc(nodeAttr($concepto, 'Unidad', nodeAttr($concepto, 'ClaveUnidad', '-'))) . '</td>'
        . '<td><b>' . esc(nodeAttr($concepto, 'Descripcion', '-')) . '</b><small>Clave SAT: ' . esc(nodeAttr($concepto, 'ClaveProdServ', '-')) . '</small></td>'
        . '<td class="right">' . money(nodeAttr($concepto, 'ValorUnitario', '0'), $currency) . '</td>'
        . '<td class="right">' . money(nodeAttr($concepto, 'Descuento', '0'), $currency) . '</td>'
        . '<td class="right">' . money(nodeAttr($concepto, 'Importe', '0'), $currency) . '</td>'
        . '</tr>';
}

$selloCfdi = nodeAttr($timbre, 'SelloCFD', nodeAttr($root, 'Sello', 'No disponible'));
$selloSat = nodeAttr($timbre, 'SelloSAT', 'No disponible');
$uuid = nodeAttr($timbre, 'UUID', '-');
$cadena = '||' . nodeAttr($root, 'Version', '4.0') . '|' . $uuid . '|' . nodeAttr($timbre, 'FechaTimbrado', '-') . '|' . nodeAttr($timbre, 'RfcProvCertif', '-') . '|' . $selloCfdi . '|' . nodeAttr($timbre, 'NoCertificadoSAT', '-') . '||';

$html = '<!doctype html><html lang="es"><head><meta charset="UTF-8"><style>'
    . '@page{margin:14px 18px 18px}'
    . 'body{font-family:DejaVu Sans,Arial,sans-serif;color:#202124;font-size:8px;margin:0}'
    . '.document{border:1px solid #d7e1e4;padding:10px}'
    . 'table{border-collapse:collapse;width:100%}'
    . '.header{border-bottom:3px solid #0b7376;padding-bottom:7px}.header td{vertical-align:top}'
    . '.supplier{width:72%}.supplier img{float:left;margin-right:10px;max-width:130px;max-height:42px}.supplier b{display:block;font-size:10px}.supplier span,.title small{display:block;font-size:7px;line-height:1.3}'
    . '.title{text-align:right;width:28%}.title span{display:block;color:#0b7376;font-weight:bold;font-size:8px}.title b{display:block;color:#0b7376;font-size:18px}'
    . '.bar{background:#0b7376;color:#fff;padding:4px 6px;margin-top:6px;font-size:7px;font-weight:bold;text-transform:uppercase}'
    . '.split td{padding:4px;text-align:center;color:#fff;font-weight:bold}.split td:first-child{background:#0b7376}.items th{background:#e9485b}.split td:last-child{background:#0b7376}'
    . '.receiver{border:1px solid #e8eef0;border-top:0}.receiver td{vertical-align:top;padding:6px;width:58%;font-size:7px;line-height:1.35}.receiver td:last-child{width:42%}.receiver b,.receiver span{display:block}'
    . '.cert{padding:0 0 0 8px}.cert b,.cert span{display:block;padding:4px;background:#f1fbfb;border-left:3px solid #0b7376;font-size:6.5px;overflow-wrap:anywhere}'
    . '.items{table-layout:fixed;margin-top:6px}.items thead{display:table-header-group}.items tr{page-break-inside:avoid}.items th{color:#fff;font-size:6.5px;text-transform:uppercase;padding:4px 3px;text-align:left}.items td{border-bottom:1px solid #e7edef;padding:4px 3px;font-size:7px;vertical-align:top;overflow-wrap:anywhere}.items th:nth-child(1),.items td:nth-child(1){width:9%}.items th:nth-child(2),.items td:nth-child(2){width:11%}.items th:nth-child(3),.items td:nth-child(3){width:39%}.items th:nth-child(4),.items td:nth-child(4){width:14%}.items th:nth-child(5),.items td:nth-child(5){width:12%}.items th:nth-child(6),.items td:nth-child(6){width:15%}.items small{display:block;font-size:6px}'
    . '.right{text-align:right}.payment{border:1px solid #d7e1e4;border-top:0}.payment td{vertical-align:top;padding:6px;font-size:7px}.payment td:first-child{width:62%}.payment td:last-child{width:38%;border-left:1px solid #b9d9da}.payment p{margin:0 0 3px}.totals p{display:table;width:100%;margin:0 0 3px}.totals p span,.totals p b{display:table-cell}.totals p b{text-align:right}.total{background:#e9485b;color:#fff!important;padding:5px;font-size:8px;font-weight:bold}.total span,.total b{color:#fff!important}'
    . '.seals{border:1px solid #d7e1e4;border-top:0}.seals td{vertical-align:top;padding:6px}.seals td:first-child{width:28%}.qr{height:100px;background:#f1fbfb;border:1px solid #b9d9da;color:#0b7376;text-align:center;padding-top:42px;box-sizing:border-box;font-size:7px}.label{display:block;color:#0b7376;font-size:7px;font-weight:bold;margin-bottom:2px}.seal{font-family:DejaVu Sans Mono,monospace;font-size:5.3px;line-height:1.15;word-break:break-all;margin:0 0 4px}.chain{border:1px solid #d7e1e4;border-top:0;padding:5px;font-family:DejaVu Sans Mono,monospace;font-size:5.3px;line-height:1.15;word-break:break-all;margin:0}.footer{margin-top:6px;background:#0b7376;color:#fff;text-align:center;padding:4px;font-size:6.5px}'
    . '</style></head><body><div class="document">'
    . '<table class="header"><tr><td class="supplier">' . $logoMarkup . '<div><b>' . esc(nodeAttr($emisor, 'Nombre', 'Artiani')) . '</b><span>RFC: ' . esc(nodeAttr($emisor, 'Rfc')) . '</span><span>Régimen fiscal: ' . esc(nodeAttr($emisor, 'RegimenFiscal')) . '</span><span>Lugar de expedición: ' . esc(nodeAttr($root, 'LugarExpedicion')) . '</span></div></td><td class="title"><span>FACTURA</span><b>' . esc($folio) . '</b><small>Fecha de emisión: ' . esc(str_replace('T', ' ', nodeAttr($root, 'Fecha'))) . '</small><small>Fecha de certificación: ' . esc(str_replace('T', ' ', nodeAttr($timbre, 'FechaTimbrado'))) . '</small></td></tr></table>'
    . '<table class="split bar"><tr><td>Receptor del comprobante fiscal</td><td>Folio fiscal</td></tr></table><table class="receiver"><tr><td><b>' . esc(nodeAttr($receptor, 'Nombre')) . '</b><span>RFC: ' . esc(nodeAttr($receptor, 'Rfc')) . '</span><span>Régimen fiscal: ' . esc(nodeAttr($receptor, 'RegimenFiscalReceptor')) . '</span><span>Domicilio fiscal: ' . esc(nodeAttr($receptor, 'DomicilioFiscalReceptor')) . '</span><span>Uso CFDI: ' . esc(nodeAttr($receptor, 'UsoCFDI')) . '</span></td><td class="cert"><b>' . esc($uuid) . '</b><span>No. certificado digital: ' . esc(nodeAttr($root, 'NoCertificado')) . '</span><span>No. certificado SAT: ' . esc(nodeAttr($timbre, 'NoCertificadoSAT')) . '</span></td></tr></table>'
    . '<table class="items"><thead><tr><th>Cantidad</th><th>Unidad</th><th>Descripción</th><th>Precio</th><th>Descuento</th><th>Importe</th></tr></thead><tbody>' . $rows . '</tbody></table>'
    . '<div class="bar">Información de pago y totales</div><table class="payment"><tr><td><p><b>Lugar de expedición:</b> ' . esc(nodeAttr($root, 'LugarExpedicion')) . '</p><p><b>Forma de pago:</b> ' . esc(nodeAttr($root, 'FormaPago')) . '</p><p><b>Método de pago:</b> ' . esc(nodeAttr($root, 'MetodoPago')) . '</p><p><b>Moneda:</b> ' . esc($currency) . '</p></td><td class="totals"><p><span>Subtotal</span><b>' . money(nodeAttr($root, 'SubTotal', '0'), $currency) . '</b></p><p><span>Descuento</span><b>' . money(nodeAttr($root, 'Descuento', '0'), $currency) . '</b></p><p><span>Impuestos</span><b>' . money(nodeAttr($impuestos, 'TotalImpuestosTrasladados', '0'), $currency) . '</b></p><p class="total"><span>Total</span><b>' . money(nodeAttr($root, 'Total', '0'), $currency) . '</b></p></td></tr></table>'
    . '<table class="split bar"><tr><td>Código bidimensional</td><td>Sellos digitales</td></tr></table><table class="seals"><tr><td><div class="qr">QR SAT<br><small>Se carga en la factura final</small></div></td><td><span class="label">Sello digital del CFDI</span><p class="seal">' . esc($selloCfdi) . '</p><span class="label">Sello digital del SAT</span><p class="seal">' . esc($selloSat) . '</p></td></tr></table><div class="bar">Cadena original del complemento de certificación digital del SAT</div><p class="chain">' . esc($cadena) . '</p><div class="footer">Este documento es una representación impresa de un CFDI · Versión ' . esc(nodeAttr($root, 'Version', '4.0')) . '</div></div></body></html>';

$options = new Dompdf\Options();
$options->set('isRemoteEnabled', false);
$dompdf = new Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$directory = dirname($outputPath);
if (!is_dir($directory)) {
    mkdir($directory, 0775, true);
}
file_put_contents($outputPath, $dompdf->output());
echo $outputPath . PHP_EOL;

