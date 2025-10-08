<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');
require_once('validarloging.php');
$tipo_depositante = 2;
$Plaza = mysql_fetch_assoc(mysql_query("SELECT num_depositante_acumulado_automatico FROM plazas WHERE cve='{$_POST['cveplaza']}'"));
$num_depositante_acumulado_automatico = $Plaza['num_depositante_acumulado_automatico'];
$res = mysql_query("SELECT * FROM usuarios");
while($row=mysql_fetch_array($res)){
	$array_usuario[$row['cve']]=$row['usuario'];
}
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
	        	<button type="button" class="btn btn-success" onClick="atcr('depositantes_contado.php','',1,0);">
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
				<th>N&uacute;mero</th>
				<th>Nombre</th>
				<th>Tipo</th>
				<th>Estatus</th>
				<th>Usuario</th>
				<th>Fecha</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>N&uacute;mero</th>
				<th>Nombre</th>
				<th>Tipo</th>
				<th>Estatus</th>
				<th>Usuario</th>
				<th>Fecha</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'depositantes_contado.php',
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
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
			{ className: "dt-head-center dt-body-center", "targets": 5 },
			{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ orderable: false, "targets": 4 }
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
	$columnas=array("numero_cliente", "nombre", "agencia", "estatus");

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

	$where = " WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='{$tipo_depositante}'";

	if($_POST['busquedanombre']!=''){
		$where .= " AND nombre LIKE '%{$_POST['busquedanombre']}%'";
	}

	if($_POST['busquedaestatus']!=''){
		$where .= " AND estatus = '{$_POST['busquedaestatus']}'";
	}

	$res = mysql_query("SELECT COUNT(cve) as registros FROM depositantes{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT cve, numero_cliente, nombre, IF(agencia=1, 'Agencia', 'Taller') as nomtipo, IF(estatus=0,'Activo','Inactivo') as nomestatus,
				usuario,CONCAT(fecha_creacion,' ',hora_creacion) as creacion
	FROM depositantes{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$row = convertir_a_utf8($row);
		$resultado['data'][] = array(
			utf8_encode($row['numero_cliente']),
			utf8_encode($row['nombre']),
			utf8_encode($row['nomtipo']),
			utf8_encode($row['nomestatus']),
			utf8_encode($array_usuario[$row['usuario']]),
			utf8_encode($row['creacion']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'depositantes_contado.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM depositantes WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('depositantes_contado.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('depositantes_contado.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
					<div class="form-group col-sm-3">
						<label for="numero_cliente">N&uacute;mero</label>
			            <input type="number" class="form-control" name="numero_cliente" id="numero_cliente" value="<?php echo $row['numero_cliente'];?>"<?php if($_POST['reg']>0 || $num_depositante_acumulado_automatico == 1){?> readOnly<?php }?>>
			        </div>
			        <div class="form-group col-sm-6">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
					
					<div class="form-group col-sm-6">
						<label for="nombre">Direccion</label>
			            <input type="text" class="form-control" name="direccion" id="direccion" value="<?php echo $row['direccion'];?>">
			        </div>
					<div class="form-group col-sm-6">
						<label for="nombre">Telefono</label>
			            <input type="text" class="form-control" name="telefono" id="telefono" value="<?php echo $row['telefono'];?>">
			        </div>
					<div class="form-group col-sm-6">
						<label for="nombre">Responsable</label>
			            <input type="text" class="form-control" name="responsable" id="responsable" value="<?php echo $row['responsable'];?>">
			        </div>
					<div class="form-group col-sm-6">
						<label for="nombre">P. Cortesia </label>
			            <input type="text" class="form-control" name="n_cortesias" id="n_cortesias" value="<?php echo $row['n_cortesias'];?>">
			        </div>
					
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-3">
						<label for="agencia">Tipo</label>
			            <select name="agencia" id="agencia" class="form-control"><option value="">Seleccione</option>
			            	<option value="1"<?php if($row['agencia']==1){ ?> selected<?php } ?>>Agencia</option>
			            	<option value="2"<?php if($row['agencia']==2){ ?> selected<?php } ?>>Taller</option>
			            </select>
			        </div>
			        <div class="form-group col-sm-3"<?php if($_POST['reg']==0 || $_POST['cveusuario'] != 1){?> style="display: none;"<?php }?>>
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
	if(trim($_POST['numero_cliente'])=='' && $num_depositante_acumulado_automatico != 1){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el número');
	}
	elseif(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	elseif(trim($_POST['agencia'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el tipo');
	}
	elseif($num_depositante_acumulado_automatico == 1){
		$res = mysql_query("SELECT cve FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='{$tipo_depositante}' AND numero_cliente='{$_POST['numero_cliente']}' AND cve != '{$_POST['reg']}'");
		if ($row = mysql_fetch_assoc($res)) {
			$resultado = array('error' => 1, 'mensaje' => 'El número de cliente ya esta asignado');
		}
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{

		
		if($_POST['reg']>0){
			
			mysql_query("UPDATE depositantes SET agencia='{$_POST['agencia']}', estatus='{$_POST['estatus']}', nombre='".addslashes($_POST['nombre'])."',
			direccion='{$_POST['direccion']}',telefono='{$_POST['telefono']}',responsable='{$_POST['responsable']}',n_cortesias ='{$_POST['n_cortesias']}'
			WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{
			if($num_depositante_acumulado_automatico==1){
				$res = mysql_query("SELECT IFNULL(MAX(numero_cliente)+1,1) as numero_cliente FROM depositantes WHERE plaza='{$_POST['cveplaza']}' AND tipo_depositante='{$tipo_depositante}'");
				$row = mysql_fetch_assoc($res);
				$_POST['numero_cliente'] = $row['numero_cliente'];
			}

			mysql_query("INSERT depositantes SET plaza='{$_POST['cveplaza']}', tipo_depositante='{$tipo_depositante}', agencia='{$_POST['agencia']}', estatus='{$_POST['estatus']}', numero_cliente='{$_POST['numero_cliente']}', nombre='".addslashes($_POST['nombre'])."',
			direccion='{$_POST['direccion']}',telefono='{$_POST['telefono']}',responsable='{$_POST['responsable']}',n_cortesias ='{$_POST['n_cortesias']}', usuario='{$_POST['cveusuario']}',
			fecha_creacion=CURDATE(),hora_creacion =CURTIME()
			");
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("depositantes_contado.php","",0,"0");</script>';
	}
	
}
?>