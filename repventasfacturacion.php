<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

function por_facturar($plaza, $fecha_ini, $fecha_fin){
	$monto = 0;
	$res = mysql_query("SELECT SUM(monto) FROM cobro_engomado WHERE plaza='{$plaza}' AND estatus!='C' AND tipo_pago NOT IN (2,6, 12) AND fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}'");
	while($row = mysql_fetch_array($res)){
		$monto+=$row[0];
	}
	$res = mysql_query("SELECT SUM(monto) FROM pagos_caja WHERE plaza='{$plaza}' AND estatus!='C' AND fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}'");
	while($row = mysql_fetch_array($res)){
		$monto+=$row[0];
	}
	
	$res = mysql_query("SELECT SUM(devolucion) FROM devolucion_certificado WHERE plaza='{$plaza}' AND estatus!='C' AND fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}'");
	while($row = mysql_fetch_array($res)){
		$monto-=$row[0];
	}
	//$res = mysql_query("SELECT SUM(IF(b.rfc!='XAXX010101000',a.total,0)), SUM(IF(b.rfc='XAXX010101000',a.total,0)) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente WHERE a.plaza='{$plaza}' AND a.estatus!='C' AND a.fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}' AND factura_copias!=1 GROUP BY a.plaza");
	$res = mysql_query("SELECT SUM(c.importe+c.importe_iva) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente INNER JOIN facturasmov c on a.plaza = c.plaza and a.cve = c.cvefact AND c.claveprodsat!='80141600' WHERE a.estatus!='C' AND a.fecha BETWEEN '{$fecha_ini}' AND '{$fecha_fin}' GROUP BY a.plaza");
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

	$res = mysql_query("SELECT plaza, SUM(monto) FROM cobro_engomado WHERE estatus!='C' AND tipo_pago NOT IN (2,6, 12) AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['ventas']=$row[1];
		$resultado[$row['plaza']]['porfacturar']=$row[1];
	}
	$res = mysql_query("SELECT plaza, SUM(monto) FROM pagos_caja WHERE estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['pagoscaja']=$row[1];
		$resultado[$row['plaza']]['porfacturar']+=$row[1];
	}
	$res = mysql_query("SELECT plaza, SUM(total) FROM vales_externos WHERE estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['valeexterno']=$row[1];
		$resultado[$row['plaza']]['porfacturar']+=$row[1];
	}
	$res = mysql_query("SELECT plaza, SUM(devolucion) FROM devolucion_certificado WHERE estatus!='C' AND fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['devoluciones']=$row[1];
		$resultado[$row['plaza']]['porfacturar']-=$row[1];
	}
	//$res = mysql_query("SELECT a.plaza, SUM(IF(b.rfc!='XAXX010101000',a.total,0)), SUM(IF(b.rfc='XAXX010101000',a.total,0)) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente WHERE a.estatus!='C' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' AND factura_copias!=1 GROUP BY a.plaza");
	$res = mysql_query("SELECT a.plaza, SUM(c.importe+c.importe_iva), SUM(IF(b.rfc!='XAXX010101000',c.importe+c.importe_iva,0)), SUM(IF(b.rfc='XAXX010101000',c.importe+c.importe_iva,0)) FROM facturas a INNER JOIN clientes b ON b.cve=a.cliente INNER JOIN facturasmov c on a.plaza = c.plaza and a.cve = c.cvefact AND c.claveprodsat!='80141600' WHERE a.estatus!='C' AND a.fecha BETWEEN '{$datos['busquedafechaini']}' AND '{$datos['busquedafechafin']}' GROUP BY a.plaza");
	while($row = mysql_fetch_array($res)){
		$resultado[$row['plaza']]['facturacion']=$row[1];
		$resultado[$row['plaza']]['porfacturar']-=$row[1];
		$resultado[$row['plaza']]['facturaciongeneral']=$row[3];
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
        
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        	<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>
				<button type="button" class="btn btn-info" onClick="atcr('repventasfacturacion.php','_blank', 120, 0);">
	            	Excel
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
		  url: 'repventasfacturacion.php',
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
		  url: 'repventasfacturacion.php',
		  type: "POST",
		  data: {
			menu: $('#cvemenu').val(),
			cmd: 2,
			cveusuario: $('#cveusuario').val(),
			fecha_ini: fecha_ini,
			fecha_fin: fecha_fin,
			plaza: plaza,
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
if($_POST['cmd']==120){
header('Content-type: application/vnd.ms-excel');
header("Content-Disposition: attachment; filename=Reporte de Importe por Facturar.xls");
header("Pragma: no-cache");
header("Expires: 0");
	$res = obtener_informacion($_POST);
	$colspan = 1;
?>

	<table class="table" >
	<tr style="font-size:24px">
	<td align="center" colspan="8" style="font-size:24px">Reporte de Importe por Facturar</td>
	</tr>
	<tr style="font-size:24px">
	<td align="left" colspan="8" style="font-size:24px"> Periodo: <?php echo $_POST['busquedafechaini'] ?> al <?php echo $_POST['busquedafechafin']?></td>
	</tr>
	</table>
	<br>
	
	<table class="table">
	  <thead>
	    <tr>
	    <!--<?php if($_POST['cveusuario']==1){

	    	//$colspan = 2;
	    ?>
	      <th scope="col" style="text-align: center;">Facturar</th>
	    <?php } ?>-->
	      <th scope="col" style="text-align: center;">Centro</th>
	      <th scope="col" style="text-align: center;">Ventas</th>
		  <th scope="col" style="text-align: center;">Pagos en Caja</th> 
	      <th scope="col" style="text-align: center;">Devoluciones</th> 
	      <th scope="col" style="text-align: center;">Facturacion</th> 
	      <th scope="col" style="text-align: center;">Por Facturar</th> 
	      <th scope="col" style="text-align: center;">Factura Publico en General</th>
	      <th scope="col" style="text-align: center;">Factura a Clientes</th> 
	    </tr>
	  </thead>
	  <tbody>
	<?php
		$totales = array();
		$i = 0;
		foreach($res as $idplaza => $row){
	?>
	    <tr>
	      <!--<?php if($_POST['cveusuario']==1){ ?>
	      	<td align="center"><span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-file-invoice-dollar" onClick="facturar(<?php echo $idplaza;?>,'<?php echo $_POST['busquedafechaini'];?>','<?php echo $_POST['busquedafechafin'];?>')" title="Facturar"></i></span></td>
	      <?php } ?>-->
	      <td align="left"><?php echo utf8_encode($row['plaza']);?></td>
	      <td align="right"><?php echo number_format($row['ventas'],2);?></td>
	      <td align="right"><?php echo number_format($row['pagoscaja'],2);?></td>
	      <td align="right"><?php echo number_format($row['devoluciones'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturacion'],2);?></td>
	      <td align="right"><?php echo number_format($row['porfacturar'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturaciongeneral'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturacion']-$row['facturaciongeneral'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['ventas'];
		$totales[1]+=$row['pagoscaja'];
		$totales[2]+=$row['devoluciones'];
		$totales[3]+=$row['facturacion'];
		$totales[4]+=$row['porfacturar'];
		$totales[5]+=$row['facturaciongeneral'];
		$totales[6]+=$row['facturacion']-$row['facturaciongeneral'];
	}
	?>
		<tr>
			<th colspan="<?php echo $colspan;?>" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[3],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[4],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[5],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[6],2);?></th>
		</tr>
	  </tbody>
	</table>
	

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
	      <th scope="col" style="text-align: center;">Ventas</th>
		  <th scope="col" style="text-align: center;">Pagos en Caja</th> 
	      <th scope="col" style="text-align: center;">Devoluciones</th> 
	      <th scope="col" style="text-align: center;">Facturacion</th> 
	      <th scope="col" style="text-align: center;">Por Facturar</th> 
	      <th scope="col" style="text-align: center;">Factura Publico en General</th>
	      <th scope="col" style="text-align: center;">Factura a Clientes</th> 
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
	      <td align="right"><?php echo number_format($row['pagoscaja'],2);?></td>
	      <td align="right"><?php echo number_format($row['devoluciones'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturacion'],2);?></td>
	      <td align="right"><?php echo number_format($row['porfacturar'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturaciongeneral'],2);?></td>
	      <td align="right"><?php echo number_format($row['facturacion']-$row['facturaciongeneral'],2);?></td>
	    </tr>
	<?php
		$i++;
		$totales[0]+=$row['ventas'];
		$totales[1]+=$row['pagoscaja'];
		$totales[2]+=$row['devoluciones'];
		$totales[3]+=$row['facturacion'];
		$totales[4]+=$row['porfacturar'];
		$totales[5]+=$row['facturaciongeneral'];
		$totales[6]+=$row['facturacion']-$row['facturaciongeneral'];
	}
	?>
		<tr>
			<th colspan="<?php echo $colspan;?>" style="text-align: left;"><?php echo $i;?> Registro(s)</th>
			<th style="text-align: right;"><?php echo number_format($totales[0],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[1],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[2],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[3],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[4],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[5],2);?></th>
			<th style="text-align: right;"><?php echo number_format($totales[6],2);?></th>
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
	$monto = por_facturar($_POST['plaza'], $_POST['fecha_ini'], $_POST['fecha_fin']);
	$mes = substr($_POST['fecha_ini'], 5, 2);
	$anio = substr($_POST['fecha_ini'], 0, 4);
	if($_POST['cliente'] > 0 && $monto > 0){
		$insert = "INSERT facturas SET plaza='{$_POST['plaza']}',serie='{$row['serie']}',folio='{$row['folio_inicial']}',fecha='".date('Y-m-d')."',fecha_creacion='".date('Y-m-d')."',hora='".date('H:i:s')."',obs='".addslashes($_POST['obs'])."',
		cliente='{$_POST['cliente']}',tipo_pago='1',forma_pago='0',usuario='{$_POST['cveusuario']}', periodicidad='04', meses='{$mes}', anio='{$anio}'";
		while(!$resinsert=mysql_query($insert)){
			$row['folio_inicial']++;
			$insert = "INSERT facturas SET plaza='{$_POST['plaza']}',serie='{$row['serie']}',folio='{$row['folio_inicial']}',fecha='".date('Y-m-d')."',fecha_creacion='".date('Y-m-d')."',hora='".date('H:i:s')."',obs='".addslashes($_POST['obs'])."',
			cliente='{$_POST['cliente']}',tipo_pago='0',forma_pago='0',usuario='{$_POST['cveusuario']}', periodicidad='04', meses='{$mes}', anio='{$anio}'";
		}
		$foliofactura = $row['serie'].' '.$row['folio_inicial'];
		$cvefact=mysql_insert_id();
		$documento=array();
		require_once("nusoap/nusoap.php");
		$importe = $monto/1.16;
		$importe_iva = $monto-$importe;
		mysql_query("INSERT facturasmov SET plaza='{$_POST['plaza']}',cvefact='{$cvefact}',cantidad='1',concepto='VENTAS DE CERTIFICADOS',
			precio='{$importe}',importe='{$importe}',iva='16',importe_iva='{$importe_iva}',unidad='SERVICIO',
			engomado='0',claveprodsat='77121503',claveunidadsat='E48'");

		mysql_query("UPDATE facturas SET subtotal='{$importe}',iva='{$importe_iva}',total='{$monto}' WHERE plaza='{$_POST['plaza']}' AND cve={$cvefact}");
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