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
        	<div class="col-sm-12" align="center">
        		<button type="button" class="btn btn-primary" onClick="buscar();">
	            	Buscar
	        	</button>&nbsp;
	        	<button type="button" class="btn btn-success" onClick="atcr('captura_brinco.php','',1,0);">
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
				<th>Placa</th>
				<th>Fecha</th>
				<th>Tipo</th>
				<th>Usuario</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Placa</th>
				<th>Fecha</th>
				<th>Tipo</th>
				<th>Usuario</th>
			</tr>
		</tfoot>
	</table>
</div>
<script>
	var tablalistado = $('#dataTable').DataTable( {
        "ajax": {
        	url: 'captura_brinco.php',
        	type: "POST",
        	"data": {
        		"cmd": 10,
        		"cveusuario": $('#cveusuario').val(),
        		'busquedafechaini': $('#busquedafechaini').val(),
        		'busquedafechafin': $('#busquedafechafin').val(),
        		'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        	}
        },
        "processing": true,
        "serverSide": true,
        "bFilter": false,
        "order": [[1, "DESC"]],
        "columnDefs": [
        	{ className: "dt-head-center dt-body-center", "targets": 0 },
        	{ className: "dt-head-center dt-body-center", "targets": 1 },
        	{ className: "dt-head-center dt-body-center", "targets": 2 },
        	{ className: "dt-head-center dt-body-left", "targets": 3 }
		  ]
    } );


	function buscar(){
		tablalistado.ajax.data({
    		"cmd": 10,
    		"cveusuario": $('#cveusuario').val(),
        	'busquedafechaini': $('#busquedafechaini').val(),
        	'busquedafechafin': $('#busquedafechafin').val(),
        	'cveplaza': '<?php echo $_POST['cveplaza'];?>'
        });
        tablalistado.ajax.reload();
	}

</script>
<?php
}


if($_POST['cmd']==10){
	$columnas=array("a.placa", "a.fecha", "b.nombre", "c.usuario");

	$orderby = "";
	foreach($_POST['order'] as $dato){
		$orderby .= ",{$columnas[$dato['column']]} {$dato['dir']}";
	}

	if($orderby == ""){
		$orderby = " ORDER BY a.fecha DESC";
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


	$res = mysql_query("SELECT COUNT(a.cve) as registros FROM placas_brincos a{$where}");
	$registros = mysql_fetch_assoc($res);
	$resultado = array(
		'data' => array(),
		'draw'=> $_POST['draw'],
		'recordsTotal'=> $registros['registros'],
		'recordsFiltered'=> $registros['registros'],
	);
	$res = mysql_query("SELECT a.cve, a.placa, a.fecha, b.nombre as nomtipo, c.usuario FROM placas_brincos a INNER JOIN tipos_brinco b ON b.cve = a.tipo INNER JOIN usuarios c ON c.cve = a.usuario{$where}{$orderby} LIMIT {$_POST['start']},{$_POST['length']}");
	$tmonto = 0;
	while($row = mysql_fetch_assoc($res)){
		$resultado['data'][] = array(
			utf8_encode($row['placa']),
			mostrar_fechas(substr($row['fecha'],0,10)).' '.substr($row['fecha'],11),
			utf8_encode($row['nomtipo']),
			utf8_encode($row['usuario'])
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