<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE devolucion_certificado SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='{$_POST['motivocancelacion']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['devolucion']}'");
	$res = mysql_query("SELECT ticket FROM devolucion_certificado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['devolucion']}'");
	$row = mysql_fetch_array($res);
	mysql_query("UPDATE cobro_engomado SET estatus='A' WHERE plaza = '{$_POST['cveplaza']}' AND cve = '{$row['ticket']}'");

	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="cvecancelar" value="">
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
					            <textarea type="text" class="form-control" rows="3" id="motivocancelacion"></textarea>
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="cancelarcve();">Cancelar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin">
        	</div>
        </div>
		
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Ticket</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedaticket" name="busquedaticket" placeholder="Ticket">
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Plantilla" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM certificados WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['usuario'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedausuario").selectpicker();	
				</script>
        	</div>
        </div>
        <div class="form-group row">
        	<label class="col-sm-2 col-form-label">Placa</label>
			<div class="col-sm-4">
            	<input type="number" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa">
        	</div>
        </div>

        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('devolucion_certificados.php','',1,0);">
	            	Nuevo
	        	</button>
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
				<th>Ticket</th>
				<th>Placa</th>
				<th>Tipo de Certificado en Venta</th>
				<th>Monto en Venta</th>
				<th>Devoluci&oacute;n</th>
				<th>Motivo</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Ticket</th>
				<th>Placa</th>
				<th>Tipo de Certificado en Venta</th>
				<th>Monto en Venta</th>
				<th>Devoluci&oacute;n<br><span id="tdevolucion" style="text-align: right;"></span></th>
				<th>Motivo</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'devolucion_certificados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedaplaca": $("#busquedaplaca").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedaticket": $("#busquedaticket").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tdevolucion').html(json.devolucion);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-right", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-right", "targets": 5 },
        	{ className: "dt-head-center dt-body-right", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-left", "targets": 8 },
        	{ className: "dt-head-center dt-body-center", "targets": 9 },
        	{ orderable: false, "targets": 9 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedaplaca": $("#busquedaplaca").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedaticket": $("#busquedaticket").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarcve(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'devolucion_certificados.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					devolucion: $('#cvecancelar').val(),
					motivocancelacion: $("#motivocancelacion").val(),
					cveplaza: $('#cveplaza').val(),
					cveusuario: $('#cveusuario').val()
				},
				success: function(data) {
					waitingDialog.hide();
					sweetAlert('', data.mensaje, data.tipo);
					buscar();
				}
			});
		}
	}

	function precancelarcve(cve){
		$('#cvecancelar').val(cve);
		$("#motivocancelacion").val('');
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
	$columnas=array("a.cve", "CONCAT(a.fecha,' ',a.hora)", "a.ticket", "a.placa", "b.nombre", 'a.monto_venta', 'a.devolucion', 'a.motivo', 'c.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve DESC";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}


	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
		if($_POST['busquedafechaini']!=''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
		}

		if($_POST['busquedaplaca']!=''){
			$where .= " AND a.placa = '{$_POST['busquedaplaca']}'";
		}

		if($_POST['busquedaticket']!=''){
			$where .= " AND a.ticket = '{$_POST['busquedaticket']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}



	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C',a.devolucion,0)) as devolucion FROM devolucion_certificado a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'devolucion' => $registros['devolucion']
	);
	$res = mysql_query("SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fechahora, a.placa, a.ticket, b.nombre as nomengomado, a.monto_venta, IF(a.estatus!='C',a.devolucion,0) as devolucion, a.motivo, c.usuario, a.estatus FROM devolucion_certificado a INNER JOIN engomados b ON b.cve = a.engomado INNER JOIN usuarios c ON c.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarcve('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'devolucion_certificados.php\',\'_blank\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
		$resultado['data'][] = array(
			($row['cve']),
			mostrar_fechas(substr($row['fechahora'],0,10)).' '.substr($row['fechahora'],11),
			$row['ticket'],
			utf8_encode($row['placa']),
			utf8_encode($row['nomengomado']),
			number_format($row['monto_venta'],2),
			number_format($row['devolucion'],2),
			utf8_encode($row['motivo']),
			utf8_encode($row['usuario']),
			$dropmenu
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res=mysql_query("SELECT constancia_rechazo FROM plazas WHERE cve='".$_POST['cveplaza']."'");
	$row=mysql_fetch_array($res);

	$constancia_rechazo = $row['constancia_rechazo'];

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('devolucion_certificados.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('devolucion_certificados.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="placa">Placa</label>
			            <input type="text" class="form-control" id="fplaca" value="" autocomplete="off" onKeyUp="this.value = this.value.toUpperCase();">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="ticket">Ticket</label>
			            <input type="text" class="form-control" id="fticket" value="" autocomplete="off" onKeyUp="if(event.keyCode==13){ traeTicket();}">
			            <input type="hidden" class="form-control" id="ticket" value="" name="ticket">
			            <input type="hidden" class="form-control" id="placa" value="" name="placa">
			        </div>
	      		</div>
	      		<div class="form-row">
			        <div class="form-group col-sm-2">
						<label for="monto_venta">Monto Venta</label>
			           	<input type="text" class="form-control" id="monto_venta" name="monto_venta" value="" readOnly>
			        </div>
			        <div class="form-group col-sm-6">
						<label for="nomengomado">Tipo de Certificado</label>
			           	<input type="text" class="form-control" id="nomengomado" value="" readOnly>
			           	<input type="hidden" class="form-control" id="engomado" name="engomado" value="" readOnly>
			        </div>
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-2">
						<label for="devolucion">Devoluci&oacute;n</label>
			         	<input type="number" class="form-control" id="devolucion" name="devolucion" value="">
			        </div>
			        <div class="form-group col-sm-6">
						<label for="motivo">Motivo</label>
			          	<textarea name="motivo" id="motivo" class="form-control" rows="3"></textarea>
			        </div>
			    </div>
	     	
	    	</div>
	  	</div>
	</div>
</div>


<script>

function traeTicket(){
	if($('#fticket').val() != '' && $('#fplaca').val() != ''){
		$.ajax({
			url: 'devolucion_certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 21,
				ticket: $('#fticket').val(),
				placa: $('#fplaca').val(),
				cveplaza: $('#cveplaza').val(),
			},
			success: function(data) {
				$('#monto_venta').val(data.monto);
				$("#engomado").val(data.engomado);
				$("#nomengomado").val(data.nomengomado);
				$("#placa").val(data.placa);
				$("#ticket").val(data.ticket);
				if(data.mensaje != ''){
					alert(data.mensaje);
				}
			}
		});
	}
}


</script>

<?php
}

if($_POST['cmd']==21){
	$resultado=array('mensaje' => '', 'ticket' => '', 'placa' => '', 'engomado' => '', 'nomengomado' => '', 'monto_venta' => '');

	$res = mysql_query("SELECT cve, placa, monto, estatus, tipo_venta, tipo_pago, engomado, factura, notacredito FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	if($row = mysql_fetch_assoc($res)){
		if($row['estatus']=='A'){
			if($row['tipo_pago'] != 1 && $row['tipo_pago'] != 5 && $row['tipo_pago'] != 7){
				$resultado['mensaje'] = 'La ticket solo puede ser devuelto con tipos de pago de contado o tarjeta de credito';
			}
			elseif($row['tipo_venta'] != 0){
				$resultado['mensaje'] = 'La ticket solo puede ser devuelto con importe';	
			}
			elseif($row['factura'] > 0 || $row['notacredito'] > 0){
				$resultado['mensaje'] = 'La ticket ya fue facturado';	
			}
			else{
				$res1 = mysql_query("SELECT cve FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND ticketpago = '{$_POST['ticket']}' AND estatus!='C'");
				if($row1 = mysql_fetch_assoc($res1)){
					$resultado['mensaje'] = 'La ticket tiene intentos';
				}
				else{
					$res1 = mysql_query("SELECT cve FROM devolucion_certificado WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['ticket']}' AND estatus != 'C'");
					if ($row1 = mysql_fetch_assoc($res1)){
						$resultado['mensaje'] = 'La ticket ya se le hizo su devolucion';
					}
					else{
						$res1 = mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['ticket']}' AND estatus!='C'");
						if($row1 = mysql_fetch_assoc($res1)){
							$resultado['mensaje'] = 'El ticket ya tiene entregado su certificado';
						}
						else{
							$engomado = mysql_fetch_assoc(mysql_query("SELECT nombre FROM engomados WHERE cve='{$row['engomado']}'"));
							$resultado['ticket'] = $row['cve'];
							$resultado['placa'] = $row['placa'];
							$resultado['engomado'] = $row['engomado'];
							$resultado['nomengomado'] = $engomado['nombre'];
							$resultado['monto'] = $row['monto'];

						}
					}
				}
			}
		}
		else{
			$resultado['mensaje'] = 'El ticket no esta activo';
		}
	}
	else{
		$resultado['mensaje'] = utf8_encode('No se encontró el ticket');
	}

	echo json_encode($resultado);
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['placa'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la placa');
	}
	elseif(trim($_POST['ticket']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el ticket');
	}
	elseif(($_POST['devolucion']/1) <= 0){
		$resultado = array('error' => 1, 'mensaje' => 'La devolución debe de ser mayor a cero');
	}
	elseif(($_POST['devolucion']/1) > ($_POST['monto_venta']/1)){
		$resultado = array('error' => 1, 'mensaje' => 'La devolución no puede ser mayor al monto de la venta');
	}
	elseif(trim($_POST['motivo']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el motivo de la devolución');
	}
	
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{
		$res = mysql_query("SELECT cve FROM devolucion_certificado WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['ticket']}' AND ticket > 0 AND estatus!='C'");
		if(mysql_num_rows($res)==0){
			$res = mysql_query("SELECT engomado FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}' AND estatus!='C' ORDER BY cve DESC LIMIT 1");
			if(mysql_num_rows($res)>0 || ($_POST['cveusuario']==1 && $_POST['ticket']==0)){
				$insert = " INSERT devolucion_certificado 
								SET 
								plaza = '{$_POST['cveplaza']}', fecha=CURDATE(), hora=CURTIME(),
								placa='{$_POST['placa']}',engomado='{$_POST['engomado']}',certificado='',
								usuario='{$_POST['cveusuario']}', estatus='A',ticket='{$_POST['ticket']}', devolucion='{$_POST['devolucion']}',
								monto_venta='{$_POST['monto_venta']}', motivo='{$_POST['motivo']}'";
				mysql_query($insert);
				$cvedevolucion=mysql_insert_id();
				mysql_query("UPDATE cobro_engomado SET estatus='D' WHERE plaza = '{$_POST['cveplaza']}' AND cve = '{$_POST['ticket']}'");
			}
		}

		echo '<script>$("#contenedorprincipal").html("");atcr("devolucion_certificados.php","",0,"");</script>';
	}
}
?>