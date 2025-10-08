<?php
require_once('fpdf/fpdf.php');
require_once("numlet.php");	
require_once("phpqrcode/phpqrcode.php");

function generaFacturaPdf($plaza,$cvefact,$mostrar=0,$tipodocumento=1){
	global $base,$array_tipo_pago,$array_forma_pago;
	$array_regimensat=array();
	$res = mysql_query("SELECT * FROM regimen_sat ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)) $array_regimensat[$row['clave']] = $row['nombre'];

	$array_usocfdi=array();
	$res = mysql_query("SELECT * FROM usocfdi_sat ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)) $array_usocfdi[$row['clave']] = $row['nombre'];

	$array_tipo_pago = array();
	$res = mysql_query("SELECT * FROM tipos_pago_factura ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)) $array_tipo_pago[$row['cve']] = $row['nombre'];

	$tabla='facturas';
	$campo='factura';
	$archivo='factura';
	$nombre='FACTURA';
	$cfdi = 'cfdi';
	$tipocomprobante = 'INGRESO';
	if($tipodocumento == 2){
		$tabla='notascredito';
		$campo='notacredito';
		$archivo='nc';
		$nombre='NOTA DE CREDITO';
		$cfdi = 'cfdinc';
		$tipocomprobante = 'EGRESO';
	}
	$arch = 'cfdi/comprobantes/'.$cfdi.'_'.$plaza.'_'.$cvefact.'.xml';
	$cadena= file_get_contents($arch);
	$dom = new DOMDocument;
	$dom->loadXML($cadena);
	$arreglo = _xmlToArray($dom);
	$color="#F0F0F0";
	$pdf = new PDF_MC_Table('P','mm','LETTER');
	$pdf->AddPage();
	$res = mysql_query("SELECT * FROM $tabla WHERE plaza='".$plaza."' AND cve='".$cvefact."'");
	$row = mysql_fetch_array($res);
	$cuenta_pago = 'NO APLICA';
	$pdf->SetFont("Arial","",8);
	$pdf->SetFillColor(240,240,240);
	$res1 = mysql_query("SELECT * FROM plazas WHERE cve='".$row['plaza']."'");
	$row1 = mysql_fetch_array($res1);
	$numeroPlaza=$row1['numero'];
	$regimen = $row1['regimensat'];
	$res1 = mysql_query("SELECT * FROM datosempresas WHERE plaza='".$row['plaza']."'");
	$row1 = mysql_fetch_array($res1);
	if($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@nombre']!=''){
		if($arreglo['cfdi:Comprobante'][0]['@NumCtaPago']!='')
			$cuenta_pago=$arreglo['cfdi:Comprobante'][0]['@NumCtaPago'];
	
		$row1['regimen']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:RegimenFiscal'][0]['@Regimen']);
		$row1['nombre']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@nombre']);
		$row1['rfc']= $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@rfc'];
		$row1['calle']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@calle']);
		$row1['numexterior']= $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@noExterior'];
		$row1['numinterior']= $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@noInterior'];
		$row1['colonia']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@colonia']);
		$row1['localidad']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@localidad']);
		$row1['municipio']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@municipio']);
		$row1['estado']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@estado']);
		$row1['codigopostal']= $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['cfdi:DomicilioFiscal'][0]['@codigoPostal'];
	}
	else{
		$cuenta_pago=$row['cuenta_cliente'];
		$row1['nombre']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@Nombre']);
		if($row['datosfacturas'] == ''){
			$datosfacturas['emisor'] = array(
				'regimen' => utf8_encode($row1['regimen']),
				'nombre' => utf8_encode($row1['nombre']),
				'rfc' => $row1['rfc'],
				'calle' => utf8_encode($row1['calle']),
				'numexterior' => utf8_encode($row1['numexterior']),
				'numinterior' => utf8_encode($row1['numinterior']),
				'colonia' => utf8_encode($row1['colonia']),
				'localidad' => utf8_encode($row1['localidad']),
				'municipio' => utf8_encode($row1['municipio']),
				'estado' => utf8_encode($row1['estado']),
				'codigopostal' => $row1['codigopostal']
			);
		}
		else{
			$datosfacturas = json_decode($row['datosfacturas'], true);
			$row1['regimen']= utf8_decode($datosfacturas['emisor']['regimen']);
			//$row1['nombre']= utf8_decode($datosfacturas['emisor']['nombre']);
			$row1['rfc']= utf8_decode($datosfacturas['emisor']['rfc']);
			$row1['calle']= utf8_decode($datosfacturas['emisor']['calle']);
			$row1['numexterior']= utf8_decode($datosfacturas['emisor']['numexterior']);
			$row1['numinterior']= utf8_decode($datosfacturas['emisor']['numinterior']);
			$row1['colonia']= utf8_decode($datosfacturas['emisor']['colonia']);
			$row1['localidad']= utf8_decode($datosfacturas['emisor']['localidad']);
			$row1['municipio']= utf8_decode($datosfacturas['emisor']['municipio']);
			$row1['estado']= utf8_decode($datosfacturas['emisor']['estado']);
			$row1['codigopostal']= utf8_decode($datosfacturas['emisor']['codigopostal']);
		}
	}
	
	$row1['rfc']= $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@Rfc'];
	if($tipodocumento != 2){
		if($row['rfc_factura'] == ''){
			mysql_query("UPDATE facturas SET rfc_factura = '".$row1['rfc']."' WHERE plaza='".$plaza."' AND cve='".$cvefact."'");
		}
	}
	$re=$row1['rfc'];
	$res2 = mysql_query("SELECT * FROM clientes WHERE cve='".$row['cliente']."'");
	$row2 = mysql_fetch_array($res2);
	$row2['rfc'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@Rfc'];
	$row2['nombre'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@Nombre'];
	$row2['codigopostal'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@DomicilioFiscalReceptor'];
	$row2['regimensat'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@RegimenFiscalReceptor'];
	/*if($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@nombre'] != '')
	{
		$row2['nombre']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@nombre']);
		$row2['rfc']= $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@rfc'];
		$row2['calle']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@calle']);
		$row2['numexterior']= $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@noExterior'];
		$row2['numinterior']= $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@noInterior'];
		$row2['colonia']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@colonia']);
		$row2['localidad']= $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@localidad'];
		$row2['municipio']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@municipio']);
		$row2['estado']= utf8_decode($arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@estado']);
		$row2['codigopostal']= $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['cfdi:Domicilio'][0]['@codigoPostal'];
	}
	else{
		if($row['rfc_cli'] != ''){
			$row2['nombre']= $row['nombre_cli'];
			$row2['rfc']= $row['rfc_cli'];
			$row2['calle']= $row['calle_cli'];
			$row2['numexterior']= $row['numext_cli'];
			$row2['numinterior']= $row['numint_cli'];
			$row2['colonia']= $row['colonia_cli'];
			$row2['localidad']= $row['localidad_cli'];
			$row2['municipio']= $row['municipio_cli'];
			$row2['estado']= $row['estado_cli'];
			$row2['codigopostal']= $row['cp_cli'];
		}
		elseif($row['datosfacturas'] == '' || $row['datosfacturas'] == 'null'){
			$datosfacturas['receptor'] = array(
				'nombre' => utf8_encode($row2['nombre']),
				'rfc' => $row2['rfc'],
				'calle' => utf8_encode($row2['calle']),
				'numexterior' => utf8_encode($row2['numexterior']),
				'numinterior' => utf8_encode($row2['numinterior']),
				'colonia' => utf8_encode($row2['colonia']),
				'localidad' => utf8_encode($row2['localidad']),
				'municipio' => utf8_encode($row2['municipio']),
				'estado' => utf8_encode($row2['estado']),
				'codigopostal' => $row2['codigopostal']
			);
		}
		else{
			$datosfacturas = json_decode($row['datosfacturas'], true);
			$row2['nombre']= utf8_decode($datosfacturas['receptor']['nombre']);
			$row2['rfc']= utf8_decode($datosfacturas['receptor']['rfc']);
			$row2['calle']= utf8_decode($datosfacturas['receptor']['calle']);
			$row2['numexterior']= utf8_decode($datosfacturas['receptor']['numexterior']);
			$row2['numinterior']= utf8_decode($datosfacturas['receptor']['numinterior']);
			$row2['colonia']= utf8_decode($datosfacturas['receptor']['colonia']);
			$row2['localidad']= utf8_decode($datosfacturas['receptor']['localidad']);
			$row2['municipio']= utf8_decode($datosfacturas['receptor']['municipio']);
			$row2['estado']= utf8_decode($datosfacturas['receptor']['estado']);
			$row2['codigopostal']= utf8_decode($datosfacturas['receptor']['codigopostal']);
		}
	}*/
	if($row['datosfacturas'] == ''){
		mysql_query("UPDATE $tabla SET datosfacturas='".addslashes(json_encode($datosfacturas))."' WHERE plaza='".$plaza."' AND cve='".$cvefact."'");
	}
	if($row1['logoencabezado']==1){
		if(file_exists("logos/logo".$plaza.".jpg")) $pdf->Image("logos/logo".$plaza.".jpg",10,5,197.5,40);
		$pdf->SetXY(122.5,45);
	}
	else{
		if(file_exists("logos/logo".$plaza.".jpg")) $pdf->Image("logos/logo".$plaza.".jpg",10,10,50,40);
		$pdf->SetXY(122.5,5);
	}
	if($row['estatus']=='C'){
		if(file_exists("img/cancelado.jpg")) $pdf->Image("img/cancelado.jpg",10,45,190,200);
	}
	
	$pdf->SetXY(65,10);
	$pdf->MultiCell(80,5,$row1['nombre'].'
'.$row1['rfc'].'
'.$row1['calle'].' '.$row1['numexterior'].' '.$row1['numinterior'].'
'.$row1['colonia'].' 
'.$row1['municipio'].', '.$row1['estado'].'
MEXICO');
	$y = $pdf->GetY();
	$pdf->SetXY(170,14);
	$pdf->Cell(20,4,'SERIE:  ',0,0,'R');
	$pdf->Cell(20,4,$row['serie'],0,0,'L');
	$pdf->Ln();
	$pdf->SetX(170);
	$pdf->Cell(20,4,$nombre.':  ',0,0,'R');
	$pdf->Cell(20,4,$row['folio'],0,0,'L');
	$pdf->Ln();
	$pdf->SetX(170);
	$pdf->Cell(20,4,'TIPO DE COMPROBANTE:  ',0,0,'R');
	$pdf->Cell(20,4,$tipocomprobante,0,0,'L');
	$pdf->Ln();
	$pdf->Ln();
	$pdf->SetX(170);
	$pdf->Cell(20,4,'FECHA:  ',0,0,'R');
	$pdf->Cell(20,4,date('j/n/Y', strtotime($row['fecha'])),0,0,'L');
	$pdf->Ln();
	$pdf->SetX(170);
	$pdf->Cell(20,4,'HORA:  ',0,0,'R');
	$pdf->Cell(20,4,$row['hora'],0,0,'L');
	$pdf->Ln();
	if($y<$pdf->GetY()){
		$y = $pdf->GetY();
	}
	$pdf->SetXY(150, 50);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(60,5,'Documento Válido',1);
	$pdf->Ln();
	$pdf->Cell(90,4,'R E C E P T O R',0,0,'C');
	$pdf->Ln();
	$y = $pdf->GetY();
	$pdf->Cell(20,4,'Cliente:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(70,4,$row2['nombre']);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'R.F.C.:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(70,4,$row2['rfc']);
	$rr=$row2['rfc'];
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'CP:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(70,4,$row2['codigopostal']);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'USO CFDI:');
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(20,4,$row2['usocfdi']);
	$pdf->Ln();
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'REGIMEN:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(80,4,$row2['regimensat'].' '.$array_regimensat[$row2['regimensat']]);
	$pdf->Ln();
	$pdf->Ln();
	$y2=$pdf->GetY();

	$pdf->SetXY(110,$y);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'REGIMEN:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(80,4,$regimen.' '.$array_regimensat[$regimen]);
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(35,4,'LUGAR DE EXPEDICION:');
	$pdf->SetFont('Arial','',8);
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->MultiCell(90,4,$arreglo['cfdi:Comprobante'][0]['@LugarExpedicion']);
	
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(50,4,'RFC PROVEEDOR CERTIFICADO:');
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(90,4,$arreglo['cfdi:Comprobante'][0]['cfdi:Complemento'][0]['tfd:TimbreFiscalDigital'][0]['@RfcProvCertif']);
	$pdf->Ln();
	if($y2<$pdf->GetY()){
		$y2 = $pdf->GetY();
	}

	$pdf->SetXY(10,$y2);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,"No. Ident.",1,0,"C",0);
	//$pdf->Cell(10,4,"Placa",1,0,"C",0);
	$pdf->Cell(10,4,"Cant.",1,0,"C",0);
	$pdf->Cell(20,4,"Clave Unidad",1,0,"C",0);
	$pdf->Cell(15,4,"Unidad",1,0,"C",0);
	$pdf->Cell(20,4,"Clave Prod.",1,0,"C",0);
	$pdf->Cell(30,4,"Concepto / Descripción",1,0,"C",0);
	$pdf->Cell(30,4,"Valor Unit",1,0,"C",0);
	$pdf->Cell(22.5,4,"Impuestos",1,0,"C",0);
	$pdf->Cell(30,4,"Importe",1,0,"C",0);
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->SetFont('Arial','',7);
	$res2 = mysql_query("SELECT * FROM ".$tabla."mov WHERE plaza='".$plaza."' AND cvefact='".$cvefact."'");
	while($row2 = mysql_fetch_array($res2)){
		//$row3 = mysql_fetch_assoc(mysql_query("SELECT placa FROM cobro_engomado WHERE plaza={$plaza} AND cve='{$row2['ticket']}'"));
		$row2['unidad'] = ($row2['claveunidadsat'] == 'E48') ? 'Servicio' : 'Pieza';
		$pdf->SetXY(10,$y);
		$pdf->MultiCell(20,4,$row2['no_ident'],1,"C",0);
		$pdf->SetXY(30,$y);
		//$pdf->Cell(10,4,$row3['placa'],1,0,"R",0);
		$pdf->Cell(10,4,round($row2['cantidad'],0),1,0,"R",0);
		$pdf->Cell(20,4,$row2['claveunidadsat'],1,0,"C",0);
		$pdf->Cell(15,4,$row2['unidad'],1,0,"C",0);
		$pdf->Cell(20,4,$row2['claveprodsat'],1,0,"C",0);
		$y3=$pdf->GetY();
		if($y!=$y3) $y=$y3;
		$pdf->MultiCell(30,4,$row2['concepto'],1,"J",0);
		$y2=$pdf->GetY();
		$pdf->SetXY(125,$y);
		$pdf->Cell(30,4,number_format(round($row2['precio'],3),3,'.',''),1,0,"R",0);
	
		$pdf->Cell(22.5,4,number_format(round($row2['importe_iva'],3),3,'.',''),1,0,"R",0);
		$pdf->Cell(30,4,number_format(round($row2['importe'],3),3,'.',''),1,0,"R",0);
		$y=$y2;
	}
	$pdf->SetXY(10,$y);
	$pdf->Cell(157.5,4,"IMPORTE CON LETRA",'1',0,'C',0);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'SUBTOTAL:',1);
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(20,4,number_format($row['subtotal'],2,'.',''),1,0,'R');
	$pdf->Ln();
	$pdf->Cell(157.5,4," ",'LR',0,'C',0);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'I.V.A. 16%:',1);
	$pdf->Cell(20,4,number_format($row['iva'],2,'.',''),1,0,'R');
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->Cell(157.5,4,'','LR',0,'L',0);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(20,4,'TOTAL:',1);
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(20,4,number_format($row['total'],2,'.',''),1,0,'R');
	$pdf->Ln();
	$pdf->Cell(157.5,24,'','LBR',0,'L',0);
	$pdf->Cell(40,24,'','LBR',0,'L',0);
	$pdf->Ln();
	$y2=$pdf->GetY();
	$pdf->SetXY(13,$y);
	$metodo_pago = ($row['forma_pago']==0) ? 'PUE PAGO EN UNA SOLA EXHIBICION' : 'PPD PAGO EN PARCIALIDADES O DIFERIDO';
	$condiciones_pago = ($row['condiciones_pago'] == 0) ? 'CONTADO' : 'CREDITO';
	$pdf->MultiCell(157,4,numlet($row['total']).'
Metodo pago: '.$metodo_pago.'
Forma pago: '.$array_tipo_pago[$row['tipo_pago']].'
Condiciones de Pago: '.$condiciones_pago.'
MONEDA: MXN');
	$pdf->SetX(13);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(157,4,'Este documento es una representación impresa de un CFDI');
	$pdf->Ln();
	$pdf->SetX(13);
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(157,4,' ');
	$y2+=4;

	$pdf->SetXY(10,$y2);
	$tt=number_format($row['total'],6,".","");
	QRcode::png("?re=".$re."&rr=".$rr."&tt=".$tt."&id=".$row['uuid'],"cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png","L",4,0);
	if(file_exists("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png")) $pdf->Image("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png",20,$y2,34,34);
	$pdf->SetXY(60,$y2);
	$pdf->Cell(26,4,"OBSERVACIONES:",0,0,'L',0);
	$pdf->Ln();
	$pdf->SetX(60);
	$pdf->MultiCell(130,4,$row['obs'],0,'L',0);
	
	$pdf->SetXY(80, $y2+35);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(100,4,'Folio fiscal: '.$row['uuid']);
	$pdf->Ln();
	$pdf->SetX(80);
	$pdf->Cell(100,4,'SERIE DEL SELLO: '.$row['seriecertificado']);
	$pdf->Ln();
	$pdf->Cell(70,4,'No de Serie del Certificado del SAT:          ',0,0,'R');
	$pdf->Cell(100,4,$row['seriecertificadosat']);
	$pdf->Ln();
	$pdf->Cell(70,4,'Fecha y hora de certificación:          ',0,0,'R');
	$pdf->Cell(100,4,date('j/n/Y - H:i:s', strtotime($row['fechatimbre'])));
	$pdf->Ln();
	$pdf->Cell(162.5,4,"Sello digital del CFDI",0,0,"L");
	$pdf->Ln();
	$pdf->SetX(20);
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(180,3,$row['sellodocumento'],0,"C",0);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(162.5,4,"Sello del SAT",0,0,"L");
	$pdf->Ln();
	$pdf->SetX(20);
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(180,3,$row['sellotimbre'],0,"C",0);
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(162.5,4,"Cadena original del complemento de certificación digital del SAT",0,0,"L");
	$pdf->Ln();
	$pdf->SetX(20);
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(180,3,$row['cadenaoriginal'],0,"C",0);
	if($row['estatus']=='C'){
		//$pdf->Ln();
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(162.5,4,"Folio de Cancelación",0,0,"L");
		$pdf->Ln();
		$pdf->SetX(20);
		$pdf->SetFont('Arial','',8);
		$pdf->MultiCell(180,3,$row['respuesta2'],0,"C",0);
	}
	
	if($mostrar==1){
		$pdf->Output();
	}
	else{
		if($row['estatus']=='C'){
			$pdf->Output("cfdi/comprobantes/".$archivo."c_".$row['plaza']."_".$row['cve'].".pdf","F");
		}
		else{
			$pdf->Output("cfdi/comprobantes/".$archivo."_".$row['plaza']."_".$row['cve'].".pdf","F");
		}
	}
	if(file_exists("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png")) unlink("cfdi/comprobantes/barcode_".$row['plaza'].'_'.$row['cve'].".png");
}

function generaPagoPdf($plaza, $cvefact, $mostrar=0){
	global $base,$array_tipo_pago,$array_unidadsat;
	$array_regimensat=array();
	$res = mysql_query("SELECT * FROM regimen_sat ORDER BY nombre");
	while($row = mysql_fetch_assoc($res)) $array_regimensat[$row['clave']] = $row['nombre'];
	$array_usocfdi=array();
	$res = mysql_query("SELECT * FROM usocfdi_sat ORDER BY nombre");
	while($row=mysql_fetch_array($res)) $array_usocfdi[$row['cve']] = $row['nombre'];
	if($empresa==21) $array_tipo_pago[2]='TRANSFERENCIA ELECTRONICA';
	$res = mysql_query("SELECT * FROM formapagosat ORDER BY nombre DESC");
	while($row=mysql_fetch_array($res)){
		$array_formapagosat[$row['cve']] = $row['nombre'];
	}
	$arch = 'cfdi/comprobantes/cfdip_'.$plaza.'_'.$cvefact.'.xml';
	$cadena= file_get_contents($arch);
	$dom = new DOMDocument;
	$dom->loadXML($cadena);
	$arreglo = _xmlToArray($dom);
	$color="#F0F0F0";
	$pdf = new PDF_MC_Table('P','mm','LETTER');
	$pdf->AddPage();
	$pdf->SetTextColor(200,200,200);
	$pdf->SetFont("Arial","",50);
	$pdf->SetY(60);
	$pdf->Cell(190,20,'RECIBO DE PAGO');
	$pdf->SetTextColor(0,0,0);
	$pdf->SetXY(10,10);
	$res = mysql_query("SELECT * FROM pagosfacturas WHERE plaza='{$plaza}' AND cve='{$cvefact}'");
	$row = mysql_fetch_array($res);
	$pdf->SetFont("Arial","",8);
	$pdf->SetFillColor(240,240,240);
	$res1 = mysql_query("SELECT * FROM plazas WHERE cve='".$row['plaza']."'");
	$row1 = mysql_fetch_array($res1);
	$numeroPlaza=$row1['numero'];
	$regimen = $row1['regimensat'];
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
	$pdf->Cell(85,6.5,"PAGO",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(85,4,$row['uuid'],0,0,"C",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FOLIO',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['folio'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FECHA EMISION',0,0,"L",0);
	$pdf->Cell(52,4,':'.$row['fecha'].'T'.$row['hora'],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FECHA TIMBRE',0,0,"L",0);
	$pdf->Cell(52,4,':'.str_replace(' ', 'T', $row['fechatimbre']),0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'FECHA PAGO',0,0,"L",0);
	$pdf->Cell(52,4,':'.str_replace(' ', 'T', $row['fecha_pago']),0,0,"L",0);
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
	$pdf->Cell(33,4,'FORMA DE PAGO',0,0,"L",0);
	$pdf->Cell(52,4,':'.$array_formapagosat[$row['formapago']],0,0,"L",0);
	$pdf->Ln();
	$pdf->SetX(122.5);
	$pdf->SetX(122.5);
	$pdf->Cell(33,4,'EFECTOS FISCALES AL PAGO',0,0,"L",0);
	$pdf->Ln();
	$y=$pdf->GetY();
	
		
	$pdf->Cell(100,6.5,"DATOS DEL EMISOR",1,0,"C",1);
	$pdf->Ln();
	$pdf->MultiCell(100,4,$arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@Nombre'],0,"L",0);
	$pdf->Cell(100,4,$arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@Rfc'],0,0,"L",0);
	$re=$arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@Rfc'];
	$row1['regimensat'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Emisor'][0]['@RegimenFiscal'];
	$pdf->Ln();
	$pdf->Ln();
	$pdf->Cell(100,4,'Regimen: '.$row1['regimensat'].' '.$array_regimensat[$row1['regimensat']],0,0,"L",0);
	$pdf->Ln();
	$pdf->Cell(100,4,'RFC Proveedor Certificado: '.$arreglo['cfdi:Comprobante'][0]['cfdi:Complemento'][0]['tfd:TimbreFiscalDigital'][0]['@RfcProvCertif'],0,0,"L",0);
	$pdf->Ln();
	$y2=$pdf->GetY();
	$pdf->SetXY(110,$y);
	$pdf->Cell(97.5,6.5,"DATOS DEL RECEPTOR",1,0,"C",1);
	$row1['nombre'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@Nombre'];
	$row1['rfc'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@Rfc'];
	$row1['usocfdi'] = $arreglo['cfdi:Comprobante'][0]['cfdi:Receptor'][0]['@UsoCFDI'];
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->MultiCell(97.5,4,$row1['nombre'],0,"L",0);
	//$pdf->Ln();
	$pdf->SetX(110);
	$pdf->Cell(97.5,4,$row1['rfc'],0,0,"L",0);
	$rr=$row1['rfc'];
	$pdf->Ln();
	$pdf->SetX(110);
	$pdf->Cell(97.5,4,$row1['usocfdi'].' '.$array_usocfdi[$row1['usocfdi']],0,0,"L",0);
	$pdf->Ln();
	if($y2>$pdf->GetY()) $pdf->SetXY(5,$y2);
	$pdf->MultiCell(190,5,"ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI",0,'C',0);
	$pdf->SetFont('Arial','',6);
	$pdf->Cell(27.5,4,"FACTURA",1,0,"C",1);
	$pdf->Cell(50,4,"UUID FACTURA",1,0,"C",1);
	$pdf->Cell(45,4,"METODO PAGO",1,0,"C",1);
	$pdf->Cell(25,4,"SALDO ANTERIOR",1,0,"C",1);
	$pdf->Cell(25,4,"IMPORTE PAGO",1,0,"C",1);
	$pdf->Cell(25,4,"NUEVO SALDO",1,0,"C",1);
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->SetFont('Arial','',6);
	$numero = '20';
	if($arreglo['cfdi:Comprobante'][0]['@Version']!='4.0') $numero = '10';
	foreach( $arreglo['cfdi:Comprobante'][0]['cfdi:Complemento'][0]['pago'.$numero.':Pagos'][0]['pago'.$numero.':Pago'][0]['pago'.$numero.':DoctoRelacionado'] as $pago){
		$pdf->Cell(27.5,3,$pago['@Folio'],0,0,"C",0);
		$pdf->Cell(50,3,$pago['@IdDocumento'],0,0,"L",0);
		$pdf->Cell(45,3,$pago['@MetodoDePagoDR'].' '.(($pago['@MetodoDePagoDR']=='PUE') ? 'PAGO EN UNA EXHIBICION':'PAGO EN PARCIALIDADES O DIFERIDO'),0,0,"L",0);
		$pdf->Cell(25,3,$pago['@ImpSaldoAnt'],0,0,"R",0);
		$pdf->Cell(25,3,$pago['@ImpPagado'],0,0,"R",0);
		$pdf->Cell(25,3,$pago['@ImpSaldoInsoluto'],0,0,"R",0);
		$pdf->Ln();
	}
	$pdf->Ln();
	$pdf->SetFont('Arial','',8);
	$pdf->MultiCell(26,4,"MONEDA: MXN",0,'L',0);
	$pdf->Cell(197.5,4,"",'T',0,'C',0);
	//$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->Ln();
	$pdf->MultiCell(137.5,4,numlet($row['monto']),0,'L',0);
	$pdf->Ln();
	if($pdf->GetY() < $y) $y=$pdf->GetY();
	$pdf->Cell(26,4,"OBSERVACIONES:",0,0,'L',0);
	$pdf->MultiCell(111.5,4,$row['obs'],0,'L',0);
	if($pdf->GetY() < $y) $y=$pdf->GetY();
	$y2=$pdf->GetY();
	$pdf->SetXY(97.5,$y);
	$pdf->Cell(30,6.5,"TOTAL",0,0,"R",0);
	$pdf->Cell(40,6.5,$row['monto'],0,0,"R",0);
	$pdf->Ln();
	$pdf->Ln();
	$y=$pdf->GetY();
	$pdf->SetX(45);
	$pdf->Cell(162.5,5.5,"CADENA ORIGINAL",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->SetFont('Arial','',6);
	$pdf->MultiCell(162.5,3,$row['cadenaoriginal'],0,"J",0);
	//$pdf->Ln();
	$pdf->SetX(45);
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(162.5,5.5,"SELLO DIGITAL EMISOR",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->SetFont('Arial','',6);
	$pdf->MultiCell(162.5,3,$row['sellodocumento'],0,"J",0);
	//$pdf->Ln();
	$pdf->SetX(45);
	$pdf->SetFont('Arial','',8);
	$pdf->Cell(162.5,5.5,"SELLO DIGITAL SAT",1,0,"C",1);
	$pdf->Ln();
	$pdf->SetX(45);
	$pdf->SetFont('Arial','',6);
	$pdf->MultiCell(162.5,3,$row['sellotimbre'],0,"J",0);
	if($row['estatus']=='C'){
		//$pdf->Ln();
		$pdf->SetFont('Arial','',8);
		$pdf->SetX(45);
		$pdf->Cell(162.5,6.5,"FOLIO CANCELACION",1,0,"C",1);
		$pdf->Ln();
		$pdf->SetX(45);
		$pdf->SetFont('Arial','',6);
		$pdf->MultiCell(162.5,3,$row['respuesta2'],0,"J",0);
	}
	$tt = $row['monto'];
	$codigo = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=".$row['uuid']."&re=".$re."&rr=".$rr."&tt=".$tt."&fe=".substr($row['sellodocumento'],-8);
	QRcode::png($codigo,"cfdi/comprobantes/barcodep_".$row['plaza'].'_'.$row['cve'].".png","L",4,0);
	if(file_exists("cfdi/comprobantes/barcodep_".$row['plaza'].'_'.$row['cve'].".png")) $pdf->Image("cfdi/comprobantes/barcodep_".$row['plaza'].'_'.$row['cve'].".png",10,$y,34,34);
	
	
	
	if($mostrar==1){
		$pdf->Output();
	}
	elseif($row['estatus']=='C'){
		$pdf->Output("cfdi/comprobantes/pagoc_".$row['plaza']."_".$row['cve'].".pdf","F");
	}
	else{
		$pdf->Output("cfdi/comprobantes/pagop_".$row['plaza']."_".$row['cve'].".pdf","F");
	}
	if(file_exists("cfdi/comprobantes/barcodep_".$row['plaza'].'_'.$row['cve'].".png")) unlink("cfdi/comprobantes/barcodep_".$row['plaza'].'_'.$row['cve'].".png");
}
?>