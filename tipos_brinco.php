<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');
require_once('validarloging.php');

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('tipos_brinco.php','',1,0);">
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
				<th>Nombre</th>
				<th>Precio</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Nombre</th>
				<th>Precio</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'tipos_brinco.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		'busquedanombre': $('#busquedanombre').val(),
        		'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-right", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ orderable: false, "targets": 2 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	'busquedanombre': $('#busquedanombre').val(),
        	'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){
	$columnas=array("nombre", "precio");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY nombre";
	}
	else{
		$orderby = " ORDER BY ".substr($orderby, 1);
	}

	$where = " WHERE plaza='{$_POST['cveplaza']}'";

	if($_POST['busquedanombre']!=''){
		$where .= " AND nombre LIKE '%{$_POST['busquedanombre']}%'";
	}


	$res = mysql_query("SELECT COUNT(cve) as registros FROM tipos_brinco{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT cve, nombre, precio FROM tipos_brinco{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$resultado['data'][] = array(
			utf8_encode($row['nombre']),
			number_format($row['precio'],2),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'tipos_brinco.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM tipos_brinco WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('tipos_brinco.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('tipos_brinco.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
			        <div class="form-group col-sm-3">
						<label for="numero">Precio</label>
			            <input type="number" class="form-control" name="precio" id="precio" value="<?php echo $row['precio'];?>">
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
	if(trim($_POST['precio'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el precio');
	}
	elseif(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{

		
		if($_POST['reg']>0){
			$res = mysql_query("SELECT nombre, precio FROM tipos_brinco WHERE cve='{$_POST['reg']}'");
			$row = mysql_fetch_assoc($res);
			if($row['nombre']!=$_POST['nombre']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Nombre', nuevo='".addslashes($_POST['nombre'])."', anterior='".addslashes($row['nombre'])."', usuario='{$_POST['cveusuario']}'");
			}
			if($row['precio']!=$_POST['precio']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Precio', nuevo='".addslashes($_POST['precio'])."', anterior='".addslashes($row['precio'])."', usuario='{$_POST['cveusuario']}'");
			}
			
			mysql_query("UPDATE tipos_brinco SET nombre='".addslashes($_POST['nombre'])."', precio='{$_POST['precio']}' WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT tipos_brinco SET plaza='{$_POST['cveplaza']}', precio='{$_POST['precio']}', nombre='".addslashes($_POST['nombre'])."', usuario='{$_POST['cveusuario']}'");
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("tipos_brinco.php","",0,"0");</script>';
	}
	
}
?>