<?php
require_once('cnx_db.php');
require_once('globales.php'); 
if($_POST['cmd']==101){
	$res = mysql_query("SELECT a.cve, a.estatus, a.respuesta1, a.serie, a.folio, b.nombre FROM facturas a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN clientes c ON c.cve = a.cliente WHERE a.plaza={$_POST['cveplaza']} AND a.cve={$_POST['reg']}");
	$row = mysql_fetch_assoc(($res));
	// Ruta del archivo XML en el servidor
	$archivo = "cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_{$_POST['reg']}.xml";
	$nombre_archivo = "{$row['nombre']} {$row['serie']} {$row['folio']}.xml";

	// Verificar que exista
	if (!file_exists($archivo)) {
	    die("El archivo {$archivo} no existe.");
	}

	// Cabeceras para forzar descarga
	header('Content-Description: File Transfer');
	header('Content-Type: application/xml');
	header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Length: ' . filesize($archivo));

	// Enviar contenido
	readfile($archivo);
	exit();
}

if($_POST['cmd']==100){
	include("imp_factura.php");
	generaFacturaPdf($_POST['cveplaza'], $_POST['reg'],1);
	exit();
}

if($_POST['cmd']==200){
	include("imp_factura.php");
	$zip = new ZipArchive();
	$fecha=date('Y_m_d_H_i_s');
	if($zip->open("cfdi/zipcfdis".$fecha.".zip",ZipArchive::CREATE)){
		$orderby = " ORDER BY a.cve";
		

		$where = "";
		if($_POST['reg']==0){
			if($_POST['serie']!=''){
				$where .= " AND a.serie = '{$_POST['serie']}'";
			}
			if($_POST['busquedafolio']>0){
				$where .= " AND a.folio = '{$_POST['busquedafolio']}'";
			}
			else{
				if($_POST['busquedacliente'] != ''){
					$where .= " AND CONCAT(c.nombre) LIKE '%{$_POST['busquedacliente']}%'";
				}

				if($_POST['busquedafechaini'] != ''){
					$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
				}

				if($_POST['busquedafechafin'] != ''){
					$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
				}
			}
		}
		else{
			$where .= " AND a.cve IN (".implode(',', $_POST['fdescargar']).")";
		}

		$archivos = array();
		$res = mysql_query("SELECT a.cve, a.estatus, a.respuesta1, a.serie, a.folio, b.nombre FROM facturas a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN clientes c ON c.cve = a.cliente WHERE a.plaza='{$_POST['cveplaza']}' AND a.respuesta1 != ''{$where}{$orderby}");
		while($row = mysql_fetch_assoc($res)){
			generaFacturaPdf($_POST['cveplaza'], $row['cve'], 0);
			if($row['estatus']=='C'){
				$zip->addFile("cfdi/comprobantes/facturac_{$_POST['cveplaza']}_{$row['cve']}.pdf","{$row['nombre']} {$row['serie']} {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/facturac_{$_POST['cveplaza']}_{$row['cve']}.pdf";
			}
			else{
				$zip->addFile("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$row['cve']}.pdf","{$row['nombre']} {$row['serie']} {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$row['cve']}.pdf";
			}
			$zip->addFile("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_{$row['cve']}.xml","{$row['nombre']} {$row['serie']} {$row['folio']}.xml");			
		}
		$zip->close(); 
	    if(file_exists("cfdi/zipcfdis".$fecha.".zip")){ 
	        header('Content-type: "application/zip"'); 
	        header('Content-Disposition: attachment; filename="zipcfdis'.$fecha.'.zip"'); 
	        readfile("cfdi/zipcfdis".$fecha.".zip"); 
	         
	        unlink("cfdi/zipcfdis".$fecha.".zip"); 
	        foreach($archivos as $archivo){
				@unlink($archivo);
			}
	    } 
	    else{
			echo '<h2>Ocurrio un problema al cerrar el archivo favor de intentarlo de nuevo2</h2>';
		}
	}
	else{
		echo '<h2>Ocurrio un problema al generar el archivo favor de intentarlo de nuevo1</h2>';
	}
	exit();
}

if($_POST['cmd']==41){

	$resultado = array('mensaje' => 'Se pago exitosamente', 'tipo'=>'success');

	$res = mysql_query("SELECT cve, serie, folio, uuid, forma_pago, total, respuesta1, cliente FROM facturas WHERE plaza = '{$_POST['cveplaza']}' AND cve='{$_POST['factura']}'");
	$Factura = mysql_fetch_array($res);
	if($Factura['estatus']!='C' && $Factura['total']>$Factura['monto_pagado']){
		$res = mysql_query("SELECT IFNULL(MAX(folio)+1, 1) as folio FROM pagosfacturas WHERE plaza = {$_POST['cveplaza']}");
		$row = mysql_fetch_array($res);
		$folio = $row['folio'];
		mysql_query("INSERT pagosfacturas 
			SET plaza='{$_POST['cveplaza']}', folio='{$folio}', fecha=CURDATE(), hora=CURTIME(), fecha_pago='{$_POST['fechapago']}', usuario = '{$_POST['cveusuario']}', estatus='A',formapago='{$_POST['formapago']}', numerooperacion='{$_POST['numerooperacion']}', cliente='{$Factura['cliente']}'");
		$cvepago = mysql_insert_id();
		mysql_query("INSERT pagosfacturasmov SET pago='{$cvepago}', factura='{$Factura['cve']}', monto='{$Factura['total']}', saldoanterior='{$Factura['total']}', uuid='{$Factura['respuesta1']}', serie='{$Factura['serie']}', folio='{$Factura['folio']}', metodo_pago = 'PPD', numeroparcialidad=1, total='{$Factura['total']}'");
		mysql_query("UPDATE facturas SET estatus_pago=1, usuario_pago='{$_POST['cveusuario']}', fecha_pago='{$_POST['fechapago']}', fechahora_pago=NOW(), monto_pagado=total WHERE plaza={$_POST['cveplaza']} AND cve={$_POST['factura']}");
		$resultadotimbres = validar_timbres($_POST['cveplaza']);
		if($resultadotimbres['seguir']){
			require_once('nusoap/nusoap.php');
			$Empresa = mysql_fetch_assoc(mysql_query("SELECT * FROM datosempresas WHERE plaza='{$_POST['cveplaza']}'"));
			$documento = genera_arreglo_pago($_POST['cveplaza'], $cvepago);
			$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);		
			$err = $oSoapClient->getError();
			if($err!=""){
				$resultado = array('mensaje' => "error1:".$err, 'tipo'=>'warning');
			}
			else{
				$oSoapClient->timeout = 300;
				$oSoapClient->response_timeout = 300;
				$respuesta = $oSoapClient->call("generaComprobantePago", array ('id' => $Empresa['idplaza'],'rfcemisor' => $Empresa['rfc'],'idcertificado' => $Empresa['idcertificado'],'documento' => $documento, 'usuario' => $Empresa['usuario'],'password' => $Empresa['pass']));
				if ($oSoapClient->fault) {
					$resultado['mensaje'] = '<p><b>Fault: ';
					$resultado['mensaje'] .=print_r($respuesta, true);
					$resultado['mensaje'] .= '</b></p>';
					$resultado['mensaje'] .= '<p><b>Request: <br>';
					$resultado['mensaje'] .= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
					$resultado['mensaje'] .= '<p><b>Response: <br>';
					$resultado['mensaje'] .= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
					$resultado['mensaje'] .= '<p><b>Debug: <br>';
					$resultado['mensaje'] .= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
					$resultado['tipo'] = 'warning';
				}
				else{
					$err = $oSoapClient->getError();
					if ($err){
						$resultado['mensaje'] = '<p><b>Error: ' . $err . '</b></p>';
						$resultado['mensaje'] .= '<p><b>Request: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
						$resultado['mensaje'] .= '<p><b>Response: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
						$resultado['mensaje'] .= '<p><b>Debug: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
						$resultado['tipo'] = 'warning';
					}
					else{
						if($respuesta['resultado']){
							mysql_query("UPDATE pagosfacturas SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}',
								sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}',
								sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}',
								fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
								WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvepago}");
							include("imp_factura.php");
							$dir="cfdi/comprobantes/";
							$dir2="cfdi/";
							$fileresult=$respuesta['archivos'];
							$strzipresponse=base64_decode($fileresult);
							$filename='cfdip_'.$_POST['cveplaza'].'_'.$cvepago;
							file_put_contents($dir2.$filename.'.zip', $strzipresponse);
							$zip = new ZipArchive;


							if ($zip->open($dir2.$filename.'.zip') === TRUE){
								$strxml=$zip->getFromName('xml.xml');
								file_put_contents($dir.$filename.'.xml', $strxml);
								$strpdf=$zip->getFromName('formato.pdf');
								file_put_contents($dir.$filename.'.pdf', $strpdf);
								$zip->close();		

								$res1 = mysql_query("SELECT * FROM clientes WHERE cve='{$Factura['cliente']}'");
								$row1 = mysql_fetch_array($res1);
								$row1['cve']=0;
								$emailenvio = $row1['email'];

								generaPagoPdf($_POST['cveplaza'],$cvepago);
								$mail = obtener_mail();
								$mail->Subject = "Complemento de Pago {$folio}";
								$mail->Body = "Complemento de Pago {$folio}";
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/pagop_{$_POST['cveplaza']}_".$cvepago.".pdf", "Pago {$folio}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdip_{$_POST['cveplaza']}_".$cvepago.".xml", "Pagp {$folio}.xml");
								$mail->Send();
								@unlink("cfdi/comprobantes/pagop_{$_POST['cveplaza']}{_{$cvepago}.pdf");
							}
							else{
								$strmsg='Error al descomprimir el archivo';
							}
							@unlink("cfdi/cfdip_{$_POST['cveplaza']}_{$cvepago}.zip");
						}
						else{
							$strmsg=$respuesta['mensaje'];
							$resultado = array('mensaje' => $strmsg, 'tipo'=>'warning');
						}
					}
				}
			}
		}
	}
	else{
		$resultado = array('mensaje' => 'No se pudo pagar la factura, esta cancelada o ya se encuentra pagada', 'tipo'=>'error');
	}

	echo json_encode($resultado);

	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	$res = mysql_query("SELECT * FROM facturas WHERE plaza = '{$_POST['cveplaza']}' AND cve='{$_POST['factura']}'");
	$row = mysql_fetch_array($res);
	if($row['estatus']!='C'){
		$Empresa = mysql_fetch_assoc(mysql_query("SELECT * FROM datosempresas WHERE plaza='{$_POST['cveplaza']}'"));
		$res1 = mysql_query("SELECT * FROM clientes WHERE cve='{$row['cliente']}'");
		$row1 = mysql_fetch_array($res1);
		$emailenvio = $row1['email'];
		$cvefact=$row['cve'];
		if($row['respuesta1']!=""){
			require_once("nusoap/nusoap.php");
			$resultadotimbres = validar_timbres($_POST['cveplaza']);
			if($resultadotimbres['seguir']){
				$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);		
				$err = $oSoapClient->getError();
				if($err!=""){
					$resultado = array('mensaje' => "error1:".$err, 'tipo'=>'warning');
				}
				else{
					$oSoapClient->timeout = 300;
					$oSoapClient->response_timeout = 300;
					$respuesta = $oSoapClient->call("cancelarCFDISAT", array ('id' => $Empresa['idplaza'],'rfcemisor' =>$Empresa['rfc'],'idcertificado' => $Empresa['idcertificado'],'uuid' => $row['respuesta1'], 'usuario' => $Empresa['usuario'],'password' => $Empresa['pass'],'motivo' => $_POST['motivocancelacion'], 'uuidsustituye' => $_POST['uuidsustituye'],'rfcreceptor'=>$row1['rfc'],'importe'=>$row['total']));
					if ($oSoapClient->fault) {
						$resultado['mensaje'] = '<p><b>Fault: ';
						$resultado['mensaje'] .=print_r($respuesta, true);
						$resultado['mensaje'] .= '</b></p>';
						$resultado['mensaje'] .= '<p><b>Request: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
						$resultado['mensaje'] .= '<p><b>Response: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
						$resultado['mensaje'] .= '<p><b>Debug: <br>';
						$resultado['mensaje'] .= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
						$resultado['tipo'] = 'warning';
					}
					else{
						$err = $oSoapClient->getError();
						if ($err){
							$resultado['mensaje'] = '<p><b>Error: ' . $err . '</b></p>';
							$resultado['mensaje'] .= '<p><b>Request: <br>';
							$resultado['mensaje'] .= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
							$resultado['mensaje'] .= '<p><b>Response: <br>';
							$resultado['mensaje'] .= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
							$resultado['mensaje'] .= '<p><b>Debug: <br>';
							$resultado['mensaje'] .= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
							$resultado['tipo'] = 'warning';
						}
						else{
							if($respuesta['resultado']){
								mysql_query("UPDATE facturas SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW(),respuesta2='{$respuesta['mensaje']}', motivo_cancelacion='{$_POST['motivocancelacion']}', uuidsustituye='{$_POST['uuidsustituye']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['factura']}'");
								mysql_query("UPDATE pagos_caja SET factura=0 WHERE plaza='{$_POST['cveplaza']}' AND factura='{$_POST['factura']}'");
								mysql_query("UPDATE cobro_engomado SET factura=0 WHERE plaza='{$_POST['cveplaza']}' AND factura='{$_POST['factura']}'");
								include("imp_factura.php");
								generaFacturaPdf($_POST['cveplaza'], $_POST['factura']);
								if($emailenvio!=""){
									$mail = obtener_mail();
									$mail->Subject = "Cancelacion de Factura {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
									$mail->Body = "Cancelacion de Factura {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo)
										$mail->AddAddress(trim($correo));
									$mail->AddAttachment("cfdi/comprobantes/facturac_{$_POST['cveplaza']}_".$cvefact.".pdf", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_".$cvefact.".xml", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.xml");
									$mail->Send();
								}	
								@unlink("cfdi/comprobantes/facturac_{$_POST['cveplaza']}_".$cvefact.".pdf");
							}
							else{
								$strmsg=$respuesta['mensaje'];
								$resultado = array('mensaje' => $strmsg, 'tipo'=>'warning');
							}
						}
					}
				}
			}
		}
		else{
			mysql_query("UPDATE facturas SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['factura']}'");
			mysql_query("UPDATE pagos_caja SET factura=0 WHERE plaza='{$_POST['cveplaza']}' AND factura='{$_POST['factura']}'");
			mysql_query("UPDATE cobro_engomado SET factura=0 WHERE plaza='{$_POST['cveplaza']}' AND factura='{$_POST['factura']}'");
		}
	}


	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==30){
	$factura = $_POST['factura'];
	$rsFactura = mysql_query("SELECT * FROM facturas WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['factura']}'");
	$Factura = mysql_fetch_assoc($rsFactura);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT * FROM plazas WHERE cve='{$Factura['plaza']}'"));
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$Factura['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);

	include("imp_factura.php");
	generaFacturaPdf($_POST['cveplaza'], $factura);
	$emailenvio = $Cliente['email'];
	if($emailenvio!=""){
		$mail = obtener_mail();
		$mail->Subject = "Factura {$Plaza['nombre']} {$Factura['serie']} {$Factura['folio']}";
		$mail->Body = "Factura {$Plaza['nombre']} {$Factura['serie']} {$Factura['folio']}";
		$correos = explode(",",trim($emailenvio));
		foreach($correos as $correo){
			if(trim($correo) != '')
				$mail->AddAddress(trim($correo));
		}
		$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['cveplaza']}_".$factura.".pdf", "{$Plaza['nombre']} {$Factura['serie']} {$Factura['folio']}.pdf");
		$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_".$factura.".xml", "{$Plaza['nombre']} {$Factura['serie']} {$Factura['folio']}.xml");
		$mail->Send();
	}	
	@unlink("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$factura}.pdf");

	exit();
}
if($_POST['cmd']==20){
	$factura = $_POST['factura'];
	$rsFactura = mysql_query("SELECT * FROM facturas WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['factura']}'");
	$Factura = mysql_fetch_assoc($rsFactura);
	$fecha_minima = date( "Y-m-d H:i:s" , strtotime ( "-70 hour" , strtotime(date('Y-m-d H:i:s')) ) );
	$fecha_factura = $Factura['fecha'].' '.$Factura['hora'];
	if ($fecha_minima > $fecha_factura) {
		$fecha_factura = date( "Y-m-d" , strtotime ( "-1 day" , strtotime(date('Y-m-d')) ) );
		mysql_query("UPDATE facturas SET fecha='{$fecha_factura}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['factura']}' AND respuesta1=''");
	}
	$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$Factura['plaza']}'");
	$Sucursal = mysql_fetch_assoc($rsSucursal);
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$Factura['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);
	$resultado = validar_timbres($_POST['cveplaza']);
	if($resultado['seguir']){
		$documento = genera_arreglo_facturacion($_POST['cveplaza'], $_POST['factura'], 'I');
		require_once('nusoap/nusoap.php');
		$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);
		$err = $oSoapClient->getError();
		if($err!="")
			echo "error1:".$err;
		else{
			//print_r($documento);
			$oSoapClient->timeout = 300;
			$oSoapClient->response_timeout = 300;
			$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $Sucursal['idplaza'],'rfcemisor' => $Sucursal['rfc'],'idcertificado' => $Sucursal['idcertificado'],'documento' => $documento, 'usuario' => $Sucursal['usuario'],'password' => $Sucursal['pass']));
			if ($oSoapClient->fault) {
				echo '<p><b>Fault: ';
				print_r($respuesta);
				echo '</b></p>';
				echo '<p><b>Request: <br>';
				echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
				echo '<p><b>Response: <br>';
				echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
				echo '<p><b>Debug: <br>';
				echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
			}
			else{
				$err = $oSoapClient->getError();
				if ($err){
					echo '<p><b>Error: ' . $err . '</b></p>';
					echo '<p><b>Request: <br>';
					echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Response: <br>';
					echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Debug: <br>';
					echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
				}
				else{
					if($respuesta['resultado']){

						mysql_query("UPDATE facturas SET respuesta1='{$respuesta['uuid']}',seriecertificado='{$respuesta['seriecertificado']}',
						sellodocumento='{$respuesta['sellodocumento']}',uuid='{$respuesta['uuid']}',seriecertificadosat='{$respuesta['seriecertificadosat']}',
						sellotimbre='{$respuesta['sellotimbre']}',cadenaoriginal='{$respuesta['cadenaoriginal']}',
						fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
						WHERE plaza='{$_POST['cveplaza']}' AND cve={$_POST['factura']}");

						mysql_query("UPDATE facturas SET rfc_cli='".$Cliente['rfc']."', nombre_cli='".$Cliente['nombre']."', calle_cli='".$row1['calle']."', numext_cli='".$row1['numexterior']."', numint_cli = '".$row1['numinterior']."', colonia_cli = '".$row1['colonia']."', localidad_cli = '".$row1['localidad']."', municipio_cli = '".$row1['municipio']."',
							estado_cli='".$row1['estado']."', cp_cli='".$Cliente['codigopostal']."'
						WHERE plaza='".$_POST['cveplaza']."' AND cve=".$_POST['factura']);
						include("imp_factura.php");
						//Tomar la informacion de Retorno
						$dir="cfdi/comprobantes/";
						//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
						//el zip siempre se deja fuera
						$dir2="cfdi/";
						//Leer el Archivo Zip
						$fileresult=$respuesta['archivos'];
						$strzipresponse=base64_decode($fileresult);
						$filename='cfdi_'.$_POST['cveplaza'].'_'.$factura;
						file_put_contents($dir2.$filename.'.zip', $strzipresponse);
						$zip = new ZipArchive;
						if ($zip->open($dir2.$filename.'.zip') === TRUE){
							$strxml=$zip->getFromName('xml.xml');
							file_put_contents($dir.$filename.'.xml', $strxml);
							$zip->close();		
							generaFacturaPdf($_POST['cveplaza'], $factura);
							$emailenvio = $Cliente['email'];
							//require_once("phpmailer/class.phpmailer.php");
							if($emailenvio!=""){
								$mail = obtener_mail();
								$mail->Subject = "Factura {$Factura['serie']} {$Factura['folio']}";
								$mail->Body = "Factura {$Factura['serie']} {$Factura['folio']}";
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['cveplaza']}_".$factura.".pdf", "{$Sucursal['nombre']} {$Factura['serie']} {$Factura['folio']}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_".$factura.".xml", "{$Sucursal['nombre']} {$Factura['serie']} {$Factura['folio']}.xml");
								$mail->Send();
							}	
							@unlink("cfdi/comprobantes/factura_{$_POST['cveplaza']}{_{$factura}.pdf");
							@unlink("cfdi/cfdi_{$_POST['cveplaza']}_{$factura}.zip");
						}
						else 
							$strmsg='Error al descomprimir el archivo';
							
						echo 'Se timbro de forma correcta ';
					}
					else{
						$strmsg=$respuesta['mensaje'];
						echo $strmsg;
					}
					
				}
			}
		}
	}
	exit();
}
require_once('validarloging.php');
if($_POST['cmd']==0){
?>
<input type="hidden" id="facturacancelar" value="">
<div id="modalCancelacion" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Cancelación</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label for="total">Motivo</label>
					            <select id="motivocancelacion" class="form-control" onChange="if(this.value=='01'){$('#divuuidsustituye').show();} else{ $('#divuuidsustituye').hide();$('#uuidsustituye').val('');}"><option value="">Seleccione</option>
					            <?php
					            	$res = mysql_query("SELECT clave, nombre FROM motivos_cancelacion_sat ORDER BY clave");
					            	while($row = mysql_fetch_assoc($res)){
					            		echo '<option value="'.$row['clave'].'">'.$row['nombre'].'</option>';
					            	}
					            ?>
					            </select>
					        </div>
					        <div class="form-group col-sm-12" style="display:none;" id="divuuidsustituye">
								<label for="total">UUID Sustituye</label>
					            <input type="text" class="form-control" id="uuidsustituye" placeholder="UUID Sustituye" value="">
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="cancelar();">Cancelar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<input type="hidden" id="facturapagar" value="">
<div id="modalPagar" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Pagar</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-12">
								<label for="formapagopagar">Forma Pago</label>
					            <select id="formapagopagar" class="form-control"><option value="">Seleccione</option>
					            <?php
					            	$res = mysql_query("SELECT cve, nombre FROM formapagosat ORDER BY cve");
					            	while($row = mysql_fetch_assoc($res)){
					            		echo '<option value="'.$row['cve'].'">'.utf8_encode($row['nombre']).'</option>';
					            	}
					            ?>
					            </select>
					        </div>
					        <div class="form-group col-sm-12">
								<label for="fechapagopagar">Fecha Pago</label>
					            <input type="date" class="form-control" id="fechapagopagar" placeholder="Fecha Pago" value="" max="<?php echo date('Y-m-d');?>">
					        </div>
					        <div class="form-group col-sm-12">
								<label for="numerooperacionpagar">Numero Operacion</label>
					            <input type="number" class="form-control" id="numerooperacionpagar" placeholder="Numero Operacion" value="">
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="pagar();">Pagar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-xl-10 col-lg-10 col-md-10">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicial</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" value="<?php echo date('Y-m');?>-01" placeholder="Fecha Inicial">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Final</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" value="<?php echo date('Y-m-d');?>" placeholder="Fecha Final">
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Folio</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedafolio" name="busquedafolio" placeholder="Folio">
        	</div>
			<label class="col-sm-2 col-form-label">Cliente</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedacliente" name="busquedacliente" placeholder="Cliente">
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">RFC Cliente</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedarfc" name="busquedarfc" placeholder="RFC">
        	</div>
        	<label class="col-sm-2 col-form-label">Estatus</label>
			<div class="col-sm-4">
            	<select class="form-control" id="busquedaestatus" name="busquedaestatus">
            		<option value="">Todos</option>
            		<option value="1">Timbrados</option>
            		<option value="2">Pendiente de Timbrar</option>
            		<option value="3">Cancelados</option>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<div class="btn-group">
		        	<button type="button" class="btn btn-primary" onClick="buscar();">
		            	Buscar
		        	</button>
		        </div>
		        	&nbsp;&nbsp;
		        <div class="btn-group">
		        	<button type="button" class="btn btn-primary" onClick="atcr('facturas.php', '', 1, 0);">
		            	Nueva Factura
		        	</button>
		        </div>
		        	&nbsp;&nbsp;
		        <?php if($_POST['cveusuario']==1){?>
		        <!--<div class="btn-group">
		        	<button type="button" class="btn btn-primary" onClick="atcr('facturas.php', '', 11, 0);">
		            	Factura Mostrador
		        	</button>
		        </div>
		        	&nbsp;&nbsp;-->
		        <?php } ?>
		        <div class="btn-group">
					<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    Descargar
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					    <a class="dropdown-item" href="javascript: descargar(0);">Descargar Listado</a>
					    <a class="dropdown-item" href="javascript: descargar(1);">Descargar Seleccionados</a>
					</div>
				</div>
				<div class="btn-group">
		        	<button type="button" class="btn btn-success" onClick="atcr('facturas.php','_blank',115,0);">
		            	Excel
		        	</button>
		        </div>
        	</div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-2 col-md-2">
    	<div class="form-row">
			<div class="form-group col-sm-12">
				<label>Existencia Timbres</label>
	             <input type="number" class="form-control-plaintext" id="existencia_timbres" value="" readOnly>
	        </div>
	    </div>
    </div>
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>&nbsp;</th>
				<th>Descargar</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo Factura</th>
				<th>Cliente</th>
				<th>RFC</th>
				<th>Tipo de Pago</th>
				<th>Total</th>
				<th>Estatus</th>
				<th>Ticket</th>
				<th>Fecha Ticket</th>
				<th>Placa Ticket</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Descargar</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo Factura</th>
				<th>Cliente</th>
				<th>RFC</th>
				<th>Tipo de Pago</th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Estatus</th>
				<th>Ticket</th>
				<th>Fecha Ticket</th>
				<th>Placa Ticket</th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>

	function descargar(tipo){
		var error = 0;
		if(tipo==1){
			if(!$('.chks').is(':checked')){
				sweetAlert('', 'Necesita seleccionar al menos una factura', 'warning');
				error=1;
			}
		}
		if(error == 0){
			atcr("facturas.php", "_blank", 200, tipo);
		}
	}

	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'facturas.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafolio": $("#busquedafolio").val(),
        		"busquedacliente": $("#busquedacliente").val(),
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedarfc": $("#busquedarfc").val(),
        		"busquedaestatus": $("#busquedaestatus").val(),
        		'cveusuario': $('#cveusuario').val(),
        		'cveplaza': $('#cveplaza').val(),
        		'cvemenu': $('#cvemenu').val()
        	},
        	fncallback: function(json){
        		$('#ttotal').html(json.total);
        		$('#existencia_timbres').val(json.existencia_timbres);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[2, "DESC"]],
        "bPaginate": true,
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-center", "targets": 9 },
        	{ className: "dt-head-center dt-body-center", "targets": 10 },
        	{ className: "dt-head-center dt-body-center", "targets": 11 },
        	{ className: "dt-head-center dt-body-center", "targets": 12 },
        	{ className: "dt-head-center dt-body-left", "targets": 13 },
        	{ orderable: false, "targets": 10 },
        	{ orderable: false, "targets": 11 },
        	{ orderable: false, "targets": 12 },
        	{ orderable: false, "targets": 13 },
        	{ orderable: false, "targets": 0 },
        	{ orderable: false, "targets": 1 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafolio": $("#busquedafolio").val(),
    		"busquedacliente": $("#busquedacliente").val(),
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedarfc": $("#busquedarfc").val(),
    		"busquedaestatus": $("#busquedaestatus").val(),
    		'cveusuario': $('#cveusuario').val(),
    		'cveplaza': $('#cveplaza').val(),
    		'cvemenu': $('#cvemenu').val()
        });
        tablalistado.ajax.reload();
	}
	
	var tablalistado_i = $('#dataTable_i').DataTable( {
        "ajax": {
        	url: 'facturas.php',
        	type: "POST",
        	"data": {
        		"cmd": 115,
        		"busquedafolio": $("#busquedafolio").val(),
        		"busquedacliente": $("#busquedacliente").val(),
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedarfc": $("#busquedarfc").val(),
        		"busquedaestatus": $("#busquedaestatus").val(),
        		'cveusuario': $('#cveusuario').val(),
        		'cveplaza': $('#cveplaza').val(),
        		'cvemenu': $('#cvemenu').val()
        	},
        	fncallback: function(json){
        		$('#ttotal_i').html(json.total);
        		//$('#existencia_timbres').val(json.existencia_timbres);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[2, "DESC"]],
        "bPaginate": true,
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-center", "targets": 9 },
        	{ className: "dt-head-center dt-body-center", "targets": 10 },
        	{ className: "dt-head-center dt-body-center", "targets": 11 },
        	{ className: "dt-head-center dt-body-center", "targets": 12 },
        	{ className: "dt-head-center dt-body-left", "targets": 13 },
        	{ orderable: false, "targets": 10 },
        	{ orderable: false, "targets": 11 },
        	{ orderable: false, "targets": 12 },
        	{ orderable: false, "targets": 13 },
        	{ orderable: false, "targets": 0 },
        	{ orderable: false, "targets": 1 }
		  ]
    } );
	function buscar_i(){
		tablalistado_i.ajax.data({
    		"cmd": 10,
    		"busquedafolio": $("#busquedafolio").val(),
    		"busquedacliente": $("#busquedacliente").val(),
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedarfc": $("#busquedarfc").val(),
    		"busquedaestatus": $("#busquedaestatus").val(),
    		'cveusuario': $('#cveusuario').val(),
    		'cveplaza': $('#cveplaza').val(),
    		'cvemenu': $('#cvemenu').val()
        });
        tablalistado_i.ajax.reload();
	}
	

	function timbrar(factura){
		waitingDialog.show();
		$.ajax({
			url: 'facturas.php',
			type: "POST",
			data: {
				cmd: 20,
				cveplaza: $('#cveplaza').val(),
				factura: factura
			},
			success: function(data) {
				waitingDialog.hide();
				sweetAlert('', data, 'success');
				buscar();
			}
		});
	}


	function reenviarcorreo(factura){
		waitingDialog.show();
		$.ajax({
			url: 'facturas.php',
			type: "POST",
			data: {
				cmd: 30,
				cveplaza: $('#cveplaza').val(),
				factura: factura
			},
			success: function(data) {
				waitingDialog.hide();
				sweetAlert('', data, 'warning');
			}
		});
	}

	function cancelar(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else if ($("#motivocancelacion").val() == "01" && $("#uuidsustituye").val() == ""){
			alert("Necesita agregar el uuid que sustituye al cancelado");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'facturas.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					factura: $('#facturacancelar').val(),
					motivocancelacion: $("#motivocancelacion").val(),
					uuidsustituye: $("#uuidsustituye").val(),
					cveplaza: $('#cveplaza').val(),
					'cveusuario': $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	function cancelarr(factura){
		$('#facturacancelar').val(factura);
		$("#motivocancelacion").val('');
		$("#uuidsustituye").val('');
		$('#divuuidsustituye').hide();
		$('#modalCancelacion').modal('show');
		
	}

	function pagar(){
		if ($("#formapagopagar").val() == ""){
			alert("Necesita seleccionar la forma de pago");
		}
		else if ($("#fechapagopagar").val() == ""){
			alert("Necesita ingresar la fecha de pago");
		}
		else if ($("#numerooperacionpagar").val() == ""){
			alert("Necesita ingresar el numero de operacion");
		}
		else{
			$('#modalPagar').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'facturas.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 41,
					factura: $('#facturapagar').val(),
					formapago: $("#formapagopagar").val(),
					fechapago: $("#fechapagopagar").val(),
					numerooperacion: $("#numerooperacionpagar").val(),
					cveplaza: $('#cveplaza').val(),
					'cveusuario': $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	function pagarr(factura){
		$('#facturapagar').val(factura);
		$("formapagopagar").val('');
		$("#fechapagopagar").val('');
		$('#numerooperacionpagar').val('');
		$('#modalPagar').modal('show');
		
	}

	$("#modalPagar").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	$("#modalCancelacion").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array('', '', "a.folio", "CONCAT(a.fecha, ' ', a.hora)", 'a.tipo_pag', "b.nombre", "b.rfc", 'c.nombre', "IF(a.estatus='C', 0, a.total)", "IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado'))");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.folio";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
	if($_POST['busquedafolio']>0){
		$where .= " AND a.folio = '{$_POST['busquedafolio']}'";
	}
	else{
		if($_POST['busquedacliente'] != ''){
			$where .= " AND CONCAT(b.nombre) LIKE '%{$_POST['busquedacliente']}%'";
		}

		if($_POST['busquedafechaini'] != ''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin'] != ''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
		}
		if($_POST['busquedarfc'] != ''){
			$where .= " AND b.rfc = '{$_POST['busquedarfc']}'";
		}
		if($_POST['busquedaestatus']==1) {
			$where .= " AND a.estatus!='C' AND a.respuesta1!=''";
		}
		elseif($_POST['busquedaestatus']==2) {
			$where .= " AND a.estatus!='C' AND a.respuesta1=''";
		}
		elseif($_POST['busquedaestatus']==3) {
			$where .= " AND a.estatus='C'";
		}
	}

	$nivelUsuario = nivelUsuario();
	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.total, 0)) as total FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'total' => $registros['total'],
		'existencia_timbres' => existencia_timbres($_POST['cveplaza'])
	);
	$res = mysql_query("SELECT a.plaza, a.cve, a.serie, a.folio, a.fecha, a.hora, IF(a.tipo_pag=0, 'Contado', 'Credito') as nomtipopag, b.nombre as nomcliente, b.rfc as rfccli, c.nombre as nomtipopagofac, IF(a.estatus='C', 0, a.total) as total, IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado')) as nomestatus, d.usuario, a.estatus, a.respuesta1, a.tipo_documento_origen, a.sin_timbrar, a.forma_pago, a.monto_pagado FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN tipos_pago_factura c ON c.cve = a.tipo_pago LEFT JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$extras = '';
		$chk = '<input type="checkbox" class="form-control chks" name="fdescargar[]" value="'.$row['cve'].'"';
		if($_POST['checkall'] == 'true') $chk .= ' checked';
		$chk .= '>';
		if($row['estatus'] != 'C' && $row['respuesta1'] == '' && ($row['sin_timbrar']==1 || $_POST['cveusuario']==1)){
			$extras .= '<a class="dropdown-item" href="#" onClick="atcr(\'facturas.php\',\'\',1,'.$row['cve'].')">Editar</a>';
			$extras .= '<a class="dropdown-item" href="#" onClick="timbrar('.$row['cve'].')">Timbrar</a>';
			$chk = '';
		}
		if($row['respuesta1'] != ''){
			$extras .= '<a class="dropdown-item" href="#" onClick="atcr(\'cfdi/comprobantes/cfdi_'.$row['plaza'].'_'.$row['cve'].'.xml\',\'_blank\',\'\','.$row['cve'].')">XML</a>';
			$extras .= '<a class="dropdown-item" href="#" onClick="reenviarcorreo('.$row['cve'].')">Reenviar correo</a>';
		}
		if($row['estatus'] != 'C' && $nivelUsuario>2){
			$extras .= '<a class="dropdown-item" href="#" onClick="cancelarr('.$row['cve'].')">Cancelar</a>';
		}
		if($row['estatus'] != 'C' && $row['forma_pago']==1 && $row['total']>$row['monto_pagado']){
			$extras .= '<a class="dropdown-item" href="#" onClick="pagarr('.$row['cve'].')">Pagar</a>';
		}
		$tickets = '';
		$fechas_tickets='';
		$placas_tickets='';

		if($row['tipo_documento_origen'] <= 1){
			$res2=mysql_query("SELECT b.* FROM venta_engomado_factura a INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.venta WHERE a.plaza='{$row['plaza']}' AND a.factura='{$row['cve']}' AND b.estatus!='C'");
			if(mysql_num_rows($res2)>10){
				$fechas_tickets = '';
				$placas_tickets = '';
				$tickets = mysql_num_rows($res2);
			}
			elseif(mysql_num_rows($res2)>0){
				while($row2 = mysql_fetch_array($res2)){
					$tickets .= '<br>'.$row2['cve'];
					$fechas_tickets .= '<br>'.$row2['fecha'];
					$placas_tickets .= '<br>'.$row2['placa'];
				}
				$tickets = substr($tickets, 4);
				$fechas_tickets = substr($fechas_tickets, 4);
				$placas_tickets = substr($placas_tickets, 4);
			}
		}
		elseif($row['tipo_documento_origen']==2){
			$res2 = mysql_query("SELECT cve FROM pagos_caja WHERE plaza='{$row['plaza']}' AND factura='{$row['cve']}' AND estatus!='C'");
			while($row2 = mysql_fetch_array($res2)){
				$tickets .= '<br>P'.$row2['cve'];
			}
		}


		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'facturas.php\',\'_blank\',100,'.$row['cve'].')">Imprimir</a>
                      '.$extras.'
                    </div>';

		


		$resultado['data'][] = array(
			$dropmenu,
			$chk,
			$row['serie'].' '.$row['folio'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['nomtipopag']),
			utf8_encode($row['nomcliente']),
			utf8_encode($row['rfccli']),
			utf8_encode($row['nomtipopagofac']),
			number_format($row['total'],2),
			$row['nomestatus'],
			$tickets,
			$fechas_tickets,
			 $placas_tickets,
			 $row['usuario']
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$Factura = mysql_fetch_assoc(mysql_query("SELECT * FROM facturas WHERE plaza={$_POST['cveplaza']} AND cve={$_POST['reg']}"));
	$infofactura = '';
	if ($_POST['reg'] > 0){
		$infofactura = 'Factura '.$Factura['serie'].' '.$Factura['folio'];
	}
	$selectengomados = '<option value="0" precio="0">Seleccione</option>';
	$res = mysql_query("SELECT a.cve, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
	$arreglo_engomados = array();
	while($row = mysql_fetch_assoc($res)){
		$arreglo_engomados[$row['cve']] = array('nombre' => $row['nombre'], 'precio' => $row['precio']);
		$selectengomados .= '<option value="'.$row['cve'].'" precio="'.$row['precio'].'">'.utf8_encode($row['nombre']).'</option>';
	}
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<button type="button" class="btn btn-success" onClick="atcr('facturas.php','',2,<?php echo $_POST['reg'];?>);">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<button type="button" class="btn btn-primary" onClick="atcr('facturas.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos<?php echo $infofactura;?></h6>
			</div>
			<div class="card-body">
				
			    <div class="form-row">
					<div class="form-group col-sm-6">
						<label for="cliente">Cliente</label>
			             <select name="cliente" class="form-control" data-container="body" data-live-search="true" title="Cliente" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="cliente"><option value="">Seleccione</option>
			            <?php
			            $res = mysql_query("SELECT cve, nombre, usocfdi FROM clientes WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'" usocfdi="'.$row['usocfdi'].'"';
			            	if($Factura['cliente']==$row['cve']) echo ' selected';
			            	echo '>'.utf8_encode($row['nombre']).'</option>';
			            }
			            ?>
			        	</select>
			        	<script>
							$("#cliente").selectpicker();	
						</script>
			        </div>
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="forma_pago">Forma de Pago</label>
			            <select id="forma_pago" name="forma_pago" class="form-control">
			            <option value="0">Pago en una sola exhibici&oacute;n</option>
			            <option value="1"<?php if($Factura['forma_pago']==1){?> selected<?php }?>>Pago en parcialidades o diferidos</option>
			        	</select>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="tipo_pago">Tipo de Pago</label>
			            <select id="tipo_pago" name="tipo_pago" class="form-control">
			            <?php
			            $res = mysql_query("SELECT cve, nombre FROM tipos_pago_factura  ORDER BY cve");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'"';
			            	if($Factura['tipo_pago']==$row['cve']) echo ' selected';
			            	echo '>'.utf8_encode($row['nombre']).'</option>';
			            }
			            ?>
			        	</select>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="condiciones_pago">Condiciones de Pago</label>
			            <select id="condiciones_pago" name="condiciones_pago" class="form-control">
			            <option value="0">Contado</option>
			            <option value="1"<?php if($Factura['condiciones_pago']==1){?> selected<?php }?>>Credito</option>
			        	</select>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-2">
						<label for="tipo_documento_origen">Tipo Origen</label>
			            <select id="tipo_documento_origen" name="tipo_documento_origen" class="form-control" onChange="mostrar_folios(true)">
			            <option value="0"<?php if($Factura['tipo_documento_origen']==0 && $_POST['reg']>0){?> selected<?php }?>>Ninguno</option>
			            <option value="1" <?php if($Factura['tipo_documento_origen']==1 || $_POST['reg']==0){?> selected<?php }?>>Tickets</option>
			            <option value="2"<?php if($Factura['tipo_documento_origen']==2){?> selected<?php }?>>Pagos caja</option>
			        	</select>
			        </div>
			        <?php 
			        $folios = '';
			        if($_POST['reg'] > 0 && $Factura['tipo_documento_origen']>0){
			        	if ($Factura['tipo_documento_origen']==1){
			        		$resFolios = mysql_query("SELECT GROUP_CONCAT(cve) as folios FROM cobro_engomado WHERE plaza={$_POST['cveplaza']} AND factura={$_POST['reg']} AND estatus!='C'");
			        	}
			        	else{
			        		$resFolios = mysql_query("SELECT GROUP_CONCAT(cve) as folios FROM pagos_caja WHERE plaza={$_POST['cveplaza']} AND factura={$_POST['reg']} AND estatus!='C'");
			        	}
			        	$Folios = mysql_fetch_assoc($resFolios);
			        	$folios = $Folios['folios'];
			        }
			        ?>
			        <div class="form-group col-sm-4">
						<label for="folios">Folios</label>
			            <input type="text" class="form-control" id="folios" name="folios" value="<?php echo $folios;?>">
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="observaciones">Observaciones</label>
			            <textarea class="form-control" id="obs" name="obs" rows="3"><?php echo utf8_encode($Factura['obs']);?></textarea>
			        </div>
			        
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="tipo_relacion">Tipo Relaci&oacute;n</label>
			            <select id="tipo_relacion" name="tipo_relacion" class="form-control"><option value="">Ninguna</option>
			            <?php
			            $res = mysql_query("SELECT cve, nombre FROM tiporelacion_sat  ORDER BY cve");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'"';
			            	if($Factura['tipo_relacion']==$row['cve']) echo ' selected';
			            	echo '>'.utf8_encode($row['nombre']).'</option>';
			            }
			            ?>
			        	</select>
			        </div>
			        <div class="form-group col-sm-5">
						<label for="uuidsrelacionados">CFDIS RELACIONADOS</label>
			            <input type="text" class="form-control" id="uuidsrelacionados" name="uuidsrelacionados" value="<?php echo $Factura['uuidsrelacionados'];?>">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="sin_timbrar">Prefactura</label>
			            <input type="checkbox" class="form-control" id="sin_timbrar" name="sin_timbrar" value="1"<?php if($Factura['sin_timbrar']==1){?> checked<?php }?>>
			        </div>
			    </div>
			    <div class="form-row">
					<div class="form-group col-sm-3">
						<label for="subtotal">Subtotal</label>
			            <input type="number" class="form-control" id="subtotal" name="subtotal" placeholder="Subtotal" value="<?php echo $Factura['subtotal'];?>" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="sucursal">IVA</label>
			            <input type="iva" class="form-control" id="iva" name="iva" placeholder="IVA" value="<?php echo $Factura['iva'];?>" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="total">Total</label>
			            <input type="number" class="form-control" id="total" name="total" placeholder="Total" value="<?php echo $Factura['total'];?>" readOnly>
			        </div>
			    </div>
		    </div>
		</div>
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Productos&nbsp;&nbsp;<span style="cursor:pointer;" class="btn-circle .btn-lg btn-info" onClick="agregar_producto();">+</span></h6>
			</div>
			<div class="card-body" id="divproductos">
				<div class="form-row">
					<div class="col-sm-1" style="text-align: center;"><label>No. Ident.</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>C&oacute;digo SAT</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Cantidad</label></div>
					<div class="col-sm-1 engomadosdetalle" style="text-align: center;"><label>Engomado</label></div>
					<div class="col-sm-4" style="text-align: center;"><label>Descripcion</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Precio</label></div>
					<div class="col-sm-2" style="text-align: center;"><label>Importe</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Borrar</label></div>
				</div>
				<?php
					$i=0;
					$resDetalles = mysql_query("SELECT * FROM facturasmov WHERE plaza={$_POST['cveplaza']} AND cvefact={$_POST['reg']} AND cvefact>0");
					while($Detalle = mysql_fetch_assoc($resDetalles)){
				?>
					<div class="form-row">
						<div class="col-sm-1" style="text-align: center;"><input type="text" class="form-control" name="no_ident[]" value="<?php echo $Detalle['no_ident'];?>" id="no_ident_<?php echo $i; ?>"></div>
						<div class="col-sm-1" style="text-align: center;"><select id="claveproductosat_<?php echo $i;?>" name="claveproductosat[]" class="form-control"><option value="77121503">77121503</option><option value="80141600"<?php if($Detalle['claveproductosat']=='80141600'){?> selected<?php }?>>80141600</option></select></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control cantidades" name="cantidad[]" value="<?php echo $Detalle['cantidad'];?>" id="cantidad_<?php echo $i; ?>" onKeyUp="calcular()"></div>
						<div class="col-sm-1 engomadosdetalle" style="text-align: center;"><select id="engomados_<?php echo $i;?>" name="engomados[]" class="form-control" onChange="traerPrecio(<?php echo $i;?>)"><?php
						foreach($arreglo_engomados as $k=>$v){
							echo '<option value="'.$k.'" precio="'.$v['precio'].'"';
							if($Detalle['engomado']==$k) echo ' selected';
							echo '>'.utf8_encode($v['nombre']).'</option>';
						}
						?></select></div>
						<div class="col-sm-4" style="text-align: center;"><input type="text" class="form-control conceptos" name="concepto[]" value="<?php echo $Detalle['concepto'];?>" id="concepto_<?php echo $i; ?>" ></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control precios" name="precio[]" value="<?php echo $Detalle['precio'];?>" id="precio_<?php echo $i; ?>" onChange="calcular()"></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[]" value="<?php echo $Detalle['importe'];?>" id="importe_<?php echo $i; ?>" readOnly></div>
						<div class="col-sm-1" style="text-align: center;"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="$(this).parent().parent().remove();calcular();" title="Borrar"></i></div>
					</div>
				<?php
						$i++;
					}
					if($i==0){
				?>
					<div class="form-row">
						<div class="col-sm-1" style="text-align: center;"><input type="text" class="form-control" name="no_ident[]" value="" id="no_ident_<?php echo $i; ?>"></div>
						<div class="col-sm-1" style="text-align: center;"><select id="claveproductosat_<?php echo $i;?>" name="claveproductosat[]" class="form-control"><option value="77121503">77121503</option><option value="80141600">80141600</option></select></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control cantidades" name="cantidad[]" value="" id="cantidad_<?php echo $i; ?>" onKeyUp="calcular()"></div>
						<div class="col-sm-1 engomadosdetalle" style="text-align: center;"><select id="engomados_<?php echo $i;?>" name="engomados[]" class="form-control" onChange="traerPrecio(<?php echo $i;?>)"><?php echo $selectengomados;?></select></div>
						<div class="col-sm-4" style="text-align: center;"><input type="text" class="form-control conceptos" name="concepto[]" value="" id="concepto_<?php echo $i; ?>" ></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control precios" name="precio[]" value="" id="precio_<?php echo $i; ?>" onChange="calcular()"></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[]" value="" id="importe_<?php echo $i; ?>" readOnly></div>
						<div class="col-sm-1" style="text-align: center;"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="$(this).parent().parent().remove();calcular();" title="Borrar"></i></div>
					</div>
				<?php
						$i++;
					}
				?>
			</div>
			<input type="hidden" name="contadorproducto" id="contadorproducto" value="<?php echo $i; ?>">
		</div>
    </div>
</div>
<script>
	function traer_usocfdi(){
		var usocfdi = $('#cliente').find('option:selected').attr('usocfdi');
		$('#usocfdi').val(usocfdi);
	}
	function agregar_producto(){
		contador = $('#contadorproducto').val()/1;
		$('#divproductos').append('\
			<div class="form-row">\
				<div class="col-sm-1" style="text-align: center;"><input type="text" class="form-control" name="no_ident[]" value="" id="no_ident_'+contador+'"></div>\
				<div class="col-sm-1" style="text-align: center;"><select id="claveproductosat_'+contador+'" name="claveproductosat[]" class="form-control"><option value="77121503">77121503</option><option value="80141600">80141600</option></select></div>\
				<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control cantidades" name="cantidad[]" value="" id="cantidad_'+contador+'" onKeyUp="calcular()"></div>\
				<div class="col-sm-1 engomadosdetalle" style="text-align: center;"><select id="engomados_'+contador+'" name="engomados[]" class="form-control" onChange="traerPrecio('+contador+')"><?php echo $selectengomados;?></select></div>\
				<div class="col-sm-4" style="text-align: center;"><input type="text" class="form-control conceptos" name="concepto[]" value="" id="concepto_'+contador+'" ></div>\
				<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control precios" name="precio[]" value="" id="precio_'+contador+'" onChange="calcular()"></div>\
				<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[]" value="" id="importe_'+contador+'" readOnly></div>\
				<div class="col-sm-1" style="text-align: center;"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="$(this).parent().parent().remove();calcular();" title="Borrar"></i></div>\
			</div>');
		contador++;
	    $('#contadorproducto').val(contador);
	    mostrar_folios(false);
	}

	function calcular(){
		var subt = 0;
		var tot = 0;
		var iv = 0;
		$('.cantidades').each(function(){
			div = $(this).parent().parent();
			importe = this.value*div.find('.precios').val();
			div.find('.importes').val(importe.toFixed(6));
			subt += importe.toFixed(6)/1;
			var ivad = importe.toFixed(6)*0.16;
			iv+=ivad.toFixed(6)/1;
		});
		$('#subtotal').val(subt.toFixed(2));
		$('#iva').val(iv.toFixed(2));
		tot = $('#subtotal').val()/1 + $('#iva').val()/1;
		$('#total').val(tot.toFixed(2));
	}

	function traerPrecio(linea){
		var precio = $('#engomados_'+linea).find('option:selected').attr('precio')/1.16;
		precio = precio.toFixed(6);
		$('#precio_'+linea).val(precio);
		var concepto = $('#engomados_'+linea).find('option:selected').html();
		$('#concepto_'+linea).val(concepto);
		calcular();
	}

	function mostrar_folios(limpiar){
		if(limpiar){
			$('#folios').val('');
		}
		if ($('#tipo_documento_origen').val() == '0'){
			$('#folios').parents('div:first').hide();
			//$('.conceptos').removeAttr('readOnly');
			//$('.engomadosdetalle').hide();
		}
		else{
			$('#folios').parents('div:first').show();
			//$('.engomadosdetalle').show();
			//$('.conceptos').attr('readOnly', 'readOnly');
		}
	}

	<?php if($_POST['reg']>0){ ?>
	mostrar_folios(false);
	calcular();
	<?php } ?>
</script>
<?php
}

if($_POST['cmd']==11){
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<button type="button" class="btn btn-success" onClick="atcr('facturas.php','',12,0);">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<button type="button" class="btn btn-primary" onClick="atcr('facturas.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-header">
				<h6 class="m-0 font-weight-bold text-secondary">Datos</h6>
			</div>
			<div class="card-body">
				<div class="form-row">
					
			        <div class="form-group col-sm-4">
						<label for="mes">Mes</label>
			            <select name="mes" id="mes" class="form-control">
			            	<?php 
			            	$res1 = mysql_query("SELECT LEFT(fecha,7) mes FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND estatus!='C' GROUP BY LEFT(fecha,7) ORDER BY LEFT(fecha,7) DESC");
			            	while($row1 = mysql_fetch_assoc($res1)){
			            		echo '<option value="'.$row1['mes'].'">'.$row1['mes'].'</option>';
			            	}
			            	?>
			            </select>
			        </div>
			        <div class="form-group col-sm-1">
			        	<br>
			        	<button type="button" class="btn btn-primary" onClick="buscar()">Buscar</button>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="obs">Observaciones</label>
			            <textarea class="form-control" id="obs" name="obs" rows="3"></textarea>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-3">
						<label for="sucursal">Cant. Ventas</label>
			            <input type="number" class="form-control" id="registros" name="registros" placeholder="Cant. Ventas" readOnly>
			        </div>
					<div class="form-group col-sm-3">
						<label for="sucursal">Subtotal</label>
			            <input type="number" class="form-control" id="subtotal" name="subtotal" placeholder="Subtotal" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="sucursal">IVA</label>
			            <input type="number" class="form-control" id="iva" name="iva" placeholder="IVA" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="sucursal">Total</label>
			            <input type="number" class="form-control" id="total" name="total" placeholder="Total" readOnly>
			        </div>
			    </div>
		    </div>
		</div>
    </div>
</div>
<script>
	function buscar(){
		waitingDialog.show();
		$.ajax({
			url: 'facturas.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 40,
				cveplaza: <?php echo $_POST['cveplaza'];?>,
				mes: $('#mes').val(),
			},
			success: function(data) {
				waitingDialog.hide();
				if(data.error==1){
					sweetAlert('Error', data.mensaje, 'error');
				}
				$('#registros').val(data.registros);
				$('#subtotal').val(data.subtotal);
				$('#iva').val(data.iva);
				$('#total').val(data.total);
			}
		});
	}
</script>
<?php
}

if($_POST['cmd']==115) {
	header('Content-type: application/vnd.ms-excel');
	header("Content-Disposition: attachment; filename=Facturas.xls");
	header("Pragma: no-cache");
	header("Expires: 0");
	$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='".$_POST['cveplaza']."'");
	$rowplaza = mysql_fetch_array($resplaza);
	

	$html= '<table width="100%" border="0" cellpadding="4" cellspacing="1"  id="tabla1" >';
	$html.= '<tr style="font-size:28px"><td align="center" colspan="12">Verificentros Plaza '.$rowplaza['numero'].'</td></tr><tr>
			<td align="center" style="font-size:24px" colspan="12">Desglose de Venta por Periodo: '.$_POST['busquedafechaini'].' al '.$_POST['busquedafechafin'].'</td>
		 </tr>
		 ';
	$html.= '</table>';
	$html.= '<br>
	
	
	<div class="table-responsive">
	<table class="table table-bordered" id="dataTable_i" width="100%" cellspacing="0" border="1">
    	<thead>
			<tr>
					<th></th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Tipo Factura</th>
				<th>Cliente</th>
				<th>RFC</th>
				<th>Tipo de Pago</th>
				<th>Total</th>
				<th>Estatus</th>
				<th>Ticket</th>
				<th>Fecha Ticket</th>
				<th>Placa Ticket</th>
				<th>Usuario</th>
			</tr>
		</thead>';
	$columnas=array('', '', "CONCAT(a.folio, ' ', a.serie)", "CONCAT(a.fecha, ' ', a.hora)", 'a.tipo_pag', "b.nombre", "b.rfc", 'c.nombre', "IF(a.estatus='C', 0, a.total)", "IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado'))");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY CONCAT(a.serie,' ',a.cve)";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
	if($_POST['busquedafolio']>0){
		$where .= " AND a.folio = '{$_POST['busquedafolio']}'";
	}
	else{
		if($_POST['busquedacliente'] != ''){
			$where .= " AND CONCAT(b.nombre) LIKE '%{$_POST['busquedacliente']}%'";
		}

		if($_POST['busquedafechaini'] != ''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin'] != ''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
		}
		if($_POST['busquedarfc'] != ''){
			$where .= " AND b.rfc = '{$_POST['busquedarfc']}'";
		}
		if($_POST['busquedaestatus']==1) {
			$where .= " AND a.estatus!='C' AND a.respuesta1!=''";
		}
		elseif($_POST['busquedaestatus']==2) {
			$where .= " AND a.estatus!='C' AND a.respuesta1=''";
		}
		elseif($_POST['busquedaestatus']==3) {
			$where .= " AND a.estatus='C'";
		}
	}

	$nivelUsuario = nivelUsuario();
	$r="SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.total, 0)) as total FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente{$where}";
	$res = mysql_query($r);
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'total' => $registros['total'],
		'existencia_timbres' => existencia_timbres($_POST['cveplaza'])
	);
	$r2="SELECT a.plaza, a.cve, a.serie, a.folio, a.fecha, a.hora, IF(a.tipo_pag=0, 'Contado', 'Credito') as nomtipopag, b.nombre as nomcliente, b.rfc as rfccli, c.nombre as nomtipopagofac, IF(a.estatus='C', 0, a.total) as total, IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado')) as nomestatus, d.usuario, a.estatus, a.respuesta1, a.tipo_documento_origen FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN tipos_pago_factura c ON c.cve = a.tipo_pago LEFT JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby}";
	$res = mysql_query($r2);
	$tmonto = 0;
	while($row = mysql_fetch_array($res)){
		$extras = '';
		$chk = '<input type="checkbox" class="form-control chks" name="fdescargar[]" value="'.$row['cve'].'"';
		if($_POST['checkall'] == 'true') $chk .= ' checked';
		$chk .= '>';
		if($row['estatus'] != 'C' && $row['respuesta1'] == ''){
			$extras .= '&nbsp;<i class="fas fa-cloud-upload-alt fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="timbrar('.$row['cve'].')" title="Timbrar"></i>';
			$chk = '';
		}
		if($row['respuesta1'] != ''){
			$extras .= '&nbsp;<i class="fas fa-file-code fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="atcr(\'facturas.php\',\'_blank\',101,'.$row['cve'].')" title="XML"></i>
			&nbsp;&nbsp;<i class="fas fa-mail-bulk fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="reenviarcorreo('.$row['cve'].')" title="Reenviar Correo"></i>';
		}
		if($row['estatus'] != 'C' && $nivelUsuario>2){
			$extras .= '&nbsp;<i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="cancelarr('.$row['cve'].')" title="Cancelar"></i>';
		}
		$tickets = '';
		$fechas_tickets='';
		$placas_tickets='';

		if($row['tipo_documento_origen'] <= 1){
			$res2=mysql_query("SELECT b.* FROM venta_engomado_factura a INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.venta WHERE a.plaza='{$row['plaza']}' AND a.factura='{$row['cve']}' AND b.estatus!='C'");
			if(mysql_num_rows($res2)>10){
				$fechas_tickets = '';
				$placas_tickets = '';
				$tickets = mysql_num_rows($res2);
			}
			elseif(mysql_num_rows($res2)>0){
				while($row2 = mysql_fetch_array($res2)){
					$tickets .= '<br>'.$row2['cve'];
					$fechas_tickets .= '<br>'.$row2['fecha'];
					$placas_tickets .= '<br>'.$row2['placa'];
				}
				$tickets = substr($tickets, 4);
				$fechas_tickets = substr($fechas_tickets, 4);
				$placas_tickets = substr($placas_tickets, 4);
			}
		}
		elseif($row['tipo_documento_origen']==2){
			$res2 = mysql_query("SELECT cve FROM pagos_caja WHERE plaza='{$row['plaza']}' AND factura='{$row['cve']}' AND estatus!='C'");
			while($row2 = mysql_fetch_array($res2)){
				$tickets .= '<br>P'.$row2['cve'];
			}
		}

			
			$html.='<tr>
			<td>'.$chk.'</td>';
			$html.='<td>'.$row['serie'].' '.$row['folio'].'</td>';
			$html.='<td>'.mostrar_fechas($row['fecha']).' '.$row['hora'].'</td>';
			$html.='<td>'.utf8_encode($row['nomtipopag']).'</td>';
			$html.='<td>'.utf8_encode($row['nomcliente']).'</td>';
			$html.='<td>'.utf8_encode($row['rfccli']).'</td>';
			$html.='<td>'.utf8_encode($row['nomtipopagofac']).'</td>';
			$html.='<td>'.number_format($row['total'],2).'</td>';
			$html.='<td>'.$row['nomestatus'].'</td>';
			$html.='<td>'.$tickets.'</td>';
			$html.='<td>'.$fechas_tickets.'</td>';
			 $html.='<td>'.$placas_tickets.'</td>';
			 $html.='<td>'.$row['usuario'].'</td></tr>';
		
	}
	$html.='</table>
	</div>';
	//echo json_encode($resultado);
	
	echo $html;
	
	
	//echo buscar_i();
	
	
}

