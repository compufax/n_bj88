<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function por_facturar($plaza, $fecha_ini, $fecha_fin){
	$monto = 0;
	$res = mysql_query("SELECT SUM(copias*costo_copias) FROM cobro_engomado WHERE plaza='{$plaza}' AND estatus!='C' AND tipo_pago NOT IN (2,6, 12) AND fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}'");
	while($row = mysql_fetch_array($res)){
		$monto+=$row[0];
	}

	$res = mysql_query("SELECT SUM(c.importe+c.importe_iva) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente INNER JOIN facturasmov c on a.plaza = c.plaza and a.cve = c.cvefact AND c.claveprodsat='80141600' WHERE a.estatus!='C' AND a.fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}' GROUP BY a.plaza");
	while($row = mysql_fetch_array($res)){
		$monto-=$row[0];
	}
	return $monto;
}

function obtener_informacion($datos){
	$resultado = array();
	$res=mysql_query("SELECT cve, numero, nombre FROM plazas WHERE estatus!='I' ORDER BY numero");
	while($row=mysql_fetch_array($res)){
		$resultado[$row['cve']] = array('plaza' => $row['numero'].' '.$row['nombre']);
	}

	$res = mysql_query("SELECT plaza, SUM(copias) FROM cobro_engomado WHERE estatus!='C' AND tipo_pago NOT IN (2,6, 12) AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['ventas']=$row[1];
		$resultado[$row['plaza']]['porfacturar']=$row[1];
	}
	
	$res = mysql_query("SELECT a.plaza, SUM(c.importe+c.importe_iva), SUM(IF(b.rfc!='XAXX010101000',c.cantidad,0)), SUM(IF(b.rfc='XAXX010101000',c.cantidad,0)) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente INNER JOIN facturasmov c on a.plaza = c.plaza and a.cve = c.cvefact AND c.claveprodsat='80141600' WHERE a.estatus!='C' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY a.plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['facturacion']=$row[1];
		$resultado[$row['plaza']]['porfacturar']-=$row[1];
	}
	
	return $resultado;
}
require_once('validarloging.php');

if($_POST['cmd']==0){
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio" value="<?php echo date('Y-m');?>-01">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
        <?php if($_POST['cveusuario']==1){ ?>
        	<div class="form-group row">
				<label class="col-sm-3 col-form-label">Concepto Factura</label>
				<div class="col-sm-6">
	            	<textarea id="concepto_factura" name="concepto_factura" class="form-control" size="3"></textarea>
	        	</div>
	        </div>
        <?php } ?>
        
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
        	</div>
        </div>
    </div>
</div>
<div class="row" id="resultadocorte">
	
</div>
<script>
	function buscar(){
		$.ajax({
		  url: 'repcopiasfacturacion.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 10,
			cveusuario: $('#cveusuario').val(),
			busquedafechaini: $('#busquedafechaini').val(),
			busquedafechafin: $('#busquedafechafin').val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				$('#resultadocorte').html(data);
			}
		});
	}

	function facturar(plaza, fecha_ini, fecha_fin){
		$.ajax({
		  url: 'repcopiasfacturacion.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 2,
			cveusuario: $('#cveusuario').val(),
			fecha_ini: fecha_ini,
			fecha_fin: fecha_fin,
			plaza: plaza,
			concepto: $('#concepto_factura').val(),
    		cvemenu: $('#cvemenu').val(),
    		cveplaza: $('#cveplaza').val(),
    		cveusuario: $('#cveusuario').val()
		  },
			success: function(data) {
				alert(data);
				buscar();
			}
		});
	}
</script>
<?php
}

