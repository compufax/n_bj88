<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_POST['cmd']==35){
?>
<table class="table">
	  <thead>
	    <tr>
	      <th scope="col" style="text-align: center;">Certificado</th>
	      <th scope="col" style="text-align: center;">Usado</th>
	    </tr>
	  </thead>
	  <tbody>
<?php
	$Compra = mysql_fetch_assoc(mysql_query("SELECT engomado FROM compra_certificados WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['compra']}'"));
	$res = mysql_query("SELECT cve, folio FROM compra_certificados_detalle WHERE plaza='{$_POST['cveplaza']}' AND cvecompra='{$_POST['compra']}'");
	while($row = mysql_fetch_assoc($res)){
		$usado = '';
		$res1 = mysql_query("SELECT cve, fecha FROM certificados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$Compra['engomado']}' AND certificado='{$row['folio']}' AND estatus!='C'");
		if($row1 = mysql_fetch_assoc($res1)){
			$usado = 'Entrega #'.$row1['cve'].', Fecha: '.$row1['fecha'];
		}
		else{
			$res1 = mysql_query("SELECT cve, fecha FROM certificados_cancelados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$Compra['engomado']}' AND certificado='{$row['folio']}' AND estatus!='C'");
			if($row1 = mysql_fetch_assoc($res1)){
				$usado = 'Cancelaci&oacute;n #'.$row1['cve'].', Fecha: '.$row1['fecha'];
			}
		}
?>
		<tr>
	      <td align="center"><?php echo $row['folio'];?></td>
	      <td align="left"><?php echo $usado;?></td>
	    </tr>
<?php
	}

?>
</tbody>
</table>
<?php
	exit();
}
if($_POST['cmd']==34){
	
	$resultado = array('mensaje' => '', 'error'=>0);
	if($_POST['cveusuario']==1){
		echo json_encode($resultado);
		exit();
	}
	$res = mysql_query("SELECT * FROM compra_certificados WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['compra']}'");
	$row = mysql_fetch_assoc($res);
	$res1=mysql_query("SELECT COUNT(cve) as entregados FROM certificados WHERE plaza='{$row['plaza']}' AND engomado='{$row['engomado']}' AND certificado BETWEEN '{$row['folioini']}' AND '{$row['foliofin']}' AND estatus!='C'");
	$row1=mysql_fetch_assoc($res1);
	if($row1['entregados'] > 0){
		$resultado['mensaje'] = 'La compra tiene certificados utilizados';
		$resultado['error'] = 1;
	}
	else{
		$res1=mysql_query("SELECT COUNT(cve) as cancelados FROM certificados_cancelados WHERE plaza='{$row['plaza']}' AND engomado='{$row['engomado']}' AND certificado BETWEEN '{$row['folioini']}' AND '{$row['foliofin']}' AND estatus!='C'");
		$row1=mysql_fetch_assoc($res1);
		if($row1['cancelados']>0){
			$resultado['mensaje'] = 'La compra tiene certificados utilizados';
			$resultado['error'] = 1;
		}
	}
	echo json_encode($resultado);
	exit();
}

if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE compra_certificados SET estatus='C',usucan='{$_POST['cveusuario']}',fechacan=NOW(), obscan='".addslashes($_POST['motivocancelacion'])."' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['compra']}'");
	mysql_query("UPDATE compra_certificados_detalle SET estatus=3 WHERE plaza='{$_POST['plazausuario']}' AND cvecompra='{$_POST['compra']}' AND estatus=0");
	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="compracancelar" value="">
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
				<button type="button" class="btn btn-danger" onClick="cancelarcompra();">Cancelar</button>
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
		     </div>
		</div>
	</div>
