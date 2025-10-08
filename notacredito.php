<?php
require_once('cnx_db.php');
require_once('globales.php'); 
if($_POST['cmd']==100){
	include("imp_factura.php");
	generaFacturaPdf($_POST['cveplaza'], $_POST['reg'],1, 2);
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
		$res = mysql_query("SELECT a.cve, a.estatus, a.respuesta1, a.serie, a.folio, b.nombre FROM notascredito a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN clientes c ON c.cve = a.cliente WHERE a.plaza='{$_POST['cveplaza']}' AND a.respuesta1 != ''{$where}{$orderby}");
		while($row = mysql_fetch_assoc($res)){
			generaFacturaPdf($_POST['cveplaza'], $row['cve'], 0, 2);
			if($row['estatus']=='C'){
				$zip->addFile("cfdi/comprobantes/ncc_{$row['cve']}.pdf","{$row['nombre']} {$row['serie']} {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/ncc_{$row['cve']}.pdf";
			}
			else{
				$zip->addFile("cfdi/comprobantes/nc_{$row['cve']}.pdf","{$row['nombre']} {$row['serie']} {$row['folio']}.pdf");
				$archivos[] = "cfdi/comprobantes/nc_{$row['cve']}.pdf";
			}
			$zip->addFile("cfdi/comprobantes/cfdinc_{$row['cve']}.xml","{$row['nombre']} {$row['serie']} {$row['folio']}.xml");			
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
			echo '<h2>Ocurrio un problema al cerrar el archivo favor de intentarlo de nuevo</h2>';
		}
	}
	else{
		echo '<h2>Ocurrio un problema al generar el archivo favor de intentarlo de nuevo</h2>';
	}
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	$res = mysql_query("SELECT * FROM notascredito WHERE plaza = '{$_POST['cveplaza']}' AND cve='{$_POST['cfdi']}'");
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
								mysql_query("UPDATE notascredito SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW(),respuesta2='{$respuesta['mensaje']}', motivo_cancelacion='{$_POST['motivocancelacion']}', uuidsustituye='{$_POST['uuidsustituye']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cfdi']}'");
								mysql_query("UPDATE cobro_engomado SET notacredito=0 WHERE plaza='{$_POST['cveplaza']}' AND notacredito='{$_POST['cfdi']}'");
								include("imp_factura.php");
								generaFacturaPdf($_POST['cveplaza'], $_POST['cfdi'], 0, 2);
								if($emailenvio!=""){
									$mail = obtener_mail();
									$mail->Subject = "Cancelacion de Nota de Credito {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
									$mail->Body = "Cancelacion de Nota de Credito {$Empresa['nombre']} {$row['serie']} {$row['folio']}";
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo)
										$mail->AddAddress(trim($correo));
									$mail->AddAttachment("cfdi/comprobantes/ncc_{$_POST['cveplaza']}_".$cvefact.".pdf", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdinc_{$_POST['cveplaza']}_".$cvefact.".xml", "{$Sucursal['nombre']} {$row['serie']} {$row['folio']}.xml");
									$mail->Send();
								}	
								@unlink("cfdi/comprobantes/ncc_{$_POST['cveplaza']}_".$cvefact.".pdf");
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
			mysql_query("UPDATE notascredito SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cfdi']}'");
			mysql_query("UPDATE cobro_engomado SET notacredito=0 WHERE plaza='{$_POST['cveplaza']}' AND notacredito='{$_POST['cfdi']}'");
		}
	}


	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==30){
	$cfdi = $_POST['cfdi'];
	$rsCFDI = mysql_query("SELECT * FROM notascredito WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['cfdi']}'");
	$CFDI = mysql_fetch_assoc($rsCFDI);
	$Plaza = mysql_fetch_assoc(mysql_query("SELECT * FROM plazas WHERE cve='{$CFDI['plaza']}'"));
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$CFDI['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);

	include("imp_factura.php");
	generaFacturaPdf($_POST['cveplaza'], $cfdi, 0, 2);
	$emailenvio = $Cliente['email'];
	if($emailenvio!=""){
		$mail = obtener_mail();
		$mail->Subject = "Nota Credito {$Plaza['nombre']} {$CFDI['serie']} {$CFDI['folio']}";
		$mail->Body = "Nota Credito {$Plaza['nombre']} {$CFDI['serie']} {$CFDI['folio']}";
		$correos = explode(",",trim($emailenvio));
		foreach($correos as $correo){
			if(trim($correo) != '')
				$mail->AddAddress(trim($correo));
		}
		$mail->AddAttachment("cfdi/comprobantes/nc_{$_POST['cveplaza']}_".$cfdi.".pdf", "{$Plaza['nombre']} {$CFDI['serie']} {$CFDI['folio']}.pdf");
		$mail->AddAttachment("cfdi/comprobantes/cfdinc_{$_POST['cveplaza']}_".$cfdi.".xml", "{$Plaza['nombre']} {$CFDI['serie']} {$CFDI['folio']}.xml");
		$mail->Send();
	}	
	@unlink("cfdi/comprobantes/nc_{$_POST['cveplaza']}_{$cfdi}.pdf");

	exit();
}
if($_POST['cmd']==20){
	$cfdi = $_POST['cfdi'];
	$rsCFDI = mysql_query("SELECT * FROM notascredito WHERE plaza='{$_POST['cveplaza']}' AND cve = '{$_POST['cfdi']}'");
	$CFDI = mysql_fetch_assoc($rsCFDI);
	mysql_query("UPDATE notascredito SET fecha=CURDATE(), hora=CURTIME() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cfdi']}' AND respuesta1=''");
	$rsSucursal = mysql_query("SELECT * FROM datosempresas WHERE plaza = '{$CFDI['plaza']}'");
	$Sucursal = mysql_fetch_assoc($rsSucursal);
	$rsCliente = mysql_query("SELECT email FROM clientes WHERE cve = '{$CFDI['cliente']}'");
	$Cliente = mysql_fetch_assoc($rsCliente);
	$resultado = validar_timbres($_POST['cveplaza']);
	if($resultado['seguir']){
		$documento = genera_arreglo_facturacion($_POST['cveplaza'], $_POST['cfdi'], 'E');
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

						mysql_query("UPDATE notascredito SET respuesta1='{$respuesta['uuid']}',seriecertificado='{$respuesta['seriecertificado']}',
						sellodocumento='{$respuesta['sellodocumento']}',uuid='{$respuesta['uuid']}',seriecertificadosat='{$respuesta['seriecertificadosat']}',
						sellotimbre='{$respuesta['sellotimbre']}',cadenaoriginal='{$respuesta['cadenaoriginal']}',
						fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
						WHERE plaza='{$_POST['cveplaza']}' AND cve={$_POST['cfdi']}");

						include("imp_factura.php");
						//Tomar la informacion de Retorno
						$dir="cfdi/comprobantes/";
						//$dir=dirname(realpath(getcwd()))."/solucionesfe_facturacion/cfdi/comprobantes/";
						//el zip siempre se deja fuera
						$dir2="cfdi/";
						//Leer el Archivo Zip
						$fileresult=$respuesta['archivos'];
						$strzipresponse=base64_decode($fileresult);
						$filename='cfdinc_'.$_POST['cveplaza'].'_'.$cfdi;
						file_put_contents($dir2.$filename.'.zip', $strzipresponse);
						$zip = new ZipArchive;
						if ($zip->open($dir2.$filename.'.zip') === TRUE){
							$strxml=$zip->getFromName('xml.xml');
							file_put_contents($dir.$filename.'.xml', $strxml);
							$zip->close();		
							generaFacturaPdf($_POST['cveplaza'], $cfdi, 0, 2);
							$emailenvio = $Cliente['email'];
							//require_once("phpmailer/class.phpmailer.php");
							if($emailenvio!=""){
								$mail = obtener_mail();
								$mail->Subject = "Nota Credito {$CFDI['serie']} {$CFDI['folio']}";
								$mail->Body = "Nota Credito {$CFDI['serie']} {$CFDI['folio']}";
								$correos = explode(",",trim($emailenvio));
								foreach($correos as $correo)
									$mail->AddAddress(trim($correo));
								$mail->AddAttachment("cfdi/comprobantes/factura_{$_POST['cveplaza']}_".$cfdi.".pdf", "{$Sucursal['nombre']} {$CFDI['serie']} {$CFDI['folio']}.pdf");
								$mail->AddAttachment("cfdi/comprobantes/cfdi_{$_POST['cveplaza']}_".$cfdi.".xml", "{$Sucursal['nombre']} {$CFDI['serie']} {$CFDI['folio']}.xml");
								$mail->Send();
							}	
							@unlink("cfdi/comprobantes/nc_{$_POST['cveplaza']}{_{$cfdi}.pdf");
							@unlink("cfdi/cfdinc_{$_POST['cveplaza']}_{$cfdi}.zip");
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
<input type="hidden" id="cfdicancelar" value="">
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
	<div class="col-xl-12 col-lg-12 col-md-12">
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
            	<input type="number" class="form-control" id="busuqedafolio" name="busuqedafolio" placeholder="Folio">
        	</div>
			<label class="col-sm-2 col-form-label">Cliente</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedacliente" name="busquedacliente" placeholder="Cliente">
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
		        <!--<div class="btn-group">
		        	<button type="button" class="btn btn-primary" onClick="atcr('notacredito.php', '', 1, 0);">
		            	Nueva Nota Credito
		        	</button>
		        </div>
		        	&nbsp;&nbsp;-->
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
</div>
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Cliente</th>
				<th>Tipo de Pago</th>
				<th>Total</th>
				<th>Estatus</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
				<th>Descargar</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Cliente</th>
				<th>Tipo de Pago</th>
				<th>Total<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Estatus</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
				<th>Descargar</th>
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
			atcr("notacredito.php", "_blank", 200, tipo);
		}
	}

	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'notacredito.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafolio": $("#busquedafolio").val(),
        		"busquedacliente": $("#busquedacliente").val(),
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		'cveusuario': $('#cveusuario').val(),
        		'cveplaza': $('#cveplaza').val(),
        		'cvemenu': $('#cvemenu').val()
        	},
        	fncallback: function(json){
        		$('#ttotal').html(json.total);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "bPaginate": true,
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-right", "targets": 4 },
        	{ className: "dt-head-center dt-body-center", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-center", "targets": 7 },
        	{ className: "dt-head-center dt-body-center", "targets": 8 },
        	{ orderable: false, "targets": 7 },
        	{ orderable: false, "targets": 8 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafolio": $("#busquedafolio").val(),
    		"busquedacliente": $("#busquedacliente").val(),
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		'cveusuario': $('#cveusuario').val(),
    		'cveplaza': $('#cveplaza').val(),
    		'cvemenu': $('#cvemenu').val()
        });
        tablalistado.ajax.reload();
	}

	function timbrar(cfdi){
		waitingDialog.show();
		$.ajax({
			url: 'notacredito.php',
			type: "POST",
			data: {
				cmd: 20,
				cveplaza: $('#cveplaza').val(),
				cfdi: cfdi
			},
			success: function(data) {
				waitingDialog.hide();
				sweetAlert('', data, 'success');
				buscar();
			}
		});
	}


	function reenviarcorreo(cfdi){
		waitingDialog.show();
		$.ajax({
			url: 'notacredito.php',
			type: "POST",
			data: {
				cmd: 30,
				cveplaza: $('#cveplaza').val(),
				cfdi: cfdi
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
				url: 'notacredito.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					cfdi: $('#cfdicancelar').val(),
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

	function cancelarr(cfdi){
		$('#cfdicancelar').val(cfdi);
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
	$columnas=array("CONCAT(a.folio, ' ', a.serie)", "CONCAT(a.fecha, ' ', a.hora)", "b.nombre", 'c.nombre', "IF(a.estatus='C', 0, a.total)", "IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado'))", 'd.usuario');

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
	}

	$nivelUsuario = nivelUsuario();
	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.total, 0)) as total FROM facturas a INNER JOIN notascredito b ON b.cve = a.cliente{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'total' => $registros['total']
	);
	$res = mysql_query("SELECT a.plaza, a.cve, a.serie, a.folio, a.fecha, a.hora, b.nombre as nomcliente, c.nombre as nomtipopagofac, IF(a.estatus='C', 0, a.total) as total, IF(a.estatus='C', 'Cancelado', IF(a.respuesta1='', 'Pendiente de Timbrar', 'Timbrado')) as nomestatus, d.usuario, a.estatus, a.respuesta1 FROM notascredito a INNER JOIN clientes b ON b.cve = a.cliente INNER JOIN tipos_pago_factura c ON c.cve = a.tipo_pago LEFT JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$extras = '';
		$chk = '<input type="checkbox" class="form-control chks" name="fdescargar[]" value="'.$row['cve'].'"';
		if($_POST['checkall'] == 'true') $chk .= ' checked';
		$chk .= '>';
		if($row['estatus'] != 'C' && $row['respuesta1'] == ''){
			$extras .= '&nbsp;<i class="fas fa-cloud-upload-alt fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="timbrar('.$row['cve'].')" title="Timbrar"></i>';
			$chk = '';
		}
		if($row['respuesta1'] != ''){
			$extras .= '&nbsp;<i class="fas fa-file-code fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="atcr(\'cfdi/comprobantes/cfdinc_'.$row['plaza'].'_'.$row['cve'].'.xml\',\'_blank\',\'\','.$row['cve'].')" title="XML"></i>
			&nbsp;&nbsp;<i class="fas fa-mail-bulk fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="reenviarcorreo('.$row['cve'].')" title="Reenviar Correo"></i>';
		}
		if($row['estatus'] != 'C' && $nivelUsuario>2){
			$extras .= '&nbsp;<i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="cancelarr('.$row['cve'].')" title="Cancelar"></i>';
		}
		
		$resultado['data'][] = array(
			$row['serie'].' '.$row['folio'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['nomcliente']),
			utf8_encode($row['nomtipopagofac']),
			number_format($row['total'],2),
			$row['nomestatus'],
			 $row['usuario'],
			'<i class="fas fa-print fa-sm fa-fw mr-2 text-primary" style="cursor:pointer;" onClick="atcr(\'notacredito.php\',\'_blank\',100,'.$row['cve'].')" title="Imprimir"></i>'.$extras,
			$chk
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$selectengomados = '<option value="0" precio="0">Seleccione</option>';
	$res = mysql_query("SELECT a.cve, a.nombre, b.precio FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.venta=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
	while($row = mysql_fetch_assoc($res)){
		$selectengomados .= '<option value="'.$row['cve'].'" precio="'.$row['precio'].'">'.utf8_encode($row['nombre']).'</option>';
	}
?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<button type="button" class="btn btn-success" onClick="atcr('notacredito.php','',2,0);">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<button type="button" class="btn btn-primary" onClick="atcr('notacredito.php','',0,0);">Volver</button>
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
					<div class="form-group col-sm-6">
						<label for="cliente">Cliente</label>
			             <select name="cliente" class="form-control" data-container="body" data-live-search="true" title="Cliente" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="cliente"><option value="">Seleccione</option>
			            <?php
			            $res = mysql_query("SELECT cve, nombre, usocfdi FROM clientes WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'" usocfdi="'.$row['usocfdi'].'">'.$row['nombre'].'</option>';
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
			            <option value="1">Pago en parcialidades o diferidos</option>
			        	</select>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="tipo_pago">Tipo de Pago</label>
			            <select id="tipo_pago" name="tipo_pago" class="form-control">
			            <?php
			            $res = mysql_query("SELECT cve, nombre FROM tipos_pago_factura  ORDER BY cve");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'">'.utf8_encode($row['nombre']).'</option>';
			            }
			            ?>
			        	</select>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-2">
						<label for="tipo_documento_origen">Engomado</label>
			            <select id="tipo_documento_origen" name="tipo_documento_origen" class="form-control" onChange="mostrar_engomados()">
			            <option value="0">No</option>
			            <option value="1" selected>Si</option>
			        	</select>
			        </div>
			    </div>
			    <div class="form-row">
			    	<div class="form-group col-sm-6">
						<label for="observaciones">Observaciones</label>
			            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
			        </div>
			        
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="tipo_relacion">Tipo Relaci&oacute;n</label>
			            <select id="tipo_relacion" name="tipo_relacion" class="form-control"><option value="">Ninguna</option>
			            <?php
			            $res = mysql_query("SELECT cve, nombre FROM tiporelacion_sat  ORDER BY cve");
			            while($row = mysql_fetch_assoc($res)){
			            	echo '<option value="'.$row['cve'].'">'.utf8_encode($row['nombre']).'</option>';
			            }
			            ?>
			        	</select>
			        </div>
			        <div class="form-group col-sm-5">
						<label for="uuidsrelacionados">CFDIS RELACIONADOS</label>
			            <input type="text" class="form-control" id="uuidsrelacionados" name="uuidsrelacionados" value="">
			        </div>
			    </div>
			    <div class="form-row">
					<div class="form-group col-sm-3">
						<label for="subtotal">Subtotal</label>
			            <input type="number" class="form-control" id="subtotal" name="subtotal" placeholder="Subtotal" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="sucursal">IVA</label>
			            <input type="iva" class="form-control" id="iva" name="iva" placeholder="IVA" readOnly>
			        </div>
			        <div class="form-group col-sm-3">
						<label for="total">Total</label>
			            <input type="number" class="form-control" id="total" name="total" placeholder="Total" readOnly>
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
					<div class="col-sm-1" style="text-align: center;"><label>C&oacute;digo SAT</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Cantidad</label></div>
					<div class="col-sm-1 engomadosdetalle" style="text-align: center;"><label>Engomado</label></div>
					<div class="col-sm-5" style="text-align: center;"><label>Descripcion</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Precio</label></div>
					<div class="col-sm-2" style="text-align: center;"><label>Importe</label></div>
					<div class="col-sm-1" style="text-align: center;"><label>Borrar</label></div>
				</div>
				<?php
					$i=0;
				?>
					<div class="form-row">
						<div class="col-sm-1" style="text-align: center;"><select id="claveproductosat_<?php echo $i;?>" name="claveproductosat[]" class="form-control"><option value="77121503">77121503</option><option value="80141600">80141600</option></select></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control cantidades" name="cantidad[]" value="" id="cantidad_<?php echo $i; ?>" onKeyUp="calcular()"></div>
						<div class="col-sm-1" style="text-align: center;"><select id="engomados_<?php echo $i;?>" name="engomados[]" class="form-control engomadosdetalle" onChange="traerPrecio(<?php echo $i;?>)"><?php echo $selectengomados;?></select></div>
						<div class="col-sm-5" style="text-align: center;"><input type="text" class="form-control conceptos" name="concepto[]" value="" id="concepto_<?php echo $i; ?>" readOnly></div>
						<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control precios" name="precio[]" value="" id="precio_<?php echo $i; ?>" onChange="calcular()"></div>
						<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[]" value="" id="importe_<?php echo $i; ?>" readOnly></div>
						<div class="col-sm-1" style="text-align: center;"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="$(this).parent().parent().remove();calcular();" title="Borrar"></i></div>
					</div>
				<?php
						$i++;
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
				<div class="col-sm-1" style="text-align: center;"><select id="claveproductosat_'+contador+'" name="claveproductosat[]" class="form-control"><option value="77121503">77121503</option><option value="80141600">80141600</option></select></div>\
				<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control cantidades" name="cantidad[]" value="" id="cantidad_'+contador+'" onKeyUp="calcular()"></div>\
				<div class="col-sm-1" style="text-align: center;"><select id="engomados_'+contador+'" name="engomados[]" class="form-control engomadosdetalle" onChange="traerPrecio('+contador+')"><?php echo $selectengomados;?></select></div>\
				<div class="col-sm-5" style="text-align: center;"><input type="text" class="form-control conceptos" name="concepto[]" value="" id="concepto_'+contador+'" readOnly></div>\
				<div class="col-sm-1" style="text-align: center;"><input type="number" class="form-control precios" name="precio[]" value="" id="precio_'+contador+'" onChange="calcular()"></div>\
				<div class="col-sm-2" style="text-align: center;"><input type="number" class="form-control importes" name="importe[]" value="" id="importe_'+contador+'" readOnly></div>\
				<div class="col-sm-1" style="text-align: center;"><i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="$(this).parent().parent().remove();calcular();" title="Borrar"></i></div>\
			</div>');
		contador++;
	    $('#contadorproducto').val(contador);
	}

	function calcular(){
		var subt = 0;
		var tot = 0;
		var iv = 0;
		$('.cantidades').each(function(){
			div = $(this).parent().parent();
			importe = this.value*div.find('.precios').val();
			subt += importe/1;
			div.find('.importes').val(importe.toFixed(2));
		});
		$('#subtotal').val(subt.toFixed(2));
		iv = subt*0.16;
		$('#iva').val(iv.toFixed(2));
		tot = iv/1 + subt/1;
		$('#total').val(tot.toFixed(2));
	}

	function traerPrecio(linea){
		var precio = $('#engomados_'+linea).find('option:selected').attr('precio')/1.16;
		precio = precio.toFixed(2);
		$('#precio_'+linea).val(precio);
		var concepto = $('#engomados_'+linea).find('option:selected').html();
		$('#concepto_'+linea).val(concepto);
		calcular();
	}

	function mostrar_engomados(){
		if ($('#tipo_documento_origen').val() != '1'){
			$('.conceptos').removeAttr('readOnly');
			$('.engomadosdetalle').parents('div:first').hide();
		}
		else{
			$('.engomadosdetalle').parents('div:first').show();
			$('.conceptos').attr('readOnly', 'readOnly');
		}
	}
</script>
<?php
}


if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');

	if(trim($_POST['cliente'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el cliente');
	}
	elseif($_POST['total']==0){
		$resultado = array('error' => 1, 'mensaje' => 'El total de la nota de credito debe de ser mayor a cero');	
	}


	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$Cliente = mysql_fetch_assoc(mysql_query("SELECT email FROM clientes WHERE cve='{$_POST['cliente']}'"));
		$emailenvio = $Cliente['email'];
		$resplaza = mysql_query("SELECT * FROM plazas WHERE cve='{$_POST['cveplaza']}'");
		$rowplaza = mysql_fetch_array($resplaza);
		$res = mysql_query("SELECT serie,folio_inicial FROM foliosiniciales WHERE plaza='{$_POST['cveplaza']}' AND tipo=0 AND tipodocumento=2");
		$row = mysql_fetch_array($res);
		
		$res1 = mysql_query("SELECT IFNULL(MAX(folio+1),1) FROM notascredito WHERE plaza='{$_POST['cveplaza']}' AND serie='{$row['serie']}'");
		$row1 = mysql_fetch_array($res1);
		if($row['folio_inicial']<$row1[0]){
			$row['folio_inicial'] = $row1[0];
		}
		$insert = "INSERT notascredito SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$_POST['cliente']}', tipo_pago='{$_POST['tipo_pago']}', forma_pago='{$_POST['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_relacion='{$_POST['tipo_relacion']}', uuidsrelacionados='{$_POST['uuidsrelacionados']}'";
		while(!$resinsert=mysql_query($insert)){
			$row['folio_inicial']++;
			$insert = "INSERT notascredito SET plaza='{$_POST['cveplaza']}', serie='{$row['serie']}', folio='{$row['folio_inicial']}', fecha=CURDATE(), fecha_creacion=CURDATE(), hora=CURTIME(), obs='".addslashes($_POST['obs'])."', cliente='{$_POST['cliente']}', tipo_pago='{$_POST['tipo_pago']}', forma_pago='{$_POST['forma_pago']}', usuario='{$_POST['cveusuario']}', tipo_relacion='{$_POST['tipo_relacion']}', uuidsrelacionados='{$_POST['uuidsrelacionados']}'";
		}
		
		$cvefact=mysql_insert_id();
		$documento=array();
		require_once("nusoap/nusoap.php");
		$fserie=$row['serie'];
		$ffolio=$row['folio_inicial'];
		
		$i=0;
		foreach($_POST['cant'] as $k=>$v){
			if($v>0){
				$claveprod = ($_POST['claveprodsat'][$k] != '') ? $_POST['claveprodsat'][$k] : '77121503';
				if(trim($_POST['unidad'][$k])=="") $_POST['unidad'][$k] = "Unidad de servicio";
				$importe_iva=round($_POST['importe'][$k]*$_POST['ivap'][$k]/100,2);
				mysql_query("INSERT notascreditomov SET plaza='".$_POST['cveplaza']."',cvefact='$cvefact',cantidad='".$v."',concepto='".$_POST['concepto'][$k]."',
				precio='".$_POST['precio'][$k]."',importe='".$_POST['importe'][$k]."',iva='".$_POST['ivap'][$k]."',importe_iva='$importe_iva',unidad='".$_POST['unidad'][$k]."',
				engomado='".$_POST['engomado_id'][$k]."',claveprodsat='{$claveprod}',claveunidadsat='E48'");
				$documento['conceptos'][$i]['cantidad']=$v;
				$documento['conceptos'][$i]['unidad']=$_POST['unidad'][$k];
				$documento['conceptos'][$i]['descripcion']=$_POST['concepto'][$k];
				$documento['conceptos'][$i]['valorUnitario']=$_POST['precio'][$k];
				$documento['conceptos'][$i]['importe']=$_POST['importe'][$k];
				$documento['conceptos'][$i]['importe_iva']=$importe_iva;
				$i++;
			}
		}

		mysql_query("UPDATE notascredito SET subtotal='{$_POST['subtotal']}', iva='{$_POST['iva']}', total='{$_POST['total']}' WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");

		$documento = genera_arreglo_facturacion($_POST['cveplaza'], $cvefact, 'E');
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
							mysql_query("UPDATE notascredito SET respuesta1='{$respuesta['uuid']}', seriecertificado='{$respuesta['seriecertificado']}', sellodocumento='{$respuesta['sellodocumento']}', uuid='{$respuesta['uuid']}', seriecertificadosat='{$respuesta['seriecertificadosat']}', sellotimbre='{$respuesta['sellotimbre']}', cadenaoriginal='{$respuesta['cadenaoriginal']}', fechatimbre='".substr($respuesta['fechatimbre'],0,10)." ".substr($respuesta['fechatimbre'],-8)."'
							WHERE plaza='{$_POST['cveplaza']}' AND cve={$cvefact}");
							//Tomar la informacion de Retorno
							$dir="cfdi/comprobantes/";
							$dir2="cfdinc/";
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
								generaFacturaPdf($_POST['cveplaza'],$cvefact, 0, 2);
								if($emailenvio!=""){
									$mail = obtener_mail();		
									$mail->FromName = "Verificentros Plaza ".$rowempresa['nombre'];
									$mail->Subject = "Nota Credito ".$fserie." ".$ffolio;
									$mail->Body = "Nota Credito ".$fserie." ".$ffolio;
									$correos = explode(",",trim($emailenvio));
									foreach($correos as $correo){
										$mail->AddAddress(trim($correo));
									}
									$mail->AddAttachment("cfdi/comprobantes/nc_{$_POST['cveplaza']}_{$cvefact}.pdf", "Nota Credito {$fserie} {$ffolio}.pdf");
									$mail->AddAttachment("cfdi/comprobantes/cfdinc_{$_POST['cveplaza']}_{$cvefact}.xml", "Nota Credito {$fserie} {$ffolio}.xml");
									$mail->Send();
								}	
								
								@unlink("cfdi/comprobantes/nc_{$_POST['cveplaza']}_{$cvefact}.pdf");
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

		echo '<script>atcr("notacredito.php","",0,0);</script>';
	}
	

	exit();
}

?>