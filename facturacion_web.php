<?php
/*echo '<h1>Pagina en mantenimiento<br>Disculpe las molestias</h1>';
exit();*/
//echo '<script>top.window.location.href!="http://gverificentros.com/facturacion";</script>';

if(isset($_POST['cmd']) && $_POST['cmd']==150){

	$body = array(
    'rfc' => $_POST['brfc'],
    'pdf' => base64_encode(file_get_contents($_FILES['constancia']['tmp_name']))
	);
	$url = 'https://api.2ai.io/cfdi/rfc';

	$ch = curl_init($url);

	//$payload = http_build_query($body);
	$payload = json_encode($body);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:c9af4c5ade781ce2e1abd4ab79612d21','Content-type: application/json'));

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$resultado = curl_exec($ch);
	//$resultado = json_decode($resultado, true);
	curl_close($ch);
	echo $resultado;
	exit();
}

if($_POST['recargado'] != 1){
	echo '<form name="forma" id="forma" method="POST" action="facturacion_web.php" enctype="multipart/form-data" class="form-horizontal">
	<input type="hidden" name="recargado" id="recargado" value="1">
	</form>
	<script>document.forma.submit();</script>';
	exit();
}
include("main2_beta.php");

include("globales.php");


$array_forma_pago=array("PAGO EN UNA SOLA EXHIBICION","PAGO EN PARCIALIDADES O DIFERIDO");
$array_tipo_pago=array(0=>"01 EFECTIVO",2=>"03 TRANSFERENCIA ELECTRONICA DE FONDOS",3=>"01 DEPOSITO",4=>"99 NO ESPECIFICADO",5=>"02 CHEQUE DENOMINATIVO",6=>"98 NO APLICA",7=>"04 CREDITO", 9=>"28 TARJETA DE DEBITO");//,1=>"CHEQUE"
$array_tipo_pagosat=array(0=>"01",2=>"03",3=>"01",4=>"99",5=>"02",6=>"98",7=>"04",8=>"99", 9=>"28");//,1=>"CHEQUE"

if($_POST['cmd'] == 200){
	include("imp_factura.php");
	$datos = explode('|', $_POST['reg']);
	$archivo='factura';
	$postfijo='';
	$nombre='Factura';
	if($datos[2] == 2){
		$archivo='nc';
		$postfijo='nc';
		$nombre='NotaCredito';
	}
	$zip = new ZipArchive();
	if($zip->open("cfdi/zip".$archivo."_".$datos[0]."_".$datos[1].".zip",ZipArchive::CREATE)){
		generaFacturaPdf($datos[0], $datos[1], 0, $datos[2]);
		$zip->addFile("cfdi/comprobantes/".$archivo."_".$datos[0]."_".$datos[1].".pdf",$nombre.".pdf");
		$zip->addFile("cfdi/comprobantes/cfdi".$postfijo."_".$datos[0]."_".$datos[1].".xml",$nombre.".xml");
		$zip->close(); 
	    if(file_exists("cfdi/zip".$archivo."_".$datos[0]."_".$datos[1].".zip")){ 
	        header('Content-type: "application/zip"'); 
	        header('Content-Disposition: attachment; filename="'.$nombre.'_'.$datos[1].'.zip"'); 
	        readfile("cfdi/zip".$archivo."_".$datos[0]."_".$datos[1].".zip"); 
	         
	        unlink("cfdi/zip".$archivo."_".$datos[0]."_".$datos[1].".zip"); 
	        @unlink("cfdi/comprobantes/".$archivo."_".$datos[0]."_".$datos[1].".pdf"); 
	    } 
	    else{
			echo '<h1>Ocurrio un problema al generar el archivo favor de intentarlo de nuevo</h1>';
		}
	}
	else{
		echo '<h1>Ocurrio un problema al generar el archivo favor de intentarlo de nuevo</h1>';
	}
	exit();
}

if($_POST['ajax']==10){
	$resultado = array('html' => '');
	$res = mysql_query("SELECT a.*, c.nombre as nomplaza FROM facturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN plazas c ON c.cve = a.plaza WHERE a.estatus!='C' AND b.rfc = '".$_POST['rfc']."' AND a.respuesta1 != '' ORDER BY fecha DESC, hora DESC");
	while($row = mysql_fetch_array($res)){
		$resultado['html'] .=  '
				<tr class="cfacturas"><td align="center"><span style="cursor:pointer;" onClick="atcr(\'facturacion_web.php\',\'\',200,\''.$row['plaza'].'|'.$row['cve'].'|1\');"><img src="images/zip_grande.png"></span></td>
				<td align="left" style="padding: 10px!important">'.utf8_encode($row['nomplaza']).'</td>
				<td align="center" style="padding: 10px!important">'.$row['serie'].' '.$row['folio'].'</td>
				<td align="center" style="padding: 10px!important">'.$row['fecha'].' '.$row['hora'].'</td>
				<td align="right" style="padding: 10px!important">'.$row['total'].'</td></tr>';
	}
	echo json_encode($resultado);				
	exit();
}

if($_POST['ajax']=='buscarRfc'){
	$body = array(
    'rfc' => $_POST['rfc'],
    //'pdf' => base64_encode(file_get_contents('ConstanciaSituacionFiscal.pdf'))
	);
	if (isset($_POST['idcif'])){
		$body['idcif'] = $_POST['idcif'];
	}
	$url = 'https://api.2ai.io/cfdi/rfc';

	$ch = curl_init($url);

	//$payload = http_build_query($body);
	$payload = json_encode($body);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:c9af4c5ade781ce2e1abd4ab79612d21','Content-type: application/json'));

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$resultado = curl_exec($ch);
	//$resultado = json_decode($resultado, true);
	curl_close($ch);
	echo $resultado;
	exit();
}

if($_POST['ajax']=='datosclientes'){
	$resultado = array(
		'nombre' => '',
		'calle' => '',
		'numexterior' => '',
		'numinterior' => '',
		'colonia' => '',
		'localidad' => '',
		'municipio' => '',
		'estado' => '',
		'codigopostal' => ''
	);
	$res = mysql_query("SELECT b.* FROM facturas b WHERE b.rfc_cli = '".$_POST['rfc']."' AND b.estatus!='C' ORDER BY b.fecha DESC, b.hora DESC LIMIT 1");
	if($row = mysql_fetch_array($res)){
		$resultado = array(
			'nombre' => $row['nombre_cli'],
			'calle' => $row['calle_cli'],
			'numexterior' => $row['numext_cli'],
			'numinterior' => $row['numint_cli'],
			'colonia' => $row['colonia_cli'],
			'localidad' => $row['localidad_cli'],
			'municipio' => $row['municipio_cli'],
			'estado' => $row['estado_cli'],
			'codigopostal' => $row['cp_cli']
		);
	}


	echo json_encode($resultado);
	exit();


}

