<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');

if($_GET['cmd']==101){
	include_once("numlet.php");
	$select= " SELECT * FROM copias_simple WHERE plaza='{$_GET['cveplaza']}' AND cve='{$_GET['cvepago']}'";
	$select.=" ORDER BY folio desc";
	$res=mysql_query($select);
	$row=mysql_fetch_array($res);
	$rowFormaPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM formas_pago WHERE cve='{$row['forma_pago']}'"));
	$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));
	$textoimp="";
	$textoimp.="    ".$array_plaza[$_POST['plazausuario']].chr(27).'!'.chr(30).chr(27).'!'.chr(30).chr(27).'!'.chr(30);
	$textoimp.="FOLIO: ".$row['folio'].chr(27).'!'.chr(30);
	$textoimp.="FECHA: ".$row['fecha']." ".$row['hora'].chr(27).'!'.chr(30);
	
	$textoimp.="Depositante: ".$rowDepositante['nombre'].chr(27).'!'.chr(30);
	$textoimp.="Forma Pago: ".$rowFormaPago['nombre'].chr(27).'!'.chr(30);
	$textoimp.="CANTIDAD: ".$row['cantidad']."|";
	$textoimp.="Importe: ".number_format(($row['cantidad']*$row['costo_uni']),2).chr(27).'!'.chr(30);
	$textoimp.=" ".numlet(number_format(($row['cantidad']*$row['costo_uni']),2)).chr(27).'!'.chr(30);
	$texto.=chr(10).chr(13).chr(29).chr(86).chr(66).chr(0);
	echo $texto;
	exit();
}


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE copias_simple SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW(), obscan='".addslashes($_POST['obscan'])."' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['venta']}'");


	echo json_encode($resultado);
	exit();
}
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>
<input type="hidden" id="ventacancelar" value="">
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
				<button type="button" class="btn btn-danger" onClick="cancelarventa();">Cancelar</button>
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
            	<input type="date" class="form-control" id="busquedafechaini" name="busquedafechaini" value="<?php echo date('Y-m');?>-01" placeholder="Fecha Inicio">
        	</div>
			<label class="col-sm-2 col-form-label">Fecha Fin</label>
			<div class="col-sm-4">
            	<input type="date" class="form-control" id="busquedafechafin" name="busquedafechafin" value="<?php echo date('Y-m-d');?>" placeholder="Fecha Fin">
        	</div>
        </div>

      <div class="form-group row">
			<label class="col-sm-2 col-form-label">Forma de Pago</label>
			<div class="col-sm-4">
            	<select name="busquedaformapago" id="busquedaformapago" class="form-control">
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM formas_pago WHERE cve=1 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Depositante</label>
			<div class="col-sm-4">
            	<select name="busquedadepositante" id="busquedadepositante" class="form-control" data-container="body" data-live-search="true" title="Depositante" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM depositantes WHERE estatus!=1 AND tipo_depositante=0 ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.$row1['nombre'].'</option>';
				}
				?>
            	</select>
            	<script>
					$("#busquedadepositante").selectpicker();	
				</script>
        	</div>
        </div>

        <div class="form-group row">
			
        	<label class="col-sm-2 col-form-label">Usuario</label>
			<div class="col-sm-4">
            	<select name="busquedausuario" id="busquedausuario" class="form-control" data-container="body" data-live-search="true" title="Usuario" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT b.cve, b.usuario FROM (SELECT usuario FROM copias_simple WHERE plaza='{$_POST['cveplaza']}' GROUP BY usuario) a INNER JOIN usuarios b ON b.cve = a.usuario ORDER BY b.usuario");
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
	        	<button type="button" class="btn btn-success" onClick="atcr('copias_simple.php','',1,0);">
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
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Depositante</th>
				<th>Forma de Pago</th>
				<th>Cantidad</th>
				<th>Importe</th>
				<th>Observaciones</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>&nbsp;</th>
				<th>Folio</th>
				<th>Fecha</th>
				<th>Depositante</th>
				<th>Forma de Pago</th>
				<th>Cantidad<br><span id="tcant" style="text-align: right;"></span></th>
				<th>Importe<br><span id="ttotal" style="text-align: right;"></span></th>
				<th>Observaciones</th>
				<th>Usuario</th>
				
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'copias_simple.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"busquedausuario": $("#busquedausuario").val(),
        		"busquedadepositante": $("#busquedadepositante").val(),
        		"busquedaformapago": $("#busquedatipopago").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tcant').html(json.cantidad);
        		$('#ttotal').html(json.total);
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-left", "targets": 4 },
        	{ className: "dt-head-center dt-body-right", "targets": 5 },
        	{ className: "dt-head-center dt-body-right", "targets": 6 },
        	{ className: "dt-head-center dt-body-left", "targets": 7 },
        	{ className: "dt-head-center dt-body-left", "targets": 8 },
        	{ orderable: false, "targets": 0 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"busquedausuario": $("#busquedausuario").val(),
    		"busquedadepositante": $("#busquedadepositante").val(),
    		"busquedaformapago": $("#busquedatipopago").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarventa(){
		if ($("#motivocancelacion").val() == ""){
			alert("Necesita seleccionar un motivo de cancelacion");
		}
		else{
			$('#modalCancelacion').modal('hide');
			waitingDialog.show();
			$.ajax({
				url: 'copias_simple.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					venta: $('#ventacancelar').val(),
					obscan: $("#motivocancelacion").val(),
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

	function precancelarventa(venta){
		$('#ventacancelar').val(venta);
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
	$columnas=array("", "a.folio", "a.fecha", "b.nombre", "c.nombre", "a.cantidad", 'a.monto', 'a.obs', 'd.usuario');

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
			$where .= " AND a.fecha >= '{$_POST['busquedafechaini']} 00:00:00'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha <= '{$_POST['busquedafechafin']} 23:59:59'";
		}

		if($_POST['busquedadepositante']!=''){
			$where .= " AND a.depositante = '{$_POST['busquedadepositante']}'";
		}

		if($_POST['busquedausuario']!=''){
			$where .= " AND a.usuario = '{$_POST['busquedausuario']}'";
		}

		if($_POST['busquedaformaago']!=''){
			$where .= " AND a.forma_pago = '{$_POST['busquedaformaago']}'";
		}

	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.cantidad, 0)) as cantidad, IF(a.estatus='C',0,a.monto) as total FROM copias_simple a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'cantidad' => $registros['cantidad'],
		'total' => $registros['total']
	);
	$res = mysql_query("SELECT a.cve, a.folio, a.fecha, b.nombre as nomdepositante, c.nombre as nomformapago, 
	IF(a.estatus='C',0,a.cantidad) as cantidad, IF(a.estatus='C',0,a.monto) as monto, a.obs, d.usuario, a.estatus 
	FROM copias_simple a 
	INNER JOIN depositantes b ON b.cve = a.depositante 
	INNER JOIN formas_pago c ON c.cve = a.forma_pago 
	INNER JOIN usuarios d ON d.cve = a.usuario 
	{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="precancelarventa('.$row['cve'].')">Cancelar</a>';
		}

		$dropmenu = '<button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton_'.$row['cve'].'" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      Acci&oacute;n
                    </button><div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton_'.$row['cve'].'" x-placement="bottom-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, 38px, 0px);">
                      <a class="dropdown-item" href="#" onClick="atcr(\'copias_simple.php\',\'\',101,'.$row['cve'].')">Imprimir</a>
                      '.$extras2.'
                    </div>';
	    if($row['estatus']=='C'){
	    	$dropmenu='CANCELADO';
	    }
	    
		$resultado['data'][] = array(
			$dropmenu,
			($row['folio']),
			mostrar_fechas($row['fecha']),
			utf8_encode($row['nomdepositante']),
			utf8_encode($row['nomformapago']),
			number_format($row['cantidad'],2),
			number_format($row['monto'],2),
			utf8_encode($row['obs']),
			utf8_encode($row['usuario']),
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM costos_copias_impresiones ORDER BY cve DESC");
	$row = mysql_fetch_assoc($res);
	$costo_copias = $row['copias'];
?>
<input type="hidden" name="costo_copias" id="costo_copias" value="<?php echo $costo_copias;?>">
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php
		if(nivelUsuario() > 1){
	?>
		<button type="button" class="btn btn-success" onClick="atcr('copias_simple.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('copias_simple.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="fecha">Fecha</label>
	           <input type="date" class="form-control" id="fecha" value="<?php echo date('Y-m-d');?>" name="fecha">
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-7">
				<label for="depositante">Depositante</label>
	         	<select name="depositante" class="form-control" data-container="body" data-live-search="true" title="Depositante" data-hide-disabled="true" data-actions-box="true" data-virtual-scroll="false" id="depositante"><option value="">Seleccione</option>
	         		<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='0' AND estatus=0 ORDER BY nombre");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'"">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
	         	</select>
	         	<script>
					$("#depositante").selectpicker();	
				</script>
	        </div>
	      </div>
	      <div class="form-row">
	        <div class="form-group col-sm-3">
						<label for="forma_pago">Forma de Pago</label>
	          <select name="forma_pago" id="forma_pago" class="form-control" onChange="muestra_referencia()">
	           	<?php
	           		$res1 = mysql_query("SELECT cve, nombre FROM formas_pago WHERE cve=1 ORDER BY cve");
								while($row1=mysql_fetch_array($res1)){
									echo '<option value="'.$row1['cve'].'"">'.utf8_encode($row1['nombre']).'</option>';
								}
							?>
						</select>
	        </div>
	        
	      </div>
	      
	      
	      
	      <div class="form-row">
	      	<div class="form-group col-sm-3">
						<label for="monto">Cantidad de copias</label>
	          <input type="number" class="form-control" id="cantidad" value="" name="cantidad" onKeyUp="calcular()">
	        </div>
	      </div>
	      <div class="form-row">
	      	<div class="form-group col-sm-3">
						<label for="monto">Importe</label>
	          <input type="number" class="form-control" id="monto" value="" name="monto" readonly>
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


function calcular(){
	var total = $('#cantidad').val() * $('#costo_copias').val();
	$('#monto').val(total.toFixed(2));
}

</script>

<?php
}




if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['forma_pago'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la forma de pago');
	}
	elseif(trim($_POST['depositante']) == ''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el depositante');
	}

	elseif($_POST['monto']<=0 && $_POST['cveusuario']!=1){
		$resultado = array('error' => 1, 'mensaje' => 'El monto debe de ser mayor a cero');
	}
	
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{
		$rsfolio=mysql_query("SELECT IFNULL(MAX(folio)+1,1) as siguiente FROM copias_simple WHERE plaza='{$_POST['cveplaza']}'") or die(mysql_error());
		$Folio=mysql_fetch_assoc($rsfolio);
		$folio = $Folio['siguiente'];
		$insert = " INSERT copias_simple SET plaza = '{$_POST['cveplaza']}',	forma_pago='{$_POST['forma_pago']}', monto='{$_POST['monto']}', depositante='{$_POST['depositante']}', usuario='{$_POST['cveusuario']}', estatus='A', cantidad='{$_POST['cantidad']}', costo='{$_POST['costo_copias']}', obs='".addslashes($_POST['obs'])."'";
		while(!$res = mysql_query($insert)){
			$folio++;
			$insert = " INSERT copias_simple SET plaza = '{$_POST['cveplaza']}',	forma_pago='{$_POST['forma_pago']}', monto='{$_POST['monto']}', depositante='{$_POST['depositante']}', usuario='{$_POST['cveusuario']}', estatus='A', cantidad='{$_POST['cantidad']}', costo='{$_POST['costo_copias']}', obs='".addslashes($_POST['obs'])."'";
		}
		$cvecobro = mysql_insert_id();
		echo '<script>$("#contenedorprincipal").html("");atcr("copias_simple.php","",0,"");atcr("cipias_simple.php","_blank",101,"'.$cvecobro.'");</script>';
	}
}

if($_POST['cmd']==101){
	/*$variables = array(
		'server' => '',
		'printer' => 'impresoratermica',
		'url' => $url_impresion.'/copias_simple.php?cmd=101&cveplaza='.$_POST['cveplaza'].'&cvepago='.$_POST['reg']
	);
	$impresion='<iframe src="http://localhost:8020/?'.http_build_query($variables).'" width=200 height=200></iframe>';*/
	require_once("numlet.php");
	$select= " SELECT * FROM copias_simple WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['cvepago']}'";
	$select.=" ORDER BY folio desc";
	$res=mysql_query($select);
	$row=mysql_fetch_array($res);
	$rowPlaza = mysql_fetch_assoc(mysql_query("SELECT numero FROM plazas WHERE cve='{$row['plaza']}'"));
	$rowFormaPago = mysql_fetch_assoc(mysql_query("SELECT nombre FROM formas_pago WHERE cve='{$row['forma_pago']}'"));
	$rowDepositante = mysql_fetch_assoc(mysql_query("SELECT nombre FROM depositantes WHERE cve='{$row['depositante']}'"));
	$textoimp="";
	$textoimp.="    ".$rowPlaza['numero'].'|||';
	$textoimp.="FOLIO: ".$row['folio'].chr(27).'!'.'|';
	$textoimp.="FECHA: ".$row['fecha']." ".$row['hora'].'|';
	
	$textoimp.="Depositante: ".$rowDepositante['nombre'].'|';
	$textoimp.="Forma Pago: ".$rowFormaPago['nombre'].'|';
	$textoimp.="CANTIDAD: ".$row['cantidad']."|";
	$textoimp.="Importe: ".number_format(($row['cantidad']*$row['costo_uni']),2).'|';
	$textoimp.=" ".numlet(number_format(($row['cantidad']*$row['costo_uni']),2)).'|';
	$impresion='<iframe src="http://localhost/impresiongeneral.php?textoimp='.$textoimp.'&copia=1&ncopias=1" width=200 height=200></iframe>';
	echo '<html><body>'.$impresion.'</body></html>';
	echo '<script>setTimeout("window.close()",5000);</script>';
}

?>