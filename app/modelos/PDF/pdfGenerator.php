<?php
    require('html_table.php');

/*$ref = isset($_GET['referencia']) ? $_GET['referencia'] : '0000000000';
$total = isset($_GET['total']) ? $_GET['total'] : '00000';
$date = isset($_GET['date']) ? $_GET['date'] : '00/00/00';*/
if(isset($_GET['data'])){
	$data = json_decode($_GET['data']);
	if(isset($data->action) && $data->action == 'printPurchase'){
		PDFGenerator::printForm($data->printData);
	}
}

class PDFGenerator{

 function generate($dataXML){
	setlocale(LC_TIME,"es_ES@euro.UTF8","es_ES.UTF8","esp",'spanish');
	define("CHARSET", "iso-8859-1");
	//$tour_date = date_create($date);
	$now = date_create(date("Y-m-d"));
	//$payment_date = date_diff($now, $tour_date);
	//if($payment_date->days > 3)
	//	$date = utf8_encode(strftime ('%A %d de %B de %Y',strtotime("+3 day", strtotime(date("Y-m-d")))));
	//else
	//	$date = utf8_encode(strftime ('%A %d de %B de %Y',strtotime("+".($payment_date->days-1)." day", strtotime(date("Y-m-d")))));
    //$html = '<table>
    //<tr>
    //<td width="785" style="border:1px solid"><strong>Cliente</strong></td>
    //</tr>
    //<tr>
    //<td width="785">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</td>
    //</tr>
    //<tr>
    //<td width="392.5"><strong>RFC:</strong></td>
    //<td width="392.5">OSG08092599A</td>
    //</tr>
    //</table>';


	$pdf = new PDF();
	$pdf->AddPage('PORTRAIT','letter');
	$pdf->SetLeftMargin(10);
	//$pdf->SetFont('Arial','',20);
	//$pdf->Image('https://panoramex.mx/imagenes/logo-email.png',95,10,22,0,'');
	$pdf->Image('https://panoramex.mx/imagenes/logo-email.png',10,6,33,0,'');
	$pdf->Ln(20);
	//$pdf->Image('images/logoNuevo.png',10,8,35,0,'');
	$pdf->SetFontSize(8);
	//$pdf->WriteHTML($html);
	//Cell(w,h,txt,border,ln,align,fill,link);
    $pdf->Ln();
    $pdf->Ln();

	$pdf->SetFont('helvetica','b');
	//$pdf->Cell(96.4,5,utf8_decode("EXPERIENCIAS PANORAMICAS de MÉXICO, S. DE R.L de C.V"),0,0,'',false);
	$pdf->Cell(96.4,5,utf8_decode($dataXML['Emisor']['Nombre'][0]),0,0,'',false);
	
	/**
	 * Tipos de ingreso
	*/
	
	$tiposIngreso = array(
	    'I'=>'Ingreso',
	    'E'=>'Egreso',
	    'T'=>'Traslado',
	    'P'=>'Recepción de Pagos',
	    'N'=>'Nómina'
	);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("Tipo de Comprobante:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	//$pdf->Cell(62.5,5,utf8_decode("Ingreso"),0,0,'',false);
	$pdf->Cell(62.5,5,utf8_decode($tiposIngreso[$dataXML['Comprobante']['TipoDeComprobante'][0]]),0,0,'',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(28.2,5,utf8_decode("RFC emisor:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	//$pdf->Cell(68.2,5,utf8_decode("EPM170605KP9"),0,0,'',false);
	$pdf->Cell(68.2,5,utf8_decode($dataXML['Emisor']['Rfc'][0]),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("Folio Fiscal:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	//$pdf->Cell(62.5,5,utf8_decode("AADCF822-7C83-4E2A-9C3E-DB4299A5B0C3"),0,0,'',false);
	$pdf->Cell(62.5,5,utf8_decode($dataXML['TimbreFiscal']['UUID'][0]),0,0,'',false);
	
	$regFiscal = array(
	    '601'=>'General de Ley Personas Morales',
	    '603'=>'Personas Morales con Fines no Lucrativos',
	    '605'=>'Sueldos y Salarios e Ingresos Asimilados a Salarios',
	    '606'=>'Arrendamiento',
	    '608'=>'Demás Ingresos',
	    '609'=>'Consolidación',
	    '610'=>'Residentes en el Extranjero sin Establecimiento Permanente en México',
	    '611'=>'Ingresos por Dividendos (socios y accionistas)',
	    '612'=>'Personas Fisicas con Actividades Empresariales y Profesionales',
	    '614'=>'Ingresos por intereses',
	    '616'=>'Sin obligaciones fiscales',
	    '620'=>'Sociedades Coorporativas de Producción que optan por diferir sus ingresos',
	    '621'=>'Incorporación Fiscal',
	    '622'=>'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'
	);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(28.2,5,utf8_decode("Régimen Fiscal:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(68.2,5,utf8_decode($dataXML['Emisor']['RegimenFiscal'][0]."-".$regFiscal[intval($dataXML['Emisor']['RegimenFiscal'][0])]),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("Factura:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(62.5,5,utf8_decode("Serie ".$dataXML['Comprobante']['Serie'][0]." Folio ".$dataXML['Comprobante']['Folio'][0]),0,0,'',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','');
	$pdf->Cell(48.2,5,"",0,0,'',false);
	$pdf->Cell(48.2,5,utf8_decode(""),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("Lugar, Fecha y Hora de"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$fechaE = new DateTime($dataXML['Comprobante']['Fecha'][0], new DateTimeZone('America/Mexico_City'));
    $fechaE = $fechaE->format("Y-m-d H:i:s");
	$pdf->Cell(62.5,5,utf8_decode($dataXML['Comprobante']['LugarExpedicion'][0]." ".$fechaE),0,0,'',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','');
	$pdf->Cell(48.2,5,"",0,0,'',false);
	$pdf->Cell(48.2,5,utf8_decode(""),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("Expedición:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(62.5,5,utf8_decode(""),0,0,'',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','');
	$pdf->Cell(48.2,5,"",0,0,'',false);
	$pdf->Cell(48.2,5,utf8_decode(""),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(35,5,utf8_decode("No. Serie CSD:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(62.5,5,utf8_decode($dataXML['TimbreFiscal']['NoCertificadoSAT'][0]),0,0,'',false);

	$pdf->Ln();
	$pdf->Ln();
	
	$pdf->SetFont('helvetica','b');
	$pdf->SetFillColor(36,26,139);
	$pdf->SetTextColor(255,255,255);
	$pdf->Cell(195,5,"Cliente",1,0,'',true);
	
	$pdf->Ln();
	$pdf->SetTextColor(0,0,0);
	//$pdf->Cell(195,5,"OPERADORA de SERVICIOS GLOBALES, S.A. de C.V.",0,0,'',false);
	$pdf->Cell(195,5,utf8_decode($dataXML['Receptor']['Nombre'][0]),0,0,'',false);
	
	$usoCFDI = array(
	    'G01'=>	'Adquisición de mercancias',
        'G02'=>	'Devoluciones, descuentos o bonificaciones',
        'G03'=>	'Gastos en general',
        'I01'=>	'Construcciones',
        'I02'=>	'Mobilario y equipo de oficina por inversiones',
        'I03'=>	'Equipo de transporte',
        'I04'=>	'Equipo de computo y accesorios',
        'I05'=>	'Dados, troqueles, moldes, matrices y herramental',
        'I06'=>	'Comunicaciones telefónicas',
        'I07'=>	'Comunicaciones satelitales',
        'I08'=>	'Otra maquinaria y equipo',
        'D01'=>	'Honorarios médicos, dentales y gastos hospitalarios.',
        'D02'=>	'Gastos médicos por incapacidad o discapacidad',
        'D03'=>	'Gastos funerales.',
        'D04'=>	'Donativos.',
        'D05'=>	'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación).',
        'D06'=>	'Aportaciones voluntarias al SAR.',
        'D07'=>	'Primas por seguros de gastos médicos.',
        'D08'=>	'Gastos de transportación escolar obligatoria.',
        'D09'=>	'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones.',
        'D10'=>	'Pagos por servicios educativos (colegiaturas)',
        'P01'=>	'Por definir',
	);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(18.5,5,utf8_decode("RFC:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(45,5,utf8_decode($dataXML['Receptor']['Rfc'][0]),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	
	$pdf->Cell(25,5,utf8_decode("Uso CFDI:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(46.5,5,($dataXML['Receptor']['UsoCFDI'][0]."-".utf8_decode($usoCFDI[strval($dataXML['Receptor']['UsoCFDI'][0])])),0,0,'',false);
	if($dataXML['Receptor']['ResidenciaFiscal'] != null){
	    $pdf->SetFont('helvetica','b');
    	$pdf->Cell(25,5,utf8_decode("Residencia fiscal:"),0,0,'',false);
    	$pdf->SetFont('helvetica','');
    	$pdf->Cell(35,5,utf8_decode($dataXML['Receptor']['ResidenciaFiscal'][0]),0,0,'',false);
	}
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	if($dataXML['Receptor']['NumRegIdTrib'] != null){
	    
	
    	$pdf->Cell(47,5,utf8_decode("Número registro identidad fiscal:"),0,0,'',false);
    	$pdf->SetFont('helvetica','');
    	$pdf->Cell(25,5,utf8_decode($dataXML['Receptor']['NumRegIdTrib'][0]),0,0,'',false);
	}
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(18.5,5,utf8_decode("Domicilio:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(104.5,5,utf8_decode("Punto Sao Paulo INTERIOR 2106"),0,0,'',false);
	
	
	
	
	
	$pdf->Ln();
	$pdf->Ln();
	
	$pdf->SetFont('helvetica','b');
	$pdf->SetFillColor(36,26,139);
	$pdf->SetTextColor(255,255,255);
	
	$pdf->Cell(27.85,5,utf8_decode("Clave Producto"),1,0,'C',true);
	$pdf->Cell(76.85,5,utf8_decode("Descripción"),1,0,'C',true);
	$pdf->Cell(17.85,5,utf8_decode("Unidad"),1,0,'C',true);
	$pdf->Cell(8.85,5,utf8_decode("Cant"),1,0,'C',true);
	$pdf->Cell(22.85,5,utf8_decode("Clave Unidad"),1,0,'C',true);
	//$pdf->Cell(27.85,5,utf8_decode("Tasa/Cuota"),1,0,'C',true);
	$pdf->Cell(22.85,5,utf8_decode("Valor Unitario"),1,0,'C',true);
	$pdf->Cell(17.85,5,utf8_decode("Importe"),1,0,'C',true);

	
	$pdf->SetTextColor(0,0,0);
	$pdf->SetFont('helvetica','');
	$pdf->tablewidths = array(27.85,76.85,17.85,8.85,22.85,22.85,17.85);
	
	foreach($dataXML['Conceptos'] as $indice => $vals){
	    foreach($vals as $atributo => $valAtributo){
	        if($atributo == 'Unidad'){
	            $unidad = $valAtributo[0];
	        }elseif($atributo == 'Importe'){
	            $importe = $valAtributo[0];
	        }elseif($atributo == 'Cantidad'){
	            $cantidad = $valAtributo[0];
	        }elseif($atributo == 'Descripcion'){
	            $descripcion = $valAtributo[0];
	        }elseif($atributo == 'ValorUnitario'){
	            $valorUnitario = $valAtributo[0];
	        }elseif($atributo == 'ClaveUnidad'){
	            $claveUnidad = $valAtributo[0];
	        }elseif($atributo == 'ClaveProdServ'){
	            $claveProd = $valAtributo[0];
	        }
	    }
	    $infoProducts[] = array(
	        $claveProd,
	        utf8_decode($descripcion),
	        $unidad,
	        $cantidad,
	        $claveUnidad,
	        $valorUnitario,
	        $importe
	    );
	}
	
	
	//$infoProducts[] = array(
	//    '90121501',
	//    utf8_decode('Tren JC vagón intermedio 10 de Abril 2 pax, reserva de TEOEMA PALMA BEDOLL A'),
    //    'SERVICIO',
	//    '1',
	//    'E48',
	//    '4950.00',
	//    '4950.00'
	//);
	  

	$pdf->Ln();
	
	/**
	 * $pdf->morepagestable($data,$heightCell,$border);
	 * $pdf->morepagestable($data,5,true);
	*/
	$pdf->morepagestable($infoProducts,5,true,false,'C');
	
	
	$pdf->Ln();
	
	//$pdf->Ln();
	//$pdf->SetFont('helvetica','');
	//$pdf->SetTextColor(0,0,0);
	//$pdf->Cell(27.85,5,utf8_decode("90121501"),1,0,'C',false);
	//$pdf->Cell(27.85,5,utf8_decode("Tasa/Cuota"),1,0,'C',true);
	//$pdf->Cell(27.85,5,utf8_decode("SERVICIO"),1,0,'C',false);
	//$pdf->Cell(27.85,5,utf8_decode("1"),1,0,'C',false);
	//$pdf->Cell(27.85,5,utf8_decode("E48"),1,0,'C',false);
	////$pdf->Cell(27.85,5,utf8_decode("Tasa IVA 0.16"),1,0,'C',false);
	//$pdf->Cell(27.85,5,utf8_decode("4950.00"),1,0,'C',false);
	//$pdf->Cell(27.85,5,utf8_decode("4950.00"),1,0,'C',false);
	
	$pdf->Ln();
	//$pdf->SetFont('helvetica','b');
	//$pdf->SetFillColor(36,26,139);
	//$pdf->SetTextColor(255,255,255);
	//
	//$pdf->Cell(195,5,utf8_decode("Descripcion"),1,0,'C',true);
	//$pdf->SetFont('helvetica','');
	//$pdf->SetTextColor(0,0,0);
	//$pdf->tablewidths = array(195);
	//$data3[] = array(
	//    utf8_decode('Tren JC vagón intermedio 10 de Abril 2 pax, reserva de TEOEMA PALMA BEDOLL A')
	//    );
	//$pdf->Ln();
	/**
	 * $pdf->morepagestable($data,$heightCell,$border);
	 * $pdf->morepagestable($data,5,true);
	*/
	//$pdf->morepagestable($data3,5,true);
	//$pdf->Cell(167.14,5,utf8_decode("Tren JC vagón intermedio 10 de Abril 2 pax, reserva de TEOEMA PALMA BEDOLL A"),1,0,'',false);
	


	$pdf->SetFontSize(8);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(25.2,5,utf8_decode("Moneda:"),0,0,'L',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(73.4,5,utf8_decode($dataXML['Comprobante']['Moneda'][0]),0,0,'L',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(48.2,5,utf8_decode("Subtotal"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(48.2,5,utf8_decode("$ ".$dataXML['Comprobante']['SubTotal'][0]),0,0,'R',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(25.2,5,utf8_decode("Forma de Pago:"),0,0,'L',false);
	$pdf->SetFont('helvetica','');
	
	$formaPago = array(
	        '01'=>'01-Efectivo',
	        '02'=>'02-Cheque nominativo',
	        '03'=>'03-Transferencia electrónica (incluye SPEI)',
	        '04'=>'04-Tarjeta de crédito',
	        '05'=>'05-Monedero electrónico',
	        '06'=>'06-Dinero electrónico',
	        '08'=>'08-Vales de despensa',
	        '28'=>'28-Tajeta de débito',
	        '29'=>'29-Tarjeta de servicio',
	        '99'=>'Otros'
	);
	    
	$metodoPago = array(
	    'PUE'=>'Pago en una sola exhibición',
	    'PPD'=>'Pago en parcialidades o diferido'
	);
	
	$impuestos = array(
	    '001'=>'ISR',
	    '002'=>'IVA',
	    '003'=>'IEPS'
	    );
	file_put_contents('json.log',(floatval($dataXML['ImpuestosTrasladados']['TasaOCuota'][0])*100).PHP_EOL,FILE_APPEND);
	file_put_contents('json.log',(0.160000*100).PHP_EOL,FILE_APPEND);
	$pdf->Cell(73.4,5,utf8_decode($formaPago[strval($dataXML['Comprobante']['FormaPago'][0])]),0,0,'L',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(32.13,5,utf8_decode("Impuestos Transladados"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(32.13,5,utf8_decode($impuestos[strval($dataXML['ImpuestosTrasladados']['Impuesto'][0])]." ".(floatval($dataXML['ImpuestosTrasladados']['TasaOCuota'][0])*100).".0000%"),0,0,'R',false);
	//$pdf->Cell(15.49,5,utf8_decode(""),0,0,'R',false);
	$pdf->Cell(32.13,5,utf8_decode("$ ".$dataXML['ImpuestosTrasladados']['TotalImpuestosTrasladados'][0]),0,0,'R',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(25.2,5,utf8_decode("Método de pago:"),0,0,'L',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(73.4,5,utf8_decode("PUE-Pago en una sola exhibición"),0,0,'L',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(48.2,5,utf8_decode("Total"),0,0,'',false);
	$pdf->Cell(48.2,5,utf8_decode("$ ".$dataXML['Comprobante']['Total'][0]),0,0,'R',false);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(25.2,5,utf8_decode("Importe con letra:"),0,0,'L',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(71.2,5,utf8_decode($this->convertir(intval($dataXML['Comprobante']['Total'][0]))),0,0,'L',false);
	$pdf->SetFont('helvetica','b');

	
	




	//$pdf->Cell(48.2,5,utf8_decode("Leyenda Fiscal"),0,0,'',false);
	
	
	
	//------------------
	if(count($infoProducts) == 1){
	    $pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
	    $pdf->Ln();
	    $pdf->Ln();
	    $pdf->Ln();

	}elseif(count($infoProducts) == 2){
	    $pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
	    
	}elseif(count($infoProducts) == 3){
	    $pdf->Ln();
    	$pdf->Ln();
    	$pdf->Ln();
	}elseif(count($infoProducts) == 4){
	    $pdf->Ln();
	}
	//------------------
	$ch = curl_init();
    // set url
    $len = strlen($dataXML['TimbreFiscal']['SelloCFD'][0]);
    $fe = substr($dataXML['TimbreFiscal']['SelloCFD'][0],$len-8,$len);
    file_put_contents('json.log',$len.PHP_EOL,FILE_APPEND);
    file_put_contents('json.log',($len-8).PHP_EOL,FILE_APPEND);
    file_put_contents('json.log',json_encode("http://expertensoft.com/qr/qr2.php?https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=".$dataXML['TimbreFiscal']['UUID'][0]."&re=".$dataXML['Emisor']['Rfc'][0]."&rr=".$dataXML['Receptor']['Rfc'][0]."&tt=".$dataXML['Comprobante']['Total'][0]."&fe=".$fe).PHP_EOL,FILE_APPEND);
    curl_setopt($ch, CURLOPT_URL, "http://expertensoft.com/qr/qr2.php?https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=".$dataXML['TimbreFiscal']['UUID'][0]."&re=".$dataXML['Emisor']['Rfc'][0]."&rr=".$dataXML['Receptor']['Rfc'][0]."&tt=".$dataXML['Comprobante']['Total'][0]."&fe=".$fe);
    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // $output contains the output string
    $output = curl_exec($ch);
    // close curl resource to free up system resources
    curl_close($ch);     
    //var_dump($output);
    file_put_contents('qr.png',$output);
	if(count($infoProducts)>4){
        $pdf->AddPage('PORTRAIT','letter');
	    $pdf->SetLeftMargin(10);
	    $pdf->Image('https://panoramex.mx/qr.png',10,68,35,0,'');
	    //$pdf->Image('https://panoramex.mx/qr.png',9,208,49,0,'');
	}else{
	    $pdf->Image('https://panoramex.mx/qr.png',10,218,35,0,'');
	}
	$pdf->SetFontSize(8);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(97.5,5,utf8_decode("Leyenda Fiscal:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(97.5,5,utf8_decode($dataXML['Leyenda']['textoLeyenda'][0]),0,0,'',false);
	
	
	$pdf->Ln();
	$pdf->SetFontSize(7);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(195,5,utf8_decode("Sello digital del CDFI:"),0,0,'',false);
	$pdf->Ln();
	$pdf->SetFont('helvetica','');
	$pdf->tablewidths = array(195);
	//$pdf->WriteHTML($html);
	$selloSat = str_replace(' ','',$dataXML['TimbreFiscal']['SelloSAT'][0]);
	$selloCFDI = str_replace(' ','',$dataXML['TimbreFiscal']['SelloCFD'][0]);
	$data1[] = array(
	    $selloCFDI
	    );
	/**
	 * $pdf->morepagestable($data,$heightCell);
	 * $pdf->morepagestable($data,5);
	*/
	$pdf->morepagestable($data1,5);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(195,5,utf8_decode("Sello digital del SAT:"),0,0,'',false);
	$pdf->Ln();
	$pdf->SetFont('helvetica','');
	$pdf->tablewidths = array(195);
	//$pdf->WriteHTML($html);
	
	
	$data2[] = array(
	    $selloSat
	    );
	/**
	 * $pdf->morepagestable($data,$heightCell);
	 * $pdf->morepagestable($data,5);
	*/
	$pdf->morepagestable($data2,5);
	
	$pdf->Ln();
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(38.2,5,utf8_decode(""),0,0,'',false);
	$pdf->Cell(166.8,5,utf8_decode("Cadena Original del complemento de certificación digital del SAT:"),0,0,'',false);

	$pdf->SetFont('helvetica','b');
	//$pdf->MultiCell(48.2,5,utf8_decode(""),0,'',false);
	$pdf->SetFont('helvetica','');
//	$pdf->Cell(146.8,5,utf8_decode("||1.1|AADCF822-7C83-4E2A-9C3E-DB4299A5B0C3|2020-03-05T19:59:38|SAT970701NN3|ZxGwRlIJQlXS1b9WD0H23CVZyerrM0p0HUuLd7XhB7GpuiDpjA5DzKVNVhSRU//3gTGRk4KoCSNtXa6svOP+C9Ymoo5FvPgRWCMRBwvHS7ZfTJ0T86dRrouEVD4MoioGIQbOp/ZHGGmUo3FSbBFtJ829V0BzwRD8xP/R+XEOiTe/yyLIG5cRlsIkOhIeksRJ2EESY4bwjZFeaqMEkAK1Vq1taI4bLiQvQ/BHWEr+3ZovU1piJn+uz6Ty/iiDHR27f0yBt2uDXKgUzo85AdFU6s8RAqwNd5HBo8D3kDN6hQTNtmXDc0RWMWJUA1bDc+a+2tFFFUUxQc+NrEFzoDN1Gw==|00001000000403258748||"),0,0,'',false);
	$pdf->tablewidths = array(38.2,156.8);
	//$pdf->WriteHTML($html);
	$pdf->Ln();
	$data4[] = array(
	    ' ',
	    '||'.$dataXML['TimbreFiscal']['Version'][0].'|'.$dataXML['TimbreFiscal']['UUID'][0].'|'.$dataXML['TimbreFiscal']['FechaTimbrado'][0].'|'.$dataXML['TimbreFiscal']['RfcProvCertif'][0].'|'.$selloCFDI.'|'.$dataXML['TimbreFiscal']['NoCertificadoSAT'][0].'||'
	    );
	/**
	 * $pdf->morepagestable($data,$heightCell);
	 * $pdf->morepagestable($data,5);
	*/
	$pdf->morepagestable($data4,5);
	

	$pdf->Cell(48.2,5,utf8_decode(""),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->SetFontSize(7);
	$pdf->Cell(43.7,5,utf8_decode("RFC del proveedor de certificación:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(29.7,5,utf8_decode($dataXML['TimbreFiscal']['RfcProvCertif'][0]),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(36.7,5,utf8_decode("Fecha y hora de certificación:"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$fechaC = new DateTime($dataXML['TimbreFiscal']['FechaTimbrado'][0], new DateTimeZone('America/Mexico_City'));
    $fechaC = $fechaC->format("Y-m-d H:i:s");
	$pdf->Cell(36.7,5,utf8_decode($fechaC),0,0,'',false);
	
	$pdf->Ln();
	$pdf->Cell(48.2,5,utf8_decode(""),0,0,'',false);
	$pdf->SetFont('helvetica','b');
	$pdf->Cell(43.7,5,utf8_decode("No. de serie del certificado SAT"),0,0,'',false);
	$pdf->SetFont('helvetica','');
	$pdf->Cell(29.7,5,utf8_decode($dataXML['TimbreFiscal']['NoCertificadoSAT'][0]),0,0,'',false);
	
	
	$result = $pdf->Output("F",$dataXML['Receptor']['Rfc'][0].".pdf");
	return true;

}

function printForm($data = null){
	$pdf = new PDF();
	$pdf->AddPage();
	$pdf->SetDrawColor(100);
	$pdf->SetLeftMargin(5);
	$pdf->SetFont('Arial','',20);
	$pdf->SetX($pdf->GetX()-5);
	$pdf->Write(5, 'REGISTRO DE COMPRA');
	$pdf->Ln(10);
	$pdf->SetFontSize(9);
	$pdf->Cell(60, 5, utf8_decode('Reservación'),0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, 'Fecha Compra',0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, utf8_decode('Descripción'),0,1);
	$pdf->Cell(60, 5, utf8_decode($data[0][16]),0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, utf8_decode($data[0][19]),0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->MultiCell(0, 5, utf8_decode($data[0][18]),0,1);
	$pdf->Ln();
	$pdf->Line(5, $pdf->GetY()-2, 200, $pdf->GetY()-2);

	$pdf->Cell(60, 5, 'Nombre',0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, utf8_decode('Teléfono'),0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, 'Correo',0,1);
	$pdf->Cell(60, 7, utf8_decode($data[0][2]),1);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 7, utf8_decode($data[0][9]),1);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(0, 7, utf8_decode($data[0][10]),1,1);
	$pdf->Ln(2);

	$pdf->Cell(50, 5, utf8_decode('Dirección a recoger'),0,1);
	$pdf->Cell(0, 7, utf8_decode($data[0][3]),1,1);
	$pdf->Ln(2);

	$pdf->Cell(60, 5, 'Fecha Tour',0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, 'Hora PickUp',0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 5, utf8_decode('Medio (Vehículo)'),0,1);
	$pdf->Cell(60, 7, utf8_decode($data[0][1]),1);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(60, 7, utf8_decode($data[0][15]),1);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(0, 7, utf8_decode($data[0][13]),1,1);
	$pdf->Ln(2);

	$pdf->Cell(50, 5, 'Destino',0,1);
	$pdf->Cell(0, 7, utf8_decode($data[0][8]),1,1);
	$pdf->Ln();
	$pdf->Line(5, $pdf->GetY()-2, 200, $pdf->GetY()-2);

	$pdf->Cell(38, 5, 'Adulto',0);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 5, 'Menor',0);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 5, 'Pago',0);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 5, utf8_decode('Método de pago'),0);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 5, 'Estatus de pago',0,1);
	$pdf->Cell(38, 7, utf8_decode($data[0][7]),1);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 7, utf8_decode($data[1][7]),1);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 7, utf8_decode($data[0][5]),1);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(38, 7, utf8_decode($data[0][4]),1);
	$pdf->SetX($pdf->GetX()+2.5);
	$pdf->Cell(0, 7, utf8_decode($data[0][11]),1,1);
	$pdf->Ln(2);

	$pdf->Cell(97.5, 5, 'Atiende',0);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(97.5, 5, 'Tipo de tour',0,1);
	$pdf->Cell(97.5, 7, utf8_decode($data[0][12]),1);
	$pdf->SetX($pdf->GetX()+5);
	$pdf->Cell(0, 7, utf8_decode($data[0][14]),1,1);
	$pdf->Ln();
	$pdf->Line(5, $pdf->GetY()-2, 200, $pdf->GetY()-2);

	$pdf->Cell(50, 5, 'Comentarios',0,2);
	$pdf->Cell(0, 15, utf8_decode($data[0][17]),1);
	$pdf->Output();
}

function unidad($numuero){
        switch ($numuero)
        {
        case 9:
        {
        $numu = "NUEVE";
        break;
        }
        case 8:
        {
        $numu = "OCHO";
        break;
        }
        case 7:
        {
        $numu = "SIETE";
        break;
        }
        case 6:
        {
        $numu = "SEIS";
        break;
        }
        case 5:
        {
        $numu = "CINCO";
        break;
        }
        case 4:
        {
        $numu = "CUATRO";
        break;
        }
        case 3:
        {
        $numu = "TRES";
        break;
        }
        case 2:
        {
        $numu = "DOS";
        break;
        }
        case 1:
        {
        $numu = "UNO";
        break;
        }
        case 0:
        {
        $numu = "";
        break;
        }
        }
        return $numu;
}

function decena($numdero){

    if ($numdero >= 90 && $numdero <= 99)
    {
    $numd = "NOVENTA ";
    if ($numdero > 90)
    $numd = $numd."Y ".($this->unidad($numdero - 90));
    }
    else if ($numdero >= 80 && $numdero <= 89)
    {
    $numd = "OCHENTA ";
    if ($numdero > 80)
    $numd = $numd."Y ".($this->unidad($numdero - 80));
    }
    else if ($numdero >= 70 && $numdero <= 79)
    {
    $numd = "SETENTA ";
    if ($numdero > 70)
    $numd = $numd."Y ".($this->unidad($numdero - 70));
    }
    else if ($numdero >= 60 && $numdero <= 69)
    {
    $numd = "SESENTA ";
    if ($numdero > 60)
    $numd = $numd."Y ".($this->unidad($numdero - 60));
    }
    else if ($numdero >= 50 && $numdero <= 59)
    {
    $numd = "CINCUENTA ";
    if ($numdero > 50)
    $numd = $numd."Y ".($this->unidad($numdero - 50));
    }
    else if ($numdero >= 40 && $numdero <= 49)
    {
    $numd = "CUARENTA ";
    if ($numdero > 40)
    $numd = $numd."Y ".($this->unidad($numdero - 40));
    }
    else if ($numdero >= 30 && $numdero <= 39)
    {
    $numd = "TREINTA ";
    if ($numdero > 30)
    $numd = $numd."Y ".($this->unidad($numdero - 30));
    }
    else if ($numdero >= 20 && $numdero <= 29)
    {
    if ($numdero == 20)
    $numd = "VEINTE ";
    else
    $numd = "VEINTI".($this->unidad($numdero - 20));
    }
    else if ($numdero >= 10 && $numdero <= 19)
    {
    switch ($numdero){
    case 10:
    {
    $numd = "DIEZ ";
    break;
    }
    case 11:
    {
    $numd = "ONCE ";
    break;
    }
    case 12:
    {
    $numd = "DOCE ";
    break;
    }
    case 13:
    {
    $numd = "TRECE ";
    break;
    }
    case 14:
    {
    $numd = "CATORCE ";
    break;
    }
    case 15:
    {
    $numd = "QUINCE ";
    break;
    }
    case 16:
    {
    $numd = "DIECISEIS ";
    break;
    }
    case 17:
    {
    $numd = "DIECISIETE ";
    break;
    }
    case 18:
    {
    $numd = "DIECIOCHO ";
    break;
    }
    case 19:
    {
    $numd = "DIECINUEVE ";
    break;
    }
    }
    }
    else
    $numd = $this->unidad($numdero);
    return $numd;
}

function centena($numc){
    if ($numc >= 100)
    {
    if ($numc >= 900 && $numc <= 999)
    {
    $numce = "NOVECIENTOS ";
    if ($numc > 900)
    $numce = $numce.($this->decena($numc - 900));
    }
    else if ($numc >= 800 && $numc <= 899)
    {
    $numce = "OCHOCIENTOS ";
    if ($numc > 800)
    $numce = $numce.($this->decena($numc - 800));
    }
    else if ($numc >= 700 && $numc <= 799)
    {
    $numce = "SETECIENTOS ";
    if ($numc > 700)
    $numce = $numce.($this->decena($numc - 700));
    }
    else if ($numc >= 600 && $numc <= 699)
    {
    $numce = "SEISCIENTOS ";
    if ($numc > 600)
    $numce = $numce.($this->decena($numc - 600));
    }
    else if ($numc >= 500 && $numc <= 599)
    {
    $numce = "QUINIENTOS ";
    if ($numc > 500)
    $numce = $numce.($this->decena($numc - 500));
    }
    else if ($numc >= 400 && $numc <= 499)
    {
    $numce = "CUATROCIENTOS ";
    if ($numc > 400)
    $numce = $numce.($this->decena($numc - 400));
    }
    else if ($numc >= 300 && $numc <= 399)
    {
    $numce = "TRESCIENTOS ";
    if ($numc > 300)
    $numce = $numce.($this->decena($numc - 300));
    }
    else if ($numc >= 200 && $numc <= 299)
    {
    $numce = "DOSCIENTOS ";
    if ($numc > 200)
    $numce = $numce.($this->decena($numc - 200));
    }
    else if ($numc >= 100 && $numc <= 199)
    {
    if ($numc == 100)
    $numce = "CIEN ";
    else
    $numce = "CIENTO ".($this->decena($numc - 100));
    }
    }
    else
    $numce = $this->decena($numc);
    
    return $numce;
}

function miles($nummero){
    if ($nummero >= 1000 && $nummero < 2000){
    $numm = "MIL ".($this->centena($nummero%1000));
    }
    if ($nummero >= 2000 && $nummero <10000){
    $numm = $this->unidad(Floor($nummero/1000))." MIL ".($this->centena($nummero%1000));
    }
    if ($nummero < 1000)
    $numm = $this->centena($nummero);
    
    return $numm;
}

function decmiles($numdmero){
    if ($numdmero == 10000)
    $numde = "DIEZ MIL";
    if ($numdmero > 10000 && $numdmero <20000){
    $numde = $this->decena(Floor($numdmero/1000))."MIL ".($this->centena($numdmero%1000));
    }
    if ($numdmero >= 20000 && $numdmero <100000){
    $numde = $this->decena(Floor($numdmero/1000))." MIL ".($this->miles($numdmero%1000));
    }
    if ($numdmero < 10000)
    $numde = $this->miles($numdmero);
    
    return $numde;
}

function cienmiles($numcmero){
    if ($numcmero == 100000)
    $num_letracm = "CIEN MIL";
    if ($numcmero >= 100000 && $numcmero <1000000){
    $num_letracm = $this->centena(Floor($numcmero/1000))." MIL ".($this->centena($numcmero%1000));
    }
    if ($numcmero < 100000)
    $num_letracm = $this->decmiles($numcmero);
    return $num_letracm;
}

function millon($nummiero){
    if ($nummiero >= 1000000 && $nummiero <2000000){
    $num_letramm = "UN MILLON ".($this->cienmiles($nummiero%1000000));
    }
    if ($nummiero >= 2000000 && $nummiero <10000000){
    $num_letramm = $this->unidad(Floor($nummiero/1000000))." MILLONES ".($this->cienmiles($nummiero%1000000));
    }
    if ($nummiero < 1000000)
    $num_letramm = $this->cienmiles($nummiero);
    
    return $num_letramm;
}

function decmillon($numerodm){
    if ($numerodm == 10000000)
    $num_letradmm = "DIEZ MILLONES";
    if ($numerodm > 10000000 && $numerodm <20000000){
    $num_letradmm = $this->decena(Floor($numerodm/1000000))."MILLONES ".($this->cienmiles($numerodm%1000000));
    }
    if ($numerodm >= 20000000 && $numerodm <100000000){
    $num_letradmm = $this->decena(Floor($numerodm/1000000))." MILLONES ".($this->millon($numerodm%1000000));
    }
    if ($numerodm < 10000000)
    $num_letradmm = $this->millon($numerodm);
    
    return $num_letradmm;
}

function cienmillon($numcmeros){
    if ($numcmeros == 100000000)
    $num_letracms = "CIEN MILLONES";
    if ($numcmeros >= 100000000 && $numcmeros <1000000000){
    $num_letracms = $this->centena(Floor($numcmeros/1000000))." MILLONES ".($this->millon($numcmeros%1000000));
    }
    if ($numcmeros < 100000000)
    $num_letracms = $this->decmillon($numcmeros);
    return $num_letracms;
    }
    
function milmillon($nummierod){
    if ($nummierod >= 1000000000 && $nummierod <2000000000){
    $num_letrammd = "MIL ".($this->cienmillon($nummierod%1000000000));
    }
    if ($nummierod >= 2000000000 && $nummierod <10000000000){
    $num_letrammd = $this->unidad(Floor($nummierod/1000000000))." MIL ".($this->cienmillon($nummierod%1000000000));
    }
    if ($nummierod < 1000000000)
    $num_letrammd = $this->cienmillon($nummierod);
    
    return $num_letrammd;
}


function convertir($numero){
    $num = str_replace(",","",$numero);
    $num = number_format($num,2,'.','');
    $cents = substr($num,strlen($num)-2,strlen($num)-1);
    $num = (int)$num;
    
    $numf = $this->milmillon($num);
    
    return $numf." PESOS ".$cents."/100 MXN";
}


}



/*require('fpdf.php');

class PDF extends FPDF {

var $tablewidths;
var $footerset;


}*/
?>