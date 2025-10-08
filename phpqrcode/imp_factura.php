<?php
require_once('fpdf153/fpdf.php');
require_once("numlet.php");	
require_once("phpqrcode/phpqrcode.php");
function generaFacturaPdf($plaza,$cvefact,$mostrar=0){
	global $base,$array_tipo_pago;
	$color="#F0F0F0";
	$pdf = new PDF_MC_Table('P','mm','LETTER');
	$pdf->AddPage();
	$res = mysql_query("SELECT * FROM facturas WHERE plaza='".$plaza."' AND cve='".$cvefact."'");
	$row = mysql_fetch_array($res);
	$pdf->SetFont("Arial","",8);
	$pdf->SetFillColor(240,240,240);
	$res1 = mysql_query("SELECT * FROM datosempresas WHERE plaza='".$row['plaza']."'");
	$row1 = mysql_fetch_array($res1);
	if($row1['logoencabezado']==1){
		if(file_exists("logos/logo".$plaza.".jpg")) $pdf->Image("logos/logo".$plaza.".jpg",10,5,197.5,40);
		$pdf->SetXY(122.5,45);
	}
	else{
		if(file_exists("logos/logo".$plaza.".jpg")) $pdf->Image("logos/logo".$plaza.".jpg",10,5,100,35);
		$pdf->SetXY(122.5,5);
	}
	if($row['estatus']=='C'){
		if(file_exists("images/cancelado.jpg")) $pdf->Image("images/cancelado.jpg",10,45,190,200);
	}
	if($row['tipo_factura']==1)
		$pdf->Cell(85,6.5,"HONORARIOS",1,0,"C",1);
	else
		$pdf->Cell(85,6.5,"FACTURA",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(85,4,$row['uuid'],0,0,"C",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FOLIO',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['cve'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FECHA EMISION',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['fecha'].' '.$row['hora'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FECHA TIMBRE',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['fechatimbre'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'CERTIFICADO EMISOR',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['seriecertificado'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'CERTIFICADO SAT',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['seriecertificadosat'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'METODO DE PAGO',0,0,"L",0);
	$pdf->Cell(52,4,':'.$array_tipo_pago[$row['tipo_pago']],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FORMA DE PAGO',0,0,"L",0);
	$pdf->Cell(52,4,':PAGO EN UNA SOLA EXHIBICION',0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'EFECTOS FISCALES AL PAGO',0,0,"L",0);
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->Cell(100,6.5,"DATOS DEL EMISOR",1,0,"C",1);
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['nombre'],0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['rfc'],0,0,"L",0);
	$re=$row1['rfc'];
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['calle'].' '.$row1['numexterior'].' '.$row1['numinterior'],0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['colonia'],0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['localidad'].' C.P. '.$row1['codigopostal'].', '.$row1['estado'].'',0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,'Lugar de Expedicion: '.$row1['municipio'].', '.$row1['estado'].'',0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,$row1['regimen'],0,0,"L",0);
	$pdf->Ln();
	$y2=$pdf->GetY();
	$pdf->SetXY(110,$y);
	$pdf->Cell(97.5,6.5,"DATOS DEL RECEPTOR",1,0,"C",1);
	$res1 = mysql_query("SELECT * FROM clientes WHERE cve='".$row['cliente']."'");
	$row1 = mysql_fetch_array($res1);
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->MultiCell(97.5,4,$row1['nombre'],0,"L",0);
	//$pdf->Ln();
	$pdf->SetX(110);
	$pdf->Cell(97.5,4,$row1['rfc'],0,0,"L",0);
	$rr=$row1['rfc'];
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->Cell(97.5,4,$row1['calle'].' '.$row1['numexterior'].' '.$row1['numinterior'],0,0,'L',0);
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->Cell(97.5,4,$row1['colonia'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(110);
	if($row1['localidad']!="")
		$pdf->Cell(97.5,4,$row1['localidad'].' C.P. '.$row1['codigopostal'].', '.$row1['municipio'].', '.$row1['estado'],0,0,"L",0);
	else
		$pdf->Cell(97.5,4,'C.P. '.$row1['codigopostal'].', '.$row1['municipio'].', '.$row1['estado'],0,0,"L",0);
	$pdf->Ln();
	//$pdf->SetX(110);
	//$pdf->Cell(97.5,4,'MEXICO',0,0,"L",0);
	//$pdf->Ln();
	if($y2>$pdf->GetY()) $pdf->SetXY(10,$y2);
	$pdf->MultiCell(190,5,"ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI",0,'C',0);
	$pdf->Cell(30,6.5,"UNIDAD",1,0,"C",1);
	$pdf->Cell(77.5,6.5,"CONCEPTO",1,0,"C",1);
	$pdf->Cell(30,6.5,"CANTIDAD",1,0,"C",1);
	$pdf->Cell(30,6.5,"PRECIO",1,0,"C",1);
	$pdf->Cell(30,6.5,"IMPORTE",1,0,"C",1);
	$pdf->Ln();
	$y=$pdf->GetY();
	$res2 = mysql_query("SELECT * FROM facturasmov WHERE plaza='".$plaza."' AND cvefact='".$cvefact."'");
	while($row2 = mysql_fetch_array($res2)){
		$pdf->SetXY(10,$y);
		$pdf->Cell(30,4,$row2['unidad'],0,0,"C",0);
		$y3=$pdf->GetY();
		if($y!=$y3) $y=$y3;
		$pdf->MultiCell(77.5,4,$row2['concepto'],0,"J",0);
		$y2=$pdf->GetY();
		$pdf->SetXY(117.5,$y);
		$pdf->Cell(30,4,$row2['cantidad'],0,0,"R",0);
		$pdf->Cell(30,4,$row2['precio'],0,0,"R",0);
		$pdf->Cell(30,4,$row2['importe'],0,0,"R",0);
		$y=$y2;
	}
	$pdf->SetXY(10,$y);
	if($row['carta_porte']==1){
		$pdf->Ln();
		$pdf->Ln();
		$pdf->Cell(26,4,"LOAD:",0,0,'L',0);
		$pdf->MultiCell(111.5,4,$row['load_cliente'],0,'L',0);
		$pdf->Cell(26,4,"CLIENTE:",0,0,'L',0);
		$pdf->MultiCell(111.5,4,$row['nombre_cliente'],0,'L',0);
		$pdf->Cell(26,4,"DIRECCION:",0,0,'L',0);
		$pdf->MultiCell(111.5,4,$row['direccion_cliente'],0,'L',0);
		$pdf->Cell(26,4,"TIPO DE PAGO:",0,0,'L',0);
		$pdf->MultiCell(111.5,4,$row['tipopago_cliente'],0,'L',0);
		//if($row['tipopago_cliente']>0){
			//$pdf->Cell(26,4,"BANCO:",0,0,'L',0);
			//$pdf->MultiCell(111.5,4,$row['banco_cliente'],0,'L',0);
			$pdf->Cell(26,4,"CUENTA:",0,0,'L',0);
			$pdf->MultiCell(111.5,4,$row['cuenta_cliente'],0,'L',0);
		//}
	}
	$pdf->Cell(197.5,4,"",'T',0,'C',0);
	//$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->Ln();
	$pdf->MultiCell(137.5,4,numlet($row['total']),0,'L',0);
	$pdf->Ln();
	if($pdf->GetY() < $y) $y=$pdf->GetY();
	$pdf->Cell(26,4,"OBSERVACIONES:",0,0,'L',0);
	$pdf->MultiCell(111.5,4,$row['obs'],0,'L',0);
	if($pdf->GetY() < $y) $y=$pdf->GetY();
	$y2=$pdf->GetY();
	$pdf->SetXY(147.5,$y);
	$pdf->Cell(30,6.5,"SUBTOTAL",1,0,"R",1);
	$pdf->Cell(30,6.5,$row['subtotal'],0,0,"R",0);
	$pdf->Ln();
	$pdf->SetX(147.5);
	$pdf->Cell(30,6.5,"I.V.A. 16%",1,0,"R",1);
	$pdf->Cell(30,6.5,$row['iva'],0,0,"R",0);
	$pdf->Ln();
	/*$pdf->SetX(147.5);
	$pdf->Cell(30,6.5,"TOTAL",1,0,"R",1);
	$pdf->Cell(30,6.5,number_format($row['total']+$row['iva_retenido']+$row['isr_retenido'],2,".",""),0,0,"R",0);
	$pdf->Ln();
	$pdf->SetX(147.5);
	$pdf->Cell(30,6.5,"RET. I.V.A. ".round($row['por_iva_retenido'],2)."%",1,0,"R",1);
	$pdf->Cell(30,6.5,$row['iva_retenido'],0,0,"R",0);
	$pdf->Ln();
	$pdf->SetX(147.5);
	$pdf->Cell(30,6.5,"RET. I.S.R. ".round($row['por_isr_retenido'],2)."%",1,0,"R",1);
	$pdf->Cell(30,6.5,$row['isr_retenido'],0,0,"R",0);
	$pdf->Ln();*/
	$pdf->SetX(147.5);
	$pdf->Cell(30,6.5,"TOTAL",1,0,"R",1);
	$pdf->Cell(30,6.5,$row['total'],0,0,"R",0);
	$tt=number_format($row['total'],6,".","");
	if($y2<$pdf->GetY()) $y2=$pdf->GetY();
	$pdf->SetY($y2);
	$pdf->Ln();
	$pdf->Cell(190,4,"IMPUESTO RETENIDO DE CONFORMIDAD CON LA LEY DEL IMPUESTO AL VALOR AGREGADO",0,0,'C');
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->SetX(45);
	$pdf->Cell(162.5,6.5,"CADENA ORIGINAL",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->MultiCell(162.5,4,$row['cadenaoriginal'],0,"J",0);
	//$pdf->Ln();
	$pdf->SetX(45);
	$pdf->Cell(162.5,6.5,"SELLO DIGITAL EMISOR",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->MultiCell(162.5,4,$row['sellodocumento'],0,"J",0);
	//$pdf->Ln();
	$pdf->SetX(45);
	$pdf->Cell(162.5,6.5,"SELLO DIGITAL SAT",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->MultiCell(162.5,4,$row['sellotimbre'],0,"J",0);
	if($row['estatus']=='C'){
		//$pdf->Ln();
		$pdf->SetX(45);
		$pdf->Cell(162.5,6.5,"FOLIO CANCELACION",1,0,"C",1);
		$pdf->Ln();
		$pdf->SetX(45);
		$pdf->MultiCell(162.5,4,$row['respuesta2'],0,"J",0);
	}
	if($empresa==21){
		$pdf->MultiCell(190,4,"
		
		
		ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI",0,'C');
	}
	QRcode::png("?re=".$re."&rr=".$rr."&tt=".$tt."&id=".$row['uuid'],"cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png","L",4,0);
	if(file_exists("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png")) $pdf->Image("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png",10,$y,34,34);
	if($row['estatus']=='C'){
		$pdf->Output("cfdi/comprobantes/facturac_".$row['plaza']."_".$row['cve'].".pdf","F");
	}
	else{
		$pdf->Output("cfdi/comprobantes/factura_".$row['plaza']."_".$row['cve'].".pdf","F");
	}
	if(file_exists("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png")) unlink("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png");
	
	
	if($mostrar==1){
		$pdf->Output();
	}
}
?>