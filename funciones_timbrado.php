<?php


function cancelar_timbrado($plaza, $cvefact) {
	require_once("nusoap/nusoap.php");
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');
	$resultadotimbres = validar_timbres($plaza);
	$res = mysql_query("SELECT * FROM facturas WHERE plaza = '{$plaza}' AND cve='{$cvefact}'");
	$row = mysql_fetch_array($res);
	if($resultadotimbres['seguir']){

		$Empresa = mysql_fetch_assoc(mysql_query("SELECT * FROM datosempresas WHERE plaza='{$plaza}'"));
		$res1 = mysql_query("SELECT * FROM clientes WHERE cve='{$row['cliente']}'");
		$row1 = mysql_fetch_array($res1);
		$emailenvio = $row1['email'];

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
						mysql_query("UPDATE facturas SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW(),respuesta2='{$respuesta['mensaje']}', motivo_cancelacion='{$_POST['motivocancelacion']}', uuidsustituye='{$_POST['uuidsustituye']}' WHERE plaza='{$plaza}' AND cve='{$cvefact}'");
						mysql_query("UPDATE pagos_caja SET factura=0 WHERE plaza='{$plaza}' AND factura='{$cvefact}'");
						mysql_query("UPDATE cobro_engomado SET factura=0 WHERE plaza='{$plaza}' AND factura='{$cvefact}'");
						include("imp_factura.php");
						generaFacturaPdf($plaza, $cvefact);
						if($emailenvio!=""){
							$mail = obtener_mail();
							$mail->Subject = "Cancelacion de Factura {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
							$mail->Body = "Cancelacion de Factura {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
							$correos = explode(",",trim($emailenvio));
							foreach($correos as $correo)
								$mail->AddAddress(trim($correo));
							$mail->AddAttachment("cfdi/comprobantes/facturac_{$plaza}_{$cvefact}.pdf", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.pdf");
							$mail->AddAttachment("cfdi/comprobantes/cfdi_{$plaza}_{$cvefact}.xml", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.xml");
							$mail->Send();
						}	
						@unlink("cfdi/comprobantes/facturac_{$plaza}_{$cvefact}.pdf");
					}
					else{
						$strmsg=$respuesta['mensaje'];
						$resultado = array('mensaje' => $strmsg, 'tipo'=>'warning');
					}
				}
			}
		}
	}
	return $resultado;
}