</div>
<div id="modalVerFolios" class="modal fade" data-backdrop="static" data-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="staticBackdropLabel">Ver Folios</h5>
		        <!--<button type="button" class="close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true">&times;</span>
		        </button>-->
			</div>
			<div class="modal-body" id="bodyverfolios">
				
			</div>
			<div class="modal-footer">
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
			<label class="col-sm-2 col-form-label">Tipo de Certificado</label>
			<div class="col-sm-4">
            	<select name="busquedatipocertificado" id="busquedatipocertificado" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false">
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM compra_certificados WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
			<label class="col-sm-2 col-form-label">Semestre de Ceritificado</label>
			<div class="col-sm-4">
            	<select multiple name="busquedaanio[]" class="form-control" data-container="body" data-live-search="true" title="Semestre" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="busquedaanio">
            	<?php
            	$res1 = mysql_query("SELECT * FROM anios_certificados  ORDER BY nombre DESC");
				$primero = true;
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'"';
					if($primero) echo ' selected';
					echo '>'.$row1['nombre'].'</option>';
					$primero = false;
				}
				?>
            	</select>
            	<script>
					$("#busquedaanio").selectpicker();	
				</script>
        	</div>
        	<label class="col-sm-2 col-form-label">Mostrar</label>
			<div class="col-sm-4">
            	<select name="busquedamostrar" id="busquedamostrar" class="form-control">
	            	<option value="0">Todos</option>
	            	<option value="1" selected>Con pendientes de entrega</option>
	            	<option value="2">Sin pedientes de entrega</option>
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
	        		<button type="button" class="btn btn-primary" onClick="buscar();">
		            	Buscar
		        	</button>&nbsp;&nbsp;
		        	<button type="button" class="btn btn-success" onClick="atcr('compra_certificados.php','',1,0);">
		            	Nuevo
		        	</button>&nbsp;&nbsp;

        	</div>
        </div>
    </div>
</div>

<div class="table-responsive">
	<table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
    	<thead>
			<tr>
				<th>&nbsp;</th>
				<th>Consecutivo</th>
				<th>Folio Compra</th>
				<th>Fecha Compra</th>
				<th>Fecha</th>
				<th>Tipo de Certificado</th>
				<th>Folio Inicial</th>
				<th>Folio Final</th>
				<th>Cantidad<br><span class="tcantidad" style="text-align: right;"></span></th>
				<th>Semestre</th>
				<th>Remanente<br><span class="tremanente" style="text-align: right;"></span></th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Consecutivo</th>
				<th>Folio Compra</th>
				<th>Fecha Compra</th>
				<th>Fecha</th>
				<th>Tipo de Certificado</th>
				<th>Folio Inicial</th>
				<th>Folio Final</th>
				<th>Cantidad<br><span class="tcantidad" style="text-align: right;"></span></th>
				<th>Semestre</th>
				<th>Remanente<br><span class="tremanente" style="text-align: right;"></span></th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'compra_certificados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedaanio": $("#busquedaanio").val(),
        		"busquedamostrar": $("#busquedamostrar").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedatipocertificado": $("#busquedatipocertificado").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('.cantidad').html(json.cantidad);
        		$('.tremanten').html(json.remanente);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ orderable: false, "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-right", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-right", "targets": 6 },
        	{ className: "dt-head-center dt-body-right", "targets": 7 },
        	{ className: "dt-head-center dt-body-right", "targets": 8 },
        	{ className: "dt-head-center dt-body-left", "targets": 9 },
        	{ className: "dt-head-center dt-body-right", "targets": 10 },
        	{ className: "dt-head-center dt-body-left", "targets": 11 }
        	
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedaanio": $("#busquedaanio").val(),
    		"busquedamostrar": $("#busquedamostrar").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedatipocertificado": $("#busquedatipocertificado").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarcompra(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'compra_certificados.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					compra: $('#compracancelar').val(),
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

	function precancelarcompra(compra){
		waitingDialog.show();
		$.ajax({
			url: 'compra_certificados.php',
			type: "POST",
			dataType: 'json',
			data: {
				cmd: 34,
				compra: compra,
				cveplaza: $('#cveplaza').val(),
				cveusuario: $('#cveusuario').val()
			},
			success: function(data) {
				waitingDialog.hide();
				if (data.error == 1) {
					sweetAlert('', data.mensaje, 'warning');
				}
				else {
					$('#compracancelar').val(compra);
					$("#motivocancelacion").val('');
					$('#modalCancelacion').modal('show');
				}
			}
		});
	}

	function ver_folios(compra){
		waitingDialog.show();
		$.ajax({
			url: 'compra_certificados.php',
			type: "POST",
			data: {
				cmd: 35,
				compra: compra,
				cveplaza: $('#cveplaza').val(),
				cveusuario: $('#cveusuario').val()
			},
			success: function(data) {
				$('#bodyverfolios').html(data);
				waitingDialog.hide();
				$('#modalVerFolios').modal('show');
			}
		});
	}

	$("#modalCancelacion").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});

	$("#modalVerFolios").modal({
		backdrop: false,
		keyboard: false,
		show: false
	});
