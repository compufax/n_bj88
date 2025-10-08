<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE reembolsos SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW() WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reembolso']}'");


	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>

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
			
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Plantilla" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false">
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM reembolsos WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('reembolsos.php','',1,0);">
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
				<th>Fecha Movimiento</th>
				<th>Fecha Creaci&oacute;n</th>
				<th>Monto</th>
				<th>Concepto</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Folio</th>
				<th>Fecha Movimiento</th>
				<th>Fecha Creaci&oacute;n</th>
				<th>Monto<br><span id="tmonto" style="text-align: right;"></span></th>
				<th>Concepto</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'pagos_caja.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tmonto').html(json.monto);
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
        	{ className: "dt-head-center dt-body-right", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ orderable: false, "targets": 6 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarreembolso(reembolso){
		if (confirm("Esta seguro de cancelar el registro?")){
			waitingDialog.show();
			$.ajax({
				url: 'reembolsos.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					reembolso: reembolso,
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

</script>
<?php
}

if($_POST['cmd']==10){
	$columnas=array("a.cve", "a.fecha_mov", "a.fecha", "IF(a.estatus='C',0,a.monto)", "a.obs",'b.usuario');

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

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.monto, 0)) as monto FROM reembolsos a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'monto' => $registros['monto'],
	);
	$res = mysql_query("SELECT a.cve, a.fecha_mov, a.fecha, a.hora, IF(a.estatus='C',0,a.monto) as monto, a.obs, b.usuario, a.estatus FROM reembolsos a INNER JOIN usuarios b ON b.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cancelarreembolso('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <!--<a class="dropdown-item" href="#" onClick="atcr(\'reembolsos.php\',\'\',101,'.$row['cve'].')">Imprimir</a>-->
                      '.$extras2.'
                    </div>';
    if($row['estatus']=='C'){
    	$dropmenu='CANCELADO';
    }
		$resultado['data'][] = array(
			($row['cve']),
			mostrar_fechas($row['fecha_mov']),
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			number_format($row['monto'],2),
			utf8_encode($row['obs']),
			utf8_encode($row['usuario']),
			$dropmenu,
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
		<button type="button" class="btn btn-success" onClick="atcr('reembolsos.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('reembolsos.php','',0,0);">Volver</button>
	</div>
</div><br>
<input type="hidden" name="importe_verificacion" id="importe_verificacion" value="<?php echo $row1['precio'];?>">
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-3">
								<label for="fecha">Fecha</label>
			           <input type="date" class="form-control" id="fecha_mov" value="<?php echo date('Y-m-d');?>" name="fecha_mov">
			        </div>
			    </div>
	      
		      <div class="form-row">
		      	<div class="form-group col-sm-2">
							<label for="monto">Monto</label>
		          <input type="number" class="form-control" id="monto" value="" name="monto">
		        </div>
		      </div>
		      <div class="form-row">
		        <div class="form-group col-sm-6">
		        	<label for="obs">Observaciones</label>
		        	<textarea rows="3" id="obs" name="obs" class="form-control"></textarea>
		        </div>
		      </div>
	    </div>
	  </div>
	</div>
</div>


<script>

</script>

<?php
}



if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['obs']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la observacion');
	}
	elseif($_POST['monto']==0){
		$resultado = array('error' => 1, 'mensaje' => 'El monto no debe de ser mayor a cero');
	}
	
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		
		$insert = " INSERT reembolsos 
							SET 
							plaza = '{$_POST['cveplaza']}',fecha_mov='{$_POST['fecha_mov']}',fecha=CURDATE(),hora=CURTIME(),
							monto='{$_POST['monto']}', usuario='{$_POST['cveusuario']}', estatus='A',
							obs='".addslashes($_POST['obs'])."'";
			mysql_query($insert);
			$cvecobro = mysql_insert_id();
			
		echo '<script>$("#contenedorprincipal").html("");atcr("reembolsos.php","",0,"");/*atcr("reembolsos.php","_blank",101,"'.$cvecobro.'");*/</script>';
	}
}



?>