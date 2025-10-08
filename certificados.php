<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_POST['cmd']==36){
	$resultado = array('mensaje' => 'Se edito exitosamente', 'tipo'=>'success');
	mysql_query("UPDATE certificados SET fecha='{$_POST['fecha']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['entrega']}'");
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE certificados SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='{$_POST['motivocancelacion']}' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['entrega']}'");

	$res = mysql_query("SELECT * FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['entrega']}'");
	$row = mysql_fetch_array($res);
	mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=0 WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$row['engomado']}' AND a.estatus!='C' AND b.folio='".intval($row['certificado'])."' AND b.estatus=1");
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
<div id="modalCambios" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Cambios</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodypago">
				<div class="row">
					<div class="col-xl-12 col-lg-12 col-md-12">
						<div class="form-row">
					        <div class="form-group col-sm-5">
								<label for="folioentrega">Folio Entrega</label>
					            <input type="text" class="form-control" id="folioentrega" value="" readOnly>
					        </div>
					    </div>
					    <div class="form-row">
					        <div class="form-group col-sm-5">
								<label for="fechaentrega">Fecha</label>
					            <input type="date" class="form-control" id="fechaentrega" value="">
					        </div>
					    </div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" onClick="guardar_cambios();">Guardar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div class="row justify-content-center">
	<div class="col-xl-7 col-lg-7 col-md-7">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Fecha Inicio</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio" value="<?php echo date('Y-m-d');?>">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
		
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select name="busquedatipocertificado" id="busquedatipocertificado" class="form-control"><option value="">Todos</option>
            	<?php
            	$tipo_certificado = array();
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
					$tipo_certificado[$row1['cve']] = $row1['nombre'];
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
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
            	<input type="text" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa">
        	</div>
        </div>

        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('certificados.php','',1,0);">
	            	Nuevo
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('certificados.php','_blank',100,0);">
	            	Excel
	        	</button>
        	</div>
        </div>
    </div>
    <div class="col-xl-5 col-lg-5 col-md-5">
    	<div class="form-group row">
				<label class="col-sm-3 col-form-label"><b>Tipo</b></label>
				<label class="col-sm-3 col-form-label"><b>Activos</b></label>
				<label class="col-sm-3 col-form-label"><b>Cancelados</b></label>
				<label class="col-sm-3 col-form-label"><b>Totales</b></label>
      </div>
    	<?php foreach($tipo_certificado as $id => $nombre){ ?>
    	<div class="form-group row">
				<label class="col-sm-3 col-form-label"><b><?php echo utf8_encode($nombre);?></b></label>
				<label class="col-sm-3 col-form-label ccantidades" id="cant_<?php echo $id;?>">0</label>
				<label class="col-sm-3 col-form-label ccantidades" id="cantc_<?php echo $id;?>">0</label>
				<label class="col-sm-3 col-form-label ccantidades" id="cantt_<?php echo $id;?>">0</label>
      </div>
      <?php } ?>
      <div class="form-group row">
				<label class="col-sm-3 col-form-label"><b>TOTAL</b></label>
				<label class="col-sm-3 col-form-label ccantidades" id="cant_0">0</label>
				<label class="col-sm-3 col-form-label ccantidades" id="cantc_0">0</label>
				<label class="col-sm-3 col-form-label ccantidades" id="cantt_0">0</label>
      </div>
    </div>
</div>

