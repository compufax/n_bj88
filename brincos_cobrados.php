<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE brincos SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='".addslashes($_POST['motivocancelacion'])."' WHERE cve='{$_POST['brinco']}'");
	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="brincocancelar" value="">
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
				<button type="button" class="btn btn-danger" onClick="cancelarbrinco();">Cancelar</button>
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
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" placeholder="Fecha Inicio" value="<?php echo date('Y-m-d');?>">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" placeholder="Fecha Fin" value="<?php echo date('Y-m-d');?>">
        	</div>
        </div>
        <div class="form-group row">
			<label class="col-sm-2 col-form-label">Placa</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedaplaca" name="busquedaplaca" placeholder="Placa" value="">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
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
				<th>Placa</th>
				<th>Tipo Pago</th>
				<th>Tipo</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Placa</th>
				<th>Tipo Pago</th>
				<th>Tipo</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'brincos_cobrados.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		'busquedafechaini': $('#busquedafechaini').val(),
        		'busquedafechafin': $('#busquedafechafin').val(),
        		'busquedaplaca': $('#busquedaplaca').val(),
        		'cvemenu': '<?php echo $_POST['cvemenu'];?>',
        		'cveplaza': '<?php echo $_POST['cveplaza'];?>'
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
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ orderable: false, "targets": 6 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	'busquedafechaini': $('#busquedafechaini').val(),
        	'busquedafechafin': $('#busquedafechafin').val(),
        	'busquedaplaca': $('#busquedaplaca').val(),
        	'cvemenu': '<?php echo $_POST['cvemenu'];?>',
        	'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}

	function cancelarbrinco(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'brincos_cobrados.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					brinco: $('#brincocancelar').val(),
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

	function precancelarbrinco(brinco){
		$('#brincocancelar').val(brinco);
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
	$columnas=array("a.folio", "CONCAT(a.fecha,' ',a.hora)", "a.placa", "b.nombre", "c.nombre", "d.usuario");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.folio DESC";
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


	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM brincos a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$nivelUsuario = nivelUsuario();
	$res = mysql_query("SELECT a.cve, a.folio, a.placa, a.fecha, a.hora, b.nombre as nomtipopago, c.nombre as nomtipo, d.usuario, a.estatus FROM brincos a INNER JOIN tipos_pago_brincos b ON b.cve = a.tipo_pago INNER JOIN tipos_brinco c ON c.cve = a.tipo INNER JOIN usuarios d ON d.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$extras = '';
		if($row['estatus']=='C'){
			$extras = 'CANCELADO';
		}
		elseif($row['estatus'] != 'C' && $nivelUsuario>2){
			$extras .= '&nbsp;<i class="fas fa-trash fa-sm fa-fw mr-2 text-danger" style="cursor:pointer;" onClick="precancelarbrinco('.$row['cve'].')" title="Cancelar"></i>';
		}
		$resultado['data'][] = array(
			$row['folio'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			utf8_encode($row['placa']),
			utf8_encode($row['nomtipopago']),
			utf8_encode($row['nomtipo']),
			utf8_encode($row['usuario']),
			$extras
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM placas_brincos WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('captura_brinco.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('captura_brinco.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="placa">Placa</label>
			            <input type="text" class="form-control" name="placa" id="placa" value="<?php echo $row['placa'];?>">
			        </div>
			        <div class="form-group col-sm-4">
						<label for="tipo">Tipo</label>
			            <select name="tipo" id="tipo" class="form-control"><option value="">Seleccione</option>
			            <?php
			            $res1 = mysql_query("SELECT cve, nombre FROM tipos_brinco WHERE plaza='{$_POST['cveplaza']}' ORDER BY nombre");
			            while($row1 = mysql_fetch_assoc($res1)){
			            	echo '<option value="'.$row1['cve'].'"';
			            	if($row1['cve']==$row['tipo']) echo ' selected';
			            	echo '>'.utf8_encode($row1['nombre']).'</option>';
			            }
			            ?>
			            </select>
			        </div>
			    </div>
			    
			</div>
		</div>
	</div>
</div>

<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['placa'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la placa');
	}
	elseif(trim($_POST['tipo'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo');
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{

		
		if($_POST['reg']>0){
			$res = mysql_query("SELECT nombre, precio FROM placas_brincos WHERE cve='{$_POST['reg']}'");
			$row = mysql_fetch_assoc($res);
			if($row['placa']!=$_POST['placa']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Placa', nuevo='".addslashes($_POST['placa'])."', anterior='".addslashes($row['placa'])."', usuario='{$_POST['cveusuario']}'");
			}
			if($row['tipo']!=$_POST['tipo']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Tipo', nuevo='".addslashes($_POST['tipo'])."', anterior='".addslashes($row['tipo'])."', usuario='{$_POST['cveusuario']}'");
			}
			
			mysql_query("UPDATE placas_brincos SET placa='".addslashes($_POST['placa'])."', tipo='{$_POST['tipo']}' WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT placas_brincos SET plaza='{$_POST['cveplaza']}', tipo='{$_POST['tipo']}', placa='".addslashes($_POST['placa'])."', usuario='{$_POST['cveusuario']}'");
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("captura_brinco.php","",0,"0");</script>';
	}
	
}
?>