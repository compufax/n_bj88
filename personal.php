<?php
error_reporting(E_ERROR | E_PARSE);
require_once('cnx_db.php');
require_once('globales.php');
require_once('validarloging.php');
$tipo_depositante = 0;

if($_POST['cmd']==0){
	$nivelUsuario = nivelUsuario();
?>

<div class="row justify-content-center">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Plaza</label>
			<div class="col-sm-4">
            	<select id="busquedaplaza" class="form-control"><option value="">Todos</option>
            	<?php
            	//$res1 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
            	if($_SESSION['CveUsuario']==1) {
				    $res1 = mysql_query("SELECT a.cve,a.numero, a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
				}
				elseif($_SESSION['TipoUsuario']==1){
				    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
				}
				else{
				    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=101 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
				}
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['numero'].' '.$row1['nombre']).'</option>';
				}
				?>	
            	</select>
        	</div>
        	<label class="col-sm-2 col-form-label">Estatus</label>
			<div class="col-sm-4">
            	<select id="busquedaestatus" class="form-control"><option value="">Todos</option>
            		<option value="1" selected>Alta</option>
            		<option value="2">Baja</option>
            		<option value="3">Inactivo</option>
            	</select>
        	</div>
        </div>
		<div class="form-group row">
			<label class="col-sm-2 col-form-label">Nombre</label>
			<div class="col-sm-4">
            	<input type="text" class="form-control" id="busquedanombre" placeholder="Nombre">
        	</div>
        	<label class="col-sm-2 col-form-label">Puesto</label>
			<div class="col-sm-4">
            	<select id="busquedapuesto" class="form-control"><option value="">Todos</option>
            	<?php
            	$res1 = mysql_query("SELECT cve, nombre FROM puestos ORDER BY nombre");
				while($row1=mysql_fetch_array($res1)){
					echo '<option value="'.$row1['cve'].'">'.utf8_encode($row1['nombre']).'</option>';
				}
				?>	
            	</select>
        	</div>
        </div>
        <div class="form-group row">
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('personal.php','',1,0);">
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
				<th>Plaza</th>
				<th>N&uacute;mero</th>
				<th>Nombre</th>
				<th>Estatus</th>
				<th>RFC</th>
				<th>Puesto</th>
				<th>&nbsp;</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Plaza</th>
				<th>N&uacute;mero</th>
				<th>Nombre</th>
				<th>Estatus</th>
				<th>RFC</th>
				<th>Puesto</th>
				<th>&nbsp;</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'personal.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		"cvemenu": $('#cvemenu').val(),
        		'busquedanombre': $('#busquedanombre').val(),
        		'busquedaestatus': $('#busquedaestatus').val(),
        		'busquedaplaza': $('#busquedaplaza').val(),
        		'busquedapuesto': $('#busquedapuesto').val(),
        		'cveplaza': '<?php $_POST['cveplaza'];?>'
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[0, "ASC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-left", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-left", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 },
        	{ className: "dt-head-center dt-body-center", "targets": 4 },
        	{ className: "dt-head-center dt-body-left", "targets": 5 },
        	{ className: "dt-head-center dt-body-center", "targets": 6 },
        	{ orderable: false, "targets": 6 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	"cvemenu": $('#cvemenu').val(),
    		'busquedanombre': $('#busquedanombre').val(),
    		'busquedaestatus': $('#busquedaestatus').val(),
    		'busquedaplaza': $('#busquedaplaza').val(),
    		'busquedapuesto': $('#busquedapuesto').val(),
        	'cveplaza': '<?php $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){

	if($_SESSION['CveUsuario']==1) {
	    $res = mysql_query("SELECT a.cve,a.numero, a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
	}
	elseif($_SESSION['TipoUsuario']==1){
	    $res = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
	}
	else{
	    $res = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=56 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
	}
	$plaza = array();
	while($row = mysql_fetch_assoc($res)){
		$plaza[] = $row['cve'];
	}

	$columnas=array("CONCAT(b.numero,' ',b.nombre)", "a.cve", "a.nombre", "a.estatus", 'a.rfc', 'c.nombre');

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

	$where = " WHERE a.plaza IN (".implode(',',$plaza).")";

	if($_POST['busquedaplaza']!=''){
		$where .= " AND a.plaza = '{$_POST['busquedaplaza']}'";
	}

	if($_POST['busquedanombre']!=''){
		$where .= " AND a.nombre LIKE '%{$_POST['busquedanombre']}%'";
	}

	if($_POST['busquedapuesto']!=''){
		$where .= " AND a.puesto = '{$_POST['busquedapuesto']}'";
	}

	if($_POST['busquedaestatus']!=''){
		$where .= " AND a.estatus = '{$_POST['busquedaestatus']}'";
	}

	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM personal a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT CONCAT(b.numero,' ',b.nombre) as nomplaza, a.cve, a.nombre, IF(a.estatus=1,'Alta',IF(a.estatus=2,'Baja','Inactivo')) as nomestatus, a.rfc, c.nombre as nompuesto FROM personal a INNER JOIN plazas b ON b.cve = a.plaza INNER JOIN puestos c ON c.cve = a.puesto{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$row = convertir_a_utf8($row);
		$resultado['data'][] = array(
			utf8_encode($row['nomplaza']),
			utf8_encode($row['cve']),
			utf8_encode($row['nombre']),
			utf8_encode($row['nomestatus']),
			utf8_encode($row['rfc']),
			utf8_encode($row['nompuesto']),
			'<span class="btn btn-circle btn-info" style="cursor:pointer;"><i class="fas fa-edit" onClick="atcr(\'personal.php\',\'\',1,'.$row['cve'].')" title="Editar"></i></span>'.$extras,
		);
	}
	echo json_encode($resultado);

}

if($_POST['cmd']==1){
	$res = mysql_query("SELECT * FROM personal WHERE cve='{$_POST['reg']}'");
	$row = mysql_fetch_assoc($res);
	$row = convertir_a_utf8($row);
	if($_POST['reg']==0){
		$row['cve'] = 'Nuevo';
		$row['fecha_ini'] = date('Y-m-d');
	}

?>
<div class="row justify-content-center">
	<div class="col-sm-12" align="center">
		<?php if (nivelUsuario() > 1) { ?>
		<button type="button" class="btn btn-success" onClick="atcr('personal.php','',2,'<?php echo $_POST['reg']; ?>');">Guardar</button>
	&nbsp;&nbsp;&nbsp;
		<?php } ?>
		<button type="button" class="btn btn-primary" onClick="$('#contenedorprincipal').html('');atcr('personal.php','',0,0);">Volver</button>
	</div>
</div><br>
<div class="row">
	<div class="col-xl-12 col-lg-12 col-md-12">
		<div class="card shadow">
			<div class="card-body">
				<div class="form-row">
			        <div class="form-group col-sm-6">
						<label for="plaza">Plaza</label>
			            <select name="plaza" id="plaza" class="form-control"><option value="">Seleccione</option>
			            	<?php
			            	//$res1 = mysql_query("SELECT cve, numero, nombre FROM plazas ORDER BY numero, nombre");
			            	if($_SESSION['CveUsuario']==1) {
							    $res1 = mysql_query("SELECT a.cve,a.numero, a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
							}
							elseif($_SESSION['TipoUsuario']==1){
							    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a WHERE a.estatus!='I' ORDER BY a.lista, a.numero, a.nombre");
							}
							else{
							    $res1 = mysql_query("SELECT a.cve,a.numero,a.nombre FROM plazas a INNER JOIN usuario_accesos b ON a.cve=b.plaza AND b.usuario='{$_SESSION['CveUsuario']}' AND b.menu=56 AND b.acceso>0 WHERE a.estatus!='I' GROUP BY a.cve ORDER BY a.lista, a.numero, a.nombre");
							}
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'"';
								if($row1['cve']==$row['plaza']) echo ' selected';
								echo '>'.utf8_encode($row1['numero'].' '.$row1['nombre']).'</option>';
							}
							?>	
			            </select>
			        </div>
			        <div class="form-group col-sm-2">
						<label for="fecha_ini">Fecha Ingreso</label>
			            <input type="date" class="form-control" name="fecha_ini" id="fecha_ini" value="<?php echo $row['fecha_ini'];?>">
			        </div>
			        <div class="form-group col-sm-2"<?php if($_POST['reg']==0){?> style="display: none;"<?php }?>>
						<label for="estatus">Estatus</label>
			            <select name="estatus" id="estatus" class="form-control" onChange="mostrar_fecha_estatus()"><option value="1">Alta</option>
			            	<option value="2"<?php if($row['estatus']==2){ ?> selected<?php } ?>>Baja</option>
			            	<option value="3"<?php if($row['estatus']==3){ ?> selected<?php } ?>>Inactivo</option>
			            </select>
			        </div>
			        <div class="form-group col-sm-2" style="display: none;">
						<label for="fecha_sta">Fecha Cambio Estatus</label>
			            <input type="date" class="form-control" name="fecha_sta" id="fecha_sta" value="<?php echo date('Y-m-d');?>">
			        </div>
			    </div>
				<div class="form-row">
					<div class="form-group col-sm-3">
						<label for="numero">N&uacute;mero</label>
			            <input type="number" class="form-control" name="numero" id="numero" value="<?php echo $row['cve'];?>" readOnly>
			        </div>
			        <div class="form-group col-sm-6">
						<label for="nombre">Nombre</label>
			            <input type="text" class="form-control" name="nombre" id="nombre" value="<?php echo $row['nombre'];?>">
			        </div>
			    </div>
			    <div class="form-row">
			        <div class="form-group col-sm-5">
						<label for="puesto">Puesto</label>
			            <select name="puesto" id="puesto" class="form-control"><option value="">Seleccione</option>
			            	<?php
			            	$res1 = mysql_query("SELECT cve, nombre FROM puestos ORDER BY nombre");
							while($row1=mysql_fetch_array($res1)){
								echo '<option value="'.$row1['cve'].'"';
								if($row1['cve']==$row['puesto']) echo ' selected';
								echo '>'.utf8_encode($row1['nombre']).'</option>';
							}
							?>	
			            </select>
			        </div>
			        <div class="form-group col-sm-6">
						<label for="rfc">RFC</label>
			            <input type="text" class="form-control" name="rfc" id="rfc" value="<?php echo $row['rfc'];?>">
			        </div>
			    </div>
			    
			</div>
		</div>
	</div>
</div>
<script>
	function mostrar_fecha_estatus(){
		if($('#fecha_sta').val() != '<?php echo $row['estatus'];?>'){
			$('#fecha_sta').parents('div:first').show();
		}
		else{
			$('#fecha_sta').parents('div:first').hide();
		}
	}
<?php
}

if($_POST['cmd']==2){
	$resultado = array('error' => 0, 'mensaje' => '');
	if(trim($_POST['plaza'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la plaza');
	}
	elseif(trim($_POST['nombre'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita ingresar el nombre');
	}
	elseif(trim($_POST['fecha_ini'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la fecha de ingreso');
	}
	elseif(trim($_POST['puesto'])==''){
		$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar el puesto');
	}
	else{
		if(trim($_POST['rfc']) != '') {
			$res = mysql_query("SELECT cve FROM personal WHERE rfc = '{$_POST['rfc']}' AND cve!='{$_POST['reg']}'");
			if ($row = mysql_fetch_assoc($res)){
				$resultado = array('error' => 1, 'mensaje' => 'El RFC ya ha sido dado de alta');
			}
		}
		if($resultado['error']==0 && $_POST['reg'] > 0){
			$res = mysql_query("SELECT * FROM personal WHERE cve='{$_POST['reg']}'");
			$row = mysql_fetch_assoc($res);
			if($row['estatus'] != $_POST['estatus'] && $_POST['fecha_sta'] == ''){
				$resultado = array('error' => 1, 'mensaje' => 'Necesita seleccionar la fecha de cambio de estatus');	
			}
		}
	}
	if($resultado['error']==1){
		$resultado['mensaje'] = utf8_encode($resultado['mensaje']);
		echo json_encode($resultado);
	}
	else{

		
		if($_POST['reg']>0){
			if ($row['fecha_ini']=='0000-00-00') $row['fecha_ini']='';
			$fecha_estatus = "";
			if ($row['estatus'] != $_POST['estatus']) {
				$fecha_estatus = ", fecha_sta='{$_POST['fecha_sta']}'";
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Estatus', nuevo='{$_POST['estatus']}', anterior='{$row['estatus']}', usuario='{$_POST['cveusuario']}', obs='{$_POST['fecha_sta']}'");
			}

			if($row['plaza']!=$_POST['plaza']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Plaza', nuevo='{$_POST['plaza']}', anterior='{$row['plaza']}', usuario='{$_POST['cveusuario']}', obs=''");	
			}

			if($row['nombre']!=$_POST['nombre']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Nombre', nuevo='".addslashes($_POST['nombre'])."', anterior='".addslashes($row['nombre'])."', usuario='{$_POST['cveusuario']}', obs=''");	
			}

			if($row['fecha_ini']!=$_POST['fecha_ini']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Fecha Ingreso', nuevo='{$_POST['fecha_ini']}', anterior='{$row['fecha_ini']}', usuario='{$_POST['cveusuario']}', obs=''");	
			}

			if($row['puesto']!=$_POST['puesto']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='Puesto', nuevo='{$_POST['puesto']}', anterior='{$row['puesto']}', usuario='{$_POST['cveusuario']}', obs=''");	
			}

			if($row['rfc']!=$_POST['rfc']){
				mysql_query("INSERT historial SET menu='{$_POST['cvemenu']}', cveaux='{$_POST['reg']}', fecha=NOW(), dato='RFC', nuevo='{$_POST['rfc']}', anterior='{$row['rfc']}', usuario='{$_POST['cveusuario']}', obs=''");	
			}
			
			mysql_query("UPDATE personal SET plaza='{$_POST['plaza']}', estatus='{$_POST['estatus']}', fecha_ini='{$_POST['fecha_ini']}', puesto='{$_POST['puesto']}', rfc='{$_POST['rfc']}', nombre='".addslashes($_POST['nombre'])."'{$fecha_estatus} WHERE cve='{$_POST['reg']}'");
			$mensaje = 'Se actualizo exitosamente';
			$id = $_POST['reg'];
		}
		else{

			mysql_query("INSERT personal SET plaza='{$_POST['plaza']}', fecha_ini='{$_POST['fecha_ini']}', puesto='{$_POST['puesto']}', rfc='{$_POST['rfc']}', estatus=1, fecha_sta='{$_POST['fecha_ini']}', nombre='".addslashes($_POST['nombre'])."'") or die(mysql_error());
			$mensaje = 'Se registro exitosamente';
		}
		
		

		echo '<script>sweetAlert("Existoso","'.$mensaje.'", "success");$("#contenedorprincipal").html("");atcr("personal.php","",0,"0");</script>';
	}
	
}
?>