if($_POST['ajax']==1.1){
	$resultado = array('error' => 0, 'html' => '', 'mensaje' => '', 'generar_factura' => 0);
	$limite30 = date( "Y-m-d" , strtotime ( "+ 30 day" , strtotime(date('Y-m-d')) ) );
	$claves = json_decode($_POST['tickets_capturados'], true);
	if(count($claves) == 0){
		$resultado['error'] = 1;
		$resultado['mensaje'] = 'Debe de capturar al menos un código de facturación';
	}
	else{
		$clavesencontradas = array();
		$fecha='';
		foreach($claves as $datosclaves){
			$clave = $datosclaves['codigo'];
			$res = mysql_query("SELECT * FROM claves_facturacion WHERE cve = '".trim($clave)."'");
			if($row=mysql_fetch_array($res)){
				$encontrado = false;
				if(in_array(trim($clave), $clavesencontradas)){
					$encontrado = true;
				}

				if($encontrado){
					$resultado['error'] = 1;
					$resultado['mensaje'] .= 'El código '.$clave.' esta duplicado'."\n";
				}
				else{
					$res=mysql_query("SELECT a.tipo_pago,a.fecha,a.factura,a.estatus,a.cve,a.plaza,(a.monto+IFNULL(f.recuperacion,0)-IFNULL(e.devolucion,0)) as monto,a.placa,b.nombre as engomado,c.nombre as nomplaza, a.notacredito, d.localidad_id 
					FROM cobro_engomado a 
					LEFT JOIN engomados b ON b.cve = a.engomado 
					INNER JOIN plazas c ON c.cve = a.plaza 
					INNER JOIN datosempresas d ON c.cve = d.plaza
					LEFT JOIN 
						(SELECT ticket, SUM(devolucion) as devolucion FROM devolucion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') e ON a.cve = e.ticket
					LEFT JOIN 
						(SELECT ticket, SUM(recuperacion) as recuperacion FROM recuperacion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') f ON a.cve = f.ticket
					WHERE a.plaza = '".$row['plaza']."' AND a.cve='".$row['ticket']."'") or die(mysql_error());
					$row = mysql_fetch_array($res);
					if($row['estatus']=='C'){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'El ticket del código '.$clave.' esta cancelado'."\n";
					}
					elseif(date('Y') == 2016 && substr($row['fecha'],0,4) == 2015 && 1==2){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'No se pueden facturar tickets del 2015'."\n";	
					}
					elseif($fecha!='' && substr($fecha,0,7) != substr($row['fecha'],0,7) && $row['localidad_id'] == 2){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'No se pueden facturar tickets de diferente mes en la misma factura'."\n";	
					}
					elseif($row['factura']>0 && $row['notacredito']==0){
						$resultado['html'] .=  '
						<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>-->';
						$res1=mysql_query("SELECT * FROM facturas WHERE plaza='".$row['plaza']."' AND cve='".$row['factura']."'");
						$row1=mysql_fetch_array($res1);
						if($row1['respuesta1']==""){
							$resultado['html'] .= '<br><b>No Timbrada</b>';
						}
						else{
							$resultado['html'] .=  '&nbsp;&nbsp;<a href="#" onClick="atcr(\'facturacion_web.php\',\'_blank\',\'200\',\''.$row1['plaza'].'|'.$row1['cve'].'|1\');"><img src="images/zip_grande.png" border="0" width="30px" height="30px" title="Descargar"></a>';
							if($row1['estatus'] == 'D') $resultado['html'] .= '<br>DEVUELTA';
							//else $resultado['html'] .=  '&nbsp;&nbsp;<a href="#" onClick="if(confirm(\'Esta seguro de generar la nota de credito de descuento? Se generara por el total de la factura.\'))atcr(\'facturacion_web.php\',\'\',\'3\',\''.$row1['plaza'].'|'.$row1['cve'].'\');"><img src="images/cerrar.gif" border="0" width="15px" height="15px" title="Generar Nota de Credito"></a>';
						}
						$resultado['html'] .=  '</td>
						<td align="left" style="padding: 10px!important">'.$row['nomplaza'].'</td>
						<td align="center" style="padding: 10px!important">(Facturado)<br>'.$row['cve'].'</td>
						<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
						<td align="right" style="padding: 10px!important">'.$row['monto'].'';
						$resultado['html'] .=  '</td></tr>';
						if($row['notacredito']>0){
							$resultado['html'] .=  '
							<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>-->';
							$res1=mysql_query("SELECT * FROM notascredito WHERE plaza='".$row['plaza']."' AND cve='".$row['factura']."'");
							$row1=mysql_fetch_array($res1);
							if($row1['respuesta1']==""){
								$resultado['html'] .= '<br><b>No Timbrada</b>';
							}
							else{
								$resultado['html'] .=  '&nbsp;&nbsp;<a href="#" onClick="atcr(\'facturacion_web.php\',\'_blank\',\'200\',\''.$row1['plaza'].'|'.$row1['cve'].'|2\');"><img src="images/zip_grande.png" border="0" width="25px" height="25px" title="Descargar"></a>';
							}
							$resultado['html'] .=  '</td>
							<td align="left" style="padding: 10px!important">'.$row['nomplaza'].'</td>
							<td align="center" style="padding: 10px!important">(Nota Credito)<br>'.$row['cve'].'</td>
							<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
							<td align="right" style="padding: 10px!important">'.$row['monto'].'';
							$resultado['html'] .=  '</td></tr>';
						}
					}
					elseif($row['fecha']<'2015-05-01'){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'Error en el código '.$clave.', solo se pueden facturar por esta pagina ventas de mayo 2015 en adelante'."\n";
					}
					/*elseif($row['fecha']<date("Y-m").'-01' && $row['localidad_id'] == 2){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'Error en el cÃ³digo '.$clave.', solo se pueden facturar ventas del mes en curso, para facturar ventas del mes anterior mandar sus datos al correo de Soporte Puebla: pueblasoporte@gmail.com'."\n";
					}*/
					elseif($row['fecha']>$limite30 && $row['localidad_id'] == 2){
						$resultado['error'] = 1;
						$resultado['mensaje'] .= 'Vencio el limite de facturación del código '.$clave.''."\n";
					}
					/*elseif($row['fecha']<date("Y-m").'-01' && $row['fecha']>=$mesanterior && date("d")>'07' && $row['localidad_id'] == 1){
						$resultado['error'] = 1;
						$resultado['mensaje'] = 'Vencio el limite de facturacion del mes anterior';
					}*/
					elseif($row['tipo_pago'] == 2 || $row['tipo_pago'] == 6){
						$resultado['error'] = 1;
						$resultado['mensaje'] = 'Error en el código '.$clave.', no se pueden facturar pagos a credito o con pago anticipado'."\n";
					}
					/*elseif(($row['plaza']==1 || $row['plaza']==15) && date('Y-m-d')<'2016-05-15'){
						$resultado['error'] = 1;
						$resultado['mensaje'] = 'La facturación de tickets de los centros CU 9006 y CU 9073 será apartir del lunes 15 de mayo por mantenimiento de información'."\n";	
					}*/
					else{
						$clavesencontradas[]=trim($clave);
						$resultado['generar_factura'] = 1;
						$resultado['html'] .=  '
						<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>--></td>
						<td align="left" style="padding: 10px!important">'.utf8_encode($row['nomplaza']).'</td>
						<td align="center" style="padding: 10px!important">'.$row['cve'].'</td>
						<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
						<td align="right" style="padding: 10px!important">'.$row['monto'].'';
						$resultado['html'] .=  '<input type="hidden" name="ticket[]" id="ticket_'.$row['plaza'].'_'.$row['cve'].'" plaza="'.$row['plaza'].'" class="tickets" value="'.$row['cve'].'">
						<input type="hidden" name="plaza[]" id="plaza_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['plaza'].'">
						<input type="hidden" name="placat[]" id="placat_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['placa'].'"></td></tr>';
					}
					$fecha = $row['fecha'];
				}
			}
			else{
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'El código '.$clave.' esta mal capturado'."\n";
			}
		}
	}	
	echo json_encode($resultado);
	exit();
}

if($_POST['ajax']==1.2){
	$resultado = array('error' => 0, 'html' => '', 'mensaje' => '', 'generar_factura' => 0);
	$limite30 = date( "Y-m-d" , strtotime ( "+ 30 day" , strtotime(date('Y-m-d')) ) );
	
	$fecha='';
	$res = mysql_query("SELECT plaza, cve as ticket FROM cobro_engomado WHERE placa = '".$_POST['placa']."' AND cve = '".$_POST['folio']."'");
	if($row=mysql_fetch_array($res)){
		
		$res=mysql_query("SELECT a.tipo_pago,a.tipo_venta,a.fecha,a.factura,a.estatus,a.cve,a.plaza,(a.monto+IFNULL(f.recuperacion,0)-IFNULL(e.devolucion,0)) as monto,a.placa,b.nombre as engomado,c.nombre as nomplaza, a.notacredito, d.localidad_id 
		FROM cobro_engomado a 
		LEFT JOIN engomados b ON b.cve = a.engomado 
		INNER JOIN plazas c ON c.cve = a.plaza 
		INNER JOIN datosempresas d ON c.cve = d.plaza
		LEFT JOIN 
			(SELECT ticket, SUM(devolucion) as devolucion FROM devolucion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') e ON a.cve = e.ticket
		LEFT JOIN 
			(SELECT ticket, SUM(recuperacion) as recuperacion FROM recuperacion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') f ON a.cve = f.ticket
		WHERE a.plaza = '".$row['plaza']."' AND a.cve='".$row['ticket']."'") or die(mysql_error());
		$row = mysql_fetch_array($res);
		if($row['estatus']=='C'){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'El ticket esta cancelado'."\n";
		}
		elseif($row['tipo_venta']==1){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'No se pueden facturar intentos'."\n";
		}
		elseif($row['tipo_venta']==2){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'No se pueden facturar cortesias'."\n";
		}
		elseif(date('Y') == 2016 && substr($row['fecha'],0,4) == 2015 && 1==2){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'No se pueden facturar tickets del 2015'."\n";	
		}
		elseif($row['factura']>0 && $row['notacredito']==0){
			$resultado['html'] .=  '
			<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>-->';
			$res1=mysql_query("SELECT * FROM facturas WHERE plaza='".$row['plaza']."' AND cve='".$row['factura']."'");
			$row1=mysql_fetch_array($res1);
			if($row1['respuesta1']==""){
				$resultado['html'] .= '<br><b>No Timbrada</b>';
			}
			else{
				$resultado['html'] .=  '&nbsp;&nbsp;<a href="#" onClick="atcr(\'facturacion_web.php\',\'_blank\',\'200\',\''.$row1['plaza'].'|'.$row1['cve'].'|1\');"><img src="images/zip_grande.png" border="0" width="30px" height="30px" title="Descargar"></a>';
				if($row1['estatus'] == 'D') $resultado['html'] .= '<br>DEVUELTA';
			}
			$resultado['html'] .=  '</td>
			<td align="left" style="padding: 10px!important">'.$row['nomplaza'].'</td>
			<td align="center" style="padding: 10px!important">(Facturado)<br>'.$row['cve'].'</td>
			<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
			<td align="right" style="padding: 10px!important">'.$row['monto'].'';
			$resultado['html'] .=  '</td></tr>';
			if($row['notacredito']>0){
				$resultado['html'] .=  '
				<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>-->';
				$res1=mysql_query("SELECT * FROM notascredito WHERE plaza='".$row['plaza']."' AND cve='".$row['factura']."'");
				$row1=mysql_fetch_array($res1);
				if($row1['respuesta1']==""){
					$resultado['html'] .= '<br><b>No Timbrada</b>';
				}
				else{
					$resultado['html'] .=  '&nbsp;&nbsp;<a href="#" onClick="atcr(\'facturacion_web.php\',\'_blank\',\'200\',\''.$row1['plaza'].'|'.$row1['cve'].'|2\');"><img src="images/zip_grande.png" border="0" width="25px" height="25px" title="Descargar"></a>';
				}
				$resultado['html'] .=  '</td>
				<td align="left" style="padding: 10px!important">'.$row['nomplaza'].'</td>
				<td align="center" style="padding: 10px!important">(Nota Credito)<br>'.$row['cve'].'</td>
				<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
				<td align="right" style="padding: 10px!important">'.$row['monto'].'';
				$resultado['html'] .=  '</td></tr>';
			}
		}
		elseif($row['fecha']<'2015-05-01'){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'Error, solo se pueden facturar por esta pagina ventas de mayo 2015 en adelante'."\n";
		}
		elseif($row['fecha']>$limite30 && $row['localidad_id'] == 2){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'Vencio el limite de facturación'."\n";
		}
		elseif($row['tipo_pago'] == 2 || $row['tipo_pago'] == 6){
			$resultado['error'] = 1;
			$resultado['mensaje'] = 'Error, no se pueden facturar pagos a credito o con pago anticipado'."\n";
		}
		/*elseif(($row['plaza']==1 || $row['plaza']==15) && date('Y-m-d')<'2016-05-15'){
			$resultado['error'] = 1;
			$resultado['mensaje'] = 'La facturación de tickets de los centros CU 9006 y CU 9073 será apartir del lunes 15 de mayo por mantenimiento de información'."\n";	
		}*/
		else{
			$clavesencontradas[]=trim($clave);
			$resultado['generar_factura'] = 1;
			$resultado['html'] .=  '
			<tr><td align="center"><!--<span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span>--></td>
			<td align="left" style="padding: 10px!important">'.utf8_encode($row['nomplaza']).'</td>
			<td align="center" style="padding: 10px!important">'.$row['cve'].'</td>
			<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
			<td align="right" style="padding: 10px!important">'.$row['monto'].'';
			$resultado['html'] .=  '<input type="hidden" name="ticket[]" id="ticket_'.$row['plaza'].'_'.$row['cve'].'" plaza="'.$row['plaza'].'" class="tickets" value="'.$row['cve'].'">
			<input type="hidden" name="plaza[]" id="plaza_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['plaza'].'">
			<input type="hidden" name="placat[]" id="placat_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['placa'].'"></td></tr>';
		}
		$fecha = $row['fecha'];
	}
	else{
		$resultado['error'] = 1;
		$resultado['mensaje'] .= 'Error en la relaci&oacute;n de la placa y folio de ticket'."\n";
	}
	echo json_encode($resultado);
	exit();
}

if($_POST['ajax']==1){
	$resultado = array('error' => 0, 'html' => '', 'htmlf' => '', 'mensaje' => '', 'generar_factura' => 0, 'plaza' => 0, 'factura' => 0);
	$limite30 = date( "Y-m-d" , strtotime ( "-30 day" , strtotime(date('Y-m-d')) ) );
	$limite1 = date( "Y-m-t" , strtotime ( "-1 month" , strtotime(date('Y-m').'-01') ) );
	$limite2 = date( "Y-m-t" , strtotime ( "-2 month" , strtotime(date('Y-m').'-01') ) );
	//$res = mysql_query("SELECT plaza, cve as ticket, tipo_pago FROM cobro_engomado WHERE placa = '".$_POST['placa']."' AND cve = '".$_POST['folio']."'");
	$res = mysql_query("SELECT a.plaza, a.cve as ticket, a.tipo_pago FROM cobro_engomado a INNER JOIN claves_facturacion b ON a.plaza = b.plaza AND a.cve = b.ticket WHERE a.placa = '".$_POST['placa']."' AND b.cve = '".$_POST['clavefacturacion']."'");
	if($row=mysql_fetch_array($res)){
		$tickets_capturados = json_decode($_POST['tickets_capturados'], true);
		$repetido = false;
		$diferente_pago = false;
		$diferentes_dias = false;
		foreach($tickets_capturados as $ticket_capturado)
		{
			if($ticket_capturado['plaza'] == $row['plaza'] && $ticket_capturado['cve'] == $row['ticket']){
				$repetido = true;
			}
			if(($row['fecha'] == date('Y-m-d') && $ticket_capturado['fecha'] < date('Y-m-d')) || ($row['fecha'] < date('Y-m-d') && $ticket_capturado['fecha'] == date('Y-m-d'))){
				$diferentes_dias = true;
			}
			if($ticket_capturado['tipo_pago'] != $row['tipo_pago']){
				$diferente_pago = true;
			}
		}
		if($repetido){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'El ticket ya esta en el listado'."\n";
		}
		elseif($diferentes_dias){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'Las fechas de los tickets deben de ser todos de hoy o todos de dias atrasados'."\n";
		}
		elseif($diferente_pago){
			$resultado['error'] = 1;
			$resultado['mensaje'] .= 'Los tipos de pago de los tickets debe de ser el mismo'."\n";
		}
		else{
			$res=mysql_query("SELECT a.tipo_pago,a.tipo_venta,a.fecha,a.factura,a.estatus,a.cve,a.plaza,(a.monto+IFNULL(f.recuperacion,0)-IFNULL(e.devolucion,0)) as monto,a.placa,b.nombre as engomado,c.nombre as nomplaza, a.notacredito, a.vale_pago_anticipado, d.localidad_id, c.bloqueada_sat 
			FROM cobro_engomado a 
			LEFT JOIN engomados b ON b.cve = a.engomado 
			INNER JOIN plazas c ON c.cve = a.plaza 
			INNER JOIN datosempresas d ON c.cve = d.plaza
			LEFT JOIN 
				(SELECT ticket, SUM(devolucion) as devolucion FROM devolucion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') e ON a.cve = e.ticket
			LEFT JOIN 
				(SELECT ticket, SUM(recuperacion) as recuperacion FROM recuperacion_certificado WHERE plaza = '".$row['plaza']."' AND estatus != 'C' AND ticket='".$row['ticket']."') f ON a.cve = f.ticket
			WHERE a.plaza = '".$row['plaza']."' AND a.cve='".$row['ticket']."'") or die(mysql_error());
			$row = mysql_fetch_array($res);
			$error_vale_externo = false;
			if($row['tipo_pago']==12 && $row['tipo_venta']==0){
				$res1 = mysql_query("SELECT factura FROM vales_externos WHERE plaza='".$row['plaza']."' AND folio_ini<='".$row['vale_pago_anticipado']."' AND folio_fin>='".$row['vale_pago_anticipado']."' AND estatus!='C'");
				$row1 = mysql_fetch_array($res1);
				if($row1['factura']>0){
					$error_vale_externo = true;
				}
			}
			if($error_vale_externo){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'La venta del vale externo ya fue facturada'."\n";
			}
			elseif($row['estatus']=='C'){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'El ticket esta cancelado'."\n";
			}
			elseif($row['estatus']=='D'){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'El ticket esta devuelto'."\n";
			}
			elseif($row['tipo_venta']==1){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'No se pueden facturar intentos'."\n";
			}
			elseif($row['tipo_venta']==2){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'No se pueden facturar cortesias'."\n";
			}
			elseif($row['bloqueada_sat']==1){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'No se pueden facturar tickets del centro '.utf8_encode($row['nomplaza'])."\n";
			}
			elseif(date('Y') == 2016 && substr($row['fecha'],0,4) == 2015 && 1==2){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'No se pueden facturar tickets del 2015'."\n";	
			}
			elseif($row['factura']>0 && $row['notacredito']==0){
				$resultado['error'] = 2;
				$resultado['plaza'] = $row['plaza'];
				$resultado['factura'] = $row['factura'];
			}
			elseif($row['fecha']<'2015-05-01'){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'Error, solo se pueden facturar por esta pagina ventas de mayo 2015 en adelante'."\n";
			}
			elseif(substr($row['fecha'],0,7)!=date('Y-m')){//if($row['fecha']<=$limite2 || ($row['fecha']<=$limite1 && date('d')>'07')){
				$resultado['error'] = 1;
				$resultado['mensaje'] .= 'Vencio el limite de facturación'."\n";
			}
			elseif($row['tipo_pago'] == 2){
				$resultado['error'] = 1;
				$resultado['mensaje'] = 'Error, no se pueden facturar pagos a credito'."\n";
			}
			else{
				$clavesencontradas[]=trim($clave);
				$resultado['generar_factura'] = 1;
				$resultado['html'] .=  '
				<tr><td align="center"><span style="cursor:pointer;" onClick="if(confirm(\'Esta seguro de quitar el ticket?\')) quitar_ticket($(this))"><img src="images/validono.gif"></span></td>
				<td align="left" style="padding: 10px!important">'.utf8_encode($row['nomplaza']).'</td>
				<td align="center" style="padding: 10px!important">'.$row['cve'].'</td>
				<td align="left" style="padding: 10px!important">'.$row['engomado'].'</td>
				<td align="right" style="padding: 10px!important">'.$row['monto'].'';
				$resultado['html'] .=  '<input type="hidden" name="ticket[]" id="ticket_'.$row['plaza'].'_'.$row['cve'].'" plaza="'.$row['plaza'].'" fecha="'.$row['fecha'].'" tipo_pago="'.$row['tipo_pago'].'" class="tickets" value="'.$row['cve'].'">
				<input type="hidden" name="plaza[]" id="plaza_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['plaza'].'">
				<input type="hidden" name="placat[]" id="placat_'.$row['plaza'].'_'.$row['cve'].'" value="'.$row['placa'].'"></td></tr>';
			}
			$fecha = $row['fecha'];
		}
	}
	else{
		$resultado['error'] = 1;
		$resultado['mensaje'] .= 'Error en la relación de la placa y folio de ticket'."\n";
	}
	echo json_encode($resultado);

	exit();
}

if($_POST['ajax']=='validarDatosSAT'){
	exit();
	require_once("nusoap/nusoap.php");
	$oSoapClient = new nusoap_client("https://urbanustienda.com/wscontribuyentes.php?wsdl", true);
	$err = $oSoapClient->getError();
	if($err!="")
	{
		echo '1'.str_replace("'","\'",$err);
	}
	else
	{
		$parametros=array ('rfc'=>$_POST['rfc'],'razonsocial'=>$_POST['nombre'],'codigopostal'=>$_POST['codigopostal'],'regimen'=>$_POST['regimensat'],'usocfdi'=>$_POST['uso_cfdi']);
		$respuesta = $oSoapClient->call("verificarContribuyente", $parametros);
		$err = $oSoapClient->getError();
		if($err!="")
		{
			echo '2'.str_replace("'","\'",$err);
		}
		else
		{
			//print_r($respuesta);
			if ($respuesta['resultado']!=1){
				echo $respuesta['mensaje'];
			}
		}
	}

	exit();
}

if($_POST['ajax']=='prefactura'){
	$datos = json_decode($_POST['datos'], true);
	$res = mysql_query("SELECT * FROM cat_rfc WHERE rfc = '".trim($datos['rfc'])."' AND estatus!=2");
	if($row = mysql_fetch_array($res)){
		echo '<h1>El sat nos pide validar su rfc.<br>
Nos puede mandar foto del ticket y datos fiscales por favor</h1>';
		echo '<p class="text-center"><input type="button" id="aceptar_generacion" class="btn btn-primary btn-lg" value="Volver" onClick="volver_editar()"></p><br /><br />		';
	}
	else{

		/*$res = mysql_query("SELECT COUNT(a.cve) FROM facturas a INNER JOIN clientes b ON a.cliente = b.cve WHERE estatus!='C' AND fecha=CURDATE() AND b.rfc = '".trim($datos['rfc'])."'");
		$row = mysql_fetch_array($res);
		if($row[0]>=10){
			mysql_query("INSERT cat_rfc SET rfc='".trim($datos['rfc'])."', nombre = '".addslashes($datos['nombre'])."'");
			echo '<h1>El sat nos pide validar su rfc.<br>
Nos puede mandar foto del ticket y datos fiscales por favor</h1>';
			echo '<p class="text-center"><input type="button" id="aceptar_generacion" class="btn btn-primary btn-lg" value="Volver" onClick="volver_editar()"></p><br /><br />		';
			require_once("phpmailer/class.phpmailer.php");
			$mail = new PHPMailer();
			$mail->Host = "localhost";
			$mail->From = "gverificentros@gverificentros.com";
			$mail->FromName = "GVerificentros";
			$mail->Subject = "RFC que quiso hacer mas de 10 facturas";
			$mail->Body = "RFC: ".$datos['rfc'];
			$mail->AddAddress('hgaribay@gmail.com');
			$mail->Send();
			exit();
		}*/

		$plaza = $datos['plaza'][0];
		$tickets = implode(',',$datos['ticket']);
		$res = mysql_query("SELECT * FROM cobro_engomado WHERE plaza='".$plaza."' AND cve IN (".$tickets.") AND estatus!='C' AND (factura=0 OR notacredito>0)");
		$row=mysql_fetch_array($res);
		$forma_pago = 0;
		/*if($row['fecha'] < date('Y-m-d')){ 
			$tipo_pago = 4;
			$forma_pago = 1;
		}
		else*/if($row['tipo_pago']==5) $tipo_pago=7;
		elseif($row['tipo_pago']==7) $tipo_pago=9;
		elseif($row['tipo_pago']==6) $tipo_pago=$datos['tipo_pago'];
		else $tipo_pago=0;
		$cuentapago='NO APLICA';
		if($tipo_pago == 2 || $tipo_pago == 5){
			$cuentapago = $datos['cuenta'];
		}
		$res1 = mysql_query("SELECT * FROM plazas WHERE cve='".$plaza."'");
		$row1 = mysql_fetch_array($res1);
		$numeroPlaza=$row1['numero'];
		$res1 = mysql_query("SELECT * FROM datosempresas WHERE plaza='".$plaza."'");
		$row1 = mysql_fetch_array($res1);
		echo '<div style="margin: 20px;background-image: url(\'images/IMAGENPREVIA.jpg\');">';
		echo '<table width="100%"><tr><td width="30%"><img src="logos/logo1.jpg" width="300px" height="100px"></td>
			<td width="40%">'.utf8_encode($row1['nombre']).'<br>
			'.utf8_encode($row1['rfc'].' '.$row1['calle'].' '.$row1['numexterior'].' '.$row1['numinterior']).'<br>
			'.utf8_encode($row1['colonia']).',<br>
			'.utf8_encode($row1['localidad'].' '.$row1['codigopostal']).'<br>
			'.utf8_encode($row1['municipio']).' '.utf8_encode($row1['estado']).'<br>
			MEXICO</td><td width="30%">&nbsp;</td></tr></table>';
		echo '<br><br>';
		echo '<table width="100%"><tr><th width="50%">R E C E P T O R</th><td width="50%"></tr>
		<tr><td valign="top">
			<table>
			<tr><th align="left">Cliente:</th><td colspan=3>'.($datos['nombre']).'</td></tr>
			<tr><th align="left">R.F.C.:</th><td colspan=3>'.($datos['rfc']).'</td></tr>
			<tr style="display:none;"><th align="left">Domicilio:</th><td colspan=3>'.($datos['calle'].' No. '.$datos['numexterior'].' '.$datos['numinterior']).'</td></tr>
			<tr style="display:none;"><th align="left">Colonia:</th><td colspan=3>'.($datos['colonia']).'</td></tr>
			<!--<tr><th align="left">Localidad:</th><td colspan=3>'.($datos['localidad']).'</td></tr>-->
			<tr style="display:none;"><th align="left">Municipio:</th><td colspan=3>'.($datos['municipio']).'</td></tr>
			<tr style="display:none;"><th align="left">Estado:</th><td colspan=3>'.($datos['estado']).'</td></tr>
			<tr style="display:none;"><th align="left">C.P.:</th><td>'.($datos['codigopostal']).'</td><th>PAIS:</th><td>MEXICO</td></tr>
			</table></td><td valign="top">
			<table>
			<tr><th align="left">REGIMEN</th><td>'.utf8_encode($row1['regimen']).'</td></tr>
			<tr><th align="left" colspan="2">LUGAR DE EXPEDICI&Oacute;N</th></tr>
			<tr><td copslan="2">'.utf8_encode($row1['calle'].' '.$row1['numexterior'].' '.$row1['numinterior']).'<br>
			'.utf8_encode($row1['colonia']).',<br>
			'.utf8_encode($row1['localidad'].', '.$row1['codigopostal'].', '.$row1['municipio'].', '.$row1['estado']).', MEXICO</td></tr>
			</table></td></tr></table>';
		echo '<br><br>';
		echo '<table width="100%" border="1">
		<tr><th>Cantidad</th><th>Unidad</th><th>Concepto/Descripci&oacute;n</th><th>Valor Unit</th><th>Importe</th></tr>';
		$res=mysql_query("SELECT a.cve, (a.monto+IFNULL(f.recuperacion,0)-IFNULL(e.devolucion,0)) as monto, a.fecha, a.placa, a.copias FROM cobro_engomado a 
			LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' 
			LEFT JOIN 
				(SELECT ticket, SUM(devolucion) as devolucion FROM devolucion_certificado WHERE plaza = '".$plaza."' AND estatus != 'C' AND ticket IN (".$tickets.") GROUP BY ticket) e ON a.cve = e.ticket
			LEFT JOIN 
				(SELECT ticket, SUM(recuperacion) as recuperacion FROM recuperacion_certificado WHERE plaza = '".$plaza."' AND estatus != 'C' AND ticket IN (".$tickets.") GROUP BY ticket) f ON a.cve = f.ticket
			WHERE a.plaza='".$plaza."' AND a.cve IN (".$tickets.") AND a.estatus!='C' AND (a.factura=0 OR a.notacredito>0) GROUP BY a.cve");
		$copias = 0;
		while($row=mysql_fetch_array($res)){
			/*if(substr($row['fecha'],0,7) < date("Y-m")){
				$array_detalles[] = array(
					'cant' => 1,
					'monto' => $row['monto'],
					'descripcion' => 'VERIFICACION DEL MES DE '.strtoupper($array_meses[intval(substr($row['fecha'],5,2))]).' '.$row['fecha'].', '.$row['placa'].', '.$row['cve']
				);
				$observaciones = 'FACTURADO EN EL MES DE '.strtoupper($array_meses[intval(date('m'))]).' A SOLICITUD DEL CLIENTE POR NO FACTURAR EN EL MES QUE VERIFICO';
			}
			else{
				$array_detalles[$row['engomado']]['cant']+=1;
				$array_detalles[$row['engomado']]['monto']+=$row['monto'];
				$observaciones = '';
			}*/
			$array_detalles[]=array(
				'engomado' => $row['engomado'],
				'cant' => 1,
				'monto' => $row['monto'],
				'placa' => $row['placa']
			);
			//$copias += $row['copias'];
			$ventas .= ','.$row['cve'];
		}
		$iva=0;
		$subtotal=0;
		$total=0;
		foreach($array_detalles as $k=>$v){
			$importe_iva=round($v['monto']*16/116,2);
			$total+=round($v['monto'],2);
			$subtotal+=round($v['monto']-$importe_iva,2);
			$iva+=$importe_iva;
			$concepto = 'VERIFICACION VEHICULAR PLACA: '.$v['placa'];
			if($v['descripcion'] != '') $concepto = $v['descripcion'];
			echo '<tr><td align="right">'.$v['cant'].'</td>
			<td align="center">No Aplica</td>
			<td align="left">'.$concepto.'</td>
			<td align="right">'.number_format(($v['monto']-$importe_iva)/$v['cant'],2).'</td>
			<td align="right">'.number_format($v['monto']-$importe_iva,2).'</td>
			</tr>';
		}
		if($copias>0){
			$monto = $copias;
			$importe_iva=round($monto*16/116,2);
			$total+=round($monto,2);
			$subtotal+=round($monto-$importe_iva,2);
			$iva+=$importe_iva;
			$concepto = 'COPIAS';
			echo '<tr><td align="right">'.$copias.'</td>
			<td align="center">Pieza</td>
			<td align="left">'.$concepto.'</td>
			<td align="right">'.number_format(($monto-$importe_iva)/$copias,2).'</td>
			<td align="right">'.number_format($monto-$importe_iva,2).'</td>
			</tr>';
		}
		echo '<tr><th colspan="3">IMPORTE CON LETRA</th><td colspan="2" rowspan="2" align="center">
		<table width="80%" border="1">
		<tr><td>SUBTOTAL:</td><td align="right">'.number_format($subtotal,2).'</td></tr>
		<tr><td>I.V.A. 16%:</td><td align="right">'.number_format($iva,2).'</td></tr>';
		
		echo '
		<tr><td>TOTAL:</td><td align="right">'.number_format($total,2).'</td></tr>
		</table></td></tr>';
		require_once('numlet.php');
		echo '<tr><td colspan="3" align="left">'.utf8_encode(numlet($total)).'<br><br>
		M&eacute;todo pago: '.$array_forma_pago[$forma_pago].'
		Forma de pago: '.$array_tipo_pago[$tipo_pago].'<br>
		Condiciones: CONTADO<br>
		No. Cta pago: '.$cuentapago.'<br></td></tr></table><br />';
		echo '<p class="text-center"><input type="button" id="aceptar_generacion" class="btn btn-success btn-lg" value="Generar" onClick="confirmar_generacion()">
		&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" id="aceptar_generacion" class="btn btn-primary btn-lg" value="Editar" onClick="volver_editar()"></p><br /><br />		';
	}
	exit();
}

top();

//echo '<input type="hidden" name="fechahoracarga" id="fechahoracarga" value="'.date('Y-m-d H:i:s').'">';
//echo '<input type="hidden" name="idcliente" id="idcliente" value="'.$_POST['idcliente'].'">';
include("imp_factura.php");

if($_POST['cmd']==3){
	$datos=explode('|',$_POST['reg']);
	require_once("phpmailer/class.phpmailer.php");
	$resfactura=mysql_query("SELECT * FROM facturas WHERE plaza='".$datos[0]."' AND cve='".$datos[1]."' AND estatus NOT IN ('C','D')");
	if($Factura = mysql_fetch_array($resFactura)){
		$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='".$datos[0]."'");
		$rowplaza = mysql_fetch_array($resplaza);
		$resempresa = mysql_query("SELECT * FROM datosempresas WHERE plaza='".$datos[0]."'");
		$rowempresa = mysql_fetch_array($resempresa);
		if($rowempresa['check_sucursal']==1){
			$datossucursal=",check_sucursal='".$Factura['check_sucursal']."',nombre_sucursal='".addslashes($Factura['nombre_sucursal'])."',
			calle_sucursal='".addslashes($Factura['calle_sucursal'])."',numero_sucursal='".$Factura['numero_sucursal']."',
			colonia_sucursal='".addslashes($Factura['colonia_sucursal'])."',rfc_sucursal='".$Factura['rfc_sucursal']."',
			localidad_sucursal='".addslashes($Factura['localidad_sucursal'])."',municipio_sucursal='".addslashes($Factura['municipio_sucursal'])."',
			estado_sucursal='".addslashes($Factura['estado_sucursal'])."',cp_sucursal='".$Factura['cp_sucursal']."'";
		}
		$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='".$datos[0]."' AND tipo=0 AND tipodocumento=2");
		$row = mysql_fetch_array($res);
		$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM notascredito WHERE plaza='".$datos[0]."' AND serie='".$row['serie']."'");
		$row1 = mysql_fetch_array($res1);
		if($row['folio_inicial']<$row1[0]){
			$row['folio_inicial'] = $row1[0];
		}
		$insert = "INSERT notascredito SET plaza='".$datos[0]."',serie='".$row['serie']."',folio='".$row['folio_inicial']."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$Factura['obs']."',
		cliente='".$Factura['cliente']."',tipo_pago='".$Factura['tipo_pago']."',forma_pago='".$Factura['forma_pago']."',usuario='-1',baniva_retenido='".$Factura['baniva_retenido']."',banisr_retenido='".$Factura['banisr_retenido']."',
		carta_porte='".$Factura['carta_porte']."',load_cliente='".$Factura['load']."',nombre_cliente='".$Factura['nombre_cliente']."',direccion_cliente='".$Factura['direccion_cliente']."',
		tipopago_cliente='".$Factura['tipopago_cliente']."',banco_cliente='".$Factura['banco_cliente']."',cuenta_cliente='".$Factura['cuenta_cliente']."',tipo_factura='".$Factura['tipo_factura']."',
		factura='".$Factura['cve']."',engomado='".$Factura['engomado']."',banco='".$Factura['banco']."',cuenta_cheque='".$Factura['cuenta_cheque']."',tiene_descuento='".$Factura['tiene_descuento']."'".$datossucursal;
		while(!$resinsert=mysql_query($insert)){
			$row['folio_inicial']++;
			$insert = "INSERT notascredito SET plaza='".$datos[0]."',serie='".$row['serie']."',folio='".$row['folio_inicial']."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$Factura['obs']."',
			cliente='".$Factura['cliente']."',tipo_pago='".$Factura['tipo_pago']."',forma_pago='".$Factura['forma_pago']."',usuario='-1',baniva_retenido='".$Factura['baniva_retenido']."',banisr_retenido='".$Factura['banisr_retenido']."',
			carta_porte='".$Factura['carta_porte']."',load_cliente='".$Factura['load']."',nombre_cliente='".$Factura['nombre_cliente']."',direccion_cliente='".$Factura['direccion_cliente']."',
			tipopago_cliente='".$Factura['tipopago_cliente']."',banco_cliente='".$Factura['banco_cliente']."',cuenta_cliente='".$Factura['cuenta_cliente']."',tipo_factura='".$Factura['tipo_factura']."',
			factura='".$Factura['cve']."',engomado='".$Factura['engomado']."',banco='".$Factura['banco']."',cuenta_cheque='".$Factura['cuenta_cheque']."',tiene_descuento='".$Factura['tiene_descuento']."'".$datossucursal;
		}
		
		$cvefact=mysql_insert_id();
		$documento=array();
		require_once("nusoap/nusoap.php");
		$fserie=$row['serie'];
		$ffolio=$row['folio_inicial'];
		//Generamos la Factura
		$documento['serie']=$row['serie'];
		$documento['folio']=$row['folio_inicial'];
		$documento['fecha']=fechaLocal().' '.horaLocal();
		$documento['formapago']=$array_forma_pago[$Factura['forma_pago']];
		$documento['idtipodocumento']=2;
		$documento['observaciones']='';//$Factura['obs'];
		$documento['metodopago']=$array_tipo_pagosat[$Factura['tipo_pago']];
		$res = mysql_query("SELECT * FROM clientes WHERE cve='".$Factura['cliente']."'");
		$row = mysql_fetch_array($res);
		$emailenvio = $row['email'];
		$row['cve']=0;
		$documento['receptor']['codigo']=$row['cve'];
		$documento['receptor']['rfc']=$row['rfc'];
		$documento['receptor']['nombre']=$row['nombre'];
		$documento['receptor']['calle']=$row['calle'];
		$documento['receptor']['num_ext']=$row['numexterior'];
		$documento['receptor']['num_int']=$row['numinterior'];
		$documento['receptor']['colonia']=$row['colonia'];
		$documento['receptor']['localidad']=$row['localidad'];
		$documento['receptor']['municipio']=$row['municipio'];
		$documento['receptor']['estado']=$row['estado'];
		$documento['receptor']['pais']='MEXICO';
		$documento['receptor']['codigopostal']=$row['codigopostal'];
		//Agregamos los conceptos
		$i=0;
		$resD=mysql_query("SELECT * FROM facturasmov WHERE plaza='".$Factura['plaza']."' AND cvefact='".$Factura['cve']."'");
		while($rowD=mysql_fetch_array($resD)){
			if(trim($rowD['unidad'])=="") $rowD['unidad'] = "Unidad de servicio";
			mysql_query("INSERT notascreditomov SET plaza='".$rowD['plaza']."',cvefact='$cvefact',cantidad='".$rowD['cantidad']."',concepto='".$rowD['concepto']."',
			precio='".$rowD['precio'][$k]."',descuento='".$rowD['descuento']."',importe='".$rowD['importe']."',iva='".$rowD['iva']."',
			importe_iva='".$rowD['importe_iva']."',unidad='".$rowD['unidad']."',
			engomado='".$rowD['engomado']."'");
			$documento['conceptos'][$i]['cantidad']=$rowD['cantidad'];
			$documento['conceptos'][$i]['unidad']=$rowD['unidad'];
			$documento['conceptos'][$i]['descripcion']=$rowD['concepto'];
			$documento['conceptos'][$i]['valorUnitario']=$rowD['precio'];
			$documento['conceptos'][$i]['importe']=$rowD['importe'];
			$documento['conceptos'][$i]['importe_iva']=$rowD['importe_iva'];
			$i++;
		}
		mysql_query("UPDATE notascredito SET subtotal='".$Factura['subtotal']."',iva='".$Factura['iva']."',total='".$Factura['total']."',
		isr_retenido='".$Factura['isr_retenido']."',por_isr_retenido='".$Factura['por_isr_retenido']."',
		iva_retenido='".$Factura['iva_retenido']."',por_iva_retenido='".$Factura['por_iva_retenido']."' WHERE plaza='".$Factura['plaza']."' AND cve=".$cvefact);
		mysql_query("UPDATE cobro_engomado SET notacredito = '$cvefact' WHERE plaza='".$Factura['plaza']."' AND factura='".$Factura['cve']."' AND factura>0");
		mysql_query("UPDATE facturas SET estatus = 'D' WHERE plaza='".$Factura['plaza']."' AND cve='".$Factura['cve']."'");
		mysql_query("UPDATE venta_engomado_factura SET notacredito = '$cvefact' WHERE plaza='".$Factura['plaza']."' AND factura='".$Factura['cve']."'");
		$documento['subtotal']=$Factura['subtotal'];
		$documento['descuento']=0;
		//Traslados
		#IVA
		if($Factura['iva']>0){
			$documento['tasaivatrasladado']=16;
			$documento['ivatrasladado']=$Factura['iva'];  //Solo 200 grava iva
		}
		if($Factura['iva_retenido'] > 0){
			$documento['ivaretenido']=$Factura['iva_retenido'];  
		}
		if($Factura['isr_retenido'] > 0){
			$documento['isrretenido']=$Factura['isr_retenido'];  
		}
		//total
		$documento['total']=$Factura['total'];
		//Moneda
		$documento['moneda']     = 1; //1=pesos, 2=Dolar, 3=Euro
		$documento['tipocambio'] = 1;
		
		//print_r($documento);
		$oSoapClient = new nusoap_client("http://compuredes.mx/webservices/wscfdi2012.php?wsdl", true);			
		$err = $oSoapClient->getError();
		if($err!="")
			echo "error1:".$err;
		else{
			//print_r($documento);
			$oSoapClient->timeout = 300;
			$oSoapClient->response_timeout = 300;
			$respuesta = $oSoapClient->call("generar", array ('id' => $rowempresa['idplaza'],'rfcemisor' => $rowempresa['rfc'],'idcertificado' => $rowempresa['idcertificado'],'documento' => $documento, 'usuario' => $rowempresa['usuario'],'password' => $rowempresa['pass']));
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
						mysql_query("UPDATE notascredito SET respuesta1='".$respuesta['uuid']."',seriecertificado='".$respuesta['seriecertificado']."',
						sellodocumento='".$respuesta['sellodocumento']."',uuid='".$respuesta['uuid']."',seriecertificadosat='".$respuesta['seriecertificadosat']."',
						sellotimbre='".$respuesta['sellotimbre']."',cadenaoriginal='".$respuesta['cadenaoriginal']."',
						fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
						WHERE plaza='".$Factura['plaza']."' AND cve=".$cvefact);
						//Tomar la informacion de Retorno
						$dir="cfdi/comprobantes/";
						//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
						//el zip siempre se deja fuera
						$dir2="cfdi/";
						//Leer el Archivo Zip
						$fileresult=$respuesta['archivos'];
						$strzipresponse=base64_decode($fileresult);
						$filename='cfdinc_'.$Factura['plaza'].'_'.$cvefact;
						file_put_contents($dir2.$filename.'.zip', $strzipresponse);
						$zip = new ZipArchive;
						if ($zip->open($dir2.$filename.'.zip') === TRUE){
							$strxml=$zip->getFromName('xml.xml');
							file_put_contents($dir.$filename.'.xml', $strxml);
							//$strpdf=$zip->getFromName('formato.pdf');
							//file_put_contents($dir.$filename.'.pdf', $strpdf);
							$zip->close();		
							generaFacturaPdf($Factura['plaza'],$cvefact,0,2);
							if($emailenvio!=""){
								$mail = obtener_mail();
								$mail->FromName = "Verificentros Plaza ".$array_plaza[$Factura['plaza']];
								$mail->Subject = "Nota de Credito ".$fserie." ".$ffolio;
								$mail->Body = "Nota de Credito ".$fserie." ".$ffolio;
								//$mail->AddAddress(trim($emailenvio));
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								if($rowempresa['email']!=""){
									$correos = explode(",",trim($rowempresa['email']));
									foreach($correos as $correo)
										$mail->AddCC(trim($correo));
								}
								$mail->AddAttachment("cfdi/comprobantes/nc_".$Factura['plaza']."_".$cvefact.".pdf", "Nota de Credito ".$fserie." ".$ffolio.".pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdinc_".$Factura['plaza']."_".$cvefact.".xml", "Nota de Credito ".$fserie." ".$ffolio.".xml");
								$mail->Send();
							}	
							if($rowempresa['email']!=""){
								$mail = obtener_mail();
								$mail->FromName = "Verificentros Plaza ".$array_plaza[$Factura['plaza']];
								$mail->Subject = "Nota de Credito ".$fserie." ".$ffolio;
								$mail->Body = "Nota de Credito ".$fserie." ".$ffolio;
								//$mail->AddAddress(trim($rowempresa['email']));
								$correos = explode(",",trim($rowempresa['email']));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/nc_".$Factura['plaza']."_".$cvefact.".pdf", "Nota de Credito ".$fserie." ".$ffolio.".pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdinc_".$Factura['plaza']."_".$cvefact.".xml", "Nota de Credito ".$fserie." ".$ffolio.".xml");
								$mail->Send();
							}	
						}
						else 
							$strmsg='Error al descomprimir el archivo';
						if(file_exists($dir2.$filename.'.zip')){
							unlink($dir2.$filename.'.zip');
						}
						echo '<h1>Se genero el folio de nota de credito '.$fserie." ".$ffolio.'</h1>';
					}
					else
						$strmsg=$respuesta['mensaje'];
					//print_r($respuesta);	
					echo $strmsg;
				}
			}
		}
	}
	else{
		echo '<h1>Error en la factura</h1>';
	}
	$_POST['cmd'] = 0;
}



if($_POST['cmd']==2){
  $res = mysql_query("SELECT cve FROM usuarios WHERE cve=1 AND cerrar_portal=1");
  if(!$row=mysql_fetch_array($res)){
	foreach($_POST as $k=>$v){
		if($k!='plaza' && $k!='placat' && $k!='ticket'){
			$_POST[$k] = mb_strtoupper(($v));
		}
	}
	$plaza = $_POST['plaza'][0];
	$tickets = implode(',',$_POST['ticket']);
	$res = mysql_query("SELECT * FROM cobro_engomado WHERE plaza='".$plaza."' AND cve IN (".$tickets.") AND estatus!='C' AND (factura=0 OR notacredito>0)");
	if($row=mysql_fetch_array($res)){
		$forma_pago = 0;
		/*if($row['fecha'] < date('Y-m-d')){ 
			$tipo_pago = 4;
			$forma_pago = 1;
		}
		else*/if($row['tipo_pago']==5) $tipo_pago=7;
		elseif($row['tipo_pago']==7) $tipo_pago=9;
		elseif($row['tipo_pago']==6) $tipo_pago=$_POST['tipo_pago'];
		else $tipo_pago=1;
		//if($_POST['idcliente'] == 98393)
		//{
		$resC = mysql_query("SELECT * FROM clientes WHERE plaza = '".$plaza."' AND rfc='".$_POST['rfc']."'");
		if(!$rowC=mysql_fetch_array($resC)){
			mysql_query("INSERT clientes SET plaza='".$plaza."',fechayhora=NOW(),usuario='-1',
								rfc='".$_POST['rfc']."',nombre='".addslashes($_POST['nombre'])."',email='".$_POST['email']."',calle='".addslashes($_POST['calle'])."',
								numexterior='".addslashes($_POST['numexterior'])."',numinterior='".addslashes($_POST['numinterior'])."',colonia='".addslashes($_POST['colonia'])."',
								municipio='".addslashes($_POST['municipio'])."',estado='".addslashes($_POST['estado'])."',codigopostal='".$_POST['codigopostal']."',
								usocfdi='G03', regimensat='{$_POST['regimensat']}'");
		
			$cliente_id = mysql_insert_id();
			if($_POST['cuenta'] != ''){
				mysql_query("INSERT clientes_cuentas SET cliente = '$cliente_id',cuenta='".$_POST['cuenta']."'");
			}
		}
		else{
			$cliente_id = $rowC['cve'];
			mysql_query("UPDATE clientes SET nombre='".addslashes($_POST['nombre'])."',email='".$_POST['email']."',codigopostal='".$_POST['codigopostal']."',usocfdi='G03', regimensat='{$_POST['regimensat']}' WHERE cve='{$cliente_id}'");
		}
		/*}
		else{
			mysql_query("UPDATE clientes SET plaza='".$plaza."' WHERE cve='".$_POST['idcliente']."'");
			$cliente_id = $_POST['idcliente'];
		}*/
		$resempresa = mysql_query("SELECT * FROM datosempresas WHERE plaza='".$plaza."'");
		$rowempresa = mysql_fetch_array($resempresa);
		$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='".$plaza."'");
		$rowplaza = mysql_fetch_array($resplaza);
		$datossucursal='';
		if($rowempresa['check_sucursal']==1){
			$datossucursal=",check_sucursal='".$rowempresa['check_sucursal']."',nombre_sucursal='".addslashes($rowempresa['nombre_sucursal'])."',
			calle_sucursal='".addslashes($rowempresa['calle_sucursal'])."',numero_sucursal='".$rowempresa['numero_sucursal']."',
			colonia_sucursal='".addslashes($rowempresa['colonia_sucursal'])."',rfc_sucursal='".$rowempresa['rfc_sucursal']."',
			localidad_sucursal='".addslashes($rowempresa['localidad_sucursal'])."',municipio_sucursal='".addslashes($rowempresa['municipio_sucursal'])."',
			estado_sucursal='".addslashes($rowempresa['estado_sucursal'])."',cp_sucursal='".$rowempresa['cp_sucursal']."'";
		}
		//require_once("phpmailer/class.phpmailer.php");
		//require_once('PHPMailer-master/PHPMailerAutoload.php');
	
		$array_detalles = array();
		$ventas = '';
		$res=mysql_query("SELECT a.cve, (a.monto+IFNULL(f.recuperacion,0)-IFNULL(e.devolucion,0)) as monto, a.fecha, a.placa, a.copias FROM cobro_engomado a 
			LEFT JOIN certificados b ON a.plaza = b.plaza AND a.cve = b.ticket AND b.estatus!='C' 
			LEFT JOIN 
				(SELECT ticket, SUM(devolucion) as devolucion FROM devolucion_certificado WHERE plaza = '".$plaza."' AND estatus != 'C' AND ticket IN (".$tickets.") GROUP BY ticket) e ON a.cve = e.ticket
			LEFT JOIN 
				(SELECT ticket, SUM(recuperacion) as recuperacion FROM recuperacion_certificado WHERE plaza = '".$plaza."' AND estatus != 'C' AND ticket IN (".$tickets.") GROUP BY ticket) f ON a.cve = f.ticket
			WHERE a.plaza='".$plaza."' AND a.cve IN (".$tickets.") AND a.estatus!='C' AND (a.factura=0 OR a.notacredito>0) GROUP BY a.cve");
		$copias=0;
		while($row=mysql_fetch_array($res)){
			/*if(substr($row['fecha'],0,7) < date("Y-m")){
				$array_detalles[] = array(
					'cant' => 1,
					'monto' => $row['monto'],
					'descripcion' => 'VERIFICACION DEL MES DE '.strtoupper($array_meses[intval(substr($row['fecha'],5,2))]).' '.$row['fecha'].', '.$row['placa'].', '.$row['cve']
				);
				$observaciones = 'FACTURADO EN EL MES DE '.strtoupper($array_meses[intval(date('m'))]).' A SOLICITUD DEL CLIENTE POR NO FACTURAR EN EL MES QUE VERIFICO';
			}
			else{
				$array_detalles[$row['engomado']]['cant']+=1;
				$array_detalles[$row['engomado']]['monto']+=$row['monto'];
				$observaciones = '';
			}*/
			$array_detalles[] = array(
				'engomado' => $row['engomado'],
				'cant' => 1,
				'monto' => $row['monto'],
				'placa' => $row['placa']
			);
			//$copias+=$row['copias'];
			$ventas .= ','.$row['cve'];
		}
		if(count($array_detalles)>0){
			$ip = getRealIP();
			$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='".$plaza."' AND tipo=0 AND tipodocumento=1");
			$row = mysql_fetch_array($res);
			$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM facturas WHERE plaza='".$plaza."' AND serie='".$row['serie']."'");
			$row1 = mysql_fetch_array($res1);
			if($row['folio_inicial']<$row1[0]){
				$row['folio_inicial'] = $row1[0];
			}
			$insert = "INSERT facturas SET plaza='".$plaza."',serie='".$row['serie']."',folio='".$row['folio_inicial']."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$observaciones."',ip='$ip',forma_pago='$forma_pago',
			cliente='".$cliente_id."',tipo_pago='$tipo_pago',usuario='-1',baniva_retenido='".$_POST['baniva_retenido']."',banisr_retenido='".$_POST['banisr_retenido']."',
			carta_porte='".$_POST['carta_porte']."',load_cliente='".$_POST['load']."',nombre_cliente='".$_POST['nombre_cliente']."',direccion_cliente='".$_POST['direccion_cliente']."',
			tipopago_cliente='".$_POST['tipopago_cliente']."',banco_cliente='".$_POST['banco_cliente']."',cuenta_cliente='".$_POST['cuenta_cliente']."',tipo_factura='".$_POST['tipo_factura']."', cuenta_cli='".$_POST['cuenta']."'".$datossucursal;
			while(!$resinsert=mysql_query($insert)){
				$row['folio_inicial']++;
				$insert = "INSERT facturas SET plaza='".$plaza."',serie='".$row['serie']."',folio='".$row['folio_inicial']."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$observaciones."',ip='$ip',forma_pago='$forma_pago',
				cliente='".$cliente_id."',tipo_pago='$tipo_pago',usuario='-1',baniva_retenido='".$_POST['baniva_retenido']."',banisr_retenido='".$_POST['banisr_retenido']."',
				carta_porte='".$_POST['carta_porte']."',load_cliente='".$_POST['load']."',nombre_cliente='".$_POST['nombre_cliente']."',direccion_cliente='".$_POST['direccion_cliente']."',
				tipopago_cliente='".$_POST['tipopago_cliente']."',banco_cliente='".$_POST['banco_cliente']."',cuenta_cliente='".$_POST['cuenta_cliente']."',tipo_factura='".$_POST['tipo_factura']."', cuenta_cli='".$_POST['cuenta']."'".$datossucursal;
			}
			/*$res1 = mysql_query("SELECT cve FROM facturas WHERE plaza='".$plaza."'");
			if(mysql_num_rows($res1) > 0){
				mysql_query("INSERT facturas SET plaza='".$plaza."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$_POST['obs']."',ip='$ip',
				cliente='".$cliente_id."',tipo_pago='0',usuario='-1',baniva_retenido='".$_POST['baniva_retenido']."',banisr_retenido='".$_POST['banisr_retenido']."',
				carta_porte='".$_POST['carta_porte']."',load_cliente='".$_POST['load']."',nombre_cliente='".$_POST['nombre_cliente']."',direccion_cliente='".$_POST['direccion_cliente']."',
				tipopago_cliente='".$_POST['tipopago_cliente']."',banco_cliente='".$_POST['banco_cliente']."',cuenta_cliente='".$_POST['cuenta_cliente']."',tipo_factura='".$_POST['tipo_factura']."'".$datossucursal) or die(mysql_error());
			}
			else{
				mysql_query("INSERT facturas SET plaza='".$plaza."',cve='".$row['folio_inicial']."',fecha='".fechaLocal()."',fecha_creacion='".fechaLocal()."',hora='".horaLocal()."',obs='".$_POST['obs']."',ip='$ip',
				cliente='".$cliente_id."',tipo_pago='0',usuario='-1',baniva_retenido='".$_POST['baniva_retenido']."',banisr_retenido='".$_POST['banisr_retenido']."',
				carta_porte='".$_POST['carta_porte']."',load_cliente='".$_POST['load']."',nombre_cliente='".$_POST['nombre_cliente']."',direccion_cliente='".$_POST['direccion_cliente']."',
				tipopago_cliente='".$_POST['tipopago_cliente']."',banco_cliente='".$_POST['banco_cliente']."',cuenta_cliente='".$_POST['cuenta_cliente']."',tipo_factura='".$_POST['tipo_factura']."'".$datossucursal) or die(mysql_error());
			}*/
			$cvefact=mysql_insert_id();
	
			$documento=array();
			require_once("nusoap/nusoap.php");
			$fserie=$row['serie'];
			$ffolio=$row['folio_inicial'];
			//Generamos la Factura
			$documento['serie']=$row['serie'];
			$documento['folio']=$row['folio_inicial'];
			$documento['fecha']=fechaLocal().' '.horaLocal();
			$documento['formapago']='PAGO EN UNA SOLA EXHIBICION';
			$documento['idtipodocumento']=1;
			$documento['observaciones']=$observaciones;//$_POST['obs'];
			$documento['metodopago']=$array_tipo_pagosat[$tipo_pago];
			$res = mysql_query("SELECT * FROM clientes WHERE cve='".$cliente_id."'");
			$row = mysql_fetch_array($res);
			if($_POST['tipo_pago']==2 || $_POST['tipo_pago']==5)
			$res2=mysql_query("SELECT * FROM clientes_cuentas WHERE cliente='".$row['cve']."' AND cuenta!=''");
			if($row2=mysql_fetch_array($res2))
				$documento['numerocuentapago']=$row2['cuenta'];
			$emailenvio = $row['email'];
			$row['cve']=0;
			$documento['receptor']['codigo']=$row['cve'];
			$documento['receptor']['rfc']=$row['rfc'];
			$documento['receptor']['nombre']=$row['nombre'];
			$documento['receptor']['calle']=$row['calle'];
			$documento['receptor']['num_ext']=$row['numexterior'];
			$documento['receptor']['num_int']=$row['numinterior'];
			$documento['receptor']['colonia']=$row['colonia'];
			$documento['receptor']['localidad']=$row['localidad'];
			$documento['receptor']['municipio']=$row['municipio'];
			$documento['receptor']['estado']=$row['estado'];
			$documento['receptor']['pais']='MEXICO';
			$documento['receptor']['codigopostal']=$row['codigopostal'];
		
			//Agregamos los conceptos
			$i=0;
			$iva=0;
			$subtotal=0;
			$total=0;
			foreach($array_detalles as $k=>$v){
				$importe_iva=round($v['monto']*16/116,2);
				$total+=round($v['monto'],2);
				$subtotal+=round($v['monto']-$importe_iva,2);
				$iva+=$importe_iva;
				$concepto = 'VERIFICACION VEHICULAR PLACA: '.$v['placa'];
				if($v['descripcion'] != '') $concepto = $v['descripcion'];
				mysql_query("INSERT facturasmov SET plaza='".$plaza."',cvefact='$cvefact',cantidad='".$v['cant']."',
				concepto='".$concepto."',claveunidadsat='E48',claveprodsat='77121503',
				precio='".round(round($v['monto']-$importe_iva,2)/$v['cant'],2)."',importe='".round($v['monto']-$importe_iva,2)."',
				iva='16',importe_iva='$importe_iva',unidad='Unidad de servicio'");
				$i++;
			}
			if($copias>0){
				$monto = $copias;
				$importe_iva=round($monto*16/116,2);
				$total+=round($monto,2);
				$subtotal+=round($monto-$importe_iva,2);
				$iva+=$importe_iva;
				$concepto = 'COPIAS';
				mysql_query("INSERT facturasmov SET plaza='".$plaza."',cvefact='$cvefact',cantidad='".$copias."',
				concepto='".$concepto."',claveunidadsat='H87',claveprodsat='80141600',
				precio='".round(round($monto-$importe_iva,2)/$copias,2)."',importe='".round($monto-$importe_iva,2)."',
				iva='16',importe_iva='$importe_iva',unidad='Pieza'");
				$i++;
			}
			mysql_query("UPDATE facturas SET subtotal='".$subtotal."',iva='".$iva."',total='".$total."',
			isr_retenido='".$_POST['isr_retenido']."',por_isr_retenido='".$_POST['por_isr_retenido']."',
			iva_retenido='".$_POST['iva_retenido']."',por_iva_retenido='".$_POST['por_iva_retenido']."' 
			WHERE plaza='".$plaza."' AND cve=".$cvefact);
			$documento['subtotal']=$subtotal;
			$documento['descuento']=0;

			mysql_query("UPDATE cobro_engomado SET factura='".$cvefact."',documento=1,notacredito=0 WHERE plaza='".$plaza."' AND cve IN (".substr($ventas,1).")");
			//Traslados
			#IVA
			if($iva>0){
				$documento['tasaivatrasladado']=16;
				$documento['ivatrasladado']=$iva;  //Solo 200 grava iva
			}
			if($_POST['iva_retenido'] > 0){
				$documento['ivaretenido']=$_POST['iva_retenido'];  
			}
			if($_POST['isr_retenido'] > 0){
				$documento['isrretenido']=$_POST['isr_retenido'];  
			}
			//total
			$documento['total']=$total;
			//Moneda
			$documento['moneda']     = 1; //1=pesos, 2=Dolar, 3=Euro
			$documento['tipocambio'] = 1;
			$documento = genera_arreglo_facturacion($plaza, $cvefact, 'I');
			mysql_query("INSERT INTO venta_engomado_factura (plaza,venta,factura) SELECT ".$plaza.",cve,factura FROM cobro_engomado WHERE plaza='".$plaza."' AND factura='".$cvefact."'");
			//print_r($documento);

			$resultadotimbres = validar_timbres($plaza);
			if($resultadotimbres['seguir']){
			if(trim($_POST['rfc'])!='RAGS660522NT3'){
				$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);	
				$err = $oSoapClient->getError();
				if($err!="")
					echo "error1:".$err;
				else{
					//print_r($documento);
					$oSoapClient->timeout = 300;
					$oSoapClient->response_timeout = 300;
					$respuesta = $oSoapClient->call("generarComprobante", array ('id' => $rowempresa['idplaza'],'rfcemisor' => $rowempresa['rfc'],'idcertificado' => $rowempresa['idcertificado'],'documento' => $documento, 'usuario' => $rowempresa['usuario'],'password' => $rowempresa['pass']));
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
								mysql_query("UPDATE facturas SET respuesta1='".$respuesta['uuid']."',seriecertificado='".$respuesta['seriecertificado']."',
								sellodocumento='".$respuesta['sellodocumento']."',uuid='".$respuesta['uuid']."',seriecertificadosat='".$respuesta['seriecertificadosat']."',
								sellotimbre='".$respuesta['sellotimbre']."',cadenaoriginal='".$respuesta['cadenaoriginal']."',
								fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
								WHERE plaza='".$plaza."' AND cve=".$cvefact);
								mysql_query("UPDATE facturas SET rfc_cli='".$_POST['rfc']."', nombre_cli='".addslashes($_POST['nombre'])."', calle_cli='".addslashes($_POST['calle'])."', numext_cli='".addslashes($_POST['numexterior'])."', numint_cli = '".addslashes($_POST['numinterior'])."', colonia_cli = '".addslashes($_POST['colonia'])."', localidad_cli = '".addslashes($_POST['localidad'])."', municipio_cli = '".addslashes($_POST['municipio'])."',
										estado_cli='".addslashes($_POST['estado'])."', cp_cli='".$_POST['codigopostal']."'
									WHERE plaza='".$plaza."' AND cve=".$cvefact);
								//Tomar la informacion de Retorno
								$dir="cfdi/comprobantes/";
								//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
								//el zip siempre se deja fuera
								$dir2="cfdi/";
								//Leer el Archivo Zip
								$fileresult=$respuesta['archivos'];
								$strzipresponse=base64_decode($fileresult);
								$filename='cfdi_'.$plaza.'_'.$cvefact;
								file_put_contents($dir2.$filename.'.zip', $strzipresponse);
								$zip = new ZipArchive;
								if ($zip->open($dir2.$filename.'.zip') === TRUE){
									$strxml=$zip->getFromName('xml.xml');
									file_put_contents($dir.$filename.'.xml', $strxml);
									//$strpdf=$zip->getFromName('formato.pdf');
									//file_put_contents($dir.$filename.'.pdf', $strpdf);
									$zip->close();		
									generaFacturaPdf($plaza,$cvefact);
									if($emailenvio!=""){
										$mail = obtener_mail();
										$mail->FromName = "Verificentros Plaza ".$array_plaza[$plaza];
										$mail->Subject = "Factura ".$fserie." ".$ffolio;
										$mail->Body = "Factura ".$fserie." ".$ffolio;
										//$mail->AddAddress(trim($emailenvio));
										$correos = explode(",",trim($emailenvio));
										foreach($correos as $correo)
											$mail->AddAddress(trim($correo));
										$mail->AddAttachment("cfdi/comprobantes/factura_".$plaza."_".$cvefact.".pdf", "Factura ".$fserie." ".$ffolio.".pdf");
										$mail->AddAttachment("cfdi/comprobantes/cfdi_".$plaza."_".$cvefact.".xml", "Factura ".$fserie." ".$ffolio.".xml");
										$mail->Send();
									}	
									if($rowempresa['email']!=""){
										$mail = obtener_mail();
										$mail->FromName = "Verificentros Plaza ".$array_plaza[$plaza];
										$mail->Subject = "Factura ".$fserie." ".$ffolio;
										$mail->Body = "Factura ".$fserie." ".$ffolio;
										//$mail->AddAddress(trim($rowempresa['email']));
										$correos = explode(",",trim($rowempresa['email']));
										foreach($correos as $correo)
											$mail->AddAddress(trim($correo));
										$mail->AddAttachment("cfdi/comprobantes/factura_".$plaza."_".$cvefact.".pdf", "Factura ".$fserie." ".$ffolio.".pdf");
										$mail->AddAttachment("cfdi/comprobantes/cfdi_".$plaza."_".$cvefact.".xml", "Factura ".$fserie." ".$ffolio.".xml");
										$mail->Send();
									}	
									@unlink("cfdi/comprobantes/factura_".$plaza."_".$cvefact.".pdf");
								}
								else 
									$strmsg='Error al descomprimir el archivo';
								if(file_exists($dir2.$filename.'.zip')){
									unlink($dir2.$filename.'.zip');
								}

								/*$body = array(
							    'rfc' => $_POST['rfc'],
							    'rs' => $_POST['nombre'],
							    'cp' => $_POST['codigopostal']
								);
								$url = 'https://api.2ai.io/cfdi/rfc/edit';

								$ch = curl_init($url);

								//$payload = http_build_query($body);
								$payload = json_encode($body);

								curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

								curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

								curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization:c9af4c5ade781ce2e1abd4ab79612d21','Content-type: application/json'));

								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

								$resultado = curl_exec($ch);
								//$resultado = json_decode($resultado, true);
								curl_close($ch);*/
									
								echo '<h1>Se genero el folio de factura '.$fserie." ".$ffolio.'  <a href="#" onClick="atcr(\'facturacion_web.php\',\'_blank\',\'200\',\''.$plaza.'|'.$cvefact.'|1\');"><img src="images/zip_grande.png" border="0" width="45px" height="45px" title="Descargar">Descarga aqui su factura</a></h1>';
							}
							else
								$strmsg=$respuesta['mensaje'];
							//print_r($respuesta);	
							echo $strmsg;
						}
					}
				}
			}
			}
		}
	}
  }
	$_POST['cmd']=0;
}

if($_POST['cmd']==10){
?>
<link href="assets/lightbox2/dist/css/lightbox.min.css" rel="stylesheet">
<script src="assets/lightbox2/dist/js/lightbox.min.js"></script>

<div class="container">
<section class="col-sm-9">
<br><br><br>
<div class="form-group" align="center">
	<div class="col-sm-12">
		<div class="row">
			<div class="col-sm-4">
				<!--<a href="images/ticket_clavefacturacion_highres.jpg" data-lightbox="Ticket" data-title="Ticket">
				<img src="images/ticket_clavefacturacion_highres.jpg" class="img-responsive" alt="Responsive image">
				</a>-->&nbsp;
			</div>
			<div class="col-sm-6">
				<p class="text-center"><input type="button" id="volver" class="btn btn-info" value="Regresar" onClick="atcr('facturacion_web.php','',0,0);"></p><br />
				<label for="codigofactura">RFC</label>
				<br>
				<div class="input-group">
					<input type="text" size="20" class="form-control input-sm" id="rfc" name="rfc" placeholder="Escriba el RFC">									
				</div>
				<br>
				<p class="text-center"><input type="button" id="traerfacturas" class="btn btn-info" value="Buscar" onClick="traerFacturas()"></p><br />
				<span id="Resultados"><span id="divespera"></span><table border="1"  id="tresultados" style="display:none;">
				<tr><th align="center" style="padding: 10px!important">&nbsp;</th>
				<th align="center" style="padding: 20px!important">Centro de Verificaci&oacute;n</th>
				<th align="center" style="padding: 10px!important">Folio</th>
				<th align="center" style="padding: 10px!important">Fecha</th>
				<th align="center" style="padding: 10px!important">Importe</th></tr></table></span>
			</div>
		</div>
	</div>
	
</div>
</section>
<section class="col-sm-3" style="margin-top: 2em;">
				<div class="alert alert-info text-center" role="alert"><b><span class="glyphicon glyphicon-question-sign" aria-hidden="true" style="font-size: 100px"></span><br>¿Problemas con su factura?</b></div>
				<!--<p><b>Soporte para facturación:</b><br><span class="text-info">Soporte Morelos: verimorelos@gmail.com</span><p>
				<p>Enviar en Asunto folio de ticket, placa y número y/o nombre del centro de verificación</p>-->
				<div class="popup" onclick="myFunction()" role="alert">
				<span class="popuptext" id="myPopup">manda  una foto de tu ticket,datos fiscales y si gustas teléfono  para poderte apoyar soporte@facturacionweb.net</span>
				</div>
				<p><h2 style="font-size:17px">manda  una foto de tu ticket,datos fiscales y si gustas teléfono  para poderte apoyar soporte@facturacionweb.net</h2></p>
				<p><h2>Se recomienda el uso de Google Chrome <img src="images/chrome.png" width="40px" height="40px"> o Mozilla Firefox<img src="images/firefox.png" width="40px" height="40px"></h2></p>
				<p style="font-size:12px"><a  target="_blank" href="https://miverificacion.com/facturacion/Aviso/Aviso_De_Privacidad.pdf">*Aviso de Privacidad</a></p>
			
</section>
</div>
<script>
	function traerFacturas(){
        if($.trim(document.forma.rfc.value)==""){
            alert("Necesita ingresar el RFC");
        }
        else{
        	$.ajax({
              url: "facturacion_web.php",
              type: "POST",
              async: false,
              dataType: "json",
              data: {
                rfc: $.trim(document.forma.rfc.value),
                ajax: 10,
                recargado: 1
              },
                beforeSend: function(){
                	$('.cfacturas').remove();
                    console.log("Do something....");
                    $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
                },
                success: function(data) {   
                	$("#divespera").html('');
                	$("#tresultados").show();
	                $("#tresultados").hide().append(data.html).fadeIn("slow");
                	//atcr('facturacion_web.php','_blank','200',''+data.plaza+'|'+data.factura+'|1');
                }
            });
        }
    }
</script>
<?php
}

if($_POST['cmd'] == 0){
?>
<link href="assets/lightbox2/dist/css/lightbox.min.css" rel="stylesheet">
<script src="assets/lightbox2/dist/js/lightbox.min.js"></script>
<div id="modalmensaje40" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel"><h1>Importante</h1></h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body">
				<h1><font color="RED">Estimado usuario, es importante que conozcas los nuevos cambios que contempla la version 4.0.<br>El cambio mas relevante ahora es que la razón social es la que viene especificada en tu cédula sin tipos de sociedad, tu régimen en el cual tributas y tu código postal registrado ante el SAT. No poner SA de CV</font></h1>
			</div>
			<div class="modal-footer">
		        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="container">
<section class="col-sm-9">
<br><br><br>
<div class="form-group" align="center">
	<div class="col-sm-12">
		<div class="row">
			<div class="col-sm-4">
				<!--<a href="images/ticket_clavefacturacion_highres.jpg" data-lightbox="Ticket" data-title="Ticket">
				<img src="images/ticket_clavefacturacion_highres.jpg" class="img-responsive" alt="Responsive image">
				</a>-->&nbsp;
			</div>
			<div class="col-sm-6">
				<p class="text-center"><input type="button" id="btnvalidarfc" class="btn btn-warning" value="Valida tus Datos Fiscales" onClick="atcr('https://www.sat.gob.mx/aplicacion/operacion/79615/valida-en-linea-rfc%C2%B4s-uno-a-uno-o-de-manera-masiva-hasta-5-mil-registros','_blank',0,0);"></p><br />
				
				<p class="text-center"><input type="button" id="descargarfacturas" class="btn btn-info" value="Descargar Facturas" onClick="atcr('facturacion_web.php','',10,0);"></p><br />
				<!--<label for="codigofactura">Clave de Facturación <br />(12 dígitos que se encuentran dentro de su ticket)</label>
				<br><br>
				<div class="input-group">
					<textarea class="form-control input-sm" id="codigofactura" name="codigofactura" placeholder="Escribir clave de facturación" cols="70" rows="7"></textarea>									
				</div>-->
				<label for="codigofactura">Placa</label>
				<br>
				<div class="input-group">
					<input type="text" size="20" class="form-control input-sm" id="placa" name="placa" placeholder="Escriba la Placa">									
				</div>
				<!--<br>
				<label for="codigofactura">Folio de Ticket</label>
				<br>
				<div class="input-group">
					<input type="text" size="20" class="form-control input-sm" id="folio" name="folio" placeholder="Escriba el folio">									
				</div>-->
				<br>
				<label for="codigofactura">Clave Facturaci&oacute;n</label>
				<br>
				<div class="input-group">
					<input type="text" size="20" class="form-control input-sm" id="clavefacturacion" name="clavefacturacion" placeholder="Clave de facturaci&oacute;n">									
				</div>
				<br>
				<!-- class="btn btn-default btn-sm"-->
				<button  class="btn btn-primary" style='width:100px; height:25px'  type="button" onClick="buscarcodigo()">Buscar</button></br>
				<!--<span class="text-muted"><small>Escribir los C&oacute;digos de Facturaci&oacute;n que desee facturar <br> m&aacute;ximo 7 por factura (1 codigo por linea).</font></small></span>-->
				<!--<span class="text-muted"><small>Escribir la placa y el folio del ticket<br>NO el c&oacute;digo de facturaci&oacute;n<br>NI el c&oacute;digo de barras.</font></small></span>-->
				</br><span class="text-muted"><small>Escribir la placa y la clave de facturaci&oacute;n<br>No el c&oacute;digo de barras.</font></small></span>
				</br></br>
				<span id="Resultados"><span id="divespera"></span><table border="1"  id="tresultados" style="display:none;">
				<tr><th align="center" style="padding: 10px!important">&nbsp;</th>
				<th align="center" style="padding: 10px!important">Centro de Verificaci&oacute;n</th>
				<th align="center" style="padding: 10px!important">Folio</th>
				<th align="center" style="padding: 10px!important">Tipo de Verificaci&oacute;n</th>
				<th align="center" style="padding: 10px!important">Importe</th></tr></table></span>
    			<!--<p class="text-center"><input type="button" id="continuar" class="btn btn-primary" value="Continuar" onClick="$('#panel').show();validar('');" disabled></p><br />-->
			</div>
		</div>
	</div>
	
</div>
<textarea style="display:none;" name="htmlcodigos" id="htmlcodigos"></textarea>
<input type="hidden" name="mostrar_campos" id="mostrar_campos" value="0">
<br><br><br>
</section>
<section class="col-sm-3" style="margin-top: 2em;">
				<div class="alert alert-info text-center" role="alert"><b><span class="glyphicon glyphicon-question-sign" aria-hidden="true" style="font-size: 100px"></span><br>¿Problemas con su factura?</b></div>
				<!--<p><b>Soporte para facturación:</b><br><span class="text-info">Soporte Morelos: verimorelos@gmail.com</span><p>
				<p>Enviar en Asunto folio de ticket, placa y número y/o nombre del centro de verificación</p>-->
				<div class="popup" onclick="myFunction()" role="alert">
				<span class="popuptext" id="myPopup">manda  una foto de tu ticket,datos fiscales y si gustas teléfono  para poderte apoyar soporte@facturacionweb.net</span>
				</div>
				<!---<p><h2 style="font-size:17px">+manda  una foto de tu ticket,datos fiscales y si gustas teléfono  para poderte apoyar soporte@facturacionweb.net</h2></p>-->
				<p><h2 style="font-size:17px">Si tiene problemas al realizar su factura, solicite apoyo en el centro donde fue emitido su Ticket</h2></p>
				<p><h2>Se recomienda el uso de Google Chrome <img src="images/chrome.png" width="40px" height="40px"> o Mozilla Firefox<img src="images/firefox.png" width="40px" height="40px"></h2></p>
				<p style="font-size:12px"><a  target="_blank" href="https://miverificacion.com/facturacion/Aviso/Aviso_De_Privacidad.pdf">*Aviso de Privacidad</a></p>
			
</section>
</div>
<script>
function myFunction() {
    var popup = document.getElementById("myPopup");
    popup.classList.toggle("show");
}
	function validar(){
		if(hayplazasdiferentes()){
            $('#panel').hide();
            alert("Los tickets deben de pertenecer al mismo centro de verificación");
        }
        else if(haytipospagosdiferentes()){
            $('#panel').hide();
            alert("Los tickets deben de ser pagados con el mismo tipo de pago");
        }
        else{
        	$('#tresultados').find('span').remove();
        	html_tickets = '';
        	$('.tickets').each(function(){
        		html_tickets += '<tr>'+$(this).parents('tr:first').html()+'</tr>';
        	});
            $("#htmlcodigos").val(html_tickets);
    		$("#mostrar_campos").val(1);
            atcr("facturacion_web.php","",1,0);
        }
	}

	function buscarcodigo(){
        if($.trim(document.forma.placa.value)==""){
            alert("Necesita ingresar la placa");
        }
        /*else if($.trim(document.forma.folio.value)==""){
            alert("Necesita ingresar el folio del ticket");
        }*/
        else{
        	tickets_capturados = [];
        	$('.tickets').each(function(){
        		campo_ticket = $(this);
        		ticket = {};
        		ticket.plaza = campo_ticket.attr('plaza');
        		ticket.cve = campo_ticket.val();
        		ticket.fecha = campo_ticket.attr('fecha');
        		ticket.tipo_pago = campo_ticket.attr('tipo_pago');
        		tickets_capturados.push(ticket);
        	});
            $.ajax({
              url: "facturacion_web.php",
              type: "POST",
              async: false,
              dataType: "json",
              data: {
                placa: $.trim(document.forma.placa.value),
                //folio: $.trim(document.forma.folio.value),
                clavefacturacion: $.trim(document.forma.clavefacturacion.value),
                ajax: 1,
                tickets_capturados: JSON.stringify(tickets_capturados),
                recargado: 1
              },
                beforeSend: function(){
                    console.log("Do something....");
                    $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
                },
                success: function(data) {   
                	$("#divespera").html('');
                	if(data.error == 1){
                		alert(data.mensaje);
                	}   
                	else if(data.error == 2){
                		if(confirm("Ticket ya facturado desea descargar la factura?")){
                			atcr('facturacion_web.php','_blank','200',''+data.plaza+'|'+data.factura+'|1');
                		}
                		document.getElementById("folio").value = '';
	                    document.getElementById("placa").value = '';
                	}   
                	else{
                		$("#tresultados").show();
	                    $("#tresultados").hide().append(data.html).fadeIn("slow");
	                    if($(".tickets").length==0){
	                    	$("#continuar").attr("disabled","disabled");
	                    }
	                    else{
	                    	validar('');
	                    	$("#continuar").removeAttr("disabled");
	                    }
	                    document.getElementById("folio").value = '';
	                    document.getElementById("placa").value = '';
	                }
                }
            });
        }
    }

    function quitar_ticket(campo){
    	campo.parents('tr:first').remove();
    	if($(".tickets").length==0){
    		$("#tresultados").hide();
        	$("#continuar").attr("disabled","disabled");
        }
        else{
        	$("#continuar").removeAttr("disabled");
        }
    }

    function hayplazasdiferentes(){
    	plaza = 0;
    	regresar = false;
    	$('.tickets').each(function(){
    		plaza_ticket = $(this).attr('plaza');
    		if(plaza == 0){
    			plaza = plaza_ticket
    		}
    		if(plaza != plaza_ticket){
    			regresar = true;
    		}
    	});
    	return regresar;
    }

    function haytipospagosdiferentes(){
    	tipopago = 0;
    	regresar = false;
    	$('.tickets').each(function(){
    		tipopago_ticket = $(this).attr('tipopago');
    		if(tipopago == 0){
    			tipopago = tipopago_ticket
    		}
    		if(tipopago != tipopago_ticket){
    			regresar = true;
    		}
    	});
    	return regresar;
    }
    
	function buscarcodigoR2(){
        if($.trim(document.forma.placa.value)==""){
            alert("Necesita ingresar la placa");
        }
        else if($.trim(document.forma.folio.value)==""){
            alert("Necesita ingresar el folio del ticket");
        }
        else{
        	$("#panel").show();
            $.ajax({
              url: "facturacion_web.php",
              type: "POST",
              async: false,
              dataType: "json",
              data: {
                ajax: 1,
                placa: $.trim(document.forma.placa.value),
                folio: $.trim(document.forma.folio.value),
                recargado: 1
              },
                beforeSend: function(){
                    console.log("Do something....");
                    $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
                },
                success: function(data) {   
                	$("#divespera").html('');
                	if(data.error == 1){
                		$("#panel").hide();
                		alert(data.mensaje);
                	}   
                	else{
                		$("#htmlcodigos").val(data.html);
                		$("#mostrar_campos").val(data.generar_factura)
	                    atcr("facturacion_web.php","",1,0);
	                }
                }
            });
        }
    }

	function buscarcodigoR(){
        if($.trim(document.forma.codigofactura.value)==""){
            alert("Necesita ingresar el código de facturación");
        }
        else{
        	$("#panel").show();
        	codigos = document.forma.codigofactura.value.split("\n");
        	tickets_capturados = [];
        	for(i=0;i<codigos.length;i++){
        		if($.trim(codigos[i]) != ""){
	        		campo_ticket = $(this);
	        		ticket = {};
	        		ticket.codigo = codigos[i];
	        		tickets_capturados.push(ticket);
	        	}
        	};
            $.ajax({
              url: "facturacion_web.php",
              type: "POST",
              async: false,
              dataType: "json",
              data: {
                ajax: 1,
                tickets_capturados: JSON.stringify(tickets_capturados),
                recargado: 1
              },
                beforeSend: function(){
                    console.log("Do something....");
                    $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
                },
                success: function(data) {   
                	$("#divespera").html('');
                	if(data.error == 1){
                		$("#panel").hide();
                		alert(data.mensaje);
                	}   
                	else{
                		$("#htmlcodigos").val(data.html);
                		$("#mostrar_campos").val(data.generar_factura)
	                    atcr("facturacion_web.php","",1,0);
	                }
                }
            });
        }
    }

    $("#placa").on("keydown", function(e){
		return e.which !== 32;
	});

	$("#folio").on("keydown", function(e){
		return e.which !== 32;
	});

	$("#modalmensaje40").modal({
		backdrop: false,
		keyboard: false,
		show: true
	});

	$('#modalmensaje40').modal('show');
</script>
<?php
}
elseif($_POST['cmd']<10){
?>
<div id="modalConstancia" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel"><h1>IDCIF</h1></h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body">
				<!--<b>Favor de subir su constancia de situaci&iacute;n fiscal del SAT para validar datos</b><br>-->
				<img src="https://vvvpuebla.com/img/idCIF.jpg"><br>
				<div class="row">
					<div class="form-group">
						<label for="idcif" class="col-sm-2 control-label">IdCIF</label>
						<div class="col-sm-6">
							<input type="text" class="form-control input-sm" id="idcif" name="idcif" value="">
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" onClick="subirconstancia()">Aceptar</button>
		        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<script>
	$("#modalConstancia").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<div id="dialog_confirmacion" style="display:none;">
	<h1><font color="RED">¿Los datos son correctos?. No habrá cancelaciones</font></h1>
</div>
<div id="prefactura" style="display:none;">

</div>
<div id="dialog" style="display:none;"><h1>El horario de facturaci&oacute;n es de 7:00 a 23:30 horas.</h1></div>
<div class="container" id="divdatosfactura">
<section class="col-sm-9">
    <h1 class="text-center">Elabora tu factura</h1>
    <!--<p class="text-center">Capture la información solicitada y finalice dando clic al botón<b>"Generar factura"</b></br>
	Campos marcados con <span class="label label-danger">borde rojo</span> son obligatorios</p>-->
	
	<div class="row">
		<!-- col izq -->
		<?php
		if($_POST['mostrar_campos'] == 1){
			/*if($_POST['idcliente'] != 98393){
				$res = mysql_query("SELECT * FROM clientes WHERE cve='".$_POST['idcliente']."'");
				$row = mysql_fetch_array($res);
				$res1=mysql_query("SELECT * FROM clientes_cuentas WHERE cliente='".$row['cve']."' AND cuenta!=''");
				$row1=mysql_fetch_array($res1);
				$readOnly = ' readOnly';
			}
			else{*/
				$row = array();
				$row1 = array();
				$readOnly = '';
			//}
		?>
		<div class="col-sm-10">
			<div class="form-group">
				<label for="rfc" class="col-sm-2 control-label">RFC</label>
				<div class="col-sm-4">
					<input type="hidden" id="rfc" name="rfc" value="">
					<input type="text" class="required mayusculas form-control input-sm" onpaste="return false;" id="brfc" name="brfc" maxlength="13" onKeyUp="this.value=this.value.toUpperCase();" value="<?php echo $row['rfc'];?>" <?php echo $readOnly; ?>>
					<br><span class="text-muted"><small>El RFC es sin espacios ni guiones, con homoclave</small></span>
				</div>
				<div class="col-sm-2">
					<p>
					<button type="button" class="btn btn-primary" style="height:30px!important" id="btnbuscar" onClick="buscarRfc()">Buscar</button></p>
				</div>
				<div class="col-sm-2" style="display:none;">
					<button type="button" class="btn btn-primary" style="height:30px!important" id="btnlimpiar" onClick="limpiardatosfiscales()">Limpiar</button>
				</div>
				<div class="col-sm-2" style="display:none;">
					<button type="button" class="btn btn-primary" style="height:30px!important" id="btnactualizar" onClick="actualizardatosfiscales()">Actualizar</button>
				</div>
			</div>
			<div class="form-group">
				<label for="nombre" class="col-sm-2 control-label">Raz&oacute;n Social</label>
				<div class="col-sm-10">
					<input type="text" class="required mayusculas form-control input-sm" id="nombre" name="nombre" onKeyUp="this.value=this.value.toUpperCase();" onpaste="return false;" value="<?php echo $row['nombre'];?>" readOnly>
					<br><span class="text-muted"><small>La raz&oacute;n social debe de ser igual a la de su c&eacute;dula fiscal, sin el r&eacute;gimen capital. Si tus datos estan incorrectos, da click en actualizar.</small></span>
				</div>
			</div>

			<div class="form-group">
				<label for="codigopostal" class="col-sm-2 control-label">Código Postal</label>
				<div class="col-sm-3">
					<input type="text" class="required mayusculas form-control input-sm" id="codigopostal" name="codigopostal" onKeyUp="this.value=this.value.toUpperCase();" onpaste="return false;" value="<?php echo $row['codigopostal'];?>" readOnly>
				</div>
			</div>
			<div class="form-group">
				<label for="idcif" class="col-sm-2 control-label">IdCIF</label>
				<div class="col-sm-3">
					<input type="text" class="required mayusculas form-control input-sm" id="idcifp" name="idcifp" onKeyUp="this.value=this.value.toUpperCase();" onpaste="return false;" value="<?php echo $row['idcif'];?>" readOnly>
				</div>
			</div>

			<div class="form-group">
				<label for="nombre" class="col-sm-2 control-label">R&eacutegimen Fiscal</label>
				<div class="col-sm-7"><select name="regimensat" id="regimensat" class="required form-control input_sm"><option value="">Seleccione</option>
					<?php
					$res1 = mysql_query("SELECT * FROM regimen_sat ORDER BY nombre");
					while($row1 = mysql_fetch_assoc($res1)){
						echo '<option value="'.$row1['clave'].'">'.utf8_encode($row1['nombre']).'</option>';
					}
					?>
				</select></div>
			</div>
			
			<div class="form-group">
				<label for="email" class="col-sm-2 control-label">Correo electrónico</label>
				<div class="col-sm-7">
					<input type="text" class="required form-control input-sm" id="email" name="email" value="<?php echo $row['email'];?>" <?php echo $readOnly; ?>>
					<br><span class="text-muted"><small>Si desea entrar mas de un email, solo separelos por comas</small></span>			
				</div>
			</div>
			
			<div class="form-group">
				<label for="confirmacionemail" class="col-sm-2 control-label">Confirmación correo electrónico</label>
				<div class="col-sm-7">
					<input type="text" class="required form-control input-sm" id="confirmacionemail" name="confirmacionemail" value="<?php echo $row['email'];?>" <?php echo $readOnly; ?>>
					<br><span class="text-muted"><small>En caso de no encontrar el correo en su bandeja de entrada buscarlo en correo no deseado(spam)</small></span>
				</div>
			</div>
			
		</div>
		
		

		<?php
		}
		?>
		
		<!-- columna codigo facturacion -->
		<div class="col-sm-12">
			<div style="display:none;" class="form-group">
				<label for="codigofactura" class="col-sm-4 control-label">Código de Facturación</label>
				<div class="col-sm-12">
					<div class="row">
						<div class="col-sm-6">
							<div class="input-group">
								<input type="text" class="form-control input-sm" id="codigofactura" name="codigofactura" placeholder="Buscar por cÃ³digo de facturaciÃ³n" onKeyUp="this.value=this.value.toUpperCase();">
								<span class="input-group-btn">
								  <button class="btn btn-default btn-sm" type="button" onClick="buscarcodigo()">Buscar</button>
								</span>
							</div>					
						</div>
						<div class="col-sm-6">
							<div class="alert alert-warning text-center" role="alert"><span class="glyphicon glyphicon-hand-left pull-left" aria-hidden="true" style="font-size: 50px"></span><b>Ya puedes bajar tu factura desde este sitio con tus datos de placa y código de facturación!</b></div>					
						</div>
					</div>

					<span class="text-muted"><small>Buscar el código de facturación para traer los datos del ticket y asi activar el boton para generar factura. Ya puedes hacer tu factura con varios tickets del mismo centro de verificaci&oacute;n. <font color="RED">Para descargar tu factura buscar la factura con los datos de placa y c&oacute;digo de facturaci&oacute;n del ticket.</font></small></span>
				</div>
				
			</div>
			
			<span id="Resultados"><span id="divespera"></span><table border="1"  id="tresultados">
			<tr><th align="center" style="padding: 10px!important">Archivos</th>
			<th align="center" style="padding: 10px!important">Centro de Verificaci&oacute;n</th>
			<th align="center" style="padding: 10px!important">Folio</th>
			<th align="center" style="padding: 10px!important">Tipo de Verificaci&oacute;n</th>
			<th align="center" style="padding: 10px!important">Importe</th></tr><?php echo $_POST['htmlcodigos']; ?></table></span>
			<p class="text-center"><input type="button" id="generar_factura" class="btn btn-primary" value="Generar factura" onClick="$('#panel').show();if(validarRFC()){validar('');} else{ $('#panel').hide(); alert('RFC invalido');}" disabled></p><br /><br />		
		</div>				
	</div>
	
</section>
<section class="col-sm-3" style="margin-top: 2em;">
				<div class="alert alert-info text-center" role="alert"><b><span class="glyphicon glyphicon-question-sign" aria-hidden="true" style="font-size: 100px"></span><br>¿Problemas con su factura?</b></div>
				<!--<p><b>Soporte para facturación:</b><br><span class="text-info">Soporte Morelos: verimorelos@gmail.com</span><p>
				<p>Enviar en Asunto folio de ticket, placa y número y/o nombre del centro de verificación</p>-->
				<p><h2>Se recomienda el uso de Google Chrome <img src="images/chrome.png" width="40px" height="40px"> o Mozilla Firefox<img src="images/firefox.png" width="40px" height="40px"></h2></p>
			
</section>

</div><!-- end container -->
<script>
	$("#dialog_confirmacion").dialog({ 
		bgiframe: true,
		autoOpen: false,
		modal: true,
		width: 600,
		height: 200,
		autoResize: true,
		buttons: {
			"Aceptar": function(){ 
				$(this).dialog("close"); 
				$('#panel').show();
				atcr("facturacion_web.php","",2,0);
			},
			"Cancelar": function(){ 
				$('#panel').hide();
				$(this).dialog("close"); 
			}
		},
	}); 

	function limpiardatosfiscales(){
		$('#rfc').val('');
		$('#brfc').val('');
		$('#idcifp').val('');
		$('#brfc').removeAttr('readOnly');
		$('#nombre').val('');
		$('#codigopostal').val('');
        $('#btnbuscar').parents('div:first').show();
		$('#btnlimpiar').parents('div:first').hide();
		$('#btnactualizar').parents('div:first').hide();
	}

	function actualizardatosfiscales(){
		document.forma.idcif.value="";
		$('#modalConstancia').modal('show');
	}

	function buscarRfc(){
		$.ajax({
          url: "facturacion_web.php",
          type: "POST",
          async: false,
          dataType: 'json',
          data: {
            ajax: 'buscarRfc',
            rfc: $('#brfc').val(),
            recargado: 1
          },
            beforeSend: function(){
                console.log("Do something....");
                $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
            },
            success: function(data) {   
            	$("#divespera").html('');
            	if (data.status == 'OK') {
            		if (data.data.idcif != undefined){
	            		$('#rfc').val(data.data.rfc);
	            		$('#nombre').val(data.data.rs);
	            		$('#codigopostal').val(data.data.cp);
	            		$('#idcifp').val(data.data.idcif);
	            		$('#brfc').attr('readOnly', 'readOnly');
	            		$('#btnbuscar').parents('div:first').hide();
	            		$('#btnlimpiar').parents('div:first').show();
						$('#btnactualizar').parents('div:first').show();
					}
					else{
						$('#rfc').val('');
	            		$('#nombre').val('');
						$('#codigopostal').val('');
	            		document.forma.idcif.value="";
	            		$('#modalConstancia').modal('show');
					}
            	}
            	else if(data.status.indexOf('no registrado') > 0){
            		$('#rfc').val('');
            		$('#nombre').val('');
					$('#codigopostal').val('');
            		document.forma.idcif.value="";
            		$('#modalConstancia').modal('show');
            	}
            	else {
            		alert("Hay error en el sistema, favor intentarlo mas tarde")
            	}
            }
        });
	}

	function subirconstancia(){
		if ($('#idcif').val()==''){
			alert("Se necesita ingresar el IdCIF");
		}
		else if(!validarIdCIF()){
			alert("Error en el IdCIF");	
		}
		else{
			$.ajax({
	          url: "facturacion_web.php",
	          type: "POST",
	          async: false,
	          dataType: 'json',
	          data: {
	            ajax: 'buscarRfc',
	            rfc: $('#brfc').val(),
	            idcif: $('#idcif').val(),
	            recargado: 1
	          },
	            beforeSend: function(){
	                console.log("Do something....");
	                $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
	            },
	            success: function(data) {   
	            	$("#divespera").html('');
	            	if (data.status == 'OK') {
	            		$('#rfc').val(data.data.rfc);
	            		$('#nombre').val(data.data.rs);
	            		$('#codigopostal').val(data.data.cp);
	            		$('#idcifp').val($('#idcif').val());
	            		$('#brfc').attr('readOnly', 'readOnly');
	            		$('#btnbuscar').parents('div:first').hide();
	            		$('#btnlimpiar').parents('div:first').show();
						$('#btnactualizar').parents('div:first').show();
						$('#modalConstancia').modal('hide');
	            	}
	            	else if(data.status.indexOf('Error') >= 0){
	            		alert(data.status);
	            	}
	            	else {
	            		alert("Hay error en el sistema, favor intentarlo mas tarde")
	            	}
	            }
	        });
		}
	}

	function subirconstancia2(){
		if ($('#constancia').val()==''){
			alert("Se necesita seleccionar la constancia");
		}
		else{
			document.forma.cmd.value=150;
			var fd = new FormData(document.getElementById("forma"));
			$.ajax({
				url: 'facturacion_web.php',
				type: "POST",
				data: fd,
				processData: false,  // tell jQuery not to process the data
		  		contentType: false,   // tell jQuery not to set contentType
				success: function(data) {
					var datos = $.parseJSON(data);
					if (datos.status == 'OK') {
						$('#rfc').val(datos.data.rfc);
	            		$('#nombre').val(datos.data.rs);
	            		$('#codigopostal').val(datos.data.cp);
	            		$('#brfc').attr('readOnly', 'readOnly');
	            		$('#btnbuscar').parents('div:first').hide();
	            		$('#btnlimpiar').parents('div:first').show();
						$('#btnactualizar').parents('div:first').show();
						$('#modalConstancia').modal('hide');
						document.forma.constancia.value="";
					}
					else {
						alert(datos.status);
					}
				}
			});
		}
	}

	function traerDatosCli(rfc){
		return false;
		$.ajax({
          url: "facturacion_web.php",
          type: "POST",
          async: false,
          dataType: 'json',
          data: {
            ajax: 'datosclientes',
            rfc: rfc,
            recargado: 1
          },
            beforeSend: function(){
                console.log("Do something....");
                $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
            },
            success: function(data) {   
            	$("#nombre").val(data.nombre);
            	$("#calle").val(data.calle);
            	$("#numexterior").val(data.numexterior);
            	$("#numinterior").val(data.numinterior);
            	$("#colonia").val(data.colonia);
            	$("#localidad").val(data.localidad);
            	$("#municipio").val(data.municipio);
            	$("#estado").val(data.estado);
            	$("#codigopostal").val(data.codigopostal);
            }
        });
	}

	function validarIdCIF(){
        var ValidChars = "0123456789";
        var cadena=document.getElementById("idcif").value;
        var correcto = true;
        if(cadena.length!=11){
            correcto = false;
        }
        else{
            for(i=0;i<cadena.length;i++) {
                digito=cadena.charAt(i);
                if (ValidChars.indexOf(digito) == -1){
                    correcto = false;
                }
            }
        }
        return correcto;
    }

    function validarRFC(){
        var ValidChars2 = "0123456789";
        var ValidChars1 = "abcdefghijklmnÃ±opqrstuvwxyzABCDEFGHIJKLMNÃ‘OPQRSTUVWXYZ&";
        var cadena=document.getElementById("rfc").value;
        correcto = true;
        if(cadena.length!=13 && cadena.length!=12){
            correcto = false;
        }
        else{
            if(cadena.length==12)
                resta=1;
            else
                resta=0;
            for(i=0;i<cadena.length;i++) {
                digito=cadena.charAt(i);
                if (i<(4-resta) && ValidChars1.indexOf(digito) == -1){
                    correcto = false;
                }
                if (i>=(4-resta) && i<(10-resta) && ValidChars2.indexOf(digito) == -1){
                    correcto = false;
                }
                if (i>=(10-resta) && ValidChars1.indexOf(digito) == -1 && ValidChars2.indexOf(digito) == -1){
                    correcto = false;
                }
            }
        }
        return correcto;
    }

    function validar(){
        if(confirm(" Nombre: "+document.getElementById("nombre").value+"\n Email: "+document.getElementById("email").value+"\n Los datos son correctos?")){
            if(document.getElementById("nombre").value==""){
                $('#panel').hide();
                alert("Necesita ingresar la razon social");
            }
            else if(document.getElementById("regimensat").value==""){
                $('#panel').hide();
                alert("Necesita seleccionar el régimen fiscal");
            }
            else if(document.getElementById("email").value==""){
                $('#panel').hide();
                alert("Necesita ingresar el email");
            }
            else if(document.getElementById("email").value!="" && document.getElementById("confirmacionemail").value==""){
                $('#panel').hide();
                alert("Necesita ingresar la confirmacion email");
            }
            else if(document.getElementById("email").value!="" && document.getElementById("confirmacionemail").value!=document.getElementById("email").value){
                $('#panel').hide();
                alert("No son iguales los emails");
            }
            else if(document.forma.rfc.value==""){
                $('#panel').hide();
                alert("Necesita ingresar el rfc");
            }
            /*else if(document.forma.calle.value==""){
                $('#panel').hide();
                alert("Necesita ingresar la calle");
            }
            else if($.trim(document.forma.localidad.value)=="D.F." || $.trim(document.forma.localidad.value)=="D. F." || $.trim(document.forma.localidad.value).indexOf("DISTRITO FEDERAL")!=-1){
                $('#panel').hide();
                alert("En la localidad puede ir distrito federal");
            }
            else if(document.forma.municipio.value==""){
                $('#panel').hide();
                alert("Necesita ingresar el municipio");
            }
            else if(document.forma.estado.value==""){
                $('#panel').hide();
                alert("Necesita ingresar el estado");
            }
            else if($.trim(document.forma.estado.value)=="D.F." || $.trim(document.forma.estado.value)=="D. F." || $.trim(document.forma.estado.value).indexOf("DISTRITO FEDERAL")!=-1){
                $('#panel').hide();
                alert("En el estado no puede ir distrito federal");
            }*/
            else if(document.forma.codigopostal.value==""){
                $('#panel').hide();
                alert("Necesita ingresar el código postal");
            }
            else if(!validarDatosSAT()){
            	$('#panel').hide();
            }
            else if($(".tickets").length==0){
                $('#panel').hide();
                alert("No hay ningun ticket cargado");
            }
            /*else if(!seleccionoTipoPago()){
                $('#panel').hide();
                alert("Necesita seleccionar el tipo de pago");
            }
            else if(!seleccionoCuenta()){
                $('#panel').hide();
                alert("Necesita ingresar los ultimos 4 digitos de la cuenta");
            }*/
           /* else if($('.tickets').length > 10){
            	$('#panel').hide();
	        	alert("Solo se pueden agregar un máximo de 10 tickets en una factura");
	        }*/
            else if(hayplazasdiferentes()){
                $('#panel').hide();
                alert("Los tickets deben de pertenecer al mismo centro de verificación");
            }
            else{
                //atcr("facturacion_web.php","",2,0);
                mostrar_prefactura();
            }
        }
        else{
            $('#panel').hide();
        }
    }

    $(".tickets").each(function(){
		if($(this).attr("tipo_pago") == "6"){
			$(".ctipospago").show();
		}
	});

	function seleccionoTipoPago(){
		regresar = true;
		$(".tickets").each(function(){
			if($(this).attr("tipo_pago") == "6" && document.forma.tipo_pago.value == ""){
				regresar = false;
			}
		});
		return regresar;
	}

	function seleccionoCuenta(){
		regresar = true;
		$(".tickets").each(function(){
			if($(this).attr("tipo_pago") == "6" && (document.forma.tipo_pago.value == "2" || document.forma.tipo_pago.value=="5") && $.trim(document.forma.cuenta.value)==""){
				regresar = false;
			}
		});
		return regresar;
	}

	function validarDatosSAT(){
		var resultado = true;
		$.ajax({
          url: "facturacion_web.php",
          type: "POST",
          async: false,
          data: {
            ajax: 'validarDatosSAT',
            rfc: document.getElementById("rfc").value,
            nombre: document.getElementById("nombre").value,
            codigopostal: document.forma.codigopostal.value,
            regimensat: document.forma.regimensat.value,
            uso_cfdi: 'G03',
            recargado: 1
          },
           success: function(data) {   
            	if(data!='' && data.indexOf('no existe en la lista de RFC') == -1){
            		alert(data);
            		resultado = false;
            	}
            }
        });
        return resultado;
	}

    function mostrar_prefactura(){
    	$.ajax({
          url: "facturacion_web.php",
          type: "POST",
          async: false,
          data: {
            ajax: 'prefactura',
            datos: JSON.stringify($("#divdatosfactura").serializeForm()),
            recargado: 1
          },
            beforeSend: function(){
                console.log("Do something....");
                $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
            },
            success: function(data) {   
            	$("#divespera").html('');
            	$("#divdatosfactura").hide();
            	$("#prefactura").html(data);
            	$("#panel").hide();
            	$("#prefactura").show();
            }
        });
    }

    function confirmar_generacion(){
    	if(confirm("¿LOS DATOS SON CORRECTOS?, NO HABRA CANCELACIONES UNA VEZ GENERADA LA FACTURA")){
    		$('#panel').show();
			atcr("facturacion_web.php","",2,0);
    	}
    }

    function volver_editar(){
    	$("#prefactura").hide();
    	$("#divdatosfactura").show();
    }

    function agregar_cuenta(){
        $("#cuentas").append('<tr><td align="center"><select name="banco[]"><option value="">Seleccione</option><?php echo $bancos ?></select></td>\<td align="center"><input type="text" class="form-control input-sm" name="cuenta[]" value=""></td></tr>');
    }
</script>
<!--section input[type="text"]{ text-transform: uppercase; }-->
<style>
    .mayusculas { text-transform: uppercase; }
    #Resultados { min-height: 50px; text-align: center; }
	.required { border-left: 2px solid red; }
</style>
<script>
    function buscarcodigo(){
        if($.trim(document.forma.codigofactura.value)==""){
            alert("Necesita ingresar el código de facturación");
        }
        else if($('.tickets').length > 10){
        	alert("Solo se pueden agregar un máximo de 10 tickets en una factura");
        }
        else{
        	tickets_capturados = [];
        	$('.tickets').each(function(){
        		campo_ticket = $(this);
        		ticket = {};
        		ticket.plaza = campo_ticket.attr('plaza');
        		ticket.cve = campo_ticket.val();
        		tickets_capturados.push(ticket);
        	});
            $.ajax({
              url: "facturacion_web.php",
              type: "POST",
              async: false,
              dataType: "json",
              data: {
                codigofactura: document.getElementById("codigofactura").value,
                placa: document.getElementById("placa").value,
                ajax: 1,
                tickets_capturados: JSON.stringify(tickets_capturados),
                recargado: 1
              },
                beforeSend: function(){
                    console.log("Do something....");
                    $("#divespera").html('<div class="text-center"><p><i class="fa fa-spinner fa-3x fa-spin"></i></p></div>');
                },
                success: function(data) {   
                	$("#divespera").html('');
                	if(data.error == 1){
                		alert(data.mensaje);
                	}   
                	else{
	                    $("#tresultados").hide().append(data.html).fadeIn("slow");
	                    if($(".tickets").length==0){
	                    	$("#generar_factura").attr("disabled","disabled");
	                    }
	                    else{
	                    	$("#generar_factura").removeAttr("disabled");
	                    }
	                    document.getElementById("codigofactura").value = '';
	                    document.getElementById("placa").value = '';
	                }
                }
            });
        }
    }

    function quitar_ticket(campo){
    	campo.parents('tr:first').remove();
    	if($(".tickets").length==0){
    		$("#tresultados").hide();
        	$("#generar_factura").attr("disabled","disabled");
        }
        else{
        	$("#generar_factura").removeAttr("disabled");
        }
    }
    function hayplazasdiferentes(){
    	plaza = 0;
    	regresar = false;
    	$('.tickets').each(function(){
    		plaza_ticket = $(this).attr('plaza');
    		if(plaza == 0){
    			plaza = plaza_ticket
    		}
    		if(plaza != plaza_ticket){
    			regresar = true;
    		}
    	});
    	return regresar;
    }
	
	$("#placa").on("keydown", function(e){
		return e.which !== 32;
	});

	if($(".tickets").length==0){
    	$("#generar_factura").attr("disabled","disabled");
    }
    else{
    	$("#generar_factura").removeAttr("disabled");
    }
	
</script>


<?php 
}
bottom(); 

$res = mysql_query("SELECT cve FROM usuarios WHERE cve=1 AND cerrar_portal=1");
if($row=mysql_fetch_array($res)){
	echo '<script>$("#panel").show();
		alert("El portal de facturación se encuentra en mantenimiento, disculpe las molestas");
		</script>';
}
else{

	if(date('d') == date('t') && date('H:i:s') >= '21:00:00'){
		echo '<script>$("#panel").show();
			alert("El portal de facturación se encuentra en mantenimiento, disculpe las molestas");
			</script>';
	}

	if(date('Y-m-d')<'2018-08-16'){
		echo '<script>$("#panel").show();
			alert("Operación apartir del 16 de agosto");
			</script>';
	}
}
/*if(date("H:i:s")>'20:00:00' || date('H:i:s')<'08:30:00' || date('Y-m-d') == '2015-10-29' || date('w') == 0){
	if(date('Y-m-d') == '2015-10-29')
		echo '<script>$("#panel").show();
		alert("El dia de hoy permanecera cerrado el formulario de facturación por mantenimiento, disculpe las molestas");
		</script>';
	else
		echo '<script>$("#panel").show();
		alert("El horario de facturación es de 08:30 a 20:00 horas de lunes a sabado");
		</script>';
}*/

?>
<!--Start of Tawk.to Script
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/58c22d0c6b2ec15bd9f9fa30/default';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->