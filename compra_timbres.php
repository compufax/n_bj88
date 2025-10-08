<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');


if($_POST['cmd']==33){
	$resultado = array('mensaje' => 'Se cancelo exitosamente', 'tipo'=>'success');

	mysql_query("UPDATE compra_timbres SET estatus='C', usucan='{$_POST['cveusuario']}', fechacan=NOW() WHERE cve='{$_POST['compra']}'");


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
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('compra_timbres.php','',1,0);">
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
				<th>Consecutivo</th>
				<th>Fecha Compra</th>
				<th>Factura</th>
				<th>Fecha</th>
				<th>Cantidad</th>
				<th>Observaciones</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Consecutivo</th>
				<th>Fecha Compra</th>
				<th>Factura</th>
				<th>Fecha</th>
				<th>Cantidad<br><span id="tcantidad" style="text-align: right;"></span></th>
				<th>Observaciones</th>
				<th>Usuario</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'compra_timbres.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"busquedafechaini": $("#busquedafechaini").val(),
        		"busquedafechafin": $("#busquedafechafin").val(),
        		"cvemenu": $('#cvemenu').val(),
        		"cveplaza": $('#cveplaza').val(),
        		"cveusuario": $('#cveusuario').val()
        	},
        	fncallback: function(json){
        		$('#tcantidad').html(json.cantidad);
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
        	{ className: "dt-head-center dt-body-right", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-left", "targets": 6 },
        	{ className: "dt-head-center dt-body-center", "targets": 7 },
        	{ orderable: false, "targets": 7 }
		  ]
    } );
	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"busquedafechaini": $("#busquedafechaini").val(),
    		"busquedafechafin": $("#busquedafechafin").val(),
    		"cvemenu": $('#cvemenu').val(),
    		"cveplaza": $('#cveplaza').val(),
    		"cveusuario": $('#cveusuario').val()
        });
        tablalistado.ajax.reload();
	}

	function cancelarcompra(compra){
		if (confirm("Esta seguro de cancelar el registro?")){
			waitingDialog.show();
			$.ajax({
				url: 'compra_timbres.php',
				type: "POST",
				dataType: 'json',
				data: {
					cmd: 33,
					compra: compra,
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
	$columnas=array("a.folio", "a.fecha_compra", 'a.factura', "CONCAT(a.fecha,' ',a.hora)", "IF(a.estatus='C',0,a.cantidad)", "a.obs",'b.usuario');

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
			$where .= " AND a.fecha_compra >= '{$_POST['busquedafechaini']}'";
		}

		if($_POST['busquedafechafin']!=''){
			$where .= " AND a.fecha_compra <= '{$_POST['busquedafechafin']}'";
		}


	$res = mysql_query("SELECT COUNT(a.cve) as registros, SUM(IF(a.estatus!='C', a.cantidad, 0)) as cantidad FROM compra_timbres a {$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
		'cantidad' => number_format($registros['cantidad'],0),
	);
	$res = mysql_query("SELECT a.cve, a.folio, a.fecha_compra, a.factura, a.fecha, a.hora, IF(a.estatus='C',0,a.cantidad) as cantidad, a.obs, b.usuario, a.estatus FROM compra_timbres a INNER JOIN usuarios b ON b.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	$nivelUsuario = nivelUsuario();
	while($row = mysql_fetch_assoc($res)){
		
		$extras2 = '';
		if ($row['estatus'] == 'A' && $nivelUsuario >= 3) {
			$extras2 .= '<a class="dropdown-item" href="#" onClick="cancelarcompra('.$row['cve'].')">Cancelar</a>';
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
    elseif($row['estatus']=='P'){
    	$dropmenu='PAGADO';
    }
		$resultado['data'][] = array(
			($row['cve']),
			mostrar_fechas($row['fecha_compra']),
			$row['factura'],
			mostrar_fechas($row['fecha']).' '.$row['hora'],
			number_format($row['cantidad'],0),
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
		<button type="button" class="btn btn-success" onClick="atcr('compra_timbres.php','',2,'0');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
	<?php
		}
	?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('compra_timbres.php','',0,0);">Volver</button>
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
			           <input type="date" class="form-control" id="fecha_compra" value="<?php echo date('Y-m-d');?>" name="fecha_compra" readOnly>
			        </div>
			    </div>
	      
		      <div class="form-row">
		      	<div class="form-group col-sm-2">
							<label for="cantidad">Cantidad</label>
		          <input type="number" class="form-control" id="cantidad" value="" name="cantidad">
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
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el concepto');
	}
	elseif($_POST['cantidad']==0){
		$resultado = array('error' => 1, 'mensaje' => 'La cantidad debe de ser mayor a cero');
	}
	
	if($resultado['error']==1){
		echo json_encode($resultado);
	}
	else{

		$resPlaza = mysql_query("SELECT a.numero, a.cveclientefactura, b.rfc FROM plazas a INNER JOIN datosempresas b ON a.cve = b.plaza WHERE a.cve = '{$_POST['cveplaza']}'");
		$rowPlaza = mysql_fetch_array($resPlaza);
		if($rowPlaza['cveclientefactura'] > 0 && $_POST['sinfactura'] != 1){
			$datos['empresa'] = $empresa_timbres;
			$datos['cantidad'] = $_POST['cantidad'];
			$datos['numeroplaza'] = $rowPlaza['numero'];
			$datos['rfcplaza'] = $rowPlaza['rfc'];
			$datos['cvecliente'] = $rowPlaza['cveclientefactura'];
			$datos['obs'] = $_POST['obs'];
			$data = array(
				'function' => 'genera_factura_timbres',
			    'datos' => $datos
			 );

			$options = array('http' => array(
				'method'  => 'POST',
				'content' => http_build_query($data)
			));
			$context  = stream_context_create($options);

			$resultado = file_get_contents('http://hoyfactura.com/ws_genera_factura_timbres.php', false, $context);
			$datosresultado = json_decode($resultado, true);
			$_POST['factura'] = $datosresultado['factura'];
			$_POST['fecha_compra'] = date('Y-m-d');
			$estatus='A';
		}
		else{
			$estatus='P';
		}

		if($_POST['factura'] > 0 || $rowPlaza['cveclientefactura'] == 0 || $_POST['sinfactura']==1){
			$res = mysql_query("SELECT IFNULL(MAX(folio)+1,1) as siguiente FROM compra_timbres WHERE plaza='{$_POST['cveplaza']}'");
			$row=mysql_fetch_array($res);	
				$insert = " INSERT compra_timbres 
								SET 
								folio='{$row[0]}',fecha_compra='{$_POST['fecha_compra']}',
								plaza = '{$_POST['cveplaza']}',fecha=CURDATE(),hora=CURTIME(),
								cantidad='{$_POST['cantidad']}',usuario='{$_POST['cveusuario']}',estatus='{$estatus}',
								factura='{$_POST['factura']}',obs='".addslashes($_POST['obs'])."'";
				mysql_query($insert) or die(mysql_error());
		}
		
			
		echo '<script>$("#contenedorprincipal").html("");atcr("compra_timbres.php","",0,"");</script>';
	}
}



?>