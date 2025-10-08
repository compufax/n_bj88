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
        	<label class="col-sm-2 col-form-label"<?php if($nivelUsuario <= 2){?> style="display: none;"<?php }?>>Estatus</label>
			<div class="col-sm-4"<?php if($nivelUsuario <= 2){?> style="display: none;"<?php }?>>
            	<select id="busquedaestatus" class="form-control"><option value="">Todos</option>
            		<option value="0"<?php if($nivelUsuario <= 2){?> selected<?php }?>>Activos</option><option value="1">Inactivos</option></select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('tecnicos.php','',1,0);">
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
				<th>Clave</th>
				<th>Nombre</th>
				<th>Estatus</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Clave</th>
				<th>Nombre</th>
				<th>Estatus</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'tecnicos.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		"cveempresa": $('#cveempresa').val(),
        		'busquedanombre': $('#busquedanombre').val(),
        		'busquedaestatus': $('#busquedaestatus').val(),
        		'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-left", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-center", "targets": 3 },
        	{ orderable: false, "targets": 3 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	"cveempresa": $('#cveempresa').val(),
        	'busquedanombre': $('#busquedanombre').val(),
        	'busquedaestatus': $('#busquedaestatus').val(),
        	'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){
	$columnas=array("clave", "nombre", "estatus");

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

	if($_POST['busquedaestatus']!=''){
		$where .= " AND estatus = '{$_POST['busquedaestatus']}'";
	}

	$res = mysql_query("SELECT COUNT(cve) as registros FROM tecnicos{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT cve, clave, nombre, IF(estatus=0,'Activo','Inactivo') as nomestatus FROM tecnicos{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$resultado['data'][] = array(
			utf8_encode($row['clave']),
			utf8_encode($row['nombre']),
			utf8_encode($row['nomestatus']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'tecnicos.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM tecnicos WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('tecnicos.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('tecnicos.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
					<div class="form-group col-sm-3">
						<label for="clave">Clave</label>
			            <input type="number" class="form-control" name="clave" id="clave" value="<?php echo $row['clave'];?>"<?php if($_POST['reg']>0){?> readOnly<?php }?>>
			        </div>
			        <div class="form-group col-sm-6">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-3"<?php if($_POST['reg']==0 || nivelUsuario()<=2){?> style="display: none;"<?php }?>>
						<label for="estatus">Estatus</label>
			            <select name="estatus" id="estatus" class="form-control"><option value="0">Activo</option>
			            	<option value="1"<?php if($row['estatus']==1){ ?> selected<?php } ?>>Inactivo</option>
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
	if(trim($_POST['clave'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar la clave');
	}
	elseif(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	else{
		$res = mysql_query("SELECT cve FROM tecnicos WHERE plaza='{$_POST['cveplaza']}' AND clave='{$_POST['clave']}' AND cve != '{$_POST['reg']}'");
		if ($row = mysql_fetch_assoc($res)) {
			$resultado = array('error' => 1, 'mensaje' => 'La clave ya esta asignada');
		}
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{

		
		if($_POST['reg']>0){
			
			mysql_query("UPDATE tecnicos SET estatus='{$_POST['estatus']}', nombre='".addslashes($_POST['nombre'])."' WHERE plaza='{$_POST['cveplaza']}' AND cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT tecnicos SET plaza='{$_POST['cveplaza']}', estatus='{$_POST['estatus']}', clave='{$_POST['clave']}', nombre='".addslashes($_POST['nombre'])."'");
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("tecnicos.php","",0,"0");</script>';
	}
	
}
?>