</script>
<?php
}
if($_POST['cmd']==10){
	$columnas=array("a.cve", "a.folio", "a.fecha_compra", "CONCAT(a.fecha, ' ',a.hora)", "b.nombre", 'a.folioini', 'a.foliofin', '(a.foliofin+1-a.folioini)', 'c.nombre', "IFNULL(d.remanente,0)", 'e.usuario');

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

	if($_POST['busquedamostrar']==1){
		$where .= " AND IF(a.estatus!='C', IFNULL(d.remanente,0), 0) > 0";
	}
	elseif($_POST['busquedamostrar']==2){
		$where .= " AND IF(a.estatus!='C', IFNULL(d.remanente,0), 0) = 0";
	}

	if($_POST['busquedatipocertificado']!=''){
		$where .= " AND a.engomado = '{$_POST['busquedatipocertificado']}'";
	}

	if(is_array($_POST['busquedaanio']) && count($_POST['busquedaanio'])>0){
		$busquedaanio = implode(',', $_POST['busquedaanio']);
		$where .= " AND a.anio IN ({$busquedaanio})";
	}

	$select_remanente = "SELECT COUNT(cve) as remanente, cvecompra FROM compra_certificados_detalle WHERE plaza='{$_POST['cveplaza']}' AND estatus=0 GROUP BY cvecompra";

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', (a.foliofin+1-a.folioini), 0)) as cantidad, SUM(IF(a.estatus!='C', IFNULL(d.remanente,0), 0)) as remanente FROM compra_certificados a LEFT JOIN ({$select_remanente}) d ON a.cve = d.cvecompra {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'cantidad' => $registros['cantidad'],
		'remanente' => $registros['remanente']
	);
	$res = mysql_query("SELECT a.cve, a.folio, a.fecha_compra, CONCAT(a.fecha, ' ', a.hora) as fecha, b.nombre as nomengomado, a.folioini, a.foliofin, IF(a.estatus!='C', (a.foliofin+1-a.folioini), 0) as cantidad, c.nombre as nomanio, IF(a.estatus!='C', IFNULL(d.remanente,0), 0) as remanente, e.usuario, a.estatus, c.fecha_fin, a.engomado FROM compra_certificados a INNER JOIN engomados b ON b.cve = a.engomado INNER JOIN  anios_certificados c ON c.cve = a.anio LEFT JOIN ({$select_remanente}) d ON a.cve = d.cvecompra INNER JOIN usuarios e ON e.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarcompra('.$row['cve'].')">Cancelar</a>';
		}

		if ($nivelUsuario>2 && $row['estatus']!='C' && $row['fecha_fin'] < date('Y-m-d') && $row['remanente'] > 0 && $row['engomado'] != 3 && $row['engomado']!=19){
			$extras2 .= '<a class="dropdown-item" href="#" onClick="if(confirm(\'Esta seguro de cancelar los faltantes\')) atcr(\'compra_certificados.php\',\'\',\'36\','.$row['cve'].')">Cancelar Faltantes</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="ver_folios('.$row['cve'].')">Ver folios</a>
                      '.$extras2.'
                    </div>';
	    if($row['estatus']=='C'){
	    	$dropmenu='CANCELADO';
	    }
		$resultado['data'][] = array(
			$dropmenu,
			($row['cve']),
			$row['folio'],
			mostrar_fechas($row['fecha_compra']),
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['nomengomado']),
			$row['folioini'],
			$row['foliofin'],
			number_format($row['cantidad'],0),
			utf8_encode($row['nomanio']),
			number_format($row['remanente'],0),
			utf8_encode($row['usuario']),
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
	<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('compra_certificados.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('compra_certificados.php','',0,0);">Volver</button>
	</div>
</div><br>


<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
	        		<div class="form-group col-sm-3">
						<label for="folio">Folio de Compra</label>
	           			<input type="text" class="form-control" id="folio" value="" name="folio">
	        		</div>
	        		<div class="form-group col-sm-3">
						<label for="fecha_compra">Fecha de Compra</label>
	           			<input type="date" class="form-control" id="fecha_compra" value="" name="fecha_compra" max="<?php echo date('Y-m-d');?>">
	        		</div>
	      		</div>
	      		<div class="form-row">
	      			<div class="form-group col-sm-4">
						<label for="anio">Semestre</label>
			            <select name="anio" id="anio" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		if($_POST['cveusuario']!=1){
								$res1 = mysql_query("SELECT * FROM anios_certificados  ORDER BY nombre DESC LIMIT 2");
			            	}
							else{
								$res1 = mysql_query("SELECT * FROM anios_certificados  ORDER BY nombre DESC");
							}
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
	        		</div>
	        		<div class="form-group col-sm-4">
						<label for="engomado">Tipo de Certificado</label>
			            <select name="engomado" id="engomado" class="form-control"><option value="0">Seleccione</option>
			           	<?php
			           		$res1 = mysql_query("SELECT a.cve, a.nombre FROM engomados a inner join engomados_plazas b on a.cve = b.engomado WHERE b.entrega=1 AND b.plaza = '{$_POST['cveplaza']}' ORDER BY a.nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
							}
						?>
						</select>
	        		</div>
	      		</div>
	            <div class="form-row">
			      	<div class="form-group col-sm-2">
						<label for="folioini">Folio Inicial</label>
			          	<input type="number" class="form-control" id="folioini" value="" name="folioini" onKeyUp="calcular()">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="foliofin">Folio Final</label>
			          	<input type="number" class="form-control" id="foliofin" value="" name="foliofin" onKeyUp="calcular()">
			        </div>
			        <div class="form-group col-sm-2">
						<label for="total">Cantidad</label>
			          	<input type="number" class="form-control" id="cantidad" value="" name="cantidad" readOnly>
			        </div>
			    </div>
	      
	    	</div>
	  	</div>
	</div>
</div>

<script>
	function calcular(){
		var cantidad = 0;
		if(($('#folioini').val()/1) > 0 && ($('#foliofin').val()/1) > 0 && ($('#folioini').val()/1)<=($('#foliofin').val()/1)){
			cantidad = ($('#foliofin').val()/1) + 1 - ($('#folioini').val()/1);
		}
		$('#cantidad').val(cantidad.toFixed(0));
		
	}
</script>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['folio']) == ''){
		$resultado['error']=1;
		$resultado['mensaje']='Necesita ingresar el folio de compra';
	}
	elseif(trim($_POST['fecha_compra']) == ''){
		$resultado['error']=1;
		$resultado['mensaje']='Necesita ingresar el folio de compra';
	}
	elseif(trim($_POST['anio']) == '0'){
		$resultado['error']=1;
		$resultado['mensaje']='Necesita seleccionar el semestre';
	}
	elseif(trim($_POST['engomado']) == '0'){
		$resultado['error']=1;
		$resultado['mensaje']='Necesita seleccionar el tipo de certificado';
	}
	elseif($_POST['cantidad'] <= 0){
		$resultado['error']=1;
		$resultado['mensaje']='La cantidad debe de ser mayor';
	}
	elseif($_POST['cveusuario']!=1){
		$res = mysql_query("SELECT * FROM compra_certificados WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}' AND anio='{$_POST['anio']}' AND ((folioini BETWEEN '{$_POST['folioini']}' AND '{$_POST['foliofin']}') OR (foliofin BETWEEN '{$_POST['folioini']}' AND '{$_POST['foliofin']}')) AND estatus!='C' ORDER BY cve DESC LIMIT 1");
		if(mysql_num_rows($res)>0){
			$resultado['error']=1;
			$resultado['mensaje']='Los folios de chocan con otra compra';
		}
	}

	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$engomado = mysql_fetch_assoc(mysql_query("SELECT precio_compra FROM engoados_plazas WHERE plaza='{$_POST['cveplaza']}' AND engomado='{$_POST['engomado']}'"));
		$insert = " INSERT compra_certificados 
						SET 
							folio='{$_POST['folio']}',fecha_compra='{$_POST['fecha_compra']}',costo='{$engomado['precio_compra']}',
								plaza = '{$_POST['cveplaza']}',fecha=CURDATE(),hora=CURTIME(),
								engomado='{$_POST['engomado']}',folioini='{$_POST['folioini']}',anio='{$_POST['anio']}',
								foliofin='{$_POST['foliofin']}',usuario='{$_POST['cveusuario']}',estatus='A'";
		mysql_query($insert);
		$cvecompra = mysql_insert_id();
		$cantidad = $_POST['foliofin']+1-$_POST['folioini'];
		$folio = $_POST['folioini']/1;
		for($i=0;$i<$cantidad;$i++){
			mysql_query("INSERT compra_certificados_detalle SET plaza='{$_POST['cveplaza']}',cvecompra='{$cvecompra}',folio='$folio',tipo=0");
			$folio++;
		}
		echo '<script>$("#contenedorprincipal").html("");atcr("compra_certificados.php","",0,"");</script>';
	}


}