if($_POST['cmd']==40){
	$resultado = array('error' => 0, 'mensaje' => '', 'registros' => 0, 'subtotal' => 0, 'iva' => 0, 'total' => 0);
	
	$fecha_ini = $_POST['mes'].'-01';
	$fecha_fin = date( "Y-m-t" , strtotime ( "+ 1 day" , strtotime($fecha_ini) ) );
		
	$res = mysql_query("SELECT COUNT(cve) as registros, SUM(monto) as total, SUM(ROUND(monto/1.16,2)) as subtotal FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND estatus='A' AND fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}' AND tipo_venta=0 AND tipo_pago IN (1,5,7) AND (factura=0 OR notacredito>0)");
	$row = mysql_fetch_assoc($res);
	$resultado['registros'] = $row['registros'];
	
	$resultado['subtotal'] = number_format($row['subtotal'],2,'.','');
	$resultado['iva'] = number_format($row['total']-$row['subtotal'],2,'.','');
	$resultado['total'] = number_format($row['total'],2,'.','');
	
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');

	if(trim($_POST['cliente'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el cliente');
	}
	elseif($_POST['total']==0){
		$resultado = array('error' => 1, 'mensaje' => 'El total de la factura debe de ser mayor a cero');	
	}
	elseif($_POST['tipo_documento_origen']==1 && trim($_POST['folios'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar los tickets');		
	}
	elseif($_POST['tipo_documento_origen']==2 && trim($_POST['folios'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita los pagos caja');		
	}
	elseif($_POST['tipo_documento_origen'] >0){
		$tickets = explode(",", $_POST['folios']);
		if(count($tickets) > 10000){
			$resultado = array('error'=>1,'mensaje'=>'Solo se pueden poner un maximo de 10 tickets por factura');
		}
		elseif($_POST['tipo_documento_origen']==1){
			foreach($tickets as $ticket){
				$res = mysql_query("SELECT cve, fecha, estatus, factura, notacredito FROM cobro_engomado WHERE plaza='".$_POST['cveplaza']."' AND cve='".$ticket."'");
				if($row=mysql_fetch_array($res)){
					if($row['estatus']=='C'){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El ticket {$ticket} esta cancelado\n";
					}
					elseif($row['factura']>0 && $row['factura']!=$_POST['reg'] && $row['notacredito'] == 0){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El ticket {$ticket} ya esta facturado\n";
					}
					elseif($row['tipo_venta']!=0){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El ticket {$ticket} no es venta\n";
					}
					elseif($row['tipo_pago']!=1 && $row['tipo_pago']!=5 && $row['tipo_pago']==7){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El ticket {$ticket} su forma de pago no es facturable\n";
					}
				}
				else{
					$resultado['error'] = 1;
					$resultado['mensaje'] .= "No se encontro ticket ".$ticket."\n";
				}
			}
		}
		elseif($_POST['tipo_documento_origen']==2){
			foreach($tickets as $ticket){
				$res = mysql_query("SELECT cve, fecha, estatus, factura FROM pagos_caja WHERE plaza='".$_POST['cveplaza']."' AND cve='".$ticket."'");
				if($row=mysql_fetch_array($res)){
					if($row['estatus']=='C'){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El pago de caja {$ticket} esta cancelado\n";
					}
					elseif($row['factura']>0 && $row['factura']!=$_POST['reg']){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= "El pago de caja {$ticket} ya esta facturado\n";
					}
				}
				else{
					$resultado['error'] = 1;
					$resultado['mensaje'] .= "No se encontro ticket ".$ticket."\n";
				}
			}
		}
	}

	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$Cliente = mysql_fetch_assoc(mysql_query("SELECT email FROM clientes WHERE cve='{$_POST['cliente']}'"));
		$emailenvio = $Cliente['email'];
		$_POST['tipo_serie'] = 0;
		$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['cveplaza']}'");
		$rowplaza = mysql_fetch_array($resplaza);


		if($_POST['reg']>0){
			$Factura = mysql_fetch_assoc(mysql_query("SELECT * FROM facturas WHERE plaza={$_POST['cveplaza']} AND cve={$_POST['reg']}"));
			mysql_query("DELETE FROM facturasmov WHERE plaza={$_POST['cveplaza']} AND cvefact={$_POST['reg']}");
			if($Factura['tipo_documento_origen']==2){
				mysql_query("UPDATE pagos_caja SET factura=0 WHERE plaza={$_POST['cveplaza']} AND factura={$_POST['reg']}");
			}
			elseif($Factura['tipo_documento_origen']==1){
				mysql_query("UPDATE cobro_engomado SET factura=0, documento=1, notacredito=0 WHERE plaza={$_POST['cveplaza']} AND factura={$_POST['reg']}");
				mysql_query("DELETE FROM venta_engomado_factura WHERE plaza={$_POST['cveplaza']} AND factura={$_POST['reg']}");
			}
			mysql_query("UPDATE facturas SET obs='".addslashes($_POST['obs'])."', cliente='{$_POST['cliente']}', tipo_pago='{$_POST['tipo_pago']}', forma_pago='{$_POST['forma_pago']}', tipo_relacion='{$_POST['tipo_relacion']}', uuidsrelacionados='{$_POST['uuidsrelacionados']}', tipo_documento_origen='{$_POST['tipo_documento_origen']}', sin_timbrar='{$_POST['sin_timbrar']}', condiciones_pago='{$_POST['condiciones_pago']}' WHERE plaza={$_POST['cveplaza']} AND cve={$_POST['reg']}");
			$cvefact=$_POST['reg'];
		}
		else{
			$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['cveplaza']}' AND tipo=0 AND tipodocumento=1");
			$row = mysql_fetch_array($res);
			
			$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM facturas WHERE plaza='{$_POST['cveplaza']}' AND serie='{$row['serie']}'");
			$row1 = mysql_fetch_array($res1);
			if($row['folio_inicial']<$row1[0]){
				$row['folio_inicial'] = $row1[0];
			}
			$insert = "INSERT facturas SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$_POST['cliente']}', tipo_pago='{$_POST['tipo_pago']}', forma_pago='{$_POST['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_serie='".$_POST['tipo_serie']."', tipo_relacion='{$_POST['tipo_relacion']}', uuidsrelacionados='{$_POST['uuidsrelacionados']}', tipo_documento_origen='{$_POST['tipo_documento_origen']}', sin_timbrar='{$_POST['sin_timbrar']}', condiciones_pago='{$_POST['condiciones_pago']}'";
			while(!$resinsert=mysql_query($insert)){
				$row['folio_inicial']++;
				$insert = "INSERT facturas SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$_POST['cliente']}', tipo_pago='{$_POST['tipo_pago']}', forma_pago='{$_POST['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_serie='".$_POST['tipo_serie']."', tipo_relacion='{$_POST['tipo_relacion']}', uuidsrelacionados='{$_POST['uuidsrelacionados']}', tipo_documento_origen='{$_POST['tipo_documento_origen']}', sin_timbrar='{$_POST['sin_timbrar']}', condiciones_pago='{$_POST['condiciones_pago']}'";
			}
			
			$cvefact=mysql_insert_id();
		}
		$documento=array();
		require_once("nusoap/nusoap.php");
		$fserie=$row['serie'];
		$ffolio=$row['folio_inicial'];
		$_POST['iva']=0;
		$_POST['subtotal']=0;
		$i=0;
		foreach($_POST['cantidad'] as $k=>$v){
			if($v>0){
				$_POST['ivap'][$k]=16;
				$claveprod = ($_POST['claveproductosat'][$k] != '') ? $_POST['claveproductosat'][$k] : '77121503';
				if(trim($_POST['unidad'][$k])=="") $_POST['unidad'][$k] = "Unidad de servicio";
				$importe_iva=round($_POST['importe'][$k]*$_POST['ivap'][$k]/100,6);
				if($_POST['engomados'][$k]>0){
					$res2 = mysql_query("SELECT a.cve, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' AND a.cve='{$_POST['engomados'][$k]}'");
					$row2=mysql_fetch_assoc($res2);
					$_POST['importe'] [$k]= round($row2['precio']*$v/1.16,6);
					$importe_iva = round($row2['precio']*$v-$_POST['importe'] [$k],6); 
					$_POST['precio'][$k]=round($_POST['importe'] [$k]/$v,6);
				}
				mysql_query("INSERT facturasmov SET plaza='".$_POST['cveplaza']."',cvefact='$cvefact',cantidad='".$v."',concepto='".$_POST['concepto'][$k]."',
				precio='".$_POST['precio'][$k]."',importe='".$_POST['importe'][$k]."',iva='".$_POST['ivap'][$k]."',importe_iva='$importe_iva',unidad='".$_POST['unidad'][$k]."',
				engomado='".$_POST['engomados'][$k]."',claveprodsat='{$claveprod}',claveunidadsat='E48', no_ident='".addslashes($_POST['no_ident'][$k])."'");
			
				$i++;
				$_POST['subtotal']+=$_POST['importe'][$k];
				$_POST['iva']+=$importe_iva;

			}
		}
		$_POST['subtotal'] = round($_POST['subtotal'],2);
		$_POST['iva'] = round($_POST['iva'],2);
		$_POST['total']=$_POST['subtotal']+$_POST['iva'];

		mysql_query("UPDATE facturas SET subtotal='{$_POST['subtotal']}', iva='{$_POST['iva']}', total='{$_POST['total']}' WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
		if($_POST['tipo_documento_origen']==2){
				mysql_query("UPDATE pagos_caja SET factura='{$cvefact}' WHERE plaza='{$_POST['cveplaza']}' AND cve IN ({$_POST['folios']})");
		}
		elseif($_POST['tipo_documento_origen']==1){
			mysql_query("UPDATE cobro_engomado SET factura='{$cvefact}', documento=1, notacredito=0 WHERE plaza='{$_POST['cveplaza']}' AND cve IN ({$_POST['folios']})");
			mysql_query("INSERT INTO venta_engomado_factura (plaza,venta,factura) SELECT {$_POST['cveplaza']}, cve, factura FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND factura='{$cvefact}'");
		}

		$documento = genera_arreglo_facturacion($_POST['cveplaza'], $cvefact, 'I');
		$resultadotimbres = validar_timbres($_POST['cveplaza']);
		if($resultadotimbres['seguir'] && $_POST['sin_timbrar']!=1){
			$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$_POST['cveplaza']}'");
			$rowempresa = mysql_fetch_assoc($rsSucursal);
			$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);	
			$err = $oSoapClient->getError();
			if($err!=""){
				echo "error1:".$err;
			}
			else{
				$oSoapClient->timeout = 300;
				$oSoapClient->response_timeout = 300;
				$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $rowempresa['idplaza'],'rfcemisor' => $rowempresa['rfc'],'idcertificado' => $rowempresa['idcertificado'],'documento' => $documento, 'usuario' => $rowempresa['usuario'],'password' => $rowempresa['pass']));
				if ($oSoapClient->fault) {
					echo '<p><b>Fault: ';
					echo '</b></p>';
					echo '<p><b>Request: <br>';
					echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Response: <br>';
					echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
					echo '<p><b>Debug: <br>';
					echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
				}
				else{
					$err = $oSoapClient->getError();
					if ($err){
						echo '<p><b>Error: ' . $err . '</b></p>';
						echo '<p><b>Request: <br>';
						echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
						echo '<p><b>Response: <br>';
						echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
						echo '<p><b>Debug: <br>';
						echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
					}
					else{
						if($respuesta['resultado']){
							require_once("imp_factura.php");
							mysql_query("UPDATE facturas SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}', sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}', sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}', fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
							WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
							mysql_query("UPDATE facturas SET rfc_cli='{$row['rfc']}', nombre_cli='{$row['nombre']}', cp_cli='{$row['codigopostal']}'
							WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
							//Tomar la informacion de Retorno
							$dir="cfdi/comprobantes/";
							$dir2="cfdi/";
							//Leer el Archivo Zip
							$fileresult=$respuesta['archivos'];
							$strzipresponse=base64_decode($fileresult);
							$filename='cfdi_'.$_POST['cveplaza'].'_'.$cvefact;
							file_put_contents($dir2.$filename.'.zip', $strzipresponse);
							$zip = new ZipArchive;
							if ($zip->open($dir2.$filename.'.zip') === TRUE){
								$strxml=$zip->getFromName('xml.xml');
								file_put_contents($dir.$filename.'.xml', $strxml);
								$zip->close();		
								generaFacturaPdf($_POST['cveplaza'],$cvefact);
								if($emailenvio!=""){
									$mail = obtener_mail();		
									$mail->FromName = "Verificentros Plaza ".$rowempresa['nombre'];
									$mail->Subject = "Factura ".$fserie." ".$ffolio;
									$mail->Body = "Factura ".$fserie." ".$ffolio;
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo){
										$mail->AddAddress(trim($correo));
									}
									$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$cvefact}.pdf", "Factura {$fserie} {$ffolio}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_{$cvefact}.xml", "Factura {$fserie} {$ffolio}.xml");
									$mail->Send();
								}	
								
								@unlink("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$cvefact}.pdf");
							}
							else 
								$strmsg='Error al descomprimir el archivo';
							if(file_exists($dir2.$filename.'.zip')){
								unlink($dir2.$filename.'.zip');
							}
						}
						else{
							$strmsg=$respuesta['mensaje'];
						}
						echo $strmsg;
					}
				}
			}
		}

		echo '<script>atcr("facturas.php","",0,0);</script>';
	}
	

	exit();
}

if($_POST['cmd']==12){
	$Cliente = mysql_fetch_assoc(mysql_query("SELECT * FROM clientes WHERE plaza='{$_POST['cveplaza']}' AND rfc='XAXX010101000'"));
	if ($Cliente['cve']>0){
		$emailenvio = $Cliente['email'];
		$fecha_ini = $_POST['mes'].'-01';
		$fecha_fin = date( "Y-m-t" , strtotime ( "+ 1 day" , strtotime($fecha_ini) ) );
		$anio = substr($_POST['mes'],0,4);
		$meses = substr($_POST['mes'],5,2);
		$seguir = true;
		while($seguir){
			$res3 = mysql_query("SELECT a.cve, a.monto, a.engomado, b.nombre FROM cobro_engomado a INNER JOIN engomados b ON b.cve = a.engomado WHERE a.plaza='{$_POST['cveplaza']}' AND a.estatus='A' AND a.fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}' AND a.tipo_venta = 0 AND a.tipo_pago IN (1,5,7) AND (a.factura = 0 OR a.notacredito>0) LIMIT 900");
			if (mysql_num_rows($res3)==0){
				$seguir = false;
				break;
			}


			$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['cveplaza']}'");
			$rowplaza = mysql_fetch_array($resplaza);
			$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['cveplaza']}' AND tipo=0 AND tipodocumento=1");
			$row = mysql_fetch_array($res);
			
			$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM facturas WHERE plaza='{$_POST['cveplaza']}' AND serie='{$row['serie']}'");
			$row1 = mysql_fetch_array($res1);
			if($row['folio_inicial']<$row1[0]){
				$row['folio_inicial'] = $row1[0];
			}
			$insert = "INSERT facturas SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$Cliente['cve']}', tipo_pago='1', forma_pago='0', usuario='{$_POST['cveusuario']}', tipo_serie='".$_POST['tipo_serie']."', tipo_relacion='', uuidsrelacionados='', tipo_documento_origen='1', periodicidad='04', meses='{$meses}', anio='{$anio}'";
			while(!$resinsert=mysql_query($insert)){
				$row['folio_inicial']++;
				$insert = "INSERT facturas SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$Cliente['cve']}', tipo_pago='1', forma_pago='0', usuario='{$_POST['cveusuario']}', tipo_serie='".$_POST['tipo_serie']."', tipo_relacion='', uuidsrelacionados='', tipo_documento_origen='1', periodicidad='04', meses='{$meses}', anio='{$anio}'";
			}
			
			$cvefact=mysql_insert_id();

			$_POST['subtotal']=0;
			$_POST['iva']=0;
			$_POST['total']=0;
			$tickets=array();
			while($row3 = mysql_fetch_array($res3)){
				$tickets[] = $row3['cve'];
				$precio = round($row3['monto']/1.16,2);
				$unidad = "Unidad de Servicio";
				$importe_iva=round($row3['monto']-$precio,2);
				mysql_query("INSERT facturasmov SET plaza='{$_POST['cveplaza']}', cvefact='{$cvefact}', cantidad='1', concepto='Venta {$row3['nombre']}', ticket='{$row3['cve']}', precio='{$precio}', importe='{$precio}', iva='16', importe_iva='{$importe_iva}', unidad='{$unidad}', engomado='{$row3['engomado']}',claveprodsat='77121503', claveunidadsat='E48'");
				$_POST['subtotal']+=$precio;
				$_POST['iva']+=$importe_iva;
				$_POST['total']+=$row3['monto'];
			}

			$pagos = array();


			mysql_query("UPDATE facturas SET subtotal='{$_POST['subtotal']}', iva='{$_POST['iva']}', total='{$_POST['total']}' WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
			if(count($tickets)>0){
				mysql_query("UPDATE cobro_engomado SET factura='{$cvefact}', documento=1, notacredito=0 WHERE plaza='{$_POST['cveplaza']}' AND cve IN (".implode(',',$tickets).")");
				mysql_query("INSERT INTO venta_engomado_factura (plaza,venta,factura) SELECT {$_POST['cveplaza']},cve,factura FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND factura='{$cvefact}'");
			}
			if($pagos!=''){
				mysql_query("UPDATE pagos_caja SET factura='{$cvefact}' WHERE plaza='{$_POST['cveplaza']}' AND cve IN (".$pagos.")");
			}
			require_once("nusoap/nusoap.php");
			$documento = genera_arreglo_facturacion($_POST['cveplaza'], $cvefact, 'I');
			$resultadotimbres = validar_timbres($_POST['cveplaza']);
			if($resultadotimbres['seguir']){
				$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$_POST['cveplaza']}'");
				$rowempresa = mysql_fetch_assoc($rsSucursal);
				$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);	
				$err = $oSoapClient->getError();
				if($err!=""){
					echo "error1:".$err;
				}
				else{
					$oSoapClient->timeout = 300;
					$oSoapClient->response_timeout = 300;
					$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $rowempresa['idplaza'],'rfcemisor' => $rowempresa['rfc'],'idcertificado' => $rowempresa['idcertificado'],'documento' => $documento, 'usuario' => $rowempresa['usuario'],'password' => $rowempresa['pass']));
					if ($oSoapClient->fault) {
						echo '<p><b>Fault: ';
						echo '</b></p>';
						echo '<p><b>Request: <br>';
						echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
						echo '<p><b>Response: <br>';
						echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
						echo '<p><b>Debug: <br>';
						echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
					}
					else{
						$err = $oSoapClient->getError();
						if ($err){
							echo '<p><b>Error: ' . $err . '</b></p>';
							echo '<p><b>Request: <br>';
							echo htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
							echo '<p><b>Response: <br>';
							echo htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
							echo '<p><b>Debug: <br>';
							echo htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
						}
						else{
							if($respuesta['resultado']){
								mysql_query("UPDATE facturas SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}', sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}', sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}', fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
								WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
								mysql_query("UPDATE facturas SET rfc_cli='{$Cliente['rfc']}', nombre_cli='".addslashes($Cliente['nombre'])."', cp_cli='{$Cliente['codigopostal']}'
								WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
								//Tomar la informacion de Retorno
								$dir="cfdi/comprobantes/";
								$dir2="cfdi/";
								//Leer el Archivo Zip
								require_once('imp_factura.php');
								$fileresult=$respuesta['archivos'];
								$strzipresponse=base64_decode($fileresult);
								$filename='cfdi_'.$_POST['cveplaza'].'_'.$cvefact;
								file_put_contents($dir2.$filename.'.zip', $strzipresponse);
								$zip = new ZipArchive;
								if ($zip->open($dir2.$filename.'.zip') === TRUE){
									$strxml=$zip->getFromName('xml.xml');
									file_put_contents($dir.$filename.'.xml', $strxml);
									$zip->close();		
									generaFacturaPdf($_POST['cveplaza'],$cvefact);
									if($emailenvio!=""){
										$mail = obtener_mail();		
										$mail->FromName = "Verificentros Plaza ".$rowempresa['nombre'];
										$mail->Subject = "Factura ".$fserie." ".$ffolio;
										$mail->Body = "Factura ".$fserie." ".$ffolio;
										$correos = explode(",",trim($emailenvio));
										foreach($correos as $correo){
											$mail->AddAddress(trim($correo));
										}
										$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$cvefact}.pdf", "Factura {$fserie} {$ffolio}.pdf");
										$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_{$cvefact}.xml", "Factura {$fserie} {$ffolio}.xml");
										$mail->Send();
									}	
									
									@unlink("cfdi/comprobantes/factura_{$_POST['cveplaza']}_{$cvefact}.pdf");
								}
								else 
									$strmsg='Error al descomprimir el archivo';
								if(file_exists($dir2.$filename.'.zip')){
									unlink($dir2.$filename.'.zip');
								}
							}
							else{
								$strmsg=$respuesta['mensaje'];
							}
							echo $strmsg;
						}
					}
				}
			}
			
			
		}

		echo '<script>atcr("facturas.php","",0,0);</script>';
	}
	else{
		$resultado['error']=1;
		$resultado['mensaje'] = 'No hay cliente de publico en general';
		echo json_encode($resultado);
	}
	

	exit();
}
?>