<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Placa</th>
				<th>Ticket</th>
				<th>Fecha Ticket</th>
				<th>Tipo de Certificado</th>
				<th>Certificado</th>
				<th>A&ntilde;o de Certificaci&oacute;n</th>
				<th>Tecnico</th>
				<th>Linea</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Placa</th>
				<th>Ticket</th>
				<th>Fecha Ticket</th>
				<th>Tipo de Certificado</th>
				<th>Certificado</th>
				<th>A&ntilde;o de Certificaci&oacute;n</th>
				<th>Tecnico</th>
				<th>Linea</th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'certificados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedaplaca": $("#busquedaplaca").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedatipocertificado": $("#busquedatipocertificado").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('.ccantidades').each(function(){
        			$(this).html('0');
        		});
        		var cantidades = JSON.parse(json.cantidades);
        		$.each(cantidades, function(i, item) {
						    $('#cant_'+item.engomado).html(item.cantidad);
						    $('#cantc_'+item.engomado).html(item.cantidad_cancelados);
						    $('#cantt_'+item.engomado).html(item.cantidad_totales);
						});
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ orderable: false, "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-right", "targets": 4 },
        	{ className: "dt-head-center dt-body-center", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-left", "targets": 8 },
        	{ className: "dt-head-center dt-body-left", "targets": 9 },
        	{ className: "dt-head-center dt-body-left", "targets": 10 },
        	{ className: "dt-head-center dt-body-left", "targets": 11 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedaplaca": $("#busquedaplaca").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedatipocertificado": $("#busquedatipocertificado").val(),
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
				url: 'certificados.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					entrega: $('#cvecancelar').val(),
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

	function cambiar_entrega(folio, fecha){
		$("#folioentrega").val(folio);
		$("#fechaentrega").val(fecha);
		$('#modalCambios').modal('show');		
	}

	function guardar_cambios(){
		$('#modalCambios').modal('hide');
		waitingDialog.show();
		$.ajax({
			url: 'certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 36,
				entrega: $('#folioentrega').val(),
				fecha: $("#fechaentrega").val(),
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

	$("#modalCambios").modal({
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
	$columnas=array('', "a.cve", "CONCAT(a.fecha,' ',a.hora)", "a.placa", "a.ticket", "CONCAT(b.fecha,' ',b.hora)", 'c.nombre', "IF(a.estatus='C','',a.certificado)", 'd.nombre', 'e.nombre', 'f.nombre', 'g.nombre', 'h.nombre', 'j.nombre', 'k.nombre', 'i.usuario');

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.cve";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}


	$where = " WHERE a.plaza='{$_POST['cveplaza']}'";
	$where1 = " WHERE plaza='{$_POST['cveplaza']}'";
		if($_POST['busquedafechaini']!=''){
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']}'";
			$where1 .= " AND fecha >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']}'";
			$where1 .= " AND fecha <= '{$_POST['busquedafechafin']}'";
		}

		if($_POST['busquedaplaca']!=''){
			$where .= " AND a.placa = '{$_POST['busquedaplaca']}'";
		}

		if($_POST['busquedatipocertificado']!=''){
			$where .= " AND a.engomado = '{$_POST['busquedatipocertificado']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

		$cantidades = array();
		$cantidad_cancelados = array();
		$res = mysql_query("SELECT engomado, COUNT(cve) as cantidad FROM certificados_cancelados{$where1} GROUP BY engomado");
		while($row = mysql_fetch_assoc($res)){
			$cantidad_cancelados[$row['engomado']] = $row['cantidad'];
		}

		$res = mysql_query("SELECT engomado, SUM(IF(estatus!='C',1,0)) as activos, SUM(IF(estatus='C',1,0)) as cancelados, COUNT(cve) as cantidad FROM certificados{$where1} GROUP BY engomado");
		$activos = $cancelados = $totales = 0;
		while($row = mysql_fetch_assoc($res)){
			$row['cantidad'] = $row['activos'] + $cantidad_cancelados[$row['engomado']];
			$cantidades[] = array('engomado' => $row['engomado'], 'cantidad' => number_format($row['activos'],0), 'cantidad_cancelados' => number_format($cantidad_cancelados[$row['engomado']],0), 'cantidad_totales' => number_format($row['cantidad'],0));
			$activos += $row['activos'];
			$cancelados += $cantidad_cancelados[$row['engomado']];
			$totales += $row['cantidad'];
		}
		$cantidades[] = array('engomado' => 0, 'cantidad' => number_format($activos,0), 'cantidad_cancelados' => number_format($cancelados,0), 'cantidad_totales' => number_format($totales,0));




	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM certificados a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'cantidades' => json_encode($cantidades)
	);
	$res = mysql_query("SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fechahora, a.placa, a.ticket, CONCAT(b.fecha,' ',b.hora) as fechahoraticket, c.nombre as nomengomado, IF(a.estatus='C','',a.certificado) as certificado, d.nombre as nomanio, g.nombre as nomtecnico, h.nombre as nomlinea, i.usuario, a.estatus FROM certificados a INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket INNER JOIN engomados c ON c.cve = a.engomado INNER JOIN anios_certificados d ON d.cve = a.anio INNER JOIN tecnicos g ON g.plaza = a.plaza AND g.cve = a.tecnico INNER JOIN cat_lineas h ON h.cve = a.linea INNER JOIN usuarios i ON i.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	$usuarios_editar=array(1,10);
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarcve('.$row['cve'].')">Cancelar</a>';
		}

		if ($row['estatus'] == 'A' && in_array($_POST['cveusuario'], $usuarios_editar)) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cambiar_entrega('.$row['cve'].',\''.substr($row['fechahora'],0,10).'\')">Editar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
		$resultado['data'][] = array(
			$dropmenu,
			($row['cve']),
			mostrar_fechas(substr($row['fechahora'],0,10)).' '.substr($row['fechahora'],11),
			$row['placa'],
			utf8_encode($row['ticket']),
			mostrar_fechas(substr($row['fechahoraticket'],0,10)).' '.substr($row['fechahoraticket'],11),
			utf8_encode($row['nomengomado']),
			utf8_encode($row['certificado']),
			utf8_encode($row['nomanio']),
			utf8_encode($row['nomtecnico']),
			utf8_encode($row['nomlinea']),
			utf8_encode($row['usuario'])
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
		<button type="button" class="btn btn-success" onClick="atcr('certificados.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('certificados.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="placa">Placa</label>
	           <input type="text" class="form-control" id="placa" value="" autocomplete="off" onKeyUp="this.value = $.trim(this.value.toUpperCase());" name="placa">
	        </div>
	        <div class="form-group col-sm-3">
						<label for="ticket">Ticket</label>
	           <input type="text" class="form-control" id="ticket" value="" autocomplete="off" onKeyUp="if(event.keyCode==13){ traeTicket();}" name="ticket">
	           <small style="color:RED">Dar enter para traer informaci&oacute;n</small>
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-2">
						<label for="fechaticket">Fecha Ticket</label>
	           <input type="text" class="form-control" id="fechaticket" value="" readOnly>
	        </div>
	        <div class="form-group col-sm-2">
						<label for="placaticket">Placa Ticket</label>
	           <input type="text" class="form-control" id="placaticket" value="" readOnly>
	        </div>
	      </div>
	     	<div class="form-row">
	        <div class="form-group col-sm-6">
	        	<label for="tecnico">Tecnico</label>
			      <select name="tecnico" class="form-control" data-container="body" data-live-search="true" title="Tecnico" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="tecnico"><option value="">Seleccione</option>
			      	<?php
	           		$res1 = mysql_query("SELECT cve, clave FROM tecnicos WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['clave']).'</option>';
								}
							?>
			      </select>
			      <script>
							$("#tecnico").selectpicker();	
						</script>
	        </div>
	        <div class="form-group col-sm-4">
						<label for="linea">Linea</label>
	          <select name="linea" id="linea" class="form-control"><option value="0">Seleccione</option>
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM cat_lineas WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-2" style="display: none;">
						<label for="certificado">Constancia Rechazo</label><br><input type="checkbox" id="constancia_rechazo" class="form-control" onClick="cambiar_check('constancia_rechazo');traeCertificado();" onChange="cambiar_check('constancia_rechazo');traeCertificado();">
						<input type="hidden" class="form-control" id="constancia_rechazo_h" name="constancia_rechazo" value="0">
	        </div>
	      	<div class="form-group col-sm-2">
						<label for="certificado">Certificado</label>
	          <input type="number" class="form-control" id="certificado" value="" name="certificado" onChange="traeCertificado();" onKeyUp="if(event.keyCode==13){ traeCertificado();}">
	        </div>
	      	<div class="form-group col-sm-3">
						<label for="engomado">Tipo de Certificado</label>
	          <select name="engomado" id="engomado" class="form-control"><option value="0">Seleccione</option></select>
	        </div>
	      </div>
	    </div>
	  </div>
	</div>
</div>


<script>
$('input[type=checkbox]').bootstrapToggle();
function traeTicket(){
	if($('#ticket').val() == ''){
		$('#fechaticket').val('');
		$('#placaticket').val('');
	}
	else{
		$.ajax({
			url: 'certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 21,
				ticket: $('#ticket').val(),
				cveplaza: $('#cveplaza').val(),
			},
			success: function(data) {
				$('#fechaticket').val(data.fecha);
				$("#placaticket").val(data.placa);
				if(data.mensaje != ''){
					alert(data.mensaje);
				}
			}
		});
	}
}


function traeTecnico(){
	if($('#no_tecnico').val() == ''){
		$('#tecnico').val('');
		$('#nom_tecnico').val('');
	}
	else{
		$.ajax({
			url: 'certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 20,
				no_tecnico: $('#no_tecnico').val(),
				cveplaza: $('#cveplaza').val(),
			},
			success: function(data) {
				$('#tecnico').val(data.tecnico);
				$("#nom_tecnico").val(data.nom_tecnico);
				if(data.mensaje != ''){
					alert(data.mensaje);
				}
			}
		});
	}
}

function traeCertificado(){
	if($('#certificado').val() == ''){
		$('#engomado').html('<option value="0">Seleccione</option>');
	}
	else{
		$.ajax({
			url: 'certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 22,
				certificado: $('#certificado').val(),
				ticket: $('#ticket').val(),
				cveplaza: $('#cveplaza').val(),
				constancia_rechazo: $('#constancia_rechazo_h').val()
			},
			success: function(data) {
				$('#engomado').html(data.engomado);
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


if($_POST['cmd']==20){
	$resultado=array('mensaje' => '', 'tecnico' => '', 'nom_tecnico' => '');

	$res = mysql_query("SELECT cve, nombre FROM tecnicos WHERE plaza='{$_POST['cveplaza']}' AND estatus='0' AND clave='{$_POST['no_tecnico']}'");
	if($row = mysql_fetch_assoc($res)){
		$resultado['tecnico'] = $row['cve'];
		$resultado['nom_tecnico'] = utf8_encode($row['nombre']);
	}
	else{
		$resultado['mensaje'] = utf8_encode('No se encontró el tecnico');
	}

	echo json_encode($resultado);
}

if($_POST['cmd']==21){
	$resultado=array('mensaje' => '', 'fecha' => '', 'placa' => '');

	$res = mysql_query("SELECT fecha, placa, estatus FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	if($row = mysql_fetch_assoc($res)){
		if($row['estatus']=='A'){
			$res1 = mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND ticket='{$_POST['ticket']}' AND estatus!='C'");
			if($row1 = mysql_fetch_assoc($res1)){
				$resultado['mensaje'] = 'El ticket ya fue entregado';
			}
			else{
				$resultado['fecha'] = $row['fecha'];
				$resultado['placa'] = $row['placa'];
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

if($_POST['cmd']==22){
	$resultado=array('mensaje' => '', 'engomado' => '<option value="0">Seleccione</option>');
	$res = mysql_query("SELECT engomado FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}'");
	if($Ticket = mysql_fetch_assoc($res)){
		$filtroengomado = " AND engomado NOT IN (20,22)";
		if($Ticket['engoamdo']!=1) $filtroengomado = " AND engomado = '".$Ticket[0]."'";
		if($_POST['constancia_rechazo']==1) $filtroengomado = " AND engomado = 19";
		$res = mysql_query("SELECT cve FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'{$filtroengomado}");
		if(mysql_num_rows($res)==0){
			$res = mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'{$filtroengomado}");
			if(mysql_num_rows($res)==0){
				if($_POST['constancia_rechazo']==1){
					$resultado['engomado'] = '<option value="19">RECHAZO</option>';
				}
				else{
					$res = mysql_query("SELECT a.engomado, b.estatus, b.tipo, a.anio, c.nombre FROM compra_certificados a 
					INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra 
					INNER JOIN engomados c ON c.cve = a.engomado
					WHERE a.plaza='{$_POST['cveplaza']}' AND a.estatus!='C' AND b.folio='".intval($_POST['certificado'])."' ORDER BY b.cve DESC LIMIT 1");
					if($row = mysql_fetch_array($res)){
							$resultado['engomado'] = '<option value="'.$row['engomado'].'">'.$row['nombre'].'</option>';
					}
					else{
						$resultado['mensaje'] = utf8_encode('El certificado no existe!');
					}
				}
			}
			else{
				$resultado['mensaje'] = utf8_encode('El certificado esta entregado');	
			}
		}
		else{
			$resultado['mensaje'] = utf8_encode('El certificado esta cancelado');
		}
	}
	else{
		$resultado['mensaje'] = utf8_encode('Necesita captura el ticket');
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
	elseif(trim($_POST['tecnico']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar al tecnico');
	}
	elseif(trim($_POST['linea']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la linea');
	}
	elseif(trim($_POST['certificado']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el certificado');
	}
	elseif(trim($_POST['engomado']) == '0'){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita cargar el certificado');
	}
	else{
		$precio = 0;
		$costo = 0;
		$res = mysql_query("SELECT cve, placa, engomado, monto_verificacion, monto, anio, estatus FROM cobro_engomado WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['ticket']}' AND estatus!='C'");
		if($Ticket = mysql_fetch_assoc($res)){
			if ($Ticket['estatus']!='A'){
				$resultado = array('error' => 1, 'mensaje' => 'El ticket no esta activo');
			}
			elseif($Ticket['placa'] == $_POST['placa']){
				$filtroengomado = " AND engomado NOT IN (21,22)";
				if($Ticket['engomado']!=20) $filtroengomado = " AND engomado = '".$Ticket['engomado']."'";
				if($_POST['constancia_rechazo']==1) $filtroengomado = " AND engomado = 19";
				$res = mysql_query("SELECT cve FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'{$filtroengomado}");
				if(mysql_num_rows($res)==0){
					$res = mysql_query("SELECT cve FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND certificado='".intval($_POST['certificado'])."' AND estatus!='C'{$filtroengomado}");
					if(mysql_num_rows($res)==0){
						if($_POST['constancia_rechazo']!=1){
							$res = mysql_query("SELECT a.engomado, b.estatus, b.tipo, a.anio, c.nombre, a.costo FROM compra_certificados a 
							INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra 
							INNER JOIN engomados c ON c.cve = a.engomado
							WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND a.estatus!='C' AND b.folio='".intval($_POST['certificado'])."' ORDER BY b.cve DESC LIMIT 1");
							if($row = mysql_fetch_array($res)){
								$costo = $row['costo'];
								if($row['engomado'] != 19){
									$row1 = mysql_fetch_assoc(mysql_query("SELECT precio FROM engomados_plazas WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$row['engomado']}'"));
									$res2 = mysql_query("SELECT SUM(a.recuperacion) as monto FROM recuperacion_certificado a WHERE a.plaza='{$_POST['cveplaza']}' AND a.estatus!='C' AND a.ticket = '{$_POST['ticket']}'");
									$row2 = mysql_fetch_array($res2);
									$Ticket['monto_verificacion']+=$row2['monto'];
									$precio = $row1['precio'];
									if($row1['precio'] > $Ticket['monto_verificacion'] && $Ticket['monto'] > 0){
										$resultado = array('error' => 1, 'mensaje' => utf8_encode('El certificado entregado es de mayor precio que el vendido'));
									}
								}
							}
							else{
								$resultado = array('error' => 1, 'mensaje' => utf8_encode('El certificado no existe!'));
							}
						}
					}
					else{
						$resultado = array('error' => 1, 'mensaje' => utf8_encode('El certificado esta entregado'));	
					}
				}
				else{
					$resultado = array('error' => 1, 'mensaje' => utf8_encode('El certificado esta cancelado'));
				}
			}
			else{
				$resultado = array('error' => 1, 'mensaje' => 'La placa capturada no es la misma que la placa del ticket');	
			}
		}
		else{
			$resultado = array('error' => 1, 'mensaje' => 'No se encontro el ticket');
		}
	}
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$costo_ambiente = 30;
		$anio = mysql_fetch_assoc(mysql_query("SELECT cve FROM anios_certificados WHERE venta=1 ORDER BY cve DESC LIMIT 1"));

		$insert = " INSERT certificados 
										SET 
										plaza = '{$_POST['cveplaza']}', fecha=CURDATE(), hora=CURTIME(), monto='{$precio}', placa='{$_POST['placa']}', engomado='{$_POST['engomado']}', certificado='{$_POST['certificado']}', anio='{$Ticket['anio']}', usuario='{$_POST['cveusuario']}', estatus='A', ticket='{$_POST['ticket']}', tecnico='{$_POST['tecnico']}', entregado='1', linea='{$_POST['linea']}', costo_ambiente='{$costo_ambiente}', costo='{$costo}', constancia_rechazo = '{$_POST['constancia_rechazo']}', tipo_vehiculo='{$_POST['tipo_vehiculo']}', tipo_prueba='{$_POST['tipo_prueba']}', problema_obdii='{$_POST['problema_obdii']}', modelo='{$_POST['modelo']}', marca='{$_POST['marca']}'";
		mysql_query($insert) or die(mysql_error());

		mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=1 WHERE a.plaza='{$_POST['cveplaza']}' AND a.engomado = '{$_POST['engomado']}' AND a.estatus!='C' AND b.folio='".intval($_POST['certificado'])."' AND b.estatus=0");

		echo '<script>$("#contenedorprincipal").html("");atcr("certificados.php","",0,"");</script>';
	}
}

if($_POST['cmd']==100){

	require_once('PHPExcel/Classes/PHPExcel.php');
	include 'PHPExcel/Classes/PHPExcel/Writer/Excel2007.php'; 
	$objPHPExcel = new PHPExcel(); 
	$filename = "reporteentregas.xlsx"; 

	header('Content-Type: application/vnd.ms-excel'); 
	header('Content-Disposition: attachment;filename="' . $filename . '"'); 
	header('Cache-Control: max-age=0'); 
	$F=$objPHPExcel->getActiveSheet(); 
	$Line = 1;
	$F->setCellValue('A'.$Line, 'Folio'); 
	$F->setCellValue('B'.$Line, 'Fecha'); 
	$F->setCellValue('C'.$Line, 'Placa'); 
	$F->setCellValue('D'.$Line, 'Ticket'); 
	$F->setCellValue('E'.$Line, 'Fecha Ticket'); 
	$F->setCellValue('F'.$Line, 'Tipo Certificado');
	$F->setCellValue('G'.$Line, 'Certificado');
	$F->setCellValue('H'.$Line, 'Año de Certificación');
	$F->setCellValue('I'.$Line, 'Tecnico');
	$F->setCellValue('J'.$Line, 'Linea');
	$F->setCellValue('K'.$Line, 'Usuario');


	$orderby = " ORDER BY a.cve DESC";
	

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

		if($_POST['busquedatipocertificado']!=''){
			$where .= " AND a.engomado = '{$_POST['busquedatipocertificado']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

	$res = mysql_query("SELECT a.cve, CONCAT(a.fecha,' ',a.hora) as fechahora, a.placa, a.ticket, CONCAT(b.fecha,' ',b.hora) as fechahoraticket, c.nombre as nomengomado, IF(a.estatus='C','',a.certificado) as certificado, d.nombre as nomanio, g.nombre as nomtecnico, h.nombre as nomlinea, i.usuario, a.estatus FROM certificados a INNER JOIN cobro_engomado b ON b.plaza = a.plaza AND b.cve = a.ticket INNER JOIN engomados c ON c.cve = a.engomado INNER JOIN anios_certificados d ON d.cve = a.anio INNER JOIN tecnicos g ON g.plaza = a.plaza AND g.cve = a.tecnico INNER JOIN cat_lineas h ON h.cve = a.linea INNER JOIN usuarios i ON i.cve = a.usuario{$where}{$orderby}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		
		++$Line; 
		
    if($row['estatus']=='C'){
    	$row['cve'].='(CANCELADO)';
    }

		$F->setCellValue('A'.$Line, $row['cve']); 
		$F->setCellValue('B'.$Line, mostrar_fechas(substr($row['fechahora'],0,10)).' '.substr($row['fechahora'],11)); 
		$F->setCellValue('C'.$Line, $row['placa']); 
		$F->setCellValue('D'.$Line, utf8_encode($row['ticket'])); 
		$F->setCellValue('E'.$Line, mostrar_fechas(substr($row['fechahoraticket'],0,10)).' '.substr($row['fechahoraticket'],11)); 
		$F->setCellValue('F'.$Line, utf8_encode($row['nomengomado']));
		$F->setCellValue('G'.$Line, utf8_encode($row['certificado']));
		$F->setCellValue('H'.$Line, utf8_encode($row['nomanio']));
		$F->setCellValue('I'.$Line, utf8_encode($row['nomtecnico']));
		$F->setCellValue('J'.$Line, utf8_encode($row['nomlinea']));
		$F->setCellValue('K'.$Line, utf8_encode($row['usuario']));
	}
	header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); 
	header('Content-Disposition: attachment;filename="'.$filename.'"'); 
	header('Cache-Control: max-age=0'); 

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007'); 
	$objWriter->save('php://output'); 
	exit; 
}
?>