if($_POST['cmd']==36){
	$resF = mysql_query("SELECT a.anio, a.engomado, b.folio, c.fecha_fin FROM compra_certificados a INNER JOIN compra_certificados_detalle b on a.plaza = b.plaza AND a.cve = b.cvecompra INNER JOIN anios_certificados c ON c.cve = a.anio WHERE a.plaza={$_POST['cveplaza']} AND a.cve={$_POST['reg']} AND b.tipo=0");
	while($folios = mysql_fetch_assoc($resF)){

		$holograma = $folios['folio'];
		$res = mysql_query("SELECT cve, fecha FROM certificados_cancelados WHERE plaza={$_POST['cveplaza']} AND engomado={$folios['engomado']} AND certificado ={$holograma} AND estatus!='C'");
		if(mysql_num_rows($res)==0){
			$res = mysql_query("SELECT cve, fecha FROM certificados WHERE plaza={$_POST['cveplaza']} AND engomado={$folios['engomado']} AND certificado={$holograma} AND estatus!='C'");
			if(mysql_num_rows($res)==0){
					$ticket = 0;
					$fechaticket='0000-00-00 00:00:00';
					
					$insert = " INSERT certificados_cancelados
										SET 
										plaza = '{$_POST['cveplaza']}', fecha='{$folios['fecha_fin']}, hora=CURTIME(), certificado='{$holograma}', motivo='12', anio='{$folios['anio']}', usuario='{$_POST['cveusuario']}', engomado='{$folios['engomado']}', estatus='A', placa='', obs='', tecnico='', linea='',ticket='$ticket', fechaticket='', cobro_tecnico='0',cant_empleados='0'";
					mysql_query($insert);
					$idcancelacion = mysql_insert_id();
					mysql_query("UPDATE compra_certificados a INNER JOIN compra_certificados_detalle b ON a.plaza = b.plaza AND a.cve = b.cvecompra SET b.estatus=1 WHERE a.plaza={$_POST['cveplaza']} AND a.engomado = {$folios['engomado']} AND b.folio={$holograma} AND a.estatus!='C'");
					
				
			}
			
		}
		
	}

	echo '<script>$("#contenedorprincipal").html("");atcr("compra_certificados.php","",0,"");</script>';
}
?>