function timbrar($plaza, $cvefact){
	$resultado = array('error' => 0, 'mensaje' => '');
	$rsFactura = mysql_query("SELECT * FROM facturas WHERE plaza='{$plaza}' AND cve = '{$cvefact}'");
	$Factura = mysql_fetch_assoc($rsFactura);
	mysql_query("UPDATE facturas SET fecha=CURDATE(), hora=CURTIME() WHERE plaza='{$plaza}' AND cve='{$cvefact}' AND respuesta1=''");
	$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$Factura['plaza']}'");
	$Sucursal = mysql_fetch_assoc($rsSucursal);
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$Factura['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);
	$resultado = validar_timbres($plaza);
	if($resultado['seguir']){
		$documento = genera_arreglo_facturacion($plaza, $cvefact, 'I');
		require_once('nusoap/nusoap.php');
		$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);
		$err = $oSoapClient->getError();
		if($err!=""){
			$resultado['error']=1;
			$resultado['mensaje']= "error1:".$err;
		}
		else{
			//print_r($documento);
			$oSoapClient->timeout = 300;
			$oSoapClient->response_timeout = 300;
			$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $Sucursal['idplaza'],'rfcemisor' => $Sucursal['rfc'],'idcertificado' => $Sucursal['idcertificado'],'documento' => $documento, 'usuario' => $Sucursal['usuario'],'password' => $Sucursal['pass']));
			if ($oSoapClient->fault) {
				$resultado['error']=1;
				$resultado['mensaje']= '<p><b>Fault: ';
				$resultado['mensaje'].= '<p><b>Fault: ';
				$resultado['mensaje'].= '</b></p>';
				$resultado['mensaje'].= '<p><b>Request: <br>';
				$resultado['mensaje'].= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
				$resultado['mensaje'].= '<p><b>Response: <br>';
				$resultado['mensaje'].= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
				$resultado['mensaje'].= '<p><b>Debug: <br>';
				$resultado['mensaje'].= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
			}
			else{
				$err = $oSoapClient->getError();
				if ($err){
					$resultado['error']=1;
					$resultado['mensaje']= '<p><b>Error: ' . $err . '</b></p>';
					$resultado['mensaje'].= '<p><b>Request: <br>';
					$resultado['mensaje'].= htmlspecialchars($oSoapClient->request, ENT_QUOTES) . '</b></p>';
					$resultado['mensaje'].= '<p><b>Response: <br>';
					$resultado['mensaje'].= htmlspecialchars($oSoapClient->response, ENT_QUOTES) . '</b></p>';
					$resultado['mensaje'].= '<p><b>Debug: <br>';
					$resultado['mensaje'].= htmlspecialchars($oSoapClient->debug_str, ENT_QUOTES) . '</b></p>';
				}
				else{
					if($respuesta['resultado']){
						$resultado['mensaje'] = 'Se timbro correctamente';
						mysql_query("UPDATE facturas SET respuesta1='{$respuesta['uuid']}',seriecertificado='{$respuesta['seriecertificado']}',
						sellodocumento='{$respuesta['sellodocumento']}',uuid='{$respuesta['uuid']}',seriecertificadosat='{$respuesta['seriecertificadosat']}',
						sellotimbre='{$respuesta['sellotimbre']}',cadenaoriginal='{$respuesta['cadenaoriginal']}',
						fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
						WHERE plaza='{$plaza}' AND cve={$cvefact}");

						mysql_query("UPDATE facturas SET rfc_cli='{$Cliente['rfc']}', nombre_cli='{$Cliente['nombre']}', calle_cli='{$row1['calle']}', numext_cli='{$row1['numexterior']}', numint_cli = '{$row1['numinterior']}', colonia_cli = '{$row1['colonia']}', localidad_cli = '{$row1['localidad']}', municipio_cli = '{$row1['municipio']}',estado_cli='{$row1['estado']}', cp_cli='{$Cliente['codigopostal']}'
						WHERE plaza='{$plaza}' AND cve={$cvefact}");
						include("imp_factura.php");
						//Tomar la informacion de Retorno
						$dir="cfdi/comprobantes/";
						//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
						//el zip siempre se deja fuera
						$dir2="cfdi/";
						//Leer el Archivo Zip
						$fileresult=$respuesta['archivos'];
						$strzipresponse=base64_decode($fileresult);
						$filename='cfdi_'.$plaza.'_'.$factura;
						file_put_contents($dir2.$filename.'.zip', $strzipresponse);
						$zip = new ZipArchive;
						if ($zip->open($dir2.$filename.'.zip') === TRUE){
							$strxml=$zip->getFromName('xml.xml');
							file_put_contents($dir.$filename.'.xml', $strxml);
							$zip->close();		
							generaFacturaPdf($plaza, $factura);
							$emailenvio = $Cliente['email'];
							//require_once("phpmailer/class.phpmailer.php");
							if($emailenvio!=""){
								$mail = obtener_mail();
								$mail->Subject = "Factura {$Factura['serie']} {$Factura['folio']}";
								$mail->Body = "Factura {$Factura['serie']} {$Factura['folio']}";
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/factura_{$plaza}_{$cvefact}.pdf", "{$Sucursal['nombre']} {$Factura['serie']} {$Factura['folio']}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdi_{$plaza}_{$cvefact}.xml", "{$Sucursal['nombre']} {$Factura['serie']} {$Factura['folio']}.xml");
								$mail->Send();
							}	
							@unlink("cfdi/comprobantes/factura_{$plaza}{_{$cvefact}.pdf");
							@unlink("cfdi/cfdi_{$plaza}_{$factura}.zip");
						}
						else 
							$resultado['error']=1;
							$resultado['mensaje']='Error al descomprimir el archivo';
							
					}
					else{
						$strmsg=$respuesta['mensaje'];
						$resultado['error']=1;
						$resultado['mensaje']= $strmsg;
					}
					
				}
			}
		}
	}
	return $resultado;
}