<?php
require_once('cnx_db.php');
require_once('globales.php'); 
if($_POST['cmd']==100){
	include("imp_factura.php");
	generaPagoPdf($_POST['cveplaza'], $_POST['reg'],1);
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
		$res = mysql_query("SELECT a.cve, a.estatus, a.respuesta1, a.folio, b.nombre FROM pagosfacturas a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN clientes c ON c.cve = a.cliente WHERE a.plaza='{$_POST['cveplaza']}' AND a.respuesta1 != ''{$where}{$orderby}");
		while($row = mysql_fetch_assoc($res)){
			generaPagoPdf($_POST['cveplaza'], $row['cve'], 0);
			if($row['estatus']=='C'){
				$zip->addFile("cfdi/comprobantes/pagoc_{$_POST['cveplaza']}_{$row['cve']}.pdf","Pago {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/pagoc_{$_POST['cveplaza']}_{$row['cve']}.pdf";
			}
			else{
				$zip->addFile("cfdi/comprobantes/pagop_{$_POST['cveplaza']}_{$row['cve']}.pdf","Pago {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/pagop_{$_POST['cveplaza']}_{$row['cve']}.pdf";
			}
			$zip->addFile("cfdi/comprobantes/cfdip_{$_POST['cveplaza']}_{$row['cve']}.xml","Pago {$row['folio']}.xml");			
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
			echo '<h1>Ocurrio un problema al cerrar el archivo favor de intentarlo de nuevo2</h1>';
		}
	}
	else{
		echo '<h1>Ocurrio un problema al generar el archivo favor de intentarlo de nuevo1</h1>';
	}
	exit();
}


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	$res = mysql_query("SELECT * FROM pagosfacturas WHERE plaza = '{$_POST['cveplaza']}' AND cve='{$_POST['pago']}'");
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
								mysql_query("UPDATE pagosfacturas SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW(),respuesta2='{$respuesta['mensaje']}', motivo_cancelacion='{$_POST['motivocancelacion']}', uuidsustituye='{$_POST['uuidsustituye']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['pago']}'");
								mysql_query("UPDATE pagosfacturasmov a INNER JOIN facturas b ON b.plaza = {$_POST['cveplaza']} AND b.cve = a.factura SET b.estatus_pago=0, b.usuario_pago='0', b.fecha_pago='', b.fechahora_pago='', b.monto_pagado=0 WHERE a.pago={$_POST['pago']}");
								include("imp_factura.php");
								generaPagoPdf($_POST['cveplaza'], $_POST['pago']);
								if($emailenvio!=""){
									$mail = obtener_mail();
									$mail->Subject = "Cancelacion de Pago {$Empresa['nombre']} {$row['folio']}";
									$mail->Body = "Cancelacion de Pago {$Empresa['nombre']} {$row['folio']}";
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo)
										$mail->AddAddress(trim($correo));
									$mail->AddAttachment("cfdi/comprobantes/pagoc_{$_POST['cveplaza']}_".$cvefact.".pdf", "Pago {$row['folio']}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdip_{$_POST['cveplaza']}_".$cvefact.".xml", "Pago {$row['folio']}.xml");
									$mail->Send();
								}	
								@unlink("cfdi/comprobantes/pagoc_{$_POST['cveplaza']}_".$cvefact.".pdf");
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
			mysql_query("UPDATE pagosfacturasmov a INNER JOIN facturas b ON b.plaza = {$_POST['cveplaza']} AND b.cve = a.factura SET b.estatus_pago=0, b.usuario_pago='0', b.fecha_pago='', b.fechahora_pago='', b.monto_pagado=0 WHERE a.pago={$_POST['pago']}");
		}
	}


	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==30){
	$pago = $_POST['pago'];
	$rsFactura = mysql_query("SELECT * FROM pagosfacturas WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['pago']}'");
	$Factura = mysql_fetch_assoc($rsFactura);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT * FROM plazas WHERE cve='{$Factura['plaza']}'"));
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$Factura['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);

	include("imp_factura.php");
	generaPagoPdf($_POST['cveplaza'], $pago);
	$emailenvio = $Cliente['email'];
	if($emailenvio!=""){
		$mail = obtener_mail();
		$mail->Subject = "Pago {$Plaza['nombre']}  {$Factura['folio']}";
		$mail->Body = "Pago {$Plaza['nombre']} {$Factura['folio']}";
		$correos = explode(",",trim($emailenvio));
		foreach($correos as $correo){
			if(trim($correo) != '')
				$mail->AddAddress(trim($correo));
		}
		$mail->AddAttachment("cfdi/comprobantes/pagop_{$_POST['cveplaza']}_".$pago.".pdf", "Pago {$Plaza['nombre']} {$Factura['folio']}.pdf");
		$mail->AddAttachment("cfdi/comprobantes/cfdip_{$_POST['cveplaza']}_".$pago.".xml", "Pago {$Plaza['nombre']} {$Factura['folio']}.xml");
		$mail->Send();
	}	
	@unlink("cfdi/comprobantes/pagop_{$_POST['cveplaza']}_{$pago}.pdf");

	exit();
}
if($_POST['cmd']==20){
	$pago = $_POST['pago'];
	$rsFactura = mysql_query("SELECT * FROM pagosfacturas WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['pago']}'");
	$Factura = mysql_fetch_assoc($rsFactura);
	if ($Factura['estatus'] != 'P' && $Factura['estatus'] != 'C'){
		mysql_query("UPDATE pagosfacturas SET fecha=CURDATE(), hora=CURTIME(), estatus='P' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['pago']}' AND respuesta1=''");
		$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$Factura['plaza']}'");
		$Sucursal = mysql_fetch_assoc($rsSucursal);
		$Empresa = mysql_fetch_assoc(mysql_query("SELECT * FROM datosempresas WHERE plaza='{$_POST['cveplaza']}'"));
		$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$Factura['cliente']}'");
		$Cliente = mysql_fetch_assoc($rsCliente);
		$resultado = validar_timbres($_POST['cveplaza']);
		if($resultado['seguir']){
			$documento = genera_arreglo_pago($_POST['cveplaza'], $_POST['pago']);
			require_once('nusoap/nusoap.php');
			$oSoapClient = new nusoap_client("https://servicios.integratucfdi.net/wscfdi.php?wsdl", true);
			$err = $oSoapClient->getError();
			if($err!="")
				echo "error1:".$err;
			else{
				//print_r($documento);
				$oSoapClient->timeout = 300;
				$oSoapClient->response_timeout = 300;
				$respuesta = $oSoapClient->call("generaComprobantePago", array ('id' => $Empresa['idplaza'],'rfcemisor' => $Empresa['rfc'],'idcertificado' => $Empresa['idcertificado'],'documento' => $documento, 'usuario' => $Empresa['usuario'],'password' => $Empresa['pass']));
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

							mysql_query("UPDATE pagosfacturas SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}',
								sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}',
								sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}',
								fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
								WHERE plaza='{$_POST['cveplaza']}' AND cve={$pago}");
							include("imp_factura.php");
							$dir="cfdi/comprobantes/";
							$dir2="cfdi/";
							$fileresult=$respuesta['archivos'];
							$strzipresponse=base64_decode($fileresult);
							$filename='cfdip_'.$_POST['cveplaza'].'_'.$pago;
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

								generaPagoPdf($_POST['cveplaza'],$pago);
								$mail = obtener_mail();
								$mail->Subject = "Complemento de Pago {$Factura['folio']}";
								$mail->Body = "Complemento de Pago {$Factura['folio']}";
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/pagop_{$_POST['cveplaza']}_".$pago.".pdf", "Pago {$Factura['folio']}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdip_{$_POST['cveplaza']}_".$pago.".xml", "Pagp {$Factura['folio']}.xml");
								$mail->Send();
								@unlink("cfdi/comprobantes/pagop_{$_POST['cveplaza']}{_{$factura}.pdf");
							}
							else{
								$strmsg='Error al descomprimir el archivo';
							}
							@unlink("cfdi/cfdip_{$_POST['cveplaza']}_{$pago}.zip");
								
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
		mysql_query("UPDATE pagosfacturas SET estatus='A' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['pago']}' AND respuesta1=''");
	}
	elseif($Factura['estatus']=='P'){
		echo 'El pago ya se encuentra en proceso de timbrado';
	}
	exit();
}
require_once('validarloging.php');
if($_POST['cmd']==0){
?>
<input type="hidden" id="pagocancelar" value="">
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

<div class="row justify-content-center">
	<div class="col-xl-10 col-lg-10 col-md-10">
		<div class="form-group row"<?php if($_POST['cveusuario']!=1){ ?> style="display: none;"<?php } ?>>
			<label class="col-sm-2 col-form-label">Tipo Fecha</label>
			<div class="col-sm-4">
            	<select name="tipo_fecha" id="tipo_fecha" class="form-control"><option value="0" selected>Fecha Creacion</option><option value="1" selected>Fecha Pago</option><option value="2">Fecha Timbrado</option></select>
        	</div>
        </div>
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
        	<label class="col-sm-2 col-form-label">Mostrar</label>
			<div class="col-sm-4">
            	<select class="form-control" id="busquedamostrar" name="busquedamostrar"><option value="">Todos</option>
            		<option value="1">Timbradas</option>
            		<option value="2">Sin Timbrar</option>
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
					<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					    Descargar
					</button>
					<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					    <a class="dropdown-item" href="javascript: descargar(0);">Descargar Listado</a>
					    <a class="dropdown-item" href="javascript: descargar(1);">Descargar Seleccionados</a>
					</div>
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
				<th>Fecha Pago</th>
				<th>Cliente</th>
				<th>RFC</th>
				<th>Forma de Pago</th>
				<th>Total</th>
				<th>Factura</th>
				<th>Estatus</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Descargar</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Fecha Pago</th>
				<th>Cliente</th>
				<th>RFC</th>
				<th>Forma de Pago</th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Factura</th>
				<th>Estatus</th>
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
				sweetAlert('', 'Necesita seleccionar al menos un pago', 'warning');
				error=1;
			}
		}
		if(error == 0){
			atcr("listadopagos.php", "_blank", 200, tipo);
		}
	}

	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'listadopagos.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafolio": $("#busquedafolio").val(),
        		"busquedacliente": $("#busquedacliente").val(),
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedarfc": $("#busquedarfc").val(),
        		'cveusuario': $('#cveusuario').val(),
        		'cveplaza': $('#cveplaza').val(),
        		"busquedamostrar": $("#busquedamostrar").val(),
        		'cvemenu': $('#cvemenu').val(),
        		'tipo_fecha': $('#tipo_fecha').val()
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
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-center", "targets": 9 },
        	{ className: "dt-head-center dt-body-center", "targets": 10 },
        	{ className: "dt-head-center dt-body-left", "targets": 11 },
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
    		'cveusuario': $('#cveusuario').val(),
    		"busquedamostrar": $("#busquedamostrar").val(),
    		'cveplaza': $('#cveplaza').val(),
    		'cvemenu': $('#cvemenu').val()
        });
        tablalistado.ajax.reload();
	}

	function timbrar(pago){
		waitingDialog.show();
		$.ajax({
			url: 'listadopagos.php',
			type: "POST",
			data: {
				cmd: 20,
				cveplaza: $('#cveplaza').val(),
				pago: pago
			},
			success: function(data) {
				waitingDialog.hide();
				sweetAlert('', data, 'success');
				buscar();
			}
		});
	}


	function reenviarcorreo(pago){
		waitingDialog.show();
		$.ajax({
			url: 'listadopagos.php',
			type: "POST",
			data: {
				cmd: 30,
				cveplaza: $('#cveplaza').val(),
				pago: pago
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
				url: 'listadopagos.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					pago: $('#pagocancelar').val(),
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

	function cancelarr(pago){
		$('#pagocancelar').val(pago);
		$("#motivocancelacion").val('');
		$("#uuidsustituye").val('');
		$('#divuuidsustituye').hide();
		$('#modalCancelacion').modal('show');
		
	}

	

	

	$("#modalCancelacion").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	
</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("","","a.folio", "CONCAT(a.fecha, ' ', a.hora)", 'a.fecha_pago', "b.nombre", "b.rfc", 'c.nombre', "IF(a.estatus='C', 0, e.monto)", "CONCAT(f.serie, ' ',f.folio)", "IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado'))", "d.usuario");

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
		$where .= " AND f.folio = '{$_POST['busquedafolio']}'";
	}
	else{
		if($_POST['busquedacliente'] != ''){
			$where .= " AND CONCAT(b.nombre) LIKE '%{$_POST['busquedacliente']}%'";
		}

		if($_POST['busquedafechaini'] != ''){
			if ($_POST['tipo_fecha']==1){
				$where .= " AND a.fechatimbre >= '{$_POST['busquedafechaini']} 00:00:00'";
			}
			else{
				$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
			}
		}

		if($_POST['busquedafechafin'] != ''){
			if ($_POST['tipo_fecha']==1){
				$where .= " AND a.fechatimbre <= '{$_POST['busquedafechafin']} 23:59:59'";
			}
			else{
				$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
			}
		}
		if($_POST['busquedarfc'] != ''){
			$where .= " AND b.rfc = '{$_POST['busquedarfc']}'";
		}
		if($_POST['busquedamostrar'] == 1){
			$where .= " AND a.respuesta1 != ''";
		}
		elseif($_POST['busquedamostrar'] == 2){
			$where .= " AND a.respuesta1 = ''";
		}
	}

	$nivelUsuario = nivelUsuario();
	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', e.monto, 0)) as total FROM pagosfacturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN pagosfacturasmov e ON e.pago=a.cve INNER JOIN facturas f ON f.plaza = a.plaza AND f.cve = e.factura{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'total' => $registros['total'],
		'existencia_timbres' => existencia_timbres($_POST['cveplaza'])
	);
	$res = mysql_query("SELECT a.plaza, a.cve, a.folio, a.fecha, a.hora, a.fecha_pago, c.nombre as nomformapago, b.nombre as nomcliente, b.rfc as rfccli, IF(a.estatus='C', 0, e.monto) as total, CONCAT(f.serie, ' ', f.folio) as foliofactura, IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado')) as nomestatus, d.usuario, a.estatus, a.respuesta1 FROM pagosfacturas a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN formapagosat c ON c.cve = a.formapago INNER JOIN pagosfacturasmov e ON e.pago=a.cve INNER JOIN facturas f ON f.plaza = a.plaza AND f.cve = e.factura LEFT JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$extras = '';
		$extras2='';

		
		$chk = '<input type="checkbox" class="form-control chks" name="fdescargar[]" value="'.$row['cve'].'"';
		if($_POST['checkall'] == 'true') $chk .= ' checked';
		$chk .= '>';
		if($row['estatus'] != 'C' && $row['respuesta1'] == ''){
			$extras .= '&nbsp;<i class="fas fa-cloud-upload-alt fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="timbrar('.$row['cve'].')" title="Timbrar"></i>';
			$extras2 .= '<a class="dropdown-item" href="#" onClick="timbrar('.$row['cve'].')">Timbrar</a>';
			$chk = '';
		}
		if($row['respuesta1'] != ''){
			$extras .= '&nbsp;<i class="fas fa-file-code fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="atcr(\'cfdi/comprobantes/cfdip_'.$row['plaza'].'_'.$row['cve'].'.xml\',\'_blank\',\'\','.$row['cve'].')" title="XML"></i>
			&nbsp;&nbsp;<i class="fas fa-mail-bulk fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="reenviarcorreo('.$row['cve'].')" title="Reenviar Correo"></i>';
			$extras2 .= '<a class="dropdown-item" href="#" onClick="atcr(\'cfdi/comprobantes/cfdip_'.$row['plaza'].'_'.$row['cve'].'.xml\',\'_blank\',\'\','.$row['cve'].')">XML</a>
			<a class="dropdown-item" href="#" onClick="reenviarcorreo('.$row['cve'].')">Reenviar Correo</a>';
		}
		if($row['estatus'] != 'C' && $nivelUsuario>2){
			$extras .= '&nbsp;<i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="cancelarr('.$row['cve'].')" title="Cancelar"></i>';
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cancelarr('.$row['cve'].')">Cancelar</a>';
		}
		


		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'listadopagos.php\',\'_blank\',100,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';

		


		$datos_renglon = array(
			$dropmenu,
			$chk,
			$row['folio'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			mostrar_fechas($row['fecha_pago']),
			utf8_encode($row['nomcliente']),
			utf8_encode($row['rfccli']),
			utf8_encode($row['nomformapago']),
			number_format($row['total'],2),
			$row['foliofactura'],
			$row['nomestatus'],
			$row['usuario']
		);
		$resultado['data'][] = $datos_renglon;
	}
	echo json_encode($resultado);

}


?>