if($_POST['cmd']==10){
	$res = obtener_informacion($_POST);
	$colspan = 1;
?>
	<table class="table">
	  <thead>
	    <tr>
	    <?php if($_POST['cveusuario']==1){

	    	$colspan = 2;
	    ?>
	      <th scope="col" style="text-align: center;">Facturar</th>
	    <?php } ?>
	      <th scope="col" style="text-align: center;">Centro</th>
	      <th scope="col" style="text-align: center;">Copias</th>
	      <th scope="col" style="text-align: center;">Facturacion</th> 
	      <th scope="col" style="text-align: center;">Por Facturar</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $idplaza => $row){
	?>
	    <tr>
	      <?php if($_POST['cveusuario']==1){ ?>
	      	<td align="center"><span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-file-invoice-dollar" onClick="facturar(<?php echo $idplaza;?>,'<?php echo $_POST['busquedafechaini'];?>','<?php echo $_POST['busquedafechafin'];?>')" title="Facturar"></i></span></td>
	      <?php } ?>
	      <td align="left"><?php echo utf8_encode($row['plaza']);?></td>
	      <td align="right"><?php echo number_format($row['ventas'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturacion'],2);?></td>
	      <td align="right"><?php echo number_format($row['porfacturar'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['ventas'];
		$totales[1]+=$row['facturacion'];
		$totales[2]+=$row['porfacturar'];
	}
	?>
		<tr>
			<th colspan="<?php echo $colspan;?>" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
		</tr>
	  </tbody>
	</table>
	

<?php
}

if($_POST['cmd']==2){
	include("imp_factura.php");
	$_POST['tipo_serie'] = 0;
	$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['plaza']}'");
	$rowplaza = mysql_fetch_array($resplaza);
	$resempresa = mysql_query("SELECT * FROM datosempresas WHERE plaza='{$_POST['plaza']}'");
	$rowempresa = mysql_fetch_array($resempresa);
	if($_POST['tipo_serie']==0)
		$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['plaza']}' AND tipo=0 AND tipodocumento=1");
	else
		$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['plaza']}' AND tipo=0 AND tipodocumento=5");
	$row = mysql_fetch_array($res);
	$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM facturas WHERE plaza='{$_POST['plaza']}' AND serie='{$row['serie']}'");
	$row1 = mysql_fetch_array($res1);
	if($row['folio_inicial']<$row1[0]){
		$row['folio_inicial'] = $row1[0];
	}
	$res1=mysql_query("SELECT cve FROM clientes WHERE plaza='{$_POST['plaza']}' AND rfc='XAXX010101000'");
	$row1=mysql_fetch_array($res1);
	$_POST['cliente'] = $row1[0];
	$monto = por_facturar($_POST['fecha'], $_POST['fecha_ini'], $_POST['fecha_fin']);
	if($_POST['cliente'] > 0 && $monto > 0){
		$insert = "INSERT facturas SET plaza='{$_POST['plaza']}',serie='{$row['serie']}',folio='{$row['folio_inicial']}',fecha=CURDATE(),fecha_creacion=CURDATE(),hora=CURTIME(),obs='".addslashes($_POST['obs'])."',factura_copias=1,
		cliente='{$_POST['cliente']}',tipo_pago='0',forma_pago='0',usuario='{$_POST['cveusuario']}'";
		while(!$resinsert=mysql_query($insert)){
			$row['folio_inicial']++;
			$insert = "INSERT facturas SET plaza='{$_POST['plaza']}',serie='{$row['serie']}',folio='{$row['folio_inicial']}',fecha=CURDATE(),fecha_creacion=CURDATE(),hora=CURTIME(),obs='".addslashes($_POST['obs'])."',factura_copias=1,
			cliente='{$_POST['cliente']}',tipo_pago='0',forma_pago='0',usuario='{$_POST['cveusuario']}'";
		}
		$foliofactura = $row['serie'].' '.$row['folio_inicial'];
		$cvefact=mysql_insert_id();
		$documento=array();
		require_once("nusoap/nusoap.php");
		$importe = $monto/1.16;
		$importe_iva = $monto-$importe;
		mysql_query("INSERT facturasmov SET plaza='{$_POST['plaza']}',cvefact='{$cvefact}',cantidad='1',concepto='COPIAS ".addslashes($_POST['concepto'])."',
			precio='{$importe}',importe='{$importe}',iva='16',importe_iva='{$importe_iva}',unidad='SERVICIO',
			engomado='0',claveprodsat='80141600',claveunidadsat='H87'");

		mysql_query("UPDATE facturas SET subtotal='{$importe}',iva='{$importe_iva}',total='{$_POST['monto']}' WHERE plaza='{$_POST['plaza']}' AND cve={$cvefact}");
		$documento = genera_arreglo_facturacion($_POST['plaza'], $cvefact, 'I');
		$resultadotimbres = validar_timbres($_POST['plaza']);
		if($resultadotimbres['seguir']){
			//$oSoapClient = new nusoap_client("http://compuredes.mx/webservices/wscfdi2012.php?wsdl", true);			
			$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);
			$err = $oSoapClient->getError();
			if($err!=""){
				echo "error1:".$err;
				desbloquear_timbre($_POST['plazausuario'], $resultadotimbres['cvecompra']);
			}
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
					desbloquear_timbre($_POST['plazausuario'], $resultadotimbres['cvecompra']);
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
						desbloquear_timbre($_POST['plazausuario'], $resultadotimbres['cvecompra']);
					}
					else{
						if($respuesta['resultado']){
							mysql_query("UPDATE facturas SET respuesta1='".$respuesta['uuid']."',seriecertificado='".$respuesta['seriecertificado']."',
							sellodocumento='".$respuesta['sellodocumento']."',uuid='".$respuesta['uuid']."',seriecertificadosat='".$respuesta['seriecertificadosat']."',
							sellotimbre='".$respuesta['sellotimbre']."',cadenaoriginal='".$respuesta['cadenaoriginal']."',
							fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
							WHERE plaza='".$_POST['plazausuario']."' AND cve=".$cvefact);
							mysql_query("UPDATE facturas SET rfc_cli='".$row['rfc']."', nombre_cli='".$row['nombre']."', calle_cli='".$row['calle']."', numext_cli='".$row['numexterior']."', numint_cli = '".$row['numinterior']."', colonia_cli = '".$row['colonia']."', localidad_cli = '".$row['localidad']."', municipio_cli = '".$row['municipio']."',
								estado_cli='".$row['estado']."', cp_cli='".$row['codigopostal']."'
							WHERE plaza='".$_POST['plazausuario']."' AND cve=".$cvefact);
							//Tomar la informacion de Retorno
							$dir="cfdi/comprobantes/";
							//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
							//el zip siempre se deja fuera
							$dir2="cfdi/";
							//Leer el Archivo Zip
							$fileresult=$respuesta['archivos'];
							$strzipresponse=base64_decode($fileresult);
							$filename='cfdi_'.$_POST['plaza'].'_'.$cvefact;
							file_put_contents($dir2.$filename.'.zip', $strzipresponse);
							$zip = new ZipArchive;
							if ($zip->open($dir2.$filename.'.zip') === TRUE){
								$strxml=$zip->getFromName('xml.xml');
								file_put_contents($dir.$filename.'.xml', $strxml);
								//$strpdf=$zip->getFromName('formato.pdf');
								//file_put_contents($dir.$filename.'.pdf', $strpdf);
								$zip->close();		
								generaFacturaPdf($_POST['plaza'],$cvefact);
								if($emailenvio!=""){
									$mail = obtener_mail();
									$mail->FromName = "Verificentros Plaza {$rowplaza['numero']}";
									$mail->Subject = "Factura {$foliofactura}";
									$mail->Body = "Factura {$foliofactura}";
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo)
										$mail->AddAddress(trim($correo));
									$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['plaza']}_{$cvefact}.pdf", "Factura {$foliofactura}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['plaza']}_{$cvefact}.xml", "Factura {$foliofactura}.xml");
									$mail->Send();
								}	
								@unlink("../cfdi/comprobantes/factura_{$_POST['plaza']}_{$cvefact}.pdf");
								echo 'Se genero la Factura '.$foliofactura;
							}
							else 
								$strmsg='Error al descomprimir el archivo';
						}
						else{
							$strmsg=$respuesta['mensaje'];
							desbloquear_timbre($_POST['plazausuario'], $resultadotimbres['cvecompra']);
						}
						//print_r($respuesta);	
						echo $strmsg;
					}
				}
			}
		}
	}
	else{
		echo 'No se encontro al cliente publico general o el importe es cero';
	